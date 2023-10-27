<?php

function pmpro_member_edit_get_panels( $user_id ) {
	static $panels_cache = array();
	if ( ! empty( $panels_cache[ $user_id ] ) ) {
		// Use cached value.
		return $panels_cache[ $user_id ];
	}

	// Include panel classes.
	require_once( PMPRO_DIR . '/adminpages/member/pmpro-abstract-class-member-edit-panel.php' );
	require_once( PMPRO_DIR . '/adminpages/member/pmpro-class-member-edit-panel-user-info.php' );
	require_once( PMPRO_DIR . '/adminpages/member/pmpro-class-member-edit-panel-memberships.php' );
	require_once( PMPRO_DIR . '/adminpages/member/pmpro-class-member-edit-panel-subscriptions.php' );
	require_once( PMPRO_DIR . '/adminpages/member/pmpro-class-member-edit-panel-orders.php' );
	require_once( PMPRO_DIR . '/adminpages/member/pmpro-class-member-edit-panel-other.php' );
	require_once( PMPRO_DIR . '/adminpages/member/pmpro-class-member-edit-panel-user-fields.php' );

	// Add default panels.
	$panels = array();
	$panels['user_info'] = new PMPro_Member_Edit_Panel_User_Info();
	$panels['memberships'] = new PMPro_Member_Edit_Panel_Memberships();
	$panels['subscriptions'] = new PMPro_Member_Edit_Panel_Subscriptions();
	$panels['orders'] = new PMPro_Member_Edit_Panel_Orders();
	$panels['other'] = new PMPro_Member_Edit_Panel_Other();

	// Add user fields panels.
	$profile_user_fields = pmpro_get_user_fields_for_profile( $user_id, true );
	if ( ! empty( $profile_user_fields ) ) {
		foreach ( $profile_user_fields as $group_name => $user_fields ) {
			$panels[ 'user_fields_' . sanitize_title( $group_name ) ] = new PMPro_Member_Edit_Panel_User_Fields( $group_name );
		}
	}

	// Filter panels.
	$panels = apply_filters( 'pmpro_member_edit_panels', $panels, $user_id );

	// Set cache.
	$panels_cache[ $user_id ] = $panels;

	// Return panels.
	return $panels;
}

function pmpro_member_edit_display() {
	global $current_user, $pmpro_msg, $pmpro_msgt;

	// Get the user that we are editing.
	if ( ! empty( $_REQUEST['user_id'] ) ) {
		$check_user = get_userdata( intval( $_REQUEST['user_id'] ) );
		if ( ! empty( $check_user->ID ) ) {
			$user = $check_user;
		}
	}

	// If a user was not found, we are adding a new user.
	if ( empty( $user) ) {
		$user = new WP_User();
	}

	// Define a constant if user is editing their own membership.
	if ( ! defined( 'IS_PROFILE_PAGE' ) ) {
		define( 'IS_PROFILE_PAGE', ( $user->ID === $current_user->ID ) );
	}

	$panels = pmpro_member_edit_get_panels( $user->ID );

	// Get the panel to default to.
	$default_panel_slug = 'user_info';
	if ( ! empty( $user->ID ) && ! empty( $_REQUEST['pmpro_member_edit_panel'] ) && ! empty( $panels[ $_REQUEST['pmpro_member_edit_panel'] ] ) ) {
		$default_panel_slug = sanitize_text_field( $_REQUEST['pmpro_member_edit_panel'] );
	}

	/**
	 * Load the Paid Memberships Pro dashboard-area header
	 */
	require_once( PMPRO_DIR . '/adminpages/admin_header.php' );

	// TODO: Do we need to update how this is displayed at all?
	if ( $pmpro_msg ) {
		?>
		<div role="alert" id="pmpro_message" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>">
			<?php echo wp_kses_post( apply_filters( 'pmpro_checkout_message', $pmpro_msg, $pmpro_msgt ) ); ?>
		</div>
		<?php
	} else {
		?>
		<div id="pmpro_message" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message' ) ); ?>" style="display: none;"></div>
		<?php
	}
	?>

	<hr class="wp-header-end">
	<h1 class="wp-heading-inline">
		<?php		
		if ( ! empty( $user->ID ) ) {
			echo get_avatar( $user->ID, 96 );
			echo wp_kses_post( sprintf( __( 'Edit Member: %s', 'paid-memberships-pro' ), '<strong>' . $user->display_name . '</strong>' ) );
		} else {
			echo esc_html_e( 'Add Member', 'paid-memberships-pro' );
		}
		?>
	</h1>
	<div id="pmpro-edit-user-div">
		<nav id="pmpro-edit-user-nav" role="tablist" aria-label="Edit Member Field Tabs">
			<?php
			foreach ( $panels as $panel_slug => $panel ) {
				?>
				<button
					role="tab"
					aria-selected="<?php echo ( $panel_slug === $default_panel_slug ) ? 'true' : 'false' ?>"
					aria-controls="pmpro-<?php echo esc_attr( $panel_slug ) ?>-panel"
					id="pmpro-<?php echo esc_attr( $panel_slug ) ?>-tab"
					<?php echo ( empty( $user->ID ) ) ? 'disabled="disabled"' : ''; ?>
					tabindex="<?php echo ( $panel_slug === $default_panel_slug ) ? '0' : '-1' ?>"
				>
					<?php echo esc_attr( $panel->get_title( $user->ID ) ); ?>
				</button>
				<?php
			}
			?>
		</nav>
		<div class="pmpro_section">
			<?php
			foreach ( $panels as $panel_slug => $panel ) {
				// When creating a new user, we only want to show the user_info panel.
				if ( empty( $user->ID ) && $panel_slug !== 'user_info' ) {
					continue;
				}

				// Display the panel.
				// TODO: Allow adding links next to title.
				?>
				<div
					id="pmpro-<?php echo esc_attr( $panel_slug ) ?>-panel"
					role="tabpanel"
					tabindex="<?php echo ( $panel_slug === $default_panel_slug ) ? '0' : '-1' ?>"
					aria-labelledby="pmpro-<?php echo esc_attr( $panel_slug ) ?>-tab"
					<?php echo ( $panel_slug === $default_panel_slug ) ? '' : 'hidden'; ?>
				>
					<h2>
						<?php
						echo esc_html( $panel->get_title( $user->ID ) );
						echo wp_kses( $panel->get_title_link( $user->ID ), array( 'a' => array( 'href' => array(), 'target' => array(), 'class' => array() ) ) );
						?>
					</h2>
					<form class="pmpro-members" action="" method="post">
						<input type="hidden" name="pmpro_member_edit_panel" value="<?php echo esc_attr( $panel_slug ); ?>">
						<?php
						// Add a nonce.
						wp_nonce_field( 'pmpro_member_edit_saved_panel_' . $panel_slug, 'pmpro_member_edit_saved_panel_nonce' );

						// Display the panel.
						$panel->display( $user->ID );

						// Display the submit button.
						$submit_text = $panel->get_submit_text( $user->ID );
						if ( ! empty( $submit_text ) ) {
							?>
							<p class="submit">
								<input type="submit" name="submit" class="button button-primary" value="<?php echo esc_attr( $submit_text ); ?>">
							</p>
							<?php
						}
						?>
					</form>
				</div>
				<?php
			}
			?>
		</div>
	</div>

	<?php
	require_once( PMPRO_DIR . '/adminpages/admin_footer.php' );	
}

function pmpro_member_edit_save() {
	// Check if we are on the pmpro-member page.
	if ( empty( $_REQUEST['page'] ) || 'pmpro-member' !== $_REQUEST['page'] ) {
		return;
	}

	// Check that data was posted.
	if ( empty( $_POST ) ) {
		return;
	}

	// Get the user that we are editing.
	$user_id = '';
	if ( ! empty( $_REQUEST['user_id'] ) ) {
		$check_user = get_userdata( intval( $_REQUEST['user_id'] ) );
		if ( ! empty( $check_user->ID ) ) {
			$user_id = $check_user->ID;
		}
	}

	// Make sure the current user can edit this user.
	// Alterred from wp-admin/user-edit.php.
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		wp_die( __( 'Sorry, you are not allowed to edit this user.', 'paid-memberships-pro' ) );
	}

	// Get the panel slug that was submitted.
	$panel_slug = empty( $_REQUEST['pmpro_member_edit_panel'] ) ? '' : sanitize_text_field( $_REQUEST['pmpro_member_edit_panel'] );
	if ( empty( $panel_slug ) ) {
		return;
	}

	// Check the nonce.
	if ( empty( $_REQUEST['pmpro_member_edit_saved_panel_nonce'] ) || ! wp_verify_nonce( $_REQUEST['pmpro_member_edit_saved_panel_nonce'], 'pmpro_member_edit_saved_panel_' . $panel_slug ) ) {
		return;
	}

	// Save the panel.
	$panels = pmpro_member_edit_get_panels( $user_id );
	if ( ! empty( $panels[ $panel_slug ] ) ) {
		$panels[ $panel_slug ]->save( $user_id );
	}
}
add_action( 'admin_init', 'pmpro_member_edit_save' );