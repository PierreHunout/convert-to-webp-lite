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
	 * Setup before each test.
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
		BrainMonkey\when( 'wp_upload_dir' )->justReturn( [ 
			'basedir' => '/var/www/uploads',
			'baseurl' => 'http://example.com/uploads'
		] );
	}

	/**
	 * Test that constructor is private (singleton pattern).
	 */
	public function test_constructor_is_private(): void {
		$this->assertMethodIsPrivate( Converter::class, '__construct' );
	}

	/**
	 * Test that clone is private (singleton pattern).
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( Converter::class, '__clone' );
	}

	/**
	 * Test that __wakeup throws RuntimeException (singleton pattern).
	 */
	public function test_wakeup_throws_exception(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot unserialize a singleton.' );

		$reflection = new ReflectionClass( Converter::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Test prepare throws exception for invalid attachment ID
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
	 * Test prepare throws exception for invalid metadata
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
	 * Test prepare returns early when filesystem fails
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
	 * Test prepare handles missing file
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
	 * Test convert throws exception for invalid filepath
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
	 * Test convert returns error when filesystem fails
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
	 * Test convert handles missing file
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
	 * Test convert handles file that is already WebP
	 */
	public function test_convert_handles_already_webp_file(): void {
		$filesystem               = Mockery::mock( 'WP_Filesystem_Base' );
		$GLOBALS['wp_filesystem'] = $filesystem;

		BrainMonkey\expect( 'WP_Filesystem' )->once()->andReturn( true );
		BrainMonkey\when( 'wp_upload_dir' )->justReturn( [ 'basedir' => '/var/www/uploads', 'baseurl' => 'http://example.com/uploads' ] );

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
	 * Test convert handles invalid path structure
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
	 * Cleanup after each test.
	 */
	protected function tear_down(): void {
		// Clean up global filesystem mock if set
		if ( isset( $GLOBALS['wp_filesystem'] ) ) {
			unset( $GLOBALS['wp_filesystem'] );
		}

		parent::tear_down();
	}
}
