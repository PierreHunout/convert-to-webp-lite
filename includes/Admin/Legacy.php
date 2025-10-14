<?php

/**
 * Handles AJAX actions for the legacy WebP conversion process and progress bar.
 *
 * @package WpConvertToWebp\Admin
 * @since 1.0.0
 */

namespace WpConvertToWebp\Admin;

use WpConvertToWebp\Tools;
use WpConvertToWebp\Converter;

/**
 * This check prevents direct access to the plugin file,
 * ensuring that it can only be accessed through WordPress.
 * 
 * @since 1.0.0
 */
if (!defined('WPINC')) {
    die;
}

class Legacy
{

    /**
     * Registers AJAX actions for the legacy conversion process.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public static function run()
    {
        add_action('wp_ajax_get_attachments', [self::class, 'get_attachments']);
        add_action('wp_ajax_convert', [self::class, 'convert']);
    }

    /**
     * AJAX handler to get all image attachment IDs for conversion.
     *
     * Checks nonce for security, fetches attachments using Tools::get_attachments(),
     * and returns them as a JSON response.
     *
     * @since 1.0.0
     * 
     * @return void
     */
    public static function get_attachments()
    {
        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Access denied.', 'wp-convert-to-webp')]);
        }

        check_ajax_referer('convert_to_webp_ajax');
        $attachments = Tools::get_attachments();
        wp_send_json_success(['attachments' => $attachments]);
    }

    /**
     * AJAX handler to convert a single image to WebP format.
     *
     * Checks nonce for security, gets attachment metadata,
     * runs the conversion, and returns a message and CSS classes for UI feedback.
     *
     * @since 1.0.0
     * 
     * @return void
     */
    public static function convert()
    {
        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Access denied.', 'wp-convert-to-webp')]);
        }

        check_ajax_referer('convert_to_webp_ajax');

        // Validate and sanitize the attachment ID
        if (!isset($_POST['attachment_id'])) {
            wp_send_json_error(['message' => esc_html__('Invalid attachment ID.', 'wp-convert-to-webp')]);
        }

        $attachment_id  = intval(sanitize_text_field(wp_unslash($_POST['attachment_id'])));
        
        if ($attachment_id <= 0) {
            wp_send_json_error(['message' => esc_html__('Invalid attachment ID.', 'wp-convert-to-webp')]);
        }

        $metadata       = wp_get_attachment_metadata($attachment_id);

        $converter      = new Converter();
        $result         = $converter->prepare($attachment_id, $metadata);

        // Get message and classes from converter result for frontend display
        $message        = isset($result[0]['message']) ? $result[0]['message'] : esc_html__('Done', 'wp-convert-to-webp');
        $classes        = isset($result[0]['classes']) ? $result[0]['classes'] : [];

        wp_send_json_success([
            'message'   => $message,
            'classes'   => $classes
        ]);
    }
}
