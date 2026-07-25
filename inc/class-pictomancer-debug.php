<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pictomancer_Debug {

	private $log_dir;
	private $log_file;

	public function __construct() {
		add_action( 'init', [ $this, 'init_debug_mode' ] );
	}

	public function init_debug_mode() {
		$options = get_option( 'pictomancer_settings' );

		if ( isset( $options['debug_mode'] ) && $options['debug_mode'] ) {
			add_action( 'pictomancer_log', [ $this, 'write_log' ], 10, 2 );
		}
	}

	public function write_log( $message, $level = 'info' ) {
		if ( ! apply_filters( 'pictomancer_debug_logging_enabled', true ) ) {
			return;
		}

		$log_file = $this->resolve_log_file();
		$this->protect_log_dir();

		$timestamp = current_time( 'mysql' );
		$log_entry = sprintf( "[%s] [%s] %s\n", $timestamp, strtoupper( $level ), $message );

		file_put_contents( $log_file, $log_entry, FILE_APPEND );
	}

	public function get_log_content() {
		$log_file = $this->resolve_log_file();
		if ( file_exists( $log_file ) ) {
			return file_get_contents( $log_file );
		}
		return '';
	}

	public function clear_log() {
		$log_file = $this->resolve_log_file();
		if ( file_exists( $log_file ) ) {
			wp_delete_file( $log_file );
		}
	}

	// Resolved on first use, never in the constructor: wp_hash lives in
	// pluggable.php, which WordPress loads only after all plugins.
	private function resolve_log_file() {
		if ( null !== $this->log_file ) {
			return $this->log_file;
		}

		$upload_dir = wp_upload_dir();
		$this->log_dir = trailingslashit( $upload_dir['basedir'] ) . 'pictomancer';
		// Site-specific, non-guessable filename derived from the auth salts so the
		// log path cannot be discovered by URL fishing.
		$suffix = substr( wp_hash( 'pictomancer-debug-log' ), 0, 12 );
		$this->log_file = trailingslashit( $this->log_dir ) . 'debug-' . $suffix . '.log';

		return $this->log_file;
	}

	// Keep the log directory off the web: a deny-all .htaccess for Apache and an
	// empty index.html so no server lists or serves the logs directly.
	private function protect_log_dir() {
		if ( ! is_dir( $this->log_dir ) ) {
			wp_mkdir_p( $this->log_dir );
		}

		$htaccess = trailingslashit( $this->log_dir ) . '.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			file_put_contents( $htaccess, "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n" );
		}

		$index = trailingslashit( $this->log_dir ) . 'index.html';
		if ( ! file_exists( $index ) ) {
			file_put_contents( $index, '' );
		}
	}
}
