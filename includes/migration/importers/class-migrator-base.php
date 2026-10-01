<?php
/**
 * Base Migrator Class.
 * Abstract base for all competitor-plugin importers.
 *
 * Subclasses read the source plugin data and build an array of campaign fields
 * using REAL WPeMatico campaign keys (campaign_feeds, campaign_max,
 * campaign_posttype = post status, campaign_customposttype = post type, ...),
 * then call create_wpematico_campaign() / update_existing_campaign().
 *
 * Persistence goes through the exact same path the admin save uses:
 *   $campaign = apply_filters('wpematico_check_campaigndata', $post_data);
 *   WPeMatico::update_campaign($id, $campaign);
 * so core defaults AND active addon defaults are applied, and everything is
 * stored in the single serialized 'campaign_data' meta. Never scatter fields as
 * individual post meta, and never write addon-specific fields by hand.
 *
 * @package WPeMatico
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class WPeMatico_Migrator_Base {

    /**
     * Plugin data for the source plugin.
     *
     * @var array
     */
    protected $plugin_data;

    /**
     * Migration log entries.
     *
     * @var array
     */
    protected $logs = array();

    /**
     * Count of successfully migrated items.
     *
     * @var int
     */
    protected $migrated_count = 0;

    /**
     * IDs of campaigns created/updated in this run.
     *
     * @var array
     */
    protected $created_campaigns = array();

    /**
     * Optional-import flags ticked by the user in the box (e.g. 'filters',
     * 'fallback_image', 'translation', 'rewrite'). Keys present + truthy = import it.
     *
     * @var array
     */
    protected $options = array();

    /**
     * Whether to adopt the posts the source plugin already published.
     *
     * @var bool
     */
    protected $import_published_posts = false;

    /**
     * Posts adopted in this run, and posts that were already linked before it.
     *
     * @var int
     */
    protected $adopted_posts = 0;
    protected $already_linked_posts = 0;

    /**
     * Source keys already adopted in this run, mapped to the ordered list of
     * source-item hashes collected for them (newest post first). Guards against
     * adopting the same set twice when one source item builds several campaigns
     * (e.g. a Feedzy feed group split into one campaign per feed).
     *
     * @var array
     */
    private $adopted_sources = array();

    /**
     * Extra post-import advisories raised while migrating (not tied to an
     * optional feature). Merged into build_import_notices().
     *
     * @var string[]
     */
    protected $extra_notices = array();

    /**
     * Posts adopted per database round trip. Adoption walks post IDs, not post
     * objects, and only touches meta, but a site migrating from a plugin with
     * years of imports can hold tens of thousands of rows — chunking keeps the
     * meta cache from growing without bound inside a single request.
     */
    const ADOPT_BATCH = 200;

    /**
     * Constructor.
     *
     * @param array $plugin_data
     */
    public function __construct($plugin_data) {
        $this->plugin_data = $plugin_data;
    }

    /**
     * Store the optional-import flags chosen in the UI.
     *
     * @param array $options
     */
    public function set_options($options) {
        $this->options = is_array($options) ? $options : array();
    }

    /**
     * Whether an optional import is enabled AND its required addon is active.
     * Both axes must hold: the source had the data (the box only renders the
     * checkbox then) and our addon is installed (otherwise nowhere to write).
     *
     * @param string $key Optional feature key.
     * @return bool
     */
    protected function option_enabled($key) {
        if (empty($this->options[$key])) {
            return false;
        }
        $features = $this->get_optional_features();
        if (isset($features[$key]['addon_const']) && !$this->addon_active($features[$key]['addon_const'])) {
            return false;
        }
        return true;
    }

    /**
     * Whether one of our addons is active, by its version constant.
     *
     * @param string $const e.g. WPEMATICOPRO_VERSION, WPE_POLYGLOT_VER, WPE_GPT_SPINNER_VER.
     * @return bool
     */
    protected function addon_active($const) {
        return $const !== '' && defined($const);
    }

    /**
     * Populate WPeMatico Professional's per-feed "Feed Name" field from the
     * source's feed/job names. Cosmetic and PRO-only: PRO stores the array (read
     * from $post_data['feed']['feed_name'] in its check) and auto-derives the feed
     * domain when a name is blank, so this only runs when Professional is active.
     * Names must be index-aligned with the campaign's campaign_feeds. No-op when
     * no name is given.
     *
     * @param array    $data  Campaign data (by reference).
     * @param string[] $names One name per feed, in campaign_feeds order.
     */
    protected function map_feed_names(&$data, $names) {
        if (!$this->addon_active('WPEMATICOPRO_VERSION')) {
            return;
        }
        $names = array_values(array_map('strval', $names));
        $has_name = false;
        foreach ($names as $name) {
            if (trim($name) !== '') {
                $has_name = true;
                break;
            }
        }
        if (!$has_name) {
            return;
        }
        if (empty($data['feed']) || !is_array($data['feed'])) {
            $data['feed'] = array();
        }
        $data['feed']['feed_name'] = $names;
    }

    /**
     * Post-import advisories for the optional features that were actually
     * applied (e.g. "GPT Spinner needs its API configured"). Built from each
     * enabled feature's 'post_note'. Surfaced in the migration result.
     *
     * @return string[]
     */
    protected function build_import_notices() {
        $notices = array();
        foreach ($this->get_optional_features() as $key => $feature) {
            if (!empty($feature['post_note']) && $this->option_enabled($key)) {
                $notices[] = $feature['post_note'];
            }
        }
        return array_values(array_unique(array_merge($notices, $this->extra_notices)));
    }

    /* --------------------------------------------------------------------- *
     *  Already-published posts (adoption)
     *
     *  Migrating only the campaign leaves the posts the source plugin already
     *  created orphaned, so the first run imports every article a second time.
     *  Adopting them writes the provenance meta core writes on a normal fetch
     *  (wpe_campaignid / wpe_feed / wpe_sourcepermalink), which makes them count
     *  as already-imported and gives them a working canonical URL.
     *
     *  Everything generic lives here. An importer only has to say WHERE the
     *  source stored its posts, by overriding get_published_posts_sources().
     * --------------------------------------------------------------------- */

    /**
     * Whether to adopt the posts already published by the source plugin.
     *
     * @param bool $enabled
     */
    public function set_import_published_posts($enabled) {
        $this->import_published_posts = (bool) $enabled;
    }

    /**
     * Where the source plugin recorded the posts it created for one of its
     * items (a Feedzy job, a WP RSS Aggregator source, ...). Each descriptor:
     *
     *   'meta_key'   => post meta key holding the source item id
     *   'meta_value' => the id to match
     *   'url_metas'  => post meta keys that may hold the ORIGINAL item URL,
     *                   in order of preference
     *
     * Several descriptors are allowed so an importer can cover more than one
     * schema (e.g. WP RSS Aggregator v5 and the v4 keys left behind by its own
     * upgrade). Return an empty array when the importer cannot locate posts —
     * the feature then simply does not offer itself for that plugin.
     *
     * @param string|int $source_key Importer-specific id of the source item.
     * @return array
     */
    protected function get_published_posts_sources($source_key) {
        return array();
    }

    /**
     * Posts published by the source plugin that are still waiting to be adopted,
     * used to show the count in the import box. 0 also means "nothing to offer":
     * either this importer cannot locate posts, the source never created any, or
     * they were all adopted by a previous import. Override with a cheap COUNT.
     *
     * @return int
     */
    public function get_published_posts_count() {
        return 0;
    }

    /**
     * Count distinct not-yet-adopted posts carrying any of the given meta keys.
     * Shared helper for get_published_posts_count() implementations.
     *
     * @param string[] $meta_keys
     * @return int
     */
    protected function count_posts_with_meta_keys($meta_keys) {
        global $wpdb;

        $meta_keys = array_values(array_filter((array) $meta_keys));
        if (empty($meta_keys)) {
            return 0;
        }
        $types        = $this->excluded_post_types();
        $placeholders = implode(', ', array_fill(0, count($meta_keys), '%s'));
        $type_holders = implode(', ', array_fill(0, count($types), '%s'));

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- placeholders built from fixed counts, values passed to prepare().
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT pm.post_id) FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key IN ({$placeholders})
               AND p.post_type NOT IN ({$type_holders})
               AND p.post_status NOT IN ('trash', 'auto-draft')
               AND NOT EXISTS (
                   SELECT 1 FROM {$wpdb->postmeta} linked
                   WHERE linked.post_id = p.ID
                     AND linked.meta_key = 'wpe_sourcepermalink'
                     AND linked.meta_value != ''
               )",
            array_merge($meta_keys, $types)
        ));
    }

    /**
     * Post types adoption must never touch: WPeMatico's own campaigns, plus
     * anything the importer flags as internal storage rather than published
     * content (see get_internal_post_types()).
     *
     * @return string[]
     */
    private function excluded_post_types() {
        return array_values(array_unique(array_merge(
            array('wpematico', 'attachment', 'revision'),
            array_map('strval', (array) $this->get_internal_post_types())
        )));
    }

    /**
     * Post types the source plugin uses as internal item storage rather than as
     * published content. They look like imported posts (same provenance meta)
     * but they are the plugin's own cache: adopting them would link WPeMatico to
     * rows that disappear with the source plugin. Override per importer.
     *
     * @return string[]
     */
    protected function get_internal_post_types() {
        return array();
    }

    /**
     * Adopt the posts a source item already published into a migrated campaign.
     *
     * Idempotent: a post already carrying wpe_sourcepermalink is counted as
     * already linked and left untouched, so re-running an import never rewrites
     * or duplicates anything. Safe to call unconditionally — it returns early
     * when the user did not tick the option or the importer has no descriptors.
     *
     * @param int        $campaign_id
     * @param string|int $source_key  Importer-specific id of the source item.
     * @param array      $feed_urls   The campaign's feed URLs.
     * @return int Posts adopted in this call.
     */
    protected function adopt_published_posts($campaign_id, $source_key, $feed_urls = array()) {
        if (!$this->import_published_posts || !$campaign_id) {
            return 0;
        }

        // One source item can build several campaigns (a Feedzy feed group split
        // per feed). The posts belong to the item as a whole and the source kept
        // no record of which feed each came from, so they are adopted once, by
        // the first campaign built from that item. The duplicate protection is
        // global anyway (it matches on the source permalink, not the campaign),
        // so the other campaigns are covered too — they just don't own the posts.
        if (isset($this->adopted_sources[$source_key])) {
            $this->seed_dedup_hashes($campaign_id, $feed_urls, $this->adopted_sources[$source_key]);
            return 0;
        }

        $descriptors = $this->get_published_posts_sources($source_key);
        if (empty($descriptors)) {
            return 0;
        }

        $post_ids = $this->find_published_post_ids($descriptors);
        if (empty($post_ids)) {
            $this->adopted_sources[$source_key] = array();
            return 0;
        }

        // With a single feed every adopted post came from it. With several, the
        // source plugins record only the item URL (Feedzy's feedzy_item_url, WPRA's
        // _wpra_url) and never which feed produced it, so each post is attributed
        // by host — and left without a feed when that is ambiguous.
        $feed_urls = array_values(array_filter((array) $feed_urls));
        $feed_url  = (count($feed_urls) === 1) ? $feed_urls[0] : '';
        $feed_hosts = ($feed_url === '') ? $this->feeds_by_host($feed_urls) : array();

        $url_metas = array();
        foreach ($descriptors as $descriptor) {
            if (!empty($descriptor['url_metas'])) {
                $url_metas = array_merge($url_metas, (array) $descriptor['url_metas']);
            }
        }
        $url_metas = array_values(array_unique($url_metas));

        $adopted = 0;
        $hashes  = array();

        // Newest first (see find_published_post_ids), so the hashes we seed are
        // the most recent items — the ones the next fetch will run into first.
        foreach (array_chunk($post_ids, self::ADOPT_BATCH) as $chunk) {
            foreach ($chunk as $post_id) {
                $url = $this->read_first_meta($post_id, $url_metas);

                if ($url !== '') {
                    $hashes[] = md5($url);
                }

                // Already linked (adopted before, or genuinely fetched by WPeMatico).
                if (get_post_meta($post_id, 'wpe_sourcepermalink', true) !== '') {
                    $this->already_linked_posts++;
                    continue;
                }
                if ($url === '') {
                    continue;  // nothing to dedup on; adopting it would be a lie.
                }

                update_post_meta($post_id, 'wpe_sourcepermalink', $url);
                if (!get_post_meta($post_id, 'wpe_campaignid', true)) {
                    update_post_meta($post_id, 'wpe_campaignid', $campaign_id);
                }
                $post_feed = ($feed_url !== '') ? $feed_url : $this->feed_for_item_url($url, $feed_hosts);
                if ($post_feed !== '' && !get_post_meta($post_id, 'wpe_feed', true)) {
                    update_post_meta($post_id, 'wpe_feed', $post_feed);
                }
                // Marks the post as adopted rather than fetched, so a support
                // request can tell the two apart.
                update_post_meta($post_id, 'wpe_adopted_from', (string) $this->plugin_data['slug']);

                $adopted++;
                $this->adopted_posts++;
            }
            // The chunk's meta is done with; drop it so a long adoption does not
            // keep every post's meta alive in the object cache.
            if (function_exists('wp_cache_delete_multiple')) {  // WP 6.0+
                wp_cache_delete_multiple($chunk, 'post_meta');
            } else {
                foreach ($chunk as $post_id) {
                    wp_cache_delete($post_id, 'post_meta');
                }
            }
        }

        $this->adopted_sources[$source_key] = $hashes;
        $this->seed_dedup_hashes($campaign_id, $feed_urls, $hashes);
        $this->harden_campaign_duplicate_checks($campaign_id);

        if ($adopted > 0 || !empty($hashes)) {
            $this->enable_source_permalink_dedup();
        }

        return $adopted;
    }

    /**
     * Post IDs created by the source item, newest first. Excludes campaigns,
     * attachments, revisions and trashed/auto-draft posts, and looks at every
     * post status so drafts imported by the source are adopted too.
     *
     * @param array $descriptors See get_published_posts_sources().
     * @return int[]
     */
    private function find_published_post_ids($descriptors) {
        global $wpdb;

        $types        = $this->excluded_post_types();
        $type_holders = implode(', ', array_fill(0, count($types), '%s'));

        $ids = array();
        foreach ($descriptors as $descriptor) {
            if (empty($descriptor['meta_key']) || !isset($descriptor['meta_value'])) {
                continue;
            }
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- placeholders built from a fixed count, values passed to prepare().
            $rows = $wpdb->get_col($wpdb->prepare(
                "SELECT p.ID FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
                 WHERE pm.meta_key = %s AND pm.meta_value = %s
                   AND p.post_type NOT IN ({$type_holders})
                   AND p.post_status NOT IN ('trash', 'auto-draft')
                 ORDER BY p.post_date DESC",
                array_merge(array($descriptor['meta_key'], (string) $descriptor['meta_value']), $types)
            ));
            foreach ($rows as $id) {
                $ids[(int) $id] = true;  // keyed: a post matching two schemas counts once.
            }
        }
        return array_keys($ids);
    }

    /**
     * Index a campaign's feeds by host, so an adopted post can be attributed to
     * the feed it most likely came from. A host shared by two feeds of the same
     * campaign is dropped: attributing those posts would be a guess, and a wrong
     * wpe_feed is worse than an empty one (it is what the post metabox shows).
     *
     * @param string[] $feed_urls
     * @return array host => feed URL
     */
    private function feeds_by_host($feed_urls) {
        $by_host = array();
        foreach ($feed_urls as $feed_url) {
            $host = $this->url_host($feed_url);
            if ($host === '') {
                continue;
            }
            $by_host[$host] = isset($by_host[$host]) ? '' : $feed_url;
        }
        return array_filter($by_host, 'strlen');
    }

    /**
     * The campaign feed an adopted item's URL belongs to, by host ('' when unknown).
     *
     * @param string $item_url
     * @param array  $feeds_by_host
     * @return string
     */
    private function feed_for_item_url($item_url, $feeds_by_host) {
        if (empty($feeds_by_host)) {
            return '';
        }
        $host = $this->url_host($item_url);
        return ($host !== '' && isset($feeds_by_host[$host])) ? $feeds_by_host[$host] : '';
    }

    /**
     * Host of a URL, lowercased and without the www prefix ('' when unparseable).
     *
     * @param string $url
     * @return string
     */
    private function url_host($url) {
        $host = wp_parse_url((string) $url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return '';
        }
        return preg_replace('/^www\./i', '', strtolower($host));
    }

    /**
     * First non-empty value among several meta keys.
     *
     * @param int      $post_id
     * @param string[] $meta_keys
     * @return string
     */
    private function read_first_meta($post_id, $meta_keys) {
        foreach ($meta_keys as $meta_key) {
            $value = get_post_meta($post_id, $meta_key, true);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }
        return '';
    }

    /**
     * Pre-load the campaign's per-feed dedup hashes with the adopted items.
     *
     * The same meta the fetch engine writes as it goes: `_lasthash_<feed>` (the
     * last item seen) and `_lasthashes_<feed>` (the ring used in "jump
     * duplicates" mode). Seeding them starts the campaign where the source plugin
     * left off instead of walking the whole feed again.
     *
     * Every feed gets the same list, since the source never recorded which one
     * produced each item — a hash belonging to another feed simply never matches.
     *
     * @param int      $campaign_id
     * @param array    $feed_urls
     * @param string[] $hashes  Item hashes, newest first.
     */
    private function seed_dedup_hashes($campaign_id, $feed_urls, $hashes) {
        if (empty($hashes) || empty($feed_urls)) {
            return;
        }
        $max = (int) apply_filters('wpematico_max_duplicated_hashes_count', 20, $campaign_id, '');
        if ($max < 1) {
            $max = 20;
        }
        $recent = array_slice($hashes, 0, $max);

        foreach ($feed_urls as $feed_url) {
            $suffix = sanitize_file_name($feed_url);

            // Only seed a feed that has never run, so re-importing a campaign
            // that has been fetching on its own cannot rewind its own progress.
            if (get_post_meta($campaign_id, '_lasthash_' . $suffix, true) === '') {
                update_post_meta($campaign_id, '_lasthash_' . $suffix, $hashes[0]);
            }

            $existing = get_post_meta($campaign_id, '_lasthashes_' . $suffix, false);
            $existing = is_array($existing) ? $existing : array();
            // Oldest first, matching the order the fetch engine appends in.
            foreach (array_reverse($recent) as $hash) {
                if (!in_array($hash, $existing, true)) {
                    add_post_meta($campaign_id, '_lasthashes_' . $suffix, $hash, false);
                    $existing[] = $hash;
                }
            }
        }
    }

    /**
     * Make the migrated campaign actually consult the adopted posts.
     *
     * The source-permalink check only runs when the campaign is checking titles
     * (campaign_allowduptitle off) and has the extra meta filter on, so the
     * campaign is switched to its own duplicate settings with both in place.
     * Only this campaign is touched; global behaviour for other campaigns is
     * left as the user configured it.
     *
     * @param int $campaign_id
     */
    private function harden_campaign_duplicate_checks($campaign_id) {
        $data = get_post_meta($campaign_id, 'campaign_data', true);
        if (!is_array($data)) {
            return;
        }
        $data['campaign_no_setting_duplicate']      = true;
        $data['campaign_allowduplicates']           = false;
        $data['campaign_allowduptitle']             = false;
        $data['campaign_add_ext_duplicate_filter_ms'] = true;
        update_post_meta($campaign_id, 'campaign_data', $data);
    }

    /**
     * Turn on the global "extra duplicate filter by source permalink" setting,
     * which is what registers the filter that reads wpe_sourcepermalink during
     * a fetch. Without it the adopted posts are invisible to the duplicate
     * check and the campaign would import everything again.
     *
     * Enabling only, never disabling — same rule as the other global toggles.
     * When custom fields are disabled site-wide the setting is inert (and
     * hidden in the UI): nothing is flipped behind the user's back, they get an
     * advisory instead.
     */
    private function enable_source_permalink_dedup() {
        $cfg = get_option(WPeMatico::OPTION_KEY);
        if (!is_array($cfg)) {
            return;
        }

        if (!empty($cfg['disableccf'])) {
            $this->extra_notices[] = __('"Disable plugin custom fields" is on in WPeMatico settings, so imported posts cannot be checked against the source permalink. Turn it off if you want the migrated campaigns to skip the posts the previous plugin already published.', 'wpematico');
            return;
        }

        if (empty($cfg['add_extra_duplicate_filter_meta_source'])) {
            $cfg['add_extra_duplicate_filter_meta_source'] = true;
            update_option(WPeMatico::OPTION_KEY, $cfg);
        }
    }

    /**
     * Human summary of what the adoption did, for the migration result.
     *
     * @return string
     */
    protected function adoption_summary() {
        if (!$this->import_published_posts) {
            return '';
        }
        if ($this->adopted_posts < 1) {
            return ($this->already_linked_posts > 0)
                ? __('The already published posts were linked to their campaigns in a previous import.', 'wpematico')
                : '';
        }
        return sprintf(
            /* translators: %d: number of already published posts linked to the migrated campaigns. */
            _n(
                'Linked %d post already published by the source plugin, so it will not be imported again.',
                'Linked %d posts already published by the source plugin, so they will not be imported again.',
                $this->adopted_posts,
                'wpematico'
            ),
            $this->adopted_posts
        );
    }

    /**
     * Optional features this importer can map, keyed by feature slug. Each entry:
     *   'label'           => string  (checkbox label)
     *   'note'            => string  (extra help text)
     *   'addon_const'     => string  (version constant of OUR addon that receives it; '' = core)
     *   'addon_name'      => string  (human name, for the "requires X" note)
     *   'has_source_data' => bool    (the source actually has this data to import)
     * The box renders a checkbox only when 'has_source_data' is true, enabled when
     * the addon is active and disabled otherwise. Override in child importers.
     *
     * @return array
     */
    public function get_optional_features() {
        return array();
    }

    /**
     * Inline monochrome SVG used as the "imported from" badge in the campaigns
     * list. Override per importer; the default is a generic migrate glyph.
     *
     * @return string
     */
    public function get_icon_svg() {
        return '<svg viewBox="0 0 20 20" width="17" height="17" xmlns="http://www.w3.org/2000/svg" fill="currentColor" aria-hidden="true">'
            . '<path d="M10 1a9 9 0 1 0 0 18 9 9 0 0 0 0-18zm0 2a7 7 0 1 1 0 14 7 7 0 0 1 0-14zm-1 3v4H5l5 5 5-5h-4V6H9z"/></svg>';
    }

    /**
     * Human label for the source plugin (for the provenance tooltip).
     *
     * @return string
     */
    public function get_source_label() {
        return isset($this->plugin_data['name']) ? $this->plugin_data['name'] : __('another plugin', 'wpematico');
    }

    /**
     * Stamp provenance meta on a migrated campaign so the campaigns-list badge can
     * identify its origin. Standard for every importer: call it right after a
     * create_wpematico_campaign() / update_existing_campaign() succeeds.
     *
     * @param int    $campaign_id
     * @param string $source_id     Original item ID in the source plugin.
     * @param string $source_title  Original item title (for the tooltip).
     */
    protected function store_provenance($campaign_id, $source_id, $source_title) {
        if (!$campaign_id) {
            return;
        }
        $slug = isset($this->plugin_data['slug']) ? $this->plugin_data['slug'] : '';
        update_post_meta($campaign_id, 'migrated_from', $slug);
        update_post_meta($campaign_id, 'migrated_source_id', $source_id);
        update_post_meta($campaign_id, 'migrated_source_title', $source_title);
    }

    /**
     * Run the migration. Must be implemented by child classes.
     */
    abstract public function migrate();

    /**
     * Preview of items to be migrated. Must be implemented by child classes.
     */
    abstract public function get_preview();

    /**
     * Count of items available for migration. Must be implemented by child classes.
     */
    abstract public function get_items_count();

    /**
     * Add a log entry.
     *
     * @param string $message
     * @param string $type info|success|warning|error|debug
     */
    protected function log($message, $type = 'info') {
        $this->logs[] = array(
            'message' => $message,
            'type'    => $type,
            // Timezone-aware current time (core convention since 2.8.20: wp_date, not current_time/date/date_i18n).
            'time'    => wp_date('Y-m-d H:i:s'),
        );
    }

    /**
     * Get all log entries.
     *
     * @return array
     */
    public function get_logs() {
        return $this->logs;
    }

    /**
     * Create a new WPeMatico campaign from mapped data.
     *
     * @param array $data Fields using real WPeMatico keys. Special keys:
     *                    'campaign_title' (post title) and 'campaign_description'
     *                    (post content) are applied to the post object.
     * @return int|false  New campaign post ID, or false on failure.
     */
    protected function create_wpematico_campaign($data) {
        if (!class_exists('WPeMatico')) {
            $this->log('WPeMatico core is not available; cannot create campaign.', 'error');
            return false;
        }

        $title       = (isset($data['campaign_title']) && $data['campaign_title'] !== '')
            ? $data['campaign_title']
            : __('Imported campaign', 'wpematico');
        $description = isset($data['campaign_description']) ? $data['campaign_description'] : '';
        $excerpt     = isset($data['campaign_excerpt']) ? $data['campaign_excerpt'] : '';

        // Create the campaign post. It stays deactivated until persisted below.
        $campaign_id = wp_insert_post(array(
            'post_title'   => $title,
            'post_content' => $description,
            'post_excerpt' => $excerpt,
            'post_status'  => 'publish',
            'post_type'    => 'wpematico',
        ), true);

        if (is_wp_error($campaign_id)) {
            $this->log('Error creating campaign: ' . $campaign_id->get_error_message(), 'error');
            return false;
        }

        $this->save_campaign_data($campaign_id, $data);

        $this->created_campaigns[] = $campaign_id;
        $this->migrated_count++;
        $this->log('Created campaign: ' . $title, 'success');

        return $campaign_id;
    }

    /**
     * Update an existing WPeMatico campaign (e.g. re-running a migration).
     *
     * @param int   $campaign_id
     * @param array $data Fields using real WPeMatico keys.
     * @return int|false
     */
    protected function update_existing_campaign($campaign_id, $data) {
        if (!class_exists('WPeMatico')) {
            $this->log('WPeMatico core is not available; cannot update campaign.', 'error');
            return false;
        }

        $title       = (isset($data['campaign_title']) && $data['campaign_title'] !== '')
            ? $data['campaign_title']
            : get_the_title($campaign_id);
        $description = isset($data['campaign_description']) ? $data['campaign_description'] : '';
        $excerpt     = isset($data['campaign_excerpt']) ? $data['campaign_excerpt'] : '';

        wp_update_post(array(
            'ID'           => $campaign_id,
            'post_title'   => $title,
            'post_content' => $description,
            'post_excerpt' => $excerpt,
            'post_status'  => 'publish',
            'post_type'    => 'wpematico',
        ));

        $this->save_campaign_data($campaign_id, $data);

        $this->migrated_count++;
        $this->log('Updated campaign: ' . $title, 'success');

        return $campaign_id;
    }

    /**
     * Build and persist the 'campaign_data' meta the WPeMatico way.
     *
     * Mirrors the admin save flow: a $_POST-like array is normalized through the
     * wpematico_check_campaigndata filter (which also applies active addon
     * defaults) and stored with WPeMatico::update_campaign().
     *
     * @param int   $campaign_id
     * @param array $data Fields using real WPeMatico keys.
     */
    protected function save_campaign_data($campaign_id, $data) {
        // These belong on the post object, not in campaign_data.
        unset($data['campaign_description'], $data['campaign_excerpt']);

        $post_data       = $data;
        $post_data['ID'] = $campaign_id;

        if (empty($post_data['campaign_title'])) {
            $post_data['campaign_title'] = get_the_title($campaign_id);
        }

        // Keep imported campaigns deactivated unless a child explicitly opts in.
        if (!isset($post_data['activated'])) {
            $post_data['activated'] = false;
        }

        // Same normalization the campaign edit screen uses on save.
        $campaign = apply_filters('wpematico_check_campaigndata', $post_data);

        if (method_exists('WPeMatico', 'update_campaign')) {
            WPeMatico::update_campaign($campaign_id, $campaign);
        } else {
            update_post_meta($campaign_id, 'campaign_data', $campaign);
        }

        $this->enable_required_global_features($campaign);
    }

    /**
     * Turn on the global feature toggles a migrated campaign needs to actually
     * work. Some addon features are gated by an "enable X" switch in the global
     * settings (off by default to save resources); without it the imported
     * campaign carries the data but the feature is neither shown nor run.
     * Only ever ENABLES (never disables) and only when the campaign uses it.
     *
     * @param array $campaign The normalized campaign_data that was saved.
     */
    protected function enable_required_global_features($campaign) {
        // Keyword filters live behind Professional's global "Keyword Filtering"
        // toggle (checked at fetch time in campaign_fetch.php).
        $kw = (isset($campaign['campaign_kwordf']) && is_array($campaign['campaign_kwordf']))
            ? $campaign['campaign_kwordf'] : array();
        $has_filters = !empty($kw['inc']) || !empty($kw['exc'])
            || !empty($kw['incregex']) || !empty($kw['excregex']);
        if ($has_filters && defined('WPEMATICOPRO_OPTION_KEY')) {
            $this->enable_global_toggle(WPEMATICOPRO_OPTION_KEY, 'enablekwordf');
        }
    }

    /**
     * Enable a single boolean toggle inside an option-array setting, if present
     * and not already on. No-op when the option or key is missing.
     *
     * @param string $option_key
     * @param string $toggle
     */
    protected function enable_global_toggle($option_key, $toggle) {
        $opts = get_option($option_key);
        if (is_array($opts) && empty($opts[$toggle])) {
            $opts[$toggle] = true;
            update_option($option_key, $opts);
        }
    }

    /**
     * Normalize a list of category IDs to WPeMatico's format: a flat array of
     * unique term IDs (consumed via array_map('intval', ...) in the edit screen).
     *
     * @param array $category_ids
     * @return array
     */
    protected function get_categories_array($category_ids) {
        $categories = array();
        foreach ((array) $category_ids as $cat_id) {
            $cat_id = (int) $cat_id;
            if ($cat_id > 0) {
                $categories[] = $cat_id;
            }
        }
        return array_values(array_unique($categories));
    }

    /**
     * Normalize a source post status to a valid WPeMatico status.
     *
     * @param string $status
     * @return string
     */
    protected function convert_post_status($status) {
        $valid = array('publish', 'draft', 'pending', 'private', 'future', 'trash');
        return in_array($status, $valid, true) ? $status : 'publish';
    }

    /**
     * Normalize a source post type to a registered public post type.
     *
     * @param string $post_type
     * @return string
     */
    protected function convert_post_type($post_type) {
        $valid = get_post_types(array('public' => true));
        return in_array($post_type, $valid, true) ? $post_type : 'post';
    }

    /**
     * Resolve a WordPress user by login/email, creating one only as a fallback.
     *
     * @param string $username
     * @param string $email
     * @return int
     */
    protected function get_or_create_user($username, $email = '') {
        if (!empty($username)) {
            $user = get_user_by('login', $username);
            if ($user) {
                return $user->ID;
            }
        }

        if (!empty($email)) {
            $user = get_user_by('email', $email);
            if ($user) {
                return $user->ID;
            }
        }

        if (empty($username)) {
            return get_current_user_id();
        }

        $user_id = wp_create_user($username, wp_generate_password(12, false), $email);
        if (is_wp_error($user_id)) {
            return get_current_user_id();
        }
        return $user_id;
    }

    /**
     * Validate and sanitize a feed URL.
     *
     * @param string $url
     * @return string|false
     */
    protected function clean_feed_url($url) {
        $url = esc_url_raw(trim((string) $url));
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        return $url;
    }

    /**
     * Current user ID, used as the default campaign author.
     *
     * @return int
     */
    protected function get_default_author() {
        return get_current_user_id();
    }

    /**
     * Build the standard result array returned to the AJAX layer.
     *
     * @param bool   $success
     * @param string $message
     * @param array  $data
     * @return array
     */
    protected function format_result($success, $message = '', $data = array()) {
        return array_merge(array(
            'success'        => $success,
            'message'        => $message,
            'migrated_count' => $this->migrated_count,
            'campaigns'      => $this->created_campaigns,
            'logs'           => $this->logs,
            'adopted_posts'  => $this->adopted_posts,
            'adopted_note'   => $this->adoption_summary(),
        ), $data);
    }

    /**
     * Find an already-migrated campaign by its source identifier.
     *
     * @param string|int $source_id
     * @return int|null  Campaign post ID, or null if none.
     */
    protected function get_existing_campaign_id($source_id) {
        global $wpdb;
        // Only match a live campaign: deleting (trash or permanent) the imported
        // campaign must let a re-import create a fresh one instead of resurrecting it.
        $id = $wpdb->get_var($wpdb->prepare(
            "SELECT pm.post_id FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = 'migrated_source_id' AND pm.meta_value = %s
               AND p.post_type = 'wpematico' AND p.post_status NOT IN ('trash', 'auto-draft')
             LIMIT 1",
            (string) $source_id
        ));
        return $id ? (int) $id : null;
    }
}
