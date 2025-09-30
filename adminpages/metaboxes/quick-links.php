<?php
/**
 * Paid Memberships Pro Quick Links Meta Box
 *
 * @package PaidMembershipsPro
 * @subpackage AdminPages
 * @since 3.5
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pmpro_dashboard_quick_links_callback() {
	$quick_lnks = array();

	if ( current_user_can( 'edit_users' ) ) {
		$quick_lnks[] = array(
			'href'        => add_query_arg( array( 'page' => 'pmpro-member' ), admin_url( 'admin.php' ) ),
			'title'       => __( 'Add a Member', 'paid-memberships-pro' ),
			'description' => __( 'Add membership to a new or existing user.', 'paid-memberships-pro' ),
			'icon'        => 'user-plus',
		);
	}

	if ( current_user_can( 'pmpro_discountcodes' ) ) {
		$quick_lnks[] = array(
			'href'        => add_query_arg(
				array(
					'page' => 'pmpro-discountcodes',
					'edit' => '-1',
				),
				admin_url( 'admin.php' )
			),
			'title'       => __( 'Create a Discount Code', 'paid-memberships-pro' ),
			'description' => __( 'Create a new discount code for your members.', 'paid-memberships-pro' ),
			'icon'        => 'tag',
		);
	}

	if ( current_user_can( 'pmpro_emailsettings' ) ) {
		$quick_lnks[] = array(
			'href'        => add_query_arg( array( 'page' => 'pmpro-emailtemplates' ), admin_url( 'admin.php' ) ),
			'title'       => __( 'Edit Email Templates', 'paid-memberships-pro' ),
			'description' => __( 'Manage your email templates for member communications.', 'paid-memberships-pro' ),
			'icon'        => 'mail',
		);
	}

	if ( current_user_can( 'pmpro_userfields' ) ) {
		$quick_lnks[] = array(
			'href'        => add_query_arg( array( 'page' => 'pmpro-userfields' ), admin_url( 'admin.php' ) ),
			'title'       => __( 'Manage User Fields', 'paid-memberships-pro' ),
			'description' => __( 'Add or edit custom profile fields for users and members.', 'paid-memberships-pro' ),
			'icon'        => 'edit-3',
		);
	}

	if ( current_user_can( 'pmpro_securitysettings' ) ) {
		$quick_lnks[] = array(
			'href'        => add_query_arg( array( 'page' => 'pmpro-securitysettings' ), admin_url( 'admin.php' ) ),
			'title'       => __( 'Manage Site Security', 'paid-memberships-pro' ),
			'description' => __( 'Configure security settings for your membership site.', 'paid-memberships-pro' ),
			'icon'        => 'shield',
		);
	}

	// Show a link to view the filtered list of Add Ons they can install.
	if ( current_user_can( 'pmpro_addons' ) ) {
		$key                 = get_option( 'pmpro_license_key', '' );
		$pmpro_license_check = get_option(
			'pmpro_license_check',
			array(
				'license' => false,
				'enddate' => 0,
			)
		);
		if ( ! pmpro_license_isValid() && empty( $key ) ) {
			$add_ons_type = 'free';
		} elseif ( pmpro_license_isValid() && ! pmpro_license_isValid( $key, pmpro_license_get_premium_types() ) ) {
			$add_ons_type = 'free';
		} else {
			$add_ons_type = 'premium';
		}
		$quick_lnks[] = array(
			'href'        => add_query_arg( array( 'page' => 'pmpro-addons#' . $add_ons_type ), admin_url( 'admin.php' ) ),
			'title'       => __( 'View Add Ons', 'paid-memberships-pro' ),
			'description' => sprintf( __( 'Explore available %s Add Ons for your membership site.', 'paid-memberships-pro' ), esc_html( $add_ons_type ) ),
			'icon'        => 'download',
		);
	}

	foreach ( $quick_lnks as $link ) {
		?>
		<a class="pmpro_box pmpro_box-clickable pmpro_box-has-icon" href="<?php echo esc_url( $link['href'] ); ?>">
			<div class="pmpro_box-title pmpro_box-title-has-icon">
				<img alt="" src="<?php echo esc_url( PMPRO_URL . '/images/' . $link['icon'] . '.svg' ); ?>" />
				<?php echo esc_html( $link['title'] ); ?>
			</div>
			<p class="pmpro_box-description">
				<?php echo esc_html( $link['description'] ); ?>
			</p>
		</a>
		<?php
	}
}