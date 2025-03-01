<?php
# @charset utf-8
if (!function_exists('add_filter'))
	exit;

// $cfg = get_option('WPeMatico_Options');
// $cfg = apply_filters('wpematico_check_options', $cfg);

if (!class_exists('WPeMatico')) {

	class WPeMatico extends WPeMatico_functions {

		const TEXTDOMAIN	 = 'wpematico';
		const PROREQUIRED	 = '3.1';
		const OPTION_KEY	 = 'WPeMatico_Options';

		public static $name		 = '';
		public static $version	 = '';
		public static $basen;/** Plugin basename * @var string	 */
		public static $uri		 = '';
		public static $dir		 = '';/** filesystem path to the plugin with trailing slash */
		public $options			 = array();

		public static function init() {

			$plugin_data	 = self::plugin_get_version(WPEMATICO_ROOTFILE);
			self :: $name	 = $plugin_data['Name'];
			self :: $version = $plugin_data['Version'];
			self :: $uri	 = plugin_dir_url(WPEMATICO_ROOTFILE);
			self :: $dir	 = plugin_dir_path(WPEMATICO_ROOTFILE);
			self :: $basen	 = plugin_basename(WPEMATICO_ROOTFILE);

			$wpematico_instance = new self(TRUE);
			$wpematico_instance->load_options();
		}
		

		/**
		 * constructor
		 *
		 * @access public
		 * @param bool $hook_in
		 * @return void
		 */
		public function __construct($hook_in = FALSE) {
			global $cfg;
			//Admin message
			//add_action('admin_notices', array( &$this, 'wpematico_admin_notice' ) ); 
			if (!$this->wpematico_env_checks())
				return;
			
			$this->load_options();

			if ($this->options['nonstatic'] && !class_exists('WPeMaticoPRO_Helpers')) {
				$this->options['nonstatic'] = false;
				$this->update_options();
			}
			add_action('admin_action_wpematico_export_settings', array($this, 'wpematico_export_settings'));
			add_action('admin_action_wpematico_import_settings', array($this, 'wpematico_import_settings'));
			$this->Create_campaigns_page();
			if ($hook_in) {
				add_action('admin_menu', array($this, 'admin_menu'));
				add_action('admin_init', array($this, 'admin_init'));

				add_action('admin_print_styles', array($this, 'all_WP_admin_styles'));
				add_action('in_admin_header', array($this, 'writing_settings_help'));

				wp_register_style('WPematStylesheet', self :: $uri . 'app/css/wpemat_styles.css');
				wp_register_script('WPemattiptip', self :: $uri . 'app/js/jquery.tipTip.minified.js', 'jQuery');
				wp_register_script('jquery-vsort', self ::$uri . 'app/js/jquery.vSort.min.js', array('jquery'));

				add_filter('wpematico_check_campaigndata', array(__CLASS__, 'check_campaigndata'), 10, 1);
				add_filter('wpematico_check_options', array(__CLASS__, 'check_options'), 10, 1);
			}
			//add Empty Trash folder buttons
			if ($this->options['emptytrashbutton']) {
				// Add button to list table for all post types
				add_action('restrict_manage_posts', array(&$this, 'add_button'), 90);
			}
			
			if(isset($cfg['enablemimetypes']) && $cfg['enablemimetypes']){
				self::wpematico_add_custom_mimetypes();
			}
			//Check timeout of running campaigns
			if ($this->options['campaign_timeout'] > 0) {
				$args		 = array('post_type' => 'wpematico', 'orderby' => 'ID', 'order' => 'ASC', 'numberposts' => -1);
				$campaigns	 = get_posts($args);
				foreach ($campaigns as $post) {
					$campaign	 = $this->get_campaign($post->ID);
					$starttime	 = @$campaign['starttime'];
					if ($starttime > 0) {
						$runtime = current_time('timestamp') - $starttime;
						if (($this->options['campaign_timeout'] <= $runtime)) {
							$campaign['lastrun']		 = $starttime;
							$campaign['lastruntime']	 = ' <span style="color:red;">Timeout: ' . $this->options['campaign_timeout'] . '</span>';
							$campaign['starttime']		 = '';
							$campaign['lastpostscount']	 = 0;
							$this->update_campaign($post->ID, $campaign);  //Save Campaign new data
						}
					}
				}
			}
		}
		
		/**
		 * Display empty trash button on list tables
		 * @return void
		 */
		public function add_button() {
			global $typenow, $post_type, $pagenow, $wp_post_types;
			// Don't show on comments list table
			if ('edit-comments.php' == $pagenow)
				return;
			// Don't show on trash page
			if (isset($_REQUEST['post_status']) && $_REQUEST['post_status'] == 'trash')
				return;
			// Don't show if current user is not allowed to edit other's posts for this post type
			if (empty($typenow))
				$typenow = $post_type;
			// Don't show if current user is not allowed to edit other's posts for this post type
			if (!current_user_can(get_post_type_object($typenow)->cap->edit_others_posts))
				return;
			// Don't show if there are no items in the trash for this post type
			if (0 == intval(wp_count_posts($typenow, 'readable')->trash))
				return;

			$display	 = false;
			$args		 = array();
			$output		 = 'names'; // names or objects
			$post_types	 = get_post_types($args, $output);
			foreach ($post_types as $post_t) {
				if ($post_t != $typenow)
					continue;
				if (isset($this->options['cpt_trashbutton'][$post_t]) && $this->options['cpt_trashbutton'][$post_t]) {
					$display = true;
				}
			}

			if (!$display)
				return;
			?><div class="alignright empty_trash"><?php
			submit_button(__('Empty Trash', 'wpematico'), 'apply', 'delete_all', false, array('onClick' => "jQuery('.post_status_page').val('trash');"));
			?></div><?php
		}

		/**
		 * admin menu custom post type
		 *
		 * @access public
		 * @return void
		 */
		public static function Create_campaigns_page() {

			//$NotCampaignsBanner = __('No campaign found', 'wpematico');
			$NotCampaignsBanner	 = "
			<div class=\"wpematico-smart-notification\">
			<div class=\"description-smart-notification\">
				<p class=\"parr-wpmatico-smart-notification\">
					<br>
					<strong>" . __('There is no campaigns yet.', 'wpematico') . "</strong>
					<br>
					" . __('You need to create a campaign to begin using WPeMatico.', 'wpematico') . "
					<br>
					" . __('If you need help, check out the tips to get started with WPeMatico or just ', 'wpematico') . "
					<a href=\"" . admin_url('post-new.php?post_type=wpematico') . "\" class=\"\">" . __('add New Campaign', 'wpematico') . "</a>
					<br>
					<br>
					<a href=\"" . admin_url('index.php?page=wpematico-getting-started') . "\" class=\"button button-primary button-hero\">" . __('Getting Started', 'wpematico') . "</a>
				</p>
				<br>
			</div>
			</div>";
			$labels				 = array(
				'name'				 => __('Campaigns', 'wpematico'),
				'singular_name'		 => __('Campaign', 'wpematico'),
				'add_new'			 => __('Add New', 'wpematico'),
				'add_new_item'		 => __('Add New Campaign', 'wpematico'),
				'edit_item'			 => __('Edit Campaign', 'wpematico'),
				'new_item'			 => __('New Campaign', 'wpematico'),
				'all_items'			 => __('All Campaigns', 'wpematico'),
				'view_item'			 => __('View Campaign', 'wpematico'),
				'search_items'		 => __('Search Campaign', 'wpematico'),
				'not_found'			 => $NotCampaignsBanner,
				'not_found_in_trash' => __('No Campaign found in Trash', 'wpematico'),
				'parent_item_colon'	 => '',
				'menu_name'			 => 'WPeMatico');
			$args				 = array(
				'labels'				 => $labels,
				//'public' => true,
				'public'				 => false,
				'exclude_from_search'	 => true,
				'publicly_queryable'	 => false,
				'show_ui'				 => true,
				'show_in_menu'			 => true,
				'query_var'				 => true,
				'rewrite'				 => true,
				'capability_type'		 => 'post',
				'has_archive'			 => true,
				'hierarchical'			 => false,
				'menu_position'			 => (get_option('wpem_menu_position')) ? 999 : 7,
				'menu_icon'				 => self :: $uri . '/images/robotico_orange-25x25.png',
				'register_meta_box_cb'	 => array('WPeMatico_Campaign_edit', 'create_meta_boxes'),
				'map_meta_cap'			 => true,
				'supports'				 => array('title', 'excerpt'));
			register_post_type('wpematico', $args);
		}

//

		/**
		 * admin_init
		 *
		 * @access public
		 * @return void
		 */
		public function admin_init() {
			$sect_title = '<img src="' . self :: $uri . '/images/robotico_orange-50x50.png' . '" style="margin: 0pt 2px -2px 0pt;">' . ' WPeMatico ' . WPEMATICO_VERSION;
			add_settings_section('wpematico', $sect_title, array($this, 'writing_settings'), 'writing');
			register_setting('writing', 'wpem_menu_position'); //, 'sanitize_callback' );
			register_setting('writing', 'wpem_show_locally_addons'); //, 'sanitize_callback' );
			register_setting('writing', 'wpem_hide_reviews'); //, 'sanitize_callback' );
			add_settings_field(
					'wpem_menu_position',
					__('Reset Menu Position', 'wpematico'),
					array($this, 'writing_wp_form'),
					'writing',
					'wpematico',
					array(//The array of arguments to pass to the callback.
						'id'			 => 'wpem_menu_position',
						'description'	 => __('Activate this setting if you can\'t see WPeMatico menu at left under Posts menu item.', 'wpematico')
					)
			);
			add_settings_field(
					'wpem_show_locally_addons',
					__('See local Addons in plugin list', 'wpematico'),
					array($this, 'writing_wp_form'),
					'writing',
					'wpematico',
					array(//The array of arguments to pass to the callback.
						'id'			 => 'wpem_show_locally_addons',
						'description'	 => __('Activate this setting if you have problems with the addons page to see them in your plugin page like any other plugin.', 'wpematico')
					)
			);
			add_settings_field(
					'wpem_hide_reviews',
					__('Hide Reviews on Settings', 'wpematico'),
					array($this, 'writing_wp_form'),
					'writing',
					'wpematico',
					array(//The array of arguments to pass to the callback. In this case, just a description.
						'id'			 => 'wpem_hide_reviews',
						'description'	 => __('Activate this setting if you don\'t see the WPeMatico Settings page complete or can\'t read externals URLs.', 'wpematico'),
					)
			);
		}

		/**
		 * Wordpress writing settings 
		 *
		 * @access public
		 * @return void
		 */
		public function writing_settings($arg) {
			echo "<p></p>";
		}

		public function writing_wp_form($args) {
			// Note the ID and the name attribute of the element match that of the ID in the call to add_settings_field
			$html = '<input type="checkbox" id="' . $args['id'] . '" name="' . $args['id'] . '" value="1" ' . checked(1, get_option($args['id']), false) . '/>';

			// Here, we will take the first argument of the array and add it to a label next to the checkbox
			$html .= '<label for="' . $args['id'] . '"> ' . $args['description'] . '</label>';

			echo $html;
			//echo print_r($args);
		}

		/**
		 * Add to Wordpress writing settings help
		 *
		 * @access public
		 * @return void
		 */
		public function writing_settings_help($arg) {
			$screen = get_current_screen();
			if ('options-writing' === $screen->base) {
				$screen->add_help_tab(array(
					'id'		 => 'wpematico',
					'title'		 => 'WPeMatico',
					'content'	 => '<p>' . __('If you don\'t see the WPeMatico Menu may be another plugin or a custom menu added by your theme are "overwritten" the WPeMatico menu position.', 'wpematico') . '<br />' .
					'' . __('Click the checkbox "Reset Menu Position" to show the menu on last position in your Wordpress menu.', 'wpematico') . '</p>' .
					'<p></p>' .
					'<p>' . __('If you can\'t see well the WPeMatico Settings page is probable that you are having problems to read external wordpress web pages from your server.', 'wpematico') . '<br />' .
					'' . __('Click the checkbox "Hide Reviews on Settings" to avoid this and show just a link to Wordpress reviews page.', 'wpematico') . '</p>' .
					'<p></p>' .
					'<p><a href="http://www.wpematico.com" target="_blank">WPeMatico WebPage</a>  -  <a href="https://etruel.com/downloads/category/wpematico-add-ons/" target="_blank">WPeMatico Add-Ons</a>  -  <a href="https://etruel.com/support/" target="_blank">etruel\'s Custom Support</a></p>' .
					'<p></p>' .
					'<p>' . __('You must click the Save Changes button at the bottom of the screen for new settings to take effect.', 'wpematico') . '</p>',
				));
			}
		}

		/**
		 * admin menu
		 *
		 * @access public
		 * @return void
		 */
		public function admin_menu() {
			$page = add_submenu_page(
					'edit.php?post_type=wpematico',
					__('Settings', 'wpematico'),
					__('Settings', 'wpematico'),
					'manage_options',
					'wpematico_settings',
					'wpematico_settings_page'
			);
			add_action('admin_print_styles-' . $page, array('WPeMatico_Settings', 'styles'));

			$page = add_submenu_page(
				'edit.php?post_type=wpematico',
				__('Tools', 'wpematico'),
				__('Tools', 'wpematico'),
				'manage_options',
				'wpematico_tools',
				'wpematico_tools_page'
			);
		add_action('admin_print_styles-' . $page, array('WPeMatico_Tools', 'styles'));
		}

		public function all_WP_admin_styles() {
			?><style type="text/css">
				.menu-icon-wpematico img {
					margin-top: -5px;
				}
			</style><?php
		}

		/**
		 * load_options in class options attribute
		 * 
		 * @access public 
		 * load array with options in class options attribute 
		 * @return void
		 */
		public function load_options() {
			global $cfg;
			$cfg = get_option(self :: OPTION_KEY);
			if (!$cfg) {
				/**
				 * Default values at 1st time
				 */
				$default_options						 = array();
				$default_options['set_stupidly_fast']	 = true;
				$default_options['disable_credits']		 = true;
				$default_options['wpematico_set_canonical'] = true;
				$this->options							 = $this->check_options($default_options);
				add_option(self :: OPTION_KEY, $this->options, '', 'yes');
			} else {
				$this->options = $this->check_options($cfg);
			}
			return;
		}

		public static function check_options($options) {
			$cfg['mailmethod']	 = (!isset($options['mailmethod'])) ? 'mail' : sanitize_text_field($options['mailmethod']);
			$cfg['mailsndemail'] = (!isset($options['mailsndemail'])) ? '' : sanitize_email($options['mailsndemail']);
			$cfg['mailsndname']	 = (!isset($options['mailsndname'])) ? '' : sanitize_text_field($options['mailsndname']);
			$cfg['mailsendmail'] = (!isset($options['mailsendmail'])) ? '' : untrailingslashit(str_replace('//', '/', str_replace('\\', '/', stripslashes($options['mailsendmail']))));
			$cfg['mailsecure']	 = (!isset($options['mailsecure'])) ? '' : sanitize_text_field($options['mailsecure']);
			$cfg['mailhost']	 = (!isset($options['mailhost'])) ? '' : sanitize_text_field($options['mailhost']);
			$cfg['mailport']	 = (!isset($options['mailport'])) ? '' : intval($options['mailport']);
			$cfg['mailuser']	 = (!isset($options['mailuser'])) ? '' : sanitize_text_field($options['mailuser']);
			$cfg['mailpass']	 = (!isset($options['mailpass'])) ? '' : sanitize_text_field($options['mailpass']);

			$cfg['disabledashboard']		 = (!isset($options['disabledashboard']) || empty($options['disabledashboard'])) ? false : ( ($options['disabledashboard'] == 1) ? true : false );
			$cfg['roles_widget']			 = (!isset($options['roles_widget']) || !is_array($options['roles_widget'])) ? array("administrator" => "administrator") : $options['roles_widget'];
			$cfg['dontruncron']				 = (!isset($options['dontruncron']) || empty($options['dontruncron'])) ? false : ( ($options['dontruncron'] == 1) ? true : false );
			$cfg['enable_alternate_wp_cron'] = (!isset($options['enable_alternate_wp_cron']) || empty($options['enable_alternate_wp_cron'])) ? false : ( ($options['enable_alternate_wp_cron'] == 1) ? true : false );

			$cfg['disablewpcron']		 = (!isset($options['disablewpcron']) || empty($options['disablewpcron'])) ? false : ( ($options['disablewpcron'] == 1) ? true : false );
			$cfg['set_cron_code']		 = (!isset($options['set_cron_code']) || empty($options['set_cron_code'])) ? false : ( ($options['set_cron_code'] == 1) ? true : false );
			$cfg['cron_code']			 = (!isset($options['cron_code'])) ? '' : sanitize_text_field($options['cron_code']);
			$cfg['logexternalcron']		 = (!isset($options['logexternalcron']) || empty($options['logexternalcron'])) ? false : ( ($options['logexternalcron'] == 1) ? true : false );
			$cfg['disable_credits']		 = (!isset($options['disable_credits']) || empty($options['disable_credits'])) ? false : ( ($options['disable_credits'] == 1) ? true : false );
			$cfg['disablecheckfeeds']	 = (!isset($options['disablecheckfeeds']) || empty($options['disablecheckfeeds'])) ? false : ( ($options['disablecheckfeeds'] == 1) ? true : false );
			$cfg['enabledelhash']		 = (!isset($options['enabledelhash']) || empty($options['enabledelhash'])) ? false : ( ($options['enabledelhash'] == 1) ? true : false );
			$cfg['enableseelog']		 = (!isset($options['enableseelog']) || empty($options['enableseelog'])) ? false : ( ($options['enableseelog'] == 1) ? true : false );
			$cfg['enablerewrite']		 = (!isset($options['enablerewrite']) || empty($options['enablerewrite'])) ? false : ( ($options['enablerewrite'] == 1) ? true : false );
			$cfg['enableword2cats']		 = (!isset($options['enableword2cats']) || empty($options['enableword2cats'])) ? false : ( ($options['enableword2cats'] == 1) ? true : false );
			$cfg['wpematico_set_canonical']	 = (!isset($options['wpematico_set_canonical']) || empty($options['wpematico_set_canonical'])) ? false : ( ($options['wpematico_set_canonical'] == 1) ? true : false );
			$cfg['customupload']		 = (!isset($options['customupload']) || empty($options['customupload'])) ? false : ( ($options['customupload'] == 1) ? true : false );
			$cfg['imgattach']			 = (!isset($options['imgattach']) || empty($options['imgattach'])) ? false : ( ($options['imgattach'] == 1) ? true : false );
			$cfg['imgcache']			 = (!isset($options['imgcache']) || empty($options['imgcache'])) ? false : ( ($options['imgcache'] == 1) ? true : false );
			if(defined( 'FIFU_PLUGIN_DIR' )){
				$cfg['fifu']				 = (!isset($options['fifu']) || empty($options['fifu'])) ? false : ( ($options['fifu'] == 1) ? true : false );
				$cfg['fifu-video']			 = (!isset($options['fifu-video']) || empty($options['fifu-video'])) ? false : ( ($options['fifu-video'] == 1) ? true : false );
			}else{
				$cfg['fifu']				 = false;
				$cfg['fifu-video']			 = false;
			}
			
			$cfg['gralnolinkimg']		 = (!isset($options['gralnolinkimg']) || empty($options['gralnolinkimg'])) ? false : ( ($options['gralnolinkimg'] == 1) ? true : false );
			$cfg['image_srcset']		 = (!isset($options['image_srcset']) || empty($options['image_srcset'])) ? false : ( ($options['image_srcset'] == 1) ? true : false );

			$cfg['audio_attach']		 = (!isset($options['audio_attach']) || empty($options['audio_attach'])) ? false : ( ($options['audio_attach'] == 1) ? true : false );
			$cfg['audio_cache']			 = (!isset($options['audio_cache']) || empty($options['audio_cache'])) ? false : ( ($options['audio_cache'] == 1) ? true : false );
			$cfg['gralnolink_audio']	 = (!isset($options['gralnolink_audio']) || empty($options['gralnolink_audio'])) ? false : ( ($options['gralnolink_audio'] == 1) ? true : false );
			$cfg['customupload_audios']	 = (!isset($options['customupload_audios']) || empty($options['customupload_audios'])) ? false : ( ($options['customupload_audios'] == 1) ? true : false );
			$audio_allowed_ext			 = self::get_audios_allowed_mimes(); //'mp4';
			$cfg['audio_allowed_ext']	 = (!isset($options['audio_allowed_ext'])) ? $audio_allowed_ext : sanitize_text_field($options['audio_allowed_ext']);
			$cfg['audio_allowed_ext']	 = str_replace(' ', '', $cfg['audio_allowed_ext']);  // strip spaces from string			

			$cfg['video_attach']		 = (!isset($options['video_attach']) || empty($options['video_attach'])) ? false : ( ($options['video_attach'] == 1) ? true : false );
			$cfg['video_cache']			 = (!isset($options['video_cache']) || empty($options['video_cache'])) ? false : ( ($options['video_cache'] == 1) ? true : false );
			$cfg['gralnolink_video']	 = (!isset($options['gralnolink_video']) || empty($options['gralnolink_video'])) ? false : ( ($options['gralnolink_video'] == 1) ? true : false );
			$cfg['customupload_videos']	 = (!isset($options['customupload_videos']) || empty($options['customupload_videos'])) ? false : ( ($options['customupload_videos'] == 1) ? true : false );
			$video_allowed_ext			 = self::get_videos_allowed_mimes(); //'mp4';
			$cfg['video_allowed_ext']	 = (!isset($options['video_allowed_ext'])) ? $video_allowed_ext : sanitize_text_field($options['video_allowed_ext']);
			$cfg['video_allowed_ext']	 = str_replace(' ', '', $cfg['video_allowed_ext']);  // strip spaces from string			

			$images_allowed_ext			 = self::get_images_allowed_mimes(); //'jpg,gif,png,tif,bmp,jpeg';
			$cfg['images_allowed_ext']	 = (!isset($options['images_allowed_ext'])) ? $images_allowed_ext : sanitize_text_field($options['images_allowed_ext']);
			$cfg['images_allowed_ext']	 = str_replace(' ', '', $cfg['images_allowed_ext']);  // strip spaces from string
			$cfg['enablemimetypes']		 = (!isset($options['enablemimetypes']) || empty($options['enablemimetypes'])) ? false : ( ($options['enablemimetypes'] == 1) ? true : false );
			$cfg['save_attr_images']		 = (!isset($options['save_attr_images']) || empty($options['save_attr_images'])) ? false : ( ($options['save_attr_images'] == 1) ? true : false );
			$cfg['featuredimg']			 = (!isset($options['featuredimg']) || empty($options['featuredimg'])) ? false : ( ($options['featuredimg'] == 1) ? true : false );
			$cfg['rmfeaturedimg']		 = (!isset($options['rmfeaturedimg']) || empty($options['rmfeaturedimg'])) ? false : ( ($options['rmfeaturedimg'] == 1) ? true : false );

			$cfg['force_mysimplepie']			 = (!isset($options['force_mysimplepie']) || empty($options['force_mysimplepie'])) ? false : ( ($options['force_mysimplepie'] == 1) ? true : false );
			$cfg['set_stupidly_fast']			 = (!isset($options['set_stupidly_fast']) || empty($options['set_stupidly_fast'])) ? false : ( ($options['set_stupidly_fast'] == 1) ? true : false );
			$cfg['simplepie_strip_htmltags']	 = (!isset($options['simplepie_strip_htmltags']) || empty($options['simplepie_strip_htmltags'])) ? false : ( ($options['simplepie_strip_htmltags'] == 1) ? true : false );
			$cfg['simplepie_strip_attributes']	 = (!isset($options['simplepie_strip_attributes']) || empty($options['simplepie_strip_attributes'])) ? false : ( ($options['simplepie_strip_attributes'] == 1) ? true : false );
			$cfg['strip_htmltags']				 = (!isset($options['strip_htmltags'])) ? '' : sanitize_text_field($options['strip_htmltags']);
			$cfg['strip_htmlattr']				 = (!isset($options['strip_htmlattr'])) ? '' : sanitize_text_field($options['strip_htmlattr']);

			$cfg['woutfilter']		 = (!isset($options['woutfilter']) || empty($options['woutfilter'])) ? false : ( ($options['woutfilter'] == 1) ? true : false );
			$cfg['campaign_timeout'] = (!isset($options['campaign_timeout']) ) ? 300 : (int) $options['campaign_timeout'];
			$cfg['throttle']		 = (!isset($options['throttle']) ) ? 0 : (int) $options['throttle'];
			$cfg['allowduplicates']	 = (!isset($options['allowduplicates']) || empty($options['allowduplicates'])) ? false : ( ($options['allowduplicates'] == 1) ? true : false );
			$cfg['allowduptitle']	 = (!isset($options['allowduptitle']) || empty($options['allowduptitle'])) ? false : ( ($options['allowduptitle'] == 1) ? true : false );
			$cfg['allowduphash']	 = (!isset($options['allowduphash']) || empty($options['allowduphash'])) ? false : ( ($options['allowduphash'] == 1) ? true : false );
			$cfg['jumpduplicates']	 = (!isset($options['jumpduplicates']) || empty($options['jumpduplicates'])) ? false : ( ($options['jumpduplicates'] == 1) ? true : false );
			$cfg['disableccf']		 = (!isset($options['disableccf']) || empty($options['disableccf'])) ? false : ( ($options['disableccf'] == 1) ? true : false );

			$cfg['add_extra_duplicate_filter_meta_source'] = (!isset($options['add_extra_duplicate_filter_meta_source']) || empty($options['add_extra_duplicate_filter_meta_source'])) ? false : ( ($options['add_extra_duplicate_filter_meta_source'] == 1) ? true : false );

			$cfg['nonstatic']		 = (!isset($options['nonstatic']) || empty($options['nonstatic'])) ? false : ( ($options['nonstatic'] == 1) ? true : false );
			$cfg['emptytrashbutton'] = (!isset($options['emptytrashbutton']) || empty($options['emptytrashbutton'])) ? false : ( ($options['emptytrashbutton'] == 1) ? true : false );
			$cfg['cpt_trashbutton']	 = (!isset($options['cpt_trashbutton']) || !is_array($options['cpt_trashbutton'])) ? array('post' => 1, 'page' => 1) : $options['cpt_trashbutton'];

			$cfg['campaign_in_postslist']				 = (!isset($options['campaign_in_postslist']) || empty($options['campaign_in_postslist'])) ? false : ( ($options['campaign_in_postslist'] == 1) ? true : false );
			$cfg['column_campaign_pos']					 = (!isset($options['column_campaign_pos']) ) ? 2 : (int) $options['column_campaign_pos'];
			$cfg['disable_metaboxes_wpematico_posts']	 = (!isset($options['disable_metaboxes_wpematico_posts']) || empty($options['disable_metaboxes_wpematico_posts'])) ? false : ( ($options['disable_metaboxes_wpematico_posts'] == 1) ? true : false );

			$cfg['disable_categories_description']	 = (!isset($options['disable_categories_description']) || empty($options['disable_categories_description'])) ? false : ( ($options['disable_categories_description'] == 1) ? true : false );
			$cfg['enable_xml_upload']				 = (!isset($options['enable_xml_upload']) || empty($options['enable_xml_upload'])) ? false : ( ($options['enable_xml_upload'] == 1) ? true : false );
			$cfg['entity_decode_html']				 = (!isset($options['entity_decode_html']) || empty($options['entity_decode_html'])) ? false : ( ($options['entity_decode_html'] == 1) ? true : false );

			if(defined('MULTISITE') && MULTISITE){
				$cfg['disable_extensions_feed_page'] = true;
			}else{
				$cfg['disable_extensions_feed_page'] = (!isset($options['disable_extensions_feed_page']) || empty($options['disable_extensions_feed_page'])) ? false : ( ($options['disable_extensions_feed_page'] == 1) ? true : false );

			}
				
			//Disable Extensions feed Page. 
			return apply_filters('wpematico_more_options', $cfg, $options);
		}

		/**
		 * update_options
		 *
		 * @access protected
		 * @return bool True, if option was changed
		 */
		public function update_options() {
			return update_option(self :: OPTION_KEY, $this->options);
		}

		public static function wpematico_get_mime_type_by_extension($extension) {
			$mime_types_img = array(
				'ai'   => 'application/postscript, application/adobe.illustrator, application/illustrator',
				'bmp'  => 'image/bmp',
				'gif'  => 'image/gif',
				'ico'  => 'image/x-icon',
				'jpeg' => 'image/jpeg',
				'jpg'  => 'image/jpeg',
				'png'  => 'image/png',
				'ps'   => 'application/postscript',
				'psd'  => 'image/vnd.adobe.photoshop',
				'svg'  => 'image/svg+xml',
				'tif'  => 'image/tiff',
				'tiff' => 'image/tiff',
				'webp' => 'image/webp',
				'apng' => 'image/apng',
				'avif' => 'image/avif',
				'jfif' => 'image/jpeg',
				'pjpeg' => 'image/jpeg',
				'pjp' => 'image/jpeg',
			);
		
			// Return the MIME type if it exists, otherwise, return a default value
			return isset($mime_types_img[$extension]) ? $mime_types_img[$extension] : array();
		}

		public static function wpematico_add_custom_mimetypes($mimetypes=array()){
			global $cfg;
			$allowed = (isset($cfg['images_allowed_ext']) && !empty($cfg['images_allowed_ext'])) ? $cfg['images_allowed_ext'] : 'jpg,gif,png,tif,bmp,jpeg';
			$allowed = apply_filters('wpematico_allowext', $allowed);
			$allowedArray = explode(',', $allowed);
			
			$allowedWP = explode(',', self::get_images_allowed_mimes());
			
			$arrayDiff = array_diff($allowedArray, $allowedWP);
			
			foreach ($arrayDiff as $diffExtension) {
				$customMimeType = self::wpematico_get_mime_type_by_extension($diffExtension);
				
				if (!empty($customMimeType)) {
					$mimetypes[$diffExtension] = $customMimeType;
				}
			}
			add_filter('upload_mimes', function ($mimes) use ($mimetypes) {
				$mimes = array_merge($mimes, $mimetypes);
				return $mimes;
			});
		}

	}

	// Class WPeMatico
}