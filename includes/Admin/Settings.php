<?php
/**
 * Manages the settings page and admin actions for the Convert to WebP Lite plugin.
 *
 * @package ConvertToWebpLite
 * @since 1.0.0
 */

namespace ConvertToWebpLite\Admin;

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
 * Class Options
 *
 * Manages the plugin options page and admin interface.
 *
 * @since 1.0.0
 */
class Settings {

	/**
	 * Holds the Singleton instance.
	 *
	 * @since 1.0.0
	 * @var Settings|null The Singleton instance.
	 */
	protected static ?Settings $instance = null;

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
	 * @return Settings The Singleton instance.
	 */
	public static function get_instance(): Settings {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Class Runner for the WebP conversion settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', [ __CLASS__, 'add_settings' ] );
		add_action( 'admin_init', [ __CLASS__, 'save_settings' ] );
	}

	/**
	 * Adds the plugin options page to the WordPress admin menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function add_settings(): void {
		add_menu_page(
			__( 'WebP Conversion', 'convert-to-webp-lite' ),
			__( 'WebP Conversion', 'convert-to-webp-lite' ),
			'manage_options',
			'convert-to-webp-lite',
			[ __CLASS__, 'render_page' ],
			'dashicons-images-alt2',
			99
		);
	}

	/**
	 * Renders the plugin options page in the WordPress admin.
	 * Displays forms for options, conversion, deletion, and comparison UI.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function render_page(): void {
		// Enqueue media scripts for image selector
		wp_enqueue_media();

		// Define allowed HTML tags for wp_kses once
		$allowed_html = (array) [
			'strong' => [],
			'code'   => [],
		];

		$webp_quality         = (int) get_option( 'convert_to_webp_lite_quality', 85 );
		$replace_mode         = (bool) get_option( 'convert_to_webp_lite_replace_mode', false );
		$debug_mode           = (bool) get_option( 'convert_to_webp_lite_debug_mode', false );
		$delete_on_deactivate = (bool) get_option( 'delete_webp_on_deactivate', false );
		$delete_on_uninstall  = (bool) get_option( 'delete_webp_on_uninstall', false );
		?>
		<div class="wrap convert-to-webp-lite">
			<h1 class="convert-to-webp-lite__title"><?php esc_html_e( 'Manage WebP Conversion', 'convert-to-webp-lite' ); ?></h1>
			<div id="convert-to-webp-lite-grid" class="convert-to-webp-lite__grid convert-to-webp-lite__grid--main">
				<div class="convert-to-webp-lite__forms">
					<!-- Options form -->
					<form method="post" action="" class="convert-to-webp-lite__form convert-to-webp-lite__form--options">
						<?php wp_nonce_field( 'convert_to_webp_lite_save_options' ); ?>
						<div class="convert-to-webp-lite__table">
							<div class="convert-to-webp-lite__row">
								<h2 class="convert-to-webp-lite__subtitle"><?php esc_html_e( 'WebP Quality', 'convert-to-webp-lite' ); ?></h2>
								<div class="convert-to-webp-lite__inputs">
									<input type="number" 
										id="convert_to_webp_lite_quality" 
										class="convert-to-webp-lite__input convert-to-webp-lite__input--number" 
										name="convert_to_webp_lite_quality" 
										min="0" 
										max="100" 
										value="<?php echo esc_attr( $webp_quality ); ?>">
									<input type="range" 
										id="convert_to_webp_lite_quality_slider" 
										class="convert-to-webp-lite__input convert-to-webp-lite__input--range" 
										min="0" 
										max="100" 
										value="<?php echo esc_attr( $webp_quality ); ?>" 
										oninput="document.getElementById('convert_to_webp_lite_quality').value = this.value;">
								</div>
								<p class="convert-to-webp-lite__description">
									<?php esc_html_e( 'Higher means better quality but larger files.', 'convert-to-webp-lite' ); ?>
								</p>
							</div>

							<div class="convert-to-webp-lite__row">
								<h2 class="convert-to-webp-lite__subtitle"><?php esc_html_e( 'Replace mode', 'convert-to-webp-lite' ); ?></h2>
								<p class="convert-to-webp-lite__description">
									<?php
									echo wp_kses( __( 'Enable replacement of <strong>&#60;img&#62;</strong> tags with <strong>&#60;picture&#62;</strong> tags.', 'convert-to-webp-lite' ), $allowed_html );
									?>
								</p>
								<div class="convert-to-webp-lite__inputs">
									<input type="checkbox" class="convert-to-webp-lite__input convert-to-webp-lite__input--toggle" name="convert_to_webp_lite_replace_mode" value="1" <?php checked( $replace_mode, 1 ); ?> />
									<p class="convert-to-webp-lite__label">
										<?php
										echo wp_kses( __( 'Use <strong>&#60;picture&#62;</strong> tags', 'convert-to-webp-lite' ), $allowed_html );
										?>
									</p>
								</div>
								<p class="convert-to-webp-lite__description">
									<?php
									echo wp_kses( __( 'If enabled, all images will be replaced by their WebP versions inside the <strong>&#60;picture&#62;</strong> tags. Otherwise, the original <strong>&#60;img&#62;</strong> tags will be used.', 'convert-to-webp-lite' ), $allowed_html );
									?>
								</p>
							</div>

							<div class="convert-to-webp-lite__row">
								<h2 class="convert-to-webp-lite__subtitle"><?php esc_html_e( 'Debug mode', 'convert-to-webp-lite' ); ?></h2>
								<p class="convert-to-webp-lite__description">
									<?php
									echo wp_kses( __( 'Enable debug mode to log additional information during the conversion/deletion process.', 'convert-to-webp-lite' ), $allowed_html );
									?>
								</p>
								<div class="convert-to-webp-lite__inputs">
									<input type="checkbox" class="convert-to-webp-lite__input convert-to-webp-lite__input--toggle" name="convert_to_webp_lite_debug_mode" value="1" <?php checked( $debug_mode, 1 ); ?> />
									<p class="convert-to-webp-lite__label">
										<?php
										echo wp_kses( __( 'Enable debug logging', 'convert-to-webp-lite' ), $allowed_html );
										?>
									</p>
								</div>
								<p class="convert-to-webp-lite__description convert-to-webp-lite__description--info">
									<?php
									echo wp_kses( __( ' Logs can be viewed in the <code>convert-to-webp-lite-logs</code> folder, in your <code>wp-content</code> folder.', 'convert-to-webp-lite' ), $allowed_html );
									?>
								</p>
							</div>

							<div class="convert-to-webp-lite__row">
								<h2 class="convert-to-webp-lite__subtitle">
									<?php
									echo wp_kses( __( 'Clean data on <strong>deactivate</strong>', 'convert-to-webp-lite' ), $allowed_html );
									?>
								</h2>
								<div class="convert-to-webp-lite__inputs">
									<input type="checkbox" class="convert-to-webp-lite__input convert-to-webp-lite__input--toggle" name="delete_webp_on_deactivate" value="1" <?php checked( $delete_on_deactivate, 1 ); ?> />
									<p class="convert-to-webp-lite__label"><?php esc_html_e( 'Delete WebP files and options', 'convert-to-webp-lite' ); ?></p>
								</div>
							</div>

							<div class="convert-to-webp-lite__row">
								<h2 class="convert-to-webp-lite__subtitle">
									<?php
									echo wp_kses( __( 'Clean data on <strong>uninstall</strong>', 'convert-to-webp-lite' ), $allowed_html );
									?>
								</h2>
								<div class="convert-to-webp-lite__inputs">
									<input type="checkbox" class="convert-to-webp-lite__input convert-to-webp-lite__input--toggle" name="delete_webp_on_uninstall" value="1" <?php checked( $delete_on_uninstall, 1 ); ?> />
									<p class="convert-to-webp-lite__label"><?php esc_html_e( 'Delete WebP files and options', 'convert-to-webp-lite' ); ?></p>
								</div>
							</div>
						</div>
						<div class="convert-to-webp-lite__submit">
							<input type="hidden" name="action" value="save_options">
							<button type="submit" class="button button-primary convert-to-webp-lite__button convert-to-webp-lite__button--primary"><?php esc_html_e( 'Save options', 'convert-to-webp-lite' ); ?></button>
						</div>
					</form>

					<!-- Legacy conversion form -->
					<div class="convert-to-webp-lite__form convert-to-webp-lite__form--legacy">
						<div class="convert-to-webp-lite__table">
							<div class="convert-to-webp-lite__row">
								<h2 class="convert-to-webp-lite__subtitle"><?php esc_html_e( 'Convert old images', 'convert-to-webp-lite' ); ?></h2>
								<p class="convert-to-webp-lite__description">
									<?php esc_html_e( 'This will convert all existing images in your media library to WebP format. New images will be converted automatically upon upload.', 'convert-to-webp-lite' ); ?>
								</p>
								<div class="convert-to-webp-lite__submit convert-to-webp-lite__submit--secondary">
									<input type="hidden" name="action" value="convert_to_webp_lite_legacy">
									<button type="submit" id="convert-to-webp-lite-legacy" class="button convert-to-webp-lite__button"><?php esc_html_e( 'Convert previously uploaded images', 'convert-to-webp-lite' ); ?></button>
								</div>
							</div>
						</div>
					</div>

					<!-- Delete all WebP files form -->
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="convert-to-webp-lite__form convert-to-webp-lite__form--delete" onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete all WebP files?', 'convert-to-webp-lite' ) ); ?>');">
						<?php wp_nonce_field( 'delete_all_webp' ); ?>
						<div class="convert-to-webp-lite__table">
							<div class="convert-to-webp-lite__row">
								<h2 class="convert-to-webp-lite__subtitle"><?php esc_html_e( 'Delete all WebP files', 'convert-to-webp-lite' ); ?></h2>
								<div class="convert-to-webp-lite__submit convert-to-webp-lite__submit--secondary">
									<input type="hidden" name="action" value="delete_all_webp">
									<button type="submit" id="convert-to-webp-lite-delete-all" class="button button-danger convert-to-webp-lite__button convert-to-webp-lite__button--danger"><?php esc_html_e( 'Delete all WebP files', 'convert-to-webp-lite' ); ?></button>
								</div>
							</div>
						</div>
					</form>
				</div>

				<!-- Image comparison UI -->
				<div class="convert-to-webp-lite__comparison">
					<!-- Image Selector for Comparison UI -->
					<form method="post" action="" id="comparison-form" class="convert-to-webp-lite__form convert-to-webp-lite__form--comparison">
						<?php wp_nonce_field( 'convert_to_webp_lite_comparison' ); ?>
						<div class="convert-to-webp-lite__table">
							<div class="convert-to-webp-lite__row">
								<h2 class="convert-to-webp-lite__subtitle"><?php esc_html_e( 'Select an Image for Comparison', 'convert-to-webp-lite' ); ?></h2>
								<div class="convert-to-webp-lite__submit convert-to-webp-lite__submit--secondary">
									<button type="button" class="button convert-to-webp-lite__button" id="comparison-button"><?php esc_html_e( 'Select Image', 'convert-to-webp-lite' ); ?></button>
								</div>
							</div>
						</div>
					</form>

					<!-- Comparison UI for original vs WebP image -->
					<div id="comparison-container" class="convert-to-webp-lite__compare">
						<div class="convert-to-webp-lite__images">
							<img id="comparison-original" class="convert-to-webp-lite__image convert-to-webp-lite__image--origin" src="">
							<img id="comparison-webp" class="convert-to-webp-lite__image convert-to-webp-lite__image--webp" src="">
						</div>
						<input type="range" min="0" max="100" value="50" id="comparison-range" class="convert-to-webp-lite__range">
						<div class="convert-to-webp-lite__handler"></div>
						<div class="convert-to-webp-lite__sizes">
							<p class="convert-to-webp-lite__size">Original size: <strong id="comparison-original-size"></strong></p>
							<p class="convert-to-webp-lite__size">WebP size: <strong id="comparison-webp-size"></strong></p>
						</div>
					</div>
				</div>
			</div>

			<!-- Popup for conversion progress -->
			<div id="convert-to-webp-lite-progress-popup" class="convert-to-webp-lite__popup convert-to-webp-lite__popup--progress">
				<div class="convert-to-webp-lite__container">
					<div class="convert-to-webp-lite__inner convert-to-webp-lite__grid">
						<div class="convert-to-webp-lite__header">
							<h2 class="convert-to-webp-lite__subtitle"><?php esc_html_e( 'Conversion Progress', 'convert-to-webp-lite' ); ?></h2>
						</div>
						<div class="convert-to-webp-lite__content">
							<ul id="convert-to-webp-lite-progress-messages" class="convert-to-webp-lite__messages convert-to-webp-lite__messages--progress"></ul>
						</div>
						<div class="convert-to-webp-lite__sidebar">
							<div class="convert-to-webp-lite__chart convert-to-webp-lite__chart--donut">
								<canvas id="convert-to-webp-lite-progress-donut" width="120" height="120" style="display:block;margin:auto;"></canvas>
							</div>
						</div>
					</div>
				</div>
				<div id="convert-to-webp-lite-progress-close" class="convert-to-webp-lite__button convert-to-webp-lite__button--close"><?php esc_html_e( 'Close', 'convert-to-webp-lite' ); ?></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handles saving the plugin options from the admin form.
	 * Validates and saves quality and deletion options.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function save_settings(): void {
		// Verify user capabilities first
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if (
			isset( $_POST['action'] ) && sanitize_text_field( wp_unslash( $_POST['action'] ) ) === 'save_options'
			&& check_admin_referer( 'convert_to_webp_lite_save_options' )
		) {
			$quality = (int) ( isset( $_POST['convert_to_webp_lite_quality'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['convert_to_webp_lite_quality'] ) ) ) : 85 );

			// Ensure quality is within bounds
			if ( $quality < 0 ) {
				$quality = 0;
			}
			if ( $quality > 100 ) {
				$quality = 100;
			}

			$mode       = (int) isset( $_POST['convert_to_webp_lite_replace_mode'] ) && sanitize_text_field( wp_unslash( $_POST['convert_to_webp_lite_replace_mode'] ) ) === '1' ? 1 : 0;
			$debug_mode = (int) isset( $_POST['convert_to_webp_lite_debug_mode'] ) && sanitize_text_field( wp_unslash( $_POST['convert_to_webp_lite_debug_mode'] ) ) === '1' ? 1 : 0;
			$deactivate = (int) isset( $_POST['delete_webp_on_deactivate'] ) && sanitize_text_field( wp_unslash( $_POST['delete_webp_on_deactivate'] ) ) === '1' ? 1 : 0;
			$uninstall  = (int) isset( $_POST['delete_webp_on_uninstall'] ) && sanitize_text_field( wp_unslash( $_POST['delete_webp_on_uninstall'] ) ) === '1' ? 1 : 0;

			update_option( 'convert_to_webp_lite_quality', $quality );
			update_option( 'convert_to_webp_lite_replace_mode', $mode );
			update_option( 'convert_to_webp_lite_debug_mode', $debug_mode );
			update_option( 'delete_webp_on_deactivate', $deactivate );
			update_option( 'delete_webp_on_uninstall', $uninstall );

			// Add admin notice for successful save
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Options saved successfully.', 'convert-to-webp-lite' ) . '</p></div>';
				}
			);
		}
	}
}
