<?php
/*
	Addon: PMPro Addon Packages
	Slug: pmpro-addon-packages
*/
pmpro_add_addon('github', array(
		'title' => 'PMPro Addon Packages',
		'version' => '.1.3',
		'widget' => 'pmpro_addon_pmpro_addon_packages_widget',
		'enabled' => function_exists('pmproap_post_meta')
	)
);

function pmpro_addon_pmpro_addon_packages_widget($addon)
{
?>
<div class="info">							
	<p>Sell access to individual pages or posts for a flat fee. This is a workaround if you would like to allow multiple membership levels per user.</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a href="https://github.com/strangerstudios/pmpro-addon-packages/blob/master/readme.txt" class="button">Enabled</a>
		<?php } elseif(file_exists(dirname(__FILE__) . "/../../../pmpro-addon-packages/pmpro-addon-packages.php")) { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=pmpro-addon-packages/pmpro-addon-packages.php'), 'activate-plugin_pmpro-addon-packages/pmpro-addon-packages.php')?>" class="button button-primary">Activate</a>
		<?php } else { ?>
			<a href="https://github.com/strangerstudios/pmpro-addon-packages/archive/master.zip" class="button button-primary">Download</a>
		<?php } ?>
	</div>						
</div> <!-- end info -->
<?php
}
