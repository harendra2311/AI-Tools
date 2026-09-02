<?php
/**
 * Findings persistence.
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Findings store.
 */
class WPSMS_Findings_Store {

	/**
	 * Table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'wpsms_findings';
	}

	/**
	 * Delete findings for a scan.
	 *
	 * @param string $scan_id Scan id.
	 */
	public static function clear_scan( $scan_id ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'scan_id' => $scan_id ), array( '%s' ) );
	}

	/**
	 * Insert a finding.
	 *
	 * @param array $row Row.
	 * @return int
	 */
	public static function add( array $row ) {
		global $wpdb;
		$defaults = array(
			'scan_id'             => '',
			'severity'            => 'info',
			'type'                => 'file',
			'location'            => '',
			'line_number'         => 0,
			'matched_pattern'     => '',
			'snippet'             => '',
			'title'               => '',
			'why_suspicious'      => '',
			'risk_explanation'    => '',
			'recommended_action'  => '',
			'file_hash'           => '',
			'file_mtime'          => 0,
			'file_size'           => 0,
			'file_perms'          => '',
			'file_owner'          => '',
			'confidence'          => 'suspicious',
			'extra'               => '',
			'created_at'          => current_time( 'mysql' ),
		);
		$row = wp_parse_args( $row, $defaults );
		if ( is_array( $row['extra'] ) ) {
			$row['extra'] = wp_json_encode( $row['extra'] );
		}
		$row['snippet']          = WPSMS_Redactor::redact( $row['snippet'] );
		$row['why_suspicious']   = WPSMS_Redactor::redact( $row['why_suspicious'] );
		$row['risk_explanation'] = WPSMS_Redactor::redact( $row['risk_explanation'] );
		$row['location']         = sanitize_text_field( $row['location'] );
		$row['title']            = sanitize_text_field( $row['title'] );
		$row['severity']         = sanitize_key( $row['severity'] );
		$row['type']             = sanitize_key( $row['type'] );
		$row['matched_pattern']  = sanitize_text_field( $row['matched_pattern'] );
		$row['confidence']       = sanitize_text_field( $row['confidence'] );

		$data = array(
			'scan_id'            => $row['scan_id'],
			'severity'           => $row['severity'],
			'type'               => $row['type'],
			'location'           => $row['location'],
			'line_number'        => (int) $row['line_number'],
			'matched_pattern'    => $row['matched_pattern'],
			'snippet'            => $row['snippet'],
			'title'              => $row['title'],
			'why_suspicious'     => $row['why_suspicious'],
			'risk_explanation'   => $row['risk_explanation'],
			'recommended_action' => $row['recommended_action'],
			'file_hash'          => $row['file_hash'],
			'file_mtime'         => (int) $row['file_mtime'],
			'file_size'          => (int) $row['file_size'],
			'file_perms'         => $row['file_perms'],
			'file_owner'         => $row['file_owner'],
			'confidence'         => $row['confidence'],
			'extra'              => $row['extra'],
			'created_at'         => $row['created_at'],
		);

		$wpdb->insert(
			self::table(),
			$data,
			array( '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * Query findings.
	 *
	 * @param string $scan_id Scan.
	 * @param array  $args    Args.
	 * @return array
	 */
	public static function query( $scan_id, array $args = array() ) {
		global $wpdb;
		$table    = self::table();
		$severity = isset( $args['severity'] ) ? sanitize_key( $args['severity'] ) : '';
		$type     = isset( $args['type'] ) ? sanitize_key( $args['type'] ) : '';
		$search   = isset( $args['search'] ) ? sanitize_text_field( $args['search'] ) : '';
		$offset   = isset( $args['offset'] ) ? max( 0, (int) $args['offset'] ) : 0;
		$limit    = isset( $args['limit'] ) ? min( 200, max( 1, (int) $args['limit'] ) ) : 50;
		$order    = 'FIELD(severity,"critical","high","medium","low","info"), id DESC';

		$where = array( 'scan_id = %s' );
		$params = array( $scan_id );

		if ( $severity && 'all' !== $severity ) {
			$where[]  = 'severity = %s';
			$params[] = $severity;
		}
		if ( $type && 'all' !== $type ) {
			if ( 'files' === $type || 'file' === $type ) {
				$where[] = 'type IN ("file","uploads","core","url")';
			} elseif ( 'mail' === $type ) {
				$where[] = 'type = %s';
				$params[] = 'mail';
			} elseif ( 'plugins' === $type ) {
				$where[] = 'type = %s';
				$params[] = 'plugin';
			} elseif ( 'themes' === $type ) {
				$where[] = 'type = %s';
				$params[] = 'theme';
			} elseif ( 'cron' === $type ) {
				$where[] = 'type = %s';
				$params[] = 'cron';
			} elseif ( 'database' === $type ) {
				$where[] = 'type = %s';
				$params[] = 'database';
			} elseif ( 'users' === $type ) {
				$where[] = 'type = %s';
				$params[] = 'user';
			} else {
				$where[]  = 'type = %s';
				$params[] = $type;
			}
		}
		if ( $search ) {
			$where[]  = '(location LIKE %s OR title LIKE %s OR matched_pattern LIKE %s OR snippet LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$where_sql = implode( ' AND ', $where );
		$sql       = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$order} LIMIT %d OFFSET %d";
		$params[]  = $limit;
		$params[]  = $offset;
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
	}

	/**
	 * Count by severity.
	 *
	 * @param string $scan_id Scan.
	 * @return array
	 */
	public static function counts( $scan_id ) {
		global $wpdb;
		$table = self::table();
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT severity, COUNT(*) AS c FROM {$table} WHERE scan_id = %s GROUP BY severity",
				$scan_id
			),
			ARRAY_A
		);
		$out = array(
			'critical' => 0,
			'high'     => 0,
			'medium'   => 0,
			'low'      => 0,
			'info'     => 0,
			'total'    => 0,
		);
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$key = $row['severity'];
				if ( isset( $out[ $key ] ) ) {
					$out[ $key ] = (int) $row['c'];
					$out['total'] += (int) $row['c'];
				}
			}
		}
		return $out;
	}

	/**
	 * Get one.
	 *
	 * @param int    $id      ID.
	 * @param string $scan_id Optional scan constraint.
	 * @return array|null
	 */
	public static function get( $id, $scan_id = '' ) {
		global $wpdb;
		$table = self::table();
		if ( $scan_id ) {
			$row = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d AND scan_id = %s", $id, $scan_id ),
				ARRAY_A
			);
		} else {
			$row = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ),
				ARRAY_A
			);
		}
		return $row ? $row : null;
	}

	/**
	 * All for report.
	 *
	 * @param string $scan_id Scan.
	 * @return array
	 */
	public static function all( $scan_id ) {
		global $wpdb;
		$table = self::table();
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE scan_id = %s ORDER BY FIELD(severity,'critical','high','medium','low','info'), id ASC", $scan_id ),
			ARRAY_A
		);
	}
}
