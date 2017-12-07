<?php
// don't load directly 
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
if (!class_exists('wpematico_preview')) :

class wpematico_preview {
	public static $cfg = array();
	/**
	* Static function hooks
	* @access public
	* @return void
	* @since 1.9
	*/
	public static function hooks() {

		
		add_action('admin_post_wpematico_preview', array(__CLASS__, 'print_preview'));
		add_action('wpematico_preview_print_styles', array(__CLASS__, 'styles'));
		add_action('wpematico_preview_print_scripts', array(__CLASS__, 'scripts'));
	}
	/**
	* Static function styles
	* @access public
	* @return void
	* @since 1.9
	*/
	public static function styles() {
		wp_enqueue_style('wpematico-preview', WPeMatico::$uri . 'app/css/campaign_preview.css', array(), WPEMATICO_VERSION);	
	}
	/**
	* Static function scripts
	* @access public
	* @return void
	* @since 1.9
	*/
	public static function scripts() {
		
	}
	/**
	* Static function print_preview
	* @access public
	* @return void
	* @since 1.9
	*/
	public static function print_preview($message) {
		
		self::$cfg = get_option(WPeMatico::OPTION_KEY);

		if (!empty($_REQUEST['feed'])) {
			$feed = $_REQUEST['feed'];
		} else {
			wp_die(__('The feed is invalid.', 'wpematico'));
		}
		$campaign_id = intval($_REQUEST['campaign']);
		if (empty($campaign_id)) {
			wp_die(__('The campaign is invalid.', 'wpematico'));
		} 
		$campaign = WPeMatico::get_campaign($campaign_id);

		if (defined('WP_DEBUG') and WP_DEBUG){
			set_error_handler('wpematico_joberrorhandler',E_ALL | E_STRICT);
		}else{
			set_error_handler('wpematico_joberrorhandler',E_ALL & ~E_NOTICE);
		}

		$fetch_feed_params = array(
			'url' 			=> $feed,
			'stupidly_fast' => true,
			'max' 			=> 0,
			'order_by_date' => false,
			'force_feed' 	=> false,
			'disable_simplepie_notice' => true,
		);
		$fetch_feed_params = apply_filters('wpematico_preview_fetch_feed_params', $fetch_feed_params, 0, $campaign);
		$simplepie =  WPeMatico::fetchFeed($fetch_feed_params);


		$count = 0;
		$prime = true;
		$lasthash = array();
		$currenthash = array();


		$posts_fetched = array();
		$posts_next = array();

		foreach($simplepie->get_items() as $item) {
			if($prime){
				//with first item get the hash of the last item (new) that will be saved.
				$lasthash[$feed] = md5($item->get_permalink()); 
				$prime = false;
			}

			$currenthash[$feed] = md5($item->get_permalink()); 
			if( !self::$cfg['allowduplicates'] || !self::$cfg['allowduptitle'] || !self::$cfg['allowduphash']  || self::$cfg['add_extra_duplicate_filter_meta_source']){
				if( !self::$cfg['allowduphash'] ){
					// chequeo a la primer coincidencia sale del foreach
					$lasthashvar = '_lasthash_'.sanitize_file_name($feed);
					$hashvalue = get_post_meta($campaign_id, $lasthashvar, true );
					if (!isset($campaign[$feed]['lasthash'] ) ) $campaign[$feed]['lasthash'] = '';
					
					$dupi = ( $campaign[$feed]['lasthash'] == $currenthash[$feed] ) || 
								( $hashvalue == $currenthash[$feed] ); 
					if ($dupi) {
						$posts_fetched[md5($item->get_permalink())] = true;
						trigger_error(sprintf(__('Found duplicated hash \'%1s\'', 'wpematico' ),$item->get_permalink()).': '.$currenthash[$feed] ,E_USER_NOTICE);
						if( !self::$cfg['jumpduplicates'] ) {
							trigger_error(__('Filtering duplicated posts.', 'wpematico' ),E_USER_NOTICE);
							break;
						}else {
							trigger_error(__('Jumping duplicated post. Continuing.', 'wpematico' ),E_USER_NOTICE);
							continue;
						}
					}
				}
				if( !self::$cfg['allowduptitle'] ){
					if(WPeMatico::is_duplicated_item($campaign, $feed, $item)) {
						$posts_fetched[md5($item->get_permalink())] = true;
						trigger_error(sprintf(__('Found duplicated title \'%1s\'', 'wpematico' ),$item->get_title()).': '.$currenthash[$feed] ,E_USER_NOTICE);
						if( !self::$cfg['jumpduplicates'] ) {
							trigger_error(__('Filtering duplicated posts.', 'wpematico' ),E_USER_NOTICE);
							break;
						}else {
							trigger_error(__('Jumping duplicated post. Continuing.', 'wpematico' ),E_USER_NOTICE);
							continue;
						}
					}
				}

			}
			$posts_next[md5($item->get_permalink())] = true;
			$count++;	  
			if($count == $campaign['campaign_max']) {
				trigger_error(sprintf(__('Campaign fetch limit reached at %1s.', 'wpematico' ), $campaign['campaign_max']),E_USER_NOTICE);
				break;
			}
		}


		$have_gettext = function_exists('__');

		if ( ! did_action( 'admin_head' ) ) :
			if ( !headers_sent() ) {
				status_header(200);
				nocache_headers();
				header( 'Content-Type: text/html; charset=utf-8' );
			}

			
			$text_direction = 'ltr';
			if ( function_exists( 'is_rtl' ) && is_rtl() ) {
				$text_direction = 'rtl';
			}

	?>
	<!DOCTYPE html>
	<!-- Ticket #11289, IE bug fix: always pad the error page with enough characters such that it is greater than 512 bytes, even after gzip compression abcdefghijklmnopqrstuvwxyz1234567890aabbccddeeffgghhiijjkkllmmnnooppqqrrssttuuvvwwxxyyzz11223344556677889900abacbcbdcdcededfefegfgfhghgihihjijikjkjlklkmlmlnmnmononpopoqpqprqrqsrsrtstsubcbcdcdedefefgfabcadefbghicjkldmnoepqrfstugvwxhyz1i234j567k890laabmbccnddeoeffpgghqhiirjjksklltmmnunoovppqwqrrxsstytuuzvvw0wxx1yyz2z113223434455666777889890091abc2def3ghi4jkl5mno6pqr7stu8vwx9yz11aab2bcc3dd4ee5ff6gg7hh8ii9j0jk1kl2lmm3nnoo4p5pq6qrr7ss8tt9uuvv0wwx1x2yyzz13aba4cbcb5dcdc6dedfef8egf9gfh0ghg1ihi2hji3jik4jkj5lkl6kml7mln8mnm9ono
	-->
	<html xmlns="http://www.w3.org/1999/xhtml" <?php if ( function_exists( 'language_attributes' ) && function_exists( 'is_rtl' ) ) language_attributes(); else echo "dir='$text_direction'"; ?>>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width">
		<?php
		if ( function_exists( 'wp_no_robots' ) ) {
			wp_no_robots();
		}
		?>
		<title><?php _e('WPeMatico Preview Feed', 'wpematico'); ?></title>
		<?php
			if ( 'rtl' == $text_direction ) {
				echo '<style type="text/css"> body { font-family: Tahoma, Arial; } </style>';
			}
			do_action('wpematico_preview_print_styles');
			wp_print_styles();
			do_action('wpematico_preview_print_scripts');
			wp_print_scripts();
		?>
	</head>
	<body>

	<?php endif; // ! did_action( 'admin_head' ) ?>

	<?php 
		
		
		
	?>
		<div id="preview-page">
			<div class="feed-title">
				<h2><?php echo $simplepie->get_title(); ?></h2>
				
			</div>
			<div class="table-nav">
			    <div class="alignleft actions bulkactions">
			        <label for="bulk-action-selector-top" class="screen-reader-text">Selecciona acción en lote</label>
			        <select name="action" id="bulk-action-selector-top">
			            <option value="-1">Acciones en lote</option>
			            <option value="start_campaigns">Start campaigns</option>
			            <option value="stop_campaigns">Stop campaigns</option>
			            <option value="edit" class="hide-if-no-js">Editar</option>
			            <option value="trash">Mover a la papelera</option>
			        </select>
			        <input type="submit" id="doaction" class="button action" value="Aplicar">
			    </div>
			    <h2 class="screen-reader-text">Navegación por el listado de entradas</h2>
			    <div class="tablenav-pages"><span class="displaying-num">23 elementos</span>
			        <span class="pagination-links"><span class="tablenav-pages-navspan" aria-hidden="true">«</span>
			        <span class="tablenav-pages-navspan" aria-hidden="true">‹</span>
			        <span class="paging-input"><label for="current-page-selector" class="screen-reader-text">Página actual</label><input class="current-page" id="current-page-selector" type="text" name="paged" value="1" size="1" aria-describedby="table-paging"><span class="tablenav-paging-text"> de <span class="total-pages">2</span></span>
			        </span>
			        <a class="next-page" href="http://localhost/Trabajo/InkPress/wp-admin/edit.php?post_type=wpematico&amp;paged=2"><span class="screen-reader-text">Página siguiente</span><span aria-hidden="true">›</span></a>
			        <span class="tablenav-pages-navspan" aria-hidden="true">»</span></span>
			    </div>
			</div>
			<div class="table-responsive">
			  <table class="table-preview">
			  	<thead>
			  		<tr>
			  			<th id="cb" class="check-column">
			  				<label class="screen-reader-text" for="cb-select-all-1">Seleccionar todos</label>
			  				<input id="cb-select-all-1" type="checkbox">
			  			</th>
			  			<th>Post</th>
			  			<th>Status</th>
			  			<th>Actions</th>
			  		</tr>
			  	</thead>
			  	<tbody>
			  		<?php 

			  			foreach($simplepie->get_items() as $item) : 
			  				
			  				$is_published = false;
			  				$is_next = false;


			  				if (!empty($posts_fetched[md5($item->get_permalink())])) {
			  					$is_published = true;
			  				}

			  				if (!empty($posts_next[md5($item->get_permalink())])) {
			  					$is_next = true;
			  				}
			  				$description = $item->get_description(); 
			  				$description = strip_tags($description);
			  				if (strlen($description) > 303) {
			  					$description = mb_substr($description, 0, 300);
			  					$description .= '...'; 
			  				}
			  				

			  		?>
					    <tr class="<?php echo (($is_published) ? 'pfeed-published' : ($is_next ? 'pfeed-nextfetch' : 'pfeed-unpublished')); ?>">
					    	<td>
					    		<label class="screen-reader-text" for="cb-select">Seleccionar</label>
				  				<input id="cb-select" type="checkbox">
					    	</td>
					    	<td>
					    		<a href="#" id="pfeed-id" target="_blank"><?php echo $item->get_title(); ?></a>
					    		<span id="pfeed-date">miercoles, 5 de diciembre de 2017 2:32 p.m.</span>
					    		<p><?php echo $description; ?></p>
					    	</td>
					    	<td>
					    		<span class="status <?php echo (($is_published) ? 'published' : ($is_next ? 'nextfetch' : 'unpublished')); ?>"><?php echo (($is_published) ? __('Published', 'wpematico') : ($is_next ? __('Next fetch', 'wpematico') : __('Unpublished', 'wpematico'))); ?></span>
					    	</td>
					    	<td>
					    		<button type="button" class="state_buttons cpanelbutton dashicons dashicons-controls-play" title="Run Once"></button>
					    		<button type="button" disabled="" class="state_buttons cpanelbutton dashicons dashicons-update red"></button><button type="button" class="state_buttons cpanelbutton dashicons dashicons-controls-pause" btn-href="#" title="Stop and deactivate this campaign"></button>
					    	</td>
					    </tr>
					<?php endforeach; ?>
				    
			    </tbody>
			  </table>
			</div>
			<div class="table-nav mt-20">
			    <div class="alignleft actions bulkactions">
			        <label for="bulk-action-selector-top" class="screen-reader-text">Selecciona acción en lote</label>
			        <select name="action" id="bulk-action-selector-top">
			            <option value="-1">Acciones en lote</option>
			            <option value="start_campaigns">Start campaigns</option>
			            <option value="stop_campaigns">Stop campaigns</option>
			            <option value="edit" class="hide-if-no-js">Editar</option>
			            <option value="trash">Mover a la papelera</option>
			        </select>
			        <input type="submit" id="doaction" class="button action" value="Aplicar">
			    </div>
			    <h2 class="screen-reader-text">Navegación por el listado de entradas</h2>
			    <div class="tablenav-pages"><span class="displaying-num">23 elementos</span>
			        <span class="pagination-links"><span class="tablenav-pages-navspan" aria-hidden="true">«</span>
			        <span class="tablenav-pages-navspan" aria-hidden="true">‹</span>
			        <span class="paging-input"><label for="current-page-selector" class="screen-reader-text">Página actual</label><input class="current-page" id="current-page-selector" type="text" name="paged" value="1" size="1" aria-describedby="table-paging"><span class="tablenav-paging-text"> de <span class="total-pages">2</span></span>
			        </span>
			        <a class="next-page" href="http://localhost/Trabajo/InkPress/wp-admin/edit.php?post_type=wpematico&amp;paged=2"><span class="screen-reader-text">Página siguiente</span><span aria-hidden="true">›</span></a>
			        <span class="tablenav-pages-navspan" aria-hidden="true">»</span></span>
			    </div>
			</div>

			
		</div>
		
	</body>
	</html>
	<?php
	die();

	}
}

endif;
wpematico_preview::hooks();

?>