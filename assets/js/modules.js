/* 
 * Javascript functions for modules dashboard.
 *
 * @since      3.0
 * @package    WPeMatico
 * @subpackage WPeMatico\Core
 * @author     Etruel Developments LLC <hello@etruel.com>
 */

jQuery(function ($) {
	const FILTER_KEY = 'wpematico_module_filter';

	/**
	 * Show the cards of one category and hide the rest.
	 *
	 * One decision and one animation per card. The previous version handed a
	 * completion callback to fadeOut() on the whole set, and jQuery runs that
	 * callback once per element -- so "Show All" fired 35 nested fadeIn() calls
	 * over all 35 cards, more than a thousand animations for one click.
	 */
	function applyFilter(category, animate) {
		$('.filterbymodule').removeClass('active')
			.filter(function () { return this.id === category; }).addClass('active');

		$('.wpematico-module-card').each(function () {
			const $card = $(this);
			const show = category === 'all'
				|| $card.hasClass(category)
				|| $card.hasClass('essentials-box');

			$card.stop(true, true);
			if (!animate) {
				$card.toggle(show);
			} else {
				$card[show ? 'fadeIn' : 'fadeOut'](200);
			}
		});
	}

	$(document).on('click', '.filterbymodule', function () {
		const category = this.id;
		if ($(this).hasClass('active')) return;

		applyFilter(category, true);
		// Installing a plugin reloads the page; without this the user is dropped
		// back into "Show All" and has to find the card again.
		try { window.sessionStorage.setItem(FILTER_KEY, category); } catch (e) {}
	});

	try {
		const saved = window.sessionStorage.getItem(FILTER_KEY);
		if (saved && saved !== 'all'
			&& $('.filterbymodule').filter(function () { return this.id === saved; }).length) {
			applyFilter(saved, false);
		}
	} catch (e) {}

	/**
	 * The dialog of this screen, in its two shapes: a question with two answers,
	 * or a message with one button. Everything the screen has to say goes through
	 * here -- browser alert() froze the whole tab and looked nothing like the rest
	 * of the page, next to a confirmation dialog that was already ours.
	 */
	function openModal(message, options) {
		const $yes = $('#modalYes').off('click');
		const $no = $('#modalNo').off('click');

		$('#modalMessage').text(message);
		$yes.text(options.confirmText);
		if (options.cancelText) {
			$no.text(options.cancelText).show();
		} else {
			$no.hide();
		}

		$('#customModal').show(0, function () {
			$(this).addClass('show');
		});
		$yes.trigger('focus');

		const close = function (answer) {
			$('#customModal').removeClass('show').fadeOut(0);
			$(document).off('keydown.wpematicoModal');
			if (options.done) options.done(answer);
		};

		$yes.on('click', function () { close(true); });
		$no.on('click', function () { close(false); });
		$(document).on('keydown.wpematicoModal', function (e) {
			if (e.key === 'Escape') close(false);
		});
	}

	function showModal(message, yesText, noText, callback) {
		openModal(message, { confirmText: yesText, cancelText: noText, done: callback });
	}

	/** Tell the user how something went. One button, no question. */
	function showNotice(message, callback) {
		openModal(message, { confirmText: wpematico_module.i18n.ok, done: callback });
	}


    $(document).on('click', '.pluginstall', function (e) {
        e.preventDefault();
        const $button = $(this);
        // It is an anchor: prop('disabled') means nothing to it, so the class is
        // what keeps a second click from starting another install.
        if ($button.hasClass('installing')) return;
		const $switch = $button.siblings('.wpematico-switch').find('input');
		// A requirement button installs the plugin this module stands on, not the
		// module's own: it carries its own (empty) data-module so the install does
		// not run the module callback afterwards.
		const module = $button.is('[data-module]') ? $button.data('module') : $switch.data('module');
        const pluginUrl = $button.data('url');
		// Kept aside because the label is written with .text(), which wipes it: while
		// installing that is what we want -- core's .installing draws its own spinner
		// in the :before and two icons would sit side by side -- but every label after
		// that has to get the download icon back.
		const iconHtml = $button.find('.dashicons').prop('outerHTML') || '';

        // Step 1: Confirm install
        showModal(
            wpematico_module.i18n.confirm_install,
            wpematico_module.i18n.install,
            wpematico_module.i18n.no,
            function(confirmInstall) {
                if (!confirmInstall) return;

                // Step 2: Ask if should activate after install
                showModal(
                    wpematico_module.i18n.confirm_activate,
                    wpematico_module.i18n.activate,
                    wpematico_module.i18n.no,
                    function(confirmActivate) {
                        const activateAfter = confirmActivate ? 1 : 0;

                        // Say what is happening and stop taking clicks. The
                        // `installing` class is core's own: it replaces the
                        // download icon with the spinning update one.
                        $button.addClass('installing').attr('aria-disabled', 'true')
                            .text(wpematico_module.i18n.installing);

                        // "Installing…" is wider than "Install", and on a card that
                        // also carries a Settings link the footer has no room to
                        // spare: the row would wrap and the card would jump taller
                        // mid-install. Settings leads nowhere useful while an
                        // install is running, so it steps aside and comes back with
                        // the button.
                        const $settings = $button.siblings('.module-settings').hide();

                        const restoreButton = function (label) {
                            $settings.show();
                            $button.removeClass('installing').removeAttr('aria-disabled')
                                .html(iconHtml + $('<span>').text(label).html());
                        };

                        // AJAX call
                        $.post(wpematico_module.ajax_url, {
                            action: 'wpematico_install_plugin',
                            nonce: wpematico_module.nonce,
                            plugin_url: pluginUrl,
                            module: module,
                            activate_after: activateAfter
                        }).done(function (response) {
                            if (response.success) {
                                restoreButton(wpematico_module.i18n.installed);
                                $button.removeClass('pluginstall').addClass('installed');

                                // Come back carrying the flag core sets on a bulk
                                // activation, so a plugin that queued its welcome
                                // page on activation stands down and the user stays
                                // here. Featured Image from URL does exactly that.
                                const back = new URL(window.location.href);
                                if (activateAfter) {
                                    back.searchParams.set('activate-multi', '1');
                                }
                                const reload = () => window.location.assign(back.toString());

                                if (response.data.message) {
                                    showNotice(response.data.message, reload);
                                } else {
                                    setTimeout(reload, 600);
                                }
                            } else {
                                restoreButton(wpematico_module.i18n.install_error);
                                showNotice(response.data.message || wpematico_module.i18n.install_failed);
                            }
                        }).fail(function () {
                            restoreButton(wpematico_module.i18n.install_error);
                            showNotice(wpematico_module.i18n.connection_error);
                        });
                    }
                );
            }
        );
    });

	// Toggle module switch
	$(document).on('change', '.wpematico-switch input', function () {
		const $switch = $(this);
		const $card = $switch.closest('.wpematico-module-card');
		const module = $switch.data('module');
		const plugin = $switch.data('plugin');
		const enabled = $switch.is(':checked');

		const revert = function (message) {
			$switch.prop('disabled', false).prop('checked', !enabled);
			$card.toggleClass('active', !enabled);
			if (message) {
				showNotice(message);
			}
		};

		// The card's accent border says the same thing as the switch, so it moves
		// with it right away instead of waiting for the next page load.
		$card.toggleClass('active', enabled);

		// Turning a module off here turns off a whole plugin, and other features
		// may be leaning on it, so ask before doing it.
		if (!enabled && plugin) {
			showModal(
				wpematico_module.i18n.confirm_off.replace('%s', plugin),
				wpematico_module.i18n.yes,
				wpematico_module.i18n.no,
				function (confirmed) {
					if (!confirmed) {
						revert();
						return;
					}
					sendToggle();
				}
			);
			return;
		}

		sendToggle();

		function sendToggle() {
		$switch.prop('disabled', true);

		$.post(wpematico_module.ajax_url, {
			action: 'wpematico_toggle_module',
			nonce: wpematico_module.nonce,
			module: module,
			enable: enabled ? 1 : 0
		}).done(function (response) {
			$switch.prop('disabled', false);

			if (!response.success) {
				revert(wpematico_module.i18n.module_error + response.data);
			}
		}).fail(function () {
			revert(wpematico_module.i18n.connection_error);
		});
		}
	});
});