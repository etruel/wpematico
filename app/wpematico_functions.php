<?php
/**
 * WPeMatico plugin for WordPress
 * wpematico_functions
 * Contains all the auxiliary methods and functions to be called for the plugin inside WordPress pages.

 * @requires  campaign_fetch_functions
 * @package   wpematico
 * @link      https://github.com/etruel/wpematico
 * @author    Esteban Truelsegaard <etruel@etruel.com>
 * @copyright 2006-2019 Esteban Truelsegaard
 * @license   GPL v2 or later
 */
// don't load directly 
if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}
if (!class_exists('WPeMatico_functions')) {
	
	class WPeMatico_functions {

		public static $current_feed = ''; // The current feed that is running.

		/**
		 * @access public
		 * @return $dev Bool true on duplicate item.
		 * @since 1.9
		 */
		public static function is_duplicated_item($campaign, $feed, $item) {
			// Post slugs must be unique across all posts.
			global $wpdb, $wp_rewrite;
			$post_ID = 0;
			$cpost_type = $campaign['campaign_customposttype'];
			$dev = false;

			$wfeeds = $wp_rewrite->feeds;
			if (!is_array($wfeeds))
				$wfeeds = array();
			$title = $item->get_title();

			$title = htmlspecialchars_decode($title);
			if ($campaign['campaign_enable_convert_utf8']) {
				$title = WPeMatico::change_to_utf8($title);
			}

			$title = esc_attr($title);
			$title = html_entity_decode($title, ENT_QUOTES | ENT_HTML401, 'UTF-8');
			if ($campaign['copy_permanlink_source']) {
				$permalink = $item->get_permalink();
				$slug = self::get_slug_from_permalink($permalink);
			} else {
				$slug = sanitize_title($title);
			}

			$exist_post_on_db = false;
			/**
			 * Deprecated since 1.6 in favor of a query improved by db indexes
			  //$check_sql = "SELECT post_name FROM $wpdb->posts WHERE post_name = %s AND post_type = %s AND ID != %d LIMIT 1";
			  //$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $cpost_type, $post_ID ) );
			  if ($exist_post_on_db || in_array( $slug, $wfeeds ) || apply_filters( 'wp_unique_post_slug_is_bad_flat_slug', false, $slug, $cpost_type ) ) {
			  $dev = true;
			  }
			 */
			$check_sql = "SELECT ID, post_name, post_type FROM $wpdb->posts WHERE post_name = %s LIMIT 1";
			$post_name_check = $wpdb->get_results($wpdb->prepare($check_sql, $slug));
			if (!empty($post_name_check)) {
				if ($post_name_check[0]->ID == 0 || $cpost_type == $post_name_check[0]->post_type) {
					$exist_post_on_db = true;
				}
			}

			if ($exist_post_on_db) {
				$dev = true;
			} else {
				if (in_array($slug, $wfeeds)) {
					$dev = true;
				} else {
					if (apply_filters('wp_unique_post_slug_is_bad_flat_slug', false, $slug, $cpost_type)) {
						$dev = true;
					}
				}
			}

			if (has_filter('wpematico_duplicates'))
				$dev = apply_filters('wpematico_duplicates', $dev, $campaign, $item);
			//  http://wordpress.stackexchange.com/a/72691/65771
			//  https://codex.wordpress.org/Function_Reference/get_page_by_title

			$dupmsg = ($dev) ? __('Yes', 'wpematico') : __('No', 'wpematico');
			/* translators: the title of the post. */
			trigger_error(sprintf(__('Checking duplicated title \'%s\'', 'wpematico'), $title) . ': ' . $dupmsg, E_USER_NOTICE);

			return $dev;
		}

		/**
		 * Static function change_to_utf8
		 * This function convert a string to UTF-8 if its has a different encoding.
		 * @access public
		 * @param $string String to convert to UTF-8
		 * @return $string String with UTF-8 encoding.
		 * @since 1.9.0
		 */
		public static function change_to_utf8($string) {
			$from = apply_filters('wpematico_custom_chrset', mb_detect_encoding($string, "auto"));
			if ($from && $from != 'UTF-8') {
				$string = mb_convert_encoding($string, 'UTF-8', $from);
			}
			return $string;
		}
		/**
		 * Static function get_enconding_from_url
		 * This function get the encoding from headers of a URL.
		 * @access public
		 * @param $url String with an URL
		 * @return $encoding String with the encoding of the URL.
		 * @since 1.9.1
		 */
		public static function get_enconding_from_header($url) {
			static $encoding_hosts = array();
			if (empty($encoding_hosts)) {
				$encoding_hosts = get_transient('wpematico_encoding_hosts');
				if ($encoding_hosts === false) {
					$encoding_hosts = array();
				}
			}

			$parsed_url = parse_url($url);
			$host = (isset($parsed_url['host']) ? $parsed_url['host'] : time());

			if (!isset($encoding_hosts[$host])) {
				/**
				 * First checks encoding in the feed file attribute on first line
				 * if not found find in their headers
				 */
				$encoding = '';
				$response = wp_remote_get(esc_url_raw($url));
				if (!empty($response)) {
					$body = wp_remote_retrieve_body($response);
					$lin1 = strtok($body, PHP_EOL);
					if (preg_match('/.+?encoding\s?=\s?[\"\'].*?(.+?)[\"\']/s', $lin1, $m)) {
						$encoding = $m[1];
					}
				}
				if ($encoding === '') {
					$content_type = wp_remote_retrieve_header($response, 'content-type');
					if (!empty($content_type)) {
						if (preg_match("#.+?/.+?;\\s?charset\\s?=\\s?(.+)#i", $content_type, $m)) {
							$encoding = $m[1];
						}
					}
				}
				$encoding_hosts[$host] = strtoupper($encoding);
				set_transient('wpematico_encoding_hosts', $encoding_hosts, (HOUR_IN_SECONDS * 6));
			}
			return $encoding_hosts[$host];
		}

		/**
		 * Static function detect_encoding_from_headers
		 * This function filter the input encoding used in change_to_utf8
		 * @access public
		 * @param $from String with the input encoding 
		 * @return $from String with the input encoding that maybe is from HTTP headers.
		 * @since 1.9.1
		 */
		public static function detect_encoding_from_headers($from) {
			if (strtoupper($from) == 'ASCII') {
				$from = WPeMatico::get_enconding_from_header(WPeMatico::$current_feed);
			}
			return $from;
		}

		/**
		 * @access public
		 * @return $options Array of current duplicate settings.
		 * @since 2.0
		 */
		public static function get_duplicate_options($settings = array(), $campaign = array()) {
			$options = array();
			$options['allowduplicates'] = $settings['allowduplicates'];
			$options['allowduptitle'] = $settings['allowduptitle'];
			$options['allowduphash'] = $settings['allowduphash'];
			$options['jumpduplicates'] = $settings['jumpduplicates'];
			$options['add_extra_duplicate_filter_meta_source'] = $settings['add_extra_duplicate_filter_meta_source'];

			if (isset($campaign['campaign_no_setting_duplicate']) && $campaign['campaign_no_setting_duplicate']) {

				$options['allowduplicates'] = $campaign['campaign_allowduplicates'];
				$options['allowduptitle'] = $campaign['campaign_allowduptitle'];
				$options['allowduphash'] = $campaign['campaign_allowduphash'];
				$options['jumpduplicates'] = $campaign['campaign_jumpduplicates'];
				$options['add_extra_duplicate_filter_meta_source'] = $campaign['campaign_add_ext_duplicate_filter_ms'];
			}
			$options = apply_filters('wpematico_duplicate_options', $options, $settings, $campaign);
			return $options;
		}

		/**
		 * @access public
		 * @return $options Array of current images settings.
		 * @since 1.7.0
		 */
		public static function get_images_options($settings = array(), $campaign = array()) {
			
			$options = array();
			$options['imgcache'] = $settings['imgcache'];
			$options['fifu'] = $settings['fifu'];
			$options['fifu-video'] = $settings['fifu-video'];
			$options['imgattach'] = $settings['imgattach'];
			$options['gralnolinkimg'] = $settings['gralnolinkimg'];
			$options['image_srcset'] = $settings['image_srcset'];
			$options['save_attr_images'] = $settings['save_attr_images'];
			$options['featuredimg'] = $settings['featuredimg'];
			$options['rmfeaturedimg'] = $settings['rmfeaturedimg'];
			$options['customupload'] = $settings['customupload'];
			if (!$options['imgcache']) {
				$options['imgattach'] = false;
				$options['gralnolinkimg'] = false;
				$options['image_srcset'] = false;
				if (!$options['featuredimg']) {
					$options['customupload'] = false;
				}
			}
			if (isset($campaign['campaign_no_setting_img']) && $campaign['campaign_no_setting_img']) {
				$options['imgcache'] = $campaign['campaign_imgcache'];
				$options['imgattach'] = $campaign['campaign_attach_img'];
				$options['gralnolinkimg'] = $campaign['campaign_nolinkimg'];
				$options['image_srcset'] = $campaign['campaign_image_srcset'];
				$options['save_attr_images'] = $campaign['campaign_attr_images'];
				$options['featuredimg'] = $campaign['campaign_featuredimg'];
				$options['fifu'] = $campaign['campaign_fifu'];
				$options['fifu-video'] = $campaign['campaign_fifu_video'];
				$options['rmfeaturedimg'] = $campaign['campaign_rmfeaturedimg'];
				$options['customupload'] = $campaign['campaign_customupload'];
			}
			$options = apply_filters('wpematico_images_options', $options, $settings, $campaign);

			
			return $options;
		}

		/**
		 * @access public
		 * @return $options Array of current audios settings.
		 * @since 1.7.0
		 */
		public static function get_audios_options($settings = array(), $campaign = array()) {

			$options = array();
			$options['audio_cache'] = $settings['audio_cache'];
			$options['audio_attach'] = $settings['audio_attach'];
			$options['gralnolink_audio'] = $settings['gralnolink_audio'];
			$options['customupload_audios'] = $settings['customupload_audios'];
			if (!$options['audio_cache']) {
				$options['audio_attach'] = false;
				$options['gralnolink_audio'] = false;
				$options['customupload_audios'] = false;
			}
			if (isset($campaign['campaign_no_setting_audio']) && $campaign['campaign_no_setting_audio']) {
				$options['audio_cache'] = $campaign['campaign_audio_cache'];
				$options['audio_attach'] = $campaign['campaign_attach_audio'];
				$options['gralnolink_audio'] = $campaign['campaign_nolink_audio'];
				$options['customupload_audios'] = $campaign['campaign_customupload_audio'];
			}
			$options = apply_filters('wpematico_audios_options', $options, $settings, $campaign);
			return $options;
		}

		/**
		 * @access public
		 * @return $options Array of current videos settings.
		 * @since 1.7.0
		 */
		public static function get_videos_options($settings = array(), $campaign = array()) {
			$options = array();
			$options['video_cache'] = $settings['video_cache'];
			$options['video_attach'] = $settings['video_attach'];
			$options['gralnolink_video'] = $settings['gralnolink_video'];
			$options['customupload_videos'] = $settings['customupload_videos'];
			if (!$options['video_cache']) {
				$options['video_attach'] = false;
				$options['gralnolink_video'] = false;
				$options['customupload_videos'] = false;
			}
			if (isset($campaign['campaign_no_setting_video']) && $campaign['campaign_no_setting_video']) {
				$options['video_cache'] = $campaign['campaign_video_cache'];
				$options['video_attach'] = $campaign['campaign_attach_video'];
				$options['gralnolink_video'] = $campaign['campaign_nolink_video'];
				$options['customupload_videos'] = $campaign['campaign_customupload_video'];
			}
			$options = apply_filters('wpematico_videos_options', $options, $settings, $campaign);
			return $options;
		}

		/**
		 * @access public
		 * @return string $options  all wp defaults image mime types plus added by custom filters in standard ways.
		 * @since 2.5.3
		 */
		public static function get_images_allowed_mimes() {
			$mime_types = get_allowed_mime_types();
			$return = '';
			foreach ($mime_types as $key => $mime) {
				// Validate image types and replace | by ,
				if (strpos($mime, 'image/') !== false) {
					$return .= str_replace('|', ',', "$key,");
				}
			}
			//Deletes last chr if a ,
			$return = (substr($return, -1) == ",") ? substr($return, 0, -1) : $return;
			/**
			 * $return has array of all wp defaults image mime types plus added by custom filters in standard ways
			 */
			return apply_filters('get_wpematico_images_allowed_mimes', $return);
		}

		/**
		 * @access public
		 * @return array $options all wp defaults video mime types plus added by custom filters in standard ways.
		 * @since 2.5.3
		 */
		public static function get_audios_allowed_mimes() {
			$mime_types = get_allowed_mime_types();
			$return = '';
			foreach ($mime_types as $key => $mime) {
				// Validate audio types and replace | by ,
				if (strpos($mime, 'audio/') !== false) {
					$return .= str_replace('|', ',', "$key,");
				}
			}
			//Deletes last chr if a ,
			$return = (substr($return, -1) == ",") ? substr($return, 0, -1) : $return;
			/**
			 * $return has all wp defaults audio mime types plus added by custom filters in standard ways
			 */
			return apply_filters('get_wpematico_audios_allowed_mimes', $return);
		}

		/**
		 * @access public
		 * @return array $options all wp defaults video mime types plus added by custom filters in standard ways.
		 * @since 2.5.3
		 */
		public static function get_videos_allowed_mimes() {
			$mime_types = get_allowed_mime_types();
			$return = '';
			foreach ($mime_types as $key => $mime) {
				// Validate video types and replace | by ,
				if (strpos($mime, 'video/') !== false) {
					$return .= str_replace('|', ',', "$key,");
				}
			}
			//Deletes last chr if a ,
			$return = (substr($return, -1) == ",") ? substr($return, 0, -1) : $return;
			/**
			 * $return has all wp defaults video mime types plus added by custom filters in standard ways
			 */
			return apply_filters('get_wpematico_videos_allowed_mimes', $return);
		}

		/**
		 * save_file_from_url 
		 * Try several ways to download a file by url with filters to rename the local file
		 * 
		 * @access public
		 * @param $url_origin String contain the URL of File will be uploaded.
		 * @param $new_file String contain the Path of File where it will be saved.
		 * @return string Path to file if uploaded, bool false if not success
		 * @since 1.9.0
		 */
		public static function save_file_from_url($url_origin, $new_file) {
			/**
			 * Beta: Filter to avoid run these methods by using an external function through the filter.
			 * The function should read the $url_origin and save it as $new_file and return the file path as string.
			 * If return false will continue trying to upload with the following methods below (see $allow_continue)
			 */
			$file_path = apply_filters('wpematico_user_custom_upload', false, $url_origin, $new_file);
			if ($file_path !== false) {
				return $file_path;  // Return path of the already uploaded file
			} else {
				/**
				 * Beta: Continuing prior filter if it returned false.
				 * Description: Allow or avoid continue trying with the below methods.
				 * False to avoid continue and return false as the file was not uploaded.
				 * True to skip this and follow the own custom uploads methods below.
				 */
				$allow_continue = apply_filters('wpematico_user_custom_upload_continue', true, $url_origin, $new_file);
				if (!$allow_continue)
					return false;  // Return 
			}

			/**
			 * Filter to avoid download and return just the new name as it was downloaded.
			 */
			$dest_file = apply_filters('wpematico_overwrite_file', $new_file);
			if ($dest_file === FALSE)
				return $new_file;  // Don't upload it and return the name like it was uploaded
			$new_file = $dest_file;
			$i = 1;
			while (file_exists($new_file)) {
				$file_extension = strrchr($new_file, '.'); //Will return .JPEG
				if ($i == 1) {
					$file_name = substr($new_file, 0, strlen($new_file) - strlen($file_extension));
					$new_file = $file_name . "-$i" . $file_extension;
				} else {
					$file_name = substr($new_file, 0, strlen($new_file) - strlen($file_extension) - strlen("-$i"));
					$new_file = $file_name . "-$i" . $file_extension;
				}
				$i++;
			}

			global $wp_filesystem;
			/* checks if exists $wp_filesystem */
			if (empty($wp_filesystem) || !isset($GLOBALS['wp_filesystem']) || !is_object($GLOBALS['wp_filesystem'])) {

				if (file_exists(ABSPATH . '/wp-admin/includes/file.php')) {
					include_once( ABSPATH . '/wp-admin/includes/file.php' );
				}
				$upload_dir = wp_upload_dir();
				$context = trailingslashit($upload_dir['path']); /* Used by request_filesystem_credentials to verify the folder permissions if it needs credentials. */

				ob_start();
				$creds = request_filesystem_credentials('edit.php?post_type=wpematico', '', false, $context);
				ob_end_clean();

				if ($creds === false) {
					return false;
				}
				$init = WP_Filesystem($creds, $context);
				if (!$init)
					return false;
			}

			$origin_content = '';
			$wrote = false;
			// $wp_filesystem->get_contents in 'direct' method allows url downloads, other methods should work only on local files
			if (defined('FS_METHOD') && FS_METHOD == 'direct') {
				$origin_content = $wp_filesystem->get_contents($url_origin);
			}
			if (empty($origin_content)) {
				// first try if no 'direct' method
				$download_file = download_url($url_origin);  // 300 seconds timeout by default 
				if (!is_wp_error($download_file)) {
					/**
					 * if success we try to move the file instead get and put contents to improve performance.  
					 * (copy and unlink pasted from wp->file.php line 868~ )
					 */
					$move_new_file = @copy($download_file, $new_file);
					if (false === $move_new_file) {
						$origin_content = $wp_filesystem->get_contents($download_file);
					} else {
						//Successfully moved
						$origin_content = '';
						$wrote = true;
					}
					unlink($download_file);
				} else {
					//third try to obtain the file 
					/* translators: the previous error message. */
					trigger_error(sprintf(__('Download error: %s Using an alternate download method...', 'wpematico'), $download_file->get_error_message()), E_USER_WARNING);
					$origin_content = WPeMatico::wpematico_get_contents($url_origin, array());
				}
			}

			if (!empty($origin_content)) {
				$wrote = $wp_filesystem->put_contents($new_file, $origin_content);

				if (!$wrote) {
					unlink($new_file);
				}
			}
			return ($wrote) ? $new_file : false;
		}

		/**
		 * Static function get_attribute_value
		 * @access public
		 * @param string $atribute
		 * @param string $string
		 * @return string $value with value of HTML attribute.
		 * @since 1.7.0
		 */
		public static function get_attribute_value($atribute, $string) {
			$value = '';
			$attribute_patterns = array();
			$attribute_patterns[] = $atribute . '=';
			$attribute_patterns[] = $atribute . ' = ';
			$attribute_patterns[] = $atribute . '= ';
			$attribute_patterns[] = $atribute . ' =';
			$pos_var = false;
			$index_pattern = -1;
			foreach ($attribute_patterns as $kp => $pattern) {
				$pos_var = strpos($string, $pattern);
				$index_pattern = $kp;
				if ($pos_var !== false) {
					break;
				}
			}
			if ($pos_var === false) {
				return $value;
			}
			$len_pattern = strlen($attribute_patterns[$index_pattern]);
			$pos_offset_one = strpos($string, '"', $pos_var + $len_pattern + 1);
			$pos_offset = $pos_offset_one;
			$pos_offset_two = strpos($string, "'", $pos_var + $len_pattern + 1);
			if ($pos_offset_one === false) {
				$pos_offset_one = PHP_INT_MAX;
			}
			if ($pos_offset_two === false) {
				$pos_offset_two = PHP_INT_MAX;
			}

			if ($pos_offset_two < $pos_offset_one) {
				$pos_offset = $pos_offset_two;
			}
			$offset_substr = ($pos_offset - ($pos_var + $len_pattern));
			$value = substr($string, $pos_var + $len_pattern, $offset_substr);
			$value = str_replace('"', '', $value);
			$value = str_replace("'", '', $value);
			return $value;
		}

		/**
		 * Static function get_tags
		 * @access public
		 * @param string $tag
		 * @param string $string
		 * @return array
		 * @since 1.7.1
		 */
		public static function get_tags($tag, $string) {
			$tags_content = array();
			$current_offset = 0;
			do {
				$tag_return = self::get_tag($tag, $string, $current_offset);
				if ($tag_return) {
					$tags_content[] = $tag_return[1];
					$current_offset = $tag_return[0];
				}
			} while ($tag_return !== false);
			return $tags_content;
		}

		/**
		 * Static function get_tag
		 * @access public
		 * @return array|bool
		 * @since 1.7.1
		 */
		public static function get_tag($tag, $string, $offset_start = 0) {
			$value = '';
			$tag_patterns = array();
			$tag_patterns[] = '<' . $tag;
			$tag_patterns[] = '< ' . $tag;
			$pos_var = false;
			$index_pattern = -1;
			foreach ($tag_patterns as $kp => $pattern) {
				$pos_var = strpos($string, $pattern, $offset_start);
				$index_pattern = $kp;
				if ($pos_var !== false) {
					break;
				}
			}
			if ($pos_var === false) {
				return false;
			}
			$tag_end_patterns = array();
			$tag_end_patterns[] = '</' . $tag . '>';
			$tag_end_patterns[] = '</ ' . $tag . '>';
			$tag_end_patterns[] = '/>';
			$tag_end_patterns[] = '/ >';

			$pos_offset_end = false;
			$index_pattern_end = -1;
			$len_pattern = strlen($tag_patterns[$index_pattern]);
			foreach ($tag_end_patterns as $kp => $pattern) {
				$pos_offset_end = strpos($string, $pattern, $pos_var + $len_pattern + 2);
				$index_pattern_end = $kp;
				if ($pos_offset_end !== false) {
					break;
				}
			}

			if ($pos_offset_end === false) {
				return false;
			}

			$value = substr($string, $pos_var, $pos_offset_end);
			return array($pos_offset_end, $value);
		}

		public static function strip_tags_content($text, $tags = '', $invert = FALSE) {

			preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
			$tags = array_unique($tags[1]);

			if (is_array($tags) AND count($tags) > 0) {
				if ($invert == FALSE) {
					return preg_replace('@<(?!(?:' . implode('|', $tags) . ')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
				} else {
					return preg_replace('@<(' . implode('|', $tags) . ')\b.*?>.*?</\1>@si', '', $text);
				}
			} elseif ($invert == FALSE) {
				return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
			}
			return $text;
		}

		public static function wpematico_env_checks() {
			global $wp_version, $user_ID;
			$message = $wpematico_admin_message = '';
			$message = '';
			$checks = true;
			if (!is_admin())
				return false;
			if (version_compare($wp_version, '3.9', '<')) { // check WP Version
				$message .= __('- WordPress 3.9 or higher needed!', 'wpematico') . '<br />';
				$checks = false;
			}
			if (version_compare(phpversion(), '5.3.0', '<')) { // check PHP Version
				$message .= __('- PHP 5.3.0 or higher needed!', 'wpematico') . '<br />';
				$checks = false;
			}
			// Check if PRO version is installed and its required version
			$is_pro_active = wpematico_is_pro_active();
			if ($is_pro_active !== FALSE) {
				$active_plugins = get_option('active_plugins');
				$plpath = trailingslashit(WP_PLUGIN_DIR) . $active_plugins[$is_pro_active];
				$proplugin_data = self::plugin_get_version($plpath);

				$core_version = (property_exists('WPeMaticoPRO', 'wpemshould') ? WPeMaticoPRO::$wpemshould: WPEMATICO_VERSION );

				if ($proplugin_data['Name'] == 'WPeMatico Professional' && version_compare($proplugin_data['Version'], WPeMatico::PROREQUIRED, '<')) {
					$message .= __('Your current version of WPeMatico Professional does not support WPeMatico ', 'wpematico') . $core_version . '<br />';
					/* translators: the required version of WPeMatico Professional. */
					$message .= sprintf(__('Must install at least WPeMatico Professional %s', 'wpematico'), WPeMatico::PROREQUIRED);
					$message .= ' <a href="' . admin_url('plugins.php?page=wpemaddons') . '#wpematico-pro"> ' . __('Go to update Now', 'wpematico') . '</a>';
					$message .= '<script type="text/javascript">jQuery(document).ready(function($){$("#wpematico-pro").css("backgroundColor","yellow");});</script>';
					//Commented to allow access to the settings page
					//$checks=false;
				}
			}

			if (wp_next_scheduled('wpematico_cron') != 0 and wp_next_scheduled('wpematico_cron') > (time() + 360)) {  //check cron jobs work
				$message .= __("- WP-Cron don't working please check it!", 'wpematico') . '<br />';
			}
			//put massage if one
			if (!empty($message))
				$wpematico_admin_message = '<div id="message" class="error fade"><strong>WPeMatico:</strong><br />' . $message . '</div>';

//		$notice = delete_option('wpematico_notices');
			$notice = get_option('wpematico_notices');
			if (!empty($notice)) {
				foreach ($notice as $key => $mess) {
					if ($mess['user_ID'] == $user_ID) {
						$class = ($mess['error']) ? "notice notice-error" : "notice notice-success";
						$class .= ($mess['is-dismissible']) ? " is-dismissible" : "";
						$class .= ($mess['below-h2']) ? " below-h2" : "";
						$wpematico_admin_message .= '<div id="message" class="' . $class . '"><p>' . $mess['text'] . '</p></div>';
						unset($notice[$key]);
					}
				}
				update_option('wpematico_notices', $notice);
			}

			if (!empty($wpematico_admin_message)) {
				//send response to admin notice : ejemplo con la función dentro del add_action
				add_action('admin_notices', function () use ($wpematico_admin_message) {
					//echo '<div class="error"><p>', esc_html($wpematico_admin_message), '</p></div>';
					echo $wpematico_admin_message;
				});
			}
			return $checks;
		}

		/**
		 * get All Statuses without domains
		 * @global type $wp_post_statuses
		 * @param type $statuses
		 * @return type array
		 */
		static function getAllStatuses($statuses = array()) {
			global $wp_post_statuses;
			$statuses = array_filter($wp_post_statuses, function ($object) {
				if ($object->label_count['domain'] == '')
					return true;
			});
			$args = apply_filters('wpematico_statuses_args', array(
//			'_builtin'                  => 1,
				'show_in_admin_status_list' => 1,
				'show_in_admin_all_list' => 1,
			));
			return apply_filters('wpematico_campaign_statuses', wp_filter_object_list($statuses, $args));
		}

		/** add_wp_notice
		 * 
		 * @param  mixed $new_notice 
		 * 	optional   ['user_ID'] to shows the notice default = currentuser
		 * 	optional   ['error'] true or false to define style. Default = false
		 * 	optional   ['is-dismissible'] true or false to hideable. Default = true
		 * 	optional   ['below-h2'] true or false to shows above page Title. Default = true
		 * 	   ['text'] The Text to be displayed. Default = ''
		 * 
		 */
		public static function add_wp_notice($new_notice) {
			if (is_string($new_notice))
				$adm_notice['text'] = $new_notice;
			else
				$adm_notice['text'] = (!isset($new_notice['text'])) ? '' : $new_notice['text'];
			$adm_notice['error'] = (!isset($new_notice['error'])) ? false : $new_notice['error'];
			$adm_notice['below-h2'] = (!isset($new_notice['below-h2'])) ? true : $new_notice['below-h2'];
			$adm_notice['is-dismissible'] = (!isset($new_notice['is-dismissible'])) ? true : $new_notice['is-dismissible'];
			$adm_notice['user_ID'] = (!isset($new_notice['user_ID'])) ? get_current_user_id() : $new_notice['user_ID'];

			$notice = get_option('wpematico_notices');
			$notice[] = $adm_notice;
			update_option('wpematico_notices', $notice);
		}

		//file size
		public static function formatBytes($bytes, $precision = 2) {
			$units = array('B', 'KB', 'MB', 'GB', 'TB');
			$bytes = max($bytes, 0);
			$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
			$pow = min($pow, count($units) - 1);
			$bytes /= pow(1024, $pow);
			return round($bytes, $precision) . ' ' . $units[$pow];
		}

		//************************* CARGA CAMPAÑASS *******************************************************

		/**
		 * Load all campaigns data
		 * 
		 * @return array with all campaigns data 
		 * */
		public static function get_campaigns() {
			$campaigns_data = array();
			$args = array(
				'orderby' => 'ID',
				'order' => 'ASC',
				'post_type' => 'wpematico',
				'numberposts' => -1
			);
			$campaigns = get_posts($args);
			foreach ($campaigns as $post):
				$campaigns_data[] = self::get_campaign($post->ID);
			endforeach;
			return $campaigns_data;
		}

		//************************* CARGA CAMPAÑA *******************************************************

		/**
		 * Load campaign data
		 * Required @param   integer  $post_id    Campaign ID to load
		 * @param   boolean  $getfromdb  if set to true run get_post($post_ID) and retuirn object post
		 * 
		 * @return array with campaign data 
		 * */
		public static function get_campaign($post_id, $getfromdb = false) {
			if ($getfromdb) {
				$campaign = get_post($post_id);
			}
			$campaign_data = get_post_meta($post_id, 'campaign_data');
			$campaign_data = ( isset($campaign_data[0]) ) ? $campaign_data[0] : array(0);
			/**
			 * wpematico_check_campaigndata Filter to sanitize and strip all fields 
			 */
			$campaign_data = apply_filters('wpematico_check_campaigndata', $campaign_data);
			return $campaign_data;
		}

		//************************* Check campaign data *************************************

		/**
		 * Check campaign data
		 * Required @param $campaigndata array with campaign data values
		 * 
		 * @return array with campaign data fixed all empty values
		 * */
		/*		 * ************ CHECK DATA ************************************************ */
		public static function check_campaigndata($post_data) {
			global $post, $cfg;
			if (is_null($cfg))
				$cfg = get_option(WPeMatico::OPTION_KEY);

			$campaigndata = array();
			if (isset($post_data['ID']) && !empty($post_data['ID'])) {
				$campaigndata['ID'] = (int) $post_data['ID'];
			} elseif (isset($post_data['campaign_id']) && !empty($post_data['campaign_id'])) {
				$campaigndata['ID'] = (int) $post_data['campaign_id'];
			} elseif (isset($post->ID) && $post->ID > 0) {
				$campaigndata['ID'] = $post->ID;
			} else {
				$campaigndata['ID'] = 0;
			}


			//$campaigndata['campaign_id'] = $post_id;
			$campaigndata['campaign_title'] = (isset($post_data['campaign_title']) && !empty($post_data['campaign_title']) ) ? sanitize_text_field($post_data['campaign_title']) : get_the_title($campaigndata['ID']);

			$campaigndata['campaign_type'] = (!isset($post_data['campaign_type']) ) ? 'feed' : sanitize_text_field($post_data['campaign_type']);

			$campaigndata['campaign_posttype'] = (!isset($post_data['campaign_posttype']) ) ? 'publish' : sanitize_text_field($post_data['campaign_posttype']);
			$campaigndata['campaign_customposttype'] = (!isset($post_data['campaign_customposttype']) ) ? 'post' : sanitize_text_field($post_data['campaign_customposttype']);
			$arrTaxonomies = get_object_taxonomies($campaigndata['campaign_customposttype']);
			if (in_array('post_format', $arrTaxonomies)) {
				$campaigndata['campaign_post_format'] = (!isset($post_data['campaign_post_format']) ) ? '0' : sanitize_text_field($post_data['campaign_post_format']);
			} else {
				$campaigndata['campaign_post_format'] = '0';
			}
			$campaigndata['activated'] = (!isset($post_data['activated']) || empty($post_data['activated'])) ? false : ( ($post_data['activated'] == 1) ? true : false );

			$campaigndata['campaign_feed_order_date'] = (!isset($post_data['campaign_feed_order_date']) || empty($post_data['campaign_feed_order_date'])) ? false : ( ($post_data['campaign_feed_order_date'] == 1) ? true : false );
			$campaigndata['campaign_feeddate'] = (!isset($post_data['campaign_feeddate']) || empty($post_data['campaign_feeddate'])) ? false : ( ($post_data['campaign_feeddate'] == 1) ? true : false );
			$campaigndata['campaign_feeddate_forced'] = (!isset($post_data['campaign_feeddate_forced']) || empty($post_data['campaign_feeddate_forced'])) ? false : ( ($post_data['campaign_feeddate_forced'] == 1) ? true : false );

			$campaign_feeds = array();
			$all_feeds = ( isset($post_data['campaign_feeds']) && !empty($post_data['campaign_feeds']) ) ? $post_data['campaign_feeds'] : Array();

			if (!empty($all_feeds) && is_array($all_feeds)) {  // Proceso los feeds sacando los que estan en blanco
				foreach ($all_feeds as $id => $feedname) {
					if (!empty($feedname))
						$campaign_feeds[] = $feedname;
				}
			}
			$campaigndata['campaign_feeds'] = (array) $campaign_feeds;

			$campaigndata['cron'] = (!isset($post_data['cronminutes']) ) ? ( (!isset($post_data['cron']) ) ? '0 3 * * *' : $post_data['cron'] ) : WPeMatico :: cron_string($post_data);

			$campaigndata['cronnextrun'] = (isset($post_data['cronnextrun']) && !empty($post_data['cronnextrun']) ) ? (int) $post_data['cronnextrun'] : (int) WPeMatico :: time_cron_next($campaigndata['cron']);

			// Email address to send campaign logs.
			$campaigndata['mailerroronly'] = (!isset($post_data['mailerroronly']) || empty($post_data['mailerroronly'])) ? false : ( ($post_data['mailerroronly'] == 1) ? true : false );
			$campaigndata['mailaddresslog'] = (!isset($post_data['mailaddresslog']) ) ? '' : sanitize_email($post_data['mailaddresslog']);

			// *** Campaign Options
			$campaigndata['campaign_max'] = (!isset($post_data['campaign_max']) ) ? 5 : (int) $post_data['campaign_max'];
			$campaigndata['campaign_author'] = (!isset($post_data['campaign_author']) ) ? 0 : (int) $post_data['campaign_author'];
			$campaigndata['campaign_linktosource'] = (!isset($post_data['campaign_linktosource']) || empty($post_data['campaign_linktosource'])) ? false : ( ($post_data['campaign_linktosource'] == 1) ? true : false );

			if (!isset($post_data['copy_permanlink_source']) || empty($post_data['copy_permanlink_source'])) {
				$campaigndata['copy_permanlink_source'] = false;
			} else {
				if ($post_data['copy_permanlink_source'] == 1) {
					$campaigndata['copy_permanlink_source'] = ($post_data['campaign_type'] != 'youtube') ? true : false;
				} else {
					$campaigndata['copy_permanlink_source'] = false;
				}
			}

			$campaigndata['avoid_search_redirection'] = (!isset($post_data['avoid_search_redirection']) || empty($post_data['avoid_search_redirection'])) ? false : ( ($post_data['avoid_search_redirection'] == 1) ? true : false );

			$campaigndata['campaign_strip_links'] = (!isset($post_data['campaign_strip_links']) || empty($post_data['campaign_strip_links'])) ? false : ( ($post_data['campaign_strip_links'] == 1) ? true : false );
			$campaigndata['campaign_strip_links_options'] = (!isset($post_data['campaign_strip_links_options']) || !is_array($post_data['campaign_strip_links_options'])) ? array('a' => true, 'strip_domain' => false, 'script' => true, 'iframe' => true) : $post_data['campaign_strip_links_options'];
			
			$campaigndata['campaign_strip_links_options']['a'] = (!isset($post_data['campaign_strip_links_options']['a']) || empty($post_data['campaign_strip_links_options']['a'])) ? false : ( ($post_data['campaign_strip_links_options']['a']) ? true : false );

			$campaigndata['campaign_strip_links_options']['strip_domain'] = (!isset($post_data['campaign_strip_links_options']['strip_domain']) || empty($post_data['campaign_strip_links_options']['strip_domain'])) ? false : ( ($post_data['campaign_strip_links_options']['strip_domain']) ? true : false );

			$campaigndata['campaign_strip_links_options']['script'] = (!isset($post_data['campaign_strip_links_options']['script']) || empty($post_data['campaign_strip_links_options']['script'])) ? false : ( ($post_data['campaign_strip_links_options']['script']) ? true : false );
			$campaigndata['campaign_strip_links_options']['iframe'] = (!isset($post_data['campaign_strip_links_options']['iframe']) || empty($post_data['campaign_strip_links_options']['iframe'])) ? false : ( ($post_data['campaign_strip_links_options']['iframe']) ? true : false );

			$campaigndata['campaign_commentstatus'] = (!isset($post_data['campaign_commentstatus']) ) ? 'closed' : sanitize_text_field($post_data['campaign_commentstatus']);
			$campaigndata['campaign_allowpings'] = (!isset($post_data['campaign_allowpings']) || empty($post_data['campaign_allowpings'])) ? false : ( ($post_data['campaign_allowpings'] == 1) ? true : false );
			$campaigndata['campaign_woutfilter'] = (!isset($post_data['campaign_woutfilter']) || empty($post_data['campaign_woutfilter'])) ? false : ( ($post_data['campaign_woutfilter'] == 1) ? true : false );
			$campaigndata['campaign_striphtml'] = (!isset($post_data['campaign_striphtml']) || empty($post_data['campaign_striphtml'])) ? false : ( ($post_data['campaign_striphtml'] == 1) ? true : false );
			$campaigndata['campaign_get_excerpt'] = (!isset($post_data['campaign_get_excerpt']) || empty($post_data['campaign_get_excerpt'])) ? false : ( ($post_data['campaign_get_excerpt'] == 1) ? true : false );

			$campaigndata['campaign_enable_convert_utf8'] = (!isset($post_data['campaign_enable_convert_utf8']) || empty($post_data['campaign_enable_convert_utf8'])) ? false : ( ($post_data['campaign_enable_convert_utf8'] == 1) ? true : false );
			// *** Campaign Audios
			$campaigndata['campaign_no_setting_audio'] = (!isset($post_data['campaign_no_setting_audio']) || empty($post_data['campaign_no_setting_audio'])) ? false : ( ($post_data['campaign_no_setting_audio'] == 1) ? true : false );
			$campaigndata['campaign_audio_cache'] = (!isset($post_data['campaign_audio_cache']) || empty($post_data['campaign_audio_cache'])) ? false : ( ($post_data['campaign_audio_cache'] == 1) ? true : false );
			$campaigndata['campaign_attach_audio'] = (!isset($post_data['campaign_attach_audio']) || empty($post_data['campaign_attach_audio'])) ? false : ( ($post_data['campaign_attach_audio'] == 1) ? true : false );
			$campaigndata['campaign_nolink_audio'] = (!isset($post_data['campaign_nolink_audio']) || empty($post_data['campaign_nolink_audio'])) ? false : ( ($post_data['campaign_nolink_audio'] == 1) ? true : false );
			$campaigndata['campaign_customupload_audio'] = (!isset($post_data['campaign_customupload_audio']) || empty($post_data['campaign_customupload_audio'])) ? false : ( ($post_data['campaign_customupload_audio'] == 1) ? true : false );
			if (!$campaigndata['campaign_audio_cache']) {
				$campaigndata['campaign_attach_audio'] = false;
				$campaigndata['campaign_nolink_audio'] = false;
				$campaigndata['campaign_customupload_audio'] = false;
			}

			// *** Campaign Videos
			$campaigndata['campaign_no_setting_video'] = (!isset($post_data['campaign_no_setting_video']) || empty($post_data['campaign_no_setting_video'])) ? false : ( ($post_data['campaign_no_setting_video'] == 1) ? true : false );
			$campaigndata['campaign_video_cache'] = (!isset($post_data['campaign_video_cache']) || empty($post_data['campaign_video_cache'])) ? false : ( ($post_data['campaign_video_cache'] == 1) ? true : false );
			$campaigndata['campaign_attach_video'] = (!isset($post_data['campaign_attach_video']) || empty($post_data['campaign_attach_video'])) ? false : ( ($post_data['campaign_attach_video'] == 1) ? true : false );
			$campaigndata['campaign_nolink_video'] = (!isset($post_data['campaign_nolink_video']) || empty($post_data['campaign_nolink_video'])) ? false : ( ($post_data['campaign_nolink_video'] == 1) ? true : false );
			$campaigndata['campaign_customupload_video'] = (!isset($post_data['campaign_customupload_video']) || empty($post_data['campaign_customupload_video'])) ? false : ( ($post_data['campaign_customupload_video'] == 1) ? true : false );
			if (!$campaigndata['campaign_video_cache']) {
				$campaigndata['campaign_attach_video'] = false;
				$campaigndata['campaign_nolink_video'] = false;
				$campaigndata['campaign_customupload_video'] = false;
			}

			// *** Campaign Images
			$campaigndata['campaign_no_setting_img'] = (!isset($post_data['campaign_no_setting_img']) || empty($post_data['campaign_no_setting_img'])) ? false : ( ($post_data['campaign_no_setting_img'] == 1) ? true : false );
			$campaigndata['campaign_imgcache'] = (!isset($post_data['campaign_imgcache']) || empty($post_data['campaign_imgcache'])) ? false : ( ($post_data['campaign_imgcache'] == 1) ? true : false );
			$campaigndata['campaign_attach_img'] = (!isset($post_data['campaign_attach_img']) || empty($post_data['campaign_attach_img'])) ? false : ( ($post_data['campaign_attach_img'] == 1) ? true : false );
			$campaigndata['campaign_nolinkimg'] = (!isset($post_data['campaign_nolinkimg']) || empty($post_data['campaign_nolinkimg'])) ? false : ( ($post_data['campaign_nolinkimg'] == 1) ? true : false );
			$campaigndata['campaign_image_srcset'] = (!isset($post_data['campaign_image_srcset']) || empty($post_data['campaign_image_srcset'])) ? false : ( ($post_data['campaign_image_srcset'] == 1) ? true : false );

			$campaigndata['campaign_featuredimg'] = (!isset($post_data['campaign_featuredimg']) || empty($post_data['campaign_featuredimg'])) ? false : ( ($post_data['campaign_featuredimg'] == 1) ? true : false );
			$campaigndata['campaign_fifu'] = (!isset($post_data['campaign_fifu']) || empty($post_data['campaign_fifu'])) ? false : ( ($post_data['campaign_fifu'] == 1) ? true : false );

			$campaigndata['campaign_fifu_video'] = (!isset($post_data['campaign_fifu_video']) || empty($post_data['campaign_fifu_video'])) ? false : ( ($post_data['campaign_fifu_video'] == 1) ? true : false );


			$campaigndata['campaign_attr_images'] = (!isset($post_data['campaign_attr_images']) || empty($post_data['campaign_attr_images'])) ? false : ( ($post_data['campaign_attr_images'] == 1) ? true : false );

			$campaigndata['campaign_enable_featured_image_selector'] = (!isset($post_data['campaign_enable_featured_image_selector']) || empty($post_data['campaign_enable_featured_image_selector'])) ? false : ( ($post_data['campaign_enable_featured_image_selector'] == 1) ? true : false );
			$campaigndata['campaign_featured_selector_index'] = (!isset($post_data['campaign_featured_selector_index']) || empty($post_data['campaign_featured_selector_index'])) ? '0' : (int) $post_data['campaign_featured_selector_index'];
			$campaigndata['campaign_featured_selector_ifno'] = (!isset($post_data['campaign_featured_selector_ifno']) || empty($post_data['campaign_featured_selector_ifno'])) ? 'first' : sanitize_text_field($post_data['campaign_featured_selector_ifno']);

			$campaigndata['campaign_rmfeaturedimg'] = (!isset($post_data['campaign_rmfeaturedimg']) || empty($post_data['campaign_rmfeaturedimg'])) ? false : ( ($post_data['campaign_rmfeaturedimg'] == 1) ? true : false );
			$campaigndata['campaign_customupload'] = (!isset($post_data['campaign_customupload']) || empty($post_data['campaign_customupload'])) ? false : ( ($post_data['campaign_customupload'] == 1) ? true : false );

			if (!$campaigndata['campaign_imgcache']) {
				$campaigndata['campaign_attach_img'] = false;
				$campaigndata['campaign_nolinkimg'] = false;
				if (!$campaigndata['campaign_featuredimg']) {
					$campaigndata['campaign_customupload'] = false;
				}
			}
			// *** Campaign Template
			$campaigndata['campaign_enable_template'] = (!isset($post_data['campaign_enable_template']) || empty($post_data['campaign_enable_template'])) ? false : ( ($post_data['campaign_enable_template'] == 1) ? true : false );
			if (isset($post_data['campaign_template']))
				$campaigndata['campaign_template'] = $post_data['campaign_template'];
			else {
				$campaigndata['campaign_enable_template'] = false;
				$campaigndata['campaign_template'] = '';
			}

			// *** Processed posts count
			$campaigndata['postscount'] = (!isset($post_data['postscount']) ) ? 0 : (int) $post_data['postscount'];
			$campaigndata['lastpostscount'] = (!isset($post_data['lastpostscount']) ) ? 0 : (int) $post_data['lastpostscount'];
			$campaigndata['lastrun'] = (!isset($post_data['lastrun']) ) ? 0 : (int) $post_data['lastrun'];
			$campaigndata['lastruntime'] = (!isset($post_data['lastruntime']) ) ? 0 : $post_data['lastruntime'];  // can be string

			$campaigndata['starttime'] = (!isset($post_data['starttime']) ) ? 0 : (int) $post_data['starttime'];

			//campaign_categories & tags		
			if (in_array('post_tag', $arrTaxonomies)) {
				$campaigndata['campaign_tags'] = (!isset($post_data['campaign_tags']) ) ? '' : sanitize_text_field($post_data['campaign_tags']);
			} else {
				$campaigndata['campaign_tags'] = '';
			}

			$campaigndata['campaign_autocats'] = (!isset($post_data['campaign_autocats']) || empty($post_data['campaign_autocats'])) ? false : ( ($post_data['campaign_autocats'] == 1) ? true : false );

			$campaigndata['campaign_category_limit'] = (!isset($post_data['campaign_category_limit']) || empty($post_data['campaign_category_limit'])) ? false : ( ($post_data['campaign_category_limit'] == 1) ? true : false );

			$campaigndata['max_categories'] = (!isset($post_data['max_categories']) || empty($post_data['max_categories'])) ? 5 : (int) $post_data['max_categories'];

			$campaigndata['campaign_parent_autocats'] = (!isset($post_data['campaign_parent_autocats']) || empty($post_data['campaign_parent_autocats'])) ? -1 : (int) $post_data['campaign_parent_autocats'];

			// Process the new categories if any and add them to the end of the array
			# New categories
			if (isset($post_data['campaign_newcat'])) {
				foreach ($post_data['campaign_newcat'] as $k => $on) {
					$catname = $post_data['campaign_newcatname'][$k];
					if (!empty($catname)) {
						$catname = sanitize_text_field($catname);
						$arg_description = apply_filters('wpematico_addcat_description', __('Category Added in a WPeMatico Campaign', 'wpematico'), $catname);
						if (isset($cfg['disable_categories_description']) && $cfg['disable_categories_description']) {
							$arg_description = '';
						}

						$arg = array('description' => $arg_description, 'parent' => "0");
						$newcat = wp_insert_term($catname, "category", $arg);
						$post_data['post_category'][] = (is_array($newcat)) ? $newcat['term_id'] : $newcat;
					}
				}
			}
			# All: Las elegidas + las nuevas ya agregadas
			if (in_array('category', $arrTaxonomies)) {
				$campaigndata['campaign_categories'] = (!isset($post_data['post_category']) ) ? ( (!isset($post_data['campaign_categories']) ) ? array() : (array) $post_data['campaign_categories'] ) : (array) $post_data['post_category'];
			} else {
				$campaigndata['campaign_categories'] = array();
			}

			# Order Words to Category and strip the blank fields
			//campaign_wrd2cat, campaign_wrd2cat_regex, campaign_wrd2cat_category
			$campaign_wrd2cat = Array();
			if (isset($post_data['campaign_wrd2cat']['word'])) {
				//for ($i = 0; $i <= count(@$campaign_wrd2cat['word']); $i++) {
				foreach ($post_data['campaign_wrd2cat']['word'] as $id => $value) {
					//$word = ( isset($post_data['_wp_http_referer']) ) ? addslashes($post_data['campaign_wrd2cat']['word'][$id]): $post_data['campaign_wrd2cat']['word'][$id];
					$word = ($post_data['campaign_wrd2cat']['word'][$id]);
					$title = (isset($post_data['campaign_wrd2cat']['title'][$id]) && $post_data['campaign_wrd2cat']['title'][$id] == 1) ? true : false;
					$regex = (isset($post_data['campaign_wrd2cat']['regex'][$id]) && $post_data['campaign_wrd2cat']['regex'][$id] == 1) ? true : false;
					$cases = (isset($post_data['campaign_wrd2cat']['cases'][$id]) && $post_data['campaign_wrd2cat']['cases'][$id] == 1) ? true : false;
					$w2ccateg = (isset($post_data['campaign_wrd2cat']['w2ccateg'][$id]) && !empty($post_data['campaign_wrd2cat']['w2ccateg'][$id]) ) ? $post_data['campaign_wrd2cat']['w2ccateg'][$id] : '';
					if (!empty($word)) {
						$campaign_wrd2cat['word'][] = ($regex) ? $word : sanitize_text_field($word);
						$campaign_wrd2cat['title'][] = $title;
						$campaign_wrd2cat['regex'][] = $regex;
						$campaign_wrd2cat['cases'][] = $cases;
						$campaign_wrd2cat['w2ccateg'][] = sanitize_text_field($w2ccateg);
					}
				}
			}
			$_wrd2cat = array('word' => array(''), 'title' => array(false), 'regex' => array(false), 'w2ccateg' => array(0), 'cases' => array(false));
			$campaigndata['campaign_wrd2cat'] = (!empty($campaign_wrd2cat) ) ? (array) $campaign_wrd2cat : (array) $_wrd2cat;

			$campaigndata['campaign_w2c_only_use_a_category'] = (!isset($post_data['campaign_w2c_only_use_a_category']) || empty($post_data['campaign_w2c_only_use_a_category'])) ? false : ( ($post_data['campaign_w2c_only_use_a_category'] == 1) ? true : false );
			$campaigndata['campaign_w2c_the_category_most_used'] = (!isset($post_data['campaign_w2c_the_category_most_used']) || empty($post_data['campaign_w2c_the_category_most_used'])) ? false : ( ($post_data['campaign_w2c_the_category_most_used'] == 1) ? true : false );

			// *** Campaign Rewrites	
			// Proceso los rewrites sacando los que estan en blanco
//		$campaign_rewrites = Array();
			$campaign_rewrites = ( isset($post_data['campaign_rewrites']) && !empty($post_data['campaign_rewrites']) ) ? $post_data['campaign_rewrites'] : Array();
			if (isset($post_data['campaign_word_origin']) && is_array($post_data['campaign_word_origin'])) {

				foreach ($post_data['campaign_word_origin'] as $id => $rewrite) {
					$origin = wp_check_invalid_utf8($post_data['campaign_word_origin'][$id]);
					$regex = (isset($post_data['campaign_word_option_regex'][$id]) && $post_data['campaign_word_option_regex'][$id] == 1) ? true : false;
					$title = (isset($post_data['campaign_word_option_title'][$id]) && $post_data['campaign_word_option_title'][$id] == 1) ? true : false;

					$rewrite = wp_check_invalid_utf8($post_data['campaign_word_rewrite'][$id]);
					$relink = wp_check_invalid_utf8($post_data['campaign_word_relink'][$id]);
					if (!empty($origin)) {
						$campaign_rewrites['origin'][] = $origin;
						$campaign_rewrites['regex'][] = $regex;
						$campaign_rewrites['title'][] = $title;
						$campaign_rewrites['rewrite'][] = $rewrite;
						$campaign_rewrites['relink'][] = $relink;
					}
				}
			}
			$campaigndata['campaign_rewrites'] = !empty($campaign_rewrites) ? (array) $campaign_rewrites : array('origin' => array(''), 'title' => array(false), 'regex' => array(false), 'rewrite' => array(''), 'relink' => array(''));

			$campaigndata['campaign_youtube_embed'] = (!isset($post_data['campaign_youtube_embed']) || empty($post_data['campaign_youtube_embed'])) ? false : ( ($post_data['campaign_youtube_embed'] == 1) ? true : false );
			$campaigndata['campaign_youtube_sizes'] = (!isset($post_data['campaign_youtube_sizes']) || empty($post_data['campaign_youtube_sizes'])) ? false : ( ($post_data['campaign_youtube_sizes'] == 1) ? true : false );
			$campaigndata['campaign_youtube_width'] = (!isset($post_data['campaign_youtube_width']) ) ? 0 : (int) $post_data['campaign_youtube_width'];
			$campaigndata['campaign_youtube_height'] = (!isset($post_data['campaign_youtube_height']) ) ? 0 : (int) $post_data['campaign_youtube_height'];

			$campaigndata['campaign_youtube_ign_image'] = (!isset($post_data['campaign_youtube_ign_image']) || empty($post_data['campaign_youtube_ign_image'])) ? false : ( ($post_data['campaign_youtube_ign_image'] == 1) ? true : false );
			$campaigndata['campaign_youtube_image_only_featured'] = (!isset($post_data['campaign_youtube_image_only_featured']) || empty($post_data['campaign_youtube_image_only_featured'])) ? false : ( ($post_data['campaign_youtube_image_only_featured'] == 1) ? true : false );

			$campaigndata['campaign_youtube_ign_description'] = (!isset($post_data['campaign_youtube_ign_description']) || empty($post_data['campaign_youtube_ign_description'])) ? false : ( ($post_data['campaign_youtube_ign_description'] == 1) ? true : false );

			$campaigndata['campaign_youtube_only_shorts'] = (!isset($post_data['campaign_youtube_only_shorts']) || empty($post_data['campaign_youtube_only_shorts'])) ? false : ( ($post_data['campaign_youtube_only_shorts'] == 1) ? true : false );

			$campaigndata['campaign_youtube_ign_shorts'] = (!isset($post_data['campaign_youtube_ign_shorts']) || empty($post_data['campaign_youtube_ign_shorts'])) ? false : ( ($post_data['campaign_youtube_ign_shorts'] == 1) ? true : false );

			$campaigndata['campaign_no_setting_duplicate'] = (!isset($post_data['campaign_no_setting_duplicate']) || empty($post_data['campaign_no_setting_duplicate'])) ? false : ( ($post_data['campaign_no_setting_duplicate'] == 1) ? true : false );
			$campaigndata['campaign_allowduplicates'] = (!isset($post_data['campaign_allowduplicates']) || empty($post_data['campaign_allowduplicates'])) ? false : ( ($post_data['campaign_allowduplicates'] == 1) ? true : false );
			$campaigndata['campaign_allowduptitle'] = (!isset($post_data['campaign_allowduptitle']) || empty($post_data['campaign_allowduptitle'])) ? false : ( ($post_data['campaign_allowduptitle'] == 1) ? true : false );
			$campaigndata['campaign_allowduphash'] = (!isset($post_data['campaign_allowduphash']) || empty($post_data['campaign_allowduphash'])) ? false : ( ($post_data['campaign_allowduphash'] == 1) ? true : false );
			$campaigndata['campaign_add_ext_duplicate_filter_ms'] = (!isset($post_data['campaign_add_ext_duplicate_filter_ms']) || empty($post_data['campaign_add_ext_duplicate_filter_ms'])) ? false : ( ($post_data['campaign_add_ext_duplicate_filter_ms'] == 1) ? true : false );
			$campaigndata['campaign_jumpduplicates'] = (!isset($post_data['campaign_jumpduplicates']) || empty($post_data['campaign_jumpduplicates'])) ? false : ( ($post_data['campaign_jumpduplicates'] == 1) ? true : false );

			$campaigndata['campaign_bbpress_forum'] = (!isset($post_data['campaign_bbpress_forum']) || empty($post_data['campaign_bbpress_forum'])) ? 0 : (int) $post_data['campaign_bbpress_forum'];
			$campaigndata['campaign_bbpress_topic'] = (!isset($post_data['campaign_bbpress_topic']) || empty($post_data['campaign_bbpress_topic'])) ? 0 : (int) $post_data['campaign_bbpress_topic'];

			$campaigndata['campaign_xml_feed_url'] = (isset($post_data['campaign_xml_feed_url']) && !empty($post_data['campaign_xml_feed_url']) ) ? esc_url_raw($post_data['campaign_xml_feed_url']) : '';
			$campaigndata['campaign_xml_node'] = (isset($post_data['campaign_xml_node']) && !empty($post_data['campaign_xml_node']) ) ? (array) $post_data['campaign_xml_node'] : array();
			$campaigndata['campaign_xml_node_parent'] = (isset($post_data['campaign_xml_node_parent']) && !empty($post_data['campaign_xml_node_parent']) ) ? (array) $post_data['campaign_xml_node_parent'] : array();
			if ($campaigndata['campaign_type'] == 'xml') {
				$campaigndata['campaign_feeds'] = array();
				$campaigndata['campaign_feeds'][] = site_url('/wpematico-xml-feed/');
			}

			if (has_filter('pro_check_campaigndata'))
				$campaigndata = apply_filters('pro_check_campaigndata', $campaigndata, $post_data);
			return $campaigndata;
		}

		//************************* GUARDA CAMPAÑA *******************************************************

		/**
		 * Save campaign data 
		 * Each call calculate the next cron time and save it on the campaign cron field.
		 * Some values for direct access or list columns are saved also individually.
		 *  
		 * Required @param   integer  $post_id    Campaign ID to save on.
		 *			@param   array  $campaign	All the campaign data to save.
		 * 
		 * @return int|bool with campaign data 
		 * */
		public static function update_campaign($post_id, $campaign = array()) {
			$campaign['cronnextrun'] = (int) WPeMatico :: time_cron_next($campaign['cron']);
			$campaign = apply_filters('wpematico_before_update_campaign', $campaign);

			update_post_meta($post_id, 'postscount', $campaign['postscount']);
	
			update_post_meta($post_id, 'cronnextrun', $campaign['cronnextrun']);

			update_post_meta($post_id, 'lastrun', $campaign['lastrun']);

			// *** Campaign Rewrites	
			// Proceso los rewrites agrego slashes	
			if (isset($campaign['campaign_rewrites']['origin']))
				for ($i = 0; $i < count($campaign['campaign_rewrites']['origin']); $i++) {
					$campaign['campaign_rewrites']['origin'][$i] = addslashes($campaign['campaign_rewrites']['origin'][$i]);
					$campaign['campaign_rewrites']['rewrite'][$i] = addslashes($campaign['campaign_rewrites']['rewrite'][$i]);
					$campaign['campaign_rewrites']['relink'][$i] = addslashes($campaign['campaign_rewrites']['relink'][$i]);
				}
			if (isset($campaign['campaign_wrd2cat']['word']))
				for ($i = 0; $i < count($campaign['campaign_wrd2cat']['word']); $i++) {
					$campaign['campaign_wrd2cat']['word'][$i] = addslashes($campaign['campaign_wrd2cat']['word'][$i]);
				}
			
			return update_post_meta($post_id, 'campaign_data', $campaign);
		}

		/*		 * ********* 	 Funciones para procesar campañas ***************** */

		//DoJob
		public static function wpematico_dojob($jobid) {
			global $campaign_log_message;
			$campaign_log_message = "";
			if (empty($jobid))
				return false;
			require_once(dirname(__FILE__) . '/campaign_fetch.php');
			$fetched = new wpematico_campaign_fetch($jobid);
			unset($fetched);
			return $campaign_log_message;
		}

		// Processes all campaigns
		public static function processAll() {
			$args = array('post_type' => 'wpematico', 'orderby' => 'ID', 'order' => 'ASC');
			$campaignsid = get_posts($args);
			$msglogs = "";
			foreach ($campaignsid as $campaignid) {
			    wpematico_init_set('max_execution_time', 0);
				
				$msglogs .= WPeMatico :: wpematico_dojob($campaignid->ID);
			}
			return $msglogs;
		}

		//Permalink to Source
		/*		 * * Determines what the title has to link to   * @return string new text   * */
		public static function wpematico_permalink($url) {
			// if from admin panel
			$post_id = url_to_postid($url);
			if ($post_id) {
				$campaign_id = (int) get_post_meta($post_id, 'wpe_campaignid', true);
				if ($campaign_id) {
					$campaign = self::get_campaign($campaign_id);
					if (isset($campaign['campaign_linktosource']) && $campaign['campaign_linktosource'])
						return get_post_meta($post_id, 'wpe_sourcepermalink', true);
				}
			}
			return $url;
		}

		/**
		 * Set canonical url for the post
		 *
		 * @param   string    $canonical_url          canonical url to integrate in the <head> tag
		 * @param   string    $wpe_sourcepermalink    url to integrate in the post
		 * @param   WP_Post   $post                   wpematico's post 
		 * @return  string    Canonical URL
		 * @since 2.7
		 * */

		public static function wpematico_set_canonical($canonical_url, $post){
			global $cfg;
			
			$prev = $canonical_url;

			if (isset($cfg['wpematico_set_canonical']) && $cfg['wpematico_set_canonical']) {
				$wpe_sourcepermalink = get_post_meta($post->ID, 'wpe_sourcepermalink', true);
				$canonical_url = isset($wpe_sourcepermalink) ? $wpe_sourcepermalink : $canonical_url;
			}
			
			return apply_filters('wpematico_canonical_url', $canonical_url, $prev, $post);
		}

//*********************************************************************************************************

		/**
		 * Parses a feed with SimplePie
		 *
		 * @param   boolean     $stupidly_fast    Set fast mode. Best for checks
		 * @param   integer     $max              Limit of items to fetch
		 * @return  SimplePie_Item    Feed object
		 * */
		public static function fetchFeed($args, $stupidly_fast = false, $max = 0, $order_by_date = false, $force_feed = false) {  # SimplePie
			/**
			 * Allow send args from a single var $args easier to filter.
			 * @since 1.8.0
			 */
			if (is_array($args) && isset($args['url'])) {
				extract($args);
			} else {
				$url = $args;
			}

			if (!isset($disable_simplepie_notice)) {
				$disable_simplepie_notice = false;
			}

			$cfg = get_option(WPeMatico :: OPTION_KEY);
			
			if (!class_exists('SimplePie')) {
				if (is_file(ABSPATH . WPINC . '/class-simplepie.php'))
					include_once( ABSPATH . WPINC . '/class-simplepie.php' );
				else if (is_file(ABSPATH . 'wp-admin/includes/class-simplepie.php'))
					include_once( ABSPATH . 'wp-admin/includes/class-simplepie.php' );
			}
			$feed = new SimplePie();
			$feed->timeout = apply_filters('wpe_simplepie_timeout', 130);
			$feed->enable_order_by_date($order_by_date);
			$feed->force_feed($force_feed);
			$user_agent = 'WPeMatico ' . (defined('SIMPLEPIE_NAME') ? SIMPLEPIE_NAME : '') . '/' . (defined('SIMPLEPIE_VERSION') ? SIMPLEPIE_VERSION : '') . ' (Feed Parser; ' . (defined('SIMPLEPIE_URL') ? SIMPLEPIE_URL : '') . '; Allow like Gecko) Build/' . (defined('SIMPLEPIE_BUILD') ? SIMPLEPIE_BUILD : '');
			$user_agent = apply_filters('wpematico_simplepie_user_agent', $user_agent, $url);
			$feed->set_useragent($user_agent);
			$feed->set_feed_url($url);
//			$feed->feed_url								 = rawurldecode($feed->feed_url);
			$feed->curl_options[CURLOPT_SSL_VERIFYHOST] = false;
			$feed->curl_options[CURLOPT_SSL_VERIFYPEER] = false;

			$feed->set_item_limit($max);
			$feed->set_stupidly_fast($stupidly_fast);
			if (!$stupidly_fast) {
				if ($cfg['simplepie_strip_htmltags']) {
					$strip_htmltags = sanitize_text_field($cfg['strip_htmltags']);
					$strip_htmltags = (isset($strip_htmltags) && empty($strip_htmltags) ) ? $strip_htmltags = array() : explode(',', $strip_htmltags);
					$strip_htmltags = array_map('trim', $strip_htmltags);
					$feed->strip_htmltags($strip_htmltags);
					$feed->strip_htmltags = $strip_htmltags;
				}
				if ($cfg['simplepie_strip_attributes']) {
					$feed->strip_attributes($cfg['strip_htmlattr']);
				}
			}
			if (has_filter('wpematico_fetchfeed'))
				$feed = apply_filters('wpematico_fetchfeed', $feed, $url);
			$feed->enable_cache(false);
			$feed->init();
			$feed->handle_content_type();

			return $feed;
		}

		/**
		 * Tests a feed
		 *
		 */
		public static function Test_feed($args = '') {

			if (is_array($args)) {
				extract($args);
				$ajax = false;
			} else {
				if (!isset($_POST['url'])) {
					return false;
				}
				// to test sanitizers
				//$url	 = wp_sanitize_redirect($_POST['url']);
				$url = esc_url_raw($_POST['url']);
				$ajax = true;
			}
			/**
			 * @since 1.8.0
			 * Added @fetch_feed_params to change parameters values before fetch the feed.
			 */
			$fetch_feed_params = array(
				'url' => $url,
				'stupidly_fast' => true,
				'max' => 0,
				'order_by_date' => false,
				'force_feed' => false,
			);

			$fetch_feed_params = apply_filters('wpematico_fetch_feed_params_test', $fetch_feed_params, 0, $_POST);
			$feed = self::fetchFeed($fetch_feed_params);

			$errors = $feed->error(); // if no error returned

			// Check if PRO version is installed and its required version
			if (wpematico_is_pro_active()) {
				$professional_notice = '';
			} else {
				$professional_notice = '<strong>' . __('You should use the Force Feed or Change User Agent features of ', 'wpematico') . '<a href="https://etruel.com/downloads/wpematico-professional/">WPeMatico Professional</a></strong>';
			}
			if ($ajax) {
				if (empty($errors)) {
					/* translators: the tested Feed URL. */
					$response['message'] = sprintf(__('The feed %s has been parsed successfully.', 'wpematico'), $url);
					$response['message'] .= '<br/> <strong> ' . __('Feed Title:', 'wpematico') . '</strong> ' . $feed->get_title();
					$response['message'] .= '<br/> <strong> ' . __('Generator:', 'wpematico') . '</strong> ' . self::get_generator_feed($feed);
					$response['message'] .= '<br/> <strong> ' . __('Charset Enconding:', 'wpematico') . '</strong> ' . $feed->get_encoding();

					foreach ($feed->get_items() as $item) {
						$response['message'] .= '<br/><hr/> <strong> ' . __('Last Item Title:', 'wpematico') . '</strong> ' . $item->get_title();
						$description = $item->get_content();
						$description = strip_tags($description);
						if (strlen($description) > 53) {
							$description = mb_substr($description, 0, 50);
							$description .= '...';
						}
						$response['message'] .= '<br/> <strong> ' . __('Description:', 'wpematico') . '</strong> ' . $description;
						break;
					}

					$response['success'] = true;
				} else {
					/* translators: %1$s the tested Feed URL. %2$s SimplePie error message. */
					$response['message'] = sprintf(__('The feed %1$s cannot be parsed. Simplepie said: %2$s', 'wpematico'), $url, $errors) . '<br />' . $professional_notice;
					$response['success'] = false;
				}
				wp_send_json($response);  //echo json & die
			} else {
				if (empty($errors)) {
					/* translators: the tested Feed URL. */
					printf(__('The feed %s has been parsed successfully.', 'wpematico'), $url);
				} else {
					/* translators: %1$s the tested Feed URL. %2$s SimplePie error message. */
					printf(__('The feed %1$s cannot be parsed. Simplepie said: %2$s', 'wpematico'), $url, $errors) . '<br />' . $professional_notice;
				}
				return;
			}
		}

		public static function get_generator_feed($feed) {
			$generator_text = __('Undetected', 'wpematico');
			if ($generator_tag = $feed->get_channel_tags('', 'generator')) {
				$generator_text = $generator_tag[0]['data'];
			} else if ($generator_tag = $feed->get_channel_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'generator')) {
				$generator_text = $generator_tag[0]['data'];
			} else if ($generator_tag = $feed->get_channel_tags(SIMPLEPIE_NAMESPACE_ATOM_03, 'generator')) {
				$generator_text = $generator_tag[0]['data'];
			} else if ($generator_tag = $feed->get_channel_tags(SIMPLEPIE_NAMESPACE_RDF, 'generator')) {
				$generator_text = $generator_tag[0]['data'];
			} else if ($generator_tag = $feed->get_channel_tags(SIMPLEPIE_NAMESPACE_RSS_20, 'generator')) {
				$generator_text = $generator_tag[0]['data'];
			}
			return $generator_text;
		}

		public static function get_slug_from_permalink($permalink) {
			$slug = '';
			$permalink = trim(parse_url($permalink, PHP_URL_PATH), '/');
			$pieces = explode('/', $permalink);
			while (empty($slug) && count($pieces) > 0) {
				$slug = array_pop($pieces);
			}
			if (empty($slug)) {
				$slug = str_replace('/', '-', $permalink);
			}
			return $slug;
		}

		################### ARRAYS FUNCS
		/*		 * filtering an array   */

		public static function filter_by_value($array, $index, $value) {
			$newarray = array();
			if (is_array($array) && count($array) > 0) {
				foreach (array_keys($array) as $key) {
					$temp[$key] = $array[$key][$index];
					if ($temp[$key] != $value) {
						$newarray[$key] = $array[$key];
					}
				}
			}
			return $newarray;
		}

		//Example: array_sort($my_array,'!group','surname');
		//Output: sort the array DESCENDING by group and then ASCENDING by surname. Notice the use of ! to reverse the sort order. 
		public static function array_sort_func($a, $b = NULL) {
			static $keys;
			if ($b === NULL)
				return $keys = $a;
			foreach ($keys as $k) {
				if (@$k[0] == '!') {
					$k = substr($k, 1);
					if (@$a[$k] !== @$b[$k]) {
						return strcmp(@$b[$k], @$a[$k]);
					}
				} else if (@$a[$k] !== @$b[$k]) {
					return strcmp(@$a[$k], @$b[$k]);
				}
			}
			return 0;
		}

		public static function array_sort(&$array) {
			if (!$array)
				return false;
			$keys = func_get_args();
			array_shift($keys);
			self::array_sort_func($keys);
			usort($array, array(__CLASS__, "array_sort_func"));
		}

		################### END ARRAYS FUNCS

// ********************************** CRON FUNCTIONS
		public static function cron_string($array_post) {
			if ($array_post['cronminutes'][0] == '*' or empty($array_post['cronminutes'])) {
				if (!empty($array_post['cronminutes'][1])) {
					$array_post['cronminutes'] = array('*/' . $array_post['cronminutes'][1]);
				} else {
					$array_post['cronminutes'] = array('*');
				}
			}
			if ($array_post['cronhours'][0] == '*' or empty($array_post['cronhours'])) {
				if (!empty($array_post['cronhours'][1]))
					$array_post['cronhours'] = array('*/' . $array_post['cronhours'][1]);
				else
					$array_post['cronhours'] = array('*');
			}
			if ($array_post['cronmday'][0] == '*' or empty($array_post['cronmday'])) {
				if (!empty($array_post['cronmday'][1]))
					$array_post['cronmday'] = array('*/' . $array_post['cronmday'][1]);
				else
					$array_post['cronmday'] = array('*');
			}
			if ($array_post['cronmon'][0] == '*' or empty($array_post['cronmon'])) {
				if (!empty($array_post['cronmon'][1]))
					$array_post['cronmon'] = array('*/' . $array_post['cronmon'][1]);
				else
					$array_post['cronmon'] = array('*');
			}
			if ($array_post['cronwday'][0] == '*' or empty($array_post['cronwday'])) {
				if (!empty($array_post['cronwday'][1]))
					$array_post['cronwday'] = array('*/' . $array_post['cronwday'][1]);
				else
					$array_post['cronwday'] = array('*');
			}
			return implode(",", $array_post['cronminutes']) . ' ' . implode(",", $array_post['cronhours']) . ' ' . implode(",", $array_post['cronmday']) . ' ' . implode(",", $array_post['cronmon']) . ' ' . implode(",", $array_post['cronwday']);
		}

		//******************************************************************************
		//Calcs next run for a cron string as timestamp
		public static function time_cron_next($cronstring) {
			//Cronstring zerlegen
			list($cronstr['minutes'], $cronstr['hours'], $cronstr['mday'], $cronstr['mon'], $cronstr['wday']) = explode(' ', $cronstring, 5);

			//make arrys form string
			foreach ($cronstr as $key => $value) {
				if (strstr($value, ','))
					$cronarray[$key] = explode(',', $value);
				else
					$cronarray[$key] = array(0 => $value);
			}
			//make arrys complete with ranges and steps
			foreach ($cronarray as $cronarraykey => $cronarrayvalue) {
				$cron[$cronarraykey] = array();
				foreach ($cronarrayvalue as $key => $value) {
					//steps
					$step = 1;
					if (strstr($value, '/'))
						list($value, $step) = explode('/', $value, 2);
					//replase weekeday 7 with 0 for sundays
					if ($cronarraykey == 'wday')
						$value = str_replace('7', '0', $value);
					//ranges
					if (strstr($value, '-')) {
						list($first, $last) = explode('-', $value, 2);
						if (!is_numeric($first) or!is_numeric($last) or $last > 60 or $first > 60) //check
							return false;
						if ($cronarraykey == 'minutes' and $step < 5)
							$step = 5; //set step in num to 5 min.

						$range = array();
						for ($i = $first; $i <= $last; $i = $i + $step)
							$range[] = $i;

						$cron[$cronarraykey] = array_merge($cron[$cronarraykey], $range);
					} elseif ($value == '*') {
						$range = array();
						if ($cronarraykey == 'minutes') {
							if ($step < 5)
								$step = 5; //set step in mum to 5 min.
							for ($i = 0; $i <= 59; $i = $i + $step)
								$range[] = $i;
						}
						if ($cronarraykey == 'hours') {
							for ($i = 0; $i <= 23; $i = $i + $step)
								$range[] = $i;
						}
						if ($cronarraykey == 'mday') {
							for ($i = $step; $i <= 31; $i = $i + $step)
								$range[] = $i;
						}
						if ($cronarraykey == 'mon') {
							for ($i = $step; $i <= 12; $i = $i + $step)
								$range[] = $i;
						}
						if ($cronarraykey == 'wday') {
							for ($i = 0; $i <= 6; $i = $i + $step)
								$range[] = $i;
						}
						$cron[$cronarraykey] = array_merge($cron[$cronarraykey], $range);
					} else {
						//Month names
						if (strtolower($value) == 'jan')
							$value = 1;
						if (strtolower($value) == 'feb')
							$value = 2;
						if (strtolower($value) == 'mar')
							$value = 3;
						if (strtolower($value) == 'apr')
							$value = 4;
						if (strtolower($value) == 'may')
							$value = 5;
						if (strtolower($value) == 'jun')
							$value = 6;
						if (strtolower($value) == 'jul')
							$value = 7;
						if (strtolower($value) == 'aug')
							$value = 8;
						if (strtolower($value) == 'sep')
							$value = 9;
						if (strtolower($value) == 'oct')
							$value = 10;
						if (strtolower($value) == 'nov')
							$value = 11;
						if (strtolower($value) == 'dec')
							$value = 12;
						//Week Day names
						if (strtolower($value) == 'sun')
							$value = 0;
						if (strtolower($value) == 'mon')
							$value = 1;
						if (strtolower($value) == 'tue')
							$value = 2;
						if (strtolower($value) == 'wed')
							$value = 3;
						if (strtolower($value) == 'thu')
							$value = 4;
						if (strtolower($value) == 'fri')
							$value = 5;
						if (strtolower($value) == 'sat')
							$value = 6;
						if (!is_numeric($value) or $value > 60) //check
							return false;
						$cron[$cronarraykey] = array_merge($cron[$cronarraykey], array(0 => $value));
					}
				}
			}

			//calc next timestamp
			// We should use date_i18n here to avoid new year desfase ? https://wordpress.org/support/topic/wpematico-schedules-stop-til-jan-1-2023/
			$currenttime = current_time('timestamp');
			foreach (array(date_i18n('Y'), date_i18n('Y') + 1) as $year) {
				foreach ($cron['mon'] as $mon) {
					foreach ($cron['mday'] as $mday) {
						foreach ($cron['hours'] as $hours) {
							foreach ($cron['minutes'] as $minutes) {
								$timestamp = mktime($hours, $minutes, 0, $mon, $mday, $year);
								if (in_array(date_i18n('w', $timestamp), $cron['wday']) and $timestamp > $currenttime) {
									return $timestamp;
								}
							}
						}
					}
				}
			}
			return false;
		}

		/**
		 * Returns current plugin version.
		 * 
		 * @return string Plugin version
		 */
		public static function plugin_get_version($file = '') {
			if (empty($file))
				$file = __FILE__;
			if (!function_exists('get_plugins'))
				require_once( ABSPATH . basename(admin_url()) . '/includes/plugin.php' );
			$plugin_folder = get_plugins('/' . plugin_basename(dirname($file)));
			$plugin_file = basename(( $file));
			$plugin_info = array();
			$plugin_info['Name'] = $plugin_folder[$plugin_file]['Name'];
			$plugin_info['Version'] = $plugin_folder[$plugin_file]['Version'];
			return $plugin_info;
		}

		public static function throttling_inserted_post($post_id = 0, $campaign = array()) {
			global $cfg;
			sleep($cfg['throttle']);
		}

		/**
		 * @since 2.4.2
		 * Removed all related to cURL in favor of wp_remote... functions 
		 * @param string $url  URL to get content from
		 * @param bool|array $arg if not bool used as $args: array('key'=>'value'), arguments to change defaults of wp_remote_request
		 * 
		 * @since 1.2.4
		 * @param string $url  URL to get content from
		 * @param bool $curl if exist, force to use CURL. Default true. DEPRECATED
		 * @param bool $curl if not bool used as $args: array('key'=>'value')
		 * 
		 * @return mixed String Content or False if error on get remote file content.
		 */
		public static function wpematico_get_contents($url, $arg = true) {
			/**
			 * Filter to allow change the default parameters for wp_remote_request below.
			 */
			$aux = apply_filters('wpematico_get_contents_request_params', $arg, $url);

			/**
			 * Filter to allow change the $data or any other action before make the URL request
			 */
			$data = apply_filters('wpematico_before_get_content', false, $aux, $url);

			$defaults = array(
				'timeout' => 15,
			);
			$args = wp_parse_args($aux, $defaults);
			if (!$data) { // if stil getting error on get file content try WP func, this may give timeouts 
					$response = wp_remote_request($url, $args);
				if (!is_wp_error($response)) {
					if (isset($response['response']['code']) && 200 === $response['response']['code']) {
						$data = wp_remote_retrieve_body($response);
					} else {
						trigger_error(__('Error with wp_remote_request:', 'wpematico') . print_r($response, 1), E_USER_NOTICE);
					}
				} else {
					trigger_error(__('Error with wp_remote_get:', 'wpematico') . $response->get_error_message(), E_USER_NOTICE);
				}
			}

			return $data;
		}

		public static function get_curl_version() {

			if (!function_exists('curl_version')) {
				return 0;
			}
			if (is_array($curl = curl_version())) {
				$curl = $curl['version'];
			} elseif (substr($curl, 0, 5) === 'curl/') {
				$curl = substr($curl, 5, strcspn($curl, "\x09\x0A\x0B\x0C\x0D", 5));
			} elseif (substr($curl, 0, 8) === 'libcurl/') {
				$curl = substr($curl, 8, strcspn($curl, "\x09\x0A\x0B\x0C\x0D", 8));
			} else {
				$curl = 0;
			}

			return $curl;
		}

		public static function get_danger_options() {
			$danger = get_option('WPeMatico_danger');

			if(is_array($danger)){
				$danger['wpemdeleoptions'] = (isset($danger['wpemdeleoptions']) && !empty($danger['wpemdeleoptions']) ) ? $danger['wpemdeleoptions'] : false;
				$danger['wpemdelecampaigns'] = (isset($danger['wpemdelecampaigns']) && !empty($danger['wpemdelecampaigns']) ) ? $danger['wpemdelecampaigns'] : false;
				$danger['wpe_debug_logs_campaign'] = (isset($danger['wpe_debug_logs_campaign']) && !empty($danger['wpe_debug_logs_campaign']) ) ? $danger['wpe_debug_logs_campaign'] : false;
			}else{
				$danger = [];
				$danger['wpemdeleoptions'] = false;
				$danger['wpemdelecampaigns'] = false;
				$danger['wpe_debug_logs_campaign'] = false;
			}

			return $danger;
		}
		public static function wpematico_export_settings($status = '') {
			$nonce = (isset($_REQUEST['_wpnonce']) && !empty($_REQUEST['_wpnonce']) ) ? sanitize_text_field($_REQUEST['_wpnonce']) : '';
			if (!wp_verify_nonce($nonce, 'wpematico-tools'))
				wp_die('Are you sure?');
			
			$export_settings = array();
			$cfg = get_option(WPeMatico :: OPTION_KEY);
			$cfg = apply_filters('wpematico_check_options', $cfg);
			$export_settings[WPeMatico :: OPTION_KEY] = $cfg;
			$export_settings = apply_filters('wpematico_export_options', $export_settings);
			
			$settings_data_json = json_encode($export_settings);
			$settings_data_json = base64_encode($settings_data_json);
			
			// Copy the post and insert it
			if (isset($settings_data_json) && $settings_data_json != null) {
				header('Content-type: text/plain');
				header('Content-Disposition: attachment; filename="wpematico-settings.txt"');
				print $settings_data_json;
				die();
			} else {
				wp_die(esc_attr(__('Exporting failed', 'wpematico')));
			}
		}

		public static function wpematico_import_settings() {
			$nonce = (isset($_REQUEST['_wpnonce']) && !empty($_REQUEST['_wpnonce']) ) ? sanitize_text_field($_REQUEST['_wpnonce']) : '';
			if (!wp_verify_nonce($nonce, 'wpematico-tools'))
				wp_die('Are you sure?');

			if (in_array(str_replace('.', '', strrchr($_FILES['txtsettings']['name'], '.')), explode(',', 'txt')) && ($_FILES['txtsettings']['type'] == 'text/plain') && !$_FILES['txtsettings']['error']) {
				$settings = file_get_contents($_FILES['txtsettings']['tmp_name']);
				$settings = base64_decode($settings);
				$settings = json_decode($settings, true);

				$settings[Wpematico::OPTION_KEY] = apply_filters('wpematico_check_options', $settings[Wpematico::OPTION_KEY]);
				
				foreach($settings as $settingKey => $value){
					update_option($settingKey, $value);
				}

				WPeMatico::add_wp_notice(array('text' => __('Settings Imported.', 'wpematico'), 'below-h2' => false));
				wp_redirect(admin_url('edit.php?post_type=wpematico&page=wpematico_tools&tab=tools'));
			} else {
				$message = __("Can't upload! Just .txt files allowed!", 'wpematico');
				WPeMatico::add_wp_notice(array('text' => $message, 'below-h2' => false, 'error' => true));
				wp_redirect(admin_url('edit.php?post_type=wpematico&page=wpematico_tools&tab=tools'));
			}
		}
	}

	// Class WPeMatico_functions
}  // if Class exist

/* * ***** FUNCTIONS  ********** */
add_action('admin_init', 'wpematico_process_actions');

function wpematico_process_actions() {
	if (isset($_POST['wpematico-action'])) {
		if (!is_user_logged_in())
			wp_die("Cheatin' uh?", "Closed today.");
		$action = sanitize_text_field($_POST['wpematico-action']);
		do_action('wpematico_' . $action, $_POST);
	}

	if (isset($_GET['wpematico-action'])) {
		if (!is_user_logged_in())
			wp_die("Cheatin' uh?", "Closed today.");
		$action = sanitize_text_field($_GET['wpematico-action']);
		do_action('wpematico_' . $action, $_GET);
	}
}

/**
 * Get user host
 *
 * Returns the webhost this site is using if possible
 *
 * @since 1.2.4
 * @return mixed string $host if detected, false otherwise
 */
function wpematico_get_host() {
	$host = false;

	if (defined('WPE_APIKEY')) {
		$host = 'WP Engine';
	} elseif (defined('PAGELYBIN')) {
		$host = 'Pagely';
	} elseif (DB_HOST == 'localhost:/tmp/mysql5.sock') {
		$host = 'ICDSoft';
	} elseif (DB_HOST == 'mysqlv5') {
		$host = 'NetworkSolutions';
	} elseif (strpos(DB_HOST, 'ipagemysql.com') !== false) {
		$host = 'iPage';
	} elseif (strpos(DB_HOST, 'ipowermysql.com') !== false) {
		$host = 'IPower';
	} elseif (strpos(DB_HOST, '.gridserver.com') !== false) {
		$host = 'MediaTemple Grid';
	} elseif (strpos(DB_HOST, '.pair.com') !== false) {
		$host = 'pair Networks';
	} elseif (strpos(DB_HOST, '.stabletransit.com') !== false) {
		$host = 'Rackspace Cloud';
	} elseif (strpos(DB_HOST, '.sysfix.eu') !== false) {
		$host = 'SysFix.eu Power Hosting';
	} elseif (strpos($_SERVER['SERVER_NAME'], 'Flywheel') !== false) {
		$host = 'Flywheel';
	} else {
		// Adding a general fallback for data gathering
		$host = 'DBH: ' . DB_HOST . ', SRV: ' . $_SERVER['SERVER_NAME'];
	}

	return $host;
}

/**
 * wpematico_is_pro_active
 *
 * Returns if installed & active PRO VERSION
 *
 * @since 1.2.4
 * @return bool|int if installed & active
 */
function wpematico_is_pro_active($returnbool = false) {  // Check if PRO version is installed & active
	
	if($returnbool){
		return defined('WPEMATICOPRO_VERSION');
	}

	$active_plugins = get_option('active_plugins');
	$active_plugins_names = array_map('basename', $active_plugins);
	$is_pro_active = array_search('wpematicopro.php', $active_plugins_names);

	return $is_pro_active;
}

add_action('wpematico_wp_ratings', 'wpematico_wp_ratings');

function wpematico_wp_ratings() {
	?><div class="postbox">
		<h3 class="handle"><?php _e('5 Stars Ratings on Wordpress', 'wpematico'); ?></h3>
		<?php if (get_option('wpem_hide_reviews')) : ?>
			<div class="inside" style="max-height:300px;overflow-x: hidden;">
				<p style="text-align: center;">
					<a href="https://wordpress.org/support/view/plugin-reviews/wpematico?filter=5&rate=5" id="linkgo" class="button" target="_Blank" title="Click to see 5 stars Reviews on Wordpress"> Click to see 5 stars Reviews </a>
				</p>
			</div>
		<?php else: ?>
			<div class="inside" style="max-height:300px;overflow-y: scroll;overflow-x: hidden;">
				<?php require_once('lib/wp_ratings.php'); ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * array_multi_key_exists	http://php.net/manual/es/function.array-key-exists.php#106449
 * @param array $arrNeedles
 * @param array $arrHaystack
 * @param type $blnMatchAll
 * @return boolean
 */
function array_multi_key_exists(array $arrNeedles, array $arrHaystack, $blnMatchAll = true) {
	$blnFound = array_key_exists(array_shift($arrNeedles), $arrHaystack);

	if ($blnFound && (count($arrNeedles) == 0 || !$blnMatchAll))
		return true;

	if (!$blnFound && count($arrNeedles) == 0 || $blnMatchAll)
		return false;

	return array_multi_key_exists($arrNeedles, $arrHaystack, $blnMatchAll);
}

function wpematico_get_active_seo_plugin() {
	// List of SEO plugins and their main files
	$seo_array = array(
		'yoast_seo' => 'wordpress-seo/wp-seo.php',
		// 'all_in_one_seo' => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
		'rank_math' => 'seo-by-rank-math/rank-math.php',
		'seo_framework' => 'autodescription/autodescription.php',
		// Add more SEO plugins here
	);
	$seo_plugins = apply_filters('wpematico_seo_plugins', $seo_array);
	// Verify if some SEO plugin is active
	foreach ($seo_plugins as $slug => $main_file) {
		if (is_plugin_active($main_file)) {
			// Return the slug of the $seo_plugins
			return $slug;
		}
	}
	// If doens't exist or there aren't some SEO plugin active return false
	return false;
}


/**
 * Alternative ini_set to trigger errors and changed values 
 * 
 * @param string $index	
 * @param string|int|float|bool|null $value	<p>The new value for the option.</p>
 * @param bool $log_only_fail <p>Trigger the WARNING only if fail to set the new value for the option.</p>
 * @return string|false <p>Returns the old value on success, <b><code>false</code></b> on failure.</p>
 */
function wpematico_init_set($index, $value, $log_only_fail = false) {
    $oldvalue = @ini_set($index, $value) or $oldvalue = FALSE; //@return string the old value on success, <b>FALSE</b> on failure. (after 'or' is by the @)
	
	/* translators: %1$s the tested Feed URL. 
	 * %1$s ini option to change. 
	 * %2$s The new value for the option. 
	 * %3$s Operation result. Success or Failed.
	 * %4$s Old previous value returned on fail. 
	 */
	$error_msg = __('Trying to set %1$s = %2$s: \'%3$s\' - Old value: %4$s.', 'wpematico');
	
    if ($log_only_fail) {
        if ($oldvalue === false) {
            trigger_error(sprintf($error_msg, 
					$index, //%1$s
					$value, //%2$s
					__('Failed', 'wpematico'), //%3$s
					$oldvalue //%4$s
				), E_USER_WARNING);
        }
    } else {
        trigger_error(sprintf($error_msg, 
				$index, //%1$s
				$value, //%2$s
				(($oldvalue === FALSE) ? __('Failed', 'wpematico') : __('Success', 'wpematico')), //%3$s
				$oldvalue //%4$s
			), (($oldvalue === FALSE) ? E_USER_WARNING : E_USER_NOTICE));
    }

    return $oldvalue;
}


/**
 * function for PHP error handling saved as Campaign Logs.
 * 
 * @global string $campaign_log_message Currently log where to add next line. 
 * @global int $jobwarnings Warnings quantity.
 * @global int $joberrors Errors quantity.
 * @param number $errno PHP constants error values.
 * @param string $errstr PHP Error details.
 * @param string $errfile File with error.
 * @param type $errline Line of the error in previous file.
 * @return bool True for no more php error hadling.
 */
function wpematico_joberrorhandler($errno, $errstr, $errfile, $errline) {
	global $campaign_log_message, $jobwarnings, $joberrors;

	//Generate timestamp
	if (!version_compare(phpversion(), '6.9.0', '>')) { // PHP Version < 5.7 dirname 2nd 
		if (!function_exists('memory_get_usage')) { // test if memory functions compiled in
			$timestamp = "<span style=\"background-color:#c3c3c3; padding: 0 5px;\" title=\"[Line: " . $errline . "|File: " . trailingslashit(dirname($errfile)) . basename($errfile) . "\">" . date_i18n('Y-m-d H:i.s') . ":</span> ";
		} else {
			$timestamp = "<span style=\"background-color:#c3c3c3; padding: 0 5px;\" title=\"[Line: " . $errline . "|File: " . trailingslashit(dirname($errfile)) . basename($errfile) . "|Mem: " . WPeMatico :: formatBytes(@memory_get_usage(true)) . "|Mem Max: " . WPeMatico :: formatBytes(@memory_get_peak_usage(true)) . "|Mem Limit: " . ini_get('memory_limit') . "]\">" . date_i18n('Y-m-d H:i.s') . ":</span> ";
		}
	} else {
		if (!function_exists('memory_get_usage')) { // test if memory functions compiled in
			$timestamp = "<span style=\"background-color:#c3c3c3; padding: 0 5px;\" title=\"[Line: " . $errline . "|File: " . trailingslashit(dirname($errfile, 2)) . basename($errfile) . "\">" . date_i18n('Y-m-d H:i.s') . ":</span> ";
		} else {
			$timestamp = "<span style=\"background-color:#c3c3c3; padding: 0 5px;\" title=\"[Line: " . $errline . "|File: " . trailingslashit(dirname($errfile, 2)) . basename($errfile) . "|Mem: " . WPeMatico :: formatBytes(@memory_get_usage(true)) . "|Mem Max: " . WPeMatico :: formatBytes(@memory_get_peak_usage(true)) . "|Mem Limit: " . ini_get('memory_limit') . "]\">" . date_i18n('Y-m-d H:i.s') . ":</span> ";
		}
	}

	switch ($errno) {
		case E_NOTICE:
		case E_USER_NOTICE:
			$sMessage = $timestamp . "<span>" . $errstr . "</span>";
			break;
		case E_WARNING:
		case E_USER_WARNING:
			$jobwarnings += 1;
			$sMessage = $timestamp . "<span style=\"background-color:yellow;\">" . __('[WARNING]', 'wpematico') . " " . $errstr . "</span>";
			break;
		case E_ERROR:
		case E_USER_ERROR:
			$joberrors += 1;
			$sMessage = $timestamp . "<span style=\"background-color:red;\">" . __('[ERROR]', 'wpematico') . " " . $errstr . "</span>";
			break;
		case E_DEPRECATED:
		case E_USER_DEPRECATED:
			$sMessage = $timestamp . "<span>" . __('[DEPRECATED]', 'wpematico') . " " . $errstr . "</span>";
			break;
		case E_STRICT:
			$sMessage = $timestamp . "<span>" . __('[STRICT NOTICE]', 'wpematico') . " " . $errstr . "</span>";
			break;
		case E_RECOVERABLE_ERROR:
			$sMessage = $timestamp . "<span>" . __('[RECOVERABLE ERROR]', 'wpematico') . " " . $errstr . "</span>";
			break;
		default:
			$sMessage = $timestamp . "<span>[" . $errno . "] " . $errstr . "</span>";
			break;
	}

	if (!empty($sMessage)) {

		$campaign_log_message .= $sMessage . "<br />\n";

		if ($errno == E_ERROR or $errno == E_CORE_ERROR or $errno == E_COMPILE_ERROR) {//Die on fatal php errors.
			die("Fatal Error:" . $errno);
		}
		
		// Deprecated on 2.7
		// wpematico_init_set('max_execution_time',300);
		// @set_time_limit(300);
		
		// Since 2.7.2
		if ( function_exists('ini_restore') ) {
			//  Testin restoring default value instead set it to 300
			ini_restore('max_execution_time');
		}else { // ini_restore is not enabled
			//300 is most webserver time limit. 0= max time! Give script 5 min. more to work.
			wpematico_init_set('max_execution_time', 300);
		}
		
		
		//true for no more php error hadling.
		return true;
	} else {
		return false;
	}
}

/**
 * function for feed hash.
 * 
 * @global string $type Currently type for the hash. 
 * @param string $feed current feed to make the hash.
 * @param string $feedHash feed already hashed.
 * @return string
 */
function wpematico_feed_hash_key($type, $feed, $feedHash = ''){
	$feedHash = md5($feed);

	if($type == 'campaign'){
		if(isset($type[$feed]["lasthash"])){
			$type[$feedHash]["lasthash"] = $type[$feed]["lasthash"];
		}
	}else{
		if (isset($type[$feed])) {
			$type[$feedHash] = $type[$feed];
		}
	}

	$feedHash = isset($feedHash) ? $feedHash : $feed;
	
	return apply_filters('wpematico_feed_hash_key', $feedHash);
}
