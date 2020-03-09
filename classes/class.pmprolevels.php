<?php

class PMPro_Membership_Level{

    function __construct( $id = NULL ) {
        if ( $id ) {
            return $this->get_membership_level( $id );
        } else {
            return $this->get_empty_membership_level();
        }
    }

    function get_empty_membership_level() {
        
        $this->ID = ''; // for backwards compatibility.
        $this->id = '';
        $this->name ='';
        $this->description = '';
        $this->confirmation = '';
        $this->initial_payment = '';
        $this->billing_amount = '';
        $this->cycle_number = '';
        $this->cycle_period = '';
        $this->billing_limit = '';
        $this->trial_amount = '';
        $this->trial_limit = '';
        $this->allow_signups = '';
        $this->expiration_number = '';
        $this->expiration_period = '';

        return $this;

    }

    /**
     * Function to get the membership level object by ID.
     * @since 2.3
     */
    function get_membership_level( $id ) {

        $dblobj = $this->get_membership_level_object( $id );

        if ( ! empty( $dblobj ) ) {
            $this->ID = $dblobj->id;
            $this->id = $dblobj->id;
            $this->name = $dblobj->name;
            $this->description = $dblobj->description;
            $this->confirmation = $dblobj->confirmation;
            $this->initial_payment = $dblobj->initial_payment;
            $this->billing_amount = $dblobj->billing_amount;
            $this->cycle_number = $dblobj->cycle_number;
            $this->cycle_period = $dblobj->cycle_period;
            $this->billing_limit = $dblobj->billing_limit;
            $this->trial_amount = $dblobj->trial_amount;
            $this->trial_limit = $dblobj->trial_limit;
            $this->allow_signups = $dblobj->allow_signups;
            $this->expiration_number = $dblobj->expiration_number;
            $this->expiration_period = $dblobj->expiration_period;
        } else {
            return false;
        }

        return $this;
    }

    function get_membership_level_object( $id ) {
        global $wpdb;

        // Get the discount code object.
        $dcobj = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * 
                FROM $wpdb->pmpro_membership_levels
                WHERE id = %s",
                $id
            ),
            OBJECT   
        );

        return $dcobj;
    }

    function save() {
        global $wpdb;

        $wpdb->replace(
			$wpdb->pmpro_membership_levels,
			array(
				'id'=>max($this->id, 0),
				'name' => $this->name,
				'description' => $this->description,
				'confirmation' => $this->confirmation,
				'initial_payment' => $this->initial_payment,
				'billing_amount' => $this->billing_amount,
				'cycle_number' => $this->cycle_number,
				'cycle_period' => $this->cycle_period,
				'billing_limit' => $this->billing_limit,
				'trial_amount' => $this->trial_amount,
				'trial_limit' => $this->trial_limit,
				'expiration_number' => $this->expiration_number,
				'expiration_period' => $this->expiration_period,
				'allow_signups' => $this->allow_signups
			),
			array(
				'%d',		//id
				'%s',		//name
				'%s',		//description
				'%s',		//confirmation
				'%f',		//initial_payment
				'%f',		//billing_amount
				'%d',		//cycle_number
				'%s',		//cycle_period
				'%d',		//billing_limit
				'%f',		//trial_amount
				'%d',		//trial_limit
				'%d',		//expiration_number
				'%s',		//expiration_period
				'%d',		//allow_signups
			)
		);
    }

} // end of class