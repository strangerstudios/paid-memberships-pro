<?php
/*
	Addon: PMPro Require Code to Register
	Slug: pmpro-require-code-to-register
*/
pmpro_add_addon('gists', array(
		'title' => 'PMPro Require a Code to Register',
		'version' => '.1',
		'widget' => 'pmpro_addon_pmpro_require_code_to_register_widget',
		'enabled' => function_exists('my_pmpro_registration_checks_require_code_to_register')
	)
);

function pmpro_addon_pmpro_require_code_to_register_widget($addon)
{
?>
<div class="info">
	<p>Require a discount code to checkout for a specific level.</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a target="_blank" href="https://gist.github.com/strangerstudios/5573829" class="button">Enabled</a>
		<?php } else { ?>
			<a target="_blank" href="https://gist.github.com/strangerstudios/5573829" class="button button-primary">View Gist</a>
		<?php } ?>
	</div>						
</div> <!-- end info -->
<?php
}
