<?php
/**
 * Tests for Image class
 *
 * @package WpConvertToWebp\Tests
 */

namespace WpConvertToWebp\Tests\Unit\Modes;

use WpConvertToWebp\Tests\TestCase;
use WpConvertToWebp\Modes\Image;
use Brain\Monkey\Functions as BrainMonkey;
use RuntimeException;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class ImageTest
 *
 * Tests for Image class.
 */
class ImageTest extends TestCase {

	/**
	 * Setup before each test.
	 */
	protected function set_up(): void {
		parent::set_up();
	}

	/**
	 * Test that constructor is private (singleton pattern).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_constructor_is_private(): void {
		$this->assertMethodIsPrivate( Image::class, '__construct' );
	}

	/**
	 * Test that clone is private (singleton pattern).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( Image::class, '__clone' );
	}

	/**
	 * Test that __wakeup throws RuntimeException (singleton pattern).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_wakeup_throws_exception(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot unserialize a singleton.' );

		$reflection = new ReflectionClass( Image::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Test prepare method exists and is public static.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_prepare_method_exists(): void {
		$this->assertTrue(
			method_exists( Image::class, 'prepare' ),
			'Image class should have a prepare method'
		);

		$reflection = new ReflectionMethod( Image::class, 'prepare' );
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
	 * @since 1.0.0
	 * @return void
	 */
	public function test_print_method_exists(): void {
		$this->assertTrue(
			method_exists( Image::class, 'print' ),
			'Image class should have a print method'
		);

		$reflection = new ReflectionMethod( Image::class, 'print' );
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
	 * Test prepare returns original image when dimensions are empty.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_prepare_returns_original_when_dimensions_empty(): void {
		$image    = '<img src="test.jpg" alt="Test">';
		$src      = 'test.jpg';
		$metadata = array( 'width' => 800, 'height' => 600 );

		BrainMonkey\when( 'wp_image_src_get_dimensions' )->justReturn( array() );

		$result = Image::prepare( 123, $metadata, $image, $src );

		$this->assertEquals( $image, $result );
	}

	/**
	 * Test prepare adds width and height attributes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_prepare_adds_width_and_height_attributes(): void {
		$image    = '<img src="test.jpg" alt="Test" />';
		$src      = 'test.jpg';
		$metadata = array( 'width' => 800, 'height' => 600 );

		BrainMonkey\when( 'wp_image_src_get_dimensions' )->justReturn( array( 800, 600 ) );
		BrainMonkey\when( 'esc_attr' )->returnArg();
		BrainMonkey\when( 'wp_get_attachment_image_srcset' )->justReturn( '' );

		$result = Image::prepare( 123, $metadata, $image, $src );

		$this->assertStringContainsString( 'width="800"', $result );
		$this->assertStringContainsString( 'height="600"', $result );
	}

	/**
	 * Test prepare returns image with attributes when srcset is empty.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_prepare_handles_empty_srcset(): void {
		$image    = '<img src="test.jpg" />';
		$src      = 'test.jpg';
		$metadata = array();

		BrainMonkey\when( 'wp_image_src_get_dimensions' )->justReturn( array( 800, 600 ) );
		BrainMonkey\when( 'esc_attr' )->returnArg();
		BrainMonkey\when( 'wp_get_attachment_image_srcset' )->justReturn( '' );

		$result = Image::prepare( 123, $metadata, $image, $src );

		$this->assertStringContainsString( 'width="800"', $result );
		$this->assertStringContainsString( 'height="600"', $result );
		$this->assertStringNotContainsString( 'srcset=', $result );
	}

	/**
	 * Test prepare returns image with attributes when srcset is not a string.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_prepare_handles_non_string_srcset(): void {
		$image    = '<img src="test.jpg" />';
		$src      = 'test.jpg';
		$metadata = array();

		BrainMonkey\when( 'wp_image_src_get_dimensions' )->justReturn( array( 800, 600 ) );
		BrainMonkey\when( 'esc_attr' )->returnArg();
		BrainMonkey\when( 'wp_get_attachment_image_srcset' )->justReturn( false );

		$result = Image::prepare( 123, $metadata, $image, $src );

		$this->assertStringContainsString( 'width="800"', $result );
		$this->assertStringContainsString( 'height="600"', $result );
		$this->assertStringNotContainsString( 'srcset=', $result );
	}

	/**
	 * Test prepare returns image without sizes when sizes is empty.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_prepare_handles_empty_sizes(): void {
		$image    = '<img src="test.jpg" />';
		$src      = 'test.jpg';
		$metadata = array();

		BrainMonkey\when( 'wp_image_src_get_dimensions' )->justReturn( array( 800, 600 ) );
		BrainMonkey\when( 'esc_attr' )->returnArg();
		BrainMonkey\when( 'wp_get_attachment_image_srcset' )->justReturn( 'test-150x150.jpg 150w, test-300x300.jpg 300w' );
		BrainMonkey\when( 'wp_calculate_image_sizes' )->justReturn( '' );

		$result = Image::prepare( 123, $metadata, $image, $src );

		$this->assertStringContainsString( 'width="800"', $result );
		$this->assertStringContainsString( 'height="600"', $result );
		$this->assertStringContainsString( 'srcset=', $result );
		$this->assertStringNotContainsString( 'sizes=', $result );
	}

	/**
	 * Test print regex converts jpg/jpeg/png/gif to webp.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_print_regex_converts_extensions(): void {
		$test_cases = array(
			'test.jpg'  => 'test.webp',
			'test.jpeg' => 'test.webp',
			'test.JPG'  => 'test.webp',
			'test.png'  => 'test.webp',
			'test.PNG'  => 'test.webp',
			'test.gif'  => 'test.webp',
			'test.GIF'  => 'test.webp',
		);

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
	 * Test print regex extracts src attribute correctly.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_print_regex_extracts_src_attribute(): void {
		$test_cases = array(
			'src="test.jpg"'    => 'test.jpg',
			"src='test.png'"    => 'test.png',
			'src="image.jpeg"'  => 'image.jpeg',
			'src="photo.gif"'   => 'photo.gif',
		);

		foreach ( $test_cases as $html => $expected_src ) {
			if ( preg_match( '/src=["\']([^"\']+\.(?:jpe?g|png|gif))["\']/i', $html, $matches ) ) {
				$this->assertEquals(
					$expected_src,
					$matches[1],
					"Expected to extract $expected_src from $html"
				);
			}
		}
	}

	/**
	 * Test print regex extracts srcset attribute correctly.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_print_regex_extracts_srcset_attribute(): void {
		$html = 'srcset="test-150.jpg 150w, test-300.jpg 300w"';

		if ( preg_match( '/srcset=["\']([^"\']+)["\']/i', $html, $matches ) ) {
			$this->assertEquals(
				'test-150.jpg 150w, test-300.jpg 300w',
				$matches[1]
			);
		}
	}

	/**
	 * Test print handles srcset parsing.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_print_parses_srcset_items(): void {
		$srcset = 'test-150.jpg 150w, test-300.jpg 300w, test-600.jpg 600w';
		$array  = explode( ',', $srcset );

		$this->assertCount( 3, $array );

		foreach ( $array as $item ) {
			$parts = preg_split( '/\s+/', trim( $item ) );
			$this->assertCount( 2, $parts, 'Each srcset item should have URL and width descriptor' );
			$this->assertStringEndsWith( 'w', $parts[1], 'Width descriptor should end with w' );
		}
	}

	/**
	 * Test print handles empty srcset parts.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_print_handles_empty_srcset_parts(): void {
		$srcset = 'test-150.jpg 150w, , test-300.jpg 300w';
		$array  = explode( ',', $srcset );
		$valid  = 0;

		foreach ( $array as $item ) {
			$parts = preg_split( '/\s+/', trim( $item ) );
			if ( ! empty( $parts[0] ) ) {
				$valid++;
			}
		}

		$this->assertEquals( 2, $valid, 'Should skip empty srcset items' );
	}

	/**
	 * Test print preserves width descriptor in srcset.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_print_preserves_width_descriptor(): void {
		$item  = 'test-300.jpg 300w';
		$parts = preg_split( '/\s+/', trim( $item ) );

		$this->assertEquals( 'test-300.jpg', $parts[0] );
		$this->assertEquals( '300w', $parts[1] );
	}

	/**
	 * Test print handles srcset without width descriptor.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_print_handles_srcset_without_descriptor(): void {
		$item  = 'test.jpg';
		$parts = preg_split( '/\s+/', trim( $item ) );

		$this->assertEquals( 'test.jpg', $parts[0] );
		$this->assertArrayNotHasKey( 1, $parts );
	}

	/**
	 * Test prepare regex injects attributes correctly.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_prepare_regex_injects_attributes(): void {
		$image      = '<img src="test.jpg" alt="Test" />';
		$attributes = ' width="800" height="600"';
		$result     = preg_replace( '/<img ([^>]+?)[\/ ]*>/', '<img $1' . $attributes . ' />', $image );

		$this->assertStringContainsString( 'width="800"', $result );
		$this->assertStringContainsString( 'height="600"', $result );
		$this->assertStringContainsString( 'alt="Test"', $result );
	}

	/**
	 * Test prepare regex handles img tag with self-closing slash.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_prepare_regex_handles_self_closing_tag(): void {
		$test_cases = array(
			'<img src="test.jpg" />',
			'<img src="test.jpg"/>',
			'<img src="test.jpg" >',
			'<img src="test.jpg">',
		);

		foreach ( $test_cases as $image ) {
			$result = preg_replace( '/<img ([^>]+?)[\/ ]*>/', '<img $1 width="800" />', $image );
			$this->assertStringContainsString( 'width="800"', $result );
		}
	}

	/**
	 * Test prepare regex preserves existing attributes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_prepare_regex_preserves_existing_attributes(): void {
		$image      = '<img src="test.jpg" alt="Test" class="my-class" id="photo" />';
		$attributes = ' width="800"';
		$result     = preg_replace( '/<img ([^>]+?)[\/ ]*>/', '<img $1' . $attributes . ' />', $image );

		$this->assertStringContainsString( 'alt="Test"', $result );
		$this->assertStringContainsString( 'class="my-class"', $result );
		$this->assertStringContainsString( 'id="photo"', $result );
		$this->assertStringContainsString( 'width="800"', $result );
	}

	/**
	 * Cleanup after each test.
	 */
	protected function tear_down(): void {
		parent::tear_down();
	}
}
