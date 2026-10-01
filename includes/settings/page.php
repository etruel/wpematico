<?php
/**
 * WPeMatico Pro Extra Settings Class 
 * This class is used to add the Professional Extra Settings 
 * @since 2.2
 */

defined('ABSPATH') || exit;

if (!class_exists('WPeMatico_Settings')) :

	class WPeMatico_Settings {

		public static function hooks() {
			add_action('wpematico_settings_tab_pro_licenses', array(__CLASS__, 'wpematicopro_licenses'));

			add_action('wpematico_settings_section_general_settings', array(__CLASS__, 'settings_form'), 0, 5);
			add_action('admin_post_save_wpematico_settings', array(__CLASS__, 'settings_save'));

			add_action('wpematico_settings_section_advanced_actions', array(__CLASS__, 'advanced_actions_form'), 0, 5);
			add_action('wpematico_settings_section_backend_tools', array(__CLASS__, 'backend_tools_form'), 0, 5);

			add_action('current_screen', array(__CLASS__, 'settings_help'));
			add_action('wp_ajax_process_button_click', array(__CLASS__, 'process_button_click'));

			add_action('wpematico_setting_page_before', array(__CLASS__, 'settings_wpematico_lastlog'));
		}

		public static function process_button_click() {
			// This setting belongs to the plugin settings screen.
			if (!current_user_can('manage_options')) {
				wp_send_json_error(__('Permission check failed', 'wpematico'));
			}
			// Verify the nonce
			$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
			if (!wp_verify_nonce($nonce, 'wpematico-settings-page-nonce')) {
				wp_send_json_error(__('Permission check failed', 'wpematico'));
			}

			// Retrieve the value from the AJAX request
			$value		  = isset($_POST['value']) ? true : false;
			$updateOption = false;
			if ($value)
				$updateOption = update_option('wpematico_lastlog_disabled', $value);

			if ($updateOption) {
				// Process the value (you can customize this part)
				$response = [
					'message' => __('Processing successful', 'wpematico'),
					'color'	  => 'success',
				];
				// Send the response back to the frontend
				wp_send_json_success($response);
			} else {
				wp_send_json_error();
			}
		}

		/**
		 * 		Called by function admin_menu() on wpematico_class
		 */
		public static function styles() {
			global $cfg;
			wp_enqueue_style('WPematStylesheet');
			wp_enqueue_style('wpemat-sidebar-css', WPEMATICO_PLUGIN_URL . 'assets/css/wpemat_sidebar.css', array(), WPEMATICO_VERSION);
			wp_enqueue_style('wpemat-settings-css', WPEMATICO_PLUGIN_URL . 'assets/css/wpemat_settings.css', array('wpemat-sidebar-css'), WPEMATICO_VERSION);
			add_action('admin_head', array(__CLASS__, 'wpematico_settings_head'));
			wp_enqueue_script('WPemattiptip');
			wp_enqueue_script('postbox');
			// Enqueue jQuery UI and autocomplete
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-autocomplete');

			wp_enqueue_script('wpematico_settings_page', WPEMATICO_PLUGIN_URL . 'assets/js/settings_page.js', array('jquery', 'postbox'), WPEMATICO_VERSION, true);
			//			$allowedmimes = array_diff(explode(',', WPeMatico::get_images_allowed_mimes()), explode(',', $cfg['images_allowed_ext']));
			$wpematico_object = array(
				'text_invalid_email' => __('Invalid email.', 'wpematico'),
//				'current_img_mimes'	 => $allowedmimes,
				'nonce'				 => wp_create_nonce('wpematico-settings-page-nonce')
			);
			wp_localize_script('wpematico_settings_page', 'wpematico_object', $wpematico_object);

			/* Add screen option: user can choose between 1 or 2 columns (default 2) */
			//add_screen_option('layout_columns', array('max' => 2, 'default' => 2) );
		}

		public static function wpematico_settings_head() {
			?>
			<style type="text/css">
				.insidesec {
					display: inline-block;
					vertical-align: top;
				}
				.ui-autocomplete {
					float: left;
					box-shadow: 2px 2px 3px #888888;
					background: #FFF;
				}
				.ui-menu-item {
					list-style-type: none;
					padding: 10px;
				}
				.ui-menu-item:hover {
					background: #F1F1F1;
				}
				.postbox .hndle{
					border-bottom: 1px solid #ccd0d4;
				}
				.postbox .handlediv{
					float: right;
					text-align: center;
				}
			</style>

			<?php
		}

		/**
		 * Make Licenses Tab contents
		 */
		public static function wpematicopro_licenses() {
			global $current_screen;
			if (!isset($current_screen))
				wp_die(esc_html__('Invalid request.', 'wpematico'), esc_html__('Invalid request', 'wpematico'), array('response' => 400));
			?>
			<div id="licenses">
				<div class="postbox ">
					<div class="inside">
						<?php
						/*						 * * Display license page */
						settings_errors();
						if (!has_action('wpempro_licenses_forms')) {
							echo '<div class="msg"><p>', __('This is where you would enter the license keys for one of our premium plugins, should you activate one.', 'wpematico'), '</p>';
							echo '<p>', __('See some of the WPeMatico Add-ons in the', 'wpematico'), ' <a href="', admin_url('plugins.php?page=wpemaddons') . '">Extensions list</a>.</p></div>';
						} else {
							do_action('wpempro_licenses_forms');
						}
						?>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Make General Settings form content
		 * @global type $cfg
		 * @global type $current_screen
		 * @global type $helptip
		 */
		public static function settings_form() {
			global $cfg, $current_screen, $helptip;

			// The settings form is rendered for administrators only.
			if (!current_user_can('manage_options'))
				return;

			$fifu_activated = defined('FIFU_PLUGIN_DIR');
			?>
			<input type="hidden" name="action" value="save_wpematico_settings" />
			<div class="meta-box-sortables ui-sortable">
				<div id="imgs" class="postbox">
					<button type="button" class="handlediv button-link" aria-expanded="true">
						<span class="screen-reader-text"><?php _e('Click to toggle', 'wpematico'); ?></span>
						<span class="toggle-indicator" aria-hidden="true"></span>
					</button>
					<h3 class="hndle"><span class="dashicons dashicons-format-image"></span> <?php echo _e('Global Settings for Images', 'wpematico'); ?></h3>
					<div class="inside">
						<div class="wpematico_form-table">
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['imgcache'], true); ?> name="imgcache" id="imgcache" />
										<label for="imgcache"><?php _e('Store images locally', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['imgcache'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
									<div id="nolinkimg" class="wpematico_form-inner" <?php if (!$cfg['imgcache']) echo 'style="display:none;"'; ?>>
										<div class="wpematico_form-row">
											<div class="wpematico_form-column">
												<div class="wpematico_switch wpematico_switch-horizontal">
													<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['imgattach'], true); ?> name="imgattach" id="imgattach" />
													<label for="imgattach"><?php _e('Attach images to posts', 'wpematico'); ?></label>
												</div>
												<p><?php echo html_entity_decode($helptip['imgattach'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
											</div>
										</div>
										<div class="wpematico_form-row">
											<div class="wpematico_form-column">
												<div class="wpematico_switch wpematico_switch-horizontal">
													<input class="checkbox"  value="1" type="checkbox" <?php checked($cfg['save_attr_images'], true); ?> name="save_attr_images" id="save_attr_images" />
													<label for="save_attr_images"><?php _e('Save image attributes on WP Media', 'wpematico'); ?></label>
												</div>
												<p><?php echo html_entity_decode($helptip['save_attr_images'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
											</div>
										</div>
										<div class="wpematico_form-row">
											<div class="wpematico_form-column">
												<div class="wpematico_switch wpematico_switch-horizontal">
													<input name="gralnolinkimg" id="gralnolinkimg" class="checkbox" value="1" type="checkbox" <?php checked($cfg['gralnolinkimg'], true); ?> />
													<label for="gralnolinkimg"><?php _e('Remove link to source images', 'wpematico'); ?></label>
												</div>
												<p><?php echo html_entity_decode($helptip['gralnolinkimg'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
											</div>
										</div>
										<div class="wpematico_form-row">
											<div class="wpematico_form-column">
												<div class="wpematico_switch wpematico_switch-horizontal">
													<input name="image_srcset" id="image_srcset" class="checkbox" value="1" type="checkbox" <?php checked($cfg['image_srcset'], true); ?> />
													<label for="image_srcset"><?php esc_attr_e('Read the srcset attribute of the <img> tag', 'wpematico'); ?></label>
												</div>
												<p><?php echo html_entity_decode($helptip['image_srcset'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['featuredimg'], true); ?> name="featuredimg" id="featuredimg" />
										<label for="featuredimg"><?php _e('Set the first image as featured', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['featuredimg'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['rmfeaturedimg'], true); ?> name="rmfeaturedimg" id="rmfeaturedimg" />
										<label for="rmfeaturedimg"><?php _e('Remove the featured image from the content', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['rmfeaturedimg'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
							<div class="wpematico_form-row" id="custom_uploads" style="<?php if (!$cfg['imgcache'] && !$cfg['featuredimg']) echo 'display:none;'; ?>">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['customupload'], true); ?> name="customupload" id="customupload" />
										<label for="customupload"><?php _e('Use custom upload', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['customupload'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
							<div class="wpematico_form-row" id="allowed_ext" style="<?php if (!$cfg['imgcache'] && !$cfg['featuredimg']) echo 'display:none;'; ?>">
								<div class="wpematico_form-column">
									<?php
									$comma		 = _x(',', 'mime delimiter', 'default');
									$ext_to_edit = (!is_string($cfg['images_allowed_ext'])) ? '' : $cfg['images_allowed_ext'];
									$ext_list	 = WPeMatico::get_images_allowed_mimes();
									?>
									<label for="images_allowed_ext"><?php _e('Allowed image extensions', 'wpematico'); ?></label>
									<input type="text" class="regular-text" name="images_allowed_ext" id="images_allowed_ext" value="<?php echo str_replace(',', $comma, $ext_to_edit); ?>"/>
									<p class="description" id="new-mime-images_allowed_ext-desc">
										<?php _e('Separate the allowed extensions with commas.', 'wpematico'); ?><br /> 
										<?php _e('WordPress image types:', 'wpematico'); ?> <span class="description" id="images_allowed_ext-list" title="<?php _e('Click to restore the WordPress defaults.', 'wpematico') ?>" onclick="jQuery('#images_allowed_ext').val(jQuery(this).text());return false;"><?php echo $ext_list; ?></span><br/>
										<?php _e('Recommended:', 'wpematico'); ?> <span id="images_allowed_ext-list" title="<?php _e('Click to use the recommended values.', 'wpematico') ?>" onclick="jQuery('#images_allowed_ext').val(jQuery(this).text());return false;"><?php echo "jpg,gif,png,tif,bmp,jpeg"; ?></span>
									</p>
								</div>
							</div>
							<div class="wpematico_form-row" id="enable_mimetypes" style="<?php if (!$cfg['imgcache'] && !$cfg['featuredimg']) echo 'display:none;'; ?>">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['enablemimetypes'], true); ?> name="enablemimetypes" id="enablemimetypes" />
										<label for="enablemimetypes"><?php _e('Allow more file types', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['enablemimetypes'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($fifu_activated && $cfg['fifu']); ?> name="fifu" id="fifu" <?php disabled(!$fifu_activated); ?>/>
										<label for="fifu"><?php _e('Use Featured Image from URL', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['fifu'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
									<?php // Shown, not enforced: Professional and Full Content can hand a
									// featured image over `wpematico_set_featured_img` with this option off. ?>
									<p class="description wpematico-needs-featuredimg" <?php if ($cfg['featuredimg']) echo 'style="display:none;"'; ?>>
										<span class="dashicons dashicons-warning"></span>
										<?php _e('&ldquo;Set first image in content as Featured Image.&rdquo; is off, so nothing picks the image to link &mdash; unless an addon such as Professional or Full Content supplies one.', 'wpematico'); ?>
									</p>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div id="gsvideos" class="postbox">
					<button type="button" class="handlediv button-link" aria-expanded="true">
						<span class="screen-reader-text"><?php _e('Click to toggle', 'wpematico'); ?></span>
						<span class="toggle-indicator" aria-hidden="true"></span>
					</button>
					<h3 class="hndle"><span class="dashicons dashicons-format-video"></span> <?php echo _e('Global Settings for Videos', 'wpematico'); ?></h3>
					<div class="inside">
						<div class="wpematico_form-table">
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['video_cache'], true); ?> name="video_cache" id="video_cache" />
										<label for="video_cache"><?php _e('Store videos locally', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['video_cache'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
									<div id="nolink_video" class="wpematico_form-inner" <?php if (!$cfg['video_cache']) echo 'style="display:none;"'; ?>>
										<div class="wpematico_form-row">
											<div class="wpematico_form-column">
												<div class="wpematico_switch wpematico_switch-horizontal">
													<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['video_attach'], true); ?> name="video_attach" id="video_attach" />
													<label for="video_attach"><?php _e('Attach videos to posts', 'wpematico'); ?></label>
												</div>
												<p><?php echo html_entity_decode($helptip['video_attach'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
											</div>
										</div>
										<div class="wpematico_form-row">
											<div class="wpematico_form-column">
												<div class="wpematico_switch wpematico_switch-horizontal">
													<input name="gralnolink_video" id="gralnolink_video" class="checkbox" value="1" type="checkbox" <?php checked($cfg['gralnolink_video'], true); ?> />
													<label for="gralnolink_video"><?php _e('Remove link to source videos', 'wpematico'); ?></label>
												</div>
												<p><?php echo html_entity_decode($helptip['gralnolink_video'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="wpematico_form-row" id="custom_uploads_videos" <?php if (!$cfg['video_cache']) echo 'style="display:none;"'; ?>>
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['customupload_videos'], true); ?> name="customupload_videos" id="customupload_videos" />
										<label for="customupload_videos"><?php _e('Use custom upload', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['customupload_videos'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
							<div class="wpematico_form-row" id="allowed_ext_videos" <?php if (!$cfg['video_cache']) echo 'style="display:none;"'; ?>>
								<div class="wpematico_form-column">
									<?php
									$comma		 = _x(',', 'mime delimiter', 'default');
									$ext_to_edit = (!is_string($cfg['video_allowed_ext'])) ? '' : $cfg['video_allowed_ext'];
									$ext_list	 = WPeMatico::get_videos_allowed_mimes();
									?>
									<label for="video_allowed_ext"><?php _e('Allowed video extensions', 'wpematico'); ?></label>
									<input type="text" class="regular-text" name="video_allowed_ext" id="video_allowed_ext" value="<?php echo str_replace(',', $comma, $ext_to_edit); // textarea_escaped by esc_attr()  ?>"/>
									<p class="description" id="new-mime-video_allowed_ext-desc"><?php _e('Separate the allowed extensions with commas.', 'wpematico'); ?><br /> 
										<?php _e('WordPress video types:', 'wpematico'); ?> <span class="description" id="video_allowed_ext-list" title="<?php _e('Click to restore the WordPress defaults.', 'wpematico') ?>" onclick="jQuery('#video_allowed_ext').val(jQuery(this).text());return false;"><?php echo $ext_list; ?></span><br/>
										<?php _e('Recommended:', 'wpematico'); ?> <span class="description" id="video_allowed_ext-list" title="<?php _e('Click to use the recommended values.', 'wpematico') ?>" onclick="jQuery('#video_allowed_ext').val(jQuery(this).text());return false;"><?php echo "mp4"; ?></span>
									</p>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div id="gsaudios" class="postbox">
					<button type="button" class="handlediv button-link" aria-expanded="true">
						<span class="screen-reader-text"><?php _e('Click to toggle', 'wpematico'); ?></span>
						<span class="toggle-indicator" aria-hidden="true"></span>
					</button>
					<h3 class="hndle"><span class="dashicons dashicons-format-audio"></span> <?php echo _e('Global Settings for Audios', 'wpematico'); ?></h3>
					<div class="inside">
						<div class="wpematico_form-table">
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['audio_cache'], true); ?> name="audio_cache" id="audio_cache" />
										<label for="audio_cache"><?php _e('Store audios locally', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['audio_cache'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
									<div id="nolink_audio" class="wpematico_form-inner" <?php if (!$cfg['audio_cache']) echo 'style="display:none;"'; ?>>
										<div class="wpematico_form-row">
											<div class="wpematico_form-column">
												<div class="wpematico_switch wpematico_switch-horizontal">
													<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['audio_attach'], true); ?> name="audio_attach" id="audio_attach" />
													<label for="audio_attach"><?php _e('Attach audios to posts', 'wpematico'); ?></label>
												</div>
												<p class="description"><?php echo html_entity_decode($helptip['audio_attach'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
											</div>
										</div>
										<div class="wpematico_form-row">
											<div class="wpematico_form-column">
												<div class="wpematico_switch wpematico_switch-horizontal">
													<input name="gralnolink_audio" id="gralnolink_audio" class="checkbox" value="1" type="checkbox" <?php checked($cfg['gralnolink_audio'], true); ?> />
													<label for="gralnolink_audio"><?php _e('Remove link to source audios', 'wpematico'); ?></label>
												</div>
												<p class="description"><?php echo html_entity_decode($helptip['gralnolink_audio'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="wpematico_form-row" id="custom_uploads_audios" <?php if (!$cfg['audio_cache']) echo 'style="display:none;"'; ?>>
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['customupload_audios'], true); ?> name="customupload_audios" id="customupload_audios" />
										<label for="customupload_audios"><?php _e('Use custom upload', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['customupload_audios'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
							<div class="wpematico_form-row" id="allowed_ext_audios" <?php if (!$cfg['audio_cache']) echo 'style="display:none;"'; ?>>
								<div class="wpematico_form-column">
									<?php
									$comma		 = _x(',', 'mime delimiter', 'default');
									$ext_to_edit = (!is_string($cfg['audio_allowed_ext'])) ? '' : $cfg['audio_allowed_ext'];
									$ext_list	 = WPeMatico::get_audios_allowed_mimes();
									?>
									<label for="audio_allowed_ext"><?php _e('Allowed audio extensions', 'wpematico'); ?></label>
									<input type="text" class="regular-text" name="audio_allowed_ext" id="audio_allowed_ext" value="<?php echo str_replace(',', $comma, $ext_to_edit); // textarea_escaped by esc_attr()           ?>" size="80" />
									<p class="description" id="new-mime-audio_allowed_ext-desc">
										<?php _e('Separate the allowed extensions with commas.', 'wpematico'); ?><br /> 
										<?php _e('WordPress audio types:', 'wpematico'); ?> <span class="description" id="image_allowed_ext-list" title="<?php _e('Click to restore the WordPress defaults.', 'wpematico') ?>" onclick="jQuery('#audio_allowed_ext').val(jQuery(this).text());return false;"><?php echo $ext_list; ?></span><br/>
										<?php _e('Recommended:', 'wpematico'); ?> <span class="description" id="audio_allowed_ext-list" title="<?php _e('Click to use the recommended values.', 'wpematico') ?>" onclick="jQuery('#audio_allowed_ext').val(jQuery(this).text());return false;"><?php echo "mp3"; ?></span>
									</p>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div id="spoptions" class="postbox">
					<button type="button" class="handlediv button-link" aria-expanded="true">
						<span class="screen-reader-text"><?php _e('Click to toggle', 'wpematico'); ?></span>
						<span class="toggle-indicator" aria-hidden="true"></span>
					</button>
					<h3 class="hndle"><span class="dashicons dashicons-chart-pie"></span> <?php _e('SimplePie Settings', 'wpematico'); ?></h3>
					<div class="inside">
						<div class="wpematico_form-table">
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['set_stupidly_fast'], true); ?> name="set_stupidly_fast" id="set_stupidly_fast"  onclick="jQuery('#simpie').show();"  />
										<label><?php _e('Set SimplePie "stupidly fast"', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['stupidly_fast'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
									<div id="simpie" class="wpematico_form-inner" <?php if ($cfg['set_stupidly_fast']) echo 'style="display:none;"'; ?>>
										<div class="wpematico_form-row">
											<div class="wpematico_form-column">
												<div class="wpematico_switch wpematico_switch-horizontal">
													<input name="simplepie_strip_htmltags" id="simplepie_strip_htmltags" class="checkbox" value="1" type="checkbox" <?php checked($cfg['simplepie_strip_htmltags'], true); ?> />
													<label for="simplepie_strip_htmltags"><?php _e('HTML tags SimplePie strips', 'wpematico'); ?></label>
												</div>
												<textarea <?php disabled($cfg['simplepie_strip_htmltags'], false, true); ?> name="strip_htmltags" id="strip_htmltags" ><?php echo esc_textarea($cfg['strip_htmltags']); ?></textarea>
												<p class="description"><?php echo html_entity_decode($helptip['strip_htmltags'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
											</div>
										</div>
										<div class="wpematico_form-row">
											<div class="wpematico_form-column">
												<div class="wpematico_switch wpematico_switch-horizontal">
													<input name="simplepie_strip_attributes" id="simplepie_strip_attributes" class="checkbox" value="1" type="checkbox" <?php checked($cfg['simplepie_strip_attributes'], true); ?> />
													<label for="simplepie_strip_attributes"><?php _e('HTML attributes SimplePie strips', 'wpematico'); ?></label>
												</div>
												<textarea <?php disabled($cfg['simplepie_strip_attributes'], false, true); ?> name="strip_htmlattr" id="strip_htmlattr" ><?php echo esc_textarea($cfg['strip_htmlattr']); ?></textarea>
												<p class="description"><?php echo html_entity_decode($helptip['strip_htmlattr'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div id="advancedfetching" class="postbox">
					<button type="button" class="handlediv button-link" aria-expanded="true">
						<span class="screen-reader-text"><?php _e('Click to toggle', 'wpematico'); ?></span>
						<span class="toggle-indicator" aria-hidden="true"></span>
					</button>
					<h3 class="hndle"><span class="dashicons dashicons-admin-tools"></span> <?php echo _e('Advanced Fetching', 'wpematico'); ?></h3>
					<div class="inside">
						<div class="wpematico_form-table">
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['woutfilter'], true); ?> name="woutfilter" id="woutfilter" />
										<label for="woutfilter"><?php _e('Let campaigns skip the content filters', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php _e('Dangerous: the content is stored exactly as the feed sends it, scripts included.', 'wpematico'); ?></p>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_input-group">
										<label for="campaign_timeout"><?php _e('Timeout for a running campaign:', 'wpematico'); ?></label>
										<input name="campaign_timeout" type="number" min="0" value="<?php echo esc_attr($cfg['campaign_timeout']); ?>" class="small-text" /> 
										<?php _e('Seconds.', 'wpematico'); ?>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['campaign_timeout'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_input-group">
										<label for="throttle"><?php _e('Pause after every post, in seconds:', 'wpematico'); ?></label>
										<input name="throttle" id="throttle" class="small-text" min="0" type="number" value="<?php echo esc_attr($cfg['throttle']); ?>" />
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['throttle'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['allowduplicates'], true); ?> name="allowduplicates" id="allowduplicates" />
										<label for="allowduplicates"><?php _e('Allow duplicate posts', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['allowduplicates'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
									<div id="enadup" class="wpematico_form-inner" <?php if (!$cfg['allowduplicates']) echo 'style="display:none;"'; ?>>
										<div class="wpematico_form-row">
											<div class="wpematico_form-column">
												<div class="wpematico_switch wpematico_switch-horizontal">
													<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['allowduptitle'], true); ?> name="allowduptitle" id="allowduptitle" />
													<label for="allowduptitle"><?php _e('Allow duplicate titles', 'wpematico'); ?></label>
												</div>
											</div>
										</div>
										<div class="wpematico_form-row">
											<div class="wpematico_form-column">
												<div class="wpematico_switch wpematico_switch-horizontal">
													<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['allowduphash'], true); ?> name="allowduphash" id="allowduphash" />
													<label for="allowduphash"><?php _e('Allow duplicate hashes (not recommended)', 'wpematico'); ?></label>
												</div>
												<p class="description"><?php _e('With both checks off, every item is published again on every run, for ever. To publish items that share a title, use "Allow duplicate titles" and leave this one alone.', 'wpematico'); ?></p>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="wpematico_form-row" id="div_add_extra_duplicate_filter_meta_source" <?php if ($cfg['disableccf'] || $cfg['allowduptitle']) echo 'style="display:none;"' ?> class="wrap-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input name="add_extra_duplicate_filter_meta_source" id="add_extra_duplicate_filter_meta_source" class="checkbox" value="1" type="checkbox" <?php checked($cfg['add_extra_duplicate_filter_meta_source'], true); ?> />
										<label for="add_extra_duplicate_filter_meta_source"><?php _e('Also check duplicates by source permalink', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['add_extra_duplicate_filter_meta_source'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input name="jumpduplicates" id="jumpduplicates" class="checkbox" value="1" type="checkbox" <?php checked($cfg['jumpduplicates'], true); ?> />
										<label for="jumpduplicates"><?php _e('Keep reading past a duplicate', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['jumpduplicates'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input name="disableccf" id="disableccf" class="checkbox" value="1" type="checkbox" <?php checked($cfg['disableccf'], true); ?> />
										<label for="disableccf"><?php _e('Do not save the plugin custom fields', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['disableccf'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div id="disabwpcron" class="postbox">
					<button type="button" class="handlediv button-link" aria-expanded="true">
						<span class="screen-reader-text"><?php _e('Click to toggle', 'wpematico'); ?></span>
						<span class="toggle-indicator" aria-hidden="true"></span>
					</button>
					<h3 class="hndle"><span class="dashicons dashicons-admin-tools"></span> <?php echo _e('Cron and Scheduler Settings', 'wpematico'); ?></h3>
					<div class="inside">
						<div class="wpematico_form-table">
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" id="enable_alternate_wp_cron" type="checkbox"<?php checked($cfg['enable_alternate_wp_cron'], true); ?> name="enable_alternate_wp_cron" value="1"/> 
										<label for="enable_alternate_wp_cron"><?php _e('Use ALTERNATE_WP_CRON', 'wpematico'); ?></strong></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['enable_alternate_wp_cron'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" id="dontruncron" type="checkbox" <?php checked($cfg['dontruncron'], true); ?> name="dontruncron" value="1"/> 
										<label for="dontruncron"><?php _e('Disable WPeMatico schedules', 'wpematico'); ?></label> 
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['dontruncron'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
									<div id="usecj" class="wpematico_form-inner" <?php if (!$cfg['dontruncron']) echo 'style="display:none;"'; ?>>
										<?php
										$croncode	 = ($cfg['set_cron_code']) ? '?code=' . $cfg['cron_code'] : '';
										$url_cron	 = admin_url('admin-post.php?action=wpematico_cron');
										if ($cfg['set_cron_code']) {
											$url_cron = add_query_arg(array('code' => $cfg['cron_code']), $url_cron);
										}
										?>
										<div class="wpematico_form-row">
											<div class="wpematico_form-column">
												<div id="hlpcron">
													<label><?php _e('Set up a cron job that calls:', 'wpematico'); ?></label>
													<div class="wpematico_input-group">
														<label>
															<?php
															if (!has_action('wpematico_cronjob')) {
																_e('URL:', 'wpematico');
															} else {
																do_action('wpematico_cronjob');
															}
															?>
														</label>
														<code><?php echo $url_cron; ?></code>
													</div>
												</div>
											</div>
										</div>
										<div class="wpematico_form-row">
											<div class="wpematico_form-column">
												<div class="wpematico_switch wpematico_switch-horizontal">
													<input class="checkbox" id="set_cron_code" type="checkbox"<?php checked($cfg['set_cron_code'], true); ?> name="set_cron_code" value="1"/>
													<label for="set_cron_code"><?php _e('Ask for a password on the external cron', 'wpematico'); ?></label>
												</div>
												<p class="description"><?php echo html_entity_decode($helptip['set_cron_code'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
												<div id="cronautopass" <?php if (!$cfg['set_cron_code']) echo 'style="display:none;"'; ?>>
													<div class="wpematico_input-group pl-60 pt-10">
														<input type="hidden" id="autocode" value="<?php echo substr(md5(time()), 0, 8); ?>"/>
														<a style="font-size: 2.2em;" title="<?php _e('Click to use this random code.', 'wpematico'); ?>" class="button" onclick="Javascript: jQuery('#cron_code').val(jQuery('#autocode').val());"><span class="dashicons dashicons-migrate"></span></a>
														<input name="cron_code" title="<?php _e('Any text you like.', 'wpematico'); ?>" id="cron_code" type="text" value="<?php echo esc_attr($cfg['cron_code']); ?>" class="standard-text" style="max-width: max-content;" /> 
													</div>
													<p class="description"><?php echo html_entity_decode($helptip['cron_code'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" id="disablewpcron" type="checkbox" <?php checked($cfg['disablewpcron'], true); ?> name="disablewpcron" value="1"/>
										<label for="disablewpcron"><?php _e('Disable all WP_Cron', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['disablewpcron'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
									<div id="diswpcron" class="wpematico_form-inner" <?php if (!$cfg['disablewpcron']) echo 'style="display:none;"'; ?>>
										<div class="wpematico_form-row">
											<div class="wpematico_form-column">
												<div id="hlpcron2">
													<label><?php _e('To run the WordPress cron from outside, set up a cron job that calls:', 'wpematico'); ?></label>
													<code> php -q <?php echo ABSPATH . 'wp-cron.php'; ?></code>
													<label class="mt-10"><?php _e('or URL:', 'wpematico'); ?></label>
													<code><?php echo trailingslashit(get_option('siteurl')) . 'wp-cron.php'; ?></code>
												</div>
												<p class="description">
													<?php
													/* translators: %1$s DISABLE_WP_CRON constant, %2$s true, %3$s link to the WordPress cron source. */
													printf(__('This sets %1$s to %2$s, so the %3$s no longer runs on its own.', 'wpematico'), '<code>DISABLE_WP_CRON</code>', '<code>true</code>', '<a href="https://developer.wordpress.org/plugins/cron/" target="_blank">' . esc_html__('WordPress cron', 'wpematico') . '</a>');
													?>
													<br /> 
													<?php _e('More about WP Cron, and how to set an external cron up:', 'wpematico'); ?>
													<a href="http://code.tutsplus.com/articles/insights-into-wp-cron-an-introduction-to-scheduling-tasks-in-wordpress--wp-23119" target="_blank"><?php _e('here', 'wpematico'); ?></a>.
												</p>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" id="logexternalcron" type="checkbox"<?php checked($cfg['logexternalcron'], true); ?> name="logexternalcron" value="1"/> 
										<label for="logexternalcron"><?php _e('Log file for the external cron', 'wpematico'); ?></strong></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['logexternalcron'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Make Advanced Settings form content
		 * @global type $cfg
		 * @global type $current_screen
		 * @global type $helptip
		 */
		public static function advanced_actions_form() {
			global $cfg, $current_screen, $helptip;
			$disable_extensions_feed = (defined('MULTISITE') && MULTISITE) ? 'disabled' : '';
			?>
			<input type="hidden" name="action" value="save_wpematico_settings" />
			<div class="meta-box-sortables ui-sortable">
				<div id="enablefeatures" class="postbox">
					<button type="button" class="handlediv button-link" aria-expanded="true">
						<span class="screen-reader-text"><?php _e('Click to toggle', 'wpematico'); ?></span>
						<span class="toggle-indicator" aria-hidden="true"></span>
					</button>
					<h3 class="hndle"><span class="dashicons dashicons-admin-settings"></span> <?php echo _e('Enable Features', 'wpematico'); ?></h3>
					<div class="inside">
						<div class="wpematico_form-table">
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['enablerewrite'], true); ?> name="enablerewrite" id="enablerewrite" />
										<label for="enablerewrite"><?php _e('Enable "Rewrite"', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['enablerewrite'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['enableword2cats'], true); ?> name="enableword2cats" id="enableword2cats" />
										<label for="enableword2cats"><?php _e('Enable "Words to Categories"', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['enableword2cats'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['enable_vimeo'], true); ?> name="enable_vimeo" id="enable_vimeo" />
										<label for="enable_vimeo"><?php _e('Enable the Vimeo campaign type', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['enable_vimeo'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
									<?php
									/**
									 * The other half of Vimeo lives in Professional, on another
									 * screen, and this checkbox is where somebody wondering why
									 * a campaign only brings 10 videos ends up. Professional
									 * answers the filter with the link to its own setting --
									 * only it knows whether the API Keys section is switched on
									 * -- so with no Professional installed this stays a badge.
									 */
									$vimeo_api_note = wpematico_is_pro_active() ? '' :
											'<span class="wpematico_badge-pro">' . esc_html__('PRO', 'wpematico') . '</span> ' .
											esc_html__('Reading Vimeo through its API — past the 10-video cap of the feed, with sorting and your private videos — comes with Professional.', 'wpematico');
									$vimeo_api_note = apply_filters('wpematico_vimeo_api_note', $vimeo_api_note);
									if ('' !== trim((string) $vimeo_api_note)) :
										?>
										<p class="description"><?php echo wp_kses_post($vimeo_api_note); ?></p>
									<?php endif; ?>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" type="checkbox"<?php checked($cfg['wpematico_set_canonical'], true); ?> name="wpematico_set_canonical" value="1" id="wpematico_set_canonical"/> 
										<label for="wpematico_set_canonical"><?php echo __('Canonical URL to the source', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['wpematico_set_canonical'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div id="wpem-advanced-actions" class="postbox">
					<button type="button" class="handlediv button-link" aria-expanded="true">
						<span class="screen-reader-text"><?php _e('Click to toggle', 'wpematico'); ?></span>
						<span class="toggle-indicator" aria-hidden="true"></span>
					</button>
					<h3 class="hndle"><span class="dashicons dashicons-admin-settings"></span> <?php _e('Advanced Actions', 'wpematico'); ?></h3>
					<div class="inside">
						<div class="wpematico_form-table">
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['disablecheckfeeds'], true); ?> name="disablecheckfeeds" id="disablecheckfeeds" /> 
										<label><?php _e('Do not check feeds before saving', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['disablecheckfeeds'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['enabledelhash'], true); ?> name="enabledelhash" id="enabledelhash" />
										<label><?php _e('Enable "Del hash"', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['enabledelhash'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['enableseelog'], true); ?> name="enableseelog" id="enableseelog" />
										<label><?php _e('Enable "See last log"', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['enableseelog'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['enable_xml_upload'], true); ?> name="enable_xml_upload" id="enable_xml_upload" />
										<label><?php _e('Enable XML uploads', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['enable_xml_upload'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['disable_credits'], true); ?> name="disable_credits" id="disable_credits" />
										<label><?php _e('Hide the WPeMatico credits', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['disable_credits'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
									<p class="description" id="discredits" style="<?php echo ($cfg['disable_credits']) ? '' : 'display:none;' ?>">
										<?php
										/* translators: Link to WordPress Rate plugin page 1: open anchor link, 2: close anchor */
										printf(
												__('If you cannot show them, a minute of your time to %1$s write a 5 star review on WordPress %2$s means just as much. :-) Thanks.', 'wpematico'),
												'<b><a href="https://wordpress.org/support/view/plugin-reviews/wpematico?filter=5&rate=5#new-post" target="_Blank" title="Open a new window">', '</a></b>'
										);
										?>
									</p>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['disable_categories_description'], true); ?> name="disable_categories_description" id="disable_categories_description" />
										<label><?php _e('No description on auto categories', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['disable_categories_description'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['disable_extensions_feed_page'], true);
							echo $disable_extensions_feed ?> name="disable_extensions_feed_page" id="disable_extensions_feed_page" />
										<label><?php _e('Do not load the Extensions page', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['disable_extensions_feed_page'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['entity_decode_html'], true); ?> name="entity_decode_html" id="entity_decode_html" />
										<label><?php _e('Decode HTML entities on publish', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['entity_decode_html'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div id="wpem-email-settings" class="postbox">
					<button type="button" class="handlediv button-link" aria-expanded="true">
						<span class="screen-reader-text"><?php _e('Click to toggle', 'wpematico'); ?></span>
						<span class="toggle-indicator" aria-hidden="true"></span>
					</button>
					<h3 class="hndle"><span class="dashicons dashicons-email-alt"></span> <?php echo _e('Sending e-Mails', 'wpematico'); ?></h3>
					<div class="inside">
						<div class="wpematico_form-table">
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<label for="mailsndemail"><?php _e('Sender email:', 'wpematico'); ?></label>
									<input id="mailsndemail" name="mailsndemail" type="text" value="<?php echo esc_attr($cfg['mailsndemail']); ?>" class="large-text" /><span id="mailmsg"></span>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<label for="mailsndname"><?php _e('Sender name:', 'wpematico'); ?></label>
									<input id="mailsndname" name="mailsndname" type="text" value="<?php echo esc_attr($cfg['mailsndname']); ?>" class="large-text" />
								</div>
							</div>
							<input type="hidden" name="mailmethod" value="<?php echo esc_attr($cfg['mailmethod']); // "mailmethod"="mail" or "mailmethod"="SMTP"  ?>">
							<div class="wpematico_form-row" <?php if ($cfg['mailmethod'] != 'Sendmail') echo 'style="display:none;"'; ?>>
								<div class="wpematico_form-column">
									<label id="mailsendmail"><b><?php _e('Sendmail path:', 'wpematico'); ?></label>
									<input name="mailsendmail" type="text" value="<?php echo esc_attr($cfg['mailsendmail']); ?>" class="large-text" />
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * 
		 * @global type $cfg
		 * @global type $current_screen
		 * @global type $helptip
		 * @global type $wp_roles
		 */
		public static function backend_tools_form() {
			global $cfg, $current_screen, $helptip, $wp_roles;
			?>
			<input type="hidden" name="action" value="save_wpematico_settings" />
			<div class="meta-box-sortables ui-sortable">
				<div id="emptytrashdiv" class="postbox">
					<button type="button" class="handlediv button-link" aria-expanded="true">
						<span class="screen-reader-text"><?php _e('Click to toggle', 'wpematico'); ?></span>
						<span class="toggle-indicator" aria-hidden="true"></span>
					</button>
					<h3 class="hndle"><span class="dashicons dashicons-hammer"></span> <?php echo _e('WordPress Backend Tools', 'wpematico'); ?></h3>
					<div class="inside">
						<div class="wpematico_form-table">
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" id="campaign_in_postslist" type="checkbox"<?php checked($cfg['campaign_in_postslist'], true); ?> name="campaign_in_postslist" value="1"/> 
										<label for="campaign_in_postslist"><?php _e('Campaign column in the posts lists', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['campaign_in_postslist'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
									<div id="column_campaign_pos_field" class="wpematico_form-inner" <?php if (!$cfg['campaign_in_postslist']) echo 'style="display:none;"'; ?>>
										<div class="wpematico_form-row">
											<div class="wpematico_form-column">
												<div class="wpematico_input-group">
													<label for="column_campaign_pos"><?php _e('Position of that column', 'wpematico'); ?></label>
													<input name="column_campaign_pos" id="column_campaign_pos" class="small-text" min="0" type="number" value="<?php echo esc_attr($cfg['column_campaign_pos']); ?>" /> 
												</div>
												<p class="description"><?php echo html_entity_decode($helptip['column_campaign_pos'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" id="disable_metaboxes_wpematico_posts" type="checkbox"<?php checked($cfg['disable_metaboxes_wpematico_posts'], true); ?> name="disable_metaboxes_wpematico_posts" value="1"/> 
										<label><?php _e('Hide the campaign box in the post editor', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['disable_metaboxes_wpematico_posts'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" id="emptytrashbutton" type="checkbox"<?php checked($cfg['emptytrashbutton'], true); ?> name="emptytrashbutton" value="1"/> 
										<label><?php _e('Empty trash button on the lists', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['emptytrashbutton'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
									<div id="hlptrash" class="wpematico_form-inner" <?php if (!$cfg['emptytrashbutton']) echo 'style="display:none;"'; ?>>
										<div class="wpematico_form-row">
											<div class="wpematico_form-column">
												<label><?php _e('Post types it applies to', 'wpematico'); ?></label>
												<div class="wpematico_checkbox-content">
													<?php
													// public and private so that you can display the button on all of them.
													$args		= array('public' => false);
													$args		= array();
													$output		= 'names'; // names or objects
													$output		= 'objects'; // names or objects
													$cpostypes	= $cfg['cpt_trashbutton'];
													//unset($cpostypes['attachment']);
													$post_types = get_post_types($args, $output);
													foreach ($post_types as $post_type_obj) {
														$post_type			   = $post_type_obj->name;
														$post_label			   = $post_type_obj->labels->name;
														if ($post_type == 'revision')
															continue;  // ignore 'attachment'
														if ($post_type == 'nav_menu_item')
															continue;  // ignore 'attachment'
														echo '<div class="wpematico_checkbox-group"><input type="checkbox" class="checkbox" name="cpt_trashbutton[' . $post_type . ']" value="1" ';
														if (!isset($cpostypes[$post_type]))
															$cpostypes[$post_type] = false;
														checked($cpostypes[$post_type], true);
														echo ' /> ' . __($post_label, 'default') . ' (' . __($post_type, 'default') . ')</div>';
													}
													?>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['disabledashboard'], true); ?> name="disabledashboard" id="disabledashboard" /> 
										<label><?php _e('Hide the WP dashboard widget', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php echo html_entity_decode($helptip['disabledashboard'], ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></p>
									<div id="roles" class="wpematico_form-inner" <?php if ($cfg['disabledashboard']) echo 'style="display:none;"'; ?>>
										<div class="wpematico_form-row">
											<div class="wpematico_form-column">
												<label><?php _e('Roles that see the dashboard widget:', 'wpematico'); ?></label>
												<div class="wpematico_checkbox-content">
													<?php
													if (!isset($cfg['roles_widget']))
														$cfg['roles_widget'] = array("administrator" => "administrator");
													$role_select		 = '<input type="hidden" name="roles_widget[administrator]" value="administrator" />';
													foreach ($wp_roles->role_names as $role => $name) {
														$name = _x($name, 'wpematico', 'default');
														if ($role != 'administrator') {
															if (array_search($role, $cfg['roles_widget'])) {
																$checked = 'checked="checked"';
															} else {
																$checked = '';
															}
															$role_select .= "<div class='wpematico_checkbox-group'><input style='margin:0 5px;' $checked type='checkbox' name='roles_widget[$role]' value='$role' />$name</div>";
														}
													}
													echo $role_select;
													?>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="wpematico_form-row">
								<div class="wpematico_form-column">
									<div class="wpematico_switch wpematico_switch-horizontal">
										<input class="checkbox" value="1" type="checkbox" <?php checked(WPeMatico_Tour::is_on(), true); ?> name="wpematico_user_tours" id="wpematico_user_tours" /> 
										<label for="wpematico_user_tours"><?php esc_html_e('Guided tours', 'wpematico'); ?></label>
									</div>
									<p class="description"><?php esc_html_e('Shows a short tour the first time you open each WPeMatico screen. Only for your user.', 'wpematico'); ?></p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		public static function settings_wpematico_lastlog() {
			if (!get_option('wpematico_lastlog_disabled')):

				$fetch_feed_params = array(
					'url'					   => 'https://www.wpematico.com/releases/feed/',
					'stupidly_fast'			   => true,
					'max'					   => 0,
					'order_by_date'			   => true,
					'force_feed'			   => false,
					'disable_simplepie_notice' => true,
				);

				$feed = WPeMatico::fetchFeed($fetch_feed_params);
				?>
				<div id="wpe_changelog-notice" class="wpe_changelog-notice" style="display: none;">
					<div class="wpe_changelog-header">
						<div class="wpe_changelog-header-img">
							<img src="<?php echo WPEMATICO_PLUGIN_URL; ?>/images/robotico_orange-75x130.png" alt="">
						</div>
						<div class="wpe_changelog-header-content">
							<h2>WPeMatico RSS Feed Fetcher</h2>
							<p><?php _e('Highlights of the new release', 'wpematico'); ?></p>
							<h4><?php _e('Version', 'wpematico'); ?> <span><?php echo WPEMATICO_VERSION ?></span></h4>
						</div>
					</div>
					<div class="wpe_changelog-content">
						<div class="wpe_changelog-list">
							<?php
							foreach ($feed->get_items(0, 1) as $item) {
								$content = $item->get_description();
								echo $content;
							}
							?>
						</div>
						<p><a href="https://www.wpematico.com/releases/" class="button" target="_blank"><span class="dashicons dashicons-arrow-right-alt"></span> <?php _e('Read more on wpematico.com', 'wpematico'); ?></a></p>
						<br>
						<h3><?php _e('Your opinion about WPeMatico is very important to us.', 'wpematico'); ?></h3>
						<p><?php _e('By rating WPeMatico RSS Feed Fetcher, you help the plugin creators to improve their work and help other users to find the software they need. You can write your review on WordPress forum. Use the language of your choice, we appreciate it!', 'wpematico'); ?></p>
						<p><a href="https://wordpress.org/support/view/plugin-reviews/wpematico?filter=5&rate=5#new-post" class="button" target="_blank"><span class="dashicons dashicons-star-filled"></span> <?php _e('Rate us on WordPress', 'wpematico'); ?></a></p>
						<div class="wpe_changelog-dismiss">
							<a id="button_yes_changelog" class="button awesome"><span><?php _e('YES!', 'wpematico'); ?></span></a>
						</div>
					</div>
				</div>
				<?php
			endif;
		}

		/**
		 * The heading of the settings screen: the name of the tab being shown, so an
		 * add-on's tab is not headed "General Settings".
		 *
		 * A label may carry markup -- below 2.9 it is where the tab icon lives -- so it
		 * is stripped down to its text.
		 *
		 * @param string $tab
		 * @param array  $tabs
		 * @return string
		 */
		public static function page_title($tab, $tabs) {
			$title = ('settings' === $tab)
				? __('General Settings', 'wpematico')
				: (isset($tabs[$tab]) ? trim(wp_strip_all_tags($tabs[$tab])) : '');

			if ('' === $title) {
				$title = __('Settings', 'wpematico');
			}

			return apply_filters('wpematico_settings_page_title', $title, $tab);
		}

		/** Sections of a tab. @see WPeMatico::tab_sections() */
		public static function tab_sections($tab) {
			return WPeMatico::tab_sections($tab);
		}

		/**
		 * The tab being rendered. An unknown one falls back to the default.
		 */
		public static function current_tab() {
			$tab  = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : '';
			$tabs = wpematico_get_settings_tabs();
			return isset($tabs[$tab]) ? $tab : 'settings';
		}

		/** The section being rendered. @see WPeMatico::current_screen_section() */
		public static function current_section($tab, $sections = null) {
			return WPeMatico::current_screen_section($tab, $sections);
		}

		/**
		 * Bounce an unknown tab or section to the default, on load-{$page_hook}.
		 * The whole reasoning lives on the shared helper.
		 */
		public static function validate_request() {
			WPeMatico::validate_screen_request('wpematico_settings', wpematico_get_settings_tabs(), 'settings');
		}

		public static function settings_header() {
			global $cfg, $current_screen, $helptip, $current_tab, $current_section, $tabs, $sections;

			// The settings screen is rendered for administrators only.
			if (!current_user_can('manage_options'))
				return;

			if (!isset($current_screen))
				wp_die(esc_html__('Invalid request.', 'wpematico'), esc_html__('Invalid request', 'wpematico'), array('response' => 400));

			$current_tab	 = self::current_tab();
			$tabs			 = wpematico_get_settings_tabs();
			$sections		 = self::tab_sections($current_tab);
			$current_section = '';
			if (!empty($sections)) {
				$current_section = self::current_section($current_tab, $sections);
				add_action('wpematico_settings_tab_' . $current_tab, 'wpematico_print_tab_' . $current_tab . '_' . $current_section, 0, 1);
			}

			$cfg = get_option(WPeMatico::OPTION_KEY);
			$cfg = apply_filters('wpematico_check_options', $cfg);

			if (!class_exists('SimplePie')) {
				if (is_file(ABSPATH . WPINC . '/class-simplepie.php'))
					include_once(ABSPATH . WPINC . '/class-simplepie.php');
				else if (is_file(ABSPATH . 'wp-admin/includes/class-simplepie.php'))
					include_once(ABSPATH . 'wp-admin/includes/class-simplepie.php');
			}

			$simplepie			   = new SimplePie();
			$simplepie->timeout	   = apply_filters('wpe_simplepie_timeout', 30);
			$cfg['strip_htmltags'] = (!($cfg['simplepie_strip_htmltags'])) ? implode(',', $simplepie->strip_htmltags) : $cfg['strip_htmltags'];
			$cfg['strip_htmlattr'] = (!($cfg['simplepie_strip_attributes'])) ? implode(',', $simplepie->strip_attributes) : $cfg['strip_htmlattr'];
			$cfg['mailsndemail']   = (!($cfg['mailsndemail']) || empty($cfg['mailsndemail'])) ? 'noreply@' . str_ireplace('www.', '', parse_url(get_option('siteurl'), PHP_URL_HOST)) : $cfg['mailsndemail'];
			$cfg['mailsndname']	   = (!($cfg['mailsndname']) or empty($cfg['mailsndname'])) ? 'WPeMatico Log' : $cfg['mailsndname'];
			//$cfg['mailpass']		= (!($cfg['mailpass']) or empty($cfg['mailpass']) ) ? '' : bas 64_ d co d ($cfg['mailpass']);

			$helptip = wpematico_helpsettings('tips');
			?>
			<div class="wrap" style="padding-top: 30px;">
				<h1 class="hidden"><?php _e('Settings', 'wpematico') ?></h1>
				<div class="wpematico_container show_menu">
					<div class="wpm-wrap-notices"></div>
					<div class="wpematico_content">
						<div class="wpematico_header">
							<h2><?php echo esc_html(self::page_title($current_tab, $tabs)); ?></h2>
						</div>
						<div class="postbox">
							<div class="wpematico_flex">
								<?php echo wpematico_settings_page_menu(); ?>

								<div class="wpematico_main">
									<form id="wpematico-settings-form" action="<?php echo admin_url('admin-post.php'); ?>" name="wpematico-settings" method="post" autocomplete="off">
										<?php
										wp_nonce_field('wpematico-settings');
										/* Used to save closed meta boxes and their order */
										wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false);
										wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);
										?>
										<input type="hidden" name="action" value="save_wpematico_settings" />
										<input type="hidden" name="wpematico_tab" value="<?php echo esc_attr($current_tab); ?>" />
										<input type="hidden" name="wpematico_section" value="<?php echo esc_attr($current_section); ?>" />
										<div class="wpematico_head">
											<div class="wpematico_buttons">
												<?php submit_button(__('Save settings', 'wpematico'), 'primary', 'wpematico-save-settings', false, array('form' => 'wpematico-settings-form')); ?>
												<?php /* <a href="<?php wp_nonce_url(admin_url('admin-post.php?action=reset_to_default_general_settings'), 'reset_to_default_general_settings', '_wpnonce') ?>" class="button btn_reset_to_default"><?php _e('Reset to default', 'wpematico') ?></a> */ ?>
											</div> <!-- wpematico_buttons -->
										</div>
										<div id="poststuff">

											<div id="post-body-content">
												<!-- #post-body-content -->
											</div>
											<?php
											ob_start();
											do_action('wpematico_setting_page_before');
											if (empty($sections))
												do_action('wpematico_settings_tab_' . $current_tab);
											else
												do_action('wpematico_settings_section_' . $current_section);
											$section_html = ob_get_clean();
											?>
											<input type="hidden" name="wpematico_rendered_fields" value="<?php echo esc_attr(implode(',', self::rendered_field_names($section_html))); ?>" />
											<?php echo $section_html; ?>

										</div> <!-- poststuff -->
										<div class="wpematico_footer">
											<?php echo wpematico_get_menus_social_footer(); ?>

											<div class="wpematico_buttons">
												<?php submit_button(__('Save settings', 'wpematico'), 'primary', 'wpematico-save-settings', false, array('form' => 'wpematico-settings-form')); ?>
												<?php /* <a href="<?php wp_nonce_url(admin_url('admin-post.php?action=reset_to_default_general_settings'), 'reset_to_default_general_settings', '_wpnonce') ?>" class="button btn_reset_to_default"><?php _e('Reset to default', 'wpematico') ?></a> */ ?>
											</div> <!-- wpematico_buttons -->
										</div>
									</form>
								</div>
							</div>
						</div>
					</div>

					<div class="wpematico_sidebar">
						<div id="postbox-container-1" class="postbox-container">
							<?php 
								WPeMatico_Settings_widgets::render($current_tab, $current_section);
							?>
						</div><!--  postbox-container-1 -->
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Names of the fields a section actually printed. All settings share one option,
		 * but the form only ever shows one section, so the save needs to know which keys
		 * this submit is entitled to overwrite.
		 */
		public static function rendered_field_names($html) {
			$skip  = array('action', 'wpematico_tab', 'wpematico_section', 'wpematico_rendered_fields', 'wpematico-save-settings', 'submit');
			$names = array();
			if (preg_match_all('/<(?:input|select|textarea)\b[^>]*\sname=(["\'])(.*?)\1/is', $html, $matches)) {
				foreach ($matches[2] as $name) {
					$name = preg_replace('/\[.*$/', '', html_entity_decode($name, ENT_QUOTES));
					$name = preg_replace('/[^A-Za-z0-9_\-]/', '', $name);
					if ('' === $name || '_' === $name[0] || in_array($name, $skip, true))
						continue;
					$names[$name] = $name;
				}
			}
			return array_values($names);
		}

		/**
		 * A checkbox that is off simply does not post, and each section posts only its own
		 * fields, so building the whole option out of $_POST turned off every setting the
		 * visible section did not render. Start from what is stored and let the submit
		 * replace only the fields it rendered.
		 */
		private static function merge_posted_settings() {
			$stored = get_option(WPeMatico::OPTION_KEY);
			if (!isset($_POST['wpematico_rendered_fields']) || !is_array($stored))
				return $_POST;
			foreach (explode(',', wp_unslash($_POST['wpematico_rendered_fields'])) as $key) {
				$key = preg_replace('/[^A-Za-z0-9_\-]/', '', $key);
				if ('' !== $key)
					unset($stored[$key]);
			}
			return array_merge($stored, $_POST);
		}

		public static function settings_save() {
			if ('POST' === $_SERVER['REQUEST_METHOD']) {
				// Site settings are administrator territory.
				if (!current_user_can('manage_options'))
					wp_die(esc_html__('You are not allowed to do this.', 'wpematico'), esc_html__('Permission denied', 'wpematico'), array('response' => 403));
				check_admin_referer('wpematico-settings');
				$errlev = error_reporting();
				error_reporting(E_ALL & ~E_NOTICE);

				$current_tab = isset($_POST['wpematico_tab']) ? sanitize_text_field($_POST['wpematico_tab']) : 'settings';

				// A preference of the user saving, not of the site: it lives in their user meta.
				$rendered = isset($_POST['wpematico_rendered_fields']) ? explode(',', wp_unslash($_POST['wpematico_rendered_fields'])) : array();
				if (in_array('wpematico_user_tours', $rendered, true)) {
					WPeMatico_Tour::set_on(!empty($_POST['wpematico_user_tours']));
				}

				// Save core plugin settings on every tab except the licenses tab
				if ($current_tab !== 'pro_licenses') {
					$cfg = apply_filters('wpematico_check_options', self::merge_posted_settings());
					if (!wpematico_is_pro_active())
						$cfg['nonstatic'] = false;
					else
						$cfg['nonstatic'] = true;
					wp_get_current_user();

					wp_clear_scheduled_hook('wpematico_cron');
					if (isset($cfg['disablewpcron']) && $cfg['disablewpcron']) {
						define('DISABLE_WP_CRON', true);
					}
					if (isset($cfg['enable_alternate_wp_cron']) && $cfg['enable_alternate_wp_cron']) {
						if (!defined('ALTERNATE_WP_CRON')) {
							define('ALTERNATE_WP_CRON', true);
						}
					}
					if (!(isset($cfg['dontruncron']) && $cfg['dontruncron'])) {
						wp_schedule_event(time(), 'wpematico_int', 'wpematico_cron');
					}

					if (update_option(WPeMatico::OPTION_KEY, $cfg)) {
						WPeMatico::add_wp_notice(array('text' => __('Settings saved.', 'wpematico'), 'below-h2' => false));
					}
				}

				// Save license keys when present (licenses tab)
				if (!empty($_POST['license_key']) && is_array($_POST['license_key'])) {
					$keys = array_map('sanitize_key', $_POST['license_key']);
					update_option('wpematico_license_keys', $keys);
					$plugins_args = apply_filters('wpematico_plugins_updater_args', array());
					foreach ($keys as $plugin_name => $key) {
						if (empty($plugins_args[$plugin_name]) || empty($key)) continue;
						$check_args = array(
							'license'   => $key,
							'item_name' => urlencode($plugins_args[$plugin_name]['api_data']['item_name']),
							'url'       => home_url(),
							'version'   => $plugins_args[$plugin_name]['api_data']['version'],
							'author'    => 'Esteban Truelsegaard',
						);
						$license_data = wpematico_licenses_handlers::check_license($plugins_args[$plugin_name]['api_url'], $check_args);
						if (is_object($license_data)) {
							wpematico_licenses_handlers::set_license_status($plugin_name, $license_data->license);
						}
					}
					WPeMatico::add_wp_notice(array('text' => __('License keys saved.', 'wpematico'), 'below-h2' => false));
				}

				error_reporting($errlev);

				// Back to the section the form was submitted from: a tab with no section
				// in the URL resolves to its first one.
				$target	  = array('page' => 'wpematico_settings', 'tab' => $current_tab);
				$sections = self::tab_sections($current_tab);
				$section  = isset($_POST['wpematico_section']) ? sanitize_text_field(wp_unslash($_POST['wpematico_section'])) : '';
				if ('' !== $section && isset($sections[$section]))
					$target['section'] = $section;

				wp_safe_redirect(add_query_arg($target, admin_url('admin.php')));
			}
		}

		/**
		 * Help tabs of the Settings screen. On current_screen, against the screen being
		 * rendered -- never a WP_Screen::get() of a hardcoded id.
		 *
		 * @param WP_Screen $screen
		 */
		public static function settings_help($screen) {
			if (!($screen instanceof WP_Screen) || 'wpematico_page_wpematico_settings' !== $screen->id)
				return;

			// These describe the core settings; an addon's tab brings its own.
			$current_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'settings';
			if ('settings' === $current_tab) {
				foreach (wpematico_helpsettings() as $key => $section) {
					$tabcontent = '';
					foreach ($section as $section_key => $sdata) {
						$helptip[$section_key] = htmlentities($sdata['tip']);
						$tabcontent			   .= '<p><strong>' . $sdata['title'] . '</strong><br />' .
								$sdata['tip'] . '</p>';
						$tabcontent			   .= (isset($sdata['plustip'])) ? '<p style="margin-top: 2px;margin-left: 7px;">' . $sdata['plustip'] . '</p>' : '';
					}
					$screen->add_help_tab(array(
						'id'	  => $key,
						'title'	  => $key,
						'content' => $tabcontent,
					));
				}
			}
		}
	}

	endif;

WPeMatico_Settings::hooks();
