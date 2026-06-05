<?php
/**
 * Upgrade to version 3.8
 *
 * Clean up non-historical membership level relationship rows orphaned by deleted levels.
 *
 * @since 3.8
 */
function pmpro_upgrade_3_8() {
	pmpro_delete_orphaned_membership_level_relationships();
}
