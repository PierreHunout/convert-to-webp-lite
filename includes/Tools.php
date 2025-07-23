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
     * Retrieves all files in the uploads directory.
     *
     * This method uses a RecursiveDirectoryIterator to get all files
     * in the WordPress uploads directory, which is used for WebP conversion.
     *
     * @since 1.0.0
     *
     * @return \RecursiveIteratorIterator|void Returns an iterator for the files or void if no files found.
     */
    public static function get_files()
    {
        $upload_dir = wp_upload_dir();
        $base_dir   = trailingslashit($upload_dir['basedir']);
        $files      = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base_dir));

        if ($files) {
            return $files;
        }

        return;
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
    public static function check_file($path)
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
}
