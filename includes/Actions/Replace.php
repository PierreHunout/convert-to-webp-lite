<?php
/**
 * Handles replacement of <img> tags in WordPress content with <picture> elements including WebP sources.
 *
 * @package ConvertToWebpLite
 * @since 1.0.0
 */

namespace ConvertToWebpLite\Actions;

use ConvertToWebpLite\Utils\Replacer;
use RuntimeException;

/**
 * This check prevents direct access to the plugin file,
 * ensuring that it can only be accessed through WordPress.
 *
 * @since 1.0.0
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Replace
 *
 * Handles replacement of img tags with picture elements for WebP support.
 *
 * @since 1.0.0
 */
class Replace {

	/**
	 * Holds the Singleton instance.
	 *
	 * @since 1.0.0
	 * @var Replace|null The Singleton instance.
	 */
	protected static ?Replace $instance = null;

	/**
	 * Constructor to initialize the class.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Prevent cloning of the class
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization of the class
	 *
	 * @since 1.0.0
	 * @return void
	 * @throws RuntimeException Always throws exception to prevent unserialization.
	 */
	public function __wakeup() {
		throw new RuntimeException( 'Cannot unserialize a singleton.' );
	}

	/**
	 * Returns the Singleton instance of the plugin.
	 *
	 * @since 1.0.0
	 * @return Replace The Singleton instance.
	 */
	public static function get_instance(): Replace {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Registers filters for automatic WebP replacement in content.
	 *
	 * This method hooks into 'the_content', 'post_thumbnail_html', and 'widget_text'
	 * to replace <img> tags with <picture> elements containing WebP sources.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init(): void {
		add_filter( 'the_content', [ static::class, 'replace_webp' ] );
		add_filter( 'post_thumbnail_html', [ static::class, 'replace_webp' ] );
		add_filter( 'widget_text', [ static::class, 'replace_webp' ] );
	}

	/**
	 * Replaces image tags in the content with their WebP equivalents.
	 *
	 * This method uses a regular expression to find <img> tags and replaces them
	 * with a <picture> element that includes a WebP source if available.
	 *
	 * @since 1.0.0
	 * @param string $content The content to process.
	 * @return string The modified content with WebP replacements.
	 */
	public static function replace_webp( string $content ): string {
		return preg_replace_callback(
			'/<img\s+[^>]*src=["\']([^"\']+)["\'][^>]*>/i',
			[ Replacer::class, 'prepare' ],
			$content
		);
	}
}
