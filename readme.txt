=== Paid Memberships Pro ===
Contributors: strangerstudios
Tags: memberships, ecommerce, authorize.net, paypal
Requires at least: 3.0
Tested up to: 3.3.1
Stable tag: 1.3.14

A customizable Membership Plugin for WordPress integrated with Authorize.net or PayPal(r) for recurring payments, flexible content control, themed registration, checkout, and more ...


== Description ==
Paid Memberships Pro is a WordPress Plugin and support community for membership site curators. PMPro's rich feature set allows you to add a new revenue source to your new or current blog or website and is flexible enough to fit the needs of almost all online and offline businesses.

== Installation ==

1. Upload the `paid-memberships-pro` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Follow the instructions here to setup your memberships: http://www.paidmembershipspro.com/support/initial-plugin-setup/

== Frequently Asked Questions ==

= I found a bug in the plugin. =

Please post it in the WordPress support forum and we'll fix it right away. Thanks for helping. http://wordpress.org/tags/paid-memberships-pro?forum_id=10

= I need help installing, configuring, or customizing the plugin. =

Please visit our premium support site at http://www.paidmembershipspro.com for more documentation and our support forums.

== Screenshots ==

1. Paid Memberships Pro supports multiple membership levels.
2. On-site checkout via Authorize.net or PayPal Website Payments Pro. (Off-site checkout coming soon.)
3. Use Discount Codes to offer access at lower prices for special customers.

== Changelog ==
= 1.3.14 =
* Added pmpro_show_cvv filter to hide the CVV from the checkout and billing information pages.
* Updated the billing page to use the pmpro_required_billing_fields like the checkout page does.
* Updated the Authorize.net integration to not pass an empty CVV if the value is empty. Authorize.net will still throw an error if you require CVV via your gateway settings. If you update your settings and PMpro to not require a CVV, you won't get an error.
* Passing the level cost to PayPal Express through the description.
* The billing page doesn't require SSL now if the gateway for the order was PayPal Express. A link to PayPal is shown instead of the form. (Be sure to remove the "becesure" custom field from your billing page if it has one and you don't want this page served over SSL.)
* Fixed bug where the membership level name wasn't being passed to Authorize.net in the description field for the order.
* Added a second paramter ($tags = true) to the pmpro_getLevelCost function. If this is false, strip_tags is run on the cost before returning it. (By default we wrap the prices in <strong> tags which is not good for passing to PayPal for example.)
* Some bug fixes for updating billing against Authorize.net.

= 1.3.13 =
* Fixed warning on checkout page. (Thanks Caps!)
* Fixed bug in PayPal Express checkout that resulted in trying to load the confirmation page over SSL (which would break on some servers). (Thanks Caps!)
* Updated getTaxFromPrice method of order class to allow for better filtering, by level, etc. The pmpro_tax filter now passes the $tax amount, $values (array with price passed and other values), and $this (the order object). It's a little clunky, but must be for backwards compatibility. Custom tax example here: http://www.paidmembershipspro.com/2012/02/custom-tax-structure-using-the-pmpro_tax-hook/
* Removed all TAXAMT NVP parameters in PayPal Express calls. Including these would sometimes introduce errors during checkout. The tax amount is still included in the total amounts passed. Not sure what impact dropping the TAXAMT property will have on reporting in PayPal. I don't believe their tax reporting is the best anyway. Maybe we can build a tax report into PMPro.

= 1.3.12 =
* Fixed bug in members list pagination on sites installed in a subdirectory.
* Now swapping out the PayPal Express checkout button if the level is free or becomes free with a discount code. (Thanks, Caps!)

= 1.3.11 =
* Fixed bug with cancelling a user's membership through the admin.

= 1.3.10 =
* Fixed the links in the discount code table.
* pmpro_hasMembershipLevel(0) and [membership level="0"] will once again return true for non-members. (This broke whent he pmpro_has_membership_level filter was added.)
* WP 3.3.1 testing. (Looks good!)

= 1.3.9 =
* Added a "pmpro_has_membership_level" filter ($r = apply_filters("pmpro_has_membership_level", $r, $user_id, $levels);) which can be used to override the default behavior here.
* Fixed the pmpro shortcodes to allow content above and below the shortcodes on the membership pages. (Thanks, Bluewind!)
* Now setting the user's first and last name to the billing first and last name after checkout.
* Added billing first/last name, billing address, and phone number to the members list screen and CSV export.
* Removed email header/footer code from email class because sometimes it was added twice. Now it is added by the pmpro_send_html function in paid-memberships-pro.php for all emails (WP or PMPro) if a header or footer file are found in your theme folder.
* Added a pmpro_after_phpmailer_init. (The old hook pmpro_after_pmpmailer_init had a typo -- pmpmailer instead of phpmailer.) I left the old hook in for backwards compatibility.

= 1.3.8 =
* Fixed a bug with canceling memberships. Important Note: User requested cancellations were not being forwarded to PayPal and Authorize.net in the past couple updates. Please double check your members lists with your payment gateway subscriptions. Sorry for the inconvenience.
* Fixed a bug in the billing update form.
* Wrapped some output on the billing update form in esc_attr.
* Now sorting countries alphabetically if international orders are turned on.
* Updated the membership-billing page to show country and long form fields if enabled via the hooks pmpro_international_addresses and pmpro_longform_address. (These were only showing up on the checkout form before.)

= 1.3.7 =
* Added "expiration" field to user profile page. Updated the email class to include information on expiration dates in the admin change emails.
* Added "pmpro_profile_show_membership_level" and "pmpro_profile_show_expiration" filters which will hide those fields from the edit profile screen if false is returned.
* Added a pmpro_getMembershipLevelForUser($user_id) function and replaced some redundant code in a few places where we query the DB for this. Maybe we'll have a membership level class as some point. Makes sense now.
* Fixed bug where the wrong price for levels was showing up on the edit profile page in the admin. (It would show the current user's level info instead of the edited user's info.)
* Cleaned up a few more warnings, etc.

= 1.3.6 =
* Changed a few split() calls to explode() to avoid warnings.
* Fixed a couple other warnings/notifices.
* Updated account page to hide the change billing info link if the user doesn't have an active subscription or signed up with PayPal Express.
* Added a filter pmpro_paypal_express_return_url_parameters which can be used to add parameters to the ReturnURL when checking out with PayPal Express. Return an array of key, value pairs. { return array("option" => "1"); }

= 1.3.5 =
* Important update to Authorize.net processing code to account for the "credit card expires before the start of this subscription" error that comes up. For levels/discount codes with no trials or only free trials/initial payments, the subscription setup with Authorize.net starts the day of checkout and a free trial is tacked on for 1 period vs. setting up the subscription one period out. One period is added to the billing limit as well, if applicable. Check the blog for more information.
* Important update for PayPal Website Payments Pro users. When using PayPal WPP, the user will have an option to checkout via PayPal Express as well. PayPal requires this and now we support it.

= 1.3.4 =
* Swapped the $ in the levels page code for $pmpro_currency_symbol.
* Changed the membership shortcode to apply the_content filters to the return value instead of just wpautop. This allows shortcodes within that shortcode and other filters to be run on the content. (Let me know if issues arrise from this.)
* Wrapped some post variables in checkout and billing preheaders with trim()
* Now voiding authorizations with Authorize.net. (The plugin will authorize $1 before setting up a subscription without an initial payment.)
* Now voiding an initial payment with Authorize.net if the subscription setup fails.
* Now refunding an intial payment with PayPal if the subscription setup fails.
* Added a "pmpro_checkout_after_level_cost" to add fields or arbitrary code after the level cost description on the checkout page.
* Added Diner's Club, EnRoute, and JCB as credit card options. Make sure you congiture your Gateway/Merchant account to accept these card types as well.

= 1.3.3 =
* Fixed bug where country field was resetting to default when there were errors with the checkout form submission. (If you templatized your checkout page and have international addresses enabled, you will need to add $bcountry to the globals setup at the top of your checkout template .php)

= 1.3.2 =
* Fixed issue introduced in 1.3.1 where checkout page would not redirect to HTTPS when it should have.
* Fixing issues with slashes in addresses/etc in the checkout form.
* Updated the PMProEmail class to use the wp_mail function instead of use PHPMailer directly. (Thanks VadaPrime: http://wordpress.org/support/topic/plugin-paid-memberships-pro-wp_mail?replies=6#post-2449672)
* Fixed some more notices and warnings.

= 1.3.1 =
* Fixed automatic page creation, which broke in the last update.
* Added hook pmpro_checkout_level which allows you to tweak the $level object before checkout, e.g. to change pricing for upgrades.
* Added hook pmpro_checkout_start_date which allows you to change the start date of a membership before checkout. (preheaders/checkout.php)
* Added hook pmpro_profile_start_date which allows you to change the start date of a membership that is sent to the gateway. (classes/class.memberorder.php)
* Cleaned up some notices and warnings. Will hopefully finish the remaining ones next update.
* Removed some old tinyMCE code that wasn't in use anymore. FYI, WP 3.3 will have a way to include visual editors on other pages, so we may add it to the description field of the membership levels.
* Updated order class to send phone and email to Authorize.net when creating subscriptions. The charge/authorize API support international phone numbers, but the ARB API does not. So if a customer enters an international phone number (or other phone number over 10 characters), the number will be sent for any initial payment/charge, but not for the subscription setup.
* Fixed where !!discount_code!! was not being parsed out in emails.

= 1.3 =
* Added a filter pmpro_login_redirect. You can return false to allow users to signup via the default WP login page.
* Member CSV export no longer limiting to 15 members.
* Correctly adding code_id to the pmpro_memberships_users table on signup. View here for retroactively updating your users tables in case you intend to use that value for advanced functionality.
* Changed URL to send IPN checks for live PayPal instances from www.live.paypal.com to www.paypal.com.
* Updated getfile.php to work when WP is installed in a subdomain.
* Added links to individual settings tabs in the WP menu.
* Changed the architecture of the settings pages which used to all be coded in the membershiplevels.php page. Each settings page has its own script now. I removed the pmpro-data.php service and have the pages submit to themselves now. This won't impact how things work, but will make it easier for me to develop going forward.

= 1.2.10 =
* Added pmpro_confirmation_message hook to change the output on the confirmation page without having to templatize it. The filter passes the constructed html string with the confirmation message and a second parameter containing the order/invoice object if it is a paid membership.
* Added a pmpro_checkout_boxes hook that can be used to output extra fields and other content in the middle of the checkout page.
* Now showing 2 decimals places for the tax rate when showing a membership level's cost.

= 1.2.9 =
* IMPORTANT fix so new user email addresses are properly captured when using PayPal Express.
* rewrote the IPN handler to use the WordPress HTTP API for better compatibility.
* added extra id to tables and fields for easier styling. (let me know if you have suggestions for small changes like these that can save you from having to templatize a page)
* fixed query in readiness check function.
* Authorize.net doesn't support international phone numbers, so we're not sending them to Authorize.net anymore.

= 1.2.8 =
* Ordering levels by id (ascending) on the levels page now. Added a "pmpro_levels_array" filter that can be used to reorder the levels or alter the levels before displaying them on the levels page. The array of levels is the only parameter.
* Added expiration date to the member list and export.
* Showing a member count on the member list page.
* Added filter to change subject lines for PMPro emails. (pmpro_email_subject) The filter's first paramter is the subject, the second parameter is an object containg all of the email information. There are also filters for pmpro_email_recipient, pmpro_email_sender, pmpro_email_sender_name, pmpro_email_template, amd pmpro_email_body.
* Added an RSS feed from the PMPro blog to the dashboard.
* Now only showing the discount code field at checkout if there are discount codes in the database. Can be overriden by the pmpro_show_discount_code filter.
* Cancelling with PayPal now properly updates status to "cancelled".
* No longer trying to unsubscribe from PayPal or Authorize.net if there is no subscription ID to check against (e.g. when the user was manually added to a membership level) or if the last order does not have "success" status (e.g. they already cancelled).
* Removed PHP short tags (e.g., <?=$variable?>) for wider compatibility.

= 1.2.7 =
* Fixed bug with non-USD currencies.
* Fixed bug with phone number formatting.

= 1.2.6 =
* Fixed bug with discount codes showing up in emails, confirmation pages, and invoices.
* Added currency option to gateway settings.

= 1.2.5 =
* PayPal Express support! PayPal Express requires just a verified PayPal Business account (no monthly fees from PayPal).
* Fixed a bug when plans with a "cycle number"/billing frequency that was greater than 1 (e.g. every 4 months). Before the first payment was being scheduled 1 day/month ext out instead of e.g. 4 months out... resulting in an extra payment.
* Added some hooks to support international orders: pmpro_international_addresses, pmpro_default_country, pmpro_countries, pmpro_required_billing_fields. Example code to support international credit cards: https://gist.github.com/1212479

= 1.2.4 =
* VERY IMPORTANT BUG FIX: The getMembershipLevel function of the MemberOrder class had an error where the membership level object was not being created properly during signup and so * recurring subscriptions were not being created *. This update fixes the bug. Thanks to mvp29 for catching this.
* Fixed another bug that was causing warnings on some setups, e.g. WAMP server for Windows.
* Fixed a bug that would show warnings when visiting a login page over HTTPS.
* Fixed membership pricing wording for certain cases, e.g. every 4 months for 4 more payments.
* Fixed a bug in the email generation for order confirmations when discount codes were used. This will no longer freeze the screen.

= 1.2.3 =
* Fixed an error in the DB upgrade code that was keeping the "enddate" from being added to new members' records.

= 1.2.2 =
* Added pmpro_skip_account_fields hook. This value is used to determine if the username/password accounts fields should show up at checkout. By default, it is shown when the user is logged out and not shown when logged in. The hook allows you to return true or false to override this behavior. If the fields are skipped while no user is logged in a username and password will be automatically generated for the new user after checkout.
* You can delete discount codes now from the admin.
* Added a hook pmpro_level_cost_text to allow you to override how the cost is shown on the checkout page. Obviously don't abuse this by showing a different price than what will be charged. Be careful if you change your membership levels pricing to update your filter if needed. The hook passes the text generated by the pmpro_getLevelCost(&$level) function and also a level object which is prepopulated with levels pricing and expiration settings already adjusted for any discount codes that may be in affect.
* Added expiration settings for levels. You can set an "expiration number" and "expiration period" for any level now. e.g. "6 months" or "180 days". You can also alter expiration settings via discount codes. Expirations will be useful for offering free trials which don't require a credit card... and other scenarios you guys have come up with. A script is run once a day using WP Cron that checks for any membership that has ended and then cancels that membership. The user will lose access and the subscription setup in your payment gateway will be canceled.
* Users can "extend" a membership that is set to expire via the Membership Account page.
* Added a hook pmpro_level_expiration_text to allow you to override how the expiration information is shown on the levels and checkout pages. Again don't abuse this by showing a different expiration than is real. Be careful if you change your expiration settings to update your filter if needed. The hook passes the text generated by the pmpro_getLevelExpiration(&$level) function and also a level object which is prepopulated with levels pricing and expiration settings already adjusted for any discount codes that may be in affect.
* Added an error check if the MySQL insertion of the membership level fails. This happens after the user's credit card/etc has already been charged. The plugin tries to cancel the order just made, but might fail. The user is adviced to contact the site owner instead of trying again. I don't want to scare you. We test the checkout process a lot. So assuming that the code hasn't been tampered with and there isn't an internet outage in the microseconds between the order going through and the database being updates, you should never run into this. Still it's nice to have, just in case.
* Fixed a bug that may have caused the billing amount to show up incorrectly on the Membership Account page.
* Added the discount code used to the confirmation page, invoices, and invoice emails.
* Now sending notification emails to members 1 week before their trial period ends (if applicable). A WP cron job is setup on plugin activation. You can disable the email via the pmpro_send_trial_ending_email hook.
* Now sending notification emails to members 1 week before their membership expires (if applicable). A WP cron job is setup on plugin activation. You can disable the email via the pmpro_send_expiration_warning_email hook.
* An email is sent when a membership expires. A WP cron job is setup on plugin activation. You can disable the email via the pmpro_send_expiration_email hook.
* Note: Right now users cannot "extend" a membership that is about to expire without first canceling their current membership. I plan to add "membership extensions" for these cases, but it's a little complicated and I didn't want to hold up this release for them. So Real Soon Now.

= 1.2.1 =
* Fixed bug where non-member admins would be redirected away from the "All Pages" page in the admin.

= 1.2 =
* Fixing some wonkiness with the 1.1.15 update.
* Fixed "warning" showing up on discount code pages.
* Tweaked the admin pages a bit for consistency.
* Added screenshots and FAQ to the readme.
* Figured we were due for a bigger version step.

= 1.1.15 =
* Discount Codes Added!
* Removed some redundant files that slipped into the services folder.
* Fixed the !!levels!! variable for message settings of the advanced tab.
* Changing some ids columns in tables to unsigned.

= 1.1.14 =
* Now encoding #'s when sending info via Authorize.net's API. This may prevent some address conflicts.

= 1.1.13 =
* No longer adding "besecure" custom field to the billing and checkout pages. You can still add this manually to selectively require SSL on a page. If you are trying to do a free membership without SSL, you will have to make sure the besecure custom field is deleted from the Membership-Checkout page, especially if you are upgrading from an older version of PMPro.
* Added a filter before sending the default WP welcome notification email. Return false for the "pmpro_wp_new_user_notification" hook/filter to skip sending the WP default welcome email (because in many cases they are already getting an email from PMPro as well).

= 1.1.12 =
* Revenue report on members list page. (Rought estimate only that doesn't take into account trial periods and billing limits.)
* Enabling weekly recurring payments for Authorize.net by converting week period to 7 days * # months.
* Improved error handling on checkout page.
* Now running "pmpro_after_change_membership_level" actions after the "pmpro_after_checkout" action. Previously this hook was only called when a membership level was changed via the WP admin.
* Won't complain about setting up a Payment Gateway if you only have free membership levels.
* The "besecure" custom field is not added to the billing or checkout by default anymore when you run the "create the pages for me" option in the settings. Whether or not to use HTTPS on a page is now handled in the preheader files for each page (see below).
* The plugin won't force SSL on the checkout page anymore unless the membership level requires payment. If your checkout page is still running over HTTPS/SSL for free membership checkouts, make sure the "besecure" custom field has been deleted on your checkout page. You can use the "besecure" custom field or the "pmpro_besecure" filter to override the plugin's decision.
* The plugin won't force SSL on the cancel page anymore. Again, you can override this using the "besecure" custom field or the "pmpro_besecure" filter.

= 1.1.11 =
* Removed some debug code from the invoice page that might have shown on error.
* Added check to recaptcha library code incase it is already installed. (Let's hope other plugin developers are doing the same.)
* Removed the TinyMCE editor from the description field on the edit membership level page. It was a little buggy. Might bring it back later.

= 1.1.10 =
* added a hook/filter "pmpro_rss_text_filter"
* added a hook/filter "pmpro_non_member_text_filter"
* added a hook/filter "pmpro_not_logged_in_text_filter"
* adjusted the pmpro_has_membership_access() function
* added a hook/filter "pmpro_has_membership_access_filter"
* updated the hook/filter "pmpro_has_membership_access_filter_{post-type}"
* removed the "pmpro_has_membership_access_action_{post-type}" hook/action
* update invoice page to handle case where no invoice is found

= 1.1.9 =
* You can now set individual posts to require membership without assigning them to a category.
* Fixed bug with the confirmation email during signup.
* Fixed a CSS bug on the cancel membership page.

= 1.1.8 =
* Fix for login/registration URL rerouting.
* Added members list to admin bar menu.
* Added warning/error when trying to checkout before the payment gateway is setup.
* Fixed some error handling in the order class.
* Fixed a bug that occurred when processing amounts less than $1.

= 1.1.7 =
* Fixed bugs with http to https redirects and visa versa.
* Fixed redirect bugs for sites installed in a subdomain.

= 1.1.6 =
* Fixed MySQL bug showing up on some users add membership level page.

= 1.1.5 =
* Required fix for PayPal Website Payments Pro processing. Please update.
* Fixed bug with pagination on members list.
* Fixed bugs with errors thrown by MemberOrder class.
* Updated login/registration URL rerouting.

= 1.1.4 =
* Custom Post Types default to allowing access
* Fixed login_redirect code.
* Added pmpro_login_redirect filter for when members login.

= 1.1.3 =
* Getting ready for the WP plugin repository
* License text update.

= 1.1.2 =
* Added hooks to checkout page for customizing registration fields.
* Fixed bug in pmpro_getLevelCost();
* Another CCV/CVV fix for Authorize.net.
* License text update.
* Admin notices are loaded via Ajax now.

= 1.1.1 =
* Added honeypot to signup page.
* Updated pmpro_add_pages to use capabilities instead of user levels
* Fixed checkboxes in admin screens.
* Now checking that passwords match on signup.
* Properly sending CCV/CVV codes to Authorize.net.

= 1.0 =
* This is the launch version. No changes yet.
