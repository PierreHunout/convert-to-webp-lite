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

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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
     * Retrieves all files in the uploads directory.
     *
     * This method uses a RecursiveDirectoryIterator to get all files
     * in the WordPress uploads directory, which is used for WebP conversion.
     *
     * @since 1.0.0
     *
     *  @return RecursiveIteratorIterator|void Returns an iterator for the files or void if no files are found.
     */
    public static function get_files()
    {
        $base_dir   = self::get_basedir();
        $files      = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base_dir));

        if (!$files) {
            return;
        }

        return $files;
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
    public static function is_webp($filepath)
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
