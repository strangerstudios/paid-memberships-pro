<?php
/*
	Copyright 2011	Stranger Studios	(email : jason@strangerstudios.com)
	GPLv2 Full license details in license.txt
*/

function pmpro_start_session() {
	
	//if the session has been started yet, start it (ignore if running from command line)
	if ( ! defined( 'PMPRO_USE_SESSIONS' ) || PMPRO_USE_SESSIONS == true ) {
		if ( defined( 'STDIN' ) ) {
			//command line
		} else {
			if ( version_compare( phpversion(), '5.4.0', '>=' ) ) {
				if ( session_status() == PHP_SESSION_NONE ) {
					session_start();
				}
			} else {
				if ( ! session_id() ) {
					session_start();
				}
			}
		}
	}
}

add_action( 'pmpro_checkout_preheader', 'pmpro_start_session', -1 );

/**
 * Close the session object for new updates
 * @return MemberOrder
 */
function pmpro_close_session() {
	
	if ( ! defined( 'PMPRO_USE_SESSIONS' ) || PMPRO_USE_SESSIONS == true ) {
		if ( defined( 'STDIN' ) ) {
			//command line
		} else {
			if ( version_compare( phpversion(), '5.4.0', '>=' ) ) {
				if ( session_status() == PHP_SESSION_ACTIVE ) {
					session_write_close();
				}
			} else {
				if ( session_id() ) {
					session_write_close();
				}
			}
		}
	}
}

add_action( 'pmpro_after_checkout', 'pmpro_close_session', 32768 );