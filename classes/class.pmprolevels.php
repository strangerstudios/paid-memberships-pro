<?php

class PMPro_Membership_Level{

    function __construct( $id = NULL ) {
        if ( $id ) {
            return $this->get_membership_level( $id );
        }
    }

} // end of class