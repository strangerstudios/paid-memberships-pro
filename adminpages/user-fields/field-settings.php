<?php

if ( ! empty( $field ) ) {
	// Assume field stdClass in format we save to settings.
	$field_label = $field->label;
	$field_name = $field->name;
	$field_type = $field->type;
	$field_required = $field->required;
	$field_readonly = $field->readonly;     	
	$field_profile = $field->profile;
	$field_wrapper_class = $field->wrapper_class;
	$field_element_class = $field->element_class;
	$field_hint = $field->hint;
	$field_options = $field->options;
	$field_allowed_file_types = $field->allowed_file_types;
	$field_max_file_size = $field->max_file_size;
	$field_default = $field->default;
} else {
	// Default field values
	$field_label = '';
	$field_name = '';
	$field_type = '';
	$field_required = false;
	$field_readonly = false;
	$field_profile = '';
	$field_wrapper_class = '';
	$field_element_class = '';
	$field_hint = '';
	$field_options = '';
	$field_allowed_file_types = '';
	$field_max_file_size = '';
	$field_default = '';
}

// Other vars
$levels = pmpro_sort_levels_by_order( pmpro_getAllLevels( true, true ) );
?>
<div class="pmpro_userfield-group-field pmpro_userfield-group-field-collapse">
	<ul class="pmpro_userfield-group-tbody">
		<li class="pmpro_userfield-group-column-order">
			<div class="pmpro_userfield-group-buttons">
				<button type="button" aria-disabled="false" class="pmpro_userfield-group-buttons-button pmpro_userfield-field-buttons-button-move-up" aria-label="<?php esc_attr_e( 'Move up', 'paid-memberships-pro' ); ?>">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
				</button>
				<span class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Move Field Up', 'paid-memberships-pro' ); ?></span>

				<button type="button" aria-disabled="false" class="pmpro_userfield-group-buttons-button pmpro_userfield-field-buttons-button-move-down" aria-label="<?php esc_attr_e( 'Move down', 'paid-memberships-pro' ); ?>">
					<span class="dashicons dashicons-arrow-down-alt2"></span>
				</button>
				<span class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Move Field Down', 'paid-memberships-pro' ); ?></span>
			</div> <!-- end pmpro_userfield-group-buttons -->
		</li>
		<li class="pmpro_userfield-group-column-label">
			<span class="pmpro_userfield-label"><?php echo strip_tags( wp_kses_post( $field_label ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<div class="pmpro_userfield-field-options">
				<a class="edit-field" title="<?php esc_attr_e( 'Edit field', 'paid-memberships-pro' ); ?>" href="javascript:void(0);"><?php esc_html_e( 'Edit', 'paid-memberships-pro' ); ?></a> |
				<a class="duplicate-field" title="<?php esc_attr_e( 'Duplicate field', 'paid-memberships-pro' ); ?>" href="javascript:void(0);"><?php esc_html_e( 'Duplicate', 'paid-memberships-pro' ); ?></a> |
				<a class="delete-field" title="<?php esc_attr_e( 'Delete field', 'paid-memberships-pro' ); ?>" href="javascript:void(0);"><?php esc_html_e( 'Delete', 'paid-memberships-pro' ); ?></a>
			</div> <!-- end pmpro_userfield-group-options -->
		</li>
		<li class="pmpro_userfield-group-column-name"><?php echo esc_html( $field_name); ?></li>
		<li class="pmpro_userfield-group-column-type"><?php echo esc_html( $field_type); ?></li>
	</ul>

	<div class="pmpro_userfield-field-settings" style="display: none;">

		<div id="pmpro_userfield-field-setting_label" class="pmpro_userfield-field-setting pmpro_userfield-field-setting_1-2">
			<label for="pmpro_userfields_field_label"><?php esc_html_e( 'Label', 'paid-memberships-pro' ); ?></label>
			<input type="text" name="pmpro_userfields_field_label" id="pmpro_userfields_field_label" value="<?php echo esc_attr( $field_label );?>" />
			<span class="description"><?php esc_html_e( 'Brief descriptive text for the field. Shown on user forms.', 'paid-memberships-pro' ); ?></span>
		</div> <!-- end pmpro_userfield-field-setting -->

		<div id="pmpro_userfield-field-setting_name" class="pmpro_userfield-field-setting pmpro_userfield-field-setting_1-2">
			<label for="pmpro_userfields_field_name"><?php esc_html_e( 'Name', 'paid-memberships-pro' ); ?></label>
			<input type="text" name="pmpro_userfields_field_name" id="pmpro_userfields_field_name" value="<?php echo esc_attr( $field_name );?>" />
			<span class="description"><?php esc_html_e( 'Single word with no spaces. Underscores are allowed.', 'paid-memberships-pro' ); ?></span>
		</div> <!-- end pmpro_userfield-field-setting -->

		<div id="pmpro_userfield-field-setting_type" class="pmpro_userfield-field-setting pmpro_userfield-field-setting_1-2">
			<label for="pmpro_userfields_field_type"><?php esc_html_e( 'Type', 'paid-memberships-pro' ); ?></label>
			<select name="pmpro_userfields_field_type" id="pmpro_userfields_field_type">
				<option value="text" <?php selected( $field_type, 'text' ); ?>><?php esc_html_e( 'Text', 'paid-memberships-pro' ); ?></option>
				<option value="textarea" <?php selected( $field_type, 'textarea' ); ?>><?php esc_html_e( 'Text Area', 'paid-memberships-pro' ); ?></option>
				<option value="checkbox" <?php selected( $field_type, 'checkbox' ); ?>><?php esc_html_e( 'Checkbox', 'paid-memberships-pro' ); ?></option>
				<option value="checkbox_grouped" <?php selected( $field_type, 'checkbox_grouped' ); ?>><?php esc_html_e( 'Checkbox Group', 'paid-memberships-pro' ); ?></option>
				<option value="radio" <?php selected( $field_type, 'radio' ); ?>><?php esc_html_e( 'Radio', 'paid-memberships-pro' ); ?></option>
				<option value="select" <?php selected( $field_type, 'select' ); ?>><?php esc_html_e( 'Select / Dropdown', 'paid-memberships-pro' ); ?></option>
				<option value="select2" <?php selected( $field_type, 'select2' ); ?>><?php esc_html_e( 'Select2 / Autocomplete', 'paid-memberships-pro' ); ?></option>
				<option value="multiselect" <?php selected( $field_type, 'multiselect' ); ?>><?php esc_html_e( 'Multi Select', 'paid-memberships-pro' ); ?></option>
				<option value="file" <?php selected( $field_type, 'file' ); ?>><?php esc_html_e( 'File', 'paid-memberships-pro' ); ?></option>
				<option value="number" <?php selected( $field_type, 'number' ); ?>><?php esc_html_e( 'Number', 'paid-memberships-pro' ); ?></option>
				<option value="date" <?php selected( $field_type, 'date' ); ?>><?php esc_html_e( 'Date', 'paid-memberships-pro' ); ?></option>
				<option value="readonly" <?php selected( $field_type, 'readonly' ); ?>><?php esc_html_e( 'Read-Only', 'paid-memberships-pro' ); ?></option>
				<option value="hidden" <?php selected( $field_type, 'hidden' ); ?>><?php esc_html_e( 'Hidden', 'paid-memberships-pro' ); ?></option>
			</select>
		</div> <!-- end pmpro_userfield-field-setting -->

		<div id="pmpro_userfield-field-setting_required" class="pmpro_userfield-field-setting pmpro_userfield-field-setting_1-4">
			<label for="pmpro_userfields_field_required"><?php esc_html_e( 'Required at Checkout?', 'paid-memberships-pro' ); ?></label>
			<select name="pmpro_userfields_field_required" id="pmpro_userfields_field_required">
				<option value="no" <?php selected( $field_required, 'no' );?>><?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></option>
				<option value="yes" <?php selected( $field_required, 'yes' );?>><?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></option>
			</select>
		</div> <!-- end pmpro_userfield-field-setting -->

		<div id="pmpro_userfield-field-setting_readonly" class="pmpro_userfield-field-setting pmpro_userfield-field-setting_1-4">
			<label for="pmpro_userfields_field_readonly"><?php esc_html_e( 'Read Only?', 'paid-memberships-pro' ); ?></label>
			<select name="pmpro_userfields_field_readonly" id="pmpro_userfields_field_readonly">
				<option value="no" <?php selected( $field_readonly, 'no' );?>><?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></option>
				<option value="yes" <?php selected( $field_readonly, 'yes' );?>><?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></option>
			</select>
		</div> <!-- end pmpro_userfield-field-setting -->

		<div id="pmpro_userfield-field-setting_profile" class="pmpro_userfield-field-setting pmpro_userfield-field-setting_1-2">
			<label for="pmpro_userfields_field_profile"><?php esc_html_e( 'Show field on user profile?', 'paid-memberships-pro' ); ?></label>
			<select name="pmpro_userfields_field_profile" id="pmpro_userfields_field_profile">
				<option value="" <?php selected( empty( $field_profile ), 0);?>><?php esc_html_e( '[Inherit Group Setting]', 'paid-memberships-pro' ); ?></option>
				<option value="yes" <?php selected( $field_profile, 'yes' );?>><?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></option>
				<option value="admins" <?php selected( $field_profile, 'admins' );?>><?php esc_html_e( 'Yes (only admins)', 'paid-memberships-pro' ); ?></option>
				<option value="no" <?php selected( $field_profile, 'no' );?>><?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></option>
			</select>
		</div> <!-- end pmpro_userfield-field-setting -->

		<div id="pmpro_userfield-field-setting_wrapper_class" class="pmpro_userfield-field-setting pmpro_userfield-field-setting_1-4">
			<label for="pmpro_userfields_field_class"><?php esc_html_e( 'Field Wrapper Class (optional)', 'paid-memberships-pro' ); ?></label>
			<input type="text" name="pmpro_userfields_field_class" id="pmpro_userfields_field_class" value="<?php echo esc_attr( $field_wrapper_class );?>" />
			<span class="description"><?php esc_html_e( 'Assign a custom CSS selector to the field\'s wrapping div', 'paid-memberships-pro' ); ?>.</span>
		</div> <!-- end pmpro_userfield-field-setting -->

		<div id="pmpro_userfield-field-setting_element_class" class="pmpro_userfield-field-setting pmpro_userfield-field-setting_1-4">
			<label for="pmpro_userfields_field_divclass"><?php esc_html_e( 'Field Element Class (optional)', 'paid-memberships-pro' ); ?></label>
			<input type="text" name="pmpro_userfields_field_divclass" id="pmpro_userfields_field_divclass" value="<?php echo esc_attr( $field_element_class );?>" />
			<span class="description"><?php esc_html_e( 'Assign a custom CSS selector to the field', 'paid-memberships-pro' ); ?></span>
		</div> <!-- end pmpro_userfield-field-setting -->

		<div id="pmpro_userfield-field-setting_hint" class="pmpro_userfield-field-setting pmpro_userfield-field-setting_1-2">
			<label for="pmpro_userfields_field_hint"><?php esc_html_e( 'Hint (optional)', 'paid-memberships-pro' ); ?></label>
			<textarea name="pmpro_userfields_field_hint" id="pmpro_userfields_field_hint" /><?php echo esc_textarea( $field_hint );?></textarea>
			<span class="description"><?php esc_html_e( 'Descriptive text for users or admins submitting the field.', 'paid-memberships-pro' ); ?></span>
		</div> <!-- end pmpro_userfield-field-setting -->

		<div id="pmpro_userfield-field-setting_options" class="pmpro_userfield-field-setting pmpro_userfield-field-setting_1-2">
			<label for="pmpro_userfields_field_options"><?php esc_html_e( 'Options', 'paid-memberships-pro' ); ?></label>
			<textarea name="pmpro_userfields_field_options" id="pmpro_userfields_field_options" /><?php echo esc_textarea( $field_options );?></textarea>
			<span class="description"><?php esc_html_e( 'One option per line. To set separate values and labels, use value:label.', 'paid-memberships-pro' ); ?></span>
		</div> <!-- end pmpro_userfield-field-setting -->
		
		<div id="pmpro_userfield-field-setting_default" class="pmpro_userfield-field-setting pmpro_userfield-field-setting_1-4">
			<label for="pmpro_userfields_field_default"><?php esc_html_e( 'Default Value (optional)', 'paid-memberships-pro' ); ?></label>
			<input type="text" name="pmpro_userfields_field_default" id="pmpro_userfields_field_default" value="<?php echo esc_attr( $field_default ); ?>" />
		</div> <!-- end pmpro_userfield-field-setting -->

		<div id="pmpro_userfield-row-settings_files" class="pmpro_userfield-row-settings">

			<div id="pmpro_userfield-field-setting_allowed_file_types" class="pmpro_userfield-field-setting pmpro_userfield-field-setting_1-2">
				<label for="pmpro_userfields_field_allowed_file_types"><?php esc_html_e( 'Allowed File Types', 'paid-memberships-pro' ); ?></label>
				<input type="text" name="pmpro_userfields_field_allowed_file_types" id="pmpro_userfields_field_allowed_file_types" value="<?php echo esc_attr( trim( $field_allowed_file_types ) ); ?>" />
				<span class="description"><?php esc_html_e( 'Restrict the file type that is allowed to be uploaded. Separate the file types using a comma ",". For example: png,pdf,jpg.', 'paid-memberships-pro' ); ?></span>
			</div> <!-- end pmpro_userfield-field-setting -->

			<div id="pmpro_userfield-field-setting_max_file_size" class="pmpro_userfield-field-setting pmpro_userfield-field-setting_1-2">
				<?php $server_max_upload = wp_max_upload_size() / 1024 / 1024; ?>
				<label for="pmpro_userfields_field_max_file_size"><?php esc_html_e( 'Max File Size Upload', 'paid-memberships-pro' ); ?></label>
				<input type="number" name="pmpro_userfields_field_max_file_size" id="pmpro_userfields_field_max_file_size" value="<?php echo intval( $field_max_file_size ); ?>" max="<?php echo esc_attr( $server_max_upload ); ?>"/>
				<span class="description"><?php printf( esc_html__( 'Enter an upload size limit for files in Megabytes (MB) or set it to 0 to use your default server upload limit. Your server upload limit is %s.', 'paid-memberships-pro' ), esc_html( $server_max_upload . 'MB' ) ); ?></span>
			</div> <!-- end pmpro_userfield-field-setting -->

		</div> <!-- end #pmpro_userfield-row-settings_files -->

		<div class="pmpro_userfield-field-actions">
			<button name="pmpro_userfields_close_field" class="button button-secondary pmpro_userfields_close_field">
				<?php esc_html_e( 'Close Field', 'paid-memberships-pro' ); ?>
			</button>
			<button name="pmpro_userfields_delete_field" class="button button-secondary is-destructive">
				<?php esc_html_e( 'Delete Field', 'paid-memberships-pro' ); ?>
			</button>
		</div> <!-- end pmpro_userfield-field-actions -->
	</div> <!-- end pmpro_userfield-field-settings -->
</div> <!-- end pmpro_userfield-group-field -->
