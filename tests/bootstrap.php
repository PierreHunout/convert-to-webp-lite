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

// Set up test environment constants first.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
}
