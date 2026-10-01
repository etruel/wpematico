<?php
// don't load directly
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * Help contents of the campaign edit screen.
 *
 * One entry per setting: 'title' names it, 'tip' is what the (?) shows on hover --
 * a sentence or two, because it floats over the form -- and 'plustip' carries the
 * detail, which only the Help tab prints.
 */
$helpcampaign = array(
	'Campaign Options' => array(
		'feeds' => array(
			'title' => __('Feed addresses', 'wpematico' ),
			'tip' => __('At least one address is needed. Type a domain name and WPeMatico looks for its feed. The fewer feeds a campaign has, the less each run costs.', 'wpematico' ),
		),
		'itemfetch' => array(
			'title' => __('Max items per fetch', 'wpematico' ),
			'tip' => __('How many items to take from each feed above. Between 3 and 5, running more often, loses fewer items than a big number run rarely. Use 0 for no limit.', 'wpematico' ),
		),
		'feed_order_date' => array(
			'title' => __('Order the items by date first', 'wpematico' ),
			'tip' => __('Only for feeds that do not come newest first. On those, the campaign would otherwise stop early and lose items.', 'wpematico' ),
		),
		'itemdate' => array(
			'title' => __('Use the feed item date', 'wpematico' ),
			'tip' => __('Each post keeps the date it has in the feed, instead of the moment WPeMatico published it.', 'wpematico' ),
			'plustip' => __('Badly built feeds carry impossible dates, so the feed date is used only when:', 'wpematico' ).
				'<ul style=\'list-style-type: square;margin:0 0 5px 20px;font:0.92em "Lucida Grande","Verdana";\'>
				<li>'. __('it is not further in the past than the campaign frequency, and', 'wpematico' ).' </li>
				<li>'. __('it is not in the future.', 'wpematico' ).' </li></ul>',
		),
		'campaign_feeddate_forced' => array(
			'title' => __('Force the item date', 'wpematico' ),
			'tip' => __('Takes the feed date whatever it says, including old items arriving after newer ones.', 'wpematico' ),
		),
		'linktosource' => array(
			'title' => __('Post title links to the source', 'wpematico' ),
			'tip' => __('The title of each post points at the original article instead of at your own post.', 'wpematico' ),
			'plustip' => __('It needs the plugin custom fields, so it does nothing while those are turned off in Settings.', 'wpematico' ),
		),
		'copy_permanlink_source' => array(
			'title' => __('Copy the permalink from the source', 'wpematico' ),
			'tip' => __('Your post keeps the exact permalink the source article has.', 'wpematico' ),
		),
		'avoid_search_redirection' => array(
			'title' => __('Do not follow redirects to find the permalink', 'wpematico' ),
			'tip' => __('The address in the feed is taken as it comes, without following its redirects to the final article.', 'wpematico' ),
			'plustip' => __('Leave it on for speed. Turn it off when the permalinks you get are not the original ones.', 'wpematico' ),
		),
		'allowpings' => array(
			'title' => __('Pingbacks and trackbacks', 'wpematico' ),
			'tip' => __('Allows pingbacks and trackbacks on the posts this campaign publishes.', 'wpematico' ),
		),
		'convert_utf8' => array(
			'title' => __('Convert the encoding to UTF-8', 'wpematico' ),
			'tip' => __('Title and content are converted to UTF-8 when the feed sends them in another encoding. Fixes feeds that arrive full of strange characters.', 'wpematico' ),
		),
		'commentstatus' => array(
			'title' => __('Comments', 'wpematico' ),
			'tip' => __('Whether the posts of this campaign accept comments.', 'wpematico' ),
		),
		'postsauthor' => array(
			'title' => __('Author', 'wpematico' ),
			'tip' => __('The author every post of this campaign is published under.', 'wpematico' ),
			'plustip' => __('The author and the post status are saved as your own user is entitled to set them, because the campaign runs later on its schedule, when nobody is logged in. A user who cannot publish posts saves the campaign with the status Pending review, and a user who cannot assign posts to other users saves it under their own name. The campaign screen reports it right after saving, so the value you see is always the one that will be used. Administrators and editors are not affected.', 'wpematico' ),
		),
		'get_excerpt' => array(
			'title' => __('Fill the excerpt from the feed', 'wpematico' ),
			'tip' => __('The description field of the item is saved as the post excerpt. Otherwise WordPress builds the excerpt from the content.', 'wpematico' ),
			'plustip' => __('Feeds with no description of their own send the content in that field, so you may end up with the same text twice.', 'wpematico' ),
		),
		'striphtml' => array(
			'title' => __('Strip all HTML tags', 'wpematico' ),
			/* translators: %1$s and %2$s html tags <img> and <a>. */
			'tip' => sprintf(__('The content is saved as plain text. Images %1$s and links %2$s go with it.', 'wpematico' ), '&lt;img&gt;', '&lt;a&gt;'),
		),
		'striplinks' => array(
			'title' => __('Strip links from the content', 'wpematico' ),
			'tip' => __('Links stop being clickable; their text stays in place.', 'wpematico' ),
		),
		'woutfilter' => array(
				'title' => __('Store the content unfiltered', 'wpematico' ),
				'tip' => __('The feed content is saved exactly as it arrives, past the WordPress filters and with its scripts. Use it only with sources you trust.', 'wpematico' ),
		),
	),
	'Youtube Feeds' => array(
		'feed_url' => array(
			'title' => __('YouTube feed addresses', 'wpematico' ),
			'tip' => __('Type the feed address of a channel, a user or a playlist. The campaign takes the title, the image, the embedded video and the description.', 'wpematico' ),
			'plustip' => __('For a YouTube channel: ', 'wpematico' ) . 'https://www.youtube.com/feeds/videos.xml?channel_id=%channelid%'.
				'<br>'.__('For a YouTube user: ', 'wpematico' ) . 'https://www.youtube.com/feeds/videos.xml?user=%username%'.
				'<br>'.__('For a YouTube playlist: ', 'wpematico' ) . 'https://www.youtube.com/feeds/videos.xml?playlist_id=%playlist_id%',
		),
		'youtube_embed' => array(
			'title' => __('Use [embed]', 'wpematico' ),
			'tip' => __('Wraps the video in the WordPress [embed] shortcode instead of the iframe YouTube shares.', 'wpematico' ),
			'plustip' => __('WordPress then fits the player to the width your theme gives it, up to the size you set.', 'wpematico' ),
		),
		'youtube_sizes' => array(
			'title' => __('Video size', 'wpematico' ),
			'tip' => __('Width and height of the player in the post. Leave both at 0 to let it adapt to the screen.', 'wpematico' ),
		),
	),
	'bbPress Forums' => array(
		'bbpress' => array(
			'title' => __('How the bbPress campaign type works', 'wpematico' ),
			'tip' => __('Pick a forum to publish the feed items as topics inside it, or pick none and each item creates its own forum.', 'wpematico' ),
			'plustip' => __('Picking a topic instead publishes every item as a reply to it.', 'wpematico' ),
		),
	),
	'Schedule Options' => array(
		'schedule' => array(
			'title' => __('Activate scheduling', 'wpematico' ),
			'tip' => __('The campaign runs on its own, at the times you set below. WP-cron fires within about 5 minutes of the hour; an external cron is exact.', 'wpematico' ),
			'plustip' => __('You can see some examples here:', 'wpematico' ) . ' <a href="https://etruel.com/question/use-cron-scheduling/" target="_blank">'.__('How to use the CRON scheduling ?', 'wpematico' ) .'</a>',
		),
		'cronperiod' => array(
			'title' => __('Preset schedules', 'wpematico' ),
			'tip' => __('Pick one and the fields on the right are filled in for you, ready to edit. The preset itself is not saved.', 'wpematico' ),
		),
	),
	'Media Options' => array(
		'imgoptions' => array(
				'title' => '<h3>'.__('Image options for this campaign', 'wpematico' ).'</h3>',
				'tip' => __('These replace the global Settings, for this campaign only.', 'wpematico' ),
		),
		'imgcache' => array(
				'title' => __('Store images locally', 'wpematico' ),
				/* translators: %s html tag <img>. */
				'tip' => sprintf(__('Every image in an %s tag is saved to your Uploads folder and the content points at your copy. Off, the images stay on the source server.', 'wpematico' ), '&lt;img&gt;'),
		),
		'imgattach'	=> array(
				'title' => __('Attach images to posts', 'wpematico' ),
				'tip' => __('Each downloaded image is added to the Media library and attached to its post. Needed for the featured image; turn it off if the run is too slow.', 'wpematico' ),
		),
		'gralnolinkimg' => array(
				'title' => __('Remove link to source images', 'wpematico' ),
				/* translators: %s html tag <img>. */
				'tip' => sprintf(__('When an image cannot be downloaded, its %s tag is removed instead of pointing at the source site.', 'wpematico' ), '&lt;img&gt;'),
		),
		'image_srcset' => array(
				/* translators: %s html tag <img>. */
				'title' => sprintf(__('Read the srcset attribute of the %s tag', 'wpematico' ), '&lt;img&gt;'),
				/* translators: %s html tag <img>. */
				'tip'   => sprintf(__('Takes the largest image listed in srcset and uses it as the src of the %s tag.', 'wpematico' ), '&lt;img&gt;'),
		),
		'featuredimg' => array(
				'title' => __('Set the first image as featured', 'wpematico' ),
				'tip' => __('The first image found in the content is downloaded, attached and set as the featured image.', 'wpematico' ),
		),
		'fifu'	 => array(
			'title'		 => __('Use Featured Image from URL', 'wpematico'),
			'tip'		 => __('Hands the featured image to the Featured Image from URL plugin, which serves it from the source server. Install and activate that plugin first.', 'wpematico'),
			'plustip'	 => '<small> ' . __('Read about', 'wpematico') . ' <a href="https://wordpress.org/plugins/featured-image-from-url/" rel="nofollow" target="_Blank">' . __('Featured Image from URL', 'wpematico') . '</a> ' . __('plugin in WordPress repository.','wpematico') . '</small><br />' .
				__('With that plugin inactive, WPeMatico still writes its meta fields, but no featured image is shown.', 'wpematico'),
		),
		'enable_featured_image_selector' => array(
				'title' => __('Choose which image is the featured one', 'wpematico' ),
				'tip' => __('Pick the image by its position in the content -- the first, the second, the third -- instead of always taking the first.', 'wpematico' ),
		),

		'rmfeaturedimg' => array(
				'title' => __('Remove the featured image from the content', 'wpematico' ),
				'tip' => __('The image used as featured is stripped from the post content, so your theme does not show it twice.', 'wpematico' ),
		),
		'enablemimetypes'	=> array(
			'title' => __('Allow more file types', 'wpematico' ),
			'tip' => __('Uploads files whose type the Media library does not accept by default. It does not work on every server.', 'wpematico' ),
		),
		'save_attr_images'	=> array(
			'title' => __('Save image attributes on WP Media', 'wpematico' ),
			'tip' => __('Keeps the caption, alt text and title that came with the image in the Media library.', 'wpematico' ),
		),
		'customupload'	=> array(
				'title' => __('Use custom upload for images', 'wpematico' ),
				'tip' => __('Saves the image exactly as it comes from the source, skipping WordPress. Faster, but it does not work on every server.', 'wpematico' ),
		),
		'audio_options' => array(
				'title' => '<h3>'.__('Audio options for this campaign', 'wpematico' ).'</h3>',
				'tip' => __('These replace the global Settings, for this campaign only.', 'wpematico' ),
		),
		'audio_cache' => array(
				'title' => __('Store audios locally', 'wpematico' ),
				/* translators: %s html tag <audio>. */
				'tip' => sprintf(__('Every audio in an %s tag is saved to your Uploads folder and the content points at your copy. Off, the files stay on the source server.', 'wpematico' ), '&lt;audio&gt;'),
		),
		'audio_attach'	=> array(
				'title' => __('Attach audios to posts', 'wpematico' ),
				'tip' => __('Each downloaded audio is added to the Media library and attached to its post. Turn it off if the run is too slow.', 'wpematico' ),
		),
		'gralnolink_audio' => array(
				'title' => __('Remove link to source audios', 'wpematico' ),
				/* translators: %s html tag <audio>. */
				'tip' => sprintf(__('When an audio cannot be downloaded, its %s tag is removed instead of pointing at the source site.', 'wpematico' ), '&lt;audio&gt;'),
		),
		'customupload_audios'	=> array(
				'title' => __('Use custom upload for audios', 'wpematico' ),
				'tip' => __('Saves the audio exactly as it comes from the source, skipping WordPress. Faster, but it does not work on every server.', 'wpematico' ),
		),
		'video_options' => array(
				'title' => '<h3>'.__('Video options for this campaign', 'wpematico' ).'</h3>',
				'tip' => __('These replace the global Settings, for this campaign only.', 'wpematico' ),
		),
		'video_cache' => array(
				'title' => __('Store videos locally', 'wpematico' ),
				/* translators: %s html tag <video>. */
				'tip' => sprintf(__('Every video in a %s tag is saved to your Uploads folder and the content points at your copy. Video files are heavy: they fill your disk fast.', 'wpematico' ), '&lt;video&gt;'),
		),
		'video_attach'	=> array(
				'title' => __('Attach videos to posts', 'wpematico' ),
				'tip' => __('Each downloaded video is added to the Media library and attached to its post. Turn it off if the run is too slow.', 'wpematico' ),
		),
		'gralnolink_video' => array(
				'title' => __('Remove link to source videos', 'wpematico' ),
				/* translators: %s html tag <video>. */
				'tip' => sprintf(__('When a video cannot be downloaded, its %s tag is removed instead of pointing at the source site.', 'wpematico' ), '&lt;video&gt;'),
		),
		'customupload_videos'	=> array(
				'title' => __('Use custom upload for videos', 'wpematico' ),
				'tip' => __('Saves the video exactly as it comes from the source, skipping WordPress. Faster, but it does not work on every server.', 'wpematico' ),
		),
	),

	'Duplicate Controls' => array(
		'duplicate_options' => array(
				'title' => __('Duplicate options for this campaign', 'wpematico' ),
				'tip' => __('These replace the global Settings, for this campaign only.', 'wpematico' ),
		),

		'allowduplicates' => array(
			'title' => __('Allow duplicate posts', 'wpematico' ),
			'tip' => __('Turns off both duplicate checks, so every item is published again on every run. Almost never what you want.', 'wpematico' ),
			'plustip' => '&nbsp;&nbsp;&nbsp;&nbsp;<b>'. __('The two checks', 'wpematico' ) .':</b> '. __('the post title, and a hash of the last item address the campaign read. The hash is the reliable one; titles alone fail often.', 'wpematico' ).'<br>'.
				__('With both off, the same items come in for ever. To publish items that share a title, use "Allow duplicate titles" and leave the hash check on.', 'wpematico' ),
		),
		'jumpduplicates' => array(
			'title' => __('Keep reading past a duplicate', 'wpematico' ),
			'tip' => __('The campaign skips each duplicate and keeps looking for new items, instead of stopping at the first one. Not recommended.', 'wpematico' ),
			'plustip' => '&nbsp;&nbsp;&nbsp;&nbsp;<b>' . __('How it works','wpematico').':</b> '. __('feed items come ordered newest first, so the campaign reads from the top and stops at the first item it already has: everything below it is older and already published.', 'wpematico' ).'<br>'.
				__('The hash only covers the last item read, so on a feed where the title check works poorly this option can publish the same item twice.', 'wpematico' ),
		),
		'add_extra_duplicate_filter_meta_source' => array(
				'title' => __('Also check duplicates by source permalink', 'wpematico' ),
				'tip' => __('One more query per item. Turn it on only if you still get duplicates, which happens with feeds that do not follow the standard.', 'wpematico' ),
			),
	),

	'XML Campaign Type' => array(

		'XML Campaign Type Box' => array(
				'title' => __('XML campaign type', 'wpematico' ),
				'tip' => __('Reads the items of an XML file as if the file were an RSS feed.', 'wpematico' ),
		),

		'Elements of XML' => array(
			'title' => __('Elements of the XML', 'wpematico' ),
			'tip' => __('Choose the XML node that holds each property of an item: title, content, image, date. When a node name appears more than once, name its parent in the right column.', 'wpematico' ),
		),
		'Parent Element' => array(
				'title' => __('Parent element', 'wpematico' ),
				'tip' => __('Tells WPeMatico which of the repeated nodes to read. It has to be the parent of the node picked on the left.', 'wpematico' ),
			),
	),

	'Post Template' => array(
		'postemplate' => array(
				'title' => __('Enable the post template', 'wpematico' ),
				'tip' => __('Build the content of each post around the text the feed brings: add your own text, images or campaign data before it is saved.', 'wpematico' ),
				'plustip' => '<b>' . __('Supported tags', 'wpematico' ) . '</b>
				<p>' . __('A tag is a piece of text that gets replaced dynamically when the post is created. Currently, these tags are supported:', 'wpematico' ) . '</p>
				<ul style=\'list-style-type: square;margin:0 0 5px 20px;font:0.92em "Lucida Grande","Verdana";\'>
				  <li><strong>{title}</strong> ' . __('The feed item title.', 'wpematico' ) . ' </li>
				  <li><strong>{content}</strong> ' . __('The feed item content.', 'wpematico' ) . ' </li>
				  <li><strong>{itemcontent}</strong> ' . __('The feed item description.', 'wpematico' ) . ' </li>
				  <li><strong>{image}</strong> ' . __('Put the featured image on content.', 'wpematico' ) . ' </li>
				  <li><strong>{author}</strong> ' . __('The feed item author.', 'wpematico' ) . ' </li>
				  <li><strong>{authorlink}</strong> ' . __('The feed item author link (If exist).', 'wpematico' ) . ' </li>
				  <li><strong>{permalink}</strong> ' . __('The feed item permalink.', 'wpematico' ) . ' </li>
				  <li><strong>{feedurl}</strong> ' . __('The feed URL.', 'wpematico' ) . ' </li>
				  <li><strong>{feedtitle}</strong> ' . __('The feed title.', 'wpematico' ) . ' </li>
				  <li><strong>{feeddescription}</strong> ' . __('The description of the feed.', 'wpematico' ) . ' </li>
				  <li><strong>{feedlogo}</strong> ' . __('The feed\'s logo image URL.', 'wpematico' ) . ' </li>
				  <li><strong>{feedfavicon}</strong> ' . __('The feed\'s Favicon URL.', 'wpematico' ) . ' </li>
				  <li><strong>{campaigntitle}</strong> ' . __('This campaign title', 'wpematico' ) . ' </li>
				  <li><strong>{campaignid}</strong> ' . __('This campaign ID.', 'wpematico' ) . ' </li>
				  <li><strong>{item_date}</strong> ' . __('The date of the post item.', 'wpematico' ) . ' </li>
				  <li><strong>{item_time}</strong> ' . __('The time of the post item.', 'wpematico' ) . ' </li>
				</ul>
				<p><b>' . __('Examples:', 'wpematico' ) . '</b></p>
				<div id="tags_list_examples" style="display: block;">
					<span>' . __('To close every post with a link to the source and the author name:', 'wpematico' ) . '</span>
					<div class="code">{content}<br>&lt;a href="{permalink}"&gt;' . __('Go to Source', 'wpematico' ) . '&lt;/a&gt;&lt;br /&gt;<br>Author: {author}</div>
					<p><em>{content}</em> ' . __('becomes the feed item content', 'wpematico' ) . ', <em>{permalink}</em> ' . __('the address of the original article, which turns it into a working link, and', 'wpematico' ) . ' <em>{author}</em> ' . __('the author of the feed item.', 'wpematico' ) . '</p>
					<span>' . __('To add a gallery of three columns with every image of the post, above that link:', 'wpematico' ) . '</span>
					<div class="code">{content}<br>[gallery link="file" columns="3"]<br>&lt;a href="{permalink}"&gt;' . __('Go to Source', 'wpematico' ) . '&lt;/a&gt;&lt;br /&gt;<br>Author: {author}</div>
					<p><em>[gallery link="file" columns="3"]</em> ' . __('is the WordPress gallery shortcode. Any shortcode works here: WordPress runs it when the post is displayed.', 'wpematico' ) . '</p>
					<p>' . __('To show the videos or audios of the post as a player, the playlist shortcode does the same for media files:', 'wpematico' ) . '</p>
					<div class="code">[playlist type="video" style="dark"]</div>
					<p>' . __('Read more at', 'wpematico' ) . ' <a title="How to use Post template feature" href="https://etruel.com/question/how-to-use-post-template-feature/" target="_blank">' . __('How to use the Post template feature', 'wpematico' ) . '</a>.</p>
				</div>',
		),
	),
	'Word to Category' => array(
		'wordcateg' => array(
				'title' => __('Words to Categories', 'wpematico' ),
				'tip' => __('Assigns a category to the post when a given word appears in the content.', 'wpematico' ),
				'plustip' => '<b>'. __('Example:', 'wpematico' ). '</b><br />'.
					__('To file every post that mentions "motor" under the category "Engines", type motor in the Word field and pick Engines in the categories list.', 'wpematico' ) . '<br />' .
				'<b>'. __('Regular Expressions', 'wpematico' ) . '</b><br />' .
				__("Regular expressions are supported, which saves you one box per word: instead of a box for motor and another for car, both pointing at Engines, use the | operator -- (motor|car). Write the expression without delimiters or flags; it runs over the whole field. To match regardless of case, leave the 'Case sensitive' box below unchecked.", 'wpematico' )
		),
	),
	'Rewrite options' => array(
		'rewrites' => array(
			'title' => __('Rewrite', 'wpematico' ),
			'tip' => __('Replaces words or phrases in the content with your own text, and can turn a word into a link. Click [?] below for examples.', 'wpematico' ),
			'plustip' => '<b>'. __('Basic rewriting:', 'wpematico' ) . '</b><br />'.
				__('To replace every "ass" with "butt", type ass in the origin field and butt in "rewrite to".', 'wpematico' ) . '<br />'.
				'<b>' . __('Title:', 'wpematico' ) . '</b><br />'.
				__('With "Title" checked the replacement runs on the title only, and unchecked on the content only. Add the same rewrite twice to cover both.', 'wpematico' ) . '<br />'.
				'<b>' . __('Relinking:', 'wpematico' ) . '</b><br />'.
				sprintf(__("To turn every mention of google into a link to Google, type google in the origin field and %s in the 'relink to' field.", 'wpematico'), 'https://google.com') . '<br />'.
				'<b>' . __('Regular expressions', 'wpematico' ) . '</b><br />'.
				__('Regular expressions are supported, which saves you one box per word: to replace both ass and arse with butt, use the | operator -- (ass|arse).', 'wpematico' ),
		),
	),
	'Taxonomies' => array(
		'category' => array(
				'title' => __('Categories', 'wpematico' ),
				'tip' => __('Assign categories of your own to every post, take the ones the source item carries, or both.', 'wpematico' ),
		),
		'autocats' => array(
				'title' => __('Add auto categories', 'wpematico' ),
				'tip' => __('The categories the source item carries are added to the post, and created on your site if they do not exist yet.', 'wpematico' ),
		),
		'category_limit' => array(
				'title' => __('Limit of categories', 'wpematico' ),
				'tip' => __('How many new categories a single run may create.', 'wpematico' ),
		),
		'parent_autocats' => array(
				'title' => __('Parent of the auto categories', 'wpematico' ),
				'tip' => __('Every category created by this campaign hangs from the one you pick here, instead of sitting at the top level.', 'wpematico' ),
		),
		'tags' => array(
				'title' => __('Tags', 'wpematico' ),
				'tip' => __('Tags added to every post of this campaign.', 'wpematico' ),
		),
		'postformat' => array(
				'title' => __('Post format', 'wpematico' ),
				'tip' => __('The post format for this campaign, if your theme supports formats. Leave it on Standard if it does not.', 'wpematico' ),
		),
	),
	'Log by email' => array(
		'sendlog' => array(
				'title' => __('Log by email', 'wpematico' ),
				'tip' => __('Sends you what happened on each run. You can ask for it only when something went wrong, or leave the field empty for no email at all.', 'wpematico' ),
		),
	)
);
$helpcampaign = apply_filters('wpematico_help_campaign', $helpcampaign);
global $current_screen;
$screen = $current_screen; //WP_Screen::get('wpematico_page_wpematico_settings ');
foreach($helpcampaign as $key => $section){
	$tabcontent = '';
	foreach($section as $section_key => $sdata){
		$helptip[$section_key] = htmlentities($sdata['tip']);
		$tabcontent .= '<p><strong>' . $sdata['title'] . '</strong><br />'.
				$sdata['tip'] . '</p>';
		$tabcontent .= (isset($sdata['plustip'])) ?	'<p>' . $sdata['plustip'] . '</p>' : '';
	}
	if ( ! defined('WPEMATICO_AJAX') ) {
		$screen->add_help_tab( array(
			'id'		=> $key,
			'title'		=> $key,
			'content'	=> $tabcontent,
		) );
	}
}
