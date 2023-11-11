<?php
/**
 * Render the Level Checkout Button on the frontend.
 */
// Return if selected level is empty.
if ( empty( $attributes['selected_membership_level'] ) ) {
	return;
}

echo wp_kses_post( $content );
