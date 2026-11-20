<?php
/**
 * Email Preferences panel for Member Edit page
 *
 * @package Paid Memberships Pro
 * @subpackage Admin
 * @since TBD
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PMPro_Member_Edit_Panel_Email_Preferences extends PMPro_Member_Edit_Panel {
	/**
	 * Set up the panel.
	 */
	public function __construct() {
		$this->slug        = 'email-preferences';
		$this->title       = __( 'Email Preferences', 'paid-memberships-pro' );
		$this->submit_text = __( 'Save Email Preferences', 'paid-memberships-pro' );
	}

	/**
	 * Only show the panel if there are optional emails to opt out of.
	 *
	 * @return bool
	 */
	public function should_show() {
		if ( empty( pmpro_get_active_email_categories() ) ) {
			return false;
		}

		return parent::should_show();
	}

	/**
	 * Display the panel contents.
	 */
	protected function display_panel_contents() {
		$user             = self::get_user();
		$email_categories = pmpro_get_active_email_categories();
		?>
		<p><?php esc_html_e( 'Optional emails this member can turn off from their profile page or with the unsubscribe link in the email. Account, payment, and membership emails are always sent.', 'paid-memberships-pro' ); ?></p>
		<p>
			<label for="pmpro_email_opt_out_all">
				<input type="checkbox" name="pmpro_email_opt_out_all" id="pmpro_email_opt_out_all" value="1" <?php checked( (bool) pmpro_get_user_email_opt_out( $user->ID, 'all' ) ); ?> />
				<strong><?php esc_html_e( 'Don\'t send this member any optional emails', 'paid-memberships-pro' ); ?></strong>
			</label>
			<br />
			<span class="description">
				<?php
				$opt_out_all = pmpro_get_user_email_opt_out( $user->ID, 'all' );
				if ( ! empty( $opt_out_all ) ) {
					echo esc_html( pmpro_get_email_opt_out_text( $opt_out_all ) ) . '. ';
				}
				esc_html_e( 'Overrides the choices below, including email types added later.', 'paid-memberships-pro' );
				?>
			</span>
		</p>
		<table class="widefat striped pmpro_responsive_table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Receive', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Email Type', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Emails', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Last Change', 'paid-memberships-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $email_categories as $category => $email_category ) {
					$opt_out = pmpro_get_user_email_opt_out( $user->ID, $category );
					?>
					<tr>
						<td data-colname="<?php esc_attr_e( 'Receive', 'paid-memberships-pro' ); ?>">
							<input type="checkbox" name="pmpro_email_receive[]" id="pmpro_email_receive_<?php echo esc_attr( $category ); ?>" value="<?php echo esc_attr( $category ); ?>" <?php checked( empty( $opt_out ) ); ?> />
						</td>
						<td data-colname="<?php esc_attr_e( 'Email Type', 'paid-memberships-pro' ); ?>">
							<label for="pmpro_email_receive_<?php echo esc_attr( $category ); ?>"><strong><?php echo esc_html( $email_category['label'] ); ?></strong></label>
						</td>
						<td data-colname="<?php esc_attr_e( 'Emails', 'paid-memberships-pro' ); ?>"><?php echo esc_html( implode( ', ', $email_category['templates'] ) ); ?></td>
						<td data-colname="<?php esc_attr_e( 'Last Change', 'paid-memberships-pro' ); ?>"><?php echo empty( $opt_out ) ? '&#8212;' : esc_html( pmpro_get_email_opt_out_text( $opt_out ) ); ?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Save the panel.
	 */
	public function save() {
		$user = self::get_user();
		if ( empty( $user->ID ) ) {
			return;
		}

		// Nonce is verified in pmpro_member_edit_save().
		pmpro_email_preferences_save_choices( $user->ID, 'admin' );

		pmpro_setMessage( __( 'Email preferences updated.', 'paid-memberships-pro' ), 'pmpro_success' );
	}
}

// Register the panel.
add_filter( 'pmpro_member_edit_panels', function( $panels ) {
	$panels[] = new PMPro_Member_Edit_Panel_Email_Preferences();
	return $panels;
} );
