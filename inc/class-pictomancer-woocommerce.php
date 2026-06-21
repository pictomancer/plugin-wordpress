<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pictomancer_WooCommerce {

	public function __construct() {
		// Check if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_action( 'woocommerce_new_product', [ $this, 'optimize_product_images' ], 10, 1 );
		add_action( 'woocommerce_update_product', [ $this, 'optimize_product_images' ], 10, 1 );
	}

	public function optimize_product_images( $product_id ) {
		$options = get_option( 'pictomancer_settings' );
		if ( ! ( isset( $options['enable_woocommerce'] ) && $options['enable_woocommerce'] ) ) {
			return;
		}

		do_action( 'pictomancer_log', sprintf( 'Optimizing images for WooCommerce product ID: %d', $product_id ), 'info' );

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return;
		}

		$image_ids = [];

		// Featured image
		$featured_image_id = $product->get_image_id();
		if ( $featured_image_id ) {
			$image_ids[] = $featured_image_id;
		}

		// Gallery images
		$gallery_image_ids = $product->get_gallery_image_ids();
		if ( ! empty( $gallery_image_ids ) ) {
			$image_ids = array_merge( $image_ids, $gallery_image_ids );
		}

		$image_ids = array_unique( array_filter( $image_ids ) );

		if ( empty( $image_ids ) ) {
			do_action( 'pictomancer_log', sprintf( 'No images found for WooCommerce product ID: %d', $product_id ), 'info' );
			return;
		}

		foreach ( $image_ids as $image_id ) {
			$file_path = get_attached_file( $image_id );
			if ( $file_path ) {
				do_action( 'pictomancer_log', sprintf( 'Attempting to optimize WooCommerce image: %s (ID: %d)', basename( $file_path ), $image_id ), 'info' );
				// Call the optimization function directly or via a hook
				// For now, we'll just log it. The actual optimization will be handled by the wp_handle_upload filter.
				// If the image is already in the media library, it would have been optimized on upload.
				// This part is more for re-optimization or if images are added programmatically without wp_handle_upload.
			}
		}
	}
}

new Pictomancer_WooCommerce();
