jQuery(document).ready(function(){ 
    // Discount code JS if we are showing discount codes.
    if ( pmpro.show_discount_code ) {
        //update discount code link to show field at top of form
        jQuery('#other_discount_code_toggle').attr('href', 'javascript:void(0);');
        jQuery('#other_discount_code_toggle').click(function() {
            jQuery('#other_discount_code_fields').show();
            jQuery('#other_discount_code_p').hide();
            jQuery('#pmpro_other_discount_code').focus();
        });

        //update real discount code field as the other discount code field is updated
        jQuery('#pmpro_other_discount_code').keyup(function() {
            jQuery('#pmpro_discount_code').val(jQuery('#pmpro_other_discount_code').val());
        });
        jQuery('#pmpro_other_discount_code').blur(function() {
            jQuery('#pmpro_discount_code').val(jQuery('#pmpro_other_discount_code').val());
        });

        //update other discount code field as the real discount code field is updated
        jQuery('#pmpro_discount_code').keyup(function() {
            jQuery('#pmpro_other_discount_code').val(jQuery('#pmpro_discount_code').val());
        });
        jQuery('#pmpro_discount_code').blur(function() {
            jQuery('#pmpro_other_discount_code').val(jQuery('#pmpro_discount_code').val());
        });

        // Top discount code field click handler.
        jQuery('#other_discount_code_button, #discount_code_button').click(function() {
            var code = jQuery('#pmpro_other_discount_code').val();
            var level_id = jQuery('#pmpro_level').val();
            if ( ! level_id ) {
                // If the level ID is not set, try to get it from the #level field for outdated checkout templates.
                level_id = jQuery('#level').val();
            }

            if(code)
            {
                //hide any previous message
                jQuery('.pmpro_discount_code_msg').hide();

                //disable the apply button
                jQuery('#pmpro_discount_code_button').attr('disabled', 'disabled');
                jQuery('#other_discount_code_button').attr('disabled', 'disabled');

                jQuery.ajax({
                    url: pmpro.ajaxurl, type:'GET',timeout: pmpro.ajax_timeout,
                    dataType: 'html',
                    data: "action=applydiscountcode&code=" + code + "&pmpro_level=" + level_id + "&msgfield=pmpro_message",
                    error: function(xml){
                        alert('Error applying discount code [1]');

                        //enable apply button
                        jQuery('#pmpro_discount_code_button').removeAttr('disabled');
                        jQuery('#other_discount_code_button').removeAttr('disabled');

                    },
                    success: function(responseHTML){
                        if (responseHTML == 'error')
                        {
                            alert('Error applying discount code [2]');
                        }
                        else
                        {
                            jQuery('#pmpro_message').html(responseHTML);
                        }

                        //enable invite button
                        jQuery('#pmpro_discount_code_button').removeAttr('disabled');
                        jQuery('#other_discount_code_button').removeAttr('disabled');
                    }
                });
            }
        });
    }
	
	// Validate credit card number and determine card type.
	if ( typeof jQuery('#AccountNumber').validateCreditCard == 'function' ) {
        jQuery('#AccountNumber').validateCreditCard(function(result) {
    		var cardtypenames = {
    			"amex"                      : "American Express",
    			"diners_club_carte_blanche" : "Diners Club Carte Blanche",
    			"diners_club_international" : "Diners Club International",
    			"discover"                  : "Discover",
    			"jcb"                       : "JCB",
    			"laser"                     : "Laser",
    			"maestro"                   : "Maestro",
    			"mastercard"                : "Mastercard",
    			"visa"                      : "Visa",
    			"visa_electron"             : "Visa Electron"
    		};

    		if(result.card_type)
    			jQuery('#CardType').val(cardtypenames[result.card_type.name]);
    		else
    			jQuery('#CardType').val('Unknown Card Type');
    	});
    }

	// Password visibility toggle.
	(function() {
		const toggleElements = document.querySelectorAll('.pmpro_btn-password-toggle');

		toggleElements.forEach(toggle => {
			toggle.classList.remove('hide-if-no-js');
			toggle.addEventListener('click', togglePassword);
		});

		function togglePassword() {
			const status = this.getAttribute('data-toggle');
			const passwordInputs = document.querySelectorAll('.pmpro_form_input-password');
			const icon = this.getElementsByClassName('pmpro_icon')[0];
			const state = this.getElementsByClassName('pmpro_form_field-password-toggle-state')[0];

			if (parseInt(status, 10) === 0) {
				this.setAttribute('data-toggle', 1);
				passwordInputs.forEach(input => input.setAttribute('type', 'text'));
				icon.innerHTML = `
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--pmpro--color--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye-off">
						<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
						<line x1="1" y1="1" x2="23" y2="23"></line>
					</svg>`;
				state.textContent = pmpro.hide_password_text;
			} else {
				this.setAttribute('data-toggle', 0);
				passwordInputs.forEach(input => input.setAttribute('type', 'password'));
				icon.innerHTML = `
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--pmpro--color--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye">
						<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
						<circle cx="12" cy="12" r="3"></circle>
					</svg>`;
				state.textContent = pmpro.show_password_text;
			}
		}
	})();

	// Find ALL <form> tags on your page
	jQuery('form#pmpro_form').submit(function(){
		// On submit disable its submit button
		jQuery('input[type=submit]', this).attr('disabled', 'disabled');
		jQuery('input[type=image]', this).attr('disabled', 'disabled');
		jQuery('#pmpro_processing_message').css('visibility', 'visible');
	});	

	jQuery('.pmpro_form_field-required').each(function() {
		// Check if there's an asterisk already
		var $firstLabel = jQuery(this).find('.pmpro_form_label').first();
		var $hasAsterisk = $firstLabel.find('.pmpro_asterisk').length > 0;
	
		// If there's no asterisk, add one
		if ( ! $hasAsterisk ) {
			$firstLabel.append('<span class="pmpro_asterisk"> <abbr title="Required Field">*</abbr></span>');
		}

		// Add the aria-required="true" attribute to the input.
		jQuery(this).find('.pmpro_form_input').attr('aria-required', 'true');
	});

	jQuery('.pmpro_form_input-required').each(function() {
		// Check if there's an asterisk already
		var $fieldDiv = jQuery(this).closest('.pmpro_form_field');
		var $firstLabel = $fieldDiv.find('.pmpro_form_label').first();
		var $hasAsterisk = $firstLabel.find('.pmpro_asterisk').length > 0;

		// If there's no asterisk, add one
		if ( ! $hasAsterisk ) {
			$firstLabel.append('<span class="pmpro_asterisk"> <abbr title="Required Field">*</abbr></span>');
		}

		// Add the aria-required="true" attribute to the input.
		jQuery(this).find('.pmpro_form_input').attr('aria-required', 'true');
	});

	//unhighlight error fields when the user edits them
	jQuery('.pmpro_error').bind("change keyup input", function() {
		jQuery(this).removeClass('pmpro_error');
	});

	//click apply button on enter in discount code box
	jQuery('#pmpro_discount_code').keydown(function (e){
	    if(e.keyCode == 13){
		   e.preventDefault();
		   jQuery('#pmpro_discount_code_button').click();
	    }
	});

	//hide apply button if a discount code was passed in
	if( pmpro.discount_code_passed_in ) {
		jQuery('#pmpro_discount_code_button').hide();
		jQuery('#pmpro_discount_code').bind('change keyup', function() {
			jQuery('#pmpro_discount_code_button').show();
		});
	}

	//click apply button on enter in *other* discount code box
	jQuery('#pmpro_other_discount_code').keydown(function (e){
	    if(e.keyCode == 13){
		   e.preventDefault();
		   jQuery('#other_discount_code_button').click();
	    }
	});
	
	//add javascriptok hidden field to checkout
	jQuery("input[name=submit-checkout]").after('<input type="hidden" name="javascriptok" value="1" />');
	
	// Keep bottom message box in sync with the top one.
	jQuery('#pmpro_message').bind("DOMSubtreeModified",function(){
		setTimeout( function(){ pmpro_copyMessageToBottom() }, 200);
	});
	
	function pmpro_copyMessageToBottom() {
		jQuery('#pmpro_message_bottom').html(jQuery('#pmpro_message').html());
		jQuery('#pmpro_message_bottom').attr('class', jQuery('#pmpro_message').attr('class'));
		if(jQuery('#pmpro_message').is(":visible")) {
			jQuery('#pmpro_message_bottom').show();
		} else {
			jQuery('#pmpro_message_bottom').hide();
		}
	}

	// If a user was created during this page load, update the nonce to be valid.
	if ( pmpro.update_nonce ) {
		jQuery.ajax({
			url: pmpro.ajaxurl,
			type: 'POST',
			data: {
				action: 'pmpro_get_checkout_nonce'
			}
		}).done(function(response) {
			jQuery('input[name="pmpro_checkout_nonce"]').val(response);
		});
	}
});

// Get non-sensitive checkout form data to be sent to checkout_levels endpoint.
function pmpro_getCheckoutFormDataForCheckoutLevels() {
	// We need the level, discount code, and any field with the pmpro_alter_price CSS class.
	const checkoutFormData = jQuery( "#level, #pmpro_level, #discount_code, #pmpro_discount_code, #pmpro_form .pmpro_alter_price" ).serializeArray();

	// Double check to remove sensitive data from the array.
	const sensitiveCheckoutRequestVars = pmpro.sensitiveCheckoutRequestVars;
	for ( var i = 0; i < checkoutFormData.length; i++ ) {
		if ( sensitiveCheckoutRequestVars.includes( checkoutFormData[i].name ) ) {
			// Remove sensitive data from form data and adjust index to account for removed item.
			checkoutFormData.splice( i, 1 );
			i--;
		}
	}

	// Return form data as string.
	return jQuery.param( checkoutFormData );
}