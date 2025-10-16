<?php
/**
 * @var string $edit_group The group being edited or empty if adding a new group.
 */

global $wpdb, $pmpro_msg, $pmpro_msgt;

$group = null;
if ( ! empty( $edit_group ) ) {
	// Get the current user fields settings.
	$current_settings = pmpro_get_user_fields_settings();

	// Find the group.
	foreach( $current_settings as $group_settings ) {
		if ( $group_settings->name === $edit_group ) {
			$group = $group_settings;
			break;
		}
	}
}

// If we still don't have a group, get default settings.
if ( empty( $group ) ) {
	$group = new stdClass();
	$group->name = '';
	$group->label = '';
	$group->checkout = 'yes';
	$group->profile = 'yes';
	$group->description = '';
	$group->levels = array();
}

// If the name is set but not the label, set the label to the name.
if ( ! empty( $group->name ) && empty( $group->label ) ) {
	$group->label = $group->name;
}

// Get all membership levels.
$levels = pmpro_sort_levels_by_order( pmpro_getAllLevels( true, true ) );

?>
<hr class="wp-header-end">
<?php if ( ! empty( $group->name ) ) { ?>
	<h1 class="wp-heading-inline">
		<?php
		echo sprintf(
			// translators: %s is the Level ID.
			esc_html__('Edit Field Group : %s', 'paid-memberships-pro'),
			esc_attr( $group->name )
		);
		?>
	</h1>
<?php } else { ?>
	<h1 class="wp-heading-inline"><?php esc_html_e('Add New Field Group', 'paid-memberships-pro'); ?></h1>
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
	<input type="hidden" name="action" value="save_group" />
	<?php wp_nonce_field('save_group', 'pmpro_userfields_nonce'); ?>

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
							<input id="label" name="label" type="text" value="<?php echo esc_attr( $group->label ) ?>" class="regular-text" required />
							<p class="description"><?php esc_html_e('Brief descriptive text for the field group. Shown as a header on user forms.', 'paid-memberships-pro'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label for="name"><?php esc_html_e('Name', 'paid-memberships-pro'); ?></label></th>
						<td>
							<input type="hidden" name="original_name" value="<?php echo esc_attr( $group->name ); ?>" />
							<input id="name" name="name" type="text" value="<?php echo esc_attr( $group->name ) ?>" class="regular-text" required />
							<p class="description"><?php esc_html_e('Single word with no spaces. Underscores are allowed. This is the name to represent the group in code.', 'paid-memberships-pro'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label for="description"><?php esc_html_e('Description', 'paid-memberships-pro'); ?></label></th>
						<td>
							<textarea id="description" name="description" class="large-text" rows="5"><?php echo esc_textarea( $group->description ); ?></textarea>
							<p class="description"><?php esc_html_e('Descriptive text for users or admins viewing the field group.', 'paid-memberships-pro'); ?></p>
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
						<th scope="row" valign="top"><label for="checkout"><?php esc_html_e('Show at Checkout?', 'paid-memberships-pro'); ?></label></th>
						<td>
							<select id="checkout" name="checkout">
								<option value="yes" <?php selected( $group->checkout, 'yes' ); ?>><?php esc_html_e('Yes', 'paid-memberships-pro'); ?></option>
								<option value="no" <?php selected( $group->checkout, 'no' ); ?>><?php esc_html_e('No', 'paid-memberships-pro'); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label for="profile"><?php esc_html_e('Show on User Profile?', 'paid-memberships-pro'); ?></label></th>
						<td>
							<select id="profile" name="profile">
								<option value="yes" <?php selected( $group->profile, 'yes' ); ?>><?php esc_html_e('Yes', 'paid-memberships-pro'); ?></option>
								<option value="admins" <?php selected( $group->profile, 'admins' ); ?>><?php esc_html_e('Yes (only admins)', 'paid-memberships-pro'); ?></option>
								<option value="no" <?php selected( $group->profile, 'no' ); ?>><?php esc_html_e('No', 'paid-memberships-pro'); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label for="levels"><?php esc_html_e('Restrict Fields for Membership Levels', 'paid-memberships-pro'); ?></label></th>
						<td>
							<div class="pmpro_checkbox_box" <?php if ( count( $levels ) > 3 ) { ?>style="height: 90px; overflow: auto;"<?php } ?>>
								<?php foreach( $levels as $level ) { ?>
									<div class="pmpro_clickable">
										<label>
											<input type="checkbox" name="levels[]" <?php checked( true, in_array( $level->id, $group->levels ) );?> value="<?php echo esc_attr( $level->id); ?>">
											<?php echo esc_html( $level->name ); ?>
										</label>
									</div>
								<?php } ?>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div> <!-- end pmpro_section_inside -->
	</div> <!-- end pmpro_section -->

	<p class="submit">
		<input name="save" type="submit" class="button button-primary" value="<?php esc_attr_e('Save Group', 'paid-memberships-pro'); ?>" />
		<input name="cancel" type="button" class="button" value="<?php esc_attr_e('Cancel', 'paid-memberships-pro'); ?>" onclick="location.href='<?php echo esc_url(add_query_arg('page', 'pmpro-userfields', admin_url('admin.php'))); ?>';" />
	</p>
</form>