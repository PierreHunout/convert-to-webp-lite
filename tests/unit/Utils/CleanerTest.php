<?php
/**
 * Tests for Cleaner class
 *
 * @package WpConvertToWebp\Tests
 */

namespace WpConvertToWebp\Tests\Unit\Utils;

use WpConvertToWebp\Tests\TestCase;
use WpConvertToWebp\Utils\Cleaner;
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
	 * Setup before each test.
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
	}

	/**
	 * Test that constructor is private (singleton pattern).
	 */
	public function test_constructor_is_private(): void {
		$this->assertMethodIsPrivate( Cleaner::class, '__construct' );
	}

	/**
	 * Test that clone is private (singleton pattern).
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( Cleaner::class, '__clone' );
	}

	/**
	 * Test that __wakeup throws RuntimeException (singleton pattern).
	 */
	public function test_wakeup_throws_exception(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot unserialize a singleton.' );

		$reflection = new ReflectionClass( Cleaner::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Test prepare throws exception for invalid attachment ID
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
	 * Test prepare throws exception for invalid metadata
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
	 * Test prepare returns error when filesystem fails
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

		$cleaner = $this->get_cleaner_instance();
		$method  = $this->get_method( 'prepare' );

		$result = $method->invoke( $cleaner, 123, [ 'file' => 'test.jpg' ] );

		unset( $GLOBALS['wp_filesystem'] );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertContains( 'error', $result[0]['classes'] );
	}

	/**
	 * Test prepare handles file not writable
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
	 * Test delete throws exception for invalid filepath
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
	 * Test delete returns error when filesystem fails
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
	 * Test delete handles missing file
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
	 * Test delete handles file that is already WebP
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
	 * Test delete handles invalid path structure
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
	 * Test delete handles WebP file not exists
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
	 * Test delete handles WebP file not writable
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
	 * Test delete handles deletion failure
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
	 * Test delete successfully deletes WebP file
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
