jQuery(document).ready(function ($) {

	var icon_plus = 'dashicons-visibility';
	var icon_dismis = 'dashicons-hidden';

	//minimize action
	$(".icon-minimize-div").click(function () {
		target = $(this).data('target');
		$(target).fadeOut();
	});

	//dismiss action
	$(".icon-close-div").click(function () {
		$(this).parent().parent().slideUp(500);
		wpematico_close_notification();
	});
});

function wpematico_close_notification() {
	var data = {
		'action': 'wpematico_close_notification',
	};
	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	jQuery.post(ajaxurl, data, function (response) {
		//response
	});
}
