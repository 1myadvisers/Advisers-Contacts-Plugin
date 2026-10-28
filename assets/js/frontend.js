/* global jQuery, advcFrontend */
( function ( $ ) {
	'use strict';

	var STORE_KEY = 'advc_source';
	var PARAMS    = [ 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid' ];

	/**
	 * Read the visitor's first-touch marketing source: UTM parameters in the
	 * address bar win; otherwise reuse what was saved earlier in this browser
	 * session so the original campaign survives navigating around the site.
	 */
	function getSource() {
		var saved = {};
		try {
			saved = JSON.parse( window.sessionStorage.getItem( STORE_KEY ) || '{}' ) || {};
		} catch ( e ) {
			saved = {};
		}

		var query   = new window.URLSearchParams( window.location.search );
		var hasNew  = false;
		var current = {};

		PARAMS.forEach( function ( key ) {
			var value = query.get( key );
			if ( value ) {
				current[ key ] = value.substring( 0, 255 );
				hasNew = true;
			}
		} );

		if ( hasNew ) {
			saved = current;
			saved.landing_page = window.location.href.substring( 0, 255 );
			saved.referrer     = ( document.referrer || '' ).substring( 0, 255 );
		} else if ( ! saved.landing_page ) {
			saved.landing_page = window.location.href.substring( 0, 255 );
			saved.referrer     = ( document.referrer || '' ).substring( 0, 255 );
		}

		try {
			window.sessionStorage.setItem( STORE_KEY, JSON.stringify( saved ) );
		} catch ( e ) {
			// Storage can be blocked; the values are still used for this page view.
		}

		return saved;
	}

	function copyLink( $btn ) {
		var url   = $btn.data( 'url' );
		var label = $btn.text();

		function done() {
			$btn.text( $btn.data( 'copied' ) );
			window.setTimeout( function () {
				$btn.text( label );
			}, 2000 );
		}

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( url ).then( done );
			return;
		}

		var $tmp = $( '<input type="text" readonly />' ).val( url ).appendTo( 'body' );
		$tmp[ 0 ].select();
		try {
			document.execCommand( 'copy' );
			done();
		} catch ( e ) {
			window.prompt( label, url );
		}
		$tmp.remove();
	}

	$( function () {
		$( document ).on( 'click', '.advc-share-copy', function () {
			copyLink( $( this ) );
		} );

		var source = window.URLSearchParams ? getSource() : {};

		$( '.advc-form' ).each( function () {
			var $form = $( this );

			$form.find( '[data-advc-src]' ).each( function () {
				var key = $( this ).data( 'advc-src' );
				if ( source[ key ] ) {
					$( this ).val( source[ key ] );
				}
			} );

			$form.on( 'submit', function () {
				$form.find( '.advc-submit-btn' ).prop( 'disabled', true ).text( 'Sending…' );
			} );
		} );
	} );
} )( jQuery );
