<?php
	//only admins can get this
	if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_userfields' ) ) ) {
		die( __( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
	}

	/**
	 * Get all levels regardless of visibility.
	 *
	 */
	$levels = pmpro_getAllLevels( false, true );

	/**
	 * Load the common header for admin pages.
	 *
	 */
	require_once( dirname(__FILE__) . '/admin_header.php' );

	/**
	 * Meta boxes for User Fields admin page.
	 *
	 */
	add_meta_box(
		'pmpro_userfields_save',
		esc_html( 'Save', 'paid-memberships-pro' ),
		'pmpro_userfields_save_widget',
		'memberships_page_pmpro-userfields',
		'side'
	);
	add_meta_box(
		'pmpro_userfields_help',
		esc_html( 'User Fields Help', 'paid-memberships-pro' ),
		'pmpro_userfields_help_widget',
		'memberships_page_pmpro-userfields',
		'side'
	);

	/**
	 * Meta box to show a save button and other data.
	 *
	 */
	function pmpro_userfields_save_widget() { ?>
		<p class="submit">
			<input name="savesettings" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save All Settings', 'paid-memberships-pro' ); ?>" />
		</p>
		<?php
	}

	/**
	 * Meta box to show help information.
	 *
	 */
	function pmpro_userfields_help_widget() { ?>
		<p><?php esc_html_e( 'User fields can be added to the membership checkout form, the frontend user profile edit page, and for admins only on the Edit Users Screen in the WordPress admin.', 'paid-memberships-pro' ); ?></p>
		<p><?php esc_html_e( 'Groups are used to define a collection of fields that should be displayed together under a common heading. Group settings control field locations and membership level visibility.', 'paid-memberships-pro' ); ?></p>
		<?php
	}

	?>

	<form action="" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field('savesettings', 'pmpro_userfields_nonce');?>
		
		<h1 class="wp-heading-inline"><?php esc_html_e( 'User Fields', 'paid-memberships-pro' ); ?></h1>
		<hr class="wp-header-end">

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">

				<div id="post-body-content">
					<div class="inside">

						<div class="pmpro_userfield-group pmpro_userfield-group-collapse">
							<?php /* this is just a sample view of a collapsed group */ ?> 
							<div class="pmpro_userfield-group-header">
								<div class="pmpro_userfield-group-buttons">
									<button type="button" aria-describedby="pmpro_userfield-group-buttons-description-1" id="pmpro_userfield-group-buttons-button-move-up" aria-disabled="true" disabled="disabled" class="pmpro_userfield-group-buttons-button" aria-label="<?php esc_html_e( 'Move up', 'paid-memberships-pro' ); ?>">
										<span class="dashicons dashicons-arrow-up-alt2"></span>
									</button>
									<span id="pmpro_userfield-group-buttons-description-1" class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Move Group Up', 'paid-memberships-pro' ); ?></span>

									<button type="button" aria-describedby="pmpro_userfield-group-buttons-description-2" id="pmpro_userfield-group-buttons-button-move-down" aria-disabled="false" class="pmpro_userfield-group-buttons-button" aria-label="<?php esc_html_e( 'Move down', 'paid-memberships-pro' ); ?>">
										<span class="dashicons dashicons-arrow-down-alt2"></span>
									</button>
									<span id="pmpro_userfield-group-buttons-description-2" class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Move Group Down', 'paid-memberships-pro' ); ?></span>
								</div> <!-- end pmpro_userfield-group-buttons -->
								<h3>More Information</h3>
								<button type="button" aria-describedby="pmpro_userfield-group-buttons-description-3" id="pmpro_userfield-group-buttons-button-expand-group" aria-disabled="false" class="pmpro_userfield-group-buttons-button" aria-label="<?php esc_html_e( 'Expand and Edit Group', 'paid-memberships-pro' ); ?>">
										<span class="dashicons dashicons-arrow-right"></span>
									</button>
									<span id="pmpro_userfield-group-buttons-description-3" class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Expand and Edit Group', 'paid-memberships-pro' ); ?></span>
							</div> <!-- end pmpro_userfield-group-header -->
							<div class="pmpro_userfield-inside">
								blah blah blah this is hidden.
							</div> <!-- end pmpro_userfield-inside -->	
						</div>

						<div class="pmpro_userfield-group">
							<div class="pmpro_userfield-group-header">
								<div class="pmpro_userfield-group-buttons">
									<button type="button" aria-describedby="pmpro_userfield-group-buttons-description-1" id="pmpro_userfield-group-buttons-button-move-up" aria-disabled="false" class="pmpro_userfield-group-buttons-button" aria-label="<?php esc_html_e( 'Move up', 'paid-memberships-pro' ); ?>">
										<span class="dashicons dashicons-arrow-up-alt2"></span>
									</button>
									<span id="pmpro_userfield-group-buttons-description-1" class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Move Group Up', 'paid-memberships-pro' ); ?></span>

									<button type="button" aria-describedby="pmpro_userfield-group-buttons-description-2" id="pmpro_userfield-group-buttons-button-move-down" aria-disabled="false" class="pmpro_userfield-group-buttons-button" aria-label="<?php esc_html_e( 'Move down', 'paid-memberships-pro' ); ?>">
										<span class="dashicons dashicons-arrow-down-alt2"></span>
									</button>
									<span id="pmpro_userfield-group-buttons-description-2" class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Move Group Down', 'paid-memberships-pro' ); ?></span>
								</div> <!-- end pmpro_userfield-group-buttons -->
								<h3>
									<label for="pmpro_userfields_group_name"><?php esc_html_e( 'Group Name', 'paid-memberships-pro' ); ?></label>
									<input type="text" name="pmpro_userfields_group_name" placeholder="Group Name" value="About My Cat" />
								</h3>
								<button type="button" aria-describedby="pmpro_userfield-group-buttons-description-3" id="pmpro_userfield-group-buttons-button-expand-group" aria-disabled="false" class="pmpro_userfield-group-buttons-button" aria-label="<?php esc_html_e( 'Expand and Edit Group', 'paid-memberships-pro' ); ?>">
										<span class="dashicons dashicons-arrow-down"></span>
									</button>
									<span id="pmpro_userfield-group-buttons-description-3" class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Expand and Edit Group', 'paid-memberships-pro' ); ?></span>
							</div> <!-- end pmpro_userfield-group-header -->
							
							<div class="pmpro_userfield-inside">
								
								<div class="pmpro_userfield-field-settings">
									
									<div class="pmpro_userfield-field-setting">
										<label for="pmpro_userfields_group_checkout"><?php esc_html_e( 'Show group at checkout?', 'paid-memberships-pro' ); ?></label>
										<select name="pmpro_userfields_group_checkout">
											<option value="yes"><?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></option>
											<option value="no"><?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></option>
										</select>
									</div> <!-- end pmpro_userfield-field-setting -->
									
									<div class="pmpro_userfield-field-setting">
										<label for="pmpro_userfields_group_profile"><?php esc_html_e( 'Show group on user profile?', 'paid-memberships-pro' ); ?></label>
										<select name="pmpro_userfields_group_profile">
											<option value="yes"><?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></option>
											<option value="admins"><?php esc_html_e( 'Yes (only admins)', 'paid-memberships-pro' ); ?></option>
											<option value="no"><?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></option>
										</select>
									</div> <!-- end pmpro_userfield-field-setting -->
									
									<div class="pmpro_userfield-field-setting">
										<label for="pmpro_userfields_group_description"><?php esc_html_e( 'Description (visible to users)', 'paid-memberships-pro' ); ?></label>
										<textarea name="pmpro_userfields_group_description">Complete the fields below to share more information about your feline friend.</textarea>
									</div> <!-- end pmpro_userfield-field-setting -->
									
									<div class="pmpro_userfield-field-setting">
										<label for="pmpro_userfields_group_membership"><?php esc_html_e( 'Restrict Group for Membership Levels', 'paid-memberships-pro' ); ?></label>
										<div class="checkbox_box" <?php if ( count( $levels ) > 3 ) { ?>style="height: 90px; overflow: auto;"<?php } ?>>
											<?php foreach( $levels as $level ) { ?>
												<div class="clickable"><input type="checkbox" id="pmpro_userfields_group_membership_<?php echo $level->id?>" name="pmpro_userfields_group_membership[]"> <?php echo $level->name; ?></div>
											<?php } ?>
										</div>
									</div> <!-- end pmpro_userfield-field-setting -->
								
								</div> <!-- end pmpro_userfield-field-settings -->
								
								<h3>Manage Fields in This Group</h3>

								<ul class="pmpro_userfield-group-thead">
									<li class="pmpro_userfield-group-column-order"><?php esc_html_e( 'Order', 'paid-memberships-pro'); ?></li>
									<li class="pmpro_userfield-group-column-label"><?php esc_html_e( 'Label', 'paid-memberships-pro'); ?></li>
									<li class="pmpro_userfield-group-column-name"><?php esc_html_e( 'Name', 'paid-memberships-pro'); ?></li>
									<li class="pmpro_userfield-group-column-type"><?php esc_html_e( 'Type', 'paid-memberships-pro'); ?></li>
								</ul>
								
								<div class="pmpro_userfield-group-fields">

									<div class="pmpro_userfield-group-field pmpro_userfield-group-field-collapse">
										<ul class="pmpro_userfield-group-tbody">
											<li class="pmpro_userfield-group-column-order">
												<div class="pmpro_userfield-group-buttons">
													<button type="button" aria-describedby="pmpro_userfield-group-buttons-description-1" id="pmpro_userfield-group-buttons-button-move-up" aria-disabled="true" disabled="disabled" class="pmpro_userfield-group-buttons-button" aria-label="<?php esc_html_e( 'Move up', 'paid-memberships-pro' ); ?>">
														<span class="dashicons dashicons-arrow-up-alt2"></span>
													</button>
													<span id="pmpro_userfield-group-buttons-description-1" class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Move Field Up', 'paid-memberships-pro' ); ?></span>

													<button type="button" aria-describedby="pmpro_userfield-group-buttons-description-2" id="pmpro_userfield-group-buttons-button-move-down" aria-disabled="false" class="pmpro_userfield-group-buttons-button" aria-label="<?php esc_html_e( 'Move down', 'paid-memberships-pro' ); ?>">
														<span class="dashicons dashicons-arrow-down-alt2"></span>
													</button>
													<span id="pmpro_userfield-group-buttons-description-2" class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Move Field Down', 'paid-memberships-pro' ); ?></span>
												</div> <!-- end pmpro_userfield-group-buttons -->
											</li>
											<li class="pmpro_userfield-group-column-label">
												<span class="pmpro_userfield-label">Cat's Name</span>
												<div class="pmpro_userfield-group-options">
													<a class="edit-field" title="<?php esc_html_e( 'Edit field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Edit', 'paid-memberships-pro' ); ?></a> |
													<a class="duplicate-field" title="<?php esc_html_e( 'Duplicate field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Duplicate', 'paid-memberships-pro' ); ?></a> |
													<a class="delete-field" title="<?php esc_html_e( 'Delete field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Delete', 'paid-memberships-pro' ); ?></a>
												</div> <!-- end pmpro_userfield-group-options -->
											</li>
											<li class="pmpro_userfield-group-column-name">cat_name</li>
											<li class="pmpro_userfield-group-column-type">Text</li>
										</ul>
									</div> <!-- end pmpro_userfield-group-field -->

									<div class="pmpro_userfield-group-field pmpro_userfield-group-field-collapse">
										<ul class="pmpro_userfield-group-tbody">
											<li class="pmpro_userfield-group-column-order">
												<div class="pmpro_userfield-group-buttons">
													<button type="button" aria-describedby="pmpro_userfield-group-buttons-description-1" id="pmpro_userfield-group-buttons-button-move-up" aria-disabled="false" class="pmpro_userfield-group-buttons-button" aria-label="<?php esc_html_e( 'Move up', 'paid-memberships-pro' ); ?>">
														<span class="dashicons dashicons-arrow-up-alt2"></span>
													</button>
													<span id="pmpro_userfield-group-buttons-description-1" class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Move Field Up', 'paid-memberships-pro' ); ?></span>

													<button type="button" aria-describedby="pmpro_userfield-group-buttons-description-2" id="pmpro_userfield-group-buttons-button-move-down" aria-disabled="false" class="pmpro_userfield-group-buttons-button" aria-label="<?php esc_html_e( 'Move down', 'paid-memberships-pro' ); ?>">
														<span class="dashicons dashicons-arrow-down-alt2"></span>
													</button>
													<span id="pmpro_userfield-group-buttons-description-2" class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Move Field Down', 'paid-memberships-pro' ); ?></span>
												</div> <!-- end pmpro_userfield-group-buttons -->
											</li>
											<li class="pmpro_userfield-group-column-label">
												<span class="pmpro_userfield-label">Cat's Breed</span>
												<div class="pmpro_userfield-group-options">
													<a class="edit-field" title="<?php esc_html_e( 'Edit field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Edit', 'paid-memberships-pro' ); ?></a> |
													<a class="duplicate-field" title="<?php esc_html_e( 'Duplicate field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Duplicate', 'paid-memberships-pro' ); ?></a> |
													<a class="delete-field" title="<?php esc_html_e( 'Delete field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Delete', 'paid-memberships-pro' ); ?></a>
												</div> <!-- end pmpro_userfield-group-options -->
											</li>
											<li class="pmpro_userfield-group-column-name">cat_breed</li>
											<li class="pmpro_userfield-group-column-type">Select</li>
										</ul>
									</div> <!-- end pmpro_userfield-group-field -->

									<div class="pmpro_userfield-group-field pmpro_userfield-group-field-expand">
										<ul class="pmpro_userfield-group-tbody">
											<li class="pmpro_userfield-group-column-order">
												<div class="pmpro_userfield-group-buttons">
													<button type="button" aria-describedby="pmpro_userfield-group-buttons-description-1" id="pmpro_userfield-group-buttons-button-move-up" aria-disabled="false" class="pmpro_userfield-group-buttons-button" aria-label="<?php esc_html_e( 'Move up', 'paid-memberships-pro' ); ?>">
														<span class="dashicons dashicons-arrow-up-alt2"></span>
													</button>
													<span id="pmpro_userfield-group-buttons-description-1" class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Move Field Up', 'paid-memberships-pro' ); ?></span>

													<button type="button" aria-describedby="pmpro_userfield-group-buttons-description-2" id="pmpro_userfield-group-buttons-button-move-down" aria-disabled="false" class="pmpro_userfield-group-buttons-button" aria-label="<?php esc_html_e( 'Move down', 'paid-memberships-pro' ); ?>">
														<span class="dashicons dashicons-arrow-down-alt2"></span>
													</button>
													<span id="pmpro_userfield-group-buttons-description-2" class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Move Field Down', 'paid-memberships-pro' ); ?></span>
												</div> <!-- end pmpro_userfield-group-buttons -->
											</li>
											<li class="pmpro_userfield-group-column-label">
												<span class="pmpro_userfield-label">Favorite Food</span>
												<div class="pmpro_userfield-group-options">
													<a class="edit-field" title="<?php esc_html_e( 'Edit field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Edit', 'paid-memberships-pro' ); ?></a> |
													<a class="duplicate-field" title="<?php esc_html_e( 'Duplicate field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Duplicate', 'paid-memberships-pro' ); ?></a> |
													<a class="delete-field" title="<?php esc_html_e( 'Delete field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Delete', 'paid-memberships-pro' ); ?></a>
												</div> <!-- end pmpro_userfield-group-options -->
											</li>
											<li class="pmpro_userfield-group-column-name">cat_food</li>
											<li class="pmpro_userfield-group-column-type">Text</li>
										</ul>

										<div class="pmpro_userfield-field-settings">

											<div class="pmpro_userfield-field-setting">
												<label for="pmpro_userfields_field-label"><?php esc_html_e( 'Label', 'paid-memberships-pro' ); ?></label>
												<input type="text" name="pmpro_userfields-field-label" value="Favorite Food" />
												<span class="description"><?php esc_html_e( 'Brief descriptive text for the field. Shown on user forms.', 'paid-memberships-pro' ); ?></span>
											</div> <!-- end pmpro_userfield-field-setting -->

											<div class="pmpro_userfield-field-setting">
												<label for="pmpro_userfields_field-name"><?php esc_html_e( 'Name', 'paid-memberships-pro' ); ?></label>
												<input type="text" name="pmpro_userfields-field-name" value="cat_food" />
												<span class="description"><?php esc_html_e( 'Single word with no spaces. Underscores are allowed.', 'paid-memberships-pro' ); ?></span>
											</div> <!-- end pmpro_userfield-field-setting -->

											<div class="pmpro_userfield-field-setting">
												<label for="pmpro_userfields_field-name"><?php esc_html_e( 'Type', 'paid-memberships-pro' ); ?></label>
												<select name="pmpro_userfields-field-type" />
													<option value="text"><?php esc_html_e( 'Text', 'paid-memberships-pro' ); ?></option>
													<option value="textarea"><?php esc_html_e( 'Text Area', 'paid-memberships-pro' ); ?></option>
													<option value=""><?php esc_html_e( 'Checkbox and checkbox grouped', 'paid-memberships-pro' ); ?></option>
													<option value=""><?php esc_html_e( 'Radio', 'paid-memberships-pro' ); ?></option>
													<option value=""><?php esc_html_e( 'Select', 'paid-memberships-pro' ); ?></option>
													<option value=""><?php esc_html_e( 'Multi Select - ACF calls select2 "stylized UI"', 'paid-memberships-pro' ); ?></option>
													<option value=""><?php esc_html_e( 'File', 'paid-memberships-pro' ); ?></option>
													<option value=""><?php esc_html_e( 'Number', 'paid-memberships-pro' ); ?></option>
													<option value=""><?php esc_html_e( 'Date', 'paid-memberships-pro' ); ?></option>
													<option value=""><?php esc_html_e( 'Read-Only', 'paid-memberships-pro' ); ?></option>
													<option value=""><?php esc_html_e( 'Hidden', 'paid-memberships-pro' ); ?></option>
												</select>												
											</div> <!-- end pmpro_userfield-field-setting -->

											<div class="pmpro_userfield-field-setting pmpro_userfield-field-setting-dual">
												<div class="pmpro_userfield-field-setting">
													<label for="pmpro_userfields_field-required"><?php esc_html_e( 'Required?', 'paid-memberships-pro' ); ?></label>
													<select name="pmpro_userfields_field-required">
														<option value="no" selected="selected"><?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></option>
														<option value="yes"><?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></option>
													</select>
												</div> <!-- end pmpro_userfield-field-setting -->

												<div class="pmpro_userfield-field-setting">
													<label for="pmpro_userfields_field-readonly"><?php esc_html_e( 'Read Only?', 'paid-memberships-pro' ); ?></label>
												<select name="pmpro_userfields_field-readonly">
														<option value="no" selected="selected"><?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></option>
														<option value="yes"><?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></option>
													</select>
												</div> <!-- end pmpro_userfield-field-setting -->
											</div> <!-- end pmpro_userfield-field-setting-dual -->

											<div class="pmpro_userfield-field-setting">
												<label for="pmpro_userfields_field-membership"><?php esc_html_e( 'Membership Levels', 'paid-memberships-pro' ); ?></label>
												<select name="pmpro_userfields_field-membership">
													<option><?php esc_html_e( '[Inherit Group Setting]', 'paid-memberships-pro' ); ?></option>
												</select>
											</div> <!-- end pmpro_userfield-field-setting -->

											<div class="pmpro_userfield-field-setting">
												<label for="pmpro_userfields_group_profile"><?php esc_html_e( 'Show field on user profile?', 'paid-memberships-pro' ); ?></label>
												<select name="pmpro_userfields_group_profile">
													<option><?php esc_html_e( '[Inherit Group Setting]', 'paid-memberships-pro' ); ?></option>
													<option value="yes"><?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></option>
													<option value="admins"><?php esc_html_e( 'Yes (only admins)', 'paid-memberships-pro' ); ?></option>
													<option value="no"><?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></option>
												</select>
											</div> <!-- end pmpro_userfield-field-setting -->

											<div class="pmpro_userfield-field-setting">
												<label for="pmpro_userfields_field-class"><?php esc_html_e( 'Field Wrapper Class', 'paid-memberships-pro' ); ?></label>
												<input type="text" name="pmpro_userfields-field-class" />
												<span class="description"><?php esc_html_e( 'Assign a custom CSS selector to the field\'s wrapping div', 'paid-memberships-pro' ); ?>.</span>
											</div> <!-- end pmpro_userfield-field-setting -->

											<div class="pmpro_userfield-field-setting">
												<label for="pmpro_userfields_field-divclass"><?php esc_html_e( 'Field Element Class', 'paid-memberships-pro' ); ?></label>
												<input type="text" name="pmpro_userfields-field-divclass" />
												<span class="description"><?php esc_html_e( 'Assign a custom CSS selector to the field', 'paid-memberships-pro' ); ?></span>
											</div> <!-- end pmpro_userfield-field-setting -->

											<div class="pmpro_userfield-field-setting">
												<label for="pmpro_userfields_field-hint"><?php esc_html_e( 'Hint', 'paid-memberships-pro' ); ?></label>
												<textarea name="pmpro_userfields-field-hint" /></textarea>
												<span class="description"><?php esc_html_e( 'Descriptive text for users or admins submitting the field.', 'paid-memberships-pro' ); ?></span>
											</div> <!-- end pmpro_userfield-field-setting -->
										</div> <!-- end pmpro_userfield-field-settings -->
										<div class="pmpro_userfield-field-actions">
											<button name="pmpro_userfields_add_field" class="button button-secondary">
												<?php esc_html_e( 'Close Field', 'paid-memberships-pro' ); ?>
											</button>
										</div> <!-- end pmpro_userfield-field-actions -->
									</div> <!-- end pmpro_userfield-group-field -->

									<div class="pmpro_userfield-group-field pmpro_userfield-group-field-collapse">
										<ul class="pmpro_userfield-group-tbody">
											<li class="pmpro_userfield-group-column-order">4</li>
											<li class="pmpro_userfield-group-column-label">
												<span class="pmpro_userfield-label">Cat's Age</span>
												<div class="pmpro_userfield-group-options">
													<a class="edit-field" title="<?php esc_html_e( 'Edit field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Edit', 'paid-memberships-pro' ); ?></a> |
													<a class="duplicate-field" title="<?php esc_html_e( 'Duplicate field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Duplicate', 'paid-memberships-pro' ); ?></a> |
													<a class="delete-field" title="<?php esc_html_e( 'Delete field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Delete', 'paid-memberships-pro' ); ?></a>
												</div> <!-- end pmpro_userfield-group-options -->
											</li>
											<li class="pmpro_userfield-group-column-name">cat_breed</li>
											<li class="pmpro_userfield-group-column-type">Select</li>
										</ul>
									</div> <!-- end pmpro_userfield-group-field -->

								</div> <!-- end pmpro_userfield-group-fields -->

								<div class="pmpro_userfield-group-actions">
									<button name="pmpro_userfields_add_field" class="button button-secondary button-hero">
										<?php
											/* translators: a plus sign dashicon */
											printf( esc_html__( '%s Add Field', 'paid-memberships-pro' ), '<span class="dashicons dashicons-plus"></span>' ); ?>
									</button>
								</div> <!-- end pmpro_userfield-group-actions -->

							</div> <!-- end pmpro_userfield-inside -->

						</div> <!-- end pmpro_userfield-group -->

						<div class="pmpro_userfield-group pmpro_userfield-group-collapse">
							<div class="pmpro_userfield-group-header">
								<div class="pmpro_userfield-group-buttons">
									<button type="button" aria-describedby="pmpro_userfield-group-buttons-description-1" id="pmpro_userfield-group-buttons-button-move-up" aria-disabled="true" class="pmpro_userfield-group-buttons-button" aria-label="<?php esc_html_e( 'Move up', 'paid-memberships-pro' ); ?>">
										<span class="dashicons dashicons-arrow-up-alt2"></span>
									</button>
									<span id="pmpro_userfield-group-buttons-description-1" class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Move Group Up', 'paid-memberships-pro' ); ?></span>

									<button type="button" aria-describedby="pmpro_userfield-group-buttons-description-2" id="pmpro_userfield-group-buttons-button-move-down" aria-disabled="false" class="pmpro_userfield-group-buttons-button" aria-label="<?php esc_html_e( 'Move down', 'paid-memberships-pro' ); ?>">
										<span class="dashicons dashicons-arrow-down-alt2"></span>
									</button>
									<span id="pmpro_userfield-group-buttons-description-2" class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Move Group Down', 'paid-memberships-pro' ); ?></span>
								</div> <!-- end pmpro_userfield-group-buttons -->
								<h3>About My Dog</h3>
								<button type="button" aria-describedby="pmpro_userfield-group-buttons-description-3" id="pmpro_userfield-group-buttons-button-expand-group" aria-disabled="false" class="pmpro_userfield-group-buttons-button" aria-label="<?php esc_html_e( 'Expand and Edit Group', 'paid-memberships-pro' ); ?>">
										<span class="dashicons dashicons-arrow-right"></span>
									</button>
									<span id="pmpro_userfield-group-buttons-description-3" class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Expand and Edit Group', 'paid-memberships-pro' ); ?></span>
							</div> <!-- end pmpro_userfield-group-header -->

							<div class="pmpro_userfield-inside">
							blah blah blah this is hidden.
							</div> <!-- end pmpro_userfield-inside -->	
						</div>

						<p class="text-center">
							<button name="pmpro_userfields_add_group" class="button button-primary button-hero">
								<?php
									/* translators: a plus sign dashicon */
									printf( esc_html__( '%s Add Group', 'paid-memberships-pro' ), '<span class="dashicons dashicons-plus"></span>' ); ?>
							</button>
						</p>

					</div> <!-- end inside -->
				</div> <!-- end post-body-content -->

				<div id="postbox-container-1" class="postbox-container">
					<?php do_meta_boxes( 'memberships_page_pmpro-userfields', 'side', '' ); ?>
				</div> <!-- end postbox-container-1 -->

			</div> <!-- end post-body -->
		</div> <!-- end poststuff -->
	</form>

<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");
?>
