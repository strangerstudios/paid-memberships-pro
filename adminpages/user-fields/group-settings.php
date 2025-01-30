<?php

if ( ! empty( $group ) ) {
	// Assume group stdClass in format we save to settings.
	$group_name = $group->name;
	$group_show_checkout = $group->checkout;
	$group_show_profile = $group->profile;
	$group_description = $group->description;    	
	$group_levels = $group->levels;
	$group_fields = $group->fields;
} else {
	// Default group settings.
	$group_name = '';
	$group_show_checkout = 'yes';
	$group_show_profile = 'yes';
	$group_description = '';    	
	$group_levels = array();
	$group_fields = array();
}

// Other vars
$levels = pmpro_sort_levels_by_order( pmpro_getAllLevels( true, true ) );

// Render field group HTML.
?>
<div class="pmpro_userfield-group">
	<div class="pmpro_userfield-group-header">
		<div class="pmpro_userfield-group-buttons">
			<button type="button" aria-disabled="false" class="pmpro_userfield-group-buttons-button pmpro_userfield-group-buttons-button-move-up" aria-label="<?php esc_attr_e( 'Move up', 'paid-memberships-pro' ); ?>">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
			</button>
			<span class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Move Group Up', 'paid-memberships-pro' ); ?></span>

			<button type="button" aria-disabled="false" class="pmpro_userfield-group-buttons-button pmpro_userfield-group-buttons-button-move-down" aria-label="<?php esc_attr_e( 'Move down', 'paid-memberships-pro' ); ?>">
				<span class="dashicons dashicons-arrow-down-alt2"></span>
			</button>
			<span id="pmpro_userfield-group-buttons-description-2" class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Move Group Down', 'paid-memberships-pro' ); ?></span>
		</div> <!-- end pmpro_userfield-group-buttons -->
		<h3>
			<label for="pmpro_userfields_group_name">
				<?php esc_html_e( 'Group Name', 'paid-memberships-pro' ); ?>
				<input type="text" name="pmpro_userfields_group_name" id="pmpro_userfields_group_name" placeholder="<?php esc_attr_e( 'Group Name', 'paid-memberships-pro' ); ?>" value="<?php echo esc_attr( $group_name ); ?>" />
			</label>
		</h3>
		<button type="button" aria-disabled="false" class="pmpro_userfield-group-buttons-button pmpro_userfield-group-buttons-button-toggle-group" aria-label="<?php esc_attr_e( 'Expand and Edit Group', 'paid-memberships-pro' ); ?>">
			<span class="dashicons dashicons-arrow-up"></span>
		</button>
		<span class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Expand and Edit Group', 'paid-memberships-pro' ); ?></span>
	</div> <!-- end pmpro_userfield-group-header -->

	<div class="pmpro_userfield-inside">
		<div class="pmpro_userfield-field-settings">
			
			<div id="pmpro_userfield-group-setting_group_checkout" class="pmpro_userfield-field-setting pmpro_userfield-field-setting_1-2">
				<label for="pmpro_userfields_group_checkout"><?php esc_html_e( 'Show fields at checkout?', 'paid-memberships-pro' ); ?></label>
				<select name="pmpro_userfields_group_checkout" id="pmpro_userfields_group_checkout">
					<option value="yes" <?php selected( $group_show_checkout, 'yes' ); ?>><?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></option>
					<option value="no" <?php selected( $group_show_checkout, 'no' ); ?>><?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></option>
				</select>
			</div> <!-- end pmpro_userfield-field-setting -->
			
			<div id="pmpro_userfield-group-setting_group_profile" class="pmpro_userfield-field-setting pmpro_userfield-field-setting_1-2">
				<label for="pmpro_userfields_group_profile"><?php esc_html_e( 'Show fields on user profile?', 'paid-memberships-pro' ); ?></label>
				<select name="pmpro_userfields_group_profile" id="pmpro_userfields_group_profile">
					<option value="yes" <?php selected( $group_show_profile, 'yes' ); ?>><?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></option>
					<option value="admins" <?php selected( $group_show_profile, 'admins' ); ?>><?php esc_html_e( 'Yes (only admins)', 'paid-memberships-pro' ); ?></option>
					<option value="no" <?php selected( $group_show_profile, 'no' ); ?>><?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></option>
				</select>
			</div> <!-- end pmpro_userfield-field-setting -->
			
			<div id="pmpro_userfield-group-setting_group_description" class="pmpro_userfield-field-setting pmpro_userfield-field-setting_1-2">
				<label for="pmpro_userfields_group_description"><?php esc_html_e( 'Description (optional, visible to users)', 'paid-memberships-pro' ); ?></label>
				<textarea name="pmpro_userfields_group_description" id="pmpro_userfields_group_description"><?php echo esc_textarea( $group_description );?></textarea>
			</div> <!-- end pmpro_userfield-field-setting -->
			
			<div id="pmpro_userfield-group-setting_group_membership" class="pmpro_userfield-field-setting pmpro_userfield-field-setting_1-2">
				<?php esc_html_e( 'Restrict Fields for Membership Levels', 'paid-memberships-pro' ); ?><br />
				<div class="pmpro_checkbox_box" <?php if ( count( $levels ) > 3 ) { ?>style="height: 90px; overflow: auto;"<?php } ?>>
					<?php foreach( $levels as $level ) { ?>
						<div class="pmpro_clickable">
							<label for="pmpro_userfields_group_membership_<?php echo esc_attr( $level->id); ?>">
								<input type="checkbox" id="pmpro_userfields_group_membership_<?php echo esc_attr( $level->id); ?>" name="pmpro_userfields_group_membership[]" <?php checked( true, in_array( $level->id, $group_levels ) );?>>
								<?php echo esc_html( $level->name ); ?>
							</label>
						</div>
					<?php } ?>
				</div>
			</div> <!-- end pmpro_userfield-field-setting -->
		
		</div> <!-- end pmpro_userfield-field-settings -->
		
		<h3><?php esc_html_e( 'Manage Fields in This Group', 'paid-memberships-pro' ); ?></h3>
		
		<ul class="pmpro_userfield-group-thead">
			<li class="pmpro_userfield-group-column-order"><?php esc_html_e( 'Order', 'paid-memberships-pro'); ?></li>
			<li class="pmpro_userfield-group-column-label"><?php esc_html_e( 'Label', 'paid-memberships-pro'); ?></li>
			<li class="pmpro_userfield-group-column-name"><?php esc_html_e( 'Name', 'paid-memberships-pro'); ?></li>
			<li class="pmpro_userfield-group-column-type"><?php esc_html_e( 'Type', 'paid-memberships-pro'); ?></li>
		</ul>
		
		<div class="pmpro_userfield-group-fields">
			<?php
				if ( ! empty( $group_fields ) ) {
					foreach ( $group_fields as $field ) {
						pmpro_get_field_html( $field );
					}
				}
			?>
			
			<!-- end pmpro_userfield-group-fields -->
		
		</div> <!-- end pmpro_userfield-inside -->

		<div class="pmpro_userfield-group-actions">
			<button name="pmpro_userfields_add_field" class="button button-secondary button-hero">
				<?php
					/* translators: a plus sign dashicon */
					printf( esc_html__( '%s Add Field', 'paid-memberships-pro' ), '<span class="dashicons dashicons-plus"></span>' ); ?>
			</button>
			<button name="pmpro_userfields_delete_group" class="button button-secondary is-destructive">
				<?php esc_html_e( 'Delete Group', 'paid-memberships-pro' ); ?>
			</button>
		</div> <!-- end pmpro_userfield-group-actions -->

	</div> <!-- end pmpro_userfield-group -->
</div> <!-- end inside -->
