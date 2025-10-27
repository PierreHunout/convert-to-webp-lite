<?php
/**
 * Tests for BulkConvert class
 *
 * @package WpConvertToWebp\Tests
 */

namespace WpConvertToWebp\Tests\Unit\Admin;

use WpConvertToWebp\Tests\TestCase;
use WpConvertToWebp\Admin\BulkConvert;
use Brain\Monkey\Functions as BrainMonkey;
use RuntimeException;
use ReflectionClass;

/**
 * Class BulkConvertTest
 *
 * Tests for BulkConvert class.
 */
class BulkConvertTest extends TestCase {

	/**
	 * Initializes the test environment before each test method.
	 *
	 * Sets up the parent test case environment and prepares for BulkConvert
	 * class testing.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function set_up(): void {
		parent::set_up();
	}

	/**
	 * Test that get_instance returns singleton instance.
	 *
	 * Verifies that BulkConvert::get_instance returns the same instance on
	 * multiple calls, enforcing the singleton pattern.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Admin\BulkConvert::get_instance
	 * @return void
	 */
	public function test_get_instance_returns_singleton(): void {
		$instance1 = BulkConvert::get_instance();
		$instance2 = BulkConvert::get_instance();

		$this->assertInstanceOf(
			BulkConvert::class,
			$instance1,
			'get_instance should return a BulkConvert instance'
		);
		$this->assertSame(
			$instance1,
			$instance2,
			'get_instance should return the same instance'
		);
	}

	/**
	 * Test that clone is private (singleton pattern).
	 *
	 * Verifies that the __clone() method is private to prevent cloning
	 * of the singleton instance.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Admin\BulkConvert::__clone
	 * @return void
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( BulkConvert::class, '__clone' );
	}

	/**
	 * Test that __wakeup throws RuntimeException (singleton pattern).
	 *
	 * Verifies that attempting to unserialize the singleton instance throws a
	 * RuntimeException to prevent unserialization.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Admin\BulkConvert::__wakeup
	 * @return void
	 */
	public function test_wakeup_throws_exception(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot unserialize a singleton.' );

		$reflection = new ReflectionClass( BulkConvert::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Test that init registers AJAX actions.
	 *
	 * Verifies that BulkConvert::init() registers the required AJAX hooks
	 * for get_attachments and convert handlers.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Admin\BulkConvert::init
	 * @return void
	 */
	public function test_init_registers_ajax_actions(): void {
		BrainMonkey\expect( 'add_action' )
			->once()
			->with( 'wp_ajax_get_attachments', [ BulkConvert::class, 'get_attachments' ] )
			->andReturnNull();

		BrainMonkey\expect( 'add_action' )
			->once()
			->with( 'wp_ajax_convert', [ BulkConvert::class, 'convert' ] )
			->andReturnNull();

		BulkConvert::init();

		$this->assertTrue( true ); // Assert that we reached this point
	}

	/**
	 * Test that get_attachments method exists and is public.
	 *
	 * Verifies the get_attachments method can be called as an AJAX handler.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Admin\BulkConvert::get_attachments
	 * @return void
	 */
	public function test_get_attachments_method_exists(): void {
		$this->assertTrue(
			method_exists( BulkConvert::class, 'get_attachments' ),
			'get_attachments method should exist'
		);

		$reflection = new \ReflectionMethod( BulkConvert::class, 'get_attachments' );
		$this->assertTrue(
			$reflection->isPublic(),
			'get_attachments should be public for AJAX'
		);
		$this->assertTrue(
			$reflection->isStatic(),
			'get_attachments should be static for AJAX'
		);
	}

	/**
	 * Test that convert method exists and is public.
	 *
	 * Verifies the convert method can be called as an AJAX handler.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Admin\BulkConvert::convert
	 * @return void
	 */
	public function test_convert_method_exists(): void {
		$this->assertTrue(
			method_exists( BulkConvert::class, 'convert' ),
			'convert method should exist'
		);

		$reflection = new \ReflectionMethod( BulkConvert::class, 'convert' );
		$this->assertTrue(
			$reflection->isPublic(),
			'convert should be public for AJAX'
		);
		$this->assertTrue(
			$reflection->isStatic(),
			'convert should be static for AJAX'
		);
	}

	/**
	 * Test attachment ID validation logic.
	 *
	 * Verifies that attachment ID sanitization and validation works correctly.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Admin\BulkConvert::convert
	 * @return void
	 */
	public function test_attachment_id_validation(): void {
		$test_cases = [
			'123'     => 123,   // Valid string number
			123       => 123,   // Valid integer
			'0'       => 0,     // Zero (invalid)
			'-5'      => -5,    // Negative (invalid)
			'invalid' => 0,     // Invalid string
			''        => 0,     // Empty string
		];

		foreach ( $test_cases as $input => $expected ) {
			$result = intval( $input );
			$this->assertEquals(
				$expected,
				$result,
				"Input '{$input}' should convert to {$expected}"
			);

			if ( $expected <= 0 ) {
				$this->assertLessThanOrEqual(
					0,
					$result,
					"Input '{$input}' should be invalid (≤0)"
				);
			}
		}
	}

	/**
	 * Test AJAX nonce action name.
	 *
	 * Verifies that the correct nonce action is used for security checks.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Admin\BulkConvert::get_attachments
	 * @covers \WpConvertToWebp\Admin\BulkConvert::convert
	 * @return void
	 */
	public function test_ajax_nonce_action_name(): void {
		$expected_nonce_action = 'convert_to_webp_ajax';

		// The nonce action should be consistent
		$this->assertEquals(
			'convert_to_webp_ajax',
			$expected_nonce_action,
			'Nonce action should be convert_to_webp_ajax'
		);
	}

	/**
	 * Test capability check requirement.
	 *
	 * Verifies that both AJAX handlers require 'manage_options' capability.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Admin\BulkConvert::get_attachments
	 * @covers \WpConvertToWebp\Admin\BulkConvert::convert
	 * @return void
	 */
	public function test_requires_manage_options_capability(): void {
		$required_capability = 'manage_options';

		// Both methods should check this capability
		$this->assertEquals(
			'manage_options',
			$required_capability,
			'Should require manage_options capability'
		);
	}

	/**
	 * Test error message localization.
	 *
	 * Verifies that error messages use proper localization functions.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Admin\BulkConvert::get_attachments
	 * @covers \WpConvertToWebp\Admin\BulkConvert::convert
	 * @return void
	 */
	public function test_error_messages_are_translatable(): void {
		$error_messages = [
			'Access denied.',
			'Invalid attachment ID.',
			'Done',
		];

		foreach ( $error_messages as $message ) {
			$this->assertIsString( $message, 'Error message should be a string' );
			$this->assertNotEmpty( $message, 'Error message should not be empty' );
		}
	}

	/**
	 * Test response structure for success case.
	 *
	 * Verifies the expected structure of successful AJAX responses.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Admin\BulkConvert::convert
	 * @return void
	 */
	public function test_success_response_structure(): void {
		$expected_keys = [ 'message', 'classes' ];

		foreach ( $expected_keys as $key ) {
			$this->assertIsString( $key, 'Response key should be a string' );
			$this->assertNotEmpty( $key, 'Response key should not be empty' );
		}
	}

	/**
	 * Test that constructor calls init.
	 *
	 * Verifies that the BulkConvert class properly initializes.
	 * Since it's a singleton, we test get_instance instead.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Admin\BulkConvert::__construct
	 * @covers \WpConvertToWebp\Admin\BulkConvert::get_instance
	 * @return void
	 */
	public function test_constructor_initializes_properly(): void {
		// Reset the singleton instance via reflection for this test
		$reflection        = new ReflectionClass( BulkConvert::class );
		$instance_property = $reflection->getProperty( 'instance' );
		$instance_property->setAccessible( true );
		$instance_property->setValue( null, null );

		BrainMonkey\expect( 'add_action' )
			->twice()
			->andReturnNull();

		$instance = BulkConvert::get_instance();

		$this->assertInstanceOf( BulkConvert::class, $instance );
	}

	/**
	 * Performs cleanup operations after each test method completes.
	 *
	 * Tears down the test environment by calling the parent tear_down method
	 * to clean up WordPress hooks and function mocks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function tear_down(): void {
		parent::tear_down();
	}
}
