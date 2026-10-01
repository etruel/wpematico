<?php
/**
 * WPeMatico plugin for WordPress
 * Campaign types
 *
 * One file per campaign type, named after its slug, each self-contained and hooked
 * to the public API only — the same hooks an addon would use. If a type cannot be
 * written with those hooks, the hook API has a hole worth filling rather than a
 * shortcut worth taking.
 *
 * @package   wpematico
 */
// don't load directly
if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

if (!class_exists('WPeMatico_Campaign_Types')) :

	class WPeMatico_Campaign_Types {

		/**
		 * Types that ship with core, and the option each one is switched by. A type
		 * that is off is not loaded at all: no hooks, no metabox, no cost.
		 */
		public static function registry() {
			return apply_filters('wpematico_core_campaign_types', array(
				'vimeo' => 'enable_vimeo',
			));
		}

		/**
		 * Loaded from the plugin bootstrap in every context, admin and cron alike:
		 * a campaign type has to exist when the campaign runs, not only when it is
		 * edited. Whatever is admin-only lives behind is_admin() inside each file.
		 */
		public static function load() {
			$cfg = get_option(WPeMatico::OPTION_KEY);

			foreach (self::registry() as $slug => $option) {
				// The option is read raw, before wpematico_check_options: this runs while
				// the plugin is still loading and the normalizer is not registered yet.
				if ('' !== (string) $option && empty($cfg[$option])) {
					continue;
				}
				$file = WPEMATICO_PLUGIN_DIR . 'includes/campaign-types/' . sanitize_file_name($slug) . '.php';
				if (is_file($file)) {
					require_once $file;
				}
			}
		}
	}

endif;
