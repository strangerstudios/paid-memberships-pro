<?php
#[AllowDynamicProperties]
class PMPro_Field {
	/**
	 * The name of the field.
	 *
	 * This is the name attribute of the input field and may be automatically prefixed if needed.
	 *
	 * @since 2.9
	 *
	 * @var string
	 */
	private $name = '';

	/**
	 * The type of field that this is.
	 *
	 * @since 2.9
	 *
	 * @var string
	 */
	private $type = '';

	/**
	 * The meta key for this field.
	 *
	 * Will be set to $name without any prefixes that are added.
	 *
	 * @since 2.9
	 *
	 * @var string
	 */
	private $meta_key = '';

	/**
	 * The label of the field.
	 *
	 * This is the human-readable label displayed to the user.
	 *
	 * @since 2.9
	 *
	 * @var string
	 */
	private $label = '';

	/**
	 * Whether the label should be shown.
	 *
	 * @since 2.9
	 *
	 * @var bool
	 */
	private $showmainlabel = true;

	/**
	 * A hint to be displayed with the field.
	 *
	 * @since 2.9
	 *
	 * @var string
	 */
	private $hint = '';

	/**
	 * The membership levels that this field should be displayed for.
	 *
	 * @since 2.9
	 *
	 * @var array
	 */
	private $levels = array();

	/**
	 * Whether the field is required.
	 *
	 * @since 2.9
	 *
	 * @var bool
	 */
	private $required = false;

	/**
	 * Whether the field should be shown as required if $required is set to true.
	 *
	 * @since 2.9
	 *
	 * @var bool
	 */
	private $showrequired = true;

	/**
	 * Where this field should be shown.
	 *
	 * Options are true, false, 'admin', 'only', and 'only_admin'.
	 *
	 * @since 2.9
	 *
	 * @var mixed
	 */
	private $profile = true;

	/**
	 * Whether the field is readonly.
	 *
	 * @since 2.9
	 *
	 * @var bool
	 */
	private $readonly = false;

	/**
	 * Array to define conditions when a field should be shown or hidden.
	 *
	 * @since 2.9
	 *
	 * @var array
	 */
	private $depends = array();

	/**
	 * Flag to determine if depends conditions should be ANDed or ORed together.
	 *
	 * @since 2.9.1
	 *
	 * @var bool
	 */
	private $depends_or = false;

	/**
	 * Whether the field value should be sanitized before saving.
	 *
	 * @since 2.9
	 *
	 * @var bool
	 */
	private $sanitize = true;

	/**
	 * The ID to show for the field.
	 *
	 * @since 2.9
	 *
	 * @var string
	 */
	private $id = '';

	/**
	 * Class for the input field.
	 *
	 * @since 2.9
	 *
	 * @var string
	 */
	private $class = '';

	/**
	 * Class for the div wrapper for the input field.
	 *
	 * @since 2.9
	 *
	 * @var string
	 */
	private $divclass = '';

	/**
	 * Whether this field should be included in a members list CSV export.
	 *
	 * @since 2.9
	 *
	 * @var bool
	 */
	private $memberslistcsv = false;

	/**
	 * The save function that should be used for this field.
	 *
	 * null defaults to the default save function.
	 *
	 * @since 2.9
	 *
	 * @var callable
	 */
	private $save_function = null;

	/**
	 * Whether this field should be shown when adding a member using
	 * the PMPro Add Member Add On.
	 *
	 * @since 2.9
	 *
	 * @var bool
	 */
	private $addmember = false;

	/**
	 * The size attribute when using a text input field type.
	 *
	 * @since 2.9
	 *
	 * @var int
	 */
	private $size = 30;

	/**
	 * The number of rows to show when using a textarea field type.
	 *
	 * @since 2.9
	 *
	 * @var int
	 */
	private $rows = 5;

	/**
	 * The number of columns to show when using a textarea field type.
	 *
	 * @since 2.9
	 *
	 * @var int
	 */
	private $cols = 80;

	/**
	 * The options for a select, select2, multiselect, checkbox_grouped, or radio field type.
	 *
	 * @since 2.9
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Whether multiple options should be selectable when using a select, select2, or multiselect field type.
	 *
	 * @since 2.9
	 *
	 * @var bool
	 */
	private $multiple = false;

	/**
	 * The text to show next to a checkbox when using a checkbox field type.
	 *
	 * @since 2.9
	 *
	 * @var string
	 */
	private $text = '';

	/**
	 * The HTML to show for an HTML field type.
	 *
	 * @since 2.9
	 *
	 * @var string
	 */
	private $html = '';

	/**
	 * The default value for a field.
	 *
	 * @since 3.2
	 *
	 * @var string
	 */
	private $default = '';

	/**
	 * File upload types.
	 * 
	 * @since 3.2
	 * 
	 * @var string
	 *
	 */
	private $allowed_file_types = '';

	/**
	 * File upload limit
	 * 
	 * @since 3.2
	 * 
	 * @var int
	 */
	private $max_file_size = '';

	function __construct($name = NULL, $type = NULL, $attr = NULL) {
		if ( ! empty( $name ) )
			return $this->set( $name, $type, $attr );
		else
			return true;
	}

	/**
	 * Magic getter to allow reading private class properties.
	 *
	 * @param string $name The property name.
	 * @return mixed The property value.
	 */
	function __get( $name ) {
		if ( isset( $this->$name ) ) {
			if ( ! $this->is_valid_property( $name ) ) {
				_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'The property %s is not valid for the field type %s.', 'paid-memberships-pro' ), esc_html( $name ), esc_html( $this->type ) ), '3.4' );
			}
			return $this->$name;
		} else {
			_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'The property %s does not exist.', 'paid-memberships-pro' ), esc_html( $name ) ), '3.4' );
		}

		return null;
	}

	/**
	 * Magic setter to allow setting private class properties and
	 * throwing warnings when we want to phase out a property.
	 *
	 * @param string $name The property name.
	 * @param mixed $value The property value.
	 */
	function __set( $name, $value ) {
		if ( 'type' === $name ) {
			_doing_it_wrong( __FUNCTION__, esc_html__( 'PMPro_Field properties should not be modified directly and may break in a future version. Instead, create a new PMPro_Field object.', 'paid-memberships-pro' ), '3.4' );
		}

		$this->$name = $value;
	}
	
	/**
	 * Magic isset to check if a private class property is set.
	 *
	 * @param string $name The property name.
	 * @return bool Whether the property is set.
	 */
	function __isset( $name ) {
		return isset( $this->$name );
	}

	/**
	 * Magic __call to allow calling private class methods and throwing warnings
	 * when we want to phase out a method.
	 */
	function __call( $name, $arguments ) {
		switch( $name ) {
			case 'set':
				_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'The method %s of PMPro_Field has become private and will not be available in a future version. Instead, use the $args property of the constructor when creating a new PMPro_Field object.', 'paid-memberships-pro' ), esc_html( $name ) ), '3.4' );
				break;
			case 'saveUsersTable':
			case 'saveTermRelationshipsTable':
			case 'saveFile':
				_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'The method %s of PMPro_Field has become private and will not be available in a future version. Instead, use the save_field_for_user method of the PMPro_Field object.', 'paid-memberships-pro' ), esc_html( $name ) ), '3.4' );
				break;
			case 'getHTML':
			case 'getDependenciesJS':
				_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'The method %s of PMPro_Field has become private and will not be available in a future version. Instead, use the display() method of the PMPro_Field object.', 'paid-memberships-pro' ), esc_html( $name ) ), '3.4' );
				break;
			default:
				_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'The method %s of PMPro_Field has become private and will not be available in a future version.', 'paid-memberships-pro' ), esc_html( $name ) ), '3.4' );
				break;
		}
		return call_user_func( array( $this, $name ), $arguments );
	}

	/**
	 * Check if a property should be present for the current field type.
	 *
	 * @since 3.4
	 *
	 * @param string $property The property to check.
	 * return bool Whether the property is valid for the field type.
	 */
	private function is_valid_property( $property ) {
		switch ( $property ) {
			case 'name':
			case 'type':
			case 'meta_key':
			case 'label':
			case 'showmainlabel':
			case 'hint':
			case 'levels':
			case 'required':
			case 'showrequired':
			case 'profile':
			case 'readonly':
			case 'depends':
			case 'depends_or':
			case 'sanitize':
			case 'id':
			case 'class':
			case 'divclass':
			case 'memberslistcsv':
			case 'save_function':
			case 'addmember':
			case 'default':
				return true;
				break;
			case 'size':
				return in_array( $this->type, array( 'text', 'number' ) );
				break;
			case 'rows':
			case 'cols':
				return 'textarea' === $this->type;
				break;
			case 'options':
				return in_array( $this->type, array( 'select', 'multiselect', 'select2', 'radio', 'checkbox_grouped' ) );
				break;
			case 'multiple':
				return in_array( $this->type, array( 'select', 'select2', 'multiselect' ) );
				break;
			case 'text':
				return 'checkbox' === $this->type;
				break;
			case 'html':
				return 'html' === $this->type;
				break;
			case 'allowed_file_types':
			case 'max_file_size':
				return 'file' === $this->type;
				break;
			default:
				return false;
				break;
		}
	}

	/*
		setup field based on passed values
		attr is array of one or more of the following:
		- size = int (size attribute for text fieldS)
		- required = bool (require this field at registration?)
		- options = array of strings (e.g. array("value"=>"option name", "value2" = "option 2 name"))
		- profile = mixed (show field in profile page? true for both, "admins" for admins only)
		- just_profile = bool (not required. true means only show field in profile)
		- class = string (class to add to html element)
	*/
	private function set($name, $type, $attr = array())
	{
		$this->name = $name;
		$this->type = $type;

		//set meta key
		$this->meta_key = $this->name;

		//add attributes as properties of this class
		if(!empty($attr))
		{
			foreach($attr as $key=>$value)
			{
				$this->$key = $value;
			}
		}

		//make sure levels is an array
		if ( ! empty( $this->levels ) && ! is_array( $this->levels ) ) {
			$this->levels = array( $this->levels );
		}

		//make sure we have an id
		if(empty($this->id))
			$this->id = $this->name;

		//fix class
		if(empty($this->class))
			$this->class = "pmpro_form_input";
		else
			$this->class .= " pmpro_form_input";

		//default label
		if(isset($this->label) && $this->label === false)
			$this->label = false;	//still false
		elseif(empty($this->label))
			$this->label = ucwords($this->name);

		if(!isset($this->showmainlabel))
			$this->showmainlabel = true;

		//add prefix to the name if equal to a query var
		$public_query_vars = array('m', 'p', 'posts', 'w', 'cat', 'withcomments', 'withoutcomments', 's', 'search', 'exact', 'sentence', 'calendar', 'page', 'paged', 'more', 'tb', 'pb', 'author', 'order', 'orderby', 'year', 'monthnum', 'day', 'hour', 'minute', 'second', 'name', 'category_name', 'tag', 'feed', 'author_name', 'static', 'pagename', 'page_id', 'error', 'attachment', 'attachment_id', 'subpost', 'subpost_id', 'preview', 'robots', 'taxonomy', 'term', 'cpage', 'post_type', 'title', 'embed' );
		if(in_array($this->name, $public_query_vars))
			$this->name = "pmprorhprefix_" . $this->name;

		/**
		 * Legacy filter to define what field keys are saved to the wp_users table. 
		 *
		 * @deprecated 3.1
		 */
		$user_table_fields = apply_filters_deprecated( 'pmprorh_user_table_fields', array( array( 'user_url' ) ), '3.1', 'pmpro_user_table_fields' );

		/**
		 * Filter to define what field keys are saved to the wp_users table.
		 *
		 * @since 3.1
		 *
		 * @param array $user_table_fields Array of field keys saved to the wp_users table.
		 * @return array $user_table_fields Array of field keys saved to the wp_users table.
		 */
		$user_table_fields = apply_filters( 'pmpro_user_table_fields', $user_table_fields );

		// Save wp_users table fields to the WP_User, not usermeta.
		if ( in_array( $this->name, $user_table_fields ) ) {
			//use the save date function
			$this->save_function = array( $this, 'saveUsersTable' );
		}

		// Save user taxonomy fields to wp_term_relationships, not usermeta.
		if ( ! empty( $this->taxonomy ) ) {
			// Use the save taxonomy function.
			$this->save_function = array( $this, 'saveTermRelationshipsTable' );

			// Populate terms from the taxonomy if options are empty.
			if ( empty( $this->options ) ) {
				$terms = get_terms( $this->taxonomy, array( 'hide_empty' => false ) );
				if ( isset( $terms->errors ) ) {
					$this->options = array();
				} else {
					$terms_options = array();
					foreach ( $terms as $term ) {
						$terms_options[ $term->term_id ] = $term->name;
					}
					$this->options = $terms_options;
				}
			}
		}

		//default fields
		if($this->type == "text")
		{
			if(empty($this->size))
				$this->size = 30;
		}
		elseif($this->type == "number")
		{
			if(empty($this->size))
				$this->size = 5;
		}
		elseif($this->type == "select" || $type == "multiselect" || $type == "select2" || $type == "radio")
		{
			//default option
			if ( empty( $this->options ) )
				$this->options = array( '', esc_html__( '- choose one -', 'paid-memberships-pro' ) );

			/**
			 * Legacy filter to repair non-associative options.
			 *
			 * @deprecated 3.1
			 */
			$repair_non_associative_options = apply_filters_deprecated( 'pmprorh_repair_non_associative_options', array( true ), '3.1', 'pmpro_field_repair_non_associative_options' );

			/**
			 * Filter to repair non-associative options.
			 *
			 * @since 3.1
			 *
			 * @param bool $repair_non_associative_options Whether to repair non-associative options.
			 * @return bool $repair_non_associative_options Whether to repair non-associative options.
			 */
			$repair_non_associative_options = apply_filters( 'pmpro_field_repair_non_associative_options', $repair_non_associative_options );

			// Is a non associative array is passed? Set values to labels.
			if($repair_non_associative_options && !$this->is_assoc($this->options))
			{
				$newoptions = array();
				foreach($this->options as $option)
					$newoptions[$option] = $option;
				$this->options = $newoptions;
			}
		}
		elseif($this->type == "textarea")
		{
			if(empty($this->rows))
				$this->rows = 5;
			if(empty($this->cols))
				$this->cols = 80;
		}
		elseif($this->type == "file")
		{
			// Default to file preview if image type.
			if ( ! isset( $this->preview ) ) {
				$this->preview = true;
			}
			// Default to allow file delete if full source known.
			if ( ! isset( $this->allow_delete ) ) {
				$this->allow_delete = 'only_admin';
			}
			//use the file save function
			$this->save_function = array($this, "saveFile");
		}
		elseif($this->type == "checkbox")
		{
			if ( ! isset( $this->text ) || $this->text === '' )
			{
				$this->text = $this->label;
				$this->showmainlabel = false;
			}
		}
		elseif ( $this->type == 'hidden' ) {
			// Don't show the label for the hidden field.
			$this->showmainlabel = false;
		}

		return true;
	}

	/**
	 * Get the field value from $_REQEUST or $_SESSION.
	 * The value will be sanitized if the field has the sanitize property set to true.
	 *
	 * @since 3.4
	 *
	 * @return mixed The value of the field or null if not found.
	 */
	function get_value_from_request() {
		if ( isset( $_REQUEST[ $this->name ] ) ) {
			$value = $_REQUEST[$this->name]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		} elseif ( isset( $_REQUEST[ $this->name . '_checkbox' ] ) && $this->type == 'checkbox' ) {
			// Empty checkbox.
			$value = 0;
		} elseif ( ! empty( $_REQUEST[ $this->name . '_checkbox' ] ) && in_array( $this->type, array( 'checkbox_grouped', 'select2' ) ) )	{
			// Empty group checkboxes or select2.
			$value = array();
		} elseif ( isset( $_FILES[$this->name] ) && $this->type == 'file' ) {
			// File field.
			$value = $_FILES[$this->name]['name']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}  elseif ( isset( $_SESSION[$this->name] ) ) {
			// Value stored in session.
			if ( is_array( $_SESSION[$this->name] ) && isset( $_SESSION[$this->name]['name'] ) ) {
				// File field in session.
				$_FILES[$this->name] = $_SESSION[$this->name]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$value = $_SESSION[$this->name]['name']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			} else {
				// Other field in session.
				$value = $_SESSION[$this->name]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			}

			// Clean up session.
			unset($_SESSION[$this->name]);
		} else {
			// No value found.
			return null;
		}

		// Sanitize the value if needed.
		if ( ! empty( $field->sanitize ) ) {
			if ( $this->type == 'textarea' ) {
				$value = sanitize_textarea_field( $value );
			} elseif ( is_array( $value ) ) {
				$value = array_map( 'sanitize_text_field', $value );
			} else {
				$value = sanitize_text_field( $value );
			}
		}

		return $value;
	}

	/**
	 * Save the field for a user.
	 *
	 * @since 3.4
	 *
	 * @param int $user_id The user ID to save the field for.
	 */
	function save_field_for_user( int $user_id ) {
		// Get the value of the field.
		$value = $this->get_value_from_request();

		// If field was not submitted, bail.
		if ( null === $value ) {
			return;
		}

		// Check if we have a save function.
		if ( ! empty( $this->save_function ) ) {
			// Call the save function.
			call_user_func( $this->save_function, $user_id, $this->name, $value, $this );
		} else {
			// Save the value to usermeta.
			update_user_meta($user_id, $this->meta_key, $value);
		}
	}

	// Save function for users table field.
	private function saveUsersTable( $user_id, $name, $value ) {
		// Special sanitization needed for certain user fields.
		if ( $name === 'user_url' ) {
			$value = esc_url_raw( $value );
		}
		if ( $name === 'user_nicename' ) {
			$value = sanitize_title( $value );
		}

		// Save updated profile fields.
		wp_update_user( array( 'ID' => $user_id, $name => $value ) );
	}

	// Save function for user taxonomy field.
	private function saveTermRelationshipsTable( $user_id, $name, $value ) {
		// Get the taxonomy to save for.
		if ( isset( $this->taxonomy ) ) {
			$taxonomy = $this->taxonomy;
		}

		// Convert all terms in the value submitted to slugs.
		$new_values = array();
		foreach ( (array)$value as $term ) {
			if ( is_numeric( $term ) ) {
				$term_object = get_term_by( 'ID', $term, $taxonomy );
				$new_values[] = $term_object->name;
			} else {
				$new_values[] = $term;
			}
		}

		// Sets the terms for the user.
		wp_set_object_terms( $user_id, $new_values, $taxonomy, false );

		// Remove the user taxonomy relationship to terms from the cache.
		clean_object_term_cache( $user_id, $taxonomy );

		// Save terms to usermeta.
		$meta_key = str_replace( 'pmprorhprefix_', '', $name );
		update_user_meta( $user_id, $meta_key, $value );
	}

	//save function for files
	private function saveFile($user_id, $name, $value)
	{
		//setup some vars
		$user = get_userdata($user_id);
		$meta_key = str_replace("pmprorhprefix_", "", $name);

		// deleting?
		if( isset( $_REQUEST['pmpro_delete_file_' . $name . '_field'] ) ) {
			$delete_old_file_name = sanitize_text_field( $_REQUEST['pmpro_delete_file_' . $name . '_field'] );
			if ( ! empty( $delete_old_file_name ) ) {
				// Use what's saved in user meta so we don't delete any old file.
				$old_file_meta = get_user_meta( $user->ID, $meta_key, true );
				if ( 
					! empty( $old_file_meta ) && 
					! empty( $old_file_meta['fullpath'] ) && 
					file_exists( $old_file_meta['fullpath'] ) &&
					$old_file_meta['filename'] ==  $delete_old_file_name
				) {
					unlink( $old_file_meta['fullpath'] );
					if ( ! empty( $old_file_meta['previewpath'] ) ) {
						unlink( $old_file_meta['previewpath'] );
					}
					delete_user_meta( $user->ID, $meta_key );
				}
			}
		}

		// If we don't have a file to upload, return.
		if ( empty( $_FILES[ $name ] ) || empty( $_FILES[ $name ]['name'] ) ) {
			return;
		}

		// Check if we can upload the file.
		$upload_check = pmpro_check_upload( $name );
		if ( is_wp_error( $upload_check ) ) {
			pmpro_setMessage( $upload_check->get_error_message(), 'pmpro_error' );
			return;
		}

		// Get $file and $filetype.
		$file = array_map( 'sanitize_text_field', $_FILES[ $name ] );
		$filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );;

		/*
			save file in uploads
		*/
		//check for a register helper directory in wp-content
		$upload_dir = wp_upload_dir();
		$dir_path = $upload_dir['basedir'] . "/pmpro-register-helper/" . $user->user_login . "/";
		$dir_url  = $upload_dir['baseurl'] . "/pmpro-register-helper/" . $user->user_login . "/";

		//create the dir and subdir if needed
		if(!is_dir($dir_path))
		{
			wp_mkdir_p($dir_path);
		}

		//if we already have a file for this field, delete it
		$old_file = get_user_meta($user->ID, $meta_key, true);
		if(!empty($old_file) && !empty($old_file['fullpath']) && file_exists($old_file['fullpath']))
		{
			unlink($old_file['fullpath']);
		}

		//figure out new filename
		$filename = sanitize_file_name( $file['name'] );
		$count = 0;

		while(file_exists($dir_path . $filename))
		{
			if($count)
				$filename = str_lreplace("-" . $count . "." . $filetype['ext'], "-" . strval($count+1) . "." . $filetype['ext'], $filename);
			else
				$filename = str_lreplace("." . $filetype['ext'], "-1." . $filetype['ext'], $filename);

			$count++;

			//let's not expect more than 50 files with the same name
			if($count > 50)
				die( esc_html__( "Error uploading file. Too many files with the same name.", "paid-memberships-pro" ) );
		}

		$file_path = $dir_path . $filename;
		$file_url = $dir_url . $filename;

		//save file
		if(strpos($file['tmp_name'], $upload_dir['basedir']) !== false)
		{
			//was uploaded and saved to $_SESSION
			rename($file['tmp_name'], $file_path);
		}
		else
		{
			// Make sure file was uploaded.
			if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
				pmpro_setMessage( sprintf( esc_html__( 'Sorry, the file %s was not uploaded.', 'paid-memberships-pro' ), $file['name'] ), 'pmpro_error' );
				return false;
			}

			//it was just uploaded
			move_uploaded_file($file['tmp_name'], $file_path);
		}

		// If file is an image, save a preview thumbnail.
		if ( $filetype && 0 === strpos( $filetype['type'], 'image/' ) ) {
			$preview_file = wp_get_image_editor( $file_path );
			if ( ! is_wp_error( $preview_file ) ) {
				$preview_file->resize( 400, 400, false );
				$preview_file->generate_filename( 'pmpro_file_preview' );
				$preview_file = $preview_file->save();
			}
		}

		// Swap slashes for Windows
		$file_path = str_replace( "\\", "/", $file_path );
		$file_url = str_replace( "\\", "/", $file_url );
		if ( ! empty( $preview_file ) && ! is_wp_error( $preview_file ) ) {
			$preview_file['path'] = str_replace( "\\", "/", $preview_file['path'] );
		}

		$file_meta_value_array = array(
			'original_filename'	=> $file['name'],
			'filename'			=> $filename,
			'fullpath'			=> $file_path,
			'fullurl'			=> $file_url,
			'size'				=> $file['size'],
		);

		if ( ! empty( $preview_file ) && ! is_wp_error( $preview_file ) ) {
			$file_meta_value_array['previewpath'] = $preview_file['path'];
			$file_meta_value_array['previewurl'] = $dir_url .  $preview_file['file'];
		}

		//save filename in usermeta
		update_user_meta($user_id, $meta_key, $file_meta_value_array );
	}

	/**
	 * Display the field.
	 */
	function display( $value = NULL ) {
		echo $this->getHTML( $value ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$this->getDependenciesJS();
		return;
	}

	//get HTML for the field
	private function getHTML($value = "")
	{
		// Vars to store HTML to be added to the beginning or end.
		$r_beginning = '';
		$r_end = '';

		// Add the class to the div wrapper if in the admin.
		if ( is_admin() ) {
			$r_beginning .= '<div class="' . esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-' . esc_attr( $this->type ), 'pmpro_form_field-' . esc_attr( $this->type ) ) ) . '">';
			$r_end .= "</div>";
		}

		if ( '' === $value && pmpro_is_checkout() ) {
			/**
			 * Filter to set the default value for a field. The default value will only load if no value is already found.
			 * 
			 * @param string $value The default value for the field.
			 * @param object $this The field object.
			 * 
			 * @since 3.2
			 */
			$value = apply_filters( 'pmpro_field_default_value', $this->default, $this );
		}

		if($this->type == "text")
		{
			$r = '<input type="text" id="' . esc_attr( $this->id ) . '" name="' . esc_attr( $this->name ) . '" value="' . ( is_string( $value ) ? esc_attr(wp_unslash($value) ) : '' ) . '" ';
			if(!empty($this->size))
				$r .= 'size="' . esc_attr( $this->size ) . '" ';
			if(!empty($this->class))
				$r .= 'class="' . esc_attr( $this->class ) . '" ';
			if(!empty($this->readonly))
				$r .= 'readonly="readonly" ';
			if(!empty($this->html_attributes))
				$r .= $this->getHTMLAttributes();
			$r .= ' />';
		}
		elseif($this->type == "number")
		{
			$r = '<input type="number" pattern="\d+" id="' . esc_attr( $this->id ) . '" name="' . esc_attr( $this->name ) . '" value="' . esc_attr($value) . '" ';
			if(!empty($this->size))
				$r .= 'size="' . esc_attr( $this->size ) . '" ';
			if(!empty($this->class))
				$r .= 'class="' . esc_attr( $this->class ) . '" ';
			if(!empty($this->readonly))
				$r .= 'readonly="readonly" ';
			if ( empty( $this->html_attributes ) ) {
				$this->html_attributes = array();
			}
			// If custom values not available set the defaults
			if ( ! array_key_exists( 'min', $this->html_attributes ) ) {
				$this->html_attributes['min'] = '0';
			}
			if ( ! array_key_exists( 'step', $this->html_attributes ) ) {
				$this->html_attributes['step'] = '1';
			}
			$r .= $this->getHTMLAttributes();
			$r .= ' />';
		}
		elseif($this->type == "password")
		{
			$r = '<input type="password" id="' . esc_attr( $this->id ) . '" name="' . esc_attr( $this->name ) . '" value="' . esc_attr(wp_unslash($value)) . '" ';
			if(!empty($this->size))
				$r .= 'size="' . esc_attr( $this->size ) . '" ';
			if(!empty($this->class))
				$r .= 'class="' . esc_attr( $this->class ) . '" ';
			if(!empty($this->readonly))
				$r .= 'readonly="readonly" ';
			if(!empty($this->html_attributes))
				$r .= $this->getHTMLAttributes();
			$r .= ' />';
		}
		elseif($this->type == "select")
		{
			//if multiple is set, value must be an array
			if(!empty($this->multiple) && !is_array($value))
				$value = array($value);

			if(!empty($this->multiple))
				$r = '<select id="' . esc_attr( $this->id ) . '" name="' . esc_attr( $this->name ) . '[]" ';	//multiselect
			else
				$r = '<select id="' . esc_attr( $this->id ) . '" name="' . esc_attr( $this->name ) . '" ';		//regular

			if(!empty($this->class))
				$r .= 'class="' . esc_attr( $this->class ) . '" ';
			if(!empty($this->readonly))
				$r .= 'disabled="disabled" ';
			if(!empty($this->multiple))
				$r .= 'multiple="multiple" ';
			if(!empty($this->html_attributes))
				$r .= $this->getHTMLAttributes();
			$r .= ">\n";
			foreach($this->options as $ovalue => $option)
			{
				$r .= '<option value="' . esc_attr( trim( $ovalue ) ) . '" ';
				if(!empty($this->multiple) && in_array($ovalue, $value))
					$r .= 'selected="selected" ';
				elseif ( ! empty( $ovalue ) && is_string( $value ) && trim( $ovalue ) == trim( $value ) )
					$r .= 'selected="selected" ';
				$r .= '>' . esc_html( $option ) . "</option>\n";
			}
			$r .= '</select>';
		}
		elseif($this->type == "multiselect")
		{
			//value must be an array
			if(!is_array($value))
				$value = array($value);

			$r = '<select id="' . esc_attr( $this->id ) . '" name="' . esc_attr( $this->name ) . '[]" multiple="multiple" ';
			if(!empty($this->class))
				$r .= 'class="' . esc_attr( $this->class ) . '" ';
			if(!empty($this->readonly))
				$r .= 'disabled="disabled" ';
			if(!empty($this->html_attributes))
				$r .= $this->getHTMLAttributes();
			$r .= ' size="3"'; 
			$r .= ">\n";
			foreach($this->options as $ovalue => $option)
			{
				$r .= '<option value="' . esc_attr($ovalue) . '" ';
				if(in_array( trim( $ovalue ), $value ) )
					$r .= 'selected="selected" ';
				$r .= '>' . esc_html( $option ) . "</option>\n";
			}
			$r .= '</select>';
		}
		elseif($this->type == "select2")
		{
			//value must be an array
			if(!is_array($value))
				$value = array($value);

			//build multi select
			$r = '<select id="' . esc_attr( $this->id ) . '" name="' . esc_attr( $this->name ) . '[]" multiple="multiple" style="width: 100%" ';
			if(isset($this->placeholder)) {
				$r .= 'placeholder="' . esc_attr($this->placeholder) . '" ';
				if(empty($this->select2options)) {
					$this->select2options = 'placeholder: "' . esc_attr($this->placeholder) . '"';
				}
			} else {
				$r .= 'placeholder="' . esc_html__('Choose one or more.', 'paid-memberships-pro') . '" ';
			}
			if(!empty($this->class))
				$r .= 'class="' . esc_attr( $this->class ) . '" ';
			if(!empty($this->readonly))
				$r .= 'disabled="disabled" ';
			if(!empty($this->html_attributes))
				$r .= $this->getHTMLAttributes();
			$r .= '>';
			foreach($this->options as $ovalue => $option)
			{
				$r .= '<option value="' . esc_attr($ovalue) . '" ';
				if( in_array( trim( $ovalue ), $value ) )
					$r .= 'selected="selected" ';
				$r .= '>' . esc_html( $option ) . '</option>';
			}
			$r .= '</select>';
			$r .= '<input type="hidden" name="'. esc_attr( $this->name ) .'_checkbox" value="1" />';	// Extra field so we can track unchecked boxes. Naming just for consistency.

			if(!empty($this->select2options))
				$r .= '<script>jQuery(document).ready(function($){ $("#' . esc_attr( $this->id ) . '").select2({ ' . $this->select2options . ', theme: "classic", width: "resolve" }); });</script>';
			else
				$r .= '<script>jQuery(document).ready(function($){ $("#' . $this->id . '").select2( { theme: "classic", width: "resolve" }); });</script>';
		}
		elseif($this->type == "radio")
		{
			$count = 0;
			$r = '';
			$r .= '<div class="' . esc_attr( pmpro_get_element_class( 'pmpro_form_field-radio-items' ) ) . '">';
			foreach($this->options as $ovalue => $option)
			{
				$count++;
				$r .= '<div class="' . esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-radio-item' ) ) . '">';
				$r .= '<input type="radio" id="pmpro_field_' . esc_attr( $this->name . $count ) . '" name="' . esc_attr( $this->name ) . '" value="' . esc_attr($ovalue) . '" ';
				if(!empty($ovalue) && is_string($value) && trim( $ovalue ) == trim( $value ) )
					$r .= 'checked="checked"';
				if(!empty($this->class))
				$r .= 'class="' . esc_attr( $this->class ) . '" ';
				if(!empty($this->readonly))
					$r .= 'disabled="disabled" ';
				if(!empty($this->html_attributes))
					$r .= $this->getHTMLAttributes();
				$r .= ' /> ';
				$r .= '<label class="' . esc_attr( pmpro_get_element_class( 'pmpro_form_label pmpro_form_label-inline pmpro_clickable' ) ) . '" for="pmpro_field_' . esc_attr( $this->name . $count ) . '">' . esc_html( $option ) . '</label> &nbsp; ';
				$r .= '</div> <!-- end pmpro_form_field-radio-item -->';
			}
			$r .= '</div> <!-- end pmpro_form_field-radio-items -->';
		}
		elseif($this->type == "checkbox")
		{
			$r = '<label class="' . esc_attr( pmpro_get_element_class( 'pmpro_form_label pmpro_form_label-inline pmpro_clickable' ) ) . '" for="' . esc_attr( $this->name ) . '">';
			$r .= '<input name="'. esc_attr( $this->name ) .'"' .' type="checkbox" value="1"'.' id="'. esc_attr( $this->id ) .'"';
			$r .= checked( $value, 1, false );
			if(!empty($this->class))
				$r .= 'class="' . esc_attr( $this->class ) . '" ';
			if(!empty($this->readonly))
				$r .= 'disabled="disabled" ';
			if(!empty($this->html_attributes))
				$r .= $this->getHTMLAttributes();
			$r .= ' /> ';
			$r .= wp_kses_post( $this->text );
			$r .= '</label>';
			$r .= '<input type="hidden" name="'. esc_attr( $this->name ) .'_checkbox" value="1" />';	//extra field so we can track unchecked boxes
		}

		elseif($this->type == "checkbox_grouped")
		{
			//value must be an array
			if(!is_array($value))
				$value = array($value);

			$r = '<div class="' . esc_attr( pmpro_get_element_class( 'pmpro_form_field-checkbox-grouped' ) ) . '">';
			$r .= '<ul class="' . esc_attr( pmpro_get_element_class( 'pmpro_list pmpro_list-plain' ) ) . '">';
			$counter = 1;
			foreach($this->options as $ovalue => $option)
			{
				if ( ! empty( $this->class ) ) {
					$class = $this->class;
				}

				$r .= '<li class="' . esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ) . '">';
				$r .= '<span class="' . esc_attr( pmpro_get_element_class( 'pmpro_form_field-checkbox-grouped-item' ) ) . '">';
				$r .= sprintf(
					'<input name="%1$s[]" type="checkbox" value="%2$s" id="%3$s" class="%4$s" %5$s %6$s %7$s />',
					esc_attr( $this->name ),
					esc_html( $ovalue ),
					esc_attr( "{$this->id}_{$counter}" ),
					esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-checkbox ' . $this->id ), $this->id ),
					( in_array($ovalue, $value) ? 'checked="checked"' : null ),
					( !empty( $this->readonly ) ? 'readonly="readonly"' : null ),
					$this->getHTMLAttributes()
				);

				$r .= sprintf( ' <label class="' . esc_attr( pmpro_get_element_class( 'pmpro_form_label pmpro_form_label-inline pmpro_clickable' ) ) . '" for="%1$s">%2$s</label>', esc_attr( "{$this->id}_{$counter}" ), esc_html( $option ) );
				$r .= sprintf( '<input type="hidden" name="%1$s_checkbox[]" value="%2$s" />', esc_attr( $this->name ), esc_attr( $ovalue ) );	//extra field so we can track unchecked boxes
				$counter++;
				$r .= sprintf( '</span></li>' );
			}

			$r .= sprintf( '</ul></div>' );

		}
		elseif($this->type == "textarea")
		{
			$r = '<textarea id="' . esc_attr( $this->id ) . '" name="' . esc_attr( $this->name ) . '" rows="' . $this->rows . '" cols="' . esc_attr( $this->cols ) . '" ';
			if(!empty($this->class))
				$r .= 'class="' . esc_attr( $this->class ) . '" ';
			if(!empty($this->readonly))
				$r .= 'readonly="readonly" ';
			if(!empty($this->html_attributes))
				$r .= $this->getHTMLAttributes();
				$r .= '>' . ( ( is_string( $value ) ) ? esc_textarea(wp_unslash($value) ) : '' ) . '</textarea>';
		}
		elseif($this->type == "hidden")
		{
			$r = '<input type="hidden" id="' . esc_attr( $this->id ) . '" name="' . esc_attr( $this->name ) . '" value="' . esc_attr(wp_unslash($value)) . '" ';
			if(!empty($this->class))
				$r .= 'class="' . esc_attr( $this->class ) . '" ';
			if(!empty($this->readonly))
				$r .= 'readonly="readonly" ';
			if(!empty($this->html_attributes))
				$r .= $this->getHTMLAttributes();
			$r .= '/>';
		}
		elseif($this->type == "html")
		{
			//arbitrary html/etc
			if(!empty($this->html))
				$r = $this->html;
			else
				$r = "";
		}
		elseif($this->type == "file")
		{
			$r = '';

			// Show the existing file with a preview and allow user to delete or replace.
			if ( ! empty( $value ) && ( is_array( $value ) || ! empty( $this->file ) ) ) {
				if ( is_array( $value ) ) {
					$file = $value;
				} elseif ( ! empty( $this->file ) ) {
					// Legacy support for $this->file.
					$file = $this->file;
				}

				// Show a preview of existing file if image type.
				if ( ( ! empty( $this->preview ) ) && ! empty( $file['previewurl'] ) ) {
					$filetype = wp_check_filetype( basename( $file['previewurl'] ), null );
					if ( $filetype && 0 === strpos( $filetype['type'], 'image/' ) ) {
						$r_beginning .= '<div class="' . esc_attr( pmpro_get_element_class( 'pmpro_form_field-file-preview' ) ) . '"><img src="' . esc_url( $file['previewurl'] ) . '" alt="' . esc_attr( basename($file['filename']) ) . '" /></div>';
					}
				}

				if( ! empty( $file['fullurl'] ) ) {
					$r_beginning .= '<div class="pmpro_form_field-file-name pmpro_file_' . esc_attr( $this->name ) . '_name">' . sprintf(__('Current File: %s', 'paid-memberships-pro' ), '<a target="_blank" href="' . esc_url( $file['fullurl'] ) . '">' . esc_html( basename($file['filename']) ) . '</a>' ) . '</div>';
				} elseif( is_string( $value ) ) {
					$r_beginning .= sprintf(__('Current File: %s', 'paid-memberships-pro' ), basename($value) );
				}

				$r_beginning .= '<div class="pmpro_form_field-file-actions">';
				// Allow user to delete the uploaded file if we know the full location. 
				if ( ( ! empty( $this->allow_delete ) ) && ! empty( $file['fullurl'] ) ) {
					// Check whether the current user can delete the uploaded file based on the field attribute 'allow_delete'.
					if ( $this->allow_delete === true || 
						( $this->allow_delete === 'admins' || $this->allow_delete === 'only_admin' && current_user_can( 'manage_options' ) )
					) {
						$r_beginning .= '<button class="button is-destructive pmpro_btn pmpro_btn-delete" id="pmpro_delete_file_' . esc_attr( $this->name ) . '_button" onclick="return false;">' . esc_html__( 'Delete', 'paid-memberships-pro' ) . '</button>';
					}
				}

				if( empty( $this->readonly ) ) {
					$r_beginning .= '<button class="button button-secondary pmpro_btn pmpro_btn-secondary" id="pmpro_replace_file_' . esc_attr( $this->name ) . '_button" onclick="return false;">' . esc_html__( 'Replace', 'paid-memberships-pro' ) . '</button>';
					$r_beginning .= '<button class="button button-secondary pmpro_btn pmpro_btn-cancel" id="pmpro_cancel_change_file_' . esc_attr( $this->name ) . '_button" style="display: none;" onclick="return false;">' . esc_html__( 'Cancel', 'paid-memberships-pro' ) . '</button>';
					$r_beginning .= '<input id="pmpro_delete_file_' . esc_attr( $this->name ) . '_field" name="pmpro_delete_file_' . esc_attr( $this->name ) . '_field" type="hidden" value="0" />';
				}
				$r_beginning .= '</div>';
				//include script to change enctype of the form and allow deletion
				$r .= '
				<script>
					jQuery(document).ready(function() {
						jQuery("#pmpro_delete_file_' . esc_attr( $this->name ) . '_button").on("click",function(){
							jQuery("#pmpro_delete_file_' . esc_attr( $this->name ) . '_field").val("' . esc_attr( basename( $file['filename'] ) ) . '");
							jQuery(".pmpro_file_' . esc_attr( $this->name ) . '_name").css("text-decoration", "line-through");
							jQuery("#pmpro_cancel_change_file_' . esc_attr( $this->name ) . '_button").show();
							jQuery("#pmpro_delete_file_' . esc_attr( $this->name ) . '_button").hide();
							jQuery("#pmpro_replace_file_' . esc_attr( $this->name ) . '_button").hide();
							jQuery("#pmpro_file_' . esc_attr( $this->id ) . '_upload").hide();
						});

						jQuery("#pmpro_replace_file_' . esc_attr( $this->name ) . '_button").on("click",function(){
							jQuery("#pmpro_delete_file_' . esc_attr( $this->name ) . '_field").val("' . esc_attr( basename( $file['filename'] ) ) . '");
							jQuery(".pmpro_file_' . esc_attr( $this->name ) . '_name").css("text-decoration", "line-through");
							jQuery("#pmpro_cancel_change_file_' . esc_attr( $this->name ) . '_button").show();
							jQuery("#pmpro_delete_file_' . esc_attr( $this->name ) . '_button").hide();
							jQuery("#pmpro_replace_file_' . esc_attr( $this->name ) . '_button").hide();
							jQuery("#pmpro_file_' . esc_attr( $this->id ) . '_upload").show();
						});

						jQuery("#pmpro_cancel_change_file_' . esc_attr( $this->name ) . '_button").on("click",function(){
							jQuery("#pmpro_delete_file_' . esc_attr( $this->name ) . '_field").val(0);
							// set file input field to empty.
							jQuery("#' . esc_attr( $this->id ) . '").val("");
							jQuery(".pmpro_file_' . esc_attr( $this->name ) . '_name").css("text-decoration", "none");
							jQuery("#pmpro_delete_file_' . esc_attr( $this->name ) . '_button").show();
							jQuery("#pmpro_replace_file_' . esc_attr( $this->name ) . '_button").show();
							jQuery("#pmpro_cancel_change_file_' . esc_attr( $this->name ) . '_button").hide();
							jQuery("#pmpro_file_' . esc_attr( $this->id ) . '_upload").hide();
						});

					});
				</script>
				';
			}

			$r .= '
				<script>
					jQuery(document).ready(function() {
						jQuery("#' . esc_attr( $this->id ) . '").closest("form").attr("enctype", "multipart/form-data");
					});
				</script>
				';
			$r .= '<div id="pmpro_file_' . esc_attr( $this->id ) . '_upload" class="' . esc_attr( pmpro_get_element_class( 'pmpro_form_field-file-upload' ) ) . '" ' . ((empty($value) || is_string($value)) ? '' : 'style="display: none;"') . '>';
			$r .= '<input type="file" id="' . esc_attr( $this->id ) . '" ';
			
			if ( ! empty( $this->allowed_file_types ) ) {

				// Break it out into an array if it is possible.
				$allowed_file_array = explode( ',', $this->allowed_file_types );

				// loop through the allowed arrays and add a period if there isn't one, BUT skip this if it contains a /* pattern (we can assume that it's okay.)
				foreach( $allowed_file_array as $key => $allowed_file_type ) {
					if ( strpos( $allowed_file_type, '/' ) === false && strpos( $allowed_file_type, '.' ) !== 0 ) {
						$allowed_file_array[ $key ] = '.' . $allowed_file_type;
					}
				}

				// Convert it back to a comma-separated string before adding it to the HTML.
				$this->allowed_file_types = implode( ',', $allowed_file_array );

				$r .= 'accept="' . esc_attr( $this->allowed_file_types ) . '" ';
			}

			if ( ! empty( $this->class ) ) {
				$r .= 'class="' . esc_attr( $this->class ) . '" ';
			}

			if ( ! empty( $this->html_attributes ) ) {
				$r .= $this->getHTMLAttributes();
			}

			if ( ! empty( $this->readonly ) ) {
				$r .= 'disabled="disabled" ';
			}

			$r .= 'name="' . esc_attr( $this->name ) . '" />';
			$r .= '</div>';

		}
		elseif($this->type == "date")
		{
			$r = '<input type="date" id="' . esc_attr( $this->id ) . '" name="' . esc_attr( $this->name ) . '" value="' . esc_attr(wp_unslash($value)) . '" ';
			if(!empty($this->class))
				$r .= 'class="' . esc_attr( $this->class ) . '" ';
			if(!empty($this->readonly))
				$r .= 'readonly="readonly" ';
			if(!empty($this->html_attributes))
				$r .= $this->getHTMLAttributes();
			$r .= ' />';
		}
		elseif($this->type == "readonly")
		{
			if ( empty( $value ) ) {
				$value = '&#8212;';
			}
			$r = $value;
		}
		else
		{
			$r = "Unknown type <strong>" . esc_attr( $this->type ) . "</strong> for field <strong>" . esc_attr( $this->name ) . "</strong>.";
		}

		//show required by default
		if(!empty($this->required) && !isset($this->showrequired))
			$this->showrequired = true;

		/**
		 * Legacy filter to show a field as required on the profile page.
		 *
		 * @deprecated 3.1 Use the pmpro_show_required_on_profile filter instead.
		 *
		 * @param bool $showrequired Whether to show the field as required on the profile page.
		 * @return bool Whether to show the field as required on the profile page.
		 */
		$show_required_on_profile = apply_filters_deprecated( 'pmprorh_show_required_on_profile', array( false, $this ), '3.1', 'pmpro_field_show_required_on_profile' );

		/**
		 * Filter to show a field as required on the profile page.
		 *
		 * @since 3.1
		 *
		 * @param bool $showrequired Whether to show the field as required on the profile page.
		 * @return bool Whether to show the field as required on the profile page.
		 */
		$show_required_on_profile = apply_filters( 'pmpro_field_show_required_on_profile', false, $this );

		// If the field is required and the showrequired attribute is set to false, don't show the required indicator.
		if ( defined('IS_PROFILE_PAGE') && IS_PROFILE_PAGE && ! $show_required_on_profile )
			$this->showrequired = false;

		if ( ! empty( $this->required ) && ! empty( $this->showrequired ) && is_string( $this->showrequired ) && $this->showrequired !== 'label' ) {
				$r .= $this->showrequired;
		}

		//anything meant to be added to the beginning or end?
		$r = $r_beginning . $r . $r_end;

		/**
		 * Legacy filter to allow hooking into the generated field HTML.
		 *
		 * @deprecated 3.1
		 *
		 * @param string      $r     The field HTML.
		 * @param PMPro_Field $field The field object.
		 */
		$r = apply_filters_deprecated( 'pmprorh_get_html', array( $r, $this ), '3.1', 'pmpro_field_get_html' );

		/**
		 * Filter to allow hooking into the generated field HTML.
		 *
		 * @since 3.1
		 *
		 * @param string      $r     The field HTML.
		 * @param PMPro_Field $field The field object.
		 * 
		 */
		$r = apply_filters( 'pmpro_field_get_html', $r, $this );

		return $r;
	}

	/**
	 * Get the HTML attributes for this field.
	 *
	 * Note: Attribute names and values are escaped for output.
	 *
	 * @return string The HTML attributes for this field.
	 */
	function getHTMLAttributes() {
		$html = '';

		if ( ! empty( $this->html_attributes ) ) {
			foreach ( $this->html_attributes as $name => $value ) {
				// Note: In the future, WP may introduce esc_attr_name(), but for now we'll do it ourselves.
				$attribute_name = wp_check_invalid_utf8( $name );
				$attribute_name = preg_replace( '/[^a-zA-Z0-9\-_\[\]]+/', '-', $attribute_name );

				$html .= sprintf(
					' %1$s="%2$s"',
					$attribute_name,
					esc_attr( $value )
				);
			}
		}

		return $html;
	}

	private function getDependenciesJS()
	{
		global $pmpro_user_fields;
		//dependencies
		if(!empty($this->depends))
		{
			//build the checks
			$checks_escaped = array();
			$binds = array();
			foreach($this->depends as $check)
			{
				if(!empty($check['id']))
				{
					// If checking checkbox_grouped, need to update the $check['id'] with index of option.
					$field_id = $check['id'];
					$depends_field = PMPro_Field_Group::get_field( $field_id );
					if ( empty( $depends_field ) ) {
						continue;
					}
				
					$depends_field_group = PMPro_Field_Group::get_group_for_field( $depends_field );
					if ( empty( $depends_field_group ) ) {
						continue;
					}

					// Let's simplify.
					switch ( $depends_field->type ) {
						case 'checkbox_grouped':
							// Find an input with the name of the field and the value of the option, then check if it is selected.
							// Don't use the ID.
							$checks_escaped[] = "jQuery('input[name=\"" . esc_js( $field_id ) . "[]\"][value=" . esc_js( $check['value'] ) . "]:checked').length > 0";
							$binds[] = "input[name=\"" . esc_js( $field_id ) . "[]\"]";
							break;
						case 'checkbox':
							$checks_escaped[] = "jQuery('#" . esc_html( $field_id ) . "').is(':checked') == " . ( empty( $check['value'] ) ? 'false' : 'true' );
							$binds[] = "#" . esc_html( $field_id );
							break;
						case 'radio':
							$checks_escaped[] = "jQuery('input[name=\"" . esc_js( $field_id ) . "\"][value=\"" . esc_js( $check['value'] ) . "\"]:checked').length > 0";
							$binds[] = "input[type=radio][name=\"" . esc_js( $field_id ) . "\"]";
							break;
						default:
							$checks_escaped[] = "jQuery('#" . esc_html( $field_id ) . "').val() == " . json_encode( $check['value'] ) . " || jQuery.inArray( jQuery('#" . esc_html( $field_id ) . "').val(), " . json_encode( $check['value'] ) . ") > -1";
							$binds[] = "#" . esc_html( $field_id );
							break;
					}
				}
			}

			if(!empty($checks_escaped) && !empty($binds)) {
			?>
			<script>
				//function to check and hide/show
				function pmpro_<?php echo esc_html( $this->id );?>_hideshow() {
					let checks = [];
					<?php
					foreach( $checks_escaped as $check_escaped ) {
					?>
					checks.push(<?php echo $check_escaped; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>);
					<?php
					}

					if ( $this->depends_or ) {
					?>
						let show = checks.indexOf(true) > -1;
					<?php
					} else {
					?>
						let show = checks.indexOf(false) === -1;
					<?php
					}
					?>
					if(show) {
						jQuery('#<?php echo esc_html( $this->id );?>_tr').show();
						jQuery('#<?php echo esc_html( $this->id );?>_div').show();
						jQuery('#<?php echo esc_html( $this->id );?>').removeAttr('disabled');
					} else {
						jQuery('#<?php echo esc_html( $this->id );?>_tr').hide();
						jQuery('#<?php echo esc_html( $this->id );?>_div').hide();
						jQuery('#<?php echo esc_html( $this->id );?>').attr('disabled', 'disabled');
					}
				}

				jQuery(document).ready(function() {
						//run on page load
						pmpro_<?php echo esc_html( $this->id );?>_hideshow();

						//and run when certain fields are changed
						jQuery('<?php echo implode(',', $binds); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>').bind('click change keyup', function() {
							pmpro_<?php echo esc_html( $this->id );?>_hideshow();
						});
				});
			</script>
			<?php
			}
		}
	}

	/**
	 * Display the field at checkout.
	 *
	 * @deprecated 3.4 Use PMPro_Field_Group::display() instead.
	 */
	function displayAtCheckout()
	{
		_deprecated_function( __METHOD__, '3.4', 'PMPro_Field_Group::display()' );
		global $current_user;

		if( null !== $this->get_value_from_request() ) {
			$value = $this->get_value_from_request();
		} elseif(!empty($current_user->ID) && metadata_exists("user", $current_user->ID, $this->meta_key)) {
			$value = get_user_meta($current_user->ID, $this->meta_key, true);
		} elseif ( ! empty( $current_user->ID ) ) {
			$userdata = get_userdata( $current_user->ID );
			if ( ! empty( $userdata->{$this->name} ) ) {
				$value = $userdata->{$this->name};
			} elseif(isset($this->value)) {
				$value = $this->value;
			} else {
				$value = '';
			}
		} elseif(isset($this->value)) {
			$value = $this->value;
		} else {
			$value = "";
		}

		// Fix divclass.
		if ( ! empty( $this->divclass ) ) {
			$this->divclass .= " ";
		}

		// Add a class to the field based on the type.
		$this->divclass .= "pmpro_form_field pmpro_form_field-" . $this->type;
		$this->class .= " pmpro_form_input-" . $this->type;

		// Add the required class to field.
		if ( ! empty( $this->required ) ) {
			$this->divclass .= " pmpro_form_field-required";
			$this->class .= " pmpro_form_input-required";
		}

		// Add the class to not show a field is required if set.
		if ( empty( $this->showrequired ) || is_string( $this->showrequired ) ) {
			$this->divclass .= " pmpro_form_field-hide-required";
		}

		// Run the class through the filter.
		$this->divclass = pmpro_get_element_class( $this->divclass );
		$this->class = pmpro_get_element_class( $this->class );

		?>
		<div id="<?php echo esc_attr( $this->id );?>_div" <?php if ( ! empty( $this->divclass ) ) { echo 'class="' . esc_attr( $this->divclass ) . '"'; } ?>>
			<?php if(!empty($this->showmainlabel)) { ?>
				<label class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>" for="<?php echo esc_attr($this->name);?>">
					<?php echo wp_kses_post( $this->label );?>
					<?php 
						if(!empty($this->required) && !empty($this->showrequired) && $this->showrequired === 'label')
						{
						?><span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_asterisk' ) ); ?>"> <abbr title="<?php esc_attr_e( 'Required Field' ,'paid-memberships-pro' ); ?>">*</abbr></span><?php
						}
					?>
				</label>
				<?php $this->display($value); ?>
			<?php } else { ?>
				<?php $this->display($value); ?>
			<?php } ?>

			<?php if(!empty($this->hint)) { ?>
				<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_hint' ) ); ?>"><?php echo wp_kses_post( $this->hint );?></p>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * @deprecated 3.4 Use PMPro_Field_Group::display() instead.
	 */
	function displayInProfile($user_id, $edit = NULL)
	{
		_deprecated_function( __METHOD__, '3.4', 'PMPro_Field_Group::display()' );
		global $current_user;
		if(metadata_exists("user", $user_id, $this->meta_key))
		{
			$value = get_user_meta($user_id, $this->meta_key, true);
		}
		elseif(!empty($this->value))
			$value = $this->value;
		else
			$value = "";
		?>
		<tr id="<?php echo esc_attr( $this->id );?>_tr">
			<th>
				<?php if ( ! empty( $this->showmainlabel ) ) { ?>
					<label for="<?php echo esc_attr($this->name);?>"><?php echo wp_kses_post( $this->label );?></label>
				<?php } ?>
			</th>
			<td>
				<?php
					if(current_user_can("edit_user", $user_id) && $edit !== false)
						$this->display($value);
					else
						echo "<div>" . wp_kses_post( $this->displayValue($value) ) . "</div>";
				?>
				<?php if(!empty($this->hint)) { ?>
					<p class="description"><?php echo wp_kses_post( $this->hint );?></p>
				<?php } ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Echo the value of the field based on type
	 * and taking into account fields with options.
	 * @param mixed $value The value to be shown.
	 * @param bool $echo Whether to echo the value or return it.
	 * @since 3.0 Shows files as links and added echo parameter.
	 */
	function displayValue( $value, $echo = true ) {
		// Build the output.
		$output = '';
		$allowed_html = array();

		// Switch on the type of field.
		switch( $this->type ) {
			case 'text':
			case 'textarea':
				// Make sure that the value is a string.
				$output = is_string( $value ) ? $value : '';

				// If the field is a URL, check if we should try to embed it or show it as a link.
				if ( wp_http_validate_url( $value ) ) {
					/**
					 * Filter whether links should be clickable, embedded, or shown as plain text.
					 *
					 * @since 3.4
					 *
					 * @param string $link_display_type The type of link display. Accepts 'embedded', 'clickable_link', 'clickable_label', or 'text'.
					 * @param string $value             The value to be shown.
					 * @param PMPro_Field $field	    Field object that the value is for.
					 */
					$link_display_type = apply_filters( 'pmpro_field_value_link_display_type', 'embedded', $value, $this );
					switch ( $link_display_type ) {
						case 'embedded':
							$url_embed = wp_oembed_get( $value );
							if ( ! empty( $url_embed ) ) {
								// Oembed returned a value.
								$output = $url_embed;
								$allowed_html = array(
									'iframe' => array(
										'src'             => true,
										'height'          => true,
										'width'           => true,
										'frameborder'     => true,
										'allowfullscreen' => true,
										'allow'           => true,
									),
									'script' => array(
										'type' => true,
										'src'  => true,
									),
								);
								break;
							}
							// If we got here, we can't embed. Fall through to clickable link.
						case 'clickable_link':
							$output = '<a href="' . esc_url( $value ) . '" target="_blank">' . esc_html( $value ) . '</a>';
							$allowed_html = array(
								'a' => array(
									'href'   => true,
									'target' => true,
								),
							);
							break;
						case 'clickable_label':
							$output = '<a href="' . esc_url( $value ) . '" target="_blank">' . esc_html( $this->label ) . '</a>';
							$allowed_html = array(
								'a' => array(
									'href'   => true,
									'target' => true,
								),
							);
							break;
						default:
							// Do nothing. The value is already set.
							$output = $value;
							break;
					}
				} else {
					$output = $value;
				}
				break;
			case 'checkbox':
				$output = $value ? esc_html__( 'Yes', 'paid-memberships-pro' ) : esc_html__( 'No', 'paid-memberships-pro' );
				break;
			case 'number':
					// Make sure that the value is a number.
					$output = is_numeric( $value ) ? number_format_i18n( $value ) : '';
					break;
			case 'date':
				$timestamp = strtotime( $value );
				$output = $timestamp ? date_i18n( get_option( 'date_format' ), $timestamp ) : '';
				break;
			case 'select':
			case 'multiselect':
			case 'select2':
			case 'radio':
			case 'checkbox_grouped':
				// For simplicity, make sure that $value and $this->options are arrays.
				if ( ! is_array( $value ) ) {
					$value = array( $value );
				}
				if ( ! is_array( $this->options ) ) {
					$this->options = array();
				}
				$labels = array();
				foreach( $value as $item ) {
					$labels[] = array_key_exists( $item, $this->options ) ? $this->options[ $item ] : $item;
				}
				$output = implode( ', ', $labels );
				break;
			case 'file':
				// Validate the value.
				if ( empty( $value ) ) {
					$output = esc_html__( 'No file uploaded.', 'paid-memberships-pro' );
				} elseif ( ! is_array( $value ) || empty( $value['fullurl'] ) ) {
					$output = esc_html__( 'Invalid file data.', 'paid-memberships-pro' );
				} else {
					// We have a file. Determine how to display it.
					$file_type = wp_check_filetype($value['fullurl']);
					switch ( $file_type['type'] ) {
						case 'image/jpeg':
						case 'image/png':
						case 'image/gif':
							$output = '<div class="' . pmpro_get_element_class( 'pmpro_form_field-file-preview' ) . '"><img class="' . pmpro_get_element_class( 'pmpro_form_field-file-subtype_' . $file_type['ext'] ) . '" alt="" src="' . $value['previewurl'] . '"></div><div class="' . pmpro_get_element_class( 'pmpro_form_field-file-name' ) . '"><a href="' . $value['fullurl'] . '" target="_blank">' . $value['filename'] . '</a></div>';
							$allowed_html = array(
								'a' => array(
									'href' => array(),
									'title' => array(),
									'target' => array(),
								),
								'div' => array(
									'class' => array(),
								),
								'img' => array(
									'alt' => array(),
									'class' => array(),
									'src' => array(),
								),
								'span' => array(
									'class' => array(),
								),
							);
							break;
						case 'video/mpeg':
						case 'video/mp4':
							$output = do_shortcode('[video src="' . $value['fullurl'] . '"]');
							$allowed_html = array(
								'video' => array(
									'src'       => true,
									'poster'    => true,
									'width'     => true,
									'height'    => true,
									'preload'   => true,
									'controls'  => true,
									'autoplay'  => true,
									'loop'      => true,
									'muted'     => true,
								),
								'source' => array(
									'src'   => true,
									'type'  => true,
								),
							);
							break;
						case 'audio/mpeg':
						case 'audio/wav':
							$output = do_shortcode('[audio src="' . $value['fullurl'] . '"]');
							$allowed_html = array(
								'audio' => array(
									'src'       => true,
									'controls'  => true,
									'autoplay'  => true,
									'loop'      => true,
									'muted'     => true,
									'preload'   => true,
								),
								'source' => array(
									'src'   => true,
									'type'  => true,
								),
							);
							break;
						default:
							$output = '<a href="' . $value['fullurl'] . '" target="_blank"><img class="' . pmpro_get_element_class( 'pmpro_form_field-file-subtype_' . $file_type['ext'] ) . '" src="' . wp_mime_type_icon( $file_type['type'] ) . '"><div class="' . pmpro_get_element_class( 'pmpro_form_field-file-name' ) . '">' . $value['filename'] . '</div></a>';
							$allowed_html = array(
								'a' => array(
									'href' => array(),
									'target' => array(),
								),
								'img' => array(
									'class' => array(),
									'src' => array(),
								),
								'div' => array(
									'class' => array(),
								),
							);
							break;
					}
					// Wrap the output in a div.
					$output = '<div class="' . pmpro_get_element_class( 'pmpro_form_field-file-' . $file_type['ext'] ) . '">' . $output . '</div>';
				}
				break;
			default:
				$output = (string) $value;
				break;
		}

		// Enforce string as output.
		$output = (string) $output;

		if ( $echo ) {
			echo wp_kses( $output, $allowed_html );
		} else {
			return wp_kses( $output, $allowed_html );
		}
	}

	/**
	 * Defining associative as integer array keys incrementing from 0.
	 * 
	 * Based off of https://stackoverflow.com/a/173479.
	 *
	 * @param array $array The array to check if it is associative.
	 * @return bool True if the array is associative, false otherwise.
	 */
	private function is_assoc( $array ) {
		if ( empty( $array ) ) {
			return false;
		}
		return array_keys( $array ) !== range( 0, count( $array ) - 1) ;
	}

	/**
	 * @deprecated 3.4 Use PMPro_Field_Group::get_group_for_field() instead.
	 */
	static function get_checkout_box_name_for_field( $field_name ) {
		_deprecated_function( __METHOD__, '3.4', 'PMPro_Field_Group::get_group_for_field()' );
		$field = PMPro_Field_Group::get_field( $field_name );
		if ( empty( $field ) ) {
			return '';
		}
	
		$field_group = PMPro_Field_Group::get_group_for_field( $field );
		return $field_group ? $field_group->name : '';
	}

	/**
	 * @deprecated 3.4
	 */
	function was_present_on_checkout_page() {
		_deprecated_function( __METHOD__, '3.4' );
		// Check if checkout box that field is in is on page.
		$checkout_box = PMPro_Field_Group::get_group_for_field( $this );
		if ( empty( $checkout_box ) ) {
			// Checkout box does not exist.
			return false;
		}

		$user_fields_locations = array(
			'after_username',
			'after_password',
			'after_email',
			'after_captcha',
		);
		if ( is_user_logged_in() && in_array( $checkout_box->name, $user_fields_locations ) ) {
			// User is logged in and field is only for new users.
			return false;
		}

		// Check if field is hidden because of "depends" option.
		if ( ! empty( $this->depends ) ) {
			//build the checks
			$checks = array();
			foreach($this->depends as $check) {
				if( ! empty( $check['id'] ) && isset( $check['value'] ) ) {
					// We have a valid depends statement.
					if ( isset( $_REQUEST[ $check['id'] . '_checkbox' ] ) ) {
						// This fields depends on a checkbox or checkbox_grouped input.
						if ( isset( $_REQUEST[ $check['id'] ] ) && is_array( $_REQUEST[ $check['id'] ] ) ) {
							// checkbox_grouped input.
							if ( ! in_array( $check['value'], $_REQUEST[ $check['id'] ] ) ) {
								return false;
							}
						} elseif ( isset( $_REQUEST[ $check['id'] ] ) && '1' === $_REQUEST[ $check['id'] ] ) {
							// Single checkbox that is checked.
							if ( empty( $check['value'] ) ) {
								// Set to show field only if checkbox is unchecked.
								return false;
							}
						} else {
							// Single checkbox that is unchecked. Set to show field only if checkbox is checked.
							if ( ! empty( $check['value'] ) ) {
								return false;
							}
						}
					} elseif ( isset( $_REQUEST[ $check['id'] ] ) && $check['value'] != $_REQUEST[ $check['id'] ] ) {
						// This fields depends on another field type.
						return false;
					}
				}
			}
		}

		// Field should have been showed at checkout.
		return true;
	}

	/**
	 * Check if the field was filled if needed.
	 */
	function was_filled_if_needed() {
		// If the field is not required, skip it.
		if ( empty( $this->required ) ) {
			return true;
		}

		// If this field has a 'depends` attribute, check if the field was actually shown.
		if ( ! empty( $this->depends ) ) {
			foreach ( $this->depends as $check ) {
				// If the $check object isn't valid, skip it.
				if ( ! isset( $check->id ) || ! isset( $check->value ) ) {
					return true;
				}

				// Get the field to check.
				$check_field = PMPro_Field_Group::get_field( $check->id );
				if ( empty( $check_field ) ) {
					// The check field doesn't exist, so this field wasn't shown.
					return true;
				}

				// Get the value of the field.
				$check_field_value = $check_field->get_value_from_request();
				if ( null === $check_field_value ) {
					// The check field wasn't submitted, so this field wasn't shown.
					return true;
				}

				// If $check['value'] doesn't match the value of the field, skip this field.
				if ( is_array( $check_field_value ) ) {
					if ( ! in_array( $check->value, $check_field_value ) ) {
						return true;
					}
				} else {
					if ( $check->value !== $check_field_value ) {
						return true;
					}
				}
			}
		}

		// At this point, we know that the field needs to be filled.
		$value = $this->get_value_from_request();
		switch ( $this->type ) {
			case 'text':
			case 'textarea':
			case 'number':
				$filled = ( null !== $value && '' !== trim( $value ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				break;
			case 'file':
				if ( ! empty( $_FILES[ $this->name ]['name'] ) ) {
					$filled = true;
				} elseif ( ! empty( get_user_meta( get_current_user_id(), $this->name, true ) ) && empty( $_REQUEST['pmpro_delete_file_' . $this->name . '_field'] ) ) {
					$filled = true;
				} else {
					$filled = false;
				}
				break;
			default:
				$filled = ! empty( $value );
		}
		return $filled;
	}
}