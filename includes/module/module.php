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

class Module {
    private $id;
    private $args;

    public function __construct($id, $args) {
        $this->id = $id;
        $this->args = $args;
    }

    public function get($key, $default = '') {
        return isset($this->args[$key]) ? $this->args[$key] : $default;
    }

    public function has($key) {
        return isset($this->args[$key]);
    }

    public function get_id() {
        return $this->id;
    }

    public function get_icon() {
        return $this->get('icon', 'admin-plugins');
    }

    public function is_disabled() {
        return (bool) $this->get('disabled', false);
    }

    public function is_active() {
        if ((bool) $this->get('forced', false)) return true;
        if ($this->is_disabled()) return false;
        // ★ A module whose state lives somewhere else has already answered:
        // `forced` carries that answer, resolved from the plugin or the option it
        // stands for. Falling through to wpematico_active_modules is what let a
        // card keep reading On after its setting was turned off anywhere else —
        // in Settings, in the addon's own screen, or from the Plugins list.
        if ($this->has_own_state()) return false;
        $active = get_option('wpematico_active_modules', []);
        return in_array($this->id, (array) $active, true);
    }

    /**
     * Whether this module's state is stored outside wpematico_active_modules.
     *
     * Exactly one source of truth per module, which is what keeps the card and
     * the thing it switches from disagreeing:
     *   plugin_name/plugin_folder -> the plugin being active
     *   option                    -> a key of WPeMatico_Options
     *   pro_option                -> a key of the Professional options
     *   none of them              -> wpematico_active_modules, the real toggle
     */
    public function has_own_state() {
        return $this->is_plugin_module()
            || '' !== (string) $this->get('option', '')
            || '' !== (string) $this->get('pro_option', '');
    }

    /** Whether this module's state is the state of a plugin. */
    public function is_plugin_module() {
        return '' !== (string) $this->get('plugin_name', '') || '' !== (string) $this->get('plugin_folder', '');
    }

    public function is_pro() {
        return (bool) $this->get('pro', false);
    }

    public function get_pro_link() {
        return $this->get('pro_link', '#');
    }

    public function get_type() {
        return $this->get('type', 'standard');
    }

    /**
     * A module may belong to more than one category, so it answers to more than one
     * of the filters at the top of the dashboard. 'category' takes a slug or an
     * array of slugs; the first one is the primary and is the one the card shows.
     */
    public function get_categories() {
        $categories = $this->get('category', 'core');
        if (!is_array($categories)) {
            $categories = array($categories);
        }
        $categories = array_values(array_unique(array_filter(array_map('trim', array_map('strval', $categories)), 'strlen')));

        return $categories ? $categories : array('core');
    }

    public function get_category() {
        $categories = $this->get_categories();

        return reset($categories);
    }

    public function can_display() {
        // Advanced Mode is on for everyone until 3.0 brings the Easy Mode, which
        // is what its own card says. The type is declared now so the modules are
        // already tagged when that lands; it hides nothing today. Hiding on an
        // option that is never written is what kept two modules off the screen.
        return true;
    }

    public function execute_callback($enable) {
        if ($callback = $this->get('callback')) {
            if (is_callable($callback)) {
                // The module goes along so shared callbacks know what they act on.
                call_user_func($callback, $enable, $this);
            }
        }
    }
}