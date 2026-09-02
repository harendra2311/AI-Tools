<?php
/**
 * Safe file actions (view, hash, download backup, quarantine, delete).
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * File actions.
 */
class WPSMS_File_Actions {

	/**
	 * Resolve user-supplied path.
	 *
	 * @param string $user_path Path.
	 * @return string|\WP_Error
	 */
	public static function resolve( $user_path ) {
		$paths = new WPSMS_Paths();
		$real  = $paths->resolve_safe( $user_path );
		if ( ! $real || ! is_file( $real ) ) {
			return new WP_Error( 'wpsms_path', __( 'Invalid or inaccessible file path.', 'wp-security-mail-scanner' ) );
		}
		return $real;
	}

	/**
	 * View (truncated, redacted).
	 *
	 * @param string $user_path Path.
	 * @return array|\WP_Error
	 */
	public static function view( $user_path ) {
		$real = self::resolve( $user_path );
		if ( is_wp_error( $real ) ) {
			return $real;
		}
		if ( filesize( $real ) > 400000 ) {
			return new WP_Error( 'wpsms_size', __( 'File is too large to display. Download a backup instead.', 'wp-security-mail-scanner' ) );
		}
		$content = file_get_contents( $real ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = WPSMS_Redactor::redact( (string) $content );
		if ( strlen( $content ) > 100000 ) {
			$content = substr( $content, 0, 100000 ) . "\n…";
		}
		$paths = new WPSMS_Paths();
		$meta  = WPSMS_Helpers::file_meta( $real );
		return array(
			'path'    => $paths->relative( $real ),
			'content' => $content,
			'meta'    => $meta,
			'hash'    => WPSMS_Helpers::file_sha256( $real ),
		);
	}

	/**
	 * Hash.
	 *
	 * @param string $user_path Path.
	 * @return array|\WP_Error
	 */
	public static function hash( $user_path ) {
		$real = self::resolve( $user_path );
		if ( is_wp_error( $real ) ) {
			return $real;
		}
		$paths = new WPSMS_Paths();
		$meta  = WPSMS_Helpers::file_meta( $real );
		return array(
			'path'  => $paths->relative( $real ),
			'hash'  => WPSMS_Helpers::file_sha256( $real ),
			'meta'  => $meta,
		);
	}

	/**
	 * Stream a backup download (copy, never the live unlink).
	 *
	 * @param string $user_path Path.
	 */
	public static function download_backup( $user_path ) {
		$real = self::resolve( $user_path );
		if ( is_wp_error( $real ) ) {
			wp_die( esc_html( $real->get_error_message() ) );
		}
		$backup = WPSMS_Quarantine::backup( $real );
		if ( is_wp_error( $backup ) ) {
			wp_die( esc_html( $backup->get_error_message() ) );
		}
		$filename = basename( $real ) . '.bak';
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
		header( 'Content-Length: ' . (string) filesize( $backup ) );
		header( 'X-Content-Type-Options: nosniff' );
		readfile( $backup ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}

	/**
	 * Quarantine with confirmation token.
	 *
	 * @param string $user_path Path.
	 * @param string $confirm   Must be 'confirm'.
	 * @return array|\WP_Error
	 */
	public static function quarantine( $user_path, $confirm ) {
		if ( 'confirm' !== $confirm ) {
			return new WP_Error( 'wpsms_confirm', __( 'Quarantine requires explicit administrator confirmation.', 'wp-security-mail-scanner' ) );
		}
		$real = self::resolve( $user_path );
		if ( is_wp_error( $real ) ) {
			return $real;
		}
		return WPSMS_Quarantine::quarantine( $real );
	}

	/**
	 * Delete with two confirmations.
	 *
	 * @param string $user_path Path.
	 * @param string $confirm1  First.
	 * @param string $confirm2  Second.
	 * @return array|\WP_Error
	 */
	public static function delete( $user_path, $confirm1, $confirm2 ) {
		if ( 'confirm' !== $confirm1 || 'delete' !== $confirm2 ) {
			return new WP_Error( 'wpsms_confirm', __( 'Deletion requires two confirmations (confirm + delete).', 'wp-security-mail-scanner' ) );
		}
		$real = self::resolve( $user_path );
		if ( is_wp_error( $real ) ) {
			return $real;
		}
		return WPSMS_Quarantine::delete_file( $real );
	}
}
