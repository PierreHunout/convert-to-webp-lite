=== Convert to WebP ===
Contributors: pierrehunout
Tags: webp, images, performance, compression, optimization
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A simple and efficient WordPress plugin to convert your images to the WebP format for better performance and reduced bandwidth.

== Description ==

Convert to WebP is a lightweight WordPress plugin that automatically converts your uploaded images to the WebP format, providing better compression and improved website performance.

**Key Features:**

* Automatically converts uploaded images from JPG, PNG or GIF to WebP format
* Supports bulk conversion of existing images in the media library
* Keeps original images as backup for compatibility
* Seamless integration with WordPress media management
* Lightweight and easy to use
* Supports both GD and Imagick PHP extensions

The plugin preserves your original images while creating optimized WebP versions, ensuring compatibility across different browsers and use cases.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wp-convert-to-webp` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. The plugin will start converting new uploads automatically.
4. For existing images, use the bulk conversion tool in the plugin settings.

== Frequently Asked Questions ==

= What image formats are supported? =

The plugin supports JPG, PNG, and GIF image formats for conversion to WebP.

= Are my original images preserved? =

Yes, the plugin keeps your original images as backup while creating WebP versions.

= What happens if WebP is not supported? =

The plugin includes fallback mechanisms to serve original images when WebP is not supported by the browser.

= What PHP extensions are required? =

You need either the PHP GD extension or Imagick extension with WebP support enabled.

== Screenshots ==

1. Plugin settings and bulk conversion interface
2. Media library showing WebP converted images
3. Performance improvements dashboard

== Changelog ==

= 1.0.0 =
* Initial release
* Automatic WebP conversion for new uploads
* Bulk conversion tool for existing images
* Browser compatibility detection
* Original image preservation
* Multi-language support (French, Portuguese)

== Upgrade Notice ==

= 1.0.0 =
Initial release of WP Convert to WebP plugin.

== Requirements ==

* PHP 7.4 or higher
* WordPress 5.0 or higher
* The PHP GD or Imagick extension with WebP support enabled

== Credits ==

Special thanks to Romain Preston for his help, code review and insightful comments.

Developed by Pierre Hunout - pierre.hunout@gmail.com