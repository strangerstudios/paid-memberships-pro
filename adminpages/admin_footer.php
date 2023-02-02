<div class="clear"></div>
<?php
	echo sprintf(
		wp_kses(
			/* translators: $1$s - Paid Memberships Pro plugin name; $2$s - WP.org review link. */
			__( '<p class="pmpro-rate-us">Please <a href="%1$s" target="_blank" rel="noopener noreferrer">rate us %2$s on WordPress.org</a> to help others find %3$s. Thank you from the %4$s team!</p>', 'paid-memberships-pro' ),
			[
				'a' => [
					'href'   => [],
					'target' => [],
					'rel'    => [],
				],
				'p' => [
					'class'  => [],
				],
			]
		),
		'https://wordpress.org/support/plugin/paid-memberships-pro/reviews/?filter=5#new-post',
		'<span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span>',
		'Paid Memberships Pro',
		'PMPro'
	);
?>
</div>
