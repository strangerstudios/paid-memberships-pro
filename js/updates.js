jQuery(document).ready(function() {
	//find status
	var $status = jQuery('#pmpro_updates_status');
	var $row = 1;
	var $count = 0;
	var $title = document.title;
	var $cycles = ['|','/','-','\\'];
	var $timeout = 30000;
	
	//start updates and update status
	if($status && $status.length > 0)
	{
		$status.html($status.html() + '\n' + 'JavaScript Loaded. Starting updates.\n');

		function pmpro_updates()
		{
			jQuery.ajax({
				url: ajaxurl,type:'GET', timeout: $timeout,
				dataType: 'html',
				data: 'action=pmpro_updates',
				error: function( xml, status, error ) {
					if ( status == 'timeout' ) {
						if ( window.confirm( 'Timeout error. Would you like to try again with a longer timeout?' ) ) {
							$timeout = $timeout * 2;
							pmpro_updates();
						}
					} else if ( status == 'error' && error ) {
						// Likely the case with a PHP error.
						alert( error + '. Try refreshing. If this error occurs again, check your PHP error logs or seek help on the PMPro member forums.');
					} else if ( status == 'error' ) {
						// Likely the case if the user tries to nagivate away from the update page.
						alert( 'This update could not complete. Try refreshing. If this error occurs again, seek help on the PMPro member forums.');
					}
				},
				success: function(responseHTML){
					if (responseHTML.indexOf('[error]') > -1)
					{
						alert('Error while running update: ' + responseHTML + ' Try refreshing. If this error occurs again, seek help on the PMPro member forums.');
						document.title = $title;
					}
					else if(responseHTML.indexOf('[done]') > -1)
					{
						$status.html($status.html() + '\nDone!');
						document.title = '! ' + $title;
						jQuery('#pmpro_updates_intro').html('All updates are complete.');
						location.reload(1);
					}
					else
					{
						$count++;
						// Regex to find any string between square brackets.
						re = /\[.*\]/;

						// Get all strings between square brackets.
						progress = re.exec(responseHTML);

						// If there is a string between square brackets, update the progress bar.
						if ( progress && progress.length > 0 ) {
							// Assume progress is something like [1/10].
							jQuery('#pmpro_updates_progress').html(progress[0] + ' ' + parseInt(eval(progress[0].replace(/\[|\]/ig, ''))*100) + '%');
						}

						// Update the status area.
						$status.html($status.html() + responseHTML.replace(re, ''));

						// Title bar animation.
						document.title = $cycles[$count%4] + ' ' + $title;
						$update_timer = setTimeout(function() { pmpro_updates();}, 200);
					}

					//scroll the text area unless the mouse is over it
					if (jQuery('#status:hover').length != 0) {						
						$status.scrollTop($status[0].scrollHeight - $status.height());						
					}
				}
			});
		}

		var $update_timer = setTimeout(function() { pmpro_updates();}, 200);
	}
});