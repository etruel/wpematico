<?php

defined( 'ABSPATH' ) || exit;

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

	/**
	 * The Constructor.
	 *
	 * @return void
	 */
	public static function hooks() {
		add_filter( 'site_transient_update_plugins', array(__CLASS__, 'maybe_disable_update'), 91, 1 );
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
			
			if (wpematico_licenses_handlers::get_license_status($plugin_name) != 'valid') {
				if (isset($transient->response[plugin_basename($plugin_data['plugin_file'])])) {
					// Remove available update for Pro plugin
					$transient->response[plugin_basename($plugin_data['plugin_file'])]->unavailability_reason = 'required_license';
					$transient->response[plugin_basename($plugin_data['plugin_file'])]->package = '';
				}
			}

			if (isset($transient->response[plugin_basename($plugin_data['plugin_file'])]->unavailability_reason)) {
				add_action('in_plugin_update_message-' . plugin_basename($plugin_data['plugin_file']), array(__CLASS__, 'wpematico_in_plugin_update_message'), 20,2);
			}
		}

		$plugins = get_plugins();
		foreach ($plugins as $plugin_name => $plugin_data) {
			
			if(!self::get_wpematico_ad_data($plugin_data))
				continue;

			
			if (isset($transient->response[plugin_basename(WPEMATICO_ROOTFILE)]) && !empty($transient->response[$plugin_name]->package)) {
				// Remove available update for the Current plugin
				$transient->response[$plugin_name]->unavailability_reason = 'update_free';
				$transient->response[$plugin_name]->package = '';
			}
			
			if (isset($transient->response[$plugin_name]->unavailability_reason)) {
				if(!has_action('in_plugin_update_message-' . $plugin_name, array(__CLASS__, 'wpematico_in_plugin_update_message')))
					add_action('in_plugin_update_message-' . $plugin_name, array(__CLASS__, 'wpematico_in_plugin_update_message'), 30,2);
			}
		}

        return $transient;
    }

	public static function get_wpematico_ad_data($plugin_data){
		if (strpos($plugin_data['Name'], 'WPeMatico ') === false || strpos($plugin_data['PluginURI'], 'wpematico') === false) {
			return false;
		}

		return true;
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
				$message = self::get_update_message($plugin_data['Name'], $response->unavailability_reason );
				echo ' <strong>' . wp_kses_post($message) . '</strong>';
			}
		}
	}

	/**
	 * Get unavailability reason message.
	 *
	 * @param string $reason  Unavailability reason ID, like 'not_connected'.
	 * @param mixed  $default Default text to return when specified ID has no message attached to it.
	 * @return string
	 */
	public static function get_update_message($plugin_name, $reason = '', $default = null) {
		if ( is_null( $default ) ) {
			$default = '';
		}

		$unavailability_reasons = [
			/* translators: %s Plugin Name */
			'update_free'    => sprintf(esc_html__( "Please update the free version before updating %s. ", 'wpematico' ), $plugin_name),
			/* translators: %s Plugin Name */
			'core_required'  => sprintf(esc_html__( "Please the free version is required to run the %s. ", 'wpematico' ), $plugin_name),
			/* translators: %s Plugin Name */
			'required_license'  => sprintf(esc_html__( "Please update your license to get the lastest version of %s. ", 'wpematico' ), $plugin_name),
		];

		if ( isset( $unavailability_reasons[ $reason ] ) ) {
			return $unavailability_reasons[ $reason ];
		}

		return $default;
	}
}

WPeMatico_Update::hooks();