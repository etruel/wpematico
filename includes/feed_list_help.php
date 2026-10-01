<?php
/**
 * Feed List help.
 *
 * Same shape and plumbing the rest of the plugin uses (see tools_help.php,
 * feed_viewer_help.php and migration/help.php): one array of sections, each entry
 * with a 'title' and a 'tip' (plus an optional 'plustip'). The array feeds the Help
 * tab of the Tools screen and the tipTip tooltips on the column headers at once.
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
 * Help sections for the Feed List.
 *
 * @since 2.9
 * @param string $dev 'tips' returns the flat [key => tip] array for tooltips.
 * @return array
 */
function wpematico_help_feed_list($dev = '') {
	$help = array(
		__('Feed List', 'wpematico') => array(
			'feed_list_intro'	 => array(
				'title'	  => __('What the Feed List shows.', 'wpematico'),
				'tip'	  => __('Every feed URL configured on this site, one row per feed, no matter which campaign holds it. A campaign with five feeds appears in five rows.', 'wpematico') . '<br />' .
				__('It answers the questions the campaign list cannot: whether a URL is already being imported, which campaign owns it, and whether the same feed was added twice.', 'wpematico'),
				'plustip' => __('This is a read-only view. Everything is edited from the campaign itself.', 'wpematico'),
			),
			'feed_list_url'		 => array(
				'title'	  => __('Feed URL.', 'wpematico'),
				'tip'	  => __('The address exactly as the campaign stores it, which is what gets requested on every run.', 'wpematico') . '<br />' .
				__('Seeing the same address in two rows means two campaigns import it; that is legitimate when they publish differently, and a duplicate-post source when they do not.', 'wpematico'),
			),
			'feed_list_campaign' => array(
				'title'	  => __('Campaign.', 'wpematico'),
				'tip'	  => __('The campaign this feed belongs to. The name links to its edit screen, where the feed can be changed or removed.', 'wpematico'),
			),
			'feed_list_status'	 => array(
				'title'	  => __('Status.', 'wpematico'),
				'tip'	  => __('Whether the campaign is active, that is, whether the scheduler runs it. Feeds are not activated one by one: the state belongs to the campaign, so every row of the same campaign shows the same value.', 'wpematico') . '<br />' .
				__('An inactive campaign is never fetched automatically, but can still be run by hand from the campaign list.', 'wpematico'),
			),
			'feed_list_lastrun'	 => array(
				'title'	  => __('Last run.', 'wpematico'),
				'tip'	  => __('When the campaign last completed a run — again a campaign-wide value, not a per-feed one. "Never" means it has not run since it was created or since its log was cleared.', 'wpematico'),
				'plustip' => __('A campaign that is active but has not run in a long time usually means cron is not firing. The Tools &rsaquo; System tab checks that.', 'wpematico'),
			),
			'feed_list_actions'	 => array(
				'title'	  => __('Actions.', 'wpematico'),
				'tip'	  => __('Edit campaign opens the campaign that holds this feed.', 'wpematico') . '<br />' .
				__('Inspect in Feed Viewer requests the URL and shows the raw answer, which is the fastest way to tell a broken feed from a broken campaign.', 'wpematico') . '<br />' .
				__('Open URL loads the address in a new browser tab, as your browser sees it.', 'wpematico'),
			),
			'feed_list_filter'	 => array(
				'title'	  => __('Searching and filtering.', 'wpematico'),
				'tip'	  => __('The box works at two scopes. As you type it hides, right away, the rows of the page you are looking at — useful to spot one feed among the ones already on screen.', 'wpematico') . '<br />' .
				__('Pressing Enter or the button searches every feed of the site instead, across all pages, and the list is rebuilt with the matches: the counter, the pages and the sorting all apply to the result.', 'wpematico'),
				'plustip' => __('It matches the feed URL and the campaign name. Esc clears the instant filter, and "Clear search" undoes the global one.', 'wpematico'),
			),
			'feed_list_sorting'	 => array(
				'title'	  => __('Sorting and page size.', 'wpematico'),
				'tip'	  => __('Click any underlined column header to sort by it, and again to reverse the order. Sorting applies to the whole list, not only to the page on screen.', 'wpematico') . '<br />' .
				__('How many rows each page shows is set under Screen Options, at the top right of this screen.', 'wpematico'),
			),
		),
	);

	$help = apply_filters('wpematico_help_feed_list_before', $help);

	if ($dev == 'tips') {
		$helptip = array();
		foreach ($help as $section) {
			foreach ($section as $section_key => $sdata) {
				$helptip[$section_key] = htmlentities($sdata['tip']);
			}
		}
		return apply_filters('wpematico_helptip_feed_list', $helptip);
	}

	return apply_filters('wpematico_help_feed_list', $help);
}

/**
 * Add the Feed List sections to the Tools screen help.
 */
add_filter('wpematico_help_tools_before', 'wpematico_add_feed_list_help');
function wpematico_add_feed_list_help($helptools) {
	// Prepended, not appended: the Help tabs then come out in the same order as
	// the sections of the screen.
	return array_merge(wpematico_help_feed_list(), (array) $helptools);
}
