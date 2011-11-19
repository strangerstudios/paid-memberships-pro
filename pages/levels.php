<?php 
global $wpdb, $pmpro_msg, $pmpro_msgt, $pmpro_levels, $current_user, $pmpro_currency_symbol;
if($pmpro_msg)
{
?>
<div class="message <?php echo $pmpro_msgt?>"><?php echo $pmpro_msg?></div>
<?php
}
?>

<table id="pmpro_levels_table" class="pmpro_checkout top1em" width="100%" cellpadding="0" cellspacing="0" border="0">
<thead>
  <tr>
	<th>Level</th>
	<th>Initial Payment</th>
	<th>Subscription Pricing</th>
	<th>Trial Period/Duration</th>
	<th>&nbsp;</th>
  </tr>
</thead>
<tbody>
	<?php	
	foreach($pmpro_levels as $level)
	{
	  if(isset($current_user->membership_level->ID))
		  $current_level = ($current_user->membership_level->ID == $level->id);
	  else
	  	  $current_level = false;
	?>
	<tr valign="top" class="<?php if($count++ % 2 == 0) { ?>odd<?php } ?><?php if($current_level == $level) { ?> active<?php } ?>">
		<td><?php echo $current_level ? "<strong>{$level->name}</strong>" : $level->name?></td>
		<td>
			<?php if(pmpro_isLevelFree($level)) { ?>
				<strong>Free</strong>
			<?php } else { ?>
				<?php echo $pmpro_currency_symbol?><?php echo $level->initial_payment?>
			<?php } ?>
		</td>
		<td>
		<?php if(pmpro_isLevelFree($level)) { ?>
			<strong>Free</strong>
		<?php } elseif(pmpro_isLevelRecurring($level)) { ?>
			<strong><?php echo $pmpro_currency_symbol?><?php echo $level->billing_amount?></strong>
			<?php if($level->cycle_number == '1') { ?>
				per <?php echo sornot($level->cycle_period,$level->cycle_number)?>
			<?php } else { ?>
				every <?php echo $level->cycle_number.' '.sornot($level->cycle_period,$level->cycle_number)?>
			<?php } ?>
		<?php } else { ?>
			N/A
		<?php } ?>
		</td>		
		<td>
		<?php
		  if (pmpro_isLevelTrial($level)) 
		  {			
			?>
				<p><?php if($level->trial_amount == '0.00') { ?><strong>Free</strong><?php } else { ?>$<?php echo $level->trial_amount?><?php } ?> for the next <?php echo $level->trial_limit.' ' .sornot("payment",$level->trial_limit)?>.</p>
			<?php
		  }		  
		  
		  if($level->billing_limit > 0 && $level->initial_payment > 0) 
		  {		
			?>
				<p><strong><?php echo ($level->billing_limit+1).' '.sornot("payment",($level->billing_limit+1))?></strong> total.</p>
			<?php
		  }
		  elseif($level->billing_limit)
		  {
		   ?>
				<p><strong><?php echo $level->billing_limit.' '.sornot("payment",$level->billing_limit)?></strong> total.</p>
		   <?php
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
			<a href="<?php echo pmpro_url("checkout", "?level=" . $level->id, "https")?>">I&nbsp;want&nbsp;<?php echo $level->name?>!</a>               
		<?php } elseif ( !$current_level ) { ?>                	
			<a href="<?php echo pmpro_url("checkout", "?level=" . $level->id, "https")?>">I&nbsp;want&nbsp;<?php echo $level->name?>!</a>       			
		<?php } elseif($current_level) { ?>      
			<a href="<?php echo pmpro_url("account")?>">Your Level</a>
		<?php } ?>
		</td>
	</tr>
	<?php
	}
	?>
</tbody>
<tfoot>
  <tr>
  	<td colspan="5" align="center">
		<small>-- 
		<?php if(!empty($current_user->membership_level->ID)) { ?>
			<a href="<?php echo pmpro_url("account")?>">return to your membership account</a>
		<?php } else { ?>
			<a href="<?php echo home_url()?>">return to the home page</a>
		<?php } ?>
		--</small>
	</td>
  </tr>
</tfoot>
</table>
