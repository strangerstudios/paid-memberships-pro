<?php

namespace PMPro\Tests\Helpers\Factory;

/**
 * Order Factory
 */
class Order_Factory extends \WP_UnitTest_Factory_For_Thing {

    public function __construct( $factory = null ) {
        parent::__construct( $factory );
        $this->default_generation_definitions = [
            'id' => new \WP_UnitTest_Generator_Sequence(' %s '),
            'code' => new \WP_UnitTest_Generator_Sequence(' ORDER%s '),
            'user_id' => '',
            'membership_id' => '',
            'membership_name' => 'Level Name',
            'discount_code' => '',
            'InitialPayment' => 100.00,
            'PaymentAmount' => 0,
            'ProfileStartDate' => '',
            // 'ProfileStartDate' => date( 'Y-m-d', current_time('timestamp') ) . 'T0:0:0',
            'BillingPeriod' => '',
            'BillingFrequency' => 0,
            'TotalBillingCycles' => 0,
            'TrialBillingPeriod' => '',
            'TrialBillingFrequency' => 0,
            'TrialBillingCycles' => 0,
            'TrialAmount' => 0,
            'cardtype' => '',
            'accountnumber' => '',
            'expirationmonth' => '',
            'expirationyear' => '',
            'ExpirationDate' => '',
            'ExpirationDate_YdashM' => '',
            'CVV2' => '',
            'Email' => 'customer@example.com',
            'FirstName' => 'Jessica',
            'LastName' => 'Jones',
            'Address1' => '123 Fake St.',
            'Address2' => 'Apt 69',
            'billing' => '',
            'membership_level' => '',
            'subtotal' => '',
            'tax' => '',
            'gateway' => '',
            'Gateway' => '',
            'stripeToken' => '',
        ];
    }

    /**
     * Create MemberOrder object and return it.
     */
    public function create_object( $args ) {

        // Create the MemberOrder object.
        $order = new \MemberOrder();
        foreach( $this->default_generation_definitions as $key => $value ) {
            if ( isset( $args[$key] ) ) {
                $order->$key = $args[$key];
            } else {
                $order->$key = $value;
            }
        }

        return $order;
    }

    /**
     *  We aren't saving these orders in the db for now.
     */
    public function update_object( $id, $fields ) {
        return false;
    }

    /**
     *  We aren't saving these orders in the db for now.
     */
    public function get_object_by_id( $id ) {
        return false;
    }
}
