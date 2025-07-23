<?php
/**
 * WebP Cleaner Class
 *
 * This class handles the deletion of WebP files
 * when the original image file is deleted.
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

class Cleaner
{
    
    /**
     * Deletes the WebP file corresponding to the given file path.
     *
     * This method constructs the WebP file path
     * based on the original file path
     * and deletes it if it exists.
     * 
     * It is typically called when an attachment is deleted
     * to clean up the associated WebP file.
     * 
     * It checks if the WebP file exists and deletes it if it does.
     * 
     * This method handles errors and exceptions, and logs them using error_log.
     * 
     * @since 1.0.0
     *
     * @param string $filepath The path to the original image file.
     * @return void
     */
    public function delete($filepath)
    {
        try {
            // Validate the file path
            if (!is_string($filepath) || empty($filepath)) {
                throw new \InvalidArgumentException('Invalid file path provided.');
            }

            $info   = pathinfo($filepath);

            // Ensure that the dirname and filename are not empty
            if (empty($info['dirname']) || empty($info['filename'])) {
                throw new \RuntimeException('Unable to parse file path: ' . $filepath);
            }

            $webp   = $info['dirname'] . '/' . $info['filename'] . '.webp';

            if (file_exists($webp)) {
                // Check if the WebP file is writable before attempting to delete
                if (!is_writable($webp)) {
                    throw new \RuntimeException('WebP file is not writable: ' . $webp);
                }

                // Attempt to delete the WebP file
                if (!unlink($webp)) {
                    throw new \RuntimeException('Failed to delete WebP file: ' . $webp);
                }

                @unlink($webp);
            }
        } catch (\Throwable $e) {
            // Log the error message
            error_log('[WebP Cleaner] ' . $e->getMessage());
        }
    }
}
