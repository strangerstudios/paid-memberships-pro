<?php
	//only admins can get this
	if(!function_exists("current_user_can") || !current_user_can("manage_options"))
	{
		die(__("You do not have permissions to perform this action.", "pmpro"));
	}	
	
	global $wpdb, $msg, $msgt, $pmpro_addons;
	
	/*
		Addon lists
	*/
	$pmpro_addon_lists = array(
		'repo' => array('Plugins in the WordPress Repository', 'These official PMPro plugins are available in the WordPress repository and can be installed through Plugins --> Add New.'),
		'thirdparty' => array('Third-party Integration', 'These official PMPro plugins integrate with specific third-party tools and software.'),
		'recommended' => array('Recommended Plugins', 'These plugins are not developed by the PMPro team, but are recommended for sites running PMPro.'),
		'github' => array('Plugins on GitHub', 'These official PMPro plugins must be downloaded from GitHub and installed through Plugins --> Add New --> Upload, then activated. These plugins cannot be automatically updated and may require more developer input.'),						
		'gists' => array('Code Gists', 'These are bits of code that generally must be added to your active theme\'s functions.php file or included in a custom plugin. Most gists require customization and are recommended for developers only.')
	);
	
	/*
		Function to add an addon
	*/
	function pmpro_add_addon($list, $addon)
	{
		global $pmpro_addons;
		
		//make sure we have the base array
		if(empty($pmpro_addons))
			$pmpro_addons = array();
			
		//make sure we have an array for the list
		if(empty($pmpro_addons[$list]))
			$pmpro_addons[$list] = array();
		
		//add addon to list
		$pmpro_addons[$list][] = $addon;
	}
	
	/*
		Load All Addons
	*/	
	$pmpro_addons_dir = dirname(__FILE__) . "/../adminpages/addons/";
	$cwd = getcwd();
	chdir($pmpro_addons_dir);
	$count = 0;
	foreach (glob("*.php") as $filename) 
	{
		$count++;
		require_once($filename);
	}
	chdir($cwd);		
	
	require_once(dirname(__FILE__) . "/admin_header.php");		
?>

	<h2>Add Ons</h2>
	<ul id="addon-filters" class="subsubsub">
		<li id="addon-filters-all"><a href="javascript:void(0);" class="current all tab">All</a> <span>(<?php echo $count;?>)</span></li>
		<?php foreach($pmpro_addon_lists as $list => $list_info) { ?>
			<li id="addon-filters-<?php echo $list;?>"> | <a href="javascript:void(0);>" class="tab"><?php echo $list_info[0];?></a> <span>(<?php echo count($pmpro_addons[$list]);?>)</span></li>
		<?php } ?>		
	</ul>
		
	<?php foreach($pmpro_addon_lists as $list => $list_info) { ?>
		<div id="pmpro-<?php echo $list;?>" class="pmpro-addon-list widgets-holder-wrap">

			<h3 class="section-title"><?php echo $list_info[0];?></h3>
			<p class="description"><?php echo $list_info[1];?></p>
			<br class="clear" />
		
			<div id="addons-list-<?php echo $list;?>" class="addon-list">
				
				<?php foreach($pmpro_addons[$list] as $slug => $addon) { ?>
					<div id="addon-<?php echo $slug;?>" class="widget <?php if($addon['enabled']) echo "enabled"; else echo "disabled";?>">
					<div class="widget-top">
						<div class="widget-title">
							<h4>
								<span class="status-label"><?php if($addon['enabled']) echo __("Enabled", "pmpro"); else echo __("Disabled", "pmpro");?></span>
								<span class="title"><?php echo $addon['title'];?></span>
								<span class="version pmpro_tag-grey"><?php echo $addon['version'];?></span>
								<span class="in-widget-title"></span>
							</h4>
						</div> <!-- end widget-title -->
					</div> <!-- end widget-top -->					
					<div class="widget-inside">					
						<?php call_user_func($addon['widget'], $addon);?>
					</div> <!-- end addon-inside -->
				</div> <!-- end widget -->	
				<?php } ?>						
						
				<br class="clear" />		
			</div> <!-- end addon-list -->
			
		</div> <!-- end pmpro-<?php echo $list;?> -->
	<?php } ?>

	<script>
		//tabs
		jQuery(document).ready(function() {
			jQuery('#addon-filters a.tab').click(function() {
				//which tab?
				var tab = jQuery(this).parent().attr('id').replace('addon-filters-', '');
				
				//un select tabs
				jQuery('#addon-filters a.tab').removeClass('current');
				
				//select this tab
				jQuery('#addon-filters-'+tab+' a').addClass('current');
				
				//show all?
				if(tab == 'all')
					jQuery('div.pmpro-addon-list').show();
				else
				{
					//hide all
					jQuery('div.pmpro-addon-list').hide();
					
					//show this one
					jQuery('#pmpro-'+tab).show();
				}
			});
		});
		
		//resize addon boxes
		jQuery(document).ready(function() {
			jQuery('.addon-list').each(function() {
				//what's the tallest p in the list?
				var tallest = 32;
				jQuery(this).find('div.info p').each(function() {
					tallest = Math.max(tallest, jQuery(this).height());
				});
				
				//set all p's to match
				jQuery(this).find('div.info p').css('height', tallest);
			});
		});
	</script>
	
<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");	
?>
