<?php
/*
	Addon: PMPro Email Templates Editor
	Slug: pmpro-email-templates-addon
*/
pmpro_add_addon('repo', array(
		'title' => 'PMPro Email Templates',
		'version' => '.5.2',
		'widget' => 'pmpro_addon_email_templates_widget',
		'enabled' => function_exists('pmproet_scripts')
	)
);

function pmpro_addon_email_templates_widget($addon)
{
?>
<img class="addon-thumb" src="<?php echo PMPRO_URL?>/adminpages/addons/images/pmpro-email-templates.gif" />
<div class="info">							
	<p>Easily edit system-generated Email Templates from the WordPress admin.</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a href="http://wordpress.org/plugins/pmpro-email-templates-addon/" class="button">Enabled</a>
		<?php } elseif(file_exists(dirname(__FILE__) . "/../../../pmpro-email-templates-addon/pmpro-email-templates.php")) { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=pmpro-email-templates-addon/pmpro-email-templates.php'), 'activate-plugin_pmpro-email-templates-addon/pmpro-email-templates.php')?>" class="button button-primary">Activate</a>
		<?php } else { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=pmpro-email-templates-addon'), 'install-plugin_pmpro-email-templates-addon'); ?>" class="button button-primary">Download</a>
		<?php } ?>
	</div>						
</div> <!-- end info -->
<?php
}
