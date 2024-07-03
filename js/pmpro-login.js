jQuery(document).ready(function(){ 
	// Focus
	if ( jQuery( '#password_current' ).length ) {
		jQuery( '#password_current' ).focus();
	} else if ( jQuery( '#pass1' ).length ) {
		jQuery( '#pass1' ).focus();
	}
	
	function pmpro_check_password_strength( pass_field ) {
		var pass1 = jQuery( pass_field ).val();		
		var indicator = jQuery( '#pass-strength-result' );		
		
		var strength;		
		if ( pass1 != '' ) {
			// Call the disallowed list method corresponding to appropriate WP version.
			const disallowedList = ( 'function' == typeof wp.passwordStrength.userInputDisallowedList )
				? wp.passwordStrength.userInputDisallowedList()
				: wp.passwordStrength.userInputBlacklist();

			strength = wp.passwordStrength.meter( pass1, disallowedList, pass1 );

		} else {
			strength = -1;
		}

		var submitbutton;
		if ( jQuery( '#resetpass-button' ).length ) {
			submitbutton = jQuery( '#resetpass-button' );
		} else {
			submitbutton = jQuery( '#change-password input.pmpro_btn-submit' );
		}

		indicator.removeClass( 'empty bad good strong short' );

		switch ( strength ) {
			case -1:
				indicator.addClass( 'empty' ).html( '&nbsp;' );
				if ( pmpro.allow_weak_passwords === '' ) {
					submitbutton.prop( 'disabled', true );
				}
				break;
			case 2:
				indicator.addClass( 'bad' ).html( pwsL10n.bad );
				if ( pmpro.allow_weak_passwords === '' ) {
					submitbutton.prop( 'disabled', true );
				}
				break;
			case 3:
				indicator.addClass( 'good' ).html( pwsL10n.good );
				submitbutton.prop( 'disabled', false );
				break;
			case 4:
				indicator.addClass( 'strong' ).html( pwsL10n.strong );
				submitbutton.prop( 'disabled', false );
				break;
			case 5:
				indicator.addClass( 'short' ).html( pwsL10n.mismatch );
				submitbutton.prop( 'disabled', false );
				break;
			default:
				indicator.addClass( 'short' ).html( pwsL10n['short'] );
				if ( pmpro.allow_weak_passwords === '' ) {
					submitbutton.prop( 'disabled', true );
				}
		}
	}
	
	// Set up Strong Password script.
	if ( jQuery( '#pass1' ) ) {
		pmpro_check_password_strength( jQuery( '#pass1' ) );
		jQuery( '#pass1' ).bind( 'keyup paste', function() {
			pmpro_check_password_strength( jQuery( '#pass1' ) );
		});
	}

	// Password visibility toggle (all except the wp_login_form instance).
	(function() {
		const toggleElements = document.querySelectorAll('.pmpro_btn-password-toggle');

		toggleElements.forEach(toggle => {
			toggle.classList.remove('hide-if-no-js');
			toggle.addEventListener('click', togglePassword);
		});

		function togglePassword() {
			const status = this.getAttribute('data-toggle');
			const passwordInputs = document.querySelectorAll('.pmpro_form_input-password');
			const icon = this.getElementsByClassName('pmpro_icon')[0];
			const state = this.getElementsByClassName('pmpro_form_field-password-toggle-state')[0];

			if (parseInt(status, 10) === 0) {
				this.setAttribute('data-toggle', 1);
				passwordInputs.forEach(input => input.setAttribute('type', 'text'));
				icon.innerHTML = `
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--pmpro--color--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye-off">
						<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
						<line x1="1" y1="1" x2="23" y2="23"></line>
					</svg>`;
				state.textContent = pmpro.hide_password_text;
			} else {
				this.setAttribute('data-toggle', 0);
				passwordInputs.forEach(input => input.setAttribute('type', 'password'));
				icon.innerHTML = `
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--pmpro--color--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye">
						<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
						<circle cx="12" cy="12" r="3"></circle>
					</svg>`;
				state.textContent = pmpro.show_password_text;
			}
		}
	})();

});
