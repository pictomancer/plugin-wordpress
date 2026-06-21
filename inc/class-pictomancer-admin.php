<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pictomancer_Admin {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	public function add_admin_menu() {
		add_menu_page(
			__( 'Pictomancer', 'pictomancer-image-optimizer' ),
			__( 'Pictomancer', 'pictomancer-image-optimizer' ),
			'manage_options',
			'pictomancer',
			[ $this, 'render_admin_page' ],
			'dashicons-format-image'
		);
	}

	public function render_admin_page() {
		echo '<div id="pictomancer-admin"></div>';
	}

	public function enqueue_scripts( $hook ) {
		if ( 'toplevel_page_pictomancer' !== $hook ) {
			return;
		}

		$bundle = plugin_dir_path( dirname( __FILE__ ) ) . 'build/pictomancer-admin.js';
		if ( ! file_exists( $bundle ) ) {
			return;
		}

		// Self-contained Vite IIFE bundle; React and styles are inlined and the UI
		// mounts into a Shadow DOM, so there are no script deps or stylesheet to enqueue.
		wp_enqueue_script(
			'pictomancer-admin',
			plugins_url( 'build/pictomancer-admin.js', dirname( __FILE__ ) ),
			[],
			(string) filemtime( $bundle ),
			true
		);

		wp_localize_script(
			'pictomancer-admin',
			'pictomancerData',
			[
				'restUrl' => esc_url_raw( rest_url( 'pictomancer/v1/' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			]
		);

		// Self-hosted Outfit, no third-party request. Defined in the document head
		// so the Shadow DOM UI can resolve the @font-face.
		wp_enqueue_style(
			'pictomancer-admin-font',
			plugins_url( 'assets/admin-fonts.css', dirname( __FILE__ ) ),
			[],
			PICTOMANCER_VERSION
		);

		// wp-admin chrome lives outside our Shadow DOM, so strip its gutters/footer
		// and darken the canvas here so the app fills the content area edge to edge.
		wp_add_inline_style(
			'pictomancer-admin-font',
			'#wpcontent{padding-left:0}'
				. '#wpbody-content{padding-bottom:0}'
				. '#wpfooter{display:none}'
				. 'html.wp-toolbar,body.toplevel_page_pictomancer{background:#09090b}'
				. '#pictomancer-admin{display:block;min-height:calc(100vh - 32px)}'
		);
	}
}

new Pictomancer_Admin();
