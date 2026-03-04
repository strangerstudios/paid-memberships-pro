<?php
/**
 * WP Fusion - Paid Memberships Pro Integration.
 *
 * @package   WP Fusion
 * @copyright Copyright (c) 2024, Very Good Plugins, https://verygoodplugins.com
 * @license   GPL-3.0+
 * @since     3.45.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles integration with Paid Memberships Pro.
 *
 * @since 3.45.3
 */
class WPF_PMPro extends PMPro_WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.45.3
	 * @var string $slug
	 */
	public $slug = 'pmpro';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.45.3
	 * @var string $name
	 */
	public $name = 'Paid Memberships Pro';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.45.3
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/membership/paid-memberships-pro/';

	/**
	 * Admin class instance.
	 *
	 * @since 3.45.3
	 * @var WPF_PMPro_Admin
	 */
	public $admin;

	/**
	 * Hooks class instance.
	 *
	 * @since 3.45.3
	 * @var WPF_PMPro_Hooks
	 */
	public $hooks;

	/**
	 * Batch class instance.
	 *
	 * @since 3.45.3
	 * @var WPF_PMPro_Batch
	 */
	public $batch;

	/**
	 * Approvals class instance.
	 *
	 * @since 3.45.3
	 * @var WPF_PMPro_Approvals
	 */
	public $approvals;

	/**
	 * Gets things started.
	 *
	 * @since 3.45.3
	 */
	public function init() {

		// Load required classes.
		require_once __DIR__ . '/class-pmpro-admin.php';
		require_once __DIR__ . '/class-pmpro-hooks.php';
		require_once __DIR__ . '/class-pmpro-batch.php';

		// Initialize classes.
		$this->admin = new WPF_PMPro_Admin();
		$this->hooks = new WPF_PMPro_Hooks();
		$this->batch = new WPF_PMPro_Batch();

		// Approvals integration.
		if ( class_exists( 'PMPro_Approvals' ) ) {
			require_once __DIR__ . '/class-pmpro-approvals.php';
			$this->approvals = new WPF_PMPro_Approvals();
		}

		// WPF Stuff.
		add_filter( 'wpf_user_register', array( $this, 'user_register' ) );
		add_filter( 'wpf_user_update', array( $this, 'user_register' ) );
		add_action( 'wpf_tags_modified', array( $this, 'update_membership' ), 10, 2 );

		add_action( 'wpf_user_updated', array( $this, 'user_updated' ), 10, 2 );
		add_action( 'wpf_user_imported', array( $this, 'user_updated' ), 10, 2 );
	}

	/**
	 * Gets the next payment timestamp for a user, optionally scoped to a membership level.
	 *
	 * In PMPro, pmpro_next_payment() is not membership-level specific when multiple memberships
	 * are active. This method queries PMPro_Subscription records directly to return a
	 * level-specific next payment date.
	 *
	 * @since 3.47.4.2
	 *
	 * @param int      $user_id  The user ID.
	 * @param int|bool $level_id The membership level ID, or false for all levels.
	 * @return int|null The next payment timestamp, or null if none is found.
	 */
	public function get_next_payment_timestamp( $user_id, $level_id = false ) {

		if ( ! class_exists( 'PMPro_Subscription' ) ) {
			return null;
		}

		$user_id = absint( $user_id );

		if ( empty( $user_id ) ) {
			return null;
		}

		$args_level_id = false !== $level_id ? absint( $level_id ) : null;

		$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $user_id, $args_level_id, array( 'active' ) );

		if ( empty( $subscriptions ) ) {
			return null;
		}

		$next_payment = null;

		foreach ( $subscriptions as $subscription ) {
			$timestamp = $subscription->get_next_payment_date( 'timestamp' );

			if ( empty( $timestamp ) ) {
				continue;
			}

			$timestamp = absint( $timestamp );

			if ( empty( $timestamp ) ) {
				continue;
			}

			if ( empty( $next_payment ) || $timestamp < $next_payment ) {
				$next_payment = $timestamp;
			}
		}

		return $next_payment;
	}

	/**
	 * Gets the last order for a user, optionally scoped to a membership level.
	 *
	 * @since 3.47.4.2
	 *
	 * @param int      $user_id  The user ID.
	 * @param int|bool $level_id The membership level ID, or false for all levels.
	 * @return MemberOrder|false The order object, or false if none is found.
	 */
	public function get_last_order( $user_id, $level_id = false ) {

		if ( ! class_exists( 'MemberOrder' ) ) {
			return false;
		}

		$user_id = absint( $user_id );

		if ( empty( $user_id ) ) {
			return false;
		}

		$order_statuses = apply_filters( 'pmpro_confirmation_order_status', array( 'success', 'pending', 'token' ) );
		$membership_id  = false !== $level_id ? absint( $level_id ) : null;

		$order    = new MemberOrder();
		$order_id = $order->getLastMemberOrder( $user_id, $order_statuses, $membership_id );

		if ( empty( $order_id ) ) {
			return false;
		}

		return $order;
	}

	/**
	 * Builds order field data for sync.
	 *
	 * @since 3.47.4.2
	 *
	 * @param int      $user_id  The user ID.
	 * @param int|bool $level_id The membership level ID, or false for all levels.
	 * @return array Order field data keyed by meta keys.
	 */
	private function get_order_field_data( $user_id, $level_id = false ) {

		$data = array(
			'pmpro_order_status'  => null,
			'pmpro_order_code'    => null,
			'pmpro_membership_id' => null,
			'pmpro_order_date'    => null,
		);

		$order = $this->get_last_order( $user_id, $level_id );

		if ( empty( $order ) ) {
			return $data;
		}

		$data['pmpro_order_status']  = isset( $order->status ) ? $order->status : null;
		$data['pmpro_order_code']    = isset( $order->code ) ? $order->code : null;
		$data['pmpro_membership_id'] = isset( $order->membership_id ) ? $order->membership_id : null;

		$order_timestamp = null;
		if ( method_exists( $order, 'getTimestamp' ) ) {
			$order_timestamp = $order->getTimestamp();
		} elseif ( ! empty( $order->timestamp ) ) {
			$order_timestamp = is_numeric( $order->timestamp ) ? (int) $order->timestamp : strtotime( $order->timestamp );
		}

		if ( ! empty( $order_timestamp ) ) {
			$data['pmpro_order_date'] = date_i18n( wpf_get_datetime_format(), (int) $order_timestamp );
		}

		return $data;
	}


	/**
	 * Syncs custom fields for a membership level when a user is added to the level or via the batch process.
	 *
	 * @since unknown
	 *
	 * @param int         $user_id The user ID.
	 * @param object|bool $membership_level The user membership level object, or false if no level is found.
	 */
	public function sync_membership_level_fields( $user_id, $membership_level = false ) {

		if ( $membership_level ) {

			// Handle both lowercase (pmpro_getLevel) and uppercase (user level objects) ID properties.
			$level_id = isset( $membership_level->id ) ? $membership_level->id : ( isset( $membership_level->ID ) ? $membership_level->ID : 0 );

			if ( empty( $level_id ) ) {
				return;
			}

			// Get the level name - fallback to pmpro_getLevel if name not in object.
			$level_name = '';

			if ( ! empty( $membership_level->name ) ) {
				$level_name = $membership_level->name;
			} else {
				$level = pmpro_getLevel( $level_id );
				if ( ! empty( $level->name ) ) {
					$level_name = $level->name;
				}
			}

			// Get the next payment timestamp for this specific level.
			$next_payment_timestamp = $this->get_next_payment_timestamp( $user_id, $level_id );
			$order_data             = $this->get_order_field_data( $user_id, $level_id );

			// Prepare the data for all available fields.
			$update_data = array(
				'pmpro_status'             => $this->get_membership_level_status( $user_id, $level_id ),
				'pmpro_joined_date'        => $this->get_member_joined_date( $user_id ),
				'pmpro_start_date'         => ! empty( $membership_level->startdate ) ? date_i18n( get_option( 'date_format' ), intval( $membership_level->startdate ) ) : null,
				'pmpro_next_payment_date'  => ! empty( $next_payment_timestamp ) ? date_i18n( get_option( 'date_format' ), intval( $next_payment_timestamp ) ) : null,
				'pmpro_expiration_date'    => ! empty( $membership_level->enddate ) ? date_i18n( get_option( 'date_format' ), intval( $membership_level->enddate ) ) : null,
				'pmpro_membership_level'   => $level_name,
				'pmpro_subscription_price' => isset( $membership_level->billing_amount ) ? $membership_level->billing_amount : null,
				'pmpro_initial_payment'    => isset( $membership_level->initial_payment ) ? $membership_level->initial_payment : ( isset( $membership_level->billing_amount ) ? $membership_level->billing_amount : null ),
			);

			$update_data = array_merge( $update_data, $order_data );

			// Approvals.
			$approval_status = get_user_meta( $user_id, 'pmpro_approval_' . $level_id, true );

			if ( ! empty( $approval_status ) ) {
				$update_data['pmpro_approval'] = $approval_status['status'];
			}

			// Set level-specific field mappings.
			foreach ( $update_data as $meta_key => $meta_value ) {
				$update_data[ $meta_key . '_' . $level_id ] = $meta_value;
			}
		} else {

			// No level.
			$next_payment_timestamp = $this->get_next_payment_timestamp( $user_id, false );
			$order_data             = $this->get_order_field_data( $user_id, false );

			$update_data = array(
				'pmpro_membership_level'   => null,
				'pmpro_expiration_date'    => null,
				'pmpro_subscription_price' => null,
				'pmpro_initial_payment'    => null,
				'pmpro_next_payment_date'  => ! empty( $next_payment_timestamp ) ? date_i18n( get_option( 'date_format' ), intval( $next_payment_timestamp ) ) : null,
				'pmpro_status'             => $this->get_membership_level_status( $user_id ),
			);

			$update_data = array_merge( $update_data, $order_data );
		}

		wp_fusion()->user->push_user_meta( $user_id, $update_data );
	}

	/**
	 * Applies tags based on a user's current status in a membership level, either from being added to a level or via a batch operation.
	 *
	 * @since 3.45.3
	 *
	 * @param int         $user_id The user ID.
	 * @param int         $level_id The level ID.
	 * @param string|bool $status The status of the user in the level.
	 */
	public function apply_membership_level_tags( $user_id, $level_id, $status = false ) {

		// New level apply tags.
		$settings = get_option( 'wpf_pmp_' . $level_id );

		if ( empty( $settings ) ) {
			return;
		}

		if ( false === $status ) {
			$status = $this->get_membership_level_status( $user_id, $level_id );
		}

		if ( empty( $status ) ) {
			return;
		}

		$apply_keys  = array();
		$remove_keys = array();

		if ( 'active' === $status ) {

			// Active.

			$apply_keys  = array( 'apply_tags', 'tag_link' );
			$remove_keys = array( 'apply_tags_expired', 'apply_tags_cancelled', 'apply_tags_payment_failed', 'apply_tags_pending_cancellation' );

		} elseif ( 'expired' === $status ) {

			// Expired.

			$apply_keys  = array( 'apply_tags_expired' );
			$remove_keys = array( 'tag_link' );

			if ( ! empty( $settings['remove_tags'] ) ) {
				$remove_keys[] = 'apply_tags';
			}
		} elseif ( 'cancelled' === $status || 'admin_cancelled' === $status ) {

			// Cancelled (includes admin_cancelled).

			$apply_keys  = array( 'apply_tags_cancelled' );
			$remove_keys = array( 'tag_link' );

			if ( ! empty( $settings['remove_tags'] ) ) {
				$remove_keys[] = 'apply_tags';
			}
		} elseif ( 'pending_cancellation' === $status ) {

			// Pending cancellation (cancelled but still has time remaining).

			$apply_keys = array( 'apply_tags_pending_cancellation' );

		} elseif ( 'inactive' === $status ) {

			// Inactive.

			$remove_keys = array( 'tag_link' );

			if ( ! empty( $settings['remove_tags'] ) ) {
				$remove_keys[] = 'apply_tags';
			}
		}

		$apply_tags  = array();
		$remove_tags = array();

		// Figure out which tags to apply and remove.

		foreach ( $apply_keys as $key ) {

			if ( ! empty( $settings[ $key ] ) ) {

				$apply_tags = array_merge( $apply_tags, $settings[ $key ] );

			}
		}

		foreach ( $remove_keys as $key ) {

			if ( ! empty( $settings[ $key ] ) ) {

				$remove_tags = array_merge( $remove_tags, $settings[ $key ] );

			}
		}

		$apply_tags  = apply_filters( 'wpf_pmpro_membership_status_apply_tags', $apply_tags, $status, $user_id, $level_id );
		$remove_tags = apply_filters( 'wpf_pmpro_membership_status_remove_tags', $remove_tags, $status, $user_id, $level_id );

		// For non-active statuses, preserve tags that are still needed by other active levels.
		if ( 'active' !== $status && ! empty( $remove_tags ) ) {
			$remove_tags = $this->filter_tags_from_active_levels( $remove_tags, $user_id, $level_id );
		}

		// Disable tag link function.
		remove_action( 'wpf_tags_modified', array( $this, 'update_membership' ) );

		if ( ! empty( $remove_tags ) ) {
			wp_fusion()->user->remove_tags( $remove_tags, $user_id );
		}

		if ( ! empty( $apply_tags ) ) {
			wp_fusion()->user->apply_tags( $apply_tags, $user_id );
		}

		add_action( 'wpf_tags_modified', array( $this, 'update_membership' ), 10, 2 );
	}

	/**
	 * Filters out tags that are still needed by other active membership levels.
	 *
	 * When a user cancels/expires from one level but has other active levels,
	 * this prevents removing tags that are shared between levels.
	 *
	 * @since 3.47.4
	 *
	 * @param array $remove_tags       Tags that would be removed.
	 * @param int   $user_id           The user ID.
	 * @param int   $cancelled_level_id The level being cancelled/expired.
	 * @return array Filtered tags that should actually be removed.
	 */
	private function filter_tags_from_active_levels( $remove_tags, $user_id, $cancelled_level_id ) {

		// Get all active levels for this user.
		$all_levels = pmpro_getMembershipLevelsForUser( $user_id );

		if ( empty( $all_levels ) ) {
			return $remove_tags;
		}

		$remaining_level_tags = array();

		foreach ( $all_levels as $level ) {

			// Skip the level being cancelled. Cast to int for type-safe comparison.
			if ( (int) $level->id === (int) $cancelled_level_id ) {
				continue;
			}

			// Check if this level is actually active.
			$level_status = $this->get_membership_level_status( $user_id, $level->id );

			if ( 'active' !== $level_status ) {
				continue;
			}

			// Get tags from this level's settings.
			$level_settings = get_option( 'wpf_pmp_' . $level->id );

			if ( ! empty( $level_settings['apply_tags'] ) ) {
				$remaining_level_tags = array_merge( $remaining_level_tags, $level_settings['apply_tags'] );
			}

			if ( ! empty( $level_settings['tag_link'] ) ) {
				$remaining_level_tags = array_merge( $remaining_level_tags, $level_settings['tag_link'] );
			}
		}

		if ( empty( $remaining_level_tags ) ) {
			return $remove_tags;
		}

		// Remove tags that are still needed by other levels.
		$filtered_tags = array_diff( $remove_tags, $remaining_level_tags );

		// Log if we preserved any tags.
		$preserved_tags = array_intersect( $remove_tags, $remaining_level_tags );

		if ( ! empty( $preserved_tags ) ) {
			wpf_log( 'info', $user_id, 'Preserved tags from removal because they are used by other active PMPro levels: ' . implode( ', ', array_map( 'wpf_get_tag_label', $preserved_tags ) ) );
		}

		return $filtered_tags;
	}


	/**
	 * Get a user's most recent membership status in a given level, or most recent status overall if no level ID is specified.
	 *
	 * @param int      $user_id  The user ID.
	 * @param int|bool $level_id The level ID, or false to get most recent status.
	 * @return string|null
	 */
	public function get_membership_level_status( $user_id, $level_id = false ) {

		global $wpdb;

		if ( $level_id ) {
			// Get status for specific level.
			$query = $wpdb->prepare(
				"SELECT status
				FROM {$wpdb->pmpro_memberships_users}
				WHERE user_id = %d
				AND membership_id = %d
				ORDER BY id DESC
				LIMIT 1",
				$user_id,
				$level_id
			);
		} else {
			// Get most recent status from any level.
			$query = $wpdb->prepare(
				"SELECT status
				FROM {$wpdb->pmpro_memberships_users}
				WHERE user_id = %d
				ORDER BY id DESC
				LIMIT 1",
				$user_id
			);
		}

		$level_status = $wpdb->get_var( $query );

		return ! empty( $level_status ) ? $level_status : null;
	}

	/**
	 * Gets the first membership start date for a user.
	 *
	 * @since 3.45.5
	 *
	 * @param int $user_id The user ID.
	 * @return string|null The first membership start date, or null if no memberships are found.
	 */
	public function get_member_joined_date( $user_id ) {

		$levels = pmpro_getMembershipLevelsForUser( $user_id, true );

		if ( empty( $levels ) ) {
			return null;
		}

		// Get the start date of the first membership.
		return date_i18n( get_option( 'date_format' ), intval( $levels[0]->startdate ) );
	}


	/**
	 * Updates user meta after checkout.
	 *
	 * @since 3.45.3
	 *
	 * @param array $post_data The post data.
	 * @return array Post data.
	 */
	public function user_register( $post_data ) {

		$field_map = array(
			'bfirstname'      => 'first_name',
			'blastname'       => 'last_name',
			'bemail'          => 'user_email',
			'username'        => 'user_login',
			'password'        => 'user_pass',
			'baddress1'       => 'pmpro_baddress1',
			'baddress2'       => 'pmpro_baddress2',
			'bcity'           => 'pmpro_bcity',
			'bstate'          => 'pmpro_bstate',
			'bzipcode'        => 'pmpro_bzipcode',
			'bcountry'        => 'pmpro_bcountry',
			'bphone'          => 'pmpro_bphone',
			'CardType'        => 'pmpro_CardType',
			'AccountNumber'   => 'pmpro_AccountNumber',
			'ExpirationMonth' => 'pmpro_ExpirationMonth',
			'ExpirationYear'  => 'pmpro_ExpirationYear',
			'CVV'             => 'pmpro_CVV',
		);

		$post_data = $this->map_meta_fields( $post_data, $field_map );

		return $post_data;
	}


	/**
	 * Updates user's memberships if a linked tag is added/removed.
	 *
	 * @since 3.45.3
	 *
	 * @param int   $user_id   The user ID.
	 * @param array $user_tags The user tags.
	 */
	public function update_membership( $user_id, $user_tags ) {

		// Don't bother if PMPro isn't active.
		if ( ! function_exists( 'pmpro_hasMembershipLevel' ) ) {
			return;
		}

		// Don't run while PMPro is adding / removing levels.
		if ( did_action( 'pmpro_before_change_membership_level' ) && ! did_action( 'pmpro_after_change_membership_level' ) ) {
			return;
		}

		// Update role based on user tags.
		$membership_levels = pmpro_getAllLevels( true );

		if ( empty( $membership_levels ) ) {
			return;
		}

		// Prevent looping.
		remove_action( 'pmpro_after_change_membership_level', array( $this->hooks, 'after_change_membership_level' ) );

		foreach ( $membership_levels as $level ) {

			$settings = get_option( 'wpf_pmp_' . $level->id );

			if ( empty( $settings ) || empty( $settings['tag_link'] ) ) {
				continue;
			}

			$tag_id = $settings['tag_link'][0];

			if ( in_array( $tag_id, $user_tags ) && pmpro_hasMembershipLevel( $level->id, $user_id ) == false ) {

				/**
				 * Filter the start date for the membership.
				 *
				 * Mirrors PMPro's checkout logic so site customizations that use
				 * pmpro_checkout_start_date / pmpro_checkout_end_date also apply here.
				 *
				 * @since 3.47.4.2
				 *
				 * @param string $startdate The calculated start date, formatted for MySQL.
				 * @param int    $user_id   The user ID.
				 * @param object $level     The membership level object.
				 */
				$startdate = apply_filters( 'pmpro_checkout_start_date', "'" . current_time( 'mysql' ) . "'", $user_id, $level );

				// Fix expiration date based on the level settings.
				if ( ! empty( $level->expiration_number ) ) {
					$expiration_timestamp = strtotime(
						'+ ' . $level->expiration_number . ' ' . $level->expiration_period,
						current_time( 'timestamp' )
					);

					if ( false === $expiration_timestamp ) {
						$enddate = 'NULL';
					} elseif ( 'Hour' === $level->expiration_period ) {
						$enddate = date( 'Y-m-d H:i:s', $expiration_timestamp );
					} else {
						$enddate = date( 'Y-m-d 23:59:59', $expiration_timestamp );
					}
				} else {
					$enddate = 'NULL';
				}

				/**
				 * Filter the end date for the membership.
				 *
				 * Mirrors PMPro's checkout logic so site customizations that use
				 * pmpro_checkout_end_date also apply here.
				 *
				 * @since 3.47.4.2
				 *
				 * @param string $enddate    The calculated end date, formatted for MySQL.
				 * @param int    $user_id    The user ID.
				 * @param object $level      The membership level object.
				 * @param string $startdate  The start date, formatted for MySQL.
				 */
				$enddate = apply_filters( 'pmpro_checkout_end_date', $enddate, $user_id, $level, $startdate );

				// Set up level data.
				$level_data = array(
					'user_id'         => $user_id,
					'membership_id'   => $level->id,
					'code_id'         => 0,
					'initial_payment' => $level->initial_payment,
					'billing_amount'  => $level->billing_amount,
					'cycle_number'    => $level->cycle_number,
					'cycle_period'    => $level->cycle_period,
					'billing_limit'   => $level->billing_limit,
					'trial_amount'    => $level->trial_amount,
					'trial_limit'     => $level->trial_limit,
					'startdate'       => $startdate,
					'enddate'         => $enddate,
				);

				// Logger.
				wpf_log( 'info', $user_id, 'Adding user to PMPro membership <a href="' . admin_url( 'admin.php?page=pmpro-membershiplevels&edit=' . $level->id ) . '">' . $level->name . '</a> by tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong>' );

				// Add user to level.
				pmpro_changeMembershipLevel( $level_data, $user_id, 'inactive', null );

			} elseif ( ! in_array( $tag_id, $user_tags ) && pmpro_hasMembershipLevel( $level->id, $user_id ) == true ) {

				// Logger.
				wpf_log( 'info', $user_id, 'Removing user from PMPro membership <a href="' . admin_url( 'admin.php?page=pmpro-membershiplevels&edit=' . $level->id ) . '">' . $level->name . '</a> by tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong>' );

				// Remove user from level.
				pmpro_cancelMembershipLevel( $level->id, $user_id, 'inactive' );

			}
		}

		// Re-add the action.
		add_action( 'pmpro_after_change_membership_level', array( $this->hooks, 'after_change_membership_level' ), 10, 2 );
	}

	/**
	 * Runs when meta data is loaded from the CRM. Updates the start date and expiry date if found.
	 *
	 * @since 3.45.3
	 *
	 * @param int   $user_id   The user ID.
	 * @param array $user_meta The user meta.
	 */
	public function user_updated( $user_id, $user_meta ) {

		global $wpdb;

		if ( ! function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
			return;
		}

		$membership_level = pmpro_getMembershipLevelForUser( $user_id );

		if ( ! empty( $membership_level ) && ! empty( $membership_level->subscription_id ) ) {

			// Start date.
			if ( ! empty( $user_meta['pmpro_start_date'] ) ) {

				$start_date = strtotime( $user_meta['pmpro_start_date'] );

				if ( ! empty( $start_date ) ) {

					$start_date = gmdate( 'Y-m-d 00:00:00', $start_date );

					$wpdb->query(
						$wpdb->prepare(
							"UPDATE $wpdb->pmpro_memberships_users SET `startdate`=%s WHERE `id`=%d",
							array(
								$start_date,
								$membership_level->subscription_id,
							)
						)
					);
				}
			}

			// Expiry date.
			if ( ! empty( $user_meta['pmpro_expiration_date'] ) ) {

				$expiration_date = strtotime( $user_meta['pmpro_expiration_date'] );

				if ( $expiration_date > time() ) {
					// Only set it if it's in the future.
					pmpro_set_expiration_date( $user_id, $membership_level->id, $expiration_date );
				}
			}
		}
	}
}

new WPF_PMPro();
