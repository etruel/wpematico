<?php

defined( 'ABSPATH' ) || exit;

require_once(WPEMATICO_PLUGIN_DIR . 'includes/lib/update_reasons.php');

/**
 * Plugin_Update class
 * @since 2.7.7
 */
class WPeMatico_Update{

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	private $slug = 'wpematico';

	/** Add-ons whose unattended update is held back, and since when. */
	const WAITING_OPTION = 'wpematico_updates_waiting';

	/** Set when the core plugin's automatic updates were turned on along with an add-on's. */
	const CORE_AUTOUPDATE_NOTICE = 'wpematico_core_autoupdate_added';

	/**
	 * The Constructor.
	 *
	 * @return void
	 */
	public static function hooks() {
		add_filter( 'site_transient_update_plugins', array(__CLASS__, 'maybe_disable_update'), 91, 1 );

		// The unattended updates decide in cron, where no screen exists and the filter
		// above is not even applied: an add-on never installs ahead of the core plugin.
		add_filter( 'auto_update_plugin', array(__CLASS__, 'maybe_block_auto_update'), 20, 2 );

		// Whichever screen turns an add-on's automatic updates on, the core plugin's go
		// on with it, or the add-on would wait for an update that never arrives.
		add_filter( 'pre_update_site_option_auto_update_plugins', array(__CLASS__, 'keep_core_auto_updated'), 10, 2 );

		if (is_admin()) {
			add_action( 'admin_print_footer_scripts-update-core.php', array(__CLASS__, 'mark_blocked_rows') );
			add_action( 'admin_notices', array(__CLASS__, 'core_auto_update_notice') );
		}
	}

	/**
	 * Remove package download URL if needed.
	 *
	 * @param object $transient Original transient.
	 * @return mixed
	 */
    public static function maybe_disable_update($transient) {
        if (defined('DOING_CRON') && DOING_CRON) {
            return $transient;
        }

		$plugins_args = array();
		$plugins_args = apply_filters('wpematico_plugins_updater_args', $plugins_args);

		
		foreach ($plugins_args as $plugin_name => $plugin_data) {
			
			if (class_exists('wpematico_licenses_handlers') && wpematico_licenses_handlers::get_license_status($plugin_name) != 'valid') {
				if (isset($transient->response[plugin_basename($plugin_data['plugin_file'])])) {
					// Remove available update for Pro plugin. Which license state stopped it
					// is what the row has to say, so it travels with the offer.
					$reason = WPeMatico_Update_Reasons::license_reason($plugin_name);
					$transient->response[plugin_basename($plugin_data['plugin_file'])]->unavailability_reason = ($reason) ? $reason : WPeMatico_Update_Reasons::LICENSE_REQUIRED;
					$transient->response[plugin_basename($plugin_data['plugin_file'])]->wpematico_plugin_key  = $plugin_name;
					$transient->response[plugin_basename($plugin_data['plugin_file'])]->package = '';
				}
			}

			if (isset($transient->response[plugin_basename($plugin_data['plugin_file'])]->unavailability_reason)) {
				add_action('in_plugin_update_message-' . plugin_basename($plugin_data['plugin_file']), array(__CLASS__, 'wpematico_in_plugin_update_message'), 20,2);
			}
		}

		$plugins = self::installed_plugins();
		foreach ($plugins as $plugin_name => $plugin_data) {
			
			if(!self::get_wpematico_ad_data($plugin_data))
				continue;

			
			if (isset($transient->response[plugin_basename(WPEMATICO_ROOTFILE)]) && !empty($transient->response[$plugin_name]->package)) {
				// Remove available update for the Current plugin
				$transient->response[$plugin_name]->unavailability_reason = WPeMatico_Update_Reasons::CORE_UPDATE_PENDING;
				$transient->response[$plugin_name]->wpematico_core_version = self::pending_core_version($transient);
				$transient->response[$plugin_name]->package = '';
			}
			
			if (isset($transient->response[$plugin_name]->unavailability_reason)) {
				if(!has_action('in_plugin_update_message-' . $plugin_name, array(__CLASS__, 'wpematico_in_plugin_update_message')))
					add_action('in_plugin_update_message-' . $plugin_name, array(__CLASS__, 'wpematico_in_plugin_update_message'), 30,2);
			}
		}

		self::carry_reason_to_update_screen($transient);

		return self::core_update_first($transient);
    }

	/**
	 * Puts the core plugin's offer at the head of the list.
	 *
	 * The unattended updater walks the offers in this order and does not re-read them,
	 * so the core plugin going first is what lets an add-on update in the same pass
	 * instead of waiting for the next one.
	 *
	 * @param  object $transient
	 * @return object
	 */
	protected static function core_update_first($transient) {
		$core = plugin_basename(WPEMATICO_ROOTFILE);

		if (!is_object($transient) || empty($transient->response) || !isset($transient->response[$core])
				|| $core === key($transient->response)) {
			return $transient;
		}

		$offer = $transient->response[$core];
		unset($transient->response[$core]);
		$transient->response = array_merge(array($core => $offer), $transient->response);

		return $transient;
	}

	/**
	 * Whether a plugin row belongs to a WPeMatico add-on.
	 *
	 * The name is what identifies them: every add-on is called "WPeMatico something",
	 * the core plugin is called exactly "WPeMatico", and the store address alone leaves
	 * out the add-ons published under a product slug of their own.
	 *
	 * @param  array $plugin_data Plugin headers.
	 * @return bool
	 */
	public static function get_wpematico_ad_data($plugin_data){
		if (empty($plugin_data['Name']) || strpos($plugin_data['Name'], 'WPeMatico ') !== 0) {
			return false;
		}

		$uri = strtolower((string) $plugin_data['PluginURI']);

		return (false !== strpos($uri, 'wpematico') || false !== strpos($uri, 'etruel.com'));
	}

	/**
	 * can_update
	 * 
	 * Determines if the plugin can be updated based on the current update transients.
	 * @param string $plugin_file Plugin base name
	 * @param object|null $transient Optional. The update transient data. If not provided, it defaults to fetching the 'update_plugins' site transient.
	 * @return bool True if the plugin can be updated, false otherwise.
	 */
    public static function can_update($plugin_file  , $transient = null) {
		if ( is_null( $transient ) ) {
			$transient = get_site_transient( 'update_plugins' );
		}

		if ( ! is_object( $transient ) ) {
			return true;
		}

		if ( ! isset( $transient->response ) || ! isset( $transient->response[$plugin_file] ) ) {
			return true;
		}

		return ( ! empty( $transient->response[$plugin_file]->package ) );
	}

	/**
	 * Add additional text to notice if download is not available and account is connected.
	 *
	 * @param  array  $plugin_data An array of plugin metadata.
	 * @param  object $response    An array of metadata about the available plugin update.
	 * @return void
	 */
	 public static function wpematico_in_plugin_update_message ($plugin_data, $response) {
		if (current_user_can('update_plugins')) {
			if (empty($response->package) && isset($response->unavailability_reason)) {
				$message = self::get_update_message($plugin_data['Name'], $response->unavailability_reason, null, array(
					'plugin_key'   => isset($response->wpematico_plugin_key) ? $response->wpematico_plugin_key : '',
					'core_version' => isset($response->wpematico_core_version) ? $response->wpematico_core_version : self::pending_core_version(),
				));
				echo ' <strong>' . wp_kses_post($message) . '</strong>';
			}
		}
	}

	/**
	 * The version the pending core update would install, if there is one.
	 *
	 * @param  object|null $transient Optional. The update transient data.
	 * @return string
	 */
	public static function pending_core_version($transient = null) {
		if (is_null($transient)) {
			$transient = get_site_transient('update_plugins');
		}

		$core = plugin_basename(WPEMATICO_ROOTFILE);

		return (is_object($transient) && !empty($transient->response[$core]->new_version))
			? $transient->response[$core]->new_version
			: '';
	}


	/**
	 * Dashboard > Updates fires no per row hook: the one text it prints for each plugin
	 * is the upgrade notice, so the reason is carried there as plain text.
	 *
	 * @param  object $transient
	 * @return void
	 */
	protected static function carry_reason_to_update_screen($transient) {
		if (!is_object($transient) || empty($transient->response)) {
			return;
		}

		$plugins = self::installed_plugins();
		foreach ($transient->response as $file => $offer) {
			if (!is_object($offer) || empty($offer->unavailability_reason) || !empty($offer->package)) {
				continue;
			}

			$offer->upgrade_notice = WPeMatico_Update_Reasons::plain($offer->unavailability_reason, array(
				'plugin_name'  => isset($plugins[$file]['Name']) ? $plugins[$file]['Name'] : '',
				'plugin_key'   => isset($offer->wpematico_plugin_key) ? $offer->wpematico_plugin_key : '',
				'core_version' => isset($offer->wpematico_core_version) ? $offer->wpematico_core_version : self::pending_core_version($transient),
			));
		}
	}

	/**
	 * Disables the checkbox of every blocked row on Dashboard > Updates.
	 *
	 * That screen renders a checkbox for anything with an update, and the only row it
	 * leaves out is one its PHP version forbids, so the state has to be set on the page.
	 *
	 * @return void
	 */
	public static function mark_blocked_rows() {
		$blocked = array();
		foreach (self::blocked_updates() as $file => $offer) {
			$blocked[] = $file;
		}

		if (empty($blocked)) {
			return;
		}
		?>
		<script>
		( function () {
			var blocked = <?php echo wp_json_encode($blocked); ?>;
			blocked.forEach( function ( plugin ) {
				var box = document.querySelector( '#update-plugins-table input[name="checked[]"][value="' + plugin.replace( /"/g, '\\"' ) + '"]' );
				if ( ! box ) {
					return;
				}
				box.checked  = false;
				box.disabled = true;
				box.closest( 'tr' ).classList.add( 'wpematico-update-blocked' );
			} );
		} )();
		</script>
		<style>.wpematico-update-blocked .plugin-title p { opacity: .7; }</style>
		<?php
	}

	/**
	 * The offers this plugin is holding back, keyed by plugin file.
	 *
	 * @param  object|null $transient
	 * @return array
	 */
	public static function blocked_updates($transient = null) {
		if (is_null($transient)) {
			$transient = get_site_transient('update_plugins');
		}

		$blocked = array();
		if (!is_object($transient) || empty($transient->response)) {
			return $blocked;
		}

		foreach ($transient->response as $file => $offer) {
			if (is_object($offer) && !empty($offer->unavailability_reason) && empty($offer->package)) {
				$blocked[$file] = $offer;
			}
		}

		return $blocked;
	}

	/**
	 * The reason one installed add-on is not updating, ready to print.
	 *
	 * Answers the pending update first and the unattended wait second, so a screen can
	 * ask one question and get the situation that applies.
	 *
	 * @param  string $plugin_file
	 * @return array  reason, where it came from, and context; all empty when there is
	 *                nothing to say.
	 */
	public static function reason_for_plugin($plugin_file) {
		$blocked = self::blocked_updates();
		if (isset($blocked[$plugin_file])) {
			$offer = $blocked[$plugin_file];

			return array(
				'reason'  => $offer->unavailability_reason,
				'source'  => 'offer',
				'context' => array(
					'plugin_key'   => isset($offer->wpematico_plugin_key) ? $offer->wpematico_plugin_key : '',
					'core_version' => isset($offer->wpematico_core_version) ? $offer->wpematico_core_version : self::pending_core_version(),
				),
			);
		}

		$waiting = self::waiting_updates();
		if (isset($waiting[$plugin_file])) {
			return array(
				'reason'  => WPeMatico_Update_Reasons::WAITING_CORE,
				'source'  => 'waiting',
				'context' => array(
					'core_version' => $waiting[$plugin_file]['core_version'],
					'since'		   => $waiting[$plugin_file]['since'],
				),
			);
		}

		return array('reason' => '', 'source' => '', 'context' => array());
	}

	/**
	 * get_plugins(), with its file loaded: the unattended pass and the cron request are
	 * not admin requests.
	 *
	 * @return array
	 */
	protected static function installed_plugins() {
		if (!function_exists('get_plugins')) {
			require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		}

		return get_plugins();
	}

	/**
	 * Holds back the unattended update of an add-on while the core plugin has one pending.
	 *
	 * Returning false here is what keeps it silent: leaving the package empty instead
	 * would let the update run, fail, and mail the administrator once per pass.
	 *
	 * @param  bool|null $update Whether to update.
	 * @param  object    $item   The update offer.
	 * @return bool|null
	 */
	public static function maybe_block_auto_update($update, $item) {
		if (!$update || empty($item->plugin) || $item->plugin === plugin_basename(WPEMATICO_ROOTFILE)) {
			return $update;
		}

		$plugins = self::installed_plugins();
		if (!isset($plugins[$item->plugin]) || !self::get_wpematico_ad_data($plugins[$item->plugin])) {
			return $update;
		}

		$pending = self::pending_core_version_on_disk();
		if (!$pending) {
			self::forget_waiting($item->plugin);

			return $update;
		}

		self::remember_waiting($item->plugin, $pending);

		return false;
	}

	/**
	 * The core version an update is waiting for, judged by the plugin file on disk.
	 *
	 * The offers the unattended updater walks are a snapshot taken before any of them
	 * ran, so a core plugin updated earlier in the same pass still reads as pending
	 * there. What it installed is on disk.
	 *
	 * @param  object|null $transient
	 * @return string  Empty when the core plugin is already up to date.
	 */
	public static function pending_core_version_on_disk($transient = null) {
		$offered = self::pending_core_version($transient);
		if (!$offered) {
			return '';
		}

		if (!function_exists('get_plugin_data')) {
			require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		}
		$installed = get_plugin_data(WPEMATICO_ROOTFILE, false, false);
		$installed = (!empty($installed['Version'])) ? $installed['Version'] : WPEMATICO_VERSION;

		return version_compare($installed, $offered, '<') ? $offered : '';
	}

	/**
	 * Add-ons whose unattended update is being held back, keyed by plugin file.
	 *
	 * @return array
	 */
	public static function waiting_updates() {
		$waiting = get_option(self::WAITING_OPTION, array());

		return is_array($waiting) ? $waiting : array();
	}

	/**
	 * Records that an add-on is waiting, so the admin screens can say since when.
	 *
	 * Written only when the situation changes: this runs on every unattended pass.
	 *
	 * @param  string $plugin_file
	 * @param  string $core_version
	 * @return void
	 */
	protected static function remember_waiting($plugin_file, $core_version) {
		$waiting = self::waiting_updates();
		if (isset($waiting[$plugin_file]) && $waiting[$plugin_file]['core_version'] === $core_version) {
			return;
		}

		$waiting[$plugin_file] = array('core_version' => $core_version, 'since' => time());
		update_option(self::WAITING_OPTION, $waiting, false);
	}

	/**
	 * Drops the record of an add-on that is no longer waiting.
	 *
	 * @param  string $plugin_file
	 * @return void
	 */
	protected static function forget_waiting($plugin_file) {
		$waiting = self::waiting_updates();
		if (!isset($waiting[$plugin_file])) {
			return;
		}

		unset($waiting[$plugin_file]);
		update_option(self::WAITING_OPTION, $waiting, false);
	}

	/**
	 * Turns the core plugin's automatic updates on whenever an add-on's are turned on.
	 *
	 * Every screen, bulk action and command ends up writing this option, so this is the
	 * one place where the pair can be kept together. Only this plugin's own entry is
	 * ever added.
	 *
	 * @param  array $value     The list about to be saved.
	 * @param  array $old_value The list being replaced.
	 * @return array
	 */
	public static function keep_core_auto_updated($value, $old_value) {
		if (!is_array($value)) {
			return $value;
		}

		$core = plugin_basename(WPEMATICO_ROOTFILE);
		if (in_array($core, $value, true)) {
			return $value;
		}

		$plugins = self::installed_plugins();
		$added	 = array();
		foreach ($value as $file) {
			if (isset($plugins[$file]) && self::get_wpematico_ad_data($plugins[$file])
					&& (!is_array($old_value) || !in_array($file, $old_value, true))) {
				$added[] = isset($plugins[$file]['Name']) ? $plugins[$file]['Name'] : $file;
			}
		}

		if (empty($added)) {
			return $value;
		}

		$value[] = $core;
		set_transient(self::CORE_AUTOUPDATE_NOTICE, reset($added), HOUR_IN_SECONDS);

		return $value;
	}

	/**
	 * Says, once, that the core plugin's automatic updates were turned on as well.
	 *
	 * @return void
	 */
	public static function core_auto_update_notice() {
		if (!current_user_can('update_plugins')) {
			return;
		}

		$addon = get_transient(self::CORE_AUTOUPDATE_NOTICE);
		if (empty($addon)) {
			return;
		}
		delete_transient(self::CORE_AUTOUPDATE_NOTICE);

		$message = WPeMatico_Update_Reasons::message(WPeMatico_Update_Reasons::CORE_AUTOUPDATE_ON, array('plugin_name' => $addon));
		if (empty($message['text'])) {
			return;
		}

		echo '<div class="notice notice-info is-dismissible"><p>' . esc_html($message['text']) . '</p></div>';
	}

	/**
	 * The message of one unavailability reason, as WPeMatico_Update_Reasons words it.
	 *
	 * @param string $plugin_name Add-on name.
	 * @param string $reason      Unavailability reason ID.
	 * @param mixed  $default     Text to return when the reason has no message.
	 * @param array  $context     Extra values the message needs, see WPeMatico_Update_Reasons::message().
	 * @return string
	 */
	public static function get_update_message($plugin_name, $reason = '', $default = null, $context = array()) {
		if ( is_null( $default ) ) {
			$default = '';
		}

		$context['plugin_name'] = $plugin_name;
		$message				= WPeMatico_Update_Reasons::inline($reason, $context);

		return ($message) ? $message : $default;
	}
}

WPeMatico_Update::hooks();