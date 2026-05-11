<?php
/**
 * Blockonomics Bitcoin Payment Gateway for Paid Memberships Pro.
 *
 * Integrates Blockonomics (https://www.blockonomics.co) as a non-custodial,
 * direct-to-wallet Bitcoin payment option. Members pay a unique Bitcoin address
 * generated per order; Blockonomics calls the site's callback URL when the
 * payment is detected/confirmed.
 *
 * @since 3.5
 */

require_once( dirname( __FILE__ ) . '/class.pmprogateway.php' );

add_action( 'init', array( 'PMProGateway_blockonomics', 'init' ) );

class PMProGateway_blockonomics extends PMProGateway {

	const BLOCKONOMICS_API_BASE    = 'https://www.blockonomics.co';
	const SATOSHIS_PER_BTC         = 100000000;
	const UNDERPAYMENT_TOLERANCE   = 0.99; // allow up to 1% underpayment for exchange slippage

	// -------------------------------------------------------------------
	// Bootstrap
	// -------------------------------------------------------------------

	static function init() {
		add_filter( 'pmpro_gateways',              array( 'PMProGateway_blockonomics', 'pmpro_gateways' ) );
		add_filter( 'pmpro_payment_options',        array( 'PMProGateway_blockonomics', 'pmpro_payment_options' ) );
		add_filter( 'pmpro_payment_option_fields',  array( 'PMProGateway_blockonomics', 'pmpro_payment_option_fields' ), 10, 2 );
		add_action( 'pmpro_save_payment_settings',  array( 'PMProGateway_blockonomics', 'pmpro_save_payment_option_fields' ) );

		// Callback endpoint: /?pmpro-blockonomics=callback
		add_filter( 'query_vars',  array( 'PMProGateway_blockonomics', 'add_query_vars' ) );
		add_action( 'parse_request', array( 'PMProGateway_blockonomics', 'parse_request' ) );

		// AJAX status polling for the payment waiting page.
		add_action( 'wp_ajax_pmpro_blockonomics_check_status',        array( 'PMProGateway_blockonomics', 'ajax_check_status' ) );
		add_action( 'wp_ajax_nopriv_pmpro_blockonomics_check_status', array( 'PMProGateway_blockonomics', 'ajax_check_status' ) );
	}

	// -------------------------------------------------------------------
	// Gateway registration
	// -------------------------------------------------------------------

	static function pmpro_gateways( $gateways ) {
		if ( empty( $gateways['blockonomics'] ) ) {
			$gateways['blockonomics'] = __( 'Bitcoin (Blockonomics)', 'paid-memberships-pro' );
		}
		return $gateways;
	}

	// -------------------------------------------------------------------
	// Admin settings
	// -------------------------------------------------------------------

	static function getGatewayOptions() {
		return array(
			'sslseal',
			'nuclear_HTTPS',
			'gateway_environment',
			'blockonomics_api_key',
			'blockonomics_confirmations',
		);
	}

	static function pmpro_payment_options( $options ) {
		return array_merge( $options, self::getGatewayOptions() );
	}

	static function pmpro_payment_option_fields( $values, $gateway ) {
		?>
		<tr class="pmpro_settings_divider gateway gateway_blockonomics" <?php if ( $gateway !== 'blockonomics' ) { ?>style="display: none;"<?php } ?>>
			<td colspan="2">
				<hr />
				<h2 class="title"><?php esc_html_e( 'Blockonomics Bitcoin Settings', 'paid-memberships-pro' ); ?></h2>
			</td>
		</tr>

		<tr class="gateway gateway_blockonomics" <?php if ( $gateway !== 'blockonomics' ) { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="blockonomics_api_key"><?php esc_html_e( 'API Key', 'paid-memberships-pro' ); ?></label>
			</th>
			<td>
				<input id="blockonomics_api_key" name="blockonomics_api_key" type="text"
					class="regular-text"
					value="<?php echo esc_attr( pmpro_getOption( 'blockonomics_api_key' ) ); ?>" />
				<p class="description">
					<?php
					printf(
						wp_kses(
							/* translators: %1$s: Blockonomics dashboard URL, %2$s: callback URL */
							__( 'Get your API key from <a href="%1$s" target="_blank" rel="noopener noreferrer">Blockonomics Merchants</a>. Set the store callback URL to: <code>%2$s</code>', 'paid-memberships-pro' ),
							array(
								'a'    => array( 'href' => array(), 'target' => array(), 'rel' => array() ),
								'code' => array(),
							)
						),
						'https://www.blockonomics.co/#/merchants',
						esc_html( self::get_callback_url() )
					);
					?>
				</p>
			</td>
		</tr>

		<tr class="gateway gateway_blockonomics" <?php if ( $gateway !== 'blockonomics' ) { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="blockonomics_confirmations"><?php esc_html_e( 'Confirmations Required', 'paid-memberships-pro' ); ?></label>
			</th>
			<td>
				<select id="blockonomics_confirmations" name="blockonomics_confirmations">
					<option value="0" <?php selected( pmpro_getOption( 'blockonomics_confirmations' ), '0' ); ?>><?php esc_html_e( '0 — Instant (unconfirmed)', 'paid-memberships-pro' ); ?></option>
					<option value="1" <?php selected( pmpro_getOption( 'blockonomics_confirmations' ), '1' ); ?>><?php esc_html_e( '1 — Low risk', 'paid-memberships-pro' ); ?></option>
					<option value="2" <?php selected( pmpro_getOption( 'blockonomics_confirmations' ), '2' ); ?>><?php esc_html_e( '2 — Standard (recommended)', 'paid-memberships-pro' ); ?></option>
				</select>
				<p class="description">
					<?php esc_html_e( 'Number of blockchain confirmations before the membership is activated. 0 activates on first detection (less secure).', 'paid-memberships-pro' ); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	static function pmpro_save_payment_option_fields() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$api_key       = isset( $_POST['blockonomics_api_key'] )       ? sanitize_text_field( wp_unslash( $_POST['blockonomics_api_key'] ) ) : '';
		$confirmations = isset( $_POST['blockonomics_confirmations'] ) ? absint( $_POST['blockonomics_confirmations'] )                       : 2;
		// phpcs:enable

		pmpro_setOption( 'blockonomics_api_key',       $api_key );
		pmpro_setOption( 'blockonomics_confirmations', (string) $confirmations );
	}

	// -------------------------------------------------------------------
	// Checkout: generate address, redirect to payment page
	// -------------------------------------------------------------------

	function process( &$order ) {
		$api_key = pmpro_getOption( 'blockonomics_api_key' );
		if ( empty( $api_key ) ) {
			$order->error = __( 'Blockonomics API key is not configured.', 'paid-memberships-pro' );
			return false;
		}

		$currency  = ! empty( $order->currency ) ? $order->currency : get_option( 'pmpro_currency', 'USD' );
		$btc_price = self::get_btc_price( $currency );
		if ( ! $btc_price ) {
			$order->error = __( 'Could not retrieve the current Bitcoin exchange rate. Please try again.', 'paid-memberships-pro' );
			return false;
		}

		$btc_amount = round( $order->subtotal / $btc_price, 8 );
		$address    = self::get_new_address( $api_key );
		if ( ! $address ) {
			$order->error = __( 'Could not generate a Bitcoin payment address. Please verify your Blockonomics API key.', 'paid-memberships-pro' );
			return false;
		}

		update_option(
			'pmpro_blockonomics_order_' . $address,
			array(
				'order_id'    => $order->id,
				'btc_amount'  => $btc_amount,
				'fiat_amount' => $order->subtotal,
				'currency'    => $currency,
				'created'     => time(),
				'status'      => 'pending',
			)
		);

		$order->payment_transaction_id = $address;
		$order->saveOrder();

		$redirect = add_query_arg(
			array(
				'pmpro-blockonomics' => 'pay',
				'address'            => rawurlencode( $address ),
				'amount'             => $btc_amount,
				'order_id'           => $order->id,
			),
			pmpro_url( 'checkout' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	function charge( &$order ) {
		return false; // Bitcoin payments are one-time; recurring not supported.
	}

	function cancel( &$order ) {
		$order->updateStatus( 'cancelled' );
		return true;
	}

	// -------------------------------------------------------------------
	// Payment waiting page (rendered on the checkout page URL)
	// -------------------------------------------------------------------

	static function render_payment_page() {
		if ( empty( $_GET['pmpro-blockonomics'] ) || $_GET['pmpro-blockonomics'] !== 'pay' ) {
			return;
		}

		$address  = isset( $_GET['address'] )  ? sanitize_text_field( wp_unslash( $_GET['address'] ) )  : '';
		$amount   = isset( $_GET['amount'] )   ? (float) wp_unslash( $_GET['amount'] )                   : 0;
		$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] )                             : 0;

		if ( ! $address || ! $amount || ! $order_id ) {
			wp_die( esc_html__( 'Invalid payment parameters.', 'paid-memberships-pro' ) );
		}

		$btc_uri    = 'bitcoin:' . $address . '?amount=' . number_format( $amount, 8, '.', '' );
		$qr_url     = 'https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=' . rawurlencode( $btc_uri );
		$block_link = 'https://www.blockonomics.co/#/search?q=' . rawurlencode( $address );

		$required_conf = (int) pmpro_getOption( 'blockonomics_confirmations' );
		if ( $required_conf === 0 ) {
			$conf_label = __( 'Membership will activate immediately when your payment is detected.', 'paid-memberships-pro' );
		} else {
			$conf_label = sprintf(
				_n(
					'Membership activates after %d confirmation.',
					'Membership activates after %d confirmations.',
					$required_conf,
					'paid-memberships-pro'
				),
				$required_conf
			);
		}

		// Clear output buffers and render standalone page.
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		get_header();
		?>
		<div id="pmpro-blockonomics-payment" class="pmpro_content_wrap">
			<h2><?php esc_html_e( 'Pay with Bitcoin', 'paid-memberships-pro' ); ?></h2>

			<p><?php esc_html_e( 'Send the exact amount shown below to complete your membership.', 'paid-memberships-pro' ); ?></p>

			<p class="pmpro-blockonomics-amount">
				<strong><?php echo esc_html( number_format( $amount, 8 ) ); ?> BTC</strong>
			</p>

			<div class="pmpro-blockonomics-qr">
				<a href="<?php echo esc_url( $btc_uri ); ?>">
					<img src="<?php echo esc_url( $qr_url ); ?>"
						alt="<?php esc_attr_e( 'Bitcoin payment QR code', 'paid-memberships-pro' ); ?>"
						width="250" height="250" />
				</a>
			</div>

			<p class="pmpro-blockonomics-address-label">
				<?php esc_html_e( 'Bitcoin Address', 'paid-memberships-pro' ); ?>
			</p>
			<div class="pmpro-blockonomics-address-wrap">
				<input type="text" id="pmpro-blockonomics-addr"
					value="<?php echo esc_attr( $address ); ?>" readonly />
				<button type="button" onclick="
					navigator.clipboard.writeText(
						document.getElementById('pmpro-blockonomics-addr').value
					).then(function(){
						this.textContent = <?php echo wp_json_encode( __( 'Copied!', 'paid-memberships-pro' ) ); ?>;
					}.bind(this));
				"><?php esc_html_e( 'Copy', 'paid-memberships-pro' ); ?></button>
			</div>

			<p class="pmpro-blockonomics-conf-note"><?php echo esc_html( $conf_label ); ?></p>

			<p id="pmpro-blockonomics-status" class="pmpro-blockonomics-waiting">
				<?php esc_html_e( 'Waiting for payment…', 'paid-memberships-pro' ); ?>
			</p>

			<p>
				<a href="<?php echo esc_url( $block_link ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Track on Blockonomics', 'paid-memberships-pro' ); ?>
				</a>
			</p>
		</div>

		<script>
		(function() {
			'use strict';
			var orderId  = <?php echo absint( $order_id ); ?>;
			var address  = <?php echo wp_json_encode( $address ); ?>;
			var statusEl = document.getElementById( 'pmpro-blockonomics-status' );
			var ajaxUrl  = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
			var nonce    = <?php echo wp_json_encode( wp_create_nonce( 'pmpro_blockonomics_check' ) ); ?>;

			var textSeen      = <?php echo wp_json_encode( __( 'Payment detected, waiting for confirmations…', 'paid-memberships-pro' ) ); ?>;
			var textConfirmed = <?php echo wp_json_encode( __( 'Payment confirmed! Activating membership…', 'paid-memberships-pro' ) ); ?>;

			function poll() {
				var xhr = new XMLHttpRequest();
				xhr.open( 'POST', ajaxUrl, true );
				xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
				xhr.onload = function() {
					if ( xhr.status === 200 ) {
						try {
							var data = JSON.parse( xhr.responseText );
							if ( data.data && data.data.status === 'confirmed' ) {
								statusEl.textContent = textConfirmed;
								window.location.href = data.data.redirect;
								return;
							}
							if ( data.data && data.data.status === 'seen' ) {
								statusEl.textContent = textSeen;
							}
						} catch ( e ) {}
					}
					setTimeout( poll, 15000 );
				};
				xhr.onerror = function() { setTimeout( poll, 30000 ); };
				xhr.send(
					'action=pmpro_blockonomics_check_status'
					+ '&order_id=' + orderId
					+ '&address=' + encodeURIComponent( address )
					+ '&_ajax_nonce=' + nonce
				);
			}

			setTimeout( poll, 10000 );
		}());
		</script>

		<style>
		#pmpro-blockonomics-payment { max-width: 520px; margin: 2em auto; padding: 2em; text-align: center; }
		.pmpro-blockonomics-amount  { font-size: 2em; font-weight: 700; color: #f7931a; }
		.pmpro-blockonomics-qr      { margin: 1em 0; }
		.pmpro-blockonomics-qr img  { border: 4px solid #eee; border-radius: 4px; }
		.pmpro-blockonomics-address-wrap { display: flex; gap: .5em; justify-content: center; margin: .5em 0 1em; }
		.pmpro-blockonomics-address-wrap input { flex: 1 1 300px; padding: .4em .6em; font-family: monospace; border: 1px solid #ccc; border-radius: 3px; }
		.pmpro-blockonomics-address-wrap button { padding: .4em .8em; cursor: pointer; border: 1px solid #ccc; border-radius: 3px; }
		.pmpro-blockonomics-conf-note { font-size: .85em; color: #666; }
		.pmpro-blockonomics-waiting   { font-style: italic; color: #888; }
		</style>
		<?php
		get_footer();
		exit;
	}

	// -------------------------------------------------------------------
	// Blockonomics callback (webhook from Blockonomics)
	// -------------------------------------------------------------------

	static function add_query_vars( $vars ) {
		$vars[] = 'pmpro-blockonomics';
		return $vars;
	}

	static function parse_request( $wp ) {
		if ( empty( $_GET['pmpro-blockonomics'] ) ) {
			return;
		}

		$action = sanitize_key( $_GET['pmpro-blockonomics'] );

		if ( $action === 'callback' ) {
			self::handle_callback();
			exit;
		}

		if ( $action === 'pay' ) {
			self::render_payment_page();
		}
	}

	static function handle_callback() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$addr   = isset( $_GET['addr'] )   ? sanitize_text_field( wp_unslash( $_GET['addr'] ) )   : '';
		$status = isset( $_GET['status'] ) ? (int) $_GET['status']                                  : -1;
		$value  = isset( $_GET['value'] )  ? absint( $_GET['value'] )                               : 0;
		$txid   = isset( $_GET['txid'] )   ? sanitize_text_field( wp_unslash( $_GET['txid'] ) )    : '';
		// phpcs:enable

		if ( ! $addr ) {
			status_header( 400 );
			echo 'Missing addr.';
			return;
		}

		$data = get_option( 'pmpro_blockonomics_order_' . $addr );
		if ( ! $data ) {
			status_header( 404 );
			echo 'Order not found.';
			return;
		}

		if ( $data['status'] === 'confirmed' ) {
			status_header( 200 );
			echo 'Already processed.';
			return;
		}

		$required_conf = (int) pmpro_getOption( 'blockonomics_confirmations' );

		if ( $status === 0 ) {
			$data['status'] = 'seen';
			$data['txid']   = $txid;
			update_option( 'pmpro_blockonomics_order_' . $addr, $data );

			if ( $required_conf === 0 ) {
				self::complete_order( $data, $addr, $value, $txid );
			}
		} elseif ( $status >= 1 ) {
			$expected = (int) round( $data['btc_amount'] * self::SATOSHIS_PER_BTC );
			if ( $value < $expected * self::UNDERPAYMENT_TOLERANCE ) {
				$data['status'] = 'underpaid';
				$data['paid']   = $value;
				update_option( 'pmpro_blockonomics_order_' . $addr, $data );
				status_header( 200 );
				echo 'Underpaid.';
				return;
			}

			if ( $status >= $required_conf || ( $required_conf <= 1 && $status >= 1 ) ) {
				self::complete_order( $data, $addr, $value, $txid );
			}
		}

		status_header( 200 );
		echo 'OK';
	}

	private static function complete_order( array $data, $addr, $value, $txid ) {
		$order = new MemberOrder( $data['order_id'] );
		if ( ! $order || ! $order->id ) {
			return;
		}
		if ( $order->status === 'success' ) {
			return;
		}

		$order->payment_transaction_id = $txid ?: $addr;
		$order->saveOrder();

		$data['status'] = 'confirmed';
		$data['txid']   = $txid;
		$data['paid']   = $value;
		update_option( 'pmpro_blockonomics_order_' . $addr, $data );

		pmpro_complete_async_checkout( $order );
	}

	// -------------------------------------------------------------------
	// AJAX: payment status poll
	// -------------------------------------------------------------------

	static function ajax_check_status() {
		check_ajax_referer( 'pmpro_blockonomics_check' );

		$order_id = absint( wp_unslash( $_POST['order_id'] ?? 0 ) );
		$address  = sanitize_text_field( wp_unslash( $_POST['address'] ?? '' ) );

		if ( ! $order_id || ! $address ) {
			wp_send_json_error( 'Bad params.' );
		}

		$data  = get_option( 'pmpro_blockonomics_order_' . $address );
		$order = new MemberOrder( $order_id );

		if (
			( $data && $data['status'] === 'confirmed' )
			|| ( $order && $order->status === 'success' )
		) {
			wp_send_json_success( array(
				'status'   => 'confirmed',
				'redirect' => pmpro_url( 'confirmation', '?level=' . absint( $order->membership_id ) ),
			) );
		}

		wp_send_json_success( array( 'status' => $data ? $data['status'] : 'pending' ) );
	}

	// -------------------------------------------------------------------
	// Blockonomics API helpers
	// -------------------------------------------------------------------

	static function get_callback_url() {
		return add_query_arg( 'pmpro-blockonomics', 'callback', home_url( '/' ) );
	}

	private static function get_new_address( $api_key ) {
		$response = wp_remote_post(
			self::BLOCKONOMICS_API_BASE . '/api/new_address',
			array(
				'headers' => array( 'Authorization' => 'Bearer ' . $api_key ),
				'body'    => array( 'match_callback' => self::get_callback_url() ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return isset( $body['address'] ) ? sanitize_text_field( $body['address'] ) : null;
	}

	private static function get_btc_price( $currency = 'USD' ) {
		$cache_key = 'pmpro_blockonomics_price_' . $currency;
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return (float) $cached;
		}

		$response = wp_remote_get(
			self::BLOCKONOMICS_API_BASE . '/api/price?currency=' . rawurlencode( $currency ),
			array( 'timeout' => 10 )
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$body  = json_decode( wp_remote_retrieve_body( $response ), true );
		$price = isset( $body['price'] ) ? (float) $body['price'] : null;

		if ( $price ) {
			set_transient( $cache_key, $price, 5 * MINUTE_IN_SECONDS );
		}

		return $price;
	}
}
