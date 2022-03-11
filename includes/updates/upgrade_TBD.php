<?php
/**
 * Upgrade to TBD
 * 
 */
function pmpro_upgrade_TBD() {
	pmpro_maybe_schedule_event( current_time( 'timestamp' ), 'monthly', 'pmpro_license_check_key' );
}
