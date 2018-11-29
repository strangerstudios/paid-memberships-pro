jQuery(document).ready(function($) {
	$('#other_discount_code_button').hide();
	$('.pmpro_checkout-field.pmpro_payment-discount-code').hide();
	$('#other_discount_code').bind("change keyup input", function() {
		$('.pmpro_checkout-field.pmpro_payment-discount-code').show();
		$('#other_discount_code_button').show();
		$('#discount_code_button').show();
	});

	// alert('Currently the AJAX timeout is set to ' + checkout_page_object.applydiscountcode);
	$('#AccountNumber').validateCreditCard(function(result) {
		var cardtypenames = {
			"amex"                      : "American Express",
			"diners_club_carte_blanche" : "Diners Club Carte Blanche",
			"diners_club_international" : "Diners Club International",
			"discover"                  : "Discover",
			"jcb"                       : "JCB",
			"laser"                     : "Laser",
			"maestro"                   : "Maestro",
			"mastercard"                : "Mastercard",
			"visa"                      : "Visa",
			"visa_electron"             : "Visa Electron"
		};

		if(result.card_type) {
			$('#CardType').val(cardtypenames[result.card_type.name]);
		}else{
			$('#CardType').val('Unknown Card Type');
		}
	});
	//update discount code link to show field at top of form
	$('#other_discount_code_a').attr('href', 'javascript:void(0);');
	$('#other_discount_code_a').click(function() {
		$('#other_discount_code_tr').show();
		$('#other_discount_code_p').hide();
		$('#other_discount_code').focus();
	});

	//update real discount code field as the other discount code field is updated
	$('#other_discount_code').keyup(function() {
		$('#discount_code').val($('#other_discount_code').val());
	});
	$('#other_discount_code').blur(function() {
		$('#discount_code').val($('#other_discount_code').val());
	});

	//update other discount code field as the real discount code field is updated
	$('#discount_code').keyup(function() {
		$('#other_discount_code').val($('#discount_code').val());
	});
	$('#discount_code').blur(function() {
		$('#other_discount_code').val($('#discount_code').val());
	});
	$("#credit-card-whats").click(function(e){
		e.preventDefault();
		$("#cvv-window").show();
	});
	$("#small-x-close").click(function(){
		$("#cvv-window").hide();
	});
	//checking a discount code
	$('#discount_code_button').click(function() {
		var code = $('#discount_code').val();
		var level_id = $('#level').val();

		if(code){
			//hide any previous message
			$('.pmpro_discount_code_msg').hide();

			//disable the apply button
			// $('#discount_code_button').attr('disabled', 'disabled');

			$.ajax({
				url: checkout_page_object.checkout_page_ajaxurl,
				type:'GET',
				timeout: checkout_page_object.applydiscountcode,
				dataType: 'html',
				data: "action=applydiscountcode&code=" + code + "&level=" + level_id + "&msgfield=discount_code_message",
				error: function(xml){
					alert('Error applying discount code [1]');
					$('return-discount-info').html(xml);

					//enable apply button
					$('#discount_code_button').removeAttr('disabled');
				},
				success: function(responseHTML){
					if (responseHTML == 'error'){
						alert('Error applying discount code [2]');
					}else{
						$('#discount_code_message').html(responseHTML);
					}

					//enable invite button
					$('#discount_code_button').removeAttr('disabled');
				}
			});
		}
	});


	//applying a discount code
	$('#other_discount_code_button').click(function() {
		var code = $('#other_discount_code').val();
		var level_id = $('#level').val();

		if(code){
			//hide any previous message
			$('.pmpro_discount_code_msg').hide();

			//disable the apply button
			$('#other_discount_code_button').attr('disabled', 'disabled');

			$.ajax({
				url: checkout_page_object.checkout_page_ajaxurl,
				// action: 'checkout_page_action',
				type:'GET',
				// type: 'POST',
				timeout: checkout_page_object.applydiscountcode,
				dataType: 'html',
				data: "action=applydiscountcode&code=" + code + "&level=" + level_id + "&msgfield=pmpro_message",
				error: function(xml){
					alert('Error applying discount code [1]');

					//enable apply button
					$('#other_discount_code_button').removeAttr('disabled');
				},
				success: function(responseHTML){
					if (responseHTML == 'error'){
						alert('Error applying discount code [2]');
					} else {
						$('#pmpro_message').html(responseHTML);
					}
					$('return-discount-info').html(responseHTML);
					//enable invite button
					$('#other_discount_code_button').removeAttr('disabled');
				}
			});
		}
	});
	// Find ALL <form> tags on your page
	$('form').submit(function(){
		// On submit disable its submit button
		$('input[type=submit]', this).attr('disabled', 'disabled');
		$('input[type=image]', this).attr('disabled', 'disabled');
		$('#pmpro_processing_message').css('visibility', 'visible');
	});

	//iOS Safari fix (see: http://stackoverflow.com/questions/20210093/stop-safari-on-ios7-prompting-to-save-card-data)
	var userAgent = window.navigator.userAgent;
	if(userAgent.match(/iPad/i) || userAgent.match(/iPhone/i)) {
		$('input[type=submit]').click(function() {
			try{
				$("input[type=password]").attr("type", "hidden");
			} catch(ex){
				try {
					$("input[type=password]").prop("type", "hidden");
				} catch(ex) {}
			}
		});
	}

	//add required to required fields
	$('.pmpro_required').after('<span class="pmpro_asterisk"> <abbr title="Required Field">*</abbr></span>');

	//unhighlight error fields when the user edits them
	$('.pmpro_error').bind("change keyup input", function() {
		$(this).removeClass('pmpro_error');
	});

	//click apply button on enter in discount code box
	$('#discount_code').keydown(function(e){
	    if(e.keyCode == 13){
		   e.preventDefault();
		   $('#discount_code_button').click();
	    }
	});

	//hide apply button if a discount code was passed in
	// if ( true == discount_code_present ) {
	$('#discount_code_button').hide();
		$('#discount_code').bind('change keyup', function() {
			$('#discount_code_button').show();
		});
	// }
	//click apply button on enter in *other* discount code box
	$('#other_discount_code').keydown(function (e){
	    if(e.keyCode == 13){
		   e.preventDefault();
		   $('#other_discount_code_button').click();
	    }
	});
	//add javascriptok hidden field to checkout
	$("input[name=submit-checkout]").after('<input type="hidden" name="javascriptok" value="1" />');
});