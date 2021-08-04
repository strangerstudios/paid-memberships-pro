<?php
/*
	Loading a service?
*/
/*
	Note: The applydiscountcode goes through the site_url() instead of admin-ajax to avoid HTTP/HTTPS issues.
*/
if(isset($_REQUEST['action']) && $_REQUEST['action'] == "applydiscountcode")
{		
	function pmpro_applydiscountcode_init()
	{
		require_once(dirname(__FILE__) . "/../services/applydiscountcode.php");	
		exit;
	}
	add_action("init", "pmpro_applydiscountcode_init", 11);
}
function pmpro_wp_ajax_authnet_silent_post()
{		
	require_once(dirname(__FILE__) . "/../services/authnet-silent-post.php");	
	exit;	
}
add_action('wp_ajax_nopriv_authnet_silent_post', 'pmpro_wp_ajax_authnet_silent_post');
add_action('wp_ajax_authnet_silent_post', 'pmpro_wp_ajax_authnet_silent_post');
function pmpro_wp_ajax_getfile()
{
	require_once(dirname(__FILE__) . "/../services/getfile.php");	
	exit;	
}
add_action('wp_ajax_nopriv_getfile', 'pmpro_wp_ajax_getfile');
add_action('wp_ajax_getfile', 'pmpro_wp_ajax_getfile');
function pmpro_wp_ajax_ipnhandler()
{
	require_once(dirname(__FILE__) . "/../services/ipnhandler.php");	
	exit;	
}
add_action('wp_ajax_nopriv_ipnhandler', 'pmpro_wp_ajax_ipnhandler');
add_action('wp_ajax_ipnhandler', 'pmpro_wp_ajax_ipnhandler');
function pmpro_wp_ajax_stripe_webhook()
{
	require_once(dirname(__FILE__) . "/../services/stripe-webhook.php");	
	exit;	
}
add_action('wp_ajax_nopriv_stripe_webhook', 'pmpro_wp_ajax_stripe_webhook');
add_action('wp_ajax_stripe_webhook', 'pmpro_wp_ajax_stripe_webhook');
function pmpro_wp_ajax_braintree_webhook()
{
	require_once(dirname(__FILE__) . "/../services/braintree-webhook.php");	
	exit;	
}
add_action('wp_ajax_nopriv_braintree_webhook', 'pmpro_wp_ajax_braintree_webhook');
add_action('wp_ajax_braintree_webhook', 'pmpro_wp_ajax_braintree_webhook');
function pmpro_wp_ajax_twocheckout_ins()
{
	require_once(dirname(__FILE__) . "/../services/twocheckout-ins.php");	
	exit;	
}
add_action('wp_ajax_nopriv_twocheckout-ins', 'pmpro_wp_ajax_twocheckout_ins');
add_action('wp_ajax_twocheckout-ins', 'pmpro_wp_ajax_twocheckout_ins');
function pmpro_wp_ajax_memberlist_csv()
{
	require_once(dirname(__FILE__) . "/../adminpages/memberslist-csv.php");	
	exit;	
}
add_action('wp_ajax_memberslist_csv', 'pmpro_wp_ajax_memberlist_csv');
function pmpro_wp_ajax_orders_csv()
{
	require_once(dirname(__FILE__) . "/../adminpages/orders-csv.php");	
	exit;	
}
add_action('wp_ajax_orders_csv', 'pmpro_wp_ajax_orders_csv');

/**
 * Load the Orders print view.
 *
 * @since 1.8.6
 */
function pmpro_orders_print_view() {
	require_once(dirname(__FILE__) . "/../adminpages/orders-print.php");
	exit;
}
add_action('wp_ajax_pmpro_orders_print_view', 'pmpro_orders_print_view');

/**
 * Get order JSON.
 *
 * @since 1.8.6
 */
function pmpro_get_order_json() {
	// only admins can get this
	if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_orders' ) ) ) {
		die( __( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
	}
	
	$order_id = intval( $_REQUEST['order_id'] );
	$order = new MemberOrder($order_id);
	echo json_encode($order);
	exit;
}
add_action('wp_ajax_pmpro_get_order_json', 'pmpro_get_order_json');

function pmpro_update_level_order() {
	// only admins can get this
	if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_membershiplevels' ) ) ) {
		die( __( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
	}

	$level_order = null;
	
	if ( isset( $_REQUEST['level_order'] ) && is_array( $_REQUEST['level_order'] ) ) {
		$level_order = array_map( 'intval', $_REQUEST['level_order'] );
		$level_order = implode(',', $level_order );
	} else if ( isset( $_REQUEST['level_order'] ) ) {
		$level_order = sanitize_text_field( $_REQUEST['level_order'] );
	}
	
	echo pmpro_setOption('level_order', $level_order);
    exit;
}
add_action('wp_ajax_pmpro_update_level_order', 'pmpro_update_level_order');

// User fields AJAX.
/**
 * Callback to draw a field group.
 */
function pmpro_userfields_get_group_ajax() {		
    $group_id = intval( $_REQUEST['group_id'] );
	$group_name = '';
	$group_show_checkout = 'yes';
	$group_show_profile = 'yes';
	$group_description = '';
	$levels = pmpro_getAllLevels( false, true );
	$group_levels = array();		
	?>
    <div class="pmpro_userfield-group">
        <div class="pmpro_userfield-group-header">
            <div class="pmpro_userfield-group-buttons">
                <button type="button" aria-disabled="false" class="pmpro_userfield-group-buttons-button pmpro_userfield-group-buttons-button-move-up" aria-label="<?php esc_attr_e( 'Move up', 'paid-memberships-pro' ); ?>">
                    <span class="dashicons dashicons-arrow-up-alt2"></span>
                </button>
                <span class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Move Group Up', 'paid-memberships-pro' ); ?></span>

                <button type="button" aria-disabled="false" class="pmpro_userfield-group-buttons-button pmpro_userfield-group-buttons-button-move-down" aria-label="<?php esc_attr_e( 'Move down', 'paid-memberships-pro' ); ?>">
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </button>
                <span id="pmpro_userfield-group-buttons-description-2" class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Move Group Down', 'paid-memberships-pro' ); ?></span>
            </div> <!-- end pmpro_userfield-group-buttons -->
            <h3>
                <label for="pmpro_userfields_group_name"><?php esc_html_e( 'Group Name', 'paid-memberships-pro' ); ?></label>
                <input type="text" name="pmpro_userfields_group_name" placeholder="<?php esc_attr_e( 'Group Name', 'paid-memberships-pro' ); ?>" value="<?php echo esc_attr( $group_name ); ?>" />
            </h3>
            <button type="button" aria-disabled="false" class="pmpro_userfield-group-buttons-button pmpro_userfield-group-buttons-button-expand-group" aria-label="<?php esc_attr_e( 'Expand and Edit Group', 'paid-memberships-pro' ); ?>">
                <span class="dashicons dashicons-arrow-down"></span>
            </button>
            <span class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Expand and Edit Group', 'paid-memberships-pro' ); ?></span>
        </div> <!-- end pmpro_userfield-group-header -->

        <div class="pmpro_userfield-inside">
			<div class="pmpro_userfield-field-settings">
				
				<div class="pmpro_userfield-field-setting">
					<label for="pmpro_userfields_group_checkout"><?php esc_html_e( 'Show group at checkout?', 'paid-memberships-pro' ); ?></label>
					<select name="pmpro_userfields_group_checkout">
						<option value="yes" <?php selected( $group_show_checkout, 'yes' ); ?>><?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></option>
						<option value="no" <?php selected( $group_show_checkout, 'no' ); ?>><?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></option>
					</select>
				</div> <!-- end pmpro_userfield-field-setting -->
				
				<div class="pmpro_userfield-field-setting">
					<label for="pmpro_userfields_group_profile"><?php esc_html_e( 'Show group on user profile?', 'paid-memberships-pro' ); ?></label>
					<select name="pmpro_userfields_group_profile">
						<option value="yes" <?php selected( $group_show_profile, 'yes' ); ?>><?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></option>
						<option value="admins" <?php selected( $group_show_profile, 'admins' ); ?>><?php esc_html_e( 'Yes (only admins)', 'paid-memberships-pro' ); ?></option>
						<option value="no" <?php selected( $group_show_profile, 'no' ); ?>><?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></option>
					</select>
				</div> <!-- end pmpro_userfield-field-setting -->
				
				<div class="pmpro_userfield-field-setting">
					<label for="pmpro_userfields_group_description"><?php esc_html_e( 'Description (visible to users)', 'paid-memberships-pro' ); ?></label>
					<textarea name="pmpro_userfields_group_description"><?php echo esc_html( $group_description );?></textarea>
				</div> <!-- end pmpro_userfield-field-setting -->
				
				<div class="pmpro_userfield-field-setting">
					<label for="pmpro_userfields_group_membership"><?php esc_html_e( 'Restrict Group for Membership Levels', 'paid-memberships-pro' ); ?></label>
					<div class="checkbox_box" <?php if ( count( $levels ) > 3 ) { ?>style="height: 90px; overflow: auto;"<?php } ?>>
						<?php foreach( $levels as $level ) { ?>
							<div class="clickable"><input type="checkbox" id="pmpro_userfields_group_membership_<?php echo esc_attr( $level->id); ?>" name="pmpro_userfields_group_membership[]" <?php checked( true, in_array( $level->id, $group_levels ) );?>> <?php echo esc_html( $level->name ); ?></div>
						<?php } ?>
					</div>
				</div> <!-- end pmpro_userfield-field-setting -->
			
			</div> <!-- end pmpro_userfield-field-settings -->
			
			<h3><?php esc_html_e( 'Manage Fields in This Group', 'paid-memberships-pro' ); ?></h3>
			
			<ul class="pmpro_userfield-group-thead">
				<li class="pmpro_userfield-group-column-order"><?php esc_html_e( 'Order', 'paid-memberships-pro'); ?></li>
				<li class="pmpro_userfield-group-column-label"><?php esc_html_e( 'Label', 'paid-memberships-pro'); ?></li>
				<li class="pmpro_userfield-group-column-name"><?php esc_html_e( 'Name', 'paid-memberships-pro'); ?></li>
				<li class="pmpro_userfield-group-column-type"><?php esc_html_e( 'Type', 'paid-memberships-pro'); ?></li>
			</ul>
			
			<div class="pmpro_userfield-group-fields">

				<div class="pmpro_userfield-group-field pmpro_userfield-group-field-collapse">
					
				</div> <!-- end pmpro_userfield-group-fields -->

				<div class="pmpro_userfield-group-actions">
					<button name="pmpro_userfields_add_field" class="button button-secondary button-hero">
						<?php
							/* translators: a plus sign dashicon */
							printf( esc_html__( '%s Add Field', 'paid-memberships-pro' ), '<span class="dashicons dashicons-plus"></span>' ); ?>
					</button>
				</div> <!-- end pmpro_userfield-group-actions -->

			</div> <!-- end pmpro_userfield-inside -->

		</div> <!-- end pmpro_userfield-group -->
    </div> <!-- end inside -->
    <?php
    exit;
 }
 add_action( 'wp_ajax_pmpro_userfields_get_group', 'pmpro_userfields_get_group_ajax' );