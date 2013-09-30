<?php
	global $isapage;
	$isapage = true;		
	
	//in case the file is loaded directly
	if(!defined("WP_USE_THEMES"))
	{
		define('WP_USE_THEMES', false);
		require_once(dirname(__FILE__) . '/../../../../wp-load.php');
	}
	
	//vars
	global $wpdb;
	if(!empty($_REQUEST['code']))
	{
		$discount_code = preg_replace("/[^A-Za-z0-9\-]/", "", $_REQUEST['code']);
		$discount_code_id = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_discount_codes WHERE code = '" . $discount_code . "' LIMIT 1");
	}
	else
	{
		$discount_code = "";
		$discount_code_id = "";
	}
	
	if(!empty($_REQUEST['level']))
		$level_id = (int)$_REQUEST['level'];
	else
		$level_id = NULL;
		
	if(!empty($_REQUEST['msgfield']))
		$msgfield = preg_replace("/[^A-Za-z0-9\_\-]/", "", $_REQUEST['msgfield']);
	else
		$msgfield = NULL;	
	
	//check that the code is valid
	$codecheck = pmpro_checkDiscountCode($discount_code, $level_id, true);
	if($codecheck[0] == false)
	{
		//uh oh. show code error
		echo pmpro_no_quotes($codecheck[1]);
		?>
		<script>			
			jQuery('#<?php echo $msgfield?>').show();
			jQuery('#<?php echo $msgfield?>').removeClass('pmpro_success');
			jQuery('#<?php echo $msgfield?>').addClass('pmpro_error');
			jQuery('#<?php echo $msgfield?>').addClass('pmpro_discount_code_msg');
		</script>
		<?php
		
		exit(0);
	}			
	
	//okay, send back new price info
	$sqlQuery = "SELECT l.id, cl.*, l.name, l.description, l.allow_signups FROM $wpdb->pmpro_discount_codes_levels cl LEFT JOIN $wpdb->pmpro_membership_levels l ON cl.level_id = l.id LEFT JOIN $wpdb->pmpro_discount_codes dc ON dc.id = cl.code_id WHERE dc.code = '" . $discount_code . "' AND cl.level_id = '" . $level_id . "' LIMIT 1";			
	$code_level = $wpdb->get_row($sqlQuery);
	
	//if the discount code doesn't adjust the level, let's just get the straight level
	if(empty($code_level))
		$code_level = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_membership_levels WHERE id = '" . $level_id . "' LIMIT 1");

	//filter adjustments to the level
	$code_level = apply_filters("pmpro_discount_code_level", $code_level, $discount_code_id);
	?>
	The discount code has been applied to your order.
	<script>		
		var code_level = <?php echo json_encode($code_level); ?>;				
		
		jQuery('#<?php echo $msgfield?>').show();
		jQuery('#<?php echo $msgfield?>').removeClass('pmpro_error');
		jQuery('#<?php echo $msgfield?>').addClass('pmpro_success');
		jQuery('#<?php echo $msgfield?>').addClass('pmpro_discount_code_msg');
		
		jQuery('#other_discount_code_tr').hide();
		jQuery('#other_discount_code_p').html('<a id="other_discount_code_a" href="javascript:void(0);"><?php _e('Click here to change your discount code', 'pmpro');?></a>.');
		jQuery('#other_discount_code_p').show();
		
		jQuery('#other_discount_code_a').click(function() {
			jQuery('#other_discount_code_tr').show();
			jQuery('#other_discount_code_p').hide();			
		});
		
		jQuery('#pmpro_level_cost').html('<?php printf(__('The <strong>%s</strong> code has been applied to your order.', 'pmpro'), $discount_code);?><?php echo pmpro_no_quotes(pmpro_getLevelCost($code_level), array('"', "'", "\n", "\r"))?>');
		
		<?php
			//tell gateway javascripts whether or not to fire (e.g. no Stripe on free levels)
			if(pmpro_isLevelFree($code_level))
			{
			?>
				pmpro_require_billing = false;
			<?php
			}			
			else
			{
			?>
				pmpro_require_billing = true;
			<?php
			}
			
			//hide/show billing
			if(pmpro_isLevelFree($code_level) || pmpro_getOption("gateway") == "paypalexpress" || pmpro_getOption("gateway") == "paypalstandard")
			{				
				?>
				jQuery('#pmpro_billing_address_fields').hide();
				jQuery('#pmpro_payment_information_fields').hide();			
				<?php
			}
			else			
			{
				?>			
				jQuery('#pmpro_billing_address_fields').show();
				jQuery('#pmpro_payment_information_fields').show();			
				<?php
			}
			
			//hide/show paypal button
			if(pmpro_getOption("gateway") == "paypalexpress" || pmpro_getOption("gateway") == "paypalstandard")			
			{
				if(pmpro_isLevelFree($code_level))
				{
					?>					
					jQuery('#pmpro_paypalexpress_checkout').hide();
					jQuery('#pmpro_submit_span').show();
					<?php
				}
				else
				{
					?>					
					jQuery('#pmpro_submit_span').hide();
					jQuery('#pmpro_paypalexpress_checkout').show();				
					<?php
				}
			}
		?>
	</script>
