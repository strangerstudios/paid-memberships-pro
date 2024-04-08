<?php

// Creating or editing a group.
// If we are editing an existing group, current group data.
if ( intval( $edit_group ) > 0) {
	$group = pmpro_get_level_group( $edit_group );
}

// If we don't have a group, create a new one.
if ( empty( $group ) ) {
	$group = new stdClass();
	$group->id = 0;
	$group->name = '';
	$group->displayorder = 0;
	$group->allow_multiple_selections = false;
}

// Check if we have any MMPU incompatible Add Ons.
$mmpu_incompatible_add_ons = pmpro_get_mmpu_incompatible_add_ons();

// Set up the UI.
?>
<hr class="wp-header-end">
<h1 class="wp-heading-inline">
	<?php if ( ! empty( $group->id ) ) {
		echo sprintf(
			// translators: %s is the group ID.
			esc_html__( 'Edit Group ID: %s', 'paid-memberships-pro' ),
			esc_attr( $group->id )
		);
	} else {
		esc_html_e( 'Add New Group', 'paid-memberships-pro' );
	} ?>
</h1>
<?php
if ( ! empty( $mmpu_incompatible_add_ons ) ) {
?>
	<div class="pmpro_message pmpro_error">
		<p>
			<?php
			echo sprintf(
				// translators: %s is the list of incompatible add ons.
				esc_html__( 'The following active Add Ons are not compatible with your membership level setup: %s', 'paid-memberships-pro' ),
				'<strong>' . esc_html( implode( ', ', $mmpu_incompatible_add_ons ) ) . '.</strong>'
			);
			?>
		</p>
		<p>
			<?php
			esc_html_e( 'This warning is shown because you have more than one level group or a level group that allows multiple selections. To continue using these Add Ons, you should move all levels to a single "one level per" group.', 'paid-memberships-pro' );
			?>
		</p>
	</div>
<?php
}
?>
<form action="<?php echo esc_attr( add_query_arg( 'page', 'pmpro-membershiplevels', admin_url( 'admin.php' ) ) ) ?>" method="post" enctype="multipart/form-data">
	<input name="saveid" type="hidden" value="<?php echo esc_attr( $edit_group ); ?>" />
	<input type="hidden" name="action" value="save_group" />
	<input type="hidden" name="displayorder" value="<?php echo esc_attr( $group->displayorder ); ?>" />
	<?php wp_nonce_field( 'save_group', 'pmpro_membershiplevels_nonce' ); ?>
	<div id="general-information" class="pmpro_section">
		<div class="pmpro_section_inside">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row" valign="top"><label for="name"><?php esc_html_e( 'Name', 'paid-memberships-pro' ); ?></label></th>
						<td><input id="name" name="name" type="text" size="60" value="<?php echo esc_attr( $group->name ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label for="allow_multiple_selections"><?php esc_html_e( 'Allow Multiple Selections', 'paid-memberships-pro' ); ?></label></th>
						<td>
							<input id="allow_multiple_selections" name="allow_multiple_selections" type="checkbox" value="1" <?php checked( $group->allow_multiple_selections, true ); ?> />
							<label for="allow_multiple_selections"><?php esc_html_e( 'Allow users to choose multiple levels from this group. Leave unchecked to only allow users to hold one level in this group.', 'paid-memberships-pro' ); ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
	<p class="submit">
		<input name="save" type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Group', 'paid-memberships-pro' ); ?>" />
		<input name="cancel" type="button" class="button" value="<?php esc_attr_e( 'Cancel', 'paid-memberships-pro' ); ?>" onclick="location.href='<?php echo esc_url( add_query_arg( 'page', 'pmpro-membershiplevels', admin_url( 'admin.php' ) ) ); ?>';" />
	</p>
</form>