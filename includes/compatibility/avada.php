<?php
/**
 * Compatibility for Avada Theme.
 */

 // Unhook the_content changes for Avada.
function pmpro_remove_content_changes_avada() {
	global $pmpro_avada_content_filter_priorities;

	// Remember the filter's current priority (or false if it isn't hooked) so it can be restored. Compatibility code (e.g. Elementor) may have moved it from 5.
	if ( ! is_array( $pmpro_avada_content_filter_priorities ) ) {
		$pmpro_avada_content_filter_priorities = array();
	}
	$priority = has_filter( 'the_content', 'pmpro_membership_content_filter' );
	if ( false !== $priority ) {
		remove_filter( 'the_content', 'pmpro_membership_content_filter', $priority );
	}
	$pmpro_avada_content_filter_priorities[] = $priority;
}
add_action( 'awb_remove_third_party_the_content_changes', 'pmpro_remove_content_changes_avada', 5 );

// Add the_content restriction back for Avada.
function pmpro_readd_content_changes_avada() {
	global $pmpro_avada_content_filter_priorities;

	// Restore the filter at the priority it had before Avada removed it.
	$priority = is_array( $pmpro_avada_content_filter_priorities ) ? array_pop( $pmpro_avada_content_filter_priorities ) : null;
	if ( is_int( $priority ) ) {
		add_filter( 'the_content', 'pmpro_membership_content_filter', $priority );
	}
}
add_action( 'awb_readd_third_party_the_content_changes', 'pmpro_readd_content_changes_avada', 99 );
