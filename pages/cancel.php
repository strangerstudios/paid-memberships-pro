<?php
/**
 * Template: Cancel
 * Version: 3.0
 *
 * See documentation for how to override the PMPro templates.
 * @link https://www.paidmembershipspro.com/documentation/templates/
 *
 * @version 3.0
 *
 * @author Paid Memberships Pro
 */
global $pmpro_msg, $pmpro_msgt, $current_user, $wpdb;

if(isset($_REQUEST['levelstocancel']) && $_REQUEST['levelstocancel'] !== 'all') {
	// Odd input format here (1+2+3). These values are sanitized.
	// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	//convert spaces back to +
	$_REQUEST['levelstocancel'] = str_replace(array(' ', '%20'), '+', $_REQUEST['levelstocancel']);

	//get the ids
	$old_level_ids = array_map('intval', explode("+", preg_replace("/[^0-9al\+]/", "", $_REQUEST['levelstocancel'])));
	// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
} elseif(isset($_REQUEST['levelstocancel']) && $_REQUEST['levelstocancel'] == 'all') {
	$old_level_ids = 'all';
} else {
	$old_level_ids = false;
}

$user_levels = pmpro_getMembershipLevelsForUser( $current_user->ID );
?>
<div id="pmpro_cancel" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_cancel_wrap', 'pmpro_cancel' ) ); ?>">
	<?php
		if($pmpro_msg)
		{
			?>
			<div role="alert" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>"><?php echo wp_kses_post( $pmpro_msg );?></div>
			<?php
		}
	?>
	<?php
		if ( empty( $_REQUEST['confirm'] ) ) {
			if($old_level_ids) {
				?>
				<form id="pmpro_form" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form' ) ); ?>" action="<?php echo esc_url( pmpro_url( 'cancel', '', 'https') ) ?>" method="post">
					<p>
						<?php
						if ( is_string( $old_level_ids ) && $old_level_ids == 'all' ) {
							esc_html_e( 'Are you sure you want to cancel your membership?', 'paid-memberships-pro' );
						} else {
							$level_names = $wpdb->get_col( "SELECT name FROM $wpdb->pmpro_membership_levels WHERE id IN('" . implode( "','", array_map( 'intval', $old_level_ids ) ) . "')" );
							echo esc_html( sprintf( _n( 'Are you sure you want to cancel your %s membership?', 'Are you sure you want to cancel your %s memberships?', count( $level_names ), 'paid-memberships-pro' ), pmpro_implodeToEnglish( $level_names ) ) );
						}
						?>
					</p>

					<?php
					/**
					 * Hook to add additional content to the cancel page.
					 *
					 * @since 3.0
					 *
					 * @param WP_User $user The user cancelling their membership.
					 * @param array|string   $old_level_ids The level IDs being cancelled or 'all'.
					 */
					do_action( 'pmpro_cancel_before_submit', $current_user, $old_level_ids );
					?>

					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actionlinks' ) ); ?>">
						<?php
						if ( is_string( $old_level_ids ) && $old_level_ids == 'all' ) {
							$cancel_memberships_text = __( 'Yes, cancel all of my memberships', 'paid-memberships-pro' );
							$keep_memberships_text = __( 'No, keep my memberships', 'paid-memberships-pro' );
						} elseif ( count( $old_level_ids ) > 1 ) {
							$cancel_memberships_text = __( 'Yes, cancel these memberships', 'paid-memberships-pro' );
							$keep_memberships_text = __( 'No, keep these memberships', 'paid-memberships-pro' );
						} else {
							$cancel_memberships_text = __( 'Yes, cancel this membership', 'paid-memberships-pro' );
							$keep_memberships_text = __( 'No, keep this membership', 'paid-memberships-pro' );
						}
						?>
						<input type="hidden" name="levelstocancel" value="<?php echo esc_attr( $_REQUEST['levelstocancel'] ); ?>" />
						<input type="hidden" name="confirm" value="1" />
						<?php wp_nonce_field( 'pmpro_cancel-nonce', 'pmpro_cancel-nonce' ); ?>
						<input type="submit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit pmpro_yeslink yeslink', 'pmpro_btn-submit' ) ); ?>" value="<?php echo esc_attr( $cancel_memberships_text ); ?>" />
						<a class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-cancel pmpro_nolink nolink', 'pmpro_btn-cancel' ) ); ?>" href="<?php echo esc_url( pmpro_url( "account" ) ) ?>"><?php echo esc_html( $keep_memberships_text ); ?></a>
					</div>
				</form>
				<?php
			}
			else
			{
				if ( ! empty( $user_levels ) ) {
					?>
					<h2><?php esc_html_e("My Memberships", 'paid-memberships-pro' );?></h2>
					<table class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table' ) ); ?>" width="100%" cellpadding="0" cellspacing="0" border="0">
						<thead>
							<tr>
								<th><?php esc_html_e("Level", 'paid-memberships-pro' );?></th>
								<th><?php esc_html_e("Expiration", 'paid-memberships-pro' ); ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php
								foreach ( $user_levels as $level ) {
								?>
								<tr>
									<th class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_cancel-membership-levelname' ) ); ?>">
										<?php echo esc_html( $level->name );?>
									</th>
									<td class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_cancel-membership-expiration' ) ); ?>">
									<?php
										echo wp_kses_post( pmpro_get_membership_expiration_text( $level, $current_user ) );
									?>
									</td>
									<td class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_cancel-membership-cancel' ) ); ?>">
										<a href="<?php echo esc_url( pmpro_url( "cancel", "?levelstocancel=" . $level->id ) ) ?>"><?php esc_html_e("Cancel", 'paid-memberships-pro' );?></a>
									</td>
								</tr>
								<?php
								}
							?>
						</tbody>
					</table>
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav' ) ); ?>">
						<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav-right' ) ); ?>"><a href="<?php echo esc_url( pmpro_url( "account" ) ) ?>"><?php esc_html_e('View Your Membership Account &rarr;', 'paid-memberships-pro' );?></a></span>
						<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav-left' ) ); ?>"><a href="<?php echo esc_url( pmpro_url( "cancel", "?levelstocancel=all" ) ); ?>"><?php esc_html_e("Cancel All Memberships", 'paid-memberships-pro' );?></a></span>
					</div>
					<?php
				}
			}
		}
		else
		{
			?>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav' ) ); ?>">
			<?php
				if ( ! pmpro_getMembershipLevelsForUser() ) {
					// The user cancelled all of their membership levels. Send them to the home page.
					?>
					<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_cancel_return_home pmpro_actions_nav-right', 'pmpro_cancel_return_home' ) ); ?>"><a href="<?php echo esc_url( get_home_url() )?>"><?php esc_html_e( 'View the Homepage &rarr;', 'paid-memberships-pro' ); ?></a></span>
					<?php
				} else {
					// The user still has some membership levels. Send them to the account page.
					?>
					<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav-right' ) ); ?>"><a href="<?php echo esc_url( pmpro_url( "account" ) ) ?>"><?php esc_html_e('View Your Membership Account &rarr;', 'paid-memberships-pro' );?></a></span>
					<?php
				}
			?>
			</div>
			<?php
		}
	?>
</div> <!-- end pmpro_cancel, pmpro_cancel_wrap -->
