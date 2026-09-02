<?php
/**
 * Quarantine handling with confirmation and backups.
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Quarantine.
 */
class WPSMS_Quarantine {

	/**
	 * Quarantine directory.
	 *
	 * @return string
	 */
	public static function dir() {
		$dir = WPSMS_PLUGIN_DIR . 'quarantine';
		if ( ! is_dir( $dir ) ) {
			WPSMS_Install::create_protected_dirs();
		}
		return $dir;
	}

	/**
	 * Backup directory.
	 *
	 * @return string
	 */
	public static function backup_dir() {
		$dir = self::dir() . '/backups';
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
			file_put_contents( $dir . '/.htaccess', "Deny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}
		return $dir;
	}

	/**
	 * Copy a backup of a file. Returns backup path.
	 *
	 * @param string $path Absolute safe path.
	 * @return string|\WP_Error
	 */
	public static function backup( $path ) {
		if ( ! is_readable( $path ) ) {
			return new WP_Error( 'wpsms_backup', __( 'File is not readable.', 'wp-security-mail-scanner' ) );
		}
		$name = gmdate( 'Ymd-His' ) . '-' . hash( 'sha256', $path ) . '-' . basename( $path ) . '.bak';
		$dest = trailingslashit( self::backup_dir() ) . $name;
		if ( ! copy( $path, $dest ) ) {
			return new WP_Error( 'wpsms_backup', __( 'Could not create backup copy.', 'wp-security-mail-scanner' ) );
		}
		return $dest;
	}

	/**
	 * Quarantine a file (move + disable execution).
	 *
	 * @param string $path Path.
	 * @return array|\WP_Error
	 */
	public static function quarantine( $path ) {
		$paths = new WPSMS_Paths();
		if ( $paths->is_protected_from_delete( $path ) ) {
			return new WP_Error( 'wpsms_protected', __( 'WordPress core files cannot be quarantined by this plugin.', 'wp-security-mail-scanner' ) );
		}
		if ( $paths->is_self( $path ) ) {
			return new WP_Error( 'wpsms_protected', __( 'The scanner cannot quarantine its own files.', 'wp-security-mail-scanner' ) );
		}
		$backup = self::backup( $path );
		if ( is_wp_error( $backup ) ) {
			return $backup;
		}
		$hash = WPSMS_Helpers::file_sha256( $path );
		$id   = wp_generate_password( 10, false, false );
		$dest = trailingslashit( self::dir() ) . $id . '-' . basename( $path ) . '.quarantined';
		if ( ! @rename( $path, $dest ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( ! copy( $path, $dest ) ) {
				return new WP_Error( 'wpsms_quarantine', __( 'Could not move the file into quarantine.', 'wp-security-mail-scanner' ) );
			}
			wp_delete_file( $path );
		}
		$meta = array(
			'original'       => $path,
			'relative'       => $paths->relative( $path ),
			'hash'           => $hash,
			'backup'         => $backup,
			'quarantined_at' => time(),
			'user'       => get_current_user_id(),
		);
		file_put_contents( $dest . '.json', wp_json_encode( $meta ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		return array(
			'dest'   => $dest,
			'backup' => $backup,
			'hash'   => $hash,
		);
	}

	/**
	 * Delete after backup and confirmation.
	 *
	 * @param string $path Path.
	 * @return array|\WP_Error
	 */
	public static function delete_file( $path ) {
		$paths = new WPSMS_Paths();
		if ( $paths->is_protected_from_delete( $path ) ) {
			return new WP_Error( 'wpsms_protected', __( 'WordPress core files cannot be deleted by this plugin.', 'wp-security-mail-scanner' ) );
		}
		if ( $paths->is_plugin_dir( $path ) || $paths->is_theme_dir( $path ) ) {
			return new WP_Error( 'wpsms_protected', __( 'Plugin and theme files cannot be deleted from this scanner. Remove them from the Plugins/Themes screens after investigation.', 'wp-security-mail-scanner' ) );
		}
		if ( $paths->is_self( $path ) ) {
			return new WP_Error( 'wpsms_protected', __( 'The scanner cannot delete its own files.', 'wp-security-mail-scanner' ) );
		}
		$backup = self::backup( $path );
		if ( is_wp_error( $backup ) ) {
			return $backup;
		}
		$hash = WPSMS_Helpers::file_sha256( $path );
		if ( ! wp_delete_file( $path ) && file_exists( $path ) ) {
			return new WP_Error( 'wpsms_delete', __( 'Could not delete the file.', 'wp-security-mail-scanner' ) );
		}
		return array(
			'backup' => $backup,
			'hash'   => $hash,
		);
	}
}
