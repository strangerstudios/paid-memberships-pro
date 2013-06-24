<?php
/*
	Addon: PMPro Disable PMPro Emails
	Slug: pmpro-disable-emails
*/
pmpro_add_addon('gists', array(
		'title' => 'PMPro Disable Emails',
		'version' => '.1',
		'widget' => 'pmpro_addon_pmpro_disable_emails_widget',
		'enabled' => function_exists('dae_pmpro_email_recipient')
	)
);

function pmpro_addon_pmpro_disable_emails_widget($addon)
{
?>
<div class="info">
	<p>Disable all or specific emails sent by the PMPro plugin.</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a target="_blank" href="https://gist.github.com/strangerstudios/3667545" class="button">Enabled</a>
		<?php } else { ?>
			<a target="_blank" href="https://gist.github.com/strangerstudios/3667545" class="button button-primary">View Gist</a>
		<?php } ?>
	</div>						
</div> <!-- end info -->
<?php
}
