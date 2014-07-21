<?php
/**
 * class.pmprogateway_payfast.php
 *
 * 
 * Copyright (c) 2009-2014 PayFast (Pty) Ltd
 * 
 * LICENSE:
 * 
 * This payment module is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation; either version 3 of the License, or (at
 * your option) any later version.
 * 
 * This payment module is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public
 * License for more details.
 * 
 * @author     Ron Darby - PayFast
 * @copyright  2009-2014 PayFast (Pty) Ltd
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
    require_once(dirname(__FILE__) . "/class.pmprogateway.php");
    class PMProGateway_payfast
    {

        const SANDBOX_MERCHANT_KEY = '46f0cd694581a';
        const SANDBOX_MERCHANT_ID = '10000100';        

        function PMProGateway_payfast($gateway = NULL)
        {
            $this->gateway = $gateway;
            return $this->gateway;
        }                                       
        
        function process(&$order)
        {                       
            if(empty($order->code))
                $order->code = $order->getRandomCode();         
            
            //clean up a couple values
            $order->payment_type = "PayFast";
            $order->CardType = "";
            $order->cardtype = "";
            
            //just save, the user will go to PayFast to pay
            $order->status = "review";                                                      
            $order->saveOrder();

            return true;            
        }
        
        function sendToPayFast(&$order)
        {                       
            global $pmpro_currency; 

                   
            
            //taxes on initial amount
            $initial_payment = $order->InitialPayment;
            $initial_payment_tax = $order->getTaxForPrice($initial_payment);
            $initial_payment = round((float)$initial_payment + (float)$initial_payment_tax, 2);
            
            //taxes on the amount
            $amount = $order->PaymentAmount;
            $amount_tax = $order->getTaxForPrice($amount);                      
            $order->subtotal = $amount;
            $amount = round((float)$amount + (float)$amount_tax, 2);            
            
            //build PayFast Redirect 
            $environment = pmpro_getOption("gateway_environment");
            if("sandbox" === $environment || "beta-sandbox" === $environment)
            {
                $merchant_id = self::SANDBOX_MERCHANT_ID;
                $merchant_key = self::SANDBOX_MERCHANT_KEY;
                $payfast_url ="https://sandbox.payfast.co.za/eng/process";
            }                
            else
            {
                $merchant_id = pmpro_getOption("payfast_merchant_id");
                $merchant_key = pmpro_getOption("payfast_merchant_key");
                $payfast_url = "https://www.payfast.co.za/eng/process";
            }

            $data = array(
                'merchant_id'      => $merchant_id,            
                'merchant_key'  => $merchant_key, 
                'return_url'        => pmpro_url("confirmation", "?level=" . $order->membership_level->id),
                'cancel_url'    => '',
                'notify_url'    => admin_url("admin-ajax.php") . "?action=payfast_itn_handler",
                'name_first'    => $order->FirstName,
                'name_last'     => $order->LastName,
                'email_address' => $order->Email,
                'm_payment_id'  => $order->code,
                'amount'        => number_format($initial_payment, 2),
                'item_name'     => substr($order->membership_level->name . " at " . get_bloginfo("name"), 0, 127)
                );

            foreach( $data  as $key => $val )
            {
                $pfOutput .= $key .'='. urlencode( trim( $val ) ) .'&';
            }
            
    
            // Remove last ampersand
            $pfOutput = substr( $pfOutput, 0, -1 );

            $signature = md5( $pfOutput );

            foreach( $data  as $key => $val )
            {
                $payfast_url .= '?'.$pfOutput.'&signature='.$signature;
            }              
            
            
            wp_redirect($payfast_url);
            exit;
        }
                                        
        function cancel(&$order)
        {           
            //payfast profile stuff
            $nvpStr = "";           
            $nvpStr .= "&PROFILEID=" . urlencode($order->subscription_transaction_id) . "&ACTION=Cancel&NOTE=" . urlencode("User requested cancel.");
            
            $this->httpParsedResponseAr = $this->PPHttpPost('ManageRecurringPaymentsProfileStatus', $nvpStr);                       
                        
            if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"]) || $this->httpParsedResponseAr['L_ERRORCODE0'] == "11556") {                               
                $order->updateStatus("cancelled");                  
                return true;
                //exit('CreateRecurringPaymentsProfile Completed Successfully: '.print_r($this->httpParsedResponseAr, true));
            } else  {               
                $order->status = "error";
                $order->errorcode = $this->httpParsedResponseAr['L_ERRORCODE0'];
                $order->error = urldecode($this->httpParsedResponseAr['L_LONGMESSAGE0']);
                $order->shorterror = urldecode($this->httpParsedResponseAr['L_SHORTMESSAGE0']);
                                
                return false;
                //exit('CreateRecurringPaymentsProfile failed: ' . print_r($httpParsedResponseAr, true));
            }
        }   
        
        
    }