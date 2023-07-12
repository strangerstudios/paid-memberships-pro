<?php
function pmpro_upgrade_1_4_2()
{
	/*
		Setting the new use_ssl setting.
		PayPal Website Payments Pro, Authorize.net, and Stripe will default to use ssl.
		PayPal Express and the test gateway (no gateway) will default to not use ssl.
	*/
	$gateway = get_option( "pmpro_gateway");
	if($gateway == "paypal" || $gateway == "authorizenet" || $gateway == "stripe")
		update_option("pmpro_use_ssl", 1);
	else
		update_option("pmpro_use_ssl", 0);

	update_option("pmpro_db_version", "1.42");
	return 1.42;
}
