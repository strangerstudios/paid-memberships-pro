<?php
/*
	Addon: Theme My Login
	Slug: pmpro-tml
*/
pmpro_add_addon('recommended', array(
		'title' => 'Theme My Login',
		'widget' => 'pmpro_addon_tml_widget',
		'enabled' => class_exists('Theme_My_Login'),
		'version' => '6.3.8'
	)
);

function pmpro_addon_tml_widget($addon)
{
?>
<div class="info">							
	<p>This plugin themes the WordPress login, registration and forgot password pages according to your current theme. By <a href="http://www.jfarthing.com/" target="_blank">Jeff Farthing</a></p>
	<div class="actions">							
		<form method="post" name="component-actions" action="">
			<?php if($addon['enabled']) { ?>
				<a href="<?php echo admin_url("plugins.php");?>" class="button">Enabled</a>
			<?php } elseif(file_exists(dirname(__FILE__) . "/../../../theme-my-login/theme-my-login.php")) { ?>
				<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=theme-my-login/theme-my-login.php'), 'activate-plugin_theme-my-login/theme-my-login.php')?>" class="button button-primary">Activate</a>
			<?php } else { ?>
				<a href="<?php echo wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=theme-my-login'), 'install-plugin_theme-my-login'); ?>" class="button button-primary">Download</a>
			<?php } ?>
		</form>
	</div>						
</div> <!-- end info -->
<?php
}
