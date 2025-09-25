<?php
/**
 * Main plugin class.
 *
 * @package aialvi-page-ranks
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
class AIALVI_Vue_Plugin {


	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * The admin instance.
	 *
	 * @var AIALVI_Vue_Plugin_Admin
	 */
	private $admin;

	/**
	 * The public instance.
	 *
	 * @var AIALVI_Vue_Plugin_Public
	 */
	private $public;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->version = defined( 'AIALVI_PLUGIN_VERSION' ) ? AIALVI_PLUGIN_VERSION : '1.0.0';
		$this->load_dependencies();
		$this->set_hooks();
		$this->init_database();
	}

	/**
	 * Load the required dependencies.
	 */
	private function load_dependencies() {
		// Require PHP files.
		require_once AIALVI_PLUGIN_PATH . 'src/php/class-aialvi-vue-plugin-admin.php';
		require_once AIALVI_PLUGIN_PATH . 'src/php/class-aialvi-vue-plugin-public.php';
		require_once AIALVI_PLUGIN_PATH . 'src/php/class-aialvi-vue-plugin-security.php';
	}

	/**
	 * Define all hooks.
	 */
	private function set_hooks() {
		// Initialize admin functionality.
		if ( class_exists( 'AIALVI_Vue_Plugin_Admin' ) ) {
			$this->admin = new AIALVI_Vue_Plugin_Admin();
		}

		// Initialize public functionality.
		if ( class_exists( 'AIALVI_Vue_Plugin_Public' ) ) {
			$this->public = new AIALVI_Vue_Plugin_Public();
		}

		// Plugin activation/deactivation hooks.
		register_activation_hook( AIALVI_PLUGIN_BASENAME, array( $this, 'activate' ) );
		register_deactivation_hook( AIALVI_PLUGIN_BASENAME, array( $this, 'deactivate' ) );

		// Add uninstall hook.
		register_uninstall_hook( AIALVI_PLUGIN_BASENAME, array( 'AIALVI_Vue_Plugin', 'uninstall' ) );

		// Load text domain for translations.
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Add settings link to plugins page.
		add_filter( 'plugin_action_links_' . AIALVI_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );
	}

	/**
	 * Initialize database tables if needed.
	 */
	private function init_database() {
		// Check if we need to create/update database tables.
		$installed_version = get_option( 'aialvi_vue_plugin_version', '0.0.0' );

		if ( version_compare( $installed_version, $this->version, '<' ) ) {
			$this->create_database_tables();
			update_option( 'aialvi_vue_plugin_version', $this->version );
		}
	}

	/**
	 * Create database tables.
	 */
	private function create_database_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'aialvi_vue_submissions';

		// SQL for creating the submissions table.
		$sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name tinytext NOT NULL,
            email varchar(100) NOT NULL,
            message text,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            ip_address varchar(45),
            user_agent text,
            status varchar(20) DEFAULT 'pending',
            PRIMARY KEY (id),
            INDEX idx_email (email),
            INDEX idx_submitted_at (submitted_at),
            INDEX idx_status (status)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Load plugin text domain for translations.
	 */
	public function load_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'aialvi-page-ranks' );

		load_textdomain(
			'aialvi-page-ranks',
			WP_LANG_DIR . '/plugins/aialvi-page-ranks-' . $locale . '.mo'
		);
	}

	/**
	 * Add action links to plugin page.
	 *
	 * @param array $links The existing links.
	 * @return array
	 */
	public function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=aialvi-page-ranks' ) ),
			esc_html__( 'Settings', 'aialvi-page-ranks' )
		);

		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Plugin activation callback.
	 */
	public function activate() {
		// Create database tables.
		$this->create_database_tables();

		// Set default options with proper sanitization.
		$default_settings = array(
			'table_rows'          => 5,
			'date_format'         => 'human',
			'notification_emails' => array( get_option( 'admin_email' ) ),
		);

		if ( ! get_option( 'aialvi_vue_plugin_settings' ) ) {
			add_option( 'aialvi_vue_plugin_settings', $default_settings );
		}

		// Store plugin version.
		update_option( 'aialvi_vue_plugin_version', $this->version );

		// Flush rewrite rules to ensure REST API routes are registered.
		flush_rewrite_rules();

		// Clear any cached data.
		wp_cache_flush();
	}

	/**
	 * Plugin deactivation callback.
	 */
	public function deactivate() {
		// Clear scheduled hooks.
		wp_clear_scheduled_hook( 'aialvi_vue_daily_cleanup' );

		// Clear any cached data.
		wp_cache_flush();
	}

	/**
	 * Plugin uninstall callback.
	 */
	public static function uninstall() {
		// Remove all plugin options.
		delete_option( 'aialvi_vue_plugin_settings' );
		delete_option( 'aialvi_vue_plugin_version' );

		// Remove database tables if needed (optional - you might want to keep data).
		global $wpdb;
		$table_name = $wpdb->prefix . 'aialvi_vue_submissions';

		// Uncomment the next line if you want to remove all data on uninstall..

		// Clear any cached data.
		wp_cache_flush();
	}

	/**
	 * Run the plugin.
	 */
	public function run() {
		// Plugin is already initialized in constructor.
		// This method exists for compatibility with the main plugin file.
	}

	/**
	 * Get plugin version.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Get admin instance.
	 */
	public function get_admin() {
		return $this->admin;
	}

	/**
	 * Get public instance.
	 */
	public function get_public() {
		return $this->public;
	}
}
