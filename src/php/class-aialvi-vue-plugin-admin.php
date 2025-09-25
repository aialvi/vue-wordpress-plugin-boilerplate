<?php
/**
 * Admin class for handling admin functionality of the plugin.
 *
 * @package aialvi-page-ranks
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class.
 */
class AIALVI_Vue_Plugin_Admin {


	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_aialvi_vue_save_settings', array( $this, 'handle_save_settings' ) );
		add_action( 'wp_ajax_aialvi_vue_get_settings', array( $this, 'handle_get_settings' ) );
		add_action( 'wp_ajax_aialvi_vue_get_data', array( $this, 'handle_get_data' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Add admin menu with proper capability checking.
	 */
	public function add_admin_menu() {
		add_menu_page(
			esc_html__( 'Aminul\'s Plugin Settings', 'aialvi-page-ranks' ),
			esc_html__( 'Aminul\'s Plugin', 'aialvi-page-ranks' ),
			'manage_options',
			'aialvi-page-ranks',
			array( $this, 'create_admin_page' ),
			'dashicons-admin-generic',
			6
		);
	}

	/**
	 * Create admin page with proper security measures.
	 */
	public function create_admin_page() {
		// Verify user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'aialvi-page-ranks' ) );
		}

		// Add nonce for CSRF protection.
		$nonce = wp_create_nonce( 'aialvi_vue_admin_nonce' );
		?>
		<div class="wrap">
			<div id="aialvi-vue-admin-app" data-nonce="<?php echo esc_attr( $nonce ); ?>">
				<!-- Vue.js app will mount here and replace this content -->
				<div class="aialvi-loading-state">
					<h1><?php echo esc_html__( "Aminul's Page Ranks", 'aialvi-page-ranks' ); ?></h1>
					<div class="loading-wrapper">
						<div class="spinner is-active"></div>
						<p><?php echo esc_html__( 'Loading Vue.js Admin Panel...', 'aialvi-page-ranks' ); ?></p>
					</div>
				</div>

				<!-- Fallback content for users with JavaScript disabled -->
				<noscript>
					<div class="notice notice-error">
						<p><strong><?php echo esc_html__( 'JavaScript Required', 'aialvi-page-ranks' ); ?></strong></p>
						<p><?php echo esc_html__( 'This admin panel requires JavaScript to be enabled. Please enable JavaScript in your browser to use this feature.', 'aialvi-page-ranks' ); ?>
						</p>
					</div>
					<div class="admin-fallback">
						<h1><?php echo esc_html__( "Aminul's Page Ranks", 'aialvi-page-ranks' ); ?></h1>
						<p><?php echo esc_html__( 'This plugin provides advanced functionality through a Vue.js interface. To access all features, please enable JavaScript in your browser.', 'aialvi-page-ranks' ); ?>
						</p>
						<h2><?php echo esc_html__( 'Available Features:', 'aialvi-page-ranks' ); ?></h2>
						<ul>
							<li><?php echo esc_html__( 'Data Table - View formatted data from external API.', 'aialvi-page-ranks' ); ?>
							</li>
							<li><?php echo esc_html__( 'Interactive Graphs - Visualize data trends.', 'aialvi-page-ranks' ); ?></li>
							<li><?php echo esc_html__( 'Settings Management - Configure plugin options.', 'aialvi-page-ranks' ); ?>
							</li>
						</ul>
					</div>
				</noscript>
			</div>
		</div>

		<style>
			.aialvi-loading-state {
				text-align: center;
				padding: 40px 20px;
			}

			.loading-wrapper {
				margin: 20px 0;
			}

			.spinner.is-active {
				background: url(data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxjaXJjbGUgY3g9IjEwIiBjeT0iMTAiIHI9IjEwIiBmaWxsPSJub25lIiBzdHJva2U9IiNkNjNhMzgiIHN0cm9rZS13aWR0aD0iNCIvPgogICAgPGNpcmNsZSBjeD0iMTAiIGN5PSIxMCIgcj0iMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzAwNzNhYSIgc3Ryb2tlLXdpZHRoPSI0IiBzdHJva2UtZGFzaGFycmF5PSI2MiIgc3Ryb2tlLWRhc2hvZmZzZXQ9IjMxIiBvcGFjaXR5PSIuNyI+CiAgICAgICAgPGFuaW1hdGVUcmFuc2Zvcm0gYXR0cmlidXRlTmFtZT0idHJhbnNmb3JtIiB0eXBlPSJyb3RhdGUiIGZyb209IjAgMTAgMTAiIHRvPSIzNjAgMTAgMTAiIGR1cj0iMXMiIHJlcGVhdENvdW50PSJpbmRlZmluaXRlIi8+CiAgICA8L2NpcmNsZT4KPC9zdmc+) no-repeat center center;
				background-size: 20px 20px;
				display: inline-block;
				height: 20px;
				width: 20px;
				vertical-align: middle;
				margin-right: 10px;
			}

			.admin-fallback {
				max-width: 600px;
				margin: 0 auto;
				text-align: left;
			}

			.admin-fallback ul {
				list-style-type: disc;
				margin-left: 20px;
			}
		</style>
		<?php
	}

	/**
	 * Enqueue scripts and styles with proper security measures.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public function enqueue_scripts( $hook_suffix ) {
		// Only load on our admin page.
		if ( 'toplevel_page_aialvi-page-ranks' !== $hook_suffix ) {
			return;
		}

		// Verify user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Dequeue conflicting WordPress scripts that might cause issues.
		wp_dequeue_script( 'svg-painter' );
		wp_deregister_script( 'svg-painter' );

		// Add debugging with proper escaping.

		// Plugin directory URL - use constants for security.
		$plugin_url  = defined( 'AIALVI_PLUGIN_URL' ) ? AIALVI_PLUGIN_URL : plugin_dir_url( dirname( __DIR__ ) );
		$plugin_path = defined( 'AIALVI_PLUGIN_PATH' ) ? AIALVI_PLUGIN_PATH : plugin_dir_path( dirname( __DIR__ ) );

		// Check if we're in development mode by testing if the dev server is running.
		$is_dev_mode = $this->is_vite_dev_server_running();

		if ( $is_dev_mode ) {
			// Development mode - load from Vite dev server.

			// Load Vite client for HMR.
			wp_enqueue_script(
				'aialvi-vue-admin-vite-client',
				'http://localhost:3000/@vite/client',
				array(),
				'1.0.0', // Version for cache busting.
				true
			);

			// Load main application script.
			wp_enqueue_script(
				'aialvi-vue-admin-dev',
				'http://localhost:3000/src/admin/main.js',
				array( 'aialvi-vue-admin-vite-client' ),
				'1.0.0', // Version for cache busting.
				true
			);

			// Set script type to module for both scripts.
			add_filter(
				'script_loader_tag',
				function ( $tag, $handle ) {
					if ( in_array( $handle, array( 'aialvi-vue-admin-dev', 'aialvi-vue-admin-vite-client' ), true ) ) {
						// Modify the existing script tag to add type="module".
						return str_replace( '<script', '<script type="module"', $tag );
					}
					return $tag;
				},
				10,
				3
			);

			// Pass data to JavaScript for dev mode.
			wp_localize_script(
				'aialvi-vue-admin-dev',
				'aialviVuePlugin',
				array(
					'ajax_url'   => esc_url( admin_url( 'admin-ajax.php' ) ),
					'nonce'      => wp_create_nonce( 'aialvi_vue_nonce' ),
					'rest_nonce' => wp_create_nonce( 'wp_rest' ),
					'rest_url'   => esc_url( rest_url( 'aialvi-vue/v1/' ) ),
					'plugin_url' => esc_url( $plugin_url ),
					'dev_mode'   => true,
				)
			);

		} else {
			// Production mode - load built files.
			$admin_js_file  = $plugin_path . 'dist/admin.bundle.js';
			$admin_css_file = $plugin_path . 'dist/admin.css';

			if ( file_exists( $admin_js_file ) ) {
				// Production build files.
				wp_enqueue_script(
					'aialvi-vue-admin',
					esc_url( $plugin_url . 'dist/admin.bundle.js' ),
					array( 'jquery' ),
					filemtime( $admin_js_file ),
					true
				);

				if ( file_exists( $admin_css_file ) ) {
					wp_enqueue_style(
						'aialvi-vue-admin',
						esc_url( $plugin_url . 'dist/admin.css' ),
						array(),
						filemtime( $admin_css_file )
					);

				}

				// Pass data to JavaScript.
				wp_localize_script(
					'aialvi-vue-admin',
					'aialviVuePlugin',
					array(
						'ajax_url'   => esc_url( admin_url( 'admin-ajax.php' ) ),
						'nonce'      => wp_create_nonce( 'aialvi_vue_nonce' ),
						'rest_nonce' => wp_create_nonce( 'wp_rest' ),
						'rest_url'   => esc_url( rest_url( 'aialvi-vue/v1/' ) ),
						'plugin_url' => esc_url( $plugin_url ),
						'dev_mode'   => false,
					)
				);
			} else {
				// No build files found and dev server not running.

				// Show error message to user.
				add_action(
					'admin_notices',
					function () {
						echo '<div class="notice notice-error"><p>';
						echo '<strong>AIALVI Vue Plugin:</strong> ';
						echo esc_html__( 'Assets not found. Please run "npm run build" to build the plugin assets, or "npm run dev" to start development server.', 'aialvi-page-ranks' );
						echo '</p></div>';
					}
				);
			}
		}
	}

	/**
	 * Check if Vite dev server is running.
	 */
	private function is_vite_dev_server_running() {
		$response = wp_remote_get(
			'http://localhost:3000/@vite/client',
			array(
				'timeout'   => 1,
				'sslverify' => false,
			)
		);

		return ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Handle AJAX request to save settings.
	 */
	public function handle_save_settings() {
		// Verify nonce for CSRF protection.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'aialvi_vue_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed', 'aialvi-page-ranks' ) );
		}

		// Verify user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'aialvi-page-ranks' ) );
		}

		// Sanitize and validate input data.
		$settings = array();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_settings = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array();
		if ( is_array( $raw_settings ) ) {
			foreach ( $raw_settings as $key => $value ) {
				$sanitized_key = sanitize_key( $key );
				if ( is_string( $value ) ) {
					$settings[ $sanitized_key ] = sanitize_text_field( $value );
				} elseif ( is_array( $value ) ) {
					$settings[ $sanitized_key ] = array_map( 'sanitize_text_field', $value );
				} else {
					$settings[ $sanitized_key ] = sanitize_text_field( strval( $value ) );
				}
			}
		}

		// Save settings with proper option name.
		$option_name = 'aialvi_vue_plugin_settings';
		$result      = update_option( $option_name, $settings );

		// Return JSON response.
		if ( $result ) {
			wp_send_json_success(
				array(
					'message'  => esc_html__( 'Settings saved successfully', 'aialvi-page-ranks' ),
					'settings' => $settings,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Failed to save settings', 'aialvi-page-ranks' ),
				)
			);
		}
	}

	/**
	 * Handle AJAX request to get settings.
	 */
	public function handle_get_settings() {
		// Verify nonce for CSRF protection.
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'aialvi_vue_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed', 'aialvi-page-ranks' ) );
		}

		// Verify user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'aialvi-page-ranks' ) );
		}

		// Get settings with default values.
		$option_name      = 'aialvi_vue_plugin_settings';
		$default_settings = $this->get_default_settings();
		$settings         = get_option( $option_name, $default_settings );

		// Sanitize output.
		$sanitized_settings = array();
		foreach ( $settings as $key => $value ) {
			$sanitized_key = sanitize_key( $key );
			if ( is_string( $value ) ) {
				$sanitized_settings[ $sanitized_key ] = sanitize_text_field( $value );
			} elseif ( is_array( $value ) ) {
				$sanitized_settings[ $sanitized_key ] = array_map( 'sanitize_text_field', $value );
			} else {
				$sanitized_settings[ $sanitized_key ] = sanitize_text_field( strval( $value ) );
			}
		}

		wp_send_json_success( $sanitized_settings );
	}

	/**
	 * Handle AJAX request to get external data.
	 */
	public function handle_get_data() {
		// Verify nonce for CSRF protection.
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'aialvi_vue_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed', 'aialvi-page-ranks' ) );
		}

		// Verify user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'aialvi-page-ranks' ) );
		}

		// Get cached data or fetch from external API.
		$cache_key   = 'aialvi_vue_external_data';
		$cached_data = get_transient( $cache_key );

		if ( false !== $cached_data ) {
			wp_send_json_success( $cached_data );
			return;
		}

		// Fetch data from external API.
		$response = wp_remote_get(
			'https://miusage.com/v1/challenge/2/static/',
			array(
				'timeout' => 30,
				'headers' => array(
					'User-Agent' => 'AIALVI Vue Plugin/' . ( defined( 'AIALVI_PLUGIN_VERSION' ) ? AIALVI_PLUGIN_VERSION : '1.0.0' ),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %s: error message */
						esc_html__( 'Failed to fetch external data: %s', 'aialvi-page-ranks' ),
						$response->get_error_message()
					),
				)
			);
			return;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid JSON response from external API', 'aialvi-page-ranks' ),
				)
			);
			return;
		}

		// Cache the data for 1 hour (3600 seconds).
		set_transient( $cache_key, $data, 3600 );

		wp_send_json_success( $data );
	}

	/**
	 * Register REST API routes with proper security.
	 */
	public function register_rest_routes() {
		// Log that we're registering routes.

		// Test endpoint to verify REST API is working.
		register_rest_route(
			'aialvi-vue/v1',
			'/test',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_test_endpoint' ),
				'permission_callback' => '__return_true', // Allow public access for testing.
			)
		);

		// Settings endpoints.
		register_rest_route(
			'aialvi-vue/v1',
			'/settings',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_settings' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
			)
		);

		register_rest_route(
			'aialvi-vue/v1',
			'/settings',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_save_settings' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
				'args'                => array(
					'settings' => array(
						'required' => true,
					),
				),
			)
		);

		// Data endpoint.
		register_rest_route(
			'aialvi-vue/v1',
			'/data',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_data' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
			)
		);

		// Single setting update endpoint.
		register_rest_route(
			'aialvi-vue/v1',
			'/setting/(?P<key>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_update_single_setting' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
				'args'                => array(
					'key'   => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
					'value' => array(
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Simple test endpoint to verify REST API is working.
	 *
	 * @return WP_REST_Response
	 */
	public function rest_test_endpoint() { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return rest_ensure_response(
			array(
				'message'         => 'AIALVI Vue Plugin REST API is working!',
				'timestamp'       => current_time( 'mysql' ),
				'user_logged_in'  => is_user_logged_in(),
				'user_can_manage' => current_user_can( 'manage_options' ),
			)
		);
	}

	/**
	 * REST API callback to get external data with caching.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_get_data() { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable

		$cache_key   = 'aialvi_vue_external_data';
		$cached_data = get_transient( $cache_key );

		if ( false !== $cached_data ) {

			return rest_ensure_response( $cached_data );
		}

		// Fetch data from external API.
		$response = wp_remote_get(
			'https://miusage.com/v1/challenge/2/static/',
			array(
				'timeout' => 30,
				'headers' => array(
					'User-Agent' => 'AIALVI Vue Plugin/' . ( defined( 'AIALVI_PLUGIN_VERSION' ) ? AIALVI_PLUGIN_VERSION : '1.0.0' ),
				),
			)
		);

		if ( is_wp_error( $response ) ) {

			return new WP_Error(
				'api_error',
				sprintf(
					/* translators: %s: error message */
					esc_html__( 'Failed to fetch external data: %s', 'aialvi-page-ranks' ),
					$response->get_error_message()
				),
				array( 'status' => 500 )
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {

			return new WP_Error(
				'json_error',
				esc_html__( 'Invalid JSON response from external API', 'aialvi-page-ranks' ),
				array( 'status' => 500 )
			);
		}

		// Ensure data has the expected structure.
		if ( ! is_array( $data ) ) {
			$data = array(
				'table' => array(),
				'graph' => array(),
			);
		}

		if ( ! isset( $data['table'] ) ) {
			$data['table'] = array();
		}

		if ( ! isset( $data['graph'] ) ) {
			$data['graph'] = array();
		}

		// Cache the data for 1 hour (3600 seconds).
		set_transient( $cache_key, $data, 3600 );

		return rest_ensure_response( $data );
	}

	/**
	 * REST API callback to update a single setting.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_update_single_setting( $request ) {
		$key   = $request->get_param( 'key' );
		$value = $request->get_param( 'value' );

		// Validate the setting.
		$validated_value = $this->validate_single_setting( $key, $value );
		if ( false === $validated_value ) {
			return new WP_Error(
				'invalid_setting',
				sprintf(
					/* translators: %s: setting key */
					esc_html__( 'Invalid value for setting: %s', 'aialvi-page-ranks' ),
					$key
				),
				array( 'status' => 400 )
			);
		}

		// Get current settings.
		$option_name = 'aialvi_vue_plugin_settings';
		$settings    = get_option( $option_name, $this->get_default_settings() );

		// Update the specific setting.
		$settings[ $key ] = $validated_value;

		// Save the updated settings.
		update_option( $option_name, $settings );

		// update_option returns false if the value is unchanged, so we need to check differently.
		// If we got this far with a valid setting, consider it successful.
		return rest_ensure_response(
			array(
				'success' => true,
				'message' => sprintf(
					/* translators: %s: setting key */
					esc_html__( 'Setting %s updated successfully', 'aialvi-page-ranks' ),
					$key
				),
				'value'   => $validated_value,
			)
		);
	}

	/**
	 * Permission callback for REST API.
	 *
	 * @return bool
	 */
	public function rest_permission_check() { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		// Check if user has the required capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		// For logged-in users, WordPress handles REST API nonce verification automatically.
		// when the X-WP-Nonce header is sent with the request.
		return true;
	}

	/**
	 * REST API callback to get settings.
	 *
	 * @return WP_REST_Response
	 */
	public function rest_get_settings() { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable

		$option_name      = 'aialvi_vue_plugin_settings';
		$default_settings = $this->get_default_settings();
		$settings         = get_option( $option_name, $default_settings );

		// Ensure settings is always an array.
		if ( ! is_array( $settings ) ) {
			$settings = $default_settings;
			update_option( $option_name, $settings );
		}

		// Ensure required keys exist.
		foreach ( $default_settings as $key => $default_value ) {
			if ( ! isset( $settings[ $key ] ) ) {
				$settings[ $key ] = $default_value;
			}
		}

		// Sanitize output.
		$sanitized_settings = array();
		foreach ( $settings as $key => $value ) {
			$sanitized_key = sanitize_key( $key );
			if ( is_string( $value ) ) {
				$sanitized_settings[ $sanitized_key ] = sanitize_text_field( $value );
			} elseif ( is_array( $value ) ) {
				$sanitized_settings[ $sanitized_key ] = array_map( 'sanitize_text_field', $value );
			} else {
				$sanitized_settings[ $sanitized_key ] = sanitize_text_field( strval( $value ) );
			}
		}

		return rest_ensure_response( $sanitized_settings );
	}

	/**
	 * REST API callback to save settings.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_save_settings( $request ) {
		$settings = $request->get_param( 'settings' );

		// Manual validation since we removed the callback.
		if ( ! is_array( $settings ) ) {
			return new WP_Error( 'invalid_settings', 'Settings must be an array.', array( 'status' => 400 ) );
		}

		// Manual sanitization.
		$sanitized_settings = array();
		$allowed_fields     = array( 'table_rows', 'date_format', 'notification_emails' );

		foreach ( $settings as $key => $value ) {
			if ( ! in_array( $key, $allowed_fields, true ) ) {
				continue; // Skip unknown fields.
			}

			switch ( $key ) {
				case 'table_rows':
					$int_val = intval( $value );

					if ( $int_val >= 1 && $int_val <= 5 ) {
						$sanitized_settings[ $key ] = $int_val;
					}
					break;
				case 'date_format':
					if ( in_array( $value, array( 'human', 'timestamp' ), true ) ) {
						$sanitized_settings[ $key ] = $value;
					}
					break;
				case 'notification_emails':
					if ( is_array( $value ) ) {
						$cleaned_emails = array();
						foreach ( $value as $email ) {
							$trimmed = trim( $email );
							if ( ! empty( $trimmed ) && filter_var( $trimmed, FILTER_VALIDATE_EMAIL ) ) {
								$cleaned_emails[] = $trimmed;
							}
						}
						if ( count( $cleaned_emails ) >= 1 && count( $cleaned_emails ) <= 5 ) {
							$sanitized_settings[ $key ] = $cleaned_emails;
						}
					}
					break;
			}
		}

		// Settings are manually validated and sanitized.
		$option_name = 'aialvi_vue_plugin_settings';
		update_option( $option_name, $sanitized_settings );

		// update_option returns false if the value is unchanged, so we need to check differently.
		// Let's just assume success if we got this far with valid sanitized settings.
		if ( count( $sanitized_settings ) > 0 ) {
			return rest_ensure_response(
				array(
					'success'  => true,
					'message'  => esc_html__( 'Settings saved successfully', 'aialvi-page-ranks' ),
					'settings' => $sanitized_settings,
				)
			);
		} else {
			return new WP_Error( 'save_failed', esc_html__( 'No valid settings to save', 'aialvi-page-ranks' ), array( 'status' => 400 ) );
		}
	}

	/**
	 * Get default settings with the 3 required fields.
	 */
	public function get_default_settings() {
		return array(
			'table_rows'          => 3,
			'date_format'         => 'human',
			'notification_emails' => array(),
		);
	}

	/**
	 * Sanitize settings data for REST API.
	 *
	 * @param array $param     The parameter.
	 * @return array
	 */
	public function sanitize_settings_data( $param ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( ! is_array( $param ) ) {
			return array();
		}

		$sanitized      = array();
		$allowed_fields = array( 'table_rows', 'date_format', 'notification_emails' );

		foreach ( $param as $setting_key => $setting_value ) {
			// Only process allowed fields.
			if ( ! in_array( $setting_key, $allowed_fields, true ) ) {
				continue;
			}

			$sanitized_key = sanitize_key( $setting_key );

			switch ( $setting_key ) {
				case 'table_rows':
					$sanitized[ $sanitized_key ] = absint( $setting_value );
					break;
				case 'date_format':
					$sanitized[ $sanitized_key ] = sanitize_text_field( $setting_value );
					break;
				case 'notification_emails':
					if ( is_array( $setting_value ) ) {
						$sanitized_emails = array();
						foreach ( $setting_value as $email ) {
							$trimmed_email = trim( $email );
							if ( ! empty( $trimmed_email ) ) {
								$clean_email = sanitize_email( $trimmed_email );
								if ( ! empty( $clean_email ) ) {
									$sanitized_emails[] = $clean_email;
								}
							}
						}
						$sanitized[ $sanitized_key ] = $sanitized_emails;
					} else {
						$sanitized[ $sanitized_key ] = array();
					}
					break;
			}
		}

		return $sanitized;
	}

	/**
	 * Validate a single setting.
	 *
	 * @param string $key   The setting key.
	 * @param mixed  $value The setting value.
	 */
	private function validate_single_setting( $key, $value ) {
		switch ( $key ) {
			case 'table_rows':
				$int_value = intval( $value );
				return ( $int_value >= 1 && $int_value <= 5 ) ? $int_value : false;

			case 'date_format':
				return in_array( $value, array( 'human', 'timestamp' ), true ) ? sanitize_text_field( $value ) : false;

			case 'notification_emails':
				if ( ! is_array( $value ) ) {
					return false;
				}

				// Must have at least 1 email, maximum 5.
				if ( count( $value ) < 1 || count( $value ) > 5 ) {
					return false;
				}

				$sanitized_emails = array();
				foreach ( $value as $email ) {
					$clean_email = sanitize_email( $email );
					if ( is_email( $clean_email ) ) {
						$sanitized_emails[] = $clean_email;
					} else {
						return false; // Invalid email found.
					}
				}

				return $sanitized_emails;

			default:
				return false;
		}
	}

	/**
	 * Sanitize setting value for REST API.
	 *
	 * @param mixed           $value     The value.
	 * @param WP_REST_Request $request   The request object.
	 */
	public function sanitize_setting_value( $value, $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$setting_key = $request->get_param( 'key' );
		return $this->validate_single_setting( $setting_key, $value );
	}
}