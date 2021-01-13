<?php
	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_userfields")))
	{
		die(__("You do not have permissions to perform this action.", 'paid-memberships-pro' ));
	}

	require_once(dirname(__FILE__) . "/admin_header.php");

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
							<div class="pmpro_userfield-group-inside">
								blah blah blah this is hidden.
							</div> <!-- end pmpro_userfield-group-inside -->	
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
							<div class="pmpro_userfield-group-inside">
								<div class="pmpro_userfield-group-settings">
									<ul>
										<li>
											<label for="pmpro_userfields_group_checkout"><?php esc_html_e( 'Show group at checkout?', 'paid-memberships-pro' ); ?></label>
											<select name="pmpro_userfields_group_checkout">
												<option value="yes"><?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></option>
												<option value="no"><?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></option>
											</select>
										</li>
										<li>
											<label for="pmpro_userfields_group_profile"><?php esc_html_e( 'Show group on user profile?', 'paid-memberships-pro' ); ?></label>
											<select name="pmpro_userfields_group_profile">
												<option value="yes"><?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></option>
												<option value="admins"><?php esc_html_e( 'Yes (only admins)', 'paid-memberships-pro' ); ?></option>
												<option value="no"><?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></option>
											</select>
										</li>
									</ul>
									<ul>
										<li>
											<label for="pmpro_userfields_group_membership"><?php esc_html_e( 'Restrict Group for Membership Levels', 'paid-memberships-pro' ); ?></label>
											<select multiple="multiple" name="pmpro_userfields_group_membership">
												<option>Beginner</option>
												<option>Enhanced</option>
												<option>Supporter</option>
												<option>Platinum</option>
												<option>Ultimate</option>
												<option>World Domination</option>
											</select>
										</li>
									</ul>
								</div> <!-- end pmpro_userfield-group-settings -->
								
								<div class="pmpro_userfield-group-field">
									<div class="pmpro_userfield-group-field-header">
										<button type="button" class="pmpro_userfield-action pmpro_userfield-hide"><span class="screen-reader-text"><?php esc_html_e( 'Close Field', 'paid-memberships-pro' ); ?></span></button>
										<div class="pmpro_userfield-group-field-header-field">
											<label for="pmpro_userfields_field_name"><?php esc_html_e( 'Field Name', 'paid-memberships-pro' ); ?></label>
											<input type="text" name="pmpro_userfields_field_name" placeholder="Group Name" value="Cat Name" />
										</div>
										<button type="button" class="pmpro_userfield-action pmpro_userfield-move"><span class="screen-reader-text"><?php esc_html_e( 'Move Field', 'paid-memberships-pro' ); ?></span></button>
									</div> <!-- end pmpro_userfield-group-header -->
									<div class="pmpro_userfield-group-field-settings">
										<ul>
											<li></li>
										</ul>
									</div> <!-- pmpro_userfield-group-field-settings -->
								</div>

								<p class="text-center">
									<button name="pmpro_userfields_add_field" class="button button">
										<?php
											/* translators: a plus sign dashicon */
											printf( esc_html__( '%s Add Field', 'paid-memberships-pro' ), '<span class="dashicons dashicons-plus"></span>' ); ?>
									</button>
								</p>
							</div> <!-- end pmpro_userfield-group-inside -->
						</div> <!-- end pmpro_userfield-group -->

						<div class="pmpro_userfield-group pmpro_userfield-group-collapse">
							<div class="pmpro_userfield-group-header">
								<?php /* this is just a sample view of a collapsed group */ ?> 
								<button type="button" class="pmpro_userfield-action pmpro_userfield-show"><span class="screen-reader-text"><?php esc_html_e( 'Edit Group', 'paid-memberships-pro' ); ?></span></button>
								<h3>About My Dog</h3>
								<button type="button" class="pmpro_userfield-action pmpro_userfield-move"><span class="screen-reader-text"><?php esc_html_e( 'Move Group', 'paid-memberships-pro' ); ?></span></button>
							</div> <!-- end pmpro_userfield-group-header -->
							<div class="pmpro_userfield-group-inside">
							blah blah blah this is hidden.
							</div> <!-- end pmpro_userfield-group-inside -->	
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
