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
     * @param int $post_id The ID of the attachment post.
     * @return void
     */
    public function delete_webp($post_id)
    {
        $file = get_attached_file($post_id);

        if ($file) {
            $cleaner = new Cleaner();
            $cleaner->delete($file);

            $meta = wp_get_attachment_metadata($post_id);
            if (!empty($meta['sizes'])) {
                $file_info = pathinfo($meta['file']);
                $upload_dir = wp_upload_dir();
                foreach ($meta['sizes'] as $size) {
                    if (!empty($size['file'])) {
                        $crop_path = $upload_dir['basedir'] . '/' . $file_info['dirname'] . '/' . $size['file'];
                        $cleaner->delete($crop_path);
                    }
                }
            }
        }
    }
}
