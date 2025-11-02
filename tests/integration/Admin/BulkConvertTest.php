<?php
/**
 * Integration tests for Admin\BulkConvert class
 *
 * Tests AJAX handlers for bulk conversion of images.
 *
 * @package PoetryConvertToWebp\Tests
 * @since 1.0.0
 */

namespace PoetryConvertToWebp\Tests\Integration\Admin;

use PoetryConvertToWebp\Tests\IntegrationTestCase;
use PoetryConvertToWebp\Admin\BulkConvert;

/**
 * Class BulkConvertTest
 *
 * @since 1.0.0
 * @covers \PoetryConvertToWebp\Admin\BulkConvert
 */
class BulkConvertTest extends IntegrationTestCase {

	/**
	 * Instance of BulkConvert class
	 *
	 * @var BulkConvert
	 */
	protected BulkConvert $bulk_convert;

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
		$this->bulk_convert = BulkConvert::get_instance();
		$this->bulk_convert->init(); // Initialize hooks

		// Create admin user and set as current user
		$this->admin_user_id = $this->factory->user->create(
			[
				'role' => 'administrator',
			]
		);
		wp_set_current_user( $this->admin_user_id );
	}

	/**
	 * Test that BulkConvert class is a singleton.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_singleton_pattern(): void {
		$instance1 = BulkConvert::get_instance();
		$instance2 = BulkConvert::get_instance();

		$this->assertSame( $instance1, $instance2, 'BulkConvert should return the same instance' );
	}

	/**
	 * Test that AJAX actions are registered.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_ajax_actions_registered(): void {
		$this->assertIsInt(
			has_action( 'wp_ajax_get_attachments', [ BulkConvert::class, 'get_attachments' ] ),
			'get_attachments AJAX action should be registered'
		);

		$this->assertIsInt(
			has_action( 'wp_ajax_convert', [ BulkConvert::class, 'convert' ] ),
			'convert AJAX action should be registered'
		);
	}

	/**
	 * Test get_attachments AJAX handler returns attachment IDs.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_get_attachments_returns_ids(): void {
		// Create test attachments
		$attachment_ids = [];
		for ( $i = 1; $i <= 3; $i++ ) {
			$attachment_ids[] = $this->create_test_attachment( "ajax-test-{$i}.jpg" );
		}

		// Set up AJAX request
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'poetry_convert_to_webp_ajax' );

		// Capture output
		ob_start();
		try {
			BulkConvert::get_attachments();
		} catch ( \WPAjaxDieContinueException $e ) {
			// Expected exception from wp_send_json_success()
		}
		$output = ob_get_clean();

		$this->assertNotEmpty( $output, 'AJAX response should not be empty' );

		// Decode JSON response
		$response = json_decode( $output, true );

		$this->assertIsArray( $response, 'Response should be valid JSON array' );
		$this->assertTrue( $response['success'], 'Response should be successful' );
		$this->assertArrayHasKey( 'data', $response, 'Response should have data' );
		$this->assertArrayHasKey( 'attachments', $response['data'], 'Data should have attachments' );
		$this->assertIsArray( $response['data']['attachments'], 'Attachments should be an array' );

		// Verify our attachments are included
		foreach ( $attachment_ids as $attachment_id ) {
			$this->assertContains(
				$attachment_id,
				$response['data']['attachments'],
				"Attachment {$attachment_id} should be in the list"
			);
		}
	}

	/**
	 * Test get_attachments requires admin capabilities.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_get_attachments_requires_admin(): void {
		// Set current user to subscriber
		$subscriber_id = $this->factory->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber_id );

		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'poetry_convert_to_webp_ajax' );

		ob_start();
		try {
			BulkConvert::get_attachments();
		} catch ( \WPAjaxDieStopException $e ) {
			// Expected exception from wp_send_json_error()
		}
		$output = ob_get_clean();

		$response = json_decode( $output, true );

		$this->assertFalse( $response['success'], 'Should fail for non-admin user' );
		$this->assertArrayHasKey( 'data', $response, 'Response should have error data' );
	}

	/**
	 * Test get_attachments requires valid nonce.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_get_attachments_requires_nonce(): void {
		// Invalid nonce
		$_REQUEST['_ajax_nonce'] = 'invalid_nonce';

		$this->expectException( \WPAjaxDieStopException::class );

		BulkConvert::get_attachments();
	}

	/**
	 * Test convert AJAX handler converts single image.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_convert_ajax_handler(): void {
		// First, manually delete WebP to test conversion
		$attachment_id = $this->create_test_attachment( 'ajax-convert.jpg' );
		$file_path     = get_attached_file( $attachment_id );
		$webp_path     = $this->get_webp_path( $file_path );

		// Delete WebP if it exists
		if ( file_exists( $webp_path ) ) {
			unlink( $webp_path );
		}

		$this->assertFileDoesNotExist( $webp_path, 'WebP should not exist before AJAX conversion' );

		// Set up AJAX request
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'poetry_convert_to_webp_ajax' );
		$_POST['attachment_id']  = $attachment_id;

		ob_start();
		try {
			BulkConvert::convert();
		} catch ( \WPAjaxDieContinueException $e ) {
			// Expected exception from wp_send_json_success()
		}
		$output = ob_get_clean();

		$response = json_decode( $output, true );

		$this->assertTrue( $response['success'], 'Conversion should be successful' );
		$this->assertArrayHasKey( 'data', $response, 'Response should have data' );
		$this->assertArrayHasKey( 'message', $response['data'], 'Data should have message' );
		$this->assertArrayHasKey( 'classes', $response['data'], 'Data should have classes' );

		// Verify WebP was created
		$this->assertFileExists( $webp_path, 'WebP should be created by AJAX handler' );
	}

	/**
	 * Test convert requires valid attachment ID.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_convert_requires_valid_attachment_id(): void {
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'poetry_convert_to_webp_ajax' );
		$_POST['attachment_id']  = 'invalid';

		ob_start();
		try {
			BulkConvert::convert();
		} catch ( \WPAjaxDieStopException $e ) {
			// Expected exception from wp_send_json_error()
		}
		$output = ob_get_clean();

		$response = json_decode( $output, true );

		$this->assertFalse( $response['success'], 'Should fail with invalid attachment ID' );
	}

	/**
	 * Test convert requires admin capabilities.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_convert_requires_admin(): void {
		$attachment_id = $this->create_test_attachment( 'test-auth.jpg' );

		// Set current user to subscriber
		$subscriber_id = $this->factory->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber_id );

		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'poetry_convert_to_webp_ajax' );
		$_POST['attachment_id']  = $attachment_id;

		ob_start();
		try {
			BulkConvert::convert();
		} catch ( \WPAjaxDieStopException $e ) {
			// Expected exception from wp_send_json_error()
		}
		$output = ob_get_clean();

		$response = json_decode( $output, true );

		$this->assertFalse( $response['success'], 'Should fail for non-admin user' );
	}

	/**
	 * Test convert handles missing attachment ID parameter.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_convert_handles_missing_parameter(): void {
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'poetry_convert_to_webp_ajax' );
		// Don't set $_POST['attachment_id']

		ob_start();
		try {
			BulkConvert::convert();
		} catch ( \WPAjaxDieStopException $e ) {
			// Expected exception from wp_send_json_error()
		}
		$output = ob_get_clean();

		$response = json_decode( $output, true );

		$this->assertFalse( $response['success'], 'Should fail when attachment_id is missing' );
	}

	/**
	 * Test convert handles non-existent attachment.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_convert_handles_nonexistent_attachment(): void {
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'poetry_convert_to_webp_ajax' );
		$_POST['attachment_id']  = 99999; // Non-existent

		ob_start();
		try {
			BulkConvert::convert();
		} catch ( \WPAjaxDieContinueException $e ) {
			// May succeed with empty result or fail gracefully
		}
		$output = ob_get_clean();

		$this->assertNotEmpty( $output, 'Should return some response' );
		$response = json_decode( $output, true );
		$this->assertIsArray( $response, 'Response should be valid JSON' );
	}

	/**
	 * Test bulk conversion workflow.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_bulk_conversion_workflow(): void {
		// Create multiple attachments
		$attachment_ids = [];
		for ( $i = 1; $i <= 3; $i++ ) {
			$id               = $this->create_test_attachment( "workflow-{$i}.jpg" );
			$attachment_ids[] = $id;

			// Delete WebP to simulate unconverted images
			$file_path = get_attached_file( $id );
			$webp_path = $this->get_webp_path( $file_path );
			if ( file_exists( $webp_path ) ) {
				unlink( $webp_path );
			}
		}

		// Step 1: Get attachments
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'poetry_convert_to_webp_ajax' );

		ob_start();
		try {
			BulkConvert::get_attachments();
		} catch ( \WPAjaxDieContinueException $e ) {
			// Expected
		}
		$output = ob_get_clean();

		$response = json_decode( $output, true );
		$this->assertTrue( $response['success'], 'Step 1: Get attachments should succeed' );

		// Step 2: Convert each attachment
		foreach ( $attachment_ids as $attachment_id ) {
			$_POST['attachment_id'] = $attachment_id;

			ob_start();
			try {
				BulkConvert::convert();
			} catch ( \WPAjaxDieContinueException $e ) {
				// Expected
			}
			$output = ob_get_clean();

			$response = json_decode( $output, true );
			$this->assertTrue( $response['success'], "Step 2: Convert attachment {$attachment_id} should succeed" );

			// Verify WebP created
			$file_path = get_attached_file( $attachment_id );
			$webp_path = $this->get_webp_path( $file_path );
			$this->assertFileExists( $webp_path, "WebP should exist for attachment {$attachment_id}" );
		}
	}

	/**
	 * Test response structure.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_response_structure(): void {
		$attachment_id = $this->create_test_attachment( 'test-response.jpg' );

		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'poetry_convert_to_webp_ajax' );
		$_POST['attachment_id']  = $attachment_id;

		ob_start();
		try {
			BulkConvert::convert();
		} catch ( \WPAjaxDieContinueException $e ) {
			// Expected
		}
		$output = ob_get_clean();

		$response = json_decode( $output, true );

		// Verify structure
		$this->assertArrayHasKey( 'success', $response, 'Response should have success field' );
		$this->assertArrayHasKey( 'data', $response, 'Response should have data field' );
		$this->assertArrayHasKey( 'message', $response['data'], 'Data should have message' );
		$this->assertArrayHasKey( 'classes', $response['data'], 'Data should have classes' );

		$this->assertIsString( $response['data']['message'], 'Message should be a string' );
		$this->assertIsArray( $response['data']['classes'], 'Classes should be an array' );
	}

	/**
	 * Cleanup after tests.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function tear_down(): void {
		// Clean up $_REQUEST and $_POST
		unset( $_REQUEST['_ajax_nonce'] );
		unset( $_POST['attachment_id'] );

		parent::tear_down();
	}
}
