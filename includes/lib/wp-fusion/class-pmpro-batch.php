<?php

/**
 * WP Fusion - PMPro Batch Class.
 *
 * @since 3.45.5
 */
class WPF_PMPro_Batch {

	/**
	 * Constructor.
	 *
	 * @since 3.45.5
	 */
	public function __construct() {

		// Batch operations.
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_filter( 'wpf_batch_pmpro_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_pmpro', array( $this, 'batch_step' ) );

		// Meta batch operation.
		add_filter( 'wpf_batch_pmpro_meta_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_pmpro_meta', array( $this, 'batch_step_meta' ) );
	}


	/**
	 * Adds PMPro checkbox to available export options.
	 *
	 * @since 3.45.3
	 *
	 * @param array $options The options.
	 * @return array The options.
	 */
	public function export_options( $options ) {

		$options['pmpro'] = array(
			'label'   => __( 'Paid Memberships Pro membership statuses', 'wp-fusion' ),
			'title'   => 'memberships',
			'tooltip' => __( 'Updates the tags for all members based on their current membership status. Does not create new contact records or sync any fields.', 'wp-fusion' ),
		);

		$options['pmpro_meta'] = array(
			'label'   => __( 'Paid Memberships Pro memberships meta', 'wp-fusion' ),
			'title'   => 'memberships',
			'tooltip' => __( 'Syncs any enabled membership fields to the CRM, without modifying tags.', 'wp-fusion' ),
		);

		return $options;
	}

	/**
	 * Gets total list of members to be processed.
	 *
	 * @since 3.45.3
	 *
	 * @return array User IDs.
	 */
	public function batch_init() {

		global $wpdb;

		$user_ids = array();

		$result = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT u.ID 
				FROM {$wpdb->users} u
				LEFT JOIN {$wpdb->pmpro_memberships_users} mu ON u.ID = mu.user_id
				WHERE mu.membership_id IS NOT NULL
				"
			),
			ARRAY_A
		);

		if ( ! empty( $result ) ) {
			$user_ids = wp_list_pluck( $result, 'ID' );
		}

		return $user_ids;
	}

	/**
	 * Processes member actions in batches.
	 *
	 * @since 3.45.3
	 *
	 * @param int $user_id The user ID.
	 */
	public function batch_step( $user_id ) {

		$levels = pmpro_getMembershipLevelsForUser( $user_id, true );

		// We want to process the levels in order of oldest subscription to newest, doing each level only once.

		$processed_levels = array();

		foreach ( $levels as $level ) {

			if ( in_array( $level->id, $processed_levels ) ) {
				continue;
			}

			$processed_levels[] = $level->id;

			// Apply tags.
			wp_fusion()->integrations->pmpro->apply_membership_level_tags( $user_id, $level->id );
		}
	}

	/**
	 * Processes member actions in batches.
	 *
	 * @since 3.45.3
	 *
	 * @param int $user_id The user ID.
	 */
	public function batch_step_meta( $user_id ) {

		$levels = pmpro_getMembershipLevelsForUser( $user_id, true );

		// This will return all levels but we only want to process the most recent (highest subscription ID).
		usort(
			$levels,
			function ( $a, $b ) {
				return $b->subscription_id - $a->subscription_id;
			}
		);

		if ( ! empty( $levels ) ) {
			wp_fusion()->integrations->pmpro->sync_membership_level_fields( $user_id, $levels[0] );
		}
	}
}
