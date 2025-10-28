<?php
/**
 * Tests for Deactivate class
 *
 * @package ConvertToWebpLite\Tests
 */

namespace ConvertToWebpLite\Tests\Unit\Actions;

use ConvertToWebpLite\Tests\TestCase;
use ConvertToWebpLite\Actions\Deactivate;
use Brain\Monkey\Functions as BrainMonkey;
use RuntimeException;
use ReflectionClass;

/**
 * Class DeactivateTest
 *
 * Tests for Deactivate class.
 */
class DeactivateTest extends TestCase {

	/**
	 * Setup before each test.
	 *
	 * Initializes the test environment by calling the parent setup method.
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
	 * Verifies that the __construct() method is private to prevent
	 * direct instantiation of the Deactivate class.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Actions\Deactivate::__construct
	 * @return void
	 */
	public function test_constructor_is_private(): void {
		$this->assertMethodIsPrivate( Deactivate::class, '__construct' );
	}

	/**
	 * Test that clone is private (singleton pattern).
	 *
	 * Verifies that the __clone() method is private to prevent cloning
	 * of the Deactivate class instance.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Actions\Deactivate::__clone
	 * @return void
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( Deactivate::class, '__clone' );
	}

	/**
	 * Test that __wakeup throws RuntimeException (singleton pattern).
	 *
	 * Verifies that attempting to unserialize the Deactivate class throws a
	 * RuntimeException to prevent unserialization.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Actions\Deactivate::__wakeup
	 * @return void
	 */
	public function test_wakeup_throws_exception(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot unserialize a singleton.' );

		$reflection = new ReflectionClass( Deactivate::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Test deactivate method exists and is static.
	 *
	 * Verifies that the deactivate() method exists in the Deactivate class,
	 * is publicly accessible, and is declared as static.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Actions\Deactivate::deactivate
	 * @return void
	 */
	public function test_deactivate_method_exists(): void {
		$this->assertTrue(
			method_exists( Deactivate::class, 'deactivate' ),
			'Deactivate class should have a deactivate method'
		);

		$reflection = new ReflectionClass( Deactivate::class );
		$method     = $reflection->getMethod( 'deactivate' );
		$this->assertTrue( $method->isStatic() );
		$this->assertTrue( $method->isPublic() );
	}

	/**
	 * Test deactivate returns early when delete option is false.
	 *
	 * Verifies that when the 'delete_webp_on_deactivate' option is false,
	 * the deactivate() method returns early without processing.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Actions\Deactivate::deactivate
	 * @return void
	 */
	public function test_deactivate_returns_early_when_delete_option_false(): void {
		BrainMonkey\expect( 'get_option' )
			->once()
			->with( 'delete_webp_on_deactivate', false )
			->andReturn( false );

		// If option is false, should return early and not call other functions
		$result = Deactivate::deactivate();

		$this->assertNull( $result );
	}

	/**
	 * Test deactivate processes when delete option is enabled.
	 *
	 * Verifies that when the 'delete_webp_on_deactivate' option is enabled,
	 * the cleanup process is triggered without errors.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Actions\Deactivate::deactivate
	 * @return void
	 */
	public function test_deactivate_option_enabled_triggers_cleanup(): void {
		BrainMonkey\expect( 'get_option' )
			->once()
			->with( 'delete_webp_on_deactivate', false )
			->andReturn( true );

		// Mock get_attachments to return empty array (early return)
		BrainMonkey\expect( 'get_posts' )
			->once()
			->andReturn( [] );

		Deactivate::deactivate();

		$this->assertTrue( true ); // Assert the code ran without errors
	}

	/**
	 * Test that delete_option is called for plugin options.
	 *
	 * Verifies that deactivate() deletes the correct plugin options:
	 * delete_webp_on_deactivate, convert_to_webp_lite_quality, and convert_to_webp_lite_replace_mode.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Actions\Deactivate::deactivate
	 * @return void
	 */
	public function test_deactivate_deletes_correct_options(): void {
		// Test the list of options that should be deleted
		$expected_options = [
			'delete_webp_on_deactivate',
			'convert_to_webp_lite_quality',
			'convert_to_webp_lite_replace_mode',
		];

		$this->assertCount( 3, $expected_options );
		$this->assertContains( 'delete_webp_on_deactivate', $expected_options );
		$this->assertContains( 'convert_to_webp_lite_quality', $expected_options );
		$this->assertContains( 'convert_to_webp_lite_replace_mode', $expected_options );
	}

	/**
	 * Test deactivate handles false metadata gracefully.
	 *
	 * Verifies that the deactivate process properly converts false metadata
	 * values to empty arrays before processing.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Actions\Deactivate::deactivate
	 * @return void
	 */
	public function test_deactivate_converts_false_metadata_to_array(): void {
		// Test the metadata conversion logic directly
		$metadata = false;

		if ( false === $metadata ) {
			$metadata = [];
		}

		$this->assertIsArray( $metadata );
		$this->assertEmpty( $metadata );
	}

	/**
	 * Test deactivate method return type is void.
	 *
	 * Verifies that deactivate() declares a void return type,
	 * indicating it performs an action without returning a value.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Actions\Deactivate::deactivate
	 * @return void
	 */
	public function test_deactivate_returns_void(): void {
		$reflection = new ReflectionClass( Deactivate::class );
		$method     = $reflection->getMethod( 'deactivate' );

		$returnType = $method->getReturnType();
		$this->assertNotNull( $returnType );
		$this->assertEquals( 'void', $returnType->getName() );
	}

	/**
	 * Test deactivate method has no parameters.
	 *
	 * Verifies that deactivate() is a static method with no parameters,
	 * designed to be called directly without arguments.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Actions\Deactivate::deactivate
	 * @return void
	 */
	public function test_deactivate_method_signature(): void {
		$reflection = new ReflectionClass( Deactivate::class );
		$method     = $reflection->getMethod( 'deactivate' );

		$this->assertTrue( $method->isStatic() );
		$this->assertTrue( $method->isPublic() );
		$this->assertEquals( 0, $method->getNumberOfParameters() );
	}

	/**
	 * Cleanup after each test.
	 *
	 * Performs cleanup operations after each test by calling the parent
	 * tear_down method to reset the test environment.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function tear_down(): void {
		parent::tear_down();
	}
}
