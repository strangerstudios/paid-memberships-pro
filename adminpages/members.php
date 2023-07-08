<?php
// only admins can get this
$cap = apply_filters( 'pmpro_add_member_cap', 'edit_users' );
if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( $cap ) ) ) {
	die( esc_html__( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
}

// Global vars.
global $wpdb, $current_user, $msg, $msgt, $pmpro_currency_symbol, $pmpro_required_user_fields, $pmpro_error_fields, $pmpro_msg, $pmpro_msgt;

// Check if editing a user.
if ( ! empty( $_REQUEST['user_id'] ) ) {
	$user_id = intval( $_REQUEST['user_id'] );
	$user = get_userdata( $user_id );
	if ( empty( $user->ID ) ) {
		$user_id = false;		
	} else  {
		// We have a user, let's get the user metadata
		$user_notes = get_user_meta( $user_id, 'user_notes', true );
	}	
} else {
	$user_id = '';
}

// Define a constant if user is editing their own membership.
if ( ! defined( 'IS_PROFILE_PAGE' ) ) {
	define( 'IS_PROFILE_PAGE', ( $user_id === $current_user->ID ) );
}

// Some vars for the form.
$user_login = ! empty( $_POST['user_login'] ) ? sanitize_user( $_POST['user_login'] ) : '';
$user_email = ! empty( $_POST['email'] ) ? stripslashes( sanitize_email( $_POST['email'] ) ) : '';
$first_name = ! empty( $_POST['first_name'] ) ? stripslashes( sanitize_text_field( $_POST['first_name'] ) ): '';
$last_name = ! empty( $_POST['last_name'] ) ? stripslashes( sanitize_text_field( $_POST['last_name'] ) ) : '';	
$user_pass = ! empty( $_POST['pass1'] ) ? $_POST['pass1'] : ''; // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
$send_password = ! empty( $_POST['send_password'] ) ? intval( $_POST['send_password'] ) : false;
$role = ! empty( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : get_option( 'default_role' );
//$user_notes = ! empty( $_POST['user_notes'] ) ? stripslashes( sanitize_text_field( $_POST['user_notes'] ) ) : '';

// Set values if editing a user and the form wasn't submitted.
if ( ! empty( $user ) && empty( $_POST ) ) {
	$user_login = $user->user_login;
	$user_display_name = $user->display_name;
	$user_email = $user->user_email;
	$first_name = $user->first_name;
	$last_name = $user->last_name;
	$user_notes = $user->user_notes;
	$role = implode( ', ', $user->roles );
}

// Get the current user level.
if ( isset( $_POST['membership_level'] ) ) {
	$membership_level = intval( $_POST['membership_level'] );
} elseif ( ! empty( $user ) ) {
	$user->membership_level = pmpro_getMembershipLevelForUser( $user_id );
	if ( ! empty( $user->membership_level ) ) {
		$membership_level = $user->membership_level->id;
	} else {
		$membership_level = '';
	}
} else {
		$membership_level = '';
}

// Handle user update/insert.
if ( ! empty( $_POST ) ) {
	// Make sure the current user can edit this user.
	// Alterred from wp-admin/user-edit.php.
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		wp_die( __( 'Sorry, you are not allowed to edit this user.', 'paid-memberships-pro' ) );
	}
	
	// Update the email address in signups, if present.
	// Alterred from wp-admin/user-edit.php.
	if ( is_multisite() ) {
		$user = get_userdata( $user_id );

		if ( $user->user_login && isset( $_POST['email'] ) && is_email( $_POST['email'] ) && $wpdb->get_var( $wpdb->prepare( "SELECT user_login FROM {$wpdb->signups} WHERE user_login = %s", $user->user_login ) ) ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->signups} SET user_email = %s WHERE user_login = %s", $_POST['email'], $user_login ) );
		}
	}
	
	// check for required fields
	$pmpro_required_user_fields = apply_filters(
		'pmpro_required_user_fields', array(
			'user_login' => $user_login,
			'user_email' => $user_email,
		)
	);
	$pmpro_error_fields = array();
	foreach ( $pmpro_required_user_fields as $key => $value ) {
		if ( empty( $value ) ) {
			$pmpro_error_fields[] = $key;
		}
	}

	if ( ! empty( $pmpro_error_fields ) ) {
		pmpro_setMessage( __( 'Please fill out all required fields:', 'paid-memberships-pro' ) . ' ' . implode( ', ', $pmpro_error_fields ), 'notice-error' );
	}

	// Check that the email and username are available.
	$ouser      = get_user_by( 'login', $user_login );
	$oldem_user = get_user_by( 'email', $user_email );

	/**
	 * This hook can be used to allow multiple accounts with the same email address.
	 * This is also set in preheaders/checkout.php
	 * @todo Abstract to a function so we only have one filter.
	 * Return null to allow duplicate users with the same email.
	 */
	$oldemail = apply_filters( "pmpro_checkout_oldemail", ( false !== $oldem_user ? $oldem_user->user_email : null ) );

	if ( ! empty( $ouser->user_login ) && $ouser->id !== $user_id ) {
		pmpro_setMessage( __( "That username is already taken. Please try another.", 'paid-memberships-pro' ), "notice-error" );
		$pmpro_error_fields[] = "username";
	}

	if ( ! empty( $oldemail ) && $oldem_user->id !== $user_id ) {
		pmpro_setMessage( __( "That email address is already in use. Please log in, or use a different email address.", 'paid-memberships-pro' ), "notice-error" );
		$pmpro_error_fields[] = "bemail";
		$pmpro_error_fields[] = "bconfirmemail";
	}

	// okay so far?
	if ( $pmpro_msgt != 'notice-error' ) {
		// random password if needed
		if ( ! $user_id && empty( $user_pass ) ) {
			$user_pass = wp_generate_password();
			$send_password = true; // Force this option to be true, if the password field was empty so the email may be sent.
		}

		// User data.
		$user_to_post = array( 
			'ID' => $user_id,
			'user_login' => $user_login,
			'user_email' => $user_email,
			'first_name' => $first_name,
			'last_name' => $last_name,
			'role' => $role,
		);

		// Update or insert user.
		$user_id = ! empty( $_REQUEST['user_id'] ) ? wp_update_user($user_to_post) : wp_insert_user($user_to_post);
	}

	if ( ! $user_id ) {
		// Error during user update/insert.
		if ( ! empty( $_REQUEST['user_id'] ) ) {
			pmpro_setMessage( __( 'Error updating user.', 'paid-memberships-pro' ), 'notice-error' );
			$user_id = intval( $_REQUEST['user_id'] );	// Reset user_id var so rest of form works.
		} else {
			pmpro_setMessage( __( 'Error creating user.', 'paid-memberships-pro' ), 'notice-error' );
		}
	} elseif ( $pmpro_msgt === 'notice-error' ) {		
		// There was another error above.
		if ( ! empty( $_REQUEST['user_id'] ) ) {
			$pmpro_msg = esc_html__( 'There was an error updating this user: ', 'paid-memberships-pro' ) . $pmpro_msg;
		} else {
			$pmpro_msg = esc_html__( 'There was an error adding this user: ', 'paid-memberships-pro' ) . $pmpro_msg;
		}
	} else {
		// Update/insert all good.

		// Other user meta
		//update_user_meta( $user_id, 'user_notes', $user_notes );

		// Update the user's membership level.
		// TODO: ...

		// Notify users if needed.
		if ( $send_password ) {
			wp_new_user_notification( $user_id, null, 'user' );
		}

		// clear vars
		$user_pass = '';
		
		// Set message and redirect if this is a new user.		
		if ( empty( $_REQUEST['user_id'] ) ) {
			// User inserted.
			pmpro_setMessage( esc_html__( 'User added.', 'paid-memberships-pro' ), 'updated' );
			$_REQUEST['user_id'] = $user_id;
			?>
			<script>
				jQuery(document).ready(function() {					
					jQuery('#tab-2').click();
				});
			</script>
			<?php
		} else {
			// Users updated.
			pmpro_setMessage( esc_html__( 'User updated.', 'paid-memberships-pro' ), 'updated' );
		}			
	}
}
?>

<?php
/**
 * Load the Paid Memberships Pro dashboard-area header
 */
require_once( dirname( __FILE__ ) . '/admin_header.php' ); ?>
	<hr class="wp-header-end">
	<h1 class="wp-heading-inline">
		<?php		
		if ( ! empty( $_REQUEST['user_id'] ) ) {
			echo get_avatar( $user_id, 96 );
			echo wp_kses_post( sprintf( __( 'Edit Member: %s', 'paid-memberships-pro' ), '<strong>' . $user_display_name . '</strong>' ) );
		} else {
			echo esc_html_e( 'Add Member', 'paid-memberships-pro' );
		}
		?>
	</h1>
	<div>		
		<form class="pmpro-members" action="" method="post">
			<nav id="user-menu" role="tablist" aria-label="Add Member Field Tabs">
				<button
					role="tab"
					aria-selected="true"
					aria-controls="panel-1"
					id="tab-1"
					tabindex="0">
					User Info
				</button>				
				<button
					role="tab"
					aria-selected="false"
					aria-controls="panel-2"
					id="tab-2"
					tabindex="-1">
					Membership
				</button>
				<button
					role="tab"
					aria-selected="false"
					aria-controls="panel-3"
					id="tab-3"
					tabindex="-1">
					Subscriptions
				</button>
				<button
					role="tab"
					aria-selected="false"
					aria-controls="panel-4"
					id="tab-4"
					tabindex="-1">
					Orders
				</button>
				<button
					role="tab"
					aria-selected="false"
					aria-controls="panel-5"
					id="tab-5"
					tabindex="-1">
					Other Info
				</button>						
			</nav>
			<div class="pmpro_section">
				<div id="panel-1" role="tabpanel" tabindex="0" aria-labelledby="tab-1">
					<h2>
						<?php esc_html_e( 'User Info', 'paid-memberships-pro' ); ?>
						<a href="<?php echo esc_url( add_query_arg( array( 'user_id' => intval( $user_id ) ), admin_url( 'user-edit.php' ) ) ); ?>" target="_blank" class="page-title-action pmpro-has-icon pmpro-has-icon-admin-users"><?php esc_html_e( 'Edit User', 'paid-memberships-pro' ); ?></a>
					</h2>
					<table class="form-table">
						<tr>
							<th><label for="user_login"><?php esc_html_e( 'Username (required)', 'paid-memberships-pro' ); ?></label></th>
							<td><input type="text" name="user_login" id="user_login" autocapitalize="none" autocorrect="off" autocomplete="off" required <?php if ( ! empty( $_REQUEST['user_id'] ) ) { ?>readonly="true"<?php } ?> value="<?php echo esc_attr( $user_login ) ?>"></td>
						</tr>
						<tr>
							<th><label for="email"><?php esc_html_e( 'Email (required)', 'paid-memberships-pro' ); ?></label></th>
							<td><input type="email" name="email" id="email" autocomplete="new-password" spellcheck="false" required value="<?php echo esc_attr( $user_email ) ?>"></td>
						</tr>
						<tr>
							<th><label for="first_name"><?php esc_html_e( 'First Name', 'paid-memberships-pro' ); ?></label></th>
							<td><input type="text" name="first_name" id="first_name" autocomplete="off" value="<?php echo $first_name ?>"></td>
						</tr>
						<tr>
							<th><label for="last_name"><?php esc_html_e( 'Last Name', 'paid-memberships-pro' ); ?></label></th>
							<td><input type="text" name="last_name" id="last_name" autocomplete="off" value="<?php echo $last_name ?>"></td>
						</tr>						
						<?php
						// Only show for new users.
						if ( empty( $_REQUEST['user_id'] ) ) {
						?>
						<tr>
							<th><label for="password"><?php esc_html_e( 'Password', 'paid-memberships-pro' ); ?></label></th>
							<td>
								<input type="password" name="password" id="password" autocomplete="off" required value="">
								<button class="toggle-pass-visibility" aria-controls="password" aria-expanded="false"><span class="dashicons dashicons-visibility toggle-pass-visibility"></span></button>
							</td>
						</tr>
						<tr>
							<th><label for="send_password">Send User Notification</label></th>
							<td><input type="checkbox" name="send_password" id="send_password">
							<label for="send_password">Send the new user an email about their account.</label>
							</td>
						</tr>
						<?php
						}
						?>
						<?php if ( ! IS_PROFILE_PAGE && current_user_can( 'promote_user', $user->ID ) ) { ?>
						<tr>
							<th><label for="role"><?php esc_html_e( 'Role', 'paid-memberships-pro' ); ?></label></th>
							<td>
								<select name="role" id="role" class="<?php echo pmpro_getClassForField( 'role' ); ?>">
									<?php wp_dropdown_roles( $role ); ?>
								</select>
							</td>
						</tr>
						<?php } ?>
					</table>
				</div>

				<?php
				// Only show for existing users.
				if ( ! empty( $_REQUEST['user_id'] ) ) {
				?>
				<div id="panel-2" role="tabpanel" tabindex="0" aria-labelledby="tab-2" hidden>
					<h2><?php esc_html_e( 'Membership', 'paid-memberships-pro' ); ?></h2>
					<p>(New UI goes here.)</p>
					<hr />
					<?php
						// Show all membership history for user.
						$levelshistory = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_memberships_users WHERE user_id = %s ORDER BY id DESC", $user->ID ) );

						// Build the selectors for the membership levels history list based on history count.
						$levelshistory_classes = array();
						if ( ! empty( $levelshistory ) && count( $levelshistory ) > 10 ) {
							$levelshistory_classes[] = "pmpro_scrollable";
						}
						$levelshistory_class = implode( ' ', array_unique( $levelshistory_classes ) );
					?>
					<h3><?php esc_html_e( 'Membership History', 'paid-memberships-pro' ); ?></h3>
					<div id="member-history-memberships" class="<?php echo esc_attr( $levelshistory_class ); ?>">
					<?php if ( $levelshistory ) { ?>
						<table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Level ID', 'paid-memberships-pro' ); ?>
								<th><?php esc_html_e( 'Level', 'paid-memberships-pro' ); ?></th>
								<th><?php esc_html_e( 'Start Date', 'paid-memberships-pro' ); ?></th>
								<th><?php esc_html_e( 'Date Modified', 'paid-memberships-pro' ); ?></th>
								<th><?php esc_html_e( 'End Date', 'paid-memberships-pro' ); ?></th>
								<th><?php esc_html_e( 'Level Cost', 'paid-memberships-pro' ); ?></th>
								<th><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></th>
								<?php do_action( 'pmpromh_member_history_extra_cols_header' ); ?>
							</tr>
						</thead>
						<tbody>
						<?php
							foreach ( $levelshistory as $levelhistory ) {
								$level = pmpro_getLevel( $levelhistory->membership_id );

								if ( $levelhistory->enddate === null || $levelhistory->enddate == '0000-00-00 00:00:00' ) {
									$levelhistory->enddate = __( 'Never', 'paid-memberships-pro' );
								} else {
									$levelhistory->enddate = date_i18n( get_option( 'date_format'), strtotime( $levelhistory->enddate ) );
								} ?>
								<tr>
									<td><?php if ( ! empty( $level ) ) { echo $level->id; } else { esc_html_e( 'N/A', 'paid-memberships-pro' ); } ?></td>
									<td><?php if ( ! empty( $level ) ) { echo $level->name; } else { esc_html_e( 'N/A', 'paid-memberships-pro' ); } ?></td>
									<td><?php echo ( $levelhistory->startdate === '0000-00-00 00:00:00' ? __('N/A', 'paid-memberships-pro') : date_i18n( get_option( 'date_format' ), strtotime( $levelhistory->startdate ) ) ); ?></td>
									<td><?php echo date_i18n( get_option( 'date_format'), strtotime( $levelhistory->modified ) ); ?></td>
									<td><?php echo esc_html( $levelhistory->enddate ); ?></td>
									<td><?php echo pmpro_getLevelCost( $levelhistory, true, true ); ?></td>
									<td>
										<?php 
											if ( empty( $levelhistory->status ) ) {
												echo '-';
											} else {
												echo esc_html( $levelhistory->status ); 
											}
										?>
									</td>
									<?php do_action( 'pmpromh_member_history_extra_cols_body', $user, $level ); ?>
								</tr>
								<?php
							}
						?>
						</tbody>
						</table>
						<?php } else { ?>
							<table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
								<tbody>
									<tr>
										<td><?php esc_html_e( 'No membership history found.', 'paid-memberships-pro'); ?></td>
									</tr>
								</tbody>
							</table>
						<?php } ?>
					</div> <!-- end #member-history-memberships -->					
				</div>
				<div id="panel-3" role="tabpanel" tabindex="0" aria-labelledby="tab-3" hidden>
					<h2><?php esc_html_e( 'Subscriptions', 'paid-memberships-pro' ); ?></h2>
					(edit customer in Stripe should go here once we have a hook for it)
					<?php
						// Show all subscriptions for user.
						$subscriptions = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_subscriptions WHERE user_id = %d ORDER BY startdate DESC", $user->ID ) );

						// Build the selectors for the subscription history list based on history count.
						$subscriptions_classes = array();
						if ( ! empty( $subscriptions ) && count( $subscriptions ) > 10 ) {
							$subscriptions_classes[] = "pmpro_scrollable";
						}
						$subscriptions_class = implode( ' ', array_unique( $subscriptions_classes ) );
					?>
					<div id="member-history-subscriptions" class="<?php echo esc_attr( $subscriptions_class ); ?>">
					<?php if ( $subscriptions ) { ?>
						<table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Date', 'paid-memberships-pro' ); ?></th>
								<th><?php esc_html_e( 'Subscription Transaction ID', 'paid-memberships-pro' ); ?></th>
								<th><?php esc_html_e( 'Level', 'paid-memberships-pro' ); ?></th>
								<th><?php esc_html_e( 'Gateway', 'paid-memberships-pro' ); ?>
								<th><?php esc_html_e( 'Gateway Environment', 'paid-memberships-pro' ); ?></th>
								<th><?php esc_html_e( 'Next Payment Date', 'paid-memberships-pro' ); ?></th>
								<th><?php esc_html_e( 'Ended', 'paid-memberships-pro' ); ?></th>
								<th><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php
							foreach ( $subscriptions as $subscription ) { 
								$level = pmpro_getLevel( $subscription->membership_level_id );
								?>
								<tr>
									<td><?php echo esc_html( $subscription->startdate ); ?></td>
									<td><a href="<?php echo ( esc_url( add_query_arg( array( 'page' => 'pmpro-subscriptions', 'id' => $subscription->id ), admin_url('admin.php' ) ) ) ); ?>"><?php echo esc_html( $subscription->subscription_transaction_id ); ?></a></td>
									<td><?php if ( ! empty( $level ) ) { echo esc_html( $level->name ); } else { esc_html_e( 'N/A', 'paid-memberships-pro'); } ?></td>
									<td><?php echo esc_html( $subscription->gateway ); ?>
									<td><?php echo esc_html( $subscription->gateway_environment ); ?>
									<td><?php echo esc_html( $subscription->next_payment_date ); ?>
									<td><?php echo esc_html( $subscription->enddate ); ?>
									<td><?php echo esc_html( $subscription->status ); ?>
								</tr>
								<?php
							}
						?>
						</tbody>
						</table>
						<?php } else { 
							esc_html_e( 'No subscriptions found.', 'paid-memberships-pro' );
						} ?>
					</div>
				</div>
				<div id="panel-4" role="tabpanel" tabindex="0" aria-labelledby="tab-4" hidden>
					<h2>
						<?php esc_html_e( 'Orders', 'paid-memberships-pro' ); ?>
						<a class="page-title-action" href="<?php echo admin_url( 'admin.php?page=pmpro-orders&order=-1&user_id=' . $user->ID ); ?>"><?php esc_html_e( 'Add Order', 'paid-memberships-pro' ); ?></a>
					</h2>
					<?php
						//Show all invoices for user
						$invoices = $wpdb->get_results( $wpdb->prepare( "SELECT mo.*, du.code_id as code_id FROM $wpdb->pmpro_membership_orders mo LEFT JOIN $wpdb->pmpro_discount_codes_uses du ON mo.id = du.order_id WHERE mo.user_id = %d ORDER BY mo.timestamp DESC", $user->ID ) );

						// Build the selectors for the invoices history list based on history count.
						$invoices_classes = array();
						if ( ! empty( $invoices ) && count( $invoices ) > 10 ) {
							$invoices_classes[] = "pmpro_scrollable";
						}
						$invoice_class = implode( ' ', array_unique( $invoices_classes ) );
					?>
					<div id="member-history-orders" class="<?php echo esc_attr( $invoice_class ); ?>">
					<?php if ( $invoices ) { ?>
						<table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Date', 'paid-memberships-pro' ); ?></th>
								<th><?php esc_html_e( 'Code', 'paid-memberships-pro' ); ?></th>
								<th><?php esc_html_e( 'Level', 'paid-memberships-pro' ); ?></th>
								<th><?php esc_html_e( 'Total', 'paid-memberships-pro' ); ?></th>
								<th><?php esc_html_e( 'Discount Code', 'paid-memberships-pro' ); ?></th>
								<th><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></th>
								<?php do_action('pmpromh_orders_extra_cols_header');?>
							</tr>
						</thead>
						<tbody>
						<?php
							foreach ( $invoices as $invoice ) { 
								$level = pmpro_getLevel( $invoice->membership_id );
								?>
								<tr>
									<td>
										<?php
											echo esc_html( sprintf(
												// translators: %1$s is the date and %2$s is the time.
												__( '%1$s at %2$s', 'paid-memberships-pro' ),
												esc_html( date_i18n( get_option( 'date_format' ), strtotime( get_date_from_gmt( $invoice->timestamp ) ) ) ),
												esc_html( date_i18n( get_option( 'time_format' ), strtotime( get_date_from_gmt( $invoice->timestamp ) ) ) )
											) );
										?>
									</td>
									<td class="order_code column-order_code has-row-actions">
										<strong><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 'order' => $invoice->id ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $invoice->code ); ?></a></strong>
										<div class="row-actions">
											<span class="id">
												<?php echo sprintf(
													// translators: %s is the Order ID.
													__( 'ID: %s', 'paid-memberships-pro' ),
													esc_attr( $invoice->id )
												); ?>
											</span> |
											<span class="edit">
												<a title="<?php esc_attr_e( 'Edit', 'paid-memberships-pro' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 'order' => $invoice->id ), admin_url('admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'paid-memberships-pro' ); ?></a>
											</span> |
											<span class="print">
												<a target="_blank" title="<?php esc_attr_e( 'Print', 'paid-memberships-pro' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'action' => 'pmpro_orders_print_view', 'order' => $invoice->id ), admin_url('admin-ajax.php' ) ) ); ?>"><?php esc_html_e( 'Print', 'paid-memberships-pro' ); ?></a>
											</span>
											<?php if ( function_exists( 'pmpro_add_email_order_modal' ) ) { ?>
												|
												<span class="email">
													<a title="<?php esc_attr_e( 'Email', 'paid-memberships-pro' ); ?>" href="#TB_inline?width=600&height=200&inlineId=email_invoice" class="thickbox email_link" data-order="<?php echo esc_attr( $invoice->id ); ?>"><?php esc_html_e( 'Email', 'paid-memberships-pro' ); ?></a>
												</span>
											<?php } ?>
										</div> <!-- end .row-actions -->
									</td>
									<td>
										<?php
											if ( ! empty( $level ) ) {
												echo esc_html( $level->name );
											} elseif ( $invoice->membership_id > 0 ) { ?>
												[<?php esc_html_e( 'deleted', 'paid-memberships-pro' ); ?>]
											<?php } else {
												esc_html_e( '&#8212;', 'paid-memberships-pro' );
											}
										?>
									</td>
									<td><?php echo pmpro_formatPrice( $invoice->total ); ?></td>
									<td><?php 
										if ( empty( $invoice->code_id ) ) {
											esc_html_e( '&#8212;', 'paid-memberships-pro' );
										} else {
											$discountQuery = $wpdb->prepare( "SELECT c.code FROM $wpdb->pmpro_discount_codes c WHERE c.id = %d LIMIT 1", $invoice->code_id );
											$discount_code = $wpdb->get_row( $discountQuery );
											echo '<a href="admin.php?page=pmpro-discountcodes&edit=' . esc_attr( $invoice->code_id ). '">'. esc_attr( $discount_code->code ) . '</a>';
										}
									?></td>
									<td>
										<?php
											if ( empty( $invoice->status ) ) {
												esc_html_e( '&#8212;', 'paid-memberships-pro' );
											} else { ?>
												<span class="pmpro_order-status pmpro_order-status-<?php esc_attr_e( $invoice->status ); ?>">
													<?php if ( in_array( $invoice->status, array( 'success', 'cancelled' ) ) ) {
														esc_html_e( 'Paid', 'paid-memberships-pro' );
													} else {
														esc_html_e( ucwords( $invoice->status ) );
													} ?>
												</span>
												<?php
											}
										?>
									</td>
									<?php do_action( 'pmpromh_orders_extra_cols_body', $invoice ); ?>
								</tr>
								<?php
							}
						?>
						</tbody>
						</table>
					<?php } else { ?>
						<table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
							<tbody>
								<tr>
									<td><?php esc_html_e( 'No membership orders found.', 'paid-memberships-pro' ); ?></td>
								</tr>
							</tbody>
						</table>
					<?php } ?>
					</div> <!-- end #member-history-orders -->
				</div>
				<div id="panel-5" role="tabpanel" tabindex="0" aria-labelledby="tab-5" hidden>
					<h2><?php esc_html_e( 'Other Info', 'paid-memberships-pro' ); ?></h2>
					<?php
						// Show TOS Consent History if available.
						$tospage_id = pmpro_getOption( 'tospage' );
						$consent_log = pmpro_get_consent_log( $user->ID, true );
						if ( ! empty( $tospage_id ) || ! empty( $consent_log ) ) { ?>
							<h3><?php esc_html_e("TOS Consent History", 'paid-memberships-pro' ); ?></h3>
							<div id="tos_consent_history">
								<?php
									if ( ! empty( $consent_log ) ) {
										// Build the selectors for the invoices history list based on history count.
										$consent_log_classes = array();
										$consent_log_classes[] = "pmpro_consent_log";
										if ( count( $consent_log ) > 5 ) {
											$consent_log_classes[] = "pmpro_scrollable";
										}
										$consent_log_class = implode( ' ', array_unique( $consent_log_classes ) );
										echo '<ul class="' . esc_attr( $consent_log_class ) . '">';
										foreach( $consent_log as $entry ) {
											echo '<li>' . pmpro_consent_to_text( $entry ) . '</li>';
										}
										echo '</ul> <!-- end pmpro_consent_log -->';
									} else {
										echo __( 'N/A', 'paid-memberships-pro' );
									}
								?>
							</div>
							<?php
						}
					?>
				</div>
				<?php
				}
				?>
					
				<div class="submit">
					<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>">					
					<input type="submit" name="submit" value="Save User" class="button button-primary">
				</div>
			</div>
		</form>			
    </div>
<script>
window.addEventListener("DOMContentLoaded", () => {
	const tabs = document.querySelectorAll('[role="tab"]');
	const tabList = document.querySelector('[role="tablist"]');

	// Add a click event handler to each tab
	tabs.forEach((tab) => {
	tab.addEventListener("click", changeTabs);
	});

	// Enable arrow navigation between tabs in the tab list
	let tabFocus = 0;

	tabList.addEventListener("keydown", (e) => {
	// Move Down
	if (e.key === "ArrowDown" || e.key === "ArrowUp") {
		tabs[tabFocus].setAttribute("tabindex", -1);
		if (e.key === "ArrowDown") {
		tabFocus++;
		// If we're at the end, go to the start
		if (tabFocus >= tabs.length) {
			tabFocus = 0;
		}
		// Move Up
		} else if (e.key === "ArrowUp") {
		tabFocus--;
		// If we're at the start, move to the end
		if (tabFocus < 0) {
			tabFocus = tabs.length - 1;
		}
		}

		tabs[tabFocus].setAttribute("tabindex", 0);
		tabs[tabFocus].focus();
	}
	});

	document.querySelector('.toggle-pass-visibility').addEventListener('click', function(e) {
		e.preventDefault();
		const passInput = document.querySelector('#password');
		const classToReplace = passInput.getAttribute('type') == 'password' ? 'dashicons-hidden' : 'dashicons-visibility';
		const currentClass = passInput.getAttribute('type') == 'password' ? 'dashicons-visibility' : 'dashicons-hidden';
		passInput.getAttribute('type') == 'password' ? passInput.setAttribute('type', 'text') : passInput.setAttribute('type', 'password');
		e.currentTarget.firstChild.classList.replace(currentClass, classToReplace);

		if (input.getAttribute('type') == 'password') {
			input.setAttribute('type', 'text');
		} else {
			input.setAttribute('type', 'password');
		}
	});
});

function changeTabs(e) {
	e.preventDefault();
	const target = e.target;
	const parent = target.parentNode;
  	const grandparent = parent.parentNode;

  // Remove all current selected tabs
  parent
    .querySelectorAll('[aria-selected="true"]')
    .forEach((t) => t.setAttribute("aria-selected", false));

  // Set this tab as selected
  target.setAttribute("aria-selected", true);

  // Hide all tab panels
  grandparent
    .querySelectorAll('[role="tabpanel"]')
    .forEach((p) => p.setAttribute("hidden", true));

  // Show the selected panel
  grandparent.parentNode
    .querySelector(`#${target.getAttribute("aria-controls")}`)
    .removeAttribute("hidden");
}
</script>

<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");
