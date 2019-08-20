// Used by plugns that hide/show the billing fields.
pmpro_require_billing = true;

jQuery(document).ready(function() {
    //choosing payment method
    jQuery('input[name=gateway]').click(function() {
        if(jQuery(this).val() == 'paypal') {
            jQuery('#pmpro_paypalexpress_checkout').hide();
            jQuery('#pmpro_billing_address_fields').show();
            jQuery('#pmpro_payment_information_fields').show();
            jQuery('#pmpro_submit_span').show();
        } else {
            jQuery('#pmpro_billing_address_fields').hide();
            jQuery('#pmpro_payment_information_fields').hide();
            jQuery('#pmpro_submit_span').hide();
            jQuery('#pmpro_paypalexpress_checkout').show();
        }
    });

    //select the radio button if the label is clicked on
    jQuery('a.pmpro_radio').click(function() {
        jQuery(this).prev().click();
    });
	
	//3DSecure if enabled.
	if( pmpro_paypal.enable_3dsecure ) {
		Cardinal.configure({
			logging: {
				debug: pmpro_paypal.cardinal_debug,
				logging: pmpro_paypal.cardinal_logging,
			}
		});
		
		Cardinal.setup('init', {
			jwt: pmpro_paypal.cardinal_jwt,
		});
		
		Cardinal.on('payments.setupComplete', function() {			
			console.log( 'payments.setupComplete' );
		});
		
		Cardinal.on('payments.validated', function (data, jwt) {
			console.log( data );
			console.log( jwt );
			switch(data.ActionCode){
			  case "SUCCESS":
			  // Handle successful transaction, send JWT to backend to verify
			  break;
			 
			  case "NOACTION":
			  // Handle no actionable outcome
			  break;
			 
			  case "FAILURE":
			  // Handle failed transaction attempt
			  break;
			 
			  case "ERROR":
			  // Handle service level error
			  break;
		  }
		});
		
		jQuery('.pmpro_form').submit(function(event) {
			// prevent the form from submitting with the default action
			event.preventDefault();

			//double check in case a discount code made the level free
			if ( pmpro_require_billing ) {
				var order = {
					OrderDetails: {
						OrderNumber: ''
					},
					Consumer: {
						'Email1': jQuery( '#bemail' ).val(),
						'BillingAddress': {
							'FirstName': jQuery( '#bfirstname' ).val(),
							'LastName': jQuery( '#blastname' ).val(),
							'Address1': jQuery( '#baddress1' ).val(),
							'Address2': jQuery( '#baddress2' ).val(),							
							'City': jQuery( '#bcity' ).val(),
							'State': jQuery( '#bstate' ).val(),
							'PostalCode': jQuery( '#bzipcode' ).val(),
							'CountryCode': jQuery( '#bcountry' ).val(),
							'Phone1': jQuery( '#bphone' ).val(),
						},
						Account: {
							AccountNumber: jQuery( '#AccountNumber' ).val(),
							ExpirationMonth: jQuery( '#ExpirationMonth' ).val(),
							ExpirationYear: jQuery( '#ExpirationYear' ).val(),
						}
					}
				}
				
				Cardinal.start( 'cca', order );				

				// prevent the form from submitting with the default action
				return false;
			} else {
				this.submit();
				return true;	//not using Stripe anymore
			}
		});
	}
});