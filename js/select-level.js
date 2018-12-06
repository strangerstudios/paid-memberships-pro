jQuery(document).ready(function($) {
	$('#levels-dropdown').change(function() {
		$.ajax({
			type: "POST",
			url: ajaxurl,
			dataType: 'html',
			data: {
				'action' : 'select_level_request',
				'filter' : $('#levels-dropdown').val(),
				'effpage' : select_level_object.select_page,
				'select_level_url' : select_level_object.select_level_ajaxurl,
				'select_level_nonce' : select_level_object.select_level_nonce,
			},
			success:function(data) {
				user_table = data.substring(data.indexOf('<table'), data.indexOf('</table>') + 8);
				$( '#list-table-replace table' ).html(user_table);
				console.log(data);
			},
			error: function(jqXHR, textStatus, errorThrown){
				console.log(errorThrown);
			}
		});  
	});      
});
