<?php
/**
 * View: Submissions dashboard.
 *
 * @package Advisers_Contacts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Wrapped in a closure so template variables never leak into the global scope.
( static function ( ) {

$list_table = new ADVC_List_Table();
$list_table->prepare_items();

$notice = isset( $_GET['advc_notice'] ) ? sanitize_key( wp_unslash( $_GET['advc_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$notices = array(
	'status_updated'    => __( 'Status updated.', 'advisers-contacts' ),
	'important_updated' => __( 'Updated.', 'advisers-contacts' ),
	'deleted'            => __( 'Submission deleted.', 'advisers-contacts' ),
	'bulk_done'          => __( 'Bulk action completed.', 'advisers-contacts' ),
	'reward_updated'     => __( 'Reward status updated.', 'advisers-contacts' ),
);
?>
<div class="wrap advc-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Contact Form Submissions', 'advisers-contacts' ); ?></h1>
	<hr class="wp-header-end" />

	<?php if ( $notice && isset( $notices[ $notice ] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notices[ $notice ] ); ?></p></div>
	<?php endif; ?>

	<?php $list_table->views(); ?>

	<form method="get" id="advc-submissions-filter-form">
		<input type="hidden" name="page" value="advisers-contacts" />
		<?php
		$list_table->search_box( __( 'Search submissions', 'advisers-contacts' ), 'advc-search' );
		?>
	</form>

	<form method="post" id="advc-submissions-form"
		action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="advc_bulk_action" />
		<input type="hidden" name="bulk_action" id="advc-bulk-action-field" value="" />
		<?php wp_nonce_field( 'advc_submissions_action', '_advc_nonce' ); ?>

		<?php
		// Preserve current filters as hidden fields so pagination/sort links keep them.
		foreach ( array( 'advc_status', 'advc_stage', 'advc_temp', 'advc_rewarded', 'advc_referrer', 'advc_date_from', 'advc_date_to', 's' ) as $key ) {
			if ( ! empty( $_REQUEST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				printf( '<input type="hidden" name="%s" value="%s" />', esc_attr( $key ), esc_attr( sanitize_text_field( wp_unslash( $_REQUEST[ $key ] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
		}
		$list_table->display();
		?>
	</form>
</div>

<form method="post" id="advc-row-action-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:none;">
	<input type="hidden" name="action" id="advc-row-action-name" value="" />
	<input type="hidden" name="id" id="advc-row-action-id" value="" />
	<?php wp_nonce_field( 'advc_submissions_action', '_advc_nonce' ); ?>
</form>
<?php
} )();
