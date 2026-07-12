<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pictomancer_REST_API {

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		register_rest_route( 'pictomancer/v1', '/settings', [
			[
				'methods'  => 'GET',
				'callback' => [ $this, 'get_settings' ],
				'permission_callback' => [ $this, 'get_permissions_check' ],
			],
			[
				'methods'  => 'POST',
				'callback' => [ $this, 'save_settings' ],
				'permission_callback' => [ $this, 'get_permissions_check' ],
			],
		] );

		register_rest_route( 'pictomancer/v1', '/stats', [
			[
				'methods'  => 'GET',
				'callback' => [ $this, 'get_stats' ],
				'permission_callback' => [ $this, 'get_permissions_check' ],
			],
		] );

		register_rest_route( 'pictomancer/v1', '/logs', [
			[
				'methods'  => 'GET',
				'callback' => [ $this, 'get_logs' ],
				'permission_callback' => [ $this, 'get_permissions_check' ],
			],
			[
				'methods'  => 'DELETE',
				'callback' => [ $this, 'clear_logs' ],
				'permission_callback' => [ $this, 'get_permissions_check' ],
			],
		] );
	}

	public function get_permissions_check() {
		return current_user_can( 'manage_options' );
	}

	public function get_settings() {
		$stored = $this->stored_settings();
		$locked = $this->locked_keys();

		$settings = [
			'enabled'             => (bool) ( $stored['enabled'] ?? false ),
			'api_url'             => $locked['api_url'] ? '' : (string) ( $stored['api_url'] ?? '' ),
			'api_key'             => $locked['api_key'] ? '' : (string) ( $stored['api_key'] ?? '' ),
			'quality'             => (string) ( $stored['quality'] ?? '' ),
			'optimize_thumbnails' => (bool) ( $stored['optimize_thumbnails'] ?? true ),
			'debug_mode'          => (bool) ( $stored['debug_mode'] ?? false ),
		];

		return new WP_REST_Response( [ 'settings' => $settings, 'overrides' => $locked ] );
	}

	public function save_settings( $request ) {
		$input  = (array) $request->get_json_params();
		$stored = $this->stored_settings();
		$locked = $this->locked_keys();

		// Merge known keys into the existing option; never clobber unsent fields,
		// and never overwrite a key pinned by a wp-config constant.
		if ( array_key_exists( 'enabled', $input ) ) {
			$stored['enabled'] = (int) (bool) $input['enabled'];
		}
		if ( ! $locked['api_url'] && array_key_exists( 'api_url', $input ) ) {
			$stored['api_url'] = esc_url_raw( (string) $input['api_url'] );
		}
		if ( ! $locked['api_key'] && array_key_exists( 'api_key', $input ) ) {
			$stored['api_key'] = sanitize_text_field( (string) $input['api_key'] );
		}
		if ( array_key_exists( 'quality', $input ) ) {
			$quality           = (string) $input['quality'];
			$stored['quality'] = $quality === '' ? '' : max( 0, min( 100, (int) $quality ) );
		}
		if ( array_key_exists( 'optimize_thumbnails', $input ) ) {
			$stored['optimize_thumbnails'] = (int) (bool) $input['optimize_thumbnails'];
		}
		if ( array_key_exists( 'debug_mode', $input ) ) {
			$stored['debug_mode'] = (int) (bool) $input['debug_mode'];
		}

		update_option( 'pictomancer_settings', $stored );
		do_action( 'pictomancer_settings_updated' );

		return new WP_REST_Response( [ 'success' => true ] );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function stored_settings() {
		$stored = get_option( 'pictomancer_settings', [] );

		return is_array( $stored ) ? $stored : [];
	}

	/**
	 * @return array<string, bool>
	 */
	private function locked_keys() {
		return [
			'api_key' => defined( 'PICTOMANCER_API_KEY' ) && PICTOMANCER_API_KEY !== '',
			'api_url' => defined( 'PICTOMANCER_API_URL' ) && PICTOMANCER_API_URL !== '',
		];
	}

	public function get_stats() {
		$settings = $this->stored_settings();
		$enabled  = (bool) ( $settings['enabled'] ?? false );

		$stats            = ( new Pictomancer_Stats() )->get();
		$stats['enabled'] = $enabled;
		// While optimization is off the plugin makes no remote calls at all,
		// including this health probe.
		$stats['api'] = $enabled
			? $this->api_health( $settings )
			: [ 'ok' => false, 'detail' => 'disabled' ];

		return new WP_REST_Response( $stats );
	}

	/**
	 * @param array<string, mixed> $settings
	 * @return array<string, bool|string>
	 */
	private function api_health( array $settings ) {
		try {
			$info = Pictomancer_Client_Factory::create( $settings )->info();
			return [ 'ok' => true, 'detail' => (string) ( $info['version'] ?? 'operational' ) ];
		} catch ( \Throwable $e ) {
			return [ 'ok' => false, 'detail' => $e->getMessage() ];
		}
	}

	public function get_logs() {
		$debug = new Pictomancer_Debug();
		$logs = $debug->get_log_content();
		return new WP_REST_Response( [ 'logs' => $logs ] );
	}

	public function clear_logs() {
		$debug = new Pictomancer_Debug();
		$debug->clear_log();
		return new WP_REST_Response( [ 'success' => true ] );
	}
}

new Pictomancer_REST_API();
