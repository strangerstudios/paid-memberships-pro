<?php
/*
	Addon: PMPro Register Helper
	Slug: pmpro-register-helper
*/
pmpro_add_addon('github', array(
		'title' => 'PMPro Register Helper',
		'version' => '.5.2',
		'widget' => 'pmpro_addon_pmpro_register_helper_widget',
		'enabled' => class_exists('PMProRH_Field')
	)
);

function pmpro_addon_pmpro_register_helper_widget($addon)
{
?>
<img class="addon-thumb" src="<?php echo PMPRO_URL?>/adminpages/addons/images/pmpro-register-helper.gif" />
<div class="info">							
	<p>Add additional meta fields to your PMPro checkout page and/or "Your Profile" pages. Support for text, select, multi-select, textarea, hidden, and custom HTML. Loop into existing checkout/profile field sections or add new ones.</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a href="https://github.com/strangerstudios/pmpro-register-helper/blob/master/readme.txt" class="button">Enabled</a>
		<?php } elseif(file_exists(dirname(__FILE__) . "/../../../pmpro-register-helper/pmpro-register-helper.php")) { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=pmpro-register-helper/pmpro-register-helper.php'), 'activate-plugin_pmpro-register-helper/pmpro-register-helper.php')?>" class="button button-primary">Activate</a>
		<?php } else { ?>
			<a href="http://www.paidmembershipspro.com/wp-content/uploads/plugins/pmpro-register-helper.zip" class="button button-primary">Download</a>
		<?php } ?>
	</div>						
</div> <!-- end info -->
<?php
}
