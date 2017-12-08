<?php
// don't load directly 
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
if (!class_exists('wpematico_preview')) :

class wpematico_preview {
	public static $cfg = array();
	/**
	* Static function hooks
	* @access public
	* @return void
	* @since 1.9
	*/
	public static function hooks() {

		
		add_action('admin_post_wpematico_preview', array(__CLASS__, 'print_preview'));
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
		wp_enqueue_style('wpematico-preview', WPeMatico::$uri . 'app/css/campaign_preview.css', array(), WPEMATICO_VERSION);	
	}
	/**
	* Static function scripts
	* @access public
	* @return void
	* @since 1.9
	*/
	public static function scripts() {
		wp_enqueue_script('wpematico-campaign-preview', WPeMatico::$uri .'app/js/campaign_preview.js', array( 'jquery' ), WPEMATICO_VERSION, true );
	}
	/**
	* Static function get_current_paged
	* @access public
	* @return $paged Int with current page of the feed.
	* @since 1.9
	*/
	public static function get_current_paged($feed_url) {
		$paged = 1;
		$parts = parse_url($feed_url);
		if (isset($parts['query'])) {
			parse_str($parts['query'], $query);
			if (isset($query['paged'])) {
				$paged = $query['paged'];
			}
		}
		return $paged;
	}
	/**
	* Static function exist_next_page_feed
	* @access public
	* @return void
	* @since version
	*/
	public static function exist_next_page($feed_url, $curr_simplepie, $campaign) {
		$ret = false;
		$fetch_feed_params = array(
			'url' 			=> $feed_url,
			'stupidly_fast' => true,
			'max' 			=> 0,
			'order_by_date' => false,
			'force_feed' 	=> false,
			'disable_simplepie_notice' => true,
		);
		$fetch_feed_params = apply_filters('wpematico_preview_next_fetch_feed_params', $fetch_feed_params, 0, $campaign);
		$simplepie =  WPeMatico::fetchFeed($fetch_feed_params);
		
		if(empty($simplepie->error())) {
			$hash_next = md5($simplepie->raw_data);
			$hash_curr = md5($curr_simplepie->raw_data);
			if ($hash_next != $hash_curr) {
				$ret = true;
			}
		}
		return $ret;
	}

	/**
	* Static function print_preview
	* @access public
	* @return void
	* @since 1.9
	*/
	public static function print_preview($message) {
		$nonce = '';
		if (isset($_REQUEST['nonce'])) {
			$nonce = $_REQUEST['nonce'];
		}
		
		if (!wp_verify_nonce($nonce, 'preview-feed-nonce')) {
		    wp_die('Security check'); 
		} 

		self::$cfg = get_option(WPeMatico::OPTION_KEY);

		if (!empty($_REQUEST['feed'])) {
			$feed = $_REQUEST['feed'];
		} else {
			wp_die(__('The feed is invalid.', 'wpematico'));
		}
		$campaign_id = intval($_REQUEST['campaign']);
		if (empty($campaign_id)) {
			wp_die(__('The campaign is invalid.', 'wpematico'));
		} 
		$campaign = WPeMatico::get_campaign($campaign_id);

		if (defined('WP_DEBUG') and WP_DEBUG){
			set_error_handler('wpematico_joberrorhandler',E_ALL | E_STRICT);
		}else{
			set_error_handler('wpematico_joberrorhandler',E_ALL & ~E_NOTICE);
		}

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


		$current_paged = self::get_current_paged($feed);
		$next_feed = add_query_arg('paged', $current_paged+1, $feed);
		if ($current_paged > 1) {
			$prev_feed = add_query_arg('paged', $current_paged-1, $feed);
		}
		$exist_next_page = self::exist_next_page($next_feed, $simplepie, $campaign);

		$count = 0;
		$prime = true;
		$lasthash = array();
		$currenthash = array();


		$posts_fetched = array();
		$posts_next = array();

		foreach($simplepie->get_items() as $item) {
			if($prime){
				//with first item get the hash of the last item (new) that will be saved.
				$lasthash[$feed] = md5($item->get_permalink()); 
				$prime = false;
			}

			$currenthash[$feed] = md5($item->get_permalink()); 
			if( !self::$cfg['allowduplicates'] || !self::$cfg['allowduptitle'] || !self::$cfg['allowduphash']  || self::$cfg['add_extra_duplicate_filter_meta_source']){
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
							break;
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
							break;
						}else {
							trigger_error(__('Jumping duplicated post. Continuing.', 'wpematico' ),E_USER_NOTICE);
							continue;
						}
					}
				}

			}
			$posts_next[md5($item->get_permalink())] = true;
			$count++;	  
			if($count == $campaign['campaign_max']) {
				trigger_error(sprintf(__('Campaign fetch limit reached at %1s.', 'wpematico' ), $campaign['campaign_max']),E_USER_NOTICE);
				break;
			}
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
					<h2><?php echo esc_html($simplepie->get_title()); ?> </h2>
				</div>
				<div class="table-nav">
				    <div class="alignleft actions bulkactions">
				        
				        	
					        <label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e('Select bulk action', 'wpematico');?></label>
					        <select name="bulk_action" id="bulk-action-selector-top">
					            <option value="-1"><?php esc_html_e('Bulk Actions', 'wpematico');?></option>
					            <?php 
					            	$bulk_actions = apply_filters('wpematico_preview_bulk_actions', array('fetch_items' => __('Fetch Items', 'wpematico')));
					            	foreach ($bulk_actions as $value => $text) : ?>
					       
					            	 <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($text); ?></option>
					            <?php	
					            	endforeach;
					            ?>
					        </select>
					        <input type="submit" id="doaction" class="button action" value="<?php esc_attr_e('Apply', 'wpematico'); ?>"/>
				    	
				    </div>
				    <h2 class="screen-reader-text"><?php esc_attr_e('Posts list navigation', 'wpematico'); ?></h2>
				    <div class="tablenav-pages">
				    	<?php if ($current_paged > 1) : ?>
				        	<a class="prev-page" href="<?php echo admin_url('admin-post.php?action=wpematico_preview&campaign='.$campaign_id.'&nonce='.$nonce.'&feed='.$prev_feed) ?>"><span class="screen-reader-text"><?php esc_attr_e('Previous page', 'wpematico'); ?></span><span aria-hidden="true">‹</span></a>
				        <?php endif; ?>
				    	<span class="displaying-num"><?php echo $current_paged; ?></span>
				       	<?php if ($exist_next_page) : ?>
				        	<a class="next-page" href="<?php echo admin_url('admin-post.php?action=wpematico_preview&campaign='.$campaign_id.'&nonce='.$nonce.'&feed='.$next_feed) ?>"><span class="screen-reader-text"><?php esc_attr_e('Next page', 'wpematico'); ?></span><span aria-hidden="true">›</span></a>
				        <?php endif; ?>
				    </div>
				</div>
				<div class="table-responsive">
				  <table class="table-preview">
				  	<thead>
				  		<tr>
				  			<th id="cb" class="check-column">
				  				<label class="screen-reader-text" for="cb-select-all-1"><?php esc_html_e('Select All', 'wpematico'); ?></label>
				  				<input id="cb-select-all-1" type="checkbox">
				  			</th>
				  			<th><?php esc_html_e('Post', 'wpematico'); ?></th>
				  			<th><?php esc_html_e('Status', 'wpematico'); ?></th>
				  			<th><?php esc_html_e('Actions', 'wpematico'); ?></th>
				  		</tr>
				  	</thead>
				  	<tbody>
				  		<?php 

				  			foreach($simplepie->get_items() as $item) : 
				  				
				  				$is_published = false;
				  				$is_next = false;
				  				$item_hash = md5($item->get_permalink());

				  				if (!empty($posts_fetched[$item_hash])) {
				  					$is_published = true;
				  				}

				  				if (!empty($posts_next[$item_hash])) {
				  					$is_next = true;
				  				}
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
				  				

				  		?>
						    <tr class="<?php echo (($is_published) ? 'pfeed-published' : ($is_next ? 'pfeed-nextfetch' : 'pfeed-unpublished')); ?>">
						    	<td>
						    		<label class="screen-reader-text" for="cb-select"><?php esc_html_e('Select', 'wpematico'); ?></label>
					  				<input id="cb-select-78987" type="checkbox" name="item[]" value="<?php echo $item_hash; ?>">
						    	</td>
						    	<td>
						    		<a href="#" id="pfeed-id" target="_blank"><?php echo esc_html($title); ?></a>
						    		<span id="pfeed-date">miercoles, 5 de diciembre de 2017 2:32 p.m.</span>
						    		<p><?php echo esc_html($description); ?></p>

						    	</td>
						    	<td>
						    		<span class="status <?php echo (($is_published) ? 'published' : ($is_next ? 'nextfetch' : 'unpublished')); ?>"><?php echo (($is_published) ? __('Published', 'wpematico') : ($is_next ? __('Next fetch', 'wpematico') : __('Unpublished', 'wpematico'))); ?></span>
						    	</td>
						    	<td>
						    		<button type="button" class="state_buttons cpanelbutton dashicons dashicons-controls-play" title="Run Once"></button>
						    		<button type="button" disabled="" class="state_buttons cpanelbutton dashicons dashicons-update red"></button><button type="button" class="state_buttons cpanelbutton dashicons dashicons-controls-pause" btn-href="#" title="Stop and deactivate this campaign"></button>
						    	</td>
						    </tr>
						<?php endforeach; ?>
					    
				    </tbody>
				  </table>
				</div>
				<div class="table-nav mt-20">
				    <div class="alignleft actions bulkactions">
				        
				        	
					        <label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e('Select bulk action', 'wpematico');?></label>
					        <select name="bulk_action2" id="bulk-action-selector-top">
					            <option value="-1"><?php esc_html_e('Bulk Actions', 'wpematico');?></option>
					            <?php 
					            	$bulk_actions = apply_filters('wpematico_preview_bulk_actions', array('fetch_items' => __('Fetch Items', 'wpematico')));
					            	foreach ($bulk_actions as $value => $text) : ?>
					       
					            	 <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($text); ?></option>
					            <?php	
					            	endforeach;
					            ?>
					        </select>
					        <input type="submit" id="doaction2" class="button action" value="<?php esc_attr_e('Apply', 'wpematico'); ?>"/>
				    	
				    </div>


				    <h2 class="screen-reader-text"><?php esc_attr_e('Posts list navigation', 'wpematico'); ?></h2>
				    <div class="tablenav-pages">
				    	<?php if ($current_paged > 1) : ?>
				        	<a class="prev-page" href="<?php echo admin_url('admin-post.php?action=wpematico_preview&campaign='.$campaign_id.'&nonce='.$nonce.'&feed='.$prev_feed) ?>"><span class="screen-reader-text"><?php esc_attr_e('Previous page', 'wpematico'); ?></span><span aria-hidden="true">‹</span></a>
				        <?php endif; ?>
				    	<span class="displaying-num"><?php echo $current_paged; ?></span>
				       	<?php if ($exist_next_page) : ?>
				        	<a class="next-page" href="<?php echo admin_url('admin-post.php?action=wpematico_preview&campaign='.$campaign_id.'&nonce='.$nonce.'&feed='.$next_feed) ?>"><span class="screen-reader-text"><?php esc_attr_e('Next page', 'wpematico'); ?></span><span aria-hidden="true">›</span></a>
				        <?php endif; ?>
				    </div>
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
wpematico_preview::hooks();

?>