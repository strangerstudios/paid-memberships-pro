jQuery( document ).ready( function( $ ) {
	// Unbind the default click handler
	$('.pmpro_btn-print').removeAttr('onclick');

	// Bind a new click handler
	$('.pmpro_btn-print').click(function() {
		// Find the closest parent div with class "pmpro" and the previous sibling div with class "pmpro"
		var membershipConfirmationText = $(this).closest('.pmpro').prev('.pmpro');

		// Toggle the "pmpro_hide_print" class on the first section inside the previousPmproDiv
		membershipConfirmationText.find('section').first().toggleClass('pmpro_hide_print');

		// Print the page
		window.print();

		// Toggle the "pmpro_hide_print" class back to show the previous elements
		membershipConfirmationText.find('section').first().toggleClass('pmpro_hide_print');

		return false;
	});

	// Function to poll the server to see if the order has been completed.
	// If so, refresh so the user can see the user can see their completed checkout.
	function startPolling() {
		var pollInterval = setInterval(function() {
			jQuery.noConflict().ajax({
				url: pmpro.restUrl + 'pmpro/v1/order',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', pmpro.nonce);
				},
				dataType: 'json',
				data: {
					'code': pmpro.code
				},
				success: function(response) {
					if (response.status == 'success') {
						// Order is complete.
						clearInterval(pollInterval);
						window.location.reload();
					}
				}
			});
		}, 5000); // Poll every 5 seconds.
	}

	// Initial check to see if the order is pending or in a token state so we can trigger the polling.
	jQuery.noConflict().ajax({
		url: pmpro.restUrl + 'pmpro/v1/order',
		beforeSend: function ( xhr ) {
			xhr.setRequestHeader( 'X-WP-Nonce', pmpro.nonce );
		},
		dataType: 'json',
		data: {
			'code': pmpro.code
		},
		url: pmpro.restUrl + 'pmpro/v1/order',
		beforeSend: function(xhr) {
			xhr.setRequestHeader('X-WP-Nonce', pmpro.nonce);
		},
		dataType: 'json',
		data: {
			'code': pmpro.code
		},
		success: function(response) {
			if (response.status == 'pending' || response.status == 'token') {
				// Order is not complete, start polling.
				startPolling();
			}
		}
	});

});