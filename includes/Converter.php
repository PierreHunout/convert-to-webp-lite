<?php

/**
 * WebP Converter Class
 *
 * This class handles the conversion of image files to WebP format.
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

class Converter
{

    /**
     * Converts the given attachment ID and its metadata to WebP format.
     *
     * This method retrieves the file path of the attachment,
     * checks if it exists, and then converts it to WebP format.
     * It also converts any sizes defined in the metadata.
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

            $this->convert($file);

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

                $this->convert($filepath);
            }
        } catch (Throwable $error) {
            error_log('[WP Convert to WebP] Error preparing conversion: ' . $error->getMessage());
            return;
        }
    }

    /**
     * Converts a single image file to WebP format.
     *
     * This method checks the file extension
     * and uses the appropriate image creation function
     * to convert the image to WebP format.
     * 
     * It supports JPEG, PNG, and GIF formats.
     * 
     * If the file does not exist or is not a supported format,
     * it simply returns without doing anything.
     * 
     * If the WebP file already exists, it does not convert again.
     * 
     * The converted WebP file is saved in the same directory
     * as the original file with the same name
     * but with a .webp extension.
     * 
     * @since 1.0.0
     *
     * @param string $filepath The path to the image file.
     * @return void
     */
    public function convert($filepath)
    {
        try {
            // Validate the file path
            if (!is_string($filepath) || empty($filepath)) {
                throw new InvalidArgumentException('Invalid file path provided.');
            }

            if (!file_exists($filepath)) {
                throw new RuntimeException('File does not exist: ' . $filepath);
            }

            $pathinfo   = pathinfo($filepath);
            $path       = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '.webp';

            // Check if the file is already in WebP format
            if (file_exists($path)) {
                throw new RuntimeException('WebP file already exists: ' . $path);
            }

            $webp       = null;

            switch (strtolower($pathinfo['extension'])) {
                case 'jpg':
                case 'jpeg':
                    $webp   = imagecreatefromjpeg($filepath);
                    break;
                case 'png':
                    $webp   = imagecreatefrompng($filepath);
                    break;
                case 'gif':
                    $webp   = imagecreatefromgif($filepath);
                    break;
                default:
                    // Unsupported file type, do nothing
                    return;
            }

            $webp_quality   = get_option('convert_to_webp_quality', 85);

            if ($webp) {
                imagewebp($webp, $path, $webp_quality);
                imagedestroy($webp);
            }
        } catch (Throwable $error) {
            error_log('[WP Convert to WebP] Error converting file: ' . $error->getMessage());
        }
    }
}
