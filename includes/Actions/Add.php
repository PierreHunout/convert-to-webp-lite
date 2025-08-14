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
        add_filter('wp_generate_attachment_metadata', [$this, 'convert_webp'], 10, 2);
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
     * @param int $attachment_id The ID of the attachment.
     * @return array The metadata.
     */
    public function convert_webp($metadata, $attachment_id)
    {
        if (empty($metadata) || !is_array($metadata)) {
            return $metadata;
        }

        $converter  = new Converter();
        $converter->prepare($attachment_id, $metadata);

        return $metadata;
    }
}
