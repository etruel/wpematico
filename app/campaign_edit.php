<?php 
// don't load directly 
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

add_action( 'init', array( 'WPeMatico_Campaign_edit', 'init' ) );


if ( class_exists( 'WPeMatico_Campaign_edit' ) ) return;

class WPeMatico_Campaign_edit extends WPeMatico_Campaign_edit_functions {
	
	public static function init() {
		new self();
	}
	
	public function __construct( $hook_in = FALSE ) {
		add_action('save_post', array( __CLASS__ , 'save_campaigndata'));
		add_action('wp_ajax_wpematico_run', array( &$this, 'RunNowX'));
		add_action('wp_ajax_wpematico_checkfields', array( __CLASS__, 'CheckFields'));
		add_action('wp_ajax_wpematico_test_feed', array( 'WPeMatico', 'Test_feed'));
		add_action('admin_print_styles-post.php', array( __CLASS__ ,'admin_styles'));
		add_action('admin_print_styles-post-new.php', array( __CLASS__ ,'admin_styles'));
		add_action('admin_print_scripts-post.php', array( __CLASS__ ,'admin_scripts'));
		add_action('admin_print_scripts-post-new.php', array( __CLASS__ ,'admin_scripts'));
	}

	public static function disable_autosave() {
	//	global $post_type, $post, $typenow;
		if(get_post_type() != 'wpematico') return ;
		wp_deregister_script( 'autosave' );
	}

  	public static function admin_styles(){
		global $post;
		if($post->post_type != 'wpematico') return $post->ID;
		//wp_enqueue_style('campaigns-edit',WPeMatico :: $uri .'app/css/campaigns_edit.css');	
		wp_enqueue_style( 'WPematStylesheet' );
		add_action('admin_head', array( __CLASS__ ,'campaigns_admin_head_style'));
	}
	public static function campaigns_admin_head_style() {
		global $post, $campaign_data;
		if($post->post_type != 'wpematico') return $post->ID;
		?>
<style type="text/css">
	.postbox-header h2.hndle .dashicons{ margin-right: 5px; }
	.postbox-header h2.hndle {justify-content: initial;}
	#post_format-box h2.hndle, #post_format-box .postbox-header {background: #7afed1; }
	#campaign_types h2.hndle, #campaign_types .postbox-header {background: #b4ceb1; }
	#category-box h2.hndle, #category-box .postbox-header {background: #f09999; }
	#post_tag-box h2.hndle, #post_tag-box .postbox-header {background: #f997c7; }
	#log-box h2.hndle, #log-box .postbox-header {background: #55a288; }
	#feeds-box h2.hndle, #feeds-box .postbox-header {background: #eb9600; }
	#xml-campaign-box h2.hndle, #xml-campaign-box .postbox-header {background: #ce5c00; } /* #91a78d */
	#youtube-box h2.hndle, #youtube-box .postbox-header {background: red; color: white; }
	#bbpress-box h2.hndle, #bbpress-box .postbox-header {background: #8DC770; }
	#options-box h2.hndle, #options-box .postbox-header {background: #84f384; }
	#cron-box h2.hndle, #cron-box .postbox-header {background: #d4b388; } /* cron en otra metabox */
	#images-box h2.hndle, #images-box .postbox-header {background: #e1fb34; }
	#template-box h2.hndle, #template-box .postbox-header {background: #c1fefe; }
	#word2cats-box h2.hndle, #word2cats-box .postbox-header {background: #f6e3c5; }
	#rewrite-box h2.hndle, #rewrite-box .postbox-header {background: #ffb3be; }
	#fullcontent-box h2.hndle, #fullcontent-box .postbox-header {background: #006100;	color: white; }
	#submitdiv h2.hndle, #submitdiv .postbox-header {background: #0085ba;	color: white; }
	.ruedita{background: url(<?php echo esc_url( admin_url('images/spinner.gif') ); ?>) no-repeat 4px !important;}
	<?php
		$CampaignTypesArray =  self::campaign_type_options();
		$CampaignType = $campaign_data['campaign_type'];
		foreach ($CampaignTypesArray as $type) {
			$cttype = (object)$type;
			foreach ($cttype->show as $show) {   // first hide all 
				if($CampaignType != $cttype->value) {
					//echo "#$show {display: none;}";
					echo '#' . esc_attr( $show ) . ' {display: none;}';
				}
			}
		}
		foreach ($CampaignTypesArray as $type) {
			$cttype = (object)$type;
			foreach ($cttype->show as $show) {     // shows these CT metaboxes
				if($CampaignType == $cttype->value) {
					//echo "#$show {display: block;}";
					echo '#' . esc_attr( $show ) . ' {display: block;}';
					if(isset($cttype->hide) ) {  // tiene que ocultar algun metabox ? NOT Tested seems can't be recovered later
						foreach ($cttype->hide as $hide) {  //process only the hide of selected type 
							echo '#' . esc_attr( $hide ) . ' {display: none;}';
							//echo "#$hide {display: none;}";
						}
					}
				}
			}			
		}
	?>;	
</style>
		<?php
	}
	
	public static function admin_scripts(){
		global $post;
		if($post->post_type != 'wpematico') return $post->ID;

		$cfg = get_option(WPeMatico::OPTION_KEY);
		$cfg = apply_filters('wpematico_check_options', $cfg);

		wp_enqueue_script('jquery-vsort'); 
		wp_enqueue_script( 'WPemattiptip' );
		wp_dequeue_script( 'autosave' );
		add_action('admin_head', array( __CLASS__ ,'campaigns_admin_head'));
		wp_enqueue_script('wpematico_hooks', WPeMatico::$uri .'app/js/wpe_hooks.js', array(), WPEMATICO_VERSION, true );
		wp_enqueue_script('wpematico_campaign_edit', WPeMatico::$uri .'app/js/campaign_edit.js', array( 'jquery' ), WPEMATICO_VERSION, true );
		wp_enqueue_script('wpematico_campaign_wizard', WPeMatico::$uri .'app/js/campaign_wizard.js', array( 'jquery' ), WPEMATICO_VERSION, true );
		

		$name_campaign = get_the_title($post->ID);
		$see_logs_action_url = admin_url('admin-post.php?action=wpematico_campaign_log&p='.$post->ID.'&_wpnonce=' . wp_create_nonce('clog-nonce'));
		$preview_campaign_action_url = admin_url('admin-post.php?action=wpematico_campaign_preview&p='.$post->ID.'&_wpnonce=' . wp_create_nonce('campaign-preview-nonce'));

		$wpematico_object = array(
					'text_dismiss_this_notice'			=> __('Dismiss this notice.', 'wpematico'),
					'text_type_some_feed_url' 			=> __('Type some feed URL.', 'wpematico'),
					'text_type_some_new_feed_urls' 		=> __('Type some new Feed URL/s.', 'wpematico'),
					'text_running_campaign' 			=> __('Running Campaign...', 'wpematico'),
					'text_save_before_run_campaign' 	=> __('Save before Run Campaign', 'wpematico'),
					'text_save_before_execute_action' 	=> __('Save before to execute this action', 'wpematico'),
					'text_confirm_reset_campaign'		=> __('Are you sure you want to reset this campaign?', 'wpematico'),
					'text_confirm_delhash_campaign'		=> __('Are you sure you want to delete hash code for duplicates of this campaign?', 'wpematico'),
					/* translators: the link to WPeMatico System Status page. */
					'text_fail_run_campaign'			=> sprintf(__('An error has occurred, this could be because the web server does not have all the requirements of WPeMatico please check your <a href="%s">System Status</a>, if everything is ok try again.', 'wpematico'), admin_url('edit.php?post_type=wpematico&page=wpematico_tools&tab=debug_info') ),
					'text_wizard'						=> __('Wizard', 'wpematico'),
					'text_loading'						=> __('Loading...', 'wpematico'),

					'general_settings' 					=> $cfg,
					'image_run_loading' 				=> get_bloginfo('wpurl').'/wp-admin/images/wpspin_light.gif',
					'id_campaign' 						=> $post->ID,
					'name_campaign' 					=> $name_campaign,
					'see_logs_action_url' 				=> $see_logs_action_url,
					'preview_campaign_action_url' 		=> $preview_campaign_action_url,
					'update2save' 						=> __('Update Campaign to save changes.', 'wpematico' ),
					'admin_url' 						=> admin_url('admin-post.php'),
					'visibility_trans'  				=> __('Public', 'wpematico'),
					'visibility' 						=> 'public',
					'description' 						=> __('Campaign Description', 'wpematico'),
					'description_help' 					=> __('Here you can write some observations.', 'wpematico'),
					'xml_check_data_nonce' 				=> wp_create_nonce('wpematico-xml-check-data-nonce'),
					'check_fields_nonce' 				=> wp_create_nonce('wpematico-check-fields-nonce'),
					'run_now_edit_nonce' 				=> wp_create_nonce('wpematico-run-now-nonce'),
					'Notification_Hidding'				=> __('Hidding...', 'wpematico'),
					'Notification_Dismissed'			=> __('Closed forever', 'wpematico'),


				);
		if ($cfg['enableword2cats']) {
			
			$wpematico_object['text_w2c_word'] =  __('Word:', 'wpematico');
			$wpematico_object['text_w2c_on_title'] = __('on Title', 'wpematico');
			$wpematico_object['text_w2c_regex'] = __('RegEx', 'wpematico');
			$wpematico_object['text_w2c_case_sensitive'] = __('Case sensitive', 'wpematico');
			$wpematico_object['text_w2c_to_category'] = __('To Category:', 'wpematico').' ';
			$wpematico_object['text_w2c_delete_this_item'] = __('Delete this item', 'wpematico');

			
			$wpematico_object['wpe_w2c_dropdown_categories'] = wp_dropdown_categories( array(
											'show_option_all'    => '',
											'show_option_none'   => __('Select category', 'wpematico' ),
											'hide_empty'         => 0, 
											'child_of'           => 0,
											'exclude'            => '',
											'echo'               => 0,
											'selected'           => 0,
											'hierarchical'       => 1, 
											'name'               => 'campaign_wrd2cat[w2ccateg][{index}]',
											'class'              => 'form-no-clear',
											'id'           		 => 'campaign_wrd2cat_category_{index}',
											'hide_if_empty'      => false
										));

		}

		wp_localize_script('wpematico_campaign_edit', 'wpematico_object', $wpematico_object);
	}

	function RunNowX() {
		
		$nonce = (isset($_POST['nonce'])) ?  sanitize_text_field($_POST['nonce']) : '';
        if ( ! wp_verify_nonce($nonce, 'wpematico-run-now-nonce') ) {
           die( esc_html__('Please refresh your browser and try again.', 'wpematico') ); 
        }
		
		
		if(!isset($_POST['campaign_ID'])) die('ERROR: ID no encontrado.'); 
		$campaign_ID = absint($_POST['campaign_ID']);
		echo wp_kses_post( substr( WPeMatico :: wpematico_dojob( $campaign_ID ) , 0, -1) ); // borro el ultimo caracter que es un 0
		return ''; 
	}
	
	public static function campaigns_admin_head() {
		global $post,$campaign_data;
		if($post->post_type != 'wpematico') return $post->ID;
		$post->post_password = '';

		$cfg = get_option(WPeMatico :: OPTION_KEY);
		
		?>
		<script type="text/javascript" language="javascript">
		jQuery(document).ready(function($){
			
			
			var postTypesArray = {};
			<?php
				$args = array();
				$postTypesArr =  get_post_types($args);
				foreach ($postTypesArr as $postType) {
					echo "postTypesArray['$postType'] = ['" . implode("','", get_object_taxonomies($postType)) . "'];";
				}
			?>
			var arrayTaxonomiesIds = {post_format: 'post_format-box', category: 'category-box', post_tag: 'post_tag-box'};			

			displayTaxonomies = function() {
				var currentPostType = $('input[name="campaign_customposttype"]:checked').val();
				for(var i in arrayTaxonomiesIds) {
					$('#' + arrayTaxonomiesIds[i]).fadeOut();
				}	
				for(var key in postTypesArray[currentPostType]) {
					$('#' + arrayTaxonomiesIds[postTypesArray[currentPostType][key]]).fadeIn();
				}
			}
			$( document ).ready(function() {
				$('#categorydiv').remove();
				$('#tagsdiv-post_tag').remove();
			    for(var i in postTypesArray['wpematico']) {
			    	if ($('#' + postTypesArray['wpematico'][i]).length > 0) {
			    		arrayTaxonomiesIds[postTypesArray['wpematico'][i]] = $('#' + postTypesArray['wpematico'][i]).closest('.postbox').attr('id');
			    	};
			    	if ($('#taxonomy-' + postTypesArray['wpematico'][i]).length > 0) {
			    		arrayTaxonomiesIds[postTypesArray['wpematico'][i]] = $('#taxonomy-' + postTypesArray['wpematico'][i]).closest('.postbox').attr('id');
			    	};
			    }
			    displayTaxonomies();
			});

			var before_change = 'post';
			$('input[name="campaign_customposttype"]').on("mouseup", function(){
			    before_change = $('input[name="campaign_customposttype"]:checked').val();
			}).on("change", function() {
			    var needAlert = false;
		        for(i in postTypesArray[before_change]) {
		        	if (postTypesArray[before_change][i] != "" && postTypesArray[this.value].indexOf(postTypesArray[before_change][i]) < 0) {
		        		switch(postTypesArray[before_change][i]) {
		        			case "category":
		        				if (typeof $('input[name="post_category[]"]:checked').val() != 'undefined') {
		        					needAlert = true;
		        				};
		        				break;
		        			case "post_tag":
		        				if ($('textarea[name="campaign_tags"]').val() != '') {
		        					needAlert = true;
		        				};
		        				break;
		        			case "post_format":
		        				if ($('input[name="campaign_post_format"]:checked').val() != '0') {
		        					needAlert = true;
		        				};
		        				break;
		        			default:
		        				if (typeof $('input[name="tax_input[' + postTypesArray[before_change][i] + '][]"]:checked').val() != 'undefined') {
		        					needAlert = true;
		        				};
		        		}		        		
		        	};
		        }

		        if (needAlert == true) {
		        	var r = confirm("Old campaign taxonomies will be deleted!");
				    if (r != true) {
				        $('input[name="campaign_customposttype"][value="'+before_change+'"]').prop('checked', true);
				    }
		        };
		        displayTaxonomies();
			});

			
			
			
			<?php $CampaignTypesArray =  self::campaign_type_options();	?>
			CampaignTypesArray = <?php echo wp_json_encode($CampaignTypesArray); ?>;
			
			displayCTboxes = function() {
				var campaignType = $('#campaign_type').val();
				for(var i in CampaignTypesArray) {
					CampaignTypesArray[i].show.forEach( function(metabox) {
						if(campaignType != CampaignTypesArray[i].value ) {
							$('#' + metabox).fadeOut();
						}
					});
				}
				for(var i in CampaignTypesArray) {
					CampaignTypesArray[i].show.forEach( function(metabox) {
						if(campaignType == CampaignTypesArray[i].value ) {
							$('#' + metabox).fadeIn();
							if(CampaignTypesArray[i].hasOwnProperty('hide')){
								CampaignTypesArray[i].hide.forEach( function(metab) {
									$('#' + metab).fadeOut();
								});
							}
						}
					});
				}
			}
			
			$('#campaign_type').on("change", function() {
				displayCTboxes();
			});
			

			jQuery(".help_tip").tipTip({maxWidth: "400px", edgeOffset: 5,fadeIn:50,fadeOut:50, keepAlive:true, defaultPosition: "right"});

			//} catch(err)}
		});
		</script>
		<?php
	}
	/**
	* Static function CheckFields
	* This function check required fields values before save post.
	* @access public
	* @return $err_message Int|String,  1 if OK, else return an error string
	* @since 1.0.0
	*/
	public static function CheckFields() {

		$nonce = (isset($_POST['nonce'])) ? sanitize_text_field($_POST['nonce']) : '';
        if (!wp_verify_nonce($nonce, 'wpematico-check-fields-nonce')) {
           die('1'); 
        }
		$cfg = get_option(WPeMatico::OPTION_KEY);
		$err_message = "";
		if( isset( $_POST['campaign_wrd2cat']) ) {
			$wrd2cat = array();
			parse_str($_POST['campaign_wrd2cat'], $wrd2cat);
			$campaign_wrd2cat = @$wrd2cat['campaign_wrd2cat'];

			for ($id = 0; $id < count((array) $campaign_wrd2cat); $id++) {
				$word = $campaign_wrd2cat['word'][$id];
				$regex = ($_POST['campaign_wrd2cat_regex'][$id]==1) ? true : false ;
				if(!empty($word))  {
					if($regex) 
						if(false === @preg_match($word, '')) {
							$err_message = ($err_message != "") ? $err_message."<br />" : "" ;
							/* translators: The value of the regex field with an error. */
							$err_message .= sprintf(__('There\'s an error with the supplied RegEx expression in word: %s', 'wpematico'),'<span class="coderr">'.$word.'</span>');
						}
				}
			}
		}
		
		if(isset($_POST['campaign_word_origin'])) {
			$rewrites = array();
			parse_str($_POST['campaign_word_origin'], $rewrites);
			$campaign_word_origin = @$rewrites['campaign_word_origin'];
			for ($id = 0; $id < count((array) $campaign_word_origin); $id++) {
				$origin = $campaign_word_origin[$id];
				$regex = $_POST['campaign_word_option_regex'][$id]==1 ? true : false ;
				if(!empty($origin))  {
					if($regex) 
						if(false === @preg_match($origin, '')) {
							$err_message = ($err_message != "") ? $err_message."<br />" : "" ;
							/* translators: The value of the regex field with an error. */
							$err_message .= sprintf(__('There\'s an error with the supplied RegEx expression in ReWrite: %s', 'wpematico'),'<span class="coderr">'.$origin.'</span>');
						}
				}
			}
		}
		
		$campaign_type = ( ! empty($_POST['campaign_type']) ? sanitize_text_field($_POST['campaign_type']) : 'feed' );
		
		/**
		 * Filter wpematico_campaign_type_validate_feed_before_save to allow add campaigns types fields to be checked.  
		 * We send all fields in form to sanitize in the filter functions.
		 */
		$ct_validate_feed = apply_filters('wpematico_campaign_type_validate_feed_before_save', array('feed', 'youtube', 'bbpress', 'ebay'), $_POST );
		
		if( ( !isset($cfg['disablecheckfeeds']) || !$cfg['disablecheckfeeds'] ) && in_array($campaign_type, $ct_validate_feed)  ){  // If this options isn't deactivated in settings.
			// If the campaign doesn't has a feed this give an error.
			// This process strip all feed URLs empty.
			if(isset($_POST['campaign_feeds'])) {
				$feeds = array();
				parse_str($_POST['campaign_feeds'], $feeds);
				$all_feeds = $feeds['campaign_feeds'];
				foreach($all_feeds as $id => $feedname) {
					if(!empty($feedname))  {
						if(!isset($campaign_feeds))  {
							$campaign_feeds = array();					
						}
						$campaign_feeds[] = $feedname ;
					}
				}
			}

			if(empty($campaign_feeds) || !isset($campaign_feeds)) {
				$err_message = ($err_message != "") ? $err_message."<br />" : "" ;
				$err_message .= __('At least one feed URL must be filled.', 'wpematico');
			} else {  
				/**
				 * wpematico_check_campaigndata Filter to sanitize and strip all fields 
				 */
				$post_campaign = apply_filters('wpematico_check_campaigndata', $_POST);
				$post_campaign['campaign_feeds'] = $campaign_feeds;
				foreach($campaign_feeds as $kf => $feed) {
					$pos = strpos($feed, ' '); // The feed URL can't has white spaces.
					if ($pos === false) {
						$fetch_feed_params = array(
							'url' 			=> esc_url_raw($feed),
							'stupidly_fast' => true,
							'max' 			=> 0,
							'order_by_date' => false,
							'force_feed' 	=> false,
							'disable_simplepie_notice' => true,
						);
						$fetch_feed_params = apply_filters('wpematico_check_fetch_feed_params', $fetch_feed_params, $kf, $post_campaign);
						$simplepie =  WPeMatico::fetchFeed($fetch_feed_params);
						if($simplepie->error()) {
							$err_message = ($err_message != "") ? $err_message."<br />" : "" ;
							/* translators: %1$s The feed URL with an error. %2$s Error message */
							$err_message .= sprintf(__('Feed %1$s could not be parsed. (SimplePie said: %2$s)',  'wpematico'),'<strong class="coderr">'. esc_html($feed) . '</strong>', $simplepie->error());
						}
					}else{
						$err_message = ($err_message != "") ? $err_message."<br />" : "" ;
						/* translators: The feed URL with an error. */
						$err_message .= sprintf(__('Feed %s could not be parsed because has an space in url.', 'wpematico'),'<strong class="coderr">' . esc_html($feed) . '</strong>');
					}
				}
			}
		}
		$err_message = apply_filters('wpematico_check_error_message',$err_message, $_POST);
		
		if($err_message =="" ) $err_message="1";  //NO ERROR
		die($err_message); 
	}
	/**
	* Static function save_campaigndata
	* This function save the campaign data.
	* @access public
	* @return $post_id
	* @since 1.0.0
	*/
	public static function save_campaigndata( $post_id ) {
		global $post,$cfg;
		if((defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit']) || (isset($_REQUEST['action']) && $_REQUEST['action']=='inline-save') ) {
			WPeMatico_Campaigns::save_quick_edit_post($post_id);
			return $post_id;
		}
		if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || (defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit']))
			return $post_id;
		if ( !wp_verify_nonce( @$_POST['wpematico_nonce'], 'edit-campaign' ) )
			return $post_id;

		if($post->post_type != 'wpematico') return $post_id;

		$nivelerror = error_reporting(E_ERROR | E_WARNING | E_PARSE);

		$campaign = WPeMatico::get_campaign($post_id);

		$_POST['post_status'] =  (!isset($_POST['post_status']) ) ? 'publish': sanitize_text_field($_POST['post_status']);
		$_POST['publish'] =  (!isset($_POST['publish']) ) ? 'publish': sanitize_text_field($_POST['publish']);

		$_POST['postscount']	= (!isset($campaign['postscount']) ) ? 0: (int)$campaign['postscount'];
		$_POST['lastpostscount']	= (!isset($campaign['lastpostscount']) ) ? '': (int)$campaign['lastpostscount'];
		$_POST['lastrun']	= (!isset($campaign['lastrun']) ) ? 0: (int)$campaign['lastrun'];
		$_POST['lastruntime']	= (!isset($campaign['lastruntime']) ) ? 0: sanitize_text_field($campaign['lastruntime']);  //can be string

		$campaign = array();
		/**
		 * wpematico_check_campaigndata Filter to sanitize and strip all fields 
		 */
		$campaign = apply_filters('wpematico_check_campaigndata', $_POST);
 
		error_reporting($nivelerror);

		if(has_filter('wpematico_presave_campaign')) $campaign = apply_filters('wpematico_presave_campaign', $campaign);
		
		// Saved campaign
		WPeMatico::update_campaign($post_id, $campaign);

		return $post_id ;
	}
	
}