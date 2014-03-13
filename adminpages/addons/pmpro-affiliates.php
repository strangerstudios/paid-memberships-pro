<?php
/*
	Addon: PMPro Affiliates
	Slug: pmpro-affiliates
*/
pmpro_add_addon('github', array(
		'title' => 'PMPro Affiliates',
		'version' => '.2.4',
		'widget' => 'pmpro_addon_pmpro_affiliates_widget',
		'enabled' => function_exists('pmpro_affiliates_dependencies')
	)
);

function pmpro_addon_pmpro_affiliates_widget($addon)
{
?>
<div class="info">							
	<p>Lightweight Affiliate system. Create affiliate accounts and codes; tracks checkouts by affiliate account.</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a href="https://github.com/strangerstudios/pmpro-affiliates/blob/master/readme.txt" class="button">Enabled</a>
		<?php } elseif(file_exists(dirname(__FILE__) . "/../../../pmpro-affiliates/pmpro-affiliates.php")) { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=pmpro-affiliates/pmpro-affiliates.php'), 'activate-plugin_pmpro-affiliates/pmpro-affiliates.php')?>" class="button button-primary">Activate</a>
		<?php } else { ?>
			<a href="http://www.paidmembershipspro.com/wp-content/uploads/plugins/pmpro-affiliates.zip" class="button button-primary">Download</a>
		<?php } ?>
	</div>						
</div> <!-- end info -->
<?php
}
