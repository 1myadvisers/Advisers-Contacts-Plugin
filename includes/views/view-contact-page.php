<?php
/**
 * View: Contact page settings.
 *
 * @package Advisers_Contacts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Wrapped in a closure so template variables never leak into the global scope.
( static function () {

	$page_id = ADVC_Page::get_page_id();
	$page    = $page_id ? get_post( $page_id ) : null;
	$notice  = isset( $_GET['advc_notice'] ) ? sanitize_key( wp_unslash( $_GET['advc_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$post    = admin_url( 'admin-post.php' );

	$notices = array(
		'page_saved'    => array( 'success', __( 'Contact page updated.', 'advisers-contacts' ) ),
		'page_linked'   => array( 'success', __( 'This page now carries the contact form.', 'advisers-contacts' ) ),
		'page_created'  => array( 'success', __( 'A new contact page was created.', 'advisers-contacts' ) ),
		'slug_adjusted' => array( 'warning', __( 'Saved, but that address is already used by another page, so WordPress picked a similar one. Check the address below.', 'advisers-contacts' ) ),
		'page_error'    => array( 'error', __( 'The contact page could not be saved. Please try again.', 'advisers-contacts' ) ),
	);

	$title   = $page ? $page->post_title : '';
	$slug    = $page ? $page->post_name : '';
	$content = $page ? trim( str_replace( ADVC_Page::SHORTCODE, '', $page->post_content ) ) : '';
	$link    = $page ? get_permalink( $page ) : '';
	$prefix  = $link && '' !== $slug && substr( $link, -strlen( $slug ) - 1 ) === $slug . '/' ? substr( $link, 0, -strlen( $slug ) - 1 ) : trailingslashit( home_url() );
	?>
<div class="wrap advc-wrap">
	<h1><?php esc_html_e( 'Contact Page', 'advisers-contacts' ); ?></h1>

	<?php if ( $notice && isset( $notices[ $notice ] ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notices[ $notice ][0] ); ?> is-dismissible"><p><?php echo esc_html( $notices[ $notice ][1] ); ?></p></div>
	<?php endif; ?>

	<?php if ( ! $page ) : ?>

		<p><?php esc_html_e( 'No page is carrying the contact form yet. If your site already has a contact page, choose it below and the form will be added to it. Nothing is created or changed until you click a button.', 'advisers-contacts' ); ?></p>

		<form method="post" action="<?php echo esc_url( $post ); ?>" class="advc-page-pick">
			<input type="hidden" name="action" value="advc_use_existing_page" />
			<?php wp_nonce_field( 'advc_contact_page', '_advc_nonce' ); ?>
			<label for="existing_page"><strong><?php esc_html_e( 'Use an existing page', 'advisers-contacts' ); ?></strong></label><br />
			<?php
			wp_dropdown_pages(
				array(
					'name'        => 'existing_page',
					'id'          => 'existing_page',
					'post_status' => array( 'publish', 'draft', 'private' ),
				)
			);
			?>
			<?php submit_button( __( 'Use This Page', 'advisers-contacts' ), 'primary', 'submit', false ); ?>
		</form>

		<p><?php esc_html_e( 'or', 'advisers-contacts' ); ?></p>

		<form method="post" action="<?php echo esc_url( $post ); ?>">
			<input type="hidden" name="action" value="advc_create_page" />
			<?php wp_nonce_field( 'advc_contact_page', '_advc_nonce' ); ?>
			<?php submit_button( __( 'Create a New Contact Page', 'advisers-contacts' ), 'secondary', 'submit', false ); ?>
		</form>

	<?php else : ?>

		<p>
			<?php
			printf(
				/* translators: %s: page status such as Published or Draft */
				esc_html__( 'The contact form is on this page (status: %s).', 'advisers-contacts' ),
				esc_html( get_post_status_object( $page->post_status ) ? get_post_status_object( $page->post_status )->label : $page->post_status )
			);
			?>
			<?php if ( 'trash' !== $page->post_status ) : ?>
				<a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View the live page →', 'advisers-contacts' ); ?></a>
				|
				<a href="<?php echo esc_url( get_edit_post_link( $page->ID ) ); ?>"><?php esc_html_e( 'Edit in the WordPress editor', 'advisers-contacts' ); ?></a>
			<?php endif; ?>
		</p>

		<?php if ( 'trash' === $page->post_status ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'This page is in the Trash. Restore it from Pages → Trash, or pick another page below.', 'advisers-contacts' ); ?></p></div>
		<?php endif; ?>

		<?php if ( ! get_option( 'permalink_structure' ) ) : ?>
			<div class="notice notice-info inline"><p><?php esc_html_e( 'Your site uses plain permalinks, so the page address ignores the slug. Choose another structure under Settings → Permalinks to use a custom slug.', 'advisers-contacts' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( $post ); ?>">
			<input type="hidden" name="action" value="advc_save_page_settings" />
			<?php wp_nonce_field( 'advc_contact_page', '_advc_nonce' ); ?>
			<table class="form-table">
				<tr>
					<th><label for="page_title"><?php esc_html_e( 'Page Title', 'advisers-contacts' ); ?></label></th>
					<td><input type="text" id="page_title" name="page_title" class="regular-text" value="<?php echo esc_attr( $title ); ?>" required /></td>
				</tr>
				<tr>
					<th><label for="page_slug"><?php esc_html_e( 'Page Slug', 'advisers-contacts' ); ?></label></th>
					<td>
						<code><?php echo esc_html( $prefix ); ?></code><input type="text" id="page_slug" name="page_slug" value="<?php echo esc_attr( $slug ); ?>" required /><code>/</code>
						<p class="description"><?php esc_html_e( 'The last part of the page address, for example "contact" or "get-a-quote". Use lowercase letters, numbers and hyphens.', 'advisers-contacts' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="page_content"><?php esc_html_e( 'Page Content', 'advisers-contacts' ); ?></label></th>
					<td>
						<?php
						wp_editor(
							$content,
							'page_content',
							array(
								'textarea_name' => 'page_content',
								'textarea_rows' => 10,
								'media_buttons' => true,
							)
						);
						?>
						<p class="description"><?php esc_html_e( 'This text appears above the form. The [advisers_contact_form] shortcode is added automatically at the end and always kept on the page.', 'advisers-contacts' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Save Contact Page', 'advisers-contacts' ) ); ?>
		</form>

		<hr />

		<details>
			<summary><strong><?php esc_html_e( 'Use a different page for the form', 'advisers-contacts' ); ?></strong></summary>
			<form method="post" action="<?php echo esc_url( $post ); ?>" class="advc-page-pick">
				<input type="hidden" name="action" value="advc_use_existing_page" />
				<?php wp_nonce_field( 'advc_contact_page', '_advc_nonce' ); ?>
				<?php
				wp_dropdown_pages(
					array(
						'name'        => 'existing_page',
						'id'          => 'existing_page',
						'selected'    => (int) $page_id,
						'post_status' => array( 'publish', 'draft', 'private' ),
					)
				);
				?>
				<?php submit_button( __( 'Use This Page', 'advisers-contacts' ), 'secondary', 'submit', false ); ?>
				<p class="description"><?php esc_html_e( 'The form shortcode is added to the page you choose if it is not there already. The old page is left as it is.', 'advisers-contacts' ); ?></p>
			</form>
		</details>

	<?php endif; ?>
</div>
	<?php
} )();
