<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds a configured Pictomancer SDK client from the plugin settings.
 */
class Pictomancer_Client_Factory {

	const DEFAULT_BASE_URL = 'https://api.pictomancer.ai';

	/**
	 * @param array<string, mixed> $settings the `pictomancer_settings` option
	 * @param string $integration consumer identifier appended to the SDK User-Agent
	 */
	public static function create( array $settings, string $integration = '' ): \Pictomancer\Client {
		$api_key  = self::resolve_api_key( $settings );
		$base_url = self::normalize_base_url( self::resolve_api_url( $settings ) );

		return new \Pictomancer\Client(
			$api_key !== '' ? $api_key : null,
			$base_url,
			30.0,
			null,
			$integration !== '' ? $integration : null,
		);
	}

	/**
	 * A `PICTOMANCER_API_KEY` constant (set in wp-config.php / server env) wins
	 * over the stored option, so the secret can live outside the database.
	 *
	 * @param array<string, mixed> $settings
	 */
	public static function resolve_api_key( array $settings ): string {
		if ( defined( 'PICTOMANCER_API_KEY' ) && PICTOMANCER_API_KEY !== '' ) {
			return (string) PICTOMANCER_API_KEY;
		}

		return isset( $settings['api_key'] ) ? (string) $settings['api_key'] : '';
	}

	/**
	 * @param array<string, mixed> $settings
	 */
	public static function resolve_api_url( array $settings ): string {
		if ( defined( 'PICTOMANCER_API_URL' ) && PICTOMANCER_API_URL !== '' ) {
			return (string) PICTOMANCER_API_URL;
		}

		return (string) ( $settings['api_url'] ?? '' );
	}

	/**
	 * The SDK appends `/v1`, so the base must be the host root. Tolerate older
	 * settings that stored a trailing `/v1` or slash.
	 */
	public static function normalize_base_url( string $configured ): string {
		$base = trim( $configured );
		if ( $base === '' ) {
			return self::DEFAULT_BASE_URL;
		}

		$base = rtrim( $base, '/' );
		if ( str_ends_with( $base, '/v1' ) ) {
			$base = substr( $base, 0, -3 );
		}

		return rtrim( $base, '/' );
	}
}
