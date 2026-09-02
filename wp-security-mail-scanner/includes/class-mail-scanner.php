<?php
/**
 * Mail security scanner (code locations, SMTP hints, phishing wording).
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mail scanner.
 */
class WPSMS_Mail_Scanner {

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
	 * Scan a file specifically for mail incident context (extra findings).
	 *
	 * @param string $path    Path.
	 * @param string $content Content.
	 * @param string $scan_id Scan.
	 * @param array  $meta    Meta.
	 * @return array
	 */
	public function extra_mail_findings( $path, $content, $scan_id, $meta ) {
		$relative = $this->paths->relative( $path );
		$out      = array();

		$emails = $this->extract_emails( $content );
		$has_mail_fn = (bool) preg_match( '/\bwp_mail\s*\(|(?<!wp_)(?<![A-Za-z0-9_])mail\s*\(|\bPHPMailer\b/i', $content );

		if ( $has_mail_fn && $emails && $this->paths->is_unusual_executable_location( $path ) ) {
			$out[] = array(
				'scan_id'            => $scan_id,
				'severity'           => 'high',
				'type'               => 'mail',
				'location'           => $relative,
				'line_number'        => 0,
				'matched_pattern'    => 'mail_plus_hardcoded_email',
				'snippet'            => implode( ', ', array_slice( $emails, 0, 8 ) ),
				'title'              => 'Mail function with hard-coded email addresses in an unusual location',
				'why_suspicious'     => 'Hard-coded recipients plus mail() / wp_mail() in uploads, cache, or unexpected root PHP is a common pattern for unauthorized mailers.',
				'risk_explanation'   => 'High-risk indicator. Requires investigation. Addresses listed are clues, not proof of spam.',
				'recommended_action' => 'Open the file, identify To/Subject/From, and compare with bounced DocuSign-like messages.',
				'file_hash'          => WPSMS_Helpers::file_sha256( $path ),
				'file_mtime'         => $meta['mtime'],
				'file_size'          => $meta['size'],
				'file_perms'         => $meta['perms'],
				'file_owner'         => $meta['owner'],
				'confidence'         => 'high-risk indicator',
				'extra'              => array( 'emails' => array_slice( $emails, 0, 20 ) ),
			);
		}

		if ( $has_mail_fn && preg_match( '/\b(curl_exec|wp_remote_post|wp_remote_get)\s*\(/i', $content ) && $this->paths->is_unusual_executable_location( $path ) ) {
			$out[] = array(
				'scan_id'            => $scan_id,
				'severity'           => 'high',
				'type'               => 'mail',
				'location'           => $relative,
				'line_number'        => 0,
				'matched_pattern'    => 'mail_plus_http',
				'snippet'            => '',
				'title'              => 'Mail sending combined with outbound HTTP in an unusual location',
				'why_suspicious'     => 'Unauthorized scripts sometimes fetch recipient lists or templates remotely and then send mail.',
				'risk_explanation'   => 'High-risk indicator when the file is not part of a known mail plugin.',
				'recommended_action' => 'Identify the remote host and whether this file belongs to a plugin you installed.',
				'file_hash'          => WPSMS_Helpers::file_sha256( $path ),
				'file_mtime'         => $meta['mtime'],
				'file_size'          => $meta['size'],
				'file_perms'         => $meta['perms'],
				'file_owner'         => $meta['owner'],
				'confidence'         => 'requires investigation',
				'extra'              => array(),
			);
		}

		if ( preg_match( '/\b(base64_decode|eval|gzinflate)\s*\(/i', $content ) && $has_mail_fn ) {
			$sev = $this->paths->is_unusual_executable_location( $path ) ? 'critical' : 'high';
			$out[] = array(
				'scan_id'            => $scan_id,
				'severity'           => $sev,
				'type'               => 'mail',
				'location'           => $relative,
				'line_number'        => 0,
				'matched_pattern'    => 'mail_plus_obfuscation',
				'snippet'            => '',
				'title'              => 'Mail functions combined with decoding or eval',
				'why_suspicious'     => 'This combination is a classic unauthorized mailer / dropper pattern, though some commercial plugins obfuscate license code.',
				'risk_explanation'   => ( 'critical' === $sev )
					? 'Critical high-risk indicator because the file location is also unusual.'
					: 'High-risk indicator. Confirm whether this is a known commercial plugin before taking action.',
				'recommended_action' => 'View the file. If it is unknown, generate a hash, back it up, and consider quarantine after confirmation.',
				'file_hash'          => WPSMS_Helpers::file_sha256( $path ),
				'file_mtime'         => $meta['mtime'],
				'file_size'          => $meta['size'],
				'file_perms'         => $meta['perms'],
				'file_owner'         => $meta['owner'],
				'confidence'         => 'potentially compromised',
				'extra'              => array(),
			);
		}

		return $out;
	}

	/**
	 * Extract emails, skipping obvious core/docs.
	 *
	 * @param string $content Content.
	 * @return array
	 */
	public function extract_emails( $content ) {
		if ( ! preg_match_all( '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $content, $m ) ) {
			return array();
		}
		$skip = array( 'wordpress.org', 'example.com', 'example.org', 'localhost' );
		$out  = array();
		foreach ( $m[0] as $email ) {
			$email = strtolower( $email );
			$dom   = substr( strrchr( $email, '@' ), 1 );
			if ( in_array( $dom, $skip, true ) ) {
				continue;
			}
			$out[] = $email;
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Build mail incident summary from findings + extras.
	 *
	 * @param array $findings All findings.
	 * @param array $job      Job meta.
	 * @return array
	 */
	public static function build_incident_summary( array $findings, array $job ) {
		$mail_files   = array();
		$subjects     = array();
		$recipients   = array();
		$smtp         = array();
		$cron_mail    = array();
		$recent       = array();
		$uploads_mail = array();

		foreach ( $findings as $f ) {
			$type = isset( $f['type'] ) ? $f['type'] : '';
			$loc  = isset( $f['location'] ) ? $f['location'] : '';
			$title = isset( $f['title'] ) ? $f['title'] : '';
			$ind  = '';
			if ( ! empty( $f['extra'] ) ) {
				$extra = is_array( $f['extra'] ) ? $f['extra'] : json_decode( $f['extra'], true );
				if ( isset( $extra['emails'] ) && is_array( $extra['emails'] ) ) {
					$recipients = array_merge( $recipients, $extra['emails'] );
				}
				if ( isset( $extra['indicators'] ) && is_array( $extra['indicators'] ) ) {
					$ind = implode( ',', $extra['indicators'] );
				}
			}
			$is_mail = ( 'mail' === $type ) || (bool) preg_match( '/mail|phpmailer|smtp|docusign|wp_mail/i', $title . ' ' . $ind . ' ' . ( isset( $f['matched_pattern'] ) ? $f['matched_pattern'] : '' ) );
			if ( $is_mail ) {
				$mail_files[ $loc ] = $f;
				if ( 0 === strpos( $loc, 'wp-content/uploads/' ) || false !== strpos( $loc, '/cache/' ) ) {
					$uploads_mail[ $loc ] = true;
				}
			}
			if ( 'cron' === $type && preg_match( '/mail|http|php/i', $title . $loc ) ) {
				$cron_mail[] = $loc;
			}
			if ( ! empty( $f['file_mtime'] ) && ( time() - (int) $f['file_mtime'] ) < 14 * DAY_IN_SECONDS ) {
				$recent[] = $loc;
			}
			if ( preg_match( '/smtp/i', $title ) ) {
				$smtp[] = $loc;
			}
			if ( preg_match( '/docusign|authorization request|review document/i', $title . ' ' . ( isset( $f['snippet'] ) ? $f['snippet'] : '' ) ) ) {
				$subjects[] = $loc;
			}
		}

		$has_suspicious_mail = false;
		foreach ( $mail_files as $f ) {
			if ( in_array( $f['severity'], array( 'critical', 'high', 'medium' ), true ) ) {
				$has_suspicious_mail = true;
				break;
			}
		}

		$answers = array(
			'has_suspicious_mail_code' => $has_suspicious_mail,
			'mail_files'               => array_keys( $mail_files ),
			'uploads_or_cache'         => array_keys( $uploads_mail ),
			'recipients'               => array_values( array_unique( $recipients ) ),
			'subject_leads'            => array_values( array_unique( $subjects ) ),
			'smtp_locations'           => array_values( array_unique( $smtp ) ),
			'cron_leads'               => array_values( array_unique( $cron_mail ) ),
			'recently_modified'        => array_values( array_unique( array_slice( $recent, 0, 30 ) ) ),
		);

		$narrative   = array();
		$narrative[] = $has_suspicious_mail
			? __( 'Suspicious mail-sending code was found and requires investigation. This is not a definite malware verdict by itself.', 'wp-security-mail-scanner' )
			: __( 'No high-risk unauthorized mailer pattern was confirmed. Review informational mail findings from legitimate plugins such as WooCommerce or SMTP tools.', 'wp-security-mail-scanner' );

		if ( $answers['mail_files'] ) {
			$narrative[] = sprintf(
				/* translators: %s file list */
				__( 'Mail-related code appears in: %s', 'wp-security-mail-scanner' ),
				implode( ', ', array_slice( $answers['mail_files'], 0, 12 ) )
			);
		}
		if ( $answers['uploads_or_cache'] ) {
			$narrative[] = __( 'One or more mail-related files sit inside uploads or cache directories. That location is a primary investigation path for unauthorized scripts.', 'wp-security-mail-scanner' );
		} else {
			$narrative[] = __( 'No mail-sending PHP was found inside uploads/cache in this scan. Continue reviewing plugins, mu-plugins, and unexpected root files.', 'wp-security-mail-scanner' );
		}
		if ( $answers['subject_leads'] ) {
			$narrative[] = __( 'DocuSign / document-signature wording was detected. Compare those files with bounced subjects such as “Completed: Complete with DocuSign: Draft Authorization Request”.', 'wp-security-mail-scanner' );
		} else {
			$narrative[] = __( 'No DocuSign-style subject strings were found in scanned files. The spam may be generated remotely, encoded, or stored in the database.', 'wp-security-mail-scanner' );
		}

		$narrative[] = __( 'Investigation path: (1) open High/Critical mail findings, (2) check PHP in uploads, (3) review unknown admin users, (4) inspect custom cron hooks, (5) search database options for docusign/script injections, (6) compare core checksums, (7) only quarantine after backup and confirmation.', 'wp-security-mail-scanner' );

		return array(
			'answers'   => $answers,
			'narrative' => $narrative,
			'generated' => time(),
			'scan_id'   => isset( $job['scan_id'] ) ? $job['scan_id'] : '',
		);
	}
}
