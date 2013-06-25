<?php
/*
	Addon: PMPro Stripe Lite
	Slug: pmpro-stripe-lite
*/
pmpro_add_addon('github', array(
		'title' => 'PMPro Stripe Lite',
		'version' => '.1.1',
		'widget' => 'pmpro_addon_pmpro_stripe_lite_widget',
		'enabled' => function_exists('pmprosl_pmpro_pages_shortcode_checkout')
	)
);

function pmpro_addon_pmpro_stripe_lite_widget($addon)
{
?>
<div class="info">							
	<p>Remove billing fields (not required by Stripe) from the checkout page when using the Stripe payment gateway with PMPro.</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a href="https://github.com/strangerstudios/pmpro-stripe-lite/blob/master/readme.txt" class="button">Enabled</a>
		<?php } elseif(file_exists(dirname(__FILE__) . "/../../../pmpro-stripe-lite/pmpro-stripe-lite.php")) { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=pmpro-stripe-lite/pmpro-stripe-lite.php'), 'activate-plugin_pmpro-stripe-lite/pmpro-stripe-lite.php')?>" class="button button-primary">Activate</a>
		<?php } else { ?>
			<a href="http://www.paidmembershipspro.com/wp-content/uploads/plugins/pmpro-stripe-lite.zip" class="button button-primary">Download</a>
		<?php } ?>
	</div>						
</div> <!-- end info -->
<?php
}
