<?php
/**
 * This code calls the server at www.paidmembershipspro.com to see if there are any notifications to display to the user. Notifications are shown on the PMPro settings pages in the dashboard.
 *
 */
function pmpro_notifications() {
	if ( current_user_can( 'manage_options' ) ) {
		$pmpro_notification = get_transient( 'pmpro_notification_' . PMPRO_VERSION );
		if ( empty( $pmpro_notification ) ) {
			// Set to NULL in case the below times out or fails, this way we only check once a day.
			set_transient( 'pmpro_notification_' . PMPRO_VERSION, 'NULL', 86400 );
			$pmpro_notification_url = apply_filters( 'pmpro_notifications_url', esc_url( 'https://notifications.strangerstudios.com/v2/notifications.json' ) );
			// Figure out which server to get from.
			$remote_notification = wp_remote_get( $pmpro_notification_url );

			// Get notification.
			$pmpro_notification = json_decode( wp_remote_retrieve_body( $remote_notification ) );
			
			// Update transient if we got something.
			if ( ! empty( $pmpro_notification ) ) {
				set_transient( 'pmpro_notification_' . PMPRO_VERSION, $pmpro_notification, 86400 );
			}
		}

		if ( ! empty( $pmpro_notification ) && $pmpro_notification != 'NULL' ) { 
			global $current_user;
			// Get array of hidden messages and remove from array.
			$archived_notifications = get_user_meta( $current_user->ID, 'pmpro_archived_notifications', true );

			foreach ( $pmpro_notification as $notification ) { 
				// Skip this iteration if the notification ID is inside the archived setting.
				if ( is_array( $archived_notifications ) && array_key_exists( $notification->id, $archived_notifications ) ) {
					continue;
				}?>
				<div class="pmpro_notification" id="<?php echo $notification->id; ?>">
				<?php if ( $notification->dismiss ) { ?>
					<button type="button" class="pmpro-notice-button notice-dismiss" value="<?php echo $notification->id; ?>"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'paid-memberships-pro' ); ?></span></button>
				<?php } ?>
					<div class="pmpro_notification-<?php echo $notification->type; ?>">
						<h3><span class="dashicons dashicons-<?php echo $notification->dashicon; ?>"></span> <?php echo $notification->title; ?></h3>
						<?php echo $notification->content; ?></div>
				</div>
		<?php }
		}
	}

	// Exit so we just show this content.
	exit;
}
add_action( 'wp_ajax_pmpro_notifications', 'pmpro_notifications' );

/**
 * Show Powered by Paid Memberships Pro comment (only visible in source) in the footer.
 *
*/
function pmpro_link() { ?>
Memberships powered by Paid Memberships Pro v<?php echo PMPRO_VERSION; ?>.
<?php }

function pmpro_footer_link() {
	if ( ! pmpro_getOption( 'hide_footer_link' ) ) { ?>
		<!-- <?php echo pmpro_link()?> -->
	<?php }
}
add_action( 'wp_footer', 'pmpro_footer_link' );

function pmpro_hide_notice() {
	global $current_user;
	$notification_id = sanitize_text_field( $_POST['notification_id'] );

	$archived_notifications = get_user_meta( $current_user->ID, 'pmpro_archived_notifications', true );

	if ( ! is_array( $archived_notifications ) ) {
		$archived_notifications = array();
	}

	$archived_notifications[$notification_id] = date_i18n( 'Y-m-d' );

	update_user_meta( $current_user->ID, 'pmpro_archived_notifications', $archived_notifications );
	exit;
}
add_action( 'wp_ajax_pmpro_hide_notice', 'pmpro_hide_notice' );