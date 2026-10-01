<?php
// don't load directly 
if(!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

/**
 * Retrieve Settings Tabs  
 * Default sections by tab below 
 * @since       1.2.4
 * @return      array
 */
function wpematico_get_tools_tabs() {
	$tabs					 = array();
	$tabs['tools']		 = __('Tools', 'wpematico');
	$tabs['debug_info']		 = __('System', 'wpematico');
	$danger = WPeMatico::get_danger_options();

	if(!empty($danger['wpematico_debug_log_file'])) {
		$tabs['debug_log'] = __('Logs', 'wpematico');
	}

	return apply_filters('wpematico_tools_tabs', $tabs);
}


/**
 * Retrieve debug_info tools sections 
 * Use in same way to add sections to the different tabs "wpematico_get_'tab-key'_sections"
 * @since       2.3.9
 * @return      array with Settings tab sections
 */
//function wpematico_get_debug_log_sections() {
//	$danger = WPeMatico::get_danger_options();
//	$sections = array();
//
//	if(!empty($danger['wpematico_debug_log_file'])) {
//		$sections['debug_log_file']	 = __('Debug Log File', 'wpematico');
//		$sections = apply_filters('wpematico_get_debug_sections', $sections);
//	}
//		
//	return $sections;
//
//}

function wpematico_get_debug_info_sections() {
	// Status first: it is also the landing section (the section defaults to
	// key($sections)), and the dashboard links here as "System Status". Opening
	// on the Danger Zone was both surprising and the wrong thing to offer first.
	$sections = array();
	$sections['debug_file']	 = __('Status', 'wpematico');
	$sections['danger_zone'] = __('Danger Zone', 'wpematico');
	$sections = apply_filters('wpematico_get_debug_info_sections', $sections);

	return $sections;
}

function wpematico_get_tools_sections() {
	// Ordered by how often they get used: the first one is also what the Tools menu
	// entry lands on, since the section defaults to key($sections).
	$sections = array();
	$sections['feed_list']	 = __('Feed List', 'wpematico');
	$sections['feed_viewer'] = __('Feed Viewer', 'wpematico');
	// Key stays 'tools' — it names the wpematico_tools_section_tools hook and any
	// bookmarked URL. Only the label says what the section actually holds.
	$sections['tools']		 = __('Export / Import', 'wpematico');
	// Only when the Plugin Importers module is on: the class is not even loaded
	// otherwise, so the section would render empty.
	if (class_exists('WPeMatico_Migration_Toolkit')) {
		$sections['migration'] = __('Migration Toolkit', 'wpematico');
	}
	$sections = apply_filters('wpematico_get_tools_sections', $sections);

	return $sections;
}

/**
 * Query args that belong to one visit, not to the screen.
 *
 * The tab and section links are built from the current URL, so anything left in it
 * rides along: landing on the Feed Viewer with ?feedlink= made every later click on
 * "Feed Viewer" re-open — and re-fetch — the last URL inspected instead of an empty
 * form.
 *
 * @since 2.9
 * @return array
 */
function wpematico_tools_transient_args() {
	return (array) apply_filters('wpematico_tools_transient_args', array('feedlink'));
}

//Make Tabs calling actions and Sections if exist
function wpematico_tools_page() {
	global $pagenow, $wp_roles, $current_user;
	//$cfg = get_option(WPeMatico :: OPTION_KEY);
	$current_tab = (isset($_GET['tab']) ) ? sanitize_text_field( $_GET['tab'] ) : 'tools';
	$tabs		 = wpematico_get_tools_tabs();
	$sections = array();
	$get_sections= "wpematico_get_".$current_tab."_sections";
	if(function_exists($get_sections)) {
		//$sections = $get_sections();
		add_action('wpematico_tools_tab_'.$current_tab, 'wpematico_print_tab_sections',0,1);

	}
	
	?>
	<div class="wrap">
		<h2 class="nav-tab-wrapper">
			<?php
			foreach($tabs as $tab_id => $tab_name) {
				$tab_url = add_query_arg(array(
					'tab' => $tab_id
				));

				$tab_url = remove_query_arg(array_merge(
					array('section'),
					wpematico_tools_transient_args()
					), $tab_url);

				$active = $current_tab == $tab_id ? ' nav-tab-active' : '';
				echo '<a href="' . esc_url($tab_url) . '" title="' . esc_attr(sanitize_text_field($tab_name)) . '" class="nav-tab' . $active . '">' . ( $tab_name ) . '</a>';
			}
			?>
		</h2>
		<div class="metabox-holder">
			<?php
			do_action('wpematico_tools_tab_' . $current_tab);
			?>
		</div><!-- .metabox-holder -->
	</div><!-- .wrap -->
	<?php
}


function wpematico_print_tab_sections() {
	global $pagenow, $wp_roles, $current_user;
	$current_tab = (isset($_GET['tab']) ) ? sanitize_text_field( $_GET['tab'] ) : 'tools';
	$sections = array();
	$get_sections= "wpematico_get_".$current_tab."_sections";
	if(function_exists($get_sections)) {
		$sections = $get_sections();
	}
	$current_section = WPeMatico::current_screen_section($current_tab, $sections);
	?>	
	<div class="wpe_wrap">
		<h3 class="nav-section-wrapper">
			<?php
			$f = TRUE;
			foreach($sections as $section_id => $section_name) {
				$section_url = remove_query_arg(
						wpematico_tools_transient_args(),
						add_query_arg(array('section' => $section_id))
				);
				if(!$f)
					echo " | ";
				else
					$f		 = FALSE;
				$active	 = $current_section == $section_id ? ' nav-section-active' : '';
				echo '<a href="' . esc_url($section_url) . '" title="' . esc_attr($section_name) . '" class="nav-section' . $active . '">' . ( $section_name ) . '</a>';
			}
			?>
		</h3>
		<div class="metabox-holder">
			<?php
			do_action('wpematico_tools_section_' . $current_section);
			?>
		</div><!-- .metabox-holder -->
	</div><!-- .wrap -->
	<?php
}

