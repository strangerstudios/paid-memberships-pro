<?php
/**
 * Standalone Levels and Guest Checkout functionality for PMPro.
 *
 * Standalone levels allow one-time purchases that don't assign a membership.
 * Guest checkout allows completing a standalone checkout without an account.
 * Both are enabled per-level via level meta flags on the edit level page.
 *
 * @since TBD
 */

/*
 * ----------------------------------------
 * Standalone Level Hooks
 * ----------------------------------------
 */

/**
 * Skip the membership level change for standalone levels.
 *
 * @since TBD
 *
 * @param bool        $skip         Whether to skip the level change.
 * @param MemberOrder $order        The order being processed.
 * @param array       $custom_level The level data that would be assigned.
 * @return bool
 */
function pmpro_standalone_skip_level_change( $skip, $order, $custom_level ) {
	if ( ! empty( $custom_level['membership_id'] ) && get_pmpro_membership_level_meta( $custom_level['membership_id'], 'is_standalone', true ) ) {
		return true;
	}
	return $skip;
}
add_filter( 'pmpro_checkout_skip_level_change', 'pmpro_standalone_skip_level_change', 10, 3 );

/**
 * Validate standalone level checkout restrictions.
 *
 * Standalone levels only support one-time payments — no recurring
 * billing and no expirations.
 *
 * @since TBD
 *
 * @param bool $checks Whether the checkout should continue.
 * @return bool
 */
function pmpro_standalone_checkout_checks( $checks ) {
	global $pmpro_level;

	if ( empty( $pmpro_level ) || empty( $pmpro_level->id ) ) {
		return $checks;
	}

	if ( ! get_pmpro_membership_level_meta( $pmpro_level->id, 'is_standalone', true ) ) {
		return $checks;
	}

	if ( pmpro_isLevelRecurring( $pmpro_level ) ) {
		pmpro_setMessage( __( 'Recurring payments are not supported for standalone levels.', 'paid-memberships-pro' ), 'pmpro_error' );
		return false;
	}

	if ( pmpro_isLevelExpiring( $pmpro_level ) ) {
		pmpro_setMessage( __( 'Expirations are not supported for standalone levels.', 'paid-memberships-pro' ), 'pmpro_error' );
		return false;
	}

	return $checks;
}
add_filter( 'pmpro_checkout_checks', 'pmpro_standalone_checkout_checks' );

/**
 * Add standalone note to level cost text in the admin levels list.
 *
 * @since TBD
 *
 * @param string $cost  The level cost text.
 * @param object $level The level object.
 * @return string
 */
function pmpro_standalone_level_cost_text( $cost, $level ) {
	if ( empty( $level->id ) || ! is_admin() || empty( $_REQUEST['page'] ) || $_REQUEST['page'] !== 'pmpro-membershiplevels' ) {
		return $cost;
	}
	if ( get_pmpro_membership_level_meta( $level->id, 'is_standalone', true ) ) {
		$cost .= '<br /><em>' . esc_html__( 'Standalone level. No membership assigned.', 'paid-memberships-pro' ) . '</em>';
	}
	return $cost;
}
add_filter( 'pmpro_level_cost_text', 'pmpro_standalone_level_cost_text', 20, 2 );

/**
 * Replace the checkout level name text for standalone levels.
 *
 * Replaces "You have selected the X membership level" and any level
 * replacement warnings with a standalone-appropriate message.
 *
 * @since TBD
 *
 * @param string $text  The level name and change text.
 * @param object $level The level being purchased.
 * @return string
 */
function pmpro_standalone_checkout_level_name_text( $text, $level ) {
	if ( empty( $level->id ) || ! get_pmpro_membership_level_meta( $level->id, 'is_standalone', true ) ) {
		return $text;
	}

	return sprintf(
		esc_html__( 'You are purchasing %s. This is a one-time purchase and will not affect your membership.', 'paid-memberships-pro' ),
		'<strong>' . esc_html( $level->name ) . '</strong>'
	);
}
add_filter( 'pmpro_checkout_level_name_text', 'pmpro_standalone_checkout_level_name_text', 10, 2 );


/*
 * ----------------------------------------
 * Admin: Edit Level Settings
 * ----------------------------------------
 */

/**
 * Add standalone level settings to the edit level page.
 *
 * @since TBD
 *
 * @param object $level The membership level object.
 */
function pmpro_standalone_level_settings( $level ) {
	$is_standalone = get_pmpro_membership_level_meta( $level->id, 'is_standalone', true );
	$allow_guest_checkout = get_pmpro_membership_level_meta( $level->id, 'allow_guest_checkout', true );
	?>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row" valign="top"><label for="is_standalone"><?php esc_html_e( 'Standalone Level', 'paid-memberships-pro' ); ?></label></th>
				<td>
					<input id="is_standalone" name="is_standalone" type="checkbox" value="1" <?php checked( $is_standalone, 1 ); ?> />
					<label for="is_standalone"><?php esc_html_e( 'Make this a standalone level. No membership will be assigned after purchase, existing memberships will not be cancelled, and this level can always be repurchased.', 'paid-memberships-pro' ); ?></label>
				</td>
			</tr>
			<tr id="allow_guest_checkout_tr" <?php if ( empty( $is_standalone ) ) { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top"><label for="allow_guest_checkout"><?php esc_html_e( 'Allow Guest Checkout', 'paid-memberships-pro' ); ?></label></th>
				<td>
					<input id="allow_guest_checkout" name="allow_guest_checkout" type="checkbox" value="1" <?php checked( $allow_guest_checkout, 1 ); ?> />
					<label for="allow_guest_checkout"><?php esc_html_e( 'Allow users to check out without creating an account.', 'paid-memberships-pro' ); ?></label>
				</td>
			</tr>
		</tbody>
	</table>
	<script>
		jQuery(document).ready(function($) {
			$('#is_standalone').on('change', function() {
				if ($(this).is(':checked')) {
					$('#allow_guest_checkout_tr').show();
				} else {
					$('#allow_guest_checkout_tr').hide();
					$('#allow_guest_checkout').prop('checked', false);
				}
			});
		});
	</script>
	<?php
}
add_action( 'pmpro_membership_level_after_other_settings', 'pmpro_standalone_level_settings' );

/**
 * Save standalone level settings when a level is saved.
 *
 * @since TBD
 *
 * @param int $level_id The level ID being saved.
 */
function pmpro_standalone_save_level_settings( $level_id ) {
	update_pmpro_membership_level_meta( $level_id, 'is_standalone', ! empty( $_REQUEST['is_standalone'] ) ? 1 : 0 );
	update_pmpro_membership_level_meta( $level_id, 'allow_guest_checkout', ! empty( $_REQUEST['allow_guest_checkout'] ) ? 1 : 0 );
}
add_action( 'pmpro_save_membership_level', 'pmpro_standalone_save_level_settings' );

/*
 * ----------------------------------------
 * Guest Checkout Helpers
 * ----------------------------------------
 */

/**
 * Check if the current checkout is a guest checkout.
 *
 * Requires: the guest checkout checkbox is checked, the user is not
 * logged in, and the level allows guest checkout.
 *
 * @since TBD
 *
 * @return bool Whether the current checkout is a guest checkout.
 */
function pmpro_is_guest_checkout() {
	if ( empty( $_REQUEST['pmpro_guest_checkout'] ) ) {
		return false;
	}

	if ( is_user_logged_in() ) {
		return false;
	}

	$level = pmpro_getLevelAtCheckout();
	if ( empty( $level ) || empty( $level->id ) ) {
		return false;
	}

	if ( ! get_pmpro_membership_level_meta( $level->id, 'allow_guest_checkout', true ) ) {
		return false;
	}

	return true;
}

/**
 * Check if an order was a guest checkout.
 *
 * @since TBD
 *
 * @param MemberOrder $order The order to check.
 * @return bool Whether the order was a guest checkout.
 */
function pmpro_order_is_guest_checkout( $order ) {
	if ( empty( $order->id ) ) {
		return false;
	}
	return ! empty( get_pmpro_membership_order_meta( $order->id, 'guest_email', true ) );
}

/*
 * ----------------------------------------
 * Guest Checkout Flow Hooks
 * ----------------------------------------
 */

/**
 * Skip user creation (and validation) for guest checkouts.
 *
 * @since TBD
 *
 * @param bool   $skip  Whether to skip user creation.
 * @param object $level The level being purchased.
 * @return bool
 */
function pmpro_guest_checkout_skip_user_creation( $skip, $level ) {
	if ( pmpro_is_guest_checkout() ) {
		return true;
	}
	return $skip;
}
add_filter( 'pmpro_skip_user_creation', 'pmpro_guest_checkout_skip_user_creation', 10, 2 );

/**
 * Save guest checkout data to order meta after checkout.
 *
 * @since TBD
 *
 * @param int         $user_id The user ID (0 for guests).
 * @param MemberOrder $order   The order that was created.
 */
function pmpro_guest_checkout_after_checkout( $user_id, $order ) {
	if ( ! pmpro_is_guest_checkout() ) {
		return;
	}

	$guest_email = ! empty( $_REQUEST['bemail'] ) ? sanitize_email( $_REQUEST['bemail'] ) : '';
	update_pmpro_membership_order_meta( $order->id, 'guest_email', $guest_email );
}
add_action( 'pmpro_after_checkout', 'pmpro_guest_checkout_after_checkout', 5, 2 );

/**
 * Provide a WP_User object for guest checkout emails.
 *
 * When there's no WordPress user (user_id=0), build a WP_User
 * from the checkout form data so the standard email flow works.
 *
 * @since TBD
 *
 * @param WP_User|false $user  The user object, or false if not found.
 * @param MemberOrder   $order The order being processed.
 * @return WP_User|false
 */
function pmpro_guest_checkout_email_user( $user, $order ) {
	if ( ! empty( $user ) ) {
		return $user;
	}

	if ( ! pmpro_is_guest_checkout() ) {
		return $user;
	}

	$user               = new WP_User();
	$user->user_email   = ! empty( $_REQUEST['bemail'] ) ? sanitize_email( $_REQUEST['bemail'] ) : '';
	$user->display_name = ! empty( $order->billing->name ) ? $order->billing->name : '';
	$user->user_login   = '';

	return $user;
}
add_filter( 'pmpro_checkout_email_user', 'pmpro_guest_checkout_email_user', 10, 2 );

/**
 * Redirect standalone checkouts to the invoice page instead of the confirmation page.
 *
 * The confirmation page expects the user to have the purchased level active.
 * Standalone levels skip level assignment, so we redirect to the invoice page.
 * For guests, the email is included in the URL for access.
 *
 * @since TBD
 *
 * @param string $url     The confirmation URL.
 * @param int    $user_id The user ID (0 for guests).
 * @param object $level   The membership level.
 * @return string The redirect URL.
 */
function pmpro_standalone_confirmation_url( $url, $user_id, $level ) {
	if ( empty( $level->id ) || ! get_pmpro_membership_level_meta( $level->id, 'is_standalone', true ) ) {
		return $url;
	}

	global $pmpro_review;
	if ( empty( $pmpro_review ) || empty( $pmpro_review->code ) ) {
		return $url;
	}

	$args = array( 'invoice' => $pmpro_review->code );

	// For guest checkouts, include the email for invoice access.
	if ( empty( $user_id ) ) {
		$guest_email = ! empty( $_REQUEST['bemail'] ) ? sanitize_email( $_REQUEST['bemail'] ) : '';
		$args['email'] = rawurlencode( $guest_email );
	}

	return add_query_arg( $args, pmpro_url( 'invoice' ) );
}
add_filter( 'pmpro_confirmation_url', 'pmpro_standalone_confirmation_url', 10, 3 );

/**
 * Allow guests to view their own guest orders on the invoice page.
 *
 * Validates both the order code and the guest email to prevent enumeration.
 *
 * @since TBD
 *
 * @param bool             $can_view Whether the visitor can view the order.
 * @param MemberOrder|null $order    The order being viewed.
 * @return bool
 */
function pmpro_guest_checkout_allow_viewing_order( $can_view, $order ) {
	if ( $can_view ) {
		return $can_view;
	}

	if ( empty( $order ) || ! pmpro_order_is_guest_checkout( $order ) ) {
		return $can_view;
	}

	$provided_email = ! empty( $_REQUEST['email'] ) ? sanitize_email( $_REQUEST['email'] ) : '';
	$guest_email    = get_pmpro_membership_order_meta( $order->id, 'guest_email', true );

	if ( empty( $provided_email ) || empty( $guest_email ) ) {
		return false;
	}

	if ( strtolower( $provided_email ) !== strtolower( $guest_email ) ) {
		return false;
	}

	// Set a guest user stub on the order so the invoice template can render.
	$order->user                  = new stdClass();
	$order->user->ID              = 0;
	$order->user->display_name    = ! empty( $order->billing->name ) ? $order->billing->name : __( 'Guest', 'paid-memberships-pro' );
	$order->user->user_email      = $guest_email;
	$order->user->user_login      = '';
	$order->user->user_registered = '';

	return true;
}
add_filter( 'pmpro_allow_viewing_order', 'pmpro_guest_checkout_allow_viewing_order', 10, 2 );

/*
 * ----------------------------------------
 * Admin Display
 * ----------------------------------------
 */

/**
 * Show guest checkout info after the user column in the orders list.
 *
 * @since TBD
 *
 * @param MemberOrder $item The order being displayed.
 */
function pmpro_guest_checkout_orders_column_after_user( $item ) {
	if ( ! pmpro_order_is_guest_checkout( $item ) ) {
		return;
	}

	$guest_email = get_pmpro_membership_order_meta( $item->id, 'guest_email', true );
	if ( empty( $guest_email ) ) {
		return;
	}

	echo '<br /><em>' . esc_html__( 'Guest Checkout', 'paid-memberships-pro' ) . '</em>';
	echo '<br />' . esc_html( $guest_email );
}
add_action( 'pmpro_orders_column_after_user', 'pmpro_guest_checkout_orders_column_after_user' );

/**
 * Show guest checkout info after the member info in the order view sidebar.
 *
 * @since TBD
 *
 * @param MemberOrder $order The order being viewed.
 */
function pmpro_guest_checkout_order_view_after_member_info( $order ) {
	if ( ! pmpro_order_is_guest_checkout( $order ) ) {
		return;
	}

	$guest_email = get_pmpro_membership_order_meta( $order->id, 'guest_email', true );
	if ( empty( $guest_email ) ) {
		return;
	}
	?>
	<p>
		<strong><?php esc_html_e( 'Guest Checkout', 'paid-memberships-pro' ); ?></strong><br />
		<?php echo esc_html( $guest_email ); ?>
	</p>
	<?php
}
add_action( 'pmpro_order_view_after_member_info', 'pmpro_guest_checkout_order_view_after_member_info' );

/*
 * ----------------------------------------
 * Checkout Page UI
 * ----------------------------------------
 */

/**
 * Add guest checkout checkbox to the checkout page.
 *
 * Shows a "Check out as a guest" checkbox when the level allows
 * guest checkout and the user is not logged in.
 *
 * @since TBD
 */
function pmpro_guest_checkout_add_checkout_toggle() {
	global $pmpro_level;

	// Only show for logged-out users on levels that allow guest checkout.
	if ( is_user_logged_in() || empty( $pmpro_level ) || empty( $pmpro_level->id ) ) {
		return;
	}

	if ( ! get_pmpro_membership_level_meta( $pmpro_level->id, 'allow_guest_checkout', true ) ) {
		return;
	}
	?>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-checkbox' ) ); ?>">
		<label class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label pmpro_form_label-inline pmpro_clickable' ) ); ?>">
			<input type="checkbox" id="pmpro_guest_checkout" name="pmpro_guest_checkout" value="1" <?php checked( ! empty( $_REQUEST['pmpro_guest_checkout'] ) ); ?> />
			<?php esc_html_e( 'Check out as a guest', 'paid-memberships-pro' ); ?>
		</label>
	</div>
	<script>
		jQuery(document).ready(function($) {
			var $checkbox = $('#pmpro_guest_checkout');
			var $userFieldset = $('#pmpro_user_fields');
			var $formFields = $userFieldset.find('.pmpro_form_fields');
			var $loginLink = $userFieldset.find('.pmpro_card_actions');

			// Fields to hide in guest mode.
			var $usernameField = $formFields.find('.pmpro_form_field-username');
			var $passwordFields = $formFields.find('.pmpro_form_field-password');
			var $passwordCols = $passwordFields.first().closest('.pmpro_cols-2');
			var $confirmEmailField = $formFields.find('.pmpro_form_field-bconfirmemail');
			var $emailField = $formFields.find('.pmpro_form_field-bemail');

			// The email and confirm email may share a pmpro_cols-2 wrapper.
			var $emailCols = $emailField.closest('.pmpro_cols-2');
			var isGuest = false;

			function setGuestMode(guest) {
				isGuest = guest;
				$checkbox.prop('checked', isGuest);

				if (isGuest) {
					$usernameField.hide();
					$passwordFields.hide();
					$passwordCols.hide();
					$confirmEmailField.hide();
					$loginLink.hide();

					// Move email field out of the cols-2 wrapper so hiding confirm doesn't hide email too.
					if ($emailCols.length) {
						$emailField.insertBefore($emailCols);
						$emailCols.hide();
					}
				} else {
					// Move email field back into the cols-2 wrapper.
					if ($emailCols.length) {
						$emailCols.prepend($emailField);
						$emailCols.show();
					}

					$usernameField.show();
					$passwordFields.show();
					$passwordCols.show();
					$confirmEmailField.show();
					$loginLink.show();
				}
			}

			$checkbox.on('change', function() {
				setGuestMode($(this).is(':checked'));
			});

			// If reloading with guest checkout already checked.
			if ($checkbox.is(':checked')) {
				setGuestMode(true);
			}
		});
	</script>
	<?php
}
add_action( 'pmpro_checkout_before_account_fields', 'pmpro_guest_checkout_add_checkout_toggle' );
