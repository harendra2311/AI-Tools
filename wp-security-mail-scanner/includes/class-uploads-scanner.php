<?php
/**
 * Uploads directory listing scanner.
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Uploads scanner.
 */
class WPSMS_Uploads_Scanner {

	/**
	 * Paths.
	 *
	 * @var WPSMS_Paths
	 */
	protected $paths;

	/**
	 * Constructor.
	 *
	 * @param WPSMS_Paths $paths Paths.
	 */
	public function __construct( WPSMS_Paths $paths ) {
		$this->paths = $paths;
	}

	/**
	 * Scan a single uploads file for executable / odd types.
	 *
	 * @param string $path    Path.
	 * @param string $scan_id Scan.
	 * @return array
	 */
	public function scan_uploads_file( $path, $scan_id ) {
		if ( ! $this->paths->is_uploads( $path ) || ! is_file( $path ) ) {
			return array();
		}
		$ext      = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		$relative = $this->paths->relative( $path );
		$meta     = WPSMS_Helpers::file_meta( $path );
		$base     = strtolower( basename( $path ) );

		$warn_ext = array( 'js', 'html', 'htm', 'shtml' );

		if ( in_array( $ext, array( 'php', 'phtml', 'phar', 'inc' ), true ) ) {
			return array();
		}

		if ( 'htaccess' === $base || 'htaccess' === $ext ) {
			return array(
				$this->row(
					$scan_id,
					$relative,
					$meta,
					'medium',
					'.htaccess in uploads',
					'An .htaccess file in uploads can be legitimate (security rules) or used to force PHP execution of uploaded files.',
					'Review rules for php_flag, SetHandler, or AddType that would execute uploads as PHP. Do not delete automatically.'
				),
			);
		}

		if ( in_array( $ext, $risky_ext, true ) ) {
			return array(); // PHP handled by file scanner as high; avoid duplicate-only here if already covered. Still return high for non-php binaries.
			// php/phtml handled elsewhere.
		}

		if ( in_array( $ext, array( 'exe', 'sh', 'bat', 'cmd', 'dll' ), true ) ) {
			return array(
				$this->row(
					$scan_id,
					$relative,
					$meta,
					'high',
					'Executable file in uploads',
					'Uploads should not contain native executables.',
					'Download a backup for forensics, then consider quarantine after confirmation.'
				),
			);
		}

		if ( in_array( $ext, $warn_ext, true ) && filesize( $path ) < 512000 ) {
			$content = @file_get_contents( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( is_string( $content ) && preg_match( '/eval\(|fromCharCode|document\.write|<iframe/i', $content ) ) {
				return array(
					$this->row(
						$scan_id,
						$relative,
						$meta,
						'medium',
						'Suspicious script markup in uploads',
						'HTML/JS in uploads contained eval/fromCharCode/iframe/document.write indicators.',
						'Open the file and check whether it is a cached page or an injected spam page.'
					),
				);
			}
		}

		return array();
	}

	/**
	 * Row helper.
	 *
	 * @param string $scan_id Scan.
	 * @param string $relative Rel.
	 * @param array  $meta Meta.
	 * @param string $sev Sev.
	 * @param string $title Title.
	 * @param string $why Why.
	 * @param string $action Action.
	 * @return array
	 */
	protected function row( $scan_id, $relative, $meta, $sev, $title, $why, $action ) {
		return array(
			'scan_id'            => $scan_id,
			'severity'           => $sev,
			'type'               => 'uploads',
			'location'           => $relative,
			'line_number'        => 0,
			'matched_pattern'    => 'uploads_special',
			'snippet'            => '',
			'title'              => $title,
			'why_suspicious'     => $why,
			'risk_explanation'   => 'Location and file type are unusual for media uploads. Not deleted automatically.',
			'recommended_action' => $action,
			'file_hash'          => '',
			'file_mtime'         => $meta['mtime'],
			'file_size'          => $meta['size'],
			'file_perms'         => $meta['perms'],
			'file_owner'         => $meta['owner'],
			'confidence'         => 'requires investigation',
			'extra'              => array(
				'extension' => strtolower( pathinfo( $relative, PATHINFO_EXTENSION ) ),
			),
		);
	}
}
