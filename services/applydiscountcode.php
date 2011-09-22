<?php
	global $isapage;
	$isapage = true;
		
	//wp includes	
	define('WP_USE_THEMES', false);
	require('../../../../wp-load.php');	
	
	//vars
	$discount_code = preg_replace("/[^A-Za-z0-9]/", "", $_REQUEST['code']);
	$level_id = (int)$_REQUEST['level'];
	$msgfield = preg_replace("/[^A-Za-z0-9\_\-]/", "", $_REQUEST['msgfield']);
	
	//check that the code is valid
	$codecheck = pmpro_checkDiscountCode($discount_code, $level_id, true);
	if($codecheck[0] == false)
	{
		//uh oh. show code error
		echo pmpro_no_quotes($codecheck[1]);
		?>
		<script>			
			jQuery('#<?=$msgfield?>').show();
			jQuery('#<?=$msgfield?>').removeClass('pmpro_success');
			jQuery('#<?=$msgfield?>').addClass('pmpro_error');
			jQuery('#<?=$msgfield?>').addClass('pmpro_discount_code_msg');
		</script>
		<?php
		
		exit(0);
	}			
	
	//okay, send back new price info
	$sqlQuery = "SELECT l.id, cl.*, l.name, l.description, l.allow_signups FROM $wpdb->pmpro_discount_codes_levels cl LEFT JOIN $wpdb->pmpro_membership_levels l ON cl.level_id = l.id LEFT JOIN $wpdb->pmpro_discount_codes dc ON dc.id = cl.code_id WHERE dc.code = '" . $discount_code . "' AND cl.level_id = '" . $level_id . "' LIMIT 1";			
	$code_level = $wpdb->get_row($sqlQuery);	
	?>
	The discount code has been applied to your order.
	<script>		
		jQuery('#<?=$msgfield?>').show();
		jQuery('#<?=$msgfield?>').removeClass('pmpro_error');
		jQuery('#<?=$msgfield?>').addClass('pmpro_success');
		jQuery('#<?=$msgfield?>').addClass('pmpro_discount_code_msg');
		
		jQuery('#other_discount_code_tr').hide();
		jQuery('#other_discount_code_p').html('<a id="other_discount_code_a" href="javascript:void(0);">Click here to change your discount code</a>.');
		jQuery('#other_discount_code_p').show();
		
		jQuery('#other_discount_code_a').click(function() {
			jQuery('#other_discount_code_tr').show();
			jQuery('#other_discount_code_p').hide();			
		});
		
		jQuery('#pmpro_level_cost').html('The <strong><?=$discount_code?></strong> code has been applied to your order. <?=pmpro_no_quotes(pmpro_getLevelCost($code_level), array('"', "'", "\n", "\r"))?>');
		
		<?php
			if(pmpro_isLevelFree($code_level) || pmpro_getOption("gateway") == "paypalexpress")
			{
				//hide billing
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
		?>
	</script>
	<?php
	
?>