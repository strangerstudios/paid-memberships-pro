=== Paid Memberships Pro ===
Contributors: strangerstudios
Tags: memberships, ecommerce, authorize.net, paypal, stripe
Requires at least: 3.0
Tested up to: 3.5.1
Stable tag: 1.6

A customizable Membership Plugin for WordPress integrated with Authorize.net or PayPal(r) for recurring payments, flexible content control, themed registration, checkout, and more ...


== Description ==
Paid Memberships Pro is a WordPress Plugin and support community for membership site curators. PMPro's rich feature set allows you to add a new revenue source to your new or current blog or website and is flexible enough to fit the needs of almost all online and offline businesses.

Accept one-time and recurring payments using Stripe, PayPal Website Payments Pro, PayPal Express, or Authorize.net.

== Installation ==

1. Upload the `paid-memberships-pro` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Follow the instructions in the video here:

http://www.paidmembershipspro.com/documentation/initial-plugin-setup/tutorial-video/

Written instructions:
http://www.paidmembershipspro.com/support/initial-plugin-setup/

== Frequently Asked Questions ==

= My site is broken or blank or not letting me log in after activating Paid Memberships Pro =

This is typically caused by a conflict with another plugin that is trying to redirect around the login/register pages or trying to redirect from HTTP to HTTPS, etc.

To regain access to your site, FTP to your site and rename the wp-content/plugins/paid-memberships-pro folder to wp-content/plugins/paid-memberhsips-pro-d (or anything different). Now WP will not be able to find PMPro, and you can gain access to /wp-admin/ again. From there, visit the plugins page to fully deactivate Paid Memberships Pro. (You'll want to rename the folder back to paid-memberhsips-pro again.)

Long term, you will need to find and fix the conflict. We can usually do this for you very quickly if you sign up for support at http://www.paidmembershipspro.com/pricing/ and send us your WP admin and FTP credentials.

= I found a bug in the plugin. =

Please post it in the WordPress support forum and we'll fix it right away. Thanks for helping. http://wordpress.org/tags/paid-memberships-pro?forum_id=10

= I need help installing, configuring, or customizing the plugin. =

Please visit our premium support site at http://www.paidmembershipspro.com for more documentation and our support forums.

= Does PMPro Support Multisite/Network Installs? =

"Supporting multisite" means different things to different people.

Out of the box PMPro will basically act as a stand alone plugin for each site. Each site has its own list of membership levels, members, payment settings, etc.

I've written a plugin pmpro-network, which is available on GitHub (https://github.com/strangerstudios/pmpro-network) that shows the basics for allowing users who signs up for a membership at one site to be able to create or reclaim their own site under the multisite setup.

If you would like more help using PMPro on a network install, sign up for support at http://www.paidmembershipspro.com.

== Screenshots ==

1. Paid Memberships Pro supports multiple membership levels.
2. On-site checkout via Authorize.net or PayPal Website Payments Pro. (Off-site checkout coming soon.)
3. Use Discount Codes to offer access at lower prices for special customers.

== Changelog == 
= 1.6 =
* Added Braintree payments as a gateway option. This should be considered in "beta". Please get in touch if you are using Braintree payments with PMPro. Everything should function except that we're still working out an issue with the webhook handler.
* Added a new dashboard page Orders to view all orders processed by PMPro with an option to export to CSV.
* Fixed bug where "Your membership level has changed" emails were being sent out the first time a user's profile was edited, even if the level wasn't changing.
* Removed the revenue estimate from the members list page. This causes performance issues on sites with many members. A new reports dashboard page is coming soon.
* Not showing payment settings warning now when Payflow is setup with all values filled in.
* Updated preheaders/billing.php to get the most recent successful order from the DB to use when updating. (ORDER BY id DESC in the query)
* Added pmpro_stripe_subscription_deleted hook in stripe-webhook.php for when subscriptions are cancelled on the Stripe side. Use this code to cancel on your site as well: https://gist.github.com/strangerstudios/5093710
* Now using $pmpro_currency_symbol when membership price is shown on the edit profile page in the dashboard/etc.
* Added pmpro_authorizenet_post_url filter to use Authorize.net gateway class with a different post url, e.g. if you have a gateway that offers an Authorize.net compatibility mode.
* Added pmpro_check_status_after_checkout filter so you can e.g. set the status to "pending" instead of "success" when a user checks out with the check gateway. They will still have access to the membership level, but you can update the status via the orders dashboard later.
* Added pmpro_confirmation_order_status filter so you change which status the confirmation page looks for. Can return an array as well since the getLastMemberOrder method call on MemberOrder has been updated to support $status as an array.
* Orders made via the check gateway now have PaymentType = "Check" and CardType = "".
* Added a notes column to orders.
* Fixed bug where discount codes were not showing up in checkout emails if the level was free.
* Added some wpdb->escape() wrappers to the saveOrder method of MemberOrder which will fix some bugs with orders with fields with apostrophe's in them, etc.
* Added checks for custom capabilities to the PMPro admin pages in case you want to give non-admins access. Caps are: pmpro_discountcodes, pmpro_emailsettings, pmpro_membershiplevels, pmpro_memberslist, pmpro_memberslist_csv, pmpro_orders, pmpro_orders_csv, pmpro_pagesettings, pmpro_paymentsettings
* Added pmpro_memberslist_extra_cols_header and pmpro_memberslist_extra_cols_body hooks to add extra columns to the members list page.
* Fixed pmpro_paypal_express_return_url_parameters filter to properly encode & and = so the params are properly added to the ReturnURL instead of being seen as extra params to the full PayPal Express URL. The PMPro Addon Packages plugin has been updated to take advantage of this to make that plugin more compatible with PayPal Express.
* Fixed bugs with Strip webook: now listening for charge.succeeded and charge.failed, other fixes.

= 1.5.9.2 =
* Fixed Members List bugs introduced in version 1.5.9.1.

= 1.5.9.1 =
* Revamped the ipnhandler code. It's much cleaner now and should be easier to support with all 3 PayPal APIs (Standard, Website Payments Pro, Express) working through the one handler.
* Added Payflow Pro as a gateway option. Currently, only one-time charges is supported.
* Added the pmpro_register_redirect filter to allow you to change the URL PMPro redirects wp-login.php?action=register to. Returning false or an empty string will result in no redirect from the register page.
* Added pmpro_subscription_payment_failed hook that runs if a failed payment comes in through the IPN Handler, Authorize.net silent post, or Stripe web hook. do_action("pmpro_subscription_payment_failed", $old_order); $old_order is a MemberOrder object.

= 1.5.9 =
* Fixed bug on Membership Billing page that was hiding the billing address fields.
* Changed all of the instances of "firstpayment" order statuses to "success". Also running query to fix statues in the DB. This caused issues for levels with only a one-time payment, where the invoice wouldn't show up in their account page.
* Fixed the PayPal Express gateway to correctly set the order transaction id to TRANSACTIONID instead of the PROFILEID.
* Updated the IPN handlers to accept recurring_payment_id or subscr_id when checking for recurring payment orders.
* Changed the site_url() in the javascript for discount codes on the checkout page to home_url(). home_url() is better to use in case users have their WP core files in a different directory.
* You can now place email templates in a paid-memberships-pro/email folder in your theme and PMPro will use these before using the built in email templates. Just copy the file out of the wp-plugins/paid-memberships-pro/email folder, use the same filenames, and edit the file.
* You can now place page templates in a paid-memberships-pro/pages folder in your theme and PMPro will use these before using the built in page templates. Just copy the file out of the wp-plugins/paid-memberships-pro/pages folder, use the same filename, and edit the file.
* You can now place css templates in a paid-memberships-pro/css folder in your theme and PMPro will use these before using the built in page templates. Just copy the file out of the wp-plugins/paid-memberships-pro/css folder, use the same filename, and edit the file.
* Fixed a bug where discount codes that reduced a level price to $0 were not being counted as "uses".
* Added a pmpro_email_data filter to make it easier to add variables to edited email templates.
* Added user_login to the data fields all of the emails for use by templates (use !!user_login!! in your template)
* Added $wp_version to globals set in preheaders/checkout.php to properly compare versions later in the script and avoid a notice.
* Added pmpro_email_filter hook to filter entire email object at once.
* Warning fix: Updated email class to check if the template file exists before trying to load the template into the body. (Useful if you are using the PMProEmail class for your own emails.)

= 1.5.8 =
* Fixed bugs with the Membership Billing page. (Thanks, adambware)
* The getMembershipLevelForUser function and getMembershipLevel method of the MemberOrders class will now include expiration_number and expiration_period properties on the returned level. These are needed to properly extend membership levels when checking out for the same level.
* Added pmpro_before_send_to_paypal_standard hook. This is executed at checkout before calling the sendToPayPal method on the order. The register helper plugin has been updated to update user meta fields during this hook in addition to the pmpro_after_checkout hook. (Because for PayPal Standard, when pmpro_after_checkout is called, the $_SESSION vars are unavailable to it. So other plugins relying on the pmpro_after_checkout hook may have issues with PayPal Standard.)
* Re-Added !class_exists("Stripe") check before loading Stripe library. This assumes that other plugins using the Stripe lib are loading compatible versions and/or also checking first before loading the Stripe lib. (*It's important that you test things if you are using multiple plugins loading Stripe. If the other plugins are loading old Stripe APIs first, PMPro may not work correctly.*) The alternative is to namespace the Stripe library for PMPro which would take some more effort.
* Now running email subject lines through html_entity_decode to avoid special characters for apostrophes/etc.
* pmpro_is_login_page() now also checks if is_page("login")
* The pmpro_login_redirect and pmpro_besecure functions, which handle HTTP/HTTPS logic have been updated. pmpro_besecure is now running on login_init instead of login_head to avoid a "cannot resend headers" error. pmpro_login_redirect will strip https from the URL if FORCE_SSL_LOGIN is set but FORCE_SSL_ADMIN is not set to avoid "need to login twice" bugs.
* Updated code to support auto-hiding ads with newer versions of Easy AdSense.
* Updated how the members list CSV is generated to avoid PHP notices when meta values are not found/etc. Also added a prefix to the enclose function in memberslist-csv.php (enclose => pmpro_enclose).
* Now using get_option("date_format") when outputing a date in the admin, frontend, or in an email.
* Proper trial support for Stripe. (We use the trial_period_days parameter of the Stripe plan object to push the first payment back - since the first payment is handled in its own transaction. We now also add days to this based on the # of trial subscriptions set for the level in the admin.
* Added a pmpro_require_billing javascript variable when using Stripe. If a discount code changes the membership level to free, pmpro_require_billing will be set to false and the Stripe JS checks won't fire.

= 1.5.7 =
* Ready for WordPress 3.5
* Fixed issues in the PayPal IPN Handler that were leading to errors when users would checkout using the PayPal standard gateway.

= 1.5.6.1 =
* Fixed "invalid gateway" bug when using PayPal Express option with the PayPal/PayPalExpress gateway.
* Fixed some warnings.

= 1.5.6 =
* Fixes in the new pmpro_getMemberStartdate and pmpro_getMemberDays functions.
* Fixes to SQL queries for the expiration and trial ending crons.
* Added a pmpro_required_user_fields filter similar to the pmpro_required_billing_fields filter.
* Added a function pmpro_setMessage($message, $type) that sets $pmpro_msg and $pmpro_msgt globals if they aren't set already.
* Added a function pmpro_getClassForField($field) that will return a string including "pmpro_error" or "pmpro_required" if applicable. You can filter the classes added to the fields via the pmpro_field_classes filter.
* Showing * on required fields via javascript on the checkout page.
* Updated checkout page to highlight in red fields that are related to the error message shown.
* Added headers property to the PMProEmail object. You can add headers (e.g. to add a cc or bcc) to PMPro emails using the pmpro_email_headers filter.
* Updated Stripe library to version 1.7.10. Updated PMPro to take advantage of new "interval_count" parameter in subscriptions, so you can now have subscriptions setup for "every 2 months", etc.
* Fix to pmpro_checkout_start_date_keep_startdate filter added in 1.5.5
* Added "Start Date" and "End Date" to emails sent to admins when a membership is cancelled.
* Now checks for CSS files in a paid-memberships-pro subfolder of your active theme and uses those admin.css, frontend.css, and print.css files instead if they exist. (Going to move email and page template checks to that subfolder in the future as well.)

= 1.5.5 =
* Updated pmpro_check_site_url_for_https function to cache the siteurl to limit DB queries.
* Added includes/filters.php to store hacks/filters/actions that were originally developed outside of the PMPro core and brought in later... or just things that are cleaner/easier to impement via hooks and filters.
* Added a "delay" property to the membership shortcode. E.g. [membership level="1" delay="7"]...[/membership] will show that content if a member has had level 1 for at least 7 days.
* If a member checks out for the same level again (extending their membership), the startdate added to pmpro_memberships_users will be their old startdate.
* If a member checks out for the same level again, the remaining days on their existing membership will be added to their new enddate. So e.g. if a user starts an annual membership in April 2013, then checks out again (extends) their membership in February 2014, their new enddate will be April 2015 instead of February 2015. (NOTE: if you were doing this through the custom code here - https://gist.github.com/3678054 - you should remove your custom code.)
* Fixed bug where you couldn't remove all required membership levels from a page/post. (Thanks, lisaleague)
* Updated the button CSS included in paid-memberships-pro/css/frontend.css. I added a pmpro_ prefix to these classes so they don't conflict with other .btn CSS rules. I also changed the rules a bit to show the buttons more consistently. If you relied on the old CSS rules, you may need to tweak your theme to get things looking right.

= 1.5.4 =
* Added a gateway check to preheaders/checkout.php. Mischivous users used to be able to bypass payment by passing &gateway=check or something similar to the checkout page. PMPro would then use the check gateway to checkout. Now only the active gateway option in the payments settings or gateways added via the new pmpro_valid_gateways filter (1 parameter is the array of gateways, add/edit the gateways and return the array). It is important that all PMPro users upgrade to keep mischivious users from accessing your site for free. Any site currently enabling multiple gateway options will need to add code to set the valid gateways. More info here: http://www.paidmembershipspro.com/2012/06/offering-multiple-gateway-options-at-checkout/
* Fixed bug where level restrictions would be deleted if a page were updated via quick edit.
* Added if(!class_exists("Stripe")) to the Stripe class definition. This should help with some conflicts if other plugins have their own Stripe library. (Going to udate the Stripe library in the next version and work on supporting new Stripe functionality.)
* Fixed a bug where copying a level didn't properly set recurring billing settings. (Thanks, AtheistsUnited)
* Fixed some typos. (Thanks, AtheistsUnited)
* Fixed some warnings.

= 1.5.3.1 =
* Fixed bug in expiration warning cron query. (Backported to 1.5.3)

= 1.5.3 =
* Added PayPal Standard Gateway
* Added code to support using Stripe with the minimal billing fields. Use add_filter("pmpro_stripe_lite", "__return_true"); to enable this
* Added an Email setting to send the default WordPress new user email(wp_new_user_notification) or not. By default this was being sent along with the PMPro checkout confirmation email. Now only the checkout confirmation email will be sent unless you check the new setting. You can still override this with the pmpro_wp_new_user_notification filter.
* Fixed bug: Now re-hiding the "Processing..." message if there is a Stripe javascript error at checkout.
* Updated MemberOrder method saveOrder to check for gateway and gateway_environment properties when inserting. If none are found, it will use what is set in your payment settings. This allows you to set the gateway on a MemberOrder object and save the order with that gateway instead of the default.
* Now only showing the check instructions if the gateway is "check" AND the level is not free.
* Added a check to the notification code in the settings header so it wouldn't display NULL in the notification space if WP passes that back.
* Some warning fixes.
* Fixed a bug in the PayPal Express gateway class where the pre-tax amount was being passed to PayPal instead of the tax-computed amount.
* Added Canadian Dollars as a currency option for Stripe.
* Fixed typo/bug with saving trial amounts in the memberships_users table after checkout. (Thanks, Badfun)
* Fixed bug in initial and recurring revenue calculation on members list page.
* Fixed bug when setting membership_level of current user after checkout that could cause various issues. (Thanks, drrobotnik)
* Fixed bug in Stripe webhook that resulting in cancelation emails being sent to the 1st user in the DB vs. the user who cancelled. (Thanks, Kunjan of QuarkStudios.com)
* The getLastMemberOrder() method of the MemberOrder class now returns the last order with status = 'success' by deafult. You can override this via the second parameter of the function. So getLastMemberOrder($user_id, "cancelled") to get the last cancelled order or getLastMemberOrder($user_id, false) to get the last order no matter the status.
* Added pmpro_authnet_silent_post_fields filter and pmpro_before_authnet_silent_post and pmpro_after_authnet_silent_post hooks to the Authorize.net silent post handler. All hooks are passed the $fields variable built at the top of the script that mirrors the $_REQUEST array.

= 1.5.2.1 =
* Fixed bugs with pmpro_hasMembershipLevel.
* Added ability to use the 0 level (non-member) in arrays passed to pmpro_hasMembershipLevel. e.g. pmpro_hasMembershipLevel(0,1,2) = has no membership, level 1, or level 2.
* Fixed bug with the pmpro_after_change_membership_level hook, where a level object was passed instead of the id. The object would be nice to have, but we've been passing the id in the past. I changed it back for reverse compatibility. (You can always look up the level by level_id and user_id.

= 1.5.2 =
* Added "Pay by Check" as a gateway option. Users gain immediate access. You can show instructions for who to write the check out to, where to mail it, etc.
* Added uninstall.php script. (Thanks, badfun)
* Fixed bug where the "Use SSL" option reverted to "No" for Testing, Stripe, and PayPal Express gateways whenever the payments settings page was loaded.
* Fixed bug where the IPN Handler URL was not showing up when PayPal Express was selected.
* Fixed bug where PMPro was not sending the proper trial amount to PayPal when using Website Payments Pro or PayPal Express.
* Added id and status fields to the pmpro_memberships_users table and updated all code to use these fields. This is important for allowing multiple membership levels and tracking cancelled orders. (Thanks, Zookatron!)
* Appending ?level=# to the confirmation page URL after checkout to aid in analytics tracking.
* No longer filtering pages/posts from search results if "show excerpts" is set to YES.
* Showing tax on invoices if applicable.
* Sending tax amount to PayPal Express again.
* Added code to force HTTPS if the siteurl option starts with https:
* Hiding billing information box on Membership Account page if the last invoice was by check or paypal express.
* Added pmpro_email_days_til_expiration and pmpro_email_days_til_trial_end to change how many days before expiration/etc to send an email. The default is 7.
* Fixed typo/bug in preheader/checkout.php RE the pmpro_stripe_verify_address hook. (Thanks, Oniiru!)
* Updated the_excerpt filters to prevent PMPro messages from being added to an excerpt twice in some setups.
* the_content filter removes any class="more-link" class from the content if showing an excerpt.

= 1.5.1 =
* Fixed bug in getfile.php introduced in 1.5.
* Fixed bug in the saveOrder method of the Member Order class. When "updating" vs. "inserting" the $id property of the class was being wiped out. This sometimes caused problems if the id was needed later, e.g. with PayPal Express updating orders.
* Now checking if(!defined("WP_USE_THEMES")) instead of if(function_exists("get_userdata")) to see if WP is already loaded.
* Added initial payment to the fee column of the members list.
* Added initial payment as a column in the members list CSV export.
* Added the pmpro_members_list_csv_extra_columns filter to add columns to the Members List CSV export. Sample usage here: https://gist.github.com/3111715

= 1.5 =
* Very important security fix. Please upgrade to 1.5.
* The Members List CSV export is now executed through admin-ajax.php and will only work if you are logged in as an admin (can manage options).
* Fixed service scripts to work if logged in or logged out.
* Changed the applydiscountcode service to going through the site_url() instead of admin-ajax.php to avoid HTTP/HTTPS issues.

= 1.4.9 =
* Important: Fixed handling of services sent through admin-ajax.php. Your silent post/ipnhandler URLs, etc, may have been updated.
* Added stripslashes() to membership description output on the checkout page.
* The pmpro_getLevel() function may return the wrong level on the levels, checkout, or account page where another $pmpro_levels array was setup. (The array pmpro_getLevel uses used the level id as the array keys. The older $pmpro_levels used 0-n.) To fix this, I added a pmpro_getAllLevels($include_hidden = false) function and now use that function on the levels, checkout, and account pages. The function queries the database for all levels and then puts them into an array where the level ids are the keys.
* Fix for !!billing_country!! in emails. (Somehow a previous fix for this got overwritten. My bad.)
* Settings $pmpro_level->code_id to $discount_code_id if a valid discount code is applied to a level at checkout. This is 
useful for determining if/what discount code was applied to the level when processing it in hooks.
* Added pmpro_getDomainFromURL() and using that to set PMPRO_URL.
* New hooks when orders are added/updated: pmpro_update_order (before update), pmpro_updated_order (after update), pmpro_add_order (before add), pmpro_added_order (after add). (Thanks, zookatron!)

= 1.4.8 =
* Fixed !!siteemail!! values for email templates.
* Adjusted display of "processing" message next to checkout button when clicked.
* Added billing_country to orders table in DB and the memberorder class. Handling countries better through the code.
* Removing closing ?> at the bottom of various files while working through. This can avoid errors on some setups.
* Using wp_enqueue_style to load plugin stylesheets now.
* Added the pmpro_getCheckoutButton($level_id, $button_text, $classes) function and [pmpro_button level="1"] shortcode to add buttons with links to more easily level checkout pages into your pages and themes. Copied over btn and btn-primary styles from Member Lite theme.
* Updated include/require statements to work if the wp-content folder has been renamed or moved.
* Added code to load scripts in the services folder via admin-ajax.php. (Helps when the plugins folder is not where PMPro expects it to be.)
* The discount code AJAX call is using the new service URL (/wp-admin/admin-ajax.php?action=applydiscountcode).
* Added IPN/Silent Post/Webhook instructions to payment settings page.

= 1.4.7 =
* Fixed some notices in the PayPal gateway code.
* No longer calling Stripe JS at checkout if the level is free.
* Fixed some HTTPS handling for ISS hosting. (IIS sets $_SERVER['HTTPS'] to "off" or "on" instead of TRUE or FALSE.)
* Added #pmpro_processing_message to checkout page which is shown when the submit button is clicked. You can override the message with the pmpro_processing_message filter. You can tweak the CSS to show this differently as well.

= 1.4.6 =
* No longer trying to setup a subscription with Stripe for levels with only an Initial Payment amount.
* Updated recaptchalib.php, which fixes issues with using recaptcha.
* Now setting the first_name and last_name meta fields at checkout to match the business first and last name. (Previous scripts to add additional first/last names to the checkout field should override these.)
* Updated the save profile code to only null out the expiration date for a membership if a blank expiration is explicitly passed through the form. If you had other plugins allowing users to edit their profile, etc, it might not have been passing the expiration date and thus updating users expiration dates. Admins and users would have gotten emails.
* Some updates to applydiscountcodes.php service to support plugging into how discount codes function. Added the pmpro_discount_code_level filter to applydiscountcodes.php.

= 1.4.5.1 =
* Removed debug calls to krumo() which would cause fatal errors in certain situations. Please upgrade. (Note that PMPro versions that go three dots deep are usually the most important ones :)

= 1.4.5 =
* Now setting a var "code_level" in javascript in applydiscountcode.php so it can be used to manipulate prices, etc after applying a discount code.
* Added the pmpro_cancel_previous_subscriptions filter, which is set to false will skip cancelling the old membership level/subscription at checkout. This is dangerous, but is used by the pmpro-addon-packages plugin to have an addon charge without affecting the old subscription. This works because the user is checking out for the same membership level. (So they don't really have > 1 membership level.)
* Trimming strings sent to the Authorize.net API in the subscribe and update calls.

= 1.4.4 =
* Using get_admin_url instead of home_url in various places so the links will work on sites installed in a subdirectory. (Notifications, admin bar, pagination in admin screens, etc.)
* Wrapping some XML fields in Authorize.net API calls in <![CDATA[ ]]> to avoid issues when non-text characters (e.g. &) are included in the level name, etc.

= 1.4.3 =
* Fixed a bunch of notices and warnings on discount codes page in admin.
* Added hooks for changing the discount code page: pmpro_save_discount_code_level, pmpro_save_discount_code, pmpro_discount_code_after_settings, pmpro_discount_code_after_level_settings. Look them up in discountcodes.php to see how they work.
* Updated pmpro_send_html(), which filters emails, to use wpautop instead of nl2br. This will fix any extra double spacing you may have noticed in your emails.
* Added a stripslashes around the membership level confirmation text on the confirmation page. Extra slashes were breaking links, etc.
* Added membership level to subject of checkout confirmation email sent to admins.

= 1.4.2 =
* Fixed bug that was added slashes into a level's description and confirmation when saving.
* Removed wp_editor use is the blog is running a version of WordPress < 3.3. (Note: We will only officially support the latest version of WordPress with each release.)
* Added the pmpro_pages_shortcode_{membership page} filter. This can be used to filter the content output by the pmpro_checkout and other page shortcodes. e.g. use pmpro_pages_shortcode_checkout to tweak the HTML output of the pmpro_checkout shortcode. The pages are "account", "billing", "cancel", "checkout", "confirmation", and "levels".
* Added a "use_ssl" option. For the PayPal Website Payments Pro and Authorize.net gateways, this must be on. For Stripe, this will default to on, but can be switched off. For PayPal Express and the test gateway, it will default to off but can be switched on. When on, the checkout and update billing pages will be forced to be served over SSL. If off, those pages will redirect to non-ssl versions. The previous hooks/filters for overriding this will still work.
* Added pmpro_save_membership_level and pmpro_membership_level_after_other_settings hooks to be able to add fields to the new/edit membership level page.
* Fixed some more warnings and notices.
* Updated checkout page to use pmpro_isLevelFree() in logic to display recaptcha or not.

= 1.4.1 =
* Fixed critical bugs with PayPal Express.
* When a PayPal cancellation returns error "11556" (The subscription must have status "active" or "suspended".) I am cancelling the membership without an error. Most likely the PayPal subscription was already cancelled on the PayPal side.
* No longer trying to cancel a subscription with the gateway if a membership/order doesn't have a subscription_transaction_id. (It was a initial payment only membership probably.)

= 1.4 =
* Rewrote how gateways are handled to make it easier to add and manage new gateway options.
* Added Stripe as a gateway option. (http://www.stripe.com)
* Added a "confirmation message" field to the level editor that is shown on the confirmation page after checking out. The message is added to the text that can be filtered using the pmpro_confirmation_message hook.
* Now applying "the_content" filters on the confirmation message on the confirmation page. e.g. wpautop will be run on the text to automatically add paragraphs.
* Now showing the level description on the checkout page. You can use this code to remove the description from the checkout page: https://gist.github.com/2323424
* The description and confirmation fields of the membership level editor now use WP Editor fields with full WYSIWYG support.
* Fixed the logic around setting the $pmpro_display_ads global, used by the pmpro_displayAds() and pmpro_hideAds() functions.
* Fixed bug with recaptcha logic.
* Updated /pages/checkout.php to use wp_login_url function for login link.
* Small changes to pmpro_changeMembershipLevel function to support deleting users when they cancel their account.
* Added the pmpro_member_links_top and pmpro_member_links_bottom hooks so you can add links to the "Member Links" list of the account page. Just output an a tag wrapped in a li tag. (May tweak this to build an array of links that can be filters, but this is good for now.)
* Fixed some more notices.

= 1.3.19 =
* Rewrote the pmpro_login_redirect function. It's cleaner now. Important: there was a pmpro_login_redirect hook in there that was fairly redundant with the core login_redirect hook. I've renamed the pmpro hook to pmpro_login_redirect_to because I had a hook with the same name (pmpro_login_redirect) used in a different place to control whether or not PMPro redirects the register page to the levels page. Having one hook for two things is a bad idea. It seems like more people were using the hook for controlling the registration redirect, so I left that one alone and renamed these.
* Changed PMPro page creation to set all membership pages as subpages of the membership account page. This results in nicer menus for themes that add all top level pages to the menu.
* Updated the checkout page to submit to "" (itself) instead of echoing the checkout page URL here. (Since we can have multiple checkout pages.) This also fixes from SSL conflicts that may crop up on the checkout page.
* Updated the priority of a few actions/hooks so the "besecure" https stuff gets run as soon as possible. Before it was possible that some URLs could be written out with http: on an HTTPS page before PMPro had a chance to fix things. You should have fewer SSL errors on the checkout page to deal with now.
* Added an option on the payment settings page to "nuke" http: links on all secure pages. This option can add time to your page loads, but will ensure that all http: links for your domain are replaced with https: links.
* Allowing multiple pages to use the [pmpro_checkout] shortcode so you can create multiple checkout pages. This is good if you want a separate templated checkout page for each membership level or product you have.
* You can now add a pmpro_default_level custom field, set to the id # of the level you want, that will be used if you navigate directly to a checkout page without setting a level.
* Added some stuff to support adding shipping fields via hooks. Add this plugin to your site, edit, and activate to add shipping to your checkout: https://gist.github.com/1894897
* Removed the price from the description sent to PayPal. The DESC field is limited to 127 characters and must match up across API calls. So there is a good chance the price would get truncated which could be confusing. This was a kind of hack anyway. PayPal should show the price data it has. Not sure why it won't. The price is still reviewed on the review page of your site though.
* The recaptcha code now checks for a previous error before changing pmpro_msg to "All Good".
* Fixed warning in pmpro_has_membership_access(). Fixed a bunch of other warnings here and there.
* Rewrote pmpro_updateMembershipCategories() just to be cleaner
* Added pmpro_state_dropdowns filter. If you return true, the state field will become a dropdown with US states. Use the pmpro_states and pmpro_states_abbreviations filters to change the array of states used.

= 1.3.18.1 =
* Added the new email .html templates to svn.

= 1.3.18 =
* Fixed some warnings: admin bar warning that showed up on admin pages; warning issued by pmpro_setOption(); warning in pmpro_hasMembershipLevel(); warning in billing update; warnings on the user edit page; warnings in the getTax method of the order class; warnings in save method of order class.
* Added a pmpro_checkout_confirm_email filter that can return false to hide and not require the "Confirm E-mail" field at checkout.
* Added a pmpro_checkout_confirm_password filter that can return false to hide and not require the "Confirm Password" field at checkout.
* If the PMPRO_DEFAULT_LEVEL constant is set, traffic on the levels page is redirected to the checkout page. This redirect no longer forces HTTPS.
* Moved the pmpro_paypalexpress_session_vars hook call so it will run even if existing users are checking out (upgrades, etc).
* Added some confirmation emails for admins: (1) for new user signups, (2) when an admin changes a member's level, (3) when a user cancel's their membership, and (4) when a user update's their billing information. New email templates (ending with "_admin.html") have been added to the /email/ folder of the plugin.
* Added new email settings to enable/disable the new admin emails. They will be enabled by default on install and upgrade to 1.3.18. The settings are on the email tab of the PMPro settings.
* Added a couple hooks to the checkout page to have more control over where you add fields, etc. pmpro_checkout_before_submit_button and pmpro_checkout_after_billing_fields.

= 1.3.17.1 =
* Fixing activation bug from 1.3.17.

= 1.3.17 =
* Updated pmpro_hasMembershipLevel() and [membership] shortcode to allow passing a level like "-5" which will return true if the user does NOT have level #5.
* Updated how PMPro notifications are retrieved and shown on the PMPro admin pages. We're using admin-ajax to call the pmpro_notifications function which uses WP's HTTP API to call the www.paidmembershipspro.com server. Only the PMPro version number is passed to check if a notification should be shown. This method shouldn't slow page load since the javascript is called using jQuery's ready function. If the PMPro server is unavailable, you'll get a JS error instead of a PHP one.
* Fixed warning on discount codes page. Fixed some other warnings.
* Updated expiration/trial crons to avoid blank ('') and zero ('0000-00-00 00:00:00') DB values in addition to NULLs. (Some backup programs will incorrectly export NULL dates as '' which could be interpretted as 1/1/1970... meaning the membership has expired.)
* Fixed bug where "Billing Information" was shown on the account page for some free levels.

= 1.3.16 =
* Moved the SSL Seal box lower on the payment settings page.
* Made dashboard menu and admin bar menus consistent. 
* Fixed bug with selecting categories when adding a new level.
* Fixed bug where the user was sometimes redirected to the add level page after adding a level.

= 1.3.15 =
* Fixed SSL handling on the billing page for members without an order.
* Removed single quotes from shortcode examples on page settings page. Doh! (Thanks, Caps)
* Added Multisite/Network FAQ item.
* Updated the payments settings page to convert tax rates like 7 into 0.07. (Tax rates > 1 are divided by 100.)

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
