<?php
/*
	Clean things up when deletes happen, etc. (This stuff needs a better home.)
*/
//deleting a user? remove their account info.
function pmpro_delete_user($user_id = NULL)
{
	global $wpdb;

	//changing their membership level to 0 will cancel any subscription and remove their membership level entry
	//we don't remove the orders because it would affect reporting
	if(pmpro_changeMembershipLevel(0, $user_id))
	{
		//okay
	}
	else
	{
		//okay, guessing they didn't have a level
	}
}
add_action('delete_user', 'pmpro_delete_user');
add_action('wpmu_delete_user', 'pmpro_delete_user');

//deleting a category? remove any level associations
function pmpro_delete_category($cat_id = NULL)
{
	global $wpdb;
	$sqlQuery = "DELETE FROM $wpdb->pmpro_memberships_categories WHERE category_id = '" . $cat_id . "'";
	$wpdb->query($sqlQuery);
}
add_action('delete_category', 'pmpro_delete_category');

//deleting a post? remove any level associations
function pmpro_delete_post($post_id = NULL)
{
	global $wpdb;		
	$sqlQuery = "DELETE FROM $wpdb->pmpro_memberships_pages WHERE page_id = '" . $post_id . "'";
	$wpdb->query($sqlQuery);
}
add_action('delete_post', 'pmpro_delete_post');