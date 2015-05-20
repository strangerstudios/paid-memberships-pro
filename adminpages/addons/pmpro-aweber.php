<?php
/*
	Addon: PMPro AWeber Integration
	Slug: pmpro-aweber
*/
pmpro_add_addon('thirdparty', array(
		'title' => 'PMPro AWeber Integration',
		'version' => '1.0',
		'widget' => 'pmpro_addon_pmpro_aweber_widget',
		'enabled' => function_exists('pmproaw_init')
	)
);

function pmpro_addon_pmpro_aweber_widget($addon)
{
?>
<img class="addon-thumb" src="<?php echo PMPRO_URL?>/adminpages/addons/images/pmpro-aweber.gif" />
<div class="info">							
	<p>Integrate User Registrations with AWeber. Adds members to lists based on their membership level. (Note: works without PMPro as well.)</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a href="<?php echo admin_url("plugins.php");?>" class="button">Enabled</a>
		<?php } elseif(file_exists(dirname(__FILE__) . "/../../../pmpro-aweber/pmpro-aweber.php")) { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=pmpro-aweber/pmpro-aweber.php'), 'activate-plugin_pmpro-aweber/pmpro-aweber.php')?>" class="button button-primary">Activate</a>
		<?php } else { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=pmpro-aweber'), 'install-plugin_pmpro-aweber'); ?>" class="button button-primary">Download</a>
		<?php } ?>
	</div>						
</div> <!-- end info -->
<?php
}
