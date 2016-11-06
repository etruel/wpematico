jQuery(document).ready(function($){
	jQuery('.campaign_customposttype').change(function(e){
		var id = jQuery(this).data('id');
		var post_type = jQuery(this).val();
		
		var data_send = {
			action: 'get_wpematico_quick_categories',
			campaign_id: id,
			campaign_post_type: post_type
			
		}
		
		jQuery('#categories_div_'+id).html('Loading..');
		jQuery.post(wpematico_list.ajax_url, data_send, function( data ) {
			jQuery('#categories_div_'+data_send.campaign_id).html(data);
		});
	});
});