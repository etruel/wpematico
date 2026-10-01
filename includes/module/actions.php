<?php

/*
 * Callbacks for modules on activate eachone.
 *
 * @since      3.0
 * @package    WPeMatico
 * @subpackage WPeMatico\Core
 * @author     Etruel Developments LLC <hello@etruel.com>
 */

namespace WPeMatico\Module;

defined('ABSPATH') || exit;

class Actions {

	public function __construct() {
		add_filter('wpematico_modules', [__CLASS__, 'filter_modules']);
		add_filter('wp_redirect', [__CLASS__, 'redirect_to_dashboard']);
	}

	public static function update_basic_options($cfg) {
		update_option(\WPeMatico::OPTION_KEY, $cfg);
	}

	public static function update_pro_options($procfg) {
		// The constant holds the option name; quoting it wrote to an option
		// literally called WPEMATICOPRO_OPTION_KEY, which nothing ever reads.
		if (defined('WPEMATICOPRO_OPTION_KEY')) {
			update_option(WPEMATICOPRO_OPTION_KEY, $procfg);
		}
	}

	public static function filter_modules($modules = []) {
		// Read the options, never rewrite the global: this used to assign the raw
		// stored array to $cfg, dropping every default wpematico_check_options
		// fills in for the rest of the request.
		$cfg	= (array) apply_filters('wpematico_check_options', get_option(\WPeMatico::OPTION_KEY, []));
		$procfg = defined('WPEMATICOPRO_OPTION_KEY') ? (array) get_option(WPEMATICOPRO_OPTION_KEY) : [];

		$all_modules = array_merge(
				self::get_standard_modules($cfg, $procfg),
				self::get_install_plugin_modules($cfg, $procfg),
				self::get_add_plugin_modules($cfg, $procfg),
				self::get_advanced_modules($cfg, $procfg)
		);

		return wp_parse_args(self::resolve_option_state($all_modules, $cfg, $procfg), $modules);
	}

	/**
	 * Fill in the state of every module that switches a stored option, and give it
	 * the one callback that writes that option back.
	 *
	 * ★ A card is the same switch as the setting, drawn somewhere else. Declaring
	 * the key once -- `'option' => 'imgcache'` -- is what keeps the read and the
	 * write together, so the two cannot drift apart.
	 *
	 * @param array $modules
	 * @param array $cfg
	 * @param array $procfg
	 * @return array
	 */
	private static function resolve_option_state($modules, $cfg, $procfg) {
		foreach ($modules as $id => $args) {
			if (!empty($args['option'])) {
				$modules[$id]['forced'] = !empty($cfg[$args['option']]);
			} elseif (!empty($args['pro_option'])) {
				$modules[$id]['forced'] = !empty($procfg[$args['pro_option']]);
			} else {
				continue;
			}
			if (empty($args['callback'])) {
				$modules[$id]['callback'] = [__CLASS__, 'toggle_option_module'];
			}
		}

		return $modules;
	}

	/**
	 * Switch callback of every option-backed module. Replaces eleven copies of
	 * "read the options, set one key, save".
	 *
	 * @param bool                     $enable
	 * @param \WPeMatico\Module\Module $module
	 */
	public static function toggle_option_module($enable, $module = null) {
		if (!$module) {
			return;
		}

		if ($key = $module->get('option')) {
			$cfg	   = (array) apply_filters('wpematico_check_options', get_option(\WPeMatico::OPTION_KEY, []));
			$cfg[$key] = (bool) $enable;
			self::update_basic_options($cfg);

			return;
		}

		if (($key = $module->get('pro_option')) && defined('WPEMATICOPRO_OPTION_KEY')) {
			$procfg		  = (array) get_option(WPEMATICOPRO_OPTION_KEY, []);
			$procfg[$key] = (bool) $enable;
			self::update_pro_options($procfg);
		}
	}

	/**
	 * 
	 * @param array $cfg
	 * @param array $procfg
	 * @notes
	 *			'title'			=> Displayed title,
				'desc'			=> Module or feature description,
				'icon'			=> dashicon on module,
				'type'			=> 'standard','install_plugin','add_plugin','advanced',
				'category'		=> Module's category to show and filter. An array puts the module
								   under every one of them; the first is the primary,
	 *			'option'		=> Key of WPeMatico_Options this switch stands for. Declaring it
								   makes the card read AND write that setting; no callback needed,
				'pro_option'	=> Same, for a key of the Professional options,
	 *			'pro'			=> bool = when needs a pro extension to be activated,
				'pro_link'		=> Link to the extension or plugin page,
				'forced'		=> bool = always active. Resolved automatically from 'option',
								   'pro_option' or the plugin a module stands for,
				'disabled'		=> bool = can't change,
				'disabled_text' => Yellow Disable notice text showed on over,
				'notice'		=> Light blue Notice text showed on over,
				'callback'		=> Function callback on change state.

	 * @return array
	 */
	private static function get_standard_modules($cfg, $procfg) {
		return [
			'autoblogging'		  => [
				'title'			=> __('Feed to Post', 'wpematico'),
				'desc'			=> __('AutoBlogging: Automated content curation system.', 'wpematico'),
				'icon'			=> 'admin-post',
				'type'			=> 'standard',
				'category'		=> ['core', 'campaign'],
				'forced'		=> true,
				'disabled'		=> true,
				'disabled_text' => __('Used by default as Feed Fetcher Campaign Type.', 'wpematico'),
			],
			'plugin-wffimporters' => [
				'title'	   => __('Plugin Importers', 'wpematico'),
				'desc'	   => __('Bring the feeds and settings of another RSS importing plugin into WPeMatico.', 'wpematico'),
				'icon'	   => 'share',
				'type'	   => 'standard',
				'category' => ['wffimporters', 'third_party'],
				'callback' => [__CLASS__, 'plugin_wffimporters_callback']
			],
			'bbPress'			  => [
				'title'			=> __('bbPress', 'wpematico'),
				'desc'			=> __('Publish the curated content as forum topics, or as replies to them.', 'wpematico'),
				'icon'			=> 'buddicons-bbpress-logo',
				'type'			=> 'standard',
				'category'		=> ['core', 'campaign'],
				'forced'		=> true,
				'disabled'		=> true,
				'disabled_text' => __('Enable it in a campaign by choosing its Campaign Type.', 'wpematico'),
			],
			'video-campaigns'	  => [
				'title'	   => __('Video Campaigns', 'wpematico'),
				'desc'	   => __('Create campaigns that publish video content.', 'wpematico'),
				'icon'	   => 'video-alt3',
				'type'	   => 'standard',
				'category' => ['campaign', 'core'],
				'notice'   => __('Downloads the video and audio files of each item into the WordPress uploads folder.', 'wpematico'),
				'option'	   => 'video_cache',
				'settings' => admin_url('admin.php?page=wpematico_settings&section=general_settings#gsvideos'),
			],
			'xml-campaign'		  => [
				'title'	   => __('Feeds XML', 'wpematico'),
				'desc'	   => __('Import content from XML feeds with custom structures.', 'wpematico'),
				'icon'	   => 'media-code',
				'type'	   => 'standard',
				'category' => ['core', 'campaign'],
				'option'	   => 'enable_xml_upload',
				'notice'   => __('Adds the XML file type to the ones WordPress accepts on upload.', 'wpematico'),
				'disabled' => false,
			],
			'vimeo'				  => [
				'title'	   => __('Vimeo', 'wpematico'),
				'desc'	   => __('Publish the videos of a Vimeo user, channel or group.', 'wpematico'),
				'icon'	   => 'video-alt3',
				'type'	   => 'standard',
				'category' => ['campaign', 'core'],
				'notice'   => __('Adds Vimeo to the Campaign Type list. Paste the address of the profile, channel or group and the campaign finds its feed on its own.', 'wpematico'),
				'option'	   => 'enable_vimeo',
			],
			'youtube'			  => [
				'title'			=> __('YouTube', 'wpematico'),
				'desc'			=> __('Import videos from YouTube channels.', 'wpematico'),
				'icon'			=> 'video-alt3',
				'type'			=> 'standard',
				'category'		=> ['campaign', 'core'],
				'forced'		=> true,
				'disabled'		=> true,
				'disabled_text' => __('Enable it in a campaign by choosing its Campaign Type.', 'wpematico'),
			],
			'words-to-categories' => [
				'title'	   => __('Words to Categories', 'wpematico'),
				'desc'	   => __('Assign categories automatically from the words found in the curated content.', 'wpematico'),
				'icon'	   => 'category',
				'type'	   => 'standard',
				'category' => 'core',
				'notice'   => __('Set the words and the category each one maps to in the Settings page.', 'wpematico'),
				'option'	   => 'enableword2cats',
			],
			'canonical-urls'	  => [
				'title'	   => __('Canonical URLs', 'wpematico'),
				'desc'	   => __('Credit the original article with a canonical URL on every imported post.', 'wpematico'),
				'icon'	   => 'admin-links',
				'type'	   => 'standard',
				'category' => ['core', 'seo'],
				'notice'   => __('Tells search engines which is the original article, so the copy is not judged as duplicate content.', 'wpematico'),
				'option'	   => 'wpematico_set_canonical',
			],
			'download-images'	  => [
				'title'	   => __('Download Images', 'wpematico'),
				'desc'	   => __('Download the images of each item into the WordPress Media Library.', 'wpematico'),
				'icon'	   => 'format-gallery',
				'type'	   => 'standard',
				'category' => ['core', 'images'],
				'option'	   => 'imgcache',
				'notice'   => __('Turn it on here; the size, quality and naming options are in the Settings page.', 'wpematico'),
			],
			'featured-image'	  => [
				'title'	   => __('Featured Images', 'wpematico'),
				'desc'	   => __('Give every imported post a featured image, taken from the first image of the item.', 'wpematico'),
				'icon'	   => 'cover-image',
				'type'	   => 'standard',
				'category' => ['core', 'images'],
				'option'	   => 'featuredimg',
				'notice'   => __('Each campaign can override it, and the Featured Image from URL module decides whether the image is downloaded or linked.', 'wpematico'),
				'settings' => admin_url('admin.php?page=wpematico_settings&section=general_settings#imgs'),
			],
			'email-notifications' => [
				'title'			=> __('Email Notifications', 'wpematico'),
				'desc'			=> __('Get an email report every time a campaign finishes a run.', 'wpematico'),
				'icon'			=> 'email-alt',
				'type'			=> 'standard',
				'category'		=> 'core',
				'forced'		=> true,
				'disabled'		=> true,
				'disabled_text' => __('Deactivate in each campaign by clearing the email field.', 'wpematico'),
			],
			'custom-gallery'	  => self::addon_feature_module([
				'title'			=> __('Random Galleries', 'wpematico'),
				'desc'			=> __('Group images by topic and give each post a featured image picked at random from its gallery.', 'wpematico'),
				'icon'			=> 'format-gallery',
				'type'			=> 'standard',
				'category'		=> ['images','advanced'],
				'pro_option'	=> 'enable_custom_gallery',
				'pro_link'		=> 'https://etruel.com/downloads/wpematico-professional/',
				'disabled_text' => __('Available in the Professional addon.', 'wpematico')
			], 'WPeMatico Professional', 'wpematico-professional'),
			'random-rewrites'	  => self::addon_feature_module([
				'title'			=> __('Random Rewrites', 'wpematico'),
				'desc'			=> __('Rewrite custom words randomly as synonyms.', 'wpematico'),
				'icon'			=> 'plus-alt',
				'type'			=> 'standard',
				'category'		=> ['parser', 'seo', 'advanced'],
				'notice'		=> __('You must complete the words separated by comma and per line in the textarea.', 'wpematico'),
				'pro_option'	=> 'enable_ramdom_words_rewrites',
				'pro_link'		=> 'https://etruel.com/downloads/wpematico-professional/',
				'disabled_text' => __('Available in the Professional addon.', 'wpematico')
			], 'WPeMatico Professional', 'wpematico-professional')
		];
	}

	/**
	 * Fills in the state of a module that stands for a plugin, so no module has
	 * to compute it (and get it wrong) on its own.
	 *
	 * Not installed + free  -> Install button, the switch stays off.
	 * Not installed + paid  -> GET IT button to the store.
	 * Installed             -> "Installed" badge and a switch that really
	 *                          activates or deactivates the plugin.
	 *
	 * @param array  $args     Module args.
	 * @param string $name     Plugin Name header.
	 * @param string $folder   Expected folder.
	 * @param string $download Zip URL for a free plugin, if any.
	 * @return array
	 */
	private static function plugin_module($args, $name, $folder, $download = '') {
		$file	   = self::find_plugin($name, $folder);
		$installed = ('' !== $file);
		$active	   = $installed && is_plugin_active($file);

		$args['plugin_name']   = $name;
		$args['plugin_folder'] = $folder;
		$args['installed']	   = $installed;
		// The plugin itself is the state; the modules option does not apply here.
		$args['forced']		   = $active;
		$args['disabled']	   = !$installed;
		if (!isset($args['callback'])) {
			$args['callback'] = [__CLASS__, 'toggle_plugin_module'];
		}

		if ($installed) {
			$args['badge'] = __('Installed', 'wpematico');
			unset($args['wp_link'], $args['disabled_text']);
			// Say what the switch does, since it acts on the plugin itself.
			$args['notice'] = $active
				/* translators: %s: plugin name. */
				? sprintf(__('Turning this off deactivates the %s plugin.', 'wpematico'), $name)
				/* translators: %s: plugin name. */
				: sprintf(__('The plugin is installed but not running. Turning this on activates %s.', 'wpematico'), $name);
		} elseif (!empty($download)) {
			$args['wp_link'] = $download;
			// The card shows the yellow note while the switch is disabled, so the
			// "what happens next" text has to live there, not in the blue notice.
			/* translators: %s: plugin name. */
			$args['disabled_text'] = sprintf(__('%s is not installed yet. Install it from here, free, and this switch turns it on.', 'wpematico'), $name);
		} elseif (!empty($args['pro'])) {
			// Paid and not installed: say what it takes, next to the GET IT button.
			/* translators: %s: addon name. */
			$args['disabled_text'] = sprintf(__('Needs the %s addon. Get it at etruel.com and this switch enables it.', 'wpematico'), $name);
		}

		return self::apply_requirement($args);
	}

	/**
	 * A feature that lives *inside* one of our addons: its switch flips an option
	 * of that addon, so it means nothing while the addon is not running. Unlike
	 * plugin_module(), the card is not the plugin — turning it off must never stop
	 * Professional, only clear its option.
	 *
	 * Not installed      -> GET IT to the store.
	 * Installed, stopped -> Activate button, never the store: the addon is owned.
	 * Running            -> the switch flips the addon option.
	 *
	 * @param array  $args
	 * @param string $name   Plugin Name header of the addon.
	 * @param string $folder Expected folder.
	 * @return array
	 */
	private static function addon_feature_module($args, $name, $folder) {
		$file	   = self::find_plugin($name, $folder);
		$installed = ('' !== $file);
		$active	   = $installed && is_plugin_active($file);

		$args['pro'] = true;
		// "Installed" means present on this site, the same as every plugin-backed
		// card, and it is what keeps the GET IT upsell away from an owner.
		$args['installed'] = $installed;
		$args['disabled']  = !$active;

		if ($active || !$installed) {
			return $args;
		}

		$args['forced'] = false;
		unset($args['notice']);
		/* translators: %s: addon name. */
		$args['disabled_text'] = sprintf(__('%s is installed but not active. Turn it on and this module becomes available.', 'wpematico'), $name);
		$args['require_label'] = __('Activate', 'wpematico');
		$args['require_url']   = self::activate_url($file);

		return $args;
	}

	/**
	 * A module may stand on a plugin that is not ours, the way WPeMatico Polylang
	 * stands on Polylang. While that plugin is missing the card asks for it first:
	 * the switch stays off and the button installs (or activates) that plugin
	 * instead of ours.
	 *
	 * 'requires' takes: name (string or array of names, e.g. the Pro edition),
	 * folder, test (a function that only exists while the plugin runs, which is
	 * also what catches editions we cannot name), constant (same idea, for the
	 * plugins the rest of the code already recognises by one), download and reason.
	 *
	 * @param array $args
	 * @return array
	 */
	private static function apply_requirement($args) {
		$req = isset($args['requires']) ? (array) $args['requires'] : [];

		if (empty($req['name'])) {
			return $args;
		}

		$names	  = (array) $req['name'];
		$label	  = reset($names);
		$folder	  = isset($req['folder']) ? $req['folder'] : '';
		$test	  = isset($req['test']) ? $req['test'] : '';
		$constant = isset($req['constant']) ? $req['constant'] : '';
		$reason	  = isset($req['reason']) ? $req['reason'] : '';

		$file = '';
		foreach ($names as $name) {
			$file = self::find_plugin($name, $folder);
			if ('' !== $file) {
				break;
			}
		}

		if ($constant && defined($constant)) {
			$running = true;
		} elseif ($test) {
			$running = function_exists($test);
		} else {
			$running = ('' !== $file && is_plugin_active($file));
		}

		if ($running) {
			return $args;
		}

		// A paid addon nobody bought yet keeps its GET IT flow: the base plugin is
		// something to know before buying, not the next thing to do on this card.
		if (!empty($args['pro']) && empty($args['installed'])) {
			/* translators: %s: plugin name. */
			$args['disabled_text'] = trim((isset($args['disabled_text']) ? $args['disabled_text'] : '') . ' ' . sprintf(__('It also needs the free %s plugin.', 'wpematico'), $label));

			return $args;
		}

		// Whatever the state of our own plugin, this switch cannot do its job.
		$args['forced']	  = false;
		$args['disabled'] = true;
		unset($args['wp_link'], $args['notice']);

		// The button says only what it does. Naming the plugin in it overflowed the
		// card footer on any long name, and it is redundant: the card title and the
		// note right above already say which plugin this is about.
		if ('' !== $file) {
			/* translators: %s: plugin name. */
			$args['disabled_text'] = sprintf(__('%s is installed but not active. Turn it on and this module becomes available.', 'wpematico'), $label);
			$args['require_label'] = __('Activate', 'wpematico');
			$args['require_url']   = self::activate_url($file);
		} else {
			/* translators: %s: plugin name. */
			$args['disabled_text']	 = sprintf(__('Needs the free %s plugin, which is not installed yet.', 'wpematico'), $label);
			$args['require_label']	 = __('Install', 'wpematico');
			$args['require_url']	 = (!empty($req['download']) && current_user_can('install_plugins')) ? $req['download'] : '';
			$args['require_install'] = '' !== $args['require_url'];
		}

		if ($reason) {
			$args['disabled_text'] .= ' ' . $reason;
		}

		return $args;
	}

	/**
	 * The URL that starts a plugin from one of these cards.
	 *
	 * `wpem_module_return` is our own marker, and the only thing this adds to the
	 * plain activation link core would build; see redirect_to_dashboard().
	 *
	 * ★ A bulk activation cannot be used here to get core's `activate-multi` flag:
	 * `activate-selected` reads `$_POST['checked']` only, so a link can never carry
	 * the plugin and core would activate nothing.
	 *
	 * @param string $file Plugin file, relative to the plugins dir.
	 * @return string Empty when the user may not activate plugins.
	 */
	private static function activate_url($file) {
		if (!current_user_can('activate_plugins')) {
			return '';
		}

		return wp_nonce_url(
			self_admin_url('plugins.php?action=activate&wpem_module_return=1&plugin=' . urlencode($file)),
			'activate-plugin_' . $file
		);
	}

	/**
	 * Bring the user back to the modules dashboard after a card started a plugin,
	 * instead of leaving them on the site's plugins list.
	 *
	 * ★ The destination carries `activate-multi`, the flag a plugin checks before
	 * hijacking the next admin screen with its own welcome page — which is exactly
	 * what happened here, since we activate on the user's behalf from elsewhere.
	 *
	 * Only success is intercepted: a failed activation still goes to plugins.php,
	 * where core explains what went wrong.
	 */
	public static function redirect_to_dashboard($location) {
		if (empty($_REQUEST['wpem_module_return'])) {
			return $location;
		}

		if (false === strpos($location, 'activate=true') && false === strpos($location, 'activate-multi=true')) {
			return $location;
		}

		return admin_url('admin.php?page=wpematico_dashboard&activate-multi=1');
	}

	/**
	 * Switch callback of every plugin-backed module.
	 *
	 * @param bool                        $enable
	 * @param \WPeMatico\Module\Module $module
	 */
	public static function toggle_plugin_module($enable, $module = null) {
		if (!$module) {
			return;
		}
		self::toggle_plugin($module->get('plugin_name'), $module->get('plugin_folder'), $enable);
	}

	private static function get_install_plugin_modules($cfg, $procfg) {
		return [
			'delete-dups'	=> self::plugin_module([
				'title'	   => __('Delete Duplicates', 'wpematico'),
				'desc'	   => __('Automatically search and delete duplicated posts by name or content.', 'wpematico'),
				'icon'	   => 'admin-comments',
				'type'	   => 'install_plugin',
				'category' => ['external', 'seo'],
				'badge'	   => 'FREE',
				'notice'   => __('Installs the free WP Delete Post Copies plugin from WordPress.org.', 'wpematico'),
			], 'WP Delete Post Copies', 'etruel-del-post-copies', 'https://downloads.wordpress.org/plugin/etruel-del-post-copies.zip'),

			'multilanguage' => self::plugin_module([
				'title'	   => __('WPeMatico Polylang', 'wpematico'),
				'desc'	   => __('Assigns the imported posts to the right language on sites running Polylang.', 'wpematico'),
				'icon'	   => 'translation',
				'type'	   => 'install_plugin',
				'category' => ['external', 'third_party'],
				'badge'	   => 'FREE',
				'notice'   => __('Installs the free WPeMatico Polylang plugin from WordPress.org.', 'wpematico'),
				'requires' => [
					// Polylang Pro ships under its own name and registers the same functions.
					'name'	   => ['Polylang', 'Polylang Pro'],
					'folder'   => 'polylang',
					'test'	   => 'pll_current_language',
					'download' => 'https://downloads.wordpress.org/plugin/polylang.zip',
					'reason'   => __('The addon files each imported post under a language, and languages only exist while Polylang runs.', 'wpematico'),
				],
			], 'WPeMatico Polylang', 'wpematico-polylang', 'https://downloads.wordpress.org/plugin/wpematico-polylang.zip'),

			'feed-reader'	=> self::plugin_module([
				'title'			=> __('RSS Items Aggregator', 'wpematico'),
				'desc'			=> __('Also known as RSS Feed Reader: shows the items of several feeds inside one post or page.', 'wpematico'),
				'icon'			=> 'rss',
				'type'			=> 'install_plugin',
				'category'		=> 'campaign',
				'badge'			=> 'FREE',
				'notice'		=> __('Installs the free WPeMatico RSS Feed Reader plugin from WordPress.org.', 'wpematico'),
				'disabled_text' => __('Requires WPeMatico RSS Feed Reader', 'wpematico'),
			], 'WPeMatico RSS Feed Reader', 'wpematico-rss-feed-reader', 'https://downloads.wordpress.org/plugin/wpematico-rss-feed-reader.zip'),

			// FIFU is somebody else's plugin, so this card is our integration with
			// it, never its life: the switch writes our own `fifu` option and the
			// plugin is a requirement, the same way Polylang is for the multilanguage
			// card. It used to deactivate FIFU site-wide, which also takes it away
			// from the posts the user features by hand, outside any campaign.
			'fifu'			=> self::apply_requirement([
				'title'		=> __('Featured Image from URL', 'wpematico'),
				'desc'		=> __('Use the image at its original URL as featured image, without downloading it.', 'wpematico'),
				'icon'		=> 'format-image',
				'type'		=> 'install_plugin',
				'category'	=> ['external', 'images', 'third_party'],
				'badge'		=> 'FREE',
				'option'	=> 'fifu',
				'notice'	=> empty($cfg['featuredimg'])
					? __('Featured Images is off, so nothing picks the image to link — unless an addon such as Professional or Full Content supplies one.', 'wpematico')
					: __('The image is linked from its original site instead of being downloaded, so it takes up no space in your Media Library.', 'wpematico'),
				'settings'	=> admin_url('admin.php?page=wpematico_settings&section=general_settings#imgs'),
				'requires'	=> [
					// Named editions first; the constant is what the rest of the
					// plugin already tests for, and it also covers the paid ones.
					'name'	   => ['Featured Image from URL (FIFU)', 'Featured Image from URL (FIFU) PRO'],
					'folder'   => 'featured-image-from-url',
					'constant' => 'FIFU_PLUGIN_DIR',
					'download' => 'https://downloads.wordpress.org/plugin/featured-image-from-url.zip',
					'reason'   => __('WPeMatico stores the image URL on each post; that plugin is what turns it into the featured image WordPress shows.', 'wpematico'),
				],
			]),
		];
	}

	private static function get_add_plugin_modules($cfg, $procfg) {
		$pro_link = 'https://etruel.com/downloads/wpematico-professional/';

		return [
			'delete-posts'	  => self::plugin_module([
				'title'			=> __('Auto Delete', 'wpematico'),
				'desc'			=> __('Delete the posts of the categories you choose once they reach a given age.', 'wpematico'),
				'icon'			=> 'admin-comments',
				'type'			=> 'add_plugin',
				'category'		=> 'external',
				'badge'			=> 'AddOn',
				'pro'			=> true,
				'notice'		=> __('Helpful if you want to remove stale or old items automatically.', 'wpematico'),
				'pro_link'		=> 'https://etruel.com/downloads/etruel-del-post-copies-pro/',
				'requires'		=> [
					// The addon declares `Requires Plugins: etruel-del-post-copies`,
					// so WordPress itself refuses to activate it without this one.
					'name'	   => 'WP Delete Post Copies',
					'folder'   => 'etruel-del-post-copies',
					'download' => 'https://downloads.wordpress.org/plugin/etruel-del-post-copies.zip',
					'reason'   => __('The addon extends that free plugin, the same one the Delete Duplicates card installs.', 'wpematico'),
				],
			], 'WP Delete Post Copies PRO', 'etruel-del-post-copies-pro'),

			'translate'		  => self::plugin_module([
				'title'			=> __('Polyglot', 'wpematico'),
				'desc'			=> __('Translate the fetched content with any of several free and paid translation engines.', 'wpematico'),
				'icon'			=> 'translation',
				'type'			=> 'add_plugin',
				'category'		=> ['campaign', 'parser'],
				'badge'			=> 'AddOn',
				'pro'			=> true,
				'pro_link'		=> 'https://etruel.com/downloads/wpematico-polyglot/',
				'disabled_text' => __('Requires WPeMatico Polyglot addon', 'wpematico'),
			], 'WPeMatico Polyglot', 'wpematico-polyglot'),

			'gpt-spinner'	  => self::plugin_module([
				'title'			=> __('GPT Spinner AI', 'wpematico'),
				'desc'			=> __('Rewrite the fetched content automatically through an AI or spinner API.', 'wpematico'),
				'icon'			=> 'admin-comments',
				'type'			=> 'add_plugin',
				'category'		=> ['parser', 'seo'],
				'badge'			=> 'AddOn',
				'pro'			=> true,
				'pro_link'		=> 'https://etruel.com/downloads/wpematico-gpt-spinner/',
				'disabled_text' => __('Requires AI addon', 'wpematico'),
			], 'WPeMatico GPT Spinner', 'wpematico-gpt-spinner'),

			'full-content'	  => self::plugin_module([
				'title'			=> __('Full Contents', 'wpematico'),
				'desc'			=> __('Enable full content extraction from each source website.', 'wpematico'),
				'icon'			=> 'media-text',
				'type'			=> 'add_plugin',
				'category'		=> 'parser',
				'badge'			=> 'AddOn',
				'pro'			=> true,
				'pro_link'		=> 'https://etruel.com/downloads/wpematico-full-content/',
				'disabled_text' => __('Requires WPeMatico Full Content addon', 'wpematico'),
			], 'WPeMatico Full Content', 'wpematico-full-content'),

			'bulk-sources'	  => self::addon_feature_module([
				'title'			=> __('Bulk Add Sources', 'wpematico'),
				'desc'			=> __('Paste or upload a list of feeds to add many sources to a campaign at once.', 'wpematico'),
				'icon'			=> 'plus-alt',
				'type'			=> 'add_plugin',
				'category'		=> 'wffimporters',
				'pro_option'	=> 'enableimportfeed',
				'pro_link'		=> $pro_link,
				'disabled_text' => __('Available in the Professional addon.', 'wpematico'),
				'notice'		=> __('A Professional feature, you can activate it here.', 'wpematico'),
			], 'WPeMatico Professional', 'wpematico-professional'),

			'keywords'		  => self::addon_feature_module([
				'title'			=> __('Keyword Filtering', 'wpematico'),
				'desc'			=> __('Keep or skip each item by the keywords found in its title or its content.', 'wpematico'),
				'icon'			=> 'plus-alt',
				'type'			=> 'add_plugin',
				'category'		=> 'parser',
				'pro_option'	=> 'enablekwordf',
				'pro_link'		=> $pro_link,
				'disabled_text' => __('Available in the Professional addon.', 'wpematico'),
			], 'WPeMatico Professional', 'wpematico-professional'),

			'facebook'		  => self::plugin_module([
				'title'			=> __('Facebook Source', 'wpematico'),
				'desc'			=> __('Import content from Facebook profiles and groups.', 'wpematico'),
				'icon'			=> 'facebook',
				'type'			=> 'add_plugin',
				'category'		=> 'campaign',
				'badge'			=> 'AddOn',
				'pro'			=> true,
				'pro_link'		=> 'https://etruel.com/downloads/wpematico-facebook-fetcher/',
				'disabled_text' => __('Requires Facebook Campaign addon', 'wpematico'),
			], 'WPeMatico Facebook Fetcher', 'wpematico-facebook-fetcher'),

			'import-export'	  => self::addon_feature_module([
				'title'			=> __('Export/Import Campaigns', 'wpematico'),
				'desc'			=> __('Migrate campaigns between sites.', 'wpematico'),
				'icon'			=> 'migrate',
				'type'			=> 'add_plugin',
				'category'		=> 'wffimporters',
				'pro_option'	=> 'enableeximport',
				'pro_link'		=> $pro_link,
				'disabled_text' => __('Available in the Professional addon.', 'wpematico'),
			], 'WPeMatico Professional', 'wpematico-professional'),

			'feed-creator'	  => self::plugin_module([
				'title'			=> __('Feed Creator', 'wpematico'),
				'desc'			=> __('Generate custom RSS feeds from external webpages to fetch with WPeMatico.', 'wpematico'),
				'icon'			=> 'rss',
				'type'			=> 'add_plugin',
				'category'		=> 'external',
				'badge'			=> 'AddOn',
				'pro'			=> true,
				'pro_link'		=> 'https://etruel.com/downloads/wpematico-make-me-feed-good/',
				'disabled_text' => __('Requires "Make Me Feed" addon', 'wpematico'),
			], 'WPeMatico Make me Feed Good', 'wpematico-make-me-feed'),

			'email-campaign'  => self::plugin_module([
				'title'			=> __('Publish To Email', 'wpematico'),
				'desc'			=> __('Mail each post of a campaign to an address that publishes it, instead of publishing it here or as well as here.', 'wpematico'),
				'icon'			=> 'email',
				'type'			=> 'add_plugin',
				'category'		=> 'campaign',
				'badge'			=> 'AddOn',
				'pro'			=> true,
				'pro_link'		=> 'https://etruel.com/downloads/wpematico-publish-2-email/',
				'disabled_text' => __('Requires Email Campaign addon', 'wpematico'),
			], 'WPeMatico Publish 2 Email', 'wpematico-publish-2-email'),

			'better-excerpts' => self::plugin_module([
				'title'			=> __('Better Excerpts', 'wpematico'),
				'desc'			=> __('Cut the excerpt at the length you set and back to the end of the last whole sentence, never mid-word.', 'wpematico'),
				'icon'			=> 'editor-paragraph',
				'type'			=> 'add_plugin',
				'category'		=> ['external', 'seo'],
				'pro'			=> true,
				'badge'			=> 'AddOn',
				'pro_link'		=> 'https://etruel.com/downloads/wpematico-better-excerpts/',
				'disabled_text' => __('Requires Better Excerpts addon', 'wpematico'),
			], 'WPeMatico Better Excerpts', 'wpematico-better-excerpts'),

			'synchronizer'	  => self::plugin_module([
				'title'			=> __('Synchronizer', 'wpematico'),
				'desc'			=> __('Update the posts you already imported when the source article changes, instead of publishing a duplicate.', 'wpematico'),
				'icon'			=> 'update',
				'type'			=> 'add_plugin',
				'category'		=> 'external',
				'badge'			=> 'AddOn',
				'pro'			=> true,
				'pro_link'		=> 'https://etruel.com/downloads/wpematico-synchronizer/',
				'disabled_text' => __('Requires Synchronizer addon', 'wpematico'),
			], 'WPeMatico Synchronizer', 'wpematico-synchronizer'),

			'manual-fetching' => self::plugin_module([
				'title'			=> __('Manual Fetching', 'wpematico'),
				'desc'			=> __('See the items each feed will bring, and publish the ones you choose one by one or in bulk.', 'wpematico'),
				'icon'			=> 'controls-play',
				'type'			=> 'add_plugin',
				'category'		=> 'external',
				'badge'			=> 'AddOn',
				'pro'			=> true,
				'pro_link'		=> 'https://etruel.com/downloads/wpematico-manual-fetching/',
				'disabled_text' => __('Requires Manual Fetching addon', 'wpematico'),
			], 'WPeMatico Manual Fetching', 'wpematico-manual-fetching'),

			'exporter'		  => self::plugin_module([
				'title'			=> __('Exporter', 'wpematico'),
				'desc'			=> __('Export your posts to a file on a schedule, in the format your template defines, by download, folder, FTP or SSH.', 'wpematico'),
				'icon'			=> 'download',
				'type'			=> 'add_plugin',
				'category'		=> 'external',
				'badge'			=> 'AddOn',
				'pro'			=> true,
				'pro_link'		=> 'https://etruel.com/downloads/wpematico-exporter/',
				'disabled_text' => __('Requires Exporter addon', 'wpematico'),
			], 'WPeMatico Exporter', 'wpematico-exporter'),

			'office'		  => self::plugin_module([
				'title'			=> __('Office', 'wpematico'),
				'desc'			=> __('Publish posts from the .docx and PDF files in a folder of your server, with their text and their images.', 'wpematico'),
				'icon'			=> 'media-document',
				'type'			=> 'add_plugin',
				'category'		=> ['campaign', 'external'],
				'badge'			=> 'AddOn',
				'pro'			=> true,
				'pro_link'		=> 'https://etruel.com/downloads/wpematico-office-campaign-type/',
				'disabled_text' => __('Requires Office Campaign addon', 'wpematico'),
			], 'WPeMatico Office Campaign Type', 'wpematico-office-campaign-type'),
		];
	}

	private static function get_advanced_modules($cfg, $procfg) {
		return [
			'rewrite'		  => [
				'title'	   => __('Rewrite Rules', 'wpematico'),
				'desc'	   => __('Search and replace words or HTML in the fetched content, per campaign.', 'wpematico'),
				'icon'	   => 'edit',
				'type'	   => 'advanced',
				'category' => ['parser', 'core'],
				'option'	   => 'enablerewrite',
			],
			'premium-support' => [
				'title'			=> __('Customer Support', 'wpematico'),
				'desc'			=> __('Free setup assistance and troubleshooting, or step up to Premium Support.', 'wpematico'),
				'icon'			=> 'businessman',
				'type'			=> 'advanced',
				'category'		=> 'external',
				'badge'			=> 'FREE',
				'pro'			=> true,
				'pro_link'		=> 'https://etruel.com/my-account/support/',
				'disabled'		=> true,
				'disabled_text' => __('Requires registration at etruel.com', 'wpematico')
			]
		];
	}

	/**
	 * Every installed plugin, keyed by plugin file. Read once per request.
	 *
	 * @return array
	 */
	private static function installed_plugins() {
		static $plugins = null;

		if (null === $plugins) {
			if (!function_exists('get_plugins')) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$plugins = get_plugins();
		}

		return $plugins;
	}

	/**
	 * The plugin file of an addon, or '' when it is not installed.
	 *
	 * ★ Matched by the `Plugin Name` header first, because the folder is not stable:
	 * the same addon ships as `wpematico_full_content` from etruel.com and
	 * `wpematico-full-content` from wp.org. Same reasoning as
	 * WPeMatico::ADDONS_REQUIRED, which is keyed by name too.
	 *
	 * The folder is the fallback, with hyphens and underscores treated as the same
	 * character, for plugins whose name we cannot know beforehand.
	 *
	 * @param string $name   Plugin Name header, e.g. "WPeMatico Full Content".
	 * @param string $folder Expected folder, e.g. "wpematico-full-content".
	 * @return string Plugin file relative to the plugins dir, or ''.
	 */
	public static function find_plugin($name, $folder = '') {
		foreach (self::installed_plugins() as $file => $data) {
			if (!empty($name) && isset($data['Name']) && $data['Name'] === $name) {
				return $file;
			}
		}

		if (!empty($folder)) {
			$wanted = self::normalize_slug($folder);
			foreach (array_keys(self::installed_plugins()) as $file) {
				if (self::normalize_slug(dirname($file)) === $wanted) {
					return $file;
				}
			}
		}

		return '';
	}

	/**
	 * Folder names differ only by their separators between builds, so compare
	 * them without any. Also stops "wpematico-polylang" from matching
	 * "wpematico-polylang-pro", which the old substring search did.
	 */
	private static function normalize_slug($slug) {
		return strtolower(str_replace(['-', '_', '.'], '', (string) $slug));
	}

	/**
	 * Activate or deactivate the plugin a module stands for. Replaces the
	 * per-module callbacks that each hardcoded a path that did not exist.
	 *
	 * @param string $name   Plugin Name header.
	 * @param string $folder Expected folder.
	 * @param bool   $enable
	 * @return bool True when the plugin ended up in the requested state.
	 */
	public static function toggle_plugin($name, $folder, $enable) {
		$file = self::find_plugin($name, $folder);

		if ('' === $file) {
			return false;
		}

		if ($enable) {
			if (!is_plugin_active($file)) {
				return !is_wp_error(activate_plugin($file));
			}
			return true;
		}

		if (is_plugin_active($file)) {
			deactivate_plugins($file);
		}

		return true;
	}

	// =============================================
	// CALLBACK OF THE ONE MODULE THAT SWITCHES NEITHER AN OPTION NOR A PLUGIN
	// =============================================

	public static function plugin_wffimporters_callback($enable) {
		// The whole Migration Toolkit is loaded (or not) from this switch, on the next
		// request. Reset the detector either way so the state is clean.
		// Leading backslash: this file is in the WPeMatico\Module namespace and the
		// detector is a global class — class_exists() on a string resolves globally,
		// so without it the static call below would fatal.
		if (!class_exists('WPeMatico_Migration_Detect')) {
			return;
		}
		\WPeMatico_Migration_Detect::forget();
		if ($enable) {
			\WPeMatico_Migration_Detect::refresh();
		}
	}

	//PRO Callbacks



	



}
