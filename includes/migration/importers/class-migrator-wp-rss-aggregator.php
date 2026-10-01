<?php
/**
 * WP RSS Aggregator importer (v5+).
 *
 * WP RSS Aggregator v5 stores feed sources in the custom table
 * `{prefix}agg_sources` (class SourcesStore). Each row: id, name, url, active,
 * schedule, last_update, settings, v4_id, v4_slug. The `settings` column is a
 * JSON blob = SourceSettings::toArray() (get_object_vars), so its keys are the
 * camelCase property names (postType, importLimit, downloadImages, taxonomies…).
 *
 * A source's behavior depends ONLY on settings.postType:
 *   - 'wprss_feed_item' (or empty) → the source only AGGREGATES & DISPLAYS items
 *     (free core, or premium left on the internal type). No real posts are made.
 *     We migrate it to a WPeMatico 'rss_reader' campaign (RSS Feed Reader addon),
 *     which renders the feed via a shortcode instead of creating posts.
 *   - any real public post type → the premium "Feed to Post" (plus tier) creates
 *     real posts. We migrate it to a normal WPeMatico feed campaign.
 *
 * Persistence always goes through the base class (check_campaigndata +
 * update_campaign); we never scatter individual post meta.
 *
 * @package WPeMatico
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPeMatico_Migrator_WPRSS_Aggregator extends WPeMatico_Migrator_Base {

    /**
     * WP RSS Aggregator v5 sources table (e.g. wp_agg_sources).
     *
     * @var string
     */
    private $sources_table;

    /**
     * Cache of source rows.
     *
     * @var array|null
     */
    private $sources_cache = null;

    /**
     * Cache of display rows (id, sources, settings) from {prefix}agg_displays.
     *
     * @var array|null
     */
    private $displays_cache = null;

    /**
     * Display-only sources skipped because the RSS Feed Reader addon is inactive.
     *
     * @var int
     */
    private $skipped_display_only = 0;

    /**
     * Cache of optional-feature source-data presence, keyed by feature.
     *
     * @var array
     */
    private $source_has_cache = array();

    /**
     * Whether to activate migrated reader campaigns and fetch their content once
     * during the import (opt-out via the UI). When false they are left deactivated.
     *
     * @var bool
     */
    private $reader_initial_fetch = true;

    /**
     * Post-creating sources that were active in WP RSS Aggregator and whose
     * campaign was nevertheless imported deactivated. Reported so the user is not
     * left waiting for an import that will never start on its own.
     *
     * @var int
     */
    private $deactivated_active_sources = 0;

    /**
     * The internal post type WP RSS Aggregator uses for display-only items.
     */
    const READER_TYPE = 'wprss_feed_item';

    /**
     * Constructor.
     *
     * @param array $plugin_data
     */
    public function __construct($plugin_data) {
        parent::__construct($plugin_data);
        global $wpdb;
        // Database::tableName('sources') = $wpdb->prefix . 'agg_' . 'sources'.
        $this->sources_table = $wpdb->prefix . 'agg_sources';
    }

    /* --------------------------------------------------------------------- *
     *  Public API (migrate / preview / counts)
     * --------------------------------------------------------------------- */

    /**
     * Run the migration.
     */
    public function migrate() {
        $this->log('Starting WP RSS Aggregator migration.', 'info');

        if (!$this->table_exists()) {
            return $this->format_result(false, $this->missing_table_message());
        }

        $sources = $this->get_sources();
        if (empty($sources)) {
            return $this->format_result(false, __('No feed sources found to migrate.', 'wpematico'));
        }

        foreach ($sources as $src) {
            $this->migrate_source($src);
        }

        $message = sprintf(
            /* translators: %d: number of migrated campaigns. */
            __('Successfully migrated %d campaigns.', 'wpematico'),
            $this->migrated_count
        );
        if ($this->skipped_display_only > 0) {
            $message .= ' ' . sprintf(
                /* translators: %d: number of skipped display-only feeds. */
                _n(
                    'Skipped %d display-only feed: install the WPeMatico RSS Feed Reader addon and run the migration again to import it.',
                    'Skipped %d display-only feeds: install the WPeMatico RSS Feed Reader addon and run the migration again to import them.',
                    $this->skipped_display_only,
                    'wpematico'
                ),
                $this->skipped_display_only
            );
        }

        if ($this->deactivated_active_sources > 0) {
            $this->extra_notices[] = sprintf(
                /* translators: %d: number of campaigns imported deactivated. */
                _n(
                    '%d feed that was running in WP RSS Aggregator became a campaign that <strong>creates posts</strong>, so it was imported deactivated. Review it and activate its scheduling when you are ready.',
                    '%d feeds that were running in WP RSS Aggregator became campaigns that <strong>create posts</strong>, so they were imported deactivated. Review them and activate their scheduling when you are ready.',
                    $this->deactivated_active_sources,
                    'wpematico'
                ),
                $this->deactivated_active_sources
            );
        }

        return $this->format_result(true, $message, array(
            'campaigns'            => $this->created_campaigns,
            'skipped_display_only' => $this->skipped_display_only,
            'notices'              => $this->build_import_notices(),
        ));
    }

    /**
     * Preview of what will be migrated.
     */
    public function get_preview() {
        $preview = array();

        foreach ($this->get_sources() as $src) {
            $settings    = $this->parse_settings($src->settings);
            $display_only = $this->is_display_only($settings);

            $preview[] = array(
                'id'        => $src->id,
                'title'     => $src->name,
                'feed_url'  => $src->url,
                'post_type' => $display_only ? self::READER_TYPE : $this->convert_post_type($settings['postType']),
                'target'    => $display_only ? 'rss_reader' : 'feed',
                'status'    => ((int) $src->active === 1) ? 'active' : 'inactive',
            );
        }

        return $preview;
    }

    /**
     * Count of sources available for migration.
     */
    public function get_items_count() {
        if (!$this->table_exists()) {
            return 0;
        }
        return count($this->get_sources());
    }

    /**
     * Summary used by the preview modal.
     */
    public function get_migration_summary() {
        $summary = array(
            'total_sources'      => 0,
            'feed_campaigns'     => 0,
            'reader_feeds'       => 0,
            'new_campaigns'      => 0,
            'existing_campaigns' => 0,
        );

        foreach ($this->get_sources() as $src) {
            $settings = $this->parse_settings($src->settings);
            $summary['total_sources']++;
            if ($this->is_display_only($settings)) {
                $summary['reader_feeds']++;
            } else {
                $summary['feed_campaigns']++;
            }
            // A source already migrated is updated in place, not duplicated.
            if ($this->get_existing_campaign_id($src->id . '_wprss')) {
                $summary['existing_campaigns']++;
            } else {
                $summary['new_campaigns']++;
            }
        }

        return $summary;
    }

    /* --------------------------------------------------------------------- *
     *  Provenance
     * --------------------------------------------------------------------- */

    /**
     * WP RSS Aggregator glyph (stacked feed bars), monochrome so CSS can dim it.
     *
     * @return string
     */
    public function get_icon_svg() {
        // WP RSS Aggregator's actual WP admin menu icon, recolored to currentColor.
        return '<svg viewBox="0 0 14 17" width="15" height="15" xmlns="http://www.w3.org/2000/svg" fill="currentColor" aria-hidden="true">'
            . '<path fill-rule="evenodd" clip-rule="evenodd" d="M4.97866 0.936064L4.65065 0.912109L4.4965 1.20263L4.49648 1.20267L4.47523 1.24276C4.46062 1.27035 4.43798 1.31311 4.40584 1.37387C4.34156 1.4954 4.23926 1.68898 4.08703 1.9774L3.80708 2.51155C4.19018 2.62737 4.39605 2.706 4.74817 2.8816L4.97893 2.44812C5.08034 2.25597 5.15955 2.106 5.22009 1.99145C6.31561 2.2461 8.00719 3.41484 8.45571 5.6598L8.45678 5.66515L8.45796 5.67047C8.48707 5.80138 8.52415 6.04599 8.54271 6.35634C7.38449 4.91022 6.13149 3.96175 5.00587 3.36548C4.3027 2.993 3.64827 2.75744 3.09659 2.62403C2.55579 2.49325 2.08183 2.45298 1.74822 2.50038L1.49651 2.53615L1.3756 2.7598C0.657448 4.08814 0.489595 5.77408 0.806449 7.25719C1.12195 8.73394 1.94601 10.1178 3.32756 10.7018C3.35849 10.7141 3.37824 10.7231 3.39936 10.7327C3.45529 10.7581 3.52081 10.7878 3.82872 10.8962C3.85946 10.8049 3.88532 10.725 3.90887 10.6523L3.90894 10.6521C3.98959 10.403 4.04309 10.2379 4.17224 9.98212L3.72023 9.7729C2.7497 9.36263 2.06815 8.33589 1.79268 7.04649C1.53927 5.86036 1.6533 4.5437 2.13681 3.49177C2.31533 3.49981 2.55899 3.53158 2.85955 3.60427C3.327 3.71731 3.90266 3.92233 4.53379 4.25666C5.79359 4.924 7.27076 6.10454 8.547 8.08993L8.55138 8.09673L8.55597 8.10339C9.17499 9.00166 9.61011 9.99339 9.75993 11.0496C9.78928 11.2565 9.80711 11.454 9.81461 11.6425C7.63764 11.4272 6.05468 10.2303 5.53437 9.65133L5.07812 9.14363L4.72694 9.72894C4.59418 9.9502 4.32846 10.5494 4.17213 11.0785C4.13515 11.2037 4.1028 11.3232 4.07445 11.439C4.06751 11.4673 4.06081 11.4954 4.05434 11.5234C4.04518 11.5629 4.0365 11.6021 4.02825 11.6409C3.956 11.9809 3.91925 12.2886 3.89585 12.6178C3.87502 12.9109 3.86847 13.2252 3.88371 13.5493C3.88748 13.6294 3.89259 13.7103 3.89916 13.7916C3.90397 13.8511 3.90955 13.9108 3.91597 13.9708C3.93408 14.14 3.95882 14.311 3.99132 14.4822C4.14301 15.2811 4.46841 16.1051 5.09923 16.7494L5.25857 16.9121L5.48602 16.9001C6.19155 16.863 7.11473 16.6767 7.99559 16.257C8.15225 16.1824 8.30806 16.1002 8.46144 16.0097C8.52906 15.9699 8.59623 15.9284 8.6628 15.8853C8.79681 15.7985 8.92847 15.705 9.0566 15.6043C9.30352 15.4104 9.53751 15.1898 9.74981 14.94C9.91367 14.7471 10.0643 14.5371 10.1978 14.309C10.273 14.1805 10.3427 14.0464 10.4061 13.9066C10.4945 13.7117 10.5707 13.506 10.633 13.2894C10.7293 12.9545 10.792 12.5948 10.8161 12.2096C10.8415 11.8034 10.824 11.37 10.7584 10.908C10.5923 9.73703 10.131 8.65815 9.49963 7.69923C9.58894 7.05109 9.57073 6.43533 9.52416 5.98613C9.83362 5.9578 10.2464 5.95513 10.6606 6.01873L10.6607 6.01875C10.7724 6.0359 10.8842 6.05787 10.994 6.08545C11.5943 6.23617 12.0302 6.52245 12.2015 6.98823C12.3599 7.41878 12.3627 7.74966 12.3006 8.00461C12.2379 8.26266 12.0992 8.48133 11.9161 8.66867C11.5375 9.05588 10.9726 9.26293 10.7593 9.33676C10.9247 9.71742 10.9841 9.92574 11.0604 10.2881C11.3105 10.2016 12.0891 9.93422 12.6372 9.3737C12.9173 9.08718 13.166 8.71406 13.2806 8.24294C13.3959 7.76871 13.3661 7.23301 13.148 6.64009C12.9547 6.1145 12.6018 5.74959 12.195 5.49845C12.2579 5.48983 12.32 5.49154 12.3707 5.49702C12.3983 5.5 12.4197 5.5038 12.4323 5.50634C12.4359 5.50709 12.4388 5.5077 12.4407 5.50814L12.4429 5.50864C12.7096 5.57941 12.9842 5.4224 13.058 5.1558C13.1324 4.88742 12.975 4.6096 12.7066 4.53528L12.7057 4.535L12.7046 4.53471L12.7022 4.53405C12.7004 4.53359 12.6984 4.53307 12.6962 4.53249C12.6918 4.53134 12.6865 4.52999 12.6802 4.52848C12.6678 4.52547 12.6516 4.52181 12.6323 4.51789C12.5938 4.5101 12.5416 4.50113 12.4791 4.49437C12.3562 4.48109 12.1819 4.47507 11.9884 4.51043C11.8299 4.53939 11.6628 4.59629 11.5049 4.69192C11.5588 4.49531 11.6854 4.27543 11.9971 4.05375C12.2241 3.89236 12.2772 3.57755 12.1158 3.35059C11.9544 3.12364 11.6396 3.07049 11.4127 3.23188C10.8962 3.59913 10.6231 4.0356 10.5147 4.49444C10.4754 4.66108 10.4594 4.82442 10.4578 4.97968C10.0322 4.94328 9.63487 4.95815 9.33221 4.99202L9.32936 4.98197C8.59538 2.37629 6.39503 1.0395 4.97866 0.936064ZM5.32468 10.8342C6.20763 11.5618 7.76441 12.4619 9.74852 12.6494C9.72542 12.7749 9.69699 12.8953 9.6638 13.0107C9.64283 13.0836 9.61994 13.1546 9.59522 13.2239C9.28551 13.2311 8.91592 13.1816 8.50684 13.0807C7.9772 12.9501 7.41682 12.7424 6.89481 12.5043C6.37288 12.2663 5.90149 12.0035 5.5497 11.769C5.37328 11.6514 5.23449 11.546 5.13677 11.4592C5.12979 11.453 5.12319 11.447 5.11696 11.4413C5.12415 11.416 5.13159 11.3903 5.13929 11.3643C5.19178 11.1866 5.25817 11.0021 5.32468 10.8342ZM8.26536 14.0599C8.52531 14.124 8.78931 14.1746 9.04951 14.2041C9.02714 14.2322 9.00439 14.2598 8.98128 14.287C8.85265 14.4384 8.71236 14.5776 8.56287 14.7054C7.95974 14.6904 7.1973 14.4592 6.4631 14.1372C5.80277 13.8476 5.22553 13.5116 4.88431 13.2699C4.88198 13.0703 4.88857 12.8754 4.9018 12.6893C4.90503 12.6439 4.90851 12.5993 4.9123 12.5554C4.93794 12.573 4.96398 12.5906 4.99039 12.6082C5.3929 12.8765 5.9124 13.1647 6.47635 13.4219C7.04021 13.679 7.66071 13.9107 8.26536 14.0599ZM6.05806 15.0608C6.42525 15.2218 6.82507 15.3739 7.23091 15.4908C6.6829 15.7084 6.1308 15.8276 5.6684 15.8747C5.38254 15.5122 5.16142 15.0472 5.03863 14.5528C5.34745 14.7262 5.69503 14.9016 6.05806 15.0608ZM11.3968 7.31558C11.3968 7.52066 11.2106 7.4727 10.9949 7.41717C10.8855 7.389 10.7685 7.35887 10.6644 7.35887C10.5618 7.35887 10.4639 7.38306 10.3789 7.40406C10.2078 7.44631 10.089 7.47566 10.089 7.26915C10.089 6.96 10.4674 6.77584 10.7765 6.77584C11.0857 6.77584 11.3968 7.00643 11.3968 7.31558Z"/></svg>';
    }

    /**
     * @return string
     */
    public function get_source_label() {
        return 'WP RSS Aggregator';
    }

    /* --------------------------------------------------------------------- *
     *  Optional, addon-gated features
     * --------------------------------------------------------------------- */

    /**
     * Optional imports offered for WP RSS Aggregator. Each is shown only when the
     * source feeds actually contain that data; enabled only when the matching
     * WPeMatico addon is active. Applies to post-creating (feed) campaigns.
     *
     * @return array
     */
    public function get_optional_features() {
        return array(
            'filters' => array(
                'label'           => __('Import keyword filters', 'wpematico'),
                'note'            => __('Maps WP RSS Aggregator import automations to WPeMatico include/exclude keyword filters.', 'wpematico'),
                'addon_const'     => 'WPEMATICOPRO_VERSION',
                'addon_name'      => 'WPeMatico Professional',
                'has_source_data' => $this->source_has('filters'),
                'post_note'       => __('<strong>Keyword Filtering was enabled in the WPeMatico Professional settings</strong> so the imported filters run.', 'wpematico'),
            ),
            'fallback_image' => array(
                'label'           => __('Import fallback featured image', 'wpematico'),
                'note'            => __('Uses the source\'s fallback featured image as the campaign default featured image.', 'wpematico'),
                'addon_const'     => 'WPEMATICOPRO_VERSION',
                'addon_name'      => 'WPeMatico Professional',
                'has_source_data' => $this->source_has('fallback_image'),
            ),
            'full_text' => array(
                'label'           => __('Import full content from truncated feeds', 'wpematico'),
                'note'            => __('Recreates WP RSS Aggregator\'s full-text import with the WPeMatico Full Content addon.', 'wpematico'),
                'addon_const'     => 'WPEFULLCONTENT_VERSION',
                'addon_name'      => 'WPeMatico Full Content',
                'has_source_data' => $this->source_has('full_text'),
            ),
            'rewrite' => array(
                'label'           => __('Import content rewrite', 'wpematico'),
                'note'            => __('Recreates WP RSS Aggregator\'s spin/AI rewrite with the WPeMatico GPT Spinner addon.', 'wpematico'),
                'addon_const'     => 'WPE_GPT_SPINNER_VER',
                'addon_name'      => 'WPeMatico GPT Spinner',
                'has_source_data' => $this->source_has('rewrite'),
                'post_note'       => __('WPeMatico <strong>GPT Spinner needs its API configured in Settings</strong>. Check the model: Etruel Rewriter for SpinnerChief/WordAI, GPT Machine for the AI engine.', 'wpematico'),
            ),
        );
    }

    /**
     * Whether ANY source carries the data behind an optional feature. Cached.
     *
     * @param string $feature
     * @return bool
     */
    private function source_has($feature) {
        if (isset($this->source_has_cache[$feature])) {
            return $this->source_has_cache[$feature];
        }
        // These map onto post-creating (feed) campaigns only, which exist solely
        // when Feed to Post is active. Without it every source is display-only, so
        // there is nowhere to apply the feature — hide the option entirely.
        if (!$this->feed_to_post_active()) {
            $this->source_has_cache[$feature] = false;
            return false;
        }
        $found = false;
        foreach ($this->get_sources() as $src) {
            if ($this->settings_has_feature($this->parse_settings($src->settings), $feature)) {
                $found = true;
                break;
            }
        }
        $this->source_has_cache[$feature] = $found;
        return $found;
    }

    /**
     * Whether a source's parsed settings carry the data behind an optional feature.
     *
     * @param array  $settings
     * @param string $feature
     * @return bool
     */
    private function settings_has_feature($settings, $feature) {
        switch ($feature) {
            case 'filters':
                return (!empty($settings['automations']) && is_array($settings['automations']));

            case 'fallback_image':
                return (isset($settings['fallbackFtImageId']) && (int) $settings['fallbackFtImageId'] > 0);

            case 'full_text':
                return !empty($settings['enableFullText']);

            case 'rewrite':
                return (!empty($settings['waiSpintax']) || !empty($settings['waiEnableContent'])
                    || !empty($settings['scEnableContent']) || !empty($settings['airewriteEnable'])
                    || !empty($settings['aisEnable']));
        }
        return false;
    }

    /* --------------------------------------------------------------------- *
     *  Migration of a single source
     * --------------------------------------------------------------------- */

    /**
     * Migrate one source row into the right kind of WPeMatico campaign.
     *
     * @param object $src Source table row.
     * @return int|false|null  Campaign ID, false on failure, or null when a
     *                         display-only feed was skipped (reader addon off).
     */
    private function migrate_source($src) {
        $feed_url = $this->clean_feed_url($src->url);
        if (!$feed_url) {
            $this->log('Invalid feed URL for source: ' . $src->name, 'error');
            return false;
        }

        $settings = $this->parse_settings($src->settings);

        $is_reader = $this->is_display_only($settings);
        if ($is_reader) {
            // Display-only feeds need the RSS Feed Reader addon. Without it we skip
            // them (never create a broken campaign); the user either installs the
            // addon and re-runs, or accepts that these feeds are ignored.
            if (!$this->reader_addon_active()) {
                $this->skipped_display_only++;
                $this->log('Skipped display-only feed "' . $src->name . '" (needs the WPeMatico RSS Feed Reader addon).', 'warning');
                return null;
            }
            $data = $this->build_reader_campaign_data($src, $settings, $feed_url);
        } else {
            $data = $this->build_feed_campaign_data($src, $settings, $feed_url);
            // Post-creating campaigns always arrive deactivated, however the source
            // was set, so nothing is published before the user has reviewed them.
            if ($this->source_is_active($src)) {
                $this->deactivated_active_sources++;
            }
        }

        $source_id   = $src->id . '_wprss';
        $existing_id = $this->get_existing_campaign_id($source_id);

        if ($existing_id) {
            $campaign_id = $this->update_existing_campaign($existing_id, $data);
        } else {
            $campaign_id = $this->create_wpematico_campaign($data);
        }

        if ($campaign_id) {
            $this->store_provenance($campaign_id, $source_id, $src->name);
            if ($is_reader) {
                $this->sync_reader_shortcode_name($campaign_id);
                if ($this->reader_initial_fetch && $this->source_is_active($src)) {
                    $this->run_initial_fetch($campaign_id);
                }
            } else {
                // Only Feed to Post sources ever created posts; a display-only
                // source has nothing published to adopt.
                $this->adopt_published_posts($campaign_id, $src->id, array($feed_url));
            }
        }

        return $campaign_id;
    }

    /* --------------------------------------------------------------------- *
     *  Already-published posts
     * --------------------------------------------------------------------- */

    /**
     * WP RSS Aggregator records the source and the original item URL on every
     * post it imports. Two schemas can coexist on the same site: v5 writes
     * `_wpra_source` / `_wpra_url` (ImportedPost::SOURCE / ::URL), and posts
     * created before the v5 upgrade keep the v4 pair `wprss_feed_id` /
     * `wprss_item_permalink` — its own item migrator only backfills the new
     * keys for items it processed, so both are checked and the results merged.
     *
     * @param string|int $source_key agg_sources row id.
     * @return array
     */
    protected function get_published_posts_sources($source_key) {
        $url_metas = array('_wpra_url', 'wprss_item_permalink');
        return array(
            array(
                'meta_key'   => '_wpra_source',
                'meta_value' => (int) $source_key,
                'url_metas'  => $url_metas,
            ),
            array(
                'meta_key'   => 'wprss_feed_id',
                'meta_value' => (int) $source_key,
                'url_metas'  => $url_metas,
            ),
        );
    }

    /**
     * `wprss_feed_item` is WP RSS Aggregator's own item store, not published
     * content: display-only sources park every fetched item there and it is
     * stamped with the very same _wpra_source / _wpra_url meta as a Feed to Post
     * post. Those items are recreated by the migrated reader campaign's own
     * fetch, so adopting them would tie WPeMatico to rows that vanish with the
     * source plugin — and would make the box offer to adopt posts on a site
     * that never had Feed to Post at all.
     *
     * @return string[]
     */
    protected function get_internal_post_types() {
        return array('wprss_feed_item');
    }

    /**
     * Total posts WP RSS Aggregator has published on this site.
     *
     * @return int
     */
    public function get_published_posts_count() {
        return $this->count_posts_with_meta_keys(array('_wpra_source', 'wprss_feed_id'));
    }

    /**
     * Whether to activate reader campaigns and fetch their content during import.
     *
     * @param bool $enabled
     */
    public function set_reader_initial_fetch($enabled) {
        $this->reader_initial_fetch = (bool) $enabled;
    }

    /**
     * Fetch a reader campaign once right after import so its shortcode shows
     * content immediately (unlike WP RSS Aggregator, the reader renders items the
     * campaign has already fetched into the feed_items meta, not live).
     */
    private function run_initial_fetch($campaign_id) {
        if (!method_exists('WPeMatico', 'wpematico_dojob')) {
            return;
        }
        try {
            WPeMatico::wpematico_dojob($campaign_id);
        } catch (\Throwable $e) {
            $this->log('Initial fetch failed for reader campaign ' . $campaign_id . ': ' . $e->getMessage(), 'warning');
        }
    }

    /**
     * Align wpematico_shortcode_name with the actual post slug. The reader addon
     * registers [wpematico-{post_name}] and its editor rewrites the field to the
     * slug on save, so the shortcode name must follow post_name (which WP may
     * suffix on collision) — otherwise the migrated shortcode wouldn't render.
     */
    private function sync_reader_shortcode_name($campaign_id) {
        $post = get_post($campaign_id);
        if (!$post || $post->post_name === '') {
            return;
        }
        $data = get_post_meta($campaign_id, 'campaign_data', true);
        if (!is_array($data) || (isset($data['wpematico_shortcode_name']) && $data['wpematico_shortcode_name'] === $post->post_name)) {
            return;
        }
        $data['wpematico_shortcode_name'] = $post->post_name;
        update_post_meta($campaign_id, 'campaign_data', $data);
    }

    /* --------------------------------------------------------------------- *
     *  Campaign builders
     * --------------------------------------------------------------------- */

    /**
     * Build a normal post-creating feed campaign (source had Feed to Post).
     *
     * @param object $src
     * @param array  $settings
     * @param string $feed_url
     * @return array
     */
    private function build_feed_campaign_data($src, $settings, $feed_url) {
        list($categories, $tags) = $this->map_taxonomies($settings);

        $data = array(
            'campaign_title'          => $this->campaign_title($src->name),
            'campaign_excerpt'        => $this->import_note(),
            'campaign_feeds'          => array($feed_url),
            'campaign_max'            => $this->normalize_limit($settings['importLimit']),
            'campaign_author'         => $this->resolve_author($settings),
            'campaign_customposttype' => $this->convert_post_type($settings['postType']),
            'campaign_posttype'       => $this->convert_post_status($settings['postStatus']),
            'campaign_postformat'     => $this->map_post_format($settings['postFormat']),
            'campaign_commentstatus'  => !empty($settings['commentsOpen']) ? 'open' : 'closed',
            'campaign_feeddate'       => $this->uses_item_date($settings) ? 1 : 0,

            // Per-campaign image settings so they are honored over the globals.
            'campaign_no_setting_img' => 1,
            'campaign_imgcache'       => !empty($settings['downloadImages']) ? 1 : 0,
            'campaign_featuredimg'    => !empty($settings['assignFtImage']) ? 1 : 0,
        );

        $cron = $this->map_schedule($src->schedule, $settings);
        if ($cron !== '') {
            $data['cron'] = $cron;
        }

        if (!empty($categories)) {
            $data['post_category'] = $this->get_categories_array($categories);
        }
        if (!empty($tags)) {
            $data['campaign_tags'] = implode(',', $tags);
        }

        // Optional, addon-gated imports (only when the user ticked them AND the
        // matching WPeMatico addon is active — see option_enabled()).
        if ($this->option_enabled('filters')) {
            $this->map_filters($settings, $data);
        }
        if ($this->option_enabled('fallback_image')) {
            $this->map_fallback_image($settings, $data);
        }
        if ($this->option_enabled('full_text')) {
            $this->map_full_text($settings, $data);
        }
        if ($this->option_enabled('rewrite')) {
            $this->map_rewrite($settings, $data);
        }

        // Cosmetic (PRO): name the campaign's single feed after the source.
        $this->map_feed_names($data, array($src->name));

        return $data;
    }

    /* --------------------------------------------------------------------- *
     *  Optional addon-gated mappers
     *  NOTE: these target WPRA premium (Plus/Pro/Elite) data + WPeMatico addon
     *  fields that do not exist on a free WPRA install, so they are UNVERIFIED
     *  against real data.
     * --------------------------------------------------------------------- */

    /**
     * Map WP RSS Aggregator import automations to WPeMatico Professional keyword
     * filters. WPRA automation: {actionId: import|doNotImport, conditions:[{isAnd,
     * exprs:[{subjectId: title|content|title_content, operatorId: tagsContainAny|
     * tagsContainNone, args:{value:"csv"}}]}]}. WPeMatico PRO: flat
     * campaign_kwordf_inc/exc (+ _tit/_con, _anyall). Only the plain
     * "contain any" case is mapped; negations/AND nesting are skipped + logged.
     *
     * @param array $settings
     * @param array $data (by reference)
     */
    private function map_filters($settings, &$data) {
        if (empty($settings['automations']) || !is_array($settings['automations'])) {
            return;
        }

        $inc = array();
        $exc = array();
        $on_title = false;
        $on_content = false;

        foreach ($settings['automations'] as $automation) {
            if (!is_array($automation) || (isset($automation['enabled']) && !$automation['enabled'])) {
                continue;
            }
            $action = isset($automation['actionId']) ? $automation['actionId'] : 'import';
            $conditions = isset($automation['conditions']) && is_array($automation['conditions'])
                ? $automation['conditions'] : array();

            foreach ($conditions as $condition) {
                $exprs = (is_array($condition) && !empty($condition['exprs']) && is_array($condition['exprs']))
                    ? $condition['exprs'] : array();

                foreach ($exprs as $expr) {
                    if (!is_array($expr)) {
                        continue;
                    }
                    $subject  = isset($expr['subjectId']) ? $expr['subjectId'] : '';
                    $operator = isset($expr['operatorId']) ? $expr['operatorId'] : '';
                    $value    = '';
                    if (isset($expr['args']) && is_array($expr['args']) && isset($expr['args']['value'])) {
                        $value = trim((string) $expr['args']['value']);
                    }
                    if ($value === '') {
                        continue;
                    }

                    // Only the plain "contains any" case maps cleanly. "Contain none"
                    // (negation) has no faithful flat equivalent here.
                    if ($operator !== 'tagsContainAny') {
                        $this->log('Skipped WPRA filter with unsupported operator "' . $operator . '".', 'info');
                        continue;
                    }

                    if ($subject === 'title') {
                        $on_title = true;
                    } elseif ($subject === 'content') {
                        $on_content = true;
                    } else {
                        // title_content (or anything else): apply to both.
                        $on_title = $on_content = true;
                    }

                    $words = array_filter(array_map('trim', explode(',', $value)));
                    if ($action === 'doNotImport') {
                        $exc = array_merge($exc, $words);
                    } else {
                        $inc = array_merge($inc, $words);
                    }
                }
            }
        }

        if (!$on_title && !$on_content) {
            $on_title = $on_content = true;
        }

        if (!empty($inc)) {
            $data['campaign_kwordf_inc']        = implode(',', array_unique($inc));
            $data['campaign_kwordf_inc_tit']    = $on_title ? 1 : 0;
            $data['campaign_kwordf_inc_con']    = $on_content ? 1 : 0;
            $data['campaign_kwordf_inc_anyall'] = 'anyword';
        }
        if (!empty($exc)) {
            $data['campaign_kwordf_exc']        = implode(',', array_unique($exc));
            $data['campaign_kwordf_exc_tit']    = $on_title ? 1 : 0;
            $data['campaign_kwordf_exc_con']    = $on_content ? 1 : 0;
            $data['campaign_kwordf_exc_anyall'] = 'anyword';
        }
    }

    /**
     * Map the source's fallback featured image to WPeMatico Professional's default
     * featured image. The attachment lives in this site's media library, so its ID
     * is reused directly.
     *
     * @param array $settings
     * @param array $data (by reference)
     */
    private function map_fallback_image($settings, &$data) {
        $attach_id = isset($settings['fallbackFtImageId']) ? (int) $settings['fallbackFtImageId'] : 0;
        if ($attach_id <= 0) {
            return;
        }
        $data['default_img']    = 1;
        $data['default_img_id'] = $attach_id;
        $url = wp_get_attachment_url($attach_id);
        if ($url) {
            $data['default_img_url'] = $url;
        }
    }

    /**
     * Map WP RSS Aggregator's full-text import to the WPeMatico Full Content addon.
     *
     * @param array $settings
     * @param array $data (by reference)
     */
    private function map_full_text($settings, &$data) {
        if (empty($settings['enableFullText'])) {
            return;
        }
        $data['campaign_fullcontent'] = 1;
    }

    /**
     * Map WP RSS Aggregator's spin/AI rewrite to the WPeMatico GPT Spinner addon.
     *
     * @param array $settings
     * @param array $data (by reference)
     */
    private function map_rewrite($settings, &$data) {
        $data['campaign_gpt_spinner'] = 1;
        $data['gpt_spinner_rewrite_options'] = 'rewrite_content';

        // AI rewrite (WPRA "AI Rewrite"/AIS engines) maps to GPT Machine; the
        // WordAI/SpinnerChief engines map to Etruel Rewriter. Both live under the
        // same API family. Heuristic — UNVERIFIED against real premium data.
        $is_openai = !empty($settings['airewriteEnable']) || !empty($settings['aisEnable']);
        $data['gpt_spinner_api']   = 'ai_etruel_rewriter_api';
        $data['gpt_spinner_model'] = $is_openai ? 'gpt_machine_api' : 'ai_etruel_rewriter_api';
    }

    /**
     * Build a display-only reader campaign (RSS Feed Reader addon).
     *
     * @param object $src
     * @param array  $settings
     * @param string $feed_url
     * @return array
     */
    private function build_reader_campaign_data($src, $settings, $feed_url) {
        $limit = $this->normalize_limit($settings['importLimit']);

        $data = array(
            'campaign_title'    => $this->campaign_title($src->name),
            'campaign_excerpt'  => $this->import_note(),
            'campaign_feeds'    => array($feed_url),
            'campaign_type'     => 'rss_reader',

            // Reader campaigns create no posts (they only populate the feed_items
            // meta the shortcode reads), so it is safe to activate them — and they
            // must be active to keep refreshing via cron. Opt-out via the UI.
            // A source the user had paused in WP RSS Aggregator stays paused here.
            'activated'         => ($this->reader_initial_fetch && $this->source_is_active($src)),

            // RSS Feed Reader addon fields (persisted only when the addon is
            // active; on re-import after install they get filled in).
            'campaign_max'          => $limit,
            'campaign_max_to_show'  => $limit,
            'campaign_rss_feed_reader' => 'shortcode', // most portable output mode
            'wpematico_shortcode_name' => sanitize_title($src->name),
            'campaign_feed_order_date' => $this->uses_item_date($settings) ? 1 : 0,
            'campaign_rss_layout'      => $this->get_source_layout($src->id),
        );

        $cron = $this->map_schedule($src->schedule, $settings);
        if ($cron !== '') {
            $data['cron'] = $cron;
        }

        // Cosmetic (PRO): name the campaign's single feed after the source.
        $this->map_feed_names($data, array($src->name));

        return $data;
    }

    /* --------------------------------------------------------------------- *
     *  Field mapping helpers
     * --------------------------------------------------------------------- */

    /**
     * Whether a source only displays items (no real post creation).
     *
     * Two things make a source display-only:
     *  - WP RSS Aggregator's Feed to Post is NOT active (free, or a license tier
     *    below Plus): the importer then forces the internal wprss_feed_item type
     *    for EVERY source, no matter what postType is stored — so nothing creates
     *    posts. Note settings.postType defaults to 'post' even on free, so it
     *    cannot be trusted on its own.
     *  - Feed to Post IS active but the source is explicitly set to the internal
     *    display type (postType empty or 'wprss_feed_item').
     *
     * @param array $settings
     * @return bool
     */
    private function is_display_only($settings) {
        if (!$this->feed_to_post_active()) {
            return true;
        }
        $post_type = isset($settings['postType']) ? $settings['postType'] : '';
        return ($post_type === '' || $post_type === self::READER_TYPE);
    }

    /**
     * Whether WP RSS Aggregator's Feed to Post (premium Plus tier) is active.
     * The Plus package's `posts` module registers the wpra.importer.post.type
     * filter eagerly when it loads (Plugin::loadPackages runs modules on load),
     * so the filter's presence is a reliable signal at admin render time.
     *
     * @return bool
     */
    private function feed_to_post_active() {
        return (has_filter('wpra.importer.post.type') !== false);
    }

    /**
     * Whether the WPeMatico RSS Feed Reader addon is active.
     *
     * @return bool
     */
    private function reader_addon_active() {
        return defined('WPEMATICO_RSS_FEED_READER_VER');
    }

    /**
     * Whether a source was running in WP RSS Aggregator (its `active` column, the
     * same flag the "Activate feed immediately" option sets when the feed is added).
     *
     * @param object $src
     * @return bool
     */
    private function source_is_active($src) {
        return !empty($src->active) && filter_var($src->active, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Map WP RSS Aggregator taxonomy rules to WPeMatico category IDs and tag
     * names. Only STATIC assignments are portable (assign = true, no condition);
     * conditional rules have no plain campaign equivalent and are skipped.
     *
     * settings.taxonomies = { taxonomy: [ { assign, import, terms:[id...],
     * useCondition, conditions:[...], parent }, ... ] }.
     *
     * @param array $settings
     * @return array{0:int[],1:string[]}  [category IDs, tag names]
     */
    private function map_taxonomies($settings) {
        $categories = array();
        $tags       = array();

        if (empty($settings['taxonomies']) || !is_array($settings['taxonomies'])) {
            return array($categories, $tags);
        }

        foreach ($settings['taxonomies'] as $taxonomy => $rules) {
            if (!is_array($rules)) {
                continue;
            }
            foreach ($rules as $rule) {
                if (!is_array($rule) || empty($rule['assign']) || empty($rule['terms']) || !is_array($rule['terms'])) {
                    continue;
                }
                if (!empty($rule['useCondition'])) {
                    $this->log('Skipped a conditional "' . $taxonomy . '" rule (no plain campaign equivalent).', 'info');
                    continue;
                }
                foreach ($rule['terms'] as $term_id) {
                    $term_id = (int) $term_id;
                    if ($term_id <= 0) {
                        continue;
                    }
                    if ($taxonomy === 'category') {
                        $categories[] = $term_id;
                    } elseif ($taxonomy === 'post_tag') {
                        $term = get_term($term_id, 'post_tag');
                        if ($term && !is_wp_error($term)) {
                            $tags[] = $term->name;
                        }
                    } else {
                        $this->log('Skipped term from unsupported taxonomy "' . $taxonomy . '".', 'info');
                    }
                }
            }
        }

        return array($categories, $tags);
    }

    /**
     * Resolve the source author settings to a WordPress user ID. Never creates a
     * user: a dynamic ("feed") author or a missing user falls back to current.
     *
     * @param array $settings
     * @return int
     */
    private function resolve_author($settings) {
        $fallback = isset($settings['fallbackAuthorId']) ? (int) $settings['fallbackAuthorId'] : 0;
        if ($fallback > 0 && get_user_by('id', $fallback)) {
            return $fallback;
        }
        return $this->get_default_author();
    }

    /**
     * Map WP RSS Aggregator post format to WPeMatico's ('standard' → '0').
     *
     * @param mixed $format
     * @return string
     */
    private function map_post_format($format) {
        $format = (string) $format;
        return ($format === '' || $format === 'standard') ? '0' : sanitize_key($format);
    }

    /**
     * Whether the source keeps the original feed item date.
     *
     * @param array $settings
     * @return bool
     */
    private function uses_item_date($settings) {
        $which = isset($settings['whichPostDate']) ? $settings['whichPostDate'] : 'published_date';
        // Anything other than the WP import time keeps the feed's own date.
        return ($which !== 'import_time' && $which !== 'current');
    }

    /**
     * Map a source schedule to a WPeMatico cron string. '' = keep the default.
     *
     * ★ The `schedule` column is NOT a WP cron-recurrence key: WP RSS Aggregator v5
     * writes its own compact format (ScheduleFactory::fromString) — "30m", "2h",
     * "1d", "1d 13:30", "1w", "1M" — so looking the value up in wp_get_schedules()
     * never matched and every migrated campaign silently kept WPeMatico's default
     * daily schedule. A WP key is still accepted, for v4 leftovers.
     *
     * @param mixed $schedule Row `schedule` value.
     * @param array $settings
     * @return string
     */
    private function map_schedule($schedule, $settings) {
        $key = is_string($schedule) ? trim($schedule) : '';
        if ($key === '' && !empty($settings['schedule']) && is_string($settings['schedule'])) {
            $key = trim($settings['schedule']);
        }
        if ($key === '') {
            return '';
        }

        $cron = $this->wpra_schedule_to_cron($key);
        if ($cron !== '') {
            return $cron;
        }

        $schedules = wp_get_schedules();
        $interval  = isset($schedules[$key]['interval']) ? (int) $schedules[$key]['interval'] : 0;
        if ($interval <= 0) {
            $named    = array('hourly' => 3600, 'twicedaily' => 43200, 'daily' => 86400, 'weekly' => 604800);
            $interval = isset($named[$key]) ? $named[$key] : 0;
        }

        return $this->interval_to_cron($interval);
    }

    /**
     * Convert WP RSS Aggregator's own schedule string to a WPeMatico cron string.
     * Format: a count, a unit letter (m minutes, h hours, d days, w weeks, M months)
     * and an optional "HH:MM" time of day. Returns '' when the string is not in
     * that format, so the caller can fall back.
     *
     * A daily schedule that names its hour keeps it (WPRA runs it at that time);
     * everything else is rounded to the nearest WPeMatico preset.
     *
     * @param string $schedule
     * @return string
     */
    private function wpra_schedule_to_cron($schedule) {
        $matches = array();
        if (!preg_match('/^(\d+)([mhdwM])\b(?:.*?(\d{1,2}):(\d{1,2}))?/', $schedule, $matches)) {
            return '';
        }

        $count = max(1, (int) $matches[1]);
        $units = array('m' => MINUTE_IN_SECONDS, 'h' => HOUR_IN_SECONDS, 'd' => DAY_IN_SECONDS,
                       'w' => WEEK_IN_SECONDS, 'M' => 30 * DAY_IN_SECONDS);
        $unit  = $matches[2];

        if ($unit === 'd' && $count === 1 && isset($matches[3])) {
            $hour   = min(23, (int) $matches[3]);
            $minute = min(59, (int) $matches[4]);
            return sprintf('%d %d * * *', $minute, $hour);
        }

        return $this->interval_to_cron($count * $units[$unit]);
    }

    /**
     * Convert an interval in seconds to the nearest WPeMatico cron preset string.
     *
     * @param int $interval
     * @return string
     */
    private function interval_to_cron($interval) {
        if ($interval <= 0) {
            return '';
        }
        if ($interval <= 5 * 60) {
            return '* * * * *';
        }
        if ($interval <= 15 * 60) {
            return '0,15,30,45 * * * *';
        }
        if ($interval <= 30 * 60) {
            return '0,30 * * * *';
        }
        if ($interval <= 60 * 60) {
            return '0 * * * *';
        }
        if ($interval <= 3 * 3600) {
            return '0 0,3,6,9,12,15,18,21 * * *';
        }
        if ($interval <= 6 * 3600) {
            return '0 0,6,12,18 * * *';
        }
        if ($interval <= 12 * 3600) {
            return '0 0,12 * * *';
        }
        return '0 3 * * *';
    }

    /**
     * Normalize a per-run limit to WPeMatico's campaign_max.
     *
     * @param mixed $limit
     * @return int
     */
    private function normalize_limit($limit) {
        $limit = (int) $limit;
        return ($limit > 0) ? $limit : 5;
    }

    /**
     * Build the imported campaign title (no source-plugin name in it).
     *
     * @param string $name
     * @return string
     */
    private function campaign_title($name) {
        $name = ($name !== '') ? $name : __('Imported feed', 'wpematico');
        return $name . ' - ' . __('(imported)', 'wpematico');
    }

    /**
     * Campaign notes (post_excerpt) shown as the summary in the campaigns list.
     *
     * @return string
     */
    private function import_note() {
        return sprintf(
            /* translators: %s: import date/time. */
            __('Imported from WP RSS Aggregator on %s', 'wpematico'),
            wp_date(get_option('date_format') . ' ' . get_option('time_format'))
        );
    }

    /* --------------------------------------------------------------------- *
     *  Source table access
     * --------------------------------------------------------------------- */

    /**
     * Whether the v5 sources table exists.
     *
     * @return bool
     */
    private function table_exists() {
        global $wpdb;
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $this->sources_table));
        return ($found === $this->sources_table);
    }

    /**
     * All source rows (cached).
     *
     * @return array
     */
    private function get_sources() {
        if ($this->sources_cache !== null) {
            return $this->sources_cache;
        }
        if (!$this->table_exists()) {
            $this->sources_cache = array();
            return $this->sources_cache;
        }
        global $wpdb;
        // Table name is a trusted internal identifier (prefix + literal), not user input.
        $rows = $wpdb->get_results("SELECT * FROM {$this->sources_table} ORDER BY name ASC");
        $this->sources_cache = is_array($rows) ? $rows : array();
        return $this->sources_cache;
    }

    /**
     * All display rows (cached). WP RSS Aggregator stores displays in
     * {prefix}agg_displays: a display references sources via the pipe-delimited
     * `sources` column (e.g. |1|2|) and carries its own `settings` JSON, whose
     * `layout` key ('list'|'grid'|'et') is the visual style.
     *
     * @return array
     */
    private function get_displays() {
        if ($this->displays_cache !== null) {
            return $this->displays_cache;
        }
        global $wpdb;
        $table  = $wpdb->prefix . 'agg_displays';
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        if ($exists !== $table) {
            $this->displays_cache = array();
            return $this->displays_cache;
        }
        $rows = $wpdb->get_results("SELECT id, sources, settings FROM {$table}");
        $this->displays_cache = is_array($rows) ? $rows : array();
        return $this->displays_cache;
    }

    /**
     * Best-effort RSS Feed Reader layout for a source, derived from the WP RSS
     * Aggregator display(s) that include it. A display holds the layout, not a
     * source, so a source is only mapped when every display containing it agrees
     * on the same layout; otherwise (ambiguous, or none) we keep the 'list'
     * default the reader addon uses.
     *
     * @param int $source_id
     * @return string  list|grid|excerpt_thumbnail
     */
    private function get_source_layout($source_id) {
        $needle  = '|' . (int) $source_id . '|';
        $layouts = array();

        foreach ($this->get_displays() as $display) {
            $sources = isset($display->sources) ? (string) $display->sources : '';
            if (strpos($sources, $needle) === false) {
                continue;
            }
            $decoded = json_decode(isset($display->settings) ? (string) $display->settings : '', true);
            $wpra_layout = (is_array($decoded) && isset($decoded['layout'])) ? (string) $decoded['layout'] : 'list';
            $layouts[$this->map_display_layout($wpra_layout)] = true;
        }

        return (count($layouts) === 1) ? key($layouts) : 'list';
    }

    /**
     * Map a WP RSS Aggregator display layout id to an RSS Feed Reader layout.
     * WPRA: 'list' (core), 'grid' + 'et' (premium Excerpt & Thumbnail style).
     *
     * @param string $wpra_layout
     * @return string
     */
    private function map_display_layout($wpra_layout) {
        switch ($wpra_layout) {
            case 'grid':
                return 'grid';
            case 'et':
                return 'excerpt_thumbnail';
            case 'list':
            default:
                return 'list';
        }
    }

    /**
     * Decode a source's settings JSON into an array with safe defaults.
     *
     * @param mixed $settings_json
     * @return array
     */
    private function parse_settings($settings_json) {
        $settings = array();
        if (!empty($settings_json) && is_string($settings_json)) {
            $decoded = json_decode($settings_json, true);

            // ★ WP RSS Aggregator stores this column SLASHED ({\"importLimit\":15}),
            // and strips the slashes when reading it back (SourcesStore::rowToSource).
            // Decoding the raw column therefore fails on every real install and the
            // whole mapping silently falls back to the defaults below — the campaign
            // gets limit 5, no image settings, no taxonomies. Retry the way WPRA does.
            if (!is_array($decoded)) {
                $decoded = json_decode(stripslashes($settings_json), true);
            }

            if (is_array($decoded)) {
                $settings = $decoded;
            } else {
                $this->log('Could not parse source settings JSON: ' . json_last_error_msg(), 'warning');
            }
        }

        // Defaults mirror SourceSettings so callers can read keys unconditionally.
        return array_merge(array(
            'postType'         => '',
            'postStatus'       => 'publish',
            'postFormat'       => 'standard',
            'commentsOpen'     => true,
            'importLimit'      => 0,
            'downloadImages'   => false,
            'assignFtImage'    => true,
            'fallbackAuthorId' => 0,
            'whichPostDate'    => 'published_date',
            'taxonomies'       => array(),
            'schedule'         => '',
        ), $settings);
    }

    /**
     * Message shown when the v5 table is absent (e.g. site still on v4 data).
     *
     * @return string
     */
    private function missing_table_message() {
        global $wpdb;
        $has_v4 = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wprss_feed' AND post_status != 'auto-draft'"
        );
        if ($has_v4 > 0) {
            return __('WP RSS Aggregator feeds are still in the old (v4) format. Open WP RSS Aggregator and let it migrate your feeds to v5 first, then run this migration.', 'wpematico');
        }
        return __('No WP RSS Aggregator feed sources found.', 'wpematico');
    }
}
