<?php

class PMPro_Subscription {

	function __construct( $morder = null ) {
		if ( ! empty( $morder ) ) {
			$this->get_subscription_by_order( $morder );
		} else {
			$this->get_empty_subscription();
		}
	}

	function __get( $key ) {
		if ( isset( $this->$key ) ) {
			$value = $this->$key;
		} else {
			$value = '';
		}		
		return $value;
	}

	function get_empty_subscription() {
		$this->id                          = '';
		$this->mu_id                       = '';
		$this->gateway                     = '';
		$this->gateway_environment         = '';
		$this->subscription_transaction_id = '';
		$this->status                      = 'active';
		$this->startdate                   = '';
		$this->enddate                     = '';
		$this->next_payment_date           = '';

		return $this;
	}

	function get_subscription_for_user( $user_id = null, $membership_id = null ) {
		global $current_user;
		// Get user_id if none passed.
		if ( empty( $user_id ) ) {
			$user_id = $current_user->ID;
		}
		// If we don't have a valid user, return.
		if ( empty( $user_id ) ) {
			return false;
		}
		// Get membership_id if none passed.
		if ( empty( $membership_id ) ) {
			$membership_level = pmpro_getMembershipLevelForUser( $user_id );
			$membership_id    = $membership_level->id;
		}
		// If we don't have a valid membership level, return.
		if ( empty( $membership_id ) ) {
			return false;
		}
		// Get subscription from order.
		$morder = new MemberOrder();
		$morder->getLastMemberOrder( $user_id, 'success', $membership_id );
		$this->get_subscription_by_order( $morder );
	}

	function get_subscription_by_order( $morder ) {
		// Get order object if ID passed.
		if ( is_numeric( $morder ) ) {
			$morder = new MemberOrder( $morder );
		}
		// Check that we have a valid order.
		if (
			! is_a( $morder, 'MemberOrder' ) ||
			empty( $morder->subscription_transaction_id ) ||
			empty( $morder->gateway ) ||
			empty( $morder->gateway_environment )
		) {
			return false;
		}
		// Get the subscription.
		return $this->get_subscription( $morder->subscription_transaction_id, $morder->gateway, $morder->gateway_environment );
	}

	/**
	 * Function to populate a pmpro_subscription object from a subscription_transaction_id,
	 * gateway, and gateway_environment.
	 */
	function get_subscription( $subscription_transaction_id, $gateway, $gateway_environment ) {
		global $wpdb;
		// Get the discount code object.
		$subscription_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * 
				FROM $wpdb->pmpro_subscriptions
				WHERE subscription_transaction_id = '%s'
				AND gateway = '%s'
				AND gateway_environment = '%s'",
				$subscription_transaction_id,
				$gateway,
				$gateway_environment
			),
			OBJECT
		);

		if ( ! empty( $subscription_data ) ) {
			$this->id                          = $subscription_data->id;
			$this->mu_id                       = $subscription_data->mu_id;
			$this->gateway                     = $subscription_data->gateway;
			$this->gateway_environment         = $subscription_data->gateway_environment;  
			$this->subscription_transaction_id = $subscription_data->subscription_transaction_id;
			$this->status                      = $subscription_data->status;
			$this->startdate                   = $subscription_data->startdate;
			$this->enddate                     = $subscription_data->enddate;
			$this->next_payment_date           = $subscription_data->next_payment_date;
		} else {
			return null;
		}

		return $this;
	}

	static function build_subscription_from_order( $morder ) {
		global $wpdb;
		if ( ! is_a( $morder, 'MemberOrder' ) ) {
			return false;
		}
		$subscription                              = new PMPro_Subscription();
		$subscription->gateway                     = $morder->gateway;
		$subscription->gateway_environment         = $morder->gateway_environment;
		$subscription->subscription_transaction_id = $morder->subscription_transaction_id;
		$subscription->startdate                   = current_time( 'Y-m-d H:i:s' );

		// Link subscription to pmpro_memberships_users table.
		$subscription->mu_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id 
				FROM $wpdb->pmpro_memberships_users
				WHERE user_id = '%d'
				AND membership_id = '%d'
				AND status = 'active'
				ORDER BY startdate DESC LIMIT 1",
				intval( $morder->user_id ),
				intval( $morder->membership_id )
			)
		);

		// Get next payment date.
		if ( ! empty( $morder->ProfileStartDate ) ) {
			$subscription->next_payment_date = $morder->ProfileStartDate;
		}

		$subscription->save();
		return $subscription;
	}

	static function subscription_exists_for_order( $morder ) {
		// Get order object if ID passed.
		if ( is_numeric( $morder ) ) {
			$morder = new MemberOrder( $morder );
		}
		// Check that we have a valid order.
		if (
			! is_a( $morder, 'MemberOrder' ) ||
			empty( $morder->subscription_transaction_id ) ||
			empty( $morder->gateway ) ||
			empty( $morder->gateway_environment )
		) {
			return false;
		}
		// Get the subscription.
		return self::subscription_exists( $morder->subscription_transaction_id, $morder->gateway, $morder->gateway_environment );
	}

	static function subscription_exists( $subscription_transaction_id, $gateway, $gateway_environment ) {
		global $wpdb;
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) 
				FROM $wpdb->pmpro_subscriptions
				WHERE subscription_transaction_id = '%s'
				AND gateway = '%s'
				AND gateway_environment = '%s'",
				$subscription_transaction_id,
				$gateway,
				$gateway_environment
			)
		);
		return '0' !== $count;
	}

	/**
	 * Save or update a subscription.
	 */
	function save() {
		global $wpdb;

		if ( empty( $this->id ) ) {
			$before_action = 'pmpro_create_subscription';
			$after_action  = 'pmpro_created_subscription';
		} else {
			$before_action = 'pmpro_update_subscription';
			$after_action  = 'pmpro_updated_subscription';
		}

		do_action( $before_action, $this );

		$wpdb->replace(
			$wpdb->pmpro_subscriptions,
			array(
				'id'                         => $this->id,
				'mu_id'                      => $this->mu_id,
				'gateway'                    => $this->gateway,
				'gateway_environment'        => $this->gateway_environment,
				'subscription_transaction_id'=> $this->subscription_transaction_id,
				'status'                     => $this->status,
				'startdate'                  => $this->startdate,
				'enddate'                    => $this->enddate,
				'next_payment_date'          => $this->next_payment_date,
			),
			array(
				'%d',		//id
				'%d',		//mu_id
				'%s',		//gateway
				'%s',		//gateway_environment
				'%s',		//subscription_transaction_id
				'%s',		//status
				'%s',		//startdate
				'%s',		//enddate
				'%s',		//next_payment_date
			)
		);

		if ( $wpdb->insert_id ) {
			$this->id = $wpdb->insert_id;
		}

		do_action( $after_action, $this );
	}

	function cancel() {
		$this->status = 'cancelled';
		$this->enddate = current_time('Y-m-d H:i:s');
		$this->save();
		return $this;
	}

} // end of class