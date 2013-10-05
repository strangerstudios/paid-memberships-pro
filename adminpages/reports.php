<?php
	global $pmpro_reports;
	
	require_once(dirname(__FILE__) . "/admin_header.php");
	
	//default view, report widgets
	if(empty($_REQUEST['report']))
	{				
		//wrapper
		?>
		<div id="dashboard-widgets-wrap">
			<div id="dashboard-widgets" class="metabox-holder pmpro_reports-holder columns-2">	
			<div id="postbox-container-1" class="postbox-container">
				<div id="normal-sortables" class="meta-box-sortables ui-sortable">
		<?php
		
		//report widgets
		$count = 0;
		$nreports = count($pmpro_reports);
		$split = false;
		foreach($pmpro_reports as $report => $title)
		{
			//put half of the report widgets in postbox-container-2
			if(!$split && $count++ > $nreports/2)
			{
				$split = true;
				?>
				</div></div><div id="postbox-container-2" class="postbox-container"><div id="side-sortables" class="meta-box-sortables ui-sortable">
				<?php
			}
		?>
		<div id="pmpro_report_<?php echo $report; ?>" class="postbox pmpro_clickable" onclick="location.href='<?php echo admin_url("admin.php?page=pmpro-reports&report=" . $report);?>';">			
			<h3 class="hndle"><span><?php echo $title; ?></span></h3>
			<div class="inside">
				<?php call_user_func("pmpro_report_" . $report . "_widget"); ?>
				<div style="margin-top:10px;border-top: 1px solid #ddd; padding-top: 10px; text-align:center;">
					<a class="button button-primary" href="<?php echo admin_url("admin.php?page=pmpro-reports&report=" . $report);?>"><?php _e('Details', 'pmpro');?></a>
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