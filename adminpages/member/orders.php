<div id="pmpro-orders-panel" role="tabpanel" tabindex="0" aria-labelledby="tab-4" hidden>
<h2>
    <?php esc_html_e( 'Orders', 'paid-memberships-pro' ); ?>
    <a class="page-title-action" href="<?php echo admin_url( 'admin.php?page=pmpro-orders&order=-1&user_id=' . $user->ID ); ?>"><?php esc_html_e( 'Add Order', 'paid-memberships-pro' ); ?></a>
</h2>
<?php
    //Show all invoices for user
    $invoices = $wpdb->get_results( $wpdb->prepare( "SELECT mo.*, du.code_id as code_id FROM $wpdb->pmpro_membership_orders mo LEFT JOIN $wpdb->pmpro_discount_codes_uses du ON mo.id = du.order_id WHERE mo.user_id = %d ORDER BY mo.timestamp DESC", $user->ID ) );

    // Build the selectors for the invoices history list based on history count.
    $invoices_classes = array();
    if ( ! empty( $invoices ) && count( $invoices ) > 10 ) {
        $invoices_classes[] = "pmpro_scrollable";
    }
    $invoice_class = implode( ' ', array_unique( $invoices_classes ) );
?>
<div id="member-history-orders" class="<?php echo esc_attr( $invoice_class ); ?>">
<?php if ( $invoices ) { ?>
    <table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
    <thead>
        <tr>
            <th><?php esc_html_e( 'Date', 'paid-memberships-pro' ); ?></th>
            <th><?php esc_html_e( 'Code', 'paid-memberships-pro' ); ?></th>
            <th><?php esc_html_e( 'Level', 'paid-memberships-pro' ); ?></th>
            <th><?php esc_html_e( 'Total', 'paid-memberships-pro' ); ?></th>
            <th><?php esc_html_e( 'Discount Code', 'paid-memberships-pro' ); ?></th>
            <th><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></th>
            <?php do_action('pmpromh_orders_extra_cols_header');?>
        </tr>
    </thead>
    <tbody>
    <?php
        foreach ( $invoices as $invoice ) { 
            $level = pmpro_getLevel( $invoice->membership_id );
            ?>
            <tr>
                <td>
                    <?php
                        echo esc_html( sprintf(
                            // translators: %1$s is the date and %2$s is the time.
                            __( '%1$s at %2$s', 'paid-memberships-pro' ),
                            esc_html( date_i18n( get_option( 'date_format' ), strtotime( get_date_from_gmt( $invoice->timestamp ) ) ) ),
                            esc_html( date_i18n( get_option( 'time_format' ), strtotime( get_date_from_gmt( $invoice->timestamp ) ) ) )
                        ) );
                    ?>
                </td>
                <td class="order_code column-order_code has-row-actions">
                    <strong><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 'order' => $invoice->id ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $invoice->code ); ?></a></strong>
                    <div class="row-actions">
                        <span class="id">
                            <?php echo sprintf(
                                // translators: %s is the Order ID.
                                __( 'ID: %s', 'paid-memberships-pro' ),
                                esc_attr( $invoice->id )
                            ); ?>
                        </span> |
                        <span class="edit">
                            <a title="<?php esc_attr_e( 'Edit', 'paid-memberships-pro' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 'order' => $invoice->id ), admin_url('admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'paid-memberships-pro' ); ?></a>
                        </span> |
                        <span class="print">
                            <a target="_blank" title="<?php esc_attr_e( 'Print', 'paid-memberships-pro' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'action' => 'pmpro_orders_print_view', 'order' => $invoice->id ), admin_url('admin-ajax.php' ) ) ); ?>"><?php esc_html_e( 'Print', 'paid-memberships-pro' ); ?></a>
                        </span>
                        <?php if ( function_exists( 'pmpro_add_email_order_modal' ) ) { ?>
                            |
                            <span class="email">
                                <a title="<?php esc_attr_e( 'Email', 'paid-memberships-pro' ); ?>" href="#TB_inline?width=600&height=200&inlineId=email_invoice" class="thickbox email_link" data-order="<?php echo esc_attr( $invoice->id ); ?>"><?php esc_html_e( 'Email', 'paid-memberships-pro' ); ?></a>
                            </span>
                        <?php } ?>
                    </div> <!-- end .row-actions -->
                </td>
                <td>
                    <?php
                        if ( ! empty( $level ) ) {
                            echo esc_html( $level->name );
                        } elseif ( $invoice->membership_id > 0 ) { ?>
                            [<?php esc_html_e( 'deleted', 'paid-memberships-pro' ); ?>]
                        <?php } else {
                            esc_html_e( '&#8212;', 'paid-memberships-pro' );
                        }
                    ?>
                </td>
                <td><?php echo pmpro_formatPrice( $invoice->total ); ?></td>
                <td><?php 
                    if ( empty( $invoice->code_id ) ) {
                        esc_html_e( '&#8212;', 'paid-memberships-pro' );
                    } else {
                        $discountQuery = $wpdb->prepare( "SELECT c.code FROM $wpdb->pmpro_discount_codes c WHERE c.id = %d LIMIT 1", $invoice->code_id );
                        $discount_code = $wpdb->get_row( $discountQuery );
                        echo '<a href="admin.php?page=pmpro-discountcodes&edit=' . esc_attr( $invoice->code_id ). '">'. esc_attr( $discount_code->code ) . '</a>';
                    }
                ?></td>
                <td>
                    <?php
                        if ( empty( $invoice->status ) ) {
                            esc_html_e( '&#8212;', 'paid-memberships-pro' );
                        } else { ?>
                            <span class="pmpro_order-status pmpro_order-status-<?php esc_attr_e( $invoice->status ); ?>">
                                <?php if ( in_array( $invoice->status, array( 'success', 'cancelled' ) ) ) {
                                    esc_html_e( 'Paid', 'paid-memberships-pro' );
                                } else {
                                    esc_html_e( ucwords( $invoice->status ) );
                                } ?>
                            </span>
                            <?php
                        }
                    ?>
                </td>
                <?php do_action( 'pmpromh_orders_extra_cols_body', $invoice ); ?>
            </tr>
            <?php
        }
    ?>
    </tbody>
    </table>
<?php } else { ?>
    <table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tbody>
            <tr>
                <td><?php esc_html_e( 'No membership orders found.', 'paid-memberships-pro' ); ?></td>
            </tr>
        </tbody>
    </table>
<?php } ?>
</div> <!-- end #member-history-orders -->
</div>