<?php
// only admins can get this
if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_orders' ) ) ) {
	die( __( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
}

// vars
global $wpdb;
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

if ( isset( $_REQUEST['discount_code'] ) ) {
	$discount_code = intval( $_REQUEST['discount_code'] );
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
	$start_year = date_i18n( 'Y' );
}

if ( isset( $_REQUEST['end-month'] ) ) {
	$end_month = intval( $_REQUEST['end-month'] );
} else {
	$end_month = date_i18n( 'n' );
}

if ( isset( $_REQUEST['end-day'] ) ) {
	$end_day = intval( $_REQUEST['end-day'] );
} else {
	$end_day = date_i18n( 'j' );
}

if ( isset( $_REQUEST['end-year'] ) ) {
	$end_year = intval( $_REQUEST['end-year'] );
} else {
	$end_year = date_i18n( 'Y' );
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
		$start_date = date_i18n( 'Y-m-d', strtotime( 'first day of last month', current_time( 'timestamp' ) ) );
		$end_date   = date_i18n( 'Y-m-d', strtotime( 'last day of last month', current_time( 'timestamp' ) ) );
	} elseif ( $predefined_date == 'This Month' ) {
		$start_date = date_i18n( 'Y-m-d', strtotime( 'first day of this month', current_time( 'timestamp' ) ) );
		$end_date   = date_i18n( 'Y-m-d', strtotime( 'last day of this month', current_time( 'timestamp' ) ) );
	} elseif ( $predefined_date == 'This Year' ) {
		$year       = date_i18n( 'Y' );
		$start_date = date_i18n( 'Y-m-d', strtotime( "first day of January $year", current_time( 'timestamp' ) ) );
		$end_date   = date_i18n( 'Y-m-d', strtotime( "last day of December $year", current_time( 'timestamp' ) ) );
	} elseif ( $predefined_date == 'Last Year' ) {
		$year       = date_i18n( 'Y' ) - 1;
		$start_date = date_i18n( 'Y-m-d', strtotime( "first day of January $year", current_time( 'timestamp' ) ) );
		$end_date   = date_i18n( 'Y-m-d', strtotime( "last day of December $year", current_time( 'timestamp' ) ) );
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

// deleting?
if ( ! empty( $_REQUEST['delete'] ) ) {
	$dorder = new MemberOrder( intval( $_REQUEST['delete'] ) );
	if ( $dorder->deleteMe() ) {
		$pmpro_msg  = __( 'Order deleted successfully.', 'paid-memberships-pro' );
		$pmpro_msgt = 'success';
	} else {
		$pmpro_msg  = __( 'Error deleting order.', 'paid-memberships-pro' );
		$pmpro_msgt = 'error';
	}
}

$thisyear = date_i18n( 'Y' );

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
	if ( ! in_array( 'couponamount', $read_only_fields ) && isset( $_POST['couponamount'] ) ) {
		$order->couponamount = sanitize_text_field( $_POST['couponamount'] );
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
	if ( $order->saveOrder() !== false && $nonceokay ) {
		// also update the discount code if needed
		if( isset( $_REQUEST['discount_code_id'] ) ) {
			$order->updateDiscountCode( intval( $_REQUEST['discount_code_id'] ) );
		}

		// handle timestamp
		if ( $order->updateTimestamp( intval( $_POST['ts_year'] ), intval( $_POST['ts_month'] ), intval( $_POST['ts_day'] ) ) !== false ) {
			$pmpro_msg  = __( 'Order saved successfully.', 'paid-memberships-pro' );
			$pmpro_msgt = 'success';
		} else {
			$pmpro_msg  = __( 'Error updating order timestamp.', 'paid-memberships-pro' );
			$pmpro_msgt = 'error';
		}
	} else {
		$pmpro_msg  = __( 'Error saving order.', 'paid-memberships-pro' );
		$pmpro_msgt = 'error';
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

	<?php if ( ! empty( $order->id ) ) { ?>
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Order', 'paid-memberships-pro' ); ?> #<?php echo $order->id; ?>: <?php echo $order->code; ?></h1>
		<a title="<?php _e( 'Print', 'paid-memberships-pro' ); ?>" href="<?php echo add_query_arg( array( 'action' => 'pmpro_orders_print_view', 'order' => $order->id ), admin_url('admin-ajax.php' ) ); ?>" class="page-title-action" target="_blank" ><?php _e( 'Print', 'paid-memberships-pro' ); ?></a>
		<a title="<?php _e( 'Email', 'paid-memberships-pro' ); ?>" href="#TB_inline?width=600&height=200&inlineId=email_invoice" class="thickbox email_link page-title-action" data-order="<?php echo $order->id; ?>"><?php _e( 'Email', 'paid-memberships-pro' ); ?></a>
	<?php } else { ?>
		<h1 class="wp-heading-inline"><?php esc_html_e( 'New Order', 'paid-memberships-pro' ); ?></h1>
	<?php } ?>
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

	<form method="post" action="">
		<?php wp_nonce_field( 'save', 'pmpro_orders_nonce' ); ?>

		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row" valign="top"><label>ID:</label></th>
				<td>
				<?php
				if ( ! empty( $order->id ) ) {
						echo $order->id;
				} else {
					echo '<p class="description">' . __( 'This will be generated when you save.', 'paid-memberships-pro' ) . '</p>';
				}
					?>
					</td>
			</tr>

			<tr>
				<th scope="row" valign="top"><label for="code"><?php _e( 'Code', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
						if ( in_array( 'code', $read_only_fields ) ) {
							echo $order->code;
						} else { ?>
							<input id="code" name="code" type="text" value="<?php echo esc_attr( $order->code ); ?>" class="regular-text" />
						<?php 
						}
					?>
					<?php if ( $order_id < 0 ) { ?>
						<p class="description"><?php _e( 'Randomly generated for you.', 'paid-memberships-pro' ); ?></p>
					<?php } ?>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="user_id"><?php _e( 'User ID', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
						if ( in_array( 'user_id', $read_only_fields ) && $order_id > 0 ) {
							echo $order->user_id;
						} else { ?>
							<input id="user_id" name="user_id" type="text" value="<?php echo esc_attr( $order->user_id ); ?>" class="regular-text" />
						<?php 
						}
					?>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="membership_id"><?php _e( 'Membership Level ID', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
						if ( in_array( 'membership_id', $read_only_fields ) && $order_id > 0 ) {
						echo $order->membership_id;
						} else { ?>
							<input id="membership_id" name="membership_id" type="text" value="<?php echo esc_attr( $order->membership_id ); ?>" class="regular-text" />
						<?php 
						}
					?>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="billing_name"><?php _e( 'Billing Name', 'paid-memberships-pro' ); ?>:</label>
				</th>
				<td>
					<?php
					if ( in_array( 'billing_name', $read_only_fields ) && $order_id > 0 ) {
						echo $order->billing_name;
					} else {
										?>
											<input id="billing_name" name="billing_name" type="text" size="50"
												   value="<?php echo esc_attr( $order->billing->name ); ?>"/>
					<?php } ?>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="billing_street"><?php _e( 'Billing Street', 'paid-memberships-pro' ); ?>
						:</label></th>
				<td>
					<?php
					if ( in_array( 'billing_street', $read_only_fields ) && $order_id > 0 ) {
						echo $order->billing_street;
					} else {
										?>
										<input id="billing_street" name="billing_street" type="text" size="50"
											   value="<?php echo esc_attr( $order->billing->street ); ?>"/></td>
									<?php } ?>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="billing_city"><?php _e( 'Billing City', 'paid-memberships-pro' ); ?>:</label>
				</th>
				<td>
					<?php
					if ( in_array( 'billing_city', $read_only_fields ) && $order_id > 0 ) {
						echo $order->billing_city;
					} else {
										?>
										<input id="billing_city" name="billing_city" type="text" size="50"
											   value="<?php echo esc_attr( $order->billing->city ); ?>"/></td>
									<?php } ?>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="billing_state"><?php _e( 'Billing State', 'paid-memberships-pro' ); ?>
						:</label></th>
				<td>
					<?php
					if ( in_array( 'billing_state', $read_only_fields ) && $order_id > 0 ) {
						echo $order->billing_state;
					} else {
										?>
										<input id="billing_state" name="billing_state" type="text" size="50"
											   value="<?php echo esc_attr( $order->billing->state ); ?>"/></td>
									<?php } ?>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="billing_zip"><?php _e( 'Billing Postal Code', 'paid-memberships-pro' ); ?>
						:</label></th>
				<td>
					<?php
					if ( in_array( 'billing_zip', $read_only_fields ) && $order_id > 0 ) {
						echo $order->billing_zip;
					} else {
										?>
										<input id="billing_zip" name="billing_zip" type="text" size="50"
											   value="<?php echo esc_attr( $order->billing->zip ); ?>"/></td>
									<?php } ?>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="billing_country"><?php _e( 'Billing Country', 'paid-memberships-pro' ); ?>
						:</label></th>
				<td>
					<?php
					if ( in_array( 'billing_country', $read_only_fields ) && $order_id > 0 ) {
						echo $order->billing_country;
					} else {
										?>
											<input id="billing_country" name="billing_country" type="text" size="50"
												   value="<?php echo esc_attr( $order->billing->country ); ?>"/>
					<?php } ?>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="billing_phone"><?php _e( 'Billing Phone', 'paid-memberships-pro' ); ?>
						:</label></th>
				<td>
					<?php
					if ( in_array( 'billing_phone', $read_only_fields ) && $order_id > 0 ) {
						echo $order->billing_phone;
					} else {
										?>
											<input id="billing_phone" name="billing_phone" type="text" size="50"
												   value="<?php echo esc_attr( $order->billing->phone ); ?>"/>
					<?php } ?>
				</td>
			</tr>
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
				<th scope="row" valign="top"><label for="discount_code_id"><?php _e( 'Discount Code', 'paid-memberships-pro' ); ?>:</label></th>
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
				<th scope="row" valign="top"><label for="subtotal"><?php _e( 'Sub Total', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'subtotal', $read_only_fields ) && $order_id > 0 ) {
						echo $order->subtotal;
					} else {
										?>
											<input id="subtotal" name="subtotal" type="text" size="10"
												   value="<?php echo esc_attr( $order->subtotal ); ?>"/>
					<?php } ?>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="tax"><?php _e( 'Tax', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'tax', $read_only_fields ) && $order_id > 0 ) {
						echo $order->tax;
					} else {
										?>
											<input id="tax" name="tax" type="text" size="10"
												   value="<?php echo esc_attr( $order->tax ); ?>"/>
					<?php } ?>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="couponamount"><?php _e( 'Coupon Amount', 'paid-memberships-pro' ); ?>:</label>
				</th>
				<td>
					<?php
					if ( in_array( 'couponamount', $read_only_fields ) && $order_id > 0 ) {
						echo $order->couponamount;
					} else {
										?>
											<input id="couponamount" name="couponamount" type="text" size="10"
												   value="<?php echo esc_attr( $order->couponamount ); ?>"/>
					<?php } ?>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="total"><?php _e( 'Total', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'total', $read_only_fields ) && $order_id > 0 ) {
						echo $order->total;
					} else {
										?>
											<input id="total" name="total" type="text" size="10"
												   value="<?php echo esc_attr( $order->total ); ?>"/>
					<?php } ?>
					<p class="description"><?php _e( 'Should be subtotal + tax - couponamount.', 'paid-memberships-pro' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row" valign="top"><label for="payment_type"><?php _e( 'Payment Type', 'paid-memberships-pro' ); ?>:</label>
				</th>
				<td>
					<?php
					if ( in_array( 'payment_type', $read_only_fields ) && $order_id > 0 ) {
						echo $order->payment_type;
					} else {
										?>
											<input id="payment_type" name="payment_type" type="text" size="50"
												   value="<?php echo esc_attr( $order->payment_type ); ?>"/>
					<?php } ?>
					<p class="description"><?php _e( 'e.g. PayPal Express, PayPal Standard, Credit Card.', 'paid-memberships-pro' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="cardtype"><?php _e( 'Card Type', 'paid-memberships-pro' ); ?></label></th>
				<td>
					<?php
					if ( in_array( 'cardtype', $read_only_fields ) && $order_id > 0 ) {
						echo $order->cardtype;
					} else {
										?>
											<input id="cardtype" name="cardtype" type="text" size="50"
												   value="<?php echo esc_attr( $order->cardtype ); ?>"/>
					<?php } ?>
					<p class="description"><?php _e( 'e.g. Visa, MasterCard, AMEX, etc', 'paid-memberships-pro' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="accountnumber"><?php _e( 'Account Number', 'paid-memberships-pro' ); ?>
						:</label></th>
				<td>
					<?php
					if ( in_array( 'accountnumber', $read_only_fields ) && $order_id > 0 ) {
						echo $order->accountnumber;
					} else {
										?>
											<input id="accountnumber" name="accountnumber" type="text" size="50"
												   value="<?php echo esc_attr( $order->accountnumber ); ?>"/>
					<?php } ?>
					<p class="description"><?php _e( 'Obscure all but last 4 digits.', 'paid-memberships-pro' ); ?></p>
				</td>
			</tr>
			<?php
			if ( in_array( 'ExpirationDate', $read_only_fields ) && $order_id > 0 ) {
				echo $order->ExpirationDate;
			} else {
						?>
							<tr>
								<th scope="row" valign="top"><label
							for="expirationmonth"><?php _e( 'Expiration Month', 'paid-memberships-pro' ); ?>:</label></th>
					<td>
						<input id="expirationmonth" name="expirationmonth" type="text" size="10"
				   value="<?php echo esc_attr( $order->expirationmonth ); ?>"/>
						<span class="description">MM</span>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="expirationyear"><?php _e( 'Expiration Year', 'paid-memberships-pro' ); ?>
				:</label></th>
					<td>
						<input id="expirationyear" name="expirationyear" type="text" size="10"
				   value="<?php echo esc_attr( $order->expirationyear ); ?>"/>
						<span class="description">YYYY</span>
					</td>
				</tr>
			<?php } ?>
			<tr>
				<th scope="row" valign="top"><label for="status"><?php _e( 'Status', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'status', $read_only_fields ) && $order_id > 0 ) {
						echo $order->status;
					} else { ?>
					<?php
						$statuses = pmpro_getOrderStatuses();
						?>
						<select id="status" name="status">
							<?php foreach ( $statuses as $status ) { ?>
								<option
									value="<?php echo esc_attr( $status ); ?>" <?php selected( $order->status, $status ); ?>><?php echo $status; ?></option>
							<?php } ?>
						</select>
						<?php 
						} 
					?>
				</td>
			</tr>

			<tr>
				<th scope="row" valign="top"><label for="gateway"><?php _e( 'Gateway', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'gateway', $read_only_fields ) && $order_id > 0 ) {
						echo $order->gateway;
					} else {
										?>
											<select id="gateway" name="gateway" onchange="pmpro_changeGateway(jQuery(this).val());">
												<?php
												$pmpro_gateways = pmpro_gateways();
												foreach ( $pmpro_gateways as $pmpro_gateway_name => $pmpro_gateway_label ) {
													?>
													<option
														value="<?php echo esc_attr( $pmpro_gateway_name ); ?>" <?php selected( $order->gateway, $pmpro_gateway_name ); ?>><?php echo $pmpro_gateway_label; ?></option>
								<?php
												}
												?>
											</select>
										<?php } ?>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label
						for="gateway_environment"><?php _e( 'Gateway Environment', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'gateway_environment', $read_only_fields ) && $order_id > 0 ) {
						echo $order->gateway_environment;
					} else {
										?>
											<select name="gateway_environment">
												<option value="sandbox"
									<?php
									if ( $order->gateway_environment == 'sandbox' ) {
					?>
					selected="selected"<?php } ?>><?php _e( 'Sandbox/Testing', 'paid-memberships-pro' ); ?></option>
							<option value="live"
				<?php
				if ( $order->gateway_environment == 'live' ) {
?>
selected="selected"<?php } ?>><?php _e( 'Live/Production', 'paid-memberships-pro' ); ?></option>
						</select>
					<?php } ?>
				</td>
			</tr>

			<tr>
				<th scope="row" valign="top"><label
						for="payment_transaction_id"><?php _e( 'Payment Transaction ID', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'payment_transaction_id', $read_only_fields ) && $order_id > 0 ) {
						echo $order->payment_transaction_id;
					} else {
										?>
											<input id="payment_transaction_id" name="payment_transaction_id" type="text" size="50"
												   value="<?php echo esc_attr( $order->payment_transaction_id ); ?>"/>
					<?php } ?>
					<p class="description"><?php _e( 'Generated by the gateway. Useful to cross reference orders.', 'paid-memberships-pro' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label
						for="subscription_transaction_id"><?php _e( 'Subscription Transaction ID', 'paid-memberships-pro' ); ?>
						:</label></th>
				<td>
					<?php
					if ( in_array( 'subscription_transaction_id', $read_only_fields ) && $order_id > 0 ) {
						echo $order->subscription_transaction_id;
					} else {
										?>
											<input id="subscription_transaction_id" name="subscription_transaction_id" type="text" size="50"
												   value="<?php echo esc_attr( $order->subscription_transaction_id ); ?>"/>
					<?php } ?>
					<p class="description"><?php _e( 'Generated by the gateway. Useful to cross reference subscriptions.', 'paid-memberships-pro' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row" valign="top"><label for="ts_month"><?php _e( 'Date', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'timestamp', $read_only_fields ) && $order_id > 0 ) {
						echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $order->timestamp );
					} else {
										?>
											<?php
											// set up date vars
											if ( ! empty( $order->timestamp ) ) {
												$timestamp = $order->timestamp;
											} else {
												$timestamp = current_time( 'timestamp' );
											}
											$year  = date_i18n( 'Y', $timestamp );
											$month = date_i18n( 'n', $timestamp );
											$day   = date_i18n( 'j', $timestamp );
											?>
											<select id="ts_month" name="ts_month">
							<?php
							for ( $i = 1; $i < 13; $i ++ ) {
								?>
								<option value="<?php echo $i; ?>"
					<?php
					if ( $i == $month ) {
?>
selected="selected"<?php } ?>><?php echo date_i18n( 'M', strtotime( $i . '/15/' . $year, current_time( 'timestamp' ) ) ); ?></option>
								<?php
							}
							?>
						</select>
						<input name="ts_day" type="text" size="2" value="<?php echo esc_attr( $day ); ?>"/>
						<input name="ts_year" type="text" size="4" value="<?php echo esc_attr( $year ); ?>"/>
					<?php } ?>
				</td>
			</tr>

			<?php
			$affiliates = apply_filters( 'pmpro_orders_show_affiliate_ids', false );
			if ( ! empty( $affiliates ) ) {
				?>
				<tr>
					<th scope="row" valign="top"><label for="affiliate_id"><?php _e( 'Affiliate ID', 'paid-memberships-pro' ); ?>
							:</label></th>
					<td>
						<?php
						if ( in_array( 'affiliate_id', $read_only_fields ) && $order_id > 0 ) {
							echo $order->affiliate_id;
						} else {
												?>
													<input id="affiliate_id" name="affiliate_id" type="text" size="50"
														   value="<?php echo esc_attr( $order->affiliate_id ); ?>"/>
						<?php } ?>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="affiliate_subid"><?php _e( 'Affiliate SubID', 'paid-memberships-pro' ); ?>
							:</label></th>
					<td>
						<?php
						if ( in_array( 'affiliate_subid', $read_only_fields ) && $order_id > 0 ) {
							echo $order->affiliate_subid;
						} else {
												?>
													<input id="affiliate_subid" name="affiliate_subid" type="text" size="50"
														   value="<?php echo esc_attr( $order->affiliate_subid ); ?>"/>
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
					<th scope="row" valign="top"><label for="tos_consent"><?php _e( 'TOS Consent', 'paid-memberships-pro' ); ?>:</label></th>
					<td id="tos_consent">
						<?php
							
							if( !empty( $consent_entry ) ) {
								echo pmpro_consent_to_text( $consent_entry );
							} else {
								echo __( 'N/A' );
							}
						?>
					</td>
				</tr>
				<?php
				}
			?>

			<tr>
				<th scope="row" valign="top"><label for="notes"><?php _e( 'Notes', 'paid-memberships-pro' ); ?>:</label></th>
				<td>
					<?php
					if ( in_array( 'notes', $read_only_fields ) && $order_id > 0 ) {
						echo $order->notes;
					} else {
										?>
											<textarea id="notes" name="notes" rows="5"
								  cols="80"><?php echo esc_textarea( $order->notes ); ?></textarea>
					<?php } ?>
				</td>
			</tr>

			<?php do_action( 'pmpro_after_order_settings', $order ); ?>

			</tbody>
		</table>

		<p class="submit topborder">
			<input name="order" type="hidden" value="
			<?php
			if ( ! empty( $order->id ) ) {
				echo $order->id;
			} else {
				echo $order_id;
			}
			?>
			"/>
			<input name="save" type="submit" class="button-primary" value="<?php _e( 'Save Order', 'paid-memberships-pro' ); ?>"/>
			<input name="cancel" type="button" class="cancel button-secondary" value="<?php _e( 'Cancel', 'paid-memberships-pro' ); ?>"
				   onclick="location.href='<?php echo get_admin_url( null, '/admin.php?page=pmpro-orders' ); ?>';"/>
		</p>

	</form>

<?php } else { ?>

	<form id="posts-filter" method="get" action="">

		<h1 class="wp-heading-inline"><?php esc_html_e( 'Orders', 'paid-memberships-pro' ); ?></h1>
		<a href="<?php echo add_query_arg( array( 'page' => 'pmpro-orders', 'order' => -1 ), get_admin_url(null, 'admin.php' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Order', 'paid-memberships-pro' ); ?></a>

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
		<a target="_blank" href="<?php echo $export_url; ?>" class="page-title-action"><?php _e( 'Export to CSV', 'paid-memberships-pro' ); ?></a>
		
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


		<ul class="subsubsub">
			<li>
				<?php _e( 'Show', 'paid-memberships-pro' ); ?>
				<select id="filter" name="filter">
					<option value="all" <?php selected( $filter, 'all' ); ?>><?php _e( 'All', 'paid-memberships-pro' ); ?></option>
					<option
						value="within-a-date-range" <?php selected( $filter, 'within-a-date-range' ); ?>><?php _e( 'Within a Date Range', 'paid-memberships-pro' ); ?></option>
					<option
						value="predefined-date-range" <?php selected( $filter, 'predefined-date-range' ); ?>><?php _e( 'Predefined Date Range', 'paid-memberships-pro' ); ?></option>
					<option
						value="within-a-level" <?php selected( $filter, 'within-a-level' ); ?>><?php _e( 'Within a Level', 'paid-memberships-pro' ); ?></option>
					<option
						value="with-discount-code" <?php selected( $filter, 'with-discount-code' ); ?>><?php _e( 'With a Discount Code', 'paid-memberships-pro' ); ?></option>
					<option
						value="within-a-status" <?php selected( $filter, 'within-a-status' ); ?>><?php _e( 'Within a Status', 'paid-memberships-pro' ); ?></option>
					<option 
						value="only-paid" <?php selected( $filter, 'only-paid' ); ?>><?php _e( 'Only Paid Orders', 'paid-memberships-pro' ); ?></option>
					<option 
						value="only-free" <?php selected( $filter, 'only-free' ); ?>><?php _e( 'Only Free Orders', 'paid-memberships-pro' ); ?></option>
				</select>

				<span id="from"><?php _e( 'From', 'paid-memberships-pro' ); ?></span>

				<select id="start-month" name="start-month">
					<?php for ( $i = 1; $i < 13; $i ++ ) { ?>
						<option
							value="<?php echo $i; ?>" <?php selected( $start_month, $i ); ?>><?php echo date_i18n( 'F', mktime( 0, 0, 0, $i, 2 ) ); ?></option>
					<?php } ?>
				</select>

				<input id='start-day' name="start-day" type="text" size="2"
					   value="<?php echo esc_attr( $start_day ); ?>"/>
				<input id='start-year' name="start-year" type="text" size="4"
					   value="<?php echo esc_attr( $start_year ); ?>"/>


				<span id="to"><?php _e( 'To', 'paid-memberships-pro' ); ?></span>

				<select id="end-month" name="end-month">
					<?php for ( $i = 1; $i < 13; $i ++ ) { ?>
						<option
							value="<?php echo $i; ?>" <?php selected( $end_month, $i ); ?>><?php echo date_i18n( 'F', mktime( 0, 0, 0, $i, 2 ) ); ?></option>
					<?php } ?>
				</select>


				<input id='end-day' name="end-day" type="text" size="2" value="<?php echo esc_attr( $end_day ); ?>"/>
				<input id='end-year' name="end-year" type="text" size="4" value="<?php echo esc_attr( $end_year ); ?>"/>

				<span id="filterby"><?php _e( 'filter by ', 'paid-memberships-pro' ); ?></span>

				<select id="predefined-date" name="predefined-date">

					<option
						value="<?php echo 'This Month'; ?>" <?php selected( $predefined_date, 'This Month' ); ?>><?php echo 'This Month'; ?></option>
					<option
						value="<?php echo 'Last Month'; ?>" <?php selected( $predefined_date, 'Last Month' ); ?>><?php echo 'Last Month'; ?></option>
					<option
						value="<?php echo 'This Year'; ?>" <?php selected( $predefined_date, 'This Year' ); ?>><?php echo 'This Year'; ?></option>
					<option
						value="<?php echo 'Last Year'; ?>" <?php selected( $predefined_date, 'Last Year' ); ?>><?php echo 'Last Year'; ?></option>

				</select>

				<?php
				// Note: only orders belonging to current levels can be filtered. There is no option for orders belonging to deleted levels
				$levels = pmpro_getAllLevels( true, true );
				?>
				<select id="l" name="l">
					<?php foreach ( $levels as $level ) { ?>
						<option
							value="<?php echo $level->id; ?>" <?php selected( $l, $level->id ); ?>><?php echo $level->name; ?></option>
					<?php } ?>

				</select>
				
				<?php
				$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM $wpdb->pmpro_discount_codes ";
				$sqlQuery .= "ORDER BY id DESC ";
				$codes = $wpdb->get_results($sqlQuery, OBJECT);
				if ( ! empty( $codes ) ) { ?>
				<select id="discount_code" name="discount_code">
					<?php foreach ( $codes as $code ) { ?>
						<option
							value="<?php echo $code->id; ?>" <?php selected( $discount_code, $code->id ); ?>><?php echo $code->code; ?></option>
					<?php } ?>
				</select>
				<?php } ?>

				<?php
					$statuses = pmpro_getOrderStatuses();
				?>
				<select id="status" name="status">
					<?php foreach ( $statuses as $the_status ) { ?>
						<option
							value="<?php echo esc_attr( $the_status ); ?>" <?php selected( $the_status, $status ); ?>><?php echo $the_status; ?></option>
					<?php } ?>
				</select>

				<input id="submit" class="button" type="submit" value="<?php _e( 'Filter', 'paid-memberships-pro' ); ?>"/>
			</li>
		</ul>

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
					jQuery('#discount_code').hide();
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
					jQuery('#discount_code').hide();
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
					jQuery('#discount_code').hide();
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
					jQuery('#discount_code').hide();
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
					jQuery('#discount_code').show();
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
					jQuery('#discount_code').hide();
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
					jQuery('#discount_code').hide();
					jQuery('#submit').show();
					jQuery('#from').hide();
					jQuery('#to').hide();
					jQuery('#filterby').hide();
				}
			}

			pmpro_ShowMonthOrYear();


		</script>

		<p class="search-box">
			<label class="hidden" for="post-search-input"><?php _e( 'Search Orders', 'paid-memberships-pro' ); ?>:</label>
			<input type="hidden" name="page" value="pmpro-orders"/>
			<input id="post-search-input" type="text" value="<?php echo esc_attr( $s ); ?>" name="s"/>
			<input class="button" type="submit" value="<?php _e( 'Search Orders', 'paid-memberships-pro' ); ?>"/>
		</p>

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
				$sqlQuery .= ' OR ' . $field . " LIKE '%" . esc_sql( $s ) . "%' ";
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
			<p class="clear"><?php printf( __( '%d orders found.', 'paid-memberships-pro' ), $totalrows ); ?></span></p>
			<?php
		}
		?>
		<table class="widefat">
			<thead>
			<tr class="thead">
				<th><?php _e( 'ID', 'paid-memberships-pro' ); ?></th>
				<th><?php _e( 'Code', 'paid-memberships-pro' ); ?></th>
				<th><?php _e( 'Username', 'paid-memberships-pro' ); ?></th>
				<?php do_action( 'pmpro_orders_extra_cols_header', $order_ids ); ?>
				<th><?php _e( 'Level', 'paid-memberships-pro' ); ?></th>
				<th><?php _e( 'Total', 'paid-memberships-pro' ); ?></th>
				<th><?php _e( 'Payment', 'paid-memberships-pro' ); ?></th>
				<th><?php _e( 'Gateway', 'paid-memberships-pro' ); ?></th>
				<th><?php _e( 'Transaction IDs', 'paid-memberships-pro' ); ?></th>
				<th><?php _e( 'Status', 'paid-memberships-pro' ); ?></th>
				<th><?php _e( 'Date', 'paid-memberships-pro' ); ?></th>
				<th><?php _e( 'Discount Code', 'paid-memberships-pro' );?></th>
			</tr>
			</thead>
			<tbody id="orders" class="list:order orders-list">
			<?php
			$count = 0;
			foreach ( $order_ids as $order_id ) {
				$order            = new MemberOrder();
				$order->nogateway = true;
				$order->getMemberOrderByID( $order_id );
				$order->getUser();
				?>
				<tr 
				<?php
				if ( $count ++ % 2 == 0 ) {
?>
class="alternate"<?php } ?>>
					<td>
						<a href="admin.php?page=pmpro-orders&order=<?php echo $order->id; ?>"><?php echo $order->id; ?></a>
					</td>
					<td class="order_code column-order_code has-row-actions">
						<a href="admin.php?page=pmpro-orders&order=<?php echo $order->id; ?>"><?php echo $order->code; ?></a>
						<br />
						<div class="row-actions">
							<span class="edit">
								<a title="<?php _e( 'Edit', 'paid-memberships-pro' ); ?>" href="<?php echo add_query_arg( array( 'page' => 'pmpro-orders', 'order' => $order->id ), admin_url('admin.php' ) ); ?>"><?php _e( 'Edit', 'paid-memberships-pro' ); ?></a>
							</span> |
							<span class="copy">
								<a title="<?php _e( 'Copy', 'paid-memberships-pro' ); ?>" href="<?php echo add_query_arg( array( 'page' => 'pmpro-orders', 'order' => '-1', 'copy' => $order->id ), admin_url('admin.php' ) ); ?>"><?php _e( 'Copy', 'paid-memberships-pro' ); ?></a>
							</span> |
							<span class="delete">
								<a href="javascript:pmpro_askfirst('<?php echo str_replace( "'", "\'", sprintf( __( 'Deleting orders is permanent and can affect active users. Are you sure you want to delete order %s?', 'paid-memberships-pro' ), str_replace( "'", '', $order->code ) ) ); ?>', 'admin.php?page=pmpro-orders&delete=<?php echo $order->id; ?>'); void(0);"><?php _e( 'Delete', 'paid-memberships-pro' ); ?></a>
							</span> |
							<span class="print">
								<a target="_blank" title="<?php _e( 'Print', 'paid-memberships-pro' ); ?>" href="<?php echo add_query_arg( array( 'action' => 'pmpro_orders_print_view', 'order' => $order->id ), admin_url('admin-ajax.php' ) ); ?>"><?php _e( 'Print', 'paid-memberships-pro' ); ?></a>
							</span> |
							<span class="email">
								<a href="#TB_inline?width=600&height=200&inlineId=email_invoice" class="thickbox email_link"
								   data-order="<?php echo $order->id; ?>"><?php _e( 'Email', 'paid-memberships-pro' ); ?></a>
							</span>
							<?php
							// Set up the hover actions for this user
							$actions      = apply_filters( 'pmpro_orders_user_row_actions', array(), $order->user, $order );
							$action_count = count( $actions );
							$i            = 0;
							if ( $action_count ) {
								$out = ' | ';
								foreach ( $actions as $action => $link ) {
									++ $i;
									( $i == $action_count ) ? $sep = '' : $sep = ' | ';
									$out .= "<span class='$action'>$link$sep</span>";
								}
								echo $out;
							}
							?>
						</div>
					</td>
					<td class="username column-username">
						<?php $order->getUser(); ?>
						<?php if ( ! empty( $order->user ) ) { ?>
							<a href="user-edit.php?user_id=<?php echo $order->user->ID; ?>"><?php echo $order->user->user_login; ?></a>
						<?php } elseif ( $order->user_id > 0 ) { ?>
							[<?php _e( 'deleted', 'paid-memberships-pro' ); ?>]
						<?php } else { ?>
							[<?php _e( 'none', 'paid-memberships-pro' ); ?>]
						<?php } ?>	
					</td>
					<?php do_action( 'pmpro_orders_extra_cols_body', $order ); ?>
					<td>
						<?php
							$level = pmpro_getLevel( $order->membership_id );
							echo $level->name;
						?>
					</td>
					<td><?php echo pmpro_formatPrice( $order->total ); ?></td>
					<td>
						<?php
						if ( ! empty( $order->payment_type ) ) {
							echo $order->payment_type . '<br />';
						}
						?>
						<?php if ( ! empty( $order->accountnumber ) ) { ?>
							<?php echo $order->cardtype; ?>: x<?php echo last4( $order->accountnumber ); ?><br/>
						<?php } ?>
						<?php if ( ! empty( $order->billing->name ) ) { ?>
								<?php echo $order->billing->name; ?><br/>
						<?php } ?>
						<?php if ( ! empty( $order->billing->street ) ) { ?>
							<?php echo $order->billing->street; ?><br/>
							<?php if ( $order->billing->city && $order->billing->state ) { ?>
								<?php echo $order->billing->city; ?>, <?php echo $order->billing->state; ?><?php echo $order->billing->zip; ?>
											<?php
											if ( ! empty( $order->billing->country ) ) {
												echo $order->billing->country; }
									?>
									<br/>
							<?php } ?>
						<?php } ?>
						<?php
						if ( ! empty( $order->billing->phone ) ) {
							echo formatPhone( $order->billing->phone );
						}
						?>
					</td>
					<td><?php echo $order->gateway; ?>
									<?php
									if ( $order->gateway_environment == 'test' ) {
											echo '(test)';
									}
						?>
						</td>
					<td>
						<?php _e( 'Payment', 'paid-memberships-pro' ); ?>: 
									<?php
									if ( ! empty( $order->payment_transaction_id ) ) {
										echo $order->payment_transaction_id;
									} else {
										_e( 'N/A', 'paid-memberships-pro' );
									}
						?>
						<br/>
						<?php _e( 'Subscription', 'paid-memberships-pro' ); ?>
						: 
						<?php
						if ( ! empty( $order->subscription_transaction_id ) ) {
							echo $order->subscription_transaction_id;
						} else {
							_e( 'N/A', 'paid-memberships-pro' );
						}
						?>
					</td>
					<td><?php echo $order->status; ?></td>
					<td>
						<?php echo date_i18n( get_option( 'date_format' ), $order->timestamp ); ?><br/>
						<?php echo date_i18n( get_option( 'time_format' ), $order->timestamp ); ?>
					</td>
					<td>
						<?php if ( $order->getDiscountCode() ) { ?>
							<a title="<?php _e('edit', 'paid-memberships-pro' ); ?>" href="<?php echo add_query_arg( array( 'page' => 'pmpro-discountcodes', 'edit' => $order->discount_code->id ), admin_url('admin.php' ) ); ?>">
								<?php echo $order->discount_code->code; ?>
							</a>
						<?php } ?>							
					</td>
				</tr>
				<?php
			}

			if ( ! $order_ids ) {
				?>
				<tr>
					<td colspan="9"><p><?php _e( 'No orders found.', 'paid-memberships-pro' ); ?></p></td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>
	</form>
	<?php
	// add normal args
	$pagination_url = add_query_arg( $url_params, get_admin_url( null, '/admin.php?page=pmpro-orders' ) );
	echo pmpro_getPaginationString( $pn, $totalrows, $limit, 1, $pagination_url, "&limit=$limit&pn=" );
	?>

<?php } ?>
<?php
require_once( dirname( __FILE__ ) . '/admin_footer.php' );
