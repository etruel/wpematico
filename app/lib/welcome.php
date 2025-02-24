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

		// Now remove them from the menus so plugins that allow customizing the admin menu don't show them
//		remove_submenu_page( 'index.php', 'wpematico-about' );		
		remove_submenu_page('index.php', 'wpematico-changelog');
		remove_submenu_page('index.php', 'wpematico-getting-started');
		remove_submenu_page('index.php', 'wpematico-privacy');

                //$current_screen = get_current_screen();
		if (!is_null($current_screen) && ($current_screen->id == "dashboard_page_wpematico-about" || $current_screen->id == "dashboard_page_wpematico-getting-started" || $current_screen->id == "dashboard_page_wpematico-changelog" || $current_screen->id == "dashboard_page_wpematico-privacy")) {
			?>
			<style type="text/css" media="screen">
				/*<![CDATA[*/
				[class*="dashboard_page_"] #wpcontent { background: #fff; padding: 0 24px; }
				.br-16{ border-radius: 16px; }
				.wpe-flex{ display: flex; }
				.about__section { background: #fff; font-size: 1.2em; margin: 0;}
				.about__section.mb-0{ margin-bottom: 0; }
				.about__section a{ color: #222; }
				.about__section a:hover{ color: #ef8e2f; }
				.about__header-wpematico { background-image: <?php echo 'url('.WPEMATICO_PLUGIN_URL . 'images/about-header.png)'; ?>; background-size: 80%; background-position: center right; padding-top: 3rem; background-color: #222; background-repeat: no-repeat; border-radius: 16px; position: relative; }
				.about__header-title-wpematico{ padding: 5rem 2rem 0; }
				.about__header-title-wpematico p { margin: 0; padding: 0; font-size: 4em; color: #fff; line-height: 1; font-weight: 900; text-transform: uppercase; }
				.about__header-title-wpematico p span { color: #f2f2f2; }
				.about__header-text { max-width: 25em; padding: 0 2rem 3rem; font-size: 1.5em; line-height: 1.4;}
				.about__header-text p { color: #fff; margin: 0; }
				.about__header-navigation { display: flex; justify-content: center; background: #fff; color: #222; border-bottom: 0; padding-top: 0;}
				.about__header-navigation .nav-tab{color: #222 ; margin: 0; padding: 24px 32px; float: none; font-size: 1.4em; line-height: 1; border-style: solid; background: 0 0; border-width: 0 0 6px; border-color: transparent;}
				.about__header-navigation .nav-tab-active { margin-bottom: -3px; }
				.about__header-navigation .nav-tab-active:active, .about__header-navigation .nav-tab-active:hover, .about__header-navigation .nav-tab-active { color: #ef8e2f; border-color: #ef8e2f;}
				.about__header-navigation .nav-tab:active, .about__header-navigation .nav-tab:hover{ background: #ef8e2f; color: #fff; }
				.about__container-wpematico { line-height: 1.4; color: #222; max-width: 1000px; margin: 24px auto; clear: both; --gap: 2rem;}
				.about__container-wpematico .has-accent-background-color { background: #ef8e2f; }
				.about__container-wpematico .has-subtle-background-color { background: #f2f2f2; }
				.about__header-title-wpematico .wpematico-badge { align-self: flex-end; margin-bottom: 10px; max-height: 80px; width: auto; }
				.about__section.about__section_height { min-height: 560px; }
				.about__section.about__section_height-2 { min-height: 400px; }
				.about__section.is-feature { font-size: 1.4em; }
				.about__container-wpematico h1, .about__container-wpematico h2, .about__container-wpematico h3.is-larger-heading{ margin-top: 0; margin-bottom: .5em; font-size: 1.75em; line-height: 1.2; font-weight: 600; }
				.about__container-wpematico h3{ font-size: 1.625rem; font-weight: 700; line-height: 1.4; }
				.about__container-wpematico h1.is-smaller-heading, .about__container-wpematico h2.is-smaller-heading, .about__container-wpematico h3.is-smaller-heading, .about__container-wpematico h4 { margin-top: 0; font-size: 1.125rem; font-weight: 700; }
				.about__container-wpematico h3.is-smaller-heading, .about__container-wpematico h4 { font-weight: 600 }
				.about__container-wpematico .about__image { margin-bottom: 1.5em; }
				.about__container-wpematico .about__image+h3{ margin-top: 0; }
				.about__container-wpematico .about__image.icon{ display: inline-flex; justify-content: center; align-items: center; width: 48px; height: 48px; border-radius: 5px; background-color: #f2f2f2; }
				.about__container-wpematico .has-subtle-background-color .about__image.icon{ background-color: #fff; }
				.about__container-wpematico .about__newsletter { padding: 0 32px; }
				.about__container-wpematico .is-section-header { padding: 32px; }
				.about__container-wpematico .is-section-header.pb-0 { padding-bottom: 0; }
				.about__section .span-text { font-size: .9em; }
				.feature-section a, .about__section p a { font-weight: 600; }
				.addon_block { display: flex; margin-bottom: 1em;}
				.addon_block .addon_img img { display:block; max-width: 120px; height: auto; margin-right: 10px; }
				.addon_block .addon_text { text-align: right;}
				.addon_block .addon_text p { margin: 0; font-size: 13px; }
				.about__section.has-2-columns, .about__section.has-3-columns, .about__section.has-4-columns, .about__section.has-overlap-style { display: grid; }
				.about__section.has-2-columns{ -ms-grid-columns: 1fr 1fr; grid-template-columns: 1fr 1fr; }
				.about__section.has-2-columns .column:nth-of-type(2n+1) { -ms-grid-column: 1; grid-column-start: 1; }
				.about__section.has-2-columns .column:nth-of-type(2n) { -ms-grid-column: 2; grid-column-start: 2; }
				.about__section .column.is-edge-to-edge{ color: #fff; padding: 0; }
				.about__section + .about__section .column{ padding: 32px; }
				.about__container-wpematico .is-vertically-aligned-center{ align-self: center; }
				@media all and ( max-width: 1035px ) {
					.about__header { background-size: 95%; }
				}
				@media all and ( max-width: 782px ) {
					.about__header{	background-image: none; }
					.about__header-title-wpematico{ margin-top: 0; padding-top: 0;}
					.about__header-title-wpematico p{ font-size: 3em; }
				}
				@media all and ( max-width: 782px ) and (min-width: 481px) {
					.about__header-navigation .nav-tab{ padding: 24px 16px; }
				}
				@media all and ( max-width: 600px ) and (min-width: 481px) {
					.about__header-navigation .nav-tab{ font-size: 1.1em; }
				}
				@media all and ( max-width: 600px ) {
					.about__header-wpematico { background-image: initial; }
					.about__header-title-wpematico p{ font-size: 2.25em; }
					.about__section.has-2-columns, .about__section.has-2-columns.is-wider-left, .about__section.has-2-columns.is-wider-right, .about__section.has-3-columns{ display: block; padding-bottom: 16px; }
					.about__section + .about__section .column{ padding-top: 16px; }
					.about__section.has-2-columns .column:nth-of-type(n){ padding-top: 16px; padding-bottom: 16px; }
					.about__header-navigation{ flex-direction: column; }
					.about__header-navigation .nav-tab { float: none; display: block; margin-bottom: 0; padding: 16px 16px; border-left-width: 6px; border-bottom: none; }
					.about__header-navigation .nav-tab-active { border-bottom: none; border-left-width: 6px; }
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
		<div class="about__header-title-wpematico">
			<img class="wpematico-badge" src="<?php echo WPEMATICO_PLUGIN_URL . '/images/robotico_orange-75x130.png'; ?>" alt="WPeMatico" />
			<p>
				WPeMatico <span><?php echo $display_version; ?></span>
			</p>
		</div>
		<div class="about__header-text">
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
		<nav class="about__header-navigation nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e('Secondary menu', 'wpematico'); ?>">
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
		<div class="wrap about__container about__container-wpematico">

			<div class="about__header-wpematico">

				<?php $this->welcome_message(); ?>

			</div>

			<?php $this->tabs(); ?>

			<?php $this->subscription_form(); ?>

			<div class="about__section has-2-columns">
				<div class="wpe-flex column is-vertically-aligned-center">
					<div class="about__image is-vertically-aligned-center">
						<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/image-attributes.jpg'; ?>" alt="Save image attributes on upload" />
					</div>
				</div>
				<div class="column is-vertically-aligned-center">
					<h3><?php _e('Save image attributes on upload', 'wpematico'); ?></h3>
					<p><?php _e('Now, WPeMatico enhances image uploads by automatically copying original attributes like alt text, title, and caption when adding images to the WordPress Media Library.', 'wpematico'); ?></p>
					<p><?php _e('This not only improves SEO but also streamlines content management by allowing you to reuse images across multiple posts while retaining their original attributes.', 'wpematico'); ?></p>
				</div>
			</div>

			<div class="about__section has-2-columns">
				<div class="column is-vertically-aligned-center">
					<h3><?php _e('Auto-created categories control', 'wpematico'); ?></h3>
					<p><?php _e('At the request of our users, we’ve added a new feature that allows you to set a maximum limit on the number of automatically generated categories.', 'wpematico'); ?></p>
					<p><?php _e('Many poorly structured feeds include dozens of different categories per item, which can clutter your site. With this improvement, you can maintain better organization and keep your site clean and manageable.', 'wpematico'); ?></p>
				</div>
				<div class="wpe-flex column is-vertically-aligned-center">
					<div class="about__image is-vertically-aligned-center">
						<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/max-categories.jpg'; ?>" alt="New WPeMatico&#39;s tools page added" />
					</div>
				</div>
			</div>

			<div class="about__section has-2-columns">
				<div class="wpe-flex column is-vertically-aligned-center">
					<div class="about__image is-vertically-aligned-center">
						<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/quick-edit.jpg'; ?>" alt="Better Quick Edit and Taxonomy Support" />
					</div>
				</div>
				<div class="column is-vertically-aligned-center">
					<h3><?php _e('Better Quick Edit and Taxonomy Support', 'wpematico'); ?></h3>
					<p><?php _e('We’ve improved compatibility with the Professional 3.0.3 version, optimizing taxonomy management within WPeMatico campaigns for Custom Post Types. The functionality is now smoother and more efficient with the enhanced code.', 'wpematico'); ?></p>
					<p><?php _e('The Quick Edit option for each campaign has also been optimized, allowing faster and more convenient editing of taxonomies directly from the panel without needing to access the full editor.', 'wpematico'); ?></p>
				</div>
			</div>

			<div class="about__section has-3-columns">
				
				<div class="column">
					<div class="about__image">
						<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/campaign-notification.jpg'; ?>" alt="Improved campaign execution notifications" />
					</div>
					<div class="about__content">
						<h3 class="is-smaller-heading"><?php _e('Improved campaign execution notifications', 'wpematico'); ?></h3>

						<p><?php _e('A new improvement shows execution messages directly below the campaign title, providing clear and organized feedback during AJAX campaign runs.', 'wpematico'); ?></p>
					</div>
				</div>
				
				<div class="column">
					<div class="about__image">
						<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/popup-deactivate.jpg'; ?>" alt="New Popup layout for campaign logs" />
					</div>
					<div class="about__content">
						<h3 class="is-smaller-heading"><?php _e('New popup feedback for plugin deactivation', 'wpematico'); ?></h3>

						<p><?php _e('Our goal is to keep improving our plugins, so we have added a popup window that appears when the plugin is deactivated, allowing users to easily submit their feedback.', 'wpematico'); ?></p>
					</div>
				</div>

				<div class="column">
					<div class="about__image">
						<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/performance.jpg'; ?>" alt="Much performance improvements" />
					</div>
					<div class="about__content">
						<h3 class="is-smaller-heading"><?php _e('General performance improvements', 'wpematico'); ?></h3>

						<p><?php _e('Several enhancements have been made to optimize the plugin’s overall performance, resulting in significantly improved efficiency and faster processing speeds.', 'wpematico'); ?></p>
					</div>
				</div>
				
				<div class="column">
					<div class="about__image">
						<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/canonical.jpg'; ?>" alt="Implemented Canonical links for SEO!" />
					</div>
					<div class="about__content">
						<h3 class="is-smaller-heading"><?php _e('Implemented Canonical links for SEO!', 'wpematico'); ?></h3>

						<p><?php _e('A new feature that allows to globally set the canonical link of each post (only created by WPeMatico) to the source site’s permalink, improving the SEO ranking of your website.', 'wpematico'); ?></p>
					</div>
				</div>

				<div class="column">
					<div class="about__image">
						<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/tools.jpg'; ?>" alt="New WPeMatico&#39;s Tools page added" />
					</div>
					<div class="about__content">
						<h3 class="is-smaller-heading"><?php _e('New WPeMatico&#39;s Tools page added', 'wpematico'); ?></h3>

						<p><?php _e('New Tools page to group features and functions that are not directly related to WPeMatico configuration and/or campaigns. You can also find here the System Status feature.', 'wpematico'); ?></p>
					</div>
				</div>

				<div class="column">
					<div class="about__image">
						<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/export-import.jpg'; ?>" alt="Added Export/import Settings backup!" style="max-height: 500px;" />
					</div>
					<div class="about__content">
						<h3 class="is-smaller-heading"><?php _e('Added Export/import Settings backup!', 'wpematico'); ?></h3>

						<p><?php _e('Find on the Tools page the new function to export and import WPeMatico and addons settings, to backup or export your settings to another website.', 'wpematico'); ?></p>
					</div>
				</div>
				
				<div class="column">
					<div class="about__image">
						<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/shorts.jpg'; ?>" alt="Avoid or include Shorts on YouTube Campaign type!" style="max-height: 500px;" />
					</div>
					<div class="about__content">
						<h3 class="is-smaller-heading"><?php _e('Avoid or include Shorts on YouTube Campaign type!', 'wpematico'); ?></h3>

						<p><?php _e('A new feature integrated into the YouTube campaign type, allows users to filter their YouTube feed to skip or only get short videos. Enhancing content relevance and personalization.', 'wpematico'); ?></p>
					</div>
				</div>
				
				<div class="column">
					<div class="about__image">
						<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/hash.jpg'; ?>" alt="Improvements in duplicate control" />
					</div>
					<div class="about__content">
						<h3 class="is-smaller-heading"><?php _e('Improvements in duplicate control', 'wpematico'); ?></h3>

						<p><?php _e('Significant new improvements in duplicate posts control: by refining the hash code, we have improved the system&#39;s ability to identify and manage duplicate items much more effectively.', 'wpematico'); ?></p>
					</div>
				</div>

				<div class="column">
					<div class="about__image">
						<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/mime_type.jpg'; ?>" alt="Modify the allowed mime types feature" />
					</div>
					<div class="about__content">
						<h3 class="is-smaller-heading"><?php _e('Modify the allowed mime types feature', 'wpematico'); ?></h3>

						<p><?php _e('A new feature that lets you upload of other mime types (during the campaign execution) that are not normally allowed by the WP library, giving you more flexibility over the media files.', 'wpematico'); ?></p>
					</div>
				</div>
			</div>

			<div class="about__section about__section_height has-2-columns has-subtle-background-color br-16">
				<h3 class="is-section-header pb-0"><?php _e('More Tweaks and Improvements.', 'wpematico'); ?></h3>

				<div class="column">
				<h3 class="is-smaller-heading"><?php _e('Introducing the GPT Spinner Addon', 'wpematico'); ?></h3>
				<p><?php _e('The new GPT Spinner is now included in all memberships, enhancing your content automation. With advanced AI capabilities, it automatically rewrites and generates unique posts from your RSS feed, giving you greater flexibility and content quality.', 'wpematico'); ?></p>

				<h3 class="is-smaller-heading"><?php _e('AI Etruel Rewriter API Integration', 'wpematico'); ?></h3>
				<p><?php _e('The AI Etruel Rewriter API is now available, designed to optimize and transform content seamlessly. Perfect for improving your automated posts and boosting SEO, this API ensures that your content always stands out.', 'wpematico'); ?></p>

				<h3 class="is-smaller-heading"><?php _e('More Optimizations and Performance Enhancements', 'wpematico'); ?></h3>
				<p><?php _e('We’ve further optimized campaign execution, ensuring smoother performance and improved stability when handling large amounts of data.', 'wpematico'); ?></p>
				</div>
				<div class="column">
					<h3 class="is-smaller-heading"><?php _e('WPeMatico Addons', 'wpematico'); ?></h3>

					<div class="addon_block">
						<div class="addon_img">
							<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/wpematico-essentials-200x100.jpg'; ?>" alt="WPeMatico ESSENTIAL Monthly" />
						</div>
						<div class="addon_text">
							<p><?php _e('The', 'wpematico'); ?> <a href="https://etruel.com/downloads/wpematico-essentials-monthly/" target="_blank">WPeMatico ESSENTIALS Monthly</a> 
							<?php _e('includes powerful addons such as Professional and Full Content, which allow you to enhance autoblogging with advanced features.', 'wpematico'); ?></p>
						</div>
					</div>

					<div class="addon_block">
						<div class="addon_img">
							<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/ai-etruel-rewriter-api-200x100.jpg'; ?>" alt="AI Etruel Rewriter API" />
						</div>
						<div class="addon_text">
						<p><?php _e('The', 'wpematico'); ?> <a href="https://etruel.com/downloads/ai-etruel-rewriter-api/" target="_blank">AI Etruel Rewriter API</a> 
						<?php _e('integrates seamlessly with GPT Spinner, allowing you to rewrite and enhance your content with advanced AI, ensuring originality and improved engagement.', 'wpematico'); ?></p>
						</div>
					</div>

					<div class="addon_block">
						<div class="addon_img">
							<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/wpematico-plus-200x100.jpg'; ?>" alt="WPeMatico PLUS" />
						</div>
						<div class="addon_text">
							<p><?php _e('The', 'wpematico'); ?> <a href="https://etruel.com/downloads/wpematico-plus/" target="_blank">WPeMatico PLUS</a> 
								<?php _e('combines the five most requested add-ons with a lot of great features, simplifying WordPress autoblogging with professional ease.', 'wpematico'); ?></p>
						</div>
					</div>

					<div class="addon_block">
						<div class="addon_img">
							<img src="<?php echo WPEMATICO_PLUGIN_URL . '/images/wpematico-rss-feed-reader-200x100.png'; ?>" alt="WPeMatico RSS Feed Reader" />
						</div>
						<div class="addon_text">
							<p><?php _e('The', 'wpematico'); ?> <a href="https://wordpress.org/plugins/wpematico-rss-feed-reader/" target="_blank">WPeMatico RSS Feed Reader</a> 
								<?php _e('enhances the functionality of WPeMatico by allowing to read and display the RSS feed results on your WordPress site without creating the posts.', 'wpematico'); ?></p>
						</div>
					</div>

					<h5><a style="float:right;" href="https://etruel.com/starter-memberships/" target="_blank"><?php _e('WPeMatico Starter Memberships.', 'wpematico'); ?></a>
						<a style="float:left;" href="https://etruel.com/downloads/category/wpematico-add-ons/" target="_blank"><?php _e('All available Addons', 'wpematico'); ?></a></h5>
				</div>
			</div>

			<div class="about__section has-2-columns mb-0">
				<h3 class="is-section-header pb-0"><?php _e('Even More Developer Happiness', 'wpematico'); ?></h3>
				<div class="column">
					<div class="about__image icon">
						<span class="dashicons dashicons-editor-code"></span>
					</div>
					<h3><?php _e('JavaScript hooks', 'wpematico'); ?></h3>
					<p><?php _e("We've implemented the JavaScript hooks like WordPress actions and filters! You can make functions to enqueue the scripts and hooks to already added filters in the code.", 'wpematico'); ?></p>
				</div>
				<div class="column">
					<div class="about__image icon">
						<span class="dashicons dashicons-tickets-alt"></span>
					</div>
					<h3><a href="https://etruel.com/my-account/support/" target="_blank"><?php _e('Support ticket system for free', 'wpematico'); ?></a></h3>
					<p><?php _e('Ask for any problem you may have and you\'ll get support for free. If it is necessay we will see into your website to solve your issue.', 'wpematico'); ?></p>
				</div>
			</div>
			<div class="about__section has-2-columns mb-0">
				<div class="column">
					<div class="about__image icon">
						<span class="dashicons dashicons-awards"></span>
					</div>
					<h3><a href="https://etruel.com/downloads/premium-support/" target="_blank"><?php _e('Premium Support', 'wpematico'); ?></a></h3>
					<p><?php _e('Get access to in-depth setup assistance. We\'ll dig in and do our absolute best to resolve issues for you. Any support that requires code or setup your site will need this service.', 'wpematico'); ?></p>
				</div>
				<div class="column">
					<div class="about__image icon">
						<span class="dashicons dashicons-update"></span>
					</div>
					<h3><?php _e('Nags updates individually for extensions', 'wpematico'); ?><span class="plugin-count" style="display: inline-block;background-color: #d54e21;color: #fff;font-size: 9px;line-height: 17px;font-weight: 600;margin: 1px 0 0 2px;vertical-align: top;-webkit-border-radius: 10px;border-radius: 10px;z-index: 26;padding: 0 6px;">1</span></h3>
					<p><?php _e('A more clear nag update was added for the addons in the WPeMatico Extensions and Addons menu items.', 'wpematico'); ?></p>
				</div>
			</div>
			<div class="about__section has-2-columns mb-0">
				<div class="column">
					<div class="about__image icon">
						<span class="dashicons dashicons-admin-settings"></span>
					</div>
					<h3><?php _e('Hidden Options in Settings -> Writing', 'wpematico'); ?></h3>
					<p><?php _e("If you have any problem with WPeMatico item menu, Settings page or lost some plugin, we've put there a WPeMatico Section, to try to avoid weird behaviors made by some thirds plugins.", 'wpematico'); ?></p>
				</div>
				<div class="column">
					<div class="about__image icon">
						<span class="dashicons dashicons-star-filled"></span>
					</div>
					<h3><a href="https://wordpress.org/support/view/plugin-reviews/wpematico?filter=5&rate=5#new-post" target="_blank"><?php _e('Rate 5 stars on Wordpress', 'wpematico'); ?></a><div class="wporg-ratings" title="5 out of 5 stars" style="color:#ffb900;"><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span></div></h3>
					<p><?php _e('We need your positive rating of 5 stars in WordPress. Your comment will be published on the bottom of the website and besides it will help making the plugin better.', 'wpematico'); ?></p>
				</div>
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

		<div class="wrap about__container about__container-wpematico">

			<div class="about__header-wpematico">

				<?php $this->welcome_message(); ?>

			</div>

			<?php $this->tabs(); ?>

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
				<a href="<?php echo esc_url(admin_url(add_query_arg(array('post_type' => 'wpematico', 'page' => 'wpematico_settings'), 'edit.php'))); ?>"><?php _e('Go to WPeMatico Settings', 'wpematico'); ?></a>
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

		<div class="wrap about__container about__container-wpematico">

			<div class="about__header-wpematico">

				<?php $this->welcome_message(); ?>

			</div>

			<?php $this->tabs(); ?>

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
				<a href="<?php echo esc_url(admin_url(add_query_arg(array('post_type' => 'wpematico', 'page' => 'wpematico_settings'), 'edit.php'))); ?>"><?php _e('Go to WPeMatico Settings', 'wpematico'); ?></a>
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
		<div class="wrap about__container about__container-wpematico">

			<div class="about__header-wpematico">

				<?php $this->welcome_message(); ?>

			</div>

			<?php $this->tabs(); ?>

			<div class="about__section is-feature">
				<p class="about-description"><?php _e('Autoblogging in the blink of an eye! On complete autopilot WpeMatico gets new content regularly for your site!  WPeMatico is a very easy to use autoblogging plugin. Organized into campaigns, it publishes your posts automatically from the RSS/Atom feeds of your choice.', 'wpematico'); ?></p><br />
				<p class="about-description"><?php _e('Use the tips below to get started using WPeMatico. You will be up and running in no time!', 'wpematico'); ?></p>
			</div>

			<div class="about__section about__section_height has-2-columns">
				<div class="column wpe-flex has-accent-background-color">
					<div class="about__image is-vertically-aligned-center">
						<div class="feature-section-media" style="max-height: 400px; overflow: hidden; border-radius: 16px;">
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
						</div>
					</div>
				</div>
			</div>

			<div class="about__section about__section_height has-2-columns">
				<div class="column wpe-flex has-accent-background-color">
					<div class="about__image is-vertically-aligned-center">
						<div class="feature-section-media" style="max-height: 400px; overflow: hidden;">
							<img src="<?php echo WPEMATICO_PLUGIN_URL . 'screenshot-6.jpg'; ?>" class="Creating Your First Campaign"/>
						</div>
					</div>
				</div>
				<div class="column is-vertically-aligned-center">
					<h2><?php _e('Creating Your First Campaign', 'wpematico'); ?></h2>

					<h3><a href="<?php echo admin_url('post-new.php?post_type=wpematico') ?>"><?php 
						/* translators: %s WPeMatico Plugin Name */
						printf( __('%s &rarr; Add New', 'wpematico'), 'WPeMatico' ); 
					?></a></h3>

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

			<div class="about__section has-2-columns has-subtle-background-color">
				<h3 class="is-section-header"><?php _e('Need more Help?', 'wpematico'); ?></h3>
				<div class="column">
					<div class="about__image icon">
						<span class="dashicons dashicons-tickets-alt"></span>
					</div>
					<h4><?php _e('Phenomenal Support', 'wpematico'); ?></h4>
					<p><?php echo __('We do our best to provide the best support we can. If you encounter a problem or have a question, simply open a ticket using our ', 'wpematico') . '<a target="_blank" href="https://etruel.com/my-account/support">' . __('support form', 'wpematico') . '</a>.'; ?></p>
				</div>
				<div class="column">
					<div class="about__image icon">
						<span class="dashicons dashicons-awards"></span>
					</div>
					<h4><?php _e('Need Even Better Support?', 'wpematico'); ?></h4>
					<p><?php echo __('Our ', 'wpematico') . '<a target="_blank" href="https://etruel.com/downloads/premium-support/">' . __('Premium Support', 'wpematico') . '</a> ' . __('system is there for customers that need faster and/or more in-depth assistance.', 'wpematico'); ?></p>
				</div>
			</div>
			<div class="about__section has-2-columns has-subtle-background-color">
				<h3 class="is-section-header"><?php _e('Stay Up to Date', 'wpematico'); ?></h3>
				<div class="column">
					<div class="about__image icon">
						<span class="dashicons dashicons-update"></span>
					</div>
					<h4><?php _e('Get Notified of Extension Releases', 'wpematico'); ?></h4>
					<p><?php echo __('New extensions that make WPeMatico even more powerful are released nearly every single week. Subscribe to the newsletter to stay up to date with our latest releases. ', 'wpematico') . '<a href="http://eepurl.com/bX2ANz" target="_blank">' . __('Sign up now', 'wpematico') . '</a>' . __(' to ensure you do not miss a release!', 'wpematico'); ?></p>
				</div>
				<div class="column">
					<div class="about__image icon">
						<span class="dashicons dashicons-megaphone"></span>
					</div>
					<h4><?php _e('Get Alerted About New Tutorials', 'wpematico'); ?></h4>
					<p><?php echo '<a href="http://eepurl.com/bX2ANz" target="_blank">' . __('Sign up now', 'wpematico') . '</a>' . __(' to hear about the latest tutorial releases that explain how to take WPeMatico further.', 'wpematico'); ?></p>
				</div>
			</div>
			<div class="about__section has-2-columns has-subtle-background-color">
				<h3 class="is-section-header"><?php _e('WPeMatico Add-ons', 'wpematico'); ?></h3>
				<div class="column">
					<div class="about__image icon">
						<span class="dashicons dashicons-admin-plugins"></span>
					</div>
					<h4><?php _e('Extend the plugin features', 'wpematico'); ?></h4>
					<p><?php _e('Add-on plugins are available that greatly extend the default functionality of WPeMatico. There are a Professional extension for extend the parsers of the feed contents, The Full Content add-on to scratch the source webpage looking to get the entire article, and many more.', 'wpematico'); ?></p>
				</div>
				<div class="column">
					<div class="about__image icon">
						<span class="dashicons dashicons-store"></span>
					</div>
					<h4><?php _e('Visit the Extension Store', 'wpematico'); ?></h4>
					<p><?php echo '<a href="https://etruel.com/downloads" target="_blank">' . __('The etruel store', 'wpematico') . '</a>' . __('has a list of all available extensions for WPeMatico, also other Worpdress plugins, some of them for free. Including convenient category filters so you can find exactly what you are looking for.', 'wpematico'); ?></p>
				</div>
			</div>

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
		if($suscripted_user === '' or $suscripted_user !== $current_user->data->user_email) $suscripted_user = false;
		$classes		 = "";
		if($suscripted_user === false) {
			$classes = "about__section_height-2 has-2-columns";
		}
		?>
		<div class="about__section <?php echo $classes; ?> subscription">
			<div class="column is-vertically-aligned-center">
				<h3><?php _e('Welcome to WPeMatico 2.8!', 'wpematico'); ?></h3>
				<p><?php _e('Robotico has brought artificial intelligence to WPeMatico! This is a major breakthrough for autoblogging, allowing you to generate unique, optimized, and automatically rewritten content to enhance SEO.','wpematico')?></p>
				<p><?php _e('Now, with smarter tools, you can organize and boost your posts like never before, achieving better results with less effort.', 'wpematico'); ?></p>
				<h3><?php _e('New content creation tools', 'wpematico'); ?></h3>
				<p><a href="https://etruel.com/downloads/wpematico-gpt-spinner" target="_blank" style="color: #ef8e2f; font-weight: 700; text-decoration: none; border-bottom: 2px solid #ef8e2f;">GPT Spinner</a> <?php _e('Addon allows you to rewrite and enhance your content automatically using advanced AI integrations, offering greater flexibility and creativity for your posts.', 'wpematico'); ?></p>
				<p><a href="https://etruel.com/downloads/ai-etruel-rewriter-api/" target="_blank" style="color: #ef8e2f; font-weight: 700; text-decoration: none; border-bottom: 2px solid #ef8e2f;">AI Etruel Rewriter API</a> <?php _e('is a powerful service for text rewriting, providing seamless integration with your campaigns to create unique and engaging content effortlessly.', 'wpematico'); ?></p>
			</div>
			<?php if($suscripted_user === false) { ?>
				<div class="column wpe-flex is-edge-to-edge has-accent-background-color br-16">
					<div class="about__newsletter is-vertically-aligned-center">
						<p></p>
						<h3><?php _e('Stay Ahead with Exclusive Updates!', 'wpematico'); ?></h3>
						<h3 class="is-smaller-heading wpsubscription_info">
							<?php _e('Join our newsletter and be the first to hear about exciting updates, new features, and special offers.', 'wpematico'); ?> 
						</h3>
						<h3 class="is-smaller-heading wpsubscription_info">
							<?php _e('No spam—just valuable insights straight to your inbox, about 4-5 times a year. Stay in the loop and never miss out!', 'wpematico'); ?> 
						</h3>
						<h3 class="is-smaller-heading wpsubscription_info">
							<?php _e('Subscribe now!', 'wpematico'); ?> 
						</h3>
						<form action="<?php echo admin_url('admin-post.php'); ?>" id="wpsubscription_form" method="post" class="wpcf7-form">
							<input type="hidden" name="action" value="save_subscription_wpematico"/>
							<?php wp_nonce_field('save_subscription_wpematico'); ?>
							<div class="two-form-group">
								<div class="form-group">
									<label><?php echo esc_html__("Name", "wpematico").' '. esc_html__("(optional)", "wpematico"); ?></label>
									<input type="text" id="" name="wpematico_subscription[fname]" value="<?php echo $current_user->user_firstname; ?>" size="40" class="form-control" placeholder="<?php _e("First Name", "wpematico"); ?>">
								</div>
								<div class="form-group">
									<input type="text" id="" name="wpematico_subscription[lname]" value="<?php echo $current_user->user_lastname; ?>" size="40" class="form-control" placeholder="<?php _e("Last Name", "wpematico"); ?>">
								</div>
							</div>

							<div class="form-group">
								<label><?php esc_html_e("Email", "wpematico"); ?> <span>(*)</span></label>
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
			$readme	 = explode('Privacy terms ##', $readme);
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