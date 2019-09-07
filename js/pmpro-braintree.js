jQuery(document).ready(function ($) {

    var hostedFields, threeDSecure;

    braintree.client.create({
        // Use the generated client token to instantiate the Braintree client.
        authorization: pmproBraintree.clientToken
    }).then(function (clientInstance) {
        setupComponents(pmproBraintree.clientToken)
            .then(function (instances) {
                hostedFields = instances[0];
                threeDSecure = instances[1];
            }).catch(function (e) {
            console.log(e);
        });
    }).catch(function (e) {
        console.log(e);
    });

    $('.pmpro_form').submit(function (event) {

        var billingAddress;

        // Prevent the form from submitting with the default action.
        event.preventDefault();

        // TODO Double check in case a discount code made the level free.
        if ( pmpro_require_billing ) {

            hostedFields.tokenize()
                .then(braintreeResponseHandler)
                .catch( function (e) {
                    console.log( e );
                });
                // }).then(function (response) {
                //     if( 'authenticate_successful' === response.threeDSecureInfo.status ) {
                //         $('.pmpro_form').append( '<input type="hidden" name="braintree_3ds_nonce" value="' + response.nonce + '"/>' );
                //         $('.pmpro_form').submit();
                //         return true;
                //     } else {
                //         throw new Error( response.threeDSecureInfo.status );
                //     }
                // }).catch(function (e) {
                //     console.log(e);
                // });
            // }).catch(function (e) {
            //     console.log(e);
            // });
        }
    });

    // Handle the response from Braintree.
    function braintreeResponseHandler( response ) {

        console.log(response);

        var form;

        form = $('#pmpro_form, .pmpro_form');

        if ('CreditCard' === response.type) {
            // TODO Always pass these if available to optimize 3DS challenges.
            // if ( pmproBraintree.verifyAddress ) {
            //     billingAddress = {
            //         streetAddress: $( '#baddress1' ).val(),
            //         extendedAddress: $( '#baddress2' ).val(),
            //         locality: $( '#bcity' ).val(),
            //         region: $( '#bstate' ).val(),
            //         postalCode: $( '#bzipcode' ).val(),
            //         countryCodeAlpha2: $( '#bcountry' ).val(),
            //     };
            // }

            // add first and last name if not blank
            // if ( $( '#bfirstname' ).length && $( '#blastname' ).length ) {
            //     billingAddress.givenName = $( '#bfirstname' ).val();
            //     billingAddress.surname = $( '#blastname' ).val();
            // }

            $( '#braintree_payment_method_nonce' ).val( response.nonce );
            $( '#CardType' ).val( response.details.cardType );
            $( '#BraintreeAccountNumber' ).val( 'XXXXXXXXXXXX' + response.details.lastFour );
            $( 'input[name="ExpirationMonth"]' ).val( ( '0' + response.details.expirationMonth ).slice( -2 ) );
            $( 'input[name="ExpirationYear"]' ).val( response.details.expirationYear );
            $( '#credit_card_exp' ).val( ( '0' + response.details.expirationMonth ).slice( -2 ) + '/' + response.details.expirationYear );


            threeDSecure.verifyCard({
                onLookupComplete: function (data, next) {
                    next();
                },
                // TODO Get amount.
                amount: '100.00',
                nonce: response.nonce,
                bin: response.details.bin,
                // billingAddress: billingAddress,
            }).then(braintreeResponseHandler);
        } else {
            if ('authenticate_successful' === response.threeDSecureInfo.status) {
                $( '#braintree_payment_method_nonce' ).val( response.nonce );
                form.get(0).submit();
            } else {
                // TODO Handle errors.
            }
        }
    }


    function setupComponents(clientToken) {
        return Promise.all([
            braintree.hostedFields.create({
                authorization: clientToken,
                styles: {
                    input: {
                        // 'font-size': '14px',
                        // 'font-family': 'monospace'
                    }
                },
                fields: {
                    number: {
                        selector: '#AccountNumber',
                    },
                    expirationMonth: {
                        selector: '#ExpirationMonth',
                    },
                    expirationYear: {
                        selector: '#ExpirationYear',
                    },
                    cvv: {
                        selector: '#CVV',
                    }
                }
            }),
            braintree.threeDSecure.create({
                authorization: clientToken,
                version: 2
            })
        ]);
    }

});

