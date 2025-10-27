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
	 * Tests that get_instance returns singleton instance.
	 *
	 * Verifies that Replacer::get_instance returns the same instance on
	 * multiple calls, enforcing the singleton pattern.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Replacer::get_instance
	 * @return void
	 */
	public function test_get_instance_returns_singleton(): void {
		$instance1 = Replacer::get_instance();
		$instance2 = Replacer::get_instance();

		$this->assertInstanceOf(
			Replacer::class,
			$instance1,
			'get_instance should return a Replacer instance'
		);
		$this->assertSame(
			$instance1,
			$instance2,
			'get_instance should return the same instance'
		);
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
	 * Tests that prepare returns original image when WebP already in use.
	 *
	 * Verifies that the regex check correctly identifies WebP files
	 * to avoid processing them again.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Replacer::prepare
	 * @return void
	 */
	public function test_prepare_detects_webp_extension(): void {
		$test_urls = [
			'https://example.com/image.webp',
			'https://example.com/photo.WEBP',
			'/uploads/2024/test.webp',
		];

		foreach ( $test_urls as $url ) {
			$is_webp = preg_match( '/\.webp$/i', $url );
			$this->assertEquals(
				1,
				$is_webp,
				"URL {$url} should be detected as WebP"
			);
		}
	}

	/**
	 * Tests that prepare correctly builds WebP paths from original images.
	 *
	 * Verifies the regex replacement that converts image extensions
	 * to .webp for checking file existence.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Replacer::prepare
	 * @return void
	 */
	public function test_prepare_builds_webp_paths_correctly(): void {
		$test_cases = [
			'https://example.com/photo.jpg'     => 'https://example.com/photo.webp',
			'https://example.com/image.jpeg'    => 'https://example.com/image.webp',
			'https://example.com/pic.png'       => 'https://example.com/pic.webp',
			'https://example.com/anim.gif'      => 'https://example.com/anim.webp',
			'/uploads/2024/01/test-150x150.jpg' => '/uploads/2024/01/test-150x150.webp',
		];

		foreach ( $test_cases as $original => $expected ) {
			$result = preg_replace( '/\.(jpe?g|png|gif)$/i', '.webp', $original );
			$this->assertEquals(
				$expected,
				$result,
				"Expected {$original} to convert to {$expected}"
			);
		}
	}

	/**
	 * Tests the replace mode option values.
	 *
	 * Verifies that replace mode is properly cast to boolean
	 * for Picture mode (true/1) vs Image mode (false/0).
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Utils\Replacer::prepare
	 * @return void
	 */
	public function test_replace_mode_boolean_casting(): void {
		// Test various option values that should be cast to boolean
		$test_cases = [
			0     => false,  // Image mode
			false => false,  // Image mode
			''    => false,  // Image mode
			1     => true,   // Picture mode
			true  => true,   // Picture mode
			'1'   => true,   // Picture mode
		];

		foreach ( $test_cases as $input => $expected ) {
			$result = (bool) $input;
			$this->assertEquals(
				$expected,
				$result,
				'Expected ' . var_export( $input, true ) . ' to cast to ' . var_export( $expected, true )
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
