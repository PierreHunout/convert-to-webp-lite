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
    private $process    = 'convert';

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
        // Define allowed HTML tags for wp_kses once
        $allowed_html   = ['span' => []];
        
        try {
            // Validate the attachment ID and metadata
            if (!is_int($attachment_id) || $attachment_id <= 0) {
                throw new InvalidArgumentException(__('Invalid attachment ID provided.', 'wp-convert-to-webp'));
            }

            // Check if metadata is an array and not empty
            if (!is_array($metadata) || empty($metadata)) {
                throw new InvalidArgumentException(__('Invalid metadata provided.', 'wp-convert-to-webp'));
            }

            // Initialize filesystem
            $filesystem = Tools::get_filesystem();

            if (!$filesystem) {
                throw new RuntimeException(__('Failed to initialize WordPress filesystem.', 'wp-convert-to-webp'));
            }

            // Get the main file path for the attachment
            $file       = get_attached_file($attachment_id);

            // Check if the file exists
            if (empty($file) || !$filesystem->exists($file)) {
                // translators: %s is the attachment ID of the file that doesn't exist
                throw new RuntimeException(wp_kses(sprintf(__('File does not exist for attachment ID: %s', 'wp-convert-to-webp'), '<span>' . esc_html($attachment_id) . '</span>'), $allowed_html));
            }

            // Check supported mime types
            $mime_type  = get_post_mime_type($attachment_id);

            if (!in_array($mime_type, ['image/jpeg', 'image/png', 'image/gif'])) {
                // translators: %s is the MIME type of the unsupported file
                throw new RuntimeException(wp_kses(sprintf(__('Unsupported file type: %s', 'wp-convert-to-webp'), '<span>' . esc_html($mime_type) . '</span>'), $allowed_html));
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
                if (empty($size['file'])) {
                    continue;
                }

                $filepath   = $base_dir . $pathinfo['dirname'] . '/' . $size['file'];

                if (!$filesystem->exists($filepath)) {
                    continue;
                }

                $result[]   = $this->convert($filepath, 'size');
            }
        } catch (Throwable $error) {
            // Log error if WP_DEBUG is enabled
            if (defined('WP_DEBUG') && WP_DEBUG === true) {
				error_log(
					sprintf(
						// translators: %1$s is the error message, %2$s is the filename, %3$d is the line number
						__('[WP Convert to WebP] Error preparing conversion: %1$s in %2$s on line %3$d', 'wp-convert-to-webp'),
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
        // Define allowed HTML tags for wp_kses once
        $allowed_html = ['span' => []];
        
        try {
            // Validate the file path
            if (!is_string($filepath) || empty($filepath)) {
                throw new InvalidArgumentException(__('Invalid file path provided.', 'wp-convert-to-webp'));
            }

            // Initialize filesystem
            $filesystem = Tools::get_filesystem();

            if (!$filesystem) {
                throw new RuntimeException(__('Failed to initialize WordPress filesystem.', 'wp-convert-to-webp'));
            }

            // Check if the file exists
            if (!$filesystem->exists($filepath)) {
                // translators: %s is the file path that doesn't exist
                throw new RuntimeException(wp_kses(sprintf(__('File does not exist: %s', 'wp-convert-to-webp'), '<span>' . esc_html($filepath) . '</span>'), $allowed_html));
            }

            $pathinfo   = pathinfo($filepath);

            // Ensure that the dirname and filename are not empty
            if (empty($pathinfo['dirname']) || empty($pathinfo['filename'])) {
                // translators: %s is the basename of the file path that cannot be parsed
                throw new RuntimeException(wp_kses(sprintf(__('Unable to parse file path: %s', 'wp-convert-to-webp'), '<span>' . esc_html($pathinfo['basename']) . '</span>'), $allowed_html));
            }

            // Check if file is already a WebP
            $is_webp    = Tools::attachment_is_webp($filepath);

            if ($is_webp) {
                // translators: %s is the basename of the WebP file that already exists
                throw new RuntimeException(wp_kses(sprintf(__('The original file is already a WebP file: %s', 'wp-convert-to-webp'), '<span>' . esc_html($pathinfo['basename']) . '</span>'), $allowed_html));
            }

            // Check supported mime types
            $mime_type  = mime_content_type($filepath);

            if (!in_array($mime_type, ['image/jpeg', 'image/png', 'image/gif'])) {
                // translators: %s is the unsupported MIME type of the file
                throw new RuntimeException(wp_kses(sprintf(__('Unsupported file type: %s', 'wp-convert-to-webp'), '<span>' . esc_html($mime_type) . '</span>'), $allowed_html));
            }

            // Build the WebP file path
            $path       = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '.webp';

            // If the WebP file already exists, do not convert again
            if ($filesystem->exists($path)) {
                // translators: %s is the WebP filename that already exists
                throw new RuntimeException(wp_kses(sprintf(__('WebP file already exists: %s', 'wp-convert-to-webp'), '<span>' . esc_html($pathinfo['filename']) . '.webp</span>'), $allowed_html));
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
                    // translators: %s is the basename of the file with unsupported type
                    $message    = wp_kses(sprintf(__('Unsupported file type: %s', 'wp-convert-to-webp'), '<span>' . esc_html($pathinfo['basename']) . '</span>'), $allowed_html);
                    return Tools::get_message(false, $message, $this->process, $size);
            }

            // Get quality setting from plugin options
            $quality    = get_option('convert_to_webp_quality', 85);

            // If image resource creation failed
            if (empty($webp)) {
                // translators: %s is the basename of the file for which image resource creation failed
                $message        = wp_kses(sprintf(__('Failed to create image resource: %s', 'wp-convert-to-webp'), '<span>' . esc_html($pathinfo['basename']) . '</span>'), $allowed_html);
                return Tools::get_message(false, $message, $this->process, $size);
            }

            // Attempt to save the WebP file
            if (imagewebp($webp, $path, $quality)) {
                imagedestroy($webp);

                // translators: %s is the basename of the file that was successfully converted to WebP
                $message        = wp_kses(sprintf(__('Successfully converted: %s', 'wp-convert-to-webp'), '<span>' . esc_html($pathinfo['basename']) . '</span>'), $allowed_html);
                return Tools::get_message(true, $message, $this->process, $size);
            } else {
                imagedestroy($webp);

                // translators: %s is the filename of the WebP file that couldn't be saved
                $message        = wp_kses(sprintf(__('Failed to save WebP file: %s', 'wp-convert-to-webp'), '<span>' . esc_html($pathinfo['filename']) . '.webp</span>'), $allowed_html);
                return Tools::get_message(false, $message, $this->process, $size);
            }
        } catch (Throwable $error) {
            // Log error if WP_DEBUG is enabled
            if (defined('WP_DEBUG') && WP_DEBUG === true) {
				error_log(
					sprintf(
						// translators: %1$s is the error message, %2$s is the filename, %3$d is the line number
						__('[WP Convert to WebP] Error converting file: %1$s in %2$s on line %3$d', 'wp-convert-to-webp'),
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
