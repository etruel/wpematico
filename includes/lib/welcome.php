<?php
/**
 * Welcome Page Class
 * @package     WPEMATICO
 * @subpackage  Admin/Welcome
 * @since       1.4
 */
// Exit if accessed directly
if(!defined('ABSPATH'))
	exit;

/**
 * WPEMATICO_Welcome Class
 *
 * A general class for About and changelog page.
 *
 * @since 1.4
 */
class WPEMATICO_Welcome {

	/**
	 * @var string The capability users should have to view the page
	 */
	public $minimum_capability	 = 'manage_options';
	public $api_url_subscription = 'https://www.wpematico.com/wp-admin/admin-post.php?action=wpmapirest_importdata';

	/**
	 * @var array Screen IDs of the four welcome pages.
	 */
	private $screens = array(
		'dashboard_page_wpematico-about',
		'dashboard_page_wpematico-getting-started',
		'dashboard_page_wpematico-changelog',
		'dashboard_page_wpematico-privacy',
	);

	/**
	 * Get things started
	 *
	 * @since 1.4
	 */
	public function __construct() {
		add_action('admin_menu', array($this, 'admin_menus'));
		add_action('admin_head', array($this, 'admin_head'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
		/* It'll be used on future.
		  add_action( 'admin_init', array( $this, 'prevent_double_act_redirect'), 9);
		 */
		add_action('admin_init', array($this, 'welcome'), 11);
		add_action('admin_post_save_subscription_wpematico', array($this, 'save_subscription'));
	}

	/**
	 * Register the Dashboard Pages which are later hidden but these pages
	 * are used to render the Welcome and changelog pages.
	 *
	 * @access public
	 * @since 1.4
	 * @return void
	 */
	public function admin_menus() {
		// About Page
		add_dashboard_page(
			__('Welcome to WPeMatico', 'wpematico'),
			__('WPeMatico About', 'wpematico'),
			$this->minimum_capability,
			'wpematico-about',
			array($this, 'about_screen')
		);

		// Changelog Page
		add_dashboard_page(
			__('WPeMatico Changelog', 'wpematico'),
			__('WPeMatico Changelog', 'wpematico'),
			$this->minimum_capability,
			'wpematico-changelog',
			array($this, 'changelog_screen')
		);

		// Getting Started Page
		add_dashboard_page(
			__('Getting started with WPeMatico', 'wpematico'),
			__('Getting started with WPeMatico', 'wpematico'),
			$this->minimum_capability,
			'wpematico-getting-started',
			array($this, 'getting_started_screen')
		);

		// Privacy
		add_dashboard_page(
			__('WPeMatico Privacy', 'wpematico'),
			__('WPeMatico Privacy', 'wpematico'),
			$this->minimum_capability,
			'wpematico-privacy',
			array($this, 'privacy_screen')
		);

	}


	/**
	 * Hide Individual Dashboard Pages
	 *
	 * @access public
	 * @since 1.4
	 * @return void
	 */
	public function admin_head() {
		// Now remove them from the menus so plugins that allow customizing the admin menu don't show them
//		remove_submenu_page( 'index.php', 'wpematico-about' );
		remove_submenu_page('index.php', 'wpematico-changelog');
		remove_submenu_page('index.php', 'wpematico-getting-started');
		remove_submenu_page('index.php', 'wpematico-privacy');
	}

	/**
	 * Load the stylesheet of the welcome screens, and only there.
	 *
	 * The screens carry their own stylesheet instead of reusing the classes of
	 * core's about.css: WordPress rewrites that file on major releases, which
	 * broke this layout before. See assets/css/welcome.css.
	 *
	 * @access public
	 * @since 2.9
	 * @param string $hook_suffix The current admin page.
	 * @return void
	 */
	public function enqueue_styles($hook_suffix) {
		if(!in_array($hook_suffix, $this->screens, true)) {
			return;
		}
		wp_enqueue_style('wpematico-welcome', WPEMATICO_PLUGIN_URL . 'assets/css/welcome.css', array('dashicons'), WPEMATICO_VERSION);
	}

	/**
	 * Welcome message
	 *
	 * @access public
	 * @since 2.5
	 * @return void
	 */
	public function welcome_message() {

		// The version should already be recorded by wpematico_install(). If it is
		// still not there, writing options is not sticking (typically a stale
		// object cache), so flag it: welcome() reads this flag to stop sending the
		// user here again and again, since the update check will keep matching.
		$stored_wpematico_version = get_option('wpematico_db_version');
		if(version_compare(WPEMATICO_VERSION, $stored_wpematico_version, '!=')) {
			set_transient('_wpematico_version_save_failed', true, DAY_IN_SECONDS);
			echo '<div style="display: block !important;" class="notice notice-error below-h2">' . __('WPeMatico could not update the version in your database. Please if your website has an object cache disable it.', 'wpematico') . '</div>';
		}

		list( $display_version ) = explode('-', WPEMATICO_VERSION);
		?>
		<div class="wpem-about__header">
			<img class="wpem-about__badge" src="<?php echo esc_url(WPEMATICO_PLUGIN_URL . 'images/robotico_orange-75x130.png'); ?>" alt="" />
			<div class="wpem-about__header-title">
				<h1><?php
					/* translators: %s WPeMatico Version */
					printf(__('WPeMatico %s', 'wpematico'), $display_version);
				?></h1>
			</div>
			<div class="wpem-about__header-text">
				<p>
					<?php
					_e('Thank you for updating to the latest version!', 'wpematico');
					/* translators: %s WPeMatico Version */
					printf(	'<br />'.__('WPeMatico %s is ready to make your autoblogging faster, safer, and better!', 'wpematico'),
						$display_version
					);
					?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Navigation tabs
	 *
	 * @access public
	 * @since 1.9
	 * @return void
	 */
	public function tabs() {
		$selected = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'wpematico-about';
		$tabs = array(
			'wpematico-about'			 => __("What's New", 'wpematico'),
			'wpematico-getting-started'	 => __('Getting Started', 'wpematico'),
			'wpematico-changelog'		 => __('Changelog', 'wpematico'),
			'wpematico-privacy'			 => __('Privacy', 'wpematico'),
		);
		?>
		<nav class="wpem-about__nav" aria-label="<?php esc_attr_e('Secondary menu', 'wpematico'); ?>">
			<?php foreach($tabs as $page => $label) : ?>
				<a class="wpem-about__nav-tab<?php echo $selected == $page ? ' wpem-about__nav-tab--active' : ''; ?>"<?php echo $selected == $page ? ' aria-current="page"' : ''; ?> href="<?php echo esc_url(admin_url(add_query_arg(array('page' => $page), 'index.php'))); ?>">
					<?php echo esc_html($label); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	/**
	 * URL of one of the plugin's admin screens.
	 *
	 * @access private
	 * @since 2.9
	 * @param string $screen dashboard|campaigns|settings|tools|extensions
	 * @return string
	 */
	private function screen_url($screen) {
		$urls = array(
			'dashboard'	 => 'admin.php?page=wpematico_dashboard',
			'campaigns'	 => 'edit.php?post_type=wpematico',
			'add'		 => 'post-new.php?post_type=wpematico',
			'settings'	 => 'admin.php?page=wpematico_settings',
			'tools'		 => 'admin.php?page=wpematico_tools',
			'extensions' => 'plugins.php?page=wpemaddons',
		);
		return admin_url(isset($urls[$screen]) ? $urls[$screen] : 'admin.php?page=wpematico_dashboard');
	}

	/**
	 * One of the screenshots of the About screen.
	 *
	 * @access private
	 * @since 2.9
	 * @param string $file File name inside images/.
	 * @param string $alt  Alternative text.
	 * @return void
	 */
	private function feature_image($file, $alt) {
		?>
		<div class="wpem-about__image">
			<img loading="lazy" src="<?php echo esc_url(WPEMATICO_PLUGIN_URL . 'images/' . $file); ?>" alt="<?php echo esc_attr($alt); ?>" width="800" height="800" />
		</div>
		<?php
	}

	/**
	 * Render About Screen
	 *
	 * @access public
	 * @since 1.4
	 * @return void
	 */
	public function about_screen() {
		list( $display_version ) = explode('-', WPEMATICO_VERSION);
		?>
		<div class="wrap wpem-about">

			<?php $this->welcome_message(); ?>

			<?php $this->tabs(); ?>

			<div class="wpem-about__section">
				<div class="wpem-about__column wpem-about__column--no-left wpem-about__column--no-right">
					<h2><?php
						/* translators: %s WPeMatico Version */
						printf(__('Welcome to WPeMatico %s', 'wpematico'), $display_version);
					?></h2>
					<p class="wpem-about__subheading"><?php _e('WPeMatico 2.9 is the biggest update in years. A new Dashboard puts the most important features of the plugin and its add-ons behind a single switch, the settings and the tools were rebuilt from the ground up, Vimeo campaigns are now free, and a new Migration Toolkit brings your feeds over from other RSS plugins. And everything feels faster.', 'wpematico'); ?></p>
				</div>
			</div>

			<div class="wpem-about__section wpem-about__section--2-columns">
				<div class="wpem-about__column wpem-about__column--center wpem-about__column--no-left">
					<h3><?php _e('One Dashboard for the features you use most', 'wpematico'); ?></h3>
					<p>
						<strong><?php _e('Every feature of the plugin and its add-ons, as a card with a switch.', 'wpematico'); ?></strong><br />
						<?php _e('Filter by category to find what you need and flip it on right there. Each card tells you whether the feature is already installed, free, or part of an add-on, and the card and the setting behind it are the same switch.', 'wpematico'); ?>
					</p>
					<p><a href="<?php echo esc_url($this->screen_url('dashboard')); ?>"><?php _e('Open the Dashboard', 'wpematico'); ?> &rarr;</a></p>
				</div>
				<div class="wpem-about__column wpem-about__column--center wpem-about__column--no-right">
					<?php $this->feature_image('about-29-dashboard.jpg', __('The WPeMatico Dashboard: a grid of feature cards, each with a switch', 'wpematico')); ?>
				</div>
			</div>

			<div class="wpem-about__section wpem-about__section--2-columns">
				<div class="wpem-about__column wpem-about__column--center wpem-about__column--no-left">
					<?php $this->feature_image('about-29-settings.jpg', __('The new Settings screen, with its side menu', 'wpematico')); ?>
				</div>
				<div class="wpem-about__column wpem-about__column--center wpem-about__column--no-right">
					<h3><?php _e('Settings, rebuilt from the ground up', 'wpematico'); ?></h3>
					<p>
						<strong><?php _e('A side menu, three named sections, and options that explain themselves.', 'wpematico'); ?></strong><br />
						<?php _e('General, Advanced and Backend Tools replace the single long page, and an option that depends on another one now tells you so instead of silently doing nothing.', 'wpematico'); ?>
					</p>
					<p><a href="<?php echo esc_url($this->screen_url('settings')); ?>"><?php _e('Open the Settings', 'wpematico'); ?> &rarr;</a></p>
				</div>
			</div>

			<div class="wpem-about__section wpem-about__section--2-columns">
				<div class="wpem-about__column wpem-about__column--center wpem-about__column--no-left">
					<h3><?php _e('Bring your feeds from Feedzy or WP RSS Aggregator', 'wpematico'); ?></h3>
					<p>
						<strong><?php _e('The new Migration Toolkit recreates your feeds as WPeMatico campaigns.', 'wpematico'); ?></strong><br />
						<?php _e('It shows you what it will create before it touches anything, and imports each feed with its schedule, post type, status, author and categories. Campaigns arrive deactivated, so nothing publishes until you decide.', 'wpematico'); ?>
					</p>
					<p><?php _e('Switch on the Plugin Importers card in the Dashboard to get started.', 'wpematico'); ?></p>
				</div>
				<div class="wpem-about__column wpem-about__column--center wpem-about__column--no-right">
					<?php $this->feature_image('about-29-migration.jpg', __('The Migration Toolkit, with the feeds it found on the site', 'wpematico')); ?>
				</div>
			</div>

			<div class="wpem-about__section wpem-about__section--2-columns">
				<div class="wpem-about__column wpem-about__column--center wpem-about__column--no-left">
					<?php $this->feature_image('about-29-vimeo.jpg', __('The Vimeo box of a campaign', 'wpematico')); ?>
				</div>
				<div class="wpem-about__column wpem-about__column--center wpem-about__column--no-right">
					<h3><?php _e('Vimeo campaigns are now free', 'wpematico'); ?></h3>
					<p>
						<strong><?php _e('It was a paid add-on. Now it is built into WPeMatico.', 'wpematico'); ?></strong><br />
						<?php _e('Paste the address of a Vimeo user, channel or group and the campaign finds the feed on its own: title, thumbnail, player and description, with switches for the embed, the frame size and the featured image.', 'wpematico'); ?>
					</p>
					<p><?php _e('Switch it on from its card in the Dashboard, or in Settings → Advanced → Enable Features.', 'wpematico'); ?></p>
				</div>
			</div>

			<div class="wpem-about__section wpem-about__section--2-columns">
				<div class="wpem-about__column wpem-about__column--center wpem-about__column--no-left">
					<h3><?php _e('Your campaigns at a glance, on the WordPress dashboard', 'wpematico'); ?></h3>
					<p>
						<strong><?php _e('Four numbers, a 7-day chart, and a tab that only appears when something needs you.', 'wpematico'); ?></strong><br />
						<?php _e('The rebuilt WPeMatico Summary widget shows posts today, active campaigns, the next run and all-time posts. A second widget, WPeMatico News, keeps the latest from the blog one glance away.', 'wpematico'); ?>
					</p>
				</div>
				<div class="wpem-about__column wpem-about__column--center wpem-about__column--no-right">
					<?php $this->feature_image('about-29-widgets.jpg', __('The WPeMatico Summary widget on the WordPress dashboard', 'wpematico')); ?>
				</div>
			</div>

			<hr class="wpem-about__hr--large" />

			<div class="wpem-about__section">
				<div class="wpem-about__column wpem-about__column--no-left wpem-about__column--no-right">
					<h3><?php _e('More in 2.9', 'wpematico'); ?></h3>
				</div>
			</div>

			<div class="wpem-about__section wpem-about__section--3-columns wpem-about__section--gutters">
				<div class="wpem-about__column wpem-about__column--no-left">
					<div class="wpem-about__image wpem-about__image--icon">
						<span class="dashicons dashicons-list-view" aria-hidden="true"></span>
					</div>
					<h3 class="wpem-about__heading--small"><?php _e('Feed List', 'wpematico'); ?></h3>
					<p><?php _e('Tools now opens on a table with every feed of the site: search it, sort it, and jump to the campaign or to the Feed Viewer.', 'wpematico'); ?></p>
					<p><a href="<?php echo esc_url($this->screen_url('tools')); ?>"><?php _e('Open the Tools', 'wpematico'); ?> &rarr;</a></p>
				</div>
				<div class="wpem-about__column">
					<div class="wpem-about__image wpem-about__image--icon">
						<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
					</div>
					<h3 class="wpem-about__heading--small"><?php _e('The Feed Viewer, rebuilt', 'wpematico'); ?></h3>
					<p><?php _e('It reports format, title, item count and HTTP code, finds the feed when you paste a page, and gives you the real reason a request failed.', 'wpematico'); ?></p>
				</div>
				<div class="wpem-about__column wpem-about__column--no-right">
					<div class="wpem-about__image wpem-about__image--icon">
						<span class="dashicons dashicons-performance" aria-hidden="true"></span>
					</div>
					<h3 class="wpem-about__heading--small"><?php _e('System Status opens instantly', 'wpematico'); ?></h3>
					<p><?php _e('The connection test no longer holds the page back, every check says why it is green, amber or red, and the hosting detection knows the big providers.', 'wpematico'); ?></p>
				</div>
			</div>

			<div class="wpem-about__section wpem-about__section--3-columns wpem-about__section--gutters">
				<div class="wpem-about__column wpem-about__column--no-left">
					<div class="wpem-about__image wpem-about__image--icon">
						<span class="dashicons dashicons-admin-plugins" aria-hidden="true"></span>
					</div>
					<h3 class="wpem-about__heading--small"><?php _e('The Add-ons page installs the free ones', 'wpematico'); ?></h3>
					<p><?php _e('The free add-ons install with one click, the ones you already have offer Activate, and the memberships are listed at last.', 'wpematico'); ?></p>
					<p><a href="<?php echo esc_url($this->screen_url('extensions')); ?>"><?php _e('Open the Extensions', 'wpematico'); ?> &rarr;</a></p>
				</div>
				<div class="wpem-about__column">
					<div class="wpem-about__image wpem-about__image--icon">
						<span class="dashicons dashicons-search" aria-hidden="true"></span>
					</div>
					<h3 class="wpem-about__heading--small"><?php _e('Campaign Preview for every campaign type', 'wpematico'); ?></h3>
					<p><?php _e('It asks the fetch engine the same question a run does, so it works with every campaign type — Vimeo and the types an add-on registers included.', 'wpematico'); ?></p>
				</div>
				<div class="wpem-about__column wpem-about__column--no-right">
					<div class="wpem-about__image wpem-about__image--icon">
						<span class="dashicons dashicons-update" aria-hidden="true"></span>
					</div>
					<h3 class="wpem-about__heading--small"><?php _e('Updates that never pull the rug', 'wpematico'); ?></h3>
					<p><?php _e('An add-on that falls behind keeps running and updating; only the features that need the new core wait, and a notice says which version puts it right.', 'wpematico'); ?></p>
				</div>
			</div>

			<hr class="wpem-about__hr--invisible wpem-about__hr--large" />

			<div class="wpem-about__section wpem-about__section--2-columns wpem-about__section--wider-left wpem-about__section--subtle wpem-about__section--feature">
				<h3 class="wpem-about__section-header"><?php _e('The complete changelog', 'wpematico'); ?></h3>
				<div class="wpem-about__column">
					<p><?php
						/* translators: %s WPeMatico Version */
						printf(__('For the complete list of changes in WPeMatico %s, read the changelog.', 'wpematico'), $display_version);
					?></p>
				</div>
				<div class="wpem-about__column wpem-about__column--aligncenter">
					<div class="wpem-about__image">
						<a href="<?php echo esc_url(admin_url(add_query_arg(array('page' => 'wpematico-changelog'), 'index.php'))); ?>" class="button button-primary button-hero"><?php _e('See everything new', 'wpematico'); ?></a>
					</div>
				</div>
			</div>

			<?php $this->subscription_form(); ?>

			<hr class="wpem-about__hr--large" />

			<div class="wpem-about__section wpem-about__section--2-columns">
				<div class="wpem-about__column wpem-about__column--no-left">
					<h3><?php _e('Take WPeMatico further', 'wpematico'); ?></h3>
					<p><?php _e('Add-ons and memberships extend the free plugin: the complete article from the source instead of the RSS snippet, keyword filters, custom titles, translation, AI rewriting, and much more.', 'wpematico'); ?></p>
					<p><?php _e('The new Dashboard shows the main ones as cards, so you can see what an add-on would switch on before you get it.', 'wpematico'); ?></p>
					<div class="wpem-about__addon-links">
						<a href="https://etruel.com/starter-memberships/" target="_blank"><?php _e('WPeMatico Starter Memberships', 'wpematico'); ?></a>
						<a href="https://etruel.com/downloads/category/wpematico-add-ons/" target="_blank"><?php _e('All available add-ons', 'wpematico'); ?></a>
					</div>
				</div>
				<div class="wpem-about__column wpem-about__column--no-right">
					<div class="wpem-about__addon">
						<div class="wpem-about__addon-img">
							<img src="<?php echo esc_url(WPEMATICO_PLUGIN_URL . 'images/wpematico-essentials-200x100.jpg'); ?>" alt="WPeMatico ESSENTIALS Monthly" />
						</div>
						<div class="wpem-about__addon-text">
							<p><?php _e('The', 'wpematico'); ?> <a href="https://etruel.com/downloads/wpematico-essentials-monthly/" target="_blank">WPeMatico ESSENTIALS Monthly</a>
							<?php _e('includes powerful add-ons such as Professional and Full Content, which allow you to enhance autoblogging with advanced features.', 'wpematico'); ?></p>
						</div>
					</div>

					<div class="wpem-about__addon">
						<div class="wpem-about__addon-img">
							<img src="<?php echo esc_url(WPEMATICO_PLUGIN_URL . 'images/wpematico-plus-200x100.jpg'); ?>" alt="WPeMatico PLUS" />
						</div>
						<div class="wpem-about__addon-text">
							<p><?php _e('The', 'wpematico'); ?> <a href="https://etruel.com/downloads/wpematico-plus/" target="_blank">WPeMatico PLUS</a>
								<?php _e('combines the five most requested add-ons with a lot of great features, simplifying WordPress autoblogging with professional ease.', 'wpematico'); ?></p>
						</div>
					</div>

					<div class="wpem-about__addon">
						<div class="wpem-about__addon-img">
							<img src="<?php echo esc_url(WPEMATICO_PLUGIN_URL . 'images/ai-etruel-rewriter-api-200x100.jpg'); ?>" alt="AI Etruel Rewriter API" />
						</div>
						<div class="wpem-about__addon-text">
						<p><?php _e('The', 'wpematico'); ?> <a href="https://etruel.com/downloads/ai-etruel-rewriter-api/" target="_blank">AI Etruel Rewriter API</a>
						<?php _e('integrates seamlessly with GPT Spinner, allowing you to rewrite and enhance your content with advanced AI, ensuring originality and improved engagement.', 'wpematico'); ?></p>
						</div>
					</div>

					<div class="wpem-about__addon">
						<div class="wpem-about__addon-img">
							<img src="<?php echo esc_url(WPEMATICO_PLUGIN_URL . 'images/wpematico-rss-feed-reader-200x100.png'); ?>" alt="WPeMatico RSS Feed Reader" />
						</div>
						<div class="wpem-about__addon-text">
							<p><?php _e('The free', 'wpematico'); ?> <a href="https://wordpress.org/plugins/wpematico-rss-feed-reader/" target="_blank">WPeMatico RSS Feed Reader</a>
								<?php _e('reads and displays the RSS feed results on your WordPress site without creating the posts. Install it from the Extensions page.', 'wpematico'); ?></p>
						</div>
					</div>
				</div>
			</div>

			<div class="wpem-about__section wpem-about__section--2-columns">
				<div class="wpem-about__column wpem-about__column--no-left">
					<div class="wpem-about__image wpem-about__image--icon">
						<span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
					</div>
					<h3 class="wpem-about__heading--small"><a href="https://wordpress.org/support/view/plugin-reviews/wpematico?filter=5&rate=5#new-post" target="_blank"><?php _e('Rate WPeMatico 5 stars on WordPress.org', 'wpematico'); ?></a></h3>
					<p><?php _e('Your 5-star rating helps other people find the plugin, and your comment helps us make it better.', 'wpematico'); ?></p>
				</div>
				<div class="wpem-about__column wpem-about__column--no-right">
					<div class="wpem-about__image wpem-about__image--icon">
						<span class="dashicons dashicons-tickets-alt" aria-hidden="true"></span>
					</div>
					<h3 class="wpem-about__heading--small"><a href="https://etruel.com/my-account/support/" target="_blank"><?php _e('Support ticket system for free', 'wpematico'); ?></a></h3>
					<p><?php _e('Ask about any problem you may have and you will get support for free. If it is necessary we will look into your website to solve your issue.', 'wpematico'); ?> <?php echo '<a href="https://etruel.com/downloads/premium-support/" target="_blank">' . __('Premium Support', 'wpematico') . '</a> ' . __('is there for customers that need faster or more in-depth assistance.', 'wpematico'); ?></p>
				</div>
			</div>

			<hr class="wpem-about__hr--large" />

			<div class="wpem-about__return">
				<a href="<?php echo esc_url($this->screen_url('dashboard')); ?>"><?php _e('Go to WPeMatico Dashboard', 'wpematico'); ?></a> |
				<a href="<?php echo esc_url($this->screen_url('settings')); ?>"><?php _e('Go to WPeMatico Settings', 'wpematico'); ?></a>
			</div>

		</div>
		<?php
	}

	/**
	 * Render Changelog Screen
	 *
	 * @access public
	 * @since 2.0.3
	 * @return void
	 */
	public function changelog_screen() {
		?>

		<div class="wrap wpem-about">

			<?php $this->welcome_message(); ?>

			<?php $this->tabs(); ?>

			<div class="wpem-about__section">
				<div class="wpem-about__column wpem-about__column--no-left wpem-about__column--no-right">
					<h2><?php _e('Full Changelog', 'wpematico'); ?></h2>
					<div class="wpem-about__changelog">
						<?php echo $this->parse_readme(); ?>
					</div>
				</div>
			</div>

			<hr class="wpem-about__hr--large" />

			<div class="wpem-about__return">
				<a href="<?php echo esc_url($this->screen_url('settings')); ?>"><?php _e('Go to WPeMatico Settings', 'wpematico'); ?></a>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Privacy Screen
	 *
	 * @access public
	 * @since 2.0.3
	 * @return void
	 */
	public function privacy_screen() {
		?>

		<div class="wrap wpem-about">

			<?php $this->welcome_message(); ?>

			<?php $this->tabs(); ?>

			<div class="wpem-about__section">
				<div class="wpem-about__column wpem-about__column--no-left wpem-about__column--no-right">
					<h2><?php _e('Privacy terms', 'wpematico'); ?></h2>
					<div class="wpem-about__changelog">
						<?php echo $this->parse_privacy(); ?>
					</div>
				</div>
			</div>

			<hr class="wpem-about__hr--large" />

			<div class="wpem-about__return">
				<a href="<?php echo esc_url($this->screen_url('settings')); ?>"><?php _e('Go to WPeMatico Settings', 'wpematico'); ?></a>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Getting Started Screen
	 *
	 * @access public
	 * @since 1.9
	 * @return void
	 */
	public function getting_started_screen() {
		?>
		<div class="wrap wpem-about">

			<?php $this->welcome_message(); ?>

			<?php $this->tabs(); ?>

			<div class="wpem-about__section">
				<div class="wpem-about__column wpem-about__column--no-left wpem-about__column--no-right">
					<h2><?php _e('Getting started with WPeMatico', 'wpematico'); ?></h2>
					<p class="wpem-about__subheading"><?php _e('WPeMatico reads the RSS and Atom feeds of your choice on a schedule and publishes their items as posts — or as any post type — on complete autopilot. Three steps and your first campaign is running; everything else can wait until it is.', 'wpematico'); ?></p>
				</div>
			</div>

			<div class="wpem-about__section wpem-about__section--2-columns">
				<div class="wpem-about__column wpem-about__column--center wpem-about__column--no-left">
					<h3><?php _e('1. Add the campaign and its feed', 'wpematico'); ?></h3>
					<p>
						<strong><?php _e('WPeMatico → Campaigns → Add New Campaign.', 'wpematico'); ?></strong><br />
						<?php _e('Give the campaign a name and paste the address of the feed in the Feeds box — for a WordPress blog it is usually the address of the site followed by /feed. The eye icon beside the feed reads it right there, so you know it works before you save. One campaign can carry as many feeds as you want.', 'wpematico'); ?>
					</p>
					<p><?php _e('If you would rather be guided, the Wizard button walks you through the boxes one by one.', 'wpematico'); ?></p>
					<p><a href="<?php echo esc_url($this->screen_url('add')); ?>"><?php _e('Add a campaign', 'wpematico'); ?> &rarr;</a></p>
				</div>
				<div class="wpem-about__column wpem-about__column--center wpem-about__column--no-right">
					<?php $this->feature_image('getting-started-29-campaign-editor.jpg', __('The campaign editor, with the Feeds box', 'wpematico')); ?>
				</div>
			</div>

			<div class="wpem-about__section wpem-about__section--2-columns">
				<div class="wpem-about__column wpem-about__column--center wpem-about__column--no-left">
					<?php $this->feature_image('getting-started-29-campaign-options.jpg', __('The campaign options: post type, status, author and categories', 'wpematico')); ?>
				</div>
				<div class="wpem-about__column wpem-about__column--center wpem-about__column--no-right">
					<h3><?php _e('2. Say where the posts go, and how often', 'wpematico'); ?></h3>
					<p>
						<strong><?php _e('The defaults already work: one published post per feed item.', 'wpematico'); ?></strong><br />
						<?php _e('In Options for this campaign you set how many items to bring on each run, the author of the new posts and what to do with their content. In the Publish box beside it, the status and the post type — Draft instead of Published if you want to review first — and the schedule that decides how often the campaign runs.', 'wpematico'); ?>
					</p>
				</div>
			</div>

			<div class="wpem-about__section wpem-about__section--2-columns">
				<div class="wpem-about__column wpem-about__column--center wpem-about__column--no-left">
					<h3><?php _e('3. Preview it, run it, and you are done', 'wpematico'); ?></h3>
					<p>
						<strong><?php _e('The Campaign Control Panel, beside the Publish button.', 'wpematico'); ?></strong><br />
						<?php _e('Preview shows the items the campaign would bring without publishing anything. Run now does it once, and the panel then tells you when it ran, how long it took and how many posts it fetched. From there on the campaign runs on its own, on the schedule you set.', 'wpematico'); ?>
					</p>
				</div>
				<div class="wpem-about__column wpem-about__column--center wpem-about__column--no-right">
					<?php $this->feature_image('getting-started-29-control-panel.jpg', __('The Campaign Control Panel', 'wpematico')); ?>
				</div>
			</div>

			<hr class="wpem-about__hr--large" />

			<div class="wpem-about__section">
				<div class="wpem-about__column wpem-about__column--no-left wpem-about__column--no-right">
					<h3><?php _e('Once it is running', 'wpematico'); ?></h3>
				</div>
			</div>

			<div class="wpem-about__section wpem-about__section--3-columns wpem-about__section--gutters">
				<div class="wpem-about__column wpem-about__column--no-left">
					<div class="wpem-about__image wpem-about__image--icon">
						<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
					</div>
					<h3 class="wpem-about__heading--small"><?php _e('Keep an eye on it', 'wpematico'); ?></h3>
					<p><?php _e('The Campaigns list shows the state, the last run and the post count of every campaign, and you can run or pause any of them from its row. The WPeMatico Summary widget puts the same on your WordPress dashboard.', 'wpematico'); ?></p>
					<p><a href="<?php echo esc_url($this->screen_url('campaigns')); ?>"><?php _e('Open the Campaigns', 'wpematico'); ?> &rarr;</a></p>
				</div>
				<div class="wpem-about__column">
					<div class="wpem-about__image wpem-about__image--icon">
						<span class="dashicons dashicons-screenoptions" aria-hidden="true"></span>
					</div>
					<h3 class="wpem-about__heading--small"><?php _e('Switch on more features', 'wpematico'); ?></h3>
					<p><?php _e('The Dashboard turns every feature into a card with a switch: other campaign types such as YouTube, Vimeo or Feeds XML, and extras such as Download Images or Canonical URLs.', 'wpematico'); ?></p>
					<p><a href="<?php echo esc_url($this->screen_url('dashboard')); ?>"><?php _e('Open the Dashboard', 'wpematico'); ?> &rarr;</a></p>
				</div>
				<div class="wpem-about__column wpem-about__column--no-right">
					<div class="wpem-about__image wpem-about__image--icon">
						<span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
					</div>
					<h3 class="wpem-about__heading--small"><?php _e('The settings behind every campaign', 'wpematico'); ?></h3>
					<p><?php _e('Settings holds what is global: how images and videos are handled, the scheduler that runs your campaigns, and the Backend Tools, such as the Campaign column in your post lists.', 'wpematico'); ?></p>
					<p><a href="<?php echo esc_url($this->screen_url('settings')); ?>"><?php _e('Open the Settings', 'wpematico'); ?> &rarr;</a></p>
				</div>
			</div>

			<div class="wpem-about__section wpem-about__section--3-columns wpem-about__section--gutters">
				<div class="wpem-about__column wpem-about__column--no-left">
					<div class="wpem-about__image wpem-about__image--icon">
						<span class="dashicons dashicons-rss" aria-hidden="true"></span>
					</div>
					<h3 class="wpem-about__heading--small"><?php _e('Test a feed before you use it', 'wpematico'); ?></h3>
					<p><?php _e('Paste any address in the Feed Viewer and it tells you whether it is a feed, its format and how many items it carries — or the real reason it cannot be read.', 'wpematico'); ?></p>
					<p><a href="<?php echo esc_url($this->screen_url('tools')); ?>"><?php _e('Open the Tools', 'wpematico'); ?> &rarr;</a></p>
				</div>
				<div class="wpem-about__column">
					<div class="wpem-about__image wpem-about__image--icon">
						<span class="dashicons dashicons-editor-help" aria-hidden="true"></span>
					</div>
					<h3 class="wpem-about__heading--small"><?php _e('The answers are next to the options', 'wpematico'); ?></h3>
					<p><?php _e('The (?) marks beside every option explain what it does, and often suggest a setting. The Help tab at the top right of each WPeMatico screen opens the documentation for that screen.', 'wpematico'); ?></p>
				</div>
				<div class="wpem-about__column wpem-about__column--no-right">
					<div class="wpem-about__image wpem-about__image--icon">
						<span class="dashicons dashicons-migrate" aria-hidden="true"></span>
					</div>
					<h3 class="wpem-about__heading--small"><?php _e('Coming from another RSS plugin?', 'wpematico'); ?></h3>
					<p><?php _e('The Migration Toolkit recreates the feeds of Feedzy or WP RSS Aggregator as WPeMatico campaigns, deactivated until you decide to start them.', 'wpematico'); ?></p>
				</div>
			</div>

			<hr class="wpem-about__hr--large" />

			<div class="wpem-about__section wpem-about__section--2-columns wpem-about__section--subtle">
				<h3 class="wpem-about__section-header"><?php _e('Need more help?', 'wpematico'); ?></h3>
				<div class="wpem-about__column">
					<div class="wpem-about__image wpem-about__image--icon">
						<span class="dashicons dashicons-tickets-alt" aria-hidden="true"></span>
					</div>
					<h4><?php _e('Support ticket system for free', 'wpematico'); ?></h4>
					<p><?php echo __('We do our best to provide the best support we can. If you encounter a problem or have a question, simply open a ticket using our ', 'wpematico') . '<a target="_blank" href="https://etruel.com/my-account/support/">' . __('support form', 'wpematico') . '</a>.'; ?></p>
				</div>
				<div class="wpem-about__column">
					<div class="wpem-about__image wpem-about__image--icon">
						<span class="dashicons dashicons-awards" aria-hidden="true"></span>
					</div>
					<h4><?php _e('Need even better support?', 'wpematico'); ?></h4>
					<p><?php echo __('Our ', 'wpematico') . '<a target="_blank" href="https://etruel.com/downloads/premium-support/">' . __('Premium Support', 'wpematico') . '</a> ' . __('service is there for customers that need faster or more in-depth assistance, including setting things up on your site.', 'wpematico'); ?></p>
				</div>
			</div>

			<hr class="wpem-about__hr--invisible" />

			<div class="wpem-about__section wpem-about__section--2-columns wpem-about__section--subtle">
				<h3 class="wpem-about__section-header"><?php _e('WPeMatico add-ons', 'wpematico'); ?></h3>
				<div class="wpem-about__column">
					<div class="wpem-about__image wpem-about__image--icon">
						<span class="dashicons dashicons-admin-plugins" aria-hidden="true"></span>
					</div>
					<h4><?php _e('Extend the plugin', 'wpematico'); ?></h4>
					<p><?php _e('Add-ons take WPeMatico further: Professional adds keyword filters, custom titles, tags and many more options to every campaign; Full Content fetches the complete article from the source page instead of the RSS snippet; and there are add-ons for translation, AI rewriting, images and more.', 'wpematico'); ?></p>
				</div>
				<div class="wpem-about__column">
					<div class="wpem-about__image wpem-about__image--icon">
						<span class="dashicons dashicons-store" aria-hidden="true"></span>
					</div>
					<h4><?php _e('Find them in Extensions', 'wpematico'); ?></h4>
					<p><?php echo '<a href="' . esc_url($this->screen_url('extensions')) . '">' . __('WPeMatico → Extensions', 'wpematico') . '</a> ' . __('lists every add-on and membership, installs the free ones in one click and keeps the ones you own up to date. The', 'wpematico') . ' <a href="https://etruel.com/downloads/category/wpematico-add-ons/" target="_blank">' . __('etruel store', 'wpematico') . '</a> ' . __('has the full catalogue, with category filters to find exactly what you are looking for.', 'wpematico'); ?></p>
				</div>
			</div>

			<?php $this->subscription_form(); ?>

			<hr class="wpem-about__hr--large" />

			<div class="wpem-about__return">
				<a href="<?php echo esc_url($this->screen_url('dashboard')); ?>"><?php _e('Go to WPeMatico Dashboard', 'wpematico'); ?></a> |
				<a href="<?php echo esc_url($this->screen_url('add')); ?>"><?php _e('Add your first campaign', 'wpematico'); ?></a>
			</div>

		</div>
		<?php
	}

	/**
	 * Render the newsletter subscription section. Nothing is printed once the
	 * current user has subscribed.
	 *
	 * @access public
	 * @since 1.7.0
	 * @return void
	 */
	public function subscription_form() {
		$current_user	 = wp_get_current_user();
		$suscripted_user = get_option('wpematico_subscription_email_' . md5($current_user->ID), false);
		if($suscripted_user === '' or $suscripted_user !== $current_user->data->user_email) $suscripted_user = false;
		if($suscripted_user !== false) {
			return;
		}
		?>
		<hr class="wpem-about__hr--invisible" />

		<div class="wpem-about__section wpem-about__section--2-columns wpem-about__section--wider-left wpem-about__section--accent">
			<div class="wpem-about__column wpem-about__column--center">
				<h3><?php _e('Stay ahead with exclusive updates', 'wpematico'); ?></h3>
				<p><?php _e('Join our newsletter and be the first to hear about new features, new add-ons and special offers.', 'wpematico'); ?></p>
				<p><?php _e('No spam — just valuable insights straight to your inbox, about 4-5 times a year.', 'wpematico'); ?></p>
			</div>
			<div class="wpem-about__column wpem-about__column--center wpem-about__newsletter">
				<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="wpsubscription_form" method="post">
					<input type="hidden" name="action" value="save_subscription_wpematico"/>
					<?php wp_nonce_field('save_subscription_wpematico'); ?>
					<div class="wpem-about__newsletter-row">
						<div class="wpem-about__newsletter-field">
							<label for="wpematico_subscription_fname"><?php echo esc_html__("Name", "wpematico").' '. esc_html__("(optional)", "wpematico"); ?></label>
							<input type="text" id="wpematico_subscription_fname" name="wpematico_subscription[fname]" value="<?php echo esc_attr($current_user->user_firstname); ?>" size="40" placeholder="<?php esc_attr_e("First Name", "wpematico"); ?>">
						</div>
						<div class="wpem-about__newsletter-field">
							<label for="wpematico_subscription_lname" class="screen-reader-text"><?php esc_html_e("Last Name", "wpematico"); ?></label>
							<input type="text" id="wpematico_subscription_lname" name="wpematico_subscription[lname]" value="<?php echo esc_attr($current_user->user_lastname); ?>" size="40" placeholder="<?php esc_attr_e("Last Name", "wpematico"); ?>">
						</div>
					</div>

					<div class="wpem-about__newsletter-field">
						<label for="wpematico_subscription_email"><?php esc_html_e("Email", "wpematico"); ?> <span>(*)</span></label>
						<input type="text" id="wpematico_subscription_email" name="wpematico_subscription[email]" value="<?php echo esc_attr($current_user->user_email); ?>" size="40" placeholder="<?php esc_attr_e("Email", "wpematico"); ?>">
					</div>

					<div class="wpem-about__newsletter-submit">
						<input type="submit" class="button button-primary" value="<?php esc_attr_e('Subscribe', 'wpematico'); ?>">
					</div>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Static function save_subscription
	 * @access public
	 * @return void
	 * @since 1.7.0
	 */
	public function save_subscription() {
		if(!wp_verify_nonce($_POST['_wpnonce'], 'save_subscription_wpematico')) {
			wp_die(__('Security check', 'wpematico'));
		}
		$fname	 = sanitize_text_field($_POST['wpematico_subscription']['fname']);
		$lname	 = sanitize_text_field($_POST['wpematico_subscription']['lname']);
		$email	 = sanitize_email($_POST['wpematico_subscription']['email']);
		$redir	 = wp_sanitize_redirect($_POST['_wp_http_referer']);

		if(empty($fname) || empty($lname) || empty($email) || !is_email($email)) {
			wp_redirect($redir);
			exit;
		}
		$current_user	 = wp_get_current_user();
		$response		 = wp_remote_post($this->api_url_subscription, array(
			'method'		 => 'POST',
			'timeout'		 => 45,
			'redirection'	 => 2,
			'httpversion'	 => '1.0',
			'blocking'		 => true,
			'headers'		 => array(),
			'body'			 => array('FNAME' => $fname, 'LNAME' => $lname, 'EMAIL' => $email),
			'cookies'		 => array()
			)
		);
		if(!is_wp_error($response)) {
			update_option('wpematico_subscription_email_' . md5($current_user->ID), $email);
			WPeMatico::add_wp_notice(array('text' => __('Subscription saved', 'wpematico'), 'below-h2' => true));
		}

		wp_redirect($redir);
		exit;
	}

	/**
	 * Parse the WPEMATICO readme.txt file
	 *
	 * @since 2.0.3
	 * @return string $readme HTML formatted readme file
	 */
	public function parse_readme() {
		$file = file_exists(WPEMATICO_PLUGIN_DIR . 'readme.txt') ? WPEMATICO_PLUGIN_URL . 'readme.txt' : null;

		if(!$file) {
			$readme = '<p>' . __('No valid changelog was found.', 'wpematico') . '</p>';
		}else {
			$readme	 = WPeMatico_functions::wpematico_get_contents($file);
			$readme	 = explode('== Changelog ==', $readme);
			$readme	 = end($readme);
			$readme	 = html_entity_decode($this->wpematico_markdown($readme));
		}

		return ($readme);
	}

	/**
	 * Parse the text with *limited* markdown support.
	 *
	 * @param string $text
	 * @return string
	 */
	private function wpematico_markdown($text) {
// Make it HTML safe for starters
		$text	 = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
// headlines
		$s		 = array('===', '==', '=');
		$r		 = array('h2', 'h3', 'h4');
		for($x = 0; $x < sizeof($s); $x++)
			$text	 = preg_replace('/(.*?)' . $s[$x] . '(?!\")(.*?)' . $s[$x] . '(.*?)/', '$1<' . $r[$x] . '>$2</' . $r[$x] . '>$3', $text);

// inline
		$s		 = array('\*\*', '\'');
		$r		 = array('strong', 'code');
		for($x = 0; $x < sizeof($s); $x++)
			$text	 = preg_replace('/(.*?)' . $s[$x] . '(?!\s)(.*?)(?!\s)' . $s[$x] . '(.*?)/', '$1<' . $r[$x] . '>$2</' . $r[$x] . '>$3', $text);

// ' _italic_ '
		$text = preg_replace('/(\s)_(\S.*?\S)_(\s|$)/', ' <em>$2</em> ', $text);

// Blockquotes (they have email-styled > at the start)
		$regex = '^&gt;.*?$(^(?:&gt;).*?\n|\n)*';
		preg_match_all("~$regex~m", $text, $matches, PREG_SET_ORDER);
		foreach($matches as $set) {
			$block	 = "<blockquote>\n" . trim(preg_replace('~(^|\n)[&gt; ]+~', "\n", $set[0])) . "\n</blockquote>\n";
			$text	 = str_replace($set[0], $block, $text);
		}
// Titles
		$text = preg_replace_callback("~(^|\n)(#{1,6}) ([^\n#]+)[^\n]*~", function($match) {
			$n = strlen($match[2]);
			return "\n<h$n>" . $match[3] . "</h$n>";
		}, $text);
// ul lists	
		$s		 = array('\*', '\+', '\-');
		for($x = 0; $x < sizeof($s); $x++)
			$text	 = preg_replace('/^[' . $s[$x] . '](\s)(.*?)(\n|$)/m', '<li>$2</li>', $text);
		$text	 = preg_replace('/\n<li>(.*?)/', '<ul><li>$1', $text);
		$text	 = preg_replace('/(<\/li>)(?!<li>)/', '$1</ul>', $text);

		// ol lists
		$text	 = preg_replace('/(\d{1,2}\.)\s(.*?)(\n|$)/', '<li>$2</li>', $text);
		$text	 = preg_replace('/\n<li>(.*?)/', '<ol><li>$1', $text);
		$text	 = preg_replace('/(<\/li>)(?!(\<li\>|\<\/ul\>))/', '$1</ol>', $text);

		/* 		// ol screenshots style
		  $text = preg_replace('/(?=Screenshots)(.*?)<ol>/', '$1<ol class="readme-parser-screenshots">', $text);

		  // line breaks
		  $text	 = preg_replace('/(.*?)(\n)/', "$1<br/>\n", $text);
		  $text	 = preg_replace('/(1|2|3|4)(><br\/>)/', '$1>', $text);
		  $text	 = str_replace('</ul><br/>', '</ul>', $text);
		  $text	 = str_replace('<br/><br/>', '<br/>', $text);

		  // urls
		  $text	 = str_replace('http://www.', 'www.', $text);
		  $text	 = str_replace('www.', 'http://www.', $text);
		  $text	 = preg_replace('#(^|[^\"=]{1})(http://|ftp://|mailto:|https://)([^\s<>]+)([\s\n<>]|$)#', '$1<a target=\"_blank\" href="$2$3">$3</a>$4', $text);
		 */
		// Links and Images
		$regex = '(!)*\[([^\]]+)\]\(([^\)]+?)(?: &quot;([\w\s]+)&quot;)*\)';
		preg_match_all("~$regex~", $text, $matches, PREG_SET_ORDER);
		foreach($matches as $set) {
			$title = isset($set[4]) ? " title=\"{$set[4]}\"" : '';
			if($set[1]) {
				$text = str_replace($set[0], "<img src=\"{$set[3]}\"$title alt=\"{$set[2]}\"/>", $text);
			}else {
				$text = str_replace($set[0], "<a target=\"_blank\" href=\"{$set[3]}\"$title>{$set[2]}</a>", $text);
			}
		}

		// Paragraphs
		//		$text	 = preg_replace('~\n([^><\t]+)\n~', "\n\n<p>$1</p>\n\n", $text);
		// Paragraphs (what about fixing the above?)
		//		$text	 = str_replace(array("<p>\n", "\n</p>"), array('<p>', '</p>'), $text);
		// Lines that end in two spaces require a BR
		//		$text	 = str_replace("  \n", "<br>\n", $text);
		// Reduce crazy newlines
		//		$text	= preg_replace("~\n\n\n+~", "\n\n", $text);

		return $text;
	}

	/**
	 * Column Privacy with the privacy also in readme.txt file
	 *
	 * @since 2.0.3
	 * @return string $readme HTML formatted readme file
	 */
	public function parse_privacy() {
		$file = file_exists(WPEMATICO_PLUGIN_DIR . 'readme.txt') ? WPEMATICO_PLUGIN_URL . 'readme.txt' : null;

		if(!$file) {
			return '<p>' . __('No privacy terms were found.', 'wpematico') . '</p>';
		}

		$readme	 = WPeMatico_functions::wpematico_get_contents($file);
		// Splitting on a heading readme.txt does not carry returns the whole file.
		$readme	 = explode('## Privacy & Transparency ##', $readme);
		if(count($readme) < 2) {
			return '<p>' . __('No privacy terms were found.', 'wpematico') . '</p>';
		}
		$readme	 = explode('== Installation ==', end($readme));

		return wpautop(html_entity_decode($this->wpematico_markdown($readme[0])));
	}

	/**
	 * Sends user to the Welcome page on first activation of WPEMATICO as well as each
	 * time WPEMATICO is upgraded to a new MAJOR version
	 *
	 * @access public
	 * @since 1.3.8
	 * @return void
	 */
	public function welcome() {
		// Bail if no activation redirect. The transient value is the screen to
		// show ('install' or 'update'), set by wpematico_install(); older values
		// were a plain boolean, which falls through to the update screen.
		$screen = get_transient('_wpematico_activation_redirect');
		if(!$screen)
			return;

		// The version could not be saved on a previous pass (see welcome_message()),
		// so the update check keeps matching. Don't drag the user to this screen
		// again on every request until the flag expires.
		if(get_transient('_wpematico_version_save_failed')) {
			return;
		}
		// redirect if ! AJAX
		if((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || (defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit']))
			return;

		// Delete the redirect transient
		delete_transient('_wpematico_activation_redirect');

		// Delete the etruel_wpematico_addons_data transient to create again when access the addon page
		delete_transient('etruel_wpematico_addons_data');

		// Bail if activating from network, or bulk
		if(is_network_admin() || isset($_GET['activate-multi']))
			return;



		// wpematico_install() already recorded the version; this screen only decides
		// where to send the user. Re-asserting it here is a safety net for a stale
		// object cache (hence the cache delete), not the primary write.
		wp_cache_delete('wpematico_db_version', 'options');
		update_option('wpematico_db_version', WPEMATICO_VERSION, false);

		// It constant could be used to prevent redirects.
		if(defined('WPEMATICO_PREVENT_REDIRECT')) {
			return;
		}


		if($screen === 'install') { // First time install
			wp_safe_redirect(admin_url('index.php?page=wpematico-getting-started'));
			exit;
		}else { // Update
			wp_safe_redirect(admin_url('index.php?page=wpematico-about'));
			exit;
		}
	}

	/**
	 * Static function prevent_double_act_redirect
	  It'll be used on future.
	  public function prevent_double_act_redirect() {
	  if (isset($_GET['page'])) {
	  if ($_GET['page'] == 'wpematico-getting-started' || $_GET['page'] == 'wpematico-about') {
	  define('WPE_PREVENT_REDIRECT_ACTIVE', true);

	  delete_transient( '_wpematico_activation_redirect' );
	  }
	  }
	  }
	 */
}

new WPEMATICO_Welcome();