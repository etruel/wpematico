<?php
// don't load directly 
if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

if (!class_exists('WPeMatico_Campaigns')) :

	class WPeMatico_Campaigns {

		public static function hooks() {
            // Global actions & filters (always loaded)
            add_filter('post_updated_messages', array(__CLASS__, 'wpematico_updated_messages'));
            add_action('admin_notices', array(__CLASS__, 'wpematico_debug_notice'));
            add_action('admin_action_wpematico_copy_campaign', array(__CLASS__, 'wpematico_copy_campaign'));
            add_action('admin_action_wpematico_toggle_campaign', array(__CLASS__, 'wpematico_toggle_campaign'));
            add_action('admin_action_wpematico_reset_campaign', array(__CLASS__, 'wpematico_reset_campaign'));
            add_action('admin_action_wpematico_clear_campaign', array(__CLASS__, 'wpematico_clear_campaign'));
            add_action('admin_action_wpematico_delhash_campaign', array(__CLASS__, 'wpematico_delhash_campaign'));
            add_action('in_admin_header', array(__CLASS__, 'campaigns_list_help'));

            add_action('wp_ajax_manage_wpematico_save_bulk_edit', array(__CLASS__, 'manage_wpematico_save_bulk_edit'));
            add_action('wp_ajax_get_wpematico_categ_bulk_edit', array(__CLASS__, 'get_wpematico_categ_bulk_edit'));

            // Hooks specific to campaigns list view
            add_action('admin_init', array(__CLASS__, 'register_list_hooks'));
        }

        /**
         * Register hooks for the campaigns list screen only
         */
        public static function register_list_hooks() {
            global $pagenow;
            // Only on edit.php for wpematico post type
            if ($pagenow !== 'edit.php' || empty($_GET['post_type']) || $_GET['post_type'] !== 'wpematico') {
                return;
            }

            // Bulk actions
            add_filter('bulk_actions-edit-wpematico', array(__CLASS__, 'bulk_actions'), 10, 1);
            add_filter('handle_bulk_actions-edit-wpematico', array(__CLASS__, 'bulk_action_handler'), 10, 3);

            // Columns & sorting
            add_filter('manage_edit-wpematico_columns', array(__CLASS__, 'set_edit_wpematico_columns'));
            add_action('manage_wpematico_posts_custom_column', array(__CLASS__, 'custom_wpematico_column'), 10, 2);
            add_filter('post_row_actions', array(__CLASS__, 'wpematico_quick_actions'), 10, 2);
            add_filter('manage_edit-wpematico_sortable_columns', array(__CLASS__, 'sortable_columns'));
            add_action('pre_get_posts', array(__CLASS__, 'column_orderby'));
            add_filter('wp_kses_allowed_html', array(__CLASS__, 'custom_wpematico_kses_rules'), 10, 2);

            // Filters & query
            add_action('restrict_manage_posts', array(__CLASS__, 'custom_filters'));
            add_action('pre_get_posts', array(__CLASS__, 'query_set_custom_filters'));

            // Views & dropdowns
            add_filter('views_edit-wpematico', array(__CLASS__, 'my_views_filter'));
            add_filter('disable_months_dropdown', array(__CLASS__, 'disable_list_filters'), 10, 2);
            add_filter('disable_categories_dropdown', array(__CLASS__, 'disable_list_filters'), 10, 2);

            // Styles and scripts
            add_action('admin_print_styles-edit.php', array(__CLASS__, 'list_admin_styles'));
            add_action('admin_print_scripts-edit.php', array(__CLASS__, 'list_admin_scripts'));

            // Quick & bulk edit
            add_action('quick_edit_custom_box', array(__CLASS__, 'wpematico_add_to_quick_edit_custom_box'), 10, 2);
            add_filter('editable_slug', array(__CLASS__, 'inline_custom_fields'), 999, 1);

            // Run campaigns button
            add_action('restrict_manage_posts', array(__CLASS__, 'run_selected_campaigns'), 1, 2);
        }
			
		/**
		 * 
		 * @param type $actions
		 */
		public static function wpematico_debug_notice() {
			global $post_type, $current_screen;
			if ($post_type != 'wpematico')
				return;
			$danger_options = WPeMatico::get_danger_options();
			if ($danger_options['wpe_debug_logs_campaign']) {
				$class	 = 'notice notice-warning notice-alt';
				$message = esc_html__('WARNING! WPeMatico Debug mode has been activated at Tools->System Status->Danger Zone.', 'wpematico') . '<br />'
						. esc_html__('Be sure to deactivate it after your tests to avoid performance issues.', 'wpematico');
				printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		/**
		 * Static function bulk_actions
		 * @access public
		 * @return $actions Array of all actions 
		 * @since 1.8.5
		 */
		public static function bulk_actions($actions) {

			$current_screen = get_current_screen();
			if (!empty($current_screen)) {
				if ($current_screen->id == 'edit-wpematico' && (!empty($_GET['post_status']) && $_GET['post_status'] == 'trash' )) {
					return $actions;
				}
			}

			$new_actions					= array();
			$new_actions['start_campaigns'] = __('Start campaigns', 'wpematico');
			$new_actions['stop_campaigns']	= __('Stop campaigns', 'wpematico');
			$actions						= array_merge($new_actions, $actions);

			return $actions;
		}

		/**
		 * Static function bulk_action_handler
		 * @access public
		 * @return $redirect_to String with the URL to redirect 
		 * @since 1.8.5
		 */
		public static function bulk_action_handler($redirect_to, $doaction, $post_ids) {

			switch ($doaction) {
				case 'start_campaigns':
					foreach ($post_ids as $post_id) {
						self::bulk_toggle_campaign($post_id, 'activate');
					}
					/* translators: %s Integer. Number of activated campaigns */
					WPeMatico::add_wp_notice(array('text' => sprintf(__('%s Campaigns activated', 'wpematico'), count($post_ids)), 'below-h2' => false));
					break;
				case 'stop_campaigns':
					foreach ($post_ids as $post_id) {
						self::bulk_toggle_campaign($post_id, 'deactivate');
					}
					/* translators: %s Integer. Number of deactivated campaigns */
					WPeMatico::add_wp_notice(array('text' => sprintf(__('%s Campaigns deactivated', 'wpematico'), count($post_ids)), 'below-h2' => false));
					break;
			}
			$redirect_to = add_query_arg('bulk_wpematico', count($post_ids), $redirect_to);
			return $redirect_to;
		}

		/**
		 * Static function bulk_stop_start_campaign
		 * @access public
		 * @return void
		 * @since 1.8.5
		 */
		public static function bulk_toggle_campaign($id, $action) {
			$campaign_data = WPeMatico::get_campaign($id);
			if ($action == 'activate') {
				if (empty($campaign_data['activated'])) {
					$campaign_data['activated'] = !$campaign_data['activated'];
					WPeMatico::update_campaign($id, $campaign_data);
				}
			} else {
				if (!empty($campaign_data['activated'])) {
					$campaign_data['activated'] = !$campaign_data['activated'];
					WPeMatico::update_campaign($id, $campaign_data);
				}
			}
		}

		public static function campaigns_list_help() {
			global $post_type, $current_screen;
			if ($post_type != 'wpematico')
				return;
			if ($current_screen->id == 'edit-wpematico')
				require( dirname(__FILE__) . '/campaigns_list_help.php' );
		}

		public static function custom_filters($options) {
			global $typenow, $wp_query, $current_user, $pagenow, $cfg;
			if ($pagenow == 'edit.php' && is_admin() && $typenow == 'wpematico') {

				$options = WPeMatico_Campaign_edit::campaign_type_options();
				$readonly = ( count($options) == 1 ) ? 'disabled' : '';
				$campaign_type = (isset($_GET['campaign_type']) && !empty($_GET['campaign_type']) ) ? sanitize_text_field($_GET['campaign_type']) : '';
				?>
				<div style="display: inline-block;">
					<select id="campaign_type" name="campaign_type" style="display:inline;" <?php echo esc_attr($readonly); ?>>
						<option value="" <?php selected('', $campaign_type); ?>>
							<?php esc_html_e('Campaign Type', 'wpematico'); ?>
						</option>
						<?php foreach ($options as $key => $option) : ?>
							<option value="<?php echo esc_attr($option['value']); ?>" <?php selected($option['value'], $campaign_type); ?>>
								<?php echo esc_html($option['text']); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php
			}
		}

		// Show only posts and media related to logged in author
		public static function query_set_custom_filters($wp_query) {
			global $current_user, $pagenow, $typenow;
			if ($pagenow == 'edit.php' && is_admin() && $typenow == 'wpematico') {
				$campaign_type = (isset($_GET['campaign_type']) && !empty($_GET['campaign_type']) ) ? sanitize_text_field($_GET['campaign_type']) : '';

				$filtering = false;
				if (!empty($campaign_type)) {
					$filtering	  = true;
					$meta_query[] = array(
						array(
							'key'	  => 'campaign_data',
							'value'	  => serialize($campaign_type),
							'compare' => 'LIKE'
						)
					);
				}
				if ($filtering) {
					$wp_query->set('meta_query', $meta_query);
//				add_filter('views_edit-wpsellerevents',  array(__CLASS__,'fix_post_counts'));				
				}
			}
		}

		public static function run_selected_campaigns($post_type, $which) {
			global $typenow, $post_type, $pagenow;
			if ($post_type != 'wpematico')
				return;
			// Don't show on trash page
			if (isset($_REQUEST['post_status']) && $_REQUEST['post_status'] == 'trash')
				return;
			// Don't show if current user is not allowed to edit other's posts for this post type
			if (empty($typenow))
				$typenow = $post_type;
			if (!current_user_can(get_post_type_object($typenow)->cap->edit_others_posts))
				return;

			?><div style="margin: 1px 5px 0 0; float: left; background-color: #EB9600; color: #fff; border-color: #b97600 #b97600 #b97600; box-shadow: 0 1px 0 #b97600; text-decoration: none; text-shadow: 0 -1px 1px #b97600,1px 0 1px #b97600,0 1px 1px #b97600,-1px 0 1px #b97600;" id="run_all" onclick="run_all();" class="button">
				<?php esc_html_e('Run Selected Campaigns', 'wpematico'); ?> <span style="line-height: 1.4em;" class="dashicons dashicons-controls-forward"></span>
			</div><?php
			//self::bulk_actions($which);
		}

		public static function disable_list_filters($disable, $post_type) {
			global $post_type;
			if ($post_type == 'wpematico')
				return true;
			else
				return $disable;
		}

		public static function my_views_filter($links) {
			global $post_type;
			if ($post_type != 'wpematico')
				return $links;
			$links['wpematico'] = __('Visit', 'wpematico') . ' <a href="http://www.wpematico.com" target="_Blank" class="wpelinks">www.wpematico.com<span class="dashicons dashicons-external"></span></a> ';
			$links['etruelcom'] = ' <a href="https://etruel.com" target="_Blank" class="wpelinks">AddOns Store<span class="dashicons dashicons-external"></span></a>';
			return $links;
		}

		public static function list_admin_styles() {
			global $post_type;
			if ($post_type != 'wpematico')
				return;
			wp_enqueue_style('campaigns-list', WPeMatico::$uri . 'app/css/campaigns_list.css');
			wp_enqueue_style('wpematstyles', WPeMatico::$uri . 'app/css/wpemat_styles.css');
		}

		public static function list_admin_scripts() {
			global $post_type;

			if ($post_type != 'wpematico')
				return;

			wp_enqueue_script('wpematico-Date.phpformats', WPeMatico::$uri . 'app/js/Date.phpformats.js', array('jquery'), '', true);
			wp_enqueue_script('wpematico-bulk-quick-edit', WPeMatico::$uri . 'app/js/bulk_quick_edit.js', array('jquery', 'inline-edit-post'), '', true);
			wp_enqueue_script('wpematico-wpe-hooks', WPeMatico::$uri . 'app/js/wpe_hooks.js', array(), '', true);
			wp_enqueue_script('wpematico-campaign-list', WPeMatico::$uri . 'app/js/campaign_list.js', array('jquery'), WPEMATICO_VERSION, true);

			$wpematico_object = array(
				'date_format'					=> get_option('date_format') . ' ' . get_option('time_format'),
				'i18n_date_format'				=> date_i18n(get_option('date_format') . '-' . get_option('time_format')),
				'text_running_campaign'			=> __('Running Campaign...', 'wpematico'),
				'text_select_a_campaign_to_run' => __('Please select campaign(s) to Run.', 'wpematico'),
				'text_slug'						=> __('Slug', 'wpematico'),
				'text_password'					=> __('Password', 'wpematico'),
				'text_date'						=> __('Date', 'wpematico'),
				'run_now_list_nonce'			=> wp_create_nonce('wpematico-run-now-nonce'),
				'Notification_Hidding'			=> __('Hidding...', 'wpematico'),
				'Notification_Dismissed'		=> __('Dismissed', 'wpematico'),
				'campaigns_list_nonce'		=> wp_create_nonce('wpematico-campaigns-list-nonce'),
			);
			wp_localize_script('wpematico-campaign-list', 'wpematico_object', $wpematico_object);
		}

		/**
		 * ***********ACCION COPIAR 
		 */
		public static function copy_duplicate_campaign($post, $status = '', $parent_id = '') {
			if ($post->post_type != 'wpematico')
				return;
			$prefix = "";
			$suffix = __("(Copy)", 'wpematico');
			if (!empty($prefix))
				$prefix .= " ";
			if (!empty($suffix))
				$suffix = " " . $suffix;
			$status = 'publish';

			$new_post = array(
				'menu_order'	 => $post->menu_order,
				'guid'			 => $post->guid,
				'comment_status' => $post->comment_status,
				'ping_status'	 => $post->ping_status,
				'pinged'		 => $post->pinged,
				'post_author'	 => @$post->author,
				'post_content'	 => $post->post_content,
				'post_excerpt'	 => $post->post_excerpt,
				'post_mime_type' => $post->post_mime_type,
				'post_parent'	 => $post->post_parent,
				'post_password'	 => $post->post_password,
				'post_status'	 => $status,
				'post_title'	 => $prefix . $post->post_title . $suffix,
				'post_type'		 => $post->post_type,
				'to_ping'		 => $post->to_ping,
				'post_date'		 => $post->post_date,
				'post_date_gmt'	 => get_gmt_from_date($post->post_date)
			);

			$new_post_id = wp_insert_post($new_post);

			$post_meta_keys = get_post_custom_keys($post->ID);
			if (!empty($post_meta_keys)) {
				foreach ($post_meta_keys as $meta_key) {
					$meta_values = get_post_custom_values($meta_key, $post->ID);

					foreach ($meta_values as $meta_value) {
						$meta_value = maybe_unserialize($meta_value);

						add_post_meta($new_post_id, $meta_key, $meta_value);
					}
				}
			}
			$campaign_data				= WPeMatico::get_campaign($new_post_id);
			$campaign_data['activated'] = false;

			foreach ($campaign_data as $key => $value) {
				// Check if the key is a string and contains the literal string "regex"
				if (is_string($key) && strpos($key, 'regex') !== false) {
					//if the $value is string continue
					if (is_string($value))
					// Apply addslashes to the corresponding value
						$campaign_data[$key] = addslashes($value);
				}

				// If the value is an array, recursively apply the logic
				if (is_array($value)) {
					foreach ($value as $subKey => $subValue) {
						// Check if the subKey is a string and contains the literal string "regex"
						if (is_string($subKey) && strpos($subKey, 'regex') !== false) {
							//if the $subValue is string continue
							if (is_string($subValue))
							// Apply addslashes to the corresponding subValue
								$campaign_data[$key][$subKey] = addslashes($subValue);
						}

						if(is_array($campaign_data[$key][$subKey])){
							$lastValue = $campaign_data[$key][$subKey];
							foreach ($lastValue as $lastKey => $last ) {
								if(strpos($last, '\\'))
									$campaign_data[$key][$subKey][$lastKey] = addslashes($last);
							}	
						}
					}
				}
			}

			WPeMatico::update_campaign($new_post_id, $campaign_data);

			// If the copy is not a draft or a pending entry, we have to set a proper slug.
			/* if ($new_post_status != 'draft' || $new_post_status != 'auto-draft' || $new_post_status != 'pending' ){
			  $post_name = wp_unique_post_slug($post->post_name, $new_post_id, $new_post_status, $post->post_type, $new_post_parent);

			  $new_post = array();
			  $new_post['ID'] = $new_post_id;
			  $new_post['post_name'] = $post_name;

			  // Update the post into the database
			  wp_update_post( $new_post );
			  } */

			return $new_post_id;
		}

		public static function wpematico_copy_campaign($status = '') {
			if (!( isset($_GET['post']) || isset($_POST['post']) || ( isset($_REQUEST['action']) && 'wpematico_copy_campaign' == $_REQUEST['action'] ) )) {
				wp_die(esc_html__('No campaign ID has been supplied!', 'wpematico'));
			}
			$nonce = '';
			if (isset($_REQUEST['nonce'])) {
				$nonce = sanitize_text_field($_REQUEST['nonce']);
			}
			if (!wp_verify_nonce($nonce, 'wpe-action-nonce')) {
				wp_die('Are you sure?');
			}
			// Get the original post
			$id	  = (isset($_GET['post']) ? absint($_GET['post']) : absint($_POST['post']) );
			$post = get_post($id);

			// Copy the post and insert it
			if (isset($post) && $post != null) {
				$new_id = self::copy_duplicate_campaign($post, $status);

				if ($status == '') {
					// Redirect to the post list screen
					wp_redirect(admin_url('edit.php?post_type=' . $post->post_type));
				} else {
					// Redirect to the edit screen for the new draft post
					wp_redirect(admin_url('post.php?action=edit&post=' . $new_id));
				}
				exit;
			} else {
				$post_type_obj = get_post_type_object($post->post_type);
				wp_die( esc_html__('Copy campaign failed, could not find original:', 'wpematico') . ' ' . esc_html($id) );
			}
		}

		/**
		 * ***********FIN ACCION COPIAR 
		 */

		/**
		 * ***********ACCION TOGGLE 
		 */
		public static function wpematico_toggle_campaign($status = '') {
			if (!( isset($_GET['post']) || isset($_POST['post']) || ( isset($_REQUEST['action']) && 'wpematico_toggle_campaign' == $_REQUEST['action'] ) )) {
				wp_die(esc_html__('No campaign ID has been supplied!', 'wpematico'));
			}
			$nonce = '';
			if (isset($_REQUEST['nonce'])) {
				$nonce = sanitize_text_field($_REQUEST['nonce']);
			}
			if (!wp_verify_nonce($nonce, 'wpe-action-nonce')) {
				wp_die('Are you sure?');
			}
			// Get the original post
			$id = (isset($_GET['post']) ? absint($_GET['post']) : absint($_POST['post']) );

			$campaign_data				= WPeMatico::get_campaign($id);
			$campaign_data['activated'] = !$campaign_data['activated'];
			WPeMatico::update_campaign($id, $campaign_data);

			$notice = ($campaign_data['activated']) ? __('Campaign activated', 'wpematico') : __('Campaign Deactivated', 'wpematico');
			WPeMatico::add_wp_notice(array('text' => $notice . ' <b>' . get_the_title($id) . '</b>', 'below-h2' => false));

			// Redirect to the post list screen
			if (isset($_GET['campaign_edit'])) {
				wp_redirect(admin_url('post.php?action=edit&post=' . $id));
			} else {
				wp_redirect(admin_url('edit.php?post_type=wpematico'));
			}
		}

		/*		 * *******FIN ACCION TOGGLE 	 */

		/**		 * ***********ACCION RESET 	 */
		public static function wpematico_reset_campaign($status = '') {
			if (!( isset($_GET['post']) || isset($_POST['post']) || ( isset($_REQUEST['action']) && 'wpematico_reset_campaign' == $_REQUEST['action'] ) )) {
				wp_die(esc_html__('No campaign ID has been supplied!', 'wpematico'));
			}
			$nonce = '';
			if (isset($_REQUEST['nonce'])) {
				$nonce = sanitize_text_field($_REQUEST['nonce']);
			}
			if (!wp_verify_nonce($nonce, 'wpe-action-nonce')) {
				wp_die('Are you sure?');
			}
			// Get the original post
			$id								 = (isset($_GET['post']) ? absint($_GET['post']) : absint($_POST['post']) );
			$campaign_data					 = WPeMatico::get_campaign($id);
			$campaign_data['postscount']	 = 0;
			$campaign_data['lastpostscount'] = 0;
			$campaign_data['cronnextrun']	 = WPeMatico::time_cron_next($campaign_data['cron']); //set next run
			WPeMatico::update_campaign($id, $campaign_data);
			delete_post_meta($id, 'last_campaign_log');

			WPeMatico::add_wp_notice(array('text' => __('Reset Campaign', 'wpematico') . ' <b>' . get_the_title($id) . '</b>', 'below-h2' => false));
			// Redirect to the post list screen
			if (isset($_GET['campaign_edit'])) {
				wp_redirect(admin_url('post.php?action=edit&post=' . $id));
			} else {
				wp_redirect(admin_url('edit.php?post_type=wpematico'));
			}
		}

		/*		 * ************FIN ACCION RESET 	 */

		/**		 * ***********ACCION DELHASH	 	 */
		public static function wpematico_delhash_campaign() {
			if (!( isset($_GET['post']) || isset($_POST['post']) || ( isset($_REQUEST['action']) && 'wpematico_delhash_campaign' == $_REQUEST['action'] ) )) {
				wp_die(esc_html__('No campaign ID has been supplied!', 'wpematico'));
			}
			$nonce = '';
			if (isset($_REQUEST['nonce'])) {
				$nonce = sanitize_text_field($_REQUEST['nonce']);
			}
			if (!wp_verify_nonce($nonce, 'wpe-action-nonce')) {
				wp_die('Are you sure?');
			}
			// Get the original post
			$id			   = (isset($_GET['post']) ? absint($_GET['post']) : absint($_POST['post']) );
			$campaign_data = WPeMatico::get_campaign($id);
			foreach ($campaign_data['campaign_feeds'] as $feed) { // Grabo el ultimo hash de cada feed con 0
				$campaign_data[wpematico_feed_hash_key('campaign', $feed)]['lasthash'] = "0";
				$lasthashvar					  = '_lasthash_' . sanitize_file_name($feed);
				add_post_meta($id, $lasthashvar, "0", true) or
						update_post_meta($id, $lasthashvar, "0");

				$last_hashes_name = '_lasthashes_' . sanitize_file_name($feed);
				delete_post_meta($id, $last_hashes_name);
			}
			WPeMatico::update_campaign($id, $campaign_data);
			WPeMatico::add_wp_notice(array('text' => __('Hash deleted on campaign', 'wpematico') . ' <b>' . get_the_title($id) . '</b>', 'below-h2' => false));

			// Redirect to the post list screen
			if (isset($_GET['campaign_edit'])) {
				wp_redirect(admin_url('post.php?action=edit&post=' . $id));
			} else {
				wp_redirect(admin_url('edit.php?post_type=wpematico'));
			}
		}

		/*		 * ************FIN ACCION DELHASH	 */

		/**		 * ***********ACCION CLEAR: ABORT CAMPAIGN	 	 */
		public static function wpematico_clear_campaign() {
			if (!( isset($_GET['post']) || isset($_POST['post']) || ( isset($_REQUEST['action']) && 'wpematico_clear_campaign' == $_REQUEST['action'] ) )) {
				wp_die(esc_html__('No campaign ID has been supplied!', 'wpematico'));
			}
			$nonce = '';
			if (isset($_REQUEST['nonce'])) {
				$nonce = sanitize_text_field($_REQUEST['nonce']);
			}
			if (!wp_verify_nonce($nonce, 'wpe-action-nonce')) {
				wp_die('Are you sure?');
			}

			// Get the original post
			$id			   = (isset($_GET['post']) ? absint($_GET['post']) : absint($_POST['post']) );
			$campaign_data = WPeMatico::get_campaign($id);

			$campaign_data['cronnextrun'] = WPeMatico::time_cron_next($campaign_data['cron']); //set next run
			$campaign_data['stoptime']	  = current_time('timestamp');
			$campaign_data['lastrun']	  = $campaign_data['starttime'];
			$campaign_data['lastruntime'] = $campaign_data['stoptime'] - $campaign_data['starttime'];
			$campaign_data['starttime']	  = '';

			WPeMatico::update_campaign($id, $campaign_data);
			WPeMatico::add_wp_notice(array('text' => __('Campaign cleared', 'wpematico') . ' <b>' . get_the_title($id) . '</b>', 'below-h2' => false));

			// Redirect to the post list screen
			if (isset($_GET['campaign_edit'])) {
				wp_redirect(admin_url('post.php?action=edit&post=' . $id));
			} else {
				wp_redirect(admin_url('edit.php?post_type=wpematico'));
			}
		}

		/*		 * ************FIN ACCION DELHASH	 */

		public static function wpematico_updated_messages($messages) {
			global $post, $post_ID;
			$messages['wpematico'] = array(
				0  => '', // Unused. Messages start at index 1.
				1  => __('Campaign updated.', 'wpematico'),
				2  => __('Custom field updated.', 'wpematico'),
				3  => __('Custom field deleted.', 'wpematico'),
				4  => __('Campaign updated.', 'wpematico'),
				/* translators: %s: date and time of the revision */
				5  => isset($_GET['revision']) ? sprintf(__('Campaign restored to revision from %s', 'wpematico'), wp_post_revision_title(absint($_GET['revision']), false)) : false,
				6  => __('Campaign published.', 'wpematico'),
				7  => __('Campaign saved.', 'wpematico'),
				8  => __('Campaign submitted.', 'wpematico'),
						// translators: %1$s date as string, %2$s Preview campaign Link
				9  => sprintf(__('Campaign scheduled for: %1$s.', 'wpematico') . '<a target="_blank" href="%2$s">' . __('Preview campaign', 'wpematico') . '</a>',
						// translators: Publish box date format, see http://php.net/date
						date_i18n( __('M j, Y @ G:i', 'wpematico'), strtotime($post->post_date) ), 
						esc_url(add_query_arg('preview', 'true', get_permalink($post_ID))) ),
						// translators: %s Preview campaign Link
				10 => sprintf(__('Campaign draft updated. ', 'wpematico') . '<a target="_blank" href="%s">' . __('Preview campaign', 'wpematico') . '</a>', esc_url(add_query_arg('preview', 'true', get_permalink($post_ID)))),
			);

			return $messages;
		}

		public static function wpematico_action_link($id = 0, $context = 'display', $actionslug = '') {
			global $post;
			if (!$post == get_post($id))
				return;
			switch ($actionslug) {
				case 'copy':
					$action_name = "wpematico_copy_campaign";
					break;
				case 'toggle':
					$action_name = "wpematico_toggle_campaign";
					break;
				case 'reset':
					$action_name = "wpematico_reset_campaign";
					break;
				case 'delhash':
					$action_name = "wpematico_delhash_campaign";
					break;
				case 'clear':
					$action_name = "wpematico_clear_campaign";
					break;
			}
			$nonce	= wp_create_nonce('wpe-action-nonce');
			if ('display' == $context)
				$action = '?action=' . $action_name . '&amp;post=' . $post->ID . '&amp;nonce=' . $nonce;
			else
				$action = '?action=' . $action_name . '&post=' . $post->ID . '&nonce=' . $nonce;

			$post_type_object = get_post_type_object($post->post_type);
			if (!$post_type_object)
				return;

			return apply_filters('wpematico_action_link', admin_url("admin.php" . $action), $post->ID, $context);
		}

		//change actions from custom post type list
		static function wpematico_quick_actions($actions) {
			global $post, $post_type_object;
			if ($post->post_type == 'wpematico') {
				$can_edit_post = current_user_can('edit_post', $post->ID);
				$cfg		   = get_option(WPeMatico::OPTION_KEY);
//	//		unset( $actions['edit'] );
//			unset( $actions['view'] );
//	//		unset( $actions['trash'] );
//	//		unset( $actions['inline hide-if-no-js'] );
//			unset( $actions['clone'] );
//			unset( $actions['edit_as_new_draft'] );
				$actions	   = array();
				if ($can_edit_post && 'trash' != $post->post_status) {
					$actions['edit']				 = '<a href="' . get_edit_post_link($post->ID, true) . '" title="' . esc_attr(__('Edit this item', 'wpematico')) . '">' . __('Edit', 'wpematico') . '</a>';
					$actions['inline hide-if-no-js'] = '<a href="#" class="editinline" title="' . esc_attr(__('Edit this item inline', 'wpematico')) . '">' . __('Quick Edit', 'wpematico') . '</a>';
				}
				if (current_user_can('delete_post', $post->ID)) {
					if ('trash' == $post->post_status)
						$actions['untrash'] = "<a title='" . esc_attr(__('Restore this item from the Trash', 'wpematico')) . "' href='" . wp_nonce_url(admin_url(sprintf($post_type_object->_edit_link . '&amp;action=untrash', $post->ID)), 'untrash-post_' . $post->ID) . "'>" . __('Restore', 'wpematico') . "</a>";
					elseif (EMPTY_TRASH_DAYS)
						$actions['trash']	= "<a class='submitdelete' title='" . esc_attr(__('Move this item to the Trash', 'wpematico')) . "' href='" . get_delete_post_link($post->ID) . "'>" . __('Trash', 'wpematico') . "</a>";
					if ('trash' == $post->post_status || !EMPTY_TRASH_DAYS)
						$actions['delete']	= "<a class='submitdelete' title='" . esc_attr(__('Delete this item permanently', 'wpematico')) . "' href='" . get_delete_post_link($post->ID, '', true) . "'>" . __('Delete Permanently', 'wpematico') . "</a>";
				}
				if ('trash' != $post->post_status) {
					//++++++Toggle
					$campaign_data = WPeMatico::get_campaign($post->ID);
					
					$brokenCampaign = false;
					// Check if $campaign_data is a WP_Error object
					if (is_wp_error($campaign_data)) {
						$brokenCampaign = true;
						// Remove Professional filters for quick actions to allow only trash the broken campaign
						remove_filter('post_row_actions', array('WPeMaticoPRO_Helpers', 'wpematico_quick_actions'), 30);
						
					}
					if($brokenCampaign){
						unset($actions['edit']);
						unset($actions['inline hide-if-no-js']);
					}else{
						$starttime	   = @$campaign_data['starttime'];
						if (empty($starttime)) {
							/* 					$acnow = (bool)$campaign_data['activated'];
							  $atitle = ( $acnow ) ? esc_attr(__("Deactivate this campaign", 'wpematico')) : esc_attr(__("Activate schedule", 'wpematico'));
							  $alink = ($acnow) ? __("Deactivate", 'wpematico'): __("Activate",'wpematico');
							  $actions['toggle'] = '<a href="'.self :: wpematico_action_link( $post->ID , 'display','toggle').'" title="' . $atitle . '">' .  $alink . '</a>';
							 */
							//++++++Copy
							$actions['copy']	= '<a href="' . self::wpematico_action_link($post->ID, 'display', 'copy') . '" title="' . esc_attr(__("Clone this item", 'wpematico')) . '">' . __('Copy', 'wpematico') . '</a>';
							//++++++Reset
							$actions['reset']	= '<a href="' . self::wpematico_action_link($post->ID, 'display', 'reset') . '" title="' . esc_attr(__("Reset post count", 'wpematico')) . '">' . __('Reset', 'wpematico') . '</a>';
							//++++++runnow
							//$actions['runnow'] = '<a href="JavaScript:run_now(' . $post->ID . ');" title="' . esc_attr(__("Run Once", 'wpematico')) . '">' .  __('Run Now', 'wpematico') . '</a>';
							//++++++delhash
							if (@$cfg['enabledelhash']) // Si está habilitado en settings, lo muestra 
								$actions['delhash'] = '<a href="' . self::wpematico_action_link($post->ID, 'display', 'delhash') . '" title="' . esc_attr(__("Delete hash code for duplicates", 'wpematico')) . '">' . __('Del Hash', 'wpematico') . '</a>';
							//++++++seelog
							if (@$cfg['enableseelog']) {   // Si está habilitado en settings, lo muestra 
								$nonce	   = wp_create_nonce('clog-nonce');
								$nombre	   = get_the_title($post->ID);
								$actionurl = admin_url('admin-post.php?action=wpematico_campaign_log&p=' . $post->ID . '&_wpnonce=' . $nonce);
								$actionjs  = "javascript:window.open('$actionurl','$nombre','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=700, height=600');";

								$actions['seelog'] = '<a href="#" onclick="' . $actionjs . ' return false;" title="' . esc_attr(__("See last log of campaign. (Open a PopUp window)", 'wpematico')) . '">' . __('See Log', 'wpematico') . '</a>';
							}
						} else {  // Está en ejecución o quedó a la mitad
							unset($actions['edit']);
							unset($actions['inline hide-if-no-js']);
							$actions['clear'] = '<a href="' . self::wpematico_action_link($post->ID, 'display', 'clear') . '" title="' . esc_attr(__("Clear fetching and restore campaign", 'wpematico')) . '">' . __('Clear campaign', 'wpematico') . '</a>';
						}
					} // else $brokenCampaign
				}
			}
			return $actions;
		}

		static function inline_custom_fields($text) {
			global $post, $pagenow;
			if (($pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'wpematico' ) || ($pagenow == 'admin-ajax.php' && isset($post) && $post->post_type == 'wpematico' )) {
				$campaign_data = WPeMatico::get_campaign($post->ID);

				// Check if $campaign_data is a WP_Error object
				if (is_wp_error($campaign_data)) {
					// Handle error appropriately, possibly logging it and/or setting default values
					// $errorCode = "23456";
					// $errorMessage = "We'll just return here printing the error msg on its campaign row.";

					$errorCode	  = $campaign_data->get_error_code();
					$errorMessage = $campaign_data->get_error_message();

					// We'll just return here printing the error msg on hidden div.
					$text .= '</div>
						<div class="post_id">' . $post->ID . '</div>
						<div class="error_code">' . "Error retrieving campaign data: " . $errorMessage . '</div>
						<div class="error_message">' . $errorMessage;
					return $text;
				}

				/* Custom inline data for wpematico */
				$campaign_max			 = $campaign_data['campaign_max'];
				$campaign_feeddate		 = $campaign_data['campaign_feeddate'];
				$campaign_author		 = $campaign_data['campaign_author'];
				$campaign_linktosource	 = $campaign_data['campaign_linktosource'];
				$campaign_commentstatus	 = $campaign_data['campaign_commentstatus'];
				$campaign_allowpings	 = $campaign_data['campaign_allowpings'];
				$campaign_woutfilter	 = $campaign_data['campaign_woutfilter'];
				$campaign_strip_links	 = $campaign_data['campaign_strip_links'];
				$campaign_customposttype = $campaign_data['campaign_customposttype'];
				$campaign_posttype		 = $campaign_data['campaign_posttype'];
				$campaign_post_format	 = (isset($campaign_data['campaign_post_format']) && !empty($campaign_data['campaign_post_format']) ) ? $campaign_data['campaign_post_format'] : '0';
				$campaign_categories	 = (is_array($campaign_data['campaign_categories'])) ? $campaign_data['campaign_categories'] : array();
				$campaign_tags			 = (isset($campaign_data['campaign_tags'])) ? $campaign_data['campaign_tags'] : '';

				/* Concatenation of the $text variable with HTML elements and campaign data */
				$text .= '</div>
					<div class="post_id">' . $post->ID . '</div>
					<div class="campaign_max">' . $campaign_max . '</div>
					<div class="campaign_feeddate">' . $campaign_feeddate . '</div>
					<div class="campaign_author">' . $campaign_author . '</div>
					<div class="campaign_linktosource">' . $campaign_linktosource . '</div>
					<div class="campaign_commentstatus">' . $campaign_commentstatus . '</div>
					<div class="campaign_allowpings">' . $campaign_allowpings . '</div>
					<div class="campaign_woutfilter">' . $campaign_woutfilter . '</div>
					<div class="campaign_strip_links">' . $campaign_strip_links . '</div>
					<div class="campaign_customposttype">' . $campaign_customposttype . '</div>
					<div class="campaign_posttype">' . $campaign_posttype . '</div>
					<div class="campaign_post_format">' . $campaign_post_format . '</div>
					<div class="campaign_categories">' . implode(',', $campaign_categories) . '</div>
					<div class="campaign_tags">' . stripslashes($campaign_tags);
			}
			return $text;
		}

		static function set_edit_wpematico_columns($columns) { //this function display the columns headings
			return array(
				'cb'			=> '<input type="checkbox" />',
				'title'			=> __('Campaign Name', 'wpematico'),
				'status'		=> __('Publish as', 'wpematico'),
				'campaign_type' => __('Campaign Type', 'wpematico'),
				'next'			=> __('Current State', 'wpematico'),
				'last'			=> __('Last Run', 'wpematico'),
				'count'			=> __('Posts', 'wpematico'),
			);
		}

		static function custom_wpematico_column($column, $post_id) {
			$cfg			= get_option(WPeMatico::OPTION_KEY);
			$campaign_data	= WPeMatico::get_campaign($post_id);
			$brokenCampaign = false;

			// Check if $campaign_data is a WP_Error object
			if (is_wp_error($campaign_data)) {
				$brokenCampaign = true;
				// Handle error appropriately, possibly logging it and/or setting default values
				//$errorCode		= "23456";
				//$errorMessage	= "We'll just return here printing the error msg on its campaign row.";
				$errorCode		= $campaign_data->get_error_code();
				$errorMessage	= $campaign_data->get_error_message();
			}

			if ($brokenCampaign) {
				switch ($column) {

					case 'name':
					case 'title':
						?><div class="error_code"><?php echo esc_html__("Error retrieving campaign data: ", 'wpematico') . esc_html($errorCode); ?></div>
						<div class="error_message"><?php esc_html_e($errorMessage); ?></div><?php
						break;

					case 'campaign_type':
						echo '<div id="campaign_broken-' . esc_html($post_id) . '" style="color:#b32d2e;" value="">' . esc_html__('Broken campaign :(', 'wpematico') . '</div>';

						break;
				}
			} else {
				switch ($column) {
					case 'aaaaaaaaaa_name':

						//			$taxonomy_names = get_object_taxonomies( $campaign_customposttype );
						//			foreach ( $taxonomy_names as $taxonomy_name) {
						//				$taxonomy = get_taxonomy( $taxonomy_name );
						//
						//				if ( $taxonomy->hierarchical && $taxonomy->show_ui ) {
						//
						//					$terms = get_object_term_cache( $post_id, $taxonomy_name );
						//					if ( false === $terms ) {
						//						$terms = wp_get_object_terms( $post_id, $taxonomy_name );
						//						wp_cache_add( $post_id, $terms, $taxonomy_name . '_relationships' );
						//					}
						//					$term_ids = empty( $terms ) ? array() : wp_list_pluck( $terms, 'term_id' );
						//
						//					echo '<div class="post_category" id="' . $taxonomy_name . '_' . $post_id . '">' . implode( ',', $campaign_categories ) . '</div>';
						//
						//				} elseif ( $taxonomy->show_ui ) {
						//
						//					echo '<div class="tags_input" id="'.$taxonomy_name.'_'.$post_id.'">'
						//						. esc_html( str_replace( ',', ', ', get_terms_to_edit( $post_id, $taxonomy_name ) ) ) . '</div>';
						//
						//				}
						//			}

						break;
					case 'status':
						$get_post_type_object = isset(get_post_type_object($campaign_data['campaign_customposttype'])->labels->singular_name) ? get_post_type_object($campaign_data['campaign_customposttype'])->labels->singular_name : '';
						echo '<div id="campaign_posttype-' . esc_html($post_id) . '" value="' . esc_attr($campaign_data['campaign_posttype']) . '">' . esc_html($get_post_type_object) . '<br />';
						echo '' . esc_html__(get_post_status_object($campaign_data['campaign_posttype'])->label) . '</div>';
						break;
					case 'campaign_type':
						$CampaignTypestr	  = WPeMatico_Campaign_edit::get_campaign_type_by_field($campaign_data['campaign_type']);
						echo '<div class="center" id="campaign_type-' . esc_html($post_id) . '" value="' . esc_attr__($campaign_data['campaign_type']) . '">' . esc_html(str_replace(array(' (Default)', 'Fetcher'), '', $CampaignTypestr)) . '</div>';
						break;
					case 'count':
						$postscount			  = get_post_meta($post_id, 'postscount', true);
						echo (isset($postscount) && !empty($postscount) ) ? esc_html($postscount) : esc_html($campaign_data['postscount']);
						break;
					case 'next':   // 'Current State' column
						$starttime			  = (isset($campaign_data['starttime']) && !empty($campaign_data['starttime']) ) ? $campaign_data['starttime'] : 0;
						//print_r($campaign_data);
						$activated			  = (bool) $campaign_data['activated'];
						$atitle				  = ( $activated ) ? __("Stop and deactivate this campaign", 'wpematico') : __("Start/Activate Campaign Scheduler", 'wpematico');

						// NEW BUTTONS
						if ($starttime > 0) {  // Running play verde & grab rojo & stop gris
							$runtime = current_time('timestamp') - $starttime;
							if (($cfg['campaign_timeout'] <= $runtime) && ($cfg['campaign_timeout'] > 0)) {
								$campaign_data['lastrun']		 = $starttime;
								$campaign_data['lastruntime']	 = ' <span style="color:red;">Timeout: ' . $cfg['campaign_timeout'] . '</span>';
								$campaign_data['starttime']		 = '';
								$campaign_data['lastpostscount'] = 0; //  posts procesados esta vez
								WPeMatico::update_campaign($post_id, $campaign_data);  //Save Campaign new data
							}
							$ltitle	  = __('Running since:', 'wpematico') . ' ' . $runtime . ' ' . __('sec.', 'wpematico');
							$lbotones = '<button type="button" disabled class="state_buttons cpanelbutton dashicons dashicons-controls-play green"></button>';
							if ($activated) { // Active play green & grab rojo & stop gris
								$lbotones .= '<button type="button" disabled class="state_buttons cpanelbutton dashicons dashicons-update red"></button>'; // To activate
							} else {  // Inactive play verde & grab black & stop grey
								$lbotones .= '<button type="button" class="state_buttons cpanelbutton dashicons dashicons-update" btn-href="' . WPeMatico_Campaigns::wpematico_action_link($post_id, 'display', 'toggle') . '" title="' . $atitle . '"></button>'; // To activate
							}

							$lbotones .= '<button type="button" class="state_buttons cpanelbutton dashicons dashicons-controls-pause" btn-href="' . WPeMatico_Campaigns::wpematico_action_link($post_id, 'display', 'clear') . '" title="' . __('Break fetching and restore campaign', 'wpematico') . '"></button>'; // To deactivate
						} elseif ($activated) { // Running play gris & grab rojo & stop gris
							$cronnextrun = WPeMatico::time_cron_next($campaign_data['cron']);
							$cronnextrun = (isset($cronnextrun) && !empty($cronnextrun) && ($cronnextrun > 0 ) ) ? $cronnextrun : $campaign_data['cronnextrun'];
							$ltitle		 = __('Next Run:', 'wpematico') . ' ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $cronnextrun);
							$lbotones	 = '<button type="button" class="state_buttons cpanelbutton dashicons dashicons-controls-play" title="' . esc_attr(__('Run Once', 'wpematico')) . '"></button>'; // To run now
							$lbotones	 .= '<button type="button" disabled class="state_buttons cpanelbutton dashicons dashicons-update red"></button>'; // To stop
							$lbotones	 .= '<button type="button" class="state_buttons cpanelbutton dashicons dashicons-controls-pause" btn-href="' . WPeMatico_Campaigns::wpematico_action_link($post_id, 'display', 'toggle') . '" title="' . $atitle . '"></button>'; // To deactivate
						} else {  // Inactive play gris & grab gris & stop black
							$ltitle	  = __('Inactive', 'wpematico');
							$lbotones = '<button type="button" class="state_buttons cpanelbutton dashicons dashicons-controls-play" title="' . esc_attr(__('Run Once', 'wpematico')) . '"></button>'; // To run now
							$lbotones .= '<button type="button" class="state_buttons cpanelbutton dashicons dashicons-update" btn-href="' . WPeMatico_Campaigns::wpematico_action_link($post_id, 'display', 'toggle') . '" title="' . $atitle . '"></button>'; // To activate
							$lbotones .= '<button type="button" disabled class="state_buttons cpanelbutton dashicons dashicons-controls-pause grey"></button>'; // To stop
						}


						echo '<div class="row-actions2" title="' . esc_attr($ltitle) . '">' . $lbotones . '</div>';
						break;
					case 'last':
						$lastrun	 = get_post_meta($post_id, 'lastrun', true);
						$lastrun	 = (isset($lastrun) && !empty($lastrun) ) ? $lastrun : $campaign_data['lastrun'];
						$lastruntime = (isset($campaign_data['lastruntime'])) ? $campaign_data['lastruntime'] : '';
						if ($lastrun) {
							echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $lastrun));
							if (isset($lastruntime) && !empty($lastruntime)) {
								echo ' : ' . esc_html__('Runtime:', 'wpematico') . ' <span id="lastruntime">' . esc_html($lastruntime) . '</span> ' . esc_html__('sec.', 'wpematico');
							}
						} else {
							echo esc_html__('None', 'wpematico');
						}
						$starttime = (isset($campaign_data['starttime']) && !empty($campaign_data['starttime']) ) ? $campaign_data['starttime'] : 0;
						$activated = (bool) $campaign_data['activated'];
						if ($starttime > 0) {  // Running play verde & grab rojo & stop gris
							$runtime = current_time('timestamp') - $starttime;
							$ltitle	 = __('Running since:', 'wpematico') . ' ' . $runtime . ' ' . __('sec.', 'wpematico');
						} elseif ($activated) { // Running play gris & grab rojo & stop gris
							$cronnextrun = get_post_meta($post_id, 'cronnextrun', true);
							$cronnextrun = (isset($cronnextrun) && !empty($cronnextrun) && ($cronnextrun > 0 ) ) ? $cronnextrun : $campaign_data['cronnextrun'];
							$ltitle		 = '<b>' . __('Next Run:', 'wpematico') . '</b> ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $cronnextrun);
						} else {  // Inactive play gris & grab gris & stop black
							$ltitle = '';
						}
						?><div class=''><?php echo wp_kses_post($ltitle); ?></div><?php
						
						break;
						
				} // switch $column
			} // else brokenCampaign
		}

		// Make these columns sortable
		static function sortable_columns() {
			return array(
				'title' => 'title',
				'count' => 'count',
				'next'	=> 'next',
				'last'	=> 'last'
			);
		}

		static function custom_wpematico_kses_rules($tags, $context) {
			global $pagenow, $post_type;
			if ('edit.php' != $pagenow or $post_type != 'wpematico') {
				return;
			}
			if ($context === 'post') {
				$tags['button']['btn-href'] = true;  // Permitir btn-href  // ESTO EN 2.9 DEBE ELIMINARSE cambiando los btn-href de los js a data-href
				$tags['button']['data-href'] = true;  // Permitir data-href
				$tags['button']['title'] = true;      // Asegurar atributo title
				$tags['button']['disabled'] = true;   // Permitir disabled
				$tags['button']['type'] = true;       // Permitir type
				$tags['button']['class'] = true;      // Permitir class
			}
			return $tags;
		}
		
		public static function column_orderby($query) {
			global $pagenow, $post_type;
			$orderby = $query->get('orderby');
			if ('edit.php' != $pagenow || empty($orderby) || $post_type != 'wpematico')
				return;
			switch ($orderby) {
				case 'count':
					$meta_group = array('key' => 'postscount', 'type' => 'numeric');
					$query->set('meta_query', array('sort_column' => 'count', $meta_group));
					$query->set('meta_key', 'postscount');
					$query->set('orderby', 'meta_value_num');

					break;
				case 'next':
					$meta_group = array('key' => 'cronnextrun', 'type' => 'numeric');
					$query->set('meta_query', array('sort_column' => 'next', $meta_group));
					$query->set('meta_key', 'cronnextrun');
					$query->set('orderby', 'meta_value_num');

					break;
				case 'last':
					$meta_group = array('key' => 'lastrun', 'type' => 'numeric');
					$query->set('meta_query', array('sort_column' => 'last', $meta_group));
					$query->set('meta_key', 'lastrun');
					$query->set('orderby', 'meta_value_num');

					break;

				default:
					break;
			}
		}

		static function get_wpematico_categ_bulk_edit($post_id, $post_type) {
			$post_id   = ( isset($_POST['post_id']) && !empty($_POST['post_id']) ) ? absint($_POST['post_id']) : $post_id;
			$post_type = ( isset($_POST['campaign_posttype']) && !empty($_POST['campaign_posttype']) ) ? sanitize_text_field($_POST['campaign_posttype']) : $post_type;
		}

		public static function wpematico_add_to_quick_edit_custom_box($column_name, $post_type) {
			
			if($post_type != 'wpematico') return;
			
			$post			  = get_default_post_to_edit($post_type);
			$post_type_object = get_post_type_object('post');

			$taxonomy_names			 = get_object_taxonomies('post');
			$hierarchical_taxonomies = array();
			$flat_taxonomies		 = array();
			foreach ($taxonomy_names as $taxonomy_name) {
				$taxonomy = get_taxonomy($taxonomy_name);
				if (!$taxonomy->show_ui)
					continue;

				if ($taxonomy->hierarchical)
					$hierarchical_taxonomies[] = $taxonomy;
				else
					$flat_taxonomies[]		   = $taxonomy;
			}

			switch ($post_type) {
				case 'wpematico':
					switch ($column_name) {
						case 'status':
							static $printNonce = TRUE;
							if ($printNonce) {
								$printNonce = FALSE;
								wp_nonce_field(plugin_basename(__FILE__), 'wpematico_edit_nonce');
							}
							?>
							<fieldset class="" id="optionscampaign" style="display:none;">
								<div class="inline-edit-col">
									<h4><?php esc_html_e('Campaign Options', 'wpematico'); ?></h4>
									<div class="inline-edit-group">
										<label class="alignleft">
											<span class="field-title"><?php esc_html_e('Max items to create on each fetch:', 'wpematico'); ?></span>
											<span class="input-text">
												<input type="number" min="0" size="3" name="campaign_max" class="campaign_max small-text" value="">
											</span>
										</label>
										<label class="alignleft">
											<input type="checkbox" name="campaign_feeddate" value="1">
											<span class="checkbox-title"><?php esc_html_e('Use feed date', 'wpematico'); ?></span>
										</label> 
									</div>
									<div class="inline-edit-group">						
										<label class="alignleft inline-edit-col">
											<span class="authortitle"><?php esc_html_e('Author:', 'wpematico'); ?></span>
											<span class="input-text">
												<?php wp_dropdown_users(array('name' => 'campaign_author')); ?>
											</span>
										</label>
										<label class="alignleft inline-edit-col">
											<span class="commenttitle"><?php esc_html_e('Discussion options:', 'wpematico'); ?></span>
											<span class="input-text">
												<select class="campaign_commentstatus" name="campaign_commentstatus">
													<?php
													$options = array(
														'open'			  => __('Open', 'wpematico'),
														'closed'		  => __('Closed', 'wpematico'),
														'registered_only' => __('Registered only', 'wpematico')
													);
													foreach ($options as $key => $value) {
														echo '<option value="' . esc_attr($key) . '">' . esc_html($value) . '</option>';
													}
													?>
												</select>
											</span>
										</label>

									</div>
									<div class="inline-edit-group">
										<label class="alignleft">
											<input type="checkbox" name="campaign_allowpings" value="1">
											<span class="checkbox-title"><?php esc_html_e('Allow pings?', 'wpematico'); ?>&nbsp;</span>
										</label>
										<label class="alignleft">
											<input type="checkbox" name="campaign_linktosource" value="1">
											<span class="checkbox-title"><?php esc_html_e('Post title links to source?', 'wpematico'); ?>&nbsp;&nbsp;</span>
										</label>
										<label class="alignleft">
											<input type="checkbox" name="campaign_strip_links" value="1">
											<span class="checkbox-title"><?php esc_html_e('Strip links from content', 'wpematico'); ?></span>
										</label>
										<br class="clear" />
									</div>
								</div>
							</fieldset>	
							
							<?php if(defined('WPEMATICOPRO_VERSION')): ?>		
							<fieldset class="inline-edit-col-center inline-edit-categories">
								<div class="inline-edit-col" id="taxonomies_container">
										<!-- Div to add the taxonomies -->
								</div>
							</fieldset>
							<?php else: ?>	
								<?php if (count($hierarchical_taxonomies)) : ?>					
									<fieldset class="inline-edit-col-center inline-edit-categories">
										<div class="inline-edit-col">
											<?php foreach ($hierarchical_taxonomies as $taxonomy) : ?>

												<span class="title inline-edit-categories-label"><?php echo esc_html($taxonomy->labels->name) ?></span>
												<input type="hidden" name="<?php echo ( $taxonomy->name == 'category' ) ? 'post_category[]' : 'tax_input[' . esc_attr($taxonomy->name) . '][]'; ?>" value="0" />
												<ul class="cat-checklist <?php echo esc_attr($taxonomy->name) ?>-checklist">
													<?php wp_terms_checklist(null, array('taxonomy' => $taxonomy->name)) ?>
												</ul>

											<?php endforeach; //$hierarchical_taxonomies as $taxonomy      ?>
										</div>
									</fieldset>
								<?php endif; // count( $hierarchical_taxonomies ) && !$bulk   ?>
							<?php endif; ?>	
							<fieldset class="inline-edit-col-right">
								<?php if(defined('WPEMATICOPRO_VERSION')): ?>	
									<div class="inline-edit-col" id="tags_container">
										<!-- Div to add the tags -->
									</div>
								<?php else: ?>
										<?php if (count($flat_taxonomies)) : ?>
											<div class="inline-edit-col">
												<?php foreach ($flat_taxonomies as $taxonomy) : ?>
													<?php if (current_user_can($taxonomy->cap->assign_terms)) : ?>
														<label class="inline-edit-tags">
															<span class="title"><?php echo esc_html($taxonomy->labels->name) ?></span>
															<textarea cols="22" rows="1" name="campaign_tags" class="tax_input_<?php echo esc_attr($taxonomy->name) ?>"></textarea>
														</label>
													<?php endif; ?>
												<?php endforeach; //$flat_taxonomies as $taxonomy    ?>
											</div>
										<?php endif; // count( $flat_taxonomies ) && !$bulk      ?>
								<?php endif; ?>	
								<div class="inline-edit-radiosbox">
									<label>
										<span class="title"><?php esc_html_e('Post type', 'wpematico'); ?></span>
										<br/>
										<span class="input-text"> 
											<?php
											$args		= array(
												'public' => true
											);
											$output		= 'names'; // names or objects, note names is the default
											$operator	= 'and'; // 'and' or 'or'
											$post_types = get_post_types($args, $output, $operator);
											foreach ($post_types as $posttype) {
												if ($posttype == 'wpematico')
													continue;
												echo '<label><input type="radio" name="campaign_customposttype" value="' . esc_attr($posttype) . '" id="customtype_' . esc_attr($posttype) . '" /> ' . esc_html($posttype) . '</label>';
											}
											?>
										</span>
									</label>
								</div>
								<div class="inline-edit-radiosbox">
									<label>
										<span class="title"><?php esc_html_e('Status', 'wpematico'); ?></span>
										<br/>
										<span class="input-text">
											<?php
											$status_domain = '';
											$statuses	   = WPeMatico_functions::getAllStatuses();
											foreach ($statuses as $key => $status) {
												if ($status_domain != $status->label_count['domain']) {
													$status_domain = $status->label_count['domain'];
													echo "<b>" .esc_html($status_domain) . "</b><br />";
													//echo "<option disabled='disabled' value='' /> $status_domain</option>";
												}
												$status_name  = $status->name;
												$status_label = $status->label;
												/**
												 * TODO: Allow Scheduled status with datime in the future by hours 
												 */
												if (in_array($status_name, array('future', '')))
													continue;

												echo "<label><input type='radio' name='campaign_posttype' value='" . esc_attr($status_name) . "' /> " . esc_html($status_label) . "</label>";
												//echo "<option " . selected($status_name, $campaign_posttype, false) . " value='$status_name' /> $status_label</option>";
											}
											/* 	<label><input type="radio" name="campaign_posttype" value="publish" /> <?php _e('Published'); ?></label>
											  <label><input type="radio" name="campaign_posttype" value="private" /> <?php _e('Private'); ?></label>
											  <label><input type="radio" name="campaign_posttype" value="pending" /> <?php _e('Pending'); ?></label>
											  <label><input type="radio" name="campaign_posttype" value="draft" /> <?php _e('Draft'); ?></label>
											 */
											?>
										</span>
									</label>
								</div>
								<?php
								if (current_theme_supports('post-formats')) :
									$post_formats = get_theme_support('post-formats');
									?>
									<div class="inline-edit-radiosbox qedscroll">
										<label>
											<span class="title" style="width: 100%;"><?php esc_html_e('Post Format', 'wpematico'); ?></span>
											<br/>
											<span class="input-text"> <?php
												if (is_array($post_formats[0])) :
													global $post, $campaign_data;
													$campaign_post_format = (!isset($campaign_post_format) || empty($campaign_post_format) ) ? '0' : $campaign_data['campaign_post_format'];
													?>
													<div id="post-formats-select">
														<label><input type="radio" name="campaign_post_format" class="post-format" id="post-format-0" value="0" /> <?php echo esc_html(get_post_format_string('standard')); ?></label>
														<?php foreach ($post_formats[0] as $format) : ?>
															<label><input type="radio" name="campaign_post_format" class="post-format" id="post-format-<?php echo esc_attr($format); ?>" value="<?php echo esc_attr($format); ?>" /> <?php echo esc_html(get_post_format_string($format)); ?></label>
														<?php endforeach; ?>
													</div>
												<?php endif; ?>
											</span>
										</label>
									</div>
								<?php endif; ?>

							</fieldset>
							<?php
							break;

						case 'title': // No entra en title		
							break;
						case 'others':
							/*               ?><fieldset class="inline-edit-col-right">
							  <div class="inline-edit-col">
							  <label>
							  <span class="title">Release Date</span>
							  <input type="text" name="next" value="" />
							  </label>
							  </div>
							  </fieldset><?php
							 */
							break;
					}
					break;  //		case 'wpematico'
			}
		}

		static function save_quick_edit_post($post_id) {
			//wp_die('save_quick_edit_post'.print_r($_POST,1));
			$slug  = 'wpematico';
			if (!isset($_POST['post_type']) || ( $slug !== $_POST['post_type'] ))
				return $post_id;
			if (!current_user_can('edit_post', $post_id))
				return $post_id;
			$_POST += array("{$slug}_edit_nonce" => '');
			if (!wp_verify_nonce($_POST["{$slug}_edit_nonce"], plugin_basename(__FILE__))) {
				wp_die('No verify nonce' /* .print_r($_POST,1) */);
				return;
			}

			$nivelerror = error_reporting(E_ERROR | E_WARNING | E_PARSE);

			$campaign							 = WPeMatico::get_campaign($post_id);
			$posdata							 = $_POST;
			// Fields in quick edit form
			$campaign['campaign_max']			 = (!isset($posdata['campaign_max']) ) ? 0 : absint($posdata['campaign_max']);
			$campaign['campaign_author']		 = (!isset($posdata['campaign_author']) ) ? 0 : absint($posdata['campaign_author']);
			$campaign['campaign_commentstatus']	 = (!isset($posdata['campaign_commentstatus']) ) ? 'closed' : sanitize_text_field($posdata['campaign_commentstatus']);
			$campaign['campaign_customposttype'] = (!isset($posdata['campaign_customposttype']) ) ? 'post' : sanitize_text_field($posdata['campaign_customposttype']);
			$campaign['campaign_posttype']		 = (!isset($posdata['campaign_posttype']) ) ? 'publish' : sanitize_text_field($posdata['campaign_posttype']);
			$campaign['campaign_tags']			 = (!isset($posdata['campaign_tags']) ) ? '' : sanitize_text_field($posdata['campaign_tags']);

			//parse disabled checkfields that dont send any data
			$campaign['campaign_feed_order_date'] = (!isset($posdata['campaign_feed_order_date']) || empty($posdata['campaign_feed_order_date'])) ? false : ( ($posdata['campaign_feed_order_date'] == 1) ? true : false );
			$campaign['campaign_feeddate']		  = (!isset($posdata['campaign_feeddate']) || empty($posdata['campaign_feeddate'])) ? false : ( ($posdata['campaign_feeddate'] == 1) ? true : false );
			$campaign['campaign_allowpings']	  = (!isset($posdata['campaign_allowpings']) || empty($posdata['campaign_allowpings'])) ? false : ( ($posdata['campaign_allowpings'] == 1) ? true : false );
			$campaign['campaign_linktosource']	  = (!isset($posdata['campaign_linktosource']) || empty($posdata['campaign_linktosource'])) ? false : ( ($posdata['campaign_linktosource'] == 1) ? true : false );
			$campaign['campaign_strip_links']	  = (!isset($posdata['campaign_strip_links']) || empty($posdata['campaign_strip_links'])) ? false : ( ($posdata['campaign_strip_links'] == 1) ? true : false );

			// parse checked post categories
			$campaign['post_category'] = array();
			if (isset($posdata['post_category']) && is_array($posdata['post_category'])) {
				foreach ($posdata['post_category'] as $term_id) {
					$campaign['post_category'][] = absint($term_id);
				}
			}

			//Merge postdata to avoid the loss of new campaign fields
//			$campaign = array_merge($campaign, $posdata);
			$campaign = apply_filters('wpematico_check_campaigndata', $campaign);

			error_reporting($nivelerror);

			WPeMatico::update_campaign($post_id, $campaign);

			return $post_id;
		}

		/**
		 * Saving the 'Bulk Edit' data is a little trickier because we have
		 * to get JavaScript involved. WordPress saves their bulk edit data
		 * via AJAX so, guess what, so do we.
		 *
		 * Your javascript will run an AJAX function to save your data.
		 * This is the WordPress AJAX function that will handle and save your data.
		 */
		static function manage_wpematico_save_bulk_edit() {
			// Verify user permissions and nonce
			if (!is_user_logged_in() || !current_user_can('manage_options') || !isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'], 'wpematico-campaigns-list-nonce')) {
				wp_send_json_error(__('Security check failed.', 'wpematico'), 403);
			}

			// Retrieve post IDs
			$post_ids = isset($_POST['post_ids']) && is_array($_POST['post_ids']) ? array_map('absint', $_POST['post_ids']) : null;

			if (empty($post_ids)) {
				wp_send_json_error(__('No post IDs provided.', 'wpematico'), 400);
			}

			// Prepare data to update
			$update_data = [
				'campaign_max'          => isset($_POST['campaign_max']) ? absint($_POST['campaign_max']) : null,
				'campaign_feeddate'     => !empty($_POST['campaign_feeddate']),
				'campaign_commentstatus'=> isset($_POST['campaign_commentstatus']) ? sanitize_text_field($_POST['campaign_commentstatus']) : 'closed',
				'campaign_allowpings'   => !empty($_POST['campaign_allowpings']),
				'campaign_linktosource' => !empty($_POST['campaign_linktosource']),
				'campaign_strip_links'  => !empty($_POST['campaign_strip_links']),
				'post_category'         => isset($_POST['post_category']) && is_array($_POST['post_category']) ? array_map('absint', $_POST['post_category']) : [],
				'campaign_author'       => isset($_POST['campaign_author']) ? absint($_POST['campaign_author']) : 0,
			];
			
			// Update each campaign
			foreach ($post_ids as $post_id) {
				$campaign = WPeMatico::get_campaign($post_id);

				if (is_wp_error($campaign)) {
					continue; // Skip if campaign data is invalid
				}

				// Update campaign data
				foreach ($update_data as $key => $value) {
					if (!is_null($value)) {
						$campaign[$key] = $value;
					}
				}

				// Apply filters and save campaign
				$campaign = apply_filters('wpematico_check_campaigndata', $campaign);
				$campaign = apply_filters('wpematico_presave_campaign', $campaign);
				WPeMatico::update_campaign($post_id, $campaign);
			}
			WPeMatico::add_wp_notice([ 'text' => __('Campaigns updated successfully.', 'wpematico'), 'below-h2' => false]);
			// Send success response
			wp_send_json_success(__('Campaigns updated successfully.', 'wpematico'), 200);
		}
	}

	endif;
// class
WPeMatico_Campaigns::hooks();
?>