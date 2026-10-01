jQuery(document).ready(function ($) {

	$('#campaign_vimeo_sizes').on('change', function () {
		$('#div_campaign_vimeo_sizes').toggle($(this).is(':checked'));
	});

	$('#campaign_vimeo_ign_image').on('change', function () {
		$('#div_vimeo_img_feature').toggle($(this).is(':checked'));
	});

	// The item's permalink is its Vimeo page, so copying it as the post's permalink
	// is meaningless here. Kept in sync with the field, which is forced off on save.
	//
	// Deferred by a tick on purpose: core's campaign_edit.js answers the same event
	// and re-enables the checkbox for every type that is not youtube, so running
	// inside the handler chain means whichever bound last wins. When YouTube moves
	// out to its own campaign type file, both should declare this instead and core's
	// script should read the list.
	function toggleCopyPermalink() {
		var isVimeo = $('#campaign_type').val() === 'vimeo';
		if (isVimeo) {
			$('#copy_permanlink_source').prop('checked', false);
		}
		$('#copy_permanlink_source').prop('disabled', isVimeo);
	}
	$('#campaign_type').on('change', function () {
		window.setTimeout(toggleCopyPermalink, 0);
	});
	toggleCopyPermalink();

	$('#campaign_vimeo_ign_image, #campaign_vimeo_image_only_featured, #campaign_vimeo_ign_description').on('change', function () {
		var hideImage = $('#campaign_vimeo_ign_image').is(':checked'),
			onlyFeatured = $('#campaign_vimeo_image_only_featured').is(':checked');

		$('.wpe-vimeo-featured, #wpe-vimeo-title-featured').toggle(!hideImage || onlyFeatured);
		$('.wpe-vimeo-image').toggle(!hideImage);
		$('.wpe-vimeo-description').toggle(!$('#campaign_vimeo_ign_description').is(':checked'));
	});

});
