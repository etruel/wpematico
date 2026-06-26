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
		// Skip campaigns already running (claimed by another pass/run). The per-campaign
		// atomic claim in the fetch constructor is what actually prevents duplicates; this
		// is just an early skip to avoid spinning up a fetch we know will abort. (2.8.22)
		if ( WPeMatico :: is_campaign_running( $campaign_id ) ) {
			continue;
		}
		// Re-read cronnextrun fresh right before running: a previous (slow) campaign in this
		// same pass may have taken long enough that another (overlapping) pass already claimed
		// and advanced this one's cronnextrun, so the value captured in $due above can be stale. (2.8.22)
		if ( (int) get_post_meta( $campaign_id, 'cronnextrun', true ) > time() ) {
			continue;
		}
		WPeMatico :: wpematico_dojob( $campaign_id );
	}
}
