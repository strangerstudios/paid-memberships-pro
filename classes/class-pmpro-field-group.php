<?php
/**
 * Note: One goal of this class is to abstract all uses of the global $pmpro_field_groups and $pmpro_user_fields arrays.
 *       In the next major release, we will likely remove these globals and store field groups and fields in this class
 *       instead to prevent conflicts with other plugins and themes.
 */
class PMPro_Field_Group {
	/**
	 * The name of the field group.
	 *
	 * We want this to be read only via a magic getter.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The label for the field group.
	 *
	 * @var string
	 */
	public $label;

	/**
	 * The description for the field group.
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Constructor.
	 */
	private function __construct( $name, $label, $description = '' ) {
		$this->name        = $name;
		$this->label       = $label;
		$this->description = $description;
	}

	/**
	 * Magic getter for read-only properties.
	 *
	 * @param string $name The property name.
	 * @return mixed The property value.
	 */
	public function __get( $name ) {
		if ( isset( $this->$name ) ) {
			return $this->$name;
		}

		return null;
	}

	/**
	 * Add a field group.
	 *
	 * @since 3.4
	 *
	 * @param string $name        The name of the field group.
	 * @param string|null $label  The label for the field group. If NULL, a cleaned version of the name will be used.
	 * @param string $description The description for the field group.
	 *
	 * @return PMPro_Field_Group The field group object.
	 */
	public static function add( $name, $label = NULL, $description = '' ) {
		global $pmpro_field_groups;

		// If the field group already exists, update the label and description.
		if ( ! empty( $pmpro_field_groups[ $name ] ) ) { // Looking at global to avoid infinite loop when a group doesn't exist.
			$existing_field_group = self::get( $name );
			$existing_field_group->label       = $label;
			$existing_field_group->description = $description;

			return $existing_field_group;
		}

		// If no label is provided, use the name.
		if ( empty( $label ) ) {
			if ( $name === 'checkout_boxes' ) {
				apply_filters( 'pmpro_default_field_group_label', esc_html__( 'More Information','paid-memberships-pro' ) );
			} else {
				$label = ucwords( str_replace( '_', ' ', $name ) );
			}
		}

		// Create a new field group object.
		$field_group = new PMPro_Field_Group( $name, $label, $description );

		// Add the field group to the global array.
		$pmpro_field_groups[ $name ] = $field_group;

		return $field_group;
	}

	/**
	 * Get all added field groups.
	 *
	 * @since 3.4
	 *
	 * @return array An array of PMPro_Field_Group objects.
	 */
	public static function get_all() {
		global $pmpro_field_groups;

		if ( empty( $pmpro_field_groups ) ) {
			$pmpro_field_groups = array();
		}

		return $pmpro_field_groups;
	}

	/**
	 * Get an added field group by name.
	 *
	 * @since 3.4
	 *
	 * @param string $name The name of the field group.
	 * @return PMPro_Field_Group The field group object.
	 */
	public static function get( $name ) {
		// Get all field groups.
		$field_groups = self::get_all();

		// If we don't yet have the field group, create it.
		if ( empty( $field_groups[ $name ] ) ) {
			return self::add( $name );
		}

		// Return the field group.
		return $field_groups[ $name ];
	}

	/**
	 * Get the field group for a field.
	 *
	 * @since 3.4
	 *
	 * @param PMPro_Field $field The field object.
	 * @return PMPro_Field_Group|null The field group object, or NULL if the field is not in a group.
	 */
	public static function get_group_for_field( $field ) {
		global $pmpro_field_groups;

		if ( empty( $pmpro_field_groups ) ) {
			$pmpro_field_groups = array();
		}

		foreach ( $pmpro_field_groups as $field_group ) {
			$group_fields = $field_group->get_fields();
			foreach( $group_fields as $group_field ) {
				if ( $group_field->name === $field->name ) {
					return $field_group;
				}
			}
		}

		return null;
	}

	/**
	 * Get a field by name.
	 *
	 * @since 3.4
	 *
	 * @param string $name The name of the field.
	 * @return PMPro_Field|null The field object, or NULL if the field is not in a group.
	 */
	public static function get_field( $name ) {
		global $pmpro_user_fields;

		if ( empty( $pmpro_user_fields ) ) {
			$pmpro_user_fields = array();
		}

		foreach ( $pmpro_user_fields as $group_name => $fields ) {
			foreach ( $fields as $field ) {
				if ( $field->name === $name ) {
					return $field;
				}
			}
		}

		return null;
	}

	/**
	 * Add a field to this field group.
	 *
	 * @since 3.4
	 *
	 * @param PMPro_Field $field The field object to add.
	 * @return bool True if the field was added, otherwise false.
	 */
	public function add_field( $field ) {
		global $pmpro_user_fields;
		if ( empty( $pmpro_user_fields ) ) {
			$pmpro_user_fields = array();
		}

		/**
		 * Filter the field to add.
		 * 
		 * @since 2.9.3
		 *             
		 * @param PMProField $field The field being added.
		 * @param string $group_name The name of the group to add the field to.
		 */
		$field = apply_filters( 'pmpro_add_user_field', $field, $this->name );

		// Make sure that we have a valid field.
		if ( empty( $field ) || ! pmpro_is_field( $field ) ) {
			return false;
		}

		// Make sure the group is in the global array of fields.
		if ( empty( $pmpro_user_fields[ $this->name ] ) ) {
			$pmpro_user_fields[ $this->name ] = array();
		}

		// Add the field to the group.
		$pmpro_user_fields[ $this->name ][] = $field;

		return true;
	}

	/**
	 * Get all fields in this field group.
	 *
	 * @since 3.4
	 *
	 * @return array An array of PMPro_Field objects.
	 */
	public function get_fields() {
		global $pmpro_user_fields;
		if ( empty( $pmpro_user_fields ) ) {
			$pmpro_user_fields = array();
		}

		if ( empty( $pmpro_user_fields[ $this->name ] ) ) {
			$pmpro_user_fields[ $this->name ] = array();
		}

		return $pmpro_user_fields[ $this->name ];
	}

	/**
	 * Get all fields to display in a specific context.
	 *
	 * @since 3.4
	 *
	 * @param array $args The arguments for getting the fields.
	 */
	public function get_fields_to_display( $args = array() ) {
		$default_args = array(
			'scope' => 'profile', // The scope of the fields to show. Can be 'profile' or 'checkout'.
			'user_id' => NULL, // The user ID to show the users for. If null, we are showing fields for the current user.
		);
		$args = wp_parse_args( $args, $default_args );

		// Get all fields in this group.
		$fields = $this->get_fields();

		// Get the user ID.
		$user_id = empty( $args['user_id'] ) ? get_current_user_id() : $args['user_id'];

		// Get the checkout level if this is the checkout scope.
		if ( 'checkout' === $args['scope'] ) {
			$checkout_level = pmpro_getLevelAtCheckout();
			if ( empty( $checkout_level->id ) ) {
				// If we don't have a checkout level, we can't show any fields.
				return array();
			}
		}

		// Get a list of the fields that should be displayed.
		$fields_to_display = array();
		foreach ( $fields as $field ) {
			// Validate the field for scope.
			if ( 'checkout' === $args['scope'] ) {
				// At checkout.
				// Check if this field should only be shown in the profile.
				if ( in_array( $field->profile, array( 'only', 'only_admin' ), true ) ) {
					continue;
				}

				// Check if this field is for the level being purchased.
				if ( ! empty( $field->levels ) && ! in_array( (int) $checkout_level->id, $field->levels, true ) ) {
					continue;
				}
			} else {
				// In profile.
				// Check if this field should ever be shown in the profile.
				if ( empty( $field->profile ) ) {
					continue;
				}

				// Check if this field should only be shown to admins.
				if ( ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_membership_manager' ) ) && in_array( $field->profile, array( 'admins', 'admin', 'only_admin' ), true ) ) {
					continue;
				}

				// Check if the user has a level required for this field.
				if ( ! empty( $field->levels ) && ! pmpro_hasMembershipLevel( $field->levels, $user_id ) ) {
					continue;
				}
			}

			// Add the field to the list of fields to display.
			$fields_to_display[] = $field;
		}

		return $fields_to_display;
	}

	/**
	 * Display the field group.
	 *
	 * @since 3.4
	 *
	 * @param array $args The arguments for displaying the fields.
	 */
	public function display( $args = array() ) {
		$default_args = array(
			'markup' => 'card', // The markup to use for the field group. Can be 'card', 'div' or 'table'.
			'scope' => 'profile', // The scope of the fields to show. Can be 'profile' or 'checkout'.
			'show_group_label' => true, // Whether or not to show the field group.
			'prefill_from_request' => false, // Whether or not to prefill the field values from the $_REQUEST array.
			'show_required' => false, // Whether or not to show required fields.
			'user_id' => NULL, // The user ID to show the users for. If null, we are showing fields for the current user.
		);
		$args = wp_parse_args( $args, $default_args );

		// Get the user ID.
		$user_id = empty( $args['user_id'] ) ? get_current_user_id() : $args['user_id'];

		// Get the fields to display.
		$fields_to_display = $this->get_fields_to_display( $args );

		// If we don't have any fields to display, don't display the group.
		if ( empty( $fields_to_display ) ) {
			return;
		}

		// Display the field group.
		if ( empty( $args['show_group_label'] ) ) {
			$group_header = '';
			$group_footer = '';
		} elseif ( $args['markup'] === 'card' ) {
			// Get the "header" for the field group.
			ob_start();
			?>
			<fieldset id="pmpro_form_fieldset-<?php echo esc_attr( sanitize_title( $this->name ) ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset', 'pmpro_form_fieldset-' . sanitize_title( $this->name ) ) ); ?>">
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
						<?php if ( ! empty( $this->label ) ) { ?>
							<legend class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_legend' ) ); ?>">
								<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_heading pmpro_font-large' ) ); ?>"><?php echo wp_kses_post( $this->label ); ?></h2>
							</legend>
						<?php } ?>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
							<?php if ( ! empty( $this->description ) ) { ?>
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields-description' ) ); ?>"><?php echo wp_kses_post( $this->description ); ?></div>
							<?php } ?>
			<?php
			$group_header = ob_get_clean();

			// Get the "footer" for the field group.
			ob_start();
			?>
						</div> <!-- end pmpro_form_fields -->
					</div> <!-- end pmpro_card_content -->
				</div> <!-- end pmpro_card -->
			</fieldset> <!-- end pmpro_form_fieldset -->
			<?php
			$group_footer = ob_get_clean();
		} elseif( $args['markup'] === 'div' ) {
			// Get the "header" for the field group.
			ob_start();
			?>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>
			<fieldset id="pmpro_form_fieldset-<?php echo esc_attr( sanitize_title( $this->name ) ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset', 'pmpro_form_fieldset-' . sanitize_title( $this->name ) ) ); ?>">
				<?php if ( ! empty( $this->label ) ) { ?>
					<legend class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_legend' ) ); ?>">
						<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_heading pmpro_font-large' ) ); ?>"><?php echo wp_kses_post( $this->label ); ?></h2>
					</legend>
				<?php } ?>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
					<?php if ( ! empty( $this->description ) ) { ?>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields-description' ) ); ?>"><?php echo wp_kses_post( $this->description ); ?></div>
					<?php } ?>
			<?php
			$group_header = ob_get_clean();

			// Get the "footer" for the field group.
			ob_start();
			?>
				</div> <!-- end pmpro_form_fields -->
			</fieldset> <!-- end pmpro_form_fieldset -->
			<?php
			$group_footer = ob_get_clean();
		}else {
			// Get the "header" for the field group.
			ob_start();
			?>
			<h2><?php echo wp_kses_post( $this->label ); ?></h2>
			<?php
			if ( ! empty( $box->description ) ) {
				?>
				<p><?php echo wp_kses_post( $this->description ); ?></p>
				<?php
			}
			?>
			<table class="form-table">
			<?php
			$group_header = ob_get_clean();

			// Get the "footer" for the field group.
			ob_start();
			?>
			</table>
			<?php
			$group_footer = ob_get_clean();
		}

		// Output the group header.
		echo wp_kses_post( $group_header );

		// Display the fields.
		foreach ( $fields_to_display as $field ) {
			// Get the value for this field.
			$value = '';
			if( ! empty( $args['prefill_from_request'] ) && null !== $field->get_value_from_request() ) {
				$value = $field->get_value_from_request();
			} elseif ( ! empty( $user_id ) && metadata_exists( 'user', $user_id, $field->meta_key ) ) {
				$value = get_user_meta( $user_id, $field->meta_key, true );
			} elseif ( ! empty( $user_id ) ) {
				$userdata = get_userdata( $user_id );
				if ( ! empty( $userdata->{$field->name} ) ) {
					$value = $userdata->{$field->name};
				} elseif(isset($field->value)) {
					$value = $field->value;
				}
			} elseif(isset($field->value)) {
				$value = $field->value;
			}

			if ( $args['markup'] === 'div' || $args['markup'] === 'card' ) {
				// Fix divclass.
				if ( ! empty( $field->divclass ) ) {
					$field->divclass .= " ";
				}

				// Add a class to the field based on the type.
				$field->divclass .= "pmpro_form_field pmpro_form_field-" . $field->type;
				$field->class .= " pmpro_form_input-" . $field->type;

				// Add a class to the field based on the id.
				$field->divclass .= " pmpro_form_field-" . $field->id;
				$field->class .= " pmpro_form_input-" . $field->id;

				// Add the required class to field.
				if ( ! empty( $args['show_required'] ) && ! empty( $field->required ) ) {
					$field->divclass .= " pmpro_form_field-required";
					$field->class .= " pmpro_form_input-required";
				}

				// Add the class to not show a field is required if set.
				if ( ! empty( $args['show_required'] ) && ( empty( $field->showrequired ) || is_string( $field->showrequired ) ) ) {
					$field->divclass .= " pmpro_form_field-hide-required";
				}

				// Run the class through the filter.
				$field->divclass = pmpro_get_element_class( $field->divclass, $field->id );
				$field->class = pmpro_get_element_class( $field->class, $field->id );

				?>
				<div id="<?php echo esc_attr( $field->id );?>_div" <?php if ( ! empty( $field->divclass ) ) { echo 'class="' . esc_attr( $field->divclass ) . '"'; } ?>>
					<?php if(!empty($field->showmainlabel)) { ?>
						<label class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>" for="<?php echo esc_attr( $field->name );?>">
							<?php echo wp_kses_post( $field->label );?>
							<?php 
								if ( ! empty( $field->required ) && ! empty( $field->showrequired ) && $field->showrequired === 'label' ) {
								?><span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_asterisk' ) ); ?>"> <abbr title="<?php esc_attr_e( 'Required Field' ,'paid-memberships-pro' ); ?>">*</abbr></span><?php
								}
							?>
						</label>
						<?php $field->display( $value ); ?>
					<?php } else { ?>
						<?php $field->display( $value ); ?>
					<?php } ?>

					<?php if(!empty($field->hint)) { ?>
						<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_hint' ) ); ?>"><?php echo wp_kses_post( $field->hint );?></p>
					<?php } ?>
				</div>
				<?php
			} else {
				?>
				<tr id="<?php echo esc_attr( $field->id );?>_tr">
					<th>
						<?php if ( ! empty( $field->showmainlabel ) ) { ?>
							<label for="<?php echo esc_attr($field->name);?>"><?php echo wp_kses_post( $field->label );?></label>
						<?php } ?>
					</th>
					<td>
						<?php
							if(current_user_can("edit_user", $user_id))
								$field->display($value);
							else
								echo "<div>" . wp_kses_post( $field->displayValue($value) ) . "</div>";
						?>
						<?php if(!empty($field->hint)) { ?>
							<p class="description"><?php echo wp_kses_post( $field->hint );?></p>
						<?php } ?>
					</td>
				</tr>
				<?php
			}
		}

		// Output the group footer.
		echo wp_kses_post( $group_footer );
	}

	/**
	 * Save fields in a specific context.
	 *
	 * @since 3.4
	 *
	 * @param array $args The arguments for saving the fields.
	 * @return bool True if the fields were saved, otherwise false.
	 */
	public function save_fields( $args = array() ) {
		$default_args = array(
			'scope' => 'profile', // The scope of the fields to save. Can be 'profile' or 'checkout'.
			'user_id' => NULL, // The user ID to save the users for. If null, we are saving fields for the current user.
		);
		$args = wp_parse_args( $args, $default_args );

		// Get the user ID if needed.
		$user_id = empty( $args['user_id'] ) ? get_current_user_id() : $args['user_id'];

		// Make sure the current user can edit this user.
		if ( 'scope' == 'profile' && ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		// Get the fields to display.
		$fields_to_display = $this->get_fields_to_display( $args );

		// Save the fields.
		foreach ( $fields_to_display as $field ) {
			$field->save_field_for_user( $user_id );
		}

		return true;
	}
}