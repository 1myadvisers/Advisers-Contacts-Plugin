/* global jQuery, advcAdmin */
( function ( $ ) {
	'use strict';

	$( function () {

		/* ---------------------------------------------------------
		 * Field builder: drag-drop reordering
		 * ------------------------------------------------------- */
		var $fieldList = $( '#advc-field-list' );

		if ( $fieldList.length ) {
			$fieldList.sortable( {
				handle: '.advc-drag-handle',
				axis: 'y',
				items: '> li',
				update: function () {
					// no-op; order is read from the DOM on submit.
				}
			} );

			$( '#advc-field-order-form' ).on( 'submit', function () {
				var rows = [];
				var step = 10;

				$fieldList.find( '.advc-field-row' ).each( function ( index ) {
					var $row = $( this );
					rows.push( {
						id: $row.data( 'id' ),
						sort_order: ( index + 1 ) * step,
						label: $row.find( '.advc-field-label' ).val(),
						is_enabled: $row.find( '.advc-field-enabled' ).is( ':checked' ) ? 1 : 0,
						is_required: $row.find( '.advc-field-required' ).is( ':checked' ) ? 1 : 0,
						width: $row.find( '.advc-field-width' ).val(),
						placeholder: $row.find( '.advc-field-placeholder' ).length ? $row.find( '.advc-field-placeholder' ).val() : undefined,
						help_text: $row.find( '.advc-field-help' ).length ? $row.find( '.advc-field-help' ).val() : undefined,
						options: $row.find( '.advc-field-options' ).length ? $row.find( '.advc-field-options' ).val() : undefined
					} );
				} );

				$( '#advc-fields-json' ).val( JSON.stringify( rows ) );
			} );
		}

		/* ---------------------------------------------------------
		 * Field builder: per-field settings panel + confirmations
		 * ------------------------------------------------------- */
		$( document ).on( 'click', '.advc-field-settings-toggle', function () {
			var $btn   = $( this );
			var $extra = $btn.closest( '.advc-field-row' ).find( '.advc-field-extra' );
			var open   = ! $extra.prop( 'hidden' );

			$extra.prop( 'hidden', open );
			$btn.attr( 'aria-expanded', open ? 'false' : 'true' );
		} );

		$( document ).on( 'submit', '.advc-confirm-form', function ( e ) {
			var kind = $( this ).data( 'confirm' );
			var text = 'preset' === kind ? advcAdmin.i18n.confirmPreset : advcAdmin.i18n.confirmField;

			if ( ! window.confirm( text ) ) {
				e.preventDefault();
			}
		} );

		/* ---------------------------------------------------------
		 * Submissions list: filters (the table lives inside the POST
		 * bulk-action form, so the Filter button builds a GET URL)
		 * ------------------------------------------------------- */
		$( document ).on( 'click', '#filter_action', function ( e ) {
			e.preventDefault();

			var params = {};
			$( '.advc-filters' ).find( 'select, input[name]' ).each( function () {
				var $el = $( this );
				if ( $el.attr( 'name' ) && $el.val() ) {
					params[ $el.attr( 'name' ) ] = $el.val();
				}
			} );

			var search = $( '#advc-search-search-input' ).val();
			if ( search ) {
				params.s = search;
			}

			var query = $.param( params );
			window.location.href = advcAdmin.listUrl + ( query ? '&' + query : '' );
		} );

		/* ---------------------------------------------------------
		 * Submissions list: quick "star" toggle (AJAX, no reload)
		 * ------------------------------------------------------- */
		$( document ).on( 'click', '.advc-star-toggle', function () {
			var $btn = $( this );
			var id   = $btn.data( 'id' );
			var $icon = $btn.find( '.dashicons' );

			$btn.prop( 'disabled', true );

			$.post( advcAdmin.ajaxUrl, {
				action: 'advc_quick_toggle_important',
				nonce: advcAdmin.nonce,
				id: id
			} ).done( function ( response ) {
				if ( response && response.success ) {
					if ( response.data.important ) {
						$icon.removeClass( 'dashicons-star-empty' ).addClass( 'dashicons-star-filled' );
					} else {
						$icon.removeClass( 'dashicons-star-filled' ).addClass( 'dashicons-star-empty' );
					}
				}
			} ).always( function () {
				$btn.prop( 'disabled', false );
			} );
		} );

		/* ---------------------------------------------------------
		 * Submissions list: row "Delete" action via hidden form
		 * ------------------------------------------------------- */
		$( document ).on( 'click', '.advc-delete-link', function ( e ) {
			e.preventDefault();

			if ( ! window.confirm( advcAdmin.i18n.confirmDelete ) ) {
				return;
			}

			var id = $( this ).data( 'id' );

			$( '#advc-row-action-name' ).val( 'advc_delete_submission' );
			$( '#advc-row-action-id' ).val( id );
			$( '#advc-row-action-form' ).trigger( 'submit' );
		} );

		/* ---------------------------------------------------------
		 * Submissions list: bulk action dropdown → admin-post routing
		 * WP_List_Table renders native <select name="action"> /
		 * "action2" dropdowns; disable them on submit so only our
		 * hidden "action=advc_bulk_action" field reaches admin-post.php.
		 * ------------------------------------------------------- */
		var $bulkForm = $( '#advc-submissions-form' );

		if ( $bulkForm.length ) {
			$bulkForm.on( 'submit', function ( e ) {
				var $top    = $bulkForm.find( 'select[name="action"]' );
				var $bottom = $bulkForm.find( 'select[name="action2"]' );
				var chosen  = $top.val();

				if ( ! chosen || '-1' === chosen ) {
					chosen = $bottom.val();
				}

				if ( ! chosen || '-1' === chosen ) {
					return; // let native validation / no-op happen
				}

				if ( ! $bulkForm.find( 'input[name="ids[]"]:checked' ).length ) {
					e.preventDefault();
					window.alert( 'Please select at least one submission.' );
					return;
				}

				$( '#advc-bulk-action-field' ).val( chosen );
				$top.add( $bottom ).prop( 'disabled', true );
			} );
		}


		/* Share lead: copy details to clipboard. */
		$( document ).on( 'click', '.advc-copy-lead', function () {
			var $btn  = $( this );
			var text  = $btn.attr( 'data-text' );
			var label = $btn.text();
			var done  = function () {
				$btn.text( $btn.attr( 'data-copied' ) );
				setTimeout( function () { $btn.text( label ); }, 1800 );
			};
			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( text ).then( done );
			} else {
				var $tmp = $( '<textarea>' ).val( text ).appendTo( 'body' ).select();
				document.execCommand( 'copy' );
				$tmp.remove();
				done();
			}
		} );

	} );
} )( jQuery );
