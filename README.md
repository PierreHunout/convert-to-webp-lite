# WP Convert to WebP

A simple and efficient WordPress plugin to convert your images to the WebP format for better performance and reduced bandwidth.

## Screenshot

![Plugin Screenshot](./assets/images/screenshot.png?raw=true "WP Convert to WebP Screenshot")

## Features

- Automatically converts uploaded images from JPG, PNG or GIF to WebP format
- Supports bulk conversion of existing images in the media library
- Keeps original images as backup
- Seamless integration with WordPress media management
- Lightweight and easy to use

## Installation

1. Clone or download this repository to your `wp-content/plugins` directory.
2. Install the dependencies using Composer.
3. Activate the plugin from the WordPress admin dashboard.

## Development

This plugin uses Composer for dependency management and provides several useful scripts for development:

```bash
# Navigate to the plugin directory
cd wp-content/plugins/wp-convert-to-webp

# Install dependencies
composer install
```

This will install development dependencies including:

- **PHP CodeSniffer (PHPCS)** - Code quality and WordPress coding standards
- **PHPUnit** - Unit testing framework
- **PHPStan** - Static analysis tool
- **Variable Analysis** - Additional code analysis

### Code Quality

```bash
# Run PHP CodeSniffer to check coding standards
composer run phpcs

# Automatically fix coding standards issues
composer run phpcs:fix

# Generate a summary report
composer run phpcs:report
```

### Testing

```bash
# Run all tests (PHPCS + PHPUnit)
composer run test

# Run only unit tests (quick test)
composer run test:quick

# Run only PHPUnit tests
composer run phpunit

# Run unit tests only
composer run phpunit:unit

# Run integration tests only
composer run phpunit:integration

# Generate coverage report
composer run phpunit:coverage
```

### Autoloading

The plugin uses PSR-4 autoloading for better code organization:

- Namespace: `WpConvertToWebp\`
- Classes are located in the `includes/` directory

## Usage

- New images uploaded to the media library will be automatically converted to WebP.
- To convert existing images, go to the plugin settings page and use the bulk conversion tool.
- The original images are preserved for compatibility purpose

## Requirements

### End Users

- PHP 7.4 or higher
- WordPress 5.0 or higher
- The PHP GD or Imagick extension with WebP support enabled

### Contributors & Developers

- PHP 7.4 or higher
- [Composer](https://getcomposer.org/) for dependency management
- WordPress 5.0 or higher
- The PHP GD or Imagick extension with WebP support enabled

## License

This project is open source and available under the [GPL-3.0 License](./LICENSE.md).

---

Special thanks to [Romain Preston](https://github.com/romain-preston) for his help, code review and insightful comments

---

Developed by Pierre Hunout
Email: [pierre.hunout@gmail.com](mailto:pierre.hunout@gmail.com)
