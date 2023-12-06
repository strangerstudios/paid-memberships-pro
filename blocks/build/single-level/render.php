<?php
/**
 * Render the Single Level block on the frontend.
 */
// Don't return if selected level is empty.
if ( empty( $attributes['selected_membership_level'] ) ) {
	return;
}
echo wp_kses_post( $content );
