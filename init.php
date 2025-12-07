<?php
/**
 * Plugin Name: Sign in with OTP Addon
 * Description: Sign in with OTP Addon allows you to sign in using OTP in the WordPress admin login screen.
 * Author: Jinesh P.V
 * Author URI: https://jineshpv.com/
 * Version: 1.0.0
 * Text Domain: signin-with-otp-addon
 * Requires at least: 6.0
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * php version 8.1
 *
 * @package SignInWithOtp
 */

/**
 * Copyright (c) 2019-2026 Jinesh P.V
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

defined( 'ABSPATH' ) || exit;

// Define SIOTP_PLUGIN_FILE.
if ( ! defined( 'SIOTP_PLUGIN_FILE' ) ) {
	define( 'SIOTP_PLUGIN_FILE', __FILE__ );
}

/**
 * Include the main SignInWithOtp class.
 *
 * @package  SignInWithOtp
 * @version  Release: @1.0.0@
 * @link     https://jineshpv.com/extensions/jxn-signin-with-otp-addon/
 */
if ( ! class_exists( 'SignInWithOtp', false ) ) {
	include_once dirname( SIOTP_PLUGIN_FILE ) . '/includes/class-signin-with-otp.php';
}

/**
 * Returns the main instance of SIOTP.
 *
 * @since  1.0.0
 * @return SignInWithOtp
 */
function SIOTP() {
	return SignInWithOtp::instance();
}

// Global for backwards compatibility.
$GLOBALS['signin_with_otp'] = SIOTP();
