<?php
/**
 * WP-Cron scanner (read-only).
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cron scanner.
 */
class WPSMS_Cron_Scanner {

	/**
	 * Scan cron array.
	 *
	 * @param string $scan_id Scan.
	 * @return array
	 */
	public function scan( $scan_id ) {
		$crons = _get_cron_array();
		if ( ! is_array( $crons ) ) {
			return array();
		}
		$known    = array_fill_keys( WPSMS_Patterns::known_cron_hooks(), true );
		$findings = array();

		foreach ( $crons as $timestamp => $hooks ) {
			if ( ! is_array( $hooks ) ) {
				continue;
			}
			foreach ( $hooks as $hook => $events ) {
				if ( ! is_array( $events ) ) {
					continue;
				}
				foreach ( $events as $sig => $event ) {
					$recurrence = isset( $event['schedule'] ) ? $event['schedule'] : __( 'once', 'wp-security-mail-scanner' );
					$args       = isset( $event['args'] ) ? wp_json_encode( $event['args'] ) : '[]';
					$args       = WPSMS_Redactor::redact( $args );
					$unknown    = ! isset( $known[ $hook ] );
					$mailish    = (bool) preg_match( '/mail|smtp|phpmailer|docusign|spam|newsletter/i', $hook . $args );
					$execish    = (bool) preg_match( '/exec|eval|http|curl|request|file|option|plugin/i', $hook );
					$severity   = 'info';
					$confidence = 'likely legitimate';
					if ( $unknown && ( $mailish || $execish ) ) {
						$severity   = 'medium';
						$confidence = 'requires investigation';
					} elseif ( $unknown ) {
						$severity   = 'low';
						$confidence = 'requires investigation';
					} elseif ( $mailish ) {
						$severity = 'low';
					}

					$title = $unknown
						? sprintf( 'Custom cron hook: %s', $hook )
						: sprintf( 'Scheduled hook: %s', $hook );

					$findings[] = array(
						'scan_id'            => $scan_id,
						'severity'           => $severity,
						'type'               => 'cron',
						'location'           => 'cron:' . $hook,
						'line_number'        => 0,
						'matched_pattern'    => $hook,
						'snippet'            => sprintf(
							'next=%s recurrence=%s args=%s',
							WPSMS_Helpers::format_datetime( (int) $timestamp ),
							$recurrence,
							substr( (string) $args, 0, 300 )
						),
						'title'              => $title,
						'why_suspicious'     => $unknown
							? 'This hook is not in the small built-in WordPress cron list. Many legitimate plugins register custom hooks; unknown + mail/HTTP naming deserves review.'
							: 'Built-in or very common WordPress maintenance hook.',
						'risk_explanation'   => 'Cron events are not deleted automatically. A custom hook is not malware by itself.',
						'recommended_action' => 'Search the codebase for add_action( \'' . $hook . '\' ) to see which plugin registered it.',
						'file_hash'          => '',
						'file_mtime'         => (int) $timestamp,
						'file_size'          => 0,
						'file_perms'         => '',
						'file_owner'         => '',
						'confidence'         => $confidence,
						'extra'              => array(
							'hook'       => $hook,
							'next_run'   => (int) $timestamp,
							'recurrence' => $recurrence,
							'args'       => $args,
							'signature'  => $sig,
						),
					);
				}
			}
		}

		return $findings;
	}
}
