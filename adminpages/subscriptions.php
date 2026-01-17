<?php
global $wpdb, $pmpro_msg, $pmpro_msgt;

// only admins can get this
if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_edit_members' ) ) ) {
	die( esc_html__( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
}

$subscription = PMPro_Subscription::get_subscription( empty( $_REQUEST['id'] ) ? null : sanitize_text_field( $_REQUEST['id'] ) );

// Process syncing with gateway.
if ( ! empty( $subscription ) && ! empty( $_REQUEST['update'] ) && check_admin_referer( 'update', 'pmpro_subscriptions_nonce' ) ) {
	$subscription->update();
}

// Process cancelling a subscription.
if ( ! empty( $subscription ) && ! empty( $_REQUEST['cancel'] ) && check_admin_referer( 'cancel', 'pmpro_subscriptions_nonce' ) ) {
	$subscription->cancel_at_gateway();
}

// Process moving a subscription to a new level.
if ( ! empty( $subscription ) && ! empty( $_REQUEST['change-level'] ) && is_numeric( $_REQUEST['change-level'] ) && check_admin_referer( 'change-level', 'pmpro_subscriptions_nonce' ) ) {
	$subscription->set( 'membership_level_id', sanitize_text_field( $_REQUEST['change-level'] ) );
	$subscription->save();
}

// Process linking a subscription.
if ( isset( $_REQUEST['action'] ) && 'link' === $_REQUEST['action'] ) {
	if ( ! empty( $_POST ) && check_admin_referer( 'link', 'pmpro_subscriptions_nonce' ) ) {
		// Make sure all required fields are set.
		if ( empty( $_POST['subscription_transaction_id'] ) || empty( $_POST['gateway'] ) || empty( $_POST['gateway_environment'] ) || empty( $_POST['user_id'] ) || empty( $_POST['membership_level_id'] ) ) {
			$pmpro_msg  = esc_html__( 'All fields are required.', 'paid-memberships-pro' );
			$pmpro_msgt = 'pmpro_error';
		}

		// Make sure that the user ID is valid.
		if ( ! get_userdata( sanitize_text_field( $_POST['user_id'] ) ) ) {
			$pmpro_msg  = esc_html__( 'Invalid user ID.', 'paid-memberships-pro' );
			$pmpro_msgt = 'pmpro_error';
		}

		// Make sure that the membership level ID is valid.
		if ( ! pmpro_getLevel( sanitize_text_field( $_POST['membership_level_id'] ) ) ) {
			$pmpro_msg  = esc_html__( 'Invalid membership level ID.', 'paid-memberships-pro' );
			$pmpro_msgt = 'pmpro_error';
		}

		// Check if this subscription already exists.
		if ( 'pmpro_error' !== $pmpro_msgt ) {
			$test_subscription = PMPro_Subscription::get_subscription_from_subscription_transaction_id( sanitize_text_field( $_POST['subscription_transaction_id'] ), sanitize_text_field( $_POST['gateway'] ), sanitize_text_field( $_POST['gateway_environment'] ) );

			if ( ! empty( $test_subscription ) ) {
				$pmpro_msg  = esc_html__( 'This subscription already exists on your website.', 'paid-memberships-pro' );
				$pmpro_msgt = 'pmpro_error';
			}
		}

		// Create a new subscription.
		if ( 'pmpro_error' !== $pmpro_msgt ) {
			$create_subscription_args = array(
				'user_id'              => sanitize_text_field( $_POST['user_id'] ),
				'membership_level_id'  => sanitize_text_field( $_POST['membership_level_id'] ),
				'gateway'              => sanitize_text_field( $_POST['gateway'] ),
				'gateway_environment'  => sanitize_text_field( $_POST['gateway_environment'] ),
				'subscription_transaction_id' => sanitize_text_field( $_POST['subscription_transaction_id'] ),
				'status'               => 'active',
			);
			$subscription = PMPro_Subscription::create( $create_subscription_args );

			if ( ! empty( $subscription ) ) {
				// Show a success message.
				$pmpro_msg  = esc_html__( 'Subscription linked successfully.', 'paid-memberships-pro' );
				$pmpro_msgt = 'pmpro_success';

				// Go to the "view" page.
				unset( $_REQUEST['action'] );
			} else {
				// Show an error message.
				$pmpro_msg  = esc_html__( 'Error linking subscription.', 'paid-memberships-pro' );
				$pmpro_msgt = 'pmpro_error';
			}
		}
	}
}

// Process editing a subscription.
if ( ! empty( $subscription ) && isset( $_REQUEST['action'] ) && 'edit' === $_REQUEST['action'] ) {
	if ( ! empty( $_POST ) && check_admin_referer( 'edit', 'pmpro_subscriptions_nonce' ) ) {
		// Make sure all required fields are set.
		if ( empty( $_POST['user_id'] ) || empty( $_POST['membership_level_id'] ) ) {
			$pmpro_msg  = esc_html__( 'All fields are required.', 'paid-memberships-pro' );
			$pmpro_msgt = 'pmpro_error';
		}

		// Make sure that the user ID is valid.
		if ( ! get_userdata( sanitize_text_field( $_POST['user_id'] ) ) ) {
			$pmpro_msg  = esc_html__( 'Invalid user ID.', 'paid-memberships-pro' );
			$pmpro_msgt = 'pmpro_error';
		}

		// Make sure that the membership level ID is valid.
		if ( ! pmpro_getLevel( sanitize_text_field( $_POST['membership_level_id'] ) ) ) {
			$pmpro_msg  = esc_html__( 'Invalid membership level ID.', 'paid-memberships-pro' );
			$pmpro_msgt = 'pmpro_error';
		}

		// Update the subscription.
		if ( 'pmpro_error' !== $pmpro_msgt ) {
			$subscription->set( 'user_id', sanitize_text_field( $_POST['user_id'] ) );
			$subscription->set( 'membership_level_id', sanitize_text_field( $_POST['membership_level_id'] ) );
			$subscription->save();

			// Show a success message with link to view.
			$pmpro_msg  = __( 'Subscription saved successfully.', 'paid-memberships-pro' );
			$pmpro_msg .= ' <a href="' . esc_url( add_query_arg( array( 'page' => 'pmpro-subscriptions', 'id' => $subscription->get_id() ), admin_url( 'admin.php' ) ) ) . '">';
			/* translators: %d: Subscription ID */
			$pmpro_msg .= sprintf( __( 'View Subscription # %d', 'paid-memberships-pro' ), $subscription->get_id() );
			$pmpro_msg .= '</a>';
			$pmpro_msgt = 'pmpro_success';
		}
	}
}

require_once( dirname( __FILE__ ) . '/admin_header.php' );

$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';

// Build breadcrumb navigation for single subscription views.
if ( ! empty( $subscription ) || 'link' === $action ) {
	$list_url   = add_query_arg( array( 'page' => 'pmpro-subscriptions' ), admin_url( 'admin.php' ) );
	$is_edit    = 'edit' === $action;
	$is_link    = 'link' === $action;
	/* translators: %d: Subscription ID */
	$identifier = ! empty( $subscription ) ? sprintf( __( 'Subscription # %d', 'paid-memberships-pro' ), $subscription->get_id() ) : '';
	$subscription_url = ! empty( $subscription ) ? add_query_arg( array( 'page' => 'pmpro-subscriptions', 'id' => $subscription->get_id() ), admin_url( 'admin.php' ) ) : '';

	$items = array();

	// Always start with Subscriptions (linked to list table).
	$items[] = array(
		'label'   => __( 'Subscriptions', 'paid-memberships-pro' ),
		'url'     => $list_url,
		'current' => false,
		'title'   => __( 'View All Subscriptions', 'paid-memberships-pro' ),
	);

	if ( $is_link ) {
		// Linking a new subscription.
		$items[] = array(
			'label'   => __( 'Link Subscription', 'paid-memberships-pro' ),
			'url'     => '',
			'current' => true,
		);
	} elseif ( $is_edit ) {
		// Editing a subscription.
		$items[] = array(
			'label'   => $identifier,
			'url'     => $subscription_url,
			'current' => false,
			/* translators: %d: Subscription ID */
			'title'   => sprintf( __( 'View Subscription # %d', 'paid-memberships-pro' ), $subscription->get_id() ),
		);
		$items[] = array(
			'label'   => __( 'Edit Subscription', 'paid-memberships-pro' ),
			'url'     => '', // current, not linked
			'current' => true,
		);
	} else {
		// Viewing a subscription.
		$items[] = array(
			'label'   => $identifier,
			'url'     => '', // current, not linked
			'current' => true,
		);
	}
	?>
	<nav class="pmpro-nav-secondary pmpro-breadcrumbs" aria-labelledby="pmpro-subscriptions-breadcrumbs">
		<h2 id="pmpro-subscriptions-breadcrumbs" class="screen-reader-text">
			<?php esc_html_e( 'Subscriptions navigation', 'paid-memberships-pro' ); ?>
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

<?php
if ( 'link' === $action ) {
	// Linking a subscription.
	require_once( PMPRO_DIR . '/adminpages/subscriptions/link-subscription.php' );
} elseif ( ! empty( $subscription ) && 'edit' === $action ) {
	// Editing a subscription.
	require_once( PMPRO_DIR . '/adminpages/subscriptions/edit-subscription.php' );
} elseif ( ! empty( $subscription ) ) {
	// Viewing a subscription.
	require_once( PMPRO_DIR . '/adminpages/subscriptions/view-subscription.php' );
} else {
	// Show list of subscriptions.
	?>
	<form id="subscriptions-list-form" method="get" action="">

		<h1 class="wp-heading-inline"><?php esc_html_e( 'Subscriptions', 'paid-memberships-pro' ); ?></h1>

		<a
			href="<?php echo ( esc_url( add_query_arg( array( 'page' => 'pmpro-subscriptions', 'action' => 'link' ), admin_url('admin.php' ) ) ) ); ?>"
			title="<?php esc_attr_e( 'Link Subscription', 'paid-memberships-pro' ); ?>"
			class="page-title-action pmpro-has-icon pmpro-has-icon-plus">
			<?php esc_html_e( 'Link Subscription', 'paid-memberships-pro' ); ?>
		</a>

		<?php
			$subscriptions_list_table = new PMPro_Subscriptions_List_Table();
			$subscriptions_list_table->prepare_items();
			$subscriptions_list_table->search_box( __( 'Search Subscriptions', 'paid-memberships-pro' ), 'paid-memberships-pro' );
			$subscriptions_list_table->display();
		?>
	</form>
	<?php
}

require_once( dirname( __FILE__ ) . '/admin_footer.php' );
