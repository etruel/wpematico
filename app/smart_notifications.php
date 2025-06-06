<?php

/**
 * Smart Notifications Class
 * @package     WPeMatico
 * @subpackage  Admin/Smart Notifications
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.3
 */
class wpe_smart_notifications {

	public static function init() {
		add_action('admin_head', array(__CLASS__, 'admin_head'));
		// dismiss AJAX calls
		add_action('wp_ajax_wpematico_dismiss_wprate_notice', array(__CLASS__, 'dismiss_wprate_notice'));
		add_action('wp_ajax_wpematico_dismiss_wizard_notice', array(__CLASS__, 'dismiss_wizard_notice'));
		// temp. will be deleted on finish MDM
		//add_action('wp_ajax_wpematico_dismiss_mdm_notice', array(__CLASS__, 'dismiss_mdm_notice'));
	}

	public static function dismiss_mdm_notice() {
		update_option('wpematico_dismiss_mdm_notice', true);
	}

	public static function admin_head() {
		global $post_type, $current_screen, $post;
		//die ('<pre>AAA'. print_r($current_screen, 1).'</pre>');
		if ($current_screen->post_type != 'wpematico') {
			return;
		}
		wp_enqueue_script('wpematico-smart-notifications', WPeMatico::$uri . 'app/js/smart_notifications.js', array('jquery'), WPEMATICO_VERSION, true);

		self::hooks();
	}

	public static function hooks() {
		// WPeMatico Notices
		add_action('admin_notices', array(__CLASS__, 'show_wprate_notice'));
		add_action('edit_form_top', array(__CLASS__, 'show_campaign_wizard_notice'));
		//add_action('admin_notices', array(__CLASS__, 'show_mdm_notice'));
	}

	public static function dismiss_wprate_notice() {
		$current_numbers = self::get_number_of_campaigns_post();
		$current_levels	 = self::get_levels_notifications($current_numbers);
		update_option('wpematico_level_snotifications', $current_levels);
	}

	public static function dismiss_wizard_notice() {
		update_option('wpematico_dismiss_wizard_notice', true);
	}

	/**
	 * get_number_of_campaigns_post 
	 * Numer of campaigns and posts fetched by all campaigns 
	 * Current value of posts fetched can be reset to zero in each campaign. 
	 * @global type $wpdb
	 * @param type $return	string: array, 
	 * 						campaings, count
	 * 						fetched, posts, countpost
	 * @return type
	 */
	public static function get_number_of_campaigns_post($return = "array") {
		global $wpdb;
		$ret		  = array(0, 0);
		$check_sql	  = "SELECT COALESCE(SUM(meta_value), 0) as countpost, COUNT(*) as count FROM $wpdb->postmeta WHERE meta_key = 'postscount'";
		$results_data = $wpdb->get_results($check_sql);
		if (!empty($results_data)) {
			$ret[0] = $results_data[0]->count;
			$ret[1] = $results_data[0]->countpost;
		}
		return $ret;
	}

	public static function get_levels_notifications($current_numbers) {

		$cur_level_notification_campaigns = 0;
		if ($current_numbers[0] > 7) {
			$cur_level_notification_campaigns = 1;
		}
		if ($current_numbers[0] > 15) {
			$cur_level_notification_campaigns = 2;
		}

		$cur_level_notification_posts = 0;
		if ($current_numbers[1] > 50) {
			$cur_level_notification_posts = 1;
		}
		if ($current_numbers[1] > 120) {
			$cur_level_notification_posts = 2;
		}
		if ($current_numbers[1] > 350) {
			$cur_level_notification_posts = 3;
		}

		$ret = array($cur_level_notification_campaigns, $cur_level_notification_posts);
		return $ret;
	}

	public static function show_wprate_notice() {
		global $post_type, $current_screen;
		if ($post_type != 'wpematico') {
			return;
		}
		if ($current_screen->id != 'edit-wpematico') {
			return;
		}
		$current_numbers	 = self::get_number_of_campaigns_post();
		$current_levels		 = self::get_levels_notifications($current_numbers);
		$level_notifications = get_option('wpematico_level_snotifications', array(0, 0));

		$show_wprate_notice = false;
		if ($current_levels[0] > $level_notifications[0] || $current_levels[1] > $level_notifications[1]) {
			$show_wprate_notice = true;
		}
		if (!$show_wprate_notice) {
			return;
		}
		?>
		<div class="clear"></div>
		<div id="smart-notification-rate" class="wpematico-smart-notification">
			<h3>
				<a id="smart-notification-title-link" href="https://wordpress.org/support/view/plugin-reviews/wpematico?filter=5&rate=5#new-post" target="_Blank">
					<span class="notification-title"><?php esc_html_e('Rate 5 stars on Wordpress', 'wpematico'); ?></span>
				</a>
				<span class="icon-dismiss-div dashicons dashicons-no" title="Dismiss"></span>
				<span class="icon-close-div dashicons dashicons-visibility" title="Close"></span>
			</h3>
			<div class="description-smart-notification">
				<p class="parr-wpmatico-smart-notification">

					<?php esc_html_e('The WPeMatico team work hard to offer you excellent tools for autoblogging.', 'wpematico'); ?>
					<br>
					<?php 					
					/* translators: 5-star review linked to WP rate page. */
					printf(esc_html__('So we would love you to write a %s in WordPress if you appreciate our plugin.', 'wpematico'), '<a href="https://wordpress.org/support/view/plugin-reviews/wpematico?filter=5&rate=5#new-post" id="link2rate" target="_Blank"><strong>5-star review</strong></a>'); ?>
					<br>
					<?php esc_html_e('It only takes a minute, "click the button below" to go to the form.', 'wpematico'); ?>
					<br>
					<br>
					<a href="https://wordpress.org/support/view/plugin-reviews/wpematico?filter=5&rate=5#new-post" id="linkrate" class="button button-primary button-hero" target="_Blank" title="Click here to rate plugin on Wordpress">Rate us</a>
				</p>

				<br />
			</div>
		</div>
		<?php
	}

	public static function show_campaign_wizard_notice() {
		global $post_type, $current_screen, $post;
		if ($current_screen->id != 'wpematico' || $current_screen->action == 'add') {
			return;
		}
		// User already dismissed
		$dismiss_wizard_notice = get_option('wpematico_dismiss_wizard_notice', false);
		if ($dismiss_wizard_notice) {
			return;
		}
		?>
		<div id="smart-notification-wizard" class="wpematico-smart-notification campagin_edit">
			<h3>
				<span class="notification-title"><?php esc_html_e('Check all Campaign Options with the Wizard', 'wpematico'); ?></span>
				<span class="icon-dismiss-div dashicons dashicons-no" title="Dismiss"></span>
				<span class="icon-close-div dashicons dashicons-visibility" title="Close"></span>
			</h3>
			<div class="description-smart-notification">
				<p class="parr-wpmatico-smart-notification">
					<?php esc_html_e('Want to make sure your campaign settings are fine?', 'wpematico'); ?>
					<br>
					<?php esc_html_e('Open the configuration wizard to see each metabox one by one.', 'wpematico'); ?>
					<br>
					<br>
					<a href="#wizard" class="button button-primary button-hero thickbox_open">Wizard</a>
				</p>
				<br>
			</div>
		</div>
		<?php
	}

	/**
	 * It is not currently showing, saved for another major event.
	 */
	public static function show_mdm_notice() {
		global $post_type, $current_screen, $post;
		if ($post_type != 'wpematico' 
				&& strpos($current_screen->id, 'wpematico')!==false 
				&& strpos($current_screen->id, 'wpemaddons')!==false 
				&& ($current_screen->id != 'wpematico' || $current_screen->action == 'add') 
			) {
			return;
		}
		// User already dismissed
		$dismiss_mdm_notice = get_option('wpematico_dismiss_mdm_notice', false);
		if ($dismiss_mdm_notice) {
			return;
		}
		?>
		<div id="smart-notification-mdm" class="wpematico-smart-notification" style="border-color: #de3f12;">
			<h3>
				<span class="notification-title"><?php esc_html_e('WPeMatico 2.7 series it\'s here!', 'wpematico'); ?></span>
				<span class="icon-dismiss-div dashicons dashicons-no" title="Dismiss"></span>
				<span class="icon-close-div dashicons dashicons-visibility" title="Close"></span>
			</h3>
			<div class="description-smart-notification">
				<p class="parr-wpmatico-smart-notification">

					<big><strong><?php esc_html_e('Join our giveaways to win “almost” FREE amazing prizes! 🎁', 'wpematico'); ?></strong></big><br>
					<br>
					<?php esc_html_e('MDM Giveaway will award 10 prizes on selected products available on etruel.com annual licenses discounted to $1.- ', 'wpematico'); ?><strong><?php esc_html_e('Yes! Just ONE Dollar!', 'wpematico'); ?></strong><br>
					<br>
					<a href="https://www.wpematico.com/giveaway/mdm/" target="_blank" class="button button-primary button-hero" style="background-color: #de3f12; border-color: #de3f12;font-weight: 700;">MORE INFO!</a>
				</p>
				<br>
			</div>
		</div>
		<?php
	}
	
}

wpe_smart_notifications::init();
?>
