<?php

/**
 * The Module
 *
 * @since      3.0
 * @package    WPeMatico
 * @subpackage WPeMatico\Module
 */

namespace WPeMatico\Module;

defined('ABSPATH') || exit;

class Manager
{

	private $modules	= [];
	private $categories = [];

	/** @var Manager */
	private static $instance = null;

	/**
	 * One manager per request. Building the module set walks every installed
	 * plugin, and the dashboard used to create a second manager while rendering,
	 * which did all of that twice and printed the checkout modal twice with
	 * duplicate ids.
	 */
	public static function instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct()
	{
		$this->categories = [
			'core'		   => __('Core Feature', 'wpematico'),
			'external'	   => __('External Tools', 'wpematico'),
			'parser'	   => __('Content Parsers', 'wpematico'),
			'images'	   => __('Images', 'wpematico'),
			'seo'		   => __('SEO', 'wpematico'),
			'third_party'  => __('Third party', 'wpematico'),
			'campaign'	   => __('Campaign Type', 'wpematico'),
			'wffimporters' => __('Importers', 'wpematico'),
			'advanced'	   => __('Advanced', 'wpematico'),
		];
		if (null === self::$instance) {
			self::$instance = $this;
		}
		$this->setup_modules();
		add_action('wp_ajax_wpematico_toggle_module', [$this, 'ajax_toggle_module']);
		add_action('wp_ajax_wpematico_install_plugin', [$this, 'ajax_install_plugin']);

		add_action('admin_footer', [$this, 'admin_footer']);
	}

	public function setup_modules()
	{
		$modules = apply_filters('wpematico_modules', []);

		foreach ($modules as $id => $args) {
			$this->add_module($id, $args);
		}
	}

	public function add_module($id, $args = [])
	{
		$this->modules[$id] = new Module($id, $args);
	}

	public function display_form()
	{
		if (!current_user_can('manage_options'))
			return;
		// Drop the modules Advanced Mode hides.
		$displayable_modules = array_filter($this->modules, function ($module) {
			return $module->can_display();
		});
		// Alphabetical order.
		usort($displayable_modules, function ($a, $b) {
			return strcmp($a->get('title'), $b->get('title'));
		});

		// The "Advanced Mode" module always comes first.
		$advanced_mode_module = new Module('advanced_mode', [
			'title'	   => __('Advanced Mode', 'wpematico'),
			'desc'	   => __('Unlock advanced features and show all the settings and options inside campaigns.', 'wpematico'),
			'icon'	   => 'admin-tools',
			'type'	   => 'standard',
			'category' => 'core',
			'forced'		=> true,
			'disabled'		=> true,
			'notice' => __('Used by default until version 3.0 which will include Easy Mode.😉', 'wpematico'),
			'settings' => admin_url('admin.php?page=wpematico_settings&section=advanced_actions')
		]);

		// Prepend it as a Module object.
		array_unshift($displayable_modules, $advanced_mode_module);
?>
		<div class="wpematico-modules-grid">
			<h2 class="modules-title"><?php esc_html_e('Available Modules', 'wpematico'); ?></h2>
			<label class="filterbymodule active module-category" id="all"><?php echo esc_html__("Show All", 'wpematico'); ?></label>
			<?php foreach ($this->categories as $cat => $name) : ?>
				<label class="filterbymodule module-category" id="<?php echo esc_html($cat); ?>"><?php echo esc_html($name); ?></label>
			<?php endforeach; ?>
			<p></p>
			<div class="modules-container">
				<?php $this->cta(); ?>
				<?php foreach ($displayable_modules as $module) : ?>
					<?php $this->render_module_card($module); ?>
				<?php endforeach; ?>
			</div>
		</div>
	<?php
	}

	private function render_module_card($module)
	{
		$is_active	   = $module->is_active();
		$is_disabled   = $module->is_disabled();
		$is_pro		   = $module->is_pro();
		$pro_link	   = $module->get_pro_link();
		// An addon you already have says so, whether the module is the plugin
		// itself or one of the features it unlocks.
		$badge		   = $module->get('badge') ?: ($module->get('installed') ? __('Installed', 'wpematico') : ($is_pro ? 'PRO' : ''));
		// A card carries one class per category it belongs to, which is all the
		// filter at the top needs: it selects .wpematico-module-card.<slug>.
		$cats		   = $module->get_categories();
		$card_classes  = array();
		$category_name = array();
		foreach ($cats as $slug) {
			$card_classes[]	 = sanitize_html_class($slug);
			$category_name[] = $this->categories[$slug] ?? $slug;
		}
		$cat		   = implode(' ', array_filter($card_classes));
		$category_name = implode(' · ', $category_name);

		// Tooltip per badge type.
		$badge_tooltips = [
			'FREE'	 => __('Free plugin available in WordPress.org repository', 'wpematico'),
			'PRO'	 => __('Professional addon is available at etruel.com', 'wpematico'),
			'NEW'	 => __('New feature recently added', 'wpematico'),
			'BETA'	 => __('Experimental feature - use with caution', 'wpematico'),
			// Badges personalizados de módulos
			'AddOn' => __('Additional functionality available by AddOn at etruel.com', 'wpematico'),
			__('Installed', 'wpematico') => __('The plugin is installed on this site: use the switch to turn it on or off', 'wpematico'),
		];
		$tooltip_text	= $badge_tooltips[$badge] ?? '';
		// "Installed" means two different things: the plugin itself is here, or
		// the addon that provides this feature is.
		if ($badge === __('Installed', 'wpematico') && !$module->is_plugin_module()) {
			$tooltip_text = __('Part of an addon you already have: the switch turns the feature on or off', 'wpematico');
		}
	?>
		<div class="wpematico-module-card <?php echo $is_active ? 'active' : ''; ?> <?php echo esc_attr($cat); ?>">
			<div class="module-header">
				<div class="module-icon dashicons dashicons-<?php echo esc_attr($module->get_icon()); ?>"></div>
				<div class="module-info">
					<h3><?php echo esc_html($module->get('title')); ?></h3>
					<span class="module-category"><?php echo esc_html($category_name); ?></span>
				</div>

				<div class="module-badges">
					<?php if (!empty($badge)) : ?>
						<span class="module-badge badge-<?php echo sanitize_html_class(strtolower($badge)); ?>"
							data-tooltip="<?php echo esc_attr($tooltip_text); ?>"
							aria-label="<?php echo esc_attr($tooltip_text); ?>">
							<?php echo esc_html($badge); ?>
						</span>
					<?php endif; ?>
				</div>
			</div>

			<div class="module-description">
				<p><?php echo esc_html($module->get('desc')); ?></p>
			</div>

			<div class="module-footer">
				<div class="module-actions">
					<?php if ($settings_url = $module->get('settings')) : ?>
						<a href="<?php echo esc_url($settings_url); ?>" class="button module-settings">
							<?php esc_html_e('Settings', 'wpematico'); ?>
						</a>
					<?php endif; ?>

					<?php if ($wp_link = $module->get('wp_link')) : ?>
						<a href="#" data-url="<?php echo esc_url($wp_link); ?>" class="button pluginstall button-install">
							<span class="dashicons dashicons-download" aria-hidden="true"></span>
							<?php esc_html_e('Install', 'wpematico'); ?>
						</a>
					<?php endif; ?>

					<?php // The plugin this module stands on, when it is the one missing. The
					// empty data-module keeps the install from running our module's callback. ?>
					<?php if ($require_url = $module->get('require_url')) : ?>
						<?php if ($module->get('require_install')) : ?>
							<a href="#" data-url="<?php echo esc_url($require_url); ?>" data-module=""
								class="button pluginstall button-install module-requires">
								<span class="dashicons dashicons-download" aria-hidden="true"></span>
								<?php echo esc_html($module->get('require_label')); ?>
							</a>
						<?php else : ?>
							<a href="<?php echo esc_url($require_url); ?>" class="button button-install module-requires">
								<span class="dashicons dashicons-admin-plugins" aria-hidden="true"></span>
								<?php echo esc_html($module->get('require_label')); ?>
							</a>
						<?php endif; ?>
					<?php endif; ?>

					<?php // Nothing to sell to somebody who already owns it: a card whose addon
					// is on this site but stopped offers the Activate button above instead. ?>
					<?php if ($is_pro && $is_disabled && !$module->get('installed')) : ?>
						<a href="<?php echo esc_url($pro_link); ?>" class="button button-pro" target="_blank">
							<?php esc_html_e('GET IT', 'wpematico'); ?>
						</a>
						<a href="<?php echo esc_url($pro_link); ?>" target="_blank" class="wpematico-switch">
							<span class="slider"></span>
						</a>
					<?php else : ?>
						<label class="wpematico-switch" aria-label="<?php
																	echo esc_attr(sprintf(
																		__('Toggle %s module', 'wpematico'),
																		$module->get('title')
																	));
																	?>">
							<input type="checkbox" <?php checked($is_active); ?> <?php disabled($is_disabled); ?>
								data-module="<?php echo esc_attr($module->get_id()); ?>"
								<?php if ($module->is_plugin_module()) : ?>
									data-plugin="<?php echo esc_attr($module->get('plugin_name')); ?>"
								<?php endif; ?>>
							<?php // disabled() prints the HTML attribute, so the class never landed. ?>
							<span class="slider<?php echo $is_disabled ? ' disabled' : ''; ?>"></span>
						</label>
					<?php endif; ?>
				</div>

				<?php if ($is_disabled && $module->has('disabled_text')) : ?>
					<div class="module-disabled-notice">
						<?php echo esc_html($module->get('disabled_text')); ?>
					</div>
				<?php else: ?>
					<?php //if (!$is_disabled && $module->has('notice')) : 
					?>
					<?php if ($module->has('notice')) : ?>
						<div class="module-notice">
							<?php echo esc_html($module->get('notice')); ?>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
	<?php
	}

	public function ajax_toggle_module()
	{
		check_ajax_referer('wpematico_module_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('Permission denied', 'wpematico'));
		}

		$module_id = isset($_POST['module']) ? sanitize_text_field($_POST['module']) : '';
		$enable	   = isset($_POST['enable']) && $_POST['enable'] === '1';

		if (empty($module_id) || !isset($this->modules[$module_id])) {
			wp_send_json_error(esc_html__('Invalid module', 'wpematico'));
		}

		$module = $this->modules[$module_id];

		// The form disables these, but the endpoint has to say no on its own.
		if ($module->is_disabled()) {
			wp_send_json_error(esc_html__('This module cannot be changed here.', 'wpematico'));
		}

		$module->execute_callback($enable);

		// ★ Only a module with nowhere else to keep its state is recorded here. One
		// backed by a plugin or by an option already has a source of truth, and the
		// callback above just wrote it; storing a second copy is what let the card
		// and the setting drift apart the moment the setting changed anywhere else.
		if (!$module->has_own_state()) {
			$active_modules = (array) get_option('wpematico_active_modules', []);
			if ($enable) {
				if (!in_array($module_id, $active_modules, true)) {
					$active_modules[] = $module_id;
				}
			} else {
				$active_modules = array_diff($active_modules, [$module_id]);
			}
			update_option('wpematico_active_modules', array_values($active_modules));
		}

		wp_send_json_success(['message' => __('Module updated', 'wpematico')]);
	}

	// add_action('wp_ajax_wpematico_install_plugin', [$this, 'ajax_install_plugin']);

	public function ajax_install_plugin() {
	check_ajax_referer('wpematico_module_nonce', 'nonce');

	if (! current_user_can('install_plugins')) {
		wp_send_json_error(['message' => __('You do not have permission to install plugins', 'wpematico')]);
	}

	$plugin_url     = isset($_POST['plugin_url']) ? esc_url_raw(wp_unslash($_POST['plugin_url'])) : '';
	$activate_after = ! empty($_POST['activate_after']); // new param: true = activate, false = skip
	$network        = ! empty($_POST['network_wide']) && is_multisite() && current_user_can('manage_network_plugins');
	$module_id = isset($_POST['module']) ? sanitize_text_field($_POST['module']) : '';
	// Answering for a module that does not exist used to fatal further down.
	$module    = isset($this->modules[$module_id]) ? $this->modules[$module_id] : null;

	if (empty($plugin_url)) {
		wp_send_json_error(['message' => __('Invalid plugin URL', 'wpematico')]);
	}

	// Only the downloads this install offers, never an arbitrary URL from a POST.
	if (0 !== strpos($plugin_url, 'https://downloads.wordpress.org/plugin/')) {
		wp_send_json_error(['message' => __('Only plugins from the WordPress.org repository can be installed here.', 'wpematico')]);
	}

	// Core files needed here.
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

	// Already installed exactly where we expect it.
	$expected_plugin_file = '';
	if (method_exists($this, 'get_plugin_slug_from_url')) {
		$expected_plugin_file = (string) $this->get_plugin_slug_from_url($plugin_url); // e.g. "folder/file.php"
		if ($expected_plugin_file && file_exists(trailingslashit(WP_PLUGIN_DIR) . $expected_plugin_file)) {
			if ($activate_after) {
				$activate = activate_plugin($expected_plugin_file, '', $network);
				if (is_wp_error($activate)) {
					wp_send_json_error(['message' => $activate->get_error_message(), 'plugin_file' => $expected_plugin_file]);
				}
				wp_send_json_success([
					'message'     => __('Plugin activated successfully.', 'wpematico'),
					'plugin_file' => $expected_plugin_file,
					'already'     => true,
				]);
			}
			// Installed, but skipping activation
			wp_send_json_success([
				'message'     => __('Plugin already installed. Activation skipped.', 'wpematico'),
				'plugin_file' => $expected_plugin_file,
				'already'     => true,
			]);
		}
	}

	// Init the Filesystem API — credentials cannot be asked for over AJAX.
	if (! WP_Filesystem()) {
		wp_send_json_error(['message' => __('Could not initialize filesystem API. Check file permissions (wp-content/plugins writable).', 'wpematico')]);
	}

	// Download the ZIP first, to check it exists and is reachable.
	$tmp_file = download_url($plugin_url);
	if (is_wp_error($tmp_file)) {
		wp_send_json_error(['message' => $tmp_file->get_error_message()]);
	}

	// Install from the downloaded local file.
	$skin     = class_exists('\WP_Ajax_Upgrader_Skin') ? new \WP_Ajax_Upgrader_Skin() : new \Automatic_Upgrader_Skin();
	$upgrader = new \Plugin_Upgrader($skin);

	$installed = $upgrader->install($tmp_file);
	// Clean up the temporary file.
	if (file_exists($tmp_file)) {
		@unlink($tmp_file);
	}

	if (is_wp_error($installed)) {
		wp_send_json_error([
			'message' => $installed->get_error_message(),
			'debug'   => isset($upgrader->result) ? $upgrader->result : null,
		]);
	}

	if (empty($installed) || empty($upgrader->result) || empty($upgrader->result['destination_name'])) {
		wp_send_json_error([
			'message' => __('Plugin installation failed: invalid package or could not extract files.', 'wpematico'),
			'debug'   => isset($upgrader->result) ? $upgrader->result : null,
		]);
	}

	$dest_name = $upgrader->result['destination_name']; // ej: "mi-plugin"
	$dest_dir  = trailingslashit(WP_PLUGIN_DIR) . $dest_name;
	if (! is_dir($dest_dir)) {
		wp_send_json_error([
			'message' => __('Plugin installation failed: destination directory missing.', 'wpematico'),
			'debug'   => $upgrader->result,
		]);
	}

	// Resolve the main plugin file.
	$plugin_file = '';
	if ($expected_plugin_file && file_exists(trailingslashit(WP_PLUGIN_DIR) . $expected_plugin_file)) {
		$plugin_file = $expected_plugin_file;
	} else {
		$plugin_file = $this->detect_main_plugin_file_from_folder($dest_name);
	}

	if (empty($plugin_file) || ! file_exists(trailingslashit(WP_PLUGIN_DIR) . $plugin_file)) {
		wp_send_json_error([
			'message' => __('Plugin installed but main file was not found.', 'wpematico'),
			'debug'   => $upgrader->result,
		]);
	}

	// Activate only when activate_after is true.
	if ($activate_after) {
		$activate = activate_plugin($plugin_file, '', $network);
		if (is_wp_error($activate)) {
			wp_send_json_error([
				'message'     => $activate->get_error_message(),
				'plugin_file' => $plugin_file,
				'debug'       => $upgrader->result,
			]);
		}

		if ($module) {
			$module->execute_callback(true);
		}

		wp_send_json_success([
			'message'      => __('Plugin installed and activated successfully.', 'wpematico'),
			'plugin_file'  => $plugin_file,
			'installed_in' => $dest_name,
			'network_wide' => (bool) $network,
		]);
	}

	// Installed but not activated.
	wp_send_json_success([
		'message'      => __('Plugin installed successfully. Activation skipped.', 'wpematico'),
		'plugin_file'  => $plugin_file,
		'installed_in' => $dest_name,
		'network_wide' => (bool) $network,
	]);
}


	/**
	 * Finds a plugin's main file inside an installed folder without calling get_plugins().
	 * Looks for a "Plugin Name" header in the .php files of the root and one level down.
	 *
	 * @param string $folder_name Folder name inside wp-content/plugins/
	 * @return string Relative path "folder/file.php", or '' when not found.
	 */
	protected function detect_main_plugin_file_from_folder($folder_name)
	{
		$base = trailingslashit(WP_PLUGIN_DIR) . $folder_name;
		if (! is_dir($base)) {
			return '';
		}

		// 1) Root of the folder.
		$php_files = glob($base . '/*.php');
		if (is_array($php_files)) {
			foreach ($php_files as $file) {
				$headers = get_file_data($file, ['Name' => 'Plugin Name'], 'plugin');
				if (! empty($headers['Name'])) {
					return trailingslashit($folder_name) . basename($file);
				}
			}
		}

		// 2) One level down — many ZIPs nest the plugin inside a subfolder.
		$subdirs = glob($base . '/*', GLOB_ONLYDIR);
		if (is_array($subdirs)) {
			foreach ($subdirs as $dir) {
				$php_files = glob(trailingslashit($dir) . '*.php');
				if (is_array($php_files)) {
					foreach ($php_files as $file) {
						$headers = get_file_data($file, ['Name' => 'Plugin Name'], 'plugin');
						if (! empty($headers['Name'])) {
							return $folder_name . '/' . basename($dir) . '/' . basename($file);
						}
					}
				}
			}
		}

		// 3) Last resort: a .php file named after the folder.
		$guess = $base . '/' . basename($folder_name) . '.php';
		if (file_exists($guess)) {
			return trailingslashit($folder_name) . basename($guess);
		}

		return '';
	}

	private function get_plugin_slug_from_url($url)
	{
		// Slug out of the usual WordPress.org URLs, the ZIP one included
		// (downloads.wordpress.org/plugin/slug.zip), which is what the button sends.
		if (preg_match('#wordpress\.org/plugins?/([^/?]+)#', $url, $matches)) {
			$slug = preg_replace('/\.zip$/', '', $matches[1]);
			return $slug . '/' . $slug . '.php';
		}
		return false;
	}

	/**
	 * Show CTA if WPeMatico Professional is not active.
	 *
	 * @return void
	 */
	private function cta()
	{
		// Nothing to upsell to someone who already bought it.
		if (defined('WPEMATICOPRO_VERSION')) {
			return;
		}
	?>
		<div class="wpematico-module-card essentials-box">
			<div class="module-icon dashicons dashicons-editor-expand"></div>

			<a href="JavaScript:void(0);" class="wpe-site-checkout-link" data-url="https://wpematico.com/site-checkout/">
				<header>
					<h3><?php esc_html_e('Expand Your Experience!', 'wpematico'); ?></h3>
					<ul>
						<li><?php esc_html_e('Scalable memberships', 'wpematico'); ?></li>
						<li><?php esc_html_e('From 1 to unlimited websites', 'wpematico'); ?></li>
						<li><?php esc_html_e('Expensive? Try Monthly 😏', 'wpematico'); ?></li>
						<li><?php esc_html_e('Automatic Updates', 'wpematico'); ?></li>
						<li><?php esc_html_e('Help & Customer Support', 'wpematico'); ?></li>
					</ul>
				</header>
				<div class="module-description">
				</div>
				<div class="module-footer">
					<div class="amodule-actions">
						<div class="status wp-clearfix">
							<button class="button button-secondary"><?php esc_html_e('Buy', 'wpematico'); ?></button>
						</div>
					</div>
				</div>
			</a>
		</div>
		<?php
	}

	public function admin_footer()
	{
		global $pagenow, $page_hook;
		if ($pagenow == 'admin.php' && strpos($page_hook, 'wpematico_dashboard') !== false) {
		?>
			<div id="wpe-site-checkout" class="wpe_modal_log-box fade" style="display:none;">
				<div id="wpe-site-checkout-content" class="wpe_modal_log-body">
					<a href="JavaScript:void(0);" class="wpe_modal_log-close" onclick="jQuery('#wpe-site-checkout').fadeToggle().removeClass('active'); jQuery('body').removeClass('wpe_modal_log-is-active');">
						<span class="dashicons dashicons-no-alt"></span>
					</a>
					<div class="wpe-iframe-container"></div>
				</div>
			</div>

			<script type="text/javascript">
				jQuery(document).ready(function($) {
					$('.wpe-site-checkout-link').on('click', function(e) {
						e.preventDefault();

						$('#wpe-site-checkout').fadeIn().addClass('active');
						$('body').addClass('wpe_modal_log-is-active');

						// Build the iframe.
						$('.wpe-iframe-container').html(
							$('<iframe>', {
								'title': 'OnSite Checkout',
								'width': '100%',
								'height': '100%',
								'src': 'https://wpematico.com/site-checkout/',
								'frameborder': '0',
								'allowfullscreen': ''
							})
						);
					});

					// Click outside modal content closes it
					$(document).on('click', '#wpe-site-checkout', function(e) {
						if (e.target === this) {
							$('#wpe-site-checkout').fadeOut().removeClass('active');
							$('body').removeClass('wpe_modal_log-is-active');
						}
					});

					// Close button
					$(document).on('click', '.wpe_modal_log-close', function() {
						$('#wpe-site-checkout').fadeOut().removeClass('active');
						$('body').removeClass('wpe_modal_log-is-active');
					});
				});
			</script>
<?php
		}
	}
}
