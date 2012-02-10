<?php
	global $wpdb, $msg, $msgt;
			
	//get/set settings
	global $pmpro_pages;
	if(!empty($_REQUEST['savesettings']))
	{                   		
		//page ids
		pmpro_setOption("account_page_id");
		pmpro_setOption("billing_page_id");
		pmpro_setOption("cancel_page_id");
		pmpro_setOption("checkout_page_id");
		pmpro_setOption("confirmation_page_id");
		pmpro_setOption("invoice_page_id");
		pmpro_setOption("levels_page_id");
		
		//update the pages array
		$pmpro_pages["account"] = pmpro_getOption("account_page_id");
		$pmpro_pages["billing"] = pmpro_getOption("billing_page_id");
		$pmpro_pages["cancel"] = pmpro_getOption("cancel_page_id");
		$pmpro_pages["checkout"] = pmpro_getOption("checkout_page_id");
		$pmpro_pages["confirmation"] = pmpro_getOption("confirmation_page_id");
		$pmpro_pages["invoice"] = pmpro_getOption("invoice_page_id");
		$pmpro_pages["levels"] = pmpro_getOption("levels_page_id");	

		//assume success
		$msg = true;
		$msgt = "Your page settings have been updated.";		
	}	
			
	//are we generating pages?
	if(!empty($_REQUEST['createpages']))
	{
		global $pmpro_pages;
		
		$pages_created = array();
		
		//check the pages array
		foreach($pmpro_pages as $pmpro_page_name => $pmpro_page_id)
		{
			if(!$pmpro_page_id)
			{
				//no id set. create an array to store the page info
				$insert = array(
					'post_title' => 'Membership ' . ucwords($pmpro_page_name),
					'post_status' => 'publish',
					'post_type' => 'page',
					'post_content' => '[pmpro_' . $pmpro_page_name . ']',
					'comment_status' => 'closed',
					'ping_status' => 'closed'
					);
				//create the page
				$pmpro_pages[$pmpro_page_name] = wp_insert_post( $insert );
				
				//add besecure post option to pages that need it
				/* these pages are handling this themselves in the preheader
				if(in_array($pmpro_page_name, array("billing", "checkout")))
					update_post_meta($pmpro_pages[$pmpro_page_name], "besecure", 1);								
				*/
					
				//update the option too
				pmpro_setOption($pmpro_page_name . "_page_id", $pmpro_pages[$pmpro_page_name]);
				$pages_created[] = $pmpro_pages[$pmpro_page_name];
			}
		}
		
		if(!empty($pages_created))
		{
			$msg = true;
			$msgt = "The following pages have been created for you: " . implode(", ", $pages_created) . ".";
		}
	}		
	
	require_once(dirname(__FILE__) . "/admin_header.php");		
	?>
	

	<form action="" method="post" enctype="multipart/form-data">        	        			
		<h2>Pages</h2> 
		<?php
			global $pmpro_pages_ready;
			if($pmpro_pages_ready)
			{
			?>
				<p>Manage the WordPress pages assigned to each required Paid Memberships Pro page.</p>
			<?php
			} 
			else 
			{ 
			?>
				<p>Assign the WordPress pages for each required Paid Memberships Pro page or <a href="?page=pmpro-pagesettings&createpages=1">click here to let us generate them for you</a>.</p>
			<?php
			}
		?>       			        
		<table class="form-table">
		<tbody>                
			<tr>
				<th scope="row" valign="top">                        
					<label for="account_page_id">Account Page:</label>
				</th>
				<td>
					<?php
						wp_dropdown_pages(array("name"=>"account_page_id", "show_option_none"=>"-- Choose One --", "selected"=>$pmpro_pages['account']));
					?>	
					<?php if(!empty($pmpro_pages['account'])) { ?>
						<a target="_blank" href="post.php?post=<?php echo $pmpro_pages['account']?>&action=edit" class="pmpro_page_edit">edit page</a>
					<?php } ?>
					<br /><small class="pmpro_lite">Include the shortcode [pmpro_account].</small>
				</td>
			<tr>
				<th scope="row" valign="top">
					<label for="billing_page_id">Billing Information Page:</label>
				</th>
				<td>
					<?php
						wp_dropdown_pages(array("name"=>"billing_page_id", "show_option_none"=>"-- Choose One --", "selected"=>$pmpro_pages['billing']));
					?>
					<?php if(!empty($pmpro_pages['billing'])) { ?>
						<a target="_blank" href="post.php?post=<?php echo $pmpro_pages['billing']?>&action=edit" class="pmpro_page_edit">edit page</a>
					<?php } ?>
					<br /><small class="pmpro_lite">Include the shortcode [pmpro_billing].</small>
				</td>
			<tr>
				<th scope="row" valign="top">	
					<label for="cancel_page_id">Cancel Page:</label>
				</th>
				<td>
					<?php
						wp_dropdown_pages(array("name"=>"cancel_page_id", "show_option_none"=>"-- Choose One --", "selected"=>$pmpro_pages['cancel']));
					?>	
					<?php if(!empty($pmpro_pages['cancel'])) { ?>
						<a target="_blank" href="post.php?post=<?php echo $pmpro_pages['cancel']?>&action=edit" class="pmpro_page_edit">edit page</a>
					<?php } ?>
					<br /><small class="pmpro_lite">Include the shortcode [pmpro_cancel].</small>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">	
					<label for="checkout_page_id">Checkout Page:</label>
				</th>
				<td>
					<?php
						wp_dropdown_pages(array("name"=>"checkout_page_id", "show_option_none"=>"-- Choose One --", "selected"=>$pmpro_pages['checkout']));
					?>
					<?php if(!empty($pmpro_pages['checkout'])) { ?>
						<a target="_blank" href="post.php?post=<?php echo $pmpro_pages['checkout']?>&action=edit" class="pmpro_page_edit">edit page</a>
					<?php } ?>
					<br /><small class="pmpro_lite">Include the shortcode [pmpro_checkout].</small>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">		
					<label for="confirmation_page_id">Confirmation Page:</label>
				</th>
				<td>
					<?php
						wp_dropdown_pages(array("name"=>"confirmation_page_id", "show_option_none"=>"-- Choose One --", "selected"=>$pmpro_pages['confirmation']));
					?>	
					<?php if(!empty($pmpro_pages['confirmation'])) { ?>
						<a target="_blank" href="post.php?post=<?php echo $pmpro_pages['confirmation']?>&action=edit" class="pmpro_page_edit">edit page</a>
					<?php } ?>
					<br /><small class="pmpro_lite">Include the shortcode [pmpro_confirmation].</small>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">	
					<label for="invoice_page_id">Invoice Page:</label>
				</th>
				<td>
					<?php
						wp_dropdown_pages(array("name"=>"invoice_page_id", "show_option_none"=>"-- Choose One --", "selected"=>$pmpro_pages['invoice']));
					?>
					<?php if(!empty($pmpro_pages['invoice'])) { ?>
						<a target="_blank" href="post.php?post=<?php echo $pmpro_pages['invoice']?>&action=edit" class="pmpro_page_edit">edit page</a>
					<?php } ?>
					<br /><small class="pmpro_lite">Include the shortcode [pmpro_invoice].</small>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">	
					<label for="levels_page_id">Levels Page:</label>
				</th>
				<td>
					<?php
						wp_dropdown_pages(array("name"=>"levels_page_id", "show_option_none"=>"-- Choose One --", "selected"=>$pmpro_pages['levels']));
					?>
					<?php if(!empty($pmpro_pages['levels'])) { ?>
						<a target="_blank" href="post.php?post=<?php echo $pmpro_pages['levels']?>&action=edit" class="pmpro_page_edit">edit page</a>
					<?php } ?>
					<br /><small class="pmpro_lite">Include the shortcode [pmpro_levels].</small>
				</td>
			</tr>				
		</tbody>
		</table>
		<p class="submit">            
			<input name="savesettings" type="submit" class="button-primary" value="Save Settings" /> 		                			
		</p> 			
	</form>
	
<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");	
?>
