<?php
/**
 * This file is responsible for managing the options page of the WebP conversion plugin.
 * It includes methods to add the options page, render the form, handle form submissions,
 * and save options related to WebP conversion.
 *
 * @package WpConvertToWebp\Admin
 * @since 1.0.0
 */

namespace WpConvertToWebp\Admin;

use WpConvertToWebp\Tools;
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
        add_action('admin_post_convert_to_webp_legacy', [self::class, 'convert_to_webp_legacy']);
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
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public static function render_page()
    {
        // Enqueue media scripts for image selector
        wp_enqueue_media();

        $webp_quality           = get_option('convert_to_webp_quality', 85);
        $delete_on_deactivate   = get_option('delete_webp_on_deactivate', false);
        $delete_on_uninstall    = get_option('delete_webp_on_uninstall', false);
?>
        <div class="wrap convert-to-webp">
            <h1><?php _e('WebP Conversion Options', 'wp-convert-to-webp'); ?></h1>
            <div class="grid">
                <div class="convert-to-webp__forms">
                    <!-- Options form -->
                    <form method="post" action="" class="convert-to-webp__form convert-to-webp__form--options">
                        <?php wp_nonce_field('convert_to_webp_save_options'); ?>
                        <div class="convert-to-webp__form--table">
                            <div class="convert-to-webp__form--table__row">
                                <h2><?php _e('WebP Quality', 'wp-convert-to-webp'); ?></h2>
                                <div class="convert-to-webp__form--table__row__input">
                                    <input type="number" id="convert_to_webp_quality" name="convert_to_webp_quality" min="0" max="100" value="<?php echo esc_attr($webp_quality); ?>">
                                    <input type="range" id="convert_to_webp_quality_slider" min="0" max="100" value="<?php echo esc_attr($webp_quality); ?>" oninput="document.getElementById('convert_to_webp_quality').value = this.value;">
                                </div>
                                <p class="description"><?php _e('Default: 85. Higher means better quality but larger files.', 'wp-convert-to-webp'); ?></p>
                            </div>

                            <div class="convert-to-webp__form--table__row">
                                <h2><?php _e('Clean data on <strong>deactivate</strong>', 'wp-convert-to-webp'); ?></h2>
                                <div class="convert-to-webp__form--table__row__input">
                                    <input type="checkbox" name="delete_webp_on_deactivate" value="1" <?php checked($delete_on_deactivate, 1); ?> />
                                    <?php _e('Delete all WebP files', 'wp-convert-to-webp'); ?>
                                </div>
                            </div>

                            <div class="convert-to-webp__form--table__row">
                                <h2><?php _e('Clean data on <strong>uninstall</strong>', 'wp-convert-to-webp'); ?></h2>
                                <div class="convert-to-webp__form--table__row__input">
                                    <input type="checkbox" name="delete_webp_on_uninstall" value="1" <?php checked($delete_on_uninstall, 1); ?> />
                                    <?php _e('Delete all WebP files', 'wp-convert-to-webp'); ?>
                                </div>
                            </div>
                        </div>
                        <div class="convert-to-webp__form--submit">
                            <input type="hidden" name="action" value="save_options">
                            <button type="submit" class="button button-primary"><?php _e('Save options', 'wp-convert-to-webp'); ?></button>
                        </div>
                    </form>

                    <!-- Legacy conversion form -->
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="convert-to-webp__form convert-to-webp__form--legacy">
                        <?php wp_nonce_field('convert_webp_legacy'); ?>
                        <div class="convert-to-webp__form--table">
                            <div class="convert-to-webp__form--table__row">
                                <h2><?php _e('Convert old images', 'wp-convert-to-webp'); ?></h2>
                                <div class="convert-to-webp__form--submit convert-to-webp__form--submit__secondary">
                                    <input type="hidden" name="action" value="convert_to_webp_legacy">
                                    <button type="submit" class="button"><?php _e('Convert all previously uploaded images', 'wp-convert-to-webp'); ?></button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Delete all WebP files form -->
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="convert-to-webp__form convert-to-webp__form--delete" onsubmit="return confirm('<?php echo esc_js(__('Are you sure you want to delete all WebP files?', 'wp-convert-to-webp')); ?>');">
                        <?php wp_nonce_field('delete_all_webp'); ?>
                        <div class="convert-to-webp__form--table">
                            <div class="convert-to-webp__form--table__row">
                                <h2><?php _e('Delete all WebP files', 'wp-convert-to-webp'); ?></h2>
                                <div class="convert-to-webp__form--submit convert-to-webp__form--submit__secondary">
                                    <input type="hidden" name="action" value="delete_all_webp">
                                    <button type="submit" class="button button-danger"><?php _e('Delete all WebP files', 'wp-convert-to-webp'); ?></button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="convert-to-webp__comparison">
                    <!-- Image Selector for Comparison UI -->
                    <form method="post" action="" class="convert-to-webp__form convert-to-webp__form--comparison">
                        <div class="convert-to-webp__form--table">
                            <div class="convert-to-webp__form--table__row">
                                <h2><?php _e('Select an Image for Comparison', 'wp-convert-to-webp'); ?></h2>
                                <div class="convert-to-webp__form--submit convert-to-webp__form--submit__secondary">
                                    <button type="button" class="button" id="select-image-button"><?php _e('Select Image', 'wp-convert-to-webp'); ?></button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Comparison UI -->
                    <div class="convert-to-webp__compare">
                        <div class="convert-to-webp__image">
                            <img id="comparison-original" class="convert-to-webp__image--origin" src="">
                            <img id="comparison-webp" class="convert-to-webp__image--webp" src="">
                        </div>
                        <input type="range" min="0" max="100" value="50" class="convert-to-webp__range">
                        <div class="convert-to-webp__handler"></div>
                    </div>
                </div>
            </div>
        </div>
<?php
    }

    /**
     * Handles saving the plugin options from the admin form.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public static function save_options()
    {
        if (
            isset($_POST['action']) && $_POST['action'] === 'save_options'
            && check_admin_referer('convert_to_webp_save_options')
        ) {
            $quality = isset($_POST['convert_to_webp_quality']) ? intval($_POST['convert_to_webp_quality']) : 85;

            // Ensure quality is within bounds
            if ($quality < 0) {
                $quality = 0;
            }
            if ($quality > 100) {
                $quality = 100;
            }

            update_option('convert_to_webp_quality', $quality);
            update_option('delete_webp_on_deactivate', isset($_POST['delete_webp_on_deactivate']) ? 1 : 0);
            update_option('delete_webp_on_uninstall', isset($_POST['delete_webp_on_uninstall']) ? 1 : 0);
        }
    }

    /**
     * Converts all existing images in the uploads directory to WebP format.
     * Triggered by the admin action form.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public static function convert_to_webp_legacy()
    {
        if (!current_user_can('manage_options') || !check_admin_referer('convert_webp_legacy')) {
            wp_die(__('Not allowed', 'wp-convert-to-webp'));
        }

        $quality    = get_option('convert_to_webp_quality', 85);
        $files      = Tools::get_files();

        if ($files) {
            foreach ($files as $file) {
                if ($file->isDir()) {
                    continue;
                }

                $extension      = strtolower($file->getExtension());

                // Only process supported image types
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $info       = pathinfo($file->getPathname());
                    $webp_path  = $info['dirname'] . '/' . $info['filename'] . '.webp';
                    if (!file_exists($webp_path)) {

                        $image  = null;

                        // Create image resource from file
                        switch ($extension) {
                            case 'jpg':
                            case 'jpeg':
                                $image = @imagecreatefromjpeg($file->getPathname());
                                break;
                            case 'png':
                                $image = @imagecreatefrompng($file->getPathname());
                                break;
                            case 'gif':
                                $image = @imagecreatefromgif($file->getPathname());
                                break;
                        }

                        // Save as WebP if resource is valid
                        if ($image) {
                            imagewebp($image, $webp_path, $quality);
                            imagedestroy($image);
                        }
                    }
                }
            }

            // Redirect back to the options page with a success flag
            wp_redirect(add_query_arg('converted', '1', admin_url('admin.php?page=wp-convert-to-webp')));

            exit;
        }
    }

    /**
     * Deletes all .webp files in the uploads directory.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public static function delete_all_webp()
    {
        if (!current_user_can('manage_options') || !check_admin_referer('delete_all_webp')) {
            wp_die(__('Not allowed', 'wp-convert-to-webp'));
        }

        try {
            $files  = Tools::get_files();

            foreach ($files as $file) {
                if ($file->isFile() && strtolower($file->getExtension()) === 'webp') {
                    if (!@unlink($file->getPathname())) {
                        error_log('[WP Convert to WebP] Failed to delete: ' . $file->getPathname());
                    }

                    @unlink($file->getPathname());
                }
            }

            // Redirect back to the options page with a success flag
            wp_redirect(add_query_arg('webp_deleted', '1', admin_url('admin.php?page=wp-convert-to-webp')));
            exit;
        } catch (Throwable $error) {
            error_log('[WP Convert to WebP] Uninstall error: ' . $error->getMessage());
        }
    }
}
