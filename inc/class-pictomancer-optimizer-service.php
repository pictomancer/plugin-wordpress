<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pure optimization logic, decoupled from WordPress. Takes the raw bytes of an
 * image plus its MIME type, sends them to the API as a data URI, and returns the
 * optimized bytes. No WordPress functions are used here so it can be unit tested
 * against the SDK with a fake transport.
 */
class Pictomancer_Optimizer_Service {

	/**
	 * base64 inflates by ~33%; cap the raw size so the data URI stays under the
	 * gateway's 50MB request-body limit with comfortable margin.
	 */
	const MAX_SOURCE_BYTES = 32 * 1024 * 1024;

	const MODE_FIXED          = 'fixed';
	const MODE_QUALITY_TARGET = 'quality_target';

	const DEFAULT_QUALITY_TARGET = 0.95;

	/**
	 * Formats the API accepts `quality_target` for. Anything else (png, gif,
	 * tiff) is rejected with a 422, so those keep the fixed-quality call.
	 */
	const QUALITY_TARGET_FORMATS = [
		'image/jpeg' => 'jpeg',
		'image/webp' => 'webp',
		'image/avif' => 'avif',
	];

	private \Pictomancer\Client $client;
	private int $quality;
	private string $output_format;
	private string $compression_mode;
	private float $quality_target;

	public function __construct( \Pictomancer\Client $client, int $quality = 0, string $output_format = '', string $compression_mode = self::MODE_FIXED, float $quality_target = 0.0 ) {
		$this->client           = $client;
		$this->quality          = $quality;
		$this->output_format    = strtolower( trim( $output_format ) );
		$this->compression_mode = $compression_mode;
		$this->quality_target   = $quality_target;
	}

	public function is_supported( string $mime_type ): bool {
		return str_starts_with( $mime_type, 'image/' );
	}

	/**
	 * Builds the list of attachment files to optimize from WordPress attachment
	 * metadata: the main file plus every generated size. The pristine
	 * `original_image` (kept by WP when it scales down big uploads) is never
	 * included -- it is the regeneration source and is not served. Pure: paths
	 * are computed, never touched.
	 *
	 * @param array<string, mixed> $metadata `wp_generate_attachment_metadata` payload
	 * @return list<array{path: string, mime: string, kind: string}>
	 */
	public function files_to_optimize( array $metadata, string $base_dir, string $original_mime, bool $skip_original, bool $include_sizes ): array {
		$original_rel = (string) ( $metadata['file'] ?? '' );
		if ( $original_rel === '' ) {
			return [];
		}

		$base = rtrim( $base_dir, '/' );
		$dir  = dirname( $original_rel );

		$plan = [];
		if ( ! $skip_original ) {
			$plan[] = [ 'path' => $base . '/' . $original_rel, 'mime' => $original_mime, 'kind' => 'original' ];
		}

		if ( $include_sizes ) {
			foreach ( (array) ( $metadata['sizes'] ?? [] ) as $size ) {
				$file = (string) ( $size['file'] ?? '' );
				if ( $file === '' ) {
					continue;
				}
				$rel    = $dir === '.' ? $file : $dir . '/' . $file;
				$plan[] = [
					'path' => $base . '/' . $rel,
					'mime' => (string) ( $size['mime-type'] ?? $original_mime ),
					'kind' => 'size',
				];
			}
		}

		$seen    = [];
		$deduped = [];
		foreach ( $plan as $entry ) {
			if ( isset( $seen[ $entry['path'] ] ) ) {
				continue;
			}
			$seen[ $entry['path'] ] = true;
			$deduped[]              = $entry;
		}

		return $deduped;
	}

	public function exceeds_limit( int $byte_size ): bool {
		return $byte_size > self::MAX_SOURCE_BYTES;
	}

	/**
	 * @return string optimized image bytes
	 * @throws \Pictomancer\PictomancerException on transport or API error
	 */
	public function optimize_bytes( string $bytes, string $mime_type ): string {
		$source  = $this->to_data_uri( $bytes, $mime_type );
		$options = $this->compress_options( $mime_type );

		$result = $this->client->compress( $source, $options );

		// Inline delivery returns raw bytes; this service never sets a delivery
		// target, so a non-string result means the API contract changed.
		if ( ! is_string( $result ) ) {
			throw new \Pictomancer\PictomancerException( 'expected inline bytes from compress' );
		}

		return $result;
	}

	private function to_data_uri( string $bytes, string $mime_type ): string {
		return 'data:' . $mime_type . ';base64,' . base64_encode( $bytes );
	}

	/** @return array<string, mixed> */
	private function compress_options( string $mime_type ): array {
		$target_format = $this->quality_target_format( $mime_type );
		if ( $this->compression_mode === self::MODE_QUALITY_TARGET && $target_format !== '' ) {
			// quality_target is mutually exclusive with `q` and requires an
			// explicit `format`, or the API rejects the request.
			return [
				'strip'          => true,
				'quality_target' => $this->quality_target > 0 ? $this->quality_target : self::DEFAULT_QUALITY_TARGET,
				'format'         => $target_format,
			];
		}

		$options = [ 'strip' => true ];
		if ( $this->quality > 0 ) {
			$options['q'] = $this->quality;
		}
		if ( $this->output_format !== '' ) {
			$options['format'] = $this->output_format;
		}

		return $options;
	}

	/**
	 * Resolves the explicit `format` a quality_target request must carry: the
	 * configured output format when converting, otherwise the source format so
	 * jpeg stays jpeg. Empty when the effective format does not support
	 * quality_target, which sends that file down the fixed-quality path.
	 */
	private function quality_target_format( string $mime_type ): string {
		$format = $this->output_format !== ''
			? $this->output_format
			: ( self::QUALITY_TARGET_FORMATS[ strtolower( $mime_type ) ] ?? '' );

		return in_array( $format, self::QUALITY_TARGET_FORMATS, true ) ? $format : '';
	}
}
