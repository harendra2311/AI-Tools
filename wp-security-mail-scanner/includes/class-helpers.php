<?php
/**
 * Shared helpers.
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper utilities.
 */
class WPSMS_Helpers {

	/**
	 * Current user may manage the scanner.
	 *
	 * @return bool
	 */
	public static function current_user_can_scan() {
		return current_user_can( WPSMS_CAPABILITY );
	}

	/**
	 * Verify AJAX nonce and capability. Sends JSON error and dies on failure.
	 *
	 * @param string $nonce_action Nonce action.
	 */
	public static function require_ajax_access( $nonce_action = 'wpsms_admin' ) {
		if ( ! self::current_user_can_scan() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to use the scanner.', 'wp-security-mail-scanner' ) ), 403 );
		}
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed. Reload the page and try again.', 'wp-security-mail-scanner' ) ), 403 );
		}
	}

	/**
	 * Verify standard admin request (non-AJAX).
	 *
	 * @param string $nonce_action Action.
	 * @return bool
	 */
	public static function verify_admin_request( $nonce_action = 'wpsms_admin' ) {
		if ( ! self::current_user_can_scan() ) {
			return false;
		}
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		return (bool) wp_verify_nonce( $nonce, $nonce_action );
	}

	/**
	 * Severity labels.
	 *
	 * @return array
	 */
	public static function severity_labels() {
		return array(
			'critical' => __( 'Critical', 'wp-security-mail-scanner' ),
			'high'     => __( 'High', 'wp-security-mail-scanner' ),
			'medium'   => __( 'Medium', 'wp-security-mail-scanner' ),
			'low'      => __( 'Low', 'wp-security-mail-scanner' ),
			'info'     => __( 'Info', 'wp-security-mail-scanner' ),
		);
	}

	/**
	 * Type labels.
	 *
	 * @return array
	 */
	public static function type_labels() {
		return array(
			'file'     => __( 'Files', 'wp-security-mail-scanner' ),
			'mail'     => __( 'Mail', 'wp-security-mail-scanner' ),
			'uploads'  => __( 'Files', 'wp-security-mail-scanner' ),
			'plugin'   => __( 'Plugins', 'wp-security-mail-scanner' ),
			'theme'    => __( 'Themes', 'wp-security-mail-scanner' ),
			'cron'     => __( 'Cron', 'wp-security-mail-scanner' ),
			'database' => __( 'Database', 'wp-security-mail-scanner' ),
			'user'     => __( 'Users', 'wp-security-mail-scanner' ),
			'core'     => __( 'Files', 'wp-security-mail-scanner' ),
			'url'      => __( 'Files', 'wp-security-mail-scanner' ),
		);
	}

	/**
	 * Normalize line endings and clip a snippet.
	 *
	 * @param string $content Content.
	 * @param int    $line    1-based line.
	 * @param int    $radius  Context lines.
	 * @return string
	 */
	public static function snippet_around_line( $content, $line, $radius = 2 ) {
		$lines = preg_split( "/\r\n|\n|\r/", (string) $content );
		$idx   = max( 0, (int) $line - 1 );
		$start = max( 0, $idx - $radius );
		$end   = min( count( $lines ) - 1, $idx + $radius );
		$out   = array();
		for ( $i = $start; $i <= $end; $i++ ) {
			$out[] = ( $i + 1 ) . ': ' . $lines[ $i ];
		}
		$snippet = implode( "\n", $out );
		if ( strlen( $snippet ) > 1200 ) {
			$snippet = substr( $snippet, 0, 1200 ) . '…';
		}
		return $snippet;
	}

	/**
	 * SHA-256 of a file if readable.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	public static function file_sha256( $path ) {
		if ( ! is_readable( $path ) || ! is_file( $path ) ) {
			return '';
		}
		$hash = hash_file( 'sha256', $path );
		return is_string( $hash ) ? $hash : '';
	}

	/**
	 * File metadata.
	 *
	 * @param string $path Path.
	 * @return array
	 */
	public static function file_meta( $path ) {
		$meta = array(
			'size'  => 0,
			'mtime' => 0,
			'perms' => '',
			'owner' => '',
			'hash'  => '',
		);
		if ( ! is_file( $path ) ) {
			return $meta;
		}
		$stat           = @stat( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$meta['size']   = $stat ? (int) $stat['size'] : (int) filesize( $path );
		$meta['mtime']  = $stat ? (int) $stat['mtime'] : (int) filemtime( $path );
		$meta['perms']  = is_readable( $path ) ? substr( sprintf( '%o', fileperms( $path ) ), -4 ) : '';
		if ( $stat && function_exists( 'posix_getpwuid' ) ) {
			$pw = @posix_getpwuid( $stat['uid'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( is_array( $pw ) && isset( $pw['name'] ) ) {
				$meta['owner'] = $pw['name'];
			}
		}
		return $meta;
	}

	/**
	 * Plugin settings merged with defaults.
	 *
	 * @return array
	 */
	public static function settings() {
		$saved = get_option( 'wpsms_settings', array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $saved, WPSMS_Install::default_settings() );
	}

	/**
	 * Format timestamp.
	 *
	 * @param int $ts Unix time.
	 * @return string
	 */
	public static function format_datetime( $ts ) {
		$ts = (int) $ts;
		if ( $ts <= 0 ) {
			return '—';
		}
		if ( function_exists( 'wp_date' ) ) {
			return wp_date( 'Y-m-d H:i:s', $ts );
		}
		return gmdate( 'Y-m-d H:i:s', $ts );
	}

	/**
	 * Known legitimate mail-related plugin slugs.
	 *
	 * @return array
	 */
	public static function known_mail_plugin_slugs() {
		return array(
			'woocommerce',
			'contact-form-7',
			'wpforms-lite',
			'wpforms',
			'gravityforms',
			'wp-mail-smtp',
			'fluent-smtp',
			'post-smtp',
			'easy-wp-smtp',
			'mailpoet',
			'newsletter',
			'woocommerce-subscriptions',
			'elementor',
			'elementor-pro',
			'jetpack',
			'akismet',
			'wordfence',
			'sucuri-scanner',
		);
	}
}
