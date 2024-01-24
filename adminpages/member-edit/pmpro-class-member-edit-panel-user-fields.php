<?php

class PMPro_Member_Edit_Panel_User_Fields extends PMPro_Member_Edit_Panel {
	/**
	 * Set up the panel.
	 */
	public function __construct( $field_group_name ) {
		$this->slug = 'user_fields_' . sanitize_title( $field_group_name );
		$this->title = $field_group_name;
		$this->submit_text = current_user_can( 'edit_users' ) ? __( 'Update Member', 'paid-memberships-pro' ) : '';
	}

	/**
	 * Display the panel contents.
	 */
	protected function display_panel_contents() {
		// Print the group description.
		$field_group = pmpro_get_field_group_by_name( $this->title );
		if ( ! empty( $field_group->description ) ) {
			echo wp_kses_post( wpautop( $field_group->description ) );
		}

		// Print the fields.
		$profile_user_fields = pmpro_get_user_fields_for_profile( self::get_user()->ID, true );
		?>
		<table class="form-table">
			<?php
			foreach( $profile_user_fields[$this->title] as $field ) {
				if ( pmpro_is_field( $field ) ) {
					$field->displayInProfile( self::get_user()->ID );
				}
			}
			?>
		</table>
		<?php
	}

	/**
	 * Save panel data.
	 */
	public function save() {
		// Check capabilities.
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}

		$saved = ( pmpro_save_user_fields_in_profile( self::get_user()->ID ) !== false ); // Function returns false on failed, null on saved.

		// Show success message.
		if ( $saved ) {
			pmpro_setMessage( __( 'User fields updated successfully.', 'paid-memberships-pro' ), 'pmpro_success' );
		} else {
			pmpro_setMessage( __( 'You do not have permission to update these fields.', 'paid-memberships-pro' ), 'pmpro_error' );
		}
	}
}