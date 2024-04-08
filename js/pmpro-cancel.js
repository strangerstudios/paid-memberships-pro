jQuery( document ).ready( function() { 
    // Prevent links and submit buttons from being clicked multiple times on PMPro cancel page.
    jQuery('#pmpro_cancel #pmpro_form').submit( function(){
		jQuery('#pmpro_cancel input[type=submit]').attr('disabled', 'disabled');
        jQuery('#pmpro_cancel a').attr( 'disabled', 'disabled' );
	});	
});