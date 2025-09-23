<?php
/**
 * Public class for handling the public functionality of the plugin.
 * 
 * @package AIALVI_Vue_Plugin
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class AIALVI_Vue_Plugin_Public
{

    /**
     * Plugin text domain for translations
     */
    private $text_domain;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->text_domain = defined('AIALVI_PLUGIN_TEXT_DOMAIN') ? AIALVI_PLUGIN_TEXT_DOMAIN : 'aialvi-vue-plugin';

        // Add public hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('aialvi_vue_app', array($this, 'render_shortcode'));
        add_action('wp_ajax_aialvi_vue_public_action', array($this, 'handle_public_ajax'));
        add_action('wp_ajax_nopriv_aialvi_vue_public_action', array($this, 'handle_public_ajax'));
        add_action('rest_api_init', array($this, 'register_public_rest_routes'));
    }

    /**
     * Enqueue scripts and styles for the public-facing part of the plugin.
     */
    public function enqueue_scripts()
    {
        // Only enqueue on pages that need it
        if (!$this->should_enqueue_scripts()) {
            return;
        }

        // Plugin directory URL - use constants for security
        $plugin_url = defined('AIALVI_PLUGIN_URL') ? AIALVI_PLUGIN_URL : plugin_dir_url(dirname(dirname(__FILE__)));
        $plugin_path = defined('AIALVI_PLUGIN_PATH') ? AIALVI_PLUGIN_PATH : plugin_dir_path(dirname(dirname(__FILE__)));

        // Check if built files exist, otherwise use development files
        $public_js_file = $plugin_path . 'dist/public.bundle.js';
        $public_css_file = $plugin_path . 'dist/public.css';

        if (file_exists($public_js_file)) {
            // Production build files
            wp_enqueue_script(
                'aialvi-vue-public',
                esc_url($plugin_url . 'dist/public.bundle.js'),
                array(),
                filemtime($public_js_file),
                true
            );

            if (file_exists($public_css_file)) {
                wp_enqueue_style(
                    'aialvi-vue-public',
                    esc_url($plugin_url . 'dist/public.css'),
                    array(),
                    filemtime($public_css_file)
                );
            }

            // Pass data to JavaScript with proper nonce
            wp_localize_script('aialvi-vue-public', 'aialviVuePublic', array(
                'ajax_url' => esc_url(admin_url('admin-ajax.php')),
                'nonce' => wp_create_nonce('aialvi_vue_public_nonce'),
                'rest_url' => esc_url(rest_url('aialvi-vue-public/v1/')),
                'plugin_url' => esc_url($plugin_url),
                'is_user_logged_in' => is_user_logged_in(),
            ));
        }
    }

    /**
     * Check if we should enqueue scripts on this page
     */
    private function should_enqueue_scripts()
    {
        global $post;

        // Check if current post contains our shortcode
        if ($post && has_shortcode($post->post_content, 'aialvi_vue_app')) {
            return true;
        }

        // Check if this is a page that specifically needs our scripts
        $allowed_pages = apply_filters('aialvi_vue_enqueue_pages', array());
        if (!empty($allowed_pages) && is_page($allowed_pages)) {
            return true;
        }

        return false;
    }

    /**
     * Render shortcode with proper security measures
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_shortcode($atts)
    {
        // Sanitize and validate attributes
        $atts = shortcode_atts(array(
            'id' => 'aialvi-vue-public-app',
            'class' => '',
            'data' => '',
        ), $atts, 'aialvi_vue_app');

        // Sanitize attributes
        $id = sanitize_html_class($atts['id']);
        $class = sanitize_html_class($atts['class']);
        $data = sanitize_text_field($atts['data']);

        // Create nonce for this specific instance
        $nonce = wp_create_nonce('aialvi_vue_shortcode_' . $id);

        // Build the output with proper escaping
        $output = sprintf(
            '<div id="%1$s" class="aialvi-vue-app %2$s" data-nonce="%3$s" data-config="%4$s">',
            esc_attr($id),
            esc_attr($class),
            esc_attr($nonce),
            esc_attr($data)
        );

        $output .= '<div class="loading">' . esc_html__('Loading...', $this->text_domain) . '</div>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Handle public AJAX requests
     */
    public function handle_public_ajax()
    {
        // Verify nonce for CSRF protection
        $nonce_field = $_POST['nonce'] ?? $_GET['nonce'] ?? '';
        if (!wp_verify_nonce($nonce_field, 'aialvi_vue_public_nonce')) {
            wp_die(esc_html__('Security check failed', $this->text_domain));
        }

        // Get and sanitize action
        $action = sanitize_text_field($_POST['sub_action'] ?? $_GET['sub_action'] ?? '');

        switch ($action) {
            case 'get_data':
                $this->handle_get_data();
                break;
            case 'submit_form':
                $this->handle_form_submission();
                break;
            default:
                wp_send_json_error(array(
                    'message' => esc_html__('Invalid action', $this->text_domain)
                ));
                break;
        }
    }

    /**
     * Handle get data AJAX request
     */
    private function handle_get_data()
    {
        // Get public data (no special permissions needed)
        $data = array(
            'items' => $this->get_public_items(),
            'timestamp' => current_time('timestamp'),
        );

        wp_send_json_success($data);
    }

    /**
     * Handle form submission AJAX request
     */
    private function handle_form_submission()
    {
        // Additional nonce check for form submissions
        $form_nonce = $_POST['form_nonce'] ?? '';
        if (!wp_verify_nonce($form_nonce, 'aialvi_vue_form_nonce')) {
            wp_send_json_error(array(
                'message' => esc_html__('Form security check failed', $this->text_domain)
            ));
        }

        // Sanitize form data
        $form_data = array();
        if (isset($_POST['form_data']) && is_array($_POST['form_data'])) {
            foreach ($_POST['form_data'] as $key => $value) {
                $sanitized_key = sanitize_key($key);
                if (is_string($value)) {
                    $form_data[$sanitized_key] = sanitize_text_field($value);
                } elseif (is_array($value)) {
                    $form_data[$sanitized_key] = array_map('sanitize_text_field', $value);
                }
            }
        }

        // Validate required fields
        $required_fields = array('name', 'email');
        foreach ($required_fields as $field) {
            if (empty($form_data[$field])) {
                wp_send_json_error(array(
                    'message' => sprintf(
                        esc_html__('Field %s is required', $this->text_domain),
                        esc_html($field)
                    )
                ));
            }
        }

        // Validate email
        if (!is_email($form_data['email'])) {
            wp_send_json_error(array(
                'message' => esc_html__('Please enter a valid email address', $this->text_domain)
            ));
        }

        // Process the form (save to database, send email, etc.)
        $result = $this->process_form_data($form_data);

        if ($result) {
            wp_send_json_success(array(
                'message' => esc_html__('Form submitted successfully', $this->text_domain)
            ));
        } else {
            wp_send_json_error(array(
                'message' => esc_html__('Failed to process form', $this->text_domain)
            ));
        }
    }

    /**
     * Get public items with proper sanitization
     */
    private function get_public_items()
    {
        // This is just an example - replace with your actual data retrieval logic
        $items = array();

        // Get items from database, API, etc.
        $raw_items = get_option('aialvi_vue_public_items', array());

        // Sanitize each item
        foreach ($raw_items as $item) {
            if (is_array($item)) {
                $sanitized_item = array();
                foreach ($item as $key => $value) {
                    $sanitized_key = sanitize_key($key);
                    $sanitized_item[$sanitized_key] = sanitize_text_field($value);
                }
                $items[] = $sanitized_item;
            }
        }

        return $items;
    }

    /**
     * Process form data
     */
    private function process_form_data($form_data)
    {
        // Save to database with proper sanitization
        global $wpdb;

        $table_name = $wpdb->prefix . 'aialvi_vue_submissions';

        $result = $wpdb->insert(
            $table_name,
            array(
                'name' => sanitize_text_field($form_data['name']),
                'email' => sanitize_email($form_data['email']),
                'message' => sanitize_textarea_field($form_data['message'] ?? ''),
                'submitted_at' => current_time('mysql'),
                'ip_address' => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
                'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );

        return $result !== false;
    }

    /**
     * Register public REST API routes
     */
    public function register_public_rest_routes()
    {
        register_rest_route('aialvi-vue-public/v1', '/data', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_public_data'),
            'permission_callback' => '__return_true', // Public endpoint
        ));

        register_rest_route('aialvi-vue-public/v1', '/submit', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_submit_form'),
            'permission_callback' => '__return_true', // Public endpoint
            'args' => array(
                'name' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_name'),
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'email' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_email'),
                    'sanitize_callback' => 'sanitize_email',
                ),
                'message' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
            ),
        ));
    }

    /**
     * REST API callback to get public data
     */
    public function rest_get_public_data($request)
    {
        $data = array(
            'items' => $this->get_public_items(),
            'timestamp' => current_time('timestamp'),
        );

        return rest_ensure_response($data);
    }

    /**
     * REST API callback to submit form
     */
    public function rest_submit_form($request)
    {
        $form_data = array(
            'name' => $request->get_param('name'),
            'email' => $request->get_param('email'),
            'message' => $request->get_param('message'),
        );

        $result = $this->process_form_data($form_data);

        if ($result) {
            return rest_ensure_response(array(
                'success' => true,
                'message' => esc_html__('Form submitted successfully', $this->text_domain)
            ));
        } else {
            return new WP_Error('submit_failed', esc_html__('Failed to process form', $this->text_domain), array('status' => 500));
        }
    }

    /**
     * Validate name field
     */
    public function validate_name($param, $request, $key)
    {
        return is_string($param) && !empty(trim($param)) && strlen($param) <= 100;
    }

    /**
     * Validate email field
     */
    public function validate_email($param, $request, $key)
    {
        return is_email($param);
    }
}