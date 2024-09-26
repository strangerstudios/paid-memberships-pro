<?php
// Only admins can get to this screen.
if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_emailsettings' ) ) ) {
	die (esc_html__( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
}

require_once(dirname(__FILE__) . "/admin_header.php");

global $wpdb, $msg, $msgt, $pmpro_email_templates_defaults, $current_user;

// Set the template based on the request or post value, if set.
$edit = isset( $_REQUEST['edit'] ) ? $_REQUEST['edit'] : ( isset( $_POST['edit'] ) ? $_POST['edit'] : null );
$template = isset( $pmpro_email_templates_defaults[ $edit ] ) ? $pmpro_email_templates_defaults[ $edit ] : null;

// Do we have a template to edit? If so, show the edit screen.
if ( ! empty( $template ) ) {
	require_once( PMPRO_DIR . '/adminpages/emailtemplates-edit.php' );
} else {
	// Showing the email templates list.
	?>
	<hr class="wp-header-end">
	<h1><?php esc_html_e( 'Edit Email Templates', 'paid-memberships-pro' ); ?></h1>
	<p><?php esc_html_e( 'Select an email template to customize the subject and body of emails sent through your membership site. You can also disable a specific email or send a test version through this admin page.', 'paid-memberships-pro' ); ?> <a href="https://www.paidmembershipspro.com/documentation/member-communications/list-of-pmpro-email-templates/" target="_blank"><?php esc_html_e( 'Click here for a description of each email sent to your members and admins at different stages of the member experience.', 'paid-memberships-pro'); ?></a></p>
	<table class="wp-list-table widefat striped">
		<thead>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Email Template Name', 'paid-memberships-pro' ); ?>
				</th>
				<th>
					<?php esc_html_e( 'Default Recipient', 'paid-memberships-pro' ); ?>
				</th>
				<th>
					<?php esc_html_e( 'Subject', 'paid-memberships-pro' ); ?>
				</th>
				<th>
					<?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?>
				</th>
			</tr>
		</thead>
		<tbody>
		<?php
			/**
			 * Filter to show the "default" email template in the dropdown.
			 *
			 * @since 3.1
			 *
			 * @param bool $show_default_email_template Whether to show the default email template in the dropdown.
			 */
			$show_default_email_template = apply_filters( 'pmpro_show_default_email_template_in_dropdown', false );

			// Alphabetize the email templates by description.
			uasort( $pmpro_email_templates_defaults, function( $a, $b ) {
				return strcasecmp( $a['description'], $b['description'] );
			} );

			// Move the default, header, and footer email templates to the bottom of the list.
			$pmpro_email_templates_defaults = array_merge(
				array_filter(
					$pmpro_email_templates_defaults,
					function( $key ) {
						return ! in_array( $key, [ 'default', 'header', 'footer' ], true );
					},
					ARRAY_FILTER_USE_KEY
				),
				array_filter(
					$pmpro_email_templates_defaults,
					function( $key ) {
						return in_array( $key, [ 'default', 'header', 'footer' ], true );
					},
					ARRAY_FILTER_USE_KEY
				)
			);

			foreach ( $pmpro_email_templates_defaults as $key => $template ) {
				// If the template is the default template and we're not showing it in the dropdown, skip it.
				if ( 'default' === $key && ! $show_default_email_template ) {
					continue;
				}
				?>
				<tr>
					<td class="has-row-actions" data-colname="<?php esc_attr_e( 'Email Template Name', 'paid-memberships-pro' ); ?>">
						<strong><a href="<?php echo esc_url( add_query_arg( [ 'page' => 'pmpro-emailtemplates', 'edit' => $key ] ), admin_url( 'admin.php' ) ); ?>"><?php echo esc_html( $template['description'] ); ?></a></strong>
						<div class="row-actions">
						<?php
							$actions = [
								'edit'   => sprintf(
									'<a title="%1$s" href="%2$s">%3$s</a>',
									esc_attr__( 'Edit', 'paid-memberships-pro' ),
									esc_url(
										add_query_arg(
											[
												'page' => 'pmpro-emailtemplates',
												'edit' => $key,
											],
											admin_url( 'admin.php' )
										)
									),
									esc_html__( 'Edit', 'paid-memberships-pro' )
								),
							];

							/**
							 * Filter the extra actions for this template.
							 *
							 * @since 3.2
							 *
							 * @param array  $actions The list of actions.
							 * @param object $template   The email template data.
							 */
							$actions = apply_filters( 'pmpro_emailtemplates_row_actions', $actions, $template );

							$actions_html = [];

							foreach ( $actions as $action => $link ) {
								$actions_html[] = sprintf(
									'<span class="%1$s">%2$s</span>',
									esc_attr( $action ),
									$link
								);
							}

							if ( ! empty( $actions_html ) ) {
								echo implode( ' | ', $actions_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>
						</div>
					</td>
					<td data-colname="<?php esc_attr_e( 'Default Recipient', 'paid-memberships-pro' ); ?>">
						<?php
							// If the email has _admin in $key, it's an admin email.
							// If the email is default, header, or footer, show a dash.
							if ( strpos( $key, '_admin' ) !== false ) {
								echo esc_html__( 'Admin', 'paid-memberships-pro' );
							} elseif ( in_array( $key, [ 'default', 'header', 'footer' ], true ) ) {
								echo esc_html__( '&#8212;', 'paid-memberships-pro' );
							} else {
								echo esc_html__( 'Member', 'paid-memberships-pro' );
							}
						?>
					</td>
					<td data-colname="<?php esc_attr_e( 'Subject', 'paid-memberships-pro' ); ?>">
						<?php
							$subject = get_option( 'pmpro_email_' . $key . '_subject', $template['subject'] );
							echo ! empty( $subject ) ? esc_html( $subject ) : __( '&#8212;', 'paid-memberships-pro' );
						?>
					</td>
					<td data-colname="<?php esc_attr_e( 'Status', 'paid-memberships-pro' ); ?>">
						<?php
							if ( filter_var( get_option( 'pmpro_email_' . $key . '_disabled' ), FILTER_VALIDATE_BOOLEAN ) ) {
								echo '<span class="pmpro_tag pmpro_tag-alert">' . esc_html__( 'Disabled', 'paid-memberships-pro' ) . '</span>';
							} else {
								echo '<span class="pmpro_tag pmpro_tag-success">' . esc_html__( 'Enabled', 'paid-memberships-pro' ) . '</span>';
							}
						?>
					</td>
				</tr>
				<?php
			}
		?>
		</tbody>
	</table>
	<?php
}

require_once(dirname(__FILE__) . "/admin_footer.php");
