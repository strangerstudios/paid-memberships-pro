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
	
	//some vars
	$addons = pmpro_getAddons();
	
	// Sort by ShortName
	$short_names = array_column( $addons, 'ShortName' );
	array_multisort( $short_names, SORT_ASC, SORT_STRING | SORT_FLAG_CASE, $addons );

	$addons_timestamp = get_option("pmpro_addons_timestamp", false);
	$plugin_info = get_site_transient( 'update_plugins' );
	$pmpro_license_key = get_option("pmpro_license_key", "");
	
	//get plugin status for filters
	if(!empty($_REQUEST['plugin_status']))
		$status = pmpro_sanitize_with_safelist($_REQUEST['plugin_status'], array('', 'all', 'popular', 'free', 'premium' ));

	//make sure we have an approved status
	$approved_statuses = array('all', 'popular', 'free', 'premium' );
	if ( empty( $status ) || ! in_array( $status, $approved_statuses ) ) {
		$status = 'all';
	}
	
	// Split Add Ons into groups for filtering
	$all_visible_addons = array();
	$all_hidden_addons = array();
	$popular_addons = array();
	$free_addons = array();
	$premium_addons = array();
	
	// Build array of Visible, Hidden, Popular, Free, & Premium Add Ons.
	foreach ( $addons as $addon ) {

		$plugin_file = $addon['Slug'] . '/' . $addon['Slug'] . '.php';
		$plugin_file_abs = ABSPATH . 'wp-content/plugins/' . $plugin_file;

		// Build Visible and Hidden arrays.
		if ( empty ( $addon['HideFromAddOnsList'] ) || file_exists( $plugin_file_abs ) ) {
			$all_visible_addons[] = $addon;
		} else {
			$all_hidden_addons[] = $addon;
		}

		// Build array of Popular Add Ons.
		if ( empty ( $addon['HideFromAddOnsList'] ) && in_array( $addon['Slug'], array( 'pmpro-advanced-levels-shortcode', 'pmpro-register-helper', 'pmpro-woocommerce', 'pmpro-courses', 'pmpro-member-directory', 'pmpro-subscription-delays', 'pmpro-roles', 'pmpro-add-paypal-express', 'pmpro-set-expiration-dates' ) ) ) {
			$popular_addons[] = $addon;
		}

		// Build array of Free Add Ons and Premium Add On.
		if ( ! empty ( $addon['License'] ) ) {
			if ( empty ( $addon['HideFromAddOnsList'] ) && in_array( $addon['License'], array( 'free', 'wordpress.org' ) ) ) {
				$free_addons[] = $addon;
			} elseif ( empty ( $addon['HideFromAddOnsList'] ) && in_array( $addon['License'], pmpro_license_get_premium_types() ) ) {
				$premium_addons[] = $addon;
			}
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
				<li class="addons-all"><a href="<?php echo add_query_arg( array( 'page' => 'pmpro-addons', 'plugin_status' => 'all' ), admin_url( 'admin.php' ) ); ?>" <?php if(empty($status) || $status == "all") { ?>class="current"<?php } ?>><?php esc_html_e('All', 'paid-memberships-pro' ); ?></a></li>
				<li class="addons-popular"><a href="<?php echo add_query_arg( array( 'page' => 'pmpro-addons', 'plugin_status' => 'popular' ), admin_url( 'admin.php' ) ); ?>" <?php if( ! empty( $status ) && $status == "popular") { ?>class="current"<?php } ?>><?php esc_html_e( 'Popular', 'paid-memberships-pro' ); ?></a></li>
				<li class="addons-free"><a href="<?php echo add_query_arg( array( 'page' => 'pmpro-addons', 'plugin_status' => 'free' ), admin_url( 'admin.php' ) ); ?>" <?php if( ! empty( $status ) && $status == "free") { ?>class="current"<?php } ?>><?php esc_html_e( 'Free', 'paid-memberships-pro' ); ?></a></li>
				<li class="addons-premium"><a href="<?php echo add_query_arg( array( 'page' => 'pmpro-addons', 'plugin_status' => 'premium' ), admin_url( 'admin.php' ) ); ?>" <?php if( ! empty( $status ) && $status == "premium") { ?>class="current"<?php } ?>><?php esc_html_e( 'Premium', 'paid-memberships-pro' ); ?></a></li>
			</ul>
			<form class="search-form search-plugins" method="get">
				<input type="hidden" name="tab" value="search">
				<label class="screen-reader-text" for="search-plugins"><?php esc_html_e( 'Search Add On', 'paid-memberships-pro' ); ?></label>
				<input type="search" name="s" id="search-add-ons" value="" class="wp-filter-search" placeholder="<?php esc_attr_e( 'Search Add Ons...', 'paid-memberships-pro' ); ?>">
				<input type="submit" id="search-submit" class="button hide-if-js" value="<?php esc_attr_e( 'Search Add Ons', 'paid-memberships-pro' ); ?>">
			</form>
		</div>
		<?php
			// Which Add Ons to show?
			if ( $status == 'free' ) {
				$addons = $free_addons;
			} elseif ( $status == 'premium' ) {
				$addons = $premium_addons;
			} elseif ( $status == 'popular' ) {
				$addons = $popular_addons;
			} else {
				$addons = $all_visible_addons;
			}
		?>
		<div class="tablenav top">
			<div class="alignleft actions">
				<p>
					<?php printf(__('Last checked on %s at %s.', 'paid-memberships-pro' ), date_i18n(get_option('date_format'), $addons_timestamp), date_i18n(get_option('time_format'), $addons_timestamp));?> &nbsp;	
					<a class="button" href="<?php echo esc_url( admin_url("admin.php?page=pmpro-addons&force-check=1&plugin_status=" . $status) );?>"><?php esc_html_e('Check Again', 'paid-memberships-pro' ); ?></a>
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
						<?php foreach( $addons as $addon ) {
							$plugin_file = $addon['Slug'] . '/' . $addon['Slug'] . '.php';
							$plugin_file_abs = ABSPATH . 'wp-content/plugins/' . $plugin_file;

							if ( file_exists( $plugin_file_abs ) ) {
								$plugin_data = get_plugin_data( $plugin_file_abs, false, true);	
							} else {
								$plugin_data = $addon;
							}
							
							// Make sure plugin value is set.
							if ( empty( $plugin_data['plugin'] ) ) {
								$plugin_data['plugin'] = $plugin_file;
							}

							// Set the src of the icon for this Add On.
							$plugin_data['plugin_icon_src'] = esc_url( PMPRO_URL . '/images/add-ons/' . $addon['Slug'] . '.png' );

							$plugin_name = $plugin_data['Name'];
							if ( ! empty( $addon['ShortName'] ) ) {
								$plugin_short_name = $addon['ShortName'];
							} else {
								$plugin_short_name = $addon['Name'];
							}
							$id = sanitize_title( $plugin_name );
							$checkbox_id =  "checkbox_" . md5($plugin_name);	

							// Set plugin data for 'status' and build the selectors for the Add On list items.
							$classes = array();
							$classes[] = "add-on-container";
							if ( ! empty( $plugin_data['License'] ) ) {
								$plugin_data['status'] = 'uninstalled-inactive';
								$classes[] = 'uninstalled';
								$classes[] = 'inactive';
							} elseif ( isset( $plugin_info->response[$plugin_file] ) ) {
								$plugin_data['status'] = 'active-update';
								$classes[] = 'active';
								$classes[] = 'update';
							} elseif ( is_plugin_active( $plugin_file ) ) {
								$plugin_data['status'] = 'active-';
								$classes[] = 'active';
							} elseif ( file_exists( $plugin_file_abs ) ) {
								$plugin_data['status'] = 'inactive';
								$classes[] = 'inactive';
							} else {
								$plugin_data['status'] = '';
							}

							$class = implode( ' ', array_unique( $classes ) );
							?>
						<div id="<?php echo $id; ?>" class="<?php echo esc_attr( $class ); ?>">
							<div class="add-on-item">
								<div class="details">
									<?php if ( ! empty( $plugin_data['plugin_icon_src'] ) ) { ?>
										<?php if ( ! empty( $plugin_data['PluginURI'] ) ) { ?>
											<a target="_blank" href="<?php echo esc_url( $plugin_data['PluginURI'] . '?utm_source=plugin&utm_medium=pmpro-addons&utm_campaign=add-ons&utm_content=' . $addon['Slug'] ); ?>">
										<?php } ?>
										<img src="<?php echo $plugin_data['plugin_icon_src']; ?>" alt="<?php $plugin_name; ?>">
										<?php if ( ! empty( $plugin_data['PluginURI'] ) ) { ?>
											</a>
										<?php } ?>
									<?php } ?>
									<h5 class="add-on-name">
										<?php if ( ! empty( $plugin_data['PluginURI'] ) ) { ?>
											<a target="_blank" href="<?php echo esc_url( $plugin_data['PluginURI'] . '?utm_source=plugin&utm_medium=pmpro-addons&utm_campaign=add-ons&utm_content=' . $addon['Slug'] ); ?>">
										<?php } ?>
										<?php echo $plugin_short_name; ?>
										<?php if ( ! empty( $plugin_data['PluginURI'] ) ) { ?>
											</a>
										<?php } ?>
									</h5> <!-- end add-on-name -->
									<div class="add-on-description">
										<p><?php esc_html_e( $plugin_data['Description'] ); ?></p>
										<p>
										<?php
											$plugin_meta = array();
											if ( ! empty( $plugin_data['Author'] && ! in_array( $plugin_data['Author'], array( 'Paid Memberships Pro', 'Stranger Studios' ) ) ) ) {
												$author = $plugin_data['Author'];
												if ( !empty( $plugin_data['AuthorURI'] ) )
													$author = '<a href="' . $plugin_data['AuthorURI'] . '">' . $plugin_data['Author'] . '</a>';
												$plugin_meta[] = sprintf( __( 'By %s' ), $author );
											}
											$plugin_meta = apply_filters( 'plugin_row_meta', $plugin_meta, $plugin_file, $plugin_data, $status);
											echo implode( ' | ', $plugin_meta );
											?>
										</p>
										<p class="add-on-license-type">
											<?php
												if ( $addon['License'] == 'free' ) {
													esc_html_e( 'Free', 'paid-memberships-pro' );
												} elseif( $addon['License'] == 'standard' ) {
													esc_html_e( 'Standard', 'paid-memberships-pro' );
												} elseif( $addon['License'] == 'plus' ) {
													esc_html_e( 'Plus', 'paid-memberships-pro' );
												} elseif( $addon['License'] == 'builder' ) {
													esc_html_e( 'Builder', 'paid-memberships-pro' );
												} elseif( $addon['License'] == 'wordpress.org' ) {
													esc_html_e( 'Free', 'paid-memberships-pro' );
												} else {
													esc_html_e( 'N/A', 'paid-memberships-pro' );
												}
											?>
										</p> <!-- end add-on-license-type -->
									</div>
								</div> <!-- end details -->
								<div class="actions">
									<div class="status">
										<?php
											if ( $plugin_data['status'] === 'uninstalled-inactive' ) {
												$status_label = __( 'Not Installed', 'paid-memberships-pro' );
											} elseif ( $plugin_data['status'] === 'active' ) {
												$status_label = __( 'Active', 'paid-memberships-pro' );
											} elseif ( $plugin_data['status'] === 'active-update' ) {
												$status_label = __( 'Update Available', 'paid-memberships-pro' );
											} elseif ( $plugin_data['status'] === 'inactive' ) {
												$status_label = __( 'Not Active', 'paid-memberships-pro' );
											}
										?>
										<strong>
											<?php
												printf(
													/* translators: %s - Add On status label. */
													esc_html__( 'Status: %s', 'paid-memberships-pro' ),
													'<span class="status-label status-' . esc_attr( $plugin_data['status'] ) . '">' . wp_kses_post( $status_label ) . '</span>'
												);
											?>
										</strong>
									</div>
									<div class="action-button">
									<?php
										$action_button = array();
										// The plugin is not installed or active, check license type and show install or upgrade button.
										if ( $plugin_data['status'] === 'uninstalled-inactive' ) {
											if ( $plugin_data['License'] == 'wordpress.org' ) {
												$action_button['label'] = __( 'Install', 'paid-memberships-pro' );		
												$action_button['url'] = wp_nonce_url(
													self_admin_url(
														add_query_arg( array(
															'action' => 'install-plugin',
															'plugin' => $plugin_data['Slug']
														),
														'update.php'
													),
													'install-plugin_' . $plugin_data['Slug']
													)
												);
											} elseif ( $plugin_data['License'] == 'free' ) {
												$action_button['label'] = __( 'Install', 'paid-memberships-pro' );		
												$action_button['url'] = wp_nonce_url(
													self_admin_url(
														add_query_arg( array(
															'action' => 'install-plugin',
															'plugin' => $plugin_data['Slug']
														),
														'update.php'
													),
													'install-plugin_' . $plugin_data['Slug']
													)
												);
											} elseif ( $plugin_data['License'] == 'standard' ) {
												if ( pmpro_license_isValid( null, pmpro_license_get_premium_types() ) ) {
													$action_button['label'] = __( 'Install', 'paid-memberships-pro' );		
													$action_button['url'] = wp_nonce_url(
														self_admin_url(
															add_query_arg( array(
																'action' => 'install-plugin',
																'plugin' => $plugin_data['Slug']
															),
															'update.php'
														),
														'install-plugin_' . $plugin_data['Slug']
														)
													);
												} else {
													$action_button['label'] = __( 'Upgrade', 'paid-memberships-pro' );		
													$action_button['url'] = '';
												}
											} elseif ( in_array( $plugin_data['License'], array( 'plus', 'builder' ) ) ) {
												if ( pmpro_license_isValid( null, array( 'plus', 'builder' ) ) ) {
													$action_button['label'] = __( 'Install', 'paid-memberships-pro' );		
													$action_button['url'] = wp_nonce_url(
														self_admin_url(
															add_query_arg( array(
																'action' => 'install-plugin',
																'plugin' => $plugin_data['Slug']
															),
															'update.php'
														),
														'install-plugin_' . $plugin_data['Slug']
														)
													);
												} else {
													$action_button['label'] = __( 'Upgrade', 'paid-memberships-pro' );		
													$action_button['url'] = '';
												}
											}
										} elseif ( $plugin_data['status'] === 'active' ) {
											$action_button['label'] = __( 'Active', 'paid-memberships-pro' );
											$action_button['url'] = '';
										} elseif ( $plugin_data['status'] === 'active-update' ) {
											$action_button['label'] = __( 'Update', 'paid-memberships-pro' );
											$action_button['url'] = '';
										} elseif ( $plugin_data['status'] === 'inactive' ) {
											$action_button['label'] = __( 'Activate', 'paid-memberships-pro' );
											$action_button['url'] = '';
										}
									?>
										<button href="<?php esc_attr( $action_button['url'] ); ?>"><?php echo esc_html( $action_button['label'] ); ?></button>
								</div>
								<?php
											/*
											elseif($plugin_data['License'] == 'free')
											{
												//free
												$actions['install'] = '<span class="install"><a href="' . wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=' . $plugin_data['Slug']), 'install-plugin_' . $plugin_data['Slug']) . '">' . __('Install Now', 'paid-memberships-pro' ) . '</a></span>';
												$actions['download'] = '<span class="download"><a target="_blank" href="' . $plugin_data['Download'] . '?key=' . $pmpro_license_key . '">' . __('Download', 'paid-memberships-pro' ) . '</a></span>';
											}
											elseif(empty($pmpro_license_key))
											{
												//no key
												$actions['settings'] = '<span class="settings"><a href="' . admin_url('admin.php?page=pmpro-license') . '">' . __('Update License', 'paid-memberships-pro' ) . '</a></span>';
												$actions['download'] = '<span class="download"><a target="_blank" href="' . $plugin_data['PluginURI'] . '">' . __('Download', 'paid-memberships-pro' ) . '</a></span>';
											}
											elseif(pmpro_can_download_addon_with_license($plugin_data['License']))
											{
												//valid key
												$actions['install'] = '<span class="install"><a href="' . wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=' . $plugin_data['Slug']), 'install-plugin_' . $plugin_data['Slug']) . '">' . __('Install Now', 'paid-memberships-pro' ) . '</a></span>';
												$actions['download'] = '<span class="download"><a target="_blank" href="' . $plugin_data['Download'] . '?key=' . $pmpro_license_key . '">' . __('Download', 'paid-memberships-pro' ) . '</a></span>';									
											}
											else
											{
												//invalid key
												$actions['settings'] = '<span class="settings"><a href="' . admin_url('admin.php?page=pmpro-license') . '">' . __('Update License', 'paid-memberships-pro' ) . '</a></span>';
												$actions['download'] = '<span class="download"><a target="_blank" href="' . $plugin_data['PluginURI'] . '">' . __('Download', 'paid-memberships-pro' ) . '</a></span>';
											}
										}
										elseif( $plugin_data['status'] === 'active' || $plugin_data['status'] === 'active-update')
										{
											$actions['deactivate'] = '<span class="deactivate"><a href="' . wp_nonce_url(self_admin_url('plugins.php?action=deactivate&plugin=' . $plugin_file), 'deactivate-plugin_' . $plugin_file ) . '" aria-label="' . esc_attr( sprintf( __( 'Deactivate %s' ), $plugin_data['Name'] ) ) . '">' . __('Deactivate') . '</a></span>';
										}
										elseif($plugin_data['status'] === 'inactive')
										{
											$actions['activate'] = '<span class="activate"><a href="' . wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=' . $plugin_file), 'activate-plugin_' . $plugin_file) . '" class="edit" aria-label="' . esc_attr( sprintf( __( 'Activate %s' ), $plugin_data['Name'] ) ) . '">' . __('Activate') . '</a></span>';
											$actions['delete'] = '<span class="delete"><a href="' . wp_nonce_url(self_admin_url('plugins.php?action=delete-selected&checked[]=' . $plugin_file), 'bulk-plugins') . '" class="delete" aria-label="' . esc_attr( sprintf( __( 'Delete %s' ), $plugin_data['Name'] ) ) . '">' . __('Delete') . '</a></span>';
										}
										//$actions = apply_filters( 'plugin_action_links_' . $plugin_file, $actions, $plugin_file, $plugin_data, $plugin_data['status'] );
										//echo implode(' | ',$actions);
									}
									*/
									?>
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
			<?php
			}
		?>
	</div> <!-- end pmpro-admin-add-ons -->
<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");
	wp_print_request_filesystem_credentials_modal();
?>
