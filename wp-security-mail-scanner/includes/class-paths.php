<?php
/**
 * Path classification and sanitization.
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filesystem path helpers.
 */
class WPSMS_Paths {

	/**
	 * Absolute WordPress root with trailing slash.
	 *
	 * @var string
	 */
	public $abspath;

	/**
	 * wp-content path.
	 *
	 * @var string
	 */
	public $content;

	/**
	 * This plugin directory.
	 *
	 * @var string
	 */
	public $self_dir;

	/**
	 * Constructor.
	 *
	 * @param string $abspath  ABSPATH.
	 * @param string $content  WP_CONTENT_DIR.
	 * @param string $self_dir Plugin dir.
	 */
	public function __construct( $abspath = '', $content = '', $self_dir = '' ) {
		$this->abspath  = $this->normalize( $abspath ? $abspath : ( defined( 'ABSPATH' ) ? ABSPATH : '' ) );
		$this->content  = $this->normalize( $content ? $content : ( defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : $this->abspath . 'wp-content' ) );
		$this->self_dir = $this->normalize( $self_dir ? $self_dir : ( defined( 'WPSMS_PLUGIN_DIR' ) ? WPSMS_PLUGIN_DIR : '' ) );
	}

	/**
	 * Normalize slashes and trailing slash for directories.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	public function normalize( $path ) {
		$path = str_replace( '\\', '/', (string) $path );
		return $path;
	}

	/**
	 * Real path if possible.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	public function real( $path ) {
		$real = realpath( $path );
		return $real ? $this->normalize( $real ) : $this->normalize( $path );
	}

	/**
	 * Relative to WordPress root.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	public function relative( $path ) {
		$full = $this->real( $path );
		$root = rtrim( $this->real( $this->abspath ), '/' ) . '/';
		if ( strpos( $full, $root ) === 0 ) {
			return ltrim( substr( $full, strlen( $root ) ), '/' );
		}
		$content = rtrim( $this->real( $this->content ), '/' ) . '/';
		if ( strpos( $full, $content ) === 0 ) {
			return 'wp-content/' . ltrim( substr( $full, strlen( $content ) ), '/' );
		}
		return $full;
	}

	/**
	 * Whether path is inside WordPress root or content dir.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_inside_site( $path ) {
		$full = $this->real( $path );
		$root = rtrim( $this->real( $this->abspath ), '/' ) . '/';
		if ( strpos( $full . '/', $root ) === 0 || strpos( $full, rtrim( $root, '/' ) ) === 0 ) {
			return true;
		}
		$content = rtrim( $this->real( $this->content ), '/' ) . '/';
		return ( strpos( $full . '/', $content ) === 0 );
	}

	/**
	 * Resolve a relative or absolute path safely. Returns empty string if invalid.
	 *
	 * @param string $path Path from user.
	 * @return string
	 */
	public function resolve_safe( $path ) {
		$path = $this->normalize( $path );
		if ( '' === $path || false !== strpos( $path, "\0" ) ) {
			return '';
		}
		if ( $this->is_absolute( $path ) ) {
			$candidate = $path;
		} else {
			$candidate = rtrim( $this->abspath, '/' ) . '/' . ltrim( $path, '/' );
		}
		if ( ! $this->is_inside_site( $candidate ) ) {
			return '';
		}
		$real = realpath( $candidate );
		if ( ! $real || ! $this->is_inside_site( $real ) ) {
			return '';
		}
		$self = rtrim( $this->real( $this->self_dir ), '/' ) . '/';
		$normalized_real = $this->normalize( $real );
		if ( $self && ( strpos( $normalized_real . '/', $self ) === 0 ) ) {
			// Allow viewing our own files but never deleting/quarantining scanner files via actions.
		}
		return $normalized_real;
	}

	/**
	 * Absolute path?
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_absolute( $path ) {
		return (bool) preg_match( '#^(/|[a-zA-Z]:/)#', $path );
	}

	/**
	 * Is this plugin's own directory.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_self( $path ) {
		$self = rtrim( $this->real( $this->self_dir ), '/' ) . '/';
		$full = rtrim( $this->real( $path ), '/' ) . '/';
		return $self && strpos( $full, $self ) === 0;
	}

	/**
	 * Uploads.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_uploads( $path ) {
		$rel = $this->relative( $path );
		return ( 0 === strpos( $rel, 'wp-content/uploads/' ) || 'wp-content/uploads' === $rel );
	}

	/**
	 * Cache-like directory.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_cache( $path ) {
		$rel = strtolower( $this->relative( $path ) );
		$needles = array( '/cache/', '/tmp/', '/temp/', 'w3tc', 'litespeed', 'et-cache', 'wp-rocket', 'supercache', '/upgrade/' );
		foreach ( $needles as $n ) {
			if ( false !== strpos( $rel, $n ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Core wp-admin or wp-includes.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_core_dir( $path ) {
		$rel = $this->relative( $path );
		return ( 0 === strpos( $rel, 'wp-admin/' ) || 0 === strpos( $rel, 'wp-includes/' ) );
	}

	/**
	 * Root-level PHP that is not a known core file.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_unexpected_root_php( $path ) {
		$rel = $this->relative( $path );
		if ( false !== strpos( $rel, '/' ) ) {
			return false;
		}
		if ( ! preg_match( '/\.(php|phtml|phar)$/i', $rel ) ) {
			return false;
		}
		$known = array(
			'index.php',
			'wp-activate.php',
			'wp-blog-header.php',
			'wp-comments-post.php',
			'wp-config.php',
			'wp-config-sample.php',
			'wp-cron.php',
			'wp-links-opml.php',
			'wp-load.php',
			'wp-login.php',
			'wp-mail.php',
			'wp-settings.php',
			'wp-signup.php',
			'wp-trackback.php',
			'xmlrpc.php',
		);
		return ! in_array( strtolower( $rel ), $known, true );
	}

	/**
	 * wp-content/plugins.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_plugin_dir( $path ) {
		$rel = $this->relative( $path );
		return ( 0 === strpos( $rel, 'wp-content/plugins/' ) );
	}

	/**
	 * Themes.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_theme_dir( $path ) {
		$rel = $this->relative( $path );
		return ( 0 === strpos( $rel, 'wp-content/themes/' ) );
	}

	/**
	 * MU plugins.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_mu_plugin( $path ) {
		$rel = $this->relative( $path );
		return ( 0 === strpos( $rel, 'wp-content/mu-plugins/' ) );
	}

	/**
	 * Unusual location for executable PHP.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_unusual_executable_location( $path ) {
		return $this->is_uploads( $path ) || $this->is_cache( $path ) || $this->is_unexpected_root_php( $path );
	}

	/**
	 * Core file that must never be deleted by this plugin.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_protected_from_delete( $path ) {
		if ( $this->is_core_dir( $path ) ) {
			return true;
		}
		$rel = $this->relative( $path );
		if ( false === strpos( $rel, '/' ) && preg_match( '/^wp-/', $rel ) ) {
			return true;
		}
		return in_array( $rel, array( 'index.php', 'xmlrpc.php', 'wp-config.php' ), true );
	}

	/**
	 * Scan roots.
	 *
	 * @param bool $quick Quick scan.
	 * @return array
	 */
	public function scan_roots( $quick = false ) {
		$roots = array(
			rtrim( $this->abspath, '/' ),
			$this->content,
		);
		if ( $quick ) {
			$roots = array(
				$this->content . '/plugins',
				$this->content . '/themes',
				$this->content . '/uploads',
				$this->content . '/mu-plugins',
				rtrim( $this->abspath, '/' ),
			);
		}
		$out = array();
		foreach ( $roots as $root ) {
			if ( $root && is_dir( $root ) ) {
				$out[] = $this->normalize( $root );
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Default skip directory names.
	 *
	 * @return array
	 */
	public function skip_dir_names() {
		return array( '.git', '.svn', 'node_modules', 'quarantine' );
	}

	/**
	 * Extensions to scan.
	 *
	 * @param bool $quick Quick.
	 * @return array
	 */
	public function scan_extensions( $quick = false ) {
		if ( $quick ) {
			return array( 'php', 'phtml', 'phar', 'inc', 'htaccess' );
		}
		return array( 'php', 'phtml', 'phar', 'inc', 'js', 'html', 'htm', 'htaccess' );
	}
}
