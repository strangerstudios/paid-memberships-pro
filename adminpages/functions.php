<?php
/*
	Checks if PMPro settings are complete or if there are any errors.
*/
function pmpro_checkLevelForStripeCompatibility($level = NULL)
{
	$gateway = pmpro_getOption("gateway");
	if($gateway == "stripe")
	{
		global $wpdb;
		
		//check ALL the levels
		if(empty($level))
		{
			$sqlQuery = "SELECT * FROM $wpdb->pmpro_membership_levels ORDER BY id ASC";		
			$levels = $wpdb->get_results($sqlQuery, OBJECT);
			if(!empty($levels))
			{
				foreach($levels as $level)
				{
					/*
						Stripe currently does not support:
						* Trial Amounts > 0.
						* Daily or Weekly billing periods.						
						* Billing Limits.										
					*/
					if($level->trial_amount > 0 ||
					   ($level->cycle_number > 0 && ($level->cycle_period == "Day" || $level->cycle_period == "Week")) ||
					   $level->billing_limit > 0)
					{
						return false;
					}
				}
			}
		}
		else
		{
			//need to look it up?
			if(is_numeric($level))
				$level = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_membership_levels WHERE id = '" . esc_sql($level) . "' LIMIT 1");
			
			//check this level
			if($level->trial_amount > 0 ||
			   ($level->cycle_number > 0 && ($level->cycle_period == "Day" || $level->cycle_period == "Week")) ||
			   $level->billing_limit > 0)
			{
				return false;
			}
		}
	}
	
	return true;
}

/*
	Checks if PMPro settings are complete or if there are any errors.
*/
function pmpro_checkLevelForPayflowCompatibility($level = NULL)
{
	$gateway = pmpro_getOption("gateway");
	if($gateway == "payflowpro")
	{
		global $wpdb;
		
		//check ALL the levels
		if(empty($level))
		{
			$sqlQuery = "SELECT * FROM $wpdb->pmpro_membership_levels ORDER BY id ASC";		
			$levels = $wpdb->get_results($sqlQuery, OBJECT);
			if(!empty($levels))
			{
				foreach($levels as $level)
				{
					/*
						Payflow currently does not support:
						* Trial Amounts > 0.
						* Daily billing periods.												
					*/
										
					if($level->trial_amount > 0 ||
					    $level->cycle_number > 1 ||
						($level->cycle_number == 1 && $level->cycle_period == "Day"))
					{
						return false;
					}
				}
			}
		}
		else
		{			
			//need to look it up?
			if(is_numeric($level))
				$level = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_membership_levels WHERE id = '" . esc_sql($level) . "' LIMIT 1");
						
			//check this level
			if($level->trial_amount > 0 ||
			   $level->cycle_number > 1 ||
			   ($level->cycle_number == 1 && $level->cycle_period == "Day"))
			{
				return false;
			}
		}
	}
	
	return true;
}

/*
	Checks if PMPro settings are complete or if there are any errors.
*/
function pmpro_checkLevelForBraintreeCompatibility($level = NULL)
{
	$gateway = pmpro_getOption("gateway");
	if($gateway == "braintree")
	{
		global $wpdb;
		
		//check ALL the levels
		if(empty($level))
		{
			$sqlQuery = "SELECT * FROM $wpdb->pmpro_membership_levels ORDER BY id ASC";		
			$levels = $wpdb->get_results($sqlQuery, OBJECT);
			if(!empty($levels))
			{
				foreach($levels as $level)
				{
					/*
						Braintree currently does not support:
						* Trial Amounts > 0.
						* Daily or Weekly billing periods.												
					*/
					if($level->trial_amount > 0 ||
					   ($level->cycle_number > 0 && ($level->cycle_period == "Day" || $level->cycle_period == "Week")))
					{
						return false;
					}
				}
			}
		}
		else
		{
			//need to look it up?
			if(is_numeric($level))
				$level = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_membership_levels WHERE id = '" . esc_sql($level) . "' LIMIT 1");
			
			//check this level
			if($level->trial_amount > 0 ||
			   ($level->cycle_number > 0 && ($level->cycle_period == "Day" || $level->cycle_period == "Week")))
			{
				return false;
			}
		}
	}
	
	return true;
}

