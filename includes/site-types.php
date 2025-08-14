<?php
/**
 * Code to aid with personalization based on chosen site types.
 * 
 * @since 3.5
 */

/**
 * Helper function to get all site types and their human-readable labels.
 *
 * @since 3.5
 *
 * @return array
 */
function pmpro_get_site_types() {
	// These values will all be escaped when displayed.
	return array(
		'association'		=> __( 'Association', 'paid-memberships-pro' ),
		'premium_content'	=> __( 'Blog/News', 'paid-memberships-pro' ),
		'community'			=> __( 'Community', 'paid-memberships-pro' ),
		'courses'			=> __( 'Courses', 'paid-memberships-pro' ),
		'directory'			=> __( 'Directory/Listings', 'paid-memberships-pro' ),
		'newsletter'		=> __( 'Paid Newsletter', 'paid-memberships-pro' ),
		'podcast'			=> __( 'Podcast', 'paid-memberships-pro' ),
		'video'				=> __( 'Video', 'paid-memberships-pro' ),
		'other'				=> __( 'Other', 'paid-memberships-pro' ),
	);
}

/**
 * Helper function to get the hub links based on site type.
 *
 * @since 3.5
 *
 * @return array
 */
function pmpro_get_site_type_hubs() {
	// These values will all be escaped when displayed.
	return array(
		'association'		=> 'https://www.paidmembershipspro.com/associations/hub/',
		'premium_content'	=> 'https://www.paidmembershipspro.com/blog-news/hub/',
		'community'			=> 'https://www.paidmembershipspro.com/communities/hub/',
		'courses'			=> 'https://www.paidmembershipspro.com/courses/hub/',
		'directory'			=> 'https://www.paidmembershipspro.com/add-ons/member-directory/',
		'newsletter'		=> 'https://www.paidmembershipspro.com/paid-newsletters/hub/',
		'podcast'			=> 'https://www.paidmembershipspro.com/membership-site-podcasting-benefits/',
		'video'				=> 'https://www.paidmembershipspro.com/private-videos/hub/',
	);
}
