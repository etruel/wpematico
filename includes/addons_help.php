<?php
/**
 * Add-Ons screen help.
 *
 * Same shape and plumbing the rest of the plugin uses (see tools_help.php and
 * feed_list_help.php): one array of sections, each entry with a 'title' and a 'tip'
 * (plus an optional 'plustip'), feeding the WordPress Help tabs of this screen.
 *
 * @package WPeMatico
 */

// don't load directly
if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

/**
 * Help sections for the Add-Ons screen.
 *
 * @since 2.9
 * @param string $dev 'tips' returns the flat [key => tip] array for tooltips.
 * @return array
 */
function wpematico_help_addons($dev = '') {
	$help = array(
		__('Add-Ons', 'wpematico')			 => array(
			'addons_intro'	  => array(
				'title'	  => __('What this screen lists.', 'wpematico'),
				'tip'	  => __('Two things at once: the add-ons installed on this site, and the ones published at the store that are not installed yet.', 'wpematico') . '<br />' .
				__('An installed add-on shows its version and the actions it accepts; one that is only at the store shows what it costs or which membership includes it.', 'wpematico'),
				'plustip' => __('WPeMatico add-ons are hidden from the main Plugins list by default. Settings &rsaquo; Writing &rsaquo; "See local Addons in plugin list" puts them back among the other plugins; this screen works the same either way.', 'wpematico'),
			),
			'addons_views'	  => array(
				'title'	  => __('The views, the counter and the search box.', 'wpematico'),
				'tip'	  => __('Active, Inactive and the rest count only what is installed, so the store catalogue appears under All and in search results only.', 'wpematico') . '<br />' .
				__('The number beside "WPeMatico Addons" in the menu is how many installed add-ons have an update waiting.', 'wpematico'),
				'plustip' => __('Typing in the search box hides the rows on screen as you type; pressing Enter searches the whole list, catalogue included.', 'wpematico'),
			),
			'addons_actions'  => array(
				'title'	  => __('The button on each row.', 'wpematico'),
				'tip'	  => __('Activate starts an add-on that is installed and stopped. Enable auto-updates appears once it is running and its license is valid.', 'wpematico') . '<br />' .
				__('Add license, Renew license, Activate license and Check license each point at one precise thing: hover the button and it says which.', 'wpematico') . '<br />' .
				__('Installed, with no other action, means the switch cannot be offered here: either this site runs no automatic updates at all, or your user cannot update plugins.', 'wpematico'),
			),
			'addons_bulk'	  => array(
				'title'	  => __('Bulk actions.', 'wpematico'),
				'tip'	  => __('Select rows and apply Activate, Deactivate, Delete or the auto-update actions. They are WordPress\' own actions, applied exactly as they are on the Plugins screen.', 'wpematico'),
				'plustip' => __('An add-on can only be deleted while it is deactivated, and WPeMatico itself cannot be deactivated while an add-on depends on it.', 'wpematico'),
			),
		),
		__('Automatic updates', 'wpematico') => array(
			'addons_auto_what'	  => array(
				'title'	  => __('What to expect when you turn them on.', 'wpematico'),
				'tip'	  => __('Nothing happens at that moment. WordPress checks for updates about twice a day and installs them on its own schedule, so a new version can land several hours after it is published.', 'wpematico') . '<br />' .
				__('Automatic updates are run by WP-Cron. On a site where cron does not fire, they never run — Tools &rsaquo; System Status reports that.', 'wpematico'),
				'plustip' => __('WordPress emails the site administrator after it installs an update, and when one fails.', 'wpematico'),
			),
			'addons_auto_core'	  => array(
				'title'	  => __('WPeMatico updates first, always.', 'wpematico'),
				'tip'	  => __('An add-on extends the core plugin, so it is never installed ahead of it. While WPeMatico has an update pending, add-on updates wait — by hand and automatically alike.', 'wpematico') . '<br />' .
				__('That is why turning automatic updates on for an add-on turns them on for WPeMatico as well: with both on, the add-on installs right after the core plugin, unattended.', 'wpematico'),
				'plustip' => __('You can turn WPeMatico\'s back off, and nothing stops you — but then its add-ons stay on the version they have until you update WPeMatico by hand. This screen says so when that is the case.', 'wpematico'),
			),
			'addons_auto_waiting' => array(
				'title'	  => __('An add-on that is waiting says so.', 'wpematico'),
				'tip'	  => __('When an unattended update is held back, the row says what it is waiting for and since when, instead of failing quietly.', 'wpematico') . '<br />' .
				__('Updating WPeMatico clears it: the next check installs the add-on with no further action.', 'wpematico'),
			),
			'addons_auto_off'	  => array(
				'title'	  => __('Sites where they cannot run.', 'wpematico'),
				'tip'	  => __('Some hosts manage updates themselves, and a site under version control, or one with automatic updates disabled by a constant, does not run them either.', 'wpematico') . '<br />' .
				__('The toggle is not offered there. The row says the add-on keeps its version until it is updated by hand.', 'wpematico'),
			),
		),
		__('Licenses', 'wpematico')			 => array(
			'addons_license_need'  => array(
				'title'	  => __('Updates need a valid license.', 'wpematico'),
				'tip'	  => __('A paid add-on receives updates while its license key is saved on this site, activated on it, and not expired. Keys are entered in WPeMatico &rsaquo; Settings &rsaquo; Licenses.', 'wpematico'),
				'plustip' => __('An add-on with no valid license keeps working. What it stops receiving is updates, including the security ones.', 'wpematico'),
			),
			'addons_license_state' => array(
				'title'	  => __('Each state, and what resolves it.', 'wpematico'),
				'tip'	  => __('No key saved: enter it on the Licenses screen. Not activated on this site: activate it there, which is also where a site you no longer use can be released.', 'wpematico') . '<br />' .
				__('Expired: renew it, and the button takes you to the checkout with your key and the product already filled in. Key not accepted: check the key you pasted.', 'wpematico'),
			),
			'addons_license_auto'  => array(
				'title'	  => __('A license that expires stops the automatic updates too.', 'wpematico'),
				'tip'	  => __('The toggle stays as it is, and no update arrives, because the store serves none for an expired license. The row and the plugin update notice say which license it is and what is missing.', 'wpematico'),
			),
		),
		__('Where updates show', 'wpematico') => array(
			'addons_where'	 => array(
				'title'	  => __('Three places show the same thing.', 'wpematico'),
				'tip'	  => __('This screen, under each add-on; Dashboard &rsaquo; Updates, with all the plugins of the site; and the Plugins list, when the Writing setting above is on.', 'wpematico') . '<br />' .
				__('On Dashboard &rsaquo; Updates an add-on that is waiting cannot be selected, so a bulk update does not attempt what it cannot install.', 'wpematico'),
			),
			'addons_manual'	 => array(
				'title'	  => __('Updating by hand.', 'wpematico'),
				'tip'	  => __('Update WPeMatico first, then the add-ons. The links offered on each screen already follow that order.', 'wpematico'),
				'plustip' => __('An add-on newer than the core plugin turns its own features off and says which version of WPeMatico it needs, rather than run against a core that cannot support it.', 'wpematico'),
			),
			'addons_missing' => array(
				'title'	  => __('No update showing when you expect one.', 'wpematico'),
				'tip'	  => __('WordPress caches the answer it gets about updates for several hours. "Check again" at the top of Dashboard &rsaquo; Updates asks the store and this site\'s sources right away.', 'wpematico'),
			),
		),
	);

	$help = apply_filters('wpematico_help_addons_before', $help);

	if ($dev == 'tips') {
		$helptip = array();
		foreach ($help as $section) {
			foreach ($section as $section_key => $sdata) {
				$helptip[$section_key] = htmlentities($sdata['tip']);
			}
		}
		return apply_filters('wpematico_helptip_addons', $helptip);
	}

	return apply_filters('wpematico_help_addons', $help);
}

/**
 * Adds the Help tabs to the Add-Ons screen.
 *
 * On current_screen and with the screen being rendered: an id built by hand goes stale
 * the moment the page moves in the menu.
 *
 * @param WP_Screen $screen
 * @return void
 */
add_action('current_screen', 'wpematico_addons_help_tabs');
function wpematico_addons_help_tabs($screen = null) {
	if (!($screen instanceof WP_Screen)) {
		$screen = get_current_screen();
	}
	if (!$screen || !isset($_GET['page']) || 'wpemaddons' !== $_GET['page']) {
		return;
	}

	$index = 0;
	foreach (wpematico_help_addons() as $key => $section) {
		$tabcontent = '';
		foreach ($section as $sdata) {
			$tabcontent .= '<p><strong>' . $sdata['title'] . '</strong><br />' . $sdata['tip'] . '</p>';
			$tabcontent .= (isset($sdata['plustip'])) ? '<p style="margin-top: 2px;margin-left: 7px;">' . $sdata['plustip'] . '</p>' : '';
		}
		$screen->add_help_tab(array(
			// Section names are translated, so they cannot be the tab id.
			'id'	  => 'wpematico-addons-help-' . (++$index),
			'title'	  => $key,
			'content' => $tabcontent,
		));
	}

	$screen->set_help_sidebar(
			'<p><strong>' . __('For more information:', 'wpematico') . '</strong></p>' .
			'<p><a href="https://etruel.com/downloads/category/wpematico/" target="_blank">' . __('Add-ons at the store', 'wpematico') . '</a></p>' .
			'<p><a href="https://wpematico.com/faq/" target="_blank">' . __('Frequently asked questions', 'wpematico') . '</a></p>'
	);
}
