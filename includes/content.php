<?php
/*
	Functions to detect member content and protect it.
*/
function pmpro_has_membership_access($post_id = NULL, $user_id = NULL, $return_membership_levels = false)
{
	global $post, $wpdb, $current_user;
	//use globals if no values supplied
	if(!$post_id)
		$post_id = $post->ID;
	if(!$user_id)
		$user_id = $current_user->ID;

	//no post, return true (changed from false in version 1.7.2)
	if(!$post_id)
		return true;

	//if no post or current_user object, set them up
	if(!empty($post->ID) && $post_id == $post->ID)
		$mypost = $post;
	else
		$mypost = get_post($post_id);

	if($user_id == $current_user->ID)
		$myuser = $current_user;
	else
		$myuser = get_userdata($user_id);

	//for these post types, we want to check the parent
	if($mypost->post_type == "attachment" || $mypost->post_type == "revision")
	{
		$mypost = get_post($mypost->post_parent);
	}

	if($mypost->post_type == "post")
	{
		$post_categories = wp_get_post_categories($mypost->ID);

		if(!$post_categories)
		{
			//just check for entries in the memberships_pages table
			$sqlQuery = "SELECT m.id, m.name FROM $wpdb->pmpro_memberships_pages mp LEFT JOIN $wpdb->pmpro_membership_levels m ON mp.membership_id = m.id WHERE mp.page_id = '" . $mypost->ID . "'";
		}
		else
		{
			//are any of the post categories associated with membership levels? also check the memberships_pages table
			$sqlQuery = "(SELECT m.id, m.name FROM $wpdb->pmpro_memberships_categories mc LEFT JOIN $wpdb->pmpro_membership_levels m ON mc.membership_id = m.id WHERE mc.category_id IN(" . implode(",", $post_categories) . ") AND m.id IS NOT NULL) UNION (SELECT m.id, m.name FROM $wpdb->pmpro_memberships_pages mp LEFT JOIN $wpdb->pmpro_membership_levels m ON mp.membership_id = m.id WHERE mp.page_id = '" . $mypost->ID . "')";
		}
	}
	else
	{
		//are any membership levels associated with this page?
		$sqlQuery = "SELECT m.id, m.name FROM $wpdb->pmpro_memberships_pages mp LEFT JOIN $wpdb->pmpro_membership_levels m ON mp.membership_id = m.id WHERE mp.page_id = '" . $mypost->ID . "'";
	}


	$post_membership_levels = $wpdb->get_results($sqlQuery);

	$post_membership_levels_ids = array();
	$post_membership_levels_names = array();

	if(!$post_membership_levels)
	{
		$hasaccess = true;
	}
	else
	{
		//we need to see if the user has access
		foreach($post_membership_levels as $level)
		{
			$post_membership_levels_ids[] = $level->id;
			$post_membership_levels_names[] = $level->name;
		}

		//levels found. check if this is in a feed or if the current user is in at least one of those membership levels
		if(is_feed())
		{
			//always block restricted feeds
			$hasaccess = false;
		}
		elseif(!empty($myuser->ID))
		{
			$myuser->membership_level = pmpro_getMembershipLevelForUser($myuser->ID);
			if(!empty($myuser->membership_level->ID) && in_array($myuser->membership_level->ID, $post_membership_levels_ids))
			{
				//the users membership id is one that will grant access
				$hasaccess = true;
			}
			else
			{
				//user isn't a member of a level with access
				$hasaccess = false;
			}
		}
		else
		{
			//user is not logged in and this content requires membership
			$hasaccess = false;
		}
	}

	/*
		Filters
		The generic filter is run first. Then if there is a filter for this post type, that is run.
	*/
	//general filter for all posts
	$hasaccess = apply_filters("pmpro_has_membership_access_filter", $hasaccess, $mypost, $myuser, $post_membership_levels);
	//filter for this post type
	if(has_filter("pmpro_has_membership_access_filter_" . $mypost->post_type))
		$hasaccess = apply_filters("pmpro_has_membership_access_filter_" . $mypost->post_type, $hasaccess, $mypost, $myuser, $post_membership_levels);

	//return
	if($return_membership_levels)
		return array($hasaccess, $post_membership_levels_ids, $post_membership_levels_names);
	else
		return $hasaccess;
}

function pmpro_search_filter($query)
{
    global $current_user, $wpdb, $pmpro_pages;
	
    //hide pmpro pages from search results
    if(!$query->is_admin && $query->is_search)
    {
        $query->set('post__not_in', $pmpro_pages ); // id of page or post
    }
		
    //hide member pages from non-members (make sure they aren't hidden from members)    
	if(!$query->is_admin && !$query->is_singular)
    {
        //get page ids that are in my levels
        $levels = pmpro_getMembershipLevelsForUser($current_user->ID);
        $my_pages = array();

        if($levels) {
            foreach($levels as $key => $level) {
                //get restricted posts for level
                $sql = "SELECT page_id FROM $wpdb->pmpro_memberships_pages WHERE membership_id=" . $current_user->membership_level->ID;
                $member_pages = $wpdb->get_col($sql);
                $my_pages = array_unique(array_merge($my_pages, $member_pages));
            }
        }

        //get hidden page ids
        if(!empty($my_pages))
			$sql = "SELECT page_id FROM $wpdb->pmpro_memberships_pages WHERE page_id NOT IN(" . implode(',', $my_pages) . ")";
		else
			$sql = "SELECT page_id FROM $wpdb->pmpro_memberships_pages";
        $hidden_page_ids = array_values(array_unique($wpdb->get_col($sql)));

        if($hidden_page_ids)
            $query->set('post__not_in', $hidden_page_ids);

        //get categories that are filtered by level, but not my level
        $my_cats = array();

        if($levels) {
            foreach($levels as $key => $level) {
                $member_cats = pmpro_getMembershipCategories($level->id);
                $my_cats = array_unique(array_merge($my_cats, $member_cats));
            }
        }

        //get hidden cats
        if(!empty($my_cats))
			$sql = "SELECT category_id FROM $wpdb->pmpro_memberships_categories WHERE category_id NOT IN(" . implode(',', $my_cats) . ")";
		else
			$sql = "SELECT category_id FROM $wpdb->pmpro_memberships_categories";
					
        $hidden_cat_ids = array_values(array_unique($wpdb->get_col($sql)));

        //make this work
        if($hidden_cat_ids)
            $query->set('category__not_in', $hidden_cat_ids);
    }

    return $query;
}
$filterqueries = pmpro_getOption("filterqueries");
if(!empty($filterqueries))
    add_filter( 'pre_get_posts', 'pmpro_search_filter' );
    
function pmpro_membership_content_filter($content, $skipcheck = false)
{	
	global $post, $current_user;

	if(!$skipcheck)
	{
		$hasaccess = pmpro_has_membership_access(NULL, NULL, true);
		if(is_array($hasaccess))
		{
			//returned an array to give us the membership level values
			$post_membership_levels_ids = $hasaccess[1];
			$post_membership_levels_names = $hasaccess[2];
			$hasaccess = $hasaccess[0];
		}
	}

	if($hasaccess)
	{
		//all good, return content
		return $content;
	}
	else
	{
		//if show excerpts is set, return just the excerpt
		if(pmpro_getOption("showexcerpts"))
		{			
			//show excerpt
			global $post;
			if($post->post_excerpt)
			{								
				//defined exerpt
				$content = wpautop($post->post_excerpt);
			}
			elseif(strpos($content, "<span id=\"more-" . $post->ID . "\"></span>") !== false)
			{				
				//more tag
				$pos = strpos($content, "<span id=\"more-" . $post->ID . "\"></span>");
				$content = wpautop(substr($content, 0, $pos));
			}
			elseif(strpos($content, 'class="more-link">') !== false)
			{
				//more link
				$content = preg_replace("/\<a.*class\=\"more\-link\".*\>.*\<\/a\>/", "", $content);
			}
			else
			{
				//auto generated excerpt. pulled from wp_trim_excerpt
				$content = strip_shortcodes( $content );
				$content = str_replace(']]>', ']]&gt;', $content);
				$content = strip_tags($content);
				$excerpt_length = apply_filters('excerpt_length', 55);
				$words = preg_split("/[\n\r\t ]+/", $content, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
				if ( count($words) > $excerpt_length ) {
					array_pop($words);
					$content = implode(' ', $words);
					$content = $content . "... ";
				} else {
					$content = implode(' ', $words) . "... ";
				}

				$content = wpautop($content);
			}
		}
		else
		{
			//else hide everything
			$content = "";
		}

		if(empty($post_membership_levels_ids))
			$post_membership_levels_ids = array();

		if(empty($post_membership_levels_names))
			$post_membership_levels_names = array();

		$pmpro_content_message_pre = '<div class="pmpro_content_message">';
		$pmpro_content_message_post = '</div>';

		$sr_search = array("!!levels!!", "!!referrer!!");
		$sr_replace = array(pmpro_implodeToEnglish($post_membership_levels_names), $_SERVER['REQUEST_URI']);

		//get the correct message to show at the bottom
		if(is_feed())
		{
			$newcontent = apply_filters("pmpro_rss_text_filter", stripslashes(pmpro_getOption("rsstext")));
			$content .= $pmpro_content_message_pre . str_replace($sr_search, $sr_replace, $newcontent) . $pmpro_content_message_post;
		}
		elseif($current_user->ID)
		{
			//not a member
			$newcontent = apply_filters("pmpro_non_member_text_filter", stripslashes(pmpro_getOption("nonmembertext")));
			$content .= $pmpro_content_message_pre . str_replace($sr_search, $sr_replace, $newcontent) . $pmpro_content_message_post;
		}
		else
		{
			//not logged in!
			$newcontent = apply_filters("pmpro_not_logged_in_text_filter", stripslashes(pmpro_getOption("notloggedintext")));
			$content .= $pmpro_content_message_pre . str_replace($sr_search, $sr_replace, $newcontent) . $pmpro_content_message_post;
		}
	}

	return $content;
}
add_filter('the_content', 'pmpro_membership_content_filter', 5);
add_filter('the_content_rss', 'pmpro_membership_content_filter', 5);
add_filter('comment_text_rss', 'pmpro_membership_content_filter', 5);

/*
	If the_excerpt is called, we want to disable the_content filters so the PMPro messages aren't added to the content before AND after the ecerpt.
*/
function pmpro_membership_excerpt_filter($content, $skipcheck = false)
{		
	remove_filter('the_content', 'pmpro_membership_content_filter', 5);	
	$content = pmpro_membership_content_filter($content, $skipcheck);
	add_filter('the_content', 'pmpro_membership_content_filter', 5);
	
	return $content;
}
function pmpro_membership_get_excerpt_filter_start($content, $skipcheck = false)
{	
	remove_filter('the_content', 'pmpro_membership_content_filter', 5);		
	return $content;
}
function pmpro_membership_get_excerpt_filter_end($content, $skipcheck = false)
{	
	add_filter('the_content', 'pmpro_membership_content_filter', 5);		
	return $content;
}
add_filter('the_excerpt', 'pmpro_membership_excerpt_filter', 15);
add_filter('get_the_excerpt', 'pmpro_membership_get_excerpt_filter_start', 1);
add_filter('get_the_excerpt', 'pmpro_membership_get_excerpt_filter_end', 100);

function pmpro_comments_filter($comments, $post_id = NULL)
{
	global $post, $wpdb, $current_user;
	if(!$post_id)
		$post_id = $post->ID;

	if(!$comments)
		return $comments;	//if they are closed anyway, we don't need to check

	global $post, $current_user;

	$hasaccess = pmpro_has_membership_access(NULL, NULL, true);
	if(is_array($hasaccess))
	{
		//returned an array to give us the membership level values
		$post_membership_levels_ids = $hasaccess[1];
		$post_membership_levels_names = $hasaccess[2];
		$hasaccess = $hasaccess[0];
	}

	if($hasaccess)
	{
		//all good, return content
		return $comments;
	}
	else
	{
		if(!$post_membership_levels_ids)
			$post_membership_levels_ids = array();

		if(!$post_membership_levels_names)
			$post_membership_levels_names = array();

		//get the correct message
		if(is_feed())
		{
			if(is_array($comments))
				return array();
			else
				return false;
		}
		elseif($current_user->ID)
		{
			//not a member
			if(is_array($comments))
				return array();
			else
				return false;
		}
		else
		{
			//not logged in!
			if(is_array($comments))
				return array();
			else
				return false;
		}
	}

	return $comments;
}
add_filter("comments_array", "pmpro_comments_filter");
add_filter("comments_open", "pmpro_comments_filter");

//keep non-members from getting to certain pages (attachments, etc)
function pmpro_hide_pages_redirect()
{
	global $post;

	if(!is_admin() && !empty($post->ID))
	{
		if($post->post_type == "attachment")
		{
			//check if the user has access to the parent
			if(!pmpro_has_membership_access($post->ID))
			{
				wp_redirect(pmpro_url("levels"));
				exit;
			}
		}
	}
}
add_action('wp', 'pmpro_hide_pages_redirect');
