== Changelog ==

= 2.6.20.2 Jul 21, 2023 =
* Bump to WP 6.3
* New video on WordPress main plugin page.
* Fixes get_favicon Fatal Error for XML campaign types (https://github.com/etruel/wpematico/issues/53)

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

= 2.5.3 May 09, 2020 =

This version launches a series of changes, additions and new features working with standard feeds, Youtube and media file extensions, plus a few fixes.

#### _Enhancements:_

> * Added support in Settings screen to select images, audio y video extensions allowed to upload with WP mime types as guide.
> * Added new options in Settings to allow show the Campaign ID in a new column in WordPress Posts (types) lists.
> * Added new option in campaign to allow bypass the feed date required conditions.
> * Added new free feature. We made WPeMatico compatible to work with Featured Image from URL plugin by Marcel Jacques Machado. (Thanks for the excellent plugin!)
> * Improved Youtube campaign types by use [embed] WP shortcode or Youtube shared iframe.
> * Added size with and height for inserted youtube videos. 
> * Added option to fill the excerpt field with the description of the feed item.

#### _Bugfixes:_

> * Fixes notice Deprecated: Unparenthesized notice with PHP 7.4+.
> * Changed Settings metabox name "Other Tools" to "WordPress Backend Tools" to better refer to what is it.
> * Fix images URLs with entities like &amp;to get it with correct name and replace the original in content.

#### _Recent (may break) changes:_

> * Added support in Settings screen to select images, audio y video extensions allowed to upload with WP mime types as guide.
> * **Enlarges the version required for the Professional addon to 2.6**

= 2.5.2.7 May 09, 2020 =

* Added option to fill the excerpt field with the description of the feed item. 

= 2.5.2.6 May 07, 2020 =

* Improved Youtube campaign types by use [embed] WP shortcode or Youtube shared iframe. 
* Added size with and height for inserted youtube videos. 

= 2.5.2.5 May 07, 2020 =

* Added new free feature. We made WPeMatico compatible to work with Featured Image from URL plugin by Marcel Jacques Machado. (Thanks for the excellent plugin!)

= 2.5.2.4 Apr 23, 2020 =

* Added new option in campaign to allow bypass the feed date required conditions.

= 2.5.2.3 Apr 21, 2020 =

* Fix images URLs with entities like &amp;to get it with correct name and replace the original in content.

= 2.5.2.2 Apr 11, 2020 =

* Added new options in Settings to allow show the Campaign ID in a new column in WordPress Posts (types) lists. 

= 2.5.2.1 Apr 10, 2020 =

* Added support in Settings screen to select images, audio y video extensions allowed to upload with WP mime types as guide.
* Fixes notice Deprecated: Unparenthesized notice with PHP 7.4+.
* Changed Settings metabox name "Other Tools" to "WordPress Backend Tools" to better refer to what is it.

= 2.5.2 Feb 13, 2020 =

Fixes an issue with Youtube campaign type by ignoring "Copy the permalink from the source" option.

= 2.5.1 Dec 22, 2019 =

Fixes a problem when sanitize keys on licenses page and tweaks the style just for the About screen. 

= 2.5 Dec 11, 2019 =

Version 2.5 concludes a series of radical changes that we had started since version 2.4 in terms of plugin security. Also following a strict revision to follow the standards of Wordpress coding development.

#### _Enhancements:_

> * We have completely removed the use of cURL and own functions in favor of Wordpress file processing and functions to obtain remote files.
> * We removed HTML entity decode contents by default before insert each post. You can activate the function again on Settings.

#### _Bugfixes:_

> * Fixes the loss of manually entered tags in the campaign when processing categories.

#### _Recent (may break) changes:_

> * Added an option in Settings to make HTML entity decode to the post contents before inserted to avoid possible xss from untrusted feed sources. Users who need (and want), can activate it from there. Props to [@nibiwodong](https://github.com/nibiwodong).
> * The removed cURL in many functions could work a bit different for some servers.  You should take a look if the images uploads are working well and if not, you can select the Custom Uploads option in Settings.
> * The external URL of CRON has changed since the previous version.  If your campaigns have been freezed for some time, it is because the Cron is not running.  Check out the settings for the new URL.
> * **Enlarges the version required for the Professional addon to 2.4**

= 2.4.2 Nov 19, 2019 =
* "app/wpe-cron.php" WAS DEPRECATED and removed from WPeMatico as external cron method.
* Removed cURL and file_get_contents calls from standard requests in favor of Wordpress http-api.
* Custom function upload allow partial uploads resuming downloads for big files. This must be activated in plugin Settings screen only if you need to use it.
* Custom Simplepie library can be activated in plugin Settings screen only if you have problems with the included library in Wordpress.
* Added options in Settings, in the Advanced metabox on sidebar to deactivate external requests for Extensions.
* Updated privacy Terms in readme file and inside plugin, About screen.
* Updated EDD plugin Updater for manage extensions licenses.

= 2.4.1 Oct 09, 2019 =
* Fixes a bug in the new method to add categories and tags to posts by cron.

= 2.4.0 Oct 08, 2019 =
* Improved from scratch the external cron processes.  If use external cron you should take a look at the new URLs in Settings.
* Added tag metaboxes on BBPress Topics post type.
* Improved the tags and categories insertion into the posts.
* Changed debug file printed constants to a limited whitelist of them.
* Fixes some issues in campaign edit js alerts.
* Fixes a php warning on settings page.

= 2.3.10 Oct 02, 2019 =
* Added descriptions of installed extensions in plugins page.
* Fixes some Warnings in Licenses page.
* Fixes error Uncaught Error: Call to a member function get_columns() on null for Settings page.
* More security enhancements.

= 2.3.9 Sep 26, 2019 =
* More security enhancements and minor changes to WP standards.
* Optimized some changes in the code for the new features we are preparing for version 2.4

= 2.3.8 Sep 22, 2019 =
* Incremented security on saving settings.
* Fixes a reported vulnerability that was only available to users who could access the WPeMatico Settings screen.

= 2.3.7 Sep 16, 2019 =
* Added on campaigns custom statuses without domains added to WP.
* Added wp filters to post status.
* Fixes an issue on getting the source chrset encoding.
* Fixes an issue on duplicates hash controls when jumping past items.
* Changes transient name of encoding_hosts to wpematico_encoding_hosts.
* Incremented transient cache time of encoding hosts to 6 hours.
* Many tweaks on the php code.

= 2.3.6 Jul 25, 2019 =
* Fixes some javascript issues for Pro users.
* Some tweaks in addons page.

= 2.3.5 May 14, 2019 =
* Fixes auto disabled Add Auto Categories checkbox on campaign edit.
* Fixes Must-Use Plugins info on System Status.
* Tweaks in Settings, System status texts and images.

= 2.3.4 May 01, 2019 =
* Added a Feed Viewer (Beta) under System Status tab in Settings.
* Fixes Wizard to work well with XML campaign types.
* Fixes Licenses screen to display correctly the expired and lifetime licenses.
* Fixes purchase links on extensions page to go to each product page.
* Fixes a Warning: count(): Parameter must be an array... on saving campaigns
* Fixes a Warning: count(): Parameter must be an array... on debug file.

= 2.3.3 Apr 05, 2019 =
* Fixes a Fatal error introduced in 2.3.2 with PHP 5.5 or less
* Tweaks in Settings, System status help texts.

= 2.3.2 Apr 02, 2019 =
* Fixes the parsing of filters in the Campaign Preview.
* Fixes some PHP Warning and notices on campaigns logs.
* Fixes an issue in Unfiltered Post Content feature.
* Tweaks on XML Campaign types to allow RSS Feeds when Simplepie does not recognize them.
* Tweaks in XML campaign type on authors and categories nodes.
* Tweaks the feeds validations in XML campaign type.
* Added a filter to allow parse the string gotten of the XML feed.
* Some tweaks to improve performance on campaign runs.
* Added Save Settings button in danger zone.

= 2.3.1 Mar 02, 2019 =
* Improves BlankSimplepie Class to work with the new addon: Synchronizer.
* Improves some cosmetic things, images and responsive styles.
* Improves performance in cURL requests.
* Added cURL version and recommended in debug page and debug file.

= 2.3 Feb 20, 2019 =
* **NOTE: Important changes. Highly recommended for testing in development environments prior to upgrade.**
* Added Debug Mode feature to save all campaigns logs instead only the last one. 
* Added smart notifications in campaign list.
* Added some new data in debug file.
* Added WPEMATICO_PREVENT_REDIRECT constant to prevent redirections to welcome page.
* Improves the code to allow better integration with a future item synchronizer addon.
* Tweaks in translation notification in settings.
* Tweaks in cURL requests on sending empty HTTP Encoding header for cURL +7.10.5.
* Updated Chinese language translations files.
* Updated Updater class to v1.6.18 version.
* Fixes the check feeds before save with XML Campaign type.
* Fixes an issue saving the Disable WPeMatico Credits option.
* Fixes some layouts in campaign edition using Chinese language.

= 2.2.3 Jan 21, 2019 =
* Added compatibility to import the XML files from URL o local folders with WPeMatico Exporter Addon.
* Fixes some issues on xml files processing.

= 2.2.2 Jan 15, 2019 =
* Added filters to allow import posts from xml files of WPeMatico Exporter Add-on.
* Added filters to insert categories automatically from xml files of WPeMatico Professional Add-on.
* Fixes compatibility with PHP 5.3 (again)
* Fixes compatibility with cache of DB when is inserting new categories. 

= 2.2.1 Jan 13, 2019 =
* Fixes compatibility with PHP 5.3 after introduced a method used by PHP 5.5+

= 2.2 Jan 10, 2019 =
* Added XML Campaign type to parse and fetch XML feeds.
* Added support to allow uploads of XML files to the media library to be used as URL in campaigns.
* Tested and improved compatibility for xml with Addons Professional, Manual Fetching, Full Content, Polyglot, Thumbnail Scratcher and so on.
* Some tweaks in Campaign Preview.
* Some improvements in source code and css styles.
* Tested compatibility with WordPress 5.1b.
* **Enlarges the version required for the Professional addon to 2.1v** (https://etruel.com/downloads/wpematico-professional/)

= 2.1.2 Nov 14, 2018 =
* Added wp filters to parse categories and autocategories before inserted them in posts.
* Improved some texts on code and filter descriptions.

= 2.1.1 Sep 20, 2018 =
* Fixes an issue on the delete button of the rewrite metabox.
* Tweaks some texts on System status screens.
* Updated .pot file
* Added Chinese language

= 2.1 Aug 29, 2018 =
* Added some data of feed in the feeds tester.
* Tweaks on redirect to the welcome page when updating.
* Tweaks on add more feeds to campaign javascript methods.
* Fixes an issue on {item_date} and {item_time} template tags.
* Fixes auto category description on quick add inside campaign.
* **Enlarges the version required for the Professional addon to 2.0v** (https://etruel.com/downloads/wpematico-professional/)

= 2.0 Aug 1, 2018 =
* New major version! We're introducing the new Robotico 2.0!
* Many code improvements. Graphics designs and cosmetic changes.
* Added a New Campaign type to publish to BBPress forums as forums, topics or replies.
* Added a preview template for the Youtube campaign types with options to include/exclude image and descriptions of videos.
* Added options to manage duplicated posts individually by campaign.
* Added option to disable the description of categories created by WPeMatico.
* Added some hooks to handle the cron schedules of WPeMatico.
* Updated pot file to translate well the plugin.  Our goal for the next versions is brings wpematico in your language!
* Some fixes in add-ons page.
* Some fixes in licenses page, style and messages.

= 1.9.4 May 04, 2018 =
* Added a new option to use as category the word most used into Word to Category feature.
* Added scheduled crons in System status tab.
* Updated Updater class to v1.6.16.
* Fixes PHP notices on debug page.
* Fixes Strip featured images with '*' in their names.

= 1.9.3 Mar 20, 2018 =
* Fixes some issues added in previous version on parser images. (Sorry)
* Fixes an issue on set title to source permalink.
* Fixes some responsive styles in the Addons/Extensions page.
* From now, option "Disable WPeMatico Credits" will be active by default.

= 1.9.2 Mar 14, 2018 =
* Fixes an issue on uploads videos and audios in some cases.

= 1.9.1 Mar 10, 2018 =
* Added a new wp filter to the source image name into the fetched content.
* Added some filters to allow other images parsers.
* Tweak avoid attachs an already attached file if it's not necessary.
* Tweaks the chrset encoding autodetect. 
* Tweaks on images processing and parsers.
* Fixes untranslatable tabs in help of campaigns list.
* Fixes some PHP notices on campaign edit.
* Fixes an issue in the Error Handler.
* **NOTE: Highly recommended to test it in development ambients before update**

= 1.9 Jan 31, 2018 =
* Added campaign preview feature.
* Added compatibility to use the new Manual Fetching Addon.
* Added error message when fails the run of campaign.
* Added some improvements of performance on uploading files.
* Added 'convert character encoding to UTF-8' feature.
* Added compatibility to use Cookies HTTP from Professional Addon.
* Some tweaks in helps of campaign editing screen.
* Fixes an issue on 'Copy the permalink from the source' feature.
* Fixes an issue calling campaigns_edit styles deprecated in previous versions.

= 1.8.5 Dec 4, 2017 =
* Added 'Start Campaigns' and 'Stop Campaigns' on the bulk actions of Campaigns list.
* Fixes the 'shift+click' issue to select campaigns on campaigns list.
* Removed the notices of SimplePie about 'Duplicate Class' on the 'Check Feeds before Save'.
* Tweaks the campaign logs and help messages about 'Featured Image Selector' feature.
* Improves the CSS code.

= 1.8.4 Nov 21, 2017 =
* Added some filters on checking fields when saves a campaign.
* Added the (previously ignored) force feed option when checks a feed and save campaigns.
* Added an own custom user agent and filters to change it.
* Some tweaks and filters on fetching feeds parameters.

= 1.8.3 Nov 12, 2017 =
* Fixes some issues on delete and update functions in the addons page.
* Fixes an issue on duplicated youtube metabox in campaign wizard.
* Some new filters in the images options metabox for new features in future releases.
* Tweak to show the force feed notice only for non professional addons.

= 1.8.2 Nov 3, 2017 =
* Added the option in settings to use the alternate WP Cron.
* Added improvements in the CSS style of the pages.
* Added YouTube as Campaign Type to give the posts a preformatted content.
* Added YouTube metabox in the wizard on selecting in Campaign type.
* Many improvements in the responsive styles.
* Fixes the problem of two clicks to update a campaign.
* Fixes a problem when trying to save a campaign from the wizard.
* Fixes a problem when deleting a feed in the list of feeds.
* Fixes the feeds counter at bottom of the feeds list when adds or deletes rows. 
* Fixes a problem that Delete Hash did not run in the campaign edit.
* Fixes a problem that ignored the response to the button confirmation of the Campaign Control Panel.
* Fixes a problem that could redirect indefinitely on plugin activation.

= 1.8.1 Oct 25, 2017 =
* Added "Find in title" option in the Words to Categories feature.
* Tweaks in the Campaign control panel behavior in new campaigns.
* Tweaks in the Settings page to use the standard WordPress’ UI: Meta Boxes.
* Fixes "Draft" status in new campaigns.

= 1.8 Oct 20, 2017 =
* **Major release - Recommended to test it in development ambients before update**
* Added a Campaign Control Panel with campaign info inside the editing screen.
* Added wpe hooks (for developers) to use filters and actions from JavaScript.
* Added nags updates individually for extensions.
* Many tweaks and fixes in the Campaign Wizard.
* Many tweaks in the Campaign Logs.
* Many tweaks to the debug page:
* - Adds notice to updatable plugins.
* - Adds Hard disk total and free space.
* - Adds required PHP extensions and Apache Mods.
* Fixes the blank messages on errors when the feeds are tested.
* Fixes few javascript issues on campaigns editing.
* Fixes tha jQuery Synchronous XMLHttpRequest issues on campaigns editing.
* Updated included external SimplePie library to 1.5 version.
* **Professional Add-on:**
* **Enlarges the version required for the Professional addon to 1.8v** (https://etruel.com/downloads/wpematico-professional/)
* Added name spaces for feeds with Custom Tags to be used in the Campaign Template and Custom fields.
* Added a fixed icon for Feed Advanced Options in the feed row.
* Added an option to Force Feed when Simplepie gives error. Find it in the Feed Advanced Options Popup.
* Fixes the numeric name in the file for an exported campaign.
* Fixes the malformed JSON in the content sometimes when export campaigns.

= 1.7.3 Aug 25, 2017 =
* Adds new filters for new custom features.
* **Professional Add-on:**
* **Enlarges the version required for the Professional addon to 1.7.4v** 
* Added new feature Custom Feed Tags.
* Added new feature to assign Parent page for campaigns inserting feed items as pages.
* Added new feature to strip images with incorrect content.
* Added new filters to allow the user manage the HTML tags stripped by a campaign.

= 1.7.2  Aug 9, 2017 =
* New feature to choose an image by order in the source article.
* New filters to process the featured image by attachment id.
* Some tweaks in the campaigns logs for images, audios and videos when fetchs an item..
* Fixed an issue on path redirections in the location header.
* Fixes a bug when deactivate the WP Cron.
* **Professional Add-on:**
* **Enlarges the version required for the Professional addon to 1.7.3v** 
* New feature to avoid upload the Default image again and again.
* Fixes a problem in campaigns import.
* Added plugin version validation to update the campaigns if required.
* **Full Content Add-on:**
* Redesign and lot of improvements in the Config Files Editor screen. Settings, Full Content tab.
* Adds a class Inspector to get the xPATH for the correct content in each domain.
* Adds the feature to insert a new blank txt config file from scratch.

= 1.7.1  Jul 11, 2017 =
* Fixes a bug for PHP < 5.4 introduced in previous version.
* Added support for images with 'picture' html tags with srcset attribute.
* Added filters to allow professional version to upload big file sizes by ranges for the new video/audio features.
* Added some video tutorials in FAQs section.

= 1.7.0  Jul 7, 2017 =
* Added support for mp3 audio and mp4 video files in the feed contents. Integrated with Wordpress standards, link or attach files to a post.
* Added new configurations for images inside every campaign to overwrite general settings.
* Fixes a bug on images with srcset attribute.
* Fixes a bug with Warnings on second check of duplicated posts that brokes the fetching process.
* **Recommended to test it in development ambients before update**
* **Professional Add-on:**
* **Enlarges the version required for the Professional addon to 1.7v** 
* Added support for audio and video file types allowed by WP (<mp3, ogg, wav, wma, m4a> <mp4, m4v, mov, wmv, avi, mpg, ogv, 3gp, 3g2>)
* Added the feature to get the audio and video files from the feed enclosures and podcasts.
* Added some features for audio and video files:
  Strip the queries variables in the URLs of audio and video links.
  Audio and video filenames renamer.
  Strip audios and videos html tags from the content.

= 1.6.4  Jun 6, 2017 =
* Added a wizard to create a campaign!
* Added feature "Order feed items by Date before process".
* Added support for images with srcset attribute in their tags.
* Added support for images with optional or missed protocol in their URLs.
* Fixes a problem to verify SSL on feeds URLs with https.
* Fixes an issue with strip script tags from articles contents.
* Fixes an issue with external cron when the log file was enabled.
* Fixes an issue with white spaces in the url of the images.
* A subscription form to our news was added in the welcome page.

= 1.6.3  April 28, 2017 =
* Debug Info in Settings is now System Status tab with new sections inside.
* Added new table with info and system requirements in System Status tab.
* Added many tweaks in the debug file.
* The Wordpress reviews are back in the Settings page. 
* Fixes an issue on running the controls by metafields for posts duplicated.
* Fixes an issue with Ultimate exclude categories plugin to use it with WPeMatico.
* Some fixes in many texts and tips.

= 1.6.2  April 6, 2017 =
* Improved accuracy in duplicate checking.
* Update SimplePie timeout to 30 sec.
* Added `See local Addons in plugin list` option.
* Added feature to load language packs through translate.wordpress.org

= 1.6.1  Mar 12, 2017 =
* Fixes an issue that the post template tags don't works in some cases.
* Fixes the option Simplepie stupidly fast always set to true, but set to true as initial value.
* Tweak: Added a new filter to allow add new tags to the template.
* Tweak: Improved the filters for the new duplicate checks option by metafields.

= 1.6  Mar 9, 2017 =
* Added the option to choice a parent category for the Autocategories feature.
* Added {item_date} and {item_time} tags to the post template.
* Fixes issue with Copy campaign quick action.
* Added a metabox in the post types published by WPeMatico with relevant data.
* Tweaks in duplicates validation to improve performance by almost 80%.
* Tweaks in Addons page in the buttons permalinks.
* Added an option "Add an extra duplicate filter by source permalink in meta field value". 
* Updated included SimplePie library to 1.4.3 version.
* Tweaks by default in Settings now is checked Set Simplepie stupidly fast Option.
* Tweaks showing what version of Simplepie are used by the plugin.
* Added 'wpematico_addcat_description' filter to change the description Auto Added by WPeMatico in new categories.
* Deprecated filters wpematico_post_template_tags, wpematico_post_template_replace, will be removed on version 1.7.
* Updated Updater class to 1.6.11v.
* **Enlarges the version required for the Professional addon to 1.6** 
* **Recommended to test it in development ambients before update**
* **Professional Add-on:** 
* **New Feature: Added Random Rewrites for work like synonyms.**
* Tweak: New option to add rel="nofollow" to links.
* Tweak: New option to use tags from the feed with <tag> if it exists.
* Tweak: Added bulk campaign import/export feature on bulk actions above the campaigns list.

= 1.5.1  Jan 31, 2017 =
* Fixes issue with Strip all links feature without selected sub-options.
* Fixes the CSS warning class when an addon was succesfully updated.
* Updated Updater class to 1.6.10v.

= 1.5  Jan 25, 2017 =
* Added option to make the slug by copying it from the source permalink.
* Added options to the strip links for a, iframes and script tags.
* Tweaks on feeds metabox and adding some filters to allow new professional features.
* Tweaks on post template metabox.
* Added filters to allow Professional version inserts new features before and after post template.
* Added filters to allow Professional version inserts the authors from the feed items.
* Added a fix to use with the ACF PRO Plugin, that breaks the campaign editing screen.
* Hides "Strip Links" option if "Strip all HTML Tags" option are checked.
* Fixes the strip links behavior to avoid strip also the iframes.
* Fixes the update-core for bulk plugin updates.
* Fixes the Clean Trash button tool behavior.
* Fixes a minnor bug with license keys form inputs.
* Improvements to the system on checking duplicated posts with encoded titles.
* Updated Updater class.
* **Enlarges the version required for the Professional addon to 1.5** 
* **Recommended to test it in development ambients before the update**

= 1.4.2 =
* Fixes decoding html entities from the titles and post contents to UTF-8.
* Fixes titles by getting especial characters before convert them to UTF-8
* Tweaks the query of the campaigns to run the cron.

= 1.4.1 =
* Fixes to save the posts as UTF-8 without html entities. (For international characters)
* Fixes a PHP Warning that broke the licenses handler.
* Fixes a PHP Warning at campaign running for PHP version lower than 7.0
* Fixes a conditional in addons page that could allowing to print an extra column in other plugins pages.
* Fixes a PHP Warning on Media page.
* Fixes the notices for old PRO users to allow access to Campaigns and Licences page.
* Some tweaks on the links in the Plugins page.

= 1.4 =
* Improvements on Settings page in checkboxes for images functions and Help texts.
* Added some Wordpress filters and actions to Settings metaboxes.  
* Fixes the parser for html entities in the title for some wrong formatted feeds.
* Improvements on running campaigns with php timeouts, value given by the plugin settings.
* **Improvements in some functionalities of the campaigns list.**
* The clock above with current date time is now alive by javascript.
* Added Help Tab with descriptions and tips of every thing.
* Background Color on selecting campaigns. 
* Added a column in the campaigns list with the campaign type.
* Removes the unuseful filters in top of the campaigns list.
* Moved the cron scheduler to its own metabox. (again)
* Added a Select field with some predefined schedules for easy setup the cron.
* New classes done from scratch for the management of the addons licenses.
* Fixes different issues on uploading Addons.
* Fixes a warning when activates some addons at same time.
* Added new filters by campaign types.
* Tweaks on how to load the Bulk Actions.
* **Enlarges the version required for the professional addon to 1.4**
* **Professional Add-on:** 
* Improves the Pro options for Images Metabox.
* Improves some filters to make featured the RSS images.
* Added an option to try to handle cases where images are delivered through a script and the correct file extension isn't available.
* Fixes the Image rename feature when the image extension is missed, by adding '.jpg'
* Fixes by adding the Featured Image as empty string to the post content when there is not a featured image.
* Improvements on Custom function for uploads.
* New feature to overwrite, rename or keep the duplicated images by names.

= 1.3.8.4 =
* Improved getting the source permalinks and redirecting to the source sites.
* Added a new post template tag to print the original feed content: {itemcontent}
* Fixes the timeout banner in campaigns list that was missed in last version.
* Fixes the sizes of the buttons with the new fonts introduced in WP 4.6.
* Fixes a parameter with a filter for post titles.
* Some improvements on addons Page.

= 1.3.8.3 =
* Fixes a wrong parameter in an image name filter.
* Extends the functionalities with some custom filters.
* Ensures compatibility with the latest WordPress version.
* **Enlarges the version required for the professional addon to 1.3.8**
* **Professional Add-on:** 
* Adds new features: Rename the images uploaded to the website.
* Keyword Filter for source item categories.

= 1.3.8.2 =
* Tweaks on duplicate checking by item hash when get timeouts on running campaign.
* Tweaks deleting options on uninstalling the plugin.
* Many fixes on processing and showing Next Run Cron time across the screens.
* Fixes notices on checking if a campaign are running.
* Add some reference links inside Help screens to go to the FAQs.
* **NEW ADDON** : **[WPeMatico Thumbnail Scratcher](https://etruel.com/downloads/wpematico-thumbnail-scratcher/)**
* Find images on search engines automatically.

= 1.3.8.1 =
* Fixes Welcome screen on upgrading the plugin.
* Fixes missing fields that were generating errors or crashed campaigns when running.

= 1.3.8 =
* Added New Feature Campaign Types to improve the plugin and the addons.
* New Feature to work with **Youtube standard feeds.** First approach.
* Added Dashboard page to access to Last News and "Getting started" texts.
* Added New filters to allow new Campaign Types.
* Added new parameter $item to the filter 'wpematico_inserted_post'.
* Fixes an issue on a filter uploading the featured images.
* Fixes some issues getting images from text and stripping img tags from html.
* Fixes an issue with some themes that hide the 'clear' CSS class in metaboxes.
* Fixes some other minor bugs.
* **NEW ADDON** : **[WPeMatico Facebook Fetcher](https://etruel.com/downloads/wpematico-facebook-fetcher/)**
* Allow publish posts from Facebook pages or groups in every campaign with Fb APP Credentials.

= 1.3.7.2 =
* Added dinamic search for categories in campaign editing.
* Fixes some errors on Addons Page with the new SSL certificate on etruel.com
* Fixes Custom SimplePie Class error when other plugins load the library included in Wordpress.
* Fixes variable notices.
* Some tweaks on debug file.

= 1.3.7.1 =
* Fixes the order of some filters when is taking the date.
* Some tweaks on readme file.
* Added domain to plugin header for translations.
* **Full Content Add-on**
* Added a feature to get the featured image from open graph or twitter image from source code.
* Fixes few filters that overwrite some campaign options.
* Fixes the bug that gets the full content only in manual mode.

= 1.3.7 =
* Fixes the WP_kses filters stripping videos for activated campaigns.
* Fixes a bug related to uploads of the featured images.
* Fixes the order of many parsers and filters for contents with images.
* Fixes a bug with regex on post-template for adding the image in the content.
* Improves the behaviour on Play/Run-once button in the campaigns list.
* Improves performance on gettings redirections of feed permalinks.
* Added a Welcome screen on plugin activated/updated. Thanks to EDD for its welcome file.
* **Professional Add-on**
* New improved function to add the custom featured image.
* Improves the behaviour on cutting text with featured images.
* Fixes double display of categories in Quick edit actions.
* Fixes adding the featured images to custom fields.
* Updated Plugin Updater class.
* **Full Content Add-on**
* Change the order of the filter to get the full content.
* Updated Plugin Updater class.
* **Full Content** and **Professional Add-ons** must be updated to this version.

= 1.3.6 =
* Added a Password in plugin settings to run the external cron. Deactivated by default to backward compatibility, but strongly recommended.
* Added options to delete all plugin data when is Uninstalled.
* Added SimplePie section in debug file to test server compatibilities.
* Some Cosmetic tweaks on List of All Campaigns.
* Fixes on settings tabs. Licenses and Debug Info always at end.
* Fixes a bug restoring the Post Format value on quick edit campaign.
* Fixes a bug in trash to restore deleted campaigns.
* Fixes adding the featured images at the beginning of the content when don't find images.
* Many readme Tweaks (this file).
* We continue improving new icons and banners.
* We've replaced the ugly machine by a friendly robot. Robotico. :-)
* Improves a cache for the wordpress reviews shown on Settings screen.
* **Full Content Addon**
* Added feature to read complete content for multi-page articles.
* Added feature to get the title also from source web page instead of feed.
* Added feature to get the date of the post from the source web page instead of feed.
* Added feature to get the author also from source web page and optionally create it if not exist.
* Added if gets empty full content then takes the original feed item content.
* Updated Commands Reference in Help with examples.
* Added around of 1000 config files for predefined websites.

= 1.3.5 =
* Added a Wordpress filter to integrate more default options to base plugin.
* Added a filter to allow add help texts to campaign editing.
* Fixes an issue on wpematico_pre_insert_post filter.
* Fixes an issue that made a PHP notice on posts created by a deleted campaign.
* Added Rewrite notices to campaign logs.
* **New Addon [Publish 2 Email](https://etruel.com/downloads/wpematico-publish-2-email/)**

= 1.3.4 =
* Added Extensions menu as subitem of WPeMatico.
* Added link of feed url in campaign editing.
* Tweaks on notices for old versions of Addons.
* Fixes saving html special chars on Word to category text field.
* Fixes saving html special chars on "Rewrite to" textarea field.
* Fixes checking corrects RegEx on Rewrites Origin field.
* Some cosmetic fixes and more tips in Campaign Help tab.
* **Professional Addon**
*  Fixed a debug notice with enclosure images.
*  Some cosmetic tweaks on Custom Fields metabox.
* **Full Content Addon**
*  Updated content extractors to last versions.
*  Better support for videos.
*  Added support to get iframes, objects and embeds with videos.
*  Added some wordpress filters to allow add more allowed video sites, besides html tags to check by code.

= 1.3.3 =
* Added feature to change the order of the URLs on feeds list using drag & drop.
* Compatible with Wordpress 4.5.
* Colored meta boxes titles on campaign editing.
* Many tweaks on Post Template Metabox to display the Help.
* New data on Add-Ons page showing all Add-Ons currently installed and Add-Ons On sale on etruel's store.
* Fixed - when saving Word to category and Rewriting fields for bad strip / slashes.
* Fixed - Footer displayed on the Settings page.
* Fixed - Settings are not deleted when uninstalling the plugin.
* Some other tweaks and improvements.
* **Professional Add-on:** 
* Enlarges Professional version to 1.3 (https://etruel.com/downloads/wpematico-professional/)
*  Added a feature to Keywords Filters to take one or all words to skip/keep a post.

= 1.3.2 =
* Added "Bulk Edit" for campaigns in list of campaigns, like Wordpress standards. (Just a few fields but more to come ;)
* Added support for standard categories, tags and formats on all Post Types.
* Fixes AJAX updates for WPeMatico Add-Ons Page under Plugins Section.
* Some tweaks on campaign logs.
* **Professional Add-on:**
* =Added support for Custom taxonomies on Custom Post Types.=
* Fixed a mistake in last version that has disabled automatic updates.
* Some tweaks on campaign export/import.

= 1.3.1 =
* Improved external cron on wpe-cron.php
* Fixed a featured image upload when cache images are deactivated. Now, it is possible to upload & attach only the featured image ignoring the others.
* [PRO]
* Improved getting images from enclosures and media fields of feed items.
* Added Feature Export/Import single campaign. Must be activated on PRO Settings.
* [/PRO]

= 1.3 =
* Mayor upgrade, should be tested before upgrade on a live environment site.
* Many improvements to schedules cron functions.
* Many improvements on filters and content parsers and their process order.
* Many improvements on featured images and in-content images.
* Added feature to Remove Featured Image from content to avoid duplicated images on display.
* Added option to follow redirects on URLs to get real source permalink.
* Moved option Strip All HTML tags of content from PRO Version to Free.
* Campaign editing hides Categories and Post Formats Metaboxes if Post Type value differs to Post.
* Fixed - Post Formats Metabox will be shown only if current theme supports it.
* Fixed - Notice headers already sent by settings_page.
* Fixed - PHP warnings on Campaign See Last Log quick action and wpe-cron.php.
* Added - Recalculate next cron to Campaign Reset Quick action.
* Added a page for WPeMatico Addons to separate and hide these addons from Wordpress plugins page.
* Added help texts and tutorials on Wordpress Help tab on top-right corner to campaign and Settings Pages.
* Added an option on Wordpress Settings->Writing to hide Wordpress Reviews metabox on Settings pages.
* New Images and icons :-)
* [PRO] must download versions 1.3 [PRO](https://etruel.com/downloads/wpematico-essentials/)
* FULL Content AddOn:
*  Separated from PRO as new Add-On to obtain better performance. You must Download the new add-on!!
*  Merged with TXT file Editor add-on for FREE, adds an editor for config files for every domain!
*  Also now you can move the config files of each source site to the upload folder to prevent files from being deletedwhen upgrading plugin.
* **Professional Add-on:**
*  Added feature to skip a post if there’s no image in content.
*  Added feature Image URL Cleaner.
*  Added feature Strip all Images from content.
*  Fixed some lost images for upper chars.
*  Hide PRO Settings from Menu and leave just a tab into Settings.
*  Fixes when save custom fields values on escaping for HTML attributes.

= 1.2.6 =
* Added a Section on Wordpress Settings->Writing to allow changes to WPeMatico Menu position.
* Fixed metadata filters for unsaved custom fields. 
* Better process an error response on wp_remote_request on non WP_error objects.
* [PRO] you must download version 1.2.6 [PRO](https://etruel.com/downloads/wpematico-essentials/)

* [/PRO]

= 1.2.5.2 =
* Removed SMTP mail method and added as Add-on.
* Fixed Settings page for servers without mcrypt PHP extension. 
* Added Filters and Actions for new Add-ons.

= 1.2.5.1 =
* Fixed white screen on Settings page when uses PHP < 5.4
* Added Wordpress filters to every item inserted on new posts. 
* [PRO] you must download version 1.2.5.1 [PRO](https://etruel.com/downloads/wpematico-essentials/)
* Added 'Strip HTML Tags From Title' option to Campaign Custom Title Options.
* [/PRO]

= 1.2.5 =
* Improved uploads and automatic filenames.
* Fixed attachments to Wordpress media when uploading already existing filenames.
* Fixed Featured images and image urls with '%' in its paths.
* Fixed rawurlencode of feeds with spaces in its url.
* First attempt to avoid false positives on malware detection by removing encoding as base64 of smtp password. This means if you are using SMTP for emails you must input and save the password again... thanks mediocre antivirus...
* Added new filters to fix "Post title links to source" on TwentyFifteen and other new themes.
* Added .jpeg extension to allowed files to upload.
* Fixed saving Word to category RegEx fields.
* Fixed checking Regular expressions on save campaign.
* [PRO] you must download 1.2.5 version [PRO](https://etruel.com/downloads/wpematico-essentials/)
* Fixes saving Keyword filters.
* [/PRO]

= 1.2.4.1 =
* Fixed - when file extension is not allowed strip the image link to source if it is selected.
* Fixed - now image extensions can be uppercases too.
* Added wordpress filter to change files upload by extension.

= 1.2.4 =
* Fixed, sometimes checkboxes don't save on campaign quick edit.
* Several tweaks and improvements.
* Divided Settings into new tabs system.
* Rethinking images improves on downloading and uploading files, timeouts and server loads.
* Added wpematico_img_src_url WP filter to allow modifications before downloads. (ex: to get full image instead of thumbnails)
* Added Debug Info tab on Settings.
* Removes smtp credentials values when selected PHP:mail as send method in Settings to avoid pass saving window on browser.
* [PRO] you must download version 1.2.4 [PRO](https://etruel.com/downloads/wpematico-essentials/)
* Fixed - Author per feed url not saved.
* Integrated PRO Settings and Licenses screens in tabs inside WPeMatico Settings item menu.
* Fixed a bug with var that may delete tags in some cases.
* Better performance on getting remotes files.
* New: Now with "Cut at" left first image of content as featured image.
* [/PRO]

= 1.2.3 =
* Some minor fixes.
* Removed Quick edit when campaign is running.
* Fixed a critical bug when attempting to get full content on PRO Version.
* Fixed forced autotags if tags field are blank on PRO version.

= 1.2.2 =
* Added a clock above campaigns list. Useful for tests with crons.
* Fixed a critical bug when sending an email after fetch a campaign.
* Fixed reset stats to 0 when saving a campaign. Now, you can reset it with quick action on campaigns list.
* Improved use of bad tags in auto tags feature on PRO version.

= 1.2.1 =
* New option to set a throttle between inserted posts to give a break for small servers.
* Added better control on checking php 5.3 as requirement.
* Added custom function to get plugin version to fix Warning: fopen(), fread() and fclose().
* Tweaks to automatic upgrades with license key on PRO Version.
* Fixed permalink filter on Post title links to source feature.

= 1.2 =
* Mayor upgrade. Backup your database and deactivate PRO version (for last time) before upgrade! 
* Fixed: Quick edit for some lost fields values and bad columns after save.
* Fixed: Rewrite input fields not saved in campaign.
* Fixed: Almost all PHP notices were taken off.
* Fixed: external cron broken call wpe-cron.php.
* Fixed: columns data and order on campaign list.
* New: Option to disable saving campaign custom fields on every post.
* New: Option to allow external cron for WPeMatico without deactivate all WP Crons.
* New: Option to write a log file for external cron.
* New: Log file for external cron will be saved in the uploads folder, otherwise, it will try to save it to the same dir (app).
* Improved fetching Videos from FEED content.
* New: Added Option to show a button for empty trash on all (custom) post types you want.
* Fixes correct encode decode SMTP email password.
* New: Default values on Settings to Sender email and name for logs.
* Fixed: Better-sanitized image names before save using WP filters.
* Fixed: dequeue script Autosave.
* [PRO] must download version 1.2 [PRO](https://etruel.com/downloads/wpematico-essentials/)
* New feature: License key to launch automatic upgrades.
* Automatic Upgrades from wordpress plugins page.
* Removed the hated requirement to upgrade: Remember deactivate PRO version before upgrade. That is not necessary now.
* Fixed: website will not crash when upgrading the free WPeMatico plugin or when it has not been activated yet. 
* Fixed: get automatically Image URL when selecting an image from the gallery as the Default Featured image. 
* New: Checked required versions to run with free version.
* Removed 1 min. cron feature and HTML lawed for better performance.
* [/PRO]

= 1.2 Beta =
* New Feature: Post Format for campaign posts.
* New Feature: Quick Edit in campaigns list.
* New options for SimplePie Filters on Settings. Strip attributes and other interesting things.
* New tips help you to get a better layout on Settings and campaign edit pages.
* New system message allows some new notices through plugin actions.
* Added checked categories in a campaign to the top of the list.
* Added option to jump and continue fetching a feed when a duplicate is found.
* Added option “Pending status” to campaign posts.
* Added control to save data before allowing to run the Campaign.
* Added log file to external cron.
* Improvements - use Wordpress core Categories function for metabox instead of custom function.
* Improvements to check url feeds when getting an Error on true feeds.
* Improvements in ajax funtions checking url feeds.
* Removed deprecated tools page to import old campaigns of plugin 0.xx versions.
* Fixed page name for Settings Page to avoid overwriting other setting pages.
* Fixed saving selected author in a campaign.
* Fixed Quick add campaign in category checked after saving.
* Fixed - Reset Campaign also clears last Log.
* Changed and improved - file_get_contents to wp_remote_get that uses multiples methods to get remote images 

= 1.1.96 =
* Fixed tags issue reported on forums after last upgrade.

= 1.1.95 =
* Added some filters to make plugin upgradable by modules or add-ons. This is just the beginning.
* Added support for autotags in chinese language. Take in mind that there is not perfect, but better than nothing ;)
* Some minor fixes on PHP Warning when running a campaign in manual mode.
* Fixes campaign log page to works also on WordPress Multitenancy environments. (Thanks Relevad)

= 1.1.94 =
* Some minor fixes to show uploaded images with urldecode in its filename.
* Fixes a bug seeing log of campaign.

= 1.1.93 =
* Some minor fixes. Droped many PHP notices.
* [PRO] must download version 1.1.93 [PRO](https://etruel.com/downloads/wpematico-essentials/)
* New feature: Filter Featured image by width or height on every post.
* [/PRO]

= 1.1.92 =
* Tested with Wordpress 4.2alpha
* Some minor fixes.
* Some fixes and descriptions on Readme file :)
* [PRO] must download version 1.1.92 [PRO](https://etruel.com/downloads/wpematico-essentials/)
* New feature: Now you can filter images by width or height in new posts.
* [/PRO]

= 1.1.91 =
* Fixes and improved methods to download remote images on RSS feeds.

= 1.1.9 =
* Compatible with php 5.4.  Really lots of Strict Standars PHP Warning and notices fixed.
* Wordpress 3.8 compatibility.
* New methods for check duplicated posts(types). !!!
* Improved methods to download remote images on RSS feeds.
* Improved method to save custom meta field with source permalink.
* Fixes disabling WP autosave feature php notices.
* Fixes - Content-Type to UTF-8 on Last Log campaign popup window.
* Some cosmetic fixes.

= 1.1.8 =
* New feature: Now you can rewrite part of the Post titles, too.
* Wordpress 3.7 compatibility.

= 1.1.7 =
* Fixes for duplicated image uploads that happened -in some cases- with PRO Version installed.
* Fixes for some images sizes that were not generated when attaching files to posts.
* Some minor changes.
* [PRO] must download 1.1.7 version [PRO](https://etruel.com/downloads/wpematico-essentials/)
* Fixes - + "rare" code removal that was displayed on log when fetching rss images.
* Fixes - KeywordFilter when selecting both title and content for search on "any" field.
* Added - file getcontent.php for custom edit and use with CURL if needed
* Added - tags for custom title feature: {title} and {counter} – it will be replaced on custom title field.
* [/PRO]

= 1.1.6 =
* DEPRECATED functions replaced to make the pluggin more compatible with WP3.6.
* Some minor changes.
* [PRO]
* Fixes to autotags feature that did not work in some cases.
* [/PRO]

= 1.1.5 =
* New feature to allow or not posts duplicates.
* Fixes - categories and tags added to posts. 
* Optimised screenshots to reduce the plugin size.
* Some styles improvements.
* Thanks for your donation, Mark :)

= 1.1.4 =
* Removed a link in a PHP comment that was taken as malware.

= 1.1.3 =
* Added - timeout on running campaigns. This feature allows to automatically Clear/Stop halted campaigns after some period of time.
* Fixes to the use of wp_create_category and wp_insert_category functions that sometimes did not work.
* Fixes to some styles that were working only on Firefox. 

= 1.1.2 =
* Added New feature: Now you can run all your campaigns (or any selected campaign) at once on the campaigns list.
* Improved some ajax notifications.
* Fixes to some styles that change with WP3.5.
* Added an example of images usage with [gallery] shortcode on post template.
* Upgraded package SimplePie to version 1.3.1.
* Added SimplePie: Server Compatibility Test 1.3, for check if the plugin will work on server.
* Fixes - loading localization textdomain.
* Fixes - some issues with I18n.
* Added Romanian & Slovak languages files. 

= 1.1.1 =
* Added New feature: Auto categories from source posts (where available).
* Improved image sizes.
* Updated language files. pot, es_ES. Spanish Language.
* Small fix - item date.
* Fixes to some files coded as ANSI instead of UTF-8
* Fixes (when fetching) to The RegEx stripslashes in "Word to Category" and "rewriting options".

= 1.1 =
* Added tags list to assign to posts in every campaign.
* Added Feed date feature to use the datetime of source post.
* Added {image} tag to post template for show featured image url into post content.
* Added New option for strip links from post content.
* Added to general Settings - no link to external images.
* Fixes to allow the reset of some fields when saving a campaign.
* Fixes The RegEx stripslashes in "Word to Category" and "rewriting options".
* Some fixes related with images and images urls on media library.
* Added Spanish language File.
* [PRO]
* Added Custom fields feature for fetched posts with values generated by template fields.
* Added Auto generate tags, getting tags from post content.
* Added images on enclosure media tags on feeds.
* An issue fixed on Add feed image to full-content on PRO Version.
* Default Featured image if not found image on content. Link or Upload new image from campaign.
* Added strip HTML filter also to feed content as full-content.
* [/PRO]

= 1.0.2 =
* Fixes to many DEPRECATED PHP notices on log and interrupted cron: upgraded Simplepie library to 1.3. 
* New option on advanced settings to force the use of Simplepie library in plugin. Simplepie provided by WP is not compatible with PHP 5.3.
* Added options to skip Wordpress Post content filters. Beta. (It allows to embed code like flash or videos on posts)
* Fixes to other minor details.

= 1.0.1 =
* Fixes – Not running for more than five campaigns.
* Fixed? problem with filter function wp_mail_content_type
* Trying to make the pluggin more compatible with plugins that use public custom posts urls: Added 'public' => false and 'exclude_from_search' => true to custom post type 'campaign'.
* Added new file to run external cron alone without call wp-cron.
* New website for plugin [WPeMatico](http://www.wpematico.com)

= 1.0 =

This is a big update. Lots of things you asked for, are ready in 1.0 version.

* Now you can use Wordpress custom post types for campaigns.
* Now you can move and close metaboxes.
* Now you can paginate and filter campaigns list by name.
* Now we have an image background at WP repository. :)
* Improved feed list with scroll into campaign.
* Improved feed search filter.
* Better help.
* Better performance.
* Colored boxes for better organization (To know what you’re doing.
* More options on Settings.
* New logo and images.
* Totally translatable. 
* Better use of Ajax.
* Better use of class SimplePie included into Wordpress core.
* Deactivated Wordpress Autosave only when editing WPeMatico campaigns.
* Automatic check fields values before saving a campaign without reloading the page and/or for lost fields content.
* Option to activate or deactivate automatic feed check before saving a campaign.
* Added option for testing purposes for only one feed.
* Added description field for every campaign.
* New option to del hash on feeds to fetch for duplicated posts. (Advanced config)
* New option to see last log of every campaign. (Advanced config)
* Now you can Disable Check Feeds before Saving. (Advanced config)
* Now you can choose which roles to see on the dashboard widget.
* Fixes - rewrite to also rewrite html code.
* First image on content as WP feature image.
* Now support relative paths for upload images from source content.
* [PRO]
* Option to automatic creation of author names based on source or custom typed author.
* Option to assign author per feed instead of campaign author (or you can use both options).
* Option to correct and fix wrong html code with lots of options from htmLawed.
* [/PRO]

= 0.91.1Beta =
* [PRO]
* Added New Feature: Fetch every 1 Minute.
* [/PRO]
* Minor fixes but an important one about duplicating posts.
* Minor fixes layout bugs on Settings.

= 0.90Beta =
* [PRO]
* Added New Feature: Attempt to get Full Content.
* [/PRO]
* First image attached to a post marked as Featured Image of the post.
* Added support for Wordpress Custom Post Types
* Added check Feeds before saving a campaign.
* Fixes to the layout with schedule options.
* Updated Frequently Asked Questions.
* Updated donate link with paypal.

= 0.85Beta =
* [PRO]
* Added New Feature: Custom Title with counter.
* [/PRO]
* Added {author} tag for retrieve the name of the Author of the post. 
* Added {authorlink} tag for retrieve the original link of the Author of the post.
* Added new method for check duplicate posts also with the source permalink.
* Added option to display or not the dashboard widget.
* Fixed the automatic update issue between standard and Pro versions.
* Fixed some display issues in Keyword Filters box in PRO.
* Wordpress 3.3.1 compatibility.

= 0.84Beta =
* Wordpress 3.3 compatibility.
* small fix with php function str_replace

= 0.83Beta =
* New PRO version available at website.
* [PRO]
*    New features: Delete last HTML tag option, Words count filters, Keywords filtering.
*    New options to enable or not new features: Words count filters, Keywords filtering.
*    Words count filters. Count how many words there are in your content to assign a category or to skip the post.
*      The content can be converted to text and cut to a desired amount of words or letters.
*    Keywords filtering. You can determine whether to skip the post for certain words in title or content or not.
* [/PRO]
* Fixes to images process after rewriting functions not to upload deleted images at content.
* Fixed spaces of images names.
* Fixed - little duplicate thing on titles with special chars. 

= 0.82Beta =
* New option to enable or not the new feature: Words to Category.
* Words to Category. Define custom words to assign every posts to specified categories.
* Fixes "No link to source images" Hide/show option on click "Enable cache img" 
* Added "checking" image near "Check all feeds" button.

= 0.81Beta =
* Wordpress 3.2.1 compatible.
* Add ‘Activate/Deactivate’ to options in campaign's table.
* Fixed - when clicking “Add more” in Rewrite, the form appears in the Post template section.

= 0.8Beta =
* Upgrade only for Wordpress 3.1 compatibility.

= 0.7Beta =
* Wordpress 3.0.4 compatible.
* Fixes – now you can check for duplicates on draft, private and published posts.
* Added {feeddescription} tag.
* Fixes to some issues in template post tags.

= 0.6Beta =
* Added Post template feature in every campaign.

= 0.5Beta =
* Fixed Post title links to source option.
* .pot language file updated.
* Readme.txt updated.
* Merry Christmas 2010. Jesus lives.

= 0.4Beta =
* Some issues fixed when rewriting words & links.
* Fixed - Link in Dashboard widget.
* Fixed - Allow Ping option issue.
* Change log e-mail to html format.
* New options added to enable or disable image cache in every campaign.
* New options added for each campaign to avoid linking to source image on error at image cache upload 
* Fixes - Tested up field on Readme.txt

= 0.3Beta =
* Fixed an issue in 1st feed for checking.
* Fixed - bug Warning & Error messages on running campaign.
* Added Go Back button on error saving and getting the old values.
* Added 2 more Screenshots on Wordpress repository.
* Readme.txt updated.

= 0.2Beta =
* Fixes to the version number.
* Fixes to the wrong message when activating.
* Deleted .mo & .po files and replaced with new wordpress generated .pot

= 0.1Beta =
* initial release
* [more info in spanish, en español](http://www.netmdp.com/wpematico/)



### [Add-ons](https://etruel.com):
* [Professional Add-on](https://etruel.com/downloads/wpematico-professional/)
* [FULL Content](https://etruel.com/downloads/wpematico-full-content/)
* [Manual Fetching](https://etruel.com/downloads/wpematico-manual-fetching/)
* [Make me Feed "Good"](https://etruel.com/downloads/wpematico-make-feed-good/)
* [Facebook Fetcher](https://etruel.com/downloads/wpematico-facebook-fetcher/)
* [Thumbnail Scratcher](https://etruel.com/downloads/wpematico-thumbnail-scratcher/)
* [Better Excerpts](https://etruel.com/downloads/wpematico-better-excerpts/)
* [Publish 2 Email](https://etruel.com/downloads/wpematico-publish-2-email/)
* [Polylang](https://wordpress.org/plugins/wpematico-polylang/)
* [Categories 2 Tags](https://etruel.com/downloads/wpematico-cats2tags/)
* [WPeMatico SMTP](https://etruel.com/downloads/wpematico-smtp/)
* [Chinese tags](https://etruel.com/downloads/wpematico/chinese-tags)


**Do you like WPeMatico?**


Don't hesitate to [give your feedback](https://wordpress.org/support/view/plugin-reviews/wpematico#new-post). It will help making the plugin better.
