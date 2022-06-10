<?php
// only admins can get this
if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_orders' ) ) ) {
	die( __( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
}

// vars
global $wpdb;

$now = current_time( 'timestamp' );

if ( isset( $_REQUEST['s'] ) ) {
	$s = sanitize_text_field( trim( $_REQUEST['s'] ) );
} else {
	$s = '';
}

if ( isset( $_REQUEST['l'] ) ) {
	$l = intval( $_REQUEST['l'] );
} else {
	$l = false;
}

if ( isset( $_REQUEST['discount-code'] ) ) {
	$discount_code = intval( $_REQUEST['discount-code'] );
} else {
	$discount_code = false;
}

if ( isset( $_REQUEST['start-month'] ) ) {
	$start_month = intval( $_REQUEST['start-month'] );
} else {
	$start_month = '1';
}

if ( isset( $_REQUEST['start-day'] ) ) {
	$start_day = intval( $_REQUEST['start-day'] );
} else {
	$start_day = '1';
}

if ( isset( $_REQUEST['start-year'] ) ) {
	$start_year = intval( $_REQUEST['start-year'] );
} else {
	$start_year = date( 'Y', $now );
}

if ( isset( $_REQUEST['end-month'] ) ) {
	$end_month = intval( $_REQUEST['end-month'] );
} else {
	$end_month = date( 'n', $now );
}

if ( isset( $_REQUEST['end-day'] ) ) {
	$end_day = intval( $_REQUEST['end-day'] );
} else {
	$end_day = date( 'j', $now );
}

if ( isset( $_REQUEST['end-year'] ) ) {
	$end_year = intval( $_REQUEST['end-year'] );
} else {
	$end_year = date( 'Y', $now );
}

if ( isset( $_REQUEST['predefined-date'] ) ) {
	$predefined_date = sanitize_text_field( $_REQUEST['predefined-date'] );
} else {
	$predefined_date = 'This Month';
}

if ( isset( $_REQUEST['status'] ) ) {
	$status = sanitize_text_field( $_REQUEST['status'] );
} else {
	$status = '';
}

if ( isset( $_REQUEST['filter'] ) ) {
	$filter = sanitize_text_field( $_REQUEST['filter'] );
} else {
	$filter = 'all';
}

// some vars for the search
if ( isset( $_REQUEST['pn'] ) ) {
	$pn = intval( $_REQUEST['pn'] );
} else {
	$pn = 1;
}

if ( isset( $_REQUEST['limit'] ) ) {
	$limit = intval( $_REQUEST['limit'] );
} else {
	/**
	 * Filter to set the default number of items to show per page
	 * on the Orders page in the admin.
	 *
	 * @since 1.8.4.5
	 *
	 * @param int $limit The number of items to show per page.
	 */
	$limit = apply_filters( 'pmpro_orders_per_page', 15 );
}

$end   = $pn * $limit;
$start = $end - $limit;

// filters
if ( empty( $filter ) || $filter === 'all' ) {
	$condition = '1=1';
	$filter    = 'all';
} elseif ( $filter == 'within-a-date-range' ) {
	$start_date = $start_year . '-' . $start_month . '-' . $start_day;
	$end_date   = $end_year . '-' . $end_month . '-' . $end_day;

	// add times to dates
	$start_date = $start_date . ' 00:00:00';
	$end_date   = $end_date . ' 23:59:59';

	$condition = "o.timestamp BETWEEN '" . esc_sql( $start_date ) . "' AND '" . esc_sql( $end_date ) . "'";
} elseif ( $filter == 'predefined-date-range' ) {
	if ( $predefined_date == 'Last Month' ) {
		$start_date = date( 'Y-m-d', strtotime( 'first day of last month', $now ) );
		$end_date   = date( 'Y-m-d', strtotime( 'last day of last month', $now ) );
	} elseif ( $predefined_date == 'This Month' ) {
		$start_date = date( 'Y-m-d', strtotime( 'first day of this month', $now ) );
		$end_date   = date( 'Y-m-d', strtotime( 'last day of this month', $now ) );
	} elseif ( $predefined_date == 'This Year' ) {
		$year       = date( 'Y', $now );
		$start_date = date( 'Y-m-d', strtotime( "first day of January $year", $now ) );
		$end_date   = date( 'Y-m-d', strtotime( "last day of December $year", $now ) );
	} elseif ( $predefined_date == 'Last Year' ) {
		$year       = date( 'Y', $now ) - 1;
		$start_date = date( 'Y-m-d', strtotime( "first day of January $year", $now ) );
		$end_date   = date( 'Y-m-d', strtotime( "last day of December $year", $now ) );
	}

	// add times to dates
	$start_date = $start_date . ' 00:00:00';
	$end_date   = $end_date . ' 23:59:59';

	$condition = "o.timestamp BETWEEN '" . esc_sql( $start_date ) . "' AND '" . esc_sql( $end_date ) . "'";
} elseif ( $filter == 'within-a-level' ) {
	$condition = 'o.membership_id = ' . esc_sql( $l );
} elseif ( $filter == 'with-discount-code' ) {
	$condition = 'dc.code_id = ' . esc_sql( $discount_code );
} elseif ( $filter == 'within-a-status' ) {
	$condition = "o.status = '" . esc_sql( $status ) . "' ";
} elseif ( $filter == 'only-paid' ) {
	$condition = "o.total > 0";
} elseif( $filter == 'only-free' ) {
	$condition = "o.total = 0";
}

$condition = apply_filters( 'pmpro_admin_orders_query_condition', $condition, $filter );

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
if ( ! empty( $_REQUEST['order'] ) && $_REQUEST['order'] < 0 ) {
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
		$order->status = pmpro_sanitize_with_safelist( $_POST['status'], pmpro_getOrderStatuses() );
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
	if ( ! empty( $_REQUEST['order'] ) ) {
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

require_once( dirname( __FILE__ ) . '/admin_header.php' );

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
	<hr class="wp-header-end">	
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
						} else { ?>
							<input id="user_id" name="user_id" type="text" value="<?php echo esc_attr( $order->user_id ); ?>" size="10" />
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
				   onclick="location.href='<?php echo esc_url( admin_url( '/admin.php?page=pmpro-orders' ) ); ?>';"/>
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
			'filter'          => $filter,
			's'               => $s,
			'l'               => $l,
			'start-month'     => $start_month,
			'start-day'       => $start_day,
			'start-year'      => $start_year,
			'end-month'       => $end_month,
			'end-day'         => $end_day,
			'end-year'        => $end_year,
			'predefined-date' => $predefined_date,
			'discount-code'	  => $discount_code,
			'status'          => $status,
		);
		$export_url = add_query_arg( $url_params, $export_url );
		?>
		<a target="_blank" href="<?php echo esc_url( $export_url ); ?>" class="page-title-action pmpro-has-icon pmpro-has-icon-download"><?php esc_html_e( 'Export to CSV', 'paid-memberships-pro' ); ?></a>

		<hr class="wp-header-end">


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

		<p class="search-box">
			<label class="hidden" for="post-search-input"><?php esc_html_e( 'Search Orders', 'paid-memberships-pro' ); ?>:</label>
			<input type="hidden" name="page" value="pmpro-orders"/>
			<input id="post-search-input" type="text" value="<?php echo esc_attr( wp_unslash( $s ) ); ?>" name="s"/>
			<input class="button" type="submit" value="<?php esc_attr_e( 'Search Orders', 'paid-memberships-pro' ); ?>"/>
		</p>

		<div class="tablenav top">
			<?php esc_html_e( 'Show', 'paid-memberships-pro' ); ?>
			<select id="filter" name="filter">
				<option value="all" <?php selected( $filter, 'all' ); ?>><?php esc_html_e( 'All', 'paid-memberships-pro' ); ?></option>
				<option
					value="within-a-date-range" <?php selected( $filter, 'within-a-date-range' ); ?>><?php esc_html_e( 'Within a Date Range', 'paid-memberships-pro' ); ?></option>
				<option
					value="predefined-date-range" <?php selected( $filter, 'predefined-date-range' ); ?>><?php esc_html_e( 'Predefined Date Range', 'paid-memberships-pro' ); ?></option>
				<option
					value="within-a-level" <?php selected( $filter, 'within-a-level' ); ?>><?php esc_html_e( 'Within a Level', 'paid-memberships-pro' ); ?></option>
				<option
					value="with-discount-code" <?php selected( $filter, 'with-discount-code' ); ?>><?php esc_html_e( 'With a Discount Code', 'paid-memberships-pro' ); ?></option>
				<option
					value="within-a-status" <?php selected( $filter, 'within-a-status' ); ?>><?php esc_html_e( 'Within a Status', 'paid-memberships-pro' ); ?></option>
				<option
					value="only-paid" <?php selected( $filter, 'only-paid' ); ?>><?php esc_html_e( 'Only Paid Orders', 'paid-memberships-pro' ); ?></option>
				<option
					value="only-free" <?php selected( $filter, 'only-free' ); ?>><?php esc_html_e( 'Only Free Orders', 'paid-memberships-pro' ); ?></option>

				<?php $custom_filters = apply_filters( 'pmpro_admin_orders_filters', array() ); ?>
				<?php foreach( $custom_filters as $value => $name ) { ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filter, $value ); ?>><?php esc_html_e( $name ); ?></option>
				<?php } ?>
			</select>

			<span id="from"><?php esc_html_e( 'From', 'paid-memberships-pro' ); ?></span>

			<select id="start-month" name="start-month">
				<?php for ( $i = 1; $i < 13; $i ++ ) { ?>
					<option
						value="<?php echo esc_attr( $i ); ?>" <?php selected( $start_month, $i ); ?>><?php echo esc_html( date_i18n( 'F', mktime( 0, 0, 0, $i, 2 ) ) ); ?></option>
				<?php } ?>
			</select>

			<input id='start-day' name="start-day" type="text" size="2"
				   value="<?php echo esc_attr( $start_day ); ?>"/>
			<input id='start-year' name="start-year" type="text" size="4"
				   value="<?php echo esc_attr( $start_year ); ?>"/>


			<span id="to"><?php esc_html_e( 'To', 'paid-memberships-pro' ); ?></span>

			<select id="end-month" name="end-month">
				<?php for ( $i = 1; $i < 13; $i ++ ) { ?>
					<option
						value="<?php echo esc_attr( $i ); ?>" <?php selected( $end_month, $i ); ?>><?php echo esc_html( date_i18n( 'F', mktime( 0, 0, 0, $i, 2 ) ) ); ?></option>
				<?php } ?>
			</select>


			<input id='end-day' name="end-day" type="text" size="2" value="<?php echo esc_attr( $end_day ); ?>"/>
			<input id='end-year' name="end-year" type="text" size="4" value="<?php echo esc_attr( $end_year ); ?>"/>

			<span id="filterby"><?php esc_html_e( 'filter by ', 'paid-memberships-pro' ); ?></span>

			<select id="predefined-date" name="predefined-date">

				<option
					value="<?php echo 'This Month'; ?>" <?php selected( $predefined_date, 'This Month' ); ?>><?php esc_html_e( 'This Month', 'paid-memberships-pro' ); ?></option>
				<option
					value="<?php echo 'Last Month'; ?>" <?php selected( $predefined_date, 'Last Month' ); ?>><?php esc_html_e( 'Last Month', 'paid-memberships-pro' ); ?></option>
				<option
					value="<?php echo 'This Year'; ?>" <?php selected( $predefined_date, 'This Year' ); ?>><?php esc_html_e( 'This Year', 'paid-memberships-pro' ); ?></option>
				<option
					value="<?php echo 'Last Year'; ?>" <?php selected( $predefined_date, 'Last Year' ); ?>><?php esc_html_e( 'Last Year', 'paid-memberships-pro' ); ?></option>

			</select>

			<?php
			// Note: only orders belonging to current levels can be filtered. There is no option for orders belonging to deleted levels
			$levels = pmpro_sort_levels_by_order( pmpro_getAllLevels( true, true ) );
			?>
			<select id="l" name="l">
				<?php foreach ( $levels as $level ) { ?>
					<option
						value="<?php echo esc_attr( $level->id ); ?>" <?php selected( $l, $level->id ); ?>><?php echo esc_html( $level->name ); ?></option>
				<?php } ?>

			</select>

			<?php
			$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM $wpdb->pmpro_discount_codes ";
			$sqlQuery .= "ORDER BY id DESC ";
			$codes = $wpdb->get_results($sqlQuery, OBJECT);
			if ( ! empty( $codes ) ) { ?>
			<select id="discount-code" name="discount-code">
				<?php foreach ( $codes as $code ) { ?>
					<option
						value="<?php echo esc_attr( $code->id ); ?>" <?php selected( $discount_code, $code->id ); ?>><?php echo esc_html( $code->code ); ?></option>
				<?php } ?>
			</select>
			<?php } ?>

			<?php
				$statuses = pmpro_getOrderStatuses();
			?>
			<select id="status" name="status">
				<?php foreach ( $statuses as $the_status ) { ?>
					<option
						value="<?php echo esc_attr( $the_status ); ?>" <?php selected( $the_status, $status ); ?>><?php echo esc_html( $the_status ); ?></option>
				<?php } ?>
			</select>

			<input id="submit" class="button" type="submit" value="<?php esc_attr_e( 'Filter', 'paid-memberships-pro' ); ?>"/>

		<script>
			//update month/year when period dropdown is changed
			jQuery(document).ready(function () {
				jQuery('#filter').change(function () {
					pmpro_ShowMonthOrYear();
				});
			});

			function pmpro_ShowMonthOrYear() {
				var filter = jQuery('#filter').val();
				if (filter == 'all') {
					jQuery('#start-month').hide();
					jQuery('#start-day').hide();
					jQuery('#start-year').hide();
					jQuery('#end-month').hide();
					jQuery('#end-day').hide();
					jQuery('#end-year').hide();
					jQuery('#predefined-date').hide();
					jQuery('#status').hide();
					jQuery('#l').hide();
					jQuery('#discount-code').hide();
					jQuery('#from').hide();
					jQuery('#to').hide();
					jQuery('#submit').show();
					jQuery('#filterby').hide();
				}
				else if (filter == 'within-a-date-range') {
					jQuery('#start-month').show();
					jQuery('#start-day').show();
					jQuery('#start-year').show();
					jQuery('#end-month').show();
					jQuery('#end-day').show();
					jQuery('#end-year').show();
					jQuery('#predefined-date').hide();
					jQuery('#status').hide();
					jQuery('#l').hide();
					jQuery('#discount-code').hide();
					jQuery('#submit').show();
					jQuery('#from').show();
					jQuery('#to').show();
					jQuery('#filterby').hide();
				}
				else if (filter == 'predefined-date-range') {
					jQuery('#start-month').hide();
					jQuery('#start-day').hide();
					jQuery('#start-year').hide();
					jQuery('#end-month').hide();
					jQuery('#end-day').hide();
					jQuery('#end-year').hide();
					jQuery('#predefined-date').show();
					jQuery('#status').hide();
					jQuery('#l').hide();
					jQuery('#discount-code').hide();
					jQuery('#submit').show();
					jQuery('#from').hide();
					jQuery('#to').hide();
					jQuery('#filterby').show();
				}
				else if (filter == 'within-a-level') {
					jQuery('#start-month').hide();
					jQuery('#start-day').hide();
					jQuery('#start-year').hide();
					jQuery('#end-month').hide();
					jQuery('#end-day').hide();
					jQuery('#end-year').hide();
					jQuery('#predefined-date').hide();
					jQuery('#status').hide();
					jQuery('#l').show();
					jQuery('#discount-code').hide();
					jQuery('#submit').show();
					jQuery('#from').hide();
					jQuery('#to').hide();
					jQuery('#filterby').show();
				}
				else if (filter == 'with-discount-code') {
					jQuery('#start-month').hide();
					jQuery('#start-day').hide();
					jQuery('#start-year').hide();
					jQuery('#end-month').hide();
					jQuery('#end-day').hide();
					jQuery('#end-year').hide();
					jQuery('#predefined-date').hide();
					jQuery('#status').hide();
					jQuery('#l').hide();
					jQuery('#discount-code').show();
					jQuery('#submit').show();
					jQuery('#from').hide();
					jQuery('#to').hide();
					jQuery('#filterby').show();
				}
				else if (filter == 'within-a-status') {
					jQuery('#start-month').hide();
					jQuery('#start-day').hide();
					jQuery('#start-year').hide();
					jQuery('#end-month').hide();
					jQuery('#end-day').hide();
					jQuery('#end-year').hide();
					jQuery('#predefined-date').hide();
					jQuery('#status').show();
					jQuery('#l').hide();
					jQuery('#discount-code').hide();
					jQuery('#submit').show();
					jQuery('#from').hide();
					jQuery('#to').hide();
					jQuery('#filterby').show();
				}
				else if(filter == 'only-paid' || filter == 'only-free' ) {
					jQuery('#start-month').hide();
					jQuery('#start-day').hide();
					jQuery('#start-year').hide();
					jQuery('#end-month').hide();
					jQuery('#end-day').hide();
					jQuery('#end-year').hide();
					jQuery('#predefined-date').hide();
					jQuery('#status').hide();
					jQuery('#l').hide();
					jQuery('#discount-code').hide();
					jQuery('#submit').show();
					jQuery('#from').hide();
					jQuery('#to').hide();
					jQuery('#filterby').hide();
				}
			}

			pmpro_ShowMonthOrYear();


		</script>

		<?php
		if ( $s ) {
			$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS o.id FROM $wpdb->pmpro_membership_orders o LEFT JOIN $wpdb->users u ON o.user_id = u.ID LEFT JOIN $wpdb->pmpro_membership_levels l ON o.membership_id = l.id ";

			$join_with_usermeta = apply_filters( 'pmpro_orders_search_usermeta', false );
			if ( $join_with_usermeta ) {
				$sqlQuery .= "LEFT JOIN $wpdb->usermeta um ON o.user_id = um.user_id ";
			}

			if ( $filter === 'with-discount-code' ) {
				$sqlQuery .= "LEFT JOIN $wpdb->pmpro_discount_codes_uses dc ON o.id = dc.order_id ";
			}

			$sqlQuery .= 'WHERE (1=2 ';

			$fields = array(
				'o.id',
				'o.code',
				'o.billing_name',
				'o.billing_street',
				'o.billing_city',
				'o.billing_state',
				'o.billing_zip',
				'o.billing_phone',
				'o.payment_type',
				'o.cardtype',
				'o.accountnumber',
				'o.status',
				'o.gateway',
				'o.gateway_environment',
				'o.payment_transaction_id',
				'o.subscription_transaction_id',
				'u.user_login',
				'u.user_email',
				'u.display_name',
				'l.name',
			);

			if ( $join_with_usermeta ) {
				$fields[] = 'um.meta_value';
			}

			$fields = apply_filters( 'pmpro_orders_search_fields', $fields );

			foreach ( $fields as $field ) {
				$sqlQuery .= ' OR ' . esc_sql( $field ) . " LIKE '%" . esc_sql( $s ) . "%' ";
			}
			$sqlQuery .= ') ';

			$sqlQuery .= 'AND ' . $condition . ' ';

			$sqlQuery .= 'GROUP BY o.id ORDER BY o.id DESC, o.timestamp DESC ';
		} else {
			$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS o.id FROM $wpdb->pmpro_membership_orders o ";

			if ( $filter === 'with-discount-code' ) {
				$sqlQuery .= "LEFT JOIN $wpdb->pmpro_discount_codes_uses dc ON o.id = dc.order_id ";
			}

			$sqlQuery .= "WHERE " . $condition . ' ORDER BY o.id DESC, o.timestamp DESC ';
		}

		$sqlQuery .= "LIMIT $start, $limit";

		$order_ids = $wpdb->get_col( $sqlQuery );

		$totalrows = $wpdb->get_var( 'SELECT FOUND_ROWS() as found_rows' );

		if ( $order_ids ) {
			?>
			<div class="tablenav-pages one-page">
				<span class="displaying-num"><?php printf( __( '%d orders found.', 'paid-memberships-pro' ), $totalrows ); ?></span>
			</div>
			<?php
		}

		// Get all the gateways so we can reference their name later.
		$pmpro_gateways = pmpro_gateways();

		// Get the value for whether to show discount codes or now.
		if ( $wpdb->get_var( "SELECT id FROM $wpdb->pmpro_discount_codes LIMIT 1" ) ) {
			$pmpro_discount_codes = true;
		} else {
			$pmpro_discount_codes = false;
		}
		?>
			<br class="clear" />
		</div> <!-- end tablenav -->

		<table class="wp-list-table widefat striped">
			<thead>
			<tr class="thead">
				<th class="column-code"><?php esc_html_e( 'Code', 'paid-memberships-pro' ); ?></th>
				<th class="column-username"><?php esc_html_e( 'User', 'paid-memberships-pro' ); ?></th>
				<?php do_action( 'pmpro_orders_extra_cols_header', $order_ids ); ?>
				<th class="column-level"><?php esc_html_e( 'Level', 'paid-memberships-pro' ); ?></th>
				<th class="column-total"><?php esc_html_e( 'Total', 'paid-memberships-pro' ); ?></th>
				<th class="column-billing"><?php esc_html_e( 'Billing', 'paid-memberships-pro' ); ?></th>
				<th class="column-gateway"><?php esc_html_e( 'Gateway', 'paid-memberships-pro' ); ?></th>
				<th class="column-transaction-ids"><?php esc_html_e( 'Transaction IDs', 'paid-memberships-pro' ); ?></th>
				<th class="column-status"><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></th>
				<th class="column-date"><?php esc_html_e( 'Date', 'paid-memberships-pro' ); ?></th>
				<?php if ( ! empty( $pmpro_discount_codes ) ) { ?>
					<th class="column-discount-code"><?php esc_html_e( 'Discount Code', 'paid-memberships-pro' );?></th>
				<?php } ?>
			</tr>
			</thead>
			<tbody id="orders" class="list:order orders-list">
			<?php
			foreach ( $order_ids as $order_id ) {
				$order            = new MemberOrder();
				$order->nogateway = true;
				$order->getMemberOrderByID( $order_id );
				$order->getUser();
				?>
				<tr>
					<td class="column-code has-row-actions" data-colname="<?php esc_attr_e( 'Code', 'paid-memberships-pro' ); ?>">
						<strong><a href="admin.php?page=pmpro-orders&order=<?php echo esc_attr( $order->id ); ?>"><?php echo esc_html( $order->code ); ?></a></strong>
						<div class="row-actions">
							<?php
							$delete_text = esc_html(
								sprintf(
									// translators: %s is the Order Code.
									__( 'Deleting orders is permanent and can affect active users. Are you sure you want to delete order %s?', 'paid-memberships-pro' ),
									str_replace( "'", '', $order->code )
								)
							);

							$delete_nonce_url = wp_nonce_url(
								add_query_arg(
									[
										'page'   => 'pmpro-orders',
										'action' => 'delete_order',
										'delete' => $order->id,
									],
									admin_url( 'admin.php' )
								),
								'delete_order',
								'pmpro_orders_nonce'
							);

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
									],
									admin_url( 'admin.php' )
								),
								'refund_order',
								'pmpro_orders_nonce'
							);

							$actions = [
								'id'	 => sprintf(
									// translators: %s is the Order ID.
									__( 'ID: %s', 'paid-memberships-pro' ),
									esc_attr( $order->id )
								),
								'edit'   => sprintf(
									'<a title="%1$s" href="%2$s">%3$s</a>',
									esc_attr__( 'Edit', 'paid-memberships-pro' ),
									esc_url(
										add_query_arg(
											[
												'page'  => 'pmpro-orders',
												'order' => $order->id,
											],
											admin_url( 'admin.php' )
										)
									),
									esc_html__( 'Edit', 'paid-memberships-pro' )
								),
								'copy'   => sprintf(
									'<a title="%1$s" href="%2$s">%3$s</a>',
									esc_attr__( 'Copy', 'paid-memberships-pro' ),
									esc_url(
										add_query_arg(
											[
												'page'  => 'pmpro-orders',
												'order' => - 1,
												'copy'  => $order->id,
											],
											admin_url( 'admin.php' )
										)
									),
									esc_html__( 'Copy', 'paid-memberships-pro' )
								),
								'delete'  => sprintf(
									'<a title="%1$s" href="%2$s">%3$s</a>',
									esc_attr__( 'Delete', 'paid-memberships-pro' ),
									'javascript:pmpro_askfirst(\'' . esc_js( $delete_text ) . '\', \'' . esc_js( $delete_nonce_url ) . '\'); void(0);',
									esc_html__( 'Delete', 'paid-memberships-pro' )
								),
								'print'   => sprintf(
									'<a title="%1$s" href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>',
									esc_attr__( 'Print', 'paid-memberships-pro' ),
									esc_url(
										add_query_arg(
											[
												'action' => 'pmpro_orders_print_view',
												'order'  => $order->id,
											],
											admin_url( 'admin-ajax.php' )
										)
									),
									esc_html__( 'Print', 'paid-memberships-pro' )
								),
								'email'   => sprintf(
									'<a title="%1$s" href="%2$s" data-order="%3$s" class="thickbox email_link">%4$s</a>',
									esc_attr__( 'Email', 'paid-memberships-pro' ),
									'#TB_inline?width=600&height=200&inlineId=email_invoice',
									esc_attr( $order->id ),
									esc_html__( 'Email', 'paid-memberships-pro' )
								),
							];

							if( pmpro_allowed_refunds( $order ) ) {
								$actions['refund'] = sprintf(
									'<a title="%1$s" href="%2$s">%3$s</a>',
									esc_attr__( 'Refund', 'paid-memberships-pro' ),
									esc_js( 'javascript:pmpro_askfirst(' . wp_json_encode( $refund_text ) . ', ' . wp_json_encode( $refund_nonce_url ) . '); void(0);' ),
									esc_html__( 'Refund', 'paid-memberships-pro' )
								);
							}

							/**
							 * Filter the extra actions for this user on this order.
							 *
							 * @param array       $actions The list of actions.
							 * @param object      $user    The user data.
							 * @param MemberOrder $order   The current order.
							 */
							$actions = apply_filters( 'pmpro_orders_user_row_actions', $actions, $order->user, $order );

							$actions_html = [];

							foreach ( $actions as $action => $link ) {
								$actions_html[] = sprintf(
									'<span class="%1$s">%2$s</span>',
									esc_attr( $action ),
									$link
								);
							}

							if ( ! empty( $actions_html ) ) {
								echo implode( ' | ', $actions_html );
							}
							?>
						</div>
					</td>
					<td class="column-username" data-colname="<?php esc_attr_e( 'User', 'paid-memberships-pro' ); ?>">
						<?php $order->getUser(); ?>
						<?php if ( ! empty( $order->user ) ) { ?>
							<a href="user-edit.php?user_id=<?php echo esc_attr( $order->user->ID ); ?>"><?php echo esc_html( $order->user->user_login ); ?></a><br />
							<?php echo esc_html( $order->user->user_email ); ?>
						<?php } elseif ( $order->user_id > 0 ) { ?>
							[<?php esc_html_e( 'deleted', 'paid-memberships-pro' ); ?>]
						<?php } else { ?>
							[<?php esc_html_e( 'none', 'paid-memberships-pro' ); ?>]
						<?php } ?>
					</td>
					<?php do_action( 'pmpro_orders_extra_cols_body', $order ); ?>
					<td class="column-level" data-colname="<?php esc_attr_e( 'Level', 'paid-memberships-pro' ); ?>">
						<?php
							$level = pmpro_getLevel( $order->membership_id );
							if ( ! empty( $level ) ) {
								echo esc_html( $level->name );
							} elseif ( $order->membership_id > 0 ) { ?>
								[<?php esc_html_e( 'deleted', 'paid-memberships-pro' ); ?>]
							<?php } else {
								esc_html_e( '&#8212;', 'paid-memberships-pro' );
							}
						?>
					</td>
					<td class="column-total" data-colname="<?php esc_attr_e( 'Total', 'paid-memberships-pro' ); ?>"><?php echo pmpro_escape_price( pmpro_formatPrice( $order->total ) ); ?></td>
					<td class="column-billing" data-colname="<?php esc_attr_e( 'Billing', 'paid-memberships-pro' ); ?>">
						<?php
							// Build our return variable.
							$r = '';

							if ( ! empty( $order->payment_type ) ) {
								if ( in_array( $order->payment_type, array( 'PayPal Standard', 'PayPal Express' ) ) ) {
									$r .= esc_html( 'PayPal', 'paid-memberships-pro' );
								} else {
									$r .= esc_html( ucwords( $order->payment_type ) );
								}
								$r .= '<br />';
							}

							if ( ! empty( $order->accountnumber ) ) {
								$r .= esc_html( $order->cardtype ) . ': x' . esc_html( last4( $order->accountnumber ) ) . '<br />';
							}

							if ( ! empty( $order->billing->name ) ) {
								$r .= esc_html( $order->billing->name ) . '<br />';
							}

							if ( ! empty( $order->billing->street ) ) {
								$r .= esc_html( $order->billing->street ) . '<br />';
							}

							if ( $order->billing->city && $order->billing->state ) {
								$r .= esc_html( $order->billing->city ) . ', ';
								$r .= esc_html( $order->billing->state ) . ' ';
								$r .= esc_html( $order->billing->zip ) . ' ';
								if ( ! empty( $order->billing->country ) ) {
									$r .= esc_html( $order->billing->country );
								}
							}

							if ( ! empty( $order->billing->phone ) ) {
								$r .= '<br />' . esc_html( formatPhone( $order->billing->phone ) );
							}

							// If this column is completely empty, set $r to a dash.
							if ( empty( $r ) ) {
								$r .= esc_html( '&#8212;', 'paid-memberships-pro' );
							}

							// Echo the data for this column.
							echo $r;
						?>
					</td>
					<td class="column-gateway" data-colname="<?php esc_attr_e( 'Gateway', 'paid-memberships-pro' ); ?>">
						<?php
							if ( ! empty( $order->gateway ) ) {
								if ( ! empty( $pmpro_gateways[$order->gateway] ) ) {
									echo $pmpro_gateways[$order->gateway];
								} else {
									esc_html_e( ucwords( $order->gateway ) );
								}
								if ( $order->gateway_environment == 'sandbox' ) {
									echo ' (test)';
								}
							} else {
								esc_html_e( '&#8212;', 'paid-memberships-pro' );
							}
						?>
					</td>
					<td class="column-transaction-ids" data-colname="<?php esc_attr_e( 'Transaction IDs', 'paid-memberships-pro' ); ?>">
						<?php esc_html_e( 'Payment', 'paid-memberships-pro' ); ?>:
						<?php
						if ( ! empty( $order->payment_transaction_id ) ) {
							echo esc_html( $order->payment_transaction_id );
						} else {
							esc_html_e( 'N/A', 'paid-memberships-pro' );
						}
						?>
						<br/>
						<?php esc_html_e( 'Subscription', 'paid-memberships-pro' ); ?>:
						<?php
						if ( ! empty( $order->subscription_transaction_id ) ) {
							echo esc_html( $order->subscription_transaction_id );
						} else {
							esc_html_e( 'N/A', 'paid-memberships-pro' );
						}
						?>
					</td>
					<td class="column-status" data-colname="<?php esc_attr_e( 'Status', 'paid-memberships-pro' ); ?>">
						<span class="pmpro_order-status pmpro_order-status-<?php esc_attr_e( $order->status ); ?>">
							<?php if ( in_array( $order->status, array( 'success', 'cancelled' ) ) ) {
								esc_html_e( 'Paid', 'paid-memberships-pro' );
							} else {
								esc_html_e( ucwords( $order->status ) );
							} ?>
						</span>
						<?php if ( $order->is_renewal() ) { ?>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 's' => $order->subscription_transaction_id ), admin_url( 'admin.php' ) ) ); ?>" title="<?php esc_attr_e( 'View all orders for this subscription', 'paid-memberships-pro' ); ?>" class="pmpro_order-renewal"><?php esc_html_e( 'Renewal', 'paid-memberships-pro' ); ?></a>
						<?php } ?>
					</td>
					<td class="column-date" data-colname="<?php esc_attr_e( 'Date', 'paid-memberships-pro' ); ?>">
						<?php
							 echo esc_html( sprintf(
								// translators: %1$s is the date and %2$s is the time.
								__( '%1$s at %2$s', 'paid-memberships-pro' ),
								esc_html( date_i18n( get_option( 'date_format' ), $order->getTimestamp() ) ),
								esc_html( date_i18n( get_option( 'time_format' ), $order->getTimestamp() ) )
							) ); ?>
					</td>
					<?php if ( ! empty( $pmpro_discount_codes ) ) { ?>
						<td class="column-discount-code" data-colname="<?php esc_attr_e( 'Discount Code', 'paid-memberships-pro' ); ?>">
							<?php if ( $order->getDiscountCode() ) { ?>
								<a title="<?php esc_attr_e('edit', 'paid-memberships-pro' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-discountcodes', 'edit' => $order->discount_code->id ), admin_url('admin.php' ) ) ); ?>">
									<?php echo esc_html( $order->discount_code->code ); ?>
								</a>
							<?php } else {
								esc_html_e( '&#8212;', 'paid-memberships-pro' );
							} ?>
						</td>
					<?php } ?>
				</tr>
				<?php
			}

			if ( ! $order_ids ) {
				?>
				<tr>
					<td colspan="9"><p><?php esc_html_e( 'No orders found.', 'paid-memberships-pro' ); ?></p></td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>
	</form>
	<div class="tablenav bottom">
		<div class="tablenav-pages">
			<?php if ( $order_ids ) { ?>
				<span class="displaying-num"><?php printf( __( '%d orders found.', 'paid-memberships-pro' ), $totalrows ); ?></span>
			<?php } ?>
			<?php
				// add normal args
				$pagination_url = esc_url( add_query_arg( $url_params, admin_url( '/admin.php?page=pmpro-orders' ) ) );
				echo pmpro_getPaginationString( $pn, $totalrows, $limit, 1, $pagination_url, "&limit=$limit&pn=" );
			?>
		</div>
	</div>
<?php } ?>
<?php
require_once( dirname( __FILE__ ) . '/admin_footer.php' );
