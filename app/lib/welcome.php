<?php
/**
 * Welcome Page Class
 * @package     WPEMATICO
 * @subpackage  Admin/Welcome
 * @copyright   Copyright (c) 2015, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
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
	public $api_url_subscription = 'http://www.wpematico.com/wp-admin/admin-post.php?action=wpmapirest_importdata';

	/**
	 * Get things started
	 *
	 * @since 1.4
	 */
	public function __construct() {
		add_action('admin_menu', array($this, 'admin_menus'));
		add_action('admin_head', array($this, 'admin_head'));
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


		// Now remove them from the menus so plugins that allow customizing the admin menu don't show them
//		remove_submenu_page( 'index.php', 'wpematico-about' );
		remove_submenu_page('index.php', 'wpematico-changelog');
		remove_submenu_page('index.php', 'wpematico-getting-started');
		remove_submenu_page('index.php', 'wpematico-privacy');
	}

	/**
	 * Hide Individual Dashboard Pages
	 *
	 * @access public
	 * @since 1.4
	 * @return void
	 */
	public function admin_head() {
		global $current_screen;
		//$current_screen = get_current_screen();
		if (!is_null($current_screen) && ($current_screen->id == "dashboard_page_wpematico-about" || $current_screen->id == "dashboard_page_wpematico-getting-started" || $current_screen->id == "dashboard_page_wpematico-changelog" || $current_screen->id == "dashboard_page_wpematico-privacy")) {
			?>
			<style type="text/css" media="screen">
				/*<![CDATA[*/
				[class*="dashboard_page_"] #wpcontent { /*background: #fff;*/ padding: 0 24px; }
				.wpe-flex{ display: flex; }
				.about__header-navigation { background: #fff; color: #333;}
				.about__section { background: #fff; }
				.about__header { background-color: #222; }
				.about__header-title p { color: #fff; }
				.about__header-title p span { color: #eee; }
				.about__header-text { background: #ef8e2f; }
				.about__header-text p { color: #fff; }
				.about__header-navigation { border-color: #222 }
				.about__header-navigation .nav-tab-active:active, .about__header-navigation .nav-tab-active:hover,
				.about__header-navigation .nav-tab-active { color: #ef8e2f; border-color: #ef8e2f;}
				.about__header-navigation .nav-tab:active, .about__header-navigation .nav-tab:hover{ background: #f5f5f5; color: #ef8e2f; }
				.about__container .has-accent-background-color { background: #ef8e2f; }
				.about__container .has-subtle-background-color { background: #f9f9f9; }
				.about__header-title .wpematico-badge { align-self: flex-end; margin-bottom: 20px; max-height: 80px; width: auto; }
				.about__section.about__section_height { min-height: 560px; }
				.about__section.about__section_height-2 { min-height: 400px; }
				.about__section.is-feature { font-size: 1.4em; }
				.about__container .about__image { padding: 0 32px; }
				.about__section .span-text { font-size: .9em; }
				.feature-section a, .about__section p a { font-weight: 600; }
				.addon_block { display: flex; margin-bottom: 1em;}
				.addon_block .addon_img img { display:block; max-width: 120px; height: auto; margin-right: 10px; }
				.addon_block .addon_text { text-align: right;}
				.addon_block .addon_text p { margin: 0; font-size: 13px; }
				@media all and ( max-width: 782px ) {
					.about__header-title .wpematico-badge{ display: none; }
				}
				@media all and ( max-width: 782px ) and (min-width: 481px) {
					.about__header-navigation .nav-tab{ padding: 24px 16px; }
				}
				@media all and ( max-width: 600px ) and (min-width: 481px) {
					.about__header-navigation .nav-tab{ font-size: 1.1em; }
				}
				/*]]>*/
			</style>
			<?php
		}
	}

	/**
	 * Welcome message
	 *
	 * @access public
	 * @since 2.5
	 * @return void
	 */
	public function welcome_message() {

		$stored_wpematico_version = get_option('wpematico_db_version');
		if(version_compare(WPEMATICO_VERSION, $stored_wpematico_version, '!=')) {
			set_transient('_wpematico_user_has_seen_welcome_page', true, DAY_IN_SECONDS);
			echo '<div style="display: block !important;" class="notice notice-error below-h2">' . __('WPeMatico could not update the version in your database. Please if your website has an object cache disable it.', 'wpematico') . '</div>';
		}

		list( $display_version ) = explode('-', WPEMATICO_VERSION);
		?>
		<div class="about__header-text">
			<p>
				<?php
				_e('Thank you for updating to the latest version!', 'wpematico');
				printf(	'<br />'.__('WPeMatico %s is ready to make your autoblogging faster, safer, and better!', 'wpematico'),
					$display_version
				);
				?>
			</p>
		</div>

		<div class="about__header-title">
			<img class="wpematico-badge" src="<?php echo WPEMATICO_PLUGIN_URL . '/images/robotico_orange-75x130.png'; ?>" alt="<?php _e('WPeMatico', 'wpematico'); ?>" / >
			<p>
				<?php _e('WPeMatico'); ?>
				<span><?php echo $display_version; ?></span>
			</p>
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
		?>
		<nav class="about__header-navigation nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e('Secondary menu'); ?>">
			<a class="nav-tab <?php echo $selected == 'wpematico-about' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(admin_url(add_query_arg(array('page' => 'wpematico-about'), 'index.php'))); ?>">
				<?php _e("What's New", 'wpematico'); ?>
			</a>
			<a class="nav-tab <?php echo $selected == 'wpematico-getting-started' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(admin_url(add_query_arg(array('page' => 'wpematico-getting-started'), 'index.php'))); ?>">
				<?php _e('Getting Started', 'wpematico'); ?>
			</a>
			<a class="nav-tab <?php echo $selected == 'wpematico-changelog' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(admin_url(add_query_arg(array('page' => 'wpematico-changelog'), 'index.php'))); ?>">
				<?php _e('Changelog', 'wpematico'); ?>
			</a>
			<a class="nav-tab <?php echo $selected == 'wpematico-privacy' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(admin_url(add_query_arg(array('page' => 'wpematico-privacy'), 'index.php'))); ?>">
				<?php _e('Privacy', 'wpematico'); ?>
			</a>
		</nav>
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
		?>
		<div class="wrap about__container">

			<div class="about__header">

				<?php $this->welcome_message(); ?>

				<?php $this->tabs(); ?>

			</div>

			<?php $this->subscription_form(); ?>

			<hr />

			<div class="about__section about__section_height has-2-columns">
				<div class="column wpe-flex is-edge-to-edge has-accent-background-color">
					<div class="about__image is-vertically-aligned-center">
						<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/media_ext.png'; ?>" alt="Use Featured Image From URL" style="max-height: 500px;" />
					</div>
				</div>
				<div class="column is-vertically-aligned-center">
					<h2><?php _e('Choose Media Mime Types.', 'wpematico'); ?></h2>

					<h3><?php _e('Separate with commas the allowed mime types for WPeMatico Uploads.', 'wpematico'); ?></h3>

					<p><?php _e('You can choose which media files will be uploaded to your website, you can add or remove the extensions you want and WPeMatico will do the rest.', 'wpematico'); ?></p>

					<h2><?php _e('Use Featured Image From URL.', 'wpematico'); ?></h2>

					<h3><?php _e('Use the featured images without storing them on your own website.', 'wpematico'); ?></h3>

					<p><?php _e('Avoid storing images on your site, this new feature allows you to use the featured image from an external URL by activating the "Use Featured Image from URL" option from WPeMatico Settings or from each campaign.', 'wpematico'); ?></p>
					<p></p>
					<h3><?php _e('NOTE: Featured Image From URL plugin is required for this functionality.', 'wpematico'); ?></h3>
				</div>
			</div>

			<div class="about__section about__section_height has-2-columns">
				<div class="column is-vertically-aligned-center">
					<h2><?php _e('Force Item Date.', 'wpematico'); ?></h2>

					<p><?php _e('You can force the use of the original date of each feed item ignoring incoherent dates like past posts published after new ones.', 'wpematico'); ?></p>

					<h2><?php _e('Create Post Excerpt Automatically.', 'wpematico'); ?></h2>

					<p><?php _e('By default WordPress creates the excerpts "on the fly" from the post content, WPeMatico allows to create them using the description tag of the items in the feed.', 'wpematico'); ?></p>

					<h2><?php _e('Custom Posts Statuses.', 'wpematico'); ?></h2>

					<h3><?php _e('Post status allows you to organize your Posts.', 'wpematico'); ?></h3>

					<p><?php _e('Post status is an very useful editorial tool that allows you to organize your Posts based on their respective stages during the editorial workflow.', 'wpematico'); ?></p>

				</div>
				<div class="column wpe-flex is-edge-to-edge has-accent-background-color">
					<div class="about__image is-vertically-aligned-center">
						<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/force_date-post_excerpt.png'; ?>" alt="Debug Mode" />
					</div>
				</div>
			</div>

			<div class="about__section about__section_height has-2-columns">
				<div class="column wpe-flex is-edge-to-edge has-accent-background-color">
					<div class="about__image is-vertically-aligned-center">
						<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/xml_campaign.png'; ?>" alt="XML Campaign Type" />
					</div>
				</div>
				<div class="column is-vertically-aligned-center">
					<h2><?php _e('A new Campaign Type for XML feeds.', 'wpematico'); ?></h2>

					<h3><?php _e('XML Campaign type to parse and fetch XML feeds', 'wpematico'); ?></h3>

					<p><?php _e('This feature allows you to configure every campaign with the fields that are found in the XML tags.  A very important addition that will allow to import almost anything that come in XML format to WordPress Posts (types).', 'wpematico'); ?></p>

					<p><?php _e('An option in Settings enables the upload of XML files in the WordPress Media Library in order to use its URL in the campaigns.', 'wpematico'); ?></p>

					<p><?php _e('Also, using the addons as the Professional will allow overwriting the data of a campaign and add the author, categories and tags also from the XML tags.', 'wpematico'); ?></p>
				</div>
			</div>

			<div class="about__section about__section_height has-2-columns">
				<div class="column is-vertically-aligned-center">
					<h2><?php _e('A new preview for the YouTube campaign.', 'wpematico'); ?></h2>

					<h3><?php _e('This feature shows how the posts fetched from YouTube will look before that are created.', 'wpematico'); ?></h3>

					<p><?php _e('Now you have the possibility to choose which elements of the YouTube feeds will be included in the post, the image, the featured image or the description, the preview section in the metabox will show you in real time.', 'wpematico'); ?></p>

					<p><?php _e("You can mark the selection options to see the possible results of the Post Template.", 'wpematico'); ?></p>

				</div>
				<div class="column wpe-flex is-edge-to-edge has-accent-background-color">
					<div class="about__image is-vertically-aligned-center">
						<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/yt-campaign.png'; ?>" alt="Preview for the YouTube campaign" />
					</div>
				</div>
			</div>

			<div class="about__section about__section_height has-2-columns">
				<div class="column wpe-flex is-edge-to-edge has-accent-background-color">
					<div class="about__image is-vertically-aligned-center">
						<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/previewbutton.png'; ?>" alt="Campaign Preview" />
					</div>
				</div>
				<div class="column is-vertically-aligned-center">
					<h2><?php _e('A new Campaign Fetch Preview.', 'wpematico'); ?></h2>

					<h3><?php _e('Introducing the Campaign Preview Feature', 'wpematico'); ?></h3>

					<p><?php _e('The most visible change in version 1.9 is certainly the Campaign Preview.', 'wpematico'); ?><br />
						<?php _e('This new feature lets you view the list of the posts the campaign will fetch the next time it runs.', 'wpematico'); ?><br />
						<?php _e('You can see the title, image and an excerpt of its content, but you can click in the title to see all its content like it will bring by WPeMatico plugin.', 'wpematico'); ?></p>

					<h3><?php _e('Using the Campaign Preview.', 'wpematico'); ?></h3>

					<p><?php _e('When click in the “eye” icon, a popup will open to show you the next items to fetch.  This allow you to see if the campaign has pending items to publish from any feed inside it.', 'wpematico'); ?></p>
				</div>
			</div>

			<hr />

			<div class="about__section about__section_height has-2-columns has-subtle-background-color">
				<h2 class="is-section-header"><?php _e('More Tweaks and Improvements.'); ?></h2>

				<div class="column">
					<h3><?php _e('Better speed on uploading files.', 'wpematico'); ?></h3>
					<p><?php _e("We've improved the functions and the ways used for pull the images and attach to the published post.", 'wpematico'); ?></p>

					<h3><?php _e('Better control on running campaigns manually.', 'wpematico'); ?></h3>
					<p><?php _e('Until now if you run a campaign and give an error in the execution, the campaign would hangs up, but from now when fails, it will show an alert with the error message.', 'wpematico'); ?></p>

					<h3><?php _e('More icons and cosmetics things.', 'wpematico'); ?></h3>
					<p><?php _e("We're optimizing the screens to make them be more readable by humans, and also get more and better helps with examples and tips in the campaign editing or other screens.", 'wpematico'); ?></p>
					<p><?php _e("Find tips by clicking in the \"Help\" tab in the top-right corner inside Wordpress admin screens.", 'wpematico'); ?></p>
				</div>
				<div class="column">
					<h3><?php _e('WPeMatico Addons', 'wpematico'); ?></h3>

					<div class="addon_block">
						<div class="addon_img">
							<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/wpematico-premium-200x100.png'; ?>" alt="WPeMatico Premium Package" />
						</div>
						<div class="addon_text">
							<p><?php _e('The', 'wpematico'); ?> <a href="https://etruel.com/downloads/wpematico-exporter/" target="_blank">WPeMatico Premium Package</a> 
								<?php _e('contains the five preferred add-Ons with the most wanted features for autoblogging with WordPress in a very easy professional way.', 'wpematico'); ?></p>
						</div>
					</div>

					<div class="addon_block">
						<div class="addon_img">
							<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/wpematico_manual_fetching-200x100.png'; ?>" alt="WPeMatico Manual Fetching" />
						</div>
						<div class="addon_text">
							<p><?php _e('The', 'wpematico'); ?> <a href="https://etruel.com/downloads/wpematico-manual-fetching/" target="_blank">WPeMatico Manual Fetching</a> 
								<?php _e('extends the Campaign Preview functionality to every feed individually and allows you to review and insert each item, one by one or in bulk mode.', 'wpematico'); ?></p>
						</div>
					</div>

					<div class="addon_block">
						<div class="addon_img">
							<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/wpematico_ebay-200x100.png'; ?>" alt="WPeMatico eBay Campaign Type" />
						</div>
						<div class="addon_text">
							<p><?php _e('The', 'wpematico'); ?> <a href="https://etruel.com/downloads/ebay-campaign-type/" target="_blank">WPeMatico eBay Campaign Type</a>
								<?php _e('uses eBay products in your site and publish them as posts or WooCommerce products by relating your eBay Parter Network Campaigns IDs.', 'wpematico'); ?></p>
						</div>
					</div>

					<div class="addon_block">
						<div class="addon_img">
							<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/wpematico-hooks_200x100.png'; ?>" alt="WPeMatico Custom Hooks" />
						</div>
						<div class="addon_text">
							<p><?php _e('The', 'wpematico'); ?> <a href="https://wordpress.org/plugins/wpematico-custom-hooks/" target="_blank">WPeMatico Custom Hooks</a> 
								<?php _e('is a FREE addon that allows you to execute actions and filters provided by WPeMatico in order to create custom behavior in the execution of your campaigns.', 'wpematico'); ?></p>
						</div>
					</div>

					<h5><a style="float:right;" href="https://etruel.com/starter-packages/" target="_blank"><?php _e('Starter Packages.', 'wpematico'); ?></a>
						<a style="float:left;" href="https://etruel.com/downloads/category/wpematico-add-ons/" target="_blank"><?php _e('All available Addons', 'wpematico'); ?></a></h5>
				</div>
			</div>

			<hr />

			<div class="about__section has-3-columns">
				<h2 class="is-section-header"><?php _e('Even More Developer Happiness', 'wpematico'); ?></h2>
				<div class="column">
					<h3><?php _e('JavaScript hooks', 'wpematico'); ?></h3>
					<p><?php _e("We've implemented the JavaScript hooks like WordPress actions and filters! You can make functions to enqueue the scripts and hooks to already added filters in the code.", 'wpematico'); ?></p>
				</div>
				<div class="column">
					<h3><a href="https://etruel.com/my-account/support/" target="_blank"><?php _e('Support ticket system for free', 'wpematico'); ?></a></h3>
					<p><?php _e('Ask for any problem you may have and you\'ll get support for free. If it is necessay we will see into your website to solve your issue.', 'wpematico'); ?></p>
				</div>
				<div class="column">
					<h3><a href="https://etruel.com/downloads/premium-support/" target="_blank"><?php _e('Premium Support', 'wpematico'); ?></a></h3>
					<p><?php _e('Get access to in-depth setup assistance. We\'ll dig in and do our absolute best to resolve issues for you. Any support that requires code or setup your site will need this service.', 'wpematico'); ?></p>
				</div>
			</div>
			<div class="about__section has-3-columns">
				<div class="column">
					<h3><?php _e('Nags updates individually for extensions', 'wpematico'); ?><span class="plugin-count" style="display: inline-block;background-color: #d54e21;color: #fff;font-size: 9px;line-height: 17px;font-weight: 600;margin: 1px 0 0 2px;vertical-align: top;-webkit-border-radius: 10px;border-radius: 10px;z-index: 26;padding: 0 6px;">1</span></h3>
					<p><?php _e('A more clear nag update was added for the addons in the WPeMatico Extensions and Addons menu items.', 'wpematico'); ?></p>
				</div>
				<div class="column">
					<h3><?php _e('Hidden Options in Settings -> Writing', 'wpematico'); ?></h3>
					<p><?php _e("If you have any problem with WPeMatico item menu, Settings page or lost some plugin, we've put there a WPeMatico Section, to try to avoid weird behaviors made by some thirds plugins.", 'wpematico'); ?></p>
				</div>
				<div class="column">
					<h3><a href="https://wordpress.org/support/view/plugin-reviews/wpematico?filter=5&rate=5#new-post" target="_blank"><?php _e('Rate 5 stars on Wordpress', 'wpematico'); ?></a><div class="wporg-ratings" title="5 out of 5 stars" style="color:#ffb900;float: right;"><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span></div></h3>
					<p><?php _e('We need your positive rating of 5 stars in WordPress. Your comment will be published on the bottom of the website and besides it will help making the plugin better.', 'wpematico'); ?></p>
				</div>
			</div>

			<hr />

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

		<div class="wrap about__container">

			<div class="about__header">

				<?php $this->welcome_message(); ?>

				<?php $this->tabs(); ?>

			</div>

			<div class="about__section">

				<h2 class="is-section-header"><?php _e('Full Changelog', 'wpematico'); ?></h2>

				<div class="column is-vertically-aligned-center" style="padding-top: 0;">

					<div class="feature-section">
						<?php echo $this->parse_readme(); ?>
					</div>

				</div>
			</div>

			<hr />

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url(admin_url(add_query_arg(array('post_type' => 'wpematico', 'page' => 'wpematico-settings'), 'edit.php'))); ?>"><?php _e('Go to WPeMatico Settings', 'wpematico'); ?></a>
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
	public function privacy_screen() {
		?>

		<div class="wrap about__container">

			<div class="about__header">

				<?php $this->welcome_message(); ?>

				<?php $this->tabs(); ?>

			</div>

			<div class="about__section">

				<h2 class="is-section-header"><?php _e('Privacy terms', 'wpematico'); ?></h2>

				<div class="column is-vertically-aligned-center">

					<div class="feature-section">
						<?php echo $this->parse_privacy(); ?>
					</div>

				</div>
			</div>

			<hr />

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url(admin_url(add_query_arg(array('post_type' => 'wpematico', 'page' => 'wpematico-settings'), 'edit.php'))); ?>"><?php _e('Go to WPeMatico Settings', 'wpematico'); ?></a>
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
		<div class="wrap about__container">

			<div class="about__header">

				<?php $this->welcome_message(); ?>

				<?php $this->tabs(); ?>

			</div>

			<div class="about__section is-feature">
				<p class="about-description"><?php _e('Autoblogging in the blink of an eye! On complete autopilot WpeMatico gets new content regularly for your site!  WPeMatico is a very easy to use autoblogging plugin. Organized into campaigns, it publishes your posts automatically from the RSS/Atom feeds of your choice.', 'wpematico'); ?></p><br />
				<p class="about-description"><?php _e('Use the tips below to get started using WPeMatico. You will be up and running in no time!', 'wpematico'); ?></p>
			</div>

			<hr />

			<div class="about__section about__section_height has-2-columns">
				<div class="column wpe-flex is-edge-to-edge has-accent-background-color">
					<div class="about__image is-vertically-aligned-center">
						<div class="feature-section-media" style="max-height: 400px; overflow: hidden;">
							<img src="<?php echo WPEMATICO_PLUGIN_URL . 'screenshot-5.jpg'; ?>" class="Fill in the Settings"/>
						</div>
					</div>
				</div>
				<div class="column is-vertically-aligned-center">
					<h2><?php _e('Fill in the Settings', 'wpematico'); ?></h2>

					<h3><?php _e('RSS', 'wpematico'); ?></h3>
					<p><?php _e('RSS is a technology to facilitate the distribution of information in a centralized way. Usually daily visit several websites to see if there is anything new in our favorite places. The fundamental principle behind RSS is that "the receiver is no longer in search of information, is the information that goes in search of the receiver." If you use an RSS aggregators not have to visit each of these sites because they receive all the news in one place. The aggregator checks your favorite websites in search of new content and features directly without any effort on your part.', 'wpematico'); ?></p>
					<p><?php _e('Blogs contain in its main page a XML file. In the case of blogs on WordPress the feed is defined as follows:', 'wpematico'); ?></p><code>http://domain.com/feed</code>
					<p><?php _e('We have to add this URL to the RSS field to receive the items.', 'wpematico'); ?></p>
				</div>
			</div>

			<div class="about__section about__section_height has-2-columns">
				<div class="column is-vertically-aligned-center">
					<h3><a href="<?php echo admin_url('edit.php?post_type=wpematico&page=wpematico_settings') ?>"><?php echo 'WPeMatico &rarr; ' . __('Settings', 'wpematico'); ?></a></h3>
					<p><?php _e('The WPeMatico Settings menu is where you\'ll set all global aspects for the operation of the plugin and the global options for campaigns, advanced options and tools.', 'wpematico'); ?></p>
					<p><?php _e('There are also here the tests and the configuration options for the SimplePie library to get differnet behaviour when fetch the feed items.', 'wpematico'); ?></p>
					<p><?php _e('Set to an external or internal Wordpress CRON scheduler and look at for the configuration tabs of all plugin extensions and Add-ons.', 'wpematico'); ?></p>
				</div>
				<div class="column wpe-flex is-edge-to-edge has-accent-background-color">
					<div class="about__image is-vertically-aligned-center">
						<div class="feature-section-media" style="max-height: 400px; overflow: hidden;">
							<img src="<?php echo WPEMATICO_PLUGIN_URL . 'screenshot-7.jpg'; ?>" class="Fill in the Settings"/>
							<p style="text-align:center; color: #fff; margin:1em 0 0;"><?php _e('Testing SimplePie library', 'wpematico'); ?></p>
						</div>
					</div>
				</div>
			</div>

			<div class="about__section about__section_height has-2-columns">
				<div class="column wpe-flex is-edge-to-edge has-accent-background-color">
					<div class="about__image is-vertically-aligned-center">
						<div class="feature-section-media" style="max-height: 400px; overflow: hidden;">
							<img src="<?php echo WPEMATICO_PLUGIN_URL . 'screenshot-6.jpg'; ?>" class="Creating Your First Campaign"/>
						</div>
					</div>
				</div>
				<div class="column is-vertically-aligned-center">
					<h2><?php _e('Creating Your First Campaign', 'wpematico'); ?></h2>

					<h3><a href="<?php echo admin_url('post-new.php?post_type=wpematico') ?>"><?php printf(__('%s &rarr; Add New', 'wpematico'), 'WPeMatico'); ?></a></h3>

					<p><?php printf(__('The WPeMatico All Campaigns menu is your access point for all aspects of your Feed campaigns creation and setup to fetch the items and insert them as posts or any Custom Post Type. To create your first campaign, simply click Add New and then fill out the campaign details.', 'wpematico')); ?></p>
				</div>
			</div>

			<div class="about__section about__section_height has-2-columns">
				<div class="column is-vertically-aligned-center">
					<h2><?php _e('Need Help?', 'wpematico'); ?></h2>

					<h3><?php _e('Inline Documentation', 'wpematico'); ?></h3>

					<p><?php _e('Are those small sentences and/or phrases that you see alongside, or underneath, a feature in WPeMatico that give a short but very helpful explanation of what the feature is and serve as guiding tips that correspond with each feature. These tips sometimes even provide basic, recommended settings.', 'wpematico'); ?></p>

					<h3><?php _e('Help Tab', 'wpematico'); ?></h3>

					<p><?php _e('In addition to the inline documentation that you see scattered throughout the Dashboard, you’ll find a helpful tab in the upper-right corner of your Dashboard labeled Help. Click this tab and a panel drops down that contains a lot of text providing documentation relevant to the page you are currently viewing on your Dashboard.', 'wpematico'); ?></p><br />

					<span class="span-text"><?php _e('For example, if you’re viewing the WPeMatico Settings page, the Help tab drops down documentation relevant to the WPeMatico Settings page. Likewise, if you’re viewing the Add New Campaign page, clicking the Help tab drops down documentation with topics relevant to the settings and features you find on the Add New Campaign page within your Dashboard.', 'wpematico'); ?></span>

					<span class="span-text"><?php _e('Just click the Help tab again to close the Help panel.', 'wpematico'); ?></span>
				</div>
				<div class="column wpe-flex is-edge-to-edge has-accent-background-color">
					<div class="about__image is-vertically-aligned-center">
						<div class="feature-section-media" style="max-height: 400px; overflow: hidden;">
							<img src="<?php echo WPEMATICO_PLUGIN_URL . 'screenshot-4.jpg'; ?>" alt="Need Help?"/>
						</div>
					</div>
				</div>
			</div>

			<hr />

			<div class="about__section has-2-columns has-subtle-background-color">
				<h2 class="is-section-header"><?php _e('Need more Help?', 'wpematico'); ?></h2>
				<div class="column">
					<h3><?php _e('Phenomenal Support', 'wpematico'); ?></h3>
					<p><?php echo __('We do our best to provide the best support we can. If you encounter a problem or have a question, simply open a ticket using our ', 'wpematico') . '<a target="_blank" href="https://etruel.com/my-account/support">' . __('support form', 'wpematico') . '</a>.'; ?></p>
				</div>
				<div class="column">
					<h3><?php _e('Need Even Better Support?', 'wpematico'); ?></h3>
					<p><?php echo __('Our ', 'wpematico') . '<a target="_blank" href="https://etruel.com/downloads/premium-support/">' . __('Premium Support', 'wpematico') . '</a>' . __('system is there for customers that need faster and/or more in-depth assistance.', 'wpematico'); ?></p>
				</div>
			</div>
			<div class="about__section has-2-columns has-subtle-background-color">
				<h2 class="is-section-header"><?php _e('Stay Up to Date', 'wpematico'); ?></h2>
				<div class="column">
					<h3><?php _e('Get Notified of Extension Releases', 'wpematico'); ?></h3>
					<p><?php echo __('New extensions that make WPeMatico even more powerful are released nearly every single week. Subscribe to the newsletter to stay up to date with our latest releases. ', 'wpematico') . '<a href="http://eepurl.com/bX2ANz" target="_blank">' . __('Sign up now', 'wpematico') . '</a>' . __(' to ensure you do not miss a release!', 'wpematico'); ?></p>
				</div>
				<div class="column">
					<h3><?php _e('Get Alerted About New Tutorials', 'wpematico'); ?></h3>
					<p><?php echo '<a href="http://eepurl.com/bX2ANz" target="_blank">' . __('Sign up now', 'wpematico') . '</a>' . __(' to hear about the latest tutorial releases that explain how to take WPeMatico further.', 'wpematico'); ?></p>
				</div>
			</div>
			<div class="about__section has-2-columns has-subtle-background-color">
				<h2 class="is-section-header"><?php _e('WPeMatico Add-ons', 'wpematico'); ?></h2>
				<div class="column">
					<h3><?php _e('Extend the plugin features', 'wpematico'); ?></h3>
					<p><?php _e('Add-on plugins are available that greatly extend the default functionality of WPeMatico. There are a Professional extension for extend the parsers of the feed contents, The Full Content add-on to scratch the source webpage looking to get the entire article, and many more.', 'wpematico'); ?></p>
				</div>
				<div class="column">
					<h3><?php _e('Visit the Extension Store', 'wpematico'); ?></h3>
					<p><?php echo '<a href="https://etruel.com/downloads" target="_blank">' . __('The etruel store', 'wpematico') . '</a>' . __('has a list of all available extensions for WPeMatico, also other Worpdress plugins, some of them for free. Including convenient category filters so you can find exactly what you are looking for.', 'wpematico'); ?></p>
				</div>
			</div>

			<hr />

		</div>
		<?php
	}

	/**
	 * Render Subscription Screen
	 *
	 * @access public
	 * @since 1.7.0
	 * @return void
	 */
	public function subscription_form() {
		?>
		<?php
		$current_user	 = wp_get_current_user();
		?>
		<style type="text/css">
			.subscription {
			}
			.form-group label{
				display: block;
				margin-bottom: .5em;
				font-size: 1rem;
				font-weight: 600;
				color: #fff;
			}
			.two-form-group{
				display: flex;
				flex-wrap: wrap;
				align-items: flex-end;
				margin-left: -7.5px;
				margin-right: -7.5px;
			}
			.two-form-group .form-group{
				-ms-flex: 0 0 50%;
				flex: 0 0 50%;
				max-width: 50%;
				padding-left: 7.5px;
				padding-right: 7.5px;
				box-sizing: border-box;
				margin-bottom: 1em;
			}
			.subscription #wpsubscription_form .form-control{
				padding: 7.5px 15px;
				box-shadow: none!important;
				display: block;
				width: 100%;
				border-radius: 0;
				border: 2px solid #d3741c;
			}
			.wpbutton-submit-subscription{
				margin-top: 32px;
				text-align: right;
			}
			.wpbutton-submit-subscription .button-primary{
				padding: 10px 20px;
				border-radius: 0;
				font-size: 15px;
				background: #222;
				border-color: #222;
			}
			.wpbutton-submit-subscription .button-primary:hover{
				background: #111;
				border-color: #111;
			}
		</style>
		<?php
		$suscripted_user = get_option('wpematico_subscription_email_' . md5($current_user->ID), false);
		$classes		 = "";
		if($suscripted_user === false) {
			$classes = "about__section_height-2 has-2-columns";
		}
		?>
		<div class="about__section <?php echo $classes; ?> subscription">
			<div class="column is-vertically-aligned-center">
				<h2><?php _e('Last News in This Version!', 'wpematico'); ?></h2>
				<p><?php _e('WPeMatico continues to improve and innovate with each update, once again we include new features in order to improve the user experience and cover all their needs.', 'wpematico'); ?></p>
				<p><?php _e('Choose which media files will be uploaded to your website by allowing or not their extension!','wpematico')?></p>
				<p><?php _e('Can you imagine using the featured images without storing them on your own website? With this new version it\'s now possible! What are you waiting for to test it?', 'wpematico'); ?></p>
				<h3><?php _e('NOTE: Featured Image From URL plugin is required for this functionality.', 'wpematico'); ?></h3>
				<p><?php _e('In addition to this, in this new version you can also create excerpts using the description tag of the items in the feed and you can even force the use of the original date of each post!', 'wpematico'); ?></p>
			</div>
			<?php if($suscripted_user === false) { ?>
				<div class="column wpe-flex is-edge-to-edge has-accent-background-color">
					<div class="about__image is-vertically-aligned-center">
						<p></p>
						<h2><strong><?php _e('Stay Informed!', 'wpematico'); ?></strong></h2>
						<h3 class="wpsubscription_info"><?php _e('Subscribe to our Newsletter and be the first to receive our news.', 'wpematico'); ?> 
							<?php _e('We send around 4 or 5 emails per year. Really.', 'wpematico'); ?></h3>
						<form action="<?php echo admin_url('admin-post.php'); ?>" id="wpsubscription_form" method="post" class="wpcf7-form">
							<input type="hidden" name="action" value="save_subscription_wpematico"/>
							<?php wp_nonce_field('save_subscription_wpematico'); ?>
							<div class="two-form-group">
								<div class="form-group">
									<label><?php _e("Name", "wpematico"); ?></label>
									<input type="text" id="" name="wpematico_subscription[fname]" value="<?php echo $current_user->user_firstname; ?>" size="40" class="form-control" placeholder="<?php _e("First Name", "wpematico"); ?>">
								</div>
								<div class="form-group">
									<input type="text" id="" name="wpematico_subscription[lname]" value="<?php echo $current_user->user_lastname; ?>" size="40" class="form-control" placeholder="<?php _e("Last Name", "wpematico"); ?>">
								</div>
							</div>

							<div class="form-group">
								<label><?php _e("Email", "wpematico"); ?> <span>(*)</span></label>
								<input type="text" id="" name="wpematico_subscription[email]" value="<?php echo $current_user->user_email; ?>" size="40" class="form-control" placeholder="<?php _e("Email", "wpematico"); ?>">
							</div>

							<div class="wpbutton-submit-subscription">
								<input type="submit" class="button button-primary"  value="<?php _e('Subscribe', 'wpematico'); ?>">
							</div>
						</form>
					</div>
				</div>
			<?php } ?>
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
			$readme = '<p>' . __('No valid changelog was found.', 'wpematico') . '</p>';
		}else {
			$readme	 = WPeMatico_functions::wpematico_get_contents($file);
			$readme	 = explode('Privacy terms =', $readme);
			$readme	 = end($readme);
			$readme	 = explode('== Installation ==', $readme);
			$readme	 = $readme[0];
			$readme	 = nl2br(html_entity_decode($readme));

			$readme	 = preg_replace('/`(.*?)`/', '<code>\\1</code>', $readme);
			$readme	 = preg_replace('/[\040]\*\*(.*?)\*\*/', ' <strong>\\1</strong>', $readme);
			$readme	 = preg_replace('/[\040]\*(.*?)\*/', ' <em>\\1</em>', $readme);
			$readme	 = preg_replace('/= (.*?) =/', '<h4>\\1</h4>', $readme);
			$readme	 = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="\\2">\\1</a>', $readme);
		}

		return $readme;
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
		// Bail if no activation redirect
		if(!get_transient('_wpematico_activation_redirect'))
			return;

		// If a user has seen the welcome page then not redirect him again. 
		if(get_transient('_wpematico_user_has_seen_welcome_page')) {
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



		$upgrade = get_option('wpematico_db_version');
		wp_cache_delete('wpematico_db_version', 'options');
		update_option('wpematico_db_version', WPEMATICO_VERSION, false);

		// It constant could be used to prevent redirects.
		if(defined('WPEMATICO_PREVENT_REDIRECT')) {
			return;
		}


		if(!$upgrade) { // First time install
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
