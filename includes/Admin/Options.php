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
    public static function run()
    {
        add_action('admin_menu', [self::class, 'add_options_page']);
        add_action('admin_init', [self::class, 'save_options']);
        add_action('admin_post_wpctw_convert_old', [self::class, 'convert_old_images']);
        add_action('admin_post_wpctw_delete_all_webp', [self::class, 'delete_all_webp']);
        add_action('wp_ajax_wpctw_get_sample_image', [self::class, 'ajax_get_sample_image']);
    }

    public static function add_options_page()
    {
        add_options_page(
            __('WebP Conversion', 'wp-convert-to-webp'),
            __('WebP Conversion', 'wp-convert-to-webp'),
            'manage_options',
            'wp-convert-to-webp-options',
            [self::class, 'render_page']
        );
    }

    public static function render_page()
    {
        $webp_quality = get_option('webp_quality', 85);
        $delete_on_uninstall = get_option('wpctw_delete_webp_on_uninstall', false);

        ?>
        <div class="wrap">
            <h1><?php _e('WebP Conversion Options', 'wp-convert-to-webp'); ?></h1>
            <form method="post" action="">
                <?php wp_nonce_field('wpctw_save_options'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('WebP Quality', 'wp-convert-to-webp'); ?></th>
                        <td>
                            <input type="number" id="webp_quality" name="webp_quality" min="0" max="100" value="<?php echo esc_attr($webp_quality); ?>" style="width:70px;">
                            <input type="range" id="webp_quality_slider" min="0" max="100" value="<?php echo esc_attr($webp_quality); ?>" style="width:150px;vertical-align:middle;margin-left:10px;">
                            <p class="description"><?php _e('Default: 85. Higher means better quality but larger files.', 'wp-convert-to-webp'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Delete WebP files on uninstall', 'wp-convert-to-webp'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="wpctw_delete_webp_on_uninstall" value="1" <?php checked($delete_on_uninstall, 1); ?> />
                                <?php _e('Delete all WebP files when uninstalling the plugin', 'wp-convert-to-webp'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="hidden" name="action" value="save_options">
                    <button type="submit" class="button button-primary"><?php _e('Save options', 'wp-convert-to-webp'); ?></button>
                </p>
            </form>

            <hr>

            <h2><?php _e('Convert old images', 'wp-convert-to-webp'); ?></h2>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('wpctw_convert_old'); ?>
                <input type="hidden" name="action" value="wpctw_convert_old">
                <button type="submit" class="button"><?php _e('Convert all previously uploaded images to WebP', 'wp-convert-to-webp'); ?></button>
            </form>

            <h2><?php _e('Delete all WebP files', 'wp-convert-to-webp'); ?></h2>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" onsubmit="return confirm('<?php echo esc_js(__('Are you sure you want to delete all WebP files?', 'wp-convert-to-webp')); ?>');">
                <?php wp_nonce_field('wpctw_delete_all_webp'); ?>
                <input type="hidden" name="action" value="wpctw_delete_all_webp">
                <button type="submit" class="button button-danger"><?php _e('Delete all WebP files', 'wp-convert-to-webp'); ?></button>
            </form>

            <hr>
            <h2><?php _e('Original / WebP Comparison', 'wp-convert-to-webp'); ?></h2>
            <p><?php _e('Here is an example comparison between an original image and its generated WebP version:', 'wp-convert-to-webp'); ?></p>
            <div id="webp-compare" style="max-width:400px;position:relative;">
                <img id="original-img" src="" alt="<?php esc_attr_e('Original', 'wp-convert-to-webp'); ?>" style="width:100%;display:block;">
                <img id="webp-img" src="" alt="<?php esc_attr_e('WebP', 'wp-convert-to-webp'); ?>" style="width:100%;position:absolute;top:0;left:0;clip-path:inset(0 50% 0 0);">
                <input type="range" id="compare-slider" min="0" max="100" value="50" style="width:100%;margin-top:10px;">
                <div style="display:flex;justify-content:space-between;font-size:12px;">
                    <span><?php _e('Original', 'wp-convert-to-webp'); ?></span><span>WebP</span>
                </div>
            </div>
        </div>
        <?php
    }

    public static function save_options()
    {
        if (
            isset($_POST['action']) && $_POST['action'] === 'save_options'
            && check_admin_referer('wpctw_save_options')
        ) {
            $quality = isset($_POST['webp_quality']) ? intval($_POST['webp_quality']) : 85;
            if ($quality < 0) $quality = 0;
            if ($quality > 100) $quality = 100;
            update_option('webp_quality', $quality);
            update_option('wpctw_delete_webp_on_uninstall', isset($_POST['wpctw_delete_webp_on_uninstall']) ? 1 : 0);
        }
    }

    public static function convert_old_images()
    {
        if (!current_user_can('manage_options') || !check_admin_referer('wpctw_convert_old')) {
            wp_die(__('Not allowed', 'wp-convert-to-webp'));
        }
        // Convert all previously uploaded images
        $upload_dir = wp_upload_dir();
        $base_dir = trailingslashit($upload_dir['basedir']);
        $quality = get_option('webp_quality', 85);

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base_dir));
        
        foreach ($files as $file) {
            if ($file->isDir()) continue;
            $ext = strtolower($file->getExtension());
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $info = pathinfo($file->getPathname());
                $webp_path = $info['dirname'] . '/' . $info['filename'] . '.webp';
                if (!file_exists($webp_path)) {
                    $image = null;
                    switch ($ext) {
                        case 'jpg':
                        case 'jpeg':
                            $image = imagecreatefromjpeg($file->getPathname());
                            break;
                        case 'png':
                            $image = imagecreatefrompng($file->getPathname());
                            break;
                        case 'gif':
                            $image = imagecreatefromgif($file->getPathname());
                            break;
                    }
                    if ($image) {
                        imagewebp($image, $webp_path, $quality);
                        imagedestroy($image);
                    }
                }
            }
        }
        wp_redirect(add_query_arg('converted', '1', wp_get_referer()));
        exit;
    }

    public static function delete_all_webp()
    {
        if (!current_user_can('manage_options') || !check_admin_referer('wpctw_delete_all_webp')) {
            wp_die('Non autorisé');
        }
        $upload_dir = wp_upload_dir();
        $base_dir = trailingslashit($upload_dir['basedir']);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base_dir));
        foreach ($files as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'webp') {
                @unlink($file->getPathname());
            }
        }
        wp_redirect(add_query_arg('webp_deleted', '1', wp_get_referer()));
        exit;
    }

    public static function ajax_get_sample_image()
    {
        $upload_dir = wp_upload_dir();
        $baseurl = $upload_dir['baseurl'];
        $basedir = $upload_dir['basedir'];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($basedir));
        foreach ($files as $file) {
            if ($file->isDir()) continue;
            $ext = strtolower($file->getExtension());
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $rel = str_replace($basedir, '', $file->getPathname());
                $original = $baseurl . $rel;
                $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $original);
                $webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file->getPathname());
                if (file_exists($webp_path)) {
                    wp_send_json(['original' => $original, 'webp' => $webp]);
                }
            }
        }
        wp_send_json(['original' => '', 'webp' => '']);
    }
}
