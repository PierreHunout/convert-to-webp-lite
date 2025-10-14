<?php

/**
 * Handles the logic for replacing <img> tags with WebP versions in WordPress content.
 * 
 * @package WpConvertToWebp
 * @since 1.0.0
 */

namespace WpConvertToWebp;

use WpConvertToWebp\Modes\Picture as Picture;
use WpConvertToWebp\Modes\Image as Image;

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
     * Prepares the image replacement process.
     * Checks if a WebP version exists, gets attachment ID and metadata,
     * adds responsive attributes, and replaces image sources.
     *
     * @since 1.0.0
     * 
     * @param array $matches Regex matches from preg_replace_callback.
     * @return string Modified image HTML or original if no WebP found.
     */
    public static function prepare($matches)
    {
        $image          = $matches[0];
        $src            = $matches[1];
        $replace_mode   = get_option('convert_to_webp_replace_mode', false);

        if (empty($replace_mode)) {
            $support        = Tools::browser_support();

            // If the browser does not support WebP, return original image
            if (empty($support)) {
                return $image;
            }
        }

        // Build WebP file path from original src
        $webp           = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $src);
        $is_webp        = Tools::is_file($webp);

        // If no WebP file exists, return original image
        if (empty($is_webp)) {
            return $image;
        }

        // If WebP is larger than original file, return original image
        $is_larger      = Tools::is_larger($src, $webp);
        
        if ($is_larger) {
            return $image;
        }

        // Get attachment ID from URL (handles crops/thumbnails)
        $attachment_id  = Tools::get_attachment_id_from_url($src);

        // If no attachment found, return original image
        if (empty($attachment_id)) {
            return $image;
        }

        // Get attachment metadata (sizes, dimensions, etc.)
        $metadata       = wp_get_attachment_metadata($attachment_id);

        // If no metadata, return original image
        if (empty($metadata)) {
            return $image;
        }

        if ($replace_mode) {
            // Add responsive attributes (srcset, sizes)
            $picture    = Picture::prepare($attachment_id, $metadata, $image, $src);

            // Replace src and srcset attributes by their WebP equivalents
            return self::replace($picture);
        }

        // Add responsive attributes (width, height, srcset, sizes)
        $image          = Image::prepare($attachment_id, $metadata, $image, $src);

        // Replace src and srcset attributes by their WebP equivalents
        return self::replace($image);
    }

    /**
     * Replaces src and srcset attributes in the <img> tag by their WebP equivalents.
     * If a WebP file exists for the src or srcset item, substitutes it.
     *
     * @since 1.0.0
     * 
     * @param string $image The <img> HTML.
     * @return string The modified <img> HTML with WebP sources.
     */
    private static function replace($image)
    {
        $replace_mode   = get_option('convert_to_webp_replace_mode', false);

        if ($replace_mode) {
            return Picture::print($image);
        }

        return Image::print($image);
    }
}
