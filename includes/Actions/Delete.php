<?php
/**
 * Handles deletion of WebP files when attachments are deleted in WordPress.
 *
 * @package WpConvertToWebp
 * @since 1.0.0
 */

namespace WpConvertToWebp\Actions;

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
 * Class Delete
 *
 * Handles deletion of WebP files when original attachments are deleted.
 *
 * @since 1.0.0
 */
class Delete {

	/**
	 * Holds the Singleton instance.
	 *
	 * @since 1.0.0
	 * @var Delete|null The Singleton instance.
	 */
	protected static ?Delete $instance = null;

	/**
	 * Constructor to initialize the class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->init();
	}

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
	 * Returns the Singleton instance of the plugin.
	 *
	 * @since 1.0.0
	 * @return Delete The Singleton instance.
	 */
	public static function get_instance(): Delete {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Registers the action for automatic WebP cleanup on attachment deletion.
	 *
	 * This method hooks into the 'delete_attachment' action,
	 * so that every time an attachment is deleted, its associated WebP files are also deleted.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init(): void {
		add_action( 'delete_attachment', [ $this, 'delete_webp' ] );
	}

	/**
	 * Deletes WebP files associated with the given attachment ID.
	 *
	 * This method checks if the attachment has a file and deletes the corresponding
	 * WebP file, as well as any cropped versions if they exist.
	 *
	 * @since 1.0.0
	 * @param int $attachment_id The ID of the attachment post.
	 * @return void
	 */
	public function delete_webp( int $attachment_id ): void {
		// Get the attachment metadata
		$metadata = (array) wp_get_attachment_metadata( $attachment_id );

		// Instantiate the Cleaner and delete associated WebP files
		$cleaner = (object) new Cleaner();
		$cleaner->prepare( $attachment_id, $metadata );
	}
}
