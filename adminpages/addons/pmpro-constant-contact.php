<?php
/*
	Addon: PMPro Constant Contact  Integration
	Slug: pmpro-constant-contact
*/
pmpro_add_addon('thirdparty', array(
		'title' => 'PMPro Constant Contact Integration',
		'version' => '1.0',
		'widget' => 'pmpro_addon_pmpro_constant_contact_widget',
		'enabled' => function_exists('pmprocc_init')
	)
);

function pmpro_addon_pmpro_constant_contact_widget($addon)
{
?>
<img class="addon-thumb" src="<?php echo PMPRO_URL?>/adminpages/addons/images/pmpro-constant-contact.gif" />
<div class="info">							
	<p>Integrate User Registrations with Constant Contact . Adds members to lists based on their membership level. (Note: works without PMPro as well.)</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a href="<?php echo admin_url("plugins.php");?>" class="button">Enabled</a>
		<?php } elseif(file_exists(dirname(__FILE__) . "/../../../pmpro-constant-contact/pmpro-constant-contact.php")) { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=pmpro-constant-contact/pmpro-constant-contact.php'), 'activate-plugin_pmpro-constant-contact/pmpro-constant-contact.php')?>" class="button button-primary">Activate</a>
		<?php } else { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=pmpro-constant-contact'), 'install-plugin_pmpro-constant-contact'); ?>" class="button button-primary">Download</a>
		<?php } ?>
	</div>						
</div> <!-- end info -->
<?php
}
