<?php
/**
 * Add TOS field to advanced settings.
 *
 * @since 3.2
 *
 * @param array $settings The array of settings.
 * @return array The array of settings with the TOS field added.
 */
function pmpro_add_tos_field_to_advanced_settings( $settings ) {
	$settings['tospage'] = array(
		'field_name' => 'tospage',
		'field_type' => 'callback',
		'label' => __( 'Require Terms of Service on signups?', 'paid-memberships-pro' ),
		'description' => __( 'If yes, create a WordPress page containing your TOS agreement and assign it using the dropdown above.', 'paid-memberships-pro' ),
		'callback' => 'pmpro_tos_advanced_settings_callback',
	);
	return $settings;
}
add_filter( 'pmpro_custom_advanced_settings', 'pmpro_add_tos_field_to_advanced_settings' );

/**
 * Callback function to display the TOS field on the advanced settings page.
 *
 * @since 3.2
 */
function pmpro_tos_advanced_settings_callback() {
	$tospage = get_option( 'pmpro_tospage' );
	wp_dropdown_pages(
		array(
			'name' => 'tospage',
			'show_option_none' => 'No',
			'selected' => esc_html( $tospage )
		)
	);
}

/**
 * Add a TOS consent checkbox to the checkout page.
 *
 * @since 3.2
 */
function pmpro_show_tos_at_checkout() {
	global $pmpro_review;

	// We are showing the TOS checkbox on the checkout page. Unhook pmpro_unhook_pmpro_show_tos_at_checkout().
	remove_action( 'pmpro_checkout_after_tos_fields', 'pmpro_unhook_pmpro_show_tos_at_checkout' );

	// If checkout is being reviewed, don't show the TOS checkbox.
	if ( $pmpro_review ) {
		do_action_deprecated( 'pmpro_checkout_after_tos_fields', array(), '3.2' );
		return;
	}

	// Check if we have a TOS page. If not, don't show the TOS checkbox.
	$tospage = get_option( "pmpro_tospage" );
	if ( $tospage ) {
		$tospage = get_post( $tospage );
	}
	if ( empty( $tospage ) ) {
		do_action_deprecated( 'pmpro_checkout_after_tos_fields', array(), '3.2' );
		return;
	}

	// Show the TOS checkbox.
	?>
	<div id="pmpro_tos_fields" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset', 'pmpro_tos_fields' ) ); ?>">
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
			<?php
				if ( isset( $_REQUEST['tos'] ) ) {
					$tos = intval( $_REQUEST['tos'] );
				} else {
					$tos = "";
				}
			?>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-checkbox pmpro_form_field-required' ) ); ?>">
				<label class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label pmpro_clickable', 'tos' ) ); ?>" for="tos">
					<input type="checkbox" name="tos" value="1" id="tos" <?php checked( 1, $tos ); ?> class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-checkbox pmpro_form_input-required', 'tos' ) ); ?>" />
					<?php
						/* translators: 1: TOS page URL, 2: TOS page title. */
						$tos_label = sprintf( __( 'I agree to the <a href="%1$s" target="_blank">%2$s</a>', 'paid-memberships-pro' ), esc_url( get_permalink( $tospage->ID ) ), esc_html( $tospage->post_title ) );
						/**
						 * Filter the Terms of Service field label.
						 *
						 * @since 3.1
						 *
						 * @param string $tos_label The field label.
						 * @param object $tospage The Terms of Service page object.
						 * @return string The filtered field label.
						 */
						$tos_label = apply_filters( 'pmpro_tos_field_label', $tos_label, $tospage );
						echo wp_kses_post( $tos_label );
					?>
				</label>
			</div> <!-- end pmpro_form_field-tos -->
			<?php
				/**
				 * Allow adding text or more checkboxes after the Tos checkbox
				 * This is NOT intended to support multiple Tos checkboxes
				 *
				 * @since 2.8
				 */
				do_action_deprecated( 'pmpro_checkout_after_tos', array(), '3.2' );
			?>
		</div> <!-- end pmpro_form_fields -->
	</div> <!-- end pmpro_tos_fields -->
	<?php
	do_action_deprecated( 'pmpro_checkout_after_tos_fields', array(), '3.2' );

}
add_action( 'pmpro_checkout_before_submit_button', 'pmpro_show_tos_at_checkout', 5 ); // 5 to show before reCAPTCHA.

/**
 * If a pre-3.2 checkout template is being used, then the TOS checkbox will be displayed in the checkout template.
 *
 * This function will be unhooked at the top of pmpro_checkout_before_submit_button(), so this will only prevent pmpro_checkout_before_submit_button() from generating TOS fields if
 * the `pmpro_checkout_after_tos_fields` hook is called from the checkout preheader before `pmpro_checkout_before_submit_button` is run.
 *
 * Note: This function will be removed in a future version of Paid Memberships Pro once pre-3.2 templates have had time to phase out.
 *
 * @since 3.2
 */
function pmpro_unhook_pmpro_show_tos_at_checkout() {
	remove_action( 'pmpro_checkout_before_submit_button', 'pmpro_show_tos_at_checkout', 5 );
}
add_action( 'pmpro_checkout_after_tos_fields', 'pmpro_unhook_pmpro_show_tos_at_checkout' );

/**
 * In case a pre-3.2 checkout template is being used, we need to set the $tospage global in order to display the TOS checkbox.
 *
 * Note: This function will be removed in a future version of Paid Memberships Pro once pre-3.2 templates have had time to phase out.
 *
 * @since 3.2
 */
function pmpro_set_tospage_global() {
	global $tospage;
	$tospage = get_option( "pmpro_tospage" );
	if ( $tospage ) {
		$tospage = get_post( $tospage );
	}
}
add_action( 'pmpro_checkout_preheader', 'pmpro_set_tospage_global' );


/**
 * Validate the TOS checkbox at checkout.
 *
 * @since 3.2
 *
 * @param bool $pmpro_continue_registration Whether or not to continue with registration.
 */
function pmpro_validate_tos_at_checkout( $pmpro_continue_registration ) {
	global $pmpro_error_fields;

	// If checkout is already halted, don't check the TOS.
	if ( ! $pmpro_continue_registration ) {
		return $pmpro_continue_registration;
	}

	// Check if we have a TOS page. If not, don't validate the TOS checkbox.
	$tospage = get_option( "pmpro_tospage" );
	if ( $tospage ) {
		$tospage = get_post( $tospage );
	}
	if ( empty( $tospage ) ) {
		return $pmpro_continue_registration;
	}

	// If the TOS checkbox is not checked, halt registration.
	if ( ! isset( $_REQUEST['tos'] ) || empty( $_REQUEST['tos'] ) ) {
		$pmpro_continue_registration = false;
		$pmpro_error_fields[] = 'tospage';
		/* translators: 1: TOS page title. */
		pmpro_setMessage( sprintf( __( "Please check the box to agree to the %s.", 'paid-memberships-pro' ), $tospage->post_title ), "pmpro_error" );
	}

	return $pmpro_continue_registration;
}
add_filter( 'pmpro_checkout_user_creation_checks', 'pmpro_validate_tos_at_checkout' );
add_filter( 'pmpro_checkout_order_creation_checks', 'pmpro_validate_tos_at_checkout' );

/**
 * Update TOS consent log after checkout.
 * @since 1.9.5
 */
function pmpro_after_checkout_update_consent( $user_id, $order ) {
	if( !empty( $_REQUEST['tos'] ) ) {
		$tospage_id = get_option( 'pmpro_tospage' );
		pmpro_save_consent( $user_id, $tospage_id, NULL, $order->id );
	} elseif ( !empty( $_SESSION['tos'] ) ) {
		// PayPal Express and others might save tos info into a session variable
		$tospage_id = $_SESSION['tos']['post_id'];
		$tospage_modified = $_SESSION['tos']['post_modified'];
		pmpro_save_consent( $user_id, $tospage_id, $tospage_modified, $order->id );
		unset( $_SESSION['tos'] );
	}
}
add_action( 'pmpro_after_checkout', 'pmpro_after_checkout_update_consent', 10, 2 );
add_action( 'pmpro_before_send_to_paypal_standard', 'pmpro_after_checkout_update_consent', 10, 2);
add_action( 'pmpro_before_send_to_twocheckout', 'pmpro_after_checkout_update_consent', 10, 2);
add_action( 'pmpro_before_send_to_payfast', 'pmpro_after_checkout_update_consent', 10, 2 );

/**
 * Save a TOS consent timestamp to user meta.
 * @since 1.9.5
 */
function pmpro_save_consent( $user_id = NULL, $post_id = NULL, $post_modified = NULL, $order_id = NULL ) {
	// Default to current user.
	if( empty( $user_id ) ) {
		global $current_user;
		$user_id = $current_user->ID;
	}

	if( empty( $user_id ) ) {
		return false;
	}

	// Default to the TOS post chosen on the advanced settings page
	if( empty( $post_id ) ) {
		$post_id = get_option( 'pmpro_tospage' );
	}

	if( empty( $post_id ) ) {
		return false;
	}

	$post = get_post( $post_id );

	if( empty( $post_modified ) ) {
		$post_modified = $post->post_modified;
	}

	$log = pmpro_get_consent_log( $user_id );
	$log[] = array(
		'user_id' => $user_id,
		'post_id' => $post_id,
		'post_modified' => $post_modified,
		'order_id' => $order_id,
		'consented' => true,
		'timestamp' => current_time( 'timestamp' ),
	);

	update_user_meta( $user_id, 'pmpro_consent_log', $log );
	return true;
}

/**
 * Get the TOS consent log from user meta.
 * @since  1.9.5
 */
function pmpro_get_consent_log( $user_id = NULL, $reversed = true ) {
	// Default to current user.
	if( empty( $user_id ) ) {
		global $current_user;
		$user_id = $current_user->ID;
	}

	if( empty( $user_id ) ) {
		return false;
	}

	$log = get_user_meta( $user_id, 'pmpro_consent_log', true );

	// Default log.
	if( empty( $log ) ) {
		$log = array();
	}

	if( $reversed ) {
		$log = array_reverse( $log );
	}

	return $log;
}

/**
 * Convert a consent entry into a English sentence.
 * @since  1.9.5
 */
function pmpro_consent_to_text( $entry ) {
	// Check for bad data. Shouldn't happen in practice.
	if ( empty( $entry ) || empty( $entry['user_id'] ) ) {		
		return '';
	}
	
	$user = get_userdata( $entry['user_id'] );
	$post = get_post( $entry['post_id'] );

	/* translators: 1: User display name, 2: TOS post title, 3: TOS post ID, 4: TOS post modified date, 5: Consent timestamp date. */
	$s = sprintf( __( '%1$s agreed to %2$s (ID #%3$d, last modified %4$s) on %5$s.', 'paid-memberships-pro' ),
				  $user->display_name,
				  $post->post_title,
				  $post->ID,
				  $entry['post_modified'],
				  date( get_option( 'date_format' ), $entry['timestamp'] ) );

	if( !pmpro_is_consent_current( $entry ) ) {
		$s .= ' ' . __( 'That post has since been updated.', 'paid-memberships-pro' );
	}

	return $s;
}

/**
 * Check if a consent entry is current.
 * @since  1.9.5
 */
function pmpro_is_consent_current( $entry ) {
	$post = get_post( $entry['post_id'] );
	if( !empty( $post ) && !empty( $post->post_modified ) && $post->post_modified == $entry['post_modified'] ) {
		return true;
	}
	return false;
}

/**
 * Show TOS log on the View Order page.
 *
 * @since 3.6.1
 *
 * @param MemberOrder $order The order object being viewed.
 */
function pmpro_show_tos_log_on_view_order_page( $order ) {
	// Return early if no TOS page is set.
	if ( empty( get_option( 'pmpro_tospage' ) ) ) {
		return;
	}

	$consent_entry = pmpro_get_consent_log_entry_for_order( $order );
	if ( ! empty( $consent_entry ) ) {
		?>
		<div id="pmpro_order-tos-consent" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'TOS Consent', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<?php
					echo esc_html( pmpro_consent_to_text( $consent_entry ) );
				?>
			</div>
		</div>
		<?php
	}
}
add_action( 'pmpro_after_order_view_main', 'pmpro_show_tos_log_on_view_order_page' );

/**
 * Show TOS log on the Edit Order page.
 *
 * @since 3.2
 * 
 * @param MemberOrder $order The order object being edited.
 */
function pmpro_show_tos_log_on_edit_order_page( $order ) {
	// Return early if no TOS page is set.
	if ( empty( get_option( 'pmpro_tospage' ) ) ) {
		return;
	}

	$consent_entry = pmpro_get_consent_log_entry_for_order( $order );
	if ( ! empty( $consent_entry ) ) {
		?>
		<tr>
			<th scope="row" valign="top"><label for="tos_consent"><?php esc_html_e( 'TOS Consent', 'paid-memberships-pro' ); ?></label></th>
			<td id="tos_consent">
				<?php
					echo esc_html( pmpro_consent_to_text( $consent_entry ) );
				?>
			</td>
		</tr>
		<?php
	}
}
add_action( 'pmpro_after_order_settings', 'pmpro_show_tos_log_on_edit_order_page' );

/**
 * Helper function to get the TOS concent log entry for an order.
 *
 * @since 3.2
 *
 * @param MemberOrder $order The order object.
 * @return array|bool The TOS consent log entry for the order, or false if not found.
 */
function pmpro_get_consent_log_entry_for_order( $order ) {
	$consent_log = pmpro_get_consent_log( $order->user_id );
	if( !empty( $consent_log ) ) {
		foreach( $consent_log as $entry ) {
			if( !empty( $entry['order_id'] ) && $entry['order_id'] == $order->id ) {
				return $entry;
			}
		}
	}

	return false;
}

/**
 * Add TOS consent log entry to the order CSV export.
 *
 * @since 3.2
 *
 * @param array $extra_columns The extra columns to add to the CSV export as $heading => $callback.
 */
function pmpro_add_tos_consent_log_entry_to_order_csv_export( $extra_columns ) {
	$extra_columns['tos_consent_post_id'] = 'pmpro_get_tos_consent_log_entry_post_id_for_order_csv_export';
	$extra_columns['tos_consent_post_modified'] = 'pmpro_get_tos_consent_log_entry_post_modified_for_order_csv_export';
	return $extra_columns;
}
add_filter( 'pmpro_orders_csv_extra_columns', 'pmpro_add_tos_consent_log_entry_to_order_csv_export' );

/**
 * Callback function to get the TOS consent log entry post ID for the order CSV export.
 *
 * @since 3.2
 *
 * @param MemberOrder $order The order object.
 */
function pmpro_get_tos_consent_log_entry_post_id_for_order_csv_export( $order ) {
	$entry = pmpro_get_consent_log_entry_for_order( $order );
	if( !empty( $entry ) ) {
		return $entry['post_id'];
	}
	return '';
}

/**
 * Callback function to get the TOS consent log entry post modified date for the order CSV export.
 *
 * @since 3.2
 *
 * @param MemberOrder $order The order object.
 */
function pmpro_get_tos_consent_log_entry_post_modified_for_order_csv_export( $order ) {
	$entry = pmpro_get_consent_log_entry_for_order( $order );
	if( !empty( $entry ) ) {
		return $entry['post_modified'];
	}
	return '';
}

/**
 * Add the re-acceptance meta box to the Terms of Service page.
 *
 * @since 3.9
 *
 * @param WP_Post $post The post being edited.
 */
function pmpro_tos_reaccept_add_metabox( $post ) {
	// Only show on the page set as the Terms of Service page.
	$tospage = get_option( 'pmpro_tospage' );
	if ( empty( $tospage ) || $post->ID != $tospage ) {
		return;
	}

	add_meta_box(
		'pmpro_tos_reaccept',
		__( 'Terms of Service', 'paid-memberships-pro' ),
		'pmpro_tos_reaccept_metabox_html',
		'page',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes_page', 'pmpro_tos_reaccept_add_metabox' );

/**
 * Display the re-acceptance checkbox in the meta box.
 *
 * @since 3.9
 *
 * @param WP_Post $post The post being edited.
 */
function pmpro_tos_reaccept_metabox_html( $post ) {
	wp_nonce_field( 'pmpro_tos_reaccept_metabox', 'pmpro_tos_reaccept_metabox_nonce' );
	?>
	<input type="hidden" name="pmpro_tos_reaccept_metabox_present" value="1" />
	<p>
		<label>
			<input type="checkbox" name="pmpro_tos_reaccept" value="1" <?php checked( get_option( 'pmpro_tos_reaccept' ), $post->ID ); ?> />
			<?php esc_html_e( 'Require all members to re-accept this Terms of Service.', 'paid-memberships-pro' ); ?>
		</label>
	</p>
	<p class="description">
		<?php esc_html_e( 'Members who have not accepted the current version will see a notice asking them to accept it again. Uncheck this to stop asking.', 'paid-memberships-pro' ); ?>
	</p>
	<?php
}

/**
 * Save the re-acceptance checkbox when the Terms of Service page is saved.
 *
 * @since 3.9
 *
 * @param int $post_id The post being saved.
 */
function pmpro_tos_reaccept_save_metabox( $post_id ) {
	// The meta box was not shown, so there is nothing to save.
	if ( empty( $_POST['pmpro_tos_reaccept_metabox_present'] ) ) {
		return;
	}

	if ( empty( $_POST['pmpro_tos_reaccept_metabox_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['pmpro_tos_reaccept_metabox_nonce'] ), 'pmpro_tos_reaccept_metabox' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_page', $post_id ) ) {
		return;
	}

	// Only track the page that is currently set as the Terms of Service page.
	$tospage = get_option( 'pmpro_tospage' );
	if ( empty( $tospage ) || $post_id != $tospage ) {
		return;
	}

	if ( ! empty( $_POST['pmpro_tos_reaccept'] ) ) {
		update_option( 'pmpro_tos_reaccept', $tospage, false );
	} else {
		delete_option( 'pmpro_tos_reaccept' );
	}
}
add_action( 'save_post_page', 'pmpro_tos_reaccept_save_metabox' );

/**
 * Check if members are being asked to re-accept the Terms of Service.
 *
 * @since 3.9
 *
 * @return int|false The Terms of Service page ID, or false if re-acceptance is not required.
 */
function pmpro_is_tos_reaccept_required() {
	$tospage = get_option( 'pmpro_tospage' );
	if ( empty( $tospage ) ) {
		return false;
	}

	// The setting stores the page it applies to, so switching the TOS page turns this off.
	$reaccept = get_option( 'pmpro_tos_reaccept' );
	if ( empty( $reaccept ) || $reaccept != $tospage ) {
		return false;
	}

	return intval( $tospage );
}

/**
 * Check if a member still needs to accept the current Terms of Service.
 *
 * @since 3.9
 *
 * @param int $user_id Optional. The user ID to check. Defaults to the current user.
 * @return bool True if the member should be asked to accept the Terms of Service again.
 */
function pmpro_user_needs_tos_reaccept( $user_id = null ) {
	$tospage_id = pmpro_is_tos_reaccept_required();
	if ( empty( $tospage_id ) ) {
		return false;
	}

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	if ( empty( $user_id ) ) {
		return false;
	}

	// If any consent entry matches the current version of the page, they are up to date.
	$consent_log = pmpro_get_consent_log( $user_id );
	if ( ! empty( $consent_log ) ) {
		foreach ( $consent_log as $entry ) {
			if ( empty( $entry['post_id'] ) || $entry['post_id'] != $tospage_id || empty( $entry['post_modified'] ) ) {
				continue;
			}

			if ( pmpro_is_consent_current( $entry ) ) {
				return false;
			}
		}
	}

	return true;
}

/**
 * Show a notice asking the member to accept the Terms of Service again.
 *
 * @since 3.9
 */
function pmpro_tos_reaccept_banner() {
	global $pmpro_tos_reaccept_banner_shown;

	// Don't show the notice in the admin or to logged out visitors.
	if ( is_admin() || ! is_user_logged_in() || ! pmpro_user_needs_tos_reaccept() ) {
		return;
	}

	$tospage_id = pmpro_is_tos_reaccept_required();
	$tospage    = get_post( $tospage_id );
	if ( empty( $tospage ) ) {
		return;
	}

	$pmpro_tos_reaccept_banner_shown = true;
	?>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_tos_reaccept_banner', 'pmpro_tos_reaccept_banner' ) ); ?>" role="region" aria-label="<?php esc_attr_e( 'Terms of Service', 'paid-memberships-pro' ); ?>">
		<?php
			/* translators: %s: Terms of Service page title. */
			$message  = sprintf( __( 'Our %s has been updated. Please review it and accept it to continue.', 'paid-memberships-pro' ), esc_html( $tospage->post_title ) );
			$message .= ' ' . sprintf( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( get_permalink( $tospage->ID ) ), esc_html__( 'View the Terms of Service', 'paid-memberships-pro' ) );
		?>
		<p><?php echo wp_kses_post( $message ); ?></p>
		<form method="post">
			<input type="hidden" name="pmpro_tos_reaccept_action" value="1" />
			<?php wp_nonce_field( 'pmpro_tos_reaccept_' . get_current_user_id(), 'pmpro_tos_reaccept_nonce' ); ?>
			<button type="submit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_tos_reaccept_button', 'pmpro_tos_reaccept_button' ) ); ?>">
				<?php
					/* translators: %s: Terms of Service page title. */
					echo esc_html( sprintf( __( 'I agree to the %s', 'paid-memberships-pro' ), $tospage->post_title ) );
				?>
			</button>
		</form>
	</div> <!-- end pmpro_tos_reaccept_banner -->
	<?php
}
add_action( 'wp_body_open', 'pmpro_tos_reaccept_banner' );

/**
 * Show the re-acceptance notice in the footer for themes that don't call wp_body_open().
 *
 * @since 3.9
 */
function pmpro_tos_reaccept_banner_fallback() {
	global $pmpro_tos_reaccept_banner_shown;

	// The notice already rendered in the body, so don't show it twice.
	if ( ! empty( $pmpro_tos_reaccept_banner_shown ) ) {
		return;
	}

	pmpro_tos_reaccept_banner();
}
add_action( 'wp_footer', 'pmpro_tos_reaccept_banner_fallback', 5 );

/**
 * Log consent when a member accepts the Terms of Service from the re-acceptance notice.
 *
 * @since 3.9
 */
function pmpro_tos_reaccept_handle_acceptance() {
	if ( is_admin() || empty( $_POST['pmpro_tos_reaccept_action'] ) || ! is_user_logged_in() ) {
		return;
	}

	$user_id = get_current_user_id();

	if ( empty( $_POST['pmpro_tos_reaccept_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['pmpro_tos_reaccept_nonce'] ), 'pmpro_tos_reaccept_' . $user_id ) ) {
		return;
	}

	$tospage_id = pmpro_is_tos_reaccept_required();
	if ( empty( $tospage_id ) ) {
		return;
	}

	// Log consent only if the member has not accepted this version yet,
	// so a double submit does not create duplicate consent entries.
	if ( pmpro_user_needs_tos_reaccept( $user_id ) ) {
		pmpro_save_consent( $user_id, $tospage_id );
	}

	// Redirect back to the same page so a refresh doesn't submit the form again.
	if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
		wp_safe_redirect( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
		exit;
	}
}
add_action( 'template_redirect', 'pmpro_tos_reaccept_handle_acceptance' );
