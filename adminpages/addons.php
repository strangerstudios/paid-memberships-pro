<?php
	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_addons")))
	{
		die(__("You do not have permissions to perform this action.", "pmpro"));
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
	$addons_timestamp = get_option("pmpro_addons_timestamp", false);
	$plugin_info = get_site_transient( 'update_plugins' );
	$pmpro_license_key = get_option("pmpro_license_key", "");
	
	//get plugin status for filters
	if(!empty($_REQUEST['plugin_status']))
		$status = $_REQUEST['plugin_status'];
	else
		$status = "all";
	
	//split addons into groups for filtering
	$addons_all = $addons;
	$addons_active = array();
	$addons_inactive = array();
	$addons_update = array();
	$addons_uninstalled = array();

	foreach($addons as $addon)
	{
		$plugin_file = $addon['Slug'] . '/' . $addon['Slug'] . '.php';
		$plugin_file_abs = ABSPATH . 'wp-content/plugins/' . $plugin_file;
		
		//active?
		if(is_plugin_active($plugin_file))
			$addons_active[] = $addon;
		else
			$addons_inactive[] = $addon;

		//has update?
		if(isset($plugin_info->response[$plugin_file]))
			$addons_update[] = $addon;

		//not installed?
		if(!file_exists($plugin_file_abs))
			$addons_uninstalled[] = $addon;
	}
	?>
	<h2><?php _e('Add Ons', 'pmpro'); ?></h2>

	<?php
		pmpro_showMessage();
	?>
	
	<p>
		<?php printf(__('Last checked on %s at %s.', 'pmpro'), date(get_option('date_format'), $addons_timestamp), date(get_option('time_format'), $addons_timestamp));?> &nbsp;	
		<a class="button" href="<?php echo admin_url("admin.php?page=pmpro-addons&force-check=1&plugin_status=" . $status);?>"><?php _e('Check Again', 'pmpro'); ?></a>
	</p>

	<ul class="subsubsub">
		<li class="all"><a href="admin.php?page=pmpro-addons&plugin_status=all" <?php if(empty($status) || $status == "all") { ?>class="current"<?php } ?>><?php _e('All', 'pmpro'); ?> <span class="count">(<?php echo count($addons);?>)</span></a> |</li>
		<li class="active"><a href="admin.php?page=pmpro-addons&plugin_status=active" <?php if($status == "active") { ?>class="current"<?php } ?>><?php _e('Active', 'pmpro'); ?> <span class="count">(<?php echo count($addons_active);?>)</span></a> |</li>
		<li class="inactive"><a href="admin.php?page=pmpro-addons&plugin_status=inactive" <?php if($status == "inactive") { ?>class="current"<?php } ?>><?php _e('Inactive', 'pmpro'); ?> <span class="count">(<?php echo count($addons_inactive);?>)</span></a> |</li>
		<li class="update"><a href="admin.php?page=pmpro-addons&plugin_status=update" <?php if($status == "update") { ?>class="current"<?php } ?>><?php _e('Update Available', 'pmpro'); ?><span class="count">(<?php echo count($addons_update);?>)</span></a> |</li>
		<li class="uninstalled"><a href="admin.php?page=pmpro-addons&plugin_status=uninstalled" <?php if($status == "uninstalled") { ?>class="current"<?php } ?>><?php _e('Not Installed', 'pmpro'); ?> <span class="count">(<?php echo count($addons_uninstalled);?>)</span></a></li>
	</ul>

	<br /><br />

	<table class="wp-list-table widefat plugins">
	<thead>
	<tr>
		<th scope="col" id="cb" class="manage-column column-cb check-column" style="">
			<?php /*
			<label class="screen-reader-text" for="cb-select-all-1"><?php _e('Select All'); ?></label><input id="cb-select-all-1" type="checkbox">
			*/ ?>
		</th>	
		<th scope="col" id="name" class="manage-column column-name" style=""><?php _e('Add On Name','pmpro'); ?></th>
		<th scope="col" id="type" class="manage-column column-type" style=""><?php _e('Type', 'pmpro'); ?></th>
		<th scope="col" id="description" class="manage-column column-description" style=""><?php _e('Description', 'pmpro'); ?></th>		
	</tr>
	</thead>
	<tbody id="the-list">
		<?php
			//which addons to show?
			if($status == "active")
				$addons = $addons_active;
			elseif($status == "inactive")
				$addons = $addons_inactive;
			elseif($status == "update")
				$addons = $addons_update;
			elseif($status == "uninstalled")
				$addons = $addons_uninstalled;
			else
				$addons = $addons_all;

			//no addons for this filter?
			if(count($addons) < 1)
			{
			?>
			<tr>
				<td></td>
				<td colspan="3"><p><?php _e('No Add Ons found.', 'pmpro'); ?></p></td>	
			</tr>
			<?php
			}

			foreach($addons as $addon)
			{
				$plugin_file = $addon['Slug'] . '/' . $addon['Slug'] . '.php';
				$plugin_file_abs = ABSPATH . 'wp-content/plugins/' . $plugin_file;
				
				if(file_exists($plugin_file_abs))
					$plugin_data = get_plugin_data( $plugin_file_abs, false, true); 					
				else
					$plugin_data = $addon;
				
				//make sure plugin value is set
				if(empty($plugin_data['plugin']))
					$plugin_data['plugin'] = $plugin_file;
				
				$plugin_name = $plugin_data['Name'];
				$id = sanitize_title( $plugin_name );
				$checkbox_id =  "checkbox_" . md5($plugin_name);	
								
				if(!empty($plugin_data['License']))
				{
					$context = 'uninstalled inactive';
				}
				elseif(isset($plugin_info->response[$plugin_file]))
				{
					$context = 'active update';
				}
				elseif(is_plugin_active($plugin_file))
				{
					$context = 'active';
				}
				elseif(file_exists($plugin_file_abs))
				{
					$context = 'inactive';
				}
				else
				{
					$context = false;
				}
				?>
				<tr id="<?php echo $id; ?>" class="<?php echo $context;?>" data-slug="<?php echo $id; ?>">					
					<th scope="row" class="check-column">
					<?php /*
						<label class="screen-reader-text" for="<?php echo $checkbox_id; ?>"><?php sprintf( __( 'Select %s' ), $plugin_name ); ?></label>
						<input type="checkbox" name="checked[]" value="<?php esc_attr( $plugin_file ); ?>" id="<?php echo $checkbox_id; ?>">
					*/ ?>
					</th>
					<td class="plugin-title">
						<strong><?php echo $plugin_name; ?></strong>
						<div class="row-actions visible">
						<?php
							$actions = array();
							if($context === 'uninstalled inactive')
							{
								if($plugin_data['License'] == 'wordpress.org')
								{
									//wordpress.org
									$actions['install'] = '<span class="install"><a href="' . wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=' . $plugin_data['Slug']), 'install-plugin_' . $plugin_data['Slug']) . '">' . __('Install Now', 'pmpro') . '</a></span>';
								}
								elseif($plugin_data['License'] == 'free')
								{
									//free
									$actions['install'] = '<span class="install"><a href="' . wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=' . $plugin_data['Slug']), 'install-plugin_' . $plugin_data['Slug']) . '">' . __('Install Now', 'pmpro') . '</a></span>';
									$actions['download'] = '<span class="download"><a target="_blank" href="' . $plugin_data['Download'] . '?key=' . $pmpro_license_key . '">' . __('Download', 'pmpro') . '</a></span>';
								}
								elseif(empty($pmpro_license_key))
								{
									//no key
									$actions['settings'] = '<span class="settings"><a href="' . admin_url('options-general.php?page=pmpro_license_settings') . '">' . __('Update License', 'pmpro') . '</a></span>';
									$actions['download'] = '<span class="download"><a target="_blank" href="' . $plugin_data['PluginURI'] . '">' . __('Download', 'pmpro') . '</a></span>';
								}
								elseif(pmpro_license_isValid($pmpro_license_key, $plugin_data['License']))
								{
									//valid key
									$actions['install'] = '<span class="install"><a href="' . wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=' . $plugin_data['Slug']), 'install-plugin_' . $plugin_data['Slug']) . '">' . __('Install Now', 'pmpro') . '</a></span>';
									$actions['download'] = '<span class="download"><a target="_blank" href="' . $plugin_data['Download'] . '?key=' . $pmpro_license_key . '">' . __('Download', 'pmpro') . '</a></span>';									
								}
								else
								{
									//invalid key
									$actions['settings'] = '<span class="settings"><a href="' . admin_url('options-general.php?page=pmpro_license_settings') . '">' . __('Update License', 'pmpro') . '</a></span>';
									$actions['download'] = '<span class="download"><a target="_blank" href="' . $plugin_data['PluginURI'] . '">' . __('Download', 'pmpro') . '</a></span>';
								}
							}
							elseif($context === 'active' || $context === 'active update')
							{
								$actions['deactivate'] = '<span class="deactivate"><a href="' . wp_nonce_url(self_admin_url('plugins.php?action=deactivate&plugin=' . $plugin_file), 'deactivate-plugin_' . $plugin_file ) . '" aria-label="' . esc_attr( sprintf( __( 'Deactivate %s' ), $plugin_data['Name'] ) ) . '">' . __('Deactivate') . '</a></span>';
							}
							elseif($context === 'inactive')
							{
								$actions['activate'] = '<span class="activate"><a href="' . wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=' . $plugin_file), 'activate-plugin_' . $plugin_file) . '" class="edit" aria-label="' . esc_attr( sprintf( __( 'Activate %s' ), $plugin_data['Name'] ) ) . '">' . __('Activate') . '</a></span>';
								$actions['delete'] = '<span class="delete"><a href="' . wp_nonce_url(self_admin_url('plugins.php?action=delete-selected&checked[]=' . $plugin_file), 'bulk-plugins') . '" class="delete" aria-label="' . esc_attr( sprintf( __( 'Delete %s' ), $plugin_data['Name'] ) ) . '">' . __('Delete') . '</a></span>';
							}
							$actions = apply_filters( 'plugin_action_links_' . $plugin_file, $actions, $plugin_file, $plugin_data, $context );
							echo implode(' | ',$actions);
						?>
						</div>
					</td>
					<td class="column-type">
						<?php
							if($addon['License'] == 'free')
								_e("PMPro Free", "pmpro");
							elseif($addon['License'] == 'core')
								_e("PMPro Core", "pmpro");
							elseif($addon['License'] == 'plus')
								_e("PMPro Plus", "pmpro");
							elseif($addon['License'] == 'wordpress.org')
								_e("WordPress.org", "pmpro");
							else
								_e("N/A", "pmpro");
						?>
					</td>
					<td class="column-description desc">
						<div class="plugin-description"><p><?php echo $plugin_data['Description']; ?></p></div>
						<div class="inactive second plugin-version-author-uri">
						<?php
						$plugin_meta = array();
							if ( !empty( $plugin_data['Version'] ) )
								$plugin_meta[] = sprintf( __( 'Version %s' ), $plugin_data['Version'] );
							if ( !empty( $plugin_data['Author'] ) ) {
								$author = $plugin_data['Author'];
								if ( !empty( $plugin_data['AuthorURI'] ) )
									$author = '<a href="' . $plugin_data['AuthorURI'] . '">' . $plugin_data['Author'] . '</a>';
								$plugin_meta[] = sprintf( __( 'By %s' ), $author );
							}
							// Details link using API info, if available
							if ( isset( $plugin_data['slug'] ) && current_user_can( 'install_plugins' ) ) {
								$plugin_meta[] = sprintf( '<a href="%s" class="thickbox" aria-label="%s" data-title="%s">%s</a>',
									esc_url( network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $plugin_data['slug'] .
										'&TB_iframe=true&width=600&height=550' ) ),
									esc_attr( sprintf( __( 'More information about %s' ), $plugin_name ) ),
									esc_attr( $plugin_name ),
									__( 'View details' )
								);
							} elseif ( ! empty( $plugin_data['PluginURI'] ) ) {
								$plugin_meta[] = sprintf( '<a href="%s">%s</a>',
									esc_url( $plugin_data['PluginURI'] ),
									__( 'Visit plugin site' )
								);
							}
							$plugin_meta = apply_filters( 'plugin_row_meta', $plugin_meta, $plugin_file, $plugin_data );
							echo implode( ' | ', $plugin_meta );
							?>
						</div>
					</td>					
				</tr>
				<?php
								
				ob_start();
				wp_plugin_update_row( $plugin_file, $plugin_data );
				$row = ob_get_contents();
				ob_end_clean();
				
				echo str_replace('colspan="0"', 'colspan="4"', $row);
			}
		?>
		</tbody>
	</table>				

<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");
	wp_print_request_filesystem_credentials_modal();
	echo '</div>';
?>