<?php
/**
 * Content scanner for files.
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * File malware / indicator scanner.
 */
class WPSMS_File_Scanner {

	/**
	 * Paths helper.
	 *
	 * @var WPSMS_Paths
	 */
	protected $paths;

	/**
	 * Settings.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Constructor.
	 *
	 * @param WPSMS_Paths $paths    Paths.
	 * @param array       $settings Settings.
	 */
	public function __construct( WPSMS_Paths $paths, array $settings ) {
		$this->paths    = $paths;
		$this->settings = $settings;
	}

	/**
	 * Scan one file and return finding rows (not yet inserted).
	 *
	 * @param string $path    Absolute path.
	 * @param string $scan_id Scan id.
	 * @return array
	 */
	public function scan_file( $path, $scan_id ) {
		if ( $this->paths->is_self( $path ) ) {
			return array();
		}
		if ( ! is_file( $path ) || ! is_readable( $path ) ) {
			return array();
		}

		$ext      = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		$max      = (int) $this->settings['max_file_bytes'];
		$meta     = WPSMS_Helpers::file_meta( $path );
		$relative = $this->paths->relative( $path );
		$findings = array();

		if ( $meta['size'] > $max ) {
			if ( $this->paths->is_uploads( $path ) && in_array( $ext, array( 'php', 'phtml', 'phar' ), true ) ) {
				$findings[] = $this->make_uploads_php_finding( $scan_id, $path, $relative, $meta, 'File exceeds scan size limit but is executable PHP in uploads.' );
			}
			return $findings;
		}

		$content = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $content ) {
			return $findings;
		}

		$matches    = $this->match_patterns( $content );
		$indicators = array();
		foreach ( $matches as $m ) {
			$indicators[] = $m['indicator'];
		}

		$plugin_slug = $this->plugin_slug_from_path( $relative );
		$known_mail  = $plugin_slug && in_array( $plugin_slug, WPSMS_Helpers::known_mail_plugin_slugs(), true );

		if ( $this->paths->is_uploads( $path ) && in_array( $ext, array( 'php', 'phtml', 'phar', 'inc' ), true ) ) {
			$indicators[] = 'uploads_php';
			$findings[]   = $this->make_uploads_php_finding( $scan_id, $path, $relative, $meta, 'PHP/executable code inside wp-content/uploads is unusual because uploads normally contain media rather than executable PHP.' );
		}

		if ( $this->paths->is_unexpected_root_php( $path ) ) {
			$indicators[] = 'unexpected_root';
		}

		$risk     = WPSMS_Risk_Engine::score( $indicators, $path, $this->paths, $known_mail );
		$priority = WPSMS_Risk_Engine::prioritize_matches( $matches, 8 );

		$skip_low_noise = ( 'info' === $risk['severity'] && count( $priority ) > 0 && ! $this->paths->is_unusual_executable_location( $path ) );
		if ( $skip_low_noise && ! isset( array_fill_keys( $indicators, true )['phish_wording'] ) && ! isset( array_fill_keys( $indicators, true )['docusign'] ) ) {
			// Keep a single informational finding for mail in unknown plugins; drop noise in core.
			if ( $this->paths->is_core_dir( $path ) ) {
				return $findings;
			}
		}

		$file_hash = '';
		if ( in_array( $risk['severity'], array( 'critical', 'high', 'medium' ), true ) ) {
			$file_hash = WPSMS_Helpers::file_sha256( $path );
		}

		foreach ( $priority as $m ) {
			$type = ( 'mail' === $m['category'] ) ? 'mail' : 'file';
			if ( $this->paths->is_theme_dir( $path ) && 'mail' !== $type ) {
				$type = 'theme';
			} elseif ( $this->paths->is_plugin_dir( $path ) && 'mail' !== $type ) {
				$type = 'plugin';
			}
			$sev = $risk['severity'];
			// Individual low-weight matches inherit file score but mail-only in known plugins stay info/low.
			if ( $known_mail && 'mail' === $type && $risk['score'] < 22 ) {
				$sev = 'info';
			}
			$findings[] = array(
				'scan_id'            => $scan_id,
				'severity'           => $sev,
				'type'               => $type,
				'location'           => $relative,
				'line_number'        => (int) $m['line'],
				'matched_pattern'    => $m['id'] . ' / ' . $m['indicator'],
				'snippet'            => WPSMS_Helpers::snippet_around_line( $content, $m['line'] ),
				'title'              => $m['title'],
				'why_suspicious'     => $m['why'],
				'risk_explanation'   => $risk['explanation'],
				'recommended_action' => $m['action'],
				'file_hash'          => $file_hash,
				'file_mtime'         => $meta['mtime'],
				'file_size'          => $meta['size'],
				'file_perms'         => $meta['perms'],
				'file_owner'         => $meta['owner'],
				'confidence'         => $risk['confidence'],
				'extra'              => array(
					'indicators' => $risk['indicators'],
					'score'      => $risk['score'],
					'plugin'     => $plugin_slug,
				),
			);
		}

		if ( $this->paths->is_unexpected_root_php( $path ) ) {
			$findings[] = array(
				'scan_id'            => $scan_id,
				'severity'           => 'high',
				'type'               => 'file',
				'location'           => $relative,
				'line_number'        => 0,
				'matched_pattern'    => 'unexpected_root_php',
				'snippet'            => '',
				'title'              => 'Unexpected PHP file in WordPress root',
				'why_suspicious'     => 'WordPress root normally contains a small set of core PHP files. Extra PHP at the root is a common drop location for unauthorized scripts.',
				'risk_explanation'   => 'High-risk indicator. Requires investigation. This is not definite malware by location alone.',
				'recommended_action' => 'Compare with a clean WordPress install. Open the file and inspect for mail or eval patterns.',
				'file_hash'          => $file_hash ? $file_hash : WPSMS_Helpers::file_sha256( $path ),
				'file_mtime'         => $meta['mtime'],
				'file_size'          => $meta['size'],
				'file_perms'         => $meta['perms'],
				'file_owner'         => $meta['owner'],
				'confidence'         => 'requires investigation',
				'extra'              => array( 'indicators' => $indicators, 'score' => $risk['score'] ),
			);
		}

		$url_findings = WPSMS_URL_Extractor::from_content( $content, $relative, $scan_id, $meta, $risk['severity'] );
		foreach ( $url_findings as $uf ) {
			$findings[] = $uf;
		}

		return $findings;
	}

	/**
	 * Match patterns.
	 *
	 * @param string $content Content.
	 * @return array
	 */
	public function match_patterns( $content ) {
		$out   = array();
		$lines = preg_split( "/\r\n|\n|\r/", $content );
		foreach ( WPSMS_Patterns::file_patterns() as $pattern ) {
			if ( ! preg_match_all( $pattern['regex'], $content, $m, PREG_OFFSET_CAPTURE ) ) {
				continue;
			}
			$count = 0;
			foreach ( $m[0] as $hit ) {
				if ( $count >= 3 ) {
					break;
				}
				$offset = $hit[1];
				$line   = $this->line_from_offset( $content, $offset, $lines );
				$row    = $pattern;
				$row['line']  = $line;
				$row['match'] = substr( $hit[0], 0, 120 );
				$out[]        = $row;
				++$count;
			}
		}
		return $out;
	}

	/**
	 * Line number from offset.
	 *
	 * @param string $content Content.
	 * @param int    $offset  Offset.
	 * @param array  $lines   Lines.
	 * @return int
	 */
	protected function line_from_offset( $content, $offset, $lines ) {
		$before = substr( $content, 0, $offset );
		return substr_count( $before, "\n" ) + 1;
	}

	/**
	 * Plugin slug from relative path.
	 *
	 * @param string $relative Relative.
	 * @return string
	 */
	protected function plugin_slug_from_path( $relative ) {
		if ( preg_match( '#wp-content/plugins/([^/]+)/#', $relative, $m ) ) {
			return $m[1];
		}
		return '';
	}

	/**
	 * Uploads PHP finding.
	 *
	 * @param string $scan_id  Scan.
	 * @param string $path     Path.
	 * @param string $relative Rel.
	 * @param array  $meta     Meta.
	 * @param string $why      Why.
	 * @return array
	 */
	protected function make_uploads_php_finding( $scan_id, $path, $relative, $meta, $why ) {
		return array(
			'scan_id'            => $scan_id,
			'severity'           => 'high',
			'type'               => 'uploads',
			'location'           => $relative,
			'line_number'        => 0,
			'matched_pattern'    => 'php_in_uploads',
			'snippet'            => '',
			'title'              => 'Executable PHP in uploads directory',
			'why_suspicious'     => $why,
			'risk_explanation'   => 'High-risk indicator. Uploads should not normally contain PHP. This plugin will not delete the file automatically.',
			'recommended_action' => 'View the file. If it is not a known legitimate exception, consider quarantining after backup and confirmation.',
			'file_hash'          => WPSMS_Helpers::file_sha256( $path ),
			'file_mtime'         => $meta['mtime'],
			'file_size'          => $meta['size'],
			'file_perms'         => $meta['perms'],
			'file_owner'         => $meta['owner'],
			'confidence'         => 'high-risk indicator',
			'extra'              => array( 'extension' => strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ) ),
		);
	}
}
