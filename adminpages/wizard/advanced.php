<?php 
// Get options.
$filter_queries = pmpro_getOption( 'filterqueries', true );
$show_excerpts = pmpro_getOption( 'showexcerpts', true );
$spam_protection = pmpro_getOption( 'spamprotection', true );
$wisdom_tracking = pmpro_getOption( 'wisdom_opt_out', true );
?>
<div class="pmpro-wizard__step pmpro-wizard__step-4">
	<form action="" method="post">
		<div class="pmpro-wizard__step-header">
			<h1><?php esc_html_e( 'Advanced Settings', 'paid-memberships-pro' ); ?></h1>
			<p><?php esc_html_e( 'Configure advanced settings relating to your membership site. You can configure additional settings later.', 'paid-memberships-pro' ); ?></p>
		</div>
		<div class="pmpro-wizard__field">
			<label class="pmpro-wizard__label-block">
				<?php esc_html_e( 'Filter searches and archives?', 'paid-memberships-pro' ); ?>
			</label>
			<select name="filterqueries" id="filterqueries" class="pmpro-wizard__field-block">
				<option value="0" <?php selected( 0, $filter_queries); ?>><?php esc_html_e( 'No - Non-members will see restricted posts/pages in searches and archives.', 'paid-memberships-pro' ); ?></option>
				<option value="1" <?php selected( 1, $filter_queries ); ?>><?php esc_html_e( 'Yes - Only members will see restricted posts/pages in searches and archives.', 'paid-memberships-pro' ); ?></option>
			</select><br><br>
			<label class="pmpro-wizard__label-block">
				<?php esc_html_e( 'Show excerpts to non-members?', 'paid-memberships-pro' ); ?>
			</label>
			<select name="showexcerpts" id="showexcerpts" class="pmpro-wizard__field-block">
				<option value="0" <?php selected( 0, $show_excerpts ); ?>><?php esc_html_e( 'No - Hide excerpts.', 'paid-memberships-pro' ); ?></option>
				<option value="1" <?php selected( 1, $show_excerpts ); ?>><?php esc_html_e( 'Yes - Show excerpts.', 'paid-memberships-pro' ); ?></option>
			</select>
		</div>
		<div class="pmpro-wizard__field">
			<label class="pmpro-wizard__label-block">
				<input type="checkbox" name="spamprotection" id="spamprotection" value="2" <?php checked( 2, $spam_protection ); ?>>
				<?php esc_html_e( 'Enable Spam Protection', 'paid-memberships-pro' ); ?>
			</label>
			<p class="pmpro-wizard__field-description"><?php esc_html_e( 'Block IPs from checkout if there are more than 10 failures within 15 minutes.', 'paid-memberships-pro' ); ?></p>
		</div>
		
		<div class="pmpro-wizard__field">
			<label class="pmpro-wizard__label-block">
				<?php esc_html_e( 'Enable Tracking?', 'paid-memberships-pro' ); ?>
			</label>
			<select name="wisdom_opt_out" id="wisdom_opt_out" class="pmpro-wizard__field-block">
				<option value="0" <?php selected( 0, $wisdom_tracking ); ?>><?php esc_html_e( 'Yes - Allow usage of Paid Memberships Pro to be tracked.', 'paid-memberships-pro' ); ?></option>
				<option value="1" <?php selected( 1, $wisdom_tracking ); ?>><?php esc_html_e( 'No - Do not track usage of Paid Memberships Pro on my site.', 'paid-memberships-pro' ); ?></option>
			</select>
			<p class="pmpro-wizard__field-description"><?php esc_html_e( 'Sharing non-sensitive membership site data helps us analyze how our plugin is meeting your needs and identify opportunities to improve. Can be turned off under "Advanced" Settings.', 'paid-memberships-pro' ); ?></p>
		</div>

		<p class="pmpro_wizard__submit">
			<?php wp_nonce_field( 'pmpro_wizard_step_4_nonce', 'pmpro_wizard_step_4_nonce' ); ?>
			<input type="hidden" name="wizard-action" id="wizard-action" value="step-4"/>
			<input type="submit" name="submit" class="button button-primary button-hero" value="<?php esc_attr_e( 'Submit and Continue', 'paid-memberships-pro' ); ?>" /><br/>
			<a class="pmpro_wizard__skip" href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-wizard&step=done' ) );?>"><?php esc_html_e( 'Skip', 'paid-memberships-pro' ); ?></a>
		</p>
	</form>
</div> <!-- end pmpro-wizard__step-4 -->