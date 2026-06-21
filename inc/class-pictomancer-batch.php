<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pictomancer_Batch {

	public function __construct() {
		add_action( 'wp_ajax_pictomancer_start_batch_optimization', [ $this, 'start_batch_optimization' ] );
		add_action( 'wp_ajax_pictomancer_get_batch_progress', [ $this, 'get_batch_progress' ] );
	}

	public function start_batch_optimization() {
		// Nonce verification for security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'pictomancer_batch_optimization' ) ) {
			wp_send_json_error( 'Security check failed.' );
		}

		// Start batch optimization process (e.g., using WP Cron or a custom queue)
		// For now, just a placeholder
		update_option( 'pictomancer_batch_status', [ 'total' => 100, 'processed' => 0, 'status' => 'in_progress' ] );
		wp_send_json_success( 'Batch optimization started.' );
	}

	public function get_batch_progress() {
		// Nonce verification for security
		if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'pictomancer_batch_optimization' ) ) {
			wp_send_json_error( 'Security check failed.' );
		}

		$status = get_option( 'pictomancer_batch_status', [ 'total' => 0, 'processed' => 0, 'status' => 'idle' ] );
		wp_send_json_success( $status );
	}
}

new Pictomancer_Batch();
