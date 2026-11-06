( function ( $ ) {
	'use strict';

	$( function () {
		let frame;
		let itineraryIndex = $( '.wra-itinerary-item' ).length;

		$( '.wra-itinerary-add' ).on( 'click', function () {
			const template = $( '#tmpl-wra-itinerary-item' ).html().replaceAll( '__INDEX__', itineraryIndex );
			$( '.wra-itinerary-list' ).append( template );
			itineraryIndex += 1;
		} );

		$( document ).on( 'click', '.wra-itinerary-remove', function () {
			if ( window.confirm( 'Diesen Programmpunkt entfernen?' ) ) {
				$( this ).closest( '.wra-itinerary-item' ).remove();
			}
		} );

		$( '.wra-gallery-select' ).on( 'click', function ( event ) {
			event.preventDefault();

			if ( frame ) {
				frame.open();
				return;
			}

			frame = wp.media( {
				title: 'Reisebilder auswählen',
				button: { text: 'Galerie übernehmen' },
				library: { type: 'image' },
				multiple: true
			} );

			frame.on( 'select', function () {
				const selection = frame.state().get( 'selection' ).toJSON();
				const ids = selection.map( ( image ) => image.id );
				const preview = selection.map( ( image ) => {
					const source = image.sizes && image.sizes.thumbnail ? image.sizes.thumbnail.url : image.url;
					return '<img src="' + source + '" alt="">';
				} );

				$( '.wra-gallery-ids' ).val( ids.join( ',' ) );
				$( '.wra-gallery-preview' ).html( preview.join( '' ) );
			} );

			frame.open();
		} );

		$( '.wra-gallery-clear' ).on( 'click', function ( event ) {
			event.preventDefault();
			$( '.wra-gallery-ids' ).val( '' );
			$( '.wra-gallery-preview' ).empty();
		} );
	} );
}( jQuery ) );

