jQuery(document).ready(function ($) {
	$('#button_yes_changelog').on('click', function() {
        // Set the value to true
        var data = {
            'action': 'process_button_click',
            'value': true,
			'nonce': wpematico_object.nonce
        };
		jQuery.post(ajaxurl, data, function (response) {
            if (response.success) {
				$('#wpe_changelog-notice').hide();
            }else{
				console.log(response);
			}
        });
    });
	jQuery('#mailsndemail').on( "blur", function () {
		var x = jQuery(this).val();
		var atpos = x.indexOf("@");
		var dotpos = x.lastIndexOf(".");
		if (atpos < 1 || dotpos < atpos + 2 || dotpos + 2 >= x.length) {
			jQuery('#mailmsg').text(wpematico_object.text_invalid_email);
			return false;
		} else {
			jQuery('#mailmsg').text("");
			return true;
		}
	});

	jQuery('#imgcache').on("click", function () {
		if (true == jQuery('#imgcache').is(':checked')) {
			jQuery('#nolinkimg').fadeIn();
		} else {
			jQuery('#nolinkimg').fadeOut();
		}
	});

	jQuery('#imgcache').on("click", function () {
		if (true == jQuery('#imgcache').is(':checked')) {
			jQuery('.attr_image_p').fadeIn();
		} else {
			jQuery('.attr_image_p').fadeOut();
		}
	});
	
	jQuery('#fifu').on("click", function () {
		if (true == jQuery('#fifu').is(':checked')) {
			jQuery('#fifu_extra_options').fadeIn();
		} else {
			jQuery('#fifu_extra_options').fadeOut();
		}
	});

	jQuery('#audio_cache').on("click", function () {
		if (true == jQuery('#audio_cache').is(':checked')) {
			jQuery('#nolink_audio').fadeIn();
			jQuery('#custom_uploads_audios').fadeIn();
		} else {
			jQuery('#nolink_audio').fadeOut();
			jQuery('#custom_uploads_audios').fadeOut();
		}
	});

	jQuery('#video_cache').on("click", function () {
		if (true == jQuery('#video_cache').is(':checked')) {
			jQuery('#nolink_video').fadeIn();
			jQuery('#custom_uploads_videos').fadeIn();
		} else {
			jQuery('#nolink_video').fadeOut();
			jQuery('#custom_uploads_videos').fadeOut();
		}
	});

	jQuery('#imgcache, #featuredimg').on("click", function () {
		if (true == jQuery('#imgcache').is(':checked') || true == jQuery('#featuredimg').is(':checked')) {
			jQuery('#custom_uploads').fadeIn();
		} else {
			jQuery('#custom_uploads').fadeOut();
		}
	});

	jQuery('#allowduplicates').on("click", function () {
		if (true == jQuery('#allowduplicates').is(':checked')) {
			jQuery('#enadup').fadeIn();
		} else {
			jQuery('#allowduptitle').removeAttr("checked");
			jQuery('#allowduphash').removeAttr("checked");
			jQuery('#enadup').fadeOut();
		}
	});
	jQuery('#disabledashboard').on("click", function () {
		if (true == jQuery('#disabledashboard').is(':checked')) {
			jQuery('#roles').fadeOut();
			jQuery('#roleslabel').fadeOut();
		} else {
			jQuery('#roles').fadeIn();
			jQuery('#roleslabel').fadeIn();
		}
	});

	jQuery('#disable_credits').on("click", function () {
		if (jQuery('#disable_credits').is(':checked')) {
			jQuery('#discredits').fadeIn();
		} else {
			jQuery('#discredits').fadeOut();
		}
	});

	jQuery('#set_stupidly_fast').on("click", function () {
		if (false == jQuery('#set_stupidly_fast').is(':checked')) {
			jQuery('#simplepie_strip_attributes').removeAttr("disabled");
			jQuery('#simplepie_strip_htmltags').removeAttr("disabled");
		} else {
			jQuery('#simplepie_strip_attributes').prop("checked", false);
			jQuery('#simplepie_strip_htmltags').prop("checked", false);
			jQuery('#simplepie_strip_attributes').attr("disabled", "disabled");
			jQuery('#simplepie_strip_htmltags').attr("disabled", "disabled");
		}
	});
	jQuery('#simplepie_strip_htmltags').on("click", function () {
		if (false == jQuery('#simplepie_strip_htmltags').is(':checked')) {
			jQuery('#strip_htmltags').attr('disabled', true);
		} else {
			jQuery('#strip_htmltags').removeAttr("disabled");
		}
	});
	jQuery('#simplepie_strip_attributes').on("click", function () {
		if (false == jQuery('#simplepie_strip_attributes').is(':checked')) {
			jQuery('#strip_htmlattr').attr('disabled', true);
		} else {
			jQuery('#strip_htmlattr').removeAttr("disabled");
		}
	});
	jQuery('#emptytrashbutton').on("click", function () {
		if (true == jQuery('#emptytrashbutton').is(':checked')) {
			jQuery('#hlptrash').fadeIn();
		} else {
			jQuery('#hlptrash').fadeOut();
		}
	});

	jQuery('#campaign_in_postslist').on("click", function () {
		if (true == jQuery('#campaign_in_postslist').is(':checked')) {
			jQuery('#column_campaign_pos_field').fadeIn();
		} else {
			jQuery('#column_campaign_pos_field').fadeOut();
		}
	});

	jQuery('#disableccf, #allowduptitle').on("change", function () {
		if (jQuery('#disableccf, #allowduptitle').is(':checked')) {
			jQuery('#div_add_extra_duplicate_filter_meta_source').fadeOut();
		} else {
			jQuery('#div_add_extra_duplicate_filter_meta_source').fadeIn();
		}
	});

});

jQuery(function () {
	jQuery(".help_tip").tipTip({maxWidth: "300px", edgeOffset: 5, fadeIn: 50, fadeOut: 50, keepAlive: true, defaultPosition: "right"});
});

//Metaboxes on Settings
jQuery(document).on('ready', function ($) {
	postboxes.save_state = function () {

	};
	postboxes.save_order = function () {

	};
	// close postboxes that should be closed
	jQuery('.if-js-closed').removeClass('if-js-closed').addClass('closed');
	postboxes.add_postbox_toggles('wpematico_page_wpematico_settings');
});

// Danger Zone

jQuery(document).on('ready', function ($) {
	jQuery(document).on("click", '#wpe_debug_logs_campaign', function () {
		if (true == jQuery('#wpe_debug_logs_campaign').is(':checked')) {
			jQuery('#deledebug').fadeOut();
			jQuery('#wpe_delete_debug_logs_campaign').removeAttr("checked");
		} else {
			jQuery('#deledebug').fadeIn();
			jQuery('#wpe_delete_debug_logs_campaign').prop("checked",true);
		}
	});
});