<?php
/**
 * Plugin Name: Aminul's Vue Plugin
 * Description: A plugin built using Vue.js
 * Version: 1.0.0
 * Author: Aminul Islam Alvi
 * Author URI: aialvi.me
 * License: GPL2
 * Text Domain: aialvi-vue-plugin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants for security
define( 'AIALVI_PLUGIN_VERSION', '1.0.0' );
define( 'AIALVI_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'AIALVI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AIALVI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'AIALVI_PLUGIN_TEXT_DOMAIN', 'aialvi-vue-plugin' );

// Include the main plugin class.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-aialvi-vue.php';

// Initialize the plugin.
function run_aialvi_vue_plugin() {
    $plugin = new AIALVI_Vue_Plugin();
    $plugin->run();
}
run_aialvi_vue_plugin();