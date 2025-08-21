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

        $support        = self::browser_support();

        // If the browser does not support WebP, return original image
        if (empty($support)) {
            return $image;
        }

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
     * Checks if the browser supports WebP using the HTTP ACCEPT header.
     *
     * @since 1.0.0
     * 
     * @return bool True if WebP is supported, false otherwise.
     */
    private static function browser_support()
    {
        // Check HTTP Accept header for WebP support
        $http_accept    = isset($_SERVER['HTTP_ACCEPT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_ACCEPT'])) : '';
        
        if (strpos($http_accept, 'image/webp') !== false) {
            return true;
        }

        // Get browser info from User-Agent
        $browser        = self::get_browser();
        $name           = strtolower($browser['name']);
        $version        = $browser['version'];

        // Internet Explorer never supports WebP
        if ($name === 'ie') {
            return false;
        }

        // List of browsers and minimum versions supporting WebP
        $matrix         = [
            'chrome'        => '32.0',
            'firefox'       => '65.0',
            'edge'          => '18.0',
            'opera'         => '19.0',
            'safari'        => '16.0', // iOS Safari 16+, macOS Safari 16+
            'android'       => '4.0',  // Android Browser 4.0+
            'samsung'       => '4.0',  // Samsung Internet 4+
        ];
        
        // Check support for known browsers
        foreach ($matrix as $key => $min) {
            if (strpos($name, $key) !== false) {
                // If version is unknown, assume no support
                if ($version === '?' || $version === '') {
                    return false;
                }
                // Compare only the major version
                $major = intval(explode('.', $version)[0]);
                if ($min !== false && $major >= $min) {
                    // WebP is supported by this browser
                    return true;
                }
            }
        }

        // Fallback: not supported
        return false;
    }

    /**
     * Gets the browser name and version from the User-Agent string.
     *
     * @since 1.0.0
     * 
     * @return array An associative array with 'name' and 'version' keys.
     */
    private static function get_browser()
    {
        $user_agent = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? ''));
        $name       = 'Unknown';
        $version    = '';

        // List of browsers to check
        $browsers   = [
            'Edge'              => 'Edge',
            'OPR'               => 'Opera',
            'Opera'             => 'Opera',
            'Chrome'            => 'Chrome',
            'Safari'            => 'Safari',
            'Firefox'           => 'Firefox',
            'MSIE'              => 'IE',
            'Trident'           => 'IE',
            'SamsungBrowser'    => 'Samsung',
            'Android'           => 'Android'
        ];

        foreach ($browsers as $key => $browser_name) {
            if (stripos($user_agent, $key) !== false) {
                $name   = $browser_name;
                // Build regex for version extraction
                if ($key === 'Trident') {
                    // IE 11+
                    if (preg_match('/rv:([0-9\.]+)/', $user_agent, $matches)) {
                        $version    = $matches[1];
                    }
                } elseif ($key === 'OPR') {
                    // Opera (Chromium)
                    if (preg_match('/OPR\/([0-9\.]+)/', $user_agent, $matches)) {
                        $version    = $matches[1];
                    }
                } elseif ($key === 'SamsungBrowser') {
                    if (preg_match('/SamsungBrowser\/([0-9\.]+)/', $user_agent, $matches)) {
                        $version    = $matches[1];
                    }
                } elseif ($key === 'Android') {
                    if (preg_match('/Android\s([0-9\.]+)/', $user_agent, $matches)) {
                        $version    = $matches[1];
                    }
                } else {
                    if (preg_match('/' . preg_quote($key, '/') . '[\/ ]([0-9\.]+)/', $user_agent, $matches)) {
                        $version    = $matches[1];
                    }
                }
                break;
            }
        }

        // Special case for Safari (exclude Chrome)
        if ($name === 'Safari' && stripos($user_agent, 'Chrome') !== false) {
            $name   = 'Chrome';
            if (preg_match('/Chrome\/([0-9\.]+)/', $user_agent, $matches)) {
                $version    = $matches[1];
            }
        }

        // Fallback if no version found
        if ($version === '') {
            $version    = '?';
        }

        return [
            'name'      => $name,
            'version'   => $version
        ];
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
