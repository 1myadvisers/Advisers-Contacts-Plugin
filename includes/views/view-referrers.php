<?php
/**
 * View: Referrers overview + per-referrer history, rewards and payout details.
 *
 * @package Advisers_Contacts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Wrapped in a closure so template variables never leak into the global scope.
( static function () {

	$drill_into = isset( $_GET['referrer'] ) ? sanitize_text_field( wp_unslash( $_GET['referrer'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$notice     = isset( $_GET['advc_notice'] ) ? sanitize_key( wp_unslash( $_GET['advc_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$stats      = ADVC_DB::get_referrer_stats();
	$statuses   = ADVC_Helpers::statuses();
	$post_url   = admin_url( 'admin-post.php' );
	$notices    = array(
		'payout_saved'   => __( 'Payout details saved.', 'advisers-contacts' ),
		'reward_updated' => __( 'Reward status updated.', 'advisers-contacts' ),
	);

	// Totals across every referrer.
	$sum_total    = 0;
	$sum_rewarded = 0;
	foreach ( $stats as $stat_row ) {
		$sum_total    += (int) $stat_row['total'];
		$sum_rewarded += (int) $stat_row['rewarded'];
	}
	?>
<div class="wrap advc-wrap">
	<h1><?php esc_html_e( 'Referrers', 'advisers-contacts' ); ?></h1>

	<?php if ( $notice && isset( $notices[ $notice ] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notices[ $notice ] ); ?></p></div>
	<?php endif; ?>

	<?php if ( ! $drill_into ) : ?>

		<div class="advc-stat-cards">
			<div class="advc-stat-card"><span class="advc-stat-number"><?php echo (int) ADVC_DB::count_referrers(); ?></span><span class="advc-stat-label"><?php esc_html_e( 'Referrers', 'advisers-contacts' ); ?></span></div>
			<div class="advc-stat-card"><span class="advc-stat-number"><?php echo (int) $sum_total; ?></span><span class="advc-stat-label"><?php esc_html_e( 'Total Referrals', 'advisers-contacts' ); ?></span></div>
			<div class="advc-stat-card"><span class="advc-stat-number"><?php echo (int) $sum_rewarded; ?></span><span class="advc-stat-label"><?php esc_html_e( 'Rewarded', 'advisers-contacts' ); ?></span></div>
			<div class="advc-stat-card"><span class="advc-stat-number"><?php echo (int) ( $sum_total - $sum_rewarded ); ?></span><span class="advc-stat-label"><?php esc_html_e( 'Not Yet Rewarded', 'advisers-contacts' ); ?></span></div>
		</div>

		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Referrer', 'advisers-contacts' ); ?></th>
					<th><?php esc_html_e( 'Total Referrals', 'advisers-contacts' ); ?></th>
					<th><?php esc_html_e( 'Approved', 'advisers-contacts' ); ?></th>
					<th><?php esc_html_e( 'Rejected', 'advisers-contacts' ); ?></th>
					<th><?php esc_html_e( 'Pending', 'advisers-contacts' ); ?></th>
					<th><?php esc_html_e( 'Rewarded', 'advisers-contacts' ); ?></th>
					<th><?php esc_html_e( 'Not Yet Rewarded', 'advisers-contacts' ); ?></th>
					<th><?php esc_html_e( 'Payout Details', 'advisers-contacts' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! $stats ) : ?>
					<tr><td colspan="9"><?php esc_html_e( 'No referrals recorded yet.', 'advisers-contacts' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $stats as $row ) : ?>
					<?php $payout = ADVC_DB::get_payout_details( $row['referrer'] ); ?>
					<tr>
						<td><strong><?php echo esc_html( $row['referrer'] ); ?></strong></td>
						<td><?php echo (int) $row['total']; ?></td>
						<td><span class="advc-badge advc-badge-approved"><?php echo (int) $row['approved']; ?></span></td>
						<td><span class="advc-badge advc-badge-rejected"><?php echo (int) $row['rejected']; ?></span></td>
						<td><span class="advc-badge advc-badge-pending"><?php echo (int) $row['pending']; ?></span></td>
						<td><?php echo (int) $row['rewarded']; ?></td>
						<td><?php echo (int) ( $row['total'] - $row['rewarded'] ); ?></td>
						<td class="advc-payout-cell">
							<?php if ( '' !== $payout ) : ?>
								<?php echo esc_html( wp_trim_words( $payout, 8 ) ); ?>
							<?php else : ?>
								<span class="advc-muted"><?php esc_html_e( 'Not added', 'advisers-contacts' ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'advc-referrers', 'referrer' => rawurlencode( $row['referrer'] ) ), admin_url( 'admin.php' ) ) ); ?>" class="button">
								<?php esc_html_e( 'History & Payout', 'advisers-contacts' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

	<?php else : ?>

		<a href="<?php echo esc_url( admin_url( 'admin.php?page=advc-referrers' ) ); ?>" class="page-title-action"><?php esc_html_e( '← All Referrers', 'advisers-contacts' ); ?></a>
		<h2><?php echo esc_html( $drill_into ); ?></h2>

		<?php
		// The cards use the grouped totals from get_referrer_stats(), so they
		// cover every referral, not just the rows on this page of the list.
		$ref_totals = array(
			'total'    => 0,
			'rewarded' => 0,
		);
		foreach ( $stats as $stat_row ) {
			if ( 0 === strcasecmp( (string) $stat_row['referrer'], $drill_into ) ) {
				$ref_totals['total']    = (int) $stat_row['total'];
				$ref_totals['rewarded'] = (int) $stat_row['rewarded'];
				break;
			}
		}

		$per_page = 25;
		$paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination.
		$result   = ADVC_DB::query_submissions(
			array(
				'referrer' => $drill_into,
				'orderby'  => 'created_at',
				'order'    => 'DESC',
				'per_page' => $per_page,
				'paged'    => $paged,
			)
		);
		?>

		<div class="advc-stat-cards">
			<div class="advc-stat-card"><span class="advc-stat-number"><?php echo (int) $ref_totals['total']; ?></span><span class="advc-stat-label"><?php esc_html_e( 'Total Referrals', 'advisers-contacts' ); ?></span></div>
			<div class="advc-stat-card"><span class="advc-stat-number"><?php echo (int) $ref_totals['rewarded']; ?></span><span class="advc-stat-label"><?php esc_html_e( 'Rewarded', 'advisers-contacts' ); ?></span></div>
			<div class="advc-stat-card"><span class="advc-stat-number"><?php echo (int) ( $ref_totals['total'] - $ref_totals['rewarded'] ); ?></span><span class="advc-stat-label"><?php esc_html_e( 'Not Yet Rewarded', 'advisers-contacts' ); ?></span></div>
		</div>

		<div class="advc-payout-box">
			<h3><?php esc_html_e( 'Payout Details', 'advisers-contacts' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Type where this referrer should be paid, for example a UPI ID, bank account details or PayPal email. Only administrators can see this.', 'advisers-contacts' ); ?></p>
			<form method="post" action="<?php echo esc_url( $post_url ); ?>">
				<input type="hidden" name="action" value="advc_save_payout" />
				<input type="hidden" name="referrer" value="<?php echo esc_attr( $drill_into ); ?>" />
				<?php wp_nonce_field( 'advc_referrers', '_advc_nonce' ); ?>
				<textarea name="payout_details" class="large-text" rows="4" maxlength="1000" aria-label="<?php esc_attr_e( 'Payout details', 'advisers-contacts' ); ?>"><?php echo esc_textarea( ADVC_DB::get_payout_details( $drill_into ) ); ?></textarea>
				<?php submit_button( __( 'Save Payout Details', 'advisers-contacts' ), 'primary', 'submit', false ); ?>
			</form>
		</div>

		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Referred Person', 'advisers-contacts' ); ?></th>
					<th><?php esc_html_e( 'Mobile', 'advisers-contacts' ); ?></th>
					<th><?php esc_html_e( 'Email', 'advisers-contacts' ); ?></th>
					<th><?php esc_html_e( 'Status', 'advisers-contacts' ); ?></th>
					<th><?php esc_html_e( 'Date', 'advisers-contacts' ); ?></th>
					<th><?php esc_html_e( 'Reward', 'advisers-contacts' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $result['items'] as $item ) : ?>
					<tr>
						<td><?php echo esc_html( $item->full_name ); ?></td>
						<td><a href="<?php echo esc_url( ADVC_Helpers::whatsapp_url( $item->mobile_number ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $item->mobile_number ); ?></a></td>
						<td><a href="<?php echo esc_url( ADVC_Helpers::mailto_url( $item->email ) ); ?>"><?php echo esc_html( $item->email ); ?></a></td>
						<td><span class="advc-badge <?php echo esc_attr( ADVC_Helpers::status_badge_class( $item->status ) ); ?>"><?php echo esc_html( isset( $statuses[ $item->status ] ) ? $statuses[ $item->status ] : $item->status ); ?></span></td>
						<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $item->created_at ) ); ?></td>
						<td>
							<form method="post" action="<?php echo esc_url( $post_url ); ?>" class="advc-inline-form">
								<input type="hidden" name="action" value="advc_toggle_rewarded" />
								<input type="hidden" name="id" value="<?php echo (int) $item->id; ?>" />
								<input type="hidden" name="rewarded" value="<?php echo $item->is_rewarded ? '0' : '1'; ?>" />
								<?php wp_nonce_field( 'advc_submissions_action', '_advc_nonce' ); ?>
								<?php if ( $item->is_rewarded ) : ?>
									<span class="advc-badge advc-badge-approved"><?php esc_html_e( 'Rewarded', 'advisers-contacts' ); ?></span>
									<?php if ( $item->rewarded_at ) : ?>
										<span class="advc-muted"><?php echo esc_html( mysql2date( get_option( 'date_format' ), $item->rewarded_at ) ); ?></span>
									<?php endif; ?>
									<button type="submit" class="button-link"><?php esc_html_e( 'Undo', 'advisers-contacts' ); ?></button>
								<?php else : ?>
									<button type="submit" class="button button-small"><?php esc_html_e( 'Mark Rewarded', 'advisers-contacts' ); ?></button>
								<?php endif; ?>
							</form>
						</td>
						<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=advc-view-submission&id=' . $item->id ) ); ?>"><?php esc_html_e( 'View', 'advisers-contacts' ); ?></a></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php
		$page_links = paginate_links(
			array(
				'base'      => add_query_arg( 'paged', '%#%', admin_url( 'admin.php?page=advc-referrers&referrer=' . rawurlencode( $drill_into ) ) ),
				'format'    => '',
				'current'   => $paged,
				'total'     => max( 1, (int) ceil( (int) $result['total'] / $per_page ) ),
				'prev_text' => '&laquo;',
				'next_text' => '&raquo;',
			)
		);
		if ( $page_links ) :
			?>
			<div class="tablenav"><div class="tablenav-pages"><?php echo wp_kses_post( $page_links ); ?></div></div>
		<?php endif; ?>
	<?php endif; ?>
</div>
	<?php
} )();
