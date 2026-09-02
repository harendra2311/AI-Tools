<?php
/**
 * User security listing (read-only).
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User scanner.
 */
class WPSMS_User_Scanner {

	/**
	 * Scan users.
	 *
	 * @param string $scan_id Scan.
	 * @return array
	 */
	public function scan( $scan_id ) {
		$settings = WPSMS_Helpers::settings();
		$known    = array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', (string) $settings['known_admin_logins'] ) ) );
		$known    = array_map( 'strtolower', $known );

		$users    = get_users( array( 'number' => 500, 'fields' => 'all' ) );
		$findings = array();
		$admin_count = 0;

		foreach ( $users as $user ) {
			if ( ! $user instanceof WP_User ) {
				continue;
			}
			$roles     = (array) $user->roles;
			$is_admin  = in_array( 'administrator', $roles, true ) || user_can( $user, 'manage_options' );
			$reg       = strtotime( $user->user_registered );
			$last      = (int) get_user_meta( $user->ID, 'wpsms_last_login', true );
			if ( ! $last ) {
				$sessions = get_user_meta( $user->ID, 'session_tokens', true );
				if ( is_array( $sessions ) ) {
					foreach ( $sessions as $s ) {
						if ( isset( $s['login'] ) ) {
							$last = max( $last, (int) $s['login'] );
						}
					}
				}
			}

			$flags = array();
			if ( $is_admin ) {
				++$admin_count;
				if ( $known && ! in_array( strtolower( $user->user_login ), $known, true ) && ! in_array( strtolower( $user->user_email ), $known, true ) ) {
					$flags[] = 'unknown_admin';
				}
				if ( $reg && ( time() - $reg ) < 30 * DAY_IN_SECONDS ) {
					$flags[] = 'recent_admin';
				}
			}
			foreach ( WPSMS_Patterns::suspicious_usernames() as $rx ) {
				if ( preg_match( $rx, $user->user_login ) ) {
					$flags[] = 'suspicious_username';
					break;
				}
			}
			if ( user_can( $user, 'install_plugins' ) && ! $is_admin ) {
				$flags[] = 'elevated';
			}

			if ( empty( $flags ) && ! $is_admin ) {
				continue;
			}

			$severity = 'info';
			if ( in_array( 'recent_admin', $flags, true ) || in_array( 'suspicious_username', $flags, true ) ) {
				$severity = 'high';
			} elseif ( in_array( 'unknown_admin', $flags, true ) || in_array( 'elevated', $flags, true ) ) {
				$severity = 'medium';
			} elseif ( $is_admin ) {
				$severity = 'low';
			}

			$findings[] = array(
				'scan_id'            => $scan_id,
				'severity'           => $severity,
				'type'               => 'user',
				'location'           => 'user:' . $user->user_login,
				'line_number'        => (int) $user->ID,
				'matched_pattern'    => implode( ',', $flags ? $flags : array( 'administrator' ) ),
				'snippet'            => sprintf(
					'id=%d email=%s roles=%s registered=%s last_login=%s',
					$user->ID,
					$user->user_email,
					implode( '|', $roles ),
					$user->user_registered,
					$last ? WPSMS_Helpers::format_datetime( $last ) : __( 'unknown (not tracked yet)', 'wp-security-mail-scanner' )
				),
				'title'              => $is_admin
					? sprintf( 'Administrator account: %s', $user->user_login )
					: sprintf( 'User with elevated privileges: %s', $user->user_login ),
				'why_suspicious'     => $this->why( $flags, $is_admin ),
				'risk_explanation'   => 'Users are never deleted by this plugin. Unexpected administrators are a common persistence mechanism after a compromise.',
				'recommended_action' => 'Verify this person should have access. Reset passwords and enable 2FA if the account is unknown. Do not delete users until you have a backup and a recovery admin.',
				'file_hash'          => '',
				'file_mtime'         => $reg ? $reg : 0,
				'file_size'          => $user->ID,
				'file_perms'         => implode( ',', $roles ),
				'file_owner'         => $user->user_email,
				'confidence'         => empty( $flags ) ? 'likely legitimate' : 'requires investigation',
				'extra'              => array(
					'user_id'     => $user->ID,
					'user_login'  => $user->user_login,
					'email'       => $user->user_email,
					'roles'       => $roles,
					'registered'  => $user->user_registered,
					'last_login'  => $last,
					'flags'       => $flags,
				),
			);
		}

		return $findings;
	}

	/**
	 * Why text.
	 *
	 * @param array $flags Flags.
	 * @param bool  $is_admin Admin.
	 * @return string
	 */
	protected function why( array $flags, $is_admin ) {
		if ( in_array( 'recent_admin', $flags, true ) ) {
			return 'This administrator was created recently. Recently created admin accounts are a high-priority review item after a spam incident.';
		}
		if ( in_array( 'suspicious_username', $flags, true ) ) {
			return 'The username matches a pattern often used by automated or abusive accounts.';
		}
		if ( in_array( 'unknown_admin', $flags, true ) ) {
			return 'This administrator is not in your “known admins” list in scanner settings.';
		}
		if ( in_array( 'elevated', $flags, true ) ) {
			return 'This account can install plugins without the administrator role.';
		}
		if ( $is_admin ) {
			return 'Listed for review. Not suspicious by itself.';
		}
		return 'Listed for review.';
	}
}
