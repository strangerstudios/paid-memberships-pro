<?php
/*
	Addon: PMPro Require Name and Address for Free Level
	Slug: pmpro-freerequire
*/
pmpro_add_addon('github', array(
		'title' => 'PMPro Require Name/Address for Free Level',
		'version' => '.2',
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
			<a href="https://github.com/strangerstudios/pmpro-address-for-free-levels" class="button">Enabled</a>
		<?php } elseif(file_exists(dirname(__FILE__) . "/../../../pmpro-address-for-free-levels/pmpro-address-for-free-levels.php")) { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=pmpro-address-for-free-levels/pmpro-address-for-free-levels.php'), 'activate-plugin_pmpro-address-for-free-levels/pmpro-address-for-free-levels.php')?>" class="button button-primary">Activate</a>
		<?php } else { ?>
			<a href="http://www.paidmembershipspro.com/wp-content/uploads/plugins/pmpro-address-for-free-levels.zip" class="button button-primary">Download</a>
		<?php } ?>				
	</div>						
</div> <!-- end info -->
<?php
}
