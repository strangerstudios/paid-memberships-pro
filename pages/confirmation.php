<?php
/**
 * Template: Confirmation
 * Version: 2.0
 *
 * See documentation for how to override the PMPro templates.
 * @link https://www.paidmembershipspro.com/documentation/templates/
 *
 * @version 2.0
 *
 * @author Paid Memberships Pro
 */
global $wpdb, $current_user, $pmpro_invoice, $pmpro_msg, $pmpro_msgt;
?>
<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_confirmation_wrap' ) ); ?>">
	<?php
	// Show message if it was passed in.
	if ( $pmpro_msg ) {
		?>
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>"><?php echo wp_kses_post( $pmpro_msg );?></div>
		<?php
	}

	// Start building the confirmation message.
	if ( empty( $current_user->membership_level ) ) {
		$confirmation_message = '<p>' . __('Your payment has been submitted. Your membership will be activated shortly.', 'paid-memberships-pro' ) . '</p>';
	} else {
		$confirmation_message = '<p>' . sprintf(__('Thank you for your membership to %s. Your %s membership is now active.', 'paid-memberships-pro' ), get_bloginfo("name"), $current_user->membership_level->name) . '</p>';
	}

	// Add the level confirmation message if set.
	$level_message = $wpdb->get_var("SELECT l.confirmation FROM $wpdb->pmpro_membership_levels l LEFT JOIN $wpdb->pmpro_memberships_users mu ON l.id = mu.membership_id WHERE mu.status = 'active' AND mu.user_id = '" . intval( $current_user->ID ) . "' LIMIT 1");
	if ( ! empty( $level_message ) ) {
		$confirmation_message .= wpautop( stripslashes( $level_message ) );
	}

	// Get the invoice if we have one.
	$confirmation_invoice = ( ! empty( $pmpro_invoice ) && ! empty( $pmpro_invoice->id ) ) ? $pmpro_invoice : false;

	// Add some details to the confirmation message about the invoice.
	if ( ! empty( $confirmation_invoice ) ) {
		$confirmation_invoice->getUser();
		$confirmation_invoice->getMembershipLevel();
		$confirmation_message .= '<p>' . sprintf( __( 'Below are details about your membership account and a receipt for your initial membership invoice. A welcome email with a copy of your initial membership invoice has been sent to %s.', 'paid-memberships-pro' ), $current_user->user_email ) . '</p>';
	} else {
		$confirmation_message .= '<p>' . sprintf( __( 'Below are details about your membership account. A welcome email has been sent to %s.', 'paid-memberships-pro' ), $current_user->user_email ) . '</p>';
	}

	/**
	 * Allow devs to filter the confirmation message.
	 * We also have a function in includes/filters.php that applies the the_content filters to this message.
	 * @param string $confirmation_message The confirmation message.
	 * @param object $pmpro_invoice The PMPro Invoice/Order object.
	 */
	$confirmation_message = apply_filters("pmpro_confirmation_message", $confirmation_message, $pmpro_invoice);
	echo wp_kses_post( $confirmation_message );

	if ( ! empty( $confirmation_invoice ) ) {
		// Show the invoice, but make sure we don't show $pmpro_msg again.
		$pmpro_msg = false;
		$pmpro_msgt = false;
		echo pmpro_loadTemplate( 'invoice' );
	} else {
		?>
		<ul>
			<li><strong><?php esc_html_e('Account', 'paid-memberships-pro' );?>:</strong> <?php echo esc_html( $current_user->display_name );?> (<?php echo esc_html( $current_user->user_email );?>)</li>
			<li><strong><?php esc_html_e('Membership Level', 'paid-memberships-pro' );?>:</strong> <?php if(!empty($current_user->membership_level)) echo esc_html( $current_user->membership_level->name ); else esc_html_e("Pending", 'paid-memberships-pro' );?></li>
			<?php if( !empty( $current_user->membership_level->expiration_period ) && $current_user->membership_level->expiration_period == 'Hour' && apply_filters( 'pmpro_confirmation_display_hour_expiration', true, $current_user ) ){ ?>
			<li><strong><?php esc_html_e('Expires In', 'paid-memberships-pro' );?>:</strong> <?php echo esc_html( $current_user->membership_level->expiration_number . ' ' . pmpro_translate_billing_period( $current_user->membership_level->expiration_period, $current_user->membership_level->expiration_number ) ); ?></li>
			<?php }
			?>
		</ul>
		<?php
	}

	?>
	<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav' ) ); ?>">
		<?php
		if ( ! empty( $current_user->membership_level ) ) {
			if ( empty( $confirmation_invoice ) ) { // The invoice already shows a link to the account page.
				?>
				<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav-right' ) ); ?>"><a href="<?php echo esc_url( pmpro_url( 'account' ) ); ?>"><?php esc_html_e( 'View Your Membership Account &rarr;', 'paid-memberships-pro' ); ?></a></span>
				<?php
			}
		} else {
			esc_html_e( 'If your account is not activated within a few minutes, please contact the site owner.', 'paid-memberships-pro' );
		}
		?>
	</p> <!-- end pmpro_actions_nav -->
</div> <!-- end pmpro_confirmation_wrap -->
