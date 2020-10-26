<?php
namespace PMPro\Tests;

use PMPro\Tests\Helpers\Factory\Level;
use PMPro\Tests\Helpers\Factory\Order_Factory;
use PMPro\Tests\Helpers\Factory\Checkout_Factory;
use PMPro\Tests\Helpers\Traits\Utility;

abstract class Base Extends \WP_UnitTestCase {

	use Utility;

	function __get( $name ) {
		if ( 'factory' === $name ) {
			return $this->_pmpro_factory();
		}
	}

	/**
	 * Fetches the factory object for generating WordPress & PMPro fixtures.
	 *
	 * @return WP_UnitTest_Factory The fixture factory.
	 */
	protected function _pmpro_factory() {
		$factory = self::factory();

		$factory->pmpro_level = new Level( $this );
		$factory->checkout = new Checkout_Factory( $this );
		$factory->order = new Order_Factory( $this );

		return $factory;
	}

	/**
	 * Runs the routine after all tests have been run.
	 */
	public static function tearDownAfterClass() {
		self::_delete_all_pmpro_data();

		parent::tearDownAfterClass();
	}

	protected static function _delete_all_pmpro_data() {
		global $wpdb;

		$tables = [
			$wpdb->pmpro_discount_codes,
			$wpdb->pmpro_discount_codes_levels,
			$wpdb->pmpro_discount_codes_uses,
			$wpdb->pmpro_membership_levelmeta,
			$wpdb->pmpro_membership_levels,
			$wpdb->pmpro_membership_ordermeta,
			$wpdb->pmpro_membership_orders,
			$wpdb->pmpro_memberships_categories,
			$wpdb->pmpro_memberships_pages,
			$wpdb->pmpro_memberships_users,
		];

		foreach ( $tables as $table ) {
			$wpdb->query( "DELETE FROM {$table}" );
			$wpdb->query( "ALTER TABLE {$table} AUTO_INCREMENT = 0" );
		}
	}

} // end of class

//EOF
