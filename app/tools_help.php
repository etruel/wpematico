<?php
// don't load directly 
if(!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

/**
 * WPeMatico Settings Help
 * This class is used to make the help contents on tabs and tips
 * @since 2.7.1
 */
function wpematico_helptools($dev = '') {
	$helptools	 = array(
		'Global Settings'	 => array(
			'imgoptions'	 => array(
				'title'	 => __('Global Settings For Images.', 'wpematico'),
				'tip'	 => __('Set this features for all campaigns and can be overridden inside any campaign.', 'wpematico'),
			),
			'imgcache'		 => array(
				'title'	 => __('Store images locally. (Uploads)', 'wpematico'),
				'tip'	 => sprintf(__('When Store images locally is on, a copy of every image found in content of every feed item (only in %s tags) is downloaded to the Wordpress UPLOADS Dir.', 'wpematico'), '&lt;img&gt;') . "<br />" .
				__('If not enabled all images will be linked to the image owner\'s server, but also make your website faster for your visitors.', 'wpematico') . "<br />" .
				"<b>" . __('Caching all images', 'wpematico') . ":</b> " .
				__('This featured in the general Settings section, will be overridden for the campaign-specific options.', 'wpematico'),
			),
			'imgattach'		 => array(
				'title'		 => __('Attach Images to post.', 'wpematico'),
				'tip'		 => "<b>" . __('Image Attaching', 'wpematico') . ":</b> " .
				__('When Uploads images to Wordpress (and everything is working fine), every image attached is added to the Wordpress Media.', 'wpematico') . "<br />" .
				__('If enable this feature all the images will be attached to its owner post in WP media library.', 'wpematico'),
				'plustip'	 => __('If you see that the job process is too slowly you can deactivate this here.', 'wpematico') . "<br />" .
				__('This feature may not work if you use the Custom Function for Uploads.', 'wpematico')
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
		$helptip = array_merge($helptip, array(
			'PROfeatures'		 => __('Features only available when you buy the Professional Addon.', 'wpematico'),
			'enablekwordf'		 => __('This is for exclude or include posts according to the keywords found at content or title.', 'wpematico'),
			'enablewcf'			 => __('This is for cut, exclude or include posts according to the letters o words counted at content.', 'wpematico'),
			'enablecustomtitle'	 => __('If you want a custom title for posts of a campaign, you can activate here.', 'wpematico'),
			'enabletags'		 => __('This feature generate tags automatically on every published post, on campaign edit you can disable auto feature and manually enter a list of tags or leave empty.', 'wpematico'),
			'enablecfields'		 => __('Add custom fields with values as templates on every post.', 'wpematico'),
			'fullcontent'		 => __('Full Content is the correct addon if you want to attempt to obtain full items content from source site instead of the campaign feed.', 'wpematico'),
			'authorfeed'		 => __('This option allow you assign an author per feed when editing campaign. If no choice any author, the campaign author will be taken.', 'wpematico'),
			'importfeeds'		 => __('On campaign edit you can import, copy and paste in a textarea field, a list of feed addresses with/out author names.', 'wpematico'),
			)
		);
		return apply_filters('wpematico_helptip_tools', $helptip);
	}
	return apply_filters('wpematico_help_tools', $helptools);
}