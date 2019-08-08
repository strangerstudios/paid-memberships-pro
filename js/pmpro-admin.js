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