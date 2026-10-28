<?php
/**
 * Field-builder data layer for Advisers Contacts.
 *
 * @package Advisers_Contacts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ADVC_Fields
 *
 * Manages the row set in wp_advc_fields that drives both the front-end
 * form render order and the admin field-builder screen.
 * @since 1.0.0
 */
class ADVC_Fields {

	/** @var ADVC_Fields|null */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @since 1.0.0
	 * @return ADVC_Fields
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Every built-in field, in the order they appear in the Field Builder's
	 * "Restore" list. Every field — including Full Name, Mobile Number, Email
	 * ID and the Submit button — can be disabled, reordered, relabelled or
	 * permanently deleted, and any deleted default can be restored later.
	 *
	 * Even if the Submit field row is deleted, ADVC_Frontend always renders a
	 * working submit button so the form itself never breaks.
	 *
	 * @since 1.0.0
	 * @since 1.3.0 Added lead-generation fields, widths, help text and the radio/checkbox types.
	 * @return array[]
	 */
	private static function default_definitions() {
		$defs = array(
			array( 'key' => 'full_name', 'label' => __( 'Full Name', 'advisers-contacts' ), 'type' => 'text', 'required' => 1, 'width' => 'half', 'placeholder' => __( 'Jane Doe', 'advisers-contacts' ) ),
			array( 'key' => 'email', 'label' => __( 'Email ID', 'advisers-contacts' ), 'type' => 'email', 'required' => 1, 'width' => 'half', 'placeholder' => __( 'you@company.com', 'advisers-contacts' ) ),
			array( 'key' => 'mobile_number', 'label' => __( 'Mobile Number', 'advisers-contacts' ), 'type' => 'tel', 'required' => 1, 'width' => 'half', 'placeholder' => __( '+91 98765 43210', 'advisers-contacts' ) ),
			array( 'key' => 'company_name', 'label' => __( 'Company Name', 'advisers-contacts' ), 'type' => 'text', 'required' => 0, 'width' => 'half', 'placeholder' => __( 'Your company', 'advisers-contacts' ) ),
			array( 'key' => 'job_title', 'label' => __( 'Job Title', 'advisers-contacts' ), 'type' => 'text', 'required' => 0, 'width' => 'half', 'placeholder' => __( 'Marketing Manager', 'advisers-contacts' ) ),
			array(
				'key'      => 'service_interested',
				'label'    => __( 'Service Interested In', 'advisers-contacts' ),
				'type'     => 'select',
				'required' => 1,
				'width'    => 'half',
				'help'     => '',
			),
			array(
				'key'      => 'budget_range',
				'label'    => __( 'Monthly Budget', 'advisers-contacts' ),
				'type'     => 'select',
				'required' => 0,
				'width'    => 'half',
				'help'     => __( 'Order the options from lowest to highest: the lead score uses their position.', 'advisers-contacts' ),
				'options'  => array( 'Under $500', '$500 – $1,000', '$1,000 – $5,000', '$5,000 – $10,000', '$10,000+' ),
			),
			array(
				'key'      => 'timeline',
				'label'    => __( 'When do you want to start?', 'advisers-contacts' ),
				'type'     => 'select',
				'required' => 0,
				'width'    => 'half',
				'options'  => array( 'Immediately', 'Within 1 month', '1–3 months', '3–6 months', 'Just exploring' ),
			),
			array(
				'key'      => 'contact_method',
				'label'    => __( 'Preferred Contact Method', 'advisers-contacts' ),
				'type'     => 'radio',
				'required' => 0,
				'width'    => 'full',
				'options'  => array( 'Phone call', 'WhatsApp', 'Email' ),
			),
			array(
				'key'      => 'contact_time',
				'label'    => __( 'Best Time to Contact', 'advisers-contacts' ),
				'type'     => 'select',
				'required' => 0,
				'width'    => 'half',
				'options'  => array( 'Morning (9am – 12pm)', 'Afternoon (12pm – 4pm)', 'Evening (4pm – 8pm)', 'Anytime' ),
			),
			array( 'key' => 'requirements', 'label' => __( 'Requirements', 'advisers-contacts' ), 'type' => 'textarea', 'required' => 0, 'width' => 'full', 'placeholder' => __( 'Tell us about your project, goals and challenges…', 'advisers-contacts' ) ),
			array(
				'key'      => 'how_heard',
				'label'    => __( 'How did you hear about us?', 'advisers-contacts' ),
				'type'     => 'select',
				'required' => 0,
				'width'    => 'half',
				'options'  => array( 'Google Search', 'Social Media', 'Referral / Friend', 'Advertisement', 'Blog / Content', 'Event / Webinar', 'Other' ),
			),
			array( 'key' => 'referred_by', 'label' => __( 'Referred By', 'advisers-contacts' ), 'type' => 'text', 'required' => 0, 'width' => 'half', 'placeholder' => __( 'Name of the person who referred you', 'advisers-contacts' ) ),
			array( 'key' => 'whatsapp_optin', 'label' => __( 'Send me updates on WhatsApp', 'advisers-contacts' ), 'type' => 'checkbox', 'required' => 0, 'width' => 'full' ),
			array( 'key' => 'consent', 'label' => __( 'I agree to be contacted about my enquiry and to the storage of my details as described in the privacy policy.', 'advisers-contacts' ), 'type' => 'checkbox', 'required' => 1, 'width' => 'full' ),
			array( 'key' => 'submit_button', 'label' => __( 'Send Message', 'advisers-contacts' ), 'type' => 'submit', 'required' => 0, 'width' => 'full' ),

			// Fields that are available but switched off in the lead-generation layout.
			array( 'key' => 'subject', 'label' => __( 'Subject', 'advisers-contacts' ), 'type' => 'text', 'required' => 0, 'width' => 'half' ),
			array( 'key' => 'website_url', 'label' => __( 'Website URL', 'advisers-contacts' ), 'type' => 'url', 'required' => 0, 'width' => 'half', 'placeholder' => 'https://' ),
			array( 'key' => 'city', 'label' => __( 'City', 'advisers-contacts' ), 'type' => 'text', 'required' => 0, 'width' => 'half' ),
			array( 'key' => 'country', 'label' => __( 'Country', 'advisers-contacts' ), 'type' => 'text', 'required' => 0, 'width' => 'half' ),
			array(
				'key'      => 'industry',
				'label'    => __( 'Industry', 'advisers-contacts' ),
				'type'     => 'select',
				'required' => 0,
				'width'    => 'half',
				'options'  => array( 'Technology / SaaS', 'E-commerce / Retail', 'Education', 'Healthcare', 'Real Estate', 'Finance / Insurance', 'Travel / Hospitality', 'Manufacturing', 'Professional Services', 'Other' ),
			),
			array(
				'key'      => 'company_size',
				'label'    => __( 'Company Size', 'advisers-contacts' ),
				'type'     => 'select',
				'required' => 0,
				'width'    => 'half',
				'options'  => array( 'Just me', '2–10 people', '11–50 people', '51–200 people', '201–1,000 people', '1,000+ people' ),
			),
			array( 'key' => 'date_of_birth', 'label' => __( 'Date of Birth', 'advisers-contacts' ), 'type' => 'date', 'required' => 0, 'width' => 'half' ),
			array( 'key' => 'parent_name', 'label' => __( 'Parent Name', 'advisers-contacts' ), 'type' => 'text', 'required' => 0, 'width' => 'half' ),
			array(
				'key'      => 'gender',
				'label'    => __( 'Gender', 'advisers-contacts' ),
				'type'     => 'select',
				'required' => 0,
				'width'    => 'half',
				'options'  => array( 'Male', 'Female', 'Other', 'Prefer not to say' ),
			),
			array( 'key' => 'religion', 'label' => __( 'Religion', 'advisers-contacts' ), 'type' => 'text', 'required' => 0, 'width' => 'half' ),
			array( 'key' => 'nationality', 'label' => __( 'Nationality', 'advisers-contacts' ), 'type' => 'text', 'required' => 0, 'width' => 'half' ),
			array( 'key' => 'residential_address', 'label' => __( 'Residential Address', 'advisers-contacts' ), 'type' => 'textarea', 'required' => 0, 'width' => 'full' ),
			array( 'key' => 'upload_files', 'label' => __( 'Upload Files', 'advisers-contacts' ), 'type' => 'file', 'required' => 0, 'width' => 'full' ),
		);

		return $defs;
	}

	/**
	 * Keys that make up the ready-made "Lead Generation" layout, in order.
	 *
	 * @since 1.3.0
	 * @return string[]
	 */
	public static function lead_preset_keys() {
		return array( 'full_name', 'email', 'mobile_number', 'company_name', 'job_title', 'service_interested', 'budget_range', 'timeline', 'contact_method', 'requirements', 'how_heard', 'referred_by', 'whatsapp_optin', 'consent', 'submit_button' );
	}

	/**
	 * Build the row written to the fields table for a default definition.
	 *
	 * @since 1.2.1
	 * @since 1.3.0 Stores width, placeholder and help text.
	 *
	 * @param array $def     Default definition.
	 * @param int   $order   Sort order.
	 * @param int   $enabled Whether the field starts enabled.
	 * @return array
	 */
	private static function row_from_definition( $def, $order, $enabled = 1 ) {
		return array(
			'field_key'   => $def['key'],
			'label'       => $def['label'],
			'type'        => $def['type'],
			'options'     => ! empty( $def['options'] ) ? wp_json_encode( $def['options'] ) : '',
			'placeholder' => isset( $def['placeholder'] ) ? $def['placeholder'] : '',
			'help_text'   => isset( $def['help'] ) ? $def['help'] : '',
			'width'       => isset( $def['width'] ) ? $def['width'] : 'full',
			'is_required' => (int) $def['required'],
			'is_enabled'  => (int) $enabled,
			'is_locked'   => 0,
			'sort_order'  => (int) $order,
			'created_at'  => current_time( 'mysql' ),
			'updated_at'  => current_time( 'mysql' ),
		);
	}

	/**
	 * Find a default definition by key.
	 *
	 * @since 1.3.0
	 * @param string $key Field key.
	 * @return array|null
	 */
	private static function find_definition( $key ) {
		foreach ( self::default_definitions() as $candidate ) {
			if ( $candidate['key'] === $key ) {
				return $candidate;
			}
		}
		return null;
	}

	/**
	 * Highest sort_order currently in use.
	 *
	 * @since 1.2.1
	 *
	 * @return int
	 */
	private static function max_sort_order() {
		$max = 0;
		foreach ( self::get_fields() as $field ) {
			$max = max( $max, (int) $field->sort_order );
		}
		return $max;
	}

	/**
	 * Default field definitions that are NOT currently present in the
	 * fields table (i.e. were deleted by the admin and can be restored).
	 *
	 * @since 1.1.0
	 * @return array[]
	 */
	public static function get_restorable_defaults() {
		$existing = wp_list_pluck( self::get_fields(), 'field_key' );

		return array_values(
			array_filter(
				self::default_definitions(),
				function ( $def ) use ( $existing ) {
					return ! in_array( $def['key'], $existing, true );
				}
			)
		);
	}

	/**
	 * Re-add a previously deleted default field. It is appended to the end
	 * of the form; the admin can drag it wherever they like afterwards.
	 *
	 * @since 1.1.0
	 * @param string $key One of the default field keys.
	 * @return int|WP_Error New field ID, or WP_Error on failure.
	 */
	public static function restore_field( $key ) {
		global $wpdb;

		if ( self::get_field( $key ) ) {
			return new WP_Error( 'advc_exists', __( 'That field already exists in your form.', 'advisers-contacts' ) );
		}

		$def = self::find_definition( $key );
		if ( ! $def ) {
			return new WP_Error( 'advc_unknown_field', __( 'Unknown default field.', 'advisers-contacts' ) );
		}

		$inserted = $wpdb->insert( ADVC_DB::fields_table(), self::row_from_definition( $def, self::max_sort_order() + 10 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to the plugin's own table; the read cache is flushed below.
		ADVC_DB::flush_cache();

		return $inserted ? (int) $wpdb->insert_id : new WP_Error( 'advc_insert_failed', __( 'Could not restore the field.', 'advisers-contacts' ) );
	}

	/**
	 * Seed the default fields the first time the plugin runs on a site.
	 *
	 * Seeding happens only while the fields table is empty, so a field the
	 * admin deleted is never silently re-added. A fresh site gets the
	 * lead-generation layout switched on; every other built-in field is
	 * stored switched off and can be enabled in one click.
	 *
	 * @since 1.0.0
	 * @since 1.3.0 Seeds only an empty table.
	 */
	public static function maybe_seed_defaults() {
		global $wpdb;

		ADVC_DB::flush_cache();
		if ( self::get_fields() ) {
			return;
		}

		$preset = self::lead_preset_keys();
		$order  = 10;

		foreach ( $preset as $key ) {
			$def = self::find_definition( $key );
			if ( $def ) {
				$wpdb->insert( ADVC_DB::fields_table(), self::row_from_definition( $def, $order, 1 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to the plugin's own table; the read cache is flushed below.
				$order += 10;
			}
		}
		foreach ( self::default_definitions() as $def ) {
			if ( ! in_array( $def['key'], $preset, true ) ) {
				$wpdb->insert( ADVC_DB::fields_table(), self::row_from_definition( $def, $order, 0 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to the plugin's own table; the read cache is flushed below.
				$order += 10;
			}
		}

		ADVC_DB::flush_cache();
	}

	/**
	 * Give the built-in fields of a pre-1.3.0 install their half/full width.
	 * Runs once, when the width column is first added.
	 *
	 * @since 1.3.0
	 */
	public static function backfill_widths() {
		global $wpdb;

		foreach ( self::get_fields() as $field ) {
			$def = self::find_definition( $field->field_key );
			if ( $def && isset( $def['width'] ) && 'full' !== $def['width'] ) {
				$wpdb->update( ADVC_DB::fields_table(), array( 'width' => $def['width'] ), array( 'id' => (int) $field->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off upgrade write; the read cache is flushed below.
			}
		}
		ADVC_DB::flush_cache();
	}

	/**
	 * Switch the form to the ready-made lead-generation layout.
	 *
	 * Preset fields are added if missing, enabled and ordered first; custom
	 * fields the admin created stay enabled before the submit button; the
	 * remaining built-in fields are switched off (not deleted).
	 *
	 * @since 1.3.0
	 */
	public static function apply_lead_preset() {
		global $wpdb;

		$table  = ADVC_DB::fields_table();
		$preset = array_diff( self::lead_preset_keys(), array( 'submit_button' ) );
		$order  = 10;

		// 1. Make sure every preset field exists and is enabled, in order.
		foreach ( $preset as $key ) {
			$existing = self::get_field( $key );
			if ( $existing ) {
				$wpdb->update( $table, array( 'is_enabled' => 1, 'sort_order' => $order, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $existing->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to the plugin's own table; the read cache is flushed below.
			} else {
				$def = self::find_definition( $key );
				if ( $def ) {
					$wpdb->insert( $table, self::row_from_definition( $def, $order, 1 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to the plugin's own table; the read cache is flushed below.
				}
			}
			$order += 10;
		}
		ADVC_DB::flush_cache();

		// 2. Custom fields stay enabled, after the preset.
		$default_keys = wp_list_pluck( self::default_definitions(), 'key' );
		foreach ( self::get_fields() as $field ) {
			if ( ! in_array( $field->field_key, $default_keys, true ) ) {
				$wpdb->update( $table, array( 'sort_order' => $order, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $field->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to the plugin's own table; the read cache is flushed below.
				$order += 10;
			}
		}

		// 3. Submit button next (restored if it was deleted).
		$submit = self::get_field( 'submit_button' );
		if ( $submit ) {
			$wpdb->update( $table, array( 'is_enabled' => 1, 'sort_order' => $order, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $submit->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to the plugin's own table; the read cache is flushed below.
		} else {
			$def = self::find_definition( 'submit_button' );
			$wpdb->insert( $table, self::row_from_definition( $def, $order, 1 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to the plugin's own table; the read cache is flushed below.
		}
		$order += 10;
		ADVC_DB::flush_cache();

		// 4. Switch off the other built-in fields, keeping their data.
		$keep = self::lead_preset_keys();
		foreach ( self::get_fields() as $field ) {
			if ( in_array( $field->field_key, $default_keys, true ) && ! in_array( $field->field_key, $keep, true ) ) {
				$wpdb->update( $table, array( 'is_enabled' => 0, 'sort_order' => $order, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $field->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to the plugin's own table; the read cache is flushed below.
				$order += 10;
			}
		}

		ADVC_DB::flush_cache();
	}

	/**
	 * Get all fields ordered for display.
	 *
	 * @since 1.0.0
	 * @param bool $enabled_only Whether to only return enabled fields.
	 * @return object[]
	 */
	public static function get_fields( $enabled_only = false ) {
		global $wpdb;

		$enabled_only = $enabled_only ? 1 : 0;
		$key          = ADVC_DB::cache_key( 'fields', $enabled_only );
		$hit          = wp_cache_get( $key, ADVC_DB::CACHE_GROUP );
		if ( is_array( $hit ) ) {
			return $hit;
		}

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table; result is cached with wp_cache_set() below.
			$wpdb->prepare(
				'SELECT * FROM %i WHERE ( %d = 0 OR is_enabled = 1 ) ORDER BY sort_order ASC, id ASC',
				ADVC_DB::fields_table(),
				$enabled_only
			)
		);
		$rows = $rows ? $rows : array();
		wp_cache_set( $key, $rows, ADVC_DB::CACHE_GROUP );

		return $rows;
	}

	/**
	 * Get a single field definition by key.
	 *
	 * @since 1.0.0
	 * @param string $key Field key.
	 * @return object|null
	 */
	public static function get_field( $key ) {
		foreach ( self::get_fields() as $field ) {
			if ( $field->field_key === $key ) {
				return $field;
			}
		}
		return null;
	}

	/**
	 * Save the drag-drop order + enabled/required toggles from the field
	 * builder screen in one pass.
	 *
	 * @since 1.0.0
	 * @param array $rows Array of assoc arrays: id, sort_order, is_enabled, is_required, label.
	 */
	public static function save_order( $rows ) {
		global $wpdb;

		$by_id = array();
		foreach ( self::get_fields() as $field ) {
			$by_id[ (int) $field->id ] = $field;
		}

		foreach ( $rows as $row ) {
			$id = isset( $row['id'] ) ? (int) $row['id'] : 0;
			if ( ! $id || ! isset( $by_id[ $id ] ) ) {
				continue;
			}
			$field = $by_id[ $id ];

			$update = array(
				'sort_order'  => isset( $row['sort_order'] ) ? (int) $row['sort_order'] : $field->sort_order,
				'label'       => isset( $row['label'] ) ? sanitize_text_field( $row['label'] ) : $field->label,
				'is_enabled'  => isset( $row['is_enabled'] ) ? (int) (bool) $row['is_enabled'] : $field->is_enabled,
				'is_required' => isset( $row['is_required'] ) ? (int) (bool) $row['is_required'] : $field->is_required,
				'width'       => isset( $row['width'] ) && in_array( $row['width'], array( 'full', 'half' ), true ) ? $row['width'] : $field->width,
				'placeholder' => isset( $row['placeholder'] ) ? sanitize_text_field( $row['placeholder'] ) : $field->placeholder,
				'help_text'   => isset( $row['help_text'] ) ? sanitize_text_field( $row['help_text'] ) : $field->help_text,
				'updated_at'  => current_time( 'mysql' ),
			);

			if ( in_array( $field->type, array( 'select', 'radio' ), true ) && isset( $row['options'] ) ) {
				$options           = self::parse_options( $row['options'] );
				$update['options'] = $options ? wp_json_encode( $options ) : '';
			}

			$wpdb->update( ADVC_DB::fields_table(), $update, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to the plugin's own table; the read cache is flushed below.
		}

		ADVC_DB::flush_cache();
	}

	/**
	 * Add a new custom field.
	 *
	 * @since 1.0.0
	 * @param array $data label, type, options (array|string), required, placeholder.
	 * @return int|WP_Error New field ID, or WP_Error on failure.
	 */
	public static function add_field( $data ) {
		global $wpdb;

		$table = ADVC_DB::fields_table();

		$label = isset( $data['label'] ) ? sanitize_text_field( $data['label'] ) : '';
		if ( '' === $label ) {
			return new WP_Error( 'advc_missing_label', __( 'A field label is required.', 'advisers-contacts' ) );
		}

		$allowed_types = self::allowed_types();
		$type          = isset( $data['type'] ) && in_array( $data['type'], $allowed_types, true ) ? $data['type'] : 'text';

		$key = sanitize_key( 'custom_' . sanitize_title( $label ) . '_' . wp_generate_password( 4, false, false ) );

		$options = array();
		if ( in_array( $type, array( 'select', 'radio' ), true ) && ! empty( $data['options'] ) ) {
			$options = self::parse_options( $data['options'] );
		}

		// Keep the Submit button last: insert new fields just before it.
		$submit = self::get_field( 'submit_button' );
		$order  = $submit ? (int) $submit->sort_order : ( self::max_sort_order() + 10 );
		if ( $submit ) {
			$wpdb->query( $wpdb->prepare( 'UPDATE %i SET sort_order = sort_order + 10 WHERE sort_order >= %d', $table, (int) $submit->sort_order ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to the plugin's own table; the read cache is flushed below.
		}

		$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to the plugin's own table; the read cache is flushed below.
			$table,
			array(
				'field_key'   => $key,
				'label'       => $label,
				'type'        => $type,
				'options'     => $options ? wp_json_encode( $options ) : '',
				'placeholder' => isset( $data['placeholder'] ) ? sanitize_text_field( $data['placeholder'] ) : '',
				'help_text'   => isset( $data['help_text'] ) ? sanitize_text_field( $data['help_text'] ) : '',
				'width'       => isset( $data['width'] ) && 'half' === $data['width'] ? 'half' : 'full',
				'is_required' => ! empty( $data['required'] ) ? 1 : 0,
				'is_enabled'  => 1,
				'is_locked'   => 0,
				'sort_order'  => $order,
				'created_at'  => current_time( 'mysql' ),
				'updated_at'  => current_time( 'mysql' ),
			)
		);
		ADVC_DB::flush_cache();

		return $inserted ? (int) $wpdb->insert_id : new WP_Error( 'advc_insert_failed', __( 'Could not save the new field.', 'advisers-contacts' ) );
	}

	/**
	 * Delete any field — default or custom. Deleting a field only removes
	 * it from the form/field-builder; the underlying submissions table
	 * column (for the five core fields) is left untouched, so no data is
	 * lost and the field can be restored later via restore_field().
	 *
	 * @since 1.0.0
	 * @param int $id Field ID.
	 * @return bool|WP_Error
	 */
	public static function delete_field( $id ) {
		global $wpdb;

		$id    = (int) $id;
		$found = false;
		foreach ( self::get_fields() as $field ) {
			if ( (int) $field->id === $id ) {
				$found = true;
				break;
			}
		}
		if ( ! $found ) {
			return new WP_Error( 'advc_not_found', __( 'Field not found.', 'advisers-contacts' ) );
		}

		$wpdb->delete( ADVC_DB::fields_table(), array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to the plugin's own table; the read cache is flushed below.
		ADVC_DB::flush_cache();

		return true;
	}

	/**
	 * Field types the builder can create.
	 *
	 * @since 1.3.0
	 * @return string[]
	 */
	public static function allowed_types() {
		return array( 'text', 'textarea', 'email', 'tel', 'url', 'date', 'select', 'radio', 'checkbox', 'file' );
	}

	/**
	 * Turn a textarea (one option per line) or array into a clean option list.
	 *
	 * @since 1.3.0
	 * @param array|string $raw Options.
	 * @return string[]
	 */
	public static function parse_options( $raw ) {
		$raw = is_array( $raw ) ? $raw : explode( "\n", (string) $raw );

		// Request data can nest arrays; only plain strings are valid choices.
		$raw = array_filter( $raw, 'is_scalar' );

		return array_values( array_unique( array_filter( array_map( 'sanitize_text_field', array_map( 'trim', array_map( 'strval', $raw ) ) ) ) ) );
	}

	/**
	 * Decoded option list of a field row.
	 *
	 * @since 1.3.0
	 * @param object $field Field row.
	 * @return string[]
	 */
	public static function get_options( $field ) {
		$options = $field->options ? json_decode( $field->options, true ) : array();
		return is_array( $options ) ? array_values( $options ) : array();
	}

	/**
	 * Replace the choices of a dropdown or radio field.
	 *
	 * @since 1.4.2
	 *
	 * @param string       $key     Field key.
	 * @param array|string $options Choices (array or one per line).
	 * @return bool False when the field does not exist.
	 */
	public static function set_options( $key, $options ) {
		global $wpdb;

		$field = self::get_field( $key );
		if ( ! $field ) {
			return false;
		}

		$options = self::parse_options( $options );

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to the plugin's own table; the read cache is flushed below.
			ADVC_DB::fields_table(),
			array(
				'options'    => $options ? wp_json_encode( $options ) : '',
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => (int) $field->id )
		);
		ADVC_DB::flush_cache();

		return true;
	}

	/**
	 * Whether a field can be shown on the form. A dropdown or radio group
	 * with no choices is hidden instead of showing an empty list.
	 *
	 * @since 1.4.2
	 *
	 * @param object $field Field row.
	 * @return bool
	 */
	public static function is_usable( $field ) {
		if ( in_array( $field->type, array( 'select', 'radio' ), true ) ) {
			return (bool) self::get_options( $field );
		}
		return true;
	}

	/**
	 * Upgrade step: remove the pre-filled service names that earlier versions
	 * shipped, but only when the list is still exactly the original one, so
	 * services the owner already edited are kept.
	 *
	 * @since 1.4.2
	 */
	public static function clear_default_services() {
		$field = self::get_field( 'service_interested' );
		if ( ! $field ) {
			return;
		}

		$old = array( 'SEO Services', 'Paid Advertising', 'Social Media Marketing', 'Content & Creative', 'Web & App Development', 'Software & SaaS Consultancy', 'Web Hosting & Infrastructure', 'Analytics & Growth', 'Other' );
		if ( self::get_options( $field ) === $old ) {
			self::set_options( 'service_interested', array() );
		}
	}
}
