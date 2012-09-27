<?php
	//thanks jigoshop
	global $pmpro_currencies, $pmpro_default_currency;
	$pmpro_default_currency = apply_filters("pmpro_default_currency", "USD");
	
	$pmpro_currencies = array( 
			'USD' => __('US Dollars (&#36;)', 'pmpro'),
			'EUR' => __('Euros (&euro;)', 'pmpro'),
			'GBP' => __('Pounds Sterling (&pound;)', 'pmpro'),
			'AUD' => __('Australian Dollars (&#36;)', 'pmpro'),
			'BRL' => __('Brazilian Real (&#36;)', 'pmpro'),
			'CAD' => __('Canadian Dollars (&#36;)', 'pmpro'),
			'CZK' => __('Czech Koruna', 'pmpro'),
			'DKK' => __('Danish Krone', 'pmpro'),
			'HKD' => __('Hong Kong Dollar (&#36;)', 'pmpro'),
			'HUF' => __('Hungarian Forint', 'pmpro'),
			'ILS' => __('Israeli Shekel', 'pmpro'),
			'JPY' => __('Japanese Yen (&yen;)', 'pmpro'),
			'MYR' => __('Malaysian Ringgits', 'pmpro'),
			'MXN' => __('Mexican Peso (&#36;)', 'pmpro'),
			'NZD' => __('New Zealand Dollar (&#36;)', 'pmpro'),
			'NOK' => __('Norwegian Krone', 'pmpro'),
			'PHP' => __('Philippine Pesos', 'pmpro'),
			'PLN' => __('Polish Zloty', 'pmpro'),
			'SGD' => __('Singapore Dollar (&#36;)', 'pmpro'),
			'SEK' => __('Swedish Krona', 'pmpro'),
			'CHF' => __('Swiss Franc', 'pmpro'),
			'TWD' => __('Taiwan New Dollars', 'pmpro'),
			'THB' => __('Thai Baht', 'pmpro') 
			);
	
	$pmpro_currencies = apply_filters("pmpro_currencies", $pmpro_currencies);
	
	//stripe only supports a few
	global $pmpro_stripe_currencies;
	$pmpro_stripe_currencies = array(
			'USD' => __('US Dollars (&#36;)', 'pmpro'),			
			'CAD' => __('Canadian Dollars (&#36;)', 'pmpro')
	);
?>