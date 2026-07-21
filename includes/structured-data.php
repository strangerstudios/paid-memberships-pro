<?php
/**
 * Structured data (JSON-LD) for membership levels.
 *
 * Outputs Product + Offer schema on levels listing and checkout pages, and on
 * pages that render the Single Membership Level block. Targets Google product
 * snippets and machine-readable pricing for agents — not full merchant-listing
 * eligibility (no shipping/returns/GTIN).
 *
 * @since TBD
 */

/**
 * Whether structured data output is enabled.
 *
 * @since TBD
 *
 * @return bool
 */
function pmpro_structured_data_is_enabled() {
	$hide = get_option( 'pmpro_hide_structured_data' );
	$enabled = ( empty( $hide ) || 'yes' !== $hide );

	/**
	 * Filter whether PMPro should emit membership-level structured data.
	 *
	 * @since TBD
	 *
	 * @param bool $enabled Whether output is enabled.
	 */
	return (bool) apply_filters( 'pmpro_structured_data_enabled', $enabled );
}

/**
 * Print JSON-LD structured data in wp_head when on a relevant page.
 *
 * @since TBD
 */
function pmpro_structured_data_output() {
	if ( is_admin() || ! pmpro_structured_data_is_enabled() ) {
		return;
	}

	$context = pmpro_structured_data_get_context();
	if ( empty( $context ) || empty( $context['type'] ) ) {
		return;
	}

	/**
	 * Filter the resolved structured-data context before schema is built.
	 * Return empty/false to suppress output. Multi-level checkout integrations
	 * can replace the context with authoritative level data here.
	 *
	 * @since TBD
	 *
	 * @param array|false $context {
	 *     @type string $type    Context type: levels_list|checkout|single_levels.
	 *     @type array  $levels  Level objects keyed for output.
	 *     @type string $source  Detection source identifier.
	 * }
	 */
	$context = apply_filters( 'pmpro_structured_data_context', $context );
	if ( empty( $context ) || empty( $context['type'] ) || empty( $context['levels'] ) ) {
		return;
	}

	$levels = pmpro_structured_data_normalize_levels( $context['levels'] );
	if ( empty( $levels ) ) {
		return;
	}

	$include_description = ! empty( $context['include_description'] );
	$products            = array();

	foreach ( $levels as $level ) {
		$product = pmpro_structured_data_build_product_schema( $level, $context['type'], $include_description );
		if ( ! empty( $product ) ) {
			$products[] = $product;
		}
	}

	if ( empty( $products ) ) {
		return;
	}

	if ( 'levels_list' === $context['type'] || count( $products ) > 1 ) {
		$schema = pmpro_structured_data_build_item_list_schema( $products );
		/**
		 * Filter the ItemList schema for multi-level pages.
		 *
		 * @since TBD
		 *
		 * @param array $schema  ItemList schema.
		 * @param array $context Resolved context.
		 * @param array $levels  Level objects.
		 */
		$schema = apply_filters( 'pmpro_structured_data_item_list_schema', $schema, $context, $levels );
		if ( ! empty( $schema ) ) {
			pmpro_structured_data_print( $schema );
		}
		return;
	}

	pmpro_structured_data_print( $products[0] );
}
add_action( 'wp_head', 'pmpro_structured_data_output', 20 );

/**
 * Resolve the current page context for structured data.
 *
 * @since TBD
 *
 * @return array|false
 */
function pmpro_structured_data_get_context() {
	global $pmpro_pages, $pmpro_level, $pmpro_checkout_level_ids, $post;

	// Checkout: single authoritative level only (skip multi-level / MMPU bundles).
	if ( pmpro_is_checkout() ) {
		$multi_ids = array();
		if ( ! empty( $pmpro_checkout_level_ids ) && is_array( $pmpro_checkout_level_ids ) ) {
			$multi_ids = array_values( array_unique( array_map( 'intval', $pmpro_checkout_level_ids ) ) );
		}

		if ( count( $multi_ids ) > 1 ) {
			/**
			 * Allow multi-level checkout to supply structured-data context.
			 * Default is to skip automatic output when more than one level is
			 * being purchased together.
			 *
			 * @since TBD
			 *
			 * @param array|false $context Default false (skip).
			 * @param array       $level_ids Level IDs at checkout.
			 */
			return apply_filters( 'pmpro_structured_data_multi_level_checkout_context', false, $multi_ids );
		}

		if ( empty( $pmpro_level ) || empty( $pmpro_level->id ) ) {
			return false;
		}

		// Skip levels that do not allow signups unless already adjusted at checkout.
		if ( isset( $pmpro_level->allow_signups ) && empty( $pmpro_level->allow_signups ) ) {
			return false;
		}

		return array(
			'type'                 => 'checkout',
			'levels'               => array( $pmpro_level ),
			'source'               => 'checkout',
			'include_description'  => true,
		);
	}

	// Assigned Levels page.
	if ( ! empty( $pmpro_pages['levels'] ) && is_page( (int) $pmpro_pages['levels'] ) ) {
		return array(
			'type'                => 'levels_list',
			'levels'              => pmpro_structured_data_get_levels_list_levels(),
			'source'              => 'levels_page',
			'include_description' => false,
		);
	}

	if ( empty( $post ) || empty( $post->post_content ) ) {
		return false;
	}

	// Page containing levels shortcode or block (not necessarily the assigned page).
	if (
		has_shortcode( $post->post_content, 'pmpro_levels' )
		|| has_block( 'pmpro/levels-page', $post )
	) {
		return array(
			'type'                => 'levels_list',
			'levels'              => pmpro_structured_data_get_levels_list_levels(),
			'source'              => 'levels_shortcode_or_block',
			'include_description' => false,
		);
	}

	// Single Membership Level block(s) — complete product UI, not field shortcodes.
	$block_level_ids = pmpro_structured_data_get_single_level_block_ids( $post->post_content );
	if ( ! empty( $block_level_ids ) ) {
		$levels = array();
		foreach ( $block_level_ids as $level_id ) {
			$level = pmpro_getLevel( $level_id );
			if ( ! empty( $level ) && ! empty( $level->id ) && ! empty( $level->allow_signups ) ) {
				$levels[] = $level;
			}
		}
		if ( empty( $levels ) ) {
			return false;
		}
		return array(
			'type'                => 'single_levels',
			'levels'              => $levels,
			'source'              => 'single_level_block',
			'include_description' => true,
		);
	}

	return false;
}

/**
 * Levels array matching the default levels page template.
 *
 * @since TBD
 *
 * @return array
 */
function pmpro_structured_data_get_levels_list_levels() {
	$levels = pmpro_sort_levels_by_order( pmpro_getAllLevels( false, true ) );
	/**
	 * Same filter the levels template uses so custom hiding stays in sync.
	 *
	 * @param array $levels Level objects.
	 */
	$levels = apply_filters( 'pmpro_levels_array', $levels );
	return pmpro_structured_data_normalize_levels( $levels );
}

/**
 * Normalize a mixed list of level objects into unique signup-eligible levels.
 *
 * @since TBD
 *
 * @param mixed $levels Levels array or single level.
 * @return array
 */
function pmpro_structured_data_normalize_levels( $levels ) {
	if ( empty( $levels ) ) {
		return array();
	}

	if ( is_object( $levels ) ) {
		$levels = array( $levels );
	}

	if ( ! is_array( $levels ) ) {
		return array();
	}

	$normalized = array();
	foreach ( $levels as $level ) {
		if ( is_numeric( $level ) ) {
			$level = pmpro_getLevel( (int) $level );
		}
		if ( empty( $level ) || ! is_object( $level ) || empty( $level->id ) ) {
			continue;
		}
		// Listings: only levels that allow signup (pmpro_getAllLevels false,true already filters; double-check).
		if ( isset( $level->allow_signups ) && empty( $level->allow_signups ) ) {
			continue;
		}
		$normalized[ (int) $level->id ] = $level;
	}

	return array_values( $normalized );
}

/**
 * Collect distinct level IDs from pmpro/single-level blocks in post content.
 *
 * Walks nested blocks. Does not resolve reusable/synced pattern refs (v1).
 *
 * @since TBD
 *
 * @param string $content Post content.
 * @return int[]
 */
function pmpro_structured_data_get_single_level_block_ids( $content ) {
	if ( empty( $content ) || ! function_exists( 'parse_blocks' ) ) {
		return array();
	}

	$blocks = parse_blocks( $content );
	$ids    = pmpro_structured_data_collect_single_level_ids_from_blocks( $blocks );
	$ids    = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
	return $ids;
}

/**
 * Recursive helper for single-level block IDs.
 *
 * @since TBD
 *
 * @param array $blocks Parsed blocks.
 * @return int[]
 */
function pmpro_structured_data_collect_single_level_ids_from_blocks( $blocks ) {
	$ids = array();
	if ( empty( $blocks ) || ! is_array( $blocks ) ) {
		return $ids;
	}

	foreach ( $blocks as $block ) {
		if ( empty( $block['blockName'] ) ) {
			if ( ! empty( $block['innerBlocks'] ) ) {
				$ids = array_merge( $ids, pmpro_structured_data_collect_single_level_ids_from_blocks( $block['innerBlocks'] ) );
			}
			continue;
		}

		if ( 'pmpro/single-level' === $block['blockName'] ) {
			$level_id = 0;
			if ( ! empty( $block['attrs']['selected_membership_level'] ) ) {
				$level_id = (int) $block['attrs']['selected_membership_level'];
			} elseif ( ! empty( $block['attrs']['level'] ) ) {
				// Legacy attr name if present.
				$level_id = (int) $block['attrs']['level'];
			}
			if ( $level_id > 0 ) {
				$ids[] = $level_id;
			}
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			$ids = array_merge( $ids, pmpro_structured_data_collect_single_level_ids_from_blocks( $block['innerBlocks'] ) );
		}
	}

	return $ids;
}

/**
 * Build Product schema for one membership level.
 *
 * @since TBD
 *
 * @param object $level                Level object.
 * @param string $context_type         Context type string.
 * @param bool   $include_description  Whether description is visible on this page.
 * @return array
 */
function pmpro_structured_data_build_product_schema( $level, $context_type = '', $include_description = false ) {
	if ( empty( $level ) || empty( $level->id ) ) {
		return array();
	}

	$offer = pmpro_structured_data_build_offer( $level, $context_type );
	if ( empty( $offer ) ) {
		return array();
	}

	$checkout_url = pmpro_structured_data_get_level_checkout_url( $level );
	$product_id   = $checkout_url . '#pmpro-membership-level-' . (int) $level->id;

	$schema = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Product',
		'@id'      => $product_id,
		'name'     => wp_strip_all_tags( (string) $level->name ),
		'sku'      => 'pmpro-level-' . (int) $level->id,
		'category' => 'Membership',
		'url'      => $checkout_url,
		'offers'   => $offer,
	);

	if ( $include_description && ! empty( $level->description ) ) {
		$description = wp_strip_all_tags( (string) $level->description );
		$description = trim( preg_replace( '/\s+/', ' ', $description ) );
		if ( $description !== '' ) {
			$schema['description'] = $description;
		}
	}

	/**
	 * Filter product image URL for a level. Default empty (omit).
	 * Do not use the site logo/icon — only a true level/product image.
	 *
	 * @since TBD
	 *
	 * @param string $image Image URL or empty.
	 * @param object $level Level object.
	 * @param string $context_type Context type.
	 */
	$image = apply_filters( 'pmpro_structured_data_product_image', '', $level, $context_type );
	if ( ! empty( $image ) ) {
		$schema['image'] = esc_url_raw( $image );
	}

	/**
	 * Filter the full Product schema for a membership level.
	 * Return empty to skip this product.
	 *
	 * @since TBD
	 *
	 * @param array  $schema       Product schema.
	 * @param object $level        Level object.
	 * @param string $context_type Context type.
	 */
	$schema = apply_filters( 'pmpro_structured_data_schema', $schema, $level, $context_type );

	return ( ! empty( $schema ) && is_array( $schema ) ) ? $schema : array();
}

/**
 * Build Offer schema for a level.
 *
 * Offer.price is the amount payable now (initial_payment). Google treats this
 * as the active price. Recurring terms are additional UnitPriceSpecification
 * data for agents/parsers; they may not change Google rich-result display.
 *
 * Trial levels omit detailed recurring priceSpecification so we do not publish
 * an incomplete billing schedule. Finite billing_limit is modeled via
 * billingDuration when there is no trial.
 *
 * @since TBD
 *
 * @param object $level        Level object.
 * @param string $context_type Context type.
 * @return array
 */
function pmpro_structured_data_build_offer( $level, $context_type = '' ) {
	global $pmpro_currency;

	if ( empty( $level ) || empty( $level->id ) ) {
		return array();
	}

	$currency = ! empty( $pmpro_currency ) ? $pmpro_currency : get_option( 'pmpro_currency', 'USD' );
	$price    = pmpro_structured_data_format_price_value( $level->initial_payment );
	$url      = pmpro_structured_data_get_level_checkout_url( $level );

	$seller = array(
		'@type' => 'Organization',
		'name'  => wp_strip_all_tags( get_bloginfo( 'name' ) ),
		'url'   => home_url( '/' ),
	);

	/**
	 * Filter the Offer.seller Organization for membership structured data.
	 *
	 * @since TBD
	 *
	 * @param array  $seller Seller organization schema.
	 * @param object $level  Level object.
	 */
	$seller = apply_filters( 'pmpro_structured_data_seller', $seller, $level );

	$offer = array(
		'@type'         => 'Offer',
		'url'           => $url,
		'price'         => $price,
		'priceCurrency' => $currency,
		'availability'  => 'https://schema.org/OnlineOnly',
		'category'      => 'Membership',
		'seller'        => $seller,
	);

	// Recurring component — skip detailed schedule when a trial would make it incomplete.
	$has_trial = ( ! empty( $level->trial_limit ) && (int) $level->trial_limit > 0 );
	$has_recur = ( isset( $level->billing_amount ) && (float) $level->billing_amount > 0 && ! empty( $level->cycle_period ) );

	if ( $has_recur && ! $has_trial ) {
		$unit_code = pmpro_structured_data_cycle_unit_code( $level->cycle_period );
		if ( ! empty( $unit_code ) ) {
			$spec = array(
				'@type'              => 'UnitPriceSpecification',
				'price'              => pmpro_structured_data_format_price_value( $level->billing_amount ),
				'priceCurrency'      => $currency,
				'priceComponentType' => 'https://schema.org/Subscription',
				'billingIncrement'   => max( 1, (int) $level->cycle_number ),
				'unitCode'           => $unit_code,
			);

			// billingDuration = total billing periods when finite (increment * limit).
			if ( ! empty( $level->billing_limit ) && (int) $level->billing_limit > 0 ) {
				$spec['billingDuration'] = max( 1, (int) $level->cycle_number ) * (int) $level->billing_limit;
			}

			$offer['priceSpecification'] = $spec;
		}
	}

	/**
	 * Filter the Offer schema for a membership level.
	 *
	 * @since TBD
	 *
	 * @param array  $offer        Offer schema.
	 * @param object $level        Level object.
	 * @param string $context_type Context type.
	 */
	$offer = apply_filters( 'pmpro_structured_data_offer', $offer, $level, $context_type );

	return ( ! empty( $offer ) && is_array( $offer ) ) ? $offer : array();
}

/**
 * Build ItemList schema wrapping Product entries.
 *
 * @since TBD
 *
 * @param array $products Product schema arrays.
 * @return array
 */
function pmpro_structured_data_build_item_list_schema( $products ) {
	$elements = array();
	$position = 1;
	foreach ( $products as $product ) {
		// Nested products inside ItemList should not repeat @context.
		if ( isset( $product['@context'] ) ) {
			unset( $product['@context'] );
		}
		$elements[] = array(
			'@type'    => 'ListItem',
			'position' => $position,
			'item'     => $product,
		);
		$position++;
	}

	return array(
		'@context'        => 'https://schema.org',
		'@type'           => 'ItemList',
		'numberOfItems'   => count( $elements ),
		'itemListOrder'   => 'https://schema.org/ItemListOrderAscending',
		'itemListElement' => $elements,
	);
}

/**
 * Checkout URL for a level, preserving discount code when present on the level.
 *
 * @since TBD
 *
 * @param object $level Level object.
 * @return string
 */
function pmpro_structured_data_get_level_checkout_url( $level ) {
	$query = '?pmpro_level=' . (int) $level->id;
	if ( ! empty( $level->discount_code ) ) {
		$query .= '&pmpro_discount_code=' . rawurlencode( (string) $level->discount_code );
	}

	$url = pmpro_url( 'checkout', $query );
	if ( empty( $url ) ) {
		$url = add_query_arg(
			array_filter(
				array(
					'pmpro_level'         => (int) $level->id,
					'pmpro_discount_code' => ! empty( $level->discount_code ) ? (string) $level->discount_code : null,
				)
			),
			home_url( '/' )
		);
	}

	return $url;
}

/**
 * Map PMPro cycle_period to UN/CEFACT unit codes used by schema.org.
 *
 * @since TBD
 *
 * @param string $period Day|Week|Month|Year.
 * @return string Empty if unknown.
 */
function pmpro_structured_data_cycle_unit_code( $period ) {
	$map = array(
		'Day'   => 'DAY',
		'Week'  => 'WEE',
		'Month' => 'MON',
		'Year'  => 'ANN',
	);
	$period = is_string( $period ) ? $period : '';
	return isset( $map[ $period ] ) ? $map[ $period ] : '';
}

/**
 * Format a price for schema.org (dot decimal, no currency symbol).
 *
 * @since TBD
 *
 * @param mixed $amount Amount.
 * @return string
 */
function pmpro_structured_data_format_price_value( $amount ) {
	$amount = (float) $amount;
	// Keep integer prices clean ("19") and preserve cents when needed ("19.5" -> "19.50" not required; "19.99" stays).
	if ( floor( $amount ) == $amount ) {
		return (string) (int) $amount;
	}
	return rtrim( rtrim( number_format( $amount, 2, '.', '' ), '0' ), '.' );
}

/**
 * Echo a JSON-LD script tag. Uses HEX flags so filtered values cannot break out of the script element.
 *
 * @since TBD
 *
 * @param array $schema Schema array.
 */
function pmpro_structured_data_print( $schema ) {
	if ( empty( $schema ) || ! is_array( $schema ) ) {
		return;
	}

	$flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;
	$json  = wp_json_encode( $schema, $flags );
	if ( false === $json ) {
		return;
	}

	echo '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON encoded with HEX flags.
}
