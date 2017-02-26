jQuery(document).ready(function($){

	
	jQuery('#campaign_striphtml').change(function() {
		if (jQuery('#campaign_striphtml').is(':checked')) {
			jQuery('#campaign_strip_links').attr('checked', false);
			jQuery('#div_campaign_strip_links_options').fadeOut();
			jQuery('#div_campaign_strip_links').fadeOut();
		} else {
			jQuery('#div_campaign_strip_links').fadeIn();
		}
	});
	jQuery('#campaign_enable_template').change(function() {
		if (jQuery('#campaign_enable_template').is(':checked')) {
			jQuery('#postemplatearea').fadeIn();
		} else {
			jQuery('#postemplatearea').fadeOut();
		}
	});
	jQuery('#campaign_strip_links').change(function() {
		if (jQuery('#campaign_striphtml').is(':checked') && jQuery('#campaign_strip_links').is(':checked')) {
			jQuery('#campaign_strip_links').attr('checked', false);
			jQuery('#div_campaign_strip_links_options').fadeOut();
			return false;
		}
		if (jQuery('#campaign_strip_links').is(':checked')) {
			jQuery('#div_campaign_strip_links_options').fadeIn();
		} else {
			jQuery('#div_campaign_strip_links_options').fadeOut();
		}

	});

	jQuery('#campaign_linktosource').change(function() {
		if (jQuery('#campaign_linktosource').is(':checked')) {
			jQuery('#copy_permanlink_source').attr('checked', false);
		}
	});
	jQuery('#copy_permanlink_source').change(function() {
		if (jQuery('#copy_permanlink_source').is(':checked')) {
			jQuery('#campaign_linktosource').attr('checked', false);
		}
	});

	jQuery('#campaign_autocats').change(function(e) {
		if (jQuery('#campaign_autocats').is(':checked')) {
			jQuery('#autocats_container').fadeIn();
		} else {
			jQuery('#autocats_container').fadeOut();
		}
	});


});