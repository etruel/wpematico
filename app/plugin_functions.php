<?php
/**
 * WPeMatico plugin for WordPress
 * plugin_functions
 * Contains all the hooks to be called for the plugins wordpress page and the activate/deactivate/uninstall methods.

 * @package   wpematico
 * @link      https://github.com/etruel/wpematico
 * @author    Esteban Truelsegaard <etruel@etruel.com>
 * @copyright 2006-2019 Esteban Truelsegaard
 * @license   GPL v2 or later
 */

// don't load directly 
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

add_filter(	'plugin_row_meta', 'wpematico_row_meta',10,2);
add_filter(	'plugin_action_links_' . WPEMATICO_BASENAME, 'wpematico_action_links');
add_action( "after_plugin_row_" . WPEMATICO_BASENAME, 'wpematico_update_row', 10, 2 );
add_action( 'admin_head', 'WPeMatico_plugins_admin_head' );

function WPeMatico_plugins_admin_head(){
	global $pagenow, $page_hook;
	if($pagenow=='plugins.php'){
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($){
			$('tr[data-slug=wpematico]').addClass('update');
		});
	</script>
	<style type="text/css">
		@media screen and (max-width: 782px) {
			#wpematico_addons_row{
			      display: none;
			}
		}
		.wpematico_active_addon {
			padding: 1px 5px;
			background: orange;
			border-radius: 5px;
			margin: 0 3px;
}
		.wpematico_addon {
			padding: 1px 5px;
			background: #8f7444;
			border-radius: 5px;
			margin: 0 3px;
}
	</style>
	<?php 
	}
}


/**
* Actions-Links del Plugin
*
* @param   array   $data  Original Links
* @return  array   $data  modified Links
*/
function wpematico_update_row( $file, $plugin_data ) {
	$plugins = get_plugins();
	$addons=read_wpem_addons( $plugins );
	$installed = "";
	foreach($addons as $key => $plugin) {
		if( !isset($plugin['installed'])) {
			unset( $addons[ $key ] );
		}elseif( $plugin['installed']) {
			if(is_plugin_active($key)){
				$installed .= '<span class="wpematico_active_addon">'.str_replace("WPeMatico", "",$plugin['Name']).'</span> ';
			}else{
				$installed .= '<span class="wpematico_addon">'.str_replace("WPeMatico", "",$plugin['Name']).'</span> ';
			}
		}
	}
	if(empty($installed)) $installed = '<span class="wpematico_addon">'.__('None.', 'wpematico' ).'</span> ';
	echo '<tr id="wpematico_addons_row" class="plugin-update-tr active" data-slug="wpematico-active-extensions">';
	echo '<td class="plugin-update colspanchange" colspan="5">';
	echo '<div class="notice inline notice-success notice-alt">';
	$allowed_tags = array('span' => array('class' => array(), 'id' => array()),'p'=> array());
	echo '<a href="'. esc_attr(admin_url('plugins.php?page=wpemaddons')).'" target="_self" title="' . esc_attr__('Open Extensions plugins page:', 'wpematico' ).'">' . esc_html__('Installed Extensions:', 'wpematico' ).'</a>' . wp_kses($installed, $allowed_tags);
	echo '</div>';
	echo '</td>';
	echo '</tr>';
}

/**
* Actions-Links del Plugin
*
* @param   array   $data  Original Links
* @return  array   $data  modified Links
*/
function wpematico_action_links($data)	{
	if ( !current_user_can('manage_options') ) {
		return $data;
	}
	return array_merge(	$data,	array(
		'<a href="edit.php?post_type=wpematico&page=wpematico_settings" title="' . __('Load WPeMatico Settings Page', 'wpematico' ) . '">' . __('Settings', 'wpematico' ) . '</a>',
		'<a href="https://etruel.com/downloads/wpematico-perfect-package/" target="_Blank" title="' . __('Take a look at the all bundled Addons', 'wpematico' ) . '">' . __('Go Perfect', 'wpematico' ) . '</a>',
		'<a href="https://github.com/etruel/wpematico" target="_blank"><b>GitHub</b></a>',
//		'<a href="https://etruel.com/checkout?edd_action=add_to_cart&download_id=4313&edd_options[price_id]=2" target="_Blank" title="' . __('Buy all bundled Addons', 'wpematico' ) . '">' . __('Go Perfect', 'wpematico' ) . '</a>',
	));
}

/**
* Meta-Links del Plugin
*
* @param   array   $data  Original Links
* @param   string  $page  plugin actual
* @return  array   $data  modified Links
*/

function wpematico_row_meta($data, $page)	{
	if ( $page != WPEMATICO_BASENAME ) {
		return $data;
	}

	return array_merge(	$data,	array(
		//'<a href="http://www.wpematico.com/wpematico/" target="_blank">' . __('Info & comments') . '</a>',
		'<a href="'.  admin_url('plugins.php?page=wpemaddons').'" target="_self">' . __('Extensions', 'wpematico' ) . '</a>',
		'<a href="https://etruel.com/my-account/support/" target="_blank">' . __('Support', 'wpematico' ) . '</a>',
		'<a href="https://wordpress.org/support/view/plugin-reviews/wpematico?filter=5&rate=5#new-post" target="_Blank" title="Rate 5 stars on Wordpress.org">' . __('Rate Plugin', 'wpematico' ) . '</a>',
		'<strong><a href="https://etruel.com/downloads/wpematico-essentials/" target="_Blank" title="' . __('Take a look at the Essentials features', 'wpematico' ) . '">' . __('Go PRO', 'wpematico' ) . '</a></strong>',
//		'<p>' . __('Activated Extensions:', 'wpematico' ) . '</p>',
	));
}		



/***************************************************************************************
/***************************************************************************************
 * Activation, Upgrading and uninstall functions
 **************************************************************************************/
register_activation_hook( WPEMATICO_BASENAME, 'wpematico_activate' );
register_deactivation_hook( WPEMATICO_BASENAME, 'wpematico_deactivate' );
register_uninstall_hook( WPEMATICO_BASENAME, 'wpematico_uninstall' );

add_action( 'plugins_loaded', 'wpematico_update_db_check' );

function wpematico_update_db_check() {
	if (version_compare(WPEMATICO_VERSION, get_option( 'wpematico_db_version' ), '>')) { // check if updated (WILL SAVE new version on welcome )
		if ( !get_transient( '_wpematico_activation_redirect' ) ){ //just one time running
	        wpematico_install( false );  // true will re-save all the campaigns 
		}
		delete_option('wpematico_lastlog_disabled');
    }
}


function wpematico_install( $update_campaigns = false ){
	if($update_campaigns) {
		//tweaks old campaigns data, now saves meta for columns
		$campaigns_data = array();
		$args = array(
			'orderby'         => 'ID',
			'order'           => 'ASC',
			'post_type'       => 'wpematico', 
			'numberposts' => -1
		);
		if ( ! has_filter( 'wpematico_check_campaigndata' ) ) {
			add_filter( 'wpematico_check_campaigndata', array('WPeMatico','check_campaigndata'), 10, 1);
		}
		add_filter( 'wpematico_check_campaigndata', 'wpematico_campaign_compatibilty_after', 99, 1);
		$campaigns = get_posts( $args );
		foreach( $campaigns as $post ):
			$campaigndata = WPeMatico::get_campaign( $post->ID );	
			WPeMatico::update_campaign($post->ID, $campaigndata);
		endforeach; 
	}
	
	$version = rtrim(WPEMATICO_VERSION, '.0');
	$v = explode('.', $version);
	// Redirect to welcome page only in Major Updates
	if ( count($v) <= 2 ) {
		// Add the transient to redirect 
		set_transient( '_wpematico_activation_redirect', true, 120 ); // After two minutes lost welcome screen
	} else {
		update_option( 'wpematico_db_version', WPEMATICO_VERSION, false );
	}
	

}
/**
* This function will be hooked after @check_campaigndata and only on install or update of wpematico .
* @param $campaigndata an array with all campaign data.
* @return $campaigndata an array with filtered campaign data to compatibility.
* @since 1.9.0
*/
function wpematico_campaign_compatibilty_after($campaigndata) {
	$wpematico_version = get_option( 'wpematico_db_version' );	
	/**
	* Compatibility with enable convert to UTF-8
	* @since 1.9.0
	*/
	if (version_compare($wpematico_version, '1.9', '<')) {
		$campaigndata['campaign_enable_convert_utf8'] = true;
	}

	/**
	* Compatibility with previous image processing.
	* @since 1.7.0
	*/
	if (version_compare($wpematico_version, '1.6.4', '<=')) {
		if ($campaigndata['campaign_imgcache']) {
			$campaigndata['campaign_no_setting_img'] = true;
		}
	}
	$campaign_cancel_imgcache = (!isset($post_data['campaign_cancel_imgcache']) || empty($post_data['campaign_cancel_imgcache'])) ? false: (($post_data['campaign_cancel_imgcache']==1) ? true : false);
	if ($campaign_cancel_imgcache) {
		$campaigndata['campaign_no_setting_img'] = true;
		$campaigndata['campaign_imgcache'] = false;
		$campaigndata['campaign_attach_img'] = false;
		$campaigndata['campaign_featuredimg'] = false;
		$campaigndata['campaign_rmfeaturedimg'] = false;
		$campaigndata['campaign_customupload'] = false;
		if ($campaigndata['campaign_nolinkimg']) {
			$campaigndata['campaign_imgcache'] = true;
		}
	}
	
	return $campaigndata;
}

/**
 * activation
 * @return void
 */
function wpematico_activate() {
	WPeMatico :: Create_campaigns_page();
	// ATTENTION: This is *only* done during plugin activation hook // You should *NEVER EVER* do this on every page load!!
	flush_rewrite_rules();
	
	// Call installation and update routines
    wpematico_install();
	
	wp_clear_scheduled_hook('wpematico_cron');
	//make schedule
	wp_schedule_event(0, 'wpematico_int', 'wpematico_cron'); 
}

/**
 * deactivation
 * @return void
 */
function wpematico_deactivate() {
	//remove cron job
	wp_clear_scheduled_hook('wpematico_cron');
	// Don't delete options or campaigns
}

/**
 * Uninstallation
 * @global $wpdb, $blog_id
 * @return void
 */
function wpematico_uninstall() {
	global $wpdb, $blog_id;
	$danger = get_option( 'WPeMatico_danger', array());
	$danger['wpemdeleoptions']	 = (isset($danger['wpemdeleoptions']) && !empty($danger['wpemdeleoptions']) ) ? $danger['wpemdeleoptions'] : false;
	$danger['wpemdelecampaigns'] = (isset($danger['wpemdelecampaigns']) && !empty($danger['wpemdelecampaigns']) ) ? $danger['wpemdelecampaigns'] : false;
	if ( is_network_admin() && $danger['wpemdeleoptions'] ) {
		if ( isset ( $wpdb->blogs ) ) {
			$blogs = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT blog_id ' .
					'FROM ' . $wpdb->blogs . ' ' .
					"WHERE blog_id <> '%s'",
					$blog_id
				)
			);
			foreach ( $blogs as $blog ) {
				delete_blog_option( $blog->blog_id, WPeMatico :: OPTION_KEY );
			}
		}
	}
	if ($danger['wpemdeleoptions']) {
		delete_option( WPeMatico :: OPTION_KEY );
		delete_option( 'wpematico_db_version' );
	}
	//delete campaigns
	if($danger['wpemdelecampaigns']) {
		$args = array( 'post_type' => 'wpematico', 'orderby' => 'ID', 'order' => 'ASC' );
		$campaigns = get_posts( $args );
		foreach( $campaigns as $post ) {
			wp_delete_post( $post->ID, true);  // forces delete to avoid trash
		}
	}
}