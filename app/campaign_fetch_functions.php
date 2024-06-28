<?php

/**
 * WPeMatico plugin for WordPress
 * campaign_fetch_functions 
 * Contains all the auxiliary methods and filters used in the campaign fetch/run events
 * Called by campaign_fetch.php
 *  
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

if (class_exists('wpematico_campaign_fetch_functions'))
	return;

class wpematico_campaign_fetch_functions {

	public static function WPeisDuplicatedMetaSource($dev, $campaign, $item) {
		global $wpdb;
		$dev				 = false;
		$permalink			 = self::getReadUrl($item->get_permalink(), $campaign);
		$check_sql			 = "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'wpe_sourcepermalink' AND meta_value = %s LIMIT 1";
		$permantlink_check	 = $wpdb->get_var($wpdb->prepare($check_sql, $permalink));
		if ($permantlink_check) {
			$dev = true;
		}
		return $dev;
	}

	/**
	 * Filters to skip item or not
	 * @param   $current_item   array    Current post data to be saved
	 * @param   $campaign       array    Current campaign data
	 * @param   $feed           object    Feed database object
	 * @param   $item           object    SimplePie_Item object
	 *
	 * Return TRUE if skip the item 
	 */
	function exclude_filters(&$current_item, &$campaign, &$feed, &$item) {
		$categories	 = (isset($current_item['categories']) && !empty($current_item['categories']) ) ? $current_item['categories'] : '';
		$post_id	 = $this->campaign_id;
		$skip		 = false;

		/* deprecated since 1.3.8.4
		 * if( $this->cfg['nonstatic'] ) { $skip = NoNStatic :: exclfilters($current_item,$campaign,$item ); };  */

		$skip = apply_filters('wpematico_excludes', $skip, $current_item, $campaign, $item);
		return $skip;
	}

	// End exclude filters

	/**
	 * Parses an item content
	 *
	 * @param   $current_item   array    Current post data to be saved
	 * @param   $campaign       array    Current campaign data
	 * @param   $feed           object    Feed database object
	 * @param   $item           object    SimplePie_Item object
	 */
	function Item_parsers(&$current_item, &$campaign, &$feed, &$item, $count, $feedurl) {
		global $cfg;
		$post_id		 = $this->campaign_id;
		$current_item	 = apply_filters('wpematico_item_parsers', $current_item, $campaign, $feed, $item);
		//if( $this->cfg['nonstatic'] ) { $current_item = NoNStatic :: content($current_item,$campaign,$item); }
		if (isset($current_item['SKIP']) && is_int($current_item['SKIP']))
			return $current_item['SKIP'];
			
		// strip all HTML tags before apply filter wpematico_after_item_parsers
		if ($campaign['campaign_striphtml']) {
			trigger_error(sprintf(__('Deleting html tags: %s', 'wpematico'), $item->get_title()), E_USER_NOTICE);
			$current_item['content'] = strip_tags($current_item['content'], apply_filters('wpem_dont_strip_tags', ''));
		}

		if (!$cfg['disable_credits']) {
			$current_item['content'] .= '<p class="wpematico_credit"><small>Powered by <a href="http://www.wpematico.com" target="_blank">WPeMatico</a></small></p>';
		}

		$current_item = apply_filters('wpematico_after_item_parsers', $current_item, $campaign, $feed, $item);
		//if($current_item == -1 ) return -1; //Hack to allow skip the post in this instance

		return $current_item;
	}

	// End ParseItemContent


	public static function wpematico_strip_links($current_item, $campaign, $feed, $item) {
		// take out links before apply template (if don't strip before html tags)
		if ($campaign['campaign_strip_links'] && !$campaign['campaign_striphtml']) {
			trigger_error(__('Cleaning Links from content.', 'wpematico'), E_USER_NOTICE);
			$current_item['content'] = self::strip_links((string) $current_item['content'], $campaign);
		}

		return $current_item;
	}

	public static function wpematico_strip_links_a($current_item, $campaign, $feed, $item) {
		// take out links before apply template (if don't strip before html tags)
		if ($campaign['campaign_strip_links'] && !$campaign['campaign_striphtml']) {
			trigger_error(__('Cleaning Links from content.', 'wpematico'), E_USER_NOTICE);
			$current_item['content'] = self::strip_links_a((string) $current_item['content'], $campaign);
		}

		return $current_item;
	}

	public static function wpematico_template_parse($current_item, $campaign, $feed, $item) {
		// Template parse           
		if ($campaign['campaign_enable_template']) {
			trigger_error('<b>' . __('Parsing Post template.', 'wpematico') . '</b>', E_USER_NOTICE);
			if (!empty($current_item['images'][0])) {
				$img_str = "<img class=\"wpe_imgrss\" src=\"" . $current_item['images'][0] . "\">";  //Solo la imagen
			} else {
				if (!empty($current_item['featured_image'])) {
					$img_str = "<img class=\"wpe_imgrss\" src=\"" . $current_item['featured_image'] . "\">";  //Solo la imagen
				} else {
					trigger_error(__('Can\'t find the featured image to add to the content.'), E_USER_WARNING);
					$img_str = '<!-- no image -->';
				}
			}
			/**
			 * Since 1.6.1
			 * New way to get the template vars. 
			 * See below the function default_template_vars with the filter to add new tags 
			 */
			$template_vars	 = self::default_template_vars(array(), $current_item, $campaign, $feed, $item, $img_str);
			$vars			 = array();
			$replace		 = array();
			foreach ($template_vars as $tvar => $tvalue) {
				$vars[]		 = $tvar;
				$replace[]	 = $tvalue;
			}
			/**
			 *  wpematico_post_template_tags, wpematico_post_template_replace filters
			 *  Are deprecated, will be removed on version 1.7
			 */
			$vars	 = apply_filters('wpematico_post_template_tags', $vars, $current_item, $campaign, $feed, $item);
			$replace = apply_filters('wpematico_post_template_replace', $replace, $current_item, $campaign, $feed, $item);

			$current_item['content'] = str_ireplace($vars, $replace, ( $campaign['campaign_template'] ) ? stripslashes($campaign['campaign_template']) : '{content}');
		}

		return $current_item;
	}

	public static function wpematico_campaign_rewrites($current_item, $campaign, $feed, $item) {
		// Rewrite
		//$rewrites = $campaign['campaign_rewrites'];
		if (isset($campaign['campaign_rewrites']['origin']) && !empty($campaign['campaign_rewrites']['origin']))
			for ($i = 0; $i < count($campaign['campaign_rewrites']['origin']); $i++) {
				$on_title	 = ($campaign['campaign_rewrites']['title'][$i]) ? true : false;
				$origin		 = stripslashes($campaign['campaign_rewrites']['origin'][$i]);
				if (isset($campaign['campaign_rewrites']['rewrite'][$i])) {
					$reword = !empty($campaign['campaign_rewrites']['relink'][$i]) ? '<a href="' . stripslashes($campaign['campaign_rewrites']['relink'][$i]) . '">' . stripslashes($campaign['campaign_rewrites']['rewrite'][$i]) . '</a>' : stripslashes($campaign['campaign_rewrites']['rewrite'][$i]);

					if ($campaign['campaign_rewrites']['regex'][$i]) {
						if ($on_title)
							$current_item['title']	 = preg_replace($origin, $reword, $current_item['title']);
						else
							$current_item['content'] = preg_replace($origin, $reword, $current_item['content']);
					} else
					if ($on_title)
						$current_item['title']	 = str_ireplace($origin, $reword, $current_item['title']);
					else
						$current_item['content'] = str_ireplace($origin, $reword, $current_item['content']);
				} else if (!empty($campaign['campaign_rewrites']['relink'][$i]))
					$current_item['content'] = str_ireplace($origin, '<a href="' . stripslashes($campaign['campaign_rewrites']['relink'][$i]) . '">' . $origin . '</a>', $current_item['content']);
			}
		// End rewrite

		return $current_item;
	}

	public static function default_template_vars($vars, $current_item, $campaign, $feed, $item, $img_str) {
		$autor		 = '';
		$autorlink	 = '';
		if ($author		 = $item->get_author()) {
			$autor		 = $author->get_name();
			$autorlink	 = $author->get_link();
		}

		$favicon = (method_exists($feed, "get_favicon")) ? $feed->get_favicon() : "";  // 2.6.20.2 xml or other custom feeds does not have icon or its method 

		/**
		 * wpematico_feed_get_favicon filter
		 * Allow change the default favicon or assign one 
		 * Parameter 1: image 
		 */
		$favicon = apply_filters('wpematico_feed_get_favicon', $favicon); // <== 2.6.14 New by https://wordpress.org/support/topic/fetching-favicons/#post-15280216 

		$vars = array(
			'{title}'			 => $current_item['title'],
			'{content}'			 => $current_item['content'],
			'{itemcontent}'		 => $item->get_description(),
			'{image}'			 => $img_str,
			'{author}'			 => $autor,
			'{permalink}'		 => $current_item['permalink'],
			'{feedurl}'			 => $feed->feed_url,
			'{feedtitle}'		 => $feed->get_title(),
			'{feeddescription}'	 => $feed->get_description(),
			'{feedlogo}'		 => $feed->get_image_url(),
			'{feedfavicon}'		 => $favicon,
			'{campaigntitle}'	 => get_the_title($campaign['ID']),
			'{campaignid}'		 => $campaign['ID'],
			'{item_date}'		 => (($current_item['date']) ? gmdate(get_option('date_format'), $current_item['date'] + (get_option('gmt_offset') * 3600)) : date_i18n(get_option('date_format'), current_time('timestamp'))),
			'{item_time}'		 => (($current_item['date']) ? gmdate(get_option('time_format'), $current_item['date'] + (get_option('gmt_offset') * 3600)) : date_i18n(get_option('time_format'), current_time('timestamp'))),
		);

		$template_vars = apply_filters('wpematico_add_template_vars', $vars, $current_item, $campaign, $feed, $item);

		return $template_vars;
	}

	/**
	 * Filters an item content
	 * @param   $current_item   array    Current post data to be saved
	 * @param   $campaign       array    Current campaign data
	 * @param   $feed           object    Feed database object
	 * @param   $item           object    SimplePie_Item object
	 */
	function Item_filters(&$current_item, &$campaign, &$feed, &$item) {
		$categories				 = $current_item['categories'];
		$post_id				 = $this->campaign_id;
		$new_categories			 = array();
		$new_categories_words	 = array();

		$w2c_only_use_a_category	 = $campaign['campaign_w2c_only_use_a_category'];
		$w2c_the_category_most_used	 = $campaign['campaign_w2c_the_category_most_used'];
		//Proceso Words to Category y si hay las agrego al array
		if ($this->cfg['enableword2cats']) {
			if (isset($campaign['campaign_wrd2cat']['word']) && (!empty($campaign['campaign_wrd2cat']['word'][0]) ) && (!empty($campaign['campaign_wrd2cat']['w2ccateg'][0]) )
			) {
				trigger_error(sprintf(__('Processing Words to Category %s', 'wpematico'), $current_item['title']), E_USER_NOTICE);

				for ($i = 0; $i < count($campaign['campaign_wrd2cat']['word']); $i++) {
					$foundit = false;
					$word	 = stripslashes(htmlspecialchars_decode(@$campaign['campaign_wrd2cat']['word'][$i]));
					if (isset($campaign['campaign_wrd2cat']['w2ccateg'][$i])) {
						$tocat = $campaign['campaign_wrd2cat']['w2ccateg'][$i];
						if ($campaign['campaign_wrd2cat']['regex'][$i]) {
							if ($campaign['campaign_wrd2cat']['title'][$i]) {
								$foundit = (preg_match($word, $current_item['title'])) ? true : false;
							} else {
								$foundit = (preg_match($word, $current_item['content'])) ? true : false;
							}
						} else {
							if ($campaign['campaign_wrd2cat']['cases'][$i]) {
								if ($campaign['campaign_wrd2cat']['title'][$i]) {
									$foundit = strpos($current_item['title'], $word);
								} else {
									$foundit = strpos($current_item['content'], $word);
								}
							} else {
								if ($campaign['campaign_wrd2cat']['title'][$i]) {
									$foundit = stripos($current_item['title'], $word); //insensible a May/min
								} else {
									$foundit = stripos($current_item['content'], $word); //insensible a May/min
								}
							}
						}
						if ($foundit !== false) {
							trigger_error(sprintf(__('Found!: word %s to Cat_id %s', 'wpematico'), $word, $tocat), E_USER_NOTICE);
							$new_categories[]		 = $tocat;
							$new_categories_words[]	 = strtolower($word);
						} else {
							trigger_error(sprintf(__('Not found word %s', 'wpematico'), $word), E_USER_NOTICE);
						}
					}
				}

				if ($w2c_only_use_a_category) {
					if ($w2c_the_category_most_used) {
						trigger_error(__('Searching the category with more words.', 'wpematico'), E_USER_NOTICE);
						$content_lower				 = strtolower($current_item['content']);
						$current_words_count		 = 0;
						$category_with_more_words	 = 0;
						foreach ($new_categories_words as $kw => $search_word) {
							$pieces_words	 = explode($search_word, $content_lower);
							$words_count	 = count($pieces_words);
							if ($words_count > $current_words_count) {
								$current_words_count = $words_count;
								if (!empty($new_categories[$kw])) {
									$category_with_more_words = $new_categories[$kw];
								}
							}
						}
						if (!empty($category_with_more_words)) {
							trigger_error(sprintf(__('The category with more words in content: %s', 'wpematico'), $category_with_more_words), E_USER_NOTICE);
							$new_categories = array($category_with_more_words);
						}
					} else {
						$new_categories = (isset($new_categories[0]) ? array($new_categories[0]) : array());
						if (isset($new_categories[0])) {
							trigger_error(sprintf(__('Assign the first category: %s', 'wpematico'), $new_categories[0]), E_USER_NOTICE);
						}
					}
				}

				$current_item['categories'] = array_merge($current_item['categories'], $new_categories);
			}
		} // End Words to Category
		//Tags
		if (has_filter('wpematico_pretags'))
			$current_item['campaign_tags'] = apply_filters('wpematico_pretags', $current_item, $item, $this->cfg);
		if ($this->cfg['nonstatic']) {
			$current_item					 = NoNStatic :: postags($current_item, $campaign, $item);
			$current_item['campaign_tags']	 = $current_item['tags'];
		} else {
			$current_item['campaign_tags'] = explode(',', $campaign['campaign_tags']);
		}

		if (has_filter('wpematico_postags')) {
			$current_item['campaign_tags'] = apply_filters('wpematico_postags', $current_item, $item, $this->cfg);
		}

		/**
		 * wpematico_categories_after_filters
		 * Filter the array of categories to be parsed before inserted into the database.
		 * @since 2.1.2
		 * @param string $autocats The array of categories names.
		 */
		$current_item['categories'] = apply_filters('wpematico_categories_after_filters', $current_item['categories'], $item, $this->cfg);

		return $current_item;
	}

// End item filters

	/**
	 * Get URL from relative path
	 * @param $baseUrl base url
	 * @param $relative relative url
	 * @return string absolute url version of relative url
	 */
	static function getRelativeUrl($baseUrl, $relative) {
		$schemes = array('http', 'https', 'ftp');
		foreach ($schemes as $scheme) {
			if (strpos($relative, "{$scheme}://") === 0) //if not relative
				return $relative;
		}

		$urlInfo = parse_url($baseUrl);

		$basepath			 = $urlInfo['path'];
		$basepathComponent	 = explode('/', $basepath);
		$resultPath			 = $basepathComponent;
		$relativeComponent	 = explode('/', $relative);
		$last				 = array_pop($relativeComponent);
		foreach ($relativeComponent as $com) {
			if ($com === '') {
				$resultPath = array('');
			} else if ($com == '.') {
				$cur = array_pop($resultPath);
				if ($cur === '') {
					array_push($resultPath, $cur);
				} else {
					array_push($resultPath, '');
				}
			} else if ($com == '..') {
				if (count($resultPath) > 1)
					array_pop($resultPath);
				array_pop($resultPath);
				array_push($resultPath, '');
			} else {
				if (count($resultPath) > 1)
					array_pop($resultPath);
				array_push($resultPath, $com);
				array_push($resultPath, '');
			}
		}
		array_pop($resultPath);
		array_push($resultPath, $last);
		$resultPathReal = implode('/', $resultPath);
		return $urlInfo['scheme'] . '://' . $urlInfo['host'] . $resultPathReal;
	}

	static function getReadUrl($url, $campaign) {
		$permalink	 = htmlspecialchars_decode($url);
		// Colons in URL path segments get encoded by SimplePie, yet some sites expect them unencoded
		$permalink	 = str_replace('%3A', ':', $permalink);
		$permalink	 = apply_filters('wpepro_full_permalink', $permalink);

		//if is this same host return 
		$urlInfo = parse_url($permalink);
		if (empty($urlInfo['host'])) {
			return $permalink;
		}
		if ($urlInfo['host'] == $_SERVER['SERVER_NAME']) {
			return $permalink;
		}

		//search for redirections
		if (!$campaign['avoid_search_redirection']) {

			if (version_compare(phpversion(), '5.3.0', '>=')) {
				stream_context_set_default(array(
					'ssl' => array(
						'verify_peer'		 => false,
						'verify_peer_name'	 => false,
					),
				));
			}

			$headers = get_headers($permalink);
			foreach ($headers as $header) {
				$parts = explode(':', $header, 2);
				if (strtolower($parts[0]) == 'location') {
					$location	 = trim($parts[1]);
					$url_parts	 = parse_url($location);
					if (!isset($url_parts['host']) && !isset($url_parts['scheme'])) {
						$permalink_parts = parse_url($permalink);
						if (isset($permalink_parts['host']) && isset($permalink_parts['scheme'])) {
							$location = $permalink_parts['scheme'] . '://' . $permalink_parts['host'] . $location;
						}
					}
					return $location;
				}
			}
		}
		return $permalink;
	}

	/**
	 * Filters images, upload and replace on text item content
	 * @param   $current_item   array    Current post data to be saved
	 * @param   $campaign       array    Current campaign data
	 * @param   $feed           object    Feed database object
	 * @param   $item           object    SimplePie_Item object
	 */
	function Item_images(&$current_item, &$campaign, &$feed, &$item, $options_images) {
		if ($options_images['imgcache']) {
			$itemUrl = $this->current_item['permalink'];

			if (sizeof($current_item['images'])) { // Si hay alguna imagen en el contenido
				trigger_error('<b>' . __('Looking for images in content.', 'wpematico') . '</b>', E_USER_NOTICE);
				//trigger_error(print_r($current_item['images'],true),E_USER_NOTICE);
				$featured	 = false;
				$img_new_url = array();
				foreach ($current_item['images'] as $imagen_src) {
					if ($options_images['featuredimg'] && $current_item['featured_image'] == $imagen_src) {
						$featured = true;
					}
					$imagen_src		 = apply_filters('wpematico_imagen_src', $imagen_src); // allow strip parts 
					trigger_error(__('Uploading media...', 'wpematico') . $imagen_src, E_USER_NOTICE);
					$imagen_src_real = $this->getRelativeUrl($itemUrl, $imagen_src);
					// Strip all white space on images URLs.	
					$imagen_src_real = str_replace(' ', '%20', $imagen_src_real);
					// Fix images URLs with entities like &amp;	to get it with correct name and remain the original in images array.
					$imagen_src_real = html_entity_decode($imagen_src_real);
					$imagen_src_real = apply_filters('wpematico_img_src_url', $imagen_src_real); // original source
					$allowed		 = (isset($this->cfg['images_allowed_ext']) && !empty($this->cfg['images_allowed_ext']) ) ? $this->cfg['images_allowed_ext'] : 'jpg,gif,png,tif,bmp,jpeg';
					$allowed		 = apply_filters('wpematico_allowext', $allowed);
					//Fetch and Store the Image	
					///////////////***************************************************************************************////////////////////////
					$newimgname		 = apply_filters('wpematico_newimgname', sanitize_file_name(urlencode(basename($imagen_src_real))), $current_item, $campaign, $item);  // new name here
					// Primero intento con mi funcion mas rapida
					$newimgname 	 = substr($newimgname , 0, 255);
					$upload_dir		 = wp_upload_dir();
					$imagen_dst		 = trailingslashit($upload_dir['path']) . $newimgname;
					$imagen_dst_url	 = trailingslashit($upload_dir['url']) . $newimgname;
					trigger_error('Filtering image extensions:' . $allowed, E_USER_NOTICE);
					if (in_array(str_replace('.', '', strrchr(strtolower($imagen_dst), '.')), explode(',', $allowed))) {   // ----- check allowed extensions
						trigger_error('Uploading media=' . $imagen_src . ' <b>to</b> image=' . $imagen_dst . '', E_USER_NOTICE);
						// Check if try custom functions to upload files.
						$newfile = ($options_images['customupload']) ? WPeMatico::save_file_from_url($imagen_src_real, $imagen_dst) : false;
						if ($newfile) { //If <> false was uploaded
							trigger_error('Uploaded media=' . $newfile, E_USER_NOTICE);
							$imagen_dst				 = $newfile;
							$imagen_dst_url			 = trailingslashit($upload_dir['url']) . basename($newfile);
							$current_item['content'] = str_replace($imagen_src, $imagen_dst_url, $current_item['content']);
							do_action('wpematico_new_image_url_uploaded', $imagen_dst_url, $imagen_src, $current_item, $campaign);
							$img_new_url[]			 = $imagen_dst_url;
						} else { // Upload fail -> try with others
							$bits = WPeMatico::wpematico_get_contents($imagen_src_real); // Read the file
							if (!$bits) {
								// Remove the image if its upload fail.
								trigger_error(__('Upload file failed:', 'wpematico') . $imagen_dst, E_USER_WARNING);
								if ($options_images['gralnolinkimg']) {
									//	trigger_error( __('Deleted media img.', 'wpematico' ),E_USER_WARNING);
									$current_item['content'] = self::strip_Image_by_src($imagen_src, $current_item['content']);
								}
							} else {
								//here is the error

								$mirror = wp_upload_bits($newimgname, NULL, $bits);


								if (!$mirror['error']) {
									trigger_error($mirror['url'], E_USER_NOTICE);
									$current_item['content'] = str_replace($imagen_src, $mirror['url'], $current_item['content']);
									do_action('wpematico_new_image_url_uploaded', $mirror['url'], $imagen_src, $current_item, $campaign);
									$img_new_url[]			 = $mirror['url'];
								} else {
									trigger_error('wp_upload_bits error:' . print_r($mirror, true) . '.', E_USER_WARNING);
									// Si no quiere linkar las img al server borro el link de la imagen
									trigger_error(__('Upload file failed:', 'wpematico') . $imagen_dst, E_USER_WARNING);
									if ($options_images['gralnolinkimg']) {
										//	trigger_error( __('Deleted media img.', 'wpematico' ),E_USER_WARNING);
										$current_item['content'] = self::strip_Image_by_src($imagen_src, $current_item['content']);
									}
								}
							}
						}
					} else {
						trigger_error(__('Extension not allowed: ', 'wpematico') . urldecode($imagen_dst_url), E_USER_WARNING);
						if ($options_images['gralnolinkimg']) { // Si no quiere linkar las img al server borro el link de la imagen
							trigger_error(__('Stripped src.', 'wpematico'), E_USER_WARNING);
							$current_item['content'] = self::strip_Image_by_src($imagen_src, $current_item['content']);
						}
					}
				}
				$current_item['images']			 = (array) $img_new_url;
				if ($featured)
					$current_item['featured_image']	 = $current_item['images'][0]; //change to new url
			}  // // Si hay alguna imagen en el contenido
		} else {
			if (isset($current_item['images']) && sizeof($current_item['images'])) {
				trigger_error('<b>' . __('Using remotely linked images in content. No changes.', 'wpematico') . '</b>', E_USER_NOTICE);
			}
			$current_item['images'] = array();
		}
		return $current_item;
	}

// item images

	/**
	 *  // retrieves the attachment ID from the file URL
	 *  @return integer The attach ID of the image. If not exists return false.
	 */
	function get_attach_id_from_url($image_url) {
		global $wpdb;
		$attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $image_url));
		return ($attachment[0] > 0) ? $attachment[0] : FALSE;
	}

	/**
	 *  attach a file or image to a post with its post_id  
	 *  @return integer The new attach ID of the WP media
	 */
	function insertfileasattach($filename, $postid) {
		$wp_filetype	 = wp_check_filetype(basename($filename), null);
		$wp_upload_dir	 = wp_upload_dir();
		$relfilename	 = $wp_upload_dir['path'] . '/' . basename($filename);
		$guid			 = $wp_upload_dir['url'] . '/' . basename($filename);
		$attachment		 = array(
			'guid'			 => $guid,
			'post_mime_type' => $wp_filetype['type'],
			'post_title'	 => preg_replace('/\.[^.]+$/', '', basename($filename)),
			'post_content'	 => '',
			'post_author'	 => get_post_field('post_author', $postid),
			'post_status'	 => 'inherit'
		);
		trigger_error(__('Attaching file:') . $filename, E_USER_NOTICE);
		$attach_id		 = wp_insert_attachment($attachment, $relfilename, $postid);
		if (!$attach_id)
			trigger_error(__('Sorry, your attach could not be inserted. Something wrong happened.') . print_r($filename, true), E_USER_WARNING);

		if (!function_exists('wp_read_video_metadata') || !function_exists('wp_read_audio_metadata')) {
			require_once(ABSPATH . 'wp-admin/includes/media.php');
		}
		// must include the image.php file for the function wp_generate_attachment_metadata() to work
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		$attach_data = wp_generate_attachment_metadata($attach_id, $relfilename);
		wp_update_attachment_metadata($attach_id, $attach_data);

		return $attach_id;
	}

	/* 	static function Item_parseimg(&$current_item, &$campaign, &$feed, &$item) {
	  if ( stripos($current_item['content'], "[[[wpe1stimg]]]") !== FALSE ) {  // en el content
	  if (isset( $current_item['images'][0] )) {
	  $imgenc = $current_item['images'][0];
	  $imgstr = "<img class=\"wpe_imgrss\" src=\"" . $imgenc . "\">";  //Solo la imagen
	  }else{
	  trigger_error(__('Can\'t find the featured image to add to the content.'),E_USER_NOTICE);
	  $imgstr = '<!-- no image -->';
	  }
	  $current_item['content'] = str_ireplace("[[[wpe1stimg]]]",$imgstr, $current_item['content']);
	  }
	  return $current_item;
	  }
	 */

	/**
	 * @return void
	 * @since 1.7.2
	 */
	function featured_image_selector($current_item, $campaign, $feed, $item, $options_images) {
		if (isset($campaign['campaign_enable_featured_image_selector']) && $campaign['campaign_enable_featured_image_selector']) {
			trigger_error('<b>' . __('Executing featured image selector...', 'wpematico') . '</b>', E_USER_NOTICE);
			$index_selector = (int) $campaign['campaign_featured_selector_index'];
			if (isset($current_item['images'][$index_selector])) {
				trigger_error('<b>' . sprintf(__('Featured image "%s": %s', 'wpematico'), $index_selector, $current_item['images'][$index_selector]) . '</b>', E_USER_NOTICE);
				$current_item['featured_image'] = $current_item['images'][$index_selector];
			} else {
				trigger_error('<b>' . __('No image was found according to the selected index.', 'wpematico') . '</b>', E_USER_NOTICE);
				$images_array = $current_item['images'];
				if (isset($campaign['campaign_featured_selector_ifno']) && $campaign['campaign_featured_selector_ifno'] == 'first') {
					$first_image = array_shift($images_array);
					if (empty($first_image)) {
						trigger_error('<b>' . __('The first image was not found in the content.', 'wpematico') . '</b>', E_USER_NOTICE);
					} else {
						trigger_error('<b>' . __('Using first image as the featured image.', 'wpematico') . '</b>', E_USER_NOTICE);
						$current_item['featured_image'] = $first_image;
					}
				} else {
					$last_image = array_pop($images_array);
					if (empty($last_image)) {
						trigger_error('<b>' . __('The last image was not found in the content.', 'wpematico') . '</b>', E_USER_NOTICE);
					} else {
						trigger_error('<b>' . __('Using last image as the featured image.', 'wpematico') . '</b>', E_USER_NOTICE);
						$current_item['featured_image'] = $last_image;
					}
				}
			}
		}
		return $current_item;
	}

	/**
	 * Filters images, upload and replace on text item content
	 * @param   $current_item   array    Current post data to be saved
	 * @param   $campaign       array    Current campaign data
	 * @param   $item           object    SimplePie_Item object
	 */
	function Get_Item_images($current_item, $campaign, $feed, $item, $options_images) {
		if ($options_images['imgcache'] || $options_images['featuredimg']) {
			//$ItemImages = apply_filters('wpematico_item_images', 'CORE', $current_item, $campaign, $feed, $item, $options_images);
			$current_parser = apply_filters('wpematico_images_parser', 'default', $current_item, $campaign, $feed, $item, $options_images);
			if ($current_parser != 'default') {
				$images = apply_filters('wpematico_images_parser_' . $current_parser, array(), $current_item, $campaign, $feed, $item, $options_images);
			} else {
				$images = $this->parseImages($current_item['content'], $options_images);
			}

			$current_item['images']	 = $images[2];  //List of image URLs
			$current_item['content'] = $images[3];  //Replaced src by srcset(If exist and with larger images) in images.

			if ($this->cfg['nonstatic']) {
				$current_item['images'] = NoNStatic::imgfind($current_item, $campaign, $item);
			}
			$current_item['images'] = array_values(array_unique($current_item['images']));

			/**
			 * WP Filter: wpematico_get_item_images runs after get all the images from the item content.
			 * $current_item['images']: Has all images urls 
			 */
			$current_item = apply_filters('wpematico_get_item_images', $current_item, $campaign, $item, $options_images);

			foreach ($current_item['images'] as $ki => $image) {
				$new_image_url = urldecode($current_item['images'][$ki]);

				if (strpos($image, '//') === 0) {
					$new_image_url = 'http:' . $new_image_url;
				}

				if ($new_image_url != $current_item['images'][$ki]) {
					$current_item['content'] = str_replace($current_item['images'][$ki], $new_image_url, $current_item['content']);
				}
				$current_item['images'][$ki] = $new_image_url;
			}
		}
		return $current_item;
	}

	/*	 * * Devuelve todas las imagenes del contenido	 */

	static function parseImages($text, $options_images = array()) {
		$new_content = $text;

		$pattern_img = apply_filters('wpematico_pattern_img', '/<img[^>]+>/i');
		preg_match_all($pattern_img, $text, $result);
		$imgstr		 = implode('', $result[0]);

		preg_match_all('/<\s*img[^\>]*src\s*=\s*[\""\']?([^\""\'\s>]*)/', $imgstr, $out);
		$out[2] = $out[1];

		if (isset($options_images['image_srcset']) && $options_images['image_srcset']) {
			trigger_error(__("Getting srcset attribute...", 'wpematico'), E_USER_NOTICE);
			$images_array = (empty($result[0]) ? array() : $result[0]);

			foreach ($images_array as $img_tag) {
				$src_with_srcset = WPeMatico::get_attribute_value('src', $img_tag);
				if (in_array($src_with_srcset, $out[2])) {
					$srcset_string	 = WPeMatico::get_attribute_value('srcset', $img_tag);
					$pieces_srcset	 = explode(',', $srcset_string);
					$max_width		 = 0;
					$max_url		 = '';
					foreach ($pieces_srcset as $kps => $piece) {
						$piece				 = trim($piece);
						$pieces_url_srcset	 = explode(' ', $piece);
						$url_srcset			 = $pieces_url_srcset[0];
						$with				 = intval($pieces_url_srcset[1]);
						if ($with > $max_width) {
							$max_width	 = $with;
							$max_url	 = $url_srcset;
						}
					}

					if (($key_image = array_search($src_with_srcset, $out[2])) !== FALSE) {
						$new_image_tag		 = preg_replace('/\s*src\s*=\s*(["\']).*?\1/', 'src="' . $max_url . '"', $img_tag);
						$new_content		 = str_replace($img_tag, $new_image_tag, $new_content);
						$out[2][$key_image]	 = $max_url;
						/* Translator: %s: URL of a image URL value */
						trigger_error(sprintf(__("Overriding src attribute with value: %s from srcset.", 'wpematico'), $src_with_srcset), E_USER_NOTICE);
					}
				}
			}
			$array_pictures = WPeMatico::get_tags('picture', $new_content);
			foreach ($array_pictures as $picture_tag) {
				$array_sources = WPeMatico::get_tags('source', $picture_tag);
				foreach ($array_sources as $source_tag) {
					$srcset_string = WPeMatico::get_attribute_value('srcset', $source_tag);
					if (empty($srcset_string)) {
						continue;
					}
					$pieces_srcset	 = explode(',', $srcset_string);
					$max_width		 = 0;
					$max_url		 = '';
					foreach ($pieces_srcset as $kps => $piece) {
						$piece				 = trim($piece);
						$pieces_url_srcset	 = explode(' ', $piece);
						$url_srcset			 = $pieces_url_srcset[0];
						$with				 = intval($pieces_url_srcset[1]);
						if ($with > $max_width) {
							$max_width	 = $with;
							$max_url	 = $url_srcset;
						}
					}

					if (!empty($max_url)) {
						$out[2][] = $max_url;
					}
				}
			}
		}


		preg_match_all('/<link rel=\"(.+?)\" type=\"image\/jpg\" href=\"(.+?)\"(.+?)\/>/', $text, $out2); // for rel=enclosure
		array_push($out, $out2);  // sum all items to array 
		$out[3] = $new_content;
		return $out;
	}

	/*	 * * Delete images for its src	 */

	static function strip_Image_by_src($src, $content, $withlink = true) {
		trigger_error(sprintf(__("Removing: %s from content.", 'wpematico'), '"' . $src . '"'), E_USER_NOTICE);
		$img_src_real_scaped = addslashes($src);
		$img_src_real_scaped = addcslashes($img_src_real_scaped, "?.+*");

		if ($withlink) {
			$imgtag			 = '|<a(.+?)><img(.+?)src=["\']' . $img_src_real_scaped . '["\'](.*?)><\/a>|';
			$current_content = preg_replace($imgtag, '', $content);  //for tag img with a
			if (is_null($current_content)) {
				trigger_error(sprintf(__("Link with image URI not found in src.", 'wpematico'), '"' . $src . '"'), E_USER_NOTICE);
			} else {
				$content = $current_content;
				trigger_error(sprintf(__("Successfully removed with anchor link.", 'wpematico'), '"' . $src . '"'), E_USER_NOTICE);
			}
		}
		$imgtag			 = '|<img(.+?)src=["\']' . $img_src_real_scaped . '["\'](.*?)>|';
		$current_content = preg_replace($imgtag, '', $content);  //for tag img without a
		if (is_null($current_content)) {
			trigger_error(sprintf(__("Image URI not found in src.", 'wpematico'), '"' . $src . '"'), E_USER_NOTICE);
		} else {
			$content = $current_content;
			trigger_error(sprintf(__("Successfully removed.", 'wpematico'), '"' . $src . '"'), E_USER_NOTICE);
		}
		return $content;
	}

	/*	 * * Adds featured images from URLs instead of upload them *
	 * If fifu is not installed anyway save the meta fields
	 */

	static function url_meta_set_featured_image($featured_image, $current_item) {
		global $wpematico_fifu_meta;
		trigger_error(__('Setting up Featured Image From Url', 'wpematico'), E_USER_NOTICE);
		remove_action('save_post', 'fifu_save_properties');
		if (!empty($featured_image) && empty($wpematico_fifu_meta)) {
			trigger_error(__('Adding featured image post meta: ', 'wpematico') . $featured_image, E_USER_NOTICE);
			$featured_image = html_entity_decode(urldecode($featured_image), ENT_NOQUOTES, 'UTF-8');
			if (function_exists('fifu_is_on') && fifu_is_on('fifu_query_strings')) {
				$featured_image = preg_replace('/\?.*/', '', $featured_image);
			}
			$wpematico_fifu_meta					 = array();
			$wpematico_fifu_meta['fifu_image_url']	 = $featured_image;
			$wpematico_fifu_meta['fifu_image_alt']	 = $current_item['title'];
			$wpematico_fifu_meta					 = apply_filters('wpematico_fifu_meta', $wpematico_fifu_meta, $current_item);
			$featured_image							 = '';
		}
		return $featured_image;
	}

	static function url_meta_set_featured_image_setmeta($current_item, $campaign) {
		global $wpematico_fifu_meta;
		if (!empty($wpematico_fifu_meta)) {
			$current_item['meta'] = $wpematico_fifu_meta;
			add_filter('wpematico_featured_image_attach_id', function () {
				return 1; // avoid log message No Featured image
			}, 99, 0);
			add_action('wpematico_inserted_post', array(__CLASS__, 'set_attachment_from_url'), 10, 3);
		}
		return $current_item;
	}

	static function set_attachment_from_url($post_id, $campaign, $item) {
		global $wpematico_fifu_meta;
		if (!empty($wpematico_fifu_meta) && function_exists('fifu_dev_set_image')) {
			trigger_error(__('Fifu save set attachment from URL.', 'wpematico'), E_USER_NOTICE);
			fifu_dev_set_image($post_id, $wpematico_fifu_meta['fifu_image_url']);
		}
		$wpematico_fifu_meta = array();
	}

	/*	 * * END Adds featured images from URLs instead of upload them * */

	/**
	 * Strip anchors links and replace them with the anchor text
	 * @param string $text where search and replace
	 * @param array type $campaign 
	 * @return string $text with replaced links.
	 */
	public static function strip_links($text, $campaign = array()) {
		$tags = array();
		if (!empty($campaign['campaign_strip_links_options'])) {
			foreach ($campaign['campaign_strip_links_options'] as $k => $v) {
				if ($v) {
					if ($k != 'a' && $k != 'strip_domain') {
						$tags[] = $k;
					}
				}
			}
		}
		if (empty($tags) && !$campaign['campaign_strip_links_options']['a']) {
			$tags = array('iframe', 'script');
		}

		$index_script = array_search('script', $tags);
		if ($index_script !== FALSE) {
			$text = WPeMatico::strip_tags_content($text, '<script>', TRUE);
			unset($tags[$index_script]);
		}
		foreach ($tags as $tag) {
			while (preg_match('/<' . $tag . '(|\W[^>]*)>(.*)<\/' . $tag . '>/iusU', $text, $found)) {
				$text = str_replace($found[0], $found[2], $text);
			}
		}
		return preg_replace('/(<(' . join('|', $tags) . ')(|\W.*)\/>)/iusU', '', $text);
	}

	public static function strip_links_a($text, $campaign = array()) {
		$tags = array();
		if (!empty($campaign['campaign_strip_links_options'])) {
			foreach ($campaign['campaign_strip_links_options'] as $k => $v) {
				if ($v) {
					if ($k != 'script' && $k != 'iframe') {
						$tags[] = $k;
					}
				}
			}
		}

		if (empty($tags) && !$campaign['campaign_strip_links_options']['script'] && !$campaign['campaign_strip_links_options']['iframe']) {
			$tags = array('a');
		}

		foreach ($tags as $tag) {
			while (preg_match('/<' . $tag . '(|\W[^>]*)>(.*)<\/' . $tag . '>/iusU', $text, $found)) {
				$text = str_replace($found[0], $found[2], $text);
			}
		}
		return preg_replace('/(<(' . join('|', $tags) . ')(|\W.*)\/>)/iusU', '', $text);
	}

	static function wpematico_get_yt_rss_tags($content, $campaign, $feed, $item) {
		if (strpos($feed->feed_url, 'https://www.youtube.com/feeds/videos.xml') !== false) { 
			$ytvideoId = $item->get_item_tags('http://www.youtube.com/xml/schemas/2015', 'videoId');
			//iframe
			if (!$campaign['campaign_youtube_embed'] && !$campaign['campaign_youtube_sizes']) {
				$sizes = 'width="560" height="315"';
			}
			if (!$campaign['campaign_youtube_embed'] && $campaign['campaign_youtube_sizes']) {
				$sizes = ' width="' . $campaign['campaign_youtube_width'] . '" height="' . $campaign['campaign_youtube_height'] . '"';
			}
			//embed
			if ($campaign['campaign_youtube_embed'] && $campaign['campaign_youtube_sizes']) {
				$sizes = ' width="' . $campaign['campaign_youtube_width'] . '" height="' . $campaign['campaign_youtube_height'] . '"';
			}
			if ($campaign['campaign_youtube_embed'] && !$campaign['campaign_youtube_sizes']) {
				$sizes = "";
			}

			if ($campaign['campaign_youtube_embed']) {
				$video = "[embed$sizes]https://www.youtube.com/watch?v=" . $ytvideoId[0]['data'] . "[/embed]";
			} else {
				$video = '<iframe ' . $sizes . ' src="https://www.youtube.com/embed/' . $ytvideoId[0]['data'] . '" frameborder="0" allowfullscreen></iframe>';
			}
			trigger_error(__("Parsing Youtube video and feed item contents.", 'wpematico'), E_USER_NOTICE);
			$video		 = apply_filters('wpematico_yt_video', $video);
			$enclosures	 = $item->get_enclosures();
			$title		 = apply_filters('wpematico_yt_altimg', $enclosures[0]->title);
			$img		 = apply_filters('wpematico_yt_thumbnails', $enclosures[0]->thumbnails[0]);
			$description = apply_filters('wpematico_yt_description', $enclosures[0]->description);

			$image_html = "<img src=\"$img\" alt=\"$title\"><br>";
			if ($campaign['campaign_youtube_ign_image']) {
				$image_html = "";
			}

			$description_html = "<p>$description</p>";
			if ($campaign['campaign_youtube_ign_description']) {
				$description_html = "";
			}
			$content = "$image_html $video $description_html";
		}
		return $content;
	}

	public static function wpematico_get_yt_image($current_item, $campaign, $item, $options_images) {
		if ($campaign['campaign_youtube_ign_image']) {
			if ($campaign['campaign_youtube_image_only_featured']) {
				$enclosures = $item->get_enclosures();
				if (!empty($enclosures[0])) {
					if (!empty($enclosures[0]->thumbnails[0])) {
						$img = apply_filters('wpematico_yt_thumbnails', $enclosures[0]->thumbnails[0]);
						if (empty($current_item['images'])) {
							$current_item['images'][] = $img;
						}
					}
				}
			}
		}
		return $current_item;
	}

	/**
	 * Filters audios, upload and replace on text item content
	 * @param   $current_item   array    Current post data to be saved
	 * @param   $campaign       array    Current campaign data
	 * @param   $item           object    SimplePie_Item object
	 * @param   $options_audios array    Current audio options.
	 * @since 1.7.0
	 */
	function Get_Item_Audios($current_item, $campaign, $feed, $item, $options_audios) {
		if ($options_audios['audio_cache']) {
			$current_item['audios'] = $this->parseAudios($current_item['content']);

			$current_item			 = apply_filters('wpematico_get_item_audios', $current_item, $campaign, $item, $options_audios);
			//if( $this->cfg['nonstatic'] ) { 
			//$current_item['audios'] = NoNStatic::find_audios($current_item, $campaign, $item, $options_audios);
			//}
			$current_item['audios']	 = array_values(array_unique($current_item['audios']));
			foreach ($current_item['audios'] as $ki => $image) {
				if (strpos($image, '//') === 0) {
					$current_item['audios'][$ki] = 'http:' . $current_item['audios'][$ki];
				}
			}
		}
		return $current_item;
	}

	/**
	 * Filters audios, upload and replace on text item content
	 * @param   $text   	string   text of content of current post.
	 * @return  $audios 	array 	 Array of current audios on post content.
	 * @since 1.7.0
	 */
	function parseAudios($text) {

		$audios	 = array();
		if(!empty($text)){
			$dom	 = new DOMDocument();
			@$dom->loadHTML($text);
			$xpath	 = new DomXPath($dom);
			$nodes	 = $xpath->query('//audio | //audio/source');
			foreach ($nodes as $node) {
				$audios[] = $node->getAttribute('src');
			}
		}
		
		return $audios;
	}

	/**
	 * @since 1.7.0
	 */
	function strip_Audio_by_src($src, $content) {
		trigger_error(sprintf(__("Removing: %s from content.", 'wpematico'), '"' . $src . '"'), E_USER_NOTICE);
		$audio_src_real_scaped	 = addslashes($src);
		$audio_src_real_scaped	 = addcslashes($audio_src_real_scaped, "?.+*");
		$pattern				 = '|<audio(.+?)>(.*?)<source(.*?)src=["\']' . $audio_src_real_scaped . '["\'](.*?)>(.*?)<\/audio>|';
		$content_striped		 = preg_replace($pattern, '', $content);
		$content				 = ( is_null($content_striped) ) ? $content : $content_striped;
		return $content;
	}

	/**
	 * Filters audios, upload and replace on text item content
	 * @param   $current_item   array    Current post data to be saved
	 * @param   $campaign       array    Current campaign data
	 * @param   $feed           object    Feed database object
	 * @param   $item           object    SimplePie_Item object
	 * @param   $options_audios array    Current audio options.
	 * @since 1.7.0
	 */
	function Item_Audios(&$current_item, &$campaign, &$feed, &$item, $options_audios) {
		if ($options_audios['audio_cache']) {
			$itemUrl = $this->current_item['permalink'];

			if (sizeof($current_item['audios'])) { // If exist audios on content.
				trigger_error('<b>' . __('Looking for audios in content.', 'wpematico') . '</b>', E_USER_NOTICE);
				$audio_new_url_array = array();
				foreach ($current_item['audios'] as $audio_src) {

					trigger_error(__('Uploading media...', 'wpematico') . $audio_src, E_USER_NOTICE);
					$audio_src_real	 = $this->getRelativeUrl($itemUrl, $audio_src);
					// Strip all white space on audios URLs.	
					$audio_src_real	 = str_replace(' ', '%20', $audio_src_real);
					$audio_src_real	 = apply_filters('wpematico_audio_src_url', $audio_src_real); // original source
					$allowed_audio	 = (isset($this->cfg['audio_allowed_ext']) && !empty($this->cfg['audio_allowed_ext']) ) ? $this->cfg['audio_allowed_ext'] : 'mp3';
					$allowed_audio	 = apply_filters('wpematico_allowext_audio', $allowed_audio);

					// Compability with WP, Strip Query added by WP ShortCode
					$audio_src_without_query = $audio_src_real;
					if (substr($audio_src_without_query, -4) == '?_=1') {
						$audio_src_without_query = str_replace('?_=1', '', $audio_src_without_query);
					}
					// Store audio.	
					$new_audio_name	 = apply_filters('wpematico_new_audio_name', sanitize_file_name(urlencode(basename($audio_src_without_query))), $current_item, $options_audios, $item);  // new name here
					// Primero intento con mi funcion mas rapida
					$upload_dir		 = wp_upload_dir();
					$audio_dst		 = trailingslashit($upload_dir['path']) . $new_audio_name;
					$audio_dst_url	 = trailingslashit($upload_dir['url']) . $new_audio_name;
					trigger_error('Filtering audio extensions:' . $allowed_audio, E_USER_NOTICE);
					if (in_array(str_replace('.', '', strrchr(strtolower($audio_dst), '.')), explode(',', $allowed_audio))) {   // -------- Controlo extensiones permitidas
						trigger_error('Uploading media=' . $audio_src . ' <b>to</b> audio_dst=' . $audio_dst . '', E_USER_NOTICE);
						$newfile = false;
						if ($this->cfg['nonstatic'] && $options_audios['upload_ranges']) {
							$newfile = NoNStatic::partial_upload_file($audio_src_real, $audio_dst, $options_audios);
						}

						if ($options_audios['customupload_audios'] && !$newfile) {
							$newfile = WPeMatico::save_file_from_url($audio_src_real, $audio_dst);
						}

						if ($newfile) { //subió
							trigger_error('Uploaded media=' . $newfile, E_USER_NOTICE);
							$audio_dst				 = $newfile;
							$audio_dst_url			 = trailingslashit($upload_dir['url']) . basename($newfile);
							$current_item['content'] = str_replace($audio_src, $audio_dst_url, $current_item['content']);
							$audio_new_url_array[]	 = $audio_dst_url;
						} else { // falló -> intento con otros
							$bits	 = WPeMatico::wpematico_get_contents($audio_src_real);
							$mirror	 = wp_upload_bits($new_audio_name, NULL, $bits);
							if (!$mirror['error']) {
								trigger_error($mirror['url'], E_USER_NOTICE);
								$current_item['content'] = str_replace($audio_src, $mirror['url'], $current_item['content']);
								$audio_new_url_array[]	 = $mirror['url'];
							} else {
								trigger_error('wp_upload_bits error:' . print_r($mirror, true) . '.', E_USER_WARNING);
								// Si no quiere linkar los audios al server borro el link de la audio
								trigger_error(__('Upload file failed:', 'wpematico') . $audio_dst, E_USER_WARNING);
								if ($options_audios['gralnolink_audio']) {
									$current_item['content'] = $this->strip_Audio_by_src($audio_src, $current_item['content']);
								}
							}
						}
					} else {
						trigger_error(__('Extension not allowed: ', 'wpematico') . urldecode($audio_dst_url), E_USER_WARNING);
						if ($options_audios['gralnolink_audio']) { // Si no quiere linkar las img al server borro el link de la imagen
							trigger_error(__('Stripped src.', 'wpematico'), E_USER_WARNING);
							$current_item['content'] = $this->strip_Audio_by_src($audio_src, $current_item['content']);
						}
					}
				}
				$current_item['audios'] = (array) $audio_new_url_array;
			}  // // Si hay alguna imagen en el contenido
		} else {
			if (isset($current_item['audios']) && sizeof($current_item['audios'])) {
				trigger_error('<b>' . __('Using remotely linked audios in content. No changes.', 'wpematico') . '</b>', E_USER_NOTICE);
			}
			$current_item['audios'] = array();
		}
		return $current_item;
	}

// item audios

	/**
	 * Filters videos, upload and replace on text item content
	 * @param   $current_item   array    Current post data to be saved
	 * @param   $campaign       array    Current campaign data
	 * @param   $item           object    SimplePie_Item object
	 * @param   $options_videos array    Current video options.
	 * @since 1.7.0
	 */
	function Get_Item_Videos($current_item, $campaign, $feed, $item, $options_videos) {
		if ($options_videos['video_cache']) {
			$current_item['videos'] = $this->parseVideos($current_item['content']);
			
			$current_item = apply_filters('wpematico_get_item_videos', $current_item, $campaign, $item, $options_videos);

			//if( $this->cfg['nonstatic'] ) { 
			//$current_item['videos'] = NoNStatic::find_videos($current_item, $campaign,$item, $options_videos);
			//}
			$current_item['videos'] = array_values(array_unique($current_item['videos']));
			foreach ($current_item['videos'] as $ki => $image) {
				if (strpos($image, '//') === 0) {
					$current_item['videos'][$ki] = 'http:' . $current_item['videos'][$ki];
				}
			}
		}
		return $current_item;
	}

	/**
	 * Filters videos, upload and replace on text item content
	 * @param   $text   	string   text of content of current post.
	 * @return  $videos 	array 	 Array of current videos on post content.
	 * @since 1.7.0
	 */
	function parseVideos($text, $wiframes = false) {
		
		$videos	 = array();
		if(!empty($text)){
			$dom	 = new DOMDocument();
			@$dom->loadHTML($text);
			$xpath	 = new DomXPath($dom);
			if(!$wiframes){
				$nodes	 = $xpath->query('//video | //video/source');
			}else{
				$nodes	 = $xpath->query('//video | //video/source | //iframe');
			}
			foreach ($nodes as $node) {
				$videos[] = $node->getAttribute('src');
			}
		}
		return $videos;
	}

	/**
	 * @since 1.7.0
	 */
	function strip_Video_by_src($src, $content) {
		trigger_error(sprintf(__("Removing: %s from content.", 'wpematico'), '"' . $src . '"'), E_USER_NOTICE);
		$video_src_real_scaped	 = addslashes($src);
		$video_src_real_scaped	 = addcslashes($video_src_real_scaped, "?.+*");
		$pattern				 = '|<video(.+?)>(.*?)<source(.*?)src=["\']' . $video_src_real_scaped . '["\'](.*?)>(.*?)<\/video>|';
		$content_striped		 = preg_replace($pattern, '', $content);
		$content				 = ( is_null($content_striped) ) ? $content : $content_striped;
		return $content;
	}

	/**
	 * Filters videos, upload and replace on text item content
	 * @param   $current_item   array    Current post data to be saved
	 * @param   $campaign       array    Current campaign data
	 * @param   $feed           object    Feed database object
	 * @param   $item           object    SimplePie_Item object
	 * @param   $options_videos array    Current video options.
	 * @since 1.7.0
	 */
	function Item_Videos(&$current_item, &$campaign, &$feed, &$item, $options_videos) {
		if ($options_videos['video_cache']) {
			$itemUrl = $this->current_item['permalink'];

			if (sizeof($current_item['videos'])) { // If exist videos on content.
				trigger_error('<b>' . __('Looking for videos in content.', 'wpematico') . '</b>', E_USER_NOTICE);
				$video_new_url_array = array();
				foreach ($current_item['videos'] as $video_src) {

					trigger_error(__('Uploading media...', 'wpematico') . $video_src, E_USER_NOTICE);
					$video_src_real	 = $this->getRelativeUrl($itemUrl, $video_src);
					// Strip all white space on videos URLs.	
					$video_src_real	 = str_replace(' ', '%20', $video_src_real);
					$video_src_real	 = apply_filters('wpematico_video_src_url', $video_src_real); // original source
					$allowed_video	 = (isset($this->cfg['video_allowed_ext']) && !empty($this->cfg['video_allowed_ext']) ) ? $this->cfg['video_allowed_ext'] : 'mp4';
					$allowed_video	 = apply_filters('wpematico_allowext_video', $allowed_video);

					// Compability with WP, Strip Query added by WP ShortCode
					$video_src_without_query = $video_src_real;
					if (substr($video_src_without_query, -4) == '?_=1') {
						$video_src_without_query = str_replace('?_=1', '', $video_src_without_query);
					}
					// Store video.	
					$new_video_name	 = apply_filters('wpematico_new_video_name', sanitize_file_name(urlencode(basename($video_src_without_query))), $current_item, $campaign, $item);  // new name here
					// Primero intento con mi funcion mas rapida
					$upload_dir		 = wp_upload_dir();
					$video_dst		 = trailingslashit($upload_dir['path']) . $new_video_name;
					$video_dst_url	 = trailingslashit($upload_dir['url']) . $new_video_name;
					trigger_error('Filtering video extensions:' . $allowed_video, E_USER_NOTICE);
					if (in_array(str_replace('.', '', strrchr(strtolower($video_dst), '.')), explode(',', $allowed_video))) {   // -------- Controlo extensiones permitidas
						trigger_error('Uploading media=' . $video_src . ' <b>to</b> video_dst=' . $video_dst . '', E_USER_NOTICE);
						$newfile = false;

						if ($this->cfg['nonstatic'] && $options_videos['upload_ranges']) {
							$newfile = NoNStatic::partial_upload_file($video_src_real, $video_dst, $options_videos);
						}

						if ($options_videos['customupload_videos'] && !$newfile) {
							$newfile = WPeMatico::save_file_from_url($video_src_real, $video_dst);
						}

						if ($newfile) { //subió
							trigger_error('Uploaded media=' . $newfile, E_USER_NOTICE);
							$video_dst				 = $newfile;
							$video_dst_url			 = trailingslashit($upload_dir['url']) . basename($newfile);
							$current_item['content'] = str_replace($video_src, $video_dst_url, $current_item['content']);
							$video_new_url_array[]	 = $video_dst_url;
						} else { // falló -> intento con otros
							$bits	 = WPeMatico::wpematico_get_contents($video_src_real);
							$mirror	 = wp_upload_bits($new_video_name, NULL, $bits);
							if (!$mirror['error']) {
								trigger_error($mirror['url'], E_USER_NOTICE);
								$current_item['content'] = str_replace($video_src, $mirror['url'], $current_item['content']);
								$video_new_url_array[]	 = $mirror['url'];
							} else {
								trigger_error('wp_upload_bits error:' . print_r($mirror, true) . '.', E_USER_WARNING);
								// Si no quiere linkar los videos al server borro el link de la video
								trigger_error(__('Upload file failed:', 'wpematico') . $video_dst, E_USER_WARNING);
								if ($options_videos['gralnolink_video']) {
									$current_item['content'] = $this->strip_Video_by_src($video_src, $current_item['content']);
								}
							}
						}
					} else {
						trigger_error(__('Extension not allowed: ', 'wpematico') . urldecode($video_dst_url), E_USER_WARNING);
						if ($options_videos['gralnolink_video']) { // Si no quiere linkar las img al server borro el link de la imagen
							trigger_error(__('Stripped src.', 'wpematico'), E_USER_WARNING);
							$current_item['content'] = $this->strip_Video_by_src($video_src, $current_item['content']);
						}
					}
				}
				$current_item['videos'] = (array) $video_new_url_array;
			}  // // Si hay alguna imagen en el contenido
		} else {
			if (isset($current_item['videos']) && sizeof($current_item['videos'])) {
				trigger_error('<b>' . __('Using remotely linked videos in content. No changes.', 'wpematico') . '</b>', E_USER_NOTICE);
			}
			$current_item['videos'] = array();
		}
		return $current_item;
	}

	// item videos

	public static function wpematico_exclude_shorts($skip, $current_item, $campaign, $item){
		// Extract YouTube video ID
		$ytvideoId = $item->get_item_tags('http://www.youtube.com/xml/schemas/2015', 'videoId');
	
		if (!empty($ytvideoId)) {
			$ytvideoId = $ytvideoId[0]['data'];
			$url = "https://www.youtube.com/shorts/$ytvideoId";
	
			// Check if the campaign setting for only shorts is enabled
			$only_shorts_enabled = !empty($campaign['campaign_youtube_only_shorts']);
	
			// Check if the campaign setting for ignoring shorts is enabled
			$ignore_shorts_enabled = !empty($campaign['campaign_youtube_ign_shorts']);
	
			// Fetch headers
			$headers = get_headers($url, 1);
	
			// Check if the URL exists (returns true)
			$url_exists = strpos($headers[0], '200') !== false;
			
			// Determine whether to skip the item
			if ($only_shorts_enabled) {
				// No skip if the URL exists (shorts video)
				if(!$url_exists)
					trigger_error(__('Skipping standard videos...', 'wpematico'), E_USER_NOTICE);

				$skip = !$url_exists;
			} elseif ($ignore_shorts_enabled) {
				// Skip if the URL exists and ignoring shorts
				if($url_exists)
					trigger_error(__('Skipping Youtube Short...', 'wpematico'), E_USER_NOTICE);
				$skip = $url_exists;
			}
		}
		// Default behavior: do not skip
		return $skip;
	}
}

// class