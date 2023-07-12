<?php

namespace PMPro\Test_Support\Factories;

use WP_UnitTest_Factory_For_Thing as Test_Factory;
use WP_UnitTest_Generator_Sequence as Test_Sequence;

class PMPro_OrderFactory extends Test_Factory {

	/**
	 * The DB column formats.
	 *
	 * The keys
	 *
	 * @var string[]
	 */
	private $_format = [
		'id'                          => '%d',
		'code'                        => '%s',
		'session_id'                  => '%s',
		'user_id'                     => '%d',
		'membership_id'               => '%d',
		'paypal_token'                => '%s',
		'billing_name'                => '%s',
		'billing_street'              => '%s',
		'billing_city'                => '%s',
		'billing_state'               => '%s',
		'billing_zip'                 => '%s',
		'billing_country'             => '%s',
		'billing_phone'               => '%s',
		'subtotal'                    => '%s',
		'tax'                         => '%s',
		'couponamount'                => '%s',
		'checkout_id'                 => '%d',
		'certificate_id'              => '%d',
		'certificateamount'           => '%s',
		'total'                       => '%s',
		'payment_type'                => '%s',
		'cardtype'                    => '%s',
		'accountnumber'               => '%s',
		'expirationmonth'             => '%s',
		'expirationyear'              => '%s',
		'status'                      => '%s',
		'gateway'                     => '%s',
		'gateway_environment'         => '%s',
		'payment_transaction_id'      => '%s',
		'subscription_transaction_id' => '%s',
		'timestamp'                   => '%s',
		'affiliate_id'                => '%s',
		'affiliate_subid'             => '%s',
		'notes'                       => '%s',
	];

	/**
	 * The factory object table.
	 *
	 * @var string
	 */
	private $_table;

	/**
	 * PMPro_LevelFactory constructor.
	 *
	 * @param object $factory Global factory that can be used to create other objects on the system
	 * @param array $default_generation_definitions Defines what default values should the properties of the object have. The default values
	 * can be generators -- an object with next() method. There are some default generators: {@link WP_UnitTest_Generator_Sequence},
	 */
	public function __construct( $factory = null, $default_generation_definitions = array() ) {
		parent::__construct( $factory, $default_generation_definitions );

		global $wpdb;

		$this->_table = $wpdb->pmpro_membership_orders;

		$this->default_generation_definitions = [
			'id'                          => 0,
			'code'                        => new Test_Sequence( 'code_%s' ),
			'session_id'                  => '',
			'user_id'                     => 0,
			'membership_id'               => 0,
			'paypal_token'                => '',
			'billing_name'                => 'Jane Doe',
			'billing_street'              => '123 Street',
			'billing_city'                => 'City',
			'billing_state'               => 'ST',
			'billing_zip'                 => '12345',
			'billing_country'             => 'US',
			'billing_phone'               => '5558675309',
			'subtotal'                    => '50.00',
			'tax'                         => '2.50',
			'couponamount'                => '',
			'checkout_id'                 => 0,
			'certificate_id'              => 0,
			'certificateamount'           => '',
			'total'                       => '52.50',
			'payment_type'                => '',
			'cardtype'                    => 'Visa',
			'accountnumber'               => '4111111111111111',
			'expirationmonth'             => gmdate( 'm', current_time( 'timestamp' ) ),
			'expirationyear'              => ( (int) gmdate( 'Y', current_time( 'timestamp' ) ) + 1 ),
			'status'                      => 'success',
			'gateway'                     => 'check',
			'gateway_environment'         => 'sandbox',
			'payment_transaction_id'      => new Test_Sequence( 'ch_%s' ),
			'subscription_transaction_id' => '',
			'timestamp'                   => gmdate( 'Y-m-d H:i:s' ),
			'affiliate_id'                => '',
			'affiliate_subid'             => '',
			'notes'                       => 'This is a test order',
		];
	}

	public function create_object( $args ) {
		global $wpdb;

		$format = [];

		foreach ( $args as $arg => $value ) {
			$format[] = isset( $this->_format[ $arg ] ) ? $this->_format[ $arg ] : '%s';
		}

		$wpdb->insert( $this->_table, $args, $format );

		return $wpdb->insert_id;
	}

	public function update_object( $object, $args ) {
		global $wpdb;

		$args = [ 'id' => $object ] + $args;

		$format = [];

		foreach ( $args as $arg => $value ) {
			$format[] = isset( $this->_format[ $arg ] ) ? $this->_format[ $arg ] : '%s';
		}

		$wpdb->update( $this->_table, $args, $format );

		return $wpdb->insert_id;
	}

	public function get_object_by_id( $object_id ) {
		global $wpdb;

		$table = $this->_table;

		return $wpdb->get_row(
			$wpdb->prepare(
				"
					SELECT *
					FROM {$table}
					WHERE id = %d
					LIMIT 1
				",
				$object_id
			)
		);
	}
}