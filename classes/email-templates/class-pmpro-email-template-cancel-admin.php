<?php

class PMPro_Email_Template_Cancel_Admin extends PMPro_Email_Template {
	/**
	 * The user object of the user to send the email to.
	 *
	 * @var WP_User
	 */
	protected $user;

	/**
	 * The IDs of the membership levels that were cancelled.
	 *
	 * @var int
	 */
	protected $cancelled_level_ids;

	/**
	 * Constructor.
	 *
	 * @since 3.4
	 *
	 * @param WP_User $user The user object of the user to send the email to.
	 * @param int|array|null $cancelled_level_ids The ID or array of IDs of the membership levels that were cancelled. If null, "all" levels were cancelled.
	 */
	public function __construct( WP_User $user, $cancelled_level_ids ) {
		// deprecating having null $cancelled_level_ids
		if ( $cancelled_level_ids == null ) {
			_doing_it_wrong( __FUNCTION__, esc_html__( 'The $cancelled_level_ids parameter is required.', 'paid-memberships-pro' ), '3.3' );
		}
		$this->user = $user;
		$this->cancelled_level_ids = $cancelled_level_ids;
	}

	/**
	 * Get the email template slug.
	 *
	 * @since 3.4
	 *
	 * @return string The email template slug.
	 */
	public static function get_template_slug() {
		return 'cancel_admin';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since 3.4
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return esc_html__('Cancel (admin)', 'paid-memberships-pro');
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since 3.4
	 *
	 * @return string The "help text" to display to the admin when editing the email template.
	 */
	public static function get_template_description() {
		return esc_html__( 'The site administrator can manually cancel a user\'s membership through the WordPress admin or the member can cancel their own membership through your site. This email is sent to the site administrator as confirmation of a cancelled membership.', 'paid-memberships-pro' );
	}

	/**
	 * Get the default subject for the email.
	 *
	 * @since 3.4
	 *
	 * @return string The default subject for the email.
	 */
	public static function get_default_subject() {
		return esc_html__( "Membership for !!user_login!! at !!sitename!! has been CANCELLED", 'paid-memberships-pro' );
	}

	/**
	 * Get the default body content for the email.
	 *
	 * @since 3.4
	 *
	 * @return string The default body content for the email.
	 */
	public static function get_default_body() {
		return wp_kses_post( __( '<p>The membership for !!user_login!! at !!sitename!! has been cancelled.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>

<p>Membership Level: !!membership_level_name!!</p>

<p>Start Date: !!startdate!!</p>

<p>End Date: !!enddate!!</p>

<p>Log in to your WordPress admin here: !!login_url!!</p>', 'paid-memberships-pro' ) );
	}

	/**
	 * Get the email template variables for the email paired with a description of the variable.
	 *
	 * @since 3.4
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public static function get_email_template_variables_with_description() {
		 return array(
			'!!display_name!!' => esc_html__( 'The display name of the user.', 'paid-memberships-pro' ),
			'!!user_login!!' => esc_html__( 'The username of the user.', 'paid-memberships-pro' ),
			'!!user_email!!' => esc_html__( 'The email address of the user.', 'paid-memberships-pro' ),
			'!!membership_id!!' => esc_html__( 'The ID of the membership level.', 'paid-memberships-pro' ),
			'!!membership_level_name!!' => esc_html__( 'The name of the membership level.', 'paid-memberships-pro' ),
			'!!startdate!!' => esc_html__( 'The start date of the membership level.', 'paid-memberships-pro' ),
			'!!enddate!!' => esc_html__( 'The end date of the membership level.', 'paid-memberships-pro' )
		 );
	}

	/**
	 * Get the email address to send the email to.
	 *
	 * @since 3.4
	 *
	 * @return string The email address to send the email to.
	 */
	public function get_recipient_email() {
		return get_bloginfo( 'admin_email' );
	}

	/**
	 * Get the name of the email recipient.
	 *
	 * @since 3.4
	 *
	 * @return string The name of the email recipient.
	 */
	public function get_recipient_name() {
		//get user by email
		$user = get_user_by( 'email', $this->get_recipient_email() );
		return empty( $user->display_name ) ? esc_html__( 'Admin', 'paid-memberships-pro' ) : $user->display_name;
	}

	/**
	 * Get the email template variables for the email.
	 *
	 * @since 3.4
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public function get_email_template_variables() {
		global $wpdb;

		$email_template_variables = array(
			'name' => $this->user->display_name,
			'display_name' => $this->user->display_name,
			'user_login' => $this->user->user_login,
			'user_email' => $this->user->user_email,
		);

		if ( empty( $this->cancelled_level_ids ) ) {
			$email_template_variables['membership_id'] = '';
			$email_template_variables['membership_level_name'] = esc_html__( 'All Levels', 'paid-memberships-pro' );
		} elseif ( is_array( $this->cancelled_level_ids ) ) {
			$email_template_variables['membership_id'] = $this->cancelled_level_ids[0]; // Pass just the first as the level id.
			$email_template_variables['membership_level_name'] = pmpro_implodeToEnglish( $wpdb->get_col( "SELECT name FROM $wpdb->pmpro_membership_levels WHERE id IN('" . implode( "','", $this->cancelled_level_ids ) . "')" ) );
		} else {
			$email_template_variables['membership_id'] = $this->cancelled_level_ids;
			$email_template_variables['membership_level_name'] = pmpro_implodeToEnglish( $wpdb->get_col( "SELECT name FROM $wpdb->pmpro_membership_levels WHERE id = '" . $this->cancelled_level_ids . "'" ) );
		}

		$startdate = $this->get_start_and_end_date( 'startdate' );
		
		if( !empty( $startdate ) ) {
			$email_template_variables['startdate'] = date_i18n( get_option( 'date_format' ), $startdate );
		} else {
			$email_template_variables['startdate'] = "";
		}

		$enddate = $this->get_start_and_end_date( 'enddate' );
		
		if( !empty( $enddate ) ) {
			$email_template_variables['enddate'] = date_i18n( get_option( 'date_format' ), $enddate );
		} else {
			$email_template_variables['enddate'] = "";
		}

		return $email_template_variables;
	}

	/**
	 * Get the start or end date of the membership level that was cancelled.
	 *
	 * @since 3.4
	 * @param string $end_or_start_date Either startdate or enddate.
	 * @return int The start or end date of the membership level that was cancelled.
	 *
	 */
	private function get_start_and_end_date( $end_or_start_date ) {
		global $wpdb;
		
		$old_level_id = $this->cancelled_level_ids;
		// if $this->cancelled_level_ids is an array, get the first level id
		if( is_array( $this->cancelled_level_ids ) ) {
			$old_level_id = $this->cancelled_level_ids[0];
		}

		return $wpdb->get_var(
			"SELECT UNIX_TIMESTAMP(CONVERT_TZ(" . $end_or_start_date .", '+00:00', @@global.time_zone)) as " . $end_or_start_date . " 
			 FROM $wpdb->pmpro_memberships_users 
			 WHERE user_id = '" . $this->user->ID . "' 
			 	AND membership_id = '" . $old_level_id . "' 
				AND status IN('inactive', 'cancelled', 'admin_cancelled') 
			ORDER BY id DESC" );
	}

	/**
	 * Returns the arguments to send the test email from the abstract class.
	 *
	 * @since 3.5
	 *
	 * @return array The arguments to send the test email from the abstract class.
	 */
	public static function get_test_email_constructor_args() {
		global $current_user;

		$all_levels = pmpro_getAllLevels( true );
		$test_level = current( $all_levels );

		return array( $current_user, $test_level->id );
	}
}

/**
 * Register the email template.
 *
 * @since 3.4
 *
 * @param array $email_templates The email templates (template slug => email template class name)
 * @return array The modified email templates array.
 */
function pmpro_email_templates_cancel_admin( $email_templates ) {
	$email_templates['cancel_admin'] = 'PMPro_Email_Template_Cancel_Admin';

	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_templates_cancel_admin' );