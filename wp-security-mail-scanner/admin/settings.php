<?php
/**
 * Scanner settings.
 *
 * @package WPSMS
 *
 * @var array $settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php if ( ! empty( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
	<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'wp-security-mail-scanner' ); ?></p></div>
<?php endif; ?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<?php wp_nonce_field( 'wpsms_save_settings' ); ?>
	<input type="hidden" name="action" value="wpsms_save_settings" />
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="batch_size"><?php esc_html_e( 'File batch size', 'wp-security-mail-scanner' ); ?></label></th>
			<td><input name="batch_size" id="batch_size" type="number" class="small-text" value="<?php echo esc_attr( (string) $settings['batch_size'] ); ?>" min="5" max="200" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="inventory_batch"><?php esc_html_e( 'Inventory batch size', 'wp-security-mail-scanner' ); ?></label></th>
			<td><input name="inventory_batch" id="inventory_batch" type="number" class="small-text" value="<?php echo esc_attr( (string) $settings['inventory_batch'] ); ?>" min="50" max="2000" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="max_file_bytes"><?php esc_html_e( 'Max file size (bytes)', 'wp-security-mail-scanner' ); ?></label></th>
			<td><input name="max_file_bytes" id="max_file_bytes" type="number" class="regular-text" value="<?php echo esc_attr( (string) $settings['max_file_bytes'] ); ?>" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="exclude_paths"><?php esc_html_e( 'Exclude directory names', 'wp-security-mail-scanner' ); ?></label></th>
			<td><textarea name="exclude_paths" id="exclude_paths" class="large-text" rows="5"><?php echo esc_textarea( $settings['exclude_paths'] ); ?></textarea></td>
		</tr>
		<tr>
			<th scope="row"><label for="known_admin_logins"><?php esc_html_e( 'Known administrator logins/emails', 'wp-security-mail-scanner' ); ?></label></th>
			<td>
				<textarea name="known_admin_logins" id="known_admin_logins" class="large-text" rows="4"><?php echo esc_textarea( $settings['known_admin_logins'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'One per line. Other administrators are flagged as unknown (not deleted).', 'wp-security-mail-scanner' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Quick scan', 'wp-security-mail-scanner' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="quick_skip_core" value="1" <?php checked( ! empty( $settings['quick_skip_core'] ) ); ?> />
					<?php esc_html_e( 'Skip wp-admin / wp-includes during quick scans', 'wp-security-mail-scanner' ); ?>
				</label>
			</td>
		</tr>
	</table>
	<?php submit_button( __( 'Save settings', 'wp-security-mail-scanner' ) ); ?>
</form>
