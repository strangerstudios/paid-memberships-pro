<?php
/*
	Addon: Hide Admin Bar From Non-Admins
	Slug: hide-admin-bar-from-non-admins
*/
pmpro_add_addon('repo', array(
		'title' => 'Hide Admin Bar From Non-Admins',
		'version' => '1.0',
		'widget' => 'pmpro_addon_hide_admin_bar_from_non_admins_widget',
		'enabled' => function_exists('habfna_disable_admin_bar')
	)
);

function pmpro_addon_hide_admin_bar_from_non_admins_widget($addon)
{
?>
<div class="info">							
	<p>Perfect for sites where there is only one admin who needs access to the dashboard and the admin bar. When activated only administrators will see the admin bar.</p>
	<div class="actions">							
		<form method="post" name="component-actions" action="">
			<?php if($addon['enabled']) { ?>
				<a href="<?php echo admin_url("plugins.php");?>" class="button">Enabled</a>
			<?php } elseif(file_exists(dirname(__FILE__) . "/../../../hide-admin-bar-from-non-admins/hide-admin-bar-from-non-admins.php")) { ?>
				<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=hide-admin-bar-from-non-admins/hide-admin-bar-from-non-admins.php'), 'activate-plugin_hide-admin-bar-from-non-admins/hide-admin-bar-from-non-admins.php')?>" class="button button-primary">Activate</a>
			<?php } else { ?>
				<a href="<?php echo wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=hide-admin-bar-from-non-admins'), 'install-plugin_hide-admin-bar-from-non-admins'); ?>" class="button button-primary">Download</a>
			<?php } ?>
		</form>
	</div>						
</div> <!-- end info -->
<?php
}