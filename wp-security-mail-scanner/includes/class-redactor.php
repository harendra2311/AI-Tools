<?php
/**
 * Secret redaction for reports and snippets.
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redacts secrets from exported text.
 */
class WPSMS_Redactor {

	/**
	 * Redact secrets from a string.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	public static function redact( $text ) {
		$text = (string) $text;
		$patterns = array(
			'/(password\s*[:=]\s*)([^\s,;\'"]+)/i' => '$1[REDACTED]',
			'/(passwd\s*[:=]\s*)([^\s,;\'"]+)/i'    => '$1[REDACTED]',
			'/(api[_-]?key\s*[:=]\s*)([^\s,;\'"]+)/i' => '$1[REDACTED]',
			'/(secret\s*[:=]\s*)([^\s,;\'"]+)/i'    => '$1[REDACTED]',
			'/(token\s*[:=]\s*)([^\s,;\'"]+)/i'     => '$1[REDACTED]',
			'/(authorization:\s*bearer\s+)(\S+)/i'  => '$1[REDACTED]',
			'/(smtp[_-]?pass(?:word)?\s*[:=]\s*)([^\s,;\'"]+)/i' => '$1[REDACTED]',
			'/(AUTH_KEY|SECURE_AUTH_KEY|LOGGED_IN_KEY|NONCE_KEY|AUTH_SALT|SECURE_AUTH_SALT|LOGGED_IN_SALT|NONCE_SALT)([\'"]\s*,\s*[\'"])([^\'"]+)([\'"])/' => '$1$2[REDACTED]$4',
		);
		foreach ( $patterns as $pattern => $replace ) {
			$text = preg_replace( $pattern, $replace, $text );
		}
		return $text;
	}

	/**
	 * Whether an option name looks like a secret store.
	 *
	 * @param string $name Option name.
	 * @return bool
	 */
	public static function is_secret_option( $name ) {
		return (bool) preg_match( '/pass|secret|api[_-]?key|token|auth|smtp.*user|cookie|nonce|salt/i', (string) $name );
	}
}
