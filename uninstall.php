<?php

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load WordPress functions if needed (for get_option and wp_upload_dir)
if (!function_exists('get_option')) {
    require_once dirname(__FILE__, 3) . '/wp-load.php';
}

// Check if the option to delete WebP files on uninstall is enabled
$delete_webp = get_option('wpctw_delete_webp_on_uninstall', false);

if ($delete_webp) {
    try {
        $upload_dir = wp_upload_dir();
        $base_dir = trailingslashit($upload_dir['basedir']);

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base_dir));
        
        foreach ($files as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'webp') {
                if (!@unlink($file->getPathname())) {
                    error_log('[WP Convert to WebP] Failed to delete: ' . $file->getPathname());
                }
            }
        }
    } catch (Throwable $e) {
        error_log('[WP Convert to WebP] Uninstall error: ' . $e->getMessage());
    }
}