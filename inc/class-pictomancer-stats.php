<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aggregate optimization savings, persisted in the `pictomancer_stats` option.
 * The arithmetic lives in pure static helpers so it can be tested without WP.
 */
class Pictomancer_Stats {

	const OPTION = 'pictomancer_stats';

	public function record( int $original_bytes, int $optimized_bytes ): void {
		update_option( self::OPTION, self::merge( $this->raw(), $original_bytes, $optimized_bytes ) );
	}

	/**
	 * @return array<string, int|float>
	 */
	public function get(): array {
		return self::summarize( $this->raw() );
	}

	/**
	 * @return array<string, int>
	 */
	private function raw(): array {
		$data = get_option( self::OPTION, [] );

		return is_array( $data ) ? $data : [];
	}

	/**
	 * Fold one optimized file into the running totals.
	 *
	 * @param array<string, int> $current
	 * @return array<string, int>
	 */
	public static function merge( array $current, int $original_bytes, int $optimized_bytes ): array {
		return [
			'files'           => (int) ( $current['files'] ?? 0 ) + 1,
			'original_bytes'  => (int) ( $current['original_bytes'] ?? 0 ) + $original_bytes,
			'optimized_bytes' => (int) ( $current['optimized_bytes'] ?? 0 ) + $optimized_bytes,
		];
	}

	/**
	 * Derive the display shape (saved bytes, reduction percentage) from raw totals.
	 *
	 * @param array<string, int> $raw
	 * @return array<string, int|float>
	 */
	public static function summarize( array $raw ): array {
		$original  = (int) ( $raw['original_bytes'] ?? 0 );
		$optimized = (int) ( $raw['optimized_bytes'] ?? 0 );
		$saved     = max( 0, $original - $optimized );

		return [
			'files'           => (int) ( $raw['files'] ?? 0 ),
			'original_bytes'  => $original,
			'optimized_bytes' => $optimized,
			'bytes_saved'     => $saved,
			'reduction_pct'   => $original > 0 ? round( $saved / $original * 100, 1 ) : 0.0,
		];
	}
}
