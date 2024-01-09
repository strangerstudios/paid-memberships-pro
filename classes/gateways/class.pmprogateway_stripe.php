<?php
// For compatibility with old library (Namespace Alias)
use Stripe\Customer as Stripe_Customer;
use Stripe\Invoice as Stripe_Invoice;
use Stripe\Plan as Stripe_Plan;
use Stripe\Product as Stripe_Product;
use Stripe\Price as Stripe_Price;
use Stripe\Charge as Stripe_Charge;
use Stripe\PaymentIntent as Stripe_PaymentIntent;
use Stripe\SetupIntent as Stripe_SetupIntent;
use Stripe\PaymentMethod as Stripe_PaymentMethod;
use Stripe\Subscription as Stripe_Subscription;
use Stripe\ApplePayDomain as Stripe_ApplePayDomain;
use Stripe\WebhookEndpoint as Stripe_Webhook;
use Stripe\StripeClient as Stripe_Client; // Used for deleting webhook as of 2.4
use Stripe\Account as Stripe_Account;
use Stripe\Checkout\Session as Stripe_Checkout_Session;

define( "PMPRO_STRIPE_API_VERSION", "2022-11-15" );

//include pmprogateway
require_once( dirname( __FILE__ ) . "/class.pmprogateway.php" );

//load classes init method
add_action( 'init', array( 'PMProGateway_stripe', 'init' ) );

// loading plugin activation actions
add_action( 'activate_paid-memberships-pro', array( 'PMProGateway_stripe', 'pmpro_activation' ) );
add_action( 'deactivate_paid-memberships-pro', array( 'PMProGateway_stripe', 'pmpro_deactivation' ) );
add_filter( 'pmpro_registered_crons', array( 'PMProGateway_stripe', 'register_cron' ) );

/**
 * PMProGateway_stripe Class
 *
 * Handles Stripe integration.
 *
 * @since  1.4
 */
class PMProGateway_stripe extends PMProGateway {
	/**
	 * @var bool    Is the Stripe/PHP Library loaded
	 */
	private static $is_loaded = false;

	/**
	 * Stripe Class Constructor
	 *
	 * @since 1.4
	 */
	function __construct( $gateway = null ) {
		$this->gateway             = $gateway;
		$this->gateway_environment = pmpro_getOption( "gateway_environment" );

		if ( true === $this->dependencies() ) {
			$this->loadStripeLibrary();
			Stripe\Stripe::setApiKey( self::get_secretkey() );
			Stripe\Stripe::setAPIVersion( PMPRO_STRIPE_API_VERSION );
			Stripe\Stripe::setAppInfo(
				'WordPress Paid Memberships Pro',
				PMPRO_VERSION,
				'https://www.paidmembershipspro.com',
				'pp_partner_DKlIQ5DD7SFW3A'
			);
			self::$is_loaded = true;
		}

		return $this->gateway;
	}

	/****************************************
	 ************ STATIC METHODS ************
	 ****************************************/
	/**
	 * Load the Stripe API library.
	 *
	 * @since 1.8
	 * Moved into a method in version 1.8 so we only load it when needed.
	 */
	public static function loadStripeLibrary() {
		//load Stripe library if it hasn't been loaded already (usually by another plugin using Stripe)
		if ( ! class_exists( "Stripe\Stripe" ) ) {
			require_once( PMPRO_DIR . "/includes/lib/Stripe/init.php" );
		} else {
			// Another plugin may have loaded the Stripe library already.
			// Let's log the current Stripe Library info so that we know
			// where to look if we need to troubleshoot library conflicts.
			$previously_loaded_class = new \ReflectionClass( 'Stripe\Stripe' );
			pmpro_track_library_conflict( 'stripe', $previously_loaded_class->getFileName(), Stripe\Stripe::VERSION );
		}
	}

	/**
	 * Run on WP init
	 *
	 * @since 1.8
	 */
	public static function init() {
		//make sure Stripe is a gateway option
		add_filter( 'pmpro_gateways', array( 'PMProGateway_stripe', 'pmpro_gateways' ) );

		//add fields to payment settings
		add_filter( 'pmpro_payment_options', array( 'PMProGateway_stripe', 'pmpro_payment_options' ) );
		add_filter( 'pmpro_payment_option_fields', array(
			'PMProGateway_stripe',
			'pmpro_payment_option_fields'
		), 10, 2 );

		// Show webhook setup banner on payment settings page.
		add_action( 'update_option_pmpro_stripe_payment_flow', array( 'PMProGateway_stripe', 'update_option_pmpro_stripe_payment_flow' ), 10, 1 );
		add_action( 'pmpro_payment_option_fields', array( 'PMProGateway_stripe', 'show_set_up_webhooks_popup' ) );

		//add some fields to edit user page (Updates)
		add_action( 'pmpro_after_membership_level_profile_fields', array(
			'PMProGateway_stripe',
			'user_profile_fields'
		) );
		add_action( 'profile_update', array( 'PMProGateway_stripe', 'user_profile_fields_save' ) );

		//old global RE showing billing address or not
		global $pmpro_stripe_lite;
		$pmpro_stripe_lite = apply_filters( "pmpro_stripe_lite", ! pmpro_getOption( "stripe_billingaddress" ) );    //default is oposite of the stripe_billingaddress setting

		$gateway = pmpro_getGateway();
		if($gateway == "stripe")
		{
			add_filter( 'pmpro_required_billing_fields', array( 'PMProGateway_stripe', 'pmpro_required_billing_fields' ) );
		}

		//updates cron
		add_action( 'pmpro_cron_stripe_subscription_updates', array(
			'PMProGateway_stripe',
			'pmpro_cron_stripe_subscription_updates'
		) );

		//AJAX services for creating/disabling webhooks
		add_action( 'wp_ajax_pmpro_stripe_create_webhook', array( 'PMProGateway_stripe', 'wp_ajax_pmpro_stripe_create_webhook' ) );
		add_action( 'wp_ajax_pmpro_stripe_delete_webhook', array( 'PMProGateway_stripe', 'wp_ajax_pmpro_stripe_delete_webhook' ) );
		add_action( 'wp_ajax_pmpro_stripe_rebuild_webhook', array( 'PMProGateway_stripe', 'wp_ajax_pmpro_stripe_rebuild_webhook' ) );

		/*
            Filter pmpro_next_payment to get actual value
            via the Stripe API. This is disabled by default
            for performance reasons, but you can enable it
            by copying this line into a custom plugin or
            your active theme's functions.php and uncommenting
            it there.
        */
		//add_filter('pmpro_next_payment', array('PMProGateway_stripe', 'pmpro_next_payment'), 10, 3);

		//code to add at checkout if Stripe is the current gateway
		$default_gateway = pmpro_getOption( 'gateway' );
		$current_gateway = pmpro_getGateway();

		// $_REQUEST['review'] here means the PayPal Express review pag
		if ( ( $default_gateway == "stripe" || $current_gateway == "stripe" ) && empty( $_REQUEST['review'] ) ) {
			add_filter( 'pmpro_include_billing_address_fields', array(
				'PMProGateway_stripe',
				'pmpro_include_billing_address_fields'
			) );

			if ( ! self::using_stripe_checkout() ) {
				// On-site checkout flow.
				add_action( 'pmpro_after_checkout_preheader', array(
					'PMProGateway_stripe',
					'pmpro_checkout_after_preheader'
				) );
				add_filter( 'pmpro_include_cardtype_field', array(
					'PMProGateway_stripe',
					'pmpro_include_billing_address_fields'
				) );
				add_action( 'pmpro_billing_preheader', array( 'PMProGateway_stripe', 'pmpro_checkout_after_preheader' ) );
				add_filter( 'pmpro_checkout_order', array( 'PMProGateway_stripe', 'pmpro_checkout_order' ) );
				add_filter( 'pmpro_billing_order', array( 'PMProGateway_stripe', 'pmpro_checkout_order' ) );
				add_filter( 'pmpro_include_payment_information_fields', array(
					'PMProGateway_stripe',
					'pmpro_include_payment_information_fields'
				) );

				//make sure we clean up subs we will be cancelling after checkout before processing
				add_action( 'pmpro_checkout_before_processing', array(
					'PMProGateway_stripe',
					'pmpro_checkout_before_processing'
				) );
			} else {
				// Checkout flow for Stripe Checkout.
				add_filter('pmpro_include_payment_information_fields', array('PMProGateway_stripe', 'show_stripe_checkout_pending_warning'));
				add_filter('pmpro_checkout_before_change_membership_level', array('PMProGateway_stripe', 'pmpro_checkout_before_change_membership_level'), 10, 2);
				add_filter('pmprommpu_gateway_supports_multiple_level_checkout', '__return_false', 10, 2);
				add_action( 'pmpro_billing_preheader', array( 'PMProGateway_stripe', 'pmpro_billing_preheader_stripe_checkout' ) );
			}
		}

		add_action( 'pmpro_payment_option_fields', array( 'PMProGateway_stripe', 'pmpro_set_up_apple_pay' ), 10, 2 );
		add_action( 'init', array( 'PMProGateway_stripe', 'clear_saved_subscriptions' ) );
		add_action( 'pmpro_billing_preheader', array( 'PMProGateway_stripe', 'pmpro_billing_preheader_stripe_customer_portal' ), 5 );

		// Stripe Connect functions.
		add_action( 'admin_init', array( 'PMProGateway_stripe', 'stripe_connect_save_options' ) );
		add_action( 'admin_notices', array( 'PMProGateway_stripe', 'stripe_connect_show_errors' ) );
		add_action( 'admin_notices', array( 'PMProGateway_stripe', 'stripe_connect_deauthorize' ) );

		add_filter( 'pmpro_process_refund_stripe', array( 'PMProGateway_stripe', 'process_refund' ), 10, 2 );
	}

	/**
	 * Clear any saved (preserved) subscription IDs that should have been processed and are now timed out.
	 */
	public static function clear_saved_subscriptions() {

		if ( ! is_user_logged_in() ) {
			return;
		}

		global $current_user;
		$preserve = get_user_meta( $current_user->ID, 'pmpro_stripe_dont_cancel', true );

		// Clean up the subscription timeout values (if applicable)
		if ( ! empty( $preserve ) ) {

			foreach ( $preserve as $sub_id => $timestamp ) {

				// Make sure the ID has "timed out" (more than 3 days since it was last updated/added.
				if ( intval( $timestamp ) >= ( current_time( 'timestamp' ) + ( 3 * DAY_IN_SECONDS ) ) ) {
					unset( $preserve[ $sub_id ] );
				}
			}

			update_user_meta( $current_user->ID, 'pmpro_stripe_dont_cancel', $preserve );
		}
	}

	/**
	 * Make sure Stripe is in the gateways list
	 *
	 * @since 1.8
	 */
	public static function pmpro_gateways( $gateways ) {
		if ( empty( $gateways['stripe'] ) ) {
			$gateways['stripe'] = __( 'Stripe', 'paid-memberships-pro' );
		}

		return $gateways;
	}

	/**
	 * Get a list of payment options that the Stripe gateway needs/supports.
	 *
	 * @since 1.8
	 */
	public static function getGatewayOptions() {
		$options = array(
			'sslseal',
			'nuclear_HTTPS',
			'gateway_environment',
			'stripe_secretkey',
			'stripe_publishablekey',
			'live_stripe_connect_user_id',
			'live_stripe_connect_secretkey',
			'live_stripe_connect_publishablekey',
			'sandbox_stripe_connect_user_id',
			'sandbox_stripe_connect_secretkey',
			'sandbox_stripe_connect_publishablekey',
			'stripe_webhook',
			'stripe_billingaddress',
			'currency',
			'use_ssl',
			'tax_state',
			'tax_rate',
			'stripe_payment_request_button',
			'stripe_payment_flow', // 'onsite' or 'checkout'
			'stripe_update_billing_flow', // 'onsite' or 'portal'
			'stripe_checkout_billing_address', //'auto' or 'required'
			'stripe_tax', // 'no', 'inclusive', 'exclusive'
			'stripe_tax_id_collection_enabled', // '0', '1'
		);

		return $options;
	}

	/**
	 * Set payment options for payment settings page.
	 *
	 * @since 1.8
	 */
	public static function pmpro_payment_options( $options ) {
		//get stripe options
		$stripe_options = self::getGatewayOptions();

		//merge with others.
		$options = array_merge( $stripe_options, $options );

		return $options;
	}

	/**
	 * Display fields for Stripe options.
	 *
	 * @since 1.8
	 */
	public static function pmpro_payment_option_fields( $values, $gateway ) {
		$stripe = new PMProGateway_stripe();

		// Show connect fields.
		$stripe->show_connect_payment_option_fields( true, $values, $gateway ); // Show live connect fields.
		$stripe->show_connect_payment_option_fields( false, $values, $gateway ); // Show sandbox connect fields.

		if ( self::using_legacy_keys() ) {
			// Check if webhook is enabled or not.
			$webhook = self::does_webhook_exist();

			// Check to see if events are missing.
			if ( is_array( $webhook ) && isset( $webhook['enabled_events'] ) ) {
				$events = self::check_missing_webhook_events( $webhook['enabled_events'] );
				if ( $events ) {
					self::update_webhook_events();
				}
			}
		}

		// Break the country cache in case we switched accounts.
		delete_transient( 'pmpro_stripe_account_country' );

		?>
			<tr class="pmpro_settings_divider gateway gateway_stripe" <?php if ( $gateway != "stripe" ) { ?>style="display: none;"<?php } ?>>
			<td colspan="2">
				<hr />
				<h2 class="pmpro_stripe_legacy_keys" <?php if( ! self::show_legacy_keys_settings() ) {?>style="display: none;"<?php }?>><?php esc_html_e( 'Stripe API Settings (Legacy)', 'paid-memberships-pro' ); ?></h2>
				<?php if( ! self::show_legacy_keys_settings() ) {?>
				<p>
					<?php esc_html_e( 'Having trouble connecting through the button above or otherwise need to use your own API keys?', 'paid-memberships-pro' );?>
					<a id="pmpro_stripe_legacy_keys_toggle" href="javascript:void(0);"><?php esc_html_e( 'Click here to use the legacy API settings.', 'paid-memberships-pro' );?></a>
				</p>
				<script>
					// Toggle to show the Stripe legacy keys settings.
					jQuery(document).ready(function(){
						jQuery('#pmpro_stripe_legacy_keys_toggle').click(function(e){
							var btn = jQuery('#pmpro_stripe_legacy_keys_toggle');
							var div = btn.closest('.pmpro_settings_divider');
							btn.parent().remove();
							jQuery('.pmpro_stripe_legacy_keys').show();
							jQuery('.pmpro_stripe_legacy_keys').addClass('gateway_stripe');
							jQuery('#stripe_publishablekey').focus();
						});
					});
				</script>
				<?php } ?>
			</td>
		</tr>
		<tr class="gateway pmpro_stripe_legacy_keys <?php if ( self::show_legacy_keys_settings() ) { echo 'gateway_stripe'; } ?>" <?php if ( $gateway != "stripe" || ! self::show_legacy_keys_settings() ) { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="stripe_publishablekey"><?php esc_html_e( 'Publishable Key', 'paid-memberships-pro' ); ?>:</label>
			</th>
			<td>
				<input type="text" id="stripe_publishablekey" name="stripe_publishablekey" value="<?php echo esc_attr( $values['stripe_publishablekey'] ) ?>" class="regular-text code" />
				<?php
				$public_key_prefix = substr( $values['stripe_publishablekey'], 0, 3 );
				if ( ! empty( $values['stripe_publishablekey'] ) && $public_key_prefix != 'pk_' ) {
					?>
					<p class="pmpro_red"><strong><?php esc_html_e( 'Your Publishable Key appears incorrect.', 'paid-memberships-pro' ); ?></strong></p>
					<?php
				}
				?>
			</td>
		</tr>
		<tr class="gateway pmpro_stripe_legacy_keys <?php if ( self::show_legacy_keys_settings() ) { echo 'gateway_stripe'; } ?>" <?php if ( $gateway != "stripe" ||  ! self::show_legacy_keys_settings() ) { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="stripe_secretkey"><?php esc_html_e( 'Secret Key', 'paid-memberships-pro' ); ?>:</label>
			</th>
			<td>
				<input type="text" id="stripe_secretkey" name="stripe_secretkey" value="<?php echo esc_attr( $values['stripe_secretkey'] ) ?>" autocomplete="off" class="regular-text code pmpro-admin-secure-key" />
			</td>
		</tr>
		<tr class="gateway pmpro_stripe_legacy_keys <?php if ( self::show_legacy_keys_settings() ) { echo 'gateway_stripe'; } ?>" <?php if ( $gateway != "stripe" || ! self::show_legacy_keys_settings() ) { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label><?php esc_html_e( 'Webhook', 'paid-memberships-pro' ); ?>:</label>
			</th>
			<td>
				<?php if ( ! empty( $webhook ) && is_array( $webhook ) && self::show_legacy_keys_settings()) { ?>
				<button type="button" id="pmpro_stripe_create_webhook" class="button button-secondary" style="display: none;"><span class="dashicons dashicons-update-alt"></span> <?php _e( 'Create Webhook' ,'paid-memberships-pro' ); ?></button>
					<?php
						if ( 'disabled' === $webhook['status'] ) {
							// Check webhook status.
							?>
							<div class="notice error inline">
								<p id="pmpro_stripe_webhook_notice" class="pmpro_stripe_webhook_notice"><?php esc_html_e( 'A webhook is set up in Stripe, but it is disabled.', 'paid-memberships-pro' ); ?> <a id="pmpro_stripe_rebuild_webhook" href="#"><?php esc_html_e( 'Rebuild Webhook', 'paid-memberships-pro' ); ?></a></p>
							</div>
							<?php
						} elseif ( $webhook['api_version'] < PMPRO_STRIPE_API_VERSION ) {
							// Check webhook API version.
							?>
							<div class="notice error inline">
								<p id="pmpro_stripe_webhook_notice" class="pmpro_stripe_webhook_notice"><?php esc_html_e( 'A webhook is set up in Stripe, but it is using an old API version.', 'paid-memberships-pro' ); ?> <a id="pmpro_stripe_rebuild_webhook" href="#"><?php esc_html_e( 'Rebuild Webhook', 'paid-memberships-pro' ); ?></a></p>
							</div>
							<?php
						} else {
							?>
							<div class="notice notice-success inline">
								<p id="pmpro_stripe_webhook_notice" class="pmpro_stripe_webhook_notice"><?php esc_html_e( 'Your webhook is enabled.', 'paid-memberships-pro' ); ?> <a id="pmpro_stripe_delete_webhook" href="#"><?php esc_html_e( 'Disable Webhook', 'paid-memberships-pro' ); ?></a></p>
							</div>
							<?php
						}
					} elseif ( self::show_legacy_keys_settings() ) { ?>
						<button type="button" id="pmpro_stripe_create_webhook" class="button button-secondary"><span class="dashicons dashicons-update-alt"></span> <?php _e( 'Create Webhook' ,'paid-memberships-pro' ); ?></button>
						<div class="notice error inline">
							<p id="pmpro_stripe_webhook_notice" class="pmpro_stripe_webhook_notice"><?php esc_html_e('A webhook in Stripe is required to process recurring payments, manage failed payments, and synchronize cancellations.', 'paid-memberships-pro' );?></p>
						</div>
						<?php
					}
				?>
				<p class="description"><?php esc_html_e( 'Webhook URL', 'paid-memberships-pro' ); ?>:
				<code><?php echo esc_url( self::get_site_webhook_url() ); ?></code></p>
			</td>
		</tr>
		<tr class="pmpro_settings_divider gateway gateway_stripe_<?php echo $stripe->gateway_environment; ?>" <?php if ( $gateway != "stripe" ) { ?>style="display: none;"<?php } ?>>
			<td colspan="2">
				<hr />
				<h2><?php esc_html_e( 'Webhook Status', 'paid-memberships-pro' ); ?></h2>
			</td>
		</tr>
		<tr class="gateway gateway_stripe_<?php echo esc_attr( $stripe->gateway_environment ); ?>" <?php if ( $gateway != "stripe" ) { ?>style="display: none;"<?php } ?>>
    	<th scope="row" valign="top">
      </th>
      <td>
				<?php
				if ( ! empty( self::get_secretkey() ) ) {
					$required_webhook_events = $stripe->webhook_events();
					sort( $required_webhook_events );

					$webhook_event_data = array();

					$failed_webhooks = array();
					$missing_webhooks = array();
					$working_webhooks = array();
					// For sites that tracked "last webhook recieved" before we started tracking webhook events individually,
					// we want to ignore events that were sent by Stripe before site was updated to start tracking individual events.
					$legacy_last_webhook_recieved_timestamp = get_option( 'pmpro_stripe_last_webhook_received_' . $stripe->gateway_environment );
					foreach ( $required_webhook_events as $required_webhook_event ) {
						$event_data = array( 'name' => $required_webhook_event );

						$last_received = get_option( 'pmpro_stripe_webhook_last_received_' . $stripe->gateway_environment . '_' . $required_webhook_event );
						$event_data['last_received'] = empty( $last_received ) ? esc_html__( 'Never Received', 'paid-memberships-pro' ) : date_i18n( get_option('date_format') . ' ' . get_option('time_format'), $last_received );

						// Check the cache for a recently sent webhook.
						$cache_key     = 'pmpro_stripe_last_webhook_sent_' . $stripe->gateway_environment . '_' . $required_webhook_event;
						$recently_sent = get_transient( $cache_key );

						if ( false === $recently_sent ) {
							// No cache, so check Stripe for a recently sent webhook.
							// We want to ignore events that were sent by Stripe before site was updated to start tracking individual events.
							// (We don't want to ignore events that were sent by Stripe before the site was updated to start tracking individual events
							//  if the site was updated to start tracking individual events before the webhook was sent.
							$event_query_arr = array(
								'limit' => 1,
								'created' => array(
									'lt' => time() - 60, // Ignore events created in the last 60 seconds in case we haven't finished processing them yet.
								),
								'type' => $required_webhook_event,
							);
							if ( ! empty( $legacy_last_webhook_recieved_timestamp ) ) {
								$event_query_arr['created']['gt'] = strtotime( $legacy_last_webhook_recieved_timestamp );
							}

							try {
								$recently_sent_arr = Stripe\Event::all( $event_query_arr );
								$recently_sent     = empty( $recently_sent_arr->data[0] ) ? '' : $recently_sent_arr->data[0];
							} catch ( \Throwable $th ) {
								$recently_sent = $th->getMessage();
							} catch ( \Exception $e ) {
								$recently_sent = $e->getMessage();
							}

							// Cache the result for 5 minutes.
							set_transient( $cache_key, $recently_sent, 5 * MINUTE_IN_SECONDS );
						}

						if ( ! empty( $recently_sent ) && ! is_string( $recently_sent ) ) {
							if ( $last_received >= $recently_sent->created ) {
								$event_data['status'] =  '<span style="color: green;">' . esc_html__( 'Working', 'paid-memberships-pro' ) . '</span>';
								$working_webhooks[] = $event_data;
							} else {
								$event_data['status'] = '<span style="color: red;">' . esc_html__( 'Last Sent ', 'paid-memberships-pro' ) . date_i18n( get_option('date_format') . ' ' . get_option('time_format'), $recently_sent->created ) . '</span>';
								$failed_webhooks[] = $event_data;
							}
						} elseif ( is_string( $recently_sent ) && ! empty( $recently_sent ) ) {
							// An error was returned from the Stripe API. Show it.
							$event_data['status'] = '<span style="color: red;">' . esc_html__( 'Error: ', 'paid-memberships-pro' ) . $recently_sent . '</span>';
							$failed_webhooks[] = $event_data;
						} else {
							if ( ! empty( $last_received ) ) {
								$event_data['status'] = '<span style="color: green;">' . esc_html__( 'Working', 'paid-memberships-pro' ) . '</span>';
								$working_webhooks[] = $event_data;
							} else {
								$event_data['status'] = '<span style="color: grey;">' . esc_html__( 'N/A', 'paid-memberships-pro' ) . '</span>';
								$missing_webhooks[] = $event_data;
							}
						}
					}
					if ( ! empty( $failed_webhooks ) ) {
						echo '<div class="notice error inline"><p>'. esc_html__( 'Some webhooks recently sent by Stripe have not been received by your website. Please ensure that you have a webhook set up in Stripe for the Webhook URL shown below with all of the listed event types active. To test an event type again, please resend the most recent webhook event of that type from the Stripe webhook settings page or wait for it to be sent again in the future.', 'paid-memberships-pro' ) . '</p></div>';
					} elseif ( ! empty( $missing_webhooks ) ) {
						echo '<div class="notice inline"><p>'. esc_html__( 'Recent webhook attempts appear to have worked correctly, but there are some event types that have not been checked. Those event types will be checked as they are sent by Stripe. In the meantime, please ensure that you have a webhook set up in Stripe for the Webhook URL shown below with all of the listed event types active.', 'paid-memberships-pro' ) . '</p></div>';
					} else {
						echo '<div class="notice notice-success inline"><p>'. esc_html__( 'All webhooks appear to be working correctly.', 'paid-memberships-pro' ) . '</p></div>';
					}
					?>
					<div class="widgets-holder-wrap pmpro_scrollable">
						<table class="wp-list-table widefat striped fixed">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Event Type', 'paid-memberships-pro' ); ?></th>
									<th><?php esc_html_e( 'Last Received', 'paid-memberships-pro' ); ?></th>
									<th><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></th>
								</tr>
							</thead>
							<?php
								$ordered_webhooks = array_merge( $failed_webhooks, $missing_webhooks, $working_webhooks );
								foreach ( $ordered_webhooks as $webhook_event ) {
									?>
									<tr>
										<td><?php echo $webhook_event['name']; ?></td>
										<td><?php echo esc_html( $webhook_event['last_received'] ); ?></td>
										<td><?php echo $webhook_event['status']; ?></td>
									</tr>
									<?php
								}
							?>
						</table>
					</div>
					<?php
				}
				?>
				<p class="description"><?php esc_html_e( 'Webhook URL', 'paid-memberships-pro' ); ?>:
				<code><?php echo esc_html( self::get_site_webhook_url() ); ?></code></p>
            </td>
        </tr>
		<tr class="pmpro_settings_divider gateway gateway_stripe" <?php if ( $gateway != "stripe" ) { ?>style="display: none;"<?php } ?>>
			<td colspan="2">
				<hr />
				<h2><?php esc_html_e( 'Other Stripe Settings', 'paid-memberships-pro' ); ?></h2>
			</td>
		</tr>
		<tr class="gateway gateway_stripe" <?php if ( $gateway != "stripe" ) { ?>style="display: none;"<?php } ?>>
			<th><?php esc_html_e( 'Stripe API Version', 'paid-memberships-pro' ); ?>:</th>
			<td><code><?php echo esc_html( PMPRO_STRIPE_API_VERSION ); ?></code></td>
		</tr>
		<tr class="gateway gateway_stripe" <?php if ( $gateway != "stripe" ) { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="stripe_payment_flow"><?php esc_html_e( 'Payment Flow', 'paid-memberships-pro' ); ?>:</label>
			</th>
			<td>
				<select id="stripe_payment_flow" name="stripe_payment_flow">
					<option value="onsite" <?php selected( $values['stripe_payment_flow'], 'onsite' ); ?>><?php esc_html_e( 'Accept payments on this site', 'paid-memberships-pro' ); ?></option>
					<option value="checkout" <?php selected( $values['stripe_payment_flow'], 'checkout' ); ?>><?php esc_html_e( 'Accept payments in Stripe (Stripe Checkout)', 'paid-memberships-pro' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Embed the payment information fields on your Membership Checkout page or use the Stripe-hosted payment page (Stripe Checkout). If using Stripe Checkout, be sure that all webhook events listed above are set up in Stripe.', 'paid-memberships-pro' ); ?>
			</td>
		</tr>
		<tr class="gateway gateway_stripe" <?php if ( $gateway != "stripe" ) { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="stripe_update_billing_flow"><?php esc_html_e( 'Update Billing Flow', 'paid-memberships-pro' ); ?>:</label>
			</th>
			<td>
				<select id="stripe_update_billing_flow" name="stripe_update_billing_flow">
					<option value="onsite"><?php esc_html_e( 'Update billing on this site', 'paid-memberships-pro' ); ?></option>
					<option value="portal" <?php if ( $values['stripe_update_billing_flow'] === 'portal' ) { ?>selected="selected"<?php } ?>><?php esc_html_e( 'Update billing in the Stripe Customer Portal', 'paid-memberships-pro' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Embed the billing information fields on your Membership Billing page or use the Stripe Customer Portal hosted by Stripe.', 'paid-memberships-pro' ); ?></p>
			</td>
		</tr>
		<tr class="gateway gateway_stripe" <?php if ( $gateway != "stripe" ) { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="stripe_billingaddress"><?php esc_html_e( 'Show Billing Address Fields in PMPro Checkout Form', 'paid-memberships-pro' ); ?>:</label>
			</th>
			<td>
				<select id="stripe_billingaddress" name="stripe_billingaddress">
					<option value="0"
							<?php if ( empty( $values['stripe_billingaddress'] ) ) { ?>selected="selected"<?php } ?>><?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></option>
					<option value="1"
							<?php if ( ! empty( $values['stripe_billingaddress'] ) ) { ?>selected="selected"<?php } ?>><?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></option>
				</select>
				<p class="description"><?php echo wp_kses_post( __( "Stripe doesn't require billing address fields. Choose 'No' to hide them on the checkout page.<br /><strong>If No, make sure you disable address verification in the Stripe dashboard settings.</strong>", 'paid-memberships-pro' ) ); ?></p>
			</td>
		</tr>
		<tr class="gateway gateway_stripe" <?php if ( $gateway != "stripe" ) { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="stripe_payment_request_button"><?php esc_html_e( 'Show Payment Request Button for On-Site Payments', 'paid-memberships-pro' ); ?>:</label>
			</th>
			<td>
				<select id="stripe_payment_request_button" name="stripe_payment_request_button">
					<option value="0"
							<?php if ( empty( $values['stripe_payment_request_button'] ) ) { ?>selected="selected"<?php } ?>><?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></option>
					<option value="1"
							<?php if ( ! empty( $values['stripe_payment_request_button'] ) ) { ?>selected="selected"<?php } ?>><?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></option>
				</select>
				<?php
					$allowed_stripe_payment_button_html = array (
						'a' => array (
							'href' => array(),
							'target' => array(),
							'title' => array(),
						),
					);
				?>
				<p class="description"><?php echo sprintf( wp_kses( __( 'Allow users to pay using Apple Pay, Google Pay, or Microsoft Pay depending on their browser. When enabled, your domain will automatically be registered with Apple and a domain association file will be hosted on your site. <a target="_blank" href="%s" title="More Information about the domain association file for Apple Pay">More Information</a>', 'paid-memberships-pro' ), $allowed_stripe_payment_button_html ), 'https://stripe.com/docs/stripe-js/elements/payment-request-button#verifying-your-domain-with-apple-pay' ); ?></p>
					<?php
					if ( ! empty( $values['stripe_payment_request_button'] ) ) {
						// Are there any issues with how the payment request button is set up?
						$payment_request_error = null;
						$allowed_payment_request_error_html = array (
							'a' => array (
								'href' => array(),
								'target' => array(),
								'title' => array(),
							),
						);
						if ( empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off" ) {
							$payment_request_error_escaped = sprintf( wp_kses( __( 'This webpage is being served over HTTP, but the Stripe Payment Request Button will only work on pages being served over HTTPS. To resolve this, you must <a target="_blank" href="%s" title="Configuring WordPress to Always Use HTTPS/SSL">set up WordPress to always use HTTPS</a>.', 'paid-memberships-pro' ), $allowed_payment_request_error_html ), 'https://www.paidmembershipspro.com/configuring-wordpress-always-use-httpsssl/?utm_source=plugin&utm_medium=pmpro-paymentsettings&utm_campaign=blog&utm_content=configure-https' );
						} elseif ( self::using_legacy_keys() && substr( $values['stripe_publishablekey'], 0, 8 ) !== "pk_live_" && substr( $values['stripe_publishablekey'], 0, 8 ) !== "pk_test_" ) {
							$payment_request_error_escaped = sprintf( wp_kses( __( 'It looks like you are using an older Stripe publishable key. In order to use the Payment Request Button feature, you will need to update your API key, which will be prefixed with "pk_live_" or "pk_test_". <a target="_blank" href="%s" title="Stripe Dashboard API Key Settings">Log in to your Stripe Dashboard to roll your publishable key</a>.', 'paid-memberships-pro' ), $allowed_payment_request_error_html ), 'https://dashboard.stripe.com/account/apikeys' );
						} elseif ( self::using_legacy_keys() && substr( $values['stripe_secretkey'], 0, 8 ) !== "sk_live_" && substr( $values['stripe_secretkey'], 0, 8 ) !== "sk_test_" ) {
							$payment_request_error_escaped = sprintf( wp_kses( __( 'It looks like you are using an older Stripe secret key. In order to use the Payment Request Button feature, you will need to update your API key, which will be prefixed with "sk_live_" or "sk_test_". <a target="_blank" href="%s" title="Stripe Dashboard API Key Settings">Log in to your Stripe Dashboard to roll your secret key</a>.', 'paid-memberships-pro' ), $allowed_payment_request_error_html ), 'https://dashboard.stripe.com/account/apikeys' );
						} elseif ( ! $stripe->pmpro_does_apple_pay_domain_exist() ) {
							$payment_request_error_escaped = sprintf( wp_kses( __( 'Your domain could not be registered with Apple to enable Apple Pay. Please try <a target="_blank" href="%s" title="Apple Pay Settings Page in Stripe">registering your domain manually from the Apple Pay settings page in Stripe</a>.', 'paid-memberships-pro' ), $allowed_payment_request_error_html ), 'https://dashboard.stripe.com/settings/payments/apple_pay' );
						}
						if ( ! empty( $payment_request_error_escaped ) ) {
							?>
							<div class="notice error inline">
								<p id="pmpro_stripe_payment_request_button_notice"><?php echo( $payment_request_error_escaped ); ?></p>
							</div>
							<?php
						}
					}
					?>
			</td>
		</tr>
		<tr class="gateway gateway_stripe" <?php if ( $gateway != "stripe" ) { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="stripe_checkout_billing_address"><?php esc_html_e( 'Collect Billing Address in Stripe Checkout', 'paid-memberships-pro' ); ?>:</label>
			</th>
			<td>
				<select id="stripe_checkout_billing_address" name="stripe_checkout_billing_address">
					<option value="auto"><?php esc_html_e( 'Only when necessary', 'paid-memberships-pro' ); ?></option>
					<option value="required" <?php if ( 'required' === $values['stripe_checkout_billing_address'] ) { ?>selected="selected"<?php } ?>><?php esc_html_e( 'Always', 'paid-memberships-pro' ); ?></option>
				</select>
			</td>
		</tr>
		<tr class="gateway gateway_stripe" <?php if ( $gateway != "stripe" ) { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="stripe_tax"><?php esc_html_e( 'Calculate Tax in Stripe Checkout', 'paid-memberships-pro' ); ?>:</label>
			</th>
			<td>
				<select id="stripe_tax" name="stripe_tax">
					<option value="no"><?php esc_html_e( 'Do not calculate tax', 'paid-memberships-pro' ); ?></option>
					<option value="inclusive" <?php if ( $values['stripe_tax'] === 'inclusive' ) { ?>selected="selected"<?php } ?>><?php esc_html_e( 'Membership price includes tax', 'paid-memberships-pro' ); ?></option>
					<option value="exclusive" <?php if ( $values['stripe_tax'] === 'exclusive' ) { ?>selected="selected"<?php } ?>><?php esc_html_e( 'Calculate tax on top of membership price', 'paid-memberships-pro' ); ?></option>
				</select>
				<?php
					$allowed_stripe_tax_description_html = array (
						'a' => array (
							'href' => array(),
							'target' => array(),
							'title' => array(),
						),
					);
				?>
				<p class="description"><?php echo sprintf( wp_kses( __( 'Stripe Tax is only available when using Stripe Checkout (the Stripe-hosted payment page). You must <a target="_blank" href="%s">activate Stripe Tax</a> in your Stripe dashboard. <a target="_blank" href="%s">More information about Stripe Tax »</a>', 'paid-memberships-pro' ), $allowed_stripe_tax_description_html ), 'https://stripe.com/tax', 'https://dashboard.stripe.com/settings/tax/activate' ); ?></p>
			</td>
		</tr>
		<tr class="gateway gateway_stripe gateway_stripe_checkout_fields" <?php if ( $gateway != "stripe"  ) { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="stripe_tax_id_collection_enabled"><?php esc_html_e( 'Collect Tax IDs in Stripe Checkout', 'paid-memberships-pro' ); ?>:</label>
			</th>
			<td>
				<select id="stripe_tax_id_collection_enabled" name="stripe_tax_id_collection_enabled">
					<option value="0"><?php esc_html_e( 'No, do not collect tax IDs.', 'paid-memberships-pro' ); ?></option>
					<option value="1" <?php if ( ! empty( $values['stripe_tax_id_collection_enabled'] ) ) { ?>selected="selected"<?php } ?>><?php esc_html_e( 'Yes, collect tax IDs.', 'paid-memberships-pro' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Tax IDs are only collected if you have enabled Stripe Tax. Stripe only performs automatic validation for ABN, EU VAT, and GB VAT numbers. You must verify that provided tax IDs are valid during the Session for all other numbers.', 'paid-memberships-pro' ); ?></p>
			</td>
		</tr>
		<?php if ( ! function_exists( 'pmproappe_pmpro_valid_gateways' ) ) {
				$allowed_appe_html = array (
					'a' => array (
						'href' => array(),
						'target' => array(),
						'title' => array(),
					),
				);
				echo '<tr class="gateway gateway_stripe"';
				if ( $gateway != "stripe" ) {
					echo ' style="display: none;"';
				}
				echo '><th>&nbsp;</th><td><p class="description">' . sprintf( wp_kses( __( 'Optional: Offer PayPal Express as an option at checkout using the <a target="_blank" href="%s" title="Paid Memberships Pro - Add PayPal Express Option at Checkout Add On">Add PayPal Express Add On</a>.', 'paid-memberships-pro' ), $allowed_appe_html ), 'https://www.paidmembershipspro.com/add-ons/pmpro-add-paypal-express-option-checkout/?utm_source=plugin&utm_medium=pmpro-paymentsettings&utm_campaign=add-ons&utm_content=pmpro-add-paypal-express-option-checkout' ) . '</p></td></tr>';
		}
	}

	/**
	 * AJAX callback to create webhooks.
	 */
	public static function wp_ajax_pmpro_stripe_create_webhook( $silent = false ) {
		$secretkey = sanitize_text_field( $_REQUEST['secretkey'] );

		$stripe = new PMProGateway_stripe();
		Stripe\Stripe::setApiKey( $secretkey );

		$update_webhook_response = $stripe::update_webhook_events();

		if ( empty( $update_webhook_response ) || is_wp_error( $update_webhook_response ) ) {
			$message = empty( $update_webhook_response ) ? __( 'Webhook creation failed. You might already have a webhook set up.', 'paid-memberships-pro' ) : $update_webhook_response->get_error_message();
			$r = array(
				'success' => false,
				'notice' => 'error',
				'message' => esc_html( $message ),
				'response' => esc_html( $update_webhook_response )
			);
		} else {
			$r = array(
				'success' => true,
				'notice' => 'notice-success',
				'message' => esc_html__( 'Your webhook is enabled.', 'paid-memberships-pro' ),
				'response' => esc_html( $update_webhook_response )
			);
		}

		if ( $silent ) {
			return $r;
		} else {
			echo json_encode( $r );	// Values escaped above.
			exit;
		}
	}

	/**
	 * AJAX callback to disable webhooks.
	 */
	public static function wp_ajax_pmpro_stripe_delete_webhook( $silent = false ) {
		$secretkey = sanitize_text_field( $_REQUEST['secretkey'] );

		$stripe = new PMProGateway_stripe();
		Stripe\Stripe::setApiKey( $secretkey );

		$webhook = self::does_webhook_exist();

		$r = array(
			'success' => true,
			'notice' => 'error',
			'message' => __( 'A webhook in Stripe is required to process recurring payments, manage failed payments, and synchronize cancellations.', 'paid-memberships-pro' )
		);
		if ( ! empty( $webhook ) ) {
			$delete_webhook_response = $stripe::delete_webhook( $webhook, $secretkey );

			if ( is_wp_error( $delete_webhook_response ) || empty( $delete_webhook_response['deleted'] ) || $delete_webhook_response['deleted'] != true ) {
				$message = is_wp_error( $delete_webhook_response ) ? $delete_webhook_response->get_error_message() : __( 'There was an error deleting the webhook.', 'paid-memberships-pro' );
				$r = array(
					'success' => false,
					'notice' => 'error',
					'message' => esc_html( $message ),
				);
			}
			$r['response'] = esc_html( $delete_webhook_response );
		}

		if ( $silent ) {
			return $r;
		} else {
			echo json_encode( $r );	// Values escaped above.
			exit;
		}
	}

	/**
	 * AJAX callback to rebuild webhook.
	 */
	public static function wp_ajax_pmpro_stripe_rebuild_webhook() {
		// First try to delete the webhook.
		$r = self::wp_ajax_pmpro_stripe_delete_webhook( true ) ;
		if ( $r['success'] ) {
			// Webhook was successfully deleted. Now make a new one.
			$r = self::wp_ajax_pmpro_stripe_create_webhook( true );
			if ( ! $r['success'] ) {
				$r['message'] = esc_html__( 'Webhook creation failed. Please refresh and try again.', 'paid-memberships-pro' );
			}
		}

		echo json_encode( $r ); // Values escaped above.
		exit;
	}

	/**
	 * Code added to checkout preheader.
	 *
	 * @since 1.8
	 */
	public static function pmpro_checkout_after_preheader( $order ) {
		global $gateway, $pmpro_level, $current_user, $pmpro_requirebilling, $pmpro_pages, $pmpro_currency;

		$default_gateway = pmpro_getOption( "gateway" );

		if ( $gateway == "stripe" || $default_gateway == "stripe" ) {
			//stripe js library
			wp_enqueue_script( "stripe", "https://js.stripe.com/v3/", array(), null );

			if ( ! function_exists( 'pmpro_stripe_javascript' ) ) {
				$localize_vars = array(
					'publishableKey' => self::get_publishablekey(),
					'user_id'        => self::get_connect_user_id(),
					'verifyAddress'  => apply_filters( 'pmpro_stripe_verify_address', pmpro_getOption( 'stripe_billingaddress' ) ),
					'ajaxUrl'        => admin_url( "admin-ajax.php" ),
					'msgAuthenticationValidated' => __( 'Verification steps confirmed. Your payment is processing.', 'paid-memberships-pro' ),
					'pmpro_require_billing' => $pmpro_requirebilling,
					'restUrl' => get_rest_url(),
					'siteName' => get_bloginfo( 'name' ),
					'updatePaymentRequestButton' => apply_filters( 'pmpro_stripe_update_payment_request_button', true ),
					'currency' => strtolower( $pmpro_currency ),
					'accountCountry' => self::get_account_country(),
				);

				if ( ! empty( $order ) ) {
					if ( ! empty( $order->stripe_payment_intent ) ) {
						$localize_vars['paymentIntent'] = $order->stripe_payment_intent;
					}
					if ( ! empty( $order->stripe_setup_intent ) ) {
						$localize_vars['setupIntent']  = $order->stripe_setup_intent;
					}
				}

				wp_register_script( 'pmpro_stripe',
					plugins_url( 'js/pmpro-stripe.js', PMPRO_BASE_FILE ),
					array( 'jquery' ),
					PMPRO_VERSION );
				wp_localize_script( 'pmpro_stripe', 'pmproStripe', $localize_vars );
				wp_enqueue_script( 'pmpro_stripe' );
			}
		}
	}

	/**
	 * Don't require the CVV.
	 * Don't require address fields if they are set to hide.
	 */
	public static function pmpro_required_billing_fields( $fields ) {
		global $pmpro_stripe_lite, $current_user, $bemail, $bconfirmemail;

		//CVV is not required if set that way at Stripe. The Stripe JS will require it if it is required.
		$remove = [ 'CVV' ];

		//if using stripe lite, remove some fields from the required array
		if ( $pmpro_stripe_lite ) {
			$remove = array_merge( $remove, [ 'bfirstname', 'blastname', 'baddress', 'bcity', 'bstate', 'bzipcode', 'bphone', 'bcountry', 'CardType' ] );
		}

		// If a user is logged in, don't require bemail either
		if ( ! empty( $current_user->user_email ) ) {
			$remove        = array_merge( $remove, [ 'bemail' ] );
			$bemail        = $current_user->user_email;
			$bconfirmemail = $bemail;
		}

		// If using Stripe Checkout, don't require card information.
		if ( self::using_stripe_checkout() ) {
			$remove = array_merge( $remove, [ 'CardType', 'AccountNumber', 'ExpirationMonth', 'ExpirationYear', 'CVV' ] );
		}

		// Remove the fields.
		foreach ( $remove as $field ) {
			unset( $fields[ $field ] );
		}

		return $fields;
	}

	/**
	 * Filtering orders at checkout.
	 *
	 * @since 1.8
	 */
	public static function pmpro_checkout_order( $morder ) {

		// Create a code for the order.
		if ( empty( $morder->code ) ) {
			$morder->code = $morder->getRandomCode();
		}

		// Add the PaymentIntent ID to the order.
		if ( ! empty ( $_REQUEST['payment_intent_id'] ) ) {
			$morder->payment_intent_id = sanitize_text_field( $_REQUEST['payment_intent_id'] );
		}

		// Add the SetupIntent ID to the order.
		if ( ! empty ( $_REQUEST['setup_intent_id'] ) ) {
			$morder->setup_intent_id = sanitize_text_field( $_REQUEST['setup_intent_id'] );
		}

		// Add the PaymentMethod ID to the order.
		if ( ! empty ( $_REQUEST['payment_method_id'] ) ) {
			$morder->payment_method_id = sanitize_text_field( $_REQUEST['payment_method_id'] );
		}

		//stripe lite code to get name from other sources if available
		global $pmpro_stripe_lite, $current_user;
		if ( ! empty( $pmpro_stripe_lite ) && empty( $morder->FirstName ) && empty( $morder->LastName ) ) {
			if ( ! empty( $current_user->ID ) ) {
				$morder->FirstName = get_user_meta( $current_user->ID, "first_name", true );
				$morder->LastName  = get_user_meta( $current_user->ID, "last_name", true );
			} elseif ( ! empty( $_REQUEST['first_name'] ) && ! empty( $_REQUEST['last_name'] ) ) {
				$morder->FirstName = sanitize_text_field( $_REQUEST['first_name'] );
				$morder->LastName  = sanitize_text_field( $_REQUEST['last_name'] );
			}
		}

		return $morder;
	}

	/**
	 * Code to run after checkout
	 *
	 * @since 1.8
	 */
	public static function pmpro_after_checkout( $user_id, $morder ) {
		global $gateway;

		if ( $gateway == "stripe" ) {
			if ( self::$is_loaded && ! empty( $morder ) && ! empty( $morder->Gateway ) && ! empty( $morder->Gateway->customer ) && ! empty( $morder->Gateway->customer->id ) ) {
				update_user_meta( $user_id, "pmpro_stripe_customerid", $morder->Gateway->customer->id );
			}
		}
	}

	/**
	 * Check settings if billing address should be shown.
	 * @since 1.8
	 */
	public static function pmpro_include_billing_address_fields( $include ) {
		//check settings RE showing billing address
		if ( ! pmpro_getOption( "stripe_billingaddress" ) ) {
			$include = false;
		}

		return $include;
	}

	/**
	 * Use our own payment fields at checkout. (Remove the name attributes.)
	 * @since 1.8
	 */
	public static function pmpro_include_payment_information_fields( $include ) {
		//global vars
		global $pmpro_requirebilling, $pmpro_show_discount_code, $discount_code, $CardType, $AccountNumber, $ExpirationMonth, $ExpirationYear;

		//include ours
		?>
        <div id="pmpro_payment_information_fields" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout', 'pmpro_payment_information_fields' ) ); ?>"
		     <?php if ( ! $pmpro_requirebilling || apply_filters( "pmpro_hide_payment_information_fields", false ) ) { ?>style="display: none;"<?php } ?>>
            <h2>
                <span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-h2-name' ) ); ?>"><?php esc_html_e( 'Payment Information', 'paid-memberships-pro' ); ?></span>
                <span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-h2-msg' ) ); ?>"><?php esc_html_e( 'We accept all major credit cards', 'paid-memberships-pro' ); ?></span>
            </h2>
			<?php $sslseal = pmpro_getOption( "sslseal" ); ?>
			<?php if ( ! empty( $sslseal ) ) { ?>
            <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-fields-display-seal' ) ); ?>">
				<?php } ?>
		<?php
			if ( pmpro_getOption( 'stripe_payment_request_button' ) ) { ?>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-field pmpro_checkout-field-payment-request-button', 'pmpro_checkout-field-payment-request-button' ) ); ?>">
					<div id="payment-request-button"><!-- Alternate payment method will be inserted here. --></div>
					<h4 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-field pmpro_payment-credit-card', 'pmpro_payment-credit-card' ) ); ?>"><?php esc_html_e( 'Pay with Credit Card', 'paid-memberships-pro' ); ?></h4>
				</div>
				<?php
			}
		?>
                <div class="pmpro_checkout-fields<?php if ( ! empty( $sslseal ) ) { ?> pmpro_checkout-fields-leftcol<?php } ?>">
                    <input type="hidden" id="CardType" name="CardType"
                           value="<?php echo esc_attr( $CardType ); ?>"/>
                    <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-field pmpro_payment-account-number', 'pmpro_payment-account-number' ) ); ?>">
                        <label for="AccountNumber"><?php esc_html_e( 'Card Number', 'paid-memberships-pro' ); ?></label>
                        <div id="AccountNumber"></div>
                    </div>
                    <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-field pmpro_payment-expiration', 'pmpro_payment-expiration' ) ); ?>">
                        <label for="Expiry"><?php esc_html_e( 'Expiration Date', 'paid-memberships-pro' ); ?></label>
                        <div id="Expiry"></div>
                    </div>
					<?php
					$pmpro_show_cvv = apply_filters( "pmpro_show_cvv", true );
					if ( $pmpro_show_cvv ) { ?>
                        <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-field pmpro_payment-cvv', 'pmpro_payment-cvv' ) ); ?>">
                            <label for="CVV"><?php esc_html_e( 'CVC', 'paid-memberships-pro' ); ?></label>
                            <div id="CVV"></div>
                        </div>
					<?php } ?>
					<?php if ( $pmpro_show_discount_code ) { ?>
                        <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-field pmpro_payment-discount-code', 'pmpro_payment-discount-code' ) ); ?>">
                            <label for="discount_code"><?php esc_html_e( 'Discount Code', 'paid-memberships-pro' ); ?></label>
                            <input class="<?php echo esc_attr( pmpro_get_element_class( 'input pmpro_alter_price', 'discount_code' ) ); ?>"
                                   id="discount_code" name="discount_code" type="text" size="10"
                                   value="<?php echo esc_attr( $discount_code ) ?>"/>
                            <input aria-label="<?php esc_html_e( 'Apply discount code', 'paid-memberships-pro' ); ?>" type="button" id="discount_code_button" name="discount_code_button"
                                   value="<?php esc_attr_e( 'Apply', 'paid-memberships-pro' ); ?>"/>
                            <p id="discount_code_message" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message' ) ); ?>" style="display: none;"></p>
                        </div>
					<?php } ?>
                </div> <!-- end pmpro_checkout-fields -->
				<?php if ( ! empty( $sslseal ) ) { ?>
                <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-fields-rightcol pmpro_sslseal', 'pmpro_sslseal' ) ); ?>"><?php echo stripslashes( $sslseal ); ?></div>
            </div> <!-- end pmpro_checkout-fields-display-seal -->
		<?php } ?>
        </div> <!-- end pmpro_payment_information_fields -->
		<?php

		//don't include the default
		return false;
	}

	/**
	 * Fields shown on edit user page
	 *
	 * @since 1.8
	 */
	public static function user_profile_fields( $user ) {
		global $wpdb, $current_user, $pmpro_currency_symbol;

		//make sure the current user has privileges
		$membership_level_capability = apply_filters( "pmpro_edit_member_capability", "manage_options" );
		if ( ! current_user_can( $membership_level_capability ) ) {
			return false;
		}

		//more privelges they should have
		$show_membership_level = apply_filters( "pmpro_profile_show_membership_level", true, $user );
		if ( ! $show_membership_level ) {
			return false;
		}

		// Get the user's Stripe Customer if they have one.
		$stripe = new PMProGateway_Stripe();
		$customer = $stripe->get_customer_for_user( $user->ID );

		// Check whether we have a Stripe Customer.
		if ( ! empty( $customer ) ) {
			// Get the link to edit the customer.
			?>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Stripe Customer', 'paid-memberships-pro' ); ?></th>
					<td>
						<a target="_blank" href="<?php echo esc_url( 'https://dashboard.stripe.com/' . ( pmpro_getOption( 'gateway_environment' ) == 'sandbox' ? 'test/' : '' ) . 'customers/' . $customer->id ); ?>"><?php esc_html_e( 'Edit customer in Stripe', 'paid-memberships-pro' ); ?></a>
					</td>
				</tr>
			</table>
			<?php
				if ( ! empty( $user->pmpro_stripe_updates ) && is_array( $user->pmpro_stripe_updates ) ) {
					$stripe->user_profile_fields_subscription_updates( $user, $customer );
				}
			?>
			<?php
		}
	}

	/**
	 * Temporary function to allow users to delete subscription updates.
	 * Will be removed once subscription updates are completely deprecated.
	 *
	 * @since 2.7.0.
	 */
	static function user_profile_fields_save( $user_id ) {
		global $wpdb;
		//check capabilities
		$membership_level_capability = apply_filters( "pmpro_edit_member_capability", "manage_options" );
		if ( ! current_user_can( $membership_level_capability ) ) {
			return false;
		}

		//make sure subscription updates were shown.
		if ( ! isset( $_POST['pmpro_subscription_updates_visible'] ) ) {
			return;
		}

		// Check whether all updates were deleted.
		if ( ! isset( $_POST['updates_when'] ) || ! is_array( $_POST['updates_when'] ) ) {
			delete_user_meta( $user_id, 'pmpro_stripe_updates' );
			delete_user_meta( $user_id, 'pmpro_stripe_next_on_date_update' );
			return;
		}

		//vars
		$updates             = array();
		$next_on_date_update = "";

		//build array of updates
		for ( $i = 0; $i < count( $_POST['updates_when'] ); $i ++ ) {
			$update = array();

			//all updates have these values
			$update['when']           = pmpro_sanitize_with_safelist( sanitize_text_field( $_POST['updates_when'][ $i ] ), array(
				'now',
				'payment',
				'date'
			) );
			$update['billing_amount'] = sanitize_text_field( $_POST['updates_billing_amount'][ $i ] );
			$update['cycle_number']   = intval( $_POST['updates_cycle_number'][ $i ] );
			$update['cycle_period']   = sanitize_text_field( $_POST['updates_cycle_period'][ $i ] );

			//these values only for on date updates
			if ( $_POST['updates_when'][ $i ] == "date" ) {
				$update['date_month'] = str_pad( intval( $_POST['updates_date_month'][ $i ] ), 2, "0", STR_PAD_LEFT );
				$update['date_day']   = str_pad( intval( $_POST['updates_date_day'][ $i ] ), 2, "0", STR_PAD_LEFT );
				$update['date_year']  = intval( $_POST['updates_date_year'][ $i ] );
			}

			//make sure the update is valid
			if ( empty( $update['cycle_number'] ) ) {
				continue;
			}

			//if when is now, update the subscription
			if ( $update['when'] == "now" ) {
				self::updateSubscription( $update, $user_id );

				continue;
			} elseif ( $update['when'] == 'date' ) {
				if ( ! empty( $next_on_date_update ) ) {
					$next_on_date_update = min( $next_on_date_update, $update['date_year'] . "-" . $update['date_month'] . "-" . $update['date_day'] );
				} else {
					$next_on_date_update = $update['date_year'] . "-" . $update['date_month'] . "-" . $update['date_day'];
				}
			}

			//add to array
			$updates[] = $update;
		}

		//save in user meta
		update_user_meta( $user_id, "pmpro_stripe_updates", $updates );

		//save date of next on-date update to make it easier to query for these in cron job
		update_user_meta( $user_id, "pmpro_stripe_next_on_date_update", $next_on_date_update );
	}

	/**
	 * Cron activation for subscription updates.
	 *
	 * The subscription updates menu is no longer accessible as of v2.6.
	 * This function is staying to process subscription updates that were already queued.
	 *
	 * @since 1.8
	 */
	public static function pmpro_activation() {
		pmpro_maybe_schedule_event( time(), 'daily', 'pmpro_cron_stripe_subscription_updates' );
	}

	/**
	 * Cron deactivation for subscription updates.
	 *
	 * The subscription updates menu is no longer accessible as of v2.6.
	 * This function is staying to process subscription updates that were already queued.
	 *
	 * @since 1.8
	 */
	public static function pmpro_deactivation() {
		wp_clear_scheduled_hook( 'pmpro_cron_stripe_subscription_updates' );
	}

	/**
	 * Register the cron we need for Stripe subscription updates.
	 *
	 * @since 2.8
	 *
	 * @param array $crons The list of registered crons for Paid Memberships Pro.
	 *
	 * @return array The list of registered crons for Paid Memberships Pro.
	 */
	public static function register_cron( $crons ) {
		$crons['pmpro_cron_stripe_subscription_updates'] = [
			'interval' => 'daily',
		];

		return $crons;
	}

	/**
	 * Cron job for subscription updates.
	 *
	 * The subscription updates menu is no longer accessible as of v2.6.
	 * This function is staying to process subscription updates that were already queued.
	 *
	 * @since 1.8
	 */
	public static function pmpro_cron_stripe_subscription_updates() {
		global $wpdb;

		//get all updates for today (or before today)
		$sqlQuery = "SELECT *
					 FROM $wpdb->usermeta
					 WHERE meta_key = 'pmpro_stripe_next_on_date_update'
						AND meta_value IS NOT NULL
						AND meta_value <> ''
						AND meta_value < '" . date_i18n( "Y-m-d", strtotime( "+1 day", current_time( 'timestamp' ) ) ) . "'";
		$updates  = $wpdb->get_results( $sqlQuery );

		if ( ! empty( $updates ) ) {
			//loop through
			foreach ( $updates as $update ) {
				//pull values from update
				$user_id = $update->user_id;

				$user = get_userdata( $user_id );

				//if user is missing, delete the update info and continue
				if ( empty( $user ) || empty( $user->ID ) ) {
					delete_user_meta( $user_id, "pmpro_stripe_updates" );
					delete_user_meta( $user_id, "pmpro_stripe_next_on_date_update" );

					continue;
				}

				$user_updates        = $user->pmpro_stripe_updates;
				$next_on_date_update = "";

				//loop through updates looking for updates happening today or earlier
				if ( ! empty( $user_updates ) ) {
					foreach ( $user_updates as $key => $ud ) {
						if ( $ud['when'] == 'date' &&
						     $ud['date_year'] . "-" . $ud['date_month'] . "-" . $ud['date_day'] <= date_i18n( "Y-m-d", current_time( 'timestamp' ) )
						) {
							self::updateSubscription( $ud, $user_id );

							//remove update from list
							unset( $user_updates[ $key ] );
						} elseif ( $ud['when'] == 'date' ) {
							//this is an on date update for the future, update the next on date update
							if ( ! empty( $next_on_date_update ) ) {
								$next_on_date_update = min( $next_on_date_update, $ud['date_year'] . "-" . $ud['date_month'] . "-" . $ud['date_day'] );
							} else {
								$next_on_date_update = $ud['date_year'] . "-" . $ud['date_month'] . "-" . $ud['date_day'];
							}
						}
					}
				}

				//save updates in case we removed some
				update_user_meta( $user_id, "pmpro_stripe_updates", $user_updates );

				//save date of next on-date update to make it easier to query for these in cron job
				update_user_meta( $user_id, "pmpro_stripe_next_on_date_update", $next_on_date_update );
			}
		}
	}

	/**
	 * Before processing a checkout, check for pending invoices we want to clean up.
	 * This prevents double billing issues in cases where Stripe has pending invoices
	 * because of an expired credit card/etc and a user checks out to renew their subscription
	 * instead of updating their billing information via the billing info page.
	 */
	public static function pmpro_checkout_before_processing() {
		global $wpdb, $current_user;

		// we're only worried about cases where the user is logged in
		if ( ! is_user_logged_in() ) {
			return;
		}

		// make sure we're checking out with Stripe
		$current_gateway = pmpro_getGateway();
		if ( $current_gateway != 'stripe' ) {
			return;
		}

		//check the $pmpro_cancel_previous_subscriptions filter
		//this is used in add ons like Gift Memberships to stop PMPro from cancelling old memberships
		$pmpro_cancel_previous_subscriptions = true;
		$pmpro_cancel_previous_subscriptions = apply_filters( 'pmpro_cancel_previous_subscriptions', $pmpro_cancel_previous_subscriptions );
		if ( ! $pmpro_cancel_previous_subscriptions ) {
			return;
		}

		//get user and membership level
		$membership_level = pmpro_getMembershipLevelForUser( $current_user->ID );

		//no level, then probably no subscription at Stripe anymore
		if ( empty( $membership_level ) ) {
			return;
		}

		/**
		 * Filter which levels to cancel at the gateway.
		 * MMPU will set this to all levels that are going to be cancelled during this checkout.
		 * Others may want to display this by add_filter('pmpro_stripe_levels_to_cancel_before_checkout', __return_false);
		 */
		$levels_to_cancel = apply_filters( 'pmpro_stripe_levels_to_cancel_before_checkout', array( $membership_level->id ), $current_user );

		foreach ( $levels_to_cancel as $level_to_cancel ) {
			//get the last order for this user/level
			$last_order = new MemberOrder();
			$last_order->getLastMemberOrder( $current_user->ID, 'success', $level_to_cancel, 'stripe' );

			//so let's cancel the user's susbcription
			if ( ! empty( $last_order ) && ! empty( $last_order->subscription_transaction_id ) ) {
				$subscription = $last_order->Gateway->get_subscription( $last_order->subscription_transaction_id );
				if ( ! empty( $subscription ) ) {
					$last_order->Gateway->cancelSubscriptionAtGateway( $subscription, true );

					//Stripe was probably going to cancel this subscription 7 days past the payment failure (maybe just one hour, use a filter for sure)
					$memberships_users_row = $wpdb->get_row( "SELECT * FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . $current_user->ID . "' AND membership_id = '" . $level_to_cancel . "' AND status = 'active' LIMIT 1" );

					if ( ! empty( $memberships_users_row ) && ( empty( $memberships_users_row->enddate ) || $memberships_users_row->enddate == '0000-00-00 00:00:00' ) ) {
						/**
						 * Filter graced period days when canceling existing subscriptions at checkout.
						 *
						 * @param int $days Grace period defaults to 3 days
						 * @param object $membership Membership row from pmpro_memberships_users including membership_id, user_id, and enddate
						 *
						 * @since 1.9.4
						 *
						 */
						$days_grace  = apply_filters( 'pmpro_stripe_days_grace_when_canceling_existing_subscriptions_at_checkout', 3, $memberships_users_row );
						$new_enddate = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) + 3600 * 24 * $days_grace );
						$wpdb->update( $wpdb->pmpro_memberships_users, array( 'enddate' => $new_enddate ), array(
							'user_id'       => $current_user->ID,
							'membership_id' => $level_to_cancel,
							'status'        => 'active'
						), array( '%s' ), array( '%d', '%d', '%s' ) );
					}
				}
			}
		}
	}

	/**
	 * Filter pmpro_next_payment to get date via API if possible
	 *
	 * @since 1.8.6
	 */
	public static function pmpro_next_payment( $timestamp, $user_id, $order_status ) {
		//find the last order for this user
		if ( ! empty( $user_id ) ) {
			//get last order
			$order = new MemberOrder();
			$order->getLastMemberOrder( $user_id, $order_status );

			//check if this is a Stripe order with a subscription transaction id
			if ( ! empty( $order->id ) && ! empty( $order->subscription_transaction_id ) && $order->gateway == "stripe" ) {
				//get the subscription and return the current_period end or false
				$subscription = $order->Gateway->get_subscription( $order->subscription_transaction_id );

				if ( ! empty( $subscription ) ) {
					$customer = $order->Gateway->get_customer_for_user( $user_id );
					if ( ! $customer->delinquent && ! empty ( $subscription->current_period_end ) ) {
						$offset = get_option( 'gmt_offset' );
						$timestamp = $subscription->current_period_end + ( $offset * 3600 );
					} elseif ( $customer->delinquent && ! empty( $subscription->current_period_start ) ) {
						$offset = get_option( 'gmt_offset' );
						$timestamp = $subscription->current_period_start + ( $offset * 3600 );
					} else {
						$timestamp = null;  // shouldn't really get here
					}
				}
			}
		}

		return $timestamp;
	}

	public static function pmpro_set_up_apple_pay( $payment_option_values, $gateway  ) {
		// Check that we just saved Stripe settings.
		if ( $gateway != 'stripe' || empty( $_REQUEST['savesettings'] ) ) {
			return;
		}

		// Check that payment request button is enabled.
		if ( empty( $payment_option_values['stripe_payment_request_button'] ) ) {
			// We don't want to unregister domain or remove file in case
			// other plugins are using it.
			return;
		}

		// Make sure that Apple Pay is set up.
		// TODO: Apple Pay API functions don't seem to work with
		//       test API keys. Need to figure this out.
		$stripe = new PMProGateway_stripe();
		if ( ! $stripe->pmpro_does_apple_pay_domain_exist() ) {
			// 1. Make sure domain association file available.
			flush_rewrite_rules();
			// 2. Register Domain with Apple.
			$stripe->pmpro_create_apple_pay_domain();
		}
   }

   /**
	 * This function is used to save the parameters returned after successfull connection of Stripe account.
	 *
	 * @return void
	 */
	public static function stripe_connect_save_options() {
		// Is user have permission to edit give setting.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Be sure only to connect when param present.
		if ( ! isset( $_REQUEST['pmpro_stripe_connected'] ) || ! isset( $_REQUEST['pmpro_stripe_connected_environment'] ) ) {
			return false;
		}

		// Change current gateway to Stripe
		pmpro_setOption( 'gateway', 'stripe' );
		pmpro_setOption( 'gateway_environment', $_REQUEST['pmpro_stripe_connected_environment'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$error = '';
		if (
			'false' === $_REQUEST['pmpro_stripe_connected']
			&& isset( $_REQUEST['error_message'] )
		) {
			$error = sanitize_text_field( $_REQUEST['error_message'] );
		} elseif (
			'false' === $_REQUEST['pmpro_stripe_connected']
			|| ! isset( $_REQUEST['pmpro_stripe_publishable_key'] )
			|| ! isset( $_REQUEST['pmpro_stripe_user_id'] )
			|| ! isset( $_REQUEST['pmpro_stripe_access_token'] )
		) {
			$error = __( 'Invalid response from the Stripe Connect server.', 'paid-memberships-pro' );
		} else {
			// Update keys.
			if ( $_REQUEST['pmpro_stripe_connected_environment'] === 'live' ) {
				// Update live keys.
				pmpro_setOption( 'live_stripe_connect_user_id', $_REQUEST['pmpro_stripe_user_id'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				pmpro_setOption( 'live_stripe_connect_secretkey', $_REQUEST['pmpro_stripe_access_token'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				pmpro_setOption( 'live_stripe_connect_publishablekey', $_REQUEST['pmpro_stripe_publishable_key'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			} else {
				// Update sandbox keys.
				pmpro_setOption( 'sandbox_stripe_connect_user_id', $_REQUEST['pmpro_stripe_user_id'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				pmpro_setOption( 'sandbox_stripe_connect_secretkey', $_REQUEST['pmpro_stripe_access_token'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				pmpro_setOption( 'sandbox_stripe_connect_publishablekey', $_REQUEST['pmpro_stripe_publishable_key'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			}


			// Delete option for user API key.
			delete_option( 'pmpro_stripe_secretkey' );
			delete_option( 'pmpro_stripe_publishablekey' );

			unset( $_GET['pmpro_stripe_connected'] );
			unset( $_GET['pmpro_stripe_connected_environment'] );
			unset( $_GET['pmpro_stripe_user_id'] );
			unset( $_GET['pmpro_stripe_access_token'] );
			unset( $_GET['pmpro_stripe_publishable_key'] );

			// Set a transient to show a banner to set up webhooks on the next page load.
			set_transient( 'pmpro_stripe_connect_show_webhook_set_up_banner', true, 60 );

			wp_redirect( admin_url( sprintf( 'admin.php?%s', http_build_query( $_GET ) ) ) );
			exit;
		}

		if ( ! empty( $error ) ) {
			global $pmpro_stripe_error;
			$pmpro_stripe_error = sprintf(
				/* translators: %s Error Message */
				__( '<strong>Error:</strong> PMPro could not connect to the Stripe API. Reason: %s', 'paid-memberships-pro' ),
				esc_html( $error )
			);
		}
	}

	public static function stripe_connect_show_errors() {
		global $pmpro_stripe_error;
		if ( ! empty( $pmpro_stripe_error ) ) {
			$class   = 'notice notice-error pmpro-stripe-connect-message';
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $pmpro_stripe_error );
		}
	}

	/**
	 * Disconnects user from the Stripe Connected App.
	 */
	public static function stripe_connect_deauthorize() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Be sure only to deauthorize when param present.
		if ( ! isset( $_REQUEST['pmpro_stripe_disconnected'] ) || ! isset( $_REQUEST['pmpro_stripe_disconnected_environment'] ) ) {
			return false;
		}

		// Show message if NOT disconnected.
		if (
			'false' === $_REQUEST['pmpro_stripe_disconnected']
			&& isset( $_REQUEST['error_code'] )
		) {

			$class   = 'notice notice-warning pmpro-stripe-disconnect-message';
			$message = sprintf(
				/* translators: %s Error Message */
				__( '<strong>Error:</strong> PMPro could not disconnect from the Stripe API. Reason: %s', 'paid-memberships-pro' ),
				sanitize_text_field( $_REQUEST['error_message'] )
			);

			$allowed_html = array(
				'div' => array(
					'class' => array(),
				),
				'p'   => array(),
				'strong' => array(),
			);
			echo wp_kses( sprintf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ), $allowed_html );

		}

		if ( $_REQUEST['pmpro_stripe_disconnected_environment'] === 'live' ) {
			// Delete live keys.
			delete_option( 'pmpro_live_stripe_connect_user_id' );
			delete_option( 'pmpro_live_stripe_connect_secretkey' );
			delete_option( 'pmpro_live_stripe_connect_publishablekey' );
		} else {
			// Delete sandbox keys.
			delete_option( 'pmpro_sandbox_stripe_connect_user_id' );
			delete_option( 'pmpro_sandbox_stripe_connect_secretkey' );
			delete_option( 'pmpro_sandbox_stripe_connect_publishablekey' );
		}
	}

	/**
	 * If the checkout flow has changed to Stripe Checkout, remember to show a banner to set up webhooks.
	 *
	 * @since 2.12
	 *
	 * @param string $old_value The old value of the option.
	 */
	public static function update_option_pmpro_stripe_payment_flow( $old_value ) {
		global $pmpro_stripe_old_payment_flow;
		$pmpro_stripe_old_payment_flow = empty( $old_value ) ? 'onsite' : $old_value;
	}

	/**
	 * Show a modal to the user after connecting to Stripe or switching to Stripe Checkout.
	 *
	 * @since 2.12
	 */
	public static function show_set_up_webhooks_popup() {
		global $pmpro_stripe_old_payment_flow;

		// Figure out if we need to show a popup.
		$message = null;

		// Check if we just connected to Stripe.
		if ( get_transient( 'pmpro_stripe_connect_show_webhook_set_up_banner' ) ) {
			$message = true;
			delete_transient( 'pmpro_stripe_connect_show_webhook_set_up_banner' );
		}

		// Check if we just switched to Stripe Checkout.
		if ( isset( $pmpro_stripe_old_payment_flow ) && 'onsite' === $pmpro_stripe_old_payment_flow && isset( $_REQUEST['stripe_payment_flow'] ) && 'checkout' === $_REQUEST['stripe_payment_flow'] ) {
			$message = true;
		}

		// Bail if we don't need to show a popup.
		if ( ! $message ) {
			return;
		}

		// Show the popup.
		?>
		<div id="pmpro-popup" role="dialog" class="pmpro-popup-overlay pmpro-stripe-success-connected-modal" tabindex="-1" aria-modal="true" aria-labelledby="pmpro-popup-stripe-confirmation-label" aria-describedby="pmpro-popup-stripe-confirmation-description">
			<span class="pmpro-popup-helper"></span>
			<div class="pmpro-popup-wrap pmpro-popup-stripe-confirmation">
				<div id="pmpro-popup-inner">
				<button class="pmproPopupCloseButton" title="<?php esc_attr_e( 'Close Popup', 'paid-memberships-pro' ); ?>"><span class="dashicons dashicons-no"></span></button>
					<h2 id="pmpro-popup-stripe-confirmation-label">
						<?php esc_html_e( 'Next Step: Register a Stripe Webhook.', 'paid-memberships-pro' ); ?>
					</h2>
					<p id="pmpro-popup-stripe-confirmation-description">
						<?php esc_html_e( 'In order for Stripe to function properly, there must be a Stripe Webhook configured for this website.', 'paid-memberships-pro' ); ?>
						<strong><?php esc_html_e( 'Here\'s how to create a webhook endpoint in your Stripe dashboard', 'paid-memberships-pro' ); ?>:</strong>
					</p>
					<ol>
						<li>
							<?php
							printf(
								esc_html__( 'Open the %s page in your Stripe dashboard', 'paid-memberships-pro' ),
								'<a href="https://dashboard.stripe.com/account/webhooks" target="_blank">' . esc_html__( 'Webhooks', 'paid-memberships-pro' ) . '</a>'
							)
							?>
						</li>
						<li><?php esc_html_e( 'Click "Add an endpoint"', 'paid-memberships-pro' ); ?></li>
						<li>
							<?php esc_html_e( 'Paste the following value into the "Endpoint URL" field:', 'paid-memberships-pro' ); ?>
							<code><?php echo esc_url( admin_url( 'admin-ajax.php' ) . '?action=stripe_webhook'  ); ?> </code>
						</li>
						<li>
							<?php esc_html_e( 'Select the following events to listen to:', 'paid-memberships-pro' ); ?>
							<ul>
								<?php
								$events = self::webhook_events();
								foreach ( $events as $event ) {
									echo '<li>' . esc_html( $event ) . '</li>';
								}
								?>
							</ul>
						</li>
						<li><?php esc_html_e( 'Click "Add endpoint" to save your webhook', 'paid-memberships-pro' ); ?></li>
					</ol>
					<p>
						<?php echo esc_html_e( 'You must complete these steps for both the Sandbox/Testing and Live/Production modes if you intend to use Stripe for testing.', 'paid-memberships-pro' ); ?>
					</p>
				</div>
			</div>
		</div>
		<script>
			jQuery(document).ready(function ($) {
				// If we added the successfully connected modal then show it.
				$('.pmpro-stripe-success-connected-modal').show();
			});
		</script>
		<?php
	}

	/**
	 * Determine whether the site is using legacy Stripe keys.
	 *
	 * @return bool Whether the site is using legacy Stripe keys.
	 */
	public static function using_legacy_keys() {
		$r = ! empty( pmpro_getOption( 'stripe_secretkey' ) ) && ! empty( pmpro_getOption( 'stripe_publishablekey' ) );
		return $r;
	}

	/**
	 * Determine whether the site has Stripe Connect credentials set based on gateway environment.
	 *
	 * @param null|string $gateway_environment The gateway environment to use, default uses the current saved setting.
	 *
	 * @return bool Whether the site has Stripe Connect credentials set.
	 */
	public static function has_connect_credentials( $gateway_environment = null ) {
		if ( empty( $gateway_environment ) ) {
			$gateway_environment = pmpro_getOption( 'pmpro_gateway_environment' );
		}

		if ( $gateway_environment === 'live' ) {
			// Return whether Stripe is connected for live gateway environment.
			return (
				pmpro_getOption( 'live_stripe_connect_user_id' ) &&
				pmpro_getOption( 'live_stripe_connect_secretkey' ) &&
				pmpro_getOption( 'live_stripe_connect_publishablekey' )
			);
		} else {
			// Return whether Stripe is connected for sandbox gateway environment.
			return (
				pmpro_getOption( 'sandbox_stripe_connect_user_id' ) &&
				pmpro_getOption( 'sandbox_stripe_connect_secretkey' ) &&
				pmpro_getOption( 'sandbox_stripe_connect_publishablekey' )
			);
		}
	}

	/**
	 * Warn if required extensions aren't loaded.
	 *
	 * @return bool
	 * @since 1.8.6.8.1
	 * @since 1.8.13.6 - Add json dependency
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 */
	public static function dependencies() {
		global $msg, $msgt, $pmpro_stripe_error;

		if ( version_compare( PHP_VERSION, '5.3.29', '<' ) ) {

			$pmpro_stripe_error = true;
			$msg                = - 1;
			$msgt               = sprintf( __( "The Stripe Gateway requires PHP 5.3.29 or greater. We recommend upgrading to PHP %s or greater. Ask your host to upgrade.", "paid-memberships-pro" ), PMPRO_PHP_MIN_VERSION );

			if ( ! is_admin() ) {
				pmpro_setMessage( $msgt, "pmpro_error" );
			}

			return false;
		}

		$modules = array( 'curl', 'mbstring', 'json' );

		foreach ( $modules as $module ) {
			if ( ! extension_loaded( $module ) ) {
				$pmpro_stripe_error = true;
				$msg                = - 1;
				$msgt               = sprintf( __( "The %s gateway depends on the %s PHP extension. Please enable it, or ask your hosting provider to enable it.", 'paid-memberships-pro' ), 'Stripe', $module );

				//throw error on checkout page
				if ( ! is_admin() ) {
					pmpro_setMessage( $msgt, 'pmpro_error' );
				}

				return false;
			}
		}

		self::$is_loaded = true;

		return true;
	}

	/**
	 * Check if the user has opted into the Stripe Checkout beta.
	 *
	 * @return bool
	 *
	 * @deprecated 2.10
	 */
	public static function stripe_checkout_beta_enabled() {
		_deprecated_function( __FUNCTION__, '2.10' );
		return true;
	}

	/**
	 * Check if Stripe Checkout is being used.
	 *
	 * @return bool
	 */
	public static function using_stripe_checkout() {
		return 'checkout' === pmpro_getOption( 'stripe_payment_flow' );
	}

	/**
	 * Show warning at checkout if Stripe Checkout is being used and
	 * the last order is pending.
	 *
	 * @since 2.8
	 *
	 * @param bool $show Whether to show the default payment information fields.
	 * @return bool
	 */
	static function show_stripe_checkout_pending_warning($show)
	{
		global $gateway;

		//show our submit buttons
		?>
		<span id="pmpro_payment_information_fields" <?php if( $gateway != "stripe" ) { ?>style="display: none;"<?php } ?>>
			<?php
			// If the current user's last order is a pending Stripe order, warn them that they already have a pending order.
			$last_order = new MemberOrder();
			$last_order->getLastMemberOrder( get_current_user_id(), null, null, 'stripe' );
			if ( ! empty( $last_order->id ) && $last_order->status === 'pending' ) {
				?>
				<p class="pmpro_error"><?php _e( 'Your previous order has not yet been processed. Submitting your payment again will cause a separate charge to be initiated.', 'paid-memberships-pro' ); ?></p>
				<?php
			}
			
			?>
		</span>
		<?php

		//don't show the default submit button.
		return false;
	}

	/**
	 * Instead of changeing membership levels, send users to Stripe to pay.
	 *
	 * @since 2.8
	 *
	 * @param int         $user_id ID of user who is checking out.
	 * @param MemberOrder $morder  MemberOrder object for this checkout.
	 */
	static function pmpro_checkout_before_change_membership_level($user_id, $morder)
	{
		global $pmpro_level, $discount_code, $wpdb, $pmpro_currency;

		//if no order, no need to pay
		if ( empty( $morder ) || $morder->gateway != 'stripe' ) {
			return;
		}

		$morder->user_id = $user_id;
		$morder->status  = 'token';
		$morder->saveOrder();

		pmpro_save_checkout_data_to_order( $morder );

		// Time to send the user to pay with Stripe!
		$stripe = new PMProGateway_stripe();

		// Let's first get the customer to charge.
		$customer = $stripe->update_customer_at_checkout( $morder );
		if ( empty( $customer ) ) {
			// There was an issue creating/updating the Stripe customer.
			// $order will have an error message.
			pmpro_setMessage( __( 'Could not get customer. ', 'paid-memberships-pro' ) . $morder->error, 'pmpro_error', true );
			return;
		}

		// Next, let's get the product being purchased.
		$product_id = $stripe->get_product_id_for_level( $morder->membership_id );
		if ( empty( $product_id ) ) {
			// Something went wrong getting the product ID or creating the product.
			// Show the user a general error message.
			pmpro_setMessage( __( 'Could not get product ID.', 'paid-memberships-pro' ), 'pmpro_error', true );
			return;
		}

		// Then, we need to build the line items array to charge.
		$line_items = array();

		// Used to calculate Stripe Connect fees.
		$application_fee_percentage = self::get_application_fee_percentage();

		// First, let's handle the initial payment.
		if ( ! empty( $morder->InitialPayment ) ) {
			$initial_subtotal       = $morder->InitialPayment;
			$initial_tax            = $morder->getTaxForPrice( $initial_subtotal );
			$initial_payment_amount = pmpro_round_price( (float) $initial_subtotal + (float) $initial_tax );
			$initial_payment_price  = $stripe->get_price_for_product( $product_id, $initial_payment_amount );
			if ( is_string( $initial_payment_price ) ) {
				// There was an error getting the price.
				pmpro_setMessage( __( 'Could not get price for initial payment. ', 'paid-memberships-pro' ) . $initial_payment_price, 'pmpro_error', true );
				return;
			}
			$line_items[] = array(
				'price'    => $initial_payment_price->id,
				'quantity' => 1,
			);
			if ( ! empty( $application_fee_percentage ) ) {
				$application_fee = floor( $initial_payment_price->unit_amount * $application_fee_percentage / 100 );
				if ( ! empty( $application_fee ) ) {
					$payment_intent_data = array(
						'application_fee_amount' => $application_fee,
					);
				}
			}
		}

		// Now, let's handle the recurring payments.
		if ( pmpro_isLevelRecurring( $morder->membership_level ) ) {
			$recurring_subtotal       = $morder->PaymentAmount;
			$recurring_tax            = $morder->getTaxForPrice( $recurring_subtotal );
			$recurring_payment_amount = pmpro_round_price( (float) $recurring_subtotal + (float) $recurring_tax );
			$recurring_payment_price  = $stripe->get_price_for_product( $product_id, $recurring_payment_amount, $morder->BillingPeriod, $morder->BillingFrequency );
			if ( is_string( $recurring_payment_price ) ) {
				// There was an error getting the price.
				pmpro_setMessage( __( 'Could not get price for recurring payment. ', 'paid-memberships-pro' ) . $recurring_payment_price, 'pmpro_error', true );
				return;
			}
			$line_items[] = array(
				'price'    => $recurring_payment_price->id,
				'quantity' => 1,
			);
			$subscription_data = array();

			// Check if we can combine initial and recurring payments.
			$filtered_trial_period_days = $stripe->calculate_trial_period_days( $morder );
			$unfiltered_trial_period_days = $stripe->calculate_trial_period_days( $morder, false );

			if (
				empty( $morder->TrialBillingCycles ) && // Check if there is a trial period.
				$filtered_trial_period_days === $unfiltered_trial_period_days && // Check if the trial period is the same as the filtered trial period.
				( ! empty( $initial_payment_amount ) && $initial_payment_amount === $recurring_payment_amount ) // Check if the initial payment and recurring payment prices are the same.
				) {
				// We can combine the initial payment and the recurring payment.
				array_shift( $line_items );
				$payment_intent_data = null;
			} else {
				// We need to set the trial period days and send initial and recurring payments as separate line items.
				$subscription_data['trial_period_days'] = $filtered_trial_period_days;
			}

			// Add application fee for Stripe Connect.
			$application_fee_percentage = self::get_application_fee_percentage();
			if ( ! empty( $application_fee_percentage ) ) {
				$subscription_data['application_fee_percent'] = $application_fee_percentage;
			}
		}

		// Set up tax and billing addres collection.
		$automatic_tax = ( ! empty( pmpro_getOption( 'stripe_tax' ) ) && 'no' !== pmpro_getOption( 'stripe_tax' ) ) ? array(
			'enabled' => true,
		) : array(
			'enabled' => false,
		);
		$tax_id_collection = ! empty( pmpro_getOption( 'stripe_tax_id_collection_enabled' ) ) ? array(
			'enabled' => true,
		) : array(
			'enabled' => false,
		);
		$billing_address_collection = pmpro_getOption( 'stripe_checkout_billing_address' ) ?: 'auto';

		// And let's send 'em to Stripe!
		$checkout_session_params = array(
			'customer' => $customer->id,
			'line_items' => $line_items,
			// $subscription_data is only set if level is recurring. Could be empty though.
			'mode' => isset( $subscription_data ) ? 'subscription' : 'payment',
			'automatic_tax' => $automatic_tax,
			'tax_id_collection' => $tax_id_collection,
			'billing_address_collection' => $billing_address_collection,
			'customer_update' => array(
				'address' => 'auto',
				'name' => 'auto'
			),
			'success_url' => apply_filters( 'pmpro_confirmation_url', add_query_arg( 'level', $morder->membership_level->id, pmpro_url("confirmation" ) ), $user_id, $pmpro_level ),
			'cancel_url' =>  add_query_arg( 'level', $morder->membership_level->id, pmpro_url("checkout" ) ),
		);
		if ( ! empty( $subscription_data ) ) {
			$checkout_session_params['subscription_data'] = $subscription_data;
		} elseif ( ! empty( $payment_intent_data ) ) {
			$checkout_session_params['payment_intent_data'] = $payment_intent_data;
		}

		// For one-time payments, make sure that we create an invoice.
		if ( $checkout_session_params['mode'] === 'payment' ) {
			$checkout_session_params['invoice_creation']['enabled'] = true;
		}

		$checkout_session_params = apply_filters( 'pmpro_stripe_checkout_session_parameters', $checkout_session_params, $morder, $customer );
		
		try {
			$checkout_session = Stripe_Checkout_Session::create( $checkout_session_params );
		} catch ( Throwable $th ) {
			// Error creating checkout session.
			pmpro_setMessage( __( 'Could not create checkout session. ', 'paid-memberships-pro' ) . $th->getMessage(), 'pmpro_error', true );
			return;
		} catch ( Exception $e ) {
			// Error creating checkout session.
			pmpro_setMessage( __( 'Could not create checkout session. ', 'paid-memberships-pro' ) . $e->getMessage(), 'pmpro_error', true );
			return;
		}

		// Save so that we can confirm the payment later.
		update_pmpro_membership_order_meta( $morder->id, 'stripe_checkout_session_id', $checkout_session->id );
		wp_redirect( $checkout_session->url );
		exit;
	}

	/**
	 * User has not been sent to the Customer Portal, so we need to disable
	 * Stripe Checkout for this page load and show the default update billing
	 * fields.
	 *
	 * @since 2.8
	 */
	public static function pmpro_billing_preheader_stripe_checkout() {
		// Disable Stripe Checkout functionality for the rest of this page load.
		add_filter( 'pmpro_include_cardtype_field', array(
			'PMProGateway_stripe',
			'pmpro_include_billing_address_fields'
		), 15 );
		add_action( 'pmpro_billing_preheader', array( 'PMProGateway_stripe', 'pmpro_checkout_after_preheader' ), 15 );
		add_filter( 'pmpro_billing_order', array( 'PMProGateway_stripe', 'pmpro_checkout_order' ), 15 );
		add_filter( 'pmpro_include_payment_information_fields', array(
			'PMProGateway_stripe',
			'pmpro_include_payment_information_fields'
		), 15 );
		add_filter( 'option_pmpro_stripe_payment_flow', '__return_false' ); // Disable Stripe Checkout for rest of page load.
	}

	/**
	 * Send the user to the Stripe Customer Portal if the customer portal is enabled.
	 *
	 * @since 2.10.
	 */
	public static function pmpro_billing_preheader_stripe_customer_portal() {
		if ( 'portal' === pmpro_getOption( 'stripe_update_billing_flow' ) ) {
			// Get current user.
			$user = wp_get_current_user();
			if ( empty( $user->ID ) ) {
				$error = __( 'User is not logged in.', 'paid-memberships-pro' );
			}

			if ( empty( $error ) ) {
				// Get the Stripe Customer.
				$stripe = new PMProGateway_stripe();
				$customer = $stripe->get_customer_for_user( $user->ID );
				if ( empty( $customer->id ) ) {
					$error = __( 'Could not get Stripe customer for user.', 'paid-memberships-pro' );
				}
			}

			if ( empty( $error ) ) {
				// Send the user to the customer portal.
				$customer_portal_url = $stripe->get_customer_portal_url( $customer->id );
				if ( ! empty( $customer_portal_url ) ) {
					wp_redirect( $customer_portal_url );
					exit;
				}
				$error = __( 'Could not get Customer Portal URL. This feature may not be set up in Stripe.', 'paid-memberships-pro' );
			}

			// There must have been an error while getting the customer portal URL. Show an error and let user update
			// their billing info onsite.
			pmpro_setMessage( $error . ' ' . __( 'Please contact the site administrator.', 'paid-memberships-pro' ), 'pmpro_alert', true );
		}
	}

	/****************************************
	 ************ PUBLIC METHODS ************
	 ****************************************/
	/**
	 * Process checkout and decide if a charge and or subscribe is needed
	 * Updated in v2.1 to work with Stripe v3 payment intents.
	 * @since 1.4
	 */
	public function process( &$order ) {
		if ( self::using_stripe_checkout() ) {
			// If using Stripe Checkout, we will try to collect the payment later.
			return true;
		}

		$payment_transaction_id = '';
		$subscription_transaction_id = '';
		
		// User has either just submitted the checkout form or tried to confirm their
		// payment intent.
		$customer = null; // This will be used to create the subscription later.
		if ( ! empty( $order->payment_intent_id ) ) {
			// User has just tried to confirm their payment intent. We need to make sure that it was
			// confirmed successfully, and then try to create their subscription if needed.
			$payment_intent = $this->process_payment_intent( $order->payment_intent_id );
			if ( is_string( $payment_intent ) ) {
				$order->error      = __( 'Error processing payment intent.', 'paid-memberships-pro' ) . ' ' . $payment_intent;
				$order->shorterror = $order->error;
				return false;
			}
			// Payment should now be processed.
			$payment_transaction_id = $payment_intent->latest_charge;

			// Note the customer so that we can create a subscription if needed..
			$customer = $payment_intent->customer;
		} else {
			// We have not yet tried to process this checkout.
			// Make sure we have a customer with a payment method.
			$customer = $this->update_customer_at_checkout( $order );
			if ( empty( $customer ) ) {
				// There was an issue creating/updating the Stripe customer.
				// $order will have an error message, so we don't need to add one.
				return false;
			}

			$payment_method = $this->get_payment_method( $order );
			if ( empty( $payment_method ) ) {
				// There was an issue getting the payment method.
				$order->error      = __( 'Error retrieving payment method.', 'paid-memberships-pro' ) . empty( $order->error ) ? '' : ' ' . $order->error;
				$order->shorterror = $order->error;
				return false;
			}

			// Save customer in $order for create_payment_intent().
			// This will likely be removed as we rework payment processing.
			$order->stripe_customer = $customer;

			// Process the charges.
			$charges_processed = $this->process_charges( $order );
			if ( ! empty( $order->error ) ) {
				// There was an error processing charges.
				// $order has an error message, so we don't need to add one.
				return false;
			}

			// If we needed to charge an initial payment, it was successful.
			if ( ! empty( $order->stripe_payment_intent->latest_charge ) ) {
				$payment_transaction_id = $order->stripe_payment_intent->latest_charge;
			} else {
				// If we haven't charged a payment, the payment method will not be attached to the customer.
				// Attach it now.
				try {
					$payment_method->attach(
						array(
							'customer' => $customer->id,
						)
					);
				} catch ( \Stripe\Error $e ) {
					$order->error = $e->getMessage();
					return false;
				} catch ( \Throwable $e ) {
					$order->error = $e->getMessage();
					return false;
				} catch ( \Exception $e ) {
					$order->error = $e->getMessage();
					return false;
				}
			}
		}

		// Create a subscription if we need to.
		if ( pmpro_isLevelRecurring( $order->membership_level ) ) {
			$subscription = $this->create_subscription_for_customer_from_order( $customer->id, $order );
			if ( empty( $subscription ) ) {
				// There was an issue creating the subscription. Order will have error message.
				$order->error      = __( 'Error creating subscription for customer.', 'paid-memberships-pro' ) . ' ' . $order->error;
				$order->shorterror = $order->error;
				return false;
			}
			$order->stripe_subscription = $subscription;				

			// Successfully created a subscription.
			$subscription_transaction_id = $subscription->id;
		}
		
		// All charges have been processed and all subscriptions have been created.
		$order->payment_transaction_id = $payment_transaction_id;
		$order->subscription_transaction_id = $subscription_transaction_id;
		$order->status = 'success';
		$order->saveOrder();
		return true;
	}

	/**
	 * Retrieve a Stripe_Customer for a given user.
	 *
	 * @since 2.7.0
	 *
	 * @param int $user_id to get Stripe_Customer for.
	 * @return Stripe_Customer|null
	 */
	public function get_customer_for_user( $user_id ) {
		// Pull Stripe customer ID from user meta.
		$customer_id = get_user_meta( $user_id, 'pmpro_stripe_customerid', true );

		if ( empty( $customer_id ) ) {
			// Try to figure out the cuseromer ID from their last order.
			$order = new MemberOrder();
			$order->getLastMemberOrder(
				$user_id,
				array(
					'success',
					'cancelled'
				),
				null,
				'stripe',
				$this->gateway_environment
			);

			// If we don't have a customer ID yet, get the Customer ID from their subscription.
			if ( empty( $customer_id ) && ! empty( $order->subscription_transaction_id ) && strpos( $order->subscription_transaction_id, "sub_" ) !== false ) {
				try {
					$subscription = Stripe_Subscription::retrieve( $order->subscription_transaction_id );
				} catch ( \Throwable $e ) {
					// Assume no customer found.
				} catch ( \Exception $e ) {
					// Assume no customer found.
				}
				if ( ! empty( $subscription ) && ! empty( $subscription->customer ) ) {
					$customer_id = $subscription->customer;
				}
			}

			// If we don't have a customer ID yet, get the Customer ID from their charge.
			if ( empty( $customer_id ) && ! empty( $order->payment_transaction_id ) && strpos( $order->payment_transaction_id, "ch_" ) !== false ) {
				try {
					$charge = Stripe_Charge::retrieve( $order->payment_transaction_id );
				} catch ( \Throwable $e ) {
					// Assume no customer found.
				} catch ( \Exception $e ) {
					// Assume no customer found.
				}
				if ( ! empty( $charge ) && ! empty( $charge->customer ) ) {
					$customer_id = $charge->customer;
				}
			}

			// If we don't have a customer ID yet, get the Customer ID from their invoice.
			if ( empty( $customer_id ) && ! empty( $order->payment_transaction_id ) && strpos( $order->payment_transaction_id, "in_" ) !== false ) {
				try {
					$invoice = Stripe_Invoice::retrieve( $order->payment_transaction_id );
				} catch ( \Throwable $e ) {
					// Assume no customer found.
				} catch ( \Exception $e ) {
					// Assume no customer found.
				}
				if ( ! empty( $invoice ) && ! empty( $invoice->customer ) ) {
					$customer_id = $invoice->customer;
				}
			}

			if ( ! empty( $customer_id ) ) {
				update_user_meta( $user_id, "pmpro_stripe_customerid", $customer_id );
			}
		}

		return empty( $customer_id ) ? null : $this->get_customer( $customer_id );
	}

	/**
	 * Create/Update Stripe customer for a user.
	 *
	 * @since 2.7.0
	 *
	 * @param int $user_id to create/update Stripe customer for.
	 * @return Stripe_Customer|false
	 */
	public function update_customer_from_user( $user_id ) {
		$user = get_userdata( $user_id );

		if ( empty( $user->ID ) ) {
			// User does not exist.
			return false;
		}

		// Get the existing customer from Stripe.
		$customer = $this->get_customer_for_user( $user_id );

		// Get the name for the customer.
		$name = trim( $user->first_name . " " . $user->last_name );
		if ( empty( $name ) ) {
			// In case first and last names aren't set.
			$name = $user->user_login;
		}

		// Get data to update customer with.
		$customer_args = array(
			'email'       => $user->user_email,
			'description' => $name . ' (' . $user->user_email . ')',
		);

		// Maybe update billing address for customer.
		if (
			! $this->customer_has_billing_address( $customer ) &&
			! empty( $user->pmpro_baddress1 ) &&
			! empty( $user->pmpro_bcity ) &&
			! empty( $user->pmpro_bstate ) &&
			! empty( $user->pmpro_bzipcode ) &&
			! empty( $user->pmpro_bcountry )
		) {
			// We have an address in user meta and there is
			// no address in Stripe. May as well send it.
			$customer_args['address'] = array(
				'city'        => $user->pmpro_bcity,
				'country'     => $user->pmpro_bcountry,
				'line1'       => $user->pmpro_baddress1,
				'line2'       => $user->pmpro_baddress2,
				'postal_code' => $user->pmpro_bzipcode,
				'state'       => $user->pmpro_bstate,
			);
		}

		/**
		 * Change the information that is sent when updating/creating
		 * a Stripe_Customer from a user.
		 *
		 * @since 2.7.0
		 *
		 * @param array       $customer_args to be sent.
		 * @param WP_User     $user being used to create/update customer.
		 */
		$customer_args = apply_filters( 'pmpro_stripe_update_customer_from_user', $customer_args, $user );

		// Update the customer.
		if ( empty( $customer ) ) {
			// We need to build a new customer.
			$customer = $this->create_customer( $customer_args );
		} else {
			// Update the existing customer.
			$customer = $this->update_customer( $customer->ID, $customer_args );
		}
		return is_string( $customer ) ? false : $customer;
	}

	/**
	 * Get subscription status from the Gateway.
	 *
	 * @since 2.3
	 */
	public function getSubscriptionStatus( &$order ) {
		$subscription = $this->get_subscription( $order->subscription_transaction_id );

		if ( ! empty( $subscription ) ) {
			return $subscription->status;
		} else {
			return false;
		}
	}

	/**
	 * Helper method to update the customer info via update_customer_at_checkout
	 *
	 * @since 1.4
	 */
	public function update( &$order ) {
		// Make sure the order has a subscription_transaction_id.
		if ( empty( $order->subscription_transaction_id ) ) {
			$order->error  = __( 'No subscription transaction ID.', 'paid-memberships-pro' );
			return false;
		}

		$customer = $this->update_customer_at_checkout( $order );
		if ( empty( $customer ) ) {
			// There was an issue creating/updating the Stripe customer.
			// $order will have an error message, so we don't need to add one.
			return false;
		}

		$payment_method = $this->get_payment_method( $order );
		if ( empty( $payment_method ) ) {
			// There was an issue getting the payment method.
			$order->error      = __( 'Error retrieving payment method.', 'paid-memberships-pro' ) . empty( $order->error ) ? '' : ' ' . $order->error;
			$order->shorterror = $order->error;
			return false;
		}

		// Attach the customer to the payment method.
		try {
			$payment_method->attach(
				array(
					'customer' => $customer->id,
				)
			);
		} catch ( \Stripe\Error $e ) {
			$order->error = $e->getMessage();
			return false;
		} catch ( \Throwable $e ) {
			$order->error = $e->getMessage();
			return false;
		} catch ( \Exception $e ) {
			$order->error = $e->getMessage();
			return false;
		}

		// Update the subscription.
		$subscription_args = array(
			'default_payment_method' => $order->payment_method_id,
		);
		try {
			Stripe_Subscription::update( $order->subscription_transaction_id, $subscription_args );
		} catch ( \Stripe\Error $e ) {
			$order->error = $e->getMessage();
			return false;
		} catch ( \Throwable $e ) {
			$order->error = $e->getMessage();
			return false;
		} catch ( \Exception $e ) {
			$order->error = $e->getMessage();
			return false;
		}

		return true;
	}

	/**
	 * Cancel a subscription at Stripe
	 *
	 * @since 1.4
	 */
	public function cancel( &$order, $update_status = true ) {
		global $pmpro_stripe_event;

		//no matter what happens below, we're going to cancel the order in our system
		if ( $update_status ) {
			$order->updateStatus( "cancelled" );
		}

		//require a subscription id
		if ( empty( $order->subscription_transaction_id ) ) {
			return false;
		}

		//find the customer
		$result = $this->update_customer_at_checkout( $order );

		if ( ! empty( $result ) ) {
			//find subscription with this order code
			$subscription = $this->get_subscription( $order->subscription_transaction_id );

			if ( ! empty( $subscription )
			     && ( empty( $pmpro_stripe_event ) || empty( $pmpro_stripe_event->type ) || $pmpro_stripe_event->type != 'customer.subscription.deleted' ) ) {
				if ( $this->cancelSubscriptionAtGateway( $subscription ) ) {
					//we're okay, going to return true later
				} else {
					$order->error      = __( "Could not cancel old subscription.", 'paid-memberships-pro' );
					$order->shorterror = $order->error;

					return false;
				}
			}

			/*
                Clear updates for this user. (But not if checking out, we would have already done that.)
            */
			if ( empty( $_REQUEST['submit-checkout'] ) ) {
				update_user_meta( $order->user_id, "pmpro_stripe_updates", array() );
			}

			return true;
		} else {
			$order->error      = __( "Could not find the customer.", 'paid-memberships-pro' );
			$order->shorterror = $order->error;

			return false;    //no customer found
		}
	}

	/**
	 * Get the URL for a customer's Stripe Customer Portal.
	 *
	 * @since 2.8
	 *
	 * @param string $customer_id Customer to get the URL for.
	 * @return string URL for customer portal, or empty String if not found.
	 */
	public function get_customer_portal_url( $customer_id ) {
		// Before we can send the user to the customer portal,
		// we need to have a portal configuration.
		$portal_configurations = array();
		try {
			// Get all active portal configurations.
			$portal_configurations = Stripe\BillingPortal\Configuration::all( array( 'active' => true, 'limit' => 100 ) );
		} catch( Exception $e ) {
			// Error getting portal configurations.
			return '';
		}

		// Check if one of the portal configurations is default.
		foreach ( $portal_configurations as $portal_configuration ) {
			if ( $portal_configuration->is_default ) {
				$portal_configuration_id = $portal_configuration->id;
				break;
			}
		}

		// If we still don't have a portal configuration, create one.
		if ( empty( $portal_configuration_id ) ) {
			$portal_configuration_params = array(
				'business_profile' => array(
					'headline' => esc_html__( 'Manage billing', 'woocommerce-gateway-stripe' ),
				),
				'features' => array(
					'customer_update' => array( 'enabled' => true, 'allowed_updates' => array( 'address', 'phone', 'tax_id' ) ),
					'invoice_history' => array( 'enabled' => true ),
					'payment_method_update' => array( 'enabled' => true ),
					'subscription_cancel' => array( 'enabled' => true ),
				),
			);
			try {
				$portal_configuration = Stripe\BillingPortal\Configuration::create( $portal_configuration_params );
			} catch( Exception $e ) {
				// Error creating portal configuration.
				return '';
			}

			if ( ! empty( $portal_configuration ) ) {
				$portal_configuration_id = $portal_configuration->id;
			}
		}

		try {
			$session = \Stripe\BillingPortal\Session::create([
				'customer' => $customer_id,
				'return_url' => pmpro_url( 'account' ),
			]);
			return $session->url;
		} catch ( Exception $e ) {
			return '';
		}
	}


	/****************************************
	 *********** PRIVATE METHODS ************
	 ****************************************/
	/**
	 * Shows settings for connecting to Stripe.
	 *
	 * @since 2.7.0.
	 *
	 * @param bool $livemode True if live credentials, false if sandbox.
	 * @param array $values Current settings.
	 * @param string $gateway currently being shown.
	 */
	private function show_connect_payment_option_fields( $livemode, $values, $gateway ) {
		$gateway_environment = $this->gateway_environment;

		$stripe_legacy_key      = $values['stripe_publishablekey'];
		$stripe_legacy_secret   = $values['stripe_secretkey'];
		$stripe_is_legacy_setup = ( self::using_legacy_keys() && ! empty( $stripe_legacy_key ) && ! empty( $stripe_legacy_secret ) );

		$environment = $livemode ? 'live' : 'sandbox';
		$environment2 = $livemode ? 'live' : 'test'; // For when 'test' is used instead of 'sandbox'.

		// Determine if the gateway is connected in live mode and set var.
		if ( self::has_connect_credentials( $environment ) || $stripe_is_legacy_setup ) {
			$connection_selector = 'pmpro_gateway-mode-connected';
		} else {
			$connection_selector = 'pmpro_gateway-mode-not-connected';
		}

		?>
		<tr class="pmpro_settings_divider gateway gateway_stripe_<?php echo esc_attr( $environment ); ?>"
		    <?php if ( $gateway != "stripe" || $gateway_environment != $environment ) { ?>style="display: none;"<?php } ?>>
            <td colspan="2">
				<hr />
				<h2>
					<?php esc_html_e( 'Stripe Connect Settings', 'paid-memberships-pro' ); ?>
					<span class="pmpro_gateway-mode pmpro_gateway-mode-<?php echo esc_attr( $environment2 ); ?> <?php echo esc_attr( $connection_selector ); ?>">
						<?php
							echo ( $livemode ? esc_html__( 'Live Mode:', 'paid-memberships-pro' ) : esc_html__( 'Test Mode:', 'paid-memberships-pro' ) ) . ' ';
							if ( $stripe_is_legacy_setup ) {
								esc_html_e( 'Connected with Legacy Keys', 'paid-memberships-pro' );
							} elseif( self::has_connect_credentials( $environment ) ) {
								esc_html_e( 'Connected', 'paid-memberships-pro' );
							} else {
								esc_html_e( 'Not Connected', 'paid-memberships-pro' );
							}
						?>
					</span>
				</h2>
				<?php if ( self::using_legacy_keys() && ! self::has_connect_credentials( $environment ) ) { ?>
					<div class="notice notice-large notice-warning inline">
						<p class="pmpro_stripe_webhook_notice">
							<strong><?php esc_html_e( 'Your site is using legacy API keys to authenticate with Stripe.', 'paid-memberships-pro' ); ?></strong><br />
							<?php esc_html_e( 'You can continue to use the legacy API keys or choose to upgrade to our new Stripe Connect solution below.', 'paid-memberships-pro' ); ?><br />
							<?php
							if ( $livemode ) {
								esc_html_e( 'Use the "Connect with Stripe" button below to securely authenticate with your Stripe account using Stripe Connect. Log in with the current Stripe account used for this site so that existing subscriptions are not affected by the update.', 'paid-memberships-pro' );
							} else {
								esc_html_e( 'Use the "Connect with Stripe" button below to securely authenticate with your Stripe account using Stripe Connect in test mode.', 'paid-memberships-pro' );
							}
							?>
							<a href="https://www.paidmembershipspro.com/gateway/stripe/switch-legacy-to-connect/?utm_source=plugin&utm_medium=pmpro-paymentsettings&utm_campaign=documentation&utm_content=switch-to-connect" target="_blank"><?php esc_html_e( 'Read the documentation on switching to Stripe Connect', 'paid-memberships-pro' ); ?></a>
						</p>
					</div>
				<?php } elseif ( self::using_legacy_keys() ) {  ?>
					<div class="notice notice-large notice-warning inline">
						<p class="pmpro_stripe_webhook_notice">
							<strong><?php esc_html_e( 'Your site is using legacy API keys to authenticate with Stripe.', 'paid-memberships-pro' ); ?></strong><br />
							<?php esc_html_e( 'In order to complete the transition to using Stripe legacy API keys, please click the "Disconnect from Stripe" button below.', 'paid-memberships-pro' ); ?><br />
						</p>
					</div>
				<?php } ?>
            </td>
        </tr>
		<tr class="gateway gateway_stripe_<?php echo esc_attr( $environment ); ?>" <?php if ( $gateway != "stripe" || $gateway_environment != $environment ) { ?>style="display: none;"<?php } ?>>
            <th scope="row" valign="top">
                <label><?php esc_html_e( 'Stripe Connection:', 'paid-memberships-pro' ); ?></label>
            </th>
			<td>
				<?php
				$connect_url_base = apply_filters( 'pmpro_stripe_connect_url', 'https://connect.paidmembershipspro.com' );
				if ( self::has_connect_credentials( $environment ) ) {
					$connect_url = add_query_arg(
						array(
							'action' => 'disconnect',
							'gateway_environment' => $environment2,
							'stripe_user_id' => $values[ $environment . '_stripe_connect_user_id'],
							'return_url' => rawurlencode( admin_url( 'admin.php?page=pmpro-paymentsettings' ) ),
						),
						$connect_url_base
					);
					?>
					<a href="<?php echo esc_url_raw( $connect_url ); ?>" class="pmpro-stripe-connect"><span><?php esc_html_e( 'Disconnect From Stripe', 'paid-memberships-pro' ); ?></span></a>
					<p class="description">
						<?php
						if ( $livemode ) {
							esc_html_e( 'This will disconnect all sites using this Stripe account from Stripe. Users will not be able to complete membership checkout or update their billing information. Existing subscriptions will not be affected at the gateway, but new recurring orders will not be created in this site.', 'paid-memberships-pro' );
						} else {
							esc_html_e( 'This will disconnect all sites using this Stripe account from Stripe in test mode only.', 'paid-memberships-pro' );
						}
						?>
					</p>
					<?php
				} else {
					$connect_url = add_query_arg(
						array(
							'action' => 'authorize',
							'gateway_environment' => $environment2,
							'return_url' => rawurlencode( admin_url( 'admin.php?page=pmpro-paymentsettings' ) ),
						),
						$connect_url_base
					);
					?>
					<a href="<?php echo esc_url_raw( $connect_url ); ?>" class="pmpro-stripe-connect"><span><?php esc_html_e( 'Connect with Stripe', 'paid-memberships-pro' ); ?></span></a>
					<?php
				}
				?>
				<p class="description">
					<?php
						if ( pmpro_license_isValid( null, pmpro_license_get_premium_types() ) ) {
							esc_html_e( 'Note: You have a valid license and are not charged additional platform fees for payment processing.', 'paid-memberships-pro');
						} else {
							$application_fee_percentage = self::get_application_fee_percentage();
							if ( ! empty( $application_fee_percentage ) ) {
								echo sprintf( esc_html__( 'Note: You are using the free Stripe payment gateway integration. This includes an additional %s fee for payment processing. This fee is removed by activating a premium PMPro license.', 'paid-memberships-pro' ), intval( $application_fee_percentage ) . '%' );
							} else {
								esc_html_e( 'Note: You are using the free Stripe payment gateway integration. There is no additional fee for payment processing above what Stripe charges.', 'paid-memberships-pro' );
							}
							
						}
						echo ' <a href="https://www.paidmembershipspro.com/gateway/stripe/?utm_source=plugin&utm_medium=pmpro-paymentsettings&utm_campaign=gateways&utm_content=stripe-fees#fees" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Learn More', 'paid-memberships-pro' ) . '</a>';
					?>
				</p>
				<input type='hidden' name='<?php echo esc_attr( $environment ); ?>_stripe_connect_user_id' id='<?php echo esc_attr( $environment ); ?>_stripe_connect_user_id' value='<?php echo esc_attr( $values[ $environment . '_stripe_connect_user_id'] ) ?>'/>
				<input type='hidden' name='<?php echo esc_attr( $environment ); ?>_stripe_connect_secretkey' id='<?php echo esc_attr( $environment ); ?>_stripe_connect_secretkey' value='<?php echo esc_attr( $values[ $environment . '_stripe_connect_secretkey'] ) ?>'/>
				<input type='hidden' name='<?php echo esc_attr( $environment ); ?>_stripe_connect_publishablekey' id='<?php echo esc_attr( $environment ); ?>_stripe_connect_publishablekey' value='<?php echo esc_attr( $values[ $environment . '_stripe_connect_publishablekey'] ) ?>'/>
            </td>
        </tr>
		<?php
	}

	/**
	 * Retrieve a Stripe_Customer.
	 *
	 * @since 2.7.0
	 *
	 * @param string $customer_id to retrieve.
	 * @return Stripe_Customer|null
	 */
	private function get_customer( $customer_id ) {
		try {
			$customer = Stripe_Customer::retrieve( $customer_id );
			return $customer;
		} catch ( \Throwable $e ) {
			// Assume no customer found.
		} catch ( \Exception $e ) {
			// Assume no customer found.
		}
	}

	/**
	 * Check whether a given Stripe customer has a billing address set.
	 *
	 * @since 2.7.0
	 *
	 * @param Stripe_Customer $customer to check.
	 * @return bool
	 */
	private function customer_has_billing_address( $customer ) {
		return (
			! empty( $customer ) &&
			! empty( $customer->address->line1 ) &&
			! empty( $customer->address->city ) &&
			! empty( $customer->address->state ) &&
			! empty( $customer->address->postal_code ) &&
			! empty( $customer->address->country )
		);
	}

	/**
	 * Update a customer in Stripe.
	 *
	 * @since 2.7.0
	 *
	 * @param string $customer_id to update.
	 * @param array  $args to update with.
	 * @return Stripe_Customer|string error message.
	 */
	private function update_customer( $customer_id, $args ) {
		try {
			$customer = Stripe_Customer::update( $customer_id, $args );
		} catch ( \Stripe\Error $e ) {
			return $e->getMessage();
		} catch ( \Throwable $e ) {
			return $e->getMessage();
		} catch ( \Exception $e ) {
			return $e->getMessage();
		}
		return $customer;
	}

	/**
	 * Create a new customer in Stripe.
	 *
	 * @since 2.7.0
	 *
	 * @param array  $args to update with.
	 * @return Stripe_Customer|string error message.
	 */
	private function create_customer( $args ) {
		try {
			$customer = Stripe_Customer::create( $args );
		} catch ( \Stripe\Error $e ) {
			return $e->getMessage();
		} catch ( \Throwable $e ) {
			return $e->getMessage();
		} catch ( \Exception $e ) {
			return $e->getMessage();
		}
		return $customer;
	}

	/**
	 * Create/Update Stripe customer from MemberOrder.
	 *
	 * Falls back on information in User object if insufficient
	 * information in MemberOrder.
	 *
	 * Should only be called when checkout is being proceesed. Otherwise,
	 * use update_customer_from_user() method.
	 *
	 * @since 2.7.0
	 *
	 * @param MemberOrder $order to create/update Stripe customer for.
	 * @return Stripe_Customer|false
	 */
	private function update_customer_at_checkout( $order ) {
		global $current_user;

		// Get user's ID.
		if ( ! empty( $order->user_id ) ) {
			$user_id = $order->user_id;
		}
		if ( empty( $user_id ) && ! empty( $current_user->ID ) ) {
			$user_id = $current_user->ID;
		}
		$user = empty( $user_id ) ? null : get_userdata( $user_id );
		$customer = empty( $user_id ) ? null : $this->get_customer_for_user( $user_id );

		// Get customer name.
		if ( ! empty( $order->FirstName ) && ! empty( $order->LastName ) ) {
			$name = trim( $order->FirstName . " " . $order->LastName );
		} elseif ( ! empty( $order->FirstName ) ) {
			$name = $order->FirstName;
		} elseif ( ! empty( $order->LastName ) ) {
			$name = $order->LastName;
		} elseif ( ! empty( $user->ID ) ) {
			$name = trim( $user->first_name . " " . $user->last_name );
			if ( empty( $name ) ) {
				// In case first and last names aren't set.
				$name = $user->user_login;
			}
		} else {
			$name = 'No Name';
		}

		// Get user's email.
		if ( ! empty( $order->Email ) ) {
			$email = $order->Email;
		} elseif ( ! empty( $user->user_email ) ) {
			$email = $user->user_email;
		} else {
			$email = "No Email";
		}

		// Build data to update customer with.
		$customer_args = array(
			'name'        => $name,
			'email'       => $email,
			'description' => $name . ' (' . $email . ')',
		);

		// Maybe update billing address for customer.
		if (
			! empty( $order->billing->street ) &&
			! empty( $order->billing->city ) &&
			! empty( $order->billing->state ) &&
			! empty( $order->billing->zip ) &&
			! empty( $order->billing->country )
		) {
			// We collected a billing address at checkout.
			// Send it to Stripe.
			$customer_args['address'] = array(
				'city'        => $order->billing->city,
				'country'     => $order->billing->country,
				'line1'       => $order->billing->street,
				'line2'       => '',
				'postal_code' => $order->billing->zip,
				'state'       => $order->billing->state,
			);
		} elseif (
			! $this->customer_has_billing_address( $customer ) &&
			! empty( $user->pmpro_baddress1 ) &&
			! empty( $user->pmpro_bcity ) &&
			! empty( $user->pmpro_bstate ) &&
			! empty( $user->pmpro_bzipcode ) &&
			! empty( $user->pmpro_bcountry )
		) {
			// We have an address in user meta and there is
			// no address in Stripe. May as well send it.
			$customer_args['address'] = array(
				'city'        => $user->pmpro_bcity,
				'country'     => $user->pmpro_bcountry,
				'line1'       => $user->pmpro_baddress1,
				'line2'       => $user->pmpro_baddress2,
				'postal_code' => $user->pmpro_bzipcode,
				'state'       => $user->pmpro_bstate,
			);
		}

		/**
		 * Change the information that is sent when updating/creating
		 * a Stripe_Customer from a MemberOrder.
		 *
		 * @since 2.7.0
		 *
		 * @param array       $customer_args to be sent.
		 * @param MemberOrder $order being used to create/update customer.
		 */
		$customer_args = apply_filters( 'pmpro_stripe_update_customer_at_checkout', $customer_args, $order );

		// Check if we have an existing user.
		if ( ! empty( $customer ) ) {
			// User is already a customer in Stripe. Update.
			$customer = $this->update_customer( $customer->id, $customer_args );
			if ( is_string( $customer ) ) {
				// We were not able to create a new user in Stripe.
				$order->error      = __( "Error updating customer record with Stripe.", 'paid-memberships-pro' ) . " " . $customer;
				$order->shorterror = $order->error;
				return false;
			}
			return $customer;
		}

		// No customer yet. Need to create one.
		$customer = $this->create_customer( $customer_args );
		if ( is_string( $customer ) ) {
			// We were not able to create a new user in Stripe.
			$order->error      = __( "Error creating customer record with Stripe.", 'paid-memberships-pro' ) . " " . $customer;
			$order->shorterror = $order->error;
			return false;
		}

		// If we don't have a user yet, we need to update their user meta after registration.
		if ( empty( $user_id ) ) {
			global $pmpro_stripe_customer_id;
			$pmpro_stripe_customer_id = $customer->id;
			if ( ! function_exists( 'pmpro_user_register_stripe_customerid' ) ) {
				function pmpro_user_register_stripe_customerid( $user_id ) {
					global $pmpro_stripe_customer_id;
					update_user_meta( $user_id, "pmpro_stripe_customerid", $pmpro_stripe_customer_id );
				}
				add_action( "user_register", "pmpro_user_register_stripe_customerid" );
			}
		} else {
			// User already exists. Update their Stripe customer ID.
			update_user_meta( $user_id, 'pmpro_stripe_customerid', $customer->id );
		}

		/**
		 * Fires after a Stripe_Customer is created at checkout.
		 *
		 * @since Unknown
		 * @deprecated 2.7.0. Use pmpro_stripe_update_customer_from_user or pmpro_stripe_update_customer_at_checkout.
		 *
		 * @param array       $customer_args to be sent.
		 * @param MemberOrder $order being used to create/update customer.
		 */
		do_action( 'pmpro_stripe_create_customer', $customer );
		return $customer;
	}

	/**
	 * Convert a price to a positive integer in cents (or 0 for a free price)
	 * representing how much to charge. This is how Stripe wants us to send price amounts.
	 *
	 * @param float $price to be converted into cents.
	 * @return integer
	 */
	private function convert_price_to_unit_amount( $price ) {
		$price_info = pmpro_get_price_info( $price );

		if ( ! $price_info ) {
			return 0;
		}

		return $price_info['amount_flat'];
	}

	/**
	 * Retrieve a Stripe_Subscription.
	 *
	 * @since 2.7.0
	 *
	 * @param string $subscription_id to retrieve.
	 * @return Stripe_Subscription|null
	 */
	private function get_subscription( $subscription_id ) {
		try {
			$subscription = Stripe_Subscription::retrieve( $subscription_id );
			return $subscription;
		} catch ( \Throwable $e ) {
			// Assume no subscription found.
		} catch ( \Exception $e ) {
			// Assume no subscription found.
		}
	}

	/**
	 * Get the Stripe product ID for a given membership level.
	 *
	 * @since 2.7.0
	 *
	 * @param PMPro_Membership_Leve|int $level to get product ID for.
	 * @return string|null
	 */
	private function get_product_id_for_level( $level ) {
		// Get the level object.
		if ( ! is_a( $level, 'PMPro_Membership_Level' ) ) {
			if ( is_numeric( $level ) ) {
				$level = new PMPro_Membership_Level( $level );
			}
		}

		// If we don't have a valid level, we can't get a product ID. Bail.
		if ( empty( $level->ID ) ) {
			return;
		}

		// Get the product ID from the level based on the current gateway environment.
		$gateway_environment = pmpro_getOption( 'gateway_environment' );
		if ( $gateway_environment === 'sandbox' ) {
			$stripe_product_id = $level->stripe_product_id_sandbox;
		} else {
			$stripe_product_id = $level->stripe_product_id;
		}

		// Check that the product ID exists in Stripe.
		if ( ! empty( $stripe_product_id ) ) {
			try {
				$product = Stripe_Product::retrieve( $stripe_product_id );
			} catch ( \Throwable $e ) {
				// Assume no product found.
			} catch ( \Exception $e ) {
				// Assume no product found.
			}

			if ( empty( $product ) || empty( $product->active ) ) {
				// There was an error retrieving the product or the product is archived.
				// Let's try to create a new one below.
				$stripe_product_id = null;
			}
		}

		// If a valid product does not exist for this level, create one.
		if ( empty( $stripe_product_id ) ) {
			$stripe_product_id = $this->create_product_for_level( $level, $gateway_environment );
		}

		// Return the product ID.
		return ! empty( $stripe_product_id ) ? $stripe_product_id : null;
	}

	/**
	 * Create a new Stripe product for a given membership level.
	 *
	 * WARNING: Will overwrite old Stripe product set for level if
	 * there is already one set.
	 *
	 * @since 2.7.0
   *
	 * @param PMPro_Membership_Level|int $level to create product ID for.
	 * @param string $gateway_environment to create product for.
	 * @return string|null ID of new product
	 */
	private function create_product_for_level( $level, $gateway_environment ) {
		if ( ! is_a( $level, 'PMPro_Membership_Level' ) ) {
			if ( is_numeric( $level ) ) {
				$level = new PMPro_Membership_Level( $level );
			}
		}

		if ( empty( $level->ID ) ) {
			// We do not have a valid level.
			return;
		}

		$product_args = array(
			'name' => $level->name,
		);
		/**
		 * Filter the data sent to Stripe when creating a new product for a membership level.
		 *
		 * @since 2.7.0
		 *
		 * @param array $product_args being sent to Stripe.
		 * @param PMPro_Membership_Level $level that product is being created for.
		 * @param string $gateway_environment being used.
		 */
		$product_args = apply_filters( 'pmpro_stripe_create_product_for_level', $product_args, $level, $gateway_environment );

		try {
			$product = Stripe_Product::create( $product_args );
			if ( ! empty( $product->id ) ) {
				$meta_name = 'sandbox' === $gateway_environment ? 'stripe_product_id_sandbox' : 'stripe_product_id';
				update_pmpro_membership_level_meta( $level->ID, $meta_name, $product->id );
				return $product->id;
			}
		} catch (\Throwable $th) {
			// Could not create product.
		} catch (\Exception $e) {
			// Could not create product.
		}
	}

	/**
	 * Get a Price for a given product, or create one if it doesn't exist.
	 *
	 * TODO: Add pagination.
	 *
	 * @since 2.7.0
	 *
	 * @param string $product_id to get Price for.
	 * @param float $amount that the Price will charge.
	 * @param string|null $cycle_period for subscription payments.
	 * @param string|null $cycle_number of cycle periods between each subscription payment.
	 *
	 * @return Stripe_Price|string Price or error message.
	 */
	private function get_price_for_product( $product_id, $amount, $cycle_period = null, $cycle_number = null ) {
		global $pmpro_currency;
		$currency = pmpro_get_currency();

		$is_recurring = ! empty( $cycle_period ) && ! empty( $cycle_number );

		$unit_amount = $this->convert_price_to_unit_amount( $amount );

		$cycle_period = strtolower( $cycle_period );

		// Only for use with Stripe Checkout.
		$tax_behavior = pmpro_getOption( 'stripe_tax' );
		if ( ! self::using_stripe_checkout() || empty( $tax_behavior ) ) {
			$tax_behavior = 'no';
		}

		$price_search_args = array(
			'product'  => $product_id,
			'type'     => $is_recurring ? 'recurring' : 'one_time',
			'currency' => strtolower( $pmpro_currency ),
			'limit' => 100,
		);
		if ( $is_recurring ) {
			$price_search_args['recurring'] = array( 'interval' => $cycle_period );
		}

		try {
			$prices = Stripe_Price::all( $price_search_args );
		} catch (\Throwable $th) {
			// There was an error listing prices.
			return $th->getMessage();;
		} catch (\Exception $e) {
			// There was an error listing prices.
			return $e->getMessage();
		}
		foreach ( $prices as $price ) {
			// Check whether price is the same. If not, continue.
			if ( intval( $price->unit_amount ) !== intval( $unit_amount ) ) {
				continue;
			}
			// Check if recurring structure is the same. If not, continue.
			if ( $is_recurring && ( empty( $price->recurring->interval_count ) || intval( $price->recurring->interval_count ) !== intval( $cycle_number ) ) ) {
				continue;
			}
			// Check if tax is enabled and set up correctly. If not, continue.
			if ( 'no' !== $tax_behavior && $price->tax_behavior !== $tax_behavior ) {
				continue;
			}
			return $price;
		}

		// Create a new Price.
		$price_args = array(
			'product'     => $product_id,
			'currency'    => strtolower( $pmpro_currency ),
			'unit_amount' => $unit_amount,
		);
		if ( $is_recurring ) {
			$price_args['recurring'] = array(
				'interval'       => $cycle_period,
				'interval_count' => $cycle_number
			);
		}
		if ( 'no' !== $tax_behavior ) {
			$price_args['tax_behavior'] = $tax_behavior;
		}

		try {
			$price = Stripe_Price::create( $price_args );
			if ( ! empty( $price->id ) ) {
				return $price;
			}
		} catch (\Throwable $th) {
			// Could not create product.
			return $th->getMessage();
		} catch (\Exception $e) {
			// Could not create product.
			return $e->getMessage();
		}
		return esc_html__( 'Could not create price.', 'paid-memberships-pro' );
	}

	/**
	 * Calculate the number of days until the first recurring payment
	 * for a subscription should be charged.
	 *
	 * @since 2.7.0.
	 *
	 * @param MemberOrder $order to calculate trial period days for.
	 * @param bool        $filtered whether to filter the result.
	 * @return int trial period days.
	 */
	private function calculate_trial_period_days( $order, $filtered = true ) {
		// Check if we have a free trial period set.
		if ( ! empty( $order->TrialBillingCycles ) && $order->TrialAmount == 0 ) {
			// If so, we want to account for the trial period only while calculating the profile start date.
			// We will then revert back to the original billing frequency after the calculation.
			$original_billing_frequency = $order->BillingFrequency;
			$order->BillingFrequency    = $order->BillingFrequency * ( $order->TrialBillingCycles + 1 );
		}

		// Calculate the profile start date.
		// Getting return value as Unix Timestamp so that we can calculate days more easily.
		$profile_start_date = pmpro_calculate_profile_start_date( $order, 'U', $filtered );

		// Restore the original billing frequency if needed so that the rest of the checkout has the correct info.
		if ( ! empty( $original_billing_frequency ) ) {
			$order->BillingFrequency = $original_billing_frequency;
		}

		// Convert to days. We are rounding up to ensure that customers get the full membership time that they are paying for.
		$trial_period_days = ceil( abs( $profile_start_date - time() ) / 86400 );
		return $trial_period_days;
	}

	/**
	 * Create a subscription for a customer from an order using a Stripe Price.
	 *
	 * @since 2.7.0.
	 *
	 * @param string $customer_id to create subscription for.
	 * @param MemberOrder $order to pull subscription details from.
	 * @return Stripe_Subscription|bool false if error.
	 */
	private function create_subscription_for_customer_from_order( $customer_id, $order ) {
		$subtotal = $order->PaymentAmount;
		$tax      = $order->getTaxForPrice( $subtotal );
		$amount   = pmpro_round_price( (float) $subtotal + (float) $tax );

		// Set up the subscription.
		$product_id = $this->get_product_id_for_level( $order->membership_id );
		if ( empty( $product_id ) ) {
			$order->error = esc_html__( 'Cannot find product for membership level.', 'paid-memberships-pro' );
			return false;
		}

		$price = $this->get_price_for_product( $product_id, $amount, $order->BillingPeriod, $order->BillingFrequency );
		if ( is_string( $price ) ) {
			$order->error = esc_html__( 'Cannot get price.', 'paid-memberships-pro' ) . ' ' . esc_html( $price );
			return false;
		}

		// Make sure we have a payment method on the order.
		if ( empty( $order->payment_method_id ) ) {
			$order->error = esc_html__( 'Cannot find payment method.', 'paid-memberships-pro' );
			return false;
		}

		$trial_period_days = $this->calculate_trial_period_days( $order );

		try {
			$subscription_params = array(
				'customer'          => $customer_id,
				'default_payment_method' => $order->payment_method_id,
				'items'             => array(
					array( 'price' => $price->id ),
				),
				'trial_period_days' => $trial_period_days,
				'expand'                 => array(
					'pending_setup_intent.payment_method',
				),
			);
			if ( ! self::using_legacy_keys() ) {
				$params['application_fee_percent'] = self::get_application_fee_percentage();
			}
			$subscription_params = apply_filters( 'pmpro_stripe_create_subscription_array', $subscription_params );
			$subscription = Stripe_Subscription::create( $subscription_params );
		} catch ( Stripe\Error\Base $e ) {
			$order->error = $e->getMessage();
			return false;
		} catch ( \Throwable $e ) {
			$order->error = $e->getMessage();
			return false;
		} catch ( \Exception $e ) {
			$order->error = $e->getMessage();
			return false;
		}
		return $subscription;
	}

	/**
	 * Retrieve a payment intent.
	 *
	 * @since 2.7.0.
	 *
	 * @param string $payment_intent_id to retrieve.
	 * @return Stripe_PaymentIntent|string error.
	 */
	private function retrieve_payment_intent( $payment_intent_id ) {
		try {
			$payment_intent = Stripe_PaymentIntent::retrieve( $payment_intent_id );
		} catch ( Stripe\Error\Base $e ) {
			return $e->getMessage();
		} catch ( \Throwable $e ) {
			return $e->getMessage();
		} catch ( \Exception $e ) {
			return $e->getMessage();
		}
		return $payment_intent;
	}

	/**
	 * Retrieve a setup intent.
	 *
	 * @since 2.7.0.
	 *
	 * @param string $setup_intent_id to retrieve.
	 * @return Stripe_SetupIntent|string error.
	 */
	private function retrieve_setup_intent( $setup_intent_id ) {
		try {
			$setup_intent_args = array(
				'id'     => $setup_intent_id,
				'expand' => array(
					'latest_attempt',
				),
			);
			$setup_intent = Stripe_SetupIntent::retrieve( $setup_intent_args );
		} catch ( Stripe\Error\Base $e ) {
			return $e->getMessage();
		} catch ( \Throwable $e ) {
			return $e->getMessage();
		} catch ( \Exception $e ) {
			return $e->getMessage();
		}
		return $setup_intent;
	}

	/**
	 * Confirm the payment intent after authentication.
	 *
	 * @since 2.7.0.
	 *
	 * @param string $payment_intent_id to confirm.
	 * @return Stripe_PaymentIntent|string error.
	 */
	private function process_payment_intent( $payment_intent_id ) {
		// Get the payment intent.
		$payment_intent = $this->retrieve_payment_intent( $payment_intent_id );
		if ( is_string( $payment_intent ) ) {
			// There was an issue retrieving the payment intent.
			return $payment_intent;
		}

		// Confirm the payment.
		try {
			$params = array(
				'expand' => array(
					'payment_method',
					'customer'
				),
			);
			$payment_intent->confirm( $params );
		} catch ( Stripe\Error\Base $e ) {
			return $e->getMessage();
		} catch ( \Throwable $e ) {
			return $e->getMessage();
		} catch ( \Exception $e ) {
			return $e->getMessage();
		}

		// Check that the confirmation was successful.
		if ( 'requires_action' == $payment_intent->status ) {
			return __( 'Customer authentication is required to finish setting up your subscription. Please complete the verification steps issued by your payment provider.', 'paid-memberships-pro' );
		}

		return $payment_intent;
	}

	/**
	 * Confirm the setup intent after authentication.
	 *
	 * @since 2.7.0.
	 *
	 * @param string $setup_intent_id to confirm.
	 * @return Stripe_SetupIntent|string error.
	 */
	private function process_setup_intent( $setup_intent_id ) {
		// Get the setup intent.
		$setup_intent = $this->retrieve_setup_intent( $setup_intent_id );
		if ( is_string( $setup_intent ) ) {
			return $setup_intent;
		}

		// Make sure that the setup intent was recently confirmed.
		/**
		 * The time in seconds that a setup intent must be confirmed within.
		 *
		 * @since 2.7.0
		 *
		 * @param int $seconds_to_confirm_setup_intent The time in seconds that a setup intent must be confirmed within.
		 */
		$setup_intent_timeout = apply_filters( 'pmpro_stripe_setup_intent_timeout', 60 * 10 );
		$last_setup_attempt_created = $setup_intent->latest_attempt->created;
		if (  $last_setup_attempt_created < time() - $setup_intent_timeout ) {
			return __( 'Cannot reuse an old setup intent.', 'paid-memberships-pro' );
		}

		// Make sure that the confirmation was successful.
		if ( 'requires_action' === $setup_intent->status ) {
			return __( 'Customer authentication is required to finish setting up your subscription. Please complete the verification steps issued by your payment provider.', 'paid-memberships-pro' );
		}
		return $setup_intent;
	}

	/**
	 * Add a subscription ID to the metadata of a setup intent.
	 *
	 * @since 2.7.0.
	 *
	 * @param Stripe_SetupIntent $setup_intent    The setup intent to add metadata to.
	 * @param string             $subscription_id The subscription ID that created this setup intent.
	 *
	 * @return Stripe_SetupIntent|string The setup intent object or an error message string.
	 */
	private function add_subscription_id_to_setup_intent( $setup_intent, $subscription_id ) {
		try {
			$setup_intent = Stripe_SetupIntent::update(
				$setup_intent->id,
				array(
					'metadata' => array(
						'subscription_id' => $subscription_id,
					),
					'expand' => array(
						'payment_method',
					),
				)
			);
		} catch ( \Throwable $e ) {
			return __( "Error adding metadata to setup intent.", 'paid-memberships-pro' );
		} catch ( \Exception $e ) {
			return __( "Error adding metadata to setup intent.", 'paid-memberships-pro' );
		}
		return $setup_intent;
	}

	/**
	 * Temporary function to allow users to view and delete subscription updates.
	 * Will be removed once subscription updates are completely deprecated.
	 *
	 * @since 2.7.0.
	 *
	 * @param WP_User $user whose profile is being shown.
	 * @param Stripe_Customer $customer associated with that user.
	 */
	private function user_profile_fields_subscription_updates( $user, $customer ) {
		global $pmpro_currency_symbol;

		$subscriptions = $customer->subscriptions->all();
		if ( empty( $subscriptions ) ) {
			// User does not have any subscriptions to udpate. Delete all updates.
			delete_user_meta( $user->ID, 'pmpro_stripe_updates' );
			return;
		}

		$cycles = array(
			__( 'Day(s)', 'paid-memberships-pro' )   => 'Day',
			__( 'Week(s)', 'paid-memberships-pro' )  => 'Week',
			__( 'Month(s)', 'paid-memberships-pro' ) => 'Month',
			__( 'Year(s)', 'paid-memberships-pro' )  => 'Year',
		);

		$current_year  = date_i18n( 'Y' );
		$current_month = date_i18n( 'm' );
		?>
            <h2><?php esc_html_e( 'Subscription Updates', 'paid-memberships-pro' ); ?></h2>
			<p><?php esc_html_e( 'Subscription updates will be deprecated in a future version of PMPro, though your existing subscription updates will still trigger as expected. We now instead recommend updating the subscription directly in Stripe.', 'paid-memberships-pro' ); ?></p>
            <table class="form-table">
				<input type='hidden' name='pmpro_subscription_updates_visible' value='1' />
                <tr>
                    <th><label><?php esc_html_e( 'Update', 'paid-memberships-pro' ); ?></label></th>
                    <td id="updates_td">
						<?php
						$updates = $user->pmpro_stripe_updates;

						foreach ( $updates as $update ) {
							?>
                            <div class="updates_update">
                                <select class="updates_when" name="updates_when[]" disabled>
                                    <option value="now" <?php selected( $update['when'], 'now' ); ?>>Now</option>
                                    <option value="payment" <?php selected( $update['when'], 'payment' ); ?>>After
                                        Next Payment
                                    </option>
                                    <option value="date" <?php selected( $update['when'], 'date' ); ?>>On Date
                                    </option>
                                </select>
                                <span class="updates_date"
								      <?php if ( $update['when'] != 'date' ) { ?>style="display: none;"<?php } ?>>
								<select name="updates_date_month[]" disabled>
									<?php
									for ( $i = 1; $i < 13; $i ++ ) {
										?>
                                        <option value="<?php echo esc_attr( str_pad( $i, 2, '0', STR_PAD_LEFT ) ); ?>"
										        <?php if ( ! empty( $update['date_month'] ) && $update['date_month'] == $i ) { ?>selected="selected"<?php } ?>>
											<?php echo esc_html( date_i18n( 'M', strtotime( $i . '/15/' . $current_year ) ) ); ?>
										</option>
										<?php
									}
									?>
								</select>
								<input name="updates_date_day[]" type="text" size="2"
                                       value="<?php if ( ! empty( $update['date_day'] ) ) {
									       echo esc_attr( $update['date_day'] );
								       } ?>" readonly/>
								<input name="updates_date_year[]" type="text" size="4"
                                       value="<?php if ( ! empty( $update['date_year'] ) ) {
									       echo esc_attr( $update['date_year'] );
								       } ?>" readonly/>
							</span>
                                <span class="updates_billing"
								      <?php if ( $update['when'] == "now" ) { ?>style="display: none;"<?php } ?>>
								<?php echo esc_html( $pmpro_currency_symbol ); ?><input name="updates_billing_amount[]" type="text"
                                                                           size="10"
                                                                           value="<?php echo esc_attr( $update['billing_amount'] ); ?>"
																		   readonly/>
								<small><?php esc_html_e( 'per', 'paid-memberships-pro' ); ?></small>
								<input name="updates_cycle_number[]" type="text" size="5"
                                       value="<?php echo esc_attr( $update['cycle_number'] ); ?>" readonly/>
								<select name="updates_cycle_period[]" disabled>
								  <?php
								  foreach ( $cycles as $name => $value ) {
									  echo "<option value='" . esc_attr( $value ) . "'";
									  if ( ! empty( $update['cycle_period'] ) && $update['cycle_period'] == $value ) {
										  echo " selected='selected'";
									  }
									  echo ">" . esc_html( $name ) . "</option>";
								  }
								  ?>
								</select>
							</span>
                                <span>
								<a class="updates_remove" href="javascript:void(0);"><?php esc_html_e( 'Remove', 'paid-memberships-pro' ); ?></a>
							</span>
                            </div>
							<script>
							jQuery(function () {
								//remove updates when clicking
								jQuery('.updates_remove').on('click', function () {
									jQuery(this).parent().parent().remove();
								});
								jQuery('form').on('submit', function () {
									// Makes sure that disabled select fields are still submitted.
									jQuery(this).find(':input').prop('disabled', false);
								});
							});
							</script>
							<?php
						}
						?>
                    </td>
                </tr>
            </table>
			<?php
	}

	/****************************************
	 ******* METHODS BECOMING PRIVATE *******
	 ****************************************/
	/**
	 * Get available webhooks
	 *
	 * @since 2.4
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 */
	public static function get_webhooks( $limit = 10 ) {
		// Show deprecation warning if called publically.
		pmpro_method_should_be_private( '2.7.0' );

		if ( ! class_exists( 'Stripe\WebhookEndpoint' ) ) {
			// Load Stripe library.
			new PMProGateway_stripe();
			if ( ! class_exists( 'Stripe\WebhookEndpoint' ) ) {
				// Couldn't load library.
				return false;
			}
		}

		try {
			$webhooks = Stripe_Webhook::all( [ 'limit' => apply_filters( 'pmpro_stripe_webhook_retrieve_limit', $limit ) ] );
		} catch (\Throwable $th) {
			$webhooks = $th->getMessage();
		} catch (\Exception $e) {
			$webhooks = $e->getMessage();
		}

		return $webhooks;
	}

	/**
	 * Get current webhook URL for website to compare.
	 *
	 * @since 2.4
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 */
	public static function get_site_webhook_url() {
		// Show deprecation warning if called publically.
		pmpro_method_should_be_private( '2.7.0' );
		return admin_url( 'admin-ajax.php' ) . '?action=stripe_webhook';
	}

	/**
	 * List of current enabled events required for PMPro to work.
	 *
	 * @since 2.4
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 */
	public static function webhook_events() {
		// Show deprecation warning if called publically.
		pmpro_method_should_be_private( '2.7.0' );

		$events = array(
			'invoice.payment_succeeded',
			'invoice.payment_action_required',
			'customer.subscription.deleted',
			'charge.failed',
      		'charge.refunded',
			'checkout.session.completed',
			'checkout.session.async_payment_succeeded',
			'checkout.session.async_payment_failed',
		);

		return apply_filters( 'pmpro_stripe_webhook_events', $events );
	}

	/**
	 * Create webhook with relevant events
	 *
	 * @since 2.4
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 */
	public static function create_webhook() {
		// Show deprecation warning if called publically.
		pmpro_method_should_be_private( '2.7.0' );

		try {
			$create = Stripe_Webhook::create([
				'url' => self::get_site_webhook_url(),
				'enabled_events' => self::webhook_events(),
				'api_version' => PMPRO_STRIPE_API_VERSION,
			]);

			if ( $create ) {
				return $create->id;
			}
		} catch (\Throwable $th) {
			//throw $th;
			return new WP_Error( 'error', $th->getMessage() );
		} catch (\Exception $e) {
			//throw $th;
			return new WP_Error( 'error', $e->getMessage() );
		}

	}

	/**
	 * See if a webhook is registered with Stripe.
	 *
	 * @since 2.4
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 */
	public static function does_webhook_exist( $force = false ) {
		// Show deprecation warning if called publically.
		pmpro_method_should_be_private( '2.7.0' );

		static $cached_webhook = null;
		if ( ! empty( $cached_webhook ) && ! $force ) {
			return $cached_webhook;
		}

		$webhooks = self::get_webhooks();

		$webhook_id = false;
		if ( ! empty( $webhooks ) && ! empty( $webhooks['data'] ) ) {

			$pmpro_webhook_url = self::get_site_webhook_url();

			foreach( $webhooks as $webhook ) {
				if ( $webhook->url == $pmpro_webhook_url ) {
					$webhook_id = $webhook->id;
					$webhook_events = $webhook->enabled_events;
					$webhook_api_version = $webhook->api_version;
					$webhook_status = $webhook->status;
					continue;
				}
			}
		} else {
			$webhook_id = false; // make sure it's false if none are found.
		}

		if ( $webhook_id ) {
			$webhook_data = array();
			$webhook_data['webhook_id'] = $webhook_id;
			$webhook_data['enabled_events'] = $webhook_events;
			$webhook_data['api_version'] = $webhook_api_version;
			$webhook_data['status'] = $webhook_status;
			$cached_webhook = $webhook_data;
		} else {
			$cached_webhook = false;
		}
		return $cached_webhook;
	}

	/**
	 * Get a list of events that are missing between the created existing webhook and required webhook events for Paid Memberships Pro.
	 *
	 * @since 2.4
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 */
	public static function check_missing_webhook_events( $webhook_events ) {
		// Show deprecation warning if called publically.
		pmpro_method_should_be_private( '2.7.0' );

		// Get required events
		$pmpro_webhook_events = self::webhook_events();

		// No missing events if webhook event is "All Events" selected.
		if ( is_array( $webhook_events ) && $webhook_events[0] === '*' ) {
			return false;
		}

		if ( count( array_diff( $pmpro_webhook_events, $webhook_events ) ) ) {
			$events = array_unique( array_merge( $pmpro_webhook_events, $webhook_events ) );
			// Force reset of indexes for Stripe.
			$events = array_values( $events );
		} else {
			$events = false;
		}

		return $events;
	}

	/**
	 * Update required webhook enabled events.
	 *
	 * @since 2.4
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 */
	public static function update_webhook_events() {
		// Show deprecation warning if called publically.
		pmpro_method_should_be_private( '2.7.0' );

		// Also checks database to see if it's been saved.
		$webhook = self::does_webhook_exist();

		if ( empty( $webhook ) ) {
			$create = self::create_webhook();
			return $create;
		}

		// Bail if no enabled events for a webhook are passed through.
		if ( ! isset( $webhook['enabled_events'] ) ) {
			return;
		}

		$events = self::check_missing_webhook_events( $webhook['enabled_events'] );

		if ( $events ) {

			try {
				$update = Stripe_Webhook::update(
					$webhook['webhook_id'],
					['enabled_events' => $events ]
				);

				if ( $update ) {
					return $update;
				}
			} catch (\Throwable $th) {
				//throw $th;
				return new WP_Error( 'error', $th->getMessage() );
			} catch (\Exception $e) {
				//throw $th;
				return new WP_Error( 'error', $e->getMessage() );
			}

		}

	}

	/**
	 * Delete an existing webhook.
	 *
	 * @since 2.4
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 */
	public static function delete_webhook( $webhook_id, $secretkey = false ) {
		// Show deprecation warning if called publically.
		pmpro_method_should_be_private( '2.7.0' );

		if ( empty( $secretkey ) ) {
			$secretkey = self::get_secretkey();
		}
		if ( is_array( $webhook_id ) ) {
			$webhook_id = $webhook_id['webhook_id'];
		}

		try {
			$stripe = new Stripe_Client( $secretkey );
			$delete = $stripe->webhookEndpoints->delete( $webhook_id, [] );
		} catch (\Throwable $th) {
			return new WP_Error( 'error', $th->getMessage() );
		} catch (\Exception $e) {
			return new WP_Error( 'error', $e->getMessage() );
		}

		return $delete;
	}

	/**
	 * Helper method to save the subscription ID to make sure the membership doesn't get cancelled by the webhook
	 *
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 */
	public static function ignoreCancelWebhookForThisSubscription( $subscription_id, $user_id = null ) {
		pmpro_method_should_be_private( '2.7.0' );

		if ( empty( $user_id ) ) {
			global $current_user;
			$user_id = $current_user->ID;
		}

		$preserve = get_user_meta( $user_id, 'pmpro_stripe_dont_cancel', true );

		// No previous values found, init the array
		if ( empty( $preserve ) ) {
			$preserve = array();
		}

		// Store or update the subscription ID timestamp (for cleanup)
		$preserve[ $subscription_id ] = current_time( 'timestamp' );

		update_user_meta( $user_id, 'pmpro_stripe_dont_cancel', $preserve );
	}

	/**
	 * Helper method to process a Stripe subscription update.
	 *
	 * Only called during subscription updates. Should be completely deprecated once that functionality is removed.
	 *
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 */
	static function updateSubscription( $update, $user_id ) {
		pmpro_method_should_be_private( '2.7.0' );
		global $wpdb;

		//get level for user
		$user_level = pmpro_getMembershipLevelForUser( $user_id );

		//get current plan at Stripe to get payment date
		$last_order = new MemberOrder();
		$last_order->getLastMemberOrder( $user_id );
		$last_order->setGateway( 'stripe' );
		$last_order->Gateway->update_customer_at_checkout( $last_order );

		$subscription = $last_order->Gateway->get_subscription( $last_order->subscription_transaction_id );

		if ( ! empty( $subscription ) ) {
			$end_timestamp = $subscription->current_period_end;

			//cancel the old subscription
			if ( ! $last_order->Gateway->cancelSubscriptionAtGateway( $subscription, true ) ) {
				//throw error and halt save
				if ( ! function_exists( 'pmpro_stripe_user_profile_fields_save_error' ) ) {
					//throw error and halt save
					function pmpro_stripe_user_profile_fields_save_error( $errors, $update, $user ) {
						$errors->add( 'pmpro_stripe_updates', __( 'Could not cancel the old subscription. Updates have not been processed.', 'paid-memberships-pro' ) );
					}

					add_filter( 'user_profile_update_errors', 'pmpro_stripe_user_profile_fields_save_error', 10, 3 );
				}

				//stop processing updates
				return;
			}
		}

		//if we didn't get an end date, let's set one one cycle out
		if ( empty( $end_timestamp ) ) {
			$end_timestamp = strtotime( "+" . $update['cycle_number'] . " " . $update['cycle_period'], current_time( 'timestamp' ) );
		}

		//build order object
		$update_order = new MemberOrder();
		$update_order->setGateway( 'stripe' );
		$update_order->code             = $update_order->getRandomCode();
		$update_order->user_id          = $user_id;
		$update_order->membership_id    = $user_level->id;
		$update_order->membership_name  = $user_level->name;
		$update_order->InitialPayment   = 0;
		$update_order->PaymentAmount    = $update['billing_amount'];
		$update_order->ProfileStartDate = date_i18n( "Y-m-d", $end_timestamp );
		$update_order->BillingPeriod    = $update['cycle_period'];
		$update_order->BillingFrequency = $update['cycle_number'];
		$update_order->getMembershipLevel();

		//need filter to reset ProfileStartDate
		$profile_start_date = $update_order->ProfileStartDate;
		add_filter( 'pmpro_profile_start_date', function ( $startdate, $order ) use ( $profile_start_date ) {
			return "{$profile_start_date}T0:0:0";
		}, 10, 2 );

		//update subscription
		$customer = $update_order->Gateway->update_customer_at_checkout( $update_order, true );
		$update_order->stripe_customer = $customer;
		$update_order->Gateway->process_subscriptions( $update_order );

		//update membership
		$sqlQuery = "UPDATE $wpdb->pmpro_memberships_users
						SET billing_amount = '" . esc_sql( $update['billing_amount'] ) . "',
							cycle_number = '" . esc_sql( $update['cycle_number'] ) . "',
							cycle_period = '" . esc_sql( $update['cycle_period'] ) . "',
							trial_amount = '',
							trial_limit = ''
						WHERE user_id = '" . esc_sql( $user_id ) . "'
							AND membership_id = '" . esc_sql( $last_order->membership_id ) . "'
							AND status = 'active'
						LIMIT 1";

		$wpdb->query( $sqlQuery );

		//save order so we know which plan to look for at stripe (order code = plan id)
		$update_order->Gateway->clean_up( $update_order );
		$update_order->status = "success";
		$update_order->saveOrder();
	}

	/**
	 * Update the payment method for a subscription.
	 *
	 * Only called on update billing page.
	 *
	 * @deprecated 2.7.0
	 *
	 * @param MemberOrder $order The MemberOrder object.
	 */
	public function update_payment_method_for_subscriptions( &$order ) {
		_deprecated_function( __METHOD__, '2.7.0', 'PMProGateway_stripe::update' );

		// get customer
		$customer = $this->update_customer_at_checkout( $order );

		if ( empty( $customer ) ) {
			return false;
		}

		// get all subscriptions
		if ( ! empty( $customer->subscriptions ) ) {
			$subscriptions = $customer->subscriptions->all();

			foreach( $subscriptions as $subscription ) {
				// check if cancelled or expired
				if ( in_array( $subscription->status, array( 'canceled', 'incomplete', 'incomplete_expired' ) ) ) {
					continue;
				}

				// check if we have a related order for it
				$one_order = new MemberOrder();
				$one_order->getLastMemberOrderBySubscriptionTransactionID( $subscription->id );
				if ( empty( $one_order ) || empty( $one_order->id ) ) {
					continue;
				}

				// update the payment method
				$subscription->default_payment_method = $customer->invoice_settings->default_payment_method;
				$subscription->save();
			}
		}

		return true;
	}

	/**
	 * Helper method to cancel a subscription at Stripe and also clear up any upaid invoices.
	 *
	 * @since 1.8
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 */
	public function cancelSubscriptionAtGateway( $subscription, $preserve_local_membership = false ) {
		pmpro_method_should_be_private( '2.7.0' );

		// Check if a valid sub.
		if ( empty( $subscription ) || empty( $subscription->id ) ) {
			return false;
		}

		// If this is already cancelled, return true.
		if ( ! empty( $subscription->canceled_at ) ) {
			return true;
		}

		// Make sure we get the customer for this subscription.
		$order = new MemberOrder();
		$order->getLastMemberOrderBySubscriptionTransactionID( $subscription->id );

		// No order?
		if ( empty( $order ) ) {
			//lets cancel anyway, but this is suspicious
			$r = $subscription->cancel();

			return true;
		}

		// Okay have an order, so get customer so we can cancel invoices too
		$customer = $this->update_customer_at_checkout( $order );

		// Get open invoices.
		$invoices = Stripe_Invoice::all(['customer' => $customer->id, 'status' => 'open']);

		// Found it, cancel it.
		try {
			// Find any open invoices for this subscription and forgive them.
			if ( ! empty( $invoices ) ) {
				foreach ( $invoices->data as $invoice ) {
					if ( 'open' == $invoice->status && $invoice->subscription == $subscription->id ) {
						$invoice->voidInvoice();
					}
				}
			}

			// Sometimes we don't want to cancel the local membership when Stripe sends its webhook.
			if ( $preserve_local_membership ) {
				self::ignoreCancelWebhookForThisSubscription( $subscription->id, $order->user_id );
			}

			// Cancel
			$r = $subscription->cancel();

			return true;
		} catch ( \Throwable $e ) {
			return false;
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 */
	public function get_payment_method( &$order ) {
		pmpro_method_should_be_private( '2.7.0' );
		if ( ! empty( $order->payment_method_id ) ) {
			try {
				$payment_method = Stripe_PaymentMethod::retrieve( $order->payment_method_id );
			} catch ( Stripe\Error\Base $e ) {
				$order->error = $e->getMessage();
				return false;
			} catch ( \Throwable $e ) {
				$order->error = $e->getMessage();
				return false;
			} catch ( \Exception $e ) {
				$order->error = $e->getMessage();
				return false;
			}
		}

		if ( empty( $payment_method ) ) {
			return false;
		}

		return $payment_method;
	}

	/**
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private in a future version.
	 */
	public function process_charges( &$order ) {
		pmpro_method_should_be_private( '2.7.0' );
		if ( 0 == floatval( $order->InitialPayment ) ) {
			return true;
		}

		$payment_intent = $this->get_payment_intent( $order );
		if ( empty( $payment_intent) ) {
			// There was an error, and the message should already
			// be saved on the order.
			return false;
		}
		// Save payment intent to order so that we can use it in confirm_payment_intent().
		$order->stripe_payment_intent = $payment_intent;

		$this->confirm_payment_intent( $order );

		if ( ! empty( $order->error ) ) {
			$order->error = $order->error;

			return false;
		}

		return true;
	}

	/**
	 * Only called during subscription updates. Should be completely deprecated once that functionality is removed.
	 *
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 *
	 * @param MemberOrder $order The MemberOrder object.
	 */
	public function get_setup_intent( &$order ) {
		pmpro_method_should_be_private( '2.7.0' );
		if ( ! empty( $order->setup_intent_id ) ) {
			try {
				$setup_intent = Stripe_SetupIntent::retrieve( $order->setup_intent_id );
			} catch ( Stripe\Error\Base $e ) {
				$order->error = $e->getMessage();
				return false;
			} catch ( \Throwable $e ) {
				$order->error = $e->getMessage();
				return false;
			} catch ( \Exception $e ) {
				$order->error = $e->getMessage();
				return false;
			}
		}

		if ( empty( $setup_intent ) ) {
			$setup_intent = $this->create_setup_intent( $order );
		}

		if ( empty( $setup_intent ) ) {
			return false;
		}

		return $setup_intent;
	}

	/**
	 * @deprecated 2.7.0. Use get_setup_intent() instead.
	 */
	public function set_setup_intent( &$order, $force = false ) {
		_deprecated_function( __FUNCTION__, '2.7.0', 'get_setup_intent()' );
		if ( ! empty( $this->setup_intent ) && ! $force ) {
			return true;
		}

		$setup_intent = $this->get_setup_intent( $order );

		if ( empty( $setup_intent ) ) {
			return false;
		}

		$this->setup_intent = $setup_intent;

		return true;
	}

	/**
	 * Only called during subscription updates. Should be completely deprecated once that functionality is removed.
	 *
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 *
	 * @param MemberOrder $order The MemberOrder object.
	 */
	public function create_setup_intent( &$order ) {
		pmpro_method_should_be_private( '2.7.0' );
		$this->create_plan( $order );
		$order->stripe_subscription = $this->create_subscription( $order );
		$this->delete_plan( $order );

		if ( ! empty( $order->error ) || empty( $order->stripe_subscription->pending_setup_intent ) ) {
			return false;
		}

		return $order->stripe_subscription->pending_setup_intent;
	}

	/**
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 */
	public function confirm_payment_intent( &$order ) {
		pmpro_method_should_be_private( '2.7.0' );
		try {
			$params = array(
				'expand' => array(
					'payment_method',
				),
			);
			$order->stripe_payment_intent->confirm( $params );
		} catch ( Stripe\Error\Base $e ) {
			$order->error = $e->getMessage();
			return false;
		} catch ( \Throwable $e ) {
			$order->error = $e->getMessage();
			return false;
		} catch ( \Exception $e ) {
			$order->error = $e->getMessage();
			return false;
		}

		if ( 'requires_action' == $order->stripe_payment_intent->status ) {
			$order->errorcode = true;
			$order->error = __( 'Customer authentication is required to complete this transaction. Please complete the verification steps issued by your payment provider.', 'paid-memberships-pro' );
			$order->error_type = 'pmpro_alert';

			return false;
		}

		return true;
	}

	/**
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 */
	public function confirm_setup_intent( &$order ) {
    pmpro_method_should_be_private( '2.7.0' );
		if ( empty( $order->stripe_setup_intent ) ) {
			return true;
		}

		if ( 'requires_action' === $order->stripe_setup_intent->status ) {
			$order->errorcode = true;
			$order->error     = __( 'Customer authentication is required to finish setting up your subscription. Please complete the verification steps issued by your payment provider.', 'paid-memberships-pro' );

			return false;
		}

	}

	/**
 	 * Get available Apple Pay domains.
	 *
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
 	 */
	public function pmpro_get_apple_pay_domains( $limit = 10 ) {
		pmpro_method_should_be_private( '2.7.0' );
		try {
			$apple_pay_domains = Stripe_ApplePayDomain::all( [ 'limit' => apply_filters( 'pmpro_stripe_apple_pay_domain_retrieve_limit', $limit ) ] );
		} catch (\Throwable $th) {
			$apple_pay_domains = array();
	   	}

		return $apple_pay_domains;
	}

	/**
 	 * Register domain with Apple Pay.
 	 *
 	 * @since 2.4
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
 	 */
	public function pmpro_create_apple_pay_domain() {
		pmpro_method_should_be_private( '2.7.0' );
		try {
			$create = Stripe_ApplePayDomain::create([
				'domain_name' => sanitize_text_field( $_SERVER['HTTP_HOST'] ),
			]);
		} catch (\Throwable $th) {
			//throw $th;
			return false;
		}
		return $create;
	}

	/**
 	 * See if domain is registered with Apple Pay.
 	 *
 	 * @since 2.4
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
 	 */
	public function pmpro_does_apple_pay_domain_exist() {
		pmpro_method_should_be_private( '2.7.0' );
		$apple_pay_domains = $this->pmpro_get_apple_pay_domains();

		if ( empty( $apple_pay_domains ) ) {
			return false;
		}

		foreach( $apple_pay_domains as $apple_pay_domain ) {
			if ( $apple_pay_domain->domain_name === $_SERVER['HTTP_HOST'] ) {
				return true;
			}
		}
		return false;
   }

   /**
	* @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
    */
	public function get_account() {
		pmpro_method_should_be_private( '2.7.0' );
		try {
			$account = Stripe_Account::retrieve();
		} catch ( Stripe\Error\Base $e ) {
			return false;
		} catch ( \Throwable $e ) {
			return false;
		} catch ( \Exception $e ) {
			return false;
		}

		if ( empty( $account ) ) {
			return false;
		}

		return $account;
	}

	/**
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 */
	public static function get_account_country() {
		pmpro_method_should_be_private( '2.7.0' );
		$account_country = get_transient( 'pmpro_stripe_account_country' );
		if ( empty( $account_country ) ) {
			$stripe = new PMProGateway_stripe();
			$account = $stripe->get_account();
			if ( ! empty( $account ) && ! empty( $account->country ) ) {
				$account_country = $account->country;
				set_transient( 'pmpro_stripe_account_country', $account_country );
			}
		}
		return $account_country ?: 'US';
	}

	/**
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 */
	public function clean_up( &$order ) {
    pmpro_method_should_be_private( '2.7.0' );
		if ( ! empty( $order->stripe_payment_intent ) && 'succeeded' == $order->stripe_payment_intent->status ) {
			$order->payment_transaction_id = $order->stripe_payment_intent->latest_charge;
		}

		if ( empty( $order->subscription_transaction_id ) && ! empty( $order->stripe_subscription ) ) {
			$order->subscription_transaction_id = $order->stripe_subscription->id;
		}
	}

	/**
	 * Get percentage of Stripe payment to charge as application fee.
	 *
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 *
	 * @return int percentage to charge for application fee.
	 */
	public static function get_application_fee_percentage() {
		pmpro_method_should_be_private( '2.7.0' );
		if ( self::using_legacy_keys() ) {
			return 0;
		}

		// Some countries do not allow us to use application fees. If we are in one of those
		// countries, we should set the percentage to 0. This is a temporary fix until we
		// have a better solution or until all countries allow us to use application fees.
		$countries_to_disable_application_fees = array(
			'IN', // India.
			'MX', // Mexico.
			'MY', // Malaysia.
		);
		if ( in_array( self::get_account_country(), $countries_to_disable_application_fees ) ) {
			return 0;
		}

		// Check if we specified a reduced application fee for this website.
		$application_fee_percentage = pmpro_getOption( 'stripe_connect_reduced_application_fee' );
		if ( empty( $application_fee_percentage ) ) {
			$application_fee_percentage = 2; // 2% is the default.
		}

		// Check if we have a valid license key.
		$application_fee_percentage = pmpro_license_isValid( null, pmpro_license_get_premium_types() ) ? 0 : $application_fee_percentage;
		$application_fee_percentage = apply_filters( 'pmpro_set_application_fee_percentage', $application_fee_percentage );

		return round( floatval( $application_fee_percentage ), 2 );
	}

	/**
	 * Add application fee to params to be sent to Stripe.
	 *
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 *
	 * @param  array $params to be sent to Stripe.
	 * @return array params with application fee if applicable.
	 */
	public static function add_application_fee_amount( $params ) {
		pmpro_method_should_be_private( '2.7.0' );
		if ( empty( $params['amount'] ) || self::using_legacy_keys() ) {
			return $params;
		}
		$amount = $params['amount'];
		$application_fee = $amount * ( self::get_application_fee_percentage() / 100 );
		$application_fee = floor( $application_fee );
		if ( ! empty( $application_fee ) ) {
			$params['application_fee_amount'] = intval( $application_fee );
		}
		return $params;
	}

	/**
	 * Should we show the legacy key fields on the payment settings page.
	 * We should if the site is using legacy keys already or
	 * if a filter has been set.
	 * @since 2.6
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 */
	public static function show_legacy_keys_settings() {
		pmpro_method_should_be_private( '2.7.0' );
		$r = self::using_legacy_keys();
		$r = apply_filters( 'pmpro_stripe_show_legacy_keys_settings', $r );
		return $r;
	}

	/**
	 * Get the Stripe secret key based on gateway environment.
	 *
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 *
	 * @return The Stripe secret key.
	 */
	public static function get_secretkey() {
		pmpro_method_should_be_private( '2.7.0' );
		$secretkey = '';
		if ( self::using_legacy_keys() ) {
			$secretkey = pmpro_getOption( 'stripe_secretkey' );
		} else {
			$secretkey = pmpro_getOption( 'gateway_environment' ) === 'live'
				? pmpro_getOption( 'live_stripe_connect_secretkey' )
				: pmpro_getOption( 'sandbox_stripe_connect_secretkey' );
		}
		return $secretkey;
	}

	/**
	 * Get the Stripe publishable key based on gateway environment.
	 *
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 *
	 * @return The Stripe publishable key.
	 */
	public static function get_publishablekey() {
		pmpro_method_should_be_private( '2.7.0' );
		$publishablekey = '';
		if ( self::using_legacy_keys() ) {
			$publishablekey = pmpro_getOption( 'stripe_publishablekey' );
		} else {
			$publishablekey = pmpro_getOption( 'gateway_environment' ) === 'live'
				? pmpro_getOption( 'live_stripe_connect_publishablekey' )
				: pmpro_getOption( 'sandbox_stripe_connect_publishablekey' );
		}
		return $publishablekey;
	}

	/**
	 * Get the Stripe Connect User ID based on gateway environment.
	 *
	 * @since 2.6.0
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 *
	 * @return string The Stripe Connect User ID.
	 */
	public static function get_connect_user_id() {
		pmpro_method_should_be_private( '2.7.0' );
		return pmpro_getOption( 'gateway_environment' ) === 'live'
			? pmpro_getOption( 'live_stripe_connect_user_id' )
			: pmpro_getOption( 'sandbox_stripe_connect_user_id' );
	}

	/**
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private in a future version.
	 */
	public function get_payment_intent( &$order ) {
		pmpro_method_should_be_private( '2.7.0' );
		if ( ! empty( $order->payment_intent_id ) ) {
			try {
				$payment_intent = Stripe_PaymentIntent::retrieve( $order->payment_intent_id );
			} catch ( Stripe\Error\Base $e ) {
				$order->error = $e->getMessage();
				return false;
			} catch ( \Throwable $e ) {
				$order->error = $e->getMessage();
				return false;
			} catch ( \Exception $e ) {
				$order->error = $e->getMessage();
				return false;
			}
		}

		if ( empty( $payment_intent ) ) {
			$payment_intent = $this->create_payment_intent( $order );
		}

		if ( empty( $payment_intent ) ) {
			return false;
		}

		return $payment_intent;
	}

	/**
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private in a future version.
	 */
	public function create_payment_intent( &$order ) {
		pmpro_method_should_be_private( '2.7.0' );
		global $pmpro_currency;

		$amount          = $order->InitialPayment;
		$order->subtotal = $amount;
		$tax             = $order->getTax( true );

		$amount = pmpro_round_price( (float) $order->subtotal + (float) $tax );

		$params = array(
			'customer'               => $order->stripe_customer->id,
			'payment_method'         => $order->payment_method_id,
			'amount'                 => $this->convert_price_to_unit_amount( $amount ),
			'currency'               => $pmpro_currency,
			'confirmation_method'    => 'manual',
			'description'            => apply_filters( 'pmpro_stripe_order_description', "Order #" . $order->code . ", " . trim( $order->FirstName . " " . $order->LastName ) . " (" . $order->Email . ")", $order ),
			'setup_future_usage'     => 'off_session',
		);
		$params = self::add_application_fee_amount( $params );

		/**
		 * Filter params used to create the payment intent.
		 *
		 * @since 2.4.1
		 *
	 	 * @param array  $params 	Array of params sent to Stripe.
		 * @param object $order		Order object for this checkout.
		 */
		$params = apply_filters( 'pmpro_stripe_payment_intent_params', $params, $order );

		try {
			$payment_intent = Stripe_PaymentIntent::create( $params );
		} catch ( Stripe\Error\Base $e ) {
			$order->error = $e->getMessage();
			return false;
		} catch ( \Throwable $e ) {
			$order->error = $e->getMessage();
			return false;
		} catch ( \Exception $e ) {
			$order->error = $e->getMessage();
			return false;
		}

		return $payment_intent;
	}

	/**
	 * Only called during subscription updates. Should be completely deprecated once that functionality is removed.
	 *
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private in a future version.
	 *
	 * @param MemberOrder $order The MemberOrder object.
	 */
	public function process_subscriptions( &$order ) {
		pmpro_method_should_be_private( '2.7.0' );
		if ( ! pmpro_isLevelRecurring( $order->membership_level ) ) {
			return true;
		}

		//before subscribing, let's clear out the updates so we don't trigger any during sub
		if ( ! empty( $user_id ) ) {
			$old_user_updates = get_user_meta( $user_id, "pmpro_stripe_updates", true );
			update_user_meta( $user_id, "pmpro_stripe_updates", array() );
		}

		$setup_intent = $this->get_setup_intent( $order );
		if ( empty( $setup_intent ) ) {
			// There was an error, and the message should already
			// be saved on the order.
			return false;
		}
		// Save setup intent to order so that we can use it in confirm_setup_intent().
		$order->stripe_setup_intent = $setup_intent;

		$this->confirm_setup_intent( $order );

		if ( ! empty( $order->error ) ) {
			$order->error = $order->error;

			//give the user any old updates back
			if ( ! empty( $user_id ) ) {
				update_user_meta( $user_id, "pmpro_stripe_updates", $old_user_updates );
			}

			return false;
		}

		//save new updates if this is at checkout
		//empty out updates unless set above
		if ( empty( $new_user_updates ) ) {
			$new_user_updates = array();
		}

		//update user meta
		if ( ! empty( $user_id ) ) {
			update_user_meta( $user_id, "pmpro_stripe_updates", $new_user_updates );
		} else {
			//need to remember the user updates to save later
			global $pmpro_stripe_updates;
			$pmpro_stripe_updates = $new_user_updates;

			if( ! function_exists( 'pmpro_user_register_stripe_updates' ) ) {
				function pmpro_user_register_stripe_updates( $user_id ) {
					global $pmpro_stripe_updates;
					update_user_meta( $user_id, 'pmpro_stripe_updates', $pmpro_stripe_updates );
				}
				add_action( 'user_register', 'pmpro_user_register_stripe_updates' );
			}
		}

		return true;
	}

	/**
	 * Only called during subscription updates. Should be completely deprecated once that functionality is removed.
	 *
	 * @deprecated 2.7.0. Will only be deprecated once we create a function with better params.
	 *
	 * @param MemberOrder $order The MemberOrder object.
	 */
	public function create_subscription( &$order ) {
		// _deprecated_function( __FUNCTION__, '2.7.0' );
		//subscribe to the plan
		try {
			$params              = array(
				'customer'               => $order->stripe_customer->id,
				'items'                  => array(
					array( 'plan' => $order->code ),
				),
				'trial_period_days'      => $order->TrialPeriodDays,
				'expand'                 => array(
					'pending_setup_intent.payment_method',
				),
			);
			if ( ! self::using_legacy_keys() ) {
				$params['application_fee_percent'] = self::get_application_fee_percentage();
			}
			$order->subscription = Stripe_Subscription::create( apply_filters( 'pmpro_stripe_create_subscription_array', $params ) );
		} catch ( Stripe\Error\Base $e ) {
			$order->error = $e->getMessage();
			return false;
		} catch ( \Throwable $e ) {
			$order->error = $e->getMessage();
			return false;
		} catch ( \Exception $e ) {
			$order->error = $e->getMessage();
			return false;
		}

		return $order->subscription;

	}

	/**
	 * Only called during subscription updates. Should be completely deprecated once that functionality is removed.
	 *
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 *
	 * @param MemberOrder $order The MemberOrder object.
	 */
	public function delete_plan( &$order ) {
		// _deprecated_function( __FUNCTION__, '2.7.0' );
		try {
			// Delete the product first while we have a reference to it...
			if ( ( ! empty( $order->plan->product ) ) && ( ! $this->archive_product( $order ) ) ) {
				return false;
			}
			// Then delete the plan.
			$order->plan->delete();
		} catch ( Stripe\Error\Base $e ) {
			$order->error = $e->getMessage();

			return false;
		} catch ( \Throwable $e ) {
			$order->error = $e->getMessage();

			return false;
		} catch ( \Exception $e ) {
			$order->error = $e->getMessage();

			return false;
		}

		return true;
	}

	/**
	 * Only called during subscription updates. Should be completely deprecated once that functionality is removed.
	 *
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 *
	 * @param MemberOrder $order The MemberOrder object.
	 */
	public function archive_product( &$order ) {
		// _deprecated_function( __FUNCTION__, '2.7.0' );
		try {
			$product = Stripe_Product::update( $order->plan->product, array( 'active' => false ) );
		} catch ( Stripe\Error\Base $e ) {
			$order->error = $e->getMessage();
			return false;
		} catch ( \Throwable $e ) {
			$order->error = $e->getMessage();
			return false;
		} catch ( \Exception $e ) {
			$order->error = $e->getMessage();
			return false;
		}

		return true;
	}

	/****************************************
	 ********** DEPRECATED METHODS **********
	 ****************************************/
	/**
	 * Make a one-time charge with Stripe
	 *
	 * @since 1.4
	 * @deprecated 2.7.0. Use process_charges() instead.
	 */
	public function charge( &$order ) {
		_deprecated_function( __FUNCTION__, '2.7.0' );
		global $pmpro_currency;

		//create a code for the order
		if ( empty( $order->code ) ) {
			$order->code = $order->getRandomCode();
		}

		//what amount to charge?
		$amount = $order->InitialPayment;

		//tax
		$order->subtotal = $amount;
		$tax             = $order->getTax( true );
		$amount          = pmpro_round_price( (float) $order->subtotal + (float) $tax );

		//create a customer
		$customer = $this->update_customer_at_checkout( $order );
		if ( empty( $customer ) ) {
			//failed to create customer
			return false;
		}

		//charge
		try {
			$params = array(
					"amount"      => $this->convert_price_to_unit_amount( $amount ), # amount in cents, again
					"currency"    => strtolower( $pmpro_currency ),
					"customer"    => $customer->id,
					"description" => apply_filters( 'pmpro_stripe_order_description', "Order #" . $order->code . ", " . trim( $order->FirstName . " " . $order->LastName ) . " (" . $order->Email . ")", $order )
				);
			$params   = self::add_application_fee_amount( $params  );
			/**
			 * Filter params used to create the Stripe charge.
			 *
			 * @since 2.4.4
			 *
		 	 * @param array  $params 	Array of params sent to Stripe.
			 * @param object $order		Order object for this checkout.
			 */
			$params = apply_filters( 'pmpro_stripe_charge_params', $params, $order );
			$response = Stripe_Charge::create( $params );
		} catch ( \Throwable $e ) {
			//$order->status = "error";
			$order->errorcode  = true;
			$order->error      = "Error: " . $e->getMessage();
			$order->shorterror = $order->error;

			return false;
		} catch ( \Exception $e ) {
			//$order->status = "error";
			$order->errorcode  = true;
			$order->error      = "Error: " . $e->getMessage();
			$order->shorterror = $order->error;

			return false;
		}

		if ( empty( $response["failure_message"] ) ) {
			//successful charge
			$order->payment_transaction_id = $response["id"];
			$order->updateStatus( "success" );
			$order->saveOrder();

			return true;
		} else {
			//$order->status = "error";
			$order->errorcode  = true;
			$order->error      = $response['failure_message'];
			$order->shorterror = $response['failure_message'];

			return false;
		}
	}

	/**
	 * Get a Stripe Customer object and update it.
	 *
	 * @since 1.4
	 * @deprecated 2.7.0. Use get_customer_for_user() or update_customer_from_user().
	 *
	 * @return Stripe_Customer|false
	 */
	public function getCustomer( &$order = false, $force = false ) {
		_deprecated_function( __FUNCTION__, '2.7.0', 'update_customer_from_user()' );
		return $this->update_customer_at_checkout( $order );
	}

	/**
	 * Get a Stripe subscription from a PMPro order
	 *
	 * @since 1.8
	 * @deprecated 2.7.0. Need to write replacement methods for this.
	 */
	public function getSubscription( &$order ) {
		_deprecated_function( __FUNCTION__, '2.7.0' );
		global $wpdb;

		//no order?
		if ( empty( $order ) || empty( $order->code ) ) {
			return false;
		}

		$customer = $this->update_customer_at_checkout( $order, true );    //force so we don't get a cached sub for someone else

		//no customer?
		if ( empty( $customer ) ) {
			return false;
		}

		//no subscriptions?
		if ( empty( $customer->subscriptions ) ) {
			return false;
		}

		//is there a subscription transaction id pointing to a sub?
		if ( ! empty( $order->subscription_transaction_id ) && strpos( $order->subscription_transaction_id, "sub_" ) !== false ) {
			try {
				$sub = $customer->subscriptions->retrieve( $order->subscription_transaction_id );
			} catch ( \Throwable $e ) {
				$order->error      = __( "Error getting subscription with Stripe:", 'paid-memberships-pro' ) . $e->getMessage();
				$order->shorterror = $order->error;

				return false;
			} catch ( \Exception $e ) {
				$order->error      = __( "Error getting subscription with Stripe:", 'paid-memberships-pro' ) . $e->getMessage();
				$order->shorterror = $order->error;

				return false;
			}

			return $sub;
		}

		//find subscription based on customer id and order/plan id
		$subscriptions = $customer->subscriptions->all();

		//no subscriptions
		if ( empty( $subscriptions ) || empty( $subscriptions->data ) ) {
			return false;
		}

		//we really want to test against the order codes of all orders with the same subscription_transaction_id (customer id)
		$codes = $wpdb->get_col( "SELECT code FROM $wpdb->pmpro_membership_orders WHERE user_id = '" . esc_sql( $order->user_id ) . "' AND subscription_transaction_id = '" . esc_sql( $order->subscription_transaction_id ) . "' AND status NOT IN('refunded', 'review', 'token', 'error')" );

		//find the one for this order
		foreach ( $subscriptions->data as $sub ) {
			if ( in_array( $sub->plan->id, $codes ) ) {
				return $sub;
			}
		}

		//didn't find anything yet
		return false;
	}

	/**
	 * Create a new subscription with Stripe.
	 *
	 * This function is not run as a part of the PMPro Checkout Process.
	 * See method create_setup_intent().
	 *
	 * @since 1.4
	 * @deprecated 2.7.0. Use process_subscriptions() instead.
	 */
	public function subscribe( &$order, $checkout = true ) {
		_deprecated_function( __FUNCTION__, '2.7.0' );
		global $pmpro_currency;

		//create a code for the order
		if ( empty( $order->code ) ) {
			$order->code = $order->getRandomCode();
		}

		//filter order before subscription. use with care.
		$order = apply_filters( "pmpro_subscribe_order", $order, $this );

		//figure out the user
		if ( ! empty( $order->user_id ) ) {
			$user_id = $order->user_id;
		} else {
			global $current_user;
			$user_id = $current_user->ID;
		}

		//set up customer

		$result = $this->update_customer_at_checkout( $order );
		if ( empty( $result ) ) {
			return false;    //error retrieving customer
		}
		$order->stripe_customer = $result;

		// set subscription id to custom id

		$order->subscription_transaction_id = $order->stripe_customer['id'];    //transaction id is the customer id, we save it in user meta later too

		//figure out the amounts
		$amount     = $order->PaymentAmount;
		$amount_tax = $order->getTaxForPrice( $amount );
		$amount     = pmpro_round_price( (float) $amount + (float) $amount_tax );

		$trial_period_days = $this->calculate_trial_period_days( $order );

		// Save $trial_period_days to order for now too.
		$order->TrialPeriodDays = $trial_period_days;

		//create a plan
		try {
			$plan = array(
				"amount"                 => $this->convert_price_to_unit_amount( $amount ),
				"interval_count"         => $order->BillingFrequency,
				"interval"               => strtolower( $order->BillingPeriod ),
				"trial_period_days"      => $trial_period_days,
				'product'                => array( 'name' => $order->membership_name . " for order " . $order->code ),
				"currency"               => strtolower( $pmpro_currency ),
				"id"                     => $order->code
			);
			$plan = self::add_application_fee_amount( $plan );
			$plan = Stripe_Plan::create( apply_filters( 'pmpro_stripe_create_plan_array', $plan ) );
		} catch ( \Throwable $e ) {
			$order->error      = __( "Error creating plan with Stripe:", 'paid-memberships-pro' ) . $e->getMessage();
			$order->shorterror = $order->error;

			return false;
		} catch ( \Exception $e ) {
			$order->error      = __( "Error creating plan with Stripe:", 'paid-memberships-pro' ) . $e->getMessage();
			$order->shorterror = $order->error;

			return false;
		}

		// before subscribing, let's clear out the updates so we don't trigger any during sub
		if ( ! empty( $user_id ) ) {
			$old_user_updates = get_user_meta( $user_id, "pmpro_stripe_updates", true );
			update_user_meta( $user_id, "pmpro_stripe_updates", array() );
		}


		if ( empty( $order->subscription_transaction_id ) && ! empty( $order->stripe_customer['id'] ) ) {
			$order->subscription_transaction_id = $order->stripe_customer['id'];
		}

		// subscribe to the plan
		try {
			$subscription = array( "plan" => $order->code );
			$result       = $this->create_subscription( $order );
		} catch ( \Throwable $e ) {
			//try to delete the plan
			$plan->delete();

			//give the user any old updates back
			if ( ! empty( $user_id ) ) {
				update_user_meta( $user_id, "pmpro_stripe_updates", $old_user_updates );
			}

			//return error
			$order->error      = __( "Error subscribing customer to plan with Stripe:", 'paid-memberships-pro' ) . $e->getMessage();
			$order->shorterror = $order->error;

			return false;
		} catch ( \Exception $e ) {
			//try to delete the plan
			$plan->delete();

			//give the user any old updates back
			if ( ! empty( $user_id ) ) {
				update_user_meta( $user_id, "pmpro_stripe_updates", $old_user_updates );
			}

			//return error
			$order->error      = __( "Error subscribing customer to plan with Stripe:", 'paid-memberships-pro' ) . $e->getMessage();
			$order->shorterror = $order->error;

			return false;
		}

		// delete the plan
		$plan = Stripe_Plan::retrieve( $order->code );
		$plan->delete();

		//if we got this far, we're all good
		$order->status                      = "success";
		$order->subscription_transaction_id = $result['id'];

		//save new updates if this is at checkout
		if ( $checkout ) {
			//empty out updates unless set above
			if ( empty( $new_user_updates ) ) {
				$new_user_updates = array();
			}

			//update user meta
			if ( ! empty( $user_id ) ) {
				update_user_meta( $user_id, "pmpro_stripe_updates", $new_user_updates );
			} else {
				//need to remember the user updates to save later
				global $pmpro_stripe_updates;
				$pmpro_stripe_updates = $new_user_updates;
				function pmpro_user_register_stripe_updates( $user_id ) {
					global $pmpro_stripe_updates;
					update_user_meta( $user_id, "pmpro_stripe_updates", $pmpro_stripe_updates );
				}

				add_action( "user_register", "pmpro_user_register_stripe_updates" );
			}
		} else {
			//give them their old updates back
			update_user_meta( $user_id, "pmpro_stripe_updates", $old_user_updates );
		}

		return true;
	}

	/**
	 * Refund a payment or invoice
	 *
	 * @deprecated 2.7.0.
	 *
	 * @param object &$order Related PMPro order object.
	 * @param string $transaction_id Payment or Invoice id to void.
	 *
	 * @return bool                     True or false if the void worked
	 */
	public function void( &$order, $transaction_id = null ) {
		_deprecated_function( __FUNCTION__, '2.7.0' );
		//stripe doesn't differentiate between voids and refunds, so let's just pass on to the refund function
		return $this->refund( $order, $transaction_id );
	}

	/**
	 * Refunds an order (only supports full amounts)
	 *
	 * @param bool    $success Status of the refund (default: false)
	 * @param object  $order The Member Order Object
	 * @since 2.8
	 * 
	 * @return bool   Status of the processed refund
	 */
	public static function process_refund( $success, $order ) {
		global $current_user;

		//default to using the payment id from the order
		if ( !empty( $order->payment_transaction_id ) ) {
			$transaction_id = $order->payment_transaction_id;
		}

		//need a transaction id
		if ( empty( $transaction_id ) ) {
			return false;
		}

		//if an invoice ID is passed, get the charge/payment id
		if ( strpos( $transaction_id, "in_" ) !== false ) {
			$invoice = Stripe_Invoice::retrieve( $transaction_id );

			if ( ! empty( $invoice ) && ! empty( $invoice->charge ) ) {
				$transaction_id = $invoice->charge;
			}
		}

		//attempt refund
		try {
			
			$secretkey = pmpro_getOption( 'stripe_secretkey' );

			// If they are not using legacy keys, get Stripe Connect keys for the relevant environment.
			if ( ! self::using_legacy_keys() && empty( $secretkey ) ) {
				if ( pmpro_getOption( 'gateway_environment' ) === 'live' ) {
					$secretkey = pmpro_getOption( 'live_stripe_connect_secretkey' );
				} else {
					$secretkey = pmpro_getOption( 'sandbox_stripe_connect_secretkey' );
				}
			} 

			$client = new Stripe_Client( $secretkey );
			$refund = $client->refunds->create( [
				'charge' => $transaction_id,
			] );			

			//Make sure we're refunding an order that was successful
			if ( $refund->status != 'failed' ) {
				$order->status = 'refunded';	
				$order->notes = trim( $order->notes.' '.sprintf( __('Admin: Order successfully refunded on %1$s for transaction ID %2$s by %3$s.', 'paid-memberships-pro' ), date_i18n('Y-m-d H:i:s'), $transaction_id, $current_user->display_name ) );
				$order->saveOrder();

				$user = get_user_by( 'id', $order->user_id );
				//send an email to the member
				$myemail = new PMProEmail();
				$myemail->sendRefundedEmail( $user, $order );

				//send an email to the admin
				$myemail = new PMProEmail();
				$myemail->sendRefundedAdminEmail( $user, $order );

				return true;
			}

			// Refund failed.
			$order->notes = trim( $order->notes . ' ' . __('Admin: An error occured while attempting to process this refund.', 'paid-memberships-pro' ) );
		} catch ( \Throwable $e ) {			
			$order->notes = trim( $order->notes . ' ' . __( 'Admin: There was a problem processing the refund', 'paid-memberships-pro' ) . ' ' . $e->getMessage() );	
		} catch ( \Exception $e ) {
			$order->notes = trim( $order->notes . ' ' . __( 'Admin: There was a problem processing the refund', 'paid-memberships-pro' ) . ' ' . $e->getMessage() );
		}
		$order->saveOrder();
		return false;
	}

	/**
	 * Refund a payment or invoice
	 *
	 * @deprecated 2.7.0.
	 *
	 * @param object &$order Related PMPro order object.
	 * @param string $transaction_id Payment or invoice id to void.
	 *
	 * @return bool                   True or false if the refund worked.
	 */
	public function refund( &$order, $transaction_id = null ) {
		_deprecated_function( __FUNCTION__, '2.7.0' );
		//default to using the payment id from the order
		if ( empty( $transaction_id ) && ! empty( $order->payment_transaction_id ) ) {
			$transaction_id = $order->payment_transaction_id;
		}

		//need a transaction id
		if ( empty( $transaction_id ) ) {
			return false;
		}

		//if an invoice ID is passed, get the charge/payment id
		if ( strpos( $transaction_id, "in_" ) !== false ) {
			$invoice = Stripe_Invoice::retrieve( $transaction_id );

			if ( ! empty( $invoice ) && ! empty( $invoice->charge ) ) {
				$transaction_id = $invoice->charge;
			}
		}

		//get the charge
		try {
			$charge = Stripe_Charge::retrieve( $transaction_id );
		} catch ( \Throwable $e ) {
			$charge = false;
		} catch ( \Exception $e ) {
			$charge = false;
		}

		//can't find the charge?
		if ( empty( $charge ) ) {
			$order->status     = "error";
			$order->errorcode  = "";
			$order->error      = "";
			$order->shorterror = "";

			return false;
		}

		//attempt refund
		try {
			$refund = $charge->refund();
		} catch ( \Throwable $e ) {
			$order->errorcode  = true;
			$order->error      = __( "Error: ", 'paid-memberships-pro' ) . $e->getMessage();
			$order->shorterror = $order->error;

			return false;
		} catch ( \Exception $e ) {
			$order->errorcode  = true;
			$order->error      = __( "Error: ", 'paid-memberships-pro' ) . $e->getMessage();
			$order->shorterror = $order->error;

			return false;
		}

		if ( $refund->status == "succeeded" ) {
			$order->status = "refunded";
			$order->saveOrder();

			return true;
		} else {
			$order->status     = "error";
			$order->errorcode  = true;
			$order->error      = sprintf( __( "Error: Unkown error while refunding charge #%s", 'paid-memberships-pro' ), $transaction_id );
			$order->shorterror = $order->error;

			return false;
		}
	}

	/**
	 * @deprecated 2.7.0. Use get_payment_method() instead.
	 */
	public function set_payment_method( &$order, $force = false ) {
		_deprecated_function( __FUNCTION__, '2.7.0', 'get_payment_method' );
		if ( ! empty( $this->payment_method ) && ! $force ) {
			return true;
		}

		$payment_method = $this->get_payment_method( $order );

		if ( empty( $payment_method ) ) {
			return false;
		}

		$this->payment_method = $payment_method;

		return true;
	}

	/**
	 * @deprecated 2.7.0. Use get_customer_for_user() or update_customer_from_user().
	 */
	public function set_customer( &$order, $force = false ) {
		_deprecated_function( __FUNCTION__, '2.7.0', 'get_customer_for_user()' );
		if ( ! empty( $this->customer ) && ! $force ) {
			return true;
		}
		// Temporarily setting this, will be removed when this function is deprecated.
		$this->customer = $this->update_customer_at_checkout( $order );
		return $this->customer;
	}

	/**
	 * @deprecated 2.7.0. Use set_default_payment_method_for_customer().
	 */
	public function attach_payment_method_to_customer( &$order ) {
		_deprecated_function( __FUNCTION__, '2.7.0' );
		$customer = $this->update_customer_at_checkout( $order );

		if ( ! empty( $customer->invoice_settings->default_payment_method ) &&
             $customer->invoice_settings->default_payment_method === $this->payment_method->id ) {
			return true;
		}

		try {
			$this->payment_method->attach( [ 'customer' => $customer->id ] );
			$customer->invoice_settings->default_payment_method = $this->payment_method->id;
			$customer->save();
		} catch ( Stripe\Error\Base $e ) {
			$order->error = $e->getMessage();
			return false;
		} catch ( \Throwable $e ) {
			$order->error = $e->getMessage();
			return false;
		} catch ( \Exception $e ) {
			$order->error = $e->getMessage();
			return false;
		}

		return true;
	}

	/**
	 * @deprecated 2.7.0. Use get_payment_intent() instead.
	 */
	public function set_payment_intent( &$order, $force = false ) {
		_deprecated_function( __FUNCTION__, '2.7.0', 'get_payment_intent()' );
		if ( ! empty( $order->stripe_payment_intent ) && ! $force ) {
			return true;
		}

		$payment_intent = $this->get_payment_intent( $order );

		if ( empty( $payment_intent ) ) {
			return false;
		}

		$this->payment_intent = $payment_intent;

		return true;
	}

	/**
	 * Only called during subscription updates. Should be completely deprecated once that functionality is removed.
	 *
	 * @deprecated 2.7.0. Will only be deprecated once we are using Prices.
	 */
	public function create_plan( &$order ) {
		// _deprecated_function( __FUNCTION__, '2.7.0' );
		global $pmpro_currency;

		//figure out the amounts
		$amount     = $order->PaymentAmount;
		$amount_tax = $order->getTaxForPrice( $amount );
		$amount     = pmpro_round_price( (float) $amount + (float) $amount_tax );


		$trial_period_days = $this->calculate_trial_period_days( $order );
		// Save $trial_period_days to order for now too.
		$order->TrialPeriodDays = $trial_period_days;

		//create a plan
		try {
			$plan = array(
				"amount"                 => $this->convert_price_to_unit_amount( $amount ),
				"interval_count"         => $order->BillingFrequency,
				"interval"               => strtolower( $order->BillingPeriod ),
				"trial_period_days"      => $trial_period_days,
				'product'                => array( 'name' => $order->membership_name . " for order " . $order->code ),
				"currency"               => strtolower( $pmpro_currency ),
				"id"                     => $order->code
			);
			$order->plan = Stripe_Plan::create( apply_filters( 'pmpro_stripe_create_plan_array', $plan ) );
		} catch ( Stripe\Error\Base $e ) {
			$order->error = $e->getMessage();

			return false;
		} catch ( \Throwable $e ) {
			$order->error = $e->getMessage();

			return false;
		} catch ( \Exception $e ) {
			$order->error = $e->getMessage();

			return false;
		}

		return $order->plan;
	}

	/**
	 * Determine whether the webhook is working by checking for Stripe orders with invalid transaction IDs.
	 *
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 * @deprecated 2.10. Now fully deprecated.
	 *
	 * @param string|null $gateway_environment to check webhooks for. Defaults to set gateway environment.
	 * @return bool Whether the webhook is working.
	 */
	public static function webhook_is_working( $gateway_environment = null ) {
		_deprecated_function( __FUNCTION__, '2.10' );
		global $wpdb;

		if ( empty( $gateway_environment ) ) {
			$gateway_environment = pmpro_getOption( 'pmpro_gateway_environment' );
		}

		$last_webhook = get_option( 'pmpro_stripe_last_webhook_received_' . $gateway_environment );

		if ( empty( $last_webhook ) ) {
			// Probably never got a webhook event.
			$last_webhook_safe = date( 'Y-m-d H:i:s', strtotime( '-5 years' ) );
		} else {
			// In case recurring order made after webhook event received.
			$last_webhook_safe  = date( 'Y-m-d H:i:s', strtotime( $last_webhook . ' +5 minutes' ) );
		}

		$hour_before_now = date( 'Y-m-d H:i:s', strtotime( '-1 hour' ) );

		$num_problem_orders = $wpdb->get_var(
			$wpdb->prepare(
				"
					SELECT COUNT(*)
					FROM `{$wpdb->pmpro_membership_orders}`
					WHERE
						`gateway` = 'stripe'
						AND `gateway_environment` = %s
						AND `subscription_transaction_id` <> '' 
						AND `subscription_transaction_id` IS NOT NULL
						AND `timestamp` > %s
						AND `timestamp` < %s
				",
				$gateway_environment,
				$last_webhook_safe,
				$hour_before_now
			)
		);

		return ( empty( $num_problem_orders ) );
	}

	/**
	 * Get the date the last webhook was processed.
	 * @param environment The gateway environment (live or sandbox) to check for.
	 * @returns HTML with the date of the last webhook or an error message.
	 * @since 2.6
	 * @deprecated 2.7.0. Only deprecated for public use, will be changed to private non-static in a future version.
	 * @deprecated 2.10. Now fully deprecated.
	 */
	public static function get_last_webhook_date( $environment = 'live' ) {
		_deprecated_function( __FUNCTION__, '2.10' );
		$last_webhook = get_option( 'pmpro_stripe_last_webhook_received_' . $environment );
		if ( ! empty( $last_webhook ) ) {
			echo '<p>' . esc_html__( 'Last webhook received at', 'paid-memberships-pro' ) . ': ' . esc_html( $last_webhook ) . ' GMT.</p>';
		} else {
			echo '<p>' . esc_html__( 'No webhooks have been received.', 'paid-memberships-pro' ) . '</p>';
		}
		if ( ! self::webhook_is_working( $environment ) ) {
			echo '<div class="notice error inline"><p>';
			echo esc_html__( 'Your webhook may not be working correctly.', 'paid-memberships-pro' );
			echo ' <a target="_blank" href="https://www.paidmembershipspro.com/gateway/stripe/setup/?utm_source=plugin&utm_medium=pmpro-paymentsettings&utm_campaign=gateways&utm_content=stripe-webhook#webhook">';
			echo esc_html__( 'Click here for info on setting up your webhook with Stripe.', 'paid-memberships-pro' );
			echo '</a>';
			echo '</p></div>';
		}
	}
}
