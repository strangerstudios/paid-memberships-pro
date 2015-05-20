<?php
/*
	Addon: PMPro Custom Level Cost Text
	Slug: pmpro-level-cost-text
*/
pmpro_add_addon('github', array(
		'title' => 'PMPro Level Cost Text',
		'version' => '.2',
		'widget' => 'pmpro_addon_pmpro_level_cost_text_widget',
		'enabled' => function_exists('pclct_pmpro_discount_code_after_level_settings')
	)
);

function pmpro_addon_pmpro_level_cost_text_widget($addon)
{
?>
<div class="info">							
	<p>Adds a "level cost text" field to PMPro Membership Levels and Discount Codes to allow you to override the automatically generated level cost text PMPro provides.</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a href="https://github.com/strangerstudios/pmpro-level-cost-text/blob/master/readme.txt" class="button">Enabled</a>
		<?php } elseif(file_exists(dirname(__FILE__) . "/../../../pmpro-level-cost-text/pmpro-level-cost-text.php")) { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=pmpro-level-cost-text/pmpro-level-cost-text.php'), 'activate-plugin_pmpro-level-cost-text/pmpro-level-cost-text.php')?>" class="button button-primary">Activate</a>
		<?php } else { ?>
			<a href="http://www.paidmembershipspro.com/wp-content/uploads/plugins/pmpro-level-cost-text.zip" class="button button-primary">Download</a>
		<?php } ?>
	</div>						
</div> <!-- end info -->
<?php
}
