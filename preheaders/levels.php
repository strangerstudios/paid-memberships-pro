<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $current_user;

//is there a default level to redirect to?
if (defined("PMPRO_DEFAULT_LEVEL"))
    $default_level = intval(PMPRO_DEFAULT_LEVEL);
else
    $default_level = false;

if ($default_level) {
    wp_redirect(pmpro_url("checkout", "?pmpro_level=" . $default_level)); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- pmpro_url() is filterable; add-ons such as Network Subsite point checkout at another site's domain.
    exit;
}
