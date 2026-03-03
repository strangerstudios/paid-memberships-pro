<?php

/**
 * WP Fusion - PMPro Hooks Class.
 *
 * @since 3.41.44
 */
class WPF_PMPro_Hooks {

	/**
	 * Fields to clear during sync.
	 *
	 * Stores meta keys that should be cleared in the CRM during
	 * the sync_cancelled_level_fields operation.
	 *
	 * @since 3.47.4
	 * @var array
	 */
	private $fields_to_clear = array();

	/**
	 * Cancelled levels pending sync.
	 *
	 * Stores level objects from before_change_membership_level so the
	 * after_change_membership_level hook can query the actual status
	 * from the database after it's been set.
	 *
	 * @since 3.47.4.2
	 * @var array
	 */
	private $cancelled_levels = array();

	/**
	 * Gets the level ID from a level object.
	 *
	 * Handles both pmpro_getLevel() objects (which use lowercase ->id)
	 * and user level objects (which use uppercase ->ID).
	 *
	 * @since 3.47.4
	 *
	 * @param object $level The membership level object.
	 * @return int The level ID, or 0 if not found.
	 */
	private function get_level_id( $level ) {
		return isset( $level->id ) ? $level->id : ( isset( $level->ID ) ? $level->ID : 0 );
	}

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

		// Pending cancellation - track subscription cancellation and check on shutdown.
		add_action( 'pmpro_updated_subscription', array( $this, 'track_subscription_cancellation' ) );

		// Pending cancellation - fires after frontend cancellation is processed.
		add_action( 'pmpro_cancel_processed', array( $this, 'cancel_processed' ) );
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

		// Capture the initial payment amount for one-time orders or initial subscription payment.
		if ( isset( $order->total ) ) {
			$user_meta['pmpro_initial_payment'] = $order->total;
		} elseif ( isset( $order->{'InitialPayment'} ) ) {
			// Fallback to InitialPayment property if total is not available.
			$user_meta['pmpro_initial_payment'] = $order->{'InitialPayment'};
		} elseif ( isset( $order->subtotal ) ) {
			// Fallback to subtotal if neither total nor InitialPayment are available.
			$user_meta['pmpro_initial_payment'] = $order->subtotal;
		}

		wp_fusion()->user->push_user_meta( $user_id, $user_meta );

		// Sync membership fields for the level purchased in this order.
		// Note: pmpro_getMembershipLevelForUser() is not level-specific when multiple memberships are active.
		if ( ! empty( $order->membership_id ) ) {
			// Ensure the subscription is created (if applicable) so next payment date can be queried.
			if ( is_object( $order ) && method_exists( $order, 'get_subscription' ) ) {
				$order->get_subscription();
			}

			$membership_level = pmpro_getSpecificMembershipLevelForUser( $user_id, (int) $order->membership_id );

			if ( ! empty( $membership_level ) ) {
				wp_fusion()->integrations->pmpro->sync_membership_level_fields( $user_id, $membership_level );
			}
		}

		// Handle discount codes.
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
	 * Stores cancelled level info for processing in after_change_membership_level
	 * where we can query the actual status from the database.
	 *
	 * @since 3.41.44
	 *
	 * @param int      $level_id     The new level ID (0 if cancelling).
	 * @param int      $user_id      The user ID.
	 * @param array    $old_levels   All current levels for the user.
	 * @param int|null $cancel_level The specific level ID being cancelled, or null if changing levels.
	 */
	public function before_change_membership_level( $level_id, $user_id, $old_levels, $cancel_level ) {

		// Disable tag link function.
		remove_action( 'wpf_tags_modified', array( wp_fusion()->integrations->pmpro, 'update_membership' ) );

		// Check if this is a user profile edit page and remove actions as necessary.
		global $pagenow;

		if ( 'profile.php' == $pagenow || 'user-edit.php' == $pagenow ) {
			return;
		}

		// Only process actual cancellations.
		// When PMPro changes a user to a new level, it fires this hook first with $cancel_level = null.
		// Any levels that are actually cancelled will then trigger pmpro_cancelMembershipLevel() and fire
		// this hook again with $cancel_level set to the level being cancelled.
		if ( empty( $cancel_level ) ) {
			return;
		}

		$levels_to_process = array();

		// A specific level is being cancelled - only process that one.
		foreach ( $old_levels as $old_level ) {
			if ( (int) $old_level->ID === (int) $cancel_level ) {
				$levels_to_process[] = $old_level;
				break;
			}
		}

		foreach ( $levels_to_process as $old_level ) {

			global $pmpro_next_payment_timestamp;

			if ( ! empty( $pmpro_next_payment_timestamp ) && $level_id == $old_level->ID ) {
				// If the Cancel on Next Payment Date addon is active, and the level is about to be reinstated, don't modify any tags.
				$update_data = array(
					'pmpro_expiration_date' => gmdate( get_option( 'date_format' ), $pmpro_next_payment_timestamp ),
				);

				wp_fusion()->user->push_user_meta( $user_id, $update_data );
				return;
			}

			// Get level name for logging.
			$level_name = ! empty( $old_level->name ) ? $old_level->name : 'Level ' . $old_level->ID;

			// Store the cancelled level for processing in after_change_membership_level.
			// This allows us to query the actual status from the database after it's been set.
			$this->cancelled_levels[ $old_level->ID ] = $old_level;

			// Log the change.
			wpf_log( 'info', $user_id, 'User left Paid Memberships Pro level <a href="' . admin_url( 'admin.php?page=pmpro-membershiplevels&edit=' . $old_level->ID ) . '">' . $level_name . '</a>.' );

			// Apply tags immediately (tags work with generic 'cancelled' status).
			wp_fusion()->integrations->pmpro->apply_membership_level_tags( $user_id, $old_level->ID, 'cancelled' );
		}
	}

	/**
	 * Triggered after a membership level change.
	 *
	 * Processes any stored cancelled levels with actual status from the database,
	 * then handles new level assignments.
	 *
	 * @since 3.41.44
	 *
	 * @param int $level_id The level ID.
	 * @param int $user_id  The user ID.
	 */
	public function after_change_membership_level( $level_id, $user_id ) {

		// Process any stored cancelled levels first.
		// At this point, the status has been set in the database so we can query it.
		if ( ! empty( $this->cancelled_levels ) ) {
			foreach ( $this->cancelled_levels as $cancelled_level_id => $cancelled_level ) {
				// Get the actual status from the database (admin_cancelled, cancelled, etc.).
				$actual_status = wp_fusion()->integrations->pmpro->get_membership_level_status( $user_id, $cancelled_level_id );

				if ( ! empty( $actual_status ) ) {
					$this->sync_cancelled_level_fields( $user_id, $cancelled_level, $actual_status );
				}
			}

			// Clear the stored levels.
			$this->cancelled_levels = array();
		}

		if ( ! empty( $level_id ) ) {

			$level = pmpro_getSpecificMembershipLevelForUser( $user_id, $level_id );

			if ( empty( $level ) ) {
				// Fallback to getting the level directly if user-specific level not available yet.
				$level = pmpro_getLevel( $level_id );
			}

			if ( ! empty( $level ) ) {
				$level_name = isset( $level->name ) ? $level->name : 'Level ' . $level_id;

				wpf_log( 'info', $user_id, 'User joined Paid Memberships Pro level <a href="' . admin_url( 'admin.php?page=pmpro-membershiplevels&edit=' . $level_id ) . '">' . $level_name . '</a>.' );

				wp_fusion()->integrations->pmpro->sync_membership_level_fields( $user_id, $level );
				wp_fusion()->integrations->pmpro->apply_membership_level_tags( $user_id, $level_id, 'active' );
			}
		} else {
			// User was removed from a level. Check if they have any remaining active levels.
			$remaining_levels = pmpro_getMembershipLevelsForUser( $user_id );

			if ( empty( $remaining_levels ) ) {
				// No remaining levels - sync empty/null fields.
				wp_fusion()->integrations->pmpro->sync_membership_level_fields( $user_id );
			}
			// If remaining levels exist, the cancelled level data was already synced above.
			// We don't overwrite it with remaining level data so CRM workflows can trigger
			// based on the specific level that was just cancelled.
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
	 * Tracks when a subscription is cancelled to process pending cancellation on shutdown.
	 *
	 * This is needed because the expiration date is set AFTER the subscription is saved
	 * in some flows (gateway webhook), so we need to check on shutdown when all
	 * PMPro processing is complete.
	 *
	 * @since 3.47.4
	 *
	 * @param PMPro_Subscription $subscription The subscription object.
	 */
	public function track_subscription_cancellation( $subscription ) {

		// Only process cancelled subscriptions.
		if ( 'cancelled' !== $subscription->get_status() ) {
			return;
		}

		$user_id  = $subscription->get_user_id();
		$level_id = $subscription->get_membership_level_id();

		// Check if user still has the membership level.
		if ( ! pmpro_hasMembershipLevel( $level_id, $user_id ) ) {
			return;
		}

		// Store the cancellation info to process on shutdown (always sync fields, not just tags).
		$pending_cancellations   = get_transient( 'wpf_pmpro_pending_cancellations' );
		$pending_cancellations   = is_array( $pending_cancellations ) ? $pending_cancellations : array();
		$pending_cancellations[] = array(
			'user_id'  => $user_id,
			'level_id' => $level_id,
		);
		set_transient( 'wpf_pmpro_pending_cancellations', $pending_cancellations, 60 );

		// Register shutdown handler if not already registered.
		static $shutdown_registered = false;
		if ( ! $shutdown_registered ) {
			add_action( 'shutdown', array( $this, 'process_pending_cancellations' ) );
			$shutdown_registered = true;
		}
	}

	/**
	 * Triggered after a cancellation is processed from the frontend.
	 *
	 * At this point, the expiration date has been set for pending cancellations.
	 *
	 * @since 3.47.4
	 *
	 * @param WP_User $user The user who cancelled.
	 */
	public function cancel_processed( $user ) {

		$user_id = $user->ID;

		// Determine which level(s) were requested for cancellation from the Cancel page.
		$level_ids = array();

		if ( ! empty( $_REQUEST['levelstocancel'] ) ) {
			if ( 'all' === $_REQUEST['levelstocancel'] ) {
				$levels    = pmpro_getMembershipLevelsForUser( $user_id );
				$level_ids = ! empty( $levels ) ? wp_list_pluck( $levels, 'id' ) : array();
			} else {
				$requested_ids = str_replace( array( ' ', '%20' ), '+', sanitize_text_field( wp_unslash( $_REQUEST['levelstocancel'] ) ) );
				$requested_ids = preg_replace( '/[^0-9\+]/', '', $requested_ids );
				$level_ids     = array_map( 'absint', explode( '+', $requested_ids ) );
			}
		} elseif ( ! empty( $_REQUEST['level'] ) ) {
			$level_ids = array( absint( $_REQUEST['level'] ) );
		}

		$level_ids = array_filter( array_unique( $level_ids ) );

		if ( empty( $level_ids ) ) {
			return;
		}

		foreach ( $level_ids as $level_id ) {

			$level = pmpro_getSpecificMembershipLevelForUser( $user_id, $level_id );

			// Only process pending cancellations where PMPro has set an expiration date.
			if ( empty( $level ) || empty( $level->enddate ) || 0 === (int) $level->enddate ) {
				continue;
			}

			// Get level name for logging.
			$level_name = ! empty( $level->name ) ? $level->name : 'Level ' . $level_id;

			wpf_log( 'info', $user_id, 'User cancelled Paid Memberships Pro subscription for level <a href="' . admin_url( 'admin.php?page=pmpro-membershiplevels&edit=' . $level_id ) . '">' . $level_name . '</a> (pending cancellation - access until ' . date_i18n( get_option( 'date_format' ), intval( $level->enddate ) ) . ').' );

			// Always sync the pending cancellation fields (regardless of tag settings).
			$this->sync_pending_cancellation_level_fields( $user_id, $level );

			// Apply tags if configured.
			wp_fusion()->integrations->pmpro->apply_membership_level_tags( $user_id, $level_id, 'pending_cancellation' );
		}
	}

	/**
	 * Processes pending cancellations on shutdown.
	 *
	 * At this point, all PMPro processing is complete and the expiration date
	 * should be set if it's a pending cancellation.
	 *
	 * @since 3.47.4
	 */
	public function process_pending_cancellations() {

		$pending_cancellations = get_transient( 'wpf_pmpro_pending_cancellations' );

		if ( empty( $pending_cancellations ) || ! is_array( $pending_cancellations ) ) {
			return;
		}

		// Clear the transient immediately to prevent duplicate processing.
		delete_transient( 'wpf_pmpro_pending_cancellations' );

		foreach ( $pending_cancellations as $cancellation ) {

			$user_id  = $cancellation['user_id'];
			$level_id = $cancellation['level_id'];

			// Get the user's current level to check if it has an end date set.
			$level = pmpro_getSpecificMembershipLevelForUser( $user_id, $level_id );

			// If user still has the level with an end date, it's a pending cancellation.
			if ( empty( $level ) || empty( $level->enddate ) || '0000-00-00 00:00:00' === $level->enddate ) {
				continue;
			}

			// Get level name for logging.
			$level_name = ! empty( $level->name ) ? $level->name : 'Level ' . $level_id;

			wpf_log( 'info', $user_id, 'User cancelled Paid Memberships Pro subscription for level <a href="' . admin_url( 'admin.php?page=pmpro-membershiplevels&edit=' . $level_id ) . '">' . $level_name . '</a> (pending cancellation - access until ' . date_i18n( get_option( 'date_format' ), intval( $level->enddate ) ) . ').' );

			// Always sync the pending cancellation fields (regardless of tag settings).
			$this->sync_pending_cancellation_level_fields( $user_id, $level );

			// Apply tags if configured.
			wp_fusion()->integrations->pmpro->apply_membership_level_tags( $user_id, $level_id, 'pending_cancellation' );
		}
	}

	/**
	 * Syncs level-specific fields for pending cancellation status.
	 *
	 * Updates the level-specific status and expiration date fields when a user
	 * cancels their subscription but still has access until the end of the billing period.
	 * Also clears next_payment_date since subscription is cancelled.
	 *
	 * @since 3.47.4
	 *
	 * @param int    $user_id The user ID.
	 * @param object $level   The membership level object with enddate set.
	 */
	public function sync_pending_cancellation_level_fields( $user_id, $level ) {

		$level_id   = $this->get_level_id( $level );
		$level_name = ! empty( $level->name ) ? $level->name : '';

		// Get level name from pmpro_getLevel if not in passed object.
		if ( empty( $level_name ) && ! empty( $level_id ) ) {
			$level_obj = pmpro_getLevel( $level_id );
			if ( ! empty( $level_obj->name ) ) {
				$level_name = $level_obj->name;
			}
		}

		if ( empty( $level_id ) ) {
			return;
		}

		// Prepare both global and level-specific data with pending cancellation status.
		$update_data = array(
			// Global fields.
			'pmpro_membership_level'    => $level_name,
			'pmpro_status'              => 'pending_cancellation',
			// Global next payment date (earliest among all remaining active subscriptions).
			'pmpro_next_payment_date'   => null,
			// Level-specific fields.
			'pmpro_status_' . $level_id => 'pending_cancellation',
		);

		// Set global next payment date from remaining active subscriptions (if any).
		$global_next_payment_timestamp = wp_fusion()->integrations->pmpro->get_next_payment_timestamp( $user_id, false );

		if ( ! empty( $global_next_payment_timestamp ) ) {
			$update_data['pmpro_next_payment_date'] = date_i18n( get_option( 'date_format' ), intval( $global_next_payment_timestamp ) );
		}

		// Update expiration date if available.
		if ( ! empty( $level->enddate ) && '0000-00-00 00:00:00' !== $level->enddate ) {
			$expiration_date                                     = date_i18n( get_option( 'date_format' ), intval( $level->enddate ) );
			$update_data['pmpro_expiration_date']                = $expiration_date;
			$update_data[ 'pmpro_expiration_date_' . $level_id ] = $expiration_date;
		}

		// Clear next_payment_date since subscription is cancelled.
		// Use the filter to inject empty values for fields that need to be cleared.
		$this->fields_to_clear = array(
			'pmpro_next_payment_date',
			'pmpro_next_payment_date_' . $level_id,
		);

		// If there are still active subscriptions, do not clear the global next payment date field.
		if ( ! empty( $global_next_payment_timestamp ) ) {
			$this->fields_to_clear = array( 'pmpro_next_payment_date_' . $level_id );
		}

		add_filter( 'wpf_map_meta_fields', array( $this, 'inject_cleared_fields' ), 10, 2 );

		wp_fusion()->user->push_user_meta( $user_id, $update_data );

		remove_filter( 'wpf_map_meta_fields', array( $this, 'inject_cleared_fields' ), 10 );

		// Clean up.
		$this->fields_to_clear = array();
	}

	/**
	 * Syncs level-specific fields when a membership is cancelled, expired, or inactive.
	 *
	 * This ensures that level-specific field mappings (e.g., pmpro_status_5) are updated
	 * when a user's membership status changes, not just when they are granted a level.
	 *
	 * Also syncs global fields (pmpro_membership_level, pmpro_status) with the cancelled
	 * level's data so CRM workflows can be triggered based on the cancelled level.
	 *
	 * @since 3.47.4
	 *
	 * @param int    $user_id The user ID.
	 * @param object $level   The membership level object (can be from pmpro_getLevel or user level).
	 * @param string $status  The new status (cancelled, expired, inactive, admin_cancelled).
	 */
	public function sync_cancelled_level_fields( $user_id, $level, $status ) {

		$level_id   = $this->get_level_id( $level );
		$level_name = ! empty( $level->name ) ? $level->name : '';

		if ( empty( $level_id ) ) {
			return;
		}

		// Get level name from pmpro_getLevel if not in passed object.
		if ( empty( $level_name ) ) {
			$level_obj = pmpro_getLevel( $level_id );
			if ( ! empty( $level_obj->name ) ) {
				$level_name = $level_obj->name;
			}
		}

		// Prepare both global and level-specific data with the new status.
		$update_data = array(
			// Global fields - the level that was just cancelled/expired.
			'pmpro_membership_level'    => $level_name,
			'pmpro_status'              => $status,
			// Global next payment date (earliest among all remaining active subscriptions).
			'pmpro_next_payment_date'   => null,
			// Level-specific field.
			'pmpro_status_' . $level_id => $status,
		);

		// Set global next payment date from remaining active subscriptions (if any).
		$global_next_payment_timestamp = wp_fusion()->integrations->pmpro->get_next_payment_timestamp( $user_id, false );

		if ( ! empty( $global_next_payment_timestamp ) ) {
			$update_data['pmpro_next_payment_date'] = date_i18n( get_option( 'date_format' ), intval( $global_next_payment_timestamp ) );
		}

		// For cancelled/expired, also clear certain fields.
		// We use the wpf_map_meta_fields filter to inject empty values for fields that
		// need to be cleared, since WPF filters out empty/null values by default.
		if ( 'cancelled' === $status || 'expired' === $status || 'inactive' === $status || 'admin_cancelled' === $status ) {

			$fields_to_clear = array(
				'pmpro_next_payment_date',
				'pmpro_next_payment_date_' . $level_id,
				'pmpro_subscription_price_' . $level_id,
			);

			// If there are still active subscriptions, do not clear the global next payment date field.
			if ( ! empty( $global_next_payment_timestamp ) ) {
				$fields_to_clear = array(
					'pmpro_next_payment_date_' . $level_id,
					'pmpro_subscription_price_' . $level_id,
				);
			}

			// For expired, also clear the expiration date.
			if ( 'expired' === $status ) {
				$fields_to_clear[] = 'pmpro_expiration_date_' . $level_id;
			}

			// Store fields to clear for the filter.
			$this->fields_to_clear = $fields_to_clear;

			// Add a filter to inject the empty values after field mapping.
			add_filter( 'wpf_map_meta_fields', array( $this, 'inject_cleared_fields' ), 10, 2 );

			wp_fusion()->user->push_user_meta( $user_id, $update_data );

			remove_filter( 'wpf_map_meta_fields', array( $this, 'inject_cleared_fields' ), 10 );

			// Clean up.
			$this->fields_to_clear = array();

			return;
		}

		wp_fusion()->user->push_user_meta( $user_id, $update_data );
	}

	/**
	 * Injects empty values for fields that need to be cleared.
	 *
	 * This is used as a callback for the wpf_map_meta_fields filter to bypass
	 * WP Fusion's default behavior of filtering out empty values.
	 *
	 * @since 3.47.4
	 *
	 * @param array $update_data The mapped field data.
	 * @param array $user_meta   The original user meta.
	 * @return array The modified update data.
	 */
	public function inject_cleared_fields( $update_data, $user_meta ) {

		unset( $user_meta ); // Not used but required by filter signature.

		if ( empty( $this->fields_to_clear ) ) {
			return $update_data;
		}

		$contact_fields = wp_fusion()->crm->contact_fields;

		foreach ( $this->fields_to_clear as $meta_key ) {
			if ( ! empty( $contact_fields[ $meta_key ]['crm_field'] ) ) {
				// Set empty string to clear the field in the CRM.
				$update_data[ $contact_fields[ $meta_key ]['crm_field'] ] = '';
			}
		}

		return $update_data;
	}

	/**
	 * Triggered when a user's membership expires.
	 *
	 * @since 3.41.44
	 * @param int $user_id  The user ID.
	 * @param int $level_id The level ID.
	 */
	public function membership_expiry( $user_id, $level_id ) {
		// PMPro will remove their level after the expiry, so there's no need to run before_change_membership_level() again.
		remove_action( 'pmpro_before_change_membership_level', array( $this, 'before_change_membership_level' ), 10, 4 );

		// Get the level object before it's removed.
		$level = pmpro_getLevel( $level_id );

		// Update level meta including level-specific fields.
		$update_data = array(
			'pmpro_status'          => 'expired',
			'pmpro_expiration_date' => '',
		);

		wp_fusion()->user->push_user_meta( $user_id, $update_data );

		// Sync level-specific fields with expired status.
		if ( ! empty( $level ) ) {
			$this->sync_cancelled_level_fields( $user_id, $level, 'expired' );
		}

		// Update tags.
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
