<?php
// don't load directly 
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

$helpcampaignlist = array( 
	'Campaigns List' => array( 
		'columns' => array( 
			'title' => __('Columns.', 'wpematico' ),
			'tip' => '<b>'.__('Campaign Name', 'wpematico' ).'</b>: '.__('The name you gave it. Hover to show the quick actions underneath.', 'wpematico' ).'<br>'.
				'<b>'.__('Publish as', 'wpematico' ).'</b>: '.__('The post type and status every new entry is published with.', 'wpematico' ).'<br>'.
				'<b>'.__('Campaign Type', 'wpematico' ).'</b>: '.__('Useful when add-ons give you more campaign types. You can hide this column in Screen Options.', 'wpematico' ).'<br>'.
				'<b>'.__('Current State', 'wpematico' ).'</b>: '.__('Buttons to run, activate or stop the campaign. The colours show its state.', 'wpematico' ).'<br>'.
				'<b>'.__('Last Run', 'wpematico' ).'</b>: '.__('When the campaign last ran and how many seconds it took. An activated campaign also shows when it runs next.', 'wpematico' ).'<br>'.
				'<b>'.__('Posts', 'wpematico' ).'</b>: '.__('How many posts this campaign has published since it started, or since the last Reset.', 'wpematico' ).'<br>',
		),
	),
	'Quick Actions' => array( 
		'actions' => array( 
			'title' => __('Quick Actions in every Row.', 'wpematico' ),
			'tip' => 
				'<b>'.__('Edit', 'wpematico' ).'</b>: '.__('Opens the campaign, the same as clicking its name.', 'wpematico' ).'<br>'.
				'<b>'.__('Quick Edit', 'wpematico' ).'</b>: '.__('Change the main fields without opening the campaign editor.', 'wpematico' ).'<br>'.
				'<b>'.__('Trash', 'wpematico' ).'</b>: '.__('Send the campaign to the trash.', 'wpematico' ).'<br>'.
				'<b>'.__('Copy', 'wpematico' ).'</b>: '.__('Creates a new campaign with every field of this one. Its title gets "(copy)" added.', 'wpematico' ).'<br>'.
				'<b>'.__('Reset', 'wpematico' ).'</b>: '.__('Clears the log, the last run data and the post count of the campaign.', 'wpematico' ).'<br>'.
				'<b>'.__('Del Hash', 'wpematico' ).'</b>: '.__('Every campaign saves a hash of the last feed item it fetched, to avoid duplicates. This deletes it, so those items can be fetched again.', 'wpematico' ).'<br>'.
				'<b>'.__('See Log', 'wpematico' ).'</b>: '.__('Opens the log of the last run in a new window. No older logs are kept.', 'wpematico' ).'<br>'.
				'<b>'.__('Export', 'wpematico' ).'</b>: '.__('Professional feature. Exports this campaign to a file you can import into another WordPress site.', 'wpematico' ).'<br>',
		),
	),
	'Run Selected Campaigns' => array( 
		'run_selected' => array( 
			'title' => __('Run Multiple Campaigns at once.', 'wpematico' ),
			'tip' => 
				__('Tick the checkbox in the first column of each campaign you want to run.', 'wpematico' ).'<br>'.
				__('The orange button starts the run, which can take a long time.', 'wpematico' ).'<br>'.
				__('Each campaign reports at the top of the page, and the Posts column turns red when the run published something.', 'wpematico' ).'<br>',
		),
	),
	'Bulk Actions' => array( 
		'bulk_actions' => array( 
			'title' => __('Bulk actions.', 'wpematico' ),
			'tip' => 
				__('Bulk Edit changes the main fields of every campaign you select at once.', 'wpematico' ).'<br>'.
				__('"Send to Trash" removes all the selected campaigns in one go.', 'wpematico' ).'<br>'.
				__('Select the campaigns, choose the action and click Apply.', 'wpematico' ),
		),
	),
);
$helpcampaignlist = apply_filters('wpematico_help_campaign_list', $helpcampaignlist);

$screen = $current_screen; //WP_Screen::get('wpematico_page_wpematico_settings ');
foreach($helpcampaignlist as $key => $section){
	$tabcontent = '';
	foreach($section as $section_key => $sdata){
		$helptip[$section_key] = htmlentities($sdata['tip']);
		$tabcontent .= '<p><strong>' . $sdata['title'] . '</strong><br />'.
				$sdata['tip'] . '</p>';
		$tabcontent .= (isset($sdata['plustip'])) ?	'<p>' . $sdata['plustip'] . '</p>' : '';
	}
	$screen->add_help_tab( array(
		'id'	=> $key,
		'title'	=> $sdata['title'],
		'content'=> $tabcontent,
	) );
}
