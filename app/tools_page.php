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

		public static function hooks(){
			add_action('wpematico_tools_tab_tools', [__CLASS__, 'tools_form']);
			add_action('wpematico_tools_tab_debug_log', [__CLASS__, 'debug_log_file']);
			add_action('admin_init', [__CLASS__, 'tools_help']);
			add_action('wp_ajax_download_wpematico_log', [__CLASS__, 'download_debug_log']); 
		}

		public static function debug_log_file() {
			$danger = WPeMatico::get_danger_options();

			if ( empty( $danger['wpematico_debug_log_file'] ) ) {
				printf(
					'<div class="notice notice-warning"><p>%s</p></div>',
					esc_html__( 'Debug mode is not enabled. Please enable it in the WPeMatico settings to view the debug log.', 'wpematico' )
				);
				return;
			}

			$log_file = wpematico_get_log_file_path();

			global $wp_filesystem;
			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}

			$log_exists  = $wp_filesystem->exists( $log_file );
			$log_content = $log_exists ? $wp_filesystem->get_contents( $log_file ) : '';

			if (
				! empty( $_POST['clear_log'] )
				&& check_admin_referer( 'wpematico_debug_log_clear', 'wpematico_debug_log_nonce' )
				&& $log_exists
			) {
				$wp_filesystem->put_contents( $log_file, '' );
				$log_exists  = false;
				$log_content = '';
			}

			echo '<div class="wrap">';
			echo '<div class="notice notice-info inline"><p>';
			echo esc_html__( 'When debug mode is enabled, specific information will be shown here.', 'wpematico' ) . ' ';
			printf(
				'(<a href="%s" target="_blank">%s</a>)',
				esc_url( 'https://etruel.com/question/how-to-use-wpematico-log/' ),
				esc_html__( 'Learn how to use the wpematico_log() function', 'wpematico' )
			);
			echo '</p></div>';

			echo '<h2>' . esc_html__( 'WPeMatico code Logs', 'wpematico' ) . '</h2>';

			echo '<form method="post">';
			wp_nonce_field( 'wpematico_debug_log_clear', 'wpematico_debug_log_nonce' );

			echo '<textarea name="wpematico_debug_log_content" readonly rows="20" style="width:100%; font-family: monospace;">' . esc_textarea( $log_content ) . '</textarea><br><br>';

				submit_button( __( 'Clear Log', 'wpematico' ), 'delete', 'clear_log', false );
			if ( $log_content ) {
				echo '&nbsp;';
				printf(
					'<a href="%s" class="button button-primary">%s</a>&nbsp;',
					esc_url( admin_url( 'admin-ajax.php?action=download_wpematico_log' ) ),
					esc_html__( 'Download Log', 'wpematico' )
				);
				submit_button( __( 'Copy to Clipboard', 'wpematico' ), 'secondary', 'copy_debug_log', false, array(
					'onclick' => "this.form['wpematico_debug_log_content'].focus(); this.form['wpematico_debug_log_content'].select(); document.execCommand('copy'); return false;"
				) );
			} else {
				echo '<p><em>' . esc_html__( 'No log file found yet.', 'wpematico' ) . '</em></p>';
			}

			echo '</form>';
			echo '</div>';

//			$danger = WPeMatico::get_danger_options();
//
//			if (empty($danger['wpematico_debug_log_file'])) {
//				echo '<div class="notice notice-warning"><p>' . esc_html__('Debug mode is not enabled. Please enable it in the WPeMatico settings to view the debug log.', 'wpematico') . '</p></div>';
//				return;
//			}
//			$log_file = wpematico_get_log_file_path(); 
//			$log_exists = file_exists($log_file);
//
//			if (!empty($_POST['clear_log']) && $log_exists) {
//				@file_put_contents($log_file, '');
//				$log_exists = false;
//			}
//
//			$log_content = $log_exists ? file_get_contents($log_file) : '';
//
//			echo '<div style="background:rgb(197, 197, 197); padding: 20px; border-radius: 4px;">';
//			echo '<p>' . esc_html__('When debug mode is enabled, specific information will be shown here.', 'wpematico') . 
//				' (<a href="https://etruel.com/question/how-to-use-wpematico-log/" target="_blank">' . 
//				esc_html__('Learn how to use the wpematico_log() function', 'wpematico') . 
//				'</a>)</p>';
//			echo '<h2>' . esc_html__('Code Logs', 'wpematico') . '</h2>';
//			
//
//				echo '<form method="post" id="wpematico-debug-log">';
//				echo '<textarea name="wpematico_debug_log_content" readonly rows="20" style="width:100%; font-family: monospace;">' . esc_textarea($log_content) . '</textarea><br><br>';
//				echo '<button type="submit" name="clear_log" class="button">' . esc_html__('Clear Log', 'wpematico') . '</button> ';
//				echo '<input type="hidden" name="clear_log" value="1" />';
//				if ($log_content) {
//					echo '<a href="' . esc_url(admin_url('admin-ajax.php?action=download_wpematico_log')) . '" class="button button-primary">';
//					echo esc_html__('Download Log', 'wpematico') . '</a> ';
//					submit_button( __( 'Copy to Clipboard', 'wpematico' ), 'secondary', 'wpematico-copy-debug-log', false, array( 'onclick' => "this.form['wpematico_debug_log_content'].focus();this.form['wpematico_debug_log_content'].select();document.execCommand('copy');return false;" ) );
//				} else {
//					echo '<p><em>' . esc_html__('No log file found yet.', 'wpematico') . '</em></p>';
//				}
//				echo '</form>';
//			echo '</div>';
		}

		public static function download_debug_log(){
			$log_file = wpematico_get_log_file_path();

			if (!file_exists($log_file)) {
				wp_die(esc_html__('Log file not found.', 'wpematico'), '', ['response' => 404]);
			}

			header('Content-Type: text/plain');
			header('Content-Disposition: attachment; filename="wpematico_debug.log"');
			readfile($log_file);
			exit;
		}


		/**
		 * 		Called by function admin_menu() on wpematico_class
		 */
		public static function styles(){
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

		public static function wpematico_tools_head(){
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

		public static function tools_form(){
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

						//$button_export = '<a href="' . $linkExport . '" class="button" title="' . esc_attr(__("Export and download settings", 'wpematico')) . '">' . esc_html__('Export Settings', 'wpematico') . '</a>';
						?>
						<div class="postbox">
							<h3 class="hndle ui-sortable-handle"><span class="dashicons dashicons-arrow-up-alt"></span> <span><?php esc_html_e('Export Settings', 'wpematico') ?></span></h3>
							<div class="inside">
								<p><?php esc_html_e('Export the WPeMatico settings for this site as a .json file. This allows you to easily import the configuration into another site.', 'wpematico')  ?></p>
								<p>
									<?php //echo $button_export; ?>
									<?php echo '<a href="' . esc_attr($linkExport) . '" class="button" title="' . esc_attr__("Export and download settings", 'wpematico') . '">' . esc_html__('Export Settings', 'wpematico') . '</a>'; ?>
								</p>
							</div><!-- .inside -->
						</div>
						<form action="<?php echo esc_attr($linkImport); ?>" id="importsettings" method='post' ENCTYPE='multipart/form-data'>
							<?php wp_nonce_field('import-settings', 'wpemimport_nonce'); ?>
							<div class="postbox">
								<h3 class="hndle ui-sortable-handle"><span class="dashicons dashicons-arrow-down-alt"></span> <span><?php esc_html_e('Import Settings', 'wpematico') ?></span></h3>
								<div class="inside">
									<p><?php esc_html_e('Import the WPeMatico settings for this site.', 'wpematico')  ?></p>
									<p>
										<input type="hidden" name="wpematico-action" value="import_settings" />
										<input style="display:none;" type="file" class="button" name='txtsettings' id='txtsettings'>
										<a id="importcpg" class="button" href="Javascript:void(0);" title="<?php esc_attr_e("Upload and import a settings", 'wpematico') ?>"><?php esc_html_e('Import settings', 'wpematico') ?></a>
										<script>
											(function($) {
												$('#importcpg').on('click',function() {
													$('#txtsettings').trigger('click');
												});
												var message = "<?php esc_attr_e('The import will overwrite the current configuration of WPeMatico (and its addons), do you agree?', 'wpematico'); ?>"; 
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

		public static function tools_help(){
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
