<?php
	//only admins can get this
	if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_userfields' ) ) ) {
		die( __( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
	}

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
								<button type="button" class="pmpro_userfield-action pmpro_userfield-show"><span class="screen-reader-text"><?php esc_html_e( 'Edit Group', 'paid-memberships-pro' ); ?></span></button>
								<h3>More Information</h3>
								<button type="button" class="pmpro_userfield-action pmpro_userfield-pinned"><span class="screen-reader-text"><?php esc_html_e( 'Move Group', 'paid-memberships-pro' ); ?></span></button>
							</div> <!-- end pmpro_userfield-group-header -->
							<div class="pmpro_userfield-inside">
								blah blah blah this is hidden.
							</div> <!-- end pmpro_userfield-inside -->	
						</div>

						<div class="pmpro_userfield-group">
							<div class="pmpro_userfield-group-header">
								<button type="button" class="pmpro_userfield-action pmpro_userfield-hide"><span class="screen-reader-text"><?php esc_html_e( 'Close Group', 'paid-memberships-pro' ); ?></span></button>
								<h3>
									<label for="pmpro_userfields_group_name"><?php esc_html_e( 'Group Name', 'paid-memberships-pro' ); ?></label>
									<input type="text" name="pmpro_userfields_group_name" placeholder="Group Name" value="About My Cat" />
								</h3>
								<button type="button" class="pmpro_userfield-action pmpro_userfield-move"><span class="screen-reader-text"><?php esc_html_e( 'Move Group', 'paid-memberships-pro' ); ?></span></button>
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
										<select multiple="multiple" name="pmpro_userfields_group_membership">
											<option>Beginner</option>
											<option>Enhanced</option>
											<option>Supporter</option>
											<option>Platinum</option>
											<option>Ultimate</option>
											<option>World Domination</option>
										</select>
									</div> <!-- end pmpro_userfield-field-setting -->
								
								</div> <!-- end pmpro_userfield-field-settings -->
								
								<h3>Manage Fields in This Group</h3>

								<ul class="pmpro_userfield-group-thead">
									<li class="pmpro_userfield-group-column-order"><?php esc_html_e( 'Order', 'paid-memberships-pro'); ?></li>
									<li class="pmpro_userfield-group-column-label"><?php esc_html_e( 'Label', 'paid-memberships-pro'); ?></li>
									<li class="pmpro_userfield-group-column-name"><?php esc_html_e( 'Name', 'paid-memberships-pro'); ?></li>
									<li class="pmpro_userfield-group-column-type"><?php esc_html_e( 'Type', 'paid-memberships-pro'); ?></li>
									<li class="pmpro_userfield-group-column-options"></li>
								</ul>
								
								<div class="pmpro_userfield-group-fields">

									<div class="pmpro_userfield-group-field pmpro_userfield-group-field-collapse">
										<ul class="pmpro_userfield-group-tbody">
											<li class="pmpro_userfield-group-column-order">1</li>
											<li class="pmpro_userfield-group-column-label">Cat's Name</li>
											<li class="pmpro_userfield-group-column-name">cat_name</li>
											<li class="pmpro_userfield-group-column-type">Text</li>
											<li class="pmpro_userfield-group-column-options">
												<a class="edit-field" title="<?php esc_html_e( 'Edit field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Edit', 'paid-memberships-pro' ); ?></a>
												<a class="duplicate-field" title="<?php esc_html_e( 'Duplicate field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Duplicate', 'paid-memberships-pro' ); ?></a>
												<a class="delete-field" title="<?php esc_html_e( 'Delete field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Delete', 'paid-memberships-pro' ); ?></a>
											</li>
										</ul>
									</div> <!-- end pmpro_userfield-group-field -->

									<div class="pmpro_userfield-group-field pmpro_userfield-group-field-collapse">
										<ul class="pmpro_userfield-group-tbody">
											<li class="pmpro_userfield-group-column-order">2</li>
											<li class="pmpro_userfield-group-column-label">Cat's Breed</li>
											<li class="pmpro_userfield-group-column-name">cat_breed</li>
											<li class="pmpro_userfield-group-column-type">Select</li>
											<li class="pmpro_userfield-group-column-options">
												<a class="edit-field" title="<?php esc_html_e( 'Edit field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Edit', 'paid-memberships-pro' ); ?></a>
												<a class="duplicate-field" title="<?php esc_html_e( 'Duplicate field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Duplicate', 'paid-memberships-pro' ); ?></a>
												<a class="delete-field" title="<?php esc_html_e( 'Delete field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Delete', 'paid-memberships-pro' ); ?></a>
											</li>
										</ul>
									</div> <!-- end pmpro_userfield-group-field -->

									<div class="pmpro_userfield-group-field pmpro_userfield-group-field-collapse">
										<ul class="pmpro_userfield-group-tbody">
											<li class="pmpro_userfield-group-column-order">3</li>
											<li class="pmpro_userfield-group-column-label">Cat's Sex</li>
											<li class="pmpro_userfield-group-column-name">cat_sex</li>
											<li class="pmpro_userfield-group-column-type">Radio</li>
											<li class="pmpro_userfield-group-column-options">
												<a class="edit-field" title="<?php esc_html_e( 'Edit field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Edit', 'paid-memberships-pro' ); ?></a>
												<a class="duplicate-field" title="<?php esc_html_e( 'Duplicate field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Duplicate', 'paid-memberships-pro' ); ?></a>
												<a class="delete-field" title="<?php esc_html_e( 'Delete field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Delete', 'paid-memberships-pro' ); ?></a>
											</li>
										</ul>
									</div> <!-- end pmpro_userfield-group-field -->

									<div class="pmpro_userfield-group-field pmpro_userfield-group-field-expand">
										<ul class="pmpro_userfield-group-tbody">
											<li class="pmpro_userfield-group-column-order">4</li>
											<li class="pmpro_userfield-group-column-label">Favorite Food</li>
											<li class="pmpro_userfield-group-column-name">cat_food</li>
											<li class="pmpro_userfield-group-column-type">Text</li>
											<li class="pmpro_userfield-group-column-options">
												<a class="edit-field" title="<?php esc_html_e( 'Edit field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Edit', 'paid-memberships-pro' ); ?></a>
												<a class="duplicate-field" title="<?php esc_html_e( 'Duplicate field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Duplicate', 'paid-memberships-pro' ); ?></a>
												<a class="delete-field" title="<?php esc_html_e( 'Delete field', 'paid-memberships-pro' ); ?>" href="#"><?php esc_html_e( 'Delete', 'paid-memberships-pro' ); ?></a>
											</li>
										</ul>

										<div class="pmpro_userfield-field-settings">

											<div class="pmpro_userfield-field-setting">
												<label for="pmpro_userfields_field-label"><?php esc_html_e( 'Label', 'paid-memberships-pro' ); ?></label>
												<input type="text" name="pmpro_userfields-field-label" value="Favorite Food" />
												<span class="description"><?php esc_html_e( 'Brief descriptive text for the field, shown on user forms.', 'paid-memberships-pro' ); ?></span>
											</div> <!-- end pmpro_userfield-field-setting -->

											<div class="pmpro_userfield-field-setting">
												<label for="pmpro_userfields_field-name"><?php esc_html_e( 'Name', 'paid-memberships-pro' ); ?></label>
												<input type="text" name="pmpro_userfields-field-name" value="cat_food" />
												<span class="description"><?php esc_html_e( 'Single word, no spaces. Underscores and dashes allowed', 'paid-memberships-pro' ); ?></span>
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

											<div class="pmpro_userfield-field-setting pmpro_userfield-field-setting-radio">
												<label for="pmpro_userfields_field-required"><?php esc_html_e( 'Required?', 'paid-memberships-pro' ); ?></label>
												<span><input name="pmpro_userfields_field-required" type="radio" value="1" /> <?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></span>
												<span><input name="pmpro_userfields_field-required" type="radio" value="0" /> <?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></span>
											</div> <!-- end pmpro_userfield-field-setting -->

											<div class="pmpro_userfield-field-setting pmpro_userfield-field-setting-radio">
												<label for="pmpro_userfields_field-readonly"><?php esc_html_e( 'Read Only?', 'paid-memberships-pro' ); ?></label>
												<span><input name="pmpro_userfields_field-readonly" type="radio" value="1" /> <?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></span>
												<span><input name="pmpro_userfields_field-readonly" type="radio" value="0" /> <?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></span>
											</div> <!-- end pmpro_userfield-field-setting -->

											<div class="pmpro_userfield-field-setting pmpro_userfield-field-setting-radio">
												<label for="pmpro_userfields_field-conditional"><?php esc_html_e( 'Conditional Logic', 'paid-memberships-pro' ); ?></label>
												<span><input name="pmpro_userfields_field-conditional" type="radio" value="1" /> <?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></span>
												<span><input name="pmpro_userfields_field-conditional" type="radio" value="0" /> <?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></span>
											</div> <!-- end pmpro_userfield-field-setting -->

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
												<input type="text" name="pmpro_userfields-field-hint" />
												<span class="description"><?php esc_html_e( 'Descriptive text for users or admins submitting the field.', 'paid-memberships-pro' ); ?></span>
											</div> <!-- end pmpro_userfield-field-setting -->
										</div> <!-- end pmpro_userfield-field-settings -->
										<p class="text-center">
											<button name="pmpro_userfields_add_field" class="button button-secondary">
												<?php esc_html_e( 'Close Field', 'paid-memberships-pro' ); ?>
											</button>
										</p>
									</div> <!-- end pmpro_userfield-group-field -->

								</div> <!-- end pmpro_userfield-group-fields -->

								<p class="text-center">
									<button name="pmpro_userfields_add_field" class="button button-secondary button-hero">
										<?php
											/* translators: a plus sign dashicon */
											printf( esc_html__( '%s Add Field', 'paid-memberships-pro' ), '<span class="dashicons dashicons-plus"></span>' ); ?>
									</button>
								</p>

							</div> <!-- end pmpro_userfield-inside -->

						</div> <!-- end pmpro_userfield-group -->

						<div class="pmpro_userfield-group pmpro_userfield-group-collapse">
							<div class="pmpro_userfield-group-header">
								<?php /* this is just a sample view of a collapsed group */ ?> 
								<button type="button" class="pmpro_userfield-action pmpro_userfield-show"><span class="screen-reader-text"><?php esc_html_e( 'Edit Group', 'paid-memberships-pro' ); ?></span></button>
								<h3>About My Dog</h3>
								<button type="button" class="pmpro_userfield-action pmpro_userfield-move"><span class="screen-reader-text"><?php esc_html_e( 'Move Group', 'paid-memberships-pro' ); ?></span></button>
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
