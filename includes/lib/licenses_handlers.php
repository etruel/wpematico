<?php
/**
 * File to manage *Addons* licenses to allow update them automatically.
 * Unifies all installed extensions licenses in this file to use the EDD_SL_Plugin_Updater library.
 *
 * Options: `wpematico_license_keys` (key per add-on), `wpematico_license_status`, `wpematico_license_expires`,
 * `wpematico_license_ids` and `wpematico_license_info` (what the store last said about the account, so the
 * screen never asks it on every load).
 */

// Exit if accessed directly
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
class wpematico_licenses_handlers {

	const NONCE			= 'wpe-nonce-handler-license';
	const CHECK_EVERY	= 12 * HOUR_IN_SECONDS;
	const ACCOUNT_URL	= 'https://etruel.com/my-account/purchase-history/';
	const CHECKOUT_URL	= 'https://etruel.com/checkout/';

	function __construct() {
		add_action('init', array(__CLASS__, 'updater_context'), 0 );
		add_action('wpempro_licenses_forms', array(__CLASS__, 'license_page') );
		add_action('admin_print_scripts', array(__CLASS__, 'scripts'));
		add_action('admin_print_styles', array(__CLASS__, 'styles'));

		add_action('wp_ajax_wpematico_license', array(__CLASS__, 'ajax_license'));
	}

	/**
	 * Updates come from the store: the admin builds the updaters at once. WP-Cron, whose
	 * update check never fires admin_init, loads this file and builds them only when one of
	 * its update events is about to run (see wpematico.php), so a campaign run pays nothing.
	 */
	public static function updater_context() {
		if (is_admin()) {
			self::extension_updater();
		}
	}

	public static function extension_updater() {
		static $built = false;
		if ($built) {
			return;
		}
		$built = true;
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
		// 'failed' is the answer to a deactivation that did not happen, not a state of the license.
		if (!empty($license_data->license) && is_string($license_data->license) && 'failed' !== $license_data->license) {
			self::set_license_status($plugin_name, $license_data->license);
		}
		if (!empty($license_data->expires)) {
			self::set_license_expires($plugin_name, $license_data->expires);
		}
		self::set_license_info($plugin_name, $license_data);
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

	/**
	 * What the store last said about the account behind a license.
	 *
	 * @return array Empty when the store has not been asked yet.
	 */
	public static function get_license_info($plugin_name) {
		$all = get_option('wpematico_license_info');
		return (is_array($all) && isset($all[$plugin_name]) && is_array($all[$plugin_name])) ? $all[$plugin_name] : array();
	}

	private static function set_license_info($plugin_name, $data) {
		$expires = isset($data->expires) ? (string) $data->expires : '';
		$info	 = array(
			'expires'			=> ('lifetime' === $expires) ? 'lifetime' : substr(sanitize_text_field($expires), 0, 10),
			'site_count'		=> isset($data->site_count) ? (int) $data->site_count : 0,
			'license_limit'		=> isset($data->license_limit) ? (int) $data->license_limit : 0,
			'activations_left'	=> (isset($data->activations_left) && 'unlimited' === $data->activations_left) ? 'unlimited' : (isset($data->activations_left) ? (int) $data->activations_left : 0),
			'payment_id'		=> isset($data->payment_id) ? (int) $data->payment_id : 0,
			'license_id'		=> (int) self::extract_license_id_from_data($data),
			'item_id'			=> isset($data->item_id) ? (int) $data->item_id : 0,
			'customer_name'		=> isset($data->customer_name) ? sanitize_text_field($data->customer_name) : '',
			'customer_email'	=> isset($data->customer_email) ? sanitize_email($data->customer_email) : '',
			'checked'			=> time(),
		);
		if (empty($info['license_id']) && $stored = self::get_license_id($plugin_name)) {
			$info['license_id'] = (int) $stored;
		}
		$all = get_option('wpematico_license_info');
		$all = is_array($all) ? $all : array();
		$all[$plugin_name] = $info;
		update_option('wpematico_license_info', $all, false);
	}

	/**
	 * The store did not answer: ask again in five minutes, not on every load of the screen.
	 */
	private static function postpone_check($plugin_name) {
		$all  = get_option('wpematico_license_info');
		$all  = is_array($all) ? $all : array();
		$info = (isset($all[$plugin_name]) && is_array($all[$plugin_name])) ? $all[$plugin_name] : array();
		$info['checked'] = time() - self::CHECK_EVERY + 5 * MINUTE_IN_SECONDS;
		$all[$plugin_name] = $info;
		update_option('wpematico_license_info', $all, false);
	}

	private static function forget($plugin_name) {
		foreach (array('wpematico_license_status', 'wpematico_license_expires', 'wpematico_license_ids', 'wpematico_license_info') as $option) {
			$map = get_option($option);
			if (is_array($map) && isset($map[$plugin_name])) {
				unset($map[$plugin_name]);
				update_option($option, $map, false);
			}
		}
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

	/**
	 * The store's product id of an add-on: the one it declares, else the one the store told.
	 */
	private static function download_id($plugin_name, $args) {
		if (!empty($args['api_data']['item_id'])) {
			return (int) $args['api_data']['item_id'];
		}
		$info = self::get_license_info($plugin_name);
		if (!empty($info['item_id'])) {
			return (int) $info['item_id'];
		}
		return (int) self::get_item_id_from_feed_cache($args['api_data']['item_name']);
	}

	/**
	 * A key as the screen shows it: the last four characters, so a license can be told
	 * apart without exposing it to whoever looks at the screen.
	 */
	public static function mask_key($key) {
		$key = (string) $key;
		$len = strlen($key);
		if ($len <= 8) {
			return str_repeat('*', $len);
		}
		return str_repeat('*', 12) . substr($key, -4);
	}

	/**
	 * What a field posted back: the stored key when it is the masked one, else what was typed.
	 */
	private static function resolve_posted_key($plugin_name, $posted) {
		$posted = trim(sanitize_text_field((string) $posted));
		$stored = self::get_key($plugin_name);
		if ('' !== $posted && $stored && self::mask_key($stored) === $posted) {
			return $stored;
		}
		return $posted;
	}

	/**
	 * One request to the store: the answer as an object, or false when it did not give a usable one.
	 */
	private static function post($api_url, $api_params) {
		$response = wp_remote_post(esc_url_raw($api_url), array('timeout' => 15, 'body' => $api_params));
		if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)) {
			return false;
		}
		$license_data = json_decode(wp_remote_retrieve_body($response));
		return is_object($license_data) ? $license_data : false;
	}

	/**
	 * Asks the store to check, activate or deactivate a license of an add-on, and remembers the answer.
	 *
	 * @param  string      $plugin_name  Key of the add-on in `wpematico_plugins_updater_args`.
	 * @param  string      $action       check_license | activate_license | deactivate_license
	 * @param  string|null $license      The key; the stored one when null.
	 * @return object|false
	 */
	public static function change_status_license($plugin_name, $action, $license = null) {
		if (!in_array($action, array('check_license', 'activate_license', 'deactivate_license'), true)) {
			return false;
		}
		$plugins_args = array();
		$plugins_args = apply_filters('wpematico_plugins_updater_args', $plugins_args);
		if (empty($plugins_args[$plugin_name])) {
			return false;
		}
		if (null === $license) {
			$license = self::get_key($plugin_name);
		}
		if (empty($license)) {
			return false;
		}

		$api_url = $plugins_args[$plugin_name]['api_url'];
		$args	 = self::build_check_license_args($license, $plugins_args[$plugin_name]);
		$args['edd_action'] = $action;

		$license_data = self::post($api_url, $args);
		if (!$license_data) {
			self::postpone_check($plugin_name);
			return false;
		}
		self::remember_license_data($plugin_name, $license_data);
		if ($license_id = self::extract_license_id_from_data($license_data)) {
			self::set_license_id($plugin_name, $license_id);
		}

		// A refused activation says 'invalid' and puts the reason in `error`: an expired key
		// and a key with no activations left both look the same until the license is asked.
		if ('activate_license' === $action && (empty($license_data->license) || 'valid' !== $license_data->license)) {
			$args['edd_action'] = 'check_license';
			$real = self::post($api_url, $args);
			if ($real) {
				self::remember_license_data($plugin_name, $real);
			}
		}
		return $license_data;
	}

	public static function check_license($api_url, $args) {
		$args['edd_action'] = 'check_license';
		return self::post($api_url, $args);
	}

	/**
	 * Stores the keys the Licenses tab posted and activates the ones that changed.
	 *
	 * Only the add-ons that are loaded are touched: a key of an add-on that is deactivated
	 * stays where it is. A masked key means "unchanged".
	 *
	 * @param  array $posted  license_key[] as posted, unslashed.
	 * @return array|false  text and error flag of the notice, or false when the user may not.
	 */
	public static function save_keys($posted) {
		if (!current_user_can('manage_options') || !is_array($posted)) {
			return false;
		}
		$plugins_args = array();
		$plugins_args = apply_filters('wpematico_plugins_updater_args', $plugins_args);
		$keys = get_option('wpematico_license_keys');
		$keys = is_array($keys) ? $keys : array();
		$text  = '';
		$error = false;

		foreach ($plugins_args as $plugin_name => $args) {
			if (!isset($posted[$plugin_name]) || !is_string($posted[$plugin_name])) {
				continue;
			}
			$new = self::resolve_posted_key($plugin_name, $posted[$plugin_name]);
			$old = isset($keys[$plugin_name]) ? (string) $keys[$plugin_name] : '';
			if ($new === $old) {
				continue;
			}
			if ('' !== $old && 'valid' === self::get_license_status($plugin_name)) {
				self::change_status_license($plugin_name, 'deactivate_license', $old);
			}
			if ('' === $new) {
				unset($keys[$plugin_name]);
				update_option('wpematico_license_keys', $keys);
				self::forget($plugin_name);
				continue;
			}
			$keys[$plugin_name] = $new;
			update_option('wpematico_license_keys', $keys);
			self::forget($plugin_name);
			$data  = self::change_status_license($plugin_name, 'activate_license', $new);
			$text  = self::activation_message($data);
			$error = !($data && !empty($data->license) && 'valid' === $data->license);
		}
		return array('text' => ('' !== $text) ? $text : __('License keys saved.', 'wpematico'), 'error' => $error);
	}

	/**
	 * The sentence for the answer to an activation.
	 */
	private static function activation_message($data) {
		if (!$data) {
			return __('The license server did not answer. Try again in a moment.', 'wpematico');
		}
		if (!empty($data->license) && 'valid' === $data->license) {
			return __('License activated for this site!', 'wpematico');
		}
		return self::refusal_message($data);
	}

	private static function refusal_message($data) {
		$messages = array(
			'expired'				=> __('This license has expired.', 'wpematico'),
			'disabled'				=> __('This license has been disabled.', 'wpematico'),
			'revoked'				=> __('This license has been disabled.', 'wpematico'),
			'missing'				=> __('This license key does not exist.', 'wpematico'),
			'invalid'				=> __('This license key does not exist.', 'wpematico'),
			'no_activations_left'	=> __('This license has no activations left.', 'wpematico'),
			'item_name_mismatch'	=> __('This license key is for another product.', 'wpematico'),
			'invalid_item_id'		=> __('This license key is for another product.', 'wpematico'),
		);
		$code = (isset($data->error) && is_string($data->error)) ? $data->error : ((isset($data->license) && is_string($data->license)) ? $data->license : '');
		return isset($messages[$code]) ? $messages[$code] : __('The license could not be activated.', 'wpematico');
	}

	/**
	 * Check, activate or deactivate from the buttons of the Licenses tab.
	 *
	 * Answers the refreshed status block. A key typed and not yet saved can only be checked,
	 * and then there is a message and no block.
	 */
	public static function ajax_license() {
		if (!current_user_can('manage_options') || !check_ajax_referer(self::NONCE, 'nonce', false)) {
			wp_send_json_error(array('message' => __('You are not allowed to do this.', 'wpematico')), 403);
		}
		$do			 = isset($_POST['do']) ? sanitize_key(wp_unslash($_POST['do'])) : '';
		$plugin_name = isset($_POST['plugin']) ? sanitize_text_field(wp_unslash($_POST['plugin'])) : '';
		$typed		 = isset($_POST['license']) ? self::resolve_posted_key($plugin_name, wp_unslash($_POST['license'])) : '';

		$plugins_args = array();
		$plugins_args = apply_filters('wpematico_plugins_updater_args', $plugins_args);
		if (!in_array($do, array('check_license', 'activate_license', 'deactivate_license'), true) || empty($plugins_args[$plugin_name])) {
			wp_send_json_error(array('message' => __('Invalid request.', 'wpematico')), 400);
		}

		// One question to the store per add-on every ten seconds, whoever asks and however:
		// a loop on this endpoint would otherwise become a loop on the store, and each call
		// holds a PHP worker for as long as the store takes.
		$gate = 'wpematico_license_wait_' . md5($plugin_name);
		if (get_transient($gate)) {
			wp_send_json_error(array('message' => __('Please wait a few seconds before asking the store again.', 'wpematico')));
		}
		set_transient($gate, 1, 10);

		$stored = self::get_key($plugin_name);
		if ('check_license' === $do && '' !== $typed && $typed !== $stored) {
			$args = self::build_check_license_args($typed, $plugins_args[$plugin_name]);
			$data = self::check_license($plugins_args[$plugin_name]['api_url'], $args);
			if (!$data) {
				wp_send_json_error(array('message' => __('The license server did not answer. Try again in a moment.', 'wpematico')));
			}
			$good = in_array(isset($data->license) ? $data->license : '', array('valid', 'inactive', 'site_inactive'), true);
			wp_send_json_success(array('html' => '', 'message' => $good ? __('This key is valid, please save settings.', 'wpematico') : self::refusal_message($data), 'ok' => $good));
		}

		if (!$stored) {
			wp_send_json_error(array('message' => __('Save a license key first.', 'wpematico')));
		}
		$data = self::change_status_license($plugin_name, $do);
		if (!$data) {
			wp_send_json_error(array('message' => __('The license server did not answer. Try again in a moment.', 'wpematico')));
		}
		$answer = self::answer_message($do, $data);
		wp_send_json_success(array(
			'html'		=> self::render_status($plugin_name, $plugins_args[$plugin_name]),
			'message'	=> $answer['text'],
			'ok'		=> $answer['ok'],
		));
	}

	/**
	 * The sentence under a card for what the store answered to a button. Each button says
	 * something, because pressing Check on a license that is already valid repaints the
	 * same block and would look as if nothing happened.
	 *
	 * @return array text and whether it is good news.
	 */
	private static function answer_message($do, $data) {
		$license = (!empty($data->license) && is_string($data->license)) ? $data->license : '';
		if ('activate_license' === $do) {
			return array('text' => self::activation_message($data), 'ok' => 'valid' === $license);
		}
		if ('deactivate_license' === $do) {
			$done = in_array($license, array('deactivated', 'inactive', 'site_inactive'), true);
			return array('text' => $done ? __('License deactivated for this site.', 'wpematico') : __('The license could not be deactivated.', 'wpematico'), 'ok' => $done);
		}
		if ('valid' === $license) {
			return array('text' => __('The license is valid and active for this site.', 'wpematico'), 'ok' => true);
		}
		if (in_array($license, array('inactive', 'site_inactive', 'deactivated'), true)) {
			return array('text' => __('The license is valid but this site is not activated with it.', 'wpematico'), 'ok' => false);
		}
		return array('text' => self::refusal_message($data), 'ok' => false);
	}

	public static function styles() {
		$screen = get_current_screen();
		if (!is_null($screen)) {
			if ($screen->id == 'wpematico_page_wpematico_settings') {
				wp_enqueue_style('wpematico-settings-licenses', WPEMATICO_PLUGIN_URL . 'assets/css/licenses_handlers.css', array(), WPEMATICO_VERSION);
			}
		}
	}
	public static function scripts() {
		$screen = get_current_screen();
		if (!is_null($screen) && $screen->id == 'wpematico_page_wpematico_settings') {
			wp_enqueue_script( 'wpematico-jquery-settings-licenses', WPEMATICO_PLUGIN_URL. 'assets/js/licenses_handlers.js', array( 'jquery' ), WPEMATICO_VERSION, true );
			wp_localize_script('wpematico-jquery-settings-licenses', 'wpematico_license_object',
				array('ajax_url' => admin_url( 'admin-ajax.php' ),
					'txt_check_license' 	=> __('Check License', 'wpematico'),
					'txt_working'			=> __('Working…', 'wpematico'),
					'txt_failed'			=> __('The license server did not answer. Try again in a moment.', 'wpematico'),
					'nonce_handler_license' => wp_create_nonce(self::NONCE)
				)
			);
		}
	}

	/**
	 * The page of the customer's account for this license: its sites or its upgrades.
	 */
	private static function account_url($info, $license_id, $view = '') {
		$params = array('action' => 'manage_licenses', 'payment_id' => $info['payment_id']);
		if ('' !== $view) {
			$params = array('view' => $view) + $params;
		}
		if ($license_id) {
			$params['license_id'] = $license_id;
		}
		return add_query_arg($params, self::ACCOUNT_URL);
	}

	private static function extend_url($license, $download_id) {
		return add_query_arg(array('edd_license_key' => $license, 'download_id' => $download_id ? $download_id : ''), self::CHECKOUT_URL);
	}

	/**
	 * The status part of a license card: state, buttons, and what the store says about the account.
	 *
	 * The store is asked only when it has not been in the last twelve hours; Check License,
	 * Activate and Deactivate ask it at once.
	 */
	public static function render_status($plugin_name, $args) {
		$license = self::get_key($plugin_name);
		if (!$license) {
			return '';
		}
		$info = self::get_license_info($plugin_name);
		if (empty($info['checked']) || $info['checked'] < time() - self::CHECK_EVERY) {
			self::change_status_license($plugin_name, 'check_license');
			$info = self::get_license_info($plugin_name);
		}
		$status = self::get_license_status($plugin_name);

		// A valid license has nothing to check: the screen asks the store again by itself
		// every twelve hours. Check License is for a license that is not valid, once the
		// reason (a renewal, a corrected key) has been dealt with.
		$button = '<input class="button-secondary wpe-license-act" data-do="%s" type="button" value="%s" />';
		$html	= '';
		if ('valid' === $status) {
			$html .= '<div class="wpe-status-subtitle">' . esc_html__('Activated for updates', 'wpematico') . '</div><p>'
				. '<span class="validcheck"></span><strong>' . esc_html__('Valid', 'wpematico') . '</strong> '
				. sprintf($button, 'deactivate_license', esc_attr__('Deactivate License', 'wpematico'));
		} elseif (in_array($status, array('inactive', 'deactivated', 'site_inactive'), true)) {
			$html .= '<p><span class="warningcheck"></span><strong>' . esc_html__('Inactive', 'wpematico') . '</strong> '
				. sprintf($button, 'activate_license', esc_attr__('Activate License', 'wpematico'));
		} else {
			$html .= '<p>';
			if ('expired' === $status) {
				$html .= '<span class="renewcheck"></span><strong>' . esc_html__('Expired', 'wpematico') . '</strong> ';
			} elseif ($status) {
				$html .= '<span class="renewcheck"></span><strong>' . esc_html__('Invalid', 'wpematico') . '</strong> ';
			}
			$html .= sprintf($button, 'check_license', esc_attr__('Check License', 'wpematico'));
		}
		$html .= '</p>';

		if (!empty($info['payment_id'])) {
			$expires	 = isset($info['expires']) ? $info['expires'] : '';
			$left		 = isset($info['activations_left']) ? $info['activations_left'] : 0;
			$limit		 = isset($info['license_limit']) ? (int) $info['license_limit'] : 0;
			$license_id	 = !empty($info['license_id']) ? (int) $info['license_id'] : (int) self::get_license_id($plugin_name);
			$download_id = self::download_id($plugin_name, $args);

			$html .= '<div class="license-meta-info">';
			if ('valid' !== $status && 'unlimited' !== $left && $limit > 0 && (int) $left <= 0) {
				$html .= '<div class="wpe-license-notice"><a href="' . esc_url(self::account_url($info, $license_id)) . '" target="_blank" rel="noopener">' . esc_html__("No activations left. Click here to manage the sites you've activated licenses on.", 'wpematico') . '</a></div>';
			}
			if ('lifetime' !== $expires && '' !== $expires && false !== strtotime($expires) && strtotime($expires) < time() + 2 * WEEK_IN_SECONDS) {
				$html .= '<div class="wpe-license-notice"><a href="' . esc_url(self::extend_url($license, $download_id)) . '" target="_blank" rel="noopener">' . esc_html__('Renew your license to continue receiving updates and support.', 'wpematico') . '</a></div>';
			}
			$html .= '<strong>' . esc_html__('Activations', 'wpematico') . ':</strong> ' . (int) $info['site_count'] . '/' . ($limit > 0 ? $limit : '&infin;')
				. ' (' . ('unlimited' === $left ? esc_html__('unlimited', 'wpematico') : (int) $left) . ' ' . esc_html__('left', 'wpematico') . ')<br/>'
				. '<strong>' . esc_html__('Expires on', 'wpematico') . ':</strong> <code>' . esc_html('lifetime' === $expires ? __('Lifetime', 'wpematico') : $expires) . '</code><br/>'
				. '<strong>' . esc_html__('Version', 'wpematico') . ':</strong> ' . esc_html($args['api_data']['version']) . '<br/>'
				. '<strong>' . esc_html__('Registered to', 'wpematico') . ':</strong> ' . esc_html($info['customer_name']) . ' (<code>' . esc_html($info['customer_email']) . '</code>)'
				. '<div class="wpe-license-links">'
				. '<a href="' . esc_url(self::account_url($info, $license_id)) . '" target="_blank" rel="noopener">' . esc_html__('Manage Sites', 'wpematico') . '</a>'
				. ' &ndash; '
				. '<a href="' . esc_url(self::account_url($info, $license_id, 'upgrades')) . '" target="_blank" rel="noopener">' . esc_html__('View Upgrades', 'wpematico') . '</a>'
				. ('lifetime' === $expires ? '' : ' &ndash; <a href="' . esc_url(self::extend_url($license, $download_id)) . '" target="_blank" rel="noopener">' . esc_html__('Extend', 'wpematico') . '</a>')
				. '</div></div>';
		}
		return $html;
	}

	public static function license_page() {
		if (!current_user_can('manage_options')) {
			return;
		}
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
			$name	 = esc_attr($plugin_name);

			echo '<div class="postbox-license" data-plugin="' . $name . '">
			<h2><span class="dashicons dashicons-admin-plugins"></span>' . esc_html($args['api_data']['item_name']) . esc_html__(' License', 'wpematico') . '</h2>
			<div class="wpe-license-body">
			<div class="wpe-lk-field">
				<label class="wpe-lk-label" for="license_key_' . $name . '">' . esc_html__('License Key', 'wpematico') . '</label>
				<input id="license_key_' . $name . '" data-plugin="' . $name . '" class="inp_license_key" name="license_key[' . $name . ']" type="text" autocomplete="off" spellcheck="false" value="' . esc_attr($license ? self::mask_key($license) : '') . '" />
				<span class="wpe-lk-hint">' . esc_html__('Enter your license key', 'wpematico') . '</span>
			</div>
			<div class="wpe-status-section"' . ($license ? '' : ' style="display:none;"') . '>
				<div class="wpe-status-row">' . self::render_status($plugin_name, $args) . '</div>
				<div class="wpe-license-message" role="status"></div>
			</div>
			</div><!-- .wpe-license-body -->
			</div><!-- .postbox-license -->
			';
		}
		echo '</div><!-- .wpe-licenses-form -->';
	}

}
$wpematico_licenses_handlers = new wpematico_licenses_handlers();
?>
