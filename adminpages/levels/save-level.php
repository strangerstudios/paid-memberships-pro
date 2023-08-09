<?php

global $wpdb, $allowedposttags;

if(isset($_REQUEST['saveid']))
$saveid = intval($_REQUEST['saveid']);

$ml_name = wp_kses(wp_unslash($_REQUEST['name']), $allowedposttags);
$ml_description = wp_kses(wp_unslash($_REQUEST['description']), $allowedposttags);
$ml_confirmation = wp_kses(wp_unslash($_REQUEST['confirmation']), $allowedposttags);
if(!empty($_REQUEST['confirmation_in_email']))
	$ml_confirmation_in_email = 1;
else
	$ml_confirmation_in_email = 0;

$ml_initial_payment = sanitize_text_field($_REQUEST['initial_payment']);
if(!empty($_REQUEST['recurring']))
	$ml_recurring = 1;
else
	$ml_recurring = 0;
$ml_billing_amount = sanitize_text_field($_REQUEST['billing_amount']);
$ml_cycle_number = intval($_REQUEST['cycle_number']);
$ml_cycle_period = sanitize_text_field($_REQUEST['cycle_period']);
$ml_billing_limit = intval($_REQUEST['billing_limit']);
if(!empty($_REQUEST['custom_trial']))
	$ml_custom_trial = 1;
else
	$ml_custom_trial = 0;
$ml_trial_amount = sanitize_text_field($_REQUEST['trial_amount']);
$ml_trial_limit = intval($_REQUEST['trial_limit']);
if(!empty($_REQUEST['expiration']))
	$ml_expiration = 1;
else
	$ml_expiration = 0;
$ml_expiration_number = intval($_REQUEST['expiration_number']);
$ml_expiration_period = sanitize_text_field($_REQUEST['expiration_period']);
$ml_categories = array();

//reversing disable to allow here
if(empty($_REQUEST['disable_signups']))
	$ml_allow_signups = 1;
else
	$ml_allow_signups = 0;

foreach ( $_REQUEST as $key => $value ) {
	if ( $value == 'yes' && preg_match( '/^membershipcategory_(\d+)$/i', $key, $matches ) ) {
		$ml_categories[] = $matches[1];
	}
}

//clearing out values if checkboxes aren't checked
if(empty($ml_recurring)) {
	$ml_billing_amount = $ml_cycle_number = $ml_cycle_period = $ml_billing_limit = $ml_trial_amount = $ml_trial_limit = 0;
} elseif(empty($ml_custom_trial)) {
	$ml_trial_amount = $ml_trial_limit = 0;
}
if(empty($ml_expiration)) {
	$ml_expiration_number = $ml_expiration_period = 0;
}

pmpro_insert_or_replace(
	$wpdb->pmpro_membership_levels,
	array(
		'id'=>max($saveid, 0),
		'name' => $ml_name,
		'description' => $ml_description,
		'confirmation' => $ml_confirmation,
		'initial_payment' => $ml_initial_payment,
		'billing_amount' => $ml_billing_amount,
		'cycle_number' => $ml_cycle_number,
		'cycle_period' => $ml_cycle_period,
		'billing_limit' => $ml_billing_limit,
		'trial_amount' => $ml_trial_amount,
		'trial_limit' => $ml_trial_limit,
		'expiration_number' => $ml_expiration_number,
		'expiration_period' => $ml_expiration_period,
		'allow_signups' => $ml_allow_signups
	),
	array(
		'%d',		//id
		'%s',		//name
		'%s',		//description
		'%s',		//confirmation
		'%f',		//initial_payment
		'%f',		//billing_amount
		'%d',		//cycle_number
		'%s',		//cycle_period
		'%d',		//billing_limit
		'%f',		//trial_amount
		'%d',		//trial_limit
		'%d',		//expiration_number
		'%s',		//expiration_period
		'%d',		//allow_signups
	)
);

// Was there an error inserting or updating?
if ( empty( $wpdb->last_error ) ) {		
	unset( $_REQUEST['edit'] );
	$msg = 1;

	if ( ! empty( $saveid ) ) {
		$msgt = __( 'Membership level updated successfully.', 'paid-memberships-pro' );
	} else {
		$msgt = __( 'Membership level added successfully.', 'paid-memberships-pro' );
	}
} else {
	$msg = -1;
	$msgt = __( 'Error adding membership level.', 'paid-memberships-pro' );
}

// Update saveid to insert id if this was a new level.
if ( $saveid < 1 ) {			
	$saveid = $wpdb->insert_id;
}

// If we have a saveid, update categories.
if ( $saveid > 0 ) {
	pmpro_updateMembershipCategories( $saveid, $ml_categories );
	if ( ! empty( $wpdb->last_error ) ) {			
		$page_msg = -2;
		$page_msgt = __("Error updating membership level.", 'paid-memberships-pro' );
	}
}

if( ! empty( $msgt ) && $ml_recurring && $ml_expiration ) {
	$msgt .= ' <strong class="red">' . sprintf( __( 'WARNING: A level was set with both a recurring billing amount and an expiration date. You only need to set one of these unless you really want this membership to expire after a specific time period. For more information, <a target="_blank" rel="nofollow noopener" href="%s">see our post here</a>.', 'paid-memberships-pro' ), 'https://www.paidmembershipspro.com/important-notes-on-recurring-billing-and-expiration-dates-for-membership-levels/?utm_source=plugin&utm_medium=pmpro-membershiplevels&utm_campaign=blog&utm_content=important-notes-on-recurring-billing-and-expiration-dates-for-membership-levels' ) . '</strong>';

	// turn success to errors
	if ( ! empty( $page_msg ) && $page_msg > 0 ) {
		$page_msg = 0 - $page_msg;
	}
}

// Update the Level Meta to Add Confirmation Message to Email.
if ( isset( $ml_confirmation_in_email ) ) {
	update_pmpro_membership_level_meta( $saveid, 'confirmation_in_email', $ml_confirmation_in_email );
}

// Update the level group.
if ( ! empty( $_REQUEST['level_group'] ) ) {
	pmpro_add_level_to_group( $saveid, (int) $_REQUEST['level_group'] );
}

do_action("pmpro_save_membership_level", $saveid);
