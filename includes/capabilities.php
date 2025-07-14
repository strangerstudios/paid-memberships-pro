<?php
//make sure administrators have correct capabilities
function pmpro_check_admin_capabilities()
{
    // Grab the defined (needed) admin capabilities
    $roles = pmpro_get_capability_defs('administrator');

    $caps_configured = true;

    // check whether the current user has those capabilities already
    foreach( $roles as $r )
    {
        $caps_configured = $caps_configured && current_user_can($r);
    }

    // if not, set the
    if ( false === $caps_configured && current_user_can('administrator'))
    {
        pmpro_set_capabilities_for_role('administrator');
    }
}
add_action('admin_init', 'pmpro_check_admin_capabilities', 5, 2);

// use the capability definition for $role_name and add/remove capabilities as requested
function pmpro_set_capabilities_for_role( $role_name, $action = 'enable' )
{
    $role = get_role( $role_name );
    if ( empty( $role ) ) {
        // Role does not exist.
        return false;
    }

    $cap_array = pmpro_get_capability_defs( $role_name );

    // Iterate through the relevant caps for the role & add or remove them
    foreach( $cap_array as $cap_name ) {
        if ( $action == 'enable' )
            $role->add_cap( $cap_name );

        if ( $action == 'disable' )
            $role->remove_cap( $cap_name );
    }
    return true;
}

// used to define what capabilities goes with what role.
function pmpro_get_capability_defs($role)
{
    // TODO: Add other standard roles (if/when needed)

    // caps for the administrator role
	$cap_array = array(
		'pmpro_memberships_menu',
		'pmpro_dashboard',
		'pmpro_wizard',
		'pmpro_membershiplevels',
		'pmpro_edit_members',
		'pmpro_pagesettings',
		'pmpro_paymentsettings',
		'pmpro_taxsettings',
		'pmpro_securitysettings',
		'pmpro_emailsettings',
		'pmpro_emailtemplates',
		'pmpro_designsettings',
		'pmpro_advancedsettings',
		'pmpro_addons',
		'pmpro_loginscsv',
		'pmpro_memberslist',
		'pmpro_memberslistcsv',
		'pmpro_reports',
		'pmpro_reportscsv',
		'pmpro_orders',
		'pmpro_orderscsv',
		'pmpro_sales_report_csv',
		'pmpro_discountcodes',
		'pmpro_userfields',
		'pmpro_updates',
		'pmpro_manage_pause_mode'
	);

    return apply_filters( "pmpro_assigned_{$role}_capabilities", $cap_array);
}

/**
 * Get the capability required to edit a member.
 * @since 3.0
 * @return string
 */
function pmpro_get_edit_member_capability() {
    /**
     * Filter the capability required to edit a member.
     * @since 3.0
     * @param string $capability The capability required to edit a member.
     *                           Default 'pmpro_edit_members'.
     * @return string
     */
    return apply_filters( 'pmpro_edit_member_capability', 'pmpro_edit_members' );
}
