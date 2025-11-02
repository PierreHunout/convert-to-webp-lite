<?php
/**
 * Tests for Replace class
 *
 * @package PoetryConvertToWebp\Tests
 */

namespace PoetryConvertToWebp\Tests\Unit\Actions;

use PoetryConvertToWebp\Tests\TestCase;
use PoetryConvertToWebp\Actions\Replace;
use Brain\Monkey\Functions as BrainMonkey;
use RuntimeException;
use ReflectionClass;

/**
 * Class ReplaceTest
 *
 * Tests for Replace class.
 */
class ReplaceTest extends TestCase {

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
	 * Test that get_instance returns singleton.
	 *
	 * Verifies that calling get_instance() multiple times returns the same
	 * instance, confirming the singleton pattern implementation.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Replace::get_instance
	 * @return void
	 */
	public function test_get_instance_returns_singleton(): void {
		BrainMonkey\expect( 'add_filter' )
			->times( 3 )
			->andReturn( true );

		$instance1 = Replace::get_instance();
		$instance2 = Replace::get_instance();

		$this->assertInstanceOf( Replace::class, $instance1 );
		$this->assertSame( $instance1, $instance2, 'Should return same instance' );
	}

	/**
	 * Test that get_instance creates instance on first call.
	 *
	 * Verifies that the first call to get_instance() creates a new instance
	 * and stores it in the static instance property.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Replace::get_instance
	 * @return void
	 */
	public function test_get_instance_creates_instance_on_first_call(): void {
		BrainMonkey\expect( 'add_filter' )
			->times( 3 )
			->andReturn( true );

		$reflection = new ReflectionClass( Replace::class );
		$property   = $reflection->getProperty( 'instance' );
		$property->setAccessible( true );

		// Reset the instance to null
		$property->setValue( null, null );

		$this->assertNull( $property->getValue() );

		$instance = Replace::get_instance();

		$this->assertNotNull( $property->getValue() );
		$this->assertInstanceOf( Replace::class, $instance );
	}

	/**
	 * Test that constructor calls init.
	 *
	 * Verifies that when the Replace class is instantiated, the init() method
	 * is called to register WordPress filter hooks.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Replace::__construct
	 * @covers \PoetryConvertToWebp\Actions\Replace::init
	 * @return void
	 */
	public function test_constructor_calls_init(): void {
		BrainMonkey\expect( 'add_filter' )
			->times( 3 )
			->andReturn( true );

		$instance = new Replace();

		$this->assertInstanceOf( Replace::class, $instance );
	}

	/**
	 * Test that clone is private (singleton pattern).
	 *
	 * Verifies that the __clone() method is private to prevent cloning
	 * of the singleton instance.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Replace::__clone
	 * @return void
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( Replace::class, '__clone' );
	}

	/**
	 * Test that __wakeup throws RuntimeException (singleton pattern).
	 *
	 * Verifies that attempting to unserialize the singleton throws a
	 * RuntimeException to prevent unserialization.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Replace::__wakeup
	 * @return void
	 */
	public function test_wakeup_throws_exception(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot unserialize a singleton.' );

		$reflection = new ReflectionClass( Replace::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Test that init registers filters.
	 *
	 * Verifies that the init() method registers three WordPress filter hooks
	 * for content replacement: the_content, post_thumbnail_html, and widget_text.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Replace::init
	 * @return void
	 */
	public function test_init_registers_filters(): void {
		$filters = [];

		BrainMonkey\when( 'add_filter' )->alias(
			function ( $hook, $callback ) use ( &$filters ) {
				$filters[] = [
					'hook' => $hook,
				];
				return true;
			}
		);

		$instance = new Replace();

		$this->assertCount( 3, $filters );
		$this->assertEquals( 'the_content', $filters[0]['hook'] );
		$this->assertEquals( 'post_thumbnail_html', $filters[1]['hook'] );
		$this->assertEquals( 'widget_text', $filters[2]['hook'] );
	}

	/**
	 * Test replace_webp method exists and is static.
	 *
	 * Verifies that the replace_webp() method exists on the Replace class,
	 * is a public static method, and can be called without instantiating the class.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Replace::replace_webp
	 * @return void
	 */
	public function test_replace_webp_method_exists(): void {
		$this->assertTrue(
			method_exists( Replace::class, 'replace_webp' ),
			'Replace class should have a replace_webp method'
		);

		$reflection = new ReflectionClass( Replace::class );
		$method     = $reflection->getMethod( 'replace_webp' );
		$this->assertTrue( $method->isStatic() );
		$this->assertTrue( $method->isPublic() );
	}

	/**
	 * Test init method exists and is public.
	 *
	 * Verifies that the init() method exists on the Replace class and
	 * is public, allowing it to be called during initialization.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Replace::init
	 * @return void
	 */
	public function test_init_method_exists(): void {
		$this->assertTrue(
			method_exists( Replace::class, 'init' ),
			'Replace class should have an init method'
		);

		$reflection = new ReflectionClass( Replace::class );
		$method     = $reflection->getMethod( 'init' );
		$this->assertTrue( $method->isPublic() );
	}

	/**
	 * Test replace_webp returns string.
	 *
	 * Verifies that the replace_webp() method has a return type of string,
	 * ensuring it always returns modified or original content.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Replace::replace_webp
	 * @return void
	 */
	public function test_replace_webp_returns_string(): void {
		$reflection = new ReflectionClass( Replace::class );
		$method     = $reflection->getMethod( 'replace_webp' );

		$returnType = $method->getReturnType();
		$this->assertNotNull( $returnType );
		$this->assertEquals( 'string', $returnType->getName() );
	}

	/**
	 * Test replace_webp method signature accepts content string.
	 *
	 * Verifies that the replace_webp() method is a public static method
	 * that accepts exactly one parameter named 'content'.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Replace::replace_webp
	 * @return void
	 */
	public function test_replace_webp_method_signature(): void {
		$reflection = new ReflectionClass( Replace::class );
		$method     = $reflection->getMethod( 'replace_webp' );

		$this->assertTrue( $method->isStatic() );
		$this->assertTrue( $method->isPublic() );
		$this->assertEquals( 1, $method->getNumberOfParameters() );

		$parameters = $method->getParameters();
		$this->assertEquals( 'content', $parameters[0]->getName() );
	}

	/**
	 * Test replace_webp handles empty content.
	 *
	 * Verifies that the replace_webp() method correctly handles empty string
	 * input and returns an empty string without errors.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Replace::replace_webp
	 * @return void
	 */
	public function test_replace_webp_handles_empty_content(): void {
		$content = '';
		$result  = Replace::replace_webp( $content );

		$this->assertIsString( $result );
		$this->assertEquals( '', $result );
	}

	/**
	 * Test replace_webp handles content without images.
	 *
	 * Verifies that the replace_webp() method returns content unchanged
	 * when no img tags are present in the input.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Replace::replace_webp
	 * @return void
	 */
	public function test_replace_webp_handles_content_without_images(): void {
		$content = '<p>This is some text without images.</p>';
		$result  = Replace::replace_webp( $content );

		$this->assertIsString( $result );
		$this->assertEquals( $content, $result );
	}

	/**
	 * Test replace_webp preserves content structure.
	 *
	 * Verifies that the replace_webp() method maintains the original HTML
	 * structure of content when processing, without breaking tags or nesting.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Actions\Replace::replace_webp
	 * @return void
	 */
	public function test_replace_webp_preserves_content_structure(): void {
		$content = '<div><p>Text before</p><p>Text after</p></div>';
		$result  = Replace::replace_webp( $content );

		$this->assertIsString( $result );
		$this->assertStringContainsString( '<div>', $result );
		$this->assertStringContainsString( '<p>Text before</p>', $result );
		$this->assertStringContainsString( '<p>Text after</p>', $result );
		$this->assertStringContainsString( '</div>', $result );
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
