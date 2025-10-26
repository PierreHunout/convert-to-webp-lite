<?php
/**
 * Tests for Converter class
 *
 * @package WpConvertToWebp\Tests
 */

namespace WpConvertToWebp\Tests\Unit\Utils;

use WpConvertToWebp\Tests\TestCase;
use WpConvertToWebp\Utils\Converter;
use Brain\Monkey\Functions as BrainMonkey;
use Mockery;
use RuntimeException;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class ConverterTest
 *
 * Tests for Converter class.
 */
class ConverterTest extends TestCase {

	/**
	 * Initializes the test environment before each test method.
	 *
	 * Sets up the parent test case environment and mocks common WordPress functions
	 * for testing Converter class functionality.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function set_up(): void {
		parent::set_up();

		// Mock common WordPress functions
		BrainMonkey\when( 'esc_html' )->returnArg();
		BrainMonkey\when( 'esc_attr' )->returnArg();
		BrainMonkey\when( 'wp_kses' )->returnArg();
		BrainMonkey\when( '__' )->returnArg();
		BrainMonkey\when( 'get_option' )->justReturn( 85 );
		BrainMonkey\when( 'get_post_mime_type' )->justReturn( 'image/jpeg' );
		BrainMonkey\when( 'wp_upload_dir' )->justReturn(
			[
				'basedir' => '/var/www/uploads',
				'baseurl' => 'http://example.com/uploads',
			]
		);
	}

	/**
	 * Tests that the constructor is private to enforce singleton pattern.
	 *
	 * Verifies that Converter::__construct is private, preventing direct
	 * instantiation of the Converter class.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Converter::__construct
	 * @return void
	 */
	public function test_constructor_is_private(): void {
		$this->assertMethodIsPrivate( Converter::class, '__construct' );
	}

	/**
	 * Tests that the clone method is private to enforce singleton pattern.
	 *
	 * Verifies that Converter::__clone is private, preventing cloning
	 * of the Converter singleton instance.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Converter::__clone
	 * @return void
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( Converter::class, '__clone' );
	}

	/**
	 * Tests that __wakeup throws RuntimeException to prevent unserialization.
	 *
	 * Verifies that Converter::__wakeup throws a RuntimeException with the message
	 * "Cannot unserialize a singleton." to prevent singleton deserialization.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Converter::__wakeup
	 * @return void
	 */
	public function test_wakeup_throws_exception(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot unserialize a singleton.' );

		$reflection = new ReflectionClass( Converter::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Tests that prepare method returns error for invalid attachment ID.
	 *
	 * Verifies that Converter::prepare returns an error message array when
	 * called with an invalid attachment ID (0 or negative).
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Converter::prepare
	 * @return void
	 */
	public function test_prepare_throws_exception_for_invalid_attachment_id(): void {
		$converter = $this->get_converter_instance();
		$method    = $this->get_method( 'prepare' );

		$result = $method->invoke( $converter, 0, [ 'file' => 'test.jpg' ] );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 'message', $result[0] );
		$this->assertArrayHasKey( 'classes', $result[0] );
		$this->assertContains( 'error', $result[0]['classes'] );
	}

	/**
	 * Tests that prepare method returns error for invalid metadata.
	 *
	 * Verifies that Converter::prepare returns an error message array when
	 * called with empty or invalid attachment metadata.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Converter::prepare
	 * @return void
	 */
	public function test_prepare_throws_exception_for_invalid_metadata(): void {
		$converter = $this->get_converter_instance();
		$method    = $this->get_method( 'prepare' );

		$result = $method->invoke( $converter, 1, [] );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 'message', $result[0] );
		$this->assertContains( 'error', $result[0]['classes'] );
	}

	/**
	 * Tests that prepare method returns error when filesystem initialization fails.
	 *
	 * Verifies that Converter::prepare returns an error message array when
	 * WP_Filesystem initialization fails.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Converter::prepare
	 * @return void
	 */
	public function test_prepare_returns_error_when_filesystem_fails(): void {
		BrainMonkey\expect( 'WP_Filesystem' )
			->once()
			->andReturn( false );

		$converter = $this->get_converter_instance();
		$method    = $this->get_method( 'prepare' );

		$result = $method->invoke( $converter, 1, [ 'file' => 'test.jpg' ] );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertContains( 'error', $result[0]['classes'] );
	}

	/**
	 * Tests that prepare method handles missing files gracefully.
	 *
	 * Verifies that Converter::prepare returns an error message array when
	 * the attachment file does not exist on the filesystem.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Converter::prepare
	 * @return void
	 */
	public function test_prepare_handles_missing_file(): void {
		$filesystem               = Mockery::mock( 'WP_Filesystem_Base' );
		$GLOBALS['wp_filesystem'] = $filesystem;

		BrainMonkey\expect( 'WP_Filesystem' )->once()->andReturn( true );
		BrainMonkey\expect( 'get_attached_file' )->once()->with( 123 )->andReturn( '/path/to/file.jpg' );

		$filesystem->shouldReceive( 'exists' )
			->with( '/path/to/file.jpg' )
			->andReturn( false );

		$converter = $this->get_converter_instance();
		$method    = $this->get_method( 'prepare' );

		$result = $method->invoke( $converter, 123, [ 'file' => 'test.jpg' ] );

		unset( $GLOBALS['wp_filesystem'] );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertContains( 'error', $result[0]['classes'] );
	}

	/**
	 * Tests that convert method returns error for invalid filepath.
	 *
	 * Verifies that Converter::convert returns an error message array when
	 * called with an empty or invalid file path.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Converter::convert
	 * @return void
	 */
	public function test_convert_throws_exception_for_invalid_filepath(): void {
		$converter = $this->get_converter_instance();
		$method    = $this->get_method( 'convert' );

		$result = $method->invoke( $converter, '' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'message', $result );
		$this->assertContains( 'error', $result['classes'] );
	}

	/**
	 * Tests that convert method returns error when filesystem initialization fails.
	 *
	 * Verifies that Converter::convert returns an error message array when
	 * WP_Filesystem initialization fails.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Converter::convert
	 * @return void
	 */
	public function test_convert_returns_error_when_filesystem_fails(): void {
		BrainMonkey\expect( 'WP_Filesystem' )
			->once()
			->andReturn( false );

		$converter = $this->get_converter_instance();
		$method    = $this->get_method( 'convert' );

		$result = $method->invoke( $converter, '/path/to/file.jpg' );

		$this->assertIsArray( $result );
		$this->assertContains( 'error', $result['classes'] );
	}

	/**
	 * Tests that convert method handles missing files gracefully.
	 *
	 * Verifies that Converter::convert returns an error message array when
	 * the source file does not exist on the filesystem.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Converter::convert
	 * @return void
	 */
	public function test_convert_handles_missing_file(): void {
		$filesystem               = Mockery::mock( 'WP_Filesystem_Base' );
		$GLOBALS['wp_filesystem'] = $filesystem;

		BrainMonkey\expect( 'WP_Filesystem' )->once()->andReturn( true );

		$filesystem->shouldReceive( 'exists' )
			->once()
			->with( '/path/to/file.jpg' )
			->andReturn( false );

		$converter = $this->get_converter_instance();
		$method    = $this->get_method( 'convert' );

		$result = $method->invoke( $converter, '/path/to/file.jpg' );

		unset( $GLOBALS['wp_filesystem'] );

		$this->assertIsArray( $result );
		$this->assertContains( 'error', $result['classes'] );
	}

	/**
	 * Tests that convert method rejects files that are already WebP.
	 *
	 * Verifies that Converter::convert returns an error message when
	 * attempting to convert a file that is already in WebP format.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Converter::convert
	 * @return void
	 */
	public function test_convert_handles_already_webp_file(): void {
		$filesystem               = Mockery::mock( 'WP_Filesystem_Base' );
		$GLOBALS['wp_filesystem'] = $filesystem;

		BrainMonkey\expect( 'WP_Filesystem' )->once()->andReturn( true );
		BrainMonkey\when( 'wp_upload_dir' )->justReturn(
			[
				'basedir' => '/var/www/uploads',
				'baseurl' => 'http://example.com/uploads',
			]
		);

		$filesystem->shouldReceive( 'exists' )
			->once()
			->with( '/path/to/file.webp' )
			->andReturn( true );

		$converter = $this->get_converter_instance();
		$method    = $this->get_method( 'convert' );

		$result = $method->invoke( $converter, '/path/to/file.webp' );

		unset( $GLOBALS['wp_filesystem'] );

		$this->assertIsArray( $result );
		$this->assertContains( 'error', $result['classes'] );
	}

	/**
	 * Tests that convert method handles invalid path structures.
	 *
	 * Verifies that Converter::convert returns an error message when
	 * given a path with no filename (directory only).
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Converter::convert
	 * @return void
	 */
	public function test_convert_handles_invalid_path_structure(): void {
		$filesystem               = Mockery::mock( 'WP_Filesystem_Base' );
		$GLOBALS['wp_filesystem'] = $filesystem;

		BrainMonkey\expect( 'WP_Filesystem' )->once()->andReturn( true );

		$filesystem->shouldReceive( 'exists' )
			->once()
			->with( '/' )
			->andReturn( true );

		$converter = $this->get_converter_instance();
		$method    = $this->get_method( 'convert' );

		// Path with no filename (only directory)
		$result = $method->invoke( $converter, '/' );

		unset( $GLOBALS['wp_filesystem'] );

		$this->assertIsArray( $result );
		$this->assertContains( 'error', $result['classes'] );
	}

	/**
	 * Get Converter instance using reflection
	 */
	private function get_converter_instance(): Converter {
		$reflection = new ReflectionClass( Converter::class );
		return $reflection->newInstanceWithoutConstructor();
	}

	/**
	 * Get method using reflection
	 */
	private function get_method( string $name ): ReflectionMethod {
		$method = new ReflectionMethod( Converter::class, $name );
		$method->setAccessible( true );
		return $method;
	}

	/**
	 * Performs cleanup operations after each test method completes.
	 *
	 * Tears down the test environment by cleaning up global filesystem mock
	 * and calling the parent tear_down method to clean up hooks and mocks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function tear_down(): void {
		// Clean up global filesystem mock if set
		if ( isset( $GLOBALS['wp_filesystem'] ) ) {
			unset( $GLOBALS['wp_filesystem'] );
		}

		parent::tear_down();
	}
}
