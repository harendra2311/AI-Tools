<?php
/**
 * Admin dashboard (Tools → Security & Mail Scanner).
 *
 * @package WPSMS
 *
 * @var array  $job
 * @var array  $last
 * @var array  $settings
 * @var array  $incident
 * @var string $tab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$counts = isset( $last['counts'] ) && is_array( $last['counts'] ) ? $last['counts'] : array(
	'critical' => 0,
	'high'     => 0,
	'medium'   => 0,
	'low'      => 0,
	'info'     => 0,
	'total'    => 0,
);
$tabs = array(
	'dashboard' => __( 'Dashboard', 'wp-security-mail-scanner' ),
	'findings'  => __( 'Findings', 'wp-security-mail-scanner' ),
	'mail'      => __( 'Mail incident', 'wp-security-mail-scanner' ),
	'report'    => __( 'Report', 'wp-security-mail-scanner' ),
	'settings'  => __( 'Settings', 'wp-security-mail-scanner' ),
);
if ( ! isset( $tabs[ $tab ] ) ) {
	$tab = 'dashboard';
}
$base = admin_url( 'tools.php?page=wpsms-scanner' );
?>
<div class="wrap wpsms-wrap">
	<h1><?php esc_html_e( 'WP Security & Mail Scanner', 'wp-security-mail-scanner' ); ?></h1>
	<p class="wpsms-lead">
		<?php esc_html_e( 'Investigation-first scanner for possible compromise and unauthorized spam or phishing email. Nothing is deleted or modified unless you confirm it.', 'wp-security-mail-scanner' ); ?>
	</p>

	<nav class="nav-tab-wrapper wpsms-tabs">
		<?php foreach ( $tabs as $id => $label ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'tab', $id, $base ) ); ?>" class="nav-tab <?php echo $tab === $id ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</nav>

	<?php if ( 'dashboard' === $tab ) : ?>
		<div class="wpsms-toolbar">
			<button type="button" class="button button-primary" id="wpsms-full-scan"><?php esc_html_e( 'Run Full Scan', 'wp-security-mail-scanner' ); ?></button>
			<button type="button" class="button" id="wpsms-quick-scan"><?php esc_html_e( 'Quick Scan', 'wp-security-mail-scanner' ); ?></button>
			<button type="button" class="button" id="wpsms-stop-scan"><?php esc_html_e( 'Stop scan', 'wp-security-mail-scanner' ); ?></button>
		</div>
		<div class="wpsms-progress-wrap">
			<div class="wpsms-progress"><span id="wpsms-progress-bar"></span></div>
			<p id="wpsms-status-text">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1 status 2 phase */
						__( 'Status: %1$s — phase: %2$s', 'wp-security-mail-scanner' ),
						isset( $job['status'] ) ? $job['status'] : 'idle',
						isset( $job['phase'] ) ? $job['phase'] : '—'
					)
				);
				?>
			</p>
		</div>
		<div class="wpsms-cards">
			<div class="wpsms-card">
				<h3><?php esc_html_e( 'Last scan', 'wp-security-mail-scanner' ); ?></h3>
				<p id="wpsms-last-scan"><?php echo esc_html( isset( $last['finished_at'] ) ? WPSMS_Helpers::format_datetime( $last['finished_at'] ) : '—' ); ?></p>
			</div>
			<div class="wpsms-card">
				<h3><?php esc_html_e( 'Files scanned', 'wp-security-mail-scanner' ); ?></h3>
				<p id="wpsms-files-scanned"><?php echo esc_html( isset( $last['files_scanned'] ) ? (string) $last['files_scanned'] : '0' ); ?></p>
			</div>
			<div class="wpsms-card">
				<h3><?php esc_html_e( 'Suspicious findings', 'wp-security-mail-scanner' ); ?></h3>
				<p id="wpsms-total"><?php echo esc_html( (string) $counts['total'] ); ?></p>
			</div>
			<div class="wpsms-card sev-critical">
				<h3>🔴 <?php esc_html_e( 'Critical', 'wp-security-mail-scanner' ); ?></h3>
				<p id="wpsms-critical"><?php echo esc_html( (string) $counts['critical'] ); ?></p>
			</div>
			<div class="wpsms-card sev-high">
				<h3>🟠 <?php esc_html_e( 'High', 'wp-security-mail-scanner' ); ?></h3>
				<p id="wpsms-high"><?php echo esc_html( (string) $counts['high'] ); ?></p>
			</div>
			<div class="wpsms-card sev-medium">
				<h3>🟡 <?php esc_html_e( 'Medium', 'wp-security-mail-scanner' ); ?></h3>
				<p id="wpsms-medium"><?php echo esc_html( (string) $counts['medium'] ); ?></p>
			</div>
			<div class="wpsms-card sev-low">
				<h3>🔵 <?php esc_html_e( 'Low / Info', 'wp-security-mail-scanner' ); ?></h3>
				<p id="wpsms-low"><?php echo esc_html( (string) ( (int) $counts['low'] + (int) $counts['info'] ) ); ?></p>
			</div>
		</div>
		<p class="description"><?php esc_html_e( 'Scans are batched over AJAX so large sites do not hit PHP timeouts. The scanner does not change website files while scanning.', 'wp-security-mail-scanner' ); ?></p>
	<?php endif; ?>

	<?php if ( 'findings' === $tab ) : ?>
		<?php include WPSMS_PLUGIN_DIR . 'admin/findings.php'; ?>
	<?php endif; ?>

	<?php if ( 'mail' === $tab ) : ?>
		<div class="wpsms-incident">
			<h2><?php esc_html_e( 'Possible Unauthorized Email Activity', 'wp-security-mail-scanner' ); ?></h2>
			<?php if ( empty( $incident['narrative'] ) ) : ?>
				<p><?php esc_html_e( 'Run a scan to generate the mail incident summary.', 'wp-security-mail-scanner' ); ?></p>
			<?php else : ?>
				<ol class="wpsms-questions">
					<li><?php esc_html_e( 'Is there suspicious mail-sending code?', 'wp-security-mail-scanner' ); ?>
						<strong><?php echo ! empty( $incident['answers']['has_suspicious_mail_code'] ) ? esc_html__( 'Yes — requires investigation', 'wp-security-mail-scanner' ) : esc_html__( 'No high-risk pattern confirmed', 'wp-security-mail-scanner' ); ?></strong>
					</li>
					<li><?php esc_html_e( 'Which files appear to send email?', 'wp-security-mail-scanner' ); ?>
						<code><?php echo esc_html( implode( ', ', array_slice( (array) $incident['answers']['mail_files'], 0, 20 ) ) ); ?></code>
					</li>
					<li><?php esc_html_e( 'Inside uploads/cache/unknown directories?', 'wp-security-mail-scanner' ); ?>
						<code><?php echo esc_html( implode( ', ', (array) $incident['answers']['uploads_or_cache'] ) ); ?></code>
					</li>
					<li><?php esc_html_e( 'Suspicious recipients?', 'wp-security-mail-scanner' ); ?>
						<code><?php echo esc_html( implode( ', ', array_slice( (array) $incident['answers']['recipients'], 0, 20 ) ) ); ?></code>
					</li>
					<li><?php esc_html_e( 'DocuSign / subject leads?', 'wp-security-mail-scanner' ); ?>
						<code><?php echo esc_html( implode( ', ', (array) $incident['answers']['subject_leads'] ) ); ?></code>
					</li>
					<li><?php esc_html_e( 'SMTP/API locations?', 'wp-security-mail-scanner' ); ?>
						<code><?php echo esc_html( implode( ', ', (array) $incident['answers']['smtp_locations'] ) ); ?></code>
					</li>
					<li><?php esc_html_e( 'Suspicious cron jobs?', 'wp-security-mail-scanner' ); ?>
						<code><?php echo esc_html( implode( ', ', (array) $incident['answers']['cron_leads'] ) ); ?></code>
					</li>
					<li><?php esc_html_e( 'Recently modified related files?', 'wp-security-mail-scanner' ); ?>
						<code><?php echo esc_html( implode( ', ', array_slice( (array) $incident['answers']['recently_modified'], 0, 15 ) ) ); ?></code>
					</li>
				</ol>
				<div class="wpsms-narrative">
					<?php foreach ( (array) $incident['narrative'] as $para ) : ?>
						<p><?php echo esc_html( $para ); ?></p>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( 'report' === $tab ) : ?>
		<h2><?php esc_html_e( 'Generate Security Report', 'wp-security-mail-scanner' ); ?></h2>
		<p><?php esc_html_e( 'Exports omit passwords, API keys, SMTP passwords, and authentication cookies.', 'wp-security-mail-scanner' ); ?></p>
		<p>
			<button type="button" class="button button-primary" id="wpsms-report-json"><?php esc_html_e( 'Export JSON', 'wp-security-mail-scanner' ); ?></button>
			<button type="button" class="button" id="wpsms-report-csv"><?php esc_html_e( 'Export CSV', 'wp-security-mail-scanner' ); ?></button>
			<button type="button" class="button" id="wpsms-report-html"><?php esc_html_e( 'Export HTML', 'wp-security-mail-scanner' ); ?></button>
		</p>
	<?php endif; ?>

	<?php if ( 'settings' === $tab ) : ?>
		<?php include WPSMS_PLUGIN_DIR . 'admin/settings.php'; ?>
	<?php endif; ?>
</div>
