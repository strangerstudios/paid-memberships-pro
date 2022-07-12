<div class="clear"></div>
<?php
	echo sprintf(
		wp_kses(
			/* translators: $1$s - Paid Memberships Pro plugin name; $2$s - WP.org review link. */
			__( '<p>Please <a href="%1$s" target="_blank" rel="noopener noreferrer">rate us &#9733;&#9733;&#9733;&#9733;&#9733; on WordPress.org</a> to help others find %2$s. Thank you from the %3$s team!</p>', 'paid-memberships-pro' ),
			[
				'a' => [
					'href'   => [],
					'target' => [],
					'rel'    => [],
				],
				'p' => [],
			]
		),
		'https://wordpress.org/support/plugin/paid-memberships-pro/reviews/?filter=5#new-post',
		'Paid Memberships Pro',
		'PMPro'
	);
?>
</div>
