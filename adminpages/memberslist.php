<?php

global $user_list_table;
// Query, filter, and sort the data.
$user_list_table = new PMPro_Members_List_Table();
$user_list_table->prepare_items();

require_once dirname( __DIR__ ) . '/adminpages/admin_header.php';

// Build CSV export link.
// We now use the REST API for exports. Gather current filters to pass along when starting an export.
$members_export_filters = array();
if ( isset( $_REQUEST['s'] ) ) {
    $members_export_filters['s'] = esc_attr( trim( sanitize_text_field( $_REQUEST['s'] ) ) );
}
if ( isset( $_REQUEST['l'] ) ) {
    $members_export_filters['l'] = trim( sanitize_text_field( $_REQUEST['l'] ) );
}

// Render the List Table.
?>
	<hr class="wp-header-end">
	<form id="member-list-form" method="get">		
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Members List', 'paid-memberships-pro' ); ?></h1>
		<?php if ( current_user_can( 'edit_users' ) ) { ?>
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-member'), admin_url( 'admin.php' ) ) ); ?>" class="page-title-action pmpro-has-icon pmpro-has-icon-plus"><?php esc_html_e( 'Add New Member', 'paid-memberships-pro' ); ?></a>
		<?php } ?>
		<?php if ( current_user_can( 'pmpro_memberslistcsv' ) ) { ?>
			<button type="button"
				class="page-title-action pmpro-has-icon pmpro-has-icon-download pmpro-export-button"
				aria-live="polite"
				data-status="idle"
				data-export-id=""
				data-type="members"
				data-start-url="<?php echo esc_url( rest_url( 'pmpro/v1/export/start' ) ); ?>"
				data-status-url="<?php echo esc_url( rest_url( 'pmpro/v1/export/status' ) ); ?>"
				data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>"
				data-filters='<?php echo esc_attr( wp_json_encode( $members_export_filters ) ); ?>'>
				<?php esc_html_e( 'Export to CSV', 'paid-memberships-pro' ); ?>
			</button>
		<?php } ?>
		<?php do_action( 'pmpro_memberslist_before_table' ); ?>	
		<input type="hidden" name="page" value="pmpro-memberslist" />
		<?php
			$user_list_table->search_box( __( 'Search Members', 'paid-memberships-pro' ), 'paid-memberships-pro' );
			$user_list_table->display();
		?>
	</form>
<?php
	require_once dirname( __DIR__ ) . '/adminpages/admin_footer.php';