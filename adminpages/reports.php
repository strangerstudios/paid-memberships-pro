<?php
/**
 * The Memberships Reports admin page for Paid Memberships Pro
 */

global $pmpro_reports;

/**
* Load the Paid Memberships Pro dashboard-area header
*/
require_once( dirname( __FILE__ ) . '/admin_header.php' ); ?>

<hr class="wp-header-end">

<?php
// View a single report if requested.
if ( ! empty( $_REQUEST[ 'report' ] ) ) {
	// Get the report we are viewing.
	$report = sanitize_text_field( $_REQUEST[ 'report' ] ); ?>
	<ul class="subsubsub">
		<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-reports' ) ); ?>"><?php esc_html_e('All', 'paid-memberships-pro' ); ?></a></li>
		<?php foreach ( $pmpro_reports as $report_menu_item => $report_menu_title ) {
			if ( function_exists( 'pmpro_report_' . $report_menu_item . '_page' ) ) { ?>
				<li>&nbsp;|&nbsp;<a class="<?php if ( $report === $report_menu_item ) { ?>current<?php } ?>"href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-reports&report=' . $report_menu_item ) ); ?>"><?php echo $report_menu_title; ?></a></li>
				<?php
			}
		} ?>
	</ul>
	<br class="clear" />
	<?php
		// View a single report
		call_user_func( 'pmpro_report_' . $report . '_page' );
	?>
	<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-reports' ) );?>"><?php esc_html_e( 'Back to Reports Dashboard', 'paid-memberships-pro' ); ?></a></p>

	<?php
} else { ?>
	<h1><?php esc_html_e( 'Reports', 'paid-memberships-pro' ); ?></h1>
    <?php if( ! empty( $pmpro_reports ) ) {
        $pieces = array_chunk( $pmpro_reports, ceil( count( $pmpro_reports ) / 2 ), true );
        foreach ( $pieces[0] as $report => $title ) {
            add_meta_box(
                'pmpro_report_' . $report,
                $title,
                'pmpro_report_' . $report . '_widget',
                'memberships_page_pmpro-reports',
                'advanced'
            );
        }

        if( ! empty( $pieces[1] ) ) {
	        foreach ( $pieces[1] as $report => $title ) {
		        add_meta_box(
			        'pmpro_report_' . $report,
			        $title,
			        'pmpro_report_' . $report . '_widget',
			        'memberships_page_pmpro-reports',
			        'side'
		        );
	        }
        }
    }

	?>
	<form id="pmpro-reports-form" method="post" action="admin-post.php">

		<div class="dashboard-widgets-wrap">
			<div id="dashboard-widgets" class="metabox-holder">

				<div id="postbox-container-1" class="postbox-container">
					<?php do_meta_boxes( 'memberships_page_pmpro-reports', 'advanced', '' ); ?>
				</div>

				<div id="postbox-container-2" class="postbox-container">
					<?php do_meta_boxes( 'memberships_page_pmpro-reports', 'side', '' ); ?>
				</div>

				<br class="clear">

			</div> <!-- end dashboard-widgets -->

			<?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
			<?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); ?>

		</div> <!-- end dashboard-widgets-wrap -->
	</form>
	<script type="text/javascript">
	  //<![CDATA[
	  jQuery(document).ready( function($) {
		  // close postboxes that should be closed
		  $('.if-js-closed').removeClass('if-js-closed').addClass('closed');
		  // postboxes setup
		  postboxes.add_postbox_toggles('memberships_page_pmpro-reports');
	  });
	  //]]>
	</script>

	<?php
}

/**
* Load the Paid Memberships Pro dashboard-area footer
*/
require_once(dirname(__FILE__) . "/admin_footer.php");