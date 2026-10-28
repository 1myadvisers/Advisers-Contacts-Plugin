<?php
/**
 * Plugin Name:       Advisers Contacts
 * Plugin URI:        https://myadvisers.in/advisers-contacts/
 * Description:       Fully manageable drag-and-drop contact form with a submissions dashboard, referrer tracking, WhatsApp/email quick-links and an auto-generated Contact Us page. Multisite compatible.
 * Version:           1.4.8
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            My Advisers
 * Author URI:        https://myadvisers.in
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       advisers-contacts
 *
 * Advisers Contacts is safe to Network Activate. Data (submissions, field
 * definitions and the Contact Us page) is deliberately kept per-site, not
 * shared network-wide, because a contact form's submissions belong to the
 * individual site they were submitted on. Hooks are
 * documented in readme.txt.
 *
 * @package Advisers_Contacts
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Plugin version. Bump on every functional release. @since 1.0.0 */
define( 'ADVC_VERSION', '1.4.8' );

/**
 * DB schema version this codebase expects. Bump only when the table
 * structure in ADVC_DB::install() actually changes, so maybe_upgrade() can
 * detect sites that still need dbDelta() re-run.
 *
 * @since 1.0.0
 */
define( 'ADVC_DB_VERSION', '1.3.0' );

/** Absolute path to the plugin directory, trailing slash included. @since 1.0.0 */
define( 'ADVC_DIR', plugin_dir_path( __FILE__ ) );

/** URL to the plugin directory, trailing slash included. @since 1.0.0 */
define( 'ADVC_URL', plugin_dir_url( __FILE__ ) );

/** Plugin basename (`advisers-contacts/advisers-contacts.php`). @since 1.0.0 */
define( 'ADVC_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Default capability required to manage submissions / settings, kept as a
 * constant for backward compatibility. New code should call
 * advc_get_capability() instead, which applies the `advc_capability` filter —
 * that lets a network admin swap in a custom capability (e.g. a role
 * restricted to editors on a given site) without editing plugin files.
 *
 * @since 1.0.0
 * @deprecated 1.1.0 Use advc_get_capability() instead.
 */
if ( ! defined( 'ADVC_CAP' ) ) {
	define( 'ADVC_CAP', 'manage_options' );
}

/**
 * Get the capability required to manage Advisers Contacts on the current
 * site. Filterable so multisite network admins (or single-site owners
 * running a custom role setup) can restrict access without code changes.
 *
 * @since 1.1.0
 * @return string
 */
function advc_get_capability() {
	/**
	 * Filters the capability required to manage Advisers Contacts.
	 *
	 * @since 1.1.0
	 * @param string $capability Capability slug. Default 'manage_options'.
	 */
	return apply_filters( 'advc_capability', ADVC_CAP );
}

/**
 * Autoload plugin classes following the `ADVC_Class_Name` →
 * `includes/class-advc-class-name.php` convention.
 *
 * @since 1.0.0
 * @param string $class_name Fully qualified class name.
 */
function advc_autoload( $class_name ) {
	if ( 0 !== strpos( $class_name, 'ADVC_' ) ) {
		return;
	}

	$file = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';
	$path = ADVC_DIR . 'includes/' . $file;

	if ( file_exists( $path ) ) {
		require_once $path;
	}
}
spl_autoload_register( 'advc_autoload' );

/**
 * Main plugin bootstrap / singleton.
 *
 * Handles single-site and multisite activation, per-site auto-provisioning
 * of new sites on a network, and registers the site-table cleanup that
 * fires when a site is deleted from a network.
 *
 * @since 1.0.0
 */
final class ADVC_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 * @var ADVC_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 * @return ADVC_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — wires up hooks. Kept private, use get_instance().
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'boot' ), 5 );
		add_action( 'init', array( $this, 'maybe_upgrade' ), 1 );

		// Multisite: auto-provision sites created after Network Activation.
		add_action( 'wp_initialize_site', array( $this, 'provision_new_site' ), 10, 1 );

		// Multisite: let WordPress core's own site-deletion routine drop
		// this plugin's tables along with the rest of the site's tables.
		// This is the official mechanism (see wp-admin/includes/ms.php)
		// rather than a manual DROP TABLE on a delete hook.
		add_filter( 'wpmu_drop_tables', array( $this, 'register_tables_for_site_deletion' ) );
	}

	/**
	 * Boot all plugin modules. Runs on every site load (normal or, inside
	 * a multisite network, whichever site is currently in context).
	 *
	 * @since 1.0.0
	 */
	public function boot() {
		ADVC_DB::instance();
		ADVC_Fields::instance();
		ADVC_Page::instance();
		ADVC_Frontend::instance();
		ADVC_Privacy::instance();
		ADVC_Notifications::instance();

		if ( is_admin() ) {
			ADVC_Admin::instance();
		}
	}

	/**
	 * Runs on every 'init' at priority 1 so schema upgrades apply even
	 * after a plugin update where the activation hook did not fire (e.g.
	 * auto-updates, or a site added to an already network-active plugin
	 * outside the wp_initialize_site flow).
	 *
	 * @since 1.0.0
	 */
	public function maybe_upgrade() {
		$installed = get_option( 'advc_db_version', '' );
		if ( $installed !== ADVC_DB_VERSION ) {
			$this->provision_current_site();

			// 1.1.0 added the field width column: give existing built-in fields their layout width.
			if ( '' !== $installed && version_compare( $installed, '1.1.0', '<' ) ) {
				ADVC_Fields::backfill_widths();
			}

			// 1.3.0 removed the pre-filled service names.
			if ( '' !== $installed && version_compare( $installed, '1.3.0', '<' ) ) {
				ADVC_Fields::clear_default_services();
			}
		}
	}

	/**
	 * Fires on plugin activation. On a single site this provisions that
	 * site. On a multisite network, if the plugin was Network Activated,
	 * it loops every existing site and provisions each one individually
	 * via switch_to_blog() — the standard pattern documented in the
	 * WordPress Plugin Handbook for network-wide activation.
	 *
	 * Note for very large networks (many thousands of sites): this runs
	 * synchronously during activation. For that scale, consider batching
	 * get_sites() with 'number'/'offset' and provisioning asynchronously;
	 * this straightforward loop is production-ready for typical networks.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Added multisite / network-wide activation handling.
	 * @param bool $network_wide Whether the plugin is being network-activated.
	 */
	public function activate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			$site_ids = get_sites( array( 'fields' => 'ids' ) );
			foreach ( $site_ids as $site_id ) {
				switch_to_blog( (int) $site_id );
				$this->provision_current_site();
				restore_current_blog();
			}
		} else {
			$this->provision_current_site();
		}
	}

	/**
	 * Provision the currently active site: create/upgrade tables, seed
	 * default fields, create the Contact Us page, record the DB version,
	 * and flush that site's rewrite rules.
	 *
	 * @since 1.1.0
	 */
	private function provision_current_site() {
		ADVC_DB::install();
		ADVC_Fields::maybe_seed_defaults();
		ADVC_Page::create_default_page();
		update_option( 'advc_db_version', ADVC_DB_VERSION );
		flush_rewrite_rules();
	}

	/**
	 * Auto-provision a newly created site on a network where Advisers
	 * Contacts is network-activated, so admins never have to manually
	 * "reactivate" the plugin per new site.
	 *
	 * @since 1.1.0
	 * @param WP_Site $new_site Newly created site object.
	 */
	public function provision_new_site( $new_site ) {
		if ( ! is_multisite() ) {
			return;
		}

		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! is_plugin_active_for_network( ADVC_BASENAME ) ) {
			return;
		}

		switch_to_blog( (int) $new_site->blog_id );
		$this->provision_current_site();
		restore_current_blog();
	}

	/**
	 * Add this plugin's tables to WordPress core's own list of tables to
	 * drop when a site is deleted from a network, via the `wpmu_drop_tables`
	 * filter. This is the documented, official way for a plugin to keep
	 * its per-site tables from becoming orphaned data after a site delete.
	 *
	 * @since 1.1.0
	 * @param string[] $tables Table names WordPress will drop for the site being deleted.
	 * @return string[]
	 */
	public function register_tables_for_site_deletion( $tables ) {
		$tables[] = ADVC_DB::submissions_table();
		$tables[] = ADVC_DB::fields_table();
		$tables[] = ADVC_DB::referrers_table();
		return $tables;
	}

	/**
	 * Fires on plugin deactivation. Data is intentionally kept; only
	 * transient rewrite rules are cleared. Full removal happens in
	 * uninstall.php if the site owner deletes the plugin.
	 *
	 * @since 1.0.0
	 * @param bool $network_wide Whether the plugin was network-deactivated.
	 */
	public function deactivate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			$site_ids = get_sites( array( 'fields' => 'ids' ) );
			foreach ( $site_ids as $site_id ) {
				switch_to_blog( (int) $site_id );
				flush_rewrite_rules();
				restore_current_blog();
			}
		} else {
			flush_rewrite_rules();
		}
	}
}

ADVC_Plugin::get_instance();
