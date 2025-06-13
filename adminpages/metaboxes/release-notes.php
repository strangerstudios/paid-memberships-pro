<?php
/**
 * Paid Memberships Pro Release Notes Meta Box
 *
 * @package PaidMembershipsPro
 * @subpackage AdminPages
 * @since TBD
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pmpro_dashboard_release_notes_callback() {

	$url = 'https://www.paidmembershipspro.com/category/release-notes/';

	// Get RSS Feed(s)
	if ( ! function_exists( 'fetch_feed' ) ) {
		include_once( ABSPATH . WPINC . '/feed.php' );
	}

	$release_notes = array();
	$maxitems  = 0;

	$rss = fetch_feed( $url . 'feed/' );

	if ( ! is_wp_error( $rss ) && $rss ) {
		$maxitems  = $rss->get_item_quantity( 5 );
		$release_notes = $rss->get_items( 0, $maxitems );
	}
	?>

	<!-- Release Notes -->
	<ul>
		<?php if ( empty( $release_notes ) ) : ?>
			<li><?php esc_html_e( 'No release notes found.', 'paid-memberships-pro' ); ?></li>
		<?php else : ?>
			<?php // Loop through each feed item and display each item as a hyperlink. ?>
			<?php foreach ( $release_notes as $item ) : ?>
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