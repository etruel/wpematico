<?php
namespace WPeMatico\Module;
defined('ABSPATH') || exit;
class Dashboard
{
	public static function hooks()
	{
		// Registering the module definitions is just one filter.
		new \WPeMatico\Module\Actions();
		// Building them is not: it walks every installed plugin and creates ~35
		// objects. That was happening on every admin request, for a screen the
		// user opens once in a while, so it now waits until something asks.
		if (self::needs_manager()) {
			\WPeMatico\Module\Manager::instance();
		}
	}
	/**
	 * Whether this request is the modules screen or one of its endpoints.
	 */
	private static function needs_manager()
	{
		if (wp_doing_ajax()) {
			$action = isset($_REQUEST['action']) ? sanitize_text_field(wp_unslash($_REQUEST['action'])) : '';
			return in_array($action, ['wpematico_toggle_module', 'wpematico_install_plugin'], true);
		}
		return isset($_GET['page']) && 'wpematico_dashboard' === $_GET['page'];
	}
	private static function dashboard_tabs()
	{
		return apply_filters('wpematico_dashboard_tabs', [
			'dashboard'	 => ['title' => __('Modules', 'wpematico'), 'url' => '#'],
			'settings'	 => ['title' => __('Settings', 'wpematico'), 'url' => admin_url('admin.php?page=wpematico_settings')],
			'debug_info' => ['title' => __('System Status', 'wpematico'), 'url' => admin_url('admin.php?page=wpematico_tools&tab=debug_info')]
		]);
	}
	public static function dashboard_styles()
	{
		wp_enqueue_style(
			'wpematico-dashboard-css',
			WPEMATICO_PLUGIN_URL . 'assets/css/settings_dashboard.css',
			['wffmodalpop'],
			WPEMATICO_VERSION
		);
		wp_enqueue_script(
			'wpematico-modules-js',
			WPEMATICO_PLUGIN_URL . 'assets/js/modules.js',
			['jquery'],
			WPEMATICO_VERSION,
			true
		);
		wp_localize_script('wpematico-modules-js', 'wpematico_module', [
			'nonce'	   => wp_create_nonce('wpematico_module_nonce'),
			'ajax_url' => admin_url('admin-ajax.php'),
			'i18n'	   => [
				'installed'		   => __('Installed', 'wpematico'),
				'install_error'	   => __('Installation error', 'wpematico'),
				'install_failed'   => __('Installation failed.', 'wpematico'),
				'connection_error' => __('Connection error', 'wpematico'),
				'module_error'	   => __('Module error: ', 'wpematico'),
				'confirm_install'  => __('Do you want to install this plugin?', 'wpematico'),
				'confirm_activate' => __('Do you want to activate the plugin after installation?', 'wpematico'),
				'install'		   => __('Install', 'wpematico'),
				'installing'	   => __('Installing…', 'wpematico'),
				'activate'		   => __('Activate', 'wpematico'),
				'no'			   => __('No', 'wpematico'),
				'yes'			   => __('Yes', 'wpematico'),
				'ok'			   => __('OK', 'wpematico'),
				/* translators: %s: plugin name. */
				'confirm_off'	   => __('This will deactivate the plugin %s. Continue?', 'wpematico'),
			]
		]);
	}
	public static function render_dashboard_page()
	{
		try {
			$manager = \WPeMatico\Module\Manager::instance();
			$data = apply_filters('wpematico_dashboard_data', [
				'version'	  => WPEMATICO_VERSION,
				'manager'	  => $manager,
				'current_tab' => 'dashboard',
			]);
			self::render_modules_screen($data);
		} catch (\Exception $e) {
			wp_die(
				'<h1>' . esc_html__('Error', 'wpematico') . '</h1>' .
					'<p>' . esc_html($e->getMessage()) . '</p>',
				500
			);
		}
	}
	private static function render_modules_screen($data)
	{
		if (!current_user_can('manage_options')) {
			wp_die(
				'<h1>' . esc_html__('Access denied', 'wpematico') . '</h1>' .
					'<p>' . esc_html__('You do not have permission to access this page.', 'wpematico') . '</p>',
				403
			);
		}
		extract($data);
		$tabs	 = self::dashboard_tabs();
		?>
		<div class="wrap">
			<header class="wpematico-dashboard-header">
				<h1 class="wpematico-dashboard-title">
					<?php echo esc_html__('Dashboard', 'wpematico') . '/' . $tabs[$current_tab]['title']; ?>
					<?php do_action('wpematico_after_dashboard_title'); ?>
				</h1>
				<?php if ($notices = apply_filters('wpematico_dashboard_notices', [])) : ?>
					<div class="wpematico-notices">
						<?php foreach ($notices as $notice) : ?>
							<div class="notice notice-<?php echo esc_attr($notice['type']); ?>">
								<p><?php echo esc_html($notice['message']); ?></p>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</header>
			<h2 class="nav-tab-wrapper">
				<?php foreach ($tabs as $tab_id => $tab) : ?>
					<a href="<?php echo esc_url($tab['url']); ?>" class="nav-tab <?php echo ($current_tab === $tab_id) ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html($tab['title']); ?>
					</a>
				<?php endforeach; ?>
			</h2>
			<div class="wpematico-dashboard-container">
				<main class="wpematico-dashboard-content">
					<section class="wpematico-dashboard-widgets">
						<?php do_action('wpematico_dashboard_before_widgets'); ?>
						<?php $manager->display_form(); ?>
						<?php do_action('wpematico_dashboard_after_widgets'); ?>
					</section>
				</main>
				<footer class="wpematico-dashboard-footer">
					<p class="wpematico-version">
						<?php printf(esc_html__('WPeMatico %s', 'wpematico'), esc_html($version)); ?>
					</p>
					<?php do_action('wpematico_dashboard_footer'); ?>
				</footer>
			</div>
		</div>
		<!-- Modal -->
		<div id="customModal" style="display:none;">
			<div class="modal-overlay"></div>
			<div class="modal-content">
				<p id="modalMessage"></p>
				<div class="modal-actions">
					<button id="modalYes" class="button button-primary"></button>
					<button id="modalNo" class="button"></button>
				</div>
			</div>
		</div>
<?php
	}
}
Dashboard::hooks();
