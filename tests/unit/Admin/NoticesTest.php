<?php
/**
 * Tests for Notices class
 *
 * @package WpConvertToWebp\Tests
 */

namespace WpConvertToWebp\Tests\Unit\Admin;

use WpConvertToWebp\Tests\TestCase;
use WpConvertToWebp\Admin\Notices;
use Brain\Monkey\Functions as BrainMonkey;
use RuntimeException;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class NoticesTest
 *
 * Tests for Notices class.
 */
class NoticesTest extends TestCase {

	/**
	 * Setup before each test.
	 */
	protected function set_up(): void {
		parent::set_up();

		// Reset singleton instance before each test
		$reflection = new ReflectionClass( Notices::class );
		$instance   = $reflection->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );

		// Reset $_GET superglobal
		$_GET = [];
	}

	/**
	 * Test that clone is private (singleton pattern).
	 */
	public function test_clone_is_private(): void {
		$this->assertMethodIsPrivate( Notices::class, '__clone' );
	}

	/**
	 * Test that __wakeup throws RuntimeException (singleton pattern).
	 */
	public function test_wakeup_throws_exception(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot unserialize a singleton.' );

		$reflection = new ReflectionClass( Notices::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$instance->__wakeup();
	}

	/**
	 * Test get_instance returns singleton instance.
	 */
	public function test_get_instance_returns_singleton(): void {
		BrainMonkey\expect( 'add_action' )
			->once()
			->andReturn( true );

		$instance1 = Notices::get_instance();
		$instance2 = Notices::get_instance();

		$this->assertInstanceOf( Notices::class, $instance1 );
		$this->assertSame( $instance1, $instance2, 'Should return same instance' );
	}

	/**
	 * Test init registers admin_notices action.
	 */
	public function test_init_registers_admin_notices_action(): void {
		$hook_count = 0;

		BrainMonkey\when( 'add_action' )->alias(
			function ( $hook, $callback ) use ( &$hook_count ) {
				if ( $hook === 'admin_notices' && $callback === [ Notices::class, 'display_notices' ] ) {
					$hook_count++;
				}
				return true;
			}
		);

		Notices::init();

		$this->assertEquals( 1, $hook_count, 'Should register admin_notices action exactly once' );
	}

	/**
	 * Test display_notices method exists and is public static.
	 */
	public function test_display_notices_method_exists(): void {
		$this->assertTrue(
			method_exists( Notices::class, 'display_notices' ),
			'Notices class should have a display_notices method'
		);

		$reflection = new ReflectionMethod( Notices::class, 'display_notices' );
		$this->assertTrue(
			$reflection->isPublic(),
			'display_notices method should be public'
		);
		$this->assertTrue(
			$reflection->isStatic(),
			'display_notices method should be static'
		);
	}

	/**
	 * Test display_notices returns early when not on plugin page.
	 */
	public function test_display_notices_returns_early_when_not_on_plugin_page(): void {
		$_GET['page'] = 'other-page';

		BrainMonkey\when( 'sanitize_text_field' )->returnArg();
		BrainMonkey\when( 'wp_unslash' )->returnArg();

		// Should return early without calling any output functions
		ob_start();
		Notices::display_notices();
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Should not output anything when not on plugin page' );
	}

	/**
	 * Test display_notices returns early when page param is missing.
	 */
	public function test_display_notices_returns_early_when_page_param_missing(): void {
		// No $_GET['page'] set

		// Should return early without calling any functions
		ob_start();
		Notices::display_notices();
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Should not output anything when page param is missing' );
	}

	/**
	 * Test display_notices returns early when user lacks capability.
	 */
	public function test_display_notices_returns_early_when_user_lacks_capability(): void {
		$_GET['page'] = 'wp-convert-to-webp';

		BrainMonkey\when( 'sanitize_text_field' )->returnArg();
		BrainMonkey\when( 'wp_unslash' )->returnArg();

		BrainMonkey\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( false );

		ob_start();
		Notices::display_notices();
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Should not output anything when user lacks capability' );
	}

	/**
	 * Test display_notices returns early when nonce verification fails for no_files.
	 */
	public function test_display_notices_returns_early_when_nonce_fails_for_no_files(): void {
		$_GET['page']     = 'wp-convert-to-webp';
		$_GET['no_files'] = '1';
		$_GET['_wpnonce'] = 'invalid-nonce';

		BrainMonkey\when( 'sanitize_text_field' )->returnArg();
		BrainMonkey\when( 'wp_unslash' )->returnArg();

		BrainMonkey\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( true );

		BrainMonkey\expect( 'wp_verify_nonce' )
			->once()
			->with( 'invalid-nonce', 'wp_convert_to_webp_notice' )
			->andReturn( false );

		ob_start();
		Notices::display_notices();
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Should not output anything when nonce verification fails' );
	}

	/**
	 * Test display_notices shows no files notice.
	 */
	public function test_display_notices_shows_no_files_notice(): void {
		$_GET['page']     = 'wp-convert-to-webp';
		$_GET['no_files'] = '1';
		$_GET['_wpnonce'] = 'valid-nonce';

		BrainMonkey\when( 'sanitize_text_field' )->returnArg();
		BrainMonkey\when( 'wp_unslash' )->returnArg();

		BrainMonkey\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( true );

		BrainMonkey\expect( 'wp_verify_nonce' )
			->once()
			->with( 'valid-nonce', 'wp_convert_to_webp_notice' )
			->andReturn( true );

		BrainMonkey\expect( 'esc_html__' )
			->once()
			->with( 'No files found to process.', 'wp-convert-to-webp' )
			->andReturn( 'No files found to process.' );

		BrainMonkey\expect( 'esc_html' )
			->once()
			->andReturnFirstArg();

		ob_start();
		Notices::display_notices();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'convert-to-webp__notice--nofiles', $output );
		$this->assertStringContainsString( 'No files found to process.', $output );
	}

	/**
	 * Test display_notices shows deletion notice with data.
	 */
	public function test_display_notices_shows_deletion_notice_with_data(): void {
		$_GET['page']     = 'wp-convert-to-webp';
		$_GET['deleted']  = '1';
		$_GET['_wpnonce'] = 'valid-nonce';

		$deletion_data = [
			[
				[
					'message' => 'File deleted successfully',
					'classes' => [ 'success' ],
				],
			],
		];

		BrainMonkey\when( 'sanitize_text_field' )->returnArg();
		BrainMonkey\when( 'wp_unslash' )->returnArg();

		BrainMonkey\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( true );

		BrainMonkey\expect( 'wp_verify_nonce' )
			->once()
			->with( 'valid-nonce', 'wp_convert_to_webp_notice' )
			->andReturn( true );

		BrainMonkey\expect( 'esc_html__' )
			->times( 2 )
			->andReturnUsing(
				function ( $text ) {
					return $text;
				}
			);

		BrainMonkey\expect( 'get_transient' )
			->once()
			->with( 'wp_convert_to_webp_deletion_data' )
			->andReturn( $deletion_data );

		BrainMonkey\expect( 'delete_transient' )
			->once()
			->with( 'wp_convert_to_webp_deletion_data' )
			->andReturn( true );

		BrainMonkey\when( 'esc_html' )->returnArg();
		BrainMonkey\when( 'sanitize_html_class' )->returnArg();
		BrainMonkey\when( 'esc_attr' )->returnArg();
		BrainMonkey\when( 'wp_kses' )->returnArg();

		ob_start();
		Notices::display_notices();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'convert-to-webp__notice--deletion', $output );
		$this->assertStringContainsString( 'Deleted WebP files', $output );
		$this->assertStringContainsString( 'File deleted successfully', $output );
		$this->assertStringContainsString( 'convert-to-webp__message--success', $output );
	}

	/**
	 * Test display_notices handles empty deletion data array.
	 */
	public function test_display_notices_handles_empty_deletion_data(): void {
		$_GET['page']     = 'wp-convert-to-webp';
		$_GET['deleted']  = '1';
		$_GET['_wpnonce'] = 'valid-nonce';

		BrainMonkey\when( 'sanitize_text_field' )->returnArg();
		BrainMonkey\when( 'wp_unslash' )->returnArg();

		BrainMonkey\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( true );

		BrainMonkey\expect( 'wp_verify_nonce' )
			->once()
			->with( 'valid-nonce', 'wp_convert_to_webp_notice' )
			->andReturn( true );

		BrainMonkey\expect( 'esc_html__' )
			->once()
			->with( 'No files found to process.', 'wp-convert-to-webp' )
			->andReturn( 'No files found to process.' );

		BrainMonkey\when( 'esc_html' )->returnArg();

		BrainMonkey\expect( 'esc_html__' )
			->once()
			->with( 'Deleted WebP files', 'wp-convert-to-webp' )
			->andReturn( 'Deleted WebP files' );

		BrainMonkey\expect( 'get_transient' )
			->once()
			->with( 'wp_convert_to_webp_deletion_data' )
			->andReturn( [] ); // Empty array

		BrainMonkey\expect( 'delete_transient' )
			->once()
			->with( 'wp_convert_to_webp_deletion_data' )
			->andReturn( true );

		ob_start();
		Notices::display_notices();
		$output = ob_get_clean();

		// Empty array still displays notice with count of 0
		$this->assertStringContainsString( 'convert-to-webp__notice--deletion', $output );
		$this->assertStringContainsString( 'Deleted WebP files', $output );
		$this->assertStringContainsString( '<strong>0</strong>', $output );
	}

	/**
	 * Test display_notices formats multiple messages correctly.
	 */
	public function test_display_notices_formats_multiple_messages_correctly(): void {
		$_GET['page']     = 'wp-convert-to-webp';
		$_GET['deleted']  = '1';
		$_GET['_wpnonce'] = 'valid-nonce';

		$deletion_data = [
			[
				[
					'message' => 'File 1 deleted',
					'classes' => [ 'success' ],
				],
				[
					'message' => 'File 2 failed',
					'classes' => [ 'error', 'critical' ],
				],
			],
		];

		BrainMonkey\when( 'sanitize_text_field' )->returnArg();
		BrainMonkey\when( 'wp_unslash' )->returnArg();

		BrainMonkey\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( true );

		BrainMonkey\expect( 'wp_verify_nonce' )
			->once()
			->andReturn( true );

		BrainMonkey\expect( 'esc_html__' )
			->times( 2 )
			->andReturnUsing(
				function ( $text ) {
					return $text;
				}
			);

		BrainMonkey\expect( 'get_transient' )
			->once()
			->andReturn( $deletion_data );

		BrainMonkey\expect( 'delete_transient' )
			->once()
			->andReturn( true );

		BrainMonkey\when( 'esc_html' )->returnArg();
		BrainMonkey\when( 'sanitize_html_class' )->returnArg();
		BrainMonkey\when( 'esc_attr' )->returnArg();
		BrainMonkey\when( 'wp_kses' )->returnArg();

		ob_start();
		Notices::display_notices();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'File 1 deleted', $output );
		$this->assertStringContainsString( 'File 2 failed', $output );
		$this->assertStringContainsString( 'convert-to-webp__message--success', $output );
		$this->assertStringContainsString( 'convert-to-webp__message--error', $output );
		$this->assertStringContainsString( 'convert-to-webp__message--critical', $output );
	}

	/**
	 * Test display_notices sanitizes HTML classes.
	 */
	public function test_display_notices_sanitizes_html_classes(): void {
		$_GET['page']     = 'wp-convert-to-webp';
		$_GET['deleted']  = '1';
		$_GET['_wpnonce'] = 'valid-nonce';

		$deletion_data = [
			[
				[
					'message' => 'Test message',
					'classes' => [ 'success-class' ],
				],
			],
		];

		BrainMonkey\when( 'sanitize_text_field' )->returnArg();
		BrainMonkey\when( 'wp_unslash' )->returnArg();

		BrainMonkey\expect( 'current_user_can' )
			->once()
			->andReturn( true );

		BrainMonkey\expect( 'wp_verify_nonce' )
			->once()
			->andReturn( true );

		BrainMonkey\expect( 'esc_html__' )
			->times( 2 )
			->andReturnUsing(
				function ( $text ) {
					return $text;
				}
			);

		BrainMonkey\expect( 'get_transient' )
			->once()
			->andReturn( $deletion_data );

		BrainMonkey\expect( 'delete_transient' )
			->once()
			->andReturn( true );

		BrainMonkey\when( 'esc_html' )->returnArg();

		BrainMonkey\expect( 'sanitize_html_class' )
			->once()
			->with( 'success-class' )
			->andReturn( 'success-class' );

		BrainMonkey\when( 'esc_attr' )->returnArg();
		BrainMonkey\when( 'wp_kses' )->returnArg();

		ob_start();
		Notices::display_notices();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'convert-to-webp__message--success-class', $output );
	}

	/**
	 * Test display_notices uses wp_kses for message output.
	 */
	public function test_display_notices_uses_wp_kses_for_message_output(): void {
		$_GET['page']     = 'wp-convert-to-webp';
		$_GET['deleted']  = '1';
		$_GET['_wpnonce'] = 'valid-nonce';

		$deletion_data = [
			[
				[
					'message' => '<span>Safe message</span>',
					'classes' => [ 'success' ],
				],
			],
		];

		BrainMonkey\when( 'sanitize_text_field' )->returnArg();
		BrainMonkey\when( 'wp_unslash' )->returnArg();

		BrainMonkey\expect( 'current_user_can' )
			->once()
			->andReturn( true );

		BrainMonkey\expect( 'wp_verify_nonce' )
			->once()
			->andReturn( true );

		BrainMonkey\expect( 'esc_html__' )
			->times( 2 )
			->andReturnUsing(
				function ( $text ) {
					return $text;
				}
			);

		BrainMonkey\expect( 'get_transient' )
			->once()
			->andReturn( $deletion_data );

		BrainMonkey\expect( 'delete_transient' )
			->once()
			->andReturn( true );

		BrainMonkey\when( 'esc_html' )->returnArg();
		BrainMonkey\when( 'sanitize_html_class' )->returnArg();
		BrainMonkey\when( 'esc_attr' )->returnArg();

		BrainMonkey\expect( 'wp_kses' )
			->once()
			->with( '<span>Safe message</span>', [ 'span' => [] ] )
			->andReturn( '<span>Safe message</span>' );

		ob_start();
		Notices::display_notices();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<span>Safe message</span>', $output );
	}

	/**
	 * Test instance property exists and is nullable.
	 */
	public function test_instance_property_exists(): void {
		$reflection = new ReflectionClass( Notices::class );
		$this->assertTrue(
			$reflection->hasProperty( 'instance' ),
			'Notices class should have an instance property'
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
			->once()
			->andReturn( true );

		$instance = new Notices();

		$this->assertInstanceOf( Notices::class, $instance );
	}

	/**
	 * Cleanup after each test.
	 */
	protected function tear_down(): void {
		unset( $_GET );
		parent::tear_down();
	}
}
