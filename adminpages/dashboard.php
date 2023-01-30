<?php
/**
 * The Memberships Dashboard admin page for Paid Memberships Pro
 * @since 2.0
 */

// Load all dashboard metaboxes
$dashboard_widgets_dir = __DIR__ . "/dashboard/";

$cwd = getcwd();
chdir( $dashboard_widgets_dir );
foreach ( glob( "*.php" ) as $filename ) {
	require_once( $filename );
}
chdir( $cwd );

/**
 * TODO: document.
 */
do_action( 'add_meta_boxes', 'toplevel_page_pmpro-dashboard' );

/**
 * Load the Paid Memberships Pro dashboard-area header
 */
require_once( dirname( __FILE__ ) . '/admin_header.php' ); ?>

    <form id="pmpro-dashboard-form" method="post" action="admin-post.php">

        <div class="dashboard-widgets-wrap">
            <div id="dashboard-widgets" class="metabox-holder">

				<?php do_meta_boxes( 'toplevel_page_pmpro-dashboard', 'normal', '' ); ?>

                <div id="postbox-container-1" class="postbox-container">
					<?php do_meta_boxes( 'toplevel_page_pmpro-dashboard', 'advanced', '' ); ?>
                </div>

                <div id="postbox-container-2" class="postbox-container">
					<?php do_meta_boxes( 'toplevel_page_pmpro-dashboard', 'side', '' ); ?>
                </div>

                <br class="clear">

            </div> <!-- end dashboard-widgets -->

			<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
			<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>

        </div> <!-- end dashboard-widgets-wrap -->
    </form>
    <script type="text/javascript">
        //<![CDATA[
        jQuery(document).ready(function ($) {
            // close postboxes that should be closed
            $('.if-js-closed').removeClass('if-js-closed').addClass('closed');
            // postboxes setup
            postboxes.add_postbox_toggles('toplevel_page_pmpro-dashboard');
        });
        //]]>
    </script>
<?php

/**
 * Load the Paid Memberships Pro dashboard-area footer
 */
require_once( dirname( __FILE__ ) . '/admin_footer.php' );
