<?php

namespace PMPro\Test_Support\Factories;

use WP_UnitTest_Factory_For_Thing as Test_Factory;
use WP_UnitTest_Generator_Sequence as Test_Sequence;

class PMPro_SubscriptionFactory extends Test_Factory {

	/**
	 * The DB column formats.
	 *
	 * The keys
	 *
	 * @var string[]
	 */
	private $_format = [
		'id'                          => '%d',
		'user_id'                     => '%d',
		'membership_level_id'         => '%d',
		'gateway'                     => '%s',
		'gateway_environment'         => '%s',
		'subscription_transaction_id' => '%s',
		'status'                      => '%s',
		'startdate'                   => '%s',
		'enddate'                     => '%s',
		'next_payment_date'           => '%s',
		'billing_amount'              => '%f',
		'cycle_number'                => '%d',
		'cycle_period'                => '%s',
		'billing_limit'               => '%d',
		'trial_amount'                => '%f',
		'trial_limit'                 => '%d',
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

		$this->_table = $wpdb->pmpro_subscriptions;

		$this->default_generation_definitions = [
			'id'                          => 0,
			'user_id'                     => 0,
			'membership_level_id'         => 0,
			'gateway'                     => 'check',
			'gateway_environment'         => 'sandbox',
			'subscription_transaction_id' => new Test_Sequence( 'sub_%s' ),
			'status'                      => 'active',
			'startdate'                   => gmdate( 'Y-m-d H:i:s' ),
			'enddate'                     => '',
			'next_payment_date'           => gmdate( 'Y-m-d H:i:s', strtotime( '+1 year' ) ),
			'billing_amount'              => 0,
			'cycle_number'                => 0,
			'cycle_period'                => 'Month',
			'billing_limit'               => 0,
			'trial_amount'                => 0,
			'trial_limit'                => 0,
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