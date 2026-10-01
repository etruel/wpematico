<?php
/**
 * Migration Toolkit detector — the one piece that lives outside the "Plugin
 * Importers" module, which is off by default and keeps everything else of the
 * toolkit unloaded.
 *
 * It answers "is there anything worth importing here?" with three cheap queries,
 * caches the answer and surfaces a notice. It deliberately never touches the
 * migrators, and re-asks only on a plugin upgrade or activation, on a module
 * toggle, or twice a day at most.
 *
 * @package WPeMatico
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPeMatico_Migration_Detect {

    /** Module id registered in includes/module/actions.php. */
    const MODULE = 'plugin-wffimporters';

    /** Cached detection result. */
    const CACHE = 'wpematico_importers_detected';

    /** Notice dismissals, keyed by variant + detected set. */
    const DISMISSED = 'wpematico_importers_notice_dismissed';

    /**
     * How long a detection result is trusted. Long on purpose: the events that
     * really change the answer (upgrade, plugin activation, module toggle)
     * refresh it explicitly, so this is just a slow safety net for data added to
     * an already-installed source plugin.
     */
    const CACHE_TTL = 12 * HOUR_IN_SECONDS;

    /**
     * Register the few hooks the detector needs. Nothing here touches the
     * database; the scan only runs on the events below.
     */
    public static function hooks() {
        add_action('activated_plugin', array(__CLASS__, 'refresh'));
        add_action('admin_init', array(__CLASS__, 'maybe_dismiss'));
        add_action('admin_notices', array(__CLASS__, 'render_notice'));
    }

    /**
     * Whether the Plugin Importers module is on. One read of an autoloaded
     * option — cheap enough to be the gate for loading the whole toolkit.
     *
     * @return bool
     */
    public static function module_active() {
        return in_array(self::MODULE, (array) get_option('wpematico_active_modules', array()), true);
    }

    /**
     * Supported sources and how to tell, cheaply, that they left data behind.
     * This knowingly duplicates a sliver of the manager's supported-plugins list:
     * the point of the gate is that the manager is not loaded. Keep the slugs in
     * sync with WPeMatico_Migration_Toolkit::setup_supported_plugins().
     *
     * @return array slug => array('name' => string, 'check' => callable)
     */
    private static function sources() {
        return array(
            'feedzy-rss-feeds' => array(
                'name'  => 'Feedzy RSS Feeds',
                'check' => array(__CLASS__, 'has_feedzy_data'),
            ),
            'wp-rss-aggregator' => array(
                'name'  => 'WP RSS Aggregator',
                'check' => array(__CLASS__, 'has_wpra_data'),
            ),
        );
    }

    /**
     * Import jobs left by Feedzy, whether or not the plugin is still active.
     *
     * @return bool
     */
    private static function has_feedzy_data() {
        global $wpdb;
        return (bool) $wpdb->get_var(
            "SELECT 1 FROM {$wpdb->posts}
             WHERE post_type = 'feedzy_imports' AND post_status NOT IN ('trash', 'auto-draft')
             LIMIT 1"
        );
    }

    /**
     * Feed sources in WP RSS Aggregator's own table (v5 keeps them out of the
     * posts table, so the table may simply not exist).
     *
     * @return bool
     */
    private static function has_wpra_data() {
        global $wpdb;
        $table = $wpdb->prefix . 'agg_sources';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            return false;
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- trusted prefix + literal.
        return (bool) $wpdb->get_var("SELECT 1 FROM {$table} LIMIT 1");
    }

    /**
     * Run the checks now.
     *
     * @return array slug => human name, for the sources that have data.
     */
    public static function scan() {
        $found = array();
        foreach (self::sources() as $slug => $source) {
            if (call_user_func($source['check'])) {
                $found[$slug] = $source['name'];
            }
        }
        return $found;
    }

    /**
     * Re-run the checks and cache the result. Hooked to the events that can
     * change the answer; also called straight from the upgrade routine.
     *
     * @return array
     */
    public static function refresh() {
        $found = self::scan();
        set_transient(self::CACHE, $found, self::CACHE_TTL);
        return $found;
    }

    /**
     * Cached detection result, scanning once if the cache has lapsed.
     *
     * @return array slug => human name.
     */
    public static function detected() {
        $found = get_transient(self::CACHE);
        return is_array($found) ? $found : self::refresh();
    }

    /**
     * Drop the cached result and any dismissal. Used when the module is toggled
     * so the next state starts clean.
     */
    public static function forget() {
        delete_transient(self::CACHE);
        delete_option(self::DISMISSED);
    }

    /* --------------------------------------------------------------------- *
     *  Notice
     * --------------------------------------------------------------------- */

    /**
     * Identity of a notice: its variant plus the exact set of sources detected.
     * Dismissing hides that combination only, so a source appearing later still
     * gets announced, and turning the module on changes the variant and asks
     * again with the right call to action.
     *
     * @param string $variant
     * @param array  $detected
     * @return string
     */
    private static function notice_key($variant, $detected) {
        $slugs = array_keys($detected);
        sort($slugs);
        return $variant . '|' . implode(',', $slugs);
    }

    /**
     * Handle the dismiss link.
     */
    public static function maybe_dismiss() {
        if (empty($_GET['wpematico_dismiss_importers']) || !current_user_can('manage_options')) {
            return;
        }
        $key = sanitize_text_field(wp_unslash($_GET['wpematico_dismiss_importers']));
        if (!wp_verify_nonce((isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : ''), 'wpematico_dismiss_importers')) {
            return;
        }
        $dismissed   = (array) get_option(self::DISMISSED, array());
        $dismissed[] = $key;
        update_option(self::DISMISSED, array_values(array_unique($dismissed)), false);
    }

    /**
     * Tell the admin there is something to import — either that the importer
     * has to be switched on, or that it is on and ready to use.
     */
    public static function render_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $screen_page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        $section     = isset($_GET['section']) ? sanitize_text_field(wp_unslash($_GET['section'])) : '';

        $active  = self::module_active();
        $variant = $active ? 'on' : 'off';

        // Don't nag on the page the notice is pointing at.
        if (!$active && $screen_page === 'wpematico_dashboard') {
            return;
        }
        if ($active && $screen_page === 'wpematico_tools' && $section === 'migration') {
            return;
        }

        $detected = self::detected();
        if (empty($detected)) {
            return;
        }

        $key = self::notice_key($variant, $detected);
        if (in_array($key, (array) get_option(self::DISMISSED, array()), true)) {
            return;
        }

        $names = implode(', ', array_map('esc_html', $detected));

        if ($active) {
            $target = admin_url('admin.php?page=wpematico_tools&section=migration');
            $button = __('Import them now', 'wpematico');
            $text   = sprintf(
                /* translators: %s: comma-separated list of source plugin names. */
                _n(
                    'WPeMatico found feeds from %s on this site. Import them as campaigns and you can drop the other plugin.',
                    'WPeMatico found feeds from these plugins on this site: %s. Import them as campaigns and you can drop the other plugins.',
                    count($detected),
                    'wpematico'
                ),
                $names
            );
        } else {
            $target = admin_url('admin.php?page=wpematico_dashboard');
            $button = __('Enable Plugin Importers', 'wpematico');
            $text   = sprintf(
                /* translators: %s: comma-separated list of source plugin names. */
                _n(
                    'WPeMatico found feeds from %s on this site. Enable the Plugin Importers module to bring them over as campaigns.',
                    'WPeMatico found feeds from these plugins on this site: %s. Enable the Plugin Importers module to bring them over as campaigns.',
                    count($detected),
                    'wpematico'
                ),
                $names
            );
        }

        $dismiss_url = wp_nonce_url(
            add_query_arg('wpematico_dismiss_importers', rawurlencode($key)),
            'wpematico_dismiss_importers'
        );
        ?>
        <div class="notice notice-warning">
            <p><strong><?php echo esc_html__('WPeMatico Migration Toolkit', 'wpematico'); ?></strong></p>
            <p><?php echo wp_kses_post($text); ?></p>
            <p>
                <a href="<?php echo esc_url($target); ?>" class="button button-primary"><?php echo esc_html($button); ?></a>
                <a href="<?php echo esc_url($dismiss_url); ?>" class="button-link" style="margin-left:8px;"><?php echo esc_html__('Dismiss', 'wpematico'); ?></a>
            </p>
        </div>
        <?php
    }
}
