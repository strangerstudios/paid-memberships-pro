<?php

namespace PMPro\Tests\Helpers\Factory;

// Use the Order Factory
use Order_Factory;

/**
 * Checkout Factory.
 *
 * Simulates checkout environments for processing orders.
 */
class Checkout_Factory extends \WP_UnitTest_Factory_For_Thing {

    public function __construct( $factory = null ) {
        parent::__construct( $factory );
        $this->default_generation_definitions = [
            'is_logged_in' =>'',
            'order' => '',
            'globals' => '',
            'scenario' => '',
        ];
    }

    /**
     * Create "Checkout" object and return it.
     */
    public function create_object( $args ) {

        // Create the "Checkout" object.
        $checkout = new \stdClass();
        foreach( $this->default_generation_definitions as $key => $value ) {
            if ( isset( $args[$key] ) ) {
                $checkout->$key = $args[$key];
            } else {
                $checkout->$key = $value;
            }
        }

        // TODO: Use callback classes instead?

        // Set global variables.
        $this->set_globals( $checkout );

        // Log in user.
        // TODO: Create new user if true was passed instead of an ID.
        if ( ! empty( $checkout->is_logged_in ) ) {
            wp_set_current_user( $checkout->is_logged_in );

            // if ( is_int( $checkout->is_logged_in ) ) {
            // 	$user_id = $checkout_is_logged_in;
            // }
        }

        return $checkout;
    }

    /**
     * Sets global variables based on $checkout->globals.
     */
    public function set_globals( $checkout ) {

        if ( empty( $checkout->globals ) || ! is_array( $checkout->globals ) ) {
            return false;
        }

        foreach( $checkout->globals as $type => $array ) {
            $type = strtoupper( $type );

            // Unset globals first.
            $GLOBALS["_{$type}"] = [];
            foreach( $array as $key => $value ) {
                $GLOBALS["_{$type}"][$key] = $value;
            }
        }
    }

    /**
     * Checkouts aren't saved in the db but this method is required.
     */
    public function update_object( $id, $fields ) {
        return false;
    }

    /**
     * Checkouts aren't saved in the db but this method is required.
     */
    public function get_object_by_id( $id ) {
        return false;
    }
}