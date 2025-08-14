<?php
/**
 * WP Fusion - Paid Memberships Pro Admin Integration.
 *
 * @package   WP Fusion
 * @copyright Copyright (c) 2024, Very Good Plugins, https://verygoodplugins.com
 * @license   GPL-3.0+
 * @since     3.45.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles admin functionality for the Paid Memberships Pro integration.
 *
 * @since 3.45.3
 */
class WPF_PMPro_Admin {

	/**
	 * Constructor.
	 *
	 * @since 3.45.3
	 */
	public function __construct() {

		// Meta fields.
		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ) );
		add_filter( 'wpf_meta_fields', array( $this, 'add_meta_fields' ) );

		// Admin settings.
		add_action( 'pmpro_membership_level_before_content_settings', array( $this, 'membership_level_settings' ) );
		add_action( 'pmpro_save_membership_level', array( $this, 'save_level_settings' ) );

		add_action( 'pmpro_discount_code_after_settings', array( $this, 'discount_code_settings' ) );
		add_action( 'pmpro_save_discount_code', array( $this, 'save_discount_code_settings' ) );
	}


	/**
	 * Gets the membership level CRM fields.
	 *
	 * @since 3.45.3
	 *
	 * @return array The membership level CRM fields.
	 */
	public function get_membership_level_crm_fields() {

		$fields = array(
			'pmpro_membership_level'   => array(
				'label' => 'Membership Level',
				'type'  => 'text',
				'group' => 'pmpro',
			),
			'pmpro_status'             => array(
				'label' => 'Membership Status',
				'type'  => 'text',
				'group' => 'pmpro',
			),
			'pmpro_start_date'         => array(
				'label' => 'Membership Start Date',
				'type'  => 'date',
				'group' => 'pmpro',
			),
			'pmpro_expiration_date'    => array(
				'label' => 'Membership Expiration Date',
				'type'  => 'date',
				'group' => 'pmpro',
			),
			'pmpro_subscription_price' => array(
				'label' => 'Subscription Price',
				'type'  => 'text',
				'group' => 'pmpro',
			),
			'pmpro_next_payment_date'  => array(
				'label' => 'Next Payment Date',
				'type'  => 'date',
				'group' => 'pmpro',
			),
		);

		return $fields;
	}

	/**
	 * Adds PMPro field group to meta fields list.
	 *
	 * @since 3.45.3
	 *
	 * @param array $field_groups The field groups.
	 * @return array Field groups.
	 */
	public function add_meta_field_group( $field_groups ) {

		$field_groups['pmpro'] = array(
			'title'  => 'Paid Memberships Pro',
			'fields' => array(),
		);

		return $field_groups;
	}

	/**
	 * Adds PMPro meta fields to WPF contact fields list.
	 *
	 * @since 3.45.3
	 *
	 * @param array $meta_fields The meta fields.
	 * @return array Meta fields.
	 */
	public function add_meta_fields( $meta_fields = array() ) {

		// Billing fields.
		$meta_fields['pmpro_bfirstname'] = array(
			'label' => 'Billing First Name',
			'type'  => 'text',
			'group' => 'pmpro',
		);

		$meta_fields['pmpro_blastname'] = array(
			'label' => 'Billing Last Name',
			'type'  => 'text',
			'group' => 'pmpro',
		);

		$meta_fields['pmpro_baddress1'] = array(
			'label' => 'Billing Address 1',
			'type'  => 'text',
			'group' => 'pmpro',
		);

		$meta_fields['pmpro_baddress2'] = array(
			'label' => 'Billing Address 2',
			'type'  => 'text',
			'group' => 'pmpro',
		);

		$meta_fields['pmpro_bcity'] = array(
			'label' => 'Billing City',
			'type'  => 'text',
			'group' => 'pmpro',
		);

		$meta_fields['pmpro_bstate'] = array(
			'label' => 'Billing State',
			'type'  => 'text',
			'group' => 'pmpro',
		);

		$meta_fields['pmpro_bzipcode'] = array(
			'label' => 'Billing Postal Code',
			'type'  => 'text',
			'group' => 'pmpro',
		);

		$meta_fields['pmpro_bcountry'] = array(
			'label' => 'Billing Country',
			'type'  => 'text',
			'group' => 'pmpro',
		);

		$meta_fields['pmpro_bphone'] = array(
			'label' => 'Billing Phone',
			'type'  => 'text',
			'group' => 'pmpro',
		);

		$meta_fields['pmpro_bemail'] = array(
			'label' => 'Billing Email',
			'type'  => 'text',
			'group' => 'pmpro',
		);

		$meta_fields['pmpro_joined_date'] = array(
			'label'  => 'Joined Date',
			'type'   => 'date',
			'group'  => 'pmpro',
			'pseudo' => true,
		);

		// Add membership fields from get_membership_level_crm_fields.
		$meta_fields = array_merge( $meta_fields, $this->get_membership_level_crm_fields() );

		// Add payment method field (not included in membership level fields).
		$meta_fields['pmpro_payment_method'] = array(
			'label' => 'Payment Method',
			'type'  => 'text',
			'group' => 'pmpro',
		);

		// Add level-specific field mappings for each membership level.
		$contact_fields = wpf_get_option( 'contact_fields', array() );

		foreach ( $contact_fields as $key => $value ) {
			if ( ! empty( $value['crm_field'] ) ) {
				$crm_key = substr( $key, 0, strrpos( $key, '_' ) );

				if ( isset( $membership_fields[ $crm_key ] ) ) {
					$post_id             = (int) str_replace( $crm_key . '_', '', $key );
					$meta_fields[ $key ] = array(
						'label'  => pmpro_getLevel( $post_id )->name . ' - ' . $membership_fields[ $crm_key ]['label'],
						'type'   => $membership_fields[ $crm_key ]['type'],
						'pseudo' => true,
						'group'  => 'pmpro',
					);
				}
			}
		}

		// Add user fields.
		global $pmpro_user_fields, $pmprorh_registration_fields;

		$custom_fields = array_merge( (array) $pmpro_user_fields, (array) $pmprorh_registration_fields );

		if ( ! empty( $custom_fields ) ) {
			foreach ( $custom_fields as $field_group ) {
				if ( is_array( $field_group ) ) {
					foreach ( $field_group as $field ) {
						if ( ! empty( $field->id ) ) {
							$meta_fields[ $field->id ] = array(
								'label' => $field->label,
								'type'  => 'text',
								'group' => 'pmpro',
							);
						}
					}
				}
			}
		}

		return $meta_fields;
	}

	/**
	 * Adds options to PMP membership level settings.
	 *
	 * @since 3.45.3
	 *
	 * @param object $level The level object.
	 */
	public function membership_level_settings( $level ) {

		$settings = get_option( 'wpf_pmp_' . $level->id );

		if ( empty( $settings ) ) {
			$settings = array(
				'apply_tags'                      => array(),
				'remove_tags'                     => false,
				'tag_link'                        => array(),
				'apply_tags_cancelled'            => array(),
				'apply_tags_expired'              => array(),
				'apply_tags_payment_failed'       => array(),
				'apply_tags_pending_cancellation' => array(),
			);
		}

		?>

		<div id="wp-fusion-settings" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'WP Fusion Settings', 'wp-fusion' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">

				<span class="description">
					<?php
					// translators: %1$s: opening link tag, %2$s: closing link tag.
					printf( esc_html__( 'For more information on these settings, %1$ssee our documentation%2$s.', 'wp-fusion' ), '<a href="https://wpfusion.com/documentation/membership/paid-memberships-pro/" target="_blank">', '</a>' );
					?>
				</span>

				<table class="form-table" id="wp_fusion_tab">
					<tbody>
					<tr>
						<th scope="row" valign="top"><label><?php esc_html_e( 'Apply Tags', 'wp-fusion' ); ?>:</label></th>
						<td>
							<?php
							wpf_render_tag_multiselect(
								array(
									'setting'   => $settings['apply_tags'],
									'meta_name' => 'wpf-settings',
									'field_id'  => 'apply_tags',
								)
							);
							?>
							<br/>
							<?php if ( 'yes' === get_pmpro_membership_level_meta( $level->id, 'pmprogl_enabled_for_level', true ) ) : ?>
								<small>
									<?php
									// translators: %s: CRM name.
									printf( esc_html__( 'These tags will be applied to the purchaser in %s upon purchasing this membership for another user. Tags for the gift recipient can be configured on the associated gift level.', 'wp-fusion' ), esc_html( wp_fusion()->crm->name ) );
									?>
								</small>
							<?php else : ?>
								<small>
									<?php
									// translators: %s: CRM name.
									printf( esc_html__( 'These tags will be applied to the customer in %s upon registering for this membership.', 'wp-fusion' ), esc_html( wp_fusion()->crm->name ) );
									?>
								</small>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label><?php esc_html_e( 'Remove Tags', 'wp-fusion' ); ?>:</label></th>
						<td>
							<input class="checkbox" type="checkbox" id="wpf-remove-tags" name="wpf-settings[remove_tags]"
								value="1" <?php echo checked( $settings['remove_tags'], 1, false ); ?> />
							<label for="wpf-remove-tags"><?php esc_html_e( 'Remove original tags (above) when the membership is cancelled or expires.', 'wp-fusion' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label><?php esc_html_e( 'Link with Tag', 'wp-fusion' ); ?>:</label></th>
						<td>
							<?php
							wpf_render_tag_multiselect(
								array(
									'setting'   => $settings['tag_link'],
									'meta_name' => 'wpf-settings',
									'field_id'  => 'tag_link',
									'limit'     => 1,
								)
							);
							?>
							<br/>
							<small>
								<?php
								// translators: %1$s: CRM name, %2$s: CRM name.
								printf( esc_html__( 'This tag will be applied in %1$s when a member is registered. Likewise, if this tag is applied to a user from within %2$s, they will be automatically enrolled in this membership. If the tag is removed they will be removed from the membership.', 'wp-fusion' ), esc_html( wp_fusion()->crm->name ), esc_html( wp_fusion()->crm->name ) );
								?>
							</small>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label><?php esc_html_e( 'Apply Tags - Cancelled', 'wp-fusion' ); ?>:</label></th>
						<td>
							<?php
							wpf_render_tag_multiselect(
								array(
									'setting'   => $settings['apply_tags_cancelled'],
									'meta_name' => 'wpf-settings',
									'field_id'  => 'apply_tags_cancelled',
								)
							);
							?>
							<br/>
							<small><?php esc_html_e( 'Apply these tags when a subscription is cancelled. Happens when an admin or user cancels a subscription, or if the payment gateway has canceled the subscription due to too many failed payments (will be removed if the membership is resumed).', 'wp-fusion' ); ?></small>
						</td>
					</tr>

					<?php if ( function_exists( 'pmproconpd_pmpro_change_level' ) ) : ?>

						<tr>
							<th scope="row" valign="top"><label><?php esc_html_e( 'Apply Tags - Pending Cancellation', 'wp-fusion' ); ?>:</label></th>
							<td>
								<?php
								wpf_render_tag_multiselect(
									array(
										'setting'   => $settings['apply_tags_pending_cancellation'],
										'meta_name' => 'wpf-settings',
										'field_id'  => 'apply_tags_pending_cancellation',
									)
								);
								?>
								<br/>
								<small><?php esc_html_e( 'Apply these tags when a subscription has been cancelled and there is still time remaining on the membership (via the <em>Cancel on Next Payment Date</em> extension).', 'wp-fusion' ); ?></small>
							</td>
						</tr>

					<?php endif; ?>

					<tr
					<?php
					if ( ! function_exists( 'pmpro_next_payment' ) ) {
						echo 'style="display:none;"';
					}
					?>
					>
						<th scope="row" valign="top"><label><?php esc_html_e( 'Apply Tags - Expired', 'wp-fusion' ); ?>:</label>
						</th>
						<td>
							<?php
							wpf_render_tag_multiselect(
								array(
									'setting'   => $settings['apply_tags_expired'],
									'meta_name' => 'wpf-settings',
									'field_id'  => 'apply_tags_expired',
								)
							);
							?>
							<br/>
							<small><?php esc_html_e( 'Apply these tags when a membership expires (will be removed if the membership is resumed).', 'wp-fusion' ); ?></small>

							<?php if ( function_exists( 'pmproconpd_pmpro_change_level' ) ) : ?>

								<p><small><?php esc_html_e( 'Note: With the Cancel on Next Payment Date addon active, no tags will immediately be applied or removed when a member cancels their subscription. Then the tags specified for "Apply Tags - Expired" will be applied when the member\'s access actually expires.', 'wp-fusion' ); ?></small></p>

							<?php endif; ?>
						</td>
					</tr>

					<tr
					<?php
					if ( ! function_exists( 'pmpro_next_payment' ) ) {
						echo 'style="display:none;"';
					}
					?>
					>
						<th scope="row" valign="top"><label><?php esc_html_e( 'Apply Tags - Payment Failed', 'wp-fusion' ); ?>:</label></th>
						<td>
							<?php
							wpf_render_tag_multiselect(
								array(
									'setting'   => $settings['apply_tags_payment_failed'],
									'meta_name' => 'wpf-settings',
									'field_id'  => 'apply_tags_payment_failed',
								)
							);
							?>
							<br/>
							<small><?php esc_html_e( 'Apply these tags when a recurring payment fails (will be removed if a payment is made).', 'wp-fusion' ); ?></small>
						</td>
					</tr>
					</tbody>

					<?php
					// CRM field mapping.
					$crm_fields = wp_fusion()->settings->get_crm_fields_flat();
					$crm_fields = array( '' => '- None -' ) + $crm_fields;

					$level_fields = $this->get_membership_level_crm_fields();

					echo '<tr class="header"><td colspan="2"><h3>' . esc_html__( 'Level-Specific Field Mapping', 'wp-fusion' ) . '</h3></td></tr>';
					echo '<tr><td colspan="2"><p>' . esc_html__( 'This allows you to map level-specific fields to your CRM. For example, if you have a field in your CRM called "Membership Level A Expiration Date", you can map the expiration date for this specific level to that field.', 'wp-fusion' ) . '</p></td></tr>';

					foreach ( $level_fields as $field_id => $field_data ) {
						$field_label = $field_data['label'];
						$field_type  = $field_data['type'];

						// Get the saved value.
						$meta_key = $field_id . '_' . $level->id;
						$value    = isset( $crm_fields[ $meta_key ] ) ? $crm_fields[ $meta_key ] : '';

						echo '<tr>';
						echo '<th scope="row"><label for="' . esc_attr( $meta_key ) . '">' . esc_html( $field_label ) . ':</label></th>';
						echo '<td>';
						echo '<select id="' . esc_attr( $meta_key ) . '" name="wpf_settings_pmpro_crm_fields[' . esc_attr( $meta_key ) . '][crm_field]" class="select4-crm-field" data-placeholder="' . esc_attr__( 'Select a field', 'wp-fusion' ) . '" data-type="' . esc_attr( $field_type ) . '">';

						foreach ( $crm_fields as $key => $label ) {
							echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key, $value, false ) . '>' . esc_html( $label ) . '</option>';
						}

						echo '</select>';
						echo '</td>';
						echo '</tr>';
					}
					?>

				</table>

				<?php
				/**
				 * Fires after the WP Fusion settings section on the PMPro membership level edit screen.
				 *
				 * @since 3.45.3
				 *
				 * @param object $level The Membership Level object.
				 */
				do_action( 'pmpro_membership_level_after_wp_fusion_settings', $level );
				?>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end wp-fusion-settings -->

		<?php
	}

	/**
	 * Saves changes to membership level settings.
	 *
	 * @since 3.45.3
	 *
	 * @param int $saveid The level ID.
	 */
	public function save_level_settings( $saveid ) {

		// Verify nonce for the main settings.
		if ( ! isset( $_POST['pmpro_membershiplevels_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['pmpro_membershiplevels_nonce'] ), 'save_membershiplevel' ) ) {
			return;
		}

		// Save main settings.
		if ( isset( $_POST['wpf-settings'] ) ) {
			update_option( 'wpf_pmp_' . $saveid, wpf_clean( wp_unslash( $_POST['wpf-settings'] ) ), false );
		} else {
			delete_option( 'wpf_pmp_' . $saveid );
		}

		// Save CRM field mappings.
		if ( isset( $_POST['wpf_settings_pmpro_crm_fields'] ) ) {
			$data = wpf_clean( wp_unslash( $_POST['wpf_settings_pmpro_crm_fields'] ) );

			if ( ! empty( $data ) ) {
				// Save any CRM fields to the field mapping.
				$contact_fields = wpf_get_option( 'contact_fields', array() );

				foreach ( $data as $key => $value ) {

					if ( ! empty( $value['crm_field'] ) ) {

						// Get the base field name without the level ID.
						$field_parts = explode( '_', $key );
						$level_id    = array_pop( $field_parts );
						$base_field  = implode( '_', $field_parts );

						// Get the field type from our defined fields.
						$crm_fields = $this->get_membership_level_crm_fields();
						$field_type = isset( $crm_fields[ $base_field ] ) ? $crm_fields[ $base_field ]['type'] : 'text';

						$contact_fields[ $key ] = array(
							'crm_field' => $value['crm_field'],
							'active'    => true,
							'type'      => $field_type,
						);
					} elseif ( isset( $contact_fields[ $key ] ) ) {

						// If the setting has been removed we can un-list it from the main Contact Fields list.
						unset( $contact_fields[ $key ] );
					}
				}

				wp_fusion()->settings->set( 'contact_fields', $contact_fields );
			}
		}
	}

	/**
	 * Adds options to PMP discount code settings.
	 *
	 * @since 3.45.3
	 *
	 * @param object $edit The discount code object.
	 */
	public function discount_code_settings( $edit ) {

		$settings = get_option( 'wpf_pmp_discount_' . $edit->id );

		if ( empty( $settings ) ) {
			$settings = array( 'apply_tags' => array() );
		}

		?>
		<h3><?php esc_html_e( 'WP Fusion Settings', 'wp-fusion' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row" valign="top"><label><?php esc_html_e( 'Apply Tags', 'wp-fusion' ); ?>:</label></th>
				<td>
					<?php
					wpf_render_tag_multiselect(
						array(
							'setting'   => $settings['apply_tags'],
							'meta_name' => 'wpf-settings',
							'field_id'  => 'apply_tags',
						)
					);
					?>
					<br/>
					<small>Apply the selected tags in <?php echo esc_html( wp_fusion()->crm->name ); ?> when the coupon is used.</small>
				</td>
			</tr>
		</table>

		<?php
	}

	/**
	 * Saves changes to discount code settings.
	 *
	 * @since 3.45.3
	 *
	 * @param int $saveid The discount code ID.
	 */
	public function save_discount_code_settings( $saveid ) {

		if ( isset( $_POST['wpf-settings'] ) ) {
			update_option( 'wpf_pmp_discount_' . $saveid, wpf_clean( wp_unslash( $_POST['wpf-settings'] ) ) );
		}
	}
}
