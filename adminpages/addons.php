<?php
	//only admins can get this
	if(!function_exists("current_user_can") || !current_user_can("manage_options"))
	{
		die("You do not have permissions to perform this action.");
	}	
	
	global $wpdb, $msg, $msgt;
		
	require_once(dirname(__FILE__) . "/admin_header.php");		
?>

	<h2>Add Ons</h2>
	<ul class="subsubsub">
		<li><a href="#all" class="current all tab">All</a> <span>(X)</span> | </li>
		<li><a href="#pmpro-bundled" class="tab">Bundled Features</a> <span>(X)</span> | </li>
		<li><a href="#pmpro-download" class="tab">Downloadable Features</a> <span>(X)</span> | </li>
		<li><a href="#pmpro-thirdparty" class="tab">Third-party Integrations</a> <span>(X)</span></li>
	</ul>
	
	<div id="pmpro-bundled" class="widgets-holder-wrap">
	
		<h3 class="section-title">Bundled Features</h3>
		<p class="description">These features are prepackaged with the default Paid Memberships Pro plugin and can be optionally activated here.</p>
		<br class="clear" />
	
		<div id="addons-list">
	
			<div id="addon-reghelper" class="widget disabled">
				<div class="widget-top">
					<div class="widget-title">
						<h4>
							<span class="status-label">Disabled</span>
							<span class="title">PMPro Register Helper</span>
							<span class="version pmpro_tag-grey">0.5.0</span>
							<span class="in-widget-title"></span>
						</h4>
					</div> <!-- end widget-title -->
				</div> <!-- end widget-top -->					
				<div class="widget-inside">					
					<img class="addon-thumb" src="<?php echo PMPRO_URL?>/images/pmpro_register-helper.gif" />
					<div class="info">						
						<p>Add additional meta fields to your PMPro checkout page and/or "Your Profile" pages. Support for various input types including text, select, multi-select, textarea, hidden, and custom HTML. Loop into existing checkout/profile field sections or add new ones.</p>
						<div class="actions">							
							<form method="post" name="component-actions" action="">
								<input type="submit" value="Activate" class="button-primary">
							</form>
						</div>						
					</div> <!-- end info -->
				</div> <!-- end addon-inside -->
			</div> <!-- end widget -->		
			
			<div id="addon-series" class="widget enabled">
				<div class="widget-top">
					<div class="widget-title">
						<h4>
							<span class="status-label">Enabled</span>
							<span class="title">PMPro Series</span>
							<span class="version pmpro_tag-grey">0.5.0</span>
							<span class="in-widget-title"></span>
						</h4>
					</div> <!-- end widget-title -->
				</div> <!-- end widget-top -->					
				<div class="widget-inside">					
					<img class="addon-thumb" src="<?php echo PMPRO_URL?>/images/pmpro_register-helper.gif" />
					<div class="info">						
						<p>Add series to "drip feed" content to your members over the course of their membership.</p>
						<div class="actions">							
							<form method="post" name="component-actions" action="">
								<input type="submit" value="Enabled" class="button">
							</form>
						</div>						
					</div> <!-- end info -->
				</div> <!-- end addon-inside -->
			</div> <!-- end widget -->		
					
			<br class="clear" />		
		</div> <!-- end addon-list -->
		
	</div> <!-- end pmpro-bundled -->
	
<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");	
?>
