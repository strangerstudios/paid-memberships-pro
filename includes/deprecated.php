<?php
/**
 * Deprecated hooks, filters and functions
 *
 * @since  2.0
 */

/**
 * Check for deprecated filters.
 */
function pmpro_init_check_for_deprecated_filters() {
	global $wp_filter;

	// Deprecated filter name => new filter name (or null if there is no alternative).
	$pmpro_map_deprecated_filters = array(
		'pmpro_getfile_extension_blacklist' => 'pmpro_getfile_extension_blocklist',
		'pmpro_default_field_group_label'   => 'pmprorh_section_header',
		'pmpro_stripe_subscription_deleted' => null,
		'pmpro_subscription_cancelled'      => null,
	);
	
	foreach ( $pmpro_map_deprecated_filters as $old => $new ) {
		if ( has_filter( $old ) ) {
			if ( ! empty( $new ) ) {
				// We have an alternative filter. Let's show an error message and forward to that new filter.
				/* translators: 1: the old hook name, 2: the new or replacement hook name */
				trigger_error( esc_html( sprintf( esc_html__( 'The %1$s hook has been deprecated in Paid Memberships Pro. Please use the %2$s hook instead.', 'paid-memberships-pro' ), $old, $new ) ) );
				
				// Add filters back using the new tag.
				foreach( $wp_filter[$old]->callbacks as $priority => $callbacks ) {
					foreach( $callbacks as $callback ) {
						add_filter( $new, $callback['function'], $priority, $callback['accepted_args'] ); 
					}
				}
			} else {
				// We don't have an alternative filter. Let's just show an error message.
				/* translators: 1: the old hook name */
				trigger_error( esc_html( sprintf( esc_html__( 'The %1$s hook has been deprecated in Paid Memberships Pro and may not be available in future versions.', 'paid-memberships-pro' ), $old ) ) );
			}
		}
	}
}
add_action( 'init', 'pmpro_init_check_for_deprecated_filters', 99 );

/**
 * Previously used function for class definitions for input fields to see if there was an error.
 *
 * To filter field values, we now recommend using the `pmpro_element_class` filter.
 *
 */
function pmpro_getClassForField( $field ) {
	return pmpro_get_element_class( '', $field );
}

/**
 * Redirect some old menu items to their new location
 */
function pmpro_admin_init_redirect_old_menu_items() {	
	if ( is_admin()
		&& ! empty( $_REQUEST['page'] ) && $_REQUEST['page'] == 'pmpro_license_settings'
		&& basename( sanitize_text_field( $_SERVER['SCRIPT_NAME'] ) ) == 'options-general.php' ) {
		wp_safe_redirect( admin_url( 'admin.php?page=pmpro-license' ) );
		exit;
	}
}
add_action( 'init', 'pmpro_admin_init_redirect_old_menu_items' );

/**
 * Old Register Helper functions and classes.
 */
function pmpro_register_helper_deprecated() {
	// Activated plugins run after plugins_loaded. Bail to be safe.
	if ( pmpro_activating_plugin( 'pmpro-register-helper/pmpro-register-helper.php' ) ) {
		return;
	}
	
	// PMProRH_Field class
	if ( ! class_exists( 'PMProRH_Field' ) ) {
		class PMProRH_Field extends PMPro_Field {
			// Just do what PMPro_Field does.
		}
	}
	
	// pmprorh_add_registration_field function
	if ( ! function_exists( 'pmprorh_add_registration_field' ) ) {		
		function pmprorh_add_registration_field( $where, $field ) {
			return pmpro_add_user_field( $where, $field );
		}
	}
	
	// pmprorh_add_checkout_box function
	if ( ! function_exists( 'pmprorh_add_checkout_box' ) ) {
		function pmprorh_add_checkout_box( $name, $label = NULL, $description = '', $order = NULL ) {
			return pmpro_add_field_group( $name, $label, $description, $order );
		}
	}
	
	// pmprorh_add_user_taxonomy
	if ( ! function_exists( 'pmprorh_add_user_taxonomy' ) ) {
		function pmprorh_add_user_taxonomy( $name, $name_plural ) {
			return pmpro_add_user_taxonomy( $name, $name_plural );
		}
	}
	
	// pmprorh_getCheckoutBoxByName function
	if ( ! function_exists( 'pmprorh_getCheckoutBoxByName' ) ) {
		function pmprorh_getCheckoutBoxByName( $name ) {
			return pmpro_get_field_group_by_name( $name );
		}
	}
	
	// pmprorh_getCSVFields function
	if ( ! function_exists( 'pmprorh_getCSVFields' ) ) {
		function pmprorh_getCSVFields() {
			return pmpro_get_user_fields_for_csv();
		}
	}
	
	// pmprorh_getProfileFields function
	if ( ! function_exists( 'pmprorh_getProfileFields' ) ) {
		function pmprorh_getProfileFields( $user_id, $withlocations = false  ) {
			return pmpro_get_user_fields_for_profile( $user_id, $withlocations );
		}
	}
	
	// pmprorh_checkFieldForLevel function
	if ( ! function_exists( 'pmprorh_checkFieldForLevel' ) ) {
		function pmprorh_checkFieldForLevel( $field, $scope = 'default', $args = NULL ) {
			return pmpro_check_field_for_level( $field, $scope, $args );
		}
	}
	
	// pmprorh_end function
	if ( ! function_exists( 'pmprorh_end' ) ) {
		function pmprorh_end( $array ) {
			return pmpro_array_end( $array );
		}
	}
	
	// pmprorh_sanitize function
	if ( ! function_exists( 'pmprorh_sanitize' ) ) {
		function pmprorh_sanitize( $value, $field = null  ) {
			return pmpro_sanitize( $value, $field );
		}
	}
}
add_action( 'plugins_loaded', 'pmpro_register_helper_deprecated', 20 );
/**
 * Old Multiple Memberships Per User functions and classes.
 */
function pmpro_multiple_memberships_per_user_deprecated() {
	// MemberInvoice class.
	if ( ! class_exists( 'MemberInvoice' ) ) {
		class MemberInvoice extends MemberOrder {
			// Show deprecation warning in constructor.
			public function __construct() {
				_deprecated_function( __CLASS__, '3.0', 'MemberOrder' );
			}

			function getLastMemberInvoice( $user_id = NULL, $status = 'success' ) {
				_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '3.0', 'MemberOrder::getLastMemberOrder' );
				return $this->getLastMemberOrder( $user_id, $status );
			}
		}
	}

	// pmprommpu_load_plugin_text_domain function.
	if ( ! function_exists( 'pmprommpu_load_plugin_text_domain' ) ) {
		function pmprommpu_load_plugin_text_domain() {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmprommpu_activation function.
	if ( ! function_exists( 'pmprommpu_activation' ) ) {
		function pmprommpu_activation() {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmprommpu_deactivation function.
	if ( ! function_exists( 'pmprommpu_deactivation' ) ) {
		function pmprommpu_deactivation() {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmprommpu_init function.
	if ( ! function_exists( 'pmprommpu_init' ) ) {
		function pmprommpu_init() {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmprommpu_send_checkout_emails function.
	if ( ! function_exists( 'pmprommpu_send_checkout_emails' ) ) {
		function pmprommpu_send_checkout_emails() {
			_deprecated_function( __FUNCTION__, '3.0' );
			return false;
		}
	}

	// pmprommpu_setDBTables function.
	if ( ! function_exists( 'pmprommpu_setDBTables' ) ) {
		function pmprommpu_setDBTables() {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmprommpu_is_loaded function.
	if ( ! function_exists( 'pmprommpu_is_loaded' ) ) {
		function pmprommpu_is_loaded() {
			_deprecated_function( __FUNCTION__, '3.0' );
			return true;
		}
	}

	// pmprommpu_plugin_dir function.
	if ( ! function_exists( 'pmprommpu_plugin_dir' ) ) {
		function pmprommpu_plugin_dir() {
			_deprecated_function( __FUNCTION__, '3.0' );
			return '';
		}
	}

	// pmprommpu_get_groups function.
	if ( ! function_exists( 'pmprommpu_get_groups' ) ) {
		function pmprommpu_get_groups() {
			_deprecated_function( __FUNCTION__, '3.0', 'pmpro_get_level_groups' );
			return pmpro_get_level_groups();
		}
	}

	// pmprommpu_create_group function.
	if ( ! function_exists( 'pmprommpu_create_group' ) ) {
		function pmprommpu_create_group( $name, $allowmulti ) {
			_deprecated_function( __FUNCTION__, '3.0', 'pmpro_create_level_group' );
			return pmpro_create_level_group( $name, $allowmulti );
		}
	}

	// pmprommpu_set_level_for_group function.
	if ( ! function_exists( 'pmprommpu_set_level_for_group' ) ) {
		function pmprommpu_set_level_for_group( $level_id, $group_id ) {
			_deprecated_function( __FUNCTION__, '3.0', 'pmpro_add_level_to_group' );
			return pmpro_add_level_to_group( $level_id, $group_id );
		}
	}

	// pmprommpu_get_levels_and_groups_in_order function.
	if ( ! function_exists( 'pmprommpu_get_levels_and_groups_in_order' ) ) {
		function pmprommpu_get_levels_and_groups_in_order( $includehidden = false ) {
			_deprecated_function( __FUNCTION__, '3.0', 'pmpro_get_level_groups_in_order' );
			global $wpdb;
			$retarray = array();
			$pmpro_levels = pmpro_getAllLevels($includehidden, true);
			$pmpro_level_order = get_option('pmpro_level_order');
			$pmpro_levels = apply_filters('pmpro_levels_array', $pmpro_levels );
			$include = array();
			foreach( $pmpro_levels as $level ) {
				$include[] = $level->id;
			}
			$included = esc_sql( implode(',', $include) );
			$order = array();
			if(! empty($pmpro_level_order)) { $order = explode(',', $pmpro_level_order); }
			$grouplist = $wpdb->get_col("SELECT id FROM {$wpdb->pmpro_groups} ORDER BY displayorder, id ASC");
			if($grouplist) {
				foreach($grouplist as $curgroup) {
					$curgroup = intval($curgroup);
					$levelsingroup = $wpdb->get_col(
						$wpdb->prepare( "
							SELECT level 
							FROM {$wpdb->pmpro_membership_levels_groups} AS mlg 
							INNER JOIN {$wpdb->pmpro_membership_levels} AS ml ON ml.id = mlg.level AND ml.allow_signups LIKE %s
							WHERE mlg.group = %d 
							AND ml.id IN (" . $included ." )
							ORDER BY level ASC",
						($includehidden ? '%' : 1),
						$curgroup
						)
					);
					if(count($order)>0) {
						$mylevels = array();
						foreach($order as $level_id) {
							if(in_array($level_id, $levelsingroup)) { $mylevels[] = $level_id; }
						}
						$retarray[$curgroup] = $mylevels;
					} else {
						$retarray[$curgroup] = $levelsingroup;
					}
				}
			}
			return $retarray;
		}
	}

	// pmprommpu_gateway_supports_multiple_level_checkout function.
	if ( ! function_exists( 'pmprommpu_gateway_supports_multiple_level_checkout' ) ) {
		function pmprommpu_gateway_supports_multiple_level_checkout( $gateway ) {
			_deprecated_function( __FUNCTION__, '3.0' );
			return false;
		}
	}

	// pmprommpu_override_user_pages function.
	if ( ! function_exists( 'pmprommpu_override_user_pages' ) ) {
		function pmprommpu_override_user_pages() {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmprommpu_get_group_for_level function.
	if ( ! function_exists( 'pmprommpu_get_group_for_level' ) ) {
		function pmprommpu_get_group_for_level( $level_id ) {
			_deprecated_function( __FUNCTION__, '3.0', 'pmpro_get_group_id_for_level' );
			return pmpro_get_group_id_for_level( $level_id );
		}
	}

	// pmprommpu_set_group_for_level function.
	if ( ! function_exists( 'pmprommpu_set_group_for_level' ) ) {
		function pmprommpu_set_group_for_level( $level_id, $group_id ) {
			_deprecated_function( __FUNCTION__, '3.0', 'pmpro_add_level_to_group' );
			return pmpro_add_level_to_group( $level_id, $group_id );
		}
	}

	// pmprommpu_add_group function.
	if ( ! function_exists( 'pmprommpu_add_group' ) ) {
		function pmprommpu_add_group() {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmprommpu_edit_group function.
	if ( ! function_exists( 'pmprommpu_edit_group' ) ) {
		function pmprommpu_edit_group() {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmprommpu_del_group function.
	if ( ! function_exists( 'pmprommpu_del_group' ) ) {
		function pmprommpu_del_group() {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmprommpu_update_level_and_group_order function.
	if ( ! function_exists( 'pmprommpu_update_level_and_group_order' ) ) {
		function pmprommpu_update_level_and_group_order() {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmprommpu_get_levels_from_latest_checkout function.
	if ( ! function_exists( 'pmprommpu_get_levels_from_latest_checkout' ) ) {
		function pmprommpu_get_levels_from_latest_checkout() {
			_deprecated_function( __FUNCTION__, '3.0' );
			global $wpdb, $current_user;
			if(empty($user_id))
			{
				$user_id = $current_user->ID;
			}
			if(empty($user_id))
			{
				return [];
			}
			//make sure user id is int for security
			$user_id = intval($user_id);
			$retval = array();
			$all_levels = pmpro_getAllLevels(true, true);
			$checkoutid = intval($checkout_id);
			if($checkoutid<1) {
				$checkoutid = $wpdb->get_var("SELECT MAX(checkout_id) FROM $wpdb->pmpro_membership_orders WHERE user_id=$user_id");
				if(empty($checkoutid) || intval($checkoutid)<1) { return $retval; }
			}
			$querySql = "SELECT membership_id FROM $wpdb->pmpro_membership_orders WHERE checkout_id = " . esc_sql( $checkoutid ) . " AND ( gateway = 'free' OR ";
			if(!empty($statuses_to_check) && is_array($statuses_to_check)) {
				$querySql .= "status IN('" . implode("','", $statuses_to_check) . "') ";
			} elseif(!empty($statuses_to_check)) {
				$querySql .= "status = '" . esc_sql($statuses_to_check) . "' ";
			} else {
				$querySql .= "status = 'success'";
			}
			$querySql .= " )";
			$levelids = $wpdb->get_col($querySql);
			foreach($levelids as $thelevel) {
				if(array_key_exists($thelevel, $all_levels)) {
					$retval[] = $all_levels[$thelevel];
				}
			}
			return $retval;
		}
	}

	// pmprommpu_join_with_and function.
	if ( ! function_exists( 'pmprommpu_join_with_and' ) ) {
		function pmprommpu_join_with_and( $array ) {
			_deprecated_function( __FUNCTION__, '3.0' );
			$outstring = "";
			if(!is_array($inarray) || count($inarray)<1) { return $outstring; }
			$lastone = array_pop($inarray);
			if(count($inarray)>0) {
				$outstring .= implode(', ', $inarray);
				if(count($inarray)>1) { $outstring .= ', '; } else { $outstring .= " "; }
				$outstring .= "and ";
			}
			$outstring .= "$lastone";
			return $outstring;
		}
	}

	// pmprommpu_hasMembershipGroup function.
	if ( ! function_exists( 'pmprommpu_hasMembershipGroup' ) ) {
		function pmprommpu_hasMembershipGroup( $groups = null, $user_id = null ) {
			_deprecated_function( __FUNCTION__, '3.0', 'pmpro_has_membership_access' );
			global $current_user, $wpdb;
			//assume false
			$return = false;
			//default to current user
			if(empty($user_id)) {
				$user_id = $current_user->ID;
			}
			//get membership levels (or not) for given user
			if(!empty($user_id) && is_numeric($user_id))
				$membership_levels = pmpro_getMembershipLevelsForUser($user_id);
			else
				$membership_levels = NULL;
			//make an array out of a single element so we can use the same code
			if(!is_array($groups)) {
				$groups = array($groups);
			}
			//no levels, so no groups
			if(empty($membership_levels)) {
				$return = false;
			} else {
				//we have levels, so test against groups given
				foreach($groups as $group_id) {
					foreach($membership_levels as $level) {
						$levelgroup = pmprommpu_get_group_for_level($level->id);
						if($levelgroup == $group_id) {
							$return = true;	//found one!
							break 2;
						}
					}
				}
			}
			//filter just in case
			$return = apply_filters("pmprommpu_has_membership_group", $return, $user_id, $groups);
			return $return;
		}
	}

	// pmprommpu_addMembershipLevel function.
	if ( ! function_exists( 'pmprommpu_addMembershipLevel' ) ) {
		function pmprommpu_addMembershipLevel( $level_id, $user_id = null ) {
			_deprecated_function( __FUNCTION__, '3.0', 'pmpro_changeMembershipLevel' );
			pmpro_changeMembershipLevel( $level_id, $user_id );
		}
	}

	// pmprommpu_init_checkout_levels function.
	if ( ! function_exists( 'pmprommpu_init_checkout_levels' ) ) {
		function pmprommpu_init_checkout_levels() {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmprommpu_template_redirect_dupe_level_check function.
	if ( ! function_exists( 'pmprommpu_template_redirect_dupe_level_check' ) ) {
		function pmprommpu_template_redirect_dupe_level_check() {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmprommpu_addin_jquery_dialog function.
	if ( ! function_exists( 'pmprommpu_addin_jquery_dialog' ) ) {
		function pmprommpu_addin_jquery_dialog() {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmprommpu_frontend_scripts function.
	if ( ! function_exists( 'pmprommpu_frontend_scripts' ) ) {
		function pmprommpu_frontend_scripts() {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmprommpu_checkout_level_text function.
	if ( ! function_exists( 'pmprommpu_checkout_level_text' ) ) {
		function pmprommpu_checkout_level_text( $level_text ) {
			_deprecated_function( __FUNCTION__, '3.0' );
			return $level_text;
		}
	}

	// pmprommpu_registration_checks_single_level function.
	if ( ! function_exists( 'pmprommpu_registration_checks_single_level' ) ) {
		function pmprommpu_registration_checks_single_level( $continue ) {
			_deprecated_function( __FUNCTION__, '3.0' );
			return $continue;
		}
	}

	// pmprommpu_pmpro_deactivate_old_levels function.
	if ( ! function_exists( 'pmprommpu_pmpro_deactivate_old_levels' ) ) {
		function pmprommpu_pmpro_deactivate_old_levels( $deactivate ) {
			_deprecated_function( __FUNCTION__, '3.0' );
			return $deactivate;
		}
	}

	// pmprommpu_pmpro_cancel_previous_subscriptions function.
	if ( ! function_exists( 'pmprommpu_pmpro_cancel_previous_subscriptions' ) ) {
		function pmprommpu_pmpro_cancel_previous_subscriptions( $cancel ) {
			_deprecated_function( __FUNCTION__, '3.0' );
			return $cancel;
		}
	}

	// pmprommpu_pmpro_after_checkout function.
	if ( ! function_exists( 'pmprommpu_pmpro_after_checkout' ) ) {
		function pmprommpu_pmpro_after_checkout( $user_id, $checkout_statuses ) {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmprommpu_pmpro_membership_levels_table function.
	if ( ! function_exists( 'pmprommpu_pmpro_membership_levels_table' ) ) {
		function pmprommpu_pmpro_membership_levels_table( $html ) {
			_deprecated_function( __FUNCTION__, '3.0' );
			return $html;
		}
	}

	// pmprommpu_add_group_to_level_options function.
	if ( ! function_exists( 'pmprommpu_add_group_to_level_options' ) ) {
		function pmprommpu_add_group_to_level_options() {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmprommpu_save_group_on_level_edit function.
	if ( ! function_exists( 'pmprommpu_save_group_on_level_edit' ) ) {
		function pmprommpu_save_group_on_level_edit( $level_id ) {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmprommpu_on_del_level function.
	if ( ! function_exists( 'pmprommpu_on_del_level' ) ) {
		function pmprommpu_on_del_level( $level_id ) {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmprommpu_stop_default_checkout_emails function.
	if ( ! function_exists( 'pmprommpu_stop_default_checkout_emails' ) ) {
		function pmprommpu_stop_default_checkout_emails( $send ) {
			_deprecated_function( __FUNCTION__, '3.0' );
			return $send;
		}
	}

	// pmprommpu_show_multiple_levels_in_memlist function.
	if ( ! function_exists( 'pmprommpu_show_multiple_levels_in_memlist' ) ) {
		function pmprommpu_show_multiple_levels_in_memlist( $inuser ) {
			_deprecated_function( __FUNCTION__, '3.0' );
			return $inuser;
		}
	}

	// pmprommpu_memberslist_extra_cols function.
	if ( ! function_exists( 'pmprommpu_memberslist_extra_cols' ) ) {
		function pmprommpu_memberslist_extra_cols( $cols ) {
			_deprecated_function( __FUNCTION__, '3.0' );
			return $cols;
		}
	}

	// pmprommpu_fill_memberslist_col_member_number function.
	if ( ! function_exists( 'pmprommpu_fill_memberslist_col_member_number' ) ) {
		function pmprommpu_fill_memberslist_col_member_number() {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmprommpu_set_checkout_id function.
	if ( ! function_exists( 'pmprommpu_set_checkout_id' ) ) {
		function pmprommpu_set_checkout_id() {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmprommpu_pmpro_require_billing function.
	if ( ! function_exists( 'pmprommpu_pmpro_require_billing' ) ) {
		function pmprommpu_pmpro_require_billing( $require_billing ) {
			_deprecated_function( __FUNCTION__, '3.0' );
			return $require_billing;
		}
	}

	// pmprommpu_init_profile_hooks function.
	if ( ! function_exists( 'pmprommpu_init_profile_hooks' ) ) {
		function pmprommpu_init_profile_hooks() {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmprommpu_membership_level_profile_fields function.
	if ( ! function_exists( 'pmprommpu_membership_level_profile_fields' ) ) {
		function pmprommpu_membership_level_profile_fields( $user ) {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmprommpu_membership_level_profile_fields_update function.
	if ( ! function_exists( 'pmprommpu_membership_level_profile_fields_update' ) ) {
		function pmprommpu_membership_level_profile_fields_update() {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmprommpu_setup_and_upgrade function.
	if ( ! function_exists( 'pmprommpu_setup_and_upgrade' ) ) {
		function pmprommpu_setup_and_upgrade() {
			_deprecated_function( __FUNCTION__, '3.0', 'pmpro_db_delta' );
			pmpro_db_delta();
		}
	}

	// pmprommpu_db_delta function.
	if ( ! function_exists( 'pmprommpu_db_delta' ) ) {
		function pmprommpu_db_delta() {
			_deprecated_function( __FUNCTION__, '3.0', 'pmpro_db_delta' );
			pmpro_db_delta();
		}
	}

	// pmprommpu_setup_v1 function.
	if ( ! function_exists( 'pmprommpu_setup_v1' ) ) {
		function pmprommpu_setup_v1() {
			_deprecated_function( __FUNCTION__, '3.0', 'pmpro_db_delta' );
			pmpro_db_delta();
		}
	}
}
add_action( 'plugins_loaded', 'pmpro_multiple_memberships_per_user_deprecated', 20 );

/**
 * Old Stripe Billing Limits functions.
 */
function pmpro_stripe_billing_limits_deprecated() {
	if ( ! function_exists( 'pmprosbl_pmpro_added_order' ) ) {
		function pmprosbl_pmpro_added_order() {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	if ( ! function_exists( 'pmprosbl_pmpro_stripe_subscription_deleted' ) ) {
		function pmprosbl_pmpro_stripe_subscription_deleted() {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	if ( ! function_exists( 'pmprosbl_is_billing_limit_reached' ) ) {
		function pmprosbl_is_billing_limit_reached( $order ) {
			_deprecated_function( __FUNCTION__, '3.0' );

			// Get the subscription for this order.
			$subscription = $order->get_subscription();
			if ( empty( $subscription ) ) {
				return false;
			}

			return $subscription->billing_limit_reached();
		}
	}

	if ( ! function_exists( 'pmprosbl_plugin_row_meta' ) ) {
		function pmprosbl_plugin_row_meta() {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}
}
add_action( 'plugins_loaded', 'pmpro_stripe_billing_limits_deprecated', 20 );

/**
 * Old Cancel On Next Payment Date functions.
 */
function pmpro_cancel_on_next_payment_date_deprecated() {
	// pmproconpd_load_text_domain function.
	if ( ! function_exists( 'pmproconpd_load_text_domain' ) ) {
		function pmproconpd_load_text_domain() {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmproconpd_pmpro_change_level function.
	if ( ! function_exists( 'pmproconpd_pmpro_change_level' ) ) {
		function pmproconpd_pmpro_change_level( $level_id, $user_id ) {
			_deprecated_function( __FUNCTION__, '3.0' );
		}
	}

	// pmproconpd_gettext_cancel_text function.
	if ( ! function_exists( 'pmproconpd_gettext_cancel_text' ) ) {
		function pmproconpd_gettext_cancel_text( $translated_text, $text, $domain ) {
			_deprecated_function( __FUNCTION__, '3.0' );
			return $translated_text;
		}
	}

	// pmproconpd_pmpro_email_body function.
	if ( ! function_exists( 'pmproconpd_pmpro_email_body' ) ) {
		function pmproconpd_pmpro_email_body( $body, $email ) {
			_deprecated_function( __FUNCTION__, '3.0' );
			return $body;
		}
	}

	// pmproconpd_pmpro_email_data function.
	if ( ! function_exists( 'pmproconpd_pmpro_email_data' ) ) {
		function pmproconpd_pmpro_email_data( $email_data, $email ) {
			_deprecated_function( __FUNCTION__, '3.0' );
			return $email_data;
		}
	}

	// pmproconpd_plugin_row_meta function.
	if ( ! function_exists( 'pmproconpd_plugin_row_meta' ) ) {
		function pmproconpd_plugin_row_meta( $links, $file ) {
			_deprecated_function( __FUNCTION__, '3.0' );
			return $links;
		}
	}
}
add_action( 'plugins_loaded', 'pmpro_cancel_on_next_payment_date_deprecated', 20 );

/**
 * Check for active Add Ons that are not yet MMPU compatible.
 *
 * @since 3.0
 * @return array[string] Add On names that are not yet MMPU compatible.
 */
function pmpro_get_mmpu_incompatible_add_ons() {
	// Add ons will use this filter to add their own names if they are not yet MMPU compatible.
	return apply_filters( 'pmpro_mmpu_incompatible_add_ons', array() );
}

/**
 * Get a list of deprecated PMPro Add Ons.
 *
 * @since 2.11
 *
 * @return array Add Ons that are deprecated.
 */
function pmpro_get_deprecated_add_ons() {
	global $wpdb;

	// Check if the RH restrict by username or email feature was being used.
	static $pmpro_register_helper_restricting_by_email_or_username = null;
	if ( ! isset( $pmpro_register_helper_restricting_by_email_or_username ) ) {
		$sqlQuery = "SELECT option_value FROM $wpdb->options WHERE option_name LIKE 'pmpro_level_%_restrict_emails' OR option_name LIKE 'pmpro_level_%_restrict_usernames' AND option_value <> '' LIMIT 1";
		$pmpro_register_helper_restricting_by_email_or_username = $wpdb->get_var( $sqlQuery );

		// If the option was not found then the feature was not being used.
		if( $pmpro_register_helper_restricting_by_email_or_username === null ) {
			$pmpro_register_helper_restricting_by_email_or_username = false;
		} else {
			$pmpro_register_helper_restricting_by_email_or_username = true;
		}
	}

	// If the RH restrict by username or email feature was being used, set the message.
	if ( $pmpro_register_helper_restricting_by_email_or_username ) {
		$pmpro_register_helper_message = sprintf( __( 'Restricting members by username or email was not merged into Paid Memberships Pro. If this feature was being used, a <a href="%s" target="_blank">code recipe</a> will be needed to continue using this functionality.', 'paid-memberships-pro' ), 'https://www.paidmembershipspro.com/restrict-membership-signup-by-email-or-username/' );
	} else {
		$pmpro_register_helper_message = '';
	}
	
	// Set the array of deprecated Add Ons.
	$deprecated = array(
		'pmpro-member-history' => array(
			'file' => 'pmpro-member-history.php',
			'label' => 'Member History'
		),
		'pmpro-email-templates' => array(
			'file' => 'pmpro-email-templates.php',
			'label' => 'Email Templates'
		),
		'pmpro-email-templates-addon' => array(
			'file' => 'pmpro-email-templates.php',
			'label' => 'Email Templates'
		),
		'pmpro-better-logins-report' => array(
			'file' => 'pmpro-better-logins-report.php',
			'label' => 'Better Logins Report'
		),
		'pmpro-multiple-memberships-per-user' => array(
			'file' => 'pmpro-multiple-memberships-per-user.php',
			'label' => 'Multiple Memberships Per User'
		),
		'pmpro-cancel-on-next-payment-date' => array(
			'file' => 'pmpro-cancel-on-next-payment-date.php',
			'label' => 'Cancel on Next Payment Date'
    ),
		'pmpro-stripe-billing-limits' => array(
			'file' => 'pmpro-stripe-billing-limits.php',
			'label' => 'Stripe Billing Limits'
		),
		'pmpro-register-helper' => array(
			'file' => 'pmpro-register-helper.php',
			'label' => 'Register Helper',
			'message' => $pmpro_register_helper_message
		),
		'pmpro-table-pages' => array(
			'file' => 'pmpro-table-pages.php',
			'label' => 'Table Layout Plugin Pages'
		)
	);
	
	$deprecated = apply_filters( 'pmpro_deprecated_add_ons_list', $deprecated );
	
	// If the list is empty or not an array, just bail.
	if ( empty( $deprecated ) || ! is_array( $deprecated ) ) {
		return array();
	}

	return $deprecated;
}

// Check if installed, deactivate it and show a notice now.
function pmpro_check_for_deprecated_add_ons() {
	$deprecated = pmpro_get_deprecated_add_ons();
  	$deprecated_active = array();
	$has_messages = false;
	foreach( $deprecated as $key => $values ) {
		$path = '/' . $key . '/' . $values['file'];
		if ( file_exists( WP_PLUGIN_DIR . $path ) ) {
			$deprecated_active[] = $values;
			if ( ! empty( $values['message'] ) ) {
				$has_messages = true;
			}

			// Try to deactivate it if it's enabled.
			if ( is_plugin_active( plugin_basename( $path ) ) ) {
				deactivate_plugins( $path );
			}
		}
	}

	// If any deprecated add ons are active, show warning.
	if ( ! empty( $deprecated_active ) && is_array( $deprecated_active ) ) {
		// Only show on certain pages.
		if ( ! isset( $_REQUEST['page'] ) || strpos( sanitize_text_field( $_REQUEST['page'] ), 'pmpro' ) === false  ) {
			return;
		}
		?>
		<div class="notice notice-warning">
		<p>
			<?php
				// translators: %s: The list of deprecated plugins that are active.
				echo wp_kses(
					sprintf(
						__( 'Some Add Ons are now merged into the Paid Memberships Pro core plugin. The features of the following plugins are now included in PMPro by default. You should <strong>delete these unnecessary plugins</strong> from your site: <em><strong>%s</strong></em>.', 'paid-memberships-pro' ),
						implode( ', ', wp_list_pluck( $deprecated_active, 'label' ) )
					),
					array(
						'strong' => array(),
						'em' => array(),
					)
				);
			?>
		</p>
		<?php
		// If there are any messages, show them.
		if ( $has_messages ) {
			?>
			<ul>
				<?php
				foreach( $deprecated_active as $deprecated ) {
					if ( empty( $deprecated['message'] ) ) {
						continue;
					}
					?>
					<li>
						<strong><?php echo esc_html( $deprecated['label'] ); ?></strong>:
						<?php
						echo wp_kses(
							$deprecated['message'],
							array(
								'a' => array(
								'href' => array(),
								'target' => array(),
							) )
						);
						?>
					</li>
					<?php
				}
				?>
			</ul>
			<?php
		}
		?>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'pmpro_check_for_deprecated_add_ons' );

/**
 * Remove the "Activate" link on the plugins page for deprecated add ons.
 *
 * @since 2.11
 *
 * @param array  $actions An array of plugin action links.
 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
 * @return array $actions An array of plugin action links.
 */
 function pmpro_deprecated_add_ons_action_links( $actions, $plugin_file ) {
	$deprecated = pmpro_get_deprecated_add_ons();

	foreach( $deprecated as $key => $values ) {
		if ( $plugin_file == $key . '/' . $values['file'] ) {
			$actions['activate'] = esc_html__( 'Deprecated', 'paid-memberships-pro' );
		}
	}

	return $actions;
}
add_filter( 'plugin_action_links', 'pmpro_deprecated_add_ons_action_links', 10, 2 );

/**
 * The 2Checkout gateway was deprecated in v2.6.
 * Cybersource was deprecated in 2.10.
 * PayPal Website Payments Pro was deprecated in 2.10.
 *
 * This code will add it back those gateways if it was the selected gateway.
 * In future versions, we will remove gateway code entirely.
 * And you will have to use a stand alone add on for those gateways
 * or choose a new gateway.
 */
function pmpro_check_for_deprecated_gateways() {
	$undeprecated_gateways = get_option( 'pmpro_undeprecated_gateways' );
	if ( empty( $undeprecated_gateways ) ) {
		$undeprecated_gateways = array();
	} elseif ( is_string( $undeprecated_gateways ) ) {
		// pmpro_setOption turns this into a comma separated string
		$undeprecated_gateways = explode( ',', $undeprecated_gateways );
	}
	$default_gateway = get_option( 'pmpro_gateway' );

	$deprecated_gateways = array( 'twocheckout', 'cybersource', 'paypal' );
	foreach ( $deprecated_gateways as $deprecated_gateway ) {
		if ( $default_gateway === $deprecated_gateway || in_array( $deprecated_gateway, $undeprecated_gateways ) ) {
			require_once( PMPRO_DIR . '/classes/gateways/class.pmprogateway_' . $deprecated_gateway . '.php' );
			if ( ! in_array( $deprecated_gateway, $undeprecated_gateways ) ) {
				$undeprecated_gateways[] = $deprecated_gateway;
				update_option( 'pmpro_undeprecated_gateways', $undeprecated_gateways );
			}
		}
	}
}

/**
 * Disable uninstall script for duplicates
 */
function pmpro_disable_uninstall_script_for_duplicates( $file ) {
	// bail if not a duplicate
	if ( ! in_array( $file, array_keys( pmpro_get_plugin_duplicates() ) ) ) {
		return;
	}

	// disable uninstall script
	if ( file_exists( WP_PLUGIN_DIR . '/' . dirname( $file ) . '/uninstall.php' ) ) {
		rename(
			WP_PLUGIN_DIR . '/' . dirname( $file ) . '/uninstall.php',
			WP_PLUGIN_DIR . '/' . dirname( $file ) . '/uninstall-disabled.php'
		);
	}
}
add_action( 'pre_uninstall_plugin', 'pmpro_disable_uninstall_script_for_duplicates' );

/**
 * @return array
 */
function pmpro_get_plugin_duplicates() {
	$all_plugins          = get_plugins();
	$active_plugins_names = get_option( 'active_plugins' );

	$multiple_installations = array();
	foreach ( $all_plugins as $plugin_name => $plugin_headers ) {
		// skip all active plugins
		if ( in_array( $plugin_name, $active_plugins_names ) ) {
			continue;
		}

		// skip plugins without a folder
		if ( false === strpos( $plugin_name, '/' ) ) {
			continue;
		}

		// check if plugin file is paid-memberships-pro.php
		// or Plugin Name: Paid Memberships Pro
		list( $plugin_folder, $plugin_mainfile_php ) = explode( '/', $plugin_name );
		if ( 'paid-memberships-pro.php' === $plugin_mainfile_php || 'Paid Memberships Pro' === $plugin_headers['Name'] ) {
			$multiple_installations[ $plugin_name ] = $plugin_headers;
		}
	}

	return $multiple_installations;
}
