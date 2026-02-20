<?php
// In case the file is loaded directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Minimum PHP requirement for this script.
if ( version_compare( PHP_VERSION, '5.3.29', '<' ) ) {
	return;
}

if ( ! class_exists( 'PMPro_Stripe_Webhook_Handler' ) ) {
	require_once PMPRO_DIR . '/services/class-pmpro-stripe-webhook-handler.php';
}

$event_id = null;
$post_event = null;
$livemode = get_option( 'pmpro_gateway_environment' ) === 'live';

if ( empty( $_REQUEST['event_id'] ) ) {
	$body = @file_get_contents( 'php://input' );
	$post_event = json_decode( $body );
	if ( ! empty( $post_event ) ) {
		$event_id = sanitize_text_field( $post_event->id );
		$livemode = ! empty( $post_event->livemode );
	}
} else {
	$event_id = sanitize_text_field( $_REQUEST['event_id'] );
}

PMPro_Stripe_Webhook_Handler::run(
	array(
		'event_id' => $event_id,
		'livemode' => $livemode,
		'post_event' => $post_event,
	)
);
exit;
