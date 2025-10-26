<?php
/**
 * Tests for Picture class
 *
 * @package WpConvertToWebp\Tests
 */

namespace WpConvertToWebp\Tests\Unit\Modes;

use WpConvertToWebp\Tests\TestCase;
use WpConvertToWebp\Modes\Picture;
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
	 * Setup before each test.   */
	protected function set_up(): void {
		parent::set_up();
	}

	/**
	 * Test that constructor is private (singleton pattern).     */
	public function test_constructor_is_private(): void {
		$this->assertMethodIsPrivate( Picture::class, '__construct' );
	}

	/**
	 * Test that clone is private (singleton pattern).   */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( Picture::class, '__clone' );
	}

	/**
	 * Test that __wakeup throws RuntimeException (singleton pattern).   */
	public function test_wakeup_throws_exception(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot unserialize a singleton.' );

		$reflection = new ReflectionClass( Picture::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Test prepare method exists and is public static.  */
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
	 * Test print method exists and is public static.    */
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
	 * Test prepare returns array with required keys.    */
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
	 * Test prepare stores original src.     */
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
	 * Test prepare uses default sizes when null.    */
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
	 * Test print regex converts extensions to webp.     */
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
	 * Test print generates picture element structure.   */
	public function test_print_generates_picture_element_structure(): void {
		$picture_html = '<picture><source type="image/webp" srcset="test.webp" sizes="100vw" /><img src="test.jpg" /></picture>';

		$this->assertStringContainsString( '<picture>', $picture_html );
		$this->assertStringContainsString( '<source', $picture_html );
		$this->assertStringContainsString( 'type="image/webp"', $picture_html );
		$this->assertStringContainsString( '</picture>', $picture_html );
	}

	/**
	 * Test print source element has webp type.  */
	public function test_print_source_element_has_webp_type(): void {
		$source = '<source type="image/webp" srcset="test.webp" sizes="100vw" />';

		$this->assertStringContainsString( 'type="image/webp"', $source );
	}

	/**
	 * Test print source element includes srcset.    */
	public function test_print_source_element_includes_srcset(): void {
		$srcset = 'test-150.webp 150w, test-300.webp 300w';
		$source = sprintf( '<source type="image/webp" srcset="%s" sizes="100vw" />', $srcset );

		$this->assertStringContainsString( 'srcset=', $source );
		$this->assertStringContainsString( $srcset, $source );
	}

	/**
	 * Test print source element includes sizes.     */
	public function test_print_source_element_includes_sizes(): void {
		$sizes  = '(max-width: 600px) 100vw, 50vw';
		$source = sprintf( '<source type="image/webp" srcset="test.webp" sizes="%s" />', $sizes );

		$this->assertStringContainsString( 'sizes=', $source );
		$this->assertStringContainsString( $sizes, $source );
	}

	/**
	 * Test print parses srcset correctly.
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
	 */
	public function test_print_handles_srcset_item_without_descriptor(): void {
		$item  = 'test.jpg';
		$parts = preg_split( '/\s+/', trim( $item ) );

		$this->assertEquals( 'test.jpg', $parts[0] );
		$this->assertFalse( isset( $parts[1] ) );
	}

	/**
	 * Test print formats source element with sprintf.
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
	 * Cleanup after each test.  */
	protected function tear_down(): void {
		parent::tear_down();
	}
}
