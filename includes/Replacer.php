<?php
/**
 * WebP Replacer Class
 *
 * This class is responsible for replacing image tags in the content with their WebP equivalents.
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

class Replacer
{

    /**
     * Replaces the <img> tag with a <picture> element if a WebP version exists.
     *
     * This method checks if the image source is in the uploads directory,
     * and if a corresponding WebP file exists, it replaces the <img> tag
     * with a <picture> element that includes the WebP source.
     * 
     * @since 1.0.0
     * 
     * @param array $matches The matches from the regular expression.
     * @return string The modified <picture> element or original <img> tag.
     */
    public function replace($matches)
    {  
        $image      = $matches[0];
        $src        = $matches[1];
        $upload_dir = wp_upload_dir();

        // Check if the image source is in the uploads directory
        if (strpos($src, $upload_dir['baseurl']) !== 0) {
            return $image;
        }

        // Check if the WebP file exists
        $webp_src   = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $src);
        $is_file    = Tools::check_file($webp_src);
        
        if ($is_file) {
            return sprintf(
                '<picture><source srcset="%s" type="image/webp">%s</picture>',
                esc_url($webp_src),
                $image
            );
        }

        return $image;
    }
}
