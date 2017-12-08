jQuery(document).ready(function($){
	
	$("#cb-select-all-1, #cb-select-all-2").change(function() {
		var is_checking = $(this).is(':checked');
		$("input[name='item[]']").each(function() {
			$(this).attr('checked', is_checking);
		});
	});

	$("input[name='item[]']").change(function() {
		$("#cb-select-all-1, #cb-select-all-2").attr('checked', false);

	});
	

});