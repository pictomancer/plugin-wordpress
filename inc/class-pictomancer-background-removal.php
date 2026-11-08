<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "Remove background" action in the Media Library: list-mode row action and a
 * button in the attachment details. The cutout is saved as a new png
 * attachment; the original is never modified.
 */
class Pictomancer_Background_Removal {

	const ACTION = 'pictomancer_remove_background';

	public function __construct() {
		add_filter( 'media_row_actions', [ $this, 'add_row_action' ], 10, 2 );
		add_filter( 'attachment_fields_to_edit', [ $this, 'add_attachment_field' ], 10, 2 );
		add_action( 'admin_post_' . self::ACTION, [ $this, 'handle_remove_background' ] );
		add_action( 'admin_notices', [ $this, 'render_result_notice' ] );
	}

	public function add_row_action( $actions, $post ) {
		if ( $this->is_available( $post ) ) {
			$actions[ self::ACTION ] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $this->action_url( $post->ID ) ),
				esc_html__( 'Remove background', 'pictomancer-image-optimizer' )
			);
		}

		return $actions;
	}

	public function add_attachment_field( $fields, $post ) {
		if ( $this->is_available( $post ) ) {
			$fields[ self::ACTION ] = [
				'label' => __( 'Pictomancer', 'pictomancer-image-optimizer' ),
				'input' => 'html',
				'html'  => sprintf(
					'<a class="button" href="%s">%s</a>',
					esc_url( $this->action_url( $post->ID ) ),
					esc_html__( 'Remove background', 'pictomancer-image-optimizer' )
				),
			];
		}

		return $fields;
	}

	public function handle_remove_background() {
		$attachment_id = absint( $_GET['attachment_id'] ?? 0 );

		check_admin_referer( self::ACTION . '_' . $attachment_id );

		$post = get_post( $attachment_id );
		if ( ! $post || ! $this->is_available( $post ) ) {
			wp_die( esc_html__( 'You cannot remove the background of this image.', 'pictomancer-image-optimizer' ), 403 );
		}

		$cutout_id = $this->create_cutout( $attachment_id );

		wp_safe_redirect( add_query_arg( 'pictomancer-cutout', $cutout_id > 0 ? $cutout_id : 'failed', admin_url( 'upload.php' ) ) );
		exit;
	}

	public function render_result_notice() {
		if ( ! isset( $_GET['pictomancer-cutout'] ) ) {
			return;
		}

		$cutout_id = absint( $_GET['pictomancer-cutout'] );
		if ( $cutout_id > 0 ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s <a href="%s">%s</a></p></div>',
				esc_html__( 'Background removed: the cutout was added to the Media Library as a new image.', 'pictomancer-image-optimizer' ),
				esc_url( (string) get_edit_post_link( $cutout_id ) ),
				esc_html__( 'Open it', 'pictomancer-image-optimizer' )
			);
			return;
		}

		printf(
			'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
			esc_html__( 'Background removal failed. Turn on debug mode in the Pictomancer settings to see why.', 'pictomancer-image-optimizer' )
		);
	}

	private function is_available( $post ): bool {
		$settings = get_option( 'pictomancer_settings', [] );

		// Same opt-in gate as optimization: no image leaves the site until the
		// admin enables the plugin.
		return (bool) ( $settings['enabled'] ?? false )
			&& wp_attachment_is_image( $post )
			&& current_user_can( 'upload_files' )
			&& current_user_can( 'edit_post', $post->ID );
	}

	private function action_url( int $attachment_id ): string {
		return wp_nonce_url(
			add_query_arg( [ 'action' => self::ACTION, 'attachment_id' => $attachment_id ], admin_url( 'admin-post.php' ) ),
			self::ACTION . '_' . $attachment_id
		);
	}

	private function create_cutout( int $attachment_id ): int {
		$service = new Pictomancer_Optimizer_Service(
			Pictomancer_Client_Factory::create( get_option( 'pictomancer_settings', [] ), $this->integration() )
		);

		$path = (string) get_attached_file( $attachment_id );
		$size = $path !== '' && file_exists( $path ) ? filesize( $path ) : false;
		if ( $size === false ) {
			do_action( 'pictomancer_log', sprintf( 'Background removal skipped for attachment %d: file not readable', $attachment_id ), 'error' );
			return 0;
		}

		if ( $service->exceeds_limit( $size ) ) {
			do_action( 'pictomancer_log', sprintf( 'Background removal skipped for %s: file too large (%d bytes)', basename( $path ), $size ), 'info' );
			return 0;
		}

		$bytes = file_get_contents( $path );
		if ( $bytes === false ) {
			do_action( 'pictomancer_log', sprintf( 'Failed to read %s', $path ), 'error' );
			return 0;
		}

		try {
			$cutout = $service->remove_background_bytes( $bytes, (string) get_post_mime_type( $attachment_id ) );
		} catch ( \Pictomancer\PictomancerException $e ) {
			do_action( 'pictomancer_log', sprintf( 'Background removal failed for %s: %s', basename( $path ), $e->getMessage() ), 'error' );
			return 0;
		}

		$upload = wp_upload_bits( $service->cutout_filename( basename( $path ) ), null, $cutout );
		if ( $upload['error'] ) {
			do_action( 'pictomancer_log', sprintf( 'Failed to save cutout of %s: %s', basename( $path ), $upload['error'] ), 'error' );
			return 0;
		}

		$cutout_id = wp_insert_attachment(
			[
				'post_mime_type' => 'image/png',
				/* translators: %s: title of the original image */
				'post_title'     => sprintf( __( '%s (no background)', 'pictomancer-image-optimizer' ), get_the_title( $attachment_id ) ),
				'post_status'    => 'inherit',
			],
			$upload['file'],
			0,
			true
		);
		if ( is_wp_error( $cutout_id ) ) {
			do_action( 'pictomancer_log', sprintf( 'Failed to create cutout attachment for %s: %s', basename( $path ), $cutout_id->get_error_message() ), 'error' );
			return 0;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $cutout_id, wp_generate_attachment_metadata( $cutout_id, $upload['file'] ) );

		do_action( 'pictomancer_log', sprintf( 'Removed background of %s: attachment %d', basename( $path ), $cutout_id ), 'info' );

		return $cutout_id;
	}

	private function integration(): string {
		return 'wordpress-plugin/' . PICTOMANCER_VERSION . ' wp/' . get_bloginfo( 'version' );
	}
}
