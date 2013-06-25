<?php
/*
	Addon: PMPro Post Affiliate Pro Integration
	Slug: pmpro-post-affiliate-pro
*/
pmpro_add_addon('thirdparty', array(
		'title' => 'PMPro Post Affiliate Pro Integration',
		'version' => '.3',
		'widget' => 'pmpro_addon_pmpro_post_affiliate_pro_widget',
		'enabled' => function_exists('pap_pmpro_track_sale')
	)
);

function pmpro_addon_pmpro_post_affiliate_pro_widget($addon)
{
?>
<img class="addon-thumb" src="<?php echo PMPRO_URL?>/adminpages/addons/images/pmpro-post-affiliate-pro.jpg" />
<div class="info">							
	<p>Integrate Paid Memberships Pro with the Post Affiliate Pro platform.</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a href="https://github.com/strangerstudios/pmpro-post-affiliate-pro/blob/master/readme.txt" class="button">Enabled</a>
		<?php } elseif(file_exists(dirname(__FILE__) . "/../../../pmpro-post-affiliate-pro/pmpro-post-affiliate-pro.php")) { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=pmpro-post-affiliate-pro/pmpro-post-affiliate-pro.php'), 'activate-plugin_pmpro-post-affiliate-pro/pmpro-post-affiliate-pro.php')?>" class="button button-primary">Activate</a>
		<?php } else { ?>
			<a href="http://www.paidmembershipspro.com/wp-content/uploads/plugins/pmpro-post-affiliate-pro.zip" class="button button-primary">Download</a>
		<?php } ?>
	</div>							
</div> <!-- end info -->
<?php
}
