<?php
/**
 * @var int $edit
 */

global $wpdb, $page_msg, $page_msgt, $pmpro_stripe_error;

// Get level templates.
$level_templates = pmpro_edit_level_templates();

// Load the media library for the level image selector.
wp_enqueue_media();

// Get level groups in order.
$level_groups = pmpro_get_level_groups_in_order();

// Get the template if passed in the URL.
if ( isset( $_REQUEST['template'] ) ) {
	$template = sanitize_text_field( $_REQUEST['template'] );
} else {
	$template = false;
}

// Are we copying a level?
if ( isset( $_REQUEST['copy'] ) ) {
	$copy = intval($_REQUEST['copy']);
}

// Set up the level group if copying or if group is passed in the URL.
if ( ! empty( $copy ) && $copy > 0 ) {
	// If we're copying, get the group from the copied level.
	$current_group = pmpro_get_group_id_for_level( $copy );
} else {
	$current_group = isset( $_REQUEST['level_group'] ) ? intval( $_REQUEST['level_group'] ) : 0;
}

// Get the primary gateway.
$gateway = get_option( "pmpro_gateway");

// Set up the level or create a new one.
if (!empty($edit) && $edit > 0) {
	$level = $wpdb->get_row(
		$wpdb->prepare(
			"
				SELECT * FROM $wpdb->pmpro_membership_levels
				WHERE id = %d LIMIT 1",
			$edit
		),
		OBJECT
	);
	$temp_id = $level->id;
} elseif (!empty($copy) && $copy > 0) {
	// We're copying a previous level, get that level's info.
	$level = $wpdb->get_row(
		$wpdb->prepare(
			"
        SELECT * FROM $wpdb->pmpro_membership_levels
        WHERE id = %d LIMIT 1",
			$copy
		),
		OBJECT
	);
	$temp_id = $level->id;
	$level->id = NULL;
}

// If we still don't have a level, set up a new one.
if (empty($level)) {
	$level = new stdClass();
	$level->id = NULL;
	$level->name = NULL;
	$level->description = '';
	$level->confirmation = '';
	$level->initial_payment = NULL;
	$level->billing_amount = NULL;
	$level->cycle_number = 1;
	$level->cycle_period = 'Month';
	$level->billing_limit = NULL;
	$level->trial_amount = NULL;
	$level->trial_limit = NULL;
	$level->expiration_number = NULL;
	$level->expiration_period = NULL;
	$edit = -1;

	// If we have a level template, override and set some defaults.
	if (!empty($template) && $template != 'none') {
		if ($template === 'free') {
			$level->billing_amount = NULL;
			$level->trial_amount = NULL;
			$level->initial_payment = NULL;
			$level->billing_limit = NULL;
			$level->trial_limit = NULL;
			$level->expiration_number = NULL;
			$level->expiration_period = NULL;
			$level->cycle_number = 1;
			$level->cycle_period = 'Month';
		} elseif ($template === 'onetime') {
			$level->initial_payment = 100;
			$level->billing_amount = NULL;
			$level->cycle_number = 1;
			$level->cycle_period = 'Year';
			$level->billing_limit = NULL;
			$level->trial_amount = NULL;
			$level->trial_limit = NULL;
			$level->expiration_number = 1;
			$level->expiration_period = 'Year';
		} elseif ($template === 'monthly') {
			$level->initial_payment = 25;
			$level->billing_amount = 25;
			$level->cycle_number = 1;
			$level->cycle_period = 'Month';
			$level->billing_limit = NULL;
			$level->trial_amount = NULL;
			$level->trial_limit = NULL;
			$level->expiration_number = NULL;
			$level->expiration_period = NULL;
		} elseif ($template === 'annual') {
			$level->initial_payment = 100;
			$level->billing_amount = 100;
			$level->cycle_number = 1;
			$level->cycle_period = 'Year';
			$level->billing_limit = NULL;
			$level->trial_amount = NULL;
			$level->trial_limit = NULL;
			$level->expiration_number = NULL;
			$level->expiration_period = NULL;
		} elseif ($template === 'lifetime') {
			$level->initial_payment = 500;
			$level->billing_amount = NULL;
			$level->cycle_number = 1;
			$level->cycle_period = 'Year';
			$level->billing_limit = NULL;
			$level->trial_amount = NULL;
			$level->trial_limit = NULL;
			$level->expiration_number = NULL;
			$level->expiration_period = NULL;
		} elseif ($template === 'trial') {
			$level->initial_payment = 0;
			$level->billing_amount = 25;
			$level->cycle_number = 1;
			$level->cycle_period = 'Month';
			$level->billing_limit = NULL;
			$level->trial_amount = 0;
			$level->trial_limit = 0;
			$level->expiration_number = NULL;
			$level->expiration_period = NULL;
		}
	}
	$level = apply_filters('pmpro_membershiplevels_template_level', $level, $template);
}

// Set some defaults for new levels.
if (empty($level->cycle_number)) {
	$level->cycle_number = 1;
}
if (empty($level->cycle_period)) {
	$level->cycle_period = 'Month';
}

// Grab the categories for the given level.
if (!empty($temp_id)) {
	$level->categories = $wpdb->get_col($wpdb->prepare(
		"
            SELECT c.category_id
            FROM $wpdb->pmpro_memberships_categories c
            WHERE c.membership_id = %d",
		$temp_id
	));
}

// If no categories, set up an empty array for the save event.
if (empty($level->categories)) {
	$level->categories = array();
}

// Grab the meta for the given level.
if (!empty($temp_id)) {
	$confirmation_in_email = get_pmpro_membership_level_meta($temp_id, 'confirmation_in_email', true);
} else {
	$confirmation_in_email = 0;
}

// Get the Membership Account Message via meta.
if ( ! empty( $temp_id ) ) {
	$membership_account_message = get_pmpro_membership_level_meta( $temp_id, 'membership_account_message', true );
} else {
	$membership_account_message = '';
}

// Get the level image via meta.
if ( ! empty( $temp_id ) ) {
	$level_image = (int) get_pmpro_membership_level_meta( $temp_id, 'level_image', true );
} else {
	$level_image = 0;
}

// Get the subscription delay and set expiration date settings for the given level
// and determine the type each should render as in the UI. Read the raw options
// rather than the pmpro_get_* getters: their runtime filters must not leak into
// the edit form, where the value shown is the value that gets re-saved.
$subscription_delay   = ! empty( $temp_id ) ? get_option( 'pmpro_subscription_delay_' . $temp_id, '' ) : '';
$set_expiration_date  = ! empty( $temp_id ) ? get_option( 'pmprosed_' . $temp_id, '' ) : '';
$delay_type           = empty( $subscription_delay ) ? 'none' : ( is_numeric( $subscription_delay ) ? 'days' : 'date' );
$expiration_date_type = ! empty( $set_expiration_date ) ? 'date' : 'none';
?>
<hr class="wp-header-end">
<?php if (!empty($level->id)) { ?>
	<h1 class="wp-heading-inline">
		<?php
		echo sprintf(
			// translators: %s is the Level ID.
			esc_html__('Edit Level ID: %s', 'paid-memberships-pro'),
			esc_attr($level->id)
		);
		?>
	</h1>
	<?php
	$view_checkout_url = pmpro_url('checkout', '?pmpro_level=' . $level->id, 'https');
	$view_orders_url = add_query_arg(array('page' => 'pmpro-orders', 'l' => $level->id, 'filter' => 'within-a-level'), admin_url('admin.php'));
	$view_members_url = add_query_arg(array('page' => 'pmpro-memberslist', 'l' => $level->id), admin_url('admin.php'));
	?>
	<a title="<?php esc_attr_e('View at Checkout', 'paid-memberships-pro'); ?>" href="<?php echo esc_url($view_checkout_url); ?>" target="_blank" class="page-title-action"><?php esc_html_e('View at Checkout', 'paid-memberships-pro'); ?></a>
	<a title="<?php esc_attr_e('View Members', 'paid-memberships-pro'); ?>" href="<?php echo esc_url($view_members_url); ?>" target="_blank" class="page-title-action"><?php esc_html_e('View Members', 'paid-memberships-pro'); ?></a>
	<a title="<?php esc_attr_e('View Orders', 'paid-memberships-pro'); ?>" href="<?php echo esc_url($view_orders_url); ?>" target="_blank" class="page-title-action"><?php esc_html_e('View Orders', 'paid-memberships-pro'); ?></a>
<?php } else { ?>
	<h1 class="wp-heading-inline"><?php esc_html_e('Add New Membership Level', 'paid-memberships-pro'); ?></h1>
<?php } ?>

<p><?php
	$edit_level_link = '<a title="' . esc_attr__( 'Paid Memberships Pro - Membership Level Setup Documentation', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener" href="https://www.paidmembershipspro.com/documentation/membership-levels/initial-membership-level-setup/?utm_source=plugin&utm_medium=pmpro-membershiplevels&utm_campaign=documentation&utm_content=&utm_term=">' . esc_html__( 'Membership Level Setup', 'paid-memberships-pro' ) . '</a>';
	// translators: %s: Link to Membership Level Setup doc.
	printf( esc_html__('Learn more about %s.', 'paid-memberships-pro' ), $edit_level_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?></p>

<?php
// Show the settings page message.
if (!empty($page_msg)) { ?>
	<div class="inline notice notice-large <?php echo $page_msg > 0 ? 'notice-success' : 'notice-error'; ?>">
		<p><?php echo wp_kses_post( $page_msgt ); ?></p>
	</div>
<?php }
?>
<form action="" method="post" enctype="multipart/form-data">
	<input name="saveid" type="hidden" value="<?php echo esc_attr($edit); ?>" />
	<input type="hidden" name="action" value="save_membershiplevel" />
		<?php wp_nonce_field('save_membershiplevel', 'pmpro_membershiplevels_nonce');
	// Resolve the level's group and build the group dropdown options.
	if ( empty( $current_group ) ) {
		$current_group = pmpro_get_group_id_for_level( $level->id );
	}
	$group_options = array();
	foreach ( $level_groups as $level_group ) {
		$group_options[ $level_group->id ] = $level_group->name;
	}

	pmpro_build_settings_section( array(
		'id'     => 'general-information',
		'title'  => __( 'General Information', 'paid-memberships-pro' ),
		'open'   => true,
		'fields' => array(
			array(
				'name'     => 'name',
				'label'    => __( 'Name', 'paid-memberships-pro' ),
				'type'     => 'text',
				'required' => true,
				'value'    => $level->name,
			),
			array(
				'name'    => 'level_group',
				'label'   => __( 'Group', 'paid-memberships-pro' ),
				'type'    => 'select',
				'value'   => $current_group,
				'options' => $group_options,
			),
			array(
				'name'     => 'level_image',
				'label'    => __( 'Level Image', 'paid-memberships-pro' ),
				'type'     => 'callback',
				'callback' => function() use ( $level_image ) {
					?>
					<div id="level_image_preview"><?php if ( ! empty( $level_image ) ) { echo wp_get_attachment_image( $level_image, 'medium' ); } ?></div>
					<input type="hidden" name="level_image" id="level_image" value="<?php echo esc_attr( ! empty( $level_image ) ? $level_image : '' ); ?>" />
					<button type="button" class="button" id="level_image_select"><?php esc_html_e( 'Select Image', 'paid-memberships-pro' ); ?></button>
					<button type="button" class="button" id="level_image_remove" <?php if ( empty( $level_image ) ) { echo 'style="display: none;"'; } ?>><?php esc_html_e( 'Remove Image', 'paid-memberships-pro' ); ?></button>
					<p class="description"><?php esc_html_e( 'Optional. This image is not shown at checkout. It is included in this level\'s structured data (JSON-LD) so that search engines and shopping tools can display an image for this membership.', 'paid-memberships-pro' ); ?></p>
					<script>
						jQuery( document ).ready( function( $ ) {
							var level_image_frame;
							$( '#level_image_select' ).on( 'click', function( e ) {
								e.preventDefault();
								if ( level_image_frame ) {
									level_image_frame.open();
									return;
								}
								level_image_frame = wp.media( {
									title: <?php echo wp_json_encode( __( 'Select Level Image', 'paid-memberships-pro' ) ); ?>,
									button: { text: <?php echo wp_json_encode( __( 'Use This Image', 'paid-memberships-pro' ) ); ?> },
									multiple: false,
									library: { type: 'image' }
								} );
								level_image_frame.on( 'select', function() {
									var attachment = level_image_frame.state().get( 'selection' ).first().toJSON();
									var url = ( attachment.sizes && attachment.sizes.medium ) ? attachment.sizes.medium.url : attachment.url;
									$( '#level_image' ).val( attachment.id );
									$( '#level_image_preview' ).html( $( '<img />', { src: url, style: 'max-width: 200px; height: auto;' } ) );
									$( '#level_image_remove' ).show();
								} );
								level_image_frame.open();
							} );
							$( '#level_image_remove' ).on( 'click', function( e ) {
								e.preventDefault();
								$( '#level_image' ).val( '' );
								$( '#level_image_preview' ).empty();
								$( this ).hide();
							} );
						} );
					</script>
					<?php
				},
			),
			array(
				'name'            => 'description',
				'label'           => __( 'Description', 'paid-memberships-pro' ),
				'type'            => 'editor',
				'value'           => $level->description,
				'editor_settings' => array( 'textarea_rows' => 5 ),
				'description'     => sprintf(
					esc_html__( 'This text appears at checkout and on the pricing page if using the %s. Use it to provide a brief overview of the membership level, highlighting key features and benefits to potential members.', 'paid-memberships-pro' ),
					'<a target="_blank" href="https://www.paidmembershipspro.com/add-ons/pmpro-advanced-levels-shortcode/?utm_source=plugin&utm_medium=pmpro-membershiplevels&utm_campaign=add-ons&utm_content=pmpro-advanced-levels-shortcode">' . esc_html__( 'Advanced Levels Page Add On', 'paid-memberships-pro' ) . '</a>'
				),
			),
			array(
				'name'     => 'confirmation',
				'label'    => __( 'Confirmation Message', 'paid-memberships-pro' ),
				'type'     => 'callback',
				'callback' => function() use ( $level, $confirmation_in_email ) {
					wp_editor( $level->confirmation, 'confirmation', array( 'textarea_rows' => 5 ) );
					?>
					<p><input id="confirmation_in_email" name="confirmation_in_email" type="checkbox" value="yes" <?php checked( $confirmation_in_email, 1 ); ?> aria-describedby="confirmation_in_email_description" /> <label for="confirmation_in_email"><?php esc_html_e( 'Check to include this message in the membership confirmation email.', 'paid-memberships-pro' ); ?></label></p>
					<p id="confirmation_in_email_description" class="description">
						<?php
						$allowed_confirmation_in_email_html = array(
							'a'    => array(
								'href'   => array(),
								'target' => array(),
								'title'  => array(),
								'rel'    => array(),
							),
							'code' => array(),
						);
						echo sprintf( wp_kses( __( 'Use the placeholder variable <code>%1$s</code> in your checkout <a href="%2$s" title="Edit Membership Email Templates">email templates</a> to include this information.', 'paid-memberships-pro' ), $allowed_confirmation_in_email_html ), '{{ membership_level_confirmation_message }}', esc_url( add_query_arg( 'page', 'pmpro-emailtemplates', admin_url( 'admin.php' ) ) ) );
						?>
					</p>
					<?php
				},
			),
			array(
				'name'            => 'membership_account_message',
				'label'           => __( 'Membership Account Message', 'paid-memberships-pro' ),
				'type'            => 'editor',
				'value'           => $membership_account_message,
				'editor_settings' => array( 'textarea_rows' => 5 ),
				'description'     => esc_html__( 'This message appears only to members of this level in the "My Memberships" section of the account page. Use it to share benefits or link to content specific to this level.', 'paid-memberships-pro' ),
			),
			array(
				'hook' => 'pmpro_membership_level_after_general_information',
				'args' => array( $level ),
			),
		),
	) );

	/**
	 * Allow adding form fields before the Billing Information section.
	 *
	 * @since 2.9
	 *
	 * @param object $level The Membership Level object.
	 */
	do_action('pmpro_membership_level_before_billing_information', $level);

	// Only show trial settings if the active gateway supports recurring trials or the level already has a trial set.
	$gateway_class = 'PMProGateway_' . $gateway;
	$gateway_supports_recurring_trials = method_exists( $gateway_class, 'supports' ) && $gateway_class::supports( 'recurring_trials' );

	// Render-time state for the checkboxes that the `depends` rules below reference. The rules
	// derive each row's initial visibility from the referenced checkbox's rendered state
	// automatically, then toggle live from there.
	$level_is_recurring = pmpro_isLevelRecurring( $level );
	$level_has_trial    = pmpro_isLevelTrial( $level );

	// Several billing rows are shown only while "Recurring Subscription" is checked.
	$depends_on_recurring = array( 'id' => 'recurring', 'checked' => true );

	// The Billing Amount description carries a gateway-specific warning for Stripe.
	$billing_amount_description = esc_html__( 'The amount to be billed one cycle after the initial payment.', 'paid-memberships-pro' );
	if ( 'stripe' === $gateway ) {
		$billing_amount_description .= ' <strong' . ( ! empty( $pmpro_stripe_error ) ? ' class="pmpro_red"' : '' ) . '>' . esc_html__( 'Stripe integration does not allow billing periods longer than 1 year.', 'paid-memberships-pro' ) . '</strong>';
	}

	// Build the Billing Details body as a declarative list: intro copy, the billing fields, the
	// add-on extension hooks, and the (conditional) trial fields. pmpro_build_settings_fields()
	// forms the form-tables around the runs of fields and renders the html/hook entries between them.
	$billing_fields = array(
		array(
			'html' => function() {
				$allowed_sd_html = array(
					'a' => array(
						'href'   => array(),
						'target' => array(),
						'title'  => array(),
					),
				);
				echo '<p>' . wp_kses( __( 'Set the member pricing for this level. The initial payment is collected immediately at checkout. Recurring payments, if applicable, begin one cycle after the initial payment. Changing the level price only applies to new members and does not affect existing members of this level.', 'paid-memberships-pro' ), $allowed_sd_html ) . '</p>';
			},
		),
		array(
			'name'        => 'initial_payment',
			'label'       => __( 'Initial Payment', 'paid-memberships-pro' ),
			'type'        => 'currency',
			'value'       => $level->initial_payment,
			'description' => __( 'The initial amount collected at registration.', 'paid-memberships-pro' ),
		),
		array(
			'name'           => 'recurring',
			'label'          => __( 'Recurring Subscription', 'paid-memberships-pro' ),
			'type'           => 'checkbox',
			'value'          => $level_is_recurring,
			'checkbox_label' => __( 'Check if this level has a recurring subscription payment.', 'paid-memberships-pro' ),
		),
			array(
				'label'       => __( 'Billing Amount', 'paid-memberships-pro' ),
				'type'        => 'composite',
				'row_class'   => 'recurring_info',
				'depends'     => array( $depends_on_recurring ),
				'description' => $billing_amount_description,
				'fields'      => array(
				array(
					'name'  => 'billing_amount',
					'type'  => 'currency',
					'value' => $level->billing_amount,
				),
				__( 'per', 'paid-memberships-pro' ),
				array(
					'name'  => 'cycle_number',
					'type'  => 'text',
					'class' => 'small-text',
					'value' => $level->cycle_number,
				),
				array(
					'name'    => 'cycle_period',
					'type'    => 'select',
					'value'   => ! empty( $level->cycle_period ) ? $level->cycle_period : 'Month',
					'options' => array(
						'Day'   => __( 'Day(s)', 'paid-memberships-pro' ),
						'Week'  => __( 'Week(s)', 'paid-memberships-pro' ),
						'Month' => __( 'Month(s)', 'paid-memberships-pro' ),
						'Year'  => __( 'Year(s)', 'paid-memberships-pro' ),
					),
				),
			),
		),
		array(
			'name'        => 'billing_limit',
			'label'       => __( 'Billing Cycle Limit', 'paid-memberships-pro' ),
				'type'        => 'text',
				'class'       => 'small-text',
				'value'       => $level->billing_limit,
				'row_class'   => 'recurring_info',
				'depends'     => array( $depends_on_recurring ),
				'description' => __( 'The <strong>total</strong> number of recurring billing cycles for this level, including the trial period (if applicable) but not including the initial payment. Set to zero if membership is indefinite.', 'paid-memberships-pro' ),
			),
		array(
			'label'     => __( 'First Recurring Payment', 'paid-memberships-pro' ),
			'type'      => 'callback',
			'row_class' => 'recurring_info',
			'depends'   => array( $depends_on_recurring ),
			'callback'  => function() use ( $delay_type, $subscription_delay ) {
				?>
				<fieldset id="pmpro_subscription_delay_fieldset">
					<legend class="screen-reader-text"><?php esc_html_e( 'First Recurring Payment', 'paid-memberships-pro' ); ?></legend>
					<label>
						<input type="radio" name="delay_type" value="none" <?php checked( $delay_type, 'none' ); ?> onchange="pmpro_toggle_delay_fields();" />
						<?php esc_html_e( 'Default (one billing cycle after checkout)', 'paid-memberships-pro' ); ?>
					</label>
					<br />
					<label>
						<input type="radio" name="delay_type" value="days" <?php checked( $delay_type, 'days' ); ?> onchange="pmpro_toggle_delay_fields();" />
						<?php esc_html_e( 'After a number of days', 'paid-memberships-pro' ); ?>
					</label>
					<span class="pmpro_delay_field pmpro_delay_field_days" <?php if ( $delay_type !== 'days' ) echo 'style="display:none;"'; ?>>
						&mdash;
						<input id="subscription_delay_days" name="subscription_delay_days" type="number" min="1" value="<?php echo esc_attr( $delay_type === 'days' ? $subscription_delay : '' ); ?>" class="small-text" aria-label="<?php esc_attr_e( 'Number of days after checkout', 'paid-memberships-pro' ); ?>" />
						<?php esc_html_e( 'days after checkout', 'paid-memberships-pro' ); ?>
					</span>
					<br />
					<label>
						<input type="radio" name="delay_type" value="date" <?php checked( $delay_type, 'date' ); ?> onchange="pmpro_toggle_delay_fields();" />
						<?php esc_html_e( 'On a specific date', 'paid-memberships-pro' ); ?>
					</label>
					<div class="pmpro_delay_field pmpro_delay_field_date" <?php if ( $delay_type !== 'date' ) echo 'style="display:none;"'; ?>>
						<?php pmpro_payment_schedule_render_date_builder( 'subscription_delay_date', $delay_type === 'date' ? $subscription_delay : '', 'pmpro_delay_date_builder' ); ?>
					</div>
				</fieldset>
				<?php
			},
		),
		array(
			'html' => function() use ( $level_is_recurring ) {
				?>
				<div class="pmpro_schedule_preview_inline<?php echo $level_is_recurring ? '' : ' pmpro-hidden'; ?>" data-pmpro-depends='[{"id":"recurring","checked":true}]'>
					<div class="pmpro_schedule_preview_bar">
						<span class="pmpro_schedule_preview_title"><?php esc_html_e( 'Payment Schedule Preview', 'paid-memberships-pro' ); ?></span>
						<label class="pmpro_schedule_preview_checkout_date">
							<?php esc_html_e( 'Preview checkout date:', 'paid-memberships-pro' ); ?>
							<input type="date" id="pmpro_preview_checkout_date" value="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>" />
						</label>
						<div id="pmpro_schedule_timeline" class="pmpro_schedule_timeline" role="status" aria-live="polite">
							<div class="pmpro_schedule_timeline_loading"><?php esc_html_e( 'Configure billing settings to see a preview.', 'paid-memberships-pro' ); ?></div>
						</div>
					</div>
				</div>
				<?php
			},
		),
		array(
			'hook' => 'pmpro_membership_level_after_billing_details_settings',
			'args' => array( $level ),
		),
	);

	// Trial fields, only when the gateway supports recurring trials or the level already has a trial.
	if ( $gateway_supports_recurring_trials || $level_has_trial ) {
		$billing_fields[] = array(
			'name'           => 'custom_trial',
			'label'          => __( 'Custom Trial', 'paid-memberships-pro' ),
			'type'           => 'checkbox',
				'value'          => $level_has_trial,
				'checkbox_label' => __( 'Check to add a custom trial period.', 'paid-memberships-pro' ),
				'row_class'      => 'recurring_info',
				'depends'        => array( $depends_on_recurring ),
				'description'    => $gateway_supports_recurring_trials ? '' : '<strong class="pmpro_red">' . esc_html__( 'The current payment gateway does not support recurring trials.', 'paid-memberships-pro' ) . '</strong>',
			);
		$billing_fields[] = array(
				'label'   => __( 'Trial Billing Amount', 'paid-memberships-pro' ),
				'type'    => 'composite',
				'row_class' => 'trial_info recurring_info',
				'depends' => array(
					$depends_on_recurring,
					array( 'id' => 'custom_trial', 'checked' => true ),
			),
			'fields'  => array(
				array(
					'name'  => 'trial_amount',
					'type'  => 'currency',
					'value' => $level->trial_amount,
				),
				__( 'for the first', 'paid-memberships-pro' ),
				array(
					'name'  => 'trial_limit',
					'type'  => 'text',
					'class' => 'small-text',
					'value' => $level->trial_limit,
				),
				__( 'subscription payments', 'paid-memberships-pro' ) . '.',
			),
		);
	}

	$billing_fields[] = array(
		'hook' => 'pmpro_membership_level_after_trial_settings',
		'args' => array( $level ),
	);

	pmpro_build_settings_section( array(
		'id'     => 'billing-details',
		'title'  => __( 'Billing Details', 'paid-memberships-pro' ),
		'open'   => ( ! pmpro_isLevelFree( $level ) || $template === 'none' ),
		'fields' => $billing_fields,
	) );

	$expiration_fields = array(
		array(
			'html' => function() {
				?>
				<p><?php esc_html_e( 'Control when membership access ends for this level. If left unchecked, membership access will not expire. For recurring memberships, leave expiration unchecked to continue charging members according to your billing settings.', 'paid-memberships-pro' ); ?></p>
				<div id="pmpro_expiration_warning" class="notice notice-alt notice-error inline pmpro-hidden" data-pmpro-depends='[{"id":"recurring","checked":true},{"id":"expiration","checked":true}]'>
					<p><?php
						$allowed_html = array(
							'a' => array(
								'target' => array(),
								'rel'    => array(),
								'href'   => array(),
							),
						);
						echo wp_kses( sprintf( __( 'WARNING: This level is set with both a recurring billing amount and an expiration date. You only need to set one of these unless you really want this membership to expire after a certain number of payments. For more information, <a target="_blank" rel="nofollow noopener" href="%s">see our post here</a>.', 'paid-memberships-pro' ), 'https://www.paidmembershipspro.com/membership-level-recurring-billing-and-expiration-date/?utm_source=plugin&utm_medium=pmpro-membershiplevels&utm_campaign=blog&utm_content=important-notes-on-recurring-billing-and-expiration-dates-for-membership-levels' ), $allowed_html );
					?></p>
				</div>
				<?php
			},
		),
		array(
			'name'           => 'expiration',
			'label'          => __( 'Membership Expiration', 'paid-memberships-pro' ),
			'type'           => 'checkbox',
			'value'          => pmpro_isLevelExpiring( $level ) || $expiration_date_type === 'date',
			'checkbox_label' => __( 'Check this to set when membership access expires.', 'paid-memberships-pro' ),
		),
	);

	$expiration_fields[] = array(
		'label'     => __( 'Expiration Type', 'paid-memberships-pro' ),
		'type'      => 'callback',
		'row_class' => 'expiration_info',
		'depends'   => array( array( 'id' => 'expiration', 'checked' => true ) ),
		'callback'  => function() use ( $level, $expiration_date_type, $set_expiration_date ) {
			?>
			<fieldset id="pmpro_expiration_type_fieldset">
				<legend class="screen-reader-text"><?php esc_html_e( 'Expiration Type', 'paid-memberships-pro' ); ?></legend>
				<label>
					<input type="radio" name="expiration_date_type" value="none" <?php checked( $expiration_date_type, 'none' ); ?> onchange="pmpro_toggle_expiration_type();" />
					<?php esc_html_e( 'After a set duration', 'paid-memberships-pro' ); ?>
				</label>
				<div class="pmpro_expiration_duration_fields" <?php if ( $expiration_date_type === 'date' ) echo 'style="display:none;"'; ?>>
					<input id="expiration_number" name="expiration_number" type="text" value="<?php echo esc_attr( $level->expiration_number ); ?>" class="small-text" aria-label="<?php esc_attr_e( 'Expiration number', 'paid-memberships-pro' ); ?>" />
					<select id="expiration_period" name="expiration_period" aria-label="<?php esc_attr_e( 'Expiration period', 'paid-memberships-pro' ); ?>">
						<?php
						$expiration_cycles = array(
							'Hour'  => __( 'Hour(s)', 'paid-memberships-pro' ),
							'Day'   => __( 'Day(s)', 'paid-memberships-pro' ),
							'Week'  => __( 'Week(s)', 'paid-memberships-pro' ),
							'Month' => __( 'Month(s)', 'paid-memberships-pro' ),
							'Year'  => __( 'Year(s)', 'paid-memberships-pro' ),
						);
						$current_expiration_period = ! empty( $level->expiration_period ) ? $level->expiration_period : 'Month';
						foreach ( $expiration_cycles as $value => $name ) {
							echo '<option value="' . esc_attr( $value ) . '"' . selected( $current_expiration_period, $value, false ) . '>' . esc_html( $name ) . '</option>';
						}
						?>
					</select>
					<p class="description"><?php esc_html_e( 'Set the duration of membership access. Note that any future payments (recurring subscription, if any) will be cancelled when the membership expires.', 'paid-memberships-pro' ); ?></p>
				</div>
				<br />
				<label>
					<input type="radio" name="expiration_date_type" value="date" <?php checked( $expiration_date_type, 'date' ); ?> onchange="pmpro_toggle_expiration_type();" />
					<?php esc_html_e( 'On a specific date', 'paid-memberships-pro' ); ?>
				</label>
				<div class="pmpro_expiration_date_field" <?php if ( $expiration_date_type !== 'date' ) echo 'style="display:none;"'; ?>>
					<?php pmpro_payment_schedule_render_date_builder( 'set_expiration_date', $set_expiration_date, 'pmpro_expiration_date_builder' ); ?>
				</div>
			</fieldset>
			<?php
		},
	);

	$expiration_fields[] = array(
		'hook' => 'pmpro_membership_level_after_expiration_settings',
		'args' => array( $level ),
	);

	pmpro_build_settings_section( array(
		'id'     => 'expiration-details',
		'title'  => __( 'Expiration Settings', 'paid-memberships-pro' ),
		'open'   => ( pmpro_isLevelExpiring( $level ) || $expiration_date_type === 'date' || $template === 'none' ),
		'fields' => $expiration_fields,
	) );

	/**
	 * Allow adding form fields before the Content Settings Information section.
	 *
	 * @since 2.9
	 *
	 * @param object $level The Membership Level object.
	 */
	do_action('pmpro_membership_level_before_content_settings', $level);

	$content_allowed_html = array(
		'a' => array(
			'href'   => array(),
			'title'  => array(),
			'target' => array(),
		),
	);

	pmpro_build_settings_section( array(
		'id'     => 'content-settings',
		'title'  => __( 'Content Settings', 'paid-memberships-pro' ),
		'open'   => true,
		'fields' => array(
			array(
				'html' => function() use ( $content_allowed_html ) {
					?>
					<p>
							<?php echo wp_kses( sprintf( __( 'Protect access to posts, pages, and content sections with built-in PMPro features. If you want to protect more content types, <a href="%s" rel="nofollow noopener" target="_blank">read our documentation on restricting content</a>.', 'paid-memberships-pro' ), 'https://www.paidmembershipspro.com/documentation/content-controls/?utm_source=plugin&utm_medium=pmpro-membershiplevels&utm_campaign=documentation&utm_content=pmpro-content-settings' ), $content_allowed_html );
						// Show a single message about how protected content displays to non-members, based on the Advanced Settings.
						$filterqueries = get_option( 'pmpro_filterqueries' );
						$showexcerpts  = get_option( 'pmpro_showexcerpts' );
						if ( $filterqueries == 1 ) {
							esc_html_e( 'Based on your advanced settings, protected content is hidden from non-members in searches and archives.', 'paid-memberships-pro' );
						} elseif ( $showexcerpts == 1 ) {
							esc_html_e( 'Based on your advanced settings, non-members will see the title and excerpt of protected content.', 'paid-memberships-pro' );
						} else {
							esc_html_e( 'Based on your advanced settings, non-members will see the title only for protected content.', 'paid-memberships-pro' );
						}
						echo ' ';
						echo sprintf( wp_kses( __( 'Display can vary by content type and theme. You can <a href="%s" title="Advanced Settings" target="_blank">update this setting here</a>.', 'paid-memberships-pro' ), $content_allowed_html ), esc_url( admin_url( 'admin.php?page=pmpro-advancedsettings' ) ) );
						?>
					</p>
					<?php
				},
			),
			array(
				'label'       => __( 'Categories', 'paid-memberships-pro' ),
				'type'        => 'checklist',
				'row_class'   => 'membership_categories',
				'select_all'  => true,
				'item_count'  => count( get_categories( apply_filters( 'pmpro_list_categories_args', array( 'hide_empty' => false ) ) ) ),
				'items'       => function() use ( $level ) {
					// Categories are hierarchical, so render the term tree rather than a flat option list.
					pmpro_listCategories( 0, $level->categories );
				},
				'description' => __( 'Select categories to bulk protect posts.', 'paid-memberships-pro' ),
			),
			array(
				'label'     => __( 'Single Posts', 'paid-memberships-pro' ),
				'type'      => 'html',
				'row_class' => 'membership_posts',
				'content'   => '<p>' . sprintf( __( '<a target="_blank" href="%1$s">Add</a> or <a target="_blank" href="%2$s">edit</a> a single post to protect it.', 'paid-memberships-pro' ), esc_url( admin_url( 'post-new.php' ) ), esc_url( admin_url( 'edit.php' ) ) ) . '</p>',
			),
			array(
				'label'     => __( 'Single Pages', 'paid-memberships-pro' ),
				'type'      => 'html',
				'row_class' => 'membership_posts',
				'content'   => '<p>' . sprintf( __( '<a target="_blank" href="%1$s">Add</a> or <a target="_blank" href="%2$s">edit</a> a single page to protect it.', 'paid-memberships-pro' ), esc_url( add_query_arg( array( 'post_type' => 'page' ), admin_url( 'post-new.php' ) ) ), esc_url( add_query_arg( array( 'post_type' => 'page' ), admin_url( 'edit.php' ) ) ) ) . '</p>',
			),
			array(
				'label'     => __( 'Other Content Types', 'paid-memberships-pro' ),
				'type'      => 'html',
				'row_class' => 'membership_posts',
				'content'   => '<p>' . sprintf( __( 'Protect access to other content including custom post types (CPTs), courses, events, products, communities, podcasts, and more. <a href="%s" rel="nofollow noopener" target="_blank">Read our documentation on restricting content</a>.', 'paid-memberships-pro' ), 'https://www.paidmembershipspro.com/restrict-access-wordpress/?utm_source=plugin&utm_medium=pmpro-membershiplevels&utm_campaign=blog&utm_content=pmpro-content-settings' ) . '</p>',
			),
			array(
				'hook' => 'pmpro_membership_level_after_content_settings',
				'args' => array( $level ),
			),
		),
	) );

	// The "add_on" template type opens Other Settings on page load.
	$is_addon = ! empty( $level_templates[ $template ]['type'] ) && $level_templates[ $template ]['type'] == 'add_on';

	pmpro_build_settings_section( array(
		'id'     => 'other-settings',
		'title'  => __( 'Other Settings', 'paid-memberships-pro' ),
		'open'   => ( $template == 'none' || $is_addon ),
		'fields' => array(
			array(
				'name'           => 'disable_signups',
				'label'          => __( 'Disable New Signups', 'paid-memberships-pro' ),
				'type'           => 'checkbox',
				'value'          => $level->id && ! $level->allow_signups,
				'checkbox_label' => __( 'Check to hide this level from the membership levels page and disable registration.', 'paid-memberships-pro' ),
			),
			array(
				'hook' => 'pmpro_membership_level_after_other_settings',
				'args' => array( $level ),
			),
		),
	) );
	?>

	<p class="submit">
		<input name="save" type="submit" class="button button-primary" value="<?php esc_attr_e('Save Level', 'paid-memberships-pro'); ?>" />
		<input name="cancel" type="button" class="button" value="<?php esc_attr_e('Cancel', 'paid-memberships-pro'); ?>" onclick="location.href='<?php echo esc_url(add_query_arg('page', 'pmpro-membershiplevels', admin_url('admin.php'))); ?>';" />
	</p>
</form>
<script type="text/javascript">
(function($) {
	'use strict';

	var previewDebounceTimer = null;
	var previewRequestCount = 0;
	var previewNonce = <?php echo wp_json_encode( wp_create_nonce( 'pmpro_payment_schedule_preview' ) ); ?>;

	/* ── Toggle functions ── */
	// The radios these are bound to bubble to the delegated preview listener
	// below, so the preview refreshes without an explicit call here.

	window.pmpro_toggle_delay_fields = function() {
		var delayType = $('input[name="delay_type"]:checked').val();
		$('.pmpro_delay_field_days').toggle(delayType === 'days');
		$('.pmpro_delay_field_date').toggle(delayType === 'date');
	};

	window.pmpro_toggle_expiration_type = function() {
		var expType = $('input[name="expiration_date_type"]:checked').val();
		$('.pmpro_expiration_duration_fields').toggle(expType === 'none');
		$('.pmpro_expiration_date_field').toggle(expType === 'date');
	};

	/* ── Schedule Preview (server-rendered via AJAX) ──
	 * The schedule itself is computed by wp_ajax_pmpro_payment_schedule_preview
	 * using the same date engine as checkout; this script only collects the
	 * form values and draws the returned events. */

	window.pmpro_update_schedule_preview = function() {
		clearTimeout(previewDebounceTimer);
		previewDebounceTimer = setTimeout(pmpro_do_schedule_preview, 300);
	};

	function showTimelineMessage(message) {
		$('#pmpro_schedule_timeline').empty().append(
			$('<div class="pmpro_schedule_timeline_empty"></div>').text(message)
		);
	}

	function pmpro_do_schedule_preview() {
		var data = {
			action: 'pmpro_payment_schedule_preview',
			nonce: previewNonce,
			checkout_date: $('#pmpro_preview_checkout_date').val(),
			recurring: $('#recurring').is(':checked') ? 1 : 0,
			expiration: $('#expiration').is(':checked') ? 1 : 0,
			custom_trial: $('#custom_trial').is(':checked') ? 1 : 0,
			initial_payment: $('input[name="initial_payment"]').val(),
			billing_amount: $('input[name="billing_amount"]').val(),
			cycle_number: $('input[name="cycle_number"]').val(),
			cycle_period: $('select[name="cycle_period"]').val(),
			billing_limit: $('input[name="billing_limit"]').val(),
			delay_type: $('input[name="delay_type"]:checked').val() || 'none',
			delay_days: $('#subscription_delay_days').val(),
			delay_date: $('#pmpro_delay_date_builder .pmpro_date_pattern_value').val(),
			expiration_type: $('input[name="expiration_date_type"]:checked').val() || 'none',
			expiration_number: $('input[name="expiration_number"]').val(),
			expiration_period: $('select[name="expiration_period"]').val(),
			set_expiration_date: $('#pmpro_expiration_date_builder .pmpro_date_pattern_value').val()
		};
		var requestNumber = ++previewRequestCount;
		$.post(ajaxurl, data, function(response) {
			// Ignore stale responses from superseded requests.
			if (requestNumber !== previewRequestCount) {
				return;
			}
			if (!response || !response.success || !response.data) {
				showTimelineMessage(<?php echo wp_json_encode( __( 'Unable to generate preview.', 'paid-memberships-pro' ) ); ?>);
				return;
			}
			renderTimeline(response.data);
		}).fail(function() {
			if (requestNumber === previewRequestCount) {
				showTimelineMessage(<?php echo wp_json_encode( __( 'Unable to generate preview.', 'paid-memberships-pro' ) ); ?>);
			}
		});
	}

	function renderTimeline(data) {
		if (data.empty) {
			showTimelineMessage(data.empty);
			return;
		}
		var events = data.events || [];
		var html = '<div class="pmpro_htimeline">';
		for (var i = 0; i < events.length; i++) {
			var event = events[i];
			// The type doubles as a class name suffix; only known types qualify.
			var typeClass = /^[a-z_]+$/.test(String(event.type || '')) ? event.type : '';
			html += '<div class="pmpro_htimeline_item pmpro_htimeline_item--' + typeClass + '">';
			if (event.type === 'initial') {
				html += '<div class="pmpro_htimeline_dot pmpro_htimeline_dot--calendar"><span class="dashicons dashicons-calendar-alt"></span></div>';
			} else {
				html += '<div class="pmpro_htimeline_dot"></div>';
			}
			html += '<div class="pmpro_htimeline_label">' + (event.type === 'continuation' ? '&hellip;' : escapeHtml(event.label || '')) + '</div>';
			if (event.amount) {
				html += '<div class="pmpro_htimeline_amount">' + escapeHtml(event.amount) + '</div>';
			}
			if (event.date) {
				html += '<div class="pmpro_htimeline_date">' + escapeHtml(event.date) + '</div>';
			}
			if (event.subtitle) {
				html += '<div class="pmpro_htimeline_subtitle">' + escapeHtml(event.subtitle) + '</div>';
			}
			html += '</div>';
			if (i < events.length - 1) {
				html += '<div class="pmpro_htimeline_connector"></div>';
			}
		}
		html += '</div>';
		var footnotes = data.footnotes || [];
		for (var f = 0; f < footnotes.length; f++) {
			html += '<div class="pmpro_htimeline_footnote">' + escapeHtml(footnotes[f]) + '</div>';
		}
		var notes = data.notes || [];
		for (var n = 0; n < notes.length; n++) {
			html += '<div class="pmpro_htimeline_footnote pmpro_htimeline_footnote--error">' + escapeHtml(notes[n]) + '</div>';
		}
		$('#pmpro_schedule_timeline').html(html);
	}

	function escapeHtml(text) {
		var div = document.createElement('div');
		div.appendChild(document.createTextNode(text));
		return div.innerHTML;
	}

	/* ── Init ── */

	$(document).ready(function() {
		// Trigger preview on page load.
		pmpro_update_schedule_preview();

		$('#pmpro_preview_checkout_date').on('change', pmpro_update_schedule_preview);

		// Watch all billing/expiration/delay form fields. Delegated so it also covers the
		// date pattern builder controls rendered by pmpro_payment_schedule_render_date_builder().
		$(document).on('input change', '#pmpro_subscription_delay_fieldset input, #pmpro_subscription_delay_fieldset select, #pmpro_expiration_type_fieldset input, #pmpro_expiration_type_fieldset select', pmpro_update_schedule_preview);
		$('input[name="initial_payment"], input[name="billing_amount"], input[name="cycle_number"], input[name="billing_limit"]').on('input change', pmpro_update_schedule_preview);
		$('select[name="cycle_period"]').on('change', pmpro_update_schedule_preview);
		$('#recurring, #expiration, #custom_trial').on('change', pmpro_update_schedule_preview);
	});
})(jQuery);
</script>
