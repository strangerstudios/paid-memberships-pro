/**
 * PMPro Dashboard - Updated for 3-column grid
 * This code allows users to reorder dashboard widgets via drag and drop.
 * It also disables the default WordPress postbox functionality to prevent conflicts.
 * This script uses jQuery UI's sortable functionality to manage the drag-and-drop behavior.
 * It stores the initial positions of the widgets and animates them to their new positions when reordered.
 * The new order is saved via AJAX when the user stops dragging a widget.
 */

jQuery(document).ready(function () {

	// ARIA live region helper for announcements
	function ensureLiveRegion() {
		if (!jQuery('#pmpro-dashboard-live-region').length) {
			jQuery('body').append('<div id="pmpro-dashboard-live-region" role="status" aria-live="polite" aria-atomic="true" style="position:absolute;left:-9999px;height:1px;width:1px;overflow:hidden;"></div>');
		}
	}

	// Function to announce drag actions
	function announceDrag($item, action) {
		ensureLiveRegion();
		const label = $item.find('.hndle').text().trim() || $item.attr('id');
		const text = label + ' ' + action;
		jQuery('#pmpro-dashboard-live-region').text(text);
	}

	let pmproDashboardPositions = {};
	let isAnimating = false;
	
	const dashboardForm = jQuery('#dashboard-widgets', '#pmpro-dashboard-form');

	// Store initial positions
	function storePositions() {
		pmproDashboardPositions = {};
		dashboardForm.children('.postbox').each(function() {
			if (this.id) {
				pmproDashboardPositions[this.id] = this.getBoundingClientRect();
			}
		});
	}

	// Animate elements to their new positions
	function animateToNewPositions(excludeElement) {
		if (isAnimating) return;
		isAnimating = true;

		dashboardForm.children('.postbox').not(excludeElement).each(function() {
			const oldRect = pmproDashboardPositions[this.id];
			const newRect = this.getBoundingClientRect();
			
			if (!oldRect) return;
			
			const dx = oldRect.left - newRect.left;
			const dy = oldRect.top - newRect.top;
			
			if (Math.abs(dx) > 1 || Math.abs(dy) > 1) {
				// Apply transform instantly
				this.style.transition = 'none';
				this.style.transform = `translate(${dx}px, ${dy}px)`;
				
				// Force reflow
				this.offsetHeight;
				
				// Animate back to natural position
				this.style.transition = 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
				this.style.transform = 'translate(0, 0)';
			}
		});

		// Clean up after animation
		setTimeout(() => {
			dashboardForm.children('.postbox').each(function() {
				this.style.transition = '';
				this.style.transform = '';
			});
			isAnimating = false;
		}, 300);
	}

	dashboardForm.sortable({
		items: '.postbox[role="listitem"]',
		handle: '.hndle',
		cursor: 'move',
		opacity: 0.8,
		placeholder: 'ui-sortable-placeholder',
		tolerance: 'pointer',
		distance: 10, // Reduce distance for easier drag initiation
		delay: 8, // Reduce delay for more responsive feel
		containment: 'parent', // Less restrictive containment
		start: function(event, ui) {
			// Store positions before drag starts
			storePositions();
			ui.item.addClass('pmpro-dragging');
			ui.item.attr('aria-grabbed', 'true');
			announceDrag(ui.item, 'picked up');
			
			// Copy grid column span from dragged item to placeholder
			const columnSpan = ui.item.css('grid-column');
			const spanClass = ui.item.attr('class').match(/pmpro-colspan-(\d+)/);
			
			if (spanClass) {
				// Ensure we don't exceed 3 columns in the new grid
				const spanValue = Math.min(parseInt(spanClass[1]));
				ui.placeholder.addClass('pmpro-colspan-' + spanValue);
			} else if (columnSpan && columnSpan !== 'auto') {
				ui.placeholder.css('grid-column', columnSpan);
			}
			
			// Set placeholder height to match dragged item
			const itemHeight = ui.item.height();
			ui.placeholder.css({
				'height': itemHeight + 'px',
				'box-sizing': 'border-box'
			});
		},

		change: function(event, ui) {
			// Only animate when placeholder position actually changes
			if (!isAnimating) {
				requestAnimationFrame(() => {
					animateToNewPositions(ui.item);
					// Update positions after animation starts
					setTimeout(() => {
						storePositions();
					}, 10);
				});
			}
		},

		over: function(event, ui) {
			// Force placeholder visibility when over the sortable area
			ui.placeholder.show();
		},

		out: function(event, ui) {
			// Keep placeholder visible even when temporarily outside
			ui.placeholder.show();
		},

		beforeStop: function(event, ui) {
			// Clean up any ongoing animations
			dashboardForm.children('.postbox').each(function() {
				this.style.transition = '';
				this.style.transform = '';
			});
		},

		stop: function(event, ui) {
			ui.item.removeClass('pmpro-dragging');
			ui.item.attr('aria-grabbed', 'false');
			announceDrag(ui.item, 'dropped');
			
			// Clean up placeholder classes and styles - Updated for 3-column grid
			ui.placeholder.removeClass(function (index, className) {
				return (className.match(/(^|\s)pmpro-colspan-\S+/g) || []).join(' ');
			});
			ui.placeholder.css({
				'grid-column': '',
				'height': ''
			});
			
			isAnimating = false;
		},

		update: function(event, ui) {
			// Add drop animation
			ui.item.addClass('pmpro-just-dropped');
			setTimeout(function() {
				ui.item.removeClass('pmpro-just-dropped');
			}, 250);

			// Get all metabox IDs in their new order
			var newOrder = [];
			jQuery('.postbox', dashboardForm).each(function() {
				var id = jQuery(this).attr('id');
				if (id) {
					newOrder.push(id);
				}
			});
						
			// Save the new order via AJAX
			if (newOrder.length > 0) {
				var nonceValue = jQuery('#pmpro_metabox_nonce').val();

				if (!nonceValue) {
					console.error('Nonce field not found or empty');
					return;
				}

				// Save the new sort order
				savePosition(newOrder, nonceValue);
			}
		}
	});

	/**
	 * Keyboard Accessibility for Dragging
	 * Allows users to pick up, move, and drop items using keyboard keys.
	 * Uses space/enter to pick up and drop, arrow keys to move.
	 */
	let $dragged = null;

	jQuery('#dashboard-widgets').on('keydown', '.hndle', function(e) {
		const $item = jQuery(this).closest('.postbox[role="listitem"]');

		if (($dragged === null) && (e.key === ' ' || e.key === 'Enter')) {
			// Pick up the item
			e.preventDefault();
			$dragged = $item;
			$item.attr('aria-grabbed', 'true').addClass('pmpro-dragged-by-keyboard');
			announceDrag($item, 'Picked up (use arrows to move, enter/space to drop)');
		} else if ($dragged && $item[0] === $dragged[0]) {
			// While holding an item, allow up/down/left/right to move
			if (['ArrowUp', 'ArrowLeft'].includes(e.key)) {
				e.preventDefault();
				let $prev = $item.prevAll('.postbox[role="listitem"]').first();
				if ($prev.length) {
					$prev.before($item);
					announceDrag($item, 'moved');
					$item.find('.hndle').focus();
				}
			} else if (['ArrowDown', 'ArrowRight'].includes(e.key)) {
				e.preventDefault();
				let $next = $item.nextAll('.postbox[role="listitem"]').first();
				if ($next.length) {
					$next.after($item);
					announceDrag($item, 'moved');
					$item.find('.hndle').focus();
				}
			} else if (e.key === ' ' || e.key === 'Enter') {
				// Drop
				e.preventDefault();
				$item.attr('aria-grabbed', 'false').removeClass('pmpro-dragged-by-keyboard');
				announceDrag($item, 'dropped');
				$dragged = null;
				// Optionally: trigger your save order logic here (AJAX)
				var newOrder = [];
				jQuery('.postbox[role="listitem"]', dashboardForm).each(function() {
					var id = jQuery(this).attr('id');
					if (id) {
						newOrder.push(id);
					}
				});
				if (newOrder.length > 0) {
					var nonceValue = jQuery('#pmpro_metabox_nonce').val();
					if (nonceValue) {
						// Save the new sort order
						savePosition(newOrder, nonceValue);
					}
				}
			} else if (e.key === 'Escape') {
				// Cancel
				e.preventDefault();
				$item.attr('aria-grabbed', 'false').removeClass('pmpro-dragged-by-keyboard');
				announceDrag($item, 'cancelled');
				$dragged = null;
			}
		}
	});

	// Visual focus for grabbed item
	jQuery(document).on('focusin focusout', '.hndle', function(event) {
		jQuery(this).closest('.postbox[role="listitem"]').toggleClass('pmpro-keyboard-focus', event.type === 'focusin');
	});

	// Disable WordPress postbox functionality here
	// This prevents conflicts with our custom drag-and-drop functionality.
	if (typeof postboxes !== 'undefined') {
		postboxes.handle_click = function() { return false; };
		postboxes.add_postbox_toggles = function() { return false; };
	}

	// Helper function to save the new order via AJAX
	function savePosition( newOrder, nonceValue ) {
		if (typeof ajaxurl === 'undefined') {
			console.error('AJAX URL is not defined. Ensure that the script is enqueued properly.');
			return;
		}
		// Ensure nonce value is valid
		if (!nonceValue || nonceValue === '') {
			console.error('Nonce value is not defined or empty. Ensure the nonce field exists in the form.');
			return;
		}
		// Make the AJAX request to save the new order
		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'pmpro_save_metabox_order',
				pmpro_metabox_nonce: nonceValue,
				order: newOrder.join(',')
			},
			dataType: 'json',
			timeout: 5000,
			error: function(xhr, status, error) {
				console.error('AJAX Error - Status:', status);
				console.error('AJAX Error - Error:', error);
			}
	});
	}
});