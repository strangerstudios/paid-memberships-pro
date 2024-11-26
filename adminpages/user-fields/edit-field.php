<?php
/**
 * @var string $edit The field being edited or empty if adding a new field.
 */

global $wpdb, $pmpro_msg, $pmpro_msgt;

$field = null;
if ( ! empty( $edit ) ) {
	// Get the current user fields settings.
	$current_settings = pmpro_get_user_fields_settings();

	// Find the field.
	foreach( $current_settings as $group ) {
		foreach( $group->fields as $f ) {
			if ( $f->name === $edit ) {
				$field = $f;
				$field->group = $group->name;
				break 2;
			}
		}
	}
}

// If we still don't have a field, get default settings.
if ( empty( $field ) ) {
	$field = new stdClass();
	$field->name = '';
	$field->label = '';
	$field->type = '';
	$field->required = false;
	$field->readonly = false;
	$field->profile = '';
	$field->wrapper_class = '';
	$field->element_class = '';
	$field->hint = '';
	$field->default = '';
	$field->options = '';
	$field->allowed_file_types = '';
	$field->max_file_size = '';
	$field->group = empty( $_REQUEST['group'] ) ? '' : sanitize_text_field( $_REQUEST['group'] );
}

?>
<hr class="wp-header-end">
<?php if ( ! empty( $field->name ) ) { ?>
	<h1 class="wp-heading-inline">
		<?php
		echo sprintf(
			// translators: %s is the Level ID.
			esc_html__('Edit Field : %s', 'paid-memberships-pro'),
			esc_attr( $field->name )
		);
		?>
	</h1>
<?php } else { ?>
	<h1 class="wp-heading-inline"><?php esc_html_e('Add New Field', 'paid-memberships-pro'); ?></h1>
<?php } ?>

<?php
// Show the settings page message.
if (!empty($pmpro_msg)) { ?>
	<div class="inline notice notice-large <?php echo $pmpro_msgt > 0 ? 'notice-success' : 'notice-error'; ?>">
		<p><?php echo wp_kses_post( $pmpro_msg ); ?></p>
	</div>
<?php }
?>
<form action="" method="post" enctype="multipart/form-data">
	<input type="hidden" name="action" value="save_field" />
	<?php wp_nonce_field('save_field', 'pmpro_userfields_nonce'); ?>

	<div id="general-information" class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
				<?php esc_html_e('General Information', 'paid-memberships-pro'); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row" valign="top"><label for="name"><?php esc_html_e('Label', 'paid-memberships-pro'); ?></label></th>
						<td>
							<input id="label" name="label" type="text" value="<?php echo esc_attr( $field->label ) ?>" class="regular-text" required />
							<p class="description"><?php esc_html_e('Brief descriptive text for the field. Shown on user forms.', 'paid-memberships-pro'); ?></p>
						</td>
					</tr>
					<?php
					if ( empty( $field->name ) ) {
						?>
						<tr>
							<th scope="row" valign="top"><label for="name"><?php esc_html_e('Name', 'paid-memberships-pro'); ?></label></th>
							<td>
								<input id="name" name="name" type="text" value="<?php echo esc_attr( $field->name ) ?>" class="regular-text" required />
								<p class="description"><?php esc_html_e('Single word with no spaces. Underscores are allowed. This is the field name used in the database.', 'paid-memberships-pro'); ?></p>
							</td>
						</tr>
						<?php
					} else {
						?>
						<input type="hidden" name="name" value="<?php echo esc_attr( $field->name ); ?>" />
						<?php
					}
					?>
					<tr>
						<th scope="row" valign="top"><label for="group"><?php esc_html_e('Group', 'paid-memberships-pro'); ?></label></th>
						<td>
							<select id="group" name="group" required>
								<?php
								$groups = PMPro_Field_Group::get_all();
								foreach ( $groups as $group ) {
									?>
									<option value="<?php echo esc_attr( $group->name ); ?>" <?php selected( $field->group, $group->name ); ?>><?php echo esc_html( $group->label ); ?></option>
									<?php
								}
								?>
							</select>
							<p class="description"><?php esc_html_e('The group this field belongs to.', 'paid-memberships-pro'); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div> <!-- end pmpro_section_inside -->
	</div> <!-- end pmpro_section -->

	<div id="field-attributes" class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
				<?php esc_html_e('Field Attributes', 'paid-memberships-pro'); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row" valign="top"><label for="type"><?php esc_html_e('Type', 'paid-memberships-pro'); ?></label></th>
						<td>
							<select id="type" name="type" required>
								<?php
								$types = array(
									'text' => 'Text',
									'textarea' => 'Text Area',
									'checkbox' => 'Checkbox',
									'checkbox_grouped' => 'Checkbox Group',
									'radio' => 'Radio',
									'select' => 'Select / Dropdown',
									'select2' => 'Select2 / Autocomplete',
									'multiselect' => 'Multi Select',
									'file' => 'File',
									'number' => 'Number',
									'date' => 'Date',
									'readonly' => 'Read-Only',
									'hidden' => 'Hidden',
								);
								foreach ( $types as $type => $label ) {
									?>
									<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $field->type, $type ); ?>><?php echo esc_html( $label ); ?></option>
									<?php
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label for="required"><?php esc_html_e('Required At Checkout?', 'paid-memberships-pro'); ?></label></th>
						<td>
							<select id="required" name="required">
								<option value="no" <?php selected( $field->required, 'no' ); ?>><?php esc_html_e('No', 'paid-memberships-pro'); ?></option>
								<option value="yes" <?php selected( $field->required, 'yes' ); ?>><?php esc_html_e('Yes', 'paid-memberships-pro'); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label for="hint"><?php esc_html_e('Hint', 'paid-memberships-pro'); ?></label></th>
						<td>
							<input id="hint" name="hint" type="text" value="<?php echo esc_attr( $field->hint ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e('Descriptive text for users or admins submitting the field.', 'paid-memberships-pro'); ?></p>
						</td>
					</tr>
					<tr class="field_type field_type_checkbox_grouped field_type_radio field_type_select field_type_select2 field_type_multiselect">
						<th scope="row" valign="top"><label for="options"><?php esc_html_e('Options', 'paid-memberships-pro'); ?></label></th>
						<td>
							<textarea id="options" name="options" class="large-text" rows="5"><?php echo esc_textarea( $field->options ); ?></textarea>
							<p class="description"><?php esc_html_e( 'One option per line. To set separate values and labels, use value:label.', 'paid-memberships-pro'); ?></p>
						</td>
					</tr>
					<tr class="field_type field_type_text field_type_textarea field_type_checkbox field_type_radio field_type_select field_type_date field_type_readonly field_type_hidden field_type_number">
						<th scope="row" valign="top"><label for="default"><?php esc_html_e('Default Value', 'paid-memberships-pro'); ?></label></th>
						<td><input id="default" name="default" type="text" value="<?php echo esc_attr( $field->default ); ?>" class="regular-text" /></td>
					</tr>
					<tr class="field_type field_type_file">
						<th scope="row" valign="top"><label for="allowed_file_types"><?php esc_html_e('Allowed File Types', 'paid-memberships-pro'); ?></label></th>
						<td>
							<input id="allowed_file_types" name="allowed_file_types" type="text" value="<?php echo esc_attr( $field->allowed_file_types ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Restrict the file type that is allowed to be uploaded. Separate the file types using a comma ",". For example: png,pdf,jpg.', 'paid-memberships-pro' ); ?></p>
						</td>
					</tr>
					<tr class="field_type field_type_file">
						<th scope="row" valign="top"><label for="max_file_size"><?php esc_html_e('Max File Size', 'paid-memberships-pro'); ?></label></th>
						<td>
							<input id="max_file_size" name="max_file_size" type="text" value="<?php echo esc_attr( $field->max_file_size ); ?>" class="regular-text" />
							<?php $server_max_upload = wp_max_upload_size() / 1024 / 1024; ?>
							<p class="description"><?php printf( esc_html__( 'Enter an upload size limit for files in Megabytes (MB) or set it to 0 to use your default server upload limit. Your server upload limit is %s.', 'paid-memberships-pro' ), $server_max_upload . 'MB' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div> <!-- end pmpro_section_inside -->
	</div> <!-- end pmpro_section -->

	<div id="visibility-settings" class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
				<?php esc_html_e('Visibility Settings', 'paid-memberships-pro'); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row" valign="top"><label for="profile"><?php esc_html_e('Show on User Profile?', 'paid-memberships-pro'); ?></label></th>
						<td>
							<select id="profile" name="profile">
								<option value="" <?php selected( empty( $field ) ? 0 : $field->profile, 0 ); ?>><?php esc_html_e('[Inherit Group Setting]', 'paid-memberships-pro'); ?></option>
								<option value="yes" <?php selected( empty( $field ) ? 0 : $field->profile, 'yes' ); ?>><?php esc_html_e('Yes', 'paid-memberships-pro'); ?></option>
								<option value="admins" <?php selected( empty( $field ) ? 0 : $field->profile, 'admins' ); ?>><?php esc_html_e('Yes (only admins)', 'paid-memberships-pro'); ?></option>
								<option value="no" <?php selected( empty( $field ) ? 0 : $field->profile, 'no' ); ?>><?php esc_html_e('No', 'paid-memberships-pro'); ?></option>
							</select>
						</td>
					</tr>
				</tbody>
			</table>
		</div> <!-- end pmpro_section_inside -->
	</div> <!-- end pmpro_section -->

	<div id="additional-styles" class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
				<?php esc_html_e('Additional Styling', 'paid-memberships-pro'); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row" valign="top"><label for="profile"><?php esc_html_e('Read Only?', 'paid-memberships-pro'); ?></label></th>
						<td>
							<select id="readonly" name="readonly">
								<option value="no" <?php selected( $field->readonly, 'no' ); ?>><?php esc_html_e('No', 'paid-memberships-pro'); ?></option>
								<option value="yes" <?php selected( $field->readonly, 'yes' ); ?>><?php esc_html_e('Yes', 'paid-memberships-pro'); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label for="profile"><?php esc_html_e('Field Wrapper Class', 'paid-memberships-pro'); ?></label></th>
						<td>
							<input id="wrapper_class" name="wrapper_class" type="text" value="<?php echo esc_attr( $field->wrapper_class ); ?>" />
							<p class="description"><?php esc_html_e('Assign a custom CSS selector to the field\'s wrapping div.', 'paid-memberships-pro'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label for="profile"><?php esc_html_e('Field Element Class', 'paid-memberships-pro'); ?></label></th>
						<td>
							<input id="element_class" name="element_class" type="text" value="<?php echo esc_attr( $field->element_class ); ?>"/>
							<p class="description"><?php esc_html_e('Assign a custom CSS selector to the field.', 'paid-memberships-pro'); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div> <!-- end pmpro_section_inside -->
	</div> <!-- end pmpro_section -->

	<p class="submit">
		<input name="save" type="submit" class="button button-primary" value="<?php esc_attr_e('Save Field', 'paid-memberships-pro'); ?>" />
		<input name="cancel" type="button" class="button" value="<?php esc_attr_e('Cancel', 'paid-memberships-pro'); ?>" onclick="location.href='<?php echo esc_url(add_query_arg('page', 'pmpro-userfields', admin_url('admin.php'))); ?>';" />
	</p>
</form>