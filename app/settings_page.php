<?php
// don't load directly 
if(!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

/**
 * WPeMatico Pro Extra Settings Class 
 * This class is used to add the Professional Extra Settings 
 * @since 2.2
 */
if(!class_exists('WPeMatico_Settings')) :

	class WPeMatico_Settings {

		public static function hooks() {
			add_action('wpematico_settings_tab_pro_licenses', array(__CLASS__, 'wpematicopro_licenses'));
			add_action('wpematico_settings_tab_settings', array(__CLASS__, 'settings_form'));
			add_action('admin_post_save_wpematico_settings', array(__CLASS__, 'settings_save'));
			add_action('admin_init', array(__CLASS__, 'settings_help'));
			add_action('wp_ajax_process_button_click', array(__CLASS__,'process_button_click'));
			add_action('wp_ajax_nopriv_process_button_click', array(__CLASS__,'process_button_click'));
		}

		public static function process_button_click() {
			// Verify the nonce
			$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
			if (!wp_verify_nonce($nonce, 'wpematico-settings-page-nonce')) {
				wp_send_json_error(__('Permission check failed', 'wpematico'));
			}
		
			// Retrieve the value from the AJAX request
			$value = isset($_POST['value']) ? true : false;
			$updateOption = false;
			if($value)
				$updateOption = update_option('wpematico_lastlog_disabled', $value);

			if($updateOption){
				// Process the value (you can customize this part)
				$response = [
					'message' => __('Processing successful', 'wpematico'),
					'color' => 'success',
				];
				// Send the response back to the frontend
				wp_send_json_success($response);
			}else{
				wp_send_json_error();
			}
		}

		/**
		 * 		Called by function admin_menu() on wpematico_class
		 */
		public static function styles() {
			global $cfg;
			wp_enqueue_style('WPematStylesheet');
			wp_enqueue_script('WPemattiptip');
			add_action('admin_head', array(__CLASS__, 'wpematico_settings_head'));
			wp_enqueue_script('postbox');
			// Enqueue jQuery UI and autocomplete
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-autocomplete');

			wp_enqueue_script('wpematico_settings_page', WPeMatico::$uri . 'app/js/settings_page.js', array('jquery', 'postbox'), WPEMATICO_VERSION, true);
			//			$allowedmimes = array_diff(explode(',', WPeMatico::get_images_allowed_mimes()), explode(',', $cfg['images_allowed_ext']));
			$wpematico_object = array(
				'text_invalid_email' => __('Invalid email.', 'wpematico'),
//				'current_img_mimes'	 => $allowedmimes,
				'nonce'    => wp_create_nonce('wpematico-settings-page-nonce')
			);
			wp_localize_script('wpematico_settings_page', 'wpematico_object', $wpematico_object);

			/* Add screen option: user can choose between 1 or 2 columns (default 2) */
			//add_screen_option('layout_columns', array('max' => 2, 'default' => 2) );
		}

		public static function wpematico_settings_head() {
			?>
			<style type="text/css">
				.insidesec {display: inline-block; vertical-align: top;}
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
			if(!isset($current_screen))
				wp_die("Cheatin' uh?", "Closed today.");
			?>
			<div id="licenses">
				<div class="postbox ">
					<div class="inside">
						<?php
						/*						 * * Display license page */
						settings_errors();
						if(!has_action('wpempro_licenses_forms')) {
							echo '<div class="msg"><p>', __('This is where you would enter the license keys for one of our premium plugins, should you activate one.', 'wpematico'), '</p>';
							echo '<p>', __('See some of the WPeMatico Add-ons in the', 'wpematico'), ' <a href="', admin_url('plugins.php?page=wpemaddons') . '">Extensions list</a>.</p></div>';
						}else {
							do_action('wpempro_licenses_forms');
						}
						?>
					</div>
				</div>
			</div>
			<?php
		}

		public static function settings_form() {
			global $cfg, $current_screen, $helptip;
			$fifu_activated = defined( 'FIFU_PLUGIN_DIR' );
			
			if(!isset($current_screen))
				wp_die("Cheatin' uh?", "Closed today.");
			$cfg = get_option(WPeMatico :: OPTION_KEY);
			$cfg = apply_filters('wpematico_check_options', $cfg);
			
			if (!class_exists('SimplePie')) {
				if (is_file(ABSPATH . WPINC . '/class-simplepie.php'))
					include_once(ABSPATH . WPINC . '/class-simplepie.php');
				else if (is_file(ABSPATH . 'wp-admin/includes/class-simplepie.php'))
					include_once(ABSPATH . 'wp-admin/includes/class-simplepie.php');
			}

			$simplepie				 = new SimplePie();
			$simplepie->timeout		 = apply_filters('wpe_simplepie_timeout', 30);
			$cfg['strip_htmltags']	 = (!($cfg['simplepie_strip_htmltags'])) ? implode(',', $simplepie->strip_htmltags) : $cfg['strip_htmltags'];
			$cfg['strip_htmlattr']	 = (!($cfg['simplepie_strip_attributes'])) ? implode(',', $simplepie->strip_attributes) : $cfg['strip_htmlattr'];
			$cfg['mailsndemail']	 = (!($cfg['mailsndemail']) || empty($cfg['mailsndemail']) ) ? 'noreply@' . str_ireplace('www.', '', parse_url(get_option('siteurl'), PHP_URL_HOST)) : $cfg['mailsndemail'];
			$cfg['mailsndname']		 = (!($cfg['mailsndname']) or empty($cfg['mailsndname']) ) ? 'WPeMatico Log' : $cfg['mailsndname'];
			//$cfg['mailpass']		= (!($cfg['mailpass']) or empty($cfg['mailpass']) ) ? '' : bas 64_ d co d ($cfg['mailpass']);
			$disable_extensions_feed = '';
			if(defined('MULTISITE') && MULTISITE){
				$disable_extensions_feed = 'disabled';
			}
			$helptip	 = wpematico_helpsettings('tips');
			?>
			<div class="wrap">
				<h2><?php _e('WPeMatico settings', 'wpematico'); ?></h2>
				<form  action="<?php echo admin_url('admin-post.php'); ?>" name="wpematico-settings" method="post" autocomplete="off" >
					<?php
					wp_nonce_field('wpematico-settings');
					/* Used to save closed meta boxes and their order */
					wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false);
					wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);
					?>
					<div id="poststuff">
						<?php
						if(!get_option('wpematico_lastlog_disabled')): 

						$fetch_feed_params = array(
							'url' 			=> 'https://www.wpematico.com/releases/feed/',
							'stupidly_fast' => true,
							'max' 			=> 0,
							'order_by_date' => true,
							'force_feed' 	=> false,
							'disable_simplepie_notice' => true,
						);

						$feed = WPeMatico::fetchFeed($fetch_feed_params);
						?>
						<div id="wpe_changelog-notice" class="wpe_changelog-notice">
							<div class="wpe_changelog-header">
								<div class="wpe_changelog-header-img">
									<img src="<?php echo WPeMatico :: $uri; ?>/images/robotico_orange-75x130.png" alt="">
								</div>
								<div class="wpe_changelog-header-content">
									<h2>WPeMatico RSS Feed Fetcher</h2>
									<p><?php _e('Highlights of the new release', 'wpematico'); ?></p>
									<h4><?php _e('Version', 'wpematico'); ?> <span><?php echo WPEMATICO_VERSION ?></span></h4>
								</div>
							</div>
							<div class="wpe_changelog-content">
								<div class="wpe_changelog-list">
									<?php foreach ($feed->get_items(0, 1) as $item) {
											$content = $item->get_description();
											echo $content;
										} ?>
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
						<?php endif; ?>
						<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
							<div id="post-body-content">
								<!-- #post-body-content -->
							</div>
							<div id="postbox-container-1" class="postbox-container">
								<div id="side-sortables" class="meta-box-sortables ui-sortable">
									<div id="wpem-about" class="postbox">
										<button type="button" class="handlediv button-link" aria-expanded="true">
											<span class="screen-reader-text"><?php _e('Click to toggle'); ?></span>
											<span class="toggle-indicator" aria-hidden="true"></span>
										</button>
										<h2 class="hndle"><?php _e('About', 'wpematico'); ?></h2>
										<div class="inside">
											<p><b>WPeMatico</b> <?php echo WPEMATICO_VERSION; ?> Version</p>
											<p class="icon_version">
												<a href="http://www.wpematico.com" target="_Blank" title="<?php _e('Go to the new WPeMatico WebSite', 'wpematico'); ?>">
													<img class="logover" src="<?php echo WPeMatico :: $uri; ?>/images/robotico-helmet.png" title="">	
													<span id="wpematico-website">WPeMatico Website</span><br>
												</a><span id="wpematico-websiteinfo"><?php _e('Comments and Tutorials', 'wpematico'); ?></span>
											</p>
											<p class="icon_version">
												<a href="https://etruel.com" target="_Blank" title="<?php _e('WPeMatico Addons in etruel.com store', 'wpematico'); ?>">
													<img class="logover" src="<?php echo WPeMatico :: $uri; ?>/images/etruelcom_ico.png" title="">	
													<span id="wpematico-etruel">etruel.com</span><br>
												</a><span id="wpematico-store"><?php _e('Addons store, FAQs', 'wpematico'); ?><br/>
													<?php _e('and <b>Free</b> Support', 'wpematico'); ?></span>
											</p>
											<p><?php _e('Thanks for use and test this plugin.', 'wpematico'); ?></p>
											<p></p>
											<p><?php _e('If you like this plugin, you can write a 5 star review on Wordpress.', 'wpematico'); ?></p>
											<style type="text/css">#linkrate:before { content: "\2605\2605\2605\2605\2605";font-size: 18px;}
												#linkrate { font-size: 18px;}</style>
											<p style="text-align: center;">
												<a href="https://wordpress.org/support/view/plugin-reviews/wpematico?filter=5&rate=5#new-post" id="linkrate" class="button" target="_Blank" title="Click here to rate plugin on Wordpress">  Rate </a>
											</p>
											<p></p>
											<div id="improvescampaign" style="border: 1px #ccc solid;padding-bottom: 5px;" 
												 onmouseover="javascript:jQuery('#improbuttons').stop().animate({paddingTop: '35px'}, 500);">
												<div id="improlabel" style="position:absolute;padding-top: 5px;padding-bottom: 9px;background: #EB9600;margin: 0;color: #fff;text-align: center;text-shadow: #333 1px 1px 2px;width: 91%;font-size: initial;font-weight: bold;">
													Improve your Experience
												</div>
												<div id="improbuttons" style="text-align: center;padding-top: 0px;">
													<input onmouseover="javascript:jQuery(this).addClass('button-primary');" onmouseout="javascript:jQuery(this).removeClass('button-primary');" class="button" name="buypro" value="GO PRO" onclick="javascript:window.open('https://etruel.com/downloads/wpematico-essentials/');return false;" type="button">
													&nbsp;
													<input name="buypre" value="PREMIUM" onmouseover="javascript:jQuery(this).addClass('button-primary');" onmouseout="javascript:jQuery(this).removeClass('button-primary');" onclick="javascript:window.open('https://etruel.com/downloads/wpematico-premium/');return false;" class="button" type="button">
													&nbsp;
													<input class="button" name="buyper" value="PERFECT" onmouseover="javascript:jQuery(this).addClass('button-primary');" onmouseout="javascript:jQuery(this).removeClass('button-primary');" onclick="javascript:window.open('https://etruel.com/downloads/wpematico-perfect/');return false;" type="button">
												</div>
											</div>
											<p></p>
										</div>
									</div>

									<div id="wpem-advanced-actions" class="postbox">
										<h2 style="background-color: yellow;" class="handle"><span class="dashicons dashicons-admin-settings"></span> <?php _e('Advanced Actions', 'wpematico'); ?></h2>
										<div class="inside">
											<p></p>
											<label><input class="checkbox" value="1" type="checkbox" <?php checked($cfg['disablecheckfeeds'], true); ?> name="disablecheckfeeds" id="disablecheckfeeds" /> <?php _e('Disable Check Feeds before Save', 'wpematico'); ?></label><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['disablecheckfeeds']; ?>"></span>
											<p></p>
											<label><input class="checkbox" value="1" type="checkbox" <?php checked($cfg['enabledelhash'], true); ?> name="enabledelhash" id="enabledelhash" /><b>&nbsp;<?php _e('Enable "Del Hash"', 'wpematico'); ?></b></label> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['enabledelhash']; ?>"></span>
											<p></p>
											<label><input class="checkbox" value="1" type="checkbox" <?php checked($cfg['enableseelog'], true); ?> name="enableseelog" id="enableseelog" /><b>&nbsp;<?php _e('Enable "See last log"', 'wpematico'); ?></b></label> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['enableseelog']; ?>"></span>
											<p></p>
											<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['enable_xml_upload'], true); ?> name="enable_xml_upload" id="enable_xml_upload" /><b>&nbsp;<?php _e('Enable "Upload of XMLs"', 'wpematico'); ?></b> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['enable_xml_upload']; ?>"></span>
											<p></p>
											<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['disable_credits'], true); ?> name="disable_credits" id="disable_credits" /><b>&nbsp;<?php _e('Disable "WPeMatico Credits"', 'wpematico'); ?></b> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['disable_credits']; ?>"></span>
											<span id="discredits" style="<?php echo ($cfg['disable_credits']) ? '' : 'display:none;' ?>"><br /><?php
												printf(__('If you can\'t show the WPeMatico credits in your posts, I really appreciate if you can take a minute to %s write a 5 star review on Wordpress %s. :) thanks.', 'wpematico'), '<b><a href="https://wordpress.org/support/view/plugin-reviews/wpematico?filter=5&rate=5#new-post" target="_Blank" title="Open a new window">', '</a></b>');
												?></span>
											<p></p>
											<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['disable_categories_description'], true); ?> name="disable_categories_description" id="disable_categories_description" /><b>&nbsp;<?php _e('Disable "Auto-Category description"', 'wpematico'); ?></b> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['disable_categories_description']; ?>"></span>
											<p></p>	
											<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['disable_extensions_feed_page'], true); echo $disable_extensions_feed ?> name="disable_extensions_feed_page" id="disable_extensions_feed_page" /><b>&nbsp;<?php _e('Disable Extensions feed Page', 'wpematico'); ?></b> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['disable_extensions_feed_page']; ?>"></span>
											<p></p>	
											<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['entity_decode_html'], true); ?> name="entity_decode_html" id="entity_decode_html" /><b>&nbsp;<?php _e('HTML entity decode on publish.', 'wpematico'); ?></b> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['entity_decode_html']; ?>"></span>


											<p style="text-align: right;">
												<input type="hidden" name="action" value="save_wpematico_settings" />
												<?php submit_button(__('Save settings', 'wpematico'), 'primary', 'wpematico-save-settings', false); ?>
											</p>								
										</div>
									</div>

									<div id="wpem-email-settings" class="postbox">
										<button type="button" class="handlediv button-link" aria-expanded="true">
											<span class="screen-reader-text"><?php _e('Click to toggle'); ?></span>
											<span class="toggle-indicator" aria-hidden="true"></span>
										</button>
										<h2 class="handle"><span class="dashicons dashicons-email-alt"></span> <?php _e('Sending e-Mails', 'wpematico'); ?></h2>
										<div class="inside">
											<label><b><?php _e('Sender Email:', 'wpematico'); ?></b><br /><input name="mailsndemail" id="mailsndemail" type="text" value="<?php echo esc_attr($cfg['mailsndemail']); ?>" class="large-text" /><span id="mailmsg"></span></label>
											<label><b><?php _e('Sender Name:', 'wpematico'); ?></b><br /><input name="mailsndname" type="text" value="<?php echo esc_attr($cfg['mailsndname']); ?>" class="large-text" /></label>
											<input type="hidden" name="mailmethod" value="<?php echo esc_attr($cfg['mailmethod']); // "mailmethod"="mail" or "mailmethod"="SMTP"                         ?>">
											<label id="mailsendmail" <?php if($cfg['mailmethod'] != 'Sendmail') echo 'style="display:none;"'; ?>><b><?php _e('Sendmail Path:', 'wpematico'); ?></b><br /><input name="mailsendmail" type="text" value="<?php echo esc_attr($cfg['mailsendmail']); ?>" class="large-text" /><br /></label>
										</div>
									</div>

									<div id="promo-extended" class="postbox " >
										<div class="ribbon"><span>HOT SALES</span></div>
										<button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Essentials</span><span class="toggle-indicator" aria-hidden="true"></span></button>
										<h2 class='hndle'><span>Starter Bundled Extensions</span></h2>
										<div class="inside">
											<div class="sidebar-promo worker" id="sidebar-promo">
												<h3><span class="dashicons dashicons-welcome-learn-more" style="font-size-adjust: 1;width: 50px;"></span><?php _e('Extended functionalities', 'wpematico'); ?></h3>
												<p>
													<?php
													echo sprintf(__('Many AddOns make up the %s with the most wanted features.') . '  ', '<a href="https://etruel.com/starter-packages/" target="_blank" rel="noopener"><strong>Starter Packages</strong></a>');
													?> 
													<span>
														<?php _e('Lot of new features with contents, images, tags, filters, custom fields, custom feed tags and much more extends in the WPeMatico free plugin, going further than RSS feed limits and takes you to a new experience.', 'wpematico'); ?>
													</span>
												</p>
												<p style="text-align: center;">
													<a class="button button-primary" title="Features and prices" href="https://etruel.com/starter-packages/" target="_blank"><?php _e('Starter Packages Page', 'wpematico'); ?></a>
												</p>
											</div>
										</div>
									</div>

									<div id="promo-content" class="postbox">
										<button type="button" class="handlediv" aria-expanded="true">
											<span class="screen-reader-text">Toggle panel: Support</span>
											<span class="toggle-indicator" aria-hidden="true"></span>
										</button>
										<h2 class='hndle'><span>Support</span></h2>
										<div class="inside">
											<div class="sidebar-promo" id="sidebar-promo">
												<h3><span class="dashicons dashicons-sos" style="font-size-adjust: 1;width: 50px;"></span><?php _e('Have some questions?', 'wpematico'); ?></h3>
												<p>
													<?php _e('You may find answers in our', 'wpematico'); ?> <a target="_blank" href="https://etruel.com/faqs/">FAQ</a><br><?php _e('You may', 'wpematico'); ?> <a target="_blank" href="https://etruel.com/my-account/support/"><?php _e('contact us', 'wpematico'); ?></a> <?php _e('with customization requests and suggestions.', 'wpematico'); ?><br> 
													<?php _e('Please visit our website to learn about our free and premium services at', 'wpematico'); ?> <a href="https://etruel.com/downloads/premium-support/" target="_blank" title="etruel.com">etruel.com</a>
												</p>
											</div>
										</div>
									</div>

									<div id="promo-translate" class="postbox " >
										<button type="button" class="handlediv" aria-expanded="true">
											<span class="screen-reader-text">Toggle panel: Translation</span>
											<span class="toggle-indicator" aria-hidden="true"></span>
										</button>
										<h2 class='hndle'><span>Translation</span></h2>
										<div class="inside">
											<div class="sidebar-promo" id="sidebar-translate">
												<h3 class="translate"><span class="dashicons dashicons-translation" style="font-size-adjust: 1;width: 50px;"></span><?php _e('Translation friendly', 'wpematico'); ?></h3>
												<p><?php _e('Want to improve the texts or translate the plugin to your native language?', 'wpematico'); ?></p>
												<label style="text-align: center;font-weight: bold;margin: 10px;" onclick="jQuery('#howtranslate').toggle();">Show / Hide steps</label>
												<ol id="howtranslate" style="display: none;">
													Download <a href="https://poedit.net/wordpress" target="_blank" title="See the docs">Poedit</a>.<br />
													Download <a href="https://downloads.wordpress.org/plugin/wpematico.zip" target="_blank" title="Get it from wp.org">WPeMatico</a>.<br />
													<li>Launch Poedit.</li>
													<li>Edit a translation using existing .po file in lang folder.
														In case if you find errors in existing translations.</li>
													<li>Create new translation to translate into new language.</li>
												</ol>
											</div>
										</div>
									</div>

									<div class="postbox">
										<h2 class="handle"><?php _e('Perfect Membership', 'wpematico'); ?></h2>
										<div class="inside">
											<p id="left1" onmouseover="jQuery(this).css('opacity', 0.9);this.style.backgroundColor = '#111'; this.style.color = '#fff'" onmouseout="jQuery(this).css('opacity', 0.5);this.style.backgroundColor = '#fff'; this.style.color = 'initial'" style="text-align:center;opacity: 0.5; transition: all .4s ease;"><a href="https://etruel.com/downloads/wpematico-perfect/" target="_Blank" title="Go to etruel WebSite"><img style="width: 100%;" src="https://etruel.com/wp-content/uploads/edd/2022/02/wpematico-perfect.jpg" title=""></a><br />
												WPeMatico Perfect Membership</p>
										</div>
									</div>

									<div class="inside">
										<?php do_action('wpematico_wp_ratings'); ?>
									</div>

								</div>		<!-- #side-sortables -->
							</div>		<!--  postbox-container-1 -->		

							<?php do_action('wpematico_setting_page_before'); ?>
							<div id="postbox-container-2" class="postbox-container">
								<div id="normal-sortables" class="meta-box-sortables ui-sortable">
									<div id="imgs" class="postbox">
										<button type="button" class="handlediv button-link" aria-expanded="true">
											<span class="screen-reader-text"><?php _e('Click to toggle'); ?></span>
											<span class="toggle-indicator" aria-hidden="true"></span>
										</button>
										<h3 class="hndle"><span class="dashicons dashicons-format-image"></span> <span><?php _e('Global Settings for Images', 'wpematico'); ?></span></h3>
										<div class="inside">
											<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['imgcache'], true); ?> name="imgcache" id="imgcache" />&nbsp;<b><label for="imgcache"><?php _e('Store images locally.', 'wpematico'); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['imgcache']; ?>"></span>
											<div id="nolinkimg" style="padding-left:20px; <?php if(!$cfg['imgcache']) echo 'display:none;'; ?>">
												<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['imgattach'], true); ?> name="imgattach" id="imgattach" /><b>&nbsp;<label for="imgattach"><?php _e('Attach Images to posts.', 'wpematico'); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['imgattach']; ?>"></span>
												<br/>
												<input name="gralnolinkimg" id="gralnolinkimg" class="checkbox" value="1" type="checkbox" <?php checked($cfg['gralnolinkimg'], true); ?> /><label for="gralnolinkimg"><?php _e('Remove link to source images', 'wpematico'); ?></label><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['gralnolinkimg']; ?>"></span>
												<br/>
												<input name="image_srcset" id="image_srcset" class="checkbox" value="1" type="checkbox" <?php checked($cfg['image_srcset'], true); ?> /><b>&nbsp;<label for="image_srcset"><?php esc_attr_e('Use srcset attribute instead of src of <img> tag.', 'wpematico'); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['image_srcset']; ?>"></span>
											</div>
											<p></p>
											<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['featuredimg'], true); ?> name="featuredimg" id="featuredimg" /><b>&nbsp;<label for="featuredimg"><?php _e('Set first image in content as Featured Image.', 'wpematico'); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['featuredimg']; ?>"></span>
											<br />
											<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['rmfeaturedimg'], true); ?> name="rmfeaturedimg" id="rmfeaturedimg" /><b>&nbsp;<label for="rmfeaturedimg"><?php _e('Remove Featured Image from content.', 'wpematico'); ?></label></b> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['rmfeaturedimg']; ?>"></span>
											<p></p>
											<div id="custom_uploads" style="<?php if(!$cfg['imgcache'] && !$cfg['featuredimg']) echo 'display:none;'; ?>">
												<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['customupload'], true); ?> name="customupload" id="customupload" /><b>&nbsp;<label for="customupload"><?php _e('Use custom upload.', 'wpematico'); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['customupload']; ?>"></span>
												<p></p>
												<?php
												$comma		 = _x(',', 'mime delimiter');
												$ext_to_edit = (!is_string($cfg['images_allowed_ext'])) ? '' : $cfg['images_allowed_ext'];
												$ext_list	 = WPeMatico::get_images_allowed_mimes();
												?>
												<label for="images_allowed_ext"><b><?php _e('Allowed image extensions to upload'); ?></b><br/>
													<input type="text" class="regular-text" name="images_allowed_ext" id="images_allowed_ext" value="<?php echo str_replace(',', $comma, $ext_to_edit); ?>"/>
												</label>
												<p class="description" id="new-mime-images_allowed_ext-desc"><?php _e('Separate with commas the allowed mime types for WPeMatico Uploads.', 'wpematico'); ?><br /> 
													<?php _e('WordPress image mime types.', 'wpematico'); ?> <label class="description" id="images_allowed_ext-list" title="<?php _e('Click here to restore WP defaults.', 'wpematico') ?>" onclick="jQuery('#images_allowed_ext').val(jQuery(this).text());return false;"><?php echo $ext_list; ?></label><br/>
													<?php _e('Recommended.', 'wpematico'); ?> <label class="description" id="images_allowed_ext-list" title="<?php _e('Click here to set recommended values.', 'wpematico') ?>" onclick="jQuery('#images_allowed_ext').val(jQuery(this).text());return false;"><?php echo "jpg,gif,png,tif,bmp,jpeg"; ?></label>
												</p>
												<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['enablemimetypes'], true); ?> name="enablemimetypes" id="enablemimetypes" /><b>&nbsp;<label for="enablemimetypes"><?php _e('Enable add mime types.', 'wpematico'); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['enablemimetypes']; ?>"></span>
											</div>
											<h3 class="subsection"><?php _e('Featured Image From URL', 'wpematico'); ?></h3>
											<div id="fifu_options">
												<p><input class="checkbox" value="1" type="checkbox" <?php checked($cfg['fifu'], (!$fifu_activated) ? ((!$cfg['fifu']) ? true : false ) : true ); ?> name="fifu" id="fifu"  <?php echo (!$fifu_activated ? 'disabled' : '') ?>/><b>&nbsp;<label for="fifu"><?php _e('Use Featured Image from URL.', 'wpematico'); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['fifu'].' '. __('See more info in Help tab above.','wpematico'); ?>"></span>
												<br />
												<?php
													if(!$fifu_activated){
														echo '<small >';
														echo  __('The', 'wpematico') . ' <a href="https://wordpress.org/plugins/featured-image-from-url/" rel="nofollow" target="_Blank">' . __('Featured Image from URL', 'wpematico') . '</a> ' . __('plugin needs to be installed and activated from the WordPress repository.','wpematico');
														echo '</small><br />';
													}
												?>
												</p>
												<div id="fifu_extra_options" style="padding-left: 20px;<?php if(!$cfg['fifu']) echo 'display:none;'; ?>""><input class="checkbox" value="1" type="checkbox" <?php checked($cfg['fifu-video'], (!$fifu_activated) ? ((!$cfg['fifu-video']) ? true : false ) : true ); ?> name="fifu-video" id="fifu-video"/><b>&nbsp;<label for="fifu-video"><?php _e('Use video link as featured if available.', 'wpematico'); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['fifu'].' '. __('See more info in Help tab above.','wpematico'); ?>"></span></div>
											</div>
											<?php do_action('wpematico_settings_images', $cfg); ?>
										</div>
									</div>

									<div id="imgs" class="postbox">
										<button type="button" class="handlediv button-link" aria-expanded="true">
											<span class="screen-reader-text"><?php _e('Click to toggle'); ?></span>
											<span class="toggle-indicator" aria-hidden="true"></span>
										</button>
										<h3 class="hndle"><span class="dashicons dashicons-format-video"></span> <span><?php _e('Global Settings for Videos', 'wpematico'); ?></span></h3>
										<div class="inside">
											<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['video_cache'], true); ?> name="video_cache" id="video_cache" />&nbsp;<b><label for="video_cache"><?php _e('Store videos locally.', 'wpematico'); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['video_cache']; ?>"></span>
											<div id="nolink_video" style="padding-left:20px; <?php if(!$cfg['video_cache']) echo 'display:none;'; ?>">
												<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['video_attach'], true); ?> name="video_attach" id="video_attach" /><b>&nbsp;<label for="video_attach"><?php _e('Attach Videos to posts.', 'wpematico'); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['video_attach']; ?>"></span>
												<br/>
												<input name="gralnolink_video" id="gralnolink_video" class="checkbox" value="1" type="checkbox" <?php checked($cfg['gralnolink_video'], true); ?> /><label for="gralnolink_video"><?php _e('Remove link to source videos', 'wpematico'); ?></label><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['gralnolink_video']; ?>"></span>
											</div>
											<p></p>
											<div id="custom_uploads_videos" style="<?php if(!$cfg['video_cache']) echo 'display:none;'; ?>">
												<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['customupload_videos'], true); ?> name="customupload_videos" id="customupload_videos" /><b>&nbsp;<label for="customupload_videos"><?php _e('Use custom upload.', 'wpematico'); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['customupload_videos']; ?>"></span>
												<p></p>
												<?php
												$comma		 = _x(',', 'mime delimiter');
												$ext_to_edit = (!is_string($cfg['video_allowed_ext'])) ? '' : $cfg['video_allowed_ext'];
												$ext_list	 = WPeMatico::get_videos_allowed_mimes();
												?>
												<label for="video_allowed_ext"><b><?php _e('Allowed video extensions to upload'); ?></b><br/>
													<input type="text" class="regular-text" name="video_allowed_ext" id="video_allowed_ext" value="<?php echo str_replace(',', $comma, $ext_to_edit); // textarea_escaped by esc_attr()          ?>"/>
												</label>
												<p class="description" id="new-mime-video_allowed_ext-desc"><?php _e('Separate with commas the allowed mime types for WPeMatico Uploads.', 'wpematico'); ?><br /> 
													<?php _e('WordPress video mime types.', 'wpematico'); ?> <label class="description" id="video_allowed_ext-list" title="<?php _e('Click here to restore WP defaults.', 'wpematico') ?>" onclick="jQuery('#video_allowed_ext').val(jQuery(this).text());return false;"><?php echo $ext_list; ?></label><br/>
													<?php _e('Recommended.', 'wpematico'); ?> <label class="description" id="video_allowed_ext-list" title="<?php _e('Click here to set recommended values.', 'wpematico') ?>" onclick="jQuery('#video_allowed_ext').val(jQuery(this).text());return false;"><?php echo "mp4"; ?></label>
												</p>

											</div>
											<?php do_action('wpematico_settings_videos', $cfg); ?>
										</div>
									</div>

									<div id="imgs" class="postbox">
										<button type="button" class="handlediv button-link" aria-expanded="true">
											<span class="screen-reader-text"><?php _e('Click to toggle'); ?></span>
											<span class="toggle-indicator" aria-hidden="true"></span>
										</button>
										<h3 class="hndle"><span class="dashicons dashicons-format-audio"></span> <span><?php _e('Global Settings for Audios', 'wpematico'); ?></span></h3>
										<div class="inside">
											<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['audio_cache'], true); ?> name="audio_cache" id="audio_cache" />&nbsp;<b><label for="audio_cache"><?php _e('Store audios locally.', 'wpematico'); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['audio_cache']; ?>"></span>
											<div id="nolink_audio" style="padding-left:20px; <?php if(!$cfg['audio_cache']) echo 'display:none;'; ?>">
												<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['audio_attach'], true); ?> name="audio_attach" id="audio_attach" /><b>&nbsp;<label for="audio_attach"><?php _e('Attach Audios to posts.', 'wpematico'); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['audio_attach']; ?>"></span>
												<br/>
												<input name="gralnolink_audio" id="gralnolink_audio" class="checkbox" value="1" type="checkbox" <?php checked($cfg['gralnolink_audio'], true); ?> /><label for="gralnolink_audio"><?php _e('Remove link to source audios', 'wpematico'); ?></label><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['gralnolink_audio']; ?>"></span>
											</div>
											<p></p>
											<div id="custom_uploads_audios" style="<?php if(!$cfg['audio_cache']) echo 'display:none;'; ?>">
												<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['customupload_audios'], true); ?> name="customupload_audios" id="customupload_audios" /><b>&nbsp;<label for="customupload_audios"><?php _e('Use custom upload.', 'wpematico'); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['customupload_audios']; ?>"></span>
												<p></p>
												<?php
												$comma		 = _x(',', 'mime delimiter');
												$ext_to_edit = (!is_string($cfg['audio_allowed_ext'])) ? '' : $cfg['audio_allowed_ext'];
												$ext_list	 = WPeMatico::get_audios_allowed_mimes();
												?>
												<label for="audio_allowed_ext"><b><?php _e('Allowed audio extensions to upload'); ?></b><br />
													<input type="text" class="regular-text" name="audio_allowed_ext" id="audio_allowed_ext" value="<?php echo str_replace(',', $comma, $ext_to_edit); // textarea_escaped by esc_attr()          ?>" size="80" />
												</label>
												<p class="description" id="new-mime-audio_allowed_ext-desc"><?php _e('Separate with commas the allowed mime types for WPeMatico Uploads.', 'wpematico'); ?><br /> 
													<?php _e('WordPress audio mime types.', 'wpematico'); ?> <label class="description" id="image_allowed_ext-list" title="<?php _e('Click here to restore WP defaults.', 'wpematico') ?>" onclick="jQuery('#audio_allowed_ext').val(jQuery(this).text());return false;"><?php echo $ext_list; ?></label><br/>
													<?php _e('Recommended.', 'wpematico'); ?> <label class="description" id="audio_allowed_ext-list" title="<?php _e('Click here to set recommended values.', 'wpematico') ?>" onclick="jQuery('#audio_allowed_ext').val(jQuery(this).text());return false;"><?php echo "mp3"; ?></label>
												</p>

											</div>
											<?php do_action('wpematico_settings_audios', $cfg); ?>
										</div>
									</div>

									<div id="enablefeatures" class="postbox">
										<button type="button" class="handlediv button-link" aria-expanded="true">
											<span class="screen-reader-text"><?php _e('Click to toggle'); ?></span>
											<span class="toggle-indicator" aria-hidden="true"></span>
										</button>
										<h3 class="hndle"><span><span class="dashicons dashicons-admin-settings"></span> <?php _e('Enable Features', 'wpematico'); ?></span><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['enablefeatures']; ?>"></span></h3>
										<div class="inside"> 
											<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['enablerewrite'], true); ?> name="enablerewrite" id="enablerewrite" /> <label for="enablerewrite"><?php _e('Enable "Rewrite" feature', 'wpematico'); ?></label>
											<span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['enablerewrite']; ?>"></span>
											<p></p>
											<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['enableword2cats'], true); ?> name="enableword2cats" id="enableword2cats" /> <label for="enableword2cats"><?php _e('Enable "Words to Categories" feature', 'wpematico'); ?></label>
											<span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['enableword2cats']; ?>"></span>
											<p></p>
											<input class="checkbox" type="checkbox"<?php checked($cfg['wpematico_set_canonical'], true); ?> name="wpematico_set_canonical" value="1" id="wpematico_set_canonical"/> 
											<label for="wpematico_set_canonical"><?php echo __('Use Canonical URL to Source site on post(type)', 'wpematico'); ?></label>
											<span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['wpematico_set_canonical']; ?>"></span>
											<?php if(!wpematico_is_pro_active()) : ?>

											</div>
										</div>

										<div id="PROfeatures" class="postbox">
											<button type="button" class="handlediv button-link" aria-expanded="true">
												<span class="screen-reader-text"><?php _e('Click to toggle'); ?></span>
												<span class="toggle-indicator" aria-hidden="true"></span>
											</button>
											<h3 style="float:right; background-color: yellow;"><?php _e('Availables in addons at etruel.com.', 'wpematico'); ?></h3>
											<h3 class="hndle" style="background-color: yellow;"><span><?php _e('Some Professional Features you could have.', 'wpematico'); ?></span> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['PROfeatures']; ?>"></span></h3>
											<div class="inside"> 
												<p></p>
												<?php _e('"Keyword Filtering" feature', 'wpematico'); ?> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['enablekwordf']; ?>"></span>
												<p></p>
												<?php _e('"Word count Filters" feature', 'wpematico'); ?> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['enablewcf']; ?>"></span>
												<p></p>
												<?php _e('"Custom Title" feature', 'wpematico'); ?> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['enablecustomtitle']; ?>"></span>
												<p></p>
												<?php _e('attempt to "Get Full Content" feature', 'wpematico'); ?> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['fullcontent']; ?>"></span>
												<p></p>
												<?php _e('"Author per feed" feature', 'wpematico'); ?> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['authorfeed']; ?>"></span>
												<p></p>
												<?php _e('"Import feed list" feature', 'wpematico'); ?> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['importfeeds']; ?>"></span>
												<p></p>
												<?php _e('"Auto Tags" feature.', 'wpematico'); ?> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['enabletags']; ?>"></span>
												<p></p>
												<?php _e('"Custom Fields" feature.', 'wpematico'); ?> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['enablecfields']; ?>"></span>
												<p></p>
												<?php _e('"Custom Feed Tags" feature.', 'wpematico'); ?> <span class="dashicons dashicons-warning help_tip" title="Add custom feed tags as template tags or custom field values on every post."></span>
												<p></p>
												<?php _e('"Image Filters" feature.', 'wpematico'); ?> <span class="dashicons dashicons-warning help_tip" title="You can allow or skip each image in every post depends on image dimensions."></span>
												<p></p>
												<?php _e('"Random Rewrites" feature.', 'wpematico'); ?> <span class="dashicons dashicons-warning help_tip" title="Rewrite custom words randomly as synonyms. You must complete the words separated by comma and per line in the textarea."></span>
												<p></p>
												<?php _e('"Deletes till the end of the line" feature.', 'wpematico'); ?> <span class="dashicons dashicons-warning help_tip" title="This feature allows to delete from a word or phrase until the end of the line of a sentence."></span>
												<p></p>

											<?php endif; ?>
										</div>
									</div>

									<div id="advancedfetching" class="postbox">
										<button type="button" class="handlediv button-link" aria-expanded="true">
											<span class="screen-reader-text"><?php _e('Click to toggle'); ?></span>
											<span class="toggle-indicator" aria-hidden="true"></span>
										</button>
										<h3 class="hndle"><span class="dashicons dashicons-chart-pie"></span> <span><?php _e('Advanced Fetching', 'wpematico'); ?> <?php _e('(SimplePie Settings)', 'wpematico'); ?></span></h3>
										<div class="inside">
											<p></p>

											<label><input class="checkbox" value="1" type="checkbox" <?php checked($cfg['set_stupidly_fast'], true); ?> name="set_stupidly_fast" id="set_stupidly_fast"  onclick="jQuery('#simpie').show();"  /> <?php _e('Set Simplepie "stupidly fast"', 'wpematico'); ?></label>  <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['stupidly_fast']; ?>"></span>
											<p></p>
											<div id="simpie" style="margin-left: 25px;<?php if($cfg['set_stupidly_fast']) echo 'display:none;'; ?>">
												<input name="simplepie_strip_htmltags" id="simplepie_strip_htmltags" class="checkbox" value="1" type="checkbox" <?php checked($cfg['simplepie_strip_htmltags'], true); ?> />
												<label for="simplepie_strip_htmltags"><b><?php _e('Change SimplePie HTML tags to strip', 'wpematico'); ?></b></label> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['strip_htmltags']; ?>"></span>
												<br />
												<textarea style="width:500px;" <?php disabled($cfg['simplepie_strip_htmltags'], false, true); ?> name="strip_htmltags" id="strip_htmltags" ><?php echo esc_textarea($cfg['strip_htmltags']); ?></textarea>
												<p></p>
												<input name="simplepie_strip_attributes" id="simplepie_strip_attributes" class="checkbox" value="1" type="checkbox" <?php checked($cfg['simplepie_strip_attributes'], true); ?> />
												<label for="simplepie_strip_attributes"><b><?php _e('Change SimplePie HTML attributes to strip', 'wpematico'); ?></b></label>  <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['strip_htmlattr']; ?>"></span>
												<br />
												<textarea style="width:500px;" <?php disabled($cfg['simplepie_strip_attributes'], false, true); ?> name="strip_htmlattr" id="strip_htmlattr" ><?php echo esc_textarea($cfg['strip_htmlattr']); ?></textarea>
											</div>
											<p></p>

										</div>
									</div>

									<div id="advancedfetching" class="postbox">
										<button type="button" class="handlediv button-link" aria-expanded="true">
											<span class="screen-reader-text"><?php _e('Click to toggle'); ?></span>
											<span class="toggle-indicator" aria-hidden="true"></span>
										</button>
										<h3 class="hndle"><span class="dashicons dashicons-admin-tools"></span> <span><?php _e('Advanced Fetching', 'wpematico'); ?></span></h3>
										<div class="inside">
											<p></p>
											<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['woutfilter'], true); ?> name="woutfilter" id="woutfilter" /> <?php echo '<b><i>' . __('Allow option on campaign to skip the content filters', 'wpematico') . '</b></i>'; ?><br />
											<div id="hlpspl" style="padding-left:20px;">
												<?php _e('NOTE: It is extremely dangerous to allow unfiltered content.', 'wpematico'); ?><br />
											</div> 
											<p></p>
											<p><b><?php _e('Timeout running campaign:', 'wpematico'); ?></b> <input name="campaign_timeout" type="number" min="0" value="<?php echo esc_attr($cfg['campaign_timeout']); ?>" class="small-text" /> <?php _e('Seconds.', 'wpematico'); ?>
												<span id="hlpspl" style="padding-left:20px;display: inline-block;">
													<?php _e('When a campaign running is interrupted, cannot be executed again until click "Clear Campaign".  This option clear campaign after this timeout then can run again on next scheduled cron. A value of "0" ignore this, means that remain until user make click.  Recommended 300 Seconds.', 'wpematico'); ?>
												</span></p>
											<p></p>
											<label for="throttle"><b><?php _e('Add a throttle/delay in seconds after every post.', 'wpematico'); ?></b></label> <input name="throttle" id="throttle" class="small-text" min="0" type="number" value="<?php echo esc_attr($cfg['throttle']); ?>" /> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['throttle']; ?>"></span>

											<p></p>
											<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['allowduplicates'], true); ?> name="allowduplicates" id="allowduplicates" /><b>&nbsp;<?php echo '<label for="allowduplicates">' . __('Deactivate duplicate controls.', 'wpematico') . '</label>'; ?></b>  <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['allowduplicates']; ?>"></span>
											<br>
											<div id="enadup" style="padding-left:20px; <?php if(!$cfg['allowduplicates']) echo 'display:none;'; ?>">
												<small><?php _e('NOTE: If disable both controls, all items will be fetched again and again... and again, ad infinitum.  If you want allow duplicated titles, just activate "Allow duplicated titles".', 'wpematico'); ?></small><br />
												<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['allowduptitle'], true); ?> name="allowduptitle" id="allowduptitle" /><b>&nbsp;<?php echo '<label for="allowduptitle">' . __('Allow duplicates titles.', 'wpematico') . '</label>'; ?></b><br />
												<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['allowduphash'], true); ?> name="allowduphash" id="allowduphash" /><b>&nbsp;<?php echo '<label for="allowduphash">' . __('Allow duplicates hashes. (Not Recommended)', 'wpematico') . '</label>'; ?></b>
											</div>
											<div id="div_add_extra_duplicate_filter_meta_source" <?php if($cfg['disableccf'] || $cfg['allowduptitle']) echo 'style="display:none;"' ?>>
												<input name="add_extra_duplicate_filter_meta_source" id="add_extra_duplicate_filter_meta_source" class="checkbox" value="1" type="checkbox" <?php checked($cfg['add_extra_duplicate_filter_meta_source'], true); ?> />
												<label for="add_extra_duplicate_filter_meta_source"><b><?php _e('Add an extra duplicate filter by source permalink in meta field value.', 'wpematico'); ?></b></label>  <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['add_extra_duplicate_filter_meta_source']; ?>"></span>
												<br /> 
											</div>
											<p></p>
											<input name="jumpduplicates" id="jumpduplicates" class="checkbox" value="1" type="checkbox" <?php checked($cfg['jumpduplicates'], true); ?> />
											<label for="jumpduplicates"><b><?php _e('Continue Fetching if found duplicated items.', 'wpematico'); ?></b></label>  <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['jumpduplicates']; ?>"></span>
											<p></p>
											<input name="disableccf" id="disableccf" class="checkbox" value="1" type="checkbox" <?php checked($cfg['disableccf'], true); ?> />
											<label for="disableccf"><b><?php _e('Disable plugin custom fields.', 'wpematico'); ?></b></label>  <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['disableccf']; ?>"></span>
											<br />

										</div>
									</div>

									<div id="disablewpcron" class="postbox">
										<button type="button" class="handlediv button-link" aria-expanded="true">
											<span class="screen-reader-text"><?php _e('Click to toggle'); ?></span>
											<span class="toggle-indicator" aria-hidden="true"></span>
										</button>
										<h3 class="hndle"><span class="dashicons dashicons-clock"></span> <span><?php _e('Cron and Scheduler Settings', 'wpematico'); ?></span></h3>
										<div class="inside">
											<?php // More details on https://wp-mix.com/wordpress-cron-not-working/   	   ?>
											<label><input class="checkbox" id="enable_alternate_wp_cron" type="checkbox"<?php checked($cfg['enable_alternate_wp_cron'], true); ?> name="enable_alternate_wp_cron" value="1"/> 
												<strong><?php _e('Use ALTERNATE_WP_CRON', 'wpematico'); ?></strong></label>  <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['enable_alternate_wp_cron']; ?>"></span>
											<p></p> 
											<label><input class="checkbox" id="dontruncron" type="checkbox"<?php checked($cfg['dontruncron'], true); ?> name="dontruncron" value="1"/> 
												<strong><?php _e('Disable WPeMatico schedulings', 'wpematico'); ?></strong></label> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['dontruncron']; ?>"></span>
											<br />
											<?php
											$croncode = ($cfg['set_cron_code']) ? '?code=' . $cfg['cron_code'] : '';

											$url_cron = admin_url('admin-post.php?action=wpematico_cron');
											if($cfg['set_cron_code']) {
												$url_cron = add_query_arg(array('code' => $cfg['cron_code']), $url_cron);
											}
											?>
											<div id="hlpcron" style="padding-left:20px;">
												<?php _e('You must set up a cron job that calls:', 'wpematico'); ?><br />
												<?php
												if(!has_action('wpematico_cronjob')) {
													echo '<br />';
													_e('URL:', 'wpematico');
												}else {
													do_action('wpematico_cronjob');
												}
												?>
												<br /><span class="coderr b"><i><?php echo $url_cron; ?></i></span>
												<br />
												<label><input class="checkbox" id="set_cron_code" type="checkbox"<?php checked($cfg['set_cron_code'], true); ?> name="set_cron_code" value="1"/> 
													<strong><?php _e('Set a password to access the external CRON', 'wpematico'); ?></strong></label>  <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['set_cron_code']; ?>"></span>
												<br /> 
												<label style="padding-left:20px;">
													<?php _e('Set a password to use the external CRON', 'wpematico'); ?>: 
													<input type="hidden" id="autocode" value="<?php echo substr(md5(time()), 0, 8); ?>"/> 
													<a style="font-size: 2.2em;" title="<?php _e('Paste a generated a ramdon string.'); ?>" class='dashicons dashicons-migrate' onclick="Javascript: jQuery('#cron_code').val(jQuery('#autocode').val());" > &nbsp;&nbsp;</a> &nbsp;
													<input name="cron_code" title="<?php _e('See text.'); ?>" id="cron_code" type="text" value="<?php echo esc_attr($cfg['cron_code']); ?>" class="standard-text" /> 
													<?php /* <a class='dashicons dashicons-visibility' onclick="Javascript: jQuery('#cron_code').prop('type','text');" ></a> */ ?>
												</label>  <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['cron_code']; ?>"></span>
											</div>
											<br /> 

											<label><input class="checkbox" id="disablewpcron" type="checkbox"<?php checked($cfg['disablewpcron'], true); ?> name="disablewpcron" value="1"/> 
												<strong><?php _e('Disable all WP_Cron', 'wpematico'); ?></strong></label>  <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['disablewpcron']; ?>"></span>
											<div id="hlpcron2" style="padding-left:20px;">
												<?php _e('To run the wordpress cron with external cron you can set up a cron job that calls:', 'wpematico'); ?><br />
												<span class="coderr b"><i> php -q <?php echo ABSPATH . 'wp-cron.php'; ?></i></span><br /> 
												<?php _e('or URL:', 'wpematico'); ?> &nbsp;&nbsp;&nbsp;<span class="coderr b"><i><?php echo trailingslashit(get_option('siteurl')) . 'wp-cron.php'; ?></i></span>
												<br /> 
												<div class="mphlp" style="margin-top: 10px;">
													<?php echo __('This set ', 'wpematico') . '<code>DISABLE_WP_CRON</code>' . __('to ', 'wpematico') . '<code>true</code>, ' . __('then the ', 'wpematico') . '<a href="https://core.trac.wordpress.org/browser/tags/4.2.3/src/wp-includes/cron.php#L314" target="_blank">' . __('current cron process should be killed', 'wpematico') . '</a>.'; ?>
													<br /> 
													<?php _e('You can find more info about WP Cron and also few steps to configure external crons:', 'wpematico'); ?>
													<a href="http://code.tutsplus.com/articles/insights-into-wp-cron-an-introduction-to-scheduling-tasks-in-wordpress--wp-23119" target="_blank"><?php _e('here', 'wpematico'); ?></a>.
												</div>
											</div><br /> 

											<label><input class="checkbox" id="logexternalcron" type="checkbox"<?php checked($cfg['logexternalcron'], true); ?> name="logexternalcron" value="1"/> 
												<strong><?php _e('Log file for external Cron', 'wpematico'); ?></strong></label> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['logexternalcron']; ?>"></span>
											<br /> 
										</div>
									</div>				

									<div id="emptytrashdiv" class="postbox">
										<button type="button" class="handlediv button-link" aria-expanded="true">
											<span class="screen-reader-text"><?php _e('Click to toggle'); ?></span>
											<span class="toggle-indicator" aria-hidden="true"></span>
										</button>
										<h3 class="hndle"><span class="dashicons dashicons-hammer"></span> <span><?php _e('WordPress Backend Tools', 'wpematico'); ?></span></h3>

										<div class="inside">
											<p>
												<label><input class="checkbox" id="campaign_in_postslist" type="checkbox"<?php checked($cfg['campaign_in_postslist'], true); ?> name="campaign_in_postslist" value="1"/> 
													<strong><?php _e('Wpematico Campaign Column in Posts(-types) lists.', 'wpematico'); ?></strong></label>
												<span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['campaign_in_postslist']; ?>"></span><br />
												<span id="column_campaign_pos_field" class="insidesec" style="padding-left:20px; <?php if(!$cfg['campaign_in_postslist']) echo 'display:none;'; ?>">
													<label>
														<strong><?php _e('Column position in Posts(-types) lists.', 'wpematico'); ?></strong>
														<input name="column_campaign_pos" id="column_campaign_pos" class="small-text" min="0" type="number" value="<?php echo esc_attr($cfg['column_campaign_pos']); ?>" /> 
													</label>
													<span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['column_campaign_pos']; ?>"></span>
												</span>
											</p>
											<p>
												<label><input class="checkbox" id="disable_metaboxes_wpematico_posts" type="checkbox"<?php checked($cfg['disable_metaboxes_wpematico_posts'], true); ?> name="disable_metaboxes_wpematico_posts" value="1"/> 
													<strong><?php _e('Disable metabox Wpematico Campaign Info in post editing', 'wpematico'); ?></strong></label>
												<span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['disable_metaboxes_wpematico_posts']; ?>"></span>
											</p>
											<p></p>
											<div class="insidesec" style="border-right: 1px lightgrey solid; margin-right: 5px;padding-right: 7px; ">
												<label><input class="checkbox" id="emptytrashbutton" type="checkbox"<?php checked($cfg['emptytrashbutton'], true); ?> name="emptytrashbutton" value="1"/> 
													<?php _e('Shows Button to empty trash on lists.', 'wpematico'); ?></label>  <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['emptytrashbutton']; ?>"></span>
												<br />
												<div id="hlptrash" style="padding-left:20px; <?php if(!$cfg['emptytrashbutton']) echo 'display:none;'; ?>">
													<?php _e('Select (custom) post types you want.', 'wpematico'); ?>
													<div class="hlptrash-content">
													<?php
													// publicos y privados para que pueda mostrar el boton en todos
													$args		 = array('public' => false);
													$args		 = array();
													$output		 = 'names'; // names or objects
													$output		 = 'objects'; // names or objects
													$cpostypes	 = $cfg['cpt_trashbutton'];
													//unset($cpostypes['attachment']);
													$post_types	 = get_post_types($args, $output);
													foreach($post_types as $post_type_obj) {
														$post_type				 = $post_type_obj->name;
														$post_label				 = $post_type_obj->labels->name;
														if($post_type == 'revision')
															continue;  // ignore 'attachment'
														if($post_type == 'nav_menu_item')
															continue;  // ignore 'attachment'
														echo '<div><input type="checkbox" class="checkbox" name="cpt_trashbutton[' . $post_type . ']" value="1" ';
														if(!isset($cpostypes[$post_type]))
															$cpostypes[$post_type]	 = false;
														checked($cpostypes[$post_type], true);
														echo ' /> ' . __($post_label) . ' (' . __($post_type) . ')</div>';
													}
													?>
												</div></div><br /> 
											</div>
											<div id="enabledashboard" class="insidesec">

												<label><input class="checkbox" value="1" type="checkbox" <?php checked($cfg['disabledashboard'], true); ?> name="disabledashboard" id="disabledashboard" /> <?php _e('Disable "WP Dashboard Widget"', 'wpematico'); ?></label><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['disabledashboard']; ?>"></span>
												<div>
													<label id="roleslabel" <?php if($cfg['disabledashboard']) echo 'style="display:none;"'; ?>><?php _e('User roles to show Dashboard widget:', 'wpematico'); ?></label>
													<div id="roles" <?php if($cfg['disabledashboard']) echo 'style="display:none;"'; ?>>
														<?php
														global $wp_roles;
														if(!isset($cfg['roles_widget']))
															$cfg['roles_widget'] = array("administrator" => "administrator");
														$role_select		 = '<input type="hidden" name="roles_widget[administrator]" value="administrator" />';
														foreach($wp_roles->role_names as $role => $name) {
															$name = _x($name, 'wpematico');
															if($role != 'administrator') {
																if(array_search($role, $cfg['roles_widget'])) {
																	$checked = 'checked="checked"';
																}else {
																	$checked = '';
																}
																$role_select .= "<label style='margin:0 5px;'><input style='margin:0 5px;' $checked type='checkbox' name='roles_widget[$role]' value='$role' />$name</label>";
															}
														}
														echo $role_select;
														?>
													</div>
												</div>
												<br/> 
											</div>
										</div>
									</div>				
								</div>		<!-- #normal-sortables -->
							</div>		<!--  postbox-container-2 -->		

							<div>
								<p>
									<?php submit_button(__('Save settings', 'wpematico'), 'primary', 'wpematico-save-settings2', false); ?>
								</p>
							</div>
						</div> <!-- #post-body -->
					</div> <!-- #poststuff -->
				</form>		
			</div><!-- .wrap -->
			<?php
		}

		public static function settings_save() {
			if('POST' === $_SERVER['REQUEST_METHOD']) {
				if(!is_user_logged_in())
					wp_die("<h3>Cheatin' uh?</h3>", "Closed today.");
				check_admin_referer('wpematico-settings');
				$errlev = error_reporting();
				error_reporting(E_ALL & ~E_NOTICE);  // deactive notices by _POST vars

				/**
				 * wpematico_check_options Filter to sanitize and strip all options fields 
				 */

				// var_dump($_POST);
				// die();
				$cfg				 = apply_filters('wpematico_check_options', $_POST);
				if(!wpematico_is_pro_active())
					$cfg['nonstatic']	 = false;
				else
					$cfg['nonstatic']	 = true;
				wp_get_current_user();

				wp_clear_scheduled_hook('wpematico_cron');
				if(isset($cfg['disablewpcron']) && $cfg['disablewpcron']) {
					define('DISABLE_WP_CRON', true);
				}
				if(isset($cfg['enable_alternate_wp_cron']) && $cfg['enable_alternate_wp_cron']) {
					if(!defined('ALTERNATE_WP_CRON')) {
						define('ALTERNATE_WP_CRON', true);
					}
				}

				if(!(isset($cfg['dontruncron']) && $cfg['dontruncron'] )) {
					wp_schedule_event(time(), 'wpematico_int', 'wpematico_cron');
				}

				if(update_option(WPeMatico::OPTION_KEY, $cfg)) {
					WPeMatico::add_wp_notice(array('text' => __('Settings saved.', 'wpematico'), 'below-h2' => false));
				}
				error_reporting($errlev);
				wp_redirect(admin_url('edit.php?post_type=wpematico&page=wpematico_settings&tab=settings'));
			}
		}

		public static function settings_help() {
			if(( isset($_GET['page']) && $_GET['page'] == 'wpematico_settings' ) &&
				( isset($_GET['post_type']) && $_GET['post_type'] == 'wpematico' ) &&
				( (isset($_GET['tab']) && $_GET['tab'] == 'settings' ) || !isset($_GET['tab']) )
			) {
				$screen = WP_Screen::get('wpematico_page_wpematico_settings ');
				foreach(wpematico_helpsettings() as $key => $section) {
					$tabcontent = '';
					foreach($section as $section_key => $sdata) {
						$helptip[$section_key]	 = htmlentities($sdata['tip']);
						$tabcontent				 .= '<p><strong>' . $sdata['title'] . '</strong><br />' .
							$sdata['tip'] . '</p>';
						$tabcontent				 .= (isset($sdata['plustip'])) ? '<p style="margin-top: 2px;margin-left: 7px;">' . $sdata['plustip'] . '</p>' : '';
					}
					$screen->add_help_tab(array(
						'id'		 => $key,
						'title'		 => $key,
						'content'	 => $tabcontent,
					));
				}
			}
		}

	}

	endif;

WPeMatico_Settings::hooks();