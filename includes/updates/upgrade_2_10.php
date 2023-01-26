<?php

/**
 * Upgrade to version 2.10
 *
 * We are increasing Stripe application fee, but if the site is already being
 * charged at 1%, we want to let them keep that fee.
 */
function pmpro_upgrade_2_10() {
    // Check if we have a live Stripe publishable key and secret key.
    if ( ! empty( pmpro_getOption( 'live_stripe_connect_publishablekey' ) ) && ! empty( pmpro_getOption( 'live_stripe_connect_secretkey' ) ) ) {
        // Site is already set up to charge 1% application fee. We want to let them keep that fee.
        pmpro_setOption( 'stripe_connect_reduced_application_fee', '1' );
    }
}