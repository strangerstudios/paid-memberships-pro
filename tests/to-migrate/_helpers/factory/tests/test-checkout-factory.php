<?php

/**
 * Checkout_Factory Tests
 */
 
use PMPro\Tests\Base;
 
/**
 * @testdox Checkout_Factory
 *
 * @group factory
 */
class Checkout_Factory_Test extends Base {
    
    /**
     *  Data provider for the test_create() method.
     */
    function data_create() {
        return [
            'Empty order' => [
                [
                    'order' => new \MemberOrder(),
                ]
            ],
            'Logged in user' => [
                [
                    'order' => new \MemberOrder(),
                    'is_logged_in' => 3,
                ]
            ],
        ];
    }
    
    /**
     * Test the create() method of the Checkout_Factory class.
     * 
     * @testdox create() 
     * 
     * @dataProvider data_create
     */
    function test_create( $args ) {
        $checkout = $this->factory->checkout->create( $args );
        $this->assertInstanceOf( \StdClass::class, $checkout );
        // TODO: Check for callbacks?
        foreach ( $args as $key => $value ) {
            $this->assertEquals( $args[$key], $checkout->$key );
            
            // Test logged in users.
            if ( 'is_logged_in' == $key && ! empty( $value ) ) {
                $this->assertEquals( $value, $GLOBALS['current_user']->ID );
                $this->assertTrue( is_user_logged_in(), 'User was not logged in.' );
            }
        }
    }
    
    /**
     *  Data provider for the test_set_globals() method.
     */
    function data_set_globals() {
        return [
            "\$_SESSION['key'] = 'value'; \$_REQUEST['key2'] = 'value2'; " => [
                [
                    'globals' => [
                        'session' => [
                            'key' => 'value'
                        ],
                        'request' => [
                            'key2' => 'value2'
                        ],
                    ]
                ]
            ],
            "empty arrays" => [
                [
                    'globals' => [
                        'session' => [],
                        'request' => [],
                    ]
                ]
            ],
        ];
    }
    
    /**
     * Test the set_globals() method of the Checkout_Factory class.
     * 
     * @testdox set_globals() 
     * 
     * @dataProvider data_set_globals
     */
    function test_set_globals( $args ) {
        
        $checkout = $this->factory->checkout->create( $args );
        
        foreach( $checkout->globals as $type => $array  ) {
            
            $correct_type = strtoupper( $type );
            
            if ( empty( $array ) ) {
                $this->assertEmpty( $GLOBALS["_$correct_type"], "\$_['$correct_type'] is not empty." );
            }
            
            foreach( $array as $key => $value ) {
                $this->assertEquals( $GLOBALS["_{$correct_type}"][$key], $value, "Couldn't set \$_['$correct_type']['$key'] to $value" );
            }
        }
    }
    
//    /**
//     *  Data provider for the test_set_scenario() method.
//     */
//    function data_set_scenario() {
//        return [
//            // 'Scenario 1' => [
//            //     'scenario' => NEW_CHECKOUT_FOR_LEVEL_1
//            // ],
//        ];
//    }
//
//    /**
//     * Test the set_scenario() method of the Checkout_Factory class.
//     *
//     * @testdox set_scenario()
//     *
//     * @dataProvider data_set_scenario
//     */
//    function test_set_scenario( $args ) {
//        $this->markTestIncomplete( 'Test makes no assertions.' );
//        $checkout = $this->factory->checkout->create( $args );
//    }
//

}