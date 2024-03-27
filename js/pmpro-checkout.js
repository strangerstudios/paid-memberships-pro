jQuery(document).ready(function(){ 
    // Discount code JS if we are showing discount codes.
    if ( pmpro.show_discount_code ) {
        //update discount code link to show field at top of form
        jQuery('#other_discount_code_toggle').attr('href', 'javascript:void(0);');
        jQuery('#other_discount_code_toggle').click(function() {
            jQuery('#other_discount_code_tr').show();
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
        jQuery('#other_discount_code_button').click(function() {
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
                jQuery('#other_discount_code_button').attr('disabled', 'disabled');

                jQuery.ajax({
                    url: pmpro.ajaxurl, type:'GET',timeout: pmpro.ajax_timeout,
                    dataType: 'html',
                    data: "action=applydiscountcode&code=" + code + "&pmpro_level=" + level_id + "&msgfield=pmpro_message",
                    error: function(xml){
                        alert('Error applying discount code [1]');

                        //enable apply button
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
                        jQuery('#other_discount_code_button').removeAttr('disabled');
                    }
                });
            }
        });
		
		// Bottom discount code field click handler.
		jQuery('#discount_code_button').click(function() {
			var code = jQuery('#pmpro_discount_code').val();
			var level_id = jQuery('#pmpro_level').val();

			if(code)
			{
				//hide any previous message
				jQuery('.pmpro_discount_code_msg').hide();

				//disable the apply button
				jQuery('#pmpro_discount_code_button').attr('disabled', 'disabled');

				jQuery.ajax({
					url: pmpro.ajaxurl,type:'GET',timeout: pmpro.ajax_timeout,
					dataType: 'html',
					data: "action=applydiscountcode&code=" + code + "&pmpro_level=" + level_id + "&msgfield=discount_code_message",
					error: function(xml){
						alert('Error applying discount code [1]');

						//enable apply button
						jQuery('#pmpro_discount_code_button').removeAttr('disabled');
					},
					success: function(responseHTML){
						if (responseHTML == 'error')
						{
							alert('Error applying discount code [2]');
						}
						else
						{
							jQuery('#discount_code_message').html(responseHTML);
						}

						//enable invite button
						jQuery('#pmpro_discount_code_button').removeAttr('disabled');
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
	
	// Find ALL <form> tags on your page
	jQuery('form').submit(function(){
		// On submit disable its submit button
		jQuery('input[type=submit]', this).attr('disabled', 'disabled');
		jQuery('input[type=image]', this).attr('disabled', 'disabled');
		jQuery('#pmpro_processing_message').css('visibility', 'visible');
	});	

	jQuery('.pmpro_checkout-field').each(function() {
		// Check if this checkout field is marked as required (either by class or by containing a .pmpro_required element)
		var isRequired = jQuery(this).hasClass('pmpro_checkout-field-required') || jQuery(this).find('.pmpro_required').length > 0;

		if (isRequired) {
			// Find the last input/select element within the .pmpro_checkout-field or .pmpro_display-field (if present)
			var $lastInput = jQuery(this).find('.pmpro_display-field').length ? jQuery(this).find('.pmpro_display-field:last').find('input, select').last() : jQuery(this).find('input, select').last();

			// Check if there's already an asterisk after the last input/select
			if (!$lastInput.nextAll('.pmpro_asterisk').length) {
				// If not, append the asterisk span after the last input/select
				$lastInput.after('<span class="pmpro_asterisk"> <abbr title="Required Field">*</abbr></span>');
			}
		}
	});

	//Loop through all radio type fields and move the asterisk.
	jQuery('.pmpro_checkout-field-radio').each(function () {
		if (jQuery(this).find('span').hasClass('pmpro_asterisk')) {
			jQuery(this).find(".pmpro_asterisk").remove();
			jQuery(this).find('label').first().append('<span class="pmpro_asterisk"> <abbr title="Required Field">*</abbr></span>');
		}
	});
    
    //move asterisk before hint <p>'s
    jQuery( 'span.pmpro_asterisk' ).each(function() {
        var prev = jQuery(this).prev();
        if ( prev.is('p') ) {
            jQuery(this).insertBefore(prev);
        }
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
});

// Get non-sensitve checkout form data to be sent to checkout_levels endpoint.
function pmpro_getCheckoutFormDataForCheckoutLevels() {
	// We need the level, discount code, and any field with the pmpro_alter_price CSS class.
	const checkoutFormData = jQuery( "#level, #pmpro_level, #discount_code, #pmpro_form .pmpro_alter_price" ).serializeArray();

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