<?php
//only admins can get this
if (!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_pagesettings"))) {
    die(esc_html__("You do not have permissions to perform this action.", 'paid-memberships-pro' ));
}

global $wpdb, $msg, $msgt;

//get/set settings
global $pmpro_pages;

/**
 * Adds additional page settings for use with add-on plugins, etc.
 *
 * @param array $pages {
 *     Formatted as array($name => $label)
 *
 *     @type string $name Page name. (Letters, numbers, and underscores only.)
 *     @type string $label Settings label.
 * }
 * @since 1.8.5
 */
$extra_pages = apply_filters('pmpro_extra_page_settings', array());

/**
 * @deprecated 3.0 replaced with pmpro_admin_pagesetting_post_type since 2.7.0
 */
$post_types = apply_filters_deprecated( 'pmpro_admin_pagesetting_post_type_array', array( array( 'page' ) ), '3.0', 'pmpro_admin_pagesetting_post_type' );

// For backward compatibility we extract the first element from the array
if ( is_array( $post_types ) ) {
    $post_type = reset( $post_types );
} else {
    $post_type = $post_types;
}

/**
 * Set post type to use for PMPro pages in the page settings dropdown.
 *
 * @since 2.7.0
 * @param string $post_type Accepts existing hierarchical post type
 */
$post_type = apply_filters( 'pmpro_admin_pagesetting_post_type', $post_type );

//check nonce for saving settings
if (!empty($_REQUEST['savesettings']) && (empty($_REQUEST['pmpro_pagesettings_nonce']) || !check_admin_referer('savesettings', 'pmpro_pagesettings_nonce'))) {
	$msg = -1;
	$msgt = __("Are you sure you want to do that? Try again.", 'paid-memberships-pro' );
	unset($_REQUEST['savesettings']);
}

if (!empty($_REQUEST['savesettings'])) {
    //page ids
    pmpro_setOption("account_page_id", NULL, 'intval');
    pmpro_setOption("billing_page_id", NULL, 'intval');
    pmpro_setOption("cancel_page_id", NULL, 'intval');
    pmpro_setOption("checkout_page_id", NULL, 'intval');
    pmpro_setOption("confirmation_page_id", NULL, 'intval');
    pmpro_setOption("invoice_page_id", NULL, 'intval');
    pmpro_setOption("levels_page_id", NULL, 'intval');
    pmpro_setOption("login_page_id", NULL, 'intval');
	pmpro_setOption("member_profile_edit_page_id", NULL, 'intval');

    //update the pages array
    $pmpro_pages["account"] = get_option( "pmpro_account_page_id");
    $pmpro_pages["billing"] = get_option( "pmpro_billing_page_id");
    $pmpro_pages["cancel"] = get_option( "pmpro_cancel_page_id");
    $pmpro_pages["checkout"] = get_option( "pmpro_checkout_page_id");
    $pmpro_pages["confirmation"] = get_option( "pmpro_confirmation_page_id");
    $pmpro_pages["invoice"] = get_option( "pmpro_invoice_page_id");
    $pmpro_pages["levels"] = get_option( "pmpro_levels_page_id");
	$pmpro_pages["login"] = get_option( "pmpro_login_page_id");
    $pmpro_pages['member_profile_edit'] = get_option( 'pmpro_member_profile_edit_page_id' );

    //save additional pages
    if (!empty($extra_pages)) {
        foreach ($extra_pages as $name => $label) {
            pmpro_setOption($name . '_page_id', NULL, 'intval');
            $pmpro_pages[$name] = get_option('pmpro_' . $name . '_page_id');
        }
    }

	// Save pmpro_use_custom_page_template settings.
	foreach ( $pmpro_pages as $name => $page_id ) {
		if ( isset( $_REQUEST[ 'pmpro_use_custom_page_template_' . $name ] ) ) {
			if ( ! in_array( $_REQUEST[ 'pmpro_use_custom_page_template_' . $name ], array( 'yes', 'no' ) ) ) {
				delete_option( 'pmpro_use_custom_page_template_' . $name );
			} else {
				update_option( 'pmpro_use_custom_page_template_' . $name, sanitize_text_field( $_REQUEST[ 'pmpro_use_custom_page_template_' . $name ] ) );
			}
		}
	}

	if ( empty( $_REQUEST['pmpro_disable_outdated_template_warning'] ) ) {
		delete_option( 'pmpro_disable_outdated_template_warning' );
	} else {
		update_option( 'pmpro_disable_outdated_template_warning', sanitize_text_field( $_REQUEST['pmpro_disable_outdated_template_warning'] ) );
	}

    //assume success
    $msg = true;
    $msgt = __("Your page settings have been updated.", 'paid-memberships-pro' );
}

//check nonce for generating pages
if (!empty($_REQUEST['createpages']) && (empty($_REQUEST['pmpro_pagesettings_nonce']) || !check_admin_referer('createpages', 'pmpro_pagesettings_nonce'))) {
	$msg = -1;
	$msgt = __("Are you sure you want to do that? Try again.", 'paid-memberships-pro' );
	unset($_REQUEST['createpages']);
}

//are we generating pages?
if (!empty($_REQUEST['createpages'])) {

    $pages = array();

	/**
	 * These pages were added later, and so we take extra
	 * care to make sure we only generate one version of them.
	 */
	$generate_once = array(
		'member_profile_edit' => __( 'Your Profile', 'paid-memberships-pro' ),
		'login' => 'Log In',
	);

    if(empty($_REQUEST['page_name'])) {
        //default pages
        $pages['account'] = __('Membership Account', 'paid-memberships-pro' );
        $pages['billing'] = __('Membership Billing', 'paid-memberships-pro' );
        $pages['cancel'] = __('Membership Cancel', 'paid-memberships-pro' );
        $pages['checkout'] = __('Membership Checkout', 'paid-memberships-pro' );
        $pages['confirmation'] = __('Membership Confirmation', 'paid-memberships-pro' );
        $pages['invoice'] = __('Membership Orders', 'paid-memberships-pro' );
        $pages['levels'] = __('Membership Levels', 'paid-memberships-pro' );
		$pages['login'] = __('Log In', 'paid-memberships-pro' );
		$pages['member_profile_edit'] = __('Your Profile', 'paid-memberships-pro' );
	} elseif ( in_array( $_REQUEST['page_name'], array_keys( $generate_once ) ) ) {
		$page_name = sanitize_text_field( $_REQUEST['page_name'] );
		if ( ! empty( get_option( $page_name . '_page_generated' ) ) ) {
			// Don't generate again.
			unset( $pages[$page_name] );

			// Find the old page
			$old_page = get_page_by_path( $page_name );
			if ( ! empty( $old_page ) ) {
				$pmpro_pages[$page_name] = $old_page->ID;
				pmpro_setOption( $page_name . '_page_id', $old_page->ID );
				pmpro_setOption( $page_name . '_page_generated', '1' );
				$msg = true;
				$msgt = sprintf( __( "Found an existing version of the %s page and used that one.", 'paid-memberships-pro' ), $page_name );
			} else {
				$msg = -1;
				$msgt = sprintf( __( "Error generating the %s page. You will have to choose or create one manually.", 'paid-memberships-pro' ), $page_name );
			}
		} else {
			// Generate the new Your Profile page and save an option that it was created.
			$pages[$page_name] = array(
				'title' => $generate_once[$page_name],
				'content' => '[pmpro_' . $page_name . ']',
			);
			pmpro_setOption( $page_name . '_page_generated', '1' );
		}
    } else {
        //generate extra pages one at a time
        $pmpro_page_name = sanitize_text_field($_REQUEST['page_name']);
        $pmpro_page_id = $pmpro_pages[$pmpro_page_name];
        $pages[$pmpro_page_name] = $extra_pages[$pmpro_page_name];
    }

    $pages_created = pmpro_generatePages($pages);

    if (!empty($pages_created)) {
        $msg = true;
        $msgt = __("The following pages have been created for you", 'paid-memberships-pro' ) . ": " . implode(", ", $pages_created) . ".";
    }
}

require_once(dirname(__FILE__) . "/admin_header.php"); ?>

    <form action="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-pagesettings' ) );?>" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('savesettings', 'pmpro_pagesettings_nonce');?>
        <hr class="wp-header-end">
        <h1><?php esc_html_e( 'Page Settings', 'paid-memberships-pro' ); ?></h1>
        <?php
		// check if we have all pages
		if ( $pmpro_pages['account'] ||
			$pmpro_pages['billing'] ||
			$pmpro_pages['cancel'] ||
			$pmpro_pages['checkout'] ||
			$pmpro_pages['confirmation'] ||
			$pmpro_pages['invoice'] ||
			$pmpro_pages['levels'] ||
			$pmpro_pages['member_profile_edit'] ) {
			$pmpro_some_pages_ready = true;
		} else {
			$pmpro_some_pages_ready = false;
		}

        if ( $pmpro_some_pages_ready ) { ?>
            <p><?php
				esc_html_e('Manage the WordPress pages assigned to each required Paid Memberships Pro page.', 'paid-memberships-pro' );
				echo ' ';
				$page_settings_link = '<a title="' . esc_attr__( 'Paid Memberships Pro - Page Settings Documentation', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener" href="https://www.paidmembershipspro.com/documentation/admin/page-settings/?utm_source=plugin&utm_medium=pmpro-pagesettings&utm_campaign=documentation&utm_content=&utm_term=">' . esc_html__( 'Page Settings', 'paid-memberships-pro' ) . '</a>';
				// translators: %s: Link to Page Settings doc.
				printf( esc_html__('Learn more about %s.', 'paid-memberships-pro' ), $page_settings_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?></p>
        <?php } elseif( ! empty( $_REQUEST['manualpages'] ) ) { ?>
            <p><?php esc_html_e('Assign the WordPress pages for each required Paid Memberships Pro page or', 'paid-memberships-pro' ); ?> <a
                    href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=pmpro-pagesettings&createpages=1' ), 'createpages', 'pmpro_pagesettings_nonce') );?>"><?php esc_html_e('click here to let us generate them for you', 'paid-memberships-pro' ); ?></a>.
            </p>
        <?php } else { ?>
            <div class="pmpro-new-install">
                <h2><?php esc_html_e( 'Manage Pages', 'paid-memberships-pro' ); ?></h2>
                <h4><?php esc_html_e( 'Several frontend pages are required for your Paid Memberships Pro site.', 'paid-memberships-pro' ); ?></h4>
                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=pmpro-pagesettings&createpages=1'), 'createpages', 'pmpro_pagesettings_nonce' ) ); ?>" class="button-primary"><?php esc_html_e( 'Generate Pages For Me', 'paid-memberships-pro' ); ?></a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-pagesettings&manualpages=1' ) ); ?>" class="button"><?php esc_html_e( 'Create Pages Manually', 'paid-memberships-pro' ); ?></a>
            </div> <!-- end pmpro-new-install -->
        <?php } ?>

		<?php if ( ! empty( $pmpro_some_pages_ready ) || ! empty( $_REQUEST['manualpages'] ) ) {
			/**
			 * Build one "page assignment" field: label + page dropdown + edit/view (or generate)
			 * buttons + hint. Shared by the primary and additional page settings sections.
			 *
			 * @param string $key              The page key in $pmpro_pages (input name is "{$key}_page_id").
			 * @param string $label            The row label.
			 * @param string $description_html Optional. Hint markup shown below the dropdown (already escaped).
			 * @param array  $args             Optional. 'none_label' for the empty option, 'generate' to show
			 *                                 a Generate Page link when no page is assigned.
			 */
			$pmpro_page_setting_field = function( $key, $label, $description_html = '', $args = array() ) use ( $pmpro_pages, $post_type ) {
				return array(
					'name'    => $key . '_page_id',
					'label'   => $label,
					'type'    => 'html',
					'content' => function() use ( $key, $description_html, $args, $pmpro_pages, $post_type ) {
						wp_dropdown_pages(
							array(
								'name'             => $key . '_page_id',
								// wp_dropdown_pages() does not escape show_option_none, and none_label may come from add-ons.
								'show_option_none' => esc_html( '-- ' . ( ! empty( $args['none_label'] ) ? $args['none_label'] : __( 'Choose One', 'paid-memberships-pro' ) ) . ' --' ),
								'selected'         => $pmpro_pages[ $key ],
								'post_type'        => $post_type,
							)
						);
						if ( ! empty( $pmpro_pages[ $key ] ) ) {
							?>
							<a target="_blank" href="post.php?post=<?php echo esc_attr( $pmpro_pages[ $key ] ); ?>&action=edit"
							class="button button-secondary pmpro_page_edit"><?php esc_html_e( 'edit page', 'paid-memberships-pro' ); ?></a>
							&nbsp;
							<a target="_blank" href="<?php echo esc_url( get_permalink( $pmpro_pages[ $key ] ) ); ?>"
							class="button button-secondary pmpro_page_view"><?php esc_html_e( 'view page', 'paid-memberships-pro' ); ?></a>
							<?php
						} elseif ( ! empty( $args['generate'] ) ) {
							?>
							&nbsp;
							<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'pmpro-pagesettings', 'createpages' => 1, 'page_name' => $key ), admin_url( 'admin.php' ) ), 'createpages', 'pmpro_pagesettings_nonce' ) ); ?>"><?php esc_html_e( 'Generate Page', 'paid-memberships-pro' ); ?></a>
							<?php
						}
						echo wp_kses_post( $description_html );
					},
				);
			};

			// Hint markup for the standard "shortcode or block" rows.
			$pmpro_page_shortcode_hint = function( $shortcode, $block_text ) {
				return '<p class="description">' . esc_html__( 'Include the shortcode', 'paid-memberships-pro' ) . ' ' . $shortcode . ' ' . $block_text . '.</p>';
			};

			$levels_page_hint = $pmpro_page_shortcode_hint( '[pmpro_levels]', esc_html__( 'or the Membership Levels block', 'paid-memberships-pro' ) );
			if ( ! function_exists( 'pmpro_advanced_levels_shortcode' ) ) {
				$allowed_advanced_levels_html = array (
					'a' => array (
						'href' => array(),
						'target' => array(),
						'title' => array(),
					),
				);
				$levels_page_hint .= '<p class="description">' . sprintf( wp_kses( __( 'Optional: Customize your Membership Levels page using the <a href="%s" title="Paid Memberships Pro - Advanced Levels Page Add On" target="_blank">Advanced Levels Page Add On</a>.', 'paid-memberships-pro' ), $allowed_advanced_levels_html ), 'https://www.paidmembershipspro.com/add-ons/pmpro-advanced-levels-shortcode/?utm_source=plugin&utm_medium=pmpro-pagesettings&utm_campaign=add-ons&utm_content=pmpro-advanced-levels-shortcode' ) . '</p>';
			}

			$frontend_template_customization_link_escaped = '<a title="' . esc_html__( 'Paid Memberships Pro - Frontend Page Templates', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener" href="https://www.paidmembershipspro.com/documentation/templates/?utm_source=plugin&utm_medium=pmpro-pagesettings&utm_campaign=documentation&utm_content=frontend-page-templates">' . esc_html__( 'how to customize the content of frontend pages', 'paid-memberships-pro' ) . '</a>';

			pmpro_build_settings_section( array(
				'id'     => 'pmpro-page-settings',
				'title'  => __( 'Primary Membership Page Settings', 'paid-memberships-pro' ),
				'fields' => array(
					array(
						// translators: %s: Link to Frontend Page Templates docs.
						'html' => '<p>' . sprintf( esc_html__( 'Click here for documentation on %s beyond the block or shortcode settings.', 'paid-memberships-pro' ), $frontend_template_customization_link_escaped ) . '</p>',
					),
					$pmpro_page_setting_field( 'account', __( 'Account Page', 'paid-memberships-pro' ), $pmpro_page_shortcode_hint( '[pmpro_account]', esc_html__( 'or the Membership Account block', 'paid-memberships-pro' ) ) ),
					$pmpro_page_setting_field( 'billing', __( 'Billing Information Page', 'paid-memberships-pro' ), $pmpro_page_shortcode_hint( '[pmpro_billing]', esc_html__( 'or the Membership Billing block', 'paid-memberships-pro' ) ) ),
					$pmpro_page_setting_field( 'cancel', __( 'Cancel Page', 'paid-memberships-pro' ), $pmpro_page_shortcode_hint( '[pmpro_cancel]', esc_html__( 'or the Membership Cancel block', 'paid-memberships-pro' ) ) ),
					$pmpro_page_setting_field( 'checkout', __( 'Checkout Page', 'paid-memberships-pro' ), $pmpro_page_shortcode_hint( '[pmpro_checkout]', esc_html__( 'or the Membership Checkout block', 'paid-memberships-pro' ) ) ),
					$pmpro_page_setting_field( 'confirmation', __( 'Confirmation Page', 'paid-memberships-pro' ), $pmpro_page_shortcode_hint( '[pmpro_confirmation]', esc_html__( 'or the Membership Confirmation block', 'paid-memberships-pro' ) ) ),
					$pmpro_page_setting_field( 'levels', __( 'Levels Page', 'paid-memberships-pro' ), $levels_page_hint ),
					$pmpro_page_setting_field(
						'login',
						__( 'Log In Page', 'paid-memberships-pro' ),
						'<p class="description">' . sprintf( esc_html__( 'Include the shortcode %s or the Log In Form block.', 'paid-memberships-pro' ), '[pmpro_login]' ) . '</p>',
						array(
							'none_label' => __( 'Use WordPress Default', 'paid-memberships-pro' ),
							'generate'   => empty( get_option( 'pmpro_login_page_generated' ) ),
						)
					),
					$pmpro_page_setting_field(
						'member_profile_edit',
						__( 'Member Profile Edit Page', 'paid-memberships-pro' ),
						'<p class="description">' . sprintf( esc_html__( 'Include the shortcode %s or the Member Profile Edit block.', 'paid-memberships-pro' ), '[pmpro_member_profile_edit]' ) . '</p>',
						array(
							'none_label' => __( 'Use WordPress Default', 'paid-memberships-pro' ),
							'generate'   => empty( get_option( 'pmpro_member_profile_edit_page_generated' ) ),
						)
					),
					$pmpro_page_setting_field( 'invoice', __( 'Orders Page', 'paid-memberships-pro' ), $pmpro_page_shortcode_hint( '[pmpro_invoice]', esc_html__( 'or the Membership Orders block', 'paid-memberships-pro' ) ) ),
					array( 'type' => 'submit' ),
				),
			) );
		?>
		<?php
		// Additional pages registered by add-ons via pmpro_extra_page_settings.
		if ( ! empty( $extra_pages ) ) {
			$extra_page_fields = array();
			foreach ( $extra_pages as $name => $page ) {
				if ( is_array( $page ) ) {
					$label = $page['title'];
					$hint  = ! empty( $page['hint'] ) ? $page['hint'] : '';
				} else {
					$label = $page;
					$hint  = '';
				}
				$extra_page_fields[] = $pmpro_page_setting_field(
					$name,
					$label,
					! empty( $hint ) ? '<p class="description">' . wp_kses_post( $hint ) . '</p>' : '',
					array( 'generate' => true )
				);
			}
			$extra_page_fields[] = array( 'type' => 'submit' );

			pmpro_build_settings_section( array(
				'id'     => 'pmpro-additional-page-settings',
				'title'  => __( 'Additional Page Settings', 'paid-memberships-pro' ),
				'fields' => $extra_page_fields,
			) );
		}
		?>

		<?php
			// Create a $template => $path array of all default page templates.
			$default_templates = array(
				'account' => PMPRO_DIR . '/pages/account.php',
				'billing' => PMPRO_DIR . '/pages/billing.php',
				'cancel' => PMPRO_DIR . '/pages/cancel.php',
				'checkout' => PMPRO_DIR . '/pages/checkout.php',
				'confirmation' => PMPRO_DIR . '/pages/confirmation.php',
				'invoice' => PMPRO_DIR . '/pages/invoice.php',
				'levels' => PMPRO_DIR . '/pages/levels.php',
				'login' => PMPRO_DIR . '/pages/login.php',
				'member_profile_edit' => PMPRO_DIR . '/pages/member_profile_edit.php',
			);

			// Filter $default_templates so that Add Ons can add their own templates.
			$default_templates = apply_filters( 'pmpro_default_page_templates', $default_templates );

			// Loop through each template. For each, if a custom page template is being loaded, store:
			// - The custom path being loaded.
			// - The version of the default template.
			// - The version of the custom template.
			$custom_templates = array(); // Array of $template => array( 'default_version' => $default_version, 'loaded_version' => $loaded_version, 'loaded_path' => $loaded_path ).
			foreach ( $default_templates as $template => $path ) {
				// Gather information about the default and loaded templates.
				$default_version = pmpro_get_version_for_page_template_at_path( $path );
				$loaded_path = pmpro_get_template_path_to_load( $template );
				$loaded_version = pmpro_get_version_for_page_template_at_path( $loaded_path );

				// If the $path and $loaded_path are different, a custom template is being loaded.
				if ( $path !== $loaded_path ) {
					$custom_templates[ $template ] = array(
						'default_version' => $default_version,
						'loaded_version' => $loaded_version,
						'loaded_path' => $loaded_path,
					);
				}
			}

			// If there are custom templates, display them.
			if ( ! empty( $custom_templates ) ) {
				// The template comparison list table is bespoke, so only the section wrapper and the
				// trailing warning setting use the shared helpers.
				pmpro_build_settings_section_open( array(
					'id'    => 'pmpro-custom-page-template-settings',
					'title' => __( 'Custom Page Templates', 'paid-memberships-pro' ),
				) );
				?>
						<p>
							<?php esc_html_e( 'Your site is loading custom page templates. These settings allow you to change which custom template is being loaded for your frontend pages. If your custom template is causing fatal errors or blocking the checkout process, you should load the core PMPro version while you or your developer works on template compatibility.', 'paid-memberships-pro' ); ?>
						</p>
						<h4><?php esc_html_e( 'How to Fix Outdated Page Templates', 'paid-memberships-pro' ); ?></h4>
						<ol>
							<li><?php esc_html_e( 'If your templates are loaded from a third-party plugin or theme, update to the latest version or contact the developer and let them know their templates are out of date.', 'paid-memberships-pro' ); ?></li>
							<li><?php esc_html_e( 'If you or your developer wrote your own templates, compare your version to the core PMPro version, make the required updates, and update the version number in your custom template.', 'paid-memberships-pro' ); ?></li>
							<li><?php esc_html_e( 'If you are unable to update the custom template file, use the settings below to load the core PMPro version of the template.', 'paid-memberships-pro' ); ?></li>
						</ol>
						<p>
							<a href="https://www.paidmembershipspro.com/documentation/templates/template-versions/" target="_blank"><?php esc_html_e( 'Docs: Template versions and outdated templates', 'paid-memberships-pro' ); ?></a>
						</p>
						<table class="widefat striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Template', 'paid-memberships-pro' ); ?></th>
									<th><?php esc_html_e( 'Core PMPro Version', 'paid-memberships-pro' ); ?></th>
									<th><?php esc_html_e( 'Custom Template Version', 'paid-memberships-pro' ); ?></th>
									<th><?php esc_html_e( 'Action', 'paid-memberships-pro' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ( $custom_templates as $template => $data ) {
									// Calculate data for "Custom Template Version" column.
									$versions_match = $data['loaded_version'] === $data['default_version'];
									$loaded_path_parts = explode('/', $data['loaded_path']);
									if (strpos($data['loaded_path'], '/themes/') !== false) {
										// Must be from a theme.
										$loaded_file_from_name = $loaded_path_parts[ array_search('themes', $loaded_path_parts) + 1 ];
										$loaded_path_source_type = __('theme', 'paid-memberships-pro');
									} else {
										// Must be from a plugin.
										$loaded_file_from_name = $loaded_path_parts[ array_search('plugins', $loaded_path_parts) + 1 ];
										$loaded_path_source_type = __('plugin', 'paid-memberships-pro');
									}

									// Detect the current "using page template?" setting.
									$use_custom_page_template = get_option( 'pmpro_use_custom_page_template_' . $template );
									if ( 'no' !== $use_custom_page_template && 'yes' != $use_custom_page_template ) {
										$use_custom_page_template = ''; // Empty is "use custom page template when compatible with current PMPro version".
									}

									// Output the row.
									?>
									<tr>
										<td><?php echo esc_html( $template ); ?></td>
										<td>
											<strong><?php echo esc_html( empty( $data['default_version'] ) ? esc_html__( 'N/A', 'paid-memberships-pro' ) : $data['default_version'] ); ?></strong>
											<br />
											<small><?php
											// Display the source of the PMPro version from the Paid Memberships Pro plugin.
											// translators: %1$s: The Paid Memberships Pro plugin folder name.
											printf( esc_html__( 'Plugin: %1$s', 'paid-memberships-pro' ), '<code>paid-memberships-pro</code>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											?></small>
										</td>
										<td>
											<strong style="color: var(--pmpro--color--<?php echo $versions_match ? 'success' : 'error'; ?>-text);">
												<?php echo esc_html( empty( $data['loaded_version'] ) ? esc_html__( 'N/A', 'paid-memberships-pro' ) : $data['loaded_version'] ); ?>
											</strong>
											<?php if ( $use_custom_page_template == 'yes' && ! $versions_match ) { ?>
												<span class="pmpro_tag pmpro_tag-has_icon pmpro_tag-error"><?php esc_html_e( 'Outdated Template', 'paid-memberships-pro' ); ?></span>
											<?php } ?>
											<br />
											<small><?php
											// Display the source of the loaded file and type.
											// translators: %1$s: The source type of the loaded file. %2$s: The theme or plugin folder name of the loaded file.
											printf( esc_html__( '%1$s: %2$s', 'paid-memberships-pro' ), esc_html( ucwords( $loaded_path_source_type ) ), '<code>' . esc_html( $loaded_file_from_name ) . '</code>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											?></small>
										</td>
										<td>
											<?php if ( 'yes' === $use_custom_page_template && ! $versions_match ) { ?>
												<span class="pmpro_tag pmpro_tag-has_icon pmpro_tag-error">
											<?php } ?>
											<select name="pmpro_use_custom_page_template_<?php echo esc_attr( $template ); ?>">
												<option value="yes" <?php selected( $use_custom_page_template, 'yes' ); ?>>
													<?php
													// translators: %s: The custom page template name.
													echo esc_html( sprintf( __('Custom: Always use my custom %s template.', 'paid-memberships-pro' ), $template ) );
													?>
												</option>
												<option value="" <?php selected( $use_custom_page_template, '' ); ?>>
													<?php
													// translators: %s: The custom page template name.
													echo esc_html( sprintf( __('Fallback: Use the core PMPro template if my custom %s template is not compatible.', 'paid-memberships-pro' ), $template ) );
													?>
												</option>
												<option value="no" <?php selected( $use_custom_page_template, 'no' ); ?>>
													<?php
													// translators: %s: The custom page template name.
													echo esc_html( sprintf( __('Core: Always use the core PMPro %s template.', 'paid-memberships-pro' ), $template ) );
													?>
												</option>
											</select>
											<?php if ( 'yes' === $use_custom_page_template && ! $versions_match ) { ?>
												</span>
											<?php } ?>
										</td>
									</tr>
									<?php
								}
								?>
							</tbody>
						</table>
						<?php
						// Add a dropdown setting to disable the "outdated template" warning.
						pmpro_build_settings_fields( array(
							array(
								'name'        => 'pmpro_disable_outdated_template_warning',
								'label'       => __( 'Disable Outdated Template Warning', 'paid-memberships-pro' ),
								'type'        => 'select',
								'value'       => ! empty( get_option( 'pmpro_disable_outdated_template_warning' ) ) ? 1 : 0,
								'options'     => array(
									0 => __( 'Show warning for outdated custom page templates.', 'paid-memberships-pro' ),
									1 => __( 'Do not show warning for outdated custom page templates.', 'paid-memberships-pro' ),
								),
								'description' => __( 'If you are aware of the outdated custom page templates and do not want to see the warning, you can disable it here.', 'paid-memberships-pro' ),
							),
							array( 'type' => 'submit' ),
						) );
						?>
				<?php
				pmpro_build_settings_section_close();
			}
		}
		?>
    </form>

<?php
require_once(dirname(__FILE__) . "/admin_footer.php");
?>
