<?php 
// don't load directly 
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}


if (!class_exists('wpematico_campaign_preview_item')) :

class wpematico_campaign_preview_item {
	public static $cfg;
	public static $getting_post;
	public static $current_item_args = array();
	public static $item_hash;
	/**
	* Static function hooks
	* @access public
	* @return void
	* @since 1.9
	*/
	public static function hooks() {
		add_action('admin_post_wpematico_campaign_preview_item', array(__CLASS__, 'print_preview_item'));
		add_action('wpematico_preview_item_print_styles', array(__CLASS__, 'styles'));
		add_action('wpematico_preview_item_print_scripts', array(__CLASS__, 'scripts'));
		add_action('wp_ajax_wpematico_preview_get_item', array(__CLASS__, 'ajax_get_item_post'));
	
		add_filter('wpematico_preview_item_campaign', array(__CLASS__, 'campaign_to_preview_item'), 10, 3);
		add_filter('wpematico_allow_insertpost', array(__CLASS__, 'allow_inserting_preview_item'), 9999, 3);
	}
	/**
	* Static function campaign_to_preview_item
	* This function filters the campaign values before fetch item.
	* @access public
	* @return void
	* @since 1.9
	*/
	public static function campaign_to_preview_item($campaign, $feed, $cfg) {
		$campaign['campaign_no_setting_img'] = true;
		$campaign['campaign_imgcache'] = false;
		$campaign['campaign_attach_img'] = false;
		$campaign['campaign_nolinkimg'] = false;
		$campaign['campaign_image_srcset'] = false;

		$campaign['campaign_no_setting_audio'] = true;
		$campaign['campaign_audio_cache'] = false;
		$campaign['campaign_attach_audio'] = false;
		$campaign['campaign_nolink_audio'] = false;

		$campaign['campaign_no_setting_video'] = true;
		$campaign['campaign_video_cache'] = false;
		$campaign['campaign_attach_video'] = false;
		$campaign['campaign_nolink_video'] = false;

		return $campaign;
	}
	/**
	* Static function allow_posting_preview_item
	* @access public
	* @return void
	* @since version
	*/
	public static function allow_inserting_preview_item($allow, $fetch, $args ) {
		if (!empty(self::$getting_post)) {
			$allow = false;
			self::$current_item_args = $args;
		}
		return $allow;
	}
	/**
	* Static function fetch_preview_item
	* This function takes care of execute the functions to fetch the item.
	* @access public
	* @return void
	* @since 1.9
	*/
	public static function get_current_preview_item($campaign_id, $campaign, $feed) {
		if (! class_exists('wpematico_campaign_fetch')) {
			require_once(dirname(__FILE__).'/campaign_fetch.php');
		}

		self::$getting_post = true;
		$campaign_fetch = new wpematico_campaign_fetch(0);
		$campaign_fetch->cfg = self::$cfg;
		$campaign_fetch->campaign_id = $campaign_id;
		$campaign_fetch->campaign = $campaign;

		$campaign_fetch->set_actions_and_filters();
		
		if(has_action('Wpematico_init_fetching')) do_action('Wpematico_init_fetching', $campaign_fetch->campaign);
		
		
		if($campaign['campaign_type']=="feed" or $campaign['campaign_type']=="youtube" or $campaign['campaign_type']=="bbpress" ) { 		// Access the feed
			
			$fetch_feed_params = array(
				'url' 			=> $feed,
				'stupidly_fast' => true,
				'max' 			=> 0,
				'order_by_date' => false,
				'force_feed' 	=> false,
				'disable_simplepie_notice' => true,
			);
			$fetch_feed_params = apply_filters('wpematico_preview_item_fetch_params', $fetch_feed_params, 0, $campaign);
			$simplepie =  WPeMatico::fetchFeed($fetch_feed_params);
		
		}else {
			$simplepie = apply_filters('Wpematico_process_fetching', $campaign);
		}



		$item_to_fetch = null;

		foreach($simplepie->get_items() as $item) {
			if (self::$item_hash == wpematico_campaign_preview::get_item_hash($item)) { 
				$item_to_fetch = $item;
				break;
			} 
		}

		if (empty($item_to_fetch)) {
			return false;
		}
		$campaign_fetch->processItem($simplepie, $item_to_fetch, $feed);
		if (empty(self::$current_item_args)) {
			return false;
		}

		return true;
	}
	/**
	* Static function ajax_get_item_post
	* This function takes care of manage requests to print of item preview.
	* @access public
	* @return void
	* @since 1.9
	*/
	public static function ajax_get_item_post() {
		$nonce = '';
		if (isset($_REQUEST['nonce_get_item'])) {
			$nonce = sanitize_text_field($_REQUEST['nonce_get_item']);
		}
		
		if (!wp_verify_nonce($nonce, 'campaign-preview-get-item')) {
		    status_header(404);
		    die(__('Security check.', 'wpematico'));
		} 

		self::$cfg = get_option(WPeMatico::OPTION_KEY);

		
		$campaign_id = absint($_REQUEST['campaign_id']);
		if (empty($campaign_id)) {
			status_header(404);
			die(__('The campaign is invalid.', 'wpematico'));
		} 
		$campaign = WPeMatico::get_campaign($campaign_id);

		if (empty($_REQUEST['item_hash'])) {
			status_header(404);
			die(__('The item is invalid.', 'wpematico'));
		}
		self::$item_hash = sanitize_text_field($_REQUEST['item_hash']);

		if (empty($_REQUEST['feed'])) {
			status_header(404);
			die(__('The feed is invalid.', 'wpematico'));
		}
		$feed = $_REQUEST['feed'];

		$campaign = apply_filters('wpematico_preview_item_campaign', $campaign, $feed, self::$cfg);
		

		if (! self::get_current_preview_item($campaign_id, $campaign, $feed) ) {
			status_header(404);
			die(__('The preview of the item has failed.', 'wpematico'));
		}
		?>
		<div class="preview-page-post-title">
			<h2><?php echo self::$current_item_args['post_title']; ?></h2>
		</div>
		
		<div id="preview-page-post-content">
			<?php echo self::$current_item_args['post_content']; ?>
		</div>
		<?php

		die();

	}

	
	/**
	* Static function styles
	* @access public
	* @return void
	* @since 1.9
	*/
	public static function styles() {
		wp_enqueue_style('wpematico-campaign-preview-item', WPeMatico::$uri  . 'app/css/campaign_preview_item.css', array(), WPEMATICO_VERSION);	
	}
	/**
	* Static function scripts
	* @access public
	* @return void
	* @since 1.9
	*/
	public static function scripts() {
		wp_enqueue_script('wpematico-campaign-preview-item', WPeMatico::$uri  . 'app/js/campaign_preview_item_feed.js', array( 'jquery' ), WPEMATICO_VERSION, true );
		wp_localize_script('wpematico-campaign-preview-item', 'wpematico_preview_item', 
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'is_manual_addon_active' => (defined('WPEMATICO_MANUAL_FETCHING_VER') ? true : false),
				'is_manual_addon_msg' => __('This action is available for use with the Manual Feetching Addon.', 'wpematico'),
			)
		);
		
	}

	
	/**
	* Static function print_preview_item
	* @access public
	* @return void
	* @since 1.9
	*/
	public static function print_preview_item() {
		$nonce = '';
		if (isset($_REQUEST['_wpnonce'])) {
			$nonce = sanitize_text_field($_REQUEST['_wpnonce']);
		}
		
		if (!wp_verify_nonce($nonce, 'campaign-preview-item-nonce')) {
		    wp_die('Security check'); 
		} 

		self::$cfg = get_option(WPeMatico::OPTION_KEY);

		
		$campaign_id = absint($_REQUEST['campaign']);
		if (empty($campaign_id)) {
			wp_die(__('The campaign is invalid.', 'wpematico'));
		} 
		$campaign = WPeMatico::get_campaign($campaign_id);

		if (empty($_REQUEST['item_hash'])) {
			wp_die(__('The item is invalid.', 'wpematico'));
		}
		$item_hash = sanitize_text_field($_REQUEST['item_hash']);

		
		if (empty($_REQUEST['feed'])) {
			wp_die(__('The feed is invalid.', 'wpematico'));
		}
		$feed = $_REQUEST['feed'];


		if (defined('WP_DEBUG') and WP_DEBUG){
			set_error_handler('wpematico_joberrorhandler',E_ALL | E_STRICT);
		}else{
			set_error_handler('wpematico_joberrorhandler',E_ALL & ~E_NOTICE);
		}
		
		
		

		$have_gettext = function_exists('__');

		if ( ! did_action( 'admin_head' ) ) :
			if ( !headers_sent() ) {
				status_header(200);
				nocache_headers();
				header( 'Content-Type: text/html; charset=utf-8' );
			}

			
			$text_direction = 'ltr';
			if ( function_exists( 'is_rtl' ) && is_rtl() ) {
				$text_direction = 'rtl';
			}

	?>
	<!DOCTYPE html>
	<!-- Ticket #11289, IE bug fix: always pad the error page with enough characters such that it is greater than 512 bytes, even after gzip compression abcdefghijklmnopqrstuvwxyz1234567890aabbccddeeffgghhiijjkkllmmnnooppqqrrssttuuvvwwxxyyzz11223344556677889900abacbcbdcdcededfefegfgfhghgihihjijikjkjlklkmlmlnmnmononpopoqpqprqrqsrsrtstsubcbcdcdedefefgfabcadefbghicjkldmnoepqrfstugvwxhyz1i234j567k890laabmbccnddeoeffpgghqhiirjjksklltmmnunoovppqwqrrxsstytuuzvvw0wxx1yyz2z113223434455666777889890091abc2def3ghi4jkl5mno6pqr7stu8vwx9yz11aab2bcc3dd4ee5ff6gg7hh8ii9j0jk1kl2lmm3nnoo4p5pq6qrr7ss8tt9uuvv0wwx1x2yyzz13aba4cbcb5dcdc6dedfef8egf9gfh0ghg1ihi2hji3jik4jkj5lkl6kml7mln8mnm9ono
	-->
	<html xmlns="http://www.w3.org/1999/xhtml" <?php if ( function_exists( 'language_attributes' ) && function_exists( 'is_rtl' ) ) language_attributes(); else echo "dir='$text_direction'"; ?>>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width">
		<?php
		if ( function_exists( 'wp_no_robots' ) ) {
			wp_no_robots();
		}
		?>
		<title><?php _e('WPeMatico Preview Feed', 'wpematico'); ?></title>
		<?php
			if ( 'rtl' == $text_direction ) {
				echo '<style type="text/css"> body { font-family: Tahoma, Arial; } </style>';
			}
			do_action('wpematico_preview_item_print_styles');
			wp_print_styles();
			do_action('wpematico_preview_item_print_scripts');
			wp_print_scripts();
		?>
	</head>
	<body>

	<?php endif; // ! did_action( 'admin_head' ) ?>


		<div id="preview-page">
			<input type="hidden" id="campaign_id" name="campaign_id" value="<?php echo $campaign_id; ?>"/>
			<input type="hidden" id="feed" name="feed" value="<?php echo $feed; ?>"/>
			<input type="hidden" id="item_hash" name="item_hash" value="<?php echo $item_hash; ?>"/>
			<input type="hidden" id="nonce_get_item" name="nonce_get_item" value="<?php echo wp_create_nonce('campaign-preview-get-item'); ?>"/>
				<div id="preview-post-actions">
					<?php if (!empty($_REQUEST['return_url'])) : ?>
						<a href="<?php echo esc_url($_REQUEST['return_url']); ?>" class="button">Back</a>
					<?php endif; ?>
					<button type="button" data-itemhash="<?php echo $item_hash; ?>" data-feed="<?php echo $feed; ?>" class="item_fetch cpanelbutton dashicons dashicons-welcome-add-page" title="<?php esc_attr_e('Fetch Now', 'wpematico'); ?>"></button>
					<?php do_action('wpematico_preview_item_actions', $item); ?>
					<span id="image_loading" style="display: none;" class="dashicons dashicons-admin-generic wpe_spinner"></span>
				</div>
				
				<div id="preview-post-content">
					<div class="preview-page-post-title">
						<h2><span class="dashicons dashicons-admin-generic wpe_spinner" style="margin-top: 8px;"></span> <?php _e('Loading post title...', 'wpematico'); ?></h2>
					</div>
					
					<div id="preview-page-post-content">
						<span class="dashicons dashicons-admin-generic wpe_spinner"></span> <?php _e('Loading post content...', 'wpematico'); ?>
					</div>

				</div>
				
		</div>
		
	</body>
	</html>
	<?php
	die();

	}

}
endif;
wpematico_campaign_preview_item::hooks();