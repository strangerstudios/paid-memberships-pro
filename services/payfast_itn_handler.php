<?php   

      
    //in case the file is loaded directly   
    if(!defined("WP_USE_THEMES"))
    {
        global $isapage;
        $isapage = true;
        
        define('WP_USE_THEMES', false);
        require_once(dirname(__FILE__) . '/../../../../wp-load.php');
    }

    define( 'PF_SOFTWARE_NAME', 'Paid Membership Pro' );
    define( 'PF_SOFTWARE_VER',  '');
    define( 'PF_MODULE_NAME', 'PayFast-PaidMembershipPro' );
    define( 'PF_MODULE_VER', '1.0.0' );

    define( 'PF_DEBUG', pmpro_getOption("payfast_debug") );

    // Features
    // - PHP
    $pfFeatures = 'PHP '. phpversion() .';';

    // - cURL
    if( in_array( 'curl', get_loaded_extensions() ) )
    {
        define( 'PF_CURL', '' );
        $pfVersion = curl_version();
        $pfFeatures .= ' curl '. $pfVersion['version'] .';';
    }
    else
        $pfFeatures .= ' nocurl;';

    // Create user agrent
    define( 'PF_USER_AGENT', PF_SOFTWARE_NAME .'/'. PF_SOFTWARE_VER .' ('. trim( $pfFeatures ) .') '. PF_MODULE_NAME .'/'. PF_MODULE_VER );

    // General Defines
    define( 'PF_TIMEOUT', 15 );
    define( 'PF_EPSILON', 0.01 );

    // Messages
        // Error
    define( 'PF_ERR_AMOUNT_MISMATCH', 'Amount mismatch' );
    define( 'PF_ERR_BAD_ACCESS', 'Bad access of page' );
    define( 'PF_ERR_BAD_SOURCE_IP', 'Bad source IP address' );
    define( 'PF_ERR_CONNECT_FAILED', 'Failed to connect to PayFast' );
    define( 'PF_ERR_INVALID_SIGNATURE', 'Security signature mismatch' );
    define( 'PF_ERR_MERCHANT_ID_MISMATCH', 'Merchant ID mismatch' );
    define( 'PF_ERR_NO_SESSION', 'No saved session found for ITN transaction' );
    define( 'PF_ERR_ORDER_ID_MISSING_URL', 'Order ID not present in URL' );
    define( 'PF_ERR_ORDER_ID_MISMATCH', 'Order ID mismatch' );
    define( 'PF_ERR_ORDER_INVALID', 'This order ID is invalid' );
    define( 'PF_ERR_ORDER_NUMBER_MISMATCH', 'Order Number mismatch' );
    define( 'PF_ERR_ORDER_PROCESSED', 'This order has already been processed' );
    define( 'PF_ERR_PDT_FAIL', 'PDT query failed' );
    define( 'PF_ERR_PDT_TOKEN_MISSING', 'PDT token not present in URL' );
    define( 'PF_ERR_SESSIONID_MISMATCH', 'Session ID mismatch' );
    define( 'PF_ERR_UNKNOWN', 'Unkown error occurred' );

        // General
    define( 'PF_MSG_OK', 'Payment was successful' );
    define( 'PF_MSG_FAILED', 'Payment has failed' );
    define( 'PF_MSG_PENDING',
        'The payment is pending. Please note, you will receive another Instant'.
        ' Transaction Notification when the payment status changes to'.
        ' "Completed", or "Failed"' );
    
    //uncomment to log requests in logs/ipn.txt
    //define('PMPRO_IPN_DEBUG', true);
    
    //some globals
    global $wpdb, $gateway_environment, $logstr;
    $logstr = "";   //will put debug info here and write to ipnlog.txt
    
    
    // Variable Initialization
    $pfError = false;
    $pfErrMsg = '';
    $pfDone = false;
    $pfData = array();
    $pfHost = ( ( $gateway_environment == 'sandbox' ) ? 'sandbox' : 'www' ) . '.payfast.co.za';
    $pfOrderId = '';
    $pfParamString = '';

    

    ipnlog(  'PayFast ITN call received' );
    
    //// Notify PayFast that information has been received
    if( !$pfError && !$pfDone )
    {
        header( 'HTTP/1.0 200 OK' );
        flush();
    }

    //// Get data sent by PayFast
    if( !$pfError && !$pfDone )
    {
        ipnlog(  'Get posted data' );
    
        // Posted variables from ITN
        $pfData = pmpro_pfGetData();

        $morder = new MemberOrder( $pfData['m_payment_id'] );
        $morder->getMembershipLevel();
        $morder->getUser(); 
        ipnlog(  'PayFast Data: '. print_r( $pfData, true ) );
    
        if( $pfData === false )
        {
            $pfError = true;
            $pfErrMsg = PF_ERR_BAD_ACCESS;
        }
    }

    //// Verify security signature
    if( !$pfError && !$pfDone )
    {
        ipnlog(  'Verify security signature' );
    
        // If signature different, log for debugging
        if( !pmpro_pfValidSignature( $pfData, $pfParamString ) )
        {
            $pfError = true;
            $pfErrMsg = PF_ERR_INVALID_SIGNATURE;
        }
    }

    //// Verify source IP (If not in debug mode)
    if( !$pfError && !$pfDone && !PF_DEBUG )
    {
        ipnlog(  'Verify source IP' );
    
        if( !pmpro_pfValidIP( $_SERVER['REMOTE_ADDR'] ) )
        {
            $pfError = true;
            $pfErrMsg = PF_ERR_BAD_SOURCE_IP;
        }
    }  

    //// Verify data received
    if( !$pfError )
    {
        ipnlog(  'Verify data received' );
    
        $pfValid = pmpro_pfValidData( $pfHost, $pfParamString );
    
        if( !$pfValid )
        {
            $pfError = true;
            $pfErrMsg = PF_ERR_BAD_ACCESS;
        }
    }
        
    //// Check data against internal order
    if( !$pfError && !$pfDone )
    {
       
        if( !pmpro_pfAmountsEqual( $pfData['amount_gross'], $morder->total) )
        {
            ipnlog(  'Amount Returned: '.$pfData['amount_gross']."\n Amount in Cart:".$total );
            $pfError = true;
            $pfErrMsg = PF_ERR_AMOUNT_MISMATCH;
        }
        
    }

    //// Check status and update order
    if( !$pfError && !$pfDone )
    {
        ipnlog(  'Check status and update order' );

        $transaction_id = $pfData['pf_payment_id'];

        switch( $pfData['payment_status'] )
        {
            case 'COMPLETE':                
               
                //update membership
                if(pmpro_itnChangeMembershipLevel($transaction_id, $morder))
                {                                   
                    ipnlog("Checkout processed (" . $morder->code . ") success!");      
                }
                else
                {
                    ipnlog("ERROR: Couldn't change level for order (" . $morder->code . ").");      
                }   
                
                break;

            case 'FAILED':
                ipnlog("ERROR: ITN from PayFast for order (" . $morder->code . ") Failed.");
                break;

            case 'PENDING':
                ipnlog("ERROR: ITN from PayFast for order (" . $morder->code . ") Pending.");
                
                break;

            default:
                ipnlog("ERROR: Unknown error for order (" . $morder->code . ").");
            break;
        }
    }

    // If an error occurred
    if( $pfError )
    {
        ipnlog( 'Error occurred: '. $pfErrMsg );
    }

    pmpro_ipnExit();   
    
    /*
        Add message to ipnlog string
    */
    function ipnlog($s)
    {       
        global $logstr;     
        $logstr .= "\t" . $s . "\n";
    }
    
    /*
        Output ipnlog and exit;
    */
    function pmpro_ipnExit()
    {
        global $logstr;
        
        //for log
        if($logstr)
        {
            $logstr = "Logged On: " . date("m/d/Y H:i:s") . "\n" . $logstr . "\n-------------\n";       
            
            //log?
            if( PF_DEBUG )
            {
                echo $logstr;                
                $loghandle = fopen(dirname(__FILE__) . "/../logs/payfast_itn.txt", "a+");   
                fwrite($loghandle, $logstr);
                fclose($loghandle);
            }
        }
        
        exit;
    }

      
    /*
        Change the membership level. We also update the membership order to include filtered valus.
    */
    function pmpro_itnChangeMembershipLevel($txn_id, &$morder)
    {
        //filter for level
        $morder->membership_level = apply_filters("pmpro_ipnhandler_level", $morder->membership_level, $morder->user_id);
                    
        //fix expiration date       
        if(!empty($morder->membership_level->expiration_number))
        {
            $enddate = "'" . date("Y-m-d", strtotime("+ " . $morder->membership_level->expiration_number . " " . $morder->membership_level->expiration_period)) . "'";
        }
        else
        {
            $enddate = "NULL";
        }
        
        //get discount code     (NOTE: but discount_code isn't set here. How to handle discount codes for PayPal Standard?)
        $use_discount_code = true;      //assume yes
        if(!empty($discount_code) && !empty($use_discount_code))
            $discount_code_id = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_discount_codes WHERE code = '" . $discount_code . "' LIMIT 1");
        else
            $discount_code_id = "";
        
        //set the start date to NOW() but allow filters
        $startdate = apply_filters("pmpro_checkout_start_date", "NOW()", $morder->user_id, $morder->membership_level);
        
        //custom level to change user to
        $custom_level = array(
            'user_id' => $morder->user_id,
            'membership_id' => $morder->membership_level->id,
            'code_id' => $discount_code_id,
            'initial_payment' => $morder->membership_level->initial_payment,
            'billing_amount' => $morder->membership_level->billing_amount,
            'cycle_number' => $morder->membership_level->cycle_number,
            'cycle_period' => $morder->membership_level->cycle_period,
            'billing_limit' => $morder->membership_level->billing_limit,
            'trial_amount' => $morder->membership_level->trial_amount,
            'trial_limit' => $morder->membership_level->trial_limit,
            'startdate' => $startdate,
            'enddate' => $enddate);

        global $pmpro_error;
        if(!empty($pmpro_error))
        {
            echo $pmpro_error;
            ipnlog($pmpro_error);               
        }               
        
        //change level and continue "checkout"
        if(pmpro_changeMembershipLevel($custom_level, $morder->user_id) !== false)
        {                       
            //update order status and transaction ids                   
            $morder->status = "success";
            $morder->payment_transaction_id = $txn_id;
            if(!empty($_POST['subscr_id']))
                $morder->subscription_transaction_id = $_POST['subscr_id'];
            else
                $morder->subscription_transaction_id = "";
            $morder->saveOrder();
            
            //add discount code use
            if(!empty($discount_code) && !empty($use_discount_code))
            {
                $wpdb->query("INSERT INTO $wpdb->pmpro_discount_codes_uses (code_id, user_id, order_id, timestamp) VALUES('" . $discount_code_id . "', '" . $morder->user_id . "', '" . $morder->id . "', now())");
            }                                   
        
            //save first and last name fields
            if(!empty($_POST['first_name']))
            {
                $old_firstname = get_user_meta($morder->user_id, "first_name", true);
                if(!empty($old_firstname))
                    update_user_meta($morder->user_id, "first_name", $_POST['first_name']);
            }
            if(!empty($_POST['last_name']))
            {
                $old_lastname = get_user_meta($morder->user_id, "last_name", true);
                if(!empty($old_lastname))
                    update_user_meta($morder->user_id, "last_name", $_POST['last_name']);
            }
                                                
            //hook
            do_action("pmpro_after_checkout", $morder->user_id);                        
        
            //setup some values for the emails
            if(!empty($morder))
                $invoice = new MemberOrder($morder->id);                        
            else
                $invoice = NULL;
        
            $user = get_userdata($morder->user_id);
            $user->membership_level = $morder->membership_level;        //make sure they have the right level info
        
            //send email to member
            $pmproemail = new PMProEmail();             
            $pmproemail->sendCheckoutEmail($user, $invoice);
                                        
            //send email to admin
            $pmproemail = new PMProEmail();
            $pmproemail->sendCheckoutAdminEmail($user, $invoice);
            
            return true;
        }
        else
            return false;
    }
    
    /**
     * pfGetData
     *  
     * @author Jonathan Smit (PayFast.co.za)
     */
    function pmpro_pfGetData()
    {
        // Posted variables from ITN
        $pfData = $_POST;

        // Strip any slashes in data
        foreach( $pfData as $key => $val )
            $pfData[$key] = stripslashes( $val );

        // Return "false" if no data was received
        if( sizeof( $pfData ) == 0 )
            return( false );
        else
            return( $pfData );
    }

    /**
     * pfValidSignature
     * 
     * @author Jonathan Smit (PayFast.co.za)
     */
    function pmpro_pfValidSignature( $pfData = null, &$pfParamString = null )
    {
        // Dump the submitted variables and calculate security signature
        foreach( $pfData as $key => $val )
        {
            if( $key != 'signature' )
            {
                $pfParamString .= $key .'='. urlencode( $val ) .'&';
            }
            else
            {
                break;
            }
        }

        // Remove the last '&' from the parameter string
        $pfParamString = substr( $pfParamString, 0, -1 );
        $signature = md5( $pfParamString );
        
        $result = ( $pfData['signature'] == $signature );

        ipnlog(  'Signature = '. ( $result ? 'valid' : 'invalid' ) );

        return( $result );
    }

    /**
     * pfValidData
     *
     * @author Jonathan Smit (PayFast.co.za)
     * @param $pfHost String Hostname to use 
     * @param $pfParamString String Parameter string to send
     * @param $proxy String Address of proxy to use or NULL if no proxy
     */
    function pmpro_pfValidData( $pfHost = 'www.payfast.co.za', $pfParamString = '', $pfProxy = null )
    {
        ipnlog(  'Host = '. $pfHost );
        ipnlog(  'Params = '. $pfParamString );

        // Use cURL (if available)
        if( defined( 'PF_CURL' ) )
        {
            // Variable initialization
            $url = 'https://'. $pfHost .'/eng/query/validate';

            // Create default cURL object
            $ch = curl_init();
        
            // Set cURL options - Use curl_setopt for freater PHP compatibility
            // Base settings
            curl_setopt( $ch, CURLOPT_USERAGENT, PF_USER_AGENT );  // Set user agent
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );      // Return output as string rather than outputting it
            curl_setopt( $ch, CURLOPT_HEADER, false );             // Don't include header in output
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
            
            // Standard settings
            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_POST, true );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $pfParamString );
            curl_setopt( $ch, CURLOPT_TIMEOUT, PF_TIMEOUT );
            if( !empty( $pfProxy ) )
                curl_setopt( $ch, CURLOPT_PROXY, $proxy );
        
            // Execute CURL
            $response = curl_exec( $ch );
            curl_close( $ch );
        }
        // Use fsockopen
        else
        {
            // Variable initialization
            $header = '';
            $res = '';
            $headerDone = false;
             
            // Construct Header
            $header = "POST /eng/query/validate HTTP/1.0\r\n";
            $header .= "Host: ". $pfHost ."\r\n";
            $header .= "User-Agent: ". PF_USER_AGENT ."\r\n";
            $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $header .= "Content-Length: " . strlen( $pfParamString ) . "\r\n\r\n";
     
            // Connect to server
            $socket = fsockopen( 'ssl://'. $pfHost, 443, $errno, $errstr, PF_TIMEOUT );
     
            // Send command to server
            fputs( $socket, $header . $pfParamString );
     
            // Read the response from the server
            while( !feof( $socket ) )
            {
                $line = fgets( $socket, 1024 );
     
                // Check if we are finished reading the header yet
                if( strcmp( $line, "\r\n" ) == 0 )
                {
                    // read the header
                    $headerDone = true;
                }
                // If header has been processed
                else if( $headerDone )
                {
                    // Read the main response
                    $response .= $line;
                }
            }
            
        }

        ipnlog(  "Response:\n". print_r( $response, true ) );

        // Interpret Response
        $lines = explode( "\r\n", $response );
        $verifyResult = trim( $lines[0] );

        if( strcasecmp( $verifyResult, 'VALID' ) == 0 )
            return( true );
        else
            return( false );
    }

    /**
     * pfValidIP
     *
     * @author Jonathan Smit (PayFast.co.za)
     * @param $sourceIP String Source IP address 
     */
    function pmpro_pfValidIP( $sourceIP )
    {
        // Variable initialization
        $validHosts = array(
            'www.payfast.co.za',
            'sandbox.payfast.co.za',
            'w1w.payfast.co.za',
            'w2w.payfast.co.za',
            );

        $validIps = array();

        foreach( $validHosts as $pfHostname )
        {
            $ips = gethostbynamel( $pfHostname );

            if( $ips !== false )
                $validIps = array_merge( $validIps, $ips );
        }

        // Remove duplicates
        $validIps = array_unique( $validIps );

        ipnlog(  "Valid IPs:\n". print_r( $validIps, true ) );

        if( in_array( $sourceIP, $validIps ) )
            return( true );
        else
            return( false );
    }

    /**
     * pfAmountsEqual
     * 
     * Checks to see whether the given amounts are equal using a proper floating
     * point comparison with an Epsilon which ensures that insignificant decimal
     * places are ignored in the comparison.
     * 
     * eg. 100.00 is equal to 100.0001
     *
     * @author Jonathan Smit (PayFast.co.za)
     * @param $amount1 Float 1st amount for comparison 
     * @param $amount2 Float 2nd amount for comparison
     */
    function pmpro_pfAmountsEqual( $amount1, $amount2 )
    {
        if( abs( floatval( $amount1 ) - floatval( $amount2 ) ) > PF_EPSILON )
            return( false );
        else
            return( true );
    }