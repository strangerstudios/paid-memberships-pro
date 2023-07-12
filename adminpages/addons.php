<?php
	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_addons")))
	{
		die(__("You do not have permissions to perform this action.", 'paid-memberships-pro' ));
	}	
	
	global $wpdb, $msg, $msgt, $pmpro_addons;
	
	require_once(dirname(__FILE__) . "/admin_header.php");	

	//force a check of plugin versions?
	if(!empty($_REQUEST['force-check']))
	{
		wp_version_check(array(), true);
		wp_update_plugins();
		$pmpro_license_key = get_option("pmpro_license_key", "");
		pmpro_license_isValid($pmpro_license_key, NULL, true);
	}
	
	// Get all Add Ons.
	$addons = pmpro_getAddons();

	// Get some other variables.
	$addons_timestamp = get_option("pmpro_addons_timestamp", false);
	$plugin_info = get_site_transient( 'update_plugins' );
	$pmpro_license_key = get_option( 'pmpro_license_key', '' );

	// Build array of Visible Add Ons.
	$all_visible_addons = array();
	foreach ( $addons as $addon ) {
		// Build Visible array.
		if ( empty ( $addon['HideFromAddOnsList'] ) ) {
			$all_visible_addons[] = $addon;
		}
	}

	// Get all Add On Categories.
	$addon_cats = pmpro_get_addon_categories();

	?>
	<hr class="wp-header-end">
	<div id="pmpro-admin-add-ons">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Add Ons', 'paid-memberships-pro' ); ?></h1>
		<p class="pmpro-admin-add-ons-refresh">
			<?php printf(__('Last checked on %s at %s.', 'paid-memberships-pro' ), date_i18n(get_option('date_format'), $addons_timestamp), date_i18n(get_option('time_format'), $addons_timestamp));?> &nbsp;
			<a class="button" href="<?php echo esc_url( admin_url("admin.php?page=pmpro-addons&force-check=1") );?>"><?php esc_html_e('Check Again', 'paid-memberships-pro' ); ?></a>
		</p>
		<?php
			pmpro_showMessage();
		?>
		<div class="wp-filter">
			<ul class="filter-links">
				<li class="addons-search" style="display: none;"><a href="#search"><?php esc_html_e('Search Results', 'paid-memberships-pro' ); ?></a></li>
				<li><a data-toggle="view" data-search="view" data-view="all" href="#all" class="current"><?php esc_html_e('All', 'paid-memberships-pro' ); ?></a></li>
				<li><a data-toggle="view" data-search="view" data-view="popular" href="#popular"><?php esc_html_e( 'Popular', 'paid-memberships-pro' ); ?></a></li>
				<li><a data-toggle="view" data-search="view" data-view="free" href="#free"><?php esc_html_e( 'Free', 'paid-memberships-pro' ); ?></a></li>
				<li><a data-toggle="view" data-search="view" data-view="premium" href="#premium"><?php esc_html_e( 'Premium', 'paid-memberships-pro' ); ?></a></li>
			</ul>
			<div class="search-form">
				<label class="screen-reader-text" for="search-plugins"><?php esc_html_e( 'Search Add Ons', 'paid-memberships-pro' ); ?></label>
				<input type="search" name="s" id="search-add-ons" data-search="content" class="wp-filter-search" placeholder="<?php esc_attr_e( 'Search Add Ons...', 'paid-memberships-pro' ); ?>">
			</div>
		</div> <!-- end wp-filter -->
		<br class="clear">
		<div id="pmpro-no-add-ons" class="notice notice-info notice-large inline" style="display: none;">
			<p>
				<?php esc_html_e( 'No Add Ons found.', 'paid-memberships-pro' ); ?>
				<a href="admin.php?page=pmpro-addons"><?php esc_html_e( 'View All', 'paid-memberships-pro' ); ?></a>
			</p>
		</div>
		<div id="pmpro-admin-add-ons-list">
			<div class="list">
				<?php
				$installed_plugins = array_keys( get_plugins() );
				foreach ( $all_visible_addons as $addon ) {
					$plugin_file = $addon['Slug'] . '/' . $addon['Slug'] . '.php';
					$plugin_file_abs = ABSPATH . 'wp-content/plugins/' . $plugin_file;

					// Check in case the plugin is installed but has a different file name.
					if ( ! file_exists( $plugin_file_abs ) ) {
						foreach ( $installed_plugins as $installed_plugin ) {
							if ( strpos( $installed_plugin, $addon['Slug'] . '/' ) !== false ) {
								$plugin_file = $installed_plugin;
								$plugin_file_abs = ABSPATH . 'wp-content/plugins/' . $plugin_file;
								break;
							}
						}
					}

					// Make sure plugin value is set.
					if ( empty( $addon['plugin'] ) ) {
						$addon['plugin'] = $plugin_file;
					}

					// Set the src of the icon for this Add On.
					$addon['plugin_icon_src'] = esc_url( pmpro_get_addon_icon( $addon['Slug'] ) );

					if ( empty( $addon['ShortName'] ) ) {
						$addon['ShortName'] = $addon['Name'];
					}

					// Set plugin data for whether the plugin needs to be updated.
					if ( isset( $plugin_info->response[$plugin_file] ) ) {
						$addon['needs_update'] = true;
					} else {
						$addon['needs_update'] = false;
					}

					// Set plugin data for 'status' from active, inactive, and uninstalled.
					if ( is_plugin_active( $plugin_file ) ) {
						$addon['status'] = 'active';
					} elseif ( file_exists( $plugin_file_abs ) ) {
						$addon['status'] = 'inactive';
					} else {
						$addon['status'] = 'uninstalled';
					}

					// Set plugin data for whether this user can access this Add On.
					if ( pmpro_can_download_addon_with_license( $addon['License'] ) ) {
						$addon['access'] = true;
					} else {
						$addon['access'] = false;
					}

					// Build the selectors for the Add On in the list.
					$classes = array();
					$classes[] = 'add-on-container';
					$classes[] = 'add-on-' . $addon['status'];
					if ( ! empty( $addon['needs_update'] ) ) {
						$classes[] = 'add-on-needs-update';
					}
					$class = implode( ' ', array_unique( $classes ) );

					// Build the data-view for the Add On in the list.
					$views = array();
					$views[] = 'all';
					foreach ( $addon_cats as $cat => $slugs ) {
						if ( in_array( $addon['Slug'], $slugs ) ) {
							$views[] = $cat;
						}
					}
					if ( in_array( $addon['License'], array( 'free', 'wordpress.org' ) ) ) {
						$views[] = 'free';
					}
					if ( pmpro_license_type_is_premium( $addon['License'] ) ) {
						$views[] = 'premium';
					}
					$view = implode( ' ', array_unique( $views ) );
				?>
				<div id="<?php echo esc_attr( $addon['Slug'] ); ?>" class="<?php echo esc_attr( $class ); ?>" data-search-content="<?php echo esc_attr( $addon['Name'] ); ?> <?php echo esc_attr( $addon['Slug'] ); ?> <?php echo esc_attr( $addon['Description'] ); ?> <?php echo esc_attr( $addon['License'] ); ?> <?php echo esc_attr( $view ); ?>" data-search-license="<?php echo esc_attr( $addon['License'] ); ?>" data-search-view="<?php echo esc_attr( $view ); ?>">
					<div class="add-on-item">
						<div class="details">
							<?php
								if ( $addon['License'] === 'wordpress.org' && ! empty( $addon['Author'] && ! in_array( $addon['Author'], array( 'Paid Memberships Pro', 'Stranger Studios' ) ) ) ) {
									$plugin_link = 'https://wordpress.org/plugins/' . $addon['Slug'];
								} else {
									$plugin_link = $addon['PluginURI'] . '?utm_source=plugin&utm_medium=pmpro-addons&utm_campaign=add-ons&utm_content=' . $addon['Slug'];
								}
							?>
							<?php if ( ! empty( $addon['plugin_icon_src'] ) ) { ?>
								<?php if ( ! empty( $addon['PluginURI'] ) ) { ?>
									<a target="_blank" href="<?php echo esc_url( $plugin_link ); ?>">
								<?php } ?>
								<img src="<?php echo esc_url( $addon['plugin_icon_src'] ); ?>" alt="<?php esc_attr_e( $addon['Name'] ); ?>">
								<?php if ( ! empty( $addon['PluginURI'] ) ) { ?>
									</a>
								<?php } ?>
							<?php } ?>
							<h5 class="add-on-name">
								<?php if ( ! empty( $addon['PluginURI'] ) ) { ?>
									<a target="_blank" href="<?php echo esc_url( $plugin_link ); ?>">
								<?php } ?>
								<?php esc_html_e( $addon['ShortName'] ); ?>
								<?php if ( ! empty( $addon['PluginURI'] ) ) { ?>
									</a>
								<?php } ?>
							</h5> <!-- end add-on-name -->
							<div class="add-on-description">
								<p><?php esc_html_e( $addon['Description'] ); ?></p>
								<p>
								<?php
									$plugin_meta = array();
									if ( ! empty( $addon['Author'] && ! in_array( $addon['Author'], array( 'Paid Memberships Pro', 'Stranger Studios' ) ) ) ) {
										$author = $addon['Author'];
										if ( ! empty( $addon['AuthorURI'] ) )
											$author = '<a href="' . esc_url( $addon['AuthorURI'] ) . '" target="_blank">' . esc_html( $addon['Author'] ) . '</a>';
										$plugin_meta[] = sprintf( __( 'By %s' ), $author );
									}
									//$plugin_meta = apply_filters( 'plugin_row_meta', $plugin_meta, $plugin_file, $addon, $addon['status']);
									echo implode( ' | ', $plugin_meta );
									?>
								</p>
							</div>
						</div> <!-- end details -->
						<div class="actions">
							<div class="status">
							<?php
								if ( $addon['License'] == 'free' ) {
									$license_label = __( 'Free', 'paid-memberships-pro' );
								} elseif( $addon['License'] == 'standard' ) {
									$license_label = __( 'Standard', 'paid-memberships-pro' );
								} elseif( $addon['License'] == 'plus' ) {
									$license_label = __( 'Plus', 'paid-memberships-pro' );
								} elseif( $addon['License'] == 'builder' ) {
									$license_label = __( 'Builder', 'paid-memberships-pro' );
								} elseif( $addon['License'] == 'wordpress.org' ) {
									$license_label = __( 'Free', 'paid-memberships-pro' );
								} else {
									$license_label = false;
								}
								if ( ! empty( $license_label ) ) { ?>
									<p class="add-on-license-type">
										<?php
											if ( in_array( $addon['License'], array( 'free', 'wordpress.org' ) ) ) {
												echo '<strong class="license-' . esc_attr( $addon['License'] ) . '">' . wp_kses_post( $license_label ) . '</strong>';
											} else {
												printf(
													/* translators: %s - Add On license label. */
													esc_html__( 'License: %s', 'paid-memberships-pro' ),
													'<strong class="license-' . esc_attr( $addon['License'] ) . '">' . wp_kses_post( $license_label ) . '</strong>'
												);
											}
										?>
									</p> <!-- end add-on-license-type -->
							<?php } ?>
							<?php
								if ( $addon['status'] === 'uninstalled' ) {
									$status_label = __( 'Not Installed', 'paid-memberships-pro' );
								} elseif ( $addon['status'] === 'active' ) {
									$status_label = __( 'Active', 'paid-memberships-pro' );
								} elseif ( $addon['status'] === 'inactive' ) {
									$status_label = __( 'Inactive', 'paid-memberships-pro' );
								} else {
									$status_label = false;
								}
								if ( ! empty( $status_label ) ) { ?>
									<p class="add-on-status">
										<?php
											printf(
												/* translators: %s - Add On status label. */
												esc_html__( 'Status: %s', 'paid-memberships-pro' ),
												'<strong class="status-' . esc_attr( $addon['status'] ) . '">' . wp_kses_post( $status_label ) . '</strong>'
											);
										?>
									</p>
							<?php } ?>
							</div> <!-- end status -->
							<div class="action-button">
								<?php
									$action_button = array(
										'label' => '',
										'style' => 'button pmproAddOnActionButton',
										'hidden_fields' => array()
									);

									if ( ! empty( $addon['needs_update'] ) ) {
										$action_button['label'] = __( 'Update Now', 'paid-memberships-pro' );
										if ( empty( $addon['access'] ) ) {
											// Can't update it. Popup.
											$action_button['hidden_fields']['pmproAddOnAdminAction'] = 'license';
											$action_button['hidden_fields']['pmproAddOnAdminName'] = $addon['ShortName'];
											$action_button['hidden_fields']['pmproAddOnAdminLicense'] = ucwords( $addon['License' ] );
										} else {
											$action_button['hidden_fields']['pmproAddOnAdminAction'] = 'update';
											$action_button['hidden_fields']['pmproAddOnAdminActionUrl'] = wp_nonce_url(
													self_admin_url(
														add_query_arg( array(
															'action' => 'upgrade-plugin',
															'plugin' => $plugin_file
														),
														'update.php'
													)
												),
												'upgrade-plugin_' . $plugin_file
											);
										}
									} elseif ( $addon['status'] === 'uninstalled' ) {
										$action_button['label'] = __( 'Install', 'paid-memberships-pro' );
										if ( empty( $addon['access'] ) ) {
											// Can't update it. Popup.
											$action_button['hidden_fields']['pmproAddOnAdminAction'] = 'license';
											$action_button['hidden_fields']['pmproAddOnAdminName'] = $addon['ShortName'];
											$action_button['hidden_fields']['pmproAddOnAdminLicense'] = ucwords( $addon['License' ] );
										} else {
											$action_button['hidden_fields']['pmproAddOnAdminAction'] = 'install';
											$action_button['hidden_fields']['pmproAddOnAdminActionUrl'] = wp_nonce_url(
												self_admin_url(
													add_query_arg( array(
														'action' => 'install-plugin',
														'plugin' => $addon['Slug']
													),
													'update.php'
													)
												),
												'install-plugin_' . $addon['Slug']
											);
										}
									} elseif ( $addon['status'] === 'inactive' ) {
										$action_button['label'] = __( 'Activate', 'paid-memberships-pro' );
										$action_button['hidden_fields']['pmproAddOnAdminAction'] = 'activate';
										$action_button['hidden_fields']['pmproAddOnAdminActionUrl'] = wp_nonce_url(
											self_admin_url(
												add_query_arg( array(
													'action' => 'activate',
													'plugin' => $plugin_file
												),
												'plugins.php'
												)
											),
											'activate-plugin_' . $plugin_file
										);
									} elseif ( $addon['status'] === 'active' ) {
										$actions = apply_filters( 'plugin_action_links_' . $plugin_file, array(), $plugin_file, $addon, $addon['status'] );
										if ( ! empty( $actions ) ) {
											$action_button = str_replace( '<a ', '<a class="button" ', $actions[0] );
										} else {
											$action_button['label'] = __( 'Active', 'paid-memberships-pro' );
											$action_button['style'] .= ' disabled';
										}
									}

									if ( is_array( $action_button ) ) {
										?>
										<a class="<?php echo esc_attr( $action_button['style'] ); ?>" ><?php echo esc_html( $action_button['label'] ); ?></a>
										<?php
										if ( ! empty( $action_button['hidden_fields'] ) ) {
											foreach ( $action_button['hidden_fields'] as $name => $value ) {
												?>
												<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" />
												<?php
											}
										}
									} else {
										echo $action_button;
									}
								?>
							</div> <!-- end action-button -->
						</div> <!-- end actions -->
					</div> <!-- end add-on-item -->
				</div> <!-- end add-on-container -->
				<?php
				}
			?>
			</div> <!-- end list -->
		</div> <!-- end pmpro-admin-add-ons-list -->
		<script>
			jQuery(document).ready( function($) {

				/**
				 * Catches native clear search form event and run custom code.
				 */
				$('[data-search]').on('search', () => {
					clearSearch($( '.addons-search' ))
				});

				/**
				 * Clear search.
				 */
				const clearSearch = ($addonsSearch) => {
					$('#pmpro-no-add-ons').hide();
					$addonsSearch.hide();
					const current = location.hash.split("#")[1] || 'all';
					$( `.filter-links li a[href="#${current}"` ).trigger('click');
				};

				/**
				 * Add-on search.
				 */
				$('[data-search]').on('keyup', (ev) => {
					const MIN_SEARCH_LENGTH = 3;
					const $input = $(ev.currentTarget);
					const searchTerms = $input.val().toLowerCase().split( ' ' ).filter( term => term !== '' && term.length >= MIN_SEARCH_LENGTH );
					$addonsSearch = $( '.addons-search' );

					if (searchTerms.length === 0) {
						clearSearch($( '.addons-search' ));
						return;
					}

					$addonsSearch.closest( '.filter-links' ).find( 'li a' ).removeClass( 'current' );
					$addonsSearch.addClass( 'current' ).show();

					const filter = $input.data('search');
					const $allItemsArray = $(`[data-search-${filter}]`);
					$allItemsArray.hide();

					const filteredItems = $allItemsArray.filter((index,element) => {
						const addonsSearchableDescription = $(element).data(`search-${filter}`).toLowerCase();
						return searchTerms.some((term) => addonsSearchableDescription.includes(term));
					});

					if( filteredItems.length > 0 ) {
						filteredItems.show();
						$('#pmpro-no-add-ons').hide();
					 } else {
						$('#pmpro-no-add-ons').show();
					}
				});

				/**
				 * Handles clicks on filter addons links.
				 */
				$('.filter-links li a' ).click( function(e) {
					// don't want to jump to #
					e.preventDefault();

					var views = $( this ).closest( '.filter-links' );
					var view = $(this).data('search');
					var view_items = $(`[data-search-${view}]`);
					var view_val = $(this).data('view');

					// Update the URL hash.
					$( this ).attr( 'href' ).replace( /#/, '' );

					// Unstyle view links
					views.find( 'li a' ).removeClass( 'current' );
					$( this ).addClass( 'current' );
					views.find('.addons-search').hide();

					// Clear the search input, if full.
					jQuery( '#search-add-ons' ).value = '';

					// update the URL
					if ( history.pushState ) {
					    history.pushState( null, null, '#' + view_val );
					} else {
					    location.hash = '#' + view_val;
					}

					if ( view_val != '' ) {
						view_items.hide();
						$(`[data-search-${view}*="${view_val.toLowerCase()}"]`).show();
					} else {
						view_items.show();
					}

				});

				// check if we should switch Add On content on page loads
				$( 'a[data-toggle="view"][href="' + window.location.hash + '"]' ).click();

			});
		</script>
	</div> <!-- end pmpro-admin-add-ons -->
	<div id="pmpro-popup" class="pmpro-popup-overlay">
		<span class="pmpro-popup-helper"></span>
		<div class="pmpro-popup-wrap">
			<span id="pmpro-popup-inner">
				<a class="pmproPopupCloseButton" href="#" title="<?php esc_attr_e( 'Close Popup', 'paid-memberships-pro' ); ?>"><span class="dashicons dashicons-no"></span></a>
				<a title="Paid Memberships Pro - Membership Plugin for WordPress" target="_blank" rel="noopener noreferrer" href="https://www.paidmembershipspro.com/pricing/?utm_source=plugin&utm_medium=pmpro-addons&utm_campaign=pricing&utm_content=pmpro-popup"><img src="<?php echo esc_url( PMPRO_URL . '/images/Paid-Memberships-Pro.png' ); ?>" width="350" height="75" border="0" alt="Paid Memberships Pro(c) - All Rights Reserved" /></a>
				<h1><?php printf(__( 'Get %s and more with a %s license.', 'paid-memberships-pro' ), '<strong id="addon-name"></strong>', '<strong id="addon-license"></strong>' ); ?></h1>
				<p><a class="button button-primary button-hero" href="https://www.paidmembershipspro.com/pricing/?utm_source=plugin&utm_medium=pmpro-addons&utm_campaign=pricing&utm_content=pmpro-popup"><strong><?php esc_html_e( 'View Plans and Pricing', 'paid-memberships-pro' ); ?></strong></a></p>
				<p><?php printf(__( 'Already purchased? <a href="%s">Enter your license key here</a>', 'paid-memberships-pro' ), admin_url( 'admin.php?page=pmpro-license' ) ); ?></p>
			</span>
		</div>
	</div> <!-- end pmpro-popup -->
<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");
	wp_print_request_filesystem_credentials_modal();
