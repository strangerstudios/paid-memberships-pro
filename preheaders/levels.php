<?php

global $current_user;

//is there a default level to redirect to?
if (defined("PMPRO_DEFAULT_LEVEL"))
    $default_level = intval(PMPRO_DEFAULT_LEVEL);
else
    $default_level = false;

if ($default_level) {
    wp_redirect(pmpro_url("checkout", "?pmpro_level=" . $default_level));
    exit;
}
