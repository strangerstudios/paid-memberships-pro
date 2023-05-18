<?php
/*
	Upgrade to 2.10.6
    
    A number of misconfigured sites might have stored sensitive payment data
    in the pmpro_membership_ordermeta table.

	1. Find all rows in wp_pmpro_membership_ordermeta where unscrubbed AccountNumbers show up.
	2. Loop through and scrub the AccountNumbers.
*/

/**
 * Show admin notice if site was affected.
 */
function pmpro_upgrade_2_10_6_notice() {
	// Check if we need to show the notice.
	if ( ! get_option( 'pmpro_upgrade_2_10_6_notice' ) ) {
		return;
	}

	// Only show on PMPro admin pages.
	if ( empty( $_REQUEST['page'] ) || strpos( $_REQUEST['page'], 'pmpro' ) === false ) {
		return;
	}

	// Check if the user has dismissed the notice.
	if ( ! empty( $_REQUEST['pmpro-hide-upgrade_2_10_6-notice'] ) ) {
		delete_option( 'pmpro_upgrade_2_10_6_notice' );
		return;
	}

	// Show a dismissable notice. Do not show a link to scrub data.
	?>
	<div class="notice notice-warning" id="pmpro-upgrade-2-10-6-notice">
		<p>
			<?php
			printf(
				wp_kses_post(
					__( 'Paid Memberships Pro has detected potentially sensitive customer information in the PMPro order meta table. This information will be safely removed from your database. For more information, read our <a href="%s">post here</a>.', 'paid-memberships-pro' )
				),
				esc_url( 'https://www.paidmembershipspro.com/pmpro-update-2-10-6/' )
			);
			?>
		</p>
		<p>
			<a href="<?php echo esc_url( add_query_arg( 'pmpro-hide-upgrade_2_10_6-notice', '1' ) ); ?>"><?php esc_html_e( 'Dismiss this notice.', 'paid-memberships-pro' ); ?></a>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'pmpro_upgrade_2_10_6_notice' );

// Only load these functions if we need to call them.
if ( $pmpro_db_version >= 2.95 ) {
	return;
}

function pmpro_upgrade_2_10_6() {
	global $wpdb;
	$sqlQuery = "SELECT pmpro_membership_order_id
				FROM $wpdb->pmpro_membership_ordermeta
				WHERE meta_key = 'checkout_request_vars'
					AND (
						( meta_value LIKE '%:\"AccountNumber\";%' AND meta_value NOT LIKE '%:\"AccountNumber\";s:16:\"XXXXXXXXXXXX%' )
						OR ( meta_value LIKE '%:\"CVV\";%' )
						OR ( meta_value LIKE '%:\"add_sub_accounts_password\";%' )
					)
				ORDER BY meta_id";
	$order_ids = $wpdb->get_col( $sqlQuery );

	if(!empty($order_ids)) {
		// Set an option so that we know to show the admin a notice that they may have been affected.
		update_option( 'pmpro_upgrade_2_10_6_notice', true );

		if(count($order_ids) > 10) {
			//if more than 10 orders, we'll need to do this via AJAX
			pmpro_addUpdate( 'pmpro_upgrade_2_10_6_ajax' );
		} else {
			//less than 10, let's just do them now
			pmpro_upgrade_2_10_6_helper_scrub_account_numbers_in_orders( $order_ids, false );
		}
	}
	pmpro_setOption( 'db_version', '2.106' );
	return 2.106;
}

/*
	If a site has > 100 orders then we run this pasrt of the update via AJAX from the updates page.
*/
function pmpro_upgrade_2_10_6_ajax() {
	global $wpdb;

	//keeping track of which order we're working on
	$last_order_id = get_option( 'pmpro_upgrade_2_10_6_last_order_id', 0 );
	
	//get orders
	$sqlQuery = "SELECT pmpro_membership_order_id
				FROM $wpdb->pmpro_membership_ordermeta
				WHERE meta_key = 'checkout_request_vars'
					AND (
						( meta_value LIKE '%:\"AccountNumber\";%' AND meta_value NOT LIKE '%:\"AccountNumber\";s:16:\"XXXXXXXXXXXX%' )
						OR ( meta_value LIKE '%:\"CVV\";%' )
						OR ( meta_value LIKE '%:\"add_sub_accounts_password\";%' )
					)
				ORDER BY meta_id";
	$order_ids = $wpdb->get_col( $sqlQuery );

	if(empty($order_ids)) {
		//done with this update
		pmpro_removeUpdate('pmpro_upgrade_2_10_6_ajax');
		delete_option( 'pmpro_upgrade_2_10_6_last_order_id' );
	} else {
		pmpro_upgrade_2_10_6_helper_scrub_account_numbers_in_orders( $order_ids, true );
	}
}

/**
 * Scrub AccountNumbers and other sensitive data from ordermeta.
 *
 * @param array(int) $order_ids to scrub.
 * @param bool $update_last_order_id. Should be true if updating via ajax.
 */
function pmpro_upgrade_2_10_6_helper_scrub_account_numbers_in_orders( $order_ids, $update_last_order_id ) {
	global $wpdb;
	
	require_once( ABSPATH . "/wp-includes/pluggable.php" );
	
    foreach( $order_ids as $order_id ) {
		$request_vars = get_pmpro_membership_order_meta( $order_id, 'checkout_request_vars', true );
        
        // Skip if we didn't get an array.
        if ( ! is_array( $request_vars ) ) {
            continue;
        }

        // Unset sensitive data.
        if ( ! empty( $request_vars['AccountNumber'] ) ) {
            unset( $request_vars['AccountNumber'] );
        }
        if ( ! empty( $request_vars['CVV'] ) ) {
            unset( $request_vars['CVV'] );
        }
		if ( ! empty( $request_vars['add_sub_accounts_password'] ) ) {
			unset( $request_vars['add_sub_accounts_password'] );
		}

        // Save updated values.
        update_pmpro_membership_order_meta( $order_id, 'checkout_request_vars', $request_vars );
	}

	if ( $update_last_order_id ) {
		update_option( 'pmpro_upgrade_2_10_6_last_order_id', $last_order_id );
	}
}
