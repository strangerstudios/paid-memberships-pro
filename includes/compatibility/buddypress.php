<?php
/**
 * All BuddyPress/BuddyBoss compatibility goes in here.
 */

/**
 * If a user is deleting their own account, we want to make sure we cancel their subscriptions as well.
 *
 * @see https://www.buddyboss.com/resources/reference/functions/bp_core_delete_account/
 *
 * @param int $user_id - The user ID that is about to be deleted from WordPress.
 * @since 2.12
 */
function pmpro_buddypress_cancel_sub_self_delete( $user_id ) {
	add_filter( 'pmpro_user_deletion_cancel_active_subscriptions', '__return_true' );
}
add_action( 'bp_core_pre_delete_account', 'pmpro_buddypress_cancel_sub_self_delete' );

/**
 * Filter BuddyPress avatar to use PMPro avatar if set.
 *
 * @since TBD
 *
 * @param string $avatar      The avatar HTML.
 * @param array  $params      Array of parameters.
 * @param int    $item_id     The item ID (user ID for users).
 * @param string $avatar_dir  The avatar directory.
 * @param string $html_css_id CSS ID for the img tag.
 * @param string $html_width  Width attribute.
 * @param string $html_height Height attribute.
 * @param string $avatar_url  The avatar URL.
 * @param string $object      The object type (user, group, etc.).
 * @return string             The filtered avatar HTML.
 */
function pmpro_bp_get_avatar( $avatar, $params, $item_id, $avatar_dir, $html_css_id, $html_width, $html_height, $avatar_url, $object = 'user' ) {
	// Only filter user avatars.
	if ( 'user' !== $object ) {
		return $avatar;
	}

	// Check if user has avatar level.
	if ( ! pmpro_user_has_avatar_level( $item_id ) ) {
		return $avatar;
	}

	// Get PMPro avatar URL.
	$size = ! empty( $params['width'] ) ? (int) $params['width'] : 96;
	$pmpro_avatar_url = pmpro_avatar_get_url( $item_id, $size );

	if ( $pmpro_avatar_url ) {
		// Replace the avatar URL in the HTML.
		$avatar = preg_replace( '/src=["\'][^"\']+["\']/', 'src="' . esc_url( $pmpro_avatar_url ) . '"', $avatar );
	}

	return $avatar;
}
// Hook into BuddyPress avatar filter if available.
add_filter( 'bp_core_fetch_avatar', 'pmpro_bp_get_avatar', 100, 9 );

/**
 * Filter BuddyPress avatar URL to use PMPro avatar if set.
 *
 * @since TBD
 *
 * @param string $avatar_url The avatar URL.
 * @param array  $params     Array of parameters.
 * @return string            The filtered avatar URL.
 */
function pmpro_bp_get_avatar_url( $avatar_url, $params ) {
	// Check for user object type.
	if ( empty( $params['object'] ) || 'user' !== $params['object'] ) {
		return $avatar_url;
	}

	$user_id = ! empty( $params['item_id'] ) ? (int) $params['item_id'] : 0;

	if ( ! $user_id || ! pmpro_user_has_avatar_level( $user_id ) ) {
		return $avatar_url;
	}

	$size = ! empty( $params['width'] ) ? (int) $params['width'] : 96;
	$pmpro_avatar_url = pmpro_avatar_get_url( $user_id, $size );

	if ( $pmpro_avatar_url ) {
		return $pmpro_avatar_url;
	}

	return $avatar_url;
}
add_filter( 'bp_core_fetch_avatar_url', 'pmpro_bp_get_avatar_url', 100, 2 );
