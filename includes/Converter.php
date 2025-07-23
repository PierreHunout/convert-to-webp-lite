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
        // Validate the file path
        if (!file_exists($filepath)) {
            return;
        }

        $info   = pathinfo($filepath);
        $path   = $info['dirname'] . '/' . $info['filename'] . '.webp';

        // Check if the file is already in WebP format
        if (file_exists($path)) {
            return;
        }

        $webp   = null;

        switch (strtolower($info['extension'])) {
            case 'jpg':
            case 'jpeg':
                $webp  = imagecreatefromjpeg($filepath);
                break;
            case 'png':
                $webp  = imagecreatefrompng($filepath);
                break;
            case 'gif':
                $webp  = imagecreatefromgif($filepath);
                break;
            default:
                return;
        }

        $webp_quality   = get_option('convert_to_webp_quality', 85);

        if ($webp) {
            imagewebp($webp, $path, $webp_quality);
            imagedestroy($webp);
        }
    }
}
