<?php 

// Show the edit order form if an order ID is provided.
if ( ! empty( $order->id ) ) { ?>
	<h1 class="wp-heading-inline"><?php printf( esc_html__( 'Edit Order # %s', 'paid-memberships-pro' ), esc_html( $order->code ) ); ?></h1>
<?php } else { ?>
	<h1 class="wp-heading-inline"><?php esc_html_e( 'New Order', 'paid-memberships-pro' ); ?></h1>
<?php } ?>

<form method="post" action="">
	<div class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
				<?php esc_html_e( 'Order Information', 'paid-memberships-pro' ); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row" valign="top"><label for="code"><?php esc_html_e( 'Code', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<input id="code" name="code" type="text" value="<?php echo esc_attr( $order->code ); ?>" />
						<p class="description"><?php esc_html_e( 'A randomly generated code that serves as a unique, non-sequential order number.', 'paid-memberships-pro' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="date"><?php esc_html_e( 'Date', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<?php
							$timestamp = $order->getTimestamp();
							if ( empty( $timestamp ) ) {
								$timestamp = time();
							}
							$date_input_value = date_i18n( 'Y-m-d\TH:i', $timestamp );
						?>
						<input type="datetime-local" name="date" value="<?php echo esc_attr( $date_input_value ); ?>">
					</td>
				</tr>
				</tbody>
			</table>
		</div> <!-- end pmpro_section_inside -->
	</div> <!-- end pmpro_section -->
	<div class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
				<?php esc_html_e( 'Member Information', 'paid-memberships-pro' ); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row" valign="top"><label for="user_id"><?php esc_html_e( 'User ID', 'paid-memberships-pro' ); ?></label></th>
						<td>
							<?php
								$user_id = ! empty( $_REQUEST['user'] ) ? intval( $_REQUEST['user'] ) : $order->user_id;
							?>
							<input id="user_id" name="user_id" type="text" value="<?php echo esc_attr( $user_id ); ?>" size="10" />
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label for="membership_id"><?php esc_html_e( 'Membership Level ID', 'paid-memberships-pro' ); ?></label></th>
						<td>
							<?php
								// Get the order's current membership level ID.
								$membership_id = ! empty( $_REQUEST['membership_id'] ) ? intval( $_REQUEST['membership_id'] ) : $order->membership_id;

								// Get all membership levels.
								$levels = pmpro_getAllLevels( true, true );
							?>
							<select id="membership_id" name="membership_id">
								<option value="0" <?php selected( $membership_id, 0 ); ?>>-- <?php esc_html_e("None", 'paid-memberships-pro' );?> --</option>
								<?php
								// If the current membership level is not in the list, add it as "ID {membership_id} [deleted]".
								if ( ! empty( $membership_id ) && ! in_array( $membership_id, wp_list_pluck( $levels, 'id' ) ) ) {
									?>
									<option value="<?php echo esc_attr( $membership_id ); ?>" selected><?php echo esc_html( sprintf( __( 'ID %d [deleted]', 'paid-memberships-pro' ), $membership_id ) ); ?></option>
									<?php
								}

								// Add the rest of the levels.
								foreach ( $levels as $level ) {
									?>
									<option value="<?php echo esc_attr( $level->id ); ?>" <?php selected( $membership_id, $level->id ); ?>><?php echo esc_html( $level->name ); ?></option>
									<?php
								}
								?>
							</select>
						</td>
					</tr>
				</tbody>
			</table>
		</div> <!-- end pmpro_section_inside -->
	</div> <!-- end pmpro_section -->
	<?php
		if ( $order->has_billing_address() ) {
			$section_visibility = 'shown';
			$section_activated = 'true';
		} else {
			$section_visibility = 'hidden';
			$section_activated = 'false';
		}
	?>
	<div class="pmpro_section" data-visibility="<?php echo esc_attr($section_visibility); ?>" data-activated="<?php echo esc_attr($section_activated); ?>">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="<?php echo $section_visibility === 'hidden' ? 'false' : 'true'; ?>">
				<span class="dashicons dashicons-arrow-<?php echo $section_visibility === 'hidden' ? 'down' : 'up'; ?>-alt2"></span>
				<?php esc_html_e('Billing Address', 'paid-memberships-pro'); ?>
			</button>
		</div>
		<div class="pmpro_section_inside" <?php echo $section_visibility === 'hidden' ? 'style="display: none"' : ''; ?>>
			<table id="billing_address_fields" class="form-table">
				<tbody>
				<tr>
					<th scope="row" valign="top"><label for="billing_name"><?php esc_html_e( 'Billing Name', 'paid-memberships-pro' ); ?></label>
					</th>
					<td>
						<input id="billing_name" name="billing_name" type="text" size="50" value="<?php echo esc_attr( $order->billing->name ); ?>"/>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="billing_street"><?php esc_html_e( 'Billing Street', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<input id="billing_street" name="billing_street" type="text" size="50" value="<?php echo esc_attr( $order->billing->street ); ?>"/></td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="billing_street2"><?php esc_html_e( 'Billing Street 2', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<input id="billing_street2" name="billing_street2" type="text" size="50" value="<?php echo esc_attr( $order->billing->street2 ); ?>"/></td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="billing_city"><?php esc_html_e( 'Billing City', 'paid-memberships-pro' ); ?></label>
					</th>
					<td>
						<input id="billing_city" name="billing_city" type="text" size="50" value="<?php echo esc_attr( $order->billing->city ); ?>"/></td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="billing_state"><?php esc_html_e( 'Billing State', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<input id="billing_state" name="billing_state" type="text" size="50" value="<?php echo esc_attr( $order->billing->state ); ?>"/>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="billing_zip"><?php esc_html_e( 'Billing Postal Code', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<input id="billing_zip" name="billing_zip" type="text" size="50" value="<?php echo esc_attr( $order->billing->zip ); ?>"/></td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="billing_country"><?php esc_html_e( 'Billing Country', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<input id="billing_country" name="billing_country" type="text" size="50" value="<?php echo esc_attr( $order->billing->country ); ?>"/>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="billing_phone"><?php esc_html_e( 'Billing Phone', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<input id="billing_phone" name="billing_phone" type="text" size="50" value="<?php echo esc_attr( $order->billing->phone ); ?>"/>
					</td>
				</tr>
				</tbody>
			</table> <!-- end #billing_address_fields -->
		</div> <!-- end pmpro_section_inside -->
	</div> <!-- end pmpro_section -->
	<div class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
				<?php esc_html_e( 'Payment Information', 'paid-memberships-pro' ); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<table class="form-table">
				<tbody>
				<?php
				if ( $order_id > 0 ) {
					$order->getDiscountCode();
					if ( ! empty( $order->discount_code ) ) {
						$discount_code_id = $order->discount_code->id;
					} else {
						$discount_code_id = 0;
					}
				} else {
					$discount_code_id = 0;
				}

				$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM $wpdb->pmpro_discount_codes ";
				$sqlQuery .= "ORDER BY id DESC ";
				$codes = $wpdb->get_results($sqlQuery, OBJECT);
				if ( ! empty( $codes ) ) { ?>
				<tr>
					<th scope="row" valign="top"><label for="discount_code_id"><?php esc_html_e( 'Discount Code', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<select id="discount_code_id" name="discount_code_id">
							<option value="0" <?php selected( $discount_code_id, 0); ?>>-- <?php esc_html_e("None", 'paid-memberships-pro' );?> --</option>
							<?php foreach ( $codes as $code ) { ?>
								<option value="<?php echo esc_attr( $code->id ); ?>" <?php selected( $discount_code_id, $code->id ); ?>><?php echo esc_html( $code->code ); ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
				<?php } ?>
				<tr>
					<th scope="row" valign="top"><label for="subtotal"><?php esc_html_e( 'Sub Total', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<input id="subtotal" name="subtotal" type="text" size="10" value="<?php echo esc_attr( $order->subtotal ); ?>"/>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="tax"><?php esc_html_e( 'Tax', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<input id="tax" name="tax" type="text" size="10" value="<?php echo esc_attr( $order->tax ); ?>"/>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="total"><?php esc_html_e( 'Total', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<input id="total" name="total" type="text" size="10" value="<?php echo esc_attr( $order->total ); ?>"/>
					</td>
				</tr>

				<tr>
					<th scope="row" valign="top"><label for="payment_type"><?php esc_html_e( 'Payment Type', 'paid-memberships-pro' ); ?></label>
					</th>
					<td>
						<input id="payment_type" name="payment_type" type="text" size="50" value="<?php echo esc_attr( $order->payment_type ); ?>"/>
						<p class="description"><?php esc_html_e( 'e.g. PayPal Express, PayPal Standard, Credit Card.', 'paid-memberships-pro' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="cardtype"><?php esc_html_e( 'Card Type', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<input id="cardtype" name="cardtype" type="text" size="50" value="<?php echo esc_attr( $order->cardtype ); ?>"/>
						<p class="description"><?php esc_html_e( 'e.g. Visa, MasterCard, AMEX, etc', 'paid-memberships-pro' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="accountnumber"><?php esc_html_e( 'Account Number', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<input id="accountnumber" name="accountnumber" type="text" size="50" value="<?php echo esc_attr( $order->accountnumber ); ?>"/>
						<p class="description"><?php esc_html_e( 'Only the last 4 digits are stored in this site to use as a reference with the gateway.', 'paid-memberships-pro' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="expirationmonth"><?php esc_html_e( 'Expiration', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<input id="expirationmonth" name="expirationmonth" type="text" size="10"
				value="<?php echo esc_attr( $order->expirationmonth ); ?>"/> /
						<input id="expirationyear" name="expirationyear" type="text" size="10"
				value="<?php echo esc_attr( $order->expirationyear ); ?>"/>
						<span class="description"><?php esc_html_e( 'MM/YYYY', 'paid-memberships-pro' );?></span>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="status"><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<?php
							$statuses = pmpro_getOrderStatuses();
						?>
						<select id="status" name="status">
							<?php foreach ( $statuses as $status ) { ?>
								<option
									value="<?php echo esc_attr( $status ); ?>" <?php selected( $order->status, $status ); ?>><?php echo esc_html( $status ); ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
				</tbody>
			</table>
		</div> <!-- end pmpro_section_inside -->
	</div> <!-- end pmpro_section -->
	<div class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
				<?php esc_html_e( 'Payment Gateway Information', 'paid-memberships-pro' ); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row" valign="top"><label for="gateway"><?php esc_html_e( 'Gateway', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<select id="gateway" name="gateway" onchange="pmpro_changeGateway(jQuery(this).val());">
						<?php
							$pmpro_gateways = pmpro_gateways();
							foreach ( $pmpro_gateways as $pmpro_gateway_name => $pmpro_gateway_label ) {
								?>
								<option
									value="<?php echo esc_attr( $pmpro_gateway_name ); ?>" <?php selected( $order->gateway, $pmpro_gateway_name ); ?>><?php echo esc_html( $pmpro_gateway_label ); ?></option>
								<?php
							}
						?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label
							for="gateway_environment"><?php esc_html_e( 'Gateway Environment', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<select name="gateway_environment">
							<option value="sandbox" <?php if ( $order->gateway_environment == 'sandbox' ) { ?>selected="selected"<?php } ?>><?php esc_html_e( 'Sandbox/Testing', 'paid-memberships-pro' ); ?></option>
							<option value="live" <?php if ( $order->gateway_environment == 'live' ) { ?>selected="selected"<?php } ?>><?php esc_html_e( 'Live/Production', 'paid-memberships-pro' ); ?></option>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row" valign="top"><label
							for="payment_transaction_id"><?php esc_html_e( 'Payment Transaction ID', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<input id="payment_transaction_id" name="payment_transaction_id" type="text" size="50" value="<?php echo esc_attr( $order->payment_transaction_id ); ?>"/>
						<p class="description"><?php esc_html_e( 'Generated by the gateway. Useful to cross reference orders.', 'paid-memberships-pro' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label
							for="subscription_transaction_id"><?php esc_html_e( 'Subscription Transaction ID', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<input id="subscription_transaction_id" name="subscription_transaction_id" type="text" size="50" value="<?php echo esc_attr( $order->subscription_transaction_id ); ?>"/>
						<p class="description"><?php esc_html_e( 'Generated by the gateway. Useful to cross reference subscriptions.', 'paid-memberships-pro' ); ?></p>
					</td>
				</tr>
				</tbody>
			</table>
		</div> <!-- end pmpro_section_inside -->
	</div> <!-- end pmpro_section -->
	<div class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
				<?php esc_html_e( 'Additional Order Information', 'paid-memberships-pro' ); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<table class="form-table">
				<tbody>
				<?php
				$affiliates = apply_filters( 'pmpro_orders_show_affiliate_ids', false );
				if ( ! empty( $affiliates ) ) {
					?>
					<tr>
						<th scope="row" valign="top"><label for="affiliate_id"><?php esc_html_e( 'Affiliate ID', 'paid-memberships-pro' ); ?>
								:</label></th>
						<td>
							<input id="affiliate_id" name="affiliate_id" type="text" size="50" value="<?php echo esc_attr( $order->affiliate_id ); ?>"/>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label for="affiliate_subid"><?php esc_html_e( 'Affiliate SubID', 'paid-memberships-pro' ); ?>
								:</label></th>
						<td>
							<input id="affiliate_subid" name="affiliate_subid" type="text" size="50" value="<?php echo esc_attr( $order->affiliate_subid ); ?>"/>
						</td>
					</tr>
				<?php } ?>
				<tr>
					<th scope="row" valign="top"><label for="notes"><?php esc_html_e( 'Notes', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<textarea id="notes" name="notes" rows="5" cols="80"><?php echo esc_textarea( $order->notes ); ?></textarea>
					</td>
				</tr>

				<?php do_action( 'pmpro_after_order_settings', $order ); ?>

				</tbody>
			</table>
		</div> <!-- end pmpro_section_inside -->
	</div> <!-- end pmpro_section -->

	<?php
		/**
		 * Allow adding other content after the Order Settings table.
		 *
		 * @since 2.5.10
		 *
		 * @param MemberOrder $order Member order object.
		 */
		do_action( 'pmpro_after_order_settings_table', $order );
	?>

	<p class="submit">
		<?php wp_nonce_field( 'save_order', 'pmpro_orders_nonce' ); ?>
		<input name="id" type="hidden" value="<?php echo esc_html( empty( $order->id ) ? $order_id : $order->id ); ?>"/>
		<input name="edit" type="hidden" value="1" />
		<input name="action" type="hidden" value="save_order" />
		<input name="save" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Order', 'paid-memberships-pro' ); ?>"/>
		<input name="cancel" type="button" class="cancel button-secondary" value="<?php esc_attr_e( 'Cancel', 'paid-memberships-pro' ); ?>"
				onclick="location.href='<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 'id' => $order->id ), admin_url( 'admin.php' ) ) ); ?>';"/>
	</p>

</form>
