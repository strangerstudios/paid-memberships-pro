<?php

class PMPro_Member_Edit_Panel_User_Fields extends PMPro_Member_Edit_Panel {
	/**
	 * Set up the panel.
	 */
	public function __construct( $field_group_name ) {
		$this->slug = 'user-fields-' . sanitize_title( $field_group_name );
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
			echo wp_kses_post( $field_group->description );
		}

		// Check if this is a checkout field location and show a message about custom code.
		$checkout_field_locations = array(
			'after_username',
			'after_password',
			'after_email',
			'after_captcha',
			'checkout_boxes',
			'after_billing_fields',
			'before_submit_button',
			'just_profile'
		);
		if ( in_array( $this->title, $checkout_field_locations ) ) {
			esc_html_e( 'These user fields were added via custom code to hook into the following location:', 'paid-memberships-pro' );
			echo ' <code>' . esc_html( $this->title ) . '</code>';
		}

		// Print the fields.
		$profile_user_fields = pmpro_get_user_fields_for_profile( self::get_user()->ID, true );
		?>
		<table class="form-table">
			<?php
			foreach( $profile_user_fields[$this->title] as $field ) {
				if ( pmpro_is_field( $field ) ) {
					$field->displayInProfile( self::get_user()->ID ); // Field will be readonly if cannot edit users.
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
		$saved = ( pmpro_save_user_fields_in_profile( self::get_user()->ID ) !== false ); // Function returns false on failed, null on saved. Will check edit_users cap in function.

		// Show success message.
		if ( $saved ) {
			pmpro_setMessage( __( 'User fields updated successfully.', 'paid-memberships-pro' ), 'pmpro_success' );
		} else {
			pmpro_setMessage( __( 'You do not have permission to update these fields.', 'paid-memberships-pro' ), 'pmpro_error' );
		}
	}
}