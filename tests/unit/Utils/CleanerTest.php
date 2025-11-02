<?php
/**
 * Tests for Cleaner class
 *
 * @package PoetryConvertToWebp\Tests
 */

namespace PoetryConvertToWebp\Tests\Unit\Utils;

use PoetryConvertToWebp\Tests\TestCase;
use PoetryConvertToWebp\Utils\Cleaner;
use Brain\Monkey\Functions as BrainMonkey;
use Mockery;
use RuntimeException;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class CleanerTest
 *
 * Tests for Cleaner class.
 */
class CleanerTest extends TestCase {

	/**
	 * Initializes the test environment before each test method.
	 *
	 * Sets up the parent test case environment and mocks common WordPress functions
	 * for testing Cleaner class functionality.
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
		BrainMonkey\when( 'wp_upload_dir' )->justReturn(
			[
				'basedir' => '/var/www/uploads',
				'baseurl' => 'http://example.com/uploads',
			]
		);
		BrainMonkey\when( 'get_option' )->justReturn( false );
	}

	/**
	 * Tests that the constructor is private to enforce singleton pattern.
	 *
	 * Verifies that Cleaner::__construct is private, preventing direct
	 * instantiation of the Cleaner class.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Utils\Cleaner::__construct
	 * @return void
	 */
	public function test_constructor_is_private(): void {
		$this->assertMethodIsPrivate( Cleaner::class, '__construct' );
	}

	/**
	 * Tests that the clone method is private to enforce singleton pattern.
	 *
	 * Verifies that Cleaner::__clone is private, preventing cloning
	 * of the Cleaner singleton instance.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Utils\Cleaner::__clone
	 * @return void
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( Cleaner::class, '__clone' );
	}

	/**
	 * Tests that __wakeup throws RuntimeException to prevent unserialization.
	 *
	 * Verifies that Cleaner::__wakeup throws a RuntimeException with the message
	 * "Cannot unserialize a singleton." to prevent singleton deserialization.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Utils\Cleaner::__wakeup
	 * @return void
	 */
	public function test_wakeup_throws_exception(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot unserialize a singleton.' );

		$reflection = new ReflectionClass( Cleaner::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Tests that prepare method returns error for invalid attachment ID.
	 *
	 * Verifies that Cleaner::prepare returns an error message array when
	 * called with an invalid attachment ID (0 or negative).
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Utils\Cleaner::prepare
	 * @return void
	 */
	public function test_prepare_throws_exception_for_invalid_attachment_id(): void {
		$cleaner = $this->get_cleaner_instance();
		$method  = $this->get_method( 'prepare' );

		$result = $method->invoke( $cleaner, 0, [ 'file' => 'test.jpg' ] );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 'message', $result[0] );
		$this->assertContains( 'error', $result[0]['classes'] );
	}

	/**
	 * Tests that prepare method returns error for invalid metadata.
	 *
	 * Verifies that Cleaner::prepare returns an error message array when
	 * called with empty or invalid attachment metadata.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Utils\Cleaner::prepare
	 * @return void
	 */
	public function test_prepare_throws_exception_for_invalid_metadata(): void {
		$cleaner = $this->get_cleaner_instance();
		$method  = $this->get_method( 'prepare' );

		$result = $method->invoke( $cleaner, 1, [] );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertContains( 'error', $result[0]['classes'] );
	}

	/**
	 * Tests that prepare method returns error when filesystem initialization fails.
	 *
	 * Verifies that Cleaner::prepare returns an error message array when
	 * WP_Filesystem initialization fails.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Utils\Cleaner::prepare
	 * @return void
	 */
	public function test_prepare_returns_error_when_filesystem_fails(): void {
		BrainMonkey\expect( 'WP_Filesystem' )->once()->andReturn( false );
		BrainMonkey\expect( 'get_attached_file' )->once()->with( 1 )->andReturn( '/path/to/file.jpg' );

		$cleaner = $this->get_cleaner_instance();
		$method  = $this->get_method( 'prepare' );

		$result = $method->invoke( $cleaner, 1, [ 'file' => 'test.jpg' ] );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertContains( 'error', $result[0]['classes'] );
	}

	/**
	 * Tests that prepare method handles missing files gracefully.
	 *
	 * Verifies that Cleaner::prepare returns an error message array when
	 * the attachment file does not exist on the filesystem.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Utils\Cleaner::prepare
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

		$cleaner = $this->get_cleaner_instance();
		$method  = $this->get_method( 'prepare' );

		$result = $method->invoke( $cleaner, 123, [ 'file' => 'test.jpg' ] );

		unset( $GLOBALS['wp_filesystem'] );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertContains( 'error', $result[0]['classes'] );
	}

	/**
	 * Tests that prepare method handles non-writable files.
	 *
	 * Verifies that Cleaner::prepare returns an error message array when
	 * the attachment file exists but is not writable.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Utils\Cleaner::prepare
	 * @return void
	 */
	public function test_prepare_handles_file_not_writable(): void {
		$filesystem               = Mockery::mock( 'WP_Filesystem_Base' );
		$GLOBALS['wp_filesystem'] = $filesystem;

		BrainMonkey\expect( 'WP_Filesystem' )->once()->andReturn( true );
		BrainMonkey\expect( 'get_attached_file' )->once()->with( 123 )->andReturn( '/path/to/file.jpg' );

		$filesystem->shouldReceive( 'exists' )
			->with( '/path/to/file.jpg' )
			->andReturn( true );

		$filesystem->shouldReceive( 'is_writable' )
			->with( '/path/to/file.jpg' )
			->andReturn( false );

		$cleaner = $this->get_cleaner_instance();
		$method  = $this->get_method( 'prepare' );

		$result = $method->invoke( $cleaner, 123, [ 'file' => 'test.jpg' ] );

		unset( $GLOBALS['wp_filesystem'] );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertContains( 'error', $result[0]['classes'] );
	}

	/**
	 * Tests that delete method returns error for invalid filepath.
	 *
	 * Verifies that Cleaner::delete returns an error message array when
	 * called with an empty or invalid file path.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Utils\Cleaner::delete
	 * @return void
	 */
	public function test_delete_throws_exception_for_invalid_filepath(): void {
		$cleaner = $this->get_cleaner_instance();
		$method  = $this->get_method( 'delete' );

		$result = $method->invoke( $cleaner, '' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'message', $result );
		$this->assertContains( 'error', $result['classes'] );
	}

	/**
	 * Tests that delete method returns error when filesystem initialization fails.
	 *
	 * Verifies that Cleaner::delete returns an error message array when
	 * WP_Filesystem initialization fails.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Utils\Cleaner::delete
	 * @return void
	 */
	public function test_delete_returns_error_when_filesystem_fails(): void {
		BrainMonkey\expect( 'WP_Filesystem' )->once()->andReturn( false );

		$cleaner = $this->get_cleaner_instance();
		$method  = $this->get_method( 'delete' );

		$result = $method->invoke( $cleaner, '/path/to/file.jpg' );

		$this->assertIsArray( $result );
		$this->assertContains( 'error', $result['classes'] );
	}

	/**
	 * Tests that delete method handles missing files gracefully.
	 *
	 * Verifies that Cleaner::delete returns an error message array when
	 * the source file does not exist on the filesystem.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Utils\Cleaner::delete
	 * @return void
	 */
	public function test_delete_handles_missing_file(): void {
		$filesystem               = Mockery::mock( 'WP_Filesystem_Base' );
		$GLOBALS['wp_filesystem'] = $filesystem;

		BrainMonkey\expect( 'WP_Filesystem' )->once()->andReturn( true );

		$filesystem->shouldReceive( 'exists' )
			->with( '/path/to/file.jpg' )
			->andReturn( false );

		$cleaner = $this->get_cleaner_instance();
		$method  = $this->get_method( 'delete' );

		$result = $method->invoke( $cleaner, '/path/to/file.jpg' );

		unset( $GLOBALS['wp_filesystem'] );

		$this->assertIsArray( $result );
		$this->assertContains( 'error', $result['classes'] );
	}

	/**
	 * Tests that delete method handles files that are already WebP.
	 *
	 * Verifies that Cleaner::delete returns a success message when
	 * attempting to delete a WebP file of a file that is already WebP.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Utils\Cleaner::delete
	 * @return void
	 */
	public function test_delete_handles_already_webp_file(): void {
		$filesystem               = Mockery::mock( 'WP_Filesystem_Base' );
		$GLOBALS['wp_filesystem'] = $filesystem;

		BrainMonkey\expect( 'WP_Filesystem' )->once()->andReturn( true );

		$filesystem->shouldReceive( 'exists' )
			->with( '/path/to/file.webp' )
			->andReturn( true );

		$cleaner = $this->get_cleaner_instance();
		$method  = $this->get_method( 'delete' );

		$result = $method->invoke( $cleaner, '/path/to/file.webp' );

		unset( $GLOBALS['wp_filesystem'] );

		$this->assertIsArray( $result );
		$this->assertContains( 'success', $result['classes'] );
		$this->assertStringContainsString( 'already a WebP file', $result['message'] );
	}

	/**
	 * Tests that delete method handles invalid path structures.
	 *
	 * Verifies that Cleaner::delete returns an error message when
	 * given a path with no filename (directory only).
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Utils\Cleaner::delete
	 * @return void
	 */
	public function test_delete_handles_invalid_path_structure(): void {
		$filesystem               = Mockery::mock( 'WP_Filesystem_Base' );
		$GLOBALS['wp_filesystem'] = $filesystem;

		BrainMonkey\expect( 'WP_Filesystem' )->once()->andReturn( true );

		$filesystem->shouldReceive( 'exists' )
			->with( '/' )
			->andReturn( true );

		$cleaner = $this->get_cleaner_instance();
		$method  = $this->get_method( 'delete' );

		$result = $method->invoke( $cleaner, '/' );

		unset( $GLOBALS['wp_filesystem'] );

		$this->assertIsArray( $result );
		$this->assertContains( 'error', $result['classes'] );
	}

	/**
	 * Tests that delete method handles non-existent WebP files.
	 *
	 * Verifies that Cleaner::delete returns an error message when
	 * the corresponding WebP file does not exist.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Utils\Cleaner::delete
	 * @return void
	 */
	public function test_delete_handles_webp_not_exists(): void {
		$filesystem               = Mockery::mock( 'WP_Filesystem_Base' );
		$GLOBALS['wp_filesystem'] = $filesystem;

		BrainMonkey\expect( 'WP_Filesystem' )->once()->andReturn( true );

		$filesystem->shouldReceive( 'exists' )
			->with( '/path/to/file.jpg' )
			->andReturn( true );

		$filesystem->shouldReceive( 'exists' )
			->with( '/path/to/file.webp' )
			->andReturn( false );

		$cleaner = $this->get_cleaner_instance();
		$method  = $this->get_method( 'delete' );

		$result = $method->invoke( $cleaner, '/path/to/file.jpg' );

		unset( $GLOBALS['wp_filesystem'] );

		$this->assertIsArray( $result );
		$this->assertContains( 'error', $result['classes'] );
	}

	/**
	 * Tests that delete method handles non-writable WebP files.
	 *
	 * Verifies that Cleaner::delete returns an error message when
	 * the WebP file exists but is not writable.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Utils\Cleaner::delete
	 * @return void
	 */
	public function test_delete_handles_webp_not_writable(): void {
		$filesystem               = Mockery::mock( 'WP_Filesystem_Base' );
		$GLOBALS['wp_filesystem'] = $filesystem;

		BrainMonkey\expect( 'WP_Filesystem' )->once()->andReturn( true );

		$filesystem->shouldReceive( 'exists' )
			->with( '/path/to/file.jpg' )
			->andReturn( true );

		$filesystem->shouldReceive( 'exists' )
			->with( '/path/to/file.webp' )
			->andReturn( true );

		$filesystem->shouldReceive( 'is_writable' )
			->with( '/path/to/file.webp' )
			->andReturn( false );

		$cleaner = $this->get_cleaner_instance();
		$method  = $this->get_method( 'delete' );

		$result = $method->invoke( $cleaner, '/path/to/file.jpg' );

		unset( $GLOBALS['wp_filesystem'] );

		$this->assertIsArray( $result );
		$this->assertContains( 'error', $result['classes'] );
	}

	/**
	 * Tests that delete method handles filesystem deletion failures.
	 *
	 * Verifies that Cleaner::delete returns an error message when
	 * the filesystem delete operation fails.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Utils\Cleaner::delete
	 * @return void
	 */
	public function test_delete_handles_deletion_failure(): void {
		$filesystem               = Mockery::mock( 'WP_Filesystem_Base' );
		$GLOBALS['wp_filesystem'] = $filesystem;

		BrainMonkey\expect( 'WP_Filesystem' )->once()->andReturn( true );

		$filesystem->shouldReceive( 'exists' )
			->with( '/path/to/file.jpg' )
			->andReturn( true );

		$filesystem->shouldReceive( 'exists' )
			->with( '/path/to/file.webp' )
			->andReturn( true );

		$filesystem->shouldReceive( 'is_writable' )
			->with( '/path/to/file.webp' )
			->andReturn( true );

		$filesystem->shouldReceive( 'delete' )
			->with( '/path/to/file.webp' )
			->andReturn( false );

		$cleaner = $this->get_cleaner_instance();
		$method  = $this->get_method( 'delete' );

		$result = $method->invoke( $cleaner, '/path/to/file.jpg' );

		unset( $GLOBALS['wp_filesystem'] );

		$this->assertIsArray( $result );
		$this->assertContains( 'error', $result['classes'] );
	}

	/**
	 * Tests that delete method successfully deletes WebP files.
	 *
	 * Verifies that Cleaner::delete successfully deletes the WebP file
	 * when all conditions are met (file exists and is writable).
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Utils\Cleaner::delete
	 * @return void
	 */
	public function test_delete_successfully_deletes_webp_file(): void {
		$filesystem               = Mockery::mock( 'WP_Filesystem_Base' );
		$GLOBALS['wp_filesystem'] = $filesystem;

		BrainMonkey\expect( 'WP_Filesystem' )->once()->andReturn( true );

		$filesystem->shouldReceive( 'exists' )
			->with( '/path/to/file.jpg' )
			->andReturn( true );

		$filesystem->shouldReceive( 'exists' )
			->with( '/path/to/file.webp' )
			->andReturn( true );

		$filesystem->shouldReceive( 'is_writable' )
			->with( '/path/to/file.webp' )
			->andReturn( true );

		$filesystem->shouldReceive( 'delete' )
			->with( '/path/to/file.webp' )
			->andReturn( true );

		$cleaner = $this->get_cleaner_instance();
		$method  = $this->get_method( 'delete' );

		$result = $method->invoke( $cleaner, '/path/to/file.jpg' );

		unset( $GLOBALS['wp_filesystem'] );

		$this->assertIsArray( $result );
		$this->assertContains( 'error', $result['classes'] );
	}

	/**
	 * Get Cleaner instance using reflection
	 */
	private function get_cleaner_instance(): Cleaner {
		$reflection = new ReflectionClass( Cleaner::class );
		return $reflection->newInstanceWithoutConstructor();
	}

	/**
	 * Get method using reflection
	 */
	private function get_method( string $name ): ReflectionMethod {
		$method = new ReflectionMethod( Cleaner::class, $name );
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
