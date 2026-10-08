# WPeMatico

**Autoblogging for WordPress.** WPeMatico publishes posts automatically from the RSS/Atom feeds, XML files and video channels you choose, organized into campaigns.

[![WordPress plugin version](https://img.shields.io/wordpress/plugin/v/wpematico?label=wordpress.org)](https://wordpress.org/plugins/wpematico/)
[![Active installs](https://img.shields.io/wordpress/plugin/installs/wpematico)](https://wordpress.org/plugins/wpematico/)
[![Rating](https://img.shields.io/wordpress/plugin/stars/wpematico)](https://wordpress.org/support/plugin/wpematico/reviews/)
[![Tested up to](https://img.shields.io/wordpress/plugin/tested/wpematico)](https://wordpress.org/plugins/wpematico/)

[Plugin page on WordPress.org](https://wordpress.org/plugins/wpematico/) · [Screenshots](https://wordpress.org/plugins/wpematico/#screenshots) · [Releases and changelog](https://www.wpematico.com/releases/) · [Documentation](https://etruel.com/faqs/) · [Add-ons](https://etruel.com/downloads/category/wpematico-add-ons/)

This is the development repository of the WPeMatico core plugin. The stable version is the one published on WordPress.org.

## What it does

A **campaign** groups one or more sources with everything that decides how their items become posts: the schedule, the post type, status and author, the categories and tags, the images and the template of the content. WPeMatico runs each campaign on its schedule and publishes what is new.

### Sources

* **RSS and Atom feeds**, read with the SimplePie library that ships with WordPress. Paste the address of a site and the feed is discovered for you.
* **XML files**, sitemaps and namespaced XML included, mapping each node to a post field.
* **YouTube** channels, playlists and users.
* **Vimeo** users, channels and groups.
* **bbPress**: publish forums, topics and replies.

### Publishing

* Any public post type, post status and post format.
* Categories and tags per campaign, categories created from the source, and words that send a post to a category.
* A post template with tags for the title, content, author, source, image and more.
* Word and phrase rewriting, with regular expressions, and automatic links on the words you choose.
* Duplicate control, so an item is published once.
* The title link can point to the source site, with a canonical URL for the imported post.

### Images, audio and video

* Images, audio and video are stored in the Media Library, or left linked to the source.
* The first image of a post can become its featured image, alone or together with the rest.
* Works with *Featured Image from URL* to use remote images as featured images.

### Running it

* WordPress cron, or an external cron job for precise schedules and best performance.
* A log per campaign, sent by e-mail on every run or only on errors.
* Campaign Preview lists the items the next run will publish.

### The admin

* **Dashboard**: one card per feature, to switch on only what the site uses.
* **Campaigns list** with Quick Edit and Bulk Edit.
* **Tools**: Feed List, Feed Viewer, Export / Import, and a **Migration Toolkit** that imports campaigns from Feedzy RSS Feeds and WP RSS Aggregator.
* **System Status**, to check what the server offers.
* Contextual Help tabs and guided tours on every screen.
* Translated into Spanish, German, French, Dutch, Greek, Persian, Russian, Slovak, Romanian, Arabic and Chinese.

## Requirements

| | |
|---|---|
| WordPress | 4.8 or higher, tested up to 7.1 |
| PHP | 7.0 or higher |

The **System Status** screen inside the plugin lists everything else it checks on your server.

## Installation

### From WordPress.org (recommended)

In your WordPress admin go to **Plugins → Add New**, search for `WPeMatico`, then **Install Now** and **Activate**.

### From GitHub

Download the `wpematico-*.zip` file attached to a [release](https://github.com/etruel/wpematico/releases) and upload it in **Plugins → Add New → Upload Plugin**. That file unpacks into the `wpematico/` folder, which is the one WordPress and every add-on expect, so it updates the copy you already have.

The *Source code* archives and the green *Download ZIP* button unpack into a differently named folder, and WordPress would install that as a second plugin beside the first. Use the release file.

### First steps

1. Open **WPeMatico → Settings** and review the general options.
2. Create a campaign in **WPeMatico → Campaigns → Add New** and add its feeds.
3. Use **Check feeds** and the **Campaign Preview** to see what will be published, then activate the campaign.

## Add-ons

Add-ons extend the core through its public hooks. Those sold at [etruel.com](https://etruel.com/downloads/category/wpematico-add-ons/) are also available together in the [Essentials](https://etruel.com/downloads/wpematico-essentials/), [Plus](https://etruel.com/downloads/wpematico-plus/), [Premium](https://etruel.com/downloads/wpematico-premium/) and [Perfect](https://etruel.com/downloads/wpematico-perfect/) memberships.

WPeMatico 2.9 needs the add-on versions released with it; those versions also run on WPeMatico 2.8.27, so update your add-ons first.

### Free, on WordPress.org

| Add-on | What it adds |
|---|---|
| [RSS Feed Reader](https://wordpress.org/plugins/wpematico-rss-feed-reader/) | Prints the contents of a feed on pages, posts and widgets, without creating posts. |
| [Polylang](https://wordpress.org/plugins/wpematico-polylang/) | Assigns the posts of a campaign to a language of the Polylang plugin. |
| [Custom Hooks](https://wordpress.org/plugins/wpematico-custom-hooks/) | Runs your own PHP on the WPeMatico actions and filters, from the WordPress admin. |

### Premium, at etruel.com

| Add-on | What it adds |
|---|---|
| [Professional](https://etruel.com/downloads/wpematico-professional/) | Advanced parsing, media filtering, tag management, custom fields and automation rules. |
| [GPT Spinner](https://etruel.com/downloads/wpematico-gpt-spinner/) | Rewrites the content with AI before it is published. |
| [Full Content](https://etruel.com/downloads/wpematico-full-content/) | Gets the full article from the source site when the feed only brings a summary, and its featured image from the page's meta tags. |
| [Synchronizer](https://etruel.com/downloads/wpematico-synchronizer/) | Keeps imported posts updated with their source: content, media, authors and categories. |
| [Manual Fetching](https://etruel.com/downloads/wpematico-manual-fetching/) | Review the items of a campaign and publish only the ones you choose. |
| [Polyglot](https://etruel.com/downloads/wpematico-polyglot/) | Translates posts automatically before publishing. |
| [Make me Feed "Good"](https://etruel.com/downloads/wpematico-make-me-feed-good/) | Creates an RSS 2.0 feed from a site that does not offer one. |
| [Facebook Fetcher](https://etruel.com/downloads/wpematico-facebook-fetcher/) | Imports the posts, images and comments of your own Facebook pages. |
| [Better Excerpts](https://etruel.com/downloads/wpematico-better-excerpts/) | Builds clean excerpts from the opening of each post. |
| [Publish 2 Email](https://etruel.com/downloads/wpematico-publish-2-email/) | Sends fetched posts by e-mail, to publish on other sites through "Post via Email". |
| [Exporter](https://etruel.com/downloads/wpematico-exporter/) | Exports the posts of the site to a file on a schedule, as XML, JSON or CSV from your own template, to the server or over FTP, SFTP or SSH. |
| [Office Campaign Type](https://etruel.com/downloads/wpematico-office-campaign-type/) | Publishes posts from office documents hosted or uploaded in a folder on the server. |

## For developers

WPeMatico is extended through WordPress actions and filters, the same ones its own add-ons use. The usual entry points:

| Hook | Use it to |
|---|---|
| `wpematico_item_parsers` | Change an item while it is being processed. |
| `wpematico_inserted_post` | Act on a post right after it is published. |
| `wpematico_create_metaboxes` | Add a box to the campaign editor. |
| `wpematico_campaign_addon_fields` | Declare campaign fields of your own and their defaults. |
| `wpematico_modules` | Register a card on the Dashboard. |
| `wpematico_rss_campaign_types` | Declare a campaign type whose source is a feed. |

```php
add_filter( 'wpematico_item_parsers', function ( $current_item, $campaign, $feed, $item ) {
	$current_item['title'] = ucfirst( $current_item['title'] );
	return $current_item;
}, 10, 4 );
```

The plugin follows the WordPress Coding Standards and has no build step: the repository is the plugin. The text domain is `wpematico` and the catalogues live in `lang/`.

## Support and contributing

* **Bugs and feature requests**: [open an issue](https://github.com/etruel/wpematico/issues).
* **Help with your site**: [open a support ticket](https://etruel.com/my-account/support/) or browse the [FAQs and tutorials](https://etruel.com/faqs/).
* **Translations**: join the [translation team](https://translate.wordpress.org/projects/wp-plugins/wpematico) on WordPress.org.
* **Pull requests** are welcome against `master`.

If WPeMatico saves you time, a [5-star review](https://wordpress.org/support/plugin/wpematico/reviews/#new-post) on WordPress.org helps a lot.

## Responsible use

Using WPeMatico, the Full Content add-on or any other product or service provided by Etruel to infringe the intellectual property rights of third parties is prohibited. Users may be liable for copyright infringement if they copy, reproduce or republish content for which they do not hold a valid license.

## License

WPeMatico is free software, released under the GNU General Public License, version 2 or later.

Made by [Etruel Developments LLC](https://etruel.com) · [wpematico.com](https://www.wpematico.com)
