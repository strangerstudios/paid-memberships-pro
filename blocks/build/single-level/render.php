<?php
/**
 * Render the Single Level block on the frontend.
 */
// Don't return if selected level is empty.
if ( empty( $attributes['selected_membership_level'] ) ) {
	return;
}
// The content here is the block's inner content, which should already be escaped.
echo $content; // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
