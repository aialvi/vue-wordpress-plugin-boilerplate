<?php
/**
 * Admin class for handling admin functionality of the plugin.
 */
class AIALVI_Vue_Plugin_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Aminul\'s Plugin Settings',
            'Aminul\'s Plugin',
            'manage_options',
            'aialvi-vue-plugin',
            [$this, 'create_admin_page'],
            'dashicons-admin-generic',
            6
        );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <div id="aialvi-vue-admin-app">
                <div class="loading">Loading Vue.js Admin Panel...</div>
            </div>
        </div>
        <script>
            console.log('Admin page rendered, checking for Vue mount element...');
            console.log('Element found:', document.getElementById('aialvi-vue-admin-app'));
        </script>
        <?php
    }

    public function enqueue_scripts($hook_suffix) {
        // Only load on our admin page
        if ('toplevel_page_aialvi-vue-plugin' !== $hook_suffix) {
            return;
        }

        // Add debugging
        error_log('Enqueuing scripts for: ' . $hook_suffix);

        // Plugin directory URL
        $plugin_url = plugin_dir_url(dirname(dirname(__FILE__)));

        // Check if built files exist, otherwise use development files
        $admin_js_file = plugin_dir_path(dirname(dirname(__FILE__))) . 'dist/admin.bundle.js';
        $admin_css_file = plugin_dir_path(dirname(dirname(__FILE__))) . 'dist/admin.css';

        error_log('Checking for JS file: ' . $admin_js_file);
        error_log('JS file exists: ' . (file_exists($admin_js_file) ? 'yes' : 'no'));

        if (file_exists($admin_js_file)) {
            // Production build files
            wp_enqueue_script(
                'aialvi-vue-admin',
                $plugin_url . 'dist/admin.bundle.js',
                [],
                filemtime($admin_js_file),
                true
            );

            error_log('Enqueued JS: ' . $plugin_url . 'dist/admin.bundle.js');

            if (file_exists($admin_css_file)) {
                wp_enqueue_style(
                    'aialvi-vue-admin',
                    $plugin_url . 'dist/admin.css',
                    [],
                    filemtime($admin_css_file)
                );

                error_log('Enqueued CSS: ' . $plugin_url . 'dist/admin.css');
            }

            // Pass data to JavaScript
            wp_localize_script('aialvi-vue-admin', 'aialviVuePlugin', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aialvi_vue_nonce'),
                'rest_url' => rest_url('aialvi-vue/v1/'),
            ]);
        } else {
            error_log('Using development mode');
            // Development mode - need to run vite dev server
            wp_enqueue_script(
                'aialvi-vue-admin-dev',
                'http://localhost:3000/src/admin/main.js',
                [],
                time(),
                true
            );

            // Pass data to JavaScript for dev mode
            wp_localize_script('aialvi-vue-admin-dev', 'aialviVuePlugin', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aialvi_vue_nonce'),
                'rest_url' => rest_url('aialvi-vue/v1/'),
            ]);
        }
    }
}