<?php
/**
 * Oxygen Builder Compatibility.
 *
 * @since 2.11
 */
if( function_exists( 'oxygen_vsb_register_condition' ) ) {

    $pmpro_levels = pmpro_getAllLevels( true );

    $oxygen_pmpro_levels = array( '0' => '[0] '.__( 'Non-Members', 'paid-memberships-pro' ) );

    if ( ! empty( $pmpro_levels ) ) {
        foreach( $pmpro_levels as $pmpro_level ) {            
            $oxygen_pmpro_levels[$pmpro_level->id] = '['.$pmpro_level->id.'] '.$pmpro_level->name;
        }
    }

    oxygen_vsb_register_condition( 
        __( 'Paid Memberships Pro Level', 'paid-memberships-pro' ), 
        array( 'options' => $oxygen_pmpro_levels, 'custom' => true ), 
        array( '', '==', '!=' ),
        'pmpro_oxygen_builder_condition_callback', 
        'Other'
    );

}

function pmpro_oxygen_builder_condition_callback( $value, $operator ) {

    preg_match_all("/([^[]+(?=]))/", $value, $matches); 

    if( ! isset( $matches[1] ) ) {
        return true;
    }

    if( ! isset( $matches[1][0] ) ) {
        return true;        
    }
    
    $level_id = (int) $matches[1][0];
    
    if( $operator === '==' ) {
        //If they have the required level, show the element
        if( pmpro_hasMembershipLevel( $level_id ) ) {
            return true;
        } else {
            return false;
        }
    } else {
        //If they don't have the required level, show the element
        if( ! pmpro_hasMembershipLevel( $level_id ) ) {
            return true;
        } else {
            return false;
        }
    }

    return true;

}