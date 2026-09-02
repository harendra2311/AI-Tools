<?php
/**
 * Theme inventory scanner (read-only).
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme scanner.
 */
class WPSMS_Theme_Scanner {

	/**
	 * Scan themes.
	 *
	 * @param string $scan_id Scan.
	 * @return array
	 */
	public function scan( $scan_id ) {
		$themes   = wp_get_themes();
		$active   = get_stylesheet();
		$parent   = get_template();
		$findings = array();

		foreach ( $themes as $slug => $theme ) {
			$dir     = $theme->get_stylesheet_directory();
			$is_used = ( $slug === $active || $slug === $parent );
			$files   = array( 'functions.php', 'header.php', 'footer.php' );
			foreach ( $files as $rel ) {
				$path = $dir . '/' . $rel;
				if ( ! is_readable( $path ) ) {
					continue;
				}
				$content = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				if ( ! is_string( $content ) ) {
					continue;
				}
				$hits = array();
				if ( preg_match( '/\b(eval|base64_decode|gzinflate)\s*\(/i', $content ) ) {
					$hits[] = 'obfuscation';
				}
				if ( preg_match( '/wp_mail\s*\(|(?<!wp_)mail\s*\(/i', $content ) ) {
					$hits[] = 'mail';
				}
				if ( preg_match( '/<iframe|fromCharCode|document\.write/i', $content ) ) {
					$hits[] = 'injected_js';
				}
				if ( preg_match( '/wp_redirect\s*\(\s*[\'"]https?:/i', $content ) ) {
					$hits[] = 'redirect';
				}
				if ( empty( $hits ) ) {
					continue;
				}
				$sev = in_array( 'obfuscation', $hits, true ) || in_array( 'injected_js', $hits, true ) ? 'high' : 'medium';
				$findings[] = array(
					'scan_id'            => $scan_id,
					'severity'           => $sev,
					'type'               => 'theme',
					'location'           => $this->rel_theme_path( $path ),
					'line_number'        => 0,
					'matched_pattern'    => implode( ',', $hits ),
					'snippet'            => substr( $content, 0, 400 ),
					'title'              => sprintf( 'Suspicious theme file: %s (%s)', $rel, $theme->get( 'Name' ) ),
					'why_suspicious'     => 'Theme header/footer/functions files are a common injection point for spam redirects and hidden scripts.',
					'risk_explanation'   => 'Requires investigation. Some parent themes include mail or iframe widgets legitimately.',
					'recommended_action' => 'Compare this file with a clean copy of the theme. Do not overwrite automatically.',
					'file_hash'          => WPSMS_Helpers::file_sha256( $path ),
					'file_mtime'         => (int) filemtime( $path ),
					'file_size'          => (int) filesize( $path ),
					'file_perms'         => substr( sprintf( '%o', fileperms( $path ) ), -4 ),
					'file_owner'         => $is_used ? 'active' : 'inactive',
					'confidence'         => 'requires investigation',
					'extra'              => array(
						'theme'  => $slug,
						'active' => $is_used,
						'hits'   => $hits,
					),
				);
			}
		}

		return $findings;
	}

	/**
	 * Relative path.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	protected function rel_theme_path( $path ) {
		$paths = new WPSMS_Paths();
		return $paths->relative( $path );
	}
}
