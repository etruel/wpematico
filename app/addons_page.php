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
	if ($plugin_updates === false) {
		$plugin_updates = new stdClass();
	}
	if (empty($plugin_updates)) {
		$plugin_updates = new stdClass();
	}

	if (!isset($plugin_updates->response)) {
		$plugin_updates->response = array();
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

			$location = '';

			$actioned = array_multi_key_exists(array('error', 'deleted', 'activate', 'activate-selected', 'activate-multi', 'deactivate', 'deactivate-selected', 'deactivate-multi', '_error_nonce'), $_REQUEST, false);
			if (( isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'page=wpemaddons') ) && $actioned) {
				$location = add_query_arg('page', 'wpemaddons', $location);
				if (!headers_sent()) {
					wp_redirect($location);
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

add_action('admin_menu', 'wpe_addon_admin_menu', 99);

function wpe_addon_admin_menu() {

	if (!empty($_REQUEST['verify-delete'])) {
		wpe_include_plugins();
		return false;
	}

	// Process Bulk actions
	if (!empty($_REQUEST['action'])) {
		// List of checked plugin rows
		$plugins = isset($_REQUEST['checked']) ? (array) wp_unslash($_REQUEST['checked']) : array();

		if ($_REQUEST['action'] == 'delete-selected') {
			$plugins = array_filter($plugins, 'is_plugin_inactive'); // Do not allow to delete Activated plugins.
			if (empty($plugins)) {
				wpe_include_plugins();
				return false;
			}
		}
		if ($_REQUEST['action'] == 'activate-selected') {
			$plugins = array_filter($plugins, 'is_plugin_active'); // Do not allow to activate Activated plugins.
			if (empty($plugins)) {
				wpe_include_plugins();
				return false;
			}
		}
		if ($_REQUEST['action'] == 'deactivate-selected') {
			$plugins = array_filter($plugins, 'is_plugin_inactive'); // Do not allow to deactivate inactive plugins.
			if (empty($plugins)) {
				wpe_include_plugins();
				return false;
			}
		}
	}

	$update_wpematico_addons = wpematico_get_addons_update();
	$count_menu				 = '';
	if (!empty($update_wpematico_addons) && $update_wpematico_addons > 0) {
		$count_menu = "<span class='update-plugins count-{$update_wpematico_addons}' style='position: absolute;	margin-left: 5px;'><span class='plugin-count'>" . number_format_i18n($update_wpematico_addons) . "</span></span>";
	}

	$page	 = add_submenu_page(
			'plugins.php',
			__('Add-ons', 'wpematico'),
			__('WPeMatico Addons', 'wpematico') . ' ' . $count_menu,
			'manage_options',
			'wpemaddons',
			'add_admin_plugins_page'
	);
	add_action('admin_print_scripts-' . $page, 'WPeAddon_admin_scripts');
	$page	 = add_submenu_page(
			'edit.php?post_type=wpematico',
			__('Add-ons', 'wpematico'),
			'<span style="color:#7cc048"> ' . __('Extensions', 'wpematico') . '</span> ' . $count_menu,
			'manage_options',
			'plugins.php?page=wpemaddons'
	);
}

function WPeAddon_admin_scripts() {
	wp_enqueue_script('jquery');
	wp_enqueue_script('plupload-all');
	wp_enqueue_style('plugin-install');
	wp_enqueue_script('plugin-install');
	add_thickbox();
	wp_enqueue_script('wpematico-update', WPeMatico::$uri . 'app/js/wpematico_updates.js', array('jquery', 'inline-edit-post'), WPEMATICO_VERSION, true);
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
			@media screen and (max-width: 782px) {
				.plugins_page_wpemaddons .column-name{
					display: none;
				}
			}
		</style>
		<?php
	}
}

add_action('admin_init', 'wpematico_activate_deactivate_plugins', 0);

function wpematico_activate_deactivate_plugins() {
	global $plugins, $status, $wp_list_table;

	if (wp_doing_ajax() or wp_doing_cron())
		return;

	$accepted_actions	 = array();
	$accepted_actions[]	 = 'deactivate';
	$accepted_actions[]	 = 'activate';
	$accepted_actions[]	 = 'deactivate-selected';
	$accepted_actions[]	 = 'activate-selected';

	// Get the current whole URL
	$current_url = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']));

	// Get the params and componets of the URL
	$parsed_url = parse_url($current_url);

	$get_action = get_option('action_notice_wpematico_addons');
	if (isset($parsed_url['query'])) {
		if (strpos($current_url, 'wpemaddons') !== false || strpos($parsed_url['query'], 'wpematico') !== false) {
			if ($get_action === 'deactivate-addon' or $get_action === 'deactivate-selected-addon') {
				?>
				<div id="message" class="updated notice is-dismissible"><p><?php _e('Addon deactivated.', 'wpematico'); ?></p></div>
				<?php
				delete_option('action_notice_wpematico_addons');
			} elseif ($get_action === 'activate-addon' or $get_action === 'activate-selected-addon') {
				?>
				<div id="message" class="updated notice is-dismissible"><p><?php _e('Addon activated.', 'wpematico'); ?></p></div>
				<?php
				delete_option('action_notice_wpematico_addons');
			}
		}
	}
	// Get the querys params 
	if (isset($parsed_url['query'])) {
		parse_str($parsed_url['query'], $query_params);
		// Continue using $query_params
		$action = isset($query_params['action']) ? $query_params['action'] : '';
		if (strpos($parsed_url['query'], 'wpematico') !== false || strpos($parsed_url['query'], 'wpemaddons') !== false) {
			if (in_array($action, $accepted_actions)) {
				update_option('action_notice_wpematico_addons', $action . '-addon');
			}
		}
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

	$s					 = (!isset($s) || is_null($s)) ? '' : $s;
	$status				 = 'all';
	$page				 = (!isset($page) or is_null($page)) ? 1 : $page;
	$plugins['all']		 = get_plugins();
	wp_update_plugins();
	wp_clean_plugins_cache(false);
	$plugins_list_table	 = new WP_Plugins_List_Table();
	$plugins_list_table->prepare_items();

	echo '<div class="wrap">';
	echo '<h1 class="wp-heading-inline">' . __('WPeMatico Add-Ons Plugins', 'wpematico') . '</h1>';
	echo '<hr class="wp-header-end">';
	// Output the list table HTML
	$plugins_list_table->views();

	echo '<form class="search-form search-plugins" method="get">';
	$plugins_list_table->search_box('Search Plugins', 'plugin-search-input');
	echo '</form>';
	?>
	<form method="post" id="bulk-action-form">
		<input type="hidden" name="plugin_status" value="<?php echo esc_attr($status); ?>" />
		<input type="hidden" name="paged" value="<?php echo esc_attr($page); ?>" />
		<?php
		$plugins_list_table->display();
		?>
	</form>
	<?php
	echo '</div>';
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
	// Return if don't have the wpematico word in its name or uri
	if (strpos($plugin_data['Name'], 'WPeMatico ') === false && strpos($plugin_data['PluginURI'], 'wpematico') === false) {
		return true;
	}

	$cfg	 = WPeMatico::check_options(get_option('WPeMatico_Options'));
	$addons	 = array();
	if (empty($cfg['disable_extensions_feed_page'])) {
		// Get the addon from the transient saved before
		$addons = wpematico_get_addons_maybe_fetch();
	}

	foreach ($addons as $value) {
		$plugin_data_uri = strstr($plugin_data['PluginURI'], '://');
		$addon_data_uri	 = strstr($value['PluginURI'], '://');
		if (($plugin_data['Name'] == $value['Name']) or ($plugin_data_uri == $addon_data_uri)) {
			$addon = $value;
			break;
		}
	}
	switch ($column_name) {
		case 'icon':
			if (isset($addon['icon'])) {
				echo $addon['icon'];
			}
			break;

		case 'buybutton':
			$caption = ( (isset($plugin_data['installed']) && ($plugin_data['installed']) ) || !isset($plugin_data['Remote'])) ? __('Installed', 'wpematico') : __('Purchase', 'wpematico');
			// The css class of the button to give it different style depending on the action given
			$class	 = 'button';
			if (isset($plugin_data['installed']) && ($plugin_data['installed'])) {
				if (!isset($plugin_data['Remote'])) {
					$caption = __('Installed', 'wpematico');
					$title	 = __('See details and prices on etruel\'s store', 'wpematico');
					$url	 = 'https' . strstr($plugin_data['PluginURI'], '://');
					$style	 = "background-color: greenyellow;";
					$class	 .= '';
					//		}else{
					//			$caption = __('Buy', 'wpematico');
				}
			} else {
				//wp_die('<pre>ADDON:' . print_r($addon, 1) . '</pre>');

				if (!isset($plugin_data['Remote'])) {
					$caption = __('Locally', 'wpematico');
					$title	 = __('Go to plugin URI', 'wpematico');
					$url	 = '#' . $plugin_data['Name'];
					$class	 .= '';
					$style	 = "background-color: yellow;";
				} else {
					// is membership ?
					if (isset($addon) && !isset($addon['memberships'][0])) {
						$caption = __('See Plugins', 'wpematico');
						$title	 = __('See plugins in membership at etruel\'s store', 'wpematico');
						$url	 = 'https' . strstr($plugin_data['PluginURI'], '://');
						$class	 .= ' membership button-primary';
						$style	 = "";
					} else { // Is a normal extension available to purchase on website
						$caption = __('Purchase', 'wpematico'); //**** Bundled products always show 'Purchase'
						$title	 = __('Go to purchase on the etruel\'s store', 'wpematico');
						//$url   = 'https'.strstr( $plugin_data['buynowURI'], '://');
						$url	 = 'https' . strstr($plugin_data['PluginURI'], '://');
						$class	 .= ' button-primary';
						$style	 = "";
					}
				}
			}

			$target = ( $caption == __('Locally', 'wpematico') ) ? '_self' : '_blank';
			echo '<a target="' . $target . '" class="' . $class . '" title="' . $title . '" href="' . $url . '" style="' . $style . '">' . $caption . '</a>';
			break;

		default:
			break;
	}
	return true;
}

add_filter('all_plugins', 'wpematico_showhide_addons');

function wpematico_showhide_addons($plugins) {
	global $current_screen;
	if (function_exists('wp_plugin_update_rows')) {
		wp_plugin_update_rows();
	}

	$show_on_plugin_page = get_option('wpem_show_locally_addons', false);
	if ($current_screen->id == 'plugins_page_wpemaddons') {
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

function wpematico_get_addons_maybe_fetch() {
	$cached = get_transient('etruel_wpematico_addons_data');
	if (!isset($cached) || !is_array($cached)) { // If no cache read source feed
		$urls_addon	 = 'http://etruel.com/downloads/category/wpematico-add-ons/feed/';
		$addonitems	 = WPeMatico::fetchFeed($urls_addon, true, 200);
		$addon		 = array();
		if (!is_wp_error($addonitems)) {
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

				$plugindirname			 = str_replace('-', '_', strtolower(sanitize_file_name($itemtitle)));
				$img					 = $item->get_enclosure()->link;
				$icon					 = "<img width=\"100\" src=\"$img\" alt=\"$itemtitle\">";
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
			$addons	 = apply_filters('etruel_wpematico_addons_array', array_filter($addon));
			$length	 = apply_filters('etruel_wpematico_addons_transient_length', DAY_IN_SECONDS * 5);
			set_transient('etruel_wpematico_addons_data', $addons, $length);
			$cached	 = $addons;
		}
	}
	return $cached;
}

//		$active_plugins = get_option('active_plugins');

/**
 * Return the array of plugins plus WPeMatico Add-on found on etruel.com website
 * @param type $plugins array of current plugins
 */
function read_wpem_addons($plugins) {

	$cfg	 = WPeMatico::check_options(get_option('WPeMatico_Options'));
	$cached	 = array();
	if (empty($cfg['disable_extensions_feed_page'])) {
		$cached = wpematico_get_addons_maybe_fetch();
	}
//	echo('<pre>PLUGINS:' . print_r($plugins, 1) . '</pre>');
//	wp_die('<pre>REMOTE:' . print_r($cached, 1) . '</pre>');

	foreach ($plugins as $key => $plugin) {
		foreach ($cached as $Akey => $addon) {
			// if Plugin URIs are equal then unset the one of the feed
			if ($plugin['Name'] == 'WPeMatico Make me Feed Good' && $addon['Name'] == 'WPeMatico Make me Feed Good') {
				$a = 'a';
			}
			if (strstr($plugin['PluginURI'], '://') == strstr($addon['PluginURI'], '://')) {
				// mark as installed
				unset($cached[$Akey]);
				$plugins[$key]['installed'] = true;

				//see if part of membership ?
			}
		}
	}
	$plugins = array_merge_recursive($plugins, $cached);

	return $plugins;
}
