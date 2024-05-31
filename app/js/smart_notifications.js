jQuery(document).ready(function ($) {

	var icon_plus = 'dashicons-visibility';
	var icon_dismis = 'dashicons-hidden';

	//close action (hide)
	$(".wpematico-smart-notification .icon-close-div").on("click", function () {
		$('.wpematico-smart-notification .notification-title').text( wpematico_object.Notification_Hidding );
		$(this).parent().parent().slideUp(500);
	});

	//dismiss action wprate
	$("#smart-notification-rate .icon-dismiss-div").on("click", function () {
		$('.wpematico-smart-notification .notification-title').text(' *****  *****  ***** '+ wpematico_object.Notification_Dismissed+' *****  *****  ***** ');
		$(this).parent().parent().slideUp(500);
		wpematico_dismiss_notice('wprate');
	});

	//dismiss action 
	$("#smart-notification-wizard .icon-dismiss-div").on("click", function () {
		$('.wpematico-smart-notification .notification-title').text(' *****  *****  ***** '+ wpematico_object.Notification_Dismissed+' *****  *****  ***** ');
		$(this).parent().parent().slideUp(500);
		wpematico_dismiss_notice('wizard');
	});

	//dismiss action MDM
	$("#smart-notification-mdm .icon-dismiss-div").click(function () {
		//$('.wpematico-smart-notification .notification-title').text(' *****  *****  ***** '+ wpematico_object.Notification_Dismissed+' *****  *****  ***** ');
		$(this).parent().parent().slideUp(500);
		wpematico_dismiss_notice('mdm');
	});
});

function wpematico_dismiss_notice($notify_id) {
switch($notify_id) {
  case 'wprate':
    var data = {'action': 'wpematico_dismiss_wprate_notice'};
    break;
  case 'wizard':
	  var data = {'action': 'wpematico_dismiss_wizard_notice'};
    break;
  case 'mdm':
	  var data = {'action': 'wpematico_dismiss_mdm_notice'};
    break;
  default:
	  //No AJAX
	  var data = {'action': ''};
}

	jQuery.post(ajaxurl, data, function (response) {
		//response
	});
}
