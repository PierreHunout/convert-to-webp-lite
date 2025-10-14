<?php

/**
 * Manages the options page and admin actions for the WP Convert to WebP plugin.
 *
 * @package WpConvertToWebp\Admin
 * @since 1.0.0
 */

namespace WpConvertToWebp\Admin;

use WpConvertToWebp\Tools;
use WpConvertToWebp\Cleaner;

/**
 * This check prevents direct access to the plugin file,
 * ensuring that it can only be accessed through WordPress.
 * 
 * @since 1.0.0
 */
if (!defined('WPINC')) {
    die;
}

class Options
{

    /**
     * Class Runner for the WebP conversion options.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public static function run()
    {
        add_action('admin_menu', [self::class, 'add_options']);
        add_action('admin_init', [self::class, 'save_options']);

        // Register custom admin actions for legacy conversion and deletion
        add_action('admin_post_delete_all_webp', [self::class, 'delete_all_webp']);
    }

    /**
     * Adds the plugin options page to the WordPress admin menu.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public static function add_options()
    {
        add_menu_page(
            __('WebP Conversion', 'wp-convert-to-webp'),
            __('WebP Conversion', 'wp-convert-to-webp'),
            'manage_options',
            'wp-convert-to-webp',
            [self::class, 'render_page'],
            'dashicons-images-alt2',
            99
        );
    }

    /**
     * Renders the plugin options page in the WordPress admin.
     * Displays forms for options, conversion, deletion, and comparison UI.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public static function render_page()
    {
        // Enqueue media scripts for image selector
        wp_enqueue_media();

        // Define allowed HTML tags for wp_kses once
        $allowed_html           = ['strong' => []];

        $webp_quality           = get_option('convert_to_webp_quality', 85);
        $replace_mode           = get_option('convert_to_webp_replace_mode', false);
        $delete_on_deactivate   = get_option('delete_webp_on_deactivate', false);
        $delete_on_uninstall    = get_option('delete_webp_on_uninstall', false);
?>
        <div class="wrap convert-to-webp">
            <h1 class="convert-to-webp__title"><?php esc_html_e('Manage WebP Conversion', 'wp-convert-to-webp'); ?></h1>
            <div id="convert-to-webp-grid" class="convert-to-webp__grid convert-to-webp__grid--main">
                <div class="convert-to-webp__forms">
                    <!-- Options form -->
                    <form method="post" action="" class="convert-to-webp__form convert-to-webp__form--options">
                        <?php wp_nonce_field('convert_to_webp_save_options'); ?>
                        <div class="convert-to-webp__table">
                            <div class="convert-to-webp__row">
                                <h2 class="convert-to-webp__subtitle"><?php esc_html_e('WebP Quality', 'wp-convert-to-webp'); ?></h2>
                                <div class="convert-to-webp__inputs">
                                    <input type="number" id="convert_to_webp_quality" class="convert-to-webp__input convert-to-webp__input--number" name="convert_to_webp_quality" min="0" max="100" value="<?php echo esc_attr($webp_quality); ?>">
                                    <input type="range" id="convert_to_webp_quality_slider" class="convert-to-webp__input convert-to-webp__input--range" min="0" max="100" value="<?php echo esc_attr($webp_quality); ?>" oninput="document.getElementById('convert_to_webp_quality').value = this.value;">
                                </div>
                                <p class="convert-to-webp__description">
                                    <?php esc_html_e('Higher means better quality but larger files.', 'wp-convert-to-webp'); ?>
                                </p>
                            </div>

                            <div class="convert-to-webp__row">
                                <h2 class="convert-to-webp__subtitle"><?php esc_html_e('Replace mode', 'wp-convert-to-webp'); ?></h2>
                                <p class="convert-to-webp__description">
                                    <?php 
                                    echo wp_kses(__('Enable replacement of <strong>&#60;img&#62;</strong> tags with <strong>&#60;picture&#62;</strong> tags.', 'wp-convert-to-webp'), $allowed_html); 
                                    ?>
                                </p>
                                <div class="convert-to-webp__inputs">
                                    <input type="checkbox" class="convert-to-webp__input convert-to-webp__input--toggle" name="convert_to_webp_replace_mode" value="1" <?php checked($replace_mode, 1); ?> />
                                    <label class="convert-to-webp__label">
                                        <?php 
                                        echo wp_kses(__('Use <strong>&#60;picture&#62;</strong> tags', 'wp-convert-to-webp'), $allowed_html); 
                                        ?>
                                    </label>
                                </div>
                                <p class="convert-to-webp__description">
                                    <?php 
                                    echo wp_kses(__('If enabled, all images will be replaced by their WebP versions inside the <strong>&#60;picture&#62;</strong> tags. Otherwise, the original <strong>&#60;img&#62;</strong> tags will be used.', 'wp-convert-to-webp'), $allowed_html); 
                                    ?>
                                </p>
                            </div>

                            <div class="convert-to-webp__row">
                                <h2 class="convert-to-webp__subtitle">
                                    <?php 
                                    echo wp_kses(__('Clean data on <strong>deactivate</strong>', 'wp-convert-to-webp'), $allowed_html); 
                                    ?>
                                </h2>
                                <div class="convert-to-webp__inputs">
                                    <input type="checkbox" class="convert-to-webp__input convert-to-webp__input--toggle" name="delete_webp_on_deactivate" value="1" <?php checked($delete_on_deactivate, 1); ?> />
                                    <label class="convert-to-webp__label"><?php esc_html_e('Delete WebP files and options', 'wp-convert-to-webp'); ?></label>
                                </div>
                            </div>

                            <div class="convert-to-webp__row">
                                <h2 class="convert-to-webp__subtitle">
                                    <?php 
                                    echo wp_kses(__('Clean data on <strong>uninstall</strong>', 'wp-convert-to-webp'), $allowed_html); 
                                    ?>
                                </h2>
                                <div class="convert-to-webp__inputs">
                                    <input type="checkbox" class="convert-to-webp__input convert-to-webp__input--toggle" name="delete_webp_on_uninstall" value="1" <?php checked($delete_on_uninstall, 1); ?> />
                                    <label class="convert-to-webp__label"><?php esc_html_e('Delete WebP files and options', 'wp-convert-to-webp'); ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="convert-to-webp__submit">
                            <input type="hidden" name="action" value="save_options">
                            <button type="submit" class="button button-primary convert-to-webp__button convert-to-webp__button--primary"><?php esc_html_e('Save options', 'wp-convert-to-webp'); ?></button>
                        </div>
                    </form>

                    <!-- Legacy conversion form -->
                    <div class="convert-to-webp__form convert-to-webp__form--legacy">
                        <div class="convert-to-webp__table">
                            <div class="convert-to-webp__row">
                                <h2 class="convert-to-webp__subtitle"><?php esc_html_e('Convert old images', 'wp-convert-to-webp'); ?></h2>
                                <p class="convert-to-webp__description">
                                    <?php esc_html_e('This will convert all existing images in your media library to WebP format. New images will be converted automatically upon upload.', 'wp-convert-to-webp'); ?>
                                </p>
                                <div class="convert-to-webp__submit convert-to-webp__submit--secondary">
                                    <input type="hidden" name="action" value="convert_to_webp_legacy">
                                    <button type="submit" id="convert-to-webp-legacy" class="button convert-to-webp__button"><?php esc_html_e('Convert previously uploaded images', 'wp-convert-to-webp'); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Delete all WebP files form -->
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="convert-to-webp__form convert-to-webp__form--delete" onsubmit="return confirm('<?php echo esc_js(__('Are you sure you want to delete all WebP files?', 'wp-convert-to-webp')); ?>');">
                        <?php wp_nonce_field('delete_all_webp'); ?>
                        <div class="convert-to-webp__table">
                            <div class="convert-to-webp__row">
                                <h2 class="convert-to-webp__subtitle"><?php esc_html_e('Delete all WebP files', 'wp-convert-to-webp'); ?></h2>
                                <div class="convert-to-webp__submit convert-to-webp__submit--secondary">
                                    <input type="hidden" name="action" value="delete_all_webp">
                                    <button type="submit" id="convert-to-webp-delete-all" class="button button-danger convert-to-webp__button convert-to-webp__button--danger"><?php esc_html_e('Delete all WebP files', 'wp-convert-to-webp'); ?></button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Image comparison UI -->
                <div class="convert-to-webp__comparison">
                    <!-- Image Selector for Comparison UI -->
                    <form method="post" action="" id="comparison-form" class="convert-to-webp__form convert-to-webp__form--comparison">
                        <?php wp_nonce_field('convert_to_webp_comparison'); ?>
                        <div class="convert-to-webp__table">
                            <div class="convert-to-webp__row">
                                <h2 class="convert-to-webp__subtitle"><?php esc_html_e('Select an Image for Comparison', 'wp-convert-to-webp'); ?></h2>
                                <div class="convert-to-webp__submit convert-to-webp__submit--secondary">
                                    <button type="button" class="button convert-to-webp__button" id="comparison-button"><?php esc_html_e('Select Image', 'wp-convert-to-webp'); ?></button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Comparison UI for original vs WebP image -->
                    <div id="comparison-container" class="convert-to-webp__compare">
                        <div class="convert-to-webp__images">
                            <img id="comparison-original" class="convert-to-webp__image convert-to-webp__image--origin" src="">
                            <img id="comparison-webp" class="convert-to-webp__image convert-to-webp__image--webp" src="">
                        </div>
                        <input type="range" min="0" max="100" value="50" id="comparison-range" class="convert-to-webp__range">
                        <div class="convert-to-webp__handler"></div>
                        <div class="convert-to-webp__sizes">
                            <p class="convert-to-webp__size">Original size: <strong id="comparison-original-size"></strong></p>
                            <p class="convert-to-webp__size">WebP size: <strong id="comparison-webp-size"></strong></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Popup for conversion progress -->
            <div id="convert-to-webp-progress-popup" class="convert-to-webp__popup convert-to-webp__popup--progress">
                <div class="convert-to-webp__container">
                    <div class="convert-to-webp__inner convert-to-webp__grid">
                        <div class="convert-to-webp__header">
                            <h2 class="convert-to-webp__subtitle"><?php esc_html_e('Conversion Progress', 'wp-convert-to-webp'); ?></h2>
                        </div>
                        <div class="convert-to-webp__content">
                            <ul id="convert-to-webp-progress-messages" class="convert-to-webp__messages convert-to-webp__messages--progress"></ul>
                        </div>
                        <div class="convert-to-webp__sidebar">
                            <div class="convert-to-webp__chart convert-to-webp__chart--donut">
                                <canvas id="convert-to-webp-progress-donut" width="120" height="120" style="display:block;margin:auto;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="convert-to-webp-progress-close" class="convert-to-webp__button convert-to-webp__button--close"><?php esc_html_e('Close', 'wp-convert-to-webp'); ?></div>
            </div>
        </div>
<?php
    }

    /**
     * Handles saving the plugin options from the admin form.
     * Validates and saves quality and deletion options.
     *
     * @since 1.0.0
     * 
     * @return void
     */
    public static function save_options()
    {
        // Verify user capabilities first
        if (!current_user_can('manage_options')) {
            return;
        }

        if (
            isset($_POST['action']) && sanitize_text_field(wp_unslash($_POST['action'])) === 'save_options'
            && check_admin_referer('convert_to_webp_save_options')
        ) {
            $quality    = isset($_POST['convert_to_webp_quality']) ? intval(sanitize_text_field(wp_unslash($_POST['convert_to_webp_quality']))) : 85;

            // Ensure quality is within bounds
            if ($quality < 0) {
                $quality    = 0;
            }
            if ($quality > 100) {
                $quality    = 100;
            }

            $mode       = isset($_POST['convert_to_webp_replace_mode']) && sanitize_text_field(wp_unslash($_POST['convert_to_webp_replace_mode'])) === '1' ? 1 : 0;
            $deactivate = isset($_POST['delete_webp_on_deactivate']) && sanitize_text_field(wp_unslash($_POST['delete_webp_on_deactivate'])) === '1' ? 1 : 0;
            $uninstall  = isset($_POST['delete_webp_on_uninstall']) && sanitize_text_field(wp_unslash($_POST['delete_webp_on_uninstall'])) === '1' ? 1 : 0;

            update_option('convert_to_webp_quality', $quality);
            update_option('convert_to_webp_replace_mode', $mode);
            update_option('delete_webp_on_deactivate', $deactivate);
            update_option('delete_webp_on_uninstall', $uninstall);

            // Add admin notice for successful save
            add_action('admin_notices', function () {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Options saved successfully.', 'wp-convert-to-webp') . '</p></div>';
            });
        }
    }

    /**
     * Deletes all .webp files in the uploads directory.
     * Stores deletion results for admin notice.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public static function delete_all_webp()
    {
        if (!current_user_can('manage_options') || !check_admin_referer('delete_all_webp')) {
            wp_die(esc_html__('Not allowed', 'wp-convert-to-webp'));
        }

        $attachments    = Tools::get_attachments();

        if (empty($attachments)) {
            $redirect_url   = add_query_arg([
                'no_files'      => '1',
                '_wpnonce'      => wp_create_nonce('wp_convert_to_webp_notice')
            ], admin_url('admin.php?page=wp-convert-to-webp'));
            wp_safe_redirect($redirect_url);
            exit;
        }

        $result         = [];

        // Loop through all attachments and delete their WebP files
        foreach ($attachments as $attachment_id) {
            $metadata   = wp_get_attachment_metadata($attachment_id);
            $cleaner    = new Cleaner();
            $result[]   = $cleaner->prepare($attachment_id, $metadata);
        }

        // Store details for notice
        set_transient('wp_convert_to_webp_deletion_data', $result, 60);

        // Clear the cache
        wp_cache_flush();

        // Clear also the media library cache
        wp_update_attachment_metadata(0, []);

        // Redirect back to the options page with a success flag
        $redirect_url   = add_query_arg([
            'deleted'      => '1',
            '_wpnonce'     => wp_create_nonce('wp_convert_to_webp_notice')
        ], admin_url('admin.php?page=wp-convert-to-webp'));
        
        wp_safe_redirect($redirect_url);

        exit;
    }
}
