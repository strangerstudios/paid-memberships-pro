<?php

/**
 * Return an array of all level groups, with the key being the level group id.
 *
 * @since 3.0
 *
 * @return array
 */
function pmpro_get_level_groups() {
	global $wpdb;

	$groups = $wpdb->get_results( "SELECT * FROM $wpdb->pmpro_groups ORDER BY id" );
	$to_return = array();
	foreach ( $groups as $group ) {
		$to_return[ $group->id ] = $group;
	}

	// If we don't have any groups yet, create the default one and add all levels to it.
	if ( empty( $to_return ) ) {
		$group_id = pmpro_create_level_group( 'Main Group', false );
		if ( ! empty( $group_id ) ) {
			$levels = pmpro_getAllLevels( true, true );
			foreach ( $levels as $level ) {
				pmpro_add_level_to_group( $level->id, $group_id );
			}
			return array( $group_id => (object) array( 'id' => $group_id, 'name' => 'Main Group', 'allow_multiple_selections' => 0, 'displayorder' => 0 ) );
		}
	}

	return $to_return;
}

/**
 * Return an array of all level groups in order.
 *
 * @since 3.0
 *
 * @return array
 */
function pmpro_get_level_groups_in_order() {
	$level_groups = pmpro_get_level_groups();
	usort( $level_groups, function ( $a, $b ) {
		return (int) $a->displayorder - (int) $b->displayorder;
	} );

	return $level_groups;
}

/**
 * Get data for a level group.
 *
 * @since 3.0
 *
 * @param int $group_id The ID of the group to get data for.
 * @return object|bool The group data, or false if the group doesn't exist.
 */
function pmpro_get_level_group( $group_id ) {
	$all_groups = pmpro_get_level_groups();
	if ( ! empty( $all_groups[ $group_id ] ) ) {
		return $all_groups[ $group_id ];
	} else {
		return false;
	}
}

/**
 * Create a level group.
 *
 * @since 3.0
 *
 * @param string $name The name of the group.
 * @param bool   $allow_multiple_levels Whether or not to allow multiple levels to be selected from this group.
 * @param int    $displayorder The display order for the group.
 *
 * @return int|false The id of the new group or false if the group could not be created.
 */
function pmpro_create_level_group( $name, $allow_multiple_levels = true, $displayorder = null ) {
	global $wpdb;

	if ( empty( $displayorder ) ) {
		$displayorder = $wpdb->get_var( "SELECT MAX(displayorder) FROM $wpdb->pmpro_groups LIMIT 1" );
		$displayorder = intval( $displayorder ) + 1;
	}

	$result = $wpdb->insert(
		$wpdb->pmpro_groups,
		array(
			'name' => $name,
			'allow_multiple_selections' => (int) $allow_multiple_levels,
			'displayorder' => (int) $displayorder,
		),
		array( '%s', '%d', '%d' )
	);

	return empty( $result ) ? false : $wpdb->insert_id;
}

/**
 * Edit a level group.
 *
 * @since 3.0
 *
 * @param int    $id The id of the group to edit.
 * @param string $name The name of the group.
 * @param bool   $allow_multiple_levels Whether or not to allow multiple levels to be selected from this group.
 * @param int    $displayorder The display order of the group.
 *
 * @return bool True if the group was edited, false otherwise.
 */
function pmpro_edit_level_group( $id, $name, $allow_multiple_levels = true, $displayorder = null ) {
	global $wpdb;

	if ( empty( $displayorder ) ) {
		$displayorder = $wpdb->get_var( "SELECT MAX(displayorder) FROM $wpdb->pmpro_groups LIMIT 1" );
		$displayorder = intval( $displayorder ) + 1;
	}

	$result = $wpdb->update(
		$wpdb->pmpro_groups,
		array(
			'name' => $name,
			'allow_multiple_selections' => (int) $allow_multiple_levels,
			'displayorder' => (int) $displayorder,
		),
		array( 'id' => $id ),
		array( '%s', '%d', '%d', '%d' ),
	);

	return ! empty( $result );
}

/**
 * Delete a level group.
 *
 * @since 3.0
 *
 * @param int $id The id of the group to delete.
 *
 * @return bool True if the group was deleted, false otherwise.
 */
function pmpro_delete_level_group( $id ) {
	global $wpdb;

	// Make sure that there are no levels in this group.
	$levels_in_group = pmpro_get_level_ids_for_group( $id );
	if ( ! empty( $levels_in_group ) ) {
		return false;
	}

	$result = $wpdb->delete(
		$wpdb->pmpro_groups,
		array( 'id' => $id ),
		array( '%d' )
	);

	return ! empty( $result );
}

/**
 * Add a membership level to a level group.
 *
 * @since 3.0
 *
 * @param int $level_id The id of the level to add.
 * @param int $group_id The id of the group to add the level to.
 */
function pmpro_add_level_to_group( $level_id, $group_id ) {
	global $wpdb;

	// Remove the level from its current group.
	$wpdb->delete( $wpdb->pmpro_membership_levels_groups, array( 'level' => $level_id ) );

	// Add the level to the new group.
	$wpdb->insert( $wpdb->pmpro_membership_levels_groups, array('level' => $level_id, 'group' => $group_id ), array( '%d', '%d' ) );
}

/**
 * Get the group for a level.
 *
 * @since 3.0
 *
 * @param int $level_id The id of the level to get the group for.
 * @return int|false The id of the group the level is in or false if the level is not in a group.
 */
function pmpro_get_group_id_for_level( $level_id ) {
	global $wpdb;
	
	$sqlQuery = $wpdb->prepare( "SELECT `group` FROM $wpdb->pmpro_membership_levels_groups WHERE `level` = %d LIMIT 1", $level_id );
	$group_id = $wpdb->get_var( $sqlQuery );

	return empty( $group_id ) ? false : $group_id;
}

/**
 * Get the membership levels for a given group.
 *
 * @since 3.0
 *
 * @param int $group_id The id of the group to get the levels for.
 * @return array An array of membership levels.
 */
function pmpro_get_levels_for_group( $group_id ) {
	global $wpdb;
	
	$sqlQuery = $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_membership_levels WHERE id IN ( SELECT level FROM $wpdb->pmpro_membership_levels_groups WHERE `group` = %d )", $group_id );
	$levels = $wpdb->get_results( $sqlQuery );

	return empty( $levels ) ? array() : $levels;
}

/**
 * Get the level IDs for a given group.
 *
 * @since 3.0
 *
 * @param int $group_id The id of the group to get the levels for.
 * @return array An array of membership level IDs.
 */
function pmpro_get_level_ids_for_group( $group_id ) {
	global $wpdb;
	
	$sqlQuery = $wpdb->prepare( "SELECT level FROM $wpdb->pmpro_membership_levels_groups WHERE `group` = %d", $group_id );
	$levels = $wpdb->get_col( $sqlQuery );

	return empty( $levels ) ? array() : $levels;
}
