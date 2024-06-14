<?php
/*
	Upgrade to 1.8.8
	* Running the cron job cleanup again.
	* Fixing old Authorize.net orders with empty status.
	* Fixing old $0 Stripe orders.	
*/
function pmpro_upgrade_1_8_8() {
	global $wpdb;
	
	//Running the cron job cleanup again.
	require_once(PMPRO_DIR . "/includes/updates/upgrade_1_8_7.php");
	pmpro_upgrade_1_8_7();
	
	//Fixing old Authorize.net orders with empty status.
	$sqlQuery = "UPDATE $wpdb->pmpro_membership_orders SET status = 'success' WHERE gateway = 'authorizenet' AND status = ''";
	$wpdb->query($sqlQuery);
	
	// Since 3.0: Removed the Stripe update, which relied on deprecated code.


	update_option("pmpro_db_version", "1.88");
	return 1.88;
}
