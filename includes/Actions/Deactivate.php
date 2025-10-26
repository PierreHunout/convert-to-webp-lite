<?php
/**
 * Handles plugin deactivation and cleanup of WebP files and options.
 *
 * @package WpConvertToWebp
 * @since 1.0.0
 */

namespace WpConvertToWebp\Actions;

use WpConvertToWebp\Utils\Helpers;
use WpConvertToWebp\Utils\Cleaner;
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
 * Class Deactivate
 *
 * Handles the deactivation of the plugin and performs cleanup operations.
 *
 * @since 1.0.0
 */
class Deactivate {

	/**
	 * Prevent instantiation of the class
	 *
	 * @since 1.0.0
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of the class
	 *
	 * @since 1.0.0
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization of the class
	 *
	 * @since 1.0.0
	 * @throws RuntimeException Always throws exception to prevent unserialization.
	 */
	public function __wakeup() {
		throw new RuntimeException( 'Cannot unserialize a singleton.' );
	}

	/**
	 * Called when the plugin is deactivated.
	 * Deletes all WebP files if the option is enabled,
	 * and removes plugin options from the database.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function deactivate(): void {
		// Check if the user requested to delete WebP files
		$delete_webp = (bool) get_option( 'delete_webp_on_deactivate', false );

		if ( ! $delete_webp ) {
			// If not requested, exit without doing anything
			return;
		}

		// Get all image attachments from the database
		$attachments = (array) Helpers::get_attachments();

		if ( empty( $attachments ) ) {
			// If no attachments found, exit without doing anything
			return;
		}

		// Loop through all attachments and delete their WebP files
		foreach ( $attachments as $attachment_id ) {
			$metadata = wp_get_attachment_metadata( $attachment_id );

			// Ensure metadata is an array
			if ( false === $metadata ) {
				$metadata = [];
			}

			$cleaner = Cleaner::get_instance();
			$cleaner->prepare( $attachment_id, $metadata );
		}

		// Remove plugin options from the database
		delete_option( 'delete_webp_on_deactivate' );
		delete_option( 'convert_to_webp_quality' );
		delete_option( 'convert_to_webp_replace_mode' );
	}
}
