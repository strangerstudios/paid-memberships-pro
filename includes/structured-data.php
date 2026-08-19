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
	static $printed = false;
	if ( $printed || is_admin() || ! pmpro_structured_data_is_enabled() ) {
		return;
	}

	$context = pmpro_structured_data_get_context();
	if ( empty( $context ) || ! is_array( $context ) || empty( $context['type'] ) ) {
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
	 *     @type string $type                 Context type: levels_list|checkout|single_levels.
	 *     @type array  $levels               Level objects for output.
	 *     @type string $source               Detection source identifier.
	 *     @type bool   $include_description  Whether level descriptions are included.
	 * }
	 */
	$context = apply_filters( 'pmpro_structured_data_context', $context );
	if ( empty( $context ) || ! is_array( $context ) || empty( $context['type'] ) || empty( $context['levels'] ) ) {
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
		if ( ! empty( $schema ) && is_array( $schema ) ) {
			pmpro_structured_data_print( $schema );
			$printed = true;
		}
		return;
	}

	pmpro_structured_data_print( $products[0] );
	$printed = true;
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

	// Content-based detection only on the singular queried object (avoid loop leftovers).
	if ( ! is_singular() || empty( $post ) || empty( $post->post_content ) ) {
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
	if ( ! has_block( 'pmpro/single-level', $post ) ) {
		return false;
	}

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

	// Stable public checkout URL (no discount codes — those must not leak into crawlable schema).
	$checkout_url = pmpro_structured_data_get_level_checkout_url( $level );
	// Stable @id independent of discount / request query args.
	$product_id   = home_url( '/#pmpro-membership-level-' . (int) $level->id );

	$schema = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Product',
		'@id'      => $product_id,
		'name'     => wp_strip_all_tags( (string) $level->name ),
		'sku'      => 'pmpro-level-' . (int) $level->id,
		'category' => 'Membership',
		'brand'    => array(
			'@type' => 'Brand',
			'name'  => wp_strip_all_tags( get_bloginfo( 'name' ) ),
		),
		'url'      => $checkout_url,
		'offers'   => $offer,
	);

	// Description when available (optional for Google; include on checkout / single-level pages).
	if ( $include_description && ! empty( $level->description ) ) {
		$description = wp_strip_all_tags( (string) $level->description );
		$description = trim( preg_replace( '/\s+/', ' ', $description ) );
		if ( $description !== '' ) {
			$schema['description'] = $description;
		}
	} elseif ( ! empty( $level->name ) ) {
		// Fallback so Product snippets always have a short description when level body is empty.
		$schema['description'] = sprintf(
			/* translators: %s: membership level name */
			__( '%s membership', 'paid-memberships-pro' ),
			wp_strip_all_tags( (string) $level->name )
		);
	}

	/**
	 * Filter product image URL for a level.
	 * Default: site icon → custom logo → omitted. Image is recommended for
	 * product snippets and required only for merchant listing experiences.
	 *
	 * @since TBD
	 *
	 * @param string $image        Image URL.
	 * @param object $level        Level object.
	 * @param string $context_type Context type.
	 */
	$image = apply_filters(
		'pmpro_structured_data_product_image',
		pmpro_structured_data_get_fallback_image(),
		$level,
		$context_type
	);
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
 * Offer.price is the primary advertised price Google treats as active:
 * - initial_payment when > 0
 * - otherwise recurring billing_amount when the level is free-to-start
 *   (avoids advertising $0 for a paid subscription)
 *
 * Recurring terms are always added as UnitPriceSpecification when present
 * (including after a trial) so agents can see the ongoing rate. Finite
 * billing_limit is modeled via billingDuration.
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
	$url      = pmpro_structured_data_get_level_checkout_url( $level );

	$has_recur = ( isset( $level->billing_amount ) && (float) $level->billing_amount > 0 && ! empty( $level->cycle_period ) );
	$has_trial = ( ! empty( $level->trial_limit ) && (int) $level->trial_limit > 0 );
	$initial   = isset( $level->initial_payment ) ? (float) $level->initial_payment : 0.0;

	/*
	 * Primary Offer.price (what Google treats as active):
	 * 1. initial_payment when charged now
	 * 2. trial_amount when a trial is configured (amount for trial periods)
	 * 3. billing_amount for free-to-start recurring (avoid advertising $0 paid subs)
	 * 4. else 0 (truly free)
	 */
	if ( $initial > 0 ) {
		$price_amount = $initial;
	} elseif ( $has_trial ) {
		$price_amount = isset( $level->trial_amount ) ? (float) $level->trial_amount : 0.0;
	} elseif ( $has_recur ) {
		$price_amount = (float) $level->billing_amount;
	} else {
		$price_amount = $initial;
	}
	$price = pmpro_structured_data_format_price_value( $price_amount );

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
	if ( empty( $seller ) || ! is_array( $seller ) ) {
		$seller = array(
			'@type' => 'Organization',
			'name'  => wp_strip_all_tags( get_bloginfo( 'name' ) ),
			'url'   => home_url( '/' ),
		);
	}

	$offer = array(
		'@type'         => 'Offer',
		'url'           => $url,
		'price'         => $price,
		'priceCurrency' => $currency,
		'availability'  => 'https://schema.org/OnlineOnly',
		'category'      => 'Membership',
		'seller'        => $seller,
	);

	/*
	 * Recurring UnitPriceSpecification for non-trial levels so agents see the
	 * ongoing rate. Skip when a trial is configured — a partial schedule that
	 * ignores trial_amount/trial_limit would misstate what the customer pays.
	 */
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
 * Default product image for structured data.
 * Site icon, then custom logo. Empty when neither is set so we never publish
 * an image that doesn't represent the site's own branding.
 *
 * @since TBD
 *
 * @return string Image URL or empty.
 */
function pmpro_structured_data_get_fallback_image() {
	$url = get_site_icon_url( 512 );
	if ( empty( $url ) ) {
		$logo_id = get_theme_mod( 'custom_logo' );
		if ( ! empty( $logo_id ) ) {
			$url = wp_get_attachment_image_url( (int) $logo_id, 'full' );
		}
	}
	return ! empty( $url ) ? $url : '';
}

/**
 * Public checkout URL for a level.
 *
 * Discount codes are never included — private/targeted codes must not appear
 * in crawlable JSON-LD. Checkout pages still use the discount-adjusted level
 * object for Offer.price when a code is active in the request.
 *
 * @since TBD
 *
 * @param object $level Level object.
 * @return string
 */
function pmpro_structured_data_get_level_checkout_url( $level ) {
	$query = '?pmpro_level=' . (int) $level->id;
	$url   = pmpro_url( 'checkout', $query, 'https' );
	if ( empty( $url ) ) {
		$url = add_query_arg( 'pmpro_level', (int) $level->id, home_url( '/' ) );
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
	$period = is_string( $period ) ? ucfirst( strtolower( $period ) ) : '';
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
	if ( ! is_numeric( $amount ) ) {
		return '0';
	}
	$amount = (float) $amount;
	if ( ! is_finite( $amount ) || $amount < 0 ) {
		return '0';
	}
	$decimals = function_exists( 'pmpro_get_decimal_place' ) ? (int) pmpro_get_decimal_place() : 2;
	if ( $decimals < 0 ) {
		$decimals = 2;
	}
	// Keep integer prices clean when currency allows whole units.
	if ( $decimals === 0 || floor( $amount ) == $amount ) {
		return (string) (int) round( $amount );
	}
	return number_format( $amount, $decimals, '.', '' );
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
