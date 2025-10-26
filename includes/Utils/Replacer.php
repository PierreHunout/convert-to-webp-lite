<?php
/**
 * Handles the logic for replacing <img> tags with WebP versions in WordPress content.
 *
 * @package WpConvertToWebp
 * @since 1.0.0
 */

namespace WpConvertToWebp\Utils;

use WpConvertToWebp\Modes\Picture;
use WpConvertToWebp\Modes\Image;
use RuntimeException;

/**
 * Class Replacer
 *
 * Handles replacement of img tags with WebP versions in content.
 *
 * @since 1.0.0
 */
class Replacer {

	/**
	 * Holds the Singleton instance.
	 *
	 * @since 1.0.0
	 * @var Replacer|null The Singleton instance.
	 */
	protected static ?Replacer $instance = null;

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
	 * Returns the Singleton instance of the plugin.
	 *
	 * @since 1.0.0
	 * @return Replacer The Singleton instance.
	 */
	public static function get_instance(): Replacer {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Prepares the image replacement process.
	 * Checks if a WebP version exists, gets attachment ID and metadata,
	 * adds responsive attributes, and replaces image sources.
	 *
	 * @since 1.0.0
	 * @param array $matches Regex matches from preg_replace_callback.
	 * @return string Modified image HTML or original if no WebP found.
	 */
	public static function prepare( array $matches ): string {
		$image        = (string) $matches[0];
		$src          = (string) $matches[1];
		$replace_mode = (bool) get_option( 'convert_to_webp_replace_mode', false );

		if ( empty( $replace_mode ) ) {
			$support = (array) Helpers::browser_support();

			// If the browser does not support WebP, return original image
			if ( empty( $support ) ) {
				return $image;
			}
		}

		// Build WebP file path from original src
		$webp    = (string) preg_replace( '/\.(jpe?g|png|gif)$/i', '.webp', $src );
		$is_webp = (bool) Helpers::is_file( $webp );

		// If no WebP file exists, return original image
		if ( empty( $is_webp ) ) {
			return $image;
		}

		// If WebP is larger than original file, return original image
		$is_larger = (bool) Helpers::is_larger( $src, $webp );

		if ( $is_larger ) {
			return $image;
		}

		// Get attachment ID from URL (handles crops/thumbnails)
		$attachment_id = (int) Helpers::get_attachment_id_from_url( $src );

		// If no attachment found, return original image
		if ( empty( $attachment_id ) ) {
			return $image;
		}

		// Get attachment metadata (sizes, dimensions, etc.)
		$metadata = (array) wp_get_attachment_metadata( $attachment_id );

		// If no metadata, return original image
		if ( empty( $metadata ) ) {
			return $image;
		}

		if ( $replace_mode ) {
			// Add responsive attributes (srcset, sizes)
			$picture = (string) Picture::prepare( $attachment_id, $metadata, $image, $src );

			// Replace src and srcset attributes by their WebP equivalents
			return self::replace( $picture );
		}

		// Add responsive attributes (width, height, srcset, sizes)
		$image = (string) Image::prepare( $attachment_id, $metadata, $image, $src );

		// Replace src and srcset attributes by their WebP equivalents
		return self::replace( $image );
	}

	/**
	 * Replaces src and srcset attributes in the <img> tag by their WebP equivalents.
	 * If a WebP file exists for the src or srcset item, substitutes it.
	 *
	 * @since 1.0.0
	 * @param mixed $image The <img> HTML.
	 * @return string The modified <img> HTML with WebP sources.
	 */
	private static function replace( mixed $image ): string {
		$replace_mode = (bool) get_option( 'convert_to_webp_replace_mode', false );

		if ( $replace_mode ) {
			return Picture::print( $image );
		}

		return Image::print( $image );
	}
}
