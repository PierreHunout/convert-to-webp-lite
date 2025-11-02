<?php
/**
 * Integration tests for Actions\Add class
 *
 * Tests automatic WebP conversion on image upload.
 *
 * @package PoetryConvertToWebp\Tests
 * @since 1.0.0
 */

namespace PoetryConvertToWebp\Tests\Integration\Actions;

use PoetryConvertToWebp\Tests\IntegrationTestCase;
use PoetryConvertToWebp\Actions\Add;

/**
 * Class AddTest
 *
 * @since 1.0.0
 * @covers \PoetryConvertToWebp\Actions\Add
 */
class AddTest extends IntegrationTestCase {

	/**
	 * Instance of Add class
	 *
	 * @var Add
	 */
	protected Add $add;

	/**
	 * Setup before each test.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		$this->add = Add::get_instance();
		$this->add->init(); // Initialize hooks
	}

	/**
	 * Test that Add class is a singleton.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_singleton_pattern(): void {
		$instance1 = Add::get_instance();
		$instance2 = Add::get_instance();

		$this->assertSame( $instance1, $instance2, 'Add should return the same instance' );
	}

	/**
	 * Test that filter is registered on init.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_filter_registered(): void {
		$priority = has_filter( 'wp_generate_attachment_metadata', [ $this->add, 'convert_webp' ] );

		$this->assertSame( 10, $priority, 'Filter should be registered with priority 10' );
	}

	/**
	 * Test automatic WebP conversion on JPEG upload.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_converts_jpeg_to_webp_on_upload(): void {
		// Create test JPEG attachment
		$attachment_id = $this->create_test_attachment( 'test-jpeg.jpg' );

		// Get the uploaded file path
		$file_path = get_attached_file( $attachment_id );
		$this->assertFileExists( $file_path, 'Original JPEG should exist' );

		// Check if WebP was created
		$this->assertWebPExists( $file_path, 'WebP version should be created automatically' );

		// Verify WebP file is valid
		$webp_path = $this->get_webp_path( $file_path );
		$this->assertWebPSmallerThanOriginal( $file_path, $webp_path );
	}

	/**
	 * Test automatic WebP conversion on PNG upload.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_converts_png_to_webp_on_upload(): void {
		// Create test PNG attachment
		$attachment_id = $this->create_test_attachment( 'test-png.png' );

		// Get the uploaded file path
		$file_path = get_attached_file( $attachment_id );
		$this->assertFileExists( $file_path, 'Original PNG should exist' );

		// Check if WebP was created
		$this->assertWebPExists( $file_path, 'WebP version should be created automatically' );
	}

	/**
	 * Test conversion of image sizes/thumbnails.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_converts_image_sizes_to_webp(): void {
		// Register test image sizes
		add_image_size( 'test-small', 150, 150, true );
		add_image_size( 'test-medium', 300, 300, true );

		// Create test attachment
		$attachment_id = $this->create_test_attachment( 'test-sizes.jpg' );

		// Get metadata
		$metadata = wp_get_attachment_metadata( $attachment_id );

		$this->assertIsArray( $metadata, 'Metadata should be an array' );
		$this->assertArrayHasKey( 'sizes', $metadata, 'Metadata should contain sizes' );

		// Get upload directory
		$upload_dir = wp_upload_dir();
		$base_dir   = $upload_dir['basedir'];

		// Check that WebP was created for each size
		if ( isset( $metadata['file'] ) ) {
			$dirname = dirname( $metadata['file'] );

			foreach ( $metadata['sizes'] as $size => $size_data ) {
				$size_path = $base_dir . '/' . $dirname . '/' . $size_data['file'];
				$this->assertWebPExists( $size_path, "WebP should exist for size: {$size}" );
			}
		}

		// Clean up image sizes
		remove_image_size( 'test-small' );
		remove_image_size( 'test-medium' );
	}

	/**
	 * Test conversion respects quality setting.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_respects_quality_setting(): void {
		// Set high quality
		update_option( 'poetry_convert_to_webp_quality', 95 );

		$attachment_id_high = $this->create_test_attachment( 'test-quality-high.jpg' );
		$file_high          = get_attached_file( $attachment_id_high );
		$webp_high          = $this->get_webp_path( $file_high );

		$this->assertFileExists( $webp_high, 'WebP with high quality should be created' );
		$size_high = filesize( $webp_high );

		// Clean up
		wp_delete_attachment( $attachment_id_high, true );
		if ( file_exists( $webp_high ) ) {
			unlink( $webp_high );
		}

		// Set low quality
		update_option( 'poetry_convert_to_webp_quality', 50 );

		$attachment_id_low = $this->create_test_attachment( 'test-quality-low.jpg' );
		$file_low          = get_attached_file( $attachment_id_low );
		$webp_low          = $this->get_webp_path( $file_low );

		$this->assertFileExists( $webp_low, 'WebP with low quality should be created' );
		$size_low = filesize( $webp_low );

		// High quality should produce larger file than low quality
		$this->assertGreaterThan(
			$size_low,
			$size_high,
			'Higher quality setting should produce larger file size'
		);
	}

	/**
	 * Test conversion handles empty metadata gracefully.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_handles_empty_metadata(): void {
		$result = $this->add->convert_webp( [], 999 );

		$this->assertIsArray( $result, 'Should return array even with empty metadata' );
		$this->assertEmpty( $result, 'Should return empty array when given empty metadata' );
	}

	/**
	 * Test conversion handles invalid metadata.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_handles_invalid_metadata(): void {
		$invalid_metadata = [
			'width'  => 800,
			'height' => 600,
			// Missing 'file' key
		];

		$attachment_id = $this->create_test_attachment( 'test-invalid.jpg' );
		$result        = $this->add->convert_webp( $invalid_metadata, $attachment_id );

		$this->assertIsArray( $result, 'Should return array even with invalid metadata' );
	}

	/**
	 * Test that original metadata is returned unchanged.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_returns_original_metadata(): void {
		$attachment_id = $this->create_test_attachment( 'test-metadata.jpg' );
		$metadata      = wp_get_attachment_metadata( $attachment_id );

		$result = $this->add->convert_webp( $metadata, $attachment_id );

		$this->assertEquals( $metadata, $result, 'Original metadata should be returned unchanged' );
	}

	/**
	 * Test conversion with non-image attachment.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_handles_non_image_attachment(): void {
		// Create a text file attachment
		$upload = wp_upload_bits( 'test.txt', null, 'Test content' );

		$attachment = [
			'post_mime_type' => 'text/plain',
			'post_title'     => 'Test Text File',
			'post_content'   => '',
			'post_status'    => 'inherit',
		];

		$attachment_id = wp_insert_attachment( $attachment, $upload['file'], 0 );

		$metadata = [];
		$result   = $this->add->convert_webp( $metadata, $attachment_id );

		$this->assertIsArray( $result, 'Should handle non-image attachments gracefully' );
	}

	/**
	 * Test multiple uploads create multiple WebP files.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_multiple_uploads(): void {
		$attachments = [];

		// Upload multiple images
		for ( $i = 1; $i <= 3; $i++ ) {
			$attachments[] = $this->create_test_attachment( "test-multi-{$i}.jpg" );
		}

		// Verify all WebP files were created
		foreach ( $attachments as $attachment_id ) {
			$file_path = get_attached_file( $attachment_id );
			$this->assertWebPExists( $file_path, "WebP should exist for attachment {$attachment_id}" );
		}
	}
}
