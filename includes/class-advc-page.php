<?php
/**
 * Contact page management for Advisers Contacts.
 *
 * @package Advisers_Contacts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ADVC_Page
 *
 * Keeps track of the WordPress page that carries the [advisers_contact_form]
 * shortcode. It never creates a second page when one already exists: an
 * existing page with the shortcode is adopted, and a page that already uses
 * the Contact slug is left alone.
 *
 * @since 1.0.0
 * @since 1.4.1 Adopts an existing page instead of creating a duplicate.
 */
class ADVC_Page {

	/** Shortcode that renders the form. */
	const SHORTCODE = '[advisers_contact_form]';

	/** @var ADVC_Page|null */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @since 1.0.0
	 * @return ADVC_Page
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
	 * @since 1.0.0
	 */
	private function hooks() {
		// Deliberately no hooks: the page is created once on activation. If the
		// owner deletes it, they decide whether to create it again (Contact Page screen).
	}

	/**
	 * Whether a post contains the contact form shortcode.
	 *
	 * @since 1.4.1
	 *
	 * @param WP_Post|null $post Post.
	 * @return bool
	 */
	private static function has_form( $post ) {
		return $post && has_shortcode( $post->post_content, 'advisers_contact_form' );
	}

	/**
	 * ID of the page that carries the form, or 0. Order: the page saved in
	 * the settings, then any page that already contains the shortcode.
	 * A page in the Trash still counts, so deleting it by mistake never
	 * leads to a duplicate being created.
	 *
	 * @since 1.4.1
	 *
	 * @return int
	 */
	public static function get_page_id() {
		$saved = (int) get_option( 'advc_page_id', 0 );
		$post  = $saved ? get_post( $saved ) : null;
		if ( $post && 'page' === $post->post_type ) {
			return $saved;
		}

		$candidates = get_posts(
			array(
				'post_type'        => 'page',
				'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ),
				'posts_per_page'   => 20,
				's'                => 'advisers_contact_form',
				'orderby'          => 'ID',
				'order'            => 'ASC',
			)
		);

		foreach ( $candidates as $candidate ) {
			if ( self::has_form( $candidate ) ) {
				update_option( 'advc_page_id', (int) $candidate->ID );
				return (int) $candidate->ID;
			}
		}

		return 0;
	}

	/**
	 * Make sure the site has a contact page, without ever duplicating one.
	 *
	 * @since 1.0.0
	 * @since 1.4.1 Adopts an existing page and skips creation when the slug is taken.
	 *
	 * @return int Page ID, or 0 when none exists and none was created.
	 */
	public static function create_default_page() {
		$existing = self::get_page_id();
		if ( $existing ) {
			return $existing;
		}

		// A page already lives at the default address: leave it alone.
		if ( get_page_by_path( 'contact', OBJECT, 'page' ) ) {
			return 0;
		}

		return self::create_page();
	}

	/**
	 * Create a new contact page (used on first run and by the "Create a new
	 * page" button). WordPress picks a unique slug if the wanted one is taken.
	 *
	 * @since 1.4.1
	 *
	 * @return int Page ID, or 0 on failure.
	 */
	public static function create_page() {
		$page_id = wp_insert_post(
			array(
				'post_title'   => __( 'Contact Us', 'advisers-contacts' ),
				'post_name'    => 'contact',
				'post_content' => self::SHORTCODE,
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);

		if ( ! $page_id || is_wp_error( $page_id ) ) {
			return 0;
		}

		update_option( 'advc_page_id', (int) $page_id );
		return (int) $page_id;
	}

	/**
	 * Point the plugin at a page the site owner picked, adding the form
	 * shortcode to it if it is missing.
	 *
	 * @since 1.4.1
	 *
	 * @param int $page_id Page ID.
	 * @return bool
	 */
	public static function use_existing_page( $page_id ) {
		$post = get_post( (int) $page_id );
		if ( ! $post || 'page' !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) {
			return false;
		}

		if ( ! self::has_form( $post ) ) {
			wp_update_post(
				array(
					'ID'           => $post->ID,
					'post_content' => rtrim( $post->post_content ) . "\n\n" . self::SHORTCODE,
				)
			);
		}

		update_option( 'advc_page_id', (int) $post->ID );
				return true;
	}

	/**
	 * Update the page's title, slug and content. The shortcode is kept on
	 * the page so the form never disappears.
	 *
	 * @since 1.0.0
	 * @since 1.4.1 Edits the page the site actually uses; reports the final slug.
	 *
	 * @param string $title   New page title.
	 * @param string $slug    New page slug.
	 * @param string $content New page content (shortcode is appended automatically).
	 * @return string|false The slug WordPress saved, or false on failure.
	 */
	public static function update_page( $title, $slug, $content ) {
		$page_id = self::get_page_id();
		if ( ! $page_id || ! current_user_can( 'edit_post', $page_id ) ) {
			return false;
		}

		if ( false === strpos( $content, self::SHORTCODE ) ) {
			$content = rtrim( $content ) . "\n\n" . self::SHORTCODE;
		}

		$slug = sanitize_title( $slug );

		$args = array(
			'ID'           => $page_id,
			'post_title'   => sanitize_text_field( $title ),
			'post_content' => wp_kses_post( $content ),
		);
		if ( '' !== $slug ) {
			$args['post_name'] = $slug;
		}

		$result = wp_update_post( $args, true );
		if ( is_wp_error( $result ) ) {
			return false;
		}

		$saved = get_post( $page_id );
		return $saved ? $saved->post_name : false;
	}
}
