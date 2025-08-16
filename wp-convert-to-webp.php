<?php

/**
 * @since             1.0.0
 * @package           WPConvertToWebp
 *
 * Plugin Name:       Convert to WebP
 * Plugin Slug:       wp-convert-to-webp
 * Plugin URI:        https://github.com/PierreHunout/wp-convert-to-webp
 * Description:       Convert images to WebP format for better performance.
 * Version:           1.0.0
 * Author:            Pierre Hunout
 * Author URI:        https://pierrehunout.com/
 * Text Domain:       wp-convert-to-webp
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * GitHub Plugin URI:  PierreHunout/wp-convert-to-webp
 */

namespace WpConvertToWebp;

/**
 * This check prevents direct access to the plugin file,
 * ensuring that it can only be accessed through WordPress.
 * 
 * @since 1.0.0
 */
if (!defined('WPINC')) {
	die;
}

/**
 * Define plugin constants for version, file, path, slug, CSS, and JS URLs.
 *
 * @since 1.0.0
 */
define('WP_CONVERT_TO_WEBP_VERSION', '1.0.0');
define('WP_CONVERT_TO_WEBP_FILE', __FILE__);
define('WP_CONVERT_TO_WEBP_PATH', plugin_dir_path(WP_CONVERT_TO_WEBP_FILE));
define('WP_CONVERT_TO_WEBP_BASENAME', plugin_basename(WP_CONVERT_TO_WEBP_FILE));
define('WP_CONVERT_TO_WEBP_SLUG', dirname(WP_CONVERT_TO_WEBP_BASENAME));
define('WP_CONVERT_TO_WEBP_CSS', plugins_url('assets/css/', __FILE__));
define('WP_CONVERT_TO_WEBP_JS', plugins_url('assets/js/', __FILE__));

/**
 * Optionally include Composer autoload if available.
 *
 * @since 1.0.0
 */
if (file_exists(__DIR__ . '/lib/autoload.php')) {
	require_once __DIR__ . '/lib/autoload.php';
}

/**
 * Main plugin class for Convert to WebP.
 *
 * Implements the Singleton pattern to ensure a single instance.
 * Handles plugin loading, file inclusion, and asset enqueueing.
 */
class WpConvertToWebp
{

	/**
	 * Holds the Singleton instance.
	 *
	 * @since 1.0.0
	 * @var WpConvertToWebp|null
	 */
	private static $instance = null;

	/**
	 * Returns the Singleton instance of the plugin.
	 *
	 * @since 1.0.0
	 * @return WpConvertToWebp
	 */
	public static function get_instance()
	{
		if (null === self::$instance) {
			self::$instance	= new self();
		}

		return self::$instance;
	}

	/**
	 * Called when the plugin is loaded.
	 * Sets up actions, enqueues assets, and loads plugin files.
	 *
	 * @since 1.0.0
	 * 
	 * @return void
	 */
	public function plugin_loaded()
	{
		self::run_enqueue();
		self::run_files();

		// Register hooks for deactivation and uninstall
		register_deactivation_hook(__FILE__, ['\WpConvertToWebp\Actions\Deactivate', 'deactivate']);
		register_uninstall_hook(__FILE__, ['\WpConvertToWebp\Actions\Uninstall', 'uninstall']);
	}

	/**
	 * Loads and runs all PHP files in the 'includes' directory.
	 *
	 * Scans subdirectories for PHP files, instantiates each class,
	 * and calls its `run` method if available.
	 *
	 * @since 1.0.0
	 * 
	 * @return void
	 */
	public static function run_files()
	{
		$path 			= WP_CONVERT_TO_WEBP_PATH . 'includes/';

		// Check if the includes directory exists
		if (!is_dir($path)) {
			return;
		}

		// Get all subdirectories in the includes folder
		$directories	= array_diff(scandir($path), ['..'], ['.']);

		foreach ($directories as $directory) {
			$dir 		= $path . $directory;

			// Only process if it's a directory
			if (!is_dir($path)) {
				continue;
			}

			// Get all files in the subdirectory
			$files 	= array_diff(scandir($dir), ['..'], ['.']);

			foreach ($files as $file) {
				// Only process files with .php extension
				if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
					// Get the class name based on folder and file name
					$name 		= basename($file, '.php');
					$class		= 'WpConvertToWebp\\' . $directory . '\\' . $name;
					$instance	= new $class;

					// If the class has a run() method, call it
					if (method_exists($instance, 'run')) {
						$instance->run();
					}
				}
			}
		}
	}

	/**
	 * Hooks into 'admin_enqueue_scripts' to enqueue plugin styles and scripts.
	 *
	 * @since 1.0.0
	 * 
	 * @return void
	 */
	public static function run_enqueue()
	{
		add_action('admin_enqueue_scripts', [self::class, 'admin_enqueue'], 1);
	}

	/**
	 * Enqueues the plugin's CSS and JS files for the admin area.
	 *
	 * @since 1.0.0
	 * 
	 * @return void
	 */
	public static function admin_enqueue()
	{
		wp_enqueue_style('wp-convert-to-webp', WP_CONVERT_TO_WEBP_CSS . 'styles.css', [], WP_CONVERT_TO_WEBP_VERSION, 'all');
		wp_enqueue_script('wp-convert-to-webp', WP_CONVERT_TO_WEBP_JS . 'scripts.js', [], WP_CONVERT_TO_WEBP_VERSION, true);
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
		$WpConvertToWebp = WpConvertToWebp::get_instance();
		$WpConvertToWebp->plugin_loaded();
	}
);
