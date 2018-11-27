jQuery(document).ready(function($) {
	jQuery('#AccountNumber').validateCreditCard(function(result) {
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

		if(result.card_type)
			jQuery('#CardType').val(cardtypenames[result.card_type.name]);
		else
			jQuery('#CardType').val('Unknown Card Type');
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

	//checking a discount code
	jQuery('#discount_code_button').click(function() {
		var code = jQuery('#discount_code').val();
		var level_id = jQuery('#level').val();

		if(code)
		{
			//hide any previous message
			jQuery('.pmpro_discount_code_msg').hide();

			//disable the apply button
			jQuery('#discount_code_button').attr('disabled', 'disabled');

			jQuery.ajax({
				url: '<?php echo admin_url('admin-ajax.php'); ?>',type:'GET',timeout:<?php echo apply_filters("pmpro_ajax_timeout", 5000, "applydiscountcode");?>,
				dataType: 'html',
				data: "action=applydiscountcode&code=" + code + "&level=" + level_id + "&msgfield=discount_code_message",
				error: function(xml){
					alert('Error applying discount code [1]');

					//enable apply button
					jQuery('#discount_code_button').removeAttr('disabled');
				},
				success: function(responseHTML){
					if (responseHTML == 'error')
					{
						alert('Error applying discount code [2]');
					}
					else
					{
						jQuery('#discount_code_message').html(responseHTML);
					}

					//enable invite button
					jQuery('#discount_code_button').removeAttr('disabled');
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
				action: 'checkout_page_action',
				// type:'GET',
				type: 'POST',
				timeout: 5000,
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
					//enable invite button
					$('#other_discount_code_button').removeAttr('disabled');
				}
			});
		}
	});
});