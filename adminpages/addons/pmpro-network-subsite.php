<?php
/*
	Addon: PMPro Network
	Slug: pmpro-network-subsite
*/
pmpro_add_addon('github', array(
		'title' => 'PMPro Network Subsite Helper',
		'version' => '.2',
		'widget' => 'pmpro_addon_pmpro_network_subsite_widget',
		'enabled' => function_exists('pmpron_subsite_activated_plugin')
	)
);

function pmpro_addon_pmpro_network_subsite_widget($addon)
{
?>
<img class="addon-thumb" src="<?php echo PMPRO_URL?>/adminpages/addons/images/pmpro-network.gif" />
<div class="info">							
	<p>Have network subsites use membership data from a "main" site to handle access restrictions.</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a href="https://github.com/strangerstudios/pmpro-network-subsite" class="button">Enabled</a>
		<?php } elseif(file_exists(dirname(__FILE__) . "/../../../pmpro-network-subsite/pmpro-network-subsite.php")) { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=pmpro-network-subsite/pmpro-network-subsite.php'), 'activate-plugin_pmpro-network-subsite/pmpro-network-subsite.php')?>" class="button button-primary">Activate</a>
		<?php } else { ?>
			<a href="http://www.paidmembershipspro.com/wp-content/uploads/plugins/pmpro-network-subsite.zip" class="button button-primary">Download</a>
		<?php } ?>
	</div>						
</div> <!-- end info -->
<?php
}
