<?php
/**
 * Migration Toolkit Box View — tabbed importer.
 * Rendered in the WPeMatico Tools page, "Migration Toolkit" section.
 *
 * @package WPeMatico
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get the main plugin instance.
$migration_toolkit = WPeMatico_Migration_Toolkit::get_instance();
$detected_plugins  = $migration_toolkit->get_detected_plugins();
$supported_plugins = $migration_toolkit->get_supported_plugins();

// Tooltip texts, same source as the Help tab of this screen.
$helptip = wpematico_help_migration('tips');

/**
 * Render a (?) tooltip handled by tipTip, the same way the campaign editor does.
 *
 * $tip arrives already run through htmlentities() (which escapes quotes too), so
 * it is attribute-safe as is. Escaping it again would encode the ampersands of
 * those entities and the tooltip would show "&lt;br /&gt;" as text instead of a
 * line break — tipTip pulls the browser-decoded title and injects it as HTML.
 *
 * @param string $tip Already html-entitied tip text.
 */
if (!function_exists('wpematico_migration_help_tip')) {
    function wpematico_migration_help_tip($tip) {
        if (empty($tip)) {
            return;
        }
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- entitied above.
        echo '<span class="dashicons dashicons-editor-help help_tip wpematico-help-tip"'
            . ' title-heltip="' . $tip . '" title="' . $tip . '"></span>';
    }
}

/**
 * Render the shared list of import options for a given plugin context.
 *
 * @param string $slug    Plugin slug ('manual' for the manual tab).
 * @param array  $plugin  Plugin data (may be empty for manual).
 */
if (!function_exists('wpematico_migration_render_options')) {
    function wpematico_migration_render_options($slug, $plugin = array(), $optional_features = array(), $published_posts = 0) {
        $helptip         = wpematico_help_migration('tips');
        $has_feed_groups = !empty($plugin['has_feed_groups']);
        $plugin_active   = !empty($plugin['plugin_file']) && function_exists('is_plugin_active') && is_plugin_active($plugin['plugin_file']);
        ?>
        <div class="wpematico-options-list">
            <label class="wpematico-option-item">
                <input type="checkbox" class="wpematico-import-check" value="campaigns" checked disabled>
                <span class="wpematico-checkmark"></span>
                <span class="wpematico-option-label has-note">
                    <?php esc_html_e('Import campaigns &amp; feeds', 'wpematico'); ?>
                    <?php wpematico_migration_help_tip($helptip['campaigns']); ?>
                    <span class="description wpematico-option-note">
                        <?php esc_html_e('Campaigns that create posts are imported deactivated, whether or not the feed was running.', 'wpematico'); ?>
                    </span>
                </span>
            </label>

            <?php if ($has_feed_groups) :
                $source_name = !empty($plugin['name']) ? $plugin['name'] : __('the source plugin', 'wpematico'); ?>
            <label class="wpematico-option-item">
                <input type="checkbox" class="wpematico-feed-group-check" data-plugin="<?php echo esc_attr($slug); ?>" checked>
                <span class="wpematico-checkmark"></span>
                <span class="wpematico-option-label has-note">
                    <?php esc_html_e('Keep the feeds of a group together in one campaign', 'wpematico'); ?>
                    <?php wpematico_migration_help_tip($helptip['feed_groups']); ?>
                    <span class="description wpematico-option-note">
                        <?php
                        /* translators: %s: source plugin name. */
                        printf(esc_html__('A group is imported through the %s import job that uses it, never on its own. Unchecked, each feed of the group becomes its own campaign.', 'wpematico'), esc_html($source_name));
                        ?>
                    </span>
                </span>
            </label>
            <?php endif; ?>

            <?php foreach ($optional_features as $feature_key => $feature) :
                $is_enabled = !empty($feature['enabled']); ?>
            <label class="wpematico-option-item<?php echo $is_enabled ? '' : ' is-disabled'; ?>">
                <input type="checkbox" class="wpematico-optional-check" value="<?php echo esc_attr($feature_key); ?>" data-plugin="<?php echo esc_attr($slug); ?>" <?php checked($is_enabled); ?> <?php disabled(!$is_enabled); ?>>
                <span class="wpematico-checkmark"></span>
                <span class="wpematico-option-label">
                    <?php echo esc_html($feature['label']); ?>
                    <?php if (!$is_enabled && !empty($feature['addon_name'])) : ?>
                        <span class="wpematico-badge-addon">
                            <?php
                            /* translators: %s: required addon name. */
                            printf(esc_html__('Requires %s', 'wpematico'), esc_html($feature['addon_name']));
                            ?>
                        </span>
                    <?php endif; ?>
                    <?php wpematico_migration_help_tip(!empty($feature['note']) ? htmlentities($feature['note']) : $helptip['optional_features']); ?>
                </span>
            </label>
            <?php endforeach; ?>

            <?php if ($published_posts > 0) : ?>
            <label class="wpematico-option-item">
                <input type="checkbox" class="wpematico-import-posts-check" data-plugin="<?php echo esc_attr($slug); ?>" checked>
                <span class="wpematico-checkmark"></span>
                <span class="wpematico-option-label">
                    <?php
                    printf(
                        esc_html(_n(
                            'Adopt the %d post already published by this plugin',
                            'Adopt the %d posts already published by this plugin',
                            $published_posts,
                            'wpematico'
                        )),
                        (int) $published_posts
                    );
                    ?>
                    <?php wpematico_migration_help_tip($helptip['published_posts']); ?>
                </span>
            </label>
            <?php endif; ?>

            <?php if ($plugin_active) : ?>
            <label class="wpematico-option-item wpematico-option-highlight">
                <input type="checkbox" class="wpematico-auto-deactivate-check" data-plugin="<?php echo esc_attr($slug); ?>" checked>
                <span class="wpematico-checkmark"></span>
                <span class="wpematico-option-label">
                    <strong><?php esc_html_e('Deactivate the source plugin after import', 'wpematico'); ?></strong>
                    <?php wpematico_migration_help_tip($helptip['auto_deactivate']); ?>
                </span>
            </label>
            <?php endif; ?>

            <?php
            $has_locked_feature = false;
            foreach ($optional_features as $feature) {
                if (empty($feature['enabled'])) {
                    $has_locked_feature = true;
                    break;
                }
            }
            if ($has_locked_feature) : ?>
            <p class="description wpematico-optional-note">
                <span class="dashicons dashicons-info-outline"></span>
                <?php esc_html_e('You can import now without the required addon — no source data is lost. Install the addon later and re-import this plugin to fill in the missing fields.', 'wpematico'); ?>
            </p>
            <?php endif; ?>
        </div>
        <?php
    }
}
?>

<div class="postbox wpematico-migration-toolkit-box">
    <h3 class="hndle ui-sortable-handle">
        <span class="dashicons dashicons-migrate"></span>
        <span><?php esc_html_e('Migration Toolkit', 'wpematico'); ?></span>
        <?php wpematico_migration_help_tip($helptip['migration_intro']); ?>
    </h3>

    <div class="inside">
        <p class="description wpematico-migration-intro">
            <?php esc_html_e('Moving from another RSS importing plugin? Select it below to recreate its feeds as WPeMatico campaigns in a couple of clicks.', 'wpematico'); ?>
        </p>

            <div class="wpematico-migration-tabs">
                <div class="wpematico-tabs-header">
                    <?php $first = true; ?>
                    <?php foreach ($detected_plugins as $slug => $plugin) : ?>
                        <button type="button" class="wpematico-tab-button <?php echo $first ? 'is-active' : ''; ?>" data-tab="<?php echo esc_attr($slug); ?>">
                            <span class="dashicons dashicons-admin-plugins"></span>
                            <?php echo esc_html($plugin['name']); ?>
                        </button>
                        <?php $first = false; ?>
                    <?php endforeach; ?>

                    <button type="button" class="wpematico-tab-button <?php echo empty($detected_plugins) ? 'is-active' : ''; ?>" data-tab="manual">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <?php esc_html_e('Other', 'wpematico'); ?>
                    </button>
                </div>

                <div class="wpematico-tabs-body">
                    <?php $first = true; ?>
                    <?php foreach ($detected_plugins as $slug => $plugin) : ?>
                        <div id="tab-content-<?php echo esc_attr($slug); ?>" class="wpematico-tab-content <?php echo $first ? 'is-active' : ''; ?>" data-plugin="<?php echo esc_attr($slug); ?>">
                            <div class="wpematico-import-options">
                                <h4 class="wpematico-import-title">
                                    <?php
                                    /* translators: %s: source plugin name. */
                                    printf(esc_html__('Import from %s', 'wpematico'), esc_html($plugin['name']));
                                    ?>
                                </h4>
                                <?php if (!empty($plugin['description'])) : ?>
                                    <p class="description"><?php echo esc_html($plugin['description']); ?></p>
                                <?php endif; ?>

                                <?php wpematico_migration_render_options($slug, $plugin, $migration_toolkit->get_optional_features_for($slug), $migration_toolkit->get_published_posts_count($slug)); ?>
                            </div>

                            <?php $breakdown = $migration_toolkit->get_source_breakdown($slug);
                            if ($breakdown) : ?>
                            <p class="description wpematico-source-breakdown">
                                <span class="dashicons dashicons-list-view"></span>
                                <?php
                                $bd_update = isset($breakdown['update']) ? (int) $breakdown['update'] : 0;
                                $bd_create = isset($breakdown['create']) ? (int) $breakdown['create'] : (int) $breakdown['total'];
                                if ($bd_update > 0) {
                                    // Re-import: some sources were already migrated (updated in place).
                                    if ($bd_create > 0) {
                                        printf(
                                            /* translators: 1: campaigns to create phrase, 2: campaigns to update phrase. */
                                            esc_html__('This import will create %1$s and update %2$s.', 'wpematico'),
                                            esc_html(sprintf(_n('%d campaign', '%d campaigns', $bd_create, 'wpematico'), $bd_create)),
                                            esc_html(sprintf(_n('%d already migrated', '%d already migrated', $bd_update, 'wpematico'), $bd_update))
                                        );
                                    } else {
                                        printf(
                                            esc_html(_n('This import will update %d already migrated campaign.', 'This import will update %d already migrated campaigns.', $bd_update, 'wpematico')),
                                            $bd_update
                                        );
                                    }
                                    if ($breakdown['reader'] > 0) {
                                        echo ' ' . esc_html(sprintf(_n('%d of them is an RSS Feed Reader campaign.', '%d of them are RSS Feed Reader campaigns.', $breakdown['reader'], 'wpematico'), $breakdown['reader']));
                                    }
                                } elseif ($breakdown['feed'] > 0 && $breakdown['reader'] > 0) {
                                    printf(
                                        /* translators: 1: campaigns phrase, 2: reader campaigns phrase. */
                                        esc_html__('This import will create %1$s and %2$s.', 'wpematico'),
                                        esc_html(sprintf(_n('%d campaign', '%d campaigns', $breakdown['feed'], 'wpematico'), $breakdown['feed'])),
                                        esc_html(sprintf(_n('%d RSS Feed Reader campaign', '%d RSS Feed Reader campaigns', $breakdown['reader'], 'wpematico'), $breakdown['reader']))
                                    );
                                } elseif ($breakdown['feed'] > 0) {
                                    printf(
                                        esc_html(_n('This import will create %d campaign.', 'This import will create %d campaigns.', $breakdown['feed'], 'wpematico')),
                                        (int) $breakdown['feed']
                                    );
                                } else {
                                    printf(
                                        esc_html(_n('This import will create %d RSS Feed Reader campaign.', 'This import will create %d RSS Feed Reader campaigns.', $breakdown['reader'], 'wpematico')),
                                        (int) $breakdown['reader']
                                    );
                                }
                                ?>
                            </p>
                            <?php elseif (($plan = $migration_toolkit->get_import_plan($slug)) && $plan['total'] > 0) : ?>
                            <p class="description wpematico-source-breakdown">
                                <span class="dashicons dashicons-list-view"></span>
                                <?php
                                if ($plan['create'] > 0 && $plan['update'] > 0) {
                                    printf(
                                        /* translators: 1: campaigns to create phrase, 2: campaigns to update phrase. */
                                        esc_html__('This import will create %1$s and update %2$s.', 'wpematico'),
                                        esc_html(sprintf(_n('%d campaign', '%d campaigns', $plan['create'], 'wpematico'), $plan['create'])),
                                        esc_html(sprintf(_n('%d already migrated', '%d already migrated', $plan['update'], 'wpematico'), $plan['update']))
                                    );
                                } elseif ($plan['update'] > 0) {
                                    printf(
                                        esc_html(_n('This import will update %d already migrated campaign.', 'This import will update %d already migrated campaigns.', $plan['update'], 'wpematico')),
                                        (int) $plan['update']
                                    );
                                } else {
                                    printf(
                                        esc_html(_n('This import will create %d campaign.', 'This import will create %d campaigns.', $plan['create'], 'wpematico')),
                                        (int) $plan['create']
                                    );
                                }
                                ?>
                            </p>
                            <?php endif; ?>

                            <?php if ($breakdown && $breakdown['reader'] > 0) : ?>
                            <label class="wpematico-option-item">
                                <input type="checkbox" class="wpematico-reader-activate-check" data-plugin="<?php echo esc_attr($slug); ?>" checked>
                                <span class="wpematico-checkmark"></span>
                                <span class="wpematico-option-label">
                                    <?php esc_html_e('Activate reader campaigns and fetch their content now', 'wpematico'); ?>
                                    <?php wpematico_migration_help_tip($helptip['reader_activate']); ?>
                                </span>
                            </label>
                            <?php endif; ?>

                            <?php $reader_req = $migration_toolkit->get_reader_requirement($slug);
                            if ($reader_req) : ?>
                            <div class="wpematico-reader-requirement notice notice-warning inline"
                                 data-plugin="<?php echo esc_attr($slug); ?>"
                                 data-install-url="<?php echo esc_url($reader_req['install_url']); ?>"
                                 data-module="<?php echo esc_attr($reader_req['module']); ?>"
                                 data-installed="<?php echo $reader_req['installed'] ? '1' : '0'; ?>">
                                <p>
                                    <span class="dashicons dashicons-info-outline"></span>
                                    <?php
                                    if ($reader_req['all_display_only']) {
                                        printf(
                                            esc_html(_n(
                                                'This feed only displays items (it does not create posts). Add the free WPeMatico RSS Feed Reader addon to import it — without it there is nothing to import.',
                                                'All %d feeds only display items (they do not create posts). Add the free WPeMatico RSS Feed Reader addon to import them — without it there is nothing to import.',
                                                $reader_req['reader_feeds'],
                                                'wpematico'
                                            )),
                                            (int) $reader_req['reader_feeds']
                                        );
                                    } else {
                                        printf(
                                            esc_html(_n(
                                                '%d of these feeds only displays items (it does not create posts). To import it you need the free WPeMatico RSS Feed Reader addon.',
                                                '%d of these feeds only display items (they do not create posts). To import them you need the free WPeMatico RSS Feed Reader addon.',
                                                $reader_req['reader_feeds'],
                                                'wpematico'
                                            )),
                                            (int) $reader_req['reader_feeds']
                                        );
                                    }
                                    ?>
                                </p>
                                <p>
                                    <button type="button" class="button button-primary wpematico-install-reader-btn">
                                        <span class="dashicons <?php echo $reader_req['installed'] ? 'dashicons-yes-alt' : 'dashicons-download'; ?>"></span>
                                        <?php
                                        echo $reader_req['installed']
                                            ? esc_html__('Activate RSS Feed Reader', 'wpematico')
                                            : esc_html__('Install &amp; activate RSS Feed Reader', 'wpematico');
                                        ?>
                                    </button>
                                    <?php if (!$reader_req['all_display_only']) : ?>
                                    <span class="wpematico-reader-or">
                                        <?php esc_html_e('…or import now and skip the display-only feeds.', 'wpematico'); ?>
                                    </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?php endif; ?>

                            <?php $import_gate = $migration_toolkit->get_import_gate($slug); ?>
                            <div class="wpematico-action-buttons">
                                <button type="button" class="button button-primary wpematico-import-btn" data-plugin="<?php echo esc_attr($slug); ?>" data-gated="<?php echo $import_gate ? '1' : '0'; ?>" <?php disabled((bool) $import_gate); ?>>
                                    <?php esc_html_e('Import', 'wpematico'); ?>
                                </button>
                                <button type="button" class="button wpematico-preview-btn" data-plugin="<?php echo esc_attr($slug); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php esc_html_e('Preview', 'wpematico'); ?>
                                </button>
                                <?php wpematico_migration_help_tip($helptip['preview']); ?>
                                <?php if ($import_gate) : ?>
                                <span class="wpematico-import-gate-reason"><?php echo esc_html($import_gate['reason']); ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if ($slug === 'wp-rss-aggregator') : ?>
                            <div class="wpematico-shortcode-scan" data-plugin="wp-rss-aggregator" style="display:none;"></div>
                            <?php endif; ?>
                        </div>
                        <?php $first = false; ?>
                    <?php endforeach; ?>

                    <div id="tab-content-manual" class="wpematico-tab-content <?php echo empty($detected_plugins) ? 'is-active' : ''; ?>" data-plugin="manual">
                        <div class="wpematico-import-options">
                            <h4 class="wpematico-import-title"><?php esc_html_e('Other plugin', 'wpematico'); ?></h4>
                            <?php if (empty($detected_plugins)) : ?>
                                <p class="description"><?php esc_html_e('No data from a supported plugin was found on this site. Set up feeds in a supported plugin and its importer will show up here automatically.', 'wpematico'); ?></p>
                            <?php endif; ?>
                            <p class="description"><?php esc_html_e('Missing a plugin you want to migrate from? Let us know and we may add it.', 'wpematico'); ?></p>
                            <p>
                                <a href="https://etruel.com/contact/" target="_blank" rel="noopener noreferrer" class="button">
                                    <span class="dashicons dashicons-email-alt"></span>
                                    <?php esc_html_e('Request integration', 'wpematico'); ?>
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
    </div>
</div>

<!-- Migration progress modal -->
<div id="wpematico-migration-modal" class="wpematico-modal" style="display:none;">
    <div class="wpematico-modal-content">
        <div class="wpematico-modal-header">
            <h3><?php esc_html_e('Migration in progress', 'wpematico'); ?></h3>
            <span class="wpematico-modal-close dashicons dashicons-no-alt"></span>
        </div>
        <div class="wpematico-modal-body">
            <div class="migration-progress">
                <div class="spinner is-active"></div>
                <p class="migration-status"><?php esc_html_e('Preparing migration…', 'wpematico'); ?></p>
            </div>
            <div class="migration-results" style="display:none;"></div>
        </div>
        <div class="wpematico-modal-footer">
            <button type="button" class="button wpematico-modal-close-btn"><?php esc_html_e('Close', 'wpematico'); ?></button>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=wpematico')); ?>" class="button button-primary wpematico-view-campaigns-btn" style="display:none;">
                <?php esc_html_e('View campaigns', 'wpematico'); ?>
            </a>
        </div>
    </div>
</div>

<!-- Preview modal -->
<div id="wpematico-preview-modal" class="wpematico-modal" style="display:none;">
    <div class="wpematico-modal-content wpematico-modal-large">
        <div class="wpematico-modal-header">
            <h3><?php esc_html_e('Migration preview', 'wpematico'); ?></h3>
            <span class="wpematico-modal-close dashicons dashicons-no-alt"></span>
        </div>
        <div class="wpematico-modal-body">
            <div class="preview-loading">
                <div class="spinner is-active"></div>
                <p><?php esc_html_e('Loading preview…', 'wpematico'); ?></p>
            </div>
            <div class="preview-content" style="display:none;">
                <p class="preview-summary"></p>
                <table class="widefat striped preview-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Title', 'wpematico'); ?></th>
                            <th><?php esc_html_e('Feed URL(s)', 'wpematico'); ?></th>
                            <th><?php esc_html_e('Post type', 'wpematico'); ?></th>
                            <th><?php esc_html_e('Status', 'wpematico'); ?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        <div class="wpematico-modal-footer">
            <button type="button" class="button wpematico-modal-close-btn"><?php esc_html_e('Close', 'wpematico'); ?></button>
            <button type="button" class="button button-primary wpematico-preview-migrate-btn"><?php esc_html_e('Start migration', 'wpematico'); ?></button>
        </div>
    </div>
</div>
