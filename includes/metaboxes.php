<?php
/*
	Require Membership Meta Box
*/
function pmpro_page_meta()
{
	global $membership_levels, $post, $wpdb;
	$page_levels = $wpdb->get_col("SELECT membership_id FROM {$wpdb->pmpro_memberships_pages} WHERE page_id = '{$post->ID}'");
?>
    <ul id="membershipschecklist" class="list:category categorychecklist form-no-clear">
    <input type="hidden" name="pmpro_noncename" id="pmpro_noncename" value="<?php echo wp_create_nonce( plugin_basename(__FILE__) )?>" />
	<?php
		$in_member_cat = false;
		foreach($membership_levels as $level)
		{
	?>
    	<li id="membership-level-<?php echo $level->id?>">
        	<label class="selectit">
            	<input id="in-membership-level-<?php echo $level->id?>" type="checkbox" <?php if(in_array($level->id, $page_levels)) { ?>checked="checked"<?php } ?> name="page_levels[]" value="<?php echo $level->id?>" />
				<?php
					echo $level->name;
					//Check which categories are protected for this level
					$protectedcategories = $wpdb->get_col("SELECT category_id FROM $wpdb->pmpro_memberships_categories WHERE membership_id = $level->id");	
					//See if this post is in any of the level's protected categories
					if(in_category($protectedcategories, $post->id))
					{
						$in_member_cat = true;
						echo ' *';
					}
				?>
            </label>
        </li>
    <?php
		}
    ?>
    </ul>
	<?php if('post' == get_post_type($post) && $in_member_cat) { ?>
		<p class="pmpro_meta_notice">* <?php _e("This post is already protected for this level because it is within a category that requires membership.", "pmpro");?></p>
	<?php } ?>
<?php
}

//saves meta options
function pmpro_page_save($post_id)
{
	global $wpdb;

	if(empty($post_id))
		return false;
	
	if (!empty($_POST['pmpro_noncename']) && !wp_verify_nonce( $_POST['pmpro_noncename'], plugin_basename(__FILE__) )) {
		return $post_id;
	}

	// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want
	// to do anything
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
		return $post_id;

	// Check permissions
	if(!empty($_POST['post_type']) && 'page' == $_POST['post_type'] )
	{
		if ( !current_user_can( 'edit_page', $post_id ) )
			return $post_id;
	}
	else
	{
		if ( !current_user_can( 'edit_post', $post_id ) )
			return $post_id;
	}

	// OK, we're authenticated: we need to find and save the data	
	if(isset($_POST['pmpro_noncename']))
	{
		if(!empty($_POST['page_levels']))
			$mydata = $_POST['page_levels'];
		else
			$mydata = NULL;
	
		//remove all memberships for this page
		$wpdb->query("DELETE FROM {$wpdb->pmpro_memberships_pages} WHERE page_id = '$post_id'");

		//add new memberships for this page
		if(is_array($mydata))
		{
			foreach($mydata as $level)
				$wpdb->query("INSERT INTO {$wpdb->pmpro_memberships_pages} (membership_id, page_id) VALUES('" . intval($level) . "', '" . intval($post_id) . "')");
		}
	
		return $mydata;
	}
	else
		return $post_id;
}

//wrapper to add meta boxes
function pmpro_page_meta_wrapper()
{
	add_meta_box('pmpro_page_meta', __('Require Membership', 'pmpro'), 'pmpro_page_meta', 'page', 'side');
	add_meta_box('pmpro_page_meta', __('Require Membership', 'pmpro'), 'pmpro_page_meta', 'post', 'side');
}
if (is_admin())
{
	add_action('admin_menu', 'pmpro_page_meta_wrapper');
	add_action('save_post', 'pmpro_page_save');

	require_once(PMPRO_DIR . "/adminpages/dashboard.php");
}