<?php
/**
 * Blockonomics payment gateway.
 *
 * @package PMPro_Blockonomics
 */

class PMProGateway_blockonomics extends PMProGateway {
	/**
	 * Gateway slug.
	 *
	 * @var string
	 */
	public $gateway;

	/**
	 * Constructor.
	 *
	 * @param string|null $gateway Gateway slug.
	 */
	public function __construct( $gateway = null ) {
		$this->gateway = $gateway;
	}

	/**
	 * Run on WP init.
	 */
	public static function init() {
		add_filter( 'pmpro_gateways', array( 'PMProGateway_blockonomics', 'pmpro_gateways' ) );
		add_filter( 'pmpro_include_billing_address_fields', array( 'PMProGateway_blockonomics', 'pmpro_include_billing_address_fields' ) );
		add_filter( 'pmpro_include_payment_information_fields', array( 'PMProGateway_blockonomics', 'pmpro_include_payment_information_fields' ) );
		add_filter( 'pmpro_required_billing_fields', array( 'PMProGateway_blockonomics', 'pmpro_required_billing_fields' ) );
		add_filter( 'pmpro_confirmation_payment_incomplete_message', array( 'PMProGateway_blockonomics', 'pmpro_confirmation_payment_incomplete_message' ), 10, 2 );
	}

	/**
	 * Make sure Blockonomics is in the gateways list.
	 *
	 * @param array $gateways Gateways.
	 * @return array
	 */
	public static function pmpro_gateways( $gateways ) {
		if ( empty( $gateways['blockonomics'] ) ) {
			$gateways['blockonomics'] = __( 'Blockonomics Bitcoin', 'pmpro-blockonomics' );
		}

		return $gateways;
	}

	/**
	 * Get a description for this gateway.
	 *
	 * @return string
	 */
	public static function get_description_for_gateway_settings() {
		return esc_html__( 'Accept one-time Bitcoin payments through Blockonomics. Payments are sent directly to your wallet and PMPro activates the membership after a confirmed callback.', 'pmpro-blockonomics' );
	}

	/**
	 * Check if the gateway supports a certain feature.
	 *
	 * @param string $feature Feature to check.
	 * @return bool
	 */
	public static function supports( $feature ) {
		$supports = array(
			'check_token_orders' => true,
		);

		return ! empty( $supports[ $feature ] );
	}

	/**
	 * Show settings fields.
	 */
	public static function show_settings_fields() {
		$api_key         = get_option( 'pmpro_blockonomics_api_key' );
		$callback_secret = get_option( 'pmpro_blockonomics_callback_secret' );
		$callback_url    = self::get_callback_url();
		?>
		<p>
			<?php
			printf(
				/* translators: %s: URL to the Blockonomics API documentation. */
				esc_html__( 'Connect PMPro to Blockonomics using the Payments API. For more details, see the %s.', 'pmpro-blockonomics' ),
				'<a href="https://developers.blockonomics.co/reference" target="_blank" rel="nofollow noopener">' . esc_html__( 'Blockonomics API documentation', 'pmpro-blockonomics' ) . '</a>'
			);
			?>
		</p>
		<div id="pmpro_blockonomics" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Settings', 'pmpro-blockonomics' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<table class="form-table">
					<tbody>
						<tr class="gateway gateway_blockonomics">
							<th scope="row" valign="top">
								<label for="blockonomics_api_key"><?php esc_html_e( 'API Key', 'pmpro-blockonomics' ); ?></label>
							</th>
							<td>
								<input type="password" id="blockonomics_api_key" name="blockonomics_api_key" class="regular-text code" autocomplete="off" value="<?php echo esc_attr( $api_key ); ?>" />
								<p class="description"><?php esc_html_e( 'Your Blockonomics merchant API key. PMPro uses this to generate a unique Bitcoin address and price for each checkout.', 'pmpro-blockonomics' ); ?></p>
							</td>
						</tr>
						<tr class="gateway gateway_blockonomics">
							<th scope="row" valign="top">
								<label for="blockonomics_callback_secret"><?php esc_html_e( 'Callback Secret', 'pmpro-blockonomics' ); ?></label>
							</th>
							<td>
								<input type="text" id="blockonomics_callback_secret" name="blockonomics_callback_secret" class="regular-text code" autocomplete="off" value="<?php echo esc_attr( $callback_secret ); ?>" />
								<p class="description"><?php esc_html_e( 'A private value included in the callback URL so PMPro can reject unsigned payment updates. Leave blank when saving to generate a new secret.', 'pmpro-blockonomics' ); ?></p>
							</td>
						</tr>
						<tr class="gateway gateway_blockonomics">
							<th scope="row" valign="top">
								<?php esc_html_e( 'HTTP Callback URL', 'pmpro-blockonomics' ); ?>
							</th>
							<td>
								<input type="text" readonly class="large-text code" value="<?php echo esc_attr( $callback_url ); ?>" onclick="this.select();" />
								<p class="description"><?php esc_html_e( 'Copy this URL into the HTTP Callback URL field in your Blockonomics merchant settings.', 'pmpro-blockonomics' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Save settings fields.
	 */
	public static function save_settings_fields() {
		if ( isset( $_REQUEST['blockonomics_api_key'] ) ) {
			update_option( 'pmpro_blockonomics_api_key', sanitize_text_field( wp_unslash( $_REQUEST['blockonomics_api_key'] ) ) );
		}

		if ( isset( $_REQUEST['blockonomics_callback_secret'] ) ) {
			$callback_secret = sanitize_text_field( wp_unslash( $_REQUEST['blockonomics_callback_secret'] ) );
			if ( empty( $callback_secret ) ) {
				$callback_secret = wp_generate_password( 32, false, false );
			}
			update_option( 'pmpro_blockonomics_callback_secret', $callback_secret );
		}
	}

	/**
	 * Hide billing address fields for Blockonomics checkout.
	 *
	 * @param bool $include Whether to include billing address fields.
	 * @return bool
	 */
	public static function pmpro_include_billing_address_fields( $include ) {
		return 'blockonomics' === pmpro_getGateway() ? false : $include;
	}

	/**
	 * Hide credit card fields for Blockonomics checkout.
	 *
	 * @param bool $include Whether to include payment fields.
	 * @return bool
	 */
	public static function pmpro_include_payment_information_fields( $include ) {
		return 'blockonomics' === pmpro_getGateway() ? false : $include;
	}

	/**
	 * Remove billing fields required by card gateways.
	 *
	 * @param array $fields Required billing fields.
	 * @return array
	 */
	public static function pmpro_required_billing_fields( $fields ) {
		if ( 'blockonomics' !== pmpro_getGateway() ) {
			return $fields;
		}

		foreach ( array( 'bfirstname', 'blastname', 'baddress1', 'bcity', 'bstate', 'bzipcode', 'bphone', 'bemail', 'bcountry', 'CardType', 'AccountNumber', 'ExpirationMonth', 'ExpirationYear', 'CVV' ) as $field ) {
			unset( $fields[ $field ] );
		}

		return $fields;
	}

	/**
	 * Process checkout.
	 *
	 * @param MemberOrder $order Order to process.
	 * @return bool
	 */
	public function process( &$order ) {
		$level = $order->getMembershipLevelAtCheckout();
		if ( pmpro_isLevelRecurring( $level ) ) {
			$order->error = __( 'Blockonomics Bitcoin payments can only be used for one-time membership checkouts.', 'pmpro-blockonomics' );
			return false;
		}

		if ( empty( $order->total ) || (float) $order->total <= 0 ) {
			$order->error = __( 'Blockonomics cannot process a checkout with no initial payment.', 'pmpro-blockonomics' );
			return false;
		}

		$api_key = get_option( 'pmpro_blockonomics_api_key' );
		if ( empty( $api_key ) ) {
			$order->error = __( 'Blockonomics is not configured. Please contact the site owner.', 'pmpro-blockonomics' );
			return false;
		}

		$callback_secret = get_option( 'pmpro_blockonomics_callback_secret' );
		if ( empty( $callback_secret ) ) {
			$order->error = __( 'Blockonomics callback security is not configured. Please contact the site owner.', 'pmpro-blockonomics' );
			return false;
		}

		$currency = pmpro_getOption( 'currency' );
		if ( empty( $currency ) ) {
			$currency = 'USD';
		}

		$btc_price = self::get_btc_price( $currency, $api_key );
		if ( is_wp_error( $btc_price ) ) {
			$order->error = $btc_price->get_error_message();
			return false;
		}

		$btc_amount = (float) $order->total / $btc_price;
		$satoshis   = (int) ceil( $btc_amount * 100000000 );
		if ( $satoshis <= 0 ) {
			$order->error = __( 'Could not calculate the Bitcoin payment amount for this order.', 'pmpro-blockonomics' );
			return false;
		}

		$address = self::get_new_address( $api_key );
		if ( is_wp_error( $address ) ) {
			$order->error = $address->get_error_message();
			return false;
		}

		$order->payment_type            = 'Bitcoin';
		$order->cardtype                = '';
		$order->accountnumber           = '';
		$order->expirationmonth         = '';
		$order->expirationyear          = '';
		$order->payment_transaction_id  = $address;
		$order->subscription_transaction_id = '';
		$order->status                  = 'token';
		$order->saveOrder();

		pmpro_save_checkout_data_to_order( $order );
		update_pmpro_membership_order_meta( $order->id, 'blockonomics_address', $address );
		update_pmpro_membership_order_meta( $order->id, 'blockonomics_expected_satoshis', $satoshis );
		update_pmpro_membership_order_meta( $order->id, 'blockonomics_expected_btc', self::format_btc_amount( $satoshis ) );
		update_pmpro_membership_order_meta( $order->id, 'blockonomics_currency', strtoupper( $currency ) );
		update_pmpro_membership_order_meta( $order->id, 'blockonomics_btc_price', $btc_price );

		wp_redirect( pmpro_url( 'confirmation', '?pmpro_level=' . intval( $order->membership_id ) ) );
		exit;
	}

	/**
	 * Check whether a token order has been paid.
	 *
	 * @param MemberOrder $order Order to check.
	 * @return true|string
	 */
	public function check_token_order( $order ) {
		if ( 'token' !== $order->status && 'pending' !== $order->status ) {
			return __( 'This is not an unpaid Blockonomics order.', 'pmpro-blockonomics' );
		}

		$address = get_pmpro_membership_order_meta( $order->id, 'blockonomics_address', true );
		if ( empty( $address ) ) {
			return __( 'No Blockonomics payment address was found for this order.', 'pmpro-blockonomics' );
		}

		return __( 'Waiting for a confirmed Blockonomics payment callback.', 'pmpro-blockonomics' );
	}

	/**
	 * Show Blockonomics payment details on the confirmation page.
	 *
	 * @param string      $message Existing message.
	 * @param MemberOrder $order Current order.
	 * @return string
	 */
	public static function pmpro_confirmation_payment_incomplete_message( $message, $order ) {
		if ( empty( $order->gateway ) || 'blockonomics' !== $order->gateway ) {
			return $message;
		}

		$address      = get_pmpro_membership_order_meta( $order->id, 'blockonomics_address', true );
		$expected_btc = get_pmpro_membership_order_meta( $order->id, 'blockonomics_expected_btc', true );
		if ( empty( $address ) || empty( $expected_btc ) ) {
			return $message;
		}

		$bitcoin_uri = 'bitcoin:' . rawurlencode( $address ) . '?amount=' . rawurlencode( $expected_btc );

		$output  = '<p>' . esc_html__( 'Send the exact Bitcoin amount below. Your membership will be activated after Blockonomics confirms the payment.', 'pmpro-blockonomics' ) . '</p>';
		$output .= '<p><strong>' . esc_html__( 'Bitcoin Amount:', 'pmpro-blockonomics' ) . '</strong> <code>' . esc_html( $expected_btc ) . ' BTC</code></p>';
		$output .= '<p><strong>' . esc_html__( 'Bitcoin Address:', 'pmpro-blockonomics' ) . '</strong> <code>' . esc_html( $address ) . '</code></p>';
		$output .= '<p><a class="' . esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit' ) ) . '" href="' . esc_url( $bitcoin_uri ) . '">' . esc_html__( 'Open Bitcoin Wallet', 'pmpro-blockonomics' ) . '</a></p>';

		return $output;
	}

	/**
	 * Handle a Blockonomics callback.
	 */
	public static function handle_callback() {
		pmpro_doing_webhook( 'blockonomics', true );

		$configured_secret = get_option( 'pmpro_blockonomics_callback_secret' );
		$request_secret    = isset( $_REQUEST['secret'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['secret'] ) ) : '';
		if ( empty( $configured_secret ) || empty( $request_secret ) || ! hash_equals( $configured_secret, $request_secret ) ) {
			status_header( 403 );
			echo esc_html__( 'Invalid Blockonomics callback secret.', 'pmpro-blockonomics' );
			exit;
		}

		$address = isset( $_REQUEST['addr'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['addr'] ) ) : '';
		$status  = isset( $_REQUEST['status'] ) ? intval( $_REQUEST['status'] ) : null;
		$value   = isset( $_REQUEST['value'] ) ? intval( $_REQUEST['value'] ) : 0;
		$txid    = isset( $_REQUEST['txid'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['txid'] ) ) : '';

		if ( empty( $address ) || null === $status ) {
			status_header( 400 );
			echo esc_html__( 'Missing Blockonomics callback fields.', 'pmpro-blockonomics' );
			exit;
		}

		$order = self::get_order_by_payment_address( $address );
		if ( empty( $order ) || empty( $order->id ) ) {
			status_header( 404 );
			echo esc_html__( 'Blockonomics order not found.', 'pmpro-blockonomics' );
			exit;
		}

		if ( $status < 0 ) {
			$order->status = 'error';
			$order->add_order_note( sprintf( __( 'Blockonomics reported payment status %1$d for transaction %2$s.', 'pmpro-blockonomics' ), $status, $txid ) );
			$order->saveOrder();
			status_header( 200 );
			echo esc_html__( 'Blockonomics payment error recorded.', 'pmpro-blockonomics' );
			exit;
		}

		if ( $status < 2 ) {
			$order->status = 'pending';
			$order->add_order_note( sprintf( __( 'Blockonomics detected an unconfirmed payment of %1$d satoshis. Transaction ID: %2$s', 'pmpro-blockonomics' ), $value, $txid ) );
			$order->saveOrder();
			status_header( 200 );
			echo esc_html__( 'Blockonomics payment is waiting for confirmation.', 'pmpro-blockonomics' );
			exit;
		}

		$expected_satoshis = (int) get_pmpro_membership_order_meta( $order->id, 'blockonomics_expected_satoshis', true );
		if ( $expected_satoshis <= 0 ) {
			$order->status = 'error';
			$order->add_order_note( sprintf( __( 'Blockonomics callback rejected because the expected payment amount is missing. Transaction ID: %s', 'pmpro-blockonomics' ), $txid ) );
			$order->saveOrder();
			status_header( 200 );
			echo esc_html__( 'Blockonomics expected payment amount is missing.', 'pmpro-blockonomics' );
			exit;
		}

		if ( $value < $expected_satoshis ) {
			$order->status = 'error';
			$order->add_order_note( sprintf( __( 'Blockonomics callback rejected. Received %1$d satoshis, expected %2$d satoshis. Transaction ID: %3$s', 'pmpro-blockonomics' ), $value, $expected_satoshis, $txid ) );
			$order->saveOrder();
			status_header( 200 );
			echo esc_html__( 'Blockonomics payment amount is too low.', 'pmpro-blockonomics' );
			exit;
		}

		if ( 'success' === $order->status ) {
			status_header( 200 );
			echo esc_html__( 'Blockonomics payment was already processed.', 'pmpro-blockonomics' );
			exit;
		}

		$order->payment_transaction_id = $txid;
		$order->payment_type           = 'Bitcoin';
		$order->add_order_note( sprintf( __( 'Blockonomics confirmed payment of %1$d satoshis. Transaction ID: %2$s', 'pmpro-blockonomics' ), $value, $txid ) );
		pmpro_pull_checkout_data_from_order( $order );

		if ( pmpro_complete_checkout( $order ) ) {
			status_header( 200 );
			echo esc_html__( 'Blockonomics payment processed.', 'pmpro-blockonomics' );
			exit;
		}

		$order->status = 'error';
		$order->saveOrder();
		status_header( 500 );
		echo esc_html__( 'Could not complete the Blockonomics checkout.', 'pmpro-blockonomics' );
		exit;
	}

	/**
	 * Get the configured callback URL.
	 *
	 * @return string
	 */
	public static function get_callback_url() {
		$secret = get_option( 'pmpro_blockonomics_callback_secret' );
		return add_query_arg(
			array(
				'action' => 'blockonomics_callback',
				'secret' => $secret,
			),
			admin_url( 'admin-ajax.php' )
		);
	}

	/**
	 * Get a new Blockonomics address.
	 *
	 * @param string $api_key API key.
	 * @return string|WP_Error
	 */
	private static function get_new_address( $api_key ) {
		$filtered_address = apply_filters( 'pmpro_blockonomics_new_address', null, $api_key );
		if ( ! empty( $filtered_address ) ) {
			return sanitize_text_field( $filtered_address );
		}

		$response = wp_remote_post(
			self::get_api_url( 'new_address' ),
			array(
				'timeout' => 20,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
				),
				'body'    => '',
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );
		if ( 200 !== $code || empty( $data->address ) ) {
			return new WP_Error( 'pmpro_blockonomics_address_error', __( 'Could not generate a Blockonomics Bitcoin address.', 'pmpro-blockonomics' ) . ' ' . wp_strip_all_tags( $body ) );
		}

		return sanitize_text_field( $data->address );
	}

	/**
	 * Get the current BTC price in the given currency.
	 *
	 * @param string $currency Currency code.
	 * @param string $api_key API key.
	 * @return float|WP_Error
	 */
	private static function get_btc_price( $currency, $api_key ) {
		$filtered_price = apply_filters( 'pmpro_blockonomics_btc_price', null, $currency, $api_key );
		if ( ! empty( $filtered_price ) && is_numeric( $filtered_price ) ) {
			return (float) $filtered_price;
		}

		$response = wp_remote_get(
			add_query_arg(
				array(
					'crypto'   => 'BTC',
					'currency' => strtoupper( $currency ),
				),
				self::get_api_url( 'price' )
			),
			array(
				'timeout' => 20,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code  = wp_remote_retrieve_response_code( $response );
		$body  = wp_remote_retrieve_body( $response );
		$data  = json_decode( $body );
		$price = self::parse_price_response( $data, strtoupper( $currency ) );
		if ( 200 !== $code || empty( $price ) ) {
			return new WP_Error( 'pmpro_blockonomics_price_error', __( 'Could not retrieve the Blockonomics Bitcoin exchange rate for this checkout.', 'pmpro-blockonomics' ) . ' ' . wp_strip_all_tags( $body ) );
		}

		return $price;
	}

	/**
	 * Parse a Blockonomics price response.
	 *
	 * @param mixed  $data API response body decoded from JSON.
	 * @param string $currency Currency code.
	 * @return float
	 */
	private static function parse_price_response( $data, $currency ) {
		if ( is_numeric( $data ) ) {
			return (float) $data;
		}

		if ( is_object( $data ) ) {
			foreach ( array( 'price', 'value', strtolower( $currency ), $currency ) as $key ) {
				if ( isset( $data->{$key} ) && is_numeric( $data->{$key} ) ) {
					return (float) $data->{$key};
				}
			}
		}

		return 0.0;
	}

	/**
	 * Build a Blockonomics API URL.
	 *
	 * @param string $endpoint Endpoint path.
	 * @return string
	 */
	private static function get_api_url( $endpoint ) {
		$base_url = apply_filters( 'pmpro_blockonomics_api_base_url', 'https://www.blockonomics.co/api/' );
		return trailingslashit( $base_url ) . ltrim( $endpoint, '/' );
	}

	/**
	 * Format satoshis as a BTC amount.
	 *
	 * @param int $satoshis Satoshi amount.
	 * @return string
	 */
	private static function format_btc_amount( $satoshis ) {
		return rtrim( rtrim( number_format( $satoshis / 100000000, 8, '.', '' ), '0' ), '.' );
	}

	/**
	 * Find an order by its Blockonomics payment address.
	 *
	 * @param string $address Payment address.
	 * @return MemberOrder|null
	 */
	private static function get_order_by_payment_address( $address ) {
		global $wpdb;

		$order_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT pmpro_membership_order_id FROM {$wpdb->pmpro_membership_ordermeta} WHERE meta_key = %s AND meta_value = %s ORDER BY pmpro_membership_order_id DESC LIMIT 1",
				'blockonomics_address',
				$address
			)
		);

		if ( empty( $order_id ) ) {
			return null;
		}

		return new MemberOrder( $order_id );
	}
}
