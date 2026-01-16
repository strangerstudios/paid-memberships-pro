<?php

/**
 * WP Fusion - PMPro Approvals Class.
 *
 * @since 3.45.5
 */
class WPF_PMPro_Approvals {

	/**
	 * Constructor.
	 *
	 * @since 3.45.5
	 */
	public function __construct() {

		// Approvals support.
		add_filter( 'wpf_meta_fields', array( $this, 'prepare_approval_meta_fields' ), 15 ); // 15 so it runs after other meta fields are added.
		add_action( 'updated_user_meta', array( $this, 'sync_approval_status' ), 10, 4 );
		add_action( 'added_user_meta', array( $this, 'sync_approval_status' ), 10, 4 );

		// Sync approval with CRM.
		add_filter( 'wpf_get_user_meta', array( $this, 'get_approval_meta' ), 10, 2 );
		add_filter( 'wpf_set_user_meta', array( $this, 'set_approval_meta' ), 10, 2 );
	}


	/**
	 * Adds PMP Approvals meta fields to WPF contact fields list
	 *
	 * @since 3.45.5
	 *
	 * @param array $meta_fields The meta fields.
	 * @return array The meta fields.
	 */
	public function prepare_approval_meta_fields( $meta_fields ) {

		$meta_fields['pmpro_approval'] = array(
			'label' => __( 'Approval Status', 'wp-fusion' ),
			'type'  => 'text',
			'group' => 'pmpro',
		);

		return $meta_fields;
	}


	/**
	 * Sync the approval status when it's edited.
	 *
	 * @since 3.37.12
	 *
	 * @param int    $meta_id     The meta ID.
	 * @param int    $object_id   The user ID.
	 * @param string $meta_key    The meta key.
	 * @param mixed  $_meta_value The meta value.
	 */
	public function sync_approval_status( $meta_id, $object_id, $meta_key, $_meta_value ) {

		if ( strpos( $meta_key, 'pmpro_approval_' ) === 0 && isset( $_meta_value['status'] ) ) {
			wp_fusion()->user->push_user_meta( $object_id, array( 'pmpro_approval' => $_meta_value['status'] ) );
		}
	}


	/**
	 * Merge the approval status into the usermeta.
	 *
	 * @since  3.37.12
	 *
	 * @param  array $user_meta The user meta.
	 * @param  int   $user_id   The user identifier.
	 * @return array The user meta.
	 */
	public function get_approval_meta( $user_meta, $user_id ) {

		$level = pmpro_getMembershipLevelForUser( $user_id );

		if ( ! empty( $level ) ) {

			$approval_status = get_user_meta( $user_id, 'pmpro_approval_' . $level->id, true );

			if ( ! empty( $approval_status ) ) {
				$user_meta['pmpro_approval'] = $approval_status['status'];
			}
		}

		return $user_meta;
	}

	/**
	 * Filter user meta at registration
	 *
	 * @since 3.45.5
	 *
	 * @param array $user_meta The user meta.
	 * @param int   $user_id   The user ID.
	 * @return array The user meta.
	 */
	public function set_approval_meta( $user_meta, $user_id ) {

		if ( ! empty( $user_meta['pmpro_approval'] ) ) {

			$level = pmpro_getMembershipLevelForUser( $user_id );

			if ( ! empty( $level ) ) {

				$status = get_user_meta( $user_id, 'pmpro_approval_' . $level->id, true );

				$status['status']    = $user_meta['pmpro_approval'];
				$status['timestamp'] = current_time( 'timestamp' );

				$user_meta[ 'pmpro_approval_' . $level->id ] = $status;

				unset( $user_meta['pmpro_approval'] );

			}
		}

		return $user_meta;
	}
}
