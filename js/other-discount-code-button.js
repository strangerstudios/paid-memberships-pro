jQuedry(document).ready(function($){
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

	//applying a discount code
	$('#other_discount_code_button').click(function() {
		var code = $('#other_discount_code').val();
		var level_id = $('#level').val();

		if(code)
		{
			//hide any previous message
			$('.pmpro_discount_code_msg').hide();

			//disable the apply button
			$('#other_discount_code_button').attr('disabled', 'disabled');

			$.ajax({
				url: '<?php echo admin_url('admin-ajax.php'); ?>',type:'GET',timeout:<?php echo apply_filters("pmpro_ajax_timeout", 5000, "applydiscountcode");?>,
				dataType: 'html',
				data: "action=applydiscountcode&code=" + code + "&level=" + level_id + "&msgfield=pmpro_message",
				error: function(xml){
					alert('Error applying discount code [1]');

					//enable apply button
					$('#other_discount_code_button').removeAttr('disabled');
				},
				success: function(responseHTML){
					if (responseHTML == 'error')
					{
						alert('Error applying discount code [2]');
					}
					else
					{
						$('#pmpro_message').html(responseHTML);
					}

					//enable invite button
					$('#other_discount_code_button').removeAttr('disabled');
				}
			});
		}
	});
});
