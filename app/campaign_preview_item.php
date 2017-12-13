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
	public static $getting_post = false;
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
	}
	/**
	* Static function ajax_get_item_post
	* @access public
	* @return void
	* @since 1.9
	*/
	public static function ajax_get_item_post() {
		$nonce = '';
		if (isset($_REQUEST['nonce_get_item'])) {
			$nonce = $_REQUEST['nonce_get_item'];
		}
		
		if (!wp_verify_nonce($nonce, 'campaign-preview-item-nonce')) {
		    wp_die('Security check'); 
		} 

		self::$cfg = get_option(WPeMatico::OPTION_KEY);

		
		$campaign_id = intval($_REQUEST['campaign']);
		if (empty($campaign_id)) {
			status_header(404);
			wp_die(__('The campaign is invalid.', 'wpematico'), 404);
		} 
		$campaign = WPeMatico::get_campaign($campaign_id);

		if (empty($_REQUEST['item_hash'])) {
			status_header(404);
			wp_die(__('The item is invalid.', 'wpematico'), 404);
		}
		$item_hash = $_REQUEST['item_hash'];

		if (empty($_REQUEST['feed'])) {
			status_header(404);
			wp_die(__('The feed is invalid.', 'wpematico'), 404);
		}
		$feed = $_REQUEST['feed'];
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
			$nonce = $_REQUEST['_wpnonce'];
		}
		
		if (!wp_verify_nonce($nonce, 'campaign-preview-item-nonce')) {
		    wp_die('Security check'); 
		} 

		self::$cfg = get_option(WPeMatico::OPTION_KEY);

		
		$campaign_id = intval($_REQUEST['campaign']);
		if (empty($campaign_id)) {
			wp_die(__('The campaign is invalid.', 'wpematico'));
		} 
		$campaign = WPeMatico::get_campaign($campaign_id);

		if (empty($_REQUEST['item_hash'])) {
			wp_die(__('The item is invalid.', 'wpematico'));
		}
		$item_hash = $_REQUEST['item_hash'];

		
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
		<title><?php esc_html_e('WPeMatico Preview Feed', 'wpematico'); ?></title>
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

				<?php if (!empty($_REQUEST['return_url'])) : ?>
					<a href="<?php echo $_REQUEST['return_url']; ?>" class="button">Back</a>
				<?php endif; ?>
				
				<div class="preview-page-post-title">
					<h2><img src="<?php echo admin_url('images/wpspin_light.gif'); ?>"> <?php _e('Loading post title...', 'wpematico'); ?></h2>

				</div>
				
				<div id="preview-page-post-content">
					<img src="<?php echo admin_url('images/wpspin_light.gif'); ?>"> <?php _e('Loading post content...', 'wpematico'); ?>
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