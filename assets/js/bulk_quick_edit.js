(function($) {
	// Bulk Edit opens on "No change" every time. Core gives the bulk row the same
	// inline-edit-row class as the quick edit one, so a value left over from an
	// earlier Quick Edit would show up here and be applied to every campaign.
	var $wp_set_bulk = inlineEditPost.setBulk;
	inlineEditPost.setBulk = function () {
		var result = $wp_set_bulk.apply(this, arguments);
		var $bulk_row = $('#bulk-edit');
		$bulk_row.find('input[name="campaign_max"]').val('');
		$bulk_row.find('select[name^="campaign_"]').val('');
		return result;
	};

	// we create a copy of the WP inline edit post function
	var $wp_inline_edit = inlineEditPost.edit;
	
	// and then we overwrite the function with our own code
	inlineEditPost.edit = function( id ) {
	
		// "call" the original WP edit function
		// we don't want to leave WordPress hanging
		$wp_inline_edit.apply( this, arguments );
		// get the post ID
		var $post_id = 0;
		if ( typeof( id ) == 'object' )
			$post_id = parseInt( this.getId( id ) );
			
		if ( $post_id > 0 ) {
			// define the edit row
                    var $edit_row = $( '#edit-' + $post_id );
                    var $wc_inline_data = $('#inline_' + $post_id );

                    var $campaign_max     = $wc_inline_data.find('.campaign_max').text();
			$edit_row.find('input[name="campaign_max"]').val($campaign_max);

                    var $campaign_feeddate = $wc_inline_data.find('.campaign_feeddate').text();
			//$('input[name="campaign_feeddate"]', '.inline-edit-row').val($campaign_feeddate);
			$edit_row.find('input[name="campaign_feeddate"]').prop('checked', $campaign_feeddate == '1');
                    var $campaign_author = $wc_inline_data.find('.campaign_author').text();
			$edit_row.find( 'select[name="campaign_author"]' ).val( $campaign_author );
                    var $campaign_commentstatus = $wc_inline_data.find('.campaign_commentstatus').text();
			$edit_row.find( 'select[name="campaign_commentstatus"]' ).val( $campaign_commentstatus );
                    var $campaign_allowpings = $wc_inline_data.find('.campaign_allowpings').text();
			$edit_row.find('input[name="campaign_allowpings"]').prop('checked', $campaign_allowpings == '1');
                    var $campaign_linktosource = $wc_inline_data.find('.campaign_linktosource').text();
			$edit_row.find('input[name="campaign_linktosource"]').prop('checked', $campaign_linktosource == '1');
                    var $campaign_strip_links = $wc_inline_data.find('.campaign_strip_links').text();
			$edit_row.find('input[name="campaign_strip_links"]').prop('checked', $campaign_strip_links == '1');

			// get the campaign_posttype (posts_status)
                    var $campaign_posttype = $wc_inline_data.find('.campaign_posttype').text();
			$edit_row.find( 'select[name="campaign_posttype"]' ).val( $campaign_posttype );

			// get the campaign_customposttype (posttype or custom posttype)
                    var $campaign_customposttype = $wc_inline_data.find('.campaign_customposttype').text();
			$edit_row.find( 'select[name="campaign_customposttype"]' ).val( $campaign_customposttype );

                        // get the campaign_post_format (posts formats)
                    var $campaign_post_format = $wc_inline_data.find('.campaign_post_format').text();
			$edit_row.find( 'select[name="campaign_post_format"]' ).val( $campaign_post_format );

                 	// hierarchical categories
                    var $campaign_categories = $wc_inline_data.find('.campaign_categories').text();
					$edit_row.find('ul.category-checklist :checkbox').val($campaign_categories.split(','));
                        
                    var $campaign_tags = $wc_inline_data.find('.campaign_tags').text();
                        $edit_row.find( 'textarea[name="campaign_tags"]' ).text( $campaign_tags );

						custom_type($campaign_customposttype,$post_id, $campaign_categories);
						custom_tags($campaign_customposttype,$post_id);
			// get the release date and set the release date
//			var $release_date = $( '#release_date-' + $post_id ).text();
//			$edit_row.find( 'input[name="release_date"]' ).val( $release_date );
			
			// get the film rating and set the film rating
//			var $film_rating = $( '#film_rating-' + $post_id ).text();
//			$edit_row.find( 'select[name="film_rating"]' ).val( $film_rating );

			// Listen to changes on radio buttons
			$edit_row.find('[name=campaign_customposttype]').on('change', function () {
				var postType = $(this).val();
				custom_type(postType, $post_id, $campaign_categories);
				custom_tags(postType, $post_id); // Fetch and display tags for the new post type
			});
		}
		
	};
	 
	
    function custom_type(postType, post_id = 0){
		if ($('#taxonomies_container').length) {
			js_apply_filters('wpematico_load_custom_type', postType, post_id);
		}else{
			if(postType != 'post'){
				$('.inline-edit-categories .inline-edit-col').hide();
			}else{
				$('.inline-edit-categories .inline-edit-col').show();
			}
		}
    }

	function custom_tags(postType, post_id = 0) {
		if ($('#tags_container').length) {
			js_apply_filters('wpematico_load_tags', postType, post_id);
		}else{
			if(postType != 'post'){
				$('.inline-edit-col .inline-edit-tags').hide();
			}else{
				$('.inline-edit-col .inline-edit-tags').show();
			}
		}
	}
        
//        $( '#inline-edit' ).on( 'click', function() {
//		var $post_id = 0;
//		if ( typeof( id ) == 'object' )
//			$post_id = parseInt( this.getId( id ) );
//			
//		if ( $post_id > 0 ) {
//                    var $wc_inline_data = $('inline-edit-wpematico' + $post_id );
//                }
//        });
        
    $( '.submit.inline-edit-save .save' ).on( 'click', function() {
//		inlineEditPost.revert();
		var post_id = $(this).closest('tr').attr('id');

		post_id = post_id.replace("post-", "");

		var $wc_inline_data = $('#post-' + post_id);
                
                var $campaign_max = $wc_inline_data.find( 'input[name="campaign_max"]' ).val();

	});

        
    $( '#bulk_edit' ).on( 'click', function(e) {
		// define the bulk edit row
		var $bulk_row = $( '#bulk-edit' );
		
		// get the selected post ids that are being edited
		var $post_ids = new Array();
		$('input[name="post[]"]:checked').each(function() {
			$post_ids.push($(this).val());
		});
		
		// Only the fields the user actually set. Every control of the row starts on
		// "No change" (empty value) and an empty one is left out of the request, so
		// the campaign keeps what it had. Posting all of them is what made a bulk
		// edit overwrite the options nobody touched.
		var $changed = 0;
		var $data = {
			action: 'manage_wpematico_save_bulk_edit',
			post_ids: $post_ids,
			wpnonce: wpematico_object.campaigns_list_nonce
		};

		$.each([
			'campaign_max',
			'campaign_author',
			'campaign_commentstatus',
			'campaign_customposttype',
			'campaign_posttype',
			'campaign_post_format',
			'campaign_feeddate',
			'campaign_allowpings',
			'campaign_linktosource',
			'campaign_strip_links'
		], function (i, field) {
			var $field = $bulk_row.find('[name="' + field + '"]');
			if (!$field.length) {
				return;
			}
			var value = $field.val();
			if (value === undefined || value === null || value === '') {
				return;
			}
			$data[field] = value;
			$changed++;
		});

		// Every control still on "No change": there is nothing of ours to save, so the
		// request is not made at all and the submit goes on as WordPress meant it to.
		if (!$changed) {
			return;
		}

		e.preventDefault();

		// save the data
		$.ajax({
			url: ajaxurl,
			type: 'POST',
 			cache: false,
			data: $data,
			success: function(response) {
				// Handle 200 OK
				// Optionally update UI or trigger events here
				location.reload();
			},
			error: function(xhr) {
				$('#bulk-edit .inline-edit-status').remove();
				if (xhr.responseJSON && xhr.responseJSON.data) {
					// Show the error message sent with wp_send_json_error
					$('#bulk-edit .inline-edit-save').prepend('<div class="inline-edit-status error"><p>' + xhr.responseJSON.data + '</p></div>');
				}
			}
		});
		
	});
	
})(jQuery);