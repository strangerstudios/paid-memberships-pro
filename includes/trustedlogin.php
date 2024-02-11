<?php


// For a plugin or theme:
include_once trailingslashit( PMPRO_DIR ) . 'vendor-namespaced/autoload.php';

/**
 * Configuration for TrustedLogin Client
 *
 *
 * @see https://docs.trustedlogin.com/Client/configuration
 */
$public_key = '7a21b4f6e7eb0914';
$config = [
	'auth' => [
		'api_key' => $public_key,
	],
	'vendor' => [
		'namespace' => 'pmpro',
		'title' => 'Paid Memberships Pro',
		'email' => 'support@paidmembershipspro.com',
		'website' => 'https://embarrassed-elk-ewa3s.test.trustedlogin.dev',
		'support_url' => 'https://www.paidmembershipspro.com/support/',
        'logo_url' => plugins_url( 'images/Paid-Memberships-Pro.png', PMPRO_BASE_FILE ),
	],
	'role' => 'administrator',
    'menu' => [
		'slug' => 'pmpro-dashboard',
		'title' => 'Grant Support Access',
	],
	'webhook' => [
		'url' => 'https://example.com/webhook',
		'create_ticket' => true,
		'debug_data' => true,
	],
];

$config = new PMPro\TrustedLogin\Config( $config );
try {
	new PMPro\TrustedLogin\Client(
		$config
	);
} catch ( \Exception $exception ) {
	error_log( $exception->getMessage() );

	add_action( 'admin_notices', function() use ( $exception ) {

		if( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<div class="notice notice-error">
			<p><?php echo $exception->getMessage(); ?></p>
		</div>
		<?php
	} );
}
