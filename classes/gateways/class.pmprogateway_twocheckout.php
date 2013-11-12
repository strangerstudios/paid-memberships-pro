<?php	
	require_once(dirname(__FILE__) . "/class.pmprogateway.php");
	if(!class_exists("Twocheckout"))
		require_once(dirname(__FILE__) . "/../../includes/lib/Twocheckout/Twocheckout.php");
	class PMProGateway_Twocheckout
	{
		function PMProGateway_Twocheckout($gateway = NULL)
		{
			$this->gateway = $gateway;
			return $this->gateway;
		}										
		
		function process(&$order)
		{						
			if(empty($order->code))
				$order->code = $order->getRandomCode();			
			
			//clean up a couple values
			$order->payment_type = "2CheckOut";
			$order->CardType = "";
			$order->cardtype = "";
			
			//just save, the user will go to 2checkout to pay
			$order->status = "review";														
			$order->saveOrder();

			return true;			
		}
		
		function sendToTwocheckout(&$order)
		{						
			global $pmpro_currency;			
			// Set up credentials
			Twocheckout::setCredentials( pmpro_getOption("twocheckout_apiusername"), pmpro_getOption("twocheckout_apipassword") );

			$tco_args = array(
				'sid' => pmpro_getOption("twocheckout_accountnumber"),
				'mode' => '2CO', // will always be 2CO according to docs (@see https://www.2checkout.com/documentation/checkout/parameter-sets/pass-through-products/)
				'li_0_type' => 'product',
				'li_0_name' => substr($order->membership_level->name . " at " . get_bloginfo("name"), 0, 127),
				'li_0_quantity' => 1,
				'li_0_tangible' => 'N',
				'li_0_product_id' => $order->code,
				'currency_code' => $pmpro_currency,
				'pay_method' => 'CC',
				'purchase_step' => 'billing-information',
				'x_receipt_link_url' => pmpro_url("confirmation", "?level=" . $order->membership_level->id)
			);

			//taxes on initial amount
			$initial_payment = $order->InitialPayment;
			$initial_payment_tax = $order->getTaxForPrice($initial_payment);
			$initial_payment = round((float)$initial_payment + (float)$initial_payment_tax, 2);

			// Recurring membership
			if( pmpro_isLevelRecurring( $order->membership_level ) ) {
				$tco_args['li_0_startup_fee'] = $initial_payment;

				$recurring_payment = $order->membership_level->billing_amount;
				$recurring_payment_tax = $order->getTaxForPrice($recurring_payment);
				$recurring_payment = round((float)$recurring_payment + (float)$recurring_payment_tax, 2);
				$tco_args['li_0_price'] = $recurring_payment;

				$tco_args['li_0_recurrance'] = ( $order->BillingFrequency == 1 ) ? $order->BillingFrequency . ' ' . $order->BillingPeriod : $order->BillingFrequency . ' ' . $order->BillingPeriod . 's';

				if( property_exists( $order, 'TotalBillingCycles' ) )
					$tco_args['li_0_duration'] = ($order->BillingFrequency * $order->TotalBillingCycles ) . ' ' . $order->BillingPeriod;
			}
			// Non-recurring membership
			else {
				$tco_args['li_0_price'] = $initial_payment;
			}

			// Demo mode?
			$environment = pmpro_getOption("gateway_environment");
			if("sandbox" === $environment || "beta-sandbox" === $environment)
				$tco_args['demo'] = 'Y';
			
			// Trial?
			//li_#_startup_fee	Any start up fees for the product or service. Can be negative to provide discounted first installment pricing, but cannot equal or surpass the product price.
			if(!empty($order->TrialBillingPeriod)) {
				$trial_amount = $order->TrialAmount;
				$trial_tax = $order->getTaxForPrice($trial_amount);
				$trial_amount = round((float)$trial_amount + (float)$trial_tax, 2);
				$tco_args['li_0_startup_fee'] = $trial_amount; // Negative trial amount
			}

			//taxes on the amount (NOT CURRENTLY USED)
			$amount = $order->PaymentAmount;
			$amount_tax = $order->getTaxForPrice($amount);						
			$order->subtotal = $amount;
			$amount = round((float)$amount + (float)$amount_tax, 2);			
			
			$ptpStr = '';
			foreach( $tco_args as $key => $value ) {
				reset( $tco_args ); // Used to verify whether or not we're on the first argument
				$ptpStr .= ( $key == key($tco_args) ) ? '?' . $key . '=' . urlencode( $value ) : '&' . $key . '=' . urlencode( $value );
			}

			//anything modders might add
			$additional_parameters = apply_filters( 'pmpro_twocheckout_return_url_parameters', array() );									
			if( ! empty( $additional_parameters ) )
				foreach( $additional_parameters as $key => $value )				
					$ptpStr .= urlencode( "&" . $key . "=" . $value );

			$ptpStr = apply_filters( 'pmpro_twocheckout_ptpstr', $ptpStr, $order );
						
			//redirect to 2checkout			
			$tco_url = 'https://www.2checkout.com/checkout/purchase' . $ptpStr;
			
			//echo $tco_url;
			//die();
			wp_redirect( $tco_url );
			exit;
		}

		function cancel(&$order) {
			// If recurring, stop the recurring payment
			if(pmpro_isLevelRecurring($order->membership_level)) {
				$params['sale_id'] = $order->payment_transaction_id;
				$result = Twocheckout_Sale::stop( $params ); // Stop the recurring billing

				// Successfully cancelled
				if (isset($result['response_code']) && $result['response_code'] === 'OK') {
					$order->updateStatus("cancelled");	
					return true;
				}
				// Failed
				else {
					$order->status = "error";
					$order->errorcode = $result->getCode();
					$order->error = $result->getMessage();
									
					return false;
				}
			}

			return $order;
		}
	}
?>
