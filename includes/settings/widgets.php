<?php
// don't load directly 
if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

/**
 * WPeMatico Pro Extra Settings Class 
 * This class is used to add the Professional Extra Settings 
 * @since 2.2
 */
if (!class_exists('WPeMatico_Settings_widgets')) :

	class WPeMatico_Settings_widgets {

		/**
		 * Id of the first box of the sidebar being rendered.
		 *
		 * The top box of the column is the one that carries a filled colour header, and
		 * only that one. An addon registers its own box at priority 0, so this answers
		 * "is there an addon box above me?" without the About box having to know which
		 * tabs belong to an addon -- and a new addon needs to declare nothing.
		 *
		 * @since 2.9
		 * @var string
		 */
		private static $first_widget = '';

		/**
		 * The sidebar of the settings screen, as data.
		 *
		 * Registering each box on wpematico_settings_widget_tab_{tab} made the sidebar
		 * append-only: an addon could add a box but not drop one it did not register,
		 * and every tab got the same six regardless of what the screen was about. The
		 * list below is the sidebar; filter it to add, remove or replace a box.
		 *
		 * @param string $tab
		 * @param string $section
		 * @return array id => array('callback' => callable, 'priority' => int)
		 */
		public static function get_widgets($tab, $section = '') {
			return self::get_sidebar('settings', $tab, $section);
		}

		/**
		 * Returns the sidebar boxes for a screen, so both the Settings screen and the
		 * Tools > System Status screen render the same list.
		 *
		 * The wpematico_settings_widgets filters apply to the settings screen only. Use
		 * wpematico_sidebar_widgets_{screen} to change the sidebar of another screen.
		 *
		 * @since 2.9
		 * @param string $screen 'settings' | 'tools'
		 * @param string $tab
		 * @param string $section
		 * @return array id => array('callback' => callable, 'priority' => int)
		 */
		public static function get_sidebar($screen, $tab, $section = '') {
			$widgets = array(
				'about'			 => array('callback' => array(__CLASS__, 'about'), 'priority' => 10),
				'rate_us'		 => array('callback' => array(__CLASS__, 'rate_us'), 'priority' => 20),
				'support'		 => array('callback' => array(__CLASS__, 'support'), 'priority' => 30),
				'translate'		 => array('callback' => array(__CLASS__, 'translate'), 'priority' => 40),
				'memberships'	 => array('callback' => array(__CLASS__, 'memberships'), 'priority' => 50),
				'promoperfect'	 => array('callback' => array(__CLASS__, 'promoperfect'), 'priority' => 60),
				// The reviews this plugin has on wordpress.org, fetched remotely -- rate_us
				// above is the link to leave one. Drawn straight after render() until 2.9,
				// so no screen could leave it out of its sidebar.
				'wp_ratings'	 => array('callback' => array(__CLASS__, 'wp_ratings'), 'priority' => 70),
			);

			/**
			 * @since 2.9
			 * @param array  $widgets id => array('callback' => callable, 'priority' => int)
			 * @param string $screen  Which screen is drawing the sidebar.
			 * @param string $tab
			 * @param string $section
			 */
			$widgets = apply_filters('wpematico_sidebar_widgets', $widgets, $screen, $tab, $section);
			$widgets = apply_filters('wpematico_sidebar_widgets_' . $screen, $widgets, $tab, $section);

			if ('settings' === $screen) {
				$widgets = apply_filters('wpematico_settings_widgets', $widgets, $tab, $section);
				// The tab owns its sidebar: an addon sets it once here for every section
				// it ships, and a section only has to say what is different about itself.
				$widgets = apply_filters('wpematico_settings_widgets_' . $tab, $widgets, $section);
				if (!empty($section)) {
					$widgets = apply_filters('wpematico_settings_widgets_' . $tab . '_' . $section, $widgets);
				}
			}
			return $widgets;
		}

		/**
		 * Prints the sidebar of the current tab/section.
		 *
		 * @param string $tab
		 * @param string $section
		 */
		public static function render($tab, $section = '') {
			self::render_sidebar('settings', $tab, $section);
		}

		/**
		 * Prints the sidebar of a screen, in priority order.
		 *
		 * @since 2.9
		 * @param string $screen 'settings' | 'tools'
		 * @param string $tab
		 * @param string $section
		 */
		public static function render_sidebar($screen, $tab, $section = '') {
			$widgets = self::get_sidebar($screen, $tab, $section);

			uasort($widgets, function ($a, $b) {
				return ((int) (isset($a['priority']) ? $a['priority'] : 10)) <=> ((int) (isset($b['priority']) ? $b['priority'] : 10));
			});

			self::$first_widget = '';

			foreach ($widgets as $id => $widget) {
				if (empty($widget['callback']) || !is_callable($widget['callback'])) {
					continue;
				}
				if ('' === self::$first_widget) {
					self::$first_widget = $id;
				}
				call_user_func($widget['callback'], $tab, $section, $id);
			}

			if ('settings' === $screen) {
				// Kept firing for anything registered the old way.
				do_action('wpematico_settings_widget_tab_' . $tab, $section);
				if (!empty($section)) {
					do_action('wpematico_settings_widget_section_' . $section, $tab);
				}
			}
		}


		/**
		 * The WPeMatico box of the sidebar.
		 *
		 * Two shapes, one component. First box of the column: filled orange header with
		 * the mark, the name and the version, the same shape every addon box uses. With
		 * an addon box above it: the native grey header, so the column never carries two
		 * colour headers and the screen's own addon keeps the top of the hierarchy.
		 */
		public static function about($tab = '', $section = '', $id = 'about') {
			$hero = ($id === self::$first_widget);

			/**
			 * Whether this box takes the filled header. Answered by its position, which is
			 * what an addon already decides by registering its own box at priority 0.
			 *
			 * @since 2.9
			 */
			$hero = (bool) apply_filters('wpematico_settings_about_hero', $hero, $tab, $section);

			// An addon whose own box already sells the upgrade turns these off, so the
			// sidebar stops offering the customer what they are already using.
			$show_memberships = apply_filters('wpematico_settings_about_memberships', true, $tab, $section);
			$version = esc_html(WPEMATICO_VERSION);
			?>
			<div id="wpem-about" class="postbox wpem-about<?php echo $hero ? ' wpem-about-hero' : ''; ?>">
				<?php if ($hero) : ?>
					<div class="wpem-about-header">
						<img class="wpem-about-mark" src="<?php echo esc_url(WPeMatico::$uri . '/images/robotico-helmet.png'); ?>" alt="" />
						<div class="wpem-about-name">
							<strong>WPeMatico</strong>
							<span><?php esc_html_e('Making autoblogging easy', 'wpematico'); ?></span>
						</div>
						<span class="wpem-about-version">v<?php echo $version; ?></span>
					</div>
				<?php else : ?>
					<h3 class="wpematico-hndle">
						<img class="wpem-about-mark" src="<?php echo esc_url(WPeMatico::$uri . '/images/robotico-helmet.png'); ?>" alt="" />
						<span class="wpem-about-title"><?php printf(esc_html__('About %s', 'wpematico'), 'WPeMatico'); ?></span>
						<span class="wpem-about-version">v<?php echo $version; ?></span>
					</h3>
				<?php endif; ?>
				<div class="inside">
					<ul class="wpem-about-links">
						<li>
							<a href="https://www.wpematico.com" target="_blank" rel="noopener">
								<span class="dashicons dashicons-welcome-learn-more" aria-hidden="true"></span>
								<span class="wpem-about-link-text">
									<strong>WPeMatico Website</strong>
									<span><?php esc_html_e('Comments and Tutorials', 'wpematico'); ?></span>
								</span>
							</a>
						</li>
						<li>
							<a href="https://etruel.com" target="_blank" rel="noopener">
								<span class="dashicons dashicons-store" aria-hidden="true"></span>
								<span class="wpem-about-link-text">
									<strong>etruel.com</strong>
									<span><?php esc_html_e('Addons store, FAQs and free support', 'wpematico'); ?></span>
								</span>
							</a>
						</li>
					</ul>
					<?php if ($show_memberships) : ?>
					<div id="improvescampaign">
						<div id="improlabel">
							<?php esc_html_e('Improve your Experience', 'wpematico'); ?>
						</div>
						<div id="improbuttons">
							<a href="https://etruel.com/downloads/wpematico-essentials/" class="button" title="<?php esc_attr_e('Pro-ready: Let’s get started!', 'wpematico'); ?>" target="_blank"><?php esc_html_e('ESSENTIALS', 'wpematico'); ?></a>
							<a href="https://etruel.com/downloads/wpematico-premium/" class="button" title="<?php esc_attr_e('Leveling up: Premium mode!', 'wpematico'); ?>" target="_blank"><?php esc_html_e('PREMIUM', 'wpematico'); ?></a>
							<a href="https://etruel.com/downloads/wpematico-plus/" class="button" title="<?php esc_attr_e('Most powerfull addons', 'wpematico'); ?>" target="_blank"><?php esc_html_e('PLUS', 'wpematico'); ?></a>
							<a href="https://etruel.com/downloads/wpematico-perfect/" class="button" title="<?php esc_attr_e('Kicking off your Pro level to Perfect!', 'wpematico'); ?>" target="_blank"><?php esc_html_e('PERFECT', 'wpematico'); ?></a>
						</div>
					</div>
					<?php endif; ?>
				</div>
			</div>
			<?php
		}

		/**
		 * Banner Rate on WordPress
		 */
		public static function wp_ratings() {
			do_action('wpematico_wp_ratings');
		}

		public static function rate_us() {
			?>
			<div id="wpem-rate-us" class="postbox">
				<h3 class="wpematico-hndle"><?php _e('Rate us', 'wpematico'); ?></h3>
				<div class="inside">
					<h3 class="text-center"><?php _e('Thanks for using our plugin', 'wpematico'); ?></h3>
					<p class="description text-center"><?php _e('If you like this plugin, you can write a 5-star review on WordPress.', 'wpematico'); ?></p>
					<div class="wpematico_rating">
						<div class="wpematico_rating-stars">
							<span class="dashicons dashicons-star-filled"></span>
							<span class="dashicons dashicons-star-filled"></span>
							<span class="dashicons dashicons-star-filled"></span>
							<span class="dashicons dashicons-star-filled"></span>
							<span class="dashicons dashicons-star-filled"></span>
						</div>
						<a href="https://wordpress.org/support/view/plugin-reviews/wpematico?filter=5&amp;rate=5#new-post" id="linkrate" class="button button-primary" target="_Blank" title="<?php esc_attr_e('Click here to rate plugin on WordPress', 'wpematico'); ?>">  <?php esc_html_e('Rate on WordPress', 'wpematico'); ?> </a>
					</div>
				</div>
			</div>
			<?php
		}
		
		/**
		 * Banner Hot Sales starter memberships
		 */
		public static function memberships() {
			?>
			<div id="promo-extended" class = "postbox">
				<div class="ribbon"><span>HOT SALES</span></div>
				<h3 class='wpematico-hndle'><span>Starter Bundled Extensions</span></h3>
				<div class="inside">
					<div class="sidebar-promo worker" id="sidebar-promo-memberships">
						<h3><span class="dashicons dashicons-welcome-learn-more"></span> <?php _e('Extended functionalities', 'wpematico');
						?></h3>
						<p class="description">
							<?php
							/* translators: Link to Starter Memberships page */
							echo sprintf(__('Many AddOns make up the %s with the most wanted features.', 'wpematico') . '  ', '<a href="https://etruel.com/starter-memberships/" target="_blank" rel="noopener"><strong>WPeMatico Extensions</strong></a>');
							?> 
						</p>
						<p class="description">
							<?php _e('Lot of new features with contents, images, tags, filters, custom fields, custom feed tags and much more extends in the WPeMatico free plugin, going further than RSS feed limits and takes you to a new experience.', 'wpematico'); ?>
						</p>
						<div class="text-center" style="margin-top: 15px;">
							<a class="button button-primary" title="Features and prices" href="https://etruel.com/starter-memberships/" target="_blank"><?php _e('Pro status: Here we go!', 'wpematico'); ?></a>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
		
		/**
		 * Banner FAQ and etruel support on site
		 */
		public static function support() {
			?>
		<div id="promo-content" class="postbox">
				<h3 class='wpematico-hndle'><span>Support</span></h3>
				<div class="inside">
					<div class="sidebar-promo" id="sidebar-promo-support">
						<h3><span class="dashicons dashicons-sos"></span> <?php _e('Have some questions?', 'wpematico'); ?></h3>
						<p class="description">
							<?php _e('You may find answers in our', 'wpematico'); ?> <a target="_blank" href="https://etruel.com/faqs/">FAQ</a>.
						</p>
						<p class="description">
							<?php _e('You may', 'wpematico'); ?> <a target="_blank" href="https://etruel.com/my-account/support/"><?php _e('contact us', 'wpematico'); ?></a> <?php _e('with customization requests and suggestions.', 'wpematico'); ?>
						</p>
						<p class="description">
							<?php _e('Please visit our website to learn about our free and premium services at', 'wpematico'); ?> <a href="https://etruel.com/downloads/premium-support/" target="_blank" title="etruel.com">etruel.com</a>.
						</p>
					</div>
				</div>
			</div>
			<?php
		}
		
		/**
		 * Banner promo: the plugin's languages, and how to ask for one more
		 *
		 * The count is read from the catalogues on disk, so the box cannot promise a
		 * language the install does not carry -- and it grows on its own with every pass.
		 */
		public static function translate() {
			$locales = glob(WPEMATICO_PLUGIN_DIR . 'lang/' . WPeMatico::TEXTDOMAIN . '-*.mo');
			$count	 = is_array($locales) ? count($locales) : 0;
			?>
			<div id="translate" class="postbox " >
				<h3 class='wpematico-hndle'><span><?php esc_html_e('Languages', 'wpematico'); ?></span></h3>
				<div class="inside">
					<div class="sidebar-promo" id="sidebar-translate">
						<h3 class="translate"><span class="dashicons dashicons-translation"></span> <?php esc_html_e('Translation friendly', 'wpematico'); ?></h3>
						<?php if ($count > 1) { ?>
							<p class="title"><strong><?php esc_html_e('WPeMatico speaks your language', 'wpematico'); ?></strong></p>
							<p class="description"><?php printf(esc_html__('Ready in %1$s languages. The plugin follows whatever language you set in WordPress.', 'wpematico'), number_format_i18n($count)); ?></p>
						<?php } ?>
						<p class="title"><strong><?php esc_html_e('Miss your language?', 'wpematico'); ?></strong></p>
						<p class="description"><?php esc_html_e('Yours is not among them? Ask for it. Tell us which language you work in and we will put WPeMatico into it.', 'wpematico'); ?></p>
						<p style="text-align: center; margin-top: 12px;">
							<a class="button button-primary" href="https://etruel.com/my-account/support/" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Ask for your language', 'wpematico'); ?></a>
						</p>
					</div>
				</div>
			</div>
			<?php
		}
		
		/** One dot per extension on the orbit of the Perfect box. */
		const PERFECT_EXTENSIONS = 15;

		/**
		 * Banner promo Perfect
		 *
		 * The picture says what the claim says: the reactor -- our own mark, the one in
		 * the admin menu -- at the centre, and one dot per extension going round it.
		 * Drawn, so the box costs no image request and stays sharp at any zoom, and the
		 * dots are a single <circle>: a dash pattern with round caps spaces them evenly
		 * by itself, where markup would need one element each.
		 *
		 * The lettering is the serif already on every system. A webfont for one
		 * promotional box is not worth the request, and wordpress.org will not have one
		 * fetched from a third party.
		 */
		public static function promoperfect() {
			// Dots evenly spaced on the orbit: one dash period per extension, the dash
			// itself reduced to a point that the round cap turns into the dot.
			$orbit		= 2 * M_PI * 54;
			$dasharray	= '0.01 ' . round($orbit / self::PERFECT_EXTENSIONS - 0.01, 4);
			?>
			<div class="postbox wpem-perfect">
				<h3 class="wpematico-hndle"><?php esc_html_e('Perfect Membership', 'wpematico'); ?></h3>
				<div class="inside">
					<a class="wpem-perfect__card" href="https://etruel.com/downloads/wpematico-perfect/" target="_blank" rel="noopener noreferrer">
						<span class="wpem-perfect__stage" aria-hidden="true">
							<svg class="wpem-perfect__orbit" viewBox="0 0 128 128" width="128" height="128" focusable="false">
								<circle class="wpem-perfect__orbit-path" cx="64" cy="64" r="54"/>
								<circle class="wpem-perfect__orbit-dots" cx="64" cy="64" r="54" stroke-dasharray="<?php echo esc_attr($dasharray); ?>"/>
							</svg>
							<span class="wpem-perfect__core"><?php
								echo function_exists('wpematico_logo_svg') ? wpematico_logo_svg(46) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?></span>
						</span>
						<span class="wpem-perfect__claim">
							<span class="wpem-perfect__claim-1"><?php esc_html_e('One membership', 'wpematico'); ?></span>
							<span class="wpem-perfect__claim-2"><?php esc_html_e('to rule them all', 'wpematico'); ?></span>
						</span>
						<span class="wpem-perfect__sub"><?php esc_html_e('Every WPeMatico extension, one licence, one renewal.', 'wpematico'); ?></span>
						<span class="wpem-perfect__cta"><?php esc_html_e('Go Perfect', 'wpematico'); ?></span>
						<img class="wpem-perfect__mascot" src="<?php echo esc_url(WPEMATICO_PLUGIN_URL . 'images/robotico-helmet.png'); ?>" width="72" height="72" alt="" aria-hidden="true" />
					</a>
				</div>
			</div>
			<?php
		}
	}

	endif;
