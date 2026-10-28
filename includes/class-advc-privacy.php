<?php
/**
 * Privacy integration for Advisers Contacts.
 *
 * Registers suggested privacy policy text and hooks into the WordPress
 * personal data exporter and eraser (Tools -> Export/Erase Personal Data)
 * so that site owners can fulfil GDPR / similar access and erasure requests
 * for data collected through the contact form.
 *
 * @package Advisers_Contacts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ADVC_Privacy
 *
 * @since 1.2.0
 */
class ADVC_Privacy {

	/**
	 * Singleton instance.
	 *
	 * @since 1.2.0
	 * @var ADVC_Privacy|null
	 */
	private static $instance = null;

	/**
	 * Number of submissions handled per exporter/eraser request page.
	 *
	 * @since 1.2.0
	 * @var int
	 */
	const BATCH = 50;

	/**
	 * Get singleton instance.
	 *
	 * @since 1.2.0
	 *
	 * @return ADVC_Privacy
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
	 *
	 * @since 1.2.0
	 */
	private function hooks() {
		add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
	}

	/**
	 * Add suggested text to Settings -> Privacy -> Policy Guide.
	 *
	 * @since 1.2.0
	 */
	public function add_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content  = '<h2>' . esc_html__( 'Contact form submissions', 'advisers-contacts' ) . '</h2>';
		$content .= '<p class="privacy-policy-tutorial">' . esc_html__( 'Advisers Contacts stores the information visitors enter in the contact form in your site database. Review the list below and adjust it to match the fields you have enabled.', 'advisers-contacts' ) . '</p>';
		$content .= '<p><strong class="privacy-policy-tutorial">' . esc_html__( 'Suggested text:', 'advisers-contacts' ) . '</strong> ';
		$content .= esc_html__( 'When you submit our contact form we collect the details you enter (for example your name, email address, mobile number, address and any files you upload), together with your IP address and browser user agent for spam prevention, and information about how you reached our website (such as the campaign, landing page and referring site). This information is stored on our website and used only to respond to your enquiry and to keep a record of it. Uploaded files are stored in our media library. You can ask us to export or erase your data at any time.', 'advisers-contacts' ) . '</p>';

		wp_add_privacy_policy_content( __( 'Advisers Contacts', 'advisers-contacts' ), wp_kses_post( $content ) );
	}

	/**
	 * Register the personal data exporter.
	 *
	 * @since 1.2.0
	 *
	 * @param array $exporters Registered exporters.
	 * @return array
	 */
	public function register_exporter( $exporters ) {
		$exporters['advisers-contacts'] = array(
			'exporter_friendly_name' => __( 'Advisers Contacts submissions', 'advisers-contacts' ),
			'callback'               => array( $this, 'export_personal_data' ),
		);
		return $exporters;
	}

	/**
	 * Register the personal data eraser.
	 *
	 * @since 1.2.0
	 *
	 * @param array $erasers Registered erasers.
	 * @return array
	 */
	public function register_eraser( $erasers ) {
		$erasers['advisers-contacts'] = array(
			'eraser_friendly_name' => __( 'Advisers Contacts submissions', 'advisers-contacts' ),
			'callback'             => array( $this, 'erase_personal_data' ),
		);
		return $erasers;
	}

	/**
	 * Export every submission stored for an email address.
	 *
	 * @since 1.2.0
	 *
	 * @param string $email_address Email address of the data subject.
	 * @param int    $page          1-indexed page of results.
	 * @return array {
	 *     @type array $data Export items.
	 *     @type bool  $done Whether the exporter has finished.
	 * }
	 */
	public function export_personal_data( $email_address, $page = 1 ) {
		$rows = ADVC_DB::get_submissions_by_email( $email_address, self::BATCH, $page );

		$labels = array();
		foreach ( ADVC_Fields::get_fields() as $field ) {
			$labels[ $field->field_key ] = $field->label;
		}

		$items = array();

		foreach ( $rows as $row ) {
			$data = array(
				array( 'name' => __( 'Full Name', 'advisers-contacts' ), 'value' => $row->full_name ),
				array( 'name' => __( 'Mobile Number', 'advisers-contacts' ), 'value' => $row->mobile_number ),
				array( 'name' => __( 'Email ID', 'advisers-contacts' ), 'value' => $row->email ),
				array( 'name' => __( 'Subject', 'advisers-contacts' ), 'value' => $row->subject ),
				array( 'name' => __( 'Referred By', 'advisers-contacts' ), 'value' => $row->referred_by ),
			);

			$extra = $row->field_data ? json_decode( $row->field_data, true ) : array();
			if ( is_array( $extra ) ) {
				foreach ( $extra as $key => $value ) {
					if ( '' === (string) $value ) {
						continue;
					}
					$data[] = array(
						'name'  => isset( $labels[ $key ] ) ? $labels[ $key ] : $key,
						'value' => (string) $value,
					);
				}
			}

			foreach ( ADVC_Helpers::get_attachments( $row->attachment_ids ) as $file ) {
				$data[] = array(
					'name'  => __( 'Uploaded file', 'advisers-contacts' ),
					'value' => $file['url'],
				);
			}

			$source = $row->source_data ? json_decode( $row->source_data, true ) : array();
			if ( is_array( $source ) ) {
				foreach ( $source as $source_key => $source_value ) {
					$data[] = array(
						'name'  => $source_key,
						'value' => (string) $source_value,
					);
				}
			}

			$data[] = array( 'name' => __( 'IP Address', 'advisers-contacts' ), 'value' => $row->ip_address );
			$data[] = array( 'name' => __( 'Browser', 'advisers-contacts' ), 'value' => $row->user_agent );
			$data[] = array( 'name' => __( 'Submitted', 'advisers-contacts' ), 'value' => $row->created_at );

			$items[] = array(
				'group_id'          => 'advisers-contacts',
				'group_label'       => __( 'Contact Form Submissions', 'advisers-contacts' ),
				'group_description' => __( 'Messages submitted through the contact form.', 'advisers-contacts' ),
				'item_id'           => 'advisers-contacts-' . (int) $row->id,
				'data'              => $data,
			);
		}

		return array(
			'data' => $items,
			'done' => count( $rows ) < self::BATCH,
		);
	}

	/**
	 * Erase every submission (and its uploaded files) for an email address.
	 *
	 * Because rows are deleted as we go, the first page is fetched on every
	 * pass instead of advancing the page number.
	 *
	 * @since 1.2.0
	 *
	 * @param string $email_address Email address of the data subject.
	 * @param int    $page          Unused; see method description.
	 * @return array Eraser response as documented in the WordPress core handbook.
	 */
	public function erase_personal_data( $email_address, $page = 1 ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$rows     = ADVC_DB::get_submissions_by_email( $email_address, self::BATCH, 1 );
		$removed  = false;
		$retained = false;
		$messages = array();

		foreach ( $rows as $row ) {
			foreach ( array_filter( explode( ',', (string) $row->attachment_ids ) ) as $attachment_id ) {
				wp_delete_attachment( (int) $attachment_id, true );
			}

			if ( ADVC_DB::delete_submission( (int) $row->id ) ) {
				$removed = true;
			} else {
				$retained = true;
				/* translators: %d: submission ID */
				$messages[] = sprintf( __( 'Contact form submission #%d could not be erased.', 'advisers-contacts' ), (int) $row->id );
			}
		}

		return array(
			'items_removed'  => $removed,
			'items_retained' => $retained,
			'messages'       => $messages,
			// Stop if anything failed so a stuck row cannot loop forever.
			'done'           => count( $rows ) < self::BATCH || $retained,
		);
	}
}
