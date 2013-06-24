<?php
/*
	Addon: PMPro bbPress
	Slug: pmpro-bbpress
*/
pmpro_add_addon('gists', array(
		'title' => 'PMPro bbPress',
		'version' => '.1',
		'widget' => 'pmpro_addon_pmpro_bbpress_widget',
		'enabled' => function_exists('pmpro_check_forum')
	)
);

function pmpro_addon_pmpro_bbpress_widget($addon)
{
?>
<div class="info">
	<p>Locking down bbPress Forums by Membership Level and Forum ID.</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a target="_blank" href="https://gist.github.com/strangerstudios/1633637" class="button">Enabled</a>
		<?php } else { ?>
			<a target="_blank" href="https://gist.github.com/strangerstudios/1633637" class="button button-primary">View Gist</a>
		<?php } ?>
	</div>						
</div> <!-- end info -->
<?php
}
