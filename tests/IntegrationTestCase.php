<?php
/**
 * Base Test Case for integration tests
 *
 * Provides common functionality for all integration tests with WordPress test framework.
 *
 * @package WpConvertToWebp\Tests
 * @since 1.0.0
 */

namespace WpConvertToWebp\Tests;

use WP_UnitTestCase;

/**
 * Class IntegrationTestCase
 *
 * Base test case for integration tests with WordPress functionality.
 *
 * @since 1.0.0
 */
abstract class IntegrationTestCase extends WP_UnitTestCase {

	/**
	 * Test uploads directory
	 *
	 * @var string
	 */
	protected string $test_uploads_dir;

	/**
	 * Test images directory
	 *
	 * @var string
	 */
	protected string $test_images_dir;

	/**
	 * Setup before each test.
	 *
	 * Initializes test environment and creates necessary directories.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// Set up test directories
		$upload_dir             = wp_upload_dir();
		$this->test_uploads_dir = $upload_dir['basedir'];
		$this->test_images_dir  = dirname( __DIR__ ) . '/tests/fixtures/images';

		// Ensure uploads directory exists
		if ( ! file_exists( $this->test_uploads_dir ) ) {
			wp_mkdir_p( $this->test_uploads_dir );
		}

		// Set default options
		update_option( 'convert_to_webp_quality', 85 );
		update_option( 'convert_to_webp_replace_mode', 0 );
		update_option( 'delete_webp_on_deactivate', 0 );
		update_option( 'delete_webp_on_uninstall', 0 );
	}

	/**
	 * Cleanup after each test.
	 *
	 * Removes test files and resets options.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function tear_down(): void {
		// Clean up test uploads
		$this->clean_uploads_dir();

		// Reset options
		delete_option( 'convert_to_webp_quality' );
		delete_option( 'convert_to_webp_replace_mode' );
		delete_option( 'delete_webp_on_deactivate' );
		delete_option( 'delete_webp_on_uninstall' );

		parent::tear_down();
	}

	/**
	 * Clean up all files in the uploads directory.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function clean_uploads_dir(): void {
		if ( ! file_exists( $this->test_uploads_dir ) ) {
			return;
		}

		$files = glob( $this->test_uploads_dir . '/*' );
		if ( false === $files ) {
			return;
		}

		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				unlink( $file );
			} elseif ( is_dir( $file ) ) {
				$this->remove_dir_recursive( $file );
			}
		}
	}

	/**
	 * Recursively remove directory and its contents.
	 *
	 * @since 1.0.0
	 * @param string $dir Directory path.
	 * @return void
	 */
	protected function remove_dir_recursive( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$files = array_diff( scandir( $dir ), [ '.', '..' ] );
		foreach ( $files as $file ) {
			$path = $dir . '/' . $file;
			if ( is_dir( $path ) ) {
				$this->remove_dir_recursive( $path );
			} else {
				unlink( $path );
			}
		}
		rmdir( $dir );
	}

	/**
	 * Create a test attachment from a fixture image.
	 *
	 * @since 1.0.0
	 * @param string $filename Fixture filename (e.g., 'test-image.jpg').
	 * @param int    $parent_post_id Optional parent post ID.
	 * @return int Attachment ID.
	 */
	protected function create_test_attachment( string $filename, int $parent_post_id = 0 ): int {
		$source_file = $this->test_images_dir . '/' . $filename;

		// If fixture doesn't exist, create a simple test image
		if ( ! file_exists( $source_file ) ) {
			$source_file = $this->create_test_image( $filename );
		}

		$upload = wp_upload_bits( $filename, null, file_get_contents( $source_file ) );

		$this->assertFalse( $upload['error'], 'Failed to upload test file: ' . $upload['error'] );

		$attachment = [
			'post_mime_type' => wp_check_filetype( $upload['file'] )['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $upload['file'] ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		];

		$attachment_id = wp_insert_attachment( $attachment, $upload['file'], $parent_post_id );

		$this->assertIsInt( $attachment_id );
		$this->assertGreaterThan( 0, $attachment_id );

		// Generate metadata
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		return $attachment_id;
	}

	/**
	 * Create a test image file programmatically.
	 *
	 * @since 1.0.0
	 * @param string $filename Filename for the test image.
	 * @return string Path to created image.
	 */
	protected function create_test_image( string $filename ): string {
		// Create fixtures directory if it doesn't exist
		if ( ! file_exists( $this->test_images_dir ) ) {
			wp_mkdir_p( $this->test_images_dir );
		}

		$filepath = $this->test_images_dir . '/' . $filename;

		// Create a simple colored image
		$image = imagecreatetruecolor( 800, 600 );

		// Fill with a color
		$color = imagecolorallocate( $image, 100, 150, 200 );
		imagefill( $image, 0, 0, $color );

		// Add some text
		$text_color = imagecolorallocate( $image, 255, 255, 255 );
		imagestring( $image, 5, 10, 10, 'Test Image', $text_color );

		// Determine format from extension
		$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

		switch ( $extension ) {
			case 'jpg':
			case 'jpeg':
				imagejpeg( $image, $filepath, 90 );
				break;
			case 'png':
				imagepng( $image, $filepath, 9 );
				break;
			case 'gif':
				imagegif( $image, $filepath );
				break;
			default:
				imagejpeg( $image, $filepath, 90 );
		}

		imagedestroy( $image );

		return $filepath;
	}

	/**
	 * Assert that a WebP file exists for the given image path.
	 *
	 * @since 1.0.0
	 * @param string $image_path Path to the original image.
	 * @param string $message Optional custom assertion message.
	 * @return void
	 */
	protected function assertWebPExists( string $image_path, string $message = '' ): void {
		$webp_path = preg_replace( '/\.(jpe?g|png|gif)$/i', '.webp', $image_path );

		if ( empty( $message ) ) {
			$message = sprintf( 'WebP file should exist at: %s', $webp_path );
		}

		$this->assertFileExists( $webp_path, $message );
	}

	/**
	 * Assert that a WebP file does not exist for the given image path.
	 *
	 * @since 1.0.0
	 * @param string $image_path Path to the original image.
	 * @param string $message Optional custom assertion message.
	 * @return void
	 */
	protected function assertWebPNotExists( string $image_path, string $message = '' ): void {
		$webp_path = preg_replace( '/\.(jpe?g|png|gif)$/i', '.webp', $image_path );

		if ( empty( $message ) ) {
			$message = sprintf( 'WebP file should not exist at: %s', $webp_path );
		}

		$this->assertFileDoesNotExist( $webp_path, $message );
	}

	/**
	 * Get the WebP path for a given image path.
	 *
	 * @since 1.0.0
	 * @param string $image_path Path to the original image.
	 * @return string Path to the WebP version.
	 */
	protected function get_webp_path( string $image_path ): string {
		return preg_replace( '/\.(jpe?g|png|gif)$/i', '.webp', $image_path );
	}

	/**
	 * Assert that WebP file size is smaller than original.
	 *
	 * @since 1.0.0
	 * @param string $original_path Path to original image.
	 * @param string $webp_path Path to WebP image.
	 * @return void
	 */
	protected function assertWebPSmallerThanOriginal( string $original_path, string $webp_path ): void {
		$this->assertFileExists( $original_path, 'Original file must exist' );
		$this->assertFileExists( $webp_path, 'WebP file must exist' );

		$original_size = filesize( $original_path );
		$webp_size     = filesize( $webp_path );

		$this->assertGreaterThan(
			0,
			$original_size,
			'Original file size must be greater than 0'
		);

		$this->assertGreaterThan(
			0,
			$webp_size,
			'WebP file size must be greater than 0'
		);

		// WebP should typically be smaller, but allow up to 10% larger in edge cases
		$this->assertLessThanOrEqual(
			$original_size * 1.1,
			$webp_size,
			sprintf(
				'WebP file (%d bytes) should not be significantly larger than original (%d bytes)',
				$webp_size,
				$original_size
			)
		);
	}
}
