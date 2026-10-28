<?php
/**
 * Static helper utilities for Advisers Contacts.
 *
 * @package Advisers_Contacts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ADVC_Helpers
 * @since 1.0.0
 */
class ADVC_Helpers {

	/**
	 * Build a click-to-chat WhatsApp URL from a raw mobile number.
	 *
	 * Strips everything but digits, then prepends the configured default
	 * country code only if the number looks too short to already contain
	 * one (fewer digits than the country code + a typical local number).
	 *
	 * @since 1.0.0
	 * @param string $mobile_number Raw mobile number as submitted.
	 * @return string Empty string if no usable digits are found.
	 */
	public static function whatsapp_url( $mobile_number ) {
		$digits = preg_replace( '/\D+/', '', (string) $mobile_number );
		if ( '' === $digits ) {
			return '';
		}

		$default_cc = preg_replace( '/\D+/', '', (string) get_option( 'advc_default_country_code', '91' ) );

		if ( $default_cc && strlen( $digits ) <= 10 ) {
			$digits = $default_cc . $digits;
		}

		return 'https://wa.me/' . $digits;
	}

	/**
	 * Build a mailto: URL from a raw email address.
	 *
	 * @since 1.0.0
	 * @param string $email Email address.
	 * @return string Empty string if not a valid email.
	 */
	public static function mailto_url( $email ) {
		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return '';
		}
		return 'mailto:' . antispambot( $email );
	}

	/**
	 * Human-readable, translated status labels.
	 *
	 * @since 1.0.0
	 * @return array status_key => label
	 */
	public static function statuses() {
		return array(
			'pending'  => __( 'Pending', 'advisers-contacts' ),
			'approved' => __( 'Approved', 'advisers-contacts' ),
			'rejected' => __( 'Rejected', 'advisers-contacts' ),
		);
	}

	/**
	 * CSS class used for the colored status badge in wp-admin.
	 *
	 * @since 1.0.0
	 * @param string $status Status key.
	 * @return string
	 */
	public static function status_badge_class( $status ) {
		$map = array(
			'pending'  => 'advc-badge-pending',
			'approved' => 'advc-badge-approved',
			'rejected' => 'advc-badge-rejected',
		);
		return isset( $map[ $status ] ) ? $map[ $status ] : 'advc-badge-pending';
	}

	/**
	 * Sanitize a submitted value according to a field's declared type.
	 *
	 * @since 1.0.0
	 * @param string $type  Field type (text, email, tel, url, date, textarea, select, radio, checkbox).
	 * @param mixed  $value Raw value.
	 * @return string
	 */
	public static function sanitize_field_value( $type, $value ) {
		// Callers pass request data they have already run through wp_unslash().
		// Nested arrays are never valid for a single field, so drop them.
		if ( ! is_scalar( $value ) ) {
			return '';
		}
		$value = (string) $value;

		switch ( $type ) {
			case 'email':
				return sanitize_email( $value );
			case 'url':
				return esc_url_raw( $value );
			case 'textarea':
				return sanitize_textarea_field( $value );
			case 'tel':
				return preg_replace( '/[^0-9+\-\s()]/', '', $value );
			case 'checkbox':
				return '' !== (string) $value && '0' !== (string) $value ? 'Yes' : '';
			case 'date':
				$value = sanitize_text_field( $value );
				return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Get the display value + a viewable URL for each attachment ID stored
	 * against a submission.
	 *
	 * @since 1.0.0
	 * @param string $attachment_ids Comma-separated attachment IDs.
	 * @return array[] { id, title, url }
	 */
	public static function get_attachments( $attachment_ids ) {
		$out = array();
		if ( empty( $attachment_ids ) ) {
			return $out;
		}

		foreach ( array_filter( explode( ',', $attachment_ids ) ) as $id ) {
			$id  = (int) $id;
			$url = wp_get_attachment_url( $id );
			if ( ! $url ) {
				continue;
			}
			$out[] = array(
				'id'    => $id,
				'title' => get_the_title( $id ),
				'url'   => $url,
			);
		}

		return $out;
	}

	/**
	 * Lead pipeline stages.
	 *
	 * @since 1.3.0
	 * @return array stage_key => label
	 */
	public static function stages() {
		return array(
			'new'       => __( 'New', 'advisers-contacts' ),
			'contacted' => __( 'Contacted', 'advisers-contacts' ),
			'qualified' => __( 'Qualified', 'advisers-contacts' ),
			'won'       => __( 'Won', 'advisers-contacts' ),
			'lost'      => __( 'Lost', 'advisers-contacts' ),
		);
	}

	/**
	 * Lead temperature bands.
	 *
	 * @since 1.3.0
	 * @return array key => array( label, min, max ) (max -1 means no upper limit).
	 */
	public static function temperatures() {
		return array(
			'hot'  => array( __( 'Hot', 'advisers-contacts' ), 70, -1 ),
			'warm' => array( __( 'Warm', 'advisers-contacts' ), 40, 69 ),
			'cold' => array( __( 'Cold', 'advisers-contacts' ), 0, 39 ),
		);
	}

	/**
	 * Temperature key (hot|warm|cold) for a lead score.
	 *
	 * @since 1.3.0
	 * @param int $score Lead score 0-100.
	 * @return string
	 */
	public static function temperature( $score ) {
		$score = (int) $score;
		if ( $score >= 70 ) {
			return 'hot';
		}
		return $score >= 40 ? 'warm' : 'cold';
	}

	/**
	 * Position of a chosen option inside a select/radio field's option list,
	 * as a 0-1 fraction (first option 0, last option 1). Returns -1 when the
	 * field or value is missing.
	 *
	 * @since 1.3.0
	 * @param string $field_key Field key.
	 * @param string $value     Chosen value.
	 * @return float
	 */
	private static function option_fraction( $field_key, $value ) {
		$field = ADVC_Fields::get_field( $field_key );
		if ( ! $field || '' === (string) $value || ! $field->options ) {
			return -1;
		}
		$options = array_values( (array) json_decode( $field->options, true ) );
		$index   = array_search( $value, $options, true );
		if ( false === $index || count( $options ) < 2 ) {
			return -1;
		}
		return $index / ( count( $options ) - 1 );
	}

	/**
	 * Score a lead from 0 to 100.
	 *
	 * Points: valid email 10, phone 15, company 10, service chosen 5, a
	 * message of 50+ characters 10, budget up to 25 (later options in the
	 * budget list score higher), timeline up to 25 (earlier options in the
	 * timeline list score higher). Developers can replace the result with the
	 * `advc_lead_score` filter.
	 *
	 * @since 1.3.0
	 * @param array $core  Core column values (email, mobile_number ...).
	 * @param array $extra Values of the non-core fields, keyed by field key.
	 * @return int
	 */
	public static function compute_lead_score( $core, $extra ) {
		$score = 0;
		// Core columns win if a key exists in both arrays.
		$all = array_merge( (array) $extra, (array) $core );

		// Contactability first: an email or phone number is what lets you follow up.

		if ( ! empty( $all['email'] ) && is_email( $all['email'] ) ) {
			$score += 10;
		}
		if ( ! empty( $all['mobile_number'] ) ) {
			$score += 15;
		}
		if ( ! empty( $all['company_name'] ) ) {
			$score += 10;
		}
		if ( ! empty( $all['service_interested'] ) ) {
			$score += 5;
		}
		if ( ! empty( $all['requirements'] ) && strlen( (string) $all['requirements'] ) >= 50 ) {
			$score += 10;
		}

		// Budget and timeline are scored by where the chosen option sits in the
		// owner's own list, so reordering the choices changes the score.
		$budget = self::option_fraction( 'budget_range', isset( $all['budget_range'] ) ? $all['budget_range'] : '' );
		if ( $budget >= 0 ) {
			$score += (int) round( 25 * $budget );
		}

		$timeline = self::option_fraction( 'timeline', isset( $all['timeline'] ) ? $all['timeline'] : '' );
		if ( $timeline >= 0 ) {
			$score += (int) round( 25 * ( 1 - $timeline ) );
		}

		/**
		 * Filters the lead score of a new submission.
		 *
		 * @since 1.3.0
		 * @param int   $score Score from 0 to 100.
		 * @param array $core  Core column values.
		 * @param array $extra Non-core field values.
		 */
		$score = (int) apply_filters( 'advc_lead_score', $score, $core, $extra );

		return max( 0, min( 100, $score ) );
	}

	/**
	 * Marketing-source keys captured with every submission.
	 *
	 * @since 1.3.0
	 * @return string[]
	 */
	public static function tracking_keys() {
		return array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid', 'landing_page', 'referrer' );
	}

	/**
	 * Available front-end color themes.
	 *
	 * @since 1.3.0
	 * @return array key => label
	 */
	public static function themes() {
		return array(
			'blue'    => __( 'Ocean Blue', 'advisers-contacts' ),
			'emerald' => __( 'Emerald Green', 'advisers-contacts' ),
			'violet'  => __( 'Royal Violet', 'advisers-contacts' ),
			'slate'   => __( 'Slate Dark', 'advisers-contacts' ),
		);
	}

	/**
	 * Fallback text for the settings that control the form wording.
	 *
	 * @since 1.3.0
	 * @param string $key title|intro|success|autoreply_subject|autoreply_body.
	 * @return string
	 */
	public static function default_text( $key ) {
		$map = array(
			'title'             => __( 'Let’s talk about your goals', 'advisers-contacts' ),
			'intro'             => __( 'Tell us a little about your business and what you need. We reply within one business day.', 'advisers-contacts' ),
			'success'           => __( 'Thank you — your message has been received. We will be in touch shortly.', 'advisers-contacts' ),
			'autoreply_subject' => __( 'We received your message', 'advisers-contacts' ),
			'autoreply_body'    => __( "Hi {name},\n\nThank you for contacting {site_name}. We have received your enquiry and will reply within one business day.\n\nRegards,\n{site_name}", 'advisers-contacts' ),
		);
		return isset( $map[ $key ] ) ? $map[ $key ] : '';
	}
}
