<?php
/**
 * Handles AJAX actions for the bulk conversion WebP process and progress bar.
 *
 * @package PoetryConvertToWebp
 * @since 1.0.0
 */

namespace PoetryConvertToWebp\Admin;

use PoetryConvertToWebp\Utils\Helpers;
use PoetryConvertToWebp\Utils\Converter;
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
 * Class BulkConvert
 *
 * Handles AJAX actions for converting existing images to WebP format.
 *
 * @since 1.0.0
 */
class BulkConvert {


	/**
	 * Holds the Singleton instance.
	 *
	 * @since 1.0.0
	 * @var BulkConvert|null The Singleton instance.
	 */
	protected static ?BulkConvert $instance = null;

	/**
	 * Constructor to initialize the class.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		$this->init();
	}

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
	 * Returns the Singleton instance of the plugin.
	 *
	 * @since 1.0.0
	 * @return BulkConvert The Singleton instance.
	 */
	public static function get_instance(): BulkConvert {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Registers AJAX actions for the BulkConvert conversion process.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function init(): void {
		add_action( 'wp_ajax_get_attachments', [ __CLASS__, 'get_attachments' ] );
		add_action( 'wp_ajax_convert', [ __CLASS__, 'convert' ] );
	}

	/**
	 * AJAX handler to get all image attachment IDs for conversion.
	 *
	 * Checks nonce for security, fetches attachments using Helpers::get_attachments(),
	 * and returns them as a JSON response.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function get_attachments(): void {
		// Verify user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Access denied.', 'poetry-convert-to-webp' ) ] );
		}

		check_ajax_referer( 'poetry_convert_to_webp_ajax' );
		$attachments = (array) Helpers::get_attachments();
		wp_send_json_success( [ 'attachments' => $attachments ] );
	}

	/**
	 * AJAX handler to convert a single image to WebP format.
	 *
	 * Checks nonce for security, gets attachment metadata,
	 * runs the conversion, and returns a message and CSS classes for UI feedback.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function convert(): void {
		// Verify user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Access denied.', 'poetry-convert-to-webp' ) ] );
		}

		check_ajax_referer( 'poetry_convert_to_webp_ajax' );

		// Validate and sanitize the attachment ID
		if ( ! isset( $_POST['attachment_id'] ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid attachment ID.', 'poetry-convert-to-webp' ) ] );
		}

		$attachment_id = intval( sanitize_text_field( wp_unslash( $_POST['attachment_id'] ) ) );

		if ( $attachment_id <= 0 ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid attachment ID.', 'poetry-convert-to-webp' ) ] );
		}

		$metadata = wp_get_attachment_metadata( $attachment_id );

		// Ensure metadata is an array
		if ( false === $metadata ) {
			$metadata = [];
		}

		$converter = Converter::get_instance();
		$result    = (array) $converter->prepare( $attachment_id, $metadata );

		// Get message and classes from converter result for frontend display
		$message = (string) ( isset( $result[0]['message'] ) ? $result[0]['message'] : esc_html__( 'Done', 'poetry-convert-to-webp' ) );
		$classes = (array) ( isset( $result[0]['classes'] ) ? $result[0]['classes'] : [] );

		wp_send_json_success(
			[
				'message' => $message,
				'classes' => $classes,
			]
		);
	}
}
