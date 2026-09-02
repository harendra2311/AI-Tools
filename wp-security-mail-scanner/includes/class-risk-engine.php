<?php
/**
 * Risk scoring. Multiple indicators raise severity; single common functions stay low.
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Risk engine.
 */
class WPSMS_Risk_Engine {

	/**
	 * Score a file based on collected indicator IDs and location.
	 *
	 * @param array  $indicators Indicator ids.
	 * @param string $path       File path.
	 * @param object $paths      WPSMS_Paths.
	 * @param bool   $in_known_mail_plugin Known mail plugin.
	 * @return array {score, severity, confidence, explanation}
	 */
	public static function score( array $indicators, $path, $paths, $in_known_mail_plugin = false ) {
		$indicators = array_values( array_unique( $indicators ) );
		$set        = array_fill_keys( $indicators, true );
		$score      = 0;

		$weights = array();
		foreach ( WPSMS_Patterns::file_patterns() as $p ) {
			$weights[ $p['indicator'] ] = $p['base_weight'];
		}

		foreach ( $indicators as $ind ) {
			$score += isset( $weights[ $ind ] ) ? (int) $weights[ $ind ] : 5;
		}

		$unusual = $paths && method_exists( $paths, 'is_unusual_executable_location' ) && $paths->is_unusual_executable_location( $path );
		$uploads = $paths && method_exists( $paths, 'is_uploads' ) && $paths->is_uploads( $path );
		$cache   = $paths && method_exists( $paths, 'is_cache' ) && $paths->is_cache( $path );

		$has_mail = isset( $set['wp_mail'] ) || isset( $set['php_mail'] ) || isset( $set['phpmailer'] );
		$has_obf  = isset( $set['eval'] ) || isset( $set['preg_replace_e'] ) || isset( $set['compression_decode'] ) || isset( $set['str_rot13'] ) || isset( $set['long_encoded'] ) || isset( $set['fromcharcode'] );
		$has_input = isset( $set['user_input'] );

		if ( $unusual && $has_mail ) {
			$score += 40;
		}
		if ( $uploads && preg_match( '/\.(php|phtml|phar|inc)$/i', $path ) ) {
			$score += 35;
		}
		if ( $has_mail && $has_input ) {
			$score += 20;
		}
		if ( $has_mail && $has_obf ) {
			$score += 35;
		}
		if ( $has_mail && isset( $set['base64_decode'] ) ) {
			$score += 22;
		}
		if ( $has_mail && isset( $set['file_put_contents'] ) ) {
			$score += 12;
		}
		if ( isset( $set['http_out'] ) && $has_mail ) {
			$score += 10;
		}
		if ( isset( $set['docusign'] ) && $has_mail && ! $in_known_mail_plugin ) {
			$score += 20;
		}
		if ( isset( $set['phish_wording'] ) && $has_mail ) {
			$score += 25;
		}
		if ( isset( $set['remote_include'] ) ) {
			$score += 20;
		}
		if ( $in_known_mail_plugin && $has_mail && ! $unusual && ! $has_obf ) {
			$score = (int) max( 1, floor( $score * 0.35 ) );
		}
		if ( $cache && $has_mail ) {
			$score += 15;
		}

		$severity = self::severity_from_score( $score, $indicators, $unusual );
		$confidence = 'suspicious';
		if ( $score >= 90 ) {
			$confidence = 'potentially compromised';
		} elseif ( $score >= 55 ) {
			$confidence = 'high-risk indicator';
		} elseif ( $in_known_mail_plugin && $has_mail && ! $has_obf ) {
			$confidence = 'likely legitimate';
		} elseif ( $score < 15 ) {
			$confidence = 'likely legitimate';
		} else {
			$confidence = 'requires investigation';
		}

		$explanation = self::explain( $score, $severity, $has_mail, $has_obf, $has_input, $unusual, $uploads, $in_known_mail_plugin );

		return array(
			'score'       => $score,
			'severity'    => $severity,
			'confidence'  => $confidence,
			'explanation' => $explanation,
			'indicators'  => $indicators,
		);
	}

	/**
	 * Map score to severity with special cases.
	 *
	 * @param int   $score       Score.
	 * @param array $indicators  Indicators.
	 * @param bool  $unusual     Unusual location.
	 * @return string
	 */
	public static function severity_from_score( $score, array $indicators, $unusual ) {
		$set = array_fill_keys( $indicators, true );
		if ( isset( $set['remote_include'] ) || isset( $set['preg_replace_e'] ) && $unusual ) {
			return 'critical';
		}
		if ( $score >= 85 ) {
			return 'critical';
		}
		if ( $score >= 50 ) {
			return 'high';
		}
		if ( $score >= 22 ) {
			return 'medium';
		}
		if ( $score >= 8 ) {
			return 'low';
		}
		return 'info';
	}

	/**
	 * Human explanation.
	 *
	 * @param int  $score Score.
	 * @param string $severity Severity.
	 * @param bool $has_mail Mail.
	 * @param bool $has_obf Obfuscation.
	 * @param bool $has_input Input.
	 * @param bool $unusual Unusual path.
	 * @param bool $uploads Uploads.
	 * @param bool $known Known plugin.
	 * @return string
	 */
	public static function explain( $score, $severity, $has_mail, $has_obf, $has_input, $unusual, $uploads, $known ) {
		$parts = array();
		$parts[] = sprintf( 'Combined risk score: %d (%s).', (int) $score, $severity );
		if ( $has_mail && $unusual ) {
			$parts[] = 'Mail-sending code appears in an unusual location (uploads, cache, temporary, or unexpected root PHP). This combination is uncommon on healthy sites and requires investigation.';
		} elseif ( $has_mail && $known ) {
			$parts[] = 'Mail-sending code is inside a commonly used mail/form/e-commerce plugin, which is often legitimate.';
		} elseif ( $has_mail ) {
			$parts[] = 'Mail-sending functions alone are not malware; they are scored as informational unless other indicators appear.';
		}
		if ( $has_mail && $has_input ) {
			$parts[] = 'User input ($_POST/$_GET/$_REQUEST/$_COOKIE) is used in a file that also sends mail. That can be a contact form (likely legitimate) or an unauthorized mail script.';
		}
		if ( $has_mail && $has_obf ) {
			$parts[] = 'Obfuscation or dynamic execution together with mail functions is a high-risk indicator.';
		}
		if ( $uploads ) {
			$parts[] = 'Executable or scripted content under wp-content/uploads is unusual because uploads normally store media.';
		}
		$parts[] = 'This scanner does not claim the file is definitely malware without strong combined evidence.';
		return implode( ' ', $parts );
	}

	/**
	 * Collapse many line matches in one file into representative findings.
	 *
	 * @param array $matches Pattern matches with indicator.
	 * @param int   $limit   Max findings per file.
	 * @return array
	 */
	public static function prioritize_matches( array $matches, $limit = 8 ) {
		usort(
			$matches,
			function ( $a, $b ) {
				$wa = isset( $a['base_weight'] ) ? (int) $a['base_weight'] : 0;
				$wb = isset( $b['base_weight'] ) ? (int) $b['base_weight'] : 0;
				return $wb <=> $wa;
			}
		);
		$seen = array();
		$out  = array();
		foreach ( $matches as $m ) {
			$key = $m['id'] . ':' . (int) $m['line'];
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$out[]        = $m;
			if ( count( $out ) >= $limit ) {
				break;
			}
		}
		return $out;
	}
}
