<?php
/*
	Addon: PMPro Infusionsoft Integration
	Slug: pmpro-infusionsoft
*/
pmpro_add_addon('thirdparty', array(
		'title' => 'PMPro Infusionsoft Integration',
		'version' => '1.2',
		'widget' => 'pmpro_addon_pmpro_infusionsoft_widget',
		'enabled' => function_exists('pmprois_init')
	)
);

function pmpro_addon_pmpro_infusionsoft_widget($addon)
{
?>
<img class="addon-thumb" src="<?php echo PMPRO_URL?>/adminpages/addons/images/pmpro-infusionsoft.jpg" />
<div class="info">							
	<p>Integrate with Infusionsoft. Add members to email lists (groups, tags) based on their membership level. (Note: works without PMPro as well.)</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a href="<?php echo admin_url("plugins.php");?>" class="button">Enabled</a>
		<?php } elseif(file_exists(dirname(__FILE__) . "/../../../pmpro-infusionsoft/pmpro-infusionsoft.php")) { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=pmpro-infusionsoft/pmpro-infusionsoft.php'), 'activate-plugin_pmpro-infusionsoft/pmpro-infusionsoft.php')?>" class="button button-primary">Activate</a>
		<?php } else { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=pmpro-infusionsoft'), 'install-plugin_pmpro-infusionsoft'); ?>" class="button button-primary">Download</a>
		<?php } ?>				
	</div>						
</div> <!-- end info -->
<?php
}
