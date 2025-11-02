=== Poetry Convert to WebP ===
Contributors: hunoutpierre
Donate link: https://www.buymeacoffee.com/pierrehunout
Tags: webp, images, performance, optimization, speed
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPL-3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Automatically convert your images to WebP format for faster page loads and better performance. Save bandwidth and boost your site speed effortlessly.

== Description ==

**Poetry Convert to WebP** is a powerful yet lightweight WordPress plugin that automatically converts your images to the modern WebP format, delivering up to 30-50% smaller file sizes without sacrificing quality. Give your visitors a faster, smoother browsing experience while reducing bandwidth costs and improving your SEO rankings.

= Why WebP? =

WebP is a modern image format developed by Google that provides superior compression for images on the web. Converting your images to WebP means:

* **30-50% smaller file sizes** compared to JPEG and PNG
* **Faster page load times** leading to better user experience
* **Improved SEO rankings** thanks to better Core Web Vitals scores
* **Reduced bandwidth usage** and lower hosting costs
* **Universal browser support** - all modern browsers now support WebP

= Key Features =

* **Automatic Conversion** - New uploads are instantly converted to WebP  
* **Bulk Conversion Tool** - Convert your entire media library with one click  
* **Original Files Preserved** - Your original images are safely kept as backup  
* **Picture Tag Support** - Use HTML5 `<picture>` tags for automatic fallback  
* **Quality Control** - Adjust WebP quality from 0-100 (default: 85)  
* **Debug Mode** - Comprehensive logging for troubleshooting  
* **Clean Uninstall** - Option to remove all WebP files and settings  
* **WordPress Standards** - Fully compliant with WordPress coding standards  

= How It Works =

1. **Upload an image** - The plugin automatically creates a WebP version
2. **Bulk convert existing images** - Use the built-in tool for your media library
3. **Enjoy faster load times** - Your site automatically serves optimized images
4. **Original images are safe** - Kept as fallback for older browsers

= Browser Compatibility =

The plugin includes smart fallback mechanisms:

* Modern browsers (Chrome, Firefox, Edge, Safari 14+) get WebP images
* Older browsers automatically receive the original JPEG/PNG images
* No JavaScript required - uses native HTML5 `<picture>` tags

= Perfect For =

* Photographers and portfolios
* E-commerce sites with many product images
* Blogs and news sites
* Any website looking to improve performance
* Sites targeting mobile users
* SEO-conscious website owners

= Technical Features =

* Supports the **GD** PHP extension with WebP support
* Processes all WordPress image sizes (thumbnail, medium, large, full)
* Handles JPG, PNG, and GIF formats
* Comprehensive error handling with detailed logging
* AJAX-powered bulk conversion with progress tracking

= Support =

* **GitHub:** [Report issues and contribute](https://github.com/PierreHunout/poetry-convert-to-webp)

= Privacy =

This plugin does not:
* Collect any user data
* Make external API calls
* Track your usage
* Store personal information

All image processing happens locally on your server.

The plugin preserves your original images while creating optimized WebP versions, ensuring compatibility across different browsers and use cases.

== Installation ==

= Automatic Installation (Recommended) =

1. Log in to your WordPress admin panel
2. Navigate to **Plugins → Add New**
3. Search for "Poetry Convert to WebP"
4. Click **Install Now** and then **Activate**
5. Go to **Settings → Poetry Convert to WebP** to configure options
6. Use the bulk conversion tool to convert existing images

= Manual Installation =

1. Download the plugin ZIP file
2. Upload the `poetry-convert-to-webp` folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress
4. Go to **Settings → Poetry Convert to WebP** to configure options

= First Time Setup =

1. **Set your preferred quality** - Default is 85 (recommended for most sites)
2. **Enable picture tag mode** - For automatic browser fallback
3. **Convert existing images** - Use the bulk conversion tool
4. **Test your site** - Verify images are loading correctly

= Server Requirements =

Before installation, ensure your server has:
* PHP 7.4 or higher
* WordPress 5.0 or higher
* **PHP GD extension with WebP support**

= Post-Installation =

After activation:
* **New uploads** are automatically converted to WebP
* **Existing images** can be converted using the bulk tool in Settings
* **Original images** are preserved as backup
* **Settings** can be adjusted anytime under Settings → Poetry Convert to WebP

== Frequently Asked Questions ==

= What image formats are supported? =

The plugin supports conversion of **JPG**, **PNG**, and **GIF** image formats to WebP. It processes all WordPress image sizes automatically (thumbnail, medium, large, full, and any custom sizes).

= Are my original images preserved? =

Yes! The plugin **never modifies or deletes** your original images. It creates WebP versions alongside the originals, ensuring you always have a backup and compatibility with older browsers.

= What happens if a browser doesn't support WebP? =

The plugin uses HTML5 `<picture>` tags (when enabled) that provide automatic fallback to the original JPEG/PNG images for browsers that don't support WebP. No JavaScript required!

= What PHP extensions are required? =

You need:
* **PHP GD extension** with WebP support

The plugin will automatically detect if GD is available with WebP support. Most modern servers have this extension installed by default.

= Can I convert existing images in my media library? =

Absolutely! Go to **Settings → Poetry Convert to WebP** and click the **"Convert previously uploaded images"** button. The plugin will process all images in your media library with a real-time progress indicator.

= What quality setting should I use? =

The default quality of **85** is recommended for most websites as it provides an excellent balance between file size and image quality. You can adjust this from 0-100:
* **60-70:** Smaller files, some quality loss (good for large galleries)
* **80-90:** Balanced (recommended for most sites)
* **90-100:** Highest quality, larger files (for photography sites)

= Will this slow down my site? =

No! Conversion only happens during image upload or bulk conversion, not when pages are viewed. In fact, WebP images are 30-50% smaller than JPEG/PNG, so your site will load **faster** for visitors.

= Can I delete all WebP files if I change my mind? =

Yes. Go to **Settings → Poetry Convert to WebP** and scroll to the bottom. Click **"Delete all WebP files"** to remove all WebP versions. Your original images are never touched.

= Where are WebP files stored? =

WebP files are stored in the same directory as the original images in your WordPress uploads folder, with a `.webp` extension. For example:
* Original: `/wp-content/uploads/2025/10/image.jpg`
* WebP: `/wp-content/uploads/2025/10/image.jpg.webp`

= Does this plugin use external services? =

No. All image processing happens **locally on your server**. The plugin does not send your images to any external API or service.

= What data does this plugin collect? =

**None.** The plugin does not collect, store, or transmit any personal data or usage statistics. It respects your privacy completely.

= How do I enable debug mode? =

Go to **Settings → Poetry Convert to WebP** and enable **"Debug mode"**. Logs will be stored in `/wp-content/poetry-convert-to-webp-logs/` folder. This is useful for troubleshooting conversion issues.

= I'm getting conversion errors. What should I do? =

1. Enable **Debug mode** in the settings
2. Check the log files in `/wp-content/poetry-convert-to-webp-logs/`
3. Verify your server has GD extension with WebP support
4. Increase PHP memory limit if needed (add `define('WP_MEMORY_LIMIT', '256M');` to wp-config.php)
5. Contact support with the error logs

= Can I contribute to this plugin? =

Yes! We welcome contributions. Visit our [GitHub repository](https://github.com/PierreHunout/poetry-convert-to-webp) to report issues, suggest features, or submit pull requests.

== Screenshots ==

1. **Plugin Settings Page** - Configure WebP quality settings (0-100%), enable picture tag mode for automatic browser fallback, toggle debug mode for troubleshooting. The clean interface makes it easy to customize the plugin behavior to your needs.

2. **Image Quality Comparison** - Preview the difference between original and WebP images before bulk conversion. Side-by-side comparison helps you choose the optimal quality setting that balances file size reduction with visual quality for your specific needs.

3. **Bulk Operations Tool** - Convert your entire media library to WebP format with one click or safely delete all previously converted WebP images in a single operation.

4. **Conversion Progress Modal** - Visual feedback during bulk operations showing current file being processed and overall progress percentage. The tool safely processes images in batches to prevent server timeouts.

5. **WebP Deletion Confirmation** - Safety confirmation dialog when deleting all WebP files from your media library. This ensures you don't accidentally remove converted files, while preserving all original images as backup. 

== Changelog ==

= 1.0.0 - 2025-11-01 =

Automatically convert images to WebP format for better performance. Reduces image file sizes while maintaining quality. Includes bulk conversion tool, browser fallback support, and quality controls. Preserves all original images.

**Core Features:**
* Automatic WebP conversion on image upload
* Intelligent browser detection with fallback support
* Transparent `<picture>` tag implementation
* Adjustable quality settings (1-100%)
* Debug mode for developers

**Bulk Operations:**
* Bulk conversion for entire media library
* Real-time progress tracking
* Safe deletion of all WebP files
* Batch processing with memory optimization

**Security & Quality:**
* WordPress nonce verification
* Capability-based access control
* Input sanitization and validation
* MIME type verification
* Secure file operations

**Developer Features:**
* WordPress Coding Standards compliant
* Comprehensive PHPDoc documentation
* OOP architecture with dependency injection

For detailed documentation, visit the [GitHub repository](https://github.com/pierrehunout/poetry-convert-to-webp)

== Requirements ==

* PHP 7.4 or higher
* WordPress 5.0 or higher
* The PHP GD extension with WebP support enabled

== Credits ==

Developed by Pierre Hunout - pierre.hunout@gmail.com