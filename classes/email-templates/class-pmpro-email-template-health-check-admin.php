<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email sent to the site admin when a membership health check becomes critical.
 *
 * @since TBD
 */
class PMPro_Email_Template_Health_Check_Admin extends PMPro_Email_Template {

	/**
	 * The critical results to report (check ID => result from PMPro_Health_Checks::run()).
	 *
	 * @var array
	 */
	protected $problems;

	/**
	 * Constructor.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @param array $problems The critical results to report.
	 */
	public function __construct( array $problems ) {
		$this->problems = $problems;
	}

	/**
	 * Get the email template slug.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @return string The email template slug.
	 */
	public static function get_template_slug() {
		return 'health_check_admin';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return esc_html__( 'Membership Health Problem (admin)', 'paid-memberships-pro' );
	}

	/**
	 * Get the email template description.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @return string The email template description.
	 */
	public static function get_template_description() {
		return esc_html__( 'This email is sent to the site admin when a membership health check finds a critical problem, such as subscription payments not being recorded or scheduled tasks not running. It is sent once when the problem is found, and again only if the problem is fixed and comes back.', 'paid-memberships-pro' );
	}

	/**
	 * Get the email subject.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @return string The email subject.
	 */
	public static function get_default_subject() {
		return esc_html__( 'Membership site problem found at {{ sitename }}', 'paid-memberships-pro' );
	}

	/**
	 * Get the email body.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @return string The email body.
	 */
	public static function get_default_body() {
		return wp_kses_post( __( '<p>Paid Memberships Pro found a problem on {{ sitename }} that needs your attention:</p>

{{ health_problems }}

<p>See the details and how to fix it on your Memberships Dashboard: {{ dashboard_url }}</p>', 'paid-memberships-pro' ) );
	}

	/**
	 * Get the email address to send the email to.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @return string The email address to send the email to.
	 */
	public function get_recipient_email() {
		return get_bloginfo( 'admin_email' );
	}

	/**
	 * Get the name of the email recipient.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @return string The name of the email recipient.
	 */
	public function get_recipient_name() {
		$user = get_user_by( 'email', $this->get_recipient_email() );
		return empty( $user->display_name ) ? esc_html__( 'Admin', 'paid-memberships-pro' ) : $user->display_name;
	}

	/**
	 * Get the email template variables for the email paired with a description of the variable.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public static function get_email_template_variables_with_description() {
		return array(
			'{{ health_problems }}' => __( 'A list of the problems found, each with a short explanation.', 'paid-memberships-pro' ),
			'{{ dashboard_url }}'   => __( 'The URL of the Memberships Dashboard, which shows every health check.', 'paid-memberships-pro' ),
		);
	}

	/**
	 * Get the email template variables for the email.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public function get_email_template_variables() {
		$list = '<ul>';
		foreach ( $this->problems as $problem ) {
			$list .= '<li><strong>' . esc_html( $problem['title'] ) . '</strong><br />' . esc_html( $problem['message'] ) . '</li>';
		}
		$list .= '</ul>';

		return array(
			'subject'         => $this->get_default_subject(),
			'health_problems' => $list,
			'dashboard_url'   => add_query_arg( array( 'page' => 'pmpro-dashboard' ), admin_url( 'admin.php' ) ) . '#pmpro_dashboard_health',
		);
	}

	/**
	 * Returns the arguments to send the test email from the abstract class.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @return array The arguments to send the test email from the abstract class.
	 */
	public static function get_test_email_constructor_args() {
		return array(
			array(
				'renewals' => array(
					'title'   => __( 'Subscription payments are not being recorded', 'paid-memberships-pro' ),
					'message' => __( 'This is a test. When a real problem is found, this area explains what is wrong.', 'paid-memberships-pro' ),
				),
			),
		);
	}
}

/**
 * Register the email template.
 *
 * @since TBD
 *
 * @param array $email_templates The email templates (template slug => email template class name)
 * @return array The modified email templates array.
 */
function pmpro_email_templates_health_check_admin( $email_templates ) {
	$email_templates['health_check_admin'] = 'PMPro_Email_Template_Health_Check_Admin';
	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_templates_health_check_admin' );
