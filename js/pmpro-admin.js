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

		var postData = {
			action: 'pmpro_hide_notice',
			notification_id: notification_id
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
	// Whenever we make a change, warn the user if they try to nagivate away.
	function pmpro_userfields_made_a_change() {
		window.onbeforeunload = function () {
			return true;
		};
		jQuery('#pmpro_userfields_savesettings').prop("disabled", false);
	}

	// Add group button.
	jQuery('#pmpro_userfields_add_group').unbind('click').on('click', function (event) {
		jQuery('#pmpro_userfields_add_group').parent('p').before(pmpro.user_fields_blank_group);
		pmpro_userfields_prep_click_events();
		jQuery('#pmpro_userfields_add_group').parent('p').prev().find('input').focus().select();
		pmpro_userfields_made_a_change();
	});

	// Delete group button.
	jQuery('.pmpro_userfield-group-actions button[name=pmpro_userfields_delete_group]').unbind('click').on('click', function (event) {
		var thegroup = jQuery(this).closest('.pmpro_userfield-group');
		var thename = thegroup.find('input[name=pmpro_userfields_group_name]').val();
		var answer;
		if (thename.length > 0) {
			answer = window.confirm('Delete the "' + thename + '" group?');
		} else {
			answer = window.confirm('Delete this group?');
		}
		if (answer) {
			thegroup.remove();
			pmpro_userfields_made_a_change();
		}
	});

	// Add field button.
	jQuery('button[name="pmpro_userfields_add_field"]').unbind('click').on('click', function (event) {
		var thefields = jQuery(event.target).closest('div.pmpro_userfield-group-actions').siblings('div.pmpro_userfield-group-fields');
		thefields.append(pmpro.user_fields_blank_field);
		pmpro_userfields_prep_click_events();
		thefields.children().last().find('.edit-field').click();
		thefields.children().last().find('input[name="pmpro_userfields_field_label"]').focus().select();
		pmpro_userfields_made_a_change();
	});

	// Delete field button.
	jQuery('.pmpro_userfield-field-options a.delete-field, .pmpro_userfield-field-actions .is-destructive').unbind('click').on('click', function (event) {
		var thefield = jQuery(this).closest('.pmpro_userfield-group-field');
		var thelabel = thefield.find('input[name=pmpro_userfields_field_label]').val();
		var answer;
		if (thelabel.length > 0) {
			answer = window.confirm('Delete the "' + thelabel + '" field?');
		} else {
			answer = window.confirm('Delete this unlabeled field?');
		}
		if (answer) {
			thefield.remove();
			pmpro_userfields_made_a_change();
		}
	});

	// Toggle groups.    
	jQuery('button.pmpro_userfield-group-buttons-button-toggle-group, div.pmpro_userfield-group-header h3').unbind('click').on('click', function (event) {
		event.preventDefault();

		// Ignore if the text field was clicked.        
		if (jQuery(event.target).prop('nodeName') === 'INPUT') {
			return;
		}

		// Find the toggle button and open or close.
		let thebutton = jQuery(event.target).parents('.pmpro_userfield-group').find('button.pmpro_userfield-group-buttons-button-toggle-group');
		let buttonicon = thebutton.children('.dashicons');
		let groupheader = thebutton.closest('.pmpro_userfield-group-header');
		let groupinside = groupheader.siblings('.pmpro_userfield-inside');

		if (buttonicon.hasClass('dashicons-arrow-up')) {
			// closing
			buttonicon.removeClass('dashicons-arrow-up');
			buttonicon.addClass('dashicons-arrow-down');
			groupinside.slideUp();
		} else {
			// opening
			buttonicon.removeClass('dashicons-arrow-down');
			buttonicon.addClass('dashicons-arrow-up');
			groupinside.slideDown();
		}
	});

	// Move group up.
	jQuery('.pmpro_userfield-group-buttons-button-move-up').unbind('click').on('click', function (event) {
		var thegroup = jQuery(this).closest('.pmpro_userfield-group');
		var thegroupprev = thegroup.prev('.pmpro_userfield-group');
		if (thegroupprev.length > 0) {
			thegroup.insertBefore(thegroupprev);
			pmpro_userfields_made_a_change();
		}
	});

	// Move group down.
	jQuery('.pmpro_userfield-group-buttons-button-move-down').unbind('click').on('click', function (event) {
		var thegroup = jQuery(this).closest('.pmpro_userfield-group');
		var thegroupnext = thegroup.next('.pmpro_userfield-group');
		if (thegroupnext.length > 0) {
			thegroup.insertAfter(thegroupnext);
			pmpro_userfields_made_a_change();
		}
	});

	// Open field.
	jQuery('a.edit-field').unbind('click').on('click', function (event) {
		var fieldcontainer = jQuery(this).parents('.pmpro_userfield-group-field');
		var fieldsettings = fieldcontainer.children('.pmpro_userfield-field-settings');

		fieldcontainer.removeClass('pmpro_userfield-group-field-collapse');
		fieldcontainer.addClass('pmpro_userfield-group-field-expand');
		fieldsettings.find('select[name=pmpro_userfields_field_type]').change();
		fieldsettings.show();
	});

	// Close field.
	jQuery('button.pmpro_userfields_close_field').unbind('click').on('click', function (event) {
		event.preventDefault();
		var fieldcontainer = jQuery(this).parents('.pmpro_userfield-group-field');
		var fieldsettings = fieldcontainer.children('.pmpro_userfield-field-settings');
		var fieldheading = fieldsettings.prev();
		// Update label, name, and type.
		fieldheading.find('span.pmpro_userfield-label').html(fieldsettings.find('input[name=pmpro_userfields_field_label]').val().replace(/(<([^>]+)>)/gi, ''));
		fieldheading.find('li.pmpro_userfield-group-column-name').html(fieldsettings.find('input[name=pmpro_userfields_field_name]').val());
		fieldheading.find('li.pmpro_userfield-group-column-type').html(fieldsettings.find('select[name=pmpro_userfields_field_type]').val());

		// Toggle
		fieldcontainer.removeClass('pmpro_userfield-group-field-expand');
		fieldcontainer.addClass('pmpro_userfield-group-field-collapse');
		fieldsettings.hide();
	});

	// Move field up.
	jQuery('.pmpro_userfield-field-buttons-button-move-up').unbind('click').on('click', function (event) {
		var thefield = jQuery(this).closest('.pmpro_userfield-group-field');
		var thefieldprev = thefield.prev('.pmpro_userfield-group-field');
		if (thefieldprev.length > 0) {
			thefield.insertBefore(thefieldprev);
			pmpro_userfields_made_a_change();
		}
	});

	// Move field down.
	jQuery('.pmpro_userfield-field-buttons-button-move-down').unbind('click').on('click', function (event) {
		var thefield = jQuery(this).closest('.pmpro_userfield-group-field');
		var thefieldnext = thefield.next('.pmpro_userfield-group-field');
		if (thefieldnext.length > 0) {
			thefield.insertAfter(thefieldnext);
			pmpro_userfields_made_a_change();
		}
	});

	// Duplicate field.
	jQuery('a.duplicate-field').unbind('click').on('click', function (event) {
		var thefield = jQuery(this).closest('.pmpro_userfield-group-field');
		thefield.clone(true).insertAfter(thefield); // clone( true ) to clone event handlers.
		pmpro_userfields_made_a_change();
	});

	// Toggle field settings based on type.
	jQuery('select[name=pmpro_userfields_field_type]').on('change', function (event) {
		var fieldcontainer = jQuery(this).parents('.pmpro_userfield-group-field');
		var fieldsettings = fieldcontainer.children('.pmpro_userfield-field-settings');
		var fieldtype = jQuery(this).val();
		var fieldoptions = fieldsettings.find('textarea[name=pmpro_userfields_field_options]').parents('.pmpro_userfield-field-setting');

		var optiontypes = ['radio', 'select', 'select2', 'multiselect']; // eventually checkboxgroup

		if (jQuery.inArray(fieldtype, optiontypes) > -1) {
			fieldoptions.show();
		} else {
			fieldoptions.hide();
		}
	});

	// Suggest name after leaving label field.
	jQuery('input[name=pmpro_userfields_field_label]').on('focusout', function (event) {
		var fieldcontainer = jQuery(this).parents('.pmpro_userfield-group-field');
		var fieldsettings = fieldcontainer.children('.pmpro_userfield-field-settings');
		var fieldname = fieldsettings.find('input[name=pmpro_userfields_field_name]');
		if (!fieldname.val()) {
			fieldname.val(jQuery(this).val().toLowerCase().replace(/[^a-z0-9]/gi, '_').replace(/(^\_+|\_+$)/mg, ''));
		}
	});

	// If we change a field, mark it as changed.
	jQuery('.pmpro_userfield-group input, .pmpro_userfield-group textarea, .pmpro_userfield-group select').on('change', function (event) {
		pmpro_userfields_made_a_change();
	});

	// Save User Field Settings
	jQuery('#pmpro_userfields_savesettings').unbind('click').on('click', function (event) {
		///event.preventDefault();
		// We have saved, so we no longer need to warn user if they try to navigate away.
		window.onbeforeunload = null;

		let field_groups = [];
		let group_names = [];
		let default_group_name = 'More Information';

		jQuery('.pmpro_userfield-group').each(function (index, value) {
			let group_name = jQuery(this).find('input[name=pmpro_userfields_group_name]').val();

			// Make sure name is not blank.
			if (group_name.length === 0) {
				group_name = default_group_name;
			}
			// Make sure name is unique.
			let count = 1;
			while (group_names.includes(group_name)) {
				count++;
				group_name = group_name.replace(/\(0-9*\)/, '');
				group_name = group_name + ' (' + String(count) + ')';
			}
			group_names.push(group_name);

			let group_checkout = jQuery(this).find('select[name=pmpro_userfields_group_checkout]').val();
			let group_profile = jQuery(this).find('select[name=pmpro_userfields_group_profile]').val();
			let group_description = jQuery(this).find('textarea[name=pmpro_userfields_group_description]').val();

			// Get level ids.            
			let group_levels = [];
			jQuery(this).find('input[name="pmpro_userfields_group_membership[]"]:checked').each(function () {
				group_levels.push(parseInt(jQuery(this).attr('id').replace('pmpro_userfields_group_membership_', '')));
			});

			// Get fields.
			let group_fields = [];
			jQuery(this).find('div.pmpro_userfield-group-fields div.pmpro_userfield-field-settings').each(function () {
				let field_label = jQuery(this).find('input[name=pmpro_userfields_field_label]').val();
				let field_name = jQuery(this).find('input[name=pmpro_userfields_field_name]').val();
				let field_type = jQuery(this).find('select[name=pmpro_userfields_field_type]').val();
				let field_required = jQuery(this).find('select[name=pmpro_userfields_field_required]').val();
				let field_readonly = jQuery(this).find('select[name=pmpro_userfields_field_readonly]').val();
				let field_profile = jQuery(this).find('select[name=pmpro_userfields_field_profile]').val();
				let field_wrapper_class = jQuery(this).find('input[name=pmpro_userfields_field_class]').val();
				let field_element_class = jQuery(this).find('input[name=pmpro_userfields_field_divclass]').val();
				let field_hint = jQuery(this).find('textarea[name=pmpro_userfields_field_hint]').val();
				let field_options = jQuery(this).find('textarea[name=pmpro_userfields_field_options]').val();

				// Get level ids.            
				let field_levels = [];
				jQuery(this).find('input[name="pmpro_userfields_field_levels[]"]:checked').each(function () {
					field_levels.push(parseInt(jQuery(this).attr('id').replace('pmpro_userfields_field_levels_', '')));
				});

				let field = {
					'label': field_label,
					'name': field_name,
					'type': field_type,
					'required': field_required,
					'readonly': field_readonly,
					'levels': field_levels,
					'profile': field_profile,
					'wrapper_class': field_wrapper_class,
					'element_class': field_element_class,
					'hint': field_hint,
					'options': field_options,
				}

				// Add to array. (Only if it has a label or name.)
				if (field.label.length > 0 || field.name.length > 0) {
					group_fields.push(field);
				}
			});

			// Set up the field group object.
			let field_group = {
				'name': group_name,
				'checkout': group_checkout,
				'profile': group_profile,
				'description': group_description,
				'levels': group_levels,
				'fields': group_fields
			};

			// Add to array.
			field_groups.push(field_group);
		});

		// console.log( field_groups );
		jQuery('#pmpro_user_fields_settings').val(JSON.stringify(field_groups));

		return true;
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
	var template, disabled, $subject, $editor, $testemail;
	$subject = $("#pmpro_email_template_subject").closest("tr");
	$editor = $("#wp-email_template_body-wrap");
	$testemail = $("#test_email_address").closest("tr");

	$(".pmpro_admin .hide-while-loading").hide();
	$(".pmpro_admin .controls").hide();

	/* PMPro Email Template Switcher */
	$("#pmpro_email_template_switcher").change(function () {

		$(".status_message").hide();
		template = $(this).val();

		//get template data
		if (template)
			pmpro_get_template(template);
		else {
			$(".pmpro_admin .hide-while-loading").hide();
			$(".pmpro_admin .controls").hide();
		}
	});

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

	/* Functions */
	function pmpro_get_template(template) {

		//hide stuff and show ajax spinner
		$(".hide-while-loading").hide();
		$("#pmproet-spinner").show();

		//get template data
		$data = {
			template: template,
			action: 'pmpro_email_templates_get_template_data',
			security: $('input[name=security]').val()
		};

		//console.log( $data );

		$.post(ajaxurl, $data, function (response) {
			var template_data = JSON.parse(response);

			//show/hide stuff
			$("#pmproet-spinner").hide();
			$(".pmpro_admin .controls").show();
			$(".pmpro_admin .hide-while-loading").show();
			$(".pmpro_admin .status").hide();

			//change disable text
			if (template == 'header' || template === 'footer') {

				$subject.hide();
				$testemail.hide();

				if (template == 'header')
					$("#disable_label").text("Disable email header for all PMPro emails?");
				else
					$("#disable_label").text("Disable email footer for all PMPro emails?");

				//hide description
				$("#disable_description").hide();
			}
			else {
				$testemail.show();
				$("#disable_label").text("Disable this email?");
				$("#disable_description").show().text("PMPro emails with this template will not be sent.");
			}

			// populate help text, subject, and body
			$('#pmpro_email_template_help_text').text(template_data['help_text']);
			$('#pmpro_email_template_subject').val(template_data['subject']);
			$('#pmpro_email_template_body').val(template_data['body']);

			// disable form
			disabled = template_data['disabled'];
			pmpro_toggle_form_disabled(disabled);
		});
	}

	function pmpro_save_template() {

		$("#submit_template_data").attr("disabled", true);
		$(".status").hide();
		// console.log(template);

		$data = {
			template: template,
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
			$("#submit_template_data").attr("disabled", false);
			$(".status_message").html(response);
			$(".status").show();
			$(".status_message").show();
		});

		return $.Deferred().resolve();
	}

	function pmpro_reset_template() {

		var r = confirm('Are you sure? Your current template settings will be deleted permanently.');

		if (!r) return false;

		$data = {
			template: template,
			action: 'pmpro_email_templates_reset_template_data',
			security: $('input[name=security]').val()
		};
		$.post(ajaxurl, $data, function (response) {
			var template_data = $.parseJSON(response);
			$('#pmpro_email_template_subject').val(template_data['subject']);
			$('#pmpro_email_template_body').val(template_data['body']);
		});

		return true;
	}

	function pmpro_disable_template() {

		//update wp_options
		data = {
			template: template,
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
				}
				else {
					$(".status_message_wrapper").addClass("updated");
					$(".status_message").show().text("Template Enabled");
				}
			}

			$(".hide-while-loading").show();

			disabled = response['status'];

			pmpro_toggle_form_disabled(disabled);
		});
	}

	function pmpro_send_test_email() {

		//hide stuff and show ajax spinner
		$(".hide-while-loading").hide();
		$("#pmproet-spinner").show();

		data = {
			template: template,
			email: $("#test_email_address").val(),
			action: 'pmpro_email_templates_send_test',
			security: $('input[name=security]').val()
		};

		$.post(ajaxurl, data, function (success) {
			//show/hide stuff
			$("#pmproet-spinner").hide();
			$(".pmpro_admin .controls").show();
			$(".pmpro_admin .hide-while-loading").show();

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
			$(".pmpro_admin .controls").hide();
		}
		else {
			$("#pmpro_email_template_disable").prop('checked', false);
			$("#pmpro_email_template_body").removeAttr('readonly', 'readonly').removeAttr('disabled', 'disabled');
			$("#pmpro_email_template_subject").removeAttr('readonly', 'readonly').removeAttr('disabled', 'disabled');
			$(".pmpro_admin .controls").show();
		}

	}

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
						var primaryButtons = responseElement.find('.button-primary');
						if (primaryButtons.length > 0) {
							var activateButton = primaryButtons[0];
							var activateButtonHref = activateButton.getAttribute('href');
							if (activateButtonHref) {
								// Wait 1 second before showing the activate button.
								setTimeout(function () {
									button.siblings('input[name="pmproAddOnAdminAction"]').val('activate');
									button.siblings('input[name="pmproAddOnAdminActionUrl"]').val(activateButtonHref);
									button.html('Activate');
									button.removeClass('disabled');
								}, 1000);
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

					// Hide tne PMPro message.
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
