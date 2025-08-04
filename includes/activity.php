<?php

/**
 * Log an activity to the pmpro_activity_log table.
 *
 * @since TBD
 * 
 * @param array|string $args If string, used as description. If array, accepts:
 *   - event_type (string, required)
 *   - user_id (int, optional) User doing the activity.
 *   - target_user_id (int, optional) User the activity is done to.
 *   - object_id (int, optional) Related object, e.g. level ID, order ID, etc.
 *   - description (string, required)
 *
 * @return int|false Inserted row ID on success, false on failure.
 */
function pmpro_log_activity( $args ) {
    global $wpdb;

    // If $args is a string, treat as description and use defaults.
    if ( is_string( $args ) ) {
        $description = $args;
        $event_type = 'general';
        $user_id = get_current_user_id();
        $target_user_id = null;
        $object_id = 0;
    } elseif ( is_array( $args ) ) {
        $event_type = isset( $args['event_type'] ) ? $args['event_type'] : 'general';
        $user_id = isset( $args['user_id'] ) ? intval( $args['user_id'] ) : get_current_user_id();
        $target_user_id = isset( $args['target_user_id'] ) ? intval( $args['target_user_id'] ) : 0;
        $object_id = isset( $args['object_id'] ) ? intval( $args['object_id'] ) : 0;
        $description = isset( $args['description'] ) ? $args['description'] : '';
    } else {
        return false;
    }

    // Require a description.
    if ( empty( $description ) ) {
        return false;
    }

    $table = $wpdb->prefix . 'pmpro_activity_log';
    $data = array(
        'event_type' => $event_type,
        'user_id' => $user_id,
        'target_user_id' => $target_user_id,
        'object_id' => $object_id,
        'event_description' => $description,
        'timestamp' => current_time( 'mysql', 1 ), // UTC
    );
    $format = array( '%s', '%d', '%d', '%d', '%s', '%s' );

    $result = $wpdb->insert( $table, $data, $format );
    if ( $result ) {
        return $wpdb->insert_id;
    }
    return false;
}


/**
 * Log an activity when a membership level is added or removed.
 * @since TBD
 * @param int $new_level_id The ID of the new level, or 0 if removed.
 * @param int $user_id The ID of the user whose level is being changed.
 * @param int $cancel_level The ID of the level being cancelled, if any.
 * Hooks into the `pmpro_after_change_membership_level` action.
 */
function pmpro_activity_log_on_level_change( $new_level_id, $user_id, $cancel_level = 0 ) {
    $current_user = get_user_by( 'ID', get_current_user_id() );
    $target_user = get_userdata( $user_id );

    // If there is a cancelled level, log removal.
    if ( !empty( $cancel_level ) ) {
        $description = sprintf(
            '%s removed level %s from %s',
            $current_user ? $current_user->user_login : 'System',
            $cancel_level,
            $target_user ? $target_user->user_login : $user_id
        );
        pmpro_log_activity( [
            'event_type'       => 'level_removed',
            'user_id'          => get_current_user_id(),
            'target_user_id'   => $user_id,
            'object_id'        => intval( $cancel_level ),
            'description'      => $description
        ] );
    }

    // If there is a new level, log addition.
    if ( !empty( $new_level_id ) ) {
        $description = sprintf(
            '%s added level %s to %s',
            $current_user ? $current_user->user_login : 'System',
            $new_level_id,
            $target_user ? $target_user->user_login : $user_id
        );
        pmpro_log_activity( [
            'event_type'       => 'level_added',
            'user_id'          => get_current_user_id(),
            'target_user_id'   => $user_id,
            'object_id'        => intval( $new_level_id ),
            'description'      => $description
        ] );
    }
}
add_action( 'pmpro_after_change_membership_level', 'pmpro_activity_log_on_level_change', 10, 3 );