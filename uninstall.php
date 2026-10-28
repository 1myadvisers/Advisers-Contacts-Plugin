<?php
/**
 * Uninstall handler for Advisers Contacts.
 *
 * Fires only when the plugin is deleted (not on simple deactivation) via
 * wp-admin's Plugins screen — or the Network Admin Plugins screen on a
 * multisite install. Removes the plugin's custom tables and options.
 *
 * On multisite, this file runs ONCE for the whole network, not once per
 * site, so it must loop every site itself and switch into each one to
 * clean up that site's tables/options — the standard pattern documented
 * in the WordPress Plugin Handbook for multisite-aware uninstall.php.
 *
 * The auto-created Contact Us page is deliberately left in place on every
 * site, since it may contain content the site owner still wants, and
 * WordPress.org guidelines discourage deleting content on uninstall
 * unless the site owner explicitly opted in.
 *
 *
 * @package Advisers_Contacts
 * @since 1.0.0
 * @since 1.1.0 Made multisite-aware (loops every site on the network).
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Drop this plugin's tables and delete its options for whichever site is
 * currently in context (the main site on single-site installs, or the
 * site most recently switched into via switch_to_blog() on multisite).
 *
 * @since 1.1.0
 */
function advc_uninstall_current_site() {
	global $wpdb;

	foreach ( array( 'advc_submissions', 'advc_fields', 'advc_referrers' ) as $advc_table ) {
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . $advc_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Removing this plugin's own tables on uninstall.
	}

	$options = array(
		'advc_db_version',
		'advc_page_id',
		'advc_page_title',
		'advc_page_slug',
		'advc_page_content',
		'advc_default_country_code',
		'advc_per_page',
		'advc_notify_email',
		'advc_notify_enabled',
		'advc_form_title',
		'advc_form_intro',
		'advc_success_message',
		'advc_theme',
		'advc_share_enabled',
		'advc_autoreply_enabled',
		'advc_autoreply_subject',
		'advc_autoreply_body',
	);

	foreach ( $options as $option ) {
		delete_option( $option );
	}

	// Leftover from older versions; the other transients expire on their own within an hour.
	delete_transient( 'advc_page_checked' );
}

if ( is_multisite() ) {
	$advc_site_ids = get_sites( array( 'fields' => 'ids' ) );

	foreach ( $advc_site_ids as $advc_site_id ) {
		switch_to_blog( (int) $advc_site_id );
		advc_uninstall_current_site();
		restore_current_blog();
	}
} else {
	advc_uninstall_current_site();
}
