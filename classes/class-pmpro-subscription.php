<?php

class PMPro_Subscription {
	/**
	 * Create a new PMPro_Subscription object.
	 *
	 * @since TBD.
	 *
	 * @param int $id ID of the subscription to load.
	 */
	function __construct( $id = null ) {
		global $wpdb;
		if ( ! empty( $id ) ) {
			// Get an existing subscription.
			$subscription_data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * 
					FROM $wpdb->pmpro_subscriptions
					WHERE id = %d",
					$id
				),
				OBJECT
			);

			if ( ! empty( $subscription_data ) ) {
				$this->id                          = $subscription_data->id;
				$this->membership_level_id         = $subscription_data->membership_level_id;
				$this->user_id                     = $subscription_data->user_id;
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
		} else {
			// Get a new subscription.
			$this->id                          = '';
			$this->user_id                     = '';
			$this->membership_level_id         = '';
			$this->gateway                     = '';
			$this->gateway_environment         = '';
			$this->subscription_transaction_id = '';
			$this->status                      = '';
			$this->startdate                   = ''; // UTC YYYY-MM-DD HH:MM:SS.
			$this->enddate                     = ''; // UTC YYYY-MM-DD HH:MM:SS.
			$this->next_payment_date           = ''; // UTC YYYY-MM-DD HH:MM:SS.
		}

		return $this;
	}

	/**
	 * Get subscriptions for a user.
	 *
	 * @since TBD.
	 *
	 * @param int|null          $user_id              ID of the user to get subscriptions for. Defaults to current user.
	 * @param array|null        $membership_level_ids Array of membership level IDs to get subscriptions for. Defaults to all.
	 * @param string|array|null $status               Status of the subscription to get. Defaults to active.
	 *
	 * @return array Array of PMPro_Subscription objects.
	 */
	static function get_subscriptions_for_user( $user_id = null, $membership_level_ids = null, $statuses = array( 'active' ) ) {
		global $current_user, $wpdb;

		// Get user_id if none passed.
		if ( empty( $user_id ) ) {
			$user_id = $current_user->ID;
		}
		// If we don't have a valid user, return.
		if ( empty( $user_id ) ) {
			return false;
		}

		// Make sure that level IDs are formatted correctly.
		if ( ! empty( $membership_level_ids ) && ! is_array( $membership_level_ids ) ) {
			$membership_level_ids = array( $membership_level_ids );
		}

		// Make sure that statuses are formatted correctly.
		if ( ! empty( $statuses ) && ! is_array( $statuses ) ) {
			$statuses = array( $statuses );
		}

		$sql_query = $wpdb->prepare( "SELECT id FROM $wpdb->pmpro_subscriptions WHERE user_id = %s", $user_id );
		if ( ! empty( $membership_level_ids ) ) {
			$sql_query .= " AND membership_level_id IN (" . implode( ',', array_map( 'intval', $membership_level_ids ) ) . ")";
		}
		if ( ! empty( $statuses ) ) {
			$sql_query .= " AND status IN ('" . implode( "','", array_map( 'esc_sql', $statuses ) ) . "')";
		}

		// Array returned from this function should be from newest to oldest.
		$sql_query .= " ORDER BY startdate DESC";

		$subscription_ids = $wpdb->get_results( $sql_query );
		if ( empty( $subscription_ids ) ) {
			return array();
		}

		$subscriptions = array();
		foreach ( $subscription_ids as $subscription_id_obj ) {
			$subscription = new PMPro_Subscription( $subscription_id_obj->id );
			// Make sure that we found a subscription in the database.
			if ( ! empty( $subscription->subscription_transaction_id ) ) {
				$subscriptions[] = $subscription;
			}
		}
		return $subscriptions;
	}

	/**
	 * Get the subscription with the given subscription transaction ID.
	 *
	 * @since TBD.
	 *
	 * @param string $subscription_transaction_id Subscription transaction ID to get.
	 * @param string $gateway                     Gateway to get the subscription for.
	 * @param string $gateway_environment         Gateway environment to get the subscription for.
	 *
	 * @return PMPro_Subscription|null PMPro_Subscription object if found, null if not found.
	 */
	static function get_subscription_from_subscription_transaction_id( $subscription_transaction_id, $gateway, $gateway_environment ) {
		global $wpdb;
		// Get the discount code object.
		$subscription_id = $wpdb->get_row(
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

		if ( ! empty( $subscription_id->id ) ) {
			$subscription = new PMPro_Subscription( $subscription_id->id );
			if ( ! empty( $subscription->subscription_transaction_id ) ) {
				// We have a valid subscription.
				return $subscription;
			}
		}
		// Didn't find it.
		return null;
	}

	/**
	 * Create a new subscription.
	 *
	 * @since TBD.
	 *
	 * @param int    $user_id                     ID of the user to create the subscription for.
	 * @param int    $membership_level_id         ID of the membership level to create the subscription for.
	 * @param string $subscription_transaction_id Subscription transaction ID to create the subscription for.
	 * @param string $gateway                     Gateway to create the subscription for.
	 * @param string $gateway_environment         Gateway environment to create the subscription for.
	 *
	 * @return PMPro_Subscription|null PMPro_Subscription object if created, null if not.
	 */
	static function create_subscription( $user_id, $membership_level_id, $subscription_transaction_id, $gateway, $gateway_environment ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			return null;
		}

		$existing_subscription = self::get_subscription_from_subscription_transaction_id( $subscription_transaction_id, $gateway, $gateway_environment );
		if ( ! empty( $existing_subscription ) ) {
			// Subscription already exists.
			return null;
		}
	
		$new_subscription = new PMPro_Subscription();
		$new_subscription->user_id                     = $user_id;
		$new_subscription->membership_level_id         = $membership_level_id;
		$new_subscription->gateway                     = $gateway;
		$new_subscription->gateway_environment         = $gateway_environment;
		$new_subscription->subscription_transaction_id = $subscription_transaction_id;

		// Try to pull as much info as possible directly from the gateway.
		$new_subscription->update_from_gateway();

		// If we are still missing information, fall back on order and membership history.
		if ( empty( $new_subscription->status ) ) {
			$new_subscription->status = pmpro_hasMembershipLevel( $membership_level_id, $user_id ) ? 'active' : 'cancelled';
			$last_order_for_level = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * 
					FROM $wpdb->pmpro_membership_orders
					WHERE membership_id = %s
					ORDER BY timestamp DESC",
					$membership_level_id
				),
				OBJECT
			);
			if ( ! empty( $last_order_for_level->subscription_transaction_id ) && $last_order_for_level->subscription_transaction_id === $subscription_transaction_id
				&& ! empty( $last_order_for_level->gateway ) && $last_order_for_level->gateway === $gateway
				&& ! empty( $last_order_for_level->gateway_environment ) && $last_order_for_level->gateway_environment === $gateway_environment
			) {
				$new_subscription->status = 'active';
			} else {
				$new_subscription->status = 'cancelled';
			}
		}

		if ( empty( $new_subscription->startdate ) ) {
			// Get the earliest order for this subscription.
			// There should be one since we are usually making a subscription from an order.
			$first_order = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * 
					FROM $wpdb->pmpro_membership_orders
					WHERE subscription_transaction_id = %s
					AND gateway = %s
					AND gateway_environment = %s
					ORDER BY timestamp ASC",
					$subscription_transaction_id,
					$gateway,
					$gateway_environment
				),
				OBJECT
			);
			if ( ! empty( $first_order->timestamp ) ) {
				$new_subscription->startdate = $first_order->timestamp;
			}
		}

		if ( $new_subscription->status === 'active' && empty( $new_subscription->next_payment_date ) ) {
			// Calculate their next payment date based on their current membership.
			// TODO: Implement this.

		}

		if ( $new_subscription->status !== 'active' && empty( $new_subscription->enddate ) ) {
			// Get the end date for their old membership. May not work well if they've changed levels a lot.
			// TODO: Implement this.

		}

		$new_subscription->save();
		return $new_subscription;
	}

	/**
	 * Pull subscription info from the gateway.
	 *
	 * @since TBD.
	 */
	function update_from_gateway() {
		$gateway_object = $this->get_gateway_object();
		if ( method_exists( $gateway_object, 'update_subscription_info' ) ) {
			$gateway_object->update_subscription_info( $this );
		}
	}

	/**
	 * Get the next payment date for this subscription.
	 *
	 * @since TBD.
	 *
	 * @param string $format     Format to return the date in.
	 * @param bool   $local_time Whether to return the date in local time or UTC.
	 *
	 * @return string|null Date in the requested format.
	 */
	function get_next_payment_date( $format = 'timestamp', $local_time = true ) {
		return $this->format_subscription_date( $this->next_payment_date, $format, $local_time );

	}

	/**
	 * Get the start date for this subscription.
	 *
	 * @since TBD.
	 *
	 * @param string $format     Format to return the date in.
	 * @param bool   $local_time Whether to return the date in local time or UTC.
	 *
	 * @return string|null Date in the requested format.
	 */
	function get_startdate( $format = 'timestamp', $local_time = true ) {
		return $this->format_subscription_date( $this->startdate, $format, $local_time );
	}

	/**
	 * Get the end date for this subscription.
	 *
	 * @since TBD.
	 *
	 * @param string $format     Format to return the date in.
	 * @param bool   $local_time Whether to return the date in local time or UTC. 
	 *
	 * @return string|null Date in the requested format.
	 */
	function get_enddate( $format = 'timestamp', $local_time = true ) {
		return $this->format_subscription_date( $this->enddate, $format, $local_time );
	}

	/**
	 * Format a date.
	 *
	 * @since TBD.
	 *
	 * @param string $date       Date to format.
	 * @param string $format     Format to return the date in.
	 * @param bool   $local_time Whether to return the date in local time or UTC.
	 *
	 * @return string|null Date in the requested format.
	 */
	private function format_subscription_date( $date, $format = 'timestamp', $local_time = true ) {
		if ( empty( $date ) || $date == '0000-00-00 00:00:00' ) {
			return null;
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

	/**
	 * Returns the PMProGateway object for this subscription.
	 *
	 * @since TBD.
	 */
	function get_gateway_object() {
		$classname = 'PMProGateway';	// Default test gateway.
		if ( ! empty( $this->gateway ) && $this->gateway !== 'free' ) {
			$classname .= '_' . $this->gateway;	// Adding the gateway suffix.
		}

		if ( class_exists( $classname ) && isset( $this->gateway ) ) {
			return new $classname( $this->gateway );
		} else {
			return null;
		}
	}


	/**
	 * Save the subscription.
	 *
	 * @since TBD.
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
				'user_id'                    => $this->user_id,
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
				'%d',		//user_id
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
	 *
	 * @since TBD.
	 *
	 * @return bool True if the subscription was canceled successfully.
	 */
	function cancel() {
		// Cancel subscription at gateway first.
		$gateway_object = $this->get_gateway_object();

		if ( method_exists( $gateway_object, 'cancel_subscription' ) ) {
			$result = $gateway_object->cancel_subscription( $this );
		} else {
			// Legacy cancel code.
			// TODO: Make this work if gateway doesn't support cancel_subscription()
			$morder = $this->get_last_order();
			if ( is_object( $gateway_object ) && is_a( $morder, 'MemberOrder' ) ) {
				$result = $gateway_object->cancel( $morder );
			} else {
				$result = false;
			}
		}

		/*
		 * @todo We should probably send an email when the subscription cancellation fails. Gateway or MemberOrder class may already be sending a sub cancel failure email though, something to look into further. May want to remove that and use the subscription class for that.
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
		*/
		$this->save();

		return $result;
	}

} // end of class