<?php
// don't load directly 
if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

/**
 * WPeMatico Pro Extra Settings Class 
 * This class is used to add the Professional Extra Settings 
 * @since 2.2
 */
if (!class_exists('WPeMatico_Tools')) :

	class WPeMatico_Tools
	{

		public static function hooks()
		{
			add_action('wpematico_tools_tab_tools', array(__CLASS__, 'tools_form'));
			add_action('admin_init', array(__CLASS__, 'tools_help'));
		}


		/**
		 * 		Called by function admin_menu() on wpematico_class
		 */
		public static function styles()
		{
			global $cfg;
			wp_enqueue_style('WPematStylesheet');
			wp_enqueue_script('WPemattiptip');
			add_action('admin_head', array(__CLASS__, 'wpematico_tools_head'));
			wp_enqueue_script('postbox');
			// Enqueue jQuery UI and autocomplete
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-autocomplete');
			wp_enqueue_script('wpematico_settings_page', WPeMatico::$uri . 'app/js/tools_page.js', array('jquery', 'postbox'), WPEMATICO_VERSION, true);
			// wp_localize_script('wpematico_tools_page', 'ajax_object', array(
			// 	'nonce'    => wp_create_nonce('wpematico-tools-page-nonce')
			// ));
			// //			$allowedmimes = array_diff(explode(',', WPeMatico::get_images_allowed_mimes()), explode(',', $cfg['images_allowed_ext']));
			// $wpematico_object = array(
			// 	'text_invalid_email' => __('Invalid email.', 'wpematico'),
			// 	//				'current_img_mimes'	 => $allowedmimes,
			// );
			// wp_localize_script('wpematico_tools_page', 'wpematico_object', $wpematico_object);

			// /* Add screen option: user can choose between 1 or 2 columns (default 2) */
			// //add_screen_option('layout_columns', array('max' => 2, 'default' => 2) );
		}

		public static function wpematico_tools_head()
		{
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

				.postbox .hndle {
					border-bottom: 1px solid #ccd0d4;
				}

				.postbox .handlediv {
					float: right;
					text-align: center;
				}
			</style>

			<?php
		}

		public static function tools_form()
		{
			global $cfg, $current_screen, $helptip;

			if (isset($_GET['page']) && $_GET['page'] == 'wpematico_tools') :
				if (!(isset($_GET['section']) && $_GET['section'] == 'feed_viewer')) :
			?>

					<div class="wpe_wrap">
						<?php
						$nonce = wp_create_nonce('wpematico-tools');
						$action_export = "wpematico_export_settings";
						$action = '?action=' . $action_export . '&_wpnonce=' . $nonce;
						$linkExport = admin_url("admin.php" . $action);

						$action_import = "wpematico_import_settings";
						$action = '?action=' . $action_import . '&_wpnonce=' . $nonce;
						$linkImport = admin_url("admin.php" . $action);

						$button_export = '<a href="' . $linkExport . '" class="button" title="' . esc_attr(__("Export and download settings", 'wpematico')) . '">' . __('Export Settings', 'wpematico') . '</a>';
						?>
						<div class="postbox">
							<h3 class="hndle ui-sortable-handle"><span class="dashicons dashicons-arrow-up-alt"></span> <span><?php echo __('Export Settings', 'wpematico') ?></span></h3>
							<div class="inside">
								<p><?php echo __('Export the WPeMatico settings for this site as a .json file. This allows you to easily import the configuration into another site.', 'wpematico')  ?></p>
								<p>
									<?php echo $button_export; ?>
								</p>
							</div><!-- .inside -->
						</div>
						<form action="<?php echo $linkImport; ?>" id="importsettings" method='post' ENCTYPE='multipart/form-data'>
							<?php wp_nonce_field('import-settings', 'wpemimport_nonce'); ?>
							<div class="postbox">
								<h3 class="hndle ui-sortable-handle"><span class="dashicons dashicons-arrow-down-alt"></span> <span><?php echo __('Import Settings', 'wpematico') ?></span></h3>
								<div class="inside">
									<p><?php echo __('Import the WPeMatico settings for this site.', 'wpematico')  ?></p>
									<p>
										<input type="hidden" name="wpematico-action" value="import_settings" />
										<input style="display:none;" type="file" class="button" name='txtsettings' id='txtsettings'>
										<a id="importcpg" class="button" href="Javascript:void(0);" title="<?php echo esc_attr(__("Upload and import a settings", 'wpematico')) ?>"><?php echo __('Import settings', 'wpematico') ?></a>
										<script>
											(function($) {
												$('#importcpg').on('click',function() {
													$('#txtsettings').trigger('click');
												});
												var message = "<?php echo __('The import will overwrite the current configuration of WPeMatico (and its addons), do you agree?', 'wpematico'); ?>"; 
												$('#txtsettings').on('change',function() {
													if (confirm(message)) {
														$('#importsettings').trigger('submit');
													}else{
														$('#txtsettings').val('');
													}
												});
											})(jQuery);
										</script>
									</p>
								</div><!-- .inside -->
							</div>

						</form>

						<?php

						?>
					</div><!-- .wrap -->
<?php
				endif;
			endif;
		}

		public static function tools_help()
		{
			if ((isset($_GET['page']) && $_GET['page'] == 'wpematico_tools') &&
				(isset($_GET['post_type']) && $_GET['post_type'] == 'wpematico') &&
				((isset($_GET['tab']) && $_GET['tab'] == 'tools') || !isset($_GET['tab']))
			) {
				$screen = WP_Screen::get('wpematico_page_wpematico_tools ');
				foreach (wpematico_helptools() as $key => $section) {
					$tabcontent = '';
					foreach ($section as $section_key => $sdata) {
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

WPeMatico_Tools::hooks();
