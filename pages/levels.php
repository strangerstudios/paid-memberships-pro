<?php 
global $wpdb, $pmpro_msg, $pmpro_msgt, $pmpro_levels, $current_user;
if($pmpro_msg)
{
?>
<div class="message <?=$pmpro_msgt?>"><?=$pmpro_msg?></div>
<?php
}
?>

<table class="pmpro_checkout top1em" width="100%" cellpadding="0" cellspacing="0" border="0">
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
	  $current_level = ($current_user->membership_level->ID == $level->id);
	?>
	<tr valign="top" class="<?php if($count++ % 2 == 0) { ?>odd<?php } ?><?php if($current_level == $level) { ?> active<?php } ?>">
		<td><?=$current_level ? "<strong>{$level->name}</strong>" : $level->name?></td>
		<td>
			<?php if(pmpro_isLevelFree($level)) { ?>
				<strong>Free</strong>
			<?php } else { ?>
				$<?=$level->initial_payment?>
			<?php } ?>
		</td>
		<td>
		<?php if(pmpro_isLevelFree($level)) { ?>
			<strong>Free</strong>
		<?php } elseif(pmpro_isLevelRecurring($level)) { ?>
			<strong>$<?=$level->billing_amount?></strong>
			<?php if($level->cycle_number == '1') { ?>
				per <?=sornot($level->cycle_period,$level->cycle_number)?>
			<?php } else { ?>
				every <?=$level->cycle_number.' '.sornot($level->cycle_period,$level->cycle_number)?>
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
				<p><?php if($level->trial_amount == '0.00') { ?><strong>Free</strong><?php } else { ?>$<?=$level->trial_amount?><?php } ?> for the first <?=$level->trial_limit.' ' .sornot("payment",$level->trial_limit)?>.</p>
			<?php
		  }		  
		  
		  if ($level->billing_limit > 0 ) 
		  {		
			?>
				<p>Payments end after <strong><?=$level->billing_limit.' '.sornot($level->cycle_period,$level->billing_limit)?></strong>.</p>
			<?php
		  }
		  
		  $expiration_text = pmpro_getLevelExpiration($level);
		  if($expiration_text)
		  {
		  ?>
			<p><?=$expiration_text?></p>
		  <?php
		  }
		?>
		</td>
		<td>
		<?php if(!$current_user->membership_level->ID) { ?>
			<a href="<?=pmpro_url("checkout", "?level=" . $level->id, "https")?>">I&nbsp;want&nbsp;<?=$level->name?>!</a>               
		<?php } elseif ( !$current_level ) { ?>                	
			<a href="<?=pmpro_url("checkout", "?level=" . $level->id, "https")?>">I&nbsp;want&nbsp;<?=$level->name?>!</a>       			
		<?php } elseif($current_level) { ?>      
			<a href="<?=pmpro_url("account")?>">Your Level</a>
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
		<?php if($current_user->membership_level->ID) { ?>
			<a href="<?=pmpro_url("account")?>">return to your membership account</a>
		<?php } else { ?>
			<a href="<?=home_url()?>">return to the home page</a>
		<?php } ?>
		--</small>
	</td>
  </tr>
</tfoot>
</table>
