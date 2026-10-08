<?php
/**
 * Plugin Name: WPeMatico
 * Plugin URI: https://www.wpematico.com
 * Description: Create posts automatically from RSS/Atom feeds organized into campaigns with multiples filters.  If you like it, please rate it 5 stars.
 * Version: 2.9
 * Requires at least: 4.8
 * Requires PHP: 7.0
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
				define('WPEMATICO_VERSION', '2.9');

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
				self::$instance->hooks();
				self::$instance->setup_cron();
			}

			return self::$instance;
		}

		private function includes() {

			global $cfg;

			if (is_admin()) {

				if (file_exists(WPEMATICO_PLUGIN_DIR . 'includes/nonstatic.php'))
					require_once(WPEMATICO_PLUGIN_DIR . 'includes/nonstatic.php');
				require_once(WPEMATICO_PLUGIN_DIR . 'includes/plugin_functions.php');
				require_once(WPEMATICO_PLUGIN_DIR . 'includes/campaigns_list.php');
				require_once(WPEMATICO_PLUGIN_DIR . "includes/campaign_edit_functions.php");
				require_once(WPEMATICO_PLUGIN_DIR . 'includes/campaign_edit.php');
				require_once(WPEMATICO_PLUGIN_DIR . "includes/settings/help.php");
				require_once(WPEMATICO_PLUGIN_DIR . "includes/settings/page.php");
				require_once(WPEMATICO_PLUGIN_DIR . "includes/settings/functions.php");
				require_once(WPEMATICO_PLUGIN_DIR . "includes/settings/widgets.php");
				// Migration Toolkit, gated behind the "Plugin Importers" module (off by
				// default): its manager probes every supported source plugin on init, which
				// is DB work on every request for a feature a site uses once, if ever. Only
				// the detector is always here, to notice there is something to import.
				//
				// Must come before tools_page.php, which calls WPeMatico_Tools::hooks() as
				// it loads and registers the Toolkit section only if the class exists.
				require_once(WPEMATICO_PLUGIN_DIR . "includes/migration/detect.php");
				WPeMatico_Migration_Detect::hooks();
				if (WPeMatico_Migration_Detect::module_active()) {
					require_once(WPEMATICO_PLUGIN_DIR . "includes/migration/class-migration-manager.php");
				}
				require_once(WPEMATICO_PLUGIN_DIR . "includes/tools_help.php");
				require_once(WPEMATICO_PLUGIN_DIR . "includes/feed_viewer_help.php");
				require_once(WPEMATICO_PLUGIN_DIR . "includes/feed_list_help.php");
				require_once(WPEMATICO_PLUGIN_DIR . "includes/tools_page.php");
				require_once(WPEMATICO_PLUGIN_DIR . "includes/tools_tabs.php");
				require_once(WPEMATICO_PLUGIN_DIR . "includes/debug_page.php");
				require_once(WPEMATICO_PLUGIN_DIR . "includes/addons_page.php");
				require_once(WPEMATICO_PLUGIN_DIR . "includes/addons_help.php");
				require_once(WPEMATICO_PLUGIN_DIR . "includes/notification_traslate.php");
				require_once(WPEMATICO_PLUGIN_DIR . "includes/smart_notifications.php");
				require_once(WPEMATICO_PLUGIN_DIR . "includes/class-tour-helper.php");
				require_once(WPEMATICO_PLUGIN_DIR . "includes/wp-backend-helpers.php");
				require_once(WPEMATICO_PLUGIN_DIR . "includes/dashboard-widgets.php");
				require_once(WPEMATICO_PLUGIN_DIR . 'includes/lib/licenses_handlers.php');
				require_once(WPEMATICO_PLUGIN_DIR . 'includes/lib/update_class.php');
				require_once(WPEMATICO_PLUGIN_DIR . "includes/lib/welcome.php");
				require_once(WPEMATICO_PLUGIN_DIR . 'includes/campaign_log.php');
				require_once(WPEMATICO_PLUGIN_DIR . 'includes/campaign_preview.php');
				require_once(WPEMATICO_PLUGIN_DIR . 'includes/campaign_preview_item.php');
			}
			// The update gate also answers for the unattended updates and for WP-CLI,
			// where no admin file is loaded and the auto-update option is written all
			// the same.
			if (!is_admin() && (wp_doing_cron() || (defined('WP_CLI') && WP_CLI))) {
				require_once(WPEMATICO_PLUGIN_DIR . 'includes/lib/update_class.php');
			}
			require_once(WPEMATICO_PLUGIN_DIR . 'includes/cron_functions.php');
			require_once(WPEMATICO_PLUGIN_DIR . 'includes/compatibilities.php');
			require_once(WPEMATICO_PLUGIN_DIR . 'includes/wpematico_functions.php');
			require_once(WPEMATICO_PLUGIN_DIR . 'wpematico_class.php');
			require_once(WPEMATICO_PLUGIN_DIR . 'includes/xml-importer.php');
			require_once(WPEMATICO_PLUGIN_DIR . 'includes/cron.php');
			// Campaign types: after wpematico_class.php, which owns the option key the
			// loader reads, and outside the is_admin() block — a campaign type has to
			// exist when the campaign runs in cron too.
			require_once(WPEMATICO_PLUGIN_DIR . 'includes/campaign-types/loader.php');
			WPeMatico_Campaign_Types::load();
		}

		private function hooks() {

			// Addons too old for this core keep running otherwise, and fatal in the
			// middle of a fetch. Runs late on both hooks because addons register their
			// callbacks either as their file loads or on init, and it is idempotent.
			add_action('plugins_loaded', array('WPeMatico_functions', 'disable_outdated_addons_features'), 999);
			add_action('init', array('WPeMatico_functions', 'disable_outdated_addons_features'), 999);

			foreach (array('activated_plugin', 'deactivated_plugin', 'upgrader_process_complete') as $lifecycle) {
				add_action($lifecycle, array('WPeMatico_functions', 'flush_outdated_addons_cache'));
			}

			add_action('init', array('WPeMatico', 'init'));
			add_action('init', array(self::$instance, 'include_init'));

			add_action('init', array(self::$instance, 'load_textdomain'));

			add_action('the_permalink', array('WPeMatico', 'wpematico_permalink'));

			add_filter('post_link', array('WPeMatico', 'wpematico_permalink'));

			add_filter('get_canonical_url', array('WPeMatico_functions', 'wpematico_set_canonical'), 999999, 2);
		}

		public function include_init() {
			require_once(WPEMATICO_PLUGIN_DIR . "includes/module/module.php");
			require_once(WPEMATICO_PLUGIN_DIR . "includes/module/manager.php");
			require_once(WPEMATICO_PLUGIN_DIR . "includes/module/actions.php");
			require_once(WPEMATICO_PLUGIN_DIR . "includes/module/dashboard.php");
		}

		/**

		 * setup_cron 

		 *

		 * @access      public

		 * @since       1.0.0

		 * @return      void

		 */
		public function setup_cron() {
			$options = get_option( WPeMatico::OPTION_KEY, array() );

			if ( !empty( $options['disablewpcron'] ) && !defined( 'DISABLE_WP_CRON' ) ) {
				define( 'DISABLE_WP_CRON', true );
			}
			if ( !empty( $options['enable_alternate_wp_cron'] ) && !defined( 'ALTERNATE_WP_CRON' ) ) {
				define( 'ALTERNATE_WP_CRON', true );
			}
			if ( !empty( $options['dontruncron'] ) ) {
				wp_clear_scheduled_hook( 'wpematico_cron' );
				return;
			}

			add_filter( 'cron_schedules', 'wpematico_intervals' );
			add_action( 'wpematico_cron', 'wpem_cron_callback' );

			if ( ! wp_next_scheduled( 'wpematico_cron' ) ) {
				wp_schedule_event( time(), 'wpematico_int', 'wpematico_cron' );
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

