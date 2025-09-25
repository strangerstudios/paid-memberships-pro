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
	 * Syncs custom fields for a membership level when a user is added to the level or via the batch process.
	 *
	 * @since unknown
	 *
	 * @param int         $user_id The user ID.
	 * @param object|bool $membership_level The user membership level object, or false if no level is found.
	 */
	public function sync_membership_level_fields( $user_id, $membership_level ) {

		if ( $membership_level ) {

			$level_id = $membership_level->id;

			// Prepare the data for all available fields.
			$update_data = array(
				'pmpro_status'             => $this->get_membership_level_status( $user_id, $level_id ),
				'pmpro_joined_date'        => $this->get_member_joined_date( $user_id ),
				'pmpro_start_date'         => date_i18n( get_option( 'date_format' ), intval( $membership_level->startdate ) ),
				'pmpro_next_payment_date'  => pmpro_next_payment( $user_id ) ? date_i18n( get_option( 'date_format' ), intval( pmpro_next_payment( $user_id ) ) ) : null,
				'pmpro_expiration_date'    => ! empty( $membership_level->enddate ) ? date_i18n( get_option( 'date_format' ), intval( $membership_level->enddate ) ) : null,
				'pmpro_membership_level'   => $membership_level->name,
				'pmpro_subscription_price' => $membership_level->billing_amount,
			);

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
			$update_data = array(
				'pmpro_membership_level'   => null,
				'pmpro_expiration_date'    => null,
				'pmpro_subscription_price' => null,
				'pmpro_next_payment_date'  => null,
				'pmpro_status'             => $this->get_membership_level_status( $user_id ),
			);
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

			if ( $settings['remove_tags'] ) {
				$remove_keys[] = 'apply_tags';
			}
		} elseif ( 'cancelled' === $status ) {

			// Cancelled.

			$apply_keys  = array( 'apply_tags_cancelled' );
			$remove_keys = array( 'tag_link' );

			if ( $settings['remove_tags'] ) {
				$remove_keys[] = 'apply_tags';
			}
		} elseif ( 'inactive' === $status ) {

			// Inactive.

			$remove_keys = array( 'tag_link' );

			if ( $settings['remove_tags'] ) {
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
		$membership_levels = get_option( 'wpf_pmpro_tag_link' );

		if ( empty( $membership_levels ) ) {
			return;
		}

		foreach ( $membership_levels as $level ) {

			$level_id = str_replace( 'wpf_pmp_', '', $level[0] );
			$tag_id   = $level[1];

			if ( empty( $tag_id ) ) {
				continue;
			}

			if ( in_array( $tag_id, $user_tags ) && pmpro_hasMembershipLevel( $level_id, $user_id ) == false ) {

				$pmpro_level = pmpro_getLevel( $level_id );

				// Set up level data.
				$level_data = array(
					'user_id'         => $user_id,
					'membership_id'   => $level_id,
					'code_id'         => 0,
					'initial_payment' => $pmpro_level->initial_payment,
					'billing_amount'  => $pmpro_level->billing_amount,
					'cycle_number'    => $pmpro_level->cycle_number,
					'cycle_period'    => $pmpro_level->cycle_period,
					'billing_limit'   => $pmpro_level->billing_limit,
					'trial_amount'    => $pmpro_level->trial_amount,
					'trial_limit'     => $pmpro_level->trial_limit,
					'startdate'       => current_time( 'mysql' ),
					'enddate'         => '0000-00-00 00:00:00',
				);

				// Logger.
				wpf_log( 'info', $user_id, 'Adding user to PMPro membership <a href="' . admin_url( 'admin.php?page=pmpro-membershiplevels&edit=' . $level_id ) . '">' . $pmpro_level->name . '</a> by tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong>' );

				// Add user to level.
				pmpro_changeMembershipLevel( $level_data, $user_id, 'inactive', null );

			} elseif ( ! in_array( $tag_id, $user_tags ) && pmpro_hasMembershipLevel( $level_id, $user_id ) == true ) {

				$pmpro_level = pmpro_getLevel( $level_id );

				// Logger.
				wpf_log( 'info', $user_id, 'Removing user from PMPro membership <a href="' . admin_url( 'admin.php?page=pmpro-membershiplevels&edit=' . $level_id ) . '">' . $pmpro_level->name . '</a> by tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong>' );

				// Remove user from level.
				pmpro_cancelMembershipLevel( $level_id, $user_id, 'inactive' );

			}
		}
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
