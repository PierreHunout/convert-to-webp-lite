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

        // Build WebP file path from original src
        $webp           = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $src);
        $is_webp        = Tools::is_file($webp);

        // If no WebP file exists, return original image
        if (empty($is_webp)) {
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

        // Add responsive attributes (width, height, srcset, sizes)
        $image          = self::add_attributes($attachment_id, $metadata, $image, $src);

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
     * @return string Modified <img> HTML with WebP sources.
     */
    private static function replace($image)
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
                $srcset_items     = explode(',', $matches[1]);
                $webp_srcset      = [];
                foreach ($srcset_items as $item) {
                    $parts          = preg_split('/\s+/', trim($item));
                    if (!empty($parts[0])) {
                        $webp_url   = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $parts[0]);

                        if (Tools::is_file($webp_url)) {
                            $webp_srcset[]  = esc_url($webp_url) . (isset($parts[1]) ? ' ' . $parts[1] : '');
                        } else {
                            $webp_srcset[]  = $parts[0] . (isset($parts[1]) ? ' ' . $parts[1] : '');
                        }
                    }
                }

                return 'srcset="' . implode(', ', $webp_srcset) . '"';
            },
            $image
        );

        return $image;
    }

    /**
     * Sorts a srcset string by image width in ascending order.
     *
     * @since 1.0.0
     * 
     * @param string $srcset The srcset string (comma-separated).
     * @return string Sorted srcset string.
     */
    private static function sort_srcset($srcset)
    {
        $srcset = explode(', ', $srcset);
        $array  = [];
        foreach ($srcset as $item) {
            $item       = explode(' ', $item);
            $array[]    = [
                'url'       => $item[0],
                'width'    => intval($item[1])
            ];
        }

        // Sort by width ascending
        usort($array, function ($a, $b) {
            return $a['width'] <=> $b['width'];
        });

        // Rebuild srcset string
        return implode(', ', array_map(function ($item) {
            return $item['url'] . ' ' . $item['width'] . 'w';
        }, $array));
    }

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
    private static function add_attributes($attachment_id, $metadata, $image, $src)
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
        $srcset         = self::sort_srcset($srcset);

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
}
