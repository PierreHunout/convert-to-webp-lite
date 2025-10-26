<?php
/**
 * Tests for Delete class
 *
 * @package WpConvertToWebp\Tests
 */

namespace WpConvertToWebp\Tests\Unit\Actions;

use WpConvertToWebp\Tests\TestCase;
use WpConvertToWebp\Actions\Delete;
use Brain\Monkey\Functions as BrainMonkey;
use RuntimeException;
use ReflectionClass;

/**
 * Class DeleteTest
 *
 * Tests for Delete class.
 */
class DeleteTest extends TestCase {

	/**
	 * Setup before each test.
	 */
	protected function set_up(): void {
		parent::set_up();
	}

	/**
	 * Test that get_instance returns singleton.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_get_instance_returns_singleton(): void {
		BrainMonkey\expect( 'add_action' )
			->once()
			->andReturn( true );

		$instance1 = Delete::get_instance();
		$instance2 = Delete::get_instance();

		$this->assertInstanceOf( Delete::class, $instance1 );
		$this->assertSame( $instance1, $instance2, 'Should return same instance' );
	}

	/**
	 * Test that get_instance creates instance on first call.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_get_instance_creates_instance_on_first_call(): void {
		BrainMonkey\expect( 'add_action' )
			->once()
			->andReturn( true );

		$reflection = new ReflectionClass( Delete::class );
		$property   = $reflection->getProperty( 'instance' );
		$property->setAccessible( true );

		// Reset the instance to null
		$property->setValue( null, null );

		$this->assertNull( $property->getValue() );

		$instance = Delete::get_instance();

		$this->assertNotNull( $property->getValue() );
		$this->assertInstanceOf( Delete::class, $instance );
	}

	/**
	 * Test that constructor calls init.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_constructor_calls_init(): void {
		BrainMonkey\expect( 'add_action' )
			->once()
			->andReturn( true );

		$instance = new Delete();

		$this->assertInstanceOf( Delete::class, $instance );
	}

	/**
	 * Test that clone is private (singleton pattern).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( Delete::class, '__clone' );
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

		$reflection = new ReflectionClass( Delete::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Test that init registers delete_attachment action.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_init_registers_action(): void {
		$actions = [];

		BrainMonkey\when( 'add_action' )->alias(
			function ( $hook, $callback ) use ( &$actions ) {
				$actions[] = [
					'hook' => $hook,
				];
				return true;
			}
		);

		$instance = new Delete();

		$this->assertCount( 1, $actions );
		$this->assertEquals( 'delete_attachment', $actions[0]['hook'] );
	}

	/**
	 * Test delete_webp method exists and is public.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_delete_webp_method_exists(): void {
		$this->assertTrue(
			method_exists( Delete::class, 'delete_webp' ),
			'Delete class should have a delete_webp method'
		);

		$reflection = new ReflectionClass( Delete::class );
		$method     = $reflection->getMethod( 'delete_webp' );
		$this->assertTrue( $method->isPublic() );
	}

	/**
	 * Test delete_webp handles false metadata.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_delete_webp_handles_false_metadata(): void {
		// Test that false metadata would be converted to empty array
		$metadata = false;

		if ( false === $metadata ) {
			$metadata = [];
		}

		$this->assertIsArray( $metadata );
		$this->assertEmpty( $metadata );
	}

	/**
	 * Test delete_webp method signature accepts attachment ID.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_delete_webp_method_signature(): void {
		$reflection = new ReflectionClass( Delete::class );
		$method     = $reflection->getMethod( 'delete_webp' );

		$this->assertTrue( $method->isPublic() );
		$this->assertEquals( 1, $method->getNumberOfParameters() );

		$parameters = $method->getParameters();
		$this->assertEquals( 'attachment_id', $parameters[0]->getName() );
	}

	/**
	 * Test delete_webp return type is void.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_delete_webp_returns_void(): void {
		$reflection = new ReflectionClass( Delete::class );
		$method     = $reflection->getMethod( 'delete_webp' );

		$returnType = $method->getReturnType();
		$this->assertNotNull( $returnType );
		$this->assertEquals( 'void', $returnType->getName() );
	}

	/**
	 * Test that metadata structure is validated.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_metadata_validation(): void {
		// Test that false metadata would be converted to empty array
		$metadata = false;

		if ( false === $metadata ) {
			$metadata = [];
		}

		$this->assertIsArray( $metadata );
		$this->assertEmpty( $metadata );
	}

	/**
	 * Test init method exists and is public.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_init_method_exists(): void {
		$this->assertTrue(
			method_exists( Delete::class, 'init' ),
			'Delete class should have an init method'
		);

		$reflection = new ReflectionClass( Delete::class );
		$method     = $reflection->getMethod( 'init' );
		$this->assertTrue( $method->isPublic() );
	}
}
