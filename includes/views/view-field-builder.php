<?php
/**
 * View: Field builder (drag-drop reorder, per-field settings, presets).
 *
 * @package Advisers_Contacts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Wrapped in a closure so template variables never leak into the global scope.
( static function () {

	$fields     = ADVC_Fields::get_fields();
	$restorable = ADVC_Fields::get_restorable_defaults();
	$notice     = isset( $_GET['advc_notice'] ) ? sanitize_key( wp_unslash( $_GET['advc_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$post_url   = admin_url( 'admin-post.php' );

	$notices = array(
		'fields_saved'   => __( 'Form saved.', 'advisers-contacts' ),
		'field_added'    => __( 'Field added.', 'advisers-contacts' ),
		'field_deleted'  => __( 'Field deleted.', 'advisers-contacts' ),
		'field_restored' => __( 'Field restored.', 'advisers-contacts' ),
		'preset_applied' => __( 'Lead Generation layout applied.', 'advisers-contacts' ),
		'error'          => __( 'Something went wrong. Please check your input and try again.', 'advisers-contacts' ),
	);

	$type_labels = array(
		'text'     => __( 'Text', 'advisers-contacts' ),
		'textarea' => __( 'Textarea', 'advisers-contacts' ),
		'email'    => __( 'Email', 'advisers-contacts' ),
		'tel'      => __( 'Phone', 'advisers-contacts' ),
		'url'      => __( 'URL', 'advisers-contacts' ),
		'date'     => __( 'Date', 'advisers-contacts' ),
		'select'   => __( 'Dropdown', 'advisers-contacts' ),
		'radio'    => __( 'Radio buttons', 'advisers-contacts' ),
		'checkbox' => __( 'Checkbox', 'advisers-contacts' ),
		'file'     => __( 'File Upload', 'advisers-contacts' ),
		'submit'   => __( 'Submit button', 'advisers-contacts' ),
	);
	?>
<div class="wrap advc-wrap">
	<h1><?php esc_html_e( 'Field Builder', 'advisers-contacts' ); ?></h1>
	<p><?php esc_html_e( 'Build the form your business needs. Drag fields to reorder them, switch them on or off, make them required, set half or full width, and edit labels, placeholders, help text and choices. Any field, including Full Name, Email and Submit, can be deleted and restored later.', 'advisers-contacts' ); ?></p>

	<?php if ( $notice && isset( $notices[ $notice ] ) ) : ?>
		<div class="notice <?php echo 'error' === $notice ? 'notice-error' : 'notice-success'; ?> is-dismissible"><p><?php echo esc_html( $notices[ $notice ] ); ?></p></div>
	<?php endif; ?>

	<div class="advc-preset-box">
		<div>
			<strong><?php esc_html_e( 'Lead Generation layout', 'advisers-contacts' ); ?></strong>
			<p><?php esc_html_e( 'One click sets up a digital marketing lead form: contact details, company, service, budget, timeline, preferred contact method, message, referral source and consent.', 'advisers-contacts' ); ?></p>
		</div>
		<form method="post" action="<?php echo esc_url( $post_url ); ?>" class="advc-confirm-form" data-confirm="preset">
			<input type="hidden" name="action" value="advc_apply_preset" />
			<?php wp_nonce_field( 'advc_field_builder', '_advc_nonce' ); ?>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Apply Lead Generation Layout', 'advisers-contacts' ); ?></button>
		</form>
	</div>

	<form method="post" action="<?php echo esc_url( $post_url ); ?>" id="advc-field-order-form">
		<input type="hidden" name="action" value="advc_save_field_order" />
		<input type="hidden" name="fields" id="advc-fields-json" value="" />
		<?php wp_nonce_field( 'advc_field_builder', '_advc_nonce' ); ?>

		<ul id="advc-field-list" class="advc-field-list">
			<?php foreach ( $fields as $field ) : ?>
				<?php
				$has_options = in_array( $field->type, array( 'select', 'radio' ), true );
				$is_submit   = 'submit' === $field->type;
				?>
				<li class="advc-field-row" data-id="<?php echo (int) $field->id; ?>">
					<div class="advc-field-main">
						<span class="dashicons dashicons-menu advc-drag-handle" aria-hidden="true"></span>
						<input type="text" class="advc-field-label regular-text" value="<?php echo esc_attr( $field->label ); ?>" aria-label="<?php esc_attr_e( 'Field label', 'advisers-contacts' ); ?>" />
						<span class="advc-field-type"><?php echo esc_html( isset( $type_labels[ $field->type ] ) ? $type_labels[ $field->type ] : $field->type ); ?></span>

						<select class="advc-field-width" aria-label="<?php esc_attr_e( 'Width', 'advisers-contacts' ); ?>">
							<option value="full" <?php selected( $field->width, 'full' ); ?>><?php esc_html_e( 'Full width', 'advisers-contacts' ); ?></option>
							<option value="half" <?php selected( $field->width, 'half' ); ?>><?php esc_html_e( 'Half width', 'advisers-contacts' ); ?></option>
						</select>

						<label class="advc-field-toggle">
							<input type="checkbox" class="advc-field-enabled" <?php checked( 1, (int) $field->is_enabled ); ?> />
							<?php esc_html_e( 'Enabled', 'advisers-contacts' ); ?>
						</label>

						<label class="advc-field-toggle">
							<input type="checkbox" class="advc-field-required" <?php checked( 1, (int) $field->is_required ); ?> />
							<?php esc_html_e( 'Required', 'advisers-contacts' ); ?>
						</label>

						<?php if ( ! $is_submit ) : ?>
							<button type="button" class="button-link advc-field-settings-toggle" aria-expanded="false"><?php esc_html_e( 'Settings', 'advisers-contacts' ); ?></button>
						<?php endif; ?>

						<button type="submit" form="advc-delete-field-form" name="id" value="<?php echo (int) $field->id; ?>" class="button-link advc-delete-field-link" aria-label="<?php esc_attr_e( 'Delete field', 'advisers-contacts' ); ?>"><span class="dashicons dashicons-trash"></span></button>
					</div>

					<?php if ( ! $is_submit ) : ?>
						<div class="advc-field-extra" hidden>
							<?php if ( 'checkbox' !== $field->type ) : ?>
								<label>
									<span><?php esc_html_e( 'Placeholder', 'advisers-contacts' ); ?></span>
									<input type="text" class="advc-field-placeholder regular-text" value="<?php echo esc_attr( $field->placeholder ); ?>" />
								</label>
							<?php endif; ?>
							<label>
								<span><?php esc_html_e( 'Help text', 'advisers-contacts' ); ?></span>
								<input type="text" class="advc-field-help regular-text" value="<?php echo esc_attr( $field->help_text ); ?>" />
							</label>
							<?php if ( $has_options ) : ?>
								<label>
									<span><?php esc_html_e( 'Choices (one per line)', 'advisers-contacts' ); ?></span>
									<textarea class="advc-field-options large-text" rows="5"><?php echo esc_textarea( implode( "\n", ADVC_Fields::get_options( $field ) ) ); ?></textarea>
								</label>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php submit_button( __( 'Save Form', 'advisers-contacts' ) ); ?>
	</form>

	<form method="post" action="<?php echo esc_url( $post_url ); ?>" id="advc-delete-field-form" class="advc-confirm-form" data-confirm="field">
		<input type="hidden" name="action" value="advc_delete_field" />
		<?php wp_nonce_field( 'advc_field_builder', '_advc_nonce' ); ?>
	</form>

	<hr />

	<?php if ( $restorable ) : ?>
		<h2><?php esc_html_e( 'Available Fields', 'advisers-contacts' ); ?></h2>
		<p><?php esc_html_e( 'Built-in fields that are not in your form right now. Add any of them with one click.', 'advisers-contacts' ); ?></p>
		<table class="widefat striped advc-restore-table">
			<tbody>
				<?php foreach ( $restorable as $def ) : ?>
					<tr>
						<td><?php echo esc_html( $def['label'] ); ?></td>
						<td><span class="advc-field-type"><?php echo esc_html( isset( $type_labels[ $def['type'] ] ) ? $type_labels[ $def['type'] ] : $def['type'] ); ?></span></td>
						<td>
							<form method="post" action="<?php echo esc_url( $post_url ); ?>" class="advc-inline-form">
								<input type="hidden" name="action" value="advc_restore_field" />
								<input type="hidden" name="field_key" value="<?php echo esc_attr( $def['key'] ); ?>" />
								<?php wp_nonce_field( 'advc_field_builder', '_advc_nonce' ); ?>
								<button type="submit" class="button"><?php esc_html_e( 'Add to Form', 'advisers-contacts' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<hr />
	<?php endif; ?>

	<h2><?php esc_html_e( 'Add a Custom Field', 'advisers-contacts' ); ?></h2>
	<form method="post" action="<?php echo esc_url( $post_url ); ?>">
		<input type="hidden" name="action" value="advc_add_field" />
		<?php wp_nonce_field( 'advc_field_builder', '_advc_nonce' ); ?>
		<table class="form-table">
			<tr>
				<th><label for="advc-new-label"><?php esc_html_e( 'Label', 'advisers-contacts' ); ?></label></th>
				<td><input type="text" id="advc-new-label" name="label" class="regular-text" required /></td>
			</tr>
			<tr>
				<th><label for="advc-new-type"><?php esc_html_e( 'Field Type', 'advisers-contacts' ); ?></label></th>
				<td>
					<select id="advc-new-type" name="type">
						<?php foreach ( ADVC_Fields::allowed_types() as $type ) : ?>
							<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $type_labels[ $type ] ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="advc-new-width"><?php esc_html_e( 'Width', 'advisers-contacts' ); ?></label></th>
				<td>
					<select id="advc-new-width" name="width">
						<option value="full"><?php esc_html_e( 'Full width', 'advisers-contacts' ); ?></option>
						<option value="half"><?php esc_html_e( 'Half width (two per row)', 'advisers-contacts' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="advc-new-options"><?php esc_html_e( 'Choices', 'advisers-contacts' ); ?></label></th>
				<td>
					<textarea id="advc-new-options" name="options" class="large-text" rows="3" placeholder="<?php esc_attr_e( 'One choice per line (Dropdown and Radio fields only)', 'advisers-contacts' ); ?>"></textarea>
				</td>
			</tr>
			<tr>
				<th><label for="advc-new-placeholder"><?php esc_html_e( 'Placeholder', 'advisers-contacts' ); ?></label></th>
				<td><input type="text" id="advc-new-placeholder" name="placeholder" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="advc-new-help"><?php esc_html_e( 'Help text', 'advisers-contacts' ); ?></label></th>
				<td><input type="text" id="advc-new-help" name="help_text" class="regular-text" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Required', 'advisers-contacts' ); ?></th>
				<td><label><input type="checkbox" name="required" value="1" /> <?php esc_html_e( 'Make this field mandatory', 'advisers-contacts' ); ?></label></td>
			</tr>
		</table>
		<?php submit_button( __( 'Add Field', 'advisers-contacts' ) ); ?>
	</form>
</div>
	<?php
} )();
