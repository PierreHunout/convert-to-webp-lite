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
 * The plugin version.
 *
 * @since 1.0.0
 */
define('WP_CONVERT_TO_WEBP_VERSION', '1.0.0');

/**
 * The plugin file.
 *
 * @since 1.0.0
 */
define('WP_CONVERT_TO_WEBP_FILE', __FILE__);

/**
 * The plugin path.
 *
 * @since 1.0.0
 */
define('WP_CONVERT_TO_WEBP_PATH', plugin_dir_path(WP_CONVERT_TO_WEBP_FILE));

/**
 * The plugin URL.
 *
 * @since 1.0.0
 */
define('WP_CONVERT_TO_WEBP_BASENAME', plugin_basename(WP_CONVERT_TO_WEBP_FILE));

/**
 * The plugin slug.
 *
 * This is the directory name of the plugin, which is used in URLs and other references.
 *
 * @since 1.0.0
 */
define('WP_CONVERT_TO_WEBP_SLUG', dirname(WP_CONVERT_TO_WEBP_BASENAME));

/**
 * The plugin CSS URL.
 *
 * This constant is used to reference the CSS files of the plugin.
 *
 * @since 1.0.0
 */
define('WP_CONVERT_TO_WEBP_CSS', plugins_url('assets/css/', __FILE__));

/**
 * The plugin JS URL.
 *
 * This constant is used to reference the JavaScript files of the plugin.
 *
 * @since 1.0.0
 */
define('WP_CONVERT_TO_WEBP_JS', plugins_url('assets/js/', __FILE__));

/** 
 * If you don't want to use the autoloading feature, you can comment the following line.
 * 
 * It will include the autoload.php file from the lib directory.
 * Make sure that the autoload.php file exists in the lib directory.
 */
if (file_exists(__DIR__ . '/lib/autoload.php')) {
	require_once __DIR__ . '/lib/autoload.php';
}

class WpConvertToWebp
{

	/**
	 * @since 1.0.0
	 * 
	 * This variable is used to implement the Singleton pattern,
	 * ensuring that only one instance of the class exists.
	 */
	private static $instance = null;

	/**
	 * Get the instance of the WpConvertToWebp class.
	 * 
	 * This method implements the Singleton pattern to ensure that only one instance of the class exists.
	 * 
	 * @since 1.0.0
	 * 
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
	 * This method is called when the plugin is loaded.
	 * 
	 * It sets up the necessary actions and runs the enqueue and file loading methods.
	 * 
	 * @since 1.0.0
	 * 
	 * @return void
	 */
	public function plugin_loaded()
	{
		self::run_enqueue();
		self::run_files();
	}

	/**	
	 * Run all files in the includes directory.
	 * 
	 * This method scans the 'includes' directory for subdirectories,
	 * and then scans each subdirectory for PHP files.
	 * It instantiates each class found in the files
	 * and calls its `run` method.
	 * 
	 * @since 1.0.0
	 * 
	 * @return void
	 */
	public static function run_files()
	{
		$path 			= WP_CONVERT_TO_WEBP_PATH . 'includes/';
		$directories	=  array_diff(scandir($path), ['..'], ['.']);
		foreach ($directories as $directory) {
			$dir 		= $path . $directory;
			if (is_dir($dir)) {
				$files 	= array_diff(scandir($dir), ['..'], ['.']);
				foreach ($files as $file) {
					if (pathinfo($file, PATHINFO_EXTENSION)) {
						$name 	= basename($file, '.php');
						$class	= 'WpConvertToWebp\\' . $directory . '\\' . $name;
						$new 	= new $class;
						$new->run();
					}
				}
			}
		}
	}

	/**
	 * This method hooks into the 'admin_enqueue_scripts' action to enqueue styles and scripts for the admin area.
	 * It uses the `admin_enqueue` method to load the styles and scripts for the admin area.
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
	 * This method enqueues the styles and scripts for the admin area of the plugin.
	 * It uses WordPress functions to load the CSS and JS files with the appropriate versioning.
	 * 
	 * @since 1.0.0
	 */
	public static function admin_enqueue()
	{
		wp_enqueue_style('admin-styles', WP_CONVERT_TO_WEBP_CSS . 'admin.css', [], WP_CONVERT_TO_WEBP_VERSION, 'all');
		wp_enqueue_script('admin-scripts', WP_CONVERT_TO_WEBP_JS . 'admin.js', [], WP_CONVERT_TO_WEBP_VERSION, true);
	}
}

/**
 * This action hook is triggered when the plugin is loaded.
 * It calls the `plugin_loaded` method of the WpConvertToWebp class.
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
