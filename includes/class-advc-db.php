<?php
/**
 * Database access layer for Advisers Contacts.
 *
 * @package Advisers_Contacts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ADVC_DB
 *
 * Owns the two custom tables used by the plugin and provides small,
 * prepared-statement wrappers around them. No business logic lives here.
 * @since 1.0.0
 */
class ADVC_DB {

	/** @var ADVC_DB|null */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @since 1.0.0
	 * @return ADVC_DB
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Submissions table name (prefixed).
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function submissions_table() {
		global $wpdb;
		return $wpdb->prefix . 'advc_submissions';
	}

	/**
	 * Fields table name (prefixed).
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function fields_table() {
		global $wpdb;
		return $wpdb->prefix . 'advc_fields';
	}

	/**
	 * Referrers table name (prefixed). Holds the payout details the site
	 * owner types in for each referrer.
	 *
	 * @since 1.4.0
	 * @return string
	 */
	public static function referrers_table() {
		global $wpdb;
		return $wpdb->prefix . 'advc_referrers';
	}

	/**
	 * Object cache group for every read from the plugin tables.
	 *
	 * @since 1.2.1
	 * @var string
	 */
	const CACHE_GROUP = 'advc';

	/**
	 * Current "last changed" token. Cache keys embed it, so bumping it in
	 * flush_cache() invalidates every cached read at once.
	 *
	 * @since 1.2.1
	 *
	 * @return string
	 */
	private static function last_changed() {
		$token = wp_cache_get( 'last_changed', self::CACHE_GROUP );
		if ( false === $token ) {
			$token = (string) microtime();
			wp_cache_set( 'last_changed', $token, self::CACHE_GROUP );
		}
		return $token;
	}

	/**
	 * Build a cache key that is unique to a query and the current table state.
	 *
	 * @since 1.2.1
	 *
	 * @param string $name  Query name.
	 * @param mixed  $parts Anything that makes the query unique.
	 * @return string
	 */
	public static function cache_key( $name, $parts = array() ) {
		return $name . ':' . md5( (string) wp_json_encode( $parts ) ) . ':' . self::last_changed();
	}

	/**
	 * Invalidate every cached read. Call after any write to a plugin table.
	 *
	 * @since 1.2.1
	 */
	public static function flush_cache() {
		wp_cache_set( 'last_changed', (string) microtime(), self::CACHE_GROUP );
	}

	/**
	 * Create/upgrade the plugin tables using dbDelta.
	 *
	 * @since 1.0.0
	 */
	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$submissions     = self::submissions_table();
		$fields          = self::fields_table();
		$referrers       = self::referrers_table();

		$sql_submissions = "CREATE TABLE {$submissions} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			full_name VARCHAR(191) NOT NULL DEFAULT '',
			mobile_number VARCHAR(32) NOT NULL DEFAULT '',
			email VARCHAR(191) NOT NULL DEFAULT '',
			subject VARCHAR(191) NOT NULL DEFAULT '',
			referred_by VARCHAR(191) NOT NULL DEFAULT '',
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			is_important TINYINT(1) NOT NULL DEFAULT 0,
			field_data LONGTEXT NULL,
			attachment_ids VARCHAR(255) NOT NULL DEFAULT '',
			lead_score SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
			lead_stage VARCHAR(20) NOT NULL DEFAULT 'new',
			source_data LONGTEXT NULL,
			is_rewarded TINYINT(1) NOT NULL DEFAULT 0,
			rewarded_at DATETIME NULL,
			ip_address VARCHAR(100) NOT NULL DEFAULT '',
			user_agent VARCHAR(255) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY referred_by (referred_by),
			KEY is_important (is_important),
			KEY lead_stage (lead_stage),
			KEY lead_score (lead_score),
			KEY is_rewarded (is_rewarded),
			KEY created_at (created_at)
		) {$charset_collate};";

		$sql_fields = "CREATE TABLE {$fields} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			field_key VARCHAR(64) NOT NULL,
			label VARCHAR(191) NOT NULL DEFAULT '',
			type VARCHAR(20) NOT NULL DEFAULT 'text',
			options LONGTEXT NULL,
			placeholder VARCHAR(191) NOT NULL DEFAULT '',
			help_text VARCHAR(255) NOT NULL DEFAULT '',
			width VARCHAR(10) NOT NULL DEFAULT 'full',
			is_required TINYINT(1) NOT NULL DEFAULT 0,
			is_enabled TINYINT(1) NOT NULL DEFAULT 1,
			is_locked TINYINT(1) NOT NULL DEFAULT 0,
			sort_order INT NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY field_key (field_key)
		) {$charset_collate};";

		$sql_referrers = "CREATE TABLE {$referrers} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			referrer_name VARCHAR(191) NOT NULL,
			payout_details TEXT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY referrer_name (referrer_name)
		) {$charset_collate};";

		dbDelta( $sql_submissions );
		dbDelta( $sql_referrers );
		dbDelta( $sql_fields );
		self::flush_cache();
	}

	/**
	 * Insert a submission.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Column => value pairs (already sanitized by caller).
	 * @return int|false Inserted ID, or false on failure.
	 */
	public static function insert_submission( $data ) {
		global $wpdb;

		$defaults = array(
			'full_name'      => '',
			'mobile_number'  => '',
			'email'          => '',
			'subject'        => '',
			'referred_by'    => '',
			'status'         => 'pending',
			'is_important'   => 0,
			'field_data'     => wp_json_encode( array() ),
			'attachment_ids' => '',
			'lead_score'     => 0,
			'lead_stage'     => 'new',
			'source_data'    => '',
			'is_rewarded'    => 0,
			'ip_address'     => '',
			'user_agent'     => '',
		);

		$data               = wp_parse_args( $data, $defaults );
		$data['created_at'] = current_time( 'mysql' );
		$data['updated_at'] = current_time( 'mysql' );

		$inserted = $wpdb->insert( self::submissions_table(), $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to the plugin's own table; the read cache is flushed below.
		self::flush_cache();

		return $inserted ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Update a submission by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $id   Submission ID.
	 * @param array $data Column => value pairs.
	 * @return int|false Number of rows updated, or false on failure.
	 */
	public static function update_submission( $id, $data ) {
		global $wpdb;

		$data['updated_at'] = current_time( 'mysql' );

		$updated = $wpdb->update( self::submissions_table(), $data, array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to the plugin's own table; the read cache is flushed below.
		self::flush_cache();

		return $updated;
	}

	/**
	 * Delete a submission by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Submission ID.
	 * @return int|false
	 */
	public static function delete_submission( $id ) {
		global $wpdb;

		$deleted = $wpdb->delete( self::submissions_table(), array( 'id' => (int) $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to the plugin's own table; the read cache is flushed below.
		self::flush_cache();

		return $deleted;
	}

	/**
	 * Get one submission row.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Submission ID.
	 * @return object|null
	 */
	public static function get_submission( $id ) {
		global $wpdb;

		$id  = (int) $id;
		$key = self::cache_key( 'submission', $id );
		$hit = wp_cache_get( $key, self::CACHE_GROUP );
		if ( is_array( $hit ) ) {
			return $hit['v'];
		}

		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', self::submissions_table(), $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table; result is cached with wp_cache_set() below.
		wp_cache_set( $key, array( 'v' => $row ), self::CACHE_GROUP );

		return $row;
	}

	/**
	 * Query submissions with search/filter/pagination support.
	 *
	 * Every filter is applied through a fixed SQL statement using the
	 * `( %s = '' OR column = %s )` pattern, so the statement text is always
	 * a literal and every value, including the table and ORDER BY column
	 * identifiers (`%i`), goes through $wpdb->prepare().
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     @type string $search    Free-text search across name/email/mobile/subject/field_data.
	 *     @type string $status    pending|approved|rejected|'' (all).
	 *     @type string $referrer  Exact referrer name filter.
	 *     @type string $important '1'|'0'|'' (all).
	 *     @type string $date_from Y-m-d.
	 *     @type string $date_to   Y-m-d.
	 *     @type string $orderby   Column name.
	 *     @type string $order     ASC|DESC.
	 *     @type int    $per_page  Rows per page.
	 *     @type int    $paged     1-indexed page number.
	 * }
	 * @return array { items: object[], total: int }
	 */
	public static function query_submissions( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'search'    => '',
			'status'    => '',
			'referrer'  => '',
			'important' => '',
			'stage'     => '',
			'rewarded'  => '',
			'score_min' => -1,
			'score_max' => -1,
			'date_from' => '',
			'date_to'   => '',
			'orderby'   => 'created_at',
			'order'     => 'DESC',
			'per_page'  => 20,
			'paged'     => 1,
		);
		$args = wp_parse_args( $args, $defaults );

		$table     = self::submissions_table();
		$search    = (string) $args['search'];
		$like      = '%' . $wpdb->esc_like( $search ) . '%';
		$status    = (string) $args['status'];
		$referrer  = (string) $args['referrer'];
		$important = '' === (string) $args['important'] ? -1 : (int) $args['important'];
		$stage     = (string) $args['stage'];
		$rewarded  = '' === (string) $args['rewarded'] ? -1 : (int) $args['rewarded'];
		$score_min = (int) $args['score_min'];
		$score_max = (int) $args['score_max'];
		$date_from = '' !== (string) $args['date_from'] ? $args['date_from'] . ' 00:00:00' : '';
		$date_to   = '' !== (string) $args['date_to'] ? $args['date_to'] . ' 23:59:59' : '';

		$allowed_orderby = array( 'id', 'full_name', 'status', 'referred_by', 'created_at', 'lead_score', 'lead_stage' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';

		$per_page = max( 1, (int) $args['per_page'] );
		$paged    = max( 1, (int) $args['paged'] );
		$offset   = ( $paged - 1 ) * $per_page;

		$where_args = array(
			$search,
			$like,
			$like,
			$like,
			$like,
			$like,
			$like,
			$status,
			$status,
			$referrer,
			$referrer,
			$important,
			$important,
			$rewarded,
			$rewarded,
			$stage,
			$stage,
			$score_min,
			$score_min,
			$score_max,
			$score_max,
			$date_from,
			$date_from,
			$date_to,
			$date_to,
		);

		$key = self::cache_key( 'query', array( $where_args, $orderby, $order, $per_page, $offset ) );
		$hit = wp_cache_get( $key, self::CACHE_GROUP );
		if ( is_array( $hit ) ) {
			return $hit;
		}

		$total = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table; result is cached with wp_cache_set() below.
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i
				WHERE ( %s = '' OR ( full_name LIKE %s OR email LIKE %s OR mobile_number LIKE %s OR subject LIKE %s OR referred_by LIKE %s OR field_data LIKE %s ) )
				AND ( %s = '' OR status = %s )
				AND ( %s = '' OR referred_by = %s )
				AND ( %d = -1 OR is_important = %d )
				AND ( %d = -1 OR is_rewarded = %d )
				AND ( %s = '' OR lead_stage = %s )
				AND ( %d < 0 OR lead_score >= %d )
				AND ( %d < 0 OR lead_score <= %d )
				AND ( %s = '' OR created_at >= %s )
				AND ( %s = '' OR created_at <= %s )",
				$table,
				$search,
				$like,
				$like,
				$like,
				$like,
				$like,
				$like,
				$status,
				$status,
				$referrer,
				$referrer,
				$important,
				$important,
				$rewarded,
				$rewarded,
				$stage,
				$stage,
				$score_min,
				$score_min,
				$score_max,
				$score_max,
				$date_from,
				$date_from,
				$date_to,
				$date_to
			)
		);

		$items = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table; result is cached with wp_cache_set() below.
			$wpdb->prepare(
				"SELECT * FROM %i
				WHERE ( %s = '' OR ( full_name LIKE %s OR email LIKE %s OR mobile_number LIKE %s OR subject LIKE %s OR referred_by LIKE %s OR field_data LIKE %s ) )
				AND ( %s = '' OR status = %s )
				AND ( %s = '' OR referred_by = %s )
				AND ( %d = -1 OR is_important = %d )
				AND ( %d = -1 OR is_rewarded = %d )
				AND ( %s = '' OR lead_stage = %s )
				AND ( %d < 0 OR lead_score >= %d )
				AND ( %d < 0 OR lead_score <= %d )
				AND ( %s = '' OR created_at >= %s )
				AND ( %s = '' OR created_at <= %s )
				ORDER BY CASE WHEN %s = 'ASC' THEN %i END ASC, CASE WHEN %s = 'DESC' THEN %i END DESC, id DESC
				LIMIT %d OFFSET %d",
				$table,
				$search,
				$like,
				$like,
				$like,
				$like,
				$like,
				$like,
				$status,
				$status,
				$referrer,
				$referrer,
				$important,
				$important,
				$rewarded,
				$rewarded,
				$stage,
				$stage,
				$score_min,
				$score_min,
				$score_max,
				$score_max,
				$date_from,
				$date_from,
				$date_to,
				$date_to,
				$order,
				$orderby,
				$order,
				$orderby,
				$per_page,
				$offset
			)
		);

		$result = array(
			'items' => $items ? $items : array(),
			'total' => $total,
		);
		wp_cache_set( $key, $result, self::CACHE_GROUP );

		return $result;
	}

	/**
	 * Number of submissions in each lead stage.
	 *
	 * @since 1.3.0
	 *
	 * @return int[] stage_key => count.
	 */
	public static function get_stage_counts() {
		global $wpdb;

		$key = self::cache_key( 'stage_counts' );
		$hit = wp_cache_get( $key, self::CACHE_GROUP );
		if ( is_array( $hit ) ) {
			return $hit['v'];
		}

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table; result is cached with wp_cache_set() below.
			$wpdb->prepare( 'SELECT lead_stage, COUNT(*) AS total FROM %i GROUP BY lead_stage', self::submissions_table() ),
			ARRAY_A
		);

		$counts = array();
		foreach ( (array) $rows as $row ) {
			$counts[ $row['lead_stage'] ] = (int) $row['total'];
		}
		wp_cache_set( $key, array( 'v' => $counts ), self::CACHE_GROUP );

		return $counts;
	}

	/**
	 * Mark a lead as rewarded (or not).
	 *
	 * @since 1.4.0
	 *
	 * @param int  $id      Submission ID.
	 * @param bool $reward  True to mark rewarded.
	 * @return int|false
	 */
	public static function set_rewarded( $id, $reward ) {
		return self::update_submission(
			$id,
			array(
				'is_rewarded' => $reward ? 1 : 0,
				'rewarded_at' => $reward ? current_time( 'mysql' ) : null,
			)
		);
	}

	/**
	 * Payout details saved for every referrer, keyed by lower-cased name.
	 *
	 * @since 1.4.0
	 *
	 * @return string[]
	 */
	public static function get_payout_map() {
		global $wpdb;

		$key = self::cache_key( 'payout_map' );
		$hit = wp_cache_get( $key, self::CACHE_GROUP );
		if ( is_array( $hit ) ) {
			return $hit['v'];
		}

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table; result is cached with wp_cache_set() below.
			$wpdb->prepare( 'SELECT referrer_name, payout_details FROM %i', self::referrers_table() ),
			ARRAY_A
		);

		$map = array();
		foreach ( (array) $rows as $row ) {
			$map[ strtolower( $row['referrer_name'] ) ] = (string) $row['payout_details'];
		}
		wp_cache_set( $key, array( 'v' => $map ), self::CACHE_GROUP );

		return $map;
	}

	/**
	 * Payout details for one referrer.
	 *
	 * @since 1.4.0
	 *
	 * @param string $name Referrer name.
	 * @return string
	 */
	public static function get_payout_details( $name ) {
		$map = self::get_payout_map();
		$key = strtolower( (string) $name );
		return isset( $map[ $key ] ) ? $map[ $key ] : '';
	}

	/**
	 * Save (insert or update) the payout details for a referrer.
	 *
	 * @since 1.4.0
	 *
	 * @param string $name    Referrer name.
	 * @param string $details Payout details typed by the site owner.
	 * @return bool
	 */
	public static function save_payout_details( $name, $details ) {
		global $wpdb;

		$name = (string) $name;
		if ( '' === $name ) {
			return false;
		}

		$result = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to the plugin's own table; the read cache is flushed below.
			$wpdb->prepare(
				'INSERT INTO %i (referrer_name, payout_details, updated_at) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE payout_details = VALUES(payout_details), updated_at = VALUES(updated_at)',
				self::referrers_table(),
				$name,
				(string) $details,
				current_time( 'mysql' )
			)
		);
		self::flush_cache();

		return false !== $result;
	}

	/**
	 * Aggregate referrer stats: total / approved / rejected / pending counts
	 * per distinct, non-empty referred_by value.
	 *
	 * @since 1.0.0
	 *
	 * @return array[] List of assoc arrays: referrer, total, approved, rejected, pending.
	 */
	public static function get_referrer_stats() {
		global $wpdb;

		$key = self::cache_key( 'referrer_stats' );
		$hit = wp_cache_get( $key, self::CACHE_GROUP );
		if ( is_array( $hit ) ) {
			return $hit['v'];
		}

		// One grouped query gives every referrer's totals, so screens never have
		// to count rows in PHP (that breaks as soon as a list is paginated).
		$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table; result is cached with wp_cache_set() below.
			$wpdb->prepare(
				"SELECT referred_by AS referrer,
					COUNT(*) AS total,
					SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved,
					SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected,
					SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
					SUM(CASE WHEN is_rewarded = 1 THEN 1 ELSE 0 END) AS rewarded
				FROM %i
				WHERE referred_by IS NOT NULL AND referred_by <> ''
				GROUP BY referred_by
				ORDER BY total DESC",
				self::submissions_table()
			),
			ARRAY_A
		);

		$results = $results ? $results : array();
		wp_cache_set( $key, array( 'v' => $results ), self::CACHE_GROUP );

		return $results;
	}

	/**
	 * Count of distinct referrers with at least one submission.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public static function count_referrers() {
		global $wpdb;

		$key = self::cache_key( 'referrer_count' );
		$hit = wp_cache_get( $key, self::CACHE_GROUP );
		if ( is_array( $hit ) ) {
			return $hit['v'];
		}

		$count = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table; result is cached with wp_cache_set() below.
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT referred_by) FROM %i WHERE referred_by IS NOT NULL AND referred_by <> ''",
				self::submissions_table()
			)
		);
		wp_cache_set( $key, array( 'v' => $count ), self::CACHE_GROUP );

		return $count;
	}

	/**
	 * Get submissions that exactly match an email address, oldest first.
	 *
	 * Used by the WordPress personal data exporter and eraser. Deliberately
	 * not cached: the eraser deletes rows between calls and must always see
	 * the live table.
	 *
	 * @since 1.2.0
	 *
	 * @param string $email    Email address.
	 * @param int    $per_page Rows per page.
	 * @param int    $paged    1-indexed page number.
	 * @return object[]
	 */
	public static function get_submissions_by_email( $email, $per_page = 50, $paged = 1 ) {
		global $wpdb;

		$per_page = max( 1, (int) $per_page );
		$offset   = ( max( 1, (int) $paged ) - 1 ) * $per_page;

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Privacy exporter/eraser must read live data, so this is intentionally uncached.
			$wpdb->prepare(
				'SELECT * FROM %i WHERE email = %s ORDER BY id ASC LIMIT %d OFFSET %d',
				self::submissions_table(),
				sanitize_email( $email ),
				$per_page,
				$offset
			)
		);

		return $rows ? $rows : array();
	}
}
