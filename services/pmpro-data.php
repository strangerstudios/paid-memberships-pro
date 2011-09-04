<?php
  //this file is launched via AJAX to get various data from the DB for the stranger_products plugin

  //wp includes
  define('WP_USE_THEMES', false);
  require('../../../../wp-load.php');

  //some vars
  $action = $_REQUEST['action'];
  $saveandnext = $_REQUEST['saveandnext'];
  
  $saveid = $_REQUEST['saveid'];
  $deleteid = $_REQUEST['deleteid'];

  if($action == "save_membershiplevel")
  {
    $ml_name = addslashes($_REQUEST['name']);
    $ml_description = addslashes($_REQUEST['description']);
	$ml_initial_payment = addslashes($_REQUEST['initial_payment']);
	$ml_recurring = $_REQUEST['recurring'];
	$ml_billing_amount = addslashes($_REQUEST['billing_amount']);
    $ml_cycle_number = addslashes($_REQUEST['cycle_number']);
    $ml_cycle_period = addslashes($_REQUEST['cycle_period']);		
    $ml_billing_limit = addslashes($_REQUEST['billing_limit']);
    $ml_custom_trial = $_REQUEST['custom_trial'];
    $ml_trial_amount = addslashes($_REQUEST['trial_amount']);
    $ml_trial_limit = addslashes($_REQUEST['trial_limit']);  
    $ml_expiration = $_REQUEST['expiration'];
	$ml_expiration_number = addslashes($_REQUEST['expiration_number']);
	$ml_expiration_period = addslashes($_REQUEST['expiration_period']);
	$ml_categories = array();
	$ml_disable_signups = $_REQUEST['disable_signups'];
	if($ml_disable_signups)
		$ml_allow_signups = 0;
	else
		$ml_allow_signups = 1;

    foreach ( $_REQUEST as $key => $value )
    {
      if ( $value == 'yes' && preg_match( '/^membershipcategory_(\d+)$/i', $key, $matches ) )
      {
        $ml_categories[] = $matches[1];
      }
    }
    
	if ( $ml_recurring != "yes" )
    {
      $ml_billing_amount = $ml_cycle_number = $ml_cycle_period = $ml_billing_limit = $ml_trial_amount = $ml_trial_limit = 0;
    }
	elseif ( $ml_custom_trial != "yes" )
    {
      $ml_trial_amount = $ml_trial_limit = 0;
    }
	
	if($ml_expiration != "yes")
	{
		$ml_expiration_number = $ml_expiration_period = 0;
	}

    if($saveid > 0)
    {
      $sqlQuery = " UPDATE {$wpdb->pmpro_membership_levels}
                    SET name = '$ml_name',
                      description = '$ml_description',
					  initial_payment = '$ml_initial_payment',
					  billing_amount = '$ml_billing_amount',
                      cycle_number = '$ml_cycle_number',
                      cycle_period = '$ml_cycle_period',
                      billing_limit = '$ml_billing_limit',
                      trial_amount = '$ml_trial_amount',
                      trial_limit = '$ml_trial_limit',                    
					  expiration_number = '$ml_expiration_number',
                      expiration_period = '$ml_expiration_period',
					  allow_signups = '$ml_allow_signups'
                    WHERE id = '$saveid' LIMIT 1;";	 
      $wpdb->query($sqlQuery);
      pmpro_updateMembershipCategories( $saveid, $ml_categories );
      if(!mysql_errno())
      {
        wp_redirect(home_url("/wp-admin/admin.php?page=pmpro-membershiplevels&msg=2"));
		//header("Location: /wp-admin/admin.php?page=pmpro-membershiplevels&msg=2");
        exit(0);
      }
      else
      {     
	    wp_redirect(home_url("/wp-admin/admin.php?page=pmpro-membershiplevels&msg=-2"));
		//header("Location:/wp-admin/admin.php?page=pmpro-membershiplevels&msg=-2");
        exit(0);
      }
    }
    else
    {
      $sqlQuery = " INSERT INTO {$wpdb->pmpro_membership_levels}
                    ( name, description, initial_payment, billing_amount, cycle_number, cycle_period, billing_limit, trial_amount, trial_limit, expiration_number, expiration_period, allow_signups)
                    VALUES
                    ( '$ml_name', '$ml_description', '$ml_initial_payment', '$ml_billing_amount', '$ml_cycle_number', '$ml_cycle_period', '$ml_billing_limit', '$ml_trial_amount', '$ml_trial_limit', '$ml_expiration_number', '$ml_expiration_period', '$ml_allow_signups' )";
	  $wpdb->query($sqlQuery);
      if(!mysql_errno())
      {
        pmpro_updateMembershipCategories( $wpdb->insert_id, $ml_categories );
        wp_redirect(home_url("/wp-admin/admin.php?page=pmpro-membershiplevels&msg=1"));
		//header("Location:/wp-admin/admin.php?page=pmpro-membershiplevels&msg=1");
        exit(0);
      }
      else
      {
        wp_redirect(home_url("/wp-admin/admin.php?page=pmpro-membershiplevels&msg=-1"));
		//header("Location:/wp-admin/admin.php?page=pmpro-membershiplevels&msg=-1");
        exit(0);
      }
    }
  }
  elseif($action == "save_user_membership")
  {
    if(pmpro_changeMembershipLevel( $_REQUEST['level'] ) === true )
    {
      wp_redirect(pmpro_url("account", "?msg=1"));
	  //header("Location:/subscription/?msg=1");
      exit(0);
    }
    else
    {
      wp_redirect(pmpro_url("account", "?msg=-1"));
	  //header("Location:/subscription/&msg=-1");
      exit(0);
    }
  }
  elseif($action == "delete_membership_level")
  {
	  if ( !current_user_can('manage_options') ) { wp_die("You do not have permission to do this."); }
	  global $wpdb;
	  
	  $ml_id = $_REQUEST['deleteid'];
	  
		if($ml_id > 0)
		{	  
			//remove any categories from the ml
			$sqlQuery = "DELETE FROM $wpdb->pmpro_memberships_categories WHERE membership_id = '$ml_id'";	  			
			$r1 = $wpdb->query($sqlQuery);
							
			//cancel any subscriptions to the ml
			$r2 = true;
			$user_ids = $wpdb->get_col("SELECT user_id FROM $wpdb->pmpro_memberships_users WHERE membership_id = '$ml_id'");			
			foreach($user_ids as $user_id)
			{
				//change there membership level to none. that will handle the cancel
				if(pmpro_changeMembershipLevel(0, $user_id))
				{
					//okay
				}
				else
				{
					//couldn't delete the subscription
					//we should probably notify the admin	
					$pmproemail = new PMProEmail();			
					$pmproemail->data = array("body"=>"<p>There was an error canceling the subscription for user with ID=" . $user_id . ". You will want to check your payment gateway to see if their subscription is still active.</p>");
					$last_order = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_membership_orders WHERE user_id = '" . $user_id . "' ORDER BY timestamp DESC LIMIT 1");
					if($last_order)
						$pmproemail->data["body"] .= "<p>Last Invoice:<br />" . nl2br(var_export($last_order, true)) . "</p>";
					$pmproemail->sendEmail(get_bloginfo("admin_email"));	

					$r2 = false;
				}	
			}					
			
			//delete the ml
			$sqlQuery = "DELETE FROM $wpdb->pmpro_membership_levels WHERE id = '$ml_id' LIMIT 1";	  			
			$r3 = $wpdb->query($sqlQuery);
		  		  	
			if($r1 !== FALSE && $r2 !== FALSE && $r3 !== FALSE)
			{
				wp_redirect(home_url("/wp-admin/admin.php?page=pmpro-membershiplevels&msg=3"));
				//header("Location:/wp-admin/admin.php?page=pmpro-membershiplevels&msg=3");
				exit(0);
			}
			else
			{
				wp_redirect(home_url("/wp-admin/admin.php?page=pmpro-membershiplevels&msg=-3"));
				//header("Location:/wp-admin/admin.php?page=pmpro-membershiplevels&msg=-3");
				exit(0);
			}
		}
	  
		wp_redirect(home_url("/wp-admin/admin.php?page=pmpro-membershiplevels&msg=-3"));
		//header("Location:/wp-admin/admin.php?page=pmpro-membershiplevels&msg=-3");
		exit(0);
  }  
?>