var pmpro_recaptcha_validated = false;
var form_id = 'pmpro_form'; // The form we want to trigger the submit on.
var submit_button_id = pmpro_recaptcha_v3.submit_button_id; // The submit_button_id button we want to trigger the CAPTCHA On.

// Change the form ID we want to trigger on.
if ( submit_button_id === 'wp-submit' ) {
    form_id = 'loginform';
}

var pmpro_recaptcha_onSubmit = function(token) {
    if ( pmpro_recaptcha_validated ) {
        jQuery('#'+form_id).submit();
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
    if ( form_id === 'pmpro_form' ) {
        var submit_buttons = jQuery('.pmpro_btn-submit-checkout');
        if (jQuery('#pmpro_btn-submit').length === 0) {
            if (submit_buttons.length > 0) {
                jQuery(submit_buttons[0]).attr('id', 'pmpro_btn-submit');
            }
        }
    }
    
    // Render on submit button.
    grecaptcha.render(submit_button_id, {
        'sitekey': pmpro_recaptcha_v3.public_key,
        'callback': pmpro_recaptcha_onSubmit
    });
  
    // Move the <div> with the reCAPTCHA widget outside of the span that contains the submit button. For PMPro only.
    var submit_button = jQuery('#pmpro_btn-submit');
    var submit_span = submit_button.parent();
    var recaptcha_widget = jQuery('#pmpro_btn-submit').prev();
    recaptcha_widget.insertAfter( submit_span );

    // Run when the form is submitted on either the form_id or if we have the same class form.
    jQuery('#'+form_id+', .'+form_id).submit(function (event) {
        if (!pmpro_recaptcha_validated) {
            event.preventDefault();
            grecaptcha.execute();
        }
    });
};