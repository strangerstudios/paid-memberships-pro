<?php

class PMPro_Discount_Code{

    function __construct( $code = NULL ) {

        if ( $code ) {
            /// Get the discount code object here ...
        } else {
            return $this->get_discount_code_object_template();
        }
    }

    /**
     * Get an empty (but complete) discount code object.
     * @since 2.3
     */
    function get_discount_code_object_template( $code = NULL ) {

        $discount_code = new stdClass();
        $discount_code->id = '';
        $discount_code->code = empty( $code ) ? pmpro_getDiscountCode() : sanitize_text_field( $code );
        $discount_code->starts = '';
        $discount_code->expires = '';
        $discount_code->uses = '';
        $discount_code->levels = array(
            1 => array(
                'initial_payment' => '',
                'billing_amount' => '',
				'cycle_number' => '',
				'cycle_period' => 'Month',
				'billing_limit' => '',
				'custom_trial' => 0,
				'trial_amount' => '',
                'trial_limit' => '',
                'expiration_number' => '',
                'expiration_period' => ''
            )
        );

        return $discount_code;
    }

    /**
     * Get discount code object.
     * @since 2.3
     * @return $dcobj object The discount code object.
     */
    function get_discount_code_object( $code ) {
        global $wpdb;

        // Get the discount code object.
        $dcobj = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * 
                FROM $wpdb->pmpro_discount_codes 
                WHERE code = %s",
                $code
            ),
            OBJECT   
        );

        return $dcobj;
    }

    /**
     *  Get levels and level billing settings by discount code.
     * @since 2.3
     * @return $levels obj levels that are tied to a discount code.
     */
    function get_level_options_by_code( $code ) {
        global $wpdb;

        $levels = $wpdb->get_results( 
            $wpdb->prepare(
                "SELECT cl.* 
                FROM $wpdb->pmpro_discount_codes_levels cl 
                LEFT JOIN $wpdb->pmpro_discount_codes cd 
                ON cl.code_id = cd.id 
                WHERE cd.code = %s",
                $code
                ),
                OBJECT
                );

        return $levels;
    }
    /**
     * Get discount code by code
     * @since 2.3
     */
    function get_discount_code_by_code( $code ) {

        // Get discount code object and levels linked to the code object..
        $dcobj = $this->get_discount_code_object( $code );
        $levels = $this->get_level_options_by_code( $code );


        // Get the object template and map data to the object.
        $discount_code = $this->get_discount_code_object_template( $code );

        // Setup the discount code object.
        $discount_code->id = $dcobj->id;
        $discount_code->starts = $dcobj->starts;
        $discount_code->expires = $dcobj->expires;
        $discount_code->uses = $dcobj->uses;
        
        unset( $discount_code->levels[1]); //remove the default levels from template.

        foreach( $levels as $level ) {
            $discount_code->levels[$level->level_id] = array(
                'initial_payment' => $level->initial_payment,
                'billing_amount' => $level->billing_amount,
				'cycle_number' => $level->cycle_number,
				'cycle_period' => $level->cycle_period,
				'billing_limit' => $level->billing_limit,
				'custom_trial' => !isset( $level->custom_trial ) ? 0 : $level->custom_trial,
				'trial_amount' => $level->trial_amount,
                'trial_limit' => $level->trial_limit,
                'expiration_number' => $level->expiration_number,
                'expiration_period' => $level->expiration_period
            );
        }

        return $discount_code;
    }

    

} // end of class.