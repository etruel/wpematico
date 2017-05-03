<?php
/**
 * Compatibilities file to add fixes to work with conflictive plugins 
 */
// don't load directly 
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

add_action('admin_enqueue_scripts', 'wpematico_compatibilities_init', 999 ); // At the end of the init action
add_filter('pre_get_posts', 'wpematico_ksuce_exclude_categories', 1, 1); // Fix Ultimate exclude categories 


function wpematico_compatibilities_init($query) {
	add_action('admin_enqueue_scripts', 'wpematico_dequeue_acf_scripts', 99 ); //Fix Advanced custom fields PRO to use WPeMatico
}
				
//Fix Ultimate exclude categories to use WPeMatico
function wpematico_ksuce_exclude_categories($query) {
	if (isset($query->query['post_type'])) {
		if ($query->query['post_type'] == 'wpematico') {
			remove_filter('pre_get_posts', 'ksuce_exclude_categories');
		}
	}
	return $query;
}

//*********************************************************************************		//Fix Advanced custom fields PRO to use WPeMatico
function wpematico_dequeue_acf_scripts() {
	global $post_type;
	if($post_type=='wpematico')
		wp_dequeue_script( 'acf-input' );
}
