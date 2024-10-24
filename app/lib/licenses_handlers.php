<?php
/**
 * File to manage *Addons* licenses to allow update them automatically. 
 * Unifies all installed extensions licenses in this file to use the EDD_SL_Plugin_Updater library.
 */

// Exit if accessed directly
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
class wpematico_licenses_handlers {
	function __construct() {
		add_action('admin_init', array(__CLASS__, 'extension_updater'), 0 );
		add_action('wpempro_licenses_forms', array(__CLASS__, 'license_page') );
		add_action('admin_print_scripts', array(__CLASS__, 'scripts'));
		add_action('admin_print_styles', array(__CLASS__, 'styles'));
		
		add_action('wp_ajax_wpematico_check_license', array(__CLASS__, 'ajax_check_license'));
		add_action('wp_ajax_wpematico_status_license', array(__CLASS__, 'ajax_change_status_license'));
		
		add_action( 'admin_post_wpematico_save_licenses', array(__CLASS__, 'save_licenses'));

	}
	public static function extension_updater() {
		$plugins_args = array();
		$plugins_args = apply_filters('wpematico_plugins_updater_args', $plugins_args);
		
		if(!class_exists( 'EDD_SL_Plugin_Updater') && !empty($plugins_args)) {
			if(file_exists(WPEMATICO_PLUGIN_DIR . 'app/lib/EDD_SL_Plugin_Updater.php')) {
				require_once(WPEMATICO_PLUGIN_DIR . 'app/lib/EDD_SL_Plugin_Updater.php');
			} 
		}
		
		
		foreach ($plugins_args as $plugin_name => $args) {
			$license_key = self::get_key($plugin_name);
			$edd_updater = new EDD_SL_Plugin_Updater($args['api_url'], $args['plugin_file'], array(
					'version' 	=> $args['api_data']['version'], 
					'license' 	=> $license_key, 		
					'item_name' => $args['api_data']['item_name'], 	
					'author' 	=> $args['api_data']['author'],
					'item_id' 	=> (empty($args['api_data']['item_id']) ? false : $args['api_data']['item_id']),
					'beta' 		=> (empty($args['api_data']['beta']) ? false : $args['api_data']['beta']),
				)
			);
			
			if( ! is_multisite() ) {
				//$current = get_site_transient( 'update_plugins' );
				add_action( 'after_plugin_row_' . plugin_basename($args['plugin_file']), 'wp_plugin_update_row', 10, 2 );
			}
			
		}
	}
	public static function get_key($plugin_name) {
		$keys = get_option('wpematico_license_keys');
		if ($keys === false) {
			$keys = array();
		}
		if (empty($keys[$plugin_name])) {
			return false;
		}
		return $keys[$plugin_name];
	}
	public static function get_license_status($plugin_name) {
		$keys = get_option('wpematico_license_status');
		if ($keys === false) {
			$keys = array();
		}
		if (empty($keys[$plugin_name])) {
			return false;
		}
		return $keys[$plugin_name];
	}
	public static function set_license_status($plugin_name, $status) {
		$keys = get_option('wpematico_license_status');
		if ($keys === false) {
			$keys = array();
		}
		$keys[$plugin_name] = $status;
		update_option( 'wpematico_license_status', $keys);
	}
	public static function change_status_license($plugin_name, $action) {
		$plugins_args = array();
		$plugins_args = apply_filters('wpematico_plugins_updater_args', $plugins_args);
		if (empty($plugins_args[$plugin_name])) {
			return false;
		}	
		$license = self::get_key($plugin_name);
		
		$api_params = array(
			'edd_action'=> $action,
			'license' 	=> $license,
			'item_name' => urlencode($plugins_args[$plugin_name]['api_data']['item_name']),
			'url'       => home_url()
		);

			
		$response = wp_remote_post( esc_url_raw($plugins_args[$plugin_name]['api_url']), array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
		if (is_wp_error($response)) {
			return false;
		}
				
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		self::set_license_status($plugin_name, $license_data->license);
		return $license_data;
	}
	public static function ajax_change_status_license() {
		
		$nonce = !empty($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
		
		if (!wp_verify_nonce($nonce, 'wpe-nonce-handler-license')) {
		   wp_die('Security check'); 
		}
		
		
		if (!empty($_POST['plugin_name']) && !empty($_POST['status'])) {
			
			$plugin_name	= sanitize_text_field($_POST['plugin_name']);
			$status 		= sanitize_text_field($_POST['status']);
			
			$action_return = self::change_status_license($plugin_name, $status);
			echo json_encode($action_return);
			wp_die();
			
		}
		
	}
	public static function ajax_check_license() {
		$nonce = !empty($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
		
		if (!wp_verify_nonce($nonce, 'wpe-nonce-handler-license')) {
		   wp_die('Security check'); 
		}

		
		$plugin_name = sanitize_text_field($_POST['plugin_name']);
		$plugins_args = array();
		$plugins_args = apply_filters('wpematico_plugins_updater_args', $plugins_args);
		if (empty($plugins_args[$plugin_name])) {
			wp_die('error');
		}
		$license = sanitize_text_field($_POST['license']);
		$args = array(
			'license' 	=> $license,
			'item_name' => urlencode($plugins_args[$plugin_name]['api_data']['item_name']),
			'url'       => home_url(),
			'version' 	=> $plugins_args[$plugin_name]['api_data']['version'],
			'author' 	=> 'Esteban Truelsegaard'	
		);
		$api_url = $plugins_args[$plugin_name]['api_url'];
		$lisense_object = self::check_license($api_url, $args);
		echo json_encode($lisense_object);
		wp_die();
	}
	public static function check_license($api_url, $args) {
		$args['edd_action'] = 'check_license';
		$api_params = $args;
		$response = wp_remote_post( esc_url_raw($api_url), array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
		if (is_wp_error($response)) {
			return false;
		}
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		return $license_data;
		
	}
	public static function styles() {
		$screen = get_current_screen();
		if (!is_null($screen)) {
			if ($screen->id == 'wpematico_page_wpematico_settings') {
				wp_enqueue_style('wpematico-settings-licenses', WPEMATICO_PLUGIN_URL . 'app/css/licenses_handlers.css');
			}
		}
	}
	public static function scripts() {
		$screen = get_current_screen();
		if ($screen->id == 'wpematico_page_wpematico_settings') {
			wp_enqueue_script( 'wpematico-jquery-settings-licenses', WPEMATICO_PLUGIN_URL. 'app/js/licenses_handlers.js', array( 'jquery' ), WPEMATICO_VERSION, true );
			wp_localize_script('wpematico-jquery-settings-licenses', 'wpematico_license_object',
				array('ajax_url' => admin_url( 'admin-ajax.php' ),
					'txt_check_license' 	=> __('Check License', 'wpematico'),
					'nonce_handler_license' => wp_create_nonce('wpe-nonce-handler-license')
				)
			);
		}
	}
	public static function save_licenses() {
		if (!isset($_POST['wpematico_save_licenses_nonce']) || !wp_verify_nonce($_POST['wpematico_save_licenses_nonce'], 'wpematico_save_licenses')) {
			wp_redirect(admin_url('edit.php?post_type=wpematico&page=wpematico_settings&tab=pro_licenses'));
			exit();
		}
		$keys = (isset($_POST['license_key']) && !empty($_POST['license_key']) ) ? array_map( 'sanitize_key', $_POST['license_key'] ) : array(); 
		$plugins_args = array();
		$plugins_args = apply_filters('wpematico_plugins_updater_args', $plugins_args);
		update_option( 'wpematico_license_keys', $keys);
		foreach ($keys as $plugin_name => $key) {
			if (empty($plugins_args[$plugin_name])) {
				continue;
			}
			$license = $keys[$plugin_name];
			$args = array(
				'license' 	=> $license,
				'item_name' => urlencode($plugins_args[$plugin_name]['api_data']['item_name']),
				'url'       => home_url(),
				'version' 	=> $plugins_args[$plugin_name]['api_data']['version'],
				'author' 	=> 'Esteban Truelsegaard'	
			);
			$api_url = $plugins_args[$plugin_name]['api_url'];
			$lisense_object = self::check_license($api_url, $args);
			self::set_license_status($plugin_name, $lisense_object->license);
		}
		wp_redirect(admin_url('edit.php?post_type=wpematico&page=wpematico_settings&tab=pro_licenses'));
		exit();
	}
	public static function license_page() {
		
		$plugins_args = array();
		$plugins_args = apply_filters('wpematico_plugins_updater_args', $plugins_args);

		if (empty($plugins_args)) {
			echo '<div class="msg"><p>', __('This is where you would enter the license keys for one of our premium plugins, should you activate one.', 'wpematico'), '</p>';
  			 echo '<p>', __('See some of the WPeMatico Add-ons in the', 'wpematico'), ' <a href="', admin_url( 'plugins.php?page=wpemaddons').'">Extensions list</a>.</p></div>';
  			 return true;
		}
		echo '<form method="post" action="'.admin_url('admin-post.php' ).'">
				<input type="hidden" name="action" value="wpematico_save_licenses">
				'.wp_nonce_field('wpematico_save_licenses', 'wpematico_save_licenses_nonce').'
		';
		foreach ($plugins_args as $plugin_name => $args) {
			$license = self::get_key($plugin_name);
			$plugin_title_name = $args['api_data']['item_name'];
			$license_status = self::get_license_status($plugin_name);

			if ($license != false) {
					
				$args_check = array(
					'license' 	=> $license,
					'item_name' => urlencode($args['api_data']['item_name']),
					'url'       => home_url(),
					'version' 	=> $args['api_data']['version'],
					'author' 	=> 'Esteban Truelsegaard'	
				);
				$api_url = $args['api_url'];
				$license_data = self::check_license($api_url, $args_check);
					
				if (is_object($license_data)) {
					$license_status = (!empty($license_data->license) ? $license_data->license : $license_status );
				}
			}


			$status_license_html = '';
			if ($license_status != false && $license_status == 'valid') {
				$status_license_html = '<strong>'.__('Status', 'wpematico').':</strong> '.__('Valid', 'wpematico').'<span class="validcheck"> </span>
										<br/>
										<input id="'.$plugin_name.'_btn_license_deactivate" class="btn_license_deactivate button-secondary" name="'.$plugin_name.'_btn_license_deactivate" type="button" value="'.__('Deactivate License', 'wpematico').'" style="vertical-align: middle;"/>';
			} else if ($license_status === 'invalid' || $license_status === 'item_name_mismatch' ) {
				$status_license_html = '<strong>'.__('Status', 'wpematico').':</strong> '.__('Invalid', 'wpematico').'<i class="renewcheck"></i>';
			} else if ($license_status === 'expired') {
				$status_license_html = '<strong>'.__('Status', 'wpematico').':</strong> '.__('Expired', 'wpematico').'<i class="renewcheck"></i>';
			} elseif($license_status === 'inactive' || $license_status === 'deactivated' || $license_status === 'site_inactive' ) {
				$status_license_html = '<strong>'.__('Status', 'wpematico').':</strong> '.__('Inactive', 'wpematico').'<i class="warningcheck"></i>
				<br/>
				<input id="'.$plugin_name.'_btn_license_activate" class="btn_license_activate button-secondary" name="'.$plugin_name.'_btn_license_activate" type="button" value="'.__('Activate License', 'wpematico').'"/>
				';
			}
			
			
			$html_addons = '
			<div class="postbox-license">
			<div class="inside">
			<h2><span class="dashicons-before dashicons-admin-plugins"></span>' . $plugin_title_name . __(' License', 'wpematico').'</h2>
			<div class="plugin-img">
				<img src="">
			</div>
			<table class="form-table">
			<tbody>
				<tr valign="top">
					<td>
						<label style="display:block; font-weight: 600; margin-bottom: 10px;">'.__('License Key', 'wpematico').'</label>
						<input id="license_key_'.$plugin_name.'" data-plugin="'.$plugin_name.'" class="regular-text inp_license_key" name="license_key['.$plugin_name.']" type="text" value="'.esc_attr( $license ).'" /><br />
						<label class="description" for="license_key_'.$plugin_name.'">'.__('Enter your license key', 'wpematico').'</label>
					</td>
				</tr>';
				if ($license != false) {
					$html_div = '';
					

					if (is_object($license_data)) {
						
						$currentActivations = !empty($license_data->site_count) ? $license_data->site_count : 0;
						$activationsLeft = !empty($license_data->activations_left) ? $license_data->activations_left : 0;
						$activationsLimit = !empty($license_data->license_limit) ? $license_data->license_limit : 0;
						$expires = !empty($license_data->expires) ? $license_data->expires : 0;
						$expires = ( $expires=='lifetime')? __('Lifetime','wpematico') : substr( $expires, 0, strpos( $expires, " "));

						if (!empty($license_data->payment_id) && !empty($license_data->license_limit)) {
							
							$html_div .= '<small>';
							if ($license_status !== 'valid' && $activationsLeft === 0) {
								$accountUrl = 'http://etruel.com/my-account/?action=manage_licenses&payment_id=' . $license_data->payment_id;
								$html_div .= '<a href="'.$accountUrl.'">'.__("No activations left. Click here to manage the sites you've activated licenses on.", 'wpematico').'</a>
										<br/>';
								
							}
							if ( strtotime($expires) < strtotime("+2 weeks") && $license_data->expires != 'lifetime') {
								$renewalUrl = esc_attr($args['api_url']. '/checkout/?edd_license_key=' . $license); 
								$html_div .= '<a href="'.$renewalUrl.'">'.__('Renew your license to continue receiving updates and support.', 'wpematico').'</a>
										<br/>';
								
							}
							$html_div .= '<strong>'.__('Activations', 'wpematico').':</strong>
										'.$currentActivations.'/'.$activationsLimit.' ('.$activationsLeft.' left)
									<br/>
									<strong>'.__('Expires on', 'wpematico').':</strong>
										<code>'.$expires.'</code>
									<br/>
									<strong>'.__('Registered to', 'wpematico').':</strong>
										'.$license_data->customer_name.' (<code>'.$license_data->customer_email.'</code>)
								</small>';			
							
						}
					}
								
					$html_addons .= '<tr id="tr_license_status_'.$plugin_name.'" class="tr_license_status" style="vertical-align: middle;">
						<th scope="row" style="vertical-align: middle;">
							'.__('Activated for updates', 'wpematico').'
						</th>
						<td id="td_license_status_'.$plugin_name.'">
						<p>'.$status_license_html.'</p>
						<div id="'.$plugin_name.'_ajax_status_license">'.$html_div.'</div>
						</td>
					</tr>';
				} else {
					$html_addons .= '<tr id="tr_license_status_'.$plugin_name.'" class="tr_license_status" style="vertical-align: middle; display:none;">
						<th scope="row" style="vertical-align: middle;">
							'.__('Activated for updates', 'wpematico').'
						</th>
						<td id="td_license_status_'.$plugin_name.'">
							
							<input id="'.$plugin_name.'_btn_license_check" class="btn_license_check button-secondary" name="'.$plugin_name.'_btn_license_check" type="button" value="'.__('Check License', 'wpematico').'"/>
							<div id="'.$plugin_name.'_ajax_status_license" style="display:none;"></div>
						</td>
					</tr>';
					
				}
				
						
			$html_addons .= '</tbody>
			</table>
			</div>
			</div>
			';
			
			echo $html_addons;
			
		}
		submit_button();
		echo '</form>';
	}
	
}
$wpematico_licenses_handlers = new wpematico_licenses_handlers();
?>