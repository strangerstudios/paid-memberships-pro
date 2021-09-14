<?php
namespace PMPro\Tests\Helpers\Factory;

/**
 * THIS CLASS HAS BEEN MIGRATED ALREADY.
 */
class Level_Factory extends \WP_UnitTest_Factory_For_Thing {

	private $_format = [
		'%d',		// id
		'%s',		// name
		'%s',		// description
		'%s',		// confirmation
		'%f',		// initial_payment
		'%f',		// billing_amount
		'%d',		// cycle_number
		'%s',		// cycle_period
		'%d',		// billing_limit
		'%f',		// trial_amount
		'%d',		// trial_limit
		'%d',		// expiration_number
		'%s',		// expiration_period
		'%d',		// allow_signups
	];
	private $_table;
	private $_wpdb;

	public function __construct( $factory = null ) {
		parent::__construct( $factory );

		global $wpdb;

		$this->_wpdb                          = $wpdb;
		$this->_table                         = $wpdb->pmpro_membership_levels;
		$this->default_generation_definitions = [
			'id'                => '',
			'name'              => new \WP_UnitTest_Generator_Sequence( 'Level name %s' ),
			'description'       => new \WP_UnitTest_Generator_Sequence( 'Level description %s' ),
			'confirmation'      => '',
			'initial_payment'   => 1,
			'billing_amount'    => 1,
			'cycle_number'      => 1,
			'cycle_period'      => new \WP_UnitTest_Generator_Sequence( 'Level cycle period %s' ),
			'billing_limit'     => 0,
			'trial_amount'      => 0,
			'trial_limit'       => 0,
			'expiration_number' => 0,
			'expiration_period' => '',
			'allow_signups'     => 1
		];
	}

	public function create_object( $args ) {
		$format = $this->_format;

		$this->_wpdb->insert( $this->_table, $args, $format );

		return $this->_wpdb->insert_id;
	}

	public function update_object( $id, $fields ) {
		$fields = [ 'id' => $id ] + $fields;
		$format = $this->_format;
		$table  = $this->_table;

		$this->_wpdb->update( $table, $fields, $format );

		return $this->_wpdb->insert_id;
	}

	public function get_object_by_id( $id ) {
		$table = $this->_table;

		return $this->_wpdb->get_results( $this->_wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id ) );
	}
}