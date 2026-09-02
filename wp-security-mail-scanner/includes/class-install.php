<?php
/**
 * Install and schema.
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database installer.
 */
class WPSMS_Install {

	const SCHEMA_VERSION = 1;

	/**
	 * Activation callback.
	 */
	public static function activate() {
		self::create_tables();
		self::create_protected_dirs();
		add_option( 'wpsms_schema_version', self::SCHEMA_VERSION );
		add_option( 'wpsms_settings', self::default_settings() );
	}

	/**
	 * Upgrade if needed.
	 */
	public static function maybe_upgrade() {
		$current = (int) get_option( 'wpsms_schema_version', 0 );
		if ( $current < self::SCHEMA_VERSION ) {
			self::create_tables();
			update_option( 'wpsms_schema_version', self::SCHEMA_VERSION );
		}
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function default_settings() {
		return array(
			'batch_size'          => 15,
			'inventory_batch'     => 120,
			'max_file_bytes'      => 262144,
			'known_admin_logins'  => '',
			'exclude_paths'       => "node_modules\nvendor\n.git\n.svn\ncache\nupgrade",
			'quick_skip_core'     => 1,
		);
	}

	/**
	 * Create findings table.
	 */
	public static function create_tables() {
		global $wpdb;
		$table           = $wpdb->prefix . 'wpsms_findings';
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			scan_id varchar(32) NOT NULL,
			severity varchar(20) NOT NULL DEFAULT 'info',
			type varchar(40) NOT NULL DEFAULT 'file',
			location text NOT NULL,
			line_number int(11) NOT NULL DEFAULT 0,
			matched_pattern varchar(255) NOT NULL DEFAULT '',
			snippet text NULL,
			title text NOT NULL,
			why_suspicious text NULL,
			risk_explanation text NULL,
			recommended_action text NULL,
			file_hash varchar(64) NOT NULL DEFAULT '',
			file_mtime int(11) NOT NULL DEFAULT 0,
			file_size bigint(20) NOT NULL DEFAULT 0,
			file_perms varchar(20) NOT NULL DEFAULT '',
			file_owner varchar(64) NOT NULL DEFAULT '',
			confidence varchar(40) NOT NULL DEFAULT 'suspicious',
			extra longtext NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY scan_id (scan_id),
			KEY severity (severity),
			KEY type (type)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Protected scan-state and quarantine directories.
	 */
	public static function create_protected_dirs() {
		$dirs = array(
			WPSMS_PLUGIN_DIR . 'quarantine',
			WPSMS_PLUGIN_DIR . 'cache',
		);
		$deny = "Deny from all\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n";
		foreach ( $dirs as $dir ) {
			if ( ! is_dir( $dir ) ) {
				wp_mkdir_p( $dir );
			}
			$ht = trailingslashit( $dir ) . '.htaccess';
			if ( ! file_exists( $ht ) ) {
				file_put_contents( $ht, $deny ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			}
			$index = trailingslashit( $dir ) . 'index.php';
			if ( ! file_exists( $index ) ) {
				file_put_contents( $index, "<?php\n// Silence.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			}
		}
	}
}
