<?php
/**
 * Render the Content Visibility block on the frontend.
 */
// The content here is the block's inner content, which should already be escaped.
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
echo pmpro_apply_block_visibility( $attributes, $content );
