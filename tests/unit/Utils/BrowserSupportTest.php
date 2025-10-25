<?php
/**
 * Tests for browser WebP support detection
 *
 * @package WpConvertToWebp\Tests
 */

namespace WpConvertToWebp\Tests\Unit\Utils;

use WpConvertToWebp\Utils\Helpers;
use WpConvertToWebp\Tests\TestCase;
use Brain\Monkey\Functions as BrainMonkey;

/**
 * Class BrowserSupportTest
 *
 * Tests for Helpers::browser_support() & Helpers::get_browser() methods.
 */
class BrowserSupportTest extends TestCase {

	/**
	 * Setup before each test.
	 */
	protected function set_up(): void {
		parent::set_up();
		
		// Mock WordPress sanitization functions
		BrainMonkey\when('sanitize_text_field')->returnArg();
		BrainMonkey\when('wp_unslash')->returnArg();
	}

	/**
	 * Test browser_support with WebP in HTTP_ACCEPT.
	 *
	 * @test
	 */
	public function test_browser_support_with_webp_accept_header(): void {
		$_SERVER['HTTP_ACCEPT'] = 'text/html,image/webp,image/apng,*/*';
		
		$result = Helpers::browser_support();

		$this->assertTrue( $result );
	}

	/**
	 * Test browser_support without WebP in HTTP_ACCEPT.
	 *
	 * @test
	 */
	public function test_browser_support_without_webp_accept_header(): void {
		$_SERVER['HTTP_ACCEPT'] = 'text/html,image/png,image/jpeg,*/*';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)';
		
		$result = Helpers::browser_support();

		$this->assertFalse( $result );
	}

	/**
	 * Test browser_support with Chrome (supports WebP).
	 *
	 * @test
	 */
	public function test_browser_support_with_chrome(): void {
		unset( $_SERVER['HTTP_ACCEPT'] );
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
		
		$result = Helpers::browser_support();

		$this->assertTrue( $result );
	}

	/**
	 * Test browser_support with old Chrome (no WebP support).
	 *
	 * @test
	 */
	public function test_browser_support_with_old_chrome(): void {
		unset( $_SERVER['HTTP_ACCEPT'] );
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.95 Safari/537.36';
		
		$result = Helpers::browser_support();

		$this->assertFalse( $result );
	}

	/**
	 * Test browser_support with Firefox (supports WebP).
	 *
	 * @test
	 */
	public function test_browser_support_with_modern_firefox(): void {
		unset( $_SERVER['HTTP_ACCEPT'] );
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0';
		
		$result = Helpers::browser_support();

		$this->assertTrue( $result );
	}

	/**
	 * Test browser_support with old Firefox (no WebP support).
	 *
	 * @test
	 */
	public function test_browser_support_with_old_firefox(): void {
		unset( $_SERVER['HTTP_ACCEPT'] );
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:60.0) Gecko/20100101 Firefox/60.0';
		
		$result = Helpers::browser_support();

		$this->assertFalse( $result );
	}

	/**
	 * Test browser_support with Safari 16+ (supports WebP).
	 *
	 * @test
	 */
	public function test_browser_support_with_modern_safari(): void {
		unset( $_SERVER['HTTP_ACCEPT'] );
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Safari/605.1.15';
		
		$result = Helpers::browser_support();

		$this->assertTrue( $result );
	}

	/**
	 * Test browser_support with old Safari (no WebP support).
	 *
	 * @test
	 */
	public function test_browser_support_with_old_safari(): void {
		unset( $_SERVER['HTTP_ACCEPT'] );
		// Safari 14 does not support WebP (needs 16+)
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Safari/605.1.15';
		
		$result = Helpers::browser_support();

		$this->assertFalse( $result );
	}

	/**
	 * Test browser_support with Internet Explorer (never supports WebP).
	 *
	 * @test
	 */
	public function test_browser_support_with_internet_explorer(): void {
		unset( $_SERVER['HTTP_ACCEPT'] );
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko';
		
		$result = Helpers::browser_support();

		$this->assertFalse( $result );
	}

	/**
	 * Test browser_support with Edge (supports WebP).
	 *
	 * @test
	 */
	public function test_browser_support_with_modern_edge(): void {
		unset( $_SERVER['HTTP_ACCEPT'] );
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36 Edge/91.0.864.59';
		
		$result = Helpers::browser_support();

		$this->assertTrue( $result );
	}

	/**
	 * Test browser_support with Opera (supports WebP).
	 *
	 * @test
	 */
	public function test_browser_support_with_opera(): void {
		unset( $_SERVER['HTTP_ACCEPT'] );
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36 OPR/77.0.4054.203';
		
		$result = Helpers::browser_support();

		$this->assertTrue( $result );
	}

	/**
	 * Test browser_support with unknown browser version.
	 *
	 * @test
	 */
	public function test_browser_support_with_unknown_version(): void {
		unset( $_SERVER['HTTP_ACCEPT'] );
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Chrome';
		
		$result = Helpers::browser_support();

		// Should return false when version is unknown
		$this->assertFalse( $result );
	}

	/**
	 * Cleanup after each test.
	 */
	protected function tear_down(): void {
		$this->unsetServerVars( [ 'HTTP_USER_AGENT', 'HTTP_ACCEPT' ] );
		parent::tear_down();
	}
}
