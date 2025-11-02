<?php
/**
 * Displays admin notices for the Poetry Convert to WebP plugin.
 *
 * @package PoetryConvertToWebp
 * @since 1.0.0
 */

namespace PoetryConvertToWebp\Admin;

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
 * Class Notices
 *
 * Handles the display of admin notices for the plugin.
 *
 * @since 1.0.0
 */
class Notices {

	/**
	 * Holds the Singleton instance.
	 *
	 * @since 1.0.0
	 * @var Notices|null The Singleton instance.
	 */
	protected static ?Notices $instance = null;

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
	 * @return Notices The Singleton instance.
	 */
	public static function get_instance(): Notices {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Class Runner for the WebP conversion notices.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function init(): void {
		// Display admin notices for deletion results
		add_action( 'admin_notices', [ __CLASS__, 'display_notices' ] );
	}

	/**
	 * Displays admin notices for deletion results.
	 * Shows details for each processed file.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function display_notices(): void {
		// Only show notices on our plugin's admin page
		if ( ! isset( $_GET['page'] ) || sanitize_text_field( wp_unslash( $_GET['page'] ) ) !== 'poetry-convert-to-webp' ) {
			return;
		}

		// Verify user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Verify nonce for notice parameters to prevent tampering
		if (
			( isset( $_GET['no_files'] ) || isset( $_GET['deleted'] ) ) &&
			( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'poetry_convert_to_webp_notice' ) )
		) {
			return;
		}

		$title = (string) esc_html__( 'No files found to process.', 'poetry-convert-to-webp' );

		// No files found notice
		if ( isset( $_GET['no_files'] ) && sanitize_text_field( wp_unslash( $_GET['no_files'] ) ) === '1' ) {
			echo '<div class="notice is-dismissible poetry-convert-to-webp__notice poetry-convert-to-webp__notice--nofiles">
                <p>' . esc_html( $title ) . '</p>
            </div>';
		}

		// Deletion notice
		if ( isset( $_GET['deleted'] ) && sanitize_text_field( wp_unslash( $_GET['deleted'] ) ) === '1' ) {
			$title = (string) esc_html__( 'Deleted WebP files', 'poetry-convert-to-webp' );
			$data  = (array) get_transient( 'poetry_convert_to_webp_deletion_data' );
			delete_transient( 'poetry_convert_to_webp_deletion_data' );

			// Display notice if there is data
			if ( isset( $data ) && is_array( $data ) ) {
				$count = (int) count( $data );
				echo '<div class="notice is-dismissible poetry-convert-to-webp__notice poetry-convert-to-webp__notice--deletion">
                    <p class="poetry-convert-to-webp__subtitle">' . esc_html( $title ) . ': <strong>' . esc_html( $count ) . '</strong></p>
                    <div class="poetry-convert-to-webp__container poetry-convert-to-webp__container--notice">
                        <div class="poetry-convert-to-webp__inner poetry-convert-to-webp__inner--notice">
                ';

				foreach ( $data as $images ) {
					echo '<ul class="poetry-convert-to-webp__messages">';

					foreach ( $images as $image ) {
						$message = (string) $image['message'];
						$classes = (array) $image['classes'];

						$class_list = [];
						foreach ( $classes as $class ) {
							$class        = (string) 'poetry-convert-to-webp__message--' . sanitize_html_class( $class );
							$class_list[] = $class;
						}

						$classes = (string) implode( ' ', $class_list );

						$allowed_html = (array) [ 'span' => [] ];
						echo '<li class="poetry-convert-to-webp__message ' . esc_attr( $classes ) . '">' . wp_kses( $message, $allowed_html ) . '</li>';
					}

					echo '</ul>';
				}

				echo '</div></div></div>';
			}
		}
	}
}
