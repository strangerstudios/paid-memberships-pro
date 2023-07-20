<?php
// only admins can get this
if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_orders' ) ) ) {
	die( __( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
}

// vars
global $wpdb;

$now = current_time( 'timestamp' );

// deleting?
if ( ! empty( $_REQUEST['delete'] ) ) {
	// Check nonce for deleting.
	$nonceokay = true;
	if ( empty( $_REQUEST['pmpro_orders_nonce'] ) || ! check_admin_referer( 'delete_order', 'pmpro_orders_nonce' ) ) {
		$nonceokay = false;
	}

	$dorder_code = intval( $_REQUEST['delete'] );
	$dorder = new MemberOrder( $dorder_code );
	if ( $nonceokay && $dorder->deleteMe() ) {
		$pmpro_msg = sprintf( __( 'Order %s deleted successfully.', 'paid-memberships-pro' ), $dorder_code );
		$pmpro_msgt = 'success';
	} else {
		$pmpro_msg  = __( 'Error deleting order.', 'paid-memberships-pro' );
		$pmpro_msgt = 'error';
	}
}

// Refund this order
if ( ! empty( $_REQUEST['refund'] ) ) {
	// Check nonce for refunding.
	$nonceokay = true;
	if ( empty( $_REQUEST['pmpro_orders_nonce'] ) || ! check_admin_referer( 'refund_order', 'pmpro_orders_nonce' ) ) {
		$nonceokay = false;
	}

	$rorder = new MemberOrder( (int) $_REQUEST['refund'] );
	if ( $nonceokay && !empty( $rorder ) && pmpro_allowed_refunds( $rorder ) ) {
		
		if( pmpro_refund_order( $rorder ) ) {
			$pmpro_msg  = __( 'Order refunded successfully.', 'paid-memberships-pro' );
			$pmpro_msgt = 'success';
		} else {
			$pmpro_msg  = __( 'Error refunding order. Please check the order notes for more information.', 'paid-memberships-pro' );
			$pmpro_msgt = 'error';
		}

	} else {
		$pmpro_msg  = __( 'Error refunding order. Please check the order notes for more information.', 'paid-memberships-pro' );
		$pmpro_msgt = 'error';
	}
}

$thisyear = date( 'Y', $now );

// this array stores fields that should be read only
$read_only_fields = apply_filters(
	'pmpro_orders_read_only_fields', array(
		'code',
		'payment_transaction_id',
		'subscription_transaction_id',
	)
);

// if this is a new order or copy of one, let's make all fields editable
// Checking orderby as order could be the order ID or whether the List Table should be sorted ascending or descending.
if ( ( ! empty( $_REQUEST['order'] ) && $_REQUEST['order'] < 0 ) && ! isset( $_REQUEST['orderby'] ) ) {
	$read_only_fields = array();
}

// saving?
if ( ! empty( $_REQUEST['save'] ) ) {
	// start with old order if applicable
	$order_id = intval( $_REQUEST['order'] );
	if ( $order_id > 0 ) {
		$order = new MemberOrder( $order_id );
	} else {
		$order = new MemberOrder();
		$order->billing = new stdClass();
	}

	// update values
	if ( ! in_array( 'code', $read_only_fields ) && isset( $_POST['code'] ) ) {
		$order->code = sanitize_text_field( $_POST['code'] );
	}
	if ( ! in_array( 'user_id', $read_only_fields ) && isset( $_POST['user_id'] ) ) {
		$order->user_id = intval( $_POST['user_id'] );
	}
	if ( ! in_array( 'membership_id', $read_only_fields ) && isset( $_POST['membership_id'] ) ) {
		$order->membership_id = intval( $_POST['membership_id'] );
	}
	if ( ! in_array( 'billing_name', $read_only_fields ) && isset( $_POST['billing_name'] ) ) {
		$order->billing->name = sanitize_text_field( wp_unslash( $_POST['billing_name'] ) );
	}
	if ( ! in_array( 'billing_street', $read_only_fields ) && isset( $_POST['billing_street'] ) ) {
		$order->billing->street = sanitize_text_field( wp_unslash( $_POST['billing_street'] ) );
	}
	if ( ! in_array( 'billing_city', $read_only_fields ) && isset( $_POST['billing_city'] ) ) {
		$order->billing->city = sanitize_text_field( wp_unslash( $_POST['billing_city'] ) );
	}
	if ( ! in_array( 'billing_state', $read_only_fields ) && isset( $_POST['billing_state'] ) ) {
		$order->billing->state = sanitize_text_field( wp_unslash( $_POST['billing_state'] ) );
	}
	if ( ! in_array( 'billing_zip', $read_only_fields ) && isset( $_POST['billing_zip'] ) ) {
		$order->billing->zip = sanitize_text_field( $_POST['billing_zip'] );
	}
	if ( ! in_array( 'billing_country', $read_only_fields ) && isset( $_POST['billing_country'] ) ) {
		$order->billing->country = sanitize_text_field( wp_unslash( $_POST['billing_country'] ) );
	}
	if ( ! in_array( 'billing_phone', $read_only_fields ) && isset( $_POST['billing_phone'] ) ) {
		$order->billing->phone = sanitize_text_field( $_POST['billing_phone'] );
	}
	if ( ! in_array( 'subtotal', $read_only_fields ) && isset( $_POST['subtotal'] ) ) {
		$order->subtotal = sanitize_text_field( $_POST['subtotal'] );
	}
	if ( ! in_array( 'tax', $read_only_fields ) && isset( $_POST['tax'] ) ) {
		$order->tax = sanitize_text_field( $_POST['tax'] );
	}

	// Hiding couponamount by default.
	$coupons = apply_filters( 'pmpro_orders_show_coupon_amounts', false );
	if ( ! empty( $coupons ) ) {
		if ( ! in_array( 'couponamount', $read_only_fields ) && isset( $_POST['couponamount'] ) ) {
			$order->couponamount = sanitize_text_field( $_POST['couponamount'] );
		}
	}

	if ( ! in_array( 'total', $read_only_fields ) && isset( $_POST['total'] ) ) {
		$order->total = sanitize_text_field( $_POST['total'] );
	}
	if ( ! in_array( 'payment_type', $read_only_fields ) && isset( $_POST['payment_type'] ) ) {
		$order->payment_type = sanitize_text_field( $_POST['payment_type'] );
	}
	if ( ! in_array( 'cardtype', $read_only_fields ) && isset( $_POST['cardtype'] ) ) {
		$order->cardtype = sanitize_text_field( $_POST['cardtype'] );
	}
	if ( ! in_array( 'accountnumber', $read_only_fields ) && isset( $_POST['accountnumber'] ) ) {
		$order->accountnumber = sanitize_text_field( $_POST['accountnumber'] );
	}
	if ( ! in_array( 'expirationmonth', $read_only_fields ) && isset( $_POST['expirationmonth'] ) ) {
		$order->expirationmonth = sanitize_text_field( $_POST['expirationmonth'] );
	}
	if ( ! in_array( 'expirationyear', $read_only_fields ) && isset( $_POST['expirationyear'] ) ) {
		$order->expirationyear = sanitize_text_field( $_POST['expirationyear'] );
	}

	if ( ! in_array( 'status', $read_only_fields ) && isset( $_POST['status'] ) ) {
		$order->status = pmpro_sanitize_with_safelist( $_POST['status'], pmpro_getOrderStatuses() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}
	if ( ! in_array( 'gateway', $read_only_fields ) && isset( $_POST['gateway'] ) ) {
		$order->gateway = sanitize_text_field( $_POST['gateway'] );
	}
	if ( ! in_array( 'gateway_environment', $read_only_fields ) && isset( $_POST['gateway_environment'] ) ) {
		$order->gateway_environment = sanitize_text_field( $_POST['gateway_environment'] );
	}
	if ( ! in_array( 'payment_transaction_id', $read_only_fields ) && isset( $_POST['payment_transaction_id'] ) ) {
		$order->payment_transaction_id = sanitize_text_field( $_POST['payment_transaction_id'] );
	}
	if ( ! in_array( 'subscription_transaction_id', $read_only_fields ) && isset( $_POST['subscription_transaction_id'] ) ) {
		$order->subscription_transaction_id = sanitize_text_field( $_POST['subscription_transaction_id'] );
	}
	if ( ! in_array( 'notes', $read_only_fields ) && isset( $_POST['notes'] ) ) {
		global $allowedposttags;
		$order->notes = wp_kses( wp_unslash( $_REQUEST['notes'] ), $allowedposttags );
	}
	if ( ! in_array( 'timestamp', $read_only_fields ) && isset( $_POST['ts_year'] ) && isset( $_POST['ts_month'] ) && isset( $_POST['ts_day'] ) && isset( $_POST['ts_hour'] ) && isset( $_POST['ts_minute'] ) ) {
		$year   = intval( $_POST['ts_year'] );
		$month  = intval( $_POST['ts_month'] );
		$day    = intval( $_POST['ts_day'] );
		$hour   = intval( $_POST['ts_hour'] );
		$minute = intval( $_POST['ts_minute'] );
		$date = get_gmt_from_date( $year . '-' . $month . '-' . $day . ' ' . $hour . ':' . $minute . ':00' , 'U' );
		$order->timestamp = $date; // Passed 'U' to get_gmt_from_date() so that we get a Unix timesamp.
	}

	// affiliate stuff
	$affiliates = apply_filters( 'pmpro_orders_show_affiliate_ids', false );
	if ( ! empty( $affiliates ) ) {
		if ( ! in_array( 'affiliate_id', $read_only_fields ) ) {
			$order->affiliate_id = sanitize_text_field( $_POST['affiliate_id'] );
		}
		if ( ! in_array( 'affiliate_subid', $read_only_fields ) ) {
			$order->affiliate_subid = sanitize_text_field( $_POST['affiliate_subid'] );
		}
	}

	// check nonce for saving
	$nonceokay = true;
	if ( empty( $_REQUEST['pmpro_orders_nonce'] ) || ! check_admin_referer( 'save', 'pmpro_orders_nonce' ) ) {
		$nonceokay = false;
	}

	// save
	if ( $nonceokay && false !== $order->saveOrder() ) {
		$order_id = $order->id;
		$pmpro_msg  = __( 'Order saved successfully.', 'paid-memberships-pro' );
		$pmpro_msgt = 'success';
	} else {
		$pmpro_msg  = __( 'Error saving order.', 'paid-memberships-pro' );
		$pmpro_msgt = 'error';
	}

	// also update the discount code if needed
	if( isset( $_REQUEST['discount_code_id'] ) ) {
		$order->updateDiscountCode( intval( $_REQUEST['discount_code_id'] ) );
	}
} else {
	// order passed?
	// Checking orderby as order could be the order ID or whether the List Table should be sorted ascending or descending.
	if ( ! empty( $_REQUEST['order'] ) && ! isset( $_REQUEST['orderby'] ) ) {
		$order_id = intval( $_REQUEST['order'] );
		if ( $order_id > 0 ) {
			$order = new MemberOrder( $order_id );
		} elseif ( ! empty( $_REQUEST['copy'] ) ) {
			$order = new MemberOrder( intval( $_REQUEST['copy'] ) );

			// new id
			$order->id = null;

			// new code
			$order->code = $order->getRandomCode();
		} else {
			$order = new MemberOrder();            // new order

			// defaults
			$order->code = $order->getRandomCode();
			$order->user_id = '';
			$order->membership_id = '';
			$order->billing = new stdClass();
			$order->billing->name = '';
			$order->billing->street = '';
			$order->billing->city = '';
			$order->billing->state = '';
			$order->billing->zip = '';
			$order->billing->country = '';
			$order->billing->phone = '';
			$order->discount_code = '';
			$order->subtotal = '';
			$order->tax = '';
			$order->couponamount = '';
			$order->total = '';
			$order->payment_type = '';
			$order->cardtype = '';
			$order->accountnumber = '';
			$order->expirationmonth = '';
			$order->expirationyear = '';
			$order->status = 'success';
			$order->gateway = pmpro_getOption( 'gateway' );
			$order->gateway_environment = pmpro_getOption( 'gateway_environment' );
			$order->payment_transaction_id = '';
			$order->subscription_transaction_id = '';
			$order->affiliate_id = '';
			$order->affiliate_subid = '';
			$order->notes = '';
		}
	}
}

require_once( dirname( __FILE__ ) . '/admin_header.php' ); ?>

<hr class="wp-header-end">

<?php
	if ( function_exists( 'pmpro_add_email_order_modal' ) ) {
		// Load the email order modal.
		pmpro_add_email_order_modal();
	}
?>

<?php if ( ! empty( $order ) ) { ?>

	<?php if ( ! empty( $order->id ) ) { 
		$refund_text = esc_html(
			sprintf(
				// translators: %s is the Order Code.
				__( 'Refund order %s at the payment gateway. This action is permanent. The user and admin will receive an email confirmation after the refund is processed. Are you sure you want to refund this order?', 'paid-memberships-pro' ),
				str_replace( "'", '', $order->code )
			)
		);

		$refund_nonce_url = wp_nonce_url(
			add_query_arg(
				[
					'page'   => 'pmpro-orders',
					'action' => 'refund_order',
					'refund' => $order->id,
					'order'  => $order->id
				],
				admin_url( 'admin.php' )
			),
			'refund_order',
			'pmpro_orders_nonce'
		);
		?>
	<br class="wp-clearfix">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Edit Order', 'paid-memberships-pro' ); ?> ID: <?php echo esc_html( $order->id ); ?></h1>
		<a title="<?php esc_attr_e( 'Print', 'paid-memberships-pro' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'action' => 'pmpro_orders_print_view', 'order' => $order->id ), admin_url( 'admin-ajax.php' ) ) ); ?>" target="_blank" class="page-title-action pmpro-has-icon pmpro-has-icon-printer"><?php esc_html_e( 'Print', 'paid-memberships-pro' ); ?></a>
		<a title="<?php esc_attr_e( 'Email', 'paid-memberships-pro' ); ?>" href="#TB_inline?width=600&height=200&inlineId=email_invoice" class="thickbox email_link page-title-action pmpro-has-icon pmpro-has-icon-email" data-order="<?php echo esc_html( $order->id ); ?>"><?php esc_html_e( 'Email', 'paid-memberships-pro' ); ?></a>
		<a title="<?php esc_attr_e( 'View', 'paid-memberships-pro' ); ?>" href="<?php echo esc_url( pmpro_url("invoice", "?invoice=" . $order->code ) ) ?>" target="_blank" class="page-title-action pmpro-has-icon pmpro-has-icon-admin-users"><?php esc_html_e( 'View', 'paid-memberships-pro' ); ?></a>
		<?php
			if( pmpro_allowed_refunds( $order ) ) {
				printf(
					'<a title="%1$s" href="%2$s" class="page-title-action pmpro-has-icon pmpro-has-icon-image-rotate">%3$s</a>',
					esc_attr__( 'Refund', 'paid-memberships-pro' ),
					esc_js( 'javascript:pmpro_askfirst(' . wp_json_encode( $refund_text ) . ', ' . wp_json_encode( $refund_nonce_url ) . '); void(0);' ),
					esc_html__( 'Refund', 'paid-memberships-pro' )
				);
			}
		?>
	<?php } else { ?>
		<h1 class="wp-heading-inline"><?php esc_html_e( 'New Order', 'paid-memberships-pro' ); ?></h1>
	<?php } ?>

	<?php if ( ! empty( $pmpro_msg ) ) { ?>
		<div id="message" class="
		<?php
		if ( $pmpro_msgt == 'success' ) {
			echo 'updated fade';
		} else {
			echo 'error';
		}
		?>
		"><p><?php echo $pmpro_msg; ?></p></div>
	<?php } ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'save', 'pmpro_orders_nonce' ); ?>

		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row" valign="top"><label for="code"><?php esc_html_e( 'Code', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
						if ( in_array( 'code', $read_only_fields ) ) {
							echo esc_html( $order->code );
						} else { ?>
							<input id="code" name="code" type="text" value="<?php echo esc_attr( $order->code ); ?>" />
						<?php
						}
					?>
					<p class="description"><?php esc_html_e( 'A randomly generated code that serves as a unique, non-sequential invoice number.', 'paid-memberships-pro' ); ?></p>
					<?php if ( $order_id < 0 ) { ?>
						<p class="description"><?php esc_html_e( 'Randomly generated for you.', 'paid-memberships-pro' ); ?></p>
					<?php } ?>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="ts_month"><?php esc_html_e( 'Date', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'timestamp', $read_only_fields ) && $order_id > 0 ) {
						echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $order->getTimestamp() ) );
					} else {
						// set up date vars
						if ( ! empty( $order->timestamp ) ) {
							$timestamp = $order->getTimestamp();
						} else {
							$timestamp = current_time( 'timestamp' );
						}						
						$year   = date( 'Y', $timestamp );
						$month  = date( 'n', $timestamp );
						$day    = date( 'j', $timestamp );
						$hour   = date( 'H', $timestamp );
						$minute = date( 'i', $timestamp );
						$second = date( 's', $timestamp );
						?>
						<select id="ts_month" name="ts_month">
						<?php
						for ( $i = 1; $i < 13; $i ++ ) {
						?>
							<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $i, $month ); ?>>
							<?php echo esc_html( date_i18n( 'F', mktime( 0, 0, 0, $i, 2 ) ) ); ?>
							</option>
						<?php
						}
						?>
						</select>
						<input name="ts_day" type="text" size="2" value="<?php echo esc_attr( $day ); ?>"/>
						<input name="ts_year" type="text" size="4" value="<?php echo esc_attr( $year ); ?>"/>
						<?php esc_html_e( 'at', 'paid-memberships-pro' ); ?>
						<input name="ts_hour" type="text" size="2" value="<?php echo esc_attr( $hour ); ?>"/> :
						<input name="ts_minute" type="text" size="2" value="<?php echo esc_attr( $minute ); ?>"/>
					<?php } ?>
				</td>
			</tr>
			</tbody>
		</table>
		<hr />
		<h2><?php esc_html_e( 'Member Information', 'paid-memberships-pro' ); ?></h2>
		<table class="form-table">
			<tbody>
			<tr>
			<tr>
				<th scope="row" valign="top"><label for="user_id"><?php esc_html_e( 'User ID', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
						if ( in_array( 'user_id', $read_only_fields ) && $order_id > 0 ) {
							echo esc_html( $order->user_id );
						} else { 
							$user_id = ! empty( $_REQUEST['user'] ) ? intval( $_REQUEST['user'] ) : $order->user_id;
							?>
							<input id="user_id" name="user_id" type="text" value="<?php echo esc_attr( $user_id ); ?>" size="10" />
						<?php
						}
					?>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="membership_id"><?php esc_html_e( 'Membership Level ID', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
						if ( in_array( 'membership_id', $read_only_fields ) && $order_id > 0 ) {
						echo esc_html( $order->membership_id );
						} else { ?>
							<input id="membership_id" name="membership_id" type="text" value="<?php echo esc_attr( $order->membership_id ); ?>" size="10" />
						<?php
						}
					?>
				</td>
			</tr>
			</tbody>
		</table>
		<hr />
		<h2>
			<?php esc_html_e( 'Billing Address', 'paid-memberships-pro' ); ?>
			<?php if ( ! $order->has_billing_address() ) { ?>
				<a href="javascript:void(0);" id="show_billing_action"><?php esc_html_e( 'Show Billing Address Fields', 'paid-memberships-pro' ); ?></a>
			<?php } ?>
		</h2>
		<table id="billing_address_fields" class="form-table"<?php if ( ! $order->has_billing_address() ) { ?> style="display: none;"<?php } ?>>
			<tbody>
			<tr>
				<th scope="row" valign="top"><label for="billing_name"><?php esc_html_e( 'Billing Name', 'paid-memberships-pro' ); ?>:</label>
				</th>
				<td>
					<?php
					if ( in_array( 'billing_name', $read_only_fields ) && $order_id > 0 ) {
						echo esc_html( $order->billing_name );
					} else {
										?>
											<input id="billing_name" name="billing_name" type="text" size="50"
												   value="<?php echo esc_attr( $order->billing->name ); ?>"/>
					<?php } ?>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="billing_street"><?php esc_html_e( 'Billing Street', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'billing_street', $read_only_fields ) && $order_id > 0 ) {
						echo esc_html( $order->billing_street );
					} else {
										?>
										<input id="billing_street" name="billing_street" type="text" size="50"
											   value="<?php echo esc_attr( $order->billing->street ); ?>"/></td>
									<?php } ?>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="billing_city"><?php esc_html_e( 'Billing City', 'paid-memberships-pro' ); ?>:</label>
				</th>
				<td>
					<?php
					if ( in_array( 'billing_city', $read_only_fields ) && $order_id > 0 ) {
						echo esc_html( $order->billing_city );
					} else {
										?>
										<input id="billing_city" name="billing_city" type="text" size="50"
											   value="<?php echo esc_attr( $order->billing->city ); ?>"/></td>
									<?php } ?>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="billing_state"><?php esc_html_e( 'Billing State', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'billing_state', $read_only_fields ) && $order_id > 0 ) {
						echo esc_html( $order->billing_state );
					} else {
										?>
										<input id="billing_state" name="billing_state" type="text" size="50"
											   value="<?php echo esc_attr( $order->billing->state ); ?>"/></td>
									<?php } ?>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="billing_zip"><?php esc_html_e( 'Billing Postal Code', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'billing_zip', $read_only_fields ) && $order_id > 0 ) {
						echo esc_html( $order->billing_zip );
					} else {
										?>
										<input id="billing_zip" name="billing_zip" type="text" size="50"
											   value="<?php echo esc_attr( $order->billing->zip ); ?>"/></td>
									<?php } ?>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="billing_country"><?php esc_html_e( 'Billing Country', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'billing_country', $read_only_fields ) && $order_id > 0 ) {
						echo esc_html( $order->billing_country );
					} else {
										?>
											<input id="billing_country" name="billing_country" type="text" size="50"
												   value="<?php echo esc_attr( $order->billing->country ); ?>"/>
					<?php } ?>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="billing_phone"><?php esc_html_e( 'Billing Phone', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'billing_phone', $read_only_fields ) && $order_id > 0 ) {
						echo esc_html( $order->billing_phone );
					} else {
										?>
											<input id="billing_phone" name="billing_phone" type="text" size="50"
												   value="<?php echo esc_attr( $order->billing->phone ); ?>"/>
					<?php } ?>
				</td>
			</tr>
			</tbody>
		</table> <!-- end #billing_address_fields -->
		<script>
			// Script to show billing fields if they are empty and hidden by default on this form.
			jQuery(document).ready(function() {
				jQuery('#show_billing_action').click(function() {
					jQuery('#show_billing_action').hide();
					jQuery('#billing_address_fields').show();
				});
			});
		</script>
		<hr />
		<h2><?php esc_html_e( 'Payment Information', 'paid-memberships-pro' ); ?></h2>
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
				<th scope="row" valign="top"><label for="discount_code_id"><?php esc_html_e( 'Discount Code', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
						if ( in_array( 'discount_code_id', $read_only_fields ) && $order_id > 0 ) {
							if( ! empty( $order->discount_code ) ) {
								echo esc_html( $order->discount_code->code );
							} else {
								esc_html_e( 'N/A', 'paid-memberships-pro' );
							}
						} else { ?>
							<select id="discount_code_id" name="discount_code_id">
								<option value="0" <?php selected( $discount_code_id, 0); ?>>-- <?php _e("None", 'paid-memberships-pro' );?> --</option>
								<?php foreach ( $codes as $code ) { ?>
									<option value="<?php echo esc_attr( $code->id ); ?>" <?php selected( $discount_code_id, $code->id ); ?>><?php echo esc_html( $code->code ); ?></option>
								<?php } ?>
							</select>
							<?php
						} ?>
				</td>
			</tr>
			<?php } ?>
			<tr>
				<th scope="row" valign="top"><label for="subtotal"><?php esc_html_e( 'Sub Total', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'subtotal', $read_only_fields ) && $order_id > 0 ) {
						echo esc_html( $order->subtotal );
					} else {
										?>
											<input id="subtotal" name="subtotal" type="text" size="10"
												   value="<?php echo esc_attr( $order->subtotal ); ?>"/>
					<?php } ?>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="tax"><?php esc_html_e( 'Tax', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'tax', $read_only_fields ) && $order_id > 0 ) {
						echo esc_html( $order->tax );
					} else {
										?>
											<input id="tax" name="tax" type="text" size="10"
												   value="<?php echo esc_attr( $order->tax ); ?>"/>
					<?php } ?>
				</td>
			</tr>
			<?php
				// Hiding couponamount by default.
				$coupons = apply_filters( 'pmpro_orders_show_coupon_amounts', false );
				if ( ! empty( $coupons ) ) { ?>
				<tr>
					<th scope="row" valign="top"><label for="couponamount"><?php esc_html_e( 'Coupon Amount', 'paid-memberships-pro' ); ?>:</label>
					</th>
					<td>
					<?php
						if ( in_array( 'couponamount', $read_only_fields ) && $order_id > 0 ) {
							echo $order->couponamount;
						} else {
						?>
							<input id="couponamount" name="couponamount" type="text" size="10" value="<?php echo esc_attr( $order->couponamount ); ?>"/>
						<?php
						}
					?>
					</td>
				</tr>
				<?php
				}
			?>
			<tr>
				<th scope="row" valign="top"><label for="total"><?php esc_html_e( 'Total', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'total', $read_only_fields ) && $order_id > 0 ) {
						echo esc_html( $order->total );
					} else {
										?>
											<input id="total" name="total" type="text" size="10"
												   value="<?php echo esc_attr( $order->total ); ?>"/>
					<?php } ?>
				</td>
			</tr>

			<tr>
				<th scope="row" valign="top"><label for="payment_type"><?php esc_html_e( 'Payment Type', 'paid-memberships-pro' ); ?>:</label>
				</th>
				<td>
					<?php
					if ( in_array( 'payment_type', $read_only_fields ) && $order_id > 0 ) {
						echo esc_html( $order->payment_type );
					} else {
										?>
											<input id="payment_type" name="payment_type" type="text" size="50"
												   value="<?php echo esc_attr( $order->payment_type ); ?>"/>
					<?php } ?>
					<p class="description"><?php esc_html_e( 'e.g. PayPal Express, PayPal Standard, Credit Card.', 'paid-memberships-pro' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="cardtype"><?php esc_html_e( 'Card Type', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'cardtype', $read_only_fields ) && $order_id > 0 ) {
						echo esc_html( $order->cardtype );
					} else {
										?>
											<input id="cardtype" name="cardtype" type="text" size="50"
												   value="<?php echo esc_attr( $order->cardtype ); ?>"/>
					<?php } ?>
					<p class="description"><?php esc_html_e( 'e.g. Visa, MasterCard, AMEX, etc', 'paid-memberships-pro' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="accountnumber"><?php esc_html_e( 'Account Number', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'accountnumber', $read_only_fields ) && $order_id > 0 ) {
						echo esc_html( $order->accountnumber );
					} else {
										?>
											<input id="accountnumber" name="accountnumber" type="text" size="50"
												   value="<?php echo esc_attr( $order->accountnumber ); ?>"/>
					<?php } ?>
					<p class="description"><?php esc_html_e( 'Obscure all but last 4 digits.', 'paid-memberships-pro' ); ?></p>
				</td>
			</tr>
			<?php
			if ( in_array( 'ExpirationDate', $read_only_fields ) && $order_id > 0 ) {
				?>

				<tr>
				    <th scope="row" valign="top"><label
						for="expirationmonth"><?php esc_html_e( 'Expiration Month', 'paid-memberships-pro' ); ?>:</label></th>
				    <td>
					<?php echo esc_html( $order->expirationmonth . '/' . $order->expirationyear ); ?>
				    </td>
				</tr>

				<?php
			} else { ?>
				<tr>
					<th scope="row" valign="top"><label for="expirationmonth"><?php esc_html_e( 'Expiration', 'paid-memberships-pro' ); ?>:</label></th>
					<td>
						<input id="expirationmonth" name="expirationmonth" type="text" size="10"
				   value="<?php echo esc_attr( $order->expirationmonth ); ?>"/> /
						<input id="expirationyear" name="expirationyear" type="text" size="10"
				   value="<?php echo esc_attr( $order->expirationyear ); ?>"/>
						<span class="description"><?php esc_html_e( 'MM/YYYY', 'paid-memberships-pro' );?></span>
					</td>
				</tr>
			<?php } ?>
			<tr>
				<th scope="row" valign="top"><label for="status"><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'status', $read_only_fields ) && $order_id > 0 ) {
						echo esc_html( ucwords( $order->status ) );
					} else { ?>
					<?php
						$statuses = pmpro_getOrderStatuses();
						?>
						<select id="status" name="status">
							<?php foreach ( $statuses as $status ) { ?>
								<option
									value="<?php echo esc_attr( $status ); ?>" <?php selected( $order->status, $status ); ?>><?php echo esc_html( $status ); ?></option>
							<?php } ?>
						</select>
						<?php
						}
					?>
				</td>
			</tr>
			</tbody>
		</table>
		<hr />
		<h2><?php esc_html_e( 'Payment Gateway Information', 'paid-memberships-pro' ); ?></h2>
		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row" valign="top"><label for="gateway"><?php esc_html_e( 'Gateway', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'gateway', $read_only_fields ) && $order_id > 0 ) {
						echo esc_html( $order->gateway );
					} else {
					?>
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
					<?php } ?>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label
						for="gateway_environment"><?php esc_html_e( 'Gateway Environment', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'gateway_environment', $read_only_fields ) && $order_id > 0 ) {
						echo esc_html( $order->gateway_environment );
					} else {
					?>
						<select name="gateway_environment">
							<option value="sandbox" <?php if ( $order->gateway_environment == 'sandbox' ) { ?>selected="selected"<?php } ?>><?php esc_html_e( 'Sandbox/Testing', 'paid-memberships-pro' ); ?></option>
							<option value="live" <?php if ( $order->gateway_environment == 'live' ) { ?>selected="selected"<?php } ?>><?php esc_html_e( 'Live/Production', 'paid-memberships-pro' ); ?></option>
						</select>
					<?php } ?>
				</td>
			</tr>

			<tr>
				<th scope="row" valign="top"><label
						for="payment_transaction_id"><?php esc_html_e( 'Payment Transaction ID', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'payment_transaction_id', $read_only_fields ) && $order_id > 0 ) {
						echo esc_html( $order->payment_transaction_id );
					} else {
										?>
											<input id="payment_transaction_id" name="payment_transaction_id" type="text" size="50"
												   value="<?php echo esc_attr( $order->payment_transaction_id ); ?>"/>
					<?php } ?>
					<p class="description"><?php esc_html_e( 'Generated by the gateway. Useful to cross reference orders.', 'paid-memberships-pro' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label
						for="subscription_transaction_id"><?php esc_html_e( 'Subscription Transaction ID', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'subscription_transaction_id', $read_only_fields ) && $order_id > 0 ) {
						echo $order->subscription_transaction_id;
					} else { ?>
						<input id="subscription_transaction_id" name="subscription_transaction_id" type="text" size="50" value="<?php echo esc_attr( $order->subscription_transaction_id ); ?>"/>
						<?php if ( $order->is_renewal() ) { ?>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 's' => $order->subscription_transaction_id ), admin_url( 'admin.php' ) ) ); ?>" title="<?php esc_attr_e( 'View all orders for this subscription', 'paid-memberships-pro' ); ?>" class="pmpro_order-renewal"><?php esc_html_e( 'Renewal', 'paid-memberships-pro' ); ?></a>
						<?php } ?>
					<?php } ?>
					<p class="description"><?php esc_html_e( 'Generated by the gateway. Useful to cross reference subscriptions.', 'paid-memberships-pro' ); ?></p>
				</td>
			</tr>
			</tbody>
		</table>
		<hr />
		<h2><?php esc_html_e( 'Additional Order Information', 'paid-memberships-pro' ); ?></h2>
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
						<?php
						if ( in_array( 'affiliate_id', $read_only_fields ) && $order_id > 0 ) {
							echo esc_html( $order->affiliate_id );
						} else {
						?>
							<input id="affiliate_id" name="affiliate_id" type="text" size="50" value="<?php echo esc_attr( $order->affiliate_id ); ?>"/>
						<?php } ?>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="affiliate_subid"><?php esc_html_e( 'Affiliate SubID', 'paid-memberships-pro' ); ?>
							:</label></th>
					<td>
						<?php
						if ( in_array( 'affiliate_subid', $read_only_fields ) && $order_id > 0 ) {
							echo esc_html( $order->affiliate_subid );
						} else {
						?>
							<input id="affiliate_subid" name="affiliate_subid" type="text" size="50" value="<?php echo esc_attr( $order->affiliate_subid ); ?>"/>
						<?php } ?>
					</td>
				</tr>
			<?php } ?>

			<?php
				$tospage_id = pmpro_getOption( 'tospage' );
				$consent_entry = $order->get_tos_consent_log_entry();

				if( !empty( $tospage_id ) || !empty( $consent_entry ) ) {
				?>
				<tr>
					<th scope="row" valign="top"><label for="tos_consent"><?php esc_html_e( 'TOS Consent', 'paid-memberships-pro' ); ?>:</label></th>
					<td id="tos_consent">
						<?php
							if( !empty( $consent_entry ) ) {
								echo esc_html( pmpro_consent_to_text( $consent_entry ) );
							} else {
								esc_html_e( 'N/A', 'paid-memberships-pro' );
							}
						?>
					</td>
				</tr>
				<?php
				}
			?>
			<tr>
				<th scope="row" valign="top"><label for="notes"><?php esc_html_e( 'Notes', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'notes', $read_only_fields ) && $order_id > 0 ) {
						echo wp_kses_post( $order->notes );
					} else {
					?>
						<textarea id="notes" name="notes" rows="5" cols="80"><?php echo esc_textarea( $order->notes ); ?></textarea>
					<?php } ?>
				</td>
			</tr>

			<?php do_action( 'pmpro_after_order_settings', $order ); ?>

			</tbody>
		</table>

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

		<p class="submit topborder">
			<input name="order" type="hidden" value="
			<?php
			if ( ! empty( $order->id ) ) {
				echo esc_html( $order->id );
			} else {
				echo esc_html( $order_id );
			}
			?>
			"/>
			<input name="save" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Order', 'paid-memberships-pro' ); ?>"/>
			<input name="cancel" type="button" class="cancel button-secondary" value="<?php esc_attr_e( 'Cancel', 'paid-memberships-pro' ); ?>"
				   onclick="location.href='<?php echo esc_url( admin_url( 'admin.php?page=pmpro-orders' ) ); ?>';"/>
		</p>

	</form>

<?php } else { ?>

	<form id="posts-filter" method="get" action="">

		<h1 class="wp-heading-inline"><?php esc_html_e( 'Orders', 'paid-memberships-pro' ); ?></h1>
		<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 'order' => -1 ), admin_url( 'admin.php' ) ) ); ?>" class="page-title-action pmpro-has-icon pmpro-has-icon-plus"><?php esc_html_e( 'Add New Order', 'paid-memberships-pro' ); ?></a>

		<?php
		// build the export URL
		$export_url = admin_url( 'admin-ajax.php?action=orders_csv' );
		$url_params = array(
			'filter'          => isset( $_REQUEST['filter'] ) ? trim( sanitize_text_field( $_REQUEST['filter'] ) ) : 'all',
			's'               => isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : '',
			'l'               => isset( $_REQUEST['l'] ) ? sanitize_text_field( $_REQUEST['l'] ) : false,
			'start-month'     => isset( $_REQUEST['start-month'] ) ? intval( $_REQUEST['start-month'] ) : '1',
			'start-day'       => isset( $_REQUEST['start-day'] ) ? intval( $_REQUEST['start-day'] ) : '1',
			'start-year'      => isset( $_REQUEST['start-year'] ) ? intval( $_REQUEST['start-year'] ) : date( 'Y', $now ),
			'end-month'       => isset( $_REQUEST['end-month'] ) ? intval( $_REQUEST['end-month'] ) : date( 'n', $now ),
			'end-day'         => isset( $_REQUEST['end-day'] ) ? intval( $_REQUEST['end-day'] ) : date( 'j', $now ),
			'end-year'        => isset( $_REQUEST['end-year'] ) ? intval( $_REQUEST['end-year'] ) : date( 'Y', $now ),
			'predefined-date' => isset( $_REQUEST['predefined-date'] ) ? sanitize_text_field( $_REQUEST['predefined-date'] ) : 'This Month',
			'discount-code'	  => isset( $_REQUEST['discount-code'] ) ? intval( $_REQUEST['discount-code'] ) : false,
			'status'          => isset( $_REQUEST['status'] ) ? sanitize_text_field( $_REQUEST['status'] ) : '',
		);
		$export_url = add_query_arg( $url_params, $export_url );
		?>
		<a target="_blank" href="<?php echo esc_url( $export_url ); ?>" class="page-title-action pmpro-has-icon pmpro-has-icon-download"><?php esc_html_e( 'Export to CSV', 'paid-memberships-pro' ); ?></a>

		<?php if ( ! empty( $pmpro_msg ) ) { ?>
			<div id="message" class="
			<?php
			if ( $pmpro_msgt == 'success' ) {
				echo 'updated fade';
			} else {
				echo 'error';
			}
			?>
			"><p><?php echo $pmpro_msg; ?></p></div>
		<?php }
		$orders_list_table = new PMPro_Orders_List_Table();
		$orders_list_table->prepare_items();
		$orders_list_table->search_box( __( 'Search Orders', 'paid-memberships-pro' ), 'paid-memberships-pro' );
		$orders_list_table->display();

		?>
	</form>
<?php }

require_once( dirname( __FILE__ ) . '/admin_footer.php' );
