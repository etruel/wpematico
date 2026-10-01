<?php

defined('ABSPATH') || exit;

/**
 * Every reason an add-on does not update, with the one action that resolves each.
 *
 * One reason per situation and one sentence per reason: the screens ask this class what to
 * say, so the same situation reads the same everywhere and no two situations share a text.
 *
 * @since 2.9
 */
class WPeMatico_Update_Reasons {

	/** The core plugin has an update pending, and an add-on never updates ahead of it. */
	const CORE_UPDATE_PENDING = 'update_free';

	/** The installed core is older than the installed add-on needs. */
	const CORE_TOO_OLD = 'core_required';

	/** No license key saved for this add-on. */
	const LICENSE_MISSING = 'license_missing';

	/** The license expired. */
	const LICENSE_EXPIRED = 'license_expired';

	/** The license is valid but not activated on this site. */
	const LICENSE_SITE_INACTIVE = 'license_site_inactive';

	/** The store did not accept the key. */
	const LICENSE_INVALID = 'license_invalid';

	/** Any other license state that stops updates. */
	const LICENSE_REQUIRED = 'required_license';

	/** An automatic update was skipped, waiting for the core plugin. */
	const WAITING_CORE = 'waiting_core';

	/** Automatic updates cannot run on this site at all. */
	const AUTOUPDATES_OFF = 'autoupdates_unavailable';

	/** The core plugin's automatic updates were turned on along with an add-on's. */
	const CORE_AUTOUPDATE_ON = 'core_autoupdate_enabled';

	/** Add-ons update automatically but the core plugin does not. */
	const CORE_AUTOUPDATE_OFF = 'core_autoupdate_off';

	/**
	 * The license reason that applies to an add-on, or an empty string when its license
	 * lets it update.
	 *
	 * @param   string  $plugin_key  Key the add-on registers in wpematico_plugins_updater_args.
	 * @return  string
	 */
	public static function license_reason($plugin_key) {
		if (!class_exists('wpematico_licenses_handlers')) {
			return '';
		}

		$status = wpematico_licenses_handlers::get_license_status($plugin_key);
		if ('valid' === $status) {
			return '';
		}

		if (empty(wpematico_licenses_handlers::get_key($plugin_key))) {
			return self::LICENSE_MISSING;
		}

		switch ($status) {
			case 'expired':
				return self::LICENSE_EXPIRED;
			case 'inactive':
			case 'site_inactive':
			case 'deactivated':
				return self::LICENSE_SITE_INACTIVE;
			case 'invalid':
			case 'item_name_mismatch':
				return self::LICENSE_INVALID;
		}

		return self::LICENSE_REQUIRED;
	}

	/**
	 * The message of one reason: the sentence, and the single action that resolves it.
	 *
	 * @param   string  $reason   One of the constants above.
	 * @param   array   $context  plugin_name, plugin_key, core_version, required_version,
	 *                            expires, since, count.
	 * @return  array   text, url and label; empty strings when the reason has no action.
	 */
	public static function message($reason, $context = array()) {
		$context = wp_parse_args($context, array(
			'plugin_name'	   => '',
			'plugin_key'	   => '',
			'core_version'	   => '',
			'required_version' => '',
			'expires'		   => '',
			'since'			   => 0,
			'count'			   => 0,
		));

		$name	= $context['plugin_name'];
		$text	= '';
		$url	= '';
		$label	= '';

		switch ($reason) {
			case self::CORE_UPDATE_PENDING:
				$text  = ($context['core_version'])
					/* translators: 1: Add-on name, 2: WPeMatico version. */
					? sprintf(__('%1$s updates right after WPeMatico: install WPeMatico %2$s first.', 'wpematico'), $name, $context['core_version'])
					/* translators: %s: Add-on name. */
					: sprintf(__('%s updates right after WPeMatico: install the pending WPeMatico update first.', 'wpematico'), $name);
				$url   = self::core_update_url();
				$label = __('Update WPeMatico now', 'wpematico');
				break;

			case self::CORE_TOO_OLD:
				/* translators: 1: Add-on name, 2: WPeMatico version. */
				$text  = sprintf(__('%1$s needs WPeMatico %2$s or newer. Its features stay off until WPeMatico is updated.', 'wpematico'), $name, $context['required_version']);
				$url   = self::core_update_url();
				$label = __('Update WPeMatico now', 'wpematico');
				break;

			case self::LICENSE_MISSING:
				/* translators: %s: Add-on name. */
				$text  = sprintf(__('%s updates once its license key is saved on this site.', 'wpematico'), $name);
				$url   = self::licenses_url();
				$label = __('Add your license key', 'wpematico');
				break;

			case self::LICENSE_EXPIRED:
				if (empty($context['expires']) && $context['plugin_key'] && class_exists('wpematico_licenses_handlers')) {
					$expires			= wpematico_licenses_handlers::get_license_expires($context['plugin_key']);
					$context['expires'] = ('lifetime' === $expires) ? '' : $expires;
				}
				if ($context['expires']) {
					// The store stores it as Y-m-d; the reader gets the site's own date
					// format. Midday, so the site's time zone cannot move it a day.
					$stamp				= strtotime($context['expires'] . ' 12:00:00');
					$context['expires'] = $stamp ? wp_date(get_option('date_format'), $stamp) : $context['expires'];
				}
				$text  = ($context['expires'])
					/* translators: 1: Add-on name, 2: Date the license expired. */
					? sprintf(__('The license of %1$s expired on %2$s. Renew it to keep receiving updates and support.', 'wpematico'), $name, $context['expires'])
					/* translators: %s: Add-on name. */
					: sprintf(__('The license of %s expired. Renew it to keep receiving updates and support.', 'wpematico'), $name);
				$url   = self::renewal_url($context['plugin_key']);
				$label = __('Renew your license', 'wpematico');
				break;

			case self::LICENSE_SITE_INACTIVE:
				/* translators: %s: Add-on name. */
				$text  = sprintf(__('The license of %s is not activated on this site, so it receives no updates here.', 'wpematico'), $name);
				$url   = self::licenses_url();
				$label = __('Activate this site', 'wpematico');
				break;

			case self::LICENSE_INVALID:
				/* translators: %s: Add-on name. */
				$text  = sprintf(__('The store did not accept the license key of %s. Check the key to resume updates.', 'wpematico'), $name);
				$url   = self::licenses_url();
				$label = __('Check your license key', 'wpematico');
				break;

			case self::LICENSE_REQUIRED:
				/* translators: %s: Add-on name. */
				$text  = sprintf(__('%s needs a valid license to receive updates.', 'wpematico'), $name);
				$url   = self::licenses_url();
				$label = __('Review your license', 'wpematico');
				break;

			case self::WAITING_CORE:
				$text  = ($context['since'])
					/* translators: 1: Add-on name, 2: Date the wait started. */
					? sprintf(__('%1$s updates automatically as soon as WPeMatico is updated. It has been waiting since %2$s.', 'wpematico'), $name, wp_date(get_option('date_format'), (int) $context['since']))
					/* translators: %s: Add-on name. */
					: sprintf(__('%s updates automatically as soon as WPeMatico is updated.', 'wpematico'), $name);
				$url   = self::core_update_url();
				$label = __('Update WPeMatico now', 'wpematico');
				break;

			case self::AUTOUPDATES_OFF:
				/* translators: %s: Add-on name. */
				$text = sprintf(__('Automatic updates are turned off on this site, so %s keeps its current version until it is updated by hand.', 'wpematico'), $name);
				break;

			case self::CORE_AUTOUPDATE_ON:
				/* translators: %s: Add-on name. */
				$text = sprintf(__('WPeMatico now updates automatically as well, so %s never updates ahead of the plugin it extends.', 'wpematico'), $name);
				break;

			case self::CORE_AUTOUPDATE_OFF:
				$text  = sprintf(
					/* translators: %d: Number of add-ons. */
					_n(
						'WPeMatico does not update automatically, so the %d add-on that does will stay on its current version until you update WPeMatico.',
						'WPeMatico does not update automatically, so the %d add-ons that do will stay on their current version until you update WPeMatico.',
						(int) $context['count'],
						'wpematico'
					),
					(int) $context['count']
				);
				$url   = self::core_update_url();
				$label = __('Update WPeMatico now', 'wpematico');
				break;
		}

		return array('text' => $text, 'url' => $url, 'label' => $label);
	}

	/**
	 * The message of one reason as a sentence plus its link, for a plugin row.
	 *
	 * @param   string  $reason
	 * @param   array   $context
	 * @return  string  HTML, empty when the reason has no message.
	 */
	public static function inline($reason, $context = array()) {
		$message = self::message($reason, $context);
		if (empty($message['text'])) {
			return '';
		}

		$inline = esc_html($message['text']);
		if ($message['url'] && $message['label']) {
			$inline .= ' <a href="' . esc_url($message['url']) . '">' . esc_html($message['label']) . '</a>';
		}

		return $inline;
	}

	/**
	 * The same message with no markup, for the screens that strip it.
	 *
	 * @param   string  $reason
	 * @param   array   $context
	 * @return  string
	 */
	public static function plain($reason, $context = array()) {
		$message = self::message($reason, $context);

		return trim($message['text']);
	}

	/**
	 * Link that updates the core plugin, or the plugins screen for a user who cannot.
	 *
	 * @return  string
	 */
	protected static function core_update_url() {
		$file = plugin_basename(WPEMATICO_ROOTFILE);

		if (!current_user_can('update_plugins')) {
			return self_admin_url('plugins.php');
		}

		return wp_nonce_url(
			self_admin_url('update.php?action=upgrade-plugin&plugin=' . urlencode($file)),
			'upgrade-plugin_' . $file
		);
	}

	/**
	 * The Licenses tab of the settings screen.
	 *
	 * @return  string
	 */
	protected static function licenses_url() {
		return add_query_arg(array('page' => 'wpematico_settings', 'tab' => 'pro_licenses'), self_admin_url('admin.php'));
	}

	/**
	 * Checkout with this add-on's key and product already filled in, so renewing is one
	 * click from the notice that asks for it.
	 *
	 * @param   string  $plugin_key
	 * @return  string
	 */
	protected static function renewal_url($plugin_key) {
		$args = array();

		if ($plugin_key && class_exists('wpematico_licenses_handlers')) {
			$key = wpematico_licenses_handlers::get_key($plugin_key);
			if ($key) {
				$args['edd_license_key'] = $key;
			}

			$updater_args = apply_filters('wpematico_plugins_updater_args', array());
			if (!empty($updater_args[$plugin_key]['api_data']['item_id'])) {
				$args['download_id'] = (int) $updater_args[$plugin_key]['api_data']['item_id'];
			}
		}

		return $args ? add_query_arg($args, 'https://etruel.com/checkout/') : 'https://etruel.com/my-account/purchase-history/';
	}
}
