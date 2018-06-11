<?php
/**
 * Sets up membership block, does not format frontend
 *
 * @package blocks/membership
 **/

namespace PMPro\Blocks;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

// Only load if Gutenberg is available.
if ( ! function_exists( 'register_block_type' ) ) {
	return;
}

add_action( 'init', __NAMESPACE__ . '\pmpro_membership_register_dynamic_block' );
/**
 * Register the dynamic block.
 *
 * @since 2.1.0
 *
 * @return void
 */
function pmpro_membership_register_dynamic_block() {

	// Hook server side rendering into render callback.
	register_block_type( 'pmpro/membership', [
		'render_callback' => __NAMESPACE__ . '\pmpro_membership_render_dynamic_block',
	] );
}

/**
 * Server rendering for /blocks/examples/12-dynamic
 *
 * @param array $attributes contains text, level, and css_class strings.
 * @return string
 **/
function pmpro_membership_render_dynamic_block( $attributes ) {
	global $post;
	$substr = '';
	if ( ! empty( $attributes['levels'] ) ) {
		$substr = get_string_between( $post->post_content, '<!-- wp:pmpro/membership {"levels":"' . $attributes['levels'] . '"} -->)', '<!-- /wp:pmpro/membership -->' );
	} else {
		$substr = get_string_between( $post->post_content, '<!-- wp:pmpro/membership -->)', '<!-- /wp:pmpro/membership -->' );
	}
	return pmpro_shortcode_membership( array( 'level' => $attributes['levels'] ), do_blocks( $substr ) );
}

/**
 * Copied from http://www.justin-cook.com/2006/03/31/php-parse-a-string-between-two-strings/.
 *
 * @param  string $string the whole string to parse.
 * @param  string $start  the string before the substring you want.
 * @param  string $end    the string after the substring you want.
 * @return string         the string between start and end
 */
function get_string_between( $string, $start, $end ) {
	$string = ' ' . $string;
	$ini    = strpos( $string, $start );
	if ( 0 === $ini ) {
		return '';
	}
	$ini += strlen( $start );
	$len  = strpos( $string, $end, $ini ) - $ini;
	return substr( $string, $ini, $len );
}
