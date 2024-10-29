<?php
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
	public function __construct( $name, $label, $description = '' ) {
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
	 * @since TBD
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
				apply_filters( 'pmpro_default_field_group_label', __( 'More Information','paid-memberships-pro' ) );
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
	 * @since TBD
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
	 * @since TBD
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
	 * Add a field to this field group.
	 *
	 * @since TBD
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
	 * @since TBD
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
}