jQuery( function ( $ ) {
	var frame;

	function renderLogoPreview( attachment ) {
		var previewWrap = $( '.giwi-logo-preview-wrap' );
		var removeButton = $( '.giwi-remove-logo' );

		if ( attachment && attachment.url ) {
			previewWrap.html(
				'<img src="' + attachment.url + '" alt="" class="giwi-logo-preview" />'
			);
			removeButton.removeClass( 'hidden' );
		} else {
			previewWrap.html(
				'<div class="giwi-logo-placeholder">No logo selected</div>'
			);
			removeButton.addClass( 'hidden' );
		}
	}

	$( document ).on( 'click', '.giwi-upload-logo', function ( event ) {
		event.preventDefault();

		if ( frame ) {
			frame.open();
			return;
		}

		frame = wp.media({
			title: giwiAdmin.title,
			button: {
				text: giwiAdmin.buttonText
			},
			library: {
				type: 'image'
			},
			multiple: false
		});

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			$( '#logo_id' ).val( attachment.id );
			renderLogoPreview( attachment );
		});

		frame.open();
	});

	$( document ).on( 'click', '.giwi-remove-logo', function ( event ) {
		event.preventDefault();
		$( '#logo_id' ).val( '' );
		renderLogoPreview( null );
	});
} );
