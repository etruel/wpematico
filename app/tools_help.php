<?php
// don't load directly 
if(!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

/**
 * WPeMatico Tools Help
 * This class is used to make the help contents on tabs and tips
 * @since 2.7.1
 */
function wpematico_helptools($dev = '') {
	$helptools	 = array(
		__('Global Settings', 'wpematico')	=> array(
			'export_settings'	 => array(
				'title'	 => __('Export Settings.', 'wpematico'),
				'tip'	 => __('This feature allows you to export the configuration settings of WPeMatico and its addons for your current site into a .json file. The exported file contains all the relevant settings.', 'wpematico') . "<br />" .
				__('It\'s useful when you want to replicate the same setup from one site to another.', 'wpematico'),
			),
			'import_settings'	 => array(
				'title'	 => __('Import Settings.', 'wpematico'),
				'tip'	 =>__('Allows to import the WPeMatico settings from an existing .json file. When you import the settings, WPeMatico will apply the same configuration to your current site.', 'wpematico') . "<br />" .
				__('This is helpful when you want to quickly set up WPeMatico on a new site using a configuration that you\'ve previously exported.', 'wpematico') . "<br />" .
				__('Make sure to have the .json file ready (exported from another site) before using this feature.', 'wpematico'),
			),
		),
	);
	$helptools	 = apply_filters('wpematico_help_tools_before', $helptools);
	if($dev == 'tips') {
		foreach($helptools as $key => $section) {
			foreach($section as $section_key => $sdata) {
				$helptip[$section_key] = htmlentities($sdata['tip']);
			}
		}
		return apply_filters('wpematico_helptip_tools', $helptip);
	}
	return apply_filters('wpematico_help_tools', $helptools);
}