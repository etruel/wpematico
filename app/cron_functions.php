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
			'display' => __('WPeMatico', 'wpematico')
		)
	);
	$schedules['wpematico_int'] = $schedule;
	return $schedules;
}

function wpem_cron_callback() {
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
		if ( $cronnextrun <= current_time('timestamp') ) {
			WPeMatico :: wpematico_dojob( $post->ID );
		}
	}
}
