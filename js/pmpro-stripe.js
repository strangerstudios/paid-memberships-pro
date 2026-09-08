var pmpro_require_billing;

// Wire up the form for Stripe.
jQuery( document ).ready( function( $ ) {

	var stripe, elements, cardNumber, cardExpiry, cardCvc, publishableKeyRefreshAttempted = false;
	var publishableKeyRefreshStorageKey = 'pmpro_stripe_publishable_key_refresh_attempted';
	var publishableKeyRefreshNoticeKey = 'pmpro_stripe_publishable_key_refresh_notice';

	try {
		if ( window.sessionStorage.getItem( publishableKeyRefreshNoticeKey ) === pmproStripe.publishableKey ) {
			window.sessionStorage.removeItem( publishableKeyRefreshNoticeKey );
			$( '#pmpro_message, #pmpro_message_bottom' )
				.text( pmproStripe.msgPublishableKeyRefreshed )
				.addClass( 'pmpro_alert' )
				.removeClass( 'pmpro_error pmpro_success' )
				.attr( 'role', 'status' )
				.show();
		}
	} catch ( storageError ) {
		// Session storage may be unavailable in privacy-restricted browsers.
	}

	/**
	 * Identify with Stripe.
	 */
	if ( pmproStripe.user_id ) {
		stripe = Stripe( pmproStripe.publishableKey, { stripeAccount: pmproStripe.user_id, locale: 'auto' } );
	} else {
		stripe = Stripe( pmproStripe.publishableKey, { locale: 'auto' } );
	}
	elements = stripe.elements();

	// Set up default credit card fields.
	cardNumber = elements.create('cardNumber', { style: pmproStripe.style });
	cardExpiry = elements.create('cardExpiry', { style: pmproStripe.style });
	cardCvc = elements.create('cardCvc', { style: pmproStripe.style });

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
	
	/**
	 * Handle request for card authentication. Only used after initial
	 * checkout form has been submitted with a valid payment method.
	 */
	function pmpro_set_checkout_for_stripe_card_authentication() {
		$('input[type=submit]', this).attr('disabled', 'disabled');
		$('input[type=image]', this).attr('disabled', 'disabled');
		$('#pmpro_processing_message').css('visibility', 'visible');
	}
	// Check if payment intent (charge) requires authentication.
	if ( 'undefined' !== typeof( pmproStripe.paymentIntent ) ) {
		if ( 'requires_action' === pmproStripe.paymentIntent.status ) {
			pmpro_set_checkout_for_stripe_card_authentication();
			stripe.handleCardAction( pmproStripe.paymentIntent.client_secret )
				.then( pmpro_stripeResponseHandler );
		}
	}
	// Check if payment intent (subscription) requires authentication.
	if ( 'undefined' !== typeof( pmproStripe.setupIntent ) ) {
		if ( 'requires_action' === pmproStripe.setupIntent.status ) {
			pmpro_set_checkout_for_stripe_card_authentication();
			stripe.handleCardSetup( pmproStripe.setupIntent.client_secret )
				.then( pmpro_stripeResponseHandler );
		}
	}

	/**
	 * Set up submit behavior for checkout form.
	 */
	// Set require billing var if not set yet.
	if ( typeof pmpro_require_billing === 'undefined' ) {
		pmpro_require_billing = pmproStripe.pmpro_require_billing;
	}
	$( '.pmpro_form' ).submit( function( event ) {
		// If the gateway field is present and not set to stripe, return.
		if ( $(this).find('input[name="gateway"]').length > 0 && $(this).find('input[name="gateway"]:checked').val() !== 'stripe' ) {
			return;
		}

		// If default is already being prevented, don't try to initiate the payment process.
		// Likely caused by ReCAPTCHA failing.
		if ( event.isDefaultPrevented() ) {
			return;
		}

		// If there is no "pmpro_level" input (or "level" input for legacy page templates), then this is not a checkout form. Return.
		if ( $(this).find('input[name="pmpro_level"]').length === 0 && $(this).find('input[name="level"]').length === 0 ) {
			return;
		}

		// If there is a payment method ID already, then this is a form submission after card authentication.
		// This may be the case when the payment request button is used, for example.
		if ( $(this).find('input[name="payment_method_id"]').length > 0 ) {
			return;
		}

		var name, address;

		// Prevent the form from submitting with the default action.
		event.preventDefault();

		// Double check in case a discount code made the level free.
		if ( typeof pmpro_require_billing === 'undefined' || pmpro_require_billing ) {
			// Get the data needed to create a payment method for this checkout.
			if ( $( '#baddress1' ).length ) {
				address = {
					line1: $( '#baddress1' ).length ? $( '#baddress1' ).val() : '',
					line2: $( '#baddress2' ).length ? $( '#baddress2' ).val() : '',
					city: $( '#bcity' ).length ? $( '#bcity' ).val() : '',
					state: $( '#bstate' ).length ? $( '#bstate' ).val() : '',
					postal_code: $( '#bzipcode' ).length ? $( '#bzipcode' ).val() : '',
					country: $( '#bcountry' ).length ? $( '#bcountry' ).val() : '',
				}
			}

			//add first and last name if not blank
			if ( $( '#bfirstname' ).length && $( '#blastname' ).length ) {
				name = $.trim( $( '#bfirstname' ).val() + ' ' + $( '#blastname' ).val() );
			}

			// Create the payment method.
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

	/**
	 * Set up payment request button.
	 */
	// Check if Payment Request Button is enabled.
	if ( $('#payment-request-button').length ) {
		var paymentRequest = null;

		// Get the level price so that information can be shown in payment request popup
		jQuery.noConflict().ajax({
			url: pmproStripe.restUrl + 'pmpro/v1/checkout_level',
			dataType: 'json',
			data: pmpro_getCheckoutFormDataForCheckoutLevels(),
			success: function(data) {
				if ( data.hasOwnProperty('initial_payment') ) {
					// Build payment request button.
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
						// Do not let customer submit the form again.
						$('#pmpro_btn-submit').attr('disabled', 'disabled');
						$('#pmpro_processing_message').css('visibility', 'visible');
						$('#payment-request-button').hide();
						/*
						 Close the payment request interface immediately. This is not the intended
						 implementation from Stripe, but we are submitting the payment method
						 through our default checkout process instead of letting Stripe
						 process it through	the payment request button. Closing immediately also
						 prevents timeouts during the payment request workflow that have caused
						 issues on slower sites in the past.
						 */
						event.complete('success');
						pmpro_stripeResponseHandler( event );
					});
				}
			}
		});
		// Hide payment request button on form submit to prevent double charges.
		jQuery('#pmpro_form').submit(function(){
			jQuery('#payment-request-button').hide();
		});	
		// Update price shown in payment request button if price changes.
		function stripeUpdatePaymentRequestButton() {
			jQuery.noConflict().ajax({
				url: pmproStripe.restUrl + 'pmpro/v1/checkout_level',
				dataType: 'json',
				data: pmpro_getCheckoutFormDataForCheckoutLevels(),
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

	/**
	 * Handle the response from Stripe.
	 */
	function pmpro_stripeResponseHandler( response ) {

		var form, data, card, paymentMethodId;

		form = $('#pmpro_form, .pmpro_form');

		if (response.error) {
			if ( pmpro_maybe_refresh_stripe_publishable_key( response.error ) ) {
				return;
			}
			if ( [ 'api_key_expired', 'platform_api_key_expired' ].indexOf( response.error.code ) === -1 ) {
				pmpro_clear_publishable_key_refresh_attempt();
			}

			pmpro_show_stripe_error( response.error.message );
			
		} else if ( response.paymentMethod ) {
			pmpro_clear_publishable_key_refresh_attempt();

			// A payment method was created successfully. Submit the checkout form and finish the checkout in PHP.
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
			form.submit();			
			
		} else if ( response.paymentIntent || response.setupIntent ) {
			pmpro_clear_publishable_key_refresh_attempt();

			// Card authentication was successful. Finish the checkout in PHP.
			// success message
			$( '#pmpro_message' ).text( pmproStripe.msgAuthenticationValidated ).addClass( 'pmpro_success' ).removeClass( 'pmpro_alert' ).removeClass( 'pmpro_error' ).show();

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
			}

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
			form.submit();
			return true;
		}
	}

	/**
	 * Refresh an expired Stripe Connect platform key and reload Elements once.
	 *
	 * Stripe Elements cannot move an existing card Element to a new Stripe instance, so a reload
	 * is required after the key changes. Session storage prevents a reload loop.
	 *
	 * @param {Object} error Stripe.js error response.
	 * @return {boolean} Whether key recovery was started.
	 */
	function pmpro_maybe_refresh_stripe_publishable_key( error ) {
		var expiredKeyCodes = [ 'api_key_expired', 'platform_api_key_expired' ];
		var attemptedKey = '';

		if ( ! error || expiredKeyCodes.indexOf( error.code ) === -1 || ! pmproStripe.user_id || publishableKeyRefreshAttempted ) {
			return false;
		}

		try {
			attemptedKey = window.sessionStorage.getItem( publishableKeyRefreshStorageKey );
		} catch ( storageError ) {
			attemptedKey = '';
		}

		if ( attemptedKey === pmproStripe.publishableKey ) {
			return false;
		}

		publishableKeyRefreshAttempted = true;
		try {
			window.sessionStorage.setItem( publishableKeyRefreshStorageKey, pmproStripe.publishableKey );
		} catch ( storageError ) {
			// The in-memory guard still prevents another attempt on this page.
		}

		$( '#pmpro_message, #pmpro_message_bottom' )
			.text( pmproStripe.msgRefreshingPublishableKey )
			.addClass( 'pmpro_alert' )
			.removeClass( 'pmpro_error pmpro_success' )
			.attr( 'role', 'status' )
			.show();

		$.post(
			pmproStripe.ajaxUrl,
			{
				action: 'pmpro_stripe_refresh_publishable_key',
				nonce: pmproStripe.publishableKeyRefreshNonce,
			}
		).done( function( response ) {
			if (
				response.success && response.data && response.data.publishableKey &&
				response.data.publishableKey !== pmproStripe.publishableKey
			) {
				try {
					window.sessionStorage.setItem( publishableKeyRefreshStorageKey, response.data.publishableKey );
					window.sessionStorage.setItem( publishableKeyRefreshNoticeKey, response.data.publishableKey );
				} catch ( storageError ) {
					// The in-memory guard still prevents another attempt on this page.
				}
				window.location.reload();
				return;
			}

			pmpro_show_stripe_error( error.message );
		} ).fail( function() {
			pmpro_show_stripe_error( error.message );
		} );

		return true;
	}

	/**
	 * Show a Stripe.js error and restore the checkout controls.
	 *
	 * @param {string} message Error message to show.
	 */
	function pmpro_show_stripe_error( message ) {
		$( '.pmpro_btn-submit-checkout,.pmpro_btn-submit' ).removeAttr( 'disabled' );
		$( '#pmpro_processing_message' ).css( 'visibility', 'hidden' );
		$( '#pmpro_message, #pmpro_message_bottom' )
			.text( message )
			.addClass( 'pmpro_error' )
			.removeClass( 'pmpro_alert pmpro_success' )
			.attr( 'role', 'alert' )
			.show();
	}

	/**
	 * Allow recovery from a future key rotation after Stripe accepts the current key.
	 */
	function pmpro_clear_publishable_key_refresh_attempt() {
		publishableKeyRefreshAttempted = false;
		try {
			window.sessionStorage.removeItem( publishableKeyRefreshStorageKey );
		} catch ( storageError ) {
			// Session storage may be unavailable in privacy-restricted browsers.
		}
	}
});
