<?php
/*
	Addon: PMPro Expiration Date
	Slug: pmpro-expiration
*/
pmpro_add_addon('github', array(
		'title' => 'PMPro Set Expiration Dates',
		'version' => '.1',
		'widget' => 'pmpro_addon_pmpro_expiration_widget',
		'enabled' => function_exists('my_pmpro_checkout_level_specific_expiration')
	)
);

function pmpro_addon_pmpro_expiration_widget($addon)
{
?>
<div class="info">
	<p>Set a specific expiration date (e.g. 2013-12-31) for a PMPro membership level or discount code.</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a href="https://github.com/strangerstudios/pmpro-set-expiration-dates/blob/master/readme.txt" class="button">Enabled</a>
		<?php } elseif(file_exists(dirname(__FILE__) . "/../../../pmpro-set-expiration-dates/pmpro-set-expiration-dates.php")) { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=pmpro-set-expiration-dates/pmpro-set-expiration-dates.php'), 'activate-plugin_pmpro-set-expiration-dates/pmpro-set-expiration-dates.php')?>" class="button button-primary">Activate</a>
		<?php } else { ?>
			<a href="http://www.paidmembershipspro.com/wp-content/uploads/plugins/pmpro-set-expiration-dates.zip" class="button button-primary">Download</a>
		<?php } ?>				
	</div>						
</div> <!-- end info -->
<?php
}
