jQuery(document).ready(function($){

	
	jQuery('#delete_all').click(function() {
		jQuery('input[name="post_status"]').val('trash');
	});
	
});