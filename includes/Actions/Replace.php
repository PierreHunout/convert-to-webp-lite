<?php
/**
 * This file is responsible for replacing image tags in WordPress content
 * with their WebP equivalents using the <picture> element.
 *
 * @package WpConvertToWebp\Actions
 * @since 1.0.0
 */

namespace WpConvertToWebp\Actions;

use WpConvertToWebp\Replacer;

/**
 * This check prevents direct access to the plugin file,
 * ensuring that it can only be accessed through WordPress.
 * 
 * @since 1.0.0
 */
if (!defined('WPINC')) {
    die;
}

class Replace
{

    /**
     * Class Runner for the WebP replacement functionality.
     *
     * This function is responsible for hooking into the WordPress content filters
     * to replace image tags with their WebP equivalents.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function run()
    {
        add_filter('the_content', [static::class, 'replace_webp']);
        add_filter('post_thumbnail_html', [static::class, 'replace_webp']);
        add_filter('wp_get_attachment_image', [static::class, 'replace_webp']);
        add_filter('widget_text', [static::class, 'replace_webp']);
    }

    /**
     * Replaces image tags in the content with their WebP equivalents.
     *
     * This method uses a regular expression to find <img> tags and replaces them
     * with a <picture> element that includes a WebP source if available.
     * 
     * @since 1.0.0
     *
     * @param string $content The content to process.
     * @return string The modified content with WebP replacements.
     */
    public static function replace_webp($content)
    {
        $replacer   = new Replacer();

        return preg_replace_callback(
            '/<img\s+[^>]*src=["\']([^"\']+)["\'][^>]*>/i',
            [$replacer, 'replace'],
            $content
        );
    }
}
