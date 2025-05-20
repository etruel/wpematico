<?php

/**
 * @package WPeMatico
 * @author etruel
 * @since 2.5.3
 */
class WPeMatico_backend_helpers {

	/**
	 * Summary of instance
	 * @return void
	 */
	public static function instance() {
		add_action('admin_init', array(__CLASS__, 'column_campaign'));
		add_action('admin_init', array(__CLASS__, 'dashboard_widget'));
		add_action('add_meta_boxes', array(__CLASS__, 'all_posttypes_metaboxes'), 9, 2);
	}

	/**
	 * column_campaign initial function to call the filters to make the campaign column
	 * @global array $cfg
	 * @since 2.5.3
	 */
	public static function column_campaign() {
		global $cfg;
		if ((!isset($cfg['campaign_in_postslist']) or!$cfg['campaign_in_postslist'])) {
			return;
		}
		// Get all post types used in campaigns only if it is allowed in settings
		$campaigns_data = array();
		$campaign_cpt = array();
		$args = array(
			'orderby' => 'ID',
			'order' => 'ASC',
			'post_type' => 'wpematico',
			'numberposts' => -1
		);
		$campaigns = get_posts($args);
		foreach ($campaigns as $post):
			$campaigns_data = WPeMatico::get_campaign($post->ID);
			$campaign_cpt[] = $campaigns_data['campaign_customposttype'];
		endforeach;
		$cpostypes = array_unique($campaign_cpt);

		$args = array('public' => true);
		$output = 'names'; // names or objects
		$post_types = get_post_types($args, $output);
		foreach ($post_types as $post_type) {
			if (in_array($post_type, $cpostypes)) {
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
		$column_campaign = ['post_campaign' => '' . __('Campaign', 'wpematico') . ''];
		// 5to lugar
		$columns = array_slice($columns, 0, $column_campaign_pos, true) + $column_campaign + array_slice($columns, $column_campaign_pos, NULL, true);
		//$columns		 = array_merge($columns, $column_campaign);
		return $columns;
	}

	public static function posts_custom_id_columns($column_name, $post_id) {
		if ($column_name === 'post_campaign') {
			// get campaign id from post
			$wpe_campaignid = get_post_meta($post_id, 'wpe_campaignid', true);
			$link = sprintf(
				'<a title="%s" href="%s">%d</a>',
				esc_attr( sprintf( __('Edit campaign', 'wpematico') . " '%s'", get_the_title( $wpe_campaignid ) ) ),
				esc_url( admin_url( 'post.php?post=' . $wpe_campaignid . '&action=edit' ) ),
				(int) $wpe_campaignid
			);			
			echo wp_kses_post($link);
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
		if ('edit.php' != $pagenow || !isset($_GET['orderby']))
			return;
		if ('post_campaign' == $_GET['orderby']) {
			$meta_group = array(
				'key' => 'wpe_campaignid',
				'type' => 'numeric',
			);
			set_query_var('meta_query', array('sort_column' => 'post_campaign', $meta_group));
			set_query_var('meta_key', 'wpe_campaignid');
			set_query_var('orderby', 'meta_value_num');
		}
	}

	/**
	 * Set the Campaign column width
	 */
	public static function post_campaign_column_width() {
		echo '<style type="text/css">';
		echo '.column-post_campaign { text-align: center !important; width:100px !important; overflow:hidden; }';
		echo '</style>';
	}

	/**
	 * Other WordPress Backend functions and tools
	 */

	/**
	 * 
	 * @param string $post_type
	 * @param object $post 
	 * @since 2.5.3
	 */
	public static function all_posttypes_metaboxes($post_type, $post) {
		global $cfg;
		// if it is allowed in settings
		if ((isset($cfg['disable_metaboxes_wpematico_posts']) && $cfg['disable_metaboxes_wpematico_posts'])) {
			return;
		}
		$campaign_id = get_post_meta($post->ID, 'wpe_campaignid', true);
		if (!empty($campaign_id)) {
			add_meta_box(
					'wpematico-all-meta-box',
					__('WPeMatico Campaign Info', 'wpematico'),
					array(__CLASS__, 'wpematico_info_metabox'),
					$post_type,
					'normal',
					'default'
			);
		}
	}


	public static function wpematico_info_metabox(){
		global $post;
		
		$meta_data = apply_filters('wpematico_get_array_metadata', array(),$post->ID);

		$campaign_id = get_post_meta($post->ID, 'wpe_campaignid', true);
		$title = get_the_title($campaign_id) ? get_the_title($campaign_id) : $campaign_id;
		
		if (empty($meta_data)) {
			// Get the specific values from $meta_values
			$meta_data['wpe_feed'] = get_post_meta($post->ID, 'wpe_feed', true);
			$meta_data['wpe_sourcepermalink'] =  get_post_meta($post->ID, 'wpe_sourcepermalink', true);
		}
	
		// Start rendering the HTML for the meta box
		echo '<span class="description">' . esc_html__('All links are no-follow and open in a new browser tab.', 'wpematico') . '</span>';
		?>
		<style type="text/css">
			#wpematico-all-meta-box h2 {
				background-color: orange;
			}
			.wpematico-data-table a {
				text-decoration: none;
			}
			.wpematico-data-table a:hover {
				text-decoration: underline;
			}
			.wpematico-data-table td:first-child {
				padding-right: 10px;
				text-align: right;
			}
			.wpematico-data-table tr {
				height: 30px;
				vertical-align: middle;
			}
		</style>
		<?php
			// Render the meta data table with editable fields
			echo '<table class="wpematico-data-table">
			<tr>
				<td><b>' . esc_html__('Published by Campaign', 'wpematico') . ':</b></td>
				<td><a title="' . esc_attr__('Edit the campaign.', 'wpematico') . '" href="' . esc_url( admin_url('post.php?post=' . $campaign_id . '&action=edit') ) . '" target="_blank">' . esc_html($title) . '</a></td>
			</tr>
			<tr>
				<td><b>' . esc_html__('From feed', 'wpematico') . ':</b></td>
				<td><span id="wpe_feed" class="editable-field"><a title="' . esc_attr__('Open the feed URL in the browser.', 'wpematico') . '" href="' . esc_url($meta_data['wpe_feed']) . '" rel="nofollow" target="_blank">' . esc_html($meta_data['wpe_feed']) . '</a></span></td>
			</tr>
			<tr>
				<td><b>' . esc_html__('Source permalink', 'wpematico') . ':</b></td>
				<td><span id="wpe_sourcepermalink" class="editable-field"><a title="' . esc_attr__('Go to the source website to see the original content.', 'wpematico') . '" href="' . esc_url($meta_data['wpe_sourcepermalink']) . '" rel="nofollow" target="_blank">' . esc_html($meta_data['wpe_sourcepermalink']) . '</a></span></td>
			</tr>';

			// Call the action to insert additional fields (like the featured image and buttons)
			do_action('wpematico_add_info_props', $meta_data);

			echo '</table>';
	}

	/**
	 * Print Dashboard widget if allowed in Settings and the correct user role
	 * @global array $cfg
	 */
	public static function dashboard_widget() {
		global $cfg, $current_user;
		//add Dashboard widget
		if (!isset($cfg['disabledashboard']) || !$cfg['disabledashboard']) {
			wp_get_current_user();
			$user_object = new WP_User($current_user->ID);
			$roles = $user_object->roles;
			$display = false;
			if (!isset($cfg['roles_widget']) || !is_array($cfg['roles_widget']))
				$cfg['roles_widget'] = array("administrator" => "administrator");
			foreach ($roles as $cur_role) {
				if (array_search($cur_role, $cfg['roles_widget'])) {
					$display = true;
				}
			}
			if ($current_user->ID && ( $display == true ) && current_user_can(get_post_type_object('wpematico')->cap->edit_others_posts)) {
				add_action('wp_dashboard_setup', array(__CLASS__, 'wpematico_add_dashboard'));
			}
		}
	}

	//add dashboard widget
	public static function wpematico_add_dashboard() {
		wp_add_dashboard_widget('wpematico_widget', esc_html__('WPeMatico Summary', 'wpematico'), array(__CLASS__, 'wpematico_dashboard_widget'));
	}

	//Dashboard widget
	public static function wpematico_dashboard_widget() {
		$campaigns = WPeMatico::get_campaigns();
		?><style type="text/css"> 
			#wpematico_widget h2,
			#wpematico_widget .postbox-header{
				background-color: #ef8e2f;
			}
			.wpematico_widget a {
				text-decoration: none;
			}
			.wpematico_widget a:hover {
				text-decoration: underline;
			}
		</style><?php
		echo '<div style="color:white; background-color: #f57900;border: 1px solid #DDDDDD; height: 20px; margin: -10px -10px 2px; padding: 5px 10px 0px;">';
		echo '<strong>' . esc_html__('Last five Processed Campaigns:', 'wpematico') . '</strong>';
		echo '<span style="float:right;"><a href="' . esc_url( admin_url('edit.php?post_type=wpematico') ) . '" title="' . esc_attr__('Go to Campaigns List', 'wpematico') . '">' . esc_html__('See All', 'wpematico') . '</span></div>';
		@$campaigns2 = WPeMatico::filter_by_value($campaigns, 'lastrun', '');
		WPeMatico::array_sort($campaigns2, '!lastrun');
		if (is_array($campaigns2)) {
			$count = 0;
			foreach ($campaigns2 as $key => $campaign_data) {
				echo '<a href="' . esc_url(admin_url('post.php?post=' . $campaign_data['ID'] . '&action=edit')) . '" title="' . esc_attr__('Edit Campaign', 'wpematico') . '">';
				if ($campaign_data['lastrun']) {
					echo " <i><strong>" . esc_html($campaign_data['campaign_title']) . "</i></strong>, ";
					echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $campaign_data['lastrun'])) . ', <i>';

					if ($campaign_data['lastpostscount'] > 0)
					/* translators: %s :integer. */
						echo ' <span style="color:green;">' . sprintf(esc_html__('Processed Posts: %s', 'wpematico'), esc_html($campaign_data['lastpostscount'])) . '</span>, ';
					else
					/* translators: %s :integer. */
						echo ' <span style="color:red;">' . sprintf(esc_html__('Processed Posts: %s', 'wpematico'), '0') . '</span>, ';

					if ($campaign_data['lastruntime'] < 10)
					/* translators: %s :seconds (integer). */
						echo ' <span style="color:green;">' . sprintf(esc_html__('Fetch done in %s sec.', 'wpematico'), esc_html($campaign_data['lastruntime'])) . '</span>';
					else
					/* translators: %s :seconds (integer). */
						echo ' <span style="color:red;">' . sprintf(esc_html__('Fetch done in %s sec.', 'wpematico'), esc_html($campaign_data['lastruntime'])) . '</span>';
				}
				echo '</i></a><br />';
				$count++;
				if ($count >= 5)
					break;
			}
		}
		unset($campaigns2);
		echo '<br />';
		echo '<div style="color:white; background-color: #f57900;border: 1px solid #DDDDDD; height: 20px; margin: -10px -10px 2px; padding: 5px 10px 0px;">';
		echo '<strong>' . esc_html__('Next Scheduled Campaigns:', 'wpematico') . '</strong>';
		echo '</div>';
		echo '<ul style="list-style: circle inside none; margin-top: 2px; margin-left: 9px;">';
		WPeMatico::array_sort($campaigns, 'cronnextrun');
		foreach ($campaigns as $key => $campaign_data) {
			if ($campaign_data['activated']) {
				echo '<li><a href="' . esc_url( admin_url('post.php?post=' . $campaign_data['ID'] . '&action=edit') ) . '" title="' . esc_attr__('Edit Campaign', 'wpematico') . '">';
				echo '<strong>' . esc_html($campaign_data['campaign_title']) . '</strong>, ';
				if ($campaign_data['starttime'] > 0 and empty($campaign_data['stoptime'])) {
					$runtime = current_time('timestamp') - $campaign_data['starttime'];
					echo esc_html__('Running since:', 'wpematico') . ' ' . esc_html($runtime) . ' ' . esc_html__('sec.', 'wpematico');
				} elseif ($campaign_data['activated']) {
					echo esc_html(date_i18n((get_option('date_format') . ' ' . get_option('time_format')), $campaign_data['cronnextrun']));
				}
				echo '</a></li>';
			}
		}
		$campaigns = WPeMatico::filter_by_value($campaigns, 'activated', '');
		if (empty($campaigns))
			echo '<i>' . esc_html__('None', 'wpematico') . '</i><br />';
		echo '</ul>';
	}

}

WPeMatico_backend_helpers::instance();
