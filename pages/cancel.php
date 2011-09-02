<?php 
	global $pmpro_msg, $pmpro_msgt, $pmpro_confirm;

	if($pmpro_msg) 
	{
?>
	<div class="pmpro_message <?=$pmpro_msgt?>"><?=$pmpro_msg?></div>
<?php
	}
?>

<?php if(!$pmpro_confirm) { ?>           

<p>Are you sure you want to cancel your membership?</p>

<p>
	<a class="yeslink" href="<?=pmpro_url("cancel", "?confirm=true")?>">Yes, cancel my account</a>
	|
	<a class="nolink" href="<?=pmpro_url("account")?>">No, keep my account</a>
</p>
<?php } else { ?>
	<p>Click here to <a href="<?=get_home_url()?>">go to the home page</a>.</p>
<?php } ?>