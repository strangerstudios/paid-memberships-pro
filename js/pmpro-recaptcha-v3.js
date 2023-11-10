var pmpro_recaptcha_validated = false;
var pmpro_recaptcha_onSubmit = function(token) {
    if ( pmpro_recaptcha_validated ) {
        jQuery('#pmpro_form').submit();
        return;
    } else {
        jQuery.ajax({
        url: pmpro_recaptcha_v3.admin_ajax_url,
        type: 'GET',
        timeout: 30000,
        dataType: 'html',
        data: {
            'action': 'pmpro_validate_recaptcha',
            'g-recaptcha-response': token,
        },
        error: function(xml){
            alert('Error validating ReCAPTCHA.');
        },
        success: function(response){
            if ( response == '1' ) {
                pmpro_recaptcha_validated = true;
                
                //get a new token to be submitted with the form
                grecaptcha.execute();
            } else {
                pmpro_recaptcha_validated = false;
                
                //warn user validation failed
                alert( pmpro_recaptcha_v3.error_message );
                
                //get a new token to be submitted with the form
                grecaptcha.execute();
            }
        }
        });
    }						
};

var pmpro_recaptcha_onloadCallback = function() {
    // Render on main submit button.
    grecaptcha.render('pmpro_btn-submit', {
    'sitekey' : pmpro_recaptcha_v3.public_key,
    'callback' : pmpro_recaptcha_onSubmit
        });
    
    // Update other submit buttons.
    var submit_buttons = jQuery('.pmpro_btn-submit-checkout');
    submit_buttons.each(function() {
        if(jQuery(this).attr('id') != 'pmpro_btn-submit') {
            jQuery(this).click(function(event) {
                event.preventDefault();
                grecaptcha.execute();
            });
        }
    });
};