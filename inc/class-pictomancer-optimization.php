<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pictomancer_Optimization {

	const OPTIMIZED_META_KEY = '_pictomancer_optimized';

	const RESULT_OPTIMIZED = 'optimized';
	const RESULT_SKIPPED   = 'skipped';
	const RESULT_FAILED    = 'failed';

	private Pictomancer_Stats $stats;

	public function __construct( ?Pictomancer_Stats $stats = null ) {
		$this->stats = $stats ?? new Pictomancer_Stats();
		add_filter( 'wp_generate_attachment_metadata', [ $this, 'optimize_attachment' ], 10, 2 );
	}

	/**
	 * Runs after WordPress has generated every intermediate size and before
	 * offload plugins (WP Offload Media, WP Stateless) push files to a bucket,
	 * so what reaches the customer's storage/CDN is already optimized.
	 */
	public function optimize_attachment( $metadata, $attachment_id ) {
		if ( ! is_array( $metadata ) ) {
			return $metadata;
		}

		$settings  = get_option( 'pictomancer_settings', [] );
		$mime_type = (string) get_post_mime_type( $attachment_id );

		$service = new Pictomancer_Optimizer_Service(
			Pictomancer_Client_Factory::create( $settings, $this->integration() ),
			(int) ( $settings['quality'] ?? 0 ),
			(string) ( $settings['output_format'] ?? '' ),
		);

		if ( ! $service->is_supported( $mime_type ) ) {
			do_action( 'pictomancer_log', sprintf( 'Skipping attachment %d: unsupported MIME type %s', $attachment_id, $mime_type ), 'info' );
			return $metadata;
		}

		// Regenerating thumbnails rewrites the sizes but not the main file;
		// recompressing the main file again would stack generation loss, so it
		// is optimized exactly once and remembered via post meta.
		$skip_original = get_post_meta( $attachment_id, self::OPTIMIZED_META_KEY, true ) !== '';
		$include_sizes = (bool) ( $settings['optimize_thumbnails'] ?? 1 );

		$plan = $service->files_to_optimize(
			$metadata,
			wp_get_upload_dir()['basedir'],
			$mime_type,
			$skip_original,
			$include_sizes,
		);

		foreach ( $plan as $entry ) {
			$result = $this->optimize_file( $service, $entry['path'], $entry['mime'], $attachment_id );

			// Mark the original processed on anything but a transient failure (a file
			// that cannot be made smaller would otherwise be re-fetched, and re-billed,
			// on every metadata regeneration).
			if ( $result !== self::RESULT_FAILED && $entry['kind'] === 'original' ) {
				update_post_meta( $attachment_id, self::OPTIMIZED_META_KEY, time() );
			}
		}

		return $metadata;
	}

	private function optimize_file( Pictomancer_Optimizer_Service $service, string $path, string $mime_type, int $attachment_id ): string {
		$size = file_exists( $path ) ? filesize( $path ) : false;
		if ( $size === false ) {
			do_action( 'pictomancer_log', sprintf( 'Skipping %s: not readable', basename( $path ) ), 'error' );
			return self::RESULT_FAILED;
		}

		if ( $service->exceeds_limit( $size ) ) {
			do_action( 'pictomancer_log', sprintf( 'Skipping %s: file too large to optimize (%d bytes)', basename( $path ), $size ), 'info' );
			return self::RESULT_SKIPPED;
		}

		$bytes = file_get_contents( $path );
		if ( $bytes === false ) {
			do_action( 'pictomancer_log', sprintf( 'Failed to read %s', $path ), 'error' );
			return self::RESULT_FAILED;
		}

		try {
			$optimized = $service->optimize_bytes( $bytes, $mime_type );
		} catch ( \Pictomancer\PictomancerException $e ) {
			do_action( 'pictomancer_log', sprintf( 'Optimization failed for %s: %s', basename( $path ), $e->getMessage() ), 'error' );
			return self::RESULT_FAILED;
		}

		// Keep whichever is smaller. Already-compressed files (WP thumbnails, tiny
		// PNGs) can come back larger; rewriting those would inflate, not optimize.
		$optimized_size = strlen( $optimized );
		if ( $optimized_size >= $size ) {
			do_action( 'pictomancer_log', sprintf( 'No gain for %s: %d -> %d bytes, kept original', basename( $path ), $size, $optimized_size ), 'info' );
			return self::RESULT_SKIPPED;
		}

		if ( file_put_contents( $path, $optimized ) === false ) {
			do_action( 'pictomancer_log', sprintf( 'Failed to write optimized file: %s', $path ), 'error' );
			return self::RESULT_FAILED;
		}

		$this->stats->record( $size, $optimized_size );

		do_action( 'pictomancer_log', sprintf( 'Optimized %s: %d -> %d bytes', basename( $path ), $size, $optimized_size ), 'info' );
		do_action( 'pictomancer_after_optimization', $attachment_id, $path );

		return self::RESULT_OPTIMIZED;
	}

	private function integration(): string {
		return 'wordpress-plugin/' . PICTOMANCER_VERSION . ' wp/' . get_bloginfo( 'version' );
	}
}

new Pictomancer_Optimization();
