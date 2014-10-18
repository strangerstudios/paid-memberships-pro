<?php
	//thanks jigoshop
	global $pmpro_currencies, $pmpro_default_currency;
	$pmpro_default_currency = apply_filters("pmpro_default_currency", "USD");
	
	$pmpro_currencies = array( 
			'USD' => __('US Dollars (&#36;)', 'pmpro'),
			'EUR' => array(
				'name' => __('Euros (&euro;)', 'pmpro'),
				'symbol' => '&euro;',
				'position' => 'right'
				),				
			'GBP' => array(
				'name' => __('Pounds Sterling (&pound;)', 'pmpro'),
				'symbol' => '&pound;',
				'position' => 'right'
				),
			'AUD' => __('Australian Dollars (&#36;)', 'pmpro'),
			'BRL' => array(
				'name' => __('Brazilian Real (R&#36;)', 'pmpro'),
				'symbol' => 'R&#36;',
				'position' => 'left'
				),
			'CAD' => __('Canadian Dollars (&#36;)', 'pmpro'),
			'CNY' => __('Chinese Yuan', 'pmpro'),
			'CZK' => __('Czech Koruna', 'pmpro'),
			'DKK' => __('Danish Krone', 'pmpro'),
			'HKD' => __('Hong Kong Dollar (&#36;)', 'pmpro'),
			'HUF' => __('Hungarian Forint', 'pmpro'),
			'INR' => __('Indian Rupee', 'pmpro'),
			'IDR' => __('Indonesia Rupiah', 'pmpro'),
			'ILS' => __('Israeli Shekel', 'pmpro'),
			'JPY' => array(
				'name' => __('Japanese Yen (&yen;)', 'pmpro'),
				'symbol' => '&yen;',
				'position' => 'right'
				),
			'MYR' => __('Malaysian Ringgits', 'pmpro'),
			'MXN' => __('Mexican Peso (&#36;)', 'pmpro'),
			'NZD' => __('New Zealand Dollar (&#36;)', 'pmpro'),
			'NOK' => __('Norwegian Krone', 'pmpro'),
			'PHP' => __('Philippine Pesos', 'pmpro'),
			'PLN' => __('Polish Zloty', 'pmpro'),
			'SGD' => array(
				'name' => __('Singapore Dollar (&#36;)', 'pmpro'),
				'symbol' => '&#36;',
				'position' => 'right'
				),
			'ZAR' => __('South African Rand', 'pmpro'),
			'KRW' => __('South Korean Won', 'pmpro'),
			'SEK' => __('Swedish Krona', 'pmpro'),
			'CHF' => __('Swiss Franc', 'pmpro'),
			'TWD' => __('Taiwan New Dollars', 'pmpro'),
			'THB' => __('Thai Baht', 'pmpro'),
			'TRY' => __('Turkish Lira', 'pmpro'),
			'VND' => __('Vietnamese Dong', 'pmpro')
			);
	
	$pmpro_currencies = apply_filters("pmpro_currencies", $pmpro_currencies);
	
	//stripe only supports a few (not using this anymore since 1.7.4)
	global $pmpro_stripe_currencies;
	$pmpro_stripe_currencies = array(
			'USD' => __('US Dollars (&#36;)', 'pmpro'),			
			'CAD' => __('Canadian Dollars (&#36;)', 'pmpro'),
			'GBP' => __('Pounds Sterling (&pound;)', 'pmpro'),
			'EUR' => __('Euros (&euro;)', 'pmpro')
	);
?>