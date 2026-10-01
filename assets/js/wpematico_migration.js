/**
 * WPeMatico Migration Toolkit - Admin JavaScript
 */
(function($) {
    'use strict';

    var currentPlugin = '';
    var currentUiKey = '';
    var autoDeactivate = false;
    var feedGroupMode = 'single';
    var optionalImports = [];
    var migrationSucceeded = false;

    // Read the "merge feed groups" choice for a plugin tab.
    // Checked (or no checkbox) = merge every feed of a group into ONE campaign
    // (faithful 1:1, 'single'). Unchecked = one campaign per feed ('multiple').
    function getFeedGroupMode(plugin) {
        var $cb = $('.wpematico-feed-group-check[data-plugin="' + plugin + '"]');
        return (!$cb.length || $cb.is(':checked')) ? 'single' : 'multiple';
    }

    // Collect the ticked optional addon-gated imports for a plugin tab.
    function getOptionalImports(plugin) {
        var values = [];
        $('.wpematico-optional-check[data-plugin="' + plugin + '"]:checked:not(:disabled)').each(function() {
            values.push($(this).val());
        });
        return values;
    }

    // Whether this plugin's import is gated (would create no campaigns). Read from
    // the Import button's disabled/data-gated state set server-side at render.
    function isPluginGated(plugin) {
        var $btn = $('.wpematico-import-btn[data-plugin="' + plugin + '"]');
        return $btn.length ? ($btn.data('gated') == 1 || $btn.prop('disabled')) : false;
    }

    $(document).ready(function() {
        initTabs();
        initEventHandlers();
        initHelpTips();
        activateTabFromHash();
        // Show the "replace shortcodes" table for sites that already migrated WPRA.
        if ($('.wpematico-shortcode-scan[data-plugin="wp-rss-aggregator"]').length) {
            scanShortcodes('wp-rss-aggregator');
        }
    });

    // Styled tooltips on the (?) icons, same settings as the campaign editor.
    // The tipTip library is already enqueued for the Tools page.
    function initHelpTips() {
        if (!$.fn.tipTip) {
            return;
        }
        $('.help_tip').tipTip({
            maxWidth: '400px',
            edgeOffset: 5,
            fadeIn: 50,
            fadeOut: 50,
            keepAlive: true,
            defaultPosition: 'right'
        });
    }

    // Reopen the tab a post-migration reload left in the hash (#mig-tab-<slug>).
    function activateTabFromHash() {
        var m = /^#mig-tab-(.+)$/.exec(window.location.hash);
        if (!m) return;
        var $btn = $('.wpematico-tab-button[data-tab="' + m[1] + '"]');
        if ($btn.length) {
            $btn.trigger('click');
        }
    }

    function initTabs() {
        $('.wpematico-tab-button').on('click', function() {
            var tabId = $(this).data('tab');
            
            $('.wpematico-tab-button').removeClass('is-active');
            $('.wpematico-tab-content').removeClass('is-active').hide();
            
            $(this).addClass('is-active');
            
            if (tabId === 'manual' || tabId === 'manual-only') {
                $('#tab-content-manual, #tab-content-manual-only').addClass('is-active').show();
            } else {
                $('#tab-content-' + tabId).addClass('is-active').show();
            }
            
            currentPlugin = (tabId === 'manual' || tabId === 'manual-only') ? '' : tabId;
        });
    }

    function initEventHandlers() {
        // Manual plugin selection
        $('#wpematico-manual-plugin-select').on('change', function() {
            var selected = $(this).val();
            
            if (selected) {
                $('#manual-options-container').slideDown();
                $('#wpematico-manual-import-btn, #wpematico-manual-preview-btn').prop('disabled', false);
                currentPlugin = selected;
            } else {
                $('#manual-options-container').slideUp();
                $('#wpematico-manual-import-btn, #wpematico-manual-preview-btn').prop('disabled', true);
            }
        });

        // Import button
        $('.wpematico-import-btn').on('click', function(e) {
            e.preventDefault();
            startMigration($(this).data('plugin'));
        });

        // Preview button
        $('.wpematico-preview-btn').on('click', function(e) {
            e.preventDefault();
            showPreview($(this).data('plugin'));
        });

        // Manual buttons (checkboxes on the manual tab use data-plugin="manual").
        $('#wpematico-manual-import-btn').on('click', function(e) {
            e.preventDefault();
            var plugin = $('#wpematico-manual-plugin-select').val();
            if (plugin) {
                startMigration(plugin, 'manual');
            }
        });

        $('#wpematico-manual-preview-btn').on('click', function(e) {
            e.preventDefault();
            var plugin = $('#wpematico-manual-plugin-select').val();
            if (plugin) {
                showPreview(plugin, 'manual');
            }
        });

        // Modals
        $('.wpematico-modal-close, .wpematico-modal-close-btn').on('click', function() {
            closeModals();
        });

        $(window).on('click', function(e) {
            if ($(e.target).hasClass('wpematico-modal')) {
                closeModals();
            }
        });

        $('.wpematico-preview-migrate-btn').on('click', function(e) {
            e.preventDefault();
            closeModals();
            if (currentPlugin) startMigration(currentPlugin, currentUiKey);
        });

        // Install & activate the RSS Feed Reader addon (reuses the module endpoint).
        // On success the page reloads so the now-active addon is picked up and the
        // display-only feeds are imported on the next Import.
        $('.wpematico-install-reader-btn').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $box = $btn.closest('.wpematico-reader-requirement');
            var originalHtml = $btn.html();
            var progressText = ($box.data('installed') == 1) ?
                wpematico_migration_toolkit.strings.activating_addon :
                wpematico_migration_toolkit.strings.installing_addon;

            $btn.prop('disabled', true)
                .html('<span class="spinner is-active" style="float:none;margin:0 6px 0 0;"></span>' +
                    progressText);

            $.ajax({
                url: wpematico_migration_toolkit.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpematico_install_plugin',
                    plugin_url: $box.data('install-url'),
                    module: $box.data('module'),
                    activate_after: 1,
                    nonce: wpematico_migration_toolkit.module_nonce
                },
                success: function(response) {
                    if (response && response.success) {
                        window.location.reload();
                    } else {
                        var msg = (response && response.data && response.data.message) ?
                            response.data.message : wpematico_migration_toolkit.strings.install_failed;
                        showReaderInstallError($box, $btn, originalHtml, msg);
                    }
                },
                error: function(xhr, status, error) {
                    showReaderInstallError($box, $btn, originalHtml, error || wpematico_migration_toolkit.strings.install_failed);
                }
            });
        });

        // Replace a single WPRA embed with its WPeMatico shortcode (per-row).
        $(document).on('click', '.wpematico-replace-shortcode-btn', function(e) {
            e.preventDefault();
            replaceShortcode($(this));
        });
    }

    function showReaderInstallError($box, $btn, originalHtml, message) {
        $btn.prop('disabled', false).html(originalHtml);
        $box.find('.wpematico-reader-install-error').remove();
        $box.append('<div class="wpematico-reader-install-error" style="color:#dc3232;margin:6px 0 0;">' +
            message + '</div>');
    }

    function startMigration(plugin, uiKey) {
        currentPlugin = plugin;
        uiKey = uiKey || plugin;

        // Collect the current UI choices HERE so both entry points behave the
        // same: the Import button and the preview modal's "Start migration".
        autoDeactivate = $('.wpematico-auto-deactivate-check[data-plugin="' + uiKey + '"]').is(':checked');
        feedGroupMode = getFeedGroupMode(uiKey);
        optionalImports = getOptionalImports(uiKey);

        if (isPluginGated(plugin)) {
            return;
        }

        if (!confirm(wpematico_migration_toolkit.strings.confirm_migration)) {
            return;
        }

        showMigrationModal();
        updateMigrationStatus(wpematico_migration_toolkit.strings.migrating);

        // Reader-campaign activation + initial fetch (opt-out; absent = default on).
        var $readerCb = $('.wpematico-reader-activate-check[data-plugin="' + plugin + '"]');
        var readerInitialFetch = (!$readerCb.length || $readerCb.is(':checked')) ? 1 : 0;

        // Adopt already published posts (opt-in; the checkbox only renders when
        // the source actually has posts, so an absent one means "nothing to do").
        var $postsCb = $('.wpematico-import-posts-check[data-plugin="' + uiKey + '"]');
        var importPosts = ($postsCb.length && $postsCb.is(':checked')) ? 1 : 0;

        $.ajax({
            url: wpematico_migration_toolkit.ajax_url,
            type: 'POST',
            data: {
                action: 'wpematico_migrate_plugin',
                plugin: plugin,
                auto_deactivate: autoDeactivate ? 1 : 0,
                feed_group_mode: feedGroupMode,
                options: optionalImports,
                reader_initial_fetch: readerInitialFetch,
                import_posts: importPosts,
                nonce: wpematico_migration_toolkit.nonce
            },
            success: function(response) {
                handleMigrationResponse(response);
            },
            error: function(xhr, status, error) {
                handleMigrationError(error);
            }
        });
    }

    function handleMigrationResponse(response) {
        var $results = $('.migration-results');
        var $progress = $('.migration-progress');
        
        $progress.hide();
        $results.show();

        if (response.success) {
            var messageHtml = '<div class="notice notice-success inline" style="margin: 0; padding: 12px;">' +
                '<p><span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> ' +
                '<strong>' + response.data.message + '</strong></p>';
            
            if (response.data.deactivated) {
                messageHtml += '<p style="margin: 5px 0 0 0; color: #646970; font-size: 13px;">' + 
                    '<span class="dashicons dashicons-warning" style="font-size: 14px; vertical-align: middle;"></span> ' +
                    wpematico_migration_toolkit.strings.plugin_deactivated + '</p>';
                
                updateTabAfterMigration(currentPlugin);
            }

            // How many already published posts were linked to their campaigns.
            if (response.data.adopted_note) {
                messageHtml += '<p style="margin: 8px 0 0 0; color: #646970; font-size: 13px;">' +
                    '<span class="dashicons dashicons-admin-links" style="font-size: 14px; vertical-align: middle;"></span> ' +
                    escapeHtml(response.data.adopted_note) + '</p>';
            }

            // Post-import advisories (e.g. addon API config needed).
            if (response.data.notices && response.data.notices.length) {
                messageHtml += '<ul style="margin: 10px 0 0 0; padding: 10px 12px; list-style: none; ' +
                    'background: #fcf4e6; border-left: 4px solid #dba617; border-radius: 2px;">';
                $.each(response.data.notices, function(i, note) {
                    messageHtml += '<li style="margin: 6px 0; color: #4a2f00; font-size: 13px; line-height: 1.5;">' +
                        '<span class="dashicons dashicons-warning" style="color: #dba617; font-size: 16px; vertical-align: text-bottom;"></span> ' +
                        note + '</li>';
                });
                messageHtml += '</ul>';
            }

            messageHtml += '</div>';

            $results.html(messageHtml);
            $('.wpematico-view-campaigns-btn').show();

            // Reload on close so the admin menu reflects the (auto-)deactivated
            // source plugin; the shortcode table re-scans itself on page load.
            migrationSucceeded = true;

            // Newly migrated WPRA campaigns give us slugs to suggest — refresh the table.
            if (currentPlugin === 'wp-rss-aggregator') {
                scanShortcodes('wp-rss-aggregator');
            }
        } else {
            $results.html(
                '<div class="notice notice-error inline" style="margin: 0; padding: 12px;">' +
                '<p><span class="dashicons dashicons-warning" style="color: #dc3232;"></span> ' +
                '<strong>' + wpematico_migration_toolkit.strings.migration_error + '</strong></p>' +
                '<p style="margin: 5px 0 0 0; color: #646970;">' + (response.data.message || 'Unknown error') + '</p>' +
                '</div>'
            );
        }
    }

    function updateTabAfterMigration(plugin) {
        var $tabButton = $('.wpematico-tab-button[data-tab="' + plugin + '"]');
        var $tabContent = $('#tab-content-' + plugin);
        
        $tabButton.css('color', '#46b450');
        $tabButton.find('.dashicons').css('color', '#46b450');
        
        $tabContent.find('.wpematico-import-btn, .wpematico-preview-btn')
            .prop('disabled', true)
            .css('opacity', '0.6');
            
        $tabContent.find('.wpematico-import-options').append(
            '<div class="notice notice-success inline" style="margin-top: 20px;">' +
            '<p><span class="dashicons dashicons-yes"></span> Migration completed!</p></div>'
        );
    }

    function handleMigrationError(error) {
        $('.migration-progress').hide();
        $('.migration-results').show().html(
            '<div class="notice notice-error inline" style="margin: 0; padding: 12px;">' +
            '<p><span class="dashicons dashicons-warning" style="color: #dc3232;"></span> ' +
            '<strong>Error</strong></p>' +
            '<p>' + error + '</p>' +
            '</div>'
        );
    }

    function showPreview(plugin, uiKey) {
        currentPlugin = plugin;
        currentUiKey = uiKey || plugin;
        feedGroupMode = getFeedGroupMode(currentUiKey);

        // Mirror the Import gate on the modal's "Start migration" button.
        $('.wpematico-preview-migrate-btn').prop('disabled', isPluginGated(plugin));

        $('#wpematico-preview-modal').fadeIn();
        $('.preview-loading').show();
        $('.preview-content').hide();
        $('.preview-table tbody').empty();

        $.ajax({
            url: wpematico_migration_toolkit.ajax_url,
            type: 'POST',
            data: {
                action: 'wpematico_get_migration_preview',
                plugin: plugin,
                feed_group_mode: feedGroupMode,
                nonce: wpematico_migration_toolkit.nonce
            },
            success: function(response) {
                handlePreviewResponse(response);
            },
            error: function(xhr, status, error) {
                handlePreviewError(error);
            }
        });
    }

    function handlePreviewResponse(response) {
        $('.preview-loading').hide();
        $('.preview-content').show();

        if (response.success && response.data.items) {
            var items = response.data.items;
            var count = response.data.count;
            
            $('.preview-summary').html('Found <strong>' + count + '</strong> item(s) ready to migrate:');

            var $tbody = $('.preview-table tbody');
            $tbody.empty();

            $.each(items, function(index, item) {
                var feedUrls = item.feed_url || (item.feed_urls ? item.feed_urls.join('<br>') : item.source || '-');
                
                var row = '<tr>' +
                    '<td><strong>' + escapeHtml(item.title) + '</strong></td>' +
                    '<td style="font-size: 12px; color: #646970;">' + feedUrls + '</td>' +
                    '<td>' + escapeHtml(item.post_type || 'post') + '</td>' +
                    '<td>' + escapeHtml(item.status || 'publish') + '</td>' +
                    '</tr>';
                
                $tbody.append(row);
            });
        } else {
            $('.preview-summary').html('No items found to migrate.');
        }
    }

    function handlePreviewError(error) {
        $('.preview-loading').hide();
        $('.preview-content').show();
        $('.preview-summary').html('<span style="color: #dc3232;">Error loading preview: ' + escapeHtml(error) + '</span>');
    }

    function showMigrationModal() {
        $('.migration-progress').show();
        $('.migration-results').hide().empty();
        $('.wpematico-view-campaigns-btn').hide();
        $('#wpematico-migration-modal').fadeIn();
    }

    function closeModals() {
        // After a successful import, closing reloads so the admin menu updates.
        // Remember the active tab in the hash so the reload reopens it.
        if (migrationSucceeded) {
            if (currentPlugin) {
                window.location.hash = 'mig-tab-' + currentPlugin;
            }
            window.location.reload();
            return;
        }
        $('.wpematico-modal').fadeOut();
    }

    function updateMigrationStatus(text) {
        $('.migration-status').text(text);
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    // escapeHtml (textNode.innerHTML) does NOT escape quotes, so it is unsafe for
    // attribute values — escape " and ' too.
    function escapeAttr(text) {
        if (text == null) return '';
        return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    // Scan the site for WPRA embeds and render the replace table for a plugin tab.
    function scanShortcodes(plugin) {
        var $container = $('.wpematico-shortcode-scan[data-plugin="' + plugin + '"]');
        if (!$container.length) return;

        $.ajax({
            url: wpematico_migration_toolkit.ajax_url,
            type: 'POST',
            data: {
                action: 'wpematico_scan_wpra_shortcodes',
                nonce: wpematico_migration_toolkit.nonce
            },
            success: function(response) {
                if (response && response.success && response.data.rows && response.data.rows.length) {
                    renderShortcodeTable($container, response.data.rows);
                    $container.show();
                } else {
                    $container.hide().empty();
                }
            }
        });
    }

    function renderShortcodeTable($container, rows) {
        var s = wpematico_migration_toolkit.strings;
        var html = '<h4 class="wpematico-shortcode-title"><span class="dashicons dashicons-shortcode"></span> ' +
            escapeHtml(s.shortcodes_title) + '</h4>' +
            '<p class="description">' + escapeHtml(s.shortcodes_intro) + '</p>' +
            '<table class="widefat striped wpematico-shortcode-table"><thead><tr>' +
            '<th>' + escapeHtml(s.col_page) + '</th>' +
            '<th>' + escapeHtml(s.col_found) + '</th>' +
            '<th>' + escapeHtml(s.col_replacement) + '</th>' +
            '<th>' + escapeHtml(s.col_action) + '</th>' +
            '</tr></thead><tbody>';

        $.each(rows, function(i, row) {
            var pageCell = row.edit_link ?
                '<a href="' + escapeAttr(row.edit_link) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(row.post_title) + '</a>' :
                escapeHtml(row.post_title);
            pageCell += ' <span class="wpematico-shortcode-ptype">' + escapeHtml(row.post_type) + ' #' + row.post_id + '</span>';

            var replaceCell = row.mappable ?
                '<code>' + escapeHtml(row.replace) + '</code>' :
                '<span class="description">' + escapeHtml(row.note) + '</span>';

            // Attribute values (the embed carries quotes, e.g. id="1") MUST be
            // attr-escaped or they truncate the attribute; read back with .attr().
            var actionCell = row.mappable ?
                '<button type="button" class="button button-small wpematico-replace-shortcode-btn"' +
                ' data-post="' + escapeAttr(row.post_id) + '"' +
                ' data-search="' + escapeAttr(row.found) + '"' +
                ' data-replace="' + escapeAttr(row.replace) + '">' +
                escapeHtml(s.replace_btn) + '</button>' :
                '<span class="dashicons dashicons-minus" style="color:#c3c4c7;"></span>';

            html += '<tr>' +
                '<td>' + pageCell + '</td>' +
                '<td><code class="wpematico-shortcode-found">' + escapeHtml(row.found) + '</code></td>' +
                '<td>' + replaceCell + '</td>' +
                '<td class="wpematico-shortcode-action">' + actionCell + '</td>' +
                '</tr>';
        });

        html += '</tbody></table>';
        $container.html(html);
    }

    function replaceShortcode($btn) {
        var s = wpematico_migration_toolkit.strings;
        var $cell = $btn.closest('.wpematico-shortcode-action');
        $btn.prop('disabled', true).text(s.replacing);

        $.ajax({
            url: wpematico_migration_toolkit.ajax_url,
            type: 'POST',
            data: {
                action: 'wpematico_replace_wpra_shortcode',
                post_id: $btn.attr('data-post'),
                search: $btn.attr('data-search'),
                replace: $btn.attr('data-replace'),
                nonce: wpematico_migration_toolkit.nonce
            },
            success: function(response) {
                if (response && response.success) {
                    $cell.html('<span class="dashicons dashicons-yes-alt" style="color:#46b450;"></span> ' + escapeHtml(s.replaced));
                    $btn.closest('tr').find('.wpematico-shortcode-found').text(response.data.replace);
                } else {
                    var msg = (response && response.data && response.data.message) ? response.data.message : s.replace_failed;
                    $btn.prop('disabled', false).text(s.replace_btn);
                    $cell.append('<span class="wpematico-replace-error" style="color:#dc3232;display:block;margin-top:4px;">' + escapeHtml(msg) + '</span>');
                }
            },
            error: function() {
                $btn.prop('disabled', false).text(s.replace_btn);
                $cell.append('<span class="wpematico-replace-error" style="color:#dc3232;display:block;margin-top:4px;">' + escapeHtml(s.replace_failed) + '</span>');
            }
        });
    }

})(jQuery);