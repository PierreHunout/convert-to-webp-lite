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

		// Mock WordPress functions
		BrainMonkey\when( 'sanitize_text_field' )->returnArg();
		BrainMonkey\when( 'wp_unslash' )->returnArg();
	}

	/**
	 * Cleanup after each test.
	 */
	protected function tear_down(): void {
		// Clean up $_SERVER variables
		unset( $_SERVER['HTTP_ACCEPT'] );
		unset( $_SERVER['HTTP_USER_AGENT'] );

		parent::tear_down();
	}

	/**
	 * Test browser_support returns true when HTTP_ACCEPT contains image/webp
	 */
	public function test_browser_support_returns_true_with_webp_accept_header(): void {
		$_SERVER['HTTP_ACCEPT'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8';

		$result = Helpers::browser_support();

		$this->assertTrue( $result );
	}

	/**
	 * Test browser_support returns false for Internet Explorer
	 */
	public function test_browser_support_returns_false_for_ie(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml,application/xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko';

		$result = Helpers::browser_support();

		$this->assertFalse( $result );
	}

	/**
	 * Test browser_support returns true for Chrome 90
	 */
	public function test_browser_support_returns_true_for_chrome_90(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Safari/537.36';

		$result = Helpers::browser_support();

		$this->assertTrue( $result );
	}

	/**
	 * Test browser_support returns false for Chrome 31 (below minimum)
	 */
	public function test_browser_support_returns_false_for_chrome_31(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.63 Safari/537.36';

		$result = Helpers::browser_support();

		$this->assertFalse( $result );
	}

	/**
	 * Test browser_support returns true for Firefox 85
	 */
	public function test_browser_support_returns_true_for_firefox_85(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:85.0) Gecko/20100101 Firefox/85.0';

		$result = Helpers::browser_support();

		$this->assertTrue( $result );
	}

	/**
	 * Test browser_support returns false for Firefox 64 (below minimum)
	 */
	public function test_browser_support_returns_false_for_firefox_64(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:64.0) Gecko/20100101 Firefox/64.0';

		$result = Helpers::browser_support();

		$this->assertFalse( $result );
	}

	/**
	 * Test browser_support returns true for Edge 90
	 */
	public function test_browser_support_returns_true_for_edge_90(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Safari/537.36 Edg/90.0.818.51';

		$result = Helpers::browser_support();

		$this->assertTrue( $result );
	}

	/**
	 * Test browser_support returns true for Safari 16
	 */
	public function test_browser_support_returns_true_for_safari_16(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Safari/605.1.15';

		$result = Helpers::browser_support();

		$this->assertTrue( $result );
	}

	/**
	 * Test browser_support returns false for Safari 15 (below minimum)
	 */
	public function test_browser_support_returns_false_for_safari_15(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6 Safari/605.1.15';

		$result = Helpers::browser_support();

		$this->assertFalse( $result );
	}

	/**
	 * Test browser_support returns true for Opera 75
	 */
	public function test_browser_support_returns_true_for_opera_75(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.82 Safari/537.36 OPR/75.0.3969.149';

		$result = Helpers::browser_support();

		$this->assertTrue( $result );
	}

	/**
	 * Test browser_support returns true for Android Browser 5.0
	 */
	public function test_browser_support_returns_true_for_android_5(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; Android 5.0; SM-G900P Build/LRX21T) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/37.0.0.0 Mobile Safari/537.36';

		$result = Helpers::browser_support();

		$this->assertTrue( $result );
	}

	/**
	 * Test browser_support returns true for Samsung Internet 15
	 */
	public function test_browser_support_returns_true_for_samsung_browser(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; Android 11; SAMSUNG SM-G998B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/15.0 Chrome/90.0.4430.210 Mobile Safari/537.36';

		$result = Helpers::browser_support();

		$this->assertTrue( $result );
	}

	/**
	 * Test browser_support returns false for unknown browser
	 */
	public function test_browser_support_returns_false_for_unknown_browser(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'UnknownBot/1.0';

		$result = Helpers::browser_support();

		$this->assertFalse( $result );
	}

	/**
	 * Test browser_support returns false when version is unknown
	 */
	public function test_browser_support_returns_false_when_version_unknown(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome Safari/537.36';

		$result = Helpers::browser_support();

		$this->assertFalse( $result );
	}

	/**
	 * Test browser_support with no HTTP_ACCEPT and no user agent
	 */
	public function test_browser_support_with_no_headers(): void {
		$result = Helpers::browser_support();

		$this->assertFalse( $result );
	}

	/**
	 * Test get_browser returns Chrome info correctly
	 */
	public function test_get_browser_detects_chrome(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Safari/537.36';

		$result = Helpers::get_browser();

		$this->assertEquals( 'Chrome', $result['name'] );
		$this->assertEquals( '90.0.4430.93', $result['version'] );
	}

	/**
	 * Test get_browser returns Firefox info correctly
	 */
	public function test_get_browser_detects_firefox(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:85.0) Gecko/20100101 Firefox/85.0';

		$result = Helpers::get_browser();

		$this->assertEquals( 'Firefox', $result['name'] );
		$this->assertEquals( '85.0', $result['version'] );
	}

	/**
	 * Test get_browser returns Edge info correctly
	 * Note: Modern Edge uses "Edg" in UA, which doesn't match "Edge" check, so it's detected as Chrome
	 */
	public function test_get_browser_detects_edge(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Safari/537.36 Edge/90.0.818.51';

		$result = Helpers::get_browser();

		$this->assertEquals( 'Edge', $result['name'] );
		$this->assertEquals( '90.0.818.51', $result['version'] );
	}

	/**
	 * Test get_browser returns Safari info correctly
	 */
	public function test_get_browser_detects_safari(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Safari/605.1.15';

		$result = Helpers::get_browser();

		$this->assertEquals( 'Safari', $result['name'] );
		$this->assertEquals( '16.0', $result['version'] );
	}

	/**
	 * Test get_browser returns Opera info correctly
	 */
	public function test_get_browser_detects_opera(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.82 Safari/537.36 OPR/75.0.3969.149';

		$result = Helpers::get_browser();

		$this->assertEquals( 'Opera', $result['name'] );
		$this->assertEquals( '75.0.3969.149', $result['version'] );
	}

	/**
	 * Test get_browser returns IE info correctly (Trident)
	 */
	public function test_get_browser_detects_ie_trident(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko';

		$result = Helpers::get_browser();

		$this->assertEquals( 'IE', $result['name'] );
		$this->assertEquals( '11.0', $result['version'] );
	}

	/**
	 * Test get_browser returns IE info correctly (MSIE)
	 */
	public function test_get_browser_detects_ie_msie(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)';

		$result = Helpers::get_browser();

		$this->assertEquals( 'IE', $result['name'] );
		$this->assertEquals( '10.0', $result['version'] );
	}

	/**
	 * Test get_browser returns Samsung Browser info correctly
	 * Using a minimal UA that only contains SamsungBrowser identifier
	 */
	public function test_get_browser_detects_samsung_browser(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; Android 11) SamsungBrowser/15.0';

		$result = Helpers::get_browser();

		$this->assertEquals( 'Samsung', $result['name'] );
		$this->assertEquals( '15.0', $result['version'] );
	}

	/**
	 * Test get_browser returns Android Browser info correctly
	 * Using a minimal UA that only contains Android identifier
	 */
	public function test_get_browser_detects_android_browser(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; Android 5.0; SM-G900P Build/LRX21T)';

		$result = Helpers::get_browser();

		$this->assertEquals( 'Android', $result['name'] );
		$this->assertEquals( '5.0', $result['version'] );
	}

	/**
	 * Test get_browser returns Unknown for unrecognized user agent
	 */
	public function test_get_browser_returns_unknown_for_unrecognized(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'UnknownBot/1.0';

		$result = Helpers::get_browser();

		$this->assertEquals( 'Unknown', $result['name'] );
		$this->assertEquals( '?', $result['version'] );
	}

	/**
	 * Test get_browser returns Unknown when no user agent is set
	 */
	public function test_get_browser_returns_unknown_when_no_user_agent(): void {
		$result = Helpers::get_browser();

		$this->assertEquals( 'Unknown', $result['name'] );
		$this->assertEquals( '?', $result['version'] );
	}

	/**
	 * Test get_browser prioritizes Chrome over Safari in user agent
	 */
	public function test_get_browser_prioritizes_chrome_over_safari(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Safari/537.36';

		$result = Helpers::get_browser();

		$this->assertEquals( 'Chrome', $result['name'] );
		$this->assertEquals( '90.0.4430.93', $result['version'] );
	}

	/**
	 * Test get_browser with empty user agent string
	 */
	public function test_get_browser_with_empty_user_agent(): void {
		$_SERVER['HTTP_USER_AGENT'] = '';

		$result = Helpers::get_browser();

		$this->assertEquals( 'Unknown', $result['name'] );
		$this->assertEquals( '?', $result['version'] );
	}

	/**
	 * Test browser_support edge case: browser matches but no version extracted
	 */
	public function test_browser_support_with_matched_browser_but_no_version(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Firefox';

		$result = Helpers::browser_support();

		$this->assertFalse( $result );
	}

	/**
	 * Test browser_support with major version exactly at minimum
	 */
	public function test_browser_support_with_exact_minimum_version(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1650.63 Safari/537.36';

		$result = Helpers::browser_support();

		$this->assertTrue( $result );
	}
}
