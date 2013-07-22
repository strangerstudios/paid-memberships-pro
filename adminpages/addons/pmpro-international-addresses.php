<?php
/*
	Addon: PMPro International Addresses
	Slug: pmpro-international-addresses
*/
pmpro_add_addon('github', array(
		'title' => 'PMPro International Addresses',
		'version' => '.2.2',
		'widget' => 'pmpro_addon_pmpro_international_addresses_widget',
		'enabled' => function_exists('pmproia_pmpro_international_addresses')
	)
);

function pmpro_addon_pmpro_international_addresses_widget($addon)
{
?>
<?php /* <img class="addon-thumb" src="<?php echo PMPRO_URL?>/adminpages/addons/images/pmpro-international-addresses.jpg" /> */ ?>
<div class="info">							
	<p>Adds long form addresses to the PMPro checkout.</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a href="https://github.com/strangerstudios/pmpro-international-addresses/" class="button">Enabled</a>
		<?php } elseif(file_exists(dirname(__FILE__) . "/../../../pmpro-international-addresses/pmpro-international-addresses.php")) { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=pmpro-international-addresses/pmpro-international-addresses.php'), 'activate-plugin_pmpro-international-addresses/pmpro-international-addresses.php')?>" class="button button-primary">Activate</a>
		<?php } else { ?>
			<a href="http://www.paidmembershipspro.com/wp-content/uploads/plugins/pmpro-international-addresses.zip" class="button button-primary">Download</a>
		<?php } ?>
	</div>						
</div> <!-- end info -->
<?php
}
