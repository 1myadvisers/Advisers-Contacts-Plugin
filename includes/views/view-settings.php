<?php
/**
 * View: Settings.
 *
 * @package Advisers_Contacts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Wrapped in a closure so template variables never leak into the global scope.
( static function () {

	$country_code = get_option( 'advc_default_country_code', '91' );
	$per_page     = get_option( 'advc_per_page', 20 );
	$form_title   = get_option( 'advc_form_title', ADVC_Helpers::default_text( 'title' ) );
	$form_intro   = get_option( 'advc_form_intro', ADVC_Helpers::default_text( 'intro' ) );
	$success      = get_option( 'advc_success_message', ADVC_Helpers::default_text( 'success' ) );
	$theme        = get_option( 'advc_theme', 'blue' );
	$service_field = ADVC_Fields::get_field( 'service_interested' );
	$services      = $service_field ? implode( "\n", ADVC_Fields::get_options( $service_field ) ) : '';
	$share_on     = get_option( 'advc_share_enabled', '1' );
	$notify_on    = get_option( 'advc_notify_enabled', '1' );
	$notify_email = get_option( 'advc_notify_email', get_option( 'admin_email' ) );
	$reply_on     = get_option( 'advc_autoreply_enabled', '' );
	$reply_sub    = get_option( 'advc_autoreply_subject', ADVC_Helpers::default_text( 'autoreply_subject' ) );
	$reply_body   = get_option( 'advc_autoreply_body', ADVC_Helpers::default_text( 'autoreply_body' ) );
	$notice       = isset( $_GET['advc_notice'] ) ? sanitize_key( wp_unslash( $_GET['advc_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	?>
<div class="wrap advc-wrap">
	<h1><?php esc_html_e( 'Advisers Contacts Settings', 'advisers-contacts' ); ?></h1>

	<?php if ( $notice && 'settings_saved' === $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'advisers-contacts' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="advc_save_general_settings" />
		<?php wp_nonce_field( 'advc_settings', '_advc_nonce' ); ?>

		<h2><?php esc_html_e( 'Form Appearance', 'advisers-contacts' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="form_title"><?php esc_html_e( 'Form Name', 'advisers-contacts' ); ?></label></th>
				<td>
					<input type="text" id="form_title" name="form_title" class="large-text" value="<?php echo esc_attr( $form_title ); ?>" />
					<p class="description"><?php esc_html_e( 'Shown as the heading above the form and used in your new-lead alert emails. Leave empty to hide the heading.', 'advisers-contacts' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="form_intro"><?php esc_html_e( 'Form Description', 'advisers-contacts' ); ?></label></th>
				<td>
					<textarea id="form_intro" name="form_intro" class="large-text" rows="3"><?php echo esc_textarea( $form_intro ); ?></textarea>
					<p class="description"><?php esc_html_e( 'A short description shown under the form name. Leave empty to hide it.', 'advisers-contacts' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="theme"><?php esc_html_e( 'Color Theme', 'advisers-contacts' ); ?></label></th>
				<td>
					<select id="theme" name="theme">
						<?php foreach ( ADVC_Helpers::themes() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $theme, $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'You can also override it per page: [advisers_contact_form theme="emerald" title="Get a free quote"].', 'advisers-contacts' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Share Buttons', 'advisers-contacts' ); ?></th>
				<td><label><input type="checkbox" name="share_enabled" value="1" <?php checked( '1', $share_on ); ?> /> <?php esc_html_e( 'Show "Share this form" buttons (WhatsApp, Email, Facebook, LinkedIn, X, Copy link) below the form', 'advisers-contacts' ); ?></label></td>
			</tr>
			<tr>
				<th><label for="success_message"><?php esc_html_e( 'Thank-you Message', 'advisers-contacts' ); ?></label></th>
				<td><textarea id="success_message" name="success_message" class="large-text" rows="2"><?php echo esc_textarea( $success ); ?></textarea></td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Services', 'advisers-contacts' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="services"><?php esc_html_e( 'Services You Offer', 'advisers-contacts' ); ?></label></th>
				<td>
					<?php if ( $service_field ) : ?>
						<textarea id="services" name="services" class="large-text" rows="6" placeholder="<?php esc_attr_e( 'One service per line, for example: Website Design', 'advisers-contacts' ); ?>"><?php echo esc_textarea( $services ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Visitors pick one of these in the "Service Interested In" dropdown. Add, edit or remove lines at any time. While the list is empty the dropdown is not shown on the form.', 'advisers-contacts' ); ?></p>
					<?php else : ?>
						<p class="description"><?php esc_html_e( 'The "Service Interested In" field is not on your form. Add it back from Field Builder → Available Fields to manage services here.', 'advisers-contacts' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Email Notifications', 'advisers-contacts' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'New Lead Alert', 'advisers-contacts' ); ?></th>
				<td><label><input type="checkbox" name="notify_enabled" value="1" <?php checked( '1', $notify_on ); ?> /> <?php esc_html_e( 'Email me every new submission', 'advisers-contacts' ); ?></label></td>
			</tr>
			<tr>
				<th><label for="notify_email"><?php esc_html_e( 'Send Alerts To', 'advisers-contacts' ); ?></label></th>
				<td><input type="email" id="notify_email" name="notify_email" class="regular-text" value="<?php echo esc_attr( $notify_email ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Auto-reply', 'advisers-contacts' ); ?></th>
				<td><label><input type="checkbox" name="autoreply_enabled" value="1" <?php checked( '1', $reply_on ); ?> /> <?php esc_html_e( 'Send the visitor a confirmation email', 'advisers-contacts' ); ?></label></td>
			</tr>
			<tr>
				<th><label for="autoreply_subject"><?php esc_html_e( 'Auto-reply Subject', 'advisers-contacts' ); ?></label></th>
				<td><input type="text" id="autoreply_subject" name="autoreply_subject" class="large-text" value="<?php echo esc_attr( $reply_sub ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="autoreply_body"><?php esc_html_e( 'Auto-reply Message', 'advisers-contacts' ); ?></label></th>
				<td>
					<textarea id="autoreply_body" name="autoreply_body" class="large-text" rows="6"><?php echo esc_textarea( $reply_body ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Placeholders: {name} and {site_name}.', 'advisers-contacts' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'General', 'advisers-contacts' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="default_country_code"><?php esc_html_e( 'Default WhatsApp Country Code', 'advisers-contacts' ); ?></label></th>
				<td>
					<input type="text" id="default_country_code" name="default_country_code" class="small-text" value="<?php echo esc_attr( $country_code ); ?>" maxlength="4" />
					<p class="description"><?php esc_html_e( 'Prepended to mobile numbers that appear to have no country code when generating the WhatsApp link (e.g. 91 for India).', 'advisers-contacts' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="per_page"><?php esc_html_e( 'Submissions Per Page', 'advisers-contacts' ); ?></label></th>
				<td><input type="number" id="per_page" name="per_page" class="small-text" min="5" max="200" value="<?php echo esc_attr( $per_page ); ?>" /></td>
			</tr>
		</table>
		<?php submit_button( __( 'Save Settings', 'advisers-contacts' ) ); ?>
	</form>
</div>
	<?php
} )();
