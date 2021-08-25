<?php

class PMPro_Subscription {
	/**
	 * Create a new PMPro_Subscription object.
	 *
	 * @param MemberOrder $morder to get subscription for.
	 */
	function __construct( $morder = null ) {
		if ( ! empty( $morder ) ) {
			$this->get_subscription_from_order( $morder );
		} else {
			$this->get_empty_subscription();
		}
	}

	// **************************************
	// Getters for PMPro_Subscription object
	// **************************************
	/**
	 * Populate object with default values.
	 */
	function get_empty_subscription() {
		$this->id                          = '';
		$this->membership_level_id         = '';
		$this->gateway                     = '';
		$this->gateway_environment         = '';
		$this->subscription_transaction_id = '';
		$this->status                      = 'active';
		$this->startdate                   = ''; // UTC YYYY-MM-DD HH:MM:SS.
		$this->enddate                     = ''; // UTC YYYY-MM-DD HH:MM:SS.
		$this->next_payment_date           = ''; // UTC YYYY-MM-DD HH:MM:SS.

		return $this;
	}

	/**
	 * Populate object with subscription data for a specified user and level.
	 *
	 * @param int $user_id of user to get subscription data for.
	 * @param int $membership_id of level to get subscription data for.
	 */
	function get_subscription_by_user( $user_id = null, $membership_id = null ) {
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
		$this->get_subscription_from_order( $morder );
	}

	/**
	 * Populate object with subscription data for a given order.
	 *
	 * @param MemberOrder $morder to get subscription data for.
	 */
	function get_subscription_from_order( $morder ) {
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
	 * Populate object with subscription data.
	 *
	 * @param string $subscription_transaction_id of subscription at payment gateway.
	 * @param string $gateway that the payment subscription was created at.
	 * @param string $gateway_environment that the subscription was created in.
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
			$this->membership_level_id         = $subscription_data->membership_level_id;
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

	// **************************************
	// Building/Updating PMPro_Subscription
	// **************************************
	/**
	 * Create a new PMPro_Subscription object or update an
	 * existing subscription by pulling information from an order.
	 *
	 * @param MemberOrder $morder to pull data from.
	 */
	static function update_subscription_from_order( $morder ) {
		global $wpdb;
		if ( ! is_a( $morder, 'MemberOrder' ) ) {
			return false;
		}

		if ( self::subscription_exists_for_order( $morder ) ) {
			$subscription = new PMPro_Subscription( $morder );
		} else {
			$subscription                              = new PMPro_Subscription();
			$subscription->gateway                     = $morder->gateway;
			$subscription->gateway_environment         = $morder->gateway_environment;
			$subscription->subscription_transaction_id = $morder->subscription_transaction_id;
			$subscription->membership_level_id         = $morder->membership_id;
			$subscription->startdate                   = $morder->datetime;
		}
		// Get next payment date.
		if ( ! empty( $morder->ProfileStartDate ) ) {
			$subscription->next_payment_date = $morder->ProfileStartDate;
		} else {
			// Get next payment date by querying gateway.
			$subscription->get_next_payment_date = $subscription->get_next_payment_date( 'Y-m-d H:i:s', false, true );
		}

		$subscription->save();
		return $subscription;
	}

	/**
	 * Get the next payment date for this subscription.
	 *
	 * @param string $format to return the next payment date in.
	 * @param bool   $local_time set to false for date in GMT.
	 * @param bool   $query_gateway for next payment date.
	 */
	function get_next_payment_date( $format = 'timestamp', $local_time = true, $query_gateway = false ) {
		if ( $query_gateway ) {
			// Get next payment date by querying gateway.
			$gateway_object = $this->get_gateway_object();
			if ( is_object( $gateway_object ) ) {
				$this->next_payment_date = $gateway_object->get_next_payment_date( $this );
			} else {
				$this->next_payment_date = '0000-00-00 00:00:00';
			}
		}
		return $this->format_subscription_date( $this->next_payment_date, $format, $local_time );
	}

	// **************************************
	// Other Getters
	// **************************************
	/**
	 * Magic method to get PMPro_Subscription properties.
	 *
	 * @param string $key property to get.
	 */
	function __get( $key ) {
		if ( isset( $this->$key ) ) {
			$value = $this->$key;
		} else {
			$value = '';
		}		
		return $value;
	}

	/**
	 * Return whether or not a subscription exists.
	 *
	 * @param string $subscription_transaction_id of subscription at payment gateway.
	 * @param string $gateway that the payment subscription was created at.
	 * @param string $gateway_environment that the subscription was created in.
	 * @return bool
	 */
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
	 * Return whether or not a subscription exists for a given order.
	 *
	 * @param MemberOrder $morder to check for subscription.
	 * @return bool
	 */
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

	/**
	 * Returns the most recent MemberOrder object for this subscription.
	 */
	function get_last_order() {
		$morder = new MemberOrder();
		$morder->getLastMemberOrderBySubscriptionTransactionID( $this->subscription_transaction_id );
		return $morder;
	}

	/**
	 * Returns the PMProGateway object for this subscription.
	 */
	function get_gateway_object() {
		$classname = 'PMProGateway';	// Default test gateway.
		if ( ! empty( $this->gateway ) && $this->gateway != 'free' ) {
			$classname .= '_' . $this->gateway;	// Adding the gateway suffix.
		}

		if ( class_exists( $classname ) && isset( $this->gateway ) ) {
			return new $classname( $this->gateway );
		} else {
			return null;
		}
	}

	/**
	 * Get the startdate for this subscription.
	 *
	 * @param string $format to return the startdate in.
	 * @param bool   $local_time set to false for date in GMT.
	 */
	function get_startdate( $format = 'timestamp', $local_time = true ) {
		return $this->format_subscription_date( $this->startdate, $format, $local_time );
	}

	/**
	 * Get the enddate for this subscription.
	 *
	 * @param string $format to return the enddate in.
	 * @param bool   $local_time set to false for date in GMT.
	 */
	function get_enddate( $format = 'timestamp', $local_time = true ) {
		return $this->format_subscription_date( $this->enddate, $format, $local_time );
	}

	/**
	 * Factoring code out of date getters.
	 *
	 * Function set to protected in case we later move out of class.
	 * If we do, we only need to make changes within this class file.
	 *
	 * @param string $date to format.
	 * @param string $format to return the next payment date in.
	 * @param bool   $local_time set to false for date in GMT.
	 */
	protected function format_subscription_date( $date, $format = 'timestamp', $local_time = true ) {
		if ( empty( $date ) || $date == '0000-00-00 00:00:00' ) {
			return false;
		} elseif ( 'timestamp' === $format ) {
			$format = 'U';
		} elseif ( 'date_format' === $format ) {
			$format = get_option( 'date_format' );
		}

		if ( $local_time ) {
			return get_date_from_gmt( $date, $format ); // Local time.
		} else {
			return date( $format, strtotime( $date ) ); // GMT.
		}
	}

	// **************************************
	// Save & Cancel
	// **************************************
	/**
	 * Saves or updates a subscription.
	 */
	function save() {
		global $wpdb;

		if (
			empty( $this->gateway ) ||
			empty( $this->gateway_environment ) ||
			empty( $this->subscription_transaction_id )
		) {
			return false;
		}

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
				'membership_level_id'        => $this->membership_level_id,
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
				'%d',		//membership_level_id
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
		return $this;
	}

	/**
	 * Cancels this subscription in PMPro and at the payment gateway.
	 */
	function cancel( $cancel_at_gateway = true ) {
		// Cancel subscription at gateway first.
		if ( ! empty( $cancel_at_gateway ) ) {
			$gateway_object = $this->get_gateway_object();
			$morder = $this->get_last_order();
			if ( is_object( $gateway_object ) && is_a( $morder, 'MemberOrder' ) ) {
				$result = $gateway_object->cancel( $morder );
			} else {
				$result = false;
			}

			if ( $result == false && is_a( $morder, 'MemberOrder' ) ) {
				// Notify the admin.
				$order_user = get_userdata($morder->user_id);
				$pmproemail = new PMProEmail();
				$pmproemail->template      = 'subscription_cancel_error';
				$pmproemail->data          = array( 'body' => '<p>' . sprintf( __( 'There was an error canceling the subscription for user with ID=%s. You will want to check your payment gateway to see if their subscription is still active.', 'paid-memberships-pro' ), strval( $this->user_id ) ) . '</p><p>Error: ' . $this->error . '</p>' );
				$pmproemail->data['body'] .= '<p>' . __( 'User Email', 'paid-memberships-pro' ) . ': ' . $order_user->user_email . '</p>';
				$pmproemail->data['body'] .= '<p>' . __( 'Username', 'paid-memberships-pro' ) . ': ' . $order_user->user_login . '</p>';
				$pmproemail->data['body'] .= '<p>' . __( 'User Display Name', 'paid-memberships-pro' ) . ': ' . $order_user->display_name . '</p>';
				$pmproemail->data['body'] .= '<p>' . __( 'Order', 'paid-memberships-pro' ) . ': ' . $morder->code . '</p>';
				$pmproemail->data['body'] .= '<p>' . __( 'Gateway', 'paid-memberships-pro' ) . ': ' . $morder->gateway . '</p>';
				$pmproemail->data['body'] .= '<p>' . __( 'Subscription Transaction ID', 'paid-memberships-pro' ) . ': ' . $morder->subscription_transaction_id . '</p>';
				$pmproemail->data['body'] .= '<hr />';
				$pmproemail->data['body'] .= '<p>' . __( 'Edit User', 'paid-memberships-pro' ) . ': ' . esc_url( add_query_arg( 'user_id', $morder->user_id, self_admin_url( 'user-edit.php' ) ) ) . '</p>';
				$pmproemail->data['body'] .= '<p>' . __( 'Edit Order', 'paid-memberships-pro' ) . ': ' . esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 'order' => $morder->id ), admin_url( 'admin.php' ) ) ) . '</p>';
				$pmproemail->sendEmail( get_bloginfo( 'admin_email' ) );
			}
		} else {
			$result = true;
		}

		// Cancel PMPro Subscription in database unless already cancelled.
		if ( $this->status != 'cancelled' ) {
			$this->status  = 'cancelled'; // TODO: What should we do if $result is false?
			$this->enddate = current_time( 'Y-m-d H:i:s', true ); // GMT.
		}
		$this->save();

		return $result;
	}

} // end of class