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
			<a target="_blank" href="https://gist.github.com/strangerstudios/3137539" class="button">Enabled</a>
		<?php } else { ?>
			<a target="_blank" href="https://gist.github.com/strangerstudios/3137539" class="button button-primary">View Gist</a>
		<?php } ?>
	</div>							
</div> <!-- end info -->
<?php
}
