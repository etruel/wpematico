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
if ( ! defined( 'ABSPATH' ) ) exit;

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
	public $minimum_capability = 'manage_options';

	public $api_url_subscription = 'http://www.wpematico.com/wp-admin/admin-post.php?action=wpmapirest_importdata';

	/**
	 * Get things started
	 *
	 * @since 1.4
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menus') );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		/* It'll be used on future.
		add_action( 'admin_init', array( $this, 'prevent_double_act_redirect'), 9);
		*/
		add_action( 'admin_init', array( $this, 'welcome'), 11 );
		add_action( 'admin_post_save_subscription_wpematico', array($this, 'save_subscription'));
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
			__( 'Welcome to WPeMatico', 'wpematico' ),
			__( 'WPeMatico News', 'wpematico' ),
			$this->minimum_capability,
			'wpematico-about',
			array( $this, 'about_screen' )
		);

		// Changelog Page
		add_dashboard_page(
			__( 'WPeMatico Changelog', 'wpematico' ),
			__( 'WPeMatico Changelog', 'wpematico' ),
			$this->minimum_capability,
			'wpematico-changelog',
			array( $this, 'changelog_screen' )
		);

		// Getting Started Page
		add_dashboard_page(
			__( 'Getting started with WPeMatico', 'wpematico' ),
			__( 'Getting started with WPeMatico', 'wpematico' ),
			$this->minimum_capability,
			'wpematico-getting-started',
			array( $this, 'getting_started_screen' )
		);

/*		// Subscription
		add_dashboard_page(
			__( 'WPeMatico Subscription', 'wpematico' ),
			__( 'WPeMatico Subscription', 'wpematico' ),
			$this->minimum_capability,
			'wpematico-subscription',
			array( $this, 'subscription_form')
		);

*/
		// Now remove them from the menus so plugins that allow customizing the admin menu don't show them
//		remove_submenu_page( 'index.php', 'wpematico-about' );
		remove_submenu_page( 'index.php', 'wpematico-changelog' );
		remove_submenu_page( 'index.php', 'wpematico-getting-started' );
//		remove_submenu_page( 'index.php', 'wpematico-subscription' );
	}

	/**
	 * Hide Individual Dashboard Pages
	 *
	 * @access public
	 * @since 1.4
	 * @return void
	 */
	public function admin_head() {
		?>
		<style type="text/css" media="screen">
			/*<![CDATA[*/
			.wpematico-about-wrap .wpematico-badge { float: right; border-radius: 4px; margin: 0 0 15px 15px; max-width: 100px; }
			.wpematico-about-wrap #wpematico-header { margin-bottom: 15px; }
			.wpematico-about-wrap #wpematico-header h1 { margin-bottom: 15px !important; }
			.wpematico-about-wrap .about-text { margin: 0 0 15px; max-width: 670px; }
			.wpematico-about-wrap .feature-section { margin-top: 20px; padding-bottom: 10px;}
			.wpematico-about-wrap .changelog { margin-bottom: 20px;}
			.wpematico-about-wrap .feature-section-content,
			.wpematico-about-wrap .feature-section-media { width: 50%; box-sizing: border-box; }
			.wpematico-about-wrap .feature-section-content { float: left; padding-right: 50px; }
			.wpematico-about-wrap .feature-section-content h4 { margin: 0 0 1em; }
			.wpematico-about-wrap .feature-section-media { float: right; text-align: right; margin-bottom: 20px; }
			.wpematico-about-wrap .feature-section-media img { border: 1px solid #ddd; }
			.wpematico-about-wrap .feature-section:not(.under-the-hood) .col { margin-top: 0; }
			/* responsive */
			@media all and ( max-width: 782px ) {
				.wpematico-about-wrap .feature-section-content,
				.wpematico-about-wrap .feature-section-media { float: none; padding-right: 0; width: 100%; text-align: left; }
				.wpematico-about-wrap .feature-section-media img { float: none; margin: 0 0 20px; }
			}
			/*]]>*/
		</style>
		<?php
	}

	/**
	 * Welcome message
	 *
	 * @access public
	 * @since 2.5
	 * @return void
	 */
	public function welcome_message() {

		$stored_wpematico_version = get_option( 'wpematico_db_version' );
		if (version_compare(WPEMATICO_VERSION, $stored_wpematico_version, '!=')) { 
			set_transient( '_wpematico_user_has_seen_welcome_page', true, DAY_IN_SECONDS);
			echo '<div style="display: block !important;" class="notice notice-error below-h2">' . __( 'WPeMatico could not update the version in your database. Please if your website has an object cache disable it.', 'wpematico') . '</div>';
	    }

		list( $display_version ) = explode( '-', WPEMATICO_VERSION );
		?>
		<div id="wpematico-header">
			<img class="wpematico-badge" src="<?php echo WPEMATICO_PLUGIN_URL . '/images/icon-256x256.png'; ?>" alt="<?php _e( 'WPeMatico', 'wpematico' ); ?>" / >
			<h1><?php printf( __( 'Welcome to WPeMatico %s', 'wpematico' ), $display_version ); ?></h1>
			<p class="about-text">
				<?php printf( __( 'Thank you for updating to the latest version! WPeMatico %s is ready to make your autoblogging faster, safer, and better!', 'wpematico' ), $display_version ); ?>
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
		$selected = isset( $_GET['page'] ) ? $_GET['page'] : 'wpematico-about';
		?>
		<h1 class="nav-tab-wrapper">
			<a class="nav-tab <?php echo $selected == 'wpematico-about' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wpematico-about' ), 'index.php' ) ) ); ?>">
				<?php _e( "What's New", 'wpematico' ); ?>
			</a>
			<a class="nav-tab <?php echo $selected == 'wpematico-getting-started' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wpematico-getting-started' ), 'index.php' ) ) ); ?>">
				<?php _e( 'Getting Started', 'wpematico' ); ?>
			</a>
			<a class="nav-tab <?php echo $selected == 'wpematico-changelog' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wpematico-changelog' ), 'index.php' ) ) ); ?>">
				<?php _e( 'Changelog', 'wpematico' ); ?>
			</a>
<?php /*			<a class="nav-tab <?php echo $selected == 'wpematico-subscription' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wpematico-subscription' ), 'index.php' ) ) ); ?>">
				<?php _e( 'Subscription', 'wpematico' ); ?>
			</a>*/ ?>
		</h1>
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
		<div class="wrap about-wrap wpematico-about-wrap">
			<?php

				// load welcome message and content tabs
				$this->welcome_message();
				$this->tabs();
			?>
			<div class="changelog">
				<h2><?php _e( 'Debug Mode.', 'wpematico' );?></h2>
				<div class="feature-section">
					<div class="feature-section-media">
						<?php $this->subscription_form(); ?>
					</div>
					<div class="feature-section-content">
						<h4><?php _e( 'Debug Mode to save all the logs from each campaign', 'wpematico' );?></h4>
						<p><?php _e( 'This feature allows you to save all the logs of each campaign rather than just the last one, in order to allow you to track all actions and behaviors when running the campaign.', 'wpematico' );?></p>
					</div>
					<div class="feature-section-media">
						<img style="float:left; width: 100%;" src="<?php echo WPEMATICO_PLUGIN_URL.'/images/debug_mode.png'; ?>" alt="Preview for the YouTube campaign" width="400" />
					</div>
				</div>
			</div>
			<hr>
			<div class="changelog">
				<h2><?php _e( 'A new Campaign Type for XML feeds.', 'wpematico' );?></h2>
				<div class="feature-section">
					<div class="feature-section-content">
						<h4><?php _e( 'XML Campaign type to parse and fetch XML feeds', 'wpematico' );?></h4>
						<p><?php _e( 'This feature allows you to configure every campaign with the fields that are found in the XML tags.  A very important addition that will allow to import almost anything that come in XML format to WordPress Posts (types).', 'wpematico' );?></p>
						<p><?php _e( 'An option in Settings enables the upload of XML files in the WordPress Media Library in order to use its URL in the campaigns.', 'wpematico' );?></p>
						<p><?php _e( 'Also, using the addons as the Professional will allow overwriting the data of a campaign and add the author, categories and tags also from the XML tags.', 'wpematico' );?></p>
					</div>
					<div class="feature-section-media">
						<img style="float:left; width: 100%;" src="<?php echo WPEMATICO_PLUGIN_URL.'/images/xml_campaign.png'; ?>" alt="Preview for the YouTube campaign" width="400" />
					</div>
				</div>
			</div>
			<hr>
			<div class="changelog">
				<h2><?php _e( 'A new Campaign Type for bbPress.', 'wpematico' );?></h2>
				<div class="feature-section">
					<div class="feature-section-content">
						<h4><?php _e( 'This new feature allows you to automatically create new bbPress Forums, Topics ands Replies', 'wpematico' );?></h4>
						<p><?php _e( 'With this Campaign Type you have the possibility to automatically create new Forums or select an already created bbPress Forum to publish new Topics inside it, these new posts (types) will be created according to the items of the feed that you are using.', 'wpematico' );?></p>
					</div>
					<div class="feature-section-media">
						<img style="float:left; width: 100%;" src="<?php echo WPEMATICO_PLUGIN_URL.'/images/bbpress_campaign.png'; ?>" alt="Preview for the YouTube campaign" width="400" />
					</div>
				</div>
			</div>
			<hr>
			<div class="changelog">
				<h2><?php _e( 'A new preview for the YouTube campaign.', 'wpematico' );?></h2>
				<div class="feature-section">
					<div class="feature-section-content">
						<h4><?php _e( 'This feature shows how the posts fetched from YouTube will look before that are created.', 'wpematico' );?></h4>
						<p><?php _e( 'Now you have the possibility to choose which elements of the YouTube feeds will be included in the post, the image, the featured image or the description, the preview section in the metabox will show you in real time.', 'wpematico' );?></p>
						<p><?php _e( "You can mark the selection options to see the possible results of the Post Template." , 'wpematico' );?></p>
					</div>
					<div class="feature-section-media">
						<img style="float:left; width: 100%;" src="<?php echo WPEMATICO_PLUGIN_URL.'/images/yt-campaign.png'; ?>" alt="Preview for the YouTube campaign" width="400" />
					</div>
				</div>
			</div>
			<hr>
			<div class="changelog">
				<h2><?php _e( 'A new Campaign Fetch Preview.', 'wpematico' );?></h2>
				<div class="feature-section">
					<div class="feature-section-media">
						<img style="border: 0;" src="<?php echo WPEMATICO_PLUGIN_URL.'/images/previewbutton.png'; ?>" alt="Campaign Preview" />
					</div>
					<div class="feature-section-content">
						<h4><?php _e( 'Introducing the Campaign Preview Feature.', 'wpematico' );?></h4>
						<p><?php _e( 'The most visible change in version 1.9 is certainly the Campaign Preview.', 'wpematico' );?><br />
						<?php _e( 'This new feature lets you view the list of the posts the campaign will fetch the next time it runs.', 'wpematico' );?><br />
						<?php _e( 'You can see the title, image and an excerpt of its content, but you can click in the title to see all its content like it will bring by WPeMatico plugin.', 'wpematico' );?>
						</p>
					
						<h4><?php _e( 'Using the Campaign Preview.', 'wpematico' );?></h4>
						<p>
							<?php _e('When click in the “eye” icon, a popup will open to show you the next items to fetch.  This allow you to see if the campaign has pending items to publish from any feed inside it.','wpematico'); ?>
						</p>

					</div>
				</div>
			</div>
			<hr>
			<div class="changelog">
				<h2><?php _e( 'More Tweaks and Improvements.', 'wpematico' );?></h2>
					<div class="feature-section">
						<div class="feature-section-media">
							<h4><?php _e( 'WPeMatico Addons', 'wpematico' );?></h4>
							<img style="float:left;margin-right: 7px; width: 120px;" src="<?php echo WPEMATICO_PLUGIN_URL.'/images/wpematico-premium-200x100.png'; ?>" alt="wpematico_exporter" width="200" />
							<p><?php _e('The','wpematico'); ?> <a href="https://etruel.com/downloads/wpematico-exporter/" target="_blank">WPeMatico Premium Package</a> 
							<?php _e( 'contains the five preferred add-Ons with the most wanted features for autoblogging with WordPress in a very easy professional way.', 'wpematico' );?></p>
							<img style="float:left;margin-right: 7px; width: 120px;" src="<?php echo WPEMATICO_PLUGIN_URL.'/images/wpematico_manual_fetching-200x100.png'; ?>" alt="wpematico_manual" width="200" />
							<p><?php _e('The','wpematico'); ?> <a href="https://etruel.com/downloads/wpematico-manual-fetching/" target="_blank">WPeMatico Manual Fetching</a> 
							<?php _e( 'extends the Campaign Preview functionality to every feed individually and allows you to review and insert each item, one by one or in bulk mode.', 'wpematico' );?></p>
							<img style="float:left;margin-right: 7px; width: 120px;" src="<?php echo WPEMATICO_PLUGIN_URL.'/images/wpematico_ebay-200x100.png'; ?>" alt="wpematico_ebay" width="200" />
							<p><?php _e('The','wpematico'); ?> <a href="https://etruel.com/downloads/ebay-campaign-type/" target="_blank">WPeMatico Ebay Campaign Type</a>
							<?php _e( 'uses eBay products in your site and publish them as posts or WooCommerce products by relating your eBay Parter Network Campaigns IDs.', 'wpematico' );?></p>
							<img style="float:left;margin-right: 7px; width: 120px;" src="<?php echo WPEMATICO_PLUGIN_URL.'/images/wpematico-hooks_200x100.png'; ?>" alt="WPeMatico Custom Hooks" width="200" />
							<p><?php _e('The','wpematico'); ?> <a href="https://wordpress.org/plugins/wpematico-custom-hooks/" target="_blank">WPeMatico Custom Hooks</a> 
							<?php _e( 'is a FREE addon that allows you to execute actions and filters provided by WPeMatico in order to create custom behavior in the execution of your campaigns.', 'wpematico' );?></p>
							<h5><a style="float:right;" href="https://etruel.com/starter-packages/" target="_blank"><?php _e( 'Starter Packages.', 'wpematico' );?></a>
							<a style="float:left;" href="https://etruel.com/downloads/category/wpematico-add-ons/" target="_blank"><?php _e( 'All available Addons', 'wpematico' );?></a></h5>
						</div>
						<div class="feature-section-content">
							<h4><?php _e('Better speed on uploading files.', 'wpematico' );?></h4>
							<p><?php  _e("We've improved the functions and the ways used for pull the images and attach to the published post.", 'wpematico' );?></p>
							
							<h4><?php _e('Better control on running campaigns manually.', 'wpematico' );?></h4>
							<p><?php  _e('Until now if you run a campaign and give an error in the execution, the campaign would hangs up, but from now when fails, it will show an alert with the error message.', 'wpematico' );?></p>
							
							<h4><?php _e('More icons and cosmetics things.', 'wpematico' );?></h4>
							<p><?php  _e("We're optimizing the screens to make them be more readable by humans, and also get more and better helps with examples and tips in the campaign editing or other screens.", 'wpematico' );?></p>
							<p><?php  _e("Find tips by clicking in the <em>Help</em> tab in the top-right corner inside Wordpress admin screens.", 'wpematico' );?></p>
						</div>
				</div>
			</div>
			
			<hr>
			<div class="changelog">
				<h2><?php _e( 'Including Media Files.', 'wpematico' );?></h2>
				<div class="feature-section">
					<div class="feature-section-media">
						<h4><?php _e( 'Mp3 and Mp4 Files', 'wpematico' );?></h4>
						<img style="float:right;width: 120px;margin: 0 0 0 5px;" src="<?php echo WPEMATICO_PLUGIN_URL.'/images/videoaudio.png'; ?>" alt="wpematico_audio y video" width="200" />
						<p><?php _e( 'In addition to including the embedded links, the version 1.7 includes a full support for MP3 and MP4 media files. ', 'wpematico' );?><br />
						<?php _e( 'Audio and video files inserted in the contents of the source can be downloaded and attached to the published post. ', 'wpematico' );?><br />
						<?php _e( 'Compatible with audio widget and video widget inserted in WordPress 4.8.', 'wpematico' );?> <?php _e( 'Only take care with the size of the files.', 'wpematico' );?> ;-)
						</p>
						
						<h4><?php _e( 'Need other file types ?', 'wpematico' );?></h4>
						<p><?php _e('The','wpematico'); ?> <strong><a href="https://etruel.com/downloads/wpematico-professional/" target="_blank">WPeMatico Professional add-on</a></strong> <?php _e( 'brings support for &lt;mp3, ogg, wav, wma, m4a&gt; &lt;mp4, m4v, mov, wmv, avi, mpg, ogv, 3gp, 3g2&gt; media file types among a lot more of special features.', 'wpematico' );?></p>

					</div>
					<div class="feature-section-content">
						<h4><?php _e( 'Introducing the Campaign Control Panel.', 'wpematico' );?></h4>
						<img style="float:right; width:245px;margin: 0 5px 0 5px;" src="<?php echo WPEMATICO_PLUGIN_URL.'/images/ccpanel.png'; ?>" alt="Campaign Control Panel" />
						<p><?php _e( 'This panel makes it possible to better control what is going on with the campaign you are editing.', 'wpematico' );?><br />
						<?php _e( 'And also the "Delete hash" buttons for the duplicates control, view the "Last-run log", or even the "Reset" are displayed as buttons, no matter if the quick actions from Settings screen are activated.', 'wpematico' );?>
						</p>
					
						<h4><?php _e( 'Available for Addons too.', 'wpematico' );?></h4>
						<p><?php _e('The','wpematico'); ?> <strong><a href="https://etruel.com/downloads/wpematico-professional/" target="_blank">WPeMatico Professional add-on</a></strong> <?php _e( 'will put an extra button to Export the campaign. The Control Panel has an proggramatic action to easily add an action button..', 'wpematico' );?></p>

					</div>
					
				</div>
			</div>
			<hr>
			<div class="changelog">
				<h2><?php _e( 'An improved Campaign Wizard.', 'wpematico' );?></h2>
				<div class="feature-section">
					<div class="feature-section-media">
						<img src="<?php echo WPEMATICO_PLUGIN_URL . 'images/wizard.png'; ?>"/>
					</div>
					<div class="feature-section-content">
						<p><?php _e( "We've made several important improvements in the Campaign Wizard.", 'wpematico' );?></p>
						<p><?php _e( 'With it you will see one by one each metabox with options of the campaign, showing the help tips to facilitate even more the creation and correct configuration of the feeds to import. And something very important is that it is compatible with the diferent addons that add metaboxes to the campaign.', 'wpematico' );?></p>

						<h4><?php _e('All icons will be Dashicons!', 'wpematico' );?></h4>
						<p><?php _e( "Until now we were using sprite images or Font Awesome Icons, but we are changing all to Dashicons, the official icon fonts of the WordPress and we're very proud of that.  We continue cleaning all, inclusive the corners." , 'wpematico' );?></p>

						<h4><?php _e('The code was optimized by everywhere.', 'wpematico' );?></h4>
						<p><?php _e( "We're optimizing the code to allow it to be more readable by humans, but the main focus is to improve the performance in the different screens, like the campaign editing or other screens.", 'wpematico' );?></p>
					</div>
				</div>
			</div>
			<hr>
			<div class="changelog">
				<h2><?php _e( 'Even More Developer Happiness', 'wpematico' );?></h2>
				<div class="feature-section three-col">
					<div class="col">
						<h4><?php _e( 'JavaScript hooks', 'wpematico' );?></h4>
						<p><?php _e( "We've implemented the JavaScript hooks like WordPress actions and filters! You can make functions to enqueue the scripts and hooks to already added filters in the code.", 'wpematico' );?></p>
					</div>
					<div class="col">
						<h4><a href="https://etruel.com/my-account/support/" target="_blank"><?php _e('Support ticket system for free', 'wpematico'); ?></a></h4>
						<p><?php _e( 'Ask for any problem you may have and you\'ll get support for free. If it is necessay we will see into your website to solve your issue.', 'wpematico' );?></p>
					</div>
					<div class="col">
						<h4><a href="https://etruel.com/downloads/premium-support/" target="_blank"><?php _e('Premium Support', 'wpematico'); ?></a></h4>
						<p><?php _e( 'Get access to in-depth setup assistance. We\'ll dig in and do our absolute best to resolve issues for you. Any support that requires code or setup your site will need this service.' ,'wpematico' );?></p>
					</div>

					<div class="col">
						<h4><?php _e( 'Nags updates individually for extensions', 'wpematico' );?><span class="plugin-count" style="display: inline-block;background-color: #d54e21;color: #fff;font-size: 9px;line-height: 17px;font-weight: 600;margin: 1px 0 0 2px;vertical-align: top;-webkit-border-radius: 10px;border-radius: 10px;z-index: 26;padding: 0 6px;">1</span></h4>
						<p><?php _e( 'A more clear nag update was added for the addons in the WPeMatico Extensions and Addons menu items.', 'wpematico' );?></p>
					</div>
					<div class="col">
						<h4><?php _e( 'Hidden Options in Settings->Writing', 'wpematico' );?></h4>
						<p><?php _e("If you have any problem with WPeMatico item menu, Settings page or lost some plugin, we've put there a WPeMatico Section, to try to avoid weird behaviors made by some thirds plugins.", 'wpematico' );?></p>
					</div>
					<div class="col">
						<h4><a href="https://wordpress.org/support/view/plugin-reviews/wpematico?filter=5&rate=5#new-post" target="_blank"><?php _e( 'Rate 5 stars on Wordpress', 'wpematico' );?></a><div class="wporg-ratings" title="5 out of 5 stars" style="color:#ffb900;float: right;"><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span></div></h4>
						<p><?php _e( 'We need your positive rating of 5 stars in WordPress. Your comment will be published on the bottom of the website and besides it will help making the plugin better.', 'wpematico' );?></p>
					</div>
				</div>
			</div>

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'post_type' => 'wpematico', 'page' => 'wpematico_settings' ), 'edit.php' ) ) ); ?>"><?php _e( 'Go to WPeMatico Settings', 'wpematico' ); ?></a> &middot;
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wpematico-changelog' ), 'index.php' ) ) ); ?>"><?php _e( 'View the Full Changelog', 'wpematico' ); ?></a>  &middot; 
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wpemaddons' ), 'plugins.php' ) ) ); ?>"><?php _e( 'Go to WPeMatico Extensions', 'wpematico' ); ?></a>
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
		<div class="wrap about-wrap wpematico-about-wrap">
			<?php
				// load welcome message and content tabs
				$this->welcome_message();
				$this->tabs();
			?>
			<div class="changelog">
				<h3><?php _e( 'Full Changelog', 'wpematico' );?></h3>

				<div class="feature-section">
					<?php echo $this->parse_readme(); ?>
				</div>
			</div>

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'post_type' => 'wpematico', 'page' => 'wpematico-settings' ), 'edit.php' ) ) ); ?>"><?php _e( 'Go to WPeMatico Settings', 'wpematico' ); ?></a>
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
		<div class="wrap about-wrap wpematico-about-wrap">
			<?php
				// load welcome message and content tabs
				$this->welcome_message();
				$this->tabs();
			?>
			<p class="about-description"><?php _e( 'Autoblogging in the blink of an eye! On complete autopilot WpeMatico gets new content regularly for your site!  WPeMatico is a very easy to use autoblogging plugin. Organized into campaigns, it publishes your posts automatically from the RSS/Atom feeds of your choice.', 'wpematico' ); ?></p>
			<p class="about-description"><?php _e( 'Use the tips below to get started using WPeMatico. You will be up and running in no time!', 'wpematico' ); ?></p>

			<div class="changelog">
				<h3><?php _e( 'Fill in the Settings', 'wpematico' );?></h3>
				<div class="feature-section">
					<div class="feature-section-media" style="max-height: 400px; overflow: hidden;">
						<img src="<?php echo WPEMATICO_PLUGIN_URL . 'screenshot-5.jpg'; ?>" class="wpematico-welcome-screenshots"/>
					</div>
					<div class="feature-section-content">
						<h4><?php _e( 'RSS', 'wpematico' );?></h4>
						<p><?php _e( 'RSS is a technology to facilitate the distribution of information in a centralized way. Usually daily visit several websites to see if there is anything new in our favorite places. The fundamental principle behind RSS is that "the receiver is no longer in search of information, is the information that goes in search of the receiver." If you use an RSS aggregators not have to visit each of these sites because they receive all the news in one place. The aggregator checks your favorite websites in search of new content and features directly without any effort on your part.', 'wpematico' );?></p>
						<p><?php _e( 'Blogs contain in its main page a XML file. In the case of blogs on WordPress the feed is defined as follows:', 'wpematico' );?></p><code>http://domain.com/feed</code>
						<p><?php _e( 'We have to add this URL to the RSS field to receive the items.', 'wpematico' );?></p>
						<br />
						<h4><a href="<?php echo admin_url( 'edit.php?post_type=wpematico&page=wpematico_settings' ) ?>"><?php _e( 'WPeMatico &rarr; Settings', 'wpematico' ) ; ?></a></h4>
						<p><?php _e( 'The WPeMatico &rarr; Settings menu is where you\'ll set all global aspects for the operation of the plugin and the global options for campaigns, advanced options and tools.', 'wpematico' ) ; ?></p>
						<p><?php _e( 'There are also here the tests and the configuration options for the SimplePie library to get differnet behaviour when fetch the feed items.', 'wpematico' ) ; ?></p>
						<p><?php _e( 'Set to an external or internal Wordpress CRON scheduler and look at for the configuration tabs of all plugin extensions and Add-ons.', 'wpematico' ) ; ?></p>
					</div>
					<div class="feature-section-media">
						<img src="<?php echo WPEMATICO_PLUGIN_URL . 'screenshot-7.jpg'; ?>" class="wpematico-welcome-screenshots"/>
						<p style="text-align:center;margin:0;"><?php _e( 'Testing SimplePie library', 'wpematico' );?></p>
					</div>
				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Creating Your First Campaign', 'wpematico' );?></h3>
				<div class="feature-section">
					<div class="feature-section-media" style="max-height: 300px; overflow: hidden;">
						<img src="<?php echo WPEMATICO_PLUGIN_URL . 'screenshot-6.jpg'; ?>" class="wpematico-welcome-screenshots"/>
					</div>
					<div class="feature-section-content">
						<h4><a href="<?php echo admin_url( 'post-new.php?post_type=wpematico' ) ?>"><?php printf( __( '%s &rarr; Add New', 'wpematico' ), 'WPeMatico' ); ?></a></h4>
						<p><?php printf( __( 'The WPeMatico &rarr; All Campaigns menu is your access point for all aspects of your Feed campaigns creation and setup to fetch the items and insert them as posts or any Custom Post Type. To create your first campaign, simply click Add New and then fill out the campaign details.', 'wpematico' ) ); ?></p>
					</div>
				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Need Help?', 'wpematico' );?></h3>
				<div class="feature-section">
					<div class="feature-section-media">
						<img src="<?php echo WPEMATICO_PLUGIN_URL . 'screenshot-4.jpg'; ?>"/>
					</div>
					<div class="feature-section-content">
						<h4><?php _e( 'Inline Documentation','wpematico' );?></h4>
						<p><?php _e( 'Are those small sentences and/or phrases that you see alongside, or underneath, a feature in WPeMatico that give a short but very helpful explanation of what the feature is and serve as guiding tips that correspond with each feature. These tips sometimes even provide basic, recommended settings.', 'wpematico' );?></p>

						<h4><?php _e( 'Help Tab', 'wpematico' );?></h4>
						<p><?php _e( 'In addition to the inline documentation that you see scattered throughout the Dashboard, you’ll find a helpful tab in the upper-right corner of your Dashboard labeled Help. Click this tab and a panel drops down that contains a lot of text providing documentation relevant to the page you are currently viewing on your Dashboard.', 'wpematico' );?></p>
					</div>
					<span><?php _e( 'For example, if you’re viewing the WPeMatico Settings page, the Help tab drops down documentation relevant to the WPeMatico Settings page. Likewise, if you’re viewing the Add New Campaign page, clicking the Help tab drops down documentation with topics relevant to the settings and features you find on the Add New Campaign page within your Dashboard.', 'wpematico' );?></span>
					<span><?php _e( 'Just click the Help tab again to close the Help panel.', 'wpematico' );?></span>
				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Need more Help?', 'wpematico' );?></h3>
				<div class="feature-section two-col">
					<div class="col">
						<h4><?php _e( 'Phenomenal Support','wpematico' );?></h4>
						<p><?php _e( 'We do our best to provide the best support we can. If you encounter a problem or have a question, simply open a ticket using our <a target="_blank" href="https://etruel.com/my-account/support">support form</a>.', 'wpematico' );?></p>
					</div>
					<div class="col">
						<h4><?php _e( 'Need Even Faster Support?', 'wpematico' );?></h4>
						<p><?php _e( 'Our <a target="_blank" href="https://etruel.com/downloads/premium-support/">Premium Support</a> system is there for customers that need faster and/or more in-depth assistance.', 'wpematico' );?></p>
					</div>
				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Stay Up to Date', 'wpematico' );?></h3>
				<div class="feature-section two-col">
					<div class="col">
						<h4><?php _e( 'Get Notified of Extension Releases','wpematico' );?></h4>
						<p><?php _e( 'New extensions that make WPeMatico even more powerful are released nearly every single week. Subscribe to the newsletter to stay up to date with our latest releases. <a href="http://eepurl.com/bX2ANz" target="_blank">Sign up now</a> to ensure you do not miss a release!', 'wpematico' );?></p>
					</div>
					<div class="col">
						<h4><?php _e( 'Get Alerted About New Tutorials', 'wpematico' );?></h4>
						<p><?php _e( '<a href="http://eepurl.com/bX2ANz" target="_blank">Sign up now</a> to hear about the latest tutorial releases that explain how to take WPeMatico further.', 'wpematico' );?></p>
					</div>
				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'WPeMatico Add-ons', 'wpematico' );?></h3>
				<div class="feature-section two-col">
					<div class="col">
						<h4><?php _e( 'Extend the plugin features','wpematico' );?></h4>
						<p><?php _e( 'Add-on plugins are available that greatly extend the default functionality of WPeMatico. There are a Professional extension for extend the parsers of the feed contents, The Full Content add-on to scratch the source webpage looking to get the entire article, and many more.', 'wpematico' );?></p>
					</div>
					<div class="col">
						<h4><?php _e( 'Visit the Extension Store', 'wpematico' );?></h4>
						<p><?php _e( '<a href="https://etruel.com/downloads" target="_blank">The etruel store</a> has a list of all available extensions for WPeMatico, also other Worpdress plugins, some of them for free. Including convenient category filters so you can find exactly what you are looking for.', 'wpematico' );?></p>
					</div>
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

	public function subscription_form(){
	?>
	<div class="wrap about-wrap wpematico-about-wrap">
			<?php
  				$current_user = wp_get_current_user();
			?>
			<style type="text/css">
				.subscription {
					text-align: left;
				}
				form#wpsubscription_form{
					background-color: #FFFFFF;
					padding: 10px 20px;
					padding-bottom: 0px;

				}
				.wpsubscription_info{
					padding: 15px;
					margin-bottom: 19px;
					border: 1px solid transparent;
					border-radius: 3px;
					color: #1f89c4;
					background-color: #e7f4fb;
					border-color: #63b7e6;
					background: #fff!important;
				}
				.form-control{
					border: 1px solid #e1e3e8;
					color: #656b79;
					height: 34px;
					padding: 7px 9px;
					box-shadow: none!important;
				    width: 50%;
				    height: 37px;
				    padding: 8px 16px;
				    font-size: 13px;
				    line-height: 1.46153846;
				    color: #3d414a;
				    background-color: #fff;
				    background-image: none;
				    border: 1px solid #e1e3e8;
				    border-radius: 3px;
				    box-shadow: inset 0 1px 1px rgba(0,0,0,.075);
				    transition: border-color ease-in-out .15s,box-shadow ease-in-out .15s;
					-webkit-appearance: textfield;
				    background-color: white;
				    -webkit-rtl-ordering: logical;
				    user-select: text;
				    cursor: auto;
				}
				.wpbutton-submit-subscription{
					background-color: #E1E1E1;
					margin-top: 0px;
					margin-top: -10px;
					padding-left: 20px;
					padding-right: 20px;
					padding-top: 20px;
					padding-bottom: 20px;
					margin-left: -20px;
					margin-top: 20px;
					text-align: right;

				}
			</style>
			<div class="subscription">
				<?php 
					$suscripted_user = get_option('wpematico_subscription_email_'.md5($current_user->ID), false);
					if ($suscripted_user === false) {
				?>
				<p class="wpsubscription_info"><?php _e('Subscribe to our Newsletter and be the first to receive our news.','wpematico'); ?></p>
				<form action="<?php echo admin_url( 'admin-post.php' ); ?>" id="wpsubscription_form" method="post" class="wpcf7-form">
					<input type="hidden" name="action" value="save_subscription_wpematico"/>
					<?php 
						wp_nonce_field('save_subscription_wpematico');
					?>
					<p>
						<label><?php _e("First Name","wpematico"); ?>
						    <span class="">
						    	<input type="text" id="" name="wpematico_subscription[fname]" value="<?php echo $current_user->user_firstname; ?>" size="40" class="form-control">
						    </span>
					    </label>
					 </p>
					 <p>
						<label><?php _e("Last Name","wpematico"); ?>
						    <span class="">
						    	<input type="text" id="" name="wpematico_subscription[lname]" value="<?php echo $current_user->user_lastname; ?>" size="40" class="form-control">
						    </span>
					    </label>
					 </p>
					 <p>
						 <label><?php _e("email","wpematico"); ?> <span>(*)</span>
						    <span class="">
						    	<input type="text" id="" name="wpematico_subscription[email]" value="<?php echo $current_user->user_email; ?>" size="40" class="form-control">
						    </span>
					    </label>
					 </p>
					 
					<p class="wpbutton-submit-subscription"><input type="submit" class="button button-primary"  value="<?php _e('Subscribe', 'wpematico'); ?>">
					</p>
				</form>
			<?php 
			} else { ?>
				<p class="wpsubscription_info"><?php echo sprintf( __('Your email %s is already subscribed.','wpematico'), '<strong>'.$suscripted_user.'</strong>'); ?></p>
			<?php 
			}
			?>
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
		if ( ! wp_verify_nonce($_POST['_wpnonce'], 'save_subscription_wpematico' ) ) {
		    wp_die(__( 'Security check', 'wpematico' )); 
		}
		if (empty($_POST['wpematico_subscription']['fname']) || empty($_POST['wpematico_subscription']['lname']) || empty($_POST['wpematico_subscription']['email'])) {
			wp_redirect($_POST['_wp_http_referer']);
			exit;
		}
		if (!is_email($_POST['wpematico_subscription']['email'])) {
			wp_redirect($_POST['_wp_http_referer']);
			exit;
		}
		$current_user = wp_get_current_user();
		$response = wp_remote_post($this->api_url_subscription, array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 2,
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => array(),
			'body' => array('FNAME' => $_POST['wpematico_subscription']['fname'], 'LNAME' => $_POST['wpematico_subscription']['lname'], 'EMAIL' => $_POST['wpematico_subscription']['email']),
			'cookies' => array()
		    )
		);
		if (!is_wp_error($response)) {
			update_option('wpematico_subscription_email_'.md5($current_user->ID), $_POST['wpematico_subscription']['email']);
		 	WPeMatico::add_wp_notice( array('text' => __('Subscription saved',  'wpematico'), 'below-h2'=> true ) );
		}
		
		wp_redirect($_POST['_wp_http_referer']);
		exit;
	}

	/**
	 * Parse the WPEMATICO readme.txt file
	 *
	 * @since 2.0.3
	 * @return string $readme HTML formatted readme file
	 */
	public function parse_readme() {
		$file = file_exists( WPEMATICO_PLUGIN_DIR . 'readme.txt' ) ? WPEMATICO_PLUGIN_DIR . 'readme.txt' : null;

		if ( ! $file ) {
			$readme = '<p>' . __( 'No valid changelog was found.', 'wpematico' ) . '</p>';
		} else {
			$readme = file_get_contents( $file );
			$readme = nl2br( esc_html( $readme ) );
			$readme = explode( '== Changelog ==', $readme );
			$readme = end( $readme );

			$readme = preg_replace( '/`(.*?)`/', '<code>\\1</code>', $readme );
			$readme = preg_replace( '/[\040]\*\*(.*?)\*\*/', ' <strong>\\1</strong>', $readme );
			$readme = preg_replace( '/[\040]\*(.*?)\*/', ' <em>\\1</em>', $readme );
			$readme = preg_replace( '/= (.*?) =/', '<h4>\\1</h4>', $readme );
			$readme = preg_replace( '/\[(.*?)\]\((.*?)\)/', '<a href="\\2">\\1</a>', $readme );
		}

		return $readme;
	}


	/**
	 * Sends user to the Welcome page on first activation of WPEMATICO as well as each
	 * time WPEMATICO is upgraded to a new version
	 *
	 * @access public
	 * @since 1.3.8
	 * @return void
	 */
	public function welcome() {
		// Bail if no activation redirect
		if ( ! get_transient( '_wpematico_activation_redirect' ) )
			return;
		
		// If a user has seen the welcome page then not redirect him again. 
		if ( get_transient( '_wpematico_user_has_seen_welcome_page' ) ) {
			return;
		}
		// redirect if ! AJAX
		if((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || (defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit']))
			return;

		// Delete the redirect transient
		delete_transient( '_wpematico_activation_redirect' );

		// Delete the etruel_wpematico_addons_data transient to create again when access the addon page
		delete_transient( 'etruel_wpematico_addons_data' );

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) )
			return;
		

		
		$upgrade = get_option( 'wpematico_db_version' );
		wp_cache_delete( 'wpematico_db_version', 'options');
		update_option( 'wpematico_db_version', WPEMATICO_VERSION, false );
		 
		// It constant could be used to prevent redirects.
		if (defined('WPEMATICO_PREVENT_REDIRECT')) {
			return;
		}
		

		if( ! $upgrade ) { // First time install
			wp_safe_redirect( admin_url( 'index.php?page=wpematico-getting-started' ) ); exit;
		} else { // Update
			wp_safe_redirect( admin_url( 'index.php?page=wpematico-about' ) ); exit;
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
