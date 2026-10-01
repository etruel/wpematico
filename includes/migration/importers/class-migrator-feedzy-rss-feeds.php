<?php
/**
 * Feedzy RSS Feeds importer.
 *
 * Each Feedzy import job (CPT feedzy_imports) maps to one WPeMatico campaign.
 * The job's `source` meta holds either direct feed URL(s) or a reference to a
 * Feed Group (CPT feedzy_categories) that bundles several feeds. Reads the real
 * Feedzy `import_*` meta keys and maps them to real WPeMatico campaign fields,
 * persisting through the base class (check_campaigndata + update_campaign).
 *
 * @package WPeMatico
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPeMatico_Migrator_Feedzy extends WPeMatico_Migrator_Base {

    /**
     * CPT for import jobs.
     *
     * @var string
     */
    private $import_post_type = 'feedzy_imports';

    /**
     * CPT for feed groups.
     *
     * @var string
     */
    private $feed_groups_post_type = 'feedzy_categories';

    /**
     * Feed group handling: 'single' = one campaign with every feed of the group
     * (faithful 1:1, default), 'multiple' = one campaign per feed of the group.
     *
     * @var string
     */
    private $feed_group_mode = 'single';

    /**
     * Cache of feed group posts.
     *
     * @var array|null
     */
    private $feed_groups_cache = null;

    /**
     * Cache of optional-feature source-data presence, keyed by feature.
     *
     * @var array
     */
    private $source_has_cache = array();

    /**
     * Cache of import job posts.
     *
     * @var WP_Post[]|null
     */
    private $jobs_cache = null;

    /**
     * How many levels of feed group are followed when a group lists another group.
     */
    const MAX_GROUP_DEPTH = 3;

    /**
     * Items per run Feedzy imports when a job has no limit of its own
     * (feedzy-rss-feeds-import.php: empty import_feed_limit → 10).
     */
    const FEEDZY_DEFAULT_LIMIT = 10;

    /**
     * Constructor.
     *
     * @param array $plugin_data
     */
    public function __construct($plugin_data) {
        parent::__construct($plugin_data);
        if (!empty($plugin_data['post_type'])) {
            $this->import_post_type = $plugin_data['post_type'];
        }
        if (!empty($plugin_data['feed_groups_post_type'])) {
            $this->feed_groups_post_type = $plugin_data['feed_groups_post_type'];
        }
        if (isset($plugin_data['feed_group_mode'])) {
            $this->set_feed_group_mode($plugin_data['feed_group_mode']);
        }
    }

    /**
     * Set the feed-group handling mode. Accepts 'single' or 'multiple'
     * (the UI sends 'merge' for single-campaign — treat it as 'single').
     *
     * @param string $mode
     */
    public function set_feed_group_mode($mode) {
        if ($mode === 'merge' || $mode === 'single') {
            $this->feed_group_mode = 'single';
        } elseif ($mode === 'multiple') {
            $this->feed_group_mode = 'multiple';
        }
    }

    /* --------------------------------------------------------------------- *
     *  Public API (migrate / preview / counts)
     * --------------------------------------------------------------------- */

    /**
     * Run the migration.
     */
    public function migrate() {
        $this->log('Starting Feedzy migration (mode: ' . $this->feed_group_mode . ').', 'info');

        $jobs = $this->get_import_jobs();
        if (empty($jobs)) {
            return $this->format_result(false, __('No import jobs found to migrate.', 'wpematico'));
        }

        foreach ($jobs as $job) {
            $source    = get_post_meta($job->ID, 'source', true);
            $feed_urls = $this->get_clean_feed_urls($source);

            if (empty($feed_urls)) {
                $this->log('No valid feed URLs for job: ' . $job->post_title, 'warning');
                continue;
            }

            if ($this->is_feed_group($source) && $this->feed_group_mode === 'multiple' && count($feed_urls) > 1) {
                $total = count($feed_urls);
                foreach ($feed_urls as $i => $url) {
                    $this->migrate_job($job, array($url), ($i + 1), $total);
                }
            } else {
                $this->migrate_job($job, $feed_urls);
            }
        }

        return $this->format_result(
            true,
            /* translators: %d: number of migrated campaigns. */
            sprintf(__('Successfully migrated %d campaigns.', 'wpematico'), $this->migrated_count),
            array(
                'campaigns' => $this->created_campaigns,
                'notices'   => $this->build_import_notices(),
            )
        );
    }

    /**
     * Preview of what will be migrated.
     */
    public function get_preview() {
        $preview = array();

        foreach ($this->get_import_jobs() as $job) {
            $source        = get_post_meta($job->ID, 'source', true);
            $feed_urls     = $this->get_clean_feed_urls($source);
            $is_feed_group = $this->is_feed_group($source);
            $post_type     = get_post_meta($job->ID, 'import_post_type', true);
            $multiple      = ($is_feed_group && $this->feed_group_mode === 'multiple' && count($feed_urls) > 1);

            $preview[] = array(
                'id'                  => $job->ID,
                'title'               => $job->post_title,
                'source'              => $source,
                'source_type'         => $is_feed_group ? 'feed_group' : 'direct_url',
                'feed_url'            => implode('<br>', array_map('esc_url', $feed_urls)),
                'feed_urls'           => $feed_urls,
                'feeds_count'         => count($feed_urls),
                'post_type'           => $post_type ? $post_type : 'post',
                'status'              => $job->post_status,
                'is_feed_group'       => $is_feed_group,
                'estimated_campaigns' => $multiple ? count($feed_urls) : 1,
            );
        }

        return $preview;
    }

    /**
     * Count of campaigns that would be created.
     */
    public function get_items_count() {
        $count = 0;
        foreach ($this->get_import_jobs() as $job) {
            $source    = get_post_meta($job->ID, 'source', true);
            $feed_urls = $this->get_clean_feed_urls($source);
            if (empty($feed_urls)) {
                continue;
            }
            if ($this->is_feed_group($source) && $this->feed_group_mode === 'multiple') {
                $count += count($feed_urls);
            } else {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Summary used by the preview modal.
     */
    public function get_migration_summary() {
        $summary = array(
            'total_import_jobs'            => 0,
            'direct_feeds'                 => 0,
            'feed_groups'                  => 0,
            'total_feed_urls'              => 0,
            'estimated_campaigns_single'   => 0,
            'estimated_campaigns_multiple' => 0,
            'new_campaigns_single'         => 0,
            'existing_campaigns_single'    => 0,
        );

        foreach ($this->get_import_jobs() as $job) {
            $source    = get_post_meta($job->ID, 'source', true);
            $feed_urls = $this->get_clean_feed_urls($source);

            $summary['total_import_jobs']++;
            $summary['total_feed_urls']            += count($feed_urls);
            $summary['estimated_campaigns_single'] += 1;

            // In the default (merge) mode one job = one campaign, keyed by this
            // source id — so we can tell whether it will be created or updated.
            if ($this->get_existing_campaign_id($job->ID . '_feedzy')) {
                $summary['existing_campaigns_single']++;
            } else {
                $summary['new_campaigns_single']++;
            }

            if ($this->is_feed_group($source)) {
                $summary['feed_groups']++;
                $summary['estimated_campaigns_multiple'] += max(1, count($feed_urls));
            } else {
                $summary['direct_feeds']++;
                $summary['estimated_campaigns_multiple'] += 1;
            }
        }

        return $summary;
    }

    /* --------------------------------------------------------------------- *
     *  Migration of a single job
     * --------------------------------------------------------------------- */

    /**
     * Migrate one import job into a WPeMatico campaign.
     *
     * @param WP_Post $job
     * @param array   $feed_urls    Clean feed URLs for this campaign.
     * @param int     $feed_index   When splitting a group, the 1-based feed index (0 = whole job).
     * @param int     $total_feeds  Total feeds in the group (for the title/description).
     * @return int|false
     */
    private function migrate_job($job, $feed_urls, $feed_index = 0, $total_feeds = 0) {
        $data = $this->build_campaign_data($job, $feed_urls, $feed_index, $total_feeds);

        $source_id   = $job->ID . '_feedzy' . ($feed_index ? '_feed_' . $feed_index : '');
        $existing_id = $this->get_existing_campaign_id($source_id);

        if ($existing_id) {
            $campaign_id = $this->update_existing_campaign($existing_id, $data);
        } else {
            $campaign_id = $this->create_wpematico_campaign($data);
        }

        if ($campaign_id) {
            // Original job title kept for the provenance tooltip in the campaigns list.
            $this->store_provenance($campaign_id, $source_id, $job->post_title);
            // Keyed by the job, not by $source_id: a split feed group produces
            // several campaigns from the same job, whose posts are one single set.
            $this->adopt_published_posts($campaign_id, $job->ID, $feed_urls);
        }

        return $campaign_id;
    }

    /* --------------------------------------------------------------------- *
     *  Already-published posts
     * --------------------------------------------------------------------- */

    /**
     * Feedzy stamps every post it creates with the job that produced it
     * (`feedzy_job`) and the original item URL (`feedzy_item_url`), both written
     * in feedzy-rss-feeds-import.php right after the insert.
     *
     * @param string|int $source_key Feedzy job post ID.
     * @return array
     */
    protected function get_published_posts_sources($source_key) {
        return array(
            array(
                'meta_key'   => 'feedzy_job',
                'meta_value' => (int) $source_key,
                'url_metas'  => array('feedzy_item_url'),
            ),
        );
    }

    /**
     * Total posts Feedzy has published on this site.
     *
     * @return int
     */
    public function get_published_posts_count() {
        return $this->count_posts_with_meta_keys(array('feedzy_job'));
    }

    /* --------------------------------------------------------------------- *
     *  Provenance + optional features
     * --------------------------------------------------------------------- */

    /**
     * Feedzy menu glyph (broadcast/RSS waves), monochrome so CSS can dim it.
     *
     * @return string
     */
    public function get_icon_svg() {
        // Feedzy's actual WP admin menu icon (broadcast circle), recolored to currentColor.
        return '<svg viewBox="0 0 77 77" width="16" height="16" xmlns="http://www.w3.org/2000/svg" fill="currentColor" aria-hidden="true">'
            . '<g transform="translate(-196,-957)"><path d="M234.5,1034 C213.237037,1034 196,1016.76296 196,995.5 C196,974.237037 213.237037,957 234.5,957 C255.762963,957 273,974.237037 273,995.5 C273,1016.76296 255.762963,1034 234.5,1034 Z M238.389087,1003.61091 C236.241256,1001.46308 232.758851,1001.46297 230.610943,1003.61088 C228.463035,1005.75879 228.463021,1009.2412 230.610913,1011.38909 C232.758804,1013.53698 236.241149,1013.53703 238.389057,1011.38912 C240.536965,1009.24121 240.536979,1005.7588 238.389087,1003.61091 Z M251.199196,996.524269 C241.71601,988.013409 227.294143,988.004307 217.800859,996.524214 C217.240496,997.027079 217.222108,997.899777 217.75448,998.43215 L220.551879,1001.22955 C221.041594,1001.71926 221.829967,1001.75226 222.350408,1001.29537 C229.282401,995.21117 239.70281,995.198209 246.649546,1001.29541 C247.170047,1001.75225 247.95842,1001.71925 248.448075,1001.22959 L251.245465,998.432205 C251.777952,997.899834 251.759561,997.027136 251.199196,996.524269 Z M259.517481,988.062818 C245.754662,975.25391 224.312531,975.191374 210.482464,988.062873 C209.95096,988.557557 209.940845,989.396689 210.454222,989.910066 L213.185489,992.641333 C213.675569,993.131413 214.462824,993.141924 214.972622,992.672355 C226.281029,982.254786 243.720804,982.256415 259.517481,988.062818 Z"/></g></svg>';
    }

    /**
     * @return string
     */
    public function get_source_label() {
        return 'Feedzy';
    }

    /**
     * Optional imports offered for Feedzy. Each is shown only when the source jobs
     * actually contain that data; enabled only when our matching addon is active.
     *
     * @return array
     */
    public function get_optional_features() {
        return array(
            'filters' => array(
                'label'           => __('Import keyword filters', 'wpematico'),
                'note'            => __('Maps Feedzy include/exclude keywords to WPeMatico keyword filters.', 'wpematico'),
                'addon_const'     => 'WPEMATICOPRO_VERSION',
                'addon_name'      => 'WPeMatico Professional',
                'has_source_data' => $this->source_has('filters'),
                'post_note'       => __('<strong>Keyword Filtering was enabled in the WPeMatico Professional settings</strong> so the imported filters run.', 'wpematico'),
            ),
            'fallback_image' => array(
                'label'           => __('Import fallback featured image', 'wpematico'),
                'note'            => __('Uses Feedzy\'s default thumbnail as the campaign default featured image.', 'wpematico'),
                'addon_const'     => 'WPEMATICOPRO_VERSION',
                'addon_name'      => 'WPeMatico Professional',
                'has_source_data' => $this->source_has('fallback_image'),
            ),
            'translation' => array(
                'label'           => __('Import auto-translation', 'wpematico'),
                'note'            => __('Recreates Feedzy auto-translation with the Polyglot addon.', 'wpematico'),
                'addon_const'     => 'WPE_POLYGLOT_VER',
                'addon_name'      => 'WPeMatico Polyglot',
                'has_source_data' => $this->source_has('translation'),
                'post_note'       => __('WPeMatico <strong>Polyglot needs its translation API configured in Settings</strong> for the imported campaigns to translate.', 'wpematico'),
            ),
            'rewrite' => array(
                'label'           => __('Import content rewrite', 'wpematico'),
                'note'            => __('Recreates Feedzy content rewrite with the GPT Spinner addon.', 'wpematico'),
                'addon_const'     => 'WPE_GPT_SPINNER_VER',
                'addon_name'      => 'WPeMatico GPT Spinner',
                'has_source_data' => $this->source_has('rewrite'),
                'post_note'       => __('WPeMatico <strong>GPT Spinner needs its API configured in Settings</strong>. Check the model: Etruel Rewriter for SpinnerChief/WordAI, GPT Machine for ChatGPT.', 'wpematico'),
            ),
        );
    }

    /**
     * Whether ANY import job carries the data behind an optional feature.
     * Cached per feature for the duration of the request.
     *
     * @param string $feature
     * @return bool
     */
    private function source_has($feature) {
        if (isset($this->source_has_cache[$feature])) {
            return $this->source_has_cache[$feature];
        }

        $found = false;
        foreach ($this->get_import_jobs() as $job) {
            if ($this->job_has_feature($job->ID, $feature)) {
                $found = true;
                break;
            }
        }

        // The fallback image can also come from Feedzy's GLOBAL default thumbnail,
        // which no single job carries: count it so the option still surfaces.
        if (!$found && $feature === 'fallback_image') {
            $found = ($this->global_thumbnail_id() > 0);
        }

        $this->source_has_cache[$feature] = $found;
        return $found;
    }

    /**
     * Whether a single job carries the data behind an optional feature.
     *
     * @param int    $job_id
     * @param string $feature
     * @return bool
     */
    private function job_has_feature($job_id, $feature) {
        switch ($feature) {
            case 'filters':
                $kw = $this->keyword_filter_values($job_id);
                return ($kw['inc'] !== '' || $kw['exc'] !== '');

            case 'fallback_image':
                $thumb = get_post_meta($job_id, 'default_thumbnail_id', true);
                return (!empty($thumb) && (int) $thumb > 0);

            case 'translation':
                return ($this->translation_target_lang($job_id) !== '');

            case 'rewrite':
                return $this->has_rewrite_tags($job_id);
        }
        return false;
    }

    /**
     * Build the WPeMatico campaign field array (real keys) from a Feedzy job.
     *
     * @param WP_Post $job
     * @param array   $feed_urls
     * @param int     $feed_index
     * @param int     $total_feeds
     * @return array
     */
    private function build_campaign_data($job, $feed_urls, $feed_index = 0, $total_feeds = 0) {
        // Real Feedzy job meta.
        $import_post_type      = get_post_meta($job->ID, 'import_post_type', true);
        $import_post_status    = get_post_meta($job->ID, 'import_post_status', true);
        $import_post_content   = get_post_meta($job->ID, 'import_post_content', true);
        $import_post_excerpt   = get_post_meta($job->ID, 'import_post_excerpt', true);
        $import_post_date      = get_post_meta($job->ID, 'import_post_date', true);
        $import_post_author    = get_post_meta($job->ID, 'import_post_author', true);
        $import_post_term      = get_post_meta($job->ID, 'import_post_term', true);
        $import_featured_img   = get_post_meta($job->ID, 'import_post_featured_img', true);
        $import_feed_limit     = $this->job_feed_limit($job->ID);
        $import_remove_html    = get_post_meta($job->ID, 'import_remove_html', true);
        $import_external_image = get_post_meta($job->ID, 'import_use_external_image', true);

        list($categories, $tags) = $this->map_terms($import_post_term);

        $template = $this->parse_feedzy_template($import_post_content);

        $title = $job->post_title;
        if ($feed_index) {
            /* translators: 1: feed index, 2: total feeds. */
            $title .= ' - ' . sprintf(__('Feed %1$d of %2$d', 'wpematico'), $feed_index, $total_feeds);
        }
        // Mark migrated campaigns without referencing the source plugin in the name.
        $title .= ' - ' . __('(imported)', 'wpematico');

        // Campaign notes (post_excerpt), shown as the campaign description in the list.
        $excerpt = sprintf(
            /* translators: %s: import date/time. */
            __('Imported from Feedzy on %s', 'wpematico'),
            wp_date(get_option('date_format') . ' ' . get_option('time_format'))
        );

        $cache_images = ($import_external_image !== 'yes'); // download unless Feedzy used external images

        $data = array(
            'campaign_title'          => $title,
            // WPeMatico shows the campaign's EXCERPT (post_excerpt) as its notes/summary;
            // the post_content (description) is unused for campaigns, so we only set the excerpt.
            'campaign_excerpt'        => $excerpt,
            'campaign_feeds'          => array_values($feed_urls),
            'campaign_max'            => $this->normalize_limit($import_feed_limit),
            'campaign_author'         => $this->resolve_author($import_post_author),
            'campaign_customposttype' => $import_post_type ? $this->convert_post_type($import_post_type) : 'post',
            'campaign_posttype'       => $import_post_status ? $this->convert_post_status($import_post_status) : 'publish',
            'campaign_feeddate'       => $this->uses_item_date($import_post_date) ? 1 : 0,
            'campaign_striphtml'      => ($import_remove_html === 'yes') ? 1 : 0,
            'campaign_get_excerpt'    => $this->has_template_value($import_post_excerpt) ? 1 : 0,

            // Per-campaign image settings (so they are honored, not the global ones).
            'campaign_no_setting_img' => 1,
            'campaign_imgcache'       => $cache_images ? 1 : 0,
            'campaign_featuredimg'    => $this->has_template_value($import_featured_img) ? 1 : 0,
        );

        // Only switch to a custom post template when it differs from WPeMatico's
        // default (plain item content); keeps simple jobs as faithful 1:1 imports.
        if ($template !== '' && preg_replace('/\s+/', '', $template) !== '{content}') {
            $data['campaign_template']        = $template;
            $data['campaign_enable_template'] = 1;
        }

        // Import the schedule only when the job had one; leave WPeMatico's default otherwise.
        // Stored even though the campaign stays deactivated, so it is preserved.
        $cron = $this->map_cron($job->ID);
        if ($cron !== '') {
            $data['cron'] = $cron;
        }

        if (!empty($categories)) {
            // WPeMatico reads the standard post_category field in check_campaigndata.
            $data['post_category'] = $this->get_categories_array($categories);
        }
        if (!empty($tags)) {
            $data['campaign_tags'] = implode(',', $tags);
        }

        // Optional, addon-gated imports (only when the user ticked them AND the
        // matching WPeMatico addon is active — see option_enabled()).
        if ($this->option_enabled('filters')) {
            $this->map_filters($job->ID, $data);
        }
        if ($this->option_enabled('fallback_image')) {
            $this->map_fallback_image($job->ID, $data);
        }
        if ($this->option_enabled('translation')) {
            $this->map_translation($job->ID, $data);
        }
        if ($this->option_enabled('rewrite')) {
            $this->map_rewrite($job->ID, $data);
        }

        // Cosmetic (PRO): name the feed after the source job when the campaign maps
        // to a single feed (a plain job, or one split-off group feed). Merged
        // multi-feed groups have no per-feed name, so leave those to PRO's domain fallback.
        if (count($data['campaign_feeds']) === 1) {
            $this->map_feed_names($data, array($job->post_title));
        }

        return $data;
    }

    /* --------------------------------------------------------------------- *
     *  Optional addon-gated mappers
     *  Feedzy PRO features → the WPeMatico addon that reproduces them. Keys are
     *  the real Feedzy 3.1.0 / free 5.2.x import-job metas. Still UNVERIFIED
     *  against a running PRO install (see the feedzy-pro-real-keys reference).
     * --------------------------------------------------------------------- */

    /**
     * Feedzy include/exclude keyword filters for a job. keywords_title (include)
     * and keywords_ban (exclude) are comma-separated strings; PRO adds regex via
     * feedzy_filter_custom_pattern at runtime. Returned as trimmed CSV strings.
     *
     * @param int $job_id
     * @return array{inc:string, exc:string}
     */
    private function keyword_filter_values($job_id) {
        $to_csv = function ($raw) {
            if (is_array($raw)) {
                $raw = implode(',', $raw);
            }
            $parts = array_filter(array_map('trim', explode(',', (string) $raw)), 'strlen');
            return implode(',', $parts);
        };
        return array(
            'inc' => $to_csv(get_post_meta($job_id, 'keywords_title', true)),
            'exc' => $to_csv(get_post_meta($job_id, 'keywords_ban', true)),
        );
    }

    /**
     * Feedzy auto-translation target language for a job ('' when off). Feedzy
     * stores it as the per-job meta import_feed_language; its presence is what
     * turns translation on.
     *
     * @param int $job_id
     * @return string
     */
    private function translation_target_lang($job_id) {
        return sanitize_text_field((string) get_post_meta($job_id, 'import_feed_language', true));
    }

    /**
     * Whether a job routes any field through Feedzy's rewrite/paraphrase service.
     * Rewrite is a magic tag (#title_feedzy_rewrite / #content_feedzy_rewrite /
     * #full_content_feedzy_rewrite) inside the field-mapping templates, not a flag.
     *
     * @param int $job_id
     * @return bool
     */
    private function has_rewrite_tags($job_id) {
        $t = $this->rewrite_tags_present($job_id);
        return ($t['title'] || $t['content']);
    }

    /**
     * Which rewrite signals a job carries, across its field templates:
     *   title   = Feedzy's #title_feedzy_rewrite tag
     *   content = #content_feedzy_rewrite / #full_content_feedzy_rewrite tag
     *   openai  = an OpenAI/ChatGPT rewrite is in play (vs Feedzy's own rewriter)
     *
     * @param int $job_id
     * @return array{title:bool, content:bool, openai:bool}
     */
    private function rewrite_tags_present($job_id) {
        $all = '';
        foreach (array('import_post_title', 'import_post_content', 'import_post_excerpt') as $key) {
            $all .= ' ' . (string) get_post_meta($job_id, $key, true);
        }
        return array(
            'title'   => (stripos($all, 'title_feedzy_rewrite') !== false),
            'content' => (stripos($all, 'content_feedzy_rewrite') !== false),
            'openai'  => (stripos($all, 'openai') !== false || stripos($all, 'chatgpt') !== false),
        );
    }

    /**
     * Map Feedzy keyword filters to WPeMatico Professional keyword filters.
     * Feedzy matches comma-separated keywords as "any", against title+content.
     *
     * @param int   $job_id
     * @param array $data (by reference)
     */
    private function map_filters($job_id, &$data) {
        $kw = $this->keyword_filter_values($job_id);
        if ($kw['inc'] !== '') {
            $data['campaign_kwordf_inc']        = $kw['inc'];
            $data['campaign_kwordf_inc_tit']    = 1;
            $data['campaign_kwordf_inc_con']    = 1;
            $data['campaign_kwordf_inc_anyall'] = 'anyword';
        }
        if ($kw['exc'] !== '') {
            $data['campaign_kwordf_exc']        = $kw['exc'];
            $data['campaign_kwordf_exc_tit']    = 1;
            $data['campaign_kwordf_exc_con']    = 1;
            $data['campaign_kwordf_exc_anyall'] = 'anyword';
        }
    }

    /**
     * Map Feedzy's default thumbnail to WPeMatico Professional's default featured
     * image. Prefers the per-job thumbnail and falls back to Feedzy's global
     * default when the job has none. Both live in this site's media library, so
     * the attachment ID is reused directly.
     *
     * @param int   $job_id
     * @param array $data (by reference)
     */
    private function map_fallback_image($job_id, &$data) {
        $attach_id = (int) get_post_meta($job_id, 'default_thumbnail_id', true);
        if ($attach_id <= 0) {
            // No per-job thumbnail: inherit Feedzy's global fallback image.
            $attach_id = $this->global_thumbnail_id();
        }
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
     * Map Feedzy auto-translation to the Polyglot addon.
     *
     * @param int   $job_id
     * @param array $data (by reference)
     */
    private function map_translation($job_id, &$data) {
        $lang = $this->translation_target_lang($job_id);
        if ($lang === '') {
            return;
        }
        $data['campaign_polyglot'] = 1;
        $data['polyglot_target']   = $lang;
    }

    /**
     * Map Feedzy content rewrite/paraphrase to the GPT Spinner addon.
     *
     * @param int   $job_id
     * @param array $data (by reference)
     */
    private function map_rewrite($job_id, &$data) {
        $data['campaign_gpt_spinner'] = 1;
        $tags = $this->rewrite_tags_present($job_id);

        // Action: rewrite title+content vs content only, mirroring the tags used.
        $data['gpt_spinner_rewrite_options'] = ($tags['title'] && $tags['content'])
            ? 'rewrite_title_and_content'
            : 'rewrite_content';

        // Model: Feedzy's own hosted rewriter (#..._feedzy_rewrite) maps to Etruel
        // Rewriter; an OpenAI/ChatGPT rewrite maps to GPT Machine. Both are models
        // under the same API family. Heuristic — UNVERIFIED against real PRO data.
        $data['gpt_spinner_api']   = 'ai_etruel_rewriter_api';
        $data['gpt_spinner_model'] = $tags['openai'] ? 'gpt_machine_api' : 'ai_etruel_rewriter_api';
    }

    /* --------------------------------------------------------------------- *
     *  Field mapping helpers
     * --------------------------------------------------------------------- */

    /**
     * Feedzy's global "general" settings (option `feedzy-rss-feeds-settings`).
     * A few of these are defaults that individual import jobs inherit implicitly
     * (global fallback thumbnail, global cron schedule); we resolve them into the
     * per-campaign fields so a migrated campaign reproduces what Feedzy actually
     * did, without ever writing to WPeMatico's own global settings.
     *
     * @return array
     */
    private function feedzy_general_settings() {
        $settings = get_option('feedzy-rss-feeds-settings', array());
        return (is_array($settings) && !empty($settings['general']) && is_array($settings['general']))
            ? $settings['general']
            : array();
    }

    /**
     * Feedzy's global fallback featured-image attachment ID (0 when unset).
     *
     * @return int
     */
    private function global_thumbnail_id() {
        $general = $this->feedzy_general_settings();
        return isset($general['default-thumbnail-id']) ? (int) $general['default-thumbnail-id'] : 0;
    }

    /**
     * Feedzy's global default cron schedule key ('' when unset).
     *
     * @return string
     */
    private function global_cron_schedule() {
        $general = $this->feedzy_general_settings();
        return (isset($general['fz_cron_schedule']) && is_string($general['fz_cron_schedule']))
            ? $general['fz_cron_schedule']
            : '';
    }

    /**
     * Feedzy's per-run item limit for a job.
     *
     * ★ The metabox field is name="…[import_feed_limit]" only on PRO; without it
     * Feedzy renders the very same input as "…[import_feed_limitlocked]" (the field
     * is a paid feature), so on a free install the number the user typed and still
     * sees is stored under that second key. Reading only the first one left every
     * free job without a limit. Feedzy's importer itself falls back to 10.
     *
     * @param int $job_id
     * @return int
     */
    private function job_feed_limit($job_id) {
        $limit = (int) get_post_meta($job_id, 'import_feed_limit', true);
        if ($limit > 0) {
            return $limit;
        }
        $locked = (int) get_post_meta($job_id, 'import_feed_limitlocked', true);
        return ($locked > 0) ? $locked : self::FEEDZY_DEFAULT_LIMIT;
    }

    /**
     * Normalize Feedzy's per-run limit to WPeMatico's campaign_max.
     *
     * @param mixed $limit
     * @return int
     */
    private function normalize_limit($limit) {
        $limit = (int) $limit;
        return ($limit > 0) ? $limit : self::FEEDZY_DEFAULT_LIMIT;
    }

    /**
     * Map a Feedzy per-job cron schedule to a WPeMatico crontab string.
     * Returns '' when the job has no schedule (keeps WPeMatico's default).
     *
     * @param int $job_id
     * @return string
     */
    private function map_cron($job_id) {
        $key = get_post_meta($job_id, 'fz_cron_schedule', true);
        if (empty($key)) {
            // Job has no schedule of its own: inherit Feedzy's global default schedule.
            $key = $this->global_cron_schedule();
        }
        if (empty($key)) {
            return '';
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
     * Resolve the Feedzy author setting to a WordPress user ID.
     *
     * @param mixed $author
     * @return int
     */
    private function resolve_author($author) {
        if (empty($author) || in_array($author, array('no', 'none', 'admin'), true)) {
            return $this->get_default_author();
        }
        if (is_numeric($author)) {
            // Honor the stored user only if it still exists; otherwise fall back.
            return get_user_by('id', (int) $author) ? (int) $author : $this->get_default_author();
        }
        // Feedzy dynamic author tags (e.g. [#item_author]) cannot map to a fixed user.
        if (strpos((string) $author, '[#') !== false) {
            return $this->get_default_author();
        }
        // A named author: use it only if such a user already exists. Never create a
        // new WordPress user as a side effect of a migration.
        $login = sanitize_text_field($author);
        $user  = get_user_by('login', $login);
        if (!$user) {
            $user = get_user_by('slug', sanitize_title($login));
        }
        return $user ? (int) $user->ID : $this->get_default_author();
    }

    /**
     * Map Feedzy's `import_post_term` ("{taxonomy}_{term_id}" CSV) to WPeMatico
     * category IDs and tag names. Dynamic tags ([#item_*], [#auto_categories]) skipped.
     *
     * @param mixed $import_post_term
     * @return array{0: int[], 1: string[]}  [category IDs, tag names]
     */
    private function map_terms($import_post_term) {
        $categories = array();
        $tags       = array();

        if (empty($import_post_term) || $import_post_term === 'none') {
            return array($categories, $tags);
        }

        foreach (explode(',', $import_post_term) as $term) {
            $term = trim($term);
            if ($term === '' || strpos($term, '[#') !== false) {
                continue;
            }
            $parts   = explode('_', $term);
            $term_id = (int) array_pop($parts);
            $taxonomy = implode('_', $parts);
            if (!$term_id || $taxonomy === '') {
                continue;
            }

            if ($taxonomy === 'category') {
                $categories[] = $term_id;
            } elseif ($taxonomy === 'post_tag') {
                $t = get_term($term_id, 'post_tag');
                if ($t && !is_wp_error($t)) {
                    $tags[] = $t->name;
                }
            } else {
                $this->log('Skipped term from unsupported taxonomy "' . $taxonomy . '".', 'info');
            }
        }

        return array($categories, $tags);
    }

    /**
     * Whether a Feedzy date template imports the original feed item date.
     *
     * @param mixed $date_template
     * @return bool
     */
    private function uses_item_date($date_template) {
        if (empty($date_template)) {
            return true; // Feedzy default keeps the item date.
        }
        return (stripos((string) $date_template, 'item_date') !== false);
    }

    /**
     * Whether a Feedzy template field holds a real (non-empty, non-"none") value.
     *
     * @param mixed $value
     * @return bool
     */
    private function has_template_value($value) {
        if (empty($value) || !is_string($value)) {
            return false;
        }
        $value = trim($value);
        return ($value !== '' && strtolower($value) !== 'none');
    }

    /**
     * Convert a Feedzy content template to a WPeMatico template.
     *
     * Handles both the plain `[#item_xxx]` form and the URL-encoded JSON (TipTap)
     * blob the Feedzy editor stores. Best-effort: when a complex blob is reduced
     * to its tag sequence, a note is logged so the user can review.
     *
     * @param mixed $raw
     * @return string
     */
    private function parse_feedzy_template($raw) {
        if (empty($raw) || !is_string($raw)) {
            return '';
        }

        $text = $raw;

        // The editor blob is URL-encoded JSON; decode it first.
        if (strpos($text, '%5B') !== false || strpos($text, '%7B') !== false || strpos($text, '%22') !== false) {
            $text = urldecode($text);
        }

        // TipTap nodes encode tags as {"tag":"item_content"}: pull them out in order.
        if (stripos($text, '"tag"') !== false) {
            if (preg_match_all('/"tag"\s*:\s*"([a-z0-9_]+)"/i', $text, $m) && !empty($m[1])) {
                $text = implode(' ', array_map(function ($t) {
                    return '[#' . $t . ']';
                }, $m[1]));
                $this->log('Feedzy content template was stored as an editor blob and simplified to its tags; review the campaign template if needed.', 'info');
            }
        }

        // Map Feedzy tags to WPeMatico tags.
        $map = array(
            '[#item_full_content]'      => '{content}',
            '[#item_content]'           => '{content}',
            '[#item_description]'       => '{content}',
            '[#translated_full_content]' => '{content}',
            '[#translated_content]'     => '{content}',
            '[#translated_description]' => '{content}',
            '[#item_title]'             => '{title}',
            '[#translated_title]'       => '{title}',
            '[#item_image]'             => '{image}',
            '[#item_url]'               => '{permalink}',
            '[#item_source]'            => '{feedurl}',
            '[#item_author]'            => '{author}',
            '[#item_date]'              => '{item_date}',
        );
        $text = str_ireplace(array_keys($map), array_values($map), $text);

        // Drop any remaining unknown [#...] tags so no literal junk is left behind.
        $text = preg_replace('/\[#[a-z0-9_]+\]/i', '', $text);

        return trim($text);
    }

    /* --------------------------------------------------------------------- *
     *  Source / feed-group resolution
     * --------------------------------------------------------------------- */

    /**
     * All Feedzy import jobs.
     *
     * @return WP_Post[]
     */
    private function get_import_jobs() {
        if ($this->jobs_cache !== null) {
            return $this->jobs_cache;
        }
        $query = new WP_Query(array(
            'post_type'      => $this->import_post_type,
            'post_status'    => array('publish', 'draft', 'future', 'pending'),
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        ));
        $this->jobs_cache = $query->posts;
        return $this->jobs_cache;
    }

    /**
     * All Feedzy feed groups (cached).
     *
     * @return WP_Post[]
     */
    private function get_feed_groups() {
        if ($this->feed_groups_cache !== null) {
            return $this->feed_groups_cache;
        }
        $query = new WP_Query(array(
            'post_type'      => $this->feed_groups_post_type,
            'post_status'    => array('publish', 'draft'),
            'posts_per_page' => -1,
            'no_found_rows'  => true,
        ));
        $this->feed_groups_cache = $query->posts;
        return $this->feed_groups_cache;
    }

    /**
     * Whether a job `source` references at least one feed group.
     *
     * A source is a COMMA-SEPARATED list that can mix both kinds of entry, e.g.
     * "http://example.com/feed, news-sites" — so the test has to be per entry, not
     * on the string as a whole (see get_feed_urls_from_source).
     *
     * @param string $source
     * @return bool
     */
    private function is_feed_group($source) {
        foreach ($this->split_source($source) as $part) {
            if (stripos($part, 'http') !== 0 && $this->find_feed_group($part)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Split a job `source` into its individual entries (URL or group name).
     *
     * @param string $source
     * @return string[]
     */
    private function split_source($source) {
        if (empty($source) || !is_string($source)) {
            return array();
        }
        return array_values(array_filter(array_map('trim', explode(',', $source)), 'strlen'));
    }

    /**
     * Find a feed group by the name Feedzy stores in `source`. Feedzy resolves it
     * with get_page_by_path() (the slug) and falls back to a title lookup, so both
     * are matched here, plus the slugified title for names typed with spaces.
     *
     * @param string $name
     * @return WP_Post|null
     */
    private function find_feed_group($name) {
        $slug = sanitize_title($name);
        foreach ($this->get_feed_groups() as $group) {
            if ($group->post_name === $name || $group->post_title === $name || $group->post_name === $slug) {
                return $group;
            }
        }
        return null;
    }

    /**
     * Resolve a job `source` to a list of validated feed URLs (expands groups).
     *
     * @param string $source
     * @return string[]
     */
    private function get_clean_feed_urls($source) {
        $urls  = $this->get_feed_urls_from_source($source);
        $clean = array();
        foreach ($urls as $url) {
            $valid = $this->clean_feed_url($url);
            if ($valid) {
                $clean[] = $valid;
            } else {
                $this->log('Invalid feed URL skipped: ' . $url, 'warning');
            }
        }
        return array_values(array_unique($clean));
    }

    /**
     * Expand a job `source` into raw feed URLs.
     *
     * ★ The source is a comma-separated list resolved ONE ENTRY AT A TIME, exactly
     * as Feedzy's own get_feed_url() does it: an entry starting with http is a URL;
     * anything else is looked up as a feed group and replaced by the group's feeds;
     * a non-group with no scheme gets http:// prepended. A mixed source silently
     * loses the group's feeds if the whole string is tested instead.
     *
     * @param string $source
     * @param int    $depth  Recursion guard for a group listing another group.
     * @return string[]
     */
    private function get_feed_urls_from_source($source, $depth = 0) {
        $urls = array();

        foreach ($this->split_source($source) as $part) {
            if (stripos($part, 'http') === 0) {
                $urls[] = $part;
                continue;
            }

            $group = $this->find_feed_group($part);
            if ($group) {
                if ($depth < self::MAX_GROUP_DEPTH) {
                    $urls = array_merge(
                        $urls,
                        $this->get_feed_urls_from_source($this->group_feeds_raw($group->ID), $depth + 1)
                    );
                } else {
                    $this->log('Feed group nesting too deep, skipped: ' . $part, 'warning');
                }
                continue;
            }

            // Not a group and not a host either: a stale group reference. Importing
            // it as "http://<name>" would give the campaign a dead feed.
            if (strpos($part, '.') === false) {
                $this->log('Feed group not found: ' . $part, 'error');
                continue;
            }

            $urls[] = 'http://' . $part;
        }

        return $urls;
    }

    /**
     * Raw `feedzy_category_feed` meta of a feed group: the comma-separated list of
     * feeds shown in the group editor's textarea.
     *
     * @param int $group_id
     * @return string
     */
    private function group_feeds_raw($group_id) {
        $feeds = get_post_meta($group_id, 'feedzy_category_feed', true);
        return is_string($feeds) ? $feeds : '';
    }
}
