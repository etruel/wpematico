/**
 * Tabs of the WPeMatico Summary dashboard widget.
 * The markup already carries the selected state, so this only has to move it.
 */
(function () {
	'use strict';

	function setup(list) {
		var tabs = Array.prototype.slice.call(list.querySelectorAll('[role="tab"]'));
		if (!tabs.length) {
			return;
		}

		function select(tab) {
			tabs.forEach(function (one) {
				var on = one === tab;
				var panel = document.getElementById(one.getAttribute('aria-controls'));
				one.setAttribute('aria-selected', on ? 'true' : 'false');
				if (panel) {
					panel.hidden = !on;
				}
			});
		}

		tabs.forEach(function (tab) {
			tab.addEventListener('click', function () {
				select(tab);
			});
			tab.addEventListener('keydown', function (event) {
				var index = tabs.indexOf(tab);
				var next = null;
				if (event.key === 'ArrowRight') {
					next = tabs[(index + 1) % tabs.length];
				}
				if (event.key === 'ArrowLeft') {
					next = tabs[(index - 1 + tabs.length) % tabs.length];
				}
				if (next) {
					event.preventDefault();
					next.focus();
					select(next);
				}
			});
		});
	}

	function init() {
		document.querySelectorAll('.wpem-tabs[role="tablist"]').forEach(setup);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
}());
