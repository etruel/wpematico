<?php
/**
 * Guided tours of the WPeMatico screens, drawn with WordPress' own wp-pointer.
 *
 * Every step of every screen is declared in screens(): adding a step is adding an
 * entry there. A step whose target is not on the page is skipped, so a step may
 * point at something that only some sites show.
 *
 * The state belongs to each user (user meta META): which screens they finished or
 * dismissed, and whether they turned the tours off.
 *
 * @package WPeMatico
 * @since 2.9
 */
if (!defined('ABSPATH')) {
	exit;
}

class WPeMatico_Tour {

	const META = 'wpematico_tours';

	public static function hooks() {
		add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue'));
		add_action('admin_head', array(__CLASS__, 'help_sidebar_link'));
		add_action('wp_ajax_wpematico_tour', array(__CLASS__, 'ajax'));
	}

	/**
	 * The tours, one per screen key (see current_screen_key()).
	 *
	 * Each step: target (jQuery selector), title, text, and where the pointer sits
	 * against its target — edge (top|bottom|left|right) and align (top|bottom|left|right|middle|center).
	 * 'html' is printed after the text as is, for markup that is not a sentence.
	 */
	public static function screens() {
		$help = array(
			'target' => '#contextual-help-link-wrap',
			'title'	 => __('Need more?', 'wpematico'),
			'text'	 => __('The Help tab at the top of every WPeMatico screen explains it in detail.', 'wpematico'),
			'edge'	 => 'top',
			'align'	 => 'right',
		);

		return array(
			'dashboard'			=> array(
				'menu'		 => array(
					'target' => '#toplevel_page_wpematico_dashboard > a',
					'title'	 => __('Welcome to WPeMatico', 'wpematico'),
					'text'	 => __('Everything lives here: Dashboard, Campaigns, Settings, Tools and Extensions.', 'wpematico'),
					'edge'	 => 'left',
					'align'	 => 'middle',
				),
				'filters'	 => array(
					'target' => '.filterbymodule:first',
					'title'	 => __('One card per feature', 'wpematico'),
					'text'	 => __('Filter them by what they do: images, SEO, campaign types, parsers and more.', 'wpematico'),
					'edge'	 => 'top',
					'align'	 => 'left',
				),
				'switch'	 => array(
					'target' => '.wpematico-module-card .wpematico-switch:first',
					'title'	 => __('Switch features on and off', 'wpematico'),
					'text'	 => __('Each card says what its switch will do before you touch it, and installs whatever it needs.', 'wpematico'),
					'edge'	 => 'top',
					'align'	 => 'left',
				),
				'campaigns'	 => array(
					'target' => '#toplevel_page_wpematico_dashboard a[href="edit.php?post_type=wpematico"]',
					'title'	 => __('Your first campaign', 'wpematico'),
					'text'	 => __('A campaign reads one or more feeds and publishes their items as posts. Start here.', 'wpematico'),
					'edge'	 => 'left',
					'align'	 => 'middle',
				),
			),
			'campaigns'			=> array(
				'add'	 => array(
					'target' => '.wrap .page-title-action:first',
					'title'	 => __('Add a campaign', 'wpematico'),
					'text'	 => __('Give it a name, paste a feed address and choose where the posts go.', 'wpematico'),
					'edge'	 => 'top',
					'align'	 => 'left',
				),
				'state'	 => array(
					'target' => '.wp-list-table thead th#next',
					'title'	 => __('Run, schedule or pause', 'wpematico'),
					'text'	 => __('Each campaign has three controls here:', 'wpematico'),
					'html'	 => self::state_legend(),
					'edge'	 => 'top',
					'align'	 => 'center',
				),
				'last'	 => array(
					'target' => '.wp-list-table thead th#last',
					'title'	 => __('What each campaign did', 'wpematico'),
					'text'	 => __('When it last ran, how long it took and how many posts it has published.', 'wpematico'),
					'edge'	 => 'top',
					'align'	 => 'center',
				),
				'help'	 => $help,
			),
			'campaign'			=> array(
				'feeds'	 => array(
					'target' => '#feeds-box #addmorefeed',
					'title'	 => __('Feeds', 'wpematico'),
					'text'	 => __('Add the feed addresses this campaign reads. Check all feeds tells you whether each one answers.', 'wpematico'),
					'edge'	 => 'bottom',
					'align'	 => 'left',
				),
				'type'	 => array(
					'target' => '#campaign_types .hndle',
					'title'	 => __('Campaign type', 'wpematico'),
					'text'	 => __('Feed is the usual choice. Other types read XML files, YouTube or Vimeo, and more come with the extensions.', 'wpematico'),
					'edge'	 => 'right',
					'align'	 => 'top',
				),
				'cron'	 => array(
					'target' => '#cron-box .hndle',
					'title'	 => __('Schedule', 'wpematico'),
					'text'	 => __('How often the campaign runs on its own.', 'wpematico'),
					'edge'	 => 'top',
					'align'	 => 'left',
				),
				'wizard' => array(
					'target' => '.wp-heading-inline ~ .thickbox_open:first',
					'title'	 => __('The Wizard', 'wpematico'),
					'text'	 => __('Prefer a guided path? The Wizard walks you through every box, one by one.', 'wpematico'),
					'edge'	 => 'top',
					'align'	 => 'left',
				),
			),
			'settings_general'	=> array(
				'menu'	 => array(
					'target' => '.wpematico_menu',
					'title'	 => __('Settings menu', 'wpematico'),
					'text'	 => __('General, Advanced and Backend Tools hold WPeMatico\'s settings. Each extension adds its own tab below them.', 'wpematico'),
					'edge'	 => 'left',
					'align'	 => 'top',
				),
				'boxes'	 => array(
					'target' => '#imgs .hndle',
					'title'	 => __('Boxes', 'wpematico'),
					'text'	 => __('Collapse a box from its header or drag it to sort the screen your way.', 'wpematico'),
					'edge'	 => 'top',
					'align'	 => 'left',
				),
				'save'	 => array(
					'target' => '#wpematico-save-settings:visible:first',
					'title'	 => __('Save', 'wpematico'),
					'text'	 => __('Saves the section you are in. The others keep what they had.', 'wpematico'),
					'edge'	 => 'top',
					'align'	 => 'right',
				),
				'help'	 => array(
					'title'	 => __('Need more?', 'wpematico'),
					'text'	 => __('Every option has a (?) with a short explanation, and the Help tab has the whole story.', 'wpematico'),
				) + $help,
			),
			'settings_advanced'	=> array(
				'features'	 => array(
					'target' => '#enablefeatures .hndle',
					'title'	 => __('Extra features', 'wpematico'),
					'text'	 => __('Switch on content rewriting, words to categories, canonical URLs and the other optional features.', 'wpematico'),
					'edge'	 => 'top',
					'align'	 => 'left',
				),
				'actions'	 => array(
					'target' => '#wpem-advanced-actions .hndle',
					'title'	 => __('Advanced actions', 'wpematico'),
					'text'	 => __('Feed checks, logs, XML uploads and other behaviour for every campaign.', 'wpematico'),
					'edge'	 => 'top',
					'align'	 => 'left',
				),
			),
			'settings_backend'	=> array(
				'backend' => array(
					'target' => '#emptytrashdiv .hndle',
					'title'	 => __('Your admin screens', 'wpematico'),
					'text'	 => __('Choose the campaign columns, the boxes of the campaign editor, the dashboard widgets and these guided tours.', 'wpematico'),
					'edge'	 => 'top',
					'align'	 => 'left',
				),
			),
			'tools'				=> array(
				'feed_list'		 => array(
					'target' => 'a.nav-section[href*="section=feed_list"]',
					'title'	 => __('Feed List', 'wpematico'),
					'text'	 => __('Every feed of every campaign in one list, with its campaign, its status and its last run.', 'wpematico'),
					'edge'	 => 'top',
					'align'	 => 'left',
				),
				'feed_viewer'	 => array(
					'target' => 'a.nav-section[href*="section=feed_viewer"]',
					'title'	 => __('Feed Viewer', 'wpematico'),
					'text'	 => __('Paste any address to see what it really returns: a feed, a page that is not one, or why it cannot be reached.', 'wpematico'),
					'edge'	 => 'top',
					'align'	 => 'left',
				),
				'export'		 => array(
					'target' => 'a.nav-section[href*="section=tools"]',
					'title'	 => __('Export / Import', 'wpematico'),
					'text'	 => __('Move your settings to another site. Campaigns are not included.', 'wpematico'),
					'edge'	 => 'top',
					'align'	 => 'left',
				),
				'migration'		 => array(
					'target' => 'a.nav-section[href*="section=migration"]',
					'title'	 => __('Migration Toolkit', 'wpematico'),
					'text'	 => __('Bring your feeds over from Feedzy or WP RSS Aggregator, with the posts they already published.', 'wpematico'),
					'edge'	 => 'top',
					'align'	 => 'left',
				),
				'system'		 => array(
					'target' => 'a.nav-tab[href*="tab=debug_info"]',
					'title'	 => __('System', 'wpematico'),
					'text'	 => __('Status gathers the site and server details support will ask for. Danger Zone holds debug logging and what uninstalling removes.', 'wpematico'),
					'edge'	 => 'top',
					'align'	 => 'left',
				),
				'help'			 => $help,
			),
			'extensions'		=> array(
				'list'		 => array(
					'target' => 'table.plugins thead th#name',
					'title'	 => __('Extensions', 'wpematico'),
					'text'	 => __('Every WPeMatico add-on, installed or not. The free ones install from here.', 'wpematico'),
					'edge'	 => 'top',
					'align'	 => 'left',
				),
				'license'	 => array(
					'target' => 'table.plugins thead th#buybutton',
					'title'	 => __('Activate and license', 'wpematico'),
					'text'	 => __('Activate what you have, and enter its licence in Settings › Licenses to receive its updates.', 'wpematico'),
					'edge'	 => 'top',
					'align'	 => 'right',
				),
				'help'		 => $help,
			),
		);
	}

	/** The three controls of the Current State column, drawn even when the list is empty. */
	private static function state_legend() {
		$rows = array(
			'dashicons-controls-play'	 => __('Runs the campaign once, now.', 'wpematico'),
			'dashicons-update'			 => __('Turns its schedule on, so it runs on its own. It shows red while the schedule is on.', 'wpematico'),
			'dashicons-controls-pause'	 => __('Turns the schedule off, or stops a run in progress.', 'wpematico'),
		);
		$html = '<ul class="wpematico-tour-legend">';
		foreach ($rows as $icon => $text) {
			$html .= '<li><span class="dashicons ' . esc_attr($icon) . '" aria-hidden="true"></span> ' . esc_html($text) . '</li>';
		}
		return $html . '</ul>';
	}

	/** The tour that belongs to the screen being rendered, or '' when there is none. */
	public static function current_screen_key() {
		$screen = get_current_screen();
		if (!$screen) {
			return '';
		}
		switch ($screen->id) {
			case 'toplevel_page_wpematico_dashboard':
				return 'dashboard';
			case 'edit-wpematico':
				return 'campaigns';
			case 'wpematico':
				return 'campaign';
			case 'plugins_page_wpemaddons':
				return 'extensions';
			case 'wpematico_page_wpematico_settings':
				if ('settings' !== WPeMatico_Settings::current_tab()) {
					return '';
				}
				$keys = array(
					'general_settings'	 => 'settings_general',
					'advanced_actions'	 => 'settings_advanced',
					'backend_tools'		 => 'settings_backend',
				);
				$section = WPeMatico::current_screen_section('settings', wpematico_get_settings_sections());
				return isset($keys[$section]) ? $keys[$section] : '';
			case 'wpematico_page_wpematico_tools':
				$tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'tools';
				return ('tools' === $tab) ? 'tools' : '';
		}
		return '';
	}

	public static function state($user_id = 0) {
		$state = get_user_meta($user_id ? $user_id : get_current_user_id(), self::META, true);
		$state = is_array($state) ? $state : array();
		return array(
			'off'	 => !empty($state['off']),
			'done'	 => isset($state['done']) ? array_values(array_filter((array) $state['done'], 'is_string')) : array(),
		);
	}

	public static function is_on($user_id = 0) {
		$state = self::state($user_id);
		return !$state['off'];
	}

	/** Turning the tours back on shows every screen's tour again. */
	public static function set_on($on, $user_id = 0) {
		$user_id = $user_id ? $user_id : get_current_user_id();
		$state	 = self::state($user_id);
		if ($on && $state['off']) {
			$state = array('off' => false, 'done' => array());
		} elseif (!$on) {
			$state['off'] = true;
		}
		update_user_meta($user_id, self::META, $state);
	}

	public static function enqueue() {
		$key = self::current_screen_key();
		if ('' === $key) {
			return;
		}
		$screens = self::screens();
		if (empty($screens[$key])) {
			return;
		}

		$steps = array();
		foreach ($screens[$key] as $step) {
			$steps[] = array(
				'target'	 => $step['target'],
				'content'	 => '<h3>' . esc_html($step['title']) . '</h3><p>' . esc_html($step['text']) . '</p>' . (isset($step['html']) ? $step['html'] : ''),
				'edge'		 => $step['edge'],
				'align'		 => $step['align'],
			);
		}

		$state = self::state();
		wp_enqueue_style('wp-pointer');
		wp_add_inline_style('wp-pointer',
				'.wpematico-tour-legend{margin:0 15px 10px;padding:0;list-style:none}' .
				'.wpematico-tour-legend li{display:flex;gap:6px;align-items:flex-start;margin:6px 0}' .
				'.wpematico-tour-legend .dashicons{flex:none}' .
				'.wpematico-tour-buttons{display:flex;justify-content:space-between;align-items:center;gap:12px}' .
				// wp-pointer aligns the box to the right but leaves the arrow on the left.
				'.wpematico-tour-align-right.wp-pointer-top .wp-pointer-arrow,.wpematico-tour-align-right.wp-pointer-bottom .wp-pointer-arrow{left:auto;right:24px}'
		);
		wp_enqueue_script('wpematico-tour', WPeMatico::$uri . 'assets/js/tour.js', array('jquery', 'wp-pointer'), WPEMATICO_VERSION, true);
		wp_localize_script('wpematico-tour', 'wpematicoTour', array(
			'screen'	 => $key,
			'steps'		 => $steps,
			'autostart'	 => !$state['off'] && !in_array($key, $state['done'], true),
			'ajaxurl'	 => admin_url('admin-ajax.php'),
			'nonce'		 => wp_create_nonce('wpematico-tour'),
			'i18n'		 => array(
				'next'		 => __('Next', 'wpematico'),
				'dismiss'	 => __('Dismiss', 'wpematico'),
				'done'		 => __('Done', 'wpematico'),
				'off'		 => __('Don\'t show tours again', 'wpematico'),
			),
		));
	}

	/** "Show this screen's tour" at the foot of the Help tab, on the screens that have both. */
	public static function help_sidebar_link() {
		$key	 = self::current_screen_key();
		$screen	 = get_current_screen();
		if ('' === $key || !$screen || !$screen->get_help_tabs()) {
			return;
		}
		$screen->set_help_sidebar(
				$screen->get_help_sidebar() .
				'<p><a href="#" class="wpematico-tour-start">' . esc_html__('Show this screen\'s tour', 'wpematico') . '</a></p>'
		);
	}

	public static function ajax() {
		check_ajax_referer('wpematico-tour', 'nonce');
		$user_id = get_current_user_id();
		if (!$user_id) {
			wp_send_json_error();
		}
		$do = isset($_POST['do']) ? sanitize_key(wp_unslash($_POST['do'])) : '';
		if ('off' === $do) {
			self::set_on(false, $user_id);
			wp_send_json_success();
		}
		$key = isset($_POST['screen']) ? sanitize_key(wp_unslash($_POST['screen'])) : '';
		if ('done' !== $do || !array_key_exists($key, self::screens())) {
			wp_send_json_error();
		}
		$state = self::state($user_id);
		if (!in_array($key, $state['done'], true)) {
			$state['done'][] = $key;
		}
		// Once every tour has been seen the option reads off, as the user left it.
		if (!array_diff(array_keys(self::screens()), $state['done'])) {
			$state['off'] = true;
		}
		update_user_meta($user_id, self::META, $state);
		wp_send_json_success();
	}
}

WPeMatico_Tour::hooks();
