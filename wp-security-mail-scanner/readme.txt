=== WP Security & Mail Scanner ===
Contributors: wpsms
Tags: security, malware, spam, email, scanner
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Investigation-first scanner for possible compromise and unauthorized spam or phishing email activity. No automatic file deletion.

== Description ==

WP Security & Mail Scanner is a professional investigation tool for WordPress administrators. It helps determine whether a site may be compromised and whether unauthorized code is sending spam, phishing, or unwanted messages (for example fake DocuSign emails).

This plugin is a **scanner first**. It does not automatically delete, modify, quarantine, or deactivate anything. Dangerous actions require administrator confirmation, a backup, and a second confirmation for deletion.

= Features =

* File malware pattern scanner with risk scoring (reduces false positives)
* Dedicated mail / spam investigation (wp_mail, PHP mail, PHPMailer, SMTP, DocuSign-like wording)
* Uploads directory executable detection
* User, plugin, theme, WP-Cron, and database option scanning
* WordPress core checksum integrity check
* Findings dashboard with filters and search
* JSON / CSV / HTML security reports (secrets redacted)
* Batched AJAX scanning with progress, resume, and stop

= Important =

Functions such as `wp_mail()`, `mail()`, `curl_exec()`, and `base64_decode()` are often legitimate. The scanner scores combinations of indicators instead of labeling every occurrence as malware.

== Installation ==

1. Upload the `wp-security-mail-scanner` folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen.
3. Go to Tools → Security & Mail Scanner.

== Changelog ==

= 1.0.1 =
* Skip failing/stuck files and continue the scan instead of stopping.
* Retry timed-out AJAX requests; smaller batches to reduce timeouts.

= 1.0.0 =
* Initial release.
