<?php
/**
 * Tests for Settings class
 *
 * @package WpConvertToWebp\Tests
 */

namespace WpConvertToWebp\Tests\Unit\Admin;

use WpConvertToWebp\Tests\TestCase;
use WpConvertToWebp\Admin\Settings;
use Brain\Monkey\Functions as BrainMonkey;
use RuntimeException;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class SettingsTest
 *
 * Tests for Settings class.
 */
class SettingsTest extends TestCase {

	/**
	 * Setup before each test.
	 */
	protected function set_up(): void {
		parent::set_up();

		// Reset singleton instance before each test
		$reflection = new ReflectionClass( Settings::class );
		$instance   = $reflection->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );

		// Reset $_POST superglobal
		$_POST = [];
	}

	/**
	 * Test that clone is private (singleton pattern).
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( Settings::class, '__clone' );
	}

	/**
	 * Test that __wakeup throws RuntimeException (singleton pattern).
	 */
	public function test_wakeup_throws_exception(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot unserialize a singleton.' );

		$reflection = new ReflectionClass( Settings::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Test get_instance returns singleton instance.
	 */
	public function test_get_instance_returns_singleton(): void {
		BrainMonkey\expect( 'add_action' )
			->times( 2 )
			->andReturn( true );

		$instance1 = Settings::get_instance();
		$instance2 = Settings::get_instance();

		$this->assertInstanceOf( Settings::class, $instance1 );
		$this->assertSame( $instance1, $instance2, 'Should return same instance' );
	}

	/**
	 * Test init registers admin actions.
	 */
	public function test_init_registers_admin_actions(): void {
		$hooks = [];

		BrainMonkey\when( 'add_action' )->alias(
			function ( $hook, $callback ) use ( &$hooks ) {
				$hooks[] = $hook;
				return true;
			}
		);

		Settings::init();

		$this->assertContains( 'admin_menu', $hooks );
		$this->assertContains( 'admin_init', $hooks );
		$this->assertCount( 2, $hooks );
	}

	/**
	 * Test add_settings method exists and is public static.
	 */
	public function test_add_settings_method_exists(): void {
		$this->assertTrue(
			method_exists( Settings::class, 'add_settings' ),
			'Settings class should have an add_settings method'
		);

		$reflection = new ReflectionMethod( Settings::class, 'add_settings' );
		$this->assertTrue(
			$reflection->isPublic(),
			'add_settings method should be public'
		);
		$this->assertTrue(
			$reflection->isStatic(),
			'add_settings method should be static'
		);
	}

	/**
	 * Test save_settings method exists and is public static.
	 */
	public function test_save_settings_method_exists(): void {
		$this->assertTrue(
			method_exists( Settings::class, 'save_settings' ),
			'Settings class should have a save_settings method'
		);

		$reflection = new ReflectionMethod( Settings::class, 'save_settings' );
		$this->assertTrue(
			$reflection->isPublic(),
			'save_settings method should be public'
		);
		$this->assertTrue(
			$reflection->isStatic(),
			'save_settings method should be static'
		);
	}

	/**
	 * Test render_page method exists and is public static.
	 */
	public function test_render_page_method_exists(): void {
		$this->assertTrue(
			method_exists( Settings::class, 'render_page' ),
			'Settings class should have a render_page method'
		);

		$reflection = new ReflectionMethod( Settings::class, 'render_page' );
		$this->assertTrue(
			$reflection->isPublic(),
			'render_page method should be public'
		);
		$this->assertTrue(
			$reflection->isStatic(),
			'render_page method should be static'
		);
	}

	/**
	 * Test save_settings returns early when user lacks capability.
	 */
	public function test_save_settings_returns_early_when_user_lacks_capability(): void {
		BrainMonkey\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( false );

		Settings::save_settings();

		// Should return early without calling update_option
		$this->assertTrue( true );
	}

	/**
	 * Test save_settings returns early when action is missing.
	 */
	public function test_save_settings_returns_early_when_action_missing(): void {
		BrainMonkey\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( true );

		// No $_POST['action'] set

		Settings::save_settings();

		// Should return early without calling update_option
		$this->assertTrue( true );
	}

	/**
	 * Test save_settings validates quality bounds minimum.
	 */
	public function test_save_settings_validates_quality_minimum(): void {
		$_POST['action']                  = 'save_options';
		$_POST['convert_to_webp_quality'] = '-10';
		$_POST['_wpnonce']                = 'valid-nonce';

		$updated_quality = null;

		BrainMonkey\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( true );

		BrainMonkey\when( 'sanitize_text_field' )->returnArg();
		BrainMonkey\when( 'wp_unslash' )->returnArg();

		BrainMonkey\expect( 'check_admin_referer' )
			->once()
			->with( 'convert_to_webp_save_options' )
			->andReturn( true );

		BrainMonkey\when( 'update_option' )->alias(
			function ( $key, $value ) use ( &$updated_quality ) {
				if ( $key === 'convert_to_webp_quality' ) {
					$updated_quality = $value;
				}
				return true;
			}
		);

		BrainMonkey\when( 'add_action' )->justReturn( true );

		Settings::save_settings();

		$this->assertEquals( 0, $updated_quality, 'Quality should be clamped to 0' );
	}

	/**
	 * Test save_settings validates quality bounds maximum.
	 */
	public function test_save_settings_validates_quality_maximum(): void {
		$_POST['action']                  = 'save_options';
		$_POST['convert_to_webp_quality'] = '150';
		$_POST['_wpnonce']                = 'valid-nonce';

		$updated_quality = null;

		BrainMonkey\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( true );

		BrainMonkey\when( 'sanitize_text_field' )->returnArg();
		BrainMonkey\when( 'wp_unslash' )->returnArg();

		BrainMonkey\expect( 'check_admin_referer' )
			->once()
			->with( 'convert_to_webp_save_options' )
			->andReturn( true );

		BrainMonkey\when( 'update_option' )->alias(
			function ( $key, $value ) use ( &$updated_quality ) {
				if ( $key === 'convert_to_webp_quality' ) {
					$updated_quality = $value;
				}
				return true;
			}
		);

		BrainMonkey\when( 'add_action' )->justReturn( true );

		Settings::save_settings();

		$this->assertEquals( 100, $updated_quality, 'Quality should be clamped to 100' );
	}

	/**
	 * Test save_settings saves valid quality value.
	 */
	public function test_save_settings_saves_valid_quality(): void {
		$_POST['action']                  = 'save_options';
		$_POST['convert_to_webp_quality'] = '75';
		$_POST['_wpnonce']                = 'valid-nonce';

		$updated_quality = null;

		BrainMonkey\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( true );

		BrainMonkey\when( 'sanitize_text_field' )->returnArg();
		BrainMonkey\when( 'wp_unslash' )->returnArg();

		BrainMonkey\expect( 'check_admin_referer' )
			->once()
			->with( 'convert_to_webp_save_options' )
			->andReturn( true );

		BrainMonkey\when( 'update_option' )->alias(
			function ( $key, $value ) use ( &$updated_quality ) {
				if ( $key === 'convert_to_webp_quality' ) {
					$updated_quality = $value;
				}
				return true;
			}
		);

		BrainMonkey\when( 'add_action' )->justReturn( true );

		Settings::save_settings();

		$this->assertEquals( 75, $updated_quality, 'Quality should be saved as 75' );
	}

	/**
	 * Test save_settings saves replace mode when enabled.
	 */
	public function test_save_settings_saves_replace_mode_enabled(): void {
		$_POST['action']                       = 'save_options';
		$_POST['convert_to_webp_quality']      = '85';
		$_POST['convert_to_webp_replace_mode'] = '1';
		$_POST['_wpnonce']                     = 'valid-nonce';

		$updated_mode = null;

		BrainMonkey\expect( 'current_user_can' )
			->once()
			->andReturn( true );

		BrainMonkey\when( 'sanitize_text_field' )->returnArg();
		BrainMonkey\when( 'wp_unslash' )->returnArg();

		BrainMonkey\expect( 'check_admin_referer' )
			->once()
			->andReturn( true );

		BrainMonkey\when( 'update_option' )->alias(
			function ( $key, $value ) use ( &$updated_mode ) {
				if ( $key === 'convert_to_webp_replace_mode' ) {
					$updated_mode = $value;
				}
				return true;
			}
		);

		BrainMonkey\when( 'add_action' )->justReturn( true );

		Settings::save_settings();

		$this->assertEquals( 1, $updated_mode, 'Replace mode should be enabled' );
	}

	/**
	 * Test save_settings saves replace mode when disabled.
	 */
	public function test_save_settings_saves_replace_mode_disabled(): void {
		$_POST['action']                  = 'save_options';
		$_POST['convert_to_webp_quality'] = '85';
		$_POST['_wpnonce']                = 'valid-nonce';

		$updated_mode = null;

		BrainMonkey\expect( 'current_user_can' )
			->once()
			->andReturn( true );

		BrainMonkey\when( 'sanitize_text_field' )->returnArg();
		BrainMonkey\when( 'wp_unslash' )->returnArg();

		BrainMonkey\expect( 'check_admin_referer' )
			->once()
			->andReturn( true );

		BrainMonkey\when( 'update_option' )->alias(
			function ( $key, $value ) use ( &$updated_mode ) {
				if ( $key === 'convert_to_webp_replace_mode' ) {
					$updated_mode = $value;
				}
				return true;
			}
		);

		BrainMonkey\when( 'add_action' )->justReturn( true );

		Settings::save_settings();

		$this->assertEquals( 0, $updated_mode, 'Replace mode should be disabled' );
	}

	/**
	 * Test save_settings saves all options correctly.
	 */
	public function test_save_settings_saves_all_options(): void {
		$_POST['action']                       = 'save_options';
		$_POST['convert_to_webp_quality']      = '90';
		$_POST['convert_to_webp_replace_mode'] = '1';
		$_POST['delete_webp_on_deactivate']    = '1';
		$_POST['delete_webp_on_uninstall']     = '1';
		$_POST['_wpnonce']                     = 'valid-nonce';

		$saved_options = [];

		BrainMonkey\expect( 'current_user_can' )
			->once()
			->andReturn( true );

		BrainMonkey\when( 'sanitize_text_field' )->returnArg();
		BrainMonkey\when( 'wp_unslash' )->returnArg();

		BrainMonkey\expect( 'check_admin_referer' )
			->once()
			->andReturn( true );

		BrainMonkey\when( 'update_option' )->alias(
			function ( $key, $value ) use ( &$saved_options ) {
				$saved_options[ $key ] = $value;
				return true;
			}
		);

		BrainMonkey\when( 'add_action' )->justReturn( true );

		Settings::save_settings();

		$this->assertEquals( 90, $saved_options['convert_to_webp_quality'] );
		$this->assertEquals( 1, $saved_options['convert_to_webp_replace_mode'] );
		$this->assertEquals( 1, $saved_options['delete_webp_on_deactivate'] );
		$this->assertEquals( 1, $saved_options['delete_webp_on_uninstall'] );
	}

	/**
	 * Test save_settings adds admin notice on success.
	 */
	public function test_save_settings_adds_admin_notice_on_success(): void {
		$_POST['action']                  = 'save_options';
		$_POST['convert_to_webp_quality'] = '85';
		$_POST['_wpnonce']                = 'valid-nonce';

		$notice_added = false;

		BrainMonkey\expect( 'current_user_can' )
			->once()
			->andReturn( true );

		BrainMonkey\when( 'sanitize_text_field' )->returnArg();
		BrainMonkey\when( 'wp_unslash' )->returnArg();

		BrainMonkey\expect( 'check_admin_referer' )
			->once()
			->andReturn( true );

		BrainMonkey\when( 'update_option' )->justReturn( true );

		BrainMonkey\when( 'add_action' )->alias(
			function ( $hook, $callback ) use ( &$notice_added ) {
				if ( $hook === 'admin_notices' ) {
					$notice_added = true;
				}
				return true;
			}
		);

		Settings::save_settings();

		$this->assertTrue( $notice_added, 'Should add admin notice on successful save' );
	}

	/**
	 * Test instance property exists and is nullable.
	 */
	public function test_instance_property_exists(): void {
		$reflection = new ReflectionClass( Settings::class );
		$this->assertTrue(
			$reflection->hasProperty( 'instance' ),
			'Settings class should have an instance property'
		);

		$property = $reflection->getProperty( 'instance' );
		$this->assertTrue(
			$property->isStatic(),
			'instance property should be static'
		);
		$this->assertTrue(
			$property->isProtected(),
			'instance property should be protected'
		);
	}

	/**
	 * Test constructor calls init method.
	 */
	public function test_constructor_calls_init(): void {
		BrainMonkey\expect( 'add_action' )
			->times( 2 )
			->andReturn( true );

		$instance = new Settings();

		$this->assertInstanceOf( Settings::class, $instance );
	}

	/**
	 * Test add_settings registers menu page.
	 */
	public function test_add_settings_registers_menu_page(): void {
		$menu_registered = false;

		BrainMonkey\expect( '__' )
			->twice()
			->andReturnUsing(
				function ( $text ) {
					return $text;
				}
			);

		BrainMonkey\when( 'add_menu_page' )->alias(
			function ( $page_title, $menu_title, $capability, $menu_slug, $callback, $icon, $position ) use ( &$menu_registered ) {
				$menu_registered = (
					$page_title === 'WebP Conversion' &&
					$menu_title === 'WebP Conversion' &&
					$capability === 'manage_options' &&
					$menu_slug === 'wp-convert-to-webp' &&
					$callback === [ Settings::class, 'render_page' ] &&
					$icon === 'dashicons-images-alt2' &&
					$position === 99
				);
				return true;
			}
		);

		Settings::add_settings();

		$this->assertTrue( $menu_registered, 'Should register menu page with correct parameters' );
	}

	/**
	 * Test init hooks callbacks correctly.
	 */
	public function test_init_hooks_callbacks_correctly(): void {
		$callbacks = [];

		BrainMonkey\when( 'add_action' )->alias(
			function ( $hook, $callback ) use ( &$callbacks ) {
				$callbacks[ $hook ] = $callback;
				return true;
			}
		);

		Settings::init();

		$this->assertEquals( [ Settings::class, 'add_settings' ], $callbacks['admin_menu'] );
		$this->assertEquals( [ Settings::class, 'save_settings' ], $callbacks['admin_init'] );
	}

	/**
	 * Test get_instance creates instance on first call.
	 */
	public function test_get_instance_creates_instance_on_first_call(): void {
		BrainMonkey\expect( 'add_action' )
			->times( 2 )
			->andReturn( true );

		// Verify instance is null before first call
		$reflection = new ReflectionClass( Settings::class );
		$property   = $reflection->getProperty( 'instance' );
		$property->setAccessible( true );
		$this->assertNull( $property->getValue() );

		// Get instance
		$instance = Settings::get_instance();

		// Verify instance is no longer null
		$this->assertNotNull( $property->getValue() );
		$this->assertInstanceOf( Settings::class, $instance );
	}

	/**
	 * Test save_settings uses default quality when not provided.
	 */
	public function test_save_settings_uses_default_quality_when_not_provided(): void {
		$_POST['action']   = 'save_options';
		$_POST['_wpnonce'] = 'valid-nonce';

		$updated_quality = null;

		BrainMonkey\expect( 'current_user_can' )
			->once()
			->andReturn( true );

		BrainMonkey\when( 'sanitize_text_field' )->returnArg();
		BrainMonkey\when( 'wp_unslash' )->returnArg();

		BrainMonkey\expect( 'check_admin_referer' )
			->once()
			->andReturn( true );

		BrainMonkey\when( 'update_option' )->alias(
			function ( $key, $value ) use ( &$updated_quality ) {
				if ( $key === 'convert_to_webp_quality' ) {
					$updated_quality = $value;
				}
				return true;
			}
		);

		BrainMonkey\when( 'add_action' )->justReturn( true );

		Settings::save_settings();

		$this->assertEquals( 85, $updated_quality, 'Quality should default to 85' );
	}

	/**
	 * Cleanup after each test.
	 */
	protected function tear_down(): void {
		unset( $_POST );
		parent::tear_down();
	}
}
