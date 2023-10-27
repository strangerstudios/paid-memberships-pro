<?php

class PMPro_Member_Edit_Panel_User_Fields extends PMPro_Member_Edit_Panel {
	private $field_group_name;

	public function __construct( $field_group_name ) {
		$this->field_group_name = $field_group_name;
	}

	public function get_title( $user_id ) {
		return trim( $this->field_group_name );
	}

	public function display( $user_id ) {
		$profile_user_fields = pmpro_get_user_fields_for_profile( $user_id, true );
		?>
		<table class="form-table">
			<?php
			foreach( $profile_user_fields[$this->field_group_name] as $field ) {
				if ( pmpro_is_field( $field ) ) {
					$field->displayInProfile( $user_id );
				}
			}
			?>
		</table>
		<?php
	}

	public function get_submit_text( $user_id ) {
		return __( 'Update Member', 'paid-memberships-pro' );
	
	}

	public function save( $user_id ) {
		pmpro_save_user_fields_in_profile( $user_id );
	}
}