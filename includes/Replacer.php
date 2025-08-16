<?php

/**
 * Handles replacement of <img> tags with <picture> elements containing WebP sources in WordPress content.
 * 
 * @package WpConvertToWebp
 * @since 1.0.0
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

class Replacer
{

    /**
     * Replaces the <img> tag with a <picture> element if a WebP version exists.
     *
     * This method checks if the image source is in the uploads directory,
     * and if a corresponding WebP file exists, it replaces the <img> tag
     * with a <picture> element that includes the WebP source.
     * If no WebP file is found, the original <img> tag is returned.
     *
     * @since 1.0.0
     *
     * @param array $matches The matches from the regular expression (from preg_replace_callback): $matches[0] is the full <img> tag, $matches[1] is the src attribute value.
     * @return string The modified <picture> element or original <img> tag.
     */
    public static function replace($matches)
    {
        $image      = $matches[0]; // The full <img> tag
        $src        = $matches[1]; // The src attribute value
        $upload_dir = wp_upload_dir();

        // Check if the image source is in the uploads directory
        if (strpos($src, $upload_dir['baseurl']) !== 0) {
            // If not, return the original <img> tag
            return $image;
        }

        // Build the expected WebP file URL
        $webp_src   = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $src);

        // Check if the WebP file exists in the uploads directory
        $is_file    = Tools::is_file($webp_src);

        if (!$is_file) {
            // If no WebP file found, return the original <img> tag
            return $image;
        }

        // Return a <picture> element with a WebP source and the original <img> tag as fallback
        return sprintf(
            '<picture><source srcset="%s" type="image/webp">%s</picture>',
            esc_url($webp_src),
            $image
        );
    }
}
