<?php
/**
 * WPeMatico plugin for WordPress
 * Vimeo campaign type
 *
 * Publishes the videos of a Vimeo user, channel or group. Vimeo serves all three as
 * RSS, so this is a plain feed campaign with two additions: the address the user
 * pastes is turned into its feed, and each item is rendered as thumbnail + player +
 * description. Every feed is capped by Vimeo at its last 10 videos.
 *
 * @package   wpematico
 * @since     2.9
 */
// don't load directly
if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

if (!class_exists('WPeMatico_Campaign_Type_Vimeo')) :

	class WPeMatico_Campaign_Type_Vimeo {

		const TYPE = 'vimeo';

		public static function register() {
			add_filter('wpematico_rss_campaign_types', array(__CLASS__, 'rss_campaign_type'));
			add_filter('wpematico_campaign_type_options', array(__CLASS__, 'campaign_type_options'), 15);
			add_filter('wpematico_campaign_addon_fields', array(__CLASS__, 'campaign_fields'), 15, 2);
			add_filter('wpematico_simplepie_url', array(__CLASS__, 'simplepie_url'), 10, 3);
			add_action('Wpematico_init_fetching', array(__CLASS__, 'init_fetching'));

			if (is_admin()) {
				add_filter('wpematico_help_campaign', array(__CLASS__, 'help'), 15);
				add_action('wpematico_create_metaboxes', array(__CLASS__, 'metabox'), 15, 2);
				add_filter('wpematico_feed_viewer_params', array(__CLASS__, 'feed_viewer_params'), 10, 2);
				add_filter('wpematico_fetch_feed_params_test', array(__CLASS__, 'test_feed_params'), 10, 3);
			}
		}

		/**
		 * Vimeo is a feed, so the campaign takes the engine's standard fetch path and
		 * inherits its ordering, timeouts, duplicate handling and filters. It has no
		 * business replacing the SimplePie object.
		 */
		public static function rss_campaign_type($types) {
			$types[] = self::TYPE;
			return $types;
		}

		public static function campaign_type_options($options) {
			$options[] = array(
				'value' => self::TYPE,
				'text'	=> __('Vimeo', 'wpematico'),
				// cron-box included on purpose: without it the campaign has no way to set
				// its own schedule, which is the point of a campaign.
				'show'	=> array('feeds-box', 'vimeo-box', 'cron-box', 'template-box', 'images-box'),
				// Full Content scrapes the page behind the item's link, and a Vimeo
				// permalink is a JavaScript player, not an article -- there is nothing
				// there to read. The box belongs to no type's 'show' list because every
				// other type can use it, so this is the only way to take it off the
				// screen for this one.
				'hide'	=> array('fullcontent-box'),
			);
			return $options;
		}

		public static function campaign_fields($data, $post_data) {
			$data['campaign_vimeo_embed']				= !empty($post_data['campaign_vimeo_embed']);
			$data['campaign_vimeo_sizes']				= !empty($post_data['campaign_vimeo_sizes']);
			$data['campaign_vimeo_width']				= isset($post_data['campaign_vimeo_width']) ? (int) $post_data['campaign_vimeo_width'] : 0;
			$data['campaign_vimeo_height']				= isset($post_data['campaign_vimeo_height']) ? (int) $post_data['campaign_vimeo_height'] : 0;
			$data['campaign_vimeo_ign_image']			= !empty($post_data['campaign_vimeo_ign_image']);
			$data['campaign_vimeo_image_only_featured']	= !empty($post_data['campaign_vimeo_image_only_featured']);
			$data['campaign_vimeo_ign_description']		= !empty($post_data['campaign_vimeo_ign_description']);

			// The item's permalink is its Vimeo page, never an article to link back to,
			// so core's default of copying it as the post's permalink makes no sense here.
			if (isset($post_data['campaign_type']) && self::TYPE === $post_data['campaign_type']) {
				$data['copy_permanlink_source'] = false;
			}

			return $data;
		}

		/**
		 * Turns whatever the user pasted into the feed Vimeo publishes for it:
		 * a profile, a channel or a group, with or without the trailing /videos.
		 * Anything already ending in /rss, and anything that is not a vimeo.com
		 * address, is left exactly as it came.
		 */
		public static function rss_url($url) {
			$url = trim((string) $url);
			if ('' === $url || !preg_match('#^https?://(?:www\.)?vimeo\.com/#i', $url)) {
				return $url;
			}
			if (preg_match('#/rss/?$#i', $url)) {
				return $url;
			}
			$path = trim((string) wp_parse_url($url, PHP_URL_PATH), '/');
			if ('' === $path) {
				return $url;
			}
			$segments = explode('/', $path);
			// A single video inside a channel or group: drop the id and keep the source.
			if (count($segments) > 1 && ctype_digit(end($segments))) {
				array_pop($segments);
			}
			if ('videos' === end($segments)) {
				array_pop($segments);
			}
			if (empty($segments)) {
				return $url;
			}

			return 'https://vimeo.com/' . implode('/', $segments) . '/videos/rss';
		}

		/**
		 * The Feed Viewer inspects whatever address it is given, and the eye icon of
		 * the feeds box hands it the one stored in the campaign -- a Vimeo profile,
		 * channel or group, which is a web page and not a feed. So the tool reported
		 * a failure on sources the campaign fetches perfectly well. Resolve it the
		 * same way the campaign does; the viewer prints the address it ended up asking
		 * for, so nothing is hidden.
		 *
		 * @param array  $params
		 * @param string $url the address as typed
		 * @return array
		 */
		public static function feed_viewer_params($params, $url) {
			if (isset($params['url']) && is_string($params['url'])) {
				$params['url'] = self::rss_url($params['url']);
			}
			return $params;
		}

		/**
		 * Same story for the check button beside each feed of the campaign editor: it
		 * posts the address as typed and nothing else, so a Vimeo campaign had its
		 * source marked red and the screen opened with "cannot be parsed... the
		 * content-type is text/html" -- which is exactly what a Vimeo channel page is.
		 * Resolve it here; the reply names the feed it ended up testing.
		 *
		 * The campaign type never reaches this filter (the request carries only the
		 * URL), and it does not need to: rss_url() answers on the address alone and
		 * leaves anything that is not vimeo.com untouched.
		 *
		 * @param array $params
		 * @param int   $feed_key
		 * @param array $post_data
		 * @return array
		 */
		public static function test_feed_params($params, $feed_key, $post_data) {
			if (isset($params['url']) && is_string($params['url'])) {
				$params['url'] = self::rss_url($params['url']);
			}
			return $params;
		}

		public static function simplepie_url($feed_url, $feed_key, $campaign) {
			if (empty($campaign['campaign_type']) || self::TYPE !== $campaign['campaign_type']) {
				return $feed_url;
			}
			return self::rss_url($feed_url);
		}

		public static function init_fetching($campaign) {
			if (empty($campaign['campaign_type']) || self::TYPE !== $campaign['campaign_type']) {
				return;
			}
			add_filter('wpematico_get_post_content', array(__CLASS__, 'item_content'), 999, 4);
			add_filter('wpematico_get_item_images', array(__CLASS__, 'item_image'), 999, 4);
			add_filter('wpematico_newimgname', array(__CLASS__, 'image_filename'), 10, 3);
		}

		/** The player URL and the thumbnail of an item, or empty strings. */
		private static function media($item) {
			$media = array('player' => '', 'thumbnail' => '');
			$enclosure = $item->get_enclosure();
			if (!$enclosure) {
				return $media;
			}
			$media['player'] = (string) $enclosure->get_player();
			$thumbnails		 = $enclosure->get_thumbnails();
			if (!empty($thumbnails[0])) {
				// Never concatenate an extension: Vimeo serves these with a query string
				// (…-d_960?region=us), so appending .jpg breaks the URL.
				$media['thumbnail'] = (string) $thumbnails[0];
			}
			return $media;
		}

		/**
		 * Gives the downloaded thumbnail a name the engine will accept.
		 *
		 * ★ Vimeo serves its thumbnails from URLs with no file extension and a query
		 * string — `…-d_1920x1080?r=pad&region=us`. The engine derives the destination
		 * filename from the URL and then checks its extension against the allowed list;
		 * with no extension there is nothing to match, so **every Vimeo thumbnail was
		 * silently skipped**: a campaign with "Download images" on ended up with no
		 * attachment and no featured image, and the post kept pointing at i.vimeocdn.com.
		 * Measured: 3 posts, 0 attachments, 0 featured images.
		 *
		 * They are JPEG (`Content-Type: image/jpeg`), so the name gets the extension it
		 * always lacked. **Only the name** — appending `.jpg` to the source URL is what
		 * the old Vimeo addon did, and the URL is not ours to rewrite.
		 *
		 * @param string $newname
		 * @param array  $current_item
		 * @param array  $campaign
		 * @return string
		 */
		public static function image_filename($newname, $current_item, $campaign) {
			if (empty($campaign['campaign_type']) || self::TYPE !== $campaign['campaign_type']) {
				return $newname;
			}
			if ('' !== (string) pathinfo($newname, PATHINFO_EXTENSION)) {
				return $newname;
			}

			return $newname . '.jpg';
		}

		public static function item_content($current_item, $campaign, $feed, $item) {
			$media = self::media($item);
			if ('' === $media['player']) {
				return $current_item;
			}

			$sizes = ' width="640" height="360"';
			if (!empty($campaign['campaign_vimeo_sizes'])) {
				$sizes = ' width="' . (int) $campaign['campaign_vimeo_width'] . '" height="' . (int) $campaign['campaign_vimeo_height'] . '"';
			} elseif (!empty($campaign['campaign_vimeo_embed'])) {
				$sizes = '';
			}

			if (!empty($campaign['campaign_vimeo_embed'])) {
				$video = '[embed' . $sizes . ']' . esc_url($media['player']) . '[/embed]';
			} else {
				$video = '<iframe title="vimeo-player" src="' . esc_url($media['player']) . '"' . $sizes . ' frameborder="0" allowfullscreen></iframe>';
			}
			$video = apply_filters('wpematico_vimeo_video', $video, $current_item, $campaign, $item);

			// Title and description come from the feed item, which already carries both.
			// Reading them from the video page instead cost 423 ms per item and returned
			// a worse title ("K-9INE! in Vimeo Staff Picks" against "K-9INE!").
			$image = '';
			if (empty($campaign['campaign_vimeo_ign_image']) && '' !== $media['thumbnail']) {
				$image = '<img src="' . esc_url($media['thumbnail']) . '" alt="' . esc_attr($current_item['title']) . '" /><br />';
			}
			$description = '';
			if (empty($campaign['campaign_vimeo_ign_description'])) {
				$description = '<p>' . $current_item['content'] . '</p>';
			}

			trigger_error(esc_html__('Parsing Vimeo video and feed item contents.', 'wpematico'), E_USER_NOTICE);
			$current_item['content'] = $image . $video . $description;

			return $current_item;
		}

		public static function item_image($current_item, $campaign, $item, $options_images) {
			if (!empty($campaign['campaign_vimeo_ign_image']) && empty($campaign['campaign_vimeo_image_only_featured'])) {
				return $current_item;
			}
			if (!empty($current_item['images'])) {
				return $current_item;
			}
			$media = self::media($item);
			if ('' !== $media['thumbnail']) {
				$current_item['images'][] = apply_filters('wpematico_vimeo_thumbnails', $media['thumbnail'], $current_item, $campaign, $item);
			}
			return $current_item;
		}

		public static function help($helps) {
			// Keys are flattened into one $helptip array shared by every box, so they are
			// prefixed: a plain 'feed_url' here would silently replace the core tip of the
			// feeds box for every campaign, whatever its type.
			$helps['Vimeo'] = array(
				'vimeo_feed_url'  => array(
					'title' => __('Vimeo Campaign Type.', 'wpematico'),
					'tip'	=> __('Paste the address of a Vimeo user, channel or group and the campaign finds its feed on its own:', 'wpematico') .
					'<br />https://vimeo.com/username' .
					'<br />https://vimeo.com/channels/channelname' .
					'<br />https://vimeo.com/groups/groupname' .
					'<br /><br />' . __('Each item brings the title, the thumbnail, the player and the short description.', 'wpematico'),
					'plustip' => __('Vimeo publishes only the last 10 videos of each one, so a campaign that runs often enough never misses any.', 'wpematico'),
				),
				'vimeo_embed'	  => array(
					'title' => __('Use [embed].', 'wpematico'),
					'tip'	=> __('Wrap the video in the WordPress [embed] shortcode instead of the iframe Vimeo shares.', 'wpematico'),
				),
				'vimeo_sizes'	  => array(
					'title' => __('Video sizes.', 'wpematico'),
					'tip'	=> __('Width and height of the video frame in the post.', 'wpematico') . '<br />' .
					__('Leave them at 0 (zero) to keep it responsive.', 'wpematico'),
				),
			);
			return $helps;
		}

		public static function metabox($campaign_data, $cfg) {
			global $helptip;

			add_meta_box(
					'vimeo-box',
					'<span class="dashicons dashicons-video-alt3"> </span> ' . __('Vimeo Campaign Options', 'wpematico') .
					'<span class="dashicons dashicons-warning help_tip" title-heltip="' . $helptip['vimeo_feed_url'] . '" title="' . $helptip['vimeo_feed_url'] . '"></span>',
					array(__CLASS__, 'render_metabox'),
					'wpematico',
					'normal',
					'high'
			);
			add_action('admin_print_styles', array(__CLASS__, 'assets'));
		}

		public static function assets() {
			wp_enqueue_style('wpematico-campaign-type-vimeo', WPEMATICO_PLUGIN_URL . 'assets/css/campaign_type_vimeo.css', array('dashicons'), WPEMATICO_VERSION);
			wp_enqueue_script('wpematico-campaign-type-vimeo', WPEMATICO_PLUGIN_URL . 'assets/js/campaign_type_vimeo.js', array('jquery'), WPEMATICO_VERSION, true);
		}

		public static function render_metabox($post) {
			global $campaign_data, $helptip;

			$embed			  = !empty($campaign_data['campaign_vimeo_embed']);
			$sizes			  = !empty($campaign_data['campaign_vimeo_sizes']);
			$width			  = isset($campaign_data['campaign_vimeo_width']) ? (int) $campaign_data['campaign_vimeo_width'] : 0;
			$height			  = isset($campaign_data['campaign_vimeo_height']) ? (int) $campaign_data['campaign_vimeo_height'] : 0;
			$ign_image		  = !empty($campaign_data['campaign_vimeo_ign_image']);
			$only_featured	  = !empty($campaign_data['campaign_vimeo_image_only_featured']);
			$ign_description  = !empty($campaign_data['campaign_vimeo_ign_description']);
			?>
			<div class="wpe-vimeo-preview">
				<h4 id="wpe-vimeo-title-featured" style="display: <?php echo ($ign_image && !$only_featured) ? 'none' : 'flex'; ?>;"><?php esc_html_e('Featured Image', 'wpematico'); ?></h4>
				<div class="wpe-vimeo-featured" style="display: <?php echo ($ign_image && !$only_featured) ? 'none' : 'flex'; ?>;">
					<span class="dashicons dashicons-format-image"></span>
				</div>
				<h4 class="wpe-vimeo-post-title"><?php esc_html_e('Post Title', 'wpematico'); ?></h4>
				<div class="wpe-vimeo-image" style="display: <?php echo $ign_image ? 'none' : 'flex'; ?>;">
					<span class="dashicons dashicons-format-image"></span>
				</div>
				<div class="wpe-vimeo-video">
					<span class="dashicons dashicons-controls-play"></span>
				</div>
				<div class="wpe-vimeo-description" style="display: <?php echo $ign_description ? 'none' : 'flex'; ?>;">
					<?php esc_html_e('The short description of the video, as published on Vimeo.', 'wpematico'); ?>
				</div>
			</div>

			<div class="wpe-vimeo-options">
				<?php echo html_entity_decode($helptip['vimeo_feed_url'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?>
				<p></p>
				<label><input class="checkbox" type="checkbox" value="1" name="campaign_vimeo_embed" id="campaign_vimeo_embed" <?php checked($embed, true); ?> /> <?php esc_html_e('Use the [embed] WordPress shortcode instead of the Vimeo iframe.', 'wpematico'); ?></label>
				<span class="dashicons dashicons-warning help_tip" title-heltip="<?php echo $helptip['vimeo_embed']; ?>" title="<?php echo $helptip['vimeo_embed']; ?>"></span><br />
				<label><input class="checkbox" type="checkbox" value="1" name="campaign_vimeo_sizes" id="campaign_vimeo_sizes" <?php checked($sizes, true); ?> /> <?php esc_html_e('Change the size of the video frame.', 'wpematico'); ?></label>
				<span class="dashicons dashicons-warning help_tip" title-heltip="<?php echo $helptip['vimeo_sizes']; ?>" title="<?php echo $helptip['vimeo_sizes']; ?>"></span><br />
				<div id="div_campaign_vimeo_sizes" style="margin-top: 10px; margin-left: 17px; <?php echo $sizes ? '' : 'display: none;'; ?>">
					<label style="display: block; margin-bottom: 10px;"><?php esc_html_e('Width:', 'wpematico'); ?> <input class="small-text" type="number" min="0" name="campaign_vimeo_width" id="campaign_vimeo_width" value="<?php echo esc_attr($width); ?>" /></label>
					<label style="display: block;"><?php esc_html_e('Height:', 'wpematico'); ?> <input class="small-text" type="number" min="0" name="campaign_vimeo_height" id="campaign_vimeo_height" value="<?php echo esc_attr($height); ?>" /></label>
				</div>
				<p><strong><?php esc_html_e('Ignore:', 'wpematico'); ?></strong></p>
				<label><input class="checkbox" type="checkbox" value="1" name="campaign_vimeo_ign_image" id="campaign_vimeo_ign_image" <?php checked($ign_image, true); ?> /> <?php esc_html_e('Image', 'wpematico'); ?></label><br />
				<div id="div_vimeo_img_feature" style="margin-left: 17px; <?php echo $ign_image ? '' : 'display: none;'; ?>">
					<label><input class="checkbox" type="checkbox" value="1" name="campaign_vimeo_image_only_featured" id="campaign_vimeo_image_only_featured" <?php checked($only_featured, true); ?> /> <?php esc_html_e('Use it only as featured image', 'wpematico'); ?></label><br />
				</div>
				<label><input class="checkbox" type="checkbox" value="1" name="campaign_vimeo_ign_description" id="campaign_vimeo_ign_description" <?php checked($ign_description, true); ?> /> <?php esc_html_e('Description', 'wpematico'); ?></label><br />

				<?php
				/**
				 * Controls an addon adds to the Vimeo box.
				 *
				 * The type is core's and reads Vimeo over RSS, which is capped by Vimeo at
				 * the last 10 videos in one fixed order, public only. Professional lifts
				 * that with the Vimeo API and needs somewhere to put its own controls --
				 * without this action it would have to draw a second Vimeo box beside this
				 * one, which is not a UI, it is a workaround.
				 *
				 * @since 2.9
				 * @param array $campaign_data
				 */
				do_action('wpematico_vimeo_metabox_options', $campaign_data);

				// Nobody listening means Professional is not installed. This is where
				// somebody goes looking for a way past the 10-video cap named right
				// above, so the control is drawn dead rather than left out: a box that
				// says nothing about the API reads as "there is no API".
				if (!has_action('wpematico_vimeo_metabox_options')) :
					?>
					<div class="wpe-vimeo-api-options">
						<p>
							<strong><?php esc_html_e('Vimeo API', 'wpematico'); ?></strong>
							<span class="wpematico_badge-pro"><?php esc_html_e('PRO Feature', 'wpematico'); ?></span>
						</p>
						<label class="wpe-vimeo-api-disabled">
							<input class="checkbox" type="checkbox" disabled="disabled" />
							<?php esc_html_e('Fetch through the Vimeo API instead of the feed.', 'wpematico'); ?>
						</label>
						<p class="description">
							<?php esc_html_e('Professional reads Vimeo through its API: as many videos as the Items limit asks for instead of the last 10, in the order you choose, and your private and unlisted ones.', 'wpematico'); ?>
						</p>
					</div>
					<?php
				endif;
				?>
			</div>
			<?php
		}
	}

	WPeMatico_Campaign_Type_Vimeo::register();

endif;
