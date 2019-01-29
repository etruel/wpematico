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


		$class_iconeyes = 'dashicons  dashicons-visibility';
		?>
		<div class="clear"></div>
		<div class="div-wpematico-smart-notification">
			<h3>Rate notification
			 <span class="icon-minimize-div  <?php print($class_iconeyes); ?>" style="margin-right: 30px;" ></span>
			 <span class="icon-close-div dashicons dashicons-no"></span></h3>
			
			 <div class="description-smart-notification">
				<p class="parr-wpmatico-smart-notification">
				
				Message notice

				</p>
				<br />
			</div>
		</div>
		<style type="text/css">
			.div-wpematico-smart-notification {
				background-color: white;
			    padding: 10px;
			    padding-top: 0px;
			    position: relative;
			    /* width: 100%; */
			    border: 1px solid #D9D9D9;
			    margin-bottom: 20px;
			    margin-top: 5px;
			    /* display: block; */
			    box-sizing: border-box;
			    margin: 5px 20px 2px 0px;
			    padding: 1px 12px;
			    display: block;
			}
			.description-smart-notification{display: flex;}
			.icon-close-div,.icon-minimize-div{position: absolute; top: 0; right: 0; font-size: 25px; margin-top: -5px; cursor: pointer;}
			.div-wpematico-smart-notification h3{color: #4C5054; position: relative; border-bottom: 1px solid #ccc; padding-bottom: 10px;}
			.parr-wpmatico-smart-notification{padding-right: 10px;}
			

		</style>
		<script type="text/javascript">
		jQuery(document).ready(function($){
			//cerramos div
			var icon_plus = 'dashicons-visibility';
			var icon_dismis = 'dashicons-hidden';

			
			function wpematico_close_notification() {
				var data = {
					'action': 'wpematico_close_notification',
				};
				// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
				jQuery.post(ajaxurl, data, function(response) {
					//response
				});
			}
			

			//minimize action
			$(".icon-minimize-div").click(function(){
				$('.parr-wpmatico-smart-notification').slideToggle(300);
				if($(this).hasClass(icon_dismis)){
					$(this).removeClass(icon_dismis);
					$(this).addClass(icon_plus);
					//ajax Icon Save
					//save_icon_eyes(icon_plus);
				}else{
					$(this).removeClass(icon_plus);
					$(this).addClass(icon_dismis);
					//ajax Icon Save
					//save_icon_eyes(icon_dismis);
				}
			});
			
			$(".icon-close-div").click(function(){
				$(this).parent().parent().slideUp(500);
				wpematico_close_notification();
			});
			
		});
	</script>

		<?php
	}
}
wpe_smart_notifications::hooks();
?>