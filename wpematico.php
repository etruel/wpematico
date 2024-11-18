<?php
/**
 * Plugin Name: WPeMatico
 * Plugin URI: https://www.wpematico.com
 * Description: Create posts automatically from RSS/Atom feeds organized into campaigns with multiples filters.  If you like it, please rate it 5 stars.
 * Version: 2.7.10
 * Author: Etruel Developments LLC
 * Author URI: https://etruel.com/wpematico/
 * Text Domain: wpematico
 * Domain Path: /lang/
 * 
 * @package WPeMatico
 * @category Core
 * @author etruel <etruel@etruel.com>
 */
# @charset utf-8
if (!function_exists('add_filter'))
	exit;
if (!class_exists('Main_WPeMatico')) {

	/**
	 * Main_WPeMatico Class.
	 */
	class Main_WPeMatico {

		private static $instance;

		private function setup_constants() {
			if (!defined('WPEMATICO_VERSION'))
				define('WPEMATICO_VERSION', '2.7.10');
			if (!defined('WPEMATICO_BASENAME'))
				define('WPEMATICO_BASENAME', plugin_basename(__FILE__));
			if (!defined('WPEMATICO_ROOTFILE'))
				define('WPEMATICO_ROOTFILE', __FILE__);
			if (!defined('WPEMATICO_PLUGIN_URL'))
				define('WPEMATICO_PLUGIN_URL', plugin_dir_url(__FILE__));
			if (!defined('WPEMATICO_PLUGIN_DIR'))
				define('WPEMATICO_PLUGIN_DIR', plugin_dir_path(__FILE__));
		}

		public static function required_php_notice() {
			?>
			<div class="error"> <p>
				<b>WPeMatico:</b> <?php esc_html_e('PHP 7.0 or higher needed!', 'wpematico'); ?><br />
			</p></div>
			<?php
		}

		public static function instance() {
			if (version_compare(phpversion(), '5.6.0', '<')) { // check PHP Version
				add_action('admin_notices', array(__CLASS__, 'required_php_notice'));
				return false;
			}

			if (!self::$instance) {
				self::$instance = new Main_WPeMatico();
				self::$instance->setup_constants();
				self::$instance->includes();
				add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
				self::$instance->hooks();
				self::$instance->setup_cron();
			}
			return self::$instance;
		}

		private function includes() {
			global $cfg;
			if (is_admin()) {
				if (file_exists('app/nonstatic.php'))
					require_once('app/nonstatic.php');
				require_once('app/plugin_functions.php');
				require_once('app/campaigns_list.php');
				require_once("app/campaign_edit_functions.php");
				require_once('app/campaign_edit.php');
				require_once("app/settings_help.php");
				require_once("app/tools_help.php");
				require_once("app/settings_page.php");
				require_once("app/tools_page.php");
				require_once("app/debug_page.php");
				require_once("app/settings_tabs.php");
				require_once("app/tools_tabs.php");
				require_once("app/addons_page.php");
				require_once("app/notification_traslate.php");
				require_once("app/smart_notifications.php");
				require_once("app/wp-backend-helpers.php");
				require_once('app/lib/licenses_handlers.php');
				require_once('app/lib/update_class.php');
				require_once("app/lib/welcome.php");
				require_once('app/campaign_log.php');
				require_once('app/campaign_preview.php');
				require_once('app/campaign_preview_item.php');
			}
			require_once('app/cron_functions.php');
			require_once('app/compatibilities.php');
			require_once('app/wpematico_functions.php');
			require_once('wpematico_class.php');
			require_once('app/xml-importer.php');
			require_once('app/cron.php');
		}

		private function hooks() {
			add_action('init', array('WPeMatico', 'init'));
			add_action('the_permalink', array('WPeMatico', 'wpematico_permalink'));
			add_filter('post_link', array('WPeMatico', 'wpematico_permalink'));
			add_filter('get_canonical_url', array('WPeMatico_functions', 'wpematico_set_canonical'), 999999, 2);
		}

		/**
		 * setup_cron 
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      void
		 */
		public function setup_cron() {
			global $cfg;
			//Disable WP_Cron
			if (isset($cfg['disablewpcron']) && $cfg['disablewpcron']) {
				if (!defined('DISABLE_WP_CRON')) {
					define('DISABLE_WP_CRON', true);
				}
			}
			if (isset($cfg['enable_alternate_wp_cron']) && $cfg['enable_alternate_wp_cron']) {
				if (!defined('ALTERNATE_WP_CRON')) {
					define('ALTERNATE_WP_CRON', true);
				}
			}
			if (isset($cfg['dontruncron']) && $cfg['dontruncron']) {
				wp_clear_scheduled_hook('wpematico_cron');
			} else {
				add_filter('cron_schedules', 'wpematico_intervals'); //add cron intervals
				add_action('wpematico_cron', 'wpem_cron_callback');  //Actions for Cron job
				//test if cron active
				if (!wp_next_scheduled('wpematico_cron')) {
					wp_schedule_event(time(), 'wpematico_int', 'wpematico_cron');
				}
			}
		}

		/**
		 * Internationalization
		 *
		 * @access      public
		 * @since       1.0.0
		 * @simplify to standard WP      2.6.3
		 * @return      void
		 */
		public function load_textdomain() {
			load_plugin_textdomain('wpematico', false, 'wpematico/lang');
		}

	}

	//class WPeMatico
}
$WPeMatico = Main_WPeMatico::instance();
