<?php

/**
 * This file is responsible for deleting WebP files when attachments are deleted in WordPress.
 * It hooks into the `delete_attachment` action to remove associated WebP files and cropped versions.
 *
 * @package WpConvertToWebp\Actions
 * @since 1.0.0
 */

namespace WpConvertToWebp\Actions;

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

class Delete
{

    /**
     * Class Runner for the WebP cleanup functionality.
     * 
     * This function is responsible for hooking into the WordPress media deletion process
     * and deleting WebP files associated with attachments when they are deleted.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public function run()
    {
        add_action('delete_attachment', [$this, 'delete_webp']);
    }

    /**
     * Deletes WebP files associated with the given attachment ID.
     *
     * This method checks if the attachment has a file and deletes the corresponding
     * WebP file, as well as any cropped versions if they exist.
     *
     * @param int $attachment_id The ID of the attachment post.
     * @return void
     */
    public function delete_webp($attachment_id)
    {
        $metadata   = wp_get_attachment_metadata($attachment_id);
        $cleaner    = new Cleaner();
        $cleaner->prepare($attachment_id, $metadata);
    }
}
