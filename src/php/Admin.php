<?php
/**
 * Admin class for handling admin functionality of the plugin.
 * 
 * @package AIALVI_Vue_Plugin
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class AIALVI_Vue_Plugin_Admin
{

    /**
     * Plugin text domain for translations
     */
    private $text_domain;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->text_domain = defined('AIALVI_PLUGIN_TEXT_DOMAIN') ? AIALVI_PLUGIN_TEXT_DOMAIN : 'aialvi-vue-plugin';

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aialvi_vue_save_settings', array($this, 'handle_save_settings'));
        add_action('wp_ajax_aialvi_vue_get_settings', array($this, 'handle_get_settings'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    /**
     * Add admin menu with proper capability checking
     */
    public function add_admin_menu()
    {
        add_menu_page(
            esc_html__('Aminul\'s Plugin Settings', $this->text_domain),
            esc_html__('Aminul\'s Plugin', $this->text_domain),
            'manage_options',
            'aialvi-vue-plugin',
            array($this, 'create_admin_page'),
            'dashicons-admin-generic',
            6
        );
    }

    /**
     * Create admin page with proper security measures
     */
    public function create_admin_page()
    {
        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', $this->text_domain));
        }

        // Add nonce for CSRF protection
        $nonce = wp_create_nonce('aialvi_vue_admin_nonce');
        ?>
        <div class="wrap">
            <div id="aialvi-vue-admin-app" data-nonce="<?php echo esc_attr($nonce); ?>">
                <div class="loading"><?php echo esc_html__('Loading Vue.js Admin Panel...', $this->text_domain); ?></div>
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue scripts and styles with proper security measures
     *
     * @param string $hook_suffix The current admin page hook suffix.
     */
    public function enqueue_scripts($hook_suffix)
    {
        // Only load on our admin page
        if ('toplevel_page_aialvi-vue-plugin' !== $hook_suffix) {
            return;
        }

        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Add debugging with proper escaping
        error_log('Enqueuing scripts for: ' . sanitize_text_field($hook_suffix));

        // Plugin directory URL - use constants for security
        $plugin_url = defined('AIALVI_PLUGIN_URL') ? AIALVI_PLUGIN_URL : plugin_dir_url(dirname(dirname(__FILE__)));

        // Check if built files exist, otherwise use development files
        $plugin_path = defined('AIALVI_PLUGIN_PATH') ? AIALVI_PLUGIN_PATH : plugin_dir_path(dirname(dirname(__FILE__)));
        $admin_js_file = $plugin_path . 'dist/admin.bundle.js';
        $admin_css_file = $plugin_path . 'dist/admin.css';

        error_log('Checking for JS file: ' . sanitize_text_field($admin_js_file));
        error_log('JS file exists: ' . (file_exists($admin_js_file) ? 'yes' : 'no'));

        if (file_exists($admin_js_file)) {
            // Production build files
            wp_enqueue_script(
                'aialvi-vue-admin',
                esc_url($plugin_url . 'dist/admin.bundle.js'),
                array(),
                filemtime($admin_js_file),
                true
            );

            error_log('Enqueued JS: ' . esc_url($plugin_url . 'dist/admin.bundle.js'));

            if (file_exists($admin_css_file)) {
                wp_enqueue_style(
                    'aialvi-vue-admin',
                    esc_url($plugin_url . 'dist/admin.css'),
                    array(),
                    filemtime($admin_css_file)
                );

                error_log('Enqueued CSS: ' . esc_url($plugin_url . 'dist/admin.css'));
            }

            // Pass data to JavaScript with proper nonce
            wp_localize_script('aialvi-vue-admin', 'aialviVuePlugin', array(
                'ajax_url' => esc_url(admin_url('admin-ajax.php')),
                'nonce' => wp_create_nonce('aialvi_vue_nonce'),
                'rest_url' => esc_url(rest_url('aialvi-vue/v1/')),
                'plugin_url' => esc_url($plugin_url),
            ));
        } else {
            error_log('Using development mode');
            // Development mode - need to run vite dev server
            wp_enqueue_script(
                'aialvi-vue-admin-dev',
                'http://localhost:3000/src/admin/main.js',
                array(),
                time(),
                true
            );

            // Pass data to JavaScript for dev mode with proper nonce
            wp_localize_script('aialvi-vue-admin-dev', 'aialviVuePlugin', array(
                'ajax_url' => esc_url(admin_url('admin-ajax.php')),
                'nonce' => wp_create_nonce('aialvi_vue_nonce'),
                'rest_url' => esc_url(rest_url('aialvi-vue/v1/')),
                'plugin_url' => esc_url($plugin_url),
            ));
        }
    }

    /**
     * Handle AJAX request to save settings
     */
    public function handle_save_settings()
    {
        // Verify nonce for CSRF protection
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'aialvi_vue_nonce')) {
            wp_die(esc_html__('Security check failed', $this->text_domain));
        }

        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions', $this->text_domain));
        }

        // Sanitize and validate input data
        $settings = array();
        if (isset($_POST['settings']) && is_array($_POST['settings'])) {
            foreach ($_POST['settings'] as $key => $value) {
                $sanitized_key = sanitize_key($key);
                if (is_string($value)) {
                    $settings[$sanitized_key] = sanitize_text_field($value);
                } elseif (is_array($value)) {
                    $settings[$sanitized_key] = array_map('sanitize_text_field', $value);
                } else {
                    $settings[$sanitized_key] = sanitize_text_field(strval($value));
                }
            }
        }

        // Save settings with proper option name
        $option_name = 'aialvi_vue_plugin_settings';
        $result = update_option($option_name, $settings);

        // Return JSON response
        if ($result) {
            wp_send_json_success(array(
                'message' => esc_html__('Settings saved successfully', $this->text_domain),
                'settings' => $settings
            ));
        } else {
            wp_send_json_error(array(
                'message' => esc_html__('Failed to save settings', $this->text_domain)
            ));
        }
    }

    /**
     * Handle AJAX request to get settings
     */
    public function handle_get_settings()
    {
        // Verify nonce for CSRF protection
        if (!wp_verify_nonce($_GET['nonce'] ?? $_POST['nonce'] ?? '', 'aialvi_vue_nonce')) {
            wp_die(esc_html__('Security check failed', $this->text_domain));
        }

        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions', $this->text_domain));
        }

        // Get settings with default values
        $option_name = 'aialvi_vue_plugin_settings';
        $default_settings = array(
            'enable_feature' => false,
            'api_key' => '',
            'max_items' => 10
        );
        $settings = get_option($option_name, $default_settings);

        // Sanitize output
        $sanitized_settings = array();
        foreach ($settings as $key => $value) {
            $sanitized_key = sanitize_key($key);
            if (is_string($value)) {
                $sanitized_settings[$sanitized_key] = sanitize_text_field($value);
            } elseif (is_array($value)) {
                $sanitized_settings[$sanitized_key] = array_map('sanitize_text_field', $value);
            } else {
                $sanitized_settings[$sanitized_key] = sanitize_text_field(strval($value));
            }
        }

        wp_send_json_success($sanitized_settings);
    }

    /**
     * Register REST API routes with proper security
     */
    public function register_rest_routes()
    {
        register_rest_route('aialvi-vue/v1', '/settings', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_settings'),
            'permission_callback' => array($this, 'rest_permission_check'),
        ));

        register_rest_route('aialvi-vue/v1', '/settings', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_save_settings'),
            'permission_callback' => array($this, 'rest_permission_check'),
            'args' => array(
                'settings' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_settings_data'),
                    'sanitize_callback' => array($this, 'sanitize_settings_data'),
                ),
            ),
        ));
    }

    /**
     * Permission callback for REST API
     */
    public function rest_permission_check($request)
    {
        return current_user_can('manage_options');
    }

    /**
     * REST API callback to get settings
     */
    public function rest_get_settings($request)
    {
        $option_name = 'aialvi_vue_plugin_settings';
        $default_settings = array(
            'enable_feature' => false,
            'api_key' => '',
            'max_items' => 10
        );
        $settings = get_option($option_name, $default_settings);

        // Sanitize output
        $sanitized_settings = array();
        foreach ($settings as $key => $value) {
            $sanitized_key = sanitize_key($key);
            if (is_string($value)) {
                $sanitized_settings[$sanitized_key] = sanitize_text_field($value);
            } elseif (is_array($value)) {
                $sanitized_settings[$sanitized_key] = array_map('sanitize_text_field', $value);
            } else {
                $sanitized_settings[$sanitized_key] = sanitize_text_field(strval($value));
            }
        }

        return rest_ensure_response($sanitized_settings);
    }

    /**
     * REST API callback to save settings
     */
    public function rest_save_settings($request)
    {
        $settings = $request->get_param('settings');

        // Settings are already validated and sanitized by the args callback
        $option_name = 'aialvi_vue_plugin_settings';
        $result = update_option($option_name, $settings);

        if ($result) {
            return rest_ensure_response(array(
                'success' => true,
                'message' => esc_html__('Settings saved successfully', $this->text_domain),
                'settings' => $settings
            ));
        } else {
            return new WP_Error('save_failed', esc_html__('Failed to save settings', $this->text_domain), array('status' => 500));
        }
    }

    /**
     * Validate settings data for REST API
     */
    public function validate_settings_data($param, $request, $key)
    {
        if (!is_array($param)) {
            return false;
        }

        // Add custom validation rules here
        foreach ($param as $setting_key => $setting_value) {
            // Validate setting keys
            if (!is_string($setting_key) || empty($setting_key)) {
                return false;
            }

            // Add specific validation for different setting types
            switch ($setting_key) {
                case 'max_items':
                    if (!is_numeric($setting_value) || $setting_value < 1 || $setting_value > 100) {
                        return false;
                    }
                    break;
                case 'api_key':
                    if (!is_string($setting_value) || strlen($setting_value) > 255) {
                        return false;
                    }
                    break;
                case 'enable_feature':
                    if (!is_bool($setting_value) && !in_array($setting_value, array('0', '1', 0, 1, true, false), true)) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Sanitize settings data for REST API
     */
    public function sanitize_settings_data($param, $request, $key)
    {
        if (!is_array($param)) {
            return array();
        }

        $sanitized = array();
        foreach ($param as $setting_key => $setting_value) {
            $sanitized_key = sanitize_key($setting_key);

            switch ($setting_key) {
                case 'max_items':
                    $sanitized[$sanitized_key] = absint($setting_value);
                    break;
                case 'api_key':
                    $sanitized[$sanitized_key] = sanitize_text_field($setting_value);
                    break;
                case 'enable_feature':
                    $sanitized[$sanitized_key] = rest_sanitize_boolean($setting_value);
                    break;
                default:
                    if (is_string($setting_value)) {
                        $sanitized[$sanitized_key] = sanitize_text_field($setting_value);
                    } elseif (is_array($setting_value)) {
                        $sanitized[$sanitized_key] = array_map('sanitize_text_field', $setting_value);
                    } else {
                        $sanitized[$sanitized_key] = sanitize_text_field(strval($setting_value));
                    }
                    break;
            }
        }

        return $sanitized;
    }
}