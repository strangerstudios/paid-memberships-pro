<?php
	if(isset($_REQUEST['page']))
		$view = $_REQUEST['page'];
	else
		$view = "";
	
	global $pmpro_ready, $msg, $msgt;
	$pmpro_ready = pmpro_is_ready();
	if(!$pmpro_ready)
	{
		global $pmpro_level_ready, $pmpro_gateway_ready, $pmpro_pages_ready;		
		if(!isset($edit))
		{
			if(isset($_REQUEST['edit']))
				$edit = $_REQUEST['edit'];
			else
				$edit = false;
		}
		
		if(empty($msg))
			$msg = -1;		
		if(empty($pmpro_level_ready) && empty($edit))
			$msgt .= " <a href=\"?page=pmpro-membershiplevels&edit=-1\">Add a membership level</a> to get started.";
		elseif($pmpro_level_ready && !$pmpro_pages_ready && $view != "pmpro-pagesettings")
			$msgt .= " <a href=\"?page=pmpro-pagesettings\">Setup the membership pages</a>.";		
		elseif($pmpro_level_ready && $pmpro_pages_ready && !$pmpro_gateway_ready && $view != "pmpro-paymentsettings")
			$msgt .= " <a href=\"?page=pmpro-paymentsettings\">Setup your SSL certificate and payment gateway</a>.";
			
		if(empty($msgt))
			$msg = false;
	}
	
	if(!empty($msg))
	{
	?>
		<div id="message" class="<?php if($msg > 0) echo "updated fade"; else echo "error"; ?>"><p><?php echo $msgt?></p></div>
	<?php
	}		

?>
<div class="wrap pmpro_admin">	
	<div class="pmpro_banner">		
		<a class="pmpro_logo" title="Paid Memberships Pro - Membership Plugin for WordPress" target="_blank" href="<?php echo pmpro_https_filter("http://www.paidmembershipspro.com")?>"><img src="<?php echo PMPRO_URL?>/images/PaidMembershipsPro.gif" width="350" height="45" border="0" alt="Paid Memberships Pro(c) - All Rights Reserved" /></a>
		<div class="pmpro_tagline">Membership Plugin for WordPress</div>
		
		<div class="pmpro_meta"><a href="<?php echo pmpro_https_filter("http://www.paidmembershipspro.com")?>">Plugin Support</a> | <a href="http://www.paidmembershipspro.com/forums/">User Forum</a> | <strong>Version <?php echo PMPRO_VERSION?></strong></div>
	</div>
	<br style="clear:both;" />
		
	<div id="pmpro_notifications">
	</div>
	<script>
		jQuery(document).ready(function() {
			jQuery.get('<?php echo home_url("/wp-admin/admin-ajax.php?action=pmpro_notifications"); ?>', function(data) {
			  jQuery('#pmpro_notifications').html(data);		 
			});
		});
	</script>
	
	<h3 class="nav-tab-wrapper">
		<a href="admin.php?page=pmpro-membershiplevels" class="nav-tab<?php if($view == 'pmpro-membershiplevels') { ?> nav-tab-active<?php } ?>">Membership Levels</a>
		<a href="admin.php?page=pmpro-pagesettings" class="nav-tab<?php if($view == 'pmpro-pagesettings') { ?> nav-tab-active<?php } ?>">Pages</a>
		<a href="admin.php?page=pmpro-paymentsettings" class="nav-tab<?php if($view == 'pmpro-paymentsettings') { ?> nav-tab-active<?php } ?>">SSL &amp; Payment Gateway</a>
		<a href="admin.php?page=pmpro-emailsettings" class="nav-tab<?php if($view == 'pmpro-emailsettings') { ?> nav-tab-active<?php } ?>">Email</a>
		<a href="admin.php?page=pmpro-advancedsettings" class="nav-tab<?php if($view == 'pmpro-advancedsettings') { ?> nav-tab-active<?php } ?>">Advanced</a>	
	</h3>
