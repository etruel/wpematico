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
function wpematico_get_settings_tabs() {
	$tabs					 = array();
	$tabs['settings']		 = __('Settings', 'wpematico');
	//allways Licenses and debug file at end
	$tabs					 = apply_filters('wpematico_settings_tabs', $tabs);
	$tabs['pro_licenses']	 = __('Licenses', 'wpematico');

	return $tabs;
}


/**
 * Retrieve debug_info tools sections 
 * Use in same way to add sections to the different tabs "wpematico_get_'tab-key'_sections"
 * @since       2.3.9
 * @return      array with Settings tab sections
 */


//Make Tabs calling actions and Sections if exist
function wpematico_settings_page() {
	global $pagenow, $wp_roles, $current_user;
	//$cfg = get_option(WPeMatico :: OPTION_KEY);
	$current_tab = (isset($_GET['tab']) ) ? sanitize_text_field( $_GET['tab'] ) : 'settings';
	$tabs		 = wpematico_get_settings_tabs();
	$sections = array();
	$get_sections= "wpematico_get_".$current_tab."_sections";
	if(function_exists($get_sections)) {
		//$sections = $get_sections();
		add_action('wpematico_settings_tab_'.$current_tab, 'wpematico_print_tab_tools_sections',0,1);

	}
	
	?>
	<div class="wrap">
		<h2 class="nav-tab-wrapper">
			<?php
			foreach($tabs as $tab_id => $tab_name) {
				$tab_url = add_query_arg(array(
					'tab' => $tab_id
				));

				$tab_url = remove_query_arg(array(
					'section'
					), $tab_url);

				$active = $current_tab == $tab_id ? ' nav-tab-active' : '';
				echo '<a href="' . esc_url($tab_url) . '" title="' . esc_attr(sanitize_text_field($tab_name)) . '" class="nav-tab' . esc_attr($active) . '">' . wp_kses_post( $tab_name ) . '</a>';
			}
			?>
		</h2>
		<div class="metabox-holder">
			<?php
			do_action('wpematico_settings_tab_' . $current_tab);
			?>
		</div><!-- .metabox-holder -->
	</div><!-- .wrap -->
	<?php
}


function wpematico_print_tab_tools_sections() {
	global $pagenow, $wp_roles, $current_user;
	$current_tab = (isset($_GET['tab']) ) ? sanitize_text_field( $_GET['tab'] ) : 'settings';
	$sections = array();
	$get_sections= "wpematico_get_".$current_tab."_sections";
	if(function_exists($get_sections)) {
		$sections = $get_sections();
	}
	$current_section = (isset($_GET['section']) ) ? sanitize_text_field( $_GET['section'] ) : key($sections);
	?>	
	<div class="wrap">
		<h3 class="nav-section-wrapper">
			<?php
			$f = TRUE;
			foreach($sections as $section_id => $section_name) {
				$section_url = add_query_arg(array(
					'section' => $section_id
				));
				if(!$f)
					echo " | ";
				else
					$f		 = FALSE;
				$active	 = $current_section == $section_id ? ' nav-section-active' : '';
				echo '<a href="' . esc_url($section_url) . '" title="' . esc_attr($section_name) . '" class="nav-section' . esc_attr($active) . '">' . wp_kses_post( $section_name ) . '</a>';
			}
			?>
		</h3>
		<div class="metabox-holder">
			<?php
			do_action('wpematico_settings_section_' . $current_section);
			?>
		</div><!-- .metabox-holder -->
	</div><!-- .wrap -->
	<?php
}

