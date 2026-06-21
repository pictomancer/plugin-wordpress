<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pictomancer_Settings {

	public function __construct() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', [ $this, 'add_network_admin_menu' ] );
			add_action( 'network_admin_edit_pictomancer_settings', [ $this, 'save_network_settings' ] );
			add_action( 'admin_init', [ $this, 'register_network_settings' ] );
		}
	}

	public function register_settings() {
		register_setting( 'pictomancer', 'pictomancer_settings', [ 'sanitize_callback' => [ $this, 'sanitize_settings' ] ] );

		add_settings_section(
			'pictomancer_api_settings',
			__( 'API Settings', 'pictomancer-image-optimizer' ),
			null,
			'pictomancer'
		);

		add_settings_field(
			'api_url',
			__( 'API URL', 'pictomancer-image-optimizer' ),
			[ $this, 'render_api_url_field' ],
			'pictomancer',
			'pictomancer_api_settings'
		);

		add_settings_field(
			'api_key',
			__( 'API Key', 'pictomancer-image-optimizer' ),
			[ $this, 'render_api_key_field' ],
			'pictomancer',
			'pictomancer_api_settings'
		);

		add_settings_field(
			'quality',
			__( 'Quality', 'pictomancer-image-optimizer' ),
			[ $this, 'render_quality_field' ],
			'pictomancer',
			'pictomancer_api_settings'
		);

		add_settings_field(
			'optimize_thumbnails',
			__( 'Optimize Thumbnails', 'pictomancer-image-optimizer' ),
			[ $this, 'render_optimize_thumbnails_field' ],
			'pictomancer',
			'pictomancer_api_settings'
		);

		add_settings_field(
			'debug_mode',
			__( 'Debug Mode', 'pictomancer-image-optimizer' ),
			[ $this, 'render_debug_mode_field' ],
			'pictomancer',
			'pictomancer_api_settings'
		);

		add_settings_field(
			'requeue_failed',
			__( 'Requeue Failed', 'pictomancer-image-optimizer' ),
			[ $this, 'render_requeue_failed_field' ],
			'pictomancer',
			'pictomancer_api_settings'
		);

		add_settings_section(
			'pictomancer_experimental_settings',
			__( 'Experimental Features', 'pictomancer-image-optimizer' ),
			null,
			'pictomancer'
		);

		add_settings_field(
			'enable_graphql',
			__( 'Enable GraphQL Integration', 'pictomancer-image-optimizer' ),
			[ $this, 'render_enable_graphql_field' ],
			'pictomancer',
			'pictomancer_experimental_settings'
		);

		add_settings_field(
			'enable_woocommerce',
			__( 'Enable WooCommerce Integration', 'pictomancer-image-optimizer' ),
			[ $this, 'render_enable_woocommerce_field' ],
			'pictomancer',
			'pictomancer_experimental_settings'
		);
	}

	public function add_network_admin_menu() {
		add_menu_page(
			__( 'Pictomancer Network Settings', 'pictomancer-image-optimizer' ),
			__( 'Pictomancer', 'pictomancer-image-optimizer' ),
			'manage_network_options',
			'pictomancer-network',
			[ $this, 'render_network_settings_page' ],
			'dashicons-format-image'
		);
	}

	public function render_network_settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Pictomancer Network Settings', 'pictomancer-image-optimizer' ); ?></h1>
			<form method="post" action="edit.php?action=pictomancer_network_settings">
				<?php settings_fields( 'pictomancer_network' ); ?>
				<?php do_settings_sections( 'pictomancer-network' ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public function register_network_settings() {
		register_setting( 'pictomancer_network', 'pictomancer_network_settings', [ 'sanitize_callback' => [ $this, 'sanitize_network_settings' ] ] );

		add_settings_section(
			'pictomancer_network_api_settings',
			__( 'Network API Settings', 'pictomancer-image-optimizer' ),
			null,
			'pictomancer-network'
		);

		add_settings_field(
			'network_api_url',
			__( 'Network API URL', 'pictomancer-image-optimizer' ),
			[ $this, 'render_network_api_url_field' ],
			'pictomancer-network',
			'pictomancer_network_api_settings'
		);

		add_settings_field(
			'network_debug_mode',
			__( 'Network Debug Mode', 'pictomancer-image-optimizer' ),
			[ $this, 'render_network_debug_mode_field' ],
			'pictomancer-network',
			'pictomancer_network_api_settings'
		);
	}

	public function sanitize_network_settings( $input ) {
		$new_input = [];
		if ( isset( $input['api_url'] ) ) {
			$new_input['api_url'] = esc_url_raw( $input['api_url'] );
		}
		if ( isset( $input['debug_mode'] ) ) {
			$new_input['debug_mode'] = (bool) $input['debug_mode'];
		}
		return $new_input;
	}

	public function save_network_settings() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'pictomancer-image-optimizer' ) );
		}

		// Check nonce for security
		check_admin_referer( 'pictomancer-network-options' );

		// Nonce verified by check_admin_referer() above; sanitize every element
		// (sanitize_network_settings applies field-specific sanitization on top).
		$input = map_deep( wp_unslash( $_POST['pictomancer_network_settings'] ?? [] ), 'sanitize_text_field' );
		$new_input = $this->sanitize_network_settings( $input );

		$old_settings = get_site_option( 'pictomancer_network_settings' );
		$merged_settings = array_merge( $old_settings, $new_input );
		update_site_option( 'pictomancer_network_settings', $merged_settings );

		wp_safe_redirect( add_query_arg( [ 'page' => 'pictomancer-network', 'updated' => 'true' ], network_admin_url( 'settings.php' ) ) );
		exit;
	}

	public function sanitize_settings( $input ) {
		$new_input = [];
		if ( isset( $input['api_url'] ) ) {
			$new_input['api_url'] = esc_url_raw( $input['api_url'] );
		}
		if ( isset( $input['api_key'] ) ) {
			$new_input['api_key'] = sanitize_text_field( $input['api_key'] );
		}
		if ( isset( $input['quality'] ) && $input['quality'] !== '' ) {
			$new_input['quality'] = max( 0, min( 100, (int) $input['quality'] ) );
		}
		if ( isset( $input['optimize_thumbnails'] ) ) {
			$new_input['optimize_thumbnails'] = (int) (bool) $input['optimize_thumbnails'];
		}
		if ( isset( $input['debug_mode'] ) ) {
			$new_input['debug_mode'] = (bool) $input['debug_mode'];
		}
		if ( isset( $input['requeue_failed'] ) ) {
			$new_input['requeue_failed'] = (bool) $input['requeue_failed'];
		}

		if ( isset( $input['enable_graphql'] ) ) {
			$new_input['enable_graphql'] = (bool) $input['enable_graphql'];
		}
		if ( isset( $input['enable_woocommerce'] ) ) {
			$new_input['enable_woocommerce'] = (bool) $input['enable_woocommerce'];
		}

		$old_settings = get_option( 'pictomancer_settings' );
		$merged_settings = array_merge( $old_settings, $new_input );
		update_option( 'pictomancer_settings', $merged_settings );
		do_action( 'pictomancer_settings_updated' );
		return $merged_settings;
	}

	public function render_api_url_field() {
		$options = get_option( 'pictomancer_settings' );
		$network_options = get_site_option( 'pictomancer_network_settings' );
		$api_url = $options['api_url'] ?? $network_options['api_url'] ?? '';
		$from_constant = defined( 'PICTOMANCER_API_URL' ) && PICTOMANCER_API_URL !== '';
		?>
		<input type='text' name='pictomancer_settings[api_url]' value='<?php echo esc_attr( $from_constant ? PICTOMANCER_API_URL : $api_url ); ?>' class='regular-text' placeholder='https://api.pictomancer.ai' <?php disabled( $from_constant ); ?>>
		<?php if ( $from_constant ) : ?>
			<p class="description"><?php esc_html_e( 'Defined by the PICTOMANCER_API_URL constant (wp-config.php / server environment). This field is read-only.', 'pictomancer-image-optimizer' ); ?></p>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'API host root. Leave empty to use https://api.pictomancer.ai. Do not append /v1.', 'pictomancer-image-optimizer' ); ?></p>
		<?php endif; ?>
		<?php if ( is_multisite() && get_site_option( 'pictomancer_network_settings' )['api_url'] ?? false ) : ?>
			<p class="description"><?php esc_html_e( 'This setting overrides the network-wide setting.', 'pictomancer-image-optimizer' ); ?></p>
		<?php endif; ?>
		<?php
	}

	public function render_api_key_field() {
		$options = get_option( 'pictomancer_settings' );
		$from_constant = defined( 'PICTOMANCER_API_KEY' ) && PICTOMANCER_API_KEY !== '';
		?>
		<input type='password' name='pictomancer_settings[api_key]' value='<?php echo $from_constant ? '' : esc_attr( $options['api_key'] ?? '' ); ?>' class='regular-text' autocomplete='off' placeholder='<?php echo $from_constant ? esc_attr__( 'Set via PICTOMANCER_API_KEY', 'pictomancer-image-optimizer' ) : ''; ?>' <?php disabled( $from_constant ); ?>>
		<?php if ( $from_constant ) : ?>
			<p class="description"><?php esc_html_e( 'Defined by the PICTOMANCER_API_KEY constant (wp-config.php / server environment). This field is read-only and the secret is never stored in the database.', 'pictomancer-image-optimizer' ); ?></p>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'Bearer API key from your Pictomancer dashboard. Leave empty to use the free tier.', 'pictomancer-image-optimizer' ); ?></p>
		<?php endif; ?>
		<?php
	}

	public function render_quality_field() {
		$options = get_option( 'pictomancer_settings' );
		?>
		<input type='number' min='0' max='100' name='pictomancer_settings[quality]' value='<?php echo esc_attr( $options['quality'] ?? '' ); ?>' class='small-text'>
		<p class="description"><?php esc_html_e( 'Compression quality 1-100. Leave empty for the API default.', 'pictomancer-image-optimizer' ); ?></p>
		<?php
	}

	public function render_optimize_thumbnails_field() {
		$options = get_option( 'pictomancer_settings' );
		?>
		<input type='hidden' name='pictomancer_settings[optimize_thumbnails]' value='0'>
		<input type='checkbox' name='pictomancer_settings[optimize_thumbnails]' value='1' <?php checked( 1, $options['optimize_thumbnails'] ?? 1 ); ?>>
		<label><?php esc_html_e( 'Optimize every generated thumbnail size, not just the original upload. Each size is billed as one API request.', 'pictomancer-image-optimizer' ); ?></label>
		<?php
	}

	public function render_debug_mode_field() {
		$options = get_option( 'pictomancer_settings' );
		$network_options = get_site_option( 'pictomancer_network_settings' );
		$debug_mode = $options['debug_mode'] ?? $network_options['debug_mode'] ?? 0;
		?>
		<input type='checkbox' name='pictomancer_settings[debug_mode]' value='1' <?php checked( 1, $debug_mode ); ?>>
		<label><?php esc_html_e( 'Enable debug logging', 'pictomancer-image-optimizer' ); ?></label>
		<?php if ( is_multisite() && get_site_option( 'pictomancer_network_settings' )['debug_mode'] ?? false ) : ?>
			<p class="description"><?php esc_html_e( 'This setting overrides the network-wide setting.', 'pictomancer-image-optimizer' ); ?></p>
		<?php endif; ?>
		<?php
	}

	public function render_requeue_failed_field() {
		$options = get_option( 'pictomancer_settings' );
		?>
		<input type='checkbox' name='pictomancer_settings[requeue_failed]' value='1' <?php checked( 1, $options['requeue_failed'] ?? 0 ); ?>>
		<label><?php esc_html_e( 'Requeue failed optimizations via WP Cron', 'pictomancer-image-optimizer' ); ?></label>
		<?php
	}

	public function render_enable_graphql_field() {
		$options = get_option( 'pictomancer_settings' );
		?>
		<input type='checkbox' name='pictomancer_settings[enable_graphql]' value='1' <?php checked( 1, $options['enable_graphql'] ?? 0 ); ?>>
		<label><?php esc_html_e( 'Enable WPGraphQL integration', 'pictomancer-image-optimizer' ); ?></label>
		<?php
	}

	public function render_enable_woocommerce_field() {
		$options = get_option( 'pictomancer_settings' );
		?>
		<input type='checkbox' name='pictomancer_settings[enable_woocommerce]' value='1' <?php checked( 1, $options['enable_woocommerce'] ?? 0 ); ?>>
		<label><?php esc_html_e( 'Enable WooCommerce integration', 'pictomancer-image-optimizer' ); ?></label>
		<?php
	}
}