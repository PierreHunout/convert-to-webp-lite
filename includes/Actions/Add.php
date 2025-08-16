<?php

/**
 * Handles automatic conversion of uploaded images to WebP format.
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
     * Registers the filter for automatic WebP conversion on media upload.
     *
     * This method hooks into the 'wp_generate_attachment_metadata' filter,
     * so that every image uploaded to the media library is converted to WebP.
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
     * @return array The metadata (unchanged, only conversion is performed).
     */
    public function convert_webp($metadata, $attachment_id)
    {
        // Validate metadata before conversion
        if (empty($metadata) || !is_array($metadata)) {
            return $metadata;
        }

        // Instantiate the converter and convert the image and its sizes to WebP
        $converter  = new Converter();
        $converter->prepare($attachment_id, $metadata);

        // Return the original metadata (conversion does not alter it)
        return $metadata;
    }
}
