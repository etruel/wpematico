/* global jQuery, wpematicoTour */
(function ($) {
	'use strict';

	if (typeof wpematicoTour === 'undefined' || !$.fn.pointer) {
		return;
	}

	var tour = wpematicoTour;
	var $open = null;

	function save(what) {
		$.post(tour.ajaxurl, {action: 'wpematico_tour', do: what, screen: tour.screen, nonce: tour.nonce});
	}

	function close() {
		if ($open) {
			$open.pointer('close');
			$open = null;
		}
	}

	// A step whose target is missing or hidden is left out, so the last step is
	// the last one this page can actually show.
	function visibleSteps() {
		return $.grep(tour.steps, function (step) {
			var $target = $(step.target);
			return $target.length && $target.is(':visible');
		});
	}

	function button(label, cls, onClick) {
		return $('<a href="#"></a>').addClass(cls).text(label).on('click', function (e) {
			e.preventDefault();
			onClick();
		});
	}

	function show(steps, index) {
		close();
		if (index >= steps.length) {
			return;
		}
		var step = steps[index];
		var last = (index === steps.length - 1);
		var $target = $(step.target).first();

		// Bring the top of the target into view: a tall target (the Settings menu)
		// centred would leave the pointer above the window.
		var top = $target[0].getBoundingClientRect().top;
		if (top < 40 || top > window.innerHeight - 200) {
			window.scrollBy(0, top - window.innerHeight / 3);
		}

		$target.pointer({
			content: step.content,
			position: {edge: step.edge, align: step.align},
			pointerClass: 'wp-pointer wpematico-tour wpematico-tour-align-' + step.align,
			buttons: function () {
				var $buttons = $('<div class="wpematico-tour-buttons"></div>');
				if (last) {
					$buttons.append(button(tour.i18n.off, 'wpematico-tour-off', function () {
						save('off');
						close();
					}));
					$buttons.append(button(tour.i18n.done, 'button button-primary', function () {
						save('done');
						close();
					}));
				} else {
					$buttons.append(button(tour.i18n.dismiss, 'wpematico-tour-dismiss', function () {
						save('done');
						close();
					}));
					$buttons.append(button(tour.i18n.next, 'button button-primary', function () {
						show(steps, index + 1);
					}));
				}
				return $buttons;
			}
		}).pointer('open');
		$open = $target;
	}

	function start() {
		var steps = visibleSteps();
		if (steps.length) {
			show(steps, 0);
		}
	}

	$(document).on('click', '.wpematico-tour-start', function (e) {
		e.preventDefault();
		if ($('#contextual-help-wrap').is(':visible')) {
			$('#contextual-help-link').trigger('click');
		}
		// Let the Help panel finish closing, or the first pointer lands where it was.
		setTimeout(start, 400);
	});

	// A beat after ready: some targets (the Wizard button) are added by other ready
	// handlers. Not on load, which waits for every remote image of the sidebar.
	$(function () {
		if (tour.autostart) {
			setTimeout(start, 500);
		}
	});
})(jQuery);
