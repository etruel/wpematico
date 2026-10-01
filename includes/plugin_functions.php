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

add_filter('plugin_row_meta', 'wpematico_row_meta', 10, 2);
add_filter('plugin_action_links_' . WPEMATICO_BASENAME, 'wpematico_action_links');
add_action("after_plugin_row_" . WPEMATICO_BASENAME, 'wpematico_update_row', 10, 2);
add_action('admin_head', 'WPeMatico_plugins_admin_head');
add_action('admin_footer', 'wff_admin_footer');
add_action('admin_print_styles-plugins.php', 'wff_admin_styles');
add_action('admin_enqueue_scripts', 'wpematico_register_modal_style', 1);
add_action('wp_ajax_handle_feedback_submission', 'wff_handle_feedback_submission');

/**
 * The modal styles, as a handle of their own on every admin screen, so that
 * WPEMATICO_VERSION busts them — an `@import` carries no version string and
 * survives every plugin update in the browser cache.
 *
 * Registered early on admin_enqueue_scripts: everything depending on it enqueues
 * later, and a dependency missing at print time is a lost stylesheet plus a
 * _doing_it_wrong notice since WP 6.9.1.
 */
function wpematico_register_modal_style() {
	wp_register_style('wffmodalpop', WPEMATICO_PLUGIN_URL . 'assets/css/modalpop.css', array(), WPEMATICO_VERSION);
}

function wff_admin_styles() {
	global $pagenow, $page_hook;
	if($pagenow=='plugins.php' && !isset($_GET['page'])){
		wp_enqueue_style( 'wffmodalpop' );
	}
}

function wff_admin_footer() {
	global $pagenow, $page_hook;

	if ($pagenow == 'plugins.php' && !isset($_GET['page'])) {
		$nonce = wp_create_nonce('wpematico_feedback_nonce');
		?>
		<div id="wpe_feedback" class="wpe_modal_log-box fade" style="display:none;">
			<div class="wpe_modal_log-body">
				<a id="skip_feedback" style="color:lightgray" href="#"><?php esc_html_e('Skip & deactivate', 'wpematico') ?></a>
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
							<div style="float: left; margin-left: 20px;"><?php
								printf(esc_html__('💡 Remember, we offer FREE support on %s.', 'wpematico'),
										'<a href="https://etruel.com/my-account/support/" target="_blank" rel="noopener noreferrer" class="support-link">' . esc_html__('Our Site', 'wpematico') . '</a>');
								esc_html_e(' – we’re happy to help!', 'wpematico');
								?>
							</div>
							<button id="send_feedback" type="submit" class="button"><?php esc_html_e('Send & deactivate', 'wpematico') ?></button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<script type="text/javascript">
			jQuery('#deactivate-wpematico').on('click', function (e) {
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

			jQuery('#feedback_form').on('submit', function (e) {
				e.preventDefault(); // Prevent form from submitting the default way
				var reason = jQuery('input[name="deactivation_reason"]:checked').val(); // Get the selected reason
				var explicit_reason = jQuery('#other_reason').val();

				// Send AJAX request to the server
				jQuery.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'handle_feedback_submission',
						reason: reason,
						explicit_reason: explicit_reason,
						security: '<?php echo $nonce; ?>' // Nonce added here
					},
					success: function (response) {
						if (response.success) {
							// Close the feedback modal
							jQuery('#wpe_feedback').fadeToggle().removeClass('active');
							jQuery('body').removeClass('wpe_modal_log-is-active');
							// Reload the page and display the success message
							alert(response.data.message); // Display the success message
							location.reload(); // Reload the page
						} else {
							alert('Error: ' + response.data);
						}
					},
					error: function () {
						alert('An unexpected error occurred.');
					}
				});
			});

			jQuery('#skip_feedback').on('click', function () {
				// Send AJAX request to the server
				jQuery.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'handle_feedback_submission',
						reason: 'skipped',
						security: '<?php echo $nonce; ?>' // Nonce added here
					},
					success: function (response) {
						if (response.success) {
							// Close the feedback modal
							jQuery('#wpe_feedback').fadeToggle().removeClass('active');
							jQuery('body').removeClass('wpe_modal_log-is-active');

							// Reload the page and display the success message
							alert(response.data.message); // Display the success message
							location.reload(); // Reload the page
						} else {
							alert('Error: ' + response.data);
						}
					},
					error: function () {
						alert('An unexpected error occurred.');
					}
				});
			});
		</script>
		<?php
	}
}

// Feedback handling function with all safety checks
function wff_handle_feedback_submission() {
    // 1. Verify nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'wpematico_feedback_nonce')) {
        wp_send_json_error('Invalid nonce.');
    }

    // 2. Verify user permissions
    if (!current_user_can('activate_plugins')) {
        wp_send_json_error('Unauthorized user.');
    }

    // 3. Validate and sanitize the form
    $allowed_reasons = ['short_period', 'temporary_deactivation', 'stopped_working', 'broke_site', 'another_plugin', 'no_longer_needed', 'other', 'skipped'];
    $reason = isset($_POST['reason']) && in_array($_POST['reason'], $allowed_reasons) ? sanitize_text_field($_POST['reason']) : '';
    
    $explicit_reason = isset($_POST['explicit_reason']) ? sanitize_textarea_field($_POST['explicit_reason']) : '';

    if (empty($reason)) {
        wp_send_json_error('Invalid reason.');
    }

    // 4. save the attempt in the log (optional but recommended)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('WPematico feedback submission attempt by user ID: ' . get_current_user_id());
    }

    // 5. Send email (only if not skipped)
    if ($reason !== 'skipped') {
        $to = 'hello@etruel.com';
        $subject = 'Plugin Deactivation Feedback';
        $message = "A user deactivated the plugin for the following reason: " . $reason;
        
        if(!empty($explicit_reason)) {
            $message .= "\n\nThe user wrote: " . $explicit_reason;
        }
        
        $headers = ['From: ' . get_bloginfo('name') . ' <wordpress@' . parse_url(home_url(), PHP_URL_HOST) . '>'];
        
        if (!wp_mail($to, $subject, $message, $headers)) {
            wp_send_json_error('Failed to send email.');
        }
    }

    // 6. Deactivate the plugin
    $plugin_path = 'wpematico/wpematico.php';
    
    // Verify that the plugin is active before attempting to deactivate it.
    if (is_plugin_active($plugin_path)) {
        deactivate_plugins($plugin_path);
        
        // Verify that the plugin was deactivated correctly
        if (!is_plugin_active($plugin_path)) {
            wp_send_json_success(['message' => esc_html__('Deactivated successfully','wpematico')]);
        } else {
            wp_send_json_error('Failed to deactivate plugin.');
        }
    } else {
        wp_send_json_error('Plugin is not active.');
    }
}

function WPeMatico_plugins_admin_head(): void {
	global $pagenow, $page_hook;
	if ($pagenow == 'plugins.php') {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
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
function wpematico_update_row($file, $plugin_data) {
	$plugins   = get_plugins();
	$addons	   = read_wpem_addons($plugins);
	$installed = "";
	foreach ($addons as $key => $plugin) {
		if (!isset($plugin['installed'])) {
			unset($addons[$key]);
		} elseif ($plugin['installed']) {
			if (is_plugin_active($key)) {
				$installed .= '<span class="wpematico_active_addon">' . str_replace("WPeMatico", "", $plugin['Name']) . '</span> ';
			} else {
				$installed .= '<span class="wpematico_addon">' . str_replace("WPeMatico", "", $plugin['Name']) . '</span> ';
			}
		}
	}
	if (empty($installed))
		$installed	  = '<span class="wpematico_addon">' . __('None.', 'wpematico') . '</span> ';
	echo '<tr id="wpematico_addons_row" class="plugin-update-tr active" data-slug="wpematico-active-extensions">';
	echo '<td class="plugin-update colspanchange" colspan="5">';
	echo '<div class="notice inline notice-success notice-alt">';
	$allowed_tags = array('span' => array('class' => array(), 'id' => array()), 'p' => array());
	echo '<a href="' . esc_attr(admin_url('plugins.php?page=wpemaddons')) . '" target="_self" title="' . esc_attr__('Open Extensions plugins page:', 'wpematico') . '">' . esc_html__('Installed Extensions:', 'wpematico') . '</a>' . wp_kses($installed, $allowed_tags);
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
function wpematico_action_links($data) {
	if (!current_user_can('manage_options')) {
		return $data;
	}
	return array_merge($data, array(
		'<a href="' . esc_url(admin_url('admin.php?page=wpematico_settings')) . '" title="' . esc_attr__('Load WPeMatico Settings Page', 'wpematico') . '">' . esc_html__('Settings', 'wpematico') . '</a>',
		'<a href="https://etruel.com/downloads/wpematico-perfect-package/" target="_Blank" title="' . __('Take a look at all the bundled add-ons', 'wpematico') . '">' . __('Go Perfect', 'wpematico') . '</a>',
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
function wpematico_row_meta($data, $page) {
	if ($page != WPEMATICO_BASENAME) {
		return $data;
	}

	return array_merge($data, array(
		//'<a href="http://www.wpematico.com/wpematico/" target="_blank">' . __('Info & comments') . '</a>',
		'<a href="' . admin_url('plugins.php?page=wpemaddons') . '" target="_self">' . __('Extensions', 'wpematico') . '</a>',
		'<a href="https://etruel.com/my-account/support/" target="_blank">' . __('Support', 'wpematico') . '</a>',
		'<a href="https://wordpress.org/support/view/plugin-reviews/wpematico?filter=5&rate=5#new-post" target="_Blank" title="Rate 5 stars on WordPress.org">' . __('Rate Plugin', 'wpematico') . '</a>',
		'<strong><a href="https://etruel.com/downloads/wpematico-essentials/" target="_Blank" title="' . __('Take a look at the Essentials features', 'wpematico') . '">' . __('Go PRO', 'wpematico') . '</a></strong>',
//		'<p>' . __('Activated Extensions:', 'wpematico' ) . '</p>',
	));
}

/* * *************************************************************************************
  /***************************************************************************************
 * Activation, Upgrading and uninstall functions
 * ************************************************************************************ */
register_activation_hook(WPEMATICO_BASENAME, 'wpematico_activate');
register_deactivation_hook(WPEMATICO_BASENAME, 'wpematico_deactivate');
register_uninstall_hook(WPEMATICO_BASENAME, 'wpematico_uninstall');

add_action('plugins_loaded', 'wpematico_update_db_check');

function wpematico_update_db_check() {
	if (version_compare(WPEMATICO_VERSION, get_option('wpematico_db_version'), '>')) { // check if updated (WILL SAVE new version on welcome )
		if (!get_transient('_wpematico_activation_redirect')) { //just one time running
			wpematico_install(get_option('wpematico_db_version'));
		}
		delete_option('wpematico_lastlog_disabled');
	}
}

/**
 * Runs on plugin activation and on each version update. Every migration block
 * gates on $old_db_version, so a jump from 2.8.18 to 2.9.x applies all the
 * intermediate ones in order. A fresh activation passes '' and only writes the
 * version option.
 *
 * ★ Keep the raw get_post_meta / update_post_meta pattern — never get_campaign()
 * + update_campaign() here, so a migration cannot be re-shaped by whatever addons
 * happen to be loaded.
 *
 * @param string $old_db_version  The wpematico_db_version value before this update.
 */
function wpematico_install($old_db_version = '') {

	// --- Migration: 2.9 — upgrade from 2.8.x -----------------------------------------------
	// Recalculates cronnextrun for all campaigns.
	// Also applies the 2.8.20 timestamp fix for users upgrading directly from before 2.8.20:
	// old code stored current_time('timestamp') = time() + gmt_offset in lastrun;
	// new code stores time() (real UTC).
	if ($old_db_version && version_compare($old_db_version, '2.9', '<')) {
		$offset       = (int) round(get_option('gmt_offset') * 3600);
		$needs_ts_fix = $offset !== 0 && version_compare($old_db_version, '2.8.20', '<');
		$args         = array(
			'orderby'	  => 'ID',
			'order'		  => 'ASC',
			'post_type'	  => 'wpematico',
			'numberposts' => -1,
			// Only the IDs are used below, so don't hydrate a full post object per
			// campaign: on a site with hundreds of campaigns that is a lot of memory
			// for nothing, and this runs before anything else on the upgrade request.
			'fields'	  => 'ids',
		);
		$campaign_ids = get_posts($args);
		foreach ($campaign_ids as $campaign_id) {
			// Read raw campaign_data WITHOUT passing through check_campaigndata.
			// check_campaigndata rebuilds the array from scratch and strips all addon fields
			// (GPT-Spinner, FullContent, etc.), causing permanent data loss when saved back.
			$raw = get_post_meta($campaign_id, 'campaign_data', true);
			if (!is_array($raw)) {
				$raw = array();
			}

			if ($needs_ts_fix && isset($raw['lastrun']) && (int) $raw['lastrun'] > 0) {
				$raw['lastrun'] = (int) $raw['lastrun'] - $offset;
				update_post_meta($campaign_id, 'lastrun', $raw['lastrun']);
			}

			$cron               = isset($raw['cron']) ? $raw['cron'] : '0 3 * * *';
			$raw['cronnextrun'] = (int) WPeMatico::time_cron_next($cron);
			update_post_meta($campaign_id, 'cronnextrun', $raw['cronnextrun']);

			// Backfill the individual 'activated' meta that update_campaign() writes
			// from 2.9 on, so counting the switched-on campaigns is one query.
			update_post_meta($campaign_id, 'activated', !empty($raw['activated']) ? 1 : 0);

			// Save campaign_data with all fields intact, preserving addon settings.
			update_post_meta($campaign_id, 'campaign_data', $raw);
		}

		// The Vimeo campaign type used to be an addon and is part of core from 2.9.
		// A site that has that addon installed — active or not — gets the feature
		// switched on, so its Vimeo campaigns keep running once the old plugin is
		// deactivated. A site that never had it keeps the switch off, and the type
		// is not even loaded.
		if (function_exists('wpematico_installed_addon_file') && '' !== wpematico_installed_addon_file('WPeMatico Vimeo')) {
			wpematico_enable_vimeo_campaign_type();
		}
	}
	// --- End migration 2.9 ------------------------------------------------------------------

	// Add future per-version migrations here following the same pattern:
	// if ($old_db_version && version_compare($old_db_version, '2.9.X', '<')) { ... }

	// An upgrade is the moment a site arriving from a competitor plugin is most
	// likely to have data waiting: look once, here, and cache the answer. The
	// Migration Toolkit itself stays unloaded until its module is switched on;
	// this only feeds the notice that tells the admin it exists.
	if (class_exists('WPeMatico_Migration_Detect')) {
		WPeMatico_Migration_Detect::refresh();
	}

	// Record the version the migrations above were applied for, always and right
	// here: this option is the only thing that stops them from running again, and
	// that has nothing to do with the user seeing the welcome screen. Leaving the
	// write to welcome(), which can return early, left the version stale and
	// replayed every migration a couple of minutes later, forever.
	update_option('wpematico_db_version', WPEMATICO_VERSION, false);

	// Pending welcome redirect. Only for major versions (2.9, 3.0), not for
	// patches (2.9.1). The value tells welcome() which screen to show: welcome()
	// used to infer "first install" from an empty wpematico_db_version, which is
	// no longer available now that the version is recorded before it runs.
	$v = explode('.', rtrim(WPEMATICO_VERSION, '.0'));
	if (count($v) <= 2) {
		set_transient('_wpematico_activation_redirect', ($old_db_version ? 'update' : 'install'), 120); // After two minutes lost welcome screen
	}
}

/**
 * This function will be hooked after @check_campaigndata and only on install or update of wpematico .
 * @param $campaigndata an array with all campaign data.
 * @return $campaigndata an array with filtered campaign data to compatibility.
 * @since 1.9.0
 */
function wpematico_campaign_compatibilty_after($campaigndata) {
	$wpematico_version = get_option('wpematico_db_version');
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
	$campaign_cancel_imgcache = (!isset($post_data['campaign_cancel_imgcache']) || empty($post_data['campaign_cancel_imgcache'])) ? false : (($post_data['campaign_cancel_imgcache'] == 1) ? true : false);
	if ($campaign_cancel_imgcache) {
		$campaigndata['campaign_no_setting_img'] = true;
		$campaigndata['campaign_imgcache']		 = false;
		$campaigndata['campaign_attach_img']	 = false;
		$campaigndata['campaign_featuredimg']	 = false;
		$campaigndata['campaign_rmfeaturedimg']	 = false;
		$campaigndata['campaign_customupload']	 = false;
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
	WPeMatico::Create_campaigns_page();
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
 * Every option and transient this plugin owns, for the Danger Zone "delete all data".
 *
 * A new option that is not listed here outlives the uninstall, so add it when you add the
 * option. Add-on options are each add-on's own uninstall to remove.
 *
 * ★ The license keys go with everything else (decided 2026-09-28): "delete all data" means
 * the site keeps nothing. The activation itself lives at the store, so the key is copied
 * back from the customer's account there, and nothing is deactivated from here — that
 * would be one HTTP request per add-on inside an uninstall.
 *
 * @return array
 */
function wpematico_owned_options() {
	$options = array(
		WPeMatico::OPTION_KEY,
		'wpematico_db_version',
		'WPeMatico_danger',
		'wpematico_active_modules',
		'wpem_show_locally_addons',
		'wpem_menu_position',
		'wpem_hide_reviews',
		'wpematico_license_keys',
		'wpematico_license_status',
		'wpematico_license_ids',
		'wpematico_license_expires',
		'wpematico_updates_waiting',
		'wpematico_core_autoupdate_added',
		'wpematico_notices',
		'wpematico_level_snotifications',
		'wpematico_level_tnotifications',
		'wpematico_lastlog_disabled',
		'wpematico_encoding_hosts',
		'wpematico_dismiss_mdm_notice',
		'wpematico_dismiss_wizard_notice',
		'wpematico_remote_connectivity',
		'wpematico_outdated_addons',
		'wpematico_importers_detected',
		'wpematico_importers_notice_dismissed',
		'wpematico_dashboard_chart',
		'wpematico_dashboard_news',
		'wpematico_dashboard_news_backoff',
		'action_notice_wpematico_addons',
		'etruel_wpematico_addons_data',
		'_etruel_wpem_reviews_data',
		'_wpematico_activation_redirect',
		'_wpematico_version_save_failed',
	);

	return apply_filters('wpematico_owned_options', $options);
}

/**
 * The options whose names carry a locale, a user or a hash, which no list can name.
 *
 * Matched on their own prefixes only: a pattern as wide as "wpematico%" also matches
 * WPeMaticoPRO_Options and every other add-on's.
 *
 * @global $wpdb
 * @return void
 */
function wpematico_delete_owned_dynamic_options() {
	global $wpdb;

	$prefixes = apply_filters('wpematico_owned_option_prefixes', array(
		'wpematico_i18n_notif_obj_',
		'wpematico_subscription_email_',
		'_transient_wpematico_',
		'_transient_timeout_wpematico_',
		'_transient_etruel_wpematico_',
		'_transient_timeout_etruel_wpematico_',
	));

	foreach ($prefixes as $prefix) {
		$names = $wpdb->get_col($wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like($prefix) . '%'
		));
		foreach ($names as $name) {
			delete_option($name);
		}
	}
}

/**
 * Uninstallation
 * @global $wpdb, $blog_id
 * @return void
 */
function wpematico_uninstall() {
	global $wpdb, $blog_id;
	$danger						 = get_option('WPeMatico_danger', array());
	$danger['wpemdeleoptions']	 = (isset($danger['wpemdeleoptions']) && !empty($danger['wpemdeleoptions'])) ? $danger['wpemdeleoptions'] : false;
	$danger['wpemdelecampaigns'] = (isset($danger['wpemdelecampaigns']) && !empty($danger['wpemdelecampaigns'])) ? $danger['wpemdelecampaigns'] : false;
	if (is_network_admin() && $danger['wpemdeleoptions']) {
		if (isset($wpdb->blogs)) {
			$blogs = $wpdb->get_results(
					$wpdb->prepare(
							'SELECT blog_id ' .
							'FROM ' . $wpdb->blogs . ' ' .
							"WHERE blog_id <> '%s'",
							$blog_id
					)
			);
			foreach ($blogs as $blog) {
				delete_blog_option($blog->blog_id, WPeMatico::OPTION_KEY);
			}
		}
	}
	if ($danger['wpemdeleoptions']) {
		foreach (wpematico_owned_options() as $option) {
			delete_option($option);
			delete_site_option($option);
			delete_transient($option);
			delete_site_transient($option);
		}
		wpematico_delete_owned_dynamic_options();
		delete_metadata('user', 0, 'wpematico_tours', '', true);
	}
	//delete campaigns
	if ($danger['wpemdelecampaigns']) {
		$args	   = array('post_type' => 'wpematico', 'orderby' => 'ID', 'order' => 'ASC');
		$campaigns = get_posts($args);
		foreach ($campaigns as $post) {
			wp_delete_post($post->ID, true);  // forces delete to avoid trash
		}
	}
}

/**
 * The post type and campaign the quick/bulk edit taxonomy readers are allowed to answer for.
 *
 * Both endpoints echo the terms of an arbitrary post type and the categories and tags
 * stored in an arbitrary campaign, so they answer only to the campaigns list (the nonce
 * travels in wpematico_object.quick_edit_tax_nonce), for a registered post type, and to
 * a user who may edit the campaign being asked about. Bulk edit asks with post_id 0,
 * which is the campaigns list itself.
 *
 * @return array|false array($post_type, $post_id), or false when the request is refused.
 */
function wpematico_quick_edit_tax_request() {
	if (!isset($_POST['post_type'])) {
		return false;
	}

	if (!check_ajax_referer('wpematico-quick-edit-tax', 'nonce', false)) {
		return false;
	}

	$post_type = sanitize_key(wp_unslash($_POST['post_type']));
	$post_id   = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

	if (!post_type_exists($post_type)) {
		return false;
	}

	if ($post_id > 0) {
		if (!current_user_can('edit_post', $post_id)) {
			return false;
		}
	} else {
		$campaigns = get_post_type_object('wpematico');
		if (!current_user_can($campaigns ? $campaigns->cap->edit_posts : 'edit_posts')) {
			return false;
		}
	}

	return array($post_type, $post_id);
}

add_action('wp_ajax_fetch_taxonomies', 'fetch_taxonomies');

function fetch_taxonomies() {
	$request = wpematico_quick_edit_tax_request();
	if (false === $request) {
		wp_die('', '', array('response' => 403));
	}

	list($post_type, $post_id) = $request;
	$taxonomies = get_object_taxonomies($post_type, 'objects');

	if (!empty($taxonomies)) {
		foreach ($taxonomies as $taxonomy) {
			if ($taxonomy->hierarchical) { // Only show hierarchical taxonomies
				echo '<span class="title inline-edit-categories-label">' . esc_html($taxonomy->labels->name) . '</span>';
				echo '<input type="hidden" name="tax_input[' . esc_attr($taxonomy->name) . '][]" value="0" />'; // Default value 0
				echo '<ul class="cat-checklist ' . esc_attr($taxonomy->name) . '-checklist">';
				get_campaign_tax($taxonomy->name, $post_id);
				echo '</ul>';
			}
		}
	}

	wp_die(); // Properly terminate the AJAX request
}

add_action('wp_ajax_fetch_tags', 'fetch_tags');

function fetch_tags() {
	$request = wpematico_quick_edit_tax_request();
	if (false === $request) {
		wp_die('', '', array('response' => 403));
	}

	list($post_type, $post_id) = $request;

	// Get the taxonomies for the selected post type
	$taxonomy_names	 = get_object_taxonomies($post_type);
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
				$current_tags = get_campaign_tags($taxonomy->name, $post_id);
				if ($taxonomy->name != 'post_tag') {
					// Create a label for each taxonomy with a textarea for the tags
					$html .= '<label class="inline-edit-tags">';
					$html .= '<span class="title">' . esc_html($taxonomy->labels->name) . '</span>';
					$html .= '<textarea cols="22" rows="1" name="tax_input[' . esc_attr($taxonomy->name) . ']" class="tax_input_' . esc_attr($taxonomy->name) . '">' . esc_textarea($current_tags) . '</textarea>';
					$html .= '</label>';
				} else {
					// Create a label for each taxonomy with a textarea for the tags
					$html .= '<label class="inline-edit-tags">';
					$html .= '<span class="title">' . esc_html($taxonomy->labels->name) . '</span>';
					$html .= '<textarea cols="22" rows="1" name="campaign_tags" class="tax_input_' . esc_attr($taxonomy->name) . '">' . esc_textarea($current_tags) . '</textarea>';
					$html .= '</label>';
				}
			}
		}
	}
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $html;
	wp_die(); // Properly terminate the AJAX request
}

/**
 * Summary of get_campaign_tags
 * @param mixed $taxonomy_name
 * @param int $post_id Campaign the quick edit is asking about, 0 on bulk edit.
 * @return string
 */
function get_campaign_tags($taxonomy_name, $post_id = 0) {
	$post_id = intval($post_id);
	if ($taxonomy_name != 'post_tag') {
		$current_tags = get_the_terms($post_id, $taxonomy_name);
		$all_tags	  = array();

		if ($current_tags && !is_wp_error($current_tags)) {
			foreach ($current_tags as $tag) {
				$all_tags[] = $tag->name;
			}

			$current_tags = implode(',', $all_tags);
		}
	} else {
		$campaign_data = get_post_meta($post_id, 'campaign_data');
		$campaign_data = (isset($campaign_data[0])) ? $campaign_data[0] : array(0);
		$tags		   = apply_filters('wpematico_check_campaigndata', $campaign_data);
		$current_tags  = $tags['campaign_tags'];
	}
	return $current_tags;
}

function get_campaign_tax($taxonomy_name, $post_id = 0) {
	$post_id = intval($post_id);
	if ($taxonomy_name == 'category') {
		$campaign_data = get_post_meta($post_id, 'campaign_data');
		$campaign_data = (isset($campaign_data[0])) ? $campaign_data[0] : array(0);
		$tags		   = apply_filters('wpematico_check_campaigndata', $campaign_data);
		$current_tax   = $tags['campaign_categories'];

		// Check the terms for the post
		wp_terms_checklist($post_id, $args = array(
			'taxonomy'			   => $taxonomy_name,
			'descendants_and_self' => 0,
			'selected_cats'		   => array_map('intval', $current_tax),
			'popular_cats'		   => false,
			'walker'			   => null,
			'checked_ontop'		   => true
		));
	} else {
		wp_terms_checklist($post_id, array('taxonomy' => $taxonomy_name));
	}
}

// wpematico_log() and wpematico_get_log_file_path() are defined in includes/wpematico_functions.php
// so they are available both in admin and during WP-Cron (outside is_admin()).

/**
 * The reactor, the brand mark, as SVG markup.
 *
 * Paths and colours are the ones from the master file (reactor.svg), untouched:
 * the outer rhombus, the orange one inside it, the three light arcs, and the
 * inner outline once more on top so the arcs don't paint over its edge. This is
 * brand identity — redraw nothing, only strip what Inkscape left behind.
 *
 * @param int $size Rendered size in pixels.
 * @return string
 */
function wpematico_logo_svg($size = 20) {
	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 682.66669 682.66669" width="' . (int) $size . '" height="' . (int) $size . '" role="img" aria-label="WPeMatico">'
			. '<path d="M 330.86559,660.93052 144.63672,343.39515 331.09697,23.456861 517.81393,343.41118 Z" fill="#dddddd" stroke="#000000" stroke-width="22.63259697"/>'
			. '<path d="M 330.40869,577.16826 198.9974,344.47835 330.57141,109.14702 463.02901,344.63641 Z" fill="#ef8e2f" stroke="#ea6208" stroke-width="13.52285862"/>'
			. '<g fill="#ffca79">'
			. '<path d="M 365.1785,500.80015 C 355.89144,455.13711 335.7767,411.7133 306.95198,375.10048 283.95345,345.88802 255.46624,321.01172 223.4218,302.15823 l -11.78612,30.25163 c 29.91253,19.79655 56.38875,44.77042 77.89699,73.47683 28.76371,38.39007 48.54656,83.46517 57.32936,130.62459 z"/>'
			. '<path d="M 457.27369,351.27065 C 443.57501,306.85789 420.85482,265.24856 390.8988,229.71289 366.78011,201.10183 338.00474,176.42512 306.05098,156.95049 l -15.90283,31.66866 c 30.86051,18.20726 58.83471,41.29531 82.56406,68.14291 30.97747,35.04818 54.65944,76.51539 69.10583,121.00449 z"/>'
			. '<path d="M 414.82276,418.66811 C 401.64725,373.98584 377.87172,332.4672 346.00288,298.48962 320.57719,271.38152 290.056,249.06889 256.51141,233.06655 l -9.14023,31.16104 c 31.50216,17.12724 60.02806,39.71143 83.92531,66.44436 31.96119,35.75374 55.54804,78.94632 68.34965,125.16284 z"/>'
			. '</g>'
			. '<path d="M 330.40869,577.16826 198.9974,344.47835 330.57141,109.14702 463.02901,344.63641 Z" fill="none" stroke="#ea6208" stroke-width="13.52285862"/>'
			. '</svg>';
}

add_action('admin_head', 'wpematico_menu_icon_style');

/**
 * Paints the logo into the top level menu item ourselves, because both ways into
 * add_menu_page() spoil it: an image URL is printed as a padded <img> at the
 * file's own pixel size, and a base64 SVG gets every `fill` rewritten by
 * wp.svgPainter with the flat admin scheme colour.
 *
 * So the menu is registered with 'none' and the icon arrives as a background
 * image, URL-encoded rather than base64 on purpose — svgPainter looks for
 * `;base64` and leaves anything else alone, which is what keeps the orange.
 */
function wpematico_menu_icon_style() {
	$uri = 'data:image/svg+xml,' . rawurlencode(wpematico_logo_svg(28));
	?>
	<style id="wpematico-menu-icon">
		#adminmenu #toplevel_page_wpematico_dashboard .wp-menu-image {
			background: url("<?php echo $uri; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>") no-repeat center;
			background-size: 28px auto;
		}
	</style>
	<?php
}
