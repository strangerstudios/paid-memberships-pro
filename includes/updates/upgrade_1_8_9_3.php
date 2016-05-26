<?php
/*
	Fixing users who weren't given an expiration/enddate at checkout.
*/
function pmpro_upgrade_1_8_9_3($debug = true, $run = false) {
	global $wpdb;	
	
	//some vars
	$all_levels = pmpro_getAllLevels(true, true);

	if($debug && $run)
		echo "Running in live mode. The database WILL be updated.\n-----\n";
	elseif($debug)
		echo "Running in test mode. The database WILL NOT be updated.\n-----\n";

	//get all active users during the period where things may have been broken
	$user_ids = $wpdb->get_col("SELECT user_id FROM $wpdb->pmpro_memberships_users WHERE status = 'active' AND modified > '2016-05-19'");

	if(!empty($user_ids)) {
		foreach($user_ids as $user_id) {
			$user = get_userdata($user_id);
			
			//user not found for some reason
			if(empty($user)) {
				if($debug)
					echo "User #" . $user_id . " not found.\n";
				continue;
			}

			//get level
			$user->membership_level = pmpro_getMembershipLevelForUser($user->ID);

			//has a start and end date already
			if(!empty($user->membership_level->enddate) && !empty($user->membership_level->startdate)) {
				if($debug)
					echo "User #" . $user_id . " already has a start and end date.\n";
				continue;
			}

			//get order
			$last_order = new MemberOrder();
			$last_order->getLastMemberOrder();

			/*
				Figure out if this user should have been given an end date.
				The level my have an end date.
				They might have used a discount code.
				They might be using the set-expiration-dates code.
				They might have custom code setting the end date.

				Let's setup some vars as if we are at checkout.
				Then pass recreate the level with the pmpro_checkout_level filter.
				And use the end date there if there is one.
			*/
			global $pmpro_level, $discount_code, $discount_code_id;
			
			//level
			$level_id = $user->membership_level->id;
			$_REQUEST['level'] = $level_id;

			//gateway
			if(!empty($last_order) && !empty($last_order->gateway))
				$_REQUEST['gateway'] = $last_order->gateway;
			else
				$_REQUEST['gateway'] = pmpro_getGateway();

			//discount code
			$discount_code_id = $user->membership_level->code_id;
			$discount_code = $wpdb->get_var( "SELECT code FROM $wpdb->pmpro_discount_codes WHERE id = '" . $discount_code_id . "' LIMIT 1" );

			//get level
			if(!empty($discount_code_id)) {
				$sqlQuery    = "SELECT l.id, cl.*, l.name, l.description, l.allow_signups FROM $wpdb->pmpro_discount_codes_levels cl LEFT JOIN $wpdb->pmpro_membership_levels l ON cl.level_id = l.id LEFT JOIN $wpdb->pmpro_discount_codes dc ON dc.id = cl.code_id WHERE dc.code = '" . $discount_code . "' AND cl.level_id = '" . (int) $level_id . "' LIMIT 1";
		$pmpro_level = $wpdb->get_row( $sqlQuery );

				//if the discount code doesn't adjust the level, let's just get the straight level
				if ( empty( $pmpro_level ) ) {
					$pmpro_level = $all_levels[$level_id];
				}

				//filter adjustments to the level
				$pmpro_level->code_id = $discount_code_id;
				$pmpro_level          = apply_filters( "pmpro_discount_code_level", $pmpro_level, $discount_code_id );
			}

			//no level yet, use default
			if ( empty( $pmpro_level ) ) {
				$pmpro_level = $all_levels[$level_id];
			}

			//no level for some reason
			if(empty($pmpro_level) && empty($pmpro_level->id)) {
				if($debug)
					echo "No level found with ID #" . $level_id . " for user #" . $user_id . ".\n";
				continue;
			}

			//filter level
			$pmpro_level = apply_filters( "pmpro_checkout_level", $pmpro_level );

			if($debug)
				echo "Fixing user #" . $user_id . ". "; 

			//calculate and fix start date
			if(empty($user->membership_level->startdate)) {
				$startdate = $wpdb->get_var("SELECT modified FROM $wpdb->pmpro_memberships_users WHERE user_id = $user_id AND membership_id = $level_id AND status = 'active' LIMIT 1");

				//filter
				$filtered_startdate = apply_filters( "pmpro_checkout_start_date", $startdate, $user_id, $pmpro_level );

				//only use filtered value if it's not 0
				if(!empty($filtered_startdate) && $filtered_startdate != '0000-00-00 00:00:00' && $filtered_startdate != "'0000-00-00 00:00:00'")
					$startdate = $filtered_startdate;

				if($debug)
					echo "Adding startdate " . $startdate . ". ";
				if($run) {
					$sqlQuery = "UPDATE $wpdb->pmpro_memberships_users SET startdate = '" . esc_sql($startdate) . "' WHERE user_id = $user_id AND membership_id = $level_id AND status = 'active' LIMIT 1";
					$wpdb->query($sqlQuery);
				}
			} else {
				$startdate = date( "Y-m-d", $user->membership_level->startdate );
			}
			
			//calculate and fix the end date
			if(empty($user->membership_level->enddate)) {
				if ( ! empty( $pmpro_level->expiration_number ) ) {
					$enddate =  date( "Y-m-d", strtotime( "+ " . $pmpro_level->expiration_number . " " . $pmpro_level->expiration_period, current_time( "timestamp" ) ) );
				} else {
					$enddate = "NULL";
				}

				$enddate = apply_filters( "pmpro_checkout_end_date", $enddate, $user_id, $pmpro_level, $startdate );

				if(!empty($enddate) && $enddate != "NULL") {
					if($debug)
						echo "Adding enddate " . $enddate . ".";
					if($run) {
						$sqlQuery = "UPDATE $wpdb->pmpro_memberships_users SET enddate = '" . esc_sql($enddate) . "' WHERE user_id = $user_id AND membership_id = $level_id AND status = 'active' LIMIT 1";
						$wpdb->query($sqlQuery);
					}
				}
			}

			//clear vars for next pass
			$user_id = NULL;
			$level_id = NULL;
			$discount_code = NULL;
			$discount_code_id = NULL;
			$pmpro_level = NULL;
			$last_order = NULL;
			$startdate = NULL;
			$filtered_startdate = NULL;
			$enddate = NULL;

			echo "\n";
		}
	}
}

/*
	Add Menu Item
*/
function pmprou_1_8_9_3_add_pages()
{	
	//todo don't add the page if the fix is done or skipped
	add_submenu_page('pmpro-membershiplevels', __('Update Required', 'pmpro'), __('Update Required *', 'pmpro'), 'manage_options', 'pmpro-updaterequired', 'pmprou_1_8_9_3_page');
}
add_action('admin_menu', 'pmprou_1_8_9_3_add_pages');
function pmprou_1_8_9_3_page()
{
	//only admins can get this
	$cap = apply_filters('pmpro_add_member_cap', 'edit_users');
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can($cap)))
	{
		die(__("You do not have permissions to perform this action.", "pmpro"));
	}	
	
	global $wpdb;
	require_once(PMPRO_DIR . "/adminpages/admin_header.php");

	$nusers = $wpdb->get_var("SELECT COUNT(user_id) FROM $wpdb->pmpro_memberships_users WHERE status = 'active' AND modified > '2016-05-19'");
	?>
	<style>
		div.pmpro_admin #message {display: none;}
	</style>
	<h2><?php _e('PMPro Update - Fixing Start and End Dates For Recent Members', 'pmpro');?></h2>

	<p><?php _e("Paid Memberships Pro version 1.8.9.2, pushed out on May 20th, included a bug that affected all sites running PMPro. Members who checked out under this version were often assigned incorrect start and end dates.", 'pmpro');?></p>
	<p><?php _e("Clicking the 'Run Fix in Live Mode' button below will find all users who checked out after May 20th and recalculate their start and end dates. For most sites, this script should run quickly and accurately. The script uses the same filters as a normal checkout and should account for addons and custom code you are running that affects start and end dates.", 'pmpro');?>
	<p><?php _e("If you are using a large number of addons or custom code that affects users' start and end dates, you will definitely want to run the script in test mode to confirm the results first.", 'pmpro');?></p>
	<p><?php _e(" All users should back up their database before running the fix in live mode.", 'pmpro');?></p>

	<hr />

	<p><?php sprintf(__('We detect %s potentially affected user(s) that need to be fixed.', 'pmpro'), $nusers);?></p>
	
	<?php
		if(!empty($_REQUEST['run']) || !empty($_REQUEST['test'])) {
			?><textarea id="pmpro_updates_status" rows="10" cols="60"><?php
			
			if(!empty($_REQUEST['test'])) {
				//run in test mode
				pmpro_upgrade_1_8_9_3(true, false);
			} elseif(!empty($_REQUEST['live'])) {
				//run in live mode
				pmpro_upgrade_1_8_9_3(true, true);

				//set option to hide this update
				update_option('pmpro_update_1_8_9_3', 'fixed', 'no');
			}

			?></textarea><hr /><?php
		} elseif(!empty($_REQUEST['skip'])) {
			update_option('pmpro_update_1_8_9_3', 'skipped', 'no');
			?><p><?php _e('You have chosen to skip the fix. You may now navigate away from this page.', 'pmpro');?></p><?php
		}
	?>

	<?php if(empty($_REQUEST['skip'])) { ?>
	<ul>
		<li><a href="<?php echo admin_url('admin.php?page=pmpro-updaterequired&test=1');?>"><?php _e("Run the fix in TEST mode without updating the database.", 'pmpro');?></a></li>
		<li><a href="<?php echo admin_url('admin.php?page=pmpro-updaterequired&live=1');?>"><?php _e("Run the fix in LIVE mode.", 'pmpro');?></a></li>
		<li><a href="<?php echo admin_url('admin.php?page=pmpro-updaterequired&skip=1');?>"><?php _e("Skip this update without running the fix.", 'pmpro');?></a></li>
	</ul>
	<?php } ?>

	<?php
	require_once(PMPRO_DIR . "/adminpages/admin_footer.php");
}

function pmprou_1_8_9_3_admin_init() {
	//redirect to update if not skipped yet
	$update = get_option('pmpro_update_1_8_9_3', false);
	if(empty($update) && !defined('DOING_AJAX') && current_user_can('manage_options') && (empty($_REQUEST['page']) || $_REQUEST['page'] != 'pmpro-updaterequired')) {
		wp_redirect(admin_url('admin.php?page=pmpro-updaterequired'));
		exit;
	}
}
add_action('admin_init', 'pmprou_1_8_9_3_admin_init');