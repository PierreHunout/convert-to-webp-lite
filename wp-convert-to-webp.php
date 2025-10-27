<?php
/**
 * Plugin Name:       Convert to WebP
 * Plugin Slug:       wp-convert-to-webp
 * Plugin URI:        https://github.com/PierreHunout/wp-convert-to-webp
 * Description:       Automatically convert images to WebP format upon upload in WordPress. Improve website performance with optimized images.
 * Version:           1.0.0
 * Author:            Pierre Hunout
 * Author URI:        https://github.com/PierreHunout
 * Text Domain:       wp-convert-to-webp
 * Domain Path:      /languages
 * License:           GPL-3.0
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * GitHub Plugin URI:  PierreHunout/wp-convert-to-webp
 * 
 * @since             1.0.0
 * @package           WPConvertToWebp
 */

namespace WpConvertToWebp;

use WpConvertToWebp\Actions\Deactivate;
use WpConvertToWebp\Actions\Uninstall;
use RuntimeException;
use Throwable;
use ReflectionClass;

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
 * Define plugin constants for version, file, path, slug, CSS, and JS URLs.
 *
 * @since 1.0.0
 */
define( 'WP_CONVERT_TO_WEBP_VERSION', '1.0.0' );
define( 'WP_CONVERT_TO_WEBP_FILE', __FILE__ );
define( 'WP_CONVERT_TO_WEBP_PATH', plugin_dir_path( WP_CONVERT_TO_WEBP_FILE ) );
define( 'WP_CONVERT_TO_WEBP_BASENAME', plugin_basename( WP_CONVERT_TO_WEBP_FILE ) );
define( 'WP_CONVERT_TO_WEBP_SLUG', dirname( WP_CONVERT_TO_WEBP_BASENAME ) );
define( 'WP_CONVERT_TO_WEBP_CSS', plugins_url( 'assets/css/', __FILE__ ) );
define( 'WP_CONVERT_TO_WEBP_JS', plugins_url( 'assets/js/', __FILE__ ) );

/**
 * Optionally include Composer autoload if available.
 *
 * @since 1.0.0
 */
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Main plugin class for Convert to WebP.
 *
 * Implements the Singleton pattern to ensure a single instance.
 * Handles plugin loading, file inclusion, and asset enqueueing.
 */
class WpConvertToWebp {

	/**
	 * Holds the Singleton instance.
	 *
	 * @since 1.0.0
	 * @var WpConvertToWebp|null The Singleton instance.
	 */
	protected static ?WpConvertToWebp $instance = null;

	/**
	 * Private constructor to prevent direct instantiation.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Prevent cloning of the class
	 *
	 * @since 1.0.0
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization of the class
	 *
	 * @since 1.0.0
	 * @throws RuntimeException Always throws exception to prevent unserialization.
	 */
	public function __wakeup() {
		throw new RuntimeException( 'Cannot unserialize a singleton.' );
	}

	/**
	 * Returns the Singleton instance of the plugin.
	 *
	 * @since 1.0.0
	 * @return WpConvertToWebp The Singleton instance.
	 */
	public static function get_instance(): WpConvertToWebp {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Called when the plugin is loaded.
	 * Sets up actions, enqueues assets, and loads plugin files.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init(): void {
		self::enqueue();
		self::autoload();

		// Register hooks for deactivation and uninstall
		register_deactivation_hook( __FILE__, [ Deactivate::class, 'deactivate' ] );
		register_uninstall_hook( __FILE__, [ Uninstall::class, 'uninstall' ] );

		// Add plugin action links
		add_filter( 'plugin_action_links_' . WP_CONVERT_TO_WEBP_BASENAME, [ __CLASS__, 'add_action_links' ] );
	}

	/**
	 * Loads and runs all PHP files in the 'includes' directory.
	 *
	 * Scans subdirectories for PHP files, instantiates each class,
	 * and calls its `run` method if available.
	 *
	 * @since 1.0.0
	 * @throws RuntimeException If the includes directory doesn't exist.
	 * @return void
	 */
	public static function autoload(): void {
		try {
			$path = (string) WP_CONVERT_TO_WEBP_PATH . 'includes/';

			// Check if the includes directory exists
			if ( ! is_dir( $path ) ) {
				// translators: %s is the folder path that doesn't exist
				throw new RuntimeException( sprintf( __( 'The folder at %s does not exist', 'wp-convert-to-webp' ), $path ) );
			}

			// Normalize the base path for security checks.
			$normalized_base = (string) realpath( $path );

			if ( false === $normalized_base ) {
				throw new RuntimeException( __( 'Unable to resolve includes directory path', 'wp-convert-to-webp' ) );
			}

			// Get all subdirectories in the includes folder
			$directories = (array) array_diff( scandir( $path ), [ '.', '..' ] );

			foreach ( $directories as $directory ) {
				$dir = (string) $path . $directory;

				// Only process if it's a directory.
				if ( ! is_dir( $dir ) ) {
					continue;
				}

				// Security check: ensure we're still within the plugin directory.
				$normalized_dir = (string) realpath( $dir );
				if ( false === $normalized_dir || 0 !== strpos( $normalized_dir, $normalized_base ) ) {
					continue;
				}

				// Get all files in the subdirectory.
				$files = (array) array_diff( scandir( $dir ), [ '.', '..' ] );

				// Loop through each file in the directory.
				foreach ( $files as $file ) {
					// Enhanced validation: only allow proper PHP class files.
					if ( ! preg_match( '/^[A-Z][a-zA-Z0-9]*\.php$/', $file ) ) {
						continue;
					}

					$filepath = (string) $dir . DIRECTORY_SEPARATOR . $file;

					// Security check: ensure file is within expected directory.
					$normalized_file = (string) realpath( $filepath );
					if ( false === $normalized_file || 0 !== strpos( $normalized_file, $normalized_dir ) ) {
						continue;
					}

					// Check if file is readable.
					if ( ! is_readable( $filepath ) ) {
						continue;
					}

					// Get the class name based on folder and file name.
					$name  = (string) basename( $file, '.php' );
					$class = (string) 'WpConvertToWebp\\' . $directory . '\\' . $name;

					// Enhanced class validation.
					if ( ! class_exists( $class ) || 0 !== strpos( $class, 'WpConvertToWebp' ) ) {
						continue;
					}

					// Check if class is safe to instantiate (avoid utility classes).
					$reflection = (object) new ReflectionClass( $class );
					if ( $reflection->isAbstract() || $reflection->isTrait() || $reflection->isInterface() ) {
						continue;
					}

					// Check if constructor is private (utility classes).
					$constructor = (object) $reflection->getConstructor();

					if ( null !== $constructor && ! $constructor->isPublic() ) {
						continue;
					}

					try {
						$instance = (object) new $class();
						if ( method_exists( $instance, 'init' ) && is_callable( [ $instance, 'init' ] ) ) {
							// Call the init method of the class.
							$instance->init();
						}
					} catch ( Throwable $inner_error ) {
						if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
							// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
							error_log(
								sprintf(
									// translators: %1$s is the class name, %2$s is the error message, %3$s is the filename, %4$d is the line number
									__( '[WP Convert to WebP] Error running %1$s: %2$s in %3$s on line %4$d', 'wp-convert-to-webp' ),
									$class,
									$inner_error->getMessage(),
									basename( $inner_error->getFile() ),
									$inner_error->getLine()
								)
							);
						}
					}
				}
			}
		} catch ( Throwable $error ) {
			// Log error if WP_DEBUG is enabled
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging when WP_DEBUG is enabled
				error_log(
					sprintf(
						// translators: %1$s is the error message, %2$s is the filename, %3$d is the line number
						__( '[WP Convert to WebP] Error in autoload: %1$s in %2$s on line %3$d', 'wp-convert-to-webp' ),
						$error->getMessage(),
						basename( $error->getFile() ),
						$error->getLine()
					)
				);
			}
		}
	}

	/**
	 * Hooks into 'admin_enqueue_scripts' to enqueue plugin styles and scripts.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function enqueue(): void {
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_enqueue' ], 1 );
	}

	/**
	 * Enqueues the plugin's CSS and JS files for the admin area.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function admin_enqueue(): void {
		wp_enqueue_style( 'wp-convert-to-webp', WP_CONVERT_TO_WEBP_CSS . 'styles.css', [], WP_CONVERT_TO_WEBP_VERSION, 'all' );
		wp_enqueue_script( 'wp-convert-to-webp', WP_CONVERT_TO_WEBP_JS . 'scripts.js', [], WP_CONVERT_TO_WEBP_VERSION, true );
		wp_enqueue_script( 'wp-convert-to-webp-ajax', WP_CONVERT_TO_WEBP_JS . 'ajax.js', [], WP_CONVERT_TO_WEBP_VERSION, true );

		wp_localize_script( 'wp-convert-to-webp-ajax', 'wpConvertToWebp', [ 'nonce' => wp_create_nonce( 'convert_to_webp_ajax' ) ] );
	}

	/**
	 * Add custom action links to the plugin page.
	 *
	 * Adds a "Settings" link to the plugin row in the WordPress plugins page.
	 *
	 * @since 1.0.0
	 * @param array $links Existing plugin action links.
	 * @return array Modified plugin action links.
	 */
	public static function add_action_links( array $links ): array {
		$links[] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=wp-convert-to-webp' ) ),
			esc_html__( 'Settings', 'wp-convert-to-webp' )
		);

		return $links;
	}
}

/**
 * Fires when the plugin is loaded.
 * Instantiates the plugin and calls its loading method.
 *
 * @since 1.0.0
 */
add_action(
	'plugin_loaded',
	function () {
		return WpConvertToWebp::get_instance();
	}
);
