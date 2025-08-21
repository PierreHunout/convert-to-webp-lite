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
}
