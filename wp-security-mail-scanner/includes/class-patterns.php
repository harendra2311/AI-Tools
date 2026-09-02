<?php
/**
 * Detection patterns. Matches are indicators, not automatic malware verdicts.
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pattern catalog.
 */
class WPSMS_Patterns {

	/**
	 * File content patterns.
	 *
	 * Each item: id, regex, indicator, category, title, why, risk, action, base_weight
	 *
	 * @return array
	 */
	public static function file_patterns() {
		return array(
			array(
				'id'          => 'eval',
				'regex'       => '/\beval\s*\(/i',
				'indicator'   => 'eval',
				'category'    => 'file',
				'title'       => 'eval() usage',
				'why'         => 'eval() executes a string as PHP. Attackers often use it to hide payloads, but some legitimate libraries still call it.',
				'risk'        => 'Dynamic code execution is a high-risk indicator when combined with obfuscation or an unusual file location.',
				'action'      => 'Inspect the evaluated argument. If it is a hard-coded constant in a known library, it may be legitimate. If it decodes hidden data, treat as a high-risk indicator.',
				'base_weight' => 25,
			),
			array(
				'id'          => 'assert_code',
				'regex'       => '/\bassert\s*\(\s*[\'"]/i',
				'indicator'   => 'assert',
				'category'    => 'file',
				'title'       => 'assert() with a string argument',
				'why'         => 'Older PHP versions can execute assert() strings as code, which is a common malware trick.',
				'risk'        => 'String-based assert is rarely needed in modern WordPress code.',
				'action'      => 'Review the call. Prefer replacing or removing it if the file is unexpected.',
				'base_weight' => 28,
			),
			array(
				'id'          => 'base64_decode',
				'regex'       => '/\bbase64_decode\s*\(/i',
				'indicator'   => 'base64_decode',
				'category'    => 'file',
				'title'       => 'base64_decode() usage',
				'why'         => 'Decoding is common in legitimate plugins (license checks, email attachments) and in malware that hides payloads.',
				'risk'        => 'Alone this is usually informational. Combined with eval, mail, or an uploads path it becomes a high-risk indicator.',
				'action'      => 'Check whether the decoded data is a known format (images, JSON) or obfuscated PHP.',
				'base_weight' => 8,
			),
			array(
				'id'          => 'gzinflate',
				'regex'       => '/\b(gzinflate|gzuncompress|gzdecode)\s*\(/i',
				'indicator'   => 'compression_decode',
				'category'    => 'file',
				'title'       => 'Compressed-string decoding',
				'why'         => 'gzinflate/gzuncompress are frequently chained with base64_decode in obfuscated PHP malware.',
				'risk'        => 'Suspicious when the input is a long encoded string rather than a real compressed file.',
				'action'      => 'Inspect the input. Long concatenated strings are a high-risk indicator.',
				'base_weight' => 18,
			),
			array(
				'id'          => 'str_rot13',
				'regex'       => '/\bstr_rot13\s*\(/i',
				'indicator'   => 'str_rot13',
				'category'    => 'file',
				'title'       => 'str_rot13() usage',
				'why'         => 'ROT13 is rarely needed in production WordPress code and is often used to hide strings.',
				'risk'        => 'Medium on its own; higher with other obfuscation.',
				'action'      => 'Determine why text is being rotated. Unexpected files should be investigated.',
				'base_weight' => 16,
			),
			array(
				'id'          => 'preg_replace_e',
				'regex'       => '/preg_replace\s*\(\s*[\'"][^\'"]*\/[^\'"]*e[\'"]/i',
				'indicator'   => 'preg_replace_e',
				'category'    => 'file',
				'title'       => 'preg_replace() with /e modifier',
				'why'         => 'The deprecated /e modifier evaluates the replacement as PHP and is a classic remote-code pattern.',
				'risk'        => 'High-risk indicator. Almost never legitimate in modern code.',
				'action'      => 'Treat as requiring investigation. Check whether this file belongs to a known plugin version.',
				'base_weight' => 40,
			),
			array(
				'id'          => 'create_function',
				'regex'       => '/\bcreate_function\s*\(/i',
				'indicator'   => 'create_function',
				'category'    => 'file',
				'title'       => 'create_function() usage',
				'why'         => 'create_function() is deprecated and can execute dynamic PHP. Malware still uses it.',
				'risk'        => 'Suspicious in unknown files; some old plugins still contain it.',
				'action'      => 'Confirm the plugin is real and updated. Unknown files using this should be reviewed.',
				'base_weight' => 20,
			),
			array(
				'id'          => 'shell_exec',
				'regex'       => '/\b(shell_exec|exec|system|passthru|proc_open|popen)\s*\(/i',
				'indicator'   => 'shell',
				'category'    => 'file',
				'title'       => 'Shell execution function',
				'why'         => 'These functions run system commands. Backup, migration, and developer plugins may use them legitimately.',
				'risk'        => 'High-risk in uploads/cache/unknown files. Lower if inside a well-known plugin that documents the feature.',
				'action'      => 'Verify the plugin identity and whether administrators expect command execution.',
				'base_weight' => 30,
			),
			array(
				'id'          => 'wp_mail',
				'regex'       => '/\bwp_mail\s*\(/i',
				'indicator'   => 'wp_mail',
				'category'    => 'mail',
				'title'       => 'wp_mail() call',
				'why'         => 'wp_mail() is the standard WordPress mail API. Contact forms, WooCommerce, and SMTP plugins use it legitimately.',
				'risk'        => 'Informational by itself. Risk rises with user input, hard-coded recipients, obfuscation, or unusual file location.',
				'action'      => 'Identify the plugin/theme. Confirm recipients and subjects are expected.',
				'base_weight' => 4,
			),
			array(
				'id'          => 'php_mail',
				'regex'       => '/(?<!wp_|WP_)(?<![A-Za-z0-9_])mail\s*\(/i',
				'indicator'   => 'php_mail',
				'category'    => 'mail',
				'title'       => 'PHP mail() call',
				'why'         => 'Direct mail() bypasses some WordPress mail filters. It can be legitimate (SMTP libraries) or used by spam scripts.',
				'risk'        => 'Low/info in known mail libraries. Higher in uploads, cache, or unknown PHP files, especially with $_POST.',
				'action'      => 'Check location and whether user input is passed into the message.',
				'base_weight' => 10,
			),
			array(
				'id'          => 'phpmailer',
				'regex'       => '/\bPHPMailer\b/i',
				'indicator'   => 'phpmailer',
				'category'    => 'mail',
				'title'       => 'PHPMailer usage',
				'why'         => 'PHPMailer is bundled with WordPress and many SMTP plugins. Unauthorized copies in uploads are suspicious.',
				'risk'        => 'Info in core/SMTP plugins. High-risk indicator if found under uploads or paired with hard-coded recipients.',
				'action'      => 'Confirm this is WordPress core or a known mail plugin—not a dropped copy in uploads.',
				'base_weight' => 6,
			),
			array(
				'id'          => 'phpmailer_init',
				'regex'       => '/phpmailer_init/i',
				'indicator'   => 'phpmailer_init',
				'category'    => 'mail',
				'title'       => 'phpmailer_init hook',
				'why'         => 'This hook configures SMTP. Legitimate SMTP plugins use it; malware can also hijack outgoing mail.',
				'risk'        => 'Review the callback. Unexpected hosts or credentials in unknown files require investigation.',
				'action'      => 'Compare the SMTP host with the host you actually use.',
				'base_weight' => 8,
			),
			array(
				'id'          => 'wp_mail_filters',
				'regex'       => '/\b(wp_mail_from|wp_mail_from_name|wp_mail_content_type|wp_mail_failed)\b/i',
				'indicator'   => 'wp_mail_filter',
				'category'    => 'mail',
				'title'       => 'wp_mail filter/action',
				'why'         => 'These hooks change From headers or content type. SMTP and form plugins use them; spam droppers can spoof From/Reply-To.',
				'risk'        => 'Usually legitimate. Inspect unknown files that set From or Reply-To to unrelated domains.',
				'action'      => 'Note the From address and compare it with your domain.',
				'base_weight' => 6,
			),
			array(
				'id'          => 'mail_headers',
				'regex'       => '/\b(Reply-To|Return-Path|X-Mailer)\b/i',
				'indicator'   => 'mail_headers',
				'category'    => 'mail',
				'title'       => 'Email header strings',
				'why'         => 'Header strings appear in legitimate mailers and in phishing scripts that spoof From/Reply-To.',
				'risk'        => 'Informational unless combined with mail functions in an unusual location.',
				'action'      => 'Check which addresses are being set.',
				'base_weight' => 3,
			),
			array(
				'id'          => 'file_put_contents',
				'regex'       => '/\bfile_put_contents\s*\(/i',
				'indicator'   => 'file_put_contents',
				'category'    => 'file',
				'title'       => 'file_put_contents() usage',
				'why'         => 'Writing files is normal for caches, logs, and uploads. Malware uses it to drop webshells.',
				'risk'        => 'Low by itself. Higher with user input, mail functions, or unexpected directories.',
				'action'      => 'See what path is written. Writes under uploads/*.php are a high-risk indicator.',
				'base_weight' => 5,
			),
			array(
				'id'          => 'move_uploaded_file',
				'regex'       => '/\bmove_uploaded_file\s*\(/i',
				'indicator'   => 'move_uploaded_file',
				'category'    => 'file',
				'title'       => 'move_uploaded_file() usage',
				'why'         => 'Required for media and form uploads. Attackers also use it to plant executable files.',
				'risk'        => 'Info in known plugins. Investigate if the destination allows .php and the file is unknown.',
				'action'      => 'Confirm destination extension checks.',
				'base_weight' => 6,
			),
			array(
				'id'          => 'superglobals',
				'regex'       => '/\$_(GET|POST|REQUEST|COOKIE)\s*\[/i',
				'indicator'   => 'user_input',
				'category'    => 'file',
				'title'       => 'Direct use of request superglobals',
				'why'         => 'Reading $_POST/$_GET is normal. Combined with mail or eval it can indicate an unauthorized mailer or webshell.',
				'risk'        => 'Not malware by itself. Raises score when paired with mail, eval, or shell functions.',
				'action'      => 'Trace whether input is sanitized and where it is sent.',
				'base_weight' => 2,
			),
			array(
				'id'          => 'curl_exec',
				'regex'       => '/\b(curl_exec|file_get_contents\s*\(\s*[\'"]https?:|WP_Http|wp_remote_(get|post))\b/i',
				'indicator'   => 'http_out',
				'category'    => 'file',
				'title'       => 'Outbound HTTP request',
				'why'         => 'Plugins routinely call external APIs. Malware uses HTTP to load remote code or leak data.',
				'risk'        => 'Info unless combined with eval of the response or email content posting.',
				'action'      => 'Note the destination host from nearby strings.',
				'base_weight' => 4,
			),
			array(
				'id'          => 'include_remote',
				'regex'       => '/\b(include|require|include_once|require_once)\s*\(\s*[\'"]https?:/i',
				'indicator'   => 'remote_include',
				'category'    => 'file',
				'title'       => 'Remote include/require',
				'why'         => 'Loading PHP from a remote URL is almost never legitimate and is a critical indicator.',
				'risk'        => 'Critical high-risk indicator. Requires investigation.',
				'action'      => 'Do not execute the site in production until this file is reviewed. Take a backup first.',
				'base_weight' => 50,
			),
			array(
				'id'          => 'hidden_iframe',
				'regex'       => '/<iframe[^>]*(display\s*:\s*none|width\s*=\s*[\'"]0|height\s*=\s*[\'"]0)/i',
				'indicator'   => 'hidden_iframe',
				'category'    => 'file',
				'title'       => 'Hidden iframe',
				'why'         => 'Hidden iframes are a common malware/spam injection in themes and posts.',
				'risk'        => 'Suspicious. Confirm it is not a documented third-party widget.',
				'action'      => 'Inspect the iframe src. Unexpected domains should be treated as a high-risk indicator.',
				'base_weight' => 22,
			),
			array(
				'id'          => 'document_write',
				'regex'       => '/document\.write\s*\(/i',
				'indicator'   => 'document_write',
				'category'    => 'file',
				'title'       => 'document.write()',
				'why'         => 'Often used by injected scripts; also used by some older legitimate widgets.',
				'risk'        => 'Medium if the surrounding script is obfuscated or loaded from an unknown host.',
				'action'      => 'Review the written markup and script source.',
				'base_weight' => 10,
			),
			array(
				'id'          => 'fromcharcode',
				'regex'       => '/fromCharCode\s*\(/i',
				'indicator'   => 'fromcharcode',
				'category'    => 'file',
				'title'       => 'String.fromCharCode obfuscation',
				'why'         => 'Character-code packing is a frequent JavaScript obfuscation technique in injected spam.',
				'risk'        => 'Suspicious in theme/footer files and unexpected JS.',
				'action'      => 'Decode enough of the string to see the destination URL.',
				'base_weight' => 18,
			),
			array(
				'id'          => 'js_unescape',
				'regex'       => '/\bunescape\s*\(\s*[\'"][%\\x]/i',
				'indicator'   => 'js_obfuscation',
				'category'    => 'file',
				'title'       => 'Obfuscated JavaScript (unescape)',
				'why'         => 'Packed/escaped JS is commonly injected into headers and footers.',
				'risk'        => 'Suspicious. Requires investigation.',
				'action'      => 'Compare with a clean copy of the theme/plugin.',
				'base_weight' => 20,
			),
			array(
				'id'          => 'suspicious_redirect',
				'regex'       => '/\b(header\s*\(\s*[\'"]Location:|wp_redirect\s*\(\s*[\'"]https?:\/\/)/i',
				'indicator'   => 'redirect',
				'category'    => 'file',
				'title'       => 'Redirect to an absolute URL',
				'why'         => 'Redirects can be legitimate (SSO, payment). Unexpected off-site redirects in unknown files are suspicious.',
				'risk'        => 'Medium in unknown files; info in known plugins.',
				'action'      => 'Confirm the destination domain is expected.',
				'base_weight' => 8,
			),
			array(
				'id'          => 'docusign',
				'regex'       => '/docusign|docu\s*sign/i',
				'indicator'   => 'docusign',
				'category'    => 'mail',
				'title'       => 'DocuSign-related string',
				'why'         => 'Fake “Completed with DocuSign” messages are a common phishing lure. Legitimate DocuSign plugins also contain this word.',
				'risk'        => 'High-risk indicator if found with mail() in an unknown or uploads file. Likely legitimate inside an official DocuSign plugin.',
				'action'      => 'Confirm whether a real DocuSign plugin is installed. If not, investigate this file as a possible phishing mailer.',
				'base_weight' => 24,
			),
			array(
				'id'          => 'phish_wording',
				'regex'       => '/complete with docusign|draft authorization request|review document|sign(ing)? (the )?document|envelope id/i',
				'indicator'   => 'phish_wording',
				'category'    => 'mail',
				'title'       => 'Document-signature phishing wording',
				'why'         => 'Subject lines similar to “Completed: Complete with DocuSign: Draft Authorization Request” are widely used in spam campaigns.',
				'risk'        => 'High-risk indicator, especially with mail-sending functions.',
				'action'      => 'Treat as a primary lead in the mail incident investigation. Do not assume it is definitely malware until you inspect the full file.',
				'base_weight' => 36,
			),
			array(
				'id'          => 'hardcoded_mailto',
				'regex'       => '/[\'"]mailto:/i',
				'indicator'   => 'mailto',
				'category'    => 'mail',
				'title'       => 'mailto: link or address',
				'why'         => 'Often legitimate. Helpful when mapping possible recipients.',
				'risk'        => 'Informational.',
				'action'      => 'Record the address if it is unexpected.',
				'base_weight' => 1,
			),
			array(
				'id'          => 'smtp_host',
				'regex'       => '/\b(smtp\.(gmail|google|office365|sendgrid|mailgun|ses|postmark|sparkpost)|Host\s*:\s*[\'"][^\'"]+\.(com|net|org))/i',
				'indicator'   => 'smtp_host',
				'category'    => 'mail',
				'title'       => 'SMTP host reference',
				'why'         => 'SMTP hosts appear in legitimate mail plugins. Unexpected hosts in unknown files may indicate an unauthorized relay.',
				'risk'        => 'Info in SMTP plugins; higher in unknown PHP.',
				'action'      => 'Compare with the SMTP service you actually configured.',
				'base_weight' => 5,
			),
			array(
				'id'          => 'long_base64',
				'regex'       => '/[A-Za-z0-9+\/=]{200,}/',
				'indicator'   => 'long_encoded',
				'category'    => 'file',
				'title'       => 'Long encoded string',
				'why'         => 'Long base64-like blobs can be images, licenses, or hidden PHP. Context matters.',
				'risk'        => 'Informational unless adjacent to decode/eval.',
				'action'      => 'If next to base64_decode or gzinflate, treat as a high-risk indicator.',
				'base_weight' => 7,
			),
		);
	}

	/**
	 * Database search needles.
	 *
	 * @return array
	 */
	public static function database_needles() {
		return array(
			'base64_decode',
			'eval(',
			'<script',
			'<iframe',
			'document.write',
			'fromCharCode',
			'docusign',
			'Complete with DocuSign',
			'Draft Authorization Request',
			'gzinflate',
			'wp_mail(',
		);
	}

	/**
	 * Mail-related search needles for targeted mail scan.
	 *
	 * @return array
	 */
	public static function mail_needles() {
		return array(
			'wp_mail(',
			'phpmailer_init',
			'wp_mail_from',
			'wp_mail_from_name',
			'wp_mail_content_type',
			'wp_mail_failed',
			'PHPMailer',
			'Reply-To',
			'Return-Path',
			'docusign',
			'DocuSign',
			'authorization request',
			'signing',
			'envelope',
			'review document',
			'sendgrid',
			'mailgun',
			'smtp.',
		);
	}

	/**
	 * Suspicious username patterns.
	 *
	 * @return array
	 */
	public static function suspicious_usernames() {
		return array(
			'/^admin\d+$/i',
			'/hack|haxor|shell|c99|r57|b374k/i',
			'/^test\d*$/i',
			'/^user\d{3,}$/i',
		);
	}

	/**
	 * Common WP-Cron hooks considered built-in or very common.
	 *
	 * @return array
	 */
	public static function known_cron_hooks() {
		return array(
			'wp_version_check',
			'wp_update_plugins',
			'wp_update_themes',
			'wp_scheduled_delete',
			'wp_scheduled_auto_draft_delete',
			'wp_privacy_delete_old_export_files',
			'wp_site_health_scheduled_check',
			'recovery_mode_clean_expired_keys',
			'wp_https_detection',
			'wp_update_user_counts',
			'delete_expired_transients',
			'wp_delete_temp_updater_backups',
		);
	}
}
