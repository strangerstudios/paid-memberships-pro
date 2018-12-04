<?php
/**
 * Sets up membership block, does not format frontend
 *
 * @package blocks/membership
 **/

namespace PMPro\blocks\membership;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

// Only load if Gutenberg is available.
if ( ! function_exists( 'register_block_type' ) ) {
	return;
}

add_action( 'init', __NAMESPACE__ . '\register_dynamic_block' );
/**
 * Register the dynamic block.
 *
 * @since 2.1.0
 *
 * @return void
 */
function register_dynamic_block() {

	// Hook server side rendering into render callback.
	register_block_type( 'pmpro/membership', [
		'render_callback' => __NAMESPACE__ . '\render_dynamic_block',
	] );
}

function pmpro_get_all_levels_ajax() {
	$all_levels = pmpro_getAllLevels();
	$to_pass    = [];
	foreach ( $all_levels as $level ) {
		$to_pass[ $level->id ] = $level->name;
	}
	echo( wp_json_encode( $to_pass ) );
}
add_action( 'wp_ajax_pmpro_get_all_levels', __NAMESPACE__ . '\pmpro_get_all_levels_ajax' );

/**
 * Server rendering for membership block.
 *
 * @param array $attributes contains text, level, and css_class strings.
 * @return string
 **/
function render_dynamic_block( $attributes ) {
	global $post;
	$tag_modifier = '';
	if ( ( ! array_key_exists( 'levels', $attributes ) || ! is_array( $attributes['levels'] ) ) && array_key_exists( 'uid', $attributes ) ) {
		$tag_modifier = ' {"uid":"' . $attributes['uid'] . '"}';
	} elseif ( array_key_exists( 'levels', $attributes ) && array_key_exists( 'uid', $attributes ) ) {
		$tag_modifier = ' {"levels":[' . pmpro_level_array_to_str( $attributes['levels'] ) . '],"uid":"' . $attributes['uid'] . '"}';
	} else {
		return '';
	}

	$start_string = '<!-- wp:pmpro/membership' . $tag_modifier . ' -->';
	$contents     = pmpro_get_membership_block_contents( $post->post_content, $start_string );
	if ( ! array_key_exists( 'levels', $attributes ) || ! is_array( $attributes['levels'] ) ) {
		return pmpro_shortcode_membership( [], do_blocks( $contents ) );
	} else {
		return pmpro_shortcode_membership( array( 'level' => str_replace( '"', '', pmpro_level_array_to_str( $attributes['levels'] ) ) ), do_blocks( $contents ) );
	}
}

/**
 * Gets content inside of membership block, should deal with nested membership blocks
 *
 * @param  string $post_content          the whole post contents.
 * @param  string $membership_block_tag  the string before the substring you want.
 * @return string                        the contents of the membership block
 */
function pmpro_get_membership_block_contents( $post_content, $membership_block_tag ) {
	$ini = strpos( $post_content, $membership_block_tag );
	if ( false === $ini ) {
		return '';
	}
	$ini += strlen( $membership_block_tag );
	$len  = strpos( $post_content, '<!-- /wp:pmpro/membership -->', $ini ) - $ini;
	return substr( $post_content, $ini, $len );
}

function pmpro_level_array_to_str( $arr ) {
	$output = '';
		foreach ( $arr as $level_id ) {
			$output .= '"' . $level_id . '",';
		}
		if ( strlen( $output ) > 1 ) {
			$output = substr( $output, 0, -1 );
		}
		return $output;
}
