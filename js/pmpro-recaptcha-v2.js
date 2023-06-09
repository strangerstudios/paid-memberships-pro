var pmpro_recaptcha_validated = false;

// Validation callback.
function pmpro_recaptcha_validatedCallback() {
    // ReCAPTCHA worked.
    pmpro_recaptcha_validated = true;
    
    // Re-enable the submit button.
    jQuery('.pmpro_btn-submit-checkout,.pmpro_btn-submit').removeAttr('disabled');

    // Hide processing message.
    jQuery('#pmpro_processing_message').css('visibility', 'hidden');
    
    // Hide error message.
    if ( jQuery('#pmpro_message').text() == pmpro_recaptcha_v2.error_message ) {
        jQuery( '#pmpro_message' ).hide();
        jQuery( '#pmpro_message_bottom' ).hide();
    }
};

// Expiration callback.
function pmpro_recaptcha_expiredCallback() {
    pmpro_recaptcha_validated = false;
}
// Check validation on submit.
jQuery(document).ready(function(){
    jQuery('#pmpro_form').submit(function(event){
        if( pmpro_recaptcha_validated == false ) {
            event.preventDefault();
            
            // Re-enable the submit button.
            jQuery('.pmpro_btn-submit-checkout,.pmpro_btn-submit').removeAttr('disabled');

            // Hide processing message.
            jQuery('#pmpro_processing_message').css('visibility', 'hidden');

            // error message
            jQuery( '#pmpro_message' ).text( pmpro_recaptcha_v2.error_message ).addClass( 'pmpro_error' ).removeClass( 'pmpro_alert' ).removeClass( 'pmpro_success' ).hide().fadeIn();
            jQuery( '#pmpro_message_bottom' ).hide().fadeIn();

            return false;
        } else {
            return true;
        }
    });
});