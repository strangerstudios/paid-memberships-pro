<?php
/*
	Addon: PMPro Network
	Slug: pmpro-network
*/
pmpro_add_addon('github', array(
		'title' => 'PMPro Network',
		'version' => '.3.1',
		'widget' => 'pmpro_addon_pmpro_network_widget',
		'enabled' => function_exists('pmpron_new_blogs_settings')
	)
);

function pmpro_addon_pmpro_network_widget($addon)
{
?>
<img class="addon-thumb" src="<?php echo PMPRO_URL?>/adminpages/addons/images/pmpro-network.gif" />
<div class="info">							
	<p>Allow users to checkout for a membership to create a site on your WordPress multisite network.</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a href="https://github.com/strangerstudios/pmpro-network/blob/master/readme.txt" class="button">Enabled</a>
		<?php } elseif(file_exists(dirname(__FILE__) . "/../../../pmpro-network/pmpro-network.php")) { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=pmpro-network/pmpro-network.php'), 'activate-plugin_pmpro-network/pmpro-network.php')?>" class="button button-primary">Activate</a>
		<?php } else { ?>
			<a href="http://www.paidmembershipspro.com/wp-content/uploads/plugins/pmpro-network.zip" class="button button-primary">Download</a>
		<?php } ?>
	</div>						
</div> <!-- end info -->
<?php
}
