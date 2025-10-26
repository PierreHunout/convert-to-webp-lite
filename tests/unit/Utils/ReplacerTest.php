<?php
/**
 * Tests for Replacer class
 *
 * @package WpConvertToWebp\Tests
 */

namespace WpConvertToWebp\Tests\Unit\Utils;

use WpConvertToWebp\Tests\TestCase;
use WpConvertToWebp\Utils\Replacer;
use RuntimeException;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class ReplacerTest
 *
 * Tests for Replacer class.
 */
class ReplacerTest extends TestCase {

	/**
	 * Setup before each test.
	 */
	protected function set_up(): void {
		parent::set_up();
	}

	/**
	 * Test that constructor is private (singleton pattern).
	 */
	public function test_constructor_is_private(): void {
		$this->assertMethodIsPrivate( Replacer::class, '__construct' );
	}

	/**
	 * Test that clone is private (singleton pattern).
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( Replacer::class, '__clone' );
	}

	/**
	 * Test that __wakeup throws RuntimeException (singleton pattern).
	 */
	public function test_wakeup_throws_exception(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot unserialize a singleton.' );

		$reflection = new ReflectionClass( Replacer::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Test prepare method signature and return type.
	 */
	public function test_prepare_method_exists(): void {
		$this->assertTrue(
			method_exists( Replacer::class, 'prepare' ),
			'Replacer class should have a prepare method'
		);

		$reflection = new ReflectionMethod( Replacer::class, 'prepare' );
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
	 * Test replace method exists and is private.
	 */
	public function test_replace_method_is_private(): void {
		$this->assertMethodIsPrivate( Replacer::class, 'replace' );
	}

	/**
	 * Test prepare converts jpeg extension to webp in regex.
	 */
	public function test_prepare_regex_converts_jpg_to_webp(): void {
		$test_cases = [
			'image.jpg'  => 'image.webp',
			'image.jpeg' => 'image.webp',
			'image.JPG'  => 'image.webp',
			'image.JPEG' => 'image.webp',
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
	 * Test prepare regex converts png extension to webp.
	 */
	public function test_prepare_regex_converts_png_to_webp(): void {
		$test_cases = [
			'image.png' => 'image.webp',
			'image.PNG' => 'image.webp',
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
	 * Test prepare regex converts gif extension to webp.
	 */
	public function test_prepare_regex_converts_gif_to_webp(): void {
		$test_cases = [
			'animation.gif' => 'animation.webp',
			'animation.GIF' => 'animation.webp',
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
	 * Test prepare regex preserves path and filename.
	 */
	public function test_prepare_regex_preserves_path(): void {
		$test_cases = [
			'/path/to/image.jpg'                  => '/path/to/image.webp',
			'https://example.com/wp-content/uploads/image.png' => 'https://example.com/wp-content/uploads/image.webp',
			'/uploads/2024/01/photo-150x150.jpeg' => '/uploads/2024/01/photo-150x150.webp',
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
	 * Test prepare regex handles image sizes (WordPress thumbnails).
	 */
	public function test_prepare_regex_handles_image_sizes(): void {
		$test_cases = [
			'image-150x150.jpg'   => 'image-150x150.webp',
			'image-300x200.jpeg'  => 'image-300x200.webp',
			'image-1024x768.png'  => 'image-1024x768.webp',
			'image-scaled.jpg'    => 'image-scaled.webp',
			'image-1920x1080.gif' => 'image-1920x1080.webp',
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
	 * Test prepare regex only converts extension at end of string.
	 */
	public function test_prepare_regex_only_converts_extension_at_end(): void {
		$test_cases = [
			'image.jpg?version=1' => 'image.jpg?version=1',  // Should NOT convert
			'image.jpg#anchor'    => 'image.jpg#anchor',     // Should NOT convert
			'jpg.file.jpg'        => 'jpg.file.webp',        // Should convert only last
		];

		foreach ( $test_cases as $original => $expected ) {
			$result = preg_replace( '/\.(jpe?g|png|gif)$/i', '.webp', $original );
			$this->assertEquals(
				$expected,
				$result,
				"Expected $original to result in $expected"
			);
		}
	}

	/**
	 * Cleanup after each test.
	 */
	protected function tear_down(): void {
		parent::tear_down();
	}
}
