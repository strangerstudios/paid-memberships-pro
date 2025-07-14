<?php
/**
 * Paid Memberships Pro Dashboard News Updates Meta Box
 *
 * @package PaidMembershipsPro
 * @subpackage AdminPages
 * @since 2.6.0
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pmpro_dashboard_news_updates_callback() {

	$url = 'https://www.paidmembershipspro.com/';

	// Get RSS Feed(s)
	if ( ! function_exists( 'fetch_feed' ) ) {
		include_once( ABSPATH . WPINC . '/feed.php' );
	}

	$rss_items = array();
	$maxitems  = 0;

	$rss = fetch_feed( $url . 'feed/' );

	if ( ! is_wp_error( $rss ) && $rss ) {
		// Figure out how many total items there are, but limit it to 5.
		$maxitems = $rss->get_item_quantity( 5 );

		// Build an array of all the items, starting with element 0 (first element).
		$rss_items = $rss->get_items( 0, $maxitems );

		// Shuffle the order and get a random one item to display.
		shuffle( $rss_items );
	}
	?>

	<!-- News Updates -->
	<ul>
		<?php if ( empty( $rss_items ) ) : ?>
			<li><?php esc_html_e( 'No news found.', 'paid-memberships-pro' ); ?></li>
		<?php else : ?>
			<li>
				<?php echo esc_html( date_i18n( get_option( 'date_format' ), $rss_items[0]->get_date( 'U' ) ) ); ?>
				<br />
				<a href="<?php echo esc_url( $rss_items[0]->get_permalink() ); ?>"
					title="<?php echo esc_attr( sprintf( __( 'Posted %s', 'paid-memberships-pro' ), date_i18n( get_option( 'date_format' ), $rss_items[0]->get_date( 'U' ) ) ) ); ?>">
					<?php echo esc_html( $rss_items[0]->get_title() ); ?>
				</a>
			</li>
		<?php endif; ?>
	</ul>
	<p class="pmpro-dashboard-link-out"><span class="dashicons dashicons-external"></span> <a target="_blank" href="https://www.paidmembershipspro.com/blog/?utm_source=plugin&utm_medium=pmpro-dashboard&utm_campaign=blog&utm_content=news-updates-metabox"><?php esc_html_e( 'Read the Blog', 'paid-memberships-pro' ); ?></a></p>
	<?php
}