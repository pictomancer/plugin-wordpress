<?php

// Minimal stand-ins for the WordPress functions the plugin touches while it is
// being loaded. Deliberately omits wp_hash() and everything else from
// pluggable.php, which WordPress loads only AFTER every plugin file - so any
// pluggable call made during plugin load surfaces here as the same fatal that
// took the live site down.
//
// Registered hooks are captured in $GLOBALS['pictomancer_test_hooks'] keyed by
// hook name so a test can assert what a load registered and later fire a
// callback by hand.

if ( ! isset( $GLOBALS['pictomancer_test_hooks'] ) ) {
	$GLOBALS['pictomancer_test_hooks'] = [];
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return rtrim( dirname( $file ), '/\\' ) . '/';
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $string ) {
		return rtrim( $string, '/\\' ) . '/';
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['pictomancer_test_hooks'][ $hook ][] = $callback;
		return true;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['pictomancer_test_hooks'][ $hook ][] = $callback;
		return true;
	}
}

if ( ! function_exists( 'wp_upload_dir' ) ) {
	function wp_upload_dir() {
		return [ 'basedir' => sys_get_temp_dir() ];
	}
}
