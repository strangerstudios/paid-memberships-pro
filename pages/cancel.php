<?php 
	global $pmpro_msg, $pmpro_msgt, $pmpro_confirm;

	if($pmpro_msg) 
	{
?>
	<div class="pmpro_message <?php echo $pmpro_msgt?>"><?php echo $pmpro_msg?></div>
<?php
	}
?>

<?php if(!$pmpro_confirm) { ?>           

<p>Are you sure you want to cancel your membership?</p>

<p>
	<a class="yeslink" href="<?php echo pmpro_url("cancel", "?confirm=true")?>">Yes, cancel my account</a>
	|
	<a class="nolink" href="<?php echo pmpro_url("account")?>">No, keep my account</a>
</p>
<?php } else { ?>
	<p>Click here to <a href="<?php echo get_home_url()?>">go to the home page</a>.</p>
<?php } ?>