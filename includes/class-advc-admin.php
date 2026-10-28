<?php
/**
 * wp-admin controller for Advisers Contacts.
 *
 * @package Advisers_Contacts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ADVC_Admin
 * @since 1.0.0
 */
class ADVC_Admin {

	/** @var ADVC_Admin|null */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @since 1.0.0
	 * @return ADVC_Admin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->hooks();
		}
		return self::$instance;
	}

	/**
	 * Register hooks.
	 * @since 1.0.0
	 */
	private function hooks() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		add_action( 'admin_post_advc_change_status', array( $this, 'handle_change_status' ) );
		add_action( 'admin_post_advc_toggle_important', array( $this, 'handle_toggle_important' ) );
		add_action( 'admin_post_advc_delete_submission', array( $this, 'handle_delete_submission' ) );
		add_action( 'admin_post_advc_update_submission', array( $this, 'handle_update_submission' ) );
		add_action( 'admin_post_advc_bulk_action', array( $this, 'handle_bulk_action' ) );
		add_action( 'admin_post_advc_toggle_rewarded', array( $this, 'handle_toggle_rewarded' ) );
		add_action( 'admin_post_advc_save_payout', array( $this, 'handle_save_payout' ) );
		add_action( 'admin_post_advc_change_stage', array( $this, 'handle_change_stage' ) );
		add_action( 'admin_post_advc_export_csv', array( $this, 'handle_export_csv' ) );
		add_action( 'admin_post_advc_apply_preset', array( $this, 'handle_apply_preset' ) );

		add_action( 'admin_post_advc_save_field_order', array( $this, 'handle_save_field_order' ) );
		add_action( 'admin_post_advc_add_field', array( $this, 'handle_add_field' ) );
		add_action( 'admin_post_advc_delete_field', array( $this, 'handle_delete_field' ) );
		add_action( 'admin_post_advc_restore_field', array( $this, 'handle_restore_field' ) );

		add_action( 'admin_post_advc_save_page_settings', array( $this, 'handle_save_page_settings' ) );
		add_action( 'admin_post_advc_use_existing_page', array( $this, 'handle_use_existing_page' ) );
		add_action( 'admin_post_advc_create_page', array( $this, 'handle_create_page' ) );
		add_action( 'admin_post_advc_save_general_settings', array( $this, 'handle_save_general_settings' ) );

		add_action( 'wp_ajax_advc_quick_toggle_important', array( $this, 'ajax_quick_toggle_important' ) );
	}

	/**
	 * Register the admin menu and submenus.
	 * @since 1.0.0
	 */
	public function register_menus() {
		add_menu_page(
			__( 'Advisers Contacts', 'advisers-contacts' ),
			__( 'Advisers Contacts', 'advisers-contacts' ),
			advc_get_capability(),
			'advisers-contacts',
			array( $this, 'render_submissions_screen' ),
			'dashicons-email-alt',
			27
		);

		add_submenu_page( 'advisers-contacts', __( 'Submissions', 'advisers-contacts' ), __( 'Submissions', 'advisers-contacts' ), advc_get_capability(), 'advisers-contacts', array( $this, 'render_submissions_screen' ) );
		add_submenu_page( 'advisers-contacts', __( 'Referrers', 'advisers-contacts' ), __( 'Referrers', 'advisers-contacts' ), advc_get_capability(), 'advc-referrers', array( $this, 'render_referrers_screen' ) );
		add_submenu_page( 'advisers-contacts', __( 'Field Builder', 'advisers-contacts' ), __( 'Field Builder', 'advisers-contacts' ), advc_get_capability(), 'advc-field-builder', array( $this, 'render_field_builder_screen' ) );
		add_submenu_page( 'advisers-contacts', __( 'Contact Page', 'advisers-contacts' ), __( 'Contact Page', 'advisers-contacts' ), advc_get_capability(), 'advc-contact-page', array( $this, 'render_contact_page_screen' ) );
		add_submenu_page( 'advisers-contacts', __( 'Settings', 'advisers-contacts' ), __( 'Settings', 'advisers-contacts' ), advc_get_capability(), 'advc-settings', array( $this, 'render_settings_screen' ) );

		// Hidden detail screen, reached only via a row link (not shown in the menu).
		// An empty parent slug is the PHP 8.1-safe way to register it (null is deprecated).
		add_submenu_page( '', __( 'Submission Details', 'advisers-contacts' ), '', advc_get_capability(), 'advc-view-submission', array( $this, 'render_view_submission_screen' ) );
	}

	/**
	 * Enqueue admin CSS/JS only on the plugin's own screens.
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'advisers-contacts' ) === false && strpos( $hook, 'advc-' ) === false ) {
			return;
		}

		wp_enqueue_style( 'advc-admin', ADVC_URL . 'assets/css/admin.css', array(), ADVC_VERSION );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'advc-admin', ADVC_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable' ), ADVC_VERSION, true );
		wp_localize_script(
			'advc-admin',
			'advcAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'advc_admin_ajax' ),
				'listUrl' => admin_url( 'admin.php?page=advisers-contacts' ),
				'i18n'    => array(
					'confirmPreset' => __( 'Switch to the Lead Generation layout? Its fields are enabled and ordered first, and the other built-in fields are switched off (not deleted).', 'advisers-contacts' ),
					'confirmDelete' => __( 'Delete this submission? This cannot be undone.', 'advisers-contacts' ),
					'confirmField'  => __( 'Delete this field? This cannot be undone.', 'advisers-contacts' ),
				),
			)
		);
	}

	/* -----------------------------------------------------------------
	 * Screens
	 * -------------------------------------------------------------- */

	/**
	 * Submissions dashboard (WP_List_Table).
	 * @since 1.0.0
	 */
	public function render_submissions_screen() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'advisers-contacts' ) );
		}
		require ADVC_DIR . 'includes/views/view-submissions.php';
	}

	/**
	 * Single submission detail/edit screen.
	 * @since 1.0.0
	 */
	public function render_view_submission_screen() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'advisers-contacts' ) );
		}
		$id         = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$submission = $id ? ADVC_DB::get_submission( $id ) : null;

		if ( ! $submission ) {
			echo '<div class="wrap"><h1>' . esc_html__( 'Submission not found', 'advisers-contacts' ) . '</h1></div>';
			return;
		}

		require ADVC_DIR . 'includes/views/view-submission-detail.php';
	}

	/**
	 * Referrers overview + drill-down.
	 * @since 1.0.0
	 */
	public function render_referrers_screen() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'advisers-contacts' ) );
		}
		require ADVC_DIR . 'includes/views/view-referrers.php';
	}

	/**
	 * Drag-drop field builder.
	 * @since 1.0.0
	 */
	public function render_field_builder_screen() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'advisers-contacts' ) );
		}
		require ADVC_DIR . 'includes/views/view-field-builder.php';
	}

	/**
	 * Contact Us page settings.
	 * @since 1.0.0
	 */
	public function render_contact_page_screen() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'advisers-contacts' ) );
		}
		require ADVC_DIR . 'includes/views/view-contact-page.php';
	}

	/**
	 * General plugin settings.
	 * @since 1.0.0
	 */
	public function render_settings_screen() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'advisers-contacts' ) );
		}
		require ADVC_DIR . 'includes/views/view-settings.php';
	}

	/* -----------------------------------------------------------------
	 * admin-post.php handlers (form posts with full page reload)
	 * -------------------------------------------------------------- */

	/**
	 * Change a single submission's status (approve/reject/pending).
	 * @since 1.0.0
	 */
	public function handle_change_status() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'advisers-contacts' ) );
		}
		check_admin_referer( 'advc_submissions_action', '_advc_nonce' );
		$id     = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';

		if ( $id && in_array( $status, array( 'pending', 'approved', 'rejected' ), true ) ) {
			ADVC_DB::update_submission( $id, array( 'status' => $status ) );
		}

		$this->redirect_with_notice( wp_get_referer(), 'status_updated' );
	}

	/**
	 * Toggle the "important" flag.
	 * @since 1.0.0
	 */
	public function handle_toggle_important() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'advisers-contacts' ) );
		}
		check_admin_referer( 'advc_submissions_action', '_advc_nonce' );
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( $id ) {
			$submission = ADVC_DB::get_submission( $id );
			if ( $submission ) {
				ADVC_DB::update_submission( $id, array( 'is_important' => $submission->is_important ? 0 : 1 ) );
			}
		}

		$this->redirect_with_notice( wp_get_referer(), 'important_updated' );
	}

	/**
	 * Delete a submission.
	 * @since 1.0.0
	 */
	public function handle_delete_submission() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'advisers-contacts' ) );
		}
		check_admin_referer( 'advc_submissions_action', '_advc_nonce' );
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( $id ) {
			ADVC_DB::delete_submission( $id );
		}

		$url = admin_url( 'admin.php?page=advisers-contacts' );
		$this->redirect_with_notice( $url, 'deleted' );
	}

	/**
	 * Save edits made on the submission detail screen.
	 * @since 1.0.0
	 */
	public function handle_update_submission() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'advisers-contacts' ) );
		}
		check_admin_referer( 'advc_submissions_action', '_advc_nonce' );
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( ! $id ) {
			wp_die( esc_html__( 'Invalid submission.', 'advisers-contacts' ) );
		}

		$fields = ADVC_Fields::get_fields();
		$core   = array();
		$extra  = array();

		foreach ( $fields as $field ) {
			if ( in_array( $field->type, array( 'submit', 'file' ), true ) ) {
				continue;
			}
			$raw = isset( $_POST[ 'advc_field_' . $field->field_key ] ) ? wp_unslash( $_POST[ 'advc_field_' . $field->field_key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by type in ADVC_Helpers::sanitize_field_value() below; nonce verified above.
			$val = ADVC_Helpers::sanitize_field_value( $field->type, $raw );

			if ( in_array( $field->field_key, array( 'full_name', 'mobile_number', 'email', 'subject', 'referred_by' ), true ) ) {
				$core[ $field->field_key ] = $val;
			} else {
				$extra[ $field->field_key ] = $val;
			}
		}

		$existing               = ADVC_DB::get_submission( $id );
		$existing_extra         = $existing && $existing->field_data ? json_decode( $existing->field_data, true ) : array();
		$merged_extra           = array_merge( (array) $existing_extra, $extra );
		$core['field_data']     = wp_json_encode( $merged_extra );
		$core['lead_score']     = ADVC_Helpers::compute_lead_score( $core, $merged_extra );

		ADVC_DB::update_submission( $id, $core );

		$url = admin_url( 'admin.php?page=advc-view-submission&id=' . $id );
		$this->redirect_with_notice( $url, 'saved' );
	}

	/**
	 * Bulk actions from the submissions list table.
	 * @since 1.0.0
	 */
	public function handle_bulk_action() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'advisers-contacts' ) );
		}
		check_admin_referer( 'advc_submissions_action', '_advc_nonce' );

		$ids    = isset( $_POST['ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['ids'] ) ) : array();
		$action = isset( $_POST['bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['bulk_action'] ) ) : '';

		$stages = ADVC_Helpers::stages();

		foreach ( $ids as $id ) {
			if ( 0 === strpos( $action, 'stage_' ) && isset( $stages[ substr( $action, 6 ) ] ) ) {
				ADVC_DB::update_submission( $id, array( 'lead_stage' => substr( $action, 6 ) ) );
				continue;
			}

			switch ( $action ) {
				case 'approve':
					ADVC_DB::update_submission( $id, array( 'status' => 'approved' ) );
					break;
				case 'reject':
					ADVC_DB::update_submission( $id, array( 'status' => 'rejected' ) );
					break;
				case 'rewarded':
					ADVC_DB::set_rewarded( $id, true );
					break;
				case 'unrewarded':
					ADVC_DB::set_rewarded( $id, false );
					break;
				case 'important':
					ADVC_DB::update_submission( $id, array( 'is_important' => 1 ) );
					break;
				case 'unimportant':
					ADVC_DB::update_submission( $id, array( 'is_important' => 0 ) );
					break;
				case 'delete':
					ADVC_DB::delete_submission( $id );
					break;
			}
		}

		$this->redirect_with_notice( wp_get_referer(), 'bulk_done' );
	}

	/**
	 * Mark one lead as rewarded or not rewarded.
	 *
	 * @since 1.4.0
	 */
	public function handle_toggle_rewarded() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'advisers-contacts' ) );
		}
		check_admin_referer( 'advc_submissions_action', '_advc_nonce' );

		$id     = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$reward = ! empty( $_POST['rewarded'] );

		if ( $id && ADVC_DB::get_submission( $id ) ) {
			ADVC_DB::set_rewarded( $id, $reward );
		}

		$this->redirect_with_notice( wp_get_referer(), 'reward_updated' );
	}

	/**
	 * Save the payout details typed in for a referrer.
	 *
	 * @since 1.4.0
	 */
	public function handle_save_payout() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'advisers-contacts' ) );
		}
		check_admin_referer( 'advc_referrers', '_advc_nonce' );

		$referrer = isset( $_POST['referrer'] ) ? sanitize_text_field( wp_unslash( $_POST['referrer'] ) ) : '';
		$details  = isset( $_POST['payout_details'] ) ? substr( sanitize_textarea_field( wp_unslash( $_POST['payout_details'] ) ), 0, 1000 ) : '';

		if ( '' !== $referrer ) {
			ADVC_DB::save_payout_details( $referrer, $details );
		}

		$url = add_query_arg(
			array(
				'page'     => 'advc-referrers',
				'referrer' => rawurlencode( $referrer ),
			),
			admin_url( 'admin.php' )
		);
		$this->redirect_with_notice( $url, 'payout_saved' );
	}

	/**
	 * Move a lead to another pipeline stage.
	 *
	 * @since 1.3.0
	 */
	public function handle_change_stage() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'advisers-contacts' ) );
		}
		check_admin_referer( 'advc_submissions_action', '_advc_nonce' );

		$id    = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$stage = isset( $_POST['lead_stage'] ) ? sanitize_key( wp_unslash( $_POST['lead_stage'] ) ) : '';

		if ( $id && array_key_exists( $stage, ADVC_Helpers::stages() ) ) {
			ADVC_DB::update_submission( $id, array( 'lead_stage' => $stage ) );
		}

		$this->redirect_with_notice( wp_get_referer(), 'saved' );
	}

	/**
	 * Switch the form to the ready-made lead-generation layout.
	 *
	 * @since 1.3.0
	 */
	public function handle_apply_preset() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'advisers-contacts' ) );
		}
		check_admin_referer( 'advc_field_builder', '_advc_nonce' );

		ADVC_Fields::apply_lead_preset();

		$this->redirect_with_notice( admin_url( 'admin.php?page=advc-field-builder' ), 'preset_applied' );
	}

	/**
	 * Download the submissions that match the current filters as a CSV file.
	 *
	 * Cells that start with a spreadsheet formula character are prefixed with
	 * an apostrophe so a malicious submission cannot run a formula in Excel.
	 *
	 * @since 1.3.0
	 */
	public function handle_export_csv() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'advisers-contacts' ) );
		}
		check_admin_referer( 'advc_export' );

		$args = array(
			'search'    => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			'status'    => isset( $_GET['advc_status'] ) ? sanitize_key( wp_unslash( $_GET['advc_status'] ) ) : '',
			'stage'     => isset( $_GET['advc_stage'] ) ? sanitize_key( wp_unslash( $_GET['advc_stage'] ) ) : '',
			'rewarded'  => isset( $_GET['advc_rewarded'] ) ? sanitize_key( wp_unslash( $_GET['advc_rewarded'] ) ) : '',
			'referrer'  => isset( $_GET['advc_referrer'] ) ? sanitize_text_field( wp_unslash( $_GET['advc_referrer'] ) ) : '',
			'date_from' => isset( $_GET['advc_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['advc_date_from'] ) ) : '',
			'date_to'   => isset( $_GET['advc_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['advc_date_to'] ) ) : '',
			'orderby'   => 'created_at',
			'order'     => 'DESC',
			'per_page'  => 500,
		);

		$temperature = isset( $_GET['advc_temp'] ) ? sanitize_key( wp_unslash( $_GET['advc_temp'] ) ) : '';
		$temps       = ADVC_Helpers::temperatures();
		if ( isset( $temps[ $temperature ] ) ) {
			$args['score_min'] = $temps[ $temperature ][1];
			$args['score_max'] = $temps[ $temperature ][2];
		}

		$fields = array();
		foreach ( ADVC_Fields::get_fields() as $field ) {
			if ( ! in_array( $field->type, array( 'submit', 'file' ), true ) ) {
				$fields[] = $field;
			}
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="advisers-contacts-' . gmdate( 'Y-m-d' ) . '.csv"' );

		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Streaming the download to the browser, not touching the filesystem.
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- UTF-8 byte order mark so Excel reads accents correctly.

		$header = array( 'ID', 'Date', 'Status', 'Lead Stage', 'Lead Score', 'Lead Temperature' );
		foreach ( $fields as $field ) {
			$header[] = $field->label;
		}
		foreach ( ADVC_Helpers::tracking_keys() as $key ) {
			$header[] = $key;
		}
		$header[] = 'Attachments';
		$header[] = 'Rewarded';
		$header[] = 'Referrer Payout Details';
		fputcsv( $out, $header );

		$stages = ADVC_Helpers::stages();
		$paged  = 1;

		do {
			$args['paged'] = $paged;
			$result        = ADVC_DB::query_submissions( $args );

			foreach ( $result['items'] as $row ) {
				$extra  = $row->field_data ? json_decode( $row->field_data, true ) : array();
				$source = $row->source_data ? json_decode( $row->source_data, true ) : array();
				$extra  = is_array( $extra ) ? $extra : array();
				$source = is_array( $source ) ? $source : array();

				$line = array(
					$row->id,
					$row->created_at,
					$row->status,
					isset( $stages[ $row->lead_stage ] ) ? $stages[ $row->lead_stage ] : $row->lead_stage,
					$row->lead_score,
					ADVC_Helpers::temperature( $row->lead_score ),
				);
				foreach ( $fields as $field ) {
					$line[] = isset( $row->{$field->field_key} ) ? $row->{$field->field_key} : ( isset( $extra[ $field->field_key ] ) ? $extra[ $field->field_key ] : '' );
				}
				foreach ( ADVC_Helpers::tracking_keys() as $key ) {
					$line[] = isset( $source[ $key ] ) ? $source[ $key ] : '';
				}
				$urls = array();
				foreach ( ADVC_Helpers::get_attachments( $row->attachment_ids ) as $file ) {
					$urls[] = $file['url'];
				}
				$line[] = implode( ' ', $urls );
				$line[] = $row->is_rewarded ? 'Yes' : 'No';
				$line[] = $row->referred_by ? ADVC_DB::get_payout_details( $row->referred_by ) : '';

				fputcsv( $out, array_map( array( $this, 'csv_safe' ), $line ) );
			}

			++$paged;
		} while ( count( $result['items'] ) === $args['per_page'] );

		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing the output stream opened above.
		exit;
	}

	/**
	 * Neutralise spreadsheet formula injection in one CSV cell.
	 *
	 * @since 1.3.0
	 *
	 * @param mixed $value Cell value.
	 * @return string
	 */
	private function csv_safe( $value ) {
		$value = (string) $value;
		if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			return "'" . $value;
		}
		return $value;
	}

	/**
	 * Save drag-drop order + enabled/required toggles from the field builder.
	 * @since 1.0.0
	 */
	public function handle_save_field_order() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'advisers-contacts' ) );
		}
		check_admin_referer( 'advc_field_builder', '_advc_nonce' );

		$rows = isset( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Decoded and every value sanitized in ADVC_Fields::save_order(); nonce verified above.
		if ( is_string( $rows ) ) {
			$rows = json_decode( $rows, true );
		}

		if ( is_array( $rows ) ) {
			ADVC_Fields::save_order( $rows );
		}

		$url = admin_url( 'admin.php?page=advc-field-builder' );
		$this->redirect_with_notice( $url, 'fields_saved' );
	}

	/**
	 * Add a new custom field.
	 * @since 1.0.0
	 */
	public function handle_add_field() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'advisers-contacts' ) );
		}
		check_admin_referer( 'advc_field_builder', '_advc_nonce' );

		$result = ADVC_Fields::add_field(
			array(
				'label'       => isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '',
				'type'        => isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'text',
				'options'     => isset( $_POST['options'] ) ? sanitize_textarea_field( wp_unslash( $_POST['options'] ) ) : '',
				'placeholder' => isset( $_POST['placeholder'] ) ? sanitize_text_field( wp_unslash( $_POST['placeholder'] ) ) : '',
				'help_text'   => isset( $_POST['help_text'] ) ? sanitize_text_field( wp_unslash( $_POST['help_text'] ) ) : '',
				'width'       => isset( $_POST['width'] ) ? sanitize_key( wp_unslash( $_POST['width'] ) ) : 'full',
				'required'    => ! empty( $_POST['required'] ),
			)
		);

		$url = admin_url( 'admin.php?page=advc-field-builder' );
		$this->redirect_with_notice( $url, is_wp_error( $result ) ? 'error' : 'field_added' );
	}

	/**
	 * Delete any field (default or custom) from the form.
	 * @since 1.0.0
	 */
	public function handle_delete_field() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'advisers-contacts' ) );
		}
		check_admin_referer( 'advc_field_builder', '_advc_nonce' );

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		ADVC_Fields::delete_field( $id );

		$url = admin_url( 'admin.php?page=advc-field-builder' );
		$this->redirect_with_notice( $url, 'field_deleted' );
	}

	/**
	 * Restore a previously deleted default field.
	 *
	 * @since 1.1.0
	 */
	public function handle_restore_field() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'advisers-contacts' ) );
		}
		check_admin_referer( 'advc_field_builder', '_advc_nonce' );

		$key    = isset( $_POST['field_key'] ) ? sanitize_key( wp_unslash( $_POST['field_key'] ) ) : '';
		$result = $key ? ADVC_Fields::restore_field( $key ) : new WP_Error( 'advc_missing_key', __( 'Missing field key.', 'advisers-contacts' ) );

		$url = admin_url( 'admin.php?page=advc-field-builder' );
		$this->redirect_with_notice( $url, is_wp_error( $result ) ? 'error' : 'field_restored' );
	}

	/**
	 * Save Contact Us page settings.
	 * @since 1.0.0
	 */
	public function handle_save_page_settings() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'advisers-contacts' ) );
		}
		check_admin_referer( 'advc_contact_page', '_advc_nonce' );

		$requested = isset( $_POST['page_slug'] ) ? sanitize_title( wp_unslash( $_POST['page_slug'] ) ) : '';

		$saved = ADVC_Page::update_page(
			isset( $_POST['page_title'] ) ? sanitize_text_field( wp_unslash( $_POST['page_title'] ) ) : '',
			$requested,
			isset( $_POST['page_content'] ) ? wp_kses_post( wp_unslash( $_POST['page_content'] ) ) : ''
		);

		if ( false === $saved ) {
			$notice = 'page_error';
		} elseif ( '' !== $requested && $saved !== $requested ) {
			$notice = 'slug_adjusted';
		} else {
			$notice = 'page_saved';
		}

		$this->redirect_with_notice( admin_url( 'admin.php?page=advc-contact-page' ), $notice );
	}

	/**
	 * Use an existing page as the contact page.
	 *
	 * @since 1.4.1
	 */
	public function handle_use_existing_page() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'advisers-contacts' ) );
		}
		check_admin_referer( 'advc_contact_page', '_advc_nonce' );

		$page_id = isset( $_POST['existing_page'] ) ? absint( $_POST['existing_page'] ) : 0;
		$ok      = $page_id && ADVC_Page::use_existing_page( $page_id );

		$this->redirect_with_notice( admin_url( 'admin.php?page=advc-contact-page' ), $ok ? 'page_linked' : 'page_error' );
	}

	/**
	 * Create a fresh contact page on request.
	 *
	 * @since 1.4.1
	 */
	public function handle_create_page() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'advisers-contacts' ) );
		}
		check_admin_referer( 'advc_contact_page', '_advc_nonce' );

		$ok = current_user_can( 'publish_pages' ) && ADVC_Page::create_page();

		$this->redirect_with_notice( admin_url( 'admin.php?page=advc-contact-page' ), $ok ? 'page_created' : 'page_error' );
	}

	/**
	 * Save general settings.
	 * @since 1.0.0
	 */
	public function handle_save_general_settings() {
		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'advisers-contacts' ) );
		}
		check_admin_referer( 'advc_settings', '_advc_nonce' );

		update_option( 'advc_default_country_code', isset( $_POST['default_country_code'] ) ? preg_replace( '/\D+/', '', sanitize_text_field( wp_unslash( $_POST['default_country_code'] ) ) ) : '91' ); // Digits only, e.g. 91.
		update_option( 'advc_per_page', isset( $_POST['per_page'] ) ? max( 5, absint( wp_unslash( $_POST['per_page'] ) ) ) : 20 );

		update_option( 'advc_form_title', isset( $_POST['form_title'] ) ? sanitize_text_field( wp_unslash( $_POST['form_title'] ) ) : '' );
		update_option( 'advc_form_intro', isset( $_POST['form_intro'] ) ? sanitize_textarea_field( wp_unslash( $_POST['form_intro'] ) ) : '' );
		update_option( 'advc_success_message', isset( $_POST['success_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['success_message'] ) ) : '' );

		$theme = isset( $_POST['theme'] ) ? sanitize_key( wp_unslash( $_POST['theme'] ) ) : 'blue';
		update_option( 'advc_theme', array_key_exists( $theme, ADVC_Helpers::themes() ) ? $theme : 'blue' );

		if ( isset( $_POST['services'] ) ) {
			ADVC_Fields::set_options( 'service_interested', sanitize_textarea_field( wp_unslash( $_POST['services'] ) ) );
		}

		update_option( 'advc_share_enabled', empty( $_POST['share_enabled'] ) ? '' : '1' );
		update_option( 'advc_notify_enabled', empty( $_POST['notify_enabled'] ) ? '' : '1' );
		update_option( 'advc_notify_email', isset( $_POST['notify_email'] ) ? sanitize_email( wp_unslash( $_POST['notify_email'] ) ) : '' );
		update_option( 'advc_autoreply_enabled', empty( $_POST['autoreply_enabled'] ) ? '' : '1' );
		update_option( 'advc_autoreply_subject', isset( $_POST['autoreply_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['autoreply_subject'] ) ) : '' );
		update_option( 'advc_autoreply_body', isset( $_POST['autoreply_body'] ) ? sanitize_textarea_field( wp_unslash( $_POST['autoreply_body'] ) ) : '' );

		$url = admin_url( 'admin.php?page=advc-settings' );
		$this->redirect_with_notice( $url, 'settings_saved' );
	}

	/* -----------------------------------------------------------------
	 * AJAX
	 * -------------------------------------------------------------- */

	/**
	 * Quick "mark important" star toggle from the list table, no reload.
	 * @since 1.0.0
	 */
	public function ajax_quick_toggle_important() {
		check_ajax_referer( 'advc_admin_ajax', 'nonce' );

		if ( ! current_user_can( advc_get_capability() ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'advisers-contacts' ) ), 403 );
		}

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$submission = $id ? ADVC_DB::get_submission( $id ) : null;

		if ( ! $submission ) {
			wp_send_json_error( array( 'message' => __( 'Submission not found.', 'advisers-contacts' ) ), 404 );
		}

		$new_value = $submission->is_important ? 0 : 1;
		ADVC_DB::update_submission( $id, array( 'is_important' => $new_value ) );

		wp_send_json_success( array( 'important' => (bool) $new_value ) );
	}

	/* -----------------------------------------------------------------
	 * Internal helpers
	 * -------------------------------------------------------------- */

	/**
	 * Redirect back to a wp-admin screen with a notice query arg.
	 *
	 * @since 1.0.0
	 * @param string $url    Target URL.
	 * @param string $notice Notice key.
	 */
	private function redirect_with_notice( $url, $notice ) {
		$url = $url ? $url : admin_url( 'admin.php?page=advisers-contacts' );
		wp_safe_redirect( add_query_arg( 'advc_notice', $notice, $url ) );
		exit;
	}
}
