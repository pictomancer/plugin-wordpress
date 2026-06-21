<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pictomancer_GraphQL {

	public function __construct() {
		$options = get_option( 'pictomancer_settings' );
		if ( ! ( isset( $options['enable_graphql'] ) && $options['enable_graphql'] ) ) {
			return;
		}

		// Check if WPGraphQL is active
		if ( ! class_exists( 'WPGraphQL' ) ) {
			return;
		}

		add_action( 'graphql_register_types', [ $this, 'register_graphql_types' ] );
	}

	public function register_graphql_types() {
		// Register a custom GraphQL field to expose Pictomancer settings
		register_graphql_field( 'RootQuery', 'pictomancerSettings', [
			'type'        => 'String',
			'description' => __( 'Pictomancer plugin settings', 'pictomancer-image-optimizer' ),
			'resolve'     => function() {
				$settings = get_option( 'pictomancer_settings', [] );
				return wp_json_encode( $settings );
			},
		] );

		// Register a custom GraphQL field to expose Pictomancer API health (placeholder)
		register_graphql_field( 'RootQuery', 'pictomancerApiHealth', [
			'type'        => 'String',
			'description' => __( 'Pictomancer API health status', 'pictomancer-image-optimizer' ),
			'resolve'     => function() {
				// In a real scenario, this would ping the API to check its status
				return 'Operational';
			},
		] );

		// Register a custom GraphQL field to expose optimization profiles (placeholder)
		register_graphql_field( 'RootQuery', 'pictomancerOptimizationProfiles', [
			'type'        => 'String',
			'description' => __( 'Pictomancer optimization profiles', 'pictomancer-image-optimizer' ),
			'resolve'     => function() {
				// In a real scenario, this would fetch saved profiles
				$profiles = [
					[ 'name' => 'Web Standard', 'quality' => 80, 'dimensions' => '1920x1080' ],
					[ 'name' => 'Thumbnail', 'quality' => 60, 'dimensions' => '150x150' ],
				];
				return wp_json_encode( $profiles );
			},
		] );

		// Register a custom GraphQL field to expose logs (placeholder)
		register_graphql_field( 'RootQuery', 'pictomancerLogs', [
			'type'        => 'String',
			'description' => __( 'Pictomancer debug logs', 'pictomancer-image-optimizer' ),
			'resolve'     => function() {
				$debug = new Pictomancer_Debug();
				return $debug->get_log_content();
			},
		] );
	}
}

new Pictomancer_GraphQL();
