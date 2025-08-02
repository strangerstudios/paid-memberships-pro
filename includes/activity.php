
<?php

/**
 * Log an activity to the pmpro_activity_log table.
 *
 * @since TBD
 * 
 * @param array|string $args If string, used as description. If array, accepts:
 *   - event_type (string, required)
 *   - user_id (int, optional)
 *   - object_id (int, optional)
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
        $object_id = 0;
    } elseif ( is_array( $args ) ) {
        $event_type = isset( $args['event_type'] ) ? $args['event_type'] : 'general';
        $user_id = isset( $args['user_id'] ) ? intval( $args['user_id'] ) : get_current_user_id();
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
        'object_id' => $object_id,
        'event_description' => $description,
        'timestamp' => current_time( 'mysql', 1 ), // UTC
    );
    $format = array( '%s', '%d', '%d', '%s', '%s' );

    $result = $wpdb->insert( $table, $data, $format );
    if ( $result ) {
        return $wpdb->insert_id;
    }
    return false;
}
