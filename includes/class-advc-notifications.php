<?php
/**
 * Email notifications for Advisers Contacts.
 *
 * Sends the site owner an email for every new lead and, when enabled,
 * a confirmation email to the visitor.
 *
 * @package Advisers_Contacts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ADVC_Notifications
 *
 * @since 1.3.0
 */
class ADVC_Notifications {

	/**
	 * Singleton instance.
	 *
	 * @since 1.3.0
	 * @var ADVC_Notifications|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @since 1.3.0
	 *
	 * @return ADVC_Notifications
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			add_action( 'advc_after_submission', array( self::$instance, 'send' ), 10, 2 );
		}
		return self::$instance;
	}

	/**
	 * Send the owner notification and the optional visitor auto-reply.
	 *
	 * @since 1.3.0
	 *
	 * @param int   $id   New submission ID.
	 * @param array $data Stored submission data.
	 */
	public function send( $id, $data ) {
		if ( get_option( 'advc_notify_enabled', '1' ) ) {
			$this->send_owner_email( (int) $id, (array) $data );
		}

		if ( get_option( 'advc_autoreply_enabled', '' ) ) {
			$this->send_autoreply( (array) $data );
		}
	}

	/**
	 * Email the site owner the details of a new lead.
	 *
	 * @since 1.3.0
	 *
	 * @param int   $id   Submission ID.
	 * @param array $data Stored submission data.
	 */
	private function send_owner_email( $id, $data ) {
		// An empty setting means "use the site admin address".
		$to = sanitize_email( get_option( 'advc_notify_email', '' ) );
		if ( '' === $to ) {
			$to = sanitize_email( get_option( 'admin_email' ) );
		}
		if ( ! is_email( $to ) ) {
			return;
		}

		$name  = isset( $data['full_name'] ) ? $data['full_name'] : '';
		$score = isset( $data['lead_score'] ) ? (int) $data['lead_score'] : 0;
		$temps = ADVC_Helpers::temperatures();
		$temp  = $temps[ ADVC_Helpers::temperature( $score ) ][0];

		/* translators: 1: lead temperature (Hot/Warm/Cold), 2: visitor name */
		$subject = sprintf( __( '[%1$s lead] New enquiry from %2$s', 'advisers-contacts' ), $temp, $name ? $name : __( 'a visitor', 'advisers-contacts' ) );

		$lines   = array();
		$form_name = get_option( 'advc_form_title', ADVC_Helpers::default_text( 'title' ) );
		if ( '' !== (string) $form_name ) {
			/* translators: %s: form name */
			$lines[] = sprintf( __( 'A new submission was received on the form "%s".', 'advisers-contacts' ), $form_name );
		} else {
			$lines[] = __( 'A new contact form submission was received.', 'advisers-contacts' );
		}
		$lines[] = '';
		/* translators: 1: score out of 100, 2: Hot/Warm/Cold */
		$lines[] = sprintf( __( 'Lead score: %1$d / 100 (%2$s)', 'advisers-contacts' ), $score, $temp );
		$lines[] = '';

		$extra = ! empty( $data['field_data'] ) ? json_decode( $data['field_data'], true ) : array();
		$extra = is_array( $extra ) ? $extra : array();

		foreach ( ADVC_Fields::get_fields() as $field ) {
			if ( in_array( $field->type, array( 'submit', 'file' ), true ) ) {
				continue;
			}
			$value = isset( $data[ $field->field_key ] ) ? $data[ $field->field_key ] : ( isset( $extra[ $field->field_key ] ) ? $extra[ $field->field_key ] : '' );
			if ( '' !== (string) $value ) {
				$lines[] = $field->label . ': ' . $value;
			}
		}

		$source = ! empty( $data['source_data'] ) ? json_decode( $data['source_data'], true ) : array();
		if ( is_array( $source ) && $source ) {
			$lines[] = '';
			$lines[] = __( 'Marketing source', 'advisers-contacts' );
			foreach ( $source as $key => $value ) {
				$lines[] = $key . ': ' . $value;
			}
		}

		$lines[] = '';
		$lines[] = __( 'View this lead:', 'advisers-contacts' ) . ' ' . admin_url( 'admin.php?page=advc-view-submission&id=' . $id );

		$headers = array();
		if ( ! empty( $data['email'] ) && is_email( $data['email'] ) ) {
			$headers[] = 'Reply-To: ' . sanitize_email( $data['email'] );
		}

		wp_mail( $to, $this->clean_header( $subject ), implode( "\n", $lines ), $headers );
	}

	/**
	 * Email the visitor a confirmation.
	 *
	 * @since 1.3.0
	 *
	 * @param array $data Stored submission data.
	 */
	private function send_autoreply( $data ) {
		if ( empty( $data['email'] ) || ! is_email( $data['email'] ) ) {
			return;
		}

		$replace = array(
			'{name}'      => ! empty( $data['full_name'] ) ? $data['full_name'] : __( 'there', 'advisers-contacts' ),
			'{site_name}' => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		);

		$subject = get_option( 'advc_autoreply_subject', ADVC_Helpers::default_text( 'autoreply_subject' ) );
		$body    = get_option( 'advc_autoreply_body', ADVC_Helpers::default_text( 'autoreply_body' ) );

		wp_mail(
			sanitize_email( $data['email'] ),
			$this->clean_header( strtr( $subject, $replace ) ),
			strtr( $body, $replace )
		);
	}

	/**
	 * Strip line breaks so user-supplied text can never inject mail headers.
	 *
	 * @since 1.3.0
	 *
	 * @param string $text Header text.
	 * @return string
	 */
	private function clean_header( $text ) {
		return trim( preg_replace( '/[\r\n]+/', ' ', (string) $text ) );
	}
}
