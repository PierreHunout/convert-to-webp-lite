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
        $title      = __('No files found to process.', 'wp-convert-to-webp');

        // No files found notice
        if (isset($_GET['no_files']) && $_GET['no_files'] == '1') {
            $html   = <<<HTML
            <div class="notice is-dismissible convert-to-webp__notice convert-to-webp__notice--nofiles">
                <p>{$title}</p>
            </div>
            HTML;

            echo $html;
        }

        // Deletion notice
        if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
            $title  = __('Deleted WebP files', 'wp-convert-to-webp');
            $data   = get_transient('wp_convert_to_webp_deletion_data');
            delete_transient('wp_convert_to_webp_deletion_data');

            // Display notice if there is data
            if (isset($data) && is_array($data)) {
                $count = count($data);
                $html   = <<<HTML
                <div class="notice is-dismissible convert-to-webp__notice">
                    <p class="convert-to-webp__subtitle">{$title}: <strong>{$count}</strong></p>
                    <div class="convert-to-webp__container convert-to-webp__container--notice">
                        <div class="convert-to-webp__inner convert-to-webp__inner--notice">
                HTML;

                foreach ($data as $images) {
                    $html  .= <<<HTML
                            <ul class="convert-to-webp__messages">       
                    HTML;

                    foreach ($images as $image) {
                        $message    = $image['message'];
                        $classes    = $image['classes'];

                        $class_list = [];
                        foreach ($classes as $class) {
                            $class          = 'convert-to-webp__message--' . $class;
                            $class_list[]   = $class;
                        }

                        $classes    = implode(' ', $class_list);

                        $html      .= <<<HTML
                                <li class="convert-to-webp__message {$classes}">{$message}</li>
                        HTML;
                    }

                    $html  .= <<<HTML
                            </ul> 
                    HTML;
                }

                $html  .= <<<HTML
                        </div>
                    </div>
                </div>
                HTML;

                echo $html;
            }
        }
    }
}
