<?php
/**
 * Tests for browser WebP support detection
 *
 * @package ConvertToWebpLite\Tests
 */

namespace ConvertToWebpLite\Tests\Unit\Utils;

use ConvertToWebpLite\Tests\TestCase;
use ConvertToWebpLite\Utils\Helpers;
use Brain\Monkey\Functions as BrainMonkey;
use RuntimeException;
use ReflectionClass;

/**
 * Class HelpersTest
 *
 * Tests for Helpers class utility methods (excluding browser detection).
 */
class HelpersTest extends TestCase {

	/**
	 * Initializes the test environment before each test method.
	 *
	 * Sets up the parent test case environment and mocks common WordPress
	 * functions used by the Helpers class.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function set_up(): void {
		parent::set_up();

		// Mock common WordPress functions
		BrainMonkey\when( 'esc_attr' )->returnArg();
		BrainMonkey\when( 'trailingslashit' )->alias(
			function ( $string ) {
				return rtrim( $string, '/\\' ) . '/';
			}
		);
	}

	/**
	 * Test that constructor is private (singleton pattern).
	 *
	 * Verifies that the __construct() method is private to prevent direct
	 * instantiation of the Helpers class.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::__construct
	 * @return void
	 */
	public function test_constructor_is_private(): void {
		$this->assertMethodIsPrivate( Helpers::class, '__construct' );
	}

	/**
	 * Test that clone is private (singleton pattern).
	 *
	 * Verifies that the __clone() method is private to prevent cloning
	 * of the singleton instance.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::__clone
	 * @return void
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( Helpers::class, '__clone' );
	}

	/**
	 * Test that __wakeup throws RuntimeException (singleton pattern).
	 *
	 * Verifies that attempting to unserialize the singleton instance throws a
	 * RuntimeException to prevent unserialization.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::__wakeup
	 * @return void
	 */
	public function test_wakeup_throws_exception(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot unserialize a singleton.' );

		$reflection = new ReflectionClass( Helpers::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Test get_message returns success message with correct classes.
	 *
	 * Verifies that get_message() returns an array with message and classes keys,
	 * and includes the 'success' class when status is true.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_message
	 * @return void
	 */
	public function test_get_message_returns_success_message(): void {
		$result = Helpers::get_message( true, 'Success message' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'message', $result );
		$this->assertArrayHasKey( 'classes', $result );
		$this->assertEquals( 'Success message', $result['message'] );
		$this->assertContains( 'success', $result['classes'] );
	}

	/**
	 * Test get_message returns error message with correct classes.
	 *
	 * Verifies that get_message() includes the 'error' class when status is false.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_message
	 * @return void
	 */
	public function test_get_message_returns_error_message(): void {
		$result = Helpers::get_message( false, 'Error message' );

		$this->assertEquals( 'Error message', $result['message'] );
		$this->assertContains( 'error', $result['classes'] );
	}

	/**
	 * Test get_message adds context class.
	 *
	 * Verifies that get_message() adds the context parameter as an additional class
	 * (e.g., 'delete', 'convert').
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_message
	 * @return void
	 */
	public function test_get_message_adds_context_class(): void {
		$result = Helpers::get_message( true, 'Test', 'delete' );

		$this->assertContains( 'success', $result['classes'] );
		$this->assertContains( 'delete', $result['classes'] );
	}

	/**
	 * Test get_message adds size class.
	 *
	 * Verifies that get_message() adds the size parameter as an additional class
	 * (e.g., 'thumbnail', 'medium').
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_message
	 * @return void
	 */
	public function test_get_message_adds_size_class(): void {
		$result = Helpers::get_message( true, 'Test', 'convert', 'thumbnail' );

		$this->assertContains( 'success', $result['classes'] );
		$this->assertContains( 'convert', $result['classes'] );
		$this->assertContains( 'thumbnail', $result['classes'] );
	}

	/**
	 * Test get_message preserves additional classes.
	 *
	 * Verifies that get_message() preserves custom classes passed in the
	 * additional_classes parameter.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_message
	 * @return void
	 */
	public function test_get_message_preserves_additional_classes(): void {
		$result = Helpers::get_message( true, 'Test', '', '', [ 'custom-class', 'another-class' ] );

		$this->assertContains( 'success', $result['classes'] );
		$this->assertContains( 'custom-class', $result['classes'] );
		$this->assertContains( 'another-class', $result['classes'] );
	}

	/**
	 * Test get_message ignores empty size.
	 *
	 * Verifies that get_message() does not add a class when the size parameter
	 * is an empty string.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_message
	 * @return void
	 */
	public function test_get_message_ignores_empty_size(): void {
		$result = Helpers::get_message( true, 'Test', 'convert', '' );

		$this->assertContains( 'success', $result['classes'] );
		$this->assertContains( 'convert', $result['classes'] );
		$this->assertCount( 2, $result['classes'] );
	}

	/**
	 * Test get_basedir returns upload directory with trailing slash.
	 *
	 * Verifies that get_basedir() returns the WordPress upload directory
	 * with a trailing slash added by trailingslashit().
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_basedir
	 * @return void
	 */
	public function test_get_basedir_returns_upload_dir(): void {
		BrainMonkey\expect( 'wp_upload_dir' )
			->once()
			->andReturn( [ 'basedir' => '/var/www/uploads' ] );

		$result = Helpers::get_basedir();

		$this->assertEquals( '/var/www/uploads/', $result );
	}

	/**
	 * Test get_attachments returns array of attachment IDs.
	 *
	 * Verifies that get_attachments() returns an array of attachment IDs
	 * from WordPress get_posts().
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_attachments
	 * @return void
	 */
	public function test_get_attachments_returns_attachment_ids(): void {
		BrainMonkey\expect( 'get_posts' )
			->once()
			->with( \Mockery::type( 'array' ) )
			->andReturn( [ 1, 2, 3, 4, 5 ] );

		$result = Helpers::get_attachments();

		$this->assertIsArray( $result );
		$this->assertEquals( [ 1, 2, 3, 4, 5 ], $result );
	}

	/**
	 * Test get_attachments returns empty array when no attachments found.
	 *
	 * Verifies that get_attachments() returns an empty array when
	 * WordPress get_posts() finds no attachments.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_attachments
	 * @return void
	 */
	public function test_get_attachments_returns_empty_array_when_none_found(): void {
		BrainMonkey\expect( 'get_posts' )
			->once()
			->andReturn( [] );

		$result = Helpers::get_attachments();

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test get_attachment_id_from_url returns false for empty URL.
	 *
	 * Verifies that get_attachment_id_from_url() returns false when
	 * provided with an empty URL string.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_attachment_id_from_url
	 * @return void
	 */
	public function test_get_attachment_id_from_url_returns_false_for_empty_url(): void {
		$result = Helpers::get_attachment_id_from_url( '' );

		$this->assertFalse( $result );
	}

	/**
	 * Test get_attachment_id_from_url returns cached result.
	 *
	 * Verifies that get_attachment_id_from_url() returns the cached attachment ID
	 * when wp_cache_get() finds a cached value.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_attachment_id_from_url
	 * @return void
	 */
	public function test_get_attachment_id_from_url_returns_cached_result(): void {
		$url = 'https://example.com/wp-content/uploads/image.jpg';

		BrainMonkey\expect( 'wp_cache_get' )
			->once()
			->with( 'convert_to_webp_lite_attachment_id_' . md5( $url ), 'convert_to_webp_lite' )
			->andReturn( 123 );

		$result = Helpers::get_attachment_id_from_url( $url );

		$this->assertEquals( 123, $result );
	}

	/**
	 * Test get_attachment_id_from_url uses attachment_url_to_postid.
	 *
	 * Verifies that get_attachment_id_from_url() uses attachment_url_to_postid()
	 * when no cached result exists and caches the result with wp_cache_set().
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_attachment_id_from_url
	 * @return void
	 */
	public function test_get_attachment_id_from_url_uses_attachment_url_to_postid(): void {
		$url = 'https://example.com/wp-content/uploads/image.jpg';

		BrainMonkey\expect( 'wp_cache_get' )
			->once()
			->andReturn( false );

		BrainMonkey\expect( 'attachment_url_to_postid' )
			->once()
			->with( $url )
			->andReturn( 456 );

		BrainMonkey\expect( 'wp_cache_set' )
			->once()
			->with( 'convert_to_webp_lite_attachment_id_' . md5( $url ), 456, 'convert_to_webp_lite', 3600 );

		$result = Helpers::get_attachment_id_from_url( $url );

		$this->assertEquals( 456, $result );
	}

	/**
	 * Test clear_attachment_cache clears specific URL cache.
	 *
	 * Verifies that clear_attachment_cache() clears the cache for a specific URL
	 * using wp_cache_delete() when a URL is provided.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::clear_attachment_cache
	 * @return void
	 */
	public function test_clear_attachment_cache_clears_specific_url(): void {
		$url = 'https://example.com/wp-content/uploads/image.jpg';

		BrainMonkey\expect( 'wp_cache_delete' )
			->twice()
			->andReturnUsing(
				function ( $key, $group ) {
					return true;
				}
			);

		Helpers::clear_attachment_cache( $url );

		// The expectations above will be verified by Mockery
		$this->assertTrue( true );
	}

	/**
	 * Test clear_attachment_cache flushes group when no URL provided.
	 *
	 * Verifies that clear_attachment_cache() flushes the entire cache group
	 * using wp_cache_flush_group() when no URL is provided.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::clear_attachment_cache
	 * @return void
	 */
	public function test_clear_attachment_cache_flushes_group_when_no_url(): void {
		BrainMonkey\expect( 'wp_cache_flush_group' )
			->once()
			->with( 'convert_to_webp_lite' )
			->andReturn( true );

		Helpers::clear_attachment_cache();

		// The expectations above will be verified by Mockery
		$this->assertTrue( true );
	}

	/**
	 * Test parse_srcset returns empty array for empty string.
	 *
	 * Verifies that parse_srcset() returns an empty array when provided
	 * with an empty string.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::parse_srcset
	 * @return void
	 */
	public function test_parse_srcset_returns_empty_array_for_empty_string(): void {
		$result = Helpers::parse_srcset( '' );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test parse_srcset parses single image.
	 *
	 * Verifies that parse_srcset() correctly parses a srcset string containing
	 * a single image with URL and width descriptor.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::parse_srcset
	 * @return void
	 */
	public function test_parse_srcset_parses_single_image(): void {
		$srcset = 'https://example.com/image-300x200.jpg 300w';
		$result = Helpers::parse_srcset( $srcset );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertEquals( 'https://example.com/image-300x200.jpg', $result[0]['url'] );
		$this->assertEquals( 300, $result[0]['width'] );
	}

	/**
	 * Test parse_srcset parses multiple images.
	 *
	 * Verifies that parse_srcset() correctly parses a srcset string containing
	 * multiple images with their respective width descriptors.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::parse_srcset
	 * @return void
	 */
	public function test_parse_srcset_parses_multiple_images(): void {
		$srcset = 'https://example.com/image-300x200.jpg 300w, https://example.com/image-600x400.jpg 600w, https://example.com/image-1024x768.jpg 1024w';
		$result = Helpers::parse_srcset( $srcset );

		$this->assertCount( 3, $result );
		$this->assertEquals( 300, $result[0]['width'] );
		$this->assertEquals( 600, $result[1]['width'] );
		$this->assertEquals( 1024, $result[2]['width'] );
	}

	/**
	 * Test parse_srcset sorts by width.
	 *
	 * Verifies that parse_srcset() sorts the parsed images by width
	 * in ascending order.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::parse_srcset
	 * @return void
	 */
	public function test_parse_srcset_sorts_by_width(): void {
		$srcset = 'https://example.com/image-1024x768.jpg 1024w, https://example.com/image-300x200.jpg 300w, https://example.com/image-600x400.jpg 600w';
		$result = Helpers::parse_srcset( $srcset );

		$this->assertEquals( 300, $result[0]['width'] );
		$this->assertEquals( 600, $result[1]['width'] );
		$this->assertEquals( 1024, $result[2]['width'] );
	}

	/**
	 * Test get_srcset returns empty string for empty input.
	 *
	 * Verifies that get_srcset() returns an empty string when provided
	 * with an empty input string.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_srcset
	 * @return void
	 */
	public function test_get_srcset_returns_empty_string_for_empty_input(): void {
		$result = Helpers::get_srcset( '' );

		$this->assertEquals( '', $result );
	}

	/**
	 * Test get_srcset rebuilds srcset string.
	 *
	 * Verifies that get_srcset() rebuilds a srcset string from parsed data,
	 * maintaining URLs and width descriptors.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_srcset
	 * @return void
	 */
	public function test_get_srcset_rebuilds_srcset_string(): void {
		$srcset = 'https://example.com/image-300x200.jpg 300w, https://example.com/image-600x400.jpg 600w';
		$result = Helpers::get_srcset( $srcset );

		$this->assertStringContainsString( '300w', $result );
		$this->assertStringContainsString( '600w', $result );
		$this->assertStringContainsString( 'image-300x200.jpg', $result );
		$this->assertStringContainsString( 'image-600x400.jpg', $result );
	}

	/**
	 * Test get_srcset sorts images by width.
	 *
	 * Verifies that get_srcset() returns images sorted by width in ascending order,
	 * with smaller widths appearing before larger ones.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_srcset
	 * @return void
	 */
	public function test_get_srcset_sorts_images_by_width(): void {
		$srcset = 'https://example.com/image-1024x768.jpg 1024w, https://example.com/image-300x200.jpg 300w';
		$result = Helpers::get_srcset( $srcset );

		// The 300w should appear before 1024w in the result
		$pos_300  = strpos( $result, '300w' );
		$pos_1024 = strpos( $result, '1024w' );

		$this->assertLessThan( $pos_1024, $pos_300 );
	}

	/**
	 * Test attachment_is_webp returns false for invalid attachment ID.
	 *
	 * Verifies that attachment_is_webp() returns false when the file path
	 * cannot be resolved to a valid attachment ID.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::attachment_is_webp
	 * @return void
	 */
	public function test_attachment_is_webp_returns_false_for_invalid_id(): void {
		BrainMonkey\expect( 'wp_upload_dir' )
			->twice()
			->andReturn(
				[
					'basedir' => '/var/www/uploads',
					'baseurl' => 'https://example.com/uploads',
				]
			);

		BrainMonkey\expect( 'attachment_url_to_postid' )
			->once()
			->andReturn( 0 );

		$result = Helpers::attachment_is_webp( '/var/www/uploads/image.jpg' );

		$this->assertFalse( $result );
	}

	/**
	 * Test attachment_is_webp returns true for webp file.
	 *
	 * Verifies that attachment_is_webp() returns true when the attachment
	 * file has a .webp extension.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::attachment_is_webp
	 * @return void
	 */
	public function test_attachment_is_webp_returns_true_for_webp_file(): void {
		BrainMonkey\expect( 'wp_upload_dir' )
			->twice()
			->andReturn(
				[
					'basedir' => '/var/www/uploads',
					'baseurl' => 'https://example.com/uploads',
				]
			);

		BrainMonkey\expect( 'attachment_url_to_postid' )
			->once()
			->andReturn( 123 );

		BrainMonkey\expect( 'get_attached_file' )
			->once()
			->with( 123 )
			->andReturn( '/var/www/uploads/image.webp' );

		$result = Helpers::attachment_is_webp( '/var/www/uploads/image.webp' );

		$this->assertTrue( $result );
	}

	/**
	 * Test attachment_is_webp returns false for non-webp file.
	 *
	 * Verifies that attachment_is_webp() returns false when the attachment
	 * file has a non-webp extension (e.g., .jpg, .png).
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::attachment_is_webp
	 * @return void
	 */
	public function test_attachment_is_webp_returns_false_for_non_webp_file(): void {
		BrainMonkey\expect( 'wp_upload_dir' )
			->twice()
			->andReturn(
				[
					'basedir' => '/var/www/uploads',
					'baseurl' => 'https://example.com/uploads',
				]
			);

		BrainMonkey\expect( 'attachment_url_to_postid' )
			->once()
			->andReturn( 123 );

		BrainMonkey\expect( 'get_attached_file' )
			->once()
			->with( 123 )
			->andReturn( '/var/www/uploads/image.jpg' );

		$result = Helpers::attachment_is_webp( '/var/www/uploads/image.jpg' );

		$this->assertFalse( $result );
	}

	/**
	 * Performs cleanup operations after each test method completes.
	 *
	 * Tears down the test environment by calling the parent tear_down method
	 * to clean up WordPress hooks and function mocks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function tear_down(): void {
		parent::tear_down();
	}
}
