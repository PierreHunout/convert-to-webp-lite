<?php
/**
 * This file is responsible for handling the debugging functionality.
 *
 * @package WpConvertToWebp
 *
 * @since 1.0.0
 */

namespace WpConvertToWebp\Utils;

use DateTime;
use DateTimeZone;
use RuntimeException;

/**
 * This check prevents direct access to the plugin file,
 * ensuring that it can only be accessed through WordPress.
 *
 * @since 1.0.0
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Debug
 *
 * Handles Debug functionality for the plugin.
 *
 * @since 1.0.0
 */
class Debug {

	/**
	 * Prevent instantiation of the class
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of the class
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization of the class
	 *
	 * @since 1.0.0
	 * @throws RuntimeException Always throws exception to prevent unserialization.
	 * @return void
	 */
	public function __wakeup() {
		throw new RuntimeException( 'Cannot unserialize a singleton.' );
	}

	/**
	 * Logs data to a specified file.
	 *
	 * Creates JSON log files in wp-content/convert-to-webp-logs/ directory
	 * with timestamp, data type, and the actual data being logged.
	 *
	 * @since 1.0.0
	 * @param string $file The log file name (without extension).
	 * @param mixed  $data The data to log.
	 * @return void
	 */
	public static function log( string $file, mixed $data ): void {
		// Store logs in wp-content/convert-to-webp-logs/ for persistence and security
		$path       = (string) WP_CONTENT_DIR . '/convert-to-webp-logs/';
		$filesystem = Helpers::get_filesystem();

		if ( false === $filesystem ) {
			return;
		}

		if ( ! $filesystem->is_dir( $path ) ) {
			$filesystem->mkdir( $path, 0755 );

			// Add .htaccess for security (deny direct access to log files)
			$htaccess = "# Deny access to log files\n<Files \"*.json\">\n\tOrder allow,deny\n\tDeny from all\n</Files>\n\n# Deny access to directory listing\nOptions -Indexes";
			$filesystem->put_contents( $path . '.htaccess', $htaccess, 0644 );

			// Add index.php to prevent directory browsing
			$index = "<?php\n// Code so quiet, you can hear the silence.\n";
			$filesystem->put_contents( $path . 'index.php', $index, 0644 );
		}

		$name = (string) sanitize_file_name( strtolower( $file ) ) . '-' . time() . '.json';
		$type = (string) gettype( $data );
		$date = (object) new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$date = (string) $date->format( 'Y-m-d H:i:sP' );

		$log = [
			'date' => $date,
			'type' => $type,
			'data' => $data,
		];

		$json_data = (string) wp_json_encode( $log, JSON_PRETTY_PRINT );

		if ( false !== $json_data ) {
			// Attempt to write log file with error handling
			$filesystem->put_contents( $path . $name, PHP_EOL . $json_data, 0644 );
		}
	}

	/**
	 * Prints data in a styled HTML block for debugging.
	 *
	 * Outputs formatted debug information with data type and JSON-encoded content
	 * in a styled HTML block. Optionally stops execution after output.
	 *
	 * @since 1.0.0
	 * @param mixed $data The data to display.
	 * @param bool  $stop Whether to stop execution after output.
	 * @return void
	 */
	public static function print( mixed $data, bool $stop = false ): void {
		echo '<div style="position: relative; margin: 24px 0; background: #2271b1; color: #fafafa; padding: 20px; border-radius: 3px; z-index: 9999;"><pre style="white-space: pre-wrap; word-wrap: break-word;">';
		echo '<strong style="color: #b5ddfeff;">Convert to WebP Debug Output:</strong>' . PHP_EOL . PHP_EOL;

		// Get $data type
		$data_type = (string) gettype( $data );
		echo '<strong style="color: #b5ddfeff;">Type: ' . esc_html( $data_type ) . '</strong>' . PHP_EOL . PHP_EOL;

		// Use wp_json_encode for safe and readable output.
		$json_output = (string) wp_json_encode( $data, JSON_PRETTY_PRINT );
		echo esc_html( $json_output ? $json_output : 'Unable to encode data' );

		echo '</pre></div>';

		if ( true === $stop ) {
			wp_die( 'Debug output terminated.' );
		}
	}
}
