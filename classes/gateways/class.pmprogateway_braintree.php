<?php

use Braintree\WebhookNotification as Braintree_WebhookNotification;

	//include pmprogateway
	require_once(dirname(__FILE__) . "/class.pmprogateway.php");

	//load classes init method
	add_action('init', array('PMProGateway_braintree', 'init'));

	class PMProGateway_braintree extends PMProGateway
	{
		/**
		 * @var bool    Is the Braintree/PHP Library loaded
		 */
		private static $is_loaded = false;

		function __construct($gateway = NULL)
		{
			$this->gateway = $gateway;
			$this->gateway_environment = get_option("pmpro_gateway_environment");

			if( true === $this->dependencies() ) {
				$this->loadBraintreeLibrary();

				//convert to braintree nomenclature
				$environment = $this->gateway_environment;
				if($environment == "live")
					$environment = "production";

				$merch_id = get_option( "pmpro_braintree_merchantid" );
				$pk = get_option( "pmpro_braintree_publickey" );
				$sk = get_option( "pmpro_braintree_privatekey" );

                try {

                    Braintree_Configuration::environment( $environment );
                    Braintree_Configuration::merchantId( $merch_id );
                    Braintree_Configuration::publicKey( $pk );
                    Braintree_Configuration::privateKey( $sk );

                } catch( Exception $exception ) {
                    global $msg;
                    global $msgt;
                    global $pmpro_braintree_error;

                    error_log($exception->getMessage() );

                        $pmpro_braintree_error = true;
                        $msg                   = - 1;
                        $msgt                  = sprintf( esc_html__( 'Attempting to load Braintree gateway: %s', 'paid-memberships-pro' ), $exception->getMessage() );
                    return false;
                }

				self::$is_loaded = true;
			}

			return $this->gateway;
		}
		/**
		 * Warn if required extensions aren't loaded.
		 *
		 * @return bool
		 * @since 1.8.6.8.1
		 */
		public static function dependencies()
		{
			global $msg, $msgt, $pmpro_braintree_error;

			if ( version_compare( PHP_VERSION, '5.4.45', '<' )) {

				$msg = -1;
				$msgt = sprintf(esc_html__("The Braintree Gateway requires PHP 5.4.45 or greater. We recommend upgrading to PHP %s or greater. Ask your host to upgrade.", "paid-memberships-pro" ), PMPRO_MIN_PHP_VERSION );

				pmpro_setMessage( $msgt, "pmpro_error" );
				return false;
			}

			$modules = array('xmlwriter', 'SimpleXML', 'openssl', 'dom', 'hash', 'curl');

			foreach($modules as $module){
				if(!extension_loaded($module)){

				    if ( false == $pmpro_braintree_error ) {
					    $pmpro_braintree_error = true;
					    $msg                   = - 1;
					    $msgt                  = sprintf( esc_html__( "The %s gateway depends on the %s PHP extension. Please enable it, or ask your hosting provider to enable it.", 'paid-memberships-pro' ), 'Braintree', $module );
				    }

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
		 * Load the Braintree API library.
		 *
		 * @since 1.8.1
		 * Moved into a method in version 1.8.1 so we only load it when needed.
		 */
		function loadBraintreeLibrary()
		{
			//load Braintree library if it hasn't been loaded already (usually by another plugin using Braintree)
			if ( ! class_exists( "\Braintree" ) ) {
				require_once( PMPRO_DIR . "/includes/lib/Braintree/lib/Braintree.php");
			} else {
				// Another plugin may have loaded the Braintree library already.
				// Let's log the current Braintree Library info so that we know
				// where to look if we need to troubleshoot library conflicts.
				$previously_loaded_class = new \ReflectionClass( '\Braintree' );
				pmpro_track_library_conflict( 'braintree', $previously_loaded_class->getFileName(), Braintree\Version::get() );
			}
		}

		/**
		 * Get a collection of plans available for this Braintree account.
		 */
		function getPlans($force = false) {
			//check for cache
			$cache_key = 'pmpro_braintree_plans_' . md5($this->gateway_environment . get_option("pmpro_braintree_merchantid") . get_option("pmpro_braintree_publickey") . get_option("pmpro_braintree_privatekey"));

      $plans = wp_cache_get( $cache_key,'pmpro_levels' );

			//check Braintree if no transient found
			if($plans === false) {

			    try {
				    $plans = Braintree_Plan::all();

			    } catch( Braintree\Exception $exception ) {

			        global $msg;
			        global $msgt;
				    global $pmpro_braintree_error;

				    if ( false == $pmpro_braintree_error ) {

				        $pmpro_braintree_error = true;
					    $msg                   = - 1;
					    $status = $exception->getMessage();

					    if ( !empty( $status)) {
						    $msgt = sprintf( esc_html__( "Problem loading plans: %s", "paid-memberships-pro" ), $status );
					    } else {
					        $msgt = esc_html__( "Problem accessing the Braintree Gateway. Please verify your PMPro Payment Settings (Keys, etc).", "paid-memberships-pro");
                        }
				    }

			        return false;
                }

                // Save to local cache
                if ( !empty( $plans ) ) {
	                /**
	                 * @since v1.9.5.4+ - BUG FIX: Didn't expire transient
                     * @since v1.9.5.4+ - ENHANCEMENT: Use wp_cache_*() system over direct transients
	                 */
                    wp_cache_set( $cache_key,$plans,'pmpro_levels',HOUR_IN_SECONDS );
                }
			}

			return $plans;
		}

		/**
         * Clear cached plans when updating membership level
         *
		 * @param $level_id
		 */
		public static function pmpro_save_level_action( $level_id ) {

		    $BT_Gateway = new PMProGateway_braintree();

		    if ( isset( $BT_Gateway->gateway_environment ) ) {
			    $cache_key = 'pmpro_braintree_plans_' . md5($BT_Gateway->gateway_environment . get_option("pmpro_braintree_merchantid") . get_option("pmpro_braintree_publickey") . get_option("pmpro_braintree_privatekey"));

			    wp_cache_delete( $cache_key,'pmpro_levels' );
		    }
		}

		/**
		 * Search for a plan by id
		 */
		function getPlanByID($id) {
			$plans = $this->getPlans();

			if(!empty($plans)) {
				foreach($plans as $plan) {
					if($plan->id == $id)
						return $plan;
				}
			}

			return false;
		}

		/**
		 * Checks if a level has an associated plan.
		 */
		static function checkLevelForPlan($level_id) {
			$Gateway = new PMProGateway_braintree();

			$plan = $Gateway->getPlanByID( $Gateway->get_plan_id( $level_id ) );

			if(!empty($plan))
				return true;
			else
				return false;
		}

		/**
		 * Run on WP init
		 *
		 * @since 1.8
		 */
		static function init()
		{
			//make sure Braintree Payments is a gateway option
			add_filter('pmpro_gateways', array('PMProGateway_braintree', 'pmpro_gateways'));

			//add fields to payment settings
			add_filter('pmpro_payment_options', array('PMProGateway_braintree', 'pmpro_payment_options'));
			add_filter('pmpro_payment_option_fields', array('PMProGateway_braintree', 'pmpro_payment_option_fields'), 10, 2);

			//code to add at checkout if Braintree is the current gateway
			$default_gateway = get_option('pmpro_gateway');
			$current_gateway = pmpro_getGateway();
			if( ( $default_gateway == "braintree" || $current_gateway == "braintree" && empty($_REQUEST['review'])))	//$_REQUEST['review'] means the PayPal Express review page
			{
			    add_action('pmpro_checkout_preheader', array('PMProGateway_braintree', 'pmpro_checkout_preheader'));
				add_action( 'pmpro_billing_preheader', array( 'PMProGateway_braintree', 'pmpro_checkout_preheader' ) );
				add_action( 'pmpro_save_membership_level', array( 'PMProGateway_braintree', 'pmpro_save_level_action') );
				add_action('pmpro_checkout_before_submit_button', array('PMProGateway_braintree', 'pmpro_checkout_before_submit_button'));
				add_action('pmpro_billing_before_submit_button', array('PMProGateway_braintree', 'pmpro_checkout_before_submit_button'));
				add_filter('pmpro_checkout_order', array('PMProGateway_braintree', 'pmpro_checkout_order'));
				add_filter('pmpro_billing_order', array('PMProGateway_braintree', 'pmpro_checkout_order'));
				add_filter('pmpro_required_billing_fields', array('PMProGateway_braintree', 'pmpro_required_billing_fields'));
				add_filter('pmpro_include_payment_information_fields', array('PMProGateway_braintree', 'pmpro_include_payment_information_fields'));
			}
		}

		/**
		 * Make sure this gateway is in the gateways list
		 *
		 * @since 1.8
		 */
		static function pmpro_gateways($gateways)
		{
			if(empty($gateways['braintree']))
				$gateways['braintree'] = esc_html__('Braintree Payments', 'paid-memberships-pro' );

			return $gateways;
		}

		/**
		 * Get a list of payment options that the this gateway needs/supports.
		 *
		 * @since 1.8
		 */
		static function getGatewayOptions()
		{
			$options = array(
				'gateway_environment',
				'braintree_merchantid',
				'braintree_publickey',
				'braintree_privatekey',
				'braintree_encryptionkey',
				'currency',
				'tax_state',
				'tax_rate',
			);

			return $options;
		}

		/**
		 * Set payment options for payment settings page.
		 *
		 * @since 1.8
		 */
		static function pmpro_payment_options($options)
		{
			//get Braintree options
			$braintree_options = self::getGatewayOptions();

			//merge with others.
			$options = array_merge($braintree_options, $options);

			return $options;
		}

		/**
		 * Display fields for this gateway's options.
		 *
		 * @since 1.8
		 */
		static function pmpro_payment_option_fields($values, $gateway)
		{
        ?>
		<tr class="pmpro_settings_divider gateway gateway_braintree" <?php if($gateway != "braintree") { ?>style="display: none;"<?php } ?>>
			<td colspan="2">
				<hr />
				<h2 class="title"><?php esc_html_e( 'Braintree Settings', 'paid-memberships-pro' ); ?></h2>
			</td>
		</tr>
		<tr class="gateway gateway_braintree" <?php if($gateway != "braintree") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="braintree_merchantid"><?php esc_html_e('Merchant ID', 'paid-memberships-pro' );?></label>
			</th>
			<td>
				<input type="text" id="braintree_merchantid" name="braintree_merchantid" value="<?php echo esc_attr($values['braintree_merchantid'])?>" class="regular-text code" />
			</td>
		</tr>
		<tr class="gateway gateway_braintree" <?php if($gateway != "braintree") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="braintree_publickey"><?php esc_html_e('Public Key', 'paid-memberships-pro' );?></label>
			</th>
			<td>
				<input type="text" id="braintree_publickey" name="braintree_publickey" value="<?php echo esc_attr($values['braintree_publickey'])?>" class="regular-text code" />
			</td>
		</tr>
		<tr class="gateway gateway_braintree" <?php if($gateway != "braintree") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="braintree_privatekey"><?php esc_html_e('Private Key', 'paid-memberships-pro' );?></label>
			</th>
			<td>
				<input type="text" id="braintree_privatekey" name="braintree_privatekey" value="<?php echo esc_attr($values['braintree_privatekey'])?>" autocomplete="off" class="regular-text code pmpro-admin-secure-key" />
			</td>
		</tr>
		<tr class="gateway gateway_braintree" <?php if($gateway != "braintree") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="braintree_encryptionkey"><?php esc_html_e('Client-Side Encryption Key', 'paid-memberships-pro' );?></label>
			</th>
			<td>
				<textarea id="braintree_encryptionkey" name="braintree_encryptionkey" autocomplete="off" rows="3" cols="50" class="large-text code pmpro-admin-secure-key"><?php echo esc_textarea($values['braintree_encryptionkey'])?></textarea>
			</td>
		</tr>
		<tr class="gateway gateway_braintree" <?php if($gateway != "braintree") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label><?php esc_html_e('Web Hook URL', 'paid-memberships-pro' );?></label>
			</th>
			<td>
				<p><?php esc_html_e('To fully integrate with Braintree, be sure to set your Web Hook URL to', 'paid-memberships-pro' );?></p>
				<p><code><?php
						//echo admin_url("admin-ajax.php") . "?action=braintree_webhook";
						echo esc_url( add_query_arg( 'action', 'braintree_webhook', admin_url( 'admin-ajax.php' ) ) );
				?></code></p>
			</td>
		</tr>
		<?php
		}

		/**
		 * Code added to checkout preheader.
		 *
		 * @since 2.1
		 */
		static function pmpro_checkout_preheader() {
			global $gateway, $pmpro_level;

			$default_gateway = get_option("pmpro_gateway");

			if(($gateway == "braintree" || $default_gateway == "braintree")) {
				wp_enqueue_script("stripe", "https://js.braintreegateway.com/v1/braintree.js", array(), NULL);
				wp_register_script( 'pmpro_braintree',
                            plugins_url( 'js/pmpro-braintree.js', PMPRO_BASE_FILE ),
                            array( 'jquery' ),
                            PMPRO_VERSION );
				wp_localize_script( 'pmpro_braintree', 'pmpro_braintree', array(
					'encryptionkey' => get_option( 'pmpro_braintree_encryptionkey' )
				));
				wp_enqueue_script( 'pmpro_braintree' );
			}
		}

		/**
		 * Filtering orders at checkout.
		 *
		 * @since 1.8
		 */
		static function pmpro_checkout_order($morder)
		{
			//load up values
			if(isset($_REQUEST['number']))
				$braintree_number = sanitize_text_field($_REQUEST['number']);
			else
				$braintree_number = "";

			if(isset($_REQUEST['expiration_date']))
				$braintree_expiration_date = sanitize_text_field($_REQUEST['expiration_date']);
			else
				$braintree_expiration_date = "";

			if(isset($_REQUEST['cvv']))
				$braintree_cvv = sanitize_text_field($_REQUEST['cvv']);
			else
				$braintree_cvv = "";

			$morder->braintree = new stdClass();
			$morder->braintree->number = $braintree_number;
			$morder->braintree->expiration_date = $braintree_expiration_date;
			$morder->braintree->cvv = $braintree_cvv;

			return $morder;
		}

		/**
		 * Don't require the CVV, but look for cvv (lowercase) that braintree sends
		 *
		 */
		static function pmpro_required_billing_fields($fields)
		{
			unset($fields['CVV']);
			$fields['cvv'] = true;
			return $fields;
		}

		/**
		 * Add some hidden fields and JavaScript to checkout.
		 *
		 */
		static function pmpro_checkout_before_submit_button()
		{
		?>
		<input type='hidden' data-encrypted-name='expiration_date' id='credit_card_exp' />
		<input type='hidden' name='AccountNumber' id='BraintreeAccountNumber' />
		<?php
		}

		/**
		 * Use our own payment fields at checkout. (Remove the name attributes and set some data-encrypted-name attributes.)
		 * @since 1.8
		 */
		static function pmpro_include_payment_information_fields($include)
		{

			//global vars
			global $pmpro_requirebilling, $pmpro_show_discount_code, $discount_code, $CardType, $AccountNumber, $ExpirationMonth, $ExpirationYear;

			//include ours
			?>
			<fieldset id="pmpro_payment_information_fields" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset', 'pmpro_payment_information_fields' ) ); ?>" <?php if ( ! $pmpro_requirebilling || apply_filters( 'pmpro_hide_payment_information_fields', false ) ) { ?>style="display: none;"<?php } ?>>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
						<legend class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_legend' ) ); ?>">
							<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_heading pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Payment Information', 'paid-memberships-pro' ); ?></h2>
						</legend>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_payment-account-number', 'pmpro_payment-account-number' ) ); ?>">
                                <input type="hidden" id="CardType" name="CardType" value="<?php echo esc_attr($CardType);?>" />
								<script>
									jQuery(document).ready(function() {
											jQuery('#AccountNumber').validateCreditCard(function(result) {
												var cardtypenames = {
													"amex"                      : "American Express",
													"diners_club_carte_blanche" : "Diners Club Carte Blanche",
													"diners_club_international" : "Diners Club International",
													"discover"                  : "Discover",
													"jcb"                       : "JCB",
													"laser"                     : "Laser",
													"maestro"                   : "Maestro",
													"mastercard"                : "Mastercard",
													"visa"                      : "Visa",
													"visa_electron"             : "Visa Electron"
												};

												if(result.card_type)
													jQuery('#CardType').val(cardtypenames[result.card_type.name]);
												else
													jQuery('#CardType').val('Unknown Card Type');
											});
									});
								</script>
								<label for="AccountNumber" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Card Number', 'paid-memberships-pro' );?></label>
								<input id="AccountNumber" name="AccountNumber" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'AccountNumber' ) ); ?>" type="text" value="<?php echo esc_attr($AccountNumber)?>" data-encrypted-name="number" autocomplete="off" />
							</div>
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_cols-2' ) ); ?>">
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-select pmpro_payment-expiration', 'pmpro_payment-expiration' ) ); ?>">
									<label for="ExpirationMonth" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Expiration Date', 'paid-memberships-pro' );?></label>
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields-inline' ) ); ?>">
										<select id="ExpirationMonth" name="ExpirationMonth" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-select', 'ExpirationMonth' ) ); ?>">
											<option value="01" <?php if($ExpirationMonth == "01") { ?>selected="selected"<?php } ?>>01</option>
											<option value="02" <?php if($ExpirationMonth == "02") { ?>selected="selected"<?php } ?>>02</option>
											<option value="03" <?php if($ExpirationMonth == "03") { ?>selected="selected"<?php } ?>>03</option>
											<option value="04" <?php if($ExpirationMonth == "04") { ?>selected="selected"<?php } ?>>04</option>
											<option value="05" <?php if($ExpirationMonth == "05") { ?>selected="selected"<?php } ?>>05</option>
											<option value="06" <?php if($ExpirationMonth == "06") { ?>selected="selected"<?php } ?>>06</option>
											<option value="07" <?php if($ExpirationMonth == "07") { ?>selected="selected"<?php } ?>>07</option>
											<option value="08" <?php if($ExpirationMonth == "08") { ?>selected="selected"<?php } ?>>08</option>
											<option value="09" <?php if($ExpirationMonth == "09") { ?>selected="selected"<?php } ?>>09</option>
											<option value="10" <?php if($ExpirationMonth == "10") { ?>selected="selected"<?php } ?>>10</option>
											<option value="11" <?php if($ExpirationMonth == "11") { ?>selected="selected"<?php } ?>>11</option>
											<option value="12" <?php if($ExpirationMonth == "12") { ?>selected="selected"<?php } ?>>12</option>
										</select>/<select id="ExpirationYear" name="ExpirationYear" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-select', 'ExpirationYear' ) ); ?>">
										<?php
											$num_years = apply_filters( 'pmpro_num_expiration_years', 10 );

											for ( $i = date_i18n( 'Y' ); $i < intval( date_i18n( 'Y' ) ) + intval( $num_years ); $i++ )
											{
												?>
												<option value="<?php echo esc_attr( $i ) ?>" <?php if($ExpirationYear == $i) { ?>selected="selected"<?php } elseif($i == date_i18n( 'Y' ) + 1) { ?>selected="selected"<?php } ?>><?php echo esc_html( $i )?></option>
												<?php
											}
										?>
										</select>
									</div> <!-- end pmpro_form_fields-inline -->
								</div>
								<?php
									$pmpro_show_cvv = apply_filters("pmpro_show_cvv", true);
									if($pmpro_show_cvv) { ?>
										<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_payment-cvv', 'pmpro_payment-cvv' ) ); ?>">
											<label for="CVV" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('CVV', 'paid-memberships-pro' );?></label>
											<input id="CVV" name="cvv" type="text" size="4" value="<?php if(!empty( $_REQUEST['CVV'])) { echo esc_attr(sanitize_text_field($_REQUEST['CVV'])); }?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'CVV' ) ); ?>" data-encrypted-name="cvv" />
										</div>
								<?php } ?>
							</div> <!-- end pmpro_cols-2 -->
							<?php if ( $pmpro_show_discount_code ) { ?>
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_cols-2' ) ); ?>">
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_payment-discount-code', 'pmpro_payment-discount-code' ) ); ?>">
										<label for="pmpro_discount_code" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e( 'Discount Code', 'paid-memberships-pro' ); ?></label>
										<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields-inline' ) ); ?>">
											<input id="pmpro_discount_code" name="pmpro_discount_code" type="text" value="<?php echo esc_attr( $discount_code ) ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text pmpro_alter_price', 'pmpro_discount_code' ) ); ?>" />
											<input aria-label="<?php esc_html_e( 'Apply discount code', 'paid-memberships-pro' ); ?>" type="button" id="discount_code_button" name="discount_code_button" value="<?php esc_attr_e( 'Apply', 'paid-memberships-pro' ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit-discount-code', 'discount_code_button' ) ); ?>" />
										</div> <!-- end pmpro_form_fields-inline -->
										<p id="discount_code_message" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message', 'discount_code_message' ) ); ?>" style="display: none;"></p>
									</div>
								</div> <!-- end pmpro_cols-2 -->
							<?php } ?>
						</div> <!-- end pmpro_form_fields -->
					</div> <!-- end pmpro_card_content -->
				</div> <!-- end pmpro_card -->
			</fieldset> <!-- end pmpro_payment_information_fields -->
			<?php

			//don't include the default
			return false;
		}

		/**
		 * Process checkout.
		 *
		 */
		function process(&$order)
		{
			//check for initial payment
			if(floatval($order->subtotal) == 0)
			{
				//just subscribe
				return $this->subscribe($order);
			}
			else
			{
				//charge then subscribe
				if($this->charge($order))
				{
					if(pmpro_isLevelRecurring($order->membership_level))
					{
						if($this->subscribe($order))
						{
							//yay!
							return true;
						}
						else
						{
							//try to refund initial charge
							return false;
						}
					}
					else
					{
						//only a one time charge
						$order->status = "success";	//saved on checkout page
						return true;
					}
				}
				else
				{
					if(empty($order->error)) {

					    if ( !self::$is_loaded ) {

					        $order->error = esc_html__("Payment error: Please contact the webmaster (braintree-load-error)", "paid-memberships-pro");

                        } else {

						    $order->error = esc_html__( "Unknown error: Initial payment failed.", "paid-memberships-pro" );
					    }
                    }

					return false;
				}
			}
		}

		function charge(&$order)
		{
		    if ( ! self::$is_loaded ) {

                $order->error = esc_html__("Payment error: Please contact the webmaster (braintree-load-error)", "paid-memberships-pro");
                return false;
            }

			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();

			//what amount to charge?
			$tax = $order->getTax(true);
			$amount = pmpro_round_price_as_string((float)$order->subtotal + (float)$tax);

			//create a customer
			$this->getCustomer($order);
			if(empty($this->customer) || !empty($order->error))
			{
				//failed to create customer
				return false;
			}

			//charge
			try
			{
				/**
				 * Filter the array of parameters to pass to the Braintree API for a sale transaction.
				 *
				 * @since 3.0
				 *
				 * @param array $braintree_sale_array Array of parameters to pass to the Braintree API for a sale transaction.
				 * @param array The new sale array.
				 */
				$braintree_sale_array = apply_filters( 'pmpro_braintree_transaction_sale_array', array(
					'amount' => $amount,
					'customerId' => $this->customer->id
					)
				);

				$response = Braintree_Transaction::sale( $braintree_sale_array );
			}
			catch (Exception $e)
			{
				//$order->status = "error";
				$order->errorcode = true;
				$order->error = "Error: " . $e->getMessage() . " (" . get_class($e) . ")";
				$order->shorterror = $order->error;
				return false;
			}

			if($response->success)
			{
				//successful charge
				$transaction_id = $response->transaction->id;
				try {
					$response = Braintree_Transaction::submitForSettlement( $transaction_id );
				} catch ( Exception $exception ) {
					$order->errorcode = true;
					$order->error = "Error: " . $exception->getMessage() . " (" . get_class($exception) . ")";
					$order->shorterror = $order->error;
					return false;
                }

				if($response->success)
				{
					$order->payment_transaction_id = $transaction_id;
					$order->updateStatus("success");
					return true;
				}
				else
				{
					$order->errorcode = true;
					$order->error = esc_html__("Error during settlement:", 'paid-memberships-pro' ) . " " . $response->message;
					$order->shorterror = $response->message;
					return false;
				}
			}
			else
			{
				//$order->status = "error";
				$order->errorcode = true;
				$order->error = esc_html__("Error during charge:", 'paid-memberships-pro' ) . " " . $response->message;
				$order->shorterror = $response->message;
				return false;
			}
		}

		/*
			This function will return a Braintree customer object.
			If $this->customer is set, it returns it.
			It first checks if the order has a subscription_transaction_id. If so, that's the customer id.
			If not, it checks for a user_id on the order and searches for a customer id in the user meta.
			If a customer id is found, it checks for a customer through the Braintree API.
			If a customer is found and there is an AccountNumber on the order passed, it will update the customer.
			If no customer is found and there is an AccountNumber on the order passed, it will create a customer.
		*/
		function getCustomer(&$order, $force = false)
		{
            if ( ! self::$is_loaded ) {
	            $order->error = esc_html__("Payment error: Please contact the webmaster (braintree-load-error)", 'paid-memberships-pro');
	            return false;
            }

			global $current_user;

			//already have it?
			if(!empty($this->customer) && !$force)
				return $this->customer;

			//try based on user id
			if(!empty($order->user_id))
				$user_id = $order->user_id;

			//if no id passed, check the current user
			if(empty($user_id) && !empty($current_user->ID))
				$user_id = $current_user->ID;

			//check for a braintree customer id
			if(!empty($user_id))
			{
				$customer_id = get_user_meta($user_id, "pmpro_braintree_customerid", true);
			}

			$nameparts = pnp_split_full_name( $order->billing->name );

			//check for an existing Braintree customer
			if(!empty($customer_id))
			{
				try
				{
					$this->customer = Braintree_Customer::find($customer_id);

					//update the customer address, description and card
					if( ! empty( $order->braintree ) && ! empty( $order->braintree->number ) ) {
						//put data in array for Braintree API calls
						$update_array = array(
							'firstName' => empty( $nameparts['fname'] ) ? '' : $nameparts['fname'],
							'lastName' => empty( $nameparts['lname'] ) ? '' : $nameparts['lname'],
							'creditCard' => array(
								'number' => $order->braintree->number,
								'expirationDate' => $order->braintree->expiration_date,
								'cvv' => $order->braintree->cvv,
								'cardholderName' => trim( $order->billing->name ),
								'options' => array(
									'updateExistingToken' => $this->customer->creditCards[0]->token
								)
							)
						);

						//address too?
						if(!empty($order->billing))
							//make sure Address2 is set
							if(!isset($order->Address2))
								$order->Address2 = '';

							//add billing address to array
							$update_array['creditCard']['billingAddress'] = array(
								'firstName' => empty( $nameparts['fname'] ) ? '' : $nameparts['fname'],
								'lastName' => empty( $nameparts['lname'] ) ? '' : $nameparts['lname'],
								'streetAddress' => $order->billing->street,
								'extendedAddress' => $order->billing->street2,
								'locality' => $order->billing->city,
								'region' => $order->billing->state,
								'postalCode' => $order->billing->zip,
								'countryCodeAlpha2' => $order->billing->country,
								'options' => array(
									'updateExisting' => true
								)
							);

							try {
								//update
								$response = Braintree_Customer::update($customer_id, $update_array);
                            } catch ( Exception $exception ) {
								$order->error = sprintf( esc_html__("Failed to update customer: %s", 'paid-memberships-pro' ), $exception->getMessage() );
								$order->shorterror = $order->error;
								return false;
                            }

						if($response->success)
						{
							$this->customer = $response->customer;
							return $this->customer;
						}
						else
						{
							$order->error = esc_html__("Failed to update customer.", 'paid-memberships-pro' ) . " " . $response->message;
							$order->shorterror = $order->error;
							return false;
						}
					}

					return $this->customer;
				}
				catch (Exception $e)
				{
					//assume no customer found
				}
			}

			//no customer id, create one
			if(!empty($order->accountnumber))
			{
				$user = get_userdata($user_id);
				try
				{
					$result = Braintree_Customer::create(array(
						'firstName' => empty($nameparts['fname']) ? '' : $nameparts['fname'],
						'lastName' => empty($nameparts['lname']) ? '' : $nameparts['lname'],
						'email' => empty( $user->user_email ) ? '' : $user->user_email,
						'phone' => $order->billing->phone,
						'creditCard' => array(
							'number' => $order->braintree->number,
							'expirationDate' => $order->braintree->expiration_date,
							'cvv' => $order->braintree->cvv,
							'cardholderName' =>  trim($order->billing->name),
							'billingAddress' => array(
								'firstName' => empty($nameparts['fname']) ? '' : $nameparts['fname'],
								'lastName' => empty($nameparts['lname']) ? '' : $nameparts['lname'],
								'streetAddress' => $order->billing->street,
								'extendedAddress' => $order->billing->street2,
								'locality' => $order->billing->city,
								'region' => $order->billing->state,
								'postalCode' => $order->billing->zip,
								'countryCodeAlpha2' => $order->billing->country
							)
						)
					));

					if($result->success)
					{
						$this->customer = $result->customer;
					}
					else
					{
						$order->error = esc_html__("Failed to create customer.", 'paid-memberships-pro' ) . " " . $result->message;
						$order->shorterror = $order->error;
						return false;
					}
				}
				catch (Exception $e)
				{
					$order->error = esc_html__("Error creating customer record with Braintree:", 'paid-memberships-pro' ) . $e->getMessage() . " (" . get_class($e) . ")";
					$order->shorterror = $order->error;
					return false;
				}

				//if we have no user id, we need to set the customer id after the user is created
				if(empty($user_id))
				{
					global $pmpro_braintree_customerid;
					$pmpro_braintree_customerid = $this->customer->id;
					add_action('user_register', array('PMProGateway_braintree','user_register'));
				}
				else
					update_user_meta($user_id, "pmpro_braintree_customerid", $this->customer->id);

				return $this->customer;
			}

			return false;
		}

		/**
         * Create Braintree Subscription
         *
		 * @param \MemberOrder $order
		 *
		 * @return bool
		 */
		function subscribe(&$order)
		{
			if ( ! self::$is_loaded ) {
				$order->error = esc_html__("Payment error: Please contact the webmaster (braintree-load-error)", 'paid-memberships-pro');
				return false;
			}

			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();

			//set up customer
			$this->getCustomer($order);
			if(empty($this->customer) || !empty($order->error))
				return false;	//error retrieving customer

			//figure out the amounts
			$level = $order->getMembershipLevelAtCheckout();
			$amount = $level->billing_amount;
			$amount_tax = $order->getTaxForPrice($amount);
			$amount = pmpro_round_price_as_string((float)$amount + (float)$amount_tax);

			// Get the profile start date.
			$start_ts = pmpro_calculate_profile_start_date( $order, 'U' );
			$now =  strtotime( date('Y-m-d\T00:00:00', current_time('timestamp' ) ), current_time('timestamp' ) );

			//convert back to days
			$trial_period_days = ceil(abs( $now - $start_ts ) / 86400);

			//now add the actual trial set by the site
			if(!empty($level->trial_limit))
			{
				$trialOccurrences = (int)$level->trial_limit;
				if( $level->cycle_period == "Year")
					$trial_period_days = $trial_period_days + (365 * $level->cycle_number * $trialOccurrences);	//annual
				elseif( $level->cycle_period == "Day")
					$trial_period_days = $trial_period_days + (1 * $level->cycle_number * $trialOccurrences);		//daily
				elseif( $level->cycle_period == "Week")
					$trial_period_days = $trial_period_days + (7 * $level->cycle_number * $trialOccurrences);	//weekly
				else
					$trial_period_days = $trial_period_days + (30 * $level->cycle_number * $trialOccurrences);	//assume monthly
			}

			//subscribe to the plan
			try
			{

				$details = array(
				  'paymentMethodToken' => $this->customer->creditCards[0]->token,
				  'planId' => $this->get_plan_id( $order->membership_id ),
				  'price' => $amount
				);

				if(!empty($trial_period_days))
				{
					$details['trialPeriod'] = true;
					$details['trialDuration'] = $trial_period_days;
					$details['trialDurationUnit'] = "day";
				}

				if(!empty($level->billing_limit))
					$details['numberOfBillingCycles'] = $level->billing_limit;

				/**
				 * Filter the Braintree Subscription create array.
				 *
				 * @since 3.0
				 *
				 * @param array $details Array of details to create the subscription.
				 * @return array $details Array of details to create the subscription.
				 */
				$details = apply_filters( 'pmpro_braintree_subscription_create_array', $details);
				$result = Braintree_Subscription::create($details);
			}
			catch (Exception $e)
			{
				$order->error = sprint( esc_html__("Error subscribing customer to plan with Braintree: %s (%s)", 'paid-memberships-pro' ), $e->getMessage(), get_class($e) );
				//return error
				$order->shorterror = $order->error;
				return false;
			}

			if($result->success)
			{
				//if we got this far, we're all good
				$order->status = "success";
				$order->subscription_transaction_id = $result->subscription->id;
				return true;
			}
			else
			{
				$order->error = sprintf( esc_html__("Failed to subscribe with Braintree: %s", 'paid-memberships-pro' ),  $result->message );
				$order->shorterror = $result->message;
				return false;
			}
		}

		function update(&$order)
		{
			if ( ! self::$is_loaded ) {
				$order->error = esc_html__("Payment error: Please contact the webmaster (braintree-load-error)", 'paid-memberships-pro');
				return false;
			}

			//we just have to run getCustomer which will look for the customer and update it with the new token
			$this->getCustomer($order, true);

			if(!empty($this->customer) && empty($order->error))
			{
				return true;
			}
			else
			{
				return false;	//couldn't find the customer
			}
		}

		/**
      * Cancel order and Braintree Subscription if applicable
      *
		  * @param \MemberOrder $order
		  *
		  * @return bool
		  */
		function cancel(&$order)
		{
			if ( ! self::$is_loaded ) {
				$order->error = esc_html__("Payment error: Please contact the webmaster (braintree-load-error)", 'paid-memberships-pro');
				return false;
			}

			if ( isset( $_POST['bt_payload']) && isset( $_POST['bt_payload']) ) {

				try {
					// Note: Braintree needs the raw data.
					$webhookNotification = Braintree_WebhookNotification::parse( $_POST['bt_signature'], $_POST['bt_payload'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					if ( Braintree_WebhookNotification::SUBSCRIPTION_CANCELED === $webhookNotification->kind ) {
					    // Return, we're already processing the cancellation
					    return true;
		            }
				} catch ( \Exception $e ) {
				    // Don't do anything
				}
			}

			// Always cancel, even if Braintree fails
			$order->updateStatus("cancelled" );

			//require a subscription id
			if(empty($order->subscription_transaction_id))
				return false;

			//find the customer
			if(!empty($order->subscription_transaction_id))
			{
				//cancel
				try
				{
					$result = Braintree_Subscription::cancel($order->subscription_transaction_id);
				}
				catch(Exception $e)
				{
					$order->error = sprintf( esc_html__("Could not find the subscription. %s", 'paid-memberships-pro' ),  $e->getMessage() );
					$order->shorterror = $order->error;
					return false;	//no subscription found
				}

				if($result->success)
				{
					return true;
				}
				else
				{
					$order->error = sprintf( esc_html__("Could not find the subscription. %s", 'paid-memberships-pro' ), $result->message );
					$order->shorterror = $order->error;
					return false;	//no subscription found
				}
			}
			else
			{
				$order->error = esc_html__("Could not find the subscription.", 'paid-memberships-pro' );
				$order->shorterror = $order->error;
				return false;	//no customer found
			}
		}

		/*
			Save Braintree customer id after the user is registered.
		*/
		static function user_register($user_id)
		{
			global $pmpro_braintree_customerid;
			if(!empty($pmpro_braintree_customerid))
			{
				update_user_meta($user_id, 'pmpro_braintree_customerid', $pmpro_braintree_customerid);
			}
		}

		/**
		 * Gets the Braintree plan ID for a given level ID
		 * @param  int $level_id level to get plan ID for
		 * @return string        Braintree plan ID
		 */
	static function get_plan_id( $level_id ) {
		/**
			* Filter pmpro_braintree_plan_id
			*
			* Used to change the Braintree plan ID for a given level
			*
			* @since 2.1.0
			*
			* @param string  $plan_id for the given level
			* @param int $level_id the level id to make a plan id for
			*/
			return apply_filters( 'pmpro_braintree_plan_id', 'pmpro_' . $level_id, $level_id );
	}

	function get_subscription( &$order ) {
		// Does order have a subscription?
		if ( empty( $order ) || empty( $order->subscription_transaction_id ) ) {
			return false;
		}

		try {
			$subscription = Braintree_Subscription::find( $order->subscription_transaction_id );
		} catch ( Exception $e ) {
			$order->error      = esc_html__( "Error getting subscription with Braintree:", 'paid-memberships-pro' ) . $e->getMessage();
			$order->shorterror = $order->error;
			return false;
		}

		return $subscription;
	}

	/**
	 * Filter pmpro_next_payment to get date via API if possible
	 */
	static function pmpro_next_payment( $timestamp, $user_id, $order_status ) {
		// Check that we have a user ID...
		if ( ! empty( $user_id ) ) {
			// Get last order...
			$order = new MemberOrder();
			$order->getLastMemberOrder( $user_id, $order_status );

			// Check if this is a Braintree order with a subscription transaction id...
			if ( ! empty( $order->id ) && ! empty( $order->subscription_transaction_id ) && $order->gateway == "braintree" ) {
				// Get the subscription and return the next billing date.
				$subscription = $order->Gateway->get_subscription( $order );
				if ( ! empty( $subscription ) ) {
					$timestamp = $subscription->nextBillingDate->getTimestamp();
				}
			}
		}

		return $timestamp;
	}

	/**
	 * Pull subscription info from Braintree.
	 *
	 * @param PMPro_Subscription $subscription to pull data for.
	 *
	 * @return string|null Error message is returned if update fails.
	 */
	public function update_subscription_info( $subscription ) {
		// Make sure that we can access the API.
		if ( ! self::$is_loaded ) {
			return __( "Cannot access Braintree API.", 'paid-memberships-pro' );
		}

		// Get the subscription from Braintree
		try {
			$braintree_subscription = Braintree_Subscription::find( $subscription->get_subscription_transaction_id() );
			$braintree_plan         = $this->getPlanByID( $braintree_subscription->planId );
		} catch ( Exception $e ) {
			return __( "Error getting subscription with Braintree:", 'paid-memberships-pro' ) . $e->getMessage();
		}

		if ( ! empty( $braintree_subscription ) ) {
			$update_array = array(
				'startdate' => $braintree_subscription->createdAt->format( 'Y-m-d H:i:s' ),
			);
			if ( in_array( $braintree_subscription->status, array( 'Active', 'Pending', 'PastDue' ) ) ) {
				// Subscription is active.
				$update_array['status'] = 'active';
				$update_array['next_payment_date'] = $braintree_subscription->nextBillingDate->format( 'Y-m-d H:i:s' );
				$update_array['billing_amount'] = $braintree_subscription->price;
				$update_array['cycle_number']   = $braintree_plan->billingFrequency;
				$update_array['cycle_period']   = 'Month'; // Braintree only has cycle periods in months.
			} else {
				// Subscription is no longer active.
				// Can't fill subscription end date, $braintree_subscription only has the date of the last payment.
				$update_array['status'] = 'cancelled';
			}
			$subscription->set( $update_array );
		}
	}

	/**
	 * Cancels a subscription in Braintree.
	 *
	 * @param PMPro_Subscription $subscription to cancel.
	 * @return bool True if subscription was canceled, false if not.
	 */
	function cancel_subscription( $subscription ) {
		// Make sure that we can access the API.
		if ( ! self::$is_loaded ) {
			return false;
		}
		
		// Cancel the subscription in Braintree.
		try {
			$result = Braintree_Subscription::cancel( $subscription->get_subscription_transaction_id() );
		} catch( Exception $e ) {
			return false;
		}

		return (bool) $result->success;
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
			'payment_method_updates' => 'all'
		);

		if ( empty( $supports[$feature] ) ) {
			return false;
		}

		return $supports[$feature];
	}
}
