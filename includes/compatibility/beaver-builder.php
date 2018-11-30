<?php 

/* 
 * Load "the_content" filter later when Beaver Builder is installed and activated.
 */



 function beaver_builder_compatibility_for_pmpro() {
  	if ( defined('PMPRO_VERSION') ) {
 		// Filter members-only content later so that the builder's filters run before PMPro.
 		remove_filter('the_content', 'pmpro_membership_content_filter', 5);
 		add_filter('the_content', 'pmpro_membership_content_filter', 15);
 	}
 }
 add_action( 'init', 'beaver_builder_compatibility_for_pmpro' );
