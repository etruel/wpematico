<?php 
// don't load directly 
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( class_exists( 'WPeMatico_Campaign_edit_functions' ) ) return;

class WPeMatico_Campaign_edit_functions {

	public static function create_meta_boxes() {
		global $post, $current_screen, $campaign_data, $cfg,$helptip;
		require( dirname( __FILE__ ) . '/campaign_help.php' );
		$campaign_data = WPeMatico :: get_campaign ($post->ID);
//		$campaign_data = apply_filters('wpematico_check_campaigndata', $campaign_data);
		$cfg = get_option(WPeMatico :: OPTION_KEY);
		$cfg = apply_filters('wpematico_check_options', $cfg);

		do_action('wpematico_create_metaboxes_before', $campaign_data, $cfg); 
	//	add_meta_box( $id, $title, $callback, $post_type, $context, $priority, $callback_args );
		add_meta_box( 'campaign_types', '<span class="dashicons dashicons-tickets-alt"> </span> '.__( 'Campaign Type','wpematico'),  array('WPeMatico_Campaign_edit', 'campaign_type_box'), 'wpematico', 'side', 'high' );
		if ( current_theme_supports( 'post-formats' ) )
		add_meta_box( 'post_format-box', '<span class="dashicons dashicons-format-status"> </span> '.__('Campaign Posts Format','wpematico'). '<span class="dashicons dashicons-warning help_tip" title-heltip="'.$helptip['postformat'].'" title="'.$helptip['postformat'].'"></span>', array( 'WPeMatico_Campaign_edit' ,'format_box'),'wpematico','side', 'default' );
		add_meta_box( 'category-box', '<span class="dashicons dashicons-category"> </span> '.__('Campaign Categories','wpematico'). '<span class="dashicons dashicons-warning help_tip"  title-heltip="'.$helptip['category'].'"   title="'. $helptip['category'].'"></span>', array( 'WPeMatico_Campaign_edit' ,'cat_box'),'wpematico','side', 'default' );
		add_meta_box( 'post_tag-box', '<span class="dashicons dashicons-tag"> </span> '.__('Tags generation', 'wpematico' ). '<span class="dashicons dashicons-warning help_tip" title-heltip="'.$helptip['tags'].'" title="'. $helptip['tags'].'"></span>', array(  'WPeMatico_Campaign_edit'  ,'tags_box' ),'wpematico','side', 'default' );
		add_meta_box( 'log-box', '<span class="dashicons dashicons-paperclip"> </span> '.__('Send log', 'wpematico' ). '<span class="dashicons dashicons-warning help_tip" title-heltip="'.$helptip['sendlog'].'"  title="'. $helptip['sendlog'].'"></span>', array(  'WPeMatico_Campaign_edit'  ,'log_box' ),'wpematico','side', 'default' );

		add_meta_box( 'feeds-box', '<span class="dashicons dashicons-rss"> </span> '.__('Feeds for this Campaign', 'wpematico' ). '<span class="dashicons dashicons-warning help_tip" title-heltip="'.$helptip['feeds'].'" title="'. $helptip['feeds'].'"></span>', array( 'WPeMatico_Campaign_edit'  ,'feeds_box' ),'wpematico','normal', 'default' );
		add_meta_box( 'youtube-box', '<span class="dashicons dashicons-video-alt3"> </span> '.__('YouTube feeds for this Campaign', 'wpematico' ). '<span class="dashicons dashicons-warning help_tip" title-heltip="'.$helptip['feed_url'].'" title="'. $helptip['feed_url'].'"></span>', array( 'WPeMatico_Campaign_edit'  ,'youtube_box' ),'wpematico','normal', 'high' );
		
		add_meta_box( 'bbpress-box', '<span class="dashicons dashicons-bbpress-logo"> </span> '.__('bbPress Forums Options', 'wpematico' ). '<span class="dashicons dashicons-warning help_tip" title-heltip="'.$helptip['bbpress'].'" title="'. $helptip['bbpress'].'"></span>', array( 'WPeMatico_Campaign_edit'  ,'bbpress_box' ),'wpematico','normal', 'high' );
		
		add_meta_box( 'xml-campaign-box', '<span class="dashicons dashicons-rss"> </span> '.__('XML Campaign Type', 'wpematico' ). '<span class="dashicons dashicons-warning help_tip" title-heltip="'.$helptip['XML Campaign Type Box'].'" title="'. $helptip['XML Campaign Type Box'].'"></span>', array( 'WPeMatico_XML_Importer'  ,'metabox' ),'wpematico','normal', 'high' );

		add_meta_box( 'options-box', '<span class="dashicons dashicons-admin-settings"> </span> '.__('Options for this campaign', 'wpematico' ), array(  'WPeMatico_Campaign_edit'  ,'options_box' ),'wpematico','normal', 'default' );
		add_meta_box( 'cron-box', '<span class="dashicons dashicons-backup"> </span> '.__('Schedule Cron', 'wpematico' ), array(  'WPeMatico_Campaign_edit'  ,'cron_box' ),'wpematico','normal', 'default' );
		add_meta_box( 'images-box', '<span class="dashicons dashicons-images-alt2"> </span> '.__('Options for images', 'wpematico' ). '<span class="dashicons dashicons-warning help_tip" title-heltip="'.$helptip['imgoptions'].'"  title="'. $helptip['imgoptions'].'"></span>', array(  'WPeMatico_Campaign_edit'  ,'images_box' ),'wpematico','normal', 'default' );
		add_meta_box( 'audios-box', '<span class="dashicons dashicons-media-audio"> </span> '.__('Options for audios', 'wpematico' ). '<span class="dashicons dashicons-warning help_tip" title-heltip="'.$helptip['audio_options'].'"  title="'. $helptip['audio_options'].'"></span>', array(  'WPeMatico_Campaign_edit'  ,'audio_box' ),'wpematico','normal', 'default' );
		add_meta_box( 'videos-box', '<span class="dashicons dashicons-media-video"> </span> '.__('Options for videos', 'wpematico' ). '<span class="dashicons dashicons-warning help_tip" title-heltip="'.$helptip['video_options'].'"  title="'. $helptip['video_options'].'"></span>', array(  'WPeMatico_Campaign_edit'  ,'video_box' ),'wpematico','normal', 'default' );

		add_meta_box( 'duplicate-box', '<span class="dashicons dashicons-feedback"> </span> '.__('Duplicate controls', 'wpematico' ). '<span class="dashicons dashicons-warning help_tip" title-heltip="'.$helptip['duplicate_options'].'"  title="'. $helptip['duplicate_options'].'"></span>', array(  'WPeMatico_Campaign_edit'  ,'duplicate_box' ),'wpematico','normal', 'default' );
		
		add_meta_box( 'template-box', '<span class="dashicons dashicons-layout"> </span> '.__('Post Template', 'wpematico' ). '<span class="dashicons dashicons-warning help_tip" title-heltip="'.$helptip['postemplate'].'" title="'. $helptip['postemplate'].'"></span>', array(  'WPeMatico_Campaign_edit'  ,'template_box' ),'wpematico','normal', 'default' );
		if ($cfg['enableword2cats'])   // Si está habilitado en settings, lo muestra 
		add_meta_box( 'word2cats-box', '<span class="dashicons dashicons-category"> </span> '.__('Word to Category options', 'wpematico' ). '<span class="dashicons dashicons-warning help_tip" title-heltip="'.$helptip['wordcateg'].'"   title="'. $helptip['wordcateg'].'"></span>', array(  'WPeMatico_Campaign_edit'  ,'word2cats_box' ),'wpematico','normal', 'default' );
		if ($cfg['enablerewrite'])   // Si está habilitado en settings, lo muestra 
		add_meta_box( 'rewrite-box', '<span class="dashicons dashicons-image-rotate-right"> </span> '.__('Rewrite options', 'wpematico' ). '<span class="dashicons dashicons-warning help_tip" title-heltip="'.$helptip['rewrites'].'" title="'. $helptip['rewrites'].'"></span>', array(  'WPeMatico_Campaign_edit'  ,'rewrite_box' ),'wpematico','normal', 'default' );
		//***** Call nonstatic
		// Publish Meta_box edited
		add_action('post_submitbox_start', array( __CLASS__ ,'post_submitbox_start'), 10, 0); 
		add_action('post_submitbox_start', array(__CLASS__, 'campaign_run_details'), 11, 0);
		//Feed URLs columns
		add_action('wpematico_campaign_feed_header_column', array(__CLASS__, 'headerfeedURL'),10 );
		add_action('wpematico_campaign_feed_body_column', array(__CLASS__, 'bodyfeedURL'),10,3 );
		//wizard script
		add_action('admin_footer',array(__CLASS__,'campaign_wizard'));
		do_action('wpematico_create_metaboxes',$campaign_data,$cfg); 

	}
	
	/**
	* Static function headerfeedURL
	* @access public
	* @return void
	* @since 2.1
	*/
	public static function headerfeedURL() {
		?><div class="feed_column"><?php _e('Feed URL', 'wpematico'  ) ?></div><?php
	}
	
	/**
	* Static function bodyfeedURL
	* @access public
	* @return void
	* @since 2.1
	*/
	public static function bodyfeedURL($feed, $cfg, $i ) {
		$feedViewer = admin_url("edit.php?post_type=wpematico&page=wpematico_tools&tab=tools&section=feed_viewer&feedlink=$feed");
		?>
		<div class="feed_column" id="">
			<input name="campaign_feeds[<?php echo $i; ?>]" type="text" value="<?php echo (isset($feed)) ? esc_url($feed) : '';  ?>" class="large-text feedinput"/> <a href="<?php echo esc_url("$feedViewer") ?>" style="margin-right:1.5rem; margin-top:0.15rem; color:black" title="<?php echo __('Feed Viewer', 'wpematico')?>" id="href-eye" target="_Blank" class="wpefeedlink"><span class="dashicons dashicons-visibility"></span></a><a href="<?php echo (isset($feed)) ? esc_url($feed) : '';  ?>" title="<?php _e('Open URL in a new browser tab', 'wpematico' ); ?>" target="_Blank" class="wpefeedlink"><span class="dashicons dashicons-external"></span></a>
		</div>
		<?php
	}
	
	/**
	* Static function submitbox
	* @access public
	* @return void
	* @since 1.8.0
	*/
	public static function campaign_run_details() {
		global $post, $campaign_data;
		$activated = (bool)$campaign_data['activated']; 
		$lastrun = get_post_meta($post->ID, 'lastrun', true);
		$lastrun = (isset($lastrun) && !empty($lastrun) ) ? $lastrun :  $campaign_data['lastrun']; 
		$lastruntime = (isset($campaign_data['lastruntime'])) ? $campaign_data['lastruntime'] : ''; 
		
		$postscount = get_post_meta($post->ID, 'postscount', true);
		$lastpostscount = get_post_meta($post->ID, 'lastpostscount', true);
		
		$starttime = (isset($campaign_data['starttime']) && !empty($campaign_data['starttime']) ) ? $campaign_data['starttime'] : 0 ; 
			//print_r($campaign_data);
			$activated = (bool)$campaign_data['activated']; 
			$atitle = ( $activated ) ? __('Stop and deactivate this campaign', 'wpematico') : __('Start/Activate Campaign Scheduler', 'wpematico');
			if ($starttime>0) {  // Running play verde & grab rojo & stop gris
				$runtime=current_time('timestamp')-$starttime;

				$lbotones = '<button type="button" disabled class="cpanelbutton dashicons dashicons-controls-play green"></button>';
				if ($activated) { // Active play green & grab rojo & stop gris
					$lbotones.= '<button type="button" disabled class="cpanelbutton dashicons dashicons-update red"></button>'; // To activate
				} else {  // Inactive play verde & grab black & stop grey
					$lbotones.= '<button type="button" class="cpanelbutton dashicons dashicons-update" btn-href="'.WPeMatico_Campaigns::wpematico_action_link( $post->ID , 'display','toggle').'&campaign_edit=true" title="' . $atitle . '"></button>'; // To activate
				}
//				$lbotones.= "<span class='cpanelbutton stop grey'></span>"; // To stop				
				$lbotones.= '<button type="button" class="cpanelbutton dashicons dashicons-controls-pause" btn-href="'.WPeMatico_Campaigns::wpematico_action_link( $post->ID , 'display','clear').'&campaign_edit=true" title="' . __('Break fetching and restore campaign', 'wpematico') . '"></button>'; // To deactivate
				
			}elseif ($activated) { // Running play gris & grab rojo & stop gris
				$lbotones = '<button type="button" class="cpanelbutton dashicons dashicons-controls-play" id="run_now" title="' . esc_attr(__('Run Once', 'wpematico')) . '"></button>';// To run now
				$lbotones.= '<button type="button" disabled class="cpanelbutton dashicons dashicons-update red"></button>'; // To stop
				$lbotones.= '<button type="button" class="cpanelbutton dashicons dashicons-controls-pause" btn-href="'.WPeMatico_Campaigns::wpematico_action_link( $post->ID , 'display','toggle').'&campaign_edit=true" title="' . $atitle . '"></button>'; // To deactivate
				
			} else {  // Inactive play gris & grab gris & stop black
				$lbotones = '<button type="button" class="cpanelbutton dashicons dashicons-controls-play" id="run_now" title="' . esc_attr(__('Run Once', 'wpematico')) . '"></button>';// To run now
				$lbotones.= '<button type="button" class="cpanelbutton dashicons dashicons-update" btn-href="'.WPeMatico_Campaigns::wpematico_action_link( $post->ID , 'display','toggle').'&campaign_edit=true" title="' . $atitle . '"></button>'; // To activate
				$lbotones.= '<button type="button" disabled class="cpanelbutton dashicons dashicons-controls-pause grey"></button>'; // To stop
				
			}
		?>
		<div class="wpematico_campaign_details postbox">
			<div class="feed_header">Campaign Control Panel</div>
			<table class="table_wpematico_details">
				<?php 
				if ($activated) : 
					$cronnextrun = WPeMatico :: time_cron_next($campaign_data['cron']);
					$cronnextrun = (isset($cronnextrun) && !empty($cronnextrun) && ($cronnextrun > 0 ) ) ? $cronnextrun : $campaign_data['cronnextrun']; 
				?>
				<tr>
					<td><?php _e('Next Run:', 'wpematico'); ?></td>
					<td><b class="red"><?php echo date_i18n( get_option('date_format').' '. get_option('time_format'), $cronnextrun ); ?></b></td>
				</tr>
				<?php endif; ?>
				<?php 
				if ($lastrun) : ?>
				<tr>
					<td><?php _e('Last Runtime:', 'wpematico'); ?></td>
					<td><?php echo date_i18n( get_option('date_format').' '. get_option('time_format'), $lastrun ); ?></td>
				</tr>
				<tr>
					<td><?php _e('Taken time:', 'wpematico'); ?></td>
					<td><span id="lastruntime"><?php echo $lastruntime; ?></span> <?php _e('sec.', 'wpematico' ); ?></td>
				</tr>
				<?php endif; ?>
				<tr>
					<td><?php _e('Last Fetched:', 'wpematico'); ?></td>
					<td><?php echo (isset($lastpostscount) && !empty($lastpostscount) ) ? $lastpostscount : $campaign_data['lastpostscount']; ?></td>
				</tr>
				<tr>
					<td><?php _e('Fetched Totals:', 'wpematico'); ?></td>
					<td><?php echo (isset($postscount) && !empty($postscount) ) ? $postscount : $campaign_data['postscount']; ?></td>
				</tr>
				<?php do_action('wpematico_table_details'); ?>
			</table>
			<table class="wpematico_current_state">
				<tr>
					<td>
					<div id="wpematico_current_state_actions">
						<?php echo $lbotones; ?>
						<button type="button" class="cpanelbutton dashicons dashicons-backup" id="campaign_edit_reset" btn-action='<?php echo WPeMatico_Campaigns::wpematico_action_link( $post->ID , 'display','reset'); ?>&campaign_edit=true' title="<?php _e('Reset post count', 'wpematico'); ?>"></button>
						<button type="button" class="cpanelbutton dashicons dashicons-editor-unlink" id="campaign_edit_del_hash" btn-action='<?php echo WPeMatico_Campaigns::wpematico_action_link( $post->ID , 'display','delhash'); ?>&campaign_edit=true' title="<?php _e('Delete hash code for duplicates', 'wpematico'); ?>"></button>
						<button type="button" class="cpanelbutton dashicons dashicons-clipboard" id="campaign_edit_see_logs" title="<?php _e('See last log. (Open a PopUp window)', 'wpematico'); ?>"></button>
						<button type="button" class="cpanelbutton dashicons dashicons-visibility" id="campaign_edit_preview" title="<?php _e('Preview Campaign', 'wpematico'); ?>"></button>
						<?php do_action('wpematico_current_state_actions'); ?>
					</div>
					</td>
				</tr>
			</table>
			<div class="cpanel-footer"><div id="cpanelnotebar"> </div></div>
		</div>
		
		<?php
	}
	
		//*************************************************************************************
	static function campaign_type_box() {
		global $post, $campaign_data;
		$options = self::campaign_type_options();
		$readonly = ( count($options) == 1 ) ? 'disabled' : '';			
		$echoHtml = '<select id="campaign_type" '.$readonly.' name="campaign_type" style="display:inline;">';
		foreach($options as $key => $option) {
			$echoHtml .= '<option value="'.$option["value"].'"'.  selected( $option["value"], $campaign_data["campaign_type"], false ).'>'.$option["text"].'</option>';
		}
		$echoHtml .= '</select>';

		echo $echoHtml;
	}
	static function campaign_type_options() {
		$options=array(
			array( 'value'=> 'feed', 'text' => __('Feed Fetcher (Default)', 'wpematico' ), "show"=>array('feeds-box', 'audios-box','videos-box','cron-box','template-box', 'images-box') ),
			array( 'value'=> 'youtube','text' => __('You Tube Fetcher', 'wpematico' ), "show"=>array('feeds-box','youtube-box', 'audios-box','videos-box', 'cron-box','template-box', 'images-box') ),
			array( 'value'=> 'bbpress','text' => __('bbPress Forums', 'wpematico' ), "show"=>array('feeds-box','bbpress-box', 'audios-box','videos-box','cron-box','template-box', 'images-box') ),
			array( 'value'=> 'xml','text' => __('XML Campaign Type', 'wpematico' ), "show"=>array('xml-campaign-box', 'audios-box','videos-box','cron-box','template-box', 'images-box') ),
			);
		$options = apply_filters('wpematico_campaign_type_options', $options);

		return $options;
	}	
	static function get_campaign_type_by_field($value, $field='value', $return='text') {
		$options =  self::campaign_type_options();
		foreach($options as $key => $option) {
			if ($option[$field]==$value) {
				if($return=='key') return $key;
				else return $option[$return];
			}
		}
		return FALSE;
	}	
	
	
		//*************************************************************************************
	public static function format_box( $post, $box ) {
		global $post, $campaign_data, $helptip;
		if ( current_theme_supports( 'post-formats' ) ) :
			$post_formats = get_theme_support( 'post-formats' );
		$campaign_post_format = $campaign_data['campaign_post_format'];

		if ( is_array( $post_formats[0] ) ) :
			$campaign_post_format = ( @!$campaign_post_format )? '0' : $campaign_data['campaign_post_format'];
		?>
		<div id="post-formats-select">
			<input type="radio" name="campaign_post_format" class="post-format" id="post-format-0" value="0" <?php checked( $campaign_post_format, '0' ); ?> /> <label for="post-format-0" class="post-format-icon post-format-standard"><?php echo get_post_format_string( 'standard' ); ?></label>
			<?php foreach ( $post_formats[0] as $format ) : ?>
				<br /><input type="radio" name="campaign_post_format" class="post-format" id="post-format-<?php echo esc_attr( $format ); ?>" value="<?php echo esc_attr( $format ); ?>" <?php checked( $campaign_post_format, $format ); ?> /> <label for="post-format-<?php echo esc_attr( $format ); ?>" class="post-format-icon post-format-<?php echo esc_attr( $format ); ?>"><?php echo esc_html( get_post_format_string( $format ) ); ?></label>
			<?php endforeach; ?><br />
		</div>
	<?php endif; endif;
}

		//************************************************************************************* 
public static function rewrite_box( $post ) { 
	global $post, $campaign_data, $helptip;
	$campaign_rewrites = $campaign_data['campaign_rewrites'];
	?>
	<p class="he20">
		<span class="left"><?php _e('Replaces words or phrases by other that you want or turns into link.', 'wpematico' ) ?></span>
	</p>
	<div id="rewrites_edit" class="inlinetext">		
		<?php for ($i = 0; $i < count($campaign_rewrites['origin']); $i++) : ?>			
			
				<div class="jobtype-select p7 rewrite-row" id="nuevorew" style="display: block;">
					<div id="rw1" class="wi28-inline left-important p4">
						<div>
							<span class="left-important"><?php _e('Origin:','wpematico') ?>&nbsp;&nbsp;</span>
							<label class="left-important"><input name="campaign_word_option_title[<?php echo $i; ?>]" class="campaign_word_option_title" class="checkbox" value="1" type="checkbox"<?php checked($campaign_rewrites['title'][$i],true) ?> onclick="relink=jQuery(this).parent().parent().children('#rw3');if(true==jQuery(this).is(':checked')) relink.fadeOut(); else relink.fadeIn();"/><?php _e('Title','wpematico') ?></label>
							&nbsp;<label class="left-important"><input name="campaign_word_option_regex[<?php echo $i; ?>]" class="campaign_word_option_regex" class="checkbox" value="1" type="checkbox"<?php checked($campaign_rewrites['regex'][$i],true) ?> /><?php _e('RegEx','wpematico') ?></label>
						</div>
						<textarea class="large-text he35 campaign_word_origin" name="campaign_word_origin[<?php echo $i; ?>]" /><?php echo stripslashes($campaign_rewrites['origin'][$i]) ?></textarea>
					</div>
					<div class="wi28-inline left-important p4">
						<?php _e('Rewrite to:','wpematico') ?>
						<textarea class="large-text he35" id="campaign_word_rewrite" name="campaign_word_rewrite[<?php echo $i; ?>]" /><?php echo stripslashes($campaign_rewrites['rewrite'][$i]) ?></textarea>
					</div>
					<div id="rw3" class="wi28-inline left-important p4">
						<?php _e('ReLink to:','wpematico') ?>
						<textarea class="large-text he35" id="campaign_word_relink" name="campaign_word_relink[<?php echo $i; ?>]" /><?php echo stripslashes($campaign_rewrites['relink'][$i]) ?></textarea>
					</div>
					<div class="rowactions-rewrite">
						<span class="" id="w2cactions">
							<label title="<?php _e('Delete this item', 'wpematico' ); ?>" onclick=" jQuery(this).parent().parent().parent().children('#rw1').children('.campaign_word_origin').text(''); jQuery(this).parent().parent().parent().fadeOut();  disable_run_now();" class="bicon delete left"></label>
						</span>
					</div>
				</div>

	<?php endfor ?>
	<input id="rew_max" value="<?php echo $i-1; ?>" type="hidden" name="rew_max">

</div>
<div class="clear"></div>
<div id="paging-box">
	<a href="JavaScript:void(0);" class="button-primary add" id="addmorerew" style="font-weight: bold; text-decoration: none;"> <?php _e('Add more', 'wpematico' ); ?>.</a>
</div>

<?php 
}

	//**************************************************************************
public static function word2cats_box( $post ) {
	global $post, $campaign_data, $helptip;
	$campaign_wrd2cat = $campaign_data['campaign_wrd2cat'];
	$campaign_w2c_only_use_a_category = $campaign_data['campaign_w2c_only_use_a_category']; 
	$campaign_w2c_the_category_most_used = $campaign_data['campaign_w2c_the_category_most_used']; 
	?>
	<p class="he20">
		<span class="left"><?php _e('Assigning categories based on content words.', 'wpematico') ?></span> 
	</p>
	<br/>

	<input name="campaign_w2c_only_use_a_category" id="campaign_w2c_only_use_a_category" class="checkbox" value="1" type="checkbox" <?php checked($campaign_w2c_only_use_a_category, true); ?> />
	<label for="campaign_w2c_only_use_a_category"><?php _e('Only assign one category to each post.', 'wpematico' ); ?></label>
	<!-- <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['audio_cache']; ?>"></span> -->
	<div id="div_campaign_w2c_only_use_a_category" style="margin-left: 20px; <?php if (!$campaign_w2c_only_use_a_category) echo 'display:none;';?>">
			
			<p>
				<input name="campaign_w2c_the_category_most_used" id="campaign_w2c_the_category_most_used" class="checkbox left" value="1" type="checkbox" <?php checked($campaign_w2c_the_category_most_used,true); ?> />
				<label for="campaign_w2c_the_category_most_used"><?php _e('The category of the word most counted in the content. Deactivate to use the first word found.', 'wpematico' ); ?></label> <!-- <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['audio_cache']; ?>"></span> -->
			</p>

	</div>

	<br/>	
	<div id="wrd2cat_edit" class="inlinetext">		
		<?php foreach ($campaign_wrd2cat['word'] as $i => $value) :  ?>

			<div id="w2c_ID<?php echo $i; ?>" class="row_word_to_cat">
				<div class="pDiv jobtype-select p7" id="nuevow2c">
					<div id="w1">
						<label><?php _e('Word:', 'wpematico') ?> <input type="text" size="25" class="regular-text" id="campaign_wrd2cat" name="campaign_wrd2cat[word][<?php echo $i; ?>]" value="<?php echo stripslashes(htmlspecialchars_decode(@$campaign_wrd2cat['word'][$i])); ?>" /></label><br />
						<label><input name="campaign_wrd2cat[title][<?php echo $i; ?>]" id="campaign_wrd2cat_title" class="checkbox w2ctitle" value="1" type="checkbox"<?php checked($campaign_wrd2cat['title'][$i],true) ?> /><?php _e('on Title', 'wpematico'); ?>&nbsp;&nbsp;</label>
						<label><input name="campaign_wrd2cat[regex][<?php echo $i; ?>]" id="campaign_wrd2cat_regex" class="checkbox w2cregex" value="1" type="checkbox"<?php checked($campaign_wrd2cat['regex'][$i],true) ?> /><?php _e('RegEx', 'wpematico'); ?>&nbsp;&nbsp;</label>
						<label><input <?php echo ($campaign_wrd2cat['regex'][$i]) ? 'disabled' : '';?> name="campaign_wrd2cat[cases][<?php echo $i; ?>]" id="campaign_wrd2cat_cases" class="checkbox w2ccases" value="1" type="checkbox"<?php checked($campaign_wrd2cat['cases'][$i],true) ?> /><?php _e('Case sensitive', 'wpematico'); ?>&nbsp;&nbsp;</label>
					</div>
					<div id="c1">
						<?php _e('To Category:', 'wpematico'); echo ' ';
						wp_dropdown_categories( array(
											'show_option_all'    => '',
											'show_option_none'   => __('Select category', 'wpematico' ),
											'hide_empty'         => 0, 
											'child_of'           => 0,
											'exclude'            => '',
											'echo'               => 1,
											'selected'           => $campaign_wrd2cat['w2ccateg'][$i],
											'hierarchical'       => 1, 
											'name'               => 'campaign_wrd2cat[w2ccateg]['.$i.']',
											'class'              => 'form-no-clear',
											'id'           		 => 'campaign_wrd2cat_category_'.$i,
											'hide_if_empty'      => false
										));
						?>
					</div>
					<span class="wi10" id="w2cactions">
						<label title="<?php _e('Delete this item', 'wpematico'); ?>"  class="bicon delete left btn_delete_w2c"></label>
					</span>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<input id="wrd2cat_max" value="<?php echo $i; ?>" type="hidden" name="wrd2cat_max">
	<div class="clear"></div>
	<div id="paging-box">
		<a href="#" class="button-primary add" id="addmorew2c"> <?php _e('Add more', 'wpematico'); ?></a>
	</div>

	
	<br/>
	<?php 
}


	//*************************************************************************************
public static function template_box( $post ) { 
	global $post, $campaign_data, $cfg, $helptip;
		/**
		 * An action to allow Addons inserts fields before the post template textarea
		 */
		do_action('wpematico_before_template_box',$post, $cfg);
		/**
		 * 
		 */
		
		$campaign_enable_template = $campaign_data['campaign_enable_template'];
		$campaign_template = $campaign_data['campaign_template'];
		//$cfg = get_option(WPeMatico :: OPTION_KEY);
		?>
		<p class="he20"><b><?php _e('Modify, manage or add extra content to every post fetched.', 'wpematico' ) ?></b></p>
		<div id="wpe_post_template_edit" class="inlinetext" style="background: #c1fefe;;padding: 0.5em;">
			<label for="campaign_enable_template">
				<input name="campaign_enable_template" id="campaign_enable_template" class="checkbox" value="1" type="checkbox"<?php checked($campaign_enable_template,true) ?> /> <?php _e('Use custom posts template', 'wpematico' ) ?>
			</label>
			<div id="postemplatearea" style="<?php echo (checked($campaign_enable_template,true))?'':'display:none'; ?>">
				<textarea class="widefat" rows="5" id="campaign_template" name="campaign_template" /><?php echo stripslashes($campaign_template) ?></textarea><br/>
				<span class="description"><?php _e('{content} must exist in the template if you want to see the content in your post. Works after the features above.', 'wpematico' ); ?></span>
				<p class="he20" id="tags_note" class="note left"><?php _e('Allowed tags', 'wpematico' ); ?>: </p>
				<p id="tags_list" style="border-left: 3px solid #EEEEEE; color: #999999; font-size: 11px; padding-left: 6px;margin-top: 0;">
					<?php
						$tags_array = array();
						$tags_array[] = '{title}';
						$tags_array[] = '{content}';
						$tags_array[] = '{itemcontent}';
						$tags_array[] = '{image}';
						$tags_array[] = '{author}';
						$tags_array[] = '{authorlink}';
						$tags_array[] = '{permalink}';
						$tags_array[] = '{feedurl}';
						$tags_array[] = '{feedtitle}';
						$tags_array[] = '{feeddescription}';
						$tags_array[] = '{feedlogo}';
						$tags_array[] = '{feedfavicon}';
						$tags_array[] = '{campaigntitle}';
						$tags_array[] = '{campaignid}';
						$tags_array[] = '{item_date}';
						$tags_array[] = '{item_time}';
						$tags_on_campaign_edit = apply_filters('wpematico_template_tags_campaign_edit', $tags_array);
						foreach ($tags_on_campaign_edit as $tag) {
							echo '<span class="tag">'.$tag.'</span>';
							$array_slice = (array_slice($tags_on_campaign_edit, -1));
							$lastEl = array_pop($array_slice);
							if ($tag != $lastEl) {
								echo ', ';
							}
						}
					?>
					
				</p>
			</div>
			<p><a href="javascript:void(0);" title="<?php _e('Click to Show/Hide the examples', 'wpematico' ); ?>" onclick="jQuery('#tags_note,#tags_list').fadeToggle('fast'); jQuery('#tags_list_det').fadeToggle();" class="m4">
				<?php _e('Click here to see more info of the template feature.','wpematico'); ?>
			</a>
		</p>
		<div id="tags_list_det" style="display: none;">
			<b><?php _e('Supported tags', 'wpematico' ); ?></b>
			<p><?php _e('A tag is a piece of text that gets replaced dynamically when the post is created. Currently, these tags are supported:', 'wpematico' ); ?></p>
			<ul style='list-style-type: square;margin:0 0 5px 20px;font:0.92em "Lucida Grande","Verdana";'>
				<li><strong class="tag">{title}</strong> <?php _e('The feed item title.', 'wpematico' ); ?> </li>
				<li><strong class="tag">{content}</strong> <?php _e('The parsed post content.', 'wpematico' ); ?> </li>
				<li><strong class="tag">{itemcontent}</strong> <?php _e('The feed item description.', 'wpematico' ); ?> </li>
				<li><strong class="tag">{image}</strong> <?php _e('Put the featured image on content.', 'wpematico' ); ?> </li>
				<li><strong class="tag">{author}</strong> <?php _e('The feed item author.', 'wpematico' ); ?> </li>
				<li><strong class="tag">{authorlink}</strong> <?php _e('The feed item author link (If exist).', 'wpematico' ); ?> </li>
				<li><strong class="tag">{permalink}</strong> <?php _e('The feed item permalink.', 'wpematico' ); ?> </li>
				<li><strong class="tag">{feedurl}</strong> <?php _e('The feed URL.', 'wpematico' ); ?> </li>
				<li><strong class="tag">{feedtitle}</strong> <?php _e('The feed title.', 'wpematico' ); ?> </li>
				<li><strong class="tag">{feeddescription}</strong> <?php _e('The description of the feed.', 'wpematico' ); ?> </li>
				<li><strong class="tag">{feedlogo}</strong> <?php _e('The feed\'s logo image URL.', 'wpematico' ); ?> </li>
				<li><strong class="tag">{feedfavicon}</strong> <?php _e('The feed\'s Favicon URL.', 'wpematico' ); ?> </li>
				<li><strong class="tag">{campaigntitle}</strong> <?php _e('This campaign title', 'wpematico' ); ?> </li>
				<li><strong class="tag">{campaignid}</strong> <?php _e('This campaign ID.', 'wpematico' ); ?> </li>
				<li><strong class="tag">{item_date}</strong> <?php _e('The date of the post item.', 'wpematico' ); ?> </li>
				<li><strong class="tag">{item_time}</strong> <?php _e('The time of the post item.', 'wpematico' ); ?> </li>
				<?php do_action('wpematico_print_template_tags', $campaign_data); ?>
			</ul>
			<p><b><?php _e('Examples:', 'wpematico' ); ?></b></p>
			<div id="tags_list_examples" style="display: block;">
				<span><?php _e('If you want to add a link to the source at the bottom of every post and the author, the post template would look like this:', 'wpematico' ); ?></span>
				<div class="code">{content}<br>&lt;a href="{permalink}"&gt;<?php _e('Go to Source', 'wpematico' ); ?>&lt;/a&gt;&lt;br /&gt;<br>Author: {author}</div>
				<p><em>{content}</em> <?php _e('will be replaced with the feed item content', 'wpematico' ); ?>, <em>{permalink}</em> <?php _e('by the source feed item URL, which makes it a working link and', 'wpematico' ); ?> <em>{author}</em> <?php _e('with the original author of the feed item.', 'wpematico' ); ?></p>
				<span><?php _e('Also you can add a gallery with three columns with all thumbnails images clickables at the bottom of every content, but before source link and author name, the post template would look like this:', 'wpematico' ); ?></span>
				<div class="code">{content}<br>[gallery link="file" columns="3"]<br>&lt;a href="{permalink}"&gt;<?php _e('Go to Source', 'wpematico' ); ?>&lt;/a&gt;&lt;br /&gt;<br>Author: {author}</div>
				<p><em>[gallery link="file" columns="3"]</em> <?php _e('it\'s a WP shortcode for insert a gallery into the post.  You can use any shortcode here; will be processed by Wordpress.', 'wpematico' ); ?></p>
					<p><ins> <?php _e('If you want to display all the media videos or audios</ins> in the content you could use the playlist shortcode that implements the functionality of displaying a collection of WordPress audio or video files in a post.', 'wpematico' ); ?></p>
					<div class="code">[playlist type="video" style="dark"]</div>
					<p><?php _e('Read more about Post Template feature at ', 'wpematico' ); ?><a title="<?php _e('How to use Post template feature ?', 'wpematico' ); ?>" href="https://etruel.com/question/how-to-use-post-template-feature/" target="_blank"><?php _e('How to use Post template feature ?', 'wpematico' ); ?></a>.</p>
			</div>
		</div>

	</div>
		<?php //if( $cfg['nonstatic'] ) { WPeMaticoPRO_Helpers :: last_html_tag($post, $cfg); } 
		do_action('wpematico_after_template_box',$post, $cfg);
		?>
		

		<?php
	}
	//*************************************************************************************
	public static function images_box( $post ) { 
		global $post, $campaign_data, $cfg, $helptip;
		$fifu_activated 						 = defined( 'FIFU_PLUGIN_DIR' );

		$campaign_imgcache						 = $campaign_data['campaign_imgcache'];
		$campaign_no_setting_img				 = $campaign_data['campaign_no_setting_img'];
		$campaign_nolinkimg						 = $campaign_data['campaign_nolinkimg'];
		$campaign_attach_img					 = $campaign_data['campaign_attach_img'];
		$campaign_image_srcset					 = $campaign_data['campaign_image_srcset'];
		$campaign_featuredimg					 = $campaign_data['campaign_featuredimg'];
		$campaign_attr_images   				 = $campaign_data['campaign_attr_images'];
		$campaign_fifu							 = ($fifu_activated) ? $campaign_data['campaign_fifu'] : false;
		$campaign_fifu_video					 = ($fifu_activated) ? $campaign_data['campaign_fifu_video'] : false;
		$campaign_rmfeaturedimg					 = $campaign_data['campaign_rmfeaturedimg'];
		$campaign_customupload					 = $campaign_data['campaign_customupload'];
		$campaign_enable_featured_image_selector = $campaign_data['campaign_enable_featured_image_selector'];
		$campaign_featured_selector_index		 = $campaign_data['campaign_featured_selector_index'];
		$campaign_featured_selector_ifno		 = $campaign_data['campaign_featured_selector_ifno'];
		if(!$campaign_no_setting_img) {
			$campaign_imgcache		 = $cfg['imgcache'];
			$campaign_nolinkimg		 = $cfg['gralnolinkimg'];
			$campaign_attach_img	 = $cfg['imgattach'];
			$campaign_image_srcset	 = $cfg['image_srcset'];
			$campaign_featuredimg	 = $cfg['featuredimg'];
			$campaign_attr_images    = $cfg['save_attr_images'];
			$campaign_fifu			 = ($fifu_activated) ? $cfg['fifu'] : false;
			$campaign_fifu_video	 = ($fifu_activated) ? $cfg['fifu-video'] : false;
			$campaign_rmfeaturedimg	 = $cfg['rmfeaturedimg'];
			$campaign_customupload	 = $cfg['customupload'];
		}
		?>
		
		<input name="campaign_no_setting_img" id="campaign_no_setting_img" class="checkbox" value="1" type="checkbox" <?php checked($campaign_no_setting_img,true); ?> />
		<label for="campaign_no_setting_img"><?php echo __("Don't use general Settings", 'wpematico' ); ?></label> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['imgoptions']; ?>"></span>
		
		<div id="div_no_setting_img" style="margin-left: 20px; <?php if (!$campaign_no_setting_img) echo 'display:none;';?>">
			<p>
				<input name="campaign_imgcache" id="campaign_imgcache" class="checkbox left" value="1" type="checkbox" <?php checked($campaign_imgcache,true); ?> />
				<b><label for="campaign_imgcache"><?php echo __('Store images locally.', 'wpematico' ); ?></label></b> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['imgcache']; ?>"></span>
			</p>
			<div id="nolinkimg" style="margin-left: 20px; <?php if (!$campaign_imgcache) echo 'display:none;';?>">
				
				<input class="checkbox" value="1" type="checkbox" <?php checked($campaign_attach_img,true); ?> name="campaign_attach_img" id="campaign_attach_img" /><b>&nbsp;<label for="campaign_attach_img"><?php _e('Attach Images to posts.', 'wpematico' ); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['imgattach']; ?>"></span><br/>

				<p class="campaign_attr_image_p" style="margin-top:-2px; <?php if(!$campaign_attach_img) echo 'display:none;'; ?>"><input class="checkbox" value="1" type="checkbox" <?php checked($campaign_attr_images, true); ?>  name="campaign_attr_images" id="campaign_attr_images" /><b>&nbsp;<label for="campaign_attr_images"><?php _e('Save Image attributes on WP Media.', 'wpematico' ); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['save_attr_images']; ?>"></span></p>
				
				<input name="campaign_nolinkimg" id="campaign_nolinkimg" class="checkbox" value="1" type="checkbox" <?php checked($campaign_nolinkimg,true); ?> />&nbsp;<label for="campaign_nolinkimg"><?php _e('Remove link to source images', 'wpematico' ); ?></label> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['gralnolinkimg']; ?>"></span><br/>
				
				<input class="checkbox" value="1" type="checkbox" <?php checked($campaign_image_srcset,true); ?> name="campaign_image_srcset" id="campaign_image_srcset" /><b>&nbsp;<label for="campaign_image_srcset"><?php esc_attr_e('Use srcset attribute instead of src of <img> tag.', 'wpematico' ); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['image_srcset']; ?>"></span><br/>
				
			</div>
			<p></p>
			<input class="checkbox" value="1" type="checkbox" <?php checked($campaign_featuredimg, true); ?> name="campaign_featuredimg" id="campaign_featuredimg" /><b>&nbsp;<label for="campaign_featuredimg"><?php _e('Set first image in content as Featured Image.', 'wpematico' ); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['featuredimg']; ?>"></span>
			<br />
			<input class="checkbox" value="1" type="checkbox" <?php checked($campaign_enable_featured_image_selector,true); ?> name="campaign_enable_featured_image_selector" id="campaign_enable_featured_image_selector" /><b>&nbsp;<label for="campaign_enable_featured_image_selector"><?php _e('Enable featured image selector.', 'wpematico' ); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['enable_featured_image_selector']; ?>"></span>
				<div id="featured_img_selector_div" style="padding-left:20px; <?php if (!$campaign_enable_featured_image_selector) echo 'display:none;';?>">
					<b><label for="featured_selector_index"><?php _e('Index to featured', 'wpematico' ); ?>:</label></b>
					<input name="campaign_featured_selector_index" type="number" min="0" value="<?php echo $campaign_featured_selector_index; ?>" id="campaign_featured_selector_index"/><br />
					<b><label for="campaign_featured_selector_ifno"><?php _e('If no exist index', 'wpematico' ); ?>:</label></b>
					<select name="campaign_featured_selector_ifno" id="campaign_featured_selector_ifno"> 
						<option value="first" <?php selected('first', $campaign_featured_selector_ifno, true); ?>>First image</option>
						<option value="last" <?php selected('last', $campaign_featured_selector_ifno, true); ?>>Last image</option>
					</select>

			</div>
			<br />

			<input class="checkbox" value="1" type="checkbox" <?php checked($campaign_rmfeaturedimg, true); ?> name="campaign_rmfeaturedimg" id="campaign_rmfeaturedimg" /><b>&nbsp;<label for="campaign_rmfeaturedimg"><?php _e('Remove Featured Image from content.', 'wpematico' ); ?></label></b> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['rmfeaturedimg']; ?>"></span>
			<p></p>
			<div id="custom_uploads" style="<?php if (!$campaign_imgcache && !$campaign_featuredimg) echo 'display:none;';?>">
				<input class="checkbox" value="1" type="checkbox" <?php checked($campaign_customupload, true); ?> name="campaign_customupload" id="campaign_customupload" /><b>&nbsp;<label for="campaign_customupload"><?php _e('Use custom upload.', 'wpematico' ); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['customupload']; ?>"></span>
				<br/>
			</div>
			<?php
				do_action('wpematico_image_box_setting_after');
			?>
			<h3 class="subsection"><?php _e('Featured Image From URL', 'wpematico' ); ?></h3>
			<div id="fifu_options_campaign">
				<p><input class="checkbox" value="1" type="checkbox" <?php checked($campaign_fifu, (!$fifu_activated) ? ((!$campaign_fifu) ? true : false ) : true ); ?> name="campaign_fifu" id="campaign_fifu" <?php echo (!$fifu_activated ? 'disabled' : '') ?>/><b>&nbsp;<label for="campaign_fifu"><?php _e('Use Featured Image from URL.', 'wpematico' ); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['fifu']; ?>"></span>
				<br />
				<?php
					if(!$fifu_activated){
						echo '<small>';
						echo  __('The', 'wpematico') . ' <a href="https://wordpress.org/plugins/featured-image-from-url/" rel="nofollow" target="_Blank">' . __('Featured Image from URL', 'wpematico') . '</a> ' . __('plugin needs to be installed and activated from the WordPress repository.','wpematico');
						echo '</small><br />';
					}
				?>
				</p>
				<div id="fifu_campaign_extra_options" style="padding-left: 20px; <?php if (!$campaign_fifu) echo 'display:none;';?>"">
					<input class="checkbox" value="1" type="checkbox" <?php checked($campaign_fifu_video, (!$fifu_activated) ? ((!$campaign_fifu_video) ? true : false ) : true ); ?>  name="campaign_fifu_video" id="campaign_fifu_video" /><b>&nbsp;<label for="campaign_fifu_video"><?php _e('Use video link as featured if available.', 'wpematico' ); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['fifu']; ?>"></span>
				</div>
				
			</div>
		</div>
		
		<?php 
			if(wpematico_is_pro_active(true)):
		?>
		<h3 class="subsection"><?php _e('Advanced Options', 'wpematico' ); ?></h3>
		<?php do_action('wpematico_image_box_out_setting'); ?>


		<?php 
			else:
		?>
		
		<h3 class="subsection"><span class="dashicons dashicons-awards"></span> <?php _e('Do you need advanced features? Take a look at the', 'wpematico' ); ?> <a href="https://etruel.com/downloads/wpematico-professional/" target="_blank" style="text-decoration: none;"><?php _e('Professional addon', 'wpematico' ); ?><span class="dashicons dashicons-external"></span></a></h3>
		<div class="wpe_pro-features">
			<p><span class="dashicons dashicons-yes"></span> <?php _e('Strip queries variables.', 'wpematico' ); ?></p>
			<p><span class="dashicons dashicons-yes"></span> <?php _e('Determine image extension.', 'wpematico' ); ?></p>
			<p><span class="dashicons dashicons-yes"></span> <?php _e('Image renamer.', 'wpematico' ); ?></p>
			<p><span class="dashicons dashicons-yes"></span> <?php _e('Images from enclosure/media tags.', 'wpematico' ); ?></p>
			<p><span class="dashicons dashicons-yes"></span> <?php _e('Strip all images from content.', 'wpematico' ); ?></p>
			<p><span class="dashicons dashicons-yes"></span> <?php _e('Discard the Post if NO Images in Content.', 'wpematico' ); ?></p>
			<p><span class="dashicons dashicons-yes"></span> <?php _e('Default Featured image if not found image on content.', 'wpematico' ); ?></p>
			<p><span class="dashicons dashicons-yes"></span> <?php _e('Image filters allowing or skipping them by dimensions.', 'wpematico' ); ?></p>
			<p><span class="dashicons dashicons-yes"></span> <a href="https://etruel.com/downloads/wpematico-professional/" target="_blank" style="text-decoration: none;"><?php _e('and more...', 'wpematico' ); ?></a></p>
		</div>
		<?php

			endif;
	}
	/**
	* Static function audio_box
	* Create a meta-box on campaigns for audios management.
	* @access public
	* @return void
	* @since 1.7.0
	*/
	public static function audio_box( $post ) { 
		global $post, $campaign_data, $cfg, $helptip;
		$campaign_no_setting_audio = $campaign_data['campaign_no_setting_audio'];
		$campaign_audio_cache = $campaign_data['campaign_audio_cache'];
		$campaign_nolink_audio = $campaign_data['campaign_nolink_audio'];
		$campaign_attach_audio = $campaign_data['campaign_attach_audio'];
		$campaign_customupload_audio = $campaign_data['campaign_customupload_audio'];


		?>
		
		<input name="campaign_no_setting_audio" id="campaign_no_setting_audio" class="checkbox" value="1" type="checkbox" <?php checked($campaign_no_setting_audio, true); ?> />
		<label for="campaign_no_setting_audio"><?php echo __("Don't use general Settings", 'wpematico' ); ?></label> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['audio_options']; ?>"></span>
		
		<div id="div_no_setting_audio" style="margin-left: 20px; <?php if (!$campaign_no_setting_audio) echo 'display:none;';?>">
			<?php
				do_action('wpematico_audio_box_setting_before');
			?>
			<p>
				<input name="campaign_audio_cache" id="campaign_audio_cache" class="checkbox left" value="1" type="checkbox" <?php checked($campaign_audio_cache,true); ?> />
				<b><label for="campaign_audio_cache"><?php echo __('Store audios locally.', 'wpematico' ); ?></label></b> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['audio_cache']; ?>"></span>
			</p>
			<div id="nolink_audio" style="margin-left: 20px; <?php if (!$campaign_audio_cache) echo 'display:none;';?>">
				
				<input class="checkbox" value="1" type="checkbox" <?php checked($campaign_attach_audio,true); ?> name="campaign_attach_audio" id="campaign_attach_audio" /><b>&nbsp;<label for="campaign_attach_audio"><?php _e('Attach Audios to posts.', 'wpematico' ); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['audio_attach']; ?>"></span><br/>
				
				<input name="campaign_nolink_audio" id="campaign_nolink_audio" class="checkbox" value="1" type="checkbox" <?php checked($campaign_nolink_audio,true); ?> />
				<?php echo '<label for="campaign_nolink_audio">' . __('Remove link to source audios', 'wpematico' ) . '</label>'; ?> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['gralnolink_audio']; ?>"></span>
				
			</div>
			<p></p>
			<div id="custom_uploads_audios" style="<?php if (!$campaign_audio_cache) echo 'display:none;';?>">
				<input class="checkbox" value="1" type="checkbox" <?php checked($campaign_customupload_audio, true); ?> name="campaign_customupload_audio" id="campaign_customupload_audio" /><b>&nbsp;<label for="campaign_customupload_audio"><?php _e('Use custom upload.', 'wpematico' ); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['customupload_audios']; ?>"></span>
				<br/>
			</div>
			<?php
				do_action('wpematico_audio_box_setting_after');
			?>
		</div>
		
		<?php
		do_action('wpematico_audio_box_out_setting');
	}
	/**
	* Static function video_box
	* Create a meta-box on campaigns for videos management.
	* @access public
	* @return void
	* @since 1.7.0
	*/
	public static function video_box( $post ) { 
		global $post, $campaign_data, $cfg, $helptip;
		$campaign_no_setting_video = $campaign_data['campaign_no_setting_video'];
		$campaign_video_cache = $campaign_data['campaign_video_cache'];
		$campaign_nolink_video = $campaign_data['campaign_nolink_video'];
		$campaign_attach_video = $campaign_data['campaign_attach_video'];
		$campaign_customupload_video = $campaign_data['campaign_customupload_video'];

		?>
		
		<input name="campaign_no_setting_video" id="campaign_no_setting_video" class="checkbox" value="1" type="checkbox" <?php checked($campaign_no_setting_video, true); ?> />
		<label for="campaign_no_setting_video"><?php echo __("Don't use general Settings", 'wpematico' ); ?></label> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['video_options']; ?>"></span>
		
		<div id="div_no_setting_video" style="margin-left: 20px; <?php if (!$campaign_no_setting_video) echo 'display:none;';?>">
			<p>
				<input name="campaign_video_cache" id="campaign_video_cache" class="checkbox left" value="1" type="checkbox" <?php checked($campaign_video_cache,true); ?> />
				<b><label for="campaign_video_cache"><?php echo __('Store videos locally.', 'wpematico' ); ?></label></b> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['video_cache']; ?>"></span>
			</p>
			<div id="nolink_video" style="margin-left: 20px; <?php if (!$campaign_video_cache) echo 'display:none;';?>">
				
				<input class="checkbox" value="1" type="checkbox" <?php checked($campaign_attach_video,true); ?> name="campaign_attach_video" id="campaign_attach_video" /><b>&nbsp;<label for="campaign_attach_video"><?php _e('Attach Videos to posts.', 'wpematico' ); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['video_attach']; ?>"></span><br/>
				
				<input name="campaign_nolink_video" id="campaign_nolink_video" class="checkbox" value="1" type="checkbox" <?php checked($campaign_nolink_video,true); ?> />
				<?php echo '<label for="campaign_nolink_video">' . __('Remove link to source videos', 'wpematico' ) . '</label>'; ?> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['gralnolink_video']; ?>"></span>
				
			</div>
			<p></p>
			<div id="custom_uploads_videos" style="<?php if (!$campaign_video_cache) echo 'display:none;';?>">
				<input class="checkbox" value="1" type="checkbox" <?php checked($campaign_customupload_video, true); ?> name="campaign_customupload_video" id="campaign_customupload_video" /><b>&nbsp;<label for="campaign_customupload_video"><?php _e('Use custom upload.', 'wpematico' ); ?></label></b><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['customupload_videos']; ?>"></span>
				<br/>
			</div>
			<?php
				do_action('wpematico_video_box_setting_after');
			?>
		</div>
		
		<?php
		do_action('wpematico_video_box_out_setting');
	}
	/**
	* Static function video_box
	* Create a meta-box on campaigns for videos management.
	* @access public
	* @return void
	* @since 2.0
	*/
	public static function duplicate_box( $post ) { 
		global $post, $campaign_data, $cfg, $helptip;
		$campaign_no_setting_duplicate = $campaign_data['campaign_no_setting_duplicate'];
		$campaign_allowduplicates = $campaign_data['campaign_allowduplicates'];
		$campaign_allowduptitle = $campaign_data['campaign_allowduptitle'];
		$campaign_allowduphash = $campaign_data['campaign_allowduphash'];
		$campaign_add_ext_duplicate_filter_ms = $campaign_data['campaign_add_ext_duplicate_filter_ms'];
		$campaign_jumpduplicates = $campaign_data['campaign_jumpduplicates'];


		?>
		
		<input name="campaign_no_setting_duplicate" id="campaign_no_setting_duplicate" class="checkbox" value="1" type="checkbox" <?php checked($campaign_no_setting_duplicate, true); ?> />
		<label for="campaign_no_setting_duplicate"><?php echo __("Don't use general Settings", 'wpematico' ); ?></label> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['duplicate_options']; ?>"></span>
		
		<div id="div_no_setting_duplicate" style="margin-left: 20px; <?php if (!$campaign_no_setting_duplicate) echo 'display:none;';?>">
			
			<p></p>
			<input class="checkbox" value="1" type="checkbox" <?php checked($campaign_allowduplicates, true); ?> name="campaign_allowduplicates" id="campaign_allowduplicates" /><b> <label for="campaign_allowduplicates"> <?php _e('Deactivate duplicate controls.', 'wpematico'); ?></label> </b>  <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['allowduplicates']; ?>"></span>
			<br>
			<div id="enadup" style="padding-left:20px; <?php if(!$campaign_allowduplicates) echo 'display:none;'; ?>">
				<small><?php _e('NOTE: If disable both controls, all items will be fetched again and again... and again, ad infinitum.  If you want allow duplicated titles, just activate "Allow duplicated titles".', 'wpematico'); ?></small><br />
				<input class="checkbox" value="1" type="checkbox" <?php checked($campaign_allowduptitle, true); ?> name="campaign_allowduptitle" id="campaign_allowduptitle" /><b>&nbsp;<?php echo '<label for="campaign_allowduptitle">' . __('Allow duplicates titles.', 'wpematico') . '</label>'; ?></b><br />
				<input class="checkbox" value="1" type="checkbox" <?php checked($campaign_allowduphash, true); ?> name="campaign_allowduphash" id="campaign_allowduphash" /><b>&nbsp;<?php echo '<label for="campaign_allowduphash">' . __('Allow duplicates hashes. (Not Recommended)', 'wpematico') . '</label>'; ?></b>
			</div>
			<div id="div_add_extra_duplicate_filter_meta_source" <?php if($cfg['disableccf'] || $campaign_allowduptitle) echo 'style="display:none;"' ?>>
				<input name="campaign_add_ext_duplicate_filter_ms" id="campaign_add_ext_duplicate_filter_ms" class="checkbox" value="1" type="checkbox" <?php checked($campaign_add_ext_duplicate_filter_ms, true); ?> />
				<label for="campaign_add_ext_duplicate_filter_ms"><b><?php _e('Add an extra duplicate filter by source permalink in meta field value.', 'wpematico'); ?></b></label>  <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['add_extra_duplicate_filter_meta_source']; ?>"></span>
				<br /> 
			</div>
			<p></p>
			<input name="campaign_jumpduplicates" id="campaign_jumpduplicates" class="checkbox" value="1" type="checkbox" <?php checked($campaign_jumpduplicates, true); ?> />
			<label for="campaign_jumpduplicates"><b><?php _e('Continue Fetching if found duplicated items.', 'wpematico'); ?></b></label>  <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['jumpduplicates']; ?>"></span>
			<p></p>
			<?php
				do_action('wpematico_duplicate_box_setting_after');
			?>
		</div>
		
		<?php
		do_action('wpematico_video_box_out_setting');
	}
	//*************************************************************************************
public static function options_box( $post ) { 
	global $post, $campaign_data, $cfg, $helptip ;
	$campaign_max				= $campaign_data['campaign_max'];
	$campaign_feed_order_date	= $campaign_data['campaign_feed_order_date'];
	$campaign_feeddate			= $campaign_data['campaign_feeddate'];
	$campaign_feeddate_forced		 = $campaign_data['campaign_feeddate_forced'];
	$campaign_author				 = $campaign_data['campaign_author'];
	$campaign_linktosource			 = $campaign_data['campaign_linktosource'];
	$copy_permanlink_source			 = $campaign_data['copy_permanlink_source'];
	$avoid_search_redirection		 = $campaign_data['avoid_search_redirection'];
	$campaign_commentstatus			 = $campaign_data['campaign_commentstatus'];
	$campaign_allowpings			 = $campaign_data['campaign_allowpings'];
	$campaign_woutfilter			 = $campaign_data['campaign_woutfilter'];
	$campaign_strip_links			 = $campaign_data['campaign_strip_links'];
	$campaign_strip_links_options	 = $campaign_data['campaign_strip_links_options'];
	$campaign_striphtml				 = $campaign_data['campaign_striphtml'];
	$campaign_get_excerpt			 = $campaign_data['campaign_get_excerpt'];

	$campaign_enable_convert_utf8 = $campaign_data['campaign_enable_convert_utf8'];
	?>
		<div id="optionslayer" class="ibfix vtop">
			<p>
				<input name="campaign_max" type="number" min="0" size="3" value="<?php echo $campaign_max; ?>" class="small-text" id="campaign_max"/> 
				<label for="campaign_max"><?php echo __('Max items to create on each fetch.', 'wpematico'); ?></label> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['itemfetch']; ?>"></span>
			</p>
			<p>
				<input class="checkbox" type="checkbox"<?php checked($campaign_feed_order_date, true); ?> name="campaign_feed_order_date" value="1" id="campaign_feed_order_date"/>
				<label for="campaign_feed_order_date"><?php echo __('Order feed items by Date before process.', 'wpematico'); ?></label> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['feed_order_date']; ?>"></span>
			</p>
			<p>
				<input class="checkbox" type="checkbox"<?php checked($campaign_feeddate, true); ?> name="campaign_feeddate" value="1" id="campaign_feeddate"/>
				<label for="campaign_feeddate"><?php echo __('Use feed item date.', 'wpematico'); ?></label> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['itemdate']; ?>"></span>
			<div id="div_campaign_feeddate_options" style="margin-left:15px;<?php echo (($campaign_feeddate) ? '' : 'display:none;'); ?>">
				<input class="checkbox" type="checkbox"<?php checked($campaign_feeddate_forced, true); ?> name="campaign_feeddate_forced" value="1" id="campaign_feeddate_forced"/> 
				<label for="campaign_feeddate_forced"><?php _e('Force item date.', 'wpematico'); ?></label> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['campaign_feeddate_forced']; ?>"></span>
				<br />
				<?php do_action('wpematico_feeddate_tools', $campaign_data, $cfg); ?>
			</div>
		</p>
		<p>
			<input class="checkbox" type="checkbox"<?php checked($campaign_allowpings ,true);?> name="campaign_allowpings" value="1" id="campaign_allowpings"/> 
			<label for="campaign_allowpings"><?php echo __('Pingbacks y trackbacks.', 'wpematico' ); ?></label> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['allowpings']; ?>"></span>
		</p>

		<p>
			<input class="checkbox" type="checkbox"<?php checked($campaign_enable_convert_utf8, true);?> name="campaign_enable_convert_utf8" value="1" id="campaign_enable_convert_utf8"/> 
			<label for="campaign_enable_convert_utf8"><?php echo __('Convert character encoding to UTF-8.', 'wpematico' ); ?></label> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['convert_utf8']; ?>"></span>
		</p>

		<p>
			<label for="campaign_commentstatus"><?php echo __('Comments options:', 'wpematico' ); ?></label>
			<select id="campaign_commentstatus" name="campaign_commentstatus">
				<option value="open"<?php echo ($campaign_commentstatus =="open" || $campaign_commentstatus =="") ? 'SELECTED' : ''; ?> >Open</option>
				<option value="closed" <?php echo ($campaign_commentstatus =="closed") ? 'SELECTED' : ''; ?> >Closed</option>
				<option value="registered_only" <?php echo ($campaign_commentstatus =="registered_only") ? 'SELECTED' : ''; ?> >Registered only</option>
			</select>  <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['commentstatus']; ?>"></span>
		</p>
		<p>
			<label for="campaign_author"><?php echo __('Author:', 'wpematico' ); ?></label> 
			<?php wp_dropdown_users(array('name' => 'campaign_author','selected' => $campaign_author )); ?> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['postsauthor']; ?>"></span>
		</p>
	</div>	
	<div id="optionslayer-right" class="ibfix vtop">
		<p><input class="checkbox" type="checkbox"<?php checked($campaign_get_excerpt,true);?> name="campaign_get_excerpt" value="1" id="campaign_get_excerpt"/>
			<label for="campaign_get_excerpt"><?php echo __('Fill Excerpt with item description field.', 'wpematico' ); ?></label>  <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['get_excerpt']; ?>"></span>
		</p>
		
		<p><input class="checkbox" type="checkbox"<?php checked($campaign_striphtml,true);?> name="campaign_striphtml" value="1" id="campaign_striphtml"/>
			<label for="campaign_striphtml"><?php echo __('Strip All HTML Tags', 'wpematico' ); ?></label>  <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['striphtml']; ?>"></span>
		</p>

		<?php do_action('wpematico_striptags_tools',$campaign_data,$cfg);  ?>
		
		<div id="div_campaign_strip_links" style="<?php echo ((!$campaign_striphtml)?'':'display:none;'); ?>">
			<p>
				<input class="checkbox" type="checkbox"<?php checked($campaign_strip_links ,true);?> name="campaign_strip_links" value="1" id="campaign_strip_links"/> 
				<label for="campaign_strip_links"><?php echo __('Strip links from content.', 'wpematico' ); ?></label> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['striplinks']; ?>"></span>
				<div id="div_campaign_strip_links_options" style="margin-left:15px;<?php echo (($campaign_strip_links)?'':'display:none;'); ?>">
					<input class="checkbox" type="checkbox"<?php checked($campaign_strip_links_options['a'] ,true);?> name="campaign_strip_links_options[a]" value="1" id="campaign_strip_links_options_a"/> 
					<label for="campaign_strip_links_options[a]"><?php echo __('Strip ', 'wpematico') . '&lt;a&gt;.'; ?></label> <br/>
					<?php //This is a filter for add the two strip checks aditionals
					do_action('wpematico_campaing_pro_strip_links', $campaign_strip_links_options);
					?>
					<input class="checkbox" type="checkbox"<?php checked($campaign_strip_links_options['iframe'] ,true);?> name="campaign_strip_links_options[iframe]" value="1" id="campaign_strip_links_options_iframe"/> 
					<label for="campaign_strip_links_options[iframe]"><?php echo __('Strip ', 'wpematico') . '&lt;iframe&gt;.'; ?></label> <br/>
					<input class="checkbox" type="checkbox"<?php checked($campaign_strip_links_options['script'] ,true);?> name="campaign_strip_links_options[script]" value="1" id="campaign_strip_links_options_script"/> 
					<label for="campaign_strip_links_options[script]"><?php echo __('Strip ', 'wpematico') . '&lt;script&gt;.'; ?></label> 
					<p class="description">
						<?php _e('If you do not select an option, it will be assumed that all of them are selected..', 'wpematico' ); ?>
					</p>
				</div>
			</p>
		</div>
		<?php if ($cfg['woutfilter']) : ?>
			<p>
				<input class="checkbox" value="1" type="checkbox" <?php checked($campaign_woutfilter,true); ?> name="campaign_woutfilter" id="campaign_woutfilter" /> 
				<label for="campaign_woutfilter"><?php echo __('Post Content Unfiltered.', 'wpematico' ); ?></label><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['woutfilter']; ?>"></span>
			</p>
		<?php endif; ?>
		<p>
			<input class="checkbox" type="checkbox"<?php checked($campaign_linktosource ,true);?> name="campaign_linktosource" value="1" id="campaign_linktosource"/> 
			<label for="campaign_linktosource"><?php echo __('Post title links to source.', 'wpematico' ); ?></label> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['linktosource']; ?>"></span>
			<?php if($cfg['disableccf']) echo '<br /><small>'. __('Feature deactivated on Settings. Needs Metadata.', 'wpematico' ).'</small>'; ?>
		</p>
		<p>
			<input <?php echo (($campaign_data['campaign_type']!='youtube')?'':'disabled="disabled"'); ?>" class="checkbox" type="checkbox"<?php checked($copy_permanlink_source ,true);?> name="copy_permanlink_source" value="1" id="copy_permanlink_source"/> 
			<label for="copy_permanlink_source"><?php echo __('Copy the permalink from the source.', 'wpematico' ); ?></label> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['copy_permanlink_source']; ?>"></span>
		</p>


		<p>
			<input class="checkbox" type="checkbox"<?php checked($avoid_search_redirection ,true);?> name="avoid_search_redirection" value="1" id="avoid_search_redirection"/> 
			<label for="avoid_search_redirection"><?php echo __('Avoid search redirection to source permalink.', 'wpematico' ); ?></label> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['avoid_search_redirection']; ?>"></span>
		</p>
		<?php do_action('wpematico_permalinks_tools',$campaign_data,$cfg);  ?>
	</div>
	<?php
}
	//*************************************************************************************
public static function cron_box( $post ) { 
	global $post, $campaign_data, $cfg, $helptip ;
	$activated = $campaign_data['activated'];
	$cron = $campaign_data['cron'];
		//Select en campaña que rellene el cron: Cada 15 min, cada 1hs, cada 3hs, cada 6hs.  2 veces por dia. 1 vez por dia 
	$cronperiods = array(
		'every5'=>array(
			'text'=>__('Every 5 minutes', 'wpematico'),
			'min'=>'*',
			'hours'=>'*',
			'days'=>'*',
			'months'=>'*',
			'weeks'=>'*'),
		'every15'=>array(
			'text'=>__('Every 15 minutes', 'wpematico'),
			'min'=>'0,15,30,45',
			'hours'=>'*',
			'days'=>'*',
			'months'=>'*',
			'weeks'=>'*'),
/*			'every30'=>array(
				'text'=>__('Every half an hour', 'wpematico'),
				'min'=>'0,30',
				'hours'=>'*',
				'days'=>'*',
				'months'=>'*',
				'weeks'=>'*'),
*/			'every60'=>array(
				'text'=>__('Once per hour', 'wpematico'),
				'min'=>'0',
				'hours'=>'*',
				'days'=>'*',
				'months'=>'*',
				'weeks'=>'*'),
'every3h'=>array(
	'text'=>__('Every 3 hours', 'wpematico'),
	'min'=>'0',
	'hours'=>'0,3,6,9,12,15,18,21',
	'days'=>'*',
	'months'=>'*',
	'weeks'=>'*'),
'every6h'=>array(
	'text'=>__('Every 6 hours', 'wpematico'),
	'min'=>'0',
	'hours'=>'0,6,12,18',
	'days'=>'*',
	'months'=>'*',
	'weeks'=>'*'),
'every12h'=>array(
	'text'=>__('Every 12 hours', 'wpematico'),
	'min'=>'0',
	'hours'=>'0,12',
	'days'=>'*',
	'months'=>'*',
	'weeks'=>'*'),
'every1day'=>array(
	'text'=>__('Every day at 3 o\'clock', 'wpematico'),
	'min'=>'0',
	'hours'=>'3',
	'days'=>'*',
	'months'=>'*',
	'weeks'=>'*'),
);
	$cronperiods = apply_filters('wpematico_cronperiods', $cronperiods);
	?><script type="text/javascript">
	jQuery(document).ready(function($){
		$('#cronperiod').on( 'change', function(){
			switch( $(this).val() ) {
				<?php
				foreach($cronperiods as $key => $values) {
					echo "case '".$key."':
					min	   = '".$values['min']."'; 
					$('#cronminutes').val(min.split(','));
					hours  = '".$values['hours']."';
					$('#cronhours').val(hours.split(','));
					days   = '".$values['days']."';
					$('#crondays').val(days.split(','));
					months = '".$values['months']."';
					$('#cronmonths').val(months.split(','));
					weeks  = '".$values['weeks']."';
					$('#cronwday').val(weeks.split(','));
					break;
					";
				}
				?>
			}
		});
	});
</script>
<div id="schedulelayer" class="ibfix vtop">
	<p>
		<input class="checkbox" value="1" type="checkbox" <?php checked($activated,true); ?> name="activated" id="activated" /> <label for="activated"><?php _e('Activate scheduling', 'wpematico' ); ?></label><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['schedule']; ?>"></span>
	</p>
	<?php 
	/* translators: %s Link to WikiPedia 'Cron'  */
	printf(__('Working as %s job schedule:', 'wpematico'), '<a href="http://wikipedia.org/wiki/Cron" target="_blank">Cron</a>'); 
	echo ' <i>'.$cron.'</i> <br />'; 
	_e('Next runtime:', 'wpematico' ); echo ' '.date_i18n( (get_option('date_format').' '.get_option('time_format') ),WPeMatico :: time_cron_next($cron) );
		//_e('Next runtime:', 'wpematico' ); echo ' '.date('D, M j Y H:i',WPeMatico :: time_cron_next($cron));
	?>
	<p>
		<label for="cronperiod">
			<?php _e('Preselected schedules.', 'wpematico' ); ?>
		</label><span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['cronperiod']; ?>"></span>
		<br />
		<select name="cronperiod" id="cronperiod">
			<option value=""><?php _e('Select an option to change the values.','wpematico'); ?></option>
			<?php
			foreach($cronperiods as $key => $values) {
				//echo "<option value=\"".$key."\"".selected(in_array("$i",$minutes,true),true,false).">".$values['text']."</option>";
				echo "<option value=\"".$key."\">".$values['text']."</option>";
			}
			?>
		</select>
	</p>
</div>
<div id="cronboxes" class="ibfix vtop">
	<?php @list($cronstr['minutes'],$cronstr['hours'],$cronstr['mday'],$cronstr['mon'],$cronstr['wday']) = explode(' ',$cron,5);    ?>
	<div>
		<b><?php _e('Minutes: ','wpematico'); ?></b><br />
		<?php 
		if (strstr($cronstr['minutes'],'*/'))
			$minutes=explode('/',$cronstr['minutes']);
		else
			$minutes=explode(',',$cronstr['minutes']);
		?>
		<select name="cronminutes[]" id="cronminutes" multiple="multiple">
			<option value="*"<?php selected(in_array('*',$minutes,true),true,true); ?>><?php echo __('Any ','wpematico') . '(*)'; ?></option>
			<?php
			for ($i=0;$i<60;$i=$i+5) {
				echo "<option value=\"".$i."\"".selected(in_array("$i",$minutes,true),true,false).">".$i."</option>";
			}
			?>
		</select>
	</div>
	<div>
		<b><?php _e('Hours:','wpematico'); ?></b><br />
		<?php 
		if (strstr($cronstr['hours'],'*/'))
			$hours=explode('/',$cronstr['hours']);
		else
			$hours=explode(',',$cronstr['hours']);
		?>
		<select name="cronhours[]" id="cronhours" multiple="multiple">
			<option value="*"<?php selected(in_array('*',$hours,true),true,true); ?>><?php echo __('Any ','wpematico') . '(*)'; ?></option>
			<?php
			for ($i=0;$i<24;$i++) {
				echo "<option value=\"".$i."\"".selected(in_array("$i",$hours,true),true,false).">".$i."</option>";
			}
			?>
		</select>
	</div>
	<div>
		<b><?php _e('Days:','wpematico'); ?></b><br />
		<?php 
		if (strstr($cronstr['mday'],'*/'))
			$mday=explode('/',$cronstr['mday']);
		else
			$mday=explode(',',$cronstr['mday']);
		?>
		<select name="cronmday[]" id="cronmday" multiple="multiple">
			<option value="*"<?php selected(in_array('*',$mday,true),true,true); ?>><?php echo __('Any ','wpematico') . '(*)'; ?></option>
			<?php
			for ($i=1;$i<=31;$i++) {
				echo "<option value=\"".$i."\"".selected(in_array("$i",$mday,true),true,false).">".$i."</option>";
			}
			?>
		</select>
	</div>
	<div>
		<b><?php _e('Months:','wpematico'); ?></b><br />
		<?php 
		if (strstr($cronstr['mon'],'*/'))
			$mon=explode('/',$cronstr['mon']);
		else
			$mon=explode(',',$cronstr['mon']);
		?>
		<select name="cronmon[]" id="cronmon" multiple="multiple">
			<option value="*"<?php selected(in_array('*',$mon,true),true,true); ?>><?php echo __('Any ','wpematico') . '(*)'; ?></option>
			<option value="1"<?php selected(in_array('1',$mon,true),true,true); ?>><?php _e('January'); ?></option>
			<option value="2"<?php selected(in_array('2',$mon,true),true,true); ?>><?php _e('February'); ?></option>
			<option value="3"<?php selected(in_array('3',$mon,true),true,true); ?>><?php _e('March'); ?></option>
			<option value="4"<?php selected(in_array('4',$mon,true),true,true); ?>><?php _e('April'); ?></option>
			<option value="5"<?php selected(in_array('5',$mon,true),true,true); ?>><?php _e('May'); ?></option>
			<option value="6"<?php selected(in_array('6',$mon,true),true,true); ?>><?php _e('June'); ?></option>
			<option value="7"<?php selected(in_array('7',$mon,true),true,true); ?>><?php _e('July'); ?></option>
			<option value="8"<?php selected(in_array('8',$mon,true),true,true); ?>><?php _e('August'); ?></option>
			<option value="9"<?php selected(in_array('9',$mon,true),true,true); ?>><?php _e('September'); ?></option>
			<option value="10"<?php selected(in_array('10',$mon,true),true,true); ?>><?php _e('October'); ?></option>
			<option value="11"<?php selected(in_array('11',$mon,true),true,true); ?>><?php _e('November'); ?></option>
			<option value="12"<?php selected(in_array('12',$mon,true),true,true); ?>><?php _e('December'); ?></option>
		</select>
	</div>
	<div>
		<b><?php _e('Weekday:','wpematico'); ?></b><br />
		<select name="cronwday[]" id="cronwday" multiple="multiple">
			<?php 
			if (strstr($cronstr['wday'],'*/'))
				$wday=explode('/',$cronstr['wday']);
			else
				$wday=explode(',',$cronstr['wday']);
			?>
			<option value="*"<?php selected(in_array('*',$wday,true),true,true); ?>><?php echo __('Any ','wpematico') . '(*)'; ?></option>
			<option value="0"<?php selected(in_array('0',$wday,true),true,true); ?>><?php _e('Sunday'); ?></option>
			<option value="1"<?php selected(in_array('1',$wday,true),true,true); ?>><?php _e('Monday'); ?></option>
			<option value="2"<?php selected(in_array('2',$wday,true),true,true); ?>><?php _e('Tuesday'); ?></option>
			<option value="3"<?php selected(in_array('3',$wday,true),true,true); ?>><?php _e('Wednesday'); ?></option>
			<option value="4"<?php selected(in_array('4',$wday,true),true,true); ?>><?php _e('Thursday'); ?></option>
			<option value="5"<?php selected(in_array('5',$wday,true),true,true); ?>><?php _e('Friday'); ?></option>
			<option value="6"<?php selected(in_array('6',$wday,true),true,true); ?>><?php _e('Saturday'); ?></option>
		</select>
	</div>
	<br class="clear" />
</div>
<?php
}

	//*************************************************************************************
public static function feeds_box( $post ) {  
	global $post, $campaign_data, $cfg, $helptip;

	$campaign_feeds = $campaign_data['campaign_feeds'];
	?>  
	<div class="feed_content">
		<div class="feed_header">
			<?php /*
				 * Action to print each column title
				 * Complete since 2.1
				 */
				do_action('wpematico_campaign_feed_header_column'); 
			?>
			<label id="msgdrag"></label>
			<div class="right ">
				<div style="float:left;margin-left:2px;">
					<input id="psearchtext" name="psearchtext" class="srchbdr0" type="text" value=''>
				</div>
				<div id="productsearch" class="left dashicons dashicons-search" style="color:coral;"></div>
			</div>
		</div>
		<div id="feeds_list" class="maxhe290" data-callback="jQuery('#msgdrag').html('<?php _e('Update Campaign to save Feeds order', 'wpematico'  ); ?>').fadeIn();"> <!-- callback script to run on successful sort -->
			<?php //foreach($campaign_feeds as $i => $feed):
			for ($i = 0; $i <= count(@$campaign_feeds); $i++) {
				$feed = isset($campaign_feeds[$i]) ? $campaign_feeds[$i] : '';
				$lastitem = $i==count(@$campaign_feeds); ?>
			<div id="feed_ID<?php echo $i; ?>" class="sortitem <?php if($lastitem) echo 'feed_new_field'; ?> " <?php if($lastitem) echo 'style="display:none;"'; ?> > <!-- sort item -->
				<div class="sorthandle"> </div> <!-- sort handle -->
				<?php /*
				 * Action to print each column value
				 * Complete since 2.1
				 */
				do_action('wpematico_campaign_feed_body_column',$feed,$cfg, $i);
				?>
				<div class="" id="feed_actions">
					<?php do_action('wpematico_campaign_feed_actions_1',$feed,$cfg, $i); ?>
					<button type="button" title="<?php _e('Check if this feed works', 'wpematico' ); ?>" id="checkfeed" class="check1feed dashicons dashicons-editor-spellcheck"></button>
					<button type="button" title="<?php _e('Delete this item',  'wpematico'  ); ?>" id="deletefeed_<?php echo $i; ?>" data='#feed_ID<?php echo $i; ?>' class="deletefeed dashicons dashicons-trash red"></button>
					<?php do_action('wpematico_campaign_feed_actions_2',$feed,$cfg, $i); ?>
				</div>
			</div>
			<?php $a=$i;
			}?>
		</div>
		<input id="feedfield_max" value="<?php echo $a; ?>" type="hidden" name="feedfield_max">
		<?php do_action('wpematico_campaign_feed_panel'); ?>
	</div>
	<div id="paging-box">		  
		<a href="JavaScript:void(0);" class="button-primary add" id="addmorefeed" style="font-weight: bold; text-decoration: none;"> <?php _e('Add Feed', 'wpematico'  ); ?>.</a>
		<span class="button-primary" id="checkfeeds" style="font-weight: bold; text-decoration: none;" ><?php _e('Check all feeds', 'wpematico' ); ?>.</span>
		<?php do_action('wpematico_campaign_feed_panel_buttons'); ?>
		<?php // if($cfg['nonstatic']){WPeMaticoPRO_Helpers::bimport();} ?>
		<div class="pbfeet right">
			<?php _e('Displaying', 'wpematico' ); ?> <span id="pb-totalrecords" class="b"><?php echo $i-1; ?></span>&nbsp;<span id="pb-ptext">feeds </span>
			<label id="scrollfeeds" class="iconbutton right dashicons dashicons-arrow-down-alt2" title="<?php _e('Display all feeds', 'wpematico' ); ?>" titleoff="<?php _e('Display all feeds', 'wpematico' ); ?>" titleon="<?php _e('Scroll feeds list.', 'wpematico' ); ?>"></label>
		</div>
	</div>
	<?php
	}

	
	//********************************
	public static function youtube_box( $post ) {
		global $post, $campaign_data, $helptip;
		$campaign_youtube_embed = $campaign_data['campaign_youtube_embed'];
		$campaign_youtube_sizes = $campaign_data['campaign_youtube_sizes'];
		$campaign_youtube_width = $campaign_data['campaign_youtube_width'];
		$campaign_youtube_height = $campaign_data['campaign_youtube_height'];
		$campaign_youtube_ign_image = $campaign_data['campaign_youtube_ign_image'];
		$campaign_youtube_image_only_featured = $campaign_data['campaign_youtube_image_only_featured'];
		$campaign_youtube_ign_description = $campaign_data['campaign_youtube_ign_description'];
		$campaign_youtube_only_shorts = $campaign_data['campaign_youtube_only_shorts'];;
		$campaign_youtube_ign_shorts = $campaign_data['campaign_youtube_ign_shorts'];;
		?><div class="ytpreview">
			<h4 id="titlefeatured" style="display: <?php echo ($campaign_youtube_ign_image && !$campaign_youtube_image_only_featured)? 'none' : 'flex'; ?>;">Featured Image</h4>
			<div class="featured-box" style="display: <?php echo ($campaign_youtube_ign_image && !$campaign_youtube_image_only_featured)? 'none' : 'flex'; ?>;">
				<span id="imgfeatured" class="dashicons dashicons-format-image"></span>
			</div>

			<h4 id="titlecontent">Post Title</h4>
			<div class="images-box" style="display: <?php echo ($campaign_youtube_ign_image)? 'none' : 'flex'; ?>;">
				<span id="imgcontent" class="dashicons dashicons-format-image" ></span>
			</div>

			<div class="ytvideo-box">
				<span id="videocontent" class="dashicons dashicons-controls-play"></span>
			</div>
			<div id="descritptioncontent" class="" style="display: <?php echo ($campaign_youtube_ign_description)? 'none' : 'flex'; ?>;">
				Lorem ipsum dolor sit amet, duo cibo voluptua platonem ne, veritus volutpat constituto est.
			</div>	
		</div>
		
		<div class="yt-help">
			<?php echo html_entity_decode($helptip['feed_url']); ?>
			<p></p>
			<label><input class="checkbox" <?php checked($campaign_youtube_embed, true); ?> type="checkbox" name="campaign_youtube_embed" value="1" id="campaign_youtube_embed"> <?php _e('Use [embed] WP shortcode instead Youtube shared iframe.', 'wpematico'); ?></label> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['youtube_embed']; ?>"></span><br />
			<label><input class="checkbox" <?php checked($campaign_youtube_sizes, true); ?> type="checkbox" value="1" id="campaign_youtube_sizes" name="campaign_youtube_sizes"> <?php _e('Change the sizes of the video frames.', 'wpematico'); ?></label> <span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['youtube_sizes']; ?>"></span><br />
			<div id="div_campaign_youtube_sizes" style="margin-left: 17px; <?php echo (!($campaign_youtube_sizes)? 'display: none;' : ''); ?> ">
				<label><?php echo __('Width:', 'wpematico'); ?> <input name="campaign_youtube_width" type="number" min="0" size="3" value="<?php echo $campaign_youtube_width; ?>" class="small-text" id="campaign_youtube_width"/></label><br />
				<label><?php echo __('Height:', 'wpematico'); ?> <input name="campaign_youtube_height" type="number" min="0" size="3" value="<?php echo $campaign_youtube_height; ?>" class="small-text" id="campaign_youtube_height"/></label>
			</div>
			<p><strong><?php _e('Short videos options:', 'wpematico'); ?></strong></p>

			<label><input class="checkbox" <?php checked($campaign_youtube_only_shorts, true); ?> type="checkbox" name="campaign_youtube_only_shorts" value="1" id="campaign_youtube_only_shorts"> <?php _e('Only get shorts videos', 'wpematico'); ?></label><br />

			<label><input class="checkbox" <?php checked($campaign_youtube_ign_shorts, true); ?> type="checkbox" name="campaign_youtube_ign_shorts" value="1" id="campaign_youtube_ign_shorts"> <?php _e('Skip short videos', 'wpematico'); ?></label>

			<p><strong><?php _e('Ignore:', 'wpematico'); ?></strong></p>
			
			<label><input class="checkbox" <?php checked($campaign_youtube_ign_image, true); ?> type="checkbox" name="campaign_youtube_ign_image" value="1" id="campaign_youtube_ign_image"> <?php _e('Image', 'wpematico'); ?></label><br />
			<div id="div_youtube_img_feature" style="margin-left: 17px; <?php echo (empty($campaign_youtube_ign_image)? 'display: none;' : ''); ?> ">
				<label><input class="checkbox" <?php checked($campaign_youtube_image_only_featured, true); ?> type="checkbox" name="campaign_youtube_image_only_featured" value="1" id="campaign_youtube_image_only_featured"> <?php _e('Use only as featured image', 'wpematico'); ?></label><br />
			</div>
			<label><input class="checkbox" <?php checked($campaign_youtube_ign_description, true); ?> type="checkbox" name="campaign_youtube_ign_description" value="1" id="campaign_youtube_ign_description"> <?php _e('Hide description', 'wpematico'); ?></label><br />

			
		</div>
		<?php
	}

	public static function bbpress_box( $post ) {
		global $post, $campaign_data, $helptip;
		$campaign_bbpress_forum = $campaign_data['campaign_bbpress_forum'];
		$campaign_bbpress_topic = $campaign_data['campaign_bbpress_topic'];

		?>
		<?php if (!class_exists('bbPress')) : ?>
			<p style="color: red;"><?php _e('You shouldn\'t use this campaign type if you don\'t have installed and activated the bbPress Plugin', 'wpematico'); ?></p>
			<p style="color: red;"><?php _e('If you want to install the forums plugins in your site go to your "Plugins" page, "Add New", then install and activate the bbPress Plugin from Automattic.', 'wpematico'); ?></p>
		<?php else: ?>
		<p><?php _e('If you do not select a forum, this will create new forums. Otherwise, this will create new topics in the selected forum.', 'wpematico' ); ?></p>
		<p>
			<strong class="label"><?php _e('Forum', 'wpematico'); ?>:</strong>
			<label class="screen-reader-text" for="parent_id"><?php _e('Forum', 'wpematico'); ?></label>
			<?php bbp_dropdown( array(
				'post_type'          => bbp_get_forum_post_type(),
				'selected'           => $campaign_bbpress_forum,
				'numberposts'        => -1,
				'orderby'            => 'title',
				'order'              => 'ASC',
				'walker'             => '',

				// Output-related
				'select_id'          => 'campaign_bbpress_forum',
				'tab'                => bbp_get_tab_index(),
				'options_only'       => false,
				'show_none'          => __( '- Create new forums -', 'wpematico' ),
				'disable_categories' => false,
				'disabled'           => ''
			) ); ?>	
		</p>
		<div id="inside_forums" style="margin-left: 30px; <?php echo (empty($campaign_bbpress_forum)? 'display: none;' : ''); ?>">
			<p>
				<strong class="label"><?php _e('Topic', 'wpematico'); ?>:</strong>
				<label class="screen-reader-text" for="parent_id"><?php _e('Topic', 'wpematico'); ?></label>
				<?php bbp_dropdown( array(
					'post_type'          => bbp_get_topic_post_type(),
					'selected'           => $campaign_bbpress_topic,
					'numberposts'        => -1,
					'orderby'            => 'title',
					'order'              => 'ASC',
					'walker'             => '',

					// Output-related
					'select_id'          => 'campaign_bbpress_topic',
					'tab'                => bbp_get_tab_index(),
					'options_only'       => false,
					'show_none'          => __( '- Create new topics -', 'wpematico' ),
					'disable_categories' => false,
					'disabled'           => ''
				) ); ?>	
			</p>

		</div>

		<?php endif; 
	}

	//********************************
	public static function log_box( $post ) {
		global $post, $campaign_data, $helptip;
		$mailaddresslog = $campaign_data['mailaddresslog'];
		$mailerroronly = $campaign_data['mailerroronly'];
		?>
		<?php _e('E-Mail-Adress:', 'wpematico' ); ?>
		<input name="mailaddresslog" id="mailaddresslog" type="text" value="<?php echo $mailaddresslog; ?>" class="large-text" /><br />
		<input class="checkbox" value="1" type="checkbox" <?php checked($mailerroronly,true); ?> name="mailerroronly" /> <?php _e('Send only E-Mail on errors.', 'wpematico' ); ?>
		<?php
	}

	//********************************
	public static function tags_box( $post ) {
		global $post, $campaign_data, $cfg, $helptip;
		$campaign_tags = $campaign_data['campaign_tags'];
		
			do_action('wpematico_pre_tags_settings', $post);
		
		?>
		<p><b><?php echo '<label for="campaign_tags">' . __('Tags:', 'wpematico' ) . '</label>'; ?></b>
		<textarea style="" class="large-text" id="campaign_tags" name="campaign_tags"><?php echo stripslashes($campaign_tags); ?></textarea><br />
		<?php echo __('Enter comma separated list of Tags.', 'wpematico' ); ?></p>
		<?php do_action('wpematico_pos_tags_settings', $post); ?>
		<?php
	}

	//********************************
	public static function cat_box( $post ) {
		global $post, $campaign_data, $helptip;
		$campaign_categories = $campaign_data['campaign_categories'];
		$campaign_autocats = $campaign_data['campaign_autocats'];
		$campaign_parent_autocats = $campaign_data['campaign_parent_autocats'];
		$campaign_category_limit = $campaign_data['campaign_category_limit'];
		$max_categories =  $campaign_data['max_categories'];
//get_categories()
		$args = array(
			'descendants_and_self' => 0,
			'selected_cats' => array_map('intval', $campaign_categories),
			'popular_cats' => false,
			'walker' => null,
			'taxonomy' => 'category',
			'checked_ontop' => true
			);

//$aa = wp_terms_checklist( 0, $args );
			?>
			<input class="checkbox" type="checkbox"<?php checked($campaign_autocats ,true);?> name="campaign_autocats" value="1" id="campaign_autocats"/> 
			<b><?php echo '<label for="campaign_autocats">' . __('Add auto Categories', 'wpematico' ) . '</label>'; ?></b>
			<span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['autocats']; ?>"></span>
			<br/>

			<input style="margin-left:15px;" class="checkbox" type="checkbox" name="campaign_category_limit" value="1" id="campaign_category_limit" <?php checked($campaign_category_limit, true); ?>> 
			<b><label for="campaign_category_limit">Enable max categories to create</label></b>
			<span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['category_limit']; ?>"></span>
				<p style="margin-left:38px; margin-top: 0;" id="max_categories_wrapper">
					<input name="max_categories" type="number" min="1" size="3" value="<?php echo isset($max_categories) ? esc_attr($max_categories) : 5; ?>" class="small-text" id="max_categories" data-np-intersection-state="visible"> 
				</p>
			<br/>

			<?php do_action('wpematico_print_category_options', $campaign_data); ?>
			
			<div id="autocats_container" <?php if(!$campaign_autocats) echo 'style="display:none;"';?>>
				<br/>
				<b><?php echo '<label for="campaign_parent_autocats">' . __('Parent category to auto categories', 'wpematico' ) . '</label>'; ?></b>
				<span class="dashicons dashicons-warning help_tip" title="<?php echo $helptip['parent_autocats']; ?>"></span> <br/>
				<?php 
				wp_dropdown_categories( array(
					'show_option_all'    => '',
					'show_option_none'   => __('No parent category', 'wpematico' ),
					'orderby'            => 'name', 
					'order'              => 'ASC',
					'show_count'         => 0,
					'hide_empty'         => 0, 
					'child_of'           => 0,
					'exclude'            => '',
					'echo'               => 1,
					'selected'           => $campaign_parent_autocats,
					'hierarchical'       => 1, 
					'name'               => 'campaign_parent_autocats',
					'class'              => 'form-no-clear',
					'id'				 => 'campaign_parent_autocats',
					'depth'              => 3,
					'tab_index'          => 0,
					'taxonomy'           => 'category',
					'hide_if_empty'      => false
					));
					?>
					<br/>
					<br/>
				</div>

		<div class="inside" style="overflow-y: scroll; overflow-x: hidden; max-height: 250px;">
			<b><?php _e('Current Categories', 'wpematico' ); ?></b>
			<div class="right ">
				<div style="float:left;margin-left:2px;display:none;" id="catfield">
					<input id="psearchcat" name="psearchcat" class="srchbdr0" type="text" value=''>
				</div>
				<div id="catsearch" class="left mya4_sprite searchIco" style="margin-top:4px;"></div>
			</div>

			<ul id="categories" style="font-size: 11px;">
				<?php 
				wp_terms_checklist( 0, $args );
				?>
			</ul> 
		</div>
		<div id="major-publishing-actions">
			<a href="JavaScript:void(0);" id="quick_add" onclick="arand=Math.floor(Math.random()*101);jQuery('#categories').append('&lt;li&gt;&lt;input type=&quot;checkbox&quot; name=&quot;campaign_newcat[]&quot; checked=&quot;checked&quot;&gt; &lt;input type=&quot;text&quot; id=&quot;campaign_newcatname'+arand+'&quot; class=&quot;input_text&quot; name=&quot;campaign_newcatname[]&quot;&gt;&lt;/li&gt;');jQuery('#campaign_newcatname'+arand).trigger('focus');" style="font-weight: bold; text-decoration: none;" ><?php _e('Quick add',  'wpematico' ); ?>.</a>
		</div>
		<?php
	}

	// Action handler - The 'Save' button is about to be drawn on the advanced edit screen.
	public static function post_submitbox_start()	{
		global $post, $campaign_data, $helptip;
		if($post->post_type != 'wpematico') return $post->ID;
		
		$campaign_posttype = $campaign_data['campaign_posttype'];
		$campaign_customposttype = $campaign_data['campaign_customposttype'];
		wp_nonce_field( 'edit-campaign', 'wpematico_nonce' ); 
		$statuses = WPeMatico_functions::getAllStatuses();
		?>
		<div class="clear"></div><div class="publish_status">
		<div class="postbox inside">
			<b><?php _e('Status',  'wpematico' ); ?></b><br />
			<div id="stati_options">
			<?php 
				$status_domain ='';
				echo "<select id='campaign_statuses' name='campaign_posttype' >";
				foreach ($statuses  as $key=>$status ) {
					if($status_domain != $status->label_count['domain']){
						$status_domain = $status->label_count['domain'];
						//echo "<b>$status_domain</b><br />";
						echo "<option disabled='disabled' value='' /> $status_domain</option>";
					}
					$status_name = $status->name;
					$status_label = $status->label;
					/**
					 * TODO: Allow Scheduled status with datime in the future by hours 
					 */
					if (in_array($status_name, array('future','')) ) continue;
					
					//echo "<label><input type='radio' name='campaign_posttype' ".checked($status_name, $campaign_posttype, false)." value='$status_name' /> $status_label</label><br />";
					echo "<option ".selected($status_name, $campaign_posttype, false)." value='$status_name' /> $status_label</option>";
				}
				echo '</select>';
				//print_r($statuses); 

			?>
			<div id="loadingstatus" class="ruedita"></div></div>
		</div>
		<div class="clear"></div>
		<div class="postbox inside">
			<b><?php _e('Post type',  'wpematico' ); ?></b><br />
			<?php
			$args=array(
				'public'   => true
				); 
			$output = 'names'; // names or objects, note names is the default
			$output = 'objects'; // names or objects, note names is the default
			$operator = 'and'; // 'and' or 'or'
			$post_types=get_post_types($args,$output,$operator);

			/* ?><pre><?php print_r($post_types); ?></pre><?php  */
//			echo "<select name='campaign_customposttype' >";
			foreach ($post_types  as $post_type_obj ) {
				$post_type = $post_type_obj->name;
				$post_label = $post_type_obj->labels->name;
				if ($post_type == 'wpematico') continue;
				echo '<input class="radio cpt_radio" type="radio" '.checked($post_type,$campaign_customposttype,false).' name="campaign_customposttype" value="'. $post_type. 
					'" id="customtype_'. $post_type. '" /> <label for="customtype_'. $post_type. '">' . __( $post_label ) .' (' . __( $post_type ) .')</label><br />';
//				echo "<option ".selected($post_type,$campaign_customposttype, false)." value='$post_type' /> " . __( $post_label ) ."</option>";
			}

			do_action('wpematico_print_additional_options', $campaign_data);

//			echo '</select>';
			?>
			</div>
		</div><div class="clear"></div>	<?php 
	}

	//campaing wizard
		public static function campaign_wizard(){
			global $post, $campaign_data, $cfg, $helptip;
			?>	
			<div id="wizard_mask"></div>
			<div id="thickbox_wizard">
				<h2 id="campaign_wizard"><?php echo __('CAMPAIGN WIZARD','wpematico'); ?>
					<input type="button" value="x" class="closed_wizard">
				</h2>
				<div class="title_wizard" id="titlediv">
				<button type="button" id="prev_wizard" class="button control-buttons button-primary button-large">
					<i class="dashicons dashicons-arrow-left"></i> <?php echo __('Prev','wpematico'); ?>
				</button>
				<button type="button" id="next_wizard" class="button control-buttons button-primary button-large">
					<?php echo __('Next','wpematico'); ?> <i class="dashicons dashicons-arrow-right"></i>
				</button>
				</div>
				
				<!--title default wizard-->
				<div class="postbox" id="temp_postbox">
					<h2 class="hndle ui-sortable-handle temp_uisortable postbox-title" data-background-color="">
						<span></span>
					</h2>
					<div class="wpematico_divider_list_wizard">
						<span  class="dashicons dashicons-editor-help icon-wizard-help"></span>
						<p class="help_wizard"> </p>
					</div>
				</div>
			</div>
		<?php		
		}

}
?>