jQuery(document).ready(function($){
	events_submit_post($);
	wpe_others_events($);
	if ($('input[name="original_post_status"]').val() != 'publish') {
		$('#post').change();
	}
	$('button[btn-href]').click(function(e) {
		location.href = $(this).attr('btn-href');
	});
	$('button.cpanelbutton').hover(
		function(e) {
			$(this).attr('title_out', $(this).attr('title') );
			$('#cpanelnotebar').text( $(this).attr('title_out') );
			$(this).attr('title', '') ;
		},
		function(e) {
			$('#cpanelnotebar').text( '' );
			$(this).attr( 'title', $(this).attr('title_out') ) ;
		}
	);

	$('#feed_actions button').hover(
		function(e) {
			$('#cpanelnotebar').text( $(this).attr('title') );
		},
		function(e) {
			$('#cpanelnotebar').text( '' );
		}
	);

	$('#campaign_edit_reset').click(function(e) {
		if ($(this).data('wpematico_before_save')) {
			e.preventDefault();
			return false;
		}
		if (!confirm(wpematico_object.text_confirm_reset_campaign)) {
			e.preventDefault();
		}else{
			location.href = $(this).attr('btn-action');
		}
	});
	$('#campaign_edit_see_logs').click(function(e) {
		window.open(wpematico_object.see_logs_action_url, wpematico_object.name_campaign+'_see_logs','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=700, height=600');
		e.preventDefault();
	});

	$('#campaign_edit_preview').click(function(e) {
		window.open(wpematico_object.preview_campaign_action_url, wpematico_object.name_campaign+'_preview','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=900, height=600');
		e.preventDefault();
	});

	$('#campaign_edit_del_hash').click(function(e) {
		if ($(this).data('wpematico_before_save')) {
			e.preventDefault();
			return false;
		}
		if (!confirm(wpematico_object.text_confirm_delhash_campaign)) {
			e.preventDefault();
		}else{
			location.href = $(this).attr('btn-action');
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
		}).fail(function() {
			$('#fieldserror').remove();
			$("#poststuff").prepend('<div id="fieldserror" class="error fade">'+wpematico_object.text_fail_run_campaign+'</div>');
			$('html').css('cursor','auto');
			$('#run_now').removeClass('green');
		});
		e.preventDefault();
	});

	/**
	 * Feeds functions and actions
	 */
	$('#scrollfeeds').click(function() {
		$('#feeds_list').toggleClass('maxhe290');
		$(this).toggleClass('dashicons-arrow-up-alt2').toggleClass('dashicons-arrow-down-alt2');
		if( $(this).hasClass('dashicons-arrow-up-alt2')){
			title = $(this).attr('titleon');
		}else{
			title = $(this).attr('titleoff');
		}
		$(this).attr('title', title);
	});
	
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
		$('.deletefeed', feed_new).eq(0).attr('onclick', "delete_feed_url('#feed_ID"+ newval +"');");
		$('.deletefeed', feed_new).eq(0).attr('id', 'deletefeed_'+newval);
		$('.deletefeed', feed_new).eq(0).attr('data', "#feed_ID"+ newval);


		$(document).trigger("before_add_more_feed", [feed_new, newval] );
		$('#feeds_list').append(feed_new);
		$('#feeds_list').vSort();
		$('#pb-totalrecords').text( parseInt($('#pb-totalrecords').text()) + 1 );
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

	

	$(document).on("click", '.deletefeed', function(e) {
		delete_feed_url($(this).attr('data'));
		$('#pb-totalrecords').text( parseInt( $('#pb-totalrecords').text()) - 1 );
		e.preventDefault();
	});
	
	
	$(document).on("click", '.check1feed', function(event) {
		item = $(this).parent().parent().find('.feed_column input');
		feed = item.val();
		var working = $(this);
		$(working).removeClass('dashicons-editor-spellcheck')
			.removeClass("dashicons-thumbs-down red")
			.removeClass("dashicons-thumbs-up green")
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
					$(working).removeClass('dashicons-editor-spellcheck')
							.removeClass("dashicons-thumbs-down red")
							.removeClass("ruedita")
							.addClass('dashicons-thumbs-up green');
				} else {
					$(item).attr('style','Background:Red;');
					$("#poststuff").prepend('<div id="message" class="feederror notice notice-error is-dismissible"><p>ERROR: '+response.message+'</p>' +dismiss +'</div>');
					$(working).removeClass('dashicons-editor-spellcheck')
							.removeClass("dashicons-thumbs-up green")
							.removeClass("ruedita")
							.addClass('dashicons-thumbs-down red');

				}
			});
 		} else {			
			alert(wpematico_object.text_type_some_feed_url);
			$(working).removeClass('ruedita')
				.removeClass("dashicons-thumbs-down red")
				.removeClass("dashicons-thumbs-up green")
				.addClass('dashicons-editor-spellcheck');
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
				$(working).removeClass('dashicons-editor-spellcheck')
					.removeClass("dashicons-thumbs-down red")
					.removeClass("dashicons-thumbs-up green")
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
						$(working).removeClass('dashicons-editor-spellcheck')
								.removeClass("dashicons-thumbs-down red")
								.removeClass("ruedita")
								.addClass('dashicons-thumbs-up green');
					} else {
						$(item).attr('style','Background:Red;');
						$("#poststuff").prepend('<div id="message" class="feederror notice notice-error is-dismissible"><p>ERROR: '+response.message+'</p>' +dismiss +'</div>');
						$(working).removeClass('dashicons-editor-spellcheck')
								.removeClass("dashicons-thumbs-up green")
								.removeClass("ruedita")
								.addClass('dashicons-thumbs-down red');
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
	var new_template = '<div id="w2c_ID{index}" class="row_word_to_cat"><div class="pDiv jobtype-select p7" id="nuevow2c"><div id="w1"><label>'+wpematico_object.text_w2c_word+' <input type="text" size="25" class="regular-text" id="campaign_wrd2cat" name="campaign_wrd2cat[word][{index}]" value="" /></label><br /><label><input name="campaign_wrd2cat[title][{index}]" id="campaign_wrd2cat_title" class="checkbox w2ctitle" value="1" type="checkbox"/>'+wpematico_object.text_w2c_on_title+'&nbsp;&nbsp;</label><label><input name="campaign_wrd2cat[regex][{index}]" id="campaign_wrd2cat_regex" class="checkbox w2cregex" value="1" type="checkbox"/>'+wpematico_object.text_w2c_regex+'&nbsp;&nbsp;</label><label><input name="campaign_wrd2cat[cases][{index}]" id="campaign_wrd2cat_cases" class="checkbox w2ccases" value="1" type="checkbox" />'+wpematico_object.text_w2c_case_sensitive+'&nbsp;&nbsp;</label></div><div id="c1">'+wpematico_object.text_w2c_to_category+wpematico_object.wpe_w2c_dropdown_categories+'</div><span class="wi10" id="w2cactions"><label title="'+wpematico_object.text_w2c_delete_this_item+'" class="bicon delete left btn_delete_w2c"></label></span></div></div>';
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
	jQuery('.wpematico_current_state').find('a').each(function() {
	  jQuery(this).data('wpematico_before_save', 'true');
	});
	jQuery('.wpematico_current_state').find('a, button').attr('disabled','disabled');
	jQuery('.wpematico_current_state').find('a').click(function(e) { 
		e.preventDefault(); 
	}); 
	
	jQuery('#run_now').attr('title',wpematico_object.text_save_before_run_campaign);
	jQuery('.wpematico_current_state').find('a').attr('title',wpematico_object.text_save_before_execute_action);
}
delete_feed_url = function(row_id){
	jQuery(row_id).fadeOut(); 
	jQuery(row_id).remove();
	disable_run_now();
	jQuery('#msgdrag').html(wpematico_object.update2save).fadeIn();
}

function events_submit_post($) {
	

	
	$('#post').submit( function(e) {		//checkfields

		// Skip validation if already validated
	    if (! $(this).data('campaign_valid') ) {
	       var $formpost = $(this);
		    // Make sure the browser doesn't submit the form
	    	e.preventDefault();
	    	var $spinner = $('#major-publishing-actions #publishing-action .spinner');
	    	$spinner.addClass('is-active');
			var $submitButtons = $formpost.find(':submit, a.submitdelete, #post-preview');
			$submitButtons.addClass('disabled');


			$('#wpcontent .ajax-loading').attr('style',' visibility: visible;');
			
			var error = false;
			var wrd2cat= $('input[name^="campaign_wrd2cat[word]"]').serialize();
			var wrd2cat_regex  = new Array();
			$(".w2cregex").each(function() {
				if ( true == $(this).is(':checked')) {
					wrd2cat_regex.push('1');
				} else {
					wrd2cat_regex.push('0');
				}
			});

			var reword = $("textarea[name^='campaign_word_origin']").serialize();
			var reword_regex  = new Array();
			$("input[name^='campaign_word_option_regex']").each(function() {
				if ( true == $(this).is(':checked')) {
					reword_regex.push('1');
				} else {
					reword_regex.push('0');
				}
			});
			var reword_title  = new Array();
			$("input[name^='campaign_word_option_title']").each(function() {
				if ( true == $(this).is(':checked')) {
					reword_title.push('1');
				} else {
					reword_title.push('0');
				}
			});

			var feeds = $("input[name*='campaign_feeds']").serialize();
						
			var data = {
				campaign_feeds: feeds,
				campaign_word_origin: reword,
				campaign_word_option_regex: reword_regex,
				campaign_word_option_title: reword_title,
				campaign_wrd2cat: wrd2cat,
				campaign_wrd2cat_regex: wrd2cat_regex,
				action: "wpematico_checkfields"
			};
			data = js_apply_filters('wpematico_checkfields_data', data);
			
			$.post(ajaxurl, data, function(todok){  //si todo ok devuelve 1 sino el error
				if( todok != 1 ) {
		            error=true;
		            msg=todok;
		            $('#fieldserror').remove();
		            $("#poststuff").prepend('<div id="fieldserror" class="error fade">ERROR: '+msg+'</div>');
		            $('#wpcontent .ajax-loading').attr('style',' visibility: hidden;');
		        	$spinner.removeClass('is-active');
		            $submitButtons.removeClass('disabled');
		        } else {
		            $formpost.data('campaign_valid', true);
		            error=false;  //then submit campaign
		            $('.w2ccases').removeAttr('disabled'); //si todo bien habilito los check para que los tome el php
		            //$formpost.submit();
		            $submitButtons.removeClass('disabled');
		            $('#publish').click();
		            setTimeout(function(){ jQuery('#publish').click(); }, 1000);
		        }
			});
	    }
	});
	
	// This code prevents the URL from being filled with the wp-post-new-reload value
	$('#publish').click(function(e) {
		if ( $( '#original_post_status' ).val() === 'auto-draft' && window.history.replaceState ) {
			var location;
			location = window.location.href;
			if ((location.split('wp-post-new-reload').length - 1) > 1 ) {
				location = location.replace('?wp-post-new-reload=true', '');
				location = location.replace('&wp-post-new-reload=true', '');
				window.history.replaceState( null, null, location );
			}
		}
	});
}

function wpe_others_events($) {

	$('#post-visibility-display').text(wpematico_object.visibility_trans);
	$('#hidden-post-visibility').val(wpematico_object.visibility);
	$('#visibility-radio-'+wpematico_object.visibility).attr('checked', true);
	$('#postexcerpt .hndle span').text(wpematico_object.description);
	$('#postexcerpt .inside .screen-reader-text').text(wpematico_object.description);
	$('#postexcerpt .inside p').text(wpematico_object.description_help);



	$('#psearchtext').keyup(function(tecla){
		if(tecla.keyCode==27) {
			$(this).attr('value','');
			
			$('.feedinput').each(function (el,item) {
				feed = $(item).attr('value');
				if (feed != '') {
					$(item).parent().parent().show();
				} else {
					$(item).parent().parent().hide();
				}
			});
		} else {
			buscafeed = $(this).val();
			$('.feedinput').each(function (el,item) {
				feed = $(item).attr('value');
				if (feed.toLowerCase().indexOf(buscafeed) >= 0) {
					if (feed != '') {
						$(item).parent().parent().show();
					} 
								
				} else {
					$(item).parent().parent().hide();
				}
			});
		}
	});
			
	$('#psearchcat').keyup(function(tecla){
		if(tecla.keyCode==27) {
			$(this).attr('value','');
			
			$('.selectit').each(function (el,item) {
				cat = $(item).text();
				if (cat != '') {
					$(item).parent().show();
				} else {
					$(item).parent().hide();
				}
			});
			$('#catfield').fadeOut();
		} else {
			buscacat = $(this).val();
			$('.selectit').each(function (el,item) {
				cat = $(item).text();
				if (cat.toLowerCase().indexOf(buscacat) >= 0) {
					if (cat != ''){
						$(item).parent().show();
					} 
								
				} else {
					$(item).parent().hide();
				}
			});
		}
	});
			
	$('#catsearch').click(function() {
		$('#catfield').toggle();
		$('#psearchcat').focus();
	});

	jQuery('#campaign_no_setting_img').click(function() {
		if ( true == jQuery('#campaign_no_setting_img').is(':checked')) {
			jQuery('#div_no_setting_img').fadeIn();
		} else {
			jQuery('#div_no_setting_img').fadeOut();
		}
	});

	jQuery('#campaign_imgcache').click(function() {
		if ( true == jQuery('#campaign_imgcache').is(':checked')) {
			jQuery('#nolinkimg').fadeIn();
		} else {
			jQuery('#nolinkimg').fadeOut();
		}
	});
	jQuery('#campaign_enable_featured_image_selector').click(function() {
		if ( true == jQuery('#campaign_enable_featured_image_selector').is(':checked')) {
			jQuery('#featured_img_selector_div').fadeIn();
		} else {
			jQuery('#featured_img_selector_div').fadeOut();
		}
	});
			

	jQuery('#campaign_imgcache, #campaign_featuredimg').click(function() {
		if ( true == jQuery('#campaign_imgcache').is(':checked') || true == jQuery('#campaign_featuredimg').is(':checked') ) {
			jQuery('#custom_uploads').fadeIn();
		} else {
			jQuery('#custom_uploads').fadeOut();
		}
	});

	jQuery('#campaign_no_setting_audio').click(function() {
		if ( true == jQuery('#campaign_no_setting_audio').is(':checked')) {
			jQuery('#div_no_setting_audio').fadeIn();
		} else {
			jQuery('#div_no_setting_audio').fadeOut();
		}
	});


	jQuery('#campaign_audio_cache').click(function() {
		if ( true == jQuery('#campaign_audio_cache').is(':checked')) {
			jQuery('#nolink_audio').fadeIn();
			jQuery('#custom_uploads_audios').fadeIn();
		} else {
			jQuery('#nolink_audio').fadeOut();
			jQuery('#custom_uploads_audios').fadeOut();
					
		}
	});

	jQuery('#campaign_no_setting_video').click(function() {
		if ( true == jQuery('#campaign_no_setting_video').is(':checked')) {
			jQuery('#div_no_setting_video').fadeIn();
		} else {
			jQuery('#div_no_setting_video').fadeOut();
		}
	});


	jQuery('#campaign_video_cache').click(function() {
		if ( true == jQuery('#campaign_video_cache').is(':checked')) {
			jQuery('#nolink_video').fadeIn();
			jQuery('#custom_uploads_videos').fadeIn();
		} else {
			jQuery('#nolink_video').fadeOut();
			jQuery('#custom_uploads_videos').fadeOut();
					
		}
	});
	$('.tag').click(function(){
		$('#campaign_template').attr('value',$('#campaign_template').attr('value')+$(this).html());
	});
			
	$(document).on('click','.w2cregex',function() {
		var cases = $(this).parent().parent().find('#campaign_wrd2cat_cases');
		if ( true == $(this).is(':checked')) {
			cases.attr('checked','checked');
			cases.attr('disabled','disabled');
		} else {
			cases.removeAttr('checked');
			cases.removeAttr('disabled');
		}
	});

	$(document).on('click','#addmorerew',function() {
		$('#rew_max').val( parseInt($('#rew_max').val(),10) + 1 );
		newval = $('#rew_max').val();					
		nuevo= $('#nuevorew').clone();
		$('input', nuevo).eq(0).attr('name','campaign_word_option_title['+ newval +']');
		$('input', nuevo).eq(1).attr('name','campaign_word_option_regex['+ newval +']');
		$('textarea', nuevo).eq(0).attr('name','campaign_word_origin['+ newval +']');
		$('textarea', nuevo).eq(1).attr('name','campaign_word_rewrite['+ newval +']');
		$('textarea', nuevo).eq(2).attr('name','campaign_word_relink['+ newval +']');
		$('input', nuevo).eq(0).removeAttr('checked');
		$('input', nuevo).eq(1).removeAttr('checked');
		$('#rw3', nuevo).show();
		$('textarea', nuevo).eq(0).text('');
		$('textarea', nuevo).eq(1).text('');
		$('textarea', nuevo).eq(2).text('');
		nuevo.show();
		$('#rewrites_edit').append(nuevo);
	});
			
	$(document).on("click", '.notice-dismiss', function(event) {
		$(this).parent().remove();
	});
					
	$('.feedinput').focus(function() {
		$(this).attr('style','Background:#FFFFFF;');
	});

	$(document).on("change", '#post', function(event) {
		disable_run_now();
	});
}