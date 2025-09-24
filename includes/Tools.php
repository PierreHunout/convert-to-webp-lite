<?php

/**
 * WebP Utils
 * 
 * This class provides utility functions for handling WebP files in WordPress.
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

class Tools
{

    /**
     * Checks if the browser supports WebP using the HTTP ACCEPT header.
     *
     * @since 1.0.0
     * 
     * @return bool True if WebP is supported, false otherwise.
     */
    public static function browser_support()
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
    public static function get_browser()
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
     * Centralizes and formats error/success messages for admin display.
     *
     * @since 1.0.0
     * 
     * @param bool $success True for success, false for error.
     * @param string $message The message to display.
     * @param string $context Optional context (e.g. 'delete', 'convert').
     * @param string $size Optional image size context.
     * @param array $classes Additional CSS classes for the message.
     * @return array Array with 'message' and 'classes' keys.
     */
    public static function get_message($success, $message, $context = '', $size = '', $classes = [])
    {
        // Add success or error class
        $classes[]  = $success ? 'success' : 'error';

        // Add context class if provided
        if ($context) {
            $classes[]  = esc_attr($context);
        }

        // Add size class if provided
        if ($size) {
            $classes[]  = esc_attr($size);
        }

        // Return formatted message array
        $result     = [
            'message'   => $message,
            'classes'   => $classes
        ];

        return $result;
    }

    /**
     * Retrieves the base directory for uploads.
     *
     * This method returns the base directory for uploads,
     * which is used for WebP conversion.
     *
     * @since 1.0.0
     *
     * @return string The base directory for uploads.
     */
    public static function get_basedir()
    {
        $upload_dir = wp_upload_dir();
        return trailingslashit($upload_dir['basedir']);
    }

    /**
     * Gets all image attachments from the WordPress database.
     *
     * @since 1.0.0
     *
     * @return array|void Array of attachment IDs or void if none found.
     */
    public static function get_attachments()
    {
        $args           = [
            'post_type'         => 'attachment',
            'post_status'       => 'inherit',
            'posts_per_page'    => -1,
            'post_mime_type'    => ['image/jpeg', 'image/png', 'image/gif'],
            'fields'            => 'ids'
        ];

        $attachments    = get_posts($args);

        if (empty($attachments)) {
            return;
        }

        return $attachments;
    }

    /**
     * Tries to get the attachment ID from any image URL (original or crop).
     *
     * @since 1.0.0
     *
     * @param string $url The image URL.
     * @return int|false The attachment ID or false if not found.
     */
    public static function get_attachment_id_from_url($url)
    {
        global $wpdb;

        // Try the default method first
        $attachment_id  = attachment_url_to_postid($url);

        if ($attachment_id) {
            return $attachment_id;
        }

        // Extract the file name from the URL
        $file           = basename($url);

        // Search for attachments with matching meta
        $query          = $wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attachment_metadata' AND meta_value LIKE %s", '%' . $wpdb->esc_like($file) . '%');
        $data           = $wpdb->get_col($query);

        foreach ($data as $post_id) {
            $metadata   = wp_get_attachment_metadata($post_id);

            if (empty($metadata) || empty($metadata['sizes'])) {
                continue;
            }

            // Check all sizes
            foreach ($metadata['sizes'] as $size) {
                if (isset($size['file']) && $size['file'] === $file) {
                    return $post_id;
                }
            }
        }

        return false;
    }

    /**
     * Checks if a file exists in the uploads directory.
     *
     * This method checks if a file with the given path exists in the uploads directory.
     *
     * @since 1.0.0
     *
     * @param string $path The path to the file to check.
     * @return bool Returns true if the file exists, false otherwise.
     */
    public static function is_file($path)
    {
        $upload_dir = wp_upload_dir();
        $basedir    = $upload_dir['basedir'];
        $baseurl    = $upload_dir['baseurl'];
        $file       = str_replace($baseurl, $basedir, $path);

        if ((is_file($file)) && (file_exists($file))) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the WebP file is larger than the original file.
     *
     * This method compares the file sizes of the original image and its WebP version.
     *
     * @since 1.0.0
     *
     * @param string $image The path to the original image file.
     * @param string $webp The path to the WebP image file.
     * @return bool Returns true if the WebP file is larger, false otherwise.
     */
    public static function is_larger($image, $webp)
    {
        $upload_dir = wp_upload_dir();
        $basedir    = $upload_dir['basedir'];
        $baseurl    = $upload_dir['baseurl'];
        $image      = str_replace($baseurl, $basedir, $image);
        $webp       = str_replace($baseurl, $basedir, $webp);

        if (!is_file($image) || !is_file($webp)) {
            return false;
        }

        $image_size = filesize($image);
        $webp_size  = filesize($webp);

        if ($webp_size > $image_size) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the given file path is a WebP file.
     *
     * This method checks if the file at the given path is a WebP file
     * by checking its extension and returning true or false accordingly.
     *
     * @since 1.0.0
     *
     * @param string $filepath The path to the file to check.
     * @return bool Returns true if the file is a WebP file, false otherwise.
     */
    public static function attachment_is_webp($filepath)
    {
        // Get the url path from the file path
        $filepath       = str_replace(wp_upload_dir()['basedir'], wp_upload_dir()['baseurl'], $filepath);

        // Get the attachment ID from the file path
        $attachment_id  = attachment_url_to_postid($filepath);

        if (!$attachment_id || !is_int($attachment_id) || $attachment_id <= 0) {
            return false;
        }

        // Get the file path from the attachment ID
        $file           = get_attached_file($attachment_id);

        // Check the image format
        $pathinfo       = pathinfo($file);

        if ($pathinfo['extension'] === 'webp') {
            return true;
        }

        return false;
    }

    /**
     * Parses a srcset string into an array of URLs and widths & sort it by width.
     *
     * @since 1.0.0
     * 
     * @param string $srcset The srcset string (comma-separated).
     * @return array|void Parsed array of ['url' => string, 'width' => int] or null if invalid.
     */
    public static function parse_srcset($srcset)
    {
        if (!is_string($srcset) || empty($srcset)) {
            return;
        }

        $srcset = explode(', ', $srcset);
        $array  = [];

        foreach ($srcset as $item) {
            $item       = explode(' ', $item);
            $array[]    = [
                'url'       => $item[0],
                'width'     => intval($item[1])
            ];
        }

        // Sort by width ascending
        usort($array, function ($a, $b) {
            return $a['width'] <=> $b['width'];
        });

        return $array;
    }

    /**
     * Rebuilds and sorts a srcset string from an array of URLs and widths.
     *
     * @since 1.0.0
     * 
     * @param string $srcset The srcset string (comma-separated).
     * @return string|void Sorted srcset string.
     */
    public static function get_srcset($srcset)
    {
        if (empty($srcset)) {
            return;
        }

        $array  = self::parse_srcset($srcset);

        if (empty($array)) {
            return;
        }

        // Rebuild srcset string
        return implode(', ', array_map(function ($item) {
            return $item['url'] . ' ' . $item['width'] . 'w';
        }, $array));
    }
}
