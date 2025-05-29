var pmpro_recaptcha_validated = false;
var submit_button_id = pmpro_recaptcha_v2.submit_button_id; // This is without the #, so it can be a class or an ID (to give us more flexibility).
var form_id = 'loginform';

// If it's tied to a PMPro button, let's add multiple button selectors and tweak the form ID to be pmpro_form.
if ( submit_button_id == 'pmpro_btn-submit' ) {
    submit_button_id = '.pmpro_btn-submit-checkout,.pmpro_btn-submit';
    form_id = 'pmpro_form';
}

// Validation callback.
function pmpro_recaptcha_validatedCallback() {
    // ReCAPTCHA worked.
    pmpro_recaptcha_validated = true;
    
    // Re-enable the submit button.
    jQuery(submit_button_id).removeAttr('disabled');

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
    jQuery('#'.form_id).submit(function(event){
        if( pmpro_recaptcha_validated == false ) {
            event.preventDefault();
            
            // Re-enable the submit button.
            jQuery(submit_button_id).removeAttr('disabled');

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