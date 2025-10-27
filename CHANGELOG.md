# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-10-27

### Added

- Initial release of WP Convert to WebP plugin
- Automatic WebP conversion for newly uploaded images (JPG, PNG, GIF)
- Bulk conversion tool for existing images in media library
- WebP quality setting (configurable from 0-100, default 85)
- Replace mode with `<picture>` tag support for better browser compatibility
- Debug mode with comprehensive error logging
- Option to clean data on plugin deactivate or uninstall
- Image comparison tool to preview original vs WebP quality
- Progress tracking for bulk conversions
- Browser compatibility fallback to original images
- Multi-language support:
  - French (fr_FR) - 100% translated
  - Portuguese - Portugal (pt_PT) - 100% translated
- Comprehensive error handling with specific exception types:
  - InvalidArgumentException for validation errors
  - RuntimeException for execution errors
  - Detailed error messages with file context
- WordPress filesystem integration for secure file operations
- Support for all WordPress image sizes (thumbnail, medium, large, full)

### Developer Features

- Complete unit test suite (276 tests, 601 assertions)
- Integration test suite (40 tests) with WordPress Test Suite
- Code coverage analysis with Xdebug
- PHP CodeSniffer integration with WordPress Coding Standards
- Composer scripts for testing, linting, and coverage
- Brain Monkey and Mockery for advanced test mocking
- PSR-4 autoloading
- Comprehensive PHPDoc documentation

### Technical Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- GD extension or Imagick extension with WebP support

### Security

- Nonce verification for all AJAX requests
- Capability checks for admin operations
- Sanitization and validation of all user inputs
- Direct file access prevention
- WordPress filesystem API for secure file operations

[1.0.0]: https://github.com/PierreHunout/wp-convert-to-webp/releases/tag/v1.0.0
