<?php

/**
 * WP Fusion - PMPro Hooks Class.
 *
 * @since 3.41.44
 */
class WPF_PMPro_Hooks {

	/**
	 * Constructor.
	 *
	 * @since 3.41.44
	 */
	public function __construct() {

		// Membership level changes.
		add_action( 'pmpro_after_change_membership_level', array( $this, 'after_change_membership_level' ), 10, 2 );
		add_action( 'pmpro_before_change_membership_level', array( $this, 'before_change_membership_level' ), 10, 4 );

		// Checkout and orders.
		add_action( 'pmpro_after_checkout', array( $this, 'after_checkout' ), 10, 2 );
		add_action( 'pmpro_after_order_free_save', array( $this, 'after_checkout' ), 10, 2 );
		add_action( 'pmpro_subscription_payment_failed', array( $this, 'subscription_payment_failed' ) );
		add_action( 'pmpro_subscription_payment_completed', array( $this, 'subscription_payment_completed' ) );
		add_action( 'pmpro_after_order_free_save', array( $this, 'after_redeem' ), 10 );

		// Profile updates.
		add_action( 'profile_update', array( $this, 'profile_fields_update' ), 10 );

		// Membership expiry.
		add_action( 'pmpro_membership_post_membership_expiry', array( $this, 'membership_expiry' ), 10, 2 );
	}

	/**
	 * Triggered when new order is placed.
	 *
	 * @since 3.41.44
	 * @param int   $user_id The user ID.
	 * @param mixed $order   The order object.
	 */
	public function after_checkout( $user_id, $order ) {
		$user_meta = array(
			'pmpro_payment_method' => $order->payment_type,
		);

		wp_fusion()->user->push_user_meta( $user_id, $user_meta );

		// Handle discount codes
		global $discount_code_id;

		if ( ! empty( $discount_code_id ) ) {
			$settings = get_option( 'wpf_pmp_discount_' . $discount_code_id );

			if ( ! empty( $settings ) && ! empty( $settings['apply_tags'] ) ) {
				wp_fusion()->user->apply_tags( $settings['apply_tags'], $user_id );
			}
		}
	}

	/**
	 * Triggered before a membership level change.
	 *
	 * @since 3.41.44
	 * @param int   $level_id     The level ID.
	 * @param int   $user_id      The user ID.
	 * @param array $old_levels   The old levels.
	 * @param bool  $cancel_level Whether the level is being cancelled.
	 */
	public function before_change_membership_level( $level_id, $user_id, $old_levels, $cancel_level ) {

		// Disable tag link function.
		remove_action( 'wpf_tags_modified', array( wp_fusion()->integrations->pmpro, 'update_membership' ) );

		// Check if this is a user profile edit page and remove actions as necessary.
		global $pagenow;

		if ( 'profile.php' == $pagenow || 'user-edit.php' == $pagenow ) {
			return;
		}

		foreach ( $old_levels as $old_level ) {
			$old_level_settings = get_option( 'wpf_pmp_' . $old_level->ID );

			if ( ! empty( $old_level_settings ) ) {
				global $pmpro_next_payment_timestamp;

				if ( ! empty( $pmpro_next_payment_timestamp ) && $level_id == $old_level->ID ) {
					// If the Cancel on Next Payment Date addon is active, and the level is about to be reinstated, don't modify any tags
					$update_data = array(
						'pmpro_expiration_date' => gmdate( get_option( 'date_format' ), $pmpro_next_payment_timestamp ),
					);

					wp_fusion()->user->push_user_meta( $user_id, $update_data );
					return;
				}

				// Regular cancellation
				wpf_log( 'info', $user_id, 'User left Paid Memberships Pro level <a href="' . admin_url( 'admin.php?page=pmpro-membershiplevels&edit=' . $old_level->ID ) . '">' . $old_level->name . '</a>.' );

				wp_fusion()->integrations->pmpro->apply_membership_level_tags( $user_id, $old_level->ID, 'cancelled' );
			}
		}
	}

	/**
	 * Triggered after a membership level change.
	 *
	 * @since 3.41.44
	 * @param int $level_id The level ID.
	 * @param int $user_id  The user ID.
	 */
	public function after_change_membership_level( $level_id, $user_id ) {
		if ( ! empty( $level_id ) ) {

			$level = pmpro_getSpecificMembershipLevelForUser( $user_id, $level_id );

			wpf_log( 'info', $user_id, 'User joined Paid Memberships Pro level <a href="' . admin_url( 'admin.php?page=pmpro-membershiplevels&edit=' . $level_id ) . '">' . $level->name . '</a>.' );

			wp_fusion()->integrations->pmpro->sync_membership_level_fields( $user_id, $level );
			wp_fusion()->integrations->pmpro->apply_membership_level_tags( $user_id, $level_id, 'active' );
		}
	}

	/**
	 * Triggered when a recurring subscription payment fails.
	 *
	 * @since 3.41.44
	 * @param object $old_order The order object.
	 */
	public function subscription_payment_failed( $old_order ) {
		$user_id = $old_order->user_id;
		$level   = pmpro_getLevel( $old_order->membership_id );

		if ( ! empty( $level ) ) {
			$settings = get_option( 'wpf_pmp_' . $level->id );

			if ( ! empty( $settings ) && ! empty( $settings['apply_tags_payment_failed'] ) ) {
				wp_fusion()->user->apply_tags( $settings['apply_tags_payment_failed'], $user_id );
			}
		}
	}

	/**
	 * Triggered when a recurring subscription payment succeeds.
	 *
	 * @since 3.41.44
	 * @param object $order The order object.
	 */
	public function subscription_payment_completed( $order ) {
		$user_id = $order->user_id;
		$level   = pmpro_getLevel( $order->membership_id );

		if ( ! empty( $level ) ) {
			$settings = get_option( 'wpf_pmp_' . $level->id );

			if ( ! empty( $settings ) && ! empty( $settings['apply_tags_payment_failed'] ) ) {
				wp_fusion()->user->remove_tags( $settings['apply_tags_payment_failed'], $user_id );
			}
		}
	}

	/**
	 * Triggered when a user's membership expires.
	 *
	 * @since 3.41.44
	 * @param int $user_id  The user ID.
	 * @param int $level_id The level ID.
	 */
	public function membership_expiry( $user_id, $level_id ) {
		// PMPro will remove their level after the expiry, so there's no need to run before_change_membership_level() again
		remove_action( 'pmpro_before_change_membership_level', array( $this, 'before_change_membership_level' ), 10, 4 );

		// Update level meta
		$update_data = array(
			'pmpro_status'          => 'expired',
			'pmpro_expiration_date' => '',
		);

		wp_fusion()->user->push_user_meta( $user_id, $update_data );

		// Update tags
		$settings = get_option( 'wpf_pmp_' . $level_id );

		if ( ! empty( $settings ) ) {
			wp_fusion()->integrations->pmpro->apply_membership_level_tags( $user_id, $level_id, 'expired' );
		}
	}

	/**
	 * Triggered when profile fields are updated.
	 *
	 * @since 3.41.44
	 * @param int $user_id The user ID.
	 */
	public function profile_fields_update( $user_id ) {
		wp_fusion()->integrations->pmpro->sync_membership_level_fields( $user_id, pmpro_getMembershipLevelForUser( $user_id ) );
	}

	/**
	 * Triggered after redeeming a gift certificate.
	 *
	 * @since 3.41.44
	 * @param object $order The order object.
	 */
	public function after_redeem( $order ) {
		$this->after_checkout( $order->user_id, $order );
	}
}
