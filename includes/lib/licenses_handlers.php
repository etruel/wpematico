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
			if(file_exists(WPEMATICO_PLUGIN_DIR . 'includes/lib/EDD_SL_Plugin_Updater.php')) {
				require_once(WPEMATICO_PLUGIN_DIR . 'includes/lib/EDD_SL_Plugin_Updater.php');
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
	/**
	 * The date the license runs out, as the store last reported it. Stored because the
	 * screens that ask for a renewal cannot ask the store.
	 */
	public static function get_license_expires($plugin_name) {
		$dates = get_option('wpematico_license_expires');
		if (!is_array($dates) || empty($dates[$plugin_name])) {
			return '';
		}
		return $dates[$plugin_name];
	}
	public static function set_license_expires($plugin_name, $expires) {
		$dates = get_option('wpematico_license_expires');
		if (!is_array($dates)) {
			$dates = array();
		}
		$expires = ('lifetime' === $expires) ? 'lifetime' : substr((string) $expires, 0, 10);
		if (isset($dates[$plugin_name]) && $dates[$plugin_name] === $expires) {
			return;
		}
		$dates[$plugin_name] = $expires;
		update_option('wpematico_license_expires', $dates);
	}
	/**
	 * Stores what a license check just answered about this license.
	 */
	public static function remember_license_data($plugin_name, $license_data) {
		if (!is_object($license_data)) {
			return;
		}
		if (!empty($license_data->license)) {
			self::set_license_status($plugin_name, $license_data->license);
		}
		if (!empty($license_data->expires)) {
			self::set_license_expires($plugin_name, $license_data->expires);
		}
	}
	public static function get_license_id($plugin_name) {
		$ids = get_option('wpematico_license_ids');
		if ($ids === false) {
			$ids = array();
		}
		if (empty($ids[$plugin_name])) {
			return false;
		}
		return $ids[$plugin_name];
	}
	public static function set_license_id($plugin_name, $id) {
		$ids = get_option('wpematico_license_ids');
		if ($ids === false) {
			$ids = array();
		}
		$ids[$plugin_name] = (int) $id;
		update_option( 'wpematico_license_ids', $ids);
	}
	private static function extract_license_id_from_data($license_data) {
		if ( !is_object($license_data) ) {
			return false;
		}
		if ( !empty($license_data->ID) ) {
			return (int) $license_data->ID;
		}
		if ( !empty($license_data->license_id) ) {
			return (int) $license_data->license_id;
		}
		return false;
	}
	private static function get_item_id_from_feed_cache($item_name) {
		$addons = get_transient('etruel_wpematico_addons_data');
		if ( empty($addons) || !is_array($addons) ) {
			return false;
		}
		$normalized = str_replace('-', '_', strtolower(sanitize_file_name($item_name)));
		if ( !empty($addons[$normalized]['id']) ) {
			return (int) $addons[$normalized]['id'];
		}
		foreach ($addons as $addon) {
			if ( !empty($addon['Name']) && 0 === strcasecmp($addon['Name'], $item_name) ) {
				return !empty($addon['id']) ? (int) $addon['id'] : false;
			}
		}
		return false;
	}
	private static function build_check_license_args($license, $plugin_args) {
		$item_id = !empty($plugin_args['api_data']['item_id'])
			? (int) $plugin_args['api_data']['item_id']
			: self::get_item_id_from_feed_cache($plugin_args['api_data']['item_name']);
		$args = array(
			'license'   => $license,
			'item_name' => urlencode($plugin_args['api_data']['item_name']),
			'url'       => home_url(),
			'version'   => $plugin_args['api_data']['version'],
			'author'    => 'Esteban Truelsegaard',
		);
		if ($item_id) {
			$args['item_id'] = $item_id;
		}
		return $args;
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
		self::remember_license_data($plugin_name, $license_data);
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
				wp_enqueue_style('wpematico-settings-licenses', WPEMATICO_PLUGIN_URL . 'assets/css/licenses_handlers.css');
			}
		}
	}
	public static function scripts() {
		$screen = get_current_screen();
		if ($screen->id == 'wpematico_page_wpematico_settings') {
			wp_enqueue_script( 'wpematico-jquery-settings-licenses', WPEMATICO_PLUGIN_URL. 'assets/js/licenses_handlers.js', array( 'jquery' ), WPEMATICO_VERSION, true );
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
			wp_redirect(admin_url('admin.php?page=wpematico_settings&tab=pro_licenses'));
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
			$args = self::build_check_license_args($license, $plugins_args[$plugin_name]);
			$api_url = $plugins_args[$plugin_name]['api_url'];
			$lisense_object = self::check_license($api_url, $args);
			self::remember_license_data($plugin_name, $lisense_object);
			$license_id = self::extract_license_id_from_data($lisense_object);
			if ($license_id) {
				self::set_license_id($plugin_name, $license_id);
			}
		}
		wp_redirect(admin_url('admin.php?page=wpematico_settings&tab=pro_licenses'));
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
		echo '<div class="wpe-licenses-form">';
		foreach ($plugins_args as $plugin_name => $args) {
			$license = self::get_key($plugin_name);
			$plugin_title_name = $args['api_data']['item_name'];
			$license_status = self::get_license_status($plugin_name);

			if ($license != false) {
					
				$args_check = self::build_check_license_args($license, $args);
				$api_url = $args['api_url'];
				$license_data = self::check_license($api_url, $args_check);
					
				if (is_object($license_data)) {
					$license_status = (!empty($license_data->license) ? $license_data->license : $license_status );
					self::remember_license_data($plugin_name, $license_data);
					$api_id = self::extract_license_id_from_data($license_data);
					if ($api_id) {
						self::set_license_id($plugin_name, $api_id);
					}
					}
			}


			$status_license_html = '';
			if ($license_status != false && $license_status == 'valid') {
				$status_license_html = '<span class="validcheck"></span><strong>'.__('Valid', 'wpematico').'</strong>
										<input id="'.$plugin_name.'_btn_license_deactivate" class="btn_license_deactivate button-secondary" name="'.$plugin_name.'_btn_license_deactivate" type="button" value="'.__('Deactivate License', 'wpematico').'" />';
			} else if ($license_status === 'invalid' || $license_status === 'item_name_mismatch' ) {
				$status_license_html = '<span class="renewcheck"></span><strong>'.__('Invalid', 'wpematico').'</strong>';
			} else if ($license_status === 'expired') {
				$status_license_html = '<span class="renewcheck"></span><strong>'.__('Expired', 'wpematico').'</strong>';
			} elseif($license_status === 'inactive' || $license_status === 'deactivated' || $license_status === 'site_inactive' ) {
				$status_license_html = '<span class="warningcheck"></span><strong>'.__('Inactive', 'wpematico').'</strong>
				<input id="'.$plugin_name.'_btn_license_activate" class="btn_license_activate button-secondary" name="'.$plugin_name.'_btn_license_activate" type="button" value="'.__('Activate License', 'wpematico').'" />
				';
			}
			
			
			$html_addons = '
			<div class="postbox-license">
			<h2><span class="dashicons dashicons-admin-plugins"></span>' . esc_html( $plugin_title_name ) . esc_html__(' License', 'wpematico') . '</h2>
			<div class="wpe-license-body">
			<div class="wpe-lk-field">
				<label class="wpe-lk-label" for="license_key_'.$plugin_name.'">'.__('License Key', 'wpematico').'</label>
				<input id="license_key_'.$plugin_name.'" data-plugin="'.$plugin_name.'" class="inp_license_key" name="license_key['.$plugin_name.']" type="text" value="'.esc_attr( $license ).'" />
				<span class="wpe-lk-hint">'.__('Enter your license key', 'wpematico').'</span>
			</div>';
				if ($license != false) {
					$html_div = '';
					

					if (is_object($license_data)) {
						
						$currentActivations = !empty($license_data->site_count) ? $license_data->site_count : 0;
						$activationsLeft = !empty($license_data->activations_left) ? $license_data->activations_left : 0;
						$activationsLimit = !empty($license_data->license_limit) ? $license_data->license_limit : 0;
						$expires = !empty($license_data->expires) ? $license_data->expires : 0;
						$expires = ( $expires=='lifetime')? __('Lifetime','wpematico') : substr( $expires, 0, strpos( $expires, " "));

						if (!empty($license_data->payment_id) && !empty($license_data->license_limit)) {
							
							$html_div .= '<div class="license-meta-info">';

							if ($license_status !== 'valid' && $activationsLeft === 0) {
								$account_lid = self::extract_license_id_from_data($license_data) ?: self::get_license_id($plugin_name);
								$account_params = ['action' => 'manage_licenses', 'payment_id' => $license_data->payment_id];
								if ($account_lid) {
									$account_params['license_id'] = $account_lid;
								}
								$accountUrl = add_query_arg( $account_params, 'https://etruel.com/my-account/purchase-history/' );
								$html_div .= '<div class="wpe-license-notice"><a href="'.esc_url($accountUrl).'" target="_blank">'.__("No activations left. Click here to manage the sites you've activated licenses on.", 'wpematico').'</a></div>';
							}
							if ( strtotime($expires) < strtotime("+2 weeks") && $license_data->expires != 'lifetime') {
								$renewalUrl = add_query_arg( [
									'edd_license_key' => $license,
									'download_id'     => !empty($license_data->download_id) ? $license_data->download_id : '',
								], 'https://etruel.com/checkout/' );
								$html_div .= '<div class="wpe-license-notice"><a href="'.esc_url($renewalUrl).'" target="_blank">'.__('Renew your license to continue receiving updates and support.', 'wpematico').'</a></div>';
							}

							$html_div .= '<strong>'.__('Activations', 'wpematico').':</strong> '.$currentActivations.'/'.$activationsLimit.' ('.$activationsLeft.' '.__('left','wpematico').')<br/>'
								.'<strong>'.__('Expires on', 'wpematico').':</strong> <code>'.$expires.'</code><br/>'
								.'<strong>'.__('Version', 'wpematico').':</strong> '.esc_html($args['api_data']['version']).'<br/>'
								.'<strong>'.__('Registered to', 'wpematico').':</strong> '.esc_html($license_data->customer_name).' (<code>'.esc_html($license_data->customer_email).'</code>)';

							if (!empty($license_data->payment_id)) {
								$base  = 'https://etruel.com/my-account/purchase-history/';
								$pid   = $license_data->payment_id;
								$lid   = self::extract_license_id_from_data($license_data) ?: self::get_license_id($plugin_name);
								$did   = !empty($license_data->download_id) ? $license_data->download_id
										: ( !empty($license_data->item_id) ? $license_data->item_id
										: ( !empty($args['api_data']['item_id']) ? $args['api_data']['item_id']
										: ( self::get_item_id_from_feed_cache($args['api_data']['item_name']) ?: '' ) ) );

								$manage_params   = ['action' => 'manage_licenses', 'payment_id' => $pid];
								$upgrades_params = ['view' => 'upgrades', 'action' => 'manage_licenses', 'payment_id' => $pid];
								if ($lid) {
									$manage_params['license_id']   = $lid;
									$upgrades_params['license_id'] = $lid;
								}

								$manage_url   = esc_url( add_query_arg( $manage_params,   $base ) );
								$upgrades_url = esc_url( add_query_arg( $upgrades_params, $base ) );
								$extend_url   = esc_url( add_query_arg( ['edd_license_key' => $license, 'download_id' => $did], 'https://etruel.com/checkout/' ) );

								$html_div .= '<div class="wpe-license-links">'
									.'<a href="'.$manage_url.'" target="_blank">'.__('Manage Sites','wpematico').'</a>'
									.' &ndash; '
									.'<a href="'.$upgrades_url.'" target="_blank">'.__('View Upgrades','wpematico').'</a>'
									.' &ndash; '
									.'<a href="'.$extend_url.'" target="_blank">'.__('Extend','wpematico').'</a>'
									.'</div>';
							}

							$html_div .= '</div>';			
							
						}
					}
								
					$html_addons .= '<div id="tr_license_status_'.$plugin_name.'" class="tr_license_status wpe-status-section">
						<div class="wpe-status-subtitle">'.__('Activated for updates', 'wpematico').'</div>
						<div id="td_license_status_'.$plugin_name.'" class="wpe-status-row">
							<p>'.$status_license_html.'</p>
							<div id="'.$plugin_name.'_ajax_status_license">'.$html_div.'</div>
						</div>
					</div>';
				} else {
					$html_addons .= '<div id="tr_license_status_'.$plugin_name.'" class="tr_license_status wpe-status-section" style="display:none;">
						<div class="wpe-status-subtitle">'.__('Activated for updates', 'wpematico').'</div>
						<div id="td_license_status_'.$plugin_name.'" class="wpe-status-row">
							<input id="'.$plugin_name.'_btn_license_check" class="btn_license_check button-secondary" name="'.$plugin_name.'_btn_license_check" type="button" value="'.__('Check License', 'wpematico').'"/>
							<div id="'.$plugin_name.'_ajax_status_license" style="display:none;"></div>
						</div>
					</div>';
					
				}
				
						
			$html_addons .= '</div><!-- .wpe-license-body -->
			</div><!-- .postbox-license -->
			';
			
			echo $html_addons;
			
		}
		echo '</div><!-- .wpe-licenses-form -->';
	}
	
}
$wpematico_licenses_handlers = new wpematico_licenses_handlers();
?>