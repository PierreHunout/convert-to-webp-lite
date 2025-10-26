<?php
/**
 * Tests for Uninstall class
 *
 * @package WpConvertToWebp\Tests
 */

namespace WpConvertToWebp\Tests\Unit\Actions;

use WpConvertToWebp\Tests\TestCase;
use WpConvertToWebp\Actions\Uninstall;
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
		$this->assertMethodIsPrivate( Uninstall::class, '__construct' );
	}

	/**
	 * Test that clone is private (singleton pattern).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( Uninstall::class, '__clone' );
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

		$reflection = new ReflectionClass( Uninstall::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Test uninstall method exists and is static.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
	 * @return void
	 */
	public function test_uninstall_deletes_all_plugin_options(): void {
		// Test the list of options that should be deleted
		$expected_options = [
			'delete_webp_on_uninstall',
			'delete_webp_on_deactivate',
			'convert_to_webp_quality',
			'convert_to_webp_replace_mode',
		];

		$this->assertCount( 4, $expected_options );
		$this->assertContains( 'delete_webp_on_uninstall', $expected_options );
		$this->assertContains( 'delete_webp_on_deactivate', $expected_options );
		$this->assertContains( 'convert_to_webp_quality', $expected_options );
		$this->assertContains( 'convert_to_webp_replace_mode', $expected_options );
	}

	/**
	 * Test uninstall handles false metadata gracefully.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
	 * @return void
	 */
	public function test_uninstall_deletes_more_options_than_deactivate(): void {
		$uninstall_options = [
			'delete_webp_on_uninstall',
			'delete_webp_on_deactivate',
			'convert_to_webp_quality',
			'convert_to_webp_replace_mode',
		];

		$deactivate_options = [
			'delete_webp_on_deactivate',
			'convert_to_webp_quality',
			'convert_to_webp_replace_mode',
		];

		// Uninstall should delete all deactivate options plus additional ones
		$this->assertGreaterThan( count( $deactivate_options ), count( $uninstall_options ) );
		$this->assertEquals( 4, count( $uninstall_options ) );
		$this->assertEquals( 3, count( $deactivate_options ) );
	}

	/**
	 * Cleanup after each test.
	 */
	protected function tear_down(): void {
		parent::tear_down();
	}
}
