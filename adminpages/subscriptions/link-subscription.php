<?php
/**
 * Link a subscription in the admin.
 *
 * @since TBD
 */

// Link a subscription.
$subscription_transaction_id = ! empty( $_REQUEST['subscription_transaction_id'] ) ? sanitize_text_field( $_REQUEST['subscription_transaction_id'] ) : '';
$gateway                    = ! empty( $_REQUEST['gateway'] ) ? sanitize_text_field( $_REQUEST['gateway'] ) : get_option( 'pmpro_gateway', '' );
$gateway_environment        = ! empty( $_REQUEST['gateway_environment'] ) ? sanitize_text_field( $_REQUEST['gateway_environment'] ) : get_option( 'pmpro_gateway_environment', '' );
$user_id                    = ! empty( $_REQUEST['user_id'] ) ? sanitize_text_field( $_REQUEST['user_id'] ) : '';
$membership_level_id        = ! empty( $_REQUEST['membership_level_id'] ) ? sanitize_text_field( $_REQUEST['membership_level_id'] ) : '';
?>

<h1 class="wp-heading-inline"><?php esc_html_e( 'Link Subscription', 'paid-memberships-pro' ); ?></h1>

<form action="" method="post">
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
							<input id="user_id" name="user_id" type="text" size="10" value="<?php echo esc_attr( $user_id ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label for="membership_level_id"><?php esc_html_e( 'Membership Level', 'paid-memberships-pro' ); ?></label></th>
						<td>
							<?php
							// Get all membership levels.
							$levels = pmpro_getAllLevels( true, true );
							?>
							<select id="membership_level_id" name="membership_level_id">
								<?php
								foreach ( $levels as $level ) {
									?>
									<option value="<?php echo esc_attr( $level->id ); ?>" <?php selected( $level->id, $membership_level_id ); ?>><?php echo esc_html( $level->name ); ?></option>
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
						<th scope="row" valign="top"><label for="subscription_transaction_id"><?php esc_html_e( 'Subscription Transaction ID', 'paid-memberships-pro' ); ?></label></th>
						<td>
							<input id="subscription_transaction_id" name="subscription_transaction_id" type="text" size="50" value="<?php echo esc_attr( $subscription_transaction_id ); ?>" />
							<p class="description"><?php esc_html_e( 'The subscription ID from your payment gateway.', 'paid-memberships-pro' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label for="gateway"><?php esc_html_e( 'Gateway', 'paid-memberships-pro' ); ?></label></th>
						<td>
							<?php
							// Get all gateways.
							$gateways = pmpro_gateways();
							?>
							<select id="gateway" name="gateway">
								<?php
								foreach ( $gateways as $gateway_key => $gateway_label ) {
									?>
									<option value="<?php echo esc_attr( $gateway_key ); ?>" <?php selected( $gateway_key, $gateway ); ?>><?php echo esc_html( $gateway_label ); ?></option>
									<?php
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label for="gateway_environment"><?php esc_html_e( 'Gateway Environment', 'paid-memberships-pro' ); ?></label></th>
						<td>
							<select id="gateway_environment" name="gateway_environment">
								<option value="sandbox" <?php selected( 'sandbox', $gateway_environment ); ?>><?php esc_html_e( 'Sandbox/Testing', 'paid-memberships-pro' ); ?></option>
								<option value="live" <?php selected( 'live', $gateway_environment ); ?>><?php esc_html_e( 'Live/Production', 'paid-memberships-pro' ); ?></option>
							</select>
						</td>
					</tr>
				</tbody>
			</table>
		</div> <!-- end pmpro_section_inside -->
	</div> <!-- end pmpro_section -->

	<p class="submit">
		<?php wp_nonce_field( 'link', 'pmpro_subscriptions_nonce' ); ?>
		<input type="hidden" name="action" value="link" />
		<input type="submit" name="save" class="button button-primary" value="<?php esc_attr_e( 'Link Subscription', 'paid-memberships-pro' ); ?>" />
		<input type="button" name="cancel" class="button button-secondary" value="<?php esc_attr_e( 'Cancel', 'paid-memberships-pro' ); ?>" onclick="location.href='<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-subscriptions' ), admin_url( 'admin.php' ) ) ); ?>';" />
	</p>
</form>
