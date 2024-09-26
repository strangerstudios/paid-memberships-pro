<?php
global $post, $gateway, $wpdb, $besecure, $discount_code, $discount_code_id, $pmpro_level, $pmpro_msg, $pmpro_msgt, $pmpro_review, $skip_account_fields, $pmpro_paypal_token, $pmpro_show_discount_code, $pmpro_error_fields, $pmpro_required_billing_fields, $pmpro_required_user_fields, $wp_version, $current_user, $pmpro_checkout_level_ids;

// we are on the checkout page
add_filter( 'pmpro_is_checkout', '__return_true' );

//this var stores fields with errors so we can make them red on the frontend
$pmpro_error_fields = array();

//blank array for required fields, set below
$pmpro_required_billing_fields = array();
$pmpro_required_user_fields    = array();

/**
 * If there is a token order passed in the URL, we are processing the payment for that order.
 */
if ( ! empty( $_REQUEST['pmpro_order'] ) ) {
	$order_code = sanitize_text_field( $_REQUEST['pmpro_order'] );
	$order_obj  = new MemberOrder( $order_code );
	if ( ! empty( $order_obj->id ) ) {
		// $pmpro_review is a legacy variable from the old PayPal Express flow. When set, it was used to
		// display a version of the checkout page where the user could review their order before submitting.
		// Fields were not editable.
		// We are reworking this variable to maintain backwards compatiblity with custom page templates and
		// setting it whenever a token order is passed in the URL and requires addtional payment steps.
		$pmpro_review = $order_obj;

		// If the order is not for the current user or the order is in error status, redirect to the account page.
		if ( $current_user->ID != $pmpro_review->user_id || 'error' === $pmpro_review->status ) {
			wp_redirect( pmpro_url( 'account' ) );
			exit;
		}

		// If the order has already had a payment submitted, redirect to the confirmation page.
		if ( in_array( $pmpro_review->status, array( 'success', 'pending' ) ) ) {
			wp_redirect( pmpro_url( 'confirmation', '?level=' . $pmpro_review->membership_id ) );
			exit;
		}

		pmpro_pull_checkout_data_from_order( $pmpro_review );
	} else {
		// This is an invalid order. Redirect to the account page.
		wp_redirect( pmpro_url( 'account' ) );
		exit;
	}
}

//was a gateway passed?
if ( ! empty( $pmpro_review ) ) {
	$gateway = $pmpro_review->gateway;
} elseif ( ! empty( $_REQUEST['gateway'] ) ) {
	$gateway = sanitize_text_field($_REQUEST['gateway']);
} else {
	$gateway = get_option( "pmpro_gateway" );
}

//set valid gateways - the active gateway in the settings and any gateway added through the filter will be allowed
$valid_gateways = apply_filters( "pmpro_valid_gateways", array( get_option( "pmpro_gateway" ) ) );

//let's add an error now, if an invalid gateway is set
if ( ! in_array( $gateway, $valid_gateways ) ) {
	$pmpro_msg  = __( "Invalid gateway.", 'paid-memberships-pro' );
	$pmpro_msgt = "pmpro_error";
}

/**
 * Action to run extra preheader code before setting checkout level.
 *
 * @since 2.0.5
 */
do_action( 'pmpro_checkout_preheader_before_get_level_at_checkout' );

//what level are they purchasing? (discount code passed)
$pmpro_level = pmpro_getLevelAtCheckout();

/**
 * Action to run extra preheader code after setting checkout level.
 *
 * @since 2.0.5
 * //TODO update docblock
 */
do_action( 'pmpro_checkout_preheader_after_get_level_at_checkout', $pmpro_level );

if ( empty( $pmpro_level->id ) ) {
	wp_redirect( pmpro_url( "levels" ) );
	exit( 0 );
}

//enqueue some scripts
wp_enqueue_script( 'jquery.creditCardValidator', plugins_url( '/js/jquery.creditCardValidator.js', dirname( __FILE__ ) ), array( 'jquery' ), '1.2' );

global $wpdb, $current_user, $pmpro_requirebilling;
//unless we're submitting a form, let's try to figure out if https should be used

if ( ! pmpro_isLevelFree( $pmpro_level ) ) {
	//require billing and ssl
	$pagetitle            = __( "Checkout: Payment Information", 'paid-memberships-pro' );
	$pmpro_requirebilling = true;
	$besecure             = get_option( "pmpro_use_ssl" );
} else {
	//no payment so we don't need ssl
	$pagetitle            = __( "Set Up Your Account", 'paid-memberships-pro' );
	$pmpro_requirebilling = false;
	$besecure             = false;
}

// Allow for filters.
// TODO: docblock.
/**
 * @deprecated 3.2
 */
$pmpro_requirebilling = apply_filters_deprecated( 'pmpro_require_billing', array( $pmpro_requirebilling, $pmpro_level ), '3.2' );

//in case a discount code was used or something else made the level free, but we're already over ssl
if ( ! $besecure && ! empty( $_REQUEST['submit-checkout'] ) && is_ssl() ) {
	$besecure = true;
}    //be secure anyway since we're already checking out

//action to run extra code for gateways/etc
do_action( 'pmpro_checkout_preheader' );

// We set a global var for add-ons that are expecting it.
$pmpro_show_discount_code = pmpro_show_discount_code();

/**
 * Set whether the account fields should be skipped on the checkout page.
 * This filter is useful when you do not want to show the account fields during the initial signup process.
 *
 * @param bool $skip_account_fields True if the account fields should be skipped.
 * @param WP_User|null $current_user The current user object or null if there is no user.
 */
$skip_account_fields = apply_filters( "pmpro_skip_account_fields", ! empty( $current_user->ID ), $current_user );

//load em up (other fields)
global $username, $password, $password2, $bfirstname, $blastname, $baddress1, $baddress2, $bcity, $bstate, $bzipcode, $bcountry, $bphone, $bemail, $bconfirmemail, $CardType, $AccountNumber, $ExpirationMonth, $ExpirationYear;

if ( isset( $_REQUEST['order_id'] ) ) {
	$order_id = intval( $_REQUEST['order_id'] );
} else {
	$order_id = "";
}
if ( isset( $_REQUEST['bfirstname'] ) ) {
	$bfirstname = stripslashes( sanitize_text_field( $_REQUEST['bfirstname'] ) );
} else {
	$bfirstname = "";
}
if ( isset( $_REQUEST['blastname'] ) ) {
	$blastname = stripslashes( sanitize_text_field( $_REQUEST['blastname'] ) );
} else {
	$blastname = "";
}
if ( isset( $_REQUEST['fullname'] ) ) {
	$fullname = sanitize_text_field( $_REQUEST['fullname'] );
}        //honeypot for spammers
if ( isset( $_REQUEST['baddress1'] ) ) {
	$baddress1 = stripslashes( sanitize_text_field( $_REQUEST['baddress1'] ) );
} else {
	$baddress1 = "";
}
if ( isset( $_REQUEST['baddress2'] ) ) {
	$baddress2 = stripslashes( sanitize_text_field( $_REQUEST['baddress2'] ) );
} else {
	$baddress2 = "";
}
if ( isset( $_REQUEST['bcity'] ) ) {
	$bcity = stripslashes( sanitize_text_field( $_REQUEST['bcity'] ) );
} else {
	$bcity = "";
}

if ( isset( $_REQUEST['bstate'] ) ) {
	$bstate = stripslashes( sanitize_text_field( $_REQUEST['bstate'] ) );
} else {
	$bstate = "";
}

//convert long state names to abbreviations
if ( ! empty( $bstate ) ) {
	global $pmpro_states;
	foreach ( $pmpro_states as $abbr => $state ) {
		if ( $bstate == $state ) {
			$bstate = $abbr;
			break;
		}
	}
}

if ( isset( $_REQUEST['bzipcode'] ) ) {
	$bzipcode = stripslashes( sanitize_text_field( $_REQUEST['bzipcode'] ) );
} else {
	$bzipcode = "";
}
if ( isset( $_REQUEST['bcountry'] ) ) {
	$bcountry = stripslashes( sanitize_text_field( $_REQUEST['bcountry'] ) );
} else {
	$bcountry = "";
}
if ( isset( $_REQUEST['bphone'] ) ) {
	$bphone = stripslashes( sanitize_text_field( $_REQUEST['bphone'] ) );
} else {
	$bphone = "";
}
if ( isset ( $_REQUEST['bemail'] ) ) {
	$bemail = stripslashes( sanitize_email( $_REQUEST['bemail'] ) );
} elseif ( is_user_logged_in() ) {
	$bemail = $current_user->user_email;
} else {
	$bemail = "";
}
if ( isset( $_REQUEST['bconfirmemail_copy'] ) ) {
	$bconfirmemail = $bemail;
} elseif ( isset( $_REQUEST['bconfirmemail'] ) ) {
	$bconfirmemail = stripslashes( sanitize_email( $_REQUEST['bconfirmemail'] ) );
} elseif ( is_user_logged_in() ) {
	$bconfirmemail = $current_user->user_email;
} else {
	$bconfirmemail = "";
}

if ( isset( $_REQUEST['CardType'] ) && ! empty( $_REQUEST['AccountNumber'] ) ) {
	$CardType = sanitize_text_field( $_REQUEST['CardType'] );
} else {
	$CardType = "";
}
if ( isset( $_REQUEST['AccountNumber'] ) ) {
	$AccountNumber = sanitize_text_field( $_REQUEST['AccountNumber'] );
} else {
	$AccountNumber = "";
}

if ( isset( $_REQUEST['ExpirationMonth'] ) ) {
	$ExpirationMonth = sanitize_text_field( $_REQUEST['ExpirationMonth'] );
} else {
	$ExpirationMonth = "";
}
if ( isset( $_REQUEST['ExpirationYear'] ) ) {
	$ExpirationYear = sanitize_text_field( $_REQUEST['ExpirationYear'] );
} else {
	$ExpirationYear = "";
}
if ( isset( $_REQUEST['CVV'] ) ) {
	$CVV = sanitize_text_field( $_REQUEST['CVV'] );
} else {
	$CVV = "";
}

if ( ! empty( $pmpro_level->discount_code ) ) {
	$discount_code = preg_replace( "/[^A-Za-z0-9\-]/", "", sanitize_text_field( $pmpro_level->discount_code ) );
} else {
	$discount_code = "";
}
if ( isset( $_REQUEST['username'] ) ) {
	$username = sanitize_user( $_REQUEST['username'] , true);
} else {
	$username = "";
}

// Note: We can't sanitize the passwords. They get hashed when saved.
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
if ( isset( $_REQUEST['password'] ) ) {
	$password = $_REQUEST['password'];
} else {
	$password = "";
}
if ( isset( $_REQUEST['password2_copy'] ) ) {
	$password2 = $password;
} elseif ( isset( $_REQUEST['password2'] ) ) {
	$password2 = $_REQUEST['password2'];
} else {
	$password2 = "";
}
// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

$submit = pmpro_was_checkout_form_submitted();

/**
 * Hook to run actions after the parameters are set on the checkout page.
 * @since 2.1
 */
do_action( 'pmpro_checkout_after_parameters_set' );

//require fields
$pmpro_required_billing_fields = array(
	"bfirstname"      => $bfirstname,
	"blastname"       => $blastname,
	"baddress1"       => $baddress1,
	"bcity"           => $bcity,
	"bstate"          => $bstate,
	"bzipcode"        => $bzipcode,
	"bphone"          => $bphone,
	"bemail"          => $bemail,
	"bcountry"        => $bcountry,
	"CardType"        => $CardType,
	"AccountNumber"   => $AccountNumber,
	"ExpirationMonth" => $ExpirationMonth,
	"ExpirationYear"  => $ExpirationYear,
	"CVV"             => $CVV
);
$pmpro_required_billing_fields = apply_filters( "pmpro_required_billing_fields", $pmpro_required_billing_fields );
$pmpro_required_user_fields    = array(
	"username"      => $username,
	"password"      => $password,
	"password2"     => $password2,
	"bemail"        => $bemail,
	"bconfirmemail" => $bconfirmemail
);
$pmpro_required_user_fields    = apply_filters( "pmpro_required_user_fields", $pmpro_required_user_fields );

//pmpro_confirmed is set to true later if payment goes through
$pmpro_confirmed = false;

// If there was a checkout submission, make sure that the form submission is valid.
if ( $submit && $pmpro_msgt != "pmpro_error" ) {
	// Check the nonce.
	if ( empty( $_REQUEST['pmpro_checkout_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['pmpro_checkout_nonce'] ), 'pmpro_checkout_nonce' ) ) {
		// Nonce is not valid, but a nonce was only added in the 3.0 checkout template. We only want to show an error if the checkout template is 3.0 or later.
		$loaded_path = pmpro_get_template_path_to_load( 'checkout' );
		$loaded_version = pmpro_get_version_for_page_template_at_path( $loaded_path );
		if ( ! empty( $loaded_version ) && version_compare( $loaded_version, '3.0', '>=' ) ) {
			// Nonce is not valid. Show an error.
			pmpro_setMessage( __( "Nonce security check failed.", 'paid-memberships-pro' ), 'pmpro_error' );
		}
	}

	// Make sure javascript is ok.
	if ( apply_filters( "pmpro_require_javascript_for_checkout", true ) && ! empty( $_REQUEST['checkjavascript'] ) && empty( $_REQUEST['javascriptok'] ) ) {
		pmpro_setMessage( __( "There are JavaScript errors on the page. Please contact the webmaster.", 'paid-memberships-pro' ), "pmpro_error" );
	}

	// Make sure honeypot is ok.
	if ( ! empty( $fullname ) ) {
		pmpro_setMessage( __( "Are you a spammer?", 'paid-memberships-pro' ), "pmpro_error" );
		$pmpro_error_fields[] = "fullname";
	}
}

// If there is still a valid checkout submission, allow custom code to halt the checkout.
if ( $submit && $pmpro_msgt != "pmpro_error" ) {
	/**
	 * Filter whether the current checkout should continue.
	 *
	 * This filter will be checked every time that a checkout form is submitted regardless of if there is already a user or if $pmpro_review is set.
	 * It should be used for checks that have to do with the form submisision itself, such as captchas.
	 *
	 * @param bool $pmpro_checkout_checks True if the checkout should continue.
	 */
	$pmpro_checkout_checks = apply_filters( "pmpro_checkout_checks", true );
	if ( ! $pmpro_checkout_checks ) {
		// If this is false, there should have been an error message set by the filter but just in case, set a generic error message.
		pmpro_setMessage( __( 'Checkout checks failed.', 'paid-memberships-pro' ), 'pmpro_error' );
	}
}

// If there is still a valid checkout submission and we don't have an order yet, run the the code needed to get to that point in the checkout process.
if ( $submit && $pmpro_msgt != 'pmpro_error' && empty( $pmpro_review ) ) {
	// Fill out account fields if we are skipping the account fields and we don't have a user yet.
	if ( empty( $current_user->ID ) && $skip_account_fields ) {
		// If the first name, last name, and email address are set, use them to generate the username and password.
		if ( ! empty( $bfirstname ) && ! empty( $blastname ) && ! empty( $bemail ) ) {
			// Generate the username using the first name, last name and/or email address.
			$username = pmpro_generateUsername( $bfirstname, $blastname, $bemail );

			// Generate the password.
			$password  = wp_generate_password();

			// Set the password confirmation to the generated password.
			$password2 = $password;
		}
	}

	// If we don't have a user yet, check the user fields.
	if ( empty( $current_user->ID ) ) {
		foreach ( $pmpro_required_user_fields as $key => $field ) {
			if ( ! $field ) {
				$pmpro_error_fields[] = $key;
			}
		}
		if ( ! empty( $pmpro_error_fields ) ) {
			pmpro_setMessage( __( "Please complete all required fields.", 'paid-memberships-pro' ), "pmpro_error" );
		}
		if ( $password != $password2 ) {
			pmpro_setMessage( __( "Your passwords do not match. Please try again.", 'paid-memberships-pro' ), "pmpro_error" );
			$pmpro_error_fields[] = "password";
			$pmpro_error_fields[] = "password2";
		}
		if ( strcasecmp($bemail, $bconfirmemail) !== 0 ) {
			pmpro_setMessage( __( "Your email addresses do not match. Please try again.", 'paid-memberships-pro' ), "pmpro_error" );
			$pmpro_error_fields[] = "bemail";
			$pmpro_error_fields[] = "bconfirmemail";
		}
		if ( ! is_email( $bemail ) ) {
			pmpro_setMessage( __( "The email address entered is in an invalid format. Please try again.", 'paid-memberships-pro' ), "pmpro_error" );
			$pmpro_error_fields[] = "bemail";
			$pmpro_error_fields[] = "bconfirmemail";
		}
		$ouser = get_user_by( 'login', $username );
		if ( ! empty( $ouser->user_login ) ) {
			pmpro_setMessage( __( "That username is already taken. Please try another.", 'paid-memberships-pro' ), "pmpro_error" );
			$pmpro_error_fields[] = "username";
		}
		$oldem_user = get_user_by( 'email', $bemail );
		$oldem_user = apply_filters_deprecated( "pmpro_checkout_oldemail", array( ( false !== $oldem_user ? $oldem_user->user_email : null ) ), '3.2' );
		if ( ! empty( $oldem_user ) ) {
			pmpro_setMessage( __( "That email address is already in use. Please log in, or use a different email address.", 'paid-memberships-pro' ), "pmpro_error" );
			$pmpro_error_fields[] = "bemail";
			$pmpro_error_fields[] = "bconfirmemail";
		}
	}

	// Make sure to mark billing fields as missing if they aren't filled out.
	if ( $pmpro_requirebilling ) {
		//filter
		foreach ( $pmpro_required_billing_fields as $key => $field ) {
			if ( ! $field ) {
				$pmpro_error_fields[] = $key;
			}
		}
	}

	// If there is still a vaild checkout submission, give custom code the chance to halt all checkouts (to be deprecated).
	if ( $pmpro_msgt != "pmpro_error" ) {
		/**
		 * Filter whether the current checkout should continue.
		 * Note: This will be deprecated in a future version. Use pmpro_checkout_checks, pmpro_checkout_user_creation_checks, or pmpro_checkout_order_creation_checks instead.
		 *
		 * @param bool $pmpro_continue_registration True if the checkout should continue.
		 */
		$pmpro_continue_registration = apply_filters( "pmpro_registration_checks", true );
		if ( ! $pmpro_continue_registration ) {
			// If this is false, there should have been an error message set by the filter but just in case, set a generic error message.
			pmpro_setMessage( __( 'Checkout checks failed.', 'paid-memberships-pro' ), 'pmpro_error' );
		}
	}

	// If there is still a valid checkout submission and we don't have a user yet, give custom code the chance to halt user creation.
	if ( $pmpro_msgt != "pmpro_error" && empty( $current_user->ID ) ) {
		/**
		 * Filter whether this checkout should proceed to the user creation step.
		 *
		 * @param bool $pmpro_checkout_user_creation_checks True if the checkout should continue.
		 */
		$pmpro_checkout_user_creation_checks = apply_filters( 'pmpro_checkout_user_creation_checks', true );
		if ( ! $pmpro_checkout_user_creation_checks ) {
			// If this is false, there should have been an error message set by the filter but just in case, set a generic error message.
			pmpro_setMessage( __( 'User creation checks failed.', 'paid-memberships-pro' ), 'pmpro_error' );
		}
	}

	// If there is still a vaild checkout submission but we don't have a user yet, create one.
	if ( $pmpro_msgt != "pmpro_error" && empty( $current_user->ID ) ) {
		//first name
		if ( ! empty( $_REQUEST['first_name'] ) ) {
			$first_name = sanitize_text_field( $_REQUEST['first_name'] );
		} else {
			$first_name = $bfirstname;
		}
		//last name
		if ( ! empty( $_REQUEST['last_name'] ) ) {
			$last_name = sanitize_text_field( $_REQUEST['last_name'] );
		} else {
			$last_name = $blastname;
		}

		//insert user
		$new_user_array = apply_filters( 'pmpro_checkout_new_user_array', array(
				"user_login" => $username,
				"user_pass"  => $password,
				"user_email" => $bemail,
				"first_name" => $first_name,
				"last_name"  => $last_name
			)
		);

		$user_id = apply_filters_deprecated( 'pmpro_new_user', array( '', $new_user_array ), '3.2' );
		if ( empty( $user_id ) ) {
			$user_id = wp_insert_user( $new_user_array );
		}

		if ( empty( $user_id ) || is_wp_error( $user_id ) ) {
			$e_msg = '';

			if ( is_wp_error( $user_id ) ) {
				$e_msg = $user_id->get_error_message();
			}

			$pmpro_msg  = __( "There was an error setting up your account. Please contact us.", 'paid-memberships-pro' ) . sprintf( " %s", $e_msg ); // Dirty 'don't break translation hack.
			$pmpro_msgt = "pmpro_error";
		} elseif ( apply_filters( 'pmpro_setup_new_user', true, $user_id, $new_user_array, $pmpro_level ) ) {

			pmpro_maybe_send_wp_new_user_notification( $user_id, $pmpro_level->id );

			$wpuser = get_userdata( $user_id );
			$wpuser->set_role( get_option( 'default_role', 'subscriber' ) );

			/**
			 * Allow hooking before the user authentication process when setting up new user.
			 *
			 * @since 2.5.10
			 *
			 * @param int $user_id The user ID that is being setting up.
			 */
			do_action( 'pmpro_checkout_before_user_auth', $user_id );


			//okay, log them in to WP
			$creds                  = array();
			$creds['user_login']    = $new_user_array['user_login'];
			$creds['user_password'] = $new_user_array['user_pass'];
			$creds['remember']      = true;
			$user                   = wp_signon( $creds, false );
			//setting some cookies
			wp_set_current_user( $user_id, $username );
			wp_set_auth_cookie( $user_id, true, apply_filters( 'pmpro_checkout_signon_secure', force_ssl_admin() ) );
			global $current_user;
			if ( ! $current_user->ID && $user->ID ) {
				$current_user = $user;
			} //in case the user just signed up
			pmpro_set_current_user();

			// Update nonce value to be for this new user when we load the checkout page.
			add_filter( 'pmpro_update_nonce_at_checkout', '__return_true' );

			// Skip the account fields since we just created an account.
			$skip_account_fields = true;
		}
	}

	// If there is still a valid checkout submission, check the billing fields.
	if ( $pmpro_msgt != "pmpro_error" ) {
		// We can check the billing fields at this point by checking if $pmpro_error_fields is not empty.
		if ( ! empty( $pmpro_error_fields ) ) {
			pmpro_setMessage( __( "Please complete all required fields.", 'paid-memberships-pro' ), "pmpro_error" );
		}
		if ( ! empty( $bemail ) && ! is_email( $bemail ) ) {
			pmpro_setMessage( __( "The email address entered is in an invalid format. Please try again.", 'paid-memberships-pro' ), "pmpro_error" );
			$pmpro_error_fields[] = "bemail";
			$pmpro_error_fields[] = "bconfirmemail";
		}
		if ( ! in_array( $gateway, $valid_gateways ) ) {
			pmpro_setMessage( __( "Invalid gateway.", 'paid-memberships-pro' ), "pmpro_error" );
		}
		if ( ! empty( $fullname ) ) {
			pmpro_setMessage( __( "Are you a spammer?", 'paid-memberships-pro' ), "pmpro_error" );
		}
	}

	// If there is still a valid checkout submission, give custom code the chance to halt checkout.
	if ( $pmpro_msgt != "pmpro_error" ) {
		/**
		 * Filter whether this checkout should proceed to the order creation step.
		 *
		 * @param bool $pmpro_checkout_checks True if the checkout should continue.
		 */
		$pmpro_checkout_order_creation_checks = apply_filters( "pmpro_checkout_order_creation_checks", true );
		if ( ! $pmpro_checkout_order_creation_checks ) {
			// If this is false, there should have been an error message set by the filter but just in case, set a generic error message.
			pmpro_setMessage( __( 'Order creation checks failed.', 'paid-memberships-pro' ), 'pmpro_error' );
		}
	}

	// If there is still a valid checkout submission, create the order.
	if ( $pmpro_msgt != "pmpro_error" ) {
		$pmpro_review                   = new MemberOrder();
		$pmpro_review->user_id          = $current_user->ID;
		$pmpro_review->membership_id    = $pmpro_level->id;
		$pmpro_review->cardtype         = $CardType;
		$pmpro_review->accountnumber    = $AccountNumber;
		$pmpro_review->expirationmonth  = $ExpirationMonth;
		$pmpro_review->expirationyear   = $ExpirationYear;
		$pmpro_review->gateway          = $pmpro_requirebilling ? $gateway : 'free';
		$pmpro_review->billing          = new stdClass();
		$pmpro_review->billing->name    = $bfirstname . " " . $blastname;
		$pmpro_review->billing->street  = trim( $baddress1 );
		$pmpro_review->billing->street2 = trim( $baddress2 );
		$pmpro_review->billing->city    = $bcity;
		$pmpro_review->billing->state   = $bstate;
		$pmpro_review->billing->country = $bcountry;
		$pmpro_review->billing->zip     = $bzipcode;
		$pmpro_review->billing->phone   = $bphone;

		// Calculate the order subtotal, tax, and total.
		$pmpro_review->subtotal         = pmpro_round_price( $pmpro_level->initial_payment );
		$pmpro_review->tax              = pmpro_round_price( $pmpro_review->getTax( true ) );
		$pmpro_review->total            = pmpro_round_price( $pmpro_review->subtotal + $pmpro_review->tax );

		// Finish setting up the order.
		$pmpro_review->setGateway();
		$pmpro_review->getMembershipLevelAtCheckout();	

		// Filter for order, since v1.8
		if ( $pmpro_requirebilling ) {
			$pmpro_review = apply_filters( 'pmpro_checkout_order', $pmpro_review );
		} else {
			$pmpro_review = apply_filters( 'pmpro_checkout_order_free', $pmpro_review );
		}
	}
} // End if ( $submit && $pmpro_msgt != 'pmpro_error' && empty( $pmpro_review ) )

// If there is still a valid checkout submission, process the order.
if ( $submit && $pmpro_msgt != "pmpro_error" && ! empty( $pmpro_review ) ) {
	do_action( 'pmpro_checkout_before_processing' );

	// Process the payment.
	$pmpro_processed = $pmpro_review->process();
	if ( ! empty( $pmpro_processed ) ) {
		$pmpro_msg       = __( "Payment accepted.", 'paid-memberships-pro' );
		$pmpro_msgt      = "pmpro_success";
		$pmpro_confirmed = true;
	} else {
		/**
		 * Allow running code when processing fails.
		 *
		 * @since 2.7
		 * @param MemberOrder $pmpro_review The order object used at checkout.
		 */
		do_action( 'pmpro_checkout_processing_failed', $pmpro_review );

		// Make sure we have an error message.
		$pmpro_msg = !empty( $pmpro_review->error ) ? $pmpro_review->error : null;
		if ( empty( $pmpro_msg ) ) {
			$pmpro_msg = __( "Unknown error generating account. Please contact us to set up your membership.", 'paid-memberships-pro' );
		}
		if ( ! empty( $pmpro_review->error_type ) ) {
			$pmpro_msgt = $pmpro_review->error_type;
		} else {
			$pmpro_msgt = "pmpro_error";
		}
	}
}

// Hook to check payment confirmation or replace it. If we get an array back, pull the values (pmpro_review) out
// All of this is deprecated and will be removed in a future version.
if ( empty( $pmpro_review ) ) {
	// make sure we have at least an empty order here to avoid a warning
	$pmpro_review = false;
}
$pmpro_confirmed_data = apply_filters_deprecated( 'pmpro_checkout_confirmed', array( $pmpro_confirmed, $pmpro_review ), '3.2' );
if ( is_array( $pmpro_confirmed_data ) ) {
	extract( $pmpro_confirmed_data );

	// Our old PPE integration had $morder dynamically set here. We changed that variable name to $pmpro_review. In case other integrations are using this filter, set $pmpro_review to $morder.
	if ( ! empty( $morder ) ) {
		$pmpro_review = $morder;
	}
} else {
	$pmpro_confirmed = $pmpro_confirmed_data;
}

// If the payment was successful, complete the checkout.
if ( ! empty( $pmpro_confirmed ) ) {
	if ( pmpro_complete_checkout( $pmpro_review ) ) {
		//redirect to confirmation
		$rurl = pmpro_url( "confirmation", "?pmpro_level=" . $pmpro_level->id );
		$rurl = apply_filters( "pmpro_confirmation_url", $rurl, $current_user->ID, $pmpro_level );
		wp_redirect( $rurl );
		exit;
	} else {

		// Something went wrong with the checkout.
		// If we get here, then the call to pmpro_changeMembershipLevel() returned false within pmpro_complete_checkout(). Let's try to cancel the payment.
		$test = (array) $pmpro_review;
		if ( ! empty( $test ) && $pmpro_review->cancel() ) {
			$pmpro_msg = __( "IMPORTANT: Something went wrong while processing your checkout. Your credit card authorized, but we cancelled the order immediately. You should not try to submit this form again. Please contact the site owner to fix this issue.", 'paid-memberships-pro' );
			$pmpro_review    = null;
		} else {
			$pmpro_msg = __( "IMPORTANT: Something went wrong while processing your checkout. Your credit card was charged, but we couldn't assign your membership. You should not submit this form again. Please contact the site owner to fix this issue.", 'paid-memberships-pro' );
		}
	}
} else {
	//show message if the payment gateway is not setup yet
	if ( $pmpro_requirebilling && ! get_option( "pmpro_gateway" ) ) {

		if ( pmpro_isAdmin() ) {
			$pmpro_msg = sprintf( __( 'You must <a href="%s">set up a Payment Gateway</a> before any payments will be processed.', 'paid-memberships-pro' ), admin_url( 'admin.php?page=pmpro-paymentsettings' ) );
		} else {
			$pmpro_msg = __( "A Payment Gateway must be set up before any payments will be processed.", 'paid-memberships-pro' );
		}
		$pmpro_msgt = "";
	}

	//default values from DB
	if ( ! empty( $current_user->ID ) ) {
		$bfirstname    = get_user_meta( $current_user->ID, "pmpro_bfirstname", true );
		$blastname     = get_user_meta( $current_user->ID, "pmpro_blastname", true );
		$baddress1     = get_user_meta( $current_user->ID, "pmpro_baddress1", true );
		$baddress2     = get_user_meta( $current_user->ID, "pmpro_baddress2", true );
		$bcity         = get_user_meta( $current_user->ID, "pmpro_bcity", true );
		$bstate        = get_user_meta( $current_user->ID, "pmpro_bstate", true );
		$bzipcode      = get_user_meta( $current_user->ID, "pmpro_bzipcode", true );
		$bcountry      = get_user_meta( $current_user->ID, "pmpro_bcountry", true );
		$bphone        = get_user_meta( $current_user->ID, "pmpro_bphone", true );
		$bemail        = get_user_meta( $current_user->ID, "pmpro_bemail", true );
		$bconfirmemail = $bemail;    //as of 1.7.5, just setting to bemail
	}
}

// Preventing conflicts with old checkout templates that depend on the $pmpro_level global being set.
pmpro_getAllLevels();

/**
 * Hook to run actions after the checkout preheader is loaded.
 * @since 2.1
 */
do_action( 'pmpro_after_checkout_preheader', $pmpro_review );
