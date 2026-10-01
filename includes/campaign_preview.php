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
		wp_enqueue_style('wpematico-campaign-preview', WPeMatico::$uri  . 'assets/css/campaign_preview.css', array(), WPEMATICO_VERSION);	
	}
	/**
	* Static function scripts
	* @access public
	* @return void
	* @since 1.9
	*/
	public static function scripts() {
		wp_enqueue_script('wpematico-campaign-preview',WPeMatico::$uri  . 'assets/js/campaign_preview_feed.js', array( 'jquery' ), WPEMATICO_VERSION, true );
		wp_localize_script('wpematico-campaign-preview', 'wpematico_preview', 
			array(
				'is_manual_addon_active' => (defined('WPEMATICO_MANUAL_FETCHING_VER') ? true : false),
				'is_manual_addon_msg' => __('This action comes with the Manual Fetching add-on.', 'wpematico'),
			)
		);
	}
	/**
	* Static function get_item_hash
	* @access public
	* @param $item Object SimplePie Item.
	* @return $hash a item hash id.
	* @since 1.9.0
	*/
	/**
	 * Who may open a preview.
	 *
	 * edit_posts is what CheckFields asks for on the campaign editor, which is the only
	 * screen a preview is reached from.
	 *
	 * @since 2.9
	 * @return string
	 */
	public static function capability() {
		return apply_filters('wpematico_preview_capability', 'edit_posts');
	}

	/**
	 * Whether a preview may be asked for this feed URL.
	 *
	 * ★ The feed comes from the request, not from the campaign, and the server then goes
	 * and fetches it -- and in the Manual Fetching addon publishes what comes back. So it
	 * has to be one of the campaign's own sources. Compared by host rather than string:
	 * the links this screen builds carry the *resolved* address (a Vimeo channel arrives
	 * as its /videos/rss), and paging adds query args, but neither changes the host.
	 *
	 * @since 2.9
	 * @param string $feed
	 * @param array  $campaign
	 * @return bool
	 */
	public static function feed_allowed($feed, $campaign) {
		$host = self::feed_host($feed);
		if ('' === $host) {
			return false;
		}

		$allowed = array();
		foreach ((array) $campaign['campaign_feeds'] as $kf => $stored) {
			// A campaign type may rewrite its address, and a multipage feed answers with
			// a list of them.
			$candidates = array($stored, apply_filters('wpematico_simplepie_url', $stored, $kf, $campaign));
			foreach ($candidates as $candidate) {
				foreach ((array) $candidate as $url) {
					$h = self::feed_host($url);
					if ('' !== $h) {
						$allowed[$h] = true;
					}
				}
			}
		}

		return (bool) apply_filters('wpematico_preview_feed_allowed', isset($allowed[$host]), $feed, $campaign);
	}

	/**
	 * The host of a feed URL, for comparison.
	 *
	 * A campaign may hold its feed without a scheme -- "etruel.com" is a valid entry and
	 * runs fine -- and the address the preview links to is the absolute one SimplePie
	 * ended up reading, so the two have to be normalised before they can be compared.
	 *
	 * @since 2.9
	 * @param string $url
	 * @return string lowercase host without a leading www., or ''
	 */
	public static function feed_host($url) {
		$url = trim((string) $url);
		if ('' === $url) {
			return '';
		}
		$host = (string) wp_parse_url($url, PHP_URL_HOST);
		if ('' === $host) {
			// No scheme: everything landed in the path.
			$host = (string) wp_parse_url('//' . ltrim($url, '/'), PHP_URL_HOST);
		}
		$host = strtolower($host);

		return ('www.' === substr($host, 0, 4)) ? substr($host, 4) : $host;
	}

	public static function get_item_hash($item) {
		$permalink = $item->get_permalink();
		if (!empty($permalink)) {
			$hash = md5($permalink);
		} else {
			$hash = md5($item->get_title());
		}
		return $hash;
	}
	/**
	* Static function get_feeds_items_statues
	* @access public
	* @return void
	* @since 1.9
	*/
	public static function get_feeds_items_statues($feed, $campaign, $fetch_obj = null) {
		if (! class_exists('wpematico_campaign_fetch')) {
			require_once(WPEMATICO_PLUGIN_DIR.'includes/campaign_fetch.php');
		}

		// Same door the run uses: the campaign types read as a feed, the address the
		// campaign holds turned into its feed, and the custom path for sources that have
		// none. $fetch_obj is what wpematico_custom_simplepie is given, so an addon that
		// builds its own feed (Professional reading Vimeo through the API) answers here
		// too and the preview shows what the run would really fetch.
		$simplepie = WPeMatico::open_campaign_feed($feed, $campaign, array(
			'params_filter'				 => 'wpematico_preview_fetch_feed_params',
			'fetch_obj'					 => $fetch_obj,
			'stupidly_fast'				 => true,
			'max'						 => $campaign['campaign_max'],
			'disable_simplepie_notice'	 => true,
		));

		$campaign_id = $campaign['ID'];
		$count = 0;

		$duplicate_options = WPeMatico::get_duplicate_options(self::$cfg, $campaign);

		// The ring of already seen hashes the run fills while jumping duplicates. The
		// preview never read it, so every item in it was announced as pending.
		$last_hashes = array();
		if (!$duplicate_options['allowduphash'] && $duplicate_options['jumpduplicates']) {
			$last_hashes = get_post_meta($campaign_id, '_lasthashes_' . sanitize_file_name($feed), false);
			if (empty($last_hashes)) {
				$last_hashes = array();
			}
		}

		$posts_fetched = array();
		$posts_next = array();
		$breaked = false;

		foreach($simplepie->get_items(0, $campaign['campaign_max']) as $item) {
			$item_hash = self::get_item_hash($item);

			// ★ Hashed the way the run hashes it -- through getReadUrl(), which resolves
			// redirections. Hashing the raw feed permalink instead is why a feed whose
			// links redirect showed every item as pending, run after run.
			$permalink_hash = md5(wpematico_campaign_fetch::getReadUrl($item->get_permalink(), $campaign));

			if (!$breaked) {
				$duplicate = WPeMatico::item_duplicate_state($campaign_id, $campaign, $feed, $item, $duplicate_options, $permalink_hash, $last_hashes);
				if ('' !== $duplicate) {
					$posts_fetched[$item_hash] = true;
					if ('hashes' === $duplicate || $duplicate_options['jumpduplicates']) {
						trigger_error(__('Duplicate skipped, carrying on.', 'wpematico' ),E_USER_NOTICE);
						continue;
					}
					trigger_error(__('Filtering out duplicates.', 'wpematico' ),E_USER_NOTICE);
					$breaked = true;
					continue;
				}
			}

			if($breaked && WPeMatico::is_duplicated_item($campaign, $feed, $item)) {
				$posts_fetched[$item_hash] = true;
			} else {
				$posts_next[$item_hash] = true;
			}
			$count++;

			if($count == $campaign['campaign_max']) {
				/* translators: %s Campaign max fetch input value */
				trigger_error(sprintf(__('Campaign fetch limit reached at %s.', 'wpematico' ), $campaign['campaign_max']),E_USER_NOTICE);
				$breaked = true;
				continue;
			}
		}
		return array('next' => $posts_next, 'fetched' => $posts_fetched, 'simplepie' => $simplepie);
	}

	public static function get_current_item_preview($item, $campaign) {
		$current_item = array();
		if (! class_exists('wpematico_campaign_fetch')) {
			require_once(WPEMATICO_PLUGIN_DIR.'includes/campaign_fetch.php');
		}
		$current_item['permalink'] = wpematico_campaign_fetch::getReadUrl($item->get_permalink(), $campaign);
		$current_item['title'] = $item->get_title();
		$current_item['content'] = $item->get_content();
		return $current_item;
	}

	/**
	* Static function print_preview
	* @access public
	* @return void
	* @since 1.9
	*/
	public static function print_preview() {
		// The nonce is tied to the campaign it was created for, and the person opening
		// the link has to be allowed to edit that campaign.
		$campaign_id = wpematico_verify_campaign_screen_request('campaign-preview-nonce');

		// And allowed to be on this screen at all. Same capability the campaign editor
		// checks, and the same reason the Feed Viewer grew one: this screen makes the
		// server fetch a URL.
		if (!current_user_can(self::capability())) {
			wp_die(esc_html__('You do not have sufficient permissions to preview this campaign.', 'wpematico'), '', array('response' => 403));
		}

		self::$cfg = get_option(WPeMatico::OPTION_KEY);

		$campaign = WPeMatico::get_campaign($campaign_id);

		if (defined('WP_DEBUG') and WP_DEBUG){
			// E_ALL already includes E_STRICT since PHP 5.4, and PHP 8.4 deprecated the
			// constant, so referencing it here only produced a deprecation notice.
			set_error_handler('wpematico_joberrorhandler',E_ALL);
		}else{
			set_error_handler('wpematico_joberrorhandler',E_ALL & ~E_NOTICE);
		}


		if (! class_exists('wpematico_campaign_fetch')) {
			require_once(WPEMATICO_PLUGIN_DIR.'includes/campaign_fetch.php');
		}

		// The constructor bails on id 0 without filling anything, so the object arrives
		// empty; give it the campaign it is previewing before anything reads it.
		$campaign_fetch = new wpematico_campaign_fetch(0);
		$campaign_fetch->cfg			 = self::$cfg;
		$campaign_fetch->campaign_id	 = $campaign_id;
		$campaign_fetch->campaign		 = $campaign;
		$campaign_fetch->images_options	 = WPeMatico::get_images_options(self::$cfg, $campaign);
		$campaign_fetch->audios_options	 = WPeMatico::get_audios_options(self::$cfg, $campaign);
		$campaign_fetch->videos_options	 = WPeMatico::get_videos_options(self::$cfg, $campaign);

		do_action('Wpematico_init_fetching', $campaign);

		$post_to_show = array();



		foreach($campaign['campaign_feeds']  as $kf => $feed) {
			$feed_data = self::get_feeds_items_statues($feed, $campaign, $campaign_fetch);
			$simplepie = $feed_data['simplepie'];
			foreach($simplepie->get_items() as $item) {
				$item_hash = self::get_item_hash($item);
				if (empty($feed_data['next'][$item_hash])) {
				  	continue;
				}
				$current_item = self::get_current_item_preview($item, $campaign);
				
				if ( $campaign_fetch->exclude_filters($current_item, $campaign, $feed, $item )) {
					continue; 
				}

				$current_item = apply_filters('wpematico_item_pre_media', $current_item, $campaign, $simplepie, $item);
				if (isset($current_item['SKIP']) && is_int($current_item['SKIP'])) {
					continue;
				}
				
				// ★ Carry the address the campaign holds, not $item->get_feed()->feed_url.
				// A feed an addon builds itself -- Professional reading Vimeo through the
				// API -- is handed to SimplePie as raw data, so it has no feed_url at all
				// and every item link went out with feed= empty: opening an item, and the
				// Fetch Now button of the Manual Fetching addon, both answered "The feed
				// is invalid.". It is also the URL the run uses to key its dedup hashes,
				// so anything acting on the item from here now writes the same meta the
				// run reads.
				$post_to_show[] = array('item' => $item, 'feed' => $feed);
				
				
			}
		}
		unset($campaign_fetch);
		
		$campaign_customposttype = 'post';
		$campaign_post_type_name = 'Posts';
		if (!empty($campaign['campaign_customposttype'])) {
			$campaign_customposttype = $campaign['campaign_customposttype'];
		}
		$obj_posttype = get_post_type_object($campaign_customposttype);
		if (!empty($obj_posttype)) {
			if (!empty($obj_posttype->labels->name)) {
				$campaign_post_type_name = $obj_posttype->labels->name;
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
		<title><?php _e('WPeMatico Preview Feed', 'wpematico'); ?></title>
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
					<h2><?php 
						/* translators: %s Title with Posttype name */
						echo get_the_title($campaign_id).': '.sprintf(__('Next Posts(%s)', 'wpematico'), $campaign_post_type_name); 
					?></h2>
				</div>
				
				<div class="table-responsive">
				  <table class="table-preview">
				  	<thead>
				  		<tr>
				  			<th id="cb" class="check-column">
				  				
				  			</th>
				  			<th><?php _e('Post', 'wpematico'); ?></th>
				  			<th><?php _e('Status', 'wpematico'); ?></th>
				  			<th><?php _e('Actions', 'wpematico'); ?></th>
				  		</tr>
				  	</thead>
				  	<tbody>
				  		<?php 
				  			$return_url = urlencode(admin_url('admin-post.php?action=wpematico_campaign_preview&p='.$campaign_id.'&_wpnonce=' . wp_create_nonce(wpematico_campaign_screen_nonce_action('campaign-preview-nonce', $campaign_id))));
				  			foreach($post_to_show as $to_show) : 
				  				$item = $to_show['item'];
				  				
				  				
				  				$description = $item->get_description(); 
				  				$description = WPeMatico::change_to_utf8($description);
				  				$description = strip_tags($description);
				  				if (strlen($description) > 303) {
				  					$description = mb_substr($description, 0, 300);
				  					$description .= '...'; 
				  				}

				  				$title = $item->get_title();
				  				$title = WPeMatico::change_to_utf8($title);
				  				$title = strip_tags($title);
				  				if (strlen($title) > 103) {
				  					$title = mb_substr($title, 0, 100);
				  					$title .= '...'; 
				  				}
				  				$feed_url =  urlencode($to_show['feed']);
				  				$item_date = $item->get_date();
				  				$item_hash = self::get_item_hash($item);
				  				$nonce_item = wp_create_nonce(wpematico_campaign_screen_nonce_action('campaign-preview-item-nonce', $campaign_id));
				  				$post_link_preview = admin_url('admin-post.php?action=wpematico_campaign_preview_item&_wpnonce='.$nonce_item.'&campaign='.$campaign_id.'&item_hash='.$item_hash.'&feed='.$feed_url.'&return_url='.$return_url);



				  			?>
						    <tr id="tr_item_<?php echo esc_attr($item_hash); ?>" class="feed-nextfetch">
						    	<td>
						    		
						    	</td>
						    	<td>
						    		<a href="<?php echo esc_url($post_link_preview); ?>" id="pfeed-id"><?php echo esc_html($title); ?></a>
						    		<span id="pfeed-date"><?php echo esc_html($item_date); ?></span>
						    		<p><?php echo esc_html($description); ?></p>

						    	</td>
						    	<td>
						    		<span id="status_item_<?php echo esc_attr($item_hash); ?>" class="status nextfetch"><?php echo  __('Next fetch', 'wpematico'); ?></span>
						    	</td>
						    	<td>
						    		<button type="button" data-itemhash="<?php echo esc_attr($item_hash); ?>" data-feed="<?php echo esc_attr($feed_url); ?>" class="item_fetch cpanelbutton dashicons dashicons-welcome-add-page" title="<?php esc_attr_e('Fetch Now', 'wpematico'); ?>"></button>
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