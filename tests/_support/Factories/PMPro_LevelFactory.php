<?php

namespace PMPro\Test_Support\Factories;

use WP_UnitTest_Factory_For_Thing as Test_Factory;
use WP_UnitTest_Generator_Sequence as Test_Sequence;

class PMPro_LevelFactory extends Test_Factory {

	/**
	 * The DB column formats.
	 *
	 * The keys
	 *
	 * @var string[]
	 */
	private $_format = [
		'id'                => '%d',
		'name'              => '%s',
		'description'       => '%s',
		'confirmation'      => '%s',
		'initial_payment'   => '%f',
		'billing_amount'    => '%f',
		'cycle_number'      => '%d',
		'cycle_period'      => '%s',
		'billing_limit'     => '%d',
		'trial_amount'      => '%f',
		'trial_limit'       => '%d',
		'expiration_number' => '%d',
		'expiration_period' => '%s',
		'allow_signups'     => '%d',
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

		$this->_table = $wpdb->pmpro_membership_levels;

		$this->default_generation_definitions = [
			'id'                => '',
			'name'              => new Test_Sequence( 'Level name %s' ),
			'description'       => new Test_Sequence( 'Level description %s' ),
			'confirmation'      => '',
			'initial_payment'   => 1,
			'billing_amount'    => 1,
			'cycle_number'      => 1,
			'cycle_period'      => new Test_Sequence( 'Level cycle period %s' ),
			'billing_limit'     => 0,
			'trial_amount'      => 0,
			'trial_limit'       => 0,
			'expiration_number' => 0,
			'expiration_period' => '',
			'allow_signups'     => 1,
		];
	}

	public function create_object( $args ) {
		global $wpdb;

		$wpdb->insert( $this->_table, $args, $this->_format );

		return $wpdb->insert_id;
	}

	public function update_object( $object, $fields ) {
		global $wpdb;

		$fields = [ 'id' => $object ] + $fields;

		$wpdb->update( $this->_table, $fields, $this->_format );

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