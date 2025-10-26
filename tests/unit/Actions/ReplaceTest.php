<?php
/**
 * Tests for Replace class
 *
 * @package WpConvertToWebp\Tests
 */

namespace WpConvertToWebp\Tests\Unit\Actions;

use WpConvertToWebp\Tests\TestCase;
use WpConvertToWebp\Actions\Replace;
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
	 * @return void
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( Replace::class, '__clone' );
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

		$reflection = new ReflectionClass( Replace::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Test that init registers filters.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * Cleanup after each test.
	 */
	protected function tear_down(): void {
		parent::tear_down();
	}
}
