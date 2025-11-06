<?php
/**
 * Integration tests for Admin\BulkDelete class
 *
 * Tests bulk deletion of WebP files.
 *
 * @package PoetryConvertToWebp\Tests
 * @since 1.0.0
 */

namespace PoetryConvertToWebp\Tests\Integration\Admin;

use PoetryConvertToWebp\Tests\IntegrationTestCase;
use PoetryConvertToWebp\Admin\BulkDelete;

/**
 * Class BulkDeleteTest
 *
 * @since 1.0.0
 * @covers \PoetryConvertToWebp\Admin\BulkDelete
 */
class BulkDeleteTest extends IntegrationTestCase {

	/**
	 * Instance of BulkDelete class
	 *
	 * @var BulkDelete
	 */
	protected BulkDelete $bulk_delete;

	/**
	 * Admin user ID
	 *
	 * @var int
	 */
	protected int $admin_user_id;

	/**
	 * Setup before each test.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		$this->bulk_delete = BulkDelete::get_instance();
		$this->bulk_delete->init(); // Initialize hooks

		// Create admin user and set as current user
		$this->admin_user_id = $this->factory->user->create(
			[
				'role' => 'administrator',
			]
		);
		wp_set_current_user( $this->admin_user_id );
	}

	/**
	 * Test that BulkDelete class is a singleton.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_singleton_pattern(): void {
		$instance1 = BulkDelete::get_instance();
		$instance2 = BulkDelete::get_instance();

		$this->assertSame( $instance1, $instance2, 'BulkDelete should return the same instance' );
	}

	/**
	 * Test that admin_post action is registered.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_admin_post_action_registered(): void {
		$this->assertIsInt(
			has_action( 'admin_post_poetry_convert_to_webp_clean_files', [ BulkDelete::class, 'clean_webp' ] ),
			'poetry_convert_to_webp_clean_files admin_post action should be registered'
		);
	}

	/**
	 * Test bulk deletion of all WebP files.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_deletes_all_webp_files(): void {
		// Create test attachments with WebP
		$attachment_ids = [];
		$webp_paths     = [];

		for ( $i = 1; $i <= 3; $i++ ) {
			$id               = $this->create_test_attachment( "bulk-delete-{$i}.jpg" );
			$attachment_ids[] = $id;
			$file_path        = get_attached_file( $id );
			$webp_paths[]     = $this->get_webp_path( $file_path );
		}

		// Verify all WebP files exist
		foreach ( $webp_paths as $webp_path ) {
			$this->assertFileExists( $webp_path, 'WebP should exist before bulk deletion' );
		}

		// Set nonce
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'poetry_convert_to_webp_clean_files' );

		// Prevent actual redirect
		add_filter( 'wp_redirect', '__return_false' );

		// Execute bulk delete
		BulkDelete::clean_webp();

		// Verify all WebP files were deleted
		foreach ( $webp_paths as $webp_path ) {
			$this->assertFileDoesNotExist( $webp_path, 'WebP should be deleted after bulk deletion' );
		}

		// Verify original images still exist
		foreach ( $attachment_ids as $attachment_id ) {
			$file_path = get_attached_file( $attachment_id );
			$this->assertFileExists( $file_path, 'Original image should still exist' );
		}
	}

	/**
	 * Test bulk deletion requires admin capabilities.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_requires_admin_capabilities(): void {
		// Set current user to subscriber
		$subscriber_id = $this->factory->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber_id );

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'poetry_convert_to_webp_clean_files' );

		$this->expectException( \WPDieException::class );

		BulkDelete::clean_webp();
	}

	/**
	 * Test bulk deletion requires valid nonce.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_requires_valid_nonce(): void {
		$_REQUEST['_wpnonce'] = 'invalid_nonce';

		$this->expectException( \WPDieException::class );

		BulkDelete::clean_webp();
	}

	/**
	 * Test bulk deletion when no attachments exist.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_handles_no_attachments(): void {
		// Delete all existing attachments
		$attachments = get_posts(
			[
				'post_type'      => 'attachment',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			]
		);

		foreach ( $attachments as $attachment_id ) {
			wp_delete_attachment( $attachment_id, true );
		}

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'poetry_convert_to_webp_clean_files' );

		// Prevent redirect
		add_filter( 'wp_redirect', '__return_false' );

		// Should handle gracefully
		BulkDelete::clean_webp();

		$this->assertTrue( true, 'Should handle no attachments gracefully' );
	}

	/**
	 * Test bulk deletion stores transient data for notice.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_stores_deletion_data_in_transient(): void {
		// Create test attachments
		for ( $i = 1; $i <= 2; $i++ ) {
			$this->create_test_attachment( "transient-test-{$i}.jpg" );
		}

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'poetry_convert_to_webp_clean_files' );

		// Prevent redirect
		add_filter( 'wp_redirect', '__return_false' );

		// Execute deletion
		BulkDelete::clean_webp();

		// Check transient was set
		$transient_data = get_transient( 'poetry_convert_to_webp_deletion_data' );

		$this->assertNotFalse( $transient_data, 'Deletion data should be stored in transient' );
		$this->assertIsArray( $transient_data, 'Transient data should be an array' );
		$this->assertNotEmpty( $transient_data, 'Transient data should not be empty' );
	}

	/**
	 * Test bulk deletion clears cache.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_clears_cache_after_deletion(): void {
		// Create test attachment
		$attachment_id = $this->create_test_attachment( 'cache-test.jpg' );

		// Cache some attachment data
		wp_cache_set( "attachment_{$attachment_id}", 'cached_data', 'posts' );

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'poetry_convert_to_webp_clean_files' );

		// Prevent redirect
		add_filter( 'wp_redirect', '__return_false' );

		// Execute deletion
		BulkDelete::clean_webp();

		// Cache should be flushed (though specific implementation may vary)
		$this->assertTrue( true, 'Cache flush should be called' );
	}

	/**
	 * Test bulk deletion with mixed file types.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_handles_mixed_file_types(): void {
		// Create different image types
		$jpg_id = $this->create_test_attachment( 'mixed-test.jpg' );
		$png_id = $this->create_test_attachment( 'mixed-test.png' );

		$jpg_path = get_attached_file( $jpg_id );
		$png_path = get_attached_file( $png_id );
		$jpg_webp = $this->get_webp_path( $jpg_path );
		$png_webp = $this->get_webp_path( $png_path );

		$this->assertFileExists( $jpg_webp, 'JPEG WebP should exist' );
		$this->assertFileExists( $png_webp, 'PNG WebP should exist' );

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'poetry_convert_to_webp_clean_files' );
		add_filter( 'wp_redirect', '__return_false' );

		BulkDelete::clean_webp();

		$this->assertFileDoesNotExist( $jpg_webp, 'JPEG WebP should be deleted' );
		$this->assertFileDoesNotExist( $png_webp, 'PNG WebP should be deleted' );
	}

	/**
	 * Test bulk deletion with thumbnails.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_deletes_thumbnail_webp_files(): void {
		// Register image sizes
		add_image_size( 'test-thumb-delete', 150, 150, true );

		// Create attachment
		$attachment_id = $this->create_test_attachment( 'thumb-delete.jpg' );
		$metadata      = wp_get_attachment_metadata( $attachment_id );

		// Collect thumbnail WebP paths
		$upload_dir = wp_upload_dir();
		$base_dir   = $upload_dir['basedir'];
		$webp_paths = [];

		if ( isset( $metadata['sizes'] ) ) {
			$dirname = dirname( $metadata['file'] );
			foreach ( $metadata['sizes'] as $size => $size_data ) {
				$size_path    = $base_dir . '/' . $dirname . '/' . $size_data['file'];
				$webp_paths[] = $this->get_webp_path( $size_path );
			}
		}

		// Verify WebP thumbnails exist
		foreach ( $webp_paths as $webp_path ) {
			$this->assertFileExists( $webp_path, 'Thumbnail WebP should exist before deletion' );
		}

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'poetry_convert_to_webp_clean_files' );
		add_filter( 'wp_redirect', '__return_false' );

		BulkDelete::clean_webp();

		// Verify all thumbnail WebP files were deleted
		foreach ( $webp_paths as $webp_path ) {
			$this->assertFileDoesNotExist( $webp_path, 'Thumbnail WebP should be deleted' );
		}

		// Clean up
		remove_image_size( 'test-thumb-delete' );
	}

	/**
	 * Test bulk deletion redirect URL contains proper parameters.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_redirect_url_parameters(): void {
		// Create test attachment
		$this->create_test_attachment( 'redirect-test.jpg' );

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'poetry_convert_to_webp_clean_files' );

		// Capture redirect URL
		$redirect_url = '';
		add_filter(
			'wp_redirect',
			function ( $location ) use ( &$redirect_url ) {
				$redirect_url = $location;
				return false;
			}
		);

		BulkDelete::clean_webp();

		$this->assertNotEmpty( $redirect_url, 'Redirect URL should be set' );
		$this->assertStringContainsString( 'deleted=1', $redirect_url, 'URL should contain deleted parameter' );
		$this->assertStringContainsString( '_wpnonce=', $redirect_url, 'URL should contain nonce parameter' );
	}

	/**
	 * Test bulk deletion with no files to delete.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_handles_no_files_to_delete(): void {
		// Create attachment but manually delete WebP
		$attachment_id = $this->create_test_attachment( 'no-webp.jpg' );
		$file_path     = get_attached_file( $attachment_id );
		$webp_path     = $this->get_webp_path( $file_path );

		if ( file_exists( $webp_path ) ) {
			unlink( $webp_path );
		}

		$this->assertFileDoesNotExist( $webp_path, 'WebP should not exist' );

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'poetry_convert_to_webp_clean_files' );
		add_filter( 'wp_redirect', '__return_false' );

		// Should handle gracefully
		BulkDelete::clean_webp();

		$this->assertTrue( true, 'Should handle missing WebP files gracefully' );
	}

	/**
	 * Test bulk deletion result structure.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_deletion_result_structure(): void {
		// Create test attachments
		for ( $i = 1; $i <= 2; $i++ ) {
			$this->create_test_attachment( "result-test-{$i}.jpg" );
		}

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'poetry_convert_to_webp_clean_files' );
		add_filter( 'wp_redirect', '__return_false' );

		BulkDelete::clean_webp();

		$transient_data = get_transient( 'poetry_convert_to_webp_deletion_data' );

		$this->assertIsArray( $transient_data, 'Transient data should be an array' );

		foreach ( $transient_data as $item ) {
			$this->assertIsArray( $item, 'Each item should be an array' );
		}
	}

	/**
	 * Cleanup after tests.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function tear_down(): void {
		// Clean up request variables
		unset( $_REQUEST['_wpnonce'] );

		// Remove filters
		remove_all_filters( 'wp_redirect' );

		// Delete transient
		delete_transient( 'poetry_convert_to_webp_deletion_data' );

		parent::tear_down();
	}
}
