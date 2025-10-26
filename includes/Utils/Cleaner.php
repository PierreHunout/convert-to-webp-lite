<?php
/**
 * WebP Cleaner Class
 *
 * This class handles the deletion of WebP files
 * when the original image file is deleted or when cleaning up the uploads directory.
 *
 * @package WpConvertToWebp
 * @since 1.0.0
 */

namespace WpConvertToWebp\Utils;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

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
 * Class Cleaner
 *
 * Handles cleanup operations for WebP files.
 *
 * @since 1.0.0
 */
class Cleaner {

	/**
	 * Holds the Singleton instance.
	 *
	 * @since 1.0.0
	 * @var Cleaner|null The Singleton instance.
	 */
	protected static ?Cleaner $instance = null;

	/**
	 * The process type for message formatting.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $process = 'delete';

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
	 * Returns the Singleton instance of the plugin.
	 *
	 * @since 1.0.0
	 * @return Cleaner The Singleton instance.
	 */
	public static function get_instance(): Cleaner {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Prepares the deletion of WebP files associated with the given attachment ID.
	 *
	 * This method retrieves the file path of the attachment,
	 * checks if it exists, and then deletes the corresponding WebP file
	 * and any cropped versions defined in the metadata.
	 *
	 * @since 1.0.0
	 * @param int   $attachment_id The ID of the attachment.
	 * @param array $metadata The attachment metadata.
	 * @param array $result Used for recursion, do not pass manually.
	 * @throws InvalidArgumentException If attachment ID or metadata is invalid.
	 * @throws RuntimeException If filesystem operations fail.
	 * @return array Array of result messages for each processed file.
	 */
	public function prepare( int $attachment_id, array $metadata, array $result = [] ): array {
		// Define allowed HTML tags for wp_kses once
		$allowed_html = (array) [ 'span' => [] ];

		try {
			// Validate the attachment ID and metadata
			if ( ! is_int( $attachment_id ) || $attachment_id <= 0 ) {
				throw new InvalidArgumentException( __( 'Invalid attachment ID provided.', 'wp-convert-to-webp' ) );
			}

			// Check if metadata is an array and not empty
			if ( ! is_array( $metadata ) || empty( $metadata ) ) {
				throw new InvalidArgumentException( __( 'Invalid metadata provided.', 'wp-convert-to-webp' ) );
			}

			// Get the main file path for the attachment
			$file     = (string) get_attached_file( $attachment_id );
			$pathinfo = (array) pathinfo( $file );

			// Initialize filesystem
			$filesystem = Helpers::get_filesystem();

			if ( false === $filesystem ) {
				throw new RuntimeException( __( 'Failed to initialize WordPress filesystem.', 'wp-convert-to-webp' ) );
			}

			// Check if the file exists
			if ( empty( $file ) || ! $filesystem->exists( $file ) ) {
				// translators: %s is the attachment ID of the file that doesn't exist
				throw new RuntimeException( wp_kses( sprintf( __( 'File does not exist for attachment ID: %s', 'wp-convert-to-webp' ), '<span>' . esc_html( $attachment_id ) . '</span>' ), $allowed_html ) );
			}

			// Check if the file is writable before attempting to delete
			if ( ! $filesystem->is_writable( $file ) ) {
				// translators: %s is the basename of the file that is not writable
				throw new RuntimeException( wp_kses( sprintf( __( 'File is not writable: %s', 'wp-convert-to-webp' ), '<span>' . esc_html( $pathinfo['basename'] ) . '</span>' ), $allowed_html ) );
			}

			// Delete the main file's WebP version
			$result[] = (array) $this->delete( $file );

			// If there are no additional sizes, return the result
			if ( empty( $metadata['sizes'] ) || ! is_array( $metadata['sizes'] ) ) {
				return $result;
			}

			// Loop through all cropped/resized versions and delete their WebP files
			$sizes    = (array) $metadata['sizes'];
			$base_dir = (string) Helpers::get_basedir();
			$pathinfo = (array) pathinfo( $metadata['file'] );

			foreach ( $sizes as $size ) {
				if ( empty( $size['file'] ) ) {
					continue;
				}

				$filepath = (string) $base_dir . $pathinfo['dirname'] . '/' . $size['file'];

				if ( ! $filesystem->exists( $filepath ) ) {
					continue;
				}

				$result[] = (array) $this->delete( $filepath, 'size' );
			}
		} catch ( Throwable $error ) {
			// Log error if WP_DEBUG is enabled
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging when WP_DEBUG is enabled
				error_log(
					sprintf(
						// translators: %1$s is the error message, %2$s is the filename, %3$d is the line number
						__( '[WP Convert to WebP] Error preparing deletion: %1$s in %2$s on line %3$d', 'wp-convert-to-webp' ),
						$error->getMessage(),
						basename( $error->getFile() ),
						$error->getLine()
					)
				);
			}

			// Add error message to results
			$result[] = (array) Helpers::get_message( false, $error->getMessage(), $this->process );
		}

		return $result;
	}

	/**
	 * Deletes the WebP file corresponding to the given file path.
	 *
	 * This method constructs the WebP file path
	 * based on the original file path
	 * and deletes it if it exists.
	 *
	 * It is typically called when an attachment is deleted
	 * to clean up the associated WebP file.
	 *
	 * It checks if the WebP file exists and deletes it if it does.
	 *
	 * This method handles errors and exceptions, and logs them using error_log.
	 *
	 * @since 1.0.0
	 * @param string      $filepath The path to the original image file.
	 * @param string|null $size Optional. The image size context.
	 * @throws InvalidArgumentException If file path is invalid.
	 * @throws RuntimeException If filesystem initialization fails.
	 * @return array Formatted result message.
	 */
	public function delete( string $filepath, ?string $size = null ): array {
		// Define allowed HTML tags for wp_kses once
		$allowed_html = (array) [ 'span' => [] ];

		try {
			// Validate the file path
			if ( ! is_string( $filepath ) || empty( $filepath ) ) {
				throw new InvalidArgumentException( __( 'Invalid file path provided.', 'wp-convert-to-webp' ) );
			}

			// Initialize filesystem
			$filesystem = Helpers::get_filesystem();

			if ( false === $filesystem ) {
				throw new RuntimeException( __( 'Failed to initialize WordPress filesystem.', 'wp-convert-to-webp' ) );
			}

			// Check if the file exists
			if ( ! $filesystem->exists( $filepath ) ) {
				// translators: %s is the file path that does not exist
				throw new RuntimeException( wp_kses( sprintf( __( 'File does not exist: %s', 'wp-convert-to-webp' ), '<span>' . esc_html( $filepath ) . '</span>' ), $allowed_html ) );
			}

			$pathinfo = (array) pathinfo( $filepath );

			// Ensure that the dirname and filename are not empty
			if ( empty( $pathinfo['dirname'] ) || empty( $pathinfo['filename'] ) ) {
				// translators: %s is the basename of the file for which path parsing failed
				throw new RuntimeException( wp_kses( sprintf( __( 'Unable to parse file path: %s', 'wp-convert-to-webp' ), '<span>' . esc_html( $pathinfo['basename'] ) . '</span>' ), $allowed_html ) );
			}

			// If the original file is already a WebP file, nothing to delete
			if ( $pathinfo['extension'] === 'webp' ) {
				// translators: %s is the basename of the WebP file that cannot be deleted because it's already WebP
				$message = (string) wp_kses( sprintf( __( 'File is already a WebP file, nothing to delete: %s', 'wp-convert-to-webp' ), '<span>' . esc_html( $pathinfo['basename'] ) . '</span>' ), $allowed_html );
				return Helpers::get_message( true, $message, $this->process );
			}

			// Check supported mime types
			$mime_type = (string) mime_content_type( $filepath );

			if ( ! in_array( $mime_type, [ 'image/jpeg', 'image/png', 'image/gif' ], true ) ) {
				// translators: %s is the MIME type of the file that is not supported
				throw new RuntimeException( wp_kses( sprintf( __( 'Unsupported file type: %s', 'wp-convert-to-webp' ), '<span>' . esc_html( $mime_type ) . '</span>' ), $allowed_html ) );
			}

			// Build the WebP file path
			$webp = (string) $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '.webp';

			// If the WebP file does not exist, nothing to delete
			if ( ! $filesystem->exists( $webp ) ) {
				// translators: %s is the basename of the original file for which no WebP version exists
				$message = (string) wp_kses( sprintf( __( 'WebP file does not exist, nothing to delete: %s', 'wp-convert-to-webp' ), '<span>' . esc_html( $pathinfo['basename'] ) . '</span>' ), $allowed_html );
				return Helpers::get_message( false, $message, $this->process, $size ?? '' );
			}

			// Check if the WebP file is writable before attempting to delete
			if ( ! $filesystem->is_writable( $webp ) ) {
				// translators: %s is the filename of the WebP file that is not writable and cannot be deleted
				throw new RuntimeException( wp_kses( sprintf( __( 'WebP file is not writable: %s', 'wp-convert-to-webp' ), '<span>' . esc_html( $pathinfo['filename'] ) . '.webp</span>' ), $allowed_html ) );
			}

			// Attempt to delete the WebP file
			if ( ! $filesystem->delete( $webp ) ) {
				// translators: %s is the filename of the WebP file that could not be deleted
				throw new RuntimeException( wp_kses( sprintf( __( 'Failed to delete WebP file: %s', 'wp-convert-to-webp' ), '<span>' . esc_html( $pathinfo['filename'] ) . '.webp</span>' ), $allowed_html ) );
			}

			// translators: %s is the filename of the WebP file that was successfully deleted
			$message = (string) wp_kses( sprintf( __( 'Successfully deleted WebP file: %s', 'wp-convert-to-webp' ), '<span>' . esc_html( $pathinfo['filename'] ) . '.webp</span>' ), $allowed_html );

			return Helpers::get_message( true, $message, $this->process, $size ?? '' );
		} catch ( Throwable $error ) {
			// Log error if WP_DEBUG is enabled
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging when WP_DEBUG is enabled
				error_log(
					sprintf(
						// translators: %1$s is the error message, %2$s is the filename, %3$d is the line number
						__( '[WP Convert to WebP] Error deleting WebP file: %1$s in %2$s on line %3$d', 'wp-convert-to-webp' ),
						$error->getMessage(),
						basename( $error->getFile() ),
						$error->getLine()
					)
				);
			}

			// Return error message
			return Helpers::get_message( false, $error->getMessage(), $this->process, $size ?? '' );
		}
	}
}
