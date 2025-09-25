<?php
/**
 * Plugin Name: Aminul's Page Ranks
 * Description: A plugin built using Vue.js
 * Version: 1.0.0
 * Author: Aminul Islam Alvi
 * Author URI: https://aialvi.me
 * License: GPL2
 * Text Domain: aialvi-page-ranks
 *
 * @package aialvi-page-ranks
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants for security.
define( 'AIALVI_PLUGIN_VERSION', '1.0.0' );
define( 'AIALVI_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'AIALVI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AIALVI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Include the main plugin class.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-aialvi-vue-plugin.php';

/**
 * Initialize the plugin.
 */
function run_aialvi_vue_plugin() {
	$plugin = new AIALVI_Vue_Plugin();
	$plugin->run();
}
run_aialvi_vue_plugin();
