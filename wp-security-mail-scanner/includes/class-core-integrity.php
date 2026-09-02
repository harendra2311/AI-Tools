<?php
/**
 * WordPress core checksum comparison (read-only).
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core integrity.
 */
class WPSMS_Core_Integrity {

	/**
	 * Run checksum comparison.
	 *
	 * @param string $scan_id Scan.
	 * @return array
	 */
	public function scan( $scan_id ) {
		global $wp_version;
		$locale = function_exists( 'get_locale' ) ? get_locale() : 'en_US';
		$url    = add_query_arg(
			array(
				'version' => $wp_version,
				'locale'  => $locale,
			),
			'https://api.wordpress.org/core/checksums/1.0/'
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 12,
				'headers' => array( 'Accept' => 'application/json' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( $this->info( $scan_id, 'checksum_unavailable', __( 'Could not download official WordPress checksums.', 'wp-security-mail-scanner' ), $response->get_error_message() ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( 200 !== (int) $code || empty( $body['checksums'] ) || ! is_array( $body['checksums'] ) ) {
			return array( $this->info( $scan_id, 'checksum_unavailable', __( 'Official checksums were not available for this version/locale.', 'wp-security-mail-scanner' ), 'HTTP ' . $code ) );
		}

		$checksums = $body['checksums'];
		$findings  = array();
		$root      = rtrim( ABSPATH, '/\\' ) . '/';

		foreach ( $checksums as $rel => $md5 ) {
			if ( 0 === strpos( $rel, 'wp-content/' ) ) {
				continue;
			}
			$path = $root . str_replace( '/', DIRECTORY_SEPARATOR, $rel );
			if ( ! file_exists( $path ) ) {
				$findings[] = $this->core_row( $scan_id, $rel, 'missing', 'high', __( 'Missing core file', 'wp-security-mail-scanner' ) );
				continue;
			}
			$actual = md5_file( $path );
			if ( is_string( $actual ) && is_string( $md5 ) && strtolower( $actual ) !== strtolower( $md5 ) ) {
				$findings[] = $this->core_row( $scan_id, $rel, 'modified', 'high', __( 'Modified core file', 'wp-security-mail-scanner' ) );
			}
		}

		$unexpected = $this->unexpected_in_core_dirs( $checksums );
		foreach ( $unexpected as $rel ) {
			$findings[] = $this->core_row( $scan_id, $rel, 'unexpected', 'high', __( 'Unexpected file in core directory', 'wp-security-mail-scanner' ) );
		}

		if ( empty( $findings ) ) {
			$findings[] = $this->info( $scan_id, 'core_ok', __( 'Core checksums match the official WordPress package for scanned core files.', 'wp-security-mail-scanner' ), '' );
		}

		return $findings;
	}

	/**
	 * Unexpected PHP in wp-admin / wp-includes.
	 *
	 * @param array $checksums Checksums.
	 * @return array
	 */
	protected function unexpected_in_core_dirs( array $checksums ) {
		$out   = array();
		$dirs  = array( ABSPATH . 'wp-admin', ABSPATH . 'wp-includes' );
		$known = array_fill_keys( array_keys( $checksums ), true );
		foreach ( $dirs as $dir ) {
			if ( ! is_dir( $dir ) ) {
				continue;
			}
			$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );
			$count    = 0;
			foreach ( $iterator as $file ) {
				if ( ++$count > 8000 ) {
					break;
				}
				if ( ! $file->isFile() ) {
					continue;
				}
				$name = $file->getFilename();
				if ( ! preg_match( '/\.(php|js)$/i', $name ) ) {
					continue;
				}
				$full = $file->getPathname();
				$rel  = ltrim( str_replace( '\\', '/', substr( $full, strlen( rtrim( ABSPATH, '/\\' ) ) ) ), '/' );
				if ( ! isset( $known[ $rel ] ) ) {
					$out[] = $rel;
				}
				if ( count( $out ) >= 50 ) {
					return $out;
				}
			}
		}
		return $out;
	}

	/**
	 * Core finding.
	 *
	 * @param string $scan_id Scan.
	 * @param string $rel Rel.
	 * @param string $kind Kind.
	 * @param string $sev Sev.
	 * @param string $title Title.
	 * @return array
	 */
	protected function core_row( $scan_id, $rel, $kind, $sev, $title ) {
		$path = ABSPATH . $rel;
		$meta = is_file( $path ) ? WPSMS_Helpers::file_meta( $path ) : array(
			'size' => 0,
			'mtime' => 0,
			'perms' => '',
			'owner' => '',
		);
		return array(
			'scan_id'            => $scan_id,
			'severity'           => $sev,
			'type'               => 'core',
			'location'           => $rel,
			'line_number'        => 0,
			'matched_pattern'    => $kind,
			'snippet'            => strtoupper( $kind ),
			'title'              => $title . ': ' . $rel,
			'why_suspicious'     => 'Core files should match the official WordPress package. Differences can be malware, a failed update, or a legal mu-file placed in the wrong folder.',
			'risk_explanation'   => 'This plugin never overwrites core files. Treat modified/unexpected PHP as a high-priority investigation item.',
			'recommended_action' => 'Compare with a fresh copy of the same WordPress version. Restore from a trusted package only after backup.',
			'file_hash'          => is_file( $path ) ? WPSMS_Helpers::file_sha256( $path ) : '',
			'file_mtime'         => $meta['mtime'],
			'file_size'          => $meta['size'],
			'file_perms'         => $meta['perms'],
			'file_owner'         => $meta['owner'],
			'confidence'         => 'requires investigation',
			'extra'              => array( 'kind' => $kind ),
		);
	}

	/**
	 * Info row.
	 *
	 * @param string $scan_id Scan.
	 * @param string $code Code.
	 * @param string $title Title.
	 * @param string $detail Detail.
	 * @return array
	 */
	protected function info( $scan_id, $code, $title, $detail ) {
		return array(
			'scan_id'            => $scan_id,
			'severity'           => 'info',
			'type'               => 'core',
			'location'           => 'core',
			'line_number'        => 0,
			'matched_pattern'    => $code,
			'snippet'            => $detail,
			'title'              => $title,
			'why_suspicious'     => 'Informational core integrity status.',
			'risk_explanation'   => 'No files were changed.',
			'recommended_action' => 'If checksums are unavailable, retry later or compare core files manually.',
			'file_hash'          => '',
			'file_mtime'         => 0,
			'file_size'          => 0,
			'file_perms'         => '',
			'file_owner'         => '',
			'confidence'         => 'likely legitimate',
			'extra'              => array(),
		);
	}
}
