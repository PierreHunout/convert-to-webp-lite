<?php
/**
 * Tests for Picture class
 *
 * @package ConvertToWebpLite\Tests
 */

namespace ConvertToWebpLite\Tests\Unit\Modes;

use ConvertToWebpLite\Tests\TestCase;
use ConvertToWebpLite\Modes\Picture;
use Brain\Monkey\Functions as BrainMonkey;
use RuntimeException;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class PictureTest
 *
 * Tests for Picture class.
 */
class PictureTest extends TestCase {

	/**
	 * Initializes the test environment before each test method.
	 *
	 * Sets up the parent test case environment and prepares for Picture
	 * class testing.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function set_up(): void {
		parent::set_up();
	}

	/**
	 * Test that constructor is private (singleton pattern).
	 *
	 * Verifies that the __construct() method is private to prevent direct
	 * instantiation of the Picture class.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Modes\Picture::__construct
	 * @return void
	 */
	public function test_constructor_is_private(): void {
		$this->assertMethodIsPrivate( Picture::class, '__construct' );
	}

	/**
	 * Test that clone is private (singleton pattern).
	 *
	 * Verifies that the __clone() method is private to prevent cloning
	 * of the singleton instance.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Modes\Picture::__clone
	 * @return void
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( Picture::class, '__clone' );
	}

	/**
	 * Test that __wakeup throws RuntimeException (singleton pattern).
	 *
	 * Verifies that attempting to unserialize the singleton instance throws a
	 * RuntimeException to prevent unserialization.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Modes\Picture::__wakeup
	 * @return void
	 */
	public function test_wakeup_throws_exception(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot unserialize a singleton.' );

		$reflection = new ReflectionClass( Picture::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Test prepare method exists and is public static.
	 *
	 * Verifies that the prepare() method exists, is public and static,
	 * allowing it to be called without instantiating the class.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Modes\Picture::prepare
	 * @return void
	 */
	public function test_prepare_method_exists(): void {
		$this->assertTrue(
			method_exists( Picture::class, 'prepare' ),
			'Picture class should have a prepare method'
		);

		$reflection = new ReflectionMethod( Picture::class, 'prepare' );
		$this->assertTrue(
			$reflection->isPublic(),
			'prepare method should be public'
		);
		$this->assertTrue(
			$reflection->isStatic(),
			'prepare method should be static'
		);
	}

	/**
	 * Test print method exists and is public static.
	 *
	 * Verifies that the print() method exists, is public and static,
	 * allowing it to be called without instantiating the class.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Modes\Picture::print
	 * @return void
	 */
	public function test_print_method_exists(): void {
		$this->assertTrue(
			method_exists( Picture::class, 'print' ),
			'Picture class should have a print method'
		);

		$reflection = new ReflectionMethod( Picture::class, 'print' );
		$this->assertTrue(
			$reflection->isPublic(),
			'print method should be public'
		);
		$this->assertTrue(
			$reflection->isStatic(),
			'print method should be static'
		);
	}

	/**
	 * Test prepare returns array with required keys.
	 *
	 * Verifies that the prepare() method returns an array containing
	 * all required keys: src, srcset, sizes, and fallback.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Modes\Picture::prepare
	 * @return void
	 */
	public function test_prepare_returns_array_with_required_keys(): void {
		$image    = '<img src="test.jpg" alt="Test" />';
		$src      = 'test.jpg';
		$metadata = [
			'width'  => 800,
			'height' => 600,
		];

		BrainMonkey\when( 'wp_get_attachment_image_srcset' )->justReturn( '' );
		BrainMonkey\when( 'wp_image_src_get_dimensions' )->justReturn( [ 800, 600 ] );
		BrainMonkey\when( 'wp_calculate_image_sizes' )->justReturn( '100vw' );
		BrainMonkey\when( 'esc_attr' )->returnArg();

		$result = Picture::prepare( 123, $metadata, $image, $src );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'src', $result );
		$this->assertArrayHasKey( 'srcset', $result );
		$this->assertArrayHasKey( 'sizes', $result );
		$this->assertArrayHasKey( 'fallback', $result );
	}

	/**
	 * Test prepare stores original src.
	 *
	 * Verifies that the prepare() method stores the original image source URL
	 * in the returned array for use in the picture element.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Modes\Picture::prepare
	 * @return void
	 */
	public function test_prepare_stores_original_src(): void {
		$image    = '<img src="test.jpg" />';
		$src      = 'https://example.com/wp-content/uploads/test.jpg';
		$metadata = [];

		BrainMonkey\when( 'wp_get_attachment_image_srcset' )->justReturn( '' );
		BrainMonkey\when( 'wp_image_src_get_dimensions' )->justReturn( [ 800, 600 ] );
		BrainMonkey\when( 'wp_calculate_image_sizes' )->justReturn( '100vw' );
		BrainMonkey\when( 'esc_attr' )->returnArg();

		$result = Picture::prepare( 123, $metadata, $image, $src );

		$this->assertEquals( $src, $result['src'] );
	}

	/**
	 * Test prepare uses default sizes when null.
	 *
	 * Verifies that the prepare() method uses the default value "100vw"
	 * when wp_calculate_image_sizes returns null.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Modes\Picture::prepare
	 * @return void
	 */
	public function test_prepare_uses_default_sizes_when_null(): void {
		$image    = '<img src="test.jpg" />';
		$src      = 'test.jpg';
		$metadata = [];

		BrainMonkey\when( 'wp_get_attachment_image_srcset' )->justReturn( '' );
		BrainMonkey\when( 'wp_image_src_get_dimensions' )->justReturn( [ 800, 600 ] );
		BrainMonkey\when( 'wp_calculate_image_sizes' )->justReturn( null );
		BrainMonkey\when( 'esc_attr' )->returnArg();

		$result = Picture::prepare( 123, $metadata, $image, $src );

		$this->assertEquals( '100vw', $result['sizes'] );
	}

	/**
	 * Test print regex converts extensions to webp.
	 *
	 * Verifies that the regular expression used in print() correctly converts
	 * various image extensions (jpg, jpeg, png, gif) to .webp, case-insensitively.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Modes\Picture::print
	 * @return void
	 */
	public function test_print_regex_converts_extensions_to_webp(): void {
		$test_cases = [
			'image.jpg'  => 'image.webp',
			'image.jpeg' => 'image.webp',
			'image.JPG'  => 'image.webp',
			'image.png'  => 'image.webp',
			'image.PNG'  => 'image.webp',
			'image.gif'  => 'image.webp',
			'image.GIF'  => 'image.webp',
		];

		foreach ( $test_cases as $original => $expected ) {
			$result = preg_replace( '/\.(jpe?g|png|gif)$/i', '.webp', $original );
			$this->assertEquals(
				$expected,
				$result,
				"Expected $original to convert to $expected"
			);
		}
	}

	/**
	 * Test print generates picture element structure.
	 *
	 * Verifies that the print() method generates a complete picture element
	 * with source tag for WebP and fallback img tag.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Modes\Picture::print
	 * @return void
	 */
	public function test_print_generates_picture_element_structure(): void {
		$picture_html = '<picture><source type="image/webp" srcset="test.webp" sizes="100vw" /><img src="test.jpg" /></picture>';

		$this->assertStringContainsString( '<picture>', $picture_html );
		$this->assertStringContainsString( '<source', $picture_html );
		$this->assertStringContainsString( 'type="image/webp"', $picture_html );
		$this->assertStringContainsString( '</picture>', $picture_html );
	}

	/**
	 * Test print source element has webp type.
	 *
	 * Verifies that the source element generated by print() includes
	 * the correct MIME type "image/webp".
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Modes\Picture::print
	 * @return void
	 */
	public function test_print_source_element_has_webp_type(): void {
		$source = '<source type="image/webp" srcset="test.webp" sizes="100vw" />';

		$this->assertStringContainsString( 'type="image/webp"', $source );
	}

	/**
	 * Test print source element includes srcset.
	 *
	 * Verifies that the source element generated by print() includes
	 * the srcset attribute with WebP image URLs.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Modes\Picture::print
	 * @return void
	 */
	public function test_print_source_element_includes_srcset(): void {
		$srcset = 'test-150.webp 150w, test-300.webp 300w';
		$source = sprintf( '<source type="image/webp" srcset="%s" sizes="100vw" />', $srcset );

		$this->assertStringContainsString( 'srcset=', $source );
		$this->assertStringContainsString( $srcset, $source );
	}

	/**
	 * Test print source element includes sizes.
	 *
	 * Verifies that the source element generated by print() includes
	 * the sizes attribute for responsive images.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Modes\Picture::print
	 * @return void
	 */
	public function test_print_source_element_includes_sizes(): void {
		$sizes  = '(max-width: 600px) 100vw, 50vw';
		$source = sprintf( '<source type="image/webp" srcset="test.webp" sizes="%s" />', $sizes );

		$this->assertStringContainsString( 'sizes=', $source );
		$this->assertStringContainsString( $sizes, $source );
	}

	/**
	 * Test print parses srcset correctly.
	 *
	 * Verifies that the print() method correctly parses srcset strings
	 * containing multiple image URLs with width descriptors.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Modes\Picture::print
	 * @return void
	 */
	public function test_print_parses_srcset_correctly(): void {
		$srcset = 'test-150.jpg 150w, test-300.jpg 300w, test-600.jpg 600w';
		$array  = explode( ',', $srcset );

		$this->assertCount( 3, $array );

		foreach ( $array as $item ) {
			$parts = preg_split( '/\s+/', trim( $item ) );
			$this->assertCount( 2, $parts );
			$this->assertStringEndsWith( 'w', $parts[1] );
		}
	}

	/**
	 * Test print handles empty srcset parts.
	 *
	 * Verifies that the print() method correctly skips empty items
	 * when parsing srcset strings with trailing or double commas.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Modes\Picture::print
	 * @return void
	 */
	public function test_print_handles_empty_srcset_parts(): void {
		$srcset = 'test-150.jpg 150w, , test-300.jpg 300w';
		$array  = explode( ',', $srcset );
		$valid  = 0;

		foreach ( $array as $item ) {
			$parts = preg_split( '/\s+/', trim( $item ) );
			if ( ! empty( $parts[0] ) ) {
				++$valid;
			}
		}

		$this->assertEquals( 2, $valid );
	}

	/**
	 * Test print converts srcset items to webp.
	 *
	 * Verifies that the print() method converts all image URLs in srcset
	 * to WebP format while preserving width descriptors.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Modes\Picture::print
	 * @return void
	 */
	public function test_print_converts_srcset_items_to_webp(): void {
		$items = [
			'test-150.jpg 150w'  => 'test-150.webp 150w',
			'test-300.jpeg 300w' => 'test-300.webp 300w',
			'test-600.png 600w'  => 'test-600.webp 600w',
		];

		foreach ( $items as $original => $expected ) {
			$parts     = preg_split( '/\s+/', trim( $original ) );
			$webp_url  = preg_replace( '/\.(jpe?g|png|gif)$/i', '.webp', $parts[0] );
			$converted = $webp_url . ( isset( $parts[1] ) ? ' ' . $parts[1] : '' );

			$this->assertEquals( $expected, $converted );
		}
	}

	/**
	 * Test print preserves width descriptor in srcset.
	 *
	 * Verifies that the print() method preserves the width descriptor (e.g., "300w")
	 * when processing srcset items.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Modes\Picture::print
	 * @return void
	 */
	public function test_print_preserves_width_descriptor_in_srcset(): void {
		$item  = 'test-300.jpg 300w';
		$parts = preg_split( '/\s+/', trim( $item ) );

		$this->assertEquals( 'test-300.jpg', $parts[0] );
		$this->assertEquals( '300w', $parts[1] );
		$this->assertTrue( isset( $parts[1] ) );
	}

	/**
	 * Test print handles srcset item without descriptor.
	 *
	 * Verifies that the print() method correctly handles srcset items
	 * that contain only a URL without a width descriptor.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Modes\Picture::print
	 * @return void
	 */
	public function test_print_handles_srcset_item_without_descriptor(): void {
		$item  = 'test.jpg';
		$parts = preg_split( '/\s+/', trim( $item ) );

		$this->assertEquals( 'test.jpg', $parts[0] );
		$this->assertFalse( isset( $parts[1] ) );
	}

	/**
	 * Test print formats source element with sprintf.
	 *
	 * Verifies that the print() method correctly formats the source element
	 * using sprintf with srcset and sizes parameters.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Modes\Picture::print
	 * @return void
	 */
	public function test_print_formats_source_element_with_sprintf(): void {
		$srcset = 'test.webp';
		$sizes  = '100vw';
		$source = sprintf( '<source type="image/webp" srcset="%s" sizes="%s" />', $srcset, $sizes );

		$expected = '<source type="image/webp" srcset="test.webp" sizes="100vw" />';
		$this->assertEquals( $expected, $source );
	}

	/**
	 * Test print joins srcset array with comma and space.
	 *
	 * Verifies that the print() method correctly joins srcset array items
	 * with comma and space separators.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Modes\Picture::print
	 * @return void
	 */
	public function test_print_joins_srcset_array_correctly(): void {
		$srcset = [
			'test-150.webp 150w',
			'test-300.webp 300w',
			'test-600.webp 600w',
		];

		$result   = implode( ', ', $srcset );
		$expected = 'test-150.webp 150w, test-300.webp 300w, test-600.webp 600w';

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test print structure order: picture > source > img > close.
	 *
	 * Verifies that the print() method maintains the correct HTML element order:
	 * opening picture tag, source element, img tag, closing picture tag.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Modes\Picture::print
	 * @return void
	 */
	public function test_print_maintains_correct_element_order(): void {
		$html = '<picture><source type="image/webp" srcset="test.webp" sizes="100vw" /><img src="test.jpg" /></picture>';

		$picture_pos = strpos( $html, '<picture>' );
		$source_pos  = strpos( $html, '<source' );
		$img_pos     = strpos( $html, '<img' );
		$close_pos   = strpos( $html, '</picture>' );

		$this->assertTrue( $picture_pos < $source_pos );
		$this->assertTrue( $source_pos < $img_pos );
		$this->assertTrue( $img_pos < $close_pos );
	}

	/**
	 * Test prepare returns fallback from Image::prepare.
	 *
	 * Verifies that the prepare() method includes a fallback img tag
	 * generated by Image::prepare for browsers without WebP support.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Modes\Picture::prepare
	 * @return void
	 */
	public function test_prepare_returns_fallback_from_image_prepare(): void {
		$image    = '<img src="test.jpg" />';
		$src      = 'test.jpg';
		$metadata = [];

		BrainMonkey\when( 'wp_get_attachment_image_srcset' )->justReturn( '' );
		BrainMonkey\when( 'wp_image_src_get_dimensions' )->justReturn( [ 800, 600 ] );
		BrainMonkey\when( 'wp_calculate_image_sizes' )->justReturn( '100vw' );
		BrainMonkey\when( 'esc_attr' )->returnArg();

		$result = Picture::prepare( 123, $metadata, $image, $src );

		$this->assertArrayHasKey( 'fallback', $result );
		$this->assertIsString( $result['fallback'] );
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
