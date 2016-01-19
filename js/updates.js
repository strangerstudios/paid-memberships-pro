jQuery(document).ready(function() {
	//find status
	var $status = jQuery('#pmpro_updates_status');
	var $row = 1;
	var $count = 0;
	var $title = document.title;
	var $cycles = ['|','/','-','\\'];
	
	//start updates and update status
	if($status.length > 0)
	{
		$status.html($status.html() + '\n' + 'JavaScript Loaded. Starting updates.\n');

		function pmpro_updates()
		{
			jQuery.ajax({
				url: ajaxurl,type:'GET', timeout: 30000,
				dataType: 'html',
				data: 'action=pmpro_updates',
				error: function(xml){
					alert('Error with update. Try refreshing.');				
				},
				success: function(responseHTML){
					if (responseHTML == 'error')
					{
						alert('Error with update. Try refreshing.');
						document.title = $title;
					}
					else if(responseHTML == 'done')
					{
						$status.html($status.html() + '\nDone!');
						document.title = '! ' + $title;
						jQuery('#pmpro_updates_intro').html('All updates are complete.');
						location.reload(1);
					}
					else
					{
						$count++;
						$status.html($status.html() + responseHTML);
						document.title = $cycles[$count%4] + ' ' + $title;
						$update_timer = setTimeout(function() { pmpro_updates();}, 500);
					}

					//scroll the text area unless the mouse is over it
					if (jQuery('#status:hover').length != 0) {						
						$status.scrollTop($status[0].scrollHeight - $status.height());						
					}
				}
			});
		}

		var $update_timer = setTimeout(function() { pmpro_updates();}, 500);
	}
});