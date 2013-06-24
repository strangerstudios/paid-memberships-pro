<?php
/*
	Addon: PMPro Expiration Date
	Slug: pmpro-expiration
*/
pmpro_add_addon('gists', array(
		'title' => 'PMPro Expiration Date',
		'version' => '.1',
		'widget' => 'pmpro_addon_pmpro_expiration_widget',
		'enabled' => function_exists('my_pmpro_checkout_level_specific_expiration')
	)
);

function pmpro_addon_pmpro_expiration_widget($addon)
{
?>
<div class="info">
	<p>Set a specific expiration date for a Membership Level.</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a target="_blank" href="https://gist.github.com/strangerstudios/5709300" class="button">Enabled</a>
		<?php } else { ?>
			<a target="_blank" href="https://gist.github.com/strangerstudios/5709300" class="button button-primary">View Gist</a>
		<?php } ?>
	</div>						
</div> <!-- end info -->
<?php
}
