// Wire up the form for Stripe.
jQuery( document ).ready( function( $ ) {

	var stripe, elements, pmproRequireBilling, cardNumber, cardExpiry, cardCvc;

	// Identify with Stripe.
	stripe = Stripe( pmproStripe.publishableKey );
	elements = stripe.elements();

	// Used by plugns that hide/show the billing fields.
	pmproRequireBilling = true;

	// Create Elements.
	cardNumber = elements.create( 'cardNumber' );
	cardExpiry = elements.create( 'cardExpiry' );
	cardCvc = elements.create( 'cardCvc' );

	// Mount Elements.
	cardNumber.mount( '#AccountNumber' );
	cardExpiry.mount( '#Expiry' );
	cardCvc.mount( '#CVV' );

	// Handle authentication if required.
	if ( pmproStripe.requiresAuth ) {
		// TODO Disable submit button, etc.
		call( pmproStripe.authAction, pmproStripe.clientSecret )
			.then( stripeResponseHandler( result ) );
	}

	$( '.pmpro_form' ).submit( function( event ) {
		var billingDetails, paymentMethod;

		// Prevent the form from submitting with the default action.
		event.preventDefault();

		// Double check in case a discount code made the level free.
		if ( pmproRequireBilling ) {

			if ( pmproStripe.verifyAddress ) {
				billingDetails = {
					addressLine1: $( '#baddress1' ).val(),
					addressLine2: $( '#baddress2' ).val(),
					addressCity: $( '#bcity' ).val(),
					addressState: $( '#bstate' ).val(),
					addressZip: $( '#bzipcode' ).val(),
					addressCountry: $( '#bcountry' ).val(),
				};
			}

			//add first and last name if not blank
			if ( $( '#bfirstname' ).length && $( '#blastname' ).length )
				billingDetails['name'] = $.trim( $( '#bfirstname' ).val() + ' ' + $( '#blastname' ).val() );

			// Try creating a PaymentMethod from card element.
			paymentMethod = stripe.createPaymentMethod( 'card', cardNumber, {
				billingDetails: billingDetails,
			}).then( stripeResponseHandler );

			// Prevent the form from submitting with the default action.
			return false;
		} else {
			this.submit();
			return true;	//not using Stripe anymore
		}
	});

	// Handle the response from Stripe.
	function stripeResponseHandler( response ) {

		var form, data, paymentMethodId, paymentIntent, setupIntent;

		form = $( '#pmpro_form, .pmpro_form' );

		if ( response.error ) {
			// Re-enable the submit button.
			$( '.pmpro_btn-submit-checkout,.pmpro_btn-submit' ).removeAttr( 'disabled' );

			// Hide processing message.
			$( '#pmpro_processing_message' ).css( 'visibility', 'hidden' );

			$( '.pmpro_error' ).text( response.error.message );

			pmproRequireBilling = true;

			// TODO Handle this better? Let the user know?
			// Delete any incomplete subscriptions if 3DS auth failed.
			data = {
				action: 'delete_incomplete_subscription',
			};
			$.post( pmproStripe.ajaxUrl, data, function( response ) {
				// Do stuff?
			});
		} else if ( response.paymentMethod ) {
			paymentMethodId = response.paymentMethod.id;
			card = response.paymentMethod.card;

			// insert the PaymentMethod ID into the form so it gets submitted to the server
			form.append( '<input type="hidden" name="payment_method_id" value="' + paymentMethodID + '" />' );

			// TODO Do we even need this?
			// 	We can expand the PaymentIntent created later to get card info for the order.

			// insert fields for other card fields
			// if(jQuery( '#CardType[name=CardType]' ).length)
			// 	jQuery( '#CardType' ).val(card.brand);
			// else
			// form$.append("<input type='hidden' name='CardType' value='" + card.brand + "'/>' );
			// form$.append("<input type='hidden' name='AccountNumber' value='XXXXXXXXXXXX" + card.last4 + "'/>' );
			// form$.append("<input type='hidden' name='ExpirationMonth' value='" + ("0" + card.exp_month).slice(-2) + "'/>' );
			// form$.append("<input type='hidden' name='ExpirationYear' value='" + card.exp_year + "'/>' );
			// and submit

			form.get(0).submit();
		} else if ( response.paymentIntent || response.setupIntent ) {

			if (response.paymentIntent) {
				var intent = response.paymentIntent;
			} else {
				var intent = response.setupIntent;
			}
			var paymentMethodID = intent.payment_method;

			// insert the PaymentMethod ID into the form so it gets submitted to the server
			form.append( '<input type="hidden" name="payment_method_id" value="' + paymentMethodID + '" />' );
			// TODO Do we even need this?
			// insert the PaymentIntent ID into the form so it gets submitted to the server
			// form$.append("<input type='hidden' name='payment_intent_id' value='" + paymentIntentID + "'/>' );

			// If PaymentIntent succeeded, we don't have to confirm again.
			if ( 'succeeded' == intent.status) {
				// debugger;

				// Authentication was successful.
				// var card = response.payment_method.card;

				//TODO: Set all of this in pmpro_required_billing_fields based on PaymentMethod.
				// We need this for now because the checkout order doesn't use the values set in $pmpro_required_billing_fields.
				// // insert fields for other card fields
				// if(jQuery( '#CardType[name=CardType]' ).length)
				// 	jQuery( '#CardType' ).val(card.brand);
				// else
				// form$.append("<input type='hidden' name='CardType' value='" + card.brand + "'/>' );
				// form$.append("<input type='hidden' name='AccountNumber' value='XXXXXXXXXXXX" + card.last4 + "'/>' );
				// form$.append("<input type='hidden' name='ExpirationMonth' value='" + ("0" + card.exp_month).slice(-2) + "'/>' );
				// form$.append("<input type='hidden' name='ExpirationYear' value='" + card.exp_year + "'/>' );
				form$.get(0).submit();
				return true;
			}

			// Confirm PaymentIntent again.
			var data = {
				action: 'confirm_payment_intent',
				payment_method_id: paymentMethodID,
				payment_intent_id: paymentIntentID,
			};
			jQuery.post(ajaxurl, data, function (response) {
				response = JSON.parse(response);
				if (response.error) {
					// Authentication failed.

					// re-enable the submit button
					jQuery( '.pmpro_btn-submit-checkout,.pmpro_btn-submit' ).removeAttr('disabled' );

					//hide processing message
					jQuery( '#pmpro_processing_message' ).css( 'visibility', 'hidden' );

					// show the errors on the form
					alert(response.error.message);
					jQuery( '.payment-errors' ).text(response.error.message);
				} else {
					// Authentication was successful.
					// var card = response.payment_method.card;

					//TODO: Set all of this in pmpro_required_billing_fields based on PaymentMethod.
					// insert fields for other card fields
					// if(jQuery( '#CardType[name=CardType]' ).length)
					// 	jQuery( '#CardType' ).val(card.brand);
					// else
					// form$.append("<input type='hidden' name='CardType' value='" + card.brand + "'/>' );
					// form$.append("<input type='hidden' name='AccountNumber' value='XXXXXXXXXXXX" + card.last4 + "'/>' );
					// form$.append("<input type='hidden' name='ExpirationMonth' value='" + ("0" + card.exp_month).slice(-2) + "'/>' );
					// form$.append("<input type='hidden' name='ExpirationYear' value='" + card.exp_year + "'/>' );

					form$.get(0).submit();
					return true;
				}
			});
		}
	}

	// Validate credit card and set card type.
	$( '#AccountNumber' ).validateCreditCard(function (result) {
		var cardtypenames = {
			"amex": "American Express",
			"diners_club_carte_blanche": "Diners Club Carte Blanche",
			"diners_club_international": "Diners Club International",
			"discover": "Discover",
			"jcb": "JCB",
			"laser": "Laser",
			"maestro": "Maestro",
			"mastercard": "Mastercard",
			"visa": "Visa",
			"visa_electron": "Visa Electron"
		}

		if (result.card_type)
			$( '#CardType' ).val(cardtypenames[result.card_type.name]);
		else
			$( '#CardType' ).val( 'Unknown Card Type' );
	});

});
