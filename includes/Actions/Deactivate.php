<?php

/**
 * Handles plugin deactivation and cleanup of WebP files and options.
 * 
 * @package WpConvertToWebp\Actions
 * @since 1.0.0
 */

namespace WpConvertToWebp\Actions;

use WpConvertToWebp\Tools;
use WpConvertToWebp\Cleaner;

/**
 * This check prevents direct access to the plugin file,
 * ensuring that it can only be accessed through WordPress.
 * 
 * @since 1.0.0
 */
if (!defined('WPINC')) {
    die;
}

class Deactivate
{

    /**
     * Called when the plugin is deactivated.
     * Deletes all WebP files if the option is enabled,
     * and removes plugin options from the database.
     *
     * @since 1.0.0
     * 
     * @return void
     */
    public static function deactivate()
    {
        // Check if the user requested to delete WebP files 
        $delete_webp    = get_option('delete_webp_on_deactivate', false);

        if (!$delete_webp) {
            // If not requested, exit without doing anything
            return;
        }

        // Get all image attachments from the database
        $attachments    = Tools::get_attachments();

        if (empty($attachments)) {
            // If no attachments found, exit without doing anything
            return;
        }

        // Loop through all attachments and delete their WebP files
        foreach ($attachments as $attachment_id) {
            $metadata   = wp_get_attachment_metadata($attachment_id);
            $cleaner    = new Cleaner();
            $cleaner->prepare($attachment_id, $metadata);
        }

        // Remove plugin options from the database
        delete_option('delete_webp_on_deactivate');
        delete_option('convert_to_webp_quality');
        delete_option('convert_to_webp_replace_mode');
    }
}
