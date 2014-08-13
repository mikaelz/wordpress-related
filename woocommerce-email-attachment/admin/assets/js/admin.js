
jQuery( document ).ready(function( $ ) {

	$( document ).on( 'click', '.insert-media', function( event ) {
		var dst = $( '#' + $( this ).data( 'editor' ) );

		wp.media.editor.send.attachment = function( props, attachment ) {
			dst.val( attachment.url.replace( site.url, '') );
		}
	});

});
