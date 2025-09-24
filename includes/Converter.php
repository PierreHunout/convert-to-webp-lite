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
     * The process type for message formatting.
     *
     * @var string
     */
    private $process = 'convert';

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
     * @param array $result Used for recursion, do not pass manually.
     * @return array Array of result messages for each processed file.
     */
    public function prepare($attachment_id, $metadata, $result = [])
    {
        try {
            // Validate the attachment ID and metadata
            if (!is_int($attachment_id) || $attachment_id <= 0) {
                throw new InvalidArgumentException(__('Invalid attachment ID provided.', 'wp-convert-to-webp'));
            }

            // Check if metadata is an array and not empty
            if (!is_array($metadata) || empty($metadata)) {
                throw new InvalidArgumentException(__('Invalid metadata provided.', 'wp-convert-to-webp'));
            }

            // Get the main file path for the attachment
            $file       = get_attached_file($attachment_id);

            // Check if the file exists
            if (empty($file) || !file_exists($file)) {
                throw new RuntimeException(__('File does not exist for attachment ID: ', 'wp-convert-to-webp') . '<span>' . $attachment_id . '</span>');
            }

            // Check supported mime types
            $mime_type  = get_post_mime_type($attachment_id);

            if (!in_array($mime_type, ['image/jpeg', 'image/png', 'image/gif'])) {
                throw new RuntimeException(__('Unsupported file type: ', 'wp-convert-to-webp') . '<span>' . $mime_type . '</span>');
            }

            // Convert the main file
            $result[]   = $this->convert($file);

            // If there are no additional sizes, return the result
            if (empty($metadata['sizes']) || !is_array($metadata['sizes'])) {
                return $result;
            }

            // Loop through all cropped/resized versions and convert them
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

                $result[]   = $this->convert($filepath, 'size');
            }
        } catch (Throwable $error) {
            // Log error if WP_DEBUG is enabled
            if (defined('WP_DEBUG') && WP_DEBUG === true) {
				error_log(
					sprintf(
						__('[WP Convert to WebP] Error preparing conversion: %s in %s on line %d', 'wp-convert-to-webp'),
						$error->getMessage(),
						basename($error->getFile()),
						$error->getLine()
					)
				);
			}

            // Add error message to results
            $result[]   = Tools::get_message(false, $error->getMessage(), $this->process);
        }

        return $result;
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
     * @param string|null $size Optional. The image size context.
     * @return array Formatted result message.
     */
    public function convert($filepath, $size = null)
    {
        try {
            // Validate the file path
            if (!is_string($filepath) || empty($filepath)) {
                throw new InvalidArgumentException(__('Invalid file path provided.', 'wp-convert-to-webp'));
            }

            // Check if the file exists
            if (!file_exists($filepath)) {
                throw new RuntimeException(__('File does not exist: ', 'wp-convert-to-webp') . '<span>' . $filepath . '</span>');
            }

            $pathinfo   = pathinfo($filepath);

            // Ensure that the dirname and filename are not empty
            if (empty($pathinfo['dirname']) || empty($pathinfo['filename'])) {
                throw new RuntimeException(__('Unable to parse file path: ', 'wp-convert-to-webp') . '<span>' . $pathinfo['basename'] . '</span>');
            }

            // Check if file is already a WebP
            $is_webp    = Tools::attachment_is_webp($filepath);

            if ($is_webp) {
                throw new RuntimeException(__('The original file is already a WebP file: ', 'wp-convert-to-webp') . '<span>' . $pathinfo['basename'] . '</span>');
            }

            // Check supported mime types
            $mime_type  = mime_content_type($filepath);

            if (!in_array($mime_type, ['image/jpeg', 'image/png', 'image/gif'])) {
                throw new RuntimeException(__('Unsupported file type: ', 'wp-convert-to-webp') . '<span>' . $mime_type . '</span>');
            }

            // Build the WebP file path
            $path       = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '.webp';

            // If the WebP file already exists, do not convert again
            if (file_exists($path)) {
                throw new RuntimeException(__('WebP file already exists: ', 'wp-convert-to-webp') . '<span>' . $pathinfo['filename'] . '.webp</span>');
            }

            $webp       = null;

            // Create image resource based on file extension
            switch (strtolower($pathinfo['extension'])) {
                case 'jpg':
                case 'jpeg':
                    $webp       = imagecreatefromjpeg($filepath);
                    break;
                case 'png':
                    $webp       = imagecreatefrompng($filepath);
                    imagepalettetotruecolor($webp);
                    imagealphablending($webp, true);
                    imagesavealpha($webp, true);
                    break;
                case 'gif':
                    $webp       = imagecreatefromgif($filepath);
                    imagepalettetotruecolor($webp);
                    break;
                default:
                    $message    = __('Unsupported file type: ', 'wp-convert-to-webp') . '<span>' . $pathinfo['basename'] . '</span>';
                    return Tools::get_message(false, $message, $this->process, $size);
            }

            // Get quality setting from plugin options
            $quality   = get_option('convert_to_webp_quality', 85);

            // If image resource creation failed
            if (empty($webp)) {
                $message    = __('Failed to create image resource: ', 'wp-convert-to-webp') . '<span>' . $pathinfo['basename'] . '</span>';
                return Tools::get_message(false, $message, $this->process, $size);
            }

            // Attempt to save the WebP file
            if (imagewebp($webp, $path, $quality)) {
                imagedestroy($webp);

                $message    = __('Successfully converted: ', 'wp-convert-to-webp') . '<span>' . $pathinfo['basename'] . '</span>';
                return Tools::get_message(true, $message, $this->process, $size);
            } else {
                imagedestroy($webp);

                $message    = __('Failed to save WebP file: ', 'wp-convert-to-webp') . '<span>' . $pathinfo['filename'] . '.webp</span>';
                return Tools::get_message(false, $message, $this->process, $size);
            }
        } catch (Throwable $error) {
            // Log error if WP_DEBUG is enabled
            if (defined('WP_DEBUG') && WP_DEBUG === true) {
				error_log(
					sprintf(
						__('[WP Convert to WebP] Error converting file: %s in %s on line %d', 'wp-convert-to-webp'),
						$error->getMessage(),
						basename($error->getFile()),
						$error->getLine()
					)
				);
			}
            

            // Return error message
            return Tools::get_message(false, $error->getMessage(), $this->process, $size);
        }
    }
}
