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
 * Toggle fields with a specific CSS class selector.
 * @since v2.1
 */
function pmpro_toggle_fields_by_selector( selector, checked ) {
	if( checked === undefined ) {
		jQuery( selector ).toggle();
	} else if ( checked ) {
		jQuery( selector ).show();
	} else {
		jQuery( selector ).hide();
	}
}

/*
 * Clicking on the Enable 3DSecure checkbox toggles settings.
 * @since v2.1
 */
jQuery(document).ready(function() {
	jQuery( '#paypal_enable_3dsecure' ).change( function() {		
		pmpro_toggle_fields_by_selector( 'tr.pmpro_paypal_3dsecure', jQuery( this ).prop( 'checked' ) );
	});
});