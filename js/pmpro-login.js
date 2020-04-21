jQuery(document).ready(function(){ 
	// Focus
	if ( jQuery( '#password_current' ) ) {
		jQuery( '#password_current' ).focus();
	}
	
	function pmpro_check_password_strength( pass_field ) {
		var pass1 = jQuery( pass_field ).val();		
		var indicator = jQuery( '#pass-strength-result' );
		var strength;
		if ( pass1 != '' ) {
			strength = wp.passwordStrength.meter( pass1, wp.passwordStrength.userInputBlacklist(), pass1 );
		} else {
			strength = -1;
		}

		indicator.removeClass( 'empty bad good strong short' );

		switch ( strength ) {
			case -1:
				indicator.addClass( 'empty' ).html( '&nbsp;' );
				break;
			case 2:
				indicator.addClass( 'bad' ).html( pwsL10n.bad );
				break;
			case 3:
				indicator.addClass( 'good' ).html( pwsL10n.good );
				break;
			case 4:
				indicator.addClass( 'strong' ).html( pwsL10n.strong );
				break;
			case 5:
				indicator.addClass( 'short' ).html( pwsL10n.mismatch );
				break;
			default:
				indicator.addClass( 'short' ).html( pwsL10n['short'] );
		}
	}
	
	// Set up Strong Password script.
	if ( jQuery( '#password_new1' ) ) {
		pmpro_check_password_strength( jQuery( '#password_new' ) );
		jQuery( '#password_new1' ).bind( 'keyup paste', function() {
			pmpro_check_password_strength( jQuery( '#password_new1' ) );
		});
	}
});