<?php
/**
 * Submissions list table.
 *
 * @package Advisers_Contacts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class ADVC_List_Table
 *
 * Renders the searchable, filterable, paginated submissions dashboard.
 * @since 1.0.0
 */
class ADVC_List_Table extends WP_List_Table {

	/**
	 * Constructor.
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'submission',
				'plural'   => 'submissions',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Table columns.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'          => '<input type="checkbox" />',
			'important'   => '',
			'full_name'   => __( 'Full Name', 'advisers-contacts' ),
			'mobile'      => __( 'Mobile', 'advisers-contacts' ),
			'email'       => __( 'Email', 'advisers-contacts' ),
			'interest'    => __( 'Interest', 'advisers-contacts' ),
			'lead'        => __( 'Lead Score', 'advisers-contacts' ),
			'stage'       => __( 'Stage', 'advisers-contacts' ),
			'referred_by' => __( 'Referred By', 'advisers-contacts' ),
			'rewarded'    => __( 'Reward', 'advisers-contacts' ),
			'status'      => __( 'Approval', 'advisers-contacts' ),
			'created_at'  => __( 'Date', 'advisers-contacts' ),
		);
	}

	/**
	 * Sortable columns.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'full_name'   => array( 'full_name', false ),
			'referred_by' => array( 'referred_by', false ),
			'lead'        => array( 'lead_score', true ),
			'stage'       => array( 'lead_stage', false ),
			'status'      => array( 'status', false ),
			'created_at'  => array( 'created_at', true ),
		);
	}

	/**
	 * Bulk actions.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_bulk_actions() {
		$stages = array();
		foreach ( ADVC_Helpers::stages() as $key => $label ) {
			/* translators: %s: lead stage name */
			$stages[ 'stage_' . $key ] = sprintf( __( 'Stage: %s', 'advisers-contacts' ), $label );
		}

		return $stages + array(
			'approve'     => __( 'Mark Approved', 'advisers-contacts' ),
			'reject'      => __( 'Mark Rejected', 'advisers-contacts' ),
			'rewarded'    => __( 'Mark Referrer Rewarded', 'advisers-contacts' ),
			'unrewarded'  => __( 'Unmark Rewarded', 'advisers-contacts' ),
			'important'   => __( 'Mark Important', 'advisers-contacts' ),
			'unimportant' => __( 'Unmark Important', 'advisers-contacts' ),
			'delete'      => __( 'Delete', 'advisers-contacts' ),
		);
	}

	/**
	 * Checkbox column.
	 *
	 * @since 1.0.0
	 * @param object $item Row.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="ids[]" value="%d" />', $item->id );
	}

	/**
	 * Star toggle column.
	 *
	 * @since 1.0.0
	 * @param object $item Row.
	 * @return string
	 */
	public function column_important( $item ) {
		$class = $item->is_important ? 'dashicons-star-filled' : 'dashicons-star-empty';
		return sprintf(
			'<button type="button" class="advc-star-toggle button-link" data-id="%d" aria-label="%s"><span class="dashicons %s"></span></button>',
			(int) $item->id,
			esc_attr__( 'Toggle important', 'advisers-contacts' ),
			esc_attr( $class )
		);
	}

	/**
	 * Full name column with row actions.
	 *
	 * @since 1.0.0
	 * @param object $item Row.
	 * @return string
	 */
	public function column_full_name( $item ) {
		$view_url = admin_url( 'admin.php?page=advc-view-submission&id=' . $item->id );

		$actions = array(
			'view'   => sprintf( '<a href="%s">%s</a>', esc_url( $view_url ), esc_html__( 'View', 'advisers-contacts' ) ),
			'edit'   => sprintf( '<a href="%s">%s</a>', esc_url( $view_url . '#advc-edit' ), esc_html__( 'Edit', 'advisers-contacts' ) ),
			'delete' => sprintf(
				'<a href="#" class="advc-delete-link" data-id="%d">%s</a>',
				(int) $item->id,
				esc_html__( 'Delete', 'advisers-contacts' )
			),
		);

		return sprintf(
			'<strong><a href="%s">%s</a></strong>%s',
			esc_url( $view_url ),
			esc_html( $item->full_name ? $item->full_name : __( '(no name)', 'advisers-contacts' ) ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Mobile column, rendered as a WhatsApp click-to-chat link.
	 *
	 * @since 1.0.0
	 * @param object $item Row.
	 * @return string
	 */
	public function column_mobile( $item ) {
		if ( empty( $item->mobile_number ) ) {
			return '&#8212;';
		}
		$url = ADVC_Helpers::whatsapp_url( $item->mobile_number );
		return sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer" class="advc-whatsapp-link"><span class="dashicons dashicons-whatsapp"></span> %s</a>',
			esc_url( $url ),
			esc_html( $item->mobile_number )
		);
	}

	/**
	 * Email column, rendered as a mailto: link.
	 *
	 * @since 1.0.0
	 * @param object $item Row.
	 * @return string
	 */
	public function column_email( $item ) {
		if ( empty( $item->email ) ) {
			return '&#8212;';
		}
		$url = ADVC_Helpers::mailto_url( $item->email );
		return sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $item->email ) );
	}

	/**
	 * Interest column: the company and the service the lead asked about.
	 *
	 * @since 1.3.0
	 * @param object $item Row.
	 * @return string
	 */
	public function column_interest( $item ) {
		$extra   = $item->field_data ? json_decode( $item->field_data, true ) : array();
		$extra   = is_array( $extra ) ? $extra : array();
		$company = isset( $extra['company_name'] ) ? $extra['company_name'] : '';
		$service = isset( $extra['service_interested'] ) ? $extra['service_interested'] : '';

		if ( '' === $company && '' === $service && '' !== (string) $item->subject ) {
			return esc_html( $item->subject );
		}
		if ( '' === $company && '' === $service ) {
			return '&#8212;';
		}

		$out = '';
		if ( '' !== $service ) {
			$out .= '<strong>' . esc_html( $service ) . '</strong>';
		}
		if ( '' !== $company ) {
			$out .= ( $out ? '<br />' : '' ) . esc_html( $company );
		}
		return $out;
	}

	/**
	 * Lead score column with a hot/warm/cold badge.
	 *
	 * @since 1.3.0
	 * @param object $item Row.
	 * @return string
	 */
	public function column_lead( $item ) {
		$temps = ADVC_Helpers::temperatures();
		$temp  = ADVC_Helpers::temperature( $item->lead_score );
		return sprintf(
			'<span class="advc-badge advc-temp-%1$s">%2$s</span> <span class="advc-score">%3$d</span>',
			esc_attr( $temp ),
			esc_html( $temps[ $temp ][0] ),
			(int) $item->lead_score
		);
	}

	/**
	 * Lead stage column.
	 *
	 * @since 1.3.0
	 * @param object $item Row.
	 * @return string
	 */
	public function column_stage( $item ) {
		$stages = ADVC_Helpers::stages();
		$label  = isset( $stages[ $item->lead_stage ] ) ? $stages[ $item->lead_stage ] : $item->lead_stage;
		return sprintf( '<span class="advc-badge advc-stage-%1$s">%2$s</span>', esc_attr( $item->lead_stage ), esc_html( $label ) );
	}

	/**
	 * Stage links shown above the table, with counts.
	 *
	 * @since 1.3.0
	 * @return string[]
	 */
	protected function get_views() {
		$current = isset( $_REQUEST['advc_stage'] ) ? sanitize_key( wp_unslash( $_REQUEST['advc_stage'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$counts  = ADVC_DB::get_stage_counts();
		$base    = admin_url( 'admin.php?page=advisers-contacts' );
		$views   = array();

		$views['all'] = sprintf(
			'<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>',
			esc_url( $base ),
			'' === $current ? ' class="current"' : '',
			esc_html__( 'All', 'advisers-contacts' ),
			array_sum( $counts )
		);

		foreach ( ADVC_Helpers::stages() as $key => $label ) {
			$views[ $key ] = sprintf(
				'<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>',
				esc_url( add_query_arg( 'advc_stage', $key, $base ) ),
				$current === $key ? ' class="current"' : '',
				esc_html( $label ),
				isset( $counts[ $key ] ) ? (int) $counts[ $key ] : 0
			);
		}

		return $views;
	}

	/**
	 * Reward column: only meaningful for leads that name a referrer.
	 *
	 * @since 1.4.0
	 * @param object $item Row.
	 * @return string
	 */
	public function column_rewarded( $item ) {
		if ( '' === (string) $item->referred_by ) {
			return '&#8212;';
		}
		if ( $item->is_rewarded ) {
			return '<span class="advc-badge advc-badge-approved">' . esc_html__( 'Rewarded', 'advisers-contacts' ) . '</span>';
		}
		return '<span class="advc-badge advc-badge-pending">' . esc_html__( 'Not yet', 'advisers-contacts' ) . '</span>';
	}

	/**
	 * Status column, rendered as a colored badge.
	 *
	 * @since 1.0.0
	 * @param object $item Row.
	 * @return string
	 */
	public function column_status( $item ) {
		$labels = ADVC_Helpers::statuses();
		$label  = isset( $labels[ $item->status ] ) ? $labels[ $item->status ] : $item->status;
		return sprintf( '<span class="advc-badge %s">%s</span>', esc_attr( ADVC_Helpers::status_badge_class( $item->status ) ), esc_html( $label ) );
	}

	/**
	 * Default renderer for plain text columns.
	 *
	 * @since 1.0.0
	 * @param object $item        Row.
	 * @param string $column_name Column key.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'referred_by':
				return $item->referred_by ? esc_html( $item->referred_by ) : '&#8212;';
			case 'created_at':
				return esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $item->created_at ) );
			default:
				return '';
		}
	}

	/**
	 * Extra table nav: status/referrer/date filters.
	 *
	 * @since 1.0.0
	 * @param string $which top|bottom.
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$status    = isset( $_REQUEST['advc_status'] ) ? sanitize_key( wp_unslash( $_REQUEST['advc_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$referrer  = isset( $_REQUEST['advc_referrer'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['advc_referrer'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$date_from = isset( $_REQUEST['advc_date_from'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['advc_date_from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$date_to   = isset( $_REQUEST['advc_date_to'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['advc_date_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$rewarded = isset( $_REQUEST['advc_rewarded'] ) ? sanitize_key( wp_unslash( $_REQUEST['advc_rewarded'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$stage    = isset( $_REQUEST['advc_stage'] ) ? sanitize_key( wp_unslash( $_REQUEST['advc_stage'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$temp  = isset( $_REQUEST['advc_temp'] ) ? sanitize_key( wp_unslash( $_REQUEST['advc_temp'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$referrers = ADVC_DB::get_referrer_stats();

		$export_url = wp_nonce_url(
			add_query_arg(
				array_filter(
					array(
						'action'         => 'advc_export_csv',
						's'              => isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						'advc_status'    => $status,
						'advc_stage'     => $stage,
						'advc_temp'      => $temp,
						'advc_rewarded'  => $rewarded,
						'advc_referrer'  => $referrer,
						'advc_date_from' => $date_from,
						'advc_date_to'   => $date_to,
					),
					static function ( $value ) {
						return '' !== $value;
					}
				),
				admin_url( 'admin-post.php' )
			),
			'advc_export'
		);
		?>
		<div class="alignleft actions advc-filters">
			<select name="advc_status">
				<option value=""><?php esc_html_e( 'All statuses', 'advisers-contacts' ); ?></option>
				<?php foreach ( ADVC_Helpers::statuses() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>

			<select name="advc_temp">
				<option value=""><?php esc_html_e( 'All lead scores', 'advisers-contacts' ); ?></option>
				<?php foreach ( ADVC_Helpers::temperatures() as $key => $info ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $temp, $key ); ?>><?php echo esc_html( $info[0] ); ?></option>
				<?php endforeach; ?>
			</select>

			<select name="advc_rewarded">
				<option value=""><?php esc_html_e( 'All reward status', 'advisers-contacts' ); ?></option>
				<option value="1" <?php selected( $rewarded, '1' ); ?>><?php esc_html_e( 'Rewarded', 'advisers-contacts' ); ?></option>
				<option value="0" <?php selected( $rewarded, '0' ); ?>><?php esc_html_e( 'Not yet rewarded', 'advisers-contacts' ); ?></option>
			</select>

			<select name="advc_referrer">
				<option value=""><?php esc_html_e( 'All referrers', 'advisers-contacts' ); ?></option>
				<?php foreach ( $referrers as $row ) : ?>
					<option value="<?php echo esc_attr( $row['referrer'] ); ?>" <?php selected( $referrer, $row['referrer'] ); ?>>
						<?php echo esc_html( $row['referrer'] ); ?> (<?php echo (int) $row['total']; ?>)
					</option>
				<?php endforeach; ?>
			</select>

			<label class="screen-reader-text" for="advc_date_from"><?php esc_html_e( 'From date', 'advisers-contacts' ); ?></label>
			<input type="date" name="advc_date_from" value="<?php echo esc_attr( $date_from ); ?>" placeholder="<?php esc_attr_e( 'From', 'advisers-contacts' ); ?>" />

			<label class="screen-reader-text" for="advc_date_to"><?php esc_html_e( 'To date', 'advisers-contacts' ); ?></label>
			<input type="date" name="advc_date_to" value="<?php echo esc_attr( $date_to ); ?>" placeholder="<?php esc_attr_e( 'To', 'advisers-contacts' ); ?>" />

			<?php submit_button( __( 'Filter', 'advisers-contacts' ), '', 'filter_action', false ); ?>
			<a href="<?php echo esc_url( $export_url ); ?>" class="button"><?php esc_html_e( 'Export CSV', 'advisers-contacts' ); ?></a>
			<input type="hidden" name="advc_stage" value="<?php echo esc_attr( $stage ); ?>" />
		</div>
		<?php
	}

	/**
	 * Fetch, filter, sort and paginate rows.
	 * @since 1.0.0
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page = (int) get_option( 'advc_per_page', 20 );

		$args = array(
			'search'    => isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'status'    => isset( $_REQUEST['advc_status'] ) ? sanitize_key( wp_unslash( $_REQUEST['advc_status'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'rewarded'  => isset( $_REQUEST['advc_rewarded'] ) ? sanitize_key( wp_unslash( $_REQUEST['advc_rewarded'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'stage'     => isset( $_REQUEST['advc_stage'] ) ? sanitize_key( wp_unslash( $_REQUEST['advc_stage'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'referrer'  => isset( $_REQUEST['advc_referrer'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['advc_referrer'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'date_from' => isset( $_REQUEST['advc_date_from'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['advc_date_from'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'date_to'   => isset( $_REQUEST['advc_date_to'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['advc_date_to'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'orderby'   => isset( $_REQUEST['orderby'] ) ? sanitize_key( wp_unslash( $_REQUEST['orderby'] ) ) : 'created_at', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'order'     => isset( $_REQUEST['order'] ) ? sanitize_key( wp_unslash( $_REQUEST['order'] ) ) : 'DESC', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'per_page'  => $per_page,
			'paged'     => $this->get_pagenum(),
		);

		$temps = ADVC_Helpers::temperatures();
		$temp  = isset( $_REQUEST['advc_temp'] ) ? sanitize_key( wp_unslash( $_REQUEST['advc_temp'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $temps[ $temp ] ) ) {
			$args['score_min'] = $temps[ $temp ][1];
			$args['score_max'] = $temps[ $temp ][2];
		}

		$result = ADVC_DB::query_submissions( $args );

		$this->items = $result['items'];

		$this->set_pagination_args(
			array(
				'total_items' => $result['total'],
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $result['total'] / $per_page ),
			)
		);
	}
}
