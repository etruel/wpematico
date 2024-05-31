<?php
/**
 * Notications Translate Class
 * @package     WPeMatico
 * @subpackage  Admin/NotificationTraslate
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.6.4
 */
class wpe_notification_traslate {
	public static $locale = null;
	public static $textdomain = 'wpematico';
	public static $api_url = 'https://translate.wordpress.org/api/projects/wp-plugins/wpematico';
	public static $translation_exists = false;
	public static $locale_name = '';
	public static $percent_translated = '';
	public static $translation_loaded = false;
	public static $plugin_name = 'WPeMatico';
	public static $plugin_name_translate = 'WPeMatico Traslate';
	public static $plugin_translate_link = 'https://translate.wordpress.org/projects/wp-plugins/wpematico';
	/**
	* Static function hooks
	* @access public
	* @return void
	* @since 1.6.4
	*/
	public static function hooks() {
		if (!is_admin() ) {
			return;
		}
		self::$locale = self::get_admin_locale();
		if ('en_US' === self::$locale) {
			return;
		}
		
		add_action('wpematico_welcome_page_before',array(__CLASS__, 'form_wpmatico_traslate'));
		add_action('wpematico_setting_page_before',array(__CLASS__, 'form_wpmatico_traslate'));
		add_action('wpematico_settings_tab_pro_licenses',array(__CLASS__, 'form_wpmatico_traslate'));
		add_action( 'wp_ajax_wpematico_close_tnotification', array(__CLASS__, 'ajax_close_callback') );
	}
	/**
	* Static function get_admin_locale
	* Returns the locale used in the admin.
	* WordPress 4.7 introduced the ability for users to specify an Admin language
	* different from the language used on the front end. This checks if the feature
	* is available and returns the user's language, with a fallback to the site's language.
	* Can be removed when support for WordPress 4.6 will be dropped, in favor
	* of WordPress get_user_locale() that already fallbacks to the site’s locale.
	*
	* @access public
	* @return string The locale.
	* @since 1.6.4
	*/
	public static function get_admin_locale() {
		if ( function_exists( 'get_user_locale' ) ) {
			return get_user_locale();
		}

		return get_locale();
	}
	/**
	 * Try to get translation details from cache, otherwise retrieve them, then parse them.
	 *
	 * @access private
	 * @return void
	 * @since 1.6.4
	 */
	private static function translation_details() {
		$set = self::find_or_initialize_translation_details();

		self::$translation_exists = ! is_null( $set );
		self::$translation_loaded = is_textdomain_loaded(self::$textdomain );

		self::parse_translation_set( $set );
	}
	/**
	 * Set the needed private variables based on the results from Yoast Translate
	 *
	 * @param object $set The translation set
	 *
	 * @access private
	 * @since 1.6.4
	 */
	private static function parse_translation_set( $set ) {
		if (self::$translation_exists && is_object( $set ) ) {
			self::$locale_name        = $set->name;
			self::$percent_translated = $set->percent_translated;
		} else {
			self::$locale_name        = '';
			self::$percent_translated = '';
		}
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
	/**
	 * Try to find the transient for the translation set or retrieve them.
	 *
	 * @access private
	 *
	 * @return object|null
	 * @since 1.6.4
	 */
	private static function find_or_initialize_translation_details() {
		$set = get_transient('wpematico_i18n_notif_obj_' . self::$locale );
		if ( ! $set ) {
			$set = self::retrieve_translation_details();
			set_transient('wpematico_i18n_notif_obj_' .self::$locale, $set, DAY_IN_SECONDS );
		}

		return $set;
	}
	/**
	 * Retrieve the translation details from Yoast Translate
	 *
	 * @access private
	 * @return object|null
	 * @since 1.6.4
	 */
	private static function retrieve_translation_details() {

		$resp = wp_remote_get(self::$api_url );
		if ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) !== 200 ) {
			return null;
		}
		$body = wp_remote_retrieve_body( $resp );
		unset( $resp );

		if ( $body ) {
			$body = json_decode( $body );
			if ( empty( $body->translation_sets ) ) {
				return null;
			}
			foreach ( $body->translation_sets as $set ) {
				if ( ! property_exists( $set, 'wp_locale' ) ) {
					continue;
				}

				if (self::$locale === $set->wp_locale ) {
					return $set;
				}
			}
		}

		return null;
	}
	
	/**
	* Static function show_short_language
	* @access public
	* @return String with language identifier.
	* @since 1.6.4
	*/
	public static function show_short_language() {
		return  substr(self::get_admin_locale(), 0, 2);
	}
	/**
	* Static function save_iconeyes_callback
	* @access public
	* @return void
	* @since 1.6.4
	*/
	public static function ajax_close_callback() {
		//save option
		$current_numbers = self::get_number_of_campaigns_post();
		$current_levels = self::get_levels_notifications($current_numbers);
		update_option('wpematico_level_tnotifications', $current_levels);
		wp_die();
	}
	/**
	* Static function form_wpmatico_traslate
	* @access public
	* @return void
	* @since 1.6.4
	*/
	public static function form_wpmatico_traslate() {
		self::translation_details();
		$message = '';

		$current_numbers = self::get_number_of_campaigns_post();
		$current_levels = self::get_levels_notifications($current_numbers);
		$level_notifications = get_option('wpematico_level_tnotifications', array(0, 0));

		$show_notice = false;
		if ( $current_levels[0] > $level_notifications[0] || $current_levels[1] > $level_notifications[1] ) {
			$show_notice = true;
		}
		if ( ! $show_notice ) {
			return;
		}

		if (self::$translation_exists && self::$translation_loaded && self::$percent_translated < 90 ) {
			$message = __( 'As you can see, there is a translation of this plugin in %1$s. This translation is currently %3$d%% complete. We need your help to make it complete and to fix any errors. Please register at %4$s to help complete the translation to %1$s!', self::$textdomain );
		} else if ( ! self::$translation_loaded && self::$translation_exists ) {
			$message = __( 'You\'re using WordPress in %1$s. While %2$s has been translated to %1$s for %3$d%%, it\'s not been shipped with the plugin yet. You can help! Register at %4$s to help complete the translation to %1$s!', self::$textdomain );
		} else if ( ! self::$translation_exists ) {
			$message = __( 'You\'re using WordPress in a language we don\'t support yet. We\'d love for %2$s to be translated in that language too, but unfortunately, it isn\'t right now. You can change that! Register at %4$s to help translate it!', self::$textdomain );
		}

		$registration_link = sprintf( '<a href="%1$s">%2$s</a>', esc_url(self::$plugin_translate_link), esc_html(self::$plugin_name_translate) );
		$message           = sprintf( $message, esc_html(self::$locale_name ), esc_html(self::$plugin_name),  self::$percent_translated, $registration_link );



		$style_wpmatico_traslate = '';
		$style_wpmatico_traslate_div = '';
		$class_iconeyes = 'dashicons  dashicons-visibility';

		$wpmatico_languaje = strtoupper(self::show_short_language());
		if($wpmatico_languaje != 'EN'){
			$icon_eyes_status = get_option("icon_eyes_status", '');
			if(strpos($icon_eyes_status, 'dashicons-no') !== false ) {
				$style_wpmatico_traslate = "display:none !important";

			}else if(strpos($icon_eyes_status, 'dashicons-visibility') !== false ){
				$class_iconeyes = "dashicons  dashicons-visibility";
			
			}else if(strpos($icon_eyes_status, 'dashicons-hidden') !== false){
				$class_iconeyes = "dashicons  dashicons-hidden";
				$style_wpmatico_traslate_div = 'display:none;';
			}

?>	
<!--#########################HTML STRUCTURE#############################-->	
	<div id="post-body">
		<div class="div-wpmatico-traslate" style="<?php print($style_wpmatico_traslate); ?>">
			<h3>Translations of WPeMatico
			 <span class="icon-minimizar-div  <?php print($class_iconeyes); ?>" style="margin-right: 30px;" ></span>
			 <span class="icon-cerrar-div dashicons dashicons-no"></span></h3>
			
			 <div class="description-traslate" style="<?php print($style_wpmatico_traslate_div); ?>">
				<p class="parr-wpmatico-traslate"><?php echo $message; ?></p>
				<img class="img-wpmatico-traslate" src="<?php echo WPeMatico :: $uri ; ?>/images/icon-512x512.jpg" title=""></a><br />
			</div>
		</div>
	</div>
<!--####################STYLE HTML DIV################################33-->
	<style type="text/css">
		.div-wpmatico-traslate{background-color: white; padding:10px; padding-top: 0px;position: relative;width: 100%; border:1px solid #D9D9D9; margin-bottom: 20px; margin-top: 5px; box-sizing: border-box;}
		.description-traslate{display: flex;}
		.icon-cerrar-div,.icon-minimizar-div{position: absolute; top: 0; right: 0; font-size: 25px; margin-top: -5px; cursor: pointer;}
		.div-wpmatico-traslate h3{color: #4C5054; position: relative; border-bottom: 1px solid #ccc; padding-bottom: 10px;}
		.parr-wpmatico-traslate{padding-right: 10px;}
		.img-wpmatico-traslate{max-height: 130px; }

	</style>

<!--######################SCRIPT HTML DIV###################################-->
	<script type="text/javascript">
		jQuery(document).ready(function($){
			//cerramos div
			var icon_plus = 'dashicons-visibility';
			var icon_dismis = 'dashicons-hidden';

			//creating ajax function iconeyes
			function close_translate_notification(){
				var data = {
					'action': 'wpematico_close_tnotification',
				};
				// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
				jQuery.post(ajaxurl, data, function(response) {
					//response
				});
			}

			//minimizar el div
			$(".icon-minimizar-div").on("click", function(){
				$('.description-traslate').slideToggle(300);
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
			$(".icon-cerrar-div").on("click", function(){
				$(this).parent().parent().slideUp(500);
				//ajax Icon Save
				close_translate_notification();
			});
		});
	</script>

		<?php
		}
	}
}
wpe_notification_traslate::hooks();
?>