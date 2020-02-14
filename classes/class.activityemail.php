<?php
// Make sure PMPro is loaded.
if ( ! class_exists( 'PMProEmail' ) ) {
	return;
}

// Class for Admin Activity Email
class PMPro_Admin_Activity_Email extends PMProEmail {
	private static $instance;

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new PMPro_Admin_Activity_Email();
		}

		return self::$instance;
	}

	/**
	 * Send admin an email summarizing membership site activity.
	 *
	 */
	public function sendAdminActivity( ) {

		ob_start();
		
		?>
		<div style="margin:0;padding:30px;width:100%;background-color:#333333;">
		<center>
			<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;border:0;max-width:600px!important;background-color:#FFFFFF;">
				<tbody>
					<tr>
						<td valign="top" style="background: #FFFFFF;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:25px;color:#444444;padding: 30px;text-align:center;">
							<h2 style="color:#2997c8;font-size: 30px;margin:0px 0px 20px 0px;padding:0px;">Site Title Here</h2>
							<p style="font-size: 20px;line-height: 30px;margin:0px;padding:0px;">Here's a summary of what happened in your Paid Memberships Pro site {yesterday, last week}:</p>
						</td>
					</tr>
					<tr>
						<td valign="top" style="background: #EFEFEF;font-family:Helvetica,Arial,sans-serif;font-size: 20px;line-height: 30px;color:#444444;padding: 15px;text-align:center;">
							<p style="margin:0px;padding:0px;"><strong>2/9/2020 to 2/15/2020</strong></p> <!--Show date range this covers here in their site's date format-->
						</td>
					</tr>
					<tr>
						<td valign="top" style="background: #FFFFFF;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:25px;color:#444444;padding: 30px;text-align:center;">
							<h3 style="color:#2997c8;font-size: 20px;line-height: 30px;margin:0px 0px 15px 0px;padding:0px;">Sales and Revenue</h3>
							<p style="margin:0px 0px 15px 0px;padding:0px;">Your membership site made <strong>$5,600</strong> in revenue last week.</p>
							<table align="center" border="0" cellpadding="0" cellspacing="5" width="100%" style="border:0;background-color:#FFFFFF;text-align: center;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height: 25px;color:#444444;">
								<tr>
									<td width="33%" style="border: 8px solid #dff0d8;color:#3c763d;padding:10px;"><a style="color:#3c763d;display:block;text-decoration: none;" href="#" target="_blank"><div style="font-size:50px;font-weight:900;line-height:65px;">10</div>Joined</a></td>
									<td width="33%" style="border: 8px solid #fcf8e3;color:#8a6d3b;padding:10px;"><a style="color:#8a6d3b;display:block;text-decoration: none;" href="#" target="_blank"><div style="font-size:50px;font-weight:900;line-height:65px;">4</div>Expired</a></td>
									<td width="33%" style="border: 8px solid #f2dede;color:#a94442;padding:10px;"><a style="color:#a94442;display:block;text-decoration: none;" href="#" target="_blank"><div style="font-size:50px;font-weight:900;line-height:65px;">6</div>Cancelled</a></td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td valign="top" style="background: #EFEFEF;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:25px;color:#444444;padding: 30px;text-align:left;">
							<h3 style="color:#2997c8;font-size: 20px;line-height: 30px;margin:0px;padding:0px;"><span style="background: #2997c8;color:#FFFFFF;padding:10px;">180</span> Total Members&mdash;great work!</h3>
							<p>Here is a summary of your top 5 most popular levels:</p>
							<!-- Note: Only show this text above if they have > 5 levels. -->
							<ul>
								<li>Free Members: {100}</li>
								<li>Plus Members: {50}</li>
								<li>Unlimited Members: {7}</li>
								<li>VIP Members: {20}</li>
								<li>MEGA VIP Members: {3}</li>
							</ul>
							<p style="margin:0px;padding:0px;"><a style="color:#2997c8;" href="#" target="_blank">View Signups and Cancellations Report &raquo;</a></p>
						</td>
					</tr>
					<tr>
						<td valign="top" style="background: #FFFFFF;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:25px;color:#444444;padding: 30px;text-align:left;">
							<div style="border: 8px dashed #EFEFEF;padding:30px;margin: 0px 0px 30px 0px;text-align:center;">
								<h3 style="color:#2997c8;font-size: 20px;line-height: 30px;margin:0px 0px 15px 0px;padding:0px;">Discount Code Usage</h3>
								<!--Show if any checkouts using codes in past term. Show code and count of checkouts. Limit to top 5 used codes. -->
								<p style="margin:0px 0px 15px 0px;padding:0;"><strong>15 orders</strong> used a <a style="color:#2997c8;" href="#">Discount Code</a> at checkout. Here is a breakdown of your most used codes:</p>
								<p style="margin:0px 0px 15px 0px;padding:0;"><span style="background-color:#fcf8e3;font-weight:900;padding:5px;">FREEMEMBERSHIP</span> 6 Orders</p>
								<p style="margin:0px 0px 15px 0px;padding:0;"><span style="background-color:#fcf8e3;font-weight:900;padding:5px;">HALFPRICE</span> 4 Orders</p>
							</div>
						</td>
					</tr>
					<tr>
						<td valign="top" style="background: #EFEFEF;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:25px;color:#444444;padding: 30px;text-align:center;">
							<h3 style="color:#2997c8;font-size: 20px;line-height: 30px;margin:0px 0px 15px 0px;padding:0px;">Active Add Ons</h3>
							<table align="center" border="0" cellpadding="0" cellspacing="15" width="100%" style="border:0;background-color:#EFEFEF;text-align: center;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height: 25px;color:#444444;">
								<tr>
									<td width="33%" style="background:#FFFFFF;padding:10px;"><div style="font-size:50px;font-weight:900;line-height:65px;">10</div>Free Add Ons</td>
									<td width="33%" style="background:#FFFFFF;padding:10px;"><div style="font-size:50px;font-weight:900;line-height:65px;">5</div>Plus Add Ons</td>
									<td width="33%" style="background:#f2dede;padding:10px;"><a style="color:#a94442;display:block;text-decoration: none;" href="#" target="_blank"><div style="font-size:50px;font-weight:900;line-height:65px;">4</div>Required Updates</a></td>
								</tr>
							</table>
							<p style="margin:0px;padding:0px;">It is important to keep all Add Ons up to date to take advantage of security improvements, bug fixes, and expanded features. Add On updates can be made <a href="#" target="_blank">via the WordPress Dashboard</a>.</p>
						</td>
					</tr>
					<tr>
						<td valign="top" style="background: #FFFFFF;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:25px;color:#444444;padding: 30px;text-align:left;">
							<h3 style="color:#2997c8;font-size: 20px;line-height: 30px;margin:0px 0px 15px 0px;padding:0px;">Membership Site Administration</h3>
							<ul>
								<li>2 Administrators: <a href="#">jasoncoleman</a>, <a href="#">isaaccoleman</a></li><!-- {links to profile}} -->
								<li>1 Membership Manager: <a href="#">kim</a></li>
							</ul>
							<p style="margin:0px;padding:0px;">Note: It is important to review users with access to your membership site data since they control settings and can modify member accounts.</p>

							<hr style="background-color: #EFEFEF;border: 0;height: 4px;margin: 30px 0px 30px 0px;" />
							<!--Show section below only if there is no license key. -->
							<h3 style="color:#2997c8;font-size: 20px;line-height: 30px;margin:0px 0px 15px 0px;padding:0px;">License Status: None</h3> 
							<p style="margin:0px;padding:0px;">...and that is perfectly OK! PMPro is free to use for as long as you want for membership sites of all sizes. Interested in unlimited support, access to over 70 featured-enhancing Add Ons and instant installs and updates? <a style="color:#2997c8;" href="https://www.paidmembershipspro.com/pricing/?utm_source=plugin&utm_medium=pmpro-admin-activity-email&utm_campaign=pricing&utm_content=license-section" target="_blank">Check out our paid plans to learn more</a>.</p>
						</td>
					</tr>
					<tr>
						<td valign="top" style="background-color:#FFFFFF;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:25px;color:#444444;padding:0;text-align:left;">
							<table align="center" border="0" cellpadding="0" cellspacing="10" width="100%" style="border:0;background-color:#FFFFFF;text-align:left;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height: 25px;color:#444444;">
								<tr valign="top">
									<td width="60%" style="background-color:#EFEFEF;padding:15px;">
										<h3 style="color:#2997c8;font-size: 20px;line-height: 30px;margin:0px 0px 15px 0px;padding:0px;">Recent Articles</h3>
										<!-- look to this for example: https://github.com/strangerstudios/paid-memberships-pro/blob/dev/adminpages/dashboard.php#L351-L354; show max 1 time per week? if daily, exclude it? show one? needs discussion -->
										<p style="margin:0px 0px 15px 0px;padding:0;"><a style="color:#2997c8;" href="#/?utm_source=plugin&utm_medium=pmpro-admin-activity-email&utm_campaign=blog&utm_content=recent-articles-section">Join us for a Live Chat and Premiere of our "How to Set Up Register Helper" Video Stream</a> February 13, 2020</p>
										<p style="margin:0px 0px 15px 0px;padding:0;"><a style="color:#2997c8;" href="#/?utm_source=plugin&utm_medium=pmpro-admin-activity-email&utm_campaign=blog&utm_content=recent-articles-section">Troubleshooting Issues with WP-Cron and Other Scheduled Services</a> February 13, 2020</p>
										<p style="margin:0px 0px 15px 0px;padding:0;"><a style="color:#2997c8;" href="#/?utm_source=plugin&utm_medium=pmpro-admin-activity-email&utm_campaign=blog&utm_content=recent-articles-section">Remove Trial Periods for Existing Members</a> February 11, 2020</p>
										<!--Pull in via RSS Feed from specific category on our blog. Last 3? How do we make sure there isn’t the same thing sent twice? Could this be dynamic and we choose to occasionally send something different? -->
									</td>
									<td width="40%" style="background-color:#EFEFEF;padding:15px;"> 
										<h3 style="color:#2997c8;font-size: 20px;line-height: 30px;margin:0px 0px 15px 0px;padding:0px;">PMPro Stats</h3>
										<p style="margin:0px 0px 15px 0px;padding:0px;"><strong>80,000</strong> Sites Use PMPro.</p>
										<p style="margin:0px 0px 15px 0px;padding:0px;"><strong>8+</strong> Years in Development.</p>
										<p style="margin:0px 0px 15px 0px;padding:0px;"><a style="color:#2997c8;" href="https://twitter.com/pmproplugin" target="_blank">Follow @pmproplugin on Twitter</a></p>
										<p style="margin:0px 0px 15px 0px;padding:0px;"><a style="color:#2997c8;" href="https://www.facebook.com/PaidMembershipsPro/" target="_blank">Like Us on Facebook</p></p>
										<p style="margin:0px 0px 15px 0px;padding:0px;"><a style="color:#2997c8;" href="https://www.youtube.com/user/strangerstudiostv" target="_blank">Subscribe to Our YouTube Channel</a></p>
										<!-- Show Plus signups count? Rating? -->
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td valign="top" style="background: #333333;font-family:Helvetica,Arial,sans-serif;font-size:20px;line-height:30px;color:#FFFFFF;padding: 30px;text-align:center;">
							<p style="margin:0px 0px 15px 0px;padding:0px;">This email is automatically generated by your WordPress site and sent to your Administration Email Address set under Settings > General in your WordPress dashboard.</p>
							<p style="margin:0px;padding:0px;">To adjust the frequency of this message or disable these emails completely, you can <a style="color:#FFFFFF;" href="#" target="_blank">update the "Admin Activity Email" setting here</a>.</p> <!-- {link to advanced settings page} -->
						</td>
					</tr>
				</tbody>
			</table>
		</center>
		</div>
		<?php

		$admin_activity_email_body = ob_get_contents();

		ob_end_clean();

		$this->email = get_bloginfo( 'admin_email' );
		$this->subject = sprintf( __( '[%s] Paid Memberships Pro Activity for {term} - {date format}', 'paid-memberships-pro' ), get_bloginfo( 'name' ) );
		$this->template = 'admin_activity_email';
		$this->body = $admin_activity_email_body;
		$this->from     = pmpro_getOption( 'from' );
		$this->fromname = pmpro_getOption( 'from_name' );

		return $this->sendEmail();
	}

}
PMPro_Admin_Activity_Email::get_instance();

/*
 * Sends test PMPro email when "send_test_email" param is passed in URL.
 */
function my_pmpro_send_test_email() {
	if ( is_admin() && ! empty($_REQUEST[ 'send_test_email' ] ) ) {
		$pmproemail = new PMPro_Admin_Activity_Email();
		$pmproemail->sendAdminActivity( );
		echo 'Test email sent.';
		wp_die();
	}
}
add_action('init', 'my_pmpro_send_test_email');
