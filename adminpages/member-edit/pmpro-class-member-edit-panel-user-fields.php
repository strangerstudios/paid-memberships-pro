<?php

class PMPro_Member_Edit_Panel_User_Fields extends PMPro_Member_Edit_Panel {
	/**
	 * @var PMPro_Field_Group The field group .
	 * @since 3.4
	 */
	private $field_group;

	/**
	 * Set up the panel.
	 */
	public function __construct( $field_group_name ) {
		$this->field_group = PMPro_Field_Group::get( $field_group_name );
		$this->slug = 'user-fields-' . sanitize_title( $field_group_name );
		$this->title = $this->field_group->label;
		$this->submit_text = current_user_can( 'edit_users' ) ? __( 'Update Member', 'paid-memberships-pro' ) : '';
	}

	/**
	 * Display the panel contents.
	 */
	protected function display_panel_contents() {
		// Print the group description.
		if ( ! empty( $this->field_group->description ) ) {
			echo wp_kses_post( $this->field_group->description );
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
		if ( in_array( $this->field_group->name, $checkout_field_locations ) ) {
			esc_html_e( 'These user fields were added via custom code to hook into the following location:', 'paid-memberships-pro' );
			echo ' <code>' . esc_html( $this->field_group->name ) . '</code>';
		}

		// Print the fields.
		?>
		<table class="form-table">
			<?php
			$this->field_group->display(
				array(
					'markup' => 'table',
					'show_group_label' => false,
					'user_id' => self::get_user()->ID,
				)
			);
			?>
		</table>
		<?php
	}

	/**
	 * Save panel data.
	 */
	public function save() {
		$saved = $this->field_group->save_fields(
			array(
				'user_id' => self::get_user()->ID,
			)
		);

		// Show success message.
		if ( $saved ) {
			pmpro_setMessage( __( 'User fields updated successfully.', 'paid-memberships-pro' ), 'pmpro_success' );
		} else {
			pmpro_setMessage( __( 'You do not have permission to update these fields.', 'paid-memberships-pro' ), 'pmpro_error' );
		}
	}
}