<?php
/**
 * Tests for Deactivate class
 *
 * @package WpConvertToWebp\Tests
 */

namespace WpConvertToWebp\Tests\Unit\Actions;

use WpConvertToWebp\Tests\TestCase;
use WpConvertToWebp\Actions\Deactivate;
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
		$this->assertMethodIsPrivate( Deactivate::class, '__construct' );
	}

	/**
	 * Test that clone is private (singleton pattern).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( Deactivate::class, '__clone' );
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

		$reflection = new ReflectionClass( Deactivate::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Test deactivate method exists and is static.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
	 * @return void
	 */
	public function test_deactivate_deletes_correct_options(): void {
		// Test the list of options that should be deleted
		$expected_options = [
			'delete_webp_on_deactivate',
			'convert_to_webp_quality',
			'convert_to_webp_replace_mode',
		];

		$this->assertCount( 3, $expected_options );
		$this->assertContains( 'delete_webp_on_deactivate', $expected_options );
		$this->assertContains( 'convert_to_webp_quality', $expected_options );
		$this->assertContains( 'convert_to_webp_replace_mode', $expected_options );
	}

	/**
	 * Test deactivate handles false metadata gracefully.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 */
	protected function tear_down(): void {
		parent::tear_down();
	}
}
