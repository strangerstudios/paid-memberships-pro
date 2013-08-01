<?php
/*
	Addon: PMPro User Pages
	Slug: pmpro-user-pages
*/
pmpro_add_addon('github', array(
		'title' => 'PMPro User Pages',
		'version' => '.3',
		'widget' => 'pmpro_addon_pmpro_user_pages_widget',
		'enabled' => function_exists('pmproup_pmpro_after_checkout')
	)
);

function pmpro_addon_pmpro_user_pages_widget($addon)
{
?>
<div class="info">							
	<p>Creates a unique page for each Member after checkout, giving the Admin access to write customized content for each specific member.</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a href="https://github.com/strangerstudios/pmpro-user-pages/blob/master/readme.txt" class="button">Enabled</a>
		<?php } elseif(file_exists(dirname(__FILE__) . "/../../../pmpro-user-pages/pmpro-user-pages.php")) { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=pmpro-user-pages/pmpro-user-pages.php'), 'activate-plugin_pmpro-user-pages/pmpro-user-pages.php')?>" class="button button-primary">Activate</a>
		<?php } else { ?>
			<a href="http://www.paidmembershipspro.com/wp-content/uploads/plugins/pmpro-user-pages.zip" class="button button-primary">Download</a>
		<?php } ?>
	</div>						
</div> <!-- end info -->
<?php
}
