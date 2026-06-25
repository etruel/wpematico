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
	// a safety net in case a pass dies before clearing it; it is refreshed before each
	// campaign below so it never expires while the pass is still alive. (2.8.22)
	if ( get_transient( 'wpem_cron_running' ) ) {
		return;
	}
	$lock_ttl = (int) apply_filters( 'wpem_cron_lock_ttl', 10 * MINUTE_IN_SECONDS );
	set_transient( 'wpem_cron_running', time(), $lock_ttl );

	$args = array( 'post_type' => 'wpematico', 'orderby' => 'ID', 'order' => 'ASC', 'post_status' => 'publish', 'numberposts' => -1 );
	$campaigns = get_posts( $args );

	// Collect the campaigns that are due to run, then run the most overdue first so a
	// slow campaign cannot keep starving the ones after it (fairness / anti-starvation). (2.8.22)
	$due = array();
	foreach ( $campaigns as $post ) {
		$campaign = WPeMatico :: get_campaign( $post->ID );
		if ( empty( $campaign['activated'] ) ) {
			continue;
		}
		if ( (int) $campaign['cronnextrun'] <= time() ) {
			$due[ $post->ID ] = (int) $campaign['cronnextrun'];
		}
	}
	asort( $due ); // oldest cronnextrun (most overdue) first

	foreach ( $due as $campaign_id => $cronnextrun ) {
		// Skip campaigns already running (claimed by another pass/run). (2.8.22)
		if ( WPeMatico :: is_campaign_running( $campaign_id ) ) {
			continue;
		}
		// Keep the global lock alive while this pass is still working, so its TTL does
		// not expire mid-pass and let an overlapping pass restart already-run campaigns. (2.8.22)
		set_transient( 'wpem_cron_running', time(), $lock_ttl );
		WPeMatico :: wpematico_dojob( $campaign_id );
	}

	delete_transient( 'wpem_cron_running' );
}
