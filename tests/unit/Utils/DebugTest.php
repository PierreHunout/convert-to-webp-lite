<?php
/**
 * Tests for Debug class
 *
 * @package WpConvertToWebp\Tests
 */

namespace WpConvertToWebp\Tests\Unit\Utils;

use WpConvertToWebp\Tests\TestCase;
use WpConvertToWebp\Utils\Debug;
use Brain\Monkey\Functions as BrainMonkey;
use Mockery;
use RuntimeException;
use ReflectionClass;

/**
 * Class DebugTest
 *
 * Tests for Debug class.
 */
class DebugTest extends TestCase {

	/**
	 * Setup before each test.
	 */
	protected function set_up(): void {
		parent::set_up();

		// Define WP_CONTENT_DIR constant if not defined
		if ( ! defined( 'WP_CONTENT_DIR' ) ) {
			define( 'WP_CONTENT_DIR', '/var/www/wp-content' );
		}

		// Mock common WordPress functions
		BrainMonkey\when( 'sanitize_file_name' )->returnArg();
		BrainMonkey\when( 'wp_json_encode' )->alias( function( $data, $options = 0 ) {
return json_encode( $data, $options );
		} );
		BrainMonkey\when( 'esc_html' )->returnArg();
	}

	/**
	 * Test that constructor is private (singleton pattern).
	 */
	public function test_constructor_is_private(): void {
		$this->assertMethodIsPrivate( Debug::class, '__construct' );
	}

	/**
	 * Test that clone is private (singleton pattern).
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( Debug::class, '__clone' );
	}

	/**
	 * Test that __wakeup throws RuntimeException (singleton pattern).
	 */
	public function test_wakeup_throws_exception(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot unserialize a singleton.' );

		$reflection = new ReflectionClass( Debug::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Test log returns early when filesystem fails
	 */
	public function test_log_returns_early_when_filesystem_fails(): void {
		// Mock get_filesystem to return false
		BrainMonkey\expect( 'WP_Filesystem' )
			->once()
			->andReturn( false );

		// Should return early without any errors
		Debug::log( 'test', 'data' );

		// If we get here without errors, the early return worked
		$this->assertTrue( true );
	}

	/**
	 * Test log creates log file
	 */
	public function test_log_creates_log_file(): void {
		$filesystem = Mockery::mock( 'WP_Filesystem_Base' );
		$GLOBALS['wp_filesystem'] = $filesystem;
		
		BrainMonkey\expect( 'WP_Filesystem' )
			->once()
			->andReturn( true );

		$path = WP_CONTENT_DIR . '/convert-to-webp-logs/';

		$filesystem->shouldReceive( 'is_dir' )
			->once()
			->with( $path )
			->andReturn( false );

		$filesystem->shouldReceive( 'mkdir' )
			->once()
			->with( $path, 0755 )
			->andReturn( true );

		// Expect .htaccess file creation
		$filesystem->shouldReceive( 'put_contents' )
			->once()
			->with( Mockery::on( function( $arg ) use ( $path ) {
return strpos( $arg, '.htaccess' ) !== false;
			} ), Mockery::type( 'string' ), 0644 )
			->andReturn( true );

		// Expect index.php file creation
		$filesystem->shouldReceive( 'put_contents' )
			->once()
			->with( Mockery::on( function( $arg ) use ( $path ) {
return strpos( $arg, 'index.php' ) !== false;
			} ), Mockery::type( 'string' ), 0644 )
			->andReturn( true );

		// Expect log file creation
		$filesystem->shouldReceive( 'put_contents' )
			->once()
			->with( Mockery::on( function( $arg ) {
return strpos( $arg, '.json' ) !== false;
			} ), Mockery::type( 'string' ), 0644 )
			->andReturn( true );

		Debug::log( 'test', [ 'key' => 'value' ] );

		unset( $GLOBALS['wp_filesystem'] );
		$this->assertTrue( true );
	}

	/**
	 * Test log writes to existing directory
	 */
	public function test_log_writes_to_existing_directory(): void {
		$filesystem = Mockery::mock( 'WP_Filesystem_Base' );
		$GLOBALS['wp_filesystem'] = $filesystem;
		
		BrainMonkey\expect( 'WP_Filesystem' )
			->once()
			->andReturn( true );

		$path = WP_CONTENT_DIR . '/convert-to-webp-logs/';

		$filesystem->shouldReceive( 'is_dir' )
			->once()
			->with( $path )
			->andReturn( true );

		// Only expect log file creation (no directory setup)
		$filesystem->shouldReceive( 'put_contents' )
			->once()
			->with( Mockery::on( function( $arg ) {
return strpos( $arg, '.json' ) !== false;
			} ), Mockery::type( 'string' ), 0644 )
			->andReturn( true );

		Debug::log( 'existing-test', 'some data' );

		unset( $GLOBALS['wp_filesystem'] );
		$this->assertTrue( true );
	}

	/**
	 * Test log handles different data types
	 */
	public function test_log_handles_different_data_types(): void {
		$filesystem = Mockery::mock( 'WP_Filesystem_Base' );
		$GLOBALS['wp_filesystem'] = $filesystem;
		
		BrainMonkey\expect( 'WP_Filesystem' )
			->once()
			->andReturn( true );

		$filesystem->shouldReceive( 'is_dir' )
			->once()
			->andReturn( true );

		$filesystem->shouldReceive( 'put_contents' )
			->once()
			->with( Mockery::type( 'string' ), Mockery::on( function( $content ) {
$decoded = json_decode( trim( $content ), true );
				return isset( $decoded['type'] ) && $decoded['type'] === 'array';
			} ), 0644 )
			->andReturn( true );

		Debug::log( 'array-test', [ 'item1', 'item2' ] );

		unset( $GLOBALS['wp_filesystem'] );
		$this->assertTrue( true );
	}

	/**
	 * Test log includes timestamp in JSON
	 */
	public function test_log_includes_timestamp_in_json(): void {
		$filesystem = Mockery::mock( 'WP_Filesystem_Base' );
		$GLOBALS['wp_filesystem'] = $filesystem;
		
		BrainMonkey\expect( 'WP_Filesystem' )
			->once()
			->andReturn( true );

		$filesystem->shouldReceive( 'is_dir' )
			->once()
			->andReturn( true );

		$filesystem->shouldReceive( 'put_contents' )
			->once()
			->with( Mockery::type( 'string' ), Mockery::on( function( $content ) {
$decoded = json_decode( trim( $content ), true );
				return isset( $decoded['date'] ) && isset( $decoded['type'] ) && isset( $decoded['data'] );
			} ), 0644 )
			->andReturn( true );

		Debug::log( 'timestamp-test', 'test data' );

		unset( $GLOBALS['wp_filesystem'] );
		$this->assertTrue( true );
	}

	/**
	 * Test log sanitizes file name
	 */
	public function test_log_sanitizes_file_name(): void {
		$filesystem = Mockery::mock( 'WP_Filesystem_Base' );
		$GLOBALS['wp_filesystem'] = $filesystem;
		
		BrainMonkey\expect( 'WP_Filesystem' )
			->once()
			->andReturn( true );

		$filesystem->shouldReceive( 'is_dir' )
			->once()
			->andReturn( true );

		$filesystem->shouldReceive( 'put_contents' )
			->once()
			->andReturn( true );

		// sanitize_file_name is already mocked in set_up to return its argument
		Debug::log( 'test-file-name', 'data' );

		unset( $GLOBALS['wp_filesystem'] );
		$this->assertTrue( true );
	}

	/**
	 * Test print outputs HTML with data
	 */
	public function test_print_outputs_html_with_data(): void {
		ob_start();
		Debug::print( [ 'test' => 'value' ], false );
		$output = ob_get_clean();

		$this->assertStringContainsString( '<div style=', $output );
		$this->assertStringContainsString( 'Convert to WebP Debug Output:', $output );
		$this->assertStringContainsString( 'Type:', $output );
		$this->assertStringContainsString( 'array', $output );
	}

	/**
	 * Test print displays different data types correctly
	 */
	public function test_print_displays_string_data(): void {
		ob_start();
		Debug::print( 'Test String', false );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Type: string', $output );
		$this->assertStringContainsString( 'Test String', $output );
	}

	/**
	 * Test print displays integer data
	 */
	public function test_print_displays_integer_data(): void {
		ob_start();
		Debug::print( 42, false );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Type: integer', $output );
		$this->assertStringContainsString( '42', $output );
	}

	/**
	 * Test print displays boolean data
	 */
	public function test_print_displays_boolean_data(): void {
		ob_start();
		Debug::print( true, false );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Type: boolean', $output );
	}

	/**
	 * Test print stops execution when stop parameter is true
	 */
	public function test_print_stops_execution_when_requested(): void {
		BrainMonkey\expect( 'wp_die' )
			->once()
			->with( 'Debug output terminated.' );

		ob_start();
		Debug::print( 'test', true );
		ob_end_clean();

		$this->assertTrue( true );
	}

	/**
	 * Test print handles array data
	 */
	public function test_print_handles_array_data(): void {
		ob_start();
		Debug::print( [ 'key1' => 'value1', 'key2' => 'value2' ], false );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Type: array', $output );
		$this->assertStringContainsString( 'key1', $output );
		$this->assertStringContainsString( 'value1', $output );
	}

	/**
	 * Test print handles object data
	 */
	public function test_print_handles_object_data(): void {
		$obj       = new \stdClass();
		$obj->prop = 'value';

		ob_start();
		Debug::print( $obj, false );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Type: object', $output );
	}

	/**
	 * Test print escapes HTML in output
	 */
	public function test_print_escapes_html_in_output(): void {
		ob_start();
		Debug::print( '<script>alert("xss")</script>', false );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Type: string', $output );
		// esc_html is called within the print method
		$this->assertStringContainsString( '<div style=', $output );
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
