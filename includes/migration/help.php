<?php
/**
 * Migration Toolkit help.
 *
 * Same shape and plumbing the rest of the plugin uses (see campaign_help.php and
 * tools_help.php): one array of sections, each entry with a 'title' and a 'tip'
 * (plus an optional 'plustip'). The array feeds two things at once — the Help tab
 * of the screen, and the tipTip tooltips on the little (?) icons — so a wording
 * change lands in both places.
 *
 * Loaded only when the Plugin Importers module is on, like the rest of the
 * toolkit, and grafted onto the Tools help through wpematico_help_tools_before.
 *
 * @package WPeMatico
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Help sections for the Migration Toolkit.
 *
 * @param string $dev 'tips' returns the flat [key => tip] array for tooltips.
 * @return array
 */
function wpematico_help_migration($dev = '') {
    $help = array(
        __('Migration Toolkit', 'wpematico') => array(
            'migration_intro' => array(
                'title' => __('Migration Toolkit.', 'wpematico'),
                'tip'   => __('Recreates the feeds of another RSS importing plugin as WPeMatico campaigns.', 'wpematico') . ' ' .
                    __('Each detected plugin gets its own tab; nothing is changed in the source plugin, so you can import, compare the result, and only then retire it.', 'wpematico'),
                'plustip' => __('Importing twice is safe: a source already migrated updates its campaign instead of creating a second one.', 'wpematico'),
            ),
            'campaigns' => array(
                'title' => __('Import campaigns &amp; feeds.', 'wpematico'),
                'tip'   => __('Always done: every feed of the source plugin becomes a WPeMatico campaign, with its feed URLs, limits, post type, categories, author and schedule mapped to the equivalent WPeMatico settings.', 'wpematico') . '<br />' .
                    __('Imported campaigns arrive deactivated, so nothing is fetched until you review them and start them yourself.', 'wpematico'),
            ),
            'feed_groups' => array(
                'title' => __('Keep the feeds of a group together in one campaign.', 'wpematico'),
                'tip'   => __('A feed group is only a named list of feeds. What becomes a campaign is the import job that uses it (in Feedzy, a row of its Import Posts screen).', 'wpematico') . '<br />' .
                    __('So a group is imported when a job has it as its source, and the campaign of that job gets every feed of the group. A group no job uses has nothing to import.', 'wpematico') . '<br />' .
                    __('Checked (the default), those feeds stay together in that single campaign, exactly as the source plugin had them.', 'wpematico') . '<br />' .
                    __('Unchecked, each feed of the group becomes its own campaign, which is handy when you want different settings or schedules per feed.', 'wpematico'),
            ),
            'optional_features' => array(
                'title' => __('Optional feature imports.', 'wpematico'),
                'tip'   => __('Advanced settings of the source (keyword filters, fallback image, automatic translation, content rewriting) are recreated on the WPeMatico addon that provides the same feature.', 'wpematico') . '<br />' .
                    __('A checkbox only appears when the source really has that data. If the addon it needs is not active the checkbox is shown disabled, naming the addon.', 'wpematico'),
                'plustip' => __('You can import now without that addon — no source data is lost. Install it later and run the import again to fill in the missing fields.', 'wpematico'),
            ),
            'published_posts' => array(
                'title' => __('Adopt the posts already published by this plugin.', 'wpematico'),
                'tip'   => __('The source plugin has probably been publishing for a while. This links those posts to their new campaign so WPeMatico knows the items were already imported and does not publish them a second time.', 'wpematico') . '<br />' .
                    __('The posts themselves are not modified, moved or duplicated: only the origin information WPeMatico writes on any fetched post is added to them.', 'wpematico'),
                'plustip' => __('The number shown is how many posts are still waiting to be linked, so the option disappears once they all are.', 'wpematico'),
            ),
            'reader_activate' => array(
                'title' => __('Activate reader campaigns and fetch their content now.', 'wpematico'),
                'tip'   => __('Feeds that only display items (they never created posts) become RSS Feed Reader campaigns, which show the items they have already fetched.', 'wpematico') . '<br />' .
                    __('Leave this checked and the import activates them and pulls their content once, so the replacement shortcode shows something right away. Uncheck it to import them stopped and fetch later yourself.', 'wpematico'),
            ),
            'auto_deactivate' => array(
                'title' => __('Deactivate the source plugin after import.', 'wpematico'),
                'tip'   => __('Once the migration succeeds the original plugin is deactivated, together with its own add-ons, so both plugins do not import the same feeds in parallel.', 'wpematico') . '<br />' .
                    __('It is only deactivated, never deleted, and its data stays in place: you can reactivate it or run the import again at any time.', 'wpematico'),
            ),
            'preview' => array(
                'title' => __('Preview.', 'wpematico'),
                'tip'   => __('Lists what the import would create — one row per campaign, with its feed URLs, post type and status — without writing anything. The migration can be started from that same window.', 'wpematico'),
            ),
            'shortcodes' => array(
                'title' => __('Replace WP RSS Aggregator shortcodes.', 'wpematico'),
                'tip'   => __('After importing, the pages that still embed the old plugin are listed with the WPeMatico shortcode that replaces each one, and a button to swap it in place.', 'wpematico') . '<br />' .
                    __('Embeds that do not map to a single migrated feed — those showing all feeds, or combining several — are listed too, but must be replaced by hand.', 'wpematico'),
            ),
        ),
    );

    $help = apply_filters('wpematico_help_migration_before', $help);

    if ($dev == 'tips') {
        $helptip = array();
        foreach ($help as $section) {
            foreach ($section as $section_key => $sdata) {
                $helptip[$section_key] = htmlentities($sdata['tip']);
            }
        }
        return apply_filters('wpematico_helptip_migration', $helptip);
    }

    return apply_filters('wpematico_help_migration', $help);
}

/**
 * Add the Migration Toolkit sections to the Tools screen help.
 */
add_filter('wpematico_help_tools_before', 'wpematico_add_migration_help');
function wpematico_add_migration_help($helptools) {
    return array_merge((array) $helptools, wpematico_help_migration());
}
