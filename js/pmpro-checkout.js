jQuery(document).ready(function(){
    
    // Discount code JS if we are showing them.
    if ( pmpro.show_discount_code ) {
        //update discount code link to show field at top of form
        jQuery('#other_discount_code_a').attr('href', 'javascript:void(0);');
        jQuery('#other_discount_code_a').click(function() {
            jQuery('#other_discount_code_tr').show();
            jQuery('#other_discount_code_p').hide();
            jQuery('#other_discount_code').focus();
        });

        //update real discount code field as the other discount code field is updated
        jQuery('#other_discount_code').keyup(function() {
            jQuery('#discount_code').val(jQuery('#other_discount_code').val());
        });
        jQuery('#other_discount_code').blur(function() {
            jQuery('#discount_code').val(jQuery('#other_discount_code').val());
        });

        //update other discount code field as the real discount code field is updated
        jQuery('#discount_code').keyup(function() {
            jQuery('#other_discount_code').val(jQuery('#discount_code').val());
        });
        jQuery('#discount_code').blur(function() {
            jQuery('#other_discount_code').val(jQuery('#discount_code').val());
        });

        //applying a discount code
        jQuery('#other_discount_code_button').click(function() {
            var code = jQuery('#other_discount_code').val();
            var level_id = jQuery('#level').val();

            if(code)
            {
                //hide any previous message
                jQuery('.pmpro_discount_code_msg').hide();

                //disable the apply button
                jQuery('#other_discount_code_button').attr('disabled', 'disabled');

                jQuery.ajax({
                    url: pmpro.ajaxurl, type:'GET',timeout: pmpro.ajax_timeout,
                    dataType: 'html',
                    data: "action=applydiscountcode&code=" + code + "&level=" + level_id + "&msgfield=pmpro_message",
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
    }
	
	// Validate credit card number and determine card type.
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
});