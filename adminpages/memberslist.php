<?php

global $user_list_table;
// Query, filter, and sort the data.
$user_list_table = new PMPro_Members_List_Table();
$user_list_table->prepare_items();
require_once dirname( __DIR__ ) . '/adminpages/admin_header.php';

// Build CSV export link.
$csv_export_link = admin_url( 'admin-ajax.php' ) . '?action=memberslist_csv';
if ( isset( $_REQUEST['s'] ) ) {
	$csv_export_link .= '&s=' . esc_attr( sanitize_text_field( trim( $_REQUEST['s'] ) ) );
}
if ( isset( $_REQUEST['l'] ) ) {
	$csv_export_link .= '&l=' . sanitize_text_field( trim( $_REQUEST['l'] ) );
}

// Render the List Table.
?>
	<h2><?php _e( 'PMPro Members List Table', 'paid-memberships-pro' ); ?>
	<a target="_blank" href="<?php echo esc_url( $csv_export_link ); ?>" class="add-new-h2"><?php _e( 'Export to CSV', 'paid-memberships-pro' ); ?></a>
	</h2>
	<?php do_action( 'pmpro_memberslist_before_table' ); ?>
		<div id="member-list-table">			
			<form id="member-list-form" method="get">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
				<?php
					$user_list_table->search_box( __( 'Find Member', 'paid-memberships-pro' ), 'paid-memberships-pro' );
					$user_list_table->display();
				?>
		</form>
	</div>
<?php
	require_once dirname( __DIR__ ) . '/adminpages/admin_footer.php';
?>
