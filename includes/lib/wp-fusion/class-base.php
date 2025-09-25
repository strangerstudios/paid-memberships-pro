<?php
// IMPORTANT: Prefixing this base class from WP Fusion with "PMPro_" to avoid fatal errors if WP Fusion is activated after PMPro or any similar situation.
abstract class PMPro_WPF_Integrations_Base {

	/**
	 * The slug name for WP Fusion's module tracking.
	 *
	 * @since 1.0.0
	 * @var string $slug
	 */
	public $slug;

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 1.0.0
	 * @var string $name
	 */
	public $name;

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.38.14
	 * @var string|bool $docs_url The URL.
	 */
	public $docs_url = false;

	public function __construct() {

		// Make the object globally available.

		if ( $this->slug ) {
			wp_fusion()->integrations->{$this->slug} = $this;
		}

		if ( $this->is_integration_active() ) {
			$this->init();
		}

		add_filter( 'wpf_compatibility_notices', array( $this, 'compatibility_notices' ) );
		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ) );
		add_filter( 'wpf_meta_fields', array( $this, 'add_meta_fields' ) );
	}

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */
	abstract protected function init();

	/**
	 * Adds compatibility notices.
	 *
	 * @since 3.44.6
	 *
	 * @param array $notices The notices.
	 */
	public function compatibility_notices( $notices ) {
		return $notices;
	}

	/**
	 * Adds a meta field group.
	 *
	 * @since 3.44.22
	 *
	 * @param array $field_groups The field groups.
	 */
	public function add_meta_field_group( $field_groups ) {
		return $field_groups;
	}

	/**
	 * Adds meta fields.
	 *
	 * @since 3.44.22
	 *
	 * @param array $meta_fields The meta fields.
	 */
	public function add_meta_fields( $meta_fields ) {
		return $meta_fields;
	}

	/**
	 * Map meta fields collected at registration / profile update to internal fields
	 *
	 * @access  public
	 * @since   3.0
	 * @return  array Meta Fields
	 */
	protected function map_meta_fields( $meta_fields, $field_map ) {

		foreach ( $field_map as $key => $field ) {

			if ( ! empty( $meta_fields[ $key ] ) && empty( $meta_fields[ $field ] ) ) {
				$meta_fields[ $field ] = $meta_fields[ $key ];
			}
		}

		return $meta_fields;
	}

	/**
	 * Checks if the integration is active.
	 *
	 * @since 3.42.6
	 *
	 * @return bool Is the integration active.
	 */
	public function is_integration_active() {

		if ( false === $this->docs_url ) {
			return true; // some integrations don't show up in the settings and can't be disabled.
		}

		$integrations = wpf_get_option( 'integrations', array() );

		if ( isset( $integrations[ $this->slug ] ) && false === $integrations[ $this->slug ] ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Gets dynamic tags to be applied from update data
	 *
	 * @access  public
	 * @since   3.32.2
	 * @return  array Tags
	 */
	public function get_dynamic_tags( $update_data ) {

		$apply_tags = array();

		if ( ! wp_fusion()->crm->supports( 'add_tags' ) ) {
			return $apply_tags;
		}

		foreach ( $update_data as $key => $value ) {

			$crm_field = wp_fusion()->crm->get_crm_field( $key );

			if ( false === $crm_field ) {
				continue;
			}

			if ( false !== strpos( $crm_field, 'add_tag_' ) ) {

				if ( is_array( $value ) ) {

					$apply_tags = array_merge( $apply_tags, $value );

				} elseif ( ! empty( $value ) ) {

					$apply_tags[] = $value;

				}
			}
		}

		return $apply_tags;
	}

	/**
	 * Handles signups from plugins which support guest registrations
	 *
	 * @since   3.26.6
	 *
	 * @param string $email_address The email address of the contact.
	 * @param array  $update_data   The update data.
	 *
	 * @return string|bool Contact ID on success or false in case of error.
	 */
	public function guest_registration( $email_address, $update_data ) {

		$contact_id = wp_fusion()->crm->get_contact_id( $email_address );

		if ( is_wp_error( $contact_id ) ) {
			wpf_log( $contact_id->get_error_code(), 0, 'Error looking up contact ID for email address <strong>' . $email_address . '</strong>: ' . $contact_id->get_error_message() );
			return false;
		}

		$update_data = apply_filters( "wpf_{$this->slug}_guest_registration_data", $update_data, $email_address, $contact_id );

		// Log whether we're creating or updating a contact, with edit link.
		if ( false !== $contact_id ) {
			$log_text = ' Updating existing contact #' . $contact_id . ': ';
		} else {
			$log_text = ' Creating new contact: ';
		}

		wpf_log( 'info', 0, $this->name . ' guest registration.' . $log_text, array( 'meta_array' => $update_data ) );

		if ( empty( $contact_id ) ) {

			$contact_id = wp_fusion()->crm->add_contact( $update_data );

			if ( ! is_wp_error( $contact_id ) ) {
				do_action( 'wpf_guest_contact_created', $contact_id, $email_address );
			}
		} else {

			wp_fusion()->crm->update_contact( $contact_id, $update_data );

			do_action( 'wpf_guest_contact_updated', $contact_id, $email_address );

		}

		if ( is_wp_error( $contact_id ) ) {

			wpf_log( $contact_id->get_error_code(), 0, 'Error adding contact: ' . $contact_id->get_error_message() );
			return false;

		}

		return $contact_id;
	}
}
