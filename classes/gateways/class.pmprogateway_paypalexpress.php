<?php
	//include pmprogateway
	require_once(dirname(__FILE__) . "/class.pmprogateway.php");

	//load classes init method
	add_action('init', array('PMProGateway_paypalexpress', 'init'));

	class PMProGateway_paypalexpress extends PMProGateway
	{
		/** @var int Maximum number of request retries */
		protected static $maxNetworkRetries = 5;

		/** @var float Maximum delay between retries, in seconds */
		protected static $maxNetworkRetryDelay = 2.0;

		/** @var float Initial delay between retries, in seconds */
		protected static $initialNetworkRetryDelay = 0.5;


		function __construct($gateway = NULL)
		{
			$this->gateway = $gateway;
			return $this->gateway;
		}

		/**
		 * Run on WP init
		 *
		 * @since 1.8
		 */
		static function init()
		{
			//make sure PayPal Express is a gateway option
			add_filter('pmpro_gateways', array('PMProGateway_paypalexpress', 'pmpro_gateways'));

			//code to add at checkout
			$gateway = pmpro_getGateway();
			if($gateway == "paypalexpress")
			{
				add_filter('pmpro_include_billing_address_fields', '__return_false');
				add_filter('pmpro_include_payment_information_fields', '__return_false');
				add_filter('pmpro_required_billing_fields', array('PMProGateway_paypalexpress', 'pmpro_required_billing_fields'));
				add_filter('pmpro_checkout_default_submit_button', array('PMProGateway_paypalexpress', 'pmpro_checkout_default_submit_button'));
				add_action('http_api_curl', array('PMProGateway_paypalexpress', 'http_api_curl'), 10, 3);				
			}
            add_action('pmpro_checkout_preheader', array('PMProGateway_paypalexpress', 'pmpro_checkout_preheader'));
			add_filter( 'pmpro_process_refund_paypalexpress', array('PMProGateway_paypalexpress', 'process_refund' ), 10, 2 );
		}

		/**
		 * Update the SSLVERSION for CURL to support PayPal Express moving to TLS 1.2
		 *
		 * @since 1.8.9.1
		 */
		static function http_api_curl($handle, $r, $url) {
			if(strpos($url, 'paypal.com') !== false)
				curl_setopt( $handle, CURLOPT_SSLVERSION, 6 );
		}

		/**
		 * Make sure this gateway is in the gateways list
		 *
		 * @since 1.8
		 */
		static function pmpro_gateways($gateways)
		{
			if(empty($gateways['paypalexpress']))
				$gateways['paypalexpress'] = __('PayPal Express', 'paid-memberships-pro' );

			return $gateways;
		}

		/**
		 * Check whether or not a gateway supports a specific feature.
		 * 
		 * @since 3.0
		 * 
		 * @return string|boolean $supports Returns whether or not the gateway supports the requested feature.
		 */
		public static function supports( $feature ) {
			$supports = array(
				'subscription_sync' => true,
				'payment_method_updates' => false,
			);

			if ( empty( $supports[$feature] ) ) {
				return false;
			}

			return $supports[$feature];
		}

		/**
		 * Get a list of payment options that the this gateway needs/supports.
		 *
		 * @since 1.8
		 * @deprecated TBD
		 */
		static function getGatewayOptions()
		{
			_deprecated_function( __METHOD__, 'TBD' );
			$options = array(
				'gateway_environment',
				'gateway_email',
				'apiusername',
				'apipassword',
				'apisignature',
				'currency',
				'tax_state',
				'tax_rate',
				'paypalexpress_skip_confirmation',
			);

			return $options;
		}

		/**
		 * Set payment options for payment settings page.
		 *
		 * @since 1.8
		 * @deprecated TBD
		 */
		static function pmpro_payment_options($options)
		{
			_deprecated_function( __METHOD__, 'TBD' );
			//get options
			$paypal_options = PMProGateway_paypalexpress::getGatewayOptions();

			//merge with others.
			$options = array_merge($paypal_options, $options);

			return $options;
		}

		/**
		 * Display fields for this gateway's options.
		 *
		 * @since 1.8
		 * @deprecated TBD
		 */
		static function pmpro_payment_option_fields($values, $gateway)
		{
			_deprecated_function( __METHOD__, 'TBD' );
		?>
		<tr class="pmpro_settings_divider gateway gateway_paypal gateway_paypalexpress gateway_paypalstandard" <?php if($gateway != "paypal" && $gateway != "paypalexpress" && $gateway != "paypalstandard") { ?>style="display: none;"<?php } ?>>
			<td colspan="2">
				<hr />
				<h2 class="title"><?php esc_html_e( 'PayPal Settings', 'paid-memberships-pro' ); ?></h2>
			</td>
		</tr>
		<tr class="gateway gateway_paypalstandard" <?php if($gateway != "paypalstandard") { ?>style="display: none;"<?php } ?>>
			<td colspan="2" style="padding: 0px;">
				<div class="notice error inline">
					<p>
					<?php
						$allowed_message_html = array (
							'a' => array (
								'href' => array(),
								'target' => array(),
								'title' => array(),
							),
						);
						echo sprintf( wp_kses( __( 'Note: We do not recommend using PayPal Standard. We suggest using PayPal Express, Website Payments Pro (Legacy), or PayPal Pro (Payflow Pro). <a target="_blank" href="%s" title="More information on why can be found here">More information on why can be found here</a>.', 'paid-memberships-pro' ), $allowed_message_html ), 'https://www.paidmembershipspro.com/read-using-paypal-standard-paid-memberships-pro/?utm_source=plugin&utm_medium=pmpro-paymentsettings&utm_campaign=blog&utm_content=read-using-paypal-standard-paid-memberships-pro' );
					?>
					</p>
				</div>
			</td>
		</tr>
		<tr class="gateway gateway_paypal gateway_paypalexpress gateway_paypalstandard" <?php if($gateway != "paypal" && $gateway != "paypalexpress" && $gateway != "paypalstandard") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="gateway_email"><?php esc_html_e('Gateway Account Email', 'paid-memberships-pro' );?></label>
			</th>
			<td>
				<input type="text" id="gateway_email" name="gateway_email" value="<?php echo esc_attr($values['gateway_email'])?>" class="regular-text code" />
			</td>
		</tr>
		<tr class="gateway gateway_paypal gateway_paypalexpress" <?php if($gateway != "paypal" && $gateway != "paypalexpress") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="apiusername"><?php esc_html_e('API Username', 'paid-memberships-pro' );?></label>
			</th>
			<td>
				<input type="text" id="apiusername" name="apiusername" value="<?php echo esc_attr($values['apiusername'])?>" class="regular-text code" />
			</td>
		</tr>
		<tr class="gateway gateway_paypal gateway_paypalexpress" <?php if($gateway != "paypal" && $gateway != "paypalexpress") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="apipassword"><?php esc_html_e('API Password', 'paid-memberships-pro' );?></label>
			</th>
			<td>
				<input type="text" id="apipassword" name="apipassword" value="<?php echo esc_attr($values['apipassword'])?>" autocomplete="off" class="regular-text code pmpro-admin-secure-key" />
			</td>
		</tr>
		<tr class="gateway gateway_paypal gateway_paypalexpress" <?php if($gateway != "paypal" && $gateway != "paypalexpress") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="apisignature"><?php esc_html_e('API Signature', 'paid-memberships-pro' );?></label>
			</th>
			<td>
				<input type="text" id="apisignature" name="apisignature" value="<?php echo esc_attr($values['apisignature'])?>" class="regular-text code" />
			</td>
		</tr>
		<tr class="gateway gateway_paypal gateway_paypalexpress" <?php if($gateway != "paypal" && $gateway != "paypalexpress") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="paypalexpress_skip_confirmation"><?php esc_html_e('Confirmation Step', 'paid-memberships-pro' );?></label>
			</th>
			<td>
				<select id="paypalexpress_skip_confirmation" name="paypalexpress_skip_confirmation">
					<option value="0" <?php selected(get_option('pmpro_paypalexpress_skip_confirmation'), 0);?>><?php esc_html_e( 'Require an extra confirmation after users return from PayPal.', 'paid-memberships-pro' ) ?></option>
					<option value="1" <?php selected(get_option('pmpro_paypalexpress_skip_confirmation'), 1);?>><?php esc_html_e( 'Skip the extra confirmation after users return from PayPal.', 'paid-memberships-pro' ) ?></option>
				</select>
			</td>
		</tr>
		<tr class="gateway gateway_paypal gateway_paypalexpress gateway_paypalstandard" <?php if($gateway != "paypal" && $gateway != "paypalexpress" && $gateway != "paypalstandard") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label><?php esc_html_e('IPN Handler URL', 'paid-memberships-pro' );?></label>
			</th>
			<td>
				<p class="description"><?php esc_html_e('To fully integrate with PayPal, be sure to set your IPN Handler URL to ', 'paid-memberships-pro' );?></p>
				<p><code><?php echo esc_html( add_query_arg( 'action', 'ipnhandler', admin_url('admin-ajax.php') ) );?></code></p>
			</td>
		</tr>
		<?php
		}

		/**
		 * Display fields for PayPal options.
		 *
		 * @since TBD
		 */
		public static function show_settings_fields() {
			?>
			<div id="pmpro_paypal" class="pmpro_section" data-visibility="shown" data-activated="true">
				<div class="pmpro_section_toggle">
					<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
						<span class="dashicons dashicons-arrow-up-alt2"></span>
						<?php esc_html_e( 'Settings', 'paid-memberships-pro' ); ?>
					</button>
				</div>
				<div class="pmpro_section_inside">
					<table class="form-table">
						<tbody>
							<tr class="gateway gateway_paypal gateway_paypalexpress gateway_paypalstandard">
								<th scope="row" valign="top">
									<label for="gateway_email"><?php esc_html_e('Gateway Account Email', 'paid-memberships-pro' );?></label>
								</th>
								<td>
									<input type="text" id="gateway_email" name="gateway_email" value="<?php echo esc_attr( get_option( 'pmpro_gateway_email' ) ); ?>" class="regular-text code" />
								</td>
							</tr>
							<tr class="gateway gateway_paypal gateway_paypalexpress">
								<th scope="row" valign="top">
									<label for="apiusername"><?php esc_html_e('API Username', 'paid-memberships-pro' );?></label>
								</th>
								<td>
									<input type="text" id="apiusername" name="apiusername" value="<?php echo esc_attr( get_option( 'pmpro_apiusername' ) ); ?>" class="regular-text code" />
								</td>
							</tr>
							<tr class="gateway gateway_paypal gateway_paypalexpress">
								<th scope="row" valign="top">
									<label for="apipassword"><?php esc_html_e('API Password', 'paid-memberships-pro' );?></label>
								</th>
								<td>
									<input type="text" id="apipassword" name="apipassword" value="<?php echo esc_attr( get_option( 'pmpro_apipassword' ) ); ?>" autocomplete="off" class="regular-text code pmpro-admin-secure-key" />
								</td>
							</tr>
							<tr class="gateway gateway_paypal gateway_paypalexpress">
								<th scope="row" valign="top">
									<label for="apisignature"><?php esc_html_e('API Signature', 'paid-memberships-pro' );?></label>
								</th>
								<td>
									<input type="text" id="apisignature" name="apisignature" value="<?php echo esc_attr( get_option( 'pmpro_apisignature' ) ); ?>" class="regular-text code" />
								</td>
							</tr>
							<tr class="gateway gateway_paypal gateway_paypalexpress">
								<th scope="row" valign="top">
									<label for="paypalexpress_skip_confirmation"><?php esc_html_e('Confirmation Step', 'paid-memberships-pro' );?></label>
								</th>
								<td>
									<select id="paypalexpress_skip_confirmation" name="paypalexpress_skip_confirmation">
										<option value="0" <?php selected( get_option('pmpro_paypalexpress_skip_confirmation'), 0 );?>><?php esc_html_e( 'Require an extra confirmation after users return from PayPal.', 'paid-memberships-pro' ) ?></option>
										<option value="1" <?php selected( get_option('pmpro_paypalexpress_skip_confirmation'), 1 );?>><?php esc_html_e( 'Skip the extra confirmation after users return from PayPal.', 'paid-memberships-pro' ) ?></option>
									</select>
								</td>
							</tr>
							<tr class="gateway gateway_paypal gateway_paypalexpress gateway_paypalstandard">
								<th scope="row" valign="top">
									<label><?php esc_html_e('IPN Handler URL', 'paid-memberships-pro' );?></label>
								</th>
								<td>
									<p class="description"><?php esc_html_e('To fully integrate with PayPal, be sure to set your IPN Handler URL to ', 'paid-memberships-pro' );?></p>
									<p><code><?php echo esc_html( add_query_arg( 'action', 'ipnhandler', admin_url('admin-ajax.php') ) );?></code></p>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<?php
		}

		/**
		 * Save settings for PayPal.
		 *
		 * @since TBD
		 */
		public static function save_settings_fields() {
			$settings_to_save = array(
				'gateway_email',
				'apiusername',
				'apipassword',
				'apisignature',
				'paypalexpress_skip_confirmation'
			);

			foreach ( $settings_to_save as $setting ) {
				if ( isset( $_REQUEST[ $setting ] ) ) {
					update_option( 'pmpro_' . $setting, sanitize_text_field( $_REQUEST[ $setting ] ) );
				}
			}
		}

		/**
		 * Remove required billing fields
		 *
		 * @since 1.8
		 */
		static function pmpro_required_billing_fields($fields)
		{
			unset($fields['bfirstname']);
			unset($fields['blastname']);
			unset($fields['baddress1']);
			unset($fields['bcity']);
			unset($fields['bstate']);
			unset($fields['bzipcode']);
			unset($fields['bphone']);
			unset($fields['bemail']);
			unset($fields['bcountry']);
			unset($fields['CardType']);
			unset($fields['AccountNumber']);
			unset($fields['ExpirationMonth']);
			unset($fields['ExpirationYear']);
			unset($fields['CVV']);

			return $fields;
		}

		/**
		 * Code added to checkout preheader.
		 *
		 * @since 2.1
		 */
		static function pmpro_checkout_preheader() {
			global $gateway, $pmpro_level, $pmpro_review;

			// Check if the we already have an order that is being paid with PayPal Express. If not, bail.
			if ( empty( $pmpro_review ) || ! is_a( $pmpro_review, 'MemberOrder' ) || $pmpro_review->gateway !== 'paypalexpress') {
				return;
			}

			// If we are completing checkout immediately, make sure we immediately submit the checkout form with a valid nonce.
			if ( ! empty( get_option('pmpro_paypalexpress_skip_confirmation') ) ) {
				$_REQUEST['submit-checkout'] = 1;
				$_REQUEST['pmpro_checkout_nonce'] = wp_create_nonce( 'pmpro_checkout_nonce' );
			}

			// Set some globals for compatibility with pre-3.2 checkout page templates.
			global $pmpro_paypal_token;
			$pmpro_paypal_token = $pmpro_review->paypal_token;

			// For backwards compatibility with pre-3.2 checkout page templates, also check if the $_REQUEST['confirm'] attribute is set.
			// If so, we want to process the chekcout form submission.
			if ( ! empty( $_REQUEST['confirm'] ) ) {
				// Process the checkout form submission.
				$_REQUEST['submit-checkout'] = 1;
			}
		}

		/**

		 * Save session vars before processing
		 *
		 * @since 1.8
		 * @deprecated 2.12.3
		 */
		static function pmpro_checkout_before_processing() {
			global $current_user, $gateway;

			_deprecated_function( __FUNCTION__, '2.12.3' );

			//save user fields for PayPal Express
			if(!$current_user->ID) {
				//get values from post
				if(isset($_REQUEST['username']))
					$username = trim(sanitize_text_field($_REQUEST['username']));
				else
					$username = "";
				if(isset($_REQUEST['password'])) {
					// Can't sanitize the password. Be careful.
					$password = $_REQUEST['password']; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				} else {
					$password = "";
				}
				if(isset($_REQUEST['bemail']))
					$bemail = sanitize_email($_REQUEST['bemail']);
				else
					$bemail = "";

				//save to session
				$_SESSION['pmpro_signup_username'] = $username;
				$_SESSION['pmpro_signup_password'] = $password;
				$_SESSION['pmpro_signup_email'] = $bemail;
			}

			//can use this hook to save some other variables to the session
			// @deprecated 2.12.3
			do_action("pmpro_paypalexpress_session_vars");
		}

		/**
		 * Review and Confirmation code.
		 *
		 * @since 1.8
		 * @deprecated 3.2
		 */
		static function pmpro_checkout_confirmed($pmpro_confirmed)
		{
			_deprecated_function( __FUNCTION__, '3.2', 'PMProGateway_paypalexpress::process()' );
			global $pmpro_msg, $pmpro_msgt, $pmpro_level, $current_user, $pmpro_review, $pmpro_paypal_token, $discount_code, $bemail;

			//PayPal Express Call Backs
			if(!empty($_REQUEST['review']))
			{
				if(!empty($_REQUEST['PayerID']))
					$_SESSION['payer_id'] = sanitize_text_field($_REQUEST['PayerID']);
				if(!empty($_REQUEST['paymentAmount']))
					$_SESSION['paymentAmount'] = sanitize_text_field($_REQUEST['paymentAmount']);
				if(!empty($_REQUEST['currencyCodeType']))
					$_SESSION['currCodeType'] = sanitize_text_field($_REQUEST['currencyCodeType']);
				if(!empty($_REQUEST['paymentType']))
					$_SESSION['paymentType'] = sanitize_text_field($_REQUEST['paymentType']);

				$morder = new MemberOrder();
				$morder->getMemberOrderByPayPalToken(sanitize_text_field($_REQUEST['token']));

				// Pull checkout values from order meta.
				pmpro_pull_checkout_data_from_order( $morder );

				if( $morder->status === 'token' ){
					$morder->Token = $morder->paypal_token; $pmpro_paypal_token = $morder->paypal_token;
					if($morder->Token)
					{
						if($morder->Gateway->getExpressCheckoutDetails($morder))
						{
							$pmpro_review = true;
						}
						else
						{
							$pmpro_msg = $morder->error;
							$pmpro_msgt = "pmpro_error";
						}
					}
					else
					{
						$pmpro_msg = __("The PayPal Token was lost.", 'paid-memberships-pro' );
						$pmpro_msgt = "pmpro_error";
					}
				}else{
					$pmpro_msg = __("Checkout was already processed.", 'paid-memberships-pro' );
					$pmpro_msgt = "pmpro_error";
				}
			}

			if(empty($pmpro_msg) &&
				(!empty($_REQUEST['confirm']) ||
				(get_option('pmpro_paypalexpress_skip_confirmation') && $pmpro_review))
			)
			{
				$morder = new MemberOrder();
				$morder->getMemberOrderByPayPalToken(sanitize_text_field($_REQUEST['token']));
				$morder->Token = $morder->paypal_token; $pmpro_paypal_token = $morder->paypal_token;

				// Pull checkout values from order meta.
				pmpro_pull_checkout_data_from_order( $morder );

				if($morder->Token)
				{
					//set up values
					$morder->membership_id = $pmpro_level->id;
					$morder->membership_name = $pmpro_level->name;
					$morder->subtotal = pmpro_round_price( $pmpro_level->initial_payment );
					$morder->BillingFrequency = $pmpro_level->cycle_number;

					//setup level var
					$morder->getMembershipLevelAtCheckout();

					//tax
					$morder->getTax();

					if($morder->confirm())
					{
						$pmpro_confirmed = true;
					}
					else
					{
						$pmpro_msg = $morder->error;
						$pmpro_msgt = "pmpro_error";
					}
				}
				else
				{
					$pmpro_msg = __("The PayPal Token was lost.", 'paid-memberships-pro' );
					$pmpro_msgt = "pmpro_error";
				}
			}

			if(!empty($morder))
				return array("pmpro_confirmed"=>$pmpro_confirmed, "morder"=>$morder);
			else
				return $pmpro_confirmed;
		}

		/**
		 * Swap in user/pass/etc from session
		 *
		 * @since 1.8
		 * @deprecated 3.2
		 */
		static function pmpro_checkout_new_user_array($new_user_array)
		{
			_deprecated_function( __FUNCTION__, '3.2' );
			global $current_user;

			if(!$current_user->ID)
			{
				//reload the user fields
				if( ! empty( $_SESSION['pmpro_signup_username'] ) ){
					$new_user_array['user_login'] = $_SESSION['pmpro_signup_username'];
				}
				if( ! empty( $_SESSION['pmpro_signup_password'] ) ){
					$new_user_array['user_pass'] = $_SESSION['pmpro_signup_password'];
				}
				if( ! empty( $_SESSION['pmpro_signup_email'] ) ){
					$new_user_array['user_email'] = $_SESSION['pmpro_signup_email'];
				}

				//unset the user fields in session
				unset($_SESSION['pmpro_signup_username']);
				unset($_SESSION['pmpro_signup_password']);
				unset($_SESSION['pmpro_signup_email']);
			}

			return $new_user_array;
		}

		/**
		 * Process at checkout
		 *
		 * @since 2.0 - The old process() method is now confirm().
		 * @since 3.2 - This method now handles both sending users to PayPal and confirming checkouts.
		 */
		function process( &$order ) {
			// If the user has not yet been sent to PayPal, send them to pay.
			if ( empty( $order->paypal_token ) ) {
				// No. Send them to PayPal.
				$order->payment_type = "PayPal Express";
				$order->cardtype = "";

				return $this->setExpressCheckout($order);
			}

			// We know that the user had been sent to pay and has re-submitted the chekcout form to confirm.
			// Make sure the order is in `token` status.
			if ( $order->status !== 'token' ) {
				pmpro_setMessage( __("Checkout was already processed.", 'paid-memberships-pro' ), 'pmpro_error' );
				return false;
			}

			// Make sure we still have the PayPal token.
			if ( empty( $order->paypal_token ) ) {
				pmpro_setMessage( __( 'The PayPal Token was lost.', 'paid-memberships-pro' ), 'pmpro_error' );
				return false;
			}

			// Validate the PayPal Token.
			if ( ! $order->Gateway->getExpressCheckoutDetails($order) ) {
				pmpro_setMessage( $order->error, 'pmpro_error' );
				return false;
			}

			//set up values
			$pmpro_level = $order->getMembershipLevelAtCheckout();
			$user        = get_userdata( $order->user_id );
			$order->membership_id = $pmpro_level->id;
			$order->membership_name = $pmpro_level->name;
			$order->subtotal = pmpro_round_price( $pmpro_level->initial_payment );

			//setup level var
			$order->getMembershipLevelAtCheckout();

			//tax
			$order->getTax();

			if ( pmpro_isLevelRecurring( $order->membership_level ) ) {
				$success = $this->subscribe($order);
			} else {
				$success = $this->charge($order);
			}

			if ( ! $success ) {
				pmpro_setMessage( $order->error, 'pmpro_error' );
				return false;
			}

			return true;
		}

		/**
		 * Process charge or subscription after confirmation.
		 *
		 * @since 1.8
		 * @deprecated 3.2
		 */
		function confirm(&$order)
		{
			_deprecated_function( __FUNCTION__, '3.2', 'PMProGateway_paypalexpress::process()' );
			if(pmpro_isLevelRecurring($order->membership_level))
			{
				return $this->subscribe($order);
			}
			else
				return $this->charge($order);
		}

		/**
		 * Swap in our submit buttons.
		 *
		 * @since 1.8
		 */
		static function pmpro_checkout_default_submit_button($show)
		{
			global $gateway, $pmpro_requirebilling;

			//show our submit buttons
			?>
			<span id="pmpro_paypalexpress_checkout" <?php if(($gateway != "paypalexpress" && $gateway != "paypalstandard") || !$pmpro_requirebilling) { ?>style="display: none;"<?php } ?>>
				<input type="hidden" name="submit-checkout" value="1" />
				<button type="submit" id="pmpro_btn-submit-paypalexpress" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit-checkout pmpro_btn-submit-checkout-paypal' ) ); ?>">
					<?php
						printf(
							/* translators: %s is the PayPal logo */
							esc_html__( 'Check Out With %s', 'paid-memberships-pro' ),
							'<span class="pmpro_btn-submit-checkout-paypal-image"></span>'
						);
					?>
					<span class="screen-reader-text"><?php esc_html_e( 'PayPal', 'paid-memberships-pro' ); ?></span>
				</button>
			</span>

			<span id="pmpro_submit_span" <?php if(($gateway == "paypalexpress" || $gateway == "paypalstandard") && $pmpro_requirebilling) { ?>style="display: none;"<?php } ?>>
				<input type="hidden" name="submit-checkout" value="1" />
				<input type="submit" id="pmpro_btn-submit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit-checkout', 'pmpro_btn-submit-checkout' ) ); ?>" value="<?php if($pmpro_requirebilling) { esc_html_e('Submit and Check Out', 'paid-memberships-pro' ); } else { esc_html_e('Submit and Confirm', 'paid-memberships-pro' );}?>" />
			</span>
			<?php

			//don't show the default
			return false;
		}

		//PayPal Express, this is run first to authorize from PayPal
		function setExpressCheckout(&$order)
		{
			global $pmpro_currency;

			if(empty($order->code))
				$order->code = $order->getRandomCode();

			//clean up a couple values
			$order->payment_type = "PayPal Express";
			$order->cardtype = "";

			// Get the level.
			$level = $order->getMembershipLevelAtCheckout();

			//taxes on initial amount
			$initial_payment = $order->subtotal;
			$initial_payment_tax = $order->getTaxForPrice($initial_payment);

			// Note: SetExpressCheckout expects this amount to be the total including tax.
			$initial_payment = pmpro_round_price_as_string( (float) $initial_payment + (float) $initial_payment_tax );

			$profile_start_date = pmpro_calculate_profile_start_date( $order, 'Y-m-d\TH:i:s\Z' );

			//paypal profile stuff
			$nvpStr = "";
			$nvpStr .="&AMT=" . $initial_payment . "&CURRENCYCODE=" . $pmpro_currency;
			if(!empty($profile_start_date) && strtotime($profile_start_date, current_time("timestamp")) > 0)
				$nvpStr .= "&PROFILESTARTDATE=" . $profile_start_date;
			if(!empty($level->cycle_number))
				$nvpStr .= "&BILLINGPERIOD=" . $level->cycle_period . "&BILLINGFREQUENCY=" . $level->cycle_number . "&AUTOBILLOUTAMT=AddToNextBilling&L_BILLINGTYPE0=RecurringPayments";
			$nvpStr .= "&DESC=" . urlencode( apply_filters( 'pmpro_paypal_level_description', substr($order->membership_level->name . " at " . get_bloginfo("name"), 0, 127), $order->membership_level->name, $order, get_bloginfo("name")) );
			$nvpStr .= "&NOTIFYURL=" . urlencode( add_query_arg( 'action', 'ipnhandler', admin_url('admin-ajax.php') ) );
			$nvpStr .= "&NOSHIPPING=1&L_BILLINGAGREEMENTDESCRIPTION0=" . urlencode( apply_filters( 'pmpro_paypal_level_description', substr($order->membership_level->name . " at " . get_bloginfo("name"), 0, 127), $order->membership_level->name, $order, get_bloginfo("name") ) ) . "&L_PAYMENTTYPE0=Any";

			//if billing cycles are defined
			if(!empty($level->billing_limit))
				$nvpStr .= "&TOTALBILLINGCYCLES=" . $level->billing_limit;

			//if a trial period is defined
			if( pmpro_isLevelTrial( $level ) ) {
				$trial_amount = pmpro_round_price( $level->trial_amount );
				$trial_tax = $order->getTaxForPrice($trial_amount);

				// Note: SetExpressCheckout expects this amount to be the total including tax.
				$trial_amount = pmpro_round_price_as_string( (float) $trial_amount + (float) $trial_tax );

				$nvpStr .= "&TRIALBILLINGPERIOD=" . $level->cycle_period . "&TRIALBILLINGFREQUENCY=" . $level->cycle_frequency . "&TRIALAMT=" . $trial_amount . "&TRIALTOTALBILLINGCYCLES=" . $level->trial_limit;
			}

			// Build the return URL. If we are skipping confirmation, add the necessary parameters to make the checkout form appear as if it was submitted.
			$return_url_params = array(
				'pmpro_order' => $order->code,
			);
			$nvpStr .= "&ReturnUrl=" . urlencode( add_query_arg( $return_url_params, pmpro_url( 'checkout' ) ) );

			$additional_parameters = apply_filters("pmpro_paypal_express_return_url_parameters", array());
			if(!empty($additional_parameters))
			{
				foreach($additional_parameters as $key => $value)
					$nvpStr .= urlencode("&" . $key . "=" . $value);
			}

			$nvpStr .= "&CANCELURL=" . urlencode(pmpro_url("levels"));

			$account_optional = apply_filters('pmpro_paypal_account_optional', true);
    		if ($account_optional)
        		$nvpStr .= '&SOLUTIONTYPE=Sole&LANDINGPAGE=Billing';

			$nvpStr = apply_filters("pmpro_set_express_checkout_nvpstr", $nvpStr, $order);

			///echo str_replace("&", "&<br />", $nvpStr);
			///exit;

			$this->httpParsedResponseAr = $this->PPHttpPost('SetExpressCheckout', $nvpStr);

			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"])) {
				$order->status = "token";
				$order->paypal_token = urldecode($this->httpParsedResponseAr['TOKEN']);

				//update order
				$order->saveOrder();

				// Save checkout information to order meta.
				pmpro_save_checkout_data_to_order( $order );

				/**
				 * Allow performing actions just before sending the user to the gateway to complete the payment.
				 *
				 * @since 2.6.5
				 *
				 * @param MemberOrder $order The new order with status = token.
				 */
				do_action( 'pmpro_before_commit_express_checkout', $order );

				//redirect to paypal
				$paypal_url = "https://www.paypal.com/webscr?cmd=_express-checkout&useraction=commit&token=" . $this->httpParsedResponseAr['TOKEN'];
				$environment = get_option("pmpro_gateway_environment");
				if("sandbox" === $environment || "beta-sandbox" === $environment)
				{
					$paypal_url = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&useraction=commit&token="  . $this->httpParsedResponseAr['TOKEN'];
				}

				wp_redirect($paypal_url);
				exit;

				//exit('SetExpressCheckout Completed Successfully: '.print_r($this->httpParsedResponseAr, true));
			} else  {
				$order->errorcode = $this->httpParsedResponseAr['L_ERRORCODE0'];
				$order->error = urldecode($this->httpParsedResponseAr['L_LONGMESSAGE0']);
				$order->shorterror = urldecode($this->httpParsedResponseAr['L_SHORTMESSAGE0']);
				return false;
				//exit('SetExpressCheckout failed: ' . print_r($httpParsedResponseAr, true));
			}

			//write session?

			//redirect to PayPal
		}

		function getExpressCheckoutDetails(&$order)
		{
			$nvpStr="&TOKEN=".$order->paypal_token;

			$nvpStr = apply_filters("pmpro_get_express_checkout_details_nvpstr", $nvpStr, $order);

			/* Make the API call and store the results in an array.  If the
			call was a success, show the authorization details, and provide
			an action to complete the payment.  If failed, show the error
			*/
			$this->httpParsedResponseAr = $this->PPHttpPost('GetExpressCheckoutDetails', $nvpStr);

			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"])) {
				return true;
			} else  {
				$order->errorcode = $this->httpParsedResponseAr['L_ERRORCODE0'];
				$order->error = urldecode($this->httpParsedResponseAr['L_LONGMESSAGE0']);
				$order->shorterror = urldecode($this->httpParsedResponseAr['L_SHORTMESSAGE0']);
				return false;
				//exit('SetExpressCheckout failed: ' . print_r($httpParsedResponseAr, true));
			}
		}

		function charge(&$order)
		{
			global $pmpro_currency;

			if(empty($order->code))
				$order->code = $order->getRandomCode();

			//taxes on the amount
			$amount = $order->subtotal;
			$amount_tax = $order->getTaxForPrice($amount);
			$order->subtotal = $amount;

			// Note: DoExpressCheckoutPayment expects this amount to be the total including tax.
			$amount = pmpro_round_price_as_string( (float) $amount + (float) $amount_tax );

			//paypal profile stuff
			$nvpStr = "";
			if(!empty($order->paypal_token))
				$nvpStr .= "&TOKEN=" . $order->paypal_token;
			$nvpStr .="&AMT=" . $amount . "&CURRENCYCODE=" . $pmpro_currency;
			/*
			if(!empty($amount_tax))
				$nvpStr .= "&TAXAMT=" . $amount_tax;
			*/
			if(!empty($level->cycle_number))
				$nvpStr .= "&BILLINGPERIOD=" . $level->cycle_period . "&BILLINGFREQUENCY=" . $level->cycle_number . "&AUTOBILLOUTAMT=AddToNextBilling";
			$nvpStr .= "&DESC=" . urlencode( apply_filters( 'pmpro_paypal_level_description', substr($order->membership_level->name . " at " . get_bloginfo("name"), 0, 127), $order->membership_level->name, $order, get_bloginfo("name")) );
			$nvpStr .= "&NOTIFYURL=" . urlencode( add_query_arg( 'action', 'ipnhandler', admin_url('admin-ajax.php') ) );
			$nvpStr .= "&NOSHIPPING=1";

			$nvpStr .= "&PAYERID=" . sanitize_text_field( $_REQUEST['PayerID'] ) . "&PAYMENTACTION=sale";

			$nvpStr = apply_filters("pmpro_do_express_checkout_payment_nvpstr", $nvpStr, $order);

			$order->nvpStr = $nvpStr;

			$this->httpParsedResponseAr = $this->PPHttpPost('DoExpressCheckoutPayment', $nvpStr);

			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"])) {
				$order->payment_transaction_id = urldecode($this->httpParsedResponseAr['TRANSACTIONID']);
				$order->status = "success";

				//update order
				$order->saveOrder();

				return true;
			} else  {
				$order->errorcode = $this->httpParsedResponseAr['L_ERRORCODE0'];
				$order->error = urldecode($this->httpParsedResponseAr['L_LONGMESSAGE0']);
				$order->shorterror = urldecode($this->httpParsedResponseAr['L_SHORTMESSAGE0']);
				return false;
				//exit('SetExpressCheckout failed: ' . print_r($httpParsedResponseAr, true));
			}
		}

		function subscribe(&$order)
		{
			global $pmpro_currency, $pmpro_review;

			if(empty($order->code))
				$order->code = $order->getRandomCode();

			//filter order before subscription. use with care.
			$order = apply_filters("pmpro_subscribe_order", $order, $this);

			//taxes on initial amount
			$initial_payment = $order->subtotal;
			$initial_payment_tax = $order->getTaxForPrice($initial_payment);

			// Note: CreateRecurringPaymentsProfile expects this amount to be the total including tax.
			$initial_payment = pmpro_round_price_as_string( (float) $initial_payment + (float) $initial_payment_tax );

			//taxes on the amount
			$level = $order->getMembershipLevelAtCheckout();
			$amount = $level->billing_amount;
			$amount_tax = $order->getTaxForPrice( $amount );

			// Note: CreateRecurringPaymentsProfile expects this amount to be the total excluding tax.
			$amount = pmpro_round_price_as_string( $amount );

			// Adding back a fix from filters.php that allowed for start dates > 1 year out.
			$profile_start_date = pmpro_calculate_profile_start_date( $order, 'Y-m-d\TH:i:s\Z' );
			$original_start_date = $profile_start_date;
			$one_year_out = strtotime( '+1 Year', current_time( 'timestamp' ) );
			$two_years_out = strtotime( '+2 Year', current_time( 'timestamp' ) );
			$one_year_out_date = date_i18n( 'Y-m-d\TH:i:s\Z', $one_year_out );
			$days_past = floor( ( strtotime( $profile_start_date ) - $one_year_out ) / DAY_IN_SECONDS );
			$trial_amount = pmpro_round_price( $level->trial_amount );
			$trial_period = $level->cycle_period;
			$trial_frequency = $level->cycle_number;
			$trial_cycles = $level->trial_limit;
			if ( ! empty( $profile_start_date ) && $profile_start_date > $one_year_out_date ) {
				// Max out the profile start date at 1 year out no matter what.
				$profile_start_date = $one_year_out_date;

				// Try to squeeze into the trial.
				if ( empty( $trial_cycles ) && $days_past > 0 ) {
					// Update the trial information.
					$trial_amount = 0;
					$trial_period = 'Day';
					$trial_frequency = min( 365, $days_past );
					$trial_cycles = 1;
				}
	
				// if we were going to try to push it more than 2 years out, let's notify the admin
				if ( ! empty( $trial_cycles ) || strtotime( $profile_start_date ) > $two_years_out ) {
					// setup user data
					global $current_user;
					if ( empty( $order->user_id ) ) {
						$order->user_id = $current_user->ID;
					}
					$order->getUser();
	
					// create email
					$pmproemail = new PMProEmail();
					$body = '<p>' . __( "There was a potential issue while setting the 'Profile Start Date' for a user's subscription at checkout. PayPal does not allow one to set a Profile Start Date further than 1 year out. Typically, this is not an issue, but sometimes a combination of custom code or add ons for PMPro (e.g. the Prorating or Auto-renewal Checkbox add ons) will try to set a Profile Start Date out past 1 year in order to respect an existing user's original expiration date before they checked out. The user's information is below. PMPro has allowed the checkout and simply restricted the Profile Start Date to 1 year out with a possible additional free Trial of up to 1 year. You should double check this information to determine if maybe the user has overpaid or otherwise needs to be addressed. If you get many of these emails, you should consider adjusting your custom code to avoid these situations.", 'paid-memberships-pro' ) . '</p>';
					$body .= '<p>' . sprintf( __( 'User: %1$s<br />Email: %2$s<br />Membership Level: %3$s<br />Order #: %4$s<br />Original Profile Start Date: %5$s<br />Adjusted Profile Start Date: %6$s<br />Trial Period: %7$s<br />Trial Frequency: %8$s<br />', 'paid-memberships-pro' ), $order->user->user_nicename, $order->user->user_email, $level->name, $order->code, $original_start_date , $one_year_out_date, $trial_period, $trial_frequency ) . '</p>';
					$pmproemail->template = 'profile_start_date_limit_check';
					$pmproemail->subject = sprintf( __( 'Profile Start Date Issue Detected and Fixed at %s', 'paid-memberships-pro' ), get_bloginfo( 'name' ) );
					$pmproemail->data = array( 'body' => $body );
					$pmproemail->sendEmail( get_bloginfo( 'admin_email' ) );
				}
			}

			//paypal profile stuff
			$nvpStr = "";
			if(!empty($order->paypal_token))
				$nvpStr .= "&TOKEN=" . $order->paypal_token;
			$nvpStr .="&INITAMT=" . $initial_payment . "&AMT=" . $amount . "&CURRENCYCODE=" . $pmpro_currency . "&PROFILESTARTDATE=" . $profile_start_date;
			if(!empty($amount_tax))
				$nvpStr .= "&TAXAMT=" . pmpro_round_price_as_string( $amount_tax );
			$nvpStr .= "&BILLINGPERIOD=" . $level->cycle_period . "&BILLINGFREQUENCY=" . $level->cycle_number . "&AUTOBILLOUTAMT=AddToNextBilling";
			$nvpStr .= "&NOTIFYURL=" . urlencode( add_query_arg( 'action', 'ipnhandler', admin_url('admin-ajax.php') ) );
			$nvpStr .= "&DESC=" . urlencode( apply_filters( 'pmpro_paypal_level_description', substr($order->membership_level->name . " at " . get_bloginfo("name"), 0, 127), $order->membership_level->name, $order, get_bloginfo("name")) );

			//if billing cycles are defined
			if(!empty($level->billing_limit))
				$nvpStr .= "&TOTALBILLINGCYCLES=" . $level->billing_limit;

			//if a trial period is defined
			if ( pmpro_isLevelTrial( $level ) ) {
				$trial_tax = $order->getTaxForPrice($trial_amount);

				/*
				 * Note: For the CreateRecurringPaymentsProfile API call, it expects the TRIALAMT to be the total excluding taxes.
				 *
				 * However, there is no TRIALTAXAMT for trial periods so this is a workaround.
				 */
				$trial_amount = pmpro_round_price_as_string( (float) $trial_amount + (float) $trial_tax );

				$nvpStr .= "&TRIALBILLINGPERIOD=" . $trial_period . "&TRIALBILLINGFREQUENCY=" . $trial_frequency . "&TRIALAMT=" . $trial_amount . "&TRIALTOTALBILLINGCYCLES=" . $trial_cycles;
			}

			// Set MAXFAILEDPAYMENTS so subscriptions are cancelled after 1 failed payment.
			$nvpStr .= "&MAXFAILEDPAYMENTS=1";

			$nvpStr = apply_filters("pmpro_create_recurring_payments_profile_nvpstr", $nvpStr, $order);

			//for debugging let's add this to the class object
			$this->nvpStr = $nvpStr;

			///echo str_replace("&", "&<br />", $nvpStr);
			///exit;

			$this->httpParsedResponseAr = $this->PPHttpPost('CreateRecurringPaymentsProfile', $nvpStr);

			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"])) {
				// PayPal docs says that PROFILESTATUS can be:
				// 1. ActiveProfile — The recurring payment profile has been successfully created and activated for scheduled payments according the billing instructions from the recurring payments profile.
				// 2. PendingProfile — The system is in the process of creating the recurring payment profile. Please check your IPN messages for an update.
				// Also, we have seen that PROFILESTATUS can be missing. That case would be an error.
				if(isset($this->httpParsedResponseAr["PROFILESTATUS"]) && in_array($this->httpParsedResponseAr["PROFILESTATUS"], array("ActiveProfile", "PendingProfile"))) {
					$order->status = "success";

					// this is wrong, but we don't know the real transaction id at this point
					$order->payment_transaction_id = urldecode($this->httpParsedResponseAr['PROFILEID']);
					$order->subscription_transaction_id = urldecode($this->httpParsedResponseAr['PROFILEID']);

					return true;
				} else {
					// this is wrong, but we don't know the real transaction id at this point
					$order->payment_transaction_id = urldecode($this->httpParsedResponseAr['PROFILEID']);
					$order->subscription_transaction_id = urldecode($this->httpParsedResponseAr['PROFILEID']);

					$order->errorcode = '';
					$order->error = __( 'Something went wrong creating plan with PayPal; missing PROFILESTATUS.', 'paid-memberships-pro' );
					$order->shorterror = __( 'Error creating plan with PayPal.', 'paid-memberships-pro' );

					//update order
					$order->saveOrder();

					return false;
				}
			} else  {
				// stop processing the review request on checkout page
				$order->errorcode = $this->httpParsedResponseAr['L_ERRORCODE0'];
				$order->error = urldecode($this->httpParsedResponseAr['L_LONGMESSAGE0']);
				$order->shorterror = urldecode($this->httpParsedResponseAr['L_SHORTMESSAGE0']);

				return false;
			}
		}

		function cancel(&$order) {
			// Always cancel the order locally even if PayPal might fail
			$order->updateStatus("cancelled");

			// If we're processing an IPN request for this subscription, it's already cancelled at PayPal.
			if ( ( ! empty( $_POST['subscr_id'] ) && $_POST['subscr_id'] == $order->subscription_transaction_id ) ||
				 ( ! empty( $_POST['recurring_payment_id'] ) && $_POST['recurring_payment_id'] == $order->subscription_transaction_id ) ) {
				// recurring_payment_failed transaction still need to be cancelled
				if ( $_POST['txn_type'] !== 'recurring_payment_failed' ) {
					return true;
				}
			}

			// Cancel at gateway
			return $this->cancelSubscriptionAtGateway($order);
		}

		function cancelSubscriptionAtGateway(&$order) {
			// Build the nvp string for PayPal API
			$nvpStr = "";
			$nvpStr .= "&PROFILEID=" . urlencode($order->subscription_transaction_id) . "&ACTION=Cancel&NOTE=" . urlencode("User requested cancel.");

			$nvpStr = apply_filters("pmpro_manage_recurring_payments_profile_status_nvpstr", $nvpStr, $order);

			$this->httpParsedResponseAr = $this->PPHttpPost('ManageRecurringPaymentsProfileStatus', $nvpStr);

			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"])) {
				return true;
			} else {
				$order->errorcode = $this->httpParsedResponseAr['L_ERRORCODE0'];
				$order->error = urldecode($this->httpParsedResponseAr['L_LONGMESSAGE0']) . ". " . __("Please contact the site owner or cancel your subscription from within PayPal to make sure you are not charged going forward.", 'paid-memberships-pro' );
				$order->shorterror = urldecode($this->httpParsedResponseAr['L_SHORTMESSAGE0']);

				return false;
			}
		}

		/**
		 * Cancels a subscription in PayPal.
		 *
		 * @param PMPro_Subscription $subscription to cancel.
	 	 */
		function cancel_subscription( $subscription ) {
			// Build the nvp string for PayPal API
			$nvpStr = '&PROFILEID=' . urlencode( $subscription->get_subscription_transaction_id() ) . '&ACTION=Cancel&NOTE=' . urlencode('User requested cancel.');
			$this->httpParsedResponseAr = $this->PPHttpPost('ManageRecurringPaymentsProfileStatus', $nvpStr);

			return ( 'SUCCESS' == strtoupper( $this->httpParsedResponseAr['ACK'] ) || 'SUCCESSWITHWARNING' == strtoupper( $this->httpParsedResponseAr['ACK'] ) );
		}

		function getSubscriptionStatus(&$order)
		{
			if(empty($order->subscription_transaction_id))
				return false;

			//paypal profile stuff
			$nvpStr = "";
			$nvpStr .= "&PROFILEID=" . urlencode($order->subscription_transaction_id);

			$nvpStr = apply_filters("pmpro_get_recurring_payments_profile_details_nvpstr", $nvpStr, $order);

			$this->httpParsedResponseAr = $this->PPHttpPost('GetRecurringPaymentsProfileDetails', $nvpStr);

			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"]))
			{
				return $this->httpParsedResponseAr;
			}
			else
			{
				$order->errorcode = $this->httpParsedResponseAr['L_ERRORCODE0'];
				$order->error = urldecode($this->httpParsedResponseAr['L_LONGMESSAGE0']);
				$order->shorterror = urldecode($this->httpParsedResponseAr['L_SHORTMESSAGE0']);

				return false;
			}
		}

		/**
		 * Pull subscription info from PayPal.
		 *
		 * @param PMPro_Subscription $subscription to pull data for.
		 *
		 * @return string|null Error message is returned if update fails.
		 */
		function update_subscription_info( $subscription ) {
			$API_UserName	= get_option( "pmpro_apiusername" );
			$API_Password	= get_option( "pmpro_apipassword" );
			$API_Signature = get_option( "pmpro_apisignature" );
			if ( empty( $API_UserName ) || empty( $API_Password ) || empty( $API_Signature ) ) {
				return __( "PayPal login credentials are not set.", 'paid-memberships-pro' );
			}

			$subscription_transaction_id = $subscription->get_subscription_transaction_id();
			if ( empty( $subscription_transaction_id ) ) {
				return __( 'Subscription transaction ID is empty.', 'paid-memberships-pro' );
			}

			//paypal profile stuff
			$nvpStr = "";
			$nvpStr .= "&PROFILEID=" . urlencode( $subscription_transaction_id );
			$response = $this->PPHttpPost('GetRecurringPaymentsProfileDetails', $nvpStr);

			if("SUCCESS" == strtoupper($response["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($response["ACK"])) {
				// Found subscription.
				$update_array = array();

				// PayPal doesn't send the subscription start date, so let's take a guess based on the user's order history.
				$oldest_orders = $subscription->get_orders( [
					'limit'   => 1,
					'orderby' => '`timestamp` ASC, `id` ASC',
				] );

				if ( ! empty( $oldest_orders ) ) {
					$oldest_order = current( $oldest_orders );

					$update_array['startdate'] = date_i18n( 'Y-m-d H:i:s', $oldest_order->getTimestamp( true ) );
				}

				if ( in_array( $response['STATUS'], array( 'Pending', 'Active' ), true ) ) {
					// Subscription is active.
					$update_array['status'] = 'active';
					$update_array['next_payment_date'] = date( 'Y-m-d H:i:s', strtotime( $response['NEXTBILLINGDATE'] ) );
					$update_array['billing_amount'] = floatval( $response['REGULARAMT'] );
					$update_array['cycle_number'] = (int) $response['REGULARBILLINGFREQUENCY'];
					$update_array['cycle_period'] = $response['REGULARBILLINGPERIOD'];
					$update_array['trial_amount'] = empty( $response['TRIALAMT'] ) ? 0 : floatval( $response['TRIALAMT'] );
					$update_array['trial_limit'] = empty( $response['TRIALTOTALBILLINGCYCLES'] ) ? 0 : (int) $response['TRIALTOTALBILLINGCYCLES'];
					$update_array['billing_limit'] = empty( $response['REGULARTOTALBILLINGCYCLES'] ) ? 0 : (int) $response['REGULARTOTALBILLINGCYCLES'];
				} else {
					// Subscription is no longer active.
					// Can't fill subscription end date, $request only has the date of the last payment.
					$update_array['status'] = 'cancelled';
				}
				$subscription->set( $update_array );
			} else {
				return __( 'Subscription could not be found.', 'paid-memberships-pro' );
			}
		}
		
		function getTransactionStatus(&$order) {
			$transaction_details = $order->Gateway->getTransactionDetailsByOrder( $order );
			if( false === $transaction_details ){
				return false;
			}

			if( ! isset( $transaction_details['PAYMENTSTATUS'] ) ){
				return false;
			}

			return $transaction_details['PAYMENTSTATUS'];
		}

		function getTransactionDetailsByOrder(&$order)
		{
			if(empty($order->payment_transaction_id))
				return false;

			if( $order->payment_transaction_id == $order->subscription_transaction_id ){
				$payment_transaction_id = $this->getRealPaymentTransactionId( $order );
				if( ! $payment_transaction_id ){
					return false;
				}

				return $this->getTransactionDetails( $payment_transaction_id );
			}else{
				/** Recurring payment **/
				return $this->getTransactionDetails( $order->payment_transaction_id );
			}
		}

		/**
		 * Try to recover the real payment_transaction_id when payment_transaction_id === subscription_transaction_id === I-xxxxxxxx.
		 *
		 * @since 1.8.5
		*/
		function getRealPaymentTransactionId(&$order)
		{
			/** Initial payment **/
			$nvpStr = "";
			// STARTDATE is Required, even if useless here. Start from 24h before the order timestamp, to avoid timezone related issues.
			$nvpStr .= "&STARTDATE=" . urlencode( gmdate( DATE_W3C, $order->getTimestamp() - DAY_IN_SECONDS ) . 'Z' );
			// filter results by a specific transaction id.
			$nvpStr .= "&TRANSACTIONID=" . urlencode($order->subscription_transaction_id);

			$this->httpParsedResponseAr = $this->PPHttpPost('TransactionSearch', $nvpStr);

			if( ! in_array( strtoupper( $this->httpParsedResponseAr["ACK"] ), [ 'SUCCESS', 'SUCCESSWITHWARNING' ] ) ){
				// since we are using TRANSACTIONID=I-... which is NOT a transaction id,
				// paypal is returning an error. but the results are actually filtered by that transaction id, usually.

				// let's double check it.
				if( ! isset( $this->httpParsedResponseAr['L_TRANSACTIONID0'] ) ){
					// really no results? it's a real error.
					return false;
				}
			}

			$transaction_ids = [];
			for( $i = 0; $i < PHP_INT_MAX; $i++ ){
				// loop until we have results
				if( ! isset( $this->httpParsedResponseAr["L_TRANSACTIONID$i"] ) ){
					break;
				}

				// ignore I-... results
				if( "I-" === substr( $this->httpParsedResponseAr["L_TRANSACTIONID$i"], 0 ,2 ) ){
					if( $order->subscription_transaction_id != $this->httpParsedResponseAr["L_TRANSACTIONID$i"] ){
						// if we got a result from another I- subscription transaction id,
						// then something changed into paypal responses.
						// var_dump( $this->httpParsedResponseAr, $this->httpParsedResponseAr["L_TRANSACTIONID$i"] );
						throw new Exception();
					}

					continue;
				}

				$transaction_ids[] = $this->httpParsedResponseAr["L_TRANSACTIONID$i"];
			}

			// no payment_transaction_ids in results
			if( empty( $transaction_ids ) ){
				return false;
			}

			// found the payment transaction id, it's the last one (the oldest)
			$payment_transaction_id = end( $transaction_ids );

			return $payment_transaction_id;
		}

		function getTransactionDetails($payment_transaction_id)
        	{
			$nvpStr = "";
			$nvpStr .= "&TRANSACTIONID=" . urlencode($payment_transaction_id);

			$this->httpParsedResponseAr = $this->PPHttpPost('GetTransactionDetails', $nvpStr);

			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"]))
			{
				return $this->httpParsedResponseAr;
			}
			else
			{
				// var_dump( $this->httpParsedResponseAr, $this->httpParsedResponseAr["L_TRANSACTIONID$i"] );
				return false;
			}
		}

		/**
		 * Filter pmpro_next_payment to get date via API if possible
		 *
		 * @since 1.8.5
		*/
		static function pmpro_next_payment($timestamp, $user_id, $order_status)
		{
			//find the last order for this user
			if(!empty($user_id))
			{
				//get last order
				$order = new MemberOrder();
				$order->getLastMemberOrder($user_id, $order_status);

				//check if this is a paypal express order with a subscription transaction id
				if(!empty($order->id) && !empty($order->subscription_transaction_id) && $order->gateway == "paypalexpress")
				{
					//get the subscription status
					$gateway = new PMProGateway_paypalexpress();
					$status = $gateway->getSubscriptionStatus($order);

					if(!empty($status) && !empty($status['NEXTBILLINGDATE']))
					{
						//found the next billing date at PayPal, going to use that
						$timestamp = strtotime(urldecode($status['NEXTBILLINGDATE']), current_time('timestamp'));
					}
					elseif(!empty($status) && !empty($status['PROFILESTARTDATE']) && $order_status == "cancelled")
					{
						//startdate is in the future and we cancelled so going to use that as the next payment date
						$startdate_timestamp = strtotime(urldecode($status['PROFILESTARTDATE']), current_time('timestamp'));
						if($startdate_timestamp > current_time('timestamp'))
							$timestamp = $startdate_timestamp;
					}
				}
			}

			return $timestamp;
		}

		/**
		 * PAYPAL Function
		 * Send HTTP POST Request with retries
		 *
		 * @param string    The API method name
		 * @param string    The POST Message fields in &name=value pair format
		 *
		 * @return array    Parsed HTTP Response body
		 */
		function PPHttpPost( $methodName_, $nvpStr_ ) {
			// Create a UUID for this request, to enable idempotency which is supported
			// by PayPal Express even if it's legacy (thus it's not reported in the docs)
			// https://developer.paypal.com/docs/business/develop/idempotency/
			$uuid = self::RandomGenerator_uuid();

			$numRetries = 0;
			do {
				$httpParsedResponseAr = $this->PPHttpPost_DontDieOnError( $methodName_, $nvpStr_, $uuid );
				if ( is_wp_error( $httpParsedResponseAr ) ) {
					$numRetries++;
					sleep( self::sleepTime( $numRetries ) );
				} else {
					break;
				}
			} while ( $numRetries <= self::$maxNetworkRetries );

			// If we still have an error even with the retries, there's not much we can do.
			if ( is_wp_error( $httpParsedResponseAr ) ) {
				// exiting is never a good user experience and it's hard to debug, but we can
				// at least leave a trace in error log to make it easier to see this happening
				error_log( "Unable to complete $methodName_ request with $nvpStr_: " . $httpParsedResponseAr->get_error_message() );
				die( esc_html( "Unable to complete $methodName_ request with $nvpStr_: " . $httpParsedResponseAr->get_error_message() ) );
			}

			/**
			 * Allow performing actions using the http post request's response.
			 *
			 * @since 2.8
			 *
			 * @param array $httpParsedResponseAr The parsed response.
			 * @param string $methodName_ The NVP API name.
			 */
			do_action( 'pmpro_paypal_handle_http_post_response', $httpParsedResponseAr, $methodName_ );
			
			return $httpParsedResponseAr;
		}

		/**
		 * Refunds an order (only supports full amounts)
		 *
		 * @param bool    $success Status of the refund (default: false)
		 * @param object  $morder The Member Order Object
		 * @since 2.8
		 * 
		 * @return bool   Status of the processed refund
		 */
		public static function process_refund( $success, $morder ){

			//need a transaction id
			if ( empty( $morder->payment_transaction_id ) ) {
				return false;
			}

			$transaction_id = $morder->payment_transaction_id;

			//Get the real transaction ID
			if ( $transaction_id === $morder->subscription_transaction_id ) {
				$transaction_id = $morder->Gateway->getRealPaymentTransactionId( $morder );
			}

			$httpParsedResponseAr = $morder->Gateway->PPHttpPost( 'RefundTransaction', '&TRANSACTIONID='.$transaction_id );		

			if ( 'success' === strtolower( $httpParsedResponseAr['ACK'] ) ) {
				
				$success = true;

				$morder->status = 'refunded';

				global $current_user;

				// translators: %1$s is the Transaction ID. %2$s is the user display name that initiated the refund.
				$morder->notes = trim( $morder->notes . ' ' . sprintf( __('Admin: Order successfully refunded on %1$s for transaction ID %2$s by %3$s.', 'paid-memberships-pro' ), date_i18n('Y-m-d H:i:s'), $transaction_id, $current_user->display_name ) );

				$user = get_user_by( 'id', $morder->user_id );
				//send an email to the member
				$myemail = new PMProEmail();
				$myemail->sendRefundedEmail( $user, $morder );

				//send an email to the admin
				$myemail = new PMProEmail();
				$myemail->sendRefundedAdminEmail( $user, $morder );

			} else {
				//The refund failed, so lets return the gateway message
				
				// translators: %1$s is the Transaction ID. %1$s is the Gateway Error
				$morder->notes = trim( $morder->notes .' '. sprintf( __( 'Admin: There was a problem processing a refund for transaction ID %1$s. Gateway Error: %2$s.', 'paid-memberships-pro' ), $transaction_id, $httpParsedResponseAr['L_LONGMESSAGE0'] ) );
			}

			$morder->SaveOrder();

			return $success;
			
		}
    
    /**
		 * PAYPAL Function
		 * Send HTTP POST Request with uuid
		 *
		 * @param	string	The API method name
		 * @param	string	The POST Message fields in &name=value pair format
		 * @return	array|\WP_Error	Parsed HTTP Response body
		 */
		function PPHttpPost_DontDieOnError($methodName_, $nvpStr_, $uuid) {
			global $gateway_environment;
			$environment = $gateway_environment;

			$API_UserName = get_option("pmpro_apiusername");
			$API_Password = get_option("pmpro_apipassword");
			$API_Signature = get_option("pmpro_apisignature");
			$API_Endpoint = "https://api-3t.paypal.com/nvp";
			if("sandbox" === $environment || "beta-sandbox" === $environment) {
				$API_Endpoint = "https://api-3t.$environment.paypal.com/nvp";
			}

			$version = urlencode('72.0');

			//NVPRequest for submitting to server
			$nvpreq = "METHOD=" . urlencode($methodName_) . "&VERSION=" . urlencode($version) . "&PWD=" . urlencode($API_Password) . "&USER=" . urlencode($API_UserName) . "&SIGNATURE=" . urlencode($API_Signature) . "&BUTTONSOURCE=" . urlencode(PAYPAL_BN_CODE) . $nvpStr_;

			//post to PayPal
			$response = wp_remote_post( $API_Endpoint, array(
					'timeout' => 60,
					'sslverify' => FALSE,
					'httpversion' => '1.1',
					'body' => $nvpreq,
					'headers'     => array(
						'content-type'      => 'application/x-www-form-urlencoded',
						'PayPal-Request-Id' => $uuid,
					),
			    )
			);

			if ( is_wp_error( $response ) ) {
                return $response;
			}

            //extract the response details
            $httpParsedResponseAr = array();
            parse_str(wp_remote_retrieve_body($response), $httpParsedResponseAr);

            //check for valid response
            if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)) {
	            return new WP_Error( 1, "Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint." );
            }

			return $httpParsedResponseAr;
		}

		/**
		 * Provides the number of seconds to wait before retrying a request.
		 * Inspired by Stripe\HttpClient\CurlClient::sleepTime
		 *
		 * @param int $numRetries
		 *
		 * @return int
		 */
		public static function sleepTime( $numRetries ) {
			// Apply exponential backoff with $initialNetworkRetryDelay on the
			// number of $numRetries so far as inputs. Do not allow the number to exceed
			// $maxNetworkRetryDelay.
			$sleepSeconds = \min(
				self::$maxNetworkRetryDelay * 1.0 * 2 ** ( $numRetries - 1 ),
				self::$maxNetworkRetryDelay
			);

			// Apply some jitter by randomizing the value in the range of
			// ($sleepSeconds / 2) to ($sleepSeconds).
			$sleepSeconds *= 0.5 * ( 1 + self::randFloat() );

			// But never sleep less than the base sleep seconds.
			$sleepSeconds = \max( self::$initialNetworkRetryDelay, $sleepSeconds );

			return (int)$sleepSeconds;
		}

		/**
		 * Returns a random value between 0 and $max.
		 * From Stripe\Util\RandomGenerator::randFloat()
		 *
		 * @param float $max (optional)
		 *
		 * @return float
		 */
		public static function randFloat( $max = 1.0 ) {
			return \mt_rand() / \mt_getrandmax() * $max;
		}

		/**
		 * Returns a v4 UUID.
		 * From Stripe\Util\RandomGenerator::uuid()
		 *
		 * @return string
		 */
		public static function RandomGenerator_uuid() {
			$arr    = \array_values( \unpack( 'N1a/n4b/N1c', \openssl_random_pseudo_bytes( 16 ) ) );
			$arr[2] = ( $arr[2] & 0x0fff ) | 0x4000;
			$arr[3] = ( $arr[3] & 0x3fff ) | 0x8000;

			return \vsprintf( '%08x-%04x-%04x-%04x-%04x%08x', $arr );
		}
	}
