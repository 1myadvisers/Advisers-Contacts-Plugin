<?php
/**
 * View: Submission detail + edit screen.
 *
 * Expects $submission (object) to be set by the calling controller.
 *
 * @package Advisers_Contacts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Wrapped in a closure so template variables never leak into the global scope.
( static function ( $submission ) {

$fields       = ADVC_Fields::get_fields();
$extra_data   = $submission->field_data ? json_decode( $submission->field_data, true ) : array();
$attachments  = ADVC_Helpers::get_attachments( $submission->attachment_ids );
$statuses     = ADVC_Helpers::statuses();
$post_url     = admin_url( 'admin-post.php' );
$stages       = ADVC_Helpers::stages();
$temps        = ADVC_Helpers::temperatures();
$temp_key     = ADVC_Helpers::temperature( $submission->lead_score );
$source       = $submission->source_data ? json_decode( $submission->source_data, true ) : array();
$source       = is_array( $source ) ? $source : array();

/**
 * Resolve the current value for a field key from the submission row,
 * falling back to the JSON-stored "extra" data.
 *
 * @param string $key Field key.
 * @return string
 */
$get_value = function ( $key ) use ( $submission, $extra_data ) {
	if ( isset( $submission->$key ) ) {
		return $submission->$key;
	}
	return isset( $extra_data[ $key ] ) ? $extra_data[ $key ] : '';
};
?>
<div class="wrap advc-wrap">
	<h1 class="wp-heading-inline">
		<?php echo esc_html( $submission->full_name ? $submission->full_name : __( 'Submission', 'advisers-contacts' ) ); ?>
		#<?php echo (int) $submission->id; ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=advisers-contacts' ) ); ?>" class="page-title-action"><?php esc_html_e( '← Back to Submissions', 'advisers-contacts' ); ?></a>
	<hr class="wp-header-end" />

	<?php if ( isset( $_GET['advc_notice'] ) && in_array( $_GET['advc_notice'], array( 'saved', 'reward_updated' ), true ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Submission updated.', 'advisers-contacts' ); ?></p></div>
	<?php endif; ?>

	<div class="advc-detail-toolbar">
		<span class="advc-badge <?php echo esc_attr( ADVC_Helpers::status_badge_class( $submission->status ) ); ?>">
			<?php echo esc_html( isset( $statuses[ $submission->status ] ) ? $statuses[ $submission->status ] : $submission->status ); ?>
		</span>

		<?php foreach ( array( 'approved' => __( 'Approve', 'advisers-contacts' ), 'rejected' => __( 'Reject', 'advisers-contacts' ), 'pending' => __( 'Mark Pending', 'advisers-contacts' ) ) as $status_key => $label ) : ?>
			<form method="post" action="<?php echo esc_url( $post_url ); ?>" class="advc-inline-form">
				<input type="hidden" name="action" value="advc_change_status" />
				<input type="hidden" name="id" value="<?php echo (int) $submission->id; ?>" />
				<input type="hidden" name="status" value="<?php echo esc_attr( $status_key ); ?>" />
				<?php wp_nonce_field( 'advc_submissions_action', '_advc_nonce' ); ?>
				<button type="submit" class="button<?php echo $submission->status === $status_key ? ' button-primary' : ''; ?>"><?php echo esc_html( $label ); ?></button>
			</form>
		<?php endforeach; ?>

		<form method="post" action="<?php echo esc_url( $post_url ); ?>" class="advc-inline-form">
			<input type="hidden" name="action" value="advc_toggle_important" />
			<input type="hidden" name="id" value="<?php echo (int) $submission->id; ?>" />
			<?php wp_nonce_field( 'advc_submissions_action', '_advc_nonce' ); ?>
			<button type="submit" class="button">
				<span class="dashicons <?php echo $submission->is_important ? 'dashicons-star-filled' : 'dashicons-star-empty'; ?>"></span>
				<?php echo $submission->is_important ? esc_html__( 'Unmark Important', 'advisers-contacts' ) : esc_html__( 'Mark Important', 'advisers-contacts' ); ?>
			</button>
		</form>

		<form method="post" action="<?php echo esc_url( $post_url ); ?>" class="advc-inline-form" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this submission? This cannot be undone.', 'advisers-contacts' ) ); ?>');">
			<input type="hidden" name="action" value="advc_delete_submission" />
			<input type="hidden" name="id" value="<?php echo (int) $submission->id; ?>" />
			<?php wp_nonce_field( 'advc_submissions_action', '_advc_nonce' ); ?>
			<button type="submit" class="button button-link-delete"><?php esc_html_e( 'Delete', 'advisers-contacts' ); ?></button>
		</form>
	</div>

	<?php
	// Plain-text summary of this lead for sharing with a team member.
	$share_lines = array();
	foreach ( $fields as $share_field ) {
		if ( in_array( $share_field->type, array( 'submit', 'file' ), true ) ) {
			continue;
		}
		$share_value = $get_value( $share_field->field_key );
		if ( '' !== (string) $share_value ) {
			$share_lines[] = $share_field->label . ': ' . $share_value;
		}
	}
	$share_text = __( 'Lead details', 'advisers-contacts' ) . "\n\n" . implode( "\n", $share_lines );
	?>
	<div class="advc-lead-share">
		<strong><?php esc_html_e( 'Share this lead', 'advisers-contacts' ); ?></strong>
		<button type="button" class="button advc-copy-lead" data-text="<?php echo esc_attr( $share_text ); ?>" data-copied="<?php esc_attr_e( 'Copied!', 'advisers-contacts' ); ?>"><?php esc_html_e( 'Copy details', 'advisers-contacts' ); ?></button>
		<a class="button" target="_blank" rel="noopener noreferrer" href="<?php echo esc_attr( 'https://wa.me/?text=' . rawurlencode( $share_text ) ); // esc_url() would strip the %0A line breaks; the value is a fixed https prefix plus a URL-encoded string. ?>"><?php esc_html_e( 'WhatsApp', 'advisers-contacts' ); ?></a>
		<a class="button" href="<?php echo esc_attr( 'mailto:?subject=' . rawurlencode( __( 'Lead details', 'advisers-contacts' ) ) . '&body=' . rawurlencode( $share_text ) ); // Fixed mailto prefix plus URL-encoded text; esc_url() would strip line breaks. ?>"><?php esc_html_e( 'Email', 'advisers-contacts' ); ?></a>
	</div>

	<div class="advc-detail-grid">
		<div class="advc-detail-main">
			<h2><?php esc_html_e( 'Submitted Information', 'advisers-contacts' ); ?></h2>
			<table class="widefat striped advc-detail-table">
				<tbody>
				<?php foreach ( $fields as $field ) : ?>
					<?php
					if ( in_array( $field->type, array( 'submit', 'file' ), true ) ) {
						continue;
					}
					$value = $get_value( $field->field_key );
					?>
					<tr>
						<th><?php echo esc_html( $field->label ); ?></th>
						<td>
							<?php if ( 'mobile_number' === $field->field_key && $value ) : ?>
								<a href="<?php echo esc_url( ADVC_Helpers::whatsapp_url( $value ) ); ?>" target="_blank" rel="noopener noreferrer">
									<span class="dashicons dashicons-whatsapp"></span> <?php echo esc_html( $value ); ?>
								</a>
							<?php elseif ( 'email' === $field->field_key && $value ) : ?>
								<a href="<?php echo esc_url( ADVC_Helpers::mailto_url( $value ) ); ?>"><?php echo esc_html( $value ); ?></a>
							<?php elseif ( 'website_url' === $field->field_key && $value ) : ?>
								<a href="<?php echo esc_url( $value ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $value ); ?></a>
							<?php else : ?>
								<?php echo $value ? nl2br( esc_html( $value ) ) : '&#8212;'; ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>

				<?php if ( $attachments ) : ?>
					<tr>
						<th><?php esc_html_e( 'Upload Files', 'advisers-contacts' ); ?></th>
						<td>
							<?php foreach ( $attachments as $file ) : ?>
								<a href="<?php echo esc_url( $file['url'] ); ?>" target="_blank" rel="noopener noreferrer" class="advc-attachment-link"><?php echo esc_html( $file['title'] ); ?></a><br />
							<?php endforeach; ?>
						</td>
					</tr>
				<?php endif; ?>
				</tbody>
			</table>

			<h2 id="advc-edit"><?php esc_html_e( 'Edit Submission', 'advisers-contacts' ); ?></h2>
			<form method="post" action="<?php echo esc_url( $post_url ); ?>">
				<input type="hidden" name="action" value="advc_update_submission" />
				<input type="hidden" name="id" value="<?php echo (int) $submission->id; ?>" />
				<?php wp_nonce_field( 'advc_submissions_action', '_advc_nonce' ); ?>
				<table class="form-table">
					<?php foreach ( $fields as $field ) : ?>
						<?php
						if ( in_array( $field->type, array( 'submit', 'file' ), true ) ) {
							continue;
						}
						$value = $get_value( $field->field_key );
						$name  = 'advc_field_' . $field->field_key;
						?>
						<tr>
							<th><label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $field->label ); ?></label></th>
							<td>
								<?php if ( 'textarea' === $field->type ) : ?>
									<textarea class="large-text" rows="3" id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
								<?php elseif ( 'checkbox' === $field->type ) : ?>
									<label><input type="checkbox" id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( 'Yes', $value ); ?> /> <?php esc_html_e( 'Yes', 'advisers-contacts' ); ?></label>
								<?php elseif ( in_array( $field->type, array( 'select', 'radio' ), true ) ) : ?>
									<?php $options = ADVC_Fields::get_options( $field ); ?>
									<select id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>">
										<option value=""><?php esc_html_e( '— Select —', 'advisers-contacts' ); ?></option>
										<?php foreach ( (array) $options as $option ) : ?>
											<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $value, $option ); ?>><?php echo esc_html( $option ); ?></option>
										<?php endforeach; ?>
									</select>
								<?php else : ?>
									<input type="<?php echo 'date' === $field->type ? 'date' : 'text'; ?>" class="regular-text" id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" />
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
				<?php submit_button( __( 'Save Changes', 'advisers-contacts' ) ); ?>
			</form>
		</div>

		<div class="advc-detail-side">
			<h2><?php esc_html_e( 'Lead', 'advisers-contacts' ); ?></h2>
			<div class="advc-lead-card">
				<div class="advc-lead-score">
					<span class="advc-score-number"><?php echo (int) $submission->lead_score; ?></span>
					<span class="advc-score-max">/ 100</span>
					<span class="advc-badge advc-temp-<?php echo esc_attr( $temp_key ); ?>"><?php echo esc_html( $temps[ $temp_key ][0] ); ?></span>
				</div>
				<form method="post" action="<?php echo esc_url( $post_url ); ?>" class="advc-stage-form">
					<input type="hidden" name="action" value="advc_change_stage" />
					<input type="hidden" name="id" value="<?php echo (int) $submission->id; ?>" />
					<?php wp_nonce_field( 'advc_submissions_action', '_advc_nonce' ); ?>
					<label for="advc-lead-stage"><?php esc_html_e( 'Pipeline stage', 'advisers-contacts' ); ?></label>
					<select id="advc-lead-stage" name="lead_stage">
						<?php foreach ( $stages as $stage_key => $stage_label ) : ?>
							<option value="<?php echo esc_attr( $stage_key ); ?>" <?php selected( $submission->lead_stage, $stage_key ); ?>><?php echo esc_html( $stage_label ); ?></option>
						<?php endforeach; ?>
					</select>
					<button type="submit" class="button"><?php esc_html_e( 'Update', 'advisers-contacts' ); ?></button>
				</form>
			</div>

			<?php if ( '' !== (string) $submission->referred_by ) : ?>
				<h2><?php esc_html_e( 'Referral Reward', 'advisers-contacts' ); ?></h2>
				<div class="advc-lead-card">
					<p>
						<?php esc_html_e( 'Referred by', 'advisers-contacts' ); ?>
						<strong><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'advc-referrers', 'referrer' => rawurlencode( $submission->referred_by ) ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $submission->referred_by ); ?></a></strong>
					</p>
					<form method="post" action="<?php echo esc_url( $post_url ); ?>" class="advc-inline-form">
						<input type="hidden" name="action" value="advc_toggle_rewarded" />
						<input type="hidden" name="id" value="<?php echo (int) $submission->id; ?>" />
						<input type="hidden" name="rewarded" value="<?php echo $submission->is_rewarded ? '0' : '1'; ?>" />
						<?php wp_nonce_field( 'advc_submissions_action', '_advc_nonce' ); ?>
						<?php if ( $submission->is_rewarded ) : ?>
							<span class="advc-badge advc-badge-approved"><?php esc_html_e( 'Rewarded', 'advisers-contacts' ); ?></span>
							<button type="submit" class="button-link"><?php esc_html_e( 'Undo', 'advisers-contacts' ); ?></button>
						<?php else : ?>
							<span class="advc-badge advc-badge-pending"><?php esc_html_e( 'Not yet rewarded', 'advisers-contacts' ); ?></span>
							<button type="submit" class="button"><?php esc_html_e( 'Mark Rewarded', 'advisers-contacts' ); ?></button>
						<?php endif; ?>
					</form>
				</div>
			<?php endif; ?>

			<?php if ( $source ) : ?>
				<h2><?php esc_html_e( 'Marketing Source', 'advisers-contacts' ); ?></h2>
				<table class="widefat striped advc-source-table">
					<tbody>
						<?php foreach ( $source as $source_key => $source_value ) : ?>
							<tr>
								<th><?php echo esc_html( $source_key ); ?></th>
								<td>
									<?php if ( in_array( $source_key, array( 'landing_page', 'referrer' ), true ) ) : ?>
										<a href="<?php echo esc_url( $source_value ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $source_value ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $source_value ); ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Meta', 'advisers-contacts' ); ?></h2>
			<table class="widefat striped">
				<tbody>
					<tr><th><?php esc_html_e( 'Submitted', 'advisers-contacts' ); ?></th><td><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $submission->created_at ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Last Updated', 'advisers-contacts' ); ?></th><td><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $submission->updated_at ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'IP Address', 'advisers-contacts' ); ?></th><td><?php echo esc_html( $submission->ip_address ); ?></td></tr>
				</tbody>
			</table>
		</div>
	</div>
</div>
<?php
} )( $submission );
