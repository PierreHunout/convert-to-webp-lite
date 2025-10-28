<?php
/**
 * Tests for browser WebP support detection
 *
 * @package ConvertToWebpLite\Tests
 */

namespace ConvertToWebpLite\Tests\Unit\Utils;

use ConvertToWebpLite\Utils\Helpers;
use ConvertToWebpLite\Tests\TestCase;
use Brain\Monkey\Functions as BrainMonkey;

/**
 * Class BrowserSupportTest
 *
 * Tests for Helpers::browser_support() & Helpers::get_browser() methods.
 */
class BrowserSupportTest extends TestCase {

	/**
	 * Initializes the test environment before each test method.
	 *
	 * Sets up the parent test case environment and mocks WordPress sanitization
	 * functions for testing browser support detection.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function set_up(): void {
		parent::set_up();

		// Mock WordPress functions
		BrainMonkey\when( 'sanitize_text_field' )->returnArg();
		BrainMonkey\when( 'wp_unslash' )->returnArg();
	}

	/**
	 * Performs cleanup operations after each test method completes.
	 *
	 * Tears down the test environment by cleaning up $_SERVER variables
	 * and calling the parent tear_down method to clean up hooks and mocks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function tear_down(): void {
		// Clean up $_SERVER variables
		unset( $_SERVER['HTTP_ACCEPT'] );
		unset( $_SERVER['HTTP_USER_AGENT'] );

		parent::tear_down();
	}

	/**
	 * Tests that browser_support method detects WebP support via HTTP_ACCEPT header.
	 *
	 * Verifies that browser_support returns true when the HTTP_ACCEPT header
	 * contains 'image/webp', regardless of user agent.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::browser_support
	 * @return void
	 */
	public function test_browser_support_returns_true_with_webp_accept_header(): void {
		$_SERVER['HTTP_ACCEPT'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8';

		$result = Helpers::browser_support();

		$this->assertTrue( $result );
	}

	/**
	 * Tests that browser_support method rejects Internet Explorer.
	 *
	 * Verifies that browser_support returns false for any version of
	 * Internet Explorer, as IE does not support WebP format.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::browser_support
	 * @return void
	 */
	public function test_browser_support_returns_false_for_ie(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml,application/xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko';

		$result = Helpers::browser_support();

		$this->assertFalse( $result );
	}

	/**
	 * Tests that browser_support method detects WebP support in Chrome 90.
	 *
	 * Verifies that browser_support returns true for Chrome version 90,
	 * which is well above the minimum version 32 required for WebP support.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::browser_support
	 * @return void
	 */
	public function test_browser_support_returns_true_for_chrome_90(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Safari/537.36';

		$result = Helpers::browser_support();

		$this->assertTrue( $result );
	}

	/**
	 * Tests that browser_support method rejects Chrome 31.
	 *
	 * Verifies that browser_support returns false for Chrome version 31,
	 * which is below the minimum version 32 required for WebP support.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::browser_support
	 * @return void
	 */
	public function test_browser_support_returns_false_for_chrome_31(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.63 Safari/537.36';

		$result = Helpers::browser_support();

		$this->assertFalse( $result );
	}

	/**
	 * Tests that browser_support method detects WebP support in Firefox 85.
	 *
	 * Verifies that browser_support returns true for Firefox version 85,
	 * which is well above the minimum version 65 required for WebP support.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::browser_support
	 * @return void
	 */
	public function test_browser_support_returns_true_for_firefox_85(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:85.0) Gecko/20100101 Firefox/85.0';

		$result = Helpers::browser_support();

		$this->assertTrue( $result );
	}

	/**
	 * Tests that browser_support method rejects Firefox 64.
	 *
	 * Verifies that browser_support returns false for Firefox version 64,
	 * which is below the minimum version 65 required for WebP support.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::browser_support
	 * @return void
	 */
	public function test_browser_support_returns_false_for_firefox_64(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:64.0) Gecko/20100101 Firefox/64.0';

		$result = Helpers::browser_support();

		$this->assertFalse( $result );
	}

	/**
	 * Tests that browser_support method detects WebP support in Edge 90.
	 *
	 * Verifies that browser_support returns true for Edge version 90,
	 * which is well above the minimum version 18 required for WebP support.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::browser_support
	 * @return void
	 */
	public function test_browser_support_returns_true_for_edge_90(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Safari/537.36 Edg/90.0.818.51';

		$result = Helpers::browser_support();

		$this->assertTrue( $result );
	}

	/**
	 * Tests that browser_support method detects WebP support in Safari 16.
	 *
	 * Verifies that browser_support returns true for Safari version 16,
	 * which meets the minimum version 16 required for WebP support.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::browser_support
	 * @return void
	 */
	public function test_browser_support_returns_true_for_safari_16(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Safari/605.1.15';

		$result = Helpers::browser_support();

		$this->assertTrue( $result );
	}

	/**
	 * Tests that browser_support method rejects Safari 15.
	 *
	 * Verifies that browser_support returns false for Safari version 15,
	 * which is below the minimum version 16 required for WebP support.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::browser_support
	 * @return void
	 */
	public function test_browser_support_returns_false_for_safari_15(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6 Safari/605.1.15';

		$result = Helpers::browser_support();

		$this->assertFalse( $result );
	}

	/**
	 * Tests that browser_support method detects WebP support in Opera 75.
	 *
	 * Verifies that browser_support returns true for Opera version 75,
	 * which is well above the minimum version 19 required for WebP support.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::browser_support
	 * @return void
	 */
	public function test_browser_support_returns_true_for_opera_75(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.82 Safari/537.36 OPR/75.0.3969.149';

		$result = Helpers::browser_support();

		$this->assertTrue( $result );
	}

	/**
	 * Tests that browser_support method detects WebP support in Android 5.0.
	 *
	 * Verifies that browser_support returns true for Android version 5.0,
	 * which meets the minimum version 5 required for WebP support.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::browser_support
	 * @return void
	 */
	public function test_browser_support_returns_true_for_android_5(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; Android 5.0; SM-G900P Build/LRX21T) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/37.0.0.0 Mobile Safari/537.36';

		$result = Helpers::browser_support();

		$this->assertTrue( $result );
	}

	/**
	 * Tests that browser_support method detects WebP support in Samsung Internet 15.
	 *
	 * Verifies that browser_support returns true for Samsung Internet version 15,
	 * which is well above the minimum version 4 required for WebP support.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::browser_support
	 * @return void
	 */
	public function test_browser_support_returns_true_for_samsung_browser(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; Android 11; SAMSUNG SM-G998B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/15.0 Chrome/90.0.4430.210 Mobile Safari/537.36';

		$result = Helpers::browser_support();

		$this->assertTrue( $result );
	}

	/**
	 * Tests that browser_support method rejects unknown browsers.
	 *
	 * Verifies that browser_support returns false when the user agent
	 * is not recognized as a supported browser.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::browser_support
	 * @return void
	 */
	public function test_browser_support_returns_false_for_unknown_browser(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'UnknownBot/1.0';

		$result = Helpers::browser_support();

		$this->assertFalse( $result );
	}

	/**
	 * Tests that browser_support method rejects browsers without version information.
	 *
	 * Verifies that browser_support returns false when a browser is detected
	 * but its version number cannot be determined from the user agent.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::browser_support
	 * @return void
	 */
	public function test_browser_support_returns_false_when_version_unknown(): void {
		$_SERVER['HTTP_ACCEPT']     = 'text/html,application/xhtml+xml';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome Safari/537.36';

		$result = Helpers::browser_support();

		$this->assertFalse( $result );
	}

	/**
	 * Tests that browser_support method rejects requests without headers.
	 *
	 * Verifies that browser_support returns false when neither HTTP_ACCEPT
	 * nor HTTP_USER_AGENT headers are present in the request.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::browser_support
	 * @return void
	 */
	public function test_browser_support_with_no_headers(): void {
		$result = Helpers::browser_support();

		$this->assertFalse( $result );
	}

	/**
	 * Tests that get_browser method correctly identifies Chrome browser.
	 *
	 * Verifies that get_browser extracts the browser name "Chrome" and
	 * version number from a Chrome user agent string.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_browser
	 * @return void
	 */
	public function test_get_browser_detects_chrome(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Safari/537.36';

		$result = Helpers::get_browser();

		$this->assertEquals( 'Chrome', $result['name'] );
		$this->assertEquals( '90.0.4430.93', $result['version'] );
	}

	/**
	 * Tests that get_browser method correctly identifies Firefox browser.
	 *
	 * Verifies that get_browser extracts the browser name "Firefox" and
	 * version number from a Firefox user agent string.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_browser
	 * @return void
	 */
	public function test_get_browser_detects_firefox(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:85.0) Gecko/20100101 Firefox/85.0';

		$result = Helpers::get_browser();

		$this->assertEquals( 'Firefox', $result['name'] );
		$this->assertEquals( '85.0', $result['version'] );
	}

	/**
	 * Tests that get_browser method correctly identifies Edge browser.
	 *
	 * Verifies that get_browser extracts the browser name "Edge" and
	 * version number from an Edge user agent string containing "Edge".
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_browser
	 * @return void
	 */
	public function test_get_browser_detects_edge(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Safari/537.36 Edge/90.0.818.51';

		$result = Helpers::get_browser();

		$this->assertEquals( 'Edge', $result['name'] );
		$this->assertEquals( '90.0.818.51', $result['version'] );
	}

	/**
	 * Tests that get_browser method correctly identifies Safari browser.
	 *
	 * Verifies that get_browser extracts the browser name "Safari" and
	 * version number from a Safari user agent string.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_browser
	 * @return void
	 */
	public function test_get_browser_detects_safari(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Safari/605.1.15';

		$result = Helpers::get_browser();

		$this->assertEquals( 'Safari', $result['name'] );
		$this->assertEquals( '16.0', $result['version'] );
	}

	/**
	 * Tests that get_browser method correctly identifies Opera browser.
	 *
	 * Verifies that get_browser extracts the browser name "Opera" and
	 * version number from an Opera user agent string containing "OPR".
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_browser
	 * @return void
	 */
	public function test_get_browser_detects_opera(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.82 Safari/537.36 OPR/75.0.3969.149';

		$result = Helpers::get_browser();

		$this->assertEquals( 'Opera', $result['name'] );
		$this->assertEquals( '75.0.3969.149', $result['version'] );
	}

	/**
	 * Tests that get_browser method correctly identifies IE using Trident identifier.
	 *
	 * Verifies that get_browser extracts the browser name "IE" and
	 * version number from an Internet Explorer user agent string containing "Trident".
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_browser
	 * @return void
	 */
	public function test_get_browser_detects_ie_trident(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko';

		$result = Helpers::get_browser();

		$this->assertEquals( 'IE', $result['name'] );
		$this->assertEquals( '11.0', $result['version'] );
	}

	/**
	 * Tests that get_browser method correctly identifies IE using MSIE identifier.
	 *
	 * Verifies that get_browser extracts the browser name "IE" and
	 * version number from an Internet Explorer user agent string containing "MSIE".
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_browser
	 * @return void
	 */
	public function test_get_browser_detects_ie_msie(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)';

		$result = Helpers::get_browser();

		$this->assertEquals( 'IE', $result['name'] );
		$this->assertEquals( '10.0', $result['version'] );
	}

	/**
	 * Tests that get_browser method correctly identifies Samsung Internet browser.
	 *
	 * Verifies that get_browser extracts the browser name "Samsung" and
	 * version number from a Samsung Internet user agent string.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_browser
	 * @return void
	 */
	public function test_get_browser_detects_samsung_browser(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; Android 11) SamsungBrowser/15.0';

		$result = Helpers::get_browser();

		$this->assertEquals( 'Samsung', $result['name'] );
		$this->assertEquals( '15.0', $result['version'] );
	}

	/**
	 * Tests that get_browser method correctly identifies Android browser.
	 *
	 * Verifies that get_browser extracts the browser name "Android" and
	 * version number from an Android browser user agent string.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_browser
	 * @return void
	 */
	public function test_get_browser_detects_android_browser(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; Android 5.0; SM-G900P Build/LRX21T)';

		$result = Helpers::get_browser();

		$this->assertEquals( 'Android', $result['name'] );
		$this->assertEquals( '5.0', $result['version'] );
	}

	/**
	 * Tests that get_browser method returns Unknown for unrecognized user agents.
	 *
	 * Verifies that get_browser returns "Unknown" as the browser name and "?" as the
	 * version when the user agent string does not match any known browser pattern.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_browser
	 * @return void
	 */
	public function test_get_browser_returns_unknown_for_unrecognized(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'UnknownBot/1.0';

		$result = Helpers::get_browser();

		$this->assertEquals( 'Unknown', $result['name'] );
		$this->assertEquals( '?', $result['version'] );
	}

	/**
	 * Tests that get_browser method returns Unknown when no user agent is present.
	 *
	 * Verifies that get_browser returns "Unknown" as the browser name and "?" as the
	 * version when the HTTP_USER_AGENT header is not set.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_browser
	 * @return void
	 */
	public function test_get_browser_returns_unknown_when_no_user_agent(): void {
		$result = Helpers::get_browser();

		$this->assertEquals( 'Unknown', $result['name'] );
		$this->assertEquals( '?', $result['version'] );
	}

	/**
	 * Tests that get_browser method prioritizes Chrome over Safari in user agent.
	 *
	 * Verifies that get_browser correctly identifies Chrome when both "Chrome" and "Safari"
	 * appear in the user agent string, following browser detection best practices.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_browser
	 * @return void
	 */
	public function test_get_browser_prioritizes_chrome_over_safari(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Safari/537.36';

		$result = Helpers::get_browser();

		$this->assertEquals( 'Chrome', $result['name'] );
		$this->assertEquals( '90.0.4430.93', $result['version'] );
	}

	/**
	 * Tests that get_browser method handles empty user agent strings.
	 *
	 * Verifies that get_browser returns "Unknown" as the browser name and "?" as the
	 * version when the HTTP_USER_AGENT header is set but contains an empty string.
	 *
	 * @since 1.0.0
	 * @covers \ConvertToWebpLite\Utils\Helpers::get_browser
	 * @return void
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
