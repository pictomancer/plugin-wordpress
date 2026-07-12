<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pictomancer_Admin {

	const ENABLE_NOTICE_DISMISSED_META = 'pictomancer_enable_notice_dismissed';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_notices', [ $this, 'render_enable_notice' ] );
		add_action( 'admin_init', [ $this, 'dismiss_enable_notice' ] );
	}

	public function render_enable_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = get_option( 'pictomancer_settings', [] );
		if ( (bool) ( $settings['enabled'] ?? false ) ) {
			return;
		}

		if ( get_user_meta( get_current_user_id(), self::ENABLE_NOTICE_DISMISSED_META, true ) ) {
			return;
		}

		// The plugin page already shows its own state; the notice is for
		// admins who haven't found it yet.
		$screen = get_current_screen();
		if ( $screen && 'toplevel_page_pictomancer' === $screen->id ) {
			return;
		}

		printf(
			'<div class="notice notice-info"><p>%s <a href="%s">%s</a> <a href="%s">%s</a></p></div>',
			esc_html__( 'Pictomancer Image Optimizer is active but optimization is off, so no image data leaves your site.', 'pictomancer-image-optimizer' ),
			esc_url( admin_url( 'admin.php?page=pictomancer' ) ),
			esc_html__( 'Enable it in the Pictomancer settings.', 'pictomancer-image-optimizer' ),
			esc_url( wp_nonce_url( add_query_arg( 'pictomancer-dismiss-notice', '1' ), 'pictomancer-dismiss-notice' ) ),
			esc_html__( 'Dismiss', 'pictomancer-image-optimizer' )
		);
	}

	public function dismiss_enable_notice() {
		if ( ! isset( $_GET['pictomancer-dismiss-notice'] ) ) {
			return;
		}

		check_admin_referer( 'pictomancer-dismiss-notice' );

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		update_user_meta( get_current_user_id(), self::ENABLE_NOTICE_DISMISSED_META, 1 );

		wp_safe_redirect( remove_query_arg( [ 'pictomancer-dismiss-notice', '_wpnonce' ] ) );
		exit;
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
