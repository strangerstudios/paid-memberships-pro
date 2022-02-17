<?php

/**
 * The PMPro Subscription object.
 *
 * @method int    get_id                          Get the ID of the subscription.
 * @method int    get_user_id                     Get the ID of the user the subscription belongs to.
 * @method int    get_membership_level_id         Get the ID of the membership level that this subscription is for.
 * @method string get_gateway                     Get the gateway used to create the subscription.
 * @method string get_gateway_environment         Get the gateway environment used to create the subscription.
 * @method string get_subscription_transaction_id Get the ID of the subscription in the gateway.
 * @method string get_status                      Get the status of the subscription.
 * @method float  get_billing_amount              Get the billing amount.
 * @method int	  get_cycle_number                Get the number of cycles.
 * @method string get_cycle_period                Get the cycle period.
 * @method int	  get_billing_limit               Get the billing limit.
 * @method float  get_trial_amount                Get the trial amount.
 * @method int	  get_trial_limit                 Get the trial limit.
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
	protected $id = 0;

	/**
	 * The subscription user ID.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected $user_id = 0;

	/**
	 * The subscription membership level ID.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected $membership_level_id = 0;

	/**
	 * The subscription gateway.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $gateway = '';

	/**
	 * The subscription gateway environment.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $gateway_environment = '';

	/**
	 * The subscription transaction id.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $subscription_transaction_id = '';

	/**
	 * The subscription status.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $status = '';

	/**
	 * The subscription start date (UTC YYYY-MM-DD HH:MM:SS).
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $startdate = '';

	/**
	 * The subscription end date (UTC YYYY-MM-DD HH:MM:SS).
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $enddate = '';

	/**
	 * The subscription next payment date (UTC YYYY-MM-DD HH:MM:SS).
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $next_payment_date = '';

	/**
	 * The subscription billing amount.
	 *
	 * @since TBD
	 *
	 * @var float
	 */
	protected $billing_amount = 0.00;

	/**
	 * The subscription billing cycle number.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected $cycle_number = 0;

	/**
	 * The subscription billing cycle period.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $cycle_period = 'Month';

	/**
	 * The subscription billing limit.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected $billing_limit = 0;

	/**
	 * The subscription trial billing amount.
	 *
	 * @since TBD
	 *
	 * @var float
	 */
	protected $trial_amount = 0.00;

	/**
	 * The subscription trial billing cycle number.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected $trial_limit = 0;

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

		if ( ! empty( $subscription_data ) ) {
			$this->set( $subscription_data );
		}
	}

	/**
	 * Call magic methods.
	 *
	 * @since TBD
	 *
	 * @param string $name      The method that was called.
	 * @param array  $arguments The arguments passed to the method.
	 *
	 * @return mixed|null
	 */
	public function __call( $name, $arguments ) {
		if ( 0 === strpos( $name, 'get_' ) ) {
			$property_name_arr = explode( 'get_', $name );
			$property_name = $property_name_arr[1];

			$supported_properties = [
				'id',
				'user_id',
				'membership_level_id',
				'gateway',
				'gateway_environment',
				'subscription_transaction_id',
				'status',
				'billing_amount',
				'cycle_number',
				'cycle_period',
				'billing_limit',
				'trial_amount',
				'trial_limit',
			];

			if ( in_array( $property_name, $supported_properties, true ) ) {
				return $this->{$property_name};
			}
		}
		return null;
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

		// Filter by billing amount(s).
		if ( isset( $args['billing_amount'] ) && null !== $args['billing_amount'] ) {
			if ( ! is_array( $args['billing_amount'] ) ) {
				$where[]    = 'billing_amount = %f';
				$prepared[] = $args['billing_amount'];
			} else {
				$where[]  = 'billing_amount IN ( ' . implode( ', ', array_fill( 0, count( $args['billing_amount'] ), '%f' ) ) . ' )';
				$prepared = array_merge( $prepared, $args['billing_amount'] );
			}
		}

		// Filter by cycle number(s).
		if ( isset( $args['cycle_number'] ) && null !== $args['cycle_number'] ) {
			if ( ! is_array( $args['cycle_number'] ) ) {
				$where[]    = 'cycle_number = %d';
				$prepared[] = $args['cycle_number'];
			} else {
				$where[]  = 'cycle_number IN ( ' . implode( ', ', array_fill( 0, count( $args['cycle_number'] ), '%d' ) ) . ' )';
				$prepared = array_merge( $prepared, $args['cycle_number'] );
			}
		}

		// Filter by cycle period(s).
		if ( isset( $args['cycle_period'] ) && null !== $args['cycle_period'] ) {
			if ( ! is_array( $args['cycle_period'] ) ) {
				$where[]    = 'cycle_period = %s';
				$prepared[] = $args['cycle_period'];
			} else {
				$where[]  = 'cycle_period IN ( ' . implode( ', ', array_fill( 0, count( $args['cycle_period'] ), '%s' ) ) . ' )';
				$prepared = array_merge( $prepared, $args['cycle_period'] );
			}
		}

		// Filter by billing limit(s).
		if ( isset( $args['billing_limit'] ) && null !== $args['billing_limit'] ) {
			if ( ! is_array( $args['billing_limit'] ) ) {
				$where[]    = 'billing_limit = %d';
				$prepared[] = $args['billing_limit'];
			} else {
				$where[]  = 'billing_limit IN ( ' . implode( ', ', array_fill( 0, count( $args['billing_limit'] ), '%d' ) ) . ' )';
				$prepared = array_merge( $prepared, $args['billing_limit'] );
			}
		}

		// Filter by trial amount(s).
		if ( isset( $args['trial_amount'] ) && null !== $args['trial_amount'] ) {
			if ( ! is_array( $args['trial_amount'] ) ) {
				$where[]    = 'trial_amount = %f';
				$prepared[] = $args['trial_amount'];
			} else {
				$where[]  = 'trial_amount IN ( ' . implode( ', ', array_fill( 0, count( $args['trial_amount'] ), '%f' ) ) . ' )';
				$prepared = array_merge( $prepared, $args['trial_amount'] );
			}
		}

		// Filter by trial limit(s).
		if ( isset( $args['trial_limit'] ) && null !== $args['trial_limit'] ) {
			if ( ! is_array( $args['trial_limit'] ) ) {
				$where[]    = 'trial_limit = %d';
				$prepared[] = $args['trial_limit'];
			} else {
				$where[]  = 'trial_limit IN ( ' . implode( ', ', array_fill( 0, count( $args['trial_limit'] ), '%d' ) ) . ' )';
				$prepared = array_merge( $prepared, $args['trial_limit'] );
			}
		}


		// Maybe filter the data.
		if ( $where ) {
			$sql_query .= ' WHERE ' . implode( ' AND ', $where );
		}

		$sql_query .= ' ORDER BY startdate DESC';

		// Maybe limit the data.
		if ( $limit ) {
			$sql_query .= ' LIMIT %d';
			$prepared[] = $limit;
		}

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
	 * @param int|null             $user_id             ID of the user to get subscriptions for. Defaults to current user.
	 * @param int|int[]|null       $membership_level_id The membership level ID(s) to get subscriptions for. Defaults to all.
	 * @param string|string[]|null $status              The status(es) of the subscription to get. Defaults to active.
	 *
	 * @return PMPro_Subscription[] The list of subscription objects.
	 */
	public static function get_subscriptions_for_user( $user_id = null, $membership_level_id = null, $status = [ 'active' ] ) {
		// Get user_id if none passed.
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		// Check for a valid user.
		if ( empty( $user_id ) ) {
			return [];
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
	 * @param array $args {
	 *                    Arguments to create a new subscription.
	 *
	 *                    @type int    $user_id                     ID of the user to create the subscription for. Required.
	 *                    @type int    $membership_level_id         ID of the membership level to create the subscription for. Required.
	 *                    @type string $gateway                     Gateway to create the subscription for. Required.
	 *                    @type string $gateway_environment         Gateway environment to create the subscription for. Required.
	 *                    @type string $subscription_transaction_id Subscription transaction ID to create the subscription for. Required.
	 *                    @type string $status                      Status of the subscription.
	 *                    @type string $startdate                   The subscription start date (UTC YYYY-MM-DD HH:MM:SS).
	 *                    @type string $enddate                     The subscription end date (UTC YYYY-MM-DD HH:MM:SS).
	 *                    @type string $next_payment_date           The subscription next payment date (UTC YYYY-MM-DD HH:MM:SS).
	 *                    @type float  $billing_amount              The subscription billing amount.
	 *                    @type int    $cycle_number                The subscription cycle number.
	 *                    @type string $cycle_period                The subscription cycle period.
	 *                    @type int    $billing_limit               The subscription billing limit.
	 *                    @type float  $trial_amount                The subscription trial amount.
	 *                    @type int    $trial_limit                 The subscription trial limit.
	 * }
	 *
	 * @return PMPro_Subscription|null PMPro_Subscription object if created, null if not.
	 */
	public static function create( $args ) {
		global $wpdb;

		// Make sure that $args is an array.
		$subscription_data = array();
		if ( is_array( $args ) ) {
			$subscription_data = $args;
		} elseif ( is_object( $args ) ) {
			$subscription_data = get_object_vars( $args );
		} else {
			// Invalid $subscription so there's nothing we can do.
			return null;
		}

		// At a minimum, we need a user_id, membership_level_id, subscription_transaction_id, gateway, and gateway_environment.
		if (
			empty( $subscription_data['user_id'] ) ||
			empty( $subscription_data['membership_level_id'] ) ||
			empty( $subscription_data['subscription_transaction_id'] ) ||
			empty( $subscription_data['gateway'] ) ||
			empty( $subscription_data['gateway_environment'] )
		) {
			return null;
		}

		// Make sure we don't already have a subscription with this transaction ID and gateway.
		$existing_subscription = self::get_subscription_from_subscription_transaction_id( $subscription_data['subscription_transaction_id'], $subscription_data['gateway'], $subscription_data['gateway_environment'] );
		if ( ! empty( $existing_subscription ) ) {
			// Subscription already exists.
			return null;
		}

		// Create the subscription.
		$new_subscription = new PMPro_Subscription( $subscription_data );

		// Try to pull as much info as possible directly from the gateway.
		$new_subscription->update_from_gateway();

		$saved = $new_subscription->save();
		if ( ! $saved ) {
			// We couldn't save the subscription.
			return null;
		}

		return $new_subscription;
	}

	/**
	 * Pull subscription info from the gateway.
	 *
	 * @since TBD
	 */
	public function update_from_gateway() {
		$gateway_object = $this->get_gateway_object();

		if ( $gateway_object && method_exists( $gateway_object, 'update_subscription_info' ) ) {
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

		// Get date in WP local timezone.
		if ( $local_time ) {
			return get_date_from_gmt( $date, $format );
		}

		// Allow timestamps.
		if ( ! is_numeric( $date ) ) {
			$date = strtotime( $date );
		}

		// Get date in GMT timezone.
		return gmdate( $format, $date );
	}

	/**
	 * Returns the PMProGateway object for this subscription.
	 *
	 * @since TBD
	 *
	 * @return null|PMProGateway The PMProGateway object, null if not set or class found.
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
	 * Set a property for this subscription.
	 *
	 * @since TBD
	 *
	 * @param string|array $property Property to set, or an array with property => value pairs.
	 * @param mixed        $value    Value to set.
	 */
	public function set( $property, $value = null ) {
		// Check if we need to set multiple properties as an array.
		if ( is_array( $property ) ) {
			foreach ( $property as $key => $value ) {
				$this->set( $key, $value );
			}

			return;
		}

		// Perform validation as needed here.
		if ( isset( $this->{$property} ) ) {
			if ( is_int( $this->{$property} ) ) {
				$value = (int) $value;
			} elseif ( is_float( $this->{$property} ) ) {
				$value = (float) $value;
			}
		}

		$this->{$property} = $value;
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

		// TODO: Perform validation here. The setter should make sure that fields have valid values,
		// but we should also check here that the values make sense together. For example, cancelled
		// subscriptions should not have a next payment date and startdates should not be before end dates.

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
			'billing_amount'              => $this->billing_amount,
			'cycle_number'                => $this->cycle_number,
			'cycle_period'                => $this->cycle_period,
			'billing_limit'               => $this->billing_limit,
			'trial_amount'                => $this->trial_amount,
			'trial_limit'                 => $this->trial_limit,
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
			'%f', // billing_amount
			'%d', // cycle_number
			'%s', // cycle_period
			'%d', // billing_limit
			'%f', // trial_amount
			'%d', // trial_limit
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
