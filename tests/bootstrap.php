<?php
/**
 * PHPUnit Bootstrap for WpConvertToWebp\Tests
 *
 * @package WpConvertToWebp\Tests
 */

// Prevent direct access.
if ( ! defined( 'PHPUNIT_COMPOSER_INSTALL' ) && ! defined( 'PHPUNIT_TESTSUITE' ) ) {
	die( 'This file should only be accessed during PHPUnit testing.' );
}

// Define plugin constants for testing.
if ( ! defined( 'WP_CONVERT_TO_WEBP_TESTING' ) ) {
	define( 'WP_CONVERT_TO_WEBP_TESTING', true );
}

// Load Composer autoloader.
if ( file_exists( dirname( __DIR__ ) . '/vendor/autoload.php' ) ) {
	require_once dirname( __DIR__ ) . '/vendor/autoload.php';
} else {
	die( 'Composer autoloader not found. Please run "composer install".' );
}

// Determine if running integration tests
$is_integration = false;

// Check if running integration tests via environment variable or testsuite argument
if ( getenv( 'WP_TESTS_DIR' ) ) {
	$is_integration = true;
} elseif ( isset( $_SERVER['argv'] ) ) {
	foreach ( $_SERVER['argv'] as $i => $arg ) {
		if ( $arg === '--testsuite=Integration Tests' ) {
			$is_integration = true;
			break;
		}
		if ( $arg === '--testsuite' && isset( $_SERVER['argv'][ $i + 1 ] ) && $_SERVER['argv'][ $i + 1 ] === 'Integration Tests' ) {
			$is_integration = true;
			break;
		}
	}
}

// For integration tests, load WordPress test framework
if ( $is_integration ) {
	// Load WordPress test environment
	$_tests_dir = getenv( 'WP_TESTS_DIR' );

	if ( ! $_tests_dir ) {
		$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
	}

	// Try to find WordPress test suite
	if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
		// Try alternative locations
		$possible_locations = [
			'/tmp/wordpress-tests-lib',
			'/tmp/wordpress/tests/phpunit',
			dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) ) . '/wordpress-develop/tests/phpunit',
		];

		foreach ( $possible_locations as $location ) {
			if ( file_exists( $location . '/includes/functions.php' ) ) {
				$_tests_dir = $location;
				break;
			}
		}
	}

	if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
		die(
			"Could not find WordPress test suite.\n" .
			"Please set WP_TESTS_DIR environment variable or install WordPress test suite.\n" .
			"See: https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/\n"
		);
	}

	// Give access to tests_add_filter() function.
	require_once $_tests_dir . '/includes/functions.php';

	/**
	 * Manually load the plugin for integration testing.
	 */
	function _manually_load_plugin() {
		require dirname( __DIR__ ) . '/wp-convert-to-webp.php';
	}
	tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

	// Start up the WP testing environment.
	require $_tests_dir . '/includes/bootstrap.php';

	// Load the integration test case
	require __DIR__ . '/IntegrationTestCase.php';
} else {
	// For unit tests, set up minimal constants
	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', '/tmp/wordpress/' );
	}

	if ( ! defined( 'WP_CONTENT_DIR' ) ) {
		define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
	}

	if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
		define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
	}
}
