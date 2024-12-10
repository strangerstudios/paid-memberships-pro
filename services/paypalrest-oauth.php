<?php
// In case the file is loaded directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Make sure the current user can manage options.
if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) {
	exit;
}

// Get the authCode and sharedId from the request.
$authCode    = pmpro_getParam( 'authCode' );
$sharedId    = pmpro_getParam( 'sharedId' );
$environment = pmpro_getParam( 'environment' );

// If we're missing any of the required parameters, bail.
if ( empty( $authCode ) || empty( $sharedId ) || empty( $environment ) ) {
	exit;
}

// Get the nonce.
//$nonce = get_option( 'pmpro_paypalrest_oauth_nonce_' . $environment );
$nonce = get_user_meta( get_current_user_id(), 'pmpro_paypalrest_oauth_nonce_' . $environment, true );
if ( empty( $nonce ) ) {
	exit;
}

// Get the URL for API requests.
$api_url = 'live' === $environment ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

// Get the seller's access token from PayPal.
$access_token_request_url = add_query_arg(
	array(
		'grant_type'    => 'authorization_code',
		'code'          => $authCode,
		'code_verifier' => $nonce,
	),
	$api_url . '/v1/oauth2/token'
);
$access_token_request = wp_remote_post( $access_token_request_url, array(
	'headers' => array(
		// Send the shared ID in Basic Auth (empty client secret after the colon)
		'Authorization' => 'Basic ' . base64_encode( $sharedId . ':' ),
	),
) );

// If we didn't get a valid response, bail.
if ( is_wp_error( $access_token_request ) ) {
	exit;
}

// Get the access token from the response.
$access_token = json_decode( wp_remote_retrieve_body( $access_token_request ) )->access_token;

// Get the seller's REST API credentials from PayPal.
// TODO: Replace PARTNER-MERCHANT-ID with the actual Partner Merchant ID.
$credentials_request = wp_remote_get( $api_url . '/v1/customer/partners/UJ97N7FRGGD9C/merchant-integrations/credentials/', array(
	'headers' => array(
		'Authorization' => 'Bearer ' . $access_token,
	),
) );

// If we didn't get a valid response, bail.
if ( is_wp_error( $credentials_request ) ) {
	exit;
}

// Save the seller's REST API credentials.
$credentials = json_decode( wp_remote_retrieve_body( $credentials_request ) );
update_option( 'pmpro_paypalrest_client_id_' . $environment, $credentials->client_id );
update_option( 'pmpro_paypalrest_client_secret_' . $environment, $credentials->client_secret );
