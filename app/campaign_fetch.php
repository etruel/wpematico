<?php

/**
 * WPeMatico plugin for WordPress
 * campaign_fetch
 * Contains all the methods to run manually or scheduled campaign.

 * @requires  campaign_fetch_functions
 * @package   wpematico
 * @link      https://github.com/etruel/wpematico
 * @author    Esteban Truelsegaard <etruel@etruel.com>
 * @copyright 2006-2018 Esteban Truelsegaard
 * @license   GPL v2 or later
 */
// don't load directly
if (!defined('ABSPATH')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

if (!class_exists('wpematico_campaign_fetch'))
include_once("campaign_fetch_functions.php");

class wpematico_campaign_fetch extends wpematico_campaign_fetch_functions {

    public $cfg = array();
    public $campaign_id = 0;  // $post_id of campaign
    public $campaign = array();
    public $images_options = array();
    public $audios_options = array();
    public $videos_options = array();
    private $feeds = array();
    private $fetched_posts = 0;
    private $lasthash = array();
    private $currenthash = array();
    public $current_item = array();

    public function __construct($campaign_id) {
        global $wpdb, $campaign_log_message, $jobwarnings, $joberrors;
        $jobwarnings = 0;
        $joberrors = 0;

		if (empty($campaign_id)) {
            return false; // If campaign is empty return false.
        }
		
        //set function for PHP user defined error handling
        if (defined('WP_DEBUG') and WP_DEBUG) {
            set_error_handler('wpematico_joberrorhandler', E_ALL | E_STRICT);
        } else {
            set_error_handler('wpematico_joberrorhandler', E_ALL & ~E_NOTICE);
        }

        $this->campaign_id = $campaign_id;   //set campaign id
        $this->campaign = WPeMatico :: get_campaign($this->campaign_id);

        $this->cfg = get_option(WPeMatico :: OPTION_KEY);
        $this->cfg = apply_filters('wpematico_check_options', $this->cfg);

        $this->images_options = WPeMatico::get_images_options($this->cfg, $this->campaign);
        $this->audios_options = WPeMatico::get_audios_options($this->cfg, $this->campaign);
        $this->videos_options = WPeMatico::get_videos_options($this->cfg, $this->campaign);

        $campaign_timeout = (int) $this->cfg['campaign_timeout'];

        wpematico_init_set('ignore_user_abort', 'On');
        wpematico_init_set('max_execution_time', $campaign_timeout);
		
//        trigger_error(sprintf(__('Max exec time is %1$d sec.', 'wpematico'), ini_get('max_execution_time')), E_USER_WARNING);

		// Adds a delay after each inserted post
		if ((int) $this->cfg['throttle'] > 0)
            add_action('wpematico_inserted_post', array('WPeMatico', 'throttling_inserted_post'));

        //Set job start settings
        $this->campaign['starttime'] = current_time('timestamp'); //set start time for job
        $this->campaign['lastpostscount'] = 0; // Set it to zero now and assign value at end fetch.
		
        //optimize test v2.7
        // WPeMatico :: update_campaign($this->campaign_id, $this->campaign); 
        
        //Save start time data
        update_post_meta($this->campaign_id, 'lastrun', $this->campaign['lastrun']); 
		// Current actions and filters to execute on this fetch  
		$this->set_actions_and_filters();

		/** 
		 * Wpematico_init_fetching action
		 * Mostly used to add more filters to be executed later on fetching process. 
		 */
        if (has_action('Wpematico_init_fetching'))
            do_action('Wpematico_init_fetching', $this->campaign);

        // check function for memorylimit
        if (!function_exists('memory_get_usage')) {
            wpematico_init_set('memory_limit', apply_filters('admin_memory_limit', '256M')); //Wordpress default
            trigger_error(sprintf(__('Memory limit set to %1$s ,because can not use PHP: memory_get_usage() function to dynamically increase the Memory!', 'wpematico'), ini_get('memory_limit')), E_USER_WARNING);
        }
        //run job parts
        $postcount = 0;
        $this->feeds = $this->campaign['campaign_feeds']; // --- Obtengo los feeds de la campaña

        foreach ($this->feeds as $kf => $feed) {
            WPeMatico::$current_feed = $feed;
            // interrupt the script if timeout 
            if (current_time('timestamp') - $this->campaign['starttime'] >= $campaign_timeout) {
                trigger_error(sprintf(__('Ending feed, reached running timeout at %1$d sec.', 'wpematico'), $campaign_timeout), E_USER_WARNING);
                break;
            }
			// Reset the timer setting again the max_execution_time
            wpematico_init_set('max_execution_time', $campaign_timeout, true);
            $postcount += $this->processFeed($feed, $kf);   #- ---- Run all feeds      
        }

        $this->fetched_posts += $postcount;

        $this->fetch_end(); // if everything was ok, call fetch_end and end class
    }

	/**
	 * Current actions and filters to execute on each fetch
	 */
    public function set_actions_and_filters() {
        //hook to add actions and filter on init fetching 
        //add_action('Wpematico_init_fetching', array(__CLASS__, 'my_wpematico_init_fetching') ); 
        add_filter('wpematico_custom_chrset', array('WPeMatico_functions', 'detect_encoding_from_headers'), 999, 1); // move all encoding functions to wpematico_campaign_fetch_functions
        add_filter('wpematico_after_item_parsers', array('wpematico_campaign_fetch_functions', 'wpematico_strip_links_a'), 1, 4);
        add_filter('wpematico_after_item_parsers', array('wpematico_campaign_fetch_functions', 'wpematico_strip_links'), 2, 4);
        add_filter('wpematico_after_item_parsers', array('wpematico_campaign_fetch_functions', 'wpematico_template_parse'), 3, 4);
        add_filter('wpematico_after_item_parsers', array('wpematico_campaign_fetch_functions', 'wpematico_campaign_rewrites'), 4, 4);

        if ($this->campaign['campaign_type'] == "youtube") {
            add_filter('wpematico_get_post_content_feed', array('wpematico_campaign_fetch_functions', 'wpematico_get_yt_rss_tags'), 999, 4);
            add_filter('wpematico_get_item_images', array('wpematico_campaign_fetch_functions', 'wpematico_get_yt_image'), 999, 4);
            add_filter('wpematico_excludes', array('wpematico_campaign_fetch_functions', 'wpematico_exclude_shorts'), 10, 4);
        }
        if ($this->cfg['add_extra_duplicate_filter_meta_source'] && !$this->cfg['disableccf']) {
            add_filter('wpematico_duplicates', array($this, 'WPeisDuplicatedMetaSource'), 10, 3);
        }
        if (isset($this->images_options['fifu']) && $this->images_options['fifu']) {
            add_filter('wpematico_set_featured_img', array('wpematico_campaign_fetch_functions', 'url_meta_set_featured_image'), 999, 2);
            add_filter('wpematico_get_featured_img', array('wpematico_campaign_fetch_functions', 'url_meta_set_featured_image'), 999, 2);
            add_filter('wpematico_item_filters_pos_img', array('wpematico_campaign_fetch_functions', 'url_meta_set_featured_image_setmeta'), 999, 2);
        }
    }
        /**
         * Processes every feed of a campaign
         * @param   string $feed       URL string    Feed 
         * @return  int    $realcount number the posts added
         */
    private function processFeed($feed, $kf){
        global $realcount;

        //        @set_time_limit(0);
        //		  $campaign_timeout = (int) $this->cfg['campaign_timeout'];
        //        wpematico_init_set('max_execution_time', $campaign_timeout);

        trigger_error('<span class="coderr b"><b>' . sprintf(__('Processing feed %s.', 'wpematico'), esc_html($feed)) . '</b></span>', E_USER_NOTICE);   // Log

        $items = array();
        $count = 0;
        $prime = true;

        // Access the feed 
        if ($this->campaign['campaign_type'] == "feed" or $this->campaign['campaign_type'] == "youtube" or $this->campaign['campaign_type'] == "bbpress") {
            $wpe_url_feed = apply_filters('wpematico_simplepie_url', $feed, $kf, $this->campaign);
            /**
             * @since 1.8.0
             * Added @fetch_feed_params to change parameters values before fetch the feed.
             */
            $fetch_feed_params = array(
                'url' => $wpe_url_feed,
                'stupidly_fast' => $this->cfg['set_stupidly_fast'],
                'max' => $this->campaign['campaign_max'],
                'order_by_date' => $this->campaign['campaign_feed_order_date'],
                'force_feed' => false,
            );
            $fetch_feed_params = apply_filters('wpematico_fetch_feed_params', $fetch_feed_params, $kf, $this->campaign);
            $simplepie = WPeMatico::fetchFeed($fetch_feed_params);
        } else {
            /**
			 * DEPRECATED on 2.7 in favor of wpematico_custom_simplepie below. Will be removed on 2.8
			 */
            $simplepie = apply_filters('Wpematico_process_fetching', $this->campaign, $feed, $kf, $this->campaign);
            /**

			 * wpematico_custom_simplepie 
			 * Filter to make the custom simplepie objects for extra contents that does not have a feed.
			 * @since 2.7
			 * @param $simplepie as null or empty because is not used until now. Will be defined in the filter methods.
			 * @param object $this = wpematico_campaign_fetch
			 * @param string $feed
			 * @param number $kf
			 * @return SimplePie Object created
			 */

            if(empty($simplepie)){
                $simplepie = new SimplePie;
            }
            
            $simplepie = apply_filters('wpematico_custom_simplepie', $simplepie, $this, $feed, $kf);
        }

        $duplicate_options = WPeMatico::get_duplicate_options($this->cfg, $this->campaign);

        do_action('Wpematico_process_fetching_' . $this->campaign['campaign_type'], $this);  // Wpematico_process_fetching_feed

        if (!$duplicate_options['allowduphash'] && $duplicate_options['jumpduplicates']) {
            $last_hashes_name = '_lasthashes_' . sanitize_file_name($feed);
            $last_hashes = get_post_meta($this->campaign_id, $last_hashes_name, false);
            if (empty($last_hashes)) {
                $last_hashes = array();
            }
            $max_duplicated_hashes_count = apply_filters('wpematico_max_duplicated_hashes_count', 20, $this->campaign_id, $feed);

            while (sizeof($last_hashes) > $max_duplicated_hashes_count) {
                $old_hash = array_shift($last_hashes);
                if (!empty($old_hash)) {
                    delete_post_meta($this->campaign_id, $last_hashes_name, $old_hash);
                }
            }
        }
        
        // Set your desired maximum memory usage in bytes 
        $batchSize = apply_filters( 'wpematico_fetch_batchsize' , ($duplicate_options['jumpduplicates']) ? 0 : $this->campaign['campaign_max']);
        
        if(empty($batchSize)){
            $simplePieItems = $simplepie->get_items();
        }else{
            $simplePieItems = $simplepie->get_items(0, $batchSize);
        }

        foreach ($simplePieItems as $item) {
            
            if ($item->get_permalink() || $this->campaign['campaign_type'] == 'youtube' || $this->campaign['campaign_type'] == 'xml' || !empty($item->get_item_tags('', 'link')) ) {
                $permalink = $item->get_permalink();
            } else {
                $permalink = $item->get_id();
            }

           
            // Get the source Permalink trying to redirect if is set.
            $permalink = $this->getReadUrl($permalink, $this->campaign);
            
            if ($prime) {
                //with first item get the hash of the last item (new) that will be saved.
                $this->lasthash[wpematico_feed_hash_key('lasthash', $feed)] = md5($permalink);
                $prime = false;
            }


            $this->currenthash[wpematico_feed_hash_key('currenthash', $feed)] = md5($permalink); // el hash del item actual del feed feed 
            if (!$duplicate_options['allowduplicates'] || !$duplicate_options['allowduptitle'] || !$duplicate_options['allowduphash'] || $duplicate_options['add_extra_duplicate_filter_meta_source']) {
                if (!$duplicate_options['allowduphash']) {
                    // chequeo a la primer coincidencia sale del foreach
                    $lasthashvar = '_lasthash_' . sanitize_file_name($feed);
                    $hashvalue = get_post_meta($this->campaign_id, $lasthashvar, true);
                    if (!isset($this->campaign[wpematico_feed_hash_key('campaign', $feed)]['lasthash']))
                    $this->campaign[wpematico_feed_hash_key('campaign', $feed)]['lasthash'] = '';

                    $dupi = ($this->campaign[wpematico_feed_hash_key('campaign', $feed)]['lasthash'] == $this->currenthash[wpematico_feed_hash_key('currenthash', $feed)]) ||
                        ($hashvalue == $this->currenthash[wpematico_feed_hash_key('currenthash', $feed)]);
                    if ($dupi) {
                        trigger_error(sprintf(__('Found duplicated hash \'%s\'', 'wpematico'), $item->get_permalink()) . ': ' . $this->currenthash[wpematico_feed_hash_key('currenthash', $feed)], E_USER_NOTICE);
                        if (!$duplicate_options['jumpduplicates']) {
                            trigger_error(__('Filtering duplicated posts.', 'wpematico'), E_USER_NOTICE);
                            break;
                        } else {
                            trigger_error(__('Jumping duplicated post. Continuing.', 'wpematico'), E_USER_NOTICE);
                            continue;
                        }
                    }

                    if (!$duplicate_options['allowduphash'] && $duplicate_options['jumpduplicates']) {
                        if (in_array($this->currenthash[wpematico_feed_hash_key('currenthash', $feed)], $last_hashes)) {
                            trigger_error(sprintf(__('Found duplicated hash of item \'%s\'', 'wpematico'), $item->get_permalink()) . ': ' . $this->currenthash[wpematico_feed_hash_key('currenthash', $feed)], E_USER_NOTICE);
                            trigger_error(__('Jumping duplicated post. Continuing.', 'wpematico'), E_USER_NOTICE);
                            continue;
                        }
                    }
                }
                if (!$duplicate_options['allowduptitle']) {
                    if (WPeMatico::is_duplicated_item($this->campaign, $feed, $item)) {
                        trigger_error(sprintf(__('Found duplicated title \'%s\'', 'wpematico'), $item->get_title()) . ': ' . $this->currenthash[wpematico_feed_hash_key('currenthash', $feed)], E_USER_NOTICE);
                        if (!$duplicate_options['jumpduplicates']) {
                            trigger_error(__('Filtering duplicated posts.', 'wpematico'), E_USER_NOTICE);
                            break;
                        } else {
                            trigger_error(__('Jumping duplicated post. Continuing.', 'wpematico'), E_USER_NOTICE);
                            continue;
                        }
                    }
                }
            }
            $count++;
            array_unshift($items, $item); // add at Post stack in correct order by date 		  
            if ($count == $this->campaign['campaign_max']) {
                trigger_error(sprintf(__('Campaign fetch limit reached at %s.', 'wpematico'), $this->campaign['campaign_max']), E_USER_NOTICE);
                break;
            }
        }
        $campaign_timeout = (int) $this->cfg['campaign_timeout'];
        // Processes post stack
        $realcount = 0;
        foreach ($items as $item) {
            // interrupt the script if timeout 
            if (current_time('timestamp') - $this->campaign['starttime'] >= $campaign_timeout) {
                trigger_error(sprintf(__('Reached running timeout at %1$d sec.', 'wpematico'), $campaign_timeout), E_USER_WARNING);
                break;
            }
            // set timeout for rest of the items to Timeout setting less current run time
            wpematico_init_set('max_execution_time', $campaign_timeout, true); // - ( current_time('timestamp') - $this->campaign['starttime'] ), true);
            $realcount++;
            if ($item->get_permalink() || $this->campaign['campaign_type'] == 'youtube' || $this->campaign['campaign_type'] == 'xml' || !empty($item->get_item_tags('', 'link'))) {
                $permalink = $item->get_permalink();
            } else {
                $permalink = $item->get_id();
            }
            // Get the source Permalink trying to redirect if is set.
            $permalink = $this->getReadUrl($permalink, $this->campaign);
            $this->current_item['permalink'] = $permalink;
            $this->currenthash[wpematico_feed_hash_key('currenthash', $feed)] = md5($permalink); // the hash of the current item feed 
            $suma = $this->processItem($simplepie, $item, $feed);

            $lasthashvar = '_lasthash_' . sanitize_file_name($feed);
            $hashvalue = $this->currenthash[wpematico_feed_hash_key('currenthash', $feed)];
            add_post_meta($this->campaign_id, $lasthashvar, $hashvalue, true) or
                update_post_meta($this->campaign_id, $lasthashvar, $hashvalue);

            if (!$duplicate_options['allowduphash'] && $duplicate_options['jumpduplicates']) {
                add_post_meta($this->campaign_id, $last_hashes_name, $hashvalue, false);
            }

            if (isset($suma) && is_int($suma)) {
                $realcount = $realcount + $suma;
                $suma = "";
            }
            $this->current_item = array();
        }
           
        unset($items);
        unset($simplepie);

        if ($realcount) {
            trigger_error(sprintf(__('%s posts added', 'wpematico'), $realcount), E_USER_NOTICE);
        }

        return $realcount;
    }

    /**
     * Processes an item: parses and filters
     * @param   $feed       object    Feed database object
     * @param   $item       object    SimplePie_Item object
     * @return bool true on success
     */
    function processItem($feed, $item, $feedurl) {
        global $wpdb, $realcount,$wpematico_fifu_meta, $post;
        trigger_error(sprintf('<b>' . __('Processing item %s', 'wpematico'), $item->get_title() . '</b>'), E_USER_NOTICE);
        
        // First exclude filters
        if ($this->exclude_filters($this->current_item, $this->campaign, $feed, $item)) {
            return -1;  // resta este item del total 
        }
        // Item date
        $itemdate = null;  // current date
        if ($this->campaign['campaign_feeddate']) {
            $itemdate = $item->get_date('U');
        }
        if (!$this->campaign['campaign_feeddate_forced']) {
            if ($this->campaign['campaign_feeddate']) {
                if (($itemdate > $this->campaign['lastrun'] && $itemdate < current_time('timestamp', 1))) {
                    trigger_error(__('Assigning original date to post.', 'wpematico') . "($itemdate)", E_USER_NOTICE);
                } else {
                    $itemdate = null;
                    trigger_error(__('Original date out of range.  Assigning current date to post.', 'wpematico'), E_USER_NOTICE);
                }
            }
        } else {
            trigger_error(__('Forced original date to post.', 'wpematico') . "($itemdate)", E_USER_NOTICE);
        }
        $this->current_item['date'] = apply_filters('wpematico_get_feeddate', $itemdate, $this->current_item, $this->campaign, $feedurl, $item);

        // Item title
		$this->current_item['title'] = $item->get_title();
        $this->current_item['title'] = htmlspecialchars_decode($this->current_item['title']);
        if ($this->campaign['campaign_enable_convert_utf8']) {
            $this->current_item['title'] = WPeMatico::change_to_utf8($this->current_item['title']);
        }
		/**
		 * 	Since 2.7 
		 * Allows parser the title by addons or external filters
		 */
		$this->current_item['title'] = apply_filters('wpematico_get_post_title', $this->current_item['title'], $this->current_item, $this->campaign, $item, $realcount);

        $this->current_item['title'] = esc_attr($this->current_item['title']);

        $this->current_item['title'] = html_entity_decode($this->current_item['title'], ENT_QUOTES | ENT_HTML401, 'UTF-8');
		
        // Item author
        //if( $this->cfg['nonstatic'] ) { $this->current_item = WPeMaticoPRO_Helpers :: author($this->current_item,$this->campaign, $feedurl, $item ); }else $this->current_item['author'] = $this->campaign['campaign_author'];
        $this->current_item['author'] = $this->campaign['campaign_author'];
        $this->current_item = apply_filters('wpematico_get_author', $this->current_item, $this->campaign, $feedurl, $item);

        // Item content
        $this->current_item['content'] = apply_filters('wpematico_get_post_content_feed', $item->get_content(), $this->campaign, $feed, $item);
        // Item excerpt
        $this->current_item['excerpt'] = '';
        if ($this->campaign['campaign_get_excerpt']) {
            $this->current_item['excerpt'] = apply_filters('wpematico_get_post_excerpt_feed', $item->get_description(), $this->campaign, $feed, $item);
        }
        $this->current_item = apply_filters('wpematico_get_post_content', $this->current_item, $this->campaign, $feed, $item);

        if ($this->campaign['campaign_enable_convert_utf8']) {
            $this->current_item['content'] = WPeMatico::change_to_utf8($this->current_item['content']);
        }

        if ($this->cfg['entity_decode_html']) {
            $this->current_item['content'] = html_entity_decode($this->current_item['content'], ENT_COMPAT | ENT_HTML401, 'UTF-8');
        }


        $this->current_item = apply_filters('wpematico_item_pre_media', $this->current_item, $this->campaign, $feed, $item);
        
        if (isset($this->current_item['SKIP']) && is_int($this->current_item['SKIP']))
            return $this->current_item['SKIP'];

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
        $this->current_item = apply_filters('wpematico_item_filters_pre_img', $this->current_item, $this->campaign);
        //gets images array 
        $this->current_item = $this->Get_Item_images($this->current_item, $this->campaign, $feed, $item, $options_images);
        $this->current_item['featured_image'] = apply_filters('wpematico_set_featured_img', '', $this->current_item, $this->campaign, $feed, $item);
        
        if ($options_images['fifu-video']) {
            $fifu_videos = !empty($this->current_item['videos']) ? $this->current_item['videos'] : $this->parseVideos($this->current_item['content'], true);
            if (!empty($fifu_videos)) {
                if(function_exists('fifu_dev_set_video'))
                    $this->current_item['featured_image'] = fifu_dev_set_video($this->campaign_id, $fifu_videos[0]);
            }
        }else{
            if ($options_images['featuredimg']) {
                if (!empty($this->current_item['images'])) {
                    $this->current_item['featured_image'] = apply_filters('wpematico_get_featured_img', $this->current_item['images'][0], $this->current_item);
                }
            }
        }

        if ($options_images['rmfeaturedimg']) { // removes featured from content
            if(!empty($this->current_item['featured_image'])){
                $this->current_item['content'] = $this->strip_Image_by_src($this->current_item['featured_image'], $this->current_item['content']);
            }elseif(!empty($wpematico_fifu_meta['fifu_image_url'])){
                $this->current_item['content'] = $this->strip_Image_by_src($wpematico_fifu_meta['fifu_image_url'], $this->current_item['content']);
            }
        }

        /**
         * @since 2.7.7
         * Filter to put in content 1st image link
         */

         
        $this->current_item = apply_filters('wpematico_put_first_img', $this->current_item, $this->campaign, $item);

        // Uploads and changes img sources in content
        $this->current_item = $this->Item_images($this->current_item, $this->campaign, $feed, $item, $options_images);
        $this->current_item = $this->featured_image_selector($this->current_item, $this->campaign, $feed, $item, $options_images);

        $this->current_item = apply_filters('wpematico_item_filters_pos_img', $this->current_item, $this->campaign);

        $this->current_item = apply_filters('wpematico_item_pos_media', $this->current_item, $this->campaign, $feed, $item);
        if (isset($this->current_item['SKIP']) && is_int($this->current_item['SKIP']))
            return $this->current_item['SKIP'];


        //********** Do parses contents and titles
        $this->current_item = $this->Item_parsers($this->current_item, $this->campaign, $feed, $item, $realcount, $feedurl);
        if (isset($this->current_item['SKIP']) && is_int($this->current_item['SKIP']))
            return $this->current_item['SKIP'];

        // Primero proceso las categorias si las hay y las nuevas las agrego al final del array
        $this->current_item['categories'] = (array) $this->campaign['campaign_categories'];
        if ($this->campaign['campaign_autocats']) {
            if ($autocats = $item->get_categories()) {
                /**
                 * wpematico_before_insert_autocats
                 * Filters the array of categories obtained by simplepie to be parsed before inserted into the database.
                 * @since 2.1.2
                 * @param array $autocats The array of categories names.
                 */
                $autocats = apply_filters('wpematico_before_insert_autocats', $autocats, $this);
                trigger_error(__('Assigning Auto Categories.', 'wpematico'), E_USER_NOTICE);
                foreach ($autocats as $id => $catego) {
                    $catname = $catego->term;
                    if (!empty($catname)) {
                        //$this->current_item['categories'][] = wp_create_category($catname);  //Si ya existe devuelve el ID existente  // wp_insert_category(array('cat_name' => $catname));  //
                        $term = term_exists($catname, 'category');
                        if ($term !== 0 && $term !== null) {  // ya existe
                            trigger_error(__('Category exist: ', 'wpematico') . $catname, E_USER_NOTICE);
                        } else { //si no existe la creo
                            if (!isset($this->campaign['campaign_local_category']) || !$this->campaign['campaign_local_category']) { //if this option doesn't exist, continue with the creation
                                trigger_error(__('Adding Category: ', 'wpematico') . $catname, E_USER_NOTICE);
                                $parent_cat = "0";
                                if (isset($this->campaign['campaign_parent_autocats']) && $this->campaign['campaign_parent_autocats'] > 0) {
                                    $parent_cat = $this->campaign['campaign_parent_autocats'];
                                }
                                $arg_description = __('Auto Added by WPeMatico', 'wpematico');
                                if (isset($this->cfg['disable_categories_description']) && $this->cfg['disable_categories_description']) {
                                    $arg_description = '';
                                }
                                $arg_description = apply_filters('wpematico_addcat_description', $arg_description, $catname);

                                $arg = array('description' => $arg_description, 'parent' => $parent_cat);
                                $term = wp_insert_term($catname, "category", $arg);
                            }
                        }
                        if (is_wp_error($term) || empty($term)) {
                            continue;
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
        $this->current_item = $this->Item_filters($this->current_item, $this->campaign, $feed, $item);
        $this->current_item = apply_filters('wpematico_pos_item_filters', $this->current_item, $this->campaign, $feed, $item);

        $this->current_item = apply_filters('wpematico_meta_custom', $this->current_item, $this->campaign, $feed, $item);

        if ($this->cfg['nonstatic'] && !empty($this->current_item['tags'])) {
            $this->current_item['campaign_tags'] = array_unique(array_merge($this->current_item['campaign_tags'], $this->current_item['tags']), SORT_REGULAR);
        }

        // Meta
        if (isset($this->cfg['disableccf']) && $this->cfg['disableccf']) {
            $this->current_item['meta'] = array();
        } else {
            $arraycf = array(
                'wpe_campaignid' => $this->campaign_id,
                'wpe_feed' => $feed->feed_url,
                'wpe_sourcepermalink' => isset($this->current_item['permalink']) ? $this->current_item['permalink'] : ''
            );
            $this->current_item['meta'] = (isset($this->current_item['meta']) && !empty($this->current_item['meta']) ) ? array_merge($this->current_item['meta'], $arraycf) : $arraycf;

            /**
             * wpem_meta_data
             * Filter the array of meta fields to be parsed before attached to the post.
             * @since 1.3
             * @param array $this->current_item['meta']  The array of meta fields: name => value.
             */
            $this->current_item['meta'] = apply_filters('wpem_meta_data', $this->current_item['meta']);
        }

        if ($this->campaign['campaign_type'] == 'bbpress') {
            if (empty($this->campaign['campaign_bbpress_forum'])) {
                $this->current_item['customposttype'] = 'forum';
            } else {
                $this->current_item['customposttype'] = 'topic';
                if (!empty($this->campaign['campaign_bbpress_topic'])) {
                    $this->current_item['customposttype'] = 'reply';
                }
            }
        }



        $this->current_item['customposttype'] = (isset($this->current_item['customposttype']) && !empty($this->current_item['customposttype']) ) ? $this->current_item['customposttype'] : 'post';
        $this->current_item['commentstatus'] = (isset($this->current_item['commentstatus']) && !empty($this->current_item['commentstatus']) ) ? $this->current_item['commentstatus'] : 'open';

        $images = $this->current_item['images'];
        $campaign_tags = $this->current_item['campaign_tags'];
        $post_format = $this->current_item['campaign_post_format'];

        $this->current_item['date_formated'] = ($this->current_item['date']) ? gmdate('Y-m-d H:i:s', $this->current_item['date'] + (get_option('gmt_offset') * 3600)) : null;

        $truecontent = '';
        if ($this->cfg['woutfilter'] && $this->campaign['campaign_woutfilter']) {
            $truecontent = $this->current_item['content'];
            $this->current_item['content'] = '';
        }

        if ($this->campaign['copy_permanlink_source'] && $this->campaign['campaign_type'] != 'youtube') {
            $this->current_item['slug'] = WPeMatico::get_slug_from_permalink($item->get_permalink());
        } else {
            $this->current_item['slug'] = sanitize_title($this->current_item['title']);
        }

        $this->current_item['post_parent'] = 0;
        if (isset($this->campaign['campaign_parent_page']) && $this->campaign['campaign_parent_page']) {
            $this->current_item['post_parent'] = $this->campaign['campaign_parent_page'];
        }

        if ($this->campaign['campaign_type'] == 'bbpress') {
            if ($this->current_item['customposttype'] == 'topic') {
                $this->current_item['post_parent'] = $this->campaign['campaign_bbpress_forum'];
            }
            if ($this->current_item['customposttype'] == 'reply') {
                $this->current_item['post_parent'] = $this->campaign['campaign_bbpress_topic'];
            }
        }
        $this->current_item['title'] = apply_filters('wpem_parse_title', $this->current_item['title']);
        $this->current_item['content'] = apply_filters('wpem_parse_content', $this->current_item['content']);
        $this->current_item['excerpt'] = apply_filters('wpem_parse_excerpt', $this->current_item['excerpt']);
        $this->current_item['slug'] = apply_filters('wpem_parse_name', $this->current_item['slug']);
        $this->current_item['date_formated'] = apply_filters('wpem_parse_date', $this->current_item['date_formated']);
        $this->current_item['posttype'] = apply_filters('wpem_parse_status', $this->current_item['posttype']);
        $this->current_item['customposttype'] = apply_filters('wpem_parse_post_type', $this->current_item['customposttype']);
        $this->current_item['author'] = apply_filters('wpem_parse_authorid', $this->current_item['author']);
        $this->current_item['commentstatus'] = apply_filters('wpem_parse_comment_status', $this->current_item['commentstatus']);
        $this->current_item['post_parent'] = apply_filters('wpem_parse_parent', $this->current_item['post_parent']);

        $args = array(
            'post_title' => $this->current_item['title'],
            'post_content' => $this->current_item['content'],
            'post_excerpt' => isset($this->current_item['excerpt']) ? $this->current_item['excerpt'] : '',
            'post_name' => $this->current_item['slug'],
            'post_content_filtered' => apply_filters('wpem_parse_content_filtered', $this->current_item['content']),
            'post_status' => $this->current_item['posttype'],
            'post_type' => $this->current_item['customposttype'],
            'post_author' => $this->current_item['author'],
            'post_date' => $this->current_item['date_formated'],
            'comment_status' => $this->current_item['commentstatus'],
            'post_parent' => $this->current_item['post_parent'],
            'ping_status' => ($this->current_item['allowpings']) ? "open" : "closed"
        );

        if (!empty($this->current_item['categories']) && is_object_in_taxonomy($args['post_type'], 'category')) {
            if (empty($args['post_category'])) {
                $args['post_category'] = array();
            }
            $args['post_category'] = $this->current_item['categories'];
        }

        if (!empty($this->current_item['campaign_tags']) && is_object_in_taxonomy($args['post_type'], 'post_tag')) {
            if (empty($args['tags_input'])) {
                $args['tags_input'] = array();
            }
            $args['tags_input'] = $this->current_item['campaign_tags'];
        }

        if (has_filter('wpematico_pre_insert_post'))
            $args = apply_filters('wpematico_pre_insert_post', $args, $this->campaign);

        if (apply_filters('wpematico_allow_insertpost', true, $this, $args)) {
            remove_filter('content_save_pre', 'wp_filter_post_kses');
//			remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
            $post_id = wp_insert_post($args);

            if ($this->cfg['woutfilter'] && $this->campaign['campaign_woutfilter']) {
                global $wpdb, $wp_locale, $current_blog;
                $table_name = $wpdb->prefix . "posts";
                $blog_id = @$current_blog->blog_id;
                $this->current_item['content'] = $truecontent;
                trigger_error('** ' . __('Adding unfiltered content', 'wpematico') . ' **', E_USER_NOTICE);
                $wpdb->update($table_name, array('post_content' => $this->current_item['content'], 'post_content_filtered' => $this->current_item['content']), array('ID' => $post_id));
            }

            $this->postProcessItem($post_id, $item);

            // If pingback/trackbacks
            if ($this->campaign['campaign_allowpings']) {
                trigger_error(__('Processing item pingbacks', 'wpematico'), E_USER_NOTICE);
                require_once(ABSPATH . WPINC . '/comment.php');
                pingback($this->current_item['content'], $post_id);
            }
            // wpematico_allow_insertpost
        } else {
            return -1; // resta este item del total 
        }
    }

    function postProcessItem($post_id, $item) {

        $options_images = WPeMatico::get_images_options($this->cfg, $this->campaign);

        if ($this->campaign['campaign_type'] == 'bbpress') {


            if ($this->current_item['customposttype'] == 'topic') {

                if (function_exists('bbp_bump_forum_topic_count') && function_exists('bbp_update_forum_last_active_time')) {
                    bbp_bump_forum_topic_count($this->campaign['campaign_bbpress_forum']);

                    bbp_update_forum_last_active_time($this->campaign['campaign_bbpress_forum'], current_time('mysql'));
                    bbp_update_forum_last_topic_id($this->campaign['campaign_bbpress_forum'], $post_id);
                    bbp_update_forum_last_reply_id($this->campaign['campaign_bbpress_forum'], $post_id);
                    bbp_update_forum_last_active_id($this->campaign['campaign_bbpress_forum'], $post_id);
                    bbp_update_topic_last_active_time($post_id, current_time('mysql'));
                }

                $this->current_item['meta']['_bbp_forum_id'] = $this->campaign['campaign_bbpress_forum'];
                $this->current_item['meta']['_bbp_topic_id'] = $post_id;
                $this->current_item['meta']['_bbp_reply_count'] = 0;
            }
            if ($this->current_item['customposttype'] == 'reply') {

                if (function_exists('bbp_bump_topic_reply_count') && function_exists('bbp_update_topic_last_active_time')) {

                    bbp_bump_forum_reply_count($this->campaign['campaign_bbpress_forum']);
                    bbp_update_forum_last_active_time($this->campaign['campaign_bbpress_forum'], current_time('mysql'));
                    bbp_update_forum_last_active_id($this->campaign['campaign_bbpress_forum'], $this->campaign['campaign_bbpress_topic']);

                    bbp_bump_topic_reply_count($this->campaign['campaign_bbpress_topic']);
                    bbp_update_topic_last_active_time($this->campaign['campaign_bbpress_topic'], current_time('mysql'));
                    bbp_update_topic_last_active_id($this->campaign['campaign_bbpress_topic'], $post_id);
                }

                $this->current_item['meta']['_bbp_forum_id'] = $this->campaign['campaign_bbpress_forum'];
                $this->current_item['meta']['_bbp_topic_id'] = $this->campaign['campaign_bbpress_topic'];
            }
        }


        add_filter('content_save_pre', 'wp_filter_post_kses');

        if (!empty($this->current_item['categories'])) { //Adds to campaign logs the categories added
            $wpe_categories_added = wp_get_object_terms($post_id, 'category');
            $aaa = '';
            foreach ($wpe_categories_added AS $wpe_category_added) {
                $aaa .= $wpe_category_added->term_id . ', ';
            }
            if (!empty($aaa)) {
                $aaa = rtrim($aaa, ', ');
                trigger_error(__("Categories added: ", 'wpematico') . $aaa, E_USER_NOTICE);
            }
        }

        if (!empty($this->current_item['campaign_tags'])) { //Adds to campaign logs the post tags added
            $wpe_tags_added = wp_get_object_terms($post_id, 'post_tag');
            $aaa = '';
            foreach ($wpe_tags_added AS $wpe_tag_added) {
                $aaa .= $wpe_tag_added->term_id . ', ';
            }
            if (!empty($aaa)) {
                $aaa = rtrim($aaa, ', ');
                trigger_error(__("Tags added: ", 'wpematico') . $aaa, E_USER_NOTICE);
            }
        } else if (has_action('wpematico_chinese_tags')) {
            do_action('wpematico_chinese_tags', $post_id, $this->current_item['content'], $this->campaign);
        }

        $post_format = $this->current_item['campaign_post_format'];

        if (!empty($post_format)) { //inserto post format
            //$aaa = wp_set_post_terms( $post_id, $category, 'post_format');
            $aaa = set_post_format($post_id, $post_format);
            if (!empty($aaa))
                trigger_error(__("Post format added: ", 'wpematico') . $post_format, E_USER_NOTICE);
        }



        // insert PostMeta
        foreach ($this->current_item['meta'] as $key => $value) {
            add_post_meta($post_id, $key, $value, true) or
                    update_post_meta($post_id, $key, $value);
        }

        if (has_action('wpematico_inserted_post'))
            do_action('wpematico_inserted_post', $post_id, $this->campaign, $item);


        // Attaching images uploaded to created post in media library 
        // Featured Image
        $featured_image_attach_id = 0;
        $img_new_url = '';
        if (!empty($this->current_item['nofeatimg'])) {

            trigger_error('<strong>' . __('Skip Featured Image.', 'wpematico') . '</strong>', E_USER_NOTICE);
			
        } else if (!empty($this->current_item['featured_image']) && (!$this->images_options['fifu'])) {

            trigger_error(__('Featuring Image Into Post.', 'wpematico'), E_USER_NOTICE);

            if (!isset($this->current_item['images'][0]) or ($this->current_item['images'][0] != $this->current_item['featured_image'])) {

                $itemUrl = $this->current_item['permalink'];
                $imagen_src = $this->current_item['featured_image'];
                //**** ecesaria para la featured ?	$imagen_src = apply_filters('wpematico_imagen_src', $imagen_src ); // allow strip parts 
                $imagen_src_real = $this->getRelativeUrl($itemUrl, $imagen_src);
                // Strip all white space on images URLs.	
                $imagen_src_real = str_replace(' ', '%20', $imagen_src_real);
                // Fix images URLs with entities like &amp;	to get it with correct name and remain the original in images array.
                $imagen_src_real = html_entity_decode($imagen_src_real);
                $imagen_src_real = apply_filters('wpematico_img_src_url', $imagen_src_real);
                $allowed = (isset($this->cfg['images_allowed_ext']) && !empty($this->cfg['images_allowed_ext']) ) ? $this->cfg['images_allowed_ext'] : 'jpg,gif,png,tif,bmp,jpeg';
                $allowed = apply_filters('wpematico_allowext', $allowed);
                //Fetch and Store the Image	
                ///////////////***************************************************************************************////////////////////////
                $newimgname = apply_filters('wpematico_newimgname', sanitize_file_name(urlencode(basename($imagen_src_real))), $this->current_item, $this->campaign, $item);  // new name here
                // Primero intento con mi funcion mas rapida
                $upload_dir = wp_upload_dir();
                $imagen_dst = trailingslashit($upload_dir['path']) . $newimgname;
                $imagen_dst_url = trailingslashit($upload_dir['url']) . $newimgname;
                $img_new_url = "";
                if (in_array(str_replace('.', '', strrchr(strtolower($imagen_dst), '.')), explode(',', $allowed))) {   // -------- Controlo extensiones permitidas
                    trigger_error('Uploading media=' . $imagen_src . ' <b>to</b> imagen_dst=' . $imagen_dst . '', E_USER_NOTICE);
                    $newfile = ($options_images['customupload']) ? WPeMatico::save_file_from_url($imagen_src_real, $imagen_dst) : false;
                    if ($newfile) { //subió
                        trigger_error('Uploaded media=' . $newfile, E_USER_NOTICE);
                        $imagen_dst = $newfile;
                        $imagen_dst_url = trailingslashit($upload_dir['url']) . basename($newfile);
                        $img_new_url = $imagen_dst_url;
                    } else { // falló -> intento con otros
                        $bits = WPeMatico::wpematico_get_contents($imagen_src_real);
                        $mirror = wp_upload_bits($newimgname, NULL, $bits);
                        if (!$mirror['error']) {
                            trigger_error($mirror['url'], E_USER_NOTICE);
                            $img_new_url = $mirror['url'];
                        }
                    }
                }
            } else {
                $img_new_url = $this->current_item['featured_image'];
            }
            if (!empty($img_new_url)) {
                $this->current_item['featured_image'] = $img_new_url;
                array_shift($this->current_item['images']);  //deletes featured image from array to avoid double upload below
                $attachid = false;
                if (!$options_images['imgattach']) {
                    //get previously uploaded attach IDs, false if not exist.  (Just attach once/first time)
                    //	$attachid = $this->get_attach_id_from_url($this->current_item['featured_image']); 
                    $attachid = attachment_url_to_postid($this->current_item['featured_image']);
                }
                if ($attachid == false) {
                    $attachid = $this->insertfileasattach($this->current_item['featured_image'], $post_id);
                }
                set_post_thumbnail($post_id, $attachid);
                $featured_image_attach_id = $attachid;
                //add_post_meta($post_id, '_thumbnail_id', $attachid);
            } else {
                //trigger_error( __('Upload featured image failed:', 'wpematico' ).$imagen_dst,E_USER_WARNING);
            }
        }
        $featured_image_attach_id = apply_filters('wpematico_featured_image_attach_id', $featured_image_attach_id, $post_id, $this->current_item, $this->campaign, $item);
        if ($featured_image_attach_id == 0) {
            trigger_error(__('The post has no a featured image.', 'wpematico'), E_USER_WARNING);
        }

        // Attach files in post content previously uploaded
        if ($options_images['imgcache'] && $options_images['imgattach']) {
            if (is_array($this->current_item['images'])) {
                if (sizeof($this->current_item['images'])) { // Si hay alguna imagen 
                    trigger_error(__('Attaching images', 'wpematico') . ": " . sizeof($this->current_item['images']), E_USER_NOTICE);
                    foreach ($this->current_item['images'] as $imagen_src) {
                        $attachid = $this->insertfileasattach($imagen_src, $post_id);
                    }
                }
            }
        }


        /**
         * Attach audios to post
         * @since 1.7.0
         */
        $options_audios = WPeMatico::get_audios_options($this->cfg, $this->campaign);
        if ($options_audios['audio_cache'] && $options_audios['audio_attach']) {
            if (is_array($this->current_item['audios'])) {
                if (sizeof($this->current_item['audios'])) { // if exist a audio.
                    trigger_error(__('Attaching audios', 'wpematico') . ": " . sizeof($this->current_item['audios']), E_USER_NOTICE);
                    foreach ($this->current_item['audios'] as $audio_src) {
                        $attachid = $this->insertfileasattach($audio_src, $post_id);
                    }
                }
            }
        }

        /**
         * Attach videos to post
         * @since 1.7.0
         */
        $options_videos = WPeMatico::get_videos_options($this->cfg, $this->campaign);
        if ($options_videos['video_cache'] && $options_videos['video_attach']) {
            if (is_array($this->current_item['videos'])) {
                if (sizeof($this->current_item['videos'])) { // if exist a video.
                    trigger_error(__('Attaching videos', 'wpematico') . ": " . sizeof($this->current_item['videos']), E_USER_NOTICE);
                    foreach ($this->current_item['videos'] as $video_src) {
                        $attachid = $this->insertfileasattach($video_src, $post_id);
                    }
                }
            }
        }
    }

    private function fetch_end() {
        $this->campaign['lastrun'] = $this->campaign['starttime'];
        $this->campaign['lastruntime'] = current_time('timestamp') - $this->campaign['starttime'];
        $this->campaign['starttime'] = '';
        $this->campaign['postscount'] += $this->fetched_posts; // Suma los posts procesados
        $this->campaign['lastpostscount'] = $this->fetched_posts; //  posts procesados esta vez

        /* 		foreach($this->campaign['campaign_feeds'] as $feed) {    // Grabo el ultimo hash de cada feed
          @$this->campaign[$feed]['lasthash'] = $this->lasthash[$feed]; // paraa chequear duplicados por el hash del permalink original
          }
         */
        $this->campaign = apply_filters('Wpematico_end_fetching', $this->campaign, $this->fetched_posts);
        //if($this->cfg['nonstatic']){$this->campaign=WPeMaticoPRO_Helpers::ending($this->campaign,$this->fetched_posts);}

        WPeMatico :: update_campaign($this->campaign_id, $this->campaign);  //Save Campaign new data

        trigger_error(sprintf(__('Campaign fetched in %s sec.', 'wpematico'), $this->campaign['lastruntime']), E_USER_NOTICE);
    }

    public function __destruct() {
        global $campaign_log_message, $joberrors;
        //Send mail with log
        $sendmail = false;
        if ($joberrors > 0 and $this->campaign['mailerroronly'] and !empty($this->campaign['mailaddresslog']))
            $sendmail = true;
        if (!$this->campaign['mailerroronly'] and !empty($this->campaign['mailaddresslog']))
            $sendmail = true;
        if ($sendmail) {
            switch ($this->cfg['mailmethod']) {
                case 'SMTP':
                    do_action('wpematico_smtp_email');
                    break;
                default:
                    $headers[] = 'From: ' . $this->cfg['mailsndname'] . ' <' . $this->cfg['mailsndemail'] . '>';
                    //$headers[] = 'Cc: John Q Codex <jqc@wordpress.org>';
                    //$headers[] = 'Cc: iluvwp@wordpress.org'; // note you can just use a simple email address
                    break;
            }
            $headers[] = 'Content-Type: text/html; charset=UTF-8';

            $to_mail = $this->campaign['mailaddresslog'];

            $subject = __('WPeMatico Log ', 'wpematico') . ' ' . current_time('Y-m-d H:i') . ': ' . $this->campaign['campaign_title'];

            $mailbody = "WPeMatico Log" . "\n";
            $mailbody .= __("Campaign Name:", 'wpematico') . " " . $this->campaign['campaign_title'] . "\n";
            if (!empty($joberrors))
                $mailbody .= __("Errors:", 'wpematico') . " " . $joberrors . "\n";
            if (!empty($jobwarnings))
                $mailbody .= __("Warnings:", 'wpematico') . " " . $jobwarnings . "\n";

            $mailbody .= "\n" . $campaign_log_message;
            $mailbody .= "\n\n\n<hr>";
            $mailbody .= __("WPeMatico by ", "wpematico") . "<a href='https://etruel.com'>etruel</a> \n";

            wp_mail($to_mail, $subject, $mailbody, $headers, '');
        }

        $danger_options = WPeMatico::get_danger_options();

        if (!$danger_options['wpe_debug_logs_campaign']) {
            // Save last log as meta field in campaign, replace if exist
            add_post_meta($this->campaign_id, 'last_campaign_log', $campaign_log_message, true) or
                    update_post_meta($this->campaign_id, 'last_campaign_log', $campaign_log_message);
        } else {
            add_post_meta($this->campaign_id, 'last_campaign_log', $campaign_log_message, false);
        }

        $Suss = sprintf(__('Campaign fetched in %1s sec.', 'wpematico'), $this->campaign['lastruntime']) . '  ' . sprintf(__('Processed Posts: %s', 'wpematico'), $this->fetched_posts);
        $message = '<p>' . $Suss . '  <a href="JavaScript:void(0);" style="font-weight: bold; text-decoration:none; display:inline;" onclick="jQuery(\'#log_message_' . $this->campaign_id . '\').fadeToggle().addClass(\'active\'); jQuery(\'body\').addClass(\'wpe_modal_log-is-active\');">' . __('Show detailed Log', 'wpematico') . '.</a></p>';
        $campaign_log_message = $message . '<div id="log_message_' . $this->campaign_id . '" class="wpe_modal_log-box fade" style="display:none;"><div class="wpe_modal_log-body"><a href="JavaScript:void(0);" class="wpe_modal_log-close" onclick="jQuery(\'#log_message_' . $this->campaign_id . '\').fadeToggle().removeClass(\'active\'); jQuery(\'body\').removeClass(\'wpe_modal_log-is-active\');"><span class="dashicons dashicons-no-alt"></span></a><div class="wpe_modal_log-header"><h3>'. $this->campaign['campaign_title'] .' - #'. $this->campaign_id .'</h3></div><div class="wpe_modal_log-content">' . $campaign_log_message . '</div></div></div><span id="ret_lastruntime" style="display:none;">' . $this->campaign["lastruntime"] . '</span><span id="ret_lastposts" style="display:none;">' . $this->fetched_posts . '</span>';
    }
}