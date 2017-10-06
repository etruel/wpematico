jQuery(document).ready(function($){

	jQuery('#run_now').click(function(e) {
		jQuery(this).addClass('green');
		jQuery('html').css('cursor','wait');
		jQuery('#fieldserror').remove();
		msgdev="<img width='12' src='"+wpematico_object.image_run_loading+"' class='mt2'> "+wpematico_object.text_running_campaign+"";
		jQuery("#poststuff").prepend('<div id="fieldserror" class="updated fade he20">'+msgdev+'</div>');
		c_ID = jQuery('#post_ID').val();
		var data = {
			campaign_ID: c_ID ,
			action: "wpematico_run"
		};
		jQuery.post(ajaxurl, data, function(msgdev) {  //si todo ok devuelve LOG sino 0
			jQuery('#fieldserror').remove();
			if( msgdev.substring(0, 5) == 'ERROR' ){
					jQuery("#poststuff").prepend('<div id="fieldserror" class="error fade">'+msgdev+'</div>');
			}else{
				jQuery("#poststuff").prepend('<div id="fieldserror" class="updated fade">'+msgdev+'</div>');
			}
			jQuery('html').css('cursor','auto');
			jQuery('#run_now').removeClass('green');
		});
		e.preventDefault();
	});

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

	word2cats_events();
});

function word2cats_events() {
	jQuery(document).on('click', '#addmorew2c', function(e) {
		jQuery('#wrd2cat_max').val( parseInt(jQuery('#wrd2cat_max').val(),10) + 1 );
		var new_index = jQuery('#wrd2cat_max').val();
		add_word2cats_row(new_index);	
		e.preventDefault();		
	});
	word2cats_events_rows();
}
function word2cats_events_rows() {
	jQuery('.btn_delete_w2c').click(function(e) {
		jQuery(this).parent().parent().parent().remove();
		e.preventDefault();
	});
}

function add_word2cats_row(index) {
	var new_template = '<div id="w2c_ID{index}" class="row_word_to_cat"><div class="pDiv jobtype-select p7" id="nuevow2c"><div id="w1" class="left"><label>'+wpematico_object.text_w2c_word+' <input type="text" size="25" class="regular-text" id="campaign_wrd2cat" name="campaign_wrd2cat[word][{index}]" value="" /></label><br /><label><input name="campaign_wrd2cat[title][{index}]" id="campaign_wrd2cat_title" class="checkbox w2ctitle" value="1" type="checkbox"/>'+wpematico_object.text_w2c_on_title+'&nbsp;&nbsp;</label><label><input name="campaign_wrd2cat[regex][{index}]" id="campaign_wrd2cat_regex" class="checkbox w2cregex" value="1" type="checkbox"/>'+wpematico_object.text_w2c_regex+'&nbsp;&nbsp;</label><label><input name="campaign_wrd2cat[cases][{index}]" id="campaign_wrd2cat_cases" class="checkbox w2ccases" value="1" type="checkbox" />'+wpematico_object.text_w2c_case_sensitive+'&nbsp;&nbsp;</label></div><div id="c1" class="left">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'+wpematico_object.text_w2c_to_category+''+wpematico_object.wpe_w2c_dropdown_categories+'</div><span class="wi10" id="w2cactions"><label title="'+wpematico_object.text_w2c_delete_this_item+'" class="bicon delete left btn_delete_w2c"></label></span></div></div>';
	new_template = wpematico_replace_all(new_template, '{index}', index);
	jQuery('#wrd2cat_edit').append(new_template);
	word2cats_events_rows();
}
delete_row_input = function(row_id){
	jQuery(row_id).fadeOut('slow', function() { $(this).remove(); });
	disable_run_now();
}
function wpematico_replace_all(str, find, replace) {
    return str.replace(new RegExp(find, 'g'), replace);
}
