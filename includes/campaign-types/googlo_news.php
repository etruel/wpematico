<?php
/**
 * WPeMatico plugin for WordPress
 * Google News campaign type
 *
 * Publishes what Google News lists for a search, a topic or a publication. Google
 * serves every one of those pages as RSS, so this is a plain feed campaign with two
 * additions: the address the user pastes from the browser is turned into its feed,
 * and each item is tidied -- the publication's name comes off the headline and is
 * kept as data of its own.
 *
 * Every item links to Google News, which is what the feed carries. Finding the
 * article on the publication's own site belongs to an add-on, through
 * wpematico_item_permalink.
 *
 * @package   wpematico
 * @since     2.9.1
 */
// don't load directly
if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

if (!class_exists('WPeMatico_Campaign_Type_Google_News')) :

	class WPeMatico_Campaign_Type_Google_News {

		/** The slug the Googlo News add-on stored in its campaigns. */
		const TYPE = 'googlo_news';
		const HOST = 'news.google.com';

		public static function register() {
			add_filter('wpematico_rss_campaign_types', array(__CLASS__, 'rss_campaign_type'));
			add_filter('wpematico_campaign_type_options', array(__CLASS__, 'campaign_type_options'), 15);
			add_filter('wpematico_campaign_addon_fields', array(__CLASS__, 'campaign_fields'), 15, 2);
			add_filter('wpematico_simplepie_url', array(__CLASS__, 'simplepie_url'), 10, 3);
			add_action('Wpematico_init_fetching', array(__CLASS__, 'init_fetching'));

			if (is_admin()) {
				add_filter('wpematico_help_campaign', array(__CLASS__, 'help'), 15);
				add_action('wpematico_create_metaboxes', array(__CLASS__, 'metabox'), 15, 2);
				add_filter('wpematico_feed_viewer_params', array(__CLASS__, 'feed_params'));
				add_filter('wpematico_fetch_feed_params_test', array(__CLASS__, 'feed_params'));
				add_filter('wpematico_check_fetch_feed_params', array(__CLASS__, 'feed_params'));
				add_filter('wpematico_campaign_type_validate_feed_before_save', array(__CLASS__, 'rss_campaign_type'));
				add_filter('wpematico_check_error_message', array(__CLASS__, 'check_feeds'), 10, 2);
				add_filter('wpematico_template_tags_campaign_edit', array(__CLASS__, 'template_tags'), 20);
			}
		}

		public static function rss_campaign_type($types) {
			$types[] = self::TYPE;
			return $types;
		}

		public static function campaign_type_options($options) {
			$options[] = array(
				'value' => self::TYPE,
				'text'	=> __('Google News', 'wpematico'),
				'show'	=> array('feeds-box', 'googlenews-box', 'cron-box', 'template-box', 'images-box'),
			);
			return $options;
		}

		public static function campaign_fields($data, $post_data) {
			$data['campaign_googlenews_keep_source']	= !empty($post_data['campaign_googlenews_keep_source']);
			$data['campaign_googlenews_related']		= !empty($post_data['campaign_googlenews_related']);

			return $data;
		}

		private static function is_ours($campaign) {
			return !empty($campaign['campaign_type']) && self::TYPE === $campaign['campaign_type'];
		}

		/**
		 * Turns the address of a Google News page into the feed Google publishes for
		 * it. Anything that is not a news.google.com address, and any page Google has
		 * no feed for, is left exactly as it came.
		 */
		public static function rss_url($url) {
			$url   = trim((string) $url);
			$parts = wp_parse_url($url);
			if (empty($parts['host']) || self::HOST !== strtolower($parts['host'])) {
				return $url;
			}

			$path = '/' . trim(isset($parts['path']) ? $parts['path'] : '', '/');
			if (!preg_match('#^/rss(/|$)#', $path)) {
				if (in_array($path, array('/', '/home', '/topstories', '/foryou'), true)) {
					$path = '/rss';
				} elseif (preg_match('#^/(search|topics|publications|headlines)(/|$)#', $path)) {
					$path = '/rss' . $path;
				} else {
					return $url;
				}
			}

			$query = array();
			if (!empty($parts['query'])) {
				wp_parse_str($parts['query'], $query);
			}
			// The page takes the search in "for"; its feed only answers to "q".
			if (isset($query['for']) && !isset($query['q'])) {
				$query['q'] = $query['for'];
			}
			unset($query['for']);

			// With no edition in the address Google answers with the United States one,
			// whatever the site speaks. It corrects a loose edition by itself, so the
			// site's language and country are enough.
			if (!isset($query['hl']) && !isset($query['gl']) && !isset($query['ceid'])) {
				$locale = explode('_', (string) get_locale());
				if (!empty($locale[0]) && !empty($locale[1]) && 2 === strlen($locale[1])) {
					$query['hl']   = $locale[0] . '-' . $locale[1];
					$query['gl']   = $locale[1];
					$query['ceid'] = $locale[1] . ':' . $locale[0];
				} elseif (!empty($locale[0])) {
					$query['hl'] = $locale[0];
				}
			}

			$feed = 'https://' . self::HOST . $path;
			if (!empty($query)) {
				$feed .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
			}

			return $feed;
		}

		/**
		 * The Feed Viewer and the check button of the feeds box are handed the address
		 * as typed and nothing else, so it is resolved on the address alone.
		 */
		public static function feed_params($params) {
			if (isset($params['url']) && is_string($params['url'])) {
				$params['url'] = self::rss_url($params['url']);
			}
			return $params;
		}

		public static function simplepie_url($feed_url, $feed_key, $campaign) {
			if (!self::is_ours($campaign) || !is_string($feed_url)) {
				return $feed_url;
			}
			return self::rss_url($feed_url);
		}

		/**
		 * A Google News campaign reads Google News: any other address in its feeds box
		 * is a mistake worth saying before the campaign is saved.
		 */
		public static function check_feeds($err_message, $post_data) {
			if (empty($post_data['campaign_type']) || self::TYPE !== $post_data['campaign_type'] || empty($post_data['campaign_feeds'])) {
				return $err_message;
			}
			// The editor posts the feeds box as one serialized string.
			$feeds = $post_data['campaign_feeds'];
			if (is_string($feeds)) {
				parse_str(wp_unslash($feeds), $posted);
				$feeds = isset($posted['campaign_feeds']) ? $posted['campaign_feeds'] : array();
			}
			foreach ((array) $feeds as $feed) {
				$feed = is_string($feed) ? trim($feed) : '';
				if ('' === $feed) {
					continue;
				}
				$host = wp_parse_url($feed, PHP_URL_HOST);
				if (!is_string($host) || self::HOST !== strtolower($host)) {
					$err_message .= ('' !== (string) $err_message) ? '<br />' : '';
					/* translators: %s: the address typed in the feeds box. */
					$err_message .= sprintf(__('%s is not a Google News address.', 'wpematico'), '<strong class="coderr">' . esc_html($feed) . '</strong>');
				}
			}
			return $err_message;
		}

		public static function init_fetching($campaign) {
			if (!self::is_ours($campaign)) {
				return;
			}
			add_filter('wpematico_get_post_title', array(__CLASS__, 'item_title'), 10, 4);
			add_filter('wpematico_get_post_content', array(__CLASS__, 'item_content'), 5, 4);
			add_filter('wpematico_meta_custom', array(__CLASS__, 'item_meta'), 10, 4);
			add_filter('wpematico_add_template_vars', array(__CLASS__, 'template_vars'), 10, 5);
			add_filter('wpematico_duplicates', array(__CLASS__, 'duplicated_title'), 10, 3);
		}

		/**
		 * The publication an item comes from, as the feed states it.
		 *
		 * @param object $item SimplePie item.
		 * @return array name and url, empty strings when the item has no source.
		 */
		public static function source($item) {
			$source = array('name' => '', 'url' => '');
			$tags	= is_object($item) ? $item->get_item_tags('', 'source') : null;
			if (empty($tags[0])) {
				return $source;
			}
			if (isset($tags[0]['data'])) {
				$source['name'] = trim(html_entity_decode((string) $tags[0]['data'], ENT_QUOTES | ENT_HTML401, 'UTF-8'));
			}
			if (isset($tags[0]['attribs']['']['url'])) {
				$source['url'] = esc_url_raw(trim((string) $tags[0]['attribs']['']['url']));
			}
			return $source;
		}

		/**
		 * The headline as the publication wrote it: Google News ends every title with
		 * " - Name of the publication".
		 *
		 * @param string $title
		 * @param object $item SimplePie item.
		 * @return string
		 */
		public static function headline($title, $item) {
			$source = self::source($item);
			$suffix = ' - ' . $source['name'];
			$title	= trim(wp_strip_all_tags((string) $title));
			if ('' !== $source['name'] && strlen($title) > strlen($suffix) && substr($title, -strlen($suffix)) === $suffix) {
				$title = rtrim(substr($title, 0, -strlen($suffix)));
			}
			return $title;
		}

		public static function item_title($title, $current_item, $campaign, $item) {
			if (!empty($campaign['campaign_googlenews_keep_source'])) {
				return $title;
			}
			return self::headline($title, $item);
		}

		/**
		 * Google's description is the headline as a link, the publication in a <font>
		 * tag and, on a topic, the other articles covering the same story. It is
		 * rebuilt from those same parts, with the headline pointing wherever the item
		 * points.
		 */
		public static function item_content($current_item, $campaign, $feed, $item) {
			if (!preg_match_all('#<a\s[^>]*href="([^"]+)"[^>]*>(.*?)</a>(?:\s|&nbsp;|\xC2\xA0)*(?:<font[^>]*>(.*?)</font>)?#is', (string) $current_item['content'], $links, PREG_SET_ORDER)) {
				return $current_item;
			}

			$source	   = self::source($item);
			$headline  = array_shift($links);
			$permalink = !empty($current_item['permalink']) ? $current_item['permalink'] : $headline[1];
			$name	   = ('' !== $source['name']) ? $source['name'] : (isset($headline[3]) ? wp_strip_all_tags($headline[3]) : '');

			$content = '<p><a href="' . esc_url($permalink) . '">' . wp_strip_all_tags($headline[2]) . '</a>';
			$content .= ('' !== $name) ? ' <em>' . esc_html($name) . '</em></p>' : '</p>';

			if (!empty($campaign['campaign_googlenews_related']) && !empty($links)) {
				$content .= '<ul>';
				foreach ($links as $link) {
					$content .= '<li><a href="' . esc_url($link[1]) . '">' . wp_strip_all_tags($link[2]) . '</a>';
					$content .= !empty($link[3]) ? ' <em>' . wp_strip_all_tags($link[3]) . '</em></li>' : '</li>';
				}
				$content .= '</ul>';
			}

			$current_item['content'] = $content;

			return $current_item;
		}

		public static function item_meta($current_item, $campaign, $feed, $item) {
			$source = self::source($item);
			if ('' === $source['name'] && '' === $source['url']) {
				return $current_item;
			}
			if (empty($current_item['meta']) || !is_array($current_item['meta'])) {
				$current_item['meta'] = array();
			}
			$current_item['meta']['wpe_source_name'] = $source['name'];
			$current_item['meta']['wpe_source_url']	 = $source['url'];

			return $current_item;
		}

		public static function template_vars($vars, $current_item, $campaign, $feed, $item) {
			$source = self::source($item);
			$vars['{source_name}'] = $source['name'];
			$vars['{source_url}']  = $source['url'];

			return $vars;
		}

		public static function template_tags($tags) {
			$tags[] = '{source_name}';
			$tags[] = '{source_url}';
			return $tags;
		}

		/**
		 * The duplicate check compares the feed's title with the posts already
		 * published, and the posts of this campaign carry the headline alone.
		 */
		public static function duplicated_title($duplicated, $campaign, $item) {
			global $wpdb;

			if ($duplicated || !empty($campaign['campaign_googlenews_keep_source']) || !empty($campaign['copy_permanlink_source'])) {
				return $duplicated;
			}

			$title = htmlspecialchars_decode((string) $item->get_title());
			$clean = self::headline($title, $item);
			if ($clean === $title) {
				return $duplicated;
			}
			$slug = sanitize_title(html_entity_decode(esc_attr($clean), ENT_QUOTES | ENT_HTML401, 'UTF-8'));
			if ('' === $slug) {
				return $duplicated;
			}

			$post_type = !empty($campaign['campaign_customposttype']) ? $campaign['campaign_customposttype'] : 'post';

			return (bool) $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type = %s LIMIT 1", $slug, $post_type));
		}

		public static function help($helps) {
			$helps['Google News'] = array(
				'googlenews_feed_url'	 => array(
					'title'	  => __('Google News Campaign Type.', 'wpematico'),
					'tip'	  => __('Paste the address of a Google News search, topic or publication as your browser shows it, and the campaign finds its feed on its own:', 'wpematico') .
					'<br />https://news.google.com/search?q=…' .
					'<br />https://news.google.com/topics/…' .
					'<br />https://news.google.com/publications/…' .
					'<br /><br />' . __('Each item brings the headline, the date and the name of the publication, and links to Google News.', 'wpematico'),
					'plustip' => __('The name and the address of the publication are saved with each post, and the post template takes them as {source_name} and {source_url}.', 'wpematico'),
				),
				'googlenews_keep_source' => array(
					'title' => __('Publication in the title.', 'wpematico'),
					'tip'	=> __('Google News ends every headline with the name of the publication. The campaign takes it off; tick this to keep it.', 'wpematico'),
				),
				'googlenews_related'	 => array(
					'title' => __('Related coverage.', 'wpematico'),
					'tip'	=> __('On a topic, Google News lists other articles about the same story under each headline. Tick this to add that list to the post.', 'wpematico'),
				),
			);
			return $helps;
		}

		public static function metabox($campaign_data, $cfg) {
			global $helptip;

			add_meta_box(
					'googlenews-box',
					'<span class="wpe-googlenews-icon"> </span> ' . __('Google News Campaign Options', 'wpematico') .
					'<span class="dashicons dashicons-warning help_tip" title-heltip="' . $helptip['googlenews_feed_url'] . '" title="' . $helptip['googlenews_feed_url'] . '"></span>',
					array(__CLASS__, 'render_metabox'),
					'wpematico',
					'normal',
					'high'
			);
			add_action('admin_print_styles', array(__CLASS__, 'assets'));
		}

		public static function assets() {
			wp_enqueue_style('wpematico-campaign-type-googlenews', WPEMATICO_PLUGIN_URL . 'assets/css/campaign_type_googlenews.css', array(), WPEMATICO_VERSION);
		}

		public static function render_metabox($post) {
			global $campaign_data, $helptip;

			$keep_source = !empty($campaign_data['campaign_googlenews_keep_source']);
			$related	 = !empty($campaign_data['campaign_googlenews_related']);
			?>
			<p><?php echo html_entity_decode($helptip['googlenews_feed_url'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
			<label><input class="checkbox" type="checkbox" value="1" name="campaign_googlenews_keep_source" id="campaign_googlenews_keep_source" <?php checked($keep_source, true); ?> /> <?php esc_html_e('Keep the name of the publication at the end of the title', 'wpematico'); ?></label>
			<span class="dashicons dashicons-warning help_tip" title-heltip="<?php echo $helptip['googlenews_keep_source']; ?>" title="<?php echo $helptip['googlenews_keep_source']; ?>"></span><br />
			<label><input class="checkbox" type="checkbox" value="1" name="campaign_googlenews_related" id="campaign_googlenews_related" <?php checked($related, true); ?> /> <?php esc_html_e('Add the related coverage Google News lists under the headline', 'wpematico'); ?></label>
			<span class="dashicons dashicons-warning help_tip" title-heltip="<?php echo $helptip['googlenews_related']; ?>" title="<?php echo $helptip['googlenews_related']; ?>"></span><br />

			<?php
			/**
			 * Controls an addon adds to the Google News box.
			 *
			 * @since 2.9.1
			 * @param array $campaign_data
			 */
			do_action('wpematico_googlenews_metabox_options', $campaign_data);

			if (!has_action('wpematico_googlenews_metabox_options')) :
				?>
				<p>
					<strong><?php esc_html_e('Original article', 'wpematico'); ?></strong>
					<span class="wpematico_badge-pro"><?php esc_html_e('PRO Feature', 'wpematico'); ?></span>
				</p>
				<label class="wpe-googlenews-disabled">
					<input class="checkbox" type="checkbox" disabled="disabled" />
					<?php esc_html_e('Link each post to the article on the publication\'s own site', 'wpematico'); ?>
				</label><br />
				<label class="wpe-googlenews-disabled">
					<input class="checkbox" type="checkbox" disabled="disabled" />
					<?php esc_html_e('Skip the item when its article is not found', 'wpematico'); ?>
				</label>
				<p class="description">
					<?php esc_html_e('Professional looks each headline up on the site of its publication, so the source link, the canonical URL and Full Content work on the article itself.', 'wpematico'); ?>
				</p>
				<?php
			endif;
		}
	}

	WPeMatico_Campaign_Type_Google_News::register();

endif;
