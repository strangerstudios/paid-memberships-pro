<?php
/**
 * Paid Memberships Pro Dashboard YouTube Feed Meta Box
 *
 * @package PaidMembershipsPro
 * @subpackage AdminPages
 * @since 2.6.0
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pmpro_dashboard_youtube_feed_callback() {

	$url = 'https://www.youtube.com/feeds/videos.xml?channel_id=UCFtMIeYJ4_YVidi1aq9kl5g';

	// Get RSS Feed(s)
	if ( ! function_exists( 'fetch_feed' ) ) {
		include_once( ABSPATH . WPINC . '/feed.php' );
	}

	$rss_items = array();
	$maxitems  = 0;

	$rss = fetch_feed( $url );

	if ( ! is_wp_error( $rss ) && $rss ) {
		$maxitems  = $rss->get_item_quantity( 5 );
		$rss_items = $rss->get_items( 0, $maxitems );
	}
	?>

	<!-- YouTube Feed -->
	<ul>
		<?php if ( empty( $rss_items ) ) : ?>
			<li><?php esc_html_e( 'No news found.', 'paid-memberships-pro' ); ?></li>
		<?php else : ?>
			<?php // Loop through each feed item and display each item as a hyperlink. ?>
			<?php foreach ( $rss_items as $item ) : ?>
				<li><?php echo esc_html( date_i18n( get_option( 'date_format' ), $item->get_date( 'U' ) ) ); ?><br>

					<a target="_blank" href="<?php echo esc_url( $item->get_permalink() ); ?>"
						title="<?php echo esc_attr( sprintf( __( 'Posted %s', 'paid-memberships-pro' ), date_i18n( get_option( 'date_format' ), $item->get_date( 'U' ) ) ) ); ?>">
						<?php echo esc_html( $item->get_title() ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		<?php endif; ?>
	</ul>

	<?php
}