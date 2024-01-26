<?php

global $user_list_table;
// Query, filter, and sort the data.
$user_list_table = new PMPro_Members_List_Table();
$user_list_table->prepare_items();

require_once dirname( __DIR__ ) . '/adminpages/admin_header.php';

// Build CSV export link.
$csv_export_link = admin_url( 'admin-ajax.php' ) . '?action=memberslist_csv';
if ( isset( $_REQUEST['s'] ) ) {
	$csv_export_link .= '&s=' . esc_attr( trim( sanitize_text_field( $_REQUEST['s'] ) ) );
}
if ( isset( $_REQUEST['l'] ) ) {
	$csv_export_link .= '&l=' . trim( sanitize_text_field( $_REQUEST['l'] ) );
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
			<a target="_blank" href="<?php echo esc_url( $csv_export_link ); ?>" class="page-title-action pmpro-has-icon pmpro-has-icon-download"><?php esc_html_e( 'Export to CSV', 'paid-memberships-pro' ); ?></a>
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
?>
