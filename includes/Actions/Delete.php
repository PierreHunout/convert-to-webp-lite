<?php

/**
 * Handles deletion of WebP files when attachments are deleted in WordPress.
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
     * Registers the action for automatic WebP cleanup on attachment deletion.
     *
     * This method hooks into the 'delete_attachment' action,
     * so that every time an attachment is deleted, its associated WebP files are also deleted.
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
     * @since 1.0.0
     * 
     * @param int $attachment_id The ID of the attachment post.
     * @return void
     */
    public function delete_webp($attachment_id)
    {
        // Get the attachment metadata
        $metadata   = wp_get_attachment_metadata($attachment_id);

        // Instantiate the Cleaner and delete associated WebP files
        $cleaner    = new Cleaner();
        $cleaner->prepare($attachment_id, $metadata);
    }
}
