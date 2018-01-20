<?php 
// don't load directly 
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}


if (!class_exists('wpematico_campaign_preview')) :

class wpematico_campaign_preview {
	public static $cfg;
	/**
	* Static function hooks
	* @access public
	* @return void
	* @since 1.9
	*/
	public static function hooks() {
		add_action('admin_post_wpematico_campaign_preview', array(__CLASS__, 'print_preview'));
		add_action('wpematico_preview_print_styles', array(__CLASS__, 'styles'));
		add_action('wpematico_preview_print_scripts', array(__CLASS__, 'scripts'));
	}

	/**
	* Static function styles
	* @access public
	* @return void
	* @since 1.9
	*/
	public static function styles() {
		wp_enqueue_style('wpematico-campaign-preview', WPeMatico::$uri  . 'app/css/campaign_preview.css', array(), WPEMATICO_VERSION);	
	}
	/**
	* Static function scripts
	* @access public
	* @return void
	* @since 1.9
	*/
	public static function scripts() {
		wp_enqueue_script('wpematico-campaign-preview',WPeMatico::$uri  . 'app/js/campaign_preview_feed.js', array( 'jquery' ), WPEMATICO_VERSION, true );
		wp_localize_script('wpematico-campaign-preview', 'wpematico_preview', 
			array(
				'is_manual_addon_active' => (defined('WPEMATICO_MANUAL_FETCHING_VER') ? true : false),
				'is_manual_addon_msg' => __('This action is available for use with the Manual Feetching Addon.', 'wpematico'),
			)
		);
	}

	/**
	* Static function get_feeds_items_statues
	* @access public
	* @return void
	* @since 1.9
	*/
	public static function get_feeds_items_statues($feed, $campaign) {
		$fetch_feed_params = array(
			'url' 			=> $feed,
			'stupidly_fast' => true,
			'max' 			=> 0,
			'order_by_date' => false,
			'force_feed' 	=> false,
			'disable_simplepie_notice' => true,
		);
		$fetch_feed_params = apply_filters('wpematico_preview_fetch_feed_params', $fetch_feed_params, 0, $campaign);
		$simplepie =  WPeMatico::fetchFeed($fetch_feed_params);
		$campaign_id = $campaign['ID'];
		$count = 0;
		$prime = true;
		$lasthash = array();
		$currenthash = array();


		$posts_fetched = array();
		$posts_next = array();
		$breaked = false;

		foreach($simplepie->get_items() as $item) {
			if($prime){
				//with first item get the hash of the last item (new) that will be saved.
				$lasthash[$feed] = md5($item->get_permalink()); 
				$prime = false;
			}

			$currenthash[$feed] = md5($item->get_permalink()); 
			if( !$breaked && (!self::$cfg['allowduplicates'] || !self::$cfg['allowduptitle'] || !self::$cfg['allowduphash']  || self::$cfg['add_extra_duplicate_filter_meta_source']) ){
				if( !self::$cfg['allowduphash'] ){
					// chequeo a la primer coincidencia sale del foreach
					$lasthashvar = '_lasthash_'.sanitize_file_name($feed);
					$hashvalue = get_post_meta($campaign_id, $lasthashvar, true );
					if (!isset($campaign[$feed]['lasthash'] ) ) $campaign[$feed]['lasthash'] = '';
					
					$dupi = ( $campaign[$feed]['lasthash'] == $currenthash[$feed] ) || 
								( $hashvalue == $currenthash[$feed] ); 
					if ($dupi) {
						$posts_fetched[md5($item->get_permalink())] = true;
						trigger_error(sprintf(__('Found duplicated hash \'%1s\'', 'wpematico' ),$item->get_permalink()).': '.$currenthash[$feed] ,E_USER_NOTICE);
						if( !self::$cfg['jumpduplicates'] ) {
							trigger_error(__('Filtering duplicated posts.', 'wpematico' ),E_USER_NOTICE);
							$breaked = true;
							continue;
						}else {
							trigger_error(__('Jumping duplicated post. Continuing.', 'wpematico' ),E_USER_NOTICE);
							continue;
						}
					}
				}
				if( !self::$cfg['allowduptitle'] ){
					if(WPeMatico::is_duplicated_item($campaign, $feed, $item)) {
						$posts_fetched[md5($item->get_permalink())] = true;
						trigger_error(sprintf(__('Found duplicated title \'%1s\'', 'wpematico' ),$item->get_title()).': '.$currenthash[$feed] ,E_USER_NOTICE);
						if( !self::$cfg['jumpduplicates'] ) {
							trigger_error(__('Filtering duplicated posts.', 'wpematico' ),E_USER_NOTICE);
							$breaked = true;
							continue;
						}else {
							trigger_error(__('Jumping duplicated post. Continuing.', 'wpematico' ),E_USER_NOTICE);
							continue;
						}
					}
				}

			} else {
				
				
			}
			if($breaked && WPeMatico::is_duplicated_item($campaign, $feed, $item)) {
				$posts_fetched[md5($item->get_permalink())] = true;
			} else if ($breaked) {
				
			} else {
				$posts_next[md5($item->get_permalink())] = true;
			}
			$count++;	  
			if($count == $campaign['campaign_max']) {
				trigger_error(sprintf(__('Campaign fetch limit reached at %1s.', 'wpematico' ), $campaign['campaign_max']),E_USER_NOTICE);
				$breaked = true;
				continue;
			}
		}
		return array('next' => $posts_next, 'fetched' => $posts_fetched, 'simplepie' => $simplepie);
	}
	/**
	* Static function print_preview
	* @access public
	* @return void
	* @since 1.9
	*/
	public static function print_preview() {
		$nonce = '';
		if (isset($_REQUEST['_wpnonce'])) {
			$nonce = $_REQUEST['_wpnonce'];
		}
		
		if (!wp_verify_nonce($nonce, 'campaign-preview-nonce')) {
		    wp_die('Security check'); 
		} 

		self::$cfg = get_option(WPeMatico::OPTION_KEY);

		
		$campaign_id = intval($_REQUEST['p']);
		if (empty($campaign_id)) {
			wp_die(__('The campaign is invalid.', 'wpematico'));
		} 
		$campaign = WPeMatico::get_campaign($campaign_id);

		if (defined('WP_DEBUG') and WP_DEBUG){
			set_error_handler('wpematico_joberrorhandler',E_ALL | E_STRICT);
		}else{
			set_error_handler('wpematico_joberrorhandler',E_ALL & ~E_NOTICE);
		}
		$post_to_show = array();
		
		
		foreach($campaign['campaign_feeds']  as $kf => $feed) {
			$feed_data = self::get_feeds_items_statues($feed, $campaign);
			$simplepie = $feed_data['simplepie'];
			foreach($simplepie->get_items() as $item) {
				$item_hash = md5($item->get_permalink()); 
				if (!empty($feed_data['next'][$item_hash])) {
				  	$post_to_show[] = $item;
				 }
			}
		}
		

		$items_to_show = array();

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
			do_action('wpematico_preview_print_styles');
			wp_print_styles();
			do_action('wpematico_preview_print_scripts');
			wp_print_scripts();
		?>
	</head>
	<body>

	<?php endif; // ! did_action( 'admin_head' ) ?>

	<?php 
		
		
		
	?>
		<div id="preview-page">

			<form id="wpematico_bulk_actions_form" action="<?php echo admin_url('admin-post.php'); ?>" method="post">
				<input type="hidden" name="action" value="wpematico_bulk_action_handler"/>
			    <?php wp_nonce_field('wpematico_bulk_actions'); ?> 

				<div class="feed-title">
					<h2>Campaign Preview</h2>
				</div>
				
				<div class="table-responsive">
				  <table class="table-preview">
				  	<thead>
				  		<tr>
				  			<th id="cb" class="check-column">
				  				
				  			</th>
				  			<th><?php esc_html_e('Post', 'wpematico'); ?></th>
				  			<th><?php esc_html_e('Status', 'wpematico'); ?></th>
				  			<th><?php esc_html_e('Actions', 'wpematico'); ?></th>
				  		</tr>
				  	</thead>
				  	<tbody>
				  		<?php 
				  			$return_url = urlencode(admin_url('admin-post.php?action=wpematico_campaign_preview&p='.$campaign_id.'&_wpnonce=' . wp_create_nonce('campaign-preview-nonce')));
				  			foreach($post_to_show as $item) : 
				  				
				  				
				  				$description = $item->get_description(); 
				  				$description = wpematico_convert_to_utf8($description);
				  				$description = strip_tags($description);
				  				if (strlen($description) > 303) {
				  					$description = mb_substr($description, 0, 300);
				  					$description .= '...'; 
				  				}

				  				$title = $item->get_title();
				  				$title = wpematico_convert_to_utf8($title);
				  				$title = strip_tags($title);
				  				if (strlen($title) > 103) {
				  					$title = mb_substr($title, 0, 100);
				  					$title .= '...'; 
				  				}
				  				$feed_url =  urlencode($item->get_feed()->feed_url);
				  				$item_hash = md5($item->get_permalink());
				  				$nonce_item = wp_create_nonce('campaign-preview-item-nonce');
				  				$post_link_preview = admin_url('admin-post.php?action=wpematico_campaign_preview_item&_wpnonce='.$nonce_item.'&campaign='.$campaign_id.'&item_hash='.$item_hash.'&feed='.$feed_url.'&return_url='.$return_url);



				  			?>
						    <tr id="tr_item_<?php echo $item_hash; ?>" class="feed-nextfetch">
						    	<td>
						    		
						    	</td>
						    	<td>
						    		<a href="<?php echo $post_link_preview; ?>" id="pfeed-id"><?php echo esc_html($title); ?></a>
						    		<span id="pfeed-date"><?php echo $item->get_date(); ?></span>
						    		<p><?php echo esc_html($description); ?></p>

						    	</td>
						    	<td>
						    		<span id="status_item_<?php echo $item_hash; ?>" class="status nextfetch"><?php echo  __('Next fetch', 'wpematico'); ?></span>
						    	</td>
						    	<td>
						    		<button type="button" data-itemhash="<?php echo $item_hash; ?>" data-feed="<?php echo $feed_url; ?>" class="item_fetch cpanelbutton dashicons dashicons-welcome-add-page" title="Run Once"></button>
						    		<?php do_action('wpematico_preview_campaign_item_actions', $item); ?>
						    	</td>
						    </tr>
						<?php endforeach; ?>
					    
				    </tbody>
				  </table>
				</div>
				

			</form>
		</div>
		
	</body>
	</html>
	<?php
	die();

	}

}
endif;
wpematico_campaign_preview::hooks();