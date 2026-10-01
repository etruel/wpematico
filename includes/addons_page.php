<?php
// don't load directly 
if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

/**
 *  PLUGINS PAGES ADDONS
 *  Experimental.  Uses worpdress plugins.php file filtered
 */
function wpematico_get_addons_update() {
	$wpematico_updates	 = 0; // Cache received responses.
	$plugin_updates		 = get_site_transient('update_plugins');
	if (empty($plugin_updates) || !isset($plugin_updates->response) || !is_array($plugin_updates->response)) {
		return $wpematico_updates;
	}
	foreach ($plugin_updates->response as $r_plugin => $value) {
		if (strpos($r_plugin, 'wpematico_') !== false) {
			$wpematico_updates++;
		}
	}
	return $wpematico_updates;
}

add_action('admin_init', 'redirect_to_wpemaddons', 0);

function redirect_to_wpemaddons() {
	global $pagenow;
	if (wp_doing_ajax() or wp_doing_cron())
		return;
	$getpage = (isset($_REQUEST['page']) && !empty($_REQUEST['page']) ) ? $_REQUEST['page'] : '';
	if ($pagenow != 'admin-ajax.php' || $getpage == 'wpemaddons')
		if ($pagenow == 'plugins.php' && ($getpage == '')) {
			$plugin	 = isset($_REQUEST['plugin']) ? $_REQUEST['plugin'] : '';
			$s		 = isset($_REQUEST['s']) ? urlencode($_REQUEST['s']) : '';

			$actioned = array_multi_key_exists(array('error', 'deleted', 'activate', 'activate-selected', 'activate-multi', 'deactivate', 'deactivate-selected', 'deactivate-multi', '_error_nonce'), $_REQUEST, false);
			if (( isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'page=wpemaddons') ) && $actioned) {
				// Keep the query core just built: it carries the outcome of the action
				// (`deleted=N`, `activate=true`, `error=true`), and starting from an
				// empty string threw all of it away, so the screen came back with
				// nothing to report.
				$location = add_query_arg('page', 'wpemaddons', esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])));
				if (!headers_sent()) {
					wp_redirect($location);
					exit;
				}
			}
		}
}

function wpe_include_plugins() {
	global $user_ID;
	$user_ID = get_current_user_id();
	if (!defined('WPEM_ADMIN_DIR')) {
		define('WPEM_ADMIN_DIR', ABSPATH . basename(admin_url()));
	}
	$status	 = 'all';
	$page	 = (!isset($page) or is_null($page)) ? 1 : $page;
	require WPEM_ADMIN_DIR . '/plugins.php';
}

add_action('load-plugins_page_wpemaddons', 'wpematico_addons_handle_bulk_actions');

/**
 * The bulk action of the current request, read as WP_List_Table does: only
 * `action`, "-1" meaning none. Core mirrors the bottom dropdown into the top one
 * in JS since WP 5.7, so `action2` needs no handling of its own.
 *
 * @return string Action name, empty when none was chosen.
 */
function wpematico_addons_current_action() {
	if (isset($_REQUEST['action']) && '-1' != $_REQUEST['action']) {
		return sanitize_text_field(wp_unslash($_REQUEST['action']));
	}
	return '';
}

/**
 * Hands the request over to wp-admin/plugins.php, which owns every bulk action
 * offered here: capability and nonce checks, the delete confirmation screen, the
 * auto-update option and the redirect. `page=wpemaddons` exits before core would
 * otherwise see the action.
 *
 * ★ Runs on `load-{$page_hook}`, never on `admin_menu`: `delete-selected` renders
 * a confirmation through admin-header.php, and admin_menu fires before
 * set_current_screen(), so it would die on a null screen. Do not filter `checked`
 * here either — core needs the selection exactly as it arrived.
 */
function wpematico_addons_handle_bulk_actions() {
	// Refresh the update data the way core does on its own plugins list, here where
	// nothing has been printed yet. It self-throttles to twelve hours, so this is a
	// remote call twice a day and a no-op the rest of the time; it used to run in
	// the middle of rendering the table.
	wp_update_plugins();

	// "-1" means no action was chosen, and handing that over would render core's
	// own plugins list on top of this screen.
	if ('' === wpematico_addons_current_action()) {
		return;
	}

	// The second step of a delete needs nothing special: core's confirmation form
	// posts `verify-delete` back together with `action=delete-selected`.
	wpe_include_plugins();
}

function WPeAddon_admin_scripts() {
	wp_enqueue_script('jquery');
	wp_enqueue_script('plupload-all');
	wp_enqueue_style('plugin-install');
	wp_enqueue_script('plugin-install');
	wp_enqueue_script('updates'); // Powers the native "Enable/Disable auto-updates" toggle (wp.updates AJAX).
	add_thickbox();
	wp_enqueue_script('wpematico-update', WPEMATICO_PLUGIN_URL . 'assets/js/wpematico_updates.js', array('jquery', 'inline-edit-post'), WPEMATICO_VERSION, true);
}

add_action('admin_head', 'WPeAddon_admin_head');

function WPeAddon_admin_head() {
	global $pagenow, $page_hook;
	if ($pagenow == 'plugins.php' && $page_hook == 'plugins_page_wpemaddons') {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				var $all = $('.subsubsub .all a').attr('href');
				var $act = $('.subsubsub .active a').attr('href');
				var $ina = $('.subsubsub .inactive a').attr('href');
				var $rec = $('.subsubsub .recently_activated a').attr('href');
				var $upg = $('.subsubsub .upgrade a').attr('href');
				$('.subsubsub .all a').attr('href', $all + '&page=wpemaddons');
				$('.subsubsub .active a').attr('href', $act + '&page=wpemaddons');
				$('.subsubsub .inactive a').attr('href', $ina + '&page=wpemaddons');
				$('.subsubsub .recently_activated a').attr('href', $rec + '&page=wpemaddons');
				$('.subsubsub .upgrade a').attr('href', $upg + '&page=wpemaddons');
			});
		</script>
		<style type="text/css">
			table tr:has(.membership) {
				background-color: LemonChiffon;
			}
			.wpem-addons-search .wpem-addons-filter-live {
				margin-right: 8px;
				color: #646970;
			}
			.wpem-addon-icon-placeholder {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				width: 100px;
				height: 50px;
				background-color: #e5e5e5;
				border: 1px solid #dcdcde;
				box-sizing: border-box;
			}
			.wpem-addon-icon-placeholder .dashicons {
				font-size: 28px;
				width: 28px;
				height: 28px;
				color: #8c8f94;
			}
			/* Unified button sizing for the "Adquire" column so all states align.
			   Flex centering keeps a button with an icon on the same baseline as
			   the text-only ones, which vertical-align never quite managed. */
			.column-buybutton .button {
				min-width: 140px;
				display: inline-flex;
				align-items: center;
				justify-content: center;
				gap: 6px;
				text-align: center;
			}
			/* Installed (inactive / auto-update unavailable): lime green. */
			.wpem-btn-installed {
				background-color: greenyellow !important;
				border-color: #a9d900 !important;
				color: #2c3338 !important;
				text-shadow: none !important;
				box-shadow: none !important;
			}
			.wpem-btn-installed:hover,
			.wpem-btn-installed:focus {
				background-color: #b6f000 !important;
				border-color: #98c400 !important;
				color: #2c3338 !important;
			}
			/* Free on wordpress.org: green, like the FREE badge of the modules. */
			.wpem-btn-install {
				background-color: #2e8960 !important;
				border-color: #2e8960 !important;
				color: #fff !important;
				text-shadow: none !important;
				box-shadow: none !important;
			}
			.wpem-btn-install:hover,
			.wpem-btn-install:focus {
				background-color: #237c4e !important;
				border-color: #237c4e !important;
				color: #fff !important;
			}
			/* The class on its own ties with core's `.wp-core-ui .button .dashicons`,
			   which then wins the line-height and pushes the icon off centre. */
			.button.wpem-btn-install .dashicons {
				font-size: 16px;
				width: 16px;
				height: 16px;
				line-height: 1;
			}
			/* Installed but stopped: starting it is the action that matters, and it
			   must not look like the blue "Purchase" of an addon you don't own yet. */
			.wpem-btn-activate {
				background-color: #00a32a !important;
				border-color: #008a20 !important;
				color: #fff !important;
				text-shadow: none !important;
				box-shadow: none !important;
			}
			.wpem-btn-activate:hover,
			.wpem-btn-activate:focus {
				background-color: #008a20 !important;
				border-color: #007017 !important;
				color: #fff !important;
			}
			.button.wpem-btn-activate .dashicons {
				font-size: 16px;
				width: 16px;
				height: 16px;
				line-height: 1;
			}
			/* License required CTA: amber (attention). */
			.wpem-btn-license {
				background-color: #d98500 !important;
				border-color: #b06d00 !important;
				color: #fff !important;
				text-shadow: none !important;
				box-shadow: none !important;
			}
			.wpem-btn-license:hover,
			.wpem-btn-license:focus {
				background-color: #b06d00 !important;
				border-color: #935b00 !important;
				color: #fff !important;
			}
			/* Auto-update toggle (valid license): teal. */
			.wpem-btn-autoupdate {
				background-color: #2c8c99 !important;
				border-color: #227079 !important;
				color: #fff !important;
				text-shadow: none !important;
				box-shadow: none !important;
			}
			.wpem-btn-autoupdate:hover,
			.wpem-btn-autoupdate:focus {
				background-color: #227079 !important;
				border-color: #1b5960 !important;
				color: #fff !important;
			}
			/* Auto-update already enabled: teal outline. */
			.wpem-btn-autoupdate.is-enabled {
				background-color: #fff !important;
				border-color: #2c8c99 !important;
				color: #227079 !important;
				text-shadow: none !important;
			}
			.wpem-btn-autoupdate.is-enabled:hover,
			.wpem-btn-autoupdate.is-enabled:focus {
				background-color: #f0fbfc !important;
				border-color: #227079 !important;
				color: #1b5960 !important;
			}
			/* Why an installed addon is not updating, under its own control. */
			.wpem-update-reason {
				margin: 6px 0 0;
				max-width: 320px;
				font-size: 12px;
				line-height: 1.5;
				color: #50575e;
			}
			@media screen and (max-width: 782px) {
				.plugins_page_wpemaddons .column-name{
					display: none;
				}
			}
		</style>
		<?php
	}
}

function add_admin_plugins_page() {
	global $s, $plugins, $status, $wp_list_table;

	if (!defined('WPEM_ADMIN_DIR')) {
		define('WPEM_ADMIN_DIR', ABSPATH . basename(admin_url()));
	}

	if (!class_exists('WP_List_Table')) {
		require_once WPEM_ADMIN_DIR . '/includes/class-wp-list-table.php';
	}

	if (!class_exists('WP_Plugins_List_Table')) {
		require WPEM_ADMIN_DIR . '/includes/class-wp-plugins-list-table.php';
	}

	// ★ $s is the one piece of state WP_Plugins_List_Table does NOT set for itself
	// (its constructor does resolve $status and $page from the request). Only
	// wp-admin/plugins.php sets it, and this screen never reaches that file — so
	// forcing it empty here is why the search box returned the full list, always.
	// urlencode() matches core: _search_callback() urldecode()s it back.
	$s		= isset($_REQUEST['s']) ? urlencode(wp_unslash($_REQUEST['s'])) : '';
	$status	= 'all';
	$page	= 1;

	// Same cleanup core does, so the result of the last action does not ride along
	// in the paging and Screen Options URLs and get reported again on every click.
	$_SERVER['REQUEST_URI'] = remove_query_arg(
			array('error', 'deleted', 'activate', 'activate-multi', 'deactivate', 'deactivate-multi',
				'enabled-auto-update', 'disabled-auto-update', 'enabled-auto-update-multi',
				'disabled-auto-update-multi', '_error_nonce'),
			$_SERVER['REQUEST_URI']
	);
	// prepare_items() builds $plugins['all'] itself, through the all_plugins filter.
	// Pre-filling it here scanned every plugin header for a value that was then
	// overwritten, and wp_clean_plugins_cache() threw that scan away on top, so the
	// headers of every plugin on the site were read from disk twice per page load.
	// wp_update_plugins() moved to the load- handler, before any output.
	// "Inactive" means installed and switched off. Core files everything that is
	// not active into that bucket, catalogue rows included, so the view listed
	// add-ons that are not even on the site. They belong to "All" only.
	add_filter('plugins_list', 'wpematico_addons_installed_views_only');
	$plugins_list_table = new WP_Plugins_List_Table();
	$plugins_list_table->prepare_items();
	remove_filter('plugins_list', 'wpematico_addons_installed_views_only');

	echo '<div class="wrap">';
	echo '<h1 class="wp-heading-inline">' . esc_html__('WPeMatico Add-Ons Plugins', 'wpematico') . '</h1>';
	echo '<hr class="wp-header-end">';
	wpematico_addons_result_notice();
	wpematico_addons_auto_update_notice();
	// Output the list table HTML
	$plugins_list_table->views();
	?>
	<?php
	/*
	 * Core's own search box is not usable here, and neither is the class it comes
	 * in. updates.js binds a submit handler to `.search-plugins` that cancels the
	 * submit and hands the term to the `search-plugins` AJAX action, which only
	 * answers for the `plugins` screen; and its live search is bound to
	 * `.plugins-php .wp-filter-search`, a body class this screen (hook suffix
	 * `plugins_page_wpemaddons`) does not have. Between the two, the submit was
	 * swallowed and nothing ever searched. Core also prints the button as
	 * `hide-if-js`, so there was not even a button left to click.
	 * Own markup, own filtering: instant narrowing as you type, Enter for the
	 * server-side search that also works with JS off.
	 */
	?>
	<form class="search-form wpem-addons-search" method="get">
		<?php // Without this the search leaves the screen for the plain plugins list. ?>
		<input type="hidden" name="page" value="wpemaddons" />
		<p class="search-box">
			<?php // First in the row: the box floats right, so a counter after it would push the field sideways as you type. ?>
			<span class="wpem-addons-filter-live" aria-live="polite" hidden></span>
			<label class="screen-reader-text" for="wpem-addons-search-input"><?php esc_html_e('Search Add-Ons', 'wpematico'); ?></label>
			<input type="search" id="wpem-addons-search-input" name="s" value="<?php _admin_search_query(); ?>" autocomplete="off" placeholder="<?php esc_attr_e('Add-on name, feature…', 'wpematico'); ?>" />
			<input type="submit" id="search-submit" class="button" value="<?php esc_attr_e('Search Add-Ons', 'wpematico'); ?>" />
		</p>
	</form>
	<form method="post" id="bulk-action-form">
		<input type="hidden" name="plugin_status" value="<?php echo esc_attr($status); ?>" />
		<input type="hidden" name="paged" value="<?php echo esc_attr($page); ?>" />
		<?php // Marks the bulk action as coming from here, so core sends the user back. ?>
		<input type="hidden" name="wpem_addons" value="1" />
		<?php
		$plugins_list_table->display();
		?>
	</form>
	<?php
	wpematico_addons_search_script();
	echo '</div>';
}

/**
 * Keeps the catalogue out of every view but "All". A row the store sells but the
 * site does not have installed carries 'Remote'; core has no idea it is not a
 * real plugin and counts it as inactive.
 *
 * The 'search' bucket is left alone on purpose: searching looks through the whole
 * catalogue, which is the point of this screen.
 */
function wpematico_addons_installed_views_only($plugins) {
	foreach ($plugins as $bucket => $rows) {
		if ('all' === $bucket || 'search' === $bucket || !is_array($rows)) {
			continue;
		}
		foreach ($rows as $plugin_file => $plugin_data) {
			if (!empty($plugin_data['Remote'])) {
				unset($plugins[$bucket][$plugin_file]);
			}
		}
	}
	return $plugins;
}

/**
 * Narrows the rows already on screen while the user types, the same way the Feed
 * List does. Every add-on is on one page here (999 per page), so this is the
 * whole catalogue, and submitting still runs the server-side search for the
 * no-JS case.
 */
function wpematico_addons_search_script() {
	$columns = 1;
	if (function_exists('get_column_headers')) {
		$columns = max(1, count((array) get_column_headers(get_current_screen())));
	}
	?>
	<script type="text/javascript">
		jQuery(function ($) {
			var $box   = $('#wpem-addons-search-input'),
				$table = $('#bulk-action-form table.plugins'),
				$live  = $('.wpem-addons-filter-live'),
				strings = {
					/* translators: 1: matching add-ons, 2: add-ons listed. */
					live: <?php echo wp_json_encode(__('Showing %1$s of %2$s.', 'wpematico')); ?>,
					none: <?php echo wp_json_encode(__('No add-ons match your search.', 'wpematico')); ?>
				};

			if (!$box.length || !$table.length) {
				return;
			}

			var $rows = $table.find('tbody tr').not('.plugin-update-tr, .no-items'),
				total = $rows.length,
				$empty = $('<tr class="no-items wpem-addons-no-matches" hidden><td class="colspanchange" colspan="<?php echo (int) $columns; ?>"></td></tr>').appendTo($table.find('tbody').first());

			$empty.find('td').text(strings.none);

			function filter() {
				var needle = $.trim($box.val()).toLowerCase(),
					shown  = 0;

				$rows.each(function () {
					var $row = $(this),
						hay  = $row.data('wpemHaystack');

					if (undefined === hay) {
						hay = $row.text().replace(/\s+/g, ' ').toLowerCase();
						$row.data('wpemHaystack', hay);
					}

					var hit = (needle === '' || hay.indexOf(needle) >= 0);
					$row.toggle(hit);
					// The update notice is a row of its own, tied to the one above it.
					$table.find('tbody tr.plugin-update-tr[data-plugin="' + $row.attr('data-plugin') + '"]').toggle(hit);
					if (hit) {
						shown++;
					}
				});

				$empty.attr('hidden', shown !== 0 || needle === '').toggle(shown === 0 && needle !== '');

				if (needle === '' || shown === total) {
					$live.attr('hidden', true).text('');
				} else {
					$live.removeAttr('hidden').text(
						strings.live.replace('%1$s', shown).replace('%2$s', total)
					);
				}
			}

			$box.on('keyup search input', function (e) {
				if (e.keyCode === 27) {   // Esc clears, same as the Feed List.
					$box.val('');
				}
				filter();
			});

			filter();
		});
	</script>
	<?php
}

add_filter("manage_plugins_page_wpemaddons_columns", 'wpematico_addons_get_columns');

function wpematico_addons_get_columns() {
	global $status;

	return array(
		'cb'			 => !in_array($status, array('mustuse', 'dropins')) ? '<input type="checkbox" />' : '',
		'icon'			 => __('Icon', 'wpematico'),
		'name'			 => __('Add On', 'wpematico'),
		'description'	 => __('Description', 'wpematico'),
		'buybutton'		 => __('Adquire', 'wpematico'),
	);
}

add_action('manage_plugins_custom_column', 'wpematico_addons_custom_columns', 10, 3);

function wpematico_addons_custom_columns($column_name, $plugin_file, $plugin_data) {
	// Return if don't have the wpematico word in its name or uri. An addon this
	// plugin declares itself (wpematico_free_addons) is ours whatever it is
	// called, so it does not have to pass the name test.
	if (empty($plugin_data['wporg_slug'])
			&& empty($plugin_data['Remote'])
			&& strpos((string) $plugin_data['Name'], 'WPeMatico ') === false
			&& strpos((string) $plugin_data['PluginURI'], 'wpematico') === false) {
		return true;
	}

	// This callback runs once per custom column of every row, so everything that
	// does not depend on the row is resolved once for the whole request instead.
	static $addons = null;
	if (null === $addons) {
		$options = wpematico_addons_cfg();
		$addons	 = array();
		if (empty($options['disable_extensions_feed_page'])) {
			// Get the addon from the transient saved before. The free addons live in
			// their own feed, so an installed one had no artwork here: its row comes
			// from the plugin headers, and the image is only ever found in a feed.
			$addons = array_merge(
					array_values(wpematico_get_addons_maybe_fetch()),
					array_values(wpematico_get_free_addons_maybe_fetch())
			);
		}
	}

	$addon			 = array();
	$plugin_data_uri = strstr((string) $plugin_data['PluginURI'], '://');
	foreach ($addons as $value) {
		$addon_data_uri = strstr((string) $value['PluginURI'], '://');
		// strstr() returns false when there is no scheme, and false == false, so two
		// rows without a PluginURI used to match each other.
		if (($plugin_data['Name'] == $value['Name']) or ('' !== $plugin_data_uri && false !== $plugin_data_uri && $plugin_data_uri === $addon_data_uri)) {
			$addon = $value;
			break;
		}
	}
	switch ($column_name) {
		case 'icon':
			// A row that already carries its artwork (anything read from a feed)
			// uses it. Only an installed plugin has to be looked up, since its row
			// is built from the plugin headers and those have no image.
			if (!empty($plugin_data['icon'])) {
				echo $plugin_data['icon'];
			} elseif (isset($addon['icon'])) {
				echo $addon['icon'];
			}
			break;

		case 'buybutton':
			// Feed-only (purchasable) addons carry a 'Remote' flag; anything else is
			// a plugin physically present on this site, i.e. installed.
			if (!isset($plugin_data['Remote'])) {
				// Installed: show WordPress' native auto-updates toggle so users
				// managing many sites can enable automatic updates from here.
				echo wpematico_addon_auto_update_html($plugin_file, $plugin_data);
				echo wpematico_addon_update_reason_html($plugin_file, $plugin_data);
				break;
			}

			// Free on wordpress.org: offer to install it, right here, instead of
			// sending the user to a store page with nothing to buy.
			if (!empty($plugin_data['wporg_slug']) && current_user_can('install_plugins')) {
				$slug = $plugin_data['wporg_slug'];
				$url  = wp_nonce_url(
						self_admin_url('update.php?action=install-plugin&plugin=' . urlencode($slug)),
						'install-plugin_' . $slug
				);
				printf(
						'<a class="button button-primary wpem-btn-install" title="%s" href="%s"><span class="dashicons dashicons-download" aria-hidden="true"></span> %s</a>',
						/* translators: %s: addon name. */
						esc_attr(sprintf(__('Install %s from WordPress.org, free', 'wpematico'), $plugin_data['Name'])),
						esc_url($url),
						esc_html__('Install now', 'wpematico')
				);
				break;
			}

			// Not installed: link to purchase / membership at the store.
			$class = 'button';
			if (wpematico_is_membership($plugin_data)) {
				$caption = __('See Plugins', 'wpematico');
				$title	 = __('See plugins in membership at etruel\'s store', 'wpematico');
				$class	 .= ' membership button-primary';
			} else { // Is a normal extension available to purchase on website
				$caption = __('Purchase', 'wpematico'); //**** Bundled products always show 'Purchase'
				$title	 = __('Go to purchase on the etruel\'s store', 'wpematico');
				$class	 .= ' button-primary';
			}
			$url = 'https' . strstr($plugin_data['PluginURI'], '://');
			printf(
				'<a target="_blank" class="%s" title="%s" href="%s">%s</a>',
				esc_attr($class),
				esc_attr($title),
				esc_url($url),
				esc_html($caption)
			);
			break;

		default:
			break;
	}
	return true;
}

/**
 * Resolve the license slot name registered for a given installed addon.
 *
 * Addons register their updater/license data under an arbitrary key via the
 * `wpematico_plugins_updater_args` filter (e.g. 'pro_licenser'), storing the
 * absolute plugin file in $args['plugin_file']. We match that against the
 * plugin basename used by the Add-ons list table.
 *
 * @param string $plugin_file Plugin basename (dir/file.php).
 * @return string|false License slot name, or false if the addon has no license.
 */
function wpematico_addon_license_name_by_file($plugin_file) {
	// Built once: the filter is where all eleven addons declare their updater, and
	// this used to run it — and walk the result — separately for every row.
	static $by_file = null;

	if (null === $by_file) {
		$by_file	  = array();
		$plugins_args = apply_filters('wpematico_plugins_updater_args', array());
		foreach ((array) $plugins_args as $plugin_name => $args) {
			if (empty($args['plugin_file'])) {
				continue;
			}
			$by_file[plugin_basename($args['plugin_file'])] = $plugin_name;
		}
	}

	return isset($by_file[$plugin_file]) ? $by_file[$plugin_file] : false;
}

/**
 * Whether the given license slot has a key filled in AND a 'valid' status.
 *
 * @param string $license_name License slot name (see wpematico_addon_license_name_by_file()).
 * @return bool
 */
function wpematico_addon_has_valid_license($license_name) {
	if (empty($license_name) || !class_exists('wpematico_licenses_handlers')) {
		return false;
	}
	$key	 = wpematico_licenses_handlers::get_key($license_name);
	$status	 = wpematico_licenses_handlers::get_license_status($license_name);
	return (!empty($key) && $status === 'valid');
}

/**
 * Why an installed addon is not updating, printed under its own control.
 *
 * The screen asks WPeMatico_Update which situation applies and prints the sentence that
 * belongs to it, so an addon that is not updating never looks like one that is.
 *
 * @param string $plugin_file Plugin basename (dir/file.php).
 * @param array  $plugin_data Plugin data row.
 * @return string HTML markup, empty when there is nothing to say.
 */
function wpematico_addon_update_reason_html($plugin_file, $plugin_data = array()) {
	if (!class_exists('WPeMatico_Update') || !current_user_can('update_plugins')) {
		return '';
	}

	$found = WPeMatico_Update::reason_for_plugin($plugin_file);
	// An add-on with an update pending already carries its reason in the update row
	// under it, printed by WordPress itself. What has no row of its own is the
	// unattended update being held back, which is what this line is for.
	if (empty($found['reason']) || 'waiting' !== $found['source']) {
		return '';
	}

	$context				= $found['context'];
	$context['plugin_name'] = isset($plugin_data['Name']) ? $plugin_data['Name'] : '';
	$message				= WPeMatico_Update_Reasons::inline($found['reason'], $context);

	return ($message) ? '<p class="wpem-update-reason">' . $message . '</p>' : '';
}

/**
 * Says, above the list, when the add-ons on this site update automatically and WPeMatico
 * does not — the one state in which an add-on can wait for ever.
 *
 * @return void
 */
function wpematico_addons_auto_update_notice() {
	if (!class_exists('WPeMatico_Update_Reasons') || !current_user_can('update_plugins')) {
		return;
	}

	$auto_updates = (array) get_site_option('auto_update_plugins', array());
	$core		  = plugin_basename(WPEMATICO_ROOTFILE);
	if (empty($auto_updates) || in_array($core, $auto_updates, true)) {
		return;
	}

	$plugins = get_plugins();
	$addons	 = 0;
	foreach ($auto_updates as $file) {
		if (isset($plugins[$file]) && WPeMatico_Update::get_wpematico_ad_data($plugins[$file])) {
			$addons++;
		}
	}

	if (!$addons) {
		return;
	}

	$message = WPeMatico_Update_Reasons::message(WPeMatico_Update_Reasons::CORE_AUTOUPDATE_OFF, array('count' => $addons));
	echo '<div class="notice notice-warning"><p>' . esc_html($message['text'])
			. ' <a href="' . esc_url($message['url']) . '">' . esc_html($message['label']) . '</a></p></div>';
}

/**
 * The "Enable/Disable auto-updates" control of an installed addon.
 *
 * Only active addons can be auto-updated; the rest fall back to a plain
 * "Installed" label. Core's updates.js toggle is bound to `pagenow === 'plugins'`
 * and to a `.column-auto-updates` ancestor, so this is a navigating link that lets
 * plugins.php persist the option and redirect back here with a notice.
 *
 * @param string $plugin_file Plugin basename (dir/file.php).
 * @param array  $plugin_data Plugin data row (unused, kept for signature parity).
 * @return string HTML markup.
 */
function wpematico_addon_auto_update_html($plugin_file, $plugin_data = array()) {
	// Inactive plugins can't be auto-updated: keep the classic green "Installed"
	// button that links to the addon's page on etruel's store.
	if (!is_plugin_active($plugin_file)) {
		// Installed and stopped: the useful action here is to start it, not a
		// green "Installed" label linking to a store page.
		if (current_user_can('activate_plugin', $plugin_file)) {
			$url = wp_nonce_url(
					self_admin_url('plugins.php?action=activate&plugin=' . urlencode($plugin_file) . '&plugin_status=all&paged=1'),
					'activate-plugin_' . $plugin_file
			);
			return sprintf(
					'<a class="button button-primary wpem-btn-activate" title="%s" href="%s"><span class="dashicons dashicons-controls-play" aria-hidden="true"></span> %s</a>',
					/* translators: %s: addon name. */
					esc_attr(sprintf(__('Activate %s on this site', 'wpematico'), isset($plugin_data['Name']) ? $plugin_data['Name'] : '')),
					esc_url($url),
					esc_html__('Activate', 'wpematico')
			);
		}
	}

	$autoupdates_off = !function_exists('wp_is_auto_update_enabled_for_type') || !wp_is_auto_update_enabled_for_type('plugin');
	if (!is_plugin_active($plugin_file) || $autoupdates_off || !current_user_can('update_plugins')) {
		$store_url = !empty($plugin_data['PluginURI']) ? 'https' . strstr($plugin_data['PluginURI'], '://') : '#';
		// A site where nothing updates on its own says so, instead of offering a store
		// page as the answer to a control that is missing.
		$title = ($autoupdates_off && is_plugin_active($plugin_file))
				? WPeMatico_Update_Reasons::plain(WPeMatico_Update_Reasons::AUTOUPDATES_OFF, array('plugin_name' => isset($plugin_data['Name']) ? $plugin_data['Name'] : ''))
				: __('See details and prices on etruel\'s store', 'wpematico');

		return sprintf(
				'<a class="button wpem-btn-installed" target="_blank" title="%s" href="%s">%s</a>',
				esc_attr($title),
				esc_url($store_url),
				esc_html__('Installed', 'wpematico')
		);
	}

	// Auto-updates require an active license: if this addon registers a license
	// slot that isn't filled + valid, send the user to the core licenses page
	// (Settings › PRO Licenses) instead of exposing the auto-update toggle.
	// Addons with no license slot (edge case) keep the toggle available.
	$license_name = wpematico_addon_license_name_by_file($plugin_file);
	if ($license_name && !wpematico_addon_has_valid_license($license_name)) {
		// Which license state it is decides both the button and where it goes: an
		// expired license is renewed at the store, a missing key is typed here.
		$reason	 = WPeMatico_Update_Reasons::license_reason($license_name);
		$message = WPeMatico_Update_Reasons::message($reason, array(
			'plugin_name' => isset($plugin_data['Name']) ? $plugin_data['Name'] : '',
			'plugin_key'  => $license_name,
		));
		$captions = array(
			WPeMatico_Update_Reasons::LICENSE_MISSING		=> __('Add license', 'wpematico'),
			WPeMatico_Update_Reasons::LICENSE_EXPIRED		=> __('Renew license', 'wpematico'),
			WPeMatico_Update_Reasons::LICENSE_SITE_INACTIVE => __('Activate license', 'wpematico'),
			WPeMatico_Update_Reasons::LICENSE_INVALID		=> __('Check license', 'wpematico'),
		);

		return sprintf(
				'<a class="button wpem-btn-license" title="%s" href="%s">%s</a>',
				esc_attr($message['text'] ? $message['text'] : __('Activate this addon\'s license to enable automatic updates', 'wpematico')),
				esc_url($message['url'] ? $message['url'] : self_admin_url('admin.php?page=wpematico_settings&tab=pro_licenses')),
				esc_html(isset($captions[$reason]) ? $captions[$reason] : __('Activate license', 'wpematico'))
		);
	}

	$auto_updates = (array) get_site_option('auto_update_plugins', array());
	$enabled	 = in_array($plugin_file, $auto_updates, true);
	$action		 = $enabled ? 'disable' : 'enable';
	// Reuse WordPress core translations (default text domain) for these strings.
	$text		 = $enabled ? __('Disable auto-updates') : __('Enable auto-updates');
	$btn_class	 = $enabled ? 'wpem-btn-autoupdate is-enabled' : 'wpem-btn-autoupdate';

	$url = add_query_arg(
			array(
				'action'		 => $action . '-auto-update',
				'plugin'		 => $plugin_file,
				'paged'			 => 1,
				'plugin_status'	 => 'all',
				'wpem_addons'	 => 1, // Marker: request originated from the Add-ons screen.
			),
			self_admin_url('plugins.php')
	);

	return sprintf(
			'<a href="%s" class="button %s wpem-toggle-auto-update">%s</a>',
			esc_url(wp_nonce_url($url, 'updates')),
			esc_attr($btn_class),
			esc_html($text)
	);
}

/**
 * True during any plugin auto-update save request, whether the classic
 * plugins.php GET links (single or bulk) or the admin-ajax `toggle-auto-updates`
 * action used by the standard plugins list. Both intersect the saved option
 * against apply_filters('all_plugins', ...), so wpematico_showhide_addons()
 * must not hide our addons during these requests.
 *
 * @return bool
 */
function wpematico_is_autoupdate_save_action() {
	$action = wpematico_addons_current_action();
	if (empty($action)) {
		return false;
	}
	return in_array(
			$action,
			array(
				'toggle-auto-updates', // admin-ajax path (standard plugins list).
				'enable-auto-update',
				'disable-auto-update',
				'enable-auto-update-selected',
				'disable-auto-update-selected',
			),
			true
	);
}

/**
 * True when the current request is an auto-update toggle coming from the
 * WPeMatico Add-ons screen (marked with wpem_addons=1, by the row links and by
 * the bulk action form), used to send the user back here with a notice.
 *
 * @return bool
 */
function wpematico_is_addon_autoupdate_request() {
	return !empty($_REQUEST['wpem_addons'])
			&& in_array(
					wpematico_addons_current_action(),
					array(
						'enable-auto-update',
						'disable-auto-update',
						'enable-auto-update-selected',
						'disable-auto-update-selected',
					),
					true
			);
}

/**
 * Send the user back to the WPeMatico Add-ons screen (instead of the generic
 * plugins list) after toggling an addon's auto-update, preserving core's
 * enabled/disabled query flag so the notice can be shown.
 */
add_filter('wp_redirect', function($location) {
	if (wpematico_is_addon_autoupdate_request() && false !== strpos($location, 'plugins.php')) {
		$location = add_query_arg('page', 'wpemaddons', $location);
	}
	return $location;
}, 10, 1);

/**
 * The outcome of the last action, reported as core's plugins.php reports it —
 * a template this screen never reaches, so it used to come back silently.
 *
 * Read from the query args core redirects with, which is why
 * redirect_to_wpemaddons() must keep the query when it sends the user back.
 *
 * Strings with no text domain are core's own, reused on purpose so they come out
 * translated everywhere without shipping a second copy.
 */
function wpematico_addons_result_notice() {
	// Leftover of the autoloaded option this used to be carried in. Looked up in the
	// alloptions cache, which is already in memory: get_option() would cost a query
	// on every visit once the option is gone, to find out that it is gone.
	$autoloaded = wp_load_alloptions();
	if (isset($autoloaded['action_notice_wpematico_addons'])) {
		delete_option('action_notice_wpematico_addons');
	}

	if (isset($_GET['error'])) {
		$message = isset($_GET['main'])
			? __('You cannot delete a plugin while it is active on the main site.')
			: __('The plugin could not be activated because it triggered a fatal error.', 'wpematico');
		echo '<div id="message" class="error notice is-dismissible"><p>' . esc_html($message) . '</p></div>';
		return;
	}

	if (isset($_GET['deleted'])) {
		// delete_plugins() leaves its result in an option, and a failure comes back
		// as a WP_Error there and nowhere else.
		$option = 'plugins_delete_result_' . get_current_user_id();
		$result = get_option($option);
		delete_option($option);

		if (is_wp_error($result)) {
			echo '<div id="message" class="error notice is-dismissible"><p><strong>'
				. esc_html__('Deletion failed:', 'wpematico') . '</strong> '
				. esc_html($result->get_error_message()) . '</p></div>';
			return;
		}

		$message = ('1' === (string) $_GET['deleted'])
			? __('The selected add-on has been deleted.', 'wpematico')
			: __('The selected add-ons have been deleted.', 'wpematico');
		echo '<div id="message" class="updated notice is-dismissible"><p>' . esc_html($message) . '</p></div>';
		return;
	}

	$messages = array(
		'activate'					=> __('Plugin activated.'),
		'activate-multi'			=> __('Selected plugins activated.'),
		'deactivate'				=> __('Plugin deactivated.'),
		'deactivate-multi'			=> __('Selected plugins deactivated.'),
		'enabled-auto-update'		=> __('Plugin will be auto-updated.'),
		'disabled-auto-update'		=> __('Plugin will no longer be auto-updated.'),
		'enabled-auto-update-multi'	=> __('Selected plugins will be auto-updated.'),
		'disabled-auto-update-multi' => __('Selected plugins will no longer be auto-updated.'),
	);

	foreach ($messages as $arg => $message) {
		if (isset($_GET[$arg])) {
			echo '<div id="message" class="updated notice is-dismissible"><p>' . esc_html($message) . '</p></div>';
			return;
		}
	}
}

add_filter('all_plugins', 'wpematico_showhide_addons');

function wpematico_showhide_addons($plugins, $filter = false) {
	global $current_screen;

	// While WordPress saves the auto_update_plugins option it intersects it
	// against apply_filters('all_plugins', ...) to drop entries that "no longer
	// exist" (see wp-admin/plugins.php and wp_ajax_toggle_auto_updates()).
	// If we hid our addons here during that save, toggling ANY plugin would
	// silently strip our addons from the option. Leave the list untouched so
	// their auto-update setting persists.
	if (wpematico_is_autoupdate_save_action()) {
		return $plugins;
	}

	if (function_exists('wp_plugin_update_rows')) {
		wp_plugin_update_rows();
	}

	$show_on_plugin_page = get_option('wpem_show_locally_addons', false);
	// The screen is not always there (an early all_plugins filter, WP-CLI), and
	// reading ->id on null is a PHP 8 warning that also drags in core's php-error
	// body class, which reserves an unexplained gap above the admin menu.
	$screen_id			 = ($current_screen instanceof WP_Screen) ? $current_screen->id : '';
	if ($screen_id == 'plugins_page_wpemaddons') {
		$plugins = apply_filters('etruel_wpematico_addons_array', read_wpem_addons($plugins), 10, 1);
		foreach ($plugins as $key => $value) {
			if (strpos($key, 'wpematico_') === FALSE) {
				unset($plugins[$key]);
			} else {
				if (isset($plugins[$key]['Remote'])) {
					add_filter("plugin_action_links_{$key}", 'wpematico_addons_row_actions', 15, 4);
				}
			}
		}
	} else {
		/*
		 * * If wpem_show_locally_addons option is checked not will be filtered Add Ons WPeMatico. 
		 */
		if (!$show_on_plugin_page) {
			foreach ($plugins as $key => $value) {
				if (strpos($key, 'wpematico_') !== FALSE) {
					unset($plugins[$key]);
				}
			}
		}

		if ($filter) {
			$plugins = apply_filters('etruel_wpematico_addons_array', read_wpem_addons($plugins), 10, 1);
			foreach ($plugins as $key => $value) {
				if (strpos($key, 'wpematico_') === FALSE) {
					unset($plugins[$key]);
				} else {
					if (isset($plugins[$key]['Remote'])) {
						add_filter("plugin_action_links_{$key}", 'wpematico_addons_row_actions', 15, 4);
					}
				}
			}
		}
	}
//	unset( $plugins['akismet/akismet.php'] );

	return $plugins;
}

function wpematico_addons_row_actions($actions, $plugin_file, $plugin_data, $context) {
	$actions			 = array();
	$actions['buynow']	 = '<a target="_Blank" class="edit" '
			/* translators: %s WPeMatico Plugin Name */
			. 'aria-label="' . esc_attr(sprintf(__('Go to %s WebPage', 'wpematico'), $plugin_data['Name'])) . '" '
			/* translators: %s WPeMatico Plugin Name */
			. 'title="' . esc_attr(sprintf(__('Open %s WebPage in new window.', 'wpematico'), $plugin_data['Name'])) . '" '
			. 'href="' . $plugin_data['PluginURI'] . '">' . __('Details', 'wpematico') . '</a>';
	return $actions;
}

/**
 * The memberships etruel.com sells, keyed by Plugin Name.
 *
 * Read from the store's own memberships feed: the add-ons feed carries most of
 * them, but not all — PREMIUM is only published here — so a membership could be
 * missing from this screen entirely.
 *
 * @return array
 */
function wpematico_get_memberships_maybe_fetch() {
	static $by_name = null;

	if (null !== $by_name) {
		return $by_name;
	}

	$items	 = wpematico_get_addons_maybe_fetch('https://etruel.com/downloads/category/etruel-memberships/feed/', 'etruel_wpematico_memberships_data');
	$by_name = array();
	foreach ($items as $item) {
		if (!empty($item['Name'])) {
			$item['membership']		= true;
			$by_name[$item['Name']]	= $item;
		}
	}

	return $by_name;
}

/**
 * The names of every membership, from the store plus a declared safety net.
 *
 * ★ The add-ons feed already tells them apart: <membership> lists the
 * memberships a plugin *belongs to*, and a bundle belongs to none. Feed Creator
 * is a membership too (it carries Make me Feed Good and Full Content), so it has
 * no <membership> either, and rightly so.
 *
 * @return string[]
 */
function wpematico_membership_products() {
	static $products = null;

	if (null !== $products) {
		return $products;
	}

	$declared = array(
		'WPeMatico ESSENTIALS',
		'WPeMatico PLUS',
		'WPeMatico PERFECT',
		'WPeMatico PREMIUM',
		'Feed Creator',
	);
	$options = wpematico_addons_cfg();
	if (empty($options['disable_extensions_feed_page'])) {
		$declared = array_unique(array_merge($declared, array_keys(wpematico_get_memberships_maybe_fetch())));
	}

	$products = apply_filters('wpematico_membership_products', $declared);

	return $products;
}

/**
 * The plugin options, resolved once per request — check_options() runs the whole
 * wpematico_check_options chain, and this screen asks for it on every column of
 * every row.
 *
 * ★ Never name a local `$cfg` in this file: it is the plugin-wide global
 * everywhere else, so one added `global` line would silently overwrite it.
 *
 * @return array
 */
function wpematico_addons_cfg() {
	static $options = null;

	if (null === $options) {
		$options = (array) WPeMatico::check_options(get_option(WPeMatico::OPTION_KEY));
	}

	return $options;
}

/**
 * True when this row is a membership bundle rather than a single plugin.
 *
 * @param array $plugin_data
 * @return bool
 */
function wpematico_is_membership($plugin_data) {
	// A free addon on wordpress.org is never a bundle, and its own feed does not
	// carry <membership> at all, so it must not fall through to the test below.
	if (!empty($plugin_data['wporg_slug'])) {
		return false;
	}

	$name = isset($plugin_data['Name']) ? $plugin_data['Name'] : '';
	if (in_array($name, wpematico_membership_products(), true)) {
		return true;
	}

	// From the add-ons feed: belongs to no membership, so it is one.
	return (!empty($plugin_data['Remote']) && empty($plugin_data['memberships']));
}

/**
 * The free addons as published on etruel.com, keyed by Plugin Name.
 *
 * Read from the store's own free-plugins feed, which is where their artwork and
 * description live: the add-ons feed only carries what is sold, so two of the
 * three free addons had no image at all on this screen.
 *
 * @return array
 */
function wpematico_get_free_addons_maybe_fetch() {
	static $by_name = null;

	if (null !== $by_name) {
		return $by_name;
	}

	$items = wpematico_get_addons_maybe_fetch('https://etruel.com/downloads/category/free-plugins/feed/', 'etruel_wpematico_free_addons_data');
	$by_name = array();
	foreach ($items as $item) {
		if (!empty($item['Name'])) {
			$by_name[$item['Name']] = $item;
		}
	}

	return $by_name;
}

/**
 * How long a store feed is allowed to hold the admin up, in seconds.
 *
 * ★ The campaign fetch timeout (wpe_simplepie_timeout, 130s) is the right budget
 * for a feed the user asked to import and the wrong one for artwork on a settings
 * screen: three feeds at 130s is over six minutes of a blank admin page whenever
 * the store is unreachable.
 *
 * @return int
 */
function wpematico_addons_feed_timeout() {
	return (int) apply_filters('wpematico_addons_feed_timeout', 8);
}

/**
 * One store feed, cached.
 *
 * ★ Ask SimplePie's own error(), never is_wp_error(): fetchFeed() always returns a
 * SimplePie object, so an unreachable store used to fall through the success path
 * and cache an empty catalogue for five days. A failure now sets a short backoff
 * instead of poisoning the cache.
 *
 * Guarded further by a per-request memo (the table asks once per column of every
 * row) and a bounded timeout, see wpematico_addons_feed_timeout().
 *
 * @param string $feed_url
 * @param string $transient
 * @return array
 */
function wpematico_get_addons_maybe_fetch($feed_url = '', $transient = 'etruel_wpematico_addons_data') {
	static $memo = array();

	if (isset($memo[$transient])) {
		return $memo[$transient];
	}

	// The screen reads three feeds off the same store. Once one of them has failed,
	// the other two would each pay the whole timeout again on the same page load, so
	// the back-off is shared: a store that is down costs one bounded attempt, not three.
	$cached = get_transient($transient);
	if (!is_array($cached) && !get_transient($transient . '_backoff') && !get_transient('etruel_wpematico_store_backoff')) { // If no cache read source feed
		$urls_addon	 = $feed_url ? $feed_url : 'https://etruel.com/downloads/category/wpematico-add-ons/feed/';
		add_filter('wpe_simplepie_timeout', 'wpematico_addons_feed_timeout', 99);
		$addonitems	 = WPeMatico_functions::fetchFeed($urls_addon, true, 200);
		remove_filter('wpe_simplepie_timeout', 'wpematico_addons_feed_timeout', 99);
		$addon		 = array();
		$addons		 = array();
		// SimplePie answers for itself: error() carries the reason the fetch or the
		// parse failed. is_wp_error() never could — see the note above.
		$failed		 = !is_object($addonitems)
				|| !method_exists($addonitems, 'get_items')
				|| (method_exists($addonitems, 'error') && $addonitems->error());

		if (!$failed) {
			foreach ($addonitems->get_items() as $item) {
				$itemtitle	 = $item->get_title();
				$versions	 = $item->get_item_tags('', 'version');
				$version	 = (is_array($versions)) ? $versions[0]['data'] : '';
				$memberships = $item->get_item_tags('', 'membership');
				$memberships = (is_array($memberships)) ? array_column($memberships, 'data') : [];

				$guid		 = $item->get_item_tags('', 'guid');
				$guid		 = (is_array($guid)) ? $guid[0]['data'] : '';
				$download_id = 0;
				wp_parse_str($guid, $query);
				if (isset($query) && !empty($query)) {
					if (isset($query['p'])) {
						$download_id = $query['p'];
					}
				}

				// The list keeps only the keys carrying "wpematico_", so a product of
				// this very feed whose title does not start with it — "Feed Creator" —
				// was being dropped and never shown at all.
				$plugindirname			 = str_replace('-', '_', strtolower(sanitize_file_name($itemtitle)));
				if (false === strpos($plugindirname, 'wpematico_')) {
					$plugindirname = 'wpematico_' . $plugindirname;
				}
				$enclosure				 = $item->get_enclosure();
				$img					 = ( $enclosure && !empty($enclosure->link) ) ? $enclosure->link : '';
				if ('' !== $img) {
					$icon = '<img width="100" src="' . esc_url($img) . '" alt="' . esc_attr($itemtitle) . '">';
				} else {
					// No image in the feed: render a CSS placeholder box (gray + B&W plugin dashicon).
					$icon = '<span class="wpem-addon-icon-placeholder" aria-hidden="true"><span class="dashicons dashicons-admin-plugins"></span></span>';
				}
				$addon[$plugindirname]	 = Array(
					'Name'			 => $itemtitle,
					'icon'			 => $icon,
					'PluginURI'		 => $item->get_permalink(),
					'buynowURI'		 => 'https://etruel.com/checkout?edd_action=add_to_cart&download_id=' . $download_id . '&edd_options[price_id]=2',
					'Version'		 => $version, // $item->get_date('U'),
					'Description'	 => $item->get_description(),
					'Author'		 => 'Etruel Developments LLC',
					'AuthorURI'		 => 'https://etruel.com',
					'TextDomain'	 => '',
					'DomainPath'	 => '',
					'Network'		 => '',
					'Title'			 => $itemtitle,
					'AuthorName'	 => 'etruel',
					'Remote'		 => true,
					'memberships'	 => $memberships,
					'id'			 => $download_id
				);
			}
			$addons = apply_filters('etruel_wpematico_addons_array', array_filter($addon));
		}

		if ($failed || empty($addons)) {
			// Never store "the store sells nothing" for five days. Back off instead,
			// so a store that is down or slow costs one bounded attempt every so
			// often rather than one on every single load of this screen.
			$backoff = apply_filters('wpematico_addons_feed_backoff', 15 * MINUTE_IN_SECONDS);
			set_transient($transient . '_backoff', 1, $backoff);
			set_transient('etruel_wpematico_store_backoff', 1, $backoff);
		} else {
			$length	 = apply_filters('etruel_wpematico_addons_transient_length', DAY_IN_SECONDS * 5);
			set_transient($transient, $addons, $length);
			$cached	 = $addons;
		}
	}
	// Never return false: callers always expect an array to iterate.
	$memo[$transient] = is_array($cached) ? $cached : array();

	return $memo[$transient];
}

/**
 * Drop the cached store feeds, backoff included, so the next visit refetches.
 * Bound to the plugin cache events, where "what is for sale" may have changed.
 */
function wpematico_flush_addons_feed_cache() {
	foreach (array('etruel_wpematico_addons_data', 'etruel_wpematico_memberships_data', 'etruel_wpematico_free_addons_data') as $transient) {
		delete_transient($transient . '_backoff');
	}
}

add_action('upgrader_process_complete', 'wpematico_flush_addons_feed_cache');

//		$active_plugins = get_option('active_plugins');

/**
 * The addons published free on WordPress.org. The store feed this page is built
 * from only lists what is sold, so these are offered for installation from
 * wordpress.org instead.
 *
 * Keyed by wp.org slug, valued by the `Plugin Name` header — the folder is not
 * stable across builds (see WPeMatico::ADDONS_REQUIRED).
 *
 * @return array slug => Plugin Name
 */
function wpematico_free_addons() {
	return apply_filters('wpematico_free_addons', array(
		'wpematico-rss-feed-reader' => 'WPeMatico RSS Feed Reader',
		'wpematico-polylang'		=> 'WPeMatico Polylang',
		'wpematico-custom-hooks'	=> 'WPeMatico Custom Hooks',
	));
}

/**
 * The installed plugin file of an addon, matched by its `Plugin Name`, or ''.
 *
 * @param string $name Plugin Name header.
 * @return string
 */
function wpematico_installed_addon_file($name) {
	if (!function_exists('get_plugins')) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	static $installed = null;
	if (null === $installed) {
		$installed = get_plugins();
	}
	foreach ($installed as $file => $data) {
		if (isset($data['Name']) && $data['Name'] === $name) {
			return $file;
		}
	}

	return '';
}

/**
 * Whether two entries describe the same addon: same name, or same store page.
 *
 * Comparing only strstr($uri, '://') treated two plugins with no PluginURI as
 * equal, because strstr() returns false for both.
 *
 * @param array $plugin
 * @param array $addon
 * @return bool
 */
function wpematico_is_same_addon($plugin, $addon) {
	if (!empty($plugin['Name']) && !empty($addon['Name']) && $plugin['Name'] === $addon['Name']) {
		return true;
	}
	$plugin_uri	 = empty($plugin['PluginURI']) ? '' : untrailingslashit(strstr($plugin['PluginURI'], '://'));
	$addon_uri	 = empty($addon['PluginURI']) ? '' : untrailingslashit(strstr($addon['PluginURI'], '://'));

	return ('' !== $plugin_uri && $plugin_uri === $addon_uri);
}

/**
 * Return the array of plugins plus WPeMatico Add-on found on etruel.com website
 * @param array $plugins current plugins
 */
function read_wpem_addons($plugins) {

	$options = wpematico_addons_cfg();
	$cached	 = array();
	if (empty($options['disable_extensions_feed_page'])) {
		$cached = wpematico_get_addons_maybe_fetch();
	}

	// Memberships the add-ons feed does not carry (PREMIUM is only published in
	// the memberships feed) would otherwise never show up on this screen.
	if (empty($options['disable_extensions_feed_page'])) {
		$known = wp_list_pluck($cached, 'Name');
		foreach (wpematico_get_memberships_maybe_fetch() as $name => $row) {
			if (in_array($name, $known, true)) {
				continue;
			}
			$key = str_replace('-', '_', strtolower(sanitize_file_name($name)));
			if (false === strpos($key, 'wpematico_')) {
				$key = 'wpematico_' . $key;
			}
			$cached[$key] = $row;
		}
	}

	// Tag the feed entries that are free on wordpress.org, and add the free ones
	// the feed does not carry at all.
	$free	   = wpematico_free_addons();
	$free_feed = empty($options['disable_extensions_feed_page']) ? wpematico_get_free_addons_maybe_fetch() : array();
	foreach ($free as $slug => $name) {
		$found = false;
		foreach ($cached as $Akey => $addon) {
			if (isset($addon['Name']) && $addon['Name'] === $name) {
				$cached[$Akey]['wporg_slug'] = $slug;
				$found						 = true;
				break;
			}
		}
		if ($found || '' !== wpematico_installed_addon_file($name)) {
			continue;
		}
		// Not in the feed and not installed: build the row from the store's
		// free-plugins feed, which is where its artwork and description are.
		// The list only keeps keys carrying "wpematico_", so a free addon whose
		// slug does not start with it (a companion plugin, say) still shows up.
		$key = str_replace('-', '_', $slug);
		if (false === strpos($key, 'wpematico_')) {
			$key = 'wpematico_' . $key;
		}
		if (isset($free_feed[$name])) {
			$row = $free_feed[$name];
		} else {
			$row = array(
				'Name'		  => $name,
				'icon'		  => '<span class="wpem-addon-icon-placeholder" aria-hidden="true"><span class="dashicons dashicons-admin-plugins"></span></span>',
				'PluginURI'	  => 'https://wordpress.org/plugins/' . $slug . '/',
				'Version'	  => '',
				'Description' => '',
				'Author'	  => 'Etruel Developments LLC',
				'AuthorURI'	  => 'https://etruel.com',
				'TextDomain'  => '',
				'DomainPath'  => '',
				'Network'	  => '',
				'Title'		  => $name,
				'AuthorName'  => 'etruel',
				'Remote'	  => true,
				'memberships' => array(),
				'id'		  => 0,
			);
		}
		$row['wporg_slug'] = $slug;
		$cached[$key]	   = $row;
	}

	foreach ($plugins as $key => $plugin) {
		foreach ($cached as $Akey => $addon) {
			// Same addon on both sides: keep the installed one, drop the feed row.
			if (wpematico_is_same_addon($plugin, $addon)) {
				unset($cached[$Akey]);
				$plugins[$key]['installed'] = true;

				//see if part of membership ?
			}
		}
	}
	$plugins = array_merge_recursive($plugins, $cached);

	return $plugins;
}

add_filter('views_plugins', function($views) {

	$show_on_plugin_page = get_option('wpem_show_locally_addons', false);

	if($show_on_plugin_page){
		$all_plugins = get_plugins(); // Get all installed plugins
		$wpematico_addons  = wpematico_showhide_addons($all_plugins, true);
		$active_addons_count = count($wpematico_addons);

		$wpematico_link = '<a href="'. admin_url('plugins.php?wpematico_addons=addons') .'" class="wpelinks">'.__('WPeMatico Addons ', 'wpematico'). '<span class="count">(' . $active_addons_count . ')</span></a>';
		$views['wpematico-addons-active'] = $wpematico_link; // Add custom link to the list
	}else{
		$wpematico_link = '<a href="'. admin_url('plugins.php?page=wpemaddons') .'" class="wpelinks" target="_top">'.__('WPeMatico Addons', 'wpematico') . '</a>';
		$views['wpematico-addons-active'] = $wpematico_link; // Add custom link to the list
	}
    
    return $views;
});

add_action('pre_current_active_plugins', function() {
    if (isset($_GET['wpematico_addons']) && $_GET['wpematico_addons'] == 'active') {
        global $wp_list_table;
		$all_plugins = get_plugins(); // Get all installed plugins
		$wpematico_addons  = wpematico_showhide_addons($all_plugins, true);
		$wpematico_active_addons = array();
		foreach ($wpematico_addons as $key => $addon) {
			// Check if the plugin is active
			if (is_plugin_active($key)) {
				// Step 5: Add the active addon to the active addons array
				$wpematico_active_addons[] = $key;  // Store the plugin path (e.g., wpematico-addon-1/wpematico-addon-1.php)
			}
		}
		
        // Filter the plugins list to show only WPeMatico addons
        $wp_list_table->items = array_filter($wp_list_table->items, function($plugin_data, $plugin_file) use ($wpematico_active_addons) {
            return in_array($plugin_file, $wpematico_active_addons, true);
        }, ARRAY_FILTER_USE_BOTH);
    }elseif(isset($_GET['wpematico_addons']) && $_GET['wpematico_addons'] == 'addons'){
		global $wp_list_table;
		$all_plugins = get_plugins(); // Get all installed plugins
		$wpematico_addons  = wpematico_showhide_addons($all_plugins, true);
		$wpematico_active_addons = array();
		foreach ($wpematico_addons as $key => $addon) {
			// Check if the plugin is active
			$wpematico_active_addons[] = $key;  // Store the plugin path (e.g., wpematico-addon-1/wpematico-addon-1.php)
		}
		
        // Filter the plugins list to show only WPeMatico addons
        $wp_list_table->items = array_filter($wp_list_table->items, function($plugin_data, $plugin_file) use ($wpematico_active_addons) {
            return in_array($plugin_file, $wpematico_active_addons, true);
        }, ARRAY_FILTER_USE_BOTH);
	}
});



add_action('admin_init', 'manage_old_addons');

function wpematico_get_old_addons(){
	$addons = array();

	// Absorbed into core in 2.9 as the Vimeo campaign type. Resolved by Plugin Name
	// and not by folder: the etruel.com and wp.org builds install under different
	// folder names, and every lookup by folder misses one of them.
	$vimeo = wpematico_installed_addon_file('WPeMatico Vimeo');
	if ('' !== $vimeo) {
		$addons['wpematico_vimeo_campaign_type'] = array(
			'action_link'	=> __('Inactive &mdash; Now part of WPeMatico', 'wpematico'),
			'basename'		=> $vimeo,
			// Switching the plugin off must not take the feature with it: the campaigns
			// that used it keep running on the core type.
			'on_deactivate' => 'wpematico_enable_vimeo_campaign_type',
		);
	}

	return apply_filters('wpematico_get_old_addons', $addons); 
}

/**
 * Turns the Vimeo campaign type on. Called when the old addon is deactivated, and
 * from the installer when the addon is found installed, so a site that had the
 * feature keeps it after the update without having to know it moved.
 */
function wpematico_enable_vimeo_campaign_type() {
	$cfg = get_option(WPeMatico::OPTION_KEY, array());
	if (!is_array($cfg) || !empty($cfg['enable_vimeo'])) {
		return;
	}
	$cfg['enable_vimeo'] = true;
	update_option(WPeMatico::OPTION_KEY, $cfg);
}

/**
 * Deactivates the legacy extension.
 *
 * @since 3.1.1
 * @return void
 */
function manage_old_addons(){
	foreach (wpematico_get_old_addons() as $extension) {

		add_action("plugin_action_links_{$extension['basename']}",  function($links, $plugin_file) use ($extension){
			$links['activate'] = $extension['action_link'];
			return $links;
		}, 10, 2);

		if (! is_plugin_active($extension['basename'])) {
			continue;
		}
		if (wpematico_should_deactivate($extension['basename'])) {

			// $this->maybe_do_notification( $extension );

			if (! empty($extension['on_deactivate']) && is_callable($extension['on_deactivate'])) { //if the addon has some action before deactivate
				add_action("deactivate_{$extension['basename']}", $extension['on_deactivate']);
			}
			deactivate_plugins($extension['basename']);
		}
		if (! empty($extension['option'])) {
			delete_option($extension['option']);
		}
	}
}

function wpematico_should_deactivate($basename){
	return true;
}