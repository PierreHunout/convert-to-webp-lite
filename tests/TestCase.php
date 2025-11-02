<?php
/**
 * Base Test Case for unit tests
 *
 * Provides common functionality for all unit tests including Brain Monkey integration.
 *
 * @package PoetryConvertToWebp\Tests
 */

namespace PoetryConvertToWebp\Tests;

use Brain\Monkey;
use Yoast\PHPUnitPolyfills\TestCases\TestCase as PolyfillsTestCase;

/**
 * Class TestCase
 *
 * Base test case with Brain Monkey and PHPUnit Polyfills support.
 */
abstract class TestCase extends PolyfillsTestCase {

	/**
	 * Setup before each test.
	 *
	 * Initializes Brain Monkey for WordPress function mocking.
	 *
	 * @return void
	 */
	protected function set_up(): void {
		parent::set_up();
		Monkey\setUp();

		// Setup common WordPress constants if not defined
		if ( ! defined( 'WPINC' ) ) {
			define( 'WPINC', 'wp-includes' );
		}

		if ( ! defined( 'ABSPATH' ) ) {
			define( 'ABSPATH', '/tmp/wordpress/' );
		}

		if ( ! defined( 'WP_CONTENT_DIR' ) ) {
			define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
		}
	}

	/**
	 * Cleanup after each test.
	 *
	 * Closes Brain Monkey and Mockery.
	 *
	 * @return void
	 */
	protected function tear_down(): void {
		Monkey\tearDown();
		parent::tear_down();
	}

	/**
	 * Clean up $_SERVER superglobal values.
	 *
	 * @param array $keys Keys to unset from $_SERVER.
	 * @return void
	 */
	protected function unsetServerVars( array $keys ): void {
		foreach ( $keys as $key ) {
			unset( $_SERVER[ $key ] );
		}
	}

	/**
	 * Assert that a method is private.
	 *
	 * @param string $class_name The class name.
	 * @param string $method_name The method name.
	 * @return void
	 */
	protected function assertMethodIsPrivate( string $class_name, string $method_name ): void {
		$reflection = new \ReflectionClass( $class_name );
		$method     = $reflection->getMethod( $method_name );

		$this->assertTrue(
			$method->isPrivate(),
			"Failed asserting that method '{$method_name}' in class '{$class_name}' is private"
		);
	}
}
