<?php
/**
 * Compatibility for Avada Theme.
 */

 // Unhook the_content changes for Avada.
function pmpro_remove_content_changes_avada() {
	remove_filter( 'the_content', 'pmpro_membership_content_filter', 5 );
}
add_action( 'awb_remove_third_party_the_content_changes', 'pmpro_remove_content_changes_avada', 5 );

// Add the_content restriction back for Avada.
function pmpro_readd_content_changes_avada() {
	add_filter( 'the_content', 'pmpro_membership_content_filter', 5 );
}
add_action( 'awb_readd_third_party_the_content_changes', 'pmpro_readd_content_changes_avada', 99 );
