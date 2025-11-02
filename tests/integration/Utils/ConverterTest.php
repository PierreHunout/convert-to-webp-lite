<?php
/**
 * Integration tests for Utils\Converter class
 *
 * Tests real image conversion to WebP format with filesystem operations.
 *
 * @package PoetryConvertToWebp\Tests
 * @since 1.0.0
 */

namespace PoetryConvertToWebp\Tests\Integration\Utils;

use PoetryConvertToWebp\Tests\IntegrationTestCase;
use PoetryConvertToWebp\Utils\Converter;

/**
 * Class ConverterTest
 *
 * @since 1.0.0
 * @covers \PoetryConvertToWebp\Utils\Converter
 */
class ConverterTest extends IntegrationTestCase {

	/**
	 * Instance of Converter class
	 *
	 * @var Converter
	 */
	protected Converter $converter;

	/**
	 * Setup before each test.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		$this->converter = Converter::get_instance();
	}

	/**
	 * Test that Converter class is a singleton.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_singleton_pattern(): void {
		$instance1 = Converter::get_instance();
		$instance2 = Converter::get_instance();

		$this->assertSame( $instance1, $instance2, 'Converter should return the same instance' );
	}

	/**
	 * Test conversion of JPEG to WebP.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_converts_jpeg_to_webp(): void {
		// Create test JPEG
		$attachment_id = $this->create_test_attachment( 'test-jpeg-convert.jpg' );
		$metadata      = wp_get_attachment_metadata( $attachment_id );
		$file_path     = get_attached_file( $attachment_id );

		$this->assertFileExists( $file_path, 'JPEG file should exist' );

		// Get result
		$result = $this->converter->prepare( $attachment_id, $metadata, [] );

		$this->assertIsArray( $result, 'Result should be an array' );
		$this->assertNotEmpty( $result, 'Result should not be empty' );

		// Check WebP was created
		$this->assertWebPExists( $file_path, 'WebP file should be created' );

		// Verify WebP is valid
		$webp_path = $this->get_webp_path( $file_path );
		$this->assertWebPSmallerThanOriginal( $file_path, $webp_path );
	}

	/**
	 * Test conversion of PNG to WebP.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_converts_png_to_webp(): void {
		// Create test PNG
		$attachment_id = $this->create_test_attachment( 'test-png-convert.png' );
		$metadata      = wp_get_attachment_metadata( $attachment_id );
		$file_path     = get_attached_file( $attachment_id );

		$this->assertFileExists( $file_path, 'PNG file should exist' );

		// Convert
		$result = $this->converter->prepare( $attachment_id, $metadata, [] );

		$this->assertIsArray( $result, 'Result should be an array' );

		// Check WebP was created
		$this->assertWebPExists( $file_path, 'WebP file should be created' );
	}

	/**
	 * Test conversion respects quality setting.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_quality_setting(): void {
		// Test with high quality
		update_option( 'poetry_convert_to_webp_quality', 95 );

		$attachment_id_high = $this->create_test_attachment( 'test-quality-95.jpg' );
		$file_high          = get_attached_file( $attachment_id_high );
		$webp_high          = $this->get_webp_path( $file_high );

		$this->assertFileExists( $webp_high, 'High quality WebP should be created' );
		$size_high = filesize( $webp_high );

		// Clean up
		wp_delete_attachment( $attachment_id_high, true );
		if ( file_exists( $webp_high ) ) {
			unlink( $webp_high );
		}

		// Test with low quality
		update_option( 'poetry_convert_to_webp_quality', 50 );

		$attachment_id_low = $this->create_test_attachment( 'test-quality-50.jpg' );
		$file_low          = get_attached_file( $attachment_id_low );
		$webp_low          = $this->get_webp_path( $file_low );

		$this->assertFileExists( $webp_low, 'Low quality WebP should be created' );
		$size_low = filesize( $webp_low );

		// High quality should produce larger file
		$this->assertGreaterThan(
			$size_low,
			$size_high,
			'High quality WebP should be larger than low quality'
		);
	}

	/**
	 * Test conversion of image thumbnails/sizes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_converts_thumbnails(): void {
		// Register image sizes
		add_image_size( 'test-thumbnail', 150, 150, true );
		add_image_size( 'test-large', 600, 600, true );

		// Create attachment
		$attachment_id = $this->create_test_attachment( 'test-thumbnails.jpg' );
		$metadata      = wp_get_attachment_metadata( $attachment_id );

		$this->assertArrayHasKey( 'sizes', $metadata, 'Metadata should have sizes' );

		// Get upload directory
		$upload_dir = wp_upload_dir();
		$base_dir   = $upload_dir['basedir'];

		// Check each thumbnail has a WebP version
		$dirname = dirname( $metadata['file'] );
		foreach ( $metadata['sizes'] as $size => $size_data ) {
			$size_path = $base_dir . '/' . $dirname . '/' . $size_data['file'];
			$this->assertWebPExists( $size_path, "WebP should exist for size: {$size}" );
		}

		// Clean up
		remove_image_size( 'test-thumbnail' );
		remove_image_size( 'test-large' );
	}

	/**
	 * Test conversion handles missing file gracefully.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_handles_missing_file(): void {
		$metadata = [
			'file'   => 'nonexistent/file.jpg',
			'width'  => 800,
			'height' => 600,
		];

		$result = $this->converter->prepare( 999, $metadata, [] );

		$this->assertIsArray( $result, 'Should return array for missing file' );
		$this->assertNotEmpty( $result, 'Should return error message' );

		if ( ! empty( $result[0] ) ) {
			$this->assertArrayHasKey( 'message', $result[0], 'Result should have message' );
			$this->assertArrayHasKey( 'classes', $result[0], 'Result should have classes' );
		}
	}

	/**
	 * Test conversion handles empty metadata.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_handles_empty_metadata(): void {
		$result = $this->converter->prepare( 999, [], [] );

		$this->assertIsArray( $result, 'Should return array for empty metadata' );
	}

	/**
	 * Test conversion with invalid image format.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_handles_invalid_image_format(): void {
		// Create a text file disguised as an image
		$upload = wp_upload_bits( 'fake-image.jpg', null, 'This is not an image' );

		$attachment = [
			'post_mime_type' => 'image/jpeg',
			'post_title'     => 'Fake Image',
			'post_content'   => '',
			'post_status'    => 'inherit',
		];

		$attachment_id = wp_insert_attachment( $attachment, $upload['file'], 0 );

		$metadata = [
			'file'   => basename( $upload['file'] ),
			'width'  => 0,
			'height' => 0,
		];

		$result = $this->converter->prepare( $attachment_id, $metadata, [] );

		$this->assertIsArray( $result, 'Should handle invalid image gracefully' );

		// Clean up
		wp_delete_attachment( $attachment_id, true );
	}

	/**
	 * Test conversion returns proper result structure.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_result_structure(): void {
		$attachment_id = $this->create_test_attachment( 'test-structure.jpg' );
		$metadata      = wp_get_attachment_metadata( $attachment_id );

		$result = $this->converter->prepare( $attachment_id, $metadata, [] );

		$this->assertIsArray( $result, 'Result should be an array' );

		if ( ! empty( $result ) ) {
			foreach ( $result as $item ) {
				$this->assertIsArray( $item, 'Each result item should be an array' );
				$this->assertArrayHasKey( 'message', $item, 'Item should have message' );
				$this->assertArrayHasKey( 'classes', $item, 'Item should have classes' );
				$this->assertIsArray( $item['classes'], 'Classes should be an array' );
			}
		}
	}

	/**
	 * Test conversion creates WebP with correct permissions.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_webp_file_permissions(): void {
		$attachment_id = $this->create_test_attachment( 'test-permissions.jpg' );
		$file_path     = get_attached_file( $attachment_id );
		$webp_path     = $this->get_webp_path( $file_path );

		$this->assertFileExists( $webp_path, 'WebP file should exist' );
		$this->assertFileIsReadable( $webp_path, 'WebP file should be readable' );
	}

	/**
	 * Test conversion doesn't modify original file.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_preserves_original_file(): void {
		$attachment_id = $this->create_test_attachment( 'test-preserve-orig.jpg' );
		$file_path     = get_attached_file( $attachment_id );
		$metadata      = wp_get_attachment_metadata( $attachment_id );

		// Get original file stats
		$original_size    = filesize( $file_path );
		$original_content = file_get_contents( $file_path );

		// Convert
		$this->converter->prepare( $attachment_id, $metadata, [] );

		// Verify original is unchanged
		$this->assertSame(
			$original_size,
			filesize( $file_path ),
			'Original file size should not change'
		);

		$this->assertSame(
			$original_content,
			file_get_contents( $file_path ),
			'Original file content should not change'
		);
	}

	/**
	 * Test conversion with large image.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_handles_large_image(): void {
		// Create a larger test image
		$filepath = $this->test_images_dir . '/large-image.jpg';

		if ( ! file_exists( $this->test_images_dir ) ) {
			wp_mkdir_p( $this->test_images_dir );
		}

		$image = imagecreatetruecolor( 2000, 1500 );
		$color = imagecolorallocate( $image, 100, 150, 200 );
		imagefill( $image, 0, 0, $color );
		imagejpeg( $image, $filepath, 90 );
		imagedestroy( $image );

		// Upload as attachment
		$upload = wp_upload_bits( 'large-image.jpg', null, file_get_contents( $filepath ) );

		$attachment = [
			'post_mime_type' => 'image/jpeg',
			'post_title'     => 'Large Image',
			'post_content'   => '',
			'post_status'    => 'inherit',
		];

		$attachment_id = wp_insert_attachment( $attachment, $upload['file'], 0 );

		require_once ABSPATH . 'wp-admin/includes/image.php';
		$metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		$file_path = get_attached_file( $attachment_id );
		$this->assertWebPExists( $file_path, 'WebP should be created for large image' );

		// Clean up
		unlink( $filepath );
	}

	/**
	 * Test bulk conversion of multiple images.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_bulk_conversion(): void {
		$attachments = [];

		// Create multiple attachments
		for ( $i = 1; $i <= 5; $i++ ) {
			$attachments[] = $this->create_test_attachment( "bulk-test-{$i}.jpg" );
		}

		// Verify all have WebP versions
		foreach ( $attachments as $attachment_id ) {
			$file_path = get_attached_file( $attachment_id );
			$this->assertWebPExists( $file_path, "WebP should exist for attachment {$attachment_id}" );
		}
	}

	/**
	 * Test conversion result messages.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_result_messages(): void {
		$attachment_id = $this->create_test_attachment( 'test-messages.jpg' );
		$metadata      = wp_get_attachment_metadata( $attachment_id );

		$result = $this->converter->prepare( $attachment_id, $metadata, [] );

		$this->assertNotEmpty( $result, 'Result should contain messages' );

		// Check message structure
		foreach ( $result as $item ) {
			$this->assertArrayHasKey( 'message', $item, 'Each item should have a message' );
			$this->assertIsString( $item['message'], 'Message should be a string' );
			$this->assertNotEmpty( $item['message'], 'Message should not be empty' );
		}
	}
}
