/**
 * Show a system prompt before redirecting to a URL.
 * Used for delete links/etc.
 * @param	text	The prompt, i.e. are you sure?
 * @param	url		The url to redirect to.
 */
function pmpro_askfirst( text, url ) {
	var answer = window.confirm( text );

	if ( answer ) {
		window.location = url;
	}
}

/**
 * Deprecated in v2.1
 * In case add-ons/etc are expecting the non-prefixed version.
 */
if ( typeof askfirst !== 'function' ) {
    function askfirst( text, url ) {
        return pmpro_askfirst( text, url );
    }
}

/*
 * Toggle elements with a specific CSS class selector.
 * Used to hide/show sub settings when a main setting is enabled.
 * @since v2.1
 */
function pmpro_toggle_elements_by_selector( selector, checked ) {
	if( checked === undefined ) {
		jQuery( selector ).toggle();
	} else if ( checked ) {
		jQuery( selector ).show();
	} else {
		jQuery( selector ).hide();
	}
}

/*
 * Find inputs with a custom attribute pmpro_toggle_trigger_for,
 * and bind change to toggle the specified elements.
 * @since v2.1
 */
jQuery(document).ready(function() {
	jQuery( 'input[pmpro_toggle_trigger_for]' ).change( function() {		
		pmpro_toggle_elements_by_selector( jQuery( this ).attr( 'pmpro_toggle_trigger_for' ), jQuery( this ).prop( 'checked' ) );
	});
});

/** JQuery to hide the notifications. */
jQuery(document).ready(function(){
	jQuery(document).on( 'click', '.pmpro-notice-button.notice-dismiss', function() {
		var notification_id = jQuery( this ).val();

		var postData = {
			action: 'pmpro_hide_notice',
			notification_id: notification_id
		}

		jQuery.ajax({
			type: "POST",
			data: postData,
			url: ajaxurl,
			success: function( response ) {
				console.log( notification_id );
				jQuery('#'+notification_id).hide();
			}
		})
	
	});
});