<?php
function pmpro_upgrade_1_3_18()
{
	//setting new email settings defaults
	update_option("pmpro_email_admin_checkout", "1");
	update_option("pmpro_email_admin_changes", "1");
	update_option("pmpro_email_admin_cancels", "1");
	update_option("pmpro_email_admin_billing", "1");

	update_option("pmpro_db_version", "1.318");
	return 1.318;
}
