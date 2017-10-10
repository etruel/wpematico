jQuery(document).ready(function($){
	$('button[btn-href]').click(function(e) {
		location.href = $(this).attr('btn-href');
	});
	$('#campaign_edit_reset').click(function(e) {
		if ($(this).data('wpematico_before_save')) {
			e.preventDefault();
			return false;
		}
		if (!confirm(wpematico_object.text_confirm_reset_campaign)) {
			e.preventDefault();
		}
	});
	$('#campaign_edit_see_logs').click(function(e) {
		window.open(wpematico_object.see_logs_action_url, wpematico_object.name_campaign,'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=700, height=600');
		e.preventDefault();
	});
	$('#campaign_edit_del_hash').click(function(e) {
		if ($(this).data('wpematico_before_save')) {
			e.preventDefault();
			return false;
		}
		if (!confirm(wpematico_object.text_confirm_delhash_campaign)) {
			e.preventDefault();
		}
	});

//	$('#run_now').click(function(e) {
	$(document).on("click", '#run_now', function(e) {
		$(this).addClass('green');
		$('html').css('cursor','wait');
		$('#fieldserror').remove();
		msgdev="<img width='12' src='"+wpematico_object.image_run_loading+"' class='mt2'> "+wpematico_object.text_running_campaign+"";
		$("#poststuff").prepend('<div id="fieldserror" class="updated fade he20">'+msgdev+'</div>');
		c_ID = $('#post_ID').val();
		var data = {
			campaign_ID: c_ID ,
			action: "wpematico_run"
		};
		$.post(ajaxurl, data, function(msgdev) {  //si todo ok devuelve LOG sino 0
			$('#fieldserror').remove();
			if( msgdev.substring(0, 5) == 'ERROR' ){
					$("#poststuff").prepend('<div id="fieldserror" class="error fade">'+msgdev+'</div>');
			}else{
				$("#poststuff").prepend('<div id="fieldserror" class="updated fade">'+msgdev+'</div>');
			}
			$('html').css('cursor','auto');
			$('#run_now').removeClass('green');
		});
		e.preventDefault();
	});

	/**
	 * Feeds functions and actions
	 */
	$('#addmorefeed').click(function() {
		oldval = $('#feedfield_max').val();
		jQuery('#feedfield_max').val( parseInt(jQuery('#feedfield_max').val(),10) + 1 );
		newval = $('#feedfield_max').val();
		feed_new= $('.feed_new_field').clone();
		$('div.feed_new_field').removeClass('feed_new_field');
		$('div#feed_ID'+oldval).fadeIn();
		$('input[name="campaign_feeds['+oldval+']"]').focus();
		feed_new.attr('id','feed_ID'+newval);
		$('input', feed_new).eq(0).attr('name','campaign_feeds['+ newval +']');
		$('.delete', feed_new).eq(0).attr('onclick', "delete_feed_url('#feed_ID"+ newval +"');");
		$(document).trigger("before_add_more_feed", [feed_new, newval] );
		$('#feeds_list').append(feed_new);
		$('#feeds_list').vSort();
	});

	$('#campaign_striphtml').change(function() {
		if ($('#campaign_striphtml').is(':checked')) {
			$('#campaign_strip_links').attr('checked', false);
			$('#div_campaign_strip_links_options').fadeOut();
			$('#div_campaign_strip_links').fadeOut();
		} else {
			$('#div_campaign_strip_links').fadeIn();
		}
	});
	$('#campaign_enable_template').change(function() {
		if ($('#campaign_enable_template').is(':checked')) {
			$('#postemplatearea').fadeIn();
		} else {
			$('#postemplatearea').fadeOut();
		}
	});
	$('#campaign_strip_links').change(function() {
		if ($('#campaign_striphtml').is(':checked') && $('#campaign_strip_links').is(':checked')) {
			$('#campaign_strip_links').attr('checked', false);
			$('#div_campaign_strip_links_options').fadeOut();
			return false;
		}
		if ($('#campaign_strip_links').is(':checked')) {
			$('#div_campaign_strip_links_options').fadeIn();
		} else {
			$('#div_campaign_strip_links_options').fadeOut();
		}

	});

	$('#campaign_linktosource').change(function() {
		if ($('#campaign_linktosource').is(':checked')) {
			$('#copy_permanlink_source').attr('checked', false);
		}
	});
	$('#copy_permanlink_source').change(function() {
		if ($('#copy_permanlink_source').is(':checked')) {
			$('#campaign_linktosource').attr('checked', false);
		}
	});

	$('#campaign_autocats').change(function(e) {
		if ($('#campaign_autocats').is(':checked')) {
			$('#autocats_container').fadeIn();
		} else {
			$('#autocats_container').fadeOut();
		}
	});

	

	$(document).on("click", '#deletefeed', function(event) {
		delete_feed_url($(this).attr('data'));
	});
	
	$(document).on("click", '.check1feed', function(event) {
		item = $(this).parent().parent().find('.feed_column input');
		feed = item.val();
		var working = $(this);
		$(working).removeClass('warning')
			.removeClass("frowning_face")
			.removeClass("smiling_face")
			.addClass('ruedita');

		if (feed !== "") {
			$(item).attr('style','Background:#CCC;');
			var data = {
				action: "wpematico_test_feed",
				url: feed, 
				'cookie': encodeURIComponent(document.cookie)
			};
			data = js_apply_filters('wpematico_data_test_feed', data, item);
			
			$.post(ajaxurl, data, function(response){
				var dismiss = '<button type="button" class="notice-dismiss"><span class="screen-reader-text">'+wpematico_object.text_dismiss_this_notice+'</span></button>';
				$('.feederror').remove();
				if(response.success ){
					$(item).attr('style','Background:#75EC77;');
					$("#poststuff").prepend('<div id="message" class="feederror notice notice-success is-dismissible"><p>'+response.message+'</p>' +dismiss +'</div>');
					$(working).removeClass('warning')
							.removeClass("frowning_face")
							.removeClass("ruedita")
							.addClass('smiling_face');
				} else {
					$(item).attr('style','Background:Red;');
					$("#poststuff").prepend('<div id="message" class="feederror notice notice-error is-dismissible"><p>ERROR: '+response.message+'</p>' +dismiss +'</div>');
					$(working).removeClass('warning')
							.removeClass("smiling_face")
							.removeClass("ruedita")
							.addClass('frowning_face');

				}
			});
 		} else {			
			alert(wpematico_object.text_type_some_feed_url);
			$(working).removeClass('ruedita')
				.removeClass("frowning_face")
				.removeClass("smiling_face")
				.addClass('warning');
		}
	});

	$(document).on("click", '#checkfeeds', function(event) {

		var feederr = 0;
		var feedcnt = 0;
		var errmsg = "Feed ERROR";
		$('.feederror').remove();
		$('.feedinput').each(function (el,item) {
			feederr += 1;
			feed = $(item).attr('value');
			var working = $(item).parent().parent().find('#checkfeed');
			if (feed !== "") {
				$(working).removeClass('warning')
					.removeClass("frowning_face")
					.removeClass("smiling_face")
					.addClass('ruedita');
				$(item).attr('style','Background:#CCC;');
				var data = {
					action: "wpematico_test_feed",
					url: feed, 
					'cookie': encodeURIComponent(document.cookie)
				};

				data = js_apply_filters('wpematico_data_test_feed', data, $(item));

				$.post(ajaxurl, data, function(response){
					var dismiss = '<button type="button" class="notice-dismiss"><span class="screen-reader-text">'+wpematico_object.text_dismiss_this_notice+'</span></button>';
					if( response.success ){
						$(item).attr('style','Background:#75EC77;');
						$("#poststuff").prepend('<div id="message" class="feederror notice notice-success is-dismissible"><p>'+response.message+'</p>' +dismiss +'</div>');
						$(working).removeClass('warning')
								.removeClass("frowning_face")
								.removeClass("ruedita")
								.addClass('smiling_face');
					} else {
						$(item).attr('style','Background:Red;');
						$("#poststuff").prepend('<div id="message" class="feederror notice notice-error is-dismissible"><p>ERROR: '+response.message+'</p>' +dismiss +'</div>');
						$(working).removeClass('warning')
								.removeClass("smiling_face")
								.removeClass("ruedita")
								.addClass('frowning_face');
					}
					$(working).removeClass("spinner");
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
function disable_run_now() {
	jQuery('.wpematico_current_state a').each(function() {
	  jQuery(this).data('wpematico_before_save', 'true');
	});
	jQuery('.wpematico_current_state > a, button').attr('disabled','disabled');
	jQuery('.wpematico_current_state a').click(function(e) { 
		e.preventDefault(); 
	}); 
	
	jQuery('#run_now').attr('title',wpematico_object.text_save_before_run_campaign);
	jQuery('.wpematico_current_state a').attr('title',wpematico_object.text_save_before_execute_action);
}
delete_feed_url = function(row_id){
	jQuery(row_id).fadeOut(); 
	jQuery(row_id).remove();
	disable_run_now();
	jQuery('#msgdrag').html(wpematico_object.update2save).fadeIn();
}
