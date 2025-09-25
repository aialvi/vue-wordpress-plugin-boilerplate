<?php
/**
 * Security utility class for additional security measures.
 *
 * @package aialvi-page-ranks
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Security class.
 */
class AIALVI_Vue_Plugin_Security {


	/**
	 * Rate limiting storage.
	 *
	 * @var array
	 */
	private static $rate_limits = array();

	/**
	 * Validate and sanitize array data.
	 *
	 * @param array $data   Input data to sanitize.
	 * @param array $schema Validation schema.
	 * @return array|WP_Error Sanitized data or error.
	 */
	public static function sanitize_array_data( $data, $schema = array() ) {
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'invalid_data', __( 'Data must be an array', 'aialvi-page-ranks' ) );
		}

		$sanitized = array();

		foreach ( $data as $key => $value ) {
			$sanitized_key = sanitize_key( $key );

			// Check if key is allowed.
			if ( ! empty( $schema ) && ! array_key_exists( $sanitized_key, $schema ) ) {
				continue; // Skip unknown keys.
			}

			// Get expected type from schema.
			$expected_type = $schema[ $sanitized_key ] ?? 'text';

			switch ( $expected_type ) {
				case 'email':
					$sanitized[ $sanitized_key ] = sanitize_email( $value );
					break;
				case 'url':
					$sanitized[ $sanitized_key ] = esc_url_raw( $value );
					break;
				case 'textarea':
					$sanitized[ $sanitized_key ] = sanitize_textarea_field( $value );
					break;
				case 'html':
					$sanitized[ $sanitized_key ] = wp_kses_post( $value );
					break;
				case 'int':
					$sanitized[ $sanitized_key ] = absint( $value );
					break;
				case 'float':
					$sanitized[ $sanitized_key ] = floatval( $value );
					break;
				case 'bool':
					$sanitized[ $sanitized_key ] = rest_sanitize_boolean( $value );
					break;
				case 'array':
					if ( is_array( $value ) ) {
						$sanitized[ $sanitized_key ] = array_map( 'sanitize_text_field', $value );
					} else {
						$sanitized[ $sanitized_key ] = array();
					}
					break;
				case 'text':
				default:
					$sanitized[ $sanitized_key ] = sanitize_text_field( $value );
					break;
			}
		}

		return $sanitized;
	}

	/**
	 * Validate user input against XSS and other attacks.
	 *
	 * @param string $input Input to validate.
	 * @param string $type  Type of validation.
	 * @return bool True if valid, false otherwise.
	 */
	public static function validate_input( $input, $type = 'text' ) {
		if ( empty( $input ) ) {
			return false;
		}

		switch ( $type ) {
			case 'email':
				return is_email( $input );
			case 'url':
				return false !== filter_var( $input, FILTER_VALIDATE_URL );
			case 'int':
				return false !== filter_var( $input, FILTER_VALIDATE_INT );
			case 'float':
				return false !== filter_var( $input, FILTER_VALIDATE_FLOAT );
			case 'alphanumeric':
				return ctype_alnum( str_replace( array( ' ', '-', '_' ), '', $input ) );
			case 'filename':
				return preg_match( '/^[a-zA-Z0-9._-]+$/', $input );
			case 'text':
			default:
				// Check for common XSS patterns.
				$dangerous_patterns = array(
					'/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
					'/javascript:/i',
					'/on\w+\s*=/i',
					'/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi',
					'/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/mi',
					'/<embed\b[^<]*(?:(?!<\/embed>)<[^<]*)*<\/embed>/mi',
				);

				foreach ( $dangerous_patterns as $pattern ) {
					if ( preg_match( $pattern, $input ) ) {
						return false;
					}
				}

				return true;
		}
	}

	/**
	 * Rate limiting for AJAX requests.
	 *
	 * @param string $action Action being performed.
	 * @param int    $limit  Number of requests allowed.
	 * @param int    $window Time window in seconds.
	 * @return bool True if within limit, false otherwise.
	 */
	public static function check_rate_limit( $action, $limit = 10, $window = 60 ) {
		$user_ip      = self::get_user_ip();
		$key          = $action . '_' . $user_ip;
		$current_time = time();

		// Initialize if not exists.
		if ( ! isset( self::$rate_limits[ $key ] ) ) {
			self::$rate_limits[ $key ] = array();
		}

		// Remove old entries.
		self::$rate_limits[ $key ] = array_filter(
			self::$rate_limits[ $key ],
			function ( $timestamp ) use ( $current_time, $window ) {
				return ( $current_time - $timestamp ) < $window;
			}
		);

		// Check if limit exceeded.
		if ( count( self::$rate_limits[ $key ] ) >= $limit ) {
			return false;
		}

		// Add current request.
		self::$rate_limits[ $key ][] = $current_time;

		return true;
	}

	/**
	 * Get user IP address securely.
	 *
	 * @return string User IP address.
	 */
	public static function get_user_ip() {
		$ip_keys = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( true === array_key_exists( $key, $_SERVER ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );

				// Handle comma-separated IPs.
				if ( false !== strpos( $ip, ',' ) ) {
					$ip = explode( ',', $ip )[0];
				}

				$ip = trim( $ip );

				// Validate IP.
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}

	/**
	 * Generate secure random token.
	 *
	 * @param int $length Token length.
	 * @return string Random token.
	 */
	public static function generate_token( $length = 32 ) {
		if ( function_exists( 'wp_generate_password' ) ) {
			return wp_generate_password( $length, false );
		}

		return bin2hex( random_bytes( $length / 2 ) );
	}

	/**
	 * Validate file upload security.
	 *
	 * @param array $file          $_FILES array element.
	 * @param array $allowed_types Allowed MIME types.
	 * @param int   $max_size      Maximum file size in bytes.
	 * @return bool|WP_Error True if valid, WP_Error otherwise.
	 */
	public static function validate_file_upload( $file, $allowed_types = array(), $max_size = 2097152 ) {
		// Check if file was uploaded.
		if ( ! isset( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new WP_Error( 'invalid_upload', __( 'Invalid file upload', 'aialvi-page-ranks' ) );
		}

		// Check file size.
		if ( $file['size'] > $max_size ) {
			return new WP_Error( 'file_too_large', __( 'File size exceeds limit', 'aialvi-page-ranks' ) );
		}

		// Check MIME type.
		$file_type = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );

		if ( ! empty( $allowed_types ) && ! in_array( $file_type['type'], $allowed_types, true ) ) {
			return new WP_Error( 'invalid_file_type', __( 'File type not allowed', 'aialvi-page-ranks' ) );
		}

		// Check for executable files.
		$dangerous_extensions = array( 'php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'bat', 'sh', 'com', 'scr', 'vbs', 'js' );
		$file_extension       = pathinfo( $file['name'], PATHINFO_EXTENSION );

		if ( in_array( strtolower( $file_extension ), $dangerous_extensions, true ) ) {
			return new WP_Error( 'dangerous_file', __( 'File type not allowed for security reasons', 'aialvi-page-ranks' ) );
		}

		return true;
	}

	/**
	 * Log security events.
	 *
	 * @param string $event   Event description.
	 * @param array  $context Additional context.
	 */
	public static function log_security_event( $event, $context = array() ) {
		$log_entry = array(
			'timestamp'  => current_time( 'mysql' ),
			'event'      => sanitize_text_field( $event ),
			'user_ip'    => self::get_user_ip(),
			'user_id'    => get_current_user_id(),
			'user_agent' => sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ),
			'context'    => $context,
		);

		// Store in database for serious events.
		$serious_events = array( 'failed_nonce', 'permission_denied', 'rate_limit_exceeded', 'xss_attempt' );
		if ( in_array( $event, $serious_events, true ) ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'aialvi_vue_security_log';

			// Create table if it doesn't exist.
			self::create_security_log_table();

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$table_name,
				array(
					'timestamp'  => $log_entry['timestamp'],
					'event'      => $log_entry['event'],
					'user_ip'    => $log_entry['user_ip'],
					'user_id'    => $log_entry['user_id'],
					'user_agent' => $log_entry['user_agent'],
					'context'    => wp_json_encode( $log_entry['context'] ),
				),
				array(
					'%s',
					'%s',
					'%s',
					'%d',
					'%s',
					'%s',
				)
			);
		}
	}

	/**
	 * Create security log table.
	 */
	private static function create_security_log_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'aialvi_vue_security_log';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			event varchar(255) NOT NULL,
			user_ip varchar(45) NOT NULL,
			user_id bigint(20) DEFAULT 0,
			user_agent text,
			context longtext,
			PRIMARY KEY (id),
			INDEX idx_timestamp (timestamp),
			INDEX idx_event (event),
			INDEX idx_user_ip (user_ip)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		dbDelta( $sql );
	}
}
