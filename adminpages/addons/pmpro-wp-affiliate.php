<?php
/*
	Addon: PMPro WP Affiliate Platform Integration
	Slug: pmpro-wp-affiliate
*/
pmpro_add_addon('thirdparty', array(
		'title' => 'PMPro WP Affiliate Platform Integration',
		'version' => '.3',
		'widget' => 'pmpro_addon_pmpro_wp_affiliate_widget',
		'enabled' => function_exists('wpa_pmpro_after_checkout')
	)
);

function pmpro_addon_pmpro_wp_affiliate_widget($addon)
{
?>
<img class="addon-thumb" src="<?php echo PMPRO_URL?>/adminpages/addons/images/pmpro-wp-affiliate-platform.jpg" />
<div class="info">							
	<p>Process an affiliate via WP Affiliate Platform after a PMPro checkout.</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a href="https://github.com/strangerstudios/pmpro-wp-affiliate-platform/" class="button">Enabled</a>
		<?php } elseif(file_exists(dirname(__FILE__) . "/../../../pmpro-wp-affiliate-platform/pmpro-wp-affiliate-platform.php")) { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=pmpro-wp-affiliate-platform/pmpro-wp-affiliate-platform.php'), 'activate-plugin_pmpro-wp-affiliate-platform/pmpro-wp-affiliate-platform.php')?>" class="button button-primary">Activate</a>
		<?php } else { ?>
			<a href="http://www.paidmembershipspro.com/wp-content/uploads/plugins/pmpro-wp-affiliate-platform.zip" class="button button-primary">Download</a>
		<?php } ?>				
	</div>							
</div> <!-- end info -->
<?php
}
