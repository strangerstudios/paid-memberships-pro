<?php

/**
 * Helper functions for gateways. We should  move this to the gateway classes when we have the opportunity.
 *
 * @since TBD
 */
class PMProGateway_Helper {
	/**
	 * Given a gateway name return the wording for its radio button label.
	 *
	 * @param string $gateway The gateway name.
	 * @since TBD
	 */
	public static function pmpro_gateway_label( $gateway ) {
		switch ( $gateway ) {
			case 'paypalexpress':
			case 'paypalstandard':
				$label = __( 'Pay with PayPal', 'paid-memberships-pro' );
				break;
            case 'paypal':
				$label = __( 'Check Out with PayPal', 'paid-memberships-pro' );
			case 'twocheckout':
				$label = __( 'Pay with 2Checkout', 'paid-memberships-pro' );
				break;
			case 'payfast':
				$label = __( 'Pay with PayFast', 'paid-memberships-pro' );
				break;
            case 'check':
				$label = __( 'Pay by Check', 'paid-memberships-pro' );
				break;
			default:
				$label = __( 'Pay by Credit Card', 'paid-memberships-pro' );
		}
		return $label;
	}
}

