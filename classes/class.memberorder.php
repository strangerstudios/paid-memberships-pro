<?php
	class MemberOrder
	{	
		/**
		 * The Member Order ID
		 *
		 * @since 2.9
		 *
		 * @var int
		 */
		private $id = 0;

		/**
		 * The Member Order Identifier, also used as invoie number
		 *
		 * @since 2.9
		 *
		 * @var string
		 */
		private $code = '';

		/**
		 * User ID
		 *
		 * @since 2.9
		 *
		 * @var int
		 */
		private $user_id = 0;

		/**
		 * Level ID
		 *
		 * @since 2.9
		 *
		 * @var int
		 */
		private $membership_id = 0;

		/**
		 * Session ID
		 *
		 * @since 2.9
		 *
		 * @var string
		 */
		private $session_id = '';

		/**
		 * PayPal Token 
		 *
		 * @since 2.9
		 *
		 * @var string
		 */
		private $paypal_token = '';

		/**
		 * Contain a billing address object
		 *
		 * @since 2.9
		 *
		 * @var object
		 */
		private $billing = '';

		/**
		 * Subtotal value
		 *
		 * @since 2.9
		 *
		 * @var float
		 */
		private $subtotal = 0.00;

		/**
		 * Tax Amount
		 *
		 * @since 2.9
		 *
		 * @var float
		 */
		private $tax = 0.00;

		/**
		 * Total order amount
		 *
		 * @since 2.9
		 *
		 * @var float
		 */
		private $total = 0.00;

		/**
		 * The gateway name or label used (Stripe, Check etc)
		 *
		 * @since 2.9
		 *
		 * @var string
		 */
		private $payment_type = '';

		/**
		 * The Card Type used (Visa etc)
		 *
		 * @since 2.9
		 *
		 * @var string
		 */
		private $cardtype = '';

		/**
		 * Account or Card Number (only shows last 4 digits)
		 *
		 * @since 2.9
		 *
		 * @var string
		 */
		private $accountnumber = '';

		/**
		 * Card Expiration Month (02)
		 *
		 * @since 2.9
		 *
		 * @var string
		 */
		private $expirationmonth = '';

		/**
		 * Expiration Year (22)
		 *
		 * @since 2.9
		 *
		 * @var string
		 */
		private $expirationyear = '';

		/**
		 * The Order Status
		 *
		 * @since 2.9
		 *
		 * @var string
		 */
		private $status = '';

		/**
		 * The Gateway identifier (stripe, paypalexpress etc)
		 *
		 * @since 2.9
		 *
		 * @var string
		 */
		private $gateway = '';

		/**
		 * The Gateway Environment (live, sandbox)
		 *
		 * @since 2.9
		 *
		 * @var string
		 */
		private $gateway_environment = '';

		/**
		 * The payment Transaction ID
		 *
		 * @since 2.9
		 *
		 * @var string
		 */
		private $payment_transaction_id = '';

		/**
		 * The Subscription Transaction ID
		 *
		 * @since 2.9
		 *
		 * @var string
		 */
		private $subscription_transaction_id = '';

		/**
		 * The time the order was created (UTC YYYY-MM-DD HH:MM:SS)
		 *
		 * @since 2.9
		 *
		 * @var string
		 */
		private $timestamp = '';

		/**
		 * The Affiliate ID 
		 *
		 * @since 2.9
		 *
		 * @var int
		 */
		private $affiliate_id = 0;

		/**
		 * The Affiliate Sub ID
		 *
		 * @since 2.9
		 *
		 * @var string
		 */
		private $affiliate_subid = '';

		/**
		 * The Order notes
		 *
		 * @since 2.9
		 *
		 * @var string
		 */
		private $notes = '';

		/**
		 * The Checkout ID - used to track multiple orders during a single checkout
		 *
		 * @since 2.9
		 *
		 * @var string
		 */
		private $checkout_id = '';	

		/**
		 * The discount code ID that was used for this order.
		 *
		 * This property is being added in PMPro v3.0 with the intention
		 * of adding a new column to the pmpro_membership_orders table duringe next
		 * major release. For now, we need to have code to "fake" this property.
		 * For now, this property will be initialized to `null` and will be set to an int
		 * when this property is accessed.
		 *
		 * Step 1 (v3.0): Create abstracted function for modifying/searching the discount code ID.
		 * Step 2 (At least 1 year later): Update Add Ons to use the new abstracted functions.
		 * Step 3 (Next major release after all Add Ons updated): Add new column to the pmpro_membership_orders
		 *        table for the discount code ID, update the abstracted functions to use the new column, create
		 *        migration script to move the discount code ID from the pmpro_discount_codes_uses table to the
		 *        new column in the pmpro_membership_orders table, and finally, drop the pmpro_discount_codes_uses table.
		 *
		 * Search "@DISCOUNT_CODE_ID_TODO" to find the places where we need to update
		 * the code to use the new column when we add it.
		 *
		 * `0` means no discount code was used, any other int is the ID of the discount code used.
		 *
		 * @since 3.0
		 *
		 * @var int|null
		 */
		private $discount_code_id = null;

		/**
		 * Defines an array of optionally used properties
		 *
		 * @since 2.9
		 *
		 * @var array
		 */
		private $other_properties = array();	


		/**
		 * Constructor
		 */
		function __construct($id = NULL)
		{

			//set up the gateway
			$this->setGateway(get_option("pmpro_gateway"));

			//set up the billing address structure
			$this->billing = new stdClass();
			$this->billing->name = '';
			$this->billing->street = '';
			$this->billing->city = '';
			$this->billing->state = '';
			$this->billing->zip = '';
			$this->billing->country = '';
			$this->billing->phone = '';

			//get data if an id was passed
			if ( $id ) {
				if ( is_numeric( $id ) ) {
					$morder = $this->getMemberOrderByID( $id );
				} else {
					$morder = $this->getMemberOrderByCode( $id );
				}

				// If we found an order, create a subscription if needed.
				if ( ! empty( $this->id ) ) {
					$this->get_subscription();
				}
			} else {
				$morder = $this->getEmptyMemberOrder();	//blank constructor
			}

			$this->original_status = $this->status;

			return $morder;
		}
		
		/**
		 * Get Magic Method
		 *
		 * @since 2.9
		 * 
		 * @param string $property The property we want to get
		 *
		 * @return mixed|void
		 */
		public function __get( $property ) {
			/**
			 * Special case. We want to add `discount_code_id` as a property/db column in the future.
			 * For now, we should support `discount_code_id` as a "property" by adding it here and
			 * querying the pmpro_discount_codes_uses table for the discount code ID.
			 *
			 * @DISCOUNT_CODE_ID_TODO
			 */
			if ( $property == 'discount_code_id' ) {
				if ( null === $this->discount_code_id ) {
					// Get the discount code ID from the pmpro_discount_codes_uses table.
					$this->getDiscountCode( true );
				}
				return $this->discount_code_id;
			}

			if ( $property == 'other_properties' ) {
				return; //We don't want the actual other_properties array to be changed
			}

			if ( property_exists( $this, $property ) ) {
				return $this->{$property};
			}

			if ( isset( $this->other_properties[ $property ] ) ) {
				return $this->other_properties[ $property ];
			}

		}

		/**
		 * Set Magic Method
		 *
		 * @since 2.9
		 * 
		 * @param string $property The property we want to reference
		 * @param string $value The value we want to set for $property
		 *
		 * @return mixed|null
		 */
		public function __set( $property, $value ) {

			if ( $property == 'other_properties' ) {
				return; //We don't want the actual other_properties array to be changed
			}

			/**
			 * Special case. We want to add `discount_code_id` as a property/db column in the future.
			 * For now, `discount_code_id` may be null or an int. But if being updated, we always want it to be an int.
			 *
			 * @DISCOUNT_CODE_ID_TODO
			 */
			if ( $property == 'discount_code_id' ) {
				$this->discount_code_id = (int) $value;
				return;
			}

			if ( property_exists( $this, $property ) ) {
				
				// Perform validation as needed here.
				if ( is_int( $this->{$property} ) ) {
					$value = (int) $value;
				} elseif ( is_float( $this->{$property} ) ) {
					$value = (float) $value;
				}
				
				$this->{$property} = $value;

			} else {

				$this->other_properties[ $property ] = $value;

			}

		}

		/**
		 * Is Set Magic Method
		 *
		 * @since 2.9
		 * 
		 * @param string $property The property we want to reference
		 *
		 * @return bool
		 */
		public function __isset( $property ) {

			return property_exists( $this, $property ) || isset( $this->other_properties[ $property ] );
	
		}

		/**
		 * Unset Magic Method.
		 *
		 * @since 2.9.1
		 * 
		 * @param string $property The property we want to unset.
		 */
		public function __unset( $property ) {
			if ( property_exists( $this, $property ) ) {
				unset( $this->{$property} );
			} else {
				unset( $this->other_properties[ $property ] );
			}
		}
		
		/**
		 * Get a specific order by ID, code, or an array of arguments
		 *
		 * @since 2.9
		 * 
		 * @param mixed $args Specify an order ID, code, or array of arguments to find an order for.
		 *
		 */
		public static function get_order( $args = NULL ) {

			// At least one argument is required.
			if ( empty( $args ) ) {
				return null;
			}

			if ( is_numeric( $args ) ) {
				// If its numeric we assume you're trying to get an ID.
				$args = array(
					'id' => $args,
				);

			} elseif ( is_string( $args ) ) {
				// If it is a string but not numeric, we assume it's a string and should be a code.
				$args = array(
					'code' => $args,
				);
			}

			// Invalid arguments.
			if ( ! is_array( $args ) ) {
				return null;
			}

			// Force returning of one order.
			$args['limit'] = 1;

			// Get the orders using query arguments.
			$orders = self::get_orders( $args );

			// Check if we found any orders.
			if ( empty( $orders ) ) {
				return null;
			}

			// Get the first order in the array.
			return reset( $orders );

		}

		/**
		 * Get orders based on various parameters
		 *
		 * @since 2.9
		 * 
		 * @param array $args Specify what you'd like to filter the query by
		 *
		 */
		public static function get_orders( array $args = array() ) {

			global $wpdb;

			// Check if we are going to return the count of orders.
			$return_count = isset( $args['return_count'] ) ? (bool) $args['return_count'] : false;

			$sql_query = $return_count ? "SELECT COUNT(*) FROM `$wpdb->pmpro_membership_orders`" : "SELECT `o`.`id` FROM `$wpdb->pmpro_membership_orders` `o`";

			$prepared = array();
			$where    = array();

			$orderby  = isset( $args['orderby'] ) ? $args['orderby'] : '`o`.`timestamp` DESC';
			$limit    = isset( $args['limit'] ) ? (int) $args['limit'] : 100;

			// Detect unsupported orderby usage (in the future we may support better syntax).
			if ( $orderby !== preg_replace( '/[^a-zA-Z0-9\s,.`]/', ' ', $orderby ) ) {
				return array();
			}

			/*
			 * Now filter the query based on the arguments provided.
			 */

			// Filter by ID(s).
			if ( isset( $args['id'] ) ) {
				if ( ! is_array( $args['id'] ) ) {
					$where[]    = 'id = %d';
					$prepared[] = $args['id'];
				} else {
					$where[]  = 'id IN ( ' . implode( ', ', array_fill( 0, count( $args['id'] ), '%d' ) ) . ' )';
					$prepared = array_merge( $prepared, $args['id'] );
				}
			}

			// Filter by user ID(s).
			if ( isset( $args['user_id'] ) ) {
				if ( ! is_array( $args['user_id'] ) ) {
					$where[]    = 'user_id = %d';
					$prepared[] = $args['user_id'];
				} else {
					$where[]  = 'user_id IN ( ' . implode( ', ', array_fill( 0, count( $args['user_id'] ), '%d' ) ) . ' )';
					$prepared = array_merge( $prepared, $args['user_id'] );
				}
			}

			// Filter by membership level ID(s).
			if ( isset( $args['membership_level_id'] ) ) {
				if ( ! is_array( $args['membership_level_id'] ) ) {
					$where[]    = 'membership_id = %d';
					$prepared[] = $args['membership_level_id'];
				} else {
					$where[]  = 'membership_id IN ( ' . implode( ', ', array_fill( 0, count( $args['membership_level_id'] ), '%d' ) ) . ' )';
					$prepared = array_merge( $prepared, $args['membership_level_id'] );
				}
			}

			// Filter by status(es).
			if ( isset( $args['status'] ) ) {
				if ( ! is_array( $args['status'] ) ) {
					$where[]    = 'status = %s';
					$prepared[] = $args['status'];
				} else {
					$where[]  = 'status IN ( ' . implode( ', ', array_fill( 0, count( $args['status'] ), '%s' ) ) . ' )';
					$prepared = array_merge( $prepared, $args['status'] );
				}
			}

			// Filter by subscription transaction ID(s).
			if ( isset( $args['subscription_transaction_id'] ) ) {
				if ( ! is_array( $args['subscription_transaction_id'] ) ) {
					$where[]    = 'subscription_transaction_id = %s';
					$prepared[] = $args['subscription_transaction_id'];
				} else {
					$where[]  = 'subscription_transaction_id IN ( ' . implode( ', ', array_fill( 0, count( $args['subscription_transaction_id'] ), '%s' ) ) . ' )';
					$prepared = array_merge( $prepared, $args['subscription_transaction_id'] );
				}
			}

			// Filter by gateway(s).
			if ( isset( $args['gateway'] ) ) {
				if ( ! is_array( $args['gateway'] ) ) {
					$where[]    = 'gateway = %s';
					$prepared[] = $args['gateway'];
				} else {
					$where[]  = 'gateway IN ( ' . implode( ', ', array_fill( 0, count( $args['gateway'] ), '%s' ) ) . ' )';
					$prepared = array_merge( $prepared, $args['gateway'] );
				}
			}

			// Filter by gateway environment(s).
			if ( isset( $args['gateway_environment'] ) ) {
				if ( ! is_array( $args['gateway_environment'] ) ) {
					$where[]    = 'gateway_environment = %s';
					$prepared[] = $args['gateway_environment'];
				} else {
					$where[]  = 'gateway_environment IN ( ' . implode( ', ', array_fill( 0, count( $args['gateway_environment'] ), '%s' ) ) . ' )';
					$prepared = array_merge( $prepared, $args['gateway_environment'] );
				}
			}

			// Filter by billing amount(s).
			if ( isset( $args['total'] ) ) {
				if ( ! is_array( $args['total'] ) ) {
					$where[]    = 'total = %f';
					$prepared[] = $args['total'];
				} else {
					$where[]  = 'total IN ( ' . implode( ', ', array_fill( 0, count( $args['total'] ), '%f' ) ) . ' )';
					$prepared = array_merge( $prepared, $args['total'] );
				}
			}

			// Filter by payment transaction ID
			if ( isset( $args['payment_transaction_id'] ) ) {
				if ( ! is_array( $args['payment_transaction_id'] ) ) {
					$where[]    = 'payment_transaction_id = %s';
					$prepared[] = $args['payment_transaction_id'];
				} else {
					$where[]  = 'payment_transaction_id IN ( ' . implode( ', ', array_fill( 0, count( $args['payment_transaction_id'] ), '%f' ) ) . ' )';
					$prepared = array_merge( $prepared, $args['payment_transaction_id'] );
				}
			}

			// Filter by discount code ID
			/**
			 * Special case. Eventually, we want to add `discount_code_id` as a property/db column. But
			 * for now, we need to query the pmpro_discount_codes_uses table for the discount code ID.
			 *
			 * @DISCOUNT_CODE_ID_TODO
			 */
			if ( isset( $args['discount_code_id'] ) ) {
				$sql_query .= " LEFT JOIN `$wpdb->pmpro_discount_codes_uses` `dcu` ON `dcu`.`order_id` = `o`.`id`";
				if ( ! is_array( $args['discount_code_id'] ) ) {
					$where[]    = '`dcu`.`code_id` = %d';
					$prepared[] = $args['discount_code_id'];
				} else {
					$where[]  = 'dcu.code_id IN ( ' . implode( ', ', array_fill( 0, count( $args['discount_code_id'] ), '%d' ) ) . ' )';
					$prepared = array_merge( $prepared, $args['discount_code_id'] );
				}
      }

			// Filter by date range by start date. (YYYY-MM-DD HH:MM:SS UTC)
			if ( isset( $args['start_date'] ) ) {
				$where[]    = 'timestamp >= %s';
				$prepared[] = $args['start_date'];
			}

			// Filter by date range by end date. (YYYY-MM-DD HH:MM:SS UTC)
			if ( isset( $args['end_date'] ) ) {
				$where[]    = 'timestamp <= %s';
				$prepared[] = $args['end_date'];
			}

			// Maybe filter the data.
			if ( $where ) {
				$sql_query .= ' WHERE ' . implode( ' AND ', $where );
			}

			if ( ! $return_count ) {
				// Handle the order of data.
				$sql_query .= ' ORDER BY ' . $orderby;

				// Maybe limit the data.
				if ( $limit ) {
					$sql_query .= ' LIMIT %d';
					$prepared[] = $limit;
				}
			}

			// Maybe prepare the query.
			if ( $prepared ) {
				$sql_query = $wpdb->prepare( $sql_query, $prepared );
			}

			// If we're returning a count, return the count.
			if ( $return_count ) {
				return (int) $wpdb->get_var( $sql_query );
			}

			// Not returning a count, so get the order IDs.
			$member_order_ids = $wpdb->get_col( $sql_query );

			if ( empty( $member_order_ids ) ) {
				return array();
			}

			$member_orders = array();

			foreach ( $member_order_ids as $member_order_id ) {
				$morder = new MemberOrder( $member_order_id );

				// Make sure the subscription object is valid.
				if ( ! empty( $morder->id ) ) {
					$member_orders[] = $morder;
				}
			}

			return $member_orders;

		}

		/**
		 * Returns an empty (but complete) order object.
		 *
		 * @return stdClass $order - a 'clean' order object
		 *
		 * @since: 1.8.6.8
		 */
		function getEmptyMemberOrder()
		{
			//defaults
			$this->code = $this->getRandomCode();
			$this->status = "success";
			$this->gateway = get_option("pmpro_gateway");
			$this->gateway_environment = get_option("pmpro_gateway_environment");

			return $this;
		}

		/**
		 * Retrieve a member order from the DB by ID
		 */
		function getMemberOrderByID($id)
		{
			global $wpdb;

			if(!$id)
				return false;

			$dbobj = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_membership_orders WHERE id = %d LIMIT 1", $id ) );

			if($dbobj)
			{
				$this->id = $dbobj->id;
				$this->code = $dbobj->code;
				$this->session_id = $dbobj->session_id;
				$this->user_id = $dbobj->user_id;
				$this->membership_id = $dbobj->membership_id;
				$this->paypal_token = $dbobj->paypal_token;
				$this->billing = new stdClass();
				$this->billing->name = $dbobj->billing_name;
				$this->billing->street = $dbobj->billing_street;
				$this->billing->city = $dbobj->billing_city;
				$this->billing->state = $dbobj->billing_state;
				$this->billing->zip = $dbobj->billing_zip;
				$this->billing->country = $dbobj->billing_country;
				$this->billing->phone = $dbobj->billing_phone;

				//split up some values
				$nameparts = pnp_split_full_name($this->billing->name);

				if(!empty($nameparts['fname']))
					$this->FirstName = $nameparts['fname'];
				else
					$this->FirstName = "";
				if(!empty($nameparts['lname']))
					$this->LastName = $nameparts['lname'];
				else
					$this->LastName = "";

				$this->Address1 = $this->billing->street;

				//get email from user_id
				$this->Email = $wpdb->get_var( $wpdb->prepare( "SELECT user_email FROM $wpdb->users WHERE ID = %d LIMIT 1", $this->user_id ) );

				$this->subtotal = $dbobj->subtotal;
				$this->tax = (float)$dbobj->tax;
				$this->total = $dbobj->total;
				$this->payment_type = $dbobj->payment_type;
				$this->cardtype = $dbobj->cardtype;
				$this->accountnumber = trim($dbobj->accountnumber);
				$this->expirationmonth = $dbobj->expirationmonth;
				$this->expirationyear = $dbobj->expirationyear;

				//date formats sometimes useful
				$this->ExpirationDate = $this->expirationmonth . $this->expirationyear;
				$this->ExpirationDate_YdashM = $this->expirationyear . "-" . $this->expirationmonth;

				$this->status = $dbobj->status;
				$this->gateway = $dbobj->gateway;
				$this->gateway_environment = $dbobj->gateway_environment;
				$this->payment_transaction_id = $dbobj->payment_transaction_id;
				$this->subscription_transaction_id = $dbobj->subscription_transaction_id;
				$this->timestamp = strtotime( $dbobj->timestamp );
				$this->affiliate_id = $dbobj->affiliate_id;
				$this->affiliate_subid = $dbobj->affiliate_subid;

				$this->notes = $dbobj->notes;
				$this->checkout_id = $dbobj->checkout_id;

				//reset the gateway
				if(empty($this->nogateway))
					$this->setGateway();

				return $this->id;
			}
			else
				return false;	//didn't find it in the DB
		}

		/**
		 * Get the first order for this subscription.
		 * Useful to find the original order from a recurring order.
		 * @since 2.5
		 * @return mixed Order object if found or false if not.
		 */
		function get_original_subscription_order( $subscription_id = '' ){
			global $wpdb;
			
			// Default to use the subscription ID on this order object.
			if ( empty( $subscription_id ) && ! empty( $this->subscription_transaction_id ) ) {
				$subscription_id = $this->subscription_transaction_id;
			}
			
			// Must have a subscription ID.
			if ( empty( $subscription_id ) ) {
				return false;
			}
			
			// Get some other values from this order to narrow the search.
			if ( ! empty( $this->user_id ) ) {
				$user_id = $this->user_id;
			} else {
				$user_id = '';
			}
			if ( ! empty( $this->gateway ) ) {
				$gateway = $this->gateway;
			} else {
				$gateway = '';
			}
			if ( ! empty( $this->gateway_environment ) ) {
				$gateway_environment = $this->gateway_environment;
			} else {
				$gateway_environment = '';
			}
			
			// Double check for a user_id, gateway and gateway environment.
			$sql = $wpdb->prepare(
				"SELECT ID
				 FROM $wpdb->pmpro_membership_orders
				 WHERE `subscription_transaction_id` = %s
				   AND `user_id` = %d
				   AND `gateway` = %s
				   AND `gateway_environment` = %s
				 ORDER BY id ASC
				 LIMIT 1",
				 array(
					 $subscription_id,
					 $user_id,
					 $gateway,
					 $gateway_environment
				 )
			 );
			
			$order_id = $wpdb->get_var( $sql );
			if ( ! empty( $order_id ) ) {
				return new MemberOrder( $order_id );
			} else {
				return false;
			}
		}

		/**
		 * Is this order a 'renewal'?
		 * We currently define a renewal as any order from a user who has
		 * a previous paid (non-$0) order.
		 */
		function is_renewal() {
			global $wpdb;			
			
			// If our property is already set, use that.
			if ( isset( $this->is_renewal ) ) {				
				return $this->is_renewal;
			}
			
			// Can't tell if this is a renewal without a user.
			if ( empty( $this->user_id ) ) {
				$this->is_renewal = false;
				return $this->is_renewal;
			}
			
			// Can't tell if this is a renewal without a timestamp.
			if ( empty( $this->timestamp ) ) {
				$this->is_renewal = false;
				return $this->is_renewal;
			}
			
			// Check the DB.
			$sqlQuery = "SELECT `id`
						 FROM $wpdb->pmpro_membership_orders
						 WHERE `user_id` = '" . esc_sql( $this->user_id ) . "'						 	
							AND `id` <> '" . esc_sql( $this->id ) . "'
							AND `gateway_environment` = '" . esc_sql( $this->gateway_environment ) . "'
							AND `total` > 0
							AND `total` IS NOT NULL
							AND status NOT IN('refunded', 'review', 'token', 'error')
							AND timestamp < '" . esc_sql( date( 'Y-m-d H:i:s', $this->timestamp ) ) . "'
						 LIMIT 1";
			$older_order_id = $wpdb->get_var( $sqlQuery );

			if ( ! empty( $older_order_id ) ) {
				$this->is_renewal = true;
			} else {
				$this->is_renewal = false;
			}
			
			return $this->is_renewal;
		}


		/**
		 * Set up the Gateway class to use with this order.
		 *
		 * @param string $gateway Name/label for the gateway to set.
		 *
		 */
		function setGateway($gateway = NULL) {
			//set the gateway property
			if(isset($gateway)) {
				$this->gateway = $gateway;
			}

			//which one to load?
			$classname = "PMProGateway";	//default test gateway
			if(!empty($this->gateway) && $this->gateway != "free") {
				$classname .= "_" . $this->gateway;	//adding the gateway suffix
			}

			if(class_exists($classname) && isset($this->gateway)) {
				$this->Gateway = new $classname($this->gateway);
			} else {
				$this->Gateway = null;	//null out any current gateway
				$error = new WP_Error("PMPro1001", "Could not locate the gateway class file with class name = " . $classname . ".");
			}

			if(!empty($this->Gateway)) {
				return $this->Gateway;
			} else {
				//gateway wasn't setup
				return false;
			}
		}

		/**
		 * Get the most recent order for a user.
		 *
		 * @param int $user_id ID of user to find order for.
		 * @param string|string[] $status Limit search to only orders with this status. Defaults to "success".
		 * @param int|int[] $membership_id Limit search to only orders for this membership level. Defaults to NULL to find orders for any level.
		 * @param string $gateway Limit search to only orders with this gateway. Defaults to NULL to find orders for any gateway.
		 * @param string $gateway_environment Limit search to only orders with this gateway environment. Defaults to NULL to find orders for any gateway environment.
		 *
		 * @return MemberOrder
		 */
		function getLastMemberOrder($user_id = NULL, $status = 'success', $membership_id = NULL, $gateway = NULL, $gateway_environment = NULL)
		{
			global $current_user, $wpdb;
			if(!$user_id)
				$user_id = $current_user->ID;

			if(!$user_id)
				return false;

			//build query
			$this->sqlQuery = "SELECT id FROM $wpdb->pmpro_membership_orders WHERE user_id = '" . esc_sql( $user_id ) . "' ";
			if(!empty($status) && is_array($status)) {
				$this->sqlQuery .= "AND status IN('" . implode("','", array_map( 'esc_sql', $status ) ) . "') ";
			} elseif(!empty($status)) {
				$this->sqlQuery .= "AND status = '" . esc_sql($status) . "' ";
			}

			if(!empty($membership_id) && is_array($membership_id)) {
				$this->sqlQuery .= "AND membership_id IN(" . implode( ",", array_map( 'esc_sql', $membership_id ) ) . ") ";
			} elseif(!empty($membership_id)) {
				$this->sqlQuery .= "AND membership_id = '" . esc_sql( $membership_id ) . "' ";
			}

			if(!empty($gateway))
				$this->sqlQuery .= "AND gateway = '" . esc_sql($gateway) . "' ";

			if(!empty($gateway_environment))
				$this->sqlQuery .= "AND gateway_environment = '" . esc_sql($gateway_environment) . "' ";

			$this->sqlQuery .= "ORDER BY timestamp DESC LIMIT 1";

			//get id
			$id = $wpdb->get_var($this->sqlQuery);

			return $this->getMemberOrderByID($id);
		}

		/*
			Returns the order using the given order code.
		*/
		function getMemberOrderByCode($code)
		{
			global $wpdb;
			$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $wpdb->pmpro_membership_orders WHERE code = %s LIMIT 1", $code ) );
			if($id)
				return $this->getMemberOrderByID($id);
			else
				return false;
		}

		/*
			Returns the last order using the given payment_transaction_id.
		*/
		function getMemberOrderByPaymentTransactionID($payment_transaction_id)
		{
			//did they pass a trans id?
			if(empty($payment_transaction_id))
				return false;

			global $wpdb;
			$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $wpdb->pmpro_membership_orders WHERE payment_transaction_id = %s LIMIT 1", $payment_transaction_id ) );
			if($id)
				return $this->getMemberOrderByID($id);
			else
				return false;
		}

		/**
		 * Returns the last order using the given subscription_transaction_id.
		 */
		function getLastMemberOrderBySubscriptionTransactionID($subscription_transaction_id)
		{
			//did they pass a sub id?
			if(empty($subscription_transaction_id))
				return false;

			global $wpdb;
			$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $wpdb->pmpro_membership_orders WHERE subscription_transaction_id = %s ORDER BY id DESC LIMIT 1", $subscription_transaction_id ) );

			if($id)
				return $this->getMemberOrderByID($id);
			else
				return false;
		}

		/**
		 * Returns the last order using the given paypal token.
		 */
		function getMemberOrderByPayPalToken($token)
		{
			global $wpdb;
			$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $wpdb->pmpro_membership_orders WHERE paypal_token = %s LIMIT 1", $token ) );
			if($id)
				return $this->getMemberOrderByID($id);
			else
				return false;
		}

		/**
		 * Get a discount code object for the code used in this order.
		 *
		 * @param bool $force If true, it will query the database again.
		 *
		 */
		function getDiscountCode($force = false)
		{
			if(!empty($this->discount_code) && !$force)
				return $this->discount_code;

			global $wpdb;
			$this->discount_code = $wpdb->get_row( $wpdb->prepare( "SELECT dc.* FROM $wpdb->pmpro_discount_codes dc LEFT JOIN $wpdb->pmpro_discount_codes_uses dcu ON dc.id = dcu.code_id WHERE dcu.order_id = %d LIMIT 1", $this->id ) );

			//filter @since v1.7.14
			$this->discount_code = apply_filters("pmpro_order_discount_code", $this->discount_code, $this);

			/**
			 * Special case. We want to add `discount_code_id` as a property/db column in the future.
			 * For now, save the discount code ID as a property on the order object.
			 *
			 * @DISCOUNT_CODE_ID_TODO
			 */
			$this->discount_code_id = ! empty( $this->discount_code ) ? $this->discount_code->id : 0;
			return $this->discount_code;
		}

		/**
		 * Update the discount code used in this order.
		 *
		 * @param int $discount_code_id The ID of the discount code to update.
		 *
		 */
		function updateDiscountCode( $discount_code_id ) {
			global $wpdb;

			// Assumes one discount code per order
			$sqlQuery = $wpdb->prepare("
				SELECT id FROM $wpdb->pmpro_discount_codes_uses
				WHERE order_id = %d
				LIMIT 1",
				$this->id
			);
			$discount_codes_uses_id = $wpdb->get_var( $sqlQuery );

			// INSTEAD: Delete the code use if found
			if ( empty( $discount_code_id ) ) {
				if ( ! empty( $discount_codes_uses_id ) ) {
					$wpdb->delete(
						$wpdb->pmpro_discount_codes_uses,
						array( 'id' => $discount_codes_uses_id ),
						array( '%d' )
					);
				}
			} else {
				if ( ! empty( $discount_codes_uses_id ) ) {
					// Update existing row
					$wpdb->update(
						$wpdb->pmpro_discount_codes_uses,
						array( 'code_id' => $discount_code_id, 'user_id' => $this->user_id, 'order_id' => $this->id ),
						array( 'id' => $discount_codes_uses_id ),
						array( '%d', '%d', '%d' ),
						array( '%d' )
					);
				} else {
					// Insert a new row
					$wpdb->insert(
						$wpdb->pmpro_discount_codes_uses,
						array( 'code_id' => $discount_code_id, 'user_id' => $this->user_id, 'order_id' => $this->id ),
						array( '%d', '%d', '%d' )
					);
				}
			}

			// Make sure to reset properties on this object
			return $this->getDiscountCode( true );
		}

		/**
		 * Get a user object for the user associated with this order.
		 */
		function getUser()
		{
			global $wpdb;

			if(!empty($this->user))
				return $this->user;

			
			$this->user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->users WHERE ID = %d LIMIT 1", $this->user_id ) );
			
			// Fix the timestamp for local time 
			if ( ! empty( $this->user ) && ! empty( $this->user->user_registered ) ) {
				$this->user->user_registered = strtotime( get_date_from_gmt( $this->user->user_registered, 'Y-m-d H:i:s' ) );
			}

			return $this->user;
		}

		/**
		 * Get a membership level object for the level associated with this order.
		 *
		 * @param bool $force If true, it will query the database again.
		 *
		 */
		function getMembershipLevel($force = false)
		{
			global $wpdb;

			if(!empty($this->membership_level) && empty($force))
				return $this->membership_level;

			//check if there is an entry in memberships_users first
			if(!empty($this->user_id))
			{
				$sqlQuery = $wpdb->prepare( "SELECT l.id as level_id, l.name, l.description, l.allow_signups, l.expiration_number, l.expiration_period, mu.*, UNIX_TIMESTAMP(CONVERT_TZ(mu.startdate, '+00:00', @@global.time_zone)) as startdate, UNIX_TIMESTAMP(CONVERT_TZ(mu.enddate, '+00:00', @@global.time_zone)) as enddate, l.name, l.description, l.allow_signups FROM $wpdb->pmpro_membership_levels l LEFT JOIN $wpdb->pmpro_memberships_users mu ON l.id = mu.membership_id WHERE mu.status = 'active' AND l.id = %d AND mu.user_id = %d LIMIT 1", $this->membership_id, $this->user_id );
				$this->membership_level = $wpdb->get_row( $sqlQuery );

				//fix the membership level id
				if(!empty($this->membership_level->level_id))
					$this->membership_level->id = $this->membership_level->level_id;
			}

			//okay, do I have a discount code to check? (if there is no membership_level->membership_id value, that means there was no entry in memberships_users)
			if(!empty($this->discount_code) && empty($this->membership_level->membership_id))
			{
				if(!empty($this->discount_code->code))
					$discount_code = $this->discount_code->code;
				else
					$discount_code = $this->discount_code;

				$sqlQuery = $wpdb->prepare( "SELECT l.id, cl.*, l.name, l.description, l.allow_signups FROM $wpdb->pmpro_discount_codes_levels cl LEFT JOIN $wpdb->pmpro_membership_levels l ON cl.level_id = l.id LEFT JOIN $wpdb->pmpro_discount_codes dc ON dc.id = cl.code_id WHERE dc.code = %s AND cl.level_id = %d LIMIT 1", $discount_code, $this->membership_id );

				$this->membership_level = $wpdb->get_row($sqlQuery);
			}

			//just get the info from the membership table	(sigh, I really need to standardize the column names for membership_id/level_id) but we're checking if we got the information already or not
			if(empty($this->membership_level->membership_id) && empty($this->membership_level->level_id))
			{
				$this->membership_level = $wpdb->get_row( $wpdb->prepare( "SELECT l.* FROM $wpdb->pmpro_membership_levels l WHERE l.id = %d LIMIT 1", $this->membership_id ) );
			}
			
			// Round prices to avoid extra decimals.
			if( ! empty( $this->membership_level ) ) {
				$this->membership_level->initial_payment = pmpro_round_price( $this->membership_level->initial_payment );
				$this->membership_level->billing_amount = pmpro_round_price( $this->membership_level->billing_amount );
				$this->membership_level->trial_amount = pmpro_round_price( $this->membership_level->trial_amount );
			}

			return $this->membership_level;
		}
		
		/**
		 * Get a membership level object at checkout
		 * for the level associated with this order.
		 *
		 * @since 2.0.2
		 * @param bool $force If true, it will reset the property.
		 *
		 */
		function getMembershipLevelAtCheckout($force = false) {			
			if ( empty( $this->membership_level ) || $force ) {
				$this->membership_level = pmpro_getLevelAtCheckout( empty( $this->membership_id ) ? null : $this->membership_id );
			}
			
			// Fix the membership level id.
			if(!empty( $this->membership_level) && !empty($this->membership_level->level_id)) {
				$this->membership_level->id = $this->membership_level->level_id;
			}
			
			// Round prices to avoid extra decimals.
			if( ! empty( $this->membership_level ) ) {
				$this->membership_level->initial_payment = pmpro_round_price( $this->membership_level->initial_payment );
				$this->membership_level->billing_amount = pmpro_round_price( $this->membership_level->billing_amount );
				$this->membership_level->trial_amount = pmpro_round_price( $this->membership_level->trial_amount );
			}
			
			return $this->membership_level;
		}

		/**
		 * Apply tax rules for the price given.
		 */
		function getTaxForPrice($price)
		{
			//get options
			$tax_state = get_option("pmpro_tax_state");
			$tax_rate = get_option("pmpro_tax_rate");

			//default
			$tax = 0.00;

			//calculate tax
			if($tax_state && $tax_rate)
			{
				//we have values, is this order in the tax state?
				if(!empty($this->billing) && trim(strtoupper($this->billing->state)) == trim(strtoupper($tax_state)))
				{
					//return value, pass through filter
					$tax = round((float)$price * (float)$tax_rate, 2);
				}
			}

			//set values array for filter
			$values = array("price" => $price, "tax_state" => $tax_state, "tax_rate" => $tax_rate);
			if(!empty($this->billing->street))
				$values['billing_street'] = $this->billing->street;
			if(!empty($this->billing->state))
				$values['billing_state'] = $this->billing->state;
			if(!empty($this->billing->city))
				$values['billing_city'] = $this->billing->city;
			if(!empty($this->billing->zip))
				$values['billing_zip'] = $this->billing->zip;
			if(!empty($this->billing->country))
				$values['billing_country'] = $this->billing->country;

			//filter
			$tax = (float)apply_filters("pmpro_tax", $tax, $values, $this);
			return $tax;
		}

		/**
		 * Get the tax amount for this order.
		 */
		function getTax($force = false)
		{
			if(!empty($this->tax) && !$force)
				return $this->tax;

			//reset
			$this->tax = $this->getTaxForPrice($this->subtotal);

			return $this->tax;
		}

		/**
		 * Get the timestamp for this order.
		 *
		 * @param bool $gmt whether to return GMT time or local timestamp.
		 * @return int timestamp.
		 */
		function getTimestamp( $gmt = false ) {
			return $gmt ? $this->timestamp : strtotime( get_date_from_gmt( date( 'Y-m-d H:i:s', $this->timestamp ) ) );
		}

		/**
		 * Change the timestamp of an order by passing in year, month, day, time.
		 *
		 * $time should be adjusted for local timezone.
		 *
		 * NOTE: This function should no longer be used. Instead, set the timestamp
		 * for the order directly and call the MemberOrder->saveOrder() function.
		 * This function is no longer used on the /adminpages/orders.php page.
		 */
		function updateTimestamp($year, $month, $day, $time = NULL)
		{
			if(empty($this->id))
				return false;		//need a saved order

			if ( empty( $time ) ) {
				// Just save the order date.
				$date = $year . '-' . $month . '-' . $day . ' 00:00:00';
			} else {
				$date = get_gmt_from_date( $year . '-' . $month . '-' . $day . ' ' . $time, 'Y-m-d H:i:s' );
			}

			global $wpdb;
			$this->sqlQuery = $wpdb->prepare( "UPDATE $wpdb->pmpro_membership_orders SET timestamp = %s WHERE id = %d LIMIT 1", $date, $this->id );

			do_action('pmpro_update_order', $this);
			if($wpdb->query($this->sqlQuery) !== "false") {
				$this->timestamp = strtotime( $date );
				do_action('pmpro_updated_order', $this);
				
				return $this->getMemberOrderByID($this->id);
			} else {
				return false;
			}
		}

		/**
		 * Save/update the values of the order in the database.
		 */
		function saveOrder()
		{
			global $wpdb;

			// Perform calculations before each order save.
			//get a random code to use for the public ID
			if(empty($this->code))
				$this->code = $this->getRandomCode();

			// Calculate datetime/timestamp. Datetime is what actually gets saved.
			if( empty( $this->datetime ) && empty( $this->timestamp ) ) {
				$this->timestamp = time();
				$this->datetime = date("Y-m-d H:i:s", $this->timestamp);				
			} elseif( empty( $this->datetime ) && ! empty( $this->timestamp ) && is_numeric( $this->timestamp ) ) {
				$this->datetime = date("Y-m-d H:i:s", $this->timestamp);	//get datetime from timestamp
			} elseif( empty( $this->datetime ) && ! empty( $this->timestamp ) ) {
				$this->datetime = $this->timestamp;		//must have a datetime in it
				$this->timestamp = strtotime( $this->datetime );	//fixing the timestamp
			}

			// Calculate checkout_id if necessary.
			if(empty($this->checkout_id) || intval($this->checkout_id)<1) {
				$highestval = $wpdb->get_var("SELECT MAX(checkout_id) FROM $wpdb->pmpro_membership_orders");
				$this->checkout_id = intval($highestval)+1;
			}

			// Deprecating cancelled status with subscriptions table update. Change to success.
			if ( $this->status == 'cancelled' ) {
				$this->status = 'success';
			}

			//build query
			if(!empty($this->id))
			{
				//set up actions
				$before_action = "pmpro_update_order";
				$after_action = "pmpro_updated_order";
				//update
				$this->sqlQuery = "UPDATE $wpdb->pmpro_membership_orders
									SET `code` = '" . esc_sql( $this->code ) . "',
									`session_id` = '" . esc_sql( $this->session_id ) . "',
									`user_id` = " . intval($this->user_id) . ",
									`membership_id` = " . intval($this->membership_id) . ",
									`paypal_token` = '" . esc_sql( $this->paypal_token ) . "',
									`billing_name` = '" . esc_sql($this->billing->name) . "',
									`billing_street` = '" . esc_sql($this->billing->street) . "',
									`billing_city` = '" . esc_sql($this->billing->city) . "',
									`billing_state` = '" . esc_sql($this->billing->state) . "',
									`billing_zip` = '" . esc_sql($this->billing->zip) . "',
									`billing_country` = '" . esc_sql($this->billing->country) . "',
									`billing_phone` = '" . esc_sql($this->billing->phone) . "',
									`subtotal` = '" . esc_sql( $this->subtotal ) . "',
									`tax` = '" . esc_sql( $this->tax ) . "',
									`total` = '" . esc_sql( $this->total ) . "',
									`payment_type` = '" . esc_sql( $this->payment_type ) . "',
									`cardtype` = '" . esc_sql( $this->cardtype ) . "',
									`accountnumber` = '" . esc_sql( $this->accountnumber ) . "',
									`expirationmonth` = '" . esc_sql( $this->expirationmonth ) . "',
									`expirationyear` = '" . esc_sql( $this->expirationyear ) . "',
									`status` = '" . esc_sql($this->status) . "',
									`gateway` = '" . esc_sql( $this->gateway ) . "',
									`gateway_environment` = '" . esc_sql( $this->gateway_environment ) . "',
									`payment_transaction_id` = '" . esc_sql($this->payment_transaction_id) . "',
									`subscription_transaction_id` = '" . esc_sql($this->subscription_transaction_id) . "',
									`timestamp` = '" . esc_sql($this->datetime) . "',
									`affiliate_id` = '" . esc_sql($this->affiliate_id) . "',
									`affiliate_subid` = '" . esc_sql($this->affiliate_subid) . "',
									`notes` = '" . esc_sql($this->notes) . "',
									`checkout_id` = " . intval($this->checkout_id) . "
									WHERE id = '" . esc_sql( $this->id ) . "'
									LIMIT 1";
			}
			else
			{
				//set up actions
				$before_action = "pmpro_add_order";
				$after_action = "pmpro_added_order";

				// Perform calculations before a new order is added.
				// Figure out how much we charged.
				$amount = ! empty( $this->InitialPayment ) ? (float)$this->InitialPayment : (float)$this->subtotal;

				// Fill subtotal if necessary.
				if ( empty( $this->subtotal ) ) {
					$this->subtotal = $amount;
				}

				// Calculate tax if necessary.
				$this->tax = empty( $this->tax ) ? $this->getTax(true) : $this->tax;

				// Calculate total.
				$this->total = empty( $this->total ) ? (float)$amount + (float)$this->tax : $this->total;
				
				//only on inserts, we might want to set the expirationmonth and expirationyear from ExpirationDate
				if( (empty($this->expirationmonth) || empty($this->expirationyear)) && !empty($this->ExpirationDate)) {
					$this->expirationmonth = substr($this->ExpirationDate, 0, 2);
					$this->expirationyear = substr($this->ExpirationDate, 2, 4);
				}
				
				//insert
				$this->sqlQuery = "INSERT INTO $wpdb->pmpro_membership_orders
								(`code`, `session_id`, `user_id`, `membership_id`, `paypal_token`, `billing_name`, `billing_street`, `billing_city`, `billing_state`, `billing_zip`, `billing_country`, `billing_phone`, `subtotal`, `tax`, `total`, `payment_type`, `cardtype`, `accountnumber`, `expirationmonth`, `expirationyear`, `status`, `gateway`, `gateway_environment`, `payment_transaction_id`, `subscription_transaction_id`, `timestamp`, `affiliate_id`, `affiliate_subid`, `notes`, `checkout_id`)
								VALUES('" . esc_sql( $this->code ) . "',
									   '" . esc_sql( session_id() ) . "',
									   " . intval($this->user_id) . ",
									   " . intval($this->membership_id) . ",
									   '" . esc_sql( $this->paypal_token ) . "',
									   '" . esc_sql(trim($this->billing->name)) . "',
									   '" . esc_sql(trim($this->billing->street)) . "',
									   '" . esc_sql($this->billing->city) . "',
									   '" . esc_sql($this->billing->state) . "',
									   '" . esc_sql($this->billing->zip) . "',
									   '" . esc_sql($this->billing->country) . "',
									   '" . esc_sql( cleanPhone($this->billing->phone) ) . "',
									   '" . esc_sql( $this->subtotal ) . "',
									   '" . esc_sql( $this->tax ) . "',
									   '" . esc_sql( $this->total ) . "',
									   '" . esc_sql( $this->payment_type ) . "',
									   '" . esc_sql( $this->cardtype ) . "',
									   '" . esc_sql( hideCardNumber($this->accountnumber, false) ) . "',
									   '" . esc_sql( $this->expirationmonth ) . "',
									   '" . esc_sql( $this->expirationyear ) . "',
									   '" . esc_sql($this->status) . "',
									   '" . esc_sql( $this->gateway ) . "',
									   '" . esc_sql( $this->gateway_environment ) . "',
									   '" . esc_sql($this->payment_transaction_id) . "',
									   '" . esc_sql($this->subscription_transaction_id) . "',
									   '" . esc_sql($this->datetime) . "',
									   '" . esc_sql($this->affiliate_id) . "',
									   '" . esc_sql($this->affiliate_subid) . "',
										'" . esc_sql($this->notes) . "',
									    " . intval($this->checkout_id) . "
									   )";
			}

			do_action($before_action, $this);
			if($wpdb->query($this->sqlQuery) !== false)
			{
				if(empty($this->id))
					$this->id = $wpdb->insert_id;

				/**
				 * Special case. We want to add `discount_code_id` as a property/db column in the future.
				 * For now, if we are saving an order with a non-null discount_code_id property,
				 * call updateDiscountCode to save the discount code use in the current pmpro_discount_codes_uses table.
				 *
				 * @DISCOUNT_CODE_ID_TODO
				 */
				if ( null !== $this->discount_code_id ) {
					$this->updateDiscountCode( $this->discount_code_id );
				}

				do_action($after_action, $this);

				// Create a subscription if we need to.
				$subscription = $this->get_subscription();

				// Check if the subscription has reached its billing limit.
				if ( ! empty( $subscription ) && 'active' === $subscription->get_status() && $subscription->billing_limit_reached() ) {
					// Cancel the subscription.
					$subscription->cancel_at_gateway();
				}

				//Lets only run this once the update has been run successfully.
				if ( $this->status !== $this->original_status ) {
				
					/**
					 * Runs when the order status changes
					 *
					 * @param $this object The current member order object
					 * @param $original_status The original status before changing to the new status
					 * 
					 * @since 2.9
					 */
					do_action( 'pmpro_order_status_' . $this->status, $this, $this->original_status );
					
					//Set the original status to the new status
					$this->original_status = $this->status;
				}

				return $this->getMemberOrderByID($this->id);
			}
			else
			{
				return false;
			}
		}

		/**
		 * Get a random code to use as the order code.
		 */
		function getRandomCode() {
			global $wpdb;

			// We mix this with the seed to make sure we get unique codes.
			static $count = 0;
			$count++;

			if( defined( 'AUTH_KEY' ) && defined( 'SECURE_AUTH_KEY' ) ) {
				$auth_code = AUTH_KEY;
				$secure_auth_code = SECURE_AUTH_KEY;
			} else {
				//Generate our own random string and hash it
				$auth_code = md5( rand() );
				$secure_auth_code = md5( rand() );
			}

			while( empty( $code ) ) {
				$scramble = md5( $auth_code . microtime() . $secure_auth_code . $count );
				$code = substr( $scramble, 0, 10 );
				$code = apply_filters( 'pmpro_random_code', $code, $this );	//filter
				$check = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $wpdb->pmpro_membership_orders WHERE code = %s LIMIT 1", $code ) );
				if( $check || is_numeric( $code ) ) {
					$code = NULL;
				}
			}

			return strtoupper( $code );
		}

		/**
		 * Update the status of the order in the database.
		 */
		function updateStatus( $newstatus ) {
			global $wpdb;

			if( empty( $this->id ) ) {
				return false;
			}				

			if ( 'cancelled' === $newstatus ) {
				// Only cancel subscription, not order.
				return $this->cancel();
			}

			$this->status = $newstatus;
			$this->sqlQuery = $wpdb->prepare( "UPDATE $wpdb->pmpro_membership_orders SET status = %s WHERE id = %d LIMIT 1", $newstatus, $this->id );			
			
			do_action( 'pmpro_update_order', $this );
			if ( $wpdb->query( $this->sqlQuery ) !== false ) {
				do_action('pmpro_updated_order', $this);
				
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Call the process step of the gateway class.
		 */
		function process()
		{
			if (is_object($this->Gateway)) {
				return $this->Gateway->process($this);
			}
		}

		/**
		 * For offsite gateways with a confirm step.
		 *
		 * @since 1.8
		 */
		function confirm()
		{
			if (is_object($this->Gateway)) {
				return $this->Gateway->confirm($this);
			}
		}

		/**
		 * Call the cancel step of the order's subscription if needed.
		 */
		function cancel() {			
			// Only need to cancel on the gateway if there is a subscription.
			$subscription = $this->get_subscription();
			if ( empty( $subscription ) ) {
				return true;
			}

			// Cancel the subscription.
			return $subscription->cancel_at_gateway();
		}

		/**
		 * Call the update method of the gateway class.
		 */
		function updateBilling()
		{
			if (is_object($this->Gateway)) {
				return $this->Gateway->update( $this );
			}
		}

		/**
		 * Call the getSubscriptionStatus method of the gateway class.
		 */
		function getGatewaySubscriptionStatus()
		{
			if (is_object($this->Gateway)) {
				return $this->Gateway->getSubscriptionStatus( $this );
			}
		}

		/**
		 * Call the getTransactionStatus method of the gateway class.
		 */
		function getGatewayTransactionStatus()
		{
			if (is_object($this->Gateway)) {
				return $this->Gateway->getTransactionStatus( $this );
			}
		}

		/** 
		 * Get TOS consent information.
		 * @since  1.9.5
		 */
		function get_tos_consent_log_entry() {
			if ( empty( $this->id ) ) {
				return false;
			}
			
			$consent_log = pmpro_get_consent_log( $this->user_id );
			foreach( $consent_log as $entry ) {
				if( $entry['order_id'] == $this->id ) {
					return $entry;
				}
			}

			return false;
		}

		/**
		 * Sets the billing address fields on the order object.
		 * Checks the last order for the same sub or pulls from user meta.
		 * @since 2.5.5
		 */
		function find_billing_address() {
			global $wpdb;

			if ( empty( $this->billing ) || empty( $this->billing->street ) ) {
				// We do not already have a billing address.
				$last_subscription_order = new MemberOrder();
				$last_subscription_order->getLastMemberOrderBySubscriptionTransactionID( $this->subscription_transaction_id );
				if ( ! empty( $last_subscription_order->billing ) && ! empty( $last_subscription_order->billing->street ) ) {
					// Last order in subscription has biling information. Pull data from there. 
					$this->Address1    = $last_subscription_order->billing->street;
					$this->City        = $last_subscription_order->billing->city;
					$this->State       = $last_subscription_order->billing->state;
					$this->Zip         = $last_subscription_order->billing->zip;
					$this->CountryCode = $last_subscription_order->billing->country;
					$this->PhoneNumber = $last_subscription_order->billing->phone;
					$this->Email       = $wpdb->get_var( $wpdb->prepare( "SELECT user_email FROM $wpdb->users WHERE ID = %d LIMIT 1", $this->user_id ) );

					$this->billing          = new stdClass();
					$this->billing->name    = $last_subscription_order->billing->name;
					$this->billing->street  = $last_subscription_order->billing->street;
					$this->billing->city    = $last_subscription_order->billing->city;
					$this->billing->state   = $last_subscription_order->billing->state;
					$this->billing->zip     = $last_subscription_order->billing->zip;
					$this->billing->country = $last_subscription_order->billing->country;
					$this->billing->phone   = $last_subscription_order->billing->phone;
				} else {
					// Last order did not have billing information. Try to pull from usermeta.
					$this->Address1    = get_user_meta( $this->user_id, "pmpro_baddress1", true );
					$this->City        = get_user_meta( $this->user_id, "pmpro_bcity", true );
					$this->State       = get_user_meta( $this->user_id, "pmpro_bstate", true );
					$this->Zip         = get_user_meta( $this->user_id, "pmpro_bzipcode", true );
					$this->CountryCode = get_user_meta( $this->user_id, "pmpro_bcountry", true );
					$this->PhoneNumber = get_user_meta( $this->user_id, "pmpro_bphone", true );
					$this->Email       = $wpdb->get_var( $wpdb->prepare( "SELECT user_email FROM $wpdb->users WHERE ID = %d LIMIT 1", $this->user_id ) );

					$this->billing          = new stdClass();
					$this->billing->name    = get_user_meta( $this->user_id, "pmpro_bfirstname", true ) . " " . get_user_meta( $this->user_id, "pmpro_blastname", true ) ;
					$this->billing->street  = $this->Address1;
					$this->billing->city    = $this->City;
					$this->billing->state   = $this->State;
					$this->billing->zip     = $this->Zip;
					$this->billing->country = $this->CountryCode;
					$this->billing->phone   = $this->PhoneNumber;
				}
			}
		}

		/**
		 * Delete an order and associated data.
		 */
		function deleteMe()
		{
			if(empty($this->id))
				return false;

			global $wpdb;
			$this->sqlQuery = $wpdb->prepare( "DELETE FROM $wpdb->pmpro_membership_orders WHERE id = %d LIMIT 1", $this->id );
			if($wpdb->query($this->sqlQuery) !== false)
			{
				do_action("pmpro_delete_order", $this->id, $this);
				return true;
			}
			else
				return false;
		}

		/*
		* Generates a test order on the fly for orders.
		*/
		function get_test_order() {
			global $current_user;

			//$test_order = $this->getEmptyMemberOrder();
			$all_levels = pmpro_getAllLevels();
			
			if ( ! empty( $all_levels ) ) {
				$first_level                = array_shift( $all_levels );
				$this->membership_id  = $first_level->id;
				$this->InitialPayment = $first_level->initial_payment;
			} else {
				$this->membership_id  = 1;
				$this->InitialPayment = 1;
			}
			$this->user_id             = $current_user->ID;
			$this->cardtype            = "Visa";
			$this->accountnumber       = "4111111111111111";
			$this->expirationmonth     = date( 'm', current_time( 'timestamp' ) );
			$this->expirationyear      = ( intval( date( 'Y', current_time( 'timestamp' ) ) ) + 1 );
			$this->ExpirationDate      = $this->expirationmonth . $this->expirationyear;
			$this->CVV2                = '123';
			$this->FirstName           = 'Jane';
			$this->LastName            = 'Doe';
			$this->Address1            = '123 Street';
			$this->billing             = new stdClass();
			$this->billing->name       = 'Jane Doe';
			$this->billing->street     = '123 Street';
			$this->billing->city       = 'City';
			$this->billing->state      = 'ST';
			$this->billing->country    = 'US';
			$this->billing->zip        = '12345';
			$this->billing->phone      = '5558675309';
			$this->gateway_environment = 'sandbox';
			$this->timestamp		   = time();
			$this->notes               = __( 'This is a test order used with the PMPro Email Templates addon.', 'paid-memberships-pro' );

			return apply_filters( 'pmpro_test_order_data', $this );
		}

		/**
		 * Get the PMPro_Subscription object for this order.
		 * If the subscription doesn't exist, it will be created.
		 *
		 * @return PMPro_Subscription|null The subscription object or null if the order is not a subscription.
		 */
		public function get_subscription() {
			// Make sure that this order is a part of a subscription.
			if ( empty( $this->subscription_transaction_id ) || empty( $this->gateway ) || empty( $this->gateway_environment ) ) {
				// We don't have all the info needed for a subscription. Bail.
				return null;
			}

			$get_subscription_args = array(
				'subscription_transaction_id' => $this->subscription_transaction_id,
				'gateway'                     => $this->gateway,
				'gateway_environment'  	      => $this->gateway_environment,
			);
			$existing_subscription = PMPro_Subscription::get_subscription( $get_subscription_args );
			if ( ! empty( $existing_subscription ) ) {
				// We already have a subscription for this order, return it.
				return $existing_subscription;
			}

			// Only create a subscription if this order is complete.
			if ( 'success' !== $this->status ) {
				// The order is not complete, so it isn't a valid subscription payment.
				return null;
			}

			if ( pmpro_is_checkout() ) {
				// We are processing a checkout. Get the level from the checkout.
				$subscription_level = $this->getMembershipLevelAtCheckout();
			} else {
				// Not at checkout. Get the level from the database.
				$subscription_level = $this->getMembershipLevel();
			}

			if ( empty( $subscription_level ) ) {
				// We couldn't get level information, so we shouldn't create a subscription.
				return null;
			}

			$create_subscription_args = array(
				'user_id'                     => $this->user_id,
				'membership_level_id'  		  => $this->membership_id,
				'gateway'                     => $this->gateway,
				'gateway_environment'  	      => $this->gateway_environment,
				'subscription_transaction_id' => $this->subscription_transaction_id,
				'status'                      => 'active',
				'billing_amount'              => empty( $subscription_level->billing_amount ) ? 0.00 : $subscription_level->billing_amount,
				'cycle_number'                => empty( $subscription_level->cycle_number ) ? 0 : $subscription_level->cycle_number,
				'cycle_period'                => empty( $subscription_level->cycle_period ) ? 'Month' : $subscription_level->cycle_period,
				'billing_limit'               => empty( $subscription_level->billing_limit ) ? 0 : $subscription_level->billing_limit,
				'trial_amount'                => empty( $subscription_level->trial_amount ) ? 0.00 : $subscription_level->trial_amount,
				'trial_limit'                 => empty( $subscription_level->trial_limit ) ? 0 : $subscription_level->trial_limit,
				'startdate'                   => date_i18n( 'Y-m-d H:i:s', $this->timestamp ),
				'next_payment_date'           => empty( $this->ProfileStartDate ) ? date_i18n( 'Y-m-d H:i:s', strtotime( '+ ' . $subscription_level->cycle_number . ' ' . $subscription_level->cycle_period, (int)$this->timestamp ) ) : $this->ProfileStartDate,
			);

			$new_subscription = PMPro_Subscription::create( $create_subscription_args );
			return ! empty( $new_subscription ) ? $new_subscription : null;
		}

		/**
		 * Does this order have any billing address fields set?
		 * @since 2.8
		 * @return bool True if ANY billing address field is non-empty.
		 *              False if ALL billing address fields are empty.
		 */
		function has_billing_address() {
			// This is sometimes set.
			if ( ! empty( $this->Address1 ) ) {
				return true;
			}
			
			// Avoid a warning if no billing object at all.
			if ( empty( $this->billing ) ) {
				return false;
			}
			
			// Check billing fields.
			if ( ! empty( $this->billing->name ) 
				|| ! empty( $this->billing->street )
				|| ! empty( $this->billing->city )
				|| ! empty( $this->billing->state )
				|| ! empty( $this->billing->country )
				|| ! empty( $this->billing->zip )
				|| ! empty( $this->billing->phone ) ) {
				return true;
			}
		
			return false;
		}
	} // End of Class
