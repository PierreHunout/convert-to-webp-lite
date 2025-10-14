<?php

/**
 * Displays admin notices for the WP Convert to WebP plugin.
 *
 * @package WpConvertToWebp\Admin
 * @since 1.0.0
 */

namespace WpConvertToWebp\Admin;

/**
 * This check prevents direct access to the plugin file,
 * ensuring that it can only be accessed through WordPress.
 * 
 * @since 1.0.0
 */
if (!defined('WPINC')) {
    die;
}

class Notices
{

    /**
     * Class Runner for the WebP conversion notices.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public static function run()
    {
        // Display admin notices for deletion results
        add_action('admin_notices', [self::class, 'display_notices']);
    }

    /**
     * Displays admin notices for deletion results.
     * Shows details for each processed file.
     *
     * @since 1.0.0
     * 
     * @return void
     */
    public static function display_notices()
    {
        // Only show notices on our plugin's admin page
        if (!isset($_GET['page']) || sanitize_text_field(wp_unslash($_GET['page'])) !== 'wp-convert-to-webp') {
            return;
        }

        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Verify nonce for notice parameters to prevent tampering
        if (
            (isset($_GET['no_files']) || isset($_GET['deleted'])) && 
            (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'wp_convert_to_webp_notice'))
        ) {
            return;
        }

        $title  = esc_html__('No files found to process.', 'wp-convert-to-webp');

        // No files found notice
        if (isset($_GET['no_files']) && sanitize_text_field(wp_unslash($_GET['no_files'])) === '1') {
            echo '<div class="notice is-dismissible convert-to-webp__notice convert-to-webp__notice--nofiles">
                <p>' . esc_html($title) . '</p>
            </div>';
        }

        // Deletion notice
        if (isset($_GET['deleted']) && sanitize_text_field(wp_unslash($_GET['deleted'])) === '1') {
            $title  = esc_html__('Deleted WebP files', 'wp-convert-to-webp');
            $data   = get_transient('wp_convert_to_webp_deletion_data');
            delete_transient('wp_convert_to_webp_deletion_data');

            // Display notice if there is data
            if (isset($data) && is_array($data)) {
                $count  = count($data);
                echo '<div class="notice is-dismissible convert-to-webp__notice convert-to-webp__notice--deletion">
                    <p class="convert-to-webp__subtitle">' . esc_html($title) . ': <strong>' . esc_html($count) . '</strong></p>
                    <div class="convert-to-webp__container convert-to-webp__container--notice">
                        <div class="convert-to-webp__inner convert-to-webp__inner--notice">
                ';

                foreach ($data as $images) {
                    echo '<ul class="convert-to-webp__messages">';

                    foreach ($images as $image) {
                        $message        = $image['message'];
                        $classes        = $image['classes'];

                        $class_list     = [];
                        foreach ($classes as $class) {
                            $class          = 'convert-to-webp__message--' . sanitize_html_class($class);
                            $class_list[]   = $class;
                        }

                        $classes        = implode(' ', $class_list);

                        $allowed_html   = ['span' => []];
                        echo '<li class="convert-to-webp__message ' . esc_attr($classes) . '">' . wp_kses($message, $allowed_html) . '</li>';
                    }

                    echo '</ul>';
                }

                echo '</div></div></div>';
            }
        }
    }
}
