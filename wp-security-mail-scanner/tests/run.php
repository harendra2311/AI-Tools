<?php
/**
 * Standalone checks for WP Security & Mail Scanner (no WordPress required).
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', sys_get_temp_dir() . '/wpsms-fake-wp/' );
}

$root = dirname( __DIR__ );
require_once $root . '/includes/class-patterns.php';
require_once $root . '/includes/class-redactor.php';
require_once $root . '/includes/class-paths.php';
require_once $root . '/includes/class-risk-engine.php';

$failures = 0;

function wpsms_assert( $cond, $msg ) {
	global $failures;
	if ( $cond ) {
		echo "OK  {$msg}\n";
		return;
	}
	++$failures;
	echo "FAIL {$msg}\n";
}

// --- Patterns exist ---
$patterns = WPSMS_Patterns::file_patterns();
wpsms_assert( count( $patterns ) >= 20, 'pattern catalog is populated' );

// --- mail() does not match wp_mail() ---
$php_mail = null;
foreach ( $patterns as $p ) {
	if ( 'php_mail' === $p['id'] ) {
		$php_mail = $p['regex'];
	}
}
wpsms_assert( is_string( $php_mail ), 'php_mail pattern exists' );
wpsms_assert( 0 === preg_match( $php_mail, 'wp_mail( $to, $subject, $body );' ), 'php_mail regex ignores wp_mail()' );
wpsms_assert( 1 === preg_match( $php_mail, 'mail($to, $subject, $body);' ), 'php_mail regex matches mail()' );

// --- DocuSign lure ---
$docu = null;
foreach ( $patterns as $p ) {
	if ( 'phish_wording' === $p['id'] ) {
		$docu = $p['regex'];
	}
}
$sample = 'Completed: Complete with DocuSign: Draft Authorization Request';
wpsms_assert( 1 === preg_match( $docu, $sample ), 'detects DocuSign lure wording' );

// --- Risk scoring ---
class WPSMS_Fake_Paths {
	public $unusual = false;
	public $uploads = false;
	public function is_unusual_executable_location( $path ) { return $this->unusual; }
	public function is_uploads( $path ) { return $this->uploads; }
	public function is_cache( $path ) { return false; }
}

$paths = new WPSMS_Fake_Paths();
$r = WPSMS_Risk_Engine::score( array( 'wp_mail' ), '/wp-content/plugins/woocommerce/includes/mail.php', $paths, true );
wpsms_assert( in_array( $r['severity'], array( 'info', 'low' ), true ), 'wp_mail in WooCommerce stays low/info, got ' . $r['severity'] );

$paths->unusual = true;
$paths->uploads = true;
$r2 = WPSMS_Risk_Engine::score( array( 'wp_mail', 'user_input' ), '/wp-content/uploads/cache/x.php', $paths, false );
wpsms_assert( in_array( $r2['severity'], array( 'high', 'critical' ), true ), 'wp_mail + POST + uploads is high/critical, got ' . $r2['severity'] );

$r3 = WPSMS_Risk_Engine::score( array( 'wp_mail', 'base64_decode', 'eval' ), '/wp-content/uploads/mail.php', $paths, false );
wpsms_assert( 'critical' === $r3['severity'], 'mail + base64 + eval + uploads is critical, got ' . $r3['severity'] );

// --- Redactor ---
$red = WPSMS_Redactor::redact( 'password=supersecret smtp_pass: hunter2 api_key=abcd' );
wpsms_assert( false === strpos( $red, 'supersecret' ) && false === strpos( $red, 'hunter2' ), 'redacts passwords and api keys' );
wpsms_assert( WPSMS_Redactor::is_secret_option( 'woocommerce_email_smtp_password' ), 'secret option names detected' );

// --- Paths ---
$base = sys_get_temp_dir() . '/wpsms-path-test';
@mkdir( $base . '/wp-content/uploads', 0777, true );
file_put_contents( $base . '/wp-content/uploads/shell.php', '<?php mail();' );
$p = new WPSMS_Paths( $base . '/', $base . '/wp-content', $base . '/wp-content/plugins/wp-security-mail-scanner/' );
wpsms_assert( $p->is_uploads( $base . '/wp-content/uploads/shell.php' ), 'uploads path classified' );
wpsms_assert( $p->is_unusual_executable_location( $base . '/wp-content/uploads/shell.php' ), 'uploads PHP is unusual location' );
wpsms_assert( 'wp-content/uploads/shell.php' === $p->relative( $base . '/wp-content/uploads/shell.php' ), 'relative path computed' );

$safe = $p->resolve_safe( '../etc/passwd' );
wpsms_assert( '' === $safe || false === strpos( $safe, '/etc/passwd' ), 'path traversal rejected' );

wpsms_assert( $p->is_protected_from_delete( $base . '/wp-admin/index.php' ) || true === $p->is_core_dir( $base . '/wp-admin/index.php' ), 'core dir helper works' );

// --- Confirmation gates present in source ---
$actions = file_get_contents( $root . '/includes/class-file-actions.php' );
wpsms_assert( false !== strpos( $actions, "if ( 'confirm' !== \$confirm )" ), 'quarantine requires confirm' );
wpsms_assert( false !== strpos( $actions, "if ( 'confirm' !== \$confirm1 || 'delete' !== \$confirm2 )" ), 'delete requires two confirmations' );

$admin = file_get_contents( $root . '/admin/class-admin.php' );
wpsms_assert( substr_count( $admin, 'WPSMS_Helpers::require_ajax_access' ) >= 10, 'AJAX handlers check nonce+capability' );
wpsms_assert( false !== strpos( $admin, 'add_management_page' ), 'Tools menu registered' );
wpsms_assert( false !== strpos( $admin, 'WPSMS_CAPABILITY' ), 'capability constant used' );

$helpers = file_get_contents( $root . '/includes/class-helpers.php' );
wpsms_assert( false !== strpos( $helpers, 'wp_verify_nonce' ), 'nonce verification present' );
wpsms_assert( false !== strpos( $helpers, 'current_user_can' ), 'capability check present' );

$scanner = file_get_contents( $root . '/includes/class-scanner.php' );
wpsms_assert( false !== strpos( $scanner, 'Never modifies site files' ) || false !== strpos( $scanner, 'Never modifies' ), 'scanner documents read-only scan' );
wpsms_assert( false !== strpos( $scanner, 'skip_and_continue' ), 'errors skip and continue instead of aborting' );
wpsms_assert( false === strpos( $scanner, "\$job['status']      = 'error'" ), 'scan job is not marked error on exception' );

$main = file_get_contents( $root . '/wp-security-mail-scanner.php' );
wpsms_assert( false !== strpos( $main, 'Plugin Name: WP Security & Mail Scanner' ), 'plugin header present' );

$db = file_get_contents( $root . '/includes/class-database-scanner.php' );
wpsms_assert( false !== strpos( $db, '$wpdb->prepare' ), 'database scanner uses prepared SQL' );

echo "\n";
if ( $failures ) {
	echo "FAILED {$failures} assertion(s)\n";
	exit( 1 );
}
echo "All standalone checks passed.\n";
exit( 0 );
