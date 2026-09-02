<?php
/**
 * Database options scanner (read-only).
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database scanner.
 */
class WPSMS_Database_Scanner {

	/**
	 * Scan a batch of options.
	 *
	 * @param string $scan_id Scan.
	 * @param int    $offset  Offset.
	 * @param int    $limit   Limit.
	 * @return array {findings, done, next_offset, scanned}
	 */
	public function scan_batch( $scan_id, $offset, $limit = 80 ) {
		global $wpdb;
		$offset = max( 0, (int) $offset );
		$limit  = min( 200, max( 10, (int) $limit ) );
		$needles = WPSMS_Patterns::database_needles();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_id, option_name, option_value FROM {$wpdb->options} WHERE option_name NOT LIKE %s ORDER BY option_id ASC LIMIT %d OFFSET %d",
				$wpdb->esc_like( '_transient' ) . '%',
				$limit,
				$offset
			),
			ARRAY_A
		);

		$findings = array();
		if ( empty( $rows ) ) {
			return array(
				'findings'    => array(),
				'done'        => true,
				'next_offset' => $offset,
				'scanned'     => 0,
			);
		}

		foreach ( $rows as $row ) {
			$name = $row['option_name'];
			if ( WPSMS_Redactor::is_secret_option( $name ) ) {
				continue;
			}
			if ( 0 === strpos( $name, '_site_transient' ) ) {
				continue;
			}
			$value = $row['option_value'];
			if ( ! is_string( $value ) || strlen( $value ) > 500000 ) {
				continue;
			}
			$plain = $value;
			if ( is_serialized( $value ) ) {
				$plain = $value;
			}

			foreach ( $needles as $needle ) {
				if ( false === stripos( $plain, $needle ) ) {
					continue;
				}
				$sev = 'low';
				if ( preg_match( '/eval\(|base64_decode|gzinflate|docusign|iframe/i', $needle ) ) {
					$sev = 'medium';
				}
				if ( preg_match( '/docusign|Complete with DocuSign/i', $needle ) ) {
					$sev = 'high';
				}
				$snippet = WPSMS_Redactor::redact( substr( $plain, max( 0, stripos( $plain, $needle ) - 80 ), 240 ) );
				$findings[] = array(
					'scan_id'            => $scan_id,
					'severity'           => $sev,
					'type'               => 'database',
					'location'           => 'option:' . $name,
					'line_number'        => 0,
					'matched_pattern'    => $needle,
					'snippet'            => $snippet,
					'title'              => sprintf( 'Database option contains “%s”', $needle ),
					'why_suspicious'     => 'Injected scripts and phishing templates are sometimes stored in wp_options (widgets, theme mods, active_plugins, cron). Many hits are legitimate (plugin settings).',
					'risk_explanation'   => 'Read-only finding. Values are not modified. Secrets are skipped.',
					'recommended_action' => 'Inspect this option in a database tool. Do not delete options blindly; you may break the site.',
					'file_hash'          => '',
					'file_mtime'         => 0,
					'file_size'          => strlen( $plain ),
					'file_perms'         => '',
					'file_owner'         => '',
					'confidence'         => 'requires investigation',
					'extra'              => array( 'option_name' => $name ),
				);
				break;
			}
		}

		return array(
			'findings'    => $findings,
			'done'        => count( $rows ) < $limit,
			'next_offset' => $offset + count( $rows ),
			'scanned'     => count( $rows ),
		);
	}
}
