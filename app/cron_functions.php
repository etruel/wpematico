<?php
// don't load directly 
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
* Add cron interval
* This function adds the wpematico schedule to the WP Cron Schedules.
* @param array $schedules
* @return array
*/
function wpematico_intervals($schedules) {
	$schedule = apply_filters('wpematico_cron_schedule_values', 
		array(	
			'interval' => apply_filters('wpematico_cron_schedule_interval', '300'), 
			'display' => 'WPeMatico'
		)
	);
	$schedules['wpematico_int'] = $schedule;
	return $schedules;
}

function wpem_cron_callback() {
	// Global anti-overlap lock: if a previous cron pass is still running, skip this
	// one entirely so two passes never iterate the campaigns in parallel. The TTL is
	// a safety net in case a pass dies before clearing it. (2.8.22)
	if ( get_transient( 'wpem_cron_running' ) ) {
		return;
	}
	set_transient( 'wpem_cron_running', time(), (int) apply_filters( 'wpem_cron_lock_ttl', 10 * MINUTE_IN_SECONDS ) );

	$args = array( 'post_type' => 'wpematico', 'orderby' => 'ID', 'order' => 'ASC', 'post_status' => 'publish', 'numberposts' => -1 );
	$campaigns = get_posts( $args );
	foreach( $campaigns as $post ) {
		$activated = false;
		$cronnextrun = '';
		$campaign = WPeMatico :: get_campaign( $post->ID );
		$activated = $campaign['activated'];
		$cronnextrun = $campaign['cronnextrun'];
		if ( !$activated )
			continue;
		if ( $cronnextrun <= time() ) {
			// Skip campaigns already running (claimed by another pass/run). (2.8.22)
			if ( WPeMatico :: is_campaign_running( $post->ID ) ) {
				continue;
			}
			WPeMatico :: wpematico_dojob( $post->ID );
		}
	}

	delete_transient( 'wpem_cron_running' );
}
