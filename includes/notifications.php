<?php
/**
 * This code calls the server at notifications.paidmembershipspro.com
 * to see if there are any notifications to display to the user.
 * Notifications are shown on the PMPro settings pages in the dashboard.
 * Runs on the wp_ajax_pmpro_notifications hook.
 */
function pmpro_notifications() {
	if ( current_user_can( 'manage_options' ) ) {
		$notification = pmpro_get_next_notification();
		
		// TODO: Make sure we haven't shown a notification too recently.
		
		if ( ! empty( $notification ) ) {
		?>
		<div class="pmpro_notification" id="<?php echo $notification->id; ?>">
		<?php if ( $notification->dismissable ) { ?>
			<button type="button" class="pmpro-notice-button notice-dismiss" value="<?php echo $notification->id; ?>"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'paid-memberships-pro' ); ?></span></button>
		<?php } ?>
			<div class="pmpro_notification-<?php echo $notification->type; ?>">
				<h3><span class="dashicons dashicons-<?php echo $notification->dashicon; ?>"></span> <?php echo $notification->title; ?></h3>
				<?php echo $notification->content; ?>
			</div>
		</div>
		<?php
		}		
	}

	// Exit cause we're loading this via AJAX.
	exit;
}
add_action( 'wp_ajax_pmpro_notifications', 'pmpro_notifications' );

/**
 * Get the highest priority applicable notification from the list.
 */
function pmpro_get_next_notification() {
	global $current_user;	
	if ( empty( $current_user->ID ) ) {
		return false;
	}
	
	// Get all notifications.
	$pmpro_notifications = pmpro_get_all_notifications();
	if ( empty( $pmpro_notifications ) ) {
		return false;
	}
	
	// Filter out archived notifications.
	$pmpro_filtered_notifications = array();
	$archived_notifications = get_user_meta( $current_user->ID, 'pmpro_archived_notifications', true );
	foreach ( $pmpro_notifications as $notification ) {		
		if ( ( is_array( $archived_notifications ) && array_key_exists( $notification->id, $archived_notifications ) ) ) {
			continue;
		}

		$pmpro_filtered_notifications[] = $notification;		
	}	
	
	// Return the first one.
	if ( ! empty( $pmpro_filtered_notifications ) ) {
		$next_notification = $pmpro_filtered_notifications[0];
	} else {
		$next_notification = false;
	}
	
	return $next_notification;
}

/**
 * Get notifications from the notification server.
 */
function pmpro_get_all_notifications() {
	$pmpro_notifications = get_transient( 'pmpro_notifications_' . PMPRO_VERSION );

	if ( empty( $pmpro_notifications ) ) {
		// Set to NULL in case the below times out or fails, this way we only check once a day.
		set_transient( 'pmpro_notifications_' . PMPRO_VERSION, 'NULL', 86400 );
		
		// We use the filter to hit our testing servers.
		$pmpro_notification_url = apply_filters( 'pmpro_notifications_url', esc_url( 'https://notifications.strangerstudios.com/v2/notifications.json' ) );

		// Get notifications.
		$remote_notifications = wp_remote_get( $pmpro_notification_url );
		$pmpro_notifications = json_decode( wp_remote_retrieve_body( $remote_notifications ) );
		
		// Update transient if we got something.
		if ( ! empty( $pmpro_notifications ) ) {
			set_transient( 'pmpro_notifications_' . PMPRO_VERSION, $pmpro_notifications, 86400 );
		}
	}
	
	// We expect an array.
	if( $pmpro_notifications === 'NULL' ) {
		$pmpro_notifications = array();
	}
	
	// Filter notifications by start/end date.
	$pmpro_active_notifications = array();
	foreach( $pmpro_notifications as $notification ) {		
		$pmpro_active_notifications[] = $notification;
	}
	
	// Filter out notifications based on show/hide rules.
	$pmpro_applicable_notifications = array();
	foreach( $pmpro_active_notifications as $notification ) {
		if ( pmpro_is_notification_applicable( $notification ) ) {
			$pmpro_applicable_notifications[] = $notification;			
		}
	}
	
	// Sort by priority.	
	$pmpro_applicable_notifications = wp_list_sort( $pmpro_applicable_notifications, 'priority' );
	
	return $pmpro_applicable_notifications;
}

/**
 * Check rules for a notification.
 */
function pmpro_is_notification_applicable( $notification ) {
	// TODO: Check show_if and hide_if rules.

	// Hide if today's date is before notification start date.
	if ( date( 'Y-m-d', current_time( 'timestamp' ) ) < $notification->starts ) {
		return false;
	}

	// Hide if today's date is after end date.
	if ( date( 'Y-m-d', current_time( 'timestamp' ) ) > $notification->ends ) {
		return false;
	}

	// Check if only security notices should show.
	if ( $notification->priority > pmpro_get_max_notification_priority() ) {
		return false;
	}
	
	// Hide notification by default.
	$show_notification = false;

	$hide_if_plugin = !empty( $notification->hide_if->plugins_active ) ? $notification->hide_if->plugins_active : '';

	// set the show notification to true.
	if ( $hide_if_plugin ) {	
		$plugin_slug_and_file = $hide_if_plugin[0] . '/' . $hide_if_plugin[1] . ".php";

		// If this plugin is installed just bail.
		if ( is_plugin_active( $plugin_slug_and_file ) ) {
			return false;
		} else {
			$show_notification = true;
		}
	} else {
		$show_notification = true;
	}

	// Check if we need to hide the notification first, if it needs to be hidden just bail.
	$show_if = !empty( $notification->show_if ) ? $notification->show_if : '';

	if ( $show_if && $show_notification ) {
		
		$plugins = $show_if->plugins_version;

		$plugin_slug_and_file = $plugins[0] . '/' . $plugins[0] . ".php";
		
		if ( is_plugin_active( $plugin_slug_and_file ) ) {
			$plugin_current_version = get_file_data(  WP_PLUGIN_DIR . '/' . $plugin_slug_and_file, array( 'Version' => 'Version' ) );

			// plugins 2 is version to check, plugins 1 is operator, against current version.
			if ( $plugins[2] . " " . $plugins[1] . " " . $plugin_current_version['Version'] ) {
				$show_notification = true;
			} else {
				$show_notification = false;
			}
			
		} else {
			$show_notification = false;
		}
	}
	return $show_notification;
}

/**
 * Get the max notification priority allowed on this site.
 * Priority is a value from 1 to 5, or 0.
 * 0: No notifications at all.
 * 1: Security notifications.
 * 2: Core PMPro updates.
 * 3: Updates to plugins already installed.
 * 4: Suggestions based on existing plugins and settings.
 * 5: Informative.
 */
function pmpro_get_max_notification_priority() {
	static $max_priority = null;

	if ( ! isset( $max_priority ) ) {
		$max_priority = pmpro_getOption( 'maxnotificationpriority' );
		
		// default to 5
		if ( empty( $max_priority ) ) {
			$max_priority = 5;
		}
		
		// filter allows for max priority 0 to turn them off entirely
		$max_priority = apply_filters( 'pmpro_max_notification_priority', $max_priority );
	}
	
	return $max_priority;
}

/**
 * Move the top notice to the archives if dismissed.
 */
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

/**
 * Show Powered by Paid Memberships Pro comment (only visible in source) in the footer.
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
