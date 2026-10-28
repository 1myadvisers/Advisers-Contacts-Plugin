<?php
/**
 * Front-end shortcode and guest submission handling for Advisers Contacts.
 *
 * @package Advisers_Contacts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ADVC_Frontend
 * @since 1.0.0
 */
class ADVC_Frontend {

	/** @var ADVC_Frontend|null */
	private static $instance = null;

	/** Transient prefix used for guest flash (validation error) messages. */
	const FLASH_PREFIX = 'advc_flash_';

	/**
	 * Get singleton instance.
	 *
	 * @since 1.0.0
	 * @return ADVC_Frontend
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
		add_shortcode( 'advisers_contact_form', array( $this, 'render_shortcode' ) );

		add_action( 'admin_post_advc_submit_contact_form', array( $this, 'handle_submission' ) );
		add_action( 'admin_post_nopriv_advc_submit_contact_form', array( $this, 'handle_submission' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue front-end styles, only on pages that carry the shortcode.
	 * @since 1.0.0
	 */
	public function enqueue_assets() {
		// Register once; the files are only printed when something enqueues them.
		wp_register_style( 'advc-frontend', ADVC_URL . 'assets/css/frontend.css', array(), ADVC_VERSION );
		wp_register_script( 'advc-frontend', ADVC_URL . 'assets/js/frontend.js', array( 'jquery' ), ADVC_VERSION, true );

		// Fast path: the shortcode is in the main post content, so the CSS can
		// go in <head>. Anywhere else (widgets, page builders, templates) the
		// shortcode callback enqueues the same handles when it renders.
		$post = get_post();
		if ( is_singular() && $post && has_shortcode( $post->post_content, 'advisers_contact_form' ) ) {
			wp_enqueue_style( 'advc-frontend' );
			wp_enqueue_script( 'advc-frontend' );
		}
	}

	/**
	 * Render the [advisers_contact_form] shortcode.
	 *
	 * Optional attributes: title, intro, theme (blue|emerald|violet|slate).
	 * Anything left out falls back to Advisers Contacts -> Settings.
	 *
	 * @since 1.0.0
	 * @since 1.3.0 Card layout, themes, title/intro and campaign-source capture.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'title' => '',
				'intro' => '',
				'theme' => '',
				'share' => '',
			),
			$atts,
			'advisers_contact_form'
		);

		$title = '' !== $atts['title'] ? $atts['title'] : get_option( 'advc_form_title', ADVC_Helpers::default_text( 'title' ) );
		$intro = '' !== $atts['intro'] ? $atts['intro'] : get_option( 'advc_form_intro', ADVC_Helpers::default_text( 'intro' ) );
		$theme = '' !== $atts['theme'] ? $atts['theme'] : get_option( 'advc_theme', 'blue' );
		$theme = array_key_exists( $theme, ADVC_Helpers::themes() ) ? $theme : 'blue';

		$show_share = '' !== $atts['share'] ? ! in_array( strtolower( $atts['share'] ), array( 'no', 'false', '0' ), true ) : (bool) get_option( 'advc_share_enabled', '1' );

		$fields = ADVC_Fields::get_fields( true );
		$flash  = $this->get_flash();

		// Covers shortcodes rendered outside the main post content.
		wp_enqueue_style( 'advc-frontend' );
		wp_enqueue_script( 'advc-frontend' );

		ob_start();
		?>
		<div class="advc-form-wrap advc-theme-<?php echo esc_attr( $theme ); ?>">
			<div class="advc-card">
				<?php if ( $title || $intro ) : ?>
					<div class="advc-card-head">
						<?php if ( $title ) : ?>
							<h2 class="advc-title"><?php echo esc_html( $title ); ?></h2>
						<?php endif; ?>
						<?php if ( $intro ) : ?>
							<p class="advc-intro"><?php echo esc_html( $intro ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $flash['success'] ) ) : ?>
					<div class="advc-notice advc-notice-success" role="status"><?php echo esc_html( $flash['success'] ); ?></div>
				<?php endif; ?>

				<?php if ( ! empty( $flash['error'] ) ) : ?>
					<div class="advc-notice advc-notice-error" role="alert"><?php echo esc_html( $flash['error'] ); ?></div>
				<?php endif; ?>

				<?php if ( empty( $flash['success'] ) ) : ?>
				<form class="advc-form" method="post" enctype="multipart/form-data"
					action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="advc_submit_contact_form" />
					<?php wp_nonce_field( 'advc_submit_contact_form', 'advc_nonce' ); ?>
					<p class="advc-hp-field" aria-hidden="true">
						<label for="advc_website">Website</label>
						<input type="text" id="advc_website" name="advc_website" tabindex="-1" autocomplete="off" />
					</p>
					<?php foreach ( ADVC_Helpers::tracking_keys() as $tracking_key ) : ?>
						<input type="hidden" name="<?php echo esc_attr( 'advc_src_' . $tracking_key ); ?>" value="" data-advc-src="<?php echo esc_attr( $tracking_key ); ?>" />
					<?php endforeach; ?>

					<div class="advc-grid">
					<?php
					$has_submit = false;
					foreach ( $fields as $field ) {
						if ( 'submit' === $field->type ) {
							$has_submit = true;
						}
						$this->render_field( $field, isset( $flash['values'][ $field->field_key ] ) ? $flash['values'][ $field->field_key ] : '' );
					}
					?>

					<?php if ( ! $has_submit ) : ?>
						<div class="advc-field advc-w-full advc-field-submit"><button type="submit" class="advc-submit-btn"><?php esc_html_e( 'Send Message', 'advisers-contacts' ); ?></button></div>
					<?php endif; ?>
					</div>

					<p class="advc-note"><?php esc_html_e( 'We respect your privacy. Your details are used only to respond to your enquiry.', 'advisers-contacts' ); ?></p>
				</form>
				<?php endif; ?>
			</div>

			<?php
			if ( $show_share ) {
				$this->render_share_bar( $title );
			}
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Share buttons for the page that carries the form: WhatsApp, email,
	 * Facebook, LinkedIn, X and copy-link. Plain links; nothing is sent to
	 * any service until the visitor clicks one.
	 *
	 * @since 1.3.1
	 *
	 * @param string $title Text to share alongside the link.
	 */
	private function render_share_bar( $title ) {
		$url  = get_permalink();
		$url  = $url ? $url : home_url( '/' );
		$text = $title ? $title : get_bloginfo( 'name' );

		$links = array(
			'whatsapp' => array( __( 'WhatsApp', 'advisers-contacts' ), 'https://wa.me/?text=' . rawurlencode( $text . ' ' . $url ) ),
			'email'    => array( __( 'Email', 'advisers-contacts' ), 'mailto:?subject=' . rawurlencode( $text ) . '&body=' . rawurlencode( $url ) ),
			'facebook' => array( __( 'Facebook', 'advisers-contacts' ), 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url ) ),
			'linkedin' => array( __( 'LinkedIn', 'advisers-contacts' ), 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $url ) ),
			'x'        => array( __( 'X', 'advisers-contacts' ), 'https://twitter.com/intent/tweet?text=' . rawurlencode( $text ) . '&url=' . rawurlencode( $url ) ),
		);
		?>
		<div class="advc-share">
			<span class="advc-share-label"><?php esc_html_e( 'Share this form', 'advisers-contacts' ); ?></span>
			<?php foreach ( $links as $key => $link ) : ?>
				<a class="advc-share-btn advc-share-<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( $link[1], array( 'https', 'mailto' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $link[0] ); ?></a>
			<?php endforeach; ?>
			<button type="button" class="advc-share-btn advc-share-copy" data-url="<?php echo esc_url( $url ); ?>" data-copied="<?php esc_attr_e( 'Link copied!', 'advisers-contacts' ); ?>"><?php esc_html_e( 'Copy link', 'advisers-contacts' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Render a single field.
	 *
	 * @since 1.0.0
	 * @since 1.3.0 Div-based, width aware, with help text and radio/checkbox support.
	 * @param object $field Field definition row.
	 * @param string $value Previously submitted value (repopulated on validation error).
	 */
	private function render_field( $field, $value = '' ) {
		if ( ! ADVC_Fields::is_usable( $field ) ) {
			return;
		}

		$value = (string) $value;
		$width = isset( $field->width ) && 'half' === $field->width ? 'half' : 'full';
		$wrap  = 'advc-field advc-w-' . $width . ' advc-field-' . esc_attr( $field->type );

		if ( 'submit' === $field->type ) {
			$html = '<div class="advc-field advc-w-full advc-field-submit"><button type="submit" class="advc-submit-btn">' . esc_html( $field->label ) . '</button></div>';
			echo wp_kses( $html, self::allowed_form_html() );
			return;
		}

		$name        = esc_attr( $field->field_key );
		$id          = esc_attr( 'advc-' . $field->field_key );
		$required    = $field->is_required ? ' required' : '';
		$req_mark    = $field->is_required ? ' <span class="advc-required" aria-hidden="true">*</span>' : '';
		$placeholder = ! empty( $field->placeholder ) ? ' placeholder="' . esc_attr( $field->placeholder ) . '"' : '';
		$help        = ! empty( $field->help_text ) ? '<small class="advc-help">' . esc_html( $field->help_text ) . '</small>' : '';

		$html = '<div class="' . $wrap . '">';

		switch ( $field->type ) {
			case 'checkbox':
				$html .= '<label class="advc-check" for="' . $id . '"><input type="checkbox" id="' . $id . '" name="' . $name . '" value="1"' . ( 'Yes' === $value ? ' checked' : '' ) . $required . ' /><span>' . esc_html( $field->label ) . $req_mark . '</span></label>';
				break;

			case 'radio':
				$html .= '<span class="advc-label">' . esc_html( $field->label ) . $req_mark . '</span><div class="advc-choices" role="radiogroup">';
				foreach ( ADVC_Fields::get_options( $field ) as $option ) {
					$html .= '<label class="advc-choice"><input type="radio" name="' . $name . '" value="' . esc_attr( $option ) . '"' . ( (string) $option === $value ? ' checked' : '' ) . $required . ' /><span>' . esc_html( $option ) . '</span></label>';
				}
				$html .= '</div>';
				break;

			case 'textarea':
				$html .= '<label class="advc-label" for="' . $id . '">' . esc_html( $field->label ) . $req_mark . '</label>';
				$html .= '<textarea id="' . $id . '" name="' . $name . '" rows="4"' . $placeholder . $required . '>' . esc_textarea( $value ) . '</textarea>';
				break;

			case 'select':
				$html .= '<label class="advc-label" for="' . $id . '">' . esc_html( $field->label ) . $req_mark . '</label>';
				$html .= '<select id="' . $id . '" name="' . $name . '"' . $required . '>';
				$html .= '<option value="">' . esc_html__( '— Select —', 'advisers-contacts' ) . '</option>';
				foreach ( ADVC_Fields::get_options( $field ) as $option ) {
					$html .= '<option value="' . esc_attr( $option ) . '"' . ( (string) $option === $value ? ' selected' : '' ) . '>' . esc_html( $option ) . '</option>';
				}
				$html .= '</select>';
				break;

			case 'file':
				$html .= '<label class="advc-label" for="' . $id . '">' . esc_html( $field->label ) . $req_mark . '</label>';
				$html .= '<input type="file" id="' . $id . '" name="' . $name . '[]" multiple' . $required . ' />';
				break;

			case 'date':
				$html .= '<label class="advc-label" for="' . $id . '">' . esc_html( $field->label ) . $req_mark . '</label>';
				$html .= '<input type="date" id="' . $id . '" name="' . $name . '" value="' . esc_attr( $value ) . '"' . $required . ' />';
				break;

			case 'email':
			case 'tel':
			case 'url':
				$html .= '<label class="advc-label" for="' . $id . '">' . esc_html( $field->label ) . $req_mark . '</label>';
				$html .= '<input type="' . esc_attr( $field->type ) . '" id="' . $id . '" name="' . $name . '" value="' . esc_attr( $value ) . '"' . $placeholder . $required . ' />';
				break;

			default:
				$html .= '<label class="advc-label" for="' . $id . '">' . esc_html( $field->label ) . $req_mark . '</label>';
				$html .= '<input type="text" id="' . $id . '" name="' . $name . '" value="' . esc_attr( $value ) . '"' . $placeholder . $required . ' />';
				break;
		}

		$html .= $help . '</div>';

		echo wp_kses( $html, self::allowed_form_html() );
	}

	/**
	 * HTML elements and attributes a rendered form field may contain. Used
	 * with wp_kses() so every field is escaped on output.
	 *
	 * @since 1.2.1
	 *
	 * @return array
	 */
	private static function allowed_form_html() {
		return array(
			'div'      => array(
				'class' => true,
				'role'  => true,
			),
			'span'     => array(
				'class'       => true,
				'aria-hidden' => true,
			),
			'small'    => array( 'class' => true ),
			'label'    => array(
				'for'   => true,
				'class' => true,
			),
			'button'   => array(
				'type'  => true,
				'class' => true,
			),
			'textarea' => array(
				'id'          => true,
				'name'        => true,
				'rows'        => true,
				'required'    => true,
				'placeholder' => true,
			),
			'select'   => array(
				'id'       => true,
				'name'     => true,
				'required' => true,
			),
			'option'   => array(
				'value'    => true,
				'selected' => true,
			),
			'input'    => array(
				'type'        => true,
				'id'          => true,
				'name'        => true,
				'value'       => true,
				'placeholder' => true,
				'required'    => true,
				'multiple'    => true,
				'checked'     => true,
			),
		);
	}

	/**
	 * Handle POST from the front-end form (guests and logged-in users).
	 * @since 1.0.0
	 */
	public function handle_submission() {
		if ( ! isset( $_POST['advc_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['advc_nonce'] ) ), 'advc_submit_contact_form' ) ) {
			$this->fail( __( 'Security check failed. Please refresh the page and try again.', 'advisers-contacts' ) );
			return;
		}

		// Honeypot: a filled hidden field means a bot filled every input.
		if ( ! empty( $_POST['advc_website'] ) ) {
			$this->fail( __( 'Submission rejected.', 'advisers-contacts' ) );
		}

		if ( $this->is_rate_limited() ) {
			$this->fail( __( 'You are submitting too frequently. Please try again later.', 'advisers-contacts' ) );
		}

		$fields    = ADVC_Fields::get_fields( true );
		$values    = array();
		$errors    = array();
		$core      = array();
		$extra     = array();
		$file_keys = array();

		foreach ( $fields as $field ) {
			if ( 'submit' === $field->type ) {
				continue;
			}

			if ( ! ADVC_Fields::is_usable( $field ) ) {
				continue; // A dropdown with no choices is not shown, so it cannot be required.
			}

			if ( 'file' === $field->type ) {
				$file_keys[] = $field->field_key; // Handled separately below.
				continue;
			}

			$raw = isset( $_POST[ $field->field_key ] ) ? wp_unslash( $_POST[ $field->field_key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Sanitized by type in ADVC_Helpers::sanitize_field_value() on the next line; nonce verified above.
			$val = ADVC_Helpers::sanitize_field_value( $field->type, is_array( $raw ) ? '' : $raw );

			// A choice must be one of the options the admin configured.
			if ( in_array( $field->type, array( 'select', 'radio' ), true ) && '' !== $val && ! in_array( $val, ADVC_Fields::get_options( $field ), true ) ) {
				$val = '';
			}

			if ( $field->is_required && '' === $val ) {
				/* translators: %s: field label */
				$errors[] = sprintf( __( '%s is required.', 'advisers-contacts' ), $field->label );
			}

			if ( 'email' === $field->type && '' !== $val && ! is_email( $val ) ) {
				$errors[] = __( 'Please enter a valid email address.', 'advisers-contacts' );
			}

			$values[ $field->field_key ] = $val;

			if ( in_array( $field->field_key, array( 'full_name', 'mobile_number', 'email', 'subject', 'referred_by' ), true ) ) {
				$core[ $field->field_key ] = $val;
			} else {
				$extra[ $field->field_key ] = $val;
			}
		}

		if ( $errors ) {
			$this->fail( implode( ' ', $errors ), $values );
		}

		$attachment_ids = $this->handle_uploads( $file_keys );

		$data = array_merge(
			$core,
			array(
				'field_data'     => wp_json_encode( $extra ),
				'attachment_ids' => implode( ',', $attachment_ids ),
				'lead_score'     => ADVC_Helpers::compute_lead_score( $core, $extra ),
				'lead_stage'     => 'new',
				'source_data'    => wp_json_encode( $this->collect_source() ),
				'status'         => 'pending',
				'ip_address'     => $this->get_client_ip(),
				'user_agent'     => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			)
		);

		$id = ADVC_DB::insert_submission( $data );

		if ( ! $id ) {
			$this->fail( __( 'Something went wrong while saving your submission. Please try again.', 'advisers-contacts' ), $values );
		}

		/**
		 * Fires after a contact form submission has been stored.
		 *
		 * @param int   $id   New submission ID.
		 * @param array $data Stored submission data.
		 */
		do_action( 'advc_after_submission', $id, $data );

		$this->count_submission();

		$this->set_flash( array( 'success' => get_option( 'advc_success_message', ADVC_Helpers::default_text( 'success' ) ) ) );
		$this->redirect_back();
	}

	/**
	 * Marketing-source details sent along with the form (UTM parameters, ad
	 * click IDs, landing page and referring site), sanitized and length-capped.
	 *
	 * Called only from handle_submission() after its nonce check has passed.
	 *
	 * @since 1.3.0
	 * @return array
	 */
	private function collect_source() {
		$source = array();

		foreach ( ADVC_Helpers::tracking_keys() as $key ) {
			$name  = 'advc_src_' . $key;
			$value = isset( $_POST[ $name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_submission(), the only caller.

			if ( in_array( $key, array( 'landing_page', 'referrer' ), true ) ) {
				$value = esc_url_raw( $value );
			}

			$value = substr( $value, 0, 255 );
			if ( '' !== $value ) {
				$source[ $key ] = $value;
			}
		}

		// No-JavaScript fallback: the page the form was posted from is the landing page.
		if ( empty( $source['landing_page'] ) ) {
			$referer = wp_get_referer();
			if ( $referer ) {
				$source['landing_page'] = substr( esc_url_raw( $referer ), 0, 255 );
			}
		}

		return $source;
	}

	/**
	 * Move uploaded files into the Media Library and return their IDs.
	 *
	 * Called only from handle_submission() after its nonce check has passed.
	 *
	 * @since 1.0.0
	 * @since 1.2.1 Handles every enabled file field, not only `upload_files`.
	 *
	 * @param string[] $file_keys Field keys of the enabled file-type fields.
	 * @return int[]
	 */
	private function handle_uploads( $file_keys ) {
		if ( empty( $file_keys ) ) {
			return array();
		}

		// These two admin files aren't loaded on the front end. file.php gives us
		// wp_handle_upload() and image.php gives us wp_generate_attachment_metadata();
		// both are called straight away below.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$allowed_types = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'gif'          => 'image/gif',
			'pdf'          => 'application/pdf',
			'doc'          => 'application/msword',
			'docx'         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'xls'          => 'application/vnd.ms-excel',
			'xlsx'         => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'zip'          => 'application/zip',
		);
		$max_bytes     = (int) apply_filters( 'advc_max_upload_bytes', 5 * MB_IN_BYTES );

		$ids = array();

		foreach ( $file_keys as $file_key ) {
			if ( empty( $_FILES[ $file_key ]['name'] ) || ! is_array( $_FILES[ $file_key ]['name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_submission(), the only caller.
				continue;
			}

			$names  = array_map( 'sanitize_file_name', wp_unslash( $_FILES[ $file_key ]['name'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified in handle_submission(); names are sanitized here.
			$types  = isset( $_FILES[ $file_key ]['type'] ) ? array_map( 'sanitize_mime_type', wp_unslash( (array) $_FILES[ $file_key ]['type'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified in handle_submission(); types are sanitized here.
			$tmps   = isset( $_FILES[ $file_key ]['tmp_name'] ) ? (array) $_FILES[ $file_key ]['tmp_name'] : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Server-generated temp path; validated by wp_handle_upload() via is_uploaded_file().
			$errors = isset( $_FILES[ $file_key ]['error'] ) ? array_map( 'absint', (array) $_FILES[ $file_key ]['error'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_submission().
			$sizes  = isset( $_FILES[ $file_key ]['size'] ) ? array_map( 'absint', (array) $_FILES[ $file_key ]['size'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_submission().

			foreach ( $names as $i => $name ) {
				if ( '' === $name || ! isset( $tmps[ $i ], $errors[ $i ], $sizes[ $i ] ) || UPLOAD_ERR_NO_FILE === $errors[ $i ] ) {
					continue;
				}
				if ( $sizes[ $i ] > $max_bytes ) {
					continue;
				}

				$file = array(
					'name'     => $name,
					'type'     => isset( $types[ $i ] ) ? $types[ $i ] : '',
					'tmp_name' => $tmps[ $i ],
					'error'    => $errors[ $i ],
					'size'     => $sizes[ $i ],
				);

				// Restrict this upload to the allow-list without touching the
				// site-wide 'upload_mimes' filter used by other plugins.
				$results = wp_handle_upload(
					$file,
					array(
						'test_form' => false,
						'mimes'     => $allowed_types,
					)
				);

				if ( empty( $results['file'] ) ) {
					continue;
				}

				$attach_id = wp_insert_attachment(
					array(
						'post_mime_type' => $results['type'],
						'post_title'     => sanitize_file_name( pathinfo( $results['file'], PATHINFO_FILENAME ) ),
						'post_status'    => 'inherit',
					),
					$results['file']
				);
				if ( ! is_wp_error( $attach_id ) && $attach_id ) {
					wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $results['file'] ) );
					$ids[] = $attach_id;
				}
			}
		}

		return $ids;
	}

	/**
	 * Simple per-IP submission rate limit (5 per hour by default) to slow down abuse.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function is_rate_limited() {
		$count = (int) get_transient( $this->rate_limit_key() );

		// Allow filtering so a busy site can raise the limit.
		return $count >= (int) apply_filters( 'advc_rate_limit', 5 );
	}

	/**
	 * Count one stored submission against the visitor's hourly allowance.
	 * Called after a successful insert, so typing mistakes never lock
	 * someone out.
	 *
	 * @since 1.4.8
	 */
	private function count_submission() {
		$key = $this->rate_limit_key();
		set_transient( $key, (int) get_transient( $key ) + 1, HOUR_IN_SECONDS );
	}

	/**
	 * Transient key for the current visitor's rate limit counter.
	 *
	 * @since 1.4.8
	 * @return string
	 */
	private function rate_limit_key() {
		return 'advc_rl_' . md5( $this->get_client_ip() );
	}

	/**
	 * Best-effort client IP lookup.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function get_client_ip() {
		// REMOTE_ADDR is the only value a visitor can't fake with a header. Sites
		// behind a proxy should fix it at server level instead of trusting
		// X-Forwarded-For here.
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return false !== filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}

	/**
	 * Store a validation failure message + submitted values, then redirect
	 * back to the referring page so guests see inline errors.
	 *
	 * @since 1.0.0
	 * @param string $message Error message.
	 * @param array  $values  Previously entered values to repopulate.
	 */
	private function fail( $message, $values = array() ) {
		$this->set_flash( array(
			'error'  => $message,
			'values' => $values,
		) );
		$this->redirect_back();
	}

	/**
	 * Persist a flash message for this guest via a cookie-keyed transient.
	 *
	 * @since 1.0.0
	 * @param array $data Flash payload.
	 */
	private function set_flash( $data ) {
		$token = isset( $_COOKIE['advc_flash_token'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['advc_flash_token'] ) ) : '';
		if ( ! $token ) {
			$token = wp_generate_password( 20, false );
			setcookie(
				'advc_flash_token',
				$token,
				array(
					'expires'  => time() + 60,
					'path'     => COOKIEPATH ? COOKIEPATH : '/',
					'domain'   => COOKIE_DOMAIN,
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				)
			);
		}
		set_transient( self::FLASH_PREFIX . $token, $data, MINUTE_IN_SECONDS );
	}

	/**
	 * Retrieve and clear this guest's flash message.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_flash() {
		$token = isset( $_COOKIE['advc_flash_token'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['advc_flash_token'] ) ) : '';
		if ( ! $token ) {
			return array();
		}
		$data = get_transient( self::FLASH_PREFIX . $token );
		delete_transient( self::FLASH_PREFIX . $token );
		return $data ? $data : array();
	}

	/**
	 * Redirect back to the page the form was submitted from.
	 * @since 1.0.0
	 */
	private function redirect_back() {
		$referer = wp_get_referer();
		wp_safe_redirect( $referer ? $referer : home_url( '/' ) );
		exit;
	}
}
