<?php

global $wpdb;

if(isset($_REQUEST['deleteid']))
    $deleteid = intval($_REQUEST['deleteid']);

$ml_id = intval($_REQUEST['deleteid']);

if($ml_id > 0) {
    do_action("pmpro_delete_membership_level", $ml_id);

    //remove any categories from the ml
    $sqlQuery = $wpdb->prepare("
        DELETE FROM $wpdb->pmpro_memberships_categories
        WHERE membership_id = %d",
        $ml_id
    );

    $r1 = $wpdb->query($sqlQuery);

    //cancel any subscriptions to the ml
    $r2 = true;
    $user_ids = $wpdb->get_col( $wpdb->prepare( "
        SELECT user_id FROM $wpdb->pmpro_memberships_users
        WHERE membership_id = %d
        AND status = 'active'",
        $ml_id
    ) );

    foreach($user_ids as $user_id) {
        // Cancel the membership level and subscription.
        if ( ! pmpro_cancelMembershipLevel( $ml_id, $user_id, 'inactive' ) ) {
            // Couldn't delete the subscription or the membership.
            // We should probably notify the admin
            $pmproemail = new PMProEmail();
            $pmproemail->data = array("body"=>"<p>" . sprintf(__("There was an error removing the membership level for user with ID=%d. You will want to check your payment gateway to see if their subscription is still active.", 'paid-memberships-pro' ), $user_id) . "</p>");
            $last_order = $wpdb->get_row( $wpdb->prepare( "
                SELECT * FROM $wpdb->pmpro_membership_orders
                WHERE user_id = %d
                ORDER BY timestamp DESC LIMIT 1",
                $user_id
            ) );
            if($last_order)
                $pmproemail->data["body"] .= "<p>" . __("Last Invoice", 'paid-memberships-pro' ) . ":<br />" . nl2br(var_export($last_order, true)) . "</p>";
            $pmproemail->sendEmail(get_bloginfo("admin_email"));

            $r2 = false;
        }
    }

    // delete the level group entry.
    $wpdb->delete( $wpdb->pmpro_membership_levels_groups, array( 'level' => $ml_id ) );

    //delete the ml
    $sqlQuery = $wpdb->prepare( "
        DELETE FROM $wpdb->pmpro_membership_levels
        WHERE id = %d LIMIT 1",
        $ml_id
    );
    $r3 = $wpdb->query($sqlQuery);

    if($r1 !== FALSE && $r2 !== FALSE && $r3 !== FALSE) {
        $page_msg = 3;
        $page_msgt = __("Membership level deleted successfully.", 'paid-memberships-pro' );
    } else {
        $page_msg = -3;
        $page_msgt = __("Error deleting membership level.", 'paid-memberships-pro' );
    }
}
else {
    $page_msg = -3;
    $page_msgt = __("Error deleting membership level.", 'paid-memberships-pro' );
}