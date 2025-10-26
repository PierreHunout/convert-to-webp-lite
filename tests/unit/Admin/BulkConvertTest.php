<?php
/**
 * Tests for BulkConvert class
 *
 * @package WpConvertToWebp\Tests
 */

namespace WpConvertToWebp\Tests\Unit\Admin;

use WpConvertToWebp\Tests\TestCase;
use WpConvertToWebp\Admin\BulkConvert;
use RuntimeException;
use ReflectionClass;

/**
 * Class BulkConvertTest
 *
 * Tests for BulkConvert class.
 */
class BulkConvertTest extends TestCase {

	/**
	 * Setup before each test.
	 */
	protected function set_up(): void {
		parent::set_up();
	}

	/**
	 * Test that clone is private (singleton pattern).
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( BulkConvert::class, '__clone' );
	}

	/**
	 * Test that __wakeup throws RuntimeException (singleton pattern).
	 */
	public function test_wakeup_throws_exception(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot unserialize a singleton.' );

		$reflection = new ReflectionClass( BulkConvert::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Cleanup after each test.
	 */
	protected function tear_down(): void {
		parent::tear_down();
	}
}
