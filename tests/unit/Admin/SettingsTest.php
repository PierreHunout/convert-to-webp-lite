<?php
/**
 * Tests for Settings class
 *
 * @package PoetryConvertToWebp\Tests
 */

namespace PoetryConvertToWebp\Tests\Unit\Admin;

use PoetryConvertToWebp\Tests\TestCase;
use PoetryConvertToWebp\Admin\Settings;
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
	 * Initializes the test environment before each test method.
	 *
	 * Sets up the parent test case environment, resets the singleton instance,
	 * and clears the $_POST superglobal for clean test state.
	 *
	 * @since 1.0.0
	 * @return void
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
	 *
	 * Verifies that the __clone() method is private to prevent cloning
	 * of the singleton instance.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\Settings::__clone
	 * @return void
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( Settings::class, '__clone' );
	}

	/**
	 * Test that __wakeup throws RuntimeException (singleton pattern).
	 *
	 * Verifies that attempting to unserialize the singleton instance throws a
	 * RuntimeException to prevent unserialization.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\Settings::__wakeup
	 * @return void
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
	 *
	 * Verifies that the get_instance() method creates a singleton instance on first call
	 * and returns the same instance on subsequent calls.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\Settings::get_instance
	 * @return void
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
	 *
	 * Verifies that the init() method registers admin_menu and admin_init
	 * WordPress action hooks.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\Settings::init
	 * @return void
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
	 *
	 * Verifies that the add_settings() method exists, is public and static,
	 * allowing it to be called as an action hook callback.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\Settings::add_settings
	 * @return void
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
	 *
	 * Verifies that the save_settings() method exists, is public and static,
	 * allowing it to be called as an action hook callback.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\Settings::save_settings
	 * @return void
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
	 *
	 * Verifies that the render_page() method exists, is public and static,
	 * allowing it to be called as a menu page callback.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\Settings::render_page
	 * @return void
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
	 *
	 * Verifies that the save_settings() method returns early without saving
	 * when the current user does not have 'manage_options' capability.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\Settings::save_settings
	 * @return void
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
	 *
	 * Verifies that the save_settings() method returns early without saving
	 * when the $_POST['action'] parameter is not set.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\Settings::save_settings
	 * @return void
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
	 *
	 * Verifies that the save_settings() method clamps quality values below 0
	 * to the minimum allowed value of 0.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\Settings::save_settings
	 * @return void
	 */
	public function test_save_settings_validates_quality_minimum(): void {
		$_POST['action']                  = 'save_options';
		$_POST['poetry_convert_to_webp_quality'] = '-10';
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
			->with( 'poetry_convert_to_webp_save_options' )
			->andReturn( true );

		BrainMonkey\when( 'update_option' )->alias(
			function ( $key, $value ) use ( &$updated_quality ) {
				if ( $key === 'poetry_convert_to_webp_quality' ) {
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
	 *
	 * Verifies that the save_settings() method clamps quality values above 100
	 * to the maximum allowed value of 100.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\Settings::save_settings
	 * @return void
	 */
	public function test_save_settings_validates_quality_maximum(): void {
		$_POST['action']                  = 'save_options';
		$_POST['poetry_convert_to_webp_quality'] = '150';
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
			->with( 'poetry_convert_to_webp_save_options' )
			->andReturn( true );

		BrainMonkey\when( 'update_option' )->alias(
			function ( $key, $value ) use ( &$updated_quality ) {
				if ( $key === 'poetry_convert_to_webp_quality' ) {
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
	 *
	 * Verifies that the save_settings() method correctly saves a valid quality value
	 * within the allowed range (0-100).
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\Settings::save_settings
	 * @return void
	 */
	public function test_save_settings_saves_valid_quality(): void {
		$_POST['action']                  = 'save_options';
		$_POST['poetry_convert_to_webp_quality'] = '75';
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
			->with( 'poetry_convert_to_webp_save_options' )
			->andReturn( true );

		BrainMonkey\when( 'update_option' )->alias(
			function ( $key, $value ) use ( &$updated_quality ) {
				if ( $key === 'poetry_convert_to_webp_quality' ) {
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
	 *
	 * Verifies that the save_settings() method correctly saves the replace mode
	 * option when it is enabled (value = 1).
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\Settings::save_settings
	 * @return void
	 */
	public function test_save_settings_saves_replace_mode_enabled(): void {
		$_POST['action']                       = 'save_options';
		$_POST['poetry_convert_to_webp_quality']      = '85';
		$_POST['poetry_convert_to_webp_replace_mode'] = '1';
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
				if ( $key === 'poetry_convert_to_webp_replace_mode' ) {
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
	 *
	 * Verifies that the save_settings() method correctly saves the replace mode
	 * option when it is disabled (value = 0).
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\Settings::save_settings
	 * @return void
	 */
	public function test_save_settings_saves_replace_mode_disabled(): void {
		$_POST['action']                  = 'save_options';
		$_POST['poetry_convert_to_webp_quality'] = '85';
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
				if ( $key === 'poetry_convert_to_webp_replace_mode' ) {
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
	 *
	 * Verifies that the save_settings() method correctly saves all plugin options
	 * including quality, replace mode, deactivate and uninstall settings.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\Settings::save_settings
	 * @return void
	 */
	public function test_save_settings_saves_all_options(): void {
		$_POST['action']                       = 'save_options';
		$_POST['poetry_convert_to_webp_quality']      = '90';
		$_POST['poetry_convert_to_webp_replace_mode'] = '1';
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

		$this->assertEquals( 90, $saved_options['poetry_convert_to_webp_quality'] );
		$this->assertEquals( 1, $saved_options['poetry_convert_to_webp_replace_mode'] );
		$this->assertEquals( 1, $saved_options['delete_webp_on_deactivate'] );
		$this->assertEquals( 1, $saved_options['delete_webp_on_uninstall'] );
	}

	/**
	 * Test save_settings adds admin notice on success.
	 *
	 * Verifies that the save_settings() method adds an admin_notices action hook
	 * after successfully saving settings.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\Settings::save_settings
	 * @return void
	 */
	public function test_save_settings_adds_admin_notice_on_success(): void {
		$_POST['action']                  = 'save_options';
		$_POST['poetry_convert_to_webp_quality'] = '85';
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
	 *
	 * Verifies that the Settings class has a static protected instance property
	 * for storing the singleton instance.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\Settings
	 * @return void
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
	 *
	 * Verifies that when the Settings class is instantiated, the init() method
	 * is called to register WordPress action hooks.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\Settings::__construct
	 * @covers \PoetryConvertToWebp\Admin\Settings::init
	 * @return void
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
	 *
	 * Verifies that the add_settings() method registers the WordPress admin menu page
	 * with correct parameters including title, capability, slug, and icon.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\Settings::add_settings
	 * @return void
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
					$menu_slug === 'poetry-convert-to-webp' &&
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
	 *
	 * Verifies that the init() method registers action hooks with the correct
	 * callbacks for admin_menu and admin_init.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\Settings::init
	 * @return void
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
	 *
	 * Verifies that the first call to get_instance() creates a new instance
	 * and stores it in the static instance property.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\Settings::get_instance
	 * @return void
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
	 *
	 * Verifies that the save_settings() method uses the default quality value of 85
	 * when no quality parameter is provided in the POST data.
	 *
	 * @since 1.0.0
	 * @covers \PoetryConvertToWebp\Admin\Settings::save_settings
	 * @return void
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
				if ( $key === 'poetry_convert_to_webp_quality' ) {
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
	 * Performs cleanup operations after each test method completes.
	 *
	 * Tears down the test environment by clearing the $_POST superglobal
	 * and calling the parent tear_down method to clean up hooks and mocks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function tear_down(): void {
		unset( $_POST );
		parent::tear_down();
	}
}
