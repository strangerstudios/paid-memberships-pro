/**
 * Show a system prompt before redirecting to a URL.
 * Used for delete links/etc.
 * @param	text	The prompt, i.e. are you sure?
 * @param	url		The url to redirect to.
 */
function askfirst( text, url ) {
	var answer = window.confirm( text );

	if ( answer ) {
		window.location = url;
	}
}