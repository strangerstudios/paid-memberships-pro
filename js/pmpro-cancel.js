jQuery( document ).ready( function() { 
    // Prevent links from being clicked multiple times on PMPro cancel page.
    jQuery( '#pmpro_cancel .pmpro_btn-submit' ).click( function() {
        jQuery( '#pmpro_cancel a' ).unbind( 'click' );
        jQuery( '#pmpro_cancel a' ).attr( 'disabled', 'disabled' );
    });
});