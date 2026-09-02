<?php
/**
 * Batched scan orchestrator. Never modifies site files.
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scanner job runner.
 */
class WPSMS_Scanner {

	/**
	 * Option key.
	 */
	const JOB_OPTION = 'wpsms_scan_job';

	/**
	 * Start a scan.
	 *
	 * @param string $mode  full|quick.
	 * @param bool   $force Restart even if a scan is already running.
	 * @return array
	 */
	public static function start( $mode = 'full', $force = false ) {
		$mode  = ( 'quick' === $mode ) ? 'quick' : 'full';
		$job   = self::get_job();
		$stale = empty( $job['updated_at'] ) || ( time() - (int) $job['updated_at'] ) > 90;
		$busy  = isset( $job['status'] ) && in_array( $job['status'], array( 'running', 'stopping' ), true );
		if ( $busy && ! $force && ! $stale ) {
			return $job;
		}

		$scan_id = wp_generate_password( 12, false, false );
		WPSMS_Findings_Store::clear_scan( $scan_id );
		self::reset_list_file();

		$job = array(
			'status'           => 'running',
			'mode'             => $mode,
			'phase'            => 'inventory',
			'scan_id'          => $scan_id,
			'offset'           => 0,
			'db_offset'        => 0,
			'total_files'      => 0,
			'files_scanned'    => 0,
			'skipped'          => 0,
			'inventory_cursor' => '',
			'started_at'       => time(),
			'finished_at'      => 0,
			'last_error'       => '',
			'progress'         => 1,
			'stop_requested'   => false,
			'watchdog_file'    => '',
			'watchdog_hits'    => 0,
			'tick_lock'        => 0,
		);
		self::save_job( $job );
		return $job;
	}

	/**
	 * Request stop.
	 *
	 * @return array
	 */
	public static function request_stop() {
		$job = self::get_job();
		if ( isset( $job['status'] ) && 'running' === $job['status'] ) {
			$job['stop_requested'] = true;
			$job['status']         = 'stopping';
			self::save_job( $job );
		}
		return $job;
	}

	/**
	 * Get job.
	 *
	 * @return array
	 */
	public static function get_job() {
		$job = get_option( self::JOB_OPTION, array() );
		return is_array( $job ) ? $job : array();
	}

	/**
	 * Save job.
	 *
	 * @param array $job Job.
	 */
	public static function save_job( array $job ) {
		$job['updated_at'] = time();
		update_option( self::JOB_OPTION, $job, false );
	}

	/**
	 * Process one tick.
	 *
	 * @return array
	 */
	public static function tick() {
		@set_time_limit( 20 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@ini_set( 'max_execution_time', '20' ); // phpcs:ignore WordPress.PHP.IniSet.Risky, WordPress.PHP.NoSilencedErrors.Discouraged
		$job = self::get_job();
		if ( empty( $job['status'] ) ) {
			return array( 'status' => 'idle' );
		}
		if ( ! empty( $job['stop_requested'] ) ) {
			$job['status']      = 'stopped';
			$job['finished_at'] = time();
			$job['progress']    = 100;
			$job['tick_lock']   = 0;
			self::save_job( $job );
			self::store_last_scan( $job );
			return $job;
		}
		if ( 'running' !== $job['status'] ) {
			return $job;
		}

		$lock = isset( $job['tick_lock'] ) ? (int) $job['tick_lock'] : 0;
		if ( $lock && ( time() - $lock ) < 12 ) {
			return $job;
		}
		$job['tick_lock'] = time();
		self::save_job( $job );

		try {
			switch ( $job['phase'] ) {
				case 'inventory':
					$job = self::phase_inventory( $job );
					break;
				case 'files':
					$job = self::phase_files( $job );
					break;
				case 'users':
					$job = self::phase_simple( $job, 'users', array( new WPSMS_User_Scanner(), 'scan' ), 'plugins', 82 );
					break;
				case 'plugins':
					$job = self::phase_simple( $job, 'plugins', array( new WPSMS_Plugin_Scanner(), 'scan' ), 'themes', 86 );
					break;
				case 'themes':
					$job = self::phase_simple( $job, 'themes', array( new WPSMS_Theme_Scanner(), 'scan' ), 'cron', 90 );
					break;
				case 'cron':
					$job = self::phase_simple( $job, 'cron', array( new WPSMS_Cron_Scanner(), 'scan' ), 'database', 92 );
					break;
				case 'database':
					$job = self::phase_database( $job );
					break;
				case 'core':
					$job = self::phase_simple( $job, 'core', array( new WPSMS_Core_Integrity(), 'scan' ), 'correlate', 97 );
					break;
				case 'correlate':
					$job = self::phase_correlate( $job );
					break;
				default:
					$job['status']      = 'complete';
					$job['progress']    = 100;
					$job['finished_at'] = time();
					self::store_last_scan( $job );
					break;
			}
		} catch ( Throwable $e ) { // phpcs:ignore PHPCompatibility.FunctionDeclarations.NewKeywords.t_throwableFound
			$job = self::skip_and_continue( $job, $e->getMessage() );
		}

		$job['tick_lock'] = 0;
		self::save_job( $job );
		return $job;
	}

	/**
	 * Skip the current unit of work and keep the scan running.
	 *
	 * @param array  $job     Job.
	 * @param string $message Error.
	 * @return array
	 */
	protected static function skip_and_continue( array $job, $message ) {
		$job['skipped']    = isset( $job['skipped'] ) ? (int) $job['skipped'] + 1 : 1;
		$job['last_error'] = substr( (string) $message, 0, 300 );
		$job['status']     = 'running';

		$phase = isset( $job['phase'] ) ? $job['phase'] : '';
		if ( 'files' === $phase ) {
			$job['offset']         = (int) $job['offset'] + 1;
			$job['files_scanned']  = (int) $job['files_scanned'] + 1;
			$job['watchdog_file']  = '';
			$job['watchdog_hits']  = 0;
		} elseif ( 'inventory' === $phase ) {
			$state = self::read_inventory_state();
			if ( ! empty( $state['stack'] ) ) {
				array_pop( $state['stack'] );
				self::write_inventory_state( $state );
			}
		} elseif ( 'database' === $phase ) {
			$job['db_offset'] = (int) $job['db_offset'] + 80;
		} elseif ( 'core' === $phase || 'correlate' === $phase ) {
			if ( 'core' === $phase ) {
				$job['phase'] = 'correlate';
			} else {
				$job['status']      = 'complete';
				$job['progress']    = 100;
				$job['finished_at'] = time();
				self::store_last_scan( $job );
			}
		} else {
			$next = array(
				'users'   => 'plugins',
				'plugins' => 'themes',
				'themes'  => 'cron',
				'cron'    => 'database',
			);
			if ( isset( $next[ $phase ] ) ) {
				$job['phase'] = $next[ $phase ];
			}
		}
		return $job;
	}

	/**
	 * Inventory files into a state file.
	 *
	 * @param array $job Job.
	 * @return array
	 */
	protected static function phase_inventory( array $job ) {
		$settings = WPSMS_Helpers::settings();
		$paths    = new WPSMS_Paths();
		$quick    = ( 'quick' === $job['mode'] );
		$batch    = min( 200, max( 40, (int) $settings['inventory_batch'] ) );
		$exts     = array_fill_keys( $paths->scan_extensions( $quick ), true );
		$skip     = $paths->skip_dir_names();
		$extra    = array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', (string) $settings['exclude_paths'] ) ) );
		$skip     = array_unique( array_merge( $skip, $extra ) );

		$list_file = self::list_file();
		$state     = self::read_inventory_state();
		if ( empty( $state['stack'] ) ) {
			$state = array(
				'stack' => $paths->scan_roots( $quick ),
				'seen'  => array(),
			);
		}

		$added    = 0;
		$deadline = microtime( true ) + 8;
		$fh       = @fopen( $list_file, 'ab' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $fh ) {
			$job['skipped']    = isset( $job['skipped'] ) ? (int) $job['skipped'] + 1 : 1;
			$job['last_error'] = 'Could not write file list; continuing with later scan phases.';
			$job['phase']      = 'files';
			return $job;
		}
		while ( $added < $batch && ! empty( $state['stack'] ) && microtime( true ) < $deadline ) {
			$current = array_pop( $state['stack'] );
			$real    = @realpath( $current ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( ! $real ) {
				continue;
			}
			if ( is_dir( $real ) ) {
				if ( isset( $state['seen'][ $real ] ) ) {
					continue;
				}
				$state['seen'][ $real ] = 1;
				if ( $paths->is_self( $real ) ) {
					continue;
				}
				$base = basename( $real );
				if ( in_array( $base, $skip, true ) ) {
					continue;
				}
				if ( $quick && $settings['quick_skip_core'] && $paths->is_core_dir( $real ) ) {
					continue;
				}
				$items = @scandir( $real ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				if ( ! $items ) {
					++$job['skipped'];
					$job['last_error'] = 'Skipped unreadable directory: ' . $base;
					continue;
				}
				foreach ( $items as $item ) {
					if ( '.' === $item || '..' === $item ) {
						continue;
					}
					$state['stack'][] = $real . DIRECTORY_SEPARATOR . $item;
				}
				continue;
			}
			if ( ! is_file( $real ) ) {
				continue;
			}
			$ext = strtolower( pathinfo( $real, PATHINFO_EXTENSION ) );
			$bn  = strtolower( basename( $real ) );
			if ( preg_match( '/\.min\.(js|css)$/i', $bn ) ) {
				continue;
			}
			if ( ! isset( $exts[ $ext ] ) && 'htaccess' !== $bn && '.htaccess' !== $bn ) {
				if ( $paths->is_uploads( $real ) && in_array( $ext, array( 'exe', 'sh', 'bat' ), true ) ) {
					// keep.
				} else {
					continue;
				}
			}
			fwrite( $fh, $real . "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
			++$added;
			++$job['total_files'];
		}
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		self::write_inventory_state( $state );

		$job['progress'] = min( 15, 2 + (int) ( $job['total_files'] / 500 ) );
		if ( empty( $state['stack'] ) ) {
			$job['phase']  = 'files';
			$job['offset'] = 0;
			$job['progress'] = 18;
		}
		return $job;
	}

	/**
	 * Scan files batch.
	 *
	 * @param array $job Job.
	 * @return array
	 */
	protected static function phase_files( array $job ) {
		$settings = WPSMS_Helpers::settings();
		$batch    = min( 20, max( 5, (int) $settings['batch_size'] ) );
		$offset   = (int) $job['offset'];
		$lines    = self::read_list_slice( $offset, $batch );
		if ( empty( $lines ) ) {
			$job['phase']    = 'users';
			$job['progress'] = 80;
			return $job;
		}

		$first = trim( $lines[0] );
		if ( $first && isset( $job['watchdog_file'] ) && $first === $job['watchdog_file'] ) {
			$job['watchdog_hits'] = isset( $job['watchdog_hits'] ) ? (int) $job['watchdog_hits'] + 1 : 1;
		} else {
			$job['watchdog_file'] = $first;
			$job['watchdog_hits'] = 0;
		}
		if ( ! empty( $job['watchdog_hits'] ) && (int) $job['watchdog_hits'] >= 2 ) {
			array_shift( $lines );
			++$offset;
			++$job['files_scanned'];
			$job['skipped']       = isset( $job['skipped'] ) ? (int) $job['skipped'] + 1 : 1;
			$job['last_error']    = 'Skipped stuck file: ' . $first;
			$job['watchdog_file'] = '';
			$job['watchdog_hits'] = 0;
		}
		self::save_job( $job );

		$paths     = new WPSMS_Paths();
		$filescan  = new WPSMS_File_Scanner( $paths, $settings );
		$mailscan  = new WPSMS_Mail_Scanner( $paths );
		$upscan    = new WPSMS_Uploads_Scanner( $paths );
		$deadline  = microtime( true ) + 8;
		$processed = 0;

		foreach ( $lines as $path ) {
			if ( microtime( true ) >= $deadline ) {
				break;
			}
			$path = trim( $path );
			if ( '' === $path ) {
				++$processed;
				continue;
			}
			try {
				$rows = $filescan->scan_file( $path, $job['scan_id'] );
				$max  = (int) $settings['max_file_bytes'];
				if ( is_readable( $path ) && is_file( $path ) && filesize( $path ) <= $max ) {
					$content = @file_get_contents( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
					if ( is_string( $content ) && false === strpos( $content, "\0" ) ) {
						$meta = WPSMS_Helpers::file_meta( $path );
						$rows = array_merge( $rows, $mailscan->extra_mail_findings( $path, $content, $job['scan_id'], $meta ) );
					}
				}
				$rows = array_merge( $rows, $upscan->scan_uploads_file( $path, $job['scan_id'] ) );
				foreach ( $rows as $row ) {
					try {
						WPSMS_Findings_Store::add( $row );
					} catch ( Throwable $e ) { // phpcs:ignore PHPCompatibility.FunctionDeclarations.NewKeywords.t_throwableFound
						$job['skipped']    = isset( $job['skipped'] ) ? (int) $job['skipped'] + 1 : 1;
						$job['last_error'] = $e->getMessage();
					}
				}
			} catch ( Throwable $e ) { // phpcs:ignore PHPCompatibility.FunctionDeclarations.NewKeywords.t_throwableFound
				$job['skipped']    = isset( $job['skipped'] ) ? (int) $job['skipped'] + 1 : 1;
				$job['last_error'] = 'Skipped ' . $path . ': ' . $e->getMessage();
			}
			++$job['files_scanned'];
			++$processed;
		}

		$job['offset']        = $offset + $processed;
		$total                = max( 1, (int) $job['total_files'] );
		$job['progress']      = 18 + (int) min( 60, ( $job['files_scanned'] / $total ) * 60 );
		$job['watchdog_file'] = '';
		$job['watchdog_hits'] = 0;

		if ( ( $offset + $processed ) >= (int) $job['total_files'] ) {
			$job['phase']    = 'users';
			$job['progress'] = 80;
		}
		return $job;
	}

	/**
	 * Database phase.
	 *
	 * @param array $job Job.
	 * @return array
	 */
	protected static function phase_database( array $job ) {
		try {
			$scanner = new WPSMS_Database_Scanner();
			$result  = $scanner->scan_batch( $job['scan_id'], (int) $job['db_offset'], 80 );
			foreach ( $result['findings'] as $row ) {
				try {
					WPSMS_Findings_Store::add( $row );
				} catch ( Throwable $e ) { // phpcs:ignore PHPCompatibility.FunctionDeclarations.NewKeywords.t_throwableFound
					$job['skipped']    = isset( $job['skipped'] ) ? (int) $job['skipped'] + 1 : 1;
					$job['last_error'] = $e->getMessage();
				}
			}
			$job['db_offset'] = $result['next_offset'];
			$job['progress']  = min( 96, 92 + (int) ( $job['db_offset'] / 400 ) );
			if ( $result['done'] ) {
				$next            = ( 'quick' === $job['mode'] ) ? 'correlate' : 'core';
				$job['phase']    = $next;
				$job['progress'] = 96;
			}
		} catch ( Throwable $e ) { // phpcs:ignore PHPCompatibility.FunctionDeclarations.NewKeywords.t_throwableFound
			$job = self::skip_and_continue( $job, $e->getMessage() );
		}
		return $job;
	}

	/**
	 * Simple phase.
	 *
	 * @param array    $job      Job.
	 * @param string   $name     Name.
	 * @param callable $callback Callback.
	 * @param string   $next     Next phase.
	 * @param int      $progress Progress.
	 * @return array
	 */
	protected static function phase_simple( array $job, $name, $callback, $next, $progress ) {
		try {
			$findings = call_user_func( $callback, $job['scan_id'] );
			if ( is_array( $findings ) ) {
				foreach ( $findings as $row ) {
					try {
						WPSMS_Findings_Store::add( $row );
					} catch ( Throwable $e ) { // phpcs:ignore PHPCompatibility.FunctionDeclarations.NewKeywords.t_throwableFound
						$job['skipped']    = isset( $job['skipped'] ) ? (int) $job['skipped'] + 1 : 1;
						$job['last_error'] = $e->getMessage();
					}
				}
			}
		} catch ( Throwable $e ) { // phpcs:ignore PHPCompatibility.FunctionDeclarations.NewKeywords.t_throwableFound
			$job['skipped']    = isset( $job['skipped'] ) ? (int) $job['skipped'] + 1 : 1;
			$job['last_error'] = $name . ': ' . $e->getMessage();
		}
		$job['phase']    = $next;
		$job['progress'] = $progress;
		return $job;
	}

	/**
	 * Correlate mail incident.
	 *
	 * @param array $job Job.
	 * @return array
	 */
	protected static function phase_correlate( array $job ) {
		try {
			$all     = WPSMS_Findings_Store::all( $job['scan_id'] );
			$summary = WPSMS_Mail_Scanner::build_incident_summary( is_array( $all ) ? $all : array(), $job );
			update_option( 'wpsms_mail_incident', $summary, false );
		} catch ( Throwable $e ) { // phpcs:ignore PHPCompatibility.FunctionDeclarations.NewKeywords.t_throwableFound
			$job['skipped']    = isset( $job['skipped'] ) ? (int) $job['skipped'] + 1 : 1;
			$job['last_error'] = $e->getMessage();
		}
		$job['phase']       = 'done';
		$job['status']      = 'complete';
		$job['progress']    = 100;
		$job['finished_at'] = time();
		self::store_last_scan( $job );
		return $job;
	}

	/**
	 * Persist last scan summary.
	 *
	 * @param array $job Job.
	 */
	protected static function store_last_scan( array $job ) {
		$counts = array();
		if ( ! empty( $job['scan_id'] ) ) {
			$counts = WPSMS_Findings_Store::counts( $job['scan_id'] );
		}
		update_option(
			'wpsms_last_scan',
			array(
				'scan_id'       => isset( $job['scan_id'] ) ? $job['scan_id'] : '',
				'finished_at'   => isset( $job['finished_at'] ) ? $job['finished_at'] : time(),
				'started_at'    => isset( $job['started_at'] ) ? $job['started_at'] : 0,
				'mode'          => isset( $job['mode'] ) ? $job['mode'] : 'full',
				'files_scanned' => isset( $job['files_scanned'] ) ? $job['files_scanned'] : 0,
				'total_files'   => isset( $job['total_files'] ) ? $job['total_files'] : 0,
				'status'        => isset( $job['status'] ) ? $job['status'] : '',
				'counts'        => $counts,
			),
			false
		);
	}

	/**
	 * Public last scan.
	 *
	 * @return array
	 */
	public static function last_scan() {
		$last = get_option( 'wpsms_last_scan', array() );
		return is_array( $last ) ? $last : array();
	}

	/**
	 * List file path.
	 *
	 * @return string
	 */
	protected static function list_file() {
		$dir = WPSMS_PLUGIN_DIR . 'cache';
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		return $dir . '/file-list.txt';
	}

	/**
	 * Inventory state.
	 *
	 * @return string
	 */
	protected static function inventory_state_file() {
		return WPSMS_PLUGIN_DIR . 'cache/inventory.json';
	}

	/**
	 * Reset list.
	 */
	protected static function reset_list_file() {
		$list = self::list_file();
		file_put_contents( $list, '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$state = self::inventory_state_file();
		file_put_contents( $state, wp_json_encode( array( 'stack' => array(), 'seen' => array() ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	/**
	 * Read inventory state.
	 *
	 * @return array
	 */
	protected static function read_inventory_state() {
		$file = self::inventory_state_file();
		if ( ! is_readable( $file ) ) {
			return array();
		}
		$data = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Write inventory state.
	 *
	 * @param array $state State.
	 */
	protected static function write_inventory_state( array $state ) {
		if ( isset( $state['seen'] ) && count( $state['seen'] ) > 200000 ) {
			$state['seen'] = array_slice( $state['seen'], -50000, null, true );
		}
		file_put_contents( self::inventory_state_file(), wp_json_encode( $state ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	/**
	 * Read slice of file list.
	 *
	 * @param int $offset Offset lines.
	 * @param int $limit  Limit.
	 * @return array
	 */
	protected static function read_list_slice( $offset, $limit ) {
		$file = self::list_file();
		if ( ! is_readable( $file ) ) {
			return array();
		}
		$lines  = array();
		$handle = fopen( $file, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $handle ) {
			return array();
		}
		$i = 0;
		while ( ! feof( $handle ) ) {
			$line = fgets( $handle );
			if ( false === $line ) {
				break;
			}
			if ( $i++ < $offset ) {
				continue;
			}
			$lines[] = $line;
			if ( count( $lines ) >= $limit ) {
				break;
			}
		}
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		return $lines;
	}
}
