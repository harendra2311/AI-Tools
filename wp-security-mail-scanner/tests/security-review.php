<?php
/**
 * Additional static security review of plugin sources.
 *
 * @package WPSMS
 */

$root  = dirname( __DIR__ );
$fail  = 0;
$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root ) );

function fail( $msg ) {
	global $fail;
	++$fail;
	echo "FAIL {$msg}\n";
}

$ajax_actions = 0;
$nopriv       = 0;
foreach ( $files as $file ) {
	if ( ! $file->isFile() ) {
		continue;
	}
	$path = $file->getPathname();
	if ( ! preg_match( '/\.php$/', $path ) ) {
		continue;
	}
	if ( false !== strpos( $path, '/tests/' ) ) {
		continue;
	}
	$src = file_get_contents( $path );
	if ( preg_match( '/wp_ajax_nopriv_/', $src ) ) {
		++$nopriv;
		fail( "unauthenticated AJAX in {$path}" );
	}
	if ( preg_match( "/add_action\\(\\s*'wp_ajax_'/", $src ) || preg_match( '/wp_ajax_wpsms_/', $src ) ) {
		++$ajax_actions;
	}
}

if ( $nopriv === 0 ) {
	echo "OK  no wp_ajax_nopriv hooks\n";
}
if ( $ajax_actions >= 1 ) {
	echo "OK  admin AJAX actions registered ({$ajax_actions})\n";
} else {
	fail( 'no admin AJAX actions found' );
}

$boot = file_get_contents( $root . '/wp-security-mail-scanner.php' );
if ( false !== strpos( $boot, "if ( ! defined( 'ABSPATH' ) )" ) ) {
	echo "OK  main file guarded by ABSPATH\n";
} else {
	fail( 'main file missing ABSPATH guard' );
}

$un = file_get_contents( $root . '/uninstall.php' );
if ( false !== strpos( $un, 'WP_UNINSTALL_PLUGIN' ) ) {
	echo "OK  uninstall guarded\n";
} else {
	fail( 'uninstall unguarded' );
}

echo "\n";
exit( $fail ? 1 : 0 );
