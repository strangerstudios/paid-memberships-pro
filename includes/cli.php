<?php
/**
 * Register PMPro WP-CLI commands.
 *
 * Provides `wp pmpro <noun> <verb>` commands (member, level, order, subscription)
 * that wrap the same PMPro functions used by the REST API, so PMPro data is
 * scriptable by default on any install.
 *
 * @since TBD
 * @package PaidMembershipsPro\CLI
 */

defined( 'ABSPATH' ) || exit;

if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	return;
}

require_once PMPRO_DIR . '/includes/cli/class-pmpro-cli-command.php';
require_once PMPRO_DIR . '/includes/cli/class-pmpro-cli-member.php';
require_once PMPRO_DIR . '/includes/cli/class-pmpro-cli-level.php';
require_once PMPRO_DIR . '/includes/cli/class-pmpro-cli-order.php';
require_once PMPRO_DIR . '/includes/cli/class-pmpro-cli-subscription.php';

WP_CLI::add_command( 'pmpro member', 'PMPro_CLI_Member' );
WP_CLI::add_command( 'pmpro level', 'PMPro_CLI_Level' );
WP_CLI::add_command( 'pmpro order', 'PMPro_CLI_Order' );
WP_CLI::add_command( 'pmpro subscription', 'PMPro_CLI_Subscription' );

/**
 * Fires after PMPro registers its core WP-CLI commands, so Add Ons can register their own.
 *
 * @since TBD
 */
do_action( 'pmpro_cli_init' );
