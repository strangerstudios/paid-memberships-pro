<?php
/*
	Addon: PMPro Require Name and Address for Free Level
	Slug: pmpro-freerequire
*/
pmpro_add_addon('gists', array(
		'title' => 'PMPro Require Name/Address for Free Level',
		'version' => '.1',
		'widget' => 'pmpro_addon_pmpro_freerequire_widget',
		'enabled' => function_exists('my_pmpro_checkout_boxes_require_address')
	)
);

function pmpro_addon_pmpro_freerequire_widget($addon)
{
?>
<div class="info">
	<p>Require name/address for free Membership Level checkout.</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a target="_blank" href="https://gist.github.com/strangerstudios/5716249" class="button">Enabled</a>
		<?php } else { ?>
			<a target="_blank" href="https://gist.github.com/strangerstudios/5716249" class="button button-primary">View Gist</a>
		<?php } ?>
	</div>						
</div> <!-- end info -->
<?php
}
