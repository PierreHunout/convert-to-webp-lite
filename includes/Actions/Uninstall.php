<?php

/**
 *
 * @package WpConvertToWebp\Actions
 * @since 1.0.0
 */

namespace WpConvertToWebp\Actions;

use WpConvertToWebp\Tools;
use Throwable;

/**
 * This check prevents direct access to the plugin file,
 * ensuring that it can only be accessed through WordPress.
 * 
 * @since 1.0.0
 */
if (!defined('WPINC')) {
	die;
}

class Uninstall
{

	public function run()
	{
		return;
	}

	/**
	 * Uninstall the plugin.
	 *
	 * This method is called when the plugin is uninstalled.
	 * It deletes WebP files if the option is set.
	 *
	 * @since 1.0.0
	 */
	public static function uninstall()
	{
		$delete_webp    = get_option('delete_webp_on_uninstall', false);

		if ($delete_webp) {
			try {
				$files  = Tools::get_files();

				foreach ($files as $file) {
					if ($file->isFile() && strtolower($file->getExtension()) === 'webp') {
						if (!@unlink($file->getPathname())) {
							error_log('[WP Convert to WebP] Failed to delete: ' . $file->getPathname());
						}

						@unlink($file->getPathname());
					}
				}
			} catch (Throwable $error) {
				error_log('[WP Convert to WebP] Uninstall error: ' . $error->getMessage());
			}

			delete_option('delete_webp_on_uninstall');
			delete_option('delete_webp_on_deactivate');
			delete_option('convert_to_webp_quality');
		}
	}
}
