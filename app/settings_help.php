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
 * @since 2.3
 */
function wpematico_helpsettings($dev = '') {
	$helpsettings	 = array(
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
			'gralnolinkimg'	 => array(
				'title'		 => __('Don\'t link external images.', 'wpematico'),
				'tip'		 => "<b>" . __('Note', 'wpematico') . ":</b> " .
				sprintf(__('If is selected and the image upload give error, then will delete the %s HTML tag from the content. Check this to don\'t link images from external sites.', 'wpematico'), '&lt;img&gt;'),
				'plustip'	 => "<b>" . __('Note', 'wpematico') . ":</b> " .
				sprintf(__('If the image are inside %s tags, then the link is also removed from content.', 'wpematico'), '&lt;a&gt;'),
			),
			'image_srcset'	 => array(
				'title'		 => __('Use srcset attribute instead of src of ', 'wpematico') . '&lt;img&gt; ' . __('tag.', 'wpematico'),
				'tip'		 => "<b>" . __('Note', 'wpematico') . ":</b> " .
				sprintf(__('Selecting this option searches the srcset attribute if it exists, it searches for the larger image to overwrite the src attribute of the %s tag.', 'wpematico'), '&lt;img&gt;'),
				'plustip'	 => "<b>" . __('Note', 'wpematico') . ":</b> " .
				__('If the srcset attribute does not exist the image processing will work normally.', 'wpematico'),
			),
			'featuredimg'	 => array(
				'title'		 => __('Set first image on content as Featured Image.', 'wpematico'),
				'tip'		 => __('Check this to set first image found on every content to be uploaded, attached and made Featured.', 'wpematico'),
				'plustip'	 => '<small> ' . __('Read about', 'wpematico') . ' <a href="http://codex.wordpress.org/Post_Thumbnails" target="_Blank">' . __('Post Thumbnails', 'wpematico') . '</a></small>',
			),
			'fifu'	 => array(
				'title'		 => __('Use Featured Image from URL.', 'wpematico'),
				'tip'		 => __('Check this to use Featured Image from URL plugin. Be sure it is installed and activated.', 'wpematico'),
				'plustip'	 => '<small> ' . __('Read about', 'wpematico') . ' <a href="https://wordpress.org/plugins/featured-image-from-url/" rel="nofollow" target="_Blank">' . __('Featured Image from URL', 'wpematico') . '</a> ' . __('plugin in WordPress repository.','wpematico') . '</small><br />' .
					__('Note if that plugin is not activated, WPeMatico will still save the meta fields in each post without any other consequences.', 'wpematico'),
			),
			'rmfeaturedimg'	 => array(
				'title'		 => __('Remove Featured Image from content.', 'wpematico'),
				'tip'		 => __('Check this to strip the Featured Image from the post content.', 'wpematico'),
				'plustip'	 => __('Useful if you have double image in your posts pages or if you don\'t want to show the image in content for any reason.', 'wpematico'),
			),
			'customupload'	 => array(
				'title'		 => __('Custom Uploads for Images.', 'wpematico'),
				'tip'		 => __('Use this instead of Wordpress functions to improve performance. This function uploads the image "as is" from the original to use it inside the post.', 'wpematico') .
				'<br />' . __('This function may not work in all servers.', 'wpematico'),
				'plustip'	 => __('Try it at your own risk, if you see that the images are not loading, uncheck it.', 'wpematico') .
				'<br />' . __('Also uncheck this if you need all sizes of wordpress images. The WP process can take too much resources if many images are uploaded at a time.', 'wpematico'),
			),
			'enablemimetypes'	=> array( 
				'title' => __('Enable add other mime types.', 'wpematico' ),
				'tip' => __('Use this instead of Wordpress functions to improve performance. This function upload the mime types does not avaliable in the wordpress media.', 'wpematico' ).
					'<br />'. __('This function may not work in all servers.', 'wpematico' ),
			),
		),
		'Audio Settings'	 => array(
			'audio_cache'			 => array(
				'title'	 => __('Store audios locally. (Uploads)', 'wpematico'),
				'tip'	 => sprintf(__('When Store audios locally is on, a copy of every audio found in content of every feed item (only in %s tags) is downloaded to the Wordpress UPLOADS Dir.', 'wpematico'), '&lt;audio&gt;') . "<br />" .
				__('If not enabled all audios will be linked to the audio owner\'s server, but also make your website faster for your visitors.', 'wpematico') . "<br />" .
				"<b>" . __('Caching all audios', 'wpematico') . ":</b> " .
				__('This featured in the general Settings section, will be overridden for the campaign-specific options.', 'wpematico'),
			),
			'audio_attach'			 => array(
				'title'		 => __('Attach Audios to post.', 'wpematico'),
				'tip'		 => "<b>" . __('Audio Attaching', 'wpematico') . ":</b> " .
				__('When Uploads audios to Wordpress (and everything is working fine), every audio attached is added to the Wordpress Media.', 'wpematico') . "<br />" .
				__('If enable this feature all the audios will be attached to its owner post in WP media library.', 'wpematico'),
				'plustip'	 => __('If you see that the job process is too slowly you can deactivate this here.', 'wpematico') . "<br />" .
				__('This feature may not work if you use the Custom Function for Uploads.', 'wpematico')
			),
			'gralnolink_audio'		 => array(
				'title'		 => __('Don\'t link external audios.', 'wpematico'),
				'tip'		 => "<b>" . __('Note', 'wpematico') . ":</b> " .
					sprintf(__('If is selected and the audio upload give error, then will delete the %s HTML tag from the content. Check this to don\'t link audio from external sites.', 'wpematico'), '&lt;audio&gt;'),
				'plustip'	 => "<b>" . __('Note', 'wpematico') . ":</b> " .
				sprintf(__('If the audio are inside %s tags, then the link is also removed from content.', 'wpematico'), '&lt;a&gt;'),
			),
			'customupload_audios'	 => array(
				'title'		 => __('Custom Uploads for Audios.', 'wpematico'),
				'tip'		 => __('Use this instead of Wordpress functions to improve performance. This function uploads the audio "as is" from the original to use it inside the post.', 'wpematico') .
				'<br />' . __('This function may not work in all servers.', 'wpematico'),
				'plustip'	 => __('Try it at your own risk, if you see that the audios are not loading, uncheck it.', 'wpematico') .
				'<br />' . __('Also uncheck this if you need all sizes of wordpress audios. The WP process can take too much resources if many audios are uploaded at a time.', 'wpematico'),
			),
		),
		'Video Settings'	 => array(
			'video_cache'			 => array(
				'title'	 => __('Store videos locally. (Uploads)', 'wpematico'),
				'tip'	 => sprintf(__('When Store videos locally is on, a copy of every video found in content of every feed item (only in %s tags) is downloaded to the Wordpress UPLOADS Dir.', 'wpematico'), '&lt;video&gt;') . "<br />" .
				__('If not enabled all videos will be linked to the video owner\'s server, but also make your website faster for your visitors.', 'wpematico') . "<br />" .
				"<b>" . __('Caching all videos', 'wpematico') . ":</b> " .
				__('This featured in the general Settings section, will be overridden for the campaign-specific options.', 'wpematico'),
			),
			'video_attach'			 => array(
				'title'		 => __('Attach Videos to post.', 'wpematico'),
				'tip'		 => "<b>" . __('Video Attaching', 'wpematico') . ":</b> " .
				__('When Uploads videos to Wordpress (and everything is working fine), every video attached is added to the Wordpress Media.', 'wpematico') . "<br />" .
				__('If enable this feature all the videos will be attached to its owner post in WP media library.', 'wpematico'),
				'plustip'	 => __('If you see that the job process is too slowly you can deactivate this here.', 'wpematico') . "<br />" .
				__('This feature may not work if you use the Custom Function for Uploads.', 'wpematico')
			),
			'gralnolink_video'		 => array(
				'title'		 => __('Don\'t link external videos.', 'wpematico'),
				'tip'		 => "<b>" . __('Note', 'wpematico') . ":</b> " .
					sprintf(__('If is selected and the video upload give error, then will delete the %s HTML tag from the content. Check this to don\'t link videos from external sites.', 'wpematico'), '&lt;video&gt;'),
				'plustip'	 => "<b>" . __('Note', 'wpematico') . ":</b> " .
				sprintf(__('If the video are inside %s tags, then the link is also removed from content.', 'wpematico'), '&lt;a&gt;'),
			),
			'customupload_videos'	 => array(
				'title'		 => __('Custom Uploads for Videos.', 'wpematico'),
				'tip'		 => __('Use this instead of Wordpress functions to improve performance. This function uploads the video "as is" from the original to use it inside the post.', 'wpematico') .
				'<br />' . __('This function may not work in all servers.', 'wpematico'),
				'plustip'	 => __('Try it at your own risk, if you see that the videos are not loading, uncheck it.', 'wpematico') .
				'<br />' . __('Also uncheck this if you need all sizes of wordpress videos. The WP process can take too much resources if many videos are uploaded at a time.', 'wpematico'),
			),
		),
		'Enable Features'	 => array(
			'enablefeatures'	 => array(
				'title'	 => __('Enable Features.', 'wpematico'),
				'tip'	 => __('If you need these features in each campaign, you can activate them here. This is not recommended if you will not use the feature.', 'wpematico'),
			),
			'enableword2cats'	 => array(
				'title'	 => __('Word to Categories.', 'wpematico'),
				'tip'	 => __('Assign a selected category to the post if a word is found in the content.', 'wpematico'),
			),
			'enablerewrite'		 => array(
				'title'	 => __('Content Rewrites.', 'wpematico'),
				'tip'	 => __('Rewrite a word or phrase for another in the content of every post.', 'wpematico'),
			),
			'wpematico_set_canonical'	=> array( 
				'title' => __('Enable canonical url', 'wpematico' ),
				'tip' => __('This option adds the canonical URL in the <head> section of each post with the original source link of the article.', 'wpematico' ).
					'<br />'. __('However, it may not work on all servers.', 'wpematico' ),
				'plustip' => __('This indicates to search engines which is the preferred version of the page with similar or duplicated content to optimize SEO and avoid be penalized by them.', 'wpematico'),
			),
		),
		'SimplePie Settings' => array(
			'mysimplepie'	 => array(
				'title'	 => __('Force Custom Simplepie Library.', 'wpematico'),
				'tip'	 => __('Check this if you want to ignore Wordpress Simplepie library.', 'wpematico') . " " .
				__('Almost never be necessary.  Just if you have problems with version of Simplepie installed in Wordpress.', 'wpematico'),
			// 'plustip' => __('', 'wpematico' ),
			),
			'stupidly_fast'	 => array(
				'title'		 => __('Set Simplepie stupidly fast.', 'wpematico'),
				'tip'		 => __('Forgoes a substantial amount of data sanitization in favor of speed. This turns SimplePie into a dumb parser of feeds.  This means all feed content is gotten without parsers or filters.', 'wpematico'),
				'plustip'	 => __('Don\'t strip anything from the content.  All html, style and scripts codes are included in content.', 'wpematico') . "<br>" .
				__('Recommended Just if you really trust in your source feeds', 'wpematico') . ", " .
				__('otherwise you can change the allowed HTML tags and attributes from options below.', 'wpematico'),
			),
			'strip_htmltags' => array(
				'title'	 => __('Change SimplePie HTML tags to strip.', 'wpematico'),
				'tip'	 => __('By Default Simplepie strip these html tags from feed content.  You can change or allow some tags, for example if you want to allow iframes or embed code like videos.', 'wpematico'),
			// 'plustip' => __('', 'wpematico' ),
			),
			'strip_htmlattr' => array(
				'title'	 => __('Change SimplePie HTML attributes to strip.', 'wpematico'),
				'tip'	 => __('Simplepie also strip these attributes from html tags in content.  You can change it if you want to retain some of them or add more attributes to strip.', 'wpematico'),
			// 'plustip' => __('', 'wpematico' ),
			),
		),
		'Advanced Fetching'	 => array(
			'woutfilter'							 => array(
				'title'		 => __('Allow option on campaign to skip the content filters.', 'wpematico'),
				'tip'		 => __('NOTE: It is extremely dangerous to allow unfiltered content because there may be some vulnerability in the source code.', 'wpematico') . '<br>' .
				__('See How WordPress Processes Post Content: ', 'wpematico') . '<a href="http://codex.wordpress.org/How_WordPress_Processes_Post_Content" target="_blank">http://codex.wordpress.org/How_WordPress_Processes_Post_Content</a>',
				'plustip'	 => __('After Wordpress inserted the post, this option will make an update query to database with the content of the post to avoid Wordpress filters.', 'wpematico') . "<br />" .
				__('Use only with reliable sources.', 'wpematico'),
			),
			'campaign_timeout'						 => array(
				'title'	 => __('Allow option on campaign to skip the content filters.', 'wpematico'),
				'tip'	 => __('When a campaign is running and is interrupted by some issue, it cannot be executed again until click "Clear Campaign".', 'wpematico') . '<br>' .
				__('This option clear campaign after this timeout then can run again on next scheduled cron. A value of "0" ignore this, means that remain until user make click. ', 'wpematico') . "<br />" .
				__('Recommended 300 Seconds. ', 'wpematico'),
			// 'plustip' => __('', 'wpematico' ),
			),
			'throttle'								 => array(
				'title'	 => __('Add a throttle/delay in seconds after every post.', 'wpematico'),
				'tip'	 => __('This option make a delay after every action of insert a post.  May be useful if you want to give a break to the server while is fetching many posts.  Leave on 0 if you don\'t have any problem.', 'wpematico'),
			// 'plustip' => __('', 'wpematico' ),
			),
			'allowduplicates'						 => array(
				'title'		 => __('Deactivate duplicate controls.', 'wpematico'),
				'tip'		 => __('When the running campaign found a duplicated post the process is interrupted because assume that all followed posts, are also duplicates.  You can disable these controls here.', 'wpematico') . '<br>' .
				__('Duplicates checking by hash is a boost to checking for duplicates by title, which may fail many times.', 'wpematico'),
				'plustip'	 => '&nbsp;&nbsp;&nbsp;&nbsp;<b>' . __('Allowing duplicated posts', 'wpematico') . ':</b> ' . __("There are two controls for duplicates, title of the post and a hash generated by last item's url obtained on campaign process.", 'wpematico') . '<br>' .
				__('NOTE: If disable both controls, all items will be fetched again and again... and again, ad infinitum.  If you want allow duplicated titles, just activate "Allow duplicated titles".', 'wpematico'),
			),
			'jumpduplicates'						 => array(
				'title'		 => __('Continue Fetching if found duplicated items.', 'wpematico'),
				'tip'		 => __('Unless it is the first time, when finds a duplicate, it means that all following items were read before. This option avoids and allows jump every duplicate and continues reading the feed searching more new items. NOT RECOMMENDED.', 'wpematico'),
				'plustip'	 => '&nbsp;&nbsp;&nbsp;&nbsp;<b>' . __('How it works:', 'wpematico') . ':</b> ' . __('The feed items are ordered by datetime in almost all cases. When the campaign runs, goes item by item from newest to oldest, and stops when found the first duplicated item, this mean that all items following (the old ones) are also duplicated.', 'wpematico') . '<br>' .
				__('As the hash is checked only by the last retrieved item, selecting this option may generate duplicate posts if duplicate checking by title does not work well for a campaign.', 'wpematico'),
			),
			'disableccf'							 => array(
				'title'	 => __('Disable plugin custom fields.', 'wpematico'),
				'tip'	 => __('This option nulls saving custom fields on every post that campaign publishes.', 'wpematico') . '<br>'
				. __('By default the plugin saves three custom fields on every post with campaign and source item data.', 'wpematico') . '<br>'
				. __('Necessary for use permalink to source feature, identify which campaign fetch the post or to make any bulk action on post types related with original campaign.', 'wpematico') . '<br>'
				. __('Not recommended unless you want to loose this data and features in order to save DB space.', 'wpematico') . '<br>'
				. __('(Enabling this feature don\'t deletes the previous saved data.)', 'wpematico'),
			// 'plustip' => __('', 'wpematico' ),
			),
			'add_extra_duplicate_filter_meta_source' => array(
				'title'	 => __('Add an extra duplicate filter by source permalink in meta field value.', 'wpematico'),
				'tip'	 => __('This option is ONLY recommended if you continues with duplicates problems in your site. This can be given by some non-standards feeds.  NOT RECOMMENDED.', 'wpematico'),
			),
		),
		'Cron and Scheduler' => array(
			'dontruncron'				 => array(
				'title'	 => __('Disable WPeMatico schedulings.', 'wpematico'),
				'tip'	 => __('This option deactivate WPeMatico plugin cron schedules.', 'wpematico') . '<br>' .
				__('Affects all campaigns. To run campaigns you must do it manually or with external cron. (Recommended with External Cron).', 'wpematico'),
			// 'plustip' => __('', 'wpematico' ),
			),
			'set_cron_code'				 => array(
				'title'		 => __('Set a password to access the external CRON.', 'wpematico'),
				'tip'		 => __('Activate a code to allow or avoid the use of the external cron file.  Deactivated by default to backward compatibility, but strongly recommended.', 'wpematico'),
				'plustip'	 => __('If this field is not checked the password will be ignored.', 'wpematico'),
			),
			'cron_code'					 => array(
				'title'	 => __('Set a password to use the external CRON.', 'wpematico'),
				'tip'	 => __('This will be the code used in the command to run the cron.  Can be any string you want to use as ?code=this_code.  Recommended.', 'wpematico'),
			// 'plustip' => __('', 'wpematico' ),
			),
			'disablewpcron'				 => array(
				'title'	 => __('Disable all WP_Cron.', 'wpematico'),
				'tip'	 => __('Check this to deactivate all Wordpress cron schedules. Affects to Wordpress itself and all other plugins.  Not recommended unless you want to use an external Cron for your wordpress.', 'wpematico'),
			// 'plustip' => __('', 'wpematico' ),
			),
			'enable_alternate_wp_cron'	 => array(
				'title'	 => __('Use ALTERNATE_WP_CRON.', 'wpematico'),
				'tip'	 => __('Some servers disable the functionality that enables WordPress Cron to work properly. This constant provides an easy fix that should work on any server.', 'wpematico'),
			// 'plustip' => __('', 'wpematico' ),
			),
			'logexternalcron'			 => array(
				'title'	 => __('Log file for external Cron.', 'wpematico'),
				'tip'	 => __('Try to save a file with simple steps taken at run ', 'wpematico') . 'wpe-cron.php. "%campaign title%.txt.log"' . __('will be saved on uploads folder or inside plugin, "app" folder.  Recommended on issues with cron.', 'wpematico'),
			// 'plustip' => __('', 'wpematico' ),
			),
		),
		'WP Backend Tools'	 => array(
			'campaign_in_postslist'				 => array(
				'title'	 => __('Enables the campaign ID to be displayed in the posts (types) lists.', 'wpematico'),
				'tip'	 => __('This option enables WordPres to display a new column in the post lists (types) with the campaign ID that published each post.', 'wpematico'),
			),
			'column_campaign_pos'				 => array(
				'title'	 => __('Column position in Posts(-types) lists.', 'wpematico'),
				'tip'	 => __('This option allow to choose the column position in the post lists.', 'wpematico'),
			),
			'disable_metaboxes_wpematico_posts'	 => array(
				'title'	 => __('Disable metabox Wpematico Campaign Info in post editing.', 'wpematico'),
				'tip'	 => __('This option disables the metabox inside the posts editing screen created by the WPeMatico.', 'wpematico'),
			),
			'emptytrashbutton'					 => array(
				'title'	 => __('Shows Button to empty trash on lists.', 'wpematico'),
				'tip'	 => __('Just an extra tool to display a button for empty trash folder on every custom post main screen. May be posts, pages or selects what you want.', 'wpematico'),
			),
			'disabledashboard'					 => array(
				'title'	 => __('Disable WP Dashboard Widget', 'wpematico'),
				'tip'	 => __('Check this if you don\'t want to display the widget dashboard.  Anyway, only admins will see it.', 'wpematico'),
			// 'plustip' => __('', 'wpematico' ),
			),
		),
		'Sidebar Advanced'	 => array(
			'disablecheckfeeds'				 => array(
				'title'	 => __('Disable Check Feeds before Save.', 'wpematico'),
				'tip'	 => __('Check this if you don\'t want automatic check feed URLs before save every campaign.', 'wpematico'),
			),
			'enabledelhash'					 => array(
				'title'	 => __('Enable Del Hash.', 'wpematico'),
				'tip'	 => __('Show `Del Hash` link on campaigns list.  This link delete all hash codes for check duplicates on every feed per campaign.', 'wpematico'),
			),
			'enableseelog'					 => array(
				'title'	 => __('Enable See last log.', 'wpematico'),
				'tip'	 => __('Show `See Log` link on campaigns list.  This link show the last processed log of every campaign.', 'wpematico'),
			),
			'disable_credits'				 => array(
				'title'		 => __('Disable WPeMatico Credits.', 'wpematico'),
				'tip'		 => __('I really appreciate if you can left this option blank to show the plugin\'s credits.', 'wpematico'),
				'plustip'	 => sprintf(__('If you can\'t show the WPeMatico credits in your posts, I really appreciate if you can take a minute to %s write a 5 star review on Wordpress %s.  :-) thanks.', 'wpematico'),
					'<a href="https://wordpress.org/support/view/plugin-reviews/wpematico?filter=5&rate=5#new-post" target="_Blank" title="Open a new window">',
					'</a>'),
			),
			'disable_categories_description' => array(
				'title'	 => __('Disable Auto-Category Descriptions.', 'wpematico'),
				'tip'	 => __('Check this to avoid the future descriptions "Created by WPeMatico" of the created (auto)categories.  You could edit the past categories if you need to delete their descriptions.', 'wpematico'),
			),
			'disable_extensions_feed_page'	 => array(
				'title'		 => __('Disable Extensions feed page requests.', 'wpematico'),
				'tip'		 => __('Check this to avoid the fetch of WPeMatico Addons from our downloads feed page.', 'wpematico'),
				'plustip'	 => __('You\'ll see an empty page or just your installed extensions when you click on Extensions menu.', 'wpematico'),
			),
			'enable_xml_upload'				 => array(
				'title'	 => __('Enable Upload of XMLs.', 'wpematico'),
				'tip'	 => __('If you want upload XML files to use them in the XML Campaign Type. Enable this feature allow the upload XMLs from media.', 'wpematico'),
			),
			'entity_decode_html'			 => array(
				'title'	 => __('Enable html entity decode on publish.', 'wpematico'),
				'tip'	 => __('Enable this feature if you need to decode HTML entities in feed content before publishing each post.', 'wpematico'),
			),
		),
		'Sending e-Mails'	 => array(
			'sendmail'	 => array(
				'title'	 => __('Sender Email.', 'wpematico'),
				'tip'	 => __('Email address used as "FROM" field in all emails sent by this plugin.', 'wpematico'),
			),
			'namemail'	 => array(
				'title'	 => __('Sender Name.', 'wpematico'),
				'tip'	 => __('The Name that will show in your inbox related to previous email address for all emails sent by this plugin.', 'wpematico'),
			),
		)
	);
	$helpsettings	 = apply_filters('wpematico_help_settings_before', $helpsettings);
	if($dev == 'tips') {
		foreach($helpsettings as $key => $section) {
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
		return apply_filters('wpematico_helptip_settings', $helptip);
	}
	return apply_filters('wpematico_help_settings', $helpsettings);
}
