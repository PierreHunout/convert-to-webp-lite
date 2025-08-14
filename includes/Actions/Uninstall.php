<?php

/**
 * This file is responsible for uninstalling the plugin
 * and deleting WebP files if the option is set.
 * 
 * @package WpConvertToWebp\Actions
 * @since 1.0.0
 */

namespace WpConvertToWebp\Actions;

use WpConvertToWebp\Tools;
use WpConvertToWebp\Cleaner;
use RuntimeException;
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

	/**
	 * Class Runner: We don't use the autoloader.
	 *
	 * @since 1.0.0
	 */
	public function run()
	{
		return;
	}

	/**
	 * On Uninstall.
	 *
	 * This method is called when the plugin is uninstalled.
	 * It deletes WebP files if the option is set.
	 *
	 * @since 1.0.0
	 */
	public static function uninstall()
	{
		$delete_webp	= get_option('delete_webp_on_uninstall', false);

		if (!$delete_webp) {
			return;
		}

		try {
			$files		= Tools::get_files();

			if (empty($files)) {
				throw new RuntimeException('No files found for deletion.');
			}

			$cleaner    = new Cleaner();
            $cleaner->remove($files);
		} catch (Throwable $error) {
			error_log('[WP Convert to WebP] Uninstall error: ' . $error->getMessage());
		}

		delete_option('delete_webp_on_uninstall');
		delete_option('delete_webp_on_deactivate');
		delete_option('convert_to_webp_quality');
	}
}
