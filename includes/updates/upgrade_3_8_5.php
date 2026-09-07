<?php
/**
 * Upgrade to version 3.8.5
 *
 * Migrate expiration notice user meta to per-site user options.
 *
 * The expiration notice timestamp for a membership was stored as global
 * user meta (pmpro_expiration_notice_{membership_id}). On a multisite
 * network where each subsite runs its own copy of PMPro, subsites with
 * the same membership ID clobbered each other's timestamps, which led
 * to duplicate or missing expiration reminder emails. The notice is now
 * stored with update_user_option(), which prefixes the key with the
 * blog prefix, so each site tracks its own notices.
 *
 * This upgrade renames existing rows to the blog-prefixed key. Legacy rows
 * are shared across the network with no record of which site wrote them, so
 * on multisite only the main site claims them. Subsites leave the legacy
 * rows untouched and start fresh with their own prefixed keys. Rows that
 * were already renamed no longer match the LIKE pattern, so the update is
 * safe to run more than once.
 *
 * @since 3.8.5
 */
function pmpro_upgrade_3_8_5() {
	global $wpdb;

	if ( is_multisite() && ! is_main_site() ) {
		return;
	}

	$wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->usermeta}
			SET meta_key = CONCAT( %s, meta_key )
			WHERE meta_key LIKE %s",
			$wpdb->get_blog_prefix(),
			$wpdb->esc_like( 'pmpro_expiration_notice_' ) . '%'
		)
	);
}
