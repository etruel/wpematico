<?php
/**
 * WPeMatico plugin for WordPress
 * WPeMatico_Cron
 * Contains all the methods to run scheduled campaign.
 * @package   wpematico
 * @link      https://github.com/etruel/wpematico
 * @author    Esteban Truelsegaard <etruel@etruel.com>
 * @copyright 2006-2018 Esteban Truelsegaard
 * @license   GPL v2 or later
 */

// don't load directly
if ( !defined('ABSPATH') ){
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( ! class_exists( 'WPeMatico_Cron' ) )  {

	class WPeMatico_Cron {

		public static function hooks() {
			add_action('admin_post_wpematico_cron', array(__CLASS__, 'cron_http'));
			add_action( 'admin_post_nopriv_wpematico_cron', array(__CLASS__, 'cron_http') );

		}

		public static function cron_http() {

			$cfg = WPeMatico::check_options( get_option( 'WPeMatico_Options' ) );

			if($cfg['logexternalcron']) {
				$upload_dir = wp_upload_dir(); 
				//try open log file on uploads dir 
				if($upload_dir['error']==FALSE) {
					$filedir = $upload_dir['basedir'].'/';
				}else {  //if can't open in uploads dir try in this dir
					$filedir = '';	
				}
			}

			/**
			 *  check password only when set_cron_code=true
			 */
			if( $cfg['set_cron_code'] ) {
				if(!isset($_REQUEST['code']) || !( sanitize_text_field( $_REQUEST['code'] )  == $cfg['cron_code']) ) {
					die('Warning! cron.php was called with the wrong password or without one!');
				}
			}
			

			/**
			 * WP Cron deactivated, works in normal way with campaign squeduler cronnextrun
			 */
			$disablewpcron = false;
			if($cfg['disablewpcron']) {
				$disablewpcron = true;
			}
			/**
			 * WPeMatico schedulers deactivated, works running all campaigns at once without check cronnextrun
			 * @todo check parameters to run every campaign individually with a campaign ID and a password
			 */
			$dontruncron = false;
			if($cfg['dontruncron']) { 
				$dontruncron = true;
			}

			/**
			 * 
			 */
			if(!$disablewpcron && !$dontruncron) {
				die( "To use this file you must deactivate cron on WPeMatico Settings Page in Wordpress admin." );
			}
			$file_handle = false;
			$args = array( 'post_type' => 'wpematico', 'orderby' => 'ID', 'order' => 'ASC', 'numberposts' => -1 );
			$campaigns = get_posts( $args );
			foreach( $campaigns as $post ) {
				$campaign = WPeMatico :: get_campaign( $post->ID );
				$activated = $campaign['activated'];
				$cronnextrun = $campaign['cronnextrun'];
				if ( !$activated )
					continue;
				if ( $cronnextrun <= current_time('timestamp') || $dontruncron ) {
					if($cfg['logexternalcron']) {
						@$file_handle = fopen($filedir.sanitize_file_name($post->post_title.".txt.log"), "w+");  //wpemextcron.txt.log
						$msg = 'Running WPeMatico external WP-Cron'."\n";
						self::log($file_handle , $msg.PHP_EOL); 
						$msg = $post->post_title.' '."\n";
						self::log($file_handle , $msg.PHP_EOL); 
						
					}
					$msg = WPeMatico::wpematico_dojob( $post->ID );
					
					if($cfg['logexternalcron']) {
						$msg = strip_tags($msg);
						$msg .= "\n";
						self::log($file_handle , $msg . PHP_EOL); 
						
					}	
				}
			}

			if($cfg['logexternalcron'] && $file_handle != false ) {
				$msg = ' Success !'."\n";
				self::log($file_handle , $msg.PHP_EOL); echo $msg;
				if($file_handle!==FALSE) {
					fclose($file_handle ); 
				}
			}

			die('Completed.');
		}

		public static function log($handle, $msg) {

			if($handle!==FALSE) {
				fwrite($handle , $msg . PHP_EOL);
			}
			echo '<pre>' . $msg . '</pre>';
		}

	}

}
WPeMatico_Cron::hooks();