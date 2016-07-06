<?php

$_tests_dir = getenv('WP_TESTS_DIR');
if ( !$_tests_dir ) $_tests_dir = '/tmp/wordpress-tests-lib';

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	global $wp_theatre;

	/*
	 * Fake a session_start() to avoid PHP Warnings when
	 * Theater tries to start a new session.
	 * 
	 * @since 0.9.5
	 *
	 * @see WPT_Cart::init()
	 * @see https://github.com/slimndap/wp-theatre/issues/67
	 * @see http://stackoverflow.com/a/23400788
	 */	 
	@session_start();

	require dirname( __FILE__ ) . '/../theater.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
require dirname( __FILE__ ) . '/../functions/wpt_unittestcase.php';

