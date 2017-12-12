jQuery(document).ready(function($){
	
	$('button.dashicons-controls-play').click(function() {
		if (!wpematico_preview.is_manual_addon_active) {
			alert(wpematico_preview.is_manual_addon_msg);
		}
	});
});