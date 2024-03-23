<?php
/**
 * Render the Content Visibility block on the frontend.
 */
echo wp_kses_post( pmpro_apply_block_visibility( $attributes, $content ) );
