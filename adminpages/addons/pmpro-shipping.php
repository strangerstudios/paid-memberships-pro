<?php
/*
	Addon: PMPro Shipping Add On
	Slug: pmpro-shipping
*/
pmpro_add_addon('github', array(
		'title' => 'PMPro Shipping Add On',
		'version' => '.2.3',
		'widget' => 'pmpro_addon_pmpro_shipping_widget',
		'enabled' => function_exists('pmproship_pmpro_checkout_boxes')
	)
);

function pmpro_addon_pmpro_shipping_widget($addon)
{
?>
<?php /* <img class="addon-thumb" src="<?php echo PMPRO_URL?>/adminpages/addons/images/pmpro-shipping.jpg" /> */ ?>
<div class="info">							
	<p>Adds shipping fields to the checkout page, confirmation page, confirmation emails, member's list and edit user profile pages.</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a href="https://github.com/strangerstudios/pmpro-shipping/" class="button">Enabled</a>
		<?php } elseif(file_exists(dirname(__FILE__) . "/../../../pmpro-shipping/pmpro-shipping.php")) { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=pmpro-shipping/pmpro-shipping.php'), 'activate-plugin_pmpro-shipping/pmpro-shipping.php')?>" class="button button-primary">Activate</a>
		<?php } else { ?>
			<a href="http://www.paidmembershipspro.com/wp-content/uploads/plugins/pmpro-shipping.zip" class="button button-primary">Download</a>
		<?php } ?>
	</div>						
</div> <!-- end info -->
<?php
}
