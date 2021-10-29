<?php

/**
 * The PMPro Subscription object.
 *
 * @since TBD
 */
class PMPro_Subscription {

	/**
	 * The subscription ID.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public $id = 0;

	/**
	 * The subscription user ID.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public $user_id = 0;

	/**
	 * The subscription membership level ID.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public $membership_level_id = 0;

	/**
	 * The subscription gateway.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $gateway = '';

	/**
	 * The subscription gateway environment.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $gateway_environment = '';

	/**
	 * The subscription subscription transaction id.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $subscription_transaction_id = '';

	/**
	 * The subscription status.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $status = '';

	/**
	 * The subscription start date (UTC YYYY-MM-DD HH:MM:SS).
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $startdate = '';

	/**
	 * The subscription end date (UTC YYYY-MM-DD HH:MM:SS).
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $enddate = '';

	/**
	 * The subscription next payment date (UTC YYYY-MM-DD HH:MM:SS).
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $next_payment_date = '';

	/**
	 * Create a new PMPro_Subscription object.
	 *
	 * @since TBD
	 *
	 * @param null|int|array|object $subscription The ID of the subscription to set up or the subscription data to load.
	 *                                            Leave empty for a new subscription.
	 */
	public function __construct( $subscription = null ) {
		global $wpdb;

		if ( empty( $subscription ) ) {
			return;
		}

		$subscription_data = [];

		if ( is_numeric( $subscription ) ) {
			// Get an existing subscription.
			$subscription_data = $wpdb->get_row(
				$wpdb->prepare(
					"
						SELECT *
						FROM $wpdb->pmpro_subscriptions
						WHERE id = %d
					",
					$subscription
				),
				ARRAY_A
			);
		} elseif ( is_array( $subscription ) ) {
			$subscription_data = $subscription;
		} elseif ( is_object( $subscription ) ) {
			$subscription_data = get_object_vars( $subscription );
		} else {
			// Invalid $subscription so there's nothing we can do.
			return;
		}

		$int_columns = [
			'id'                  => true,
			'user_id'             => true,
			'membership_level_id' => true,
		];

		if ( ! empty( $subscription_data ) ) {
			foreach ( $subscription_data as $arg => $value ) {
				if ( isset( $int_columns[ $arg ] ) ) {
					$value = (int) $value;
				}

				$this->{$arg} = $value;
			}
		}
	}

	/**
	 * Get the subscription object based on query arguments.
	 *
	 * @param int|array $args The query arguments to use or the subscription ID.
	 *
	 * @return PMPro_Subscription|null The subscription objects or null if not found.
	 */
	public static function get_subscription( $args = [] ) {
		// At least one argument is required.
		if ( empty( $args ) ) {
			return null;
		}

		if ( is_numeric( $args ) ) {
			$args = [
				'id' => $args,
			];
		}

		// Invalid arguments.
		if ( ! is_array( $args ) ) {
			return null;
		}

		// Force returning of one subscription.
		$args['limit'] = 1;

		// Get the subscriptions using query arguments.
		$subscriptions = self::get_subscriptions( $args );

		// Check if we found any subscriptions.
		if ( empty( $subscriptions ) ) {
			return null;
		}

		// Get the first subscription in the array.
		return reset( $subscriptions );
	}

	/**
	 * Get the list of subscription objects based on query arguments.
	 *
	 * @param array $args The query arguments to use.
	 *
	 * @return PMPro_Subscription[] The list of subscription objects.
	 */
	public static function get_subscriptions( array $args = [] ) {
		global $wpdb;

		$sql_query = "SELECT id FROM $wpdb->pmpro_subscriptions";

		$prepared = [];
		$where    = [];
		$limit    = isset( $args['limit'] ) ? $args['limit'] : 100;

		/*
		 * Now filter the query based on the arguments provided.
		 *
		 * isset( $arg ) && null !== $arg is meant to deal with $args['arg'] = null usage
		 * while still supporting $args['arg'] = ''.
		 */

		// Filter by ID(s).
		if ( isset( $args['id'] ) && null !== $args['id'] ) {
			if ( ! is_array( $args['id'] ) ) {
				$where[]    = 'id = %d';
				$prepared[] = $args['id'];
			} else {
				$where[]  = 'id IN ( ' . implode( ', ', array_fill( 0, count( $args['id'] ), '%d' ) ) . ' )';
				$prepared = array_merge( $prepared, $args['id'] );
			}
		}

		// Filter by user ID(s).
		if ( isset( $args['user_id'] ) && null !== $args['user_id'] ) {
			if ( ! is_array( $args['user_id'] ) ) {
				$where[]    = 'user_id = %d';
				$prepared[] = $args['user_id'];
			} else {
				$where[]  = 'user_id IN ( ' . implode( ', ', array_fill( 0, count( $args['user_id'] ), '%d' ) ) . ' )';
				$prepared = array_merge( $prepared, $args['user_id'] );
			}
		}

		// Filter by membership level ID(s).
		if ( isset( $args['membership_level_id'] ) && null !== $args['membership_level_id'] ) {
			if ( ! is_array( $args['membership_level_id'] ) ) {
				$where[]    = 'membership_level_id = %d';
				$prepared[] = $args['membership_level_id'];
			} else {
				$where[]  = 'membership_level_id IN ( ' . implode( ', ', array_fill( 0, count( $args['membership_level_id'] ), '%d' ) ) . ' )';
				$prepared = array_merge( $prepared, $args['membership_level_id'] );
			}
		}

		// Filter by status(es).
		if ( isset( $args['status'] ) && null !== $args['status'] ) {
			if ( ! is_array( $args['status'] ) ) {
				$where[]    = 'status = %s';
				$prepared[] = $args['status'];
			} else {
				$where[]  = 'status IN ( ' . implode( ', ', array_fill( 0, count( $args['status'] ), '%s' ) ) . ' )';
				$prepared = array_merge( $prepared, $args['status'] );
			}
		}

		// Filter by subscription transaction ID(s).
		if ( isset( $args['subscription_transaction_id'] ) && null !== $args['subscription_transaction_id'] ) {
			if ( ! is_array( $args['subscription_transaction_id'] ) ) {
				$where[]    = 'subscription_transaction_id = %s';
				$prepared[] = $args['subscription_transaction_id'];
			} else {
				$where[]  = 'subscription_transaction_id IN ( ' . implode( ', ', array_fill( 0, count( $args['subscription_transaction_id'] ), '%s' ) ) . ' )';
				$prepared = array_merge( $prepared, $args['subscription_transaction_id'] );
			}
		}

		// Filter by gateway(s).
		if ( isset( $args['gateway'] ) && null !== $args['gateway'] ) {
			if ( ! is_array( $args['gateway'] ) ) {
				$where[]    = 'gateway = %s';
				$prepared[] = $args['gateway'];
			} else {
				$where[]  = 'gateway IN ( ' . implode( ', ', array_fill( 0, count( $args['gateway'] ), '%s' ) ) . ' )';
				$prepared = array_merge( $prepared, $args['gateway'] );
			}
		}

		// Filter by gateway environment(s).
		if ( isset( $args['gateway_environment'] ) && null !== $args['gateway_environment'] ) {
			if ( ! is_array( $args['gateway_environment'] ) ) {
				$where[]    = 'gateway_environment = %s';
				$prepared[] = $args['gateway_environment'];
			} else {
				$where[]  = 'gateway_environment IN ( ' . implode( ', ', array_fill( 0, count( $args['gateway_environment'] ), '%s' ) ) . ' )';
				$prepared = array_merge( $prepared, $args['gateway_environment'] );
			}
		}

		// Maybe filter the data.
		if ( $where ) {
			$sql_query .= ' WHERE ' . implode( ' AND ', $where );
		}

		// Maybe limit the data.
		if ( $limit ) {
			$sql_query .= ' LIMIT %d';
			$prepared[] = $limit;
		}

		$sql_query .= ' ORDER BY startdate DESC';

		// Maybe prepare the query.
		if ( $prepared ) {
			$sql_query = $wpdb->prepare( $sql_query, $prepared );
		}

		$subscription_ids = $wpdb->get_col( $sql_query );

		if ( empty( $subscription_ids ) ) {
			return [];
		}

		$subscriptions = [];

		foreach ( $subscription_ids as $subscription_id ) {
			$subscription = new PMPro_Subscription( $subscription_id );

			// Make sure the subscription object is valid.
			if ( ! empty( $subscription->id ) ) {
				$subscriptions[] = $subscription;
			}
		}

		return $subscriptions;
	}

	/**
	 * Get subscriptions for a user.
	 *
	 * @since TBD
	 *
	 * @param int|null          $user_id             ID of the user to get subscriptions for. Defaults to current user.
	 * @param int|array|null    $membership_level_id The membership level ID(s) to get subscriptions for. Defaults to all.
	 * @param string|array|null $status              The status(es) of the subscription to get. Defaults to active.
	 *
	 * @return PMPro_Subscription[] The list of subscription objects.
	 */
	public static function get_subscriptions_for_user( $user_id = null, $membership_level_id = null, $status = [ 'active' ] ) {
		// Get user_id if none passed.
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		// If we don't have a valid user, return.
		if ( empty( $user_id ) ) {
			return false;
		}

		// Filter by user ID.
		$args = [
			'user_id' => $user_id,
		];

		// Filter by membership level ID(s).
		if ( $membership_level_id ) {
			$args['membership_level_id'] = $membership_level_id;
		}

		// Filter by status(es).
		if ( $status ) {
			$args['status'] = $status;
		}

		return self::get_subscriptions( $args );
	}

	/**
	 * Get the subscription with the given subscription transaction ID.
	 *
	 * @since TBD
	 *
	 * @param string $subscription_transaction_id Subscription transaction ID to get.
	 * @param string $gateway                     Gateway to get the subscription for.
	 * @param string $gateway_environment         Gateway environment to get the subscription for.
	 *
	 * @return PMPro_Subscription|null PMPro_Subscription object if found, null if not found.
	 */
	public static function get_subscription_from_subscription_transaction_id( $subscription_transaction_id, $gateway, $gateway_environment ) {
		// Require subscriptio transaction ID.
		if ( empty( $subscription_transaction_id ) ) {
			return null;
		}

		// Filter by args specified.
		$args = [
			'subscription_transaction_id' => $subscription_transaction_id,
			'gateway'                     => $gateway,
			'gateway_environment'         => $gateway_environment,
		];

		return self::get_subscription( $args );
	}

	/**
	 * Create a new subscription.
	 *
	 * @since TBD
	 *
	 * @param int    $user_id                     ID of the user to create the subscription for.
	 * @param int    $membership_level_id         ID of the membership level to create the subscription for.
	 * @param string $subscription_transaction_id Subscription transaction ID to create the subscription for.
	 * @param string $gateway                     Gateway to create the subscription for.
	 * @param string $gateway_environment         Gateway environment to create the subscription for.
	 *
	 * @return PMPro_Subscription|null PMPro_Subscription object if created, null if not.
	 */
	public static function create_subscription( $user_id, $membership_level_id, $subscription_transaction_id, $gateway, $gateway_environment ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			return null;
		}

		$existing_subscription = self::get_subscription_from_subscription_transaction_id( $subscription_transaction_id, $gateway, $gateway_environment );
		if ( ! empty( $existing_subscription ) ) {
			// Subscription already exists.
			return null;
		}

		$new_subscription                              = new PMPro_Subscription();
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
			$last_order_for_level     = $wpdb->get_row( $wpdb->prepare( "SELECT * 
					FROM $wpdb->pmpro_membership_orders
					WHERE membership_id = %s
					ORDER BY timestamp DESC", $membership_level_id ), OBJECT );
			if ( ! empty( $last_order_for_level->subscription_transaction_id ) && $last_order_for_level->subscription_transaction_id === $subscription_transaction_id && ! empty( $last_order_for_level->gateway ) && $last_order_for_level->gateway === $gateway && ! empty( $last_order_for_level->gateway_environment ) && $last_order_for_level->gateway_environment === $gateway_environment ) {
				$new_subscription->status = 'active';
			} else {
				$new_subscription->status = 'cancelled';
			}
		}

		if ( empty( $new_subscription->startdate ) ) {
			// Get the earliest order for this subscription.
			// There should be one since we are usually making a subscription from an order.
			$first_order = $wpdb->get_row( $wpdb->prepare( "SELECT * 
					FROM $wpdb->pmpro_membership_orders
					WHERE subscription_transaction_id = %s
					AND gateway = %s
					AND gateway_environment = %s
					ORDER BY timestamp ASC", $subscription_transaction_id, $gateway, $gateway_environment ), OBJECT );
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
	 * @since TBD
	 */
	public function update_from_gateway() {
		$gateway_object = $this->get_gateway_object();

		if ( method_exists( $gateway_object, 'update_subscription_info' ) ) {
			$gateway_object->update_subscription_info( $this );
		}
	}

	/**
	 * Get the next payment date for this subscription.
	 *
	 * @since TBD
	 *
	 * @param string $format     Format to return the date in.
	 * @param bool   $local_time Whether to return the date in local time or UTC.
	 *
	 * @return string|null Date in the requested format.
	 */
	public function get_next_payment_date( $format = 'timestamp', $local_time = true ) {
		return $this->format_subscription_date( $this->next_payment_date, $format, $local_time );
	}

	/**
	 * Get the start date for this subscription.
	 *
	 * @since TBD
	 *
	 * @param string $format     Format to return the date in.
	 * @param bool   $local_time Whether to return the date in local time or UTC.
	 *
	 * @return string|null Date in the requested format.
	 */
	public function get_startdate( $format = 'timestamp', $local_time = true ) {
		return $this->format_subscription_date( $this->startdate, $format, $local_time );
	}

	/**
	 * Get the end date for this subscription.
	 *
	 * @since TBD
	 *
	 * @param string $format     Format to return the date in.
	 * @param bool   $local_time Whether to return the date in local time or UTC.
	 *
	 * @return string|null Date in the requested format.
	 */
	public function get_enddate( $format = 'timestamp', $local_time = true ) {
		return $this->format_subscription_date( $this->enddate, $format, $local_time );
	}

	/**
	 * Format a date.
	 *
	 * @since TBD
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
		}

		if ( 'timestamp' === $format ) {
			$format = 'U';
		} elseif ( 'date_format' === $format ) {
			$format = get_option( 'date_format' );
		}

		if ( $local_time ) {
			return get_date_from_gmt( $date, $format ); // Local time.
		}

		return date( $format, strtotime( $date ) ); // GMT.
	}

	/**
	 * Returns the PMProGateway object for this subscription.
	 *
	 * @since TBD
	 */
	public function get_gateway_object() {
		// No gatway was set.
		if ( empty( $this->gateway ) ) {
			return null;
		}

		// Default test gateway.
		$classname = 'PMProGateway';

		if ( 'free' !== $this->gateway ) {
			// Adding the gateway suffix.
			$classname .= '_' . $this->gateway;
		}

		if ( class_exists( $classname ) ) {
			return new $classname( $this->gateway );
		}

		return null;
	}

	/**
	 * Save the subscription using the current properties set. This will also set $subscription->id on creation.
	 *
	 * @since TBD
	 *
	 * @return bool The new subscription ID or false if the save did not complete.
	 */
	public function save() {
		global $wpdb;

		// Handle required fields.
		if ( empty( $this->gateway ) || empty( $this->gateway_environment ) || empty( $this->subscription_transaction_id ) ) {
			return false;
		}

		$create = empty( $this->id );

		if ( $create ) {
			/**
			 * Allow hooking into before the creation of a subscription.
			 *
			 * @since TBD
			 *
			 * @param PMPro_Subscription The subscription object.
			 */
			do_action( 'pmpro_create_subscription', $this );
		} else {
			/**
			 * Allow hooking into before the update of a subscription.
			 *
			 * @since TBD
			 *
			 * @param PMPro_Subscription The subscription object.
			 */
			do_action( 'pmpro_update_subscription', $this );
		}

		$wpdb->replace( $wpdb->pmpro_subscriptions, [
			'id'                          => $this->id,
			'user_id'                     => $this->user_id,
			'membership_level_id'         => $this->membership_level_id,
			'gateway'                     => $this->gateway,
			'gateway_environment'         => $this->gateway_environment,
			'subscription_transaction_id' => $this->subscription_transaction_id,
			'status'                      => $this->status,
			'startdate'                   => $this->startdate,
			'enddate'                     => $this->enddate,
			'next_payment_date'           => $this->next_payment_date,
		], [
			'%d', // id
			'%d', // user_id
			'%d', // membership_level_id
			'%s', // gateway
			'%s', // gateway_environment
			'%s', // subscription_transaction_id
			'%s', // status
			'%s', // startdate
			'%s', // enddate
			'%s', // next_payment_date
		] );

		if ( $wpdb->insert_id ) {
			$this->id = $wpdb->insert_id;
		}

		// The subscription was not created properly.
		if ( empty( $this->id ) ) {
			return false;
		}

		if ( $create ) {
			/**
			 * Allow hooking into after the creation of a subscription.
			 *
			 * @since TBD
			 *
			 * @param PMPro_Subscription The subscription object.
			 */
			do_action( 'pmpro_created_subscription', $this );
		} else {
			/**
			 * Allow hooking into after the creation of a subscription.
			 *
			 * @since TBD
			 *
			 * @param PMPro_Subscription The subscription object.
			 */
			do_action( 'pmpro_updated_subscription', $this );
		}

		return $this->id;
	}

	/**
	 * Cancels this subscription in PMPro and at the payment gateway.
	 *
	 * @since TBD
	 *
	 * @return bool True if the subscription was canceled successfully.
	 */
	public function cancel() {
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
