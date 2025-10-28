<?php
/**
 * Handles replacement of <img> tags with <picture> elements containing WebP sources in WordPress content.
 *
 * @package ConvertToWebpLite
 * @since 1.0.0
 */

namespace ConvertToWebpLite\Modes;

use ConvertToWebpLite\Utils\Helpers;
use ConvertToWebpLite\Modes\Image;
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
 * Class Picture
 *
 * Handles replacement of img tags with picture elements for WebP support.
 *
 * @since 1.0.0
 */
class Picture {

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
	 * @return void
	 * @throws RuntimeException Always throws exception to prevent unserialization.
	 */
	public function __wakeup() {
		throw new RuntimeException( 'Cannot unserialize a singleton.' );
	}

	/**
	 * Prepares an array with src/srcset/sizes info and modified <img> tag fallback.
	 *
	 * @since 1.0.0
	 * @param int    $attachment_id The attachment ID.
	 * @param array  $metadata The attachment metadata.
	 * @param string $image The <img> HTML.
	 * @param string $src The image src URL.
	 * @return array Array containing 'src', 'srcset', 'sizes' & 'fallback' keys.
	 */
	public static function prepare( int $attachment_id, array $metadata, string $image, string $src ): array {
		// Get srcset for the attachment
		$srcset = (string) wp_get_attachment_image_srcset( $attachment_id );

		// Get sizes for the attachment
		$dimensions = (array) wp_image_src_get_dimensions( $src, $metadata, $attachment_id );
		$sizes      = (string) ( wp_calculate_image_sizes( $dimensions, $src, $metadata, $attachment_id ) ?? '100vw' );

		$result = [
			'src'      => $src,
			'srcset'   => Helpers::get_srcset( $srcset ),
			'sizes'    => $sizes,
			'fallback' => Image::prepare( $attachment_id, $metadata, $image, $src ),
		];

		return $result;
	}

	/**
	 * Generates the final <picture> HTML with WebP source and modified <img> tag.
	 *
	 * @since 1.0.0
	 * @param array $image Array containing 'src', 'srcset', 'sizes' & 'fallback' keys.
	 * @return string The complete <picture> HTML element.
	 */
	public static function print( array $image ): string {
		$src      = (string) $image['src'];
		$srcset   = (string) $image['srcset'];
		$sizes    = (string) $image['sizes'];
		$fallback = (string) $image['fallback'];

		// Build WebP file path from original src
		$webp = (string) preg_replace( '/\.(jpe?g|png|gif)$/i', '.webp', $src );

		if ( ! Helpers::is_file( $webp ) ) {
			return $fallback;
		}

		$picture = '<picture>';

		if ( empty( $srcset ) ) {
			// If no srcset, just add the default WebP source
			$picture .= sprintf( '<source type="image/webp" srcset="%s" sizes="%s" />', esc_attr( $webp ), esc_attr( $sizes ) );
			$picture .= $fallback;
			$picture .= '</picture>';

			return $picture;
		}

		// Replace each srcset item with its .webp equivalent if available
		$array  = (array) explode( ',', $srcset );
		$srcset = [];

		foreach ( $array as $item ) {
			$parts = (array) preg_split( '/\s+/', trim( $item ) );

			if ( empty( $parts[0] ) ) {
				continue;
			}

			$webp_url = (string) preg_replace( '/\.(jpe?g|png|gif)$/i', '.webp', $parts[0] );

			if ( Helpers::is_file( $webp_url ) ) {
				$srcset[] = (string) esc_url( $webp_url ) . ( isset( $parts[1] ) ? ' ' . esc_attr( $parts[1] ) : '' );
			} else {
				$srcset[] = (string) esc_url( $parts[0] ) . ( isset( $parts[1] ) ? ' ' . esc_attr( $parts[1] ) : '' );
			}
		}

		$srcset = (string) implode( ', ', $srcset );

		// Add the WebP source with srcset and sizes attributes
		$picture .= sprintf( '<source type="image/webp" srcset="%s" sizes="%s" />', esc_attr( $srcset ), esc_attr( $sizes ) );

		// Add the fallback <img> tag
		$picture .= $fallback;
		$picture .= '</picture>';

		return $picture;
	}
}
