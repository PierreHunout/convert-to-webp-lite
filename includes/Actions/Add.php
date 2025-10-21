<?php
/**
 * Handles automatic conversion of uploaded images to WebP format.
 *
 * @package WpConvertToWebp
 * @since 1.0.0
 */

namespace WpConvertToWebp\Actions;

use WpConvertToWebp\Converter;
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
 * Class Add
 *
 * Handles the automatic conversion of newly uploaded images to WebP format.
 *
 * @since 1.0.0
 */
class Add {

	/**
	 * Holds the Singleton instance.
	 *
	 * @since 1.0.0
	 * @var Add|null The Singleton instance.
	 */
	protected static ?Add $instance = null;

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
	 * @return Add The Singleton instance.
	 */
	public static function get_instance(): Add {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Registers the filter for automatic WebP conversion on media upload.
	 *
	 * This method hooks into the 'wp_generate_attachment_metadata' filter,
	 * so that every image uploaded to the media library is converted to WebP.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init(): void {
		add_filter( 'wp_generate_attachment_metadata', [ $this, 'convert_webp' ], 10, 2 );
	}

	/**
	 * Converts all images in the metadata to WebP format.
	 *
	 * This method checks the metadata for the main file and its sizes,
	 * converting each to WebP format if they exist.
	 *
	 * It uses the Converter class to handle the actual conversion process.
	 *
	 * @since 1.0.0
	 * @param array $metadata The attachment metadata.
	 * @param int   $attachment_id The ID of the attachment.
	 * @return array The metadata (unchanged, only conversion is performed).
	 */
	public function convert_webp( array $metadata, int $attachment_id ): array {
		// Validate metadata before conversion
		if ( empty( $metadata ) || ! is_array( $metadata ) ) {
			return $metadata;
		}

		// Instantiate the converter and convert the image and its sizes to WebP
		$converter = (object) new Converter();
		$converter->prepare( $attachment_id, $metadata );

		// Return the original metadata (conversion does not alter it)
		return $metadata;
	}
}
