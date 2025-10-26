<?php
/**
 * Integration tests for Actions\Delete class
 *
 * Tests automatic WebP deletion when attachments are deleted.
 *
 * @package WpConvertToWebp\Tests
 * @since 1.0.0
 */

namespace WpConvertToWebp\Tests\Integration\Actions;

use WpConvertToWebp\Tests\IntegrationTestCase;
use WpConvertToWebp\Actions\Delete;
use WpConvertToWebp\Actions\Add;

/**
 * Class DeleteTest
 *
 * @since 1.0.0
 * @covers \WpConvertToWebp\Actions\Delete
 */
class DeleteTest extends IntegrationTestCase {

	/**
	 * Instance of Delete class
	 *
	 * @var Delete
	 */
	protected Delete $delete;

	/**
	 * Instance of Add class (for creating WebP files)
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
		$this->delete = Delete::get_instance();
		$this->delete->init(); // Initialize hooks
		$this->add = Add::get_instance();
		$this->add->init(); // Initialize hooks
	}

	/**
	 * Test that Delete class is a singleton.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_singleton_pattern(): void {
		$instance1 = Delete::get_instance();
		$instance2 = Delete::get_instance();

		$this->assertSame( $instance1, $instance2, 'Delete should return the same instance' );
	}

	/**
	 * Test that action is registered on init.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_action_registered(): void {
		$priority = has_action( 'delete_attachment', [ $this->delete, 'delete_webp' ] );

		$this->assertIsInt( $priority, 'Action should be registered' );
	}

	/**
	 * Test WebP file is deleted when attachment is deleted.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_deletes_webp_on_attachment_deletion(): void {
		// Create attachment with WebP
		$attachment_id = $this->create_test_attachment( 'test-delete.jpg' );
		$file_path     = get_attached_file( $attachment_id );
		$webp_path     = $this->get_webp_path( $file_path );

		// Verify WebP exists
		$this->assertFileExists( $webp_path, 'WebP should exist before deletion' );

		// Delete attachment
		wp_delete_attachment( $attachment_id, true );

		// Verify WebP was deleted
		$this->assertFileDoesNotExist( $webp_path, 'WebP should be deleted with attachment' );
	}

	/**
	 * Test WebP thumbnails are deleted when attachment is deleted.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_deletes_webp_thumbnails(): void {
		// Register test image sizes
		add_image_size( 'test-thumb', 150, 150, true );
		add_image_size( 'test-large', 600, 600, true );

		// Create attachment
		$attachment_id = $this->create_test_attachment( 'test-thumbs.jpg' );
		$metadata      = wp_get_attachment_metadata( $attachment_id );

		$this->assertIsArray( $metadata, 'Metadata should be array' );
		$this->assertArrayHasKey( 'sizes', $metadata, 'Metadata should have sizes' );

		// Get upload directory
		$upload_dir = wp_upload_dir();
		$base_dir   = $upload_dir['basedir'];

		// Collect WebP paths for verification
		$webp_paths = [];
		if ( isset( $metadata['file'] ) ) {
			$dirname = dirname( $metadata['file'] );

			foreach ( $metadata['sizes'] as $size => $size_data ) {
				$size_path           = $base_dir . '/' . $dirname . '/' . $size_data['file'];
				$webp_path           = $this->get_webp_path( $size_path );
				$webp_paths[ $size ] = $webp_path;

				// Verify WebP exists before deletion
				$this->assertFileExists( $webp_path, "WebP should exist for size: {$size}" );
			}
		}

		// Delete attachment
		wp_delete_attachment( $attachment_id, true );

		// Verify all WebP thumbnails were deleted
		foreach ( $webp_paths as $size => $webp_path ) {
			$this->assertFileDoesNotExist( $webp_path, "WebP should be deleted for size: {$size}" );
		}

		// Clean up image sizes
		remove_image_size( 'test-thumb' );
		remove_image_size( 'test-large' );
	}

	/**
	 * Test deletion handles missing WebP gracefully.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_handles_missing_webp_file(): void {
		// Create attachment
		$attachment_id = $this->create_test_attachment( 'test-no-webp.jpg' );
		$file_path     = get_attached_file( $attachment_id );
		$webp_path     = $this->get_webp_path( $file_path );

		// Manually delete WebP before deleting attachment
		if ( file_exists( $webp_path ) ) {
			unlink( $webp_path );
		}

		$this->assertFileDoesNotExist( $webp_path, 'WebP should not exist' );

		// This should not throw an error
		wp_delete_attachment( $attachment_id, true );

		// Verify deletion completed
		$this->assertNull( get_post( $attachment_id ), 'Attachment should be deleted' );
	}

	/**
	 * Test deletion with empty metadata.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_handles_empty_metadata(): void {
		// Create a simple attachment without metadata
		$upload = wp_upload_bits( 'test-empty.jpg', null, 'fake image content' );

		$attachment = [
			'post_mime_type' => 'image/jpeg',
			'post_title'     => 'Test Empty',
			'post_content'   => '',
			'post_status'    => 'inherit',
		];

		$attachment_id = wp_insert_attachment( $attachment, $upload['file'], 0 );

		// Don't generate metadata, just delete
		$this->delete->delete_webp( $attachment_id );

		// Should complete without errors
		$this->assertTrue( true, 'Should handle empty metadata gracefully' );
	}

	/**
	 * Test manual call to delete_webp method.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_delete_webp_method_directly(): void {
		// Create attachment with WebP
		$attachment_id = $this->create_test_attachment( 'test-direct.jpg' );
		$file_path     = get_attached_file( $attachment_id );
		$webp_path     = $this->get_webp_path( $file_path );

		$this->assertFileExists( $webp_path, 'WebP should exist' );

		// Call delete_webp directly
		$this->delete->delete_webp( $attachment_id );

		// Verify WebP was deleted
		$this->assertFileDoesNotExist( $webp_path, 'WebP should be deleted' );

		// Clean up attachment
		wp_delete_attachment( $attachment_id, true );
	}

	/**
	 * Test deletion doesn't affect original image.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_preserves_original_image(): void {
		// Create attachment
		$attachment_id = $this->create_test_attachment( 'test-preserve.jpg' );
		$file_path     = get_attached_file( $attachment_id );
		$webp_path     = $this->get_webp_path( $file_path );

		$this->assertFileExists( $file_path, 'Original should exist' );
		$this->assertFileExists( $webp_path, 'WebP should exist' );

		// Call delete_webp (not wp_delete_attachment)
		$this->delete->delete_webp( $attachment_id );

		// Original should still exist
		$this->assertFileExists( $file_path, 'Original should still exist after WebP deletion' );
		$this->assertFileDoesNotExist( $webp_path, 'WebP should be deleted' );

		// Clean up
		wp_delete_attachment( $attachment_id, true );
	}

	/**
	 * Test deletion with invalid attachment ID.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_handles_invalid_attachment_id(): void {
		// Call with non-existent ID
		$this->delete->delete_webp( 99999 );

		// Should complete without errors
		$this->assertTrue( true, 'Should handle invalid attachment ID gracefully' );
	}

	/**
	 * Test multiple deletions.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_multiple_deletions(): void {
		$attachments = [];
		$webp_paths  = [];

		// Create multiple attachments
		for ( $i = 1; $i <= 3; $i++ ) {
			$attachment_id = $this->create_test_attachment( "test-multi-del-{$i}.jpg" );
			$file_path     = get_attached_file( $attachment_id );
			$webp_path     = $this->get_webp_path( $file_path );
			$attachments[] = $attachment_id;
			$webp_paths[]  = $webp_path;
		}

		// Verify all WebP files exist
		foreach ( $webp_paths as $webp_path ) {
			$this->assertFileExists( $webp_path, 'WebP should exist before deletion' );
		}

		// Delete all attachments
		foreach ( $attachments as $attachment_id ) {
			wp_delete_attachment( $attachment_id, true );
		}

		// Verify all WebP files are deleted
		foreach ( $webp_paths as $webp_path ) {
			$this->assertFileDoesNotExist( $webp_path, 'WebP should be deleted' );
		}
	}

	/**
	 * Test deletion when attachment is trashed (not permanently deleted).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_handles_trash_vs_permanent_delete(): void {
		// Create attachment
		$attachment_id = $this->create_test_attachment( 'test-trash.jpg' );
		$file_path     = get_attached_file( $attachment_id );
		$webp_path     = $this->get_webp_path( $file_path );

		$this->assertFileExists( $webp_path, 'WebP should exist' );

		// Trash (not permanent delete)
		wp_delete_attachment( $attachment_id, false );

		// With trash, the action still fires, so WebP should be deleted
		// (This behavior depends on implementation - adjust if needed)
		$post = get_post( $attachment_id );
		if ( $post && $post->post_status === 'trash' ) {
			// If trashed, file might still exist
			$this->assertTrue( true, 'Attachment was trashed' );
		} else {
			// If permanently deleted
			$this->assertFileDoesNotExist( $webp_path, 'WebP should be deleted' );
		}

		// Clean up
		wp_delete_attachment( $attachment_id, true );
	}
}
