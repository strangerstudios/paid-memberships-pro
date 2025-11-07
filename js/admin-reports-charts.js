/*
 * PMPro Reports Charts Helper (Chart.js)
 */
(function (window, $) {

	// Only run on the reports pages. ('memberships_page_pmpro-reports' body class added)
	if (!$('body').hasClass('memberships_page_pmpro-reports')) {
		return;
	}

	const ns = (window.pmproCharts = window.pmproCharts || {});

	// Keep track of instantiated charts so we can clean up/resizing.
	ns._instances = ns._instances || {};
	
	 // Default palette of colors for charts.
	ns.palette = [
		'#3366cc',
		'#dc3912',
		'#ff9900',
		'#109618',
		'#990099',
		'#0099c6',
		'#dd4477',
		'#66aa00',
		'#b82e2e',
		'#316395',
		'#994499',
		'#22aa99',
		'#aaaa11',
		'#6633cc',
		'#e67300',
	];

	ns.getCtx = function (id) {
		const el = document.getElementById(id);
		return el ? el.getContext('2d') : null;
	};

	ns.destroy = function (id) {
		if (ns._instances[id]) {
			try {
				ns._instances[id].destroy();
			} catch (e) {}
			delete ns._instances[id];
		}
	};

	ns.ensure = function (id, config) {
		ns.destroy(id);
		const ctx = ns.getCtx(id);
		if (!ctx || !window.Chart) return null;
		const chart = new window.Chart(ctx, config);
		ns._instances[id] = chart;
		return chart;
	};

	ns.formatNumber = function (value, locale) {
		try {
			return new Intl.NumberFormat(locale || document.documentElement.lang || undefined).format(value);
		} catch (e) {
			return (value || 0).toString();
		}
	};

	ns.formatCurrency = function (value, currencySymbol, locale) {
		// Chart labels already include the currency symbol in PMPro; keep it simple.
		// Use symbol prefix + localized number formatting.
		return (currencySymbol || '') + ns.formatNumber(value, locale);
	};

	// Resize charts when WP metaboxes are toggled.
	ns.bindMetaboxResize = function () {
		if (!ns._metaboxResizeBound) {
			ns._metaboxResizeBound = true;
			$(document).on('postbox-toggled', function () {
				Object.keys(ns._instances).forEach(function (id) {
					try {
						ns._instances[id].resize();
					} catch (e) {}
				});
			});
		}
	};

	// Auto-bind on document ready in WP Admin.
	$(function () {
		ns.bindMetaboxResize();
	});
})(
	window,
	window.jQuery ||
		function () {
			return { on: function () {} };
		}
);
