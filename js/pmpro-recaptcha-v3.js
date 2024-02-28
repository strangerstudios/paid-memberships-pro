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

var pmpro_recaptcha_onloadCallback = function () {

    // If we're using a custom button, let's add the button ID to the custom button.
    var submit_buttons = jQuery('.pmpro_btn-submit-checkout');
    if (jQuery('#pmpro_btn-submit').length === 0) {
        if (submit_buttons.length > 0) {
            jQuery(submit_buttons[0]).attr('id', 'pmpro_btn-submit');
        }
    }
    
    // Render on submit button.
    grecaptcha.render('pmpro_btn-submit', {
        'sitekey': pmpro_recaptcha_v3.public_key,
        'callback': pmpro_recaptcha_onSubmit
    });
  
    // Move the <div> with the reCAPTCHA widget outside of the span that contains the submit button.
    var submit_button = jQuery('#pmpro_btn-submit');
    var submit_span = submit_button.parent();
    var recaptcha_widget = jQuery('#pmpro_btn-submit').prev();
    recaptcha_widget.insertAfter( submit_span );

    // Update other submit buttons.
    submit_buttons.each(function () {
        if (jQuery(this).attr('id') != 'pmpro_btn-submit') {
            jQuery(this).click(function (event) {
                event.preventDefault();
                grecaptcha.execute();
            });
        }
    });
};