jQuery(document).ready(function($){
	
	$('.item_fetch').click(function() {
		if (!wpematico_preview.is_manual_addon_active) {
			alert(wpematico_preview.is_manual_addon_msg);
		}
	});
});