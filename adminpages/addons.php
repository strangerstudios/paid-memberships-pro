<?php
	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_addons")))
	{
		die(__("You do not have permissions to perform this action.", 'paid-memberships-pro' ));
	}	
	
	global $wpdb, $msg, $msgt, $pmpro_addons;
	
	wp_enqueue_script( 'plugin-install' );
	add_thickbox();
	wp_enqueue_script( 'updates' );
	
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

/* NOTE: This code probably goes away with the JavaScript filtering */
	// Get plugin view for filters
	if ( ! empty($_REQUEST['view']))
		$view = pmpro_sanitize_with_safelist($_REQUEST['view'], array('', 'all', 'popular', 'free', 'premium', 'search' ));

	// Make sure we have an approved view.
	$approved_views = array('all', 'popular', 'free', 'premium', 'search' );
	if ( empty( $view ) || ! in_array( $view, $approved_views ) ) {
		$view = 'all';
	}
/* END NOTE */

	// Get Add On groups for filtering
	$popular_addons = pmpro_get_addons_by_category( 'popular' );
	$free_addons = pmpro_get_addons_by_license_types( array( 'free', 'wordpress.org' ) );
	$premium_addons = pmpro_get_addons_by_license_types( pmpro_license_get_premium_types() );

	// If search term in URL, pass it to a variable.
	if ( isset( $_REQUEST['s'] ) ) {
		$s = $_REQUEST['s'];
	} else {
		$s = false;
	}

	// Build array of Visible Add Ons.
	$all_visible_addons = array();
	foreach ( $addons as $addon ) {
		// Build Visible array.
		if ( empty ( $addon['HideFromAddOnsList'] ) ) {
			$all_visible_addons[] = $addon;
		}
	}

	?>
	<hr class="wp-header-end">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Add Ons', 'paid-memberships-pro' ); ?></h1>
	<div id="pmpro-admin-add-ons">
		<?php
			pmpro_showMessage();
		?>
		<div class="wp-filter">
			<ul class="filter-links">
				<?php
					if ( ! empty( $s ) ) { ?>
					<li class="addons-search"><a href="<?php echo add_query_arg( array( 'page' => 'pmpro-addons', 'view' => 'search', 's' => $s ), admin_url( 'admin.php' ) ); ?>" class="current"><?php esc_html_e('Search Results', 'paid-memberships-pro' ); ?></a></li>
					<?php }
				?>
				<li class="addons-all"><a href="<?php echo add_query_arg( array( 'page' => 'pmpro-addons', 'view' => 'all' ), admin_url( 'admin.php' ) ); ?>" <?php if(empty($view) || $view == "all") { ?>class="current"<?php } ?>><?php esc_html_e('All', 'paid-memberships-pro' ); ?></a></li>
				<li class="addons-popular"><a href="<?php echo add_query_arg( array( 'page' => 'pmpro-addons', 'view' => 'popular' ), admin_url( 'admin.php' ) ); ?>" <?php if( ! empty( $view ) && $view == "popular") { ?>class="current"<?php } ?>><?php esc_html_e( 'Popular', 'paid-memberships-pro' ); ?></a></li>
				<li class="addons-free"><a href="<?php echo add_query_arg( array( 'page' => 'pmpro-addons', 'view' => 'free' ), admin_url( 'admin.php' ) ); ?>" <?php if( ! empty( $view ) && $view == "free") { ?>class="current"<?php } ?>><?php esc_html_e( 'Free', 'paid-memberships-pro' ); ?></a></li>
				<li class="addons-premium"><a href="<?php echo add_query_arg( array( 'page' => 'pmpro-addons', 'view' => 'premium' ), admin_url( 'admin.php' ) ); ?>" <?php if( ! empty( $view ) && $view == "premium") { ?>class="current"<?php } ?>><?php esc_html_e( 'Premium', 'paid-memberships-pro' ); ?></a></li>
			</ul>
			<form class="search-form search-plugins" method="get">
				<input type="hidden" name="tab" value="search">
				<label class="screen-reader-text" for="search-plugins"><?php esc_html_e( 'Search Add On', 'paid-memberships-pro' ); ?></label>
				<input type="search" name="s" id="search-add-ons" data-search="content" value="<?php echo esc_attr( $s ); ?>" class="wp-filter-search" placeholder="<?php esc_attr_e( 'Search Add Ons...', 'paid-memberships-pro' ); ?>">
				<input type="submit" id="search-submit" class="button hide-if-js" value="<?php esc_attr_e( 'Search Add Ons', 'paid-memberships-pro' ); ?>">
			</form>
		</div>
		<?php
			// Which Add Ons to show?
			if ( $view == 'free' ) {
				$addons = $free_addons;
			} elseif ( $view == 'premium' ) {
				$addons = $premium_addons;
			} elseif ( $view == 'popular' ) {
				$addons = $popular_addons;
			} else {
				$addons = $all_visible_addons;
			}
		?>
		<div class="tablenav top">
			<div class="alignleft actions">
				<p>
					<?php printf(__('Last checked on %s at %s.', 'paid-memberships-pro' ), date_i18n(get_option('date_format'), $addons_timestamp), date_i18n(get_option('time_format'), $addons_timestamp));?> &nbsp;	
					<a class="button" href="<?php echo esc_url( admin_url("admin.php?page=pmpro-addons&force-check=1&view=" . $view) );?>"><?php esc_html_e('Check Again', 'paid-memberships-pro' ); ?></a>
				</p>
			</div>
			<div class="tablenav-pages one-page">
				<span class="displaying-num"><?php printf( __( '%d Add Ons found.', 'paid-memberships-pro' ), count( $addons ) ); ?></span>
			</div>
		</div>
		<br class="clear">
		<?php
			// No Add Ons for this filter?
			if ( count( $addons ) < 1 ) { ?>
				<p><?php esc_html_e('No Add Ons found.', 'paid-memberships-pro' ); ?></p>
			<?php } else { ?>
				<div id="pmpro-admin-add-ons-list">
					<div class="list">
						<?php foreach ( $addons as $addon ) {
							$plugin_file = $addon['Slug'] . '/' . $addon['Slug'] . '.php';
							$plugin_file_abs = ABSPATH . 'wp-content/plugins/' . $plugin_file;

							// Make sure plugin value is set.
							if ( empty( $addon['plugin'] ) ) {
								$addon['plugin'] = $plugin_file;
							}

							// Set the src of the icon for this Add On.
							$addon['plugin_icon_src'] = esc_url( PMPRO_URL . '/images/add-ons/' . $addon['Slug'] . '.png' );

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
								$classes[] = 'add-on-' . $addon['needs_update'];
							}
							$class = implode( ' ', array_unique( $classes ) );
						?>
						<div id="<?php echo esc_attr( $addon['Slug'] ); ?>" class="<?php echo esc_attr( $class ); ?>" data-search-content="<?php echo esc_attr( $addon['Name'] ); ?> <?php echo esc_attr( $addon['Slug'] ); ?> <?php echo esc_attr( $addon['Description'] ); ?>" data-search-license="<?php echo esc_attr( $addon['License'] ); ?>">
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
										<img src="<?php echo $addon['plugin_icon_src']; ?>" alt="<?php $addon['Name']; ?>">
										<?php if ( ! empty( $addon['PluginURI'] ) ) { ?>
											</a>
										<?php } ?>
									<?php } ?>
									<h5 class="add-on-name">
										<?php if ( ! empty( $addon['PluginURI'] ) ) { ?>
											<a target="_blank" href="<?php echo esc_url( $plugin_link ); ?>">
										<?php } ?>
										<?php echo $addon['ShortName']; ?>
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
													$author = '<a href="' . $addon['AuthorURI'] . '">' . $addon['Author'] . '</a>';
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
													printf(
														/* translators: %s - Add On license label. */
														esc_html__( 'License: %s', 'paid-memberships-pro' ),
														'<strong class="license-' . esc_attr( $addon['License'] ) . '">' . wp_kses_post( $license_label ) . '</strong>'
													);
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
											$action_button = array();
											if ( ! empty( $addon['needs_update'] ) ) {
												$action_button['label'] = __( 'Update Now', 'paid-memberships-pro' );
												if ( empty( $addon['access'] ) ) {
													// Can't update it. Popup.
													$action_button['url'] = "javascript:upgradePopup( '" . $addon['ShortName'] . "', '" . ucwords( $addon['License' ] ) . "' );";
													$action_button['style'] = 'button button-primary';
												} else {
													$action_button['url'] = self_admin_url(
															add_query_arg( array(
																'plugin_status' => 'upgrade',
															),
															'plugins.php'
														)
													);
													$action_button['style'] = 'button button-primary';
												}
											} elseif ( $addon['status'] === 'uninstalled' ) {
												$action_button['label'] = __( 'Install', 'paid-memberships-pro' );
												if ( empty( $addon['access'] ) ) {
													// Can't install it. Popup.
													$action_button['url'] = "javascript:upgradePopup( '" . $addon['ShortName'] . "', '" . ucwords( $addon['License' ] ) . "' );";
													$action_button['style'] = 'button button-primary';
												} else {
													$action_button['url'] = wp_nonce_url(
														self_admin_url(
															add_query_arg( array(
																'action' => 'install-plugin',
																'plugin' => $addon['Slug'],
															),
															'update.php',
															)
														),
														'install-plugin_' . $addon['Slug']
													);
													$action_button['style'] = 'button';
												}
											} elseif ( $addon['status'] === 'inactive' ) {
												$action_button['label'] = __( 'Activate', 'paid-memberships-pro' );
												$action_button['url'] = wp_nonce_url(
													self_admin_url(
														add_query_arg( array(
															'action' => 'activate',
															'plugin' => $plugin_file,
														),
														'plugins.php',
														)
													),
													'activate-plugin_' . $plugin_file
												);
												$action_button['style'] = 'button';
											} elseif ( $addon['status'] === 'active' ) {
												$actions = apply_filters( 'plugin_action_links_' . $plugin_file, array(), $plugin_file, $addon, $addon['status'] );
												if ( ! empty( $actions ) ) {
													$action_button = str_replace( '<a ', '<a class="button" ', $actions[0] );
												} else {
													$action_button['label'] = __( 'Active', 'paid-memberships-pro' );
													$action_button['url'] = '#';
													$action_button['style'] = 'button disabled';
												}
											}

											if ( is_array( $action_button ) ) { ?>
												<a href="<?php echo esc_attr( $action_button['url'] ); ?>" class="<?php echo esc_attr( $action_button['style'] ); ?>"><?php echo esc_html( $action_button['label'] ); ?></a>
											<?php } else { 
												echo $action_button;
											}
										?>
									</div> <!-- end action-button -->
								</div> <!-- end actions -->
							</div> <!-- end add-on-item -->
						</div> <!-- end add-on-container -->
							<?php
							/*
							ob_start();
							wp_plugin_update_row( $plugin_file, $plugin_data );
							$row = ob_get_contents();
							ob_end_clean();
							echo str_replace('colspan="0"', 'colspan="4"', $row);
							*/
						}
					?>
				</div> <!-- end list -->
			</div> <!-- end pmpro-admin-add-ons-list -->
			<script>
				jQuery(document).ready( function($) {
					$('[data-search]').keyup(function() {
						jQuery('.addons-search').show();
						var filter = $(this).data('search');
						var filter_items = $(`[data-search-${filter}]`);
						var search_val = $(this).val();

						if ( search_val != '' ) {
							filter_items.addClass('search-hide');
							$(`[data-search-${filter}*="${search_val.toLowerCase()}"]`).removeClass('search-hide');
						} else {
							filter_items.removeClass('search-hide');
							jQuery('.addons-search').hide();
						}
					});
				});
			</script>
			<?php
			}
		?>
	</div> <!-- end pmpro-admin-add-ons -->
	<script>
		jQuery( document ).ready( function() {
			//jQuery('.pmpro-popup-overlay').show();
			jQuery('.pmproPopupCloseButton').click(function() {
				jQuery('.pmpro-popup-overlay').hide();
			});
		} );
		function upgradePopup( name, license) {
			document.getElementById( 'addon-name' ).innerHTML = name;
			document.getElementById( 'addon-license' ).innerHTML = license;
			jQuery('.pmpro-popup-overlay').show();
		}
	</script>
	<div id="pmpro-popup" class="pmpro-popup-overlay">
		<span class="pmpro-popup-helper"></span>
		<div class="pmpro-popup-wrap">
			<span id="pmpro-popup-inner">
				<a class="pmproPopupCloseButton" href="#" title="<?php esc_attr_e( 'Close Popup', 'paid-memberships-pro' ); ?>"><span class="dashicons dashicons-no"></span></a>
				<a title="Paid Memberships Pro - Membership Plugin for WordPress" target="_blank" rel="noopener noreferrer" href="https://www.paidmembershipspro.com/pricing/?utm_source=plugin&utm_medium=pmpro-addons&utm_campaign=pricing&utm_content=pmpro-popup"><img src="<?php echo esc_url( PMPRO_URL . '/images/Paid-Memberships-Pro.png' ); ?>" width="350" height="75" border="0" alt="Paid Memberships Pro(c) - All Rights Reserved" /></a>
				<h1>Get <strong id="addon-name"></strong> and more with a <strong id="addon-license"></strong> license.</h1>
				<p><a class="button button-primary button-hero" href="https://www.paidmembershipspro.com/pricing/?utm_source=plugin&utm_medium=pmpro-addons&utm_campaign=pricing&utm_content=pmpro-popup"><strong><?php esc_html_e( 'View Plans and Pricing', 'paid-memberships-pro' ); ?></strong></a></p>
				<p><?php printf(__( 'Already purchased? <a href="%s">Enter your license key here &raquo;</a>', 'paid-memberships-pro' ), admin_url( 'admin.php?page=pmpro-license' ) ); ?></p>
			</span>
		</div>
	</div> <!-- end pmpro-popup -->
<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");
	wp_print_request_filesystem_credentials_modal();
?>
