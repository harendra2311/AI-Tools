<?php
/**
 * Uninstall handler. Removes scan data and options. Quarantined files are left in place
 * so an administrator can still investigate after plugin removal.
 *
 * @package WPSMS
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

delete_option( 'wpsms_settings' );
delete_option( 'wpsms_scan_job' );
delete_option( 'wpsms_last_scan' );
delete_option( 'wpsms_mail_incident' );
delete_transient( 'wpsms_checksums' );

$table = $wpdb->prefix . 'wpsms_findings';
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefixed internally.
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );

$upload = wp_upload_dir();
if ( ! empty( $upload['basedir'] ) ) {
	$state_dir = trailingslashit( $upload['basedir'] ) . 'wpsms-scan-state';
	if ( is_dir( $state_dir ) ) {
		$files = glob( $state_dir . '/*' );
		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					wp_delete_file( $file );
				}
			}
		}
	}
}
