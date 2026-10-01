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
 *
 * One entry per setting: 'title' names it, 'tip' says in a sentence or two what it
 * does -- it is printed as the description under the field, as the (?) tooltip and
 * at the top of the Help tab -- and 'plustip' carries the detail only the Help tab
 * shows. Anything longer than two sentences belongs in 'plustip'.
 */
function wpematico_helpsettings($dev = '') {
	$helpsettings	 = array(
		'Global Settings'	 => array(
			'imgoptions'	 => array(
				'title'	 => __('Global settings for images', 'wpematico'),
				'tip'	 => __('These apply to every campaign, and each campaign can override them.', 'wpematico'),
			),
			'imgcache'		 => array(
				'title'	 => __('Store images locally', 'wpematico'),
				/* translators: %s <img> html tag. */
				'tip'	 => sprintf(__('A copy of every image found in the feed item (only in %s tags) is saved to your Uploads folder. Off, images stay on the source server.', 'wpematico'), '&lt;img&gt;'),
				'plustip' => __('Linking to the source server saves space and makes your pages faster, but you lose the image the day the source removes it. Each campaign can set this on its own.', 'wpematico'),
			),
			'imgattach'		 => array(
				'title'		 => __('Attach images to posts', 'wpematico'),
				'tip'		 => __('Every downloaded image is added to the Media library and attached to its post. Needed for the featured image.', 'wpematico'),
				'plustip'	 => __('Turn it off if the run is too slow. It has no effect when custom upload is on.', 'wpematico'),
			),
			'gralnolinkimg'	 => array(
				'title'		 => __('Remove link to source images', 'wpematico'),
				/* translators: %s <img> html tag. */
				'tip'		 => sprintf(__('When an image cannot be downloaded, its %s tag is removed from the content instead of pointing at the source site.', 'wpematico'), '&lt;img&gt;'),
				/* translators: %s <a> html tag. */
				'plustip'	 => sprintf(__('If the image sits inside an %s tag, that link is removed as well.', 'wpematico'), '&lt;a&gt;'),
			),
			'image_srcset'	 => array(
				/* translators: %s <img> html tag. */
				'title'		 => sprintf(__('Read the srcset attribute of the %s tag', 'wpematico'), '&lt;img&gt;'),
				/* translators: %s <img> html tag. */
				'tip'		 => sprintf(__('Takes the largest image listed in srcset and uses it as the src of the %s tag.', 'wpematico'), '&lt;img&gt;'),
				'plustip'	 => __('Images with no srcset attribute are processed as usual.', 'wpematico'),
			),
			'featuredimg'	 => array(
				'title'		 => __('Set the first image as featured', 'wpematico'),
				'tip'		 => __('The first image found in the content is downloaded, attached and set as the featured image.', 'wpematico'),
				'plustip'	 => '<small> ' . __('Read about', 'wpematico') . ' <a href="https://developer.wordpress.org/advanced-administration/wordpress/post-thumbnail/" target="_Blank">' . __('Post Thumbnails', 'wpematico') . '</a></small>',
			),
			'fifu'	 => array(
				'title'		 => __('Use Featured Image from URL', 'wpematico'),
				'tip'		 => __('Hands the featured image to the Featured Image from URL plugin, which serves it from the source server. Install and activate that plugin first.', 'wpematico'),
				'plustip'	 => '<small> ' . __('Read about', 'wpematico') . ' <a href="https://wordpress.org/plugins/featured-image-from-url/" rel="nofollow" target="_Blank">' . __('Featured Image from URL', 'wpematico') . '</a> ' . __('plugin in WordPress repository.','wpematico') . '</small><br />' .
					__('With that plugin inactive, WPeMatico still writes its meta fields and nothing else changes.', 'wpematico'),
			),
			'rmfeaturedimg'	 => array(
				'title'		 => __('Remove the featured image from the content', 'wpematico'),
				'tip'		 => __('The image used as featured is stripped from the post content.', 'wpematico'),
				'plustip'	 => __('Useful when your theme already prints the featured image and you would otherwise see it twice.', 'wpematico'),
			),
			'customupload'	 => array(
				'title'		 => __('Use custom upload for images', 'wpematico'),
				'tip'		 => __('Saves the image exactly as it comes from the source, skipping WordPress. Faster, but it does not work on every server.', 'wpematico'),
				'plustip'	 => __('Turn it off if images stop appearing, or if you need WordPress to generate all its image sizes. Generating them costs a lot of resources when many images arrive at once.', 'wpematico'),
			),
			'enablemimetypes'	=> array(
				'title' => __('Allow more file types', 'wpematico' ),
				'tip' => __('Uploads files whose type the Media library does not accept by default. It does not work on every server.', 'wpematico' ),
			),
			'save_attr_images'	=> array(
				'title' => __('Save image attributes on WP Media', 'wpematico' ),
				'tip' => __('Keeps the caption, alt text and title that came with the image in the Media library.', 'wpematico' ),
				'plustip' => __('It adds one query per image, so turn it off if the run is too slow. It does not work on every server.', 'wpematico'),
			),
		),
		'Audio Settings'	 => array(
			'audio_options'			 => array(
				'title'	 => __('Global settings for audios', 'wpematico'),
				'tip'	 => __('These apply to every campaign, and each campaign can override them.', 'wpematico'),
			),
			'audio_cache'			 => array(
				'title'	 => __('Store audios locally', 'wpematico'),
				/* translators: %s <audio> html tag. */
				'tip'	 => sprintf(__('A copy of every audio found in the feed item (only in %s tags) is saved to your Uploads folder. Off, audios stay on the source server.', 'wpematico'), '&lt;audio&gt;'),
				'plustip' => __('Linking to the source server saves space, but you lose the file the day the source removes it. Each campaign can set this on its own.', 'wpematico'),
			),
			'audio_attach'			 => array(
				'title'		 => __('Attach audios to posts', 'wpematico'),
				'tip'		 => __('Every downloaded audio is added to the Media library and attached to its post.', 'wpematico'),
				'plustip'	 => __('Turn it off if the run is too slow. It has no effect when custom upload is on.', 'wpematico'),
			),
			'gralnolink_audio'		 => array(
				'title'		 => __('Remove link to source audios', 'wpematico'),
				/* translators: %s <audio> html tag. */
				'tip'		 => sprintf(__('When an audio cannot be downloaded, its %s tag is removed from the content instead of pointing at the source site.', 'wpematico'), '&lt;audio&gt;'),
				/* translators: %s <a> html tag. */
				'plustip'	 => sprintf(__('If the audio sits inside an %s tag, that link is removed as well.', 'wpematico'), '&lt;a&gt;'),
			),
			'customupload_audios'	 => array(
				'title'		 => __('Use custom upload for audios', 'wpematico'),
				'tip'		 => __('Saves the audio exactly as it comes from the source, skipping WordPress. Faster, but it does not work on every server.', 'wpematico'),
				'plustip'	 => __('Turn it off if the audios stop playing.', 'wpematico'),
			),
		),
		'Video Settings'	 => array(
			'video_options'			 => array(
				'title'	 => __('Global settings for videos', 'wpematico'),
				'tip'	 => __('These apply to every campaign, and each campaign can override them.', 'wpematico'),
			),
			'video_cache'			 => array(
				'title'	 => __('Store videos locally', 'wpematico'),
				/* translators: %s <video> html tag. */
				'tip'	 => sprintf(__('A copy of every video found in the feed item (only in %s tags) is saved to your Uploads folder. Off, videos stay on the source server.', 'wpematico'), '&lt;video&gt;'),
				'plustip' => __('Video files are heavy: storing them locally fills your disk fast. Each campaign can set this on its own.', 'wpematico'),
			),
			'video_attach'			 => array(
				'title'		 => __('Attach videos to posts', 'wpematico'),
				'tip'		 => __('Every downloaded video is added to the Media library and attached to its post.', 'wpematico'),
				'plustip'	 => __('Turn it off if the run is too slow. It has no effect when custom upload is on.', 'wpematico'),
			),
			'gralnolink_video'		 => array(
				'title'		 => __('Remove link to source videos', 'wpematico'),
				/* translators: %s <video> html tag. */
				'tip'		 => sprintf(__('When a video cannot be downloaded, its %s tag is removed from the content instead of pointing at the source site.', 'wpematico'), '&lt;video&gt;'),
				/* translators: %s <a> html tag. */
				'plustip'	 => sprintf(__('If the video sits inside an %s tag, that link is removed as well.', 'wpematico'), '&lt;a&gt;'),
			),
			'customupload_videos'	 => array(
				'title'		 => __('Use custom upload for videos', 'wpematico'),
				'tip'		 => __('Saves the video exactly as it comes from the source, skipping WordPress. Faster, but it does not work on every server.', 'wpematico'),
				'plustip'	 => __('Turn it off if the videos stop playing.', 'wpematico'),
			),
		),
		'Enable Features'	 => array(
			'enablefeatures'	 => array(
				'title'	 => __('Enable features', 'wpematico'),
				'tip'	 => __('Each one adds its own box to every campaign. Leave off what you do not use.', 'wpematico'),
			),
			'enableword2cats'	 => array(
				'title'	 => __('Words to Categories', 'wpematico'),
				'tip'	 => __('Assigns a category to the post when a given word appears in the content.', 'wpematico'),
			),
			'enablerewrite'		 => array(
				'title'	 => __('Rewrite', 'wpematico'),
				'tip'	 => __('Replaces a word or phrase with another one in the content of every post.', 'wpematico'),
			),
			'enable_vimeo'		 => array(
				'title'	 => __('Vimeo campaign type', 'wpematico'),
				'tip'	 => __('Adds Vimeo to the campaign type list, to publish the videos of a Vimeo user, channel or group. Paste the address and the campaign finds its feed on its own.', 'wpematico'),
				'plustip' => __('Vimeo publishes the last 10 videos of each one. Leave this off if you do not use Vimeo: the campaign type is not loaded at all.', 'wpematico'),
			),
			'wpematico_set_canonical'	=> array(
				'title' => __('Canonical URL to the source', 'wpematico' ),
				'tip' => __('Each imported post declares the original article as its canonical URL. It does not work on every server.', 'wpematico' ),
				'plustip' => __('This tells search engines which copy of the article is the one to index, so your site is not penalised for duplicated content.', 'wpematico'),
			),
		),
		'SimplePie Settings' => array(
			'mysimplepie'	 => array(
				'title'	 => __('Force the bundled SimplePie library', 'wpematico'),
				'tip'	 => __('Ignores the SimplePie that ships with WordPress. You only need this if that version gives you trouble.', 'wpematico'),
			),
			'stupidly_fast'	 => array(
				'title'		 => __('Set SimplePie "stupidly fast"', 'wpematico'),
				'tip'		 => __('SimplePie gives up most of its sanitizing in exchange for speed: the feed content arrives with no parsers and no filters.', 'wpematico'),
				'plustip'	 => __('Nothing is stripped from the content: all HTML, styles and scripts come through. Use it only with feeds you trust; otherwise set the allowed tags and attributes below.', 'wpematico'),
			),
			'strip_htmltags' => array(
				'title'	 => __('HTML tags SimplePie strips', 'wpematico'),
				'tip'	 => __('SimplePie removes these tags from the feed content. Take one out to let it through, for example iframe to keep embedded videos.', 'wpematico'),
			),
			'strip_htmlattr' => array(
				'title'	 => __('HTML attributes SimplePie strips', 'wpematico'),
				'tip'	 => __('SimplePie removes these attributes from the tags in the content. Take one out to keep it, or add more to strip.', 'wpematico'),
			),
		),
		'Advanced Fetching'	 => array(
			'woutfilter'							 => array(
				'title'		 => __('Let campaigns skip the content filters', 'wpematico'),
				'tip'		 => __('Adds a switch to every campaign that stores the feed content exactly as it comes, scripts included. Use it only with sources you trust.', 'wpematico'),
				'plustip'	 => __('WordPress inserts the post as usual and the content is then written straight to the database, past every filter.', 'wpematico') . ' ' .
				__('See How WordPress Processes Post Content: ', 'wpematico') . '<a href="https://developer.wordpress.org/apis/hooks/filter-reference/#post-content" target="_blank">developer.wordpress.org</a>',
			),
			'campaign_timeout'						 => array(
				'title'	 => __('Timeout for a running campaign', 'wpematico'),
				'tip'	 => __('A campaign interrupted halfway stays marked as running until this many seconds have passed; then it runs again on its next schedule. Recommended: 300.', 'wpematico'),
				'plustip'	 => __('Set 0 to keep it blocked until you click "Clear campaign" yourself.', 'wpematico'),
			),
			'throttle'								 => array(
				'title'	 => __('Pause after every post', 'wpematico'),
				'tip'	 => __('Waits this many seconds after inserting each post, to give the server a break on long runs. Leave 0 unless you have trouble.', 'wpematico'),
			),
			'allowduplicates'						 => array(
				'title'		 => __('Allow duplicate posts', 'wpematico'),
				'tip'		 => __('Turns off both duplicate checks, so every item is published again on every run. Almost never what you want.', 'wpematico'),
				'plustip'	 => '&nbsp;&nbsp;&nbsp;&nbsp;<b>' . __('The two checks', 'wpematico') . ':</b> ' . __('the post title, and a hash of the last item address the campaign read. The hash is the reliable one; titles alone fail often.', 'wpematico') . '<br>' .
				__('To publish items that share a title, use "Allow duplicate titles" instead and leave the hash check on.', 'wpematico'),
			),
			'jumpduplicates'						 => array(
				'title'		 => __('Keep reading past a duplicate', 'wpematico'),
				'tip'		 => __('The campaign skips each duplicate and keeps looking for new items, instead of stopping at the first one. Not recommended.', 'wpematico'),
				'plustip'	 => '&nbsp;&nbsp;&nbsp;&nbsp;<b>' . __('How it works', 'wpematico') . ':</b> ' . __('feed items come ordered newest first, so the campaign reads from the top and stops at the first item it already has: everything below it is older and already published.', 'wpematico') . '<br>' .
				__('The hash only covers the last item read, so on a feed where the title check works poorly this option can publish the same item twice.', 'wpematico'),
			),
			'disableccf'							 => array(
				'title'	 => __('Do not save the plugin custom fields', 'wpematico'),
				'tip'	 => __('Saves database space and costs you the source permalink, the campaign each post came from and every bulk action based on it.', 'wpematico'),
				'plustip' => __('By default each published post carries three custom fields with the campaign and the source item. Turning this on does not delete the ones already saved.', 'wpematico'),
			),
			'add_extra_duplicate_filter_meta_source' => array(
				'title'	 => __('Also check duplicates by source permalink', 'wpematico'),
				'tip'	 => __('One more query per item. Turn it on only if you still get duplicates, which happens with feeds that do not follow the standard.', 'wpematico'),
			),
		),
		'Cron and Scheduler' => array(
			'dontruncron'				 => array(
				'title'	 => __('Disable WPeMatico schedules', 'wpematico'),
				'tip'	 => __('No campaign runs on its own any more. You run them by hand or from an external cron, which is the recommended setup.', 'wpematico'),
			),
			'set_cron_code'				 => array(
				'title'		 => __('Ask for a password on the external cron', 'wpematico'),
				'tip'		 => __('Only a request carrying the code runs your campaigns. Off by default for backward compatibility, and strongly recommended.', 'wpematico'),
				'plustip'	 => __('While this is off, the password below is ignored.', 'wpematico'),
			),
			'cron_code'					 => array(
				'title'	 => __('External cron password', 'wpematico'),
				'tip'	 => __('Any text you like. It travels in the cron address as ?code=your_code.', 'wpematico'),
			),
			'disablewpcron'				 => array(
				'title'	 => __('Disable all WP_Cron', 'wpematico'),
				'tip'	 => __('Stops every scheduled task on the site, WordPress own and other plugins included. Only do this if an external cron takes over.', 'wpematico'),
			),
			'enable_alternate_wp_cron'	 => array(
				'title'	 => __('Use ALTERNATE_WP_CRON', 'wpematico'),
				'tip'	 => __('Some servers block the way WordPress triggers its schedules. This constant works around it on almost any server.', 'wpematico'),
			),
			'logexternalcron'			 => array(
				'title'	 => __('Log file for the external cron', 'wpematico'),
				'tip'	 => __('Writes a "campaign title.txt.log" file in the uploads folder with the steps of each external cron run. Useful while you set the cron up.', 'wpematico'),
			),
		),
		'WP Backend Tools'	 => array(
			'campaign_in_postslist'				 => array(
				'title'	 => __('Campaign column in the posts lists', 'wpematico'),
				'tip'	 => __('Adds a column showing which campaign published each post.', 'wpematico'),
			),
			'column_campaign_pos'				 => array(
				'title'	 => __('Position of that column', 'wpematico'),
				'tip'	 => __('Where the campaign column sits among the others.', 'wpematico'),
			),
			'disable_metaboxes_wpematico_posts'	 => array(
				'title'	 => __('Hide the campaign box in the post editor', 'wpematico'),
				'tip'	 => __('Removes the WPeMatico box that shows where an imported post came from.', 'wpematico'),
			),
			'emptytrashbutton'					 => array(
				'title'	 => __('Empty trash button on the lists', 'wpematico'),
				'tip'	 => __('Adds a button to empty the trash on the post types you choose.', 'wpematico'),
			),
			'disabledashboard'					 => array(
				'title'	 => __('Hide the WP dashboard widget', 'wpematico'),
				'tip'	 => __('Removes the WPeMatico widget from the WordPress dashboard.', 'wpematico'),
			),
		),
		'Sidebar Advanced'	 => array(
			'disablecheckfeeds'				 => array(
				'title'	 => __('Do not check feeds before saving', 'wpematico'),
				'tip'	 => __('Saving a campaign no longer tests each feed address first. The campaign saves faster and a broken address goes unnoticed until it runs.', 'wpematico'),
			),
			'enabledelhash'					 => array(
				'title'	 => __('Enable "Del hash"', 'wpematico'),
				'tip'	 => __('Adds a link on the campaigns list that clears the duplicate hashes of every feed in a campaign, so its items can be published again.', 'wpematico'),
			),
			'enableseelog'					 => array(
				'title'	 => __('Enable "See last log"', 'wpematico'),
				'tip'	 => __('Adds a link on the campaigns list that opens the log of the last run.', 'wpematico'),
			),
			'disable_credits'				 => array(
				'title'		 => __('Hide the WPeMatico credits', 'wpematico'),
				'tip'		 => __('I would really appreciate you leaving the credits on.', 'wpematico'),
				/* translators: %1$s open anchor link. %2$s close anchor */
				'plustip'	 => sprintf(__('If you cannot show them, a minute of your time to %1$s write a 5 star review on WordPress %2$s means just as much. :-) Thanks.', 'wpematico'),
					'<a href="https://wordpress.org/support/view/plugin-reviews/wpematico?filter=5&rate=5#new-post" target="_Blank" title="Open a new window">',
					'</a>'),
			),
			'disable_categories_description' => array(
				'title'	 => __('No description on auto categories', 'wpematico'),
				'tip'	 => __('Categories created from now on are left without the "Created by WPeMatico" description. The ones already created keep theirs until you edit them.', 'wpematico'),
			),
			'disable_extensions_feed_page'	 => array(
				'title'		 => __('Do not load the Extensions page', 'wpematico'),
				'tip'		 => __('The Extensions menu stops asking our site for the add-on catalogue.', 'wpematico'),
				'plustip'	 => __('You will see an empty page, or only the extensions you already have installed.', 'wpematico'),
			),
			'enable_xml_upload'				 => array(
				'title'	 => __('Enable XML uploads', 'wpematico'),
				'tip'	 => __('Lets you upload XML files to the Media library, to use them in an XML campaign.', 'wpematico'),
			),
			'entity_decode_html'			 => array(
				'title'	 => __('Decode HTML entities on publish', 'wpematico'),
				'tip'	 => __('Turns entities such as &amp;amp; back into their character before the post is saved. Useful with feeds that encode the content twice.', 'wpematico'),
			),
		),
		'Sending e-Mails'	 => array(
			'sendmail'	 => array(
				'title'	 => __('Sender email', 'wpematico'),
				'tip'	 => __('The address every email from this plugin is sent from.', 'wpematico'),
			),
			'namemail'	 => array(
				'title'	 => __('Sender name', 'wpematico'),
				'tip'	 => __('The name shown next to that address in your inbox.', 'wpematico'),
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
			'PROfeatures'		 => __('Features available with the Professional add-on.', 'wpematico'),
			'enablekwordf'		 => __('Publish or skip an item depending on the keywords found in its title or content.', 'wpematico'),
			'enablewcf'			 => __('Cut, publish or skip an item depending on how many words or letters its content has.', 'wpematico'),
			'enablecustomtitle'	 => __('Build the title of every post of a campaign from a template of your own.', 'wpematico'),
			'enabletags'		 => __('Generates the tags of every post automatically. Each campaign can turn that off and use a list of tags you write instead.', 'wpematico'),
			'enablecfields'		 => __('Add custom fields, with template tags as their value, to every post.', 'wpematico'),
			'fullcontent'		 => __('Full Content is the add-on that fetches the whole article from the source site when the feed only carries a summary.', 'wpematico'),
			'authorfeed'		 => __('Set an author per feed when editing a campaign. Feeds with no author of their own use the campaign author.', 'wpematico'),
			'importfeeds'		 => __('Paste a list of feed addresses, with or without author names, straight into the campaign.', 'wpematico'),
			)
		);
		return apply_filters('wpematico_helptip_settings', $helptip);
	}
	return apply_filters('wpematico_help_settings', $helpsettings);
}
