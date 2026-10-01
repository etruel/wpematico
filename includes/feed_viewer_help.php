<?php
/**
 * Feed Viewer help.
 *
 * Same shape and plumbing the rest of the plugin uses (see tools_help.php and
 * migration/help.php): one array of sections, each entry with a 'title' and a
 * 'tip' (plus an optional 'plustip'). The array feeds two things at once — the
 * Help tab of the Tools screen, and the tipTip tooltips on the (?) icons of the
 * Feed Viewer — so a wording change lands in both places.
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
 * Help sections for the Feed Viewer.
 *
 * @since 2.9
 * @param string $dev 'tips' returns the flat [key => tip] array for tooltips.
 * @return array
 */
function wpematico_help_feed_viewer($dev = '') {
	$help = array(
		__('Feed Viewer', 'wpematico') => array(
			'feed_viewer_intro'	   => array(
				'title'	  => __('What the Feed Viewer is for.', 'wpematico'),
				'tip'	  => __('It asks a URL for its content exactly the way a campaign does, and shows you the raw answer without creating any post.', 'wpematico') . '<br />' .
				__('Use it before adding a feed to a campaign, or when a campaign fetches nothing and you need to know whether the problem is the source, the network or the campaign settings.', 'wpematico'),
				'plustip' => __('Nothing is saved, published or cached: it only reads the URL and prints what came back.', 'wpematico'),
			),
			'feed_viewer_url'	   => array(
				'title'	  => __('Feed URL.', 'wpematico'),
				'tip'	  => __('Paste the address of the feed itself (it usually ends in /feed/, /rss, .xml or similar), not the address of the site that publishes it.', 'wpematico') . '<br />' .
				__('Only http:// and https:// addresses are accepted. If the feed needs a key or a token, include it in the URL just as the campaign would use it.', 'wpematico'),
				'plustip' => __('Arriving from the eye icon of a campaign feed, or from the Feed List, fills this field in and fetches it right away.', 'wpematico'),
			),
			'feed_viewer_get'	   => array(
				'title'	  => __('Get Feed.', 'wpematico'),
				'tip'	  => __('Requests the URL with the same parser, user agent and timeouts the campaigns use, so what you see here is what a campaign would receive.', 'wpematico') . '<br />' .
				__('The result is a coloured message with the summary and the headers, and the complete unmodified answer in the box below.', 'wpematico'),
			),
			'feed_viewer_ok'	   => array(
				'title'	  => __('Green result: the feed was parsed.', 'wpematico'),
				'tip'	  => __('The URL is a valid RSS or Atom feed. The summary names its format, its title and how many items it carries, and the box shows the XML as the source sent it.', 'wpematico') . '<br />' .
				__('Zero items means the feed is valid but empty right now: a campaign on it would fetch nothing until the source publishes again.', 'wpematico') . '<br />' .
				__('A line reading "Feed found by autodiscovery" means you pasted a page, not a feed, and the site advertised one: that is the address to use, because a site may stop advertising it at any time.', 'wpematico'),
				'plustip' => __('This is the answer before any campaign filter runs, so anything missing here will also be missing in the campaign.', 'wpematico'),
			),
			'feed_viewer_fallback' => array(
				'title'	  => __('Yellow result: it answers, but it is not a feed.', 'wpematico'),
				'tip'	  => __('The parser rejected the content, so the URL was requested again as a plain page to show you what is really there. The reason given by the parser is printed first.', 'wpematico') . '<br />' .
				__('An HTTP code of 200 with HTML in the box means you pasted the address of a page instead of its feed. Codes 401 or 403 mean the source is refusing the request, and 404 that the address no longer exists.', 'wpematico'),
				'plustip' => __('Many sites answer 200 with a "please enable JavaScript" or an anti-bot page; that content in the box is the source blocking automated readers, not a plugin failure.', 'wpematico'),
			),
			'feed_viewer_error'	   => array(
				'title'	  => __('Red result: the URL could not be reached.', 'wpematico'),
				'tip'	  => __('The request never got an answer, so the box shows the network error instead of content. It is a problem between your server and the source, not a problem with the feed.', 'wpematico') . '<br />' .
				__('Typical causes are a name that does not resolve, an expired or invalid SSL certificate, a firewall on your hosting blocking outgoing requests, or a source too slow to answer before the timeout.', 'wpematico'),
				'plustip' => __('If several unrelated feeds fail the same way, check the outgoing request tests on the Tools &rsaquo; System tab before blaming the sources.', 'wpematico'),
			),
			'feed_viewer_headers'  => array(
				'title'	  => __('Response headers.', 'wpematico'),
				'tip'	  => __('What the source replied about its own answer. The interesting ones are content-type (a feed should be xml, not html), last-modified and etag, which tell you how fresh the content is.', 'wpematico') . '<br />' .
				__('A content-type of text/html on a URL that still parses is usually a badly configured server, and is worth knowing when the source starts failing.', 'wpematico'),
			),
			'feed_viewer_raw'	   => array(
				'title'	  => __('Raw content box.', 'wpematico'),
				'tip'	  => __('The complete answer, untouched. Read it to confirm a feed really carries the tags you expect — full content, images, categories, custom fields — before setting a campaign to use them.', 'wpematico') . '<br />' .
				__('Use Copy to put it on the clipboard; it is what our support team asks for when a feed behaves strangely.', 'wpematico'),
			),
		),
	);

	$help = apply_filters('wpematico_help_feed_viewer_before', $help);

	if ($dev == 'tips') {
		$helptip = array();
		foreach ($help as $section) {
			foreach ($section as $section_key => $sdata) {
				$helptip[$section_key] = htmlentities($sdata['tip']);
			}
		}
		return apply_filters('wpematico_helptip_feed_viewer', $helptip);
	}

	return apply_filters('wpematico_help_feed_viewer', $help);
}

/**
 * Add the Feed Viewer sections to the Tools screen help.
 */
add_filter('wpematico_help_tools_before', 'wpematico_add_feed_viewer_help');
function wpematico_add_feed_viewer_help($helptools) {
	// Prepended, not appended: the Help tabs then come out in the same order as
	// the sections of the screen.
	return array_merge(wpematico_help_feed_viewer(), (array) $helptools);
}
