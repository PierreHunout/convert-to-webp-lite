<?php
/**
 * Handles actions for bulk deletion of WebP files.
 *
 * @package ConvertToWebpLite
 * @since 1.0.0
 */

namespace ConvertToWebpLite\Admin;

use ConvertToWebpLite\Utils\Helpers;
use ConvertToWebpLite\Utils\Cleaner;
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
 * Class BulkDelete
 *
 * Handles actions for bulk deletion of WebP files.
 *
 * @since 1.0.0
 */
class BulkDelete {

	/**
	 * Holds the Singleton instance.
	 *
	 * @since 1.0.0
	 * @var BulkDelete|null The Singleton instance.
	 */
	protected static ?BulkDelete $instance = null;

	/**
	 * Constructor to initialize the class.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		$this->init();
	}

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
	 * @return void
	 * @throws RuntimeException Always throws exception to prevent unserialization.
	 */
	public function __wakeup() {
		throw new RuntimeException( 'Cannot unserialize a singleton.' );
	}

	/**
	 * Returns the Singleton instance of the plugin.
	 *
	 * @since 1.0.0
	 * @return BulkDelete The Singleton instance.
	 */
	public static function get_instance(): BulkDelete {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Registers actions for the bulk deletion process.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_post_delete_all_webp', [ __CLASS__, 'delete_all_webp' ] );
	}

	/**
	 * Deletes all webp files in the uploads directory.
	 * Stores deletion results for admin notice.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function delete_all_webp(): void {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'delete_all_webp' ) ) {
			wp_die( esc_html__( 'Not allowed', 'convert-to-webp-lite' ) );
		}

		$attachments = (array) Helpers::get_attachments();

		if ( empty( $attachments ) ) {
			$redirect_url = (string) add_query_arg(
				[
					'no_files' => '1',
					'_wpnonce' => wp_create_nonce( 'convert_to_webp_lite_notice' ),
				],
				admin_url( 'admin.php?page=convert-to-webp-lite' )
			);
			wp_safe_redirect( $redirect_url );
			exit;
		}

		$result = [];

		// Loop through all attachments and delete their WebP files
		foreach ( $attachments as $attachment_id ) {
			$metadata = (array) wp_get_attachment_metadata( $attachment_id );
			$cleaner  = Cleaner::get_instance();
			$result[] = (array) $cleaner->prepare( $attachment_id, $metadata );
		}       // Store details for notice
		set_transient( 'convert_to_webp_lite_deletion_data', $result, 60 );

		// Clear the cache
		wp_cache_flush();

		// Clear also the media library cache
		wp_update_attachment_metadata( 0, [] );

		// Redirect back to the options page with a success flag
		$redirect_url = (string) add_query_arg(
			[
				'deleted'  => '1',
				'_wpnonce' => wp_create_nonce( 'convert_to_webp_lite_notice' ),
			],
			admin_url( 'admin.php?page=convert-to-webp-lite' )
		);

		wp_safe_redirect( $redirect_url );

		exit;
	}
}
