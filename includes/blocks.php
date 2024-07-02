<?php
/**
 * Add new block category for Paid Memberships Pro blocks.
 *
 * @since 1.0
 *
 * @param array $categories Array of block categories.
 * @return array Array of block categories.
 */
function pmpro_block_categories( $categories ) {
	return array_merge(
		$categories,
		array(
			array(
				'slug' => 'pmpro',
				'title' => esc_html__( 'Paid Memberships Pro', 'paid-memberships-pro' ),
			),
			array(
				'slug' => 'pmpro-pages',
				'title' => esc_html__( 'Paid Memberships Pro Pages', 'paid-memberships-pro' ),
			),
		)
	);
}
add_filter( 'block_categories_all', 'pmpro_block_categories' );

/**
 * Register block types for the block editor.
 */
function pmpro_register_block_types() {
	if ( function_exists( 'register_block_type' ) ) {
		register_block_type( PMPRO_DIR . '/blocks/build/account-invoices-section' );
		register_block_type( PMPRO_DIR . '/blocks/build/account-profile-section' );	
		register_block_type( PMPRO_DIR . '/blocks/build/account-links-section' );
		register_block_type( PMPRO_DIR . '/blocks/build/account-membership-section' );
		register_block_type( PMPRO_DIR . '/blocks/build/account-page' );
		register_block_type( PMPRO_DIR . '/blocks/build/billing-page' );
		register_block_type( PMPRO_DIR . '/blocks/build/cancel-page' );
		register_block_type( PMPRO_DIR . '/blocks/build/checkout-button' );
		register_block_type( PMPRO_DIR . '/blocks/build/checkout-page' );
		register_block_type( PMPRO_DIR . '/blocks/build/confirmation-page' );
		register_block_type( PMPRO_DIR . '/blocks/build/invoice-page' );
		register_block_type( PMPRO_DIR . '/blocks/build/levels-page' );
		register_block_type( PMPRO_DIR . '/blocks/build/login' );
		register_block_type( PMPRO_DIR . '/blocks/build/member-profile-edit' );
		register_block_type( PMPRO_DIR . '/blocks/build/membership' );
		register_block_type( PMPRO_DIR . '/blocks/build/single-level' );
		register_block_type( PMPRO_DIR . '/blocks/build/single-level-name' );
		register_block_type( PMPRO_DIR . '/blocks/build/single-level-expiration' );
		register_block_type( PMPRO_DIR . '/blocks/build/single-level-description' );
		register_block_type( PMPRO_DIR . '/blocks/build/single-level-price' );
	}
}
add_action( 'init', 'pmpro_register_block_types' );
/**
 * Enqueue block editor only CSS.
 */
function pmpro_block_editor_assets() {
	// Enqueue the CSS file css/blocks.editor.css.
	wp_enqueue_style(
		'pmpro-block-editor-css',
		PMPRO_URL . '/css/blocks.editor.css',
		array( 'wp-edit-blocks' )
	);

	// If we're editing a post that can be restricted, enqueue the sidebar block editor script.
	if ( in_array( get_post_type(), apply_filters( 'pmpro_restrictable_post_types', array( 'page', 'post' ) ) ) ) {
		wp_register_script(
			'pmpro-sidebar-editor-script',
			PMPRO_URL . '/blocks/build/sidebar/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-i18n', 'wp-editor', 'wp-api-request', 'wp-plugins', 'wp-edit-post' )
		);
		wp_localize_script(
			'pmpro-sidebar-editor-script',
			'pmpro_block_editor_sidebar',
			array(
				'post_id' => get_the_ID(),
			)
		);
		wp_enqueue_script( 'pmpro-sidebar-editor-script' );
	}

	wp_register_script(
		'pmpro-component-content-visibility-script',
		PMPRO_URL . '/blocks/build/component-content-visibility/index.js',
		array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-i18n', 'wp-editor', 'wp-api-request', 'wp-plugins', 'wp-edit-post' )
	);

	wp_localize_script(
		'pmpro-component-content-visibility-script',
		'pmpro_content_visibility_component',
		array(
			'post_id' => get_the_ID(),
		)
	);
	wp_enqueue_script( 'pmpro-component-content-visibility-script' );

}
add_action( 'enqueue_block_editor_assets', 'pmpro_block_editor_assets' );

/**
 * Register post meta needed for our blocks.
 *
 * @since 3.0
 */
function pmpro_register_post_meta() {
	// Register pmpro_default_level for the checkout block.
	register_post_meta(
		'',
		'pmpro_default_level',
		array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
		)
	);
}
add_action( 'init', 'pmpro_register_post_meta' );

/**
* Render the block content on the frontend based on content visibility attributes.
*
* @param array $attributes The block attributes.
* @param array  $content The block content.
* @return string the filtered output
* @since 3.0
*/
function pmpro_apply_block_visibility( $attributes, $content ) {
	$output = '';

	if ( 'all' === $attributes['segment'] && ! empty( $attributes['levels'] ) ) {
		// Legacy setup for PMPro < 3.0.
		if ( ! array_key_exists( 'levels', $attributes ) || empty( $attributes['levels'] ) ) {
			// Assume require any membership level, and do not show to non-members.
			if ( pmpro_hasMembershipLevel() ) {
				$output = do_blocks( $content );
			}
		} else {
			if ( pmpro_hasMembershipLevel( $attributes['levels'] ) ) {
				$output = do_blocks( $content );
			} elseif ( ! empty( $attributes['show_noaccess'] ) ) {
				$output = pmpro_get_no_access_message( NULL, $attributes['levels'] );
			}
		}
	} else {
		// Setup for PMPro >= 3.0.
		switch ( $attributes['segment'] ) {
			case 'all':
				$pmpro_hasMembershipLevel_params = null; // All levels.
				$all_levels = pmpro_getAllLevels( true );
				$pmpro_get_no_access_message_param_level_ids = wp_list_pluck( $all_levels, 'id' );
				$pmpro_get_no_access_message_param_level_names = wp_list_pluck( $all_levels, 'name' ); // Getting names now to avoid multiple queries in the function.
				break;
			case 'specific':
				$pmpro_hasMembershipLevel_params = $attributes['levels']; // Specific levels.
				$pmpro_get_no_access_message_param_level_ids = $attributes['levels'];
				$pmpro_get_no_access_message_param_level_names = null; // This will be done in the function.

				break;
			case 'logged_in':
				$pmpro_hasMembershipLevel_params = 'L'; // Logged in users.
				$pmpro_get_no_access_message_param_level_ids = array();
				$pmpro_get_no_access_message_param_level_names = null;
				break;
		}

		$should_show = empty( $attributes['invert_restrictions'] ) ? pmpro_hasMembershipLevel( $pmpro_hasMembershipLevel_params ) : ! pmpro_hasMembershipLevel( $pmpro_hasMembershipLevel_params );
		if ( $should_show ) {
			$output = do_blocks( $content );
		} elseif ( ! empty( $attributes['show_noaccess'] ) && empty( $attributes['invert_restrictions'] ) ) {
			$output = pmpro_get_no_access_message( NULL, $pmpro_get_no_access_message_param_level_ids, $pmpro_get_no_access_message_param_level_names );
		}
	}
	return $output;
}

/**
 * Hook into render_block to filter core blocks and apply content visibility rules.
 *
 * @param string $block_content The block content.
 * @param array  $block	The block.
 * @return string The filtered block content.
 * @since 3.0
 */
function pmpro_filter_core_blocks( $block_content, $block ) {
	// TODO Replace with https://www.php.net/manual/en/function.str-starts-with when we drop support for PHP 7.x.

	// Return block if there are no attributes or content visibility is not enabled.
	if ( empty( $block['attrs'] ) || empty( $block['attrs']['visibilityBlockEnabled'] ) ) {
		return $block_content;
	}

	// Return block if this is not a core block.
	if ( strpos( $block['blockName'], 'core/' ) != 0 ) {
		return $block_content;
	}

	// We need defaults because WP doesn't store defaults in the DB.
	$attributes = wp_parse_args( $block['attrs'], array(
		'segment' => 'all',
		'levels' => array(),
		'show_noaccess' => '0',
		'invert_restrictions' => '0',
	) );
	return pmpro_apply_block_visibility( $attributes, $block_content );
}
add_filter( 'render_block', 'pmpro_filter_core_blocks', 10, 2 );

/**
 * Add visibility content attributes server side as well.
 *
 * @param array $metadata The block metadata.
 * @return array The filtered block metadata.
 * @since TBD
 *
 */
function pmpro_block_type_metadata( $metadata ) {
	//bail if it's not a core block
	if ( empty( $metadata['name'] ) || ! str_starts_with( $metadata['name'], 'core/' ) ) {
		return $metadata;
	}

	$metadata['attributes']['visibilityBlockEnabled'] = array(
		'type' => 'boolean',
		'default' => false,
	);
	$metadata['attributes']['invert_restrictions'] = array(
		'type' => 'boolean',
		'default' => false,
	);
	$metadata['attributes']['segment'] = array(
		'type' => 'string',
		'default' => 'all',
	);
	$metadata['attributes']['levels'] = array(
		'type' => 'array',
		'default' => array(),
	);
	$metadata['attributes']['show_noaccess'] = array(
		'type' => 'boolean',
		'default' => false,
	);
	return $metadata;
}

add_filter( 'block_type_metadata', 'pmpro_block_type_metadata', 10, 1 );