<?php
/**
 * Admin UI and AJAX.
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin.
 */
class WPSMS_Admin {

	/**
	 * Instance.
	 *
	 * @var WPSMS_Admin|null
	 */
	private static $instance = null;

	/**
	 * Instance.
	 *
	 * @return WPSMS_Admin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_init', array( $this, 'maybe_download' ) );
		add_action( 'admin_post_wpsms_save_settings', array( $this, 'save_settings' ) );

		$ajax = array(
			'wpsms_start_scan'   => 'ajax_start_scan',
			'wpsms_scan_tick'    => 'ajax_scan_tick',
			'wpsms_stop_scan'    => 'ajax_stop_scan',
			'wpsms_get_status'   => 'ajax_get_status',
			'wpsms_get_findings' => 'ajax_get_findings',
			'wpsms_get_finding'  => 'ajax_get_finding',
			'wpsms_view_file'    => 'ajax_view_file',
			'wpsms_hash_file'    => 'ajax_hash_file',
			'wpsms_quarantine'   => 'ajax_quarantine',
			'wpsms_delete_file'  => 'ajax_delete_file',
			'wpsms_export_report'=> 'ajax_export_report',
		);
		foreach ( $ajax as $action => $method ) {
			add_action( 'wp_ajax_' . $action, array( $this, $method ) );
		}
	}

	/**
	 * Tools menu.
	 */
	public function menu() {
		add_management_page(
			__( 'Security & Mail Scanner', 'wp-security-mail-scanner' ),
			__( 'Security & Mail Scanner', 'wp-security-mail-scanner' ),
			WPSMS_CAPABILITY,
			'wpsms-scanner',
			array( $this, 'render' )
		);
	}

	/**
	 * Assets.
	 *
	 * @param string $hook Hook.
	 */
	public function assets( $hook ) {
		if ( 'tools_page_wpsms-scanner' !== $hook ) {
			return;
		}
		wp_enqueue_style(
			'wpsms-admin',
			WPSMS_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			WPSMS_VERSION
		);
		wp_enqueue_script(
			'wpsms-admin',
			WPSMS_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			WPSMS_VERSION,
			true
		);
		wp_localize_script(
			'wpsms-admin',
			'wpsmsAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wpsms_admin' ),
				'downloadUrl' => admin_url( 'tools.php?page=wpsms-scanner' ),
				'i18n'    => array(
					'confirmQuarantine' => __( 'Quarantine this file? A backup will be created first. Type CONFIRM in the next prompt.', 'wp-security-mail-scanner' ),
					'typeConfirm'       => __( 'Type CONFIRM to continue.', 'wp-security-mail-scanner' ),
					'confirmDelete1'    => __( 'Delete this file? A backup will be created. This cannot be undone except from the backup. Click OK then type DELETE.', 'wp-security-mail-scanner' ),
					'typeDelete'        => __( 'Type DELETE to permanently remove the file (backup is kept in the plugin quarantine folder).', 'wp-security-mail-scanner' ),
				),
			)
		);
	}

	/**
	 * Render page.
	 */
	public function render() {
		if ( ! WPSMS_Helpers::current_user_can_scan() ) {
			wp_die( esc_html__( 'You do not have permission to access this scanner.', 'wp-security-mail-scanner' ) );
		}
		$job      = WPSMS_Scanner::get_job();
		$last     = WPSMS_Scanner::last_scan();
		$settings = WPSMS_Helpers::settings();
		$incident = get_option( 'wpsms_mail_incident', array() );
		$tab      = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		include WPSMS_PLUGIN_DIR . 'admin/dashboard.php';
	}

	/**
	 * Non-AJAX backup download.
	 */
	public function maybe_download() {
		if ( ! isset( $_GET['wpsms_download'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		if ( ! WPSMS_Helpers::current_user_can_scan() ) {
			wp_die( esc_html__( 'Forbidden', 'wp-security-mail-scanner' ) );
		}
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wpsms_admin' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'wp-security-mail-scanner' ) );
		}
		$path = isset( $_GET['file'] ) ? wp_unslash( $_GET['file'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		WPSMS_File_Actions::download_backup( $path );
	}

	/**
	 * Save settings.
	 */
	public function save_settings() {
		if ( ! WPSMS_Helpers::current_user_can_scan() ) {
			wp_die( esc_html__( 'Forbidden', 'wp-security-mail-scanner' ) );
		}
		check_admin_referer( 'wpsms_save_settings' );
		$settings = WPSMS_Helpers::settings();
		$settings['batch_size']         = isset( $_POST['batch_size'] ) ? max( 5, min( 200, (int) $_POST['batch_size'] ) ) : 40;
		$settings['inventory_batch']    = isset( $_POST['inventory_batch'] ) ? max( 50, min( 2000, (int) $_POST['inventory_batch'] ) ) : 400;
		$settings['max_file_bytes']     = isset( $_POST['max_file_bytes'] ) ? max( 10240, min( 5242880, (int) $_POST['max_file_bytes'] ) ) : 1048576;
		$settings['known_admin_logins'] = isset( $_POST['known_admin_logins'] ) ? sanitize_textarea_field( wp_unslash( $_POST['known_admin_logins'] ) ) : '';
		$settings['exclude_paths']      = isset( $_POST['exclude_paths'] ) ? sanitize_textarea_field( wp_unslash( $_POST['exclude_paths'] ) ) : '';
		$settings['quick_skip_core']    = empty( $_POST['quick_skip_core'] ) ? 0 : 1;
		update_option( 'wpsms_settings', $settings );
		wp_safe_redirect( add_query_arg( array( 'page' => 'wpsms-scanner', 'tab' => 'settings', 'updated' => '1' ), admin_url( 'tools.php' ) ) );
		exit;
	}

	/**
	 * Start scan.
	 */
	public function ajax_start_scan() {
		WPSMS_Helpers::require_ajax_access();
		$mode  = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'full';
		$force = ! empty( $_POST['force'] );
		$job   = WPSMS_Scanner::start( $mode, $force );
		wp_send_json_success( $this->status_payload( $job ) );
	}

	/**
	 * Tick.
	 */
	public function ajax_scan_tick() {
		WPSMS_Helpers::require_ajax_access();
		$job = WPSMS_Scanner::tick();
		wp_send_json_success( $this->status_payload( $job ) );
	}

	/**
	 * Stop.
	 */
	public function ajax_stop_scan() {
		WPSMS_Helpers::require_ajax_access();
		$job = WPSMS_Scanner::request_stop();
		wp_send_json_success( $this->status_payload( $job ) );
	}

	/**
	 * Status.
	 */
	public function ajax_get_status() {
		WPSMS_Helpers::require_ajax_access();
		wp_send_json_success( $this->status_payload( WPSMS_Scanner::get_job() ) );
	}

	/**
	 * Findings list.
	 */
	public function ajax_get_findings() {
		WPSMS_Helpers::require_ajax_access();
		$last    = WPSMS_Scanner::last_scan();
		$job     = WPSMS_Scanner::get_job();
		$scan_id = isset( $job['scan_id'] ) ? $job['scan_id'] : ( isset( $last['scan_id'] ) ? $last['scan_id'] : '' );
		if ( ! $scan_id ) {
			wp_send_json_success( array( 'rows' => array(), 'counts' => array() ) );
		}
		$rows = WPSMS_Findings_Store::query(
			$scan_id,
			array(
				'severity' => isset( $_POST['severity'] ) ? sanitize_key( wp_unslash( $_POST['severity'] ) ) : 'all',
				'type'     => isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'all',
				'search'   => isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '',
				'offset'   => isset( $_POST['offset'] ) ? (int) $_POST['offset'] : 0,
				'limit'    => isset( $_POST['limit'] ) ? (int) $_POST['limit'] : 50,
			)
		);
		wp_send_json_success(
			array(
				'rows'   => $rows,
				'counts' => WPSMS_Findings_Store::counts( $scan_id ),
			)
		);
	}

	/**
	 * One finding.
	 */
	public function ajax_get_finding() {
		WPSMS_Helpers::require_ajax_access();
		$id  = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$row = WPSMS_Findings_Store::get( $id );
		if ( ! $row ) {
			wp_send_json_error( array( 'message' => __( 'Finding not found.', 'wp-security-mail-scanner' ) ), 404 );
		}
		wp_send_json_success( $row );
	}

	/**
	 * View file.
	 */
	public function ajax_view_file() {
		WPSMS_Helpers::require_ajax_access();
		$path = isset( $_POST['file'] ) ? wp_unslash( $_POST['file'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$res  = WPSMS_File_Actions::view( $path );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ) );
		}
		wp_send_json_success( $res );
	}

	/**
	 * Hash.
	 */
	public function ajax_hash_file() {
		WPSMS_Helpers::require_ajax_access();
		$path = isset( $_POST['file'] ) ? wp_unslash( $_POST['file'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$res  = WPSMS_File_Actions::hash( $path );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ) );
		}
		wp_send_json_success( $res );
	}

	/**
	 * Quarantine.
	 */
	public function ajax_quarantine() {
		WPSMS_Helpers::require_ajax_access();
		$path    = isset( $_POST['file'] ) ? wp_unslash( $_POST['file'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$confirm = isset( $_POST['confirm'] ) ? sanitize_text_field( wp_unslash( $_POST['confirm'] ) ) : '';
		$res     = WPSMS_File_Actions::quarantine( $path, $confirm );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ) );
		}
		wp_send_json_success( $res );
	}

	/**
	 * Delete.
	 */
	public function ajax_delete_file() {
		WPSMS_Helpers::require_ajax_access();
		$path     = isset( $_POST['file'] ) ? wp_unslash( $_POST['file'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$confirm1 = isset( $_POST['confirm'] ) ? sanitize_text_field( wp_unslash( $_POST['confirm'] ) ) : '';
		$confirm2 = isset( $_POST['confirm2'] ) ? sanitize_text_field( wp_unslash( $_POST['confirm2'] ) ) : '';
		$res      = WPSMS_File_Actions::delete( $path, $confirm1, $confirm2 );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ) );
		}
		wp_send_json_success( $res );
	}

	/**
	 * Export report.
	 */
	public function ajax_export_report() {
		WPSMS_Helpers::require_ajax_access();
		$last    = WPSMS_Scanner::last_scan();
		$job     = WPSMS_Scanner::get_job();
		$scan_id = isset( $job['scan_id'] ) ? $job['scan_id'] : ( isset( $last['scan_id'] ) ? $last['scan_id'] : '' );
		if ( ! $scan_id ) {
			wp_send_json_error( array( 'message' => __( 'No scan report is available yet.', 'wp-security-mail-scanner' ) ) );
		}
		$format = isset( $_POST['format'] ) ? sanitize_key( wp_unslash( $_POST['format'] ) ) : 'json';
		$report = WPSMS_Report::build( $scan_id );
		if ( 'csv' === $format ) {
			wp_send_json_success( array( 'filename' => 'wpsms-report.csv', 'mime' => 'text/csv', 'body' => WPSMS_Report::to_csv( $report ) ) );
		}
		if ( 'html' === $format ) {
			wp_send_json_success( array( 'filename' => 'wpsms-report.html', 'mime' => 'text/html', 'body' => WPSMS_Report::to_html( $report ) ) );
		}
		wp_send_json_success( array( 'filename' => 'wpsms-report.json', 'mime' => 'application/json', 'body' => WPSMS_Report::to_json( $report ) ) );
	}

	/**
	 * Status payload.
	 *
	 * @param array $job Job.
	 * @return array
	 */
	protected function status_payload( $job ) {
		$last   = WPSMS_Scanner::last_scan();
		$counts = array();
		$sid    = isset( $job['scan_id'] ) ? $job['scan_id'] : ( isset( $last['scan_id'] ) ? $last['scan_id'] : '' );
		if ( $sid ) {
			$counts = WPSMS_Findings_Store::counts( $sid );
		}
		return array(
			'job'     => $job,
			'last'    => $last,
			'counts'  => $counts,
			'incident'=> get_option( 'wpsms_mail_incident', array() ),
		);
	}
}
