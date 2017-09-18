jQuery(document).ready(function($){

	jQuery('#campaign_striphtml').change(function() {
		if (jQuery('#campaign_striphtml').is(':checked')) {
			jQuery('#campaign_strip_links').attr('checked', false);
			jQuery('#div_campaign_strip_links_options').fadeOut();
			jQuery('#div_campaign_strip_links').fadeOut();
		} else {
			jQuery('#div_campaign_strip_links').fadeIn();
		}
	});
	jQuery('#campaign_enable_template').change(function() {
		if (jQuery('#campaign_enable_template').is(':checked')) {
			jQuery('#postemplatearea').fadeIn();
		} else {
			jQuery('#postemplatearea').fadeOut();
		}
	});
	jQuery('#campaign_strip_links').change(function() {
		if (jQuery('#campaign_striphtml').is(':checked') && jQuery('#campaign_strip_links').is(':checked')) {
			jQuery('#campaign_strip_links').attr('checked', false);
			jQuery('#div_campaign_strip_links_options').fadeOut();
			return false;
		}
		if (jQuery('#campaign_strip_links').is(':checked')) {
			jQuery('#div_campaign_strip_links_options').fadeIn();
		} else {
			jQuery('#div_campaign_strip_links_options').fadeOut();
		}

	});

	jQuery('#campaign_linktosource').change(function() {
		if (jQuery('#campaign_linktosource').is(':checked')) {
			jQuery('#copy_permanlink_source').attr('checked', false);
		}
	});
	jQuery('#copy_permanlink_source').change(function() {
		if (jQuery('#copy_permanlink_source').is(':checked')) {
			jQuery('#campaign_linktosource').attr('checked', false);
		}
	});

	jQuery('#campaign_autocats').change(function(e) {
		if (jQuery('#campaign_autocats').is(':checked')) {
			jQuery('#autocats_container').fadeIn();
		} else {
			jQuery('#autocats_container').fadeOut();
		}
	});


	jQuery(document).on("click", '.check1feed', function(event) {
		item = jQuery(this).parent().parent().find('.feed_column input');
		feed = item.val();
		var working = jQuery(this);
		jQuery(working).removeClass('warning')
			.removeClass("frowning_face")
			.removeClass("smiling_face")
			.addClass('ruedita');

		if (feed !== "") {
			jQuery(item).attr('style','Background:#CCC;');
			var data = {
				action: "wpematico_test_feed",
				url: feed, 
				'cookie': encodeURIComponent(document.cookie)
			};
			data = js_apply_filters('wpematico_data_test_feed', data, item);
			
			jQuery.post(ajaxurl, data, function(response){
				var dismiss = '<button type="button" class="notice-dismiss"><span class="screen-reader-text">'+wpematico_object.text_dismiss_this_notice+'</span></button>';
				jQuery('.feederror').remove();
				if(response.success ){
					jQuery(item).attr('style','Background:#75EC77;');
					jQuery("#poststuff").prepend('<div id="message" class="feederror notice notice-success is-dismissible"><p>'+response.message+'</p>' +dismiss +'</div>');
					jQuery(working).removeClass('warning')
							.removeClass("frowning_face")
							.removeClass("ruedita")
							.addClass('smiling_face');
				} else {
					jQuery(item).attr('style','Background:Red;');
					jQuery("#poststuff").prepend('<div id="message" class="feederror notice notice-error is-dismissible"><p>ERROR: '+response.message+'</p>' +dismiss +'</div>');
					jQuery(working).removeClass('warning')
							.removeClass("smiling_face")
							.removeClass("ruedita")
							.addClass('frowning_face');

				}
			});
 		} else {			
			alert(wpematico_object.text_type_some_feed_url);
			jQuery(working).removeClass('ruedita')
				.removeClass("frowning_face")
				.removeClass("smiling_face")
				.addClass('warning');
		}
	});

	jQuery(document).on("click", '#checkfeeds', function(event) {

		var feederr = 0;
		var feedcnt = 0;
		var errmsg = "Feed ERROR";
		jQuery('.feederror').remove();
		jQuery('.feedinput').each(function (el,item) {
			feederr += 1;
			feed = jQuery(item).attr('value');
			var working = jQuery(item).parent().parent().find('#checkfeed');
			if (feed !== "") {
				jQuery(working).removeClass('warning')
					.removeClass("frowning_face")
					.removeClass("smiling_face")
					.addClass('ruedita');
				jQuery(item).attr('style','Background:#CCC;');
				var data = {
					action: "wpematico_test_feed",
					url: feed, 
					'cookie': encodeURIComponent(document.cookie)
				};

				data = js_apply_filters('wpematico_data_test_feed', data, jQuery(item));

				jQuery.post(ajaxurl, data, function(response){
					var dismiss = '<button type="button" class="notice-dismiss"><span class="screen-reader-text">'+wpematico_object.text_dismiss_this_notice+'</span></button>';
					if( response.success ){
						jQuery(item).attr('style','Background:#75EC77;');
						jQuery("#poststuff").prepend('<div id="message" class="feederror notice notice-success is-dismissible"><p>'+response.message+'</p>' +dismiss +'</div>');
						jQuery(working).removeClass('warning')
								.removeClass("frowning_face")
								.removeClass("ruedita")
								.addClass('smiling_face');
					} else {
						jQuery(item).attr('style','Background:Red;');
						jQuery("#poststuff").prepend('<div id="message" class="feederror notice notice-error is-dismissible"><p>ERROR: '+response.message+'</p>' +dismiss +'</div>');
						jQuery(working).removeClass('warning')
								.removeClass("smiling_face")
								.removeClass("ruedita")
								.addClass('frowning_face');
					}
					jQuery(working).removeClass("spinner");
				});
			}else{
				if(feedcnt>1) {
					alert(wpematico_object.text_type_some_new_feed_urls);
				} 
			}
		}); 
		if(feederr == 1){
			alert(errmsg);
		} else { 

		}
	});
});