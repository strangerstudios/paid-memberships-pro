<?php
/*
	Addon: PMPro bbPress
	Slug: pmpro-bbpress
*/
pmpro_add_addon('repo', array(
		'title' => 'PMPro bbPress',
		'version' => '1.0',
		'widget' => 'pmpro_addon_pmpro_bbpress_widget',
		'enabled' => function_exists('pmpro_check_forum')
	)
);

function pmpro_addon_pmpro_bbpress_widget($addon)
{
?>
<div class="info">
	<p>Locking down bbPress Forums by Membership Level and Forum ID.</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a href="<?php echo admin_url("plugins.php");?>" class="button">Enabled</a>
		<?php } elseif(file_exists(dirname(__FILE__) . "/../../../pmpro-bbpress/pmpro-bbpress.php")) { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=pmpro-bbpress/pmpro-bbpress.php'), 'activate-plugin_pmpro-bbpress/pmpro-bbpress.php')?>" class="button button-primary">Activate</a>
		<?php } else { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=pmpro-bbpress'), 'install-plugin_pmpro-bbpress'); ?>" class="button button-primary">Download</a>
		<?php } ?>				
	</div>						
</div> <!-- end info -->
<?php
}
