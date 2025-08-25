<?php

/**
 * Pantheon Compatibility
 *
 * In addition to this code to fix the PMPro password reset form, note that
 * Pantheon also has aggressive caching that your login page
 * should be excluded from, especially if you experience issues
 * with your login page.
 * 
 * https://pantheon.io/docs/wordpress-caching/#cache-exclusions
 *
 * @since 3.5.5
 */

/**
 * Increase the batch size for Action Scheduler on Pantheon.
 */
function pmpro_pantheon_increase_batch_size( $batch_size ) {
	return $batch_size * 2;
}
add_filter( 'pmpro_action_scheduler_batch_size', 'pmpro_pantheon_increase_batch_size' );