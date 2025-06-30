<?php
/**
 * This file is responsible for converting images to WebP format
 * when they are uploaded to the WordPress media library.
 *
 * @package WpConvertToWebp\Actions
 * @since 1.0.0
 */

namespace WpConvertToWebp\Actions;

use WpConvertToWebp\Converter;

/**
 * This check prevents direct access to the plugin file,
 * ensuring that it can only be accessed through WordPress.
 * 
 * @since 1.0.0
 */
if (!defined('WPINC')) {
    die;
}

class Add
{

    /**
     * Class Runner for the WebP conversion functionality.
     * 
     * This function is responsible for hooking into the WordPress media upload process
     * and converting all uploaded images to WebP format.
     * 
     * It uses the `wp_generate_attachment_metadata` filter to intercept the metadata
     * and convert images to WebP after they are uploaded.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public function run()
    {
        add_filter('wp_generate_attachment_metadata', [$this, 'convert_webp'], 20, 2);
    }

    /**
     * Converts all images in the metadata to WebP format.
     *
     * This method checks the metadata for the main file and its sizes,
     * converting each to WebP format if they exist.
     * 
     * It uses the Converter class to handle the actual conversion process.
     * 
     * @since 1.0.0
     *
     * @param array $metadata The attachment metadata.
     * @param int $attachment_id The attachment ID.
     * @return array The modified metadata.
     */
    public function convert_webp($metadata, $attachment_id)
    {
        $upload_dir = wp_upload_dir();
        $base_dir   = trailingslashit($upload_dir['basedir']);

        if (!empty($metadata['file'])) {
            $converter  = new Converter();
            $converter->convert($base_dir . $metadata['file']);
        }

        if (!empty($metadata['sizes'])) {
            $file_info  = pathinfo($metadata['file']);
            foreach ($metadata['sizes'] as $size) {
                if (!empty($size['file'])) {
                    $crop_path  = $base_dir . $file_info['dirname'] . '/' . $size['file'];
                    $converter->convert($crop_path);
                }
            }
        }

        return $metadata;
    }
}
