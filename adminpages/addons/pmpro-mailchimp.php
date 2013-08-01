<?php
/*
	Addon: PMPro MailChimp Integration
	Slug: pmpro-mailchimp
*/
pmpro_add_addon('thirdparty', array(
		'title' => 'PMPro MailChimp Integration',
		'version' => '.3.1',
		'widget' => 'pmpro_addon_pmpro_mailchimp_widget',
		'enabled' => function_exists('pmpromc_init')
	)
);

function pmpro_addon_pmpro_mailchimp_widget($addon)
{
?>
<img class="addon-thumb" src="<?php echo PMPRO_URL?>/adminpages/addons/images/pmpro-mailchimp.jpg" />
<div class="info">							
	<p>Integrate User Registrations with Mailchimp. Adds members to lists based on their membership level. (Note: works without PMPro as well.)</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a href="https://github.com/strangerstudios/pmpro-mailchimp/" class="button">Enabled</a>
		<?php } elseif(file_exists(dirname(__FILE__) . "/../../../pmpro-mailchimp/pmpro-mailchimp.php")) { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=pmpro-mailchimp/pmpro-mailchimp.php'), 'activate-plugin_pmpro-mailchimp/pmpro-mailchimp.php')?>" class="button button-primary">Activate</a>
		<?php } else { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=pmpro-mailchimp'), 'install-plugin_pmpro-mailchimp'); ?>" class="button button-primary">Download</a>
		<?php } ?>
	</div>						
</div> <!-- end info -->
<?php
}
