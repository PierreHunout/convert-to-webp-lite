<?php
/**
 * WebP Utils
 *
 * This class provides utility functions for handling WebP files in WordPress.
 *
 * @package PoetryConvertToWebp
 * @since 1.0.0
 */

namespace PoetryConvertToWebp\Utils;

use WP_Filesystem_Base;
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
 * Class Helpers
 *
 * Utility functions for WebP handling and WordPress integration.
 *
 * @since 1.0.0
 */
class Helpers {

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
	 * Checks if the browser supports WebP using the HTTP ACCEPT header.
	 *
	 * @since 1.0.0
	 * @return bool True if WebP is supported, false otherwise.
	 */
	public static function browser_support(): bool {
		// Check HTTP Accept header for WebP support
		$http_accept = (string) ( isset( $_SERVER['HTTP_ACCEPT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ) : '' );

		if ( strpos( $http_accept, 'image/webp' ) !== false ) {
			return true;
		}

		// Get browser info from User-Agent
		$browser = (array) self::get_browser();
		$name    = (string) strtolower( $browser['name'] );
		$version = (string) $browser['version'];

		// Internet Explorer never supports WebP
		if ( $name === 'ie' ) {
			return false;
		}

		// List of browsers and minimum versions supporting WebP
		$matrix = (array) [
			'chrome'  => '32.0',
			'firefox' => '65.0',
			'edge'    => '18.0',
			'opera'   => '19.0',
			'safari'  => '16.0', // iOS Safari 16+, macOS Safari 16+
			'android' => '4.0',  // Android Browser 4.0+
			'samsung' => '4.0',  // Samsung Internet 4+
		];

		// Check support for known browsers
		foreach ( $matrix as $key => $min ) {
			if ( strpos( $name, $key ) !== false ) {
				// If version is unknown, assume no support
				if ( $version === '?' || $version === '' ) {
					return false;
				}
				// Compare only the major version
				$major = (int) intval( explode( '.', $version )[0] );
				if ( $min !== false && $major >= $min ) {
					// WebP is supported by this browser
					return true;
				}
			}
		}

		// Fallback: not supported
		return false;
	}

	/**
	 * Gets the browser name and version from the User-Agent string.
	 *
	 * @since 1.0.0
	 * @return array An associative array with 'name' and 'version' keys.
	 */
	public static function get_browser(): array {
		$user_agent = (string) sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
		$name       = (string) 'Unknown';
		$version    = (string) '';

		// List of browsers to check
		$browsers = (array) [
			'Edge'           => 'Edge',
			'OPR'            => 'Opera',
			'Opera'          => 'Opera',
			'Chrome'         => 'Chrome',
			'Safari'         => 'Safari',
			'Firefox'        => 'Firefox',
			'MSIE'           => 'IE',
			'Trident'        => 'IE',
			'SamsungBrowser' => 'Samsung',
			'Android'        => 'Android',
		];

		foreach ( $browsers as $key => $browser_name ) {
			if ( stripos( $user_agent, $key ) !== false ) {
				$name = (string) $browser_name;
				// Build regex for version extraction
				if ( $key === 'Trident' ) {
					// IE 11+
					if ( preg_match( '/rv:([0-9\.]+)/', $user_agent, $matches ) ) {
						$version = (string) $matches[1];
					}
				} elseif ( $key === 'OPR' ) {
					// Opera (Chromium)
					if ( preg_match( '/OPR\/([0-9\.]+)/', $user_agent, $matches ) ) {
						$version = (string) $matches[1];
					}
				} elseif ( $key === 'Safari' ) {
					// Safari uses Version/ for the actual Safari version, not Safari/ (which is WebKit version)
					if ( preg_match( '/Version\/([0-9\.]+)/', $user_agent, $matches ) ) {
						$version = (string) $matches[1];
					}
				} elseif ( $key === 'SamsungBrowser' ) {
					if ( preg_match( '/SamsungBrowser\/([0-9\.]+)/', $user_agent, $matches ) ) {
						$version = (string) $matches[1];
					}
				} elseif ( $key === 'Android' ) {
					if ( preg_match( '/Android\s([0-9\.]+)/', $user_agent, $matches ) ) {
						$version = (string) $matches[1];
					}
				} elseif ( preg_match( '/' . preg_quote( $key, '/' ) . '[\/ ]([0-9\.]+)/', $user_agent, $matches ) ) {
						$version = (string) $matches[1];
				}
				break;
			}
		}

		// Special case for Safari (exclude Chrome)
		if ( $name === 'Safari' && stripos( $user_agent, 'Chrome' ) !== false ) {
			$name = (string) 'Chrome';
			if ( preg_match( '/Chrome\/([0-9\.]+)/', $user_agent, $matches ) ) {
				$version = $matches[1];
			}
		}

		// Fallback if no version found
		if ( $version === '' ) {
			$version = '?';
		}

		return [
			'name'    => $name,
			'version' => $version,
		];
	}

	/**
	 * Centralizes and formats error/success messages for admin display.
	 *
	 * @since 1.0.0
	 * @param bool   $success True for success, false for error.
	 * @param string $message The message to display.
	 * @param string $context Optional context (e.g. 'delete', 'convert').
	 * @param string $size Optional image size context.
	 * @param array  $classes Additional CSS classes for the message.
	 * @return array Array with 'message' and 'classes' keys.
	 */
	public static function get_message( bool $success, string $message, string $context = '', string $size = '', array $classes = [] ): array {
		// Add success or error class
		$classes[] = (string) ( $success ? 'success' : 'error' );

		// Add context class if provided
		if ( $context ) {
			$classes[] = (string) esc_attr( $context );
		}

		// Add size class if provided
		if ( $size && $size !== '' ) {
			$classes[] = (string) esc_attr( $size );
		}

		// Return formatted message array
		$result = [
			'message' => $message,
			'classes' => $classes,
		];

		return $result;
	}

	/**
	 * Retrieves the base directory for uploads.
	 *
	 * This method returns the base directory for uploads,
	 * which is used for WebP conversion.
	 *
	 * @since 1.0.0
	 * @return string The base directory for uploads.
	 */
	public static function get_basedir(): string {
		$upload_dir = (array) wp_upload_dir();
		return trailingslashit( $upload_dir['basedir'] );
	}

	/**
	 * Gets all image attachments from the WordPress database.
	 *
	 * @since 1.0.0
	 * @return array|void Array of attachment IDs or void if none found.
	 */
	public static function get_attachments(): array {
		$args = [
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'post_mime_type' => [ 'image/jpeg', 'image/png', 'image/gif' ],
			'fields'         => 'ids',
		];

		$attachments = get_posts( $args );

		if ( empty( $attachments ) ) {
			return [];
		}

		return $attachments;
	}

	/**
	 * Tries to get the attachment ID from any image URL (original or crop).
	 *
	 * @since 1.0.0
	 * @param string $url The image URL.
	 * @return int|false The attachment ID or false if not found.
	 */
	public static function get_attachment_id_from_url( string $url ): int|false {
		if ( empty( $url ) || ! is_string( $url ) ) {
			return false;
		}

		// Create cache key based on URL
		$cache_key = (string) 'poetry_convert_to_webp_attachment_id_' . md5( $url );

		// Try to get from cache first
		$result = wp_cache_get( $cache_key, 'poetry_convert_to_webp' );

		if ( $result !== false ) {
			return $result;
		}

		// Try the default method first
		$attachment_id = (int) attachment_url_to_postid( $url );

		if ( $attachment_id ) {
			// Cache the result for 1 hour (3600 seconds)
			wp_cache_set( $cache_key, $attachment_id, 'poetry_convert_to_webp', 3600 );
			return $attachment_id;
		}

		// Extract the file name from the URL
		$file = (string) basename( $url );

		// Create cache key for file-based lookup
		$file_cache_key = (string) 'poetry_convert_to_webp_file_lookup_' . md5( $file );

		// Try to get file lookup from cache
		$data = wp_cache_get( $file_cache_key, 'poetry_convert_to_webp' );

		if ( $data === false ) {
			global $wpdb;

			// Search for attachments with matching meta
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Required for performance, uses proper prepare and escaping
			$data = (array) $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attachment_metadata' AND meta_value LIKE %s", '%' . $wpdb->esc_like( $file ) . '%' ) );

			// Cache the database result for 1 hour
			wp_cache_set( $file_cache_key, $data, 'poetry_convert_to_webp', 3600 );
		}

		// Ensure $data is an array.
		$data = (array) $data;

		foreach ( $data as $post_id ) {
			$metadata = wp_get_attachment_metadata( $post_id );

			// Ensure metadata is an array (wp_get_attachment_metadata returns false if not found)
			if ( false === $metadata || empty( $metadata ) || empty( $metadata['sizes'] ) ) {
				continue;
			}

			// Check all sizes
			foreach ( $metadata['sizes'] as $size ) {
				if ( isset( $size['file'] ) && $size['file'] === $file ) {
					// Cache the positive result for 1 hour
					wp_cache_set( $cache_key, $post_id, 'poetry_convert_to_webp', 3600 );
					return $post_id;
				}
			}
		}

		// Cache the negative result for 30 minutes (to avoid repeated failed lookups)
		wp_cache_set( $cache_key, false, 'poetry_convert_to_webp', 1800 );
		return false;
	}

	/**
	 * Clear attachment URL cache.
	 *
	 * This method should be called when attachments are added, modified, or deleted
	 * to ensure cache consistency.
	 *
	 * @since 1.0.0
	 * @param string|null $url Optional. Specific URL to clear from cache.
	 * @return void
	 */
	public static function clear_attachment_cache( ?string $url = null ): void {
		if ( $url !== null ) {
			// Clear specific URL cache
			$cache_key = (string) 'poetry_convert_to_webp_attachment_id_' . md5( $url );
			wp_cache_delete( $cache_key, 'poetry_convert_to_webp' );

			// Also clear file-based cache
			$file           = (string) basename( $url );
			$file_cache_key = (string) 'poetry_convert_to_webp_file_lookup_' . md5( $file );
			wp_cache_delete( $file_cache_key, 'poetry_convert_to_webp' );
		} else {
			// Clear all attachment cache for this plugin
			wp_cache_flush_group( 'poetry_convert_to_webp' );
		}
	}

	/**
	 * Initialize the WordPress filesystem.
	 *
	 * @since 1.0.0
	 * @global WP_Filesystem_Base $wp_filesystem
	 * @return WP_Filesystem_Base|false The filesystem instance on success, false on failure.
	 */
	public static function get_filesystem(): WP_Filesystem_Base|false {
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! WP_Filesystem() ) {
			return false;
		}

		return $wp_filesystem;
	}

	/**
	 * Checks if a file exists in the uploads directory.
	 *
	 * This method checks if a file with the given path exists in the uploads directory.
	 *
	 * @since 1.0.0
	 * @param string $path The path to the file to check.
	 * @return bool Returns true if the file exists, false otherwise.
	 */
	public static function is_file( string $path ): bool {
		$upload_dir = (array) wp_upload_dir();
		$basedir    = (string) $upload_dir['basedir'];
		$baseurl    = (string) $upload_dir['baseurl'];
		$file       = (string) str_replace( $baseurl, $basedir, $path );

		$filesystem = self::get_filesystem();
		if ( ! $filesystem ) {
			return false;
		}

		return $filesystem->exists( $file ) && $filesystem->is_file( $file );
	}

	/**
	 * Checks if the WebP file is larger than the original file.
	 *
	 * This method compares the file sizes of the original image and its WebP version.
	 *
	 * @since 1.0.0
	 * @param string $image The path to the original image file.
	 * @param string $webp The path to the WebP image file.
	 * @return bool Returns true if the WebP file is larger, false otherwise.
	 */
	public static function is_larger( string $image, string $webp ): bool {
		$upload_dir = (array) wp_upload_dir();
		$basedir    = (string) $upload_dir['basedir'];
		$baseurl    = (string) $upload_dir['baseurl'];
		$image      = (string) str_replace( $baseurl, $basedir, $image );
		$webp       = (string) str_replace( $baseurl, $basedir, $webp );

		$filesystem = self::get_filesystem();
		if ( ! $filesystem ) {
			return false;
		}

		if (
			! ( $filesystem->exists( $image ) && $filesystem->is_file( $image ) ) ||
			! ( $filesystem->exists( $webp ) && $filesystem->is_file( $webp ) )
		) {
			return false;
		}

		$image_size = (int) $filesystem->size( $image );
		$webp_size  = (int) $filesystem->size( $webp );

		if ( $webp_size > $image_size ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if the given file path is a WebP file.
	 *
	 * This method checks if the file at the given path is a WebP file
	 * by checking its extension and returning true or false accordingly.
	 *
	 * @since 1.0.0
	 * @param string $filepath The path to the file to check.
	 * @return bool Returns true if the file is a WebP file, false otherwise.
	 */
	public static function attachment_is_webp( string $filepath ): bool {
		// Get the url path from the file path
		$filepath = (string) str_replace( wp_upload_dir()['basedir'], wp_upload_dir()['baseurl'], $filepath );

		// Get the attachment ID from the file path
		$attachment_id = (int) attachment_url_to_postid( $filepath );

		if ( ! $attachment_id || ! is_int( $attachment_id ) || $attachment_id <= 0 ) {
			return false;
		}

		// Get the file path from the attachment ID
		$file = (string) get_attached_file( $attachment_id );

		// Check the image format
		$pathinfo = (array) pathinfo( $file );

		if ( $pathinfo['extension'] === 'webp' ) {
			return true;
		}

		return false;
	}

	/**
	 * Parses a srcset string into an array of URLs and widths & sort it by width.
	 *
	 * @since 1.0.0
	 * @param string $srcset The srcset string (comma-separated).
	 * @return array Parsed array of ['url' => string, 'width' => int] or null if invalid.
	 */
	public static function parse_srcset( string $srcset ): array {
		if ( ! is_string( $srcset ) || empty( $srcset ) ) {
			return [];
		}

		$srcset = (array) explode( ', ', $srcset );
		$array  = [];

		foreach ( $srcset as $item ) {
			$item    = (array) explode( ' ', $item );
			$array[] = [
				'url'   => $item[0],
				'width' => intval( $item[1] ),
			];
		}

		// Sort by width ascending
		usort(
			$array,
			function ( $a, $b ) {
				return $a['width'] <=> $b['width'];
			}
		);

		return $array;
	}

	/**
	 * Rebuilds and sorts a srcset string from an array of URLs and widths.
	 *
	 * @since 1.0.0
	 * @param string $srcset The srcset string (comma-separated).
	 * @return string Sorted srcset string.
	 */
	public static function get_srcset( string $srcset ): string {
		if ( empty( $srcset ) ) {
			return '';
		}

		$array = (array) self::parse_srcset( $srcset );

		if ( empty( $array ) ) {
			return '';
		}

		// Rebuild srcset string
		return implode(
			', ',
			array_map(
				function ( $item ) {
					return $item['url'] . ' ' . $item['width'] . 'w';
				},
				$array
			)
		);
	}
}
