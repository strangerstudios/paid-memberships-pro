<?php
	global $pmpro_reports;
	
	require_once(dirname(__FILE__) . "/admin_header.php");
	
	//default view, report widgets
	if(empty($_REQUEST['report']))
	{				
		//wrapper
		?>
		<div id="dashboard-widgets-wrap">
			<div id="dashboard-widgets" class="metabox-holder columns-2">
				<div id="postbox-container-1" class="postbox-container">
					<div id="normal-sortables" class="meta-box-sortables">
		<?php
		
		//report widgets
		foreach($pmpro_reports as $report => $title)
		{
		?>
		<div id="pmpro_report_<?php echo $report; ?>" class="postbox pmpro_clickable" onclick="location.href='<?php echo admin_url("admin.php?page=pmpro-reports&report=" . $report);?>';">			
			<h3 class="hndle"><span><?php echo $title; ?></span></h3>
			<div class="inside">
				<?php call_user_func("pmpro_report_" . $report . "_widget"); ?>
				<div style="margin-top:10px;border-top: 1px solid #ddd; padding-top: 10px; text-align:center;">
					<a href="<?php echo admin_url("admin.php?page=pmpro-reports&report=" . $report);?>"><?php _e('Details', 'pmpro');?></a>
				</div>
			</div>
		</div>
		<?php
		}
		
		//end wrapper
		?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
	else
	{
		//view a single report
		$report = $_REQUEST['report'];
		call_user_func("pmpro_report_" . $report . "_page");
	}
	
	require_once(dirname(__FILE__) . "/admin_footer.php");
?>