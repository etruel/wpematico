<?php

/**
 * Description of wp-posts-list
 *
 * @author esteban
 */
class WPeMatico_backend_helpers {

	public static function instance() {
		add_action('admin_init', array(__CLASS__, 'admin_init'));
	}

	public static function admin_init() {
		global $cfg;
		// Get all post types used in campaigns only if it is allowed in settings
		if((!isset($cfg['campaign_in_postslist']) or!$cfg['campaign_in_postslist'])) {
			return;
		}
		$campaigns_data	 = array();
		$args			 = array(
			'orderby'		 => 'ID',
			'order'			 => 'ASC',
			'post_type'		 => 'wpematico',
			'numberposts'	 => -1
		);
		$campaigns		 = get_posts($args);
		foreach($campaigns as $post):
			$campaigns_data				 = WPeMatico::get_campaign($post->ID);
			$campaign_customposttype[]	 = $campaigns_data['campaign_customposttype'];
		endforeach;
		$cpostypes = array_unique($campaign_customposttype);

		$args		 = array('public' => true);
		$output		 = 'names'; // names or objects
		$post_types	 = get_post_types($args, $output);
		foreach($post_types as $post_type) {
			if(in_array($post_type, $cpostypes)) {
				//	add_filter('manage_'.$post_type.'_posts_columns', array( __CLASS__, 'posts_columns_id'), 5);
				add_filter('manage_edit-' . $post_type . '_columns', array(__CLASS__, 'posts_columns_id'), 10);
				add_action('manage_' . $post_type . '_posts_custom_column', array(__CLASS__, 'posts_custom_id_columns'), 5, 2);
				//Order
				add_filter('manage_edit-' . $post_type . '_sortable_columns', array(__CLASS__, 'campaign_column_register_sortable'));
			}
		}
		add_action('parse_query', array(__CLASS__, 'campaign_column_orderby'));
		add_action('admin_head', array(__CLASS__, 'post_campaign_column_width'));
	}

	public static function posts_columns_id($columns) {
		global $cfg;
		$column_campaign_pos = (isset($cfg['column_campaign_pos']) and $cfg['column_campaign_pos'] > 0 ) ? $cfg['column_campaign_pos'] : 2;
		$column_campaign	 = ['post_campaign' => '' . __('Campaign', 'wpematico') . ''];
		// 5to lugar
		$columns			 = array_slice($columns, 0, $column_campaign_pos, true) + $column_campaign + array_slice($columns, $column_campaign_pos, NULL, true);
		//$columns		 = array_merge($columns, $column_campaign);
		return $columns;
	}

	public static function posts_custom_id_columns($column_name, $post_id) {
		if($column_name === 'post_campaign') {
			// get campaign id from post
			$wpe_campaignid	 = get_post_meta($post_id, 'wpe_campaignid', 1);
			$link			 = '<a title="' . __('Edit campaign', 'wpematico') . ' \'' . get_the_title($wpe_campaignid) . '\'" href="' . admin_url('post.php?post=' . $wpe_campaignid . '&action=edit') . '">' . $wpe_campaignid . '</a>';
			echo $link;
		}
	}

	public static function campaign_column_register_sortable($columns) {
		$custom = array(
			'post_campaign' => 'post_campaign'
		);
		return wp_parse_args($custom, $columns);
	}

	public static function campaign_column_orderby() {
		global $pagenow, $post_type;
		if('edit.php' != $pagenow || !isset($_GET['orderby']))
			return;
		if('post_campaign' == $_GET['orderby']) {
			$meta_group = array(
				'key'	 => 'wpe_campaignid',
				'type'	 => 'numeric',
			);
			set_query_var('meta_query', array('sort_column' => 'post_campaign', $meta_group));
			set_query_var('meta_key', 'wpe_campaignid');
			set_query_var('orderby', 'meta_value_num');
		}
	}

	public static function post_campaign_column_width() {
		echo '<style type="text/css">';
		echo '.column-post_campaign { text-align: center !important; width:100px !important; overflow:hidden; }';
		echo '</style>';
	}

}

$WPeMatico_backend_helpers = WPeMatico_backend_helpers::instance();
