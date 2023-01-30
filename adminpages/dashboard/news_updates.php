<?php
/**
 * Add meta box to dashboard page.
 */
add_action( 'add_meta_boxes', function () {
	add_meta_box(
		'pmpro_dashboard_news_updates',
		__( 'Paid Memberships Pro News and Updates', 'paid-memberships-pro' ),
		'pmpro_dashboard_news_updates_callback',
		'toplevel_page_pmpro-dashboard',
		'side'
	);
} );

/**
 * Callback function for pmpro_dashboard_news_updates meta box to show RSS Feed from Paid Memberships Pro blog.
 */
function pmpro_dashboard_news_updates_callback() {

	// Get RSS Feed(s)
	include_once( ABSPATH . WPINC . '/feed.php' );

	// Get a SimplePie feed object from the specified feed source.
	$rss = fetch_feed( 'https://www.paidmembershipspro.com/feed/' );

	$maxitems = 0;

	if ( ! is_wp_error( $rss ) ) : // Checks that the object is created correctly

		// Figure out how many total items there are, but limit it to 5.
		$maxitems = $rss->get_item_quantity( 5 );

		// Build an array of all the items, starting with element 0 (first element).
		$rss_items = $rss->get_items( 0, $maxitems );

	endif;
	?>

    <ul>
		<?php if ( $maxitems == 0 ) : ?>
            <li><?php esc_html_e( 'No news found.', 'paid-memberships-pro' ); ?></li>
		<?php else : ?>
			<?php // Loop through each feed item and display each item as a hyperlink. ?>
			<?php foreach ( $rss_items as $item ) : ?>
                <li>
                    <a href="<?php echo esc_url( $item->get_permalink() ); ?>"
                       title="<?php printf( __( 'Posted %s', 'paid-memberships-pro' ), $item->get_date( get_option( 'date_format' ) ) ); ?>">
						<?php echo esc_html( $item->get_title() ); ?>
                    </a>
					<?php echo esc_html( $item->get_date( get_option( 'date_format' ) ) ); ?>
                </li>
			<?php endforeach; ?>
		<?php endif; ?>
    </ul>
    <p class="text-center"><a class="button button-primary"
                              href="https://www.paidmembershipspro.com/blog/?utm_source=plugin&utm_medium=pmpro-dashboard&utm_campaign=blog&utm_content=news-updates-metabox"><?php esc_html_e( 'View More', 'paid-memberships-pro' ); ?></a>
    </p>
	<?php
}
