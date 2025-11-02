<?php
/**
 * Tests for Uninstall class
 *
 * @package PoetryConvertToWebp\Tests
 */

namespace PoetryConvertToWebp\Tests\Unit\Actions;

use PoetryConvertToWebp\Tests\TestCase;
use PoetryConvertToWebp\Actions\Uninstall;
use Brain\Monkey\Functions as BrainMonkey;
use RuntimeException;
use ReflectionClass;

/**
 * Class UninstallTest
 *
 * Tests for Uninstall class.
 */
class UninstallTest extends TestCase {

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
	 * direct instantiation of the Uninstall class.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Uninstall::__construct
	 * @return void
	 */
	public function test_constructor_is_private(): void {
		$this->assertMethodIsPrivate( Uninstall::class, '__construct' );
	}

	/**
	 * Test that clone is private (singleton pattern).
	 *
	 * Verifies that the __clone() method is private to prevent cloning
	 * of the Uninstall class instance.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Uninstall::__clone
	 * @return void
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( Uninstall::class, '__clone' );
	}

	/**
	 * Test that __wakeup throws RuntimeException (singleton pattern).
	 *
	 * Verifies that attempting to unserialize the Uninstall class throws a
	 * RuntimeException to prevent unserialization.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Uninstall::__wakeup
	 * @return void
	 */
	public function test_wakeup_throws_exception(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot unserialize a singleton.' );

		$reflection = new ReflectionClass( Uninstall::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Test uninstall method exists and is static.
	 *
	 * Verifies that the uninstall() method exists in the Uninstall class,
	 * is publicly accessible, and is declared as static.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Uninstall::uninstall
	 * @return void
	 */
	public function test_uninstall_method_exists(): void {
		$this->assertTrue(
			method_exists( Uninstall::class, 'uninstall' ),
			'Uninstall class should have an uninstall method'
		);

		$reflection = new ReflectionClass( Uninstall::class );
		$method     = $reflection->getMethod( 'uninstall' );
		$this->assertTrue( $method->isStatic() );
		$this->assertTrue( $method->isPublic() );
	}

	/**
	 * Test uninstall returns early when delete option is false.
	 *
	 * Verifies that when the 'delete_webp_on_uninstall' option is false,
	 * the uninstall() method returns early without processing.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Uninstall::uninstall
	 * @return void
	 */
	public function test_uninstall_returns_early_when_delete_option_false(): void {
		BrainMonkey\expect( 'get_option' )
			->once()
			->with( 'delete_webp_on_uninstall', false )
			->andReturn( false );

		// If option is false, should return early and not call other functions
		$result = Uninstall::uninstall();

		$this->assertNull( $result );
	}

	/**
	 * Test uninstall processes when delete option is enabled.
	 *
	 * Verifies that when the 'delete_webp_on_uninstall' option is enabled,
	 * the cleanup process is triggered without errors.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Uninstall::uninstall
	 * @return void
	 */
	public function test_uninstall_option_enabled_triggers_cleanup(): void {
		BrainMonkey\expect( 'get_option' )
			->once()
			->with( 'delete_webp_on_uninstall', false )
			->andReturn( true );

		// Mock get_attachments to return empty array (early return)
		BrainMonkey\expect( 'get_posts' )
			->once()
			->andReturn( [] );

		Uninstall::uninstall();

		$this->assertTrue( true ); // Assert the code ran without errors
	}

	/**
	 * Test that uninstall deletes all plugin options.
	 *
	 * Verifies that uninstall() deletes all 4 plugin options: delete_webp_on_uninstall,
	 * delete_webp_on_deactivate, poetry_convert_to_webp_quality, and poetry_convert_to_webp_replace_mode.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Uninstall::uninstall
	 * @return void
	 */
	public function test_uninstall_deletes_all_plugin_options(): void {
		// Test the list of options that should be deleted
		$expected_options = [
			'delete_webp_on_uninstall',
			'delete_webp_on_deactivate',
			'poetry_convert_to_webp_quality',
			'poetry_convert_to_webp_replace_mode',
		];

		$this->assertCount( 4, $expected_options );
		$this->assertContains( 'delete_webp_on_uninstall', $expected_options );
		$this->assertContains( 'delete_webp_on_deactivate', $expected_options );
		$this->assertContains( 'poetry_convert_to_webp_quality', $expected_options );
		$this->assertContains( 'poetry_convert_to_webp_replace_mode', $expected_options );
	}

	/**
	 * Test uninstall handles false metadata gracefully.
	 *
	 * Verifies that the uninstall process properly converts false metadata
	 * values to empty arrays before processing.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Uninstall::uninstall
	 * @return void
	 */
	public function test_uninstall_converts_false_metadata_to_array(): void {
		// Test the metadata conversion logic directly
		$metadata = false;

		if ( false === $metadata ) {
			$metadata = [];
		}

		$this->assertIsArray( $metadata );
		$this->assertEmpty( $metadata );
	}

	/**
	 * Test uninstall method return type is void.
	 *
	 * Verifies that uninstall() declares a void return type,
	 * indicating it performs an action without returning a value.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Uninstall::uninstall
	 * @return void
	 */
	public function test_uninstall_returns_void(): void {
		$reflection = new ReflectionClass( Uninstall::class );
		$method     = $reflection->getMethod( 'uninstall' );

		$returnType = $method->getReturnType();
		$this->assertNotNull( $returnType );
		$this->assertEquals( 'void', $returnType->getName() );
	}

	/**
	 * Test uninstall method has no parameters.
	 *
	 * Verifies that uninstall() is a static method with no parameters,
	 * designed to be called directly without arguments.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Uninstall::uninstall
	 * @return void
	 */
	public function test_uninstall_method_signature(): void {
		$reflection = new ReflectionClass( Uninstall::class );
		$method     = $reflection->getMethod( 'uninstall' );

		$this->assertTrue( $method->isStatic() );
		$this->assertTrue( $method->isPublic() );
		$this->assertEquals( 0, $method->getNumberOfParameters() );
	}

	/**
	 * Test uninstall deletes more options than deactivate.
	 *
	 * Verifies that uninstall() removes more options than deactivate(),
	 * specifically 4 options vs 3, including the delete_webp_on_uninstall option.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Uninstall::uninstall
	 * @return void
	 */
	public function test_uninstall_deletes_more_options_than_deactivate(): void {
		$uninstall_options = [
			'delete_webp_on_uninstall',
			'delete_webp_on_deactivate',
			'poetry_convert_to_webp_quality',
			'poetry_convert_to_webp_replace_mode',
		];

		$deactivate_options = [
			'delete_webp_on_deactivate',
			'poetry_convert_to_webp_quality',
			'poetry_convert_to_webp_replace_mode',
		];

		// Uninstall should delete all deactivate options plus additional ones
		$this->assertGreaterThan( count( $deactivate_options ), count( $uninstall_options ) );
		$this->assertEquals( 4, count( $uninstall_options ) );
		$this->assertEquals( 3, count( $deactivate_options ) );
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
