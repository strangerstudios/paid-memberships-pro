jQuery(document).ready(function($) {
	$('#dropdown-levels').change(function() {
		$.ajax({
			type: "POST",
			// url: selected_level_object.selected_ajaxurl,
			url: ajaxurl,
			dataType: 'html',
			// data: "pmpros_add_post=1&pmpros_series=" + seriesid + "&pmpros_post=" + $('#pmpros_post').val() + '&pmpros_delay=' + $('#pmpros_delay').val(),
			data: {
				// 'url' : "pmpros_add_post=1&pmpros_series=" + seriesid + "&pmpros_post=" + $('#pmpros_post').val() + '&pmpros_delay=' + $('#pmpros_delay').val(),
				'action' : 'selected_level_request',
				'filter' : $('#dropdown-levels').val(),
				'selected_levelurl' : selected_level_object.selected_ajaxurl,
				'selected_nonce' : selected_level_object.selected_nonce,
			},
			success:function(data) {
				$( '#return-selected' ).html(data);
				// if ( '' !== $('#dropdown-levels').val() ) {
				// 	$('#return-levels').html(' You selected Level ' + $('#dropdown-levels').val
				// 		());
				// } else {
				// 	$('#return-levels').html('You need to select a Level');
				// }
				$( '#test-input-ajax' ).val(selected_level_object.selected_nonce);
				// This outputs the result of the ajax request
				console.log(data);
			},
			error: function(jqXHR, textStatus, errorThrown){
				alert('There is an error');

				console.log(errorThrown);
			}
		});  
	});      
});