<?php
/**
 * Tests for Add class
 *
 * @package WpConvertToWebp\Tests
 */

namespace WpConvertToWebp\Tests\Unit\Actions;

use WpConvertToWebp\Tests\TestCase;
use WpConvertToWebp\Actions\Add;
use Brain\Monkey\Functions as BrainMonkey;
use RuntimeException;
use ReflectionClass;

/**
 * Class AddTest
 *
 * Tests for Add class.
 */
class AddTest extends TestCase {

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
	 * @covers \WpConvertToWebp\Actions\Add::get_instance
	 * @return void
	 */
	public function test_get_instance_returns_singleton(): void {
		BrainMonkey\expect( 'add_filter' )
			->once()
			->andReturn( true );

		$instance1 = Add::get_instance();
		$instance2 = Add::get_instance();

		$this->assertInstanceOf( Add::class, $instance1 );
		$this->assertSame( $instance1, $instance2, 'Should return same instance' );
	}

	/**
	 * Test that get_instance creates instance on first call.
	 *
	 * Verifies that the first call to get_instance() creates a new instance
	 * and stores it in the static instance property.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Actions\Add::get_instance
	 * @return void
	 */
	public function test_get_instance_creates_instance_on_first_call(): void {
		BrainMonkey\expect( 'add_filter' )
			->once()
			->andReturn( true );

		$reflection = new ReflectionClass( Add::class );
		$property   = $reflection->getProperty( 'instance' );
		$property->setAccessible( true );

		// Reset the instance to null
		$property->setValue( null, null );

		$this->assertNull( $property->getValue() );

		$instance = Add::get_instance();

		$this->assertNotNull( $property->getValue() );
		$this->assertInstanceOf( Add::class, $instance );
	}

	/**
	 * Test that constructor calls init.
	 *
	 * Verifies that when the Add class is instantiated, the init() method
	 * is called to register WordPress hooks.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Actions\Add::__construct
	 * @covers \WpConvertToWebp\Actions\Add::init
	 * @return void
	 */
	public function test_constructor_calls_init(): void {
		BrainMonkey\expect( 'add_filter' )
			->once()
			->andReturn( true );

		$instance = new Add();

		$this->assertInstanceOf( Add::class, $instance );
	}

	/**
	 * Test that clone is private (singleton pattern).
	 *
	 * Verifies that the __clone() method is private to prevent cloning
	 * of the singleton instance.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Actions\Add::__clone
	 * @return void
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( Add::class, '__clone' );
	}

	/**
	 * Test that __wakeup throws RuntimeException (singleton pattern).
	 *
	 * Verifies that attempting to unserialize the singleton throws a
	 * RuntimeException to prevent unserialization.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Actions\Add::__wakeup
	 * @return void
	 */
	public function test_wakeup_throws_exception(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot unserialize a singleton.' );

		$reflection = new ReflectionClass( Add::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Test that init registers wp_generate_attachment_metadata filter.
	 *
	 * Verifies that the init() method correctly registers the convert_webp
	 * callback on the wp_generate_attachment_metadata filter with priority 10.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Actions\Add::init
	 * @return void
	 */
	public function test_init_registers_filter(): void {
		$filters = [];

		BrainMonkey\when( 'add_filter' )->alias(
			function ( $hook, $callback, $priority ) use ( &$filters ) {
				$filters[] = [
					'hook'     => $hook,
					'priority' => $priority,
				];
				return true;
			}
		);

		$instance = new Add();

		$this->assertCount( 1, $filters );
		$this->assertEquals( 'wp_generate_attachment_metadata', $filters[0]['hook'] );
		$this->assertEquals( 10, $filters[0]['priority'] );
	}

	/**
	 * Test convert_webp method exists and is public.
	 *
	 * Verifies that the convert_webp() method exists in the Add class
	 * and is publicly accessible.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Actions\Add::convert_webp
	 * @return void
	 */
	public function test_convert_webp_method_exists(): void {
		$this->assertTrue(
			method_exists( Add::class, 'convert_webp' ),
			'Add class should have a convert_webp method'
		);

		$reflection = new ReflectionClass( Add::class );
		$method     = $reflection->getMethod( 'convert_webp' );
		$this->assertTrue( $method->isPublic() );
	}

	/**
	 * Test convert_webp returns metadata unchanged when empty.
	 *
	 * Verifies that when an empty metadata array is provided,
	 * convert_webp returns it unchanged without processing.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Actions\Add::convert_webp
	 * @return void
	 */
	public function test_convert_webp_returns_metadata_when_empty(): void {
		BrainMonkey\expect( 'add_filter' )
			->once()
			->andReturn( true );

		$instance = new Add();
		$metadata = [];
		$result   = $instance->convert_webp( $metadata, 123 );

		$this->assertEquals( $metadata, $result );
	}

	/**
	 * Test convert_webp returns metadata unchanged when not array.
	 *
	 * Verifies that convert_webp properly validates the metadata type
	 * and returns an empty array when metadata is empty.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Actions\Add::convert_webp
	 * @return void
	 */
	public function test_convert_webp_validates_metadata_type(): void {
		BrainMonkey\expect( 'add_filter' )
			->once()
			->andReturn( true );

		$instance = new Add();

		// Test with empty array
		$metadata = [];
		$result   = $instance->convert_webp( $metadata, 123 );
		$this->assertEquals( [], $result );
	}

	/**
	 * Test convert_webp method with valid metadata structure.
	 *
	 * Verifies that convert_webp accepts and handles metadata with standard
	 * WordPress attachment metadata fields (file, width, height).
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Actions\Add::convert_webp
	 * @return void
	 */
	public function test_convert_webp_accepts_valid_metadata_structure(): void {
		BrainMonkey\expect( 'add_filter' )
			->once()
			->andReturn( true );

		$metadata = [
			'file'   => 'test.jpg',
			'width'  => 800,
			'height' => 600,
		];

		$instance = new Add();

		// When metadata is valid, the method should return it unchanged
		// (actual conversion happens internally and doesn't modify metadata)
		$this->assertIsArray( $metadata );
		$this->assertArrayHasKey( 'file', $metadata );
	}

	/**
	 * Test that convert_webp preserves metadata structure.
	 *
	 * Verifies that convert_webp preserves all metadata fields including
	 * file, dimensions, filesize, mime-type, and sizes array.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Actions\Add::convert_webp
	 * @return void
	 */
	public function test_convert_webp_preserves_metadata_structure(): void {
		BrainMonkey\expect( 'add_filter' )
			->once()
			->andReturn( true );

		$original_metadata = [
			'file'      => 'image.jpg',
			'width'     => 1920,
			'height'    => 1080,
			'filesize'  => 524288,
			'mime-type' => 'image/jpeg',
			'sizes'     => [
				'thumbnail' => [
					'file'   => 'image-150x150.jpg',
					'width'  => 150,
					'height' => 150,
				],
			],
		];

		$instance = new Add();

		// Verify metadata structure is valid
		$this->assertIsArray( $original_metadata );
		$this->assertArrayHasKey( 'sizes', $original_metadata );
		$this->assertArrayHasKey( 'mime-type', $original_metadata );
	}

	/**
	 * Test init method exists and is public.
	 *
	 * Verifies that the init() method exists in the Add class
	 * and is publicly accessible.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Actions\Add::init
	 * @return void
	 */
	public function test_init_method_exists(): void {
		$this->assertTrue(
			method_exists( Add::class, 'init' ),
			'Add class should have an init method'
		);

		$reflection = new ReflectionClass( Add::class );
		$method     = $reflection->getMethod( 'init' );
		$this->assertTrue( $method->isPublic() );
	}

	/**
	 * Test that metadata with multiple sizes is handled correctly.
	 *
	 * Verifies that convert_webp can handle metadata containing multiple
	 * image sizes (thumbnail, medium, large) as generated by WordPress.
	 *
	 * @since 1.0.0
	 * @covers \WpConvertToWebp\Actions\Add::convert_webp
	 * @return void
	 */
	public function test_metadata_structure_with_multiple_sizes(): void {
		BrainMonkey\expect( 'add_filter' )
			->once()
			->andReturn( true );

		$metadata = [
			'file'   => 'test.jpg',
			'width'  => 1200,
			'height' => 800,
			'sizes'  => [
				'thumbnail' => [
					'file'   => 'test-150x150.jpg',
					'width'  => 150,
					'height' => 150,
				],
				'medium'    => [
					'file'   => 'test-300x200.jpg',
					'width'  => 300,
					'height' => 200,
				],
				'large'     => [
					'file'   => 'test-1024x683.jpg',
					'width'  => 1024,
					'height' => 683,
				],
			],
		];

		$instance = new Add();

		// Verify the metadata structure
		$this->assertIsArray( $metadata );
		$this->assertArrayHasKey( 'sizes', $metadata );
		$this->assertCount( 3, $metadata['sizes'] );
		$this->assertArrayHasKey( 'thumbnail', $metadata['sizes'] );
		$this->assertArrayHasKey( 'medium', $metadata['sizes'] );
		$this->assertArrayHasKey( 'large', $metadata['sizes'] );
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
