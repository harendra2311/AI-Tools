<?php
/**
 * Plugin bootstrap.
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin singleton.
 */
class WPSMS_Plugin {

	/**
	 * Instance.
	 *
	 * @var WPSMS_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return WPSMS_Plugin
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
		$this->includes();
		WPSMS_Install::maybe_upgrade();

		if ( is_admin() ) {
			WPSMS_Admin::instance();
		}

		add_action( 'wp_login', array( $this, 'record_last_login' ), 10, 2 );
		add_filter( 'cron_schedules', array( $this, 'register_schedules' ) );
	}

	/**
	 * Load includes.
	 */
	private function includes() {
		$files = array(
			'includes/class-install.php',
			'includes/class-helpers.php',
			'includes/class-paths.php',
			'includes/class-redactor.php',
			'includes/class-patterns.php',
			'includes/class-risk-engine.php',
			'includes/class-findings-store.php',
			'includes/class-file-scanner.php',
			'includes/class-mail-scanner.php',
			'includes/class-uploads-scanner.php',
			'includes/class-database-scanner.php',
			'includes/class-cron-scanner.php',
			'includes/class-user-scanner.php',
			'includes/class-plugin-scanner.php',
			'includes/class-theme-scanner.php',
			'includes/class-core-integrity.php',
			'includes/class-url-extractor.php',
			'includes/class-scanner.php',
			'includes/class-quarantine.php',
			'includes/class-file-actions.php',
			'includes/class-report.php',
			'admin/class-admin.php',
		);

		foreach ( $files as $file ) {
			require_once WPSMS_PLUGIN_DIR . $file;
		}
	}

	/**
	 * Activation.
	 */
	public static function activate() {
		require_once WPSMS_PLUGIN_DIR . 'includes/class-install.php';
		WPSMS_Install::activate();
	}

	/**
	 * Deactivation. Scans are stopped; data is retained.
	 */
	public static function deactivate() {
		$job = get_option( 'wpsms_scan_job', array() );
		if ( is_array( $job ) && isset( $job['status'] ) && 'running' === $job['status'] ) {
			$job['status'] = 'stopped';
			update_option( 'wpsms_scan_job', $job, false );
		}
	}

	/**
	 * Record last login in user meta (best-effort; not available historically).
	 *
	 * @param string  $user_login Login.
	 * @param WP_User $user       User.
	 */
	public function record_last_login( $user_login, $user ) {
		if ( $user instanceof WP_User ) {
			update_user_meta( $user->ID, 'wpsms_last_login', time() );
		}
	}

	/**
	 * No custom schedules required; hook kept for compatibility.
	 *
	 * @param array $schedules Schedules.
	 * @return array
	 */
	public function register_schedules( $schedules ) {
		return $schedules;
	}
}
