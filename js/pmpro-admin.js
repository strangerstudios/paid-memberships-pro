/**
 * Show a system prompt before redirecting to a URL.
 * Used for delete links/etc.
 * @param	text	The prompt, i.e. are you sure?
 * @param	url		The url to redirect to.
 */
function pmpro_askfirst(text, url) {
	var answer = window.confirm(text);

	if (answer) {
		window.location = url;
	}
}

/**
 * Deprecated in v2.1
 * In case add-ons/etc are expecting the non-prefixed version.
 */
if (typeof askfirst !== 'function') {
	function askfirst(text, url) {
		return pmpro_askfirst(text, url);
	}
}

/*
 * Toggle elements with a specific CSS class selector.
 * Used to hide/show sub settings when a main setting is enabled.
 * @since v2.1
 */
function pmpro_toggle_elements_by_selector(selector, checked) {
	if (checked === undefined) {
		jQuery(selector).toggle();
	} else if (checked) {
		jQuery(selector).show();
	} else {
		jQuery(selector).hide();
	}
}

/*
 * Find inputs with a custom attribute pmpro_toggle_trigger_for,
 * and bind change to toggle the specified elements.
 * @since v2.1
 */
jQuery(document).ready(function () {
	jQuery('input[pmpro_toggle_trigger_for]').on('change', function () {
		pmpro_toggle_elements_by_selector(jQuery(this).attr('pmpro_toggle_trigger_for'), jQuery(this).prop('checked'));
	});
});

// Admin Settings Code.
jQuery(document).ready(function () {
	pmpro_admin_prep_click_events();
});

// Function to prep click events for admin settings.
function pmpro_admin_prep_click_events() {
	/*
	 * Toggle content within the settings sections boxes.
	 * @since 2.9
	 */
	jQuery('button.pmpro_section-toggle-button').on('click', function (event) {
		event.preventDefault();

		let thebutton = jQuery(event.target).parents('.pmpro_section').find('button.pmpro_section-toggle-button');
		let buttonicon = thebutton.children('.dashicons');
		let section = thebutton.closest('.pmpro_section');
		let sectioninside = section.children('.pmpro_section_inside');

		//let visibility = container.data('visibility');
		//let activated = container.data('activated');
		if (buttonicon.hasClass('dashicons-arrow-down-alt2')) {
			// Section is not visible. Show it.
			jQuery(sectioninside).show();
			jQuery(buttonicon).removeClass('dashicons-arrow-down-alt2');
			jQuery(buttonicon).addClass('dashicons-arrow-up-alt2');
			jQuery(section).attr('data-visibility', 'shown');
			jQuery(thebutton).attr('aria-expanded', 'true');
		} else {
			// Section is visible. Hide it.
			jQuery(sectioninside).hide();
			jQuery(buttonicon).removeClass('dashicons-arrow-up-alt2');
			jQuery(buttonicon).addClass('dashicons-arrow-down-alt2');
			jQuery(section).attr('data-visibility', 'hidden');
			jQuery(thebutton).attr('aria-expanded', 'false');
		}
	});
}

// Hide the popup if clicked outside the popup.
jQuery(document).on('click', function (e) {
	// Check if the clicked element is the close button or outside the pmpro-popup-wrap
	if ( jQuery(e.target).closest('.pmpro-popup-wrap').length === 0 ) {
		jQuery('.pmpro-popup-overlay').hide();
	}
});

/** JQuery to hide the notifications. */
jQuery(document).ready(function () {
	jQuery(document).on('click', '.pmpro-notice-button.notice-dismiss', function () {
		var notification_id = jQuery(this).val();
		var nonce = jQuery(this).data('nonce');

		var postData = {
			action: 'pmpro_hide_notice',
			notification_id: notification_id,
			nonce: nonce
		}

		jQuery.ajax({
			type: "POST",
			data: postData,
			url: ajaxurl,
			success: function (response) {
				///console.log( notification_id );
				jQuery('#' + notification_id).hide();
			}
		})

	});
});

/* jQuery to hide the pause notification if the secondary button is pressed */
jQuery(document).ready(function () {
	jQuery('#hide_pause_notification_button').click(function () {
		jQuery('#hide_pause_notification .notice-dismiss').click();
	});
});

/*
 * Create Webhook button for Stripe on the payment settings page.
 */
jQuery(document).ready(function () {
	// Check that we are on payment settings page.
	if (!jQuery('#stripe_publishablekey').length || !jQuery('#stripe_secretkey').length || !jQuery('#pmpro_stripe_create_webhook').length) {
		return;
	}

	// Disable the webhook buttons if the API keys aren't complete yet.
	jQuery('#stripe_publishablekey,#stripe_secretkey').on('change keyup', function () {
		pmpro_stripe_check_api_keys();
	});
	pmpro_stripe_check_api_keys();

	// AJAX call to create webhook.
	jQuery('#pmpro_stripe_create_webhook').on('click', function (event) {
		event.preventDefault();

		var postData = {
			action: 'pmpro_stripe_create_webhook',
			secretkey: pmpro_stripe_get_secretkey(),
		}
		jQuery.ajax({
			type: "POST",
			data: postData,
			url: ajaxurl,
			success: function (response) {
				response = jQuery.parseJSON(response);
				///console.log( response );

				jQuery('#pmpro_stripe_webhook_notice').parent('div').removeClass('error')
				jQuery('#pmpro_stripe_webhook_notice').parent('div').removeClass('notice-success')

				if (response.notice) {
					jQuery('#pmpro_stripe_webhook_notice').parent('div').addClass(response.notice);
				}
				if (response.message) {
					jQuery('#pmpro_stripe_webhook_notice').html(response.message);
				}
				if (response.success) {
					jQuery('#pmpro_stripe_create_webhook').hide();
				}
			}
		})
	});

	// AJAX call to delete webhook.
	jQuery('#pmpro_stripe_delete_webhook').on('click', function (event) {
		event.preventDefault();

		var postData = {
			action: 'pmpro_stripe_delete_webhook',
			secretkey: pmpro_stripe_get_secretkey(),
		}

		jQuery.ajax({
			type: "POST",
			data: postData,
			url: ajaxurl,
			success: function (response) {
				response = jQuery.parseJSON(response);
				///console.log( response );

				jQuery('#pmpro_stripe_webhook_notice').parent('div').removeClass('error')
				jQuery('#pmpro_stripe_webhook_notice').parent('div').removeClass('notice-success')

				if (response.notice) {
					jQuery('#pmpro_stripe_webhook_notice').parent('div').addClass(response.notice);
				}
				if (response.message) {
					jQuery('#pmpro_stripe_webhook_notice').html(response.message);
				}
				if (response.success) {
					jQuery('#pmpro_stripe_create_webhook').show();
				}
			}
		})
	});

	// AJAX call to rebuild webhook.
	jQuery('#pmpro_stripe_rebuild_webhook').on('click', function (event) {
		event.preventDefault();

		var postData = {
			action: 'pmpro_stripe_rebuild_webhook',
			secretkey: pmpro_stripe_get_secretkey(),
		}

		jQuery.ajax({
			type: "POST",
			data: postData,
			url: ajaxurl,
			success: function (response) {
				response = jQuery.parseJSON(response);
				///console.log( response );

				jQuery('#pmpro_stripe_webhook_notice').parent('div').removeClass('error')
				jQuery('#pmpro_stripe_webhook_notice').parent('div').removeClass('notice-success')

				if (response.notice) {
					jQuery('#pmpro_stripe_webhook_notice').parent('div').addClass(response.notice);
				}
				if (response.message) {
					jQuery('#pmpro_stripe_webhook_notice').html(response.message);
				}
				if (response.success) {
					jQuery('#pmpro_stripe_create_webhook').hide();
				}
			}
		})
	});
});

// Disable the webhook buttons if the API keys aren't complete yet.
function pmpro_stripe_check_api_keys() {
	if ((jQuery('#stripe_publishablekey').val().length > 0 && jQuery('#stripe_secretkey').val().length > 0) || jQuery('#live_stripe_connect_secretkey').val().length > 0) {
		jQuery('#pmpro_stripe_create_webhook').removeClass('disabled');
		jQuery('#pmpro_stripe_create_webhook').addClass('button-secondary');
	} else {
		jQuery('#pmpro_stripe_create_webhook').removeClass('button-secondary');
		jQuery('#pmpro_stripe_create_webhook').addClass('disabled');
	}
}

// User Fields Code.
jQuery(document).ready(function () {
	pmpro_userfields_prep_click_events();
});

// Function to prep click events.
function pmpro_userfields_prep_click_events() {
	function update_userfield_type_fields() {
		// Hide all <tr> elements with the `field_type` class.
		jQuery('.field_type').hide();

		// Get the selected field type.
		var field_type = jQuery('.pmpro_admin-pmpro-userfields select[name=type]').val();

		// Show al the <tr> elements with `field_type_{field_type}` class.
		jQuery('.field_type_' + field_type).show();
	}
	update_userfield_type_fields();

	// Toggle required at checkout field settings based on group settings.
	jQuery('select[name="pmpro_userfields_group_checkout"]').unbind('change').on('change', function () {
		var groupContainer = jQuery(this).closest('.pmpro_userfield-inside');
		var fieldSettings = groupContainer.find('.pmpro_userfield-group-fields');
		var requiredFields = fieldSettings.find('#pmpro_userfield-field-setting_required');

		// Toggle visibility based on group setting.
		if (jQuery(this).val() === 'yes') {
			requiredFields.show();
		} else {
			requiredFields.hide();
		}
	}).trigger('change');

	// Toggle field settings based on type.
	jQuery('.pmpro_admin-pmpro-userfields select[name=type]').on('change', function (event) {
		update_userfield_type_fields();
	});

	// Suggest name after leaving label field.
	jQuery('.pmpro_admin-pmpro-userfields input[name=label]').on('focusout', function (event) {
		// Check if the "name" field is empty and a text field.
		var name = jQuery('.pmpro_admin-pmpro-userfields input[name=name]').val();
		var label = jQuery('.pmpro_admin-pmpro-userfields input[name=label]').val();
		if ( ! name && label ) {
			// Generate a name based on the label.
			name = label.toLowerCase().replace(/[^a-z0-9]/gi, '_').replace(/(^\_+|\_+$)/mg, '');
			jQuery('.pmpro_admin-pmpro-userfields input[name=name]').val(name);
		}
	});

	jQuery('.pmpro-level-restrictions-preview-button').on('click', function(event) {
		event.preventDefault();
		jQuery(this).hide();
		jQuery(this).next('.pmpro-level-restrictions-preview-list').show();
	});
}

function pmpro_stripe_get_secretkey() {
	// We can't do the webhook calls with the Connect keys anyway,
	// so we just look for the legacy key here.
	if (jQuery('#stripe_secretkey').val().length > 0) {
		return jQuery('#stripe_secretkey').val();
	} else {
		return '';
	}
}

// EMAIL TEMPLATES.
jQuery(document).ready(function ($) {

	/* Variables */
	$template = $('#edit').val();

	$("#pmpro_submit_template_data").click(function () {
		pmpro_save_template()
	});

	$("#pmpro_reset_template_data").click(function () {
		pmpro_reset_template();
	});

	$("#pmpro_email_template_disable").click(function (e) {
		pmpro_disable_template();
	});

	$("#send_test_email").click(function (e) {
		pmpro_save_template().done(setTimeout(function () { pmpro_send_test_email(); }, '1000'));
	});

	function pmpro_save_template() {

		$("#pmpro_submit_template_data").attr("disabled", true);
		$(".status").hide();
		// console.log(template);

		$data = {
			template: $template,
			subject: $("#pmpro_email_template_subject").val(),
			body: $("#pmpro_email_template_body").val(),
			action: 'pmpro_email_templates_save_template_data',
			security: $('input[name=security]').val()
		};
		$.post(ajaxurl, $data, function (response) {
			if (response != 0) {
				$(".status_message_wrapper").addClass('updated');
			}
			else {
				$(".status_message_wrapper").addClass("error");
			}
			$("#pmpro_submit_template_data").attr("disabled", false);
			$(".status_message").html(response);
			$(".status_message").show();
			$('html, body').animate({ scrollTop : 0 }, 'fast');
		});

		return $.Deferred().resolve();
	}

	function pmpro_reset_template() {

		var r = confirm('Are you sure? Your current template settings will be deleted permanently.');

		if (!r) return false;

		$data = {
			template: $template,
			action: 'pmpro_email_templates_reset_template_data',
			security: $('input[name=security]').val()
		};
		$.post(ajaxurl, $data, function (response) {
			var template_data = $.parseJSON(response);
			$('#pmpro_email_template_subject').val(template_data['subject']);
			$('#pmpro_email_template_body').val(template_data['body']);
			$(".status_message_wrapper").addClass('updated');
			$(".status_message").html('Template Reset');
			$(".status_message").show();
			$('html, body').animate({ scrollTop : 0 }, 'fast');
		});

		return true;
	}

	function pmpro_disable_template() {

		//update wp_options
		data = {
			template: $template,
			action: 'pmpro_email_templates_disable_template',
			disabled: $("#pmpro_email_template_disable").is(":checked"),
			security: $('input[name=security]').val()
		};

		$.post(ajaxurl, data, function (response) {

			response = JSON.parse(response);

			//failure
			if (response['result'] == false) {
				$(".status_message_wrapper").addClass("error");
				$(".status_message").show().text("There was an error updating your template settings.");
			}
			else {
				if (response['status'] == 'true') {
					$(".status_message_wrapper").addClass("updated");
					$(".status_message").show().text("Template Disabled");
					$(".pmpro_tag-success").addClass("pmpro_tag-alert");
					$(".pmpro_tag-success").removeClass("pmpro_tag-success");
					$(".pmpro_tag-alert").text("Disabled");
				}
				else {
					$(".status_message_wrapper").addClass("updated");
					$(".status_message").show().text("Template Enabled");
					$(".pmpro_tag-alert").addClass("pmpro_tag-success");
					$(".pmpro_tag-alert").removeClass("pmpro_tag-alert");
					$(".pmpro_tag-success").text("Enabled");
				}
			}

			$('html, body').animate({ scrollTop : 0 }, 'fast');

			disabled = response['status'];

			pmpro_toggle_form_disabled(disabled);
		});
	}

	function pmpro_send_test_email() {

		data = {
			template: $template,
			email: $("#test_email_address").val(),
			action: 'pmpro_email_templates_send_test',
			security: $('input[name=security]').val()
		};

		$.post(ajaxurl, data, function (success) {

			if (success) {
				$(".status_message_wrapper").addClass("updated").removeClass("error");
				$(".status_message").show().text("Test email sent successfully.");
			}
			else {
				$(".status_message_wrapper").addClass("error").removeClass("updated");
				$(".status_message").show().text("Test email failed.");
			}

		})
	}

	function pmpro_toggle_form_disabled(disabled) {
		if (disabled == 'true') {
			$("#pmpro_email_template_disable").prop('checked', true);
			$("#pmpro_email_template_body").attr('readonly', 'readonly').attr('disabled', 'disabled');
			$("#pmpro_email_template_subject").attr('readonly', 'readonly').attr('disabled', 'disabled');
		}
		else {
			$("#pmpro_email_template_disable").prop('checked', false);
			$("#pmpro_email_template_body").removeAttr('readonly', 'readonly').removeAttr('disabled', 'disabled');
			$("#pmpro_email_template_subject").removeAttr('readonly', 'readonly').removeAttr('disabled', 'disabled');
		}

	}

});

// Design Settings.
jQuery(document).ready(function () {
	// Preview color changes by updating the #pmpro_global_style_colors inline styles.
	jQuery('.pmpro_color_picker').on('change', function () {
		var baseColor = jQuery('#pmpro_base_color').val();
		var contrastColor = jQuery('#pmpro_contrast_color').val();
		var accentColor = jQuery('#pmpro_accent_color').val();

		jQuery('#pmpro_global_style_colors').html(':root { --pmpro--color--base: ' + baseColor + '; --pmpro--color--contrast: ' + contrastColor + '; --pmpro--color--accent: ' + accentColor + '; }');
	});
});

// Add Ons Page Code.
jQuery(document).ready(function () {
	// Hide the license banner.
	jQuery('.pmproPopupCloseButton, .pmproPopupCompleteButton').click(function (e) {
		e.preventDefault();
		jQuery('.pmpro-popup-overlay').hide();
	});

	// Hide the popup banner if "ESC" is pressed.
	jQuery(document).keyup(function (e) {
		if (e.key === 'Escape') {
			jQuery('.pmpro-popup-overlay').hide();
		}
	});

	jQuery('#pmpro-admin-add-ons-list .action-button .pmproAddOnActionButton').click(function (e) {
		e.preventDefault();

		var button = jQuery(this);

		// Make sure we only run once.
		if (button.hasClass('disabled')) {
			return;
		}
		button.addClass('disabled');

		// Pull the action that we are performing on this button.
		var action = button.siblings('input[name="pmproAddOnAdminAction"]').val();

		if ('license' === action) {
			// Get the add on name and the user's current license type and show banner.
			document.getElementById('addon-name').innerHTML = button.siblings('input[name="pmproAddOnAdminName"]').val();
			document.getElementById('addon-license').innerHTML = button.siblings('input[name="pmproAddOnAdminLicense"]').val();
			jQuery('.pmpro-popup-overlay').show();
			button.removeClass('disabled');
			return false;
		} else {
			// Remove checkmark if there.
			button.removeClass('checkmarked');

			// Update the button text.            
			if ('activate' === action) {
				button.html('Activating...');
			} else if ('install' === action) {
				button.html('Installing...');
			} else if ('update' === action) {
				button.html('Updating...');
			} else {
				// Invalid action.
				return;
			}

			// Run the action.
			var actionUrl = button.siblings('input[name="pmproAddOnAdminActionUrl"]').val();
			jQuery.ajax({
				url: actionUrl,
				type: 'GET',
				success: function (response) {
					// Create an element that we can use jQuery to parse.
					var responseElement = jQuery('<div></div>').html(response);

					// Check for errors.
					if ('activate' === action && responseElement.find('#message').hasClass('error')) {
						button.html('Could not activate.');
						return;
					} else if ('install' === action && 0 === responseElement.find('.button-primary').length) {
						button.html('Could not install.');
						return;
					} else if ('update' === action && -1 === responseElement.html().indexOf('<p>' + pmpro.plugin_updated_successfully_text)) {
						button.html('Could not update.');
						return;
					}

					// Add check mark.
					button.addClass('checkmarked');

					// Show success message.
					if ('activate' === action) {
						button.html('Activated');
					} else if ('install' === action) {
						button.html('Installed');
					} else if ('update' === action) {
						button.html('Updated');
					}

					// If user just installed, give them the option to activate.
					// TODO: Also give option to activate after update, but this is harder.
					if ('install' === action) {
						// Find the buttons that could be the activate button.
						var primaryButtons = responseElement.find('.button-primary');

						// Loop through the buttons to find the activate button.
						for (var i = 0; i < primaryButtons.length; i++) {
							// If there is a href element beginning with plugins.php?action=activate&plugin=[plugin_slug], then it is very likely the activate button.
							if ( primaryButtons[i].getAttribute('href') && primaryButtons[i].getAttribute('href').indexOf('plugins.php?action=activate&plugin=') > -1 ) {
								// Wait 1 second before showing the activate button.
								setTimeout(function () {
									button.siblings('input[name="pmproAddOnAdminAction"]').val('activate');
									button.siblings('input[name="pmproAddOnAdminActionUrl"]').val( primaryButtons[i].getAttribute('href') );
									button.html('Activate');
									button.removeClass('disabled');
								}, 1000);
								break;
							}
						}
					}
				},
				error: function (response) {
					if ('activate' === action) {
						button.html('Could Not Activate.');
					} else if ('install' === action) {
						button.html('Could Not Install.');
					} else if ('update' === action) {
						button.html('Could Not Update.');
					}
				}
			});

		}
	});
});

/**
 * Add/Edit Member Page
 */
window.addEventListener("DOMContentLoaded", () => {
	const tabs = document.querySelectorAll('#pmpro-edit-user-div [role="tab"]');
	const tabList = document.querySelector('#pmpro-edit-user-div [role="tablist"]');
	const inputs = document.querySelectorAll('#pmpro-edit-user-div input, #pmpro-edit-user-div textarea, #pmpro-edit-user-div select');

	if ( tabs && tabList ) {
		// Track whether an input has been changed.
		let inputChanged = false;
		inputs.forEach((input) => {
			input.addEventListener('change', function(e) {
				inputChanged = true;
			});
		});

		// Add a click event handler to each tab
		tabs.forEach((tab) => {
			tab.addEventListener("click", function (e) {
				if ( pmpro_changeTabs(e, inputChanged ) ) {
					// If we changed tabs, reset the inputChanged flag.
					inputChanged = false;

					// Hide the PMPro message.
					const pmproMessage = document.querySelector('#pmpro_message');
					if ( pmproMessage ) {
						pmproMessage.style.display = 'none';
					}
				}
			});
		});

		// Enable arrow navigation between tabs in the tab list
		let tabFocus = 0;
		tabList.addEventListener("keydown", (e) => {
		// Move Down
		if (e.key === "ArrowDown" || e.key === "ArrowUp") {
			tabs[tabFocus].setAttribute("tabindex", -1);
			if (e.key === "ArrowDown") {
			tabFocus++;
			// If we're at the end, go to the start
			if (tabFocus >= tabs.length) {
				tabFocus = 0;
			}
			// Move Up
			} else if (e.key === "ArrowUp") {
			tabFocus--;
			// If we're at the start, move to the end
			if (tabFocus < 0) {
				tabFocus = tabs.length - 1;
			}
			}

			tabs[tabFocus].setAttribute("tabindex", 0);
			tabs[tabFocus].focus();
		}
		});

		// Enable the button to show more tabs.
		document.addEventListener('click', function(e) {
			const moreTabsToggle = e.target.closest('[role="showmore"]');
			if (moreTabsToggle) {
				e.preventDefault();
				const parent = moreTabsToggle.parentNode;
				const grandparent = parent.parentNode;
				grandparent.querySelectorAll('[role="tab"]').forEach((t) => t.style.display = 'block');
				parent.style.display = 'none';
			}
		});

		// If the visible panel's corresponding tab is hidden, show all tabs.
		const visiblePanel = document.querySelector('#pmpro-edit-user-div [role="tabpanel"]:not([hidden])');
		if ( visiblePanel ) {
			const visibleTab = document.querySelector(`[aria-controls="${visiblePanel.id}"]`);
			if ( visibleTab.style.display === 'none' ) {
				const moreTabsToggle = document.querySelector('[role="showmore"]');
				moreTabsToggle.click();
			}
		}

	}

});

function pmpro_changeTabs( e, inputChanged ) {
	e.preventDefault();

	if ( inputChanged ) {
		const answer = window.confirm('You have unsaved changes. Are you sure you want to switch tabs?');
		if ( ! answer ) {
			return false;
		}
	}

	const target = e.target;
	const parent = target.parentNode;
	const grandparent = parent.parentNode;

	// Remove all current selected tabs
	parent
	.querySelectorAll('[aria-selected="true"]')
	.forEach((t) => t.setAttribute("aria-selected", false));

	// Set this tab as selected
	target.setAttribute("aria-selected", true);

	// Hide all tab panels
	grandparent
	.querySelectorAll('[role="tabpanel"]')
	.forEach((p) => p.setAttribute("hidden", true));

	// Show the selected panel
	grandparent.parentNode
	.querySelector(`#${target.getAttribute("aria-controls")}`)
	.removeAttribute("hidden");

	// Update the URL to include the panel URL in the pmpro_member_edit_panel attribute.
	const fullPanelName = target.getAttribute('aria-controls');
	// Need to convert pmpro-member-edit-xyz-panel to xyz.
	const panelSlug = fullPanelName.replace(/^pmpro-member-edit-/, '').replace(/-panel$/, '');
	const url = new URL(window.location.href);
	url.searchParams.set('pmpro_member_edit_panel', panelSlug);
	window.history.pushState({}, '', url);

	return true;
}

/**
 * Edit Order Page
 */
jQuery(document).ready(function () {
	jQuery('.pmpro_admin-pmpro-orders select#membership_id').select2();
});