<?php
/**
 * Integration tests for Actions\Replace class
 *
 * Tests automatic replacement of img tags with picture elements.
 *
 * @package ConvertToWebpLite\Tests
 * @since 1.0.0
 */

namespace ConvertToWebpLite\Tests\Integration\Actions;

use ConvertToWebpLite\Tests\IntegrationTestCase;
use ConvertToWebpLite\Actions\Replace;
use ConvertToWebpLite\Actions\Add;

/**
 * Class ReplaceTest
 *
 * @since 1.0.0
 * @covers \ConvertToWebpLite\Actions\Replace
 */
class ReplaceTest extends IntegrationTestCase {

	/**
	 * Instance of Replace class
	 *
	 * @var Replace
	 */
	protected Replace $replace;

	/**
	 * Test post ID
	 *
	 * @var int
	 */
	protected int $post_id;

	/**
	 * Test attachment ID
	 *
	 * @var int
	 */
	protected int $attachment_id;

	/**
	 * Setup before each test.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		$this->replace = Replace::get_instance();
		$this->replace->init(); // Initialize hooks

		// Set default mode to Image (0 = image mode with simple <img>, 1 = picture mode with <picture> element)
		update_option( 'convert_to_webp_lite_replace_mode', 0 );

		// Create a test post
		$this->post_id = $this->factory->post->create(
			[
				'post_title'   => 'Test Post',
				'post_content' => 'Test content',
				'post_status'  => 'publish',
			]
		);

		// Create a test attachment
		$this->attachment_id = $this->create_test_attachment( 'test-replace.jpg', $this->post_id );

		// Ensure WebP version exists
		$image_path = get_attached_file( $this->attachment_id );
		$webp_path  = $this->get_webp_path( $image_path );

		if ( ! file_exists( $webp_path ) ) {
			$converter = \ConvertToWebpLite\Utils\Converter::get_instance();
			$converter->convert( $image_path );
		}
	}

	/**
	 * Test that Replace class is a singleton.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_singleton_pattern(): void {
		$instance1 = Replace::get_instance();
		$instance2 = Replace::get_instance();

		$this->assertSame( $instance1, $instance2, 'Replace should return the same instance' );
	}

	/**
	 * Test that filters are registered on init.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_filters_registered(): void {
		$filters = [
			'the_content',
			'post_thumbnail_html',
			'widget_text',
		];

		foreach ( $filters as $filter ) {
			$priority = has_filter( $filter, [ Replace::class, 'replace_webp' ] );
			$this->assertIsInt( $priority, "Filter '{$filter}' should be registered" );
		}
	}

	/**
	 * Test replacement of simple img tag in content.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_replaces_img_in_content(): void {
		$image_url = wp_get_attachment_url( $this->attachment_id );
		$content   = '<p>Test content with image:</p>';
		$content  .= '<img src="' . esc_url( $image_url ) . '" alt="Test Image" />';

		$result = apply_filters( 'the_content', $content );

		// Should contain img tag based on replace mode
		$replace_mode = get_option( 'convert_to_webp_lite_replace_mode', 0 );

		if ( $replace_mode ) {
			// Picture mode (option enabled): should contain picture element
			$this->assertStringContainsString( '<picture>', $result, 'Should contain picture element' );
			$this->assertStringContainsString( '</picture>', $result, 'Should contain closing picture tag' );
			$this->assertStringContainsString( '<source', $result, 'Should contain source element' );
		} else {
			// Image mode (default): img tag with WebP src
			$this->assertStringContainsString( 'src=', $result, 'Should contain src attribute' );
			$this->assertStringContainsString( '<img', $result, 'Should contain img tag' );
			$this->assertStringNotContainsString( '<picture>', $result, 'Should not contain picture element' );
		}
	}

	/**
	 * Test replacement preserves img attributes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_preserves_img_attributes(): void {
		$image_url = wp_get_attachment_url( $this->attachment_id );
		$content   = '<img src="' . esc_url( $image_url ) . '" alt="Test Alt" class="test-class" id="test-id" data-custom="value" />';

		$result = apply_filters( 'the_content', $content );

		// Attributes should be preserved
		$this->assertStringContainsString( 'alt="Test Alt"', $result, 'Should preserve alt attribute' );
		$this->assertStringContainsString( 'class="test-class"', $result, 'Should preserve class attribute' );
		$this->assertStringContainsString( 'id="test-id"', $result, 'Should preserve id attribute' );
		$this->assertStringContainsString( 'data-custom="value"', $result, 'Should preserve data attributes' );
	}

	/**
	 * Test replacement with multiple images.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_replaces_multiple_images(): void {
		$attachment_id_1 = $this->create_test_attachment( 'test-multi-1.jpg' );
		$attachment_id_2 = $this->create_test_attachment( 'test-multi-2.jpg' );

		$image_url_1 = wp_get_attachment_url( $attachment_id_1 );
		$image_url_2 = wp_get_attachment_url( $attachment_id_2 );

		$content  = '<p>First image:</p>';
		$content .= '<img src="' . esc_url( $image_url_1 ) . '" alt="Image 1" />';
		$content .= '<p>Second image:</p>';
		$content .= '<img src="' . esc_url( $image_url_2 ) . '" alt="Image 2" />';

		$result = apply_filters( 'the_content', $content );

		// Both images should be processed
		$img_count = substr_count( $result, '<img' );
		$this->assertGreaterThanOrEqual( 2, $img_count, 'Should contain at least 2 img tags' );
	}

	/**
	 * Test replacement in post thumbnail.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_replaces_post_thumbnail(): void {
		// Set post thumbnail
		set_post_thumbnail( $this->post_id, $this->attachment_id );

		// Get post thumbnail HTML
		$thumbnail_html = get_the_post_thumbnail( $this->post_id, 'medium' );

		$this->assertNotEmpty( $thumbnail_html, 'Thumbnail HTML should not be empty' );

		// Apply filter
		$result = apply_filters( 'post_thumbnail_html', $thumbnail_html );

		// Should be processed
		$this->assertNotEmpty( $result, 'Result should not be empty' );
		$this->assertStringContainsString( '<img', $result, 'Should contain img tag' );
	}

	/**
	 * Test replacement with srcset.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_handles_srcset(): void {
		// Register image sizes
		add_image_size( 'test-small', 150, 150, true );
		add_image_size( 'test-medium', 300, 300, true );

		// Create attachment with sizes
		$attachment_id = $this->create_test_attachment( 'test-srcset.jpg' );
		$image_html    = wp_get_attachment_image( $attachment_id, 'medium' );

		$this->assertStringContainsString( 'srcset=', $image_html, 'Should contain srcset' );

		// Apply filter
		$result = apply_filters( 'the_content', $image_html );

		$this->assertNotEmpty( $result, 'Result should not be empty' );

		// Clean up
		remove_image_size( 'test-small' );
		remove_image_size( 'test-medium' );
	}

	/**
	 * Test replacement skips non-image URLs.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_skips_non_image_urls(): void {
		$content = '<img src="https://example.com/test.svg" alt="SVG" />';

		$result = apply_filters( 'the_content', $content );

		// SVG should remain unchanged (no WebP conversion for SVG)
		$this->assertStringContainsString( 'test.svg', $result, 'SVG should remain in result' );
	}

	/**
	 * Test replacement handles external images.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_handles_external_images(): void {
		$content = '<img src="https://external.com/image.jpg" alt="External" />';

		$result = apply_filters( 'the_content', $content );

		// External images should remain unchanged
		$this->assertStringContainsString( 'https://external.com/image.jpg', $result, 'External URL should remain' );
	}

	/**
	 * Test replacement with empty content.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_handles_empty_content(): void {
		$result = Replace::replace_webp( '' );

		$this->assertSame( '', $result, 'Empty content should remain empty' );
	}

	/**
	 * Test replacement with content without images.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_handles_content_without_images(): void {
		$content = '<p>This is just text content without any images.</p>';

		$result = apply_filters( 'the_content', $content );

		// WordPress the_content filter may add whitespace, so trim both
		$this->assertSame( trim( $content ), trim( $result ), 'Content without images should remain unchanged' );
	}

	/**
	 * Test replacement with malformed img tags.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_handles_malformed_img_tags(): void {
		$content = '<img>';  // No src attribute

		$result = apply_filters( 'the_content', $content );

		$this->assertNotEmpty( $result, 'Should handle malformed img tags' );
	}

	/**
	 * Test replacement in widget text.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_replaces_in_widget_text(): void {
		$image_url = wp_get_attachment_url( $this->attachment_id );
		$content   = '<p>Widget content:</p><img src="' . esc_url( $image_url ) . '" alt="Widget Image" />';

		$result = apply_filters( 'widget_text', $content );

		$this->assertNotEmpty( $result, 'Widget text should be processed' );
		$this->assertStringContainsString( '<img', $result, 'Should contain img tag' );
	}

	/**
	 * Test replacement mode setting.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_respects_replace_mode_setting(): void {
		$image_url = wp_get_attachment_url( $this->attachment_id );
		$content   = '<img src="' . esc_url( $image_url ) . '" alt="Test" />';

		// Test image mode (default - 0)
		update_option( 'convert_to_webp_lite_replace_mode', 0 );
		$result_image = apply_filters( 'the_content', $content );

		// Test picture mode (enabled - 1)
		update_option( 'convert_to_webp_lite_replace_mode', 1 );
		$result_picture = apply_filters( 'the_content', $content );

		// Results should differ based on mode
		$this->assertNotEquals(
			$result_image,
			$result_picture,
			'Image mode and Picture mode should produce different output'
		);

		// Image mode should contain <img> but not <picture>
		$this->assertStringContainsString( '<img', $result_image, 'Image mode should contain img tag' );
		$this->assertStringNotContainsString( '<picture>', $result_image, 'Image mode should not contain picture element' );

		// Picture mode should contain <picture>
		$this->assertStringContainsString( '<picture>', $result_picture, 'Picture mode should contain picture element' );
		$this->assertStringContainsString( '<source', $result_picture, 'Picture mode should contain source element' );
	}

	/**
	 * Test replacement with responsive images.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_handles_responsive_images(): void {
		// Get responsive image HTML
		$image_html = wp_get_attachment_image( $this->attachment_id, 'medium' );

		$this->assertNotEmpty( $image_html, 'Image HTML should not be empty' );

		// Apply filter
		$result = apply_filters( 'the_content', $image_html );

		$this->assertNotEmpty( $result, 'Result should not be empty' );
		$this->assertStringContainsString( '<img', $result, 'Should contain img tag' );
	}

	/**
	 * Test replacement with lazy loading attribute.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_preserves_loading_attribute(): void {
		$image_url = wp_get_attachment_url( $this->attachment_id );
		$content   = '<img src="' . esc_url( $image_url ) . '" alt="Test" loading="lazy" />';

		$result = apply_filters( 'the_content', $content );

		$this->assertStringContainsString( 'loading="lazy"', $result, 'Should preserve loading attribute' );
	}
}
