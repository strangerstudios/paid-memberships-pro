<?php
/*
	Addon: WP Bouncer
	Slug: wp-bouncer
*/
pmpro_add_addon('repo', array(
		'title' => 'WP Bouncer',
		'version' => '1.1',
		'widget' => 'pmpro_addon_wp_bouncer_widget',
		'enabled' => class_exists('WP_Bouncer')
	)
);

function pmpro_addon_wp_bouncer_widget($addon)
{
?>
<img class="addon-thumb" src="<?php echo PMPRO_URL?>/adminpages/addons/images/wp-bouncer.gif" />
<div class="info">							
	<p>Make sure users are only logged in from one computer or device at a time.</p>
	<div class="actions">							
		<?php if($addon['enabled']) { ?>
			<a href="https://github.com/strangerstudios/wp-bouncer/blob/master/readme.txt" class="button">Enabled</a>
		<?php } elseif(file_exists(dirname(__FILE__) . "/../../../wp-bouncer/wp-bouncer.php")) { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=wp-bouncer/wp-bouncer.php'), 'activate-plugin_wp-bouncer/wp-bouncer.php')?>" class="button button-primary">Activate</a>
		<?php } else { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=wp-bouncer'), 'install-plugin_wp-bouncer'); ?>" class="button button-primary">Download</a>
		<?php } ?>
	</div>						
</div> <!-- end info -->
<?php
}
