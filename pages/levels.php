<?php 
global $wpdb, $pmpro_msg, $pmpro_msgt, $pmpro_levels, $current_user, $pmpro_currency_symbol;
if($pmpro_msg)
{
?>
<div class="message <?php echo $pmpro_msgt?>"><?php echo $pmpro_msg?></div>
<?php
}
?>

<table id="pmpro_levels_table" class="pmpro_checkout top1em">
<thead>
  <tr>
	<th><?php _e('Level', 'pmpro');?></th>
	<th><?php _e('Initial Payment', 'pmpro');?></th>
	<th><?php _e('Subscription Information', 'pmpro');?></th>	
	<th>&nbsp;</th>
  </tr>
</thead>
<tbody>
	<?php	
	$count = 0;
	foreach($pmpro_levels as $level)
	{
	  if(isset($current_user->membership_level->ID))
		  $current_level = ($current_user->membership_level->ID == $level->id);
	  else
	  	  $current_level = false;
	?>
	<tr class="<?php if($count++ % 2 == 0) { ?>odd<?php } ?><?php if($current_level == $level) { ?> active<?php } ?>">
		<td><?php echo $current_level ? "<strong>{$level->name}</strong>" : $level->name?></td>
		<td>
			<?php if(pmpro_isLevelFree($level)) { ?>
				<strong><?php _e('Free', 'pmpro');?></strong>
			<?php } else { ?>
				<?php echo $pmpro_currency_symbol?><?php echo $level->initial_payment?>
			<?php } ?>
		</td>
		<td>
		<?php
			//recurring part
			if(pmpro_isLevelFree($level))
			{
				echo "<strong>" . __('Free', 'pmpro') . "</strong>";
			}
			elseif($level->billing_amount != '0.00')
			{
				if($level->billing_limit > 1)
				{			
					if($level->cycle_number == '1')
					{
						printf(__('%s per %s for %d more %s.', 'Recurring payment in cost text generation. E.g. $5 every month for 2 more payments.', 'pmpro'), $pmpro_currency_symbol . $level->billing_amount, pmpro_translate_billing_period($level->cycle_period), $level->billing_limit, pmpro_translate_billing_period($level->cycle_period, $level->billing_limit));					
					}				
					else
					{ 
						printf(__('%s every %d %s for %d more %s.', 'Recurring payment in cost text generation. E.g., $5 every 2 months for 2 more payments.', 'pmpro'), $pmpro_currency_symbol . $level->billing_amount, $level->cycle_number, pmpro_translate_billing_period($level->cycle_period, $level->cycle_number), $level->billing_limit, pmpro_translate_billing_period($level->cycle_period, $level->billing_limit));					
					}
				}
				elseif($level->billing_limit == 1)
				{
					printf(__('%s after %d %s.', 'Recurring payment in cost text generation. E.g. $5 after 2 months.', 'pmpro'), $pmpro_currency_symbol . $level->billing_amount, $level->cycle_number, pmpro_translate_billing_period($level->cycle_period, $level->cycle_number));									
				}
				else
				{
					if($level->cycle_number == '1')
					{
						printf(__('%s per %s.', 'Recurring payment in cost text generation. E.g. $5 every month.', 'pmpro'), $pmpro_currency_symbol . $level->billing_amount, pmpro_translate_billing_period($level->cycle_period));					
					}				
					else
					{ 
						printf(__('%s every %d %s.', 'Recurring payment in cost text generation. E.g., $5 every 2 months.', 'pmpro'), $pmpro_currency_symbol . $level->billing_amount, $level->cycle_number, pmpro_translate_billing_period($level->cycle_period, $level->cycle_number));					
					}			
				}
			}			
		
			//trial
			if(pmpro_isLevelTrial($level))
			{
				if($level->trial_amount == '0.00')
				{
					if($level->trial_limit == '1')
					{
						echo ' ' . _x('After your initial payment, your first payment is Free.', 'Trial payment in cost text generation.', 'pmpro');
					}
					else
					{
						printf(' ' . _x('After your initial payment, your first %d payments are Free.', 'Trial payment in cost text generation.', 'pmpro'), $level->trial_limit);
					}
				}
				else
				{
					if($level->trial_limit == '1')
					{
						printf(' ' . _x('After your initial payment, your first payment will cost %s.', 'Trial payment in cost text generation.', 'pmpro'), $pmpro_currency_symbol . $level->trial_amount);
					}
					else
					{
						printf(' ' . _x('After your initial payment, your first %d payments will cost %s.', 'Trial payment in cost text generation. E.g. ... first 2 payments will cost $5', 'pmpro'), $level->trial_limit, $pmpro_currency_symbol . $level->trial_amount);
					}
				}
			}
						  
			$expiration_text = pmpro_getLevelExpiration($level);
			if($expiration_text)
			{
			?>
				<p><?php echo $expiration_text?></p>
			<?php
			}
		?>
		</td>
		<td>
		<?php if(empty($current_user->membership_level->ID)) { ?>
			<a href="<?php echo pmpro_url("checkout", "?level=" . $level->id, "https")?>"><?php _e('Select', 'Choose a level from levels page', 'pmpro');?></a>               
		<?php } elseif ( !$current_level ) { ?>                	
			<a href="<?php echo pmpro_url("checkout", "?level=" . $level->id, "https")?>"><?php _e('Select', 'Choose a level from levels page', 'pmpro');?></a>       			
		<?php } elseif($current_level) { ?>      
			<a href="<?php echo pmpro_url("account")?>"><?php _e('Your&nbsp;Level', 'pmpro');?></a>
		<?php } ?>
		</td>
	</tr>
	<?php
	}
	?>
</tbody>
<tfoot>
  <tr>
  	<td colspan="5">
		<small>-- 
		<?php if(!empty($current_user->membership_level->ID)) { ?>
			<a href="<?php echo pmpro_url("account")?>"><?php _e('return to your membership account', 'pmpro');?></a>
		<?php } else { ?>
			<a href="<?php echo home_url()?>"><?php _e('return to the home page', 'pmpro');?></a>
		<?php } ?>
		--</small>
	</td>
  </tr>
</tfoot>
</table>
