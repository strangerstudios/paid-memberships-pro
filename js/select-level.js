jQuery(document).ready(function($) {
	$('#filter-memberslisttable').change(function() {
		$.ajax({
			type: "POST",
			url: ajaxurl,
			data: {
				'action' : 'select_level_request',
				'filter' : $('#filter-memberslisttable').val(),
				'returnpage' : select_level_object.select_page,
				'select_level_url' : select_level_object.select_level_url,
				'select_level_nonce' : select_level_object.select_level_nonce,
			},
			success:function(data) {
				obj = JSON.parse(data);
				var returnURL = obj.select_level_url + obj.returnpage + '&s=' + obj.filter;
				var returnLink = '<a href="' + returnURL + '">' + returnURL + '</a>'; 
				$( '#redraw-table' ).attr('href',returnURL);
				$( '#level-filter-request1' ).html(returnLink);
				$( '#level-filter-request2' ).html('returnURL ' + returnLink + data);
			},
			error: function(jqXHR, textStatus, errorThrown){
				console.log(errorThrown);
			}
		});  
	});
});