<?php
/**
 * WPeMatico plugin for WordPress
 * campaign_fetch
 * Contains all the methods to run manually or scheduled campaign.
 
 * @requires  campaign_fetch_functions
 * @package   wpematico
 * @link      https://bitbucket.org/etruel/wpematico
 * @author    Esteban Truelsegaard <etruel@etruel.com>
 * @copyright 2006-2018 Esteban Truelsegaard
 * @license   GPL v2 or later
 */

// don't load directly
if ( !defined('ABSPATH') ){
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( class_exists( 'wpematico_campaign_fetch' ) ) return;
include_once("campaign_fetch_functions.php");

class wpematico_campaign_fetch extends wpematico_campaign_fetch_functions {
	public $cfg			   = array();
	public $campaign_id	   = 0;  // $post_id of campaign
	public $campaign	   = array();
	private $feeds		   = array();
	private $fetched_posts = 0;
	private $lasthash	   = array();
	private $currenthash   = array();
	public $current_item   = array();
	
	public function __construct($campaign_id) {
		global $wpdb,$campaign_log_message, $jobwarnings, $joberrors;
		$jobwarnings=0;
		$joberrors=0;
		//set function for PHP user defined error handling
		if (defined('WP_DEBUG') and WP_DEBUG){
			set_error_handler('wpematico_joberrorhandler',E_ALL | E_STRICT);
		}else{
			set_error_handler('wpematico_joberrorhandler',E_ALL & ~E_NOTICE);
		}
		
		if (!version_compare(phpversion(), '5.3.0', '>')) { // PHP Version
			wpematico_init_set('safe_mode', 'Off');   // deprecated after 5.3
		}
		wpematico_init_set('ignore_user_abort', 'On');
		
		if (empty($campaign_id)) {
			return false; // If campaign is empty return false.
		}

		//ignore_user_abort(true);			//user can't abort script (close windows or so.)
		$this->campaign_id=$campaign_id;			   //set campaign id
		$this->campaign = WPeMatico :: get_campaign($this->campaign_id);
		
		//$this->fetched_posts = $this->campaign['postscount'];
		$this->cfg = get_option(WPeMatico :: OPTION_KEY);
		$campaign_timeout = (int)$this->cfg['campaign_timeout'];

		wpematico_init_set('max_execution_time', $campaign_timeout);
		
		// new actions 
		if( (int)$this->cfg['throttle'] > 0 ) add_action('wpematico_inserted_post', array( 'WPeMatico', 'throttling_inserted_post' ));		

		//Set job start settings
		$this->campaign['starttime']	 = current_time('timestamp'); //set start time for job
		$this->campaign['lastpostscount'] = 0; // Lo pone en 0 y lo asigna al final		
		WPeMatico :: update_campaign($this->campaign_id, $this->campaign); //Save start time data
		//
		$this->set_actions_and_filters();
		
		if(has_action('Wpematico_init_fetching')) do_action('Wpematico_init_fetching', $this->campaign);

		//check max script execution tme
		if (ini_get('safe_mode') or strtolower(ini_get('safe_mode'))=='on' or ini_get('safe_mode')=='1')
			trigger_error(sprintf(__('PHP Safe Mode is on!!! Max exec time is %1$d sec.', 'wpematico' ),ini_get('max_execution_time')),E_USER_WARNING);
		// check function for memorylimit
		if (!function_exists('memory_get_usage')) {
			ini_set('memory_limit', apply_filters( 'admin_memory_limit', '256M' )); //Wordpress default
			trigger_error(sprintf(__('Memory limit set to %1$s ,because can not use PHP: memory_get_usage() function to dynamically increase the Memory!', 'wpematico' ),ini_get('memory_limit')),E_USER_WARNING);
		}
		//run job parts
		$postcount = 0;
		$this->feeds = $this->campaign['campaign_feeds'] ; // --- Obtengo los feeds de la campaña
		
		foreach($this->feeds as $kf => $feed) {
			WPeMatico::$current_feed = $feed;
			// interrupt the script if timeout 
  			if (current_time('timestamp')-$this->campaign['starttime'] >= $campaign_timeout) {
				trigger_error(sprintf(__('Ending feed, reached running timeout at %1$d sec.', 'wpematico' ), $campaign_timeout ),E_USER_WARNING);
				break;
			}
			wpematico_init_set('max_execution_time', $campaign_timeout, true);
			$postcount += $this->processFeed($feed, $kf);         #- ---- Proceso todos los feeds      
		}

		$this->fetched_posts += $postcount; 

		$this->fetch_end(); // if everything ok call fetch_end  and end class
	}
	
	public function set_actions_and_filters() {
		//hook to add actions and filter on init fetching
		//add_action('Wpematico_init_fetching', array($this, 'wpematico_init_fetching') ); 
		add_filter('wpematico_custom_chrset', array( 'WPeMatico_functions' , 'detect_encoding_from_headers'), 999, 1);   // move all encoding functions to wpematico_campaign_fetch_functions

		if($this->campaign['campaign_type']=="youtube") 
			add_filter('wpematico_get_post_content_feed', array( 'wpematico_campaign_fetch_functions' , 'wpematico_get_yt_rss_tags'),999,4);
		
		$priority = 10;
		if( $this->cfg['add_extra_duplicate_filter_meta_source'] &&  !$this->cfg['disableccf']) {
			add_filter('wpematico_duplicates', array( 'wpematico_campaign_fetch_functions' , 'WPeisDuplicatedMetaSource'),$priority,3);
		}
		
	}
	/**
	* Processes every feed of a campaign
	* @param   $feed       URL string    Feed 
	* @return  The number of posts added
	*/
	private function processFeed($feed, $kf)  {
		global $realcount;
		@set_time_limit(0);
		trigger_error('<span class="coderr b"><b>'.sprintf(__('Processing feed %1s.', 'wpematico' ),$feed).'</b></span>' , E_USER_NOTICE);   // Log
		
		$items = array();
		$count = 0;
		$prime = true;

		// Access the feed
		if($this->campaign['campaign_type']=="feed" or $this->campaign['campaign_type']=="youtube" ) { 		// Access the feed
			$wpe_url_feed = apply_filters('wpematico_simplepie_url', $feed, $kf, $this->campaign);
			/**
			* @since 1.8.0
			* Added @fetch_feed_params to change parameters values before fetch the feed.
			*/
			$fetch_feed_params = array(
				'url' 			=> $wpe_url_feed,
				'stupidly_fast' => $this->cfg['set_stupidly_fast'],
				'max' 			=> $this->campaign['campaign_max'],
				'order_by_date' => $this->campaign['campaign_feed_order_date'],
				'force_feed' 	=> false,
			);
			$fetch_feed_params = apply_filters('wpematico_fetch_feed_params', $fetch_feed_params, $kf, $this->campaign);
			$simplepie =  WPeMatico::fetchFeed($fetch_feed_params);
		}else {
			$simplepie = apply_filters('Wpematico_process_fetching', $this->campaign);
		}
		
		do_action('Wpematico_process_fetching_'.$this->campaign['campaign_type'], $this);  // Wpematico_process_fetching_feed
		foreach($simplepie->get_items() as $item) {
			if($prime){
				//with first item get the hash of the last item (new) that will be saved.
				$this->lasthash[$feed] = md5($item->get_permalink()); 
				$prime=false;
			}

			$this->currenthash[$feed] = md5($item->get_permalink()); // el hash del item actual del feed feed 
			if( !$this->cfg['allowduplicates'] || !$this->cfg['allowduptitle'] || !$this->cfg['allowduphash']  || $this->cfg['add_extra_duplicate_filter_meta_source']){
				if( !$this->cfg['allowduphash'] ){
					// chequeo a la primer coincidencia sale del foreach
					$lasthashvar = '_lasthash_'.sanitize_file_name($feed);
					$hashvalue = get_post_meta( $this->campaign_id, $lasthashvar, true );
					if (!isset( $this->campaign[$feed]['lasthash'] ) ) $this->campaign[$feed]['lasthash'] = '';
					
					$dupi = ( $this->campaign[$feed]['lasthash'] == $this->currenthash[$feed] ) || 
								( $hashvalue == $this->currenthash[$feed] ); 
					if ($dupi) {
						trigger_error(sprintf(__('Found duplicated hash \'%1s\'', 'wpematico' ),$item->get_permalink()).': '.$this->currenthash[$feed] ,E_USER_NOTICE);
						if( !$this->cfg['jumpduplicates'] ) {
							trigger_error(__('Filtering duplicated posts.', 'wpematico' ),E_USER_NOTICE);
							break;
						}else {
							trigger_error(__('Jumping duplicated post. Continuing.', 'wpematico' ),E_USER_NOTICE);
							continue;
						}
					}
				}
				if( !$this->cfg['allowduptitle'] ){
					if(WPeMatico::is_duplicated_item($this->campaign, $feed, $item)) {
						trigger_error(sprintf(__('Found duplicated title \'%1s\'', 'wpematico' ),$item->get_title()).': '.$this->currenthash[$feed] ,E_USER_NOTICE);
						if( !$this->cfg['jumpduplicates'] ) {
							trigger_error(__('Filtering duplicated posts.', 'wpematico' ),E_USER_NOTICE);
							break;
						}else {
							trigger_error(__('Jumping duplicated post. Continuing.', 'wpematico' ),E_USER_NOTICE);
							continue;
						}
					}
				}

			}
			$count++;
			array_unshift($items, $item); // add at Post stack in correct order by date 		  
			if($count == $this->campaign['campaign_max']) {
				trigger_error(sprintf(__('Campaign fetch limit reached at %1s.', 'wpematico' ),$this->campaign['campaign_max']),E_USER_NOTICE);
				break;
			}
		}
		
		$campaign_timeout = (int)$this->cfg['campaign_timeout'];
		// Processes post stack
		$realcount = 0;
		foreach($items as $item) {	
			// interrupt the script if timeout 
  			if (current_time('timestamp')-$this->campaign['starttime'] >= $campaign_timeout) {
				trigger_error(sprintf(__('Reached running timeout at %1$d sec.', 'wpematico' ), $campaign_timeout ),E_USER_WARNING);
				break;
			}
			// set timeout for rest of the items to Timeout setting less current run time
			wpematico_init_set('max_execution_time', $campaign_timeout, true); // - ( current_time('timestamp') - $this->campaign['starttime'] ), true);
			$realcount++;
			$this->currenthash[$feed] = md5($item->get_permalink()); // the hash of the current item feed 
			$suma=$this->processItem($simplepie, $item, $feed);

			$lasthashvar = '_lasthash_'.sanitize_file_name($feed);
			$hashvalue = $this->currenthash[$feed];
			add_post_meta( $this->campaign_id, $lasthashvar, $hashvalue, true )  or
				update_post_meta( $this->campaign_id, $lasthashvar, $hashvalue );

			if (isset($suma) && is_int($suma)) {
				$realcount = $realcount + $suma;
				$suma="";
			}
		}
		
		if($realcount) {
			trigger_error(sprintf(__('%s posts added', 'wpematico' ),$realcount),E_USER_NOTICE);
		}
		
		return $realcount;
	}
	
   /**
   * Processes an item: parses and filters
   * @param   $feed       object    Feed database object
   * @param   $item       object    SimplePie_Item object
   * @return true si lo procesó
   */
	function processItem($feed, $item, $feedurl) {
		global $wpdb, $realcount;
		trigger_error(sprintf('<b>' . __('Processing item %1s', 'wpematico' ),$item->get_title().'</b>' ),E_USER_NOTICE);
		$this->current_item = array();
		
		// Get the source Permalink trying to redirect if is set.
		$this->current_item['permalink'] = $this->getReadUrl($item->get_permalink(), $this->campaign);
		// First exclude filters
		if ( $this->exclude_filters($this->current_item,$this->campaign,$feed,$item )) {
			return -1 ;  // resta este item del total 
		}
		// Item date
		$itemdate = $item->get_date('U');
		$this->current_item['date'] = null;
		if($this->campaign['campaign_feeddate']) {
			if (($itemdate > $this->campaign['lastrun']) && $itemdate < current_time('timestamp', 1)) {  
				$this->current_item['date'] = $itemdate;
				trigger_error(__('Assigning original date to post.', 'wpematico' ),E_USER_NOTICE);
			}else{
				trigger_error(__('Original date out of range.  Assigning current date to post.', 'wpematico' ) ,E_USER_NOTICE);
			}
		}
		
		// Item title
		$this->current_item['title'] = $item->get_title();
		$this->current_item['title'] = htmlspecialchars_decode($this->current_item['title']);
		if($this->campaign['campaign_enable_convert_utf8']) {
			$this->current_item['title'] =  WPeMatico::change_to_utf8($this->current_item['title']);
		}

		
		if( $this->cfg['nonstatic'] ) { $this->current_item = NoNStatic :: title($this->current_item,$this->campaign,$item,$realcount ); }else $this->current_item['title'] = esc_attr($this->current_item['title']);

		$this->current_item['title'] = html_entity_decode($this->current_item['title'], ENT_QUOTES | ENT_HTML401, 'UTF-8');

 		// Item author
		//if( $this->cfg['nonstatic'] ) { $this->current_item = NoNStatic :: author($this->current_item,$this->campaign, $feedurl, $item ); }else $this->current_item['author'] = $this->campaign['campaign_author'];
		$this->current_item['author'] = $this->campaign['campaign_author'];
		$this->current_item =  apply_filters('wpematico_get_author', $this->current_item, $this->campaign, $feedurl, $item ); 
		
		// Item content
		$this->current_item['content'] = apply_filters('wpematico_get_post_content_feed', $item->get_content(), $this->campaign, $feed, $item );
		$this->current_item = apply_filters('wpematico_get_post_content', $this->current_item, $this->campaign, $feed, $item );

		if($this->campaign['campaign_enable_convert_utf8']) {
			$this->current_item['content'] =  WPeMatico::change_to_utf8($this->current_item['content']);
		}

		$this->current_item['content'] = html_entity_decode($this->current_item['content'], ENT_COMPAT | ENT_HTML401, 'UTF-8');
		
		/**
		* @since 1.7.0
		* Parse and upload audio
		*/
		$options_audios = WPeMatico::get_audios_options($this->cfg, $this->campaign);
		$this->current_item = apply_filters('wpematico_item_filters_pre_audio', $this->current_item, $this->campaign);
		$this->current_item = $this->Get_Item_Audios($this->current_item, $this->campaign, $feed, $item, $options_audios);
		// Uploads and changes img sources in content
		$this->current_item = $this->Item_Audios($this->current_item, $this->campaign, $feed, $item, $options_audios);

		/**
		* @since 1.7.0
		* Parse and upload video
		*/
		$options_videos = WPeMatico::get_videos_options($this->cfg, $this->campaign);
		$this->current_item = apply_filters('wpematico_item_filters_pre_video', $this->current_item, $this->campaign);
		//gets video array 
		$this->current_item = $this->Get_Item_Videos($this->current_item, $this->campaign, $feed, $item, $options_videos);
		// Uploads and changes img sources in content
		$this->current_item = $this->Item_Videos($this->current_item, $this->campaign, $feed, $item, $options_videos);


		//********* Parse and upload images
		/**
		* @since 1.7.0 
		* Get image options.
		*/
		$options_images = WPeMatico::get_images_options($this->cfg, $this->campaign);
		$this->current_item = apply_filters('wpematico_item_filters_pre_img', $this->current_item, $this->campaign );
		//gets images array 
		$this->current_item = $this->Get_Item_images($this->current_item,$this->campaign, $feed, $item, $options_images);
		$this->current_item['featured_image'] = apply_filters('wpematico_set_featured_img', '', $this->current_item, $this->campaign, $feed, $item );

		if($options_images['featuredimg']){
			if(!empty($this->current_item['images'])){
				$this->current_item['featured_image'] = apply_filters('wpematico_get_featured_img', $this->current_item['images'][0], $this->current_item);
			}
		}

		
		if($options_images['rmfeaturedimg'] && !empty($this->current_item['featured_image']) ){ // removes featured from content
			$this->current_item['content'] = $this->strip_Image_by_src($this->current_item['featured_image'], $this->current_item['content']);
		}
		
		if( $this->cfg['nonstatic'] ) { $this->current_item['images'] = NoNStatic :: img1s($this->current_item,$this->campaign,$item ); }

		// Uploads and changes img sources in content
		$this->current_item = $this->Item_images($this->current_item, $this->campaign, $feed, $item, $options_images);
		$this->current_item = $this->featured_image_selector($this->current_item,$this->campaign, $feed, $item, $options_images);
		
		$this->current_item = apply_filters('wpematico_item_filters_pos_img', $this->current_item, $this->campaign );
		
		//********** Do parses contents and titles
		$this->current_item = $this->Item_parsers($this->current_item,$this->campaign,$feed,$item,$realcount, $feedurl );
		if($this->current_item == -1 ) return -1;

		// Primero proceso las categorias si las hay y las nuevas las agrego al final del array
		$this->current_item['categories'] = (array)$this->campaign['campaign_categories']; 
		if ($this->campaign['campaign_autocats']) {
			if ($autocats = $item->get_categories()) {
				trigger_error(__('Assigning Auto Categories.', 'wpematico' ) ,E_USER_NOTICE);
				foreach($autocats as $id => $catego) {
					$catname = $catego->term;
					if(!empty($catname)) {
						//$this->current_item['categories'][] = wp_create_category($catname);  //Si ya existe devuelve el ID existente  // wp_insert_category(array('cat_name' => $catname));  //
						$term = term_exists($catname, 'category');
						if ($term !== 0 && $term !== null) {  // ya existe
							trigger_error(__('Category exist: ', 'wpematico' ) . $catname ,E_USER_NOTICE);
						}else{	//si no existe la creo
							trigger_error(__('Adding Category: ', 'wpematico' ) . $catname ,E_USER_NOTICE);
							$parent_cat = "0";
							if (isset($this->campaign['campaign_parent_autocats']) && $this->campaign['campaign_parent_autocats'] > 0) {
								$parent_cat = $this->campaign['campaign_parent_autocats'];
							}
							$arg = array('description' => apply_filters('wpematico_addcat_description', __("Auto Added by WPeMatico", 'wpematico' ), $catname), 'parent' => $parent_cat);
							$term = wp_insert_term($catname, "category", $arg);
						}
						$this->current_item['categories'][] = $term['term_id'];
					}					
				}
			}
		}

		$this->current_item['posttype'] = $this->campaign['campaign_posttype'];
		$this->current_item['allowpings'] = $this->campaign['campaign_allowpings'];
		$this->current_item['commentstatus'] = $this->campaign['campaign_commentstatus'];
		$this->current_item['customposttype'] = $this->campaign['campaign_customposttype'];
		$this->current_item['campaign_post_format'] = $this->campaign['campaign_post_format'];

		//********** Do filters
		$this->current_item = $this->Item_filters($this->current_item,$this->campaign,$feed,$item );

		if( $this->cfg['nonstatic'] ) { $this->current_item = NoNStatic :: metaf($this->current_item, $this->campaign, $feed, $item ); }
		
		if( $this->cfg['nonstatic'] && !empty($this->current_item['tags']) ) $this->current_item['campaign_tags']=$this->current_item['tags'];
		
		// Meta
		if( isset($this->cfg['disableccf']) && $this->cfg['disableccf'] ) {
			 $this->current_item['meta'] = array();
		}else{
		   $arraycf = array(
			   'wpe_campaignid' => $this->campaign_id, 
			   'wpe_feed' => $feed->feed_url,
			   'wpe_sourcepermalink' => $this->current_item['permalink'],
		   ); 
		   $this->current_item['meta'] = (isset($this->current_item['meta']) && !empty($this->current_item['meta']) ) ? array_merge($this->current_item['meta'], $arraycf) :  $arraycf ;
		   $this->current_item['meta'] = apply_filters('wpem_meta_data', $this->current_item['meta'] );
		}
		
		// Create post
		$title = $this->current_item['title'];
		$content= $this->current_item['content'];
		$timestamp = $this->current_item['date'];
		$category = $this->current_item['categories'];
		$status = $this->current_item['posttype'];
		$authorid = $this->current_item['author'];
		$allowpings = $this->current_item['allowpings'];
		$comment_status = (isset($this->current_item['commentstatus']) && !empty($this->current_item['commentstatus']) ) ? $this->current_item['commentstatus'] : 'open';
		$meta = $this->current_item['meta'];
		$post_type = (isset($this->current_item['customposttype']) && !empty($this->current_item['customposttype']) ) ? $this->current_item['customposttype'] : 'post';
		$images = $this->current_item['images'];
		$campaign_tags = $this->current_item['campaign_tags'];
		$post_format = $this->current_item['campaign_post_format'];
		
		$date = ($timestamp) ? gmdate('Y-m-d H:i:s', $timestamp + (get_option('gmt_offset') * 3600)) : null;
		
		if($this->cfg['woutfilter'] && $this->campaign['campaign_woutfilter'] ) {
			$truecontent = $content;
			$content = '';
		}
		if ($this->campaign['copy_permanlink_source']) {
			$slug = WPeMatico::get_slug_from_permalink($item->get_permalink());
		} else {
			$slug = sanitize_title($title);
		}
		$post_parent = 0;
		if(isset($this->campaign['campaign_parent_page']) && $this->campaign['campaign_parent_page']) {
			$post_parent = $this->campaign['campaign_parent_page'];
		}
		$args = array(
			'post_title' 	          => apply_filters('wpem_parse_title', $title),
			'post_content'  	      => apply_filters('wpem_parse_content', $content),
			'post_name'  	      	  => apply_filters('wpem_parse_name', $slug),
			'post_content_filtered'   => apply_filters('wpem_parse_content_filtered', $content),
			'post_status' 	          => apply_filters('wpem_parse_status', $status),
			'post_type' 	          => apply_filters('wpem_parse_post_type', $post_type),
			'post_author'             => apply_filters('wpem_parse_authorid', $authorid),
			'post_date'               => apply_filters('wpem_parse_date', $date),
			'comment_status'          => apply_filters('wpem_parse_comment_status', $comment_status),
			'post_parent'			  => apply_filters('wpem_parse_parent', $post_parent),
			'ping_status'             => ($allowpings) ? "open" : "closed"
		);
		if(has_filter('wpematico_pre_insert_post')) $args =  apply_filters('wpematico_pre_insert_post', $args, $this->campaign);

		if( apply_filters('wpematico_allow_insertpost', true, $this, $args ) ) {
			remove_filter('content_save_pre', 'wp_filter_post_kses');
//			remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
			$post_id = wp_insert_post( $args );
			add_filter('content_save_pre', 'wp_filter_post_kses');
//			add_filter('content_filtered_save_pre', 'wp_filter_post_kses');

			if(!empty($category)){ //solo muestra los tags si los tiene definidos
				$aaa = wp_set_post_terms( $post_id, $category, 'category');
				if(!empty($aaa)) trigger_error(__("Categories added: ", 'wpematico' ).implode(", ",$aaa) ,E_USER_NOTICE);
			}
			if(!empty($campaign_tags)){ //solo muestra los tags si los tiene definidos
				$aaa = wp_set_post_terms( $post_id, $campaign_tags);
				if(!empty($aaa)) trigger_error(__("Tags added: ", 'wpematico' ).implode(", ",$campaign_tags),E_USER_NOTICE);
			}else if(has_action('wpematico_chinese_tags')) do_action('wpematico_chinese_tags', $post_id, $content, $this->campaign );

			if(!empty($post_format)){ //inserto post format
				//$aaa = wp_set_post_terms( $post_id, $category, 'post_format');
				$aaa = set_post_format( $post_id , $post_format); 
				if(!empty($aaa)) trigger_error(__("Post format added: ", 'wpematico' ).$post_format,E_USER_NOTICE);
			}

			if($this->cfg['woutfilter'] && $this->campaign['campaign_woutfilter'] ) {
				global $wpdb, $wp_locale, $current_blog;
				$table_name = $wpdb->prefix . "posts";  
				$blog_id 	= @$current_blog->blog_id;
				$content = $truecontent;
				trigger_error(__('** Adding unfiltered content **', 'wpematico' ),E_USER_NOTICE);
				$wpdb->update( $table_name, array( 'post_content' => $content, 'post_content_filtered' => $content ), array( 'ID' => $post_id )	);
			}
			// insert PostMeta
			foreach($meta as $key => $value){
				add_post_meta($post_id, $key, $value, true);
			}

			if(has_action('wpematico_inserted_post')) do_action('wpematico_inserted_post', $post_id, $this->campaign, $item );


			// Attaching images uploaded to created post in media library 
			// Featured Image
			$featured_image_attach_id = 0;
			$img_new_url = '';
			if(!empty($this->current_item['nofeatimg'])) {
				trigger_error('<strong>'.__('Skip Featured Image.', 'wpematico' ).'</strong>',E_USER_NOTICE);
			}else if( !empty($this->current_item['featured_image']) ) {
				trigger_error(__('Featuring Image Into Post.', 'wpematico' ),E_USER_NOTICE);
				if($this->current_item['images'][0] != $this->current_item['featured_image']){
					$itemUrl = $this->current_item['permalink'];
					$imagen_src = $this->current_item['featured_image'];
//**** necesaria para la featured ?					$imagen_src = apply_filters('wpematico_imagen_src', $imagen_src ); // allow strip parts 
					$imagen_src_real = $this->getRelativeUrl($itemUrl, $imagen_src);
					// Strip all white space on images URLs.	
					$imagen_src_real = str_replace(' ', '%20', $imagen_src_real);					
					$imagen_src_real = apply_filters('wpematico_img_src_url', $imagen_src_real );
					$allowed = (isset($this->cfg['allowed']) && !empty($this->cfg['allowed']) ) ? $this->cfg['allowed'] : 'jpg,gif,png,tif,bmp,jpeg' ;
					$allowed = apply_filters('wpematico_allowext', $allowed );
					//Fetch and Store the Image	
					///////////////***************************************************************************************////////////////////////
					$newimgname = apply_filters('wpematico_newimgname', sanitize_file_name(urlencode(basename($imagen_src_real))), $this->current_item, $this->campaign, $item );  // new name here
					// Primero intento con mi funcion mas rapida
					$upload_dir = wp_upload_dir();
					$imagen_dst = trailingslashit($upload_dir['path']). $newimgname; 
					$imagen_dst_url = trailingslashit($upload_dir['url']). $newimgname;
					$img_new_url = "";
					if(in_array(str_replace('.','',strrchr( strtolower($imagen_dst), '.')), explode(',', $allowed))) {   // -------- Controlo extensiones permitidas
						trigger_error('Uploading media='.$imagen_src.' <b>to</b> imagen_dst='.$imagen_dst.'',E_USER_NOTICE);
						$newfile = ($options_images['customupload']) ? WPeMatico::save_file_from_url($imagen_src_real, $imagen_dst) : false;
						if($newfile) { //subió
							trigger_error('Uploaded media='.$newfile,E_USER_NOTICE);
							$imagen_dst = $newfile; 
							$imagen_dst_url = trailingslashit($upload_dir['url']). basename($newfile);
							$img_new_url = $imagen_dst_url;
						} else { // falló -> intento con otros
							$bits = WPeMatico::wpematico_get_contents($imagen_src_real);
							$mirror = wp_upload_bits( $newimgname, NULL, $bits);
							if(!$mirror['error']) {
								trigger_error($mirror['url'],E_USER_NOTICE);
								$img_new_url = $mirror['url'];
							}
						}
					}
				}else{
					$img_new_url=$this->current_item['featured_image'];
				}
				if(!empty($img_new_url)) { 
					$this->current_item['featured_image'] = $img_new_url;
					array_shift($this->current_item['images']);  //deletes featured image from array to avoid double upload below
					$attachid = false;
					if( !$options_images['imgattach']) {
						//get previously uploaded attach IDs, false if not exist.  (Just attach once/first time)
					//	$attachid = $this->get_attach_id_from_url($this->current_item['featured_image']); 
						$attachid = attachment_url_to_postid($this->current_item['featured_image']); 
					}
					if ($attachid == false) {
						$attachid = $this->insertfileasattach( $this->current_item['featured_image'] , $post_id);
					}
					set_post_thumbnail($post_id, $attachid );					
					$featured_image_attach_id = $attachid;
					//add_post_meta($post_id, '_thumbnail_id', $attachid);
				}else{
					//trigger_error( __('Upload featured image failed:', 'wpematico' ).$imagen_dst,E_USER_WARNING);
				}
			}
			$featured_image_attach_id = apply_filters('wpematico_featured_image_attach_id', $featured_image_attach_id, $post_id, $this->current_item, $this->campaign, $item);
			if ($featured_image_attach_id == 0) {
				trigger_error( __('The post has no a featured image.', 'wpematico' ), E_USER_WARNING);
			}
			// Attach files in post content previously uploaded
			//if(!$this->campaign['campaign_cancel_imgcache']) {
				if($options_images['imgcache'] && $options_images['imgattach']) {
					if(is_array($this->current_item['images'])) {
						if(sizeof($this->current_item['images'])) { // Si hay alguna imagen 
							trigger_error(__('Attaching images', 'wpematico' ).": ".sizeof($this->current_item['images']),E_USER_NOTICE);
							foreach($this->current_item['images'] as $imagen_src) {
								$attachid = $this->insertfileasattach($imagen_src,$post_id);
							}
						}
					}
				}			
			//}

			/**
			* Attach audios to post
			* @since 1.7.0
			*/	
			if($options_audios['audio_cache'] && $options_audios['audio_attach']) {
				if(is_array($this->current_item['audios'])) {
					if(sizeof($this->current_item['audios'])) { // if exist a audio.
						trigger_error(__('Attaching audios', 'wpematico' ).": ".sizeof($this->current_item['audios']),E_USER_NOTICE);
						foreach($this->current_item['audios'] as $audio_src) {
							$attachid = $this->insertfileasattach($audio_src,$post_id);
						}
					}
				}
			}
			/**
			* Attach videos to post
			* @since 1.7.0
			*/	
			if($options_videos['video_cache'] && $options_videos['video_attach']) {
				if(is_array($this->current_item['videos'])) {
					if(sizeof($this->current_item['videos'])) { // if exist a video.
						trigger_error(__('Attaching videos', 'wpematico' ).": ".sizeof($this->current_item['videos']),E_USER_NOTICE);
						foreach($this->current_item['videos'] as $video_src) {
							$attachid = $this->insertfileasattach($video_src,$post_id);
						}
					}
				}
			}	

			 // If pingback/trackbacks
			if($this->campaign['campaign_allowpings']) {
				trigger_error(__('Processing item pingbacks', 'wpematico' ),E_USER_NOTICE);
				require_once(ABSPATH . WPINC . '/comment.php');
				pingback($this->current_item['content'], $post_id);      
			}
		// wpematico_allow_insertpost
		} else {
			return -1; // resta este item del total 
		}
		
	}
  	

	
	private function fetch_end() {
		$this->campaign['lastrun'] 		  = $this->campaign['starttime'];
		$this->campaign['lastruntime'] 	  = current_time('timestamp') - $this->campaign['starttime'];
		$this->campaign['starttime'] 	  = '';
		$this->campaign['postscount'] 	 += $this->fetched_posts; // Suma los posts procesados 
		$this->campaign['lastpostscount'] = $this->fetched_posts; //  posts procesados esta vez

/*		foreach($this->campaign['campaign_feeds'] as $feed) {    // Grabo el ultimo hash de cada feed
			@$this->campaign[$feed]['lasthash'] = $this->lasthash[$feed]; // paraa chequear duplicados por el hash del permalink original
		}
*/		
		$this->campaign = apply_filters('Wpematico_end_fetching', $this->campaign, $this->fetched_posts );
		//if($this->cfg['nonstatic']){$this->campaign=NoNStatic::ending($this->campaign,$this->fetched_posts);}

		WPeMatico :: update_campaign($this->campaign_id, $this->campaign);  //Save Campaign new data

		trigger_error(sprintf(__('Campaign fetched in %1s sec.', 'wpematico' ),$this->campaign['lastruntime']),E_USER_NOTICE);
	}

	public function __destruct() {
		global $campaign_log_message, $joberrors;
		//Send mail with log
		$sendmail=false;
		if ($joberrors>0 and $this->campaign['mailerroronly'] and !empty($this->campaign['mailaddresslog']))
			$sendmail=true;
		if (!$this->campaign['mailerroronly'] and !empty($this->campaign['mailaddresslog']))
			$sendmail=true;
		if ($sendmail) {	
			switch($this->cfg['mailmethod']) {
			case 'SMTP':
				do_action( 'wpematico_smtp_email');
				break;
			default:
				$headers[] = 'From: '.$this->cfg['mailsndname'].' <'.$this->cfg['mailsndemail'].'>';
				//$headers[] = 'Cc: John Q Codex <jqc@wordpress.org>';
				//$headers[] = 'Cc: iluvwp@wordpress.org'; // note you can just use a simple email address
				break;
			}
			$headers[] = 'Content-Type: text/html; charset=UTF-8';
			//add_filter('wp_mail_content_type','wpe_change_content_type'); //function wpe_change_content_type(){ return 'text/html'; } 
			
			$to_mail = $this->campaign['mailaddresslog'];
					
			$title = get_the_title($this->campaign_id);
			$subject = __('WPeMatico Log ', 'wpematico' ).' '.current_time('Y-m-d H:i').': '.$title;
			
			$mailbody = "WPeMatico Log"."\n";
			$mailbody .= __("Campaign Name:", 'wpematico' )." ".$title."\n";
			if (!empty($joberrors))
				$mailbody.=__("Errors:", 'wpematico' )." ".$joberrors."\n";
			if (!empty($jobwarnings))
				$mailbody.=__("Warnings:", 'wpematico' )." ".$jobwarnings."\n";

			$mailbody.="\n".$campaign_log_message;
			$mailbody.= "\n\n\n<hr>";
			$mailbody.= __("WPeMatico by <a href='https://etruel.com'>etruel</a>", 'wpematico' ). "\n";;
			
			wp_mail($to_mail, $subject, $mailbody,$headers,'');
			
		}
		
		// Save last log as meta field in campaign, replace if exist
		add_post_meta( $this->campaign_id, 'last_campaign_log', $campaign_log_message, true )  or
          update_post_meta( $this->campaign_id, 'last_campaign_log', $campaign_log_message );
		  
		$Suss = sprintf(__('Campaign fetched in %1s sec.', 'wpematico' ),$this->campaign['lastruntime']) . '  ' . sprintf(__('Processed Posts: %1s', 'wpematico' ), $this->fetched_posts);
		$message = '<p>'. $Suss.'  <a href="JavaScript:void(0);" style="font-weight: bold; text-decoration:none; display:inline;" onclick="jQuery(\'#log_message_'.$this->campaign_id.'\').fadeToggle();">' . __('Show detailed Log', 'wpematico' ) . '.</a></p>';
		$campaign_log_message = $message .'<div id="log_message_'.$this->campaign_id.'" style="display:none;" class="error fade">'.$campaign_log_message.'</div><span id="ret_lastruntime" style="display:none;">'.$this->campaign["lastruntime"].'</span><span id="ret_lastposts" style="display:none;">'.$this->fetched_posts.'</span>';

		return;
	}
}

//function wpe_change_content_type(){ return 'text/html'; }
function wpematico_init_set($index, $value, $error_only_fail = false) {
	//$oldvalue = ini_get($index);
	$oldvalue = @ini_set($index, $value); //@return string the old value on success, <b>FALSE</b> on failure. 
	if ($error_only_fail) {
		if ($oldvalue === false) {
			trigger_error(sprintf(__('Trying to set %1$s = %2$s: <strong>%3$s</strong> - Old value:%4$s.', 'wpematico' ), $index, $value, (($oldvalue === FALSE) ? __('Failed', 'wpematico' ):__('Success', 'wpematico' )), $oldvalue),(($oldvalue === FALSE)?E_USER_WARNING:E_USER_NOTICE));
		}
	} else {
		trigger_error(sprintf(__('Trying to set %1$s = %2$s: <strong>%3$s</strong> - Old value:%4$s.', 'wpematico' ), $index, $value, (($oldvalue === FALSE)?__('Failed', 'wpematico' ):__('Success', 'wpematico' )), $oldvalue),(($oldvalue === FALSE)?E_USER_WARNING:E_USER_NOTICE));
	}
	
	return $oldvalue;
}

