<?php 
/**
 * Template: Invoice
 * Version: 3.0
 *
 * See documentation for how to override the PMPro templates.
 * @link https://www.paidmembershipspro.com/documentation/templates/
 *
 * @version 3.0
 *
 * @author Paid Memberships Pro
 */
?>
<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_invoice_wrap' ) ); ?>">
	<?php
	global $wpdb, $pmpro_invoice, $pmpro_msg, $pmpro_msgt, $current_user;

	if($pmpro_msg)
	{
	?>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>"><?php echo wp_kses_post( $pmpro_msg );?></div>
	<?php
	}
?>

<?php
	if($pmpro_invoice)
	{
		?>
		<?php
			$pmpro_invoice->getUser();
			$pmpro_invoice->getMembershipLevel();
		?>
		<h2><?php echo esc_html( sprintf(__('Invoice #%s on %s', 'paid-memberships-pro' ), $pmpro_invoice->code, date_i18n(get_option('date_format'), $pmpro_invoice->getTimestamp())) );?></h2>
		<a class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_a-print' ) ); ?>" href="javascript:window.print()"><?php esc_html_e('Print', 'paid-memberships-pro' ); ?></a>
		<ul>
			<?php do_action("pmpro_invoice_bullets_top", $pmpro_invoice); ?>
			<li><strong><?php esc_html_e('Account', 'paid-memberships-pro' );?>:</strong> <?php echo esc_html( $pmpro_invoice->user->display_name ); ?> (<?php echo esc_html( $pmpro_invoice->user->user_email ); ?>)</li>
			<li><strong><?php esc_html_e('Membership Level', 'paid-memberships-pro' );?>:</strong> <?php echo esc_html( $pmpro_invoice->membership_level->name ); ?></li>
			<?php if ( ! empty( $pmpro_invoice->status ) ) { ?>
				<li><strong><?php esc_html_e('Status', 'paid-memberships-pro' ); ?>:</strong>
				<?php
					if ( in_array( $pmpro_invoice->status, array( '', 'success', 'cancelled' ) ) ) {
						$display_status = __( 'Paid', 'paid-memberships-pro' );
					} else {
						$display_status = ucwords( $pmpro_invoice->status );
					}
					echo esc_html( $display_status );
				?>
				</li>
			<?php } ?>
			<?php if($pmpro_invoice->getDiscountCode()) { ?>
				<li><strong><?php esc_html_e('Discount Code', 'paid-memberships-pro' );?>:</strong> <?php echo esc_html( $pmpro_invoice->discount_code->code ); ?></li>
			<?php } ?>
			<?php do_action("pmpro_invoice_bullets_bottom", $pmpro_invoice); ?>
		</ul>

		<?php
			// Check instructions
			if ( $pmpro_invoice->gateway == "check" && ! pmpro_isLevelFree( $pmpro_invoice->membership_level ) ) {
				echo '<div class="' . esc_attr( pmpro_get_element_class( 'pmpro_payment_instructions' ) ) . '">' . wp_kses_post( wpautop( wp_unslash( get_option( 'pmpro_instructions' ) ) ) ) . '</div>';
			}
		?>

		<hr />
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_invoice_details' ) ); ?>">
			<?php if(!empty($pmpro_invoice->billing->street)) { ?>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_invoice-billing-address' ) ); ?>">
					<strong><?php esc_html_e('Billing Address', 'paid-memberships-pro' );?></strong>
					<p>
						<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_invoice-field-billing_name' ) ); ?>"><?php echo esc_html( $pmpro_invoice->billing->name ); ?></span>
						<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_invoice-field-billing_street' ) ); ?>"><?php echo esc_html( $pmpro_invoice->billing->street ); ?></span>
						<?php if($pmpro_invoice->billing->city && $pmpro_invoice->billing->state) { ?>
							<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_invoice-field-billing_city' ) ); ?>"><?php echo esc_html( $pmpro_invoice->billing->city ); ?></span>
							<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_invoice-field-billing_state' ) ); ?>"><?php echo esc_html( $pmpro_invoice->billing->state ); ?></span>
							<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_invoice-field-billing_zip' ) ); ?>"><?php echo esc_html( $pmpro_invoice->billing->zip ); ?></span>
							<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_invoice-field-billing_country' ) ); ?>"><?php echo esc_html( $pmpro_invoice->billing->country ); ?></span>
						<?php } ?>
						<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_invoice-field-billing_phone' ) ); ?>"><?php echo esc_html( formatPhone($pmpro_invoice->billing->phone) ); ?></span>
					</p>
				</div> <!-- end pmpro_invoice-billing-address -->
			<?php } ?>

			<?php if ( ! empty( $pmpro_invoice->accountnumber ) || ! empty( $pmpro_invoice->payment_type ) ) { ?>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_invoice-payment-method' ) ); ?>">
					<strong><?php esc_html_e('Payment Method', 'paid-memberships-pro' );?></strong>
					<?php if($pmpro_invoice->accountnumber) { ?>
						<p><?php echo esc_html( ucwords( $pmpro_invoice->cardtype ) ); ?> <?php esc_html_e('ending in', 'paid-memberships-pro' );?> <?php echo esc_html( last4($pmpro_invoice->accountnumber) )?>
						<br />
						<?php esc_html_e('Expiration', 'paid-memberships-pro' );?>: <?php echo esc_html( $pmpro_invoice->expirationmonth ); ?>/<?php echo esc_html( $pmpro_invoice->expirationyear ); ?></p>
					<?php } else { 
						if ( $pmpro_invoice->payment_type === 'Check' && ! empty( get_option( 'pmpro_check_gateway_label' ) ) ) {
							$pmpro_invoice->payment_type = get_option( 'pmpro_check_gateway_label' );
						}
						?>
						<p><?php echo esc_html( $pmpro_invoice->payment_type ); ?></p>
					<?php } ?>
				</div> <!-- end pmpro_invoice-payment-method -->
			<?php } ?>

			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_invoice-total' ) ); ?>">
				<strong><?php esc_html_e('Total Billed', 'paid-memberships-pro' );?></strong>
				<p>
					<?php
						if ( (float)$pmpro_invoice->total > 0 ) {
							//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo pmpro_escape_price( pmpro_get_price_parts( $pmpro_invoice, 'span' ) );
						} else {
							//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo pmpro_escape_price( pmpro_formatPrice(0) );
						}
					?>
				</p>
			</div> <!-- end pmpro_invoice-total -->
		</div> <!-- end pmpro_invoice_details -->
		<hr />
		<?php
	}
	else
	{
		//Show all invoices for user if no invoice ID is passed
		$invoices = $wpdb->get_results("SELECT o.*, UNIX_TIMESTAMP(CONVERT_TZ(o.timestamp, '+00:00', @@global.time_zone)) as timestamp, l.name as membership_level_name FROM $wpdb->pmpro_membership_orders o LEFT JOIN $wpdb->pmpro_membership_levels l ON o.membership_id = l.id WHERE o.user_id = '$current_user->ID' AND o.status NOT IN('review', 'token', 'error') ORDER BY timestamp DESC");
		if($invoices)
		{
			?>
			<table id="pmpro_invoices_table" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table pmpro_invoice', 'pmpro_invoices_table' ) ); ?>" width="100%" cellpadding="0" cellspacing="0" border="0">
			<thead>
				<tr>
					<th><?php esc_html_e('Date', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e('Invoice #', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e('Level', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e('Total Billed', 'paid-memberships-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
				foreach($invoices as $invoice)
				{
					?>
					<tr>
						<td><a href="<?php echo esc_url( pmpro_url("invoice", "?invoice=" . $invoice->code ) ) ?>"><?php echo esc_html( date_i18n( get_option("date_format"), strtotime( get_date_from_gmt( date( 'Y-m-d H:i:s', $invoice->timestamp ) ) ) ) )?></a></td>
						<td><a href="<?php echo esc_url( pmpro_url("invoice", "?invoice=" . $invoice->code ) ) ?>"><?php echo esc_html( $invoice->code ); ?></a></td>
						<td><?php echo esc_html( $invoice->membership_level_name );?></td>
						<td><?php echo pmpro_escape_price( pmpro_formatPrice($invoice->total) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped?></td>
					</tr>
					<?php
				}
			?>
			</tbody>
			</table>
			<?php
		}
		else
		{
			?>
			<p><?php esc_html_e('No invoices found.', 'paid-memberships-pro' );?></p>
			<?php
		}
	}
?>
<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav' ) ); ?>">
	<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav-right' ) ); ?>"><a href="<?php echo esc_url( pmpro_url( "account" ) ) ?>"><?php esc_html_e('View Your Membership Account &rarr;', 'paid-memberships-pro' );?></a></span>
	<?php if ( $pmpro_invoice ) { ?>
		<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav-left' ) ); ?>"><a href="<?php echo esc_url( pmpro_url( "invoice" ) ) ?>"><?php esc_html_e('&larr; View All Invoices', 'paid-memberships-pro' );?></a></span>
	<?php } ?>
</div> <!-- end pmpro_actions_nav -->
</div> <!-- end pmpro_invoice_wrap -->
