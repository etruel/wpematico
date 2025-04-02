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
add_action('admin_footer', 'admin_footer');
add_action('admin_print_styles-plugins.php', 'admin_styles');
add_action('admin_print_styles-plugins.php', 'admin_styles');
add_action('wp_ajax_handle_feedback_submission', 'handle_feedback_submission');


function admin_styles() {
	global $pagenow, $page_hook;
	if($pagenow=='plugins.php' && !isset($_GET['page'])){
		wp_register_style('WPematStylesheetPlugins', WPEMATICO_PLUGIN_URL . 'app/css/wpemat_plugin_styles.css');
		wp_enqueue_style( 'WPematStylesheetPlugins' );
	}
    
}

function admin_footer(){
	global $pagenow, $page_hook;

	if($pagenow=='plugins.php' && !isset($_GET['page'])){	
	?>
	<div id="wpe_feedback" class="wpe_modal_log-box fade" style="display:none;">
		<div class="wpe_modal_log-body">
			<a id="skip_feedback" style="color:lightgray" href="#"><?php _e('Skip & deactivate', 'wpematico') ?></a>
			<a href="JavaScript:void(0);" class="wpe_modal_log-close" onclick="jQuery('#wpe_feedback').fadeToggle().removeClass('active'); jQuery('body').removeClass('wpe_modal_log-is-active');">
				<span class="dashicons dashicons-no-alt"></span>
			</a>
			<div class="wpe_modal_log-header">
				<h3><?php esc_html_e('Quick Feedback', 'wpematico') ?></h3>
			</div>
			<div class="wpe_modal_log-content">
				<h3><?php esc_html_e("We’d love to know why you're deactivating. Your feedback helps us improve!", 'wpematico') ?></h3>
				<form id="feedback_form">
					<label>
						<input type="radio" name="deactivation_reason" value="short_period" required>
						<?php esc_html_e('✅ I only needed the plugin temporarily', 'wpematico') ?>
					</label><br>
					<label>
						<input type="radio" name="deactivation_reason" value="temporary_deactivation">
						<?php esc_html_e('🔧 I’m troubleshooting an issue and will likely reactivate it', 'wpematico') ?>
					</label><br>
					<label>
						<input type="radio" name="deactivation_reason" value="stopped_working">
						<?php esc_html_e("⚡ The plugin isn’t working as expected (we can help fix it!)", 'wpematico') ?>
					</label><br>
					<label>
						<input type="radio" name="deactivation_reason" value="broke_site" id="broke_site_radio">
						<?php esc_html_e('❌ The plugin caused issues on my site (let us know so we can resolve them)', 'wpematico') ?>
					</label><br>
					<label>
						<input type="radio" name="deactivation_reason" value="another_plugin">
						<?php esc_html_e('🔄 I’m switching to another plugin (tell us what’s missing, and we may add it!)', 'wpematico') ?>
					</label><br>
					<label>
						<input type="radio" name="deactivation_reason" value="no_longer_needed">
						<?php esc_html_e('🤷 I no longer need it', 'wpematico') ?>
					</label><br>
					<label>
						<input type="radio" name="deactivation_reason" value="other">
						<?php esc_html_e('📝 Other', 'wpematico') ?>
					</label>
					<div id="other_reason_div" style="margin-left:40px; display: none;">
						<label for="other_reason">
							<b><?php esc_html_e('Tell us more about your experience: (Optional)', 'wpematico') ?></b>
						</label><br>
						<textarea name="explicit_reason" id="other_reason" style="width: 100%;"></textarea>
					</div>
					<div class="form_footer">
						<div style="float: left; margin-left: 20px;"><?php printf( esc_html__('💡 Remember, we offer FREE support on %s.', 'wpematico'),
							'<a href="https://etruel.com/my-account/support/" target="_blank" rel="noopener noreferrer" class="support-link">' . esc_html__('Our Site', 'wpematico') . '</a>');
							esc_html_e(' – we’re happy to help!', 'wpematico'); ?>
						</div>
						<button id="send_feedback" type="submit" class="button"><?php _e('Send & deactivate', 'wpematico') ?></button>
					</div>
				</form>
			</div>
		</div>
	</div>
	<script type="text/javascript">
		jQuery('#deactivate-wpematico').on('click', function(e){
			e.preventDefault();
			jQuery('#wpe_feedback').fadeToggle().addClass('active');
			jQuery('body').addClass('wpe_modal_log-is-active');
		});

		jQuery('[name=deactivation_reason]').on("change", function () {
			var $otherReasonDiv = jQuery('#other_reason_div');
			$otherReasonDiv.hide();
			
			let selected = jQuery('input[name="deactivation_reason"]:checked').val();
			let tellmore = ['stopped_working', 'broke_site', 'another_plugin', 'no_longer_needed', 'other'];
			
			if (tellmore.includes(selected)) {
				// Move the div immediately after the selected radio label
				$otherReasonDiv.insertAfter(jQuery(this).closest('label'));
				$otherReasonDiv.fadeIn();
			}
		});
		
		jQuery('#feedback_form').on('submit', function(e) {
			e.preventDefault(); // Prevent form from submitting the default way
			var reason = jQuery('input[name="deactivation_reason"]:checked').val(); // Get the selected reason
			var explicit_reason = jQuery('#other_reason').val();

			// Send AJAX request to the server
			jQuery.ajax({
				url: ajaxurl, // WordPress AJAX URL
				type: 'POST',
				data: {
					action: 'handle_feedback_submission', // The PHP function to handle the feedback
					reason: reason,
					explicit_reason: explicit_reason
				},
				success: function(response) {
					if(response.success) {
						// Close the feedback modal
						jQuery('#wpe_feedback').fadeToggle().removeClass('active');
						jQuery('body').removeClass('wpe_modal_log-is-active');

						// Reload the page and display the success message
						alert(response.data.message); // Display the success message
						location.reload(); // Reload the page
					} else {
						alert('Error: ' + response.data); // Handle errors, if any
					}
				},
				error: function() {
					alert('An unexpected error occurred.');
				}
			});
		});

		jQuery('#skip_feedback').on('click', function() {
		   // Send AJAX request to the server
		   jQuery.ajax({
				url: ajaxurl, // WordPress AJAX URL
				type: 'POST',
				data: {
					action: 'handle_feedback_submission', // The PHP function to handle the feedback
					reason: 'skipped'
				},
				success: function(response) {
					if(response.success) {
						// Close the feedback modal
						jQuery('#wpe_feedback').fadeToggle().removeClass('active');
						jQuery('body').removeClass('wpe_modal_log-is-active');

						// Reload the page and display the success message
						alert(response.data.message); // Display the success message
						location.reload(); // Reload the page
					} else {
						alert('Error: ' + response.data); // Handle errors, if any
					}
				},
				error: function() {
					alert('An unexpected error occurred.');
				}
			});
		});
    </script>
    <?php
    }
}
function handle_feedback_submission() {
    // Check if the user has permission to deactivate plugins
    if (!current_user_can('activate_plugins')) {
        wp_send_json_error('Unauthorized user.');
    }

    // Get the feedback reason from the form
    $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';
	$explicit_reason = isset($_POST['explicit_reason']) ? sanitize_text_field($_POST['explicit_reason']) : '';
    // Prepare the email
    $to = 'hello@etruel.com';
    $subject = 'Plugin Deactivation Feedback';
    $message = "A user deactivated the plugin for the following reason: " . $reason;
	if($explicit_reason != '')
		$message .= "\n\nThe user wrote: $explicit_reason";
	
    $headers = ['From: ' . get_bloginfo('name') . ' <wordpress@' . parse_url(home_url(), PHP_URL_HOST) . '>'];

    // Send the email
    if ($reason == 'skipped' || wp_mail($to, $subject, $message, $headers)) {
        // Deactivate the plugin
        deactivate_plugins('wpematico/wpematico.php'); // Specify your plugin's path here

        // Return success message
        wp_send_json_success(['message' => esc_html__('Deactivated successfully','wpematico') ]);
    } else {
        // Return error message if email fails
        wp_send_json_error('Failed to send email.');
    }
}


function WPeMatico_plugins_admin_head(): void{
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

add_action('wp_ajax_fetch_taxonomies', 'fetch_taxonomies');

function fetch_taxonomies() {
    if (!isset($_POST['post_type'], $_POST['post_id'])) {
        wp_die('No post type or post ID provided.');
    }

    $post_type = sanitize_text_field($_POST['post_type']);
    $post_id = intval($_POST['post_id']); // Ensure post ID is an integer
    $taxonomies = get_object_taxonomies($post_type, 'objects');
	
    if (!empty($taxonomies)) {
        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy->hierarchical) { // Only show hierarchical taxonomies
                echo '<span class="title inline-edit-categories-label">' . esc_html($taxonomy->labels->name) . '</span>';
                echo '<input type="hidden" name="tax_input[' . esc_attr($taxonomy->name) . '][]" value="0" />'; // Default value 0
                echo '<ul class="cat-checklist ' . esc_attr($taxonomy->name) . '-checklist">';
                get_campaign_tax($taxonomy->name);
                echo '</ul>';
            }
        }
    }

    wp_die(); // Properly terminate the AJAX request
}

add_action('wp_ajax_fetch_tags', 'fetch_tags');

function fetch_tags() {
    if (!isset($_POST['post_type'])) {
        wp_die('No post type provided.');
    }

    $post_type = sanitize_text_field($_POST['post_type']);

    // Get the taxonomies for the selected post type
    $taxonomy_names = get_object_taxonomies($post_type);
    $flat_taxonomies = array();
    
    foreach ($taxonomy_names as $taxonomy_name) {
        $taxonomy = get_taxonomy($taxonomy_name);
        if (!$taxonomy->show_ui)
            continue;

        if (!$taxonomy->hierarchical)
            $flat_taxonomies[] = $taxonomy;
    }

    // Output taxonomies
    $html = '';
    
    if (count($flat_taxonomies)) {
		
        foreach ($flat_taxonomies as $taxonomy) {
            if (current_user_can($taxonomy->cap->assign_terms)) {
				$current_tags = get_campaign_tags($taxonomy->name);
				if($taxonomy->name != 'post_tag'){
					// Create a label for each taxonomy with a textarea for the tags
					$html .= '<label class="inline-edit-tags">';
					$html .= '<span class="title">' . esc_html($taxonomy->labels->name) . '</span>';
					$html .= '<textarea cols="22" rows="1" name="tax_input['.$taxonomy->name.']" class="tax_input_' . esc_attr($taxonomy->name) . '">'. $current_tags .'</textarea>';
					$html .= '</label>';
				}else{
					// Create a label for each taxonomy with a textarea for the tags
					$html .= '<label class="inline-edit-tags">';
					$html .= '<span class="title">' . esc_html($taxonomy->labels->name) . '</span>';
					$html .= '<textarea cols="22" rows="1" name="campaign_tags" class="tax_input_' . esc_attr($taxonomy->name) . '">'. $current_tags .'</textarea>';
					$html .= '</label>';
				}
            }
        }
    }

    echo $html;
    wp_die(); // Properly terminate the AJAX request
}


/**
 * Summary of get_campaign_tags
 * @param mixed $taxonomy_name
 * @return string
 */
function get_campaign_tags($taxonomy_name) {
    if (!isset($_POST['post_id'])) {
        wp_send_json_error();
    }
	if($taxonomy_name != 'post_tag'){
		$current_tags = get_the_terms($_POST['post_id'], $taxonomy_name);
		$all_tags = array();

		if ($current_tags && !is_wp_error($current_tags)) {
			foreach ($current_tags as $tag) {
				$all_tags[] = $tag->name;
			}

			$current_tags = implode(',', $all_tags);
		}
	}else{
		$campaign_data = get_post_meta($_POST['post_id'], 'campaign_data');
		$campaign_data = (isset($campaign_data[0])) ? $campaign_data[0] : array(0);
		$tags = apply_filters('wpematico_check_campaigndata', $campaign_data);
		$current_tags = $tags['campaign_tags'];
	}
   	return  $current_tags;
	
}

function get_campaign_tax($taxonomy_name) {
    if (!isset($_POST['post_id'])) {
        wp_send_json_error();
    }
	if($taxonomy_name == 'category'){
		$campaign_data = get_post_meta($_POST['post_id'], 'campaign_data');
		$campaign_data = (isset($campaign_data[0])) ? $campaign_data[0] : array(0);
		$tags = apply_filters('wpematico_check_campaigndata', $campaign_data);
		$current_tax = $tags['campaign_categories'];

		// Check the terms for the post
		wp_terms_checklist($_POST['post_id'], $args = array(
			'taxonomy' => $taxonomy_name,
			'descendants_and_self' => 0,
			'selected_cats' => array_map('intval', $current_tax),
			'popular_cats' => false,
			'walker' => null,
			'checked_ontop' => true
		));
	}else{
		wp_terms_checklist($_POST['post_id'], array('taxonomy' => $taxonomy_name));
	}
}