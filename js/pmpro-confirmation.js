jQuery( document ).ready( function() { 
    // If this script is loaded, then we are on the confirmation page for a pending or token order.
    // We want to poll the server to see if the order has been completed and if so, refresh so
    // the user can see the user can see their completed checkout.
    var pollInterval = setInterval( function() {
        // Get the level price so that information can be shown in payment request popup
		jQuery.noConflict().ajax({
			url: pmpro.restUrl + 'pmpro/v1/order',
            beforeSend: function ( xhr ) {
                xhr.setRequestHeader( 'X-WP-Nonce', pmpro.nonce );
            },
			dataType: 'json',
			data: {
                'code': pmpro.code
            },
            success: function( response ) {
                if ( response.status == 'success' ) {
                    // Order is complete.
                    clearInterval( pollInterval );
                    window.location.reload();
                }
            }
        } );
    }, 5000 ); // Poll every 5 seconds.
});