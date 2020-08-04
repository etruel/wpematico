<?php
/**
 * Smart Notifications Class
 * @package     WPeMatico
 * @subpackage  Admin/Smart Notifications
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.3
 */
class wpe_smart_notifications {
	public static function hooks() {

		add_action('admin_notices', array(__CLASS__, 'show_notice'));
		add_action( 'wp_ajax_wpematico_close_notification', array(__CLASS__, 'close_notification') );
	}
	public static function close_notification() {
		$current_numbers = self::get_number_of_campaigns_post();
		$current_levels = self::get_levels_notifications($current_numbers);
		update_option('wpematico_level_snotifications', $current_levels);
		
	}
	public static function get_number_of_campaigns_post() {
		global $wpdb;
		$ret = array(0, 0);
		$check_sql = "SELECT COALESCE(SUM(meta_value), 0) as countpost, COUNT(*) as count FROM $wpdb->postmeta WHERE meta_key = 'postscount'";
		$results_data = $wpdb->get_results( $check_sql  );
		if ( ! empty($results_data) ) {
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
	public static function show_notice() {
		global $post_type, $current_screen; 
		if( $post_type != 'wpematico' ) {
			return;
		} 	
		if( $current_screen->id != 'edit-wpematico' ) {
			return;
		}
		$current_numbers = self::get_number_of_campaigns_post();
		$current_levels = self::get_levels_notifications($current_numbers);
		$level_notifications = get_option('wpematico_level_snotifications', array(0, 0));

		$show_notice = false;
		if ( $current_levels[0] > $level_notifications[0] || $current_levels[1] > $level_notifications[1] ) {
			$show_notice = true;
		}
		if ( ! $show_notice ) {
			return;
		}

		?>
		<div class="clear"></div>
		<div class="div-wpematico-smart-notification">
			<h3><a id="smart-notification-title-link" href="https://wordpress.org/support/view/plugin-reviews/wpematico?filter=5&rate=5#new-post" target="_Blank"><?php _e( 'Rate 5 stars on Wordpress', 'wpematico' ); ?></a>
			 <span class="icon-minimize-div dashicons  dashicons-visibility" style="margin-right: 30px;" title="Close"></span>
			 <span class="icon-close-div dashicons dashicons-no" title="Dismiss"></span></h3>
			
			 <div class="description-smart-notification">
				<p class="parr-wpmatico-smart-notification">

					<?php _e( 'The WPeMatico team work hard to offer you excellent tools for autoblogging.', 'wpematico' );?>
					<br>
					<?php printf(__('So we would love you to write a %s in WordPress if you appreciate our plugin.', 'wpematico' ),'<a href="https://wordpress.org/support/view/plugin-reviews/wpematico?filter=5&rate=5#new-post" id="link2rate" target="_Blank"><strong>5-star review</strong></a>'); ?>
					<br>
					<?php _e( 'It only takes a minute, "click the button below" to go to the form.', 'wpematico' );?>
					<br>
					<br>
					<a href="https://wordpress.org/support/view/plugin-reviews/wpematico?filter=5&rate=5#new-post" id="linkrate" class="button button-primary button-hero" target="_Blank" title="Click here to rate plugin on Wordpress">Rate us</a>
				</p>

				<br />
			</div>
		</div>
		
		<?php
	}
}
wpe_smart_notifications::hooks();
?>