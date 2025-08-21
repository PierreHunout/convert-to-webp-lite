<?php
/**
 * Handles replacement of <img> tags in WordPress content with <picture> elements including WebP sources.
 *
 * @package WpConvertToWebp\Actions
 * @since 1.0.0
 */

namespace WpConvertToWebp\Actions;

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
     * Registers filters for automatic WebP replacement in content.
     *
     * This method hooks into 'the_content', 'post_thumbnail_html', and 'widget_text'
     * to replace <img> tags with <picture> elements containing WebP sources.
     *
     * @since 1.0.0
     * 
     * @return void
     */
    public function run()
    {
        add_filter('the_content', [static::class, 'replace_webp']);
        add_filter('post_thumbnail_html', [static::class, 'replace_webp']);
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
        return preg_replace_callback(
            '/<img\s+[^>]*src=["\']([^"\']+)["\'][^>]*>/i',
            ['\\WpConvertToWebp\\Replacer', 'prepare'],
            $content
        );
    }
}
