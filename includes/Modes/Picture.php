<?php

/**
 * Handles replacement of <img> tags with <picture> elements containing WebP sources in WordPress content.
 * 
 * @package WpConvertToWebp
 * @since 1.0.0
 */

namespace WpConvertToWebp\Modes;

use WpConvertToWebp\Tools;
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

class Picture
{

    /**
     * Prepares an array with src/srcset/sizes info and modified <img> tag fallback.
     *
     * @since 1.0.0
     * 
     * @param int $attachment_id The attachment ID.
     * @param array $metadata The attachment metadata.
     * @param string $image The <img> HTML.
     * @param string $src The image src URL.
     * @return array Array containing 'src', 'srcset', 'sizes' & 'fallback' keys.
     */
    public static function prepare($attachment_id, $metadata, $image, $src)
    {
        // Get srcset for the attachment
        $srcset     = wp_get_attachment_image_srcset($attachment_id);

        // Get sizes for the attachment
        $dimensions = wp_image_src_get_dimensions($src, $metadata, $attachment_id);
        $sizes      = wp_calculate_image_sizes($dimensions, $src, $metadata, $attachment_id) ?? '100vw';

        $result     = [
            'src'       => $src,
            'srcset'    => Tools::get_srcset($srcset),
            'sizes'     => $sizes,
            'fallback'  => Image::prepare($attachment_id, $metadata, $image, $src)
        ];

        return $result;
    }

    /**
     * Generates the final <picture> HTML with WebP source and modified <img> tag.
     *
     * @since 1.0.0
     * 
     * @param array $image Array containing 'src', 'srcset', 'sizes' & 'fallback' keys.
     * @return string The complete <picture> HTML element.
     */
    public static function print($image)
    {
        $src        = $image['src'];
        $srcset     = $image['srcset'];
        $sizes      = $image['sizes'];
        $fallback   = $image['fallback'];

        // Build WebP file path from original src
        $webp       = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $src);

        if (!Tools::is_file($webp)) {
            return $fallback;
        }

        $picture    = '<picture>';

        if (empty($srcset)) {
            // If no srcset, just add the default WebP source
            $picture   .= sprintf('<source type="image/webp" srcset="%s" sizes="%s" />', esc_attr($webp), esc_attr($sizes));
            $picture   .= $fallback;
            $picture   .= '</picture>';

            return $picture;
        }

        // Replace each srcset item with its .webp equivalent if available
        $array      = explode(',', $srcset);
        $srcset     = [];

        foreach ($array as $item) {
            $parts          = preg_split('/\s+/', trim($item));

            if (empty($parts[0])) {
                continue;
            }

            $webp_url       = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $parts[0]);

            if (Tools::is_file($webp_url)) {
                $srcset[]   = esc_url($webp_url) . (isset($parts[1]) ? ' ' . $parts[1] : '');
            } else {
                $srcset[]   = $parts[0] . (isset($parts[1]) ? ' ' . $parts[1] : '');
            }
        }

        $srcset     = implode(', ', $srcset);

        // Add the WebP source with srcset and sizes attributes
        $picture   .= sprintf('<source type="image/webp" srcset="%s" sizes="%s" />', $srcset, esc_attr($sizes));

        // Add the fallback <img> tag
        $picture   .= $fallback;
        $picture   .= '</picture>';

        return $picture;
    }
}
