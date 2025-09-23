<?php
/**
 * Main plugin class.
 */
class AIALVI_Vue_Plugin {
    /**
     * Constructor.
     */
    public function __construct() {
        $this->load_dependencies();
        $this->set_hooks();
    }

    /**
     * Load the required dependencies.
     */
    private function load_dependencies() {
        require_once plugin_dir_path( __FILE__ ) . '../src/php/Admin.php';
        require_once plugin_dir_path( __FILE__ ) . '../src/php/Public.php';
    }

    /**
     * Set up hooks and filters.
     */
    private function set_hooks() {
        // Initialize admin functionality
        $admin = new AIALVI_Vue_Plugin_Admin();

        // Initialize public functionality
        $public = new AIALVI_Vue_Plugin_Public();
        add_action( 'wp_enqueue_scripts', array( $public, 'enqueue_scripts' ) );
    }

    /**
     * Run the plugin.
     */
    public function run() {
        // Plugin is already initialized in constructor
        // This method exists for compatibility with the main plugin file
    }
}