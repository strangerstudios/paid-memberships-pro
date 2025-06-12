<?php 
// Get options.
$filter_queries = get_option( 'pmpro_filterqueries' );
$show_excerpts = get_option( 'pmpro_showexcerpts' );
$hide_toolbar = get_option( 'pmpro_hide_toolbar' );
$block_dashboard = get_option( 'pmpro_block_dashboard' );
$wisdom_tracking = get_option( 'pmpro_wisdom_opt_out' );

// Update Manager one-click install.
$um_slug         = 'pmpro-update-manager';
$um_plugin_file  = $um_slug . '/' . $um_slug . '.php';
$um_zip_url      = 'https://www.paidmembershipspro.com/wp-content/uploads/plugins/pmpro-update-manager.zip';
$um_installed    = file_exists( ABSPATH . 'wp-content/plugins/' . $um_plugin_file );
$um_active       = is_plugin_active( $um_plugin_file );
$um_install_url  = null;
$um_activate_url = null;

if ( ! $um_installed ) {
	$um_install_url = wp_nonce_url(
		self_admin_url(
			add_query_arg(
				array(
					'action'     => 'install-plugin',
					'plugin'     => $um_slug,
					'plugin_url' => urlencode( $um_zip_url ),
				),
				'update.php'
			)
		),
		'install-plugin_' . $um_slug
	);
} elseif ( ! $um_active ) {
	$um_activate_url = wp_nonce_url(
		self_admin_url(
			add_query_arg(
				array(
					'action' => 'activate',
					'plugin' => $um_plugin_file,
				),
				'plugins.php'
			)
		),
		'activate-plugin_' . $um_plugin_file
	);
}

?>
<div class="pmpro-wizard__step pmpro-wizard__step-4">
	<form action="" method="post">
		<div class="pmpro-wizard__step-header">
			<h2><?php esc_html_e( 'Advanced Settings', 'paid-memberships-pro' ); ?></h2>
			<p><?php esc_html_e( 'Configure advanced settings relating to your membership site. You can configure additional settings later.', 'paid-memberships-pro' ); ?></p>
		</div>
		<div class="pmpro-wizard__field">
			<h3><?php esc_html_e( 'Update Manager', 'paid-memberships-pro' ); ?></h3>
			<p><?php esc_html_e( 'Keep your Paid Memberships Pro add ons up to date with the Update Manager plugin.', 'paid-memberships-pro' ); ?></p>
			<?php if ( ! $um_installed ) : ?>
				<button type="button"
					id="pmpro-install-um-btn"
					class="button button-primary"
					data-zip-url="<?php echo esc_url( $um_zip_url ); ?>">
					<?php esc_html_e( 'Install & Activate Update Manager', 'paid-memberships-pro' ); ?>
				</button>
			<?php elseif ( ! $um_active ) : ?>
				<button type="button"
					id="pmpro-activate-um-btn"
					class="button button-primary">
					<?php esc_html_e( 'Activate Update Manager', 'paid-memberships-pro' ); ?>
				</button>
			<?php else : ?>
				<span class="button button-disabled" disabled="disabled">
					<?php esc_html_e( 'Update Manager is Active', 'paid-memberships-pro' ); ?>
				</span>
			<?php endif; ?>
		</div>		
		<div class="pmpro-wizard__field">
			<label for="filterqueries" class="pmpro-wizard__label-block">
				<?php esc_html_e( 'Filter searches and archives?', 'paid-memberships-pro' ); ?>
			</label>
			<select name="filterqueries" id="filterqueries" class="pmpro-wizard__field-block">
				<option value="0" <?php selected( 0, $filter_queries); ?>><?php esc_html_e( 'No - Non-members will see restricted posts/pages in searches and archives.', 'paid-memberships-pro' ); ?></option>
				<option value="1" <?php selected( 1, $filter_queries ); ?>><?php esc_html_e( 'Yes - Only members will see restricted posts/pages in searches and archives.', 'paid-memberships-pro' ); ?></option>
			</select><br><br>
			<label for="showexcerpts" class="pmpro-wizard__label-block">
				<?php esc_html_e( 'Show excerpts to non-members?', 'paid-memberships-pro' ); ?>
			</label>
			<select name="showexcerpts" id="showexcerpts" class="pmpro-wizard__field-block">
				<option value="0" <?php selected( 0, $show_excerpts ); ?>><?php esc_html_e( 'No - Hide excerpts.', 'paid-memberships-pro' ); ?></option>
				<option value="1" <?php selected( 1, $show_excerpts ); ?>><?php esc_html_e( 'Yes - Show excerpts.', 'paid-memberships-pro' ); ?></option>
			</select>
		</div>
		<div class="pmpro-wizard__field">
			<label class="pmpro-wizard__label-block" for="block_dashboard">
				<input type="checkbox" name="block_dashboard" id="block_dashboard" value="yes" <?php checked( $block_dashboard, 'yes' ); ?> />
				<?php esc_html_e( 'Block all users with the Subscriber role from accessing the Dashboard.', 'paid-memberships-pro' ); ?>
			</label><br><br>
			<label class="pmpro-wizard__label-block" for="hide_toolbar">
				<input type="checkbox" name="hide_toolbar" id="hide_toolbar" value="yes" <?php checked( $hide_toolbar, 'yes' ); ?> />
				<?php esc_html_e( 'Hide the Toolbar from all users with the Subscriber role.', 'paid-memberships-pro' ); ?>
			</label>
		</div>
		<div class="pmpro-wizard__field">
			<label for="wisdom_opt_out" class="pmpro-wizard__label-block">
				<?php esc_html_e( 'Enable Plugin Usage Data Sharing', 'paid-memberships-pro' ); ?>
			</label>
			<p class="pmpro-wizard__field-description"><?php esc_html_e( 'Sharing non-sensitive plugin settings data with us helps us better understand how our plugin is meeting your needs and helps us identify opportunities to improve. This can be turned off anytime under Advanced settings.', 'paid-memberships-pro' ); ?></p>
			<select name="wisdom_opt_out" id="wisdom_opt_out" class="pmpro-wizard__field-block">
				<option value="0" <?php selected( 0, $wisdom_tracking ); ?>><?php esc_html_e( 'Yes - Allow usage of Paid Memberships Pro to be shared with us.', 'paid-memberships-pro' ); ?></option>
				<option value="1" <?php selected( 1, $wisdom_tracking ); ?>><?php esc_html_e( 'No - Do not share usage data for Paid Memberships Pro on my site.', 'paid-memberships-pro' ); ?></option>
			</select>
		</div>

		<p class="pmpro_wizard__submit">
			<?php wp_nonce_field( 'pmpro_wizard_step_4_nonce', 'pmpro_wizard_step_4_nonce' ); ?>
			<input type="hidden" name="wizard-action" id="wizard-action" value="step-4"/>
			<input type="submit" name="submit" class="button button-primary button-hero" value="<?php esc_attr_e( 'Submit and Continue', 'paid-memberships-pro' ); ?>" /><br/>
			<a class="pmpro_wizard__skip" href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-wizard&step=done' ) );?>"><?php esc_html_e( 'Skip', 'paid-memberships-pro' ); ?></a>
		</p>
	</form>
</div> <!-- end pmpro-wizard__step-4 -->

<script>
jQuery(document).ready(function($){
	// Generic function to handle plugin install/activate
	function handlePluginAction($btn, action, zipUrl, pluginSlug) {
		var originalText = $btn.text();
		var loadingText = action === 'install' ? 'Installing...' : 'Activating...';
		
		$btn.prop('disabled', true).text(loadingText);

		var postData = {
			action: 'pmpro_install_and_activate',
			nonce: '<?php echo wp_create_nonce('pmpro_um_install'); ?>',
			zip_url: zipUrl || ''
		};

		// Add plugin_slug if provided
		if (pluginSlug) {
			postData.plugin_slug = pluginSlug;
		}

		$.post(ajaxurl, postData, function(response){
			console.log(response);
			if(response.success){
				$btn.text('Activated');
			} else {
				$btn.text('Error');
				$btn.prop('disabled', false).text(originalText);
			}
		});
	}

	// Install & Activate button
	$('#pmpro-install-um-btn').on('click', function(){
		var $btn = $(this);
		var zipUrl = $btn.data('zip-url');
		var pluginSlug = $btn.data('plugin-slug'); // Optional
		
		handlePluginAction($btn, 'install', zipUrl, pluginSlug);
	});

	// Activate only button
	$('#pmpro-activate-um-btn').on('click', function(){
		var $btn = $(this);
		var pluginSlug = $btn.data('plugin-slug'); // Optional
		
		handlePluginAction($btn, 'activate', '', pluginSlug);
	});

	// Generic handler for any plugin install/activate button
	$(document).on('click', '[data-pmpro-plugin-action]', function(){
		var $btn = $(this);
		var action = $btn.data('pmpro-plugin-action'); // 'install' or 'activate'
		var zipUrl = $btn.data('zip-url') || '';
		var pluginSlug = $btn.data('plugin-slug');
		
		handlePluginAction($btn, action, zipUrl, pluginSlug);
	});
});
</script>