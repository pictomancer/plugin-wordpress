<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pictomancer_Cron {

	const CRON_HOOK = 'pictomancer_requeue_failed_optimizations';

	public function __construct() {
		add_action( self::CRON_HOOK, [ $this, 'requeue_failed_optimizations' ] );
		add_action( 'admin_init', [ $this, 'schedule_cron_job' ] );
		add_action( 'pictomancer_settings_updated', [ $this, 'schedule_cron_job' ] );
	}

	public function schedule_cron_job() {
		$options = get_option( 'pictomancer_settings' );
		if ( isset( $options['requeue_failed'] ) && $options['requeue_failed'] ) {
			if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
				wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
			}
		} else {
			if ( wp_next_scheduled( self::CRON_HOOK ) ) {
				wp_clear_scheduled_hook( self::CRON_HOOK );
			}
		}
	}

	public function requeue_failed_optimizations() {
		// Placeholder for requeue logic
		do_action( 'pictomancer_log', 'Running scheduled requeue of failed optimizations.', 'info' );
		// In a real scenario, this would query for failed optimizations and re-process them.
	}
}

new Pictomancer_Cron();
