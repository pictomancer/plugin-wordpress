<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pictomancer {

	protected static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		// Activation and deactivation hooks
		register_activation_hook( __FILE__, [ $this, 'activate' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );

		// Other hooks and filters will be added here
		$this->includes();
	}

	public function activate() {
		// Activation logic
	}

	public function deactivate() {
		// Deactivation logic
	}

	private function includes() {
		$autoload = plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/autoload.php';
		if ( file_exists( $autoload ) ) {
			require_once $autoload;
		}

		require_once plugin_dir_path( __FILE__ ) . 'class-pictomancer-client.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-pictomancer-optimizer-service.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-pictomancer-stats.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-pictomancer-admin.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-pictomancer-optimization.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-pictomancer-settings.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-pictomancer-rest-api.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-pictomancer-batch.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-pictomancer-cli.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-pictomancer-debug.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-pictomancer-cron.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-pictomancer-graphql.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-pictomancer-woocommerce.php';
	}

}

Pictomancer::get_instance();
