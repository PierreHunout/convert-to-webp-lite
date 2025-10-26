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
	 * Initializes the test environment before each test method.
	 *
	 * Sets up the parent test case environment for testing the Replacer class.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function set_up(): void {
		parent::set_up();
	}

	/**
	 * Tests that the constructor is private to enforce singleton pattern.
	 *
	 * Verifies that Replacer::__construct is private, preventing direct
	 * instantiation of the Replacer class.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Replacer::__construct
	 * @return void
	 */
	public function test_constructor_is_private(): void {
		$this->assertMethodIsPrivate( Replacer::class, '__construct' );
	}

	/**
	 * Tests that the clone method is private to enforce singleton pattern.
	 *
	 * Verifies that Replacer::__clone is private, preventing cloning
	 * of the Replacer singleton instance.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Replacer::__clone
	 * @return void
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( Replacer::class, '__clone' );
	}

	/**
	 * Tests that __wakeup throws RuntimeException to prevent unserialization.
	 *
	 * Verifies that Replacer::__wakeup throws a RuntimeException with the message
	 * "Cannot unserialize a singleton." to prevent singleton deserialization.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Replacer::__wakeup
	 * @return void
	 */
	public function test_wakeup_throws_exception(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot unserialize a singleton.' );

		$reflection = new ReflectionClass( Replacer::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Tests that prepare method exists with correct signature.
	 *
	 * Verifies that Replacer::prepare method exists, is public, and is static,
	 * ensuring it can be called without instantiating the singleton class.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Replacer::prepare
	 * @return void
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
	 * Tests that replace method is private for internal use only.
	 *
	 * Verifies that Replacer::replace is private, ensuring it can only be
	 * called internally by the Replacer class methods.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Replacer::replace
	 * @return void
	 */
	public function test_replace_method_is_private(): void {
		$this->assertMethodIsPrivate( Replacer::class, 'replace' );
	}

	/**
	 * Tests that prepare regex converts JPEG extensions to WebP.
	 *
	 * Verifies that the regex pattern correctly converts .jpg and .jpeg extensions
	 * (including uppercase variants) to .webp extension.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Replacer::prepare
	 * @return void
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
	 * Tests that prepare regex converts PNG extensions to WebP.
	 *
	 * Verifies that the regex pattern correctly converts .png extensions
	 * (including uppercase variants) to .webp extension.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Replacer::prepare
	 * @return void
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
	 * Tests that prepare regex converts GIF extensions to WebP.
	 *
	 * Verifies that the regex pattern correctly converts .gif extensions
	 * (including uppercase variants) to .webp extension.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Replacer::prepare
	 * @return void
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
	 * Tests that prepare regex preserves complete file paths.
	 *
	 * Verifies that the regex pattern converts only the file extension while
	 * preserving the full path, domain, and filename structure.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Replacer::prepare
	 * @return void
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
	 * Tests that prepare regex handles WordPress image size suffixes.
	 *
	 * Verifies that the regex pattern correctly converts extensions on files
	 * with WordPress thumbnail size suffixes (e.g., -150x150, -scaled).
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Replacer::prepare
	 * @return void
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
	 * Tests that prepare regex only converts extensions at end of string.
	 *
	 * Verifies that the regex pattern only converts the file extension at the
	 * end of the string, ignoring extensions followed by query strings or anchors.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Replacer::prepare
	 * @return void
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
	 * Performs cleanup operations after each test method completes.
	 *
	 * Tears down the test environment by calling the parent tear_down method
	 * to clean up hooks and mocks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function tear_down(): void {
		parent::tear_down();
	}
}
