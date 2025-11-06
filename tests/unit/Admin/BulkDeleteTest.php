<?php
/**
 * Tests for BulkDelete class
 *
 * @package PoetryConvertToWebp\Tests
 */

namespace PoetryConvertToWebp\Tests\Unit\Admin;

use PoetryConvertToWebp\Tests\TestCase;
use PoetryConvertToWebp\Admin\BulkDelete;
use Brain\Monkey\Functions as BrainMonkey;
use RuntimeException;
use ReflectionClass;

/**
 * Class BulkDeleteTest
 *
 * Tests for BulkDelete class.
 */
class BulkDeleteTest extends TestCase {

	/**
	 * Initializes the test environment before each test method.
	 *
	 * Sets up the parent test case environment and prepares for BulkDelete
	 * class testing.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function set_up(): void {
		parent::set_up();
	}

	/**
	 * Test that get_instance returns singleton.
	 *
	 * Verifies that the get_instance() method creates a singleton instance on first call
	 * and returns the same instance on subsequent calls.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\BulkDelete::get_instance
	 * @return void
	 */
	public function test_get_instance_returns_singleton(): void {
		BrainMonkey\expect( 'add_action' )
			->once()
			->andReturn( true );

		$instance1 = BulkDelete::get_instance();
		$instance2 = BulkDelete::get_instance();

		$this->assertInstanceOf( BulkDelete::class, $instance1 );
		$this->assertSame( $instance1, $instance2, 'Should return same instance' );
	}

	/**
	 * Test that clone is private (singleton pattern).
	 *
	 * Verifies that the __clone() method is private to prevent cloning
	 * of the singleton instance.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\BulkDelete::__clone
	 * @return void
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( BulkDelete::class, '__clone' );
	}

	/**
	 * Test that __wakeup throws RuntimeException (singleton pattern).
	 *
	 * Verifies that attempting to unserialize the singleton instance throws a
	 * RuntimeException to prevent unserialization.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\BulkDelete::__wakeup
	 * @return void
	 */
	public function test_wakeup_throws_exception(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot unserialize a singleton.' );

		$reflection = new ReflectionClass( BulkDelete::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Test that init registers admin_post action.
	 *
	 * Verifies that the init() method registers the admin_post_poetry_convert_to_webp_clean_files
	 * WordPress action hook for handling bulk deletion requests.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\BulkDelete::init
	 * @return void
	 */
	public function test_init_registers_admin_post_action(): void {
		$hooks = [];

		BrainMonkey\when( 'add_action' )->alias(
			function ( $hook, $callback ) use ( &$hooks ) {
				$hooks[] = $hook;
				return true;
			}
		);

		BulkDelete::init();

		$this->assertContains( 'admin_post_poetry_convert_to_webp_clean_files', $hooks );
		$this->assertCount( 1, $hooks );
	}

	/**
	 * Test clean_webp method exists and is public static.
	 *
	 * Verifies that the clean_webp() method exists, is public and static,
	 * allowing it to be called as an action hook callback.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\BulkDelete::clean_webp
	 * @return void
	 */
	public function test_clean_webp_method_exists(): void {
		$this->assertTrue(
			method_exists( BulkDelete::class, 'clean_webp' ),
			'BulkDelete class should have a clean_webp method'
		);

		$reflection = new ReflectionClass( BulkDelete::class );
		$method     = $reflection->getMethod( 'clean_webp' );
		$this->assertTrue( $method->isPublic() );
		$this->assertTrue( $method->isStatic() );
	}

	/**
	 * Test init method exists and is public static.
	 *
	 * Verifies that the init() method exists, is public and static,
	 * allowing it to be called during plugin initialization.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\BulkDelete::init
	 * @return void
	 */
	public function test_init_method_exists(): void {
		$this->assertTrue(
			method_exists( BulkDelete::class, 'init' ),
			'BulkDelete class should have an init method'
		);

		$reflection = new ReflectionClass( BulkDelete::class );
		$method     = $reflection->getMethod( 'init' );
		$this->assertTrue( $method->isPublic() );
		$this->assertTrue( $method->isStatic() );
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
