<?php

namespace PMPro\Tests\Classes\Gateways;

use PMPro\Tests\Base;
use PMPro\Tests\Helpers\Factory\Checkout_Factory;
use PMPro\Tests\Helpers\Factory\Order_Factory;

/**
 * @group stripe
 * @testdox PMProGateway_stripe
 * @covers \PMProGateway_stripe
 */
class PMProGateway_stripe_Test extends Base {

	private static $mock_api_initialized;

    /**
     * Skip all tests for now.
     */
    function setUp() {
        $this->markTestSkipped( 'Tests need work -- skipping for now.' );
    }

    //*********************************************
    // Helper Methods
    //*********************************************

    /**
     * Use the stripe-mock API for running tests.
     *
     * @beforeClass
     */
    public static function use_stripe_mock_api()
    {
        // set up your tweaked Curl client
        $curl = new \Stripe\HttpClient\CurlClient([CURLOPT_PROXY => 'localhost:12111']);
        // tell Stripe to use the tweaked client
        \Stripe\ApiRequestor::setHttpClient($curl);
        // Set API key and base.
        \Stripe\Stripe::setApiKey('sk_test_123');
        \Stripe\Stripe::$apiBase = 'http://api.stripe.com';

        // Test the gateway.
        try {
            $charge = \Stripe\Charge::retrieve('ch_123');
            if (!empty($charge->id)) {
                self::$mock_api_initialized = true;
            }
        } catch (\Stripe\Error $e) {
            self::$mock_api_initialized = false;
        }
    }

    /**
     * Data provider for test_api
     */
    function data_test_api()
    {
        return [
            [
                '\Stripe\Customer',
                'create',
                [],
            ],
            [
                '\Stripe\Charge',
                'retrieve',
                'ch_123',
            ],
            [
                '\Stripe\PaymentMethod',
                'create',
                [],
            ],
            [
                '\Stripe\PaymentIntent',
                'create',
                ['amount' => 1000, 'currency' => 'usd'],
            ],
            [
                'not a class',
                'no method',
                'nada',
            ],
        ];
    }

    /**
     * @testdox Testing the API: $class::$method( $params )
     *
     * @dataProvider data_test_api
     */
    function test_api($class, $method, $params)
    {

        $this->skip_test_if_api_not_initialized();

        if (!class_exists($class)) {
            $this->expectException(\Error::class);
        }

        $object = $class::$method($params);

        if (!empty($object)) {
            $this->assertInstanceOf($class, $object);
        }
    }

    /**
     * Test if the mock API is working.
     */
    function skip_test_if_api_not_initialized()
    {
        if (!self::$mock_api_initialized) {
            $this->markTestSkipped('Unable to use stripe-mock server.');
        }
    }

    function tearDown()
    {

        // Clear session vars.
        if (isset($_SESSION)) {
            foreach ($_SESSION as $key => $value) {
                unset($_SESSION[$key]);
            }
        }

        // Clear request vars.
        if (isset($_REQUEST)) {
            foreach ($_REQUEST as $key => $value) {
                unset($_REQUEST[$key]);
            }
        }

    }


    //*********************************************
    // Checkout Tests
    //*********************************************

    /**
     * dataProvider for test_process()
     *
     * @return array
     */
    function data_process() {

        $data_sets = [];

        // $10 once; new Customer; new PaymentIntent; No auth
        $name = '$10 once; new Customer; new PaymentIntent; No auth';
        $order = $this->factory->order->create();
        $order->stripeToken = 'pm_no_auth';
        $order->setGateway( 'stripe' );
        $checkout = [
            'order' => $order,
            'globals' => [
                'session' => [],
            ]
        ];
        $expected = [
            true, ['status' => 'success'],
        ];
        $data_sets[$name] = [ $checkout, $expected ];

        // $10 once; PaymentIntent already confirmed
        $name = '$10 once; PaymentIntent already confirmed';
	    $order = $this->factory->order->create();
	    $order->setGateway( 'stripe' );
	    $order->InitialPayment = 10;
	    $order->Gateway->customer = new \Stripe\Customer( ['id' => 'cus_123'] );
	    $payment_intent = new \Stripe\PaymentIntent();
	    $payment_intent->status = 'succeeded';
        $order->Gateway->payment_intent = $payment_intent;
	    $checkout = [
	        'order' => $order,
        ];
	    $expected = [
	        true, ['status' => 'success'],
        ];
        $data_sets[$name] = [ $checkout, $expected ];

        // $10 once; PaymentIntent succeeded
        $name = '$10 once; PaymentIntent succeeded';
        $order = $this->factory->order->create();
        $order->setGateway( 'stripe' );
        $order->InitialPayment = 10;
        $order->Gateway->customer = new \Stripe\Customer( ['id' => 'cus_123'] );
        $order->stripeToken = 'pm_succeeded';
        $order->Gateway->customer = new \Stripe\Customer();
        $checkout = [
            'order' => $order,
            'globals' => [
                'session' => []
            ],
        ];
        $expected = [
            true, ['status' => 'success'],
        ];
        $data_sets[$name] = [ $checkout, $expected ];

        // $10 once; PaymentIntent requires action
        $name = '$10 once; PaymentIntent requires action';
        $order = $this->factory->order->create();
        $order->setGateway( 'stripe' );
        $order->InitialPayment = 10;
        $order->Gateway->customer = new \Stripe\Customer( ['id' => 'cus_123'] );
        $order->stripeToken = 'pm_visa';
        $order->Gateway->customer = new \Stripe\Customer();
        $checkout = [
            'order' => $order,
            'globals' => [
                'session' => []
            ],
        ];
        $expected = [
            true, ['status' => 'success'],
        ];
        $data_sets[$name] = [ $checkout, $expected ];

	    return $data_sets;
    }

    /**
     * Test the process() method.
     *
     * @param $args
     * @dataProvider data_process
     * @testdox process()
     */
    function test_process( $args, $expected ) {

        $this->skip_test_if_api_not_initialized();

	    $checkout = $this->factory->checkout->create( $args );
        $order = $checkout->order;
	    $result = $order->Gateway->process( $order );

        foreach( $expected as $k => $v ) {
            if ( ! is_array( $v ) ) {
                $this->assertEquals( $result, $v );
            }
        }
    }

	//*********************************************
	// Order Processing Tests
	//*********************************************
	
	/**
	  * Data provider for test_set_customer()
	  */
	function data_set_customer() {
		
		// TODO: Test these cases.
		// Logged in user; customer ID in user meta
		// Logged in user; previous order with sub ID
		// Order has error already
		// ???
		
		// New user, New Customer
		// TODO: Use payment_method_id instead of stripeToken
		$order_args = [
			'stripeToken' => 'tok_visa',
		];
		$order = $this->factory->order->create( $order_args );
		$order->setGateway( 'stripe' );
		$checkout_args = [
			'order' => $order,
		];
		
		// Logged in user; no Customer
		$checkout2_args = [
			'order' => $order,
			'is_logged_in' => 1,
		];
		
		// Invalid PaymentMethod - cus123
		// $checkout_args2 = [
		// 	'order' => $order,
		// 	'globals' => [
		// 		'request' => [
		// 			'payment_method_id' => 'cus_123',
		// 		],
		// 	]
		// ];
		
		// // $_REQUEST['payment_method_id'] empty
		// $checkout_args3 = [
		// 	'order' => $order,
		// ];
		
		return [
			"New user; New Customer" => [
				$checkout_args,
				true
			],	
			"Logged in user; New Customer" => [
				$checkout2_args,
				true
			],	
			// "\$_REQUEST['payment_method_id'] empty" => [
			// 	$checkout_args3,
			// 	false
			// ],	
		];
	}
	
	/**
	  * Test the set_customer() method.
	  *
	  * @testdox set_customer()
	  *
	  * @dataProvider data_set_customer
	  */
	function test_set_customer( $checkout_args, $expected ) {
		
		$this->skip_test_if_api_not_initialized;
		
		$checkout = $this->factory->checkout->create( $checkout_args );
		$actual = $checkout->order->Gateway->set_customer( $checkout->order );
		$order = $checkout->order;
		
		// Assertions
		$this->assertEquals( $expected, $actual );
		
		if ( false == $expected ) {
			$this->assertNotEmpty( $order->error );
		} else {
			$this->assertInstanceOf( \Stripe\Customer::class, $order->Gateway->customer );
		}
		
	}
	
	 /**
	  * Data provider for test_create_payment_intent()
	  */
	function data_create_payment_intent() {
		
		// $10 initial payment; USD
		$order_args = [
			'InitialPayment' => 10
		];
		$order = $this->factory->order->create();
		$order->setGateway( 'stripe' );
		$order->Gateway->payment_method = new \Stripe\PaymentMethod( ['id' => 'pm_123'] );
		$order->Gateway->customer = new \Stripe\Customer( ['id' => 'cus_123'] );
		$checkout_args = [
			'order' => $order,
			'globals' => [
				'global' => [
					'pmpro_currency' => 'usd',
				]
			],
		];
		
		return [
			"$10 initial payment; USD" => [
				$checkout_args,
				true
			],	
		];
	}
	
	/**
	  * Test the create_payment_intent() method.
	  *
	  * @testdox create_payment_intent()
	  *
	  * @dataProvider data_create_payment_intent
	  */
	function test_create_payment_intent( $checkout_args, $expected ) {
		
		$this->skip_test_if_api_not_initialized;
		
		$checkout = $this->factory->checkout->create( $checkout_args );
		$payment_intent = $checkout->order->Gateway->create_payment_intent( $checkout->order );
		$order = $checkout->order;
		
		if ( false == $expected ) {
			$this->assertNotEmpty( $order->error );
		} else {
			$this->assertInstanceOf( \Stripe\PaymentIntent::class, $payment_intent );
			$this->assertEquals( 1000, $payment_intent->amount );
		}
	}
	 
	 /**
	  * Data provider for test_get_payment_intent()
	  */
	function data_get_payment_intent() {
		
		// TODO: Test these cases:
		
		// No current PaymentIntent
		$order = $this->factory->order->create();
		$order->setGateway( 'stripe' );
		$order->Gateway->payment_method = new \Stripe\PaymentMethod( ['id' => 'pm_123'] );
		$order->Gateway->customer = new \Stripe\Customer( ['id' => 'cus_123'] );
		$checkout_args = [
			'order' => $order,
		];
		
		// PaymentIntent in session
		$order2 = $this->factory->order->create();
		$order2->setGateway( 'stripe' );
		$order2->Gateway->payment_method = new \Stripe\PaymentMethod( ['id' => 'pm_123'] );
		$order2->Gateway->customer = new \Stripe\Customer( ['id' => 'cus_123'] );
		$checkout2_args = [
			'order' => $order2,
			'globals' => [
				'session' => [
					'pmpro_stripe_payment_intent' => new \Stripe\PaymentIntent( [ 'id' => 'pi_in_session' ] ),
				]
			],
		];
		
		// Order already has error
		$order3 = $this->factory->order->create();
		$order3->setGateway( 'stripe' );
		$order3->Gateway->payment_method = new \Stripe\PaymentMethod( ['id' => 'pm_123'] );
		$order3->Gateway->customer = new \Stripe\Customer( ['id' => 'cus_123'] );
		$order3->error = 'testing';
		$checkout3_args = [
			'order' => $order3,
		];
		
		return [
			"No current PaymentIntent" => [
				$checkout_args,
				true
			],	
			"PaymentIntent in session" => [
				$checkout2_args,
				true
			],	
			"Order has error already" => [
				$checkout3_args,
				false
			],	
		];
	}
	
	/**
	  * Test the get_payment_intent() method.
	  *
	  * @testdox get_payment_intent2()
	  *
	  * @dataProvider data_get_payment_intent
	  */
	function test_get_payment_intent( $checkout_args, $expected ) {
		
		$this->skip_test_if_api_not_initialized;
		
		$checkout = $this->factory->checkout->create( $checkout_args );
		$payment_intent = $checkout->order->Gateway->get_payment_intent2( $checkout->order );
		$order = $checkout->order;
		
		if ( false == $expected ) {
			$this->assertNotEmpty( $order->error );
		} else {
			$this->assertInstanceOf( \Stripe\PaymentIntent::class, $payment_intent );
			$this->assertEquals( 1000, $payment_intent->amount );
		}
	}
	 
	 /**
	  * Data provider for test_set_payment_intent()
	  */
	function data_set_payment_intent() {
		
		// Got PaymentIntent
		$order = $this->factory->order->create();
		$order->setGateway( 'stripe' );
		$order->Gateway->customer = new \Stripe\Customer( ['id' => 'cus_123'] );
		$order->Gateway->payment_method = new \Stripe\PaymentMethod( ['id' => 'pm_4242'] );
		$checkout_args = [
			'order' => $order,
		];
		
		// Failed to get PaymentIntent
		// $order2 = $this->factory->order->create();
		// $order2->setGateway( 'stripe' );
		// $checkout2_args = [
		// 	'order' => $order2,
		// ];
		
		// Order has error already
		$order3 = $this->factory->order->create();
		$order3->setGateway( 'stripe' );
		$order3->error = 'testing';
		$checkout3_args = [
			'order' => $order3,
		];
		
		return [
			"Got PaymentIntent" => [
				$checkout_args,
				true
			],	
			// "Failed to get PaymentIntent" => [
			// 	$checkout2_args,
			// 	false
			// ],	
			"Order has error already" => [
				$checkout3_args,
				false
			],	
		];
	}
	
	/**
	  * Test the set_payment_intent() method.
	  *
	  * @testdox set_payment_intent()
	  *
	  * @dataProvider data_set_payment_intent
	  */
	function test_set_payment_intent( $checkout_args, $expected ) {
		$this->skip_test_if_api_not_initialized;
		
		$checkout = $this->factory->checkout->create( $checkout_args );
		$checkout->order->Gateway->set_payment_intent( $checkout->order );
		$order = $checkout->order;
		$payment_intent = pmpro_get_session_var( 'pmpro_stripe_payment_intent' );
		
		if ( false == $expected ) {
			$this->assertNotEmpty( $order->error );
		} else {
			$this->assertInstanceOf( \Stripe\PaymentIntent::class, $payment_intent );
			$this->assertEquals( 1000, $payment_intent->amount );
		}
	}
	
	/**
	 * Data provider for test_confirm_payment_intent()
	 */
	function data_confirm_payment_intent() {
		
		// Already confirmed
		$order = $this->factory->order->create();
		$order->setGateway( 'stripe' );
		$order->Gateway->payment_intent = new \Stripe\PaymentIntent( ['id' => 'pi_confirmed'] );
		$order->Gateway->payment_intent->status = 'succeeded';
		$order->Gateway->payment_method = new \Stripe\PaymentMethod( ['id' => 'pm_3220'] );
		$checkout_args = [
			'order' => $order,
		];
		
		// Requires action
		$order2 = $this->factory->order->create();
		$order2->setGateway( 'stripe' );
		$order2->Gateway->payment_intent = new \Stripe\PaymentIntent( ['id' => 'pi_requires_action'] );
		$order2->Gateway->payment_intent->status = 'requires_payment_method';
		$order2->Gateway->payment_method = new \Stripe\PaymentMethod( ['id' => 'pm_3220'] );
		$checkout2_args = [
			'order' => $order2,
		];
		
		//  Succeeded
		$order3 = $this->factory->order->create();
		$order3->setGateway( 'stripe' );
		$order3->Gateway->payment_intent = new \Stripe\PaymentIntent( ['id' => 'pi_succeeded'] );
		$order3->Gateway->payment_intent->status = 'requires_payment_method';
		$order3->Gateway->payment_method = new \Stripe\PaymentMethod( ['id' => 'pm_3220'] );
		$checkout3_args = [
			'order' => $order3,
		];
		
		return [
			"Already confirmed" => [
				$checkout_args,
				true
			],	
			"Requires action" => [
				$checkout2_args,
				false
			],	
			"Succeeded" => [
				$checkout3_args,
				true
			],	
		];
	}
	
	/**
	  * Test the confirm_payment_intent() method.
	  *
	  * @testdox confirm_payment_intent()
	  *
	  * @dataProvider data_confirm_payment_intent
	  */
	function test_confirm_payment_intent( $checkout_args, $expected ) {
		
		$this->skip_test_if_api_not_initialized;
		
		$checkout = $this->factory->checkout->create( $checkout_args );
		$checkout->order->Gateway->confirm_payment_intent( $checkout->order );
		$order = $checkout->order;
		$payment_intent = $order->Gateway->payment_intent;
		
		if ( false == $expected ) {
			$this->assertNotEmpty( $order->error );
		} else {
			$this->assertInstanceOf( \Stripe\PaymentIntent::class, $payment_intent );
			$this->assertEquals( 'succeeded', $payment_intent->status );
		}
	}

	/**
	  * Data provider for test_process_charges()
	  */
	function data_process_charges() {
		
		// TODO: Test these cases:
		// No Initial Payment
		
		// Initial Payment; PaymentIntent Already Confirmed
		$order1 = $this->factory->order->create();
		$order1->setGateway( 'stripe' );
		$order1->Gateway->payment_intent = new \Stripe\PaymentIntent( ['id' => 'pi_confirmed'] );
		$order1->Gateway->payment_intent->status = 'succeeded';
		$order1->Gateway->payment_method = new \Stripe\PaymentMethod( ['id' => 'pm_4242'] );
		$order1->Gateway->customer = new \Stripe\Customer( ['id' => 'cus_123'] );
		$checkout1 = [
			'order' => $order1,
		];
		
		// Requires action
		$order2 = $this->factory->order->create();
		$order2->setGateway( 'stripe' );
		$order2->Gateway->payment_intent = new \Stripe\PaymentIntent( ['id' => 'pi_requires_action'] );
		$order2->Gateway->payment_intent->status = 'requires_payment_method';
		$order2->Gateway->payment_method = new \Stripe\PaymentMethod( ['id' => 'pm_3220'] );
		$order2->Gateway->customer = new \Stripe\Customer( ['id' => 'cus_123'] );
		$checkout2 = [
			'order' => $order2,
		];
		
		//  Succeeded
		$order3 = $this->factory->order->create();
		$order3->setGateway( 'stripe' );
		$order3->Gateway->payment_intent = new \Stripe\PaymentIntent( ['id' => 'pi_succeeded'] );
		$order3->Gateway->payment_intent->status = 'requires_payment_method';
		$order3->Gateway->payment_method = new \Stripe\PaymentMethod( ['id' => 'pm_3220'] );
		$order3->Gateway->customer = new \Stripe\Customer( ['id' => 'cus_123'] );
		$checkout3 = [
			'order' => $order3,
		];
		
		// No initial payment
		$order4 = $this->factory->order->create();
		$order4->InitialPayment = 0;
		$order4->setGateway( 'stripe' );
		$order4->Gateway->payment_intent = new \Stripe\PaymentIntent( ['id' => 'pi_succeeded'] );
		$order4->Gateway->payment_intent->status = 'requires_payment_method';
		$order4->Gateway->payment_method = new \Stripe\PaymentMethod( ['id' => 'pm_3220'] );
		$order4->Gateway->customer = new \Stripe\Customer( ['id' => 'cus_123'] );
		$checkout4 = [
			'order' => $order4,
		];
		
		return [
			"Already confirmed" => [
				$checkout1,
				true
			],	
			"Requires action" => [
				$checkout2,
				false
			],	
			"Succeeded" => [
				$checkout3,
				true
			],	
			"No initial payment" => [
				$checkout4,
				true
			],	
		];
	}
	
	/**
	  * Test the process_charges() method.
	  *
	  * @testdox process_charges()
	  *
	  * @dataProvider data_process_charges
	  */
	function test_process_charges( $checkout_args, $expected ) {
		
		$this->skip_test_if_api_not_initialized;
		
		$checkout = $this->factory->checkout->create( $checkout_args );
		$checkout->order->Gateway->process_charges( $checkout->order );
		$order = $checkout->order;
		$payment_intent = $order->Gateway->payment_intent;
		
		if ( false == $expected ) {
			$this->assertNotEmpty( $order->error );
		} else {
			$this->assertInstanceOf( \Stripe\PaymentIntent::class, $payment_intent );
			$this->assertEquals( 'succeeded', $payment_intent->status );
		}
	}
	
	 /**
	  * Data provider for test_get_setup_intent()
	  */
	function data_get_setup_intent() {
		
		// No current SetupIntent
		$order1_args = [
			'PaymentAmount' => 10,
			'ProfileStartDate' => date( 'Y-m-d', current_time('timestamp') ) . 'T0:0:0',
			'BillingPeriod' => 'month',
			'BillingFrequency' => 1,
		];
		$order1 = $this->factory->order->create( $order1_args );
		$order1->setGateway( 'stripe' );
		$order1->Gateway->payment_method = new \Stripe\PaymentMethod( ['id' => 'pm_123'] );
		$order1->Gateway->customer = new \Stripe\Customer( ['id' => 'cus_no_setup_intent'] );
		$checkout1 = [
			'order' => $order1,
			'globals' => [
				'global' => [
					'pmpro_currency' => 'usd',
					'pmpro_currencies' => [ 'usd' ],
				]
			],
		];
		
		// SetupIntent in session
		$order2 = $this->factory->order->create();
		$order2->setGateway( 'stripe' );
		$order2->Gateway->payment_method = new \Stripe\PaymentMethod( ['id' => 'pm_123'] );
		$order2->Gateway->customer = new \Stripe\Customer( ['id' => 'cus_123'] );
		$setup_intent2 = new \Stripe\SetupIntent( [ 'id' => 'seti_in_session' ] );
		$setup_intent2->object = 'setup_intent';
		$setup_intent2->amount = 1000;
		$checkout2 = [
			'order' => $order2,
			'globals' => [
				'session' => [
					'pmpro_stripe_setup_intent' =>  $setup_intent2,
				]
			],
		];
		
		
		return [
			"No current SetupIntent" => [
				$checkout1,
				true
			],	
			"SetupIntent in session" => [
				$checkout2,
				true
			],	
			// "SetupIntent already set" => [
			// 	$checkout3,
			// 	true
			// ],	
		];
	}
	
	/**
	  * Test the get_setup_intent() method.
	  *
	  * @testdox get_setup_intent()
	  *
	  * @dataProvider data_get_setup_intent
	  */
	function test_get_setup_intent( $checkout_args, $expected ) {
		
		$this->skip_test_if_api_not_initialized;
		
		$checkout = $this->factory->checkout->create( $checkout_args );
		$setup_intent = $checkout->order->Gateway->get_setup_intent( $checkout->order );
		$order = $checkout->order;
		
		if ( false == $expected ) {
			$this->assertNotEmpty( $order->error );
		} else {
			$this->assertEquals( 'setup_intent', $setup_intent->object );
			// $this->assertInstanceOf( \Stripe\SetupIntent::class, $setup_intent );
			$this->assertEquals( 1000, $setup_intent->amount );
		}
	}
	
	
	/**
	 * Data provider for test_create_plan()
	 */
	function data_create_plan() {
		
		// TODO: Test cases:
		// $10 every 1 month after 1 month trial
		// $10 every 3 years
		// $10 every 1 month; 3 cycle limit
		// other currencies
		// ???
		
		// $10 every 1 month
		$order1_args = [
			'PaymentAmount' => 10,
			'ProfileStartDate' => date( 'Y-m-d', current_time('timestamp') ) . 'T0:0:0',
			'BillingPeriod' => 'month',
			'BillingFrequency' => 1,
		];
		$order1 = $this->factory->order->create( $order1_args );
		$order1->setGateway( 'stripe' );
		$order1->Gateway->payment_method = new \Stripe\PaymentMethod( ['id' => 'pm_123'] );
		$order1->Gateway->customer = new \Stripe\Customer( ['id' => 'cus_123'] );
		$checkout1 = [
			'order' => $order1,
			'globals' => [
				'global' => [
					'pmpro_currency' => 'usd',
					'pmpro_currencies' => [ 'usd' ],
				]
			],
		];
		
		return [
			"$10 every 1 month" => [
				$checkout1,
				true
			],	
		];
	}
	
	/**
	  * Test the create_plan() method.
	  *
	  * @testdox create_plan()
	  *
	  * @dataProvider data_create_plan
	  */
	function test_create_plan( $checkout_args, $expected ) {
		
		$this->skip_test_if_api_not_initialized;
		
		$checkout = $this->factory->checkout->create( $checkout_args );
		$plan = $checkout->order->Gateway->create_plan( $checkout->order );
		$order = $checkout->order;
		
		if ( false == $expected ) {
			$this->assertNotEmpty( $order->error );
		} else {
			$this->assertInstanceOf( \Stripe\Plan::class, $plan );
			$this->assertEquals( $order->code, $plan->id );
		}
	}
	
	/**
	 * Data provider for test_create_subscription()
	 */
	function data_create_subscription() {
		
		// TODO: Test cases:
		// ???
		
		// $10 every 1 month; trialing
		$order1 = $this->factory->order->create();
		$order1->code = 'plan_123';
		$order1->TrialPeriodDays = 30;
		$order1->setGateway( 'stripe' );
		$order1->Gateway->payment_method = new \Stripe\PaymentMethod( ['id' => 'pm_123'] );
		$order1->Gateway->customer = new \Stripe\Customer( ['id' => 'cus_trialing'] );
		$checkout1 = [
			'order' => $order1,
		];
		
		return [
			"Valid plan; trialing" => [
				$checkout1,
				'trialing',
			],	
		];
	}
	
	/**
	  * Test the create_subscription() method.
	  *
	  * @testdox create_subscription()
	  *
	  * @dataProvider data_create_subscription
	  */
	function test_create_subscription( $checkout_args, $expected ) {
		
		$this->skip_test_if_api_not_initialized;
		
		$checkout = $this->factory->checkout->create( $checkout_args );
		$subscription = $checkout->order->Gateway->create_subscription( $checkout->order );
		$order = $checkout->order;
		
		if ( false == $expected ) {
			$this->assertNotEmpty( $order->error );
		} else {
			$this->assertInstanceOf( \Stripe\Subscription::class, $subscription );
			$this->assertEquals( $expected, $subscription->status );
		}
	}
	
	/**
	 * Data provider for test_delete_plan()
	 */
	function data_delete_plan() {
		
		// TODO: Test cases:
		// Plan deleted
		// ???
		
		// Plan deleted
		$order1 = $this->factory->order->create();
		$order1->setGateway( 'stripe' );
		$order1->code = 'plan_deleted';
		$order1->plan = new \Stripe\Plan( ['id' => 'plan_deleted'] );
		$checkout1 = [
			'order' => $order1,
		];
		
		return [
			"Plan deleted" => [
				$checkout1,
				true
			],	
		];
	}
	
	/**
	  * Test the delete_plan() method.
	  *
	  * @testdox delete_plan()
	  *
	  * @dataProvider data_delete_plan
	  */
	function test_delete_plan( $checkout_args, $expected ) {
		
		$this->skip_test_if_api_not_initialized;
		
		$checkout = $this->factory->checkout->create( $checkout_args );
		$result = $checkout->order->Gateway->delete_plan( $checkout->order );
		$order = $checkout->order;
		
		if ( false == $expected ) {
			$this->assertNotEmpty( $order->error );
		} else {
			$this->assertTrue( $order->plan->deleted );
			$this->assertEquals( $order->code, $order->plan->id );
		}
	}
	 
	/**
	 * Data provider for test_create_setup_intent()
	 */
	function data_create_setup_intent() {
		
		// TODO: Test:
		// Failed to create plan
		// Failed to create subscription
		
		// Subscription created; requires action
		$order1_args = [
			'PaymentAmount' => 10,
			'ProfileStartDate' => date( 'Y-m-d', current_time('timestamp') ) . 'T0:0:0',
			'BillingPeriod' => 'month',
			'BillingFrequency' => 1,
		];
		$order1 = $this->factory->order->create( $order1_args );
		$order1->setGateway( 'stripe' );
		$order1->Gateway->payment_method = new \Stripe\PaymentMethod();
		$order1->Gateway->customer = new \Stripe\Customer( ['id' => 'cus_requires_action'] );
		$checkout1_args = [
			'order' => $order1,
			'globals' => [
				'global' => [
					'pmpro_currency' => 'usd',
				]
			],
		];
		
		return [
			"Subscription created; requires_action" => [
				$checkout1_args,
				'requires_action',
			],	
		];
	}
	
	/**
	  * Test the create_setup_intent() method.
	  *
	  * @testdox create_setup_intent()
	  *
	  * @dataProvider data_create_setup_intent
	  */
	function test_create_setup_intent( $checkout_args, $expected ) {
		
		$this->skip_test_if_api_not_initialized;
		
		$checkout = $this->factory->checkout->create( $checkout_args );
		$setup_intent = $checkout->order->Gateway->create_setup_intent( $checkout->order );
		$order = $checkout->order;
		
		if ( false === $expected ) {
			$this->assertEmpty( $setup_intent );
		} else {
			$this->assertInstanceOf( \Stripe\SetupIntent::class, $setup_intent );
			$this->assertEquals( $expected, $setup_intent->status );
		}
	}
	
	/**
	 * Data provider for test_set_setup_intent()
	 */
	function data_set_setup_intent() {
		
		// TODO Tests:
		// Failed to get SetupIntent
		
		// Got SetupIntent
		$order1 = $this->factory->order->create();
		$order1->setGateway( 'stripe' );
		$order1->Gateway->customer = new \Stripe\Customer( ['id' => 'cus_123'] );
		$order1->Gateway->payment_method = new \Stripe\PaymentMethod( ['id' => 'pm_4242'] );
		$setup_intent1 = new \Stripe\SetupIntent();
		$setup_intent1->object = 'setup_intent';
		$setup_intent1->amount = 1000;
		$checkout1 = [
			'order' => $order1,
			'globals' => [
				'session' => [
					'pmpro_stripe_setup_intent' => $setup_intent1
				]
			]
		];
		
		// // Failed to get SetupIntent
		// $order2 = $this->factory->order->create();
		// $order2->setGateway( 'stripe' );
		// $checkout2 = [
		// 	'order' => $order2,
		// ];
		
		// SetupIntent already set
		$order3 = $this->factory->order->create();
		$order3->setGateway( 'stripe' );
		$setup_intent = new \Stripe\SetupIntent( [ 'id' => 'seti_already_set' ] );
		$setup_intent->object = 'setup_intent';
		$setup_intent->amount = 1000;
		$order3->Gateway->setup_intent = $setup_intent;
		$checkout3 = [
			'order' => $order3,
		];
		
		return [
			"Got SetupIntent" => [
				$checkout1,
				true
			],	
			// "Failed to get SetupIntent" => [
			// 	$checkout2,
			// 	false
			// ],	
			"SetupIntent already set" => [
				$checkout3,
				true
			],	
		];
	}
	
	/**
	  * Test the set_setup_intent() method.
	  *
	  * @testdox set_setup_intent()
	  *
	  * @dataProvider data_set_setup_intent
	  */
	function test_set_setup_intent( $checkout_args, $expected ) {
		$this->skip_test_if_api_not_initialized;
		
		$checkout = $this->factory->checkout->create( $checkout_args );
		$checkout->order->Gateway->set_setup_intent( $checkout->order );
		$order = $checkout->order;
		$setup_intent = $order->Gateway->setup_intent;
		
		if ( false == $expected ) {
			$this->assertNotEmpty( $order->error );
		} else {
			$this->assertEquals( 'setup_intent', $setup_intent->object );
			// $this->assertInstanceOf( \Stripe\SetupIntent::class, $setup_intent );
			$this->assertEquals( 1000, $setup_intent->amount );
		}
	}
	
	/**
	 * Data provider for test_confirm_setup_intent()
	 */
	function data_confirm_setup_intent() {
		
		// Already confirmed
		$order1 = $this->factory->order->create();
		$order1->setGateway( 'stripe' );
		$order1->Gateway->setup_intent = new \Stripe\SetupIntent( ['id' => 'seti_confirmed'] );
		$order1->Gateway->setup_intent->status = 'succeeded';
		$checkout1 = [
			'order' => $order1,
		];
		
		// Requires action
		$order2 = $this->factory->order->create();
		$order2->setGateway( 'stripe' );
		$order2->Gateway->setup_intent = new \Stripe\SetupIntent( ['id' => 'seti_requires_action'] );
		$order2->Gateway->setup_intent->status = 'requires_action';
		$order2->Gateway->payment_method = new \Stripe\PaymentMethod( ['id' => 'pm_3220'] );
		$checkout2 = [
			'order' => $order2,
		];
		
		return [
			"Already confirmed" => [
				$checkout1,
				'succeeded'
			],	
			"Requires action" => [
				$checkout2,
				false
			],	
		];
	}
	
	/**
	  * Test the confirm_setup_intent() method.
	  *
	  * @testdox confirm_setup_intent()
	  *
	  * @dataProvider data_confirm_setup_intent
	  */
	function test_confirm_setup_intent( $checkout_args, $expected ) {
		
		$this->skip_test_if_api_not_initialized;
		
		$checkout = $this->factory->checkout->create( $checkout_args );
		$checkout->order->Gateway->confirm_setup_intent( $checkout->order );
		$order = $checkout->order;
		$setup_intent = $order->Gateway->setup_intent;
		
		if ( false == $expected ) {
			$this->assertNotEmpty( $order->error );
		} else {
			$this->assertInstanceOf( \Stripe\SetupIntent::class, $setup_intent );
			$this->assertEquals( 'succeeded', $setup_intent->status );
		}
	}
	
	/**
	  * Data provider for test_process_subscriptions()
	  */
	function data_process_subscriptions() {
		
		// Test:
		// No recurring subscription
		// Subscribed; No SetupIntent
		// Subscribed; SetupIntent already confirmed
		// Subscribed; SetupIntent requires action
		
		// No recurring subscription
		$order1 = $this->factory->order->create();
		$order1->setGateway( 'stripe' );
		$checkout1 = [
			'order' => $order1,
		];
		
		// Subscribed; No SetupIntent
		$order2_args = [
			'PaymentAmount' => 10,
			'ProfileStartDate' => date( 'Y-m-d', current_time('timestamp') ) . 'T0:0:0',
			'BillingPeriod' => 'month',
			'BillingFrequency' => 1,
		];
		$order2 = $this->factory->order->create();
		$order2->membership_level = new \stdClass();
		$order2->membership_level->billing_amount = 10;
		$order2->setGateway( 'stripe' );
		$order2->Gateway->payment_method = new \Stripe\PaymentMethod( ['id' => 'pm_4242'] );
		$order2->Gateway->customer = new \Stripe\Customer( ['id' => 'cus_subscribed'] );
		$checkout2 = [
			'order' => $order2,
		];
		
		// Subscribed; SetupIntent already confirmed
		$order3_args = [
			'PaymentAmount' => 10,
			'ProfileStartDate' => date( 'Y-m-d', current_time('timestamp') ) . 'T0:0:0',
			'BillingPeriod' => 'month',
			'BillingFrequency' => 1,
		];
		$order3 = $this->factory->order->create();
		$order3->membership_level = new \stdClass();
		$order3->membership_level->billing_amount = 10;
		$order3->setGateway( 'stripe' );
		$setup_intent = new \Stripe\SetupIntent();
		$setup_intent->status = 'succeeded';
		
		$checkout3 = [
			'order' => $order3,
		];
		
		// Subscribed; SetupIntent requires action
		$order4_args = [
			'PaymentAmount' => 10,
			'ProfileStartDate' => date( 'Y-m-d', current_time('timestamp') ) . 'T0:0:0',
			'BillingPeriod' => 'month',
			'BillingFrequency' => 1,
		];
		$order4 = $this->factory->order->create();
		$order4->membership_level = new \stdClass();
		$order4->membership_level->billing_amount = 10;
		$order4->setGateway( 'stripe' );
		$order4->Gateway->payment_method = new \Stripe\PaymentMethod( ['id' => 'pm_3220'] );
		$order4->Gateway->customer = new \Stripe\Customer( ['id' => 'cus_requires_action'] );
		$checkout4 = [
			'order' => $order4,
			'globals' => [
				'global' => [
					'pmpro_currency' => 'usd',
					'pmpro_currencies' => [ 'usd' ],
				]
			],
		];
		
		return [
			"No recurring subscription" => [
				$checkout1,
				true
			],	
			"Subscribed; No SetupIntent" => [
				$checkout2,
				true
			],	
			"Subscribed; SetupIntent already confirmed" => [
				$checkout3,
				true
			],	
			"Subscribed; SetupIntent requires action" => [
				$checkout4,
				true
			],	
		];
	}
	
	/**
	  * Test the process_subscriptions() method.
	  *
	  * @testdox process_subscriptions()
	  *
	  * @dataProvider data_process_subscriptions
	  */
	function test_process_subscriptions( $checkout_args, $expected ) {
		
		$this->skip_test_if_api_not_initialized;
		
		$checkout = $this->factory->checkout->create( $checkout_args );
		$checkout->order->Gateway->process_subscriptions( $checkout->order );
		$order = $checkout->order;
		
		if ( false == $expected ) {
			$this->assertNotEmpty( $order->error );
		} else if ( ! empty( $this->setup_intent ) ) {
			$setup_intent = $order->Gateway->setup_intent;
			$this->assertInstanceOf( \Stripe\SetupIntent::class, $setup_intent );
			$this->assertEquals( 'succeeded', $setup_intent->status );
		} else {
			$this->assertTrue( ! pmpro_isLevelRecurring( $order->membership_level ) );
		}
	}

}
