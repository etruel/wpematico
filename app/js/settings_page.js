jQuery(document).ready(function ($) {

	jQuery('#mailsndemail').blur(function () {
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

	jQuery('#imgcache').click(function () {
		if (true == jQuery('#imgcache').is(':checked')) {
			jQuery('#nolinkimg').fadeIn();
		} else {
			jQuery('#nolinkimg').fadeOut();
		}
	});
	jQuery('#audio_cache').click(function () {
		if (true == jQuery('#audio_cache').is(':checked')) {
			jQuery('#nolink_audio').fadeIn();
			jQuery('#custom_uploads_audios').fadeIn();
		} else {
			jQuery('#nolink_audio').fadeOut();
			jQuery('#custom_uploads_audios').fadeOut();
		}
	});

	jQuery('#video_cache').click(function () {
		if (true == jQuery('#video_cache').is(':checked')) {
			jQuery('#nolink_video').fadeIn();
			jQuery('#custom_uploads_videos').fadeIn();
		} else {
			jQuery('#nolink_video').fadeOut();
			jQuery('#custom_uploads_videos').fadeOut();
		}
	});

	jQuery('#imgcache, #featuredimg').click(function () {
		if (true == jQuery('#imgcache').is(':checked') || true == jQuery('#featuredimg').is(':checked')) {
			jQuery('#custom_uploads').fadeIn();
		} else {
			jQuery('#custom_uploads').fadeOut();
		}
	});

	jQuery('#allowduplicates').click(function () {
		if (true == jQuery('#allowduplicates').is(':checked')) {
			jQuery('#enadup').fadeIn();
		} else {
			jQuery('#allowduptitle').removeAttr("checked");
			jQuery('#allowduphash').removeAttr("checked");
			jQuery('#enadup').fadeOut();
		}
	});
	jQuery('#disabledashboard').click(function () {
		if (true == jQuery('#disabledashboard').is(':checked')) {
			jQuery('#roles').fadeOut();
			jQuery('#roleslabel').fadeOut();
		} else {
			jQuery('#roles').fadeIn();
			jQuery('#roleslabel').fadeIn();
		}
	});

	jQuery('#disable_credits').click(function () {
		if (jQuery('#disable_credits').is(':checked')) {
			jQuery('#discredits').fadeIn();
		} else {
			jQuery('#discredits').fadeOut();
		}
	});

	jQuery('#set_stupidly_fast').click(function () {
		if (false == jQuery('#set_stupidly_fast').is(':checked')) {
			jQuery('#simpie').fadeIn();
		} else {
			jQuery('#simplepie_strip_attributes').removeAttr("checked");
			jQuery('#simplepie_strip_htmltags').removeAttr("checked");
			jQuery('#simpie').fadeOut();
		}
	});
	jQuery('#simplepie_strip_htmltags').click(function () {
		if (false == jQuery('#simplepie_strip_htmltags').is(':checked')) {
			jQuery('#strip_htmltags').attr('disabled', true);
		} else {
			jQuery('#strip_htmltags').removeAttr("disabled");
		}
	});
	jQuery('#simplepie_strip_attributes').click(function () {
		if (false == jQuery('#simplepie_strip_attributes').is(':checked')) {
			jQuery('#strip_htmlattr').attr('disabled', true);
		} else {
			jQuery('#strip_htmlattr').removeAttr("disabled");
		}
	});
	jQuery('#emptytrashbutton').click(function () {
		if (true == jQuery('#emptytrashbutton').is(':checked')) {
			jQuery('#hlptrash').fadeIn();
		} else {
			jQuery('#hlptrash').fadeOut();
		}
	});

	jQuery('#campaign_in_postslist').click(function () {
		if (true == jQuery('#campaign_in_postslist').is(':checked')) {
			jQuery('#column_campaign_pos_field').fadeIn();
		} else {
			jQuery('#column_campaign_pos_field').fadeOut();
		}
	});

	jQuery('#disableccf, #allowduptitle').change(function () {
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
		return;
	};
	postboxes.save_order = function () {
		return;
	};
	// close postboxes that should be closed
	jQuery('.if-js-closed').removeClass('if-js-closed').addClass('closed');
	postboxes.add_postbox_toggles('wpematico_page_wpematico_settings');
});
