<?php 
/** 
 * Beaver Builder Compatibility
 */
function pmpro_beaver_builder_compatibility() {
	// Filter members-only content later so that the builder's filters run before PMPro.
	remove_filter('the_content', 'pmpro_membership_content_filter', 5);
	add_filter('the_content', 'pmpro_membership_content_filter', 15);
}
add_action( 'init', 'pmpro_beaver_builder_compatibility' );
