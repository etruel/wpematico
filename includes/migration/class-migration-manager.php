<?php
// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WPeMatico_Migration_Toolkit
 * Main class for the Migration Toolkit addon
 */
class WPeMatico_Migration_Toolkit {

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Array of supported competitor plugins
     */
    private $supported_plugins = array();

    /**
     * Array of detected installed plugins
     */
    private $detected_plugins = array();

    /**
     * Get single instance of the class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
            self::$instance->includes();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->setup_supported_plugins();
        $this->includes();
        $this->detect_installed_plugins();
        $this->init();
    }

    /**
     * Setup the list of supported competitor plugins
     */
    private function setup_supported_plugins() {
        $this->supported_plugins = array(
            'wp-rss-aggregator' => array(
                'name' => 'WP RSS Aggregator',
                'plugin_file' => 'wp-rss-aggregator/wp-rss-aggregator.php',
                // v5 stores sources in the custom table {prefix}agg_sources (not a
                // CPT); the migrator reads that table directly. Display-only feeds
                // go to rss_reader campaigns, Feed-to-Post feeds to normal campaigns.
                'migrator_class' => 'WPeMatico_Migrator_WPRSS_Aggregator',
                // Add-ons deactivated together with the main plugin on auto-deactivate.
                'companion_plugins' => array('wp-rss-aggregator-premium/wp-rss-aggregator-premium.php'),
                'description' => __('Import feed sources from WP RSS Aggregator. Feeds that create posts become campaigns; display-only feeds become RSS Feed Reader campaigns.', 'wpematico'),
            ),
            'feedzy-rss-feeds' => array(
                'name' => 'Feedzy RSS Feeds',
                'plugin_file' => 'feedzy-rss-feeds/feedzy-rss-feed.php',
                'post_type' => 'feedzy_imports',
                'meta_prefix' => 'feedzy_',
                'has_feed_groups' => true,
                'feed_groups_post_type' => 'feedzy_categories',
                'migrator_class' => 'WPeMatico_Migrator_Feedzy',
                // Feedzy PRO is a SEPARATE plugin that keeps importing on its own
                // when only the free one is deactivated (its "Pro Slug" header).
                'companion_plugins' => array('feedzy-rss-feeds-pro/feedzy-rss-feeds-pro.php'),
                'description' => __('Import the Feedzy import jobs as campaigns. A job whose source is a feed group brings every feed of that group into its campaign.', 'wpematico'),
            ),
        );

        // Stamp each entry with its slug so every migrator (however it is
        // constructed) can read plugin_data['slug'] — used for provenance meta.
        foreach ($this->supported_plugins as $slug => &$data) {
            $data['slug'] = $slug;
        }
        unset($data);
    }

    /**
     * Detect which supported plugins are installed and active
     */
    private function detect_installed_plugins() {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';

        foreach ($this->supported_plugins as $slug => $plugin_data) {
            if (is_plugin_active($plugin_data['plugin_file']) || $this->plugin_has_data($slug)) {
                $this->detected_plugins[$slug] = $plugin_data;
            }
        }
    }

    /**
     * Whether the source plugin still has migratable data in the database, even
     * if it is deactivated. Lets the user migrate after turning the plugin off.
     *
     * @param string $slug
     * @return bool
     */
    private function plugin_has_data($slug) {
        $migrator = $this->get_migrator($slug);
        if (!$migrator) {
            return false;
        }
        try {
            return $migrator->get_items_count() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Initialize the plugin
     */
    private function init() {
        // Register AJAX handlers
        add_action('wp_ajax_wpematico_migrate_plugin', array($this, 'ajax_migrate_plugin'));
        add_action('wp_ajax_wpematico_get_migration_preview', array($this, 'ajax_get_migration_preview'));
        add_action('wp_ajax_wpematico_scan_wpra_shortcodes', array($this, 'ajax_scan_wpra_shortcodes'));
        add_action('wp_ajax_wpematico_replace_wpra_shortcode', array($this, 'ajax_replace_wpra_shortcode'));
        
        // Admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Include required files
     */
    public function includes() {
        // Help content: feeds both the Tools Help tab and the (?) tooltips.
        require_once WPEMATICO_PLUGIN_DIR . 'includes/migration/help.php';

        // Include migrator classes
        require_once WPEMATICO_PLUGIN_DIR . 'includes/migration/importers/class-migrator-base.php';

        foreach ($this->supported_plugins as $slug => $plugin_data) {
            $migrator_file = WPEMATICO_PLUGIN_DIR . 'includes/migration/importers/class-migrator-' . str_replace('_', '-', $slug) . '.php';
            if (file_exists($migrator_file)) {
                require_once $migrator_file;
            }
        }
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on the Migration Toolkit section of the WPeMatico Tools page.
        if (!isset($_GET['page']) || $_GET['page'] !== 'wpematico_tools') {
            return;
        }
        $section = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : '';
        if ($section !== 'migration') {
            return;
        }

        wp_enqueue_style(
            'wpematico-migration-toolkit',
            WPEMATICO_PLUGIN_URL . 'assets/css/wpematico_migration.css',
            array(),
            WPEMATICO_VERSION
        );

        // WPemattiptip is the tipTip library the campaign editor uses; declared as
        // a dependency so the (?) tooltips are initialisable by the time this runs.
        wp_enqueue_script(
            'wpematico-migration-toolkit',
            WPEMATICO_PLUGIN_URL . 'assets/js/wpematico_migration.js',
            array('jquery', 'WPemattiptip'),
            WPEMATICO_VERSION,
            true
        );

        wp_localize_script('wpematico-migration-toolkit', 'wpematico_migration_toolkit', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpematico_migration_toolkit_nonce'),
            // Reuse the module system's install endpoint to add the RSS Feed Reader addon.
            'module_nonce' => wp_create_nonce('wpematico_module_nonce'),
            'strings' => array(
                'installing_addon' => __('Installing RSS Feed Reader…', 'wpematico'),
                'activating_addon' => __('Activating RSS Feed Reader…', 'wpematico'),
                'install_failed' => __('Could not install the addon.', 'wpematico'),
                'confirm_migration' => __('This will migrate all campaigns from the selected plugin to WPeMatico. Continue?', 'wpematico'),
                'migration_success' => __('Migration completed successfully!', 'wpematico'),
                'migration_error' => __('An error occurred during migration.', 'wpematico'),
                'migrating' => __('Migrating...', 'wpematico'),
                'please_wait' => __('Please wait...', 'wpematico'),
                'plugin_deactivated' => __('Original plugin has been automatically deactivated.', 'wpematico'),
                'shortcodes_title' => __('Replace WP RSS Aggregator shortcodes', 'wpematico'),
                'shortcodes_intro' => __('These pages still embed WP RSS Aggregator. Replace each one with its WPeMatico shortcode.', 'wpematico'),
                'col_page' => __('Page / Post', 'wpematico'),
                'col_found' => __('Found embed', 'wpematico'),
                'col_replacement' => __('Replacement', 'wpematico'),
                'col_action' => __('Action', 'wpematico'),
                'replace_btn' => __('Replace', 'wpematico'),
                'replaced' => __('Replaced', 'wpematico'),
                'replacing' => __('Replacing…', 'wpematico'),
                'replace_failed' => __('Replace failed.', 'wpematico'),
            ),
        ));
    }

    /**
     * Render the Migration Toolkit box in WPeMatico Tools page
     */
    public static function render_migration_toolkit_box() {
        include WPEMATICO_PLUGIN_DIR . 'includes/migration/views/migration-box.php';
    }

    /**
     * AJAX handler for migration
     */
    public function ajax_migrate_plugin() {
        check_ajax_referer('wpematico_migration_toolkit_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'wpematico')));
        }

        $plugin_slug = isset($_POST['plugin']) ? sanitize_text_field($_POST['plugin']) : '';
        $feed_group_mode = isset($_POST['feed_group_mode']) ? sanitize_text_field($_POST['feed_group_mode']) : 'single';
        $auto_deactivate = isset($_POST['auto_deactivate']) && $_POST['auto_deactivate'] == '1';
        $options = $this->read_optional_flags();

        if (empty($plugin_slug) || !isset($this->supported_plugins[$plugin_slug])) {
            wp_send_json_error(array('message' => __('Invalid plugin selected.', 'wpematico')));
        }

        // Refuse when there is nothing to import (mirrors the disabled Import button).
        $gate = $this->get_import_gate($plugin_slug);
        if ($gate !== null) {
            wp_send_json_error(array('message' => $gate['reason']));
        }

        $plugin_data = $this->supported_plugins[$plugin_slug];
        $migrator_class = $plugin_data['migrator_class'];

        if (!class_exists($migrator_class)) {
            wp_send_json_error(array('message' => __('Migrator class not found.', 'wpematico')));
        }

        try {
            $migrator = new $migrator_class($plugin_data);

            // Migration mode for feed groups.
            if (method_exists($migrator, 'set_feed_group_mode')) {
                $migrator->set_feed_group_mode($feed_group_mode);
            }
            // Optional, addon-gated imports ticked in the UI.
            if (method_exists($migrator, 'set_options')) {
                $migrator->set_options($options);
            }
            // Opt-out of activating + fetching reader campaigns during import.
            if (method_exists($migrator, 'set_reader_initial_fetch')) {
                $reader_fetch = !isset($_POST['reader_initial_fetch']) || $_POST['reader_initial_fetch'] == '1';
                $migrator->set_reader_initial_fetch($reader_fetch);
            }
            // Adopt the posts the source plugin already published (opt-in).
            if (method_exists($migrator, 'set_import_published_posts')) {
                $migrator->set_import_published_posts(isset($_POST['import_posts']) && $_POST['import_posts'] == '1');
            }

            $result = $migrator->migrate();
            
            $response_data = array(
                'message' => sprintf(
                    __('Successfully migrated %d campaigns to WPeMatico.', 'wpematico'),
                    $result['migrated_count']
                ),
                'migrated_count' => $result['migrated_count'],
                'campaigns' => $result['campaigns'],
                'notices' => isset($result['notices']) ? $result['notices'] : array(),
                'adopted_posts' => isset($result['adopted_posts']) ? (int) $result['adopted_posts'] : 0,
                'adopted_note' => isset($result['adopted_note']) ? $result['adopted_note'] : '',
                'deactivated' => false,
            );
            
            // Auto-deactivate when it was asked for and the migration succeeded.
            if ($result['success'] && $auto_deactivate) {
                $deactivated = $this->deactivate_plugin_internal($plugin_slug);
                $response_data['deactivated'] = $deactivated;
            }
            
            if ($result['success']) {
                wp_send_json_success($response_data);
            } else {
                wp_send_json_error(array('message' => $result['message']));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Read the ticked optional-import flags from the request into a
     * [feature_key => true] map. Whitelisted later by the migrator itself.
     *
     * @return array
     */
    private function read_optional_flags() {
        $options = array();
        if (isset($_POST['options']) && is_array($_POST['options'])) {
            foreach (wp_unslash($_POST['options']) as $opt) {
                $key = sanitize_key($opt);
                if ($key !== '') {
                    $options[$key] = true;
                }
            }
        }
        return $options;
    }

    /**
     * Internal method to deactivate plugin (no extra capability check).
     * Also deactivates any companion add-ons (e.g. WP RSS Aggregator - Premium).
     */
    private function deactivate_plugin_internal($plugin_slug) {
        if (!isset($this->supported_plugins[$plugin_slug])) {
            return false;
        }

        $plugin      = $this->supported_plugins[$plugin_slug];
        $plugin_file = $plugin['plugin_file'];

        // Deactivate companion add-ons first so nothing keeps the source alive.
        foreach ($this->resolve_companion_plugins($plugin) as $companion) {
            deactivate_plugins($companion);
        }

        if (!is_plugin_active($plugin_file)) {
            return true; // Ya está desactivado
        }

        deactivate_plugins($plugin_file);

        return !is_plugin_active($plugin_file);
    }

    /**
     * The source plugin's active companion add-ons (premium/pro packages that are
     * their own plugin and keep importing on their own once the main one is off).
     *
     * Matched by FOLDER, not by the exact plugin file: a premium package can name
     * its main file differently from what we declared, and missing it leaves the
     * old plugin importing in parallel with the migrated campaigns — which is the
     * single thing this option exists to prevent.
     *
     * @param array $plugin Entry of $supported_plugins.
     * @return string[] Active plugin files to deactivate.
     */
    private function resolve_companion_plugins($plugin) {
        if (empty($plugin['companion_plugins'])) {
            return array();
        }

        $folders = array();
        foreach ((array) $plugin['companion_plugins'] as $companion) {
            $folders[dirname($companion)] = true;
        }
        unset($folders['.']);  // a single-file companion has no folder to match on

        $found = array();
        foreach ((array) get_option('active_plugins', array()) as $active) {
            if (isset($folders[dirname($active)])) {
                $found[] = $active;
            }
        }

        // Declared files that are active but live outside those folders (or in a
        // single-file plugin) still have to go.
        foreach ((array) $plugin['companion_plugins'] as $companion) {
            if (is_plugin_active($companion) && !in_array($companion, $found, true)) {
                $found[] = $companion;
            }
        }

        return $found;
    }

    /**
     * AJAX handler for migration preview
     */
    public function ajax_get_migration_preview() {
        check_ajax_referer('wpematico_migration_toolkit_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'wpematico')));
        }

        $plugin_slug = isset($_POST['plugin']) ? sanitize_text_field($_POST['plugin']) : '';
        $feed_group_mode = isset($_POST['feed_group_mode']) ? sanitize_text_field($_POST['feed_group_mode']) : 'single';
        
        if (empty($plugin_slug) || !isset($this->supported_plugins[$plugin_slug])) {
            wp_send_json_error(array('message' => __('Invalid plugin selected.', 'wpematico')));
        }

        $plugin_data = $this->supported_plugins[$plugin_slug];
        $migrator_class = $plugin_data['migrator_class'];
        
        if (!class_exists($migrator_class)) {
            wp_send_json_error(array('message' => __('Migrator class not found.', 'wpematico')));
        }

        try {
            $migrator = new $migrator_class($plugin_data);
            
            // Migration mode for feed groups.
            if (method_exists($migrator, 'set_feed_group_mode')) {
                $migrator->set_feed_group_mode($feed_group_mode);
            }
            
            $preview = $migrator->get_preview();
            $summary = method_exists($migrator, 'get_migration_summary') ? $migrator->get_migration_summary() : null;
            
            wp_send_json_success(array(
                'items' => $preview,
                'count' => count($preview),
                'summary' => $summary,
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * AJAX: scan the site content for WP RSS Aggregator embeds and return the
     * rows for the "Replace shortcodes" table (post + found embed + suggested
     * [wpematico-{slug}] replacement + whether it maps to a migrated campaign).
     */
    public function ajax_scan_wpra_shortcodes() {
        check_ajax_referer('wpematico_migration_toolkit_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'wpematico')));
        }

        wp_send_json_success(array('rows' => $this->scan_wpra_shortcodes()));
    }

    /**
     * AJAX: replace one WP RSS Aggregator embed with its WPeMatico shortcode
     * inside a single post's content. Per-row, never a bulk auto-rewrite.
     */
    public function ajax_replace_wpra_shortcode() {
        check_ajax_referer('wpematico_migration_toolkit_nonce', 'nonce');

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $search  = isset($_POST['search']) ? wp_unslash($_POST['search']) : '';
        $replace = isset($_POST['replace']) ? wp_unslash($_POST['replace']) : '';

        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'wpematico')));
        }
        // Only accept a well-formed WPeMatico reader shortcode as the replacement.
        if ($search === '' || !preg_match('/^\[wpematico-[a-z0-9_\-]+\]$/', $replace)) {
            wp_send_json_error(array('message' => __('Invalid replacement.', 'wpematico')));
        }

        $post = get_post($post_id);
        if (!$post || strpos($post->post_content, $search) === false) {
            wp_send_json_error(array('message' => __('The shortcode was not found in this content (it may have changed).', 'wpematico')));
        }

        $new_content = str_replace($search, $replace, $post->post_content);
        $result = wp_update_post(array('ID' => $post_id, 'post_content' => $new_content), true);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('replace' => $replace));
    }

    /**
     * Build a map of WP RSS Aggregator source id => migrated campaign, so a scanned
     * embed's source="id" can be pointed at the right WPeMatico shortcode.
     * Reader campaigns expose a shortcode slug; post-creating (feed) campaigns don't.
     *
     * @return array [ (int) source_id => ['type'=>'reader','slug'=>string] | ['type'=>'feed'] ]
     */
    private function get_wpra_source_map() {
        $campaigns = get_posts(array(
            'post_type'      => 'wpematico',
            'post_status'    => 'any',
            'numberposts'    => -1,
            'fields'         => 'ids',
            'meta_key'       => 'migrated_from',
            'meta_value'     => 'wp-rss-aggregator',
            'suppress_filters' => true,
        ));

        $map = array();
        foreach ($campaigns as $cid) {
            $source_id = get_post_meta($cid, 'migrated_source_id', true); // e.g. "5_wprss"
            $num       = (int) preg_replace('/\D.*$/', '', (string) $source_id);
            if (!$num) {
                continue;
            }
            $data = get_post_meta($cid, 'campaign_data', true);
            $is_reader = is_array($data) && isset($data['campaign_type']) && $data['campaign_type'] === 'rss_reader';
            // The reader addon registers [wpematico-{post_name}] and its editor
            // hard-syncs wpematico_shortcode_name to the slug, so post_name is the
            // canonical shortcode name (not the stored field, which may lag).
            $post = $is_reader ? get_post($cid) : null;
            $slug = ($post && $post->post_name) ? $post->post_name : '';
            $map[$num] = $slug ? array('type' => 'reader', 'slug' => $slug) : array('type' => 'feed');
        }
        return $map;
    }

    /**
     * Scan post content for WP RSS Aggregator embeds (v5/v4 shortcodes and the
     * Gutenberg block) and describe each occurrence for the replace table.
     *
     * @return array
     */
    private function scan_wpra_shortcodes() {
        global $wpdb;

        $like = array();
        foreach (array('wp-rss-aggregator', 'wp_rss_aggregator', 'wpra-shortcode') as $token) {
            $like[] = $wpdb->prepare('post_content LIKE %s', '%' . $wpdb->esc_like($token) . '%');
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- clauses individually prepared above.
        $post_ids = $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type NOT IN ('wpematico','attachment','revision')
               AND post_status NOT IN ('trash','auto-draft')
               AND (" . implode(' OR ', $like) . ')
             ORDER BY ID DESC'
        );

        if (empty($post_ids)) {
            return array();
        }

        $source_map = $this->get_wpra_source_map();
        // v4/v5 shortcode (hyphen or underscore) and the Gutenberg block comment.
        $pattern = '/\[wp[-_]rss[-_]aggregator\b[^\]]*\]|<!--\s*wp:wpra-shortcode\/wpra-shortcode.*?-->/s';

        $rows = array();
        foreach ($post_ids as $pid) {
            $post = get_post($pid);
            if (!$post || !preg_match_all($pattern, $post->post_content, $matches)) {
                continue;
            }
            $edit_link = get_edit_post_link($pid, 'raw');
            foreach (array_unique($matches[0]) as $embed) {
                $rows[] = $this->describe_wpra_embed($post, $embed, $edit_link, $source_map);
            }
        }
        return $rows;
    }

    /**
     * Extract explicit source ids from an embed's source="" / sources="" attribute
     * (shortcode) or "source":"" JSON (block). Returns a unique list of ints.
     *
     * @return int[]
     */
    private function extract_wpra_source_ids($embed) {
        $ids = array();
        if (preg_match('/sources?["\']?\s*[=:]\s*["\']?([0-9,\s]+)/i', $embed, $m)) {
            foreach (preg_split('/[,\s]+/', $m[1]) as $part) {
                if ($part !== '' && (int) $part > 0) {
                    $ids[] = (int) $part;
                }
            }
        }
        return array_values(array_unique($ids));
    }

    /**
     * Extract the Display id from an embed's id="" attribute (shortcode) or
     * "id":"" JSON (block). 0 when absent (a bare embed uses the default display).
     *
     * @return int
     */
    private function extract_wpra_display_id($embed) {
        if (preg_match('/\bid["\']?\s*[=:]\s*["\']?([0-9]+)/i', $embed, $m)) {
            return (int) $m[1];
        }
        return 0;
    }

    /**
     * Resolve a WP RSS Aggregator Display id to its list of source ids by reading
     * the v5 {prefix}agg_displays table directly (WPRA may be deactivated after
     * import). The `sources` column is pipe-delimited, e.g. "|1|2|".
     *
     * @return int[]
     */
    private function resolve_wpra_display_sources($display_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'agg_displays';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            return array();
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is a trusted prefix+literal.
        $raw = $wpdb->get_var($wpdb->prepare("SELECT sources FROM {$table} WHERE id = %d", $display_id));
        if ($raw === null) {
            return array();
        }
        $ids = array();
        foreach (explode('|', trim((string) $raw, '|')) as $part) {
            if ((int) $part > 0) {
                $ids[] = (int) $part;
            }
        }
        return array_values(array_unique($ids));
    }

    /**
     * The Display a bare embed (no id/source) renders: the wpra_default_display_id
     * option, or the first display row as WPRA itself falls back.
     *
     * @return int
     */
    private function wpra_default_display_id() {
        $default = (int) get_option('wpra_default_display_id');
        if ($default > 0) {
            return $default;
        }
        global $wpdb;
        $table = $wpdb->prefix . 'agg_displays';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            return 0;
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is a trusted prefix+literal.
        return (int) $wpdb->get_var("SELECT id FROM {$table} ORDER BY id ASC LIMIT 1");
    }

    /**
     * Turn one matched embed into a table row: resolve its source attribute to a
     * migrated campaign and decide whether a precise replacement is possible.
     */
    private function describe_wpra_embed($post, $embed, $edit_link, $source_map) {
        $row = array(
            'post_id'     => (int) $post->ID,
            'post_title'  => $post->post_title !== '' ? $post->post_title : __('(no title)', 'wpematico'),
            'post_type'   => $post->post_type,
            'edit_link'   => $edit_link ? $edit_link : '',
            'found'       => $embed,
            'replace'     => '',
            'mappable'    => false,
            'note'        => '',
        );

        // Explicit source id(s) from a source="" / sources="" attr (or block JSON).
        $ids = $this->extract_wpra_source_ids($embed);

        // No explicit sources: the embed points at a Display (id="") — or, when it
        // carries no attributes at all, at the site's default display. A display
        // holds its own list of source ids, which is what we actually map.
        if (empty($ids)) {
            $display_id = $this->extract_wpra_display_id($embed);
            if (!$display_id) {
                $display_id = $this->wpra_default_display_id();
            }
            if ($display_id) {
                $ids = $this->resolve_wpra_display_sources($display_id);
            }
        }

        if (count($ids) !== 1) {
            $row['note'] = empty($ids)
                ? __('Shows all feeds — pick a campaign shortcode manually.', 'wpematico')
                : __('Combines several feeds — replace it manually.', 'wpematico');
            return $row;
        }

        $id = $ids[0];
        if (!isset($source_map[$id])) {
            $row['note'] = __('No migrated campaign matches this source yet — import it first.', 'wpematico');
        } elseif ($source_map[$id]['type'] === 'feed') {
            $row['note'] = __('This feed now creates posts, so it has no display shortcode.', 'wpematico');
        } else {
            $row['replace']  = '[wpematico-' . $source_map[$id]['slug'] . ']';
            $row['mappable'] = true;
        }
        return $row;
    }

    /**
     * Get detected plugins
     */
    public function get_detected_plugins() {
        return $this->detected_plugins;
    }

    /**
     * Get all supported plugins
     */
    public function get_supported_plugins() {
        return $this->supported_plugins;
    }

    /**
     * Instantiate the migrator for a supported-plugin slug (or null).
     *
     * @param string $slug
     * @return WPeMatico_Migrator_Base|null
     */
    private function get_migrator($slug) {
        if (!isset($this->supported_plugins[$slug])) {
            return null;
        }
        $class = $this->supported_plugins[$slug]['migrator_class'];
        if (!class_exists($class)) {
            return null;
        }
        return new $class($this->supported_plugins[$slug]);
    }

    /**
     * Renderable optional-import features for a plugin: only those whose source
     * data is present, each tagged with whether our addon is active ('enabled').
     *
     * @param string $slug
     * @return array
     */
    public function get_optional_features_for($slug) {
        $migrator = $this->get_migrator($slug);
        if (!$migrator) {
            return array();
        }
        $out = array();
        foreach ($migrator->get_optional_features() as $key => $feature) {
            if (empty($feature['has_source_data'])) {
                continue;
            }
            $feature['enabled'] = empty($feature['addon_const']) || defined($feature['addon_const']);
            $out[$key] = $feature;
        }
        return $out;
    }

    /**
     * Source breakdown for a plugin: how many feeds become normal (post-creating)
     * campaigns vs display-only RSS Feed Reader campaigns. Used to show the user
     * exactly what the import will create. Returns null when not applicable.
     *
     * @param string $slug
     * @return array|null  ['feed'=>int,'reader'=>int,'total'=>int] or null.
     */
    public function get_source_breakdown($slug) {
        if ($slug !== 'wp-rss-aggregator') {
            return null;
        }
        $migrator = $this->get_migrator($slug);
        if (!$migrator || !method_exists($migrator, 'get_migration_summary')) {
            return null;
        }
        $summary = $migrator->get_migration_summary();
        if (empty($summary['total_sources'])) {
            return null;
        }
        return array(
            'feed'   => (int) $summary['feed_campaigns'],
            'reader' => (int) $summary['reader_feeds'],
            'total'  => (int) $summary['total_sources'],
            'create' => isset($summary['new_campaigns']) ? (int) $summary['new_campaigns'] : (int) $summary['total_sources'],
            'update' => isset($summary['existing_campaigns']) ? (int) $summary['existing_campaigns'] : 0,
        );
    }

    /**
     * What an import will do to campaigns, split into create vs update (a source
     * already migrated is updated in place, not duplicated). For importers that
     * don't split into feed/reader kinds (e.g. Feedzy). Uses the default merge
     * mode; the real count can differ if the user unmerges feed groups. Returns
     * null when the importer exposes no summary.
     *
     * @param string $slug
     * @return array|null  ['create'=>int, 'update'=>int, 'total'=>int]
     */
    public function get_import_plan($slug) {
        $migrator = $this->get_migrator($slug);
        if (!$migrator || !method_exists($migrator, 'get_migration_summary')) {
            return null;
        }
        $summary = $migrator->get_migration_summary();
        if (!isset($summary['estimated_campaigns_single'])) {
            return null;
        }
        $total  = (int) $summary['estimated_campaigns_single'];
        $update = isset($summary['existing_campaigns_single']) ? (int) $summary['existing_campaigns_single'] : 0;
        $create = isset($summary['new_campaigns_single'])
            ? (int) $summary['new_campaigns_single']
            : max(0, $total - $update);
        return array('create' => $create, 'update' => $update, 'total' => $total);
    }

    /**
     * How many posts the source plugin has already published on this site, so
     * the box can offer to adopt them (and say how many there are). 0 means the
     * option is not offered: either the importer cannot locate the posts, or
     * the source never created any.
     *
     * @param string $slug
     * @return int
     */
    public function get_published_posts_count($slug) {
        $migrator = $this->get_migrator($slug);
        if (!$migrator || !method_exists($migrator, 'get_published_posts_count')) {
            return 0;
        }
        try {
            return (int) $migrator->get_published_posts_count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Whether the WPeMatico RSS Feed Reader addon is installed (active or not).
     * Matches both the wp.org slug (hyphen) and the dev folder (underscore).
     *
     * @return bool
     */
    private function reader_plugin_installed() {
        if (defined('WPEMATICO_RSS_FEED_READER_VER')) {
            return true;
        }
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        foreach (get_plugins() as $file => $data) {
            if ((strpos($file, 'rss_feed_reader') !== false || strpos($file, 'rss-feed-reader') !== false)
                && stripos($file, 'wpematico') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Whether a plugin's migration needs the RSS Feed Reader addon to be complete.
     *
     * WP RSS Aggregator "display-only" feeds (no Feed to Post) map to WPeMatico
     * rss_reader campaigns, which require the free WPeMatico RSS Feed Reader addon.
     * When such feeds exist and the addon is not active, the user must either
     * install/activate it or accept that those feeds are skipped.
     *
     * @param string $slug
     * @return array|null  Requirement data, or null when nothing is required.
     */
    public function get_reader_requirement($slug) {
        if ($slug !== 'wp-rss-aggregator') {
            return null;
        }
        if (defined('WPEMATICO_RSS_FEED_READER_VER')) {
            return null; // addon already active — nothing to require.
        }

        $breakdown = $this->get_source_breakdown($slug);
        if (empty($breakdown['reader'])) {
            return null;
        }

        return array(
            'reader_feeds'     => $breakdown['reader'],
            'feed_campaigns'   => $breakdown['feed'],
            'total'            => $breakdown['total'],
            // No post-creating feeds at all: importing without the addon brings nothing.
            'all_display_only' => ($breakdown['feed'] === 0),
            'installed'        => $this->reader_plugin_installed(),
            'install_url'      => 'https://downloads.wordpress.org/plugin/wpematico-rss-feed-reader.zip',
            'module'           => 'feed-reader',
        );
    }

    /**
     * Whether the Import action must be blocked because it would create zero
     * campaigns in the current state. Returns null when import is allowed, or
     * ['reason' => string] with a human explanation when it must be disabled.
     *
     * Known case: WP RSS Aggregator with only display-only feeds and the RSS
     * Feed Reader addon inactive — every source would be skipped. Feedzy has no
     * display-only concept, so it is only gated if the source has no items.
     *
     * @param string $slug
     * @return array|null
     */
    public function get_import_gate($slug) {
        $breakdown = $this->get_source_breakdown($slug);
        if ($breakdown !== null) {
            // Display-only feeds only import when the reader addon is active.
            $reader_active = defined('WPEMATICO_RSS_FEED_READER_VER');
            $creatable = $breakdown['feed'] + ($reader_active ? $breakdown['reader'] : 0);
            if ($creatable < 1) {
                return array(
                    'reason' => __('Nothing to import yet: these feeds only display items. Install the RSS Feed Reader addon above to import them.', 'wpematico'),
                );
            }
            return null;
        }

        $migrator = $this->get_migrator($slug);
        if ($migrator && (int) $migrator->get_items_count() < 1) {
            return array(
                'reason' => __('There is nothing to import from this plugin.', 'wpematico'),
            );
        }
        return null;
    }

    /**
     * Provenance badge (disabled icon button) for the campaigns-list state column.
     * Returns '' for campaigns that were not imported by the toolkit.
     *
     * @param int $post_id
     * @return string  Safe HTML (static SVG + escaped dynamic attributes).
     */
    public static function provenance_badge($post_id) {
        $from = get_post_meta($post_id, 'migrated_from', true);
        if (empty($from)) {
            return '';
        }

        // Cache icon/label per source slug: this runs once per campaign row.
        static $cache = array();
        if (!isset($cache[$from])) {
            $migrator = self::get_instance()->get_migrator($from);
            $cache[$from] = $migrator
                ? array('icon' => $migrator->get_icon_svg(), 'label' => $migrator->get_source_label())
                : array('icon' => '', 'label' => $from);
        }
        $icon  = $cache[$from]['icon'];
        $label = $cache[$from]['label'];

        $source_id    = (string) get_post_meta($post_id, 'migrated_source_id', true);
        $source_title = (string) get_post_meta($post_id, 'migrated_source_title', true);
        $source_num   = preg_replace('/\D.*$/', '', $source_id); // leading numeric source ID

        /* translators: %s: source plugin name. */
        $tip = sprintf(__('Imported from %s', 'wpematico'), $label);
        if ($source_num !== '') {
            /* translators: %s: original item ID in the source plugin. */
            $tip .= ' ' . sprintf(__('(original #%s)', 'wpematico'), $source_num);
        }
        if ($source_title !== '') {
            $tip .= ': ' . $source_title;
        }

        // Flat 2D marker: source icon inside a grey circle, sized to the state
        // buttons' height, shown before the campaign-type label. Self-styled inline
        // because the campaigns-list page does not enqueue the Migration Toolkit CSS.
        return '<span class="wpem-provenance"'
            . ' style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;background:#dcdcde;color:#50575e;vertical-align:middle;margin-right:7px;line-height:0;"'
            . ' title="' . esc_attr($tip) . '">' . $icon . '</span>';
    }
}

// Instantiate on init, not at load: the constructor runs setup_supported_plugins()
// which calls __() on the description strings, and translating before init triggers
// WP 6.7's _load_textdomain_just_in_time notice. Its AJAX/enqueue hooks fire after
// init anyway, so nothing is lost by deferring.
add_action('init', array('WPeMatico_Migration_Toolkit', 'get_instance'));