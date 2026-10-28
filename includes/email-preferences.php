<?php
/**
 * Email preferences.
 *
 * Members can opt out of optional emails by category. Categories are registered with the
 * pmpro_email_categories filter, and an email template joins a category by returning its slug
 * from PMPro_Email_Template::get_email_category(). Emails without a category are always sent.
 *
 * @since TBD
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get all registered email categories.
 *
 * @since TBD
 *
 * @return array Email categories (slug => array( 'label' => string, 'description' => string )).
 */
function pmpro_get_email_categories() {
	/**
	 * Register categories of optional emails that members can opt out of.
	 *
	 * Register a category here when your plugin sends a new type of optional email.
	 * If your email fits a category that is already registered, use that category instead.
	 *
	 * @since TBD
	 *
	 * @param array $email_categories Email categories (slug => array( 'label' => string, 'description' => string )).
	 */
	$email_categories = apply_filters( 'pmpro_email_categories', array() );

	// 'all' is reserved for the "all optional emails" setting.
	unset( $email_categories['all'] );

	return $email_categories;
}

/**
 * Register the email categories used by core and shared by Add Ons.
 *
 * @since TBD
 *
 * @param array $email_categories Email categories (slug => array( 'label' => string, 'description' => string )).
 * @return array
 */
function pmpro_email_categories_core( $email_categories ) {
	$email_categories['expiration_reminders'] = array(
		'label'       => __( 'Membership expiration reminders', 'paid-memberships-pro' ),
		'description' => __( 'Reminders before your membership ends.', 'paid-memberships-pro' ),
	);
	$email_categories['promotional'] = array(
		'label'       => __( 'Offers and promotions', 'paid-memberships-pro' ),
		'description' => __( 'Special offers, reminders about unfinished checkouts, and other promotional emails.', 'paid-memberships-pro' ),
	);
	return $email_categories;
}
add_filter( 'pmpro_email_categories', 'pmpro_email_categories_core' );

/**
 * Get the email category for an email template.
 *
 * @since TBD
 *
 * @param string $template_slug The email template slug.
 * @return string The category slug, or an empty string if members can't opt out of this email.
 */
function pmpro_get_email_template_category( $template_slug ) {
	if ( empty( $template_slug ) || ! is_string( $template_slug ) ) {
		return '';
	}

	$template_class = PMPro_Email_Template::get_email_template( $template_slug );
	if ( empty( $template_class ) || ! class_exists( $template_class ) ) {
		return '';
	}

	// Ignore categories that aren't registered so that members can always see what they opted out of.
	$category         = $template_class::get_email_category();
	$email_categories = pmpro_get_email_categories();
	return ( is_string( $category ) && isset( $email_categories[ $category ] ) ) ? $category : '';
}

/**
 * Get the email categories that members can opt out of right now.
 *
 * A category is only shown to members when at least one enabled email template uses it.
 *
 * @since TBD
 *
 * @return array Email categories (slug => array( 'label' => string, 'description' => string, 'templates' => array of template names )).
 */
function pmpro_get_active_email_categories() {
	$email_categories  = pmpro_get_email_categories();
	$active_categories = array();
	foreach ( PMPro_Email_Template::get_all_email_templates() as $template_slug => $template_class ) {
		if ( empty( $template_class ) || ! class_exists( $template_class ) ) {
			continue;
		}

		$category = $template_class::get_email_category();
		if ( ! is_string( $category ) || ! isset( $email_categories[ $category ] ) ) {
			continue;
		}

		// Note option may have 'false' stored as a string.
		$template_disabled = get_option( 'pmpro_email_' . $template_slug . '_disabled' );
		if ( ! empty( $template_disabled ) && 'false' !== $template_disabled ) {
			continue;
		}

		$active_categories[ $category ][] = $template_class::get_template_name();
	}

	// Keep the order that categories were registered in.
	$return = array();
	foreach ( $email_categories as $category => $email_category ) {
		if ( isset( $active_categories[ $category ] ) ) {
			$email_category['templates'] = $active_categories[ $category ];
			$return[ $category ]         = $email_category;
		}
	}
	return $return;
}

/**
 * Get a user's opt-out for an email category on this site.
 *
 * @since TBD
 *
 * @param int    $user_id  The user ID.
 * @param string $category The category slug, or 'all' for all optional emails.
 * @return array|false Array with 'time', 'source', and 'by' keys if the user opted out, false if not.
 */
function pmpro_get_user_email_opt_out( $user_id, $category ) {
	$opt_out = get_user_option( 'pmpro_email_opt_out_' . $category, $user_id );
	return is_array( $opt_out ) ? $opt_out : false;
}

/**
 * Check if a user opted out of an email category or all optional emails on this site.
 *
 * @since TBD
 *
 * @param int    $user_id  The user ID.
 * @param string $category The category slug.
 * @return bool
 */
function pmpro_is_user_opted_out_of_email_category( $user_id, $category ) {
	return pmpro_get_user_email_opt_out( $user_id, 'all' ) || pmpro_get_user_email_opt_out( $user_id, $category );
}

/**
 * Opt a user out of an email category or back in.
 *
 * Preferences are stored per site.
 *
 * @since TBD
 *
 * @param int    $user_id   The user ID.
 * @param string $category  The category slug, or 'all' for all optional emails.
 * @param bool   $opted_out True to opt out, false to opt back in.
 * @param string $source    Where the change was made: 'profile', 'link', 'one_click', or 'admin'.
 */
function pmpro_update_user_email_opt_out( $user_id, $category, $opted_out, $source ) {
	if ( ! $opted_out ) {
		delete_user_option( $user_id, 'pmpro_email_opt_out_' . $category );
		return;
	}

	// Keep the original date if the user already opted out.
	if ( pmpro_get_user_email_opt_out( $user_id, $category ) ) {
		return;
	}

	update_user_option(
		$user_id,
		'pmpro_email_opt_out_' . $category,
		array(
			'time'   => time(),
			'source' => $source,
			'by'     => get_current_user_id(),
		)
	);
}

/**
 * Describe how and when a user opted out, for admins.
 *
 * @since TBD
 *
 * @param array $opt_out An opt-out from pmpro_get_user_email_opt_out().
 * @return string
 */
function pmpro_get_email_opt_out_text( $opt_out ) {
	$source = isset( $opt_out['source'] ) ? $opt_out['source'] : '';
	switch ( $source ) {
		case 'one_click':
			$text = __( 'Unsubscribed with the unsubscribe button in their email app', 'paid-memberships-pro' );
			break;
		case 'link':
			$text = __( 'Unsubscribed by the member using an email link', 'paid-memberships-pro' );
			break;
		case 'admin':
			$admin = empty( $opt_out['by'] ) ? false : get_userdata( $opt_out['by'] );
			if ( ! empty( $admin ) ) {
				/* translators: %s: the name of the admin who made the change. */
				$text = sprintf( __( 'Unsubscribed by %s', 'paid-memberships-pro' ), $admin->display_name );
			} else {
				$text = __( 'Unsubscribed by an admin', 'paid-memberships-pro' );
			}
			break;
		default:
			$text = __( 'Unsubscribed by the member on their profile page', 'paid-memberships-pro' );
	}

	if ( ! empty( $opt_out['time'] ) ) {
		$text .= ', ' . date_i18n( get_option( 'date_format' ), $opt_out['time'] );
	}

	return $text;
}

/**
 * Get the key that signs a user's email preference links.
 *
 * The key is random and stored per user, so links keep working if the user changes
 * their email address and can't be guessed from anything else about the user.
 *
 * @since TBD
 *
 * @param int $user_id The user ID.
 * @return string
 */
function pmpro_get_email_preferences_key( $user_id ) {
	$key = get_user_meta( $user_id, 'pmpro_email_preferences_key', true );
	if ( empty( $key ) ) {
		$key = wp_generate_password( 32, false );
		update_user_meta( $user_id, 'pmpro_email_preferences_key', $key );
	}
	return $key;
}

/**
 * Get a signed link to a user's email preferences.
 *
 * @since TBD
 *
 * @param int    $user_id     The user ID.
 * @param string $unsubscribe Optional. A category slug, or 'all', to link to the unsubscribe page for.
 * @return string The URL, or an empty string if there is no Membership Account page.
 */
function pmpro_get_email_preferences_url( $user_id, $unsubscribe = '' ) {
	$account_url = pmpro_url( 'account' );
	if ( empty( $account_url ) ) {
		return '';
	}

	$args = array(
		'pmpro_email_user' => (int) $user_id,
		'pmpro_email_key'  => pmpro_get_email_preferences_key( $user_id ),
	);
	if ( ! empty( $unsubscribe ) ) {
		$args['pmpro_unsubscribe'] = $unsubscribe;
	}

	return add_query_arg( $args, $account_url );
}

/**
 * Check if the current request came from a signed email preferences link.
 *
 * @since TBD
 *
 * @return bool
 */
function pmpro_is_email_preferences_link_request() {
	return ! empty( $_REQUEST['pmpro_email_user'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check. The link's key is verified in pmpro_get_email_preferences_user().
}

/**
 * Get the user that a signed email preferences link is for.
 *
 * Signed links from emails identify the user without a login.
 *
 * @since TBD
 *
 * @return WP_User|false|null The user, false if the link is invalid, or null if this isn't a signed link request.
 */
function pmpro_get_email_preferences_user() {
	if ( ! pmpro_is_email_preferences_link_request() ) {
		return null;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Signed links are verified with the user's key below.
	$user = get_userdata( intval( $_REQUEST['pmpro_email_user'] ) );
	$key  = ( isset( $_REQUEST['pmpro_email_key'] ) && is_string( $_REQUEST['pmpro_email_key'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['pmpro_email_key'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	if ( empty( $user ) || empty( $key ) || ! hash_equals( pmpro_get_email_preferences_key( $user->ID ), $key ) ) {
		return false;
	}

	return $user;
}

/**
 * Get the category that the current request is unsubscribing from.
 *
 * @since TBD
 *
 * @return string A registered category slug, 'all', or an empty string.
 */
function pmpro_get_email_preferences_unsubscribe_category() {
	if ( empty( $_REQUEST['pmpro_unsubscribe'] ) || ! is_string( $_REQUEST['pmpro_unsubscribe'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only. Changes are verified separately.
		return '';
	}

	$category         = sanitize_key( wp_unslash( $_REQUEST['pmpro_unsubscribe'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only. Changes are verified separately.
	$email_categories = pmpro_get_email_categories();
	return ( 'all' === $category || isset( $email_categories[ $category ] ) ) ? $category : '';
}

/**
 * Get the {{ email_preferences }} email variable: links to unsubscribe from an optional email or manage preferences.
 *
 * @since TBD
 *
 * @param int    $user_id  The ID of the user receiving the email.
 * @param string $category The email's category slug.
 * @return string HTML.
 */
function pmpro_get_email_unsubscribe_footer( $user_id, $category ) {
	$email_categories = pmpro_get_email_categories();
	$unsubscribe_url  = pmpro_get_email_preferences_url( $user_id, $category );
	if ( empty( $unsubscribe_url ) || empty( $email_categories[ $category ] ) ) {
		return '';
	}

	return '<p>' . sprintf(
		/* translators: %s: email category name. */
		esc_html__( 'You\'re receiving this email because you\'re subscribed to %s emails.', 'paid-memberships-pro' ),
		'<strong>' . esc_html( $email_categories[ $category ]['label'] ) . '</strong>'
	) . ' <a href="' . esc_url( $unsubscribe_url ) . '">' . esc_html__( 'Unsubscribe from these emails', 'paid-memberships-pro' ) . '</a>'
	. ' &middot; <a href="' . esc_url( pmpro_get_email_preferences_url( $user_id ) ) . '">' . esc_html__( 'Manage email preferences', 'paid-memberships-pro' ) . '</a></p>';
}

/**
 * Handle one-click unsubscribes from email apps (RFC 8058).
 *
 * Email apps send a POST request with the body "List-Unsubscribe=One-Click" to the URL in the
 * List-Unsubscribe header. Visiting that URL in a browser shows a confirmation page instead,
 * so link scanners that open URLs can't unsubscribe anyone.
 *
 * @since TBD
 */
function pmpro_email_preferences_one_click_unsubscribe() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Email apps can't send a nonce. Signed links are verified with the user's key.
	if ( empty( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! isset( $_POST['List-Unsubscribe'] ) || 'One-Click' !== $_POST['List-Unsubscribe'] ) {
		return;
	}
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( ! pmpro_is_email_preferences_link_request() ) {
		return;
	}

	$user     = pmpro_get_email_preferences_user();
	$category = pmpro_get_email_preferences_unsubscribe_category();
	if ( empty( $user ) || empty( $category ) ) {
		status_header( 400 );
		exit;
	}

	pmpro_update_user_email_opt_out( $user->ID, $category, true, 'one_click' );
	status_header( 200 );
	exit;
}
add_action( 'init', 'pmpro_email_preferences_one_click_unsubscribe', 20 );

/**
 * Show email preferences on the frontend Member Profile Edit page.
 *
 * @since TBD
 *
 * @param WP_User $user The user.
 */
function pmpro_email_preferences_show_profile_fields( $user ) {
	$fields = pmpro_get_email_preferences_fields( $user );
	if ( empty( $fields ) ) {
		return;
	}
	?>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>
	<fieldset id="pmpro_form_fieldset-email-preferences" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset', 'pmpro_form_fieldset-email-preferences' ) ); ?>">
		<legend class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_legend' ) ); ?>">
			<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_heading pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Email Preferences', 'paid-memberships-pro' ); ?></h2>
		</legend>
		<input type="hidden" name="pmpro_email_preferences_fields" value="1" />
		<?php echo $fields; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped when built. ?>
	</fieldset>
	<?php
}
add_action( 'pmpro_show_user_profile', 'pmpro_email_preferences_show_profile_fields', 20 );

/**
 * Save email preferences from the frontend Member Profile Edit page.
 *
 * @since TBD
 *
 * @param int $user_id The user ID.
 */
function pmpro_email_preferences_save_profile_fields( $user_id ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- pmpro_member_profile_edit_form() verifies the nonce before running this hook.
	// Only save if the form included email preferences, so other profile saves don't clear them.
	if ( empty( $_POST['pmpro_email_preferences_fields'] ) ) {
		return;
	}

	pmpro_email_preferences_save_choices( $user_id, 'profile' );
	// phpcs:enable WordPress.Security.NonceVerification.Missing
}
add_action( 'pmpro_personal_options_update', 'pmpro_email_preferences_save_profile_fields' );

/**
 * Save a user's email preferences from the submitted checkboxes.
 *
 * Callers must verify the request first.
 *
 * @since TBD
 *
 * @param int    $user_id The user ID.
 * @param string $source  Where the change was made: 'profile', 'link', or 'admin'.
 */
function pmpro_email_preferences_save_choices( $user_id, $source ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Callers verify the request.
	$receive = ( isset( $_POST['pmpro_email_receive'] ) && is_array( $_POST['pmpro_email_receive'] ) ) ? array_map( 'sanitize_key', wp_unslash( $_POST['pmpro_email_receive'] ) ) : array();
	foreach ( array_keys( pmpro_get_active_email_categories() ) as $category ) {
		pmpro_update_user_email_opt_out( $user_id, $category, ! in_array( $category, $receive, true ), $source );
	}
	pmpro_update_user_email_opt_out( $user_id, 'all', ! empty( $_POST['pmpro_email_opt_out_all'] ), $source );
	// phpcs:enable WordPress.Security.NonceVerification.Missing
}

/**
 * Save changes from signed email preference links.
 *
 * Runs in the Membership Account page preheader.
 *
 * @since TBD
 */
function pmpro_email_preferences_process_form() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Email links can't include a nonce. Signed links are verified with the user's key.
	if ( empty( $_POST['pmpro_email_preferences_action'] ) || ! is_string( $_POST['pmpro_email_preferences_action'] ) ) {
		return;
	}

	$user = pmpro_get_email_preferences_user();
	if ( empty( $user ) ) {
		return;
	}

	$action = sanitize_key( wp_unslash( $_POST['pmpro_email_preferences_action'] ) );
	if ( 'save' === $action ) {
		pmpro_email_preferences_save_choices( $user->ID, 'link' );
		pmpro_setMessage( __( 'Your email preferences have been saved.', 'paid-memberships-pro' ), 'pmpro_success', true );
	} elseif ( 'unsubscribe' === $action || 'resubscribe' === $action ) {
		$category = pmpro_get_email_preferences_unsubscribe_category();
		if ( ! empty( $category ) ) {
			pmpro_update_user_email_opt_out( $user->ID, $category, 'unsubscribe' === $action, 'link' );
		}
		if ( ! empty( $category ) && 'resubscribe' === $action ) {
			$email_categories = pmpro_get_email_categories();
			if ( 'all' === $category ) {
				$message = __( 'You\'ve been resubscribed to optional emails.', 'paid-memberships-pro' );
			} else {
				/* translators: %s: email category name. */
				$message = sprintf( __( 'You\'ve been resubscribed to %s emails.', 'paid-memberships-pro' ), '<strong>' . esc_html( $email_categories[ $category ]['label'] ) . '</strong>' );
			}
			pmpro_setMessage( $message, 'pmpro_success', true );
		}
	}
	// phpcs:enable WordPress.Security.NonceVerification.Missing
}

/**
 * Get the email preference checkboxes for a user.
 *
 * @since TBD
 *
 * @param WP_User $user The user.
 * @return string HTML, or an empty string if there are no optional emails to choose from.
 */
function pmpro_get_email_preferences_fields( $user ) {
	$email_categories = pmpro_get_active_email_categories();
	if ( empty( $email_categories ) ) {
		return '';
	}

	ob_start();
	?>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields-description' ) ); ?>"><?php esc_html_e( 'Choose which optional emails you receive. We\'ll always email you about your account, payments, and membership changes.', 'paid-memberships-pro' ); ?></div>
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-checkbox', 'pmpro_email_opt_out_all' ) ); ?>">
			<label class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label pmpro_clickable' ) ); ?>" for="pmpro_email_opt_out_all">
				<input type="checkbox" name="pmpro_email_opt_out_all" id="pmpro_email_opt_out_all" value="1" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-checkbox' ) ); ?>" <?php checked( (bool) pmpro_get_user_email_opt_out( $user->ID, 'all' ) ); ?> />
				<?php esc_html_e( 'Don\'t send me any optional emails', 'paid-memberships-pro' ); ?>
			</label>
			<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_hint' ) ); ?>"><?php esc_html_e( 'Turns off everything below, including new types of optional emails added later.', 'paid-memberships-pro' ); ?></p>
		</div>
		<?php foreach ( $email_categories as $category => $email_category ) { ?>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-checkbox', 'pmpro_email_receive_' . $category ) ); ?>">
				<label class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label pmpro_clickable' ) ); ?>" for="pmpro_email_receive_<?php echo esc_attr( $category ); ?>">
					<input type="checkbox" name="pmpro_email_receive[]" id="pmpro_email_receive_<?php echo esc_attr( $category ); ?>" value="<?php echo esc_attr( $category ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-checkbox' ) ); ?>" <?php checked( ! pmpro_get_user_email_opt_out( $user->ID, $category ) ); ?> />
					<?php echo esc_html( $email_category['label'] ); ?>
				</label>
				<?php if ( ! empty( $email_category['description'] ) ) { ?>
					<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_hint' ) ); ?>"><?php echo esc_html( $email_category['description'] ); ?></p>
				<?php } ?>
			</div>
		<?php } ?>
	</div> <!-- end pmpro_form_fields -->
	<?php
	return ob_get_clean();
}

/**
 * Get the unsubscribe page that signed links in emails open.
 *
 * Unsubscribing takes a button press so that link scanners can't unsubscribe anyone.
 *
 * @since TBD
 *
 * @param WP_User $user     The user.
 * @param string  $category The category slug, or 'all'.
 * @return string HTML.
 */
function pmpro_get_email_preferences_unsubscribe_card( $user, $category ) {
	$email_categories = pmpro_get_email_categories();
	$is_all           = ( 'all' === $category );
	$opted_out        = (bool) pmpro_get_user_email_opt_out( $user->ID, $category );
	$category_label   = $is_all ? '' : '<strong>' . esc_html( $email_categories[ $category ]['label'] ) . '</strong>';

	ob_start();
	if ( ! $opted_out ) {
		// Show the result of resubscribing.
		pmpro_showMessage();
	} else {
		?>
		<div role="alert" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message pmpro_success', 'pmpro_success' ) ); ?>">
			<p>
				<?php
				if ( $is_all ) {
					esc_html_e( 'You\'ve been unsubscribed from all optional emails.', 'paid-memberships-pro' );
				} else {
					/* translators: %s: email category name. */
					echo wp_kses( sprintf( __( 'You\'ve been unsubscribed from %s emails.', 'paid-memberships-pro' ), $category_label ), array( 'strong' => array() ) );
				}
				?>
			</p>
		</div>
		<?php
	}
	?>
	<section id="pmpro_email_unsubscribe" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section', 'pmpro_email_unsubscribe' ) ); ?>">
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
			<form class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form', 'pmpro_form_email_unsubscribe' ) ); ?>" action="<?php echo esc_url( pmpro_get_email_preferences_url( $user->ID, $category ) ); ?>" method="post">
				<input type="hidden" name="pmpro_email_preferences_action" value="<?php echo $opted_out ? 'resubscribe' : 'unsubscribe'; ?>" />
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
					<?php if ( $opted_out ) { ?>
						<p><?php esc_html_e( 'You\'ll still get emails about your account, payments, and membership.', 'paid-memberships-pro' ); ?></p>
						<?php if ( ! $is_all && ! pmpro_get_user_email_opt_out( $user->ID, 'all' ) ) { ?>
							<p>
								<?php esc_html_e( 'Don\'t want any optional emails from us?', 'paid-memberships-pro' ); ?>
								<a href="<?php echo esc_url( pmpro_get_email_preferences_url( $user->ID, 'all' ) ); ?>"><?php esc_html_e( 'Unsubscribe from all optional emails', 'paid-memberships-pro' ); ?></a>.
							</p>
						<?php } ?>
						<p><?php esc_html_e( 'Unsubscribed by mistake?', 'paid-memberships-pro' ); ?></p>
					<?php } else { ?>
						<p>
							<?php
							if ( $is_all ) {
								esc_html_e( 'Unsubscribe from all optional emails?', 'paid-memberships-pro' );
							} else {
								/* translators: %s: email category name. */
								echo wp_kses( sprintf( __( 'Unsubscribe from %s emails?', 'paid-memberships-pro' ), $category_label ), array( 'strong' => array() ) );
							}
							?>
						</p>
						<p><?php esc_html_e( 'You\'ll still get emails about your account, payments, and membership.', 'paid-memberships-pro' ); ?></p>
					<?php } ?>
				</div> <!-- end pmpro_card_content -->
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_actions' ) ); ?>">
					<input type="submit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit', 'pmpro_btn-submit' ) ); ?>" value="<?php echo $opted_out ? esc_attr__( 'Resubscribe', 'paid-memberships-pro' ) : esc_attr__( 'Unsubscribe', 'paid-memberships-pro' ); ?>" />
					<a href="<?php echo esc_url( pmpro_get_email_preferences_url( $user->ID ) ); ?>"><?php esc_html_e( 'Manage all email preferences', 'paid-memberships-pro' ); ?></a>
				</div> <!-- end pmpro_card_actions -->
			</form>
		</div> <!-- end pmpro_card -->
	</section> <!-- end pmpro_email_unsubscribe -->
	<?php
	return ob_get_clean();
}

/**
 * Get the page that signed links in emails open on the Membership Account page.
 *
 * @since TBD
 *
 * @return string HTML.
 */
function pmpro_get_email_preferences_link_page() {
	$user = pmpro_get_email_preferences_user();
	if ( empty( $user ) ) {
		$profile_url = pmpro_url( 'member_profile_edit' );
		ob_start();
		?>
		<div role="alert" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message pmpro_error', 'pmpro_error' ) ); ?>">
			<p>
				<?php esc_html_e( 'This email preferences link is not valid.', 'paid-memberships-pro' ); ?>
				<a href="<?php echo esc_url( pmpro_login_url( empty( $profile_url ) ? pmpro_url( 'account' ) : $profile_url ) ); ?>"><?php esc_html_e( 'Log in to manage your email preferences', 'paid-memberships-pro' ); ?></a>.
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	$category = pmpro_get_email_preferences_unsubscribe_category();
	if ( ! empty( $category ) ) {
		return pmpro_get_email_preferences_unsubscribe_card( $user, $category );
	}

	$fields = pmpro_get_email_preferences_fields( $user );
	if ( empty( $fields ) ) {
		return '';
	}

	ob_start();
	?>
	<section id="pmpro_email_preferences" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section', 'pmpro_email_preferences' ) ); ?>">
		<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section_title pmpro_font-x-large' ) ); ?>"><?php esc_html_e( 'Email Preferences', 'paid-memberships-pro' ); ?></h2>
		<?php
		// Show the result of saving this form.
		if ( ! empty( $_POST['pmpro_email_preferences_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Read-only. Only decides whether to show the save message.
			pmpro_showMessage();
		}
		?>
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
			<form id="pmpro_form_email_preferences" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form', 'pmpro_form_email_preferences' ) ); ?>" action="<?php echo esc_url( pmpro_get_email_preferences_url( $user->ID ) ); ?>" method="post">
				<input type="hidden" name="pmpro_email_preferences_action" value="save" />
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
					<?php echo $fields; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped when built. ?>
				</div> <!-- end pmpro_card_content -->
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_actions' ) ); ?>">
					<input type="submit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit', 'pmpro_btn-submit' ) ); ?>" value="<?php esc_attr_e( 'Save Preferences', 'paid-memberships-pro' ); ?>" />
				</div> <!-- end pmpro_card_actions -->
			</form>
		</div> <!-- end pmpro_card -->
	</section> <!-- end pmpro_email_preferences -->
	<?php
	return ob_get_clean();
}
