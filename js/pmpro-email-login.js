jQuery(document).ready(function($) {
	// Move the "Login with Password Instead" link to below the submit button.
	if ( jQuery('#pmpro-email-login').length) {
		jQuery('#pmpro-email-login').insertAfter(jQuery('#wp-submit').parent());
	}

	jQuery('#pmpro-email-login-button').on('click', function(e) {
		e.preventDefault();

		// Make sure that the username is required if it's not already required.
		let user = document.getElementById('user_login');
		if (user) {
			user.required = true;
		}
		
		// Disable password validation completely, we only need email/username.
        let pass = document.getElementById('user_pass');
        if ( pass ) {
			pass.required = false;
        }

		var frm = document.getElementById('loginform');
		if (!frm) {
			return; // No form found, nothing to do.
		}

		// Set the custom login URL action.
		frm.action = pmpro_email_login_js.login_url;

		if (typeof frm.requestSubmit === 'function') {
			frm.requestSubmit();
			return;
		}

		// Fallback for older browsers to submit the form.
		frm.submit();
	});

});

