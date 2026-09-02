<?php
/**
 * Findings table UI.
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wpsms-findings">
	<div class="wpsms-filters">
		<label>
			<?php esc_html_e( 'Filter', 'wp-security-mail-scanner' ); ?>
			<select id="wpsms-filter">
				<option value="all"><?php esc_html_e( 'All', 'wp-security-mail-scanner' ); ?></option>
				<option value="critical"><?php esc_html_e( 'Critical', 'wp-security-mail-scanner' ); ?></option>
				<option value="high"><?php esc_html_e( 'High', 'wp-security-mail-scanner' ); ?></option>
				<option value="medium"><?php esc_html_e( 'Medium', 'wp-security-mail-scanner' ); ?></option>
				<option value="low"><?php esc_html_e( 'Low', 'wp-security-mail-scanner' ); ?></option>
				<option value="mail"><?php esc_html_e( 'Mail', 'wp-security-mail-scanner' ); ?></option>
				<option value="files"><?php esc_html_e( 'Files', 'wp-security-mail-scanner' ); ?></option>
				<option value="plugins"><?php esc_html_e( 'Plugins', 'wp-security-mail-scanner' ); ?></option>
				<option value="themes"><?php esc_html_e( 'Themes', 'wp-security-mail-scanner' ); ?></option>
				<option value="cron"><?php esc_html_e( 'Cron', 'wp-security-mail-scanner' ); ?></option>
				<option value="database"><?php esc_html_e( 'Database', 'wp-security-mail-scanner' ); ?></option>
				<option value="users"><?php esc_html_e( 'Users', 'wp-security-mail-scanner' ); ?></option>
			</select>
		</label>
		<label>
			<?php esc_html_e( 'Search', 'wp-security-mail-scanner' ); ?>
			<input type="search" id="wpsms-search" placeholder="<?php esc_attr_e( 'Path, title, pattern…', 'wp-security-mail-scanner' ); ?>" />
		</label>
		<button type="button" class="button" id="wpsms-reload-findings"><?php esc_html_e( 'Reload', 'wp-security-mail-scanner' ); ?></button>
	</div>
	<table class="widefat striped" id="wpsms-findings-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Severity', 'wp-security-mail-scanner' ); ?></th>
				<th><?php esc_html_e( 'Type', 'wp-security-mail-scanner' ); ?></th>
				<th><?php esc_html_e( 'File/Location', 'wp-security-mail-scanner' ); ?></th>
				<th><?php esc_html_e( 'Line', 'wp-security-mail-scanner' ); ?></th>
				<th><?php esc_html_e( 'Description', 'wp-security-mail-scanner' ); ?></th>
				<th><?php esc_html_e( 'Action', 'wp-security-mail-scanner' ); ?></th>
			</tr>
		</thead>
		<tbody id="wpsms-findings-body">
			<tr><td colspan="6"><?php esc_html_e( 'Loading…', 'wp-security-mail-scanner' ); ?></td></tr>
		</tbody>
	</table>
</div>

<div id="wpsms-modal" class="wpsms-modal" hidden>
	<div class="wpsms-modal-inner">
		<button type="button" class="button-link wpsms-modal-close">&times;</button>
		<div id="wpsms-modal-body"></div>
	</div>
</div>
