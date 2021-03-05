var pmpro_require_billing;

// Wire up the form for Stripe.
jQuery( document ).ready( function( $ ) {

	var stripe, elements, cardNumber, cardExpiry, cardCvc;

	// Identify with Stripe.
	stripe = Stripe( pmproStripe.publishableKey, 
		{ locale: 'auto' } 
	);
	elements = stripe.elements();

	// Create Elements.
	cardNumber = elements.create('cardNumber');
	cardExpiry = elements.create('cardExpiry');
	cardCvc = elements.create('cardCvc');

	// Mount Elements. Ensure CC field is present before loading Stripe.
	if ( $( '#AccountNumber' ).length > 0 ) { 
		cardNumber.mount('#AccountNumber');
	}
	if ( $( '#Expiry' ).length > 0 ) { 
		cardExpiry.mount('#Expiry');
	}
	if ( $( '#CVV' ).length > 0 ) { 
		cardCvc.mount('#CVV');
	}
	
	// Handle authentication for charge if required.
	if ( 'undefined' !== typeof( pmproStripe.paymentIntent ) ) {
		if ( 'requires_action' === pmproStripe.paymentIntent.status ) {
			// On submit disable its submit button
			$('input[type=submit]', this).attr('disabled', 'disabled');
			$('input[type=image]', this).attr('disabled', 'disabled');
			$('#pmpro_processing_message').css('visibility', 'visible');
			stripe.handleCardAction( pmproStripe.paymentIntent.client_secret )
				.then( pmpro_stripeResponseHandler );
		}
	}
	
	// Handle authentication for subscription if required.
	if ( 'undefined' !== typeof( pmproStripe.setupIntent ) ) {
		if ( 'requires_action' === pmproStripe.setupIntent.status ) {
			// On submit disable its submit button
			$('input[type=submit]', this).attr('disabled', 'disabled');
			$('input[type=image]', this).attr('disabled', 'disabled');
			$('#pmpro_processing_message').css('visibility', 'visible');
			stripe.handleCardSetup( pmproStripe.setupIntent.client_secret )
				.then( pmpro_stripeResponseHandler );
		}
	}

	// Set require billing var if not set yet.
	if ( typeof pmpro_require_billing === 'undefined' ) {
		pmpro_require_billing = pmproStripe.pmpro_require_billing;
	}

	$( '.pmpro_form' ).submit( function( event ) {
		var name, address;

		// Prevent the form from submitting with the default action.
		event.preventDefault();

		// Double check in case a discount code made the level free.
		if ( typeof pmpro_require_billing === 'undefined' || pmpro_require_billing ) {

			if ( pmproStripe.verifyAddress ) {
				address = {
					line1: $( '#baddress1' ).val(),
					line2: $( '#baddress2' ).val(),
					city: $( '#bcity' ).val(),
					state: $( '#bstate' ).val(),
					postal_code: $( '#bzipcode' ).val(),
					country: $( '#bcountry' ).val(),
				}
			}

			//add first and last name if not blank
			if ( $( '#bfirstname' ).length && $( '#blastname' ).length ) {
				name = $.trim( $( '#bfirstname' ).val() + ' ' + $( '#blastname' ).val() );
			}
			
			stripe.createPaymentMethod( 'card', cardNumber, {
				billing_details: {
					address: address,
					name: name,
				}
			}).then( pmpro_stripeResponseHandler );

			// Prevent the form from submitting with the default action.
			return false;
		} else {
			this.submit();
			return true;	//not using Stripe anymore
		}
	});

	// Check if Payment Request Button is enabled.
	if ( $('#payment-request-button').length ) {
		var paymentRequest = null;

		// Create payment request
		jQuery.noConflict().ajax({
			url: pmproStripe.restUrl + 'pmpro/v1/checkout_levels',
			dataType: 'json',
			data: jQuery( "#pmpro_form" ).serialize(),
			success: function(data) {
				if ( data.hasOwnProperty('initial_payment') ) {
					paymentRequest = stripe.paymentRequest({
						country: pmproStripe.accountCountry,
						currency: pmproStripe.currency,
						total: {
							label: pmproStripe.siteName,
							amount: Math.round( data.initial_payment * 100 ),
						},
						requestPayerName: true,
						requestPayerEmail: true,
					});
					var prButton = elements.create('paymentRequestButton', {
						paymentRequest: paymentRequest,
					});
					// Mount payment request button.
					paymentRequest.canMakePayment().then(function(result) {
					if (result) {
						prButton.mount('#payment-request-button');
					} else {
						$('#payment-request-button').hide();
					}
					});
					// Handle payment request button confirmation.
					paymentRequest.on('paymentmethod', function( event ) {
						$('#pmpro_btn-submit').attr('disabled', 'disabled');
						$('#pmpro_processing_message').css('visibility', 'visible');
						$('#payment-request-button').hide();
						event.complete('success');
						pmpro_stripeResponseHandler( event );
					});
				}
			}
		});

		// Find ALL <form> tags on your page
		jQuery('form').submit(function(){
			// Hide payment request button on form submit to prevent double charges.
			jQuery('#payment-request-button').hide();
		});	

		function stripeUpdatePaymentRequestButton() {
			jQuery.noConflict().ajax({
				url: pmproStripe.restUrl + 'pmpro/v1/checkout_levels',
				dataType: 'json',
				data: jQuery( "#pmpro_form" ).serialize(),
				success: function(data) {
					if ( data.hasOwnProperty('initial_payment') ) {
						paymentRequest.update({
							total: {
								label: pmproStripe.siteName,
								amount: Math.round( data.initial_payment * 100 ),
							},
						});
					}
				}
			});
		}

		if ( pmproStripe.updatePaymentRequestButton ) {
			$(".pmpro_alter_price").change(function(){
				stripeUpdatePaymentRequestButton();
			});
		}
	}

	// Handle the response from Stripe.
	function pmpro_stripeResponseHandler( response ) {

		var form, data, card, paymentMethodId, customerId;

		form = $('#pmpro_form, .pmpro_form');

		if (response.error) {

			// Re-enable the submit button.
			$('.pmpro_btn-submit-checkout,.pmpro_btn-submit').removeAttr('disabled');

			// Hide processing message.
			$('#pmpro_processing_message').css('visibility', 'hidden');

			// error message
			$( '#pmpro_message' ).text( response.error.message ).addClass( 'pmpro_error' ).removeClass( 'pmpro_alert' ).removeClass( 'pmpro_success' ).show();
			
		} else if ( response.paymentMethod ) {			
			
			paymentMethodId = response.paymentMethod.id;
			card = response.paymentMethod.card;			
			
			// Insert the Source ID into the form so it gets submitted to the server.
			form.append( '<input type="hidden" name="payment_method_id" value="' + paymentMethodId + '" />' );

			// We need this for now to make sure user meta gets updated.
			// Insert fields for other card fields.
			if( $( '#CardType[name=CardType]' ).length ) {
				$( '#CardType' ).val( card.brand );
			} else {
				form.append( '<input type="hidden" name="CardType" value="' + card.brand + '"/>' );
			}
			
			form.append( '<input type="hidden" name="AccountNumber" value="XXXXXXXXXXXX' + card.last4 + '"/>' );
			form.append( '<input type="hidden" name="ExpirationMonth" value="' + ( '0' + card.exp_month ).slice( -2 ) + '"/>' );
			form.append( '<input type="hidden" name="ExpirationYear" value="' + card.exp_year + '"/>' );

			// and submit
			form.get(0).submit();			
			
		} else if ( response.paymentIntent || response.setupIntent ) {
			
			// success message
			$( '#pmpro_message' ).text( pmproStripe.msgAuthenticationValidated ).addClass( 'pmpro_success' ).removeClass( 'pmpro_alert' ).removeClass( 'pmpro_error' ).show();
			
			customerId = pmproStripe.paymentIntent 
				? pmproStripe.paymentIntent.customer
				: pmproStripe.setupIntent.customer;
			
			paymentMethodId = pmproStripe.paymentIntent
				? pmproStripe.paymentIntent.payment_method.id
				: pmproStripe.setupIntent.payment_method.id;
				
			card = pmproStripe.paymentIntent
				? pmproStripe.paymentIntent.payment_method.card
				: pmproStripe.setupIntent.payment_method.card;

		    	if ( pmproStripe.paymentIntent ) {
				form.append( '<input type="hidden" name="payment_intent_id" value="' + pmproStripe.paymentIntent.id + '" />' );
			}
			if ( pmproStripe.setupIntent ) {
				form.append( '<input type="hidden" name="setup_intent_id" value="' + pmproStripe.setupIntent.id + '" />' );
				form.append( '<input type="hidden" name="subscription_id" value="' + pmproStripe.subscription.id + '" />' );
			}

			// Insert the Customer ID into the form so it gets submitted to the server.
			form.append( '<input type="hidden" name="customer_id" value="' + customerId + '" />' );

			// Insert the PaymentMethod ID into the form so it gets submitted to the server.
			form.append( '<input type="hidden" name="payment_method_id" value="' + paymentMethodId + '" />' );

			// We need this for now to make sure user meta gets updated.
			// Insert fields for other card fields.
			if( $( '#CardType[name=CardType]' ).length ) {
				$( '#CardType' ).val( card.brand );
			} else {
				form.append( '<input type="hidden" name="CardType" value="' + card.brand + '"/>' );
			}

			form.append( '<input type="hidden" name="AccountNumber" value="XXXXXXXXXXXX' + card.last4 + '"/>' );
			form.append( '<input type="hidden" name="ExpirationMonth" value="' + ( '0' + card.exp_month ).slice( -2 ) + '"/>' );
			form.append( '<input type="hidden" name="ExpirationYear" value="' + card.exp_year + '"/>' );
			form.get(0).submit();
			return true;
		}
	}
});
