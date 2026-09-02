<?php
/**
 * Installed plugin inventory and flags (read-only).
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin scanner.
 */
class WPSMS_Plugin_Scanner {

	/**
	 * Scan plugins.
	 *
	 * @param string $scan_id Scan.
	 * @return array
	 */
	public function scan( $scan_id ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins  = get_plugins();
		$active   = (array) get_option( 'active_plugins', array() );
		$findings = array();
		$cutoff   = time() - 7 * DAY_IN_SECONDS;

		foreach ( $plugins as $file => $data ) {
			$dir      = dirname( WP_PLUGIN_DIR . '/' . $file );
			$slug     = dirname( $file );
			if ( '.' === $slug ) {
				$slug = basename( $file, '.php' );
			}
			$recent   = $this->recently_modified( $dir, $cutoff );
			$mailish  = $this->plugin_has_mail_hint( $dir );
			$odd_name = (bool) preg_match( '/^[a-f0-9]{8,}$/i', $slug ) || (bool) preg_match( '/(shell|c99|r57|bypass|hack)/i', $slug );
			$unknown  = empty( $data['Name'] ) || $odd_name;

			$flags = array();
			if ( $odd_name ) {
				$flags[] = 'suspicious_filename';
			}
			if ( $recent ) {
				$flags[] = 'recently_modified';
			}
			if ( $mailish ) {
				$flags[] = 'sends_mail';
			}
			if ( $unknown ) {
				$flags[] = 'unknown_plugin';
			}

			$severity = 'info';
			if ( $odd_name ) {
				$severity = 'high';
			} elseif ( $recent && $mailish ) {
				$severity = 'medium';
			} elseif ( $mailish ) {
				$severity = 'low';
			}

			$findings[] = array(
				'scan_id'            => $scan_id,
				'severity'           => $severity,
				'type'               => 'plugin',
				'location'           => 'wp-content/plugins/' . $file,
				'line_number'        => 0,
				'matched_pattern'    => implode( ',', $flags ? $flags : array( 'inventory' ) ),
				'snippet'            => sprintf(
					'%s v%s active=%s path=%s',
					isset( $data['Name'] ) ? $data['Name'] : $slug,
					isset( $data['Version'] ) ? $data['Version'] : '?',
					in_array( $file, $active, true ) ? 'yes' : 'no',
					$file
				),
				'title'              => sprintf( 'Plugin: %s', isset( $data['Name'] ) ? $data['Name'] : $slug ),
				'why_suspicious'     => $this->why( $flags ),
				'risk_explanation'   => 'Plugins are not deactivated automatically. Mail capability is normal for WooCommerce, forms, and SMTP plugins.',
				'recommended_action' => 'Confirm you installed this plugin. If the slug looks random or the files were modified unexpectedly, inspect the plugin folder.',
				'file_hash'          => '',
				'file_mtime'         => $recent ? $recent : 0,
				'file_size'          => 0,
				'file_perms'         => in_array( $file, $active, true ) ? 'active' : 'inactive',
				'file_owner'         => isset( $data['Version'] ) ? $data['Version'] : '',
				'confidence'         => $odd_name ? 'requires investigation' : 'likely legitimate',
				'extra'              => array(
					'name'    => isset( $data['Name'] ) ? $data['Name'] : '',
					'version' => isset( $data['Version'] ) ? $data['Version'] : '',
					'active'  => in_array( $file, $active, true ),
					'slug'    => $slug,
					'flags'   => $flags,
				),
			);
		}

		return $findings;
	}

	/**
	 * Latest mtime if newer than cutoff.
	 *
	 * @param string $dir    Dir.
	 * @param int    $cutoff Cutoff.
	 * @return int
	 */
	protected function recently_modified( $dir, $cutoff ) {
		if ( ! is_dir( $dir ) ) {
			return 0;
		}
		$max   = 0;
		$count = 0;
		$it    = @scandir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! $it ) {
			return 0;
		}
		foreach ( $it as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}
			$p = $dir . '/' . $item;
			if ( is_file( $p ) && preg_match( '/\.(php|js)$/i', $item ) ) {
				$m = (int) filemtime( $p );
				if ( $m > $max ) {
					$max = $m;
				}
			}
			if ( ++$count > 40 ) {
				break;
			}
		}
		return ( $max >= $cutoff ) ? $max : 0;
	}

	/**
	 * Cheap mail hint: grep a few php files.
	 *
	 * @param string $dir Dir.
	 * @return bool
	 */
	protected function plugin_has_mail_hint( $dir ) {
		$candidates = array( $dir . '/' . basename( $dir ) . '.php' );
		$it         = @scandir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( $it ) {
			foreach ( $it as $item ) {
				if ( preg_match( '/\.php$/', $item ) ) {
					$candidates[] = $dir . '/' . $item;
				}
				if ( count( $candidates ) > 8 ) {
					break;
				}
			}
		}
		foreach ( $candidates as $file ) {
			if ( ! is_readable( $file ) ) {
				continue;
			}
			$buf = @file_get_contents( $file, false, null, 0, 80000 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( is_string( $buf ) && preg_match( '/wp_mail\s*\(|PHPMailer|phpmailer_init/i', $buf ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Why.
	 *
	 * @param array $flags Flags.
	 * @return string
	 */
	protected function why( array $flags ) {
		if ( in_array( 'suspicious_filename', $flags, true ) ) {
			return 'The plugin folder name looks random or matches known abusive names.';
		}
		if ( in_array( 'sends_mail', $flags, true ) ) {
			return 'This plugin appears to contain mail-sending functionality. That is often legitimate.';
		}
		if ( in_array( 'recently_modified', $flags, true ) ) {
			return 'One or more PHP/JS files in this plugin were modified in the last 7 days.';
		}
		return 'Inventory entry for administrator review.';
	}
}
