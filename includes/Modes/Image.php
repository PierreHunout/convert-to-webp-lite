<?php

/**
 * Handles the logic for replacing <img> tags with WebP versions in WordPress content.
 * 
 * @package WpConvertToWebp
 * @since 1.0.0
 */

namespace WpConvertToWebp\Modes;

use WpConvertToWebp\Tools;

/**
 * This check prevents direct access to the plugin file,
 * ensuring that it can only be accessed through WordPress.
 * 
 * @since 1.0.0
 */
if (!defined('WPINC')) {
    die;
}

class Image
{

    /**
     * Adds width, height, srcset, and sizes attributes to the <img> tag.
     * Uses WordPress functions to get dimensions and responsive attributes.
     *
     * @since 1.0.0
     * 
     * @param int $attachment_id The attachment ID.
     * @param array $metadata The attachment metadata.
     * @param string $image The <img> HTML.
     * @param string $src The image src URL.
     * @return string Modified <img> HTML with added attributes.
     */
    public static function prepare($attachment_id, $metadata, $image, $src)
    {
        // Get image dimensions
        $dimensions     = wp_image_src_get_dimensions($src, $metadata, $attachment_id);

        if (empty($dimensions)) {
            return $image;
        }

        $width          = $dimensions[0];
        $height         = $dimensions[1];

        // Build width and height attributes
        $attributes     = sprintf(' width="%s"', esc_attr($width));
        $attributes    .= sprintf(' height="%s"', esc_attr($height));

        // Get srcset for the attachment
        $srcset        = wp_get_attachment_image_srcset($attachment_id);

        if (empty($srcset) || !is_string($srcset)) {
            return preg_replace('/<img ([^>]+?)[\/ ]*>/', '<img $1' . $attributes . ' />', $image);
        }

        // Sort srcset by width ascending
        $srcset         = Tools::get_srcset($srcset);

        // Add srcset attribute
        $attributes    .= sprintf(' srcset="%s"', esc_attr($srcset));

        // Get sizes attribute for responsive images
        $sizes          = wp_calculate_image_sizes($dimensions, $src, $metadata, $attachment_id);

        if (empty($sizes)) {
            return preg_replace('/<img ([^>]+?)[\/ ]*>/', '<img $1' . $attributes . ' />', $image);
        }

        // Add sizes attribute
        $attributes    .= sprintf(' sizes="%s"', esc_attr($sizes));

        // Inject all attributes into the <img> tag
        return preg_replace('/<img ([^>]+?)[\/ ]*>/', '<img $1' . $attributes . ' />', $image);
    }

    /**
     * Prints the <img> HTML with WebP sources in src and srcset attributes if available.
     *
     * This method replaces the src and srcset attributes in the <img> tag with their
     * WebP equivalents if the corresponding WebP files exist.
     * 
     * @since 1.0.0
     * 
     * @param string $image The <img> HTML.
     * @return string The modified <img> HTML with WebP sources.
     */
    public static function print($image)
    {
        // Replace src attribute with .webp if available
        $image  = preg_replace_callback(
            '/src=["\']([^"\']+\.(?:jpe?g|png|gif))["\']/i',
            function ($matches) {
                $webp_src   = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $matches[1]);

                if (Tools::is_file($webp_src)) {
                    return 'src="' . esc_url($webp_src) . '"';
                }

                return $matches[0];
            },
            $image
        );

        // If no srcset attribute, return image as is
        if (!preg_match('/srcset=["\']([^"\']+)["\']/i', $image, $matches)) {
            return $image;
        }

        // Replace each srcset item with its .webp equivalent if available
        $image = preg_replace_callback(
            '/srcset=["\']([^"\']+)["\']/i',
            function ($matches) {
                $array   = explode(',', $matches[1]);
                $srcset  = [];

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

                return 'srcset="' . implode(', ', $srcset) . '"';
            },
            $image
        );

        return $image;
    }
}
