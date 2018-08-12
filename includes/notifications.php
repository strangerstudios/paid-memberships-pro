<?php
/*
	This code calls the server at www.paidmembershipspro.com to see if there are any notifications to display to the user. Notifications are shown on the PMPro settings pages in the dashboard.
*/
function pmpro_notifications()
{
	if(current_user_can("manage_options"))
	{
		$pmpro_notification = get_transient("pmpro_notification_" . PMPRO_VERSION);
		if(empty($pmpro_notification))
		{
			//set to NULL in case the below times out or fails, this way we only check once a day
			set_transient("pmpro_notification_" . PMPRO_VERSION, 'NULL', 86400);

			//figure out which server to get from
			if(is_ssl())
			{
				$remote_notification = wp_remote_get("https://notifications.paidmembershipspro.com/?v=" . PMPRO_VERSION);
			}
			else
			{
				$remote_notification = wp_remote_get("http://notifications.paidmembershipspro.com/?v=" . PMPRO_VERSION);
			}

			//get notification
			$pmpro_notification = wp_remote_retrieve_body($remote_notification);

			//update transient if we got something
			if(!empty($pmpro_notification))
				set_transient("pmpro_notification_" . PMPRO_VERSION, $pmpro_notification, 86400);
		}

		if($pmpro_notification && $pmpro_notification != "NULL")
		{
		?>
		<div id="pmpro_notifications">
			<?php echo $pmpro_notification; ?>
		</div>
		<?php
		}
	}

	//exit so we just show this content
	exit;
}
add_action('wp_ajax_pmpro_notifications', 'pmpro_notifications');

/**
 * Runs only when the plugin is activated.
 *
 * @since 0.1.0
 */
function pmpro_admin_notice_activation_hook() {
	// Create transient data.
	set_transient( 'pmpro-admin-notice', true, 5 );
}
register_activation_hook( PMPRO_BASE_FILE, 'pmpro_admin_notice_activation_hook' );

/**
 * Admin Notice on Activation.
 *
 * @since 0.1.0
 */
function pmpro_admin_notice() {
	// Check transient, if available display notice.
	if ( get_transient( 'pmpro-admin-notice' ) ) { ?>
		<div id="message" class="updated notice">
			<p><?php _e( '<strong>Welcome to Paid Memberships Pro</strong> &mdash; We&lsquo;re here to help you #GetPaid.', 'paid-memberships-rpo' ); ?></p>
			<p class="submit"><a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-membershiplevels&edit=-1' ) ); ?>" class="button-primary"><?php _e( 'Create Your First Membership Level', 'paid-memberships-pro' ); ?></a></p>
		</div>
		<?php
		// Delete transient, only display this notice once.
		delete_transient( 'pmpro-admin-notice' );
	}
}
add_action( 'admin_notices', 'pmpro_admin_notice' );

/*
	Show Powered by Paid Memberships Pro comment (only visible in source) in the footer.
*/
function pmpro_link()
{
?>
Memberships powered by Paid Memberships Pro v<?php echo PMPRO_VERSION?>.
<?php
}
function pmpro_footer_link()
{
	if(!pmpro_getOption("hide_footer_link"))
	{
		?>
		<!-- <?php echo pmpro_link()?> -->
		<?php
	}
}
add_action("wp_footer", "pmpro_footer_link");
