<?php
/**
 * Security report export (JSON/CSV/HTML) with secrets redacted.
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Report builder.
 */
class WPSMS_Report {

	/**
	 * Build report array.
	 *
	 * @param string $scan_id Scan.
	 * @return array
	 */
	public static function build( $scan_id ) {
		global $wp_version;
		$findings = WPSMS_Findings_Store::all( $scan_id );
		$counts   = WPSMS_Findings_Store::counts( $scan_id );
		$job      = WPSMS_Scanner::get_job();
		$last     = WPSMS_Scanner::last_scan();
		$theme    = wp_get_theme();
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins = get_plugins();
		$active  = (array) get_option( 'active_plugins', array() );
		$active_names = array();
		foreach ( $active as $file ) {
			if ( isset( $plugins[ $file ]['Name'] ) ) {
				$active_names[] = $plugins[ $file ]['Name'] . ' ' . $plugins[ $file ]['Version'];
			}
		}

		$mail     = array_values(
			array_filter(
				$findings,
				function ( $f ) {
					return isset( $f['type'] ) && 'mail' === $f['type'];
				}
			)
		);
		$users    = array_values(
			array_filter(
				$findings,
				function ( $f ) {
					return isset( $f['type'] ) && 'user' === $f['type'];
				}
			)
		);
		$crons    = array_values(
			array_filter(
				$findings,
				function ( $f ) {
					return isset( $f['type'] ) && 'cron' === $f['type'] && in_array( $f['severity'], array( 'medium', 'high', 'critical' ), true );
				}
			)
		);
		$db       = array_values(
			array_filter(
				$findings,
				function ( $f ) {
					return isset( $f['type'] ) && 'database' === $f['type'];
				}
			)
		);
		$core     = array_values(
			array_filter(
				$findings,
				function ( $f ) {
					return isset( $f['type'] ) && 'core' === $f['type'];
				}
			)
		);

		$incident = get_option( 'wpsms_mail_incident', array() );

		$recommended = array(
			__( 'Review Critical and High findings before Medium/Low noise.', 'wp-security-mail-scanner' ),
			__( 'Inspect PHP files under wp-content/uploads and cache directories.', 'wp-security-mail-scanner' ),
			__( 'Verify every administrator account.', 'wp-security-mail-scanner' ),
			__( 'Compare custom cron hooks with installed plugins.', 'wp-security-mail-scanner' ),
			__( 'Do not delete files until a backup exists and two confirmations are given.', 'wp-security-mail-scanner' ),
			__( 'Rotate SMTP/API credentials if an unauthorized mailer is confirmed (do so in your mail provider; this plugin does not store those secrets in reports).', 'wp-security-mail-scanner' ),
		);

		$report = array(
			'scan_date'          => WPSMS_Helpers::format_datetime( isset( $last['finished_at'] ) ? $last['finished_at'] : time() ),
			'wordpress_version'  => $wp_version,
			'php_version'        => PHP_VERSION,
			'active_theme'       => $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ),
			'active_plugins'     => $active_names,
			'files_scanned'      => isset( $last['files_scanned'] ) ? (int) $last['files_scanned'] : 0,
			'findings_summary'   => $counts,
			'mail_findings'      => self::summarize_rows( $mail ),
			'suspicious_users'   => self::summarize_rows( $users ),
			'suspicious_cron'    => self::summarize_rows( $crons ),
			'database_findings'  => self::summarize_rows( $db ),
			'core_integrity'     => self::summarize_rows( $core ),
			'mail_incident'      => $incident,
			'recommended_actions'=> $recommended,
			'scan_id'            => $scan_id,
			'mode'               => isset( $job['mode'] ) ? $job['mode'] : ( isset( $last['mode'] ) ? $last['mode'] : '' ),
		);

		return $report;
	}

	/**
	 * Slim rows.
	 *
	 * @param array $rows Rows.
	 * @return array
	 */
	protected static function summarize_rows( array $rows ) {
		$out = array();
		foreach ( $rows as $row ) {
			$out[] = array(
				'severity'  => $row['severity'],
				'type'      => $row['type'],
				'location'  => $row['location'],
				'line'      => (int) $row['line_number'],
				'title'     => WPSMS_Redactor::redact( $row['title'] ),
				'pattern'   => $row['matched_pattern'],
				'confidence'=> $row['confidence'],
			);
		}
		return $out;
	}

	/**
	 * JSON.
	 *
	 * @param array $report Report.
	 * @return string
	 */
	public static function to_json( array $report ) {
		return wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * CSV.
	 *
	 * @param array $report Report.
	 * @return string
	 */
	public static function to_csv( array $report ) {
		$fh = fopen( 'php://temp', 'r+' );
		fputcsv( $fh, array( 'severity', 'type', 'location', 'line', 'title', 'pattern', 'confidence' ) );
		$sets = array( 'mail_findings', 'suspicious_users', 'suspicious_cron', 'database_findings', 'core_integrity' );
		foreach ( $sets as $set ) {
			if ( empty( $report[ $set ] ) || ! is_array( $report[ $set ] ) ) {
				continue;
			}
			foreach ( $report[ $set ] as $row ) {
				fputcsv(
					$fh,
					array(
						$row['severity'],
						$row['type'],
						$row['location'],
						$row['line'],
						$row['title'],
						$row['pattern'],
						$row['confidence'],
					)
				);
			}
		}
		rewind( $fh );
		$csv = stream_get_contents( $fh );
		fclose( $fh );
		return $csv;
	}

	/**
	 * HTML.
	 *
	 * @param array $report Report.
	 * @return string
	 */
	public static function to_html( array $report ) {
		ob_start();
		echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>WP Security &amp; Mail Scanner Report</title>';
		echo '<style>body{font-family:system-ui,sans-serif;margin:24px;color:#1d2327}table{border-collapse:collapse;width:100%}td,th{border:1px solid #c3c4c7;padding:6px;text-align:left}h1{font-size:22px}</style></head><body>';
		echo '<h1>WP Security &amp; Mail Scanner Report</h1>';
		echo '<p>Scan date: ' . esc_html( $report['scan_date'] ) . '</p>';
		echo '<p>WordPress: ' . esc_html( $report['wordpress_version'] ) . ' / PHP: ' . esc_html( $report['php_version'] ) . '</p>';
		echo '<p>Theme: ' . esc_html( $report['active_theme'] ) . '</p>';
		echo '<p>Files scanned: ' . esc_html( (string) $report['files_scanned'] ) . '</p>';
		echo '<h2>Summary</h2><ul>';
		foreach ( $report['findings_summary'] as $k => $v ) {
			echo '<li>' . esc_html( $k . ': ' . $v ) . '</li>';
		}
		echo '</ul><h2>Recommended actions</h2><ul>';
		foreach ( $report['recommended_actions'] as $a ) {
			echo '<li>' . esc_html( $a ) . '</li>';
		}
		echo '</ul><h2>Active plugins</h2><ul>';
		foreach ( $report['active_plugins'] as $p ) {
			echo '<li>' . esc_html( $p ) . '</li>';
		}
		echo '</ul></body></html>';
		return (string) ob_get_clean();
	}
}
