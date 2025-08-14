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

use InvalidArgumentException;
use RuntimeException;
use Throwable;

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
     * Prepares the deletion of WebP files associated with the given attachment ID.
     *
     * This method retrieves the file path of the attachment,
     * checks if it exists, and then deletes the corresponding WebP file
     * and any cropped versions defined in the metadata.
     * 
     * @since 1.0.0
     *
     * @param int $attachment_id The ID of the attachment.
     * @param array $metadata The attachment metadata.
     * @return void
     */
    public function prepare($attachment_id, $metadata)
    {
        try {
            // Validate the attachment ID and metadata
            if (!is_int($attachment_id) || $attachment_id <= 0) {
                throw new InvalidArgumentException('Invalid attachment ID provided.');
            }

            // Check if metadata is an array and not empty
            if (!is_array($metadata) || empty($metadata)) {
                throw new InvalidArgumentException('Invalid metadata provided.');
            }

            $file       = get_attached_file($attachment_id);

            if (empty($file) || !file_exists($file)) {
                throw new RuntimeException('File does not exist for attachment ID: ' . $attachment_id);
            }

            // Check if the file is writable before attempting to delete
            if (!is_writable($file)) {
                throw new RuntimeException('File is not writable: ' . $file);
            }

            // Check the image format
            $pathinfo   = pathinfo($file);

            if ($pathinfo['extension'] === 'webp') {
                // If the original file is already a WebP file, we do not need to delete it
                return;
            }

            $this->delete($file);

            if (empty($metadata['sizes']) || !is_array($metadata['sizes'])) {
                return;
            }

            $sizes      = $metadata['sizes'];
            $base_dir   = Tools::get_basedir();
            $pathinfo   = pathinfo($metadata['file']);

            foreach ($sizes as $size) {
                if (empty($size['file']) || !file_exists($base_dir . $pathinfo['dirname'] . '/' . $size['file'])) {
                    continue;
                }

                $filepath   = $base_dir . $pathinfo['dirname'] . '/' . $size['file'];

                if (!file_exists($filepath)) {
                    continue;
                }

                $this->delete($filepath);
            }
        } catch (Throwable $error) {
            error_log('[WP Convert to WebP] Error preparing deletion: ' . $error->getMessage());
            return;
        }
    }

    /**
     * Removes WebP files from the given files iterator.
     *
     * This method iterates through the provided files,
     * checks if each file is a WebP file, and deletes it if it is not.
     * 
     * It is typically called when an attachment is deleted
     * to clean up the associated WebP files.
     * 
     * @since 1.0.0
     *
     * @param \RecursiveIteratorIterator $files The iterator containing files to check.
     * @return void
     */
    public function remove($files)
    {
        if (empty($files)) {
            return;
        }

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $filepath       = $file->getPathname();
            $is_webp        = Tools::is_webp($filepath);

            if ($is_webp) {
                // If the file is already a WebP file, we don't want to delete it
                continue;
            }

            $this->delete($filepath);
        }
    }

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
                throw new InvalidArgumentException('Invalid file path provided.');
            }

            $info   = pathinfo($filepath);

            // Ensure that the dirname and filename are not empty
            if (empty($info['dirname']) || empty($info['filename'])) {
                throw new RuntimeException('Unable to parse file path: ' . $filepath);
            }

            if ($info['extension'] === 'webp') {
                // If the original file is already a WebP file, we do not need to delete it
                return;
            }

            $webp   = $info['dirname'] . '/' . $info['filename'] . '.webp';

            if (!file_exists($webp)) {
                // If the WebP file does not exist, we do not need to delete it
                return;
            }

            // Check if the WebP file is writable before attempting to delete
            if (!is_writable($webp)) {
                throw new RuntimeException('WebP file is not writable: ' . $webp);
            }

            // Attempt to delete the WebP file
            if (!unlink($webp)) {
                throw new RuntimeException('Failed to delete WebP file: ' . $webp);
            }

            @unlink($webp);
        } catch (Throwable $error) {
            // Log the error message
            error_log('[WP Convert to WebP] Error deleting WebP file: ' . $error->getMessage());
        }
    }
}
