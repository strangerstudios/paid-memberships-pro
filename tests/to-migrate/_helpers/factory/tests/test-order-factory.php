<?php

/**
 * Test Order Factory
 */
 
use PMPro\Tests\Base;
 
/**
 * @testdox Order_Factory
 *
 * @group factory
 */
class Order_Factory_Test extends Base {
    
    /**
     *  Data provider for the test_create() method.
     */
    function data_create() {
        return [
            'New order' => [ [] ], 
            'user_id 2' => [
                [
                    'user_id' => 2
                ]
            ], 
            'membership_id 3' => [
                [
                    'membership_id' => 3
                ]
            ], 
        ];
    }
    
    /**
     * Test the create() method.
     * 
     * @testdox create() 
     * 
     * @dataProvider data_create
     */
    function test_create( $args ) {
        $checkout = $this->factory->order->create( $args );
        $this->assertInstanceOf( \MemberOrder::class, $checkout );
        foreach ( $args as $key => $value ) {
            $this->assertEquals( $args[$key], $checkout->$key );
        }
    }
    
}
