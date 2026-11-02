jQuery(document).ready(function($) {
	var cfg = window.wpematico_license_object;

	// Typing a key offers to check it before it is saved.
	$('.wpe-licenses-form').on('input paste cut', '.inp_license_key', function() {
		var $card = $(this).closest('.postbox-license'),
			$section = $card.find('.wpe-status-section');
		if ('' === $(this).val()) {
			$section.fadeOut();
			return;
		}
		$section.find('.wpe-status-row').html('<p><input class="button-secondary wpe-license-act" data-do="check_license" type="button" value="' + cfg.txt_check_license + '" /></p>');
		$section.find('.wpe-license-message').attr('class', 'wpe-license-message').text('');
		$section.fadeIn();
	});

	// Check, Activate and Deactivate: the server answers with the status block as it now is.
	$('.wpe-licenses-form').on('click', '.wpe-license-act', function() {
		var $btn = $(this),
			$card = $btn.closest('.postbox-license'),
			$row = $card.find('.wpe-status-row'),
			$msg = $card.find('.wpe-license-message');

		$card.find('.wpe-license-act').prop('disabled', true);
		$msg.attr('class', 'wpe-license-message').text(cfg.txt_working);

		$.post(cfg.ajax_url, {
			action: 'wpematico_license',
			nonce: cfg.nonce_handler_license,
			plugin: $card.data('plugin'),
			do: $btn.data('do'),
			license: $card.find('.inp_license_key').val()
		}).done(function(res) {
			if (res && res.success) {
				if (res.data.html) {
					$row.html(res.data.html);
				}
				$msg.addClass(res.data.ok ? 'ok' : 'err').text(res.data.message || '');
				$card.find('.wpe-license-act').prop('disabled', false);
			} else {
				$msg.addClass('err').text(res && res.data ? res.data.message : cfg.txt_failed);
				$card.find('.wpe-license-act').prop('disabled', false);
			}
		}).fail(function() {
			$msg.addClass('err').text(cfg.txt_failed);
			$card.find('.wpe-license-act').prop('disabled', false);
		});
	});
});
