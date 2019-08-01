<?php
namespace PMPro\Tests\Helpers\Factory;

class Order extends \WP_UnitTest_Factory_For_Thing {

	public function __construct( $factory = null ) {
		parent::__construct( $factory );
	}

	public function create_object( $args ) {
		$order = new \MemberOrder();
		return $order->saveOrder();
	}

	public function update_object( $id, $fields ) {
		$order = new \MemberOrder( $id );
		foreach( $fields as $key => $value ) {
			$order->$key = $value;
		}
		return $order->saveOrder();
	}

	public function get_object_by_id( $id ) {
		return new \MemberOrder( $id );
	}
}