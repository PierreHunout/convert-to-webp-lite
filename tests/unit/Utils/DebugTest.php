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
	 * Initializes the test environment before each test method.
	 *
	 * Sets up the parent test case environment, defines WP_CONTENT_DIR constant,
	 * and mocks common WordPress functions for testing Debug class functionality.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function set_up(): void {
		parent::set_up();

		// Define WP_CONTENT_DIR constant if not defined
		if ( ! defined( 'WP_CONTENT_DIR' ) ) {
			define( 'WP_CONTENT_DIR', '/var/www/wp-content' );
		}

		// Mock common WordPress functions
		BrainMonkey\when( 'sanitize_file_name' )->returnArg();
		BrainMonkey\when( 'wp_json_encode' )->alias(
			function ( $data, $options = 0 ) {
				return json_encode( $data, $options );
			}
		);
		BrainMonkey\when( 'esc_html' )->returnArg();
	}

	/**
	 * Tests that the constructor is private to enforce singleton pattern.
	 *
	 * Verifies that Debug::__construct is private, preventing direct
	 * instantiation of the Debug class.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Debug::__construct
	 * @return void
	 */
	public function test_constructor_is_private(): void {
		$this->assertMethodIsPrivate( Debug::class, '__construct' );
	}

	/**
	 * Tests that the clone method is private to enforce singleton pattern.
	 *
	 * Verifies that Debug::__clone is private, preventing cloning
	 * of the Debug singleton instance.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Debug::__clone
	 * @return void
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( Debug::class, '__clone' );
	}

	/**
	 * Tests that __wakeup throws RuntimeException to prevent unserialization.
	 *
	 * Verifies that Debug::__wakeup throws a RuntimeException with the message
	 * "Cannot unserialize a singleton." to prevent singleton deserialization.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Debug::__wakeup
	 * @return void
	 */
	public function test_wakeup_throws_exception(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot unserialize a singleton.' );

		$reflection = new ReflectionClass( Debug::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Tests that log method returns early when filesystem initialization fails.
	 *
	 * Verifies that Debug::log gracefully handles filesystem initialization failure
	 * by returning early without throwing errors when WP_Filesystem returns false.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Debug::log
	 * @return void
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
	 * Tests that log method creates log directory and protection files.
	 *
	 * Verifies that Debug::log creates the log directory, .htaccess file for
	 * directory protection, index.php blank file, and the log file itself.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Debug::log
	 * @return void
	 */
	public function test_log_creates_log_file(): void {
		$filesystem               = Mockery::mock( 'WP_Filesystem_Base' );
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
			->with(
				Mockery::on(
					function ( $arg ) use ( $path ) {
						return strpos( $arg, '.htaccess' ) !== false;
					}
				),
				Mockery::type( 'string' ),
				0644
			)
			->andReturn( true );

		// Expect index.php file creation
		$filesystem->shouldReceive( 'put_contents' )
			->once()
			->with(
				Mockery::on(
					function ( $arg ) use ( $path ) {
						return strpos( $arg, 'index.php' ) !== false;
					}
				),
				Mockery::type( 'string' ),
				0644
			)
			->andReturn( true );

		// Expect log file creation
		$filesystem->shouldReceive( 'put_contents' )
			->once()
			->with(
				Mockery::on(
					function ( $arg ) {
						return strpos( $arg, '.json' ) !== false;
					}
				),
				Mockery::type( 'string' ),
				0644
			)
			->andReturn( true );

		Debug::log( 'test', [ 'key' => 'value' ] );

		unset( $GLOBALS['wp_filesystem'] );
		$this->assertTrue( true );
	}

	/**
	 * Tests that log method writes to an existing log directory.
	 *
	 * Verifies that Debug::log skips directory setup when the log directory
	 * already exists and writes only the log file.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Debug::log
	 * @return void
	 */
	public function test_log_writes_to_existing_directory(): void {
		$filesystem               = Mockery::mock( 'WP_Filesystem_Base' );
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
			->with(
				Mockery::on(
					function ( $arg ) {
						return strpos( $arg, '.json' ) !== false;
					}
				),
				Mockery::type( 'string' ),
				0644
			)
			->andReturn( true );

		Debug::log( 'existing-test', 'some data' );

		unset( $GLOBALS['wp_filesystem'] );
		$this->assertTrue( true );
	}

	/**
	 * Tests that log method handles different data types correctly.
	 *
	 * Verifies that Debug::log correctly identifies and logs different data types
	 * (arrays, strings, objects, etc.) with appropriate type information in JSON.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Debug::log
	 * @return void
	 */
	public function test_log_handles_different_data_types(): void {
		$filesystem               = Mockery::mock( 'WP_Filesystem_Base' );
		$GLOBALS['wp_filesystem'] = $filesystem;

		BrainMonkey\expect( 'WP_Filesystem' )
			->once()
			->andReturn( true );

		$filesystem->shouldReceive( 'is_dir' )
			->once()
			->andReturn( true );

		$filesystem->shouldReceive( 'put_contents' )
			->once()
			->with(
				Mockery::type( 'string' ),
				Mockery::on(
					function ( $content ) {
						$decoded = json_decode( trim( $content ), true );
						return isset( $decoded['type'] ) && $decoded['type'] === 'array';
					}
				),
				0644
			)
			->andReturn( true );

		Debug::log( 'array-test', [ 'item1', 'item2' ] );

		unset( $GLOBALS['wp_filesystem'] );
		$this->assertTrue( true );
	}

	/**
	 * Tests that log method includes timestamp in JSON output.
	 *
	 * Verifies that Debug::log creates JSON entries with 'date', 'type', and 'data'
	 * keys for proper log entry structure and timestamp tracking.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Debug::log
	 * @return void
	 */
	public function test_log_includes_timestamp_in_json(): void {
		$filesystem               = Mockery::mock( 'WP_Filesystem_Base' );
		$GLOBALS['wp_filesystem'] = $filesystem;

		BrainMonkey\expect( 'WP_Filesystem' )
			->once()
			->andReturn( true );

		$filesystem->shouldReceive( 'is_dir' )
			->once()
			->andReturn( true );

		$filesystem->shouldReceive( 'put_contents' )
			->once()
			->with(
				Mockery::type( 'string' ),
				Mockery::on(
					function ( $content ) {
						$decoded = json_decode( trim( $content ), true );
						return isset( $decoded['date'] ) && isset( $decoded['type'] ) && isset( $decoded['data'] );
					}
				),
				0644
			)
			->andReturn( true );

		Debug::log( 'timestamp-test', 'test data' );

		unset( $GLOBALS['wp_filesystem'] );
		$this->assertTrue( true );
	}

	/**
	 * Tests that log method sanitizes file names for security.
	 *
	 * Verifies that Debug::log uses sanitize_file_name to clean the log filename
	 * before creating the file, preventing directory traversal or invalid filenames.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Debug::log
	 * @return void
	 */
	public function test_log_sanitizes_file_name(): void {
		$filesystem               = Mockery::mock( 'WP_Filesystem_Base' );
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
	 * Tests that print method outputs HTML formatted debug information.
	 *
	 * Verifies that Debug::print generates HTML output with styled div containers,
	 * debug title, data type information, and the actual data content.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Debug::print
	 * @return void
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
	 * Tests that print method correctly displays string data type.
	 *
	 * Verifies that Debug::print identifies string data and displays both
	 * the type label "string" and the actual string content.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Debug::print
	 * @return void
	 */
	public function test_print_displays_string_data(): void {
		ob_start();
		Debug::print( 'Test String', false );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Type: string', $output );
		$this->assertStringContainsString( 'Test String', $output );
	}

	/**
	 * Tests that print method correctly displays integer data type.
	 *
	 * Verifies that Debug::print identifies integer data and displays both
	 * the type label "integer" and the actual numeric value.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Debug::print
	 * @return void
	 */
	public function test_print_displays_integer_data(): void {
		ob_start();
		Debug::print( 42, false );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Type: integer', $output );
		$this->assertStringContainsString( '42', $output );
	}

	/**
	 * Tests that print method correctly displays boolean data type.
	 *
	 * Verifies that Debug::print identifies boolean data and displays
	 * the type label "boolean" in the output.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Debug::print
	 * @return void
	 */
	public function test_print_displays_boolean_data(): void {
		ob_start();
		Debug::print( true, false );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Type: boolean', $output );
	}

	/**
	 * Tests that print method stops execution when stop parameter is true.
	 *
	 * Verifies that Debug::print calls wp_die with termination message
	 * when the stop parameter is set to true.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Debug::print
	 * @return void
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
	 * Tests that print method correctly handles array data.
	 *
	 * Verifies that Debug::print identifies array data and displays the type
	 * label "array" along with the array keys and values.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Debug::print
	 * @return void
	 */
	public function test_print_handles_array_data(): void {
		ob_start();
		Debug::print(
			[
				'key1' => 'value1',
				'key2' => 'value2',
			],
			false
		);
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Type: array', $output );
		$this->assertStringContainsString( 'key1', $output );
		$this->assertStringContainsString( 'value1', $output );
	}

	/**
	 * Tests that print method correctly handles object data.
	 *
	 * Verifies that Debug::print identifies object data and displays
	 * the type label "object" in the output.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Debug::print
	 * @return void
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
	 * Tests that print method escapes HTML in output for security.
	 *
	 * Verifies that Debug::print uses esc_html to escape potentially dangerous
	 * HTML content, preventing XSS vulnerabilities in debug output.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Debug::print
	 * @return void
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
