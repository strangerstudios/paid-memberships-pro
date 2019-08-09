// Identify with Stripe.
Stripe.setPublishableKey( pmpro_stripe.publishablekey );

// Used by plugns that hide/show the billing fields.
pmpro_require_billing = true;

// Used to keep track of Stripe tokens.
var tokenNum = 0;

// Wire up the form for Stripe.
jQuery(document).ready(function() {
	jQuery(".pmpro_form").submit(function(event) {
		// prevent the form from submitting with the default action
		event.preventDefault();

		//double check in case a discount code made the level free
		if ( pmpro_require_billing ) {
			//build array for creating token
			var args = {
				number: jQuery('#AccountNumber').val(),
				exp_month: jQuery('#ExpirationMonth').val(),
				exp_year: jQuery('#ExpirationYear').val()			
			};
			
			if ( pmpro_stripe.verify_address ) {
				var more_args = {
					address_line1: jQuery('#baddress1').val(),
					address_line2: jQuery('#baddress2').val(),
					address_city: jQuery('#bcity').val(),
					address_state: jQuery('#bstate').val(),
					address_zip: jQuery('#bzipcode').val(),
					address_country: jQuery('#bcountry').val()
				}
				
				args = args.concat( more_args );
			}

			//add CVC if not blank
			if ( jQuery('#CVV').val().length )
				args['cvc'] = jQuery('#CVV').val();

			//add first and last name if not blank
			if ( jQuery('#bfirstname').length && jQuery('#blastname').length )
				args['name'] = jQuery.trim(jQuery('#bfirstname').val() + ' ' + jQuery('#blastname').val());

			//create token(s)
			if ( jQuery('#level').length ) {
				var levelnums = jQuery("#level").val().split(",");
				for(var cnt = 0, len = levelnums.length; cnt < len; cnt++) {
					Stripe.createToken(args, stripeResponseHandler);
				}
			} else {
				Stripe.createToken(args, stripeResponseHandler);
			}

			// prevent the form from submitting with the default action
			return false;
		} else {
			this.submit();
			return true;	//not using Stripe anymore
		}
	});
});

// Handle the response from Stripe.
function stripeResponseHandler(status, response) {
	if (response.error) {
		// re-enable the submit button
		jQuery('.pmpro_btn-submit-checkout,.pmpro_btn-submit').removeAttr("disabled");

		//hide processing message
		jQuery('#pmpro_processing_message').css('visibility', 'hidden');

		// show the errors on the form
		alert(response.error.message);
		jQuery(".payment-errors").text(response.error.message);
	} else {
		var form$ = jQuery("#pmpro_form, .pmpro_form");
		// token contains id, last4, and card type
		var token = response['id'];
		// insert the token into the form so it gets submitted to the server
		form$.append("<input type='hidden' name='stripeToken" + tokenNum + "' value='" + token + "'/>");
		tokenNum++;

		//console.log(response);

		//insert fields for other card fields
		if(jQuery('#CardType[name=CardType]').length)
			jQuery('#CardType').val(response['card']['brand']);
		else
			form$.append("<input type='hidden' name='CardType' value='" + response['card']['brand'] + "'/>");
		form$.append("<input type='hidden' name='AccountNumber' value='XXXXXXXXXXXX" + response['card']['last4'] + "'/>");
		form$.append("<input type='hidden' name='ExpirationMonth' value='" + ("0" + response['card']['exp_month']).slice(-2) + "'/>");
		form$.append("<input type='hidden' name='ExpirationYear' value='" + response['card']['exp_year'] + "'/>");

		// and submit
		form$.get(0).submit();
	}
}

// Validate credit card and set card type.
jQuery(document).ready(function() {
	jQuery('#AccountNumber').validateCreditCard(function(result) {
		var cardtypenames = {
			"amex":"American Express",
			"diners_club_carte_blanche":"Diners Club Carte Blanche",
			"diners_club_international":"Diners Club International",
			"discover":"Discover",
			"jcb":"JCB",
			"laser":"Laser",
			"maestro":"Maestro",
			"mastercard":"Mastercard",
			"visa":"Visa",
			"visa_electron":"Visa Electron"
		}

		if(result.card_type)
			jQuery('#CardType').val(cardtypenames[result.card_type.name]);
		else
			jQuery('#CardType').val('Unknown Card Type');
	});
});