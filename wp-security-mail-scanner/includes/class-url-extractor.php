<?php
/**
 * Extract external URLs from scanned content.
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * URL extractor.
 */
class WPSMS_URL_Extractor {

	/**
	 * Extract a limited number of external URLs.
	 *
	 * @param string $content  Content.
	 * @param string $relative Path.
	 * @param string $scan_id  Scan.
	 * @param array  $meta     File meta.
	 * @param string $file_sev File severity.
	 * @return array
	 */
	public static function from_content( $content, $relative, $scan_id, $meta, $file_sev ) {
		if ( ! preg_match_all( '#https?://[^\s\'"<>]+#i', $content, $m, PREG_OFFSET_CAPTURE ) ) {
			return array();
		}
		$skip_hosts = array(
			'wordpress.org',
			'api.wordpress.org',
			'downloads.wordpress.org',
			'github.com',
			'raw.githubusercontent.com',
			'schema.org',
			'w3.org',
			'jquery.com',
			'googleapis.com',
			'gstatic.com',
			'gravatar.com',
		);
		$out   = array();
		$seen  = array();
		$count = 0;
		foreach ( $m[0] as $hit ) {
			if ( $count >= 5 ) {
				break;
			}
			$url = rtrim( $hit[0], '.,);' );
			$parts = wp_parse_url( $url );
			if ( empty( $parts['host'] ) ) {
				continue;
			}
			$host = strtolower( $parts['host'] );
			$skip = false;
			foreach ( $skip_hosts as $allowed ) {
				if ( $host === $allowed || substr( $host, -strlen( '.' . $allowed ) ) === '.' . $allowed ) {
					$skip = true;
					break;
				}
			}
			if ( $skip ) {
				continue;
			}
			if ( isset( $seen[ $host ] ) ) {
				continue;
			}
			$seen[ $host ] = true;
			$line          = substr_count( substr( $content, 0, $hit[1] ), "\n" ) + 1;
			$suspicious    = (bool) preg_match( '/\.(ru|tk|top|xyz|click|pw|gq)$/i', $host );
			$sev           = $suspicious ? 'medium' : ( in_array( $file_sev, array( 'critical', 'high' ), true ) ? 'low' : 'info' );
			$out[]         = array(
				'scan_id'            => $scan_id,
				'severity'           => $sev,
				'type'               => 'url',
				'location'           => $relative,
				'line_number'        => $line,
				'matched_pattern'    => 'external_url',
				'snippet'            => WPSMS_Helpers::snippet_around_line( $content, $line ),
				'title'              => 'External URL: ' . $host,
				'why_suspicious'     => 'External URLs in unexpected files can point to remote payloads, phishing kits, or legitimate CDNs. This is a lead, not a block list.',
				'risk_explanation'   => $suspicious
					? 'The domain uses a TLD frequently seen in abuse reports. This is a suspicious indicator, not proof of malware.'
					: 'Recorded for investigation. The scanner does not automatically block domains.',
				'recommended_action' => 'Open the file at the listed line and confirm the domain is expected.',
				'file_hash'          => '',
				'file_mtime'         => isset( $meta['mtime'] ) ? $meta['mtime'] : 0,
				'file_size'          => isset( $meta['size'] ) ? $meta['size'] : 0,
				'file_perms'         => isset( $meta['perms'] ) ? $meta['perms'] : '',
				'file_owner'         => isset( $meta['owner'] ) ? $meta['owner'] : '',
				'confidence'         => $suspicious ? 'requires investigation' : 'likely legitimate',
				'extra'              => array(
					'url'    => esc_url_raw( $url ),
					'domain' => $host,
				),
			);
			++$count;
		}
		return $out;
	}
}
