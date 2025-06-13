<?php 
// Get options.
$filter_queries = get_option( 'pmpro_filterqueries' );
$show_excerpts = get_option( 'pmpro_showexcerpts' );
$hide_toolbar = get_option( 'pmpro_hide_toolbar' );
$block_dashboard = get_option( 'pmpro_block_dashboard' );
$wisdom_tracking = get_option( 'pmpro_wisdom_opt_out' );

?>
<div class="pmpro-wizard__step pmpro-wizard__step-4">
	<form action="" method="post">
		<div class="pmpro-wizard__step-header">
			<h2><?php esc_html_e( 'Advanced Settings', 'paid-memberships-pro' ); ?></h2>
			<p><?php esc_html_e( 'Configure advanced settings relating to your membership site. You can configure additional settings later.', 'paid-memberships-pro' ); ?></p>
		</div>		
		<div class="pmpro-wizard__field">
			<label for ="updatemanager" class="pmpro-wizard__label-block">
				<?php esc_html_e( 'Install Update Manager', 'paid-memberships-pro' ); ?>
			</label>
			<p class="pmpro-wizard__field-description"><?php esc_html_e( 'The Update Manager is a required plugin that enables automatic updates for Paid Memberships Pro and its Add Ons, delivered securely from our official license server.', 'paid-memberships-pro' ); ?></p>
			<select name="updatemanager" id="updatemanager" class="pmpro-wizard__field-block">
				<option value="0"><?php esc_html_e( 'Yes - Install and activate the Update Manager for me.', 'paid-memberships-pro' ); ?></option>
				<option value="1"><?php esc_html_e( 'No - I\'ll install and activate it manually later.', 'paid-memberships-pro' ); ?></option>
			</select><br><br>
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
			<p class="pmpro-wizard__field-description">
				<?php esc_html_e( 'Share non-sensitive, anonymized usage data to help us improve Paid Memberships Pro. You can disable this anytime in Advanced Settings.', 'paid-memberships-pro' ); ?>
				<a aria-label="<?php esc_attr_e( 'View Paid Memberships Pro Usage Tracking documentation in a new tab', 'paid-memberships-pro' ); ?>" href="https://www.paidmembershipspro.com/privacy-policy/usage-tracking/?utm_source=plugin&utm_medium=setup-wizard&utm_campaign=wizard-advanced&utm_content=data-collection" target="_blank"><?php esc_html_e( 'Learn more about what data we collect', 'paid-memberships-pro' ); ?></a>
			</p>
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