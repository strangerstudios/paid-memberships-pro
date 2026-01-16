<?php
global $wpdb, $pmpro_msg, $pmpro_msgt;

// only admins can get this
if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_orders' ) ) ) {
	die( esc_html__( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
}

// Process form submissions.
$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : false;
if ( ! empty( $action ) && ( empty( sanitize_key( $_REQUEST['pmpro_orders_nonce'] ) ) || ! check_admin_referer( $action, 'pmpro_orders_nonce' ) ) ) {
	$page_msg = -1;
	$page_msgt = __( 'Are you sure you want to do that? Try again.', 'paid-memberships-pro' );
	$action = false;
} else {
	$nonceokay = true;
}

if ( $nonceokay ) {
	switch ( $action ) {
		case 'save_order':
			include_once PMPRO_DIR . '/adminpages/orders/save-order.php';
			break;

		case 'delete_order':
			$dorder_id = absint( wp_unslash( $_REQUEST['delete'] ?? 0 ) );
			$dorder    = new MemberOrder( $dorder_id );

			if ( $dorder->deleteMe() ) {
				/* translators: %s: order code or ID */
				$pmpro_msg  = sprintf( __( 'Order %s deleted successfully.', 'paid-memberships-pro' ), ! empty( $dorder->code ) ? $dorder->code : $dorder->id );
				$pmpro_msgt = 'pmpro_success';
			} else {
				$pmpro_msg  = __( 'Error deleting order.', 'paid-memberships-pro' );
				$pmpro_msgt = 'pmpro_error';
			}
			break;

		case 'check_token_order':
			$token_order_id = absint( wp_unslash( $_REQUEST['token_order'] ?? 0 ) );

			if ( $token_order_id ) {
				$completed = pmpro_check_token_order_for_completion( $token_order_id );
				if ( is_string( $completed ) ) {
					// An error string was returned.
					$pmpro_msg  = __( 'Error checking token order: ', 'paid-memberships-pro' ) . $completed;
					$pmpro_msgt = 'pmpro_error';
				} else {
					$pmpro_msg  = __( 'The token order has been completed.', 'paid-memberships-pro' );
					$pmpro_msgt = 'pmpro_success';
				}
			} else {
				$pmpro_msg  = __( 'Missing or invalid token order.', 'paid-memberships-pro' );
				$pmpro_msgt = 'pmpro_error';
			}
			break;

		case 'mark_payment_received':
			$paid_order_id = absint( wp_unslash( $_REQUEST['paid_order'] ?? 0 ) );
			$paid_order    = new MemberOrder( $paid_order_id );
			if ( ! empty( $paid_order->id ) && $paid_order->payment_type === 'Check' ) {
				$paid_order->status = 'success';
				if ( $paid_order->saveOrder() ) {
					$pmpro_msg  = sprintf( __( 'Payment for order # %s has been successfully marked as paid.', 'paid-memberships-pro' ), esc_html( $paid_order->code ) );
					$pmpro_msgt = 'pmpro_success';
				} else {
					$pmpro_msg  = sprintf( __( 'Error updating status for order # %s.', 'paid-memberships-pro' ), esc_html( $paid_order->code ) );
					$pmpro_msgt = 'pmpro_error';
				}
			} else {
				$pmpro_msg  = __( 'Cannot update order status: invalid order or payment type.', 'paid-memberships-pro' );
				$pmpro_msgt = 'pmpro_error';
			}
			break;

		case 'refund_order':
			$rorder_id = absint( wp_unslash( $_REQUEST['refund'] ?? 0 ) );
			$rorder    = new MemberOrder( $rorder_id );

			if ( ! empty( $rorder->id ) && pmpro_allowed_refunds( $rorder ) ) {
				if ( pmpro_refund_order( $rorder ) ) {
					$pmpro_msg  = __( 'Order refunded successfully.', 'paid-memberships-pro' );
					$pmpro_msgt = 'pmpro_success';
				} else {
					$pmpro_msg  = __( 'Error refunding order. Please check the order notes for more information.', 'paid-memberships-pro' );
					$pmpro_msgt = 'pmpro_error';
				}
			} else {
				$pmpro_msg  = __( 'Error refunding order. Please check the order notes for more information.', 'paid-memberships-pro' );
				$pmpro_msgt = 'pmpro_error';
			}
			break;

		case 'add_order_note':
			$order_id = absint( wp_unslash( $_REQUEST['id'] ?? 0 ) );
			$note = isset( $_POST['notes'] ) ? wp_unslash( $_POST['notes'] ) : '';

			if ( $order_id && $note !== '' ) {
				$order = new MemberOrder( $order_id );
				if ( ! empty( $order->id ) ) {
					// Add the note.
					$order->add_order_note( $note );

					// Save the order.
					if ( $order->saveOrder() ) {
						$pmpro_msg  = __( 'Order note added successfully.', 'paid-memberships-pro' );
						$pmpro_msgt = 'pmpro_success';
					} else {
						$pmpro_msg  = __( 'Error adding order note.', 'paid-memberships-pro' );
						$pmpro_msgt = 'pmpro_error';
					}
				} else {
					$pmpro_msg  = __( 'Invalid order.', 'paid-memberships-pro' );
					$pmpro_msgt = 'pmpro_error';
				}
			}
			break;

		default:
			break;
	}
}

// Order passed?
if ( ! empty( $_REQUEST['id'] ) ) {
	$order_id = intval( $_REQUEST['id'] );
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
		$order->billing->street2 = '';
		$order->billing->city = '';
		$order->billing->state = '';
		$order->billing->zip = '';
		$order->billing->country = '';
		$order->billing->phone = '';
		$order->discount_code = '';
		$order->subtotal = '';
		$order->tax = '';
		$order->total = '';
		$order->payment_type = '';
		$order->cardtype = '';
		$order->accountnumber = '';
		$order->expirationmonth = '';
		$order->expirationyear = '';
		$order->status = 'success';
		$order->gateway = get_option( 'pmpro_gateway' );
		$order->gateway_environment = get_option( 'pmpro_gateway_environment' );
		$order->payment_transaction_id = '';
		$order->subscription_transaction_id = '';
		$order->affiliate_id = '';
		$order->affiliate_subid = '';
		$order->notes = '';
	}
}

require_once( dirname( __FILE__ ) . '/admin_header.php' ); ?>

<?php if ( ! empty( $order ) ) {
	$list_url   = add_query_arg( array( 'page' => 'pmpro-orders' ), admin_url( 'admin.php' ) );
	$is_edit    = isset( $_REQUEST['edit'] ) && intval( $_REQUEST['edit'] ) === 1;
	$is_new     = empty( $order->id );
	$identifier = ! empty( $order->code ) ? sprintf( __( 'Order # %s', 'paid-memberships-pro' ), $order->code ) : sprintf( __( 'Order ID: %s', 'paid-memberships-pro' ), (int) $order->id );
	$order_url  = $is_new ? '' : add_query_arg( array( 'page' => 'pmpro-orders', 'id' => (int) $order->id ), admin_url( 'admin.php' ) );

	$items = array();

	// Always start with Orders (linked to list table).
	$items[] = array(
		'label'   => __( 'Orders', 'paid-memberships-pro' ),
		'url'     => $list_url,
		'current' => false,
		'title'   => __( 'View All Orders', 'paid-memberships-pro' ),
	);

	if ( $is_new ) {
		// Adding a new order.
		$items[] = array(
			'label'   => __( 'Add New Order', 'paid-memberships-pro' ),
			'url'     => '',
			'current' => true,
		);
	} elseif ( $is_edit ) {
		// Editing an order.
		$items[] = array(
			/* translators: %s is the order code (or ID fallback). */
			'label'   => $identifier,
			'url'     => $order_url,
			'current' => false,
			'title'   => sprintf( __( 'View Order # %s', 'paid-memberships-pro' ), $identifier ),
		);
		$items[] = array(
			'label'   => __( 'Edit Order', 'paid-memberships-pro' ),
			'url'     => '', // current, not linked
			'current' => true,
		);
	} else {
		// Viewing an order.
		$items[] = array(
			/* translators: %s is the order code (or ID fallback). */
			'label'   => $identifier,
			'url'     => '', // current, not linked
			'current' => true,
		);
	}
	?>
	<nav class="pmpro-nav-secondary pmpro-breadcrumbs" aria-labelledby="pmpro-orders-breadcrumbs">
		<h2 id="pmpro-orders-breadcrumbs" class="screen-reader-text">
			<?php esc_html_e( 'Orders navigation', 'paid-memberships-pro' ); ?>
		</h2>
		<ul>
			<?php foreach ( $items as $item ) : ?>
				<li>
					<?php if ( ! empty( $item['url'] ) ) : ?>
						<a href="<?php echo esc_url( $item['url'] ); ?>"<?php echo ! empty( $item['current'] ) ? ' class="current"' : ''; ?><?php echo ! empty( $item['current'] ) ? ' aria-current="page"' : ''; ?> title="<?php echo esc_attr( $item['title'] ?? '' ); ?>">
							<?php echo esc_html( $item['label'] ); ?>
						</a>
					<?php else : ?>
						<span class="<?php echo ! empty( $item['current'] ) ? 'current' : ''; ?>" <?php echo ! empty( $item['current'] ) ? 'aria-current="page"' : ''; ?>>
							<?php echo esc_html( $item['label'] ); ?>
						</span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<?php
	}
?>

<hr class="wp-header-end">

<?php
	// Allow emailing the order from the Orders list view or single Order view.
	if ( function_exists( 'pmpro_add_email_order_modal' ) && ! isset( $_REQUEST['edit'] ) ) {
		// Load the email order modal.
		pmpro_add_email_order_modal();
	}
?>

<?php
	if ( $pmpro_msg ) {
		?>
		<div role="alert" id="pmpro_message" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>">
			<?php echo wp_kses_post( $pmpro_msg ); ?>
		</div>
		<?php
	} else {
		?>
		<div id="pmpro_message" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message' ) ); ?>" style="display: none;"></div>
		<?php
	}
?>

<?php if ( ! empty( $order ) ) {
	if ( isset( $_REQUEST['edit'] ) && intval( $_REQUEST['edit'] ) === 1 ) {
		// Editing an order.
		require_once( PMPRO_DIR . '/adminpages/orders/edit-order.php' );
	} else {
		// Viewing an order.
		require_once( PMPRO_DIR . '/adminpages/orders/view-order.php' );
	}
} else {
	// Show list of orders.
	$now = current_time( 'timestamp' );
	$thisyear = date( 'Y', $now );
	?>

	<form id="order-list-form" method="get" action="">

		<h1 class="wp-heading-inline"><?php esc_html_e( 'Orders', 'paid-memberships-pro' ); ?></h1>
		<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 'id' => -1, 'edit' => 1 ), admin_url('admin.php' ) ) ); ?>" class="page-title-action pmpro-has-icon pmpro-has-icon-plus"><?php esc_html_e( 'Add New Order', 'paid-memberships-pro' ); ?></a>

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

		<?php if ( current_user_can( 'pmpro_orderscsv' ) ) { ?>
			<a target="_blank" href="<?php echo esc_url( $export_url ); ?>" class="page-title-action pmpro-has-icon pmpro-has-icon-download"><?php esc_html_e( 'Export to CSV', 'paid-memberships-pro' ); ?></a>
		<?php } ?>

		<?php
			$orders_list_table = new PMPro_Orders_List_Table();
			$orders_list_table->prepare_items();
			$orders_list_table->search_box( __( 'Search Orders', 'paid-memberships-pro' ), 'paid-memberships-pro' );
			$orders_list_table->display();
		?>
	</form>
<?php }

require_once( dirname( __FILE__ ) . '/admin_footer.php' );
