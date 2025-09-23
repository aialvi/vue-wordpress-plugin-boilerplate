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

// Include the main plugin class.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-aialvi-vue.php';

// Initialize the plugin.
function run_aialvi_vue_plugin() {
    $plugin = new AIALVI_Vue_Plugin();
    $plugin->run();
}
run_aialvi_vue_plugin();