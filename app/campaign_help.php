<?php
// don't load directly 
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

$helpcampaign = array( 
	'Campaign Options' => array( 
		'feeds' => array( 
			'title' => __('Feeds URLs.', 'wpematico' ),
			'tip' => __('You must type at least one feed url.', 'wpematico' ).'  '.
				__('(Less feeds equal less used resources when fetching).', 'wpematico' ).' '.
				__('Type the domain name to try to autodetect the feed url.', 'wpematico' ),
		),
		'itemfetch' => array( 
			'title' => __('Max items per Fetch.', 'wpematico' ),
			'tip' => __('Items to fetch PER every feed above.', 'wpematico' ).'  '.
				__('Recommended values are between 3 and 5 fetching more times to not lose items.', 'wpematico' ).'  '.
				__('Set it to 0 for unlimited.', 'wpematico' ),
		),
		'feed_order_date' => array( 
			'title' => __('Order feed items by Date before process.', 'wpematico' ),
			'tip' => __('Recommended to use only if the feeds are not ordered by date. The campaign can lose some items if the feed are not ordered by date from newest to oldest.', 'wpematico' ),
		),
		'itemdate' => array( 
			'title' => __('Use feed item date.', 'wpematico' ),
			'tip' => __('Use the original date from the post instead of the time the post is created by WPeMatico.', 'wpematico' ).'  '.
				__('To avoid incoherent dates due to lousy setup feeds, WPeMatico will use the feed date only if these conditions are met:', 'wpematico' ).'  '.
				'<ul style=\'list-style-type: square;margin:0 0 5px 20px;font:0.92em "Lucida Grande","Verdana";\'>
				<li>'. __('The feed item date is not too far in the past (specifically, as much time as the campaign frequency).', 'wpematico' ).' </li>
				<li>'. __('The fetched feed item date is not in the future.', 'wpematico' ).' </li></ul>',
		),
		'campaign_feeddate_forced' => array( 
			'title' => __('Force item date.', 'wpematico' ),
			'tip' => __('Use the feed item date ignoring incoherent dates like past posts published after new ones.', 'wpematico' ),
		),
		'linktosource' => array( 
			'title' => __('Post title links to source.', 'wpematico' ),
			'tip' => __('This option make the title permalink to original URL.', 'wpematico' ).'<br />'. 
				__('This feature will be ignored if you deactivate Campaign Custom Fields on settings.', 'wpematico' ),
		),
		'copy_permanlink_source' => array( 
			'title' => __('Copy the permalink from the source.', 'wpematico' ),
			'tip' => __('This option allows to use the exact permalink of the source', 'wpematico' ),
		),
		'avoid_search_redirection' => array( 
			'title' => __('Avoid Search redirection to source permalink.', 'wpematico' ),
			'tip' => __('This feature allow follow or not redirections of URLs on permalinks to try to get the original source permalink.', 'wpematico' ),
			'plustip' => __('You can UNSELECT this if uses source permalinks and the obtained URLs are not the the originals.', 'wpematico' ).'<br />'. 
				__('CHECK this option to improve fetching speed and performance.', 'wpematico' ),
		),
		'allowpings' => array( 
			'title' => __('Pingbacks y trackbacks.', 'wpematico' ),
			'tip' => __('Allows pinbacks and trackbacks in the posts created by this campaign.', 'wpematico' ),
		),
		'convert_utf8' => array( 
			'title' => __('Convert character encoding to UTF-8.', 'wpematico' ),
			'tip' => __('This feature convert character encoding of content and title to UTF-8 if they have different character encoding.', 'wpematico' ),
		),
		'commentstatus' => array( 
			'title' => __('Discussion options.', 'wpematico' ),
			'tip' => __('Comments options to these posts.', 'wpematico' ),
		),
		'postsauthor' => array( 
			'title' => __('Author.', 'wpematico' ),
			'tip' => __('The posts created by this campaign will be assigned to this author.', 'wpematico' ),
		),
		'get_excerpt' => array( 
			'title' => __('Fill Excerpt with item description field.', 'wpematico' ),
			'tip' => __('Wordpress defaults are making the excerpts "on the fly" from post contents, but check this option if you want to fill in the excerpt field with the description tag of the item. ', 'wpematico' ),
			'plustip' => __('You could get the same value of the content because if the description field does not exists the content field will be used. ', 'wpematico' ),
		),
		'striphtml' => array( 
			'title' => __('Strip All HTML Tags.', 'wpematico' ),
			'tip' => __('Remove all HTML from original content', 'wpematico' ).'<br>'.
				sprintf(__('NOTE that also strip images %s and links %s', 'wpematico' ), '&lt;img&gt;', '&lt;a&gt;.'),
		),
		'striplinks' => array( 
			'title' => __('Strip links from content.', 'wpematico' ),
			'tip' => __('This option take out clickable links from content, leaving just the text.', 'wpematico' ),
		),
		'woutfilter' => array( 
				'title' => __('Post Content Unfiltered.', 'wpematico' ),
				'tip' => '<b><i>'.__('Skip the Wordpress post content filters.', 'wpematico' ).'</i></b>'.
					'<br>'.__('Saves the content exactly as the plugin has it.', 'wpematico' ).
					'<br>'.__('Not recommended.', 'wpematico' ),
		),
	),
	'Youtube Feeds' => array( 
		'feed_url' => array( 
			'title' => __('Youtube feeds URLs.', 'wpematico' ),
			'tip' => __('Channel Videos feed and User Videos feed.', 'wpematico' ).
				'<br>'.__('Fill in the feed URL field in the standard way.', 'wpematico' ).
				'<br><br>'.__('For Youtube Channel as: ', 'wpematico' ) . 'https://www.youtube.com/feeds/videos.xml?channel_id=%channelid%'.
				'<br>'.__('For Youtube User as: ', 'wpematico' ) . 'https://www.youtube.com/feeds/videos.xml?user=%username%'.
				'<br>'.__('For Youtube Playlist as: ', 'wpematico' ) . 'https://www.youtube.com/feeds/videos.xml?playlist_id=%playlist_id%'.
				'<br><br>'.__('The campaign fetches the title, the image, the embebed video and the description.', 'wpematico' ),
		),
		'youtube_embed' => array( 
			'title' => __('Use [embed]', 'wpematico' ),
			'tip' => __('Use [embed] WordPress shortcode instead Youtube shared iframe html code.', 'wpematico' ) . '<br>'
				. __('The Embed feature allows you to wrap embedded items using a simple Shortcode to set of a maximum (but not fixed) width and height. This was added as of WordPress 2.9 and is used like this: ', 'wpematico' )
				. '[embed]...[/embed]',
		),
		'youtube_sizes' => array( 
			'title' => __('Video sizes', 'wpematico' ),
			'tip' => __('Width and Height of the video frame in the post.', 'wpematico' ) . '<br>'
				. __('Recommended left 0 (zero) for responsive.', 'wpematico' ),
		),
	),
	'bbPress Forums' => array( 
		'bbpress' => array( 
			'title' => __('How works the bbPress Campaign Type', 'wpematico' ),
			'tip' => __('You can UN-select forum to create new Forums or select an already created bbPress Forum to publish new Topics inside it, according to the items in the feed.', 'wpematico' ),
			'plustip' => __('You can also select a created Topic to publish all the items as replies of it.', 'wpematico' ),
		),
	),
	'Schedule Options' => array( 
		'schedule' => array( 
			'title' => __('Activate Scheduling.', 'wpematico' ),
			'tip' => __('Activate Automatic Mode.', 'wpematico' ).
				'<br>'.__('You can define here on what times you wants to fetch this feeds.  This has 5 min. of margin on WP-cron schedules.  If you set up an external cron en WPeMatico Settings, you\'ll get better preciseness.', 'wpematico' ),
			'plustip' => __('You can see some examples here:', 'wpematico' ) . ' <a href="https://etruel.com/question/use-cron-scheduling/" target="_blank">'.__('How to use the CRON scheduling ?', 'wpematico' ) .'</a>',
		),
		'cronperiod' => array( 
			'title' => __('Preselected schedules.', 'wpematico' ),
			'tip' => __('Select a predefined scheduler to get a value easily for the cron. This value is not saved.', 'wpematico' ).
				'<br>'. __('This is also used frecuently as startpoint to define a cron schedule.', 'wpematico' ),
			'plustip' => __('Just select an option and the values will be shown in the fields at right.', 'wpematico' ),
		),
	),
	'Media Options' => array(
		'imgoptions' => array( 
				'title' => '<h3>'.__('Campaign Options For Images.', 'wpematico' ).'</h3>',
				'tip' => __('These features will override the general Settings options only for this campaign.', 'wpematico' ),
		),
		'imgcache' => array( 
				'title' => __('Cache images.', 'wpematico' ),
				'tip' => sprintf(__('All images found in %s tags in content will be uploaded to your current WP Upload Folder and replaced urls in content. Otherwise remains links to source hosting server.', 'wpematico' ), '&lt;img&gt;'),
		),
		'imgattach'	=> array( 
				'title' => __('Attach Images to post.', 'wpematico' ),
				'tip' => __('All images will be attached to the owner post and added to Wordpress Media library; necessary for Featured image, but if you see that the job process is too slowly you can deactivate this here.', 'wpematico' ),
		),
		'gralnolinkimg' => array( 
				'title' => __('Don\'t link external images.', 'wpematico' ),
				'tip' => __('If selected and image upload get error, then delete the \'src\' attribute of the &lt;img&gt;. Check this for don\'t link images from external sites.', 'wpematico' ),
		),
		'image_srcset' => array( 
				'title' => sprintf(__('Use srcset attribute instead of src of %s tag.', 'wpematico' ), '&lt;img&gt;'),
				'tip'   => sprintf(__('Selecting this option searches the srcset attribute if it exists, it searches for the larger image to overwrite the src attribute of the %s tag.', 'wpematico' ), '&lt;img&gt;'),	
		),
		'featuredimg' => array( 
				'title' => __('Set first image on content as Featured Image.', 'wpematico' ),
				'tip' => __('Check this to set first image found on every content to be uploaded, attached and made Featured.', 'wpematico' ),
		),
		'fifu'	 => array(
			'title'		 => __('Use Featured Image from URL.', 'wpematico'),
			'tip'		 => __('Check this to use Featured Image from URL plugin. Be sure it is installed and activated.', 'wpematico'),
			'plustip'	 => '<small> ' . __('Read about', 'wpematico') . ' <a href="https://wordpress.org/plugins/featured-image-from-url/" rel="nofollow" target="_Blank">' . __('Featured Image from URL', 'wpematico') . '</a> ' . __('plugin in WordPress repository.','wpematico') . '</small><br />' .
				__('Note if that plugin is not activated, WPeMatico will still save the meta fields in each post but does\'t show any featured image.', 'wpematico'),
		),
		'enable_featured_image_selector' => array( 
				'title' => __('Enable featured image selector.', 'wpematico' ),
				'tip' => __('Check this to select a featured image, it doesn\'t matter what order it have in the content. You can choice the first, second or third image.', 'wpematico' ),
		),
		
		'rmfeaturedimg' => array( 
				'title' => __('Remove Featured Image from content.', 'wpematico' ),
				'tip' => __('Check this to strip the Featured Image from the post content.', 'wpematico' ),
		),
		'enablemimetypes'	=> array( 
			'title' => __('Enable add other mime types.', 'wpematico' ),
			'tip' => __('Use this instead of Wordpress functions to improve performance. This function upload the mime types does not avaliable in the wordpress media.', 'wpematico' ).
				'<br />'. __('This function may not work in all servers.', 'wpematico' ),
		),
		'customupload'	=> array( 
				'title' => __('Custom Uploads for Images.', 'wpematico' ),
				'tip' => __('Use this instead of Wordpress functions to improve performance. This function uploads the image "as is" from the original to use it inside the post.', 'wpematico' ).
					'<br />'. __('This function may not work in all servers.', 'wpematico' ),
		),
//	),
//	'Options for Audios' => array(
		'audio_options' => array( 
				'title' => '<h3>'.__('Campaign Options For Audios.', 'wpematico' ).'</h3>',
				'tip' => __('These features will override the general Settings options only for this campaign.', 'wpematico' ),
		),
		'audio_cache' => array( 
				'title' => __('Cache audios.', 'wpematico' ),
				'tip' => sprintf(__('All audios found in %s tags in content will be uploaded to your current WP Upload Folder and replaced urls in content . Otherwise remains links to source hosting server.', 'wpematico' ), '&lt;audio&gt;'),
		),
		'audio_attach'	=> array( 
				'title' => __('Attach Audios to post.', 'wpematico' ),
				'tip' => __('All audios will be attached to the owner post and added to Wordpress Media library, but if you see that the job process is too slowly you can deactivate this here.', 'wpematico' ),
		),
		'gralnolink_audio' => array( 
				'title' => __('Don\'t link external audios.', 'wpematico' ),
				'tip' => __('If selected and audio upload get error, then delete the \'src\' attribute of the &lt;audio&gt;. Check this for don\'t link audios from external sites.', 'wpematico' ),
		),
		'customupload_audios'	=> array( 
				'title' => __('Custom Uploads for Audios.', 'wpematico' ),
				'tip' => __('Use this instead of Wordpress functions to improve performance. This function uploads the audio "as is" from the original to use it inside the post.', 'wpematico' ).
					'<br />'. __('This function may not work in all servers.', 'wpematico' ),
		),
//	),
//	'Options for Videos' => array(
		'video_options' => array( 
				'title' => '<h3>'.__('Campaign Options For Videos.', 'wpematico' ).'</h3>',
				'tip' => __('These features will override the general Settings options only for this campaign', 'wpematico' ),
		),
		'video_cache' => array( 
				'title' => __('Cache videos.', 'wpematico' ),
				'tip' => sprintf(__('All videos found in %s tags in content will be uploaded to your current WP Upload Folder and replaced urls in content . Otherwise remains links to source hosting server.', 'wpematico' ), '&lt;video&gt;'),
		),
		'video_attach'	=> array( 
				'title' => __('Attach Videos to post.', 'wpematico' ),
				'tip' => __('All videos will be attached to the owner post and added to Wordpress Media library; necessary for Featured video, but if you see that the job process is too slowly you can deactivate this here.', 'wpematico' ),
		),
		'gralnolink_video' => array( 
				'title' => __('Don\'t link external videos.', 'wpematico' ),
				'tip' => sprintf(__('If selected and video upload get error, then delete the \'src\' attribute of the %s. Check this for don\'t link videos from external sites.', 'wpematico' ), '&lt;video&gt;. '),
		),
		'customupload_videos'	=> array( 
				'title' => __('Custom Uploads for Videos.', 'wpematico' ),
				'tip' => __('Use this instead of Wordpress functions to improve performance. This function uploads the video "as is" from the original to use it inside the post.', 'wpematico' ).
					'<br />'. __('This function may not work in all servers.', 'wpematico' ),
		),
	),

	'Duplicate Controls' => array(
		'duplicate_options' => array( 
				'title' => __('Campaign Options For Duplicates.', 'wpematico' ),
				'tip' => __('These features will override the general Settings options only for this campaign.', 'wpematico' ),
		),
		
		'allowduplicates' => array( 
			'title' => __('Deactivate duplicate controls.', 'wpematico' ),
			'tip' => __('When the running campaign found a duplicated post the process is interrupted because assume that all followed posts, are also duplicates.  You can disable these controls here.', 'wpematico' ).'<br>'.
				__('Duplicates checking by hash is a boost to checking for duplicates by title, which may fail many times.', 'wpematico' ),
			'plustip' => '&nbsp;&nbsp;&nbsp;&nbsp;<b>'. __('Allowing duplicated posts', 'wpematico' ) .':</b> '. __("There are two controls for duplicates, title of the post and a hash generated by last item's url obtained on campaign process.", 'wpematico' ).'<br>'.
				__('NOTE: If disable both controls, all items will be fetched again and again... and again, ad infinitum.  If you want allow duplicated titles, just activate "Allow duplicated titles".', 'wpematico' ),
		),
		'jumpduplicates' => array( 
			'title' => __('Continue Fetching if found duplicated items.', 'wpematico' ),
			'tip' => __('Unless it is the first time, when finds a duplicate, it means that all following items were read before. This option avoids and allows jump every duplicate and continues reading the feed searching more new items. NOT RECOMMENDED.', 'wpematico' ),
			'plustip' => '&nbsp;&nbsp;&nbsp;&nbsp;<b>' . __('How it works:','wpematico').'</b> '. __('The feed items are ordered by datetime in almost all cases. When the campaign runs, goes item by item from newest to oldest, and stops when found the first duplicated item, this mean that all items following (the old ones) are also duplicated.', 'wpematico' ).'<br>'.
				__('As the hash is checked only by the last retrieved item, selecting this option may generate duplicate posts if duplicate checking by title does not work well for a campaign.', 'wpematico' ),
		),
		'add_extra_duplicate_filter_meta_source' => array( 
				'title' => __('Add an extra duplicate filter by source permalink in meta field value.', 'wpematico' ),
				'tip' => __('This option is ONLY recommended if you continues with duplicates problems in your site. This can be given by some non-standards feeds.  NOT RECOMMENDED.', 'wpematico' ),
			),
	),

	'XML Campaign Type' => array(
		
		'XML Campaign Type Box' => array( 
				'title' => __('XML Campaign Type', 'wpematico' ),
				'tip' => __('The XML campaign type provides the necessary tools to fetch the items in the XML files. As if the XMLs were RSS feeds.', 'wpematico' ),
		),
		
		'Elements of XML' => array( 
			'title' => __('Elements of XML.', 'wpematico' ),
			'tip' => __('Select the XML node corresponding to the properties of the feed items, such as title, content, image, date, etc. If the selected XML node is not unique you should use the Parent Elements in the right column to indicate which parent the selected node belongs to.', 'wpematico' ),
		),
		'Parent Element' => array( 
				'title' => __('Parent Element.', 'wpematico' ),
				'tip' => __('The function of the Parent Element is to detect the correct XML node of the feed Item properties when the node selected on the left is not unique or has more than one element.', 'wpematico' ) . '<br>'. 
					__('The selected Parent Element must be the parent of the selected XML node in the left column.', 'wpematico' ),
			),
	),

	'Post Template' => array( 
		'postemplate' => array( 
				'title' => __('Enable Post Template.', 'wpematico' ),
				'tip' => __('Campaign post template allow to modify the content fetched by adding extra information, such as text, images, campaign data, etc. before save it as post content.', 'wpematico' ).
			'<br>'.__('You can use some tags that will be replaced for current value. See below the description and examples on how to use this feature.', 'wpematico' ),
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
					<span>' . __('If you want to add a link to the source at the bottom of every post and the author, the post template would look like this:', 'wpematico' ) . '</span>
					<div class="code">{content}<br>&lt;a href="{permalink}"&gt;' . __('Go to Source', 'wpematico' ) . '&lt;/a&gt;&lt;br /&gt;<br>Author: {author}</div>
					<p><em>{content}</em> ' . __('will be replaced with the feed item content', 'wpematico' ) . ', <em>{permalink}</em> ' . __('by the source feed item URL, which makes it a working link and', 'wpematico' ) . ' <em>{author}</em> ' . __('with the original author of the feed item.', 'wpematico' ) . '</p>
					<span>' . __('Also you can add a gallery with three columns with all thumbnails images clickables at the bottom of every content, but before source link and author name, the post template would look like this:', 'wpematico' ) . '</span>
					<div class="code">{content}<br>[gallery link="file" columns="3"]<br>&lt;a href="{permalink}"&gt;' . __('Go to Source', 'wpematico' ) . '&lt;/a&gt;&lt;br /&gt;<br>Author: {author}</div>
					<p><em>[gallery link="file" columns="3"]</em> ' . __('it\'s a WP shortcode for insert a gallery into the post.  You can use any shortcode here; will be processed by Wordpress.', 'wpematico' ) . '</p>
					<p><ins>If you want to display all the media videos or audios</ins> in the content you could use the playlist shortcode 
					that implements the functionality of displaying a collection of WordPress audio or video files in a post.</p>
					<div class="code">[playlist type="video" style="dark"]</div>
					<p>Read more about Post Template feature at <a title="How to use Post template feature" href="https://etruel.com/question/how-to-use-post-template-feature/" target="_blank">How to use Post template feature ?</a>.'. '</p>
				</div>',
		),
	),
	'Word to Category' => array( 
		'wordcateg' => array( 
				'title' => __('Word to Category options.', 'wpematico' ),
				'tip' => __('Allow to assign a singular category to the post if a word is found in the content.', 'wpematico' ),
				'plustip' => '<b>'. __('Example:', 'wpematico' ). '</b><br />'.
					__('If the post content contain the word "motor" and then you want assign the post to category "Engines", simply type "motor" in the "Word" field, and select "Engine" in Categories combo.', 'wpematico' ) . '<br />' .
				'<b>'. __('Regular Expressions', 'wpematico' ) . '</b><br />' .
				__("For advanced users, regular expressions are supported. Using this will allow you to make more powerful replacements. Take multiple word replacements for example. Instead of using many Word2Cat boxes to assign motor and car to Engines, you can use the | operator: (motor|car), also the field accepts regular expressions without parameters, you should not indicate multiline or line breaks because by default use the entire text field. If you want Case insensitive on RegEx, you must check the 'Case sensitive' checkbox below.", 'wpematico' )
		),			
	),
	'Rewrite options' => array( 
		'rewrites' => array( 
			'title' => __('Content Rewrites.', 'wpematico' ),
			'tip' => __('The rewrite feature allow you to replace words or phrases of the content with the text you specify.', 'wpematico' ).' '.
				__('Also can use this feature to make simple links from some words with origin and re-link fields.', 'wpematico' ).'<br>'.
				__('For examples click on [?] below.', 'wpematico' ),
			'plustip' => '<b>'. __('Basic rewriting:', 'wpematico' ) . '</b><br />'.
				__('To replace all occurrences the word ass with butt, simply type ass in the "origin field", and butt in "rewrite to".', 'wpematico' ) . '<br />'.
				'<b>' . __('Title:', 'wpematico' ) . '</b><br />'.
				__('If you check "Title" checkbox only replace on title. If you un-check "Title" only replace on content. you must insert twice if you want to replace on both fields.', 'wpematico' ) . '<br />'.
				'<b>' . __('Relinking:', 'wpematico' ) . '</b><br />'.
				__("If you want to find all occurrences of google and make them link to Google, just type google in the 'origin field' and ", "wpematico") . "http://google.com" . __("in the 'relink to' field.", 'wpematico' ) . '<br />'.
				'<b>' . __('Regular expressions', 'wpematico' ) . '</b><br />'.
				__('For advanced users, regular expressions are supported. Using this will allow you to make more powerful replacements. Take multiple word replacements for example. Instead of using many rewriting boxes to replace ass and arse with butt, you can use the | operator: (ass|arse).', 'wpematico' ),
		),
	),
	'Taxonomies' => array( 
		'category' => array( 
				'title' => __('Campaign Categories.', 'wpematico' ),
				'tip' => __('Add categories from the source post and/or assign already existing categories.', 'wpematico' ),
		),
		'autocats' => array( 
				'title' => __('Add auto Categories.', 'wpematico' ),
				'tip' => __('If categories are found on source item, these categories will be added to the post; If category does not exist, then will be created.', 'wpematico' ),
		),
		'parent_autocats' => array( 
				'title' => __('Parent category to auto categories.', 'wpematico' ),
				'tip' => __('If you select a category in this option, all automatically added categories will be children of the selected category.', 'wpematico' ),
		),
		'tags' => array( 
				'title' => __('Tags Generation.', 'wpematico' ),
				'tip' => __('You can insert here the tags for every post of this campaign.', 'wpematico' ),
		),
		'postformat' => array( 
				'title' => __('Campaign Post Format.', 'wpematico' ),
				'tip' => __('If your theme supports post formats you can select one for the posts of this campaign, otherwise left on Standard.', 'wpematico' ),
		),
	),
	'Log by email' => array( 
		'sendlog' => array( 
				'title' => __('Sending Log.', 'wpematico' ),
				'tip' => __('An email will be sent with the events of campaign fetched. You can also filter the emails only if an error occurred or left blank to not send emails of this campaign.', 'wpematico' ),
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
