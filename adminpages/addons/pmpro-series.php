<?php
/*
	Addon: PMPro Series
	Slug: pmpro-series
*/
pmpro_add_addon('github', array(
		'title' => 'PMPro Series',
		'version' => '.2',
		'widget' => 'pmpro_addon_pmpro_series_widget',
		'enabled' => class_exists("PMProSeries")
	)
);

function pmpro_addon_pmpro_series_widget($addon)
{
?>
<div class="info">						
	<p>Add series to "drip feed" content to your members over the course of their membership.</p>
	<div class="actions">									
		<?php if($addon['enabled']) { ?>
			<a href="<?php echo admin_url("edit.php?post_type=pmpro_series");?>" class="button">Enabled</a>
		<?php } elseif(file_exists(dirname(__FILE__) . "/../../../pmpro-series/pmpro-series.php")) { ?>
			<a href="<?php echo wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=pmpro-series/pmpro-series.php'), 'activate-plugin_pmpro-series/pmpro-series.php')?>" class="button button-primary">Activate</a>
		<?php } else { ?>
			<a href="https://github.com/strangerstudios/pmpro-series/archive/master.zip" class="button button-primary">Download</a>
		<?php } ?>
	</div>						
</div> <!-- end info -->
<?php
}