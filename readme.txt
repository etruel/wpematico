﻿=== WPeMatico RSS Feed Fetcher ===
Contributors: etruel, sniuk, khaztiel
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=B8V39NWK3NFQU
Tags: RSS,XML,RSS to Post,Feed to Post,XML to post,autoblog,rss aggregator,Feed,rss to post,syndication,xml import,Post,Posts,aggregation,atom,bot,content,writing
Requires at least: 4.8
Tested up to: 6.2.2
Requires PHP: 5.6
Stable tag: trunk
License: GPLv2 or later

WPeMatico is autoblogging in the blink of an eye!  On complete autopilot WPeMatico gets new contents regularly for your site!

== Description ==
WPeMatico is a very easy to use autoblogging plugin. Organized into campaigns, it publishes your posts automatically from the RSS/Atom and XML feeds of your choice.

This plugin offers you a nice interface following the WordPress standards that allows you to manage in every campaign all the feeds you import. In order to make your site more user-friendly, you can fetch contents from multiple feeds and arrange them according to categories.

[youtube https://www.youtube.com/watch?v=N9wuKSbp1AE]


For XML and RSS fetching, it uses the Simplepie library included in Wordpress or forces to use the external library included in the plugin. As for image and files processing, it uses the core functions of Wordpress.

If you like WPeMatico, please [Rate 5 Stars](https://wordpress.org/support/view/plugin-reviews/wpematico?rate=5#new-post) on Wordpress. Thanks! :)

You can submit any bug in the [bugtracker](https://github.com/etruel/wpematico/issues).

#### FEATURES:
> #### FREE
> * Campaigns Feeds and options are organized into campaigns.
> * Comfortable interface like Worpress posts editing for every campaign.
> * Multiple feeds / categories / tags.
> * Auto add categories from source posts.
> * Integrated *or not* with the Simplepie library that comes with Wordpress. This includes **RSS 0.91** and **RSS 1.0** formats, the popular **RSS 2.0** format, **Atom** ...
> * From 2.2v also supports for **XML** feeds or files by uploading them into WordPress Media lib.
> * Feed auto discovery allows you to add feeds without even knowing the exact URL. (Thanks Simplepie!)
> * Unix cron and WordPress cron jobs. 
> * For maximum performance, you can make the RSS fetching process be called by an external cron job, or simply let WordPress handle it.
> * Options to set max items per fetch, comments on or off, sets authors and a lot of more options.
> * It allows to publish to any public Wordpress Custom post type, status and post formats.
> * Images caching are integrated with Wordpress Media Library. 
> * The first (or 2nd or 3rd, you choose) image attached to a post can be marked as the Featured Image.
> * It is possible to upload & attach only the featured image ignoring the others.
> * Compatible to work with _Featured Image from URL_ plugin by _Marcel Jacques Machado._ 
> * You can choose whether to upload images as post attachments or not. Also upload remote images or link to source. Fully configurable.
> * You can choose whether to upload *audio and video files* as post attachments or not. Also upload remote files or link to source. Fully configurable.
> * Words or phrases rewriting. Regular expressions supported.
> * Words Relinking. Define custom links for the words or phrases you specify.
> * Words to Category. Define custom words to search into content or title to assign every post to specified category.
> * Detailed Log sending to custom e-mail. On every executed cron or only on errors with a campaign.
> * Option to replace title links (Permalinks) to point to the source site.
> * Can copy the slug from original permalink for better SEO.
> * Post templating before save. Can include Galleries, link to the sources or any text you want.
> * Dashboard Widget with campaigns summary.
> * Option to choose what roles can see the dashboard widget.
> * Multilanguage ready.
>
> #### Some external integrations included:
> * Allows featured images from URL with _Featured Image from URL_ plugin.
> * Allows to work with external crons from same server or external cron services websites.
> * Allows to publish forums, topics and answers in BBPress forums.
> * Allows to publish from XML custom feeds setting up their configurations.
> * Publishing from YouTube playlists, channels and profile feeds.
> * Allows to publish in multiple languages with Polylang plugin. Requires **Polylang** and [WPeMatico Polylang](https://wordpress.org/plugins/wpematico-polylang/?utm_source=extension&utm_medium=description_tab&utm_content=wpematico-polylang&utm_campaign=readme) plugins installed.
 
#### With lots of amazing and professional features to work with images and content parsers and filters.
 
**[Professional Add-on](https://etruel.com/downloads/wpematico-professional/?utm_source=extension&utm_medium=description_tab&utm_content=wpematico-professional&utm_campaign=readme):** Extends WPeMatico with a lot of advanced and new features to parse and filter feed items contents, filters for featured, media, enclosure and in-content images, automatic tags generation, inserts custom fields with every post and much more.

---
**[Synchronizer](https://etruel.com/downloads/wpematico-synchronizer/?utm_source=extension&utm_medium=description_tab&utm_content=wpematico-synchronizer&utm_campaign=readme):** Allows you to keep updated the posts obtained, the synchronization process analyzes the content of the feed items and compares them with the post, if different it will be updated, including media files, authors, categories and tags.

---
**[FULL Content](https://etruel.com/downloads/wpematico-full-content/?utm_source=extension&utm_medium=description_tab&utm_content=wpematico-full-content&utm_campaign=readme):** Takes the item permalink and scratches its web page to find the full content. Set the featured image from meta tags of source web page, Open Graph or Twitter images.  Also allows set up a configuration file for every domain pointing to what section of the web page must be obtained.

---
**[Manual Fetching](https://etruel.com/downloads/wpematico-manual-fetching/?utm_source=extension&utm_medium=description_tab&utm_content=wpematico-manual-fetching&utm_campaign=readme):** Allows you to review and insert each item in the Campaign Preview feature, one by one or in bulk mode with just a click. You’ll see the items already parsed like will be published as posts with their images, audio and even videos.

---
**[Polyglot](https://etruel.com/downloads/wpematico-polyglot/?utm_source=extension&utm_medium=description_tab&utm_content=wpematico-polyglot&utm_campaign=readme):** Lets you translate the posts obtained from remote feeds before inserting them in your WordPress blog. Just select the original language of articles in a WPeMatico campaign so you can translate to one of the 107 supported languages.

---
**[Make me Feed "Good"](https://etruel.com/downloads/wpematico-make-feed-good/?utm_source=extension&utm_medium=description_tab&utm_content=wpematico-make-feed-good&utm_campaign=readme):** Create your custom feeds RSS 2.0 with content from external sites in your WordPress blog, regardless of whether or not those have their own feed.

---
**[Facebook Fetcher](https://etruel.com/downloads/wpematico-facebook-fetcher/?utm_source=extension&utm_medium=description_tab&utm_content=wpematico-facebook-fetcher&utm_campaign=readme):** Obtain the content from Facebook pages or groups, just adding one WPeMatico campaign per facebook page/group. Also imports images in big size, and Facebook comments with commenters names to every imported post and many more.

---
**[Better Excerpts](https://etruel.com/downloads/wpematico-better-excerpts/?utm_source=extension&utm_medium=description_tab&utm_content=wpematico-better-excerpts&utm_campaign=readme):** Makes excerpts with first post content sentence and other filters in front-end and also in feed contents.  Can be used to save the excerpts just for new posts of WPeMatico campaigns or to parse the excerpts of all posts of your site when shows them in your front-end page.

---
**[Publish 2 Email](https://etruel.com/downloads/wpematico-publish-2-email/?utm_source=extension&utm_medium=description_tab&utm_content=wpematico-publish-2-email&utm_campaign=readme):** Very useful addon that allows publishing remotely by Wordpress *Post via email*, *Jetpack Post by Email* feature or plugins like *Postie*.  Sends the fetched posts to an email account. Each campaign allows send the posts to different email accounts.

---
**[WPeMatico Polylang](https://wordpress.org/plugins/wpematico-polylang/?utm_source=extension&utm_medium=description_tab&utm_content=wpematico-polylang&utm_campaign=readme):** send the automated publishing posts of each WPeMatico campaign to a different Polylang language to allow translate them later. This translation could be done by Lingotek addon of Polylang or any other translation service manual or automated. Free.

---

#### FREE & Premium Technical Support
> #### **[FREE Technical Support](https://etruel.com/my-account/support/?utm_source=extension&utm_medium=description_tab&utm_content=support&utm_campaign=readme):**
> * Ask for any problem you may have and you'll get support for free.
> * If it is necessay we will see into your website to solve your issue.

> #### **[Premium Support](https://etruel.com/downloads/premium-support/?utm_source=extension&utm_medium=description_tab&utm_content=premium-support&utm_campaign=readme):**
> * Get access to in-depth setup assistance.
> * Whatever the issue is, we will dig in and do our absolute best to resolve issues for you.
> * We will even log directly into your site to find the problems.
> * You can rest assured knowing that we are going to find an answer, no matter how long it takes.
> * Includes the editing for a config file for *one* website for Full Content Add-on.

---
**[Flash Cache](https://wordpress.org/plugins/flash-cache/):** If you need a very good and WPeMatico compatible Cache plugin take a look at this FREE plugin. Improves the velocity till 10x comparing with with other cache plugins for WordPress.


= Requirements =

You can see all the requirements details in the System Status tab inside WPeMatico Settings screen.


= Do you like WPeMatico? =

Don't hesitate to [give your feedback](https://wordpress.org/support/view/plugin-reviews/wpematico#new-post). It will help making the plugin better. Other contributions (such as new translations or helping other users on the support forum) are welcome !

---

= Privacy terms =

For the first beta versions of this plugin I was inspired for the old WP-o-Matic and also old versions of BackWPUp to make the posttype list. I'm talking about 2009 or 2010. Thanks to the developers ;)

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version. 

This plugin works also with addons or extensions that many can be purchased on our website.
The plugin uses the Easy Digital Download Extension License Manager to check for updates and validity of licenses if they are in use.
On load the Extensions page, it reads a feed only once every 5 days from our website to keep the addons list updated. (Can be deactivated from WPeMatico Settings screen)
On load the Settings page of the plugin, once per day max, reads the 5 stars user reviews from WordPress to show them in sidebar. (Can be deactivated from WordPress Settings Writing screen)
On submit the Subscription form in Welcome page, it make a request to wpematico.com website to suscribe the form data in our newsletters list.

Plugin page: [wpematico.com](https://wpematico.com)
Add-ons page: [etruel.com](https://etruel.com).
Author page in spanish: [NetMdP](https://www.netmdp.com). 

== Installation ==

You can either install it automatically from the WordPress admin, or do it manually:

= Using the Plugin Manager =

1. Click Plugins
2. Click Add New
3. Search for `wpematico`
4. Click Install
5. Click Install Now
6. Click Activate Plugin
7. Now you must see WPeMatico Item on Wordpress menu

= Manually =

1. Upload `wpematico` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress


== Screenshots ==

1. Dashboard Widget and menu.

1. The table list of campaigns plus some info of each one of them. 

1. Quick edit campaigns inline.

1. Get help in Wordpress tabs.

1. The Settings Page.

1. Editing a complete campaign. 

1. SimplePie Requirements Tests.


== Frequently Asked Questions ==

= First Install & testing requirements Video

[youtube https://youtu.be/z-yAVJY49XM]

= Filling the Settings Video

[youtube https://youtu.be/_6naAu1C-Oc]

= Creating a Campaign Video

[youtube https://youtu.be/Kzex_AyfWyo]

= I have this plugin installed and activated. What must I do now ? =

* OK, in Wordpress admin you should see now a new area below the posts called WPeMatico. At settings, setup the plugin configuration. At Campaigns you must add one. There, add one or several feeds of your choice. You can use a campaign for grouping the feeds for a category or another custom topic that you want.

= Upgrading FREE or PRO versions =

* You can make an automatic upgrade from Wordpress plugins page or replace files through FTP.

= Is there any way to import embedded videos from feed content and add that code into my posts? =
* To allow video embeds in post content, you must add the tags 'iframe' and 'embed' in Simplepie library into **WPeMatico Settings**. See below:

* **Change SimplePie HTML tags to strip**

> base,blink,body,doctype,font,form,frame,frameset,html,input,marquee,

> meta,noscript,object,param,script,style

= Where can I get PROfessional version or other Add-Ons? =

* [etruel.com store](https://etruel.com/downloads/category/wpematico-add-ons/).

= Where can I ask/see more questions? =

* [Ask in this page](https://etruel.com/my-account/support).
* [See tips and tutorials on this page](https://etruel.com/faqs/).

= Contributions =

We want to thank the WordPress.org plugins moderators as they helped us by marking the points to reinforce in the plugin to achieve even greater compatibility with WordPress in all the functions of our plugin.

You can contribute with WPeMatico:

Don't hesitate to [give your feedback](https://wordpress.org/support/view/plugin-reviews/wpematico#new-post). It will help making the plugin better. Other contributions (such as new translations or helping other users on the support forum) are welcome !

[nikolovtmw](https://bitbucket.org/nikolovtmw/) has reported the Deprecated Synchronous XMLHttpRequest and provided the code to solve it! Thanks!

Features supported by Thomas from [Invade It](https://www.invadeit.co.th): Filter images by width or height.

Eli from [Eli the Computer Guy](https://www.elithecomputerguy.com/). There’s an excelent video on Youtube [Use WPeMatico to Create a Free WordPress AutoBlog for Legitimate Purposes](https://www.youtube.com/watch?v=CX22kAeUKY8) 
Later I've added subtitles and added it above in this page.

Branco [WebHostingGeeks.com](https://webhostinggeeks.com/user-reviews/).  Romanian & Slovak languages files.

We are receiving tutorials in text, pdf, videos and ideas for current and new features. All are welcome. Thank you!
You can send your files to e-mail wpematico [at] etruel.com

== Changelog ==
= 2.6.20.1 May 26, 2023 =
* Fixes print credits on posts contents.

= 2.6.20 May 26, 2023 =
* Bump to WP 6.2.2
* Added 'View feed content' link inside feed address field.
* Added GitHub link on plugin links to allow easy access to development version.
* Added new filters and actions in the strip link options to enable new incoming functionalities.
* Some improvements on fetching functions and parsers.

= 2.6.19 Apr 5, 2023 =
* Compatibility with WordPress 6.2
* Fixes Settings blank page since WP 6.2.
* Fixes deprecated option for PHP 8.1

= 2.6.18 Feb 27, 2023 =
* Compatibility with WordPress 6.1.1
* Fixes new year desfase hours in compaign crons.

= 2.6.17 Nov 13, 2022 =
* Bump to WP 6.1
* Tested with PHP 8.1.9
* Now Requires at least WordPress 4.8
* Improved performance on load plugins page for extensions.
* Many styles tweaks to Extensions page.
* Tweaks and fixes on System Status file. 

= 2.6.16 Jul 15, 2022 =
* Tweaks: Changed banners and icons.
* Bumps to WordPress 6.0.1

= 2.6.15 May 05, 2022 =
* Compatibility with WordPress 6.0
* Improves options by checking activation of FIFU plugin on campaign/settings screens.
* Added dismmissible banner to use the Wizard to edit campaigns.
* Added [playlist] shortcode example to Post template feature to show all the audio/video media in post content.
* Added tip on No campaigns situation to link to getting started page.
* Improves plugin images by reducing sizes.
* Fixes style on welcome screen.
* Fixes Wizard styles.
* Fixes some warnings and notices on PHP 8.1+
* Changed all instances of Packages by Memberships.

= 2.6.14 Mar 9, 2022 =
* Added {feedfavicon} tag to post template feature.
* Compatibility with PHP 8+
* Compatibility with WordPress 5.9.1
* Fixes [WARNING] Undefined array key on fetching.

= 2.6.13 Jan 31, 2022 =
* Compatibility with WordPress 5.9
* Updated addons library updater.
* Fixes two PHP warnings on saving campaign.

= 2.6.12 Sep 27, 2021 =
* Fixes a security bug only available for admins on Campaign editing screens.
* Tweaks to the subscription form on the WPeMatico About welcome page.
* Tweaks on the nags style in WPeMatico Addons menu item.

= 2.6.11 Sep 15, 2021 =
* Fixes some PHP notices.
* Updated PHP Compatibility checks to 5.6 version.

= 2.6.10 Aug 19, 2021 =
* Updated Author data.
* Updated SimplePie Compatibility Test page.
* Fixes some PHP notices.
* Fixes banner styles in About page.
* Removed PHP Safe mode calls as has been removed as of PHP 5.4
* Custom Simplepie library and its option in Settings page will be removed in next version in favor of included in WP.
* PHP Compatibility bump to 5.6 as required by Simplepie.
* Compatibility with WordPress 5.8
* Requires WordPress version bumps to 4.7

= 2.6.9 May 5, 2021 =
* Added new filters to allow use external functions to upload the images or remote files.
* Added some campaign data in debug file to help with performance.
* Fixes all warnings and notices in debug file.
* Tested with WordPress 5.7.1

= 2.6.8 Apr 8, 2021 =
* Improved the post template feature on inserting {tags} in cursor position instead of the end of content.
* Fixes custom user translation when he chooses a different of WordPress admin.

= 2.6.7 Feb 17, 2021 =
* Added an option to allow to delete all debug logs of the campains in Debug Mode.
* Fixes a bug on saving images with incorrect post author.
* Fixes the issue #17 on php implode categories reported by diogenis1971 on Github.
* Fixes the issue #18 Max items allways saving zero on bulk edit campaigns.
* Some tweaks on campaigns options texts.

= 2.6.6 Nov 25, 2020 =
* Added a Warning notice at campaigns list on activating Debug mode.
* Some tweaks on styles of categories metaboxes.
* Some tweaks in the Debug screen with functions that do not work in Apache servers.
* Fixes a bug at fetching when save meta fields if inserting a thirdparty posttype could lose its value.

= 2.6.5 Sep 24, 2020 =
* Fixes and improves compatibility on wpematico_updates javascript file for WordPress Plugins screen.

= 2.6.4 Aug 20, 2020 =

* Fixes lost categories on saving campaigns in WP 5.5.
* Fixes an issue in campaigns quick and bulk edit for deprecated function in new jQuery.
* Fixes few issues on printed strings.
* Tweak styles on Welcome page to fit the new WordPress 5.5 styles
* Improved many strings for easy translations.
* Added many languages files. Some of them with automatic translations, but more easy to corrections.

= 2.6.3 Jul 30, 2020 =

* Ready for WordPress 5.5
* Compatible with the latest Simplepie 1.5.5 which will be updated in Wordpress 5.5
* We continue to improve the feed URL parsers when saving campaigns and on the feed reader screen.
* Tweak many styles to fit the new WordPress 5.5
* Improves the way to load the language files.
* Updated .pot file to latest text strings.
* Finished spanish translations.
* Added and partially updated Romanian, Dutch, German, Russian, Chinese, Persian & Slovak languages files.

= 2.6.2 Jul 22, 2020 =

#### _Bugfixes:_

* Fixes and improves uses of FIFU plugin to use their functions. 

#### _Recent (may break) changes:_

* Tweaks the feed URL parsers to include URLs with %20 in it.

= 2.6.1 Jun 25, 2020 =

* Tweaks on help texts on Featured Image from URL option in campaign.
* Fixes a bug introduced in last update of FIFU 3.1.3 version.
* Fixes some notices of null variables.
* Fixes many notices Deprecated: Unparenthesized notice for PHP 7.4+.

= 2.6 May 11, 2020 =

With a lot of changes, fixes and additions since 2.5 we have finally reach the 2.6 version, working with _Featured Image From URL,_ new multimedia extensions, new embed shortcodes for YouTube campaign types, avoid date filters an so on.  Take a look at previous changelog for more details.

#### _Bugfixes:_

> * Corrects the bug with the Excerpts option in the latest version by not allowing posts to be published.
> * {item_date} and {item_time} added to post template descriptions tags.

#### _Recent (may break) changes:_

> * **Enlarges the version required for the Professional addon to 2.6**

= Earlier versions =
For the changelog of earlier versions, please refer to changelog.md file or [the changelog on wpematico.com](https://www.wpematico.com/wpematico-changelog/).

= 0.1 Nov 17, 2010 =
* initial release in WordPress Repository


== Upgrade Notice ==

= 2.6.20 =
Compatibility with WordPress 6.2.2