jQuery(document).ready(function ($) {
        
	theclock();

	$('button[btn-href]').on("click", function (e) {
		location.href = $(this).attr('btn-href');
	});
	$(document).on("click", 'button.state_buttons.dashicons-controls-play', function (e) {
		var post_id = jQuery(this).parent().parent().parent().attr('id').replace('post-', '');
		run_now(post_id);
		e.preventDefault();
	});


	$('span:contains("' + wpematico_object.text_slug + '")').each(function (i) {
		$(this).parent().hide();
	});
	$('span:contains("' + wpematico_object.text_password + '")').each(function (i) {
		$(this).parent().parent().hide();
	});
	$('select[name="_status"]').each(function (i) {
		$(this).parent().parent().parent().parent().hide();
	});
	$('span:contains("' + wpematico_object.text_date + '")').each(function (i) {
		$(this).parent().hide();
	});
	$('.inline-edit-date').each(function (i) {
		$(this).hide();
	});
	$('.inline-edit-col-left').append($('#optionscampaign').html());
	$('#optionscampaign').remove();

	$('#screen-meta-links').append('<div id="contextual-help-link-wrap" class="hide-if-no-js screen-meta-toggle"><button type="button" id="show-clock" class="button show-clock" aria-controls="clock-wrap" aria-expanded="false">' + wpematico_object.i18n_date_format + '</button></div>');

	$("#cb-select-all-1, #cb-select-all-2").on("change", function () {
		$("input[name='post[]']").each(function () {
			if ($(this).is(':checked')) {
				$("tr#post-" + $(this).val()).css('background-color', '#dbb27e');
			} else {
				$("tr#post-" + $(this).val()).attr('style', '');
			}
		});
	});
	$("input[name='post[]']").on("change", function () {
		if ($(this).is(':checked')) {
			$("input[name='post[]']:checked").each(function () {
				$("tr#post-" + $(this).val()).css('background-color', '#dbb27e');
			});
		} else {
			$("tr#post-" + $(this).val()).attr('style', '');
		}
	});
});

function run_now(c_ID) {
    jQuery('html').css('cursor', 'wait');
    jQuery('#post-' + c_ID + ' .state_buttons.dashicons-controls-play').addClass('green');
    jQuery('#campaign-running-' + c_ID + '.wpe_campaign-running-tr').remove();
    var msgdev = '<p><span class="dashicons dashicons-admin-generic wpe_spinner"></span> <span style="vertical-align: top;">' + wpematico_object.text_running_campaign + '</span></p>';

    // Find the td where the campaign name is located and append the message inside it
    jQuery('#post-' + c_ID ).addClass('wpe_campaign_active');
    jQuery('#post-' + c_ID ).after('<tr class="wpe_campaign-running-tr" id="campaign-running-' + c_ID + '"><td colspan="7" class="campaign-running"><div id="fieldserror" class="notice notice-alt notice-warning fade">' + msgdev + '</div></td></tr>');

    var data = {
        campaign_ID: c_ID,
        action: "wpematico_run",
        nonce: wpematico_object.run_now_list_nonce
    };
    jQuery.post(ajaxurl, data, function (msgdev) { //si todo ok devuelve LOG sino 0
        jQuery('#campaign-running-' + c_ID + '.wpe_campaign-running-tr').remove();
        
        if (msgdev.substring(0, 5) == 'ERROR') {
            jQuery('#post-' + c_ID ).after('<tr class="wpe_campaign-running-tr" id="campaign-running-' + c_ID + '"><td colspan="7" class="campaign-running"><div id="fieldserror" class="notice notice-alt notice-error fade">' + msgdev + '</div></td></tr>');
        } else {
            jQuery('#post-' + c_ID ).after('<tr class="wpe_campaign-running-tr" id="campaign-running-' + c_ID + '"><td colspan="7" class="campaign-running"><div id="fieldserror" class="notice notice-alt notice-success fade">' + msgdev + '</div></td></tr>');
            var floor = Math.floor;
            var bef_posts = floor(jQuery("tr#post-" + c_ID + " > .count").html());
            var ret_posts = floor(bef_posts + floor(jQuery("#ret_lastposts").html()));
            if (bef_posts == ret_posts) {
                jQuery("tr#post-" + c_ID + " > .count").attr('style', 'font-weight: bold;color:#555;');
            } else {
                jQuery("tr#post-" + c_ID + " > .count").attr('style', 'font-weight: bold;color:#F00;');
            }
            jQuery("tr#post-" + c_ID + " > .count").html(ret_posts.toString());
            jQuery("#lastruntime").html(jQuery("#ret_lastruntime").html());
            jQuery("#lastruntime").attr('style', 'font-weight: bold;');
        }
        jQuery('html').css('cursor', 'auto');
        jQuery('#post-' + c_ID + ' .state_buttons.dashicons-controls-play').removeClass('green');
    });
}


function run_all() {
    var selectedItems = 0;
    jQuery("input[name='post[]']:checked").each(function () {
        selectedItems++;
    });
    if (selectedItems == 0) {
        alert(wpematico_object.text_select_a_campaign_to_run);
        return false;
    }

    jQuery('html').css('cursor', 'wait');
    var lengthEach = 0;
    
    // Process each selected campaign
    jQuery("input[name='post[]']:checked").each(function () {
        var c_ID = jQuery(this).val();
        jQuery('#post-' + c_ID).addClass('wpe_campaign_active');
        jQuery('#post-' + c_ID + ' .state_buttons.dashicons-controls-play').addClass('green');
        jQuery('#campaign-running-' + c_ID + '.wpe_campaign-running-tr').remove();

        // Create a unique message container for each campaign
        var messageContainerId = 'fieldserror_' + c_ID;
        var spinner = '<p><span class="dashicons dashicons-admin-generic wpe_spinner"></span> <span style="vertical-align: top;">' + wpematico_object.text_running_campaign + '</span></p>';
        jQuery('#post-' + c_ID ).after('<tr class="wpe_campaign-running-tr" id="campaign-running-' + c_ID + '"><td colspan="7" class="campaign-running"><div id="' + messageContainerId + '" class="notice notice-alt notice-warning fade ajaxstop">' + spinner + '</div></td></tr>');

        var data = {
            campaign_ID: c_ID,
            action: "wpematico_run",
            nonce: wpematico_object.run_now_list_nonce
        };

        jQuery.post(ajaxurl, data, function (msgdev) {
            jQuery('#campaign-running-' + c_ID + '.wpe_campaign-running-tr').remove();
            if (msgdev.substring(0, 5) == 'ERROR') {
                jQuery('#post-' + c_ID ).after('<tr class="wpe_campaign-running-tr" id="campaign-running-' + c_ID + '"><td colspan="7" class="campaign-running"><div id="' + messageContainerId + '" class="notice notice-alt notice-error fade">' + msgdev + '</div></td></tr>');
            } else {
                jQuery('#post-' + c_ID ).after('<tr class="wpe_campaign-running-tr" id="campaign-running-' + c_ID + '"><td colspan="7" class="campaign-running"><div id="' + messageContainerId + '" class="notice notice-alt notice-success fade">' + msgdev + '</div></td></tr>');
                var floor = Math.floor;
                var bef_posts = floor(jQuery("tr#post-" + c_ID + " > .count").html());
                var ret_posts = floor(bef_posts + floor(jQuery('#log_message_' + c_ID).next().next("#ret_lastposts").html()));
                if (bef_posts == ret_posts) {
                    jQuery("tr#post-" + c_ID + " > .count").attr('style', 'font-weight: bold;color:#555;');
                } else {
                    jQuery("tr#post-" + c_ID + " > .count").attr('style', 'font-weight: bold;color:#F00;');
                }
                jQuery("tr#post-" + c_ID + " > .count").html(ret_posts.toString());
                jQuery("#lastruntime").html(jQuery("#ret_lastruntime").html());
                jQuery("#lastruntime").attr('style', 'font-weight: bold;');
            }

            jQuery('#post-' + c_ID + ' .state_buttons.dashicons-controls-play').removeClass('green');
        }).done(function () {
            lengthEach++;
            if (jQuery("input[name='post[]']:checked").length == lengthEach) {
                jQuery('.ajaxstop').remove();
                jQuery('html').css('cursor', 'auto');
            }
        });
    });
}


function theclock() {
	nowdate = new Date();
	now = nowdate.format(wpematico_object.date_format);
	char = (nowdate.getSeconds() % 2 == 0) ? ' ' : ':';
	jQuery('#show-clock').html(now.replace(':', char));
	setTimeout("theclock()", 1000);
}
