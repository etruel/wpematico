<?php
// don't load directly 
if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

/**
 * WPeMatico Pro Extra Settings Class 
 * This class is used to add the Professional Extra Settings 
 * @since 2.2
 */
if (!class_exists('WPeMatico_Tools')) :

	class WPeMatico_Tools
	{

		public static function hooks()
		{
			add_action('wpematico_tools_section_tools', [__CLASS__, 'tools_form']);
			add_action('wpematico_tools_tab_debug_log', [__CLASS__, 'debug_log_file']);
			add_action('wpematico_tools_section_debug_file', 'wpematico_tools_section_debug_file');
			add_action('wpematico_tools_section_danger_zone', [__CLASS__, 'wpematico_tools_section_danger_zone']);
			add_action('wpematico_tools_section_feed_list', [__CLASS__, 'wpematico_tools_section_feed_list']);
			// Gated by the Plugin Importers module: without it the toolkit is not
			// loaded, so don't answer the section either — a direct URL to
			// &section=migration would otherwise render an empty panel.
			if (class_exists('WPeMatico_Migration_Toolkit')) {
				add_action('wpematico_tools_section_migration', [__CLASS__, 'wpematico_tools_section_migration']);
			}

			add_action('admin_post_set_danger_data', [__CLASS__, 'wpematico_save_danger_data']);

			// current_screen, not admin_init: the help tabs go on the screen being
			// rendered, and get_current_screen() is not available yet at admin_init.
			add_action('current_screen', [__CLASS__, 'tools_help']);
			// Same hook for the Screen Options box: it fires before the screen meta is
			// rendered, so add_screen_option() still lands.
			add_action('current_screen', [__CLASS__, 'feed_list_screen_options']);
			// Core skips saving any non-standard screen option unless a filter answers.
			add_filter('set_screen_option_' . self::FEEDS_PER_PAGE_OPTION, [__CLASS__, 'save_feed_list_per_page'], 10, 3);
			add_action('wp_ajax_download_wpematico_log', [__CLASS__, 'download_debug_log']);
		}

		/** User meta holding the Feed List page size (Screen Options). */
		const FEEDS_PER_PAGE_OPTION = 'wpematico_feeds_per_page';

		/**
		 * Register the native Screen Options box for the Feed List.
		 *
		 * Runs on current_screen, which fires before the screen meta is rendered, so
		 * add_screen_option() still lands. Only on this section: the other Tools
		 * sections have nothing to paginate.
		 *
		 * @since 2.9
		 * @return void
		 */
		public static function feed_list_screen_options()
		{
			if (!isset($_GET['page']) || $_GET['page'] !== 'wpematico_tools') {
				return;
			}
			if (isset($_GET['tab']) && $_GET['tab'] !== 'tools') {
				return;
			}
			if (isset($_GET['section']) && $_GET['section'] !== 'feed_list') {
				return;
			}
			add_screen_option('per_page', array(
				'option'  => self::FEEDS_PER_PAGE_OPTION,
				'default' => 20,
				'label'	  => __('Number of feeds per page:', 'wpematico'),
			));
		}

		/**
		 * Persist the page size. Core skips any non-standard option whose filter
		 * returns false, so without this the box would silently never save.
		 *
		 * @since 2.9
		 * @param mixed  $status Value core would save (false = skip).
		 * @param string $option Option name.
		 * @param int    $value  Submitted value.
		 * @return mixed
		 */
		public static function save_feed_list_per_page($status, $option, $value)
		{
			if (self::FEEDS_PER_PAGE_OPTION !== $option) {
				return $status;
			}
			$value = (int) $value;

			return ($value >= 1 && $value <= 999) ? $value : $status;
		}

		/**
		 * Page size in effect for the Feed List.
		 *
		 * @since 2.9
		 * @return int
		 */
		private static function get_feeds_per_page()
		{
			$per_page = (int) get_user_option(self::FEEDS_PER_PAGE_OPTION);
			if ($per_page < 1) {
				$per_page = 20;
			}

			return (int) apply_filters(self::FEEDS_PER_PAGE_OPTION, $per_page);
		}

		/**
		 * Flatten every campaign into one row per feed URL.
		 *
		 * Reads each campaign once — the previous version asked for the same
		 * campaign_data meta three times per row, once for the feeds, once for the
		 * status and once for the date.
		 *
		 * @since 2.9
		 * @return array List of rows.
		 */
		private static function get_feed_rows()
		{
			$rows = array();

			foreach (WPeMatico::get_campaigns() as $campaign) {
				if (empty($campaign['campaign_feeds']) || !is_array($campaign['campaign_feeds'])) {
					continue;
				}

				$campaign_id = isset($campaign['ID']) ? (int) $campaign['ID'] : 0;
				$lastrun	 = (int) get_post_meta($campaign_id, 'lastrun', true);
				if (!$lastrun && !empty($campaign['lastrun'])) {
					$lastrun = (int) $campaign['lastrun'];
				}

				foreach ($campaign['campaign_feeds'] as $feed_url) {
					$feed_url = trim((string) $feed_url);
					if ('' === $feed_url) {
						continue;
					}
					$rows[] = array(
						'feed'		 => $feed_url,
						'campaign'	 => isset($campaign['campaign_title']) ? (string) $campaign['campaign_title'] : '',
						'campaign_id' => $campaign_id,
						'active'	 => !empty($campaign['activated']),
						'lastrun'	 => $lastrun,
					);
				}
			}

			return $rows;
		}

		/**
		 * Keep the rows whose feed URL or campaign name contain the term.
		 *
		 * @since 2.9
		 * @param array  $rows   Rows from get_feed_rows().
		 * @param string $search Term, already sanitized.
		 * @return array
		 */
		private static function search_feed_rows($rows, $search)
		{
			$found = array();
			foreach ($rows as $row) {
				if (false !== stripos($row['feed'], $search) || false !== stripos($row['campaign'], $search)) {
					$found[] = $row;
				}
			}

			return $found;
		}

		/**
		 * Sort the flat row list by a column.
		 *
		 * @since 2.9
		 * @param array  $rows    Rows from get_feed_rows().
		 * @param string $orderby Column key, '' for the natural order.
		 * @param string $order   'asc' or 'desc'.
		 * @return array
		 */
		private static function sort_feed_rows($rows, $orderby, $order)
		{
			if ('' === $orderby) {
				return $rows;
			}

			$dir = ('desc' === $order) ? -1 : 1;
			usort($rows, function ($a, $b) use ($orderby, $dir) {
				switch ($orderby) {
					case 'lastrun':
						$cmp = $a['lastrun'] <=> $b['lastrun'];
						break;
					case 'status':
						$cmp = ((int) $a['active']) <=> ((int) $b['active']);
						break;
					case 'campaign':
						$cmp = strcasecmp($a['campaign'], $b['campaign']);
						break;
					case 'feed':
					default:
						$cmp = strcasecmp($a['feed'], $b['feed']);
						break;
				}
				// Feed URL as the tiebreaker keeps the order stable across pages when
				// many rows share a value (every row of one campaign, typically).
				if (0 === $cmp && 'feed' !== $orderby) {
					return strcasecmp($a['feed'], $b['feed']);
				}

				return $cmp * $dir;
			});

			return $rows;
		}

		/**
		 * Current URL stripped of everything that must not be carried into a sort or
		 * page link.
		 *
		 * Order matters: remove_query_arg() runs the existing query through
		 * urlencode_deep(), so anything added afterwards survives untouched while
		 * anything added before gets re-encoded — which turned paginate_links()'
		 * "%#%" placeholder into "%25#%".
		 *
		 * @since 2.9
		 * @return string
		 */
		private static function feed_list_base_url()
		{
			return remove_query_arg(array_merge(wpematico_tools_transient_args(), array('paged')));
		}

		/**
		 * Pagination bar, in the same markup WP_List_Table::pagination() emits, so it
		 * looks and behaves like the one on the Campaigns screen: item count, first /
		 * previous arrows, an editable "N of M" box, next / last.
		 *
		 * @since 2.9
		 * @param string $which       'top' or 'bottom'.
		 * @param int    $paged       Current page.
		 * @param int    $total_pages Number of pages.
		 * @param int    $total_feeds Number of rows in total.
		 * @param string $search      Applied search term ('top' only).
		 * @param array  $helptip     Tooltip texts ('top' only).
		 * @return void
		 */
		private static function feed_list_tablenav($which, $paged, $total_pages, $total_feeds, $search = '', $helptip = array())
		{
			$base	 = self::feed_list_base_url();
			$counter = sprintf(
					/* translators: %s Number of feeds, already formatted. */
					_n('%s feed', '%s feeds', $total_feeds, 'wpematico'),
					number_format_i18n($total_feeds)
			);

			$links = array();
			// Core renders the unavailable ends as disabled spans, not as missing
			// elements, so the bar keeps its width as you page through.
			$links[] = self::feed_list_page_link($base, 1, $paged > 1, '&laquo;', __('First page', 'wpematico'), 'first-page');
			$links[] = self::feed_list_page_link($base, $paged - 1, $paged > 1, '&lsaquo;', __('Previous page', 'wpematico'), 'prev-page');

			if ('bottom' === $which) {
				$current_html = '<span class="screen-reader-text">' . esc_html__('Current Page', 'wpematico') . '</span>' .
						'<span id="table-paging" class="paging-input"><span class="tablenav-paging-text">' . number_format_i18n($paged);
			} else {
				$current_html = '<label for="feed-list-paged" class="screen-reader-text">' . esc_html__('Current Page', 'wpematico') . '</label>' .
						'<input class="current-page" id="feed-list-paged" type="text" name="paged" value="' . esc_attr($paged) . '" size="1" aria-describedby="feed-list-paging-text" />' .
						'<span class="tablenav-paging-text">';
			}
			$current_html .= ' <span class="tablenav-paging-text">' .
					/* translators: %s Total number of pages, already formatted. */
					sprintf(esc_html__('of %s', 'wpematico'), '<span class="total-pages">' . number_format_i18n($total_pages) . '</span>') .
					'</span></span>';

			$links[] = '<span class="paging-input" id="feed-list-paging-text">' . $current_html . '</span>';
			$links[] = self::feed_list_page_link($base, $paged + 1, $paged < $total_pages, '&rsaquo;', __('Next page', 'wpematico'), 'next-page');
			$links[] = self::feed_list_page_link($base, $total_pages, $paged < $total_pages, '&raquo;', __('Last page', 'wpematico'), 'last-page');
			?>
			<div class="tablenav <?php echo esc_attr($which); ?>">
				<?php if ('top' === $which) : ?>
					<?php // Left side of the same row WP gives to the bulk-action controls, so
					// the width the pagination leaves free is put to use. ?>
					<div class="alignleft actions wpe-feed-list-searchbar">
						<p class="search-box">
							<label class="screen-reader-text" for="feed-list-search"><?php esc_html_e('Search feeds:', 'wpematico'); ?></label>
							<input type="search" id="feed-list-search" name="s" value="<?php echo esc_attr($search); ?>" autocomplete="off"
								   placeholder="<?php esc_attr_e('Filter feeds...', 'wpematico'); ?>" />
							<input type="submit" id="feed-list-search-submit" class="button" value="<?php esc_attr_e('Search feeds', 'wpematico'); ?>" />
							<?php wpematico_help_tip(isset($helptip['feed_list_filter']) ? $helptip['feed_list_filter'] : ''); ?>
						</p>

						<?php // Two different scopes, so each one says out loud what it covers. ?>
						<div class="wpe-feed-list-searchinfo">
							<?php if ('' !== $search) : ?>
								<span class="wpe-search-applied">
									<?php
									printf(
											/* translators: 1: number of matches, 2: search term. */
											esc_html(_n('%1$s feed matches %2$s', '%1$s feeds match %2$s', $total_feeds, 'wpematico')),
											'<strong>' . esc_html(number_format_i18n($total_feeds)) . '</strong>',
											'<strong>&ldquo;' . esc_html($search) . '&rdquo;</strong>'
									);
									?>
									<a href="<?php echo esc_url(remove_query_arg(array('s', 'paged'), self::feed_list_base_url())); ?>"><?php esc_html_e('Clear search', 'wpematico'); ?></a>
								</span>
							<?php endif; ?>
							<span class="wpe-filter-live" hidden></span>
							<span class="wpe-filter-hint" hidden></span>
						</div>
					</div>
				<?php endif; ?>
				<div class="tablenav-pages<?php echo ($total_pages < 2) ? ' one-page' : ''; ?>">
					<span class="displaying-num"><?php echo esc_html($counter); ?></span>
					<?php if ($total_pages > 1) : ?>
						<span class="pagination-links"><?php echo implode("\n", $links); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above. ?></span>
					<?php endif; ?>
				</div>
				<br class="clear" />
			</div>
			<?php
		}

		/**
		 * One arrow of the pagination bar, as a link or as a disabled span.
		 *
		 * @since 2.9
		 * @param string $base    Base URL.
		 * @param int    $page    Page it points to.
		 * @param bool   $enabled Whether it is reachable.
		 * @param string $glyph   Arrow entity.
		 * @param string $label   Accessible label.
		 * @param string $class   Core class for this arrow.
		 * @return string
		 */
		private static function feed_list_page_link($base, $page, $enabled, $glyph, $label, $class)
		{
			if (!$enabled) {
				return '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">' . $glyph . '</span>';
			}

			return '<a class="' . esc_attr($class) . ' button" href="' . esc_url(add_query_arg('paged', (int) $page, $base)) . '">' .
					'<span class="screen-reader-text">' . esc_html($label) . '</span>' .
					'<span aria-hidden="true">' . $glyph . '</span></a>';
		}

		/**
		 * Render one sortable column header, with its help tooltip.
		 *
		 * @since 2.9
		 * @param string $key     Column key, '' for a non-sortable column.
		 * @param string $label   Visible label.
		 * @param string $tip     Already html-entitied tip, '' for none.
		 * @param string $orderby Column currently sorted.
		 * @param string $order   Current direction.
		 * @param string $classes Extra CSS classes.
		 * @return void
		 */
		private static function feed_list_column_header($key, $label, $tip, $orderby, $order, $classes = '')
		{
			$css = trim('manage-column ' . $classes);

			if ('' === $key) {
				echo '<th scope="col" class="' . esc_attr($css) . '">' . esc_html($label);
				wpematico_help_tip($tip);
				echo '</th>';
				return;
			}

			// Classes, aria and markup follow WP_List_Table::print_column_headers() to the
			// letter, because the arrows are drawn by core CSS: a single .sorting-indicator
			// (the pre-6.3 markup) is styled by nothing and shows no arrow at all.
			$aria = '';
			if ($orderby === $key) {
				// The sorted column is classed with the order on screen, and its link
				// offers the opposite one.
				$next	 = ('asc' === $order) ? 'desc' : 'asc';
				$css	.= ' sorted ' . $order;
				$aria	 = ('asc' === $order) ? ' aria-sort="ascending"' : ' aria-sort="descending"';
				$sr_text = '';
			} else {
				// Ascending first, and the class is the opposite of what the link does.
				$next	 = 'asc';
				$css	.= ' sortable desc';
				$sr_text = ' <span class="screen-reader-text">' . esc_html__('Sort ascending.', 'wpematico') . '</span>';
			}

			// Sorting resets to the first page: feed_list_base_url() already dropped it.
			$url = add_query_arg(array('orderby' => $key, 'order' => $next), self::feed_list_base_url());

			echo '<th scope="col" class="' . esc_attr($css) . '"' . $aria . '>';
			echo '<a href="' . esc_url($url) . '">' .
			'<span>' . esc_html($label) . '</span>' .
			'<span class="sorting-indicators">' .
			'<span class="sorting-indicator asc" aria-hidden="true"></span>' .
			'<span class="sorting-indicator desc" aria-hidden="true"></span>' .
			'</span>' . $sr_text . '</a>';
			wpematico_help_tip($tip);
			echo '</th>';
		}

		public static function wpematico_tools_section_feed_list()
		{
			if (!isset($_GET['page']) || $_GET['page'] !== 'wpematico_tools') {
				return;
			}
			// 'tools' is the default tab and Feed List is now the default section, so
			// the plain Tools menu entry (no tab, no section in the URL) lands here.
			// Demanding both explicitly rendered an empty panel instead.
			if (isset($_GET['tab']) && $_GET['tab'] !== 'tools') {
				return;
			}
			if (isset($_GET['section']) && $_GET['section'] !== 'feed_list') {
				return;
			}
			if (!current_user_can('manage_options')) {
				wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'wpematico'));
			}

			$helptip = wpematico_help_feed_list('tips');

			$sortable = array('feed', 'campaign', 'status', 'lastrun');
			$orderby  = (isset($_GET['orderby']) && in_array($_GET['orderby'], $sortable, true)) ? $_GET['orderby'] : '';
			$order	  = (isset($_GET['order']) && 'desc' === strtolower($_GET['order'])) ? 'desc' : 'asc';

			$all_rows	= self::get_feed_rows();
			$all_count	= count($all_rows);

			// Searching happens before counting, sorting and paging, so the result set
			// is what gets paginated. The instant filter below only ever narrows the
			// page in front of you; this is the one that sees every feed.
			$search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
			$rows	= ('' === $search) ? $all_rows : self::search_feed_rows($all_rows, $search);

			$rows		 = self::sort_feed_rows($rows, $orderby, $order);
			$total_feeds = count($rows);

			$per_page	 = self::get_feeds_per_page();
			$total_pages = max(1, (int) ceil($total_feeds / $per_page));
			$paged		 = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
			$paged		 = min($paged, $total_pages);
			$offset		 = ($paged - 1) * $per_page;
			$page_rows	 = array_slice($rows, $offset, $per_page);

			$datetime_format = get_option('date_format') . ' ' . get_option('time_format');
			?>
			<div class="wpe_wrap wpematico-feed-list">
				<h2 class="wpe-feed-list-title">
					<?php esc_html_e('Feed List', 'wpematico'); ?>
					<?php wpematico_help_tip(isset($helptip['feed_list_intro']) ? $helptip['feed_list_intro'] : ''); ?>
				</h2>
				<p class="description">
					<?php esc_html_e('Every feed URL configured on this site, with the campaign that imports it. One row per feed.', 'wpematico'); ?>
				</p>

				<?php if (empty($rows)) : ?>
					<div class="notice notice-warning inline">
						<p><?php esc_html_e('No feeds found. Add a feed URL to a campaign and it will be listed here.', 'wpematico'); ?></p>
					</div>
				<?php else : ?>

					<?php // GET form so the "of N" page box submits like it does on any WP list table. ?>
					<form method="get" class="wpe-feed-list-form">
						<?php
						foreach (array('page', 'tab', 'section', 'orderby', 'order') as $keep) {
							if (isset($_GET[$keep]) && '' !== $_GET[$keep]) {
								echo '<input type="hidden" name="' . esc_attr($keep) . '" value="' . esc_attr(sanitize_text_field(wp_unslash($_GET[$keep]))) . '" />';
							}
						}
						?>

						<?php self::feed_list_tablenav('top', $paged, $total_pages, $total_feeds, $search, $helptip); ?>

						<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<?php self::feed_list_headers($helptip, $orderby, $order); ?>
							</tr>
						</thead>

						<tbody>
							<?php
							$n = $offset;
							foreach ($page_rows as $row) :
								$n++;
								$edit_link = get_edit_post_link($row['campaign_id']);
								// Precomputed haystack so the instant filter never has to walk cells.
								$haystack = strtolower($row['feed'] . ' ' . $row['campaign']);
								?>
								<tr data-filter="<?php echo esc_attr($haystack); ?>">
									<td class="column-num" data-colname="#"><?php echo (int) $n; ?></td>
									<td class="column-primary" data-colname="<?php esc_attr_e('Feed URL', 'wpematico'); ?>">
										<?php // esc_url() like the campaign editor does on the same value, so a
										// feed stored without a scheme reads the same on both screens. ?>
										<code><?php echo esc_url($row['feed']); ?></code>
										<button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e('Show more details', 'wpematico'); ?></span></button>
									</td>
									<td data-colname="<?php esc_attr_e('Campaign', 'wpematico'); ?>">
										<?php if ($edit_link) : ?>
											<strong><a href="<?php echo esc_url($edit_link); ?>"><?php echo esc_html($row['campaign']); ?></a></strong>
										<?php else : ?>
											<strong><?php echo esc_html($row['campaign']); ?></strong>
										<?php endif; ?>
									</td>
									<td class="column-status" data-colname="<?php esc_attr_e('Status', 'wpematico'); ?>">
										<?php if ($row['active']) : ?>
											<span class="dashicons dashicons-yes-alt wpe-status-active"></span>
											<span class="status-label"><?php esc_html_e('Active', 'wpematico'); ?></span>
										<?php else : ?>
											<span class="dashicons dashicons-dismiss wpe-status-inactive"></span>
											<span class="status-label"><?php esc_html_e('Inactive', 'wpematico'); ?></span>
										<?php endif; ?>
									</td>
									<td class="column-lastrun" data-colname="<?php esc_attr_e('Last run', 'wpematico'); ?>">
										<?php if ($row['lastrun']) : ?>
											<span title="<?php
											echo esc_attr(sprintf(
															/* translators: %s Human readable time difference, e.g. "2 hours". */
															__('%s ago', 'wpematico'),
															human_time_diff($row['lastrun'])
											));
											?>"><?php echo esc_html(wp_date($datetime_format, $row['lastrun'])); ?></span>
										<?php else : ?>
											<span class="wpe-never"><?php esc_html_e('Never', 'wpematico'); ?></span>
										<?php endif; ?>
									</td>
									<td class="column-actions" data-colname="<?php esc_attr_e('Actions', 'wpematico'); ?>">
										<?php if ($edit_link) : ?>
											<a href="<?php echo esc_url($edit_link); ?>" class="wpe-row-action"
											   title="<?php esc_attr_e('Edit campaign', 'wpematico'); ?>">
												<span class="dashicons dashicons-edit"></span>
												<span class="screen-reader-text"><?php esc_html_e('Edit campaign', 'wpematico'); ?></span>
											</a>
										<?php endif; ?>
										<a href="<?php echo esc_url(self::feed_viewer_url($row['feed'])); ?>" class="wpe-row-action" target="_blank"
										   title="<?php esc_attr_e('Inspect this feed in the Feed Viewer', 'wpematico'); ?>">
											<span class="dashicons dashicons-visibility"></span>
											<span class="screen-reader-text"><?php esc_html_e('Inspect this feed in the Feed Viewer', 'wpematico'); ?></span>
										</a>
										<a href="<?php echo esc_url($row['feed']); ?>" class="wpe-row-action" target="_blank" rel="noopener noreferrer"
										   title="<?php esc_attr_e('Open URL in a new browser tab', 'wpematico'); ?>">
											<span class="dashicons dashicons-external"></span>
											<span class="screen-reader-text"><?php esc_html_e('Open URL in a new browser tab', 'wpematico'); ?></span>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>

						<tfoot>
							<tr>
								<?php self::feed_list_headers($helptip, $orderby, $order); ?>
							</tr>
						</tfoot>
					</table>

						<?php self::feed_list_tablenav('bottom', $paged, $total_pages, $total_feeds); ?>
					</form>

				<?php endif; ?>
			</div>

			<script type="text/javascript">
				jQuery(function ($) {
					if ($.fn.tipTip) {
						$('.wpematico-feed-list .help_tip').tipTip({maxWidth: '350px', edgeOffset: 5, fadeIn: 50, fadeOut: 50, keepAlive: true, defaultPosition: 'bottom'});
					}

					var $box	 = $('#feed-list-search'),
						$rows	 = $('.wpematico-feed-list tbody tr'),
						$live	 = $('.wpe-filter-live'),
						$hint	 = $('.wpe-filter-hint'),
						$tbody	 = $('.wpematico-feed-list tbody'),
						applied	 = <?php echo wp_json_encode($search); ?>,
						allCount = <?php echo (int) $all_count; ?>,
						onPage	 = $rows.length,
						strings	 = {
							/* translators: 1: matching rows, 2: rows on this page. */
							live: <?php echo wp_json_encode(__('Showing %1$s of %2$s on this page.', 'wpematico')); ?>,
							/* translators: %s: total number of feeds. */
							hint: <?php echo wp_json_encode(__('Press Enter to search all %s feeds.', 'wpematico')); ?>
						};

					if (!$box.length) {
						return;
					}

					function sprintf(template, values) {
						return template.replace(/%(\d)\$s|%s/g, function (m, n) {
							return values[n ? n - 1 : 0];
						});
					}

					// Instant narrowing of the rows already on screen. It cannot see the
					// other pages, so whenever the typed text is not what the server
					// already searched for, the hint offers the global search.
					function filter() {
						var needle = $.trim($box.val()).toLowerCase(),
							shown  = 0;

						$rows.each(function () {
							var row = $(this),
								hit = (needle === '' || (row.attr('data-filter') || '').indexOf(needle) >= 0);
							row.toggle(hit);
							if (hit) {
								shown++;
								// Re-stripe: nth-child keeps counting the hidden rows.
								row.toggleClass('wpe-odd', shown % 2 === 1);
							}
						});
						$tbody.toggleClass('wpe-filtered', needle !== '');

						if (needle === '' || shown === onPage) {
							$live.attr('hidden', true).text('');
						} else {
							$live.removeAttr('hidden').text(sprintf(strings.live, [shown, onPage]));
						}

						if (needle !== '' && needle !== applied.toLowerCase() && allCount > onPage) {
							$hint.removeAttr('hidden').text(sprintf(strings.hint, [allCount]));
						} else {
							$hint.attr('hidden', true).text('');
						}
					}

					$box.on('keyup search', function (e) {
						if (e.keyCode === 27) {   // Esc clears, same as the campaign editor.
							$box.val('');
						}
						filter();
					});

					// A new search starts from the first page: without this, searching from
					// page 3 lands on a page 3 that may not exist for the new result set.
					$box.closest('form').on('submit', function () {
						$('#feed-list-paged').val(1);
					});

					filter();
				});
			</script>
			<?php
		}

		/**
		 * The header row, shared by thead and tfoot.
		 *
		 * @since 2.9
		 * @param array  $helptip Tooltip texts.
		 * @param string $orderby Column currently sorted.
		 * @param string $order   Current direction.
		 * @return void
		 */
		private static function feed_list_headers($helptip, $orderby, $order)
		{
			$tip = function ($key) use ($helptip) {
				return isset($helptip[$key]) ? $helptip[$key] : '';
			};

			self::feed_list_column_header('', '#', '', $orderby, $order, 'column-num');
			self::feed_list_column_header('feed', __('Feed URL', 'wpematico'), $tip('feed_list_url'), $orderby, $order, 'column-primary');
			self::feed_list_column_header('campaign', __('Campaign', 'wpematico'), $tip('feed_list_campaign'), $orderby, $order);
			self::feed_list_column_header('status', __('Status', 'wpematico'), $tip('feed_list_status'), $orderby, $order, 'column-status');
			self::feed_list_column_header('lastrun', __('Last run', 'wpematico'), $tip('feed_list_lastrun'), $orderby, $order, 'column-lastrun');
			self::feed_list_column_header('', __('Actions', 'wpematico'), $tip('feed_list_actions'), $orderby, $order, 'column-actions');
		}

		/**
		 * Link that opens a feed URL already loaded in the Feed Viewer.
		 *
		 * The viewer reads ?feedlink= and fetches it on load, so the row action lands
		 * on the result instead of an empty form.
		 *
		 * @since 2.9
		 * @param string $feed_url Feed to inspect.
		 * @return string
		 */
		public static function feed_viewer_url($feed_url)
		{
			return add_query_arg(array(
				'page'	   => 'wpematico_tools',
				'tab'	   => 'tools',
				'section'  => 'feed_viewer',
				'feedlink' => $feed_url,
					), admin_url('admin.php'));
		}

		/**
		 * Display the debug info tab
		 *
		 * @since       1.2.4
		 * @return      void
		 */
		public static function wpematico_tools_section_danger_zone()
		{
			global $current_screen;

			if (!isset($current_screen))
				wp_die(esc_html__('Invalid request.', 'wpematico'), esc_html__('Invalid request', 'wpematico'), array('response' => 400));

			$danger = WPeMatico::get_danger_options();
			?>
			<form action="<?php echo admin_url('admin-post.php'); ?>" method="post" dir="ltr">
				<h3><?php _e('Debug Mode', 'wpematico'); ?></h3>

				<label>
					<input type="checkbox" name="wpematico_debug_mode" value="1" <?php checked(!empty($danger['wpematico_debug_log_file']), true); ?> />
					<?php esc_html_e('Enable WPeMatico Logs', 'wpematico'); ?>
				</label><br>
				<p class="description">
					<?php _e('This action will activate the function', 'wpematico'); ?> <code>wpematico_log("Custom log message");</code> <?php _e('and its panel in a new tab to follow log messages inside the code.', 'wpematico'); ?>
				</p>
				<label><input id="wpe_debug_logs_campaign" class="checkbox" value="1" type="checkbox" <?php checked($danger['wpe_debug_logs_campaign'], true); ?> name="wpe_debug_logs_campaign" /> <?php _e('Activate Debug Logs in Campaigns', 'wpematico'); ?></label><br />
				<p class="description">
					<?php _e('This action will save the logs of the last 10 runs of each campaign instead of only the last one, to allow follow all actions and behaviors when campaign runs.', 'wpematico'); ?>
					<br /><label id="deledebug" style="margin-left: 20px; display: none;"><input id="wpe_delete_debug_logs_campaign" class="checkbox" value="1" checked type="checkbox" name="wpe_delete_debug_logs_campaign" /> <?php _e('Delete all Debug Logs in Campaigns', 'wpematico'); ?></label>
				</p>

				<div class="div-danger-separator"></div>
				<h3><?php _e('Feed Addresses', 'wpematico'); ?></h3>

				<label><input id="wpe_allow_internal_feeds" class="checkbox" value="1" type="checkbox" <?php checked($danger['wpe_allow_internal_feeds'], true); ?> name="wpe_allow_internal_feeds" /> <?php _e('Allow feeds on private and local addresses', 'wpematico'); ?></label><br />
				<p class="description">
					<?php _e('By default campaigns only fetch feeds published on the public internet. Enable this if this site reads feeds from its own network, from an intranet server or from localhost, for example while developing.', 'wpematico'); ?>
					<br />
					<?php _e('Feeds hosted on this same site are always allowed and do not need this option.', 'wpematico'); ?>
				</p>

				<?php submit_button(__('Save Settings', 'wpematico'), 'primary', false); ?>
				<div class="div-danger-separator"></div>

				<h3><?php _e('Select Actions to Uninstall', 'wpematico'); ?></h3>
				<label><input class="checkbox" value="1" type="checkbox" <?php checked($danger['wpemdeleoptions'], true); ?> name="wpemdeleoptions" /> <?php _e('Delete all Options.', 'wpematico'); ?></label><br />
				<label><input class="checkbox" value="1" type="checkbox" <?php checked($danger['wpemdelecampaigns'], true); ?> name="wpemdelecampaigns" /> <?php _e('Delete all Campaigns.', 'wpematico'); ?></label><br />
				<p class="description">
					<?php _e('These selected actions will be performed after WPeMatico plugin is deactivated and you select "Delete" it in the WPeMatico row from the plugins list.', 'wpematico'); ?>
				</p>
				<?php wp_nonce_field('wpematico-danger'); ?>
				<input type="hidden" name="action" value="set_danger_data" />
				<p class="submit">
					<?php submit_button('Save Actions to Uninstall.', 'primary button-red', 'wpematico-set-danger-data', false); ?>
				</p>
			</form>
		<?php
		}

		public static function wpematico_save_danger_data()
		{
			// Verificar método HTTP
			if ('POST' !== $_SERVER['REQUEST_METHOD']) {
				wp_die(__('Invalid request method.', 'wpematico'));
			}

			// Verificar nonce y capacidades
			check_admin_referer('wpematico-danger');
			if (!current_user_can('manage_options')) {
				wp_die(__('You do not have sufficient permissions.', 'wpematico'));
			}

			// Sanitizar y preparar datos
			$danger = [
				'wpemdeleoptions'		   => isset($_POST['wpemdeleoptions']),
				'wpemdelecampaigns'		   => isset($_POST['wpemdelecampaigns']),
				'wpematico_debug_log_file' => isset($_POST['wpematico_debug_mode']),
				'wpe_debug_logs_campaign'  => isset($_POST['wpe_debug_logs_campaign']),
				'wpe_allow_internal_feeds' => isset($_POST['wpe_allow_internal_feeds']),
			];

			$olddanger = WPeMatico::get_danger_options();
			$notices   = [];

			// Delete the logs when debug is being turned off.
			if (wff_should_delete_logs($danger, $olddanger, $_POST)) {
				$deleted_all = wff_delete_all_campaign_logs();

				WPeMatico::add_wp_notice([
					'text'	   => $deleted_all ? __('Campaigns Logs deleted.', 'wpematico') : __('Failed to delete all campaign logs. ', 'wpematico') . '<br/>' .
						__('Some logs may already have been deleted or could not be removed.', 'wpematico'),
					'error'	   => !$deleted_all,
					'below-h2' => false
				]);
			}

			// Save new options
			if (update_option('WPeMatico_danger', $danger) || add_option('WPeMatico_danger', $danger)) {
				// One notice per possible change.
				$changes = [
					'wpematico_debug_log_file' => [
						'activate'	 => __('The WPeMatico Logs were activated.', 'wpematico'),
						'deactivate' => __('The WPeMatico Logs were deactivated.', 'wpematico')
					],
					'wpe_debug_logs_campaign'  => [
						'activate'	 => __('The Campaigns Debug Logs were activated.', 'wpematico'),
						'deactivate' => __('The Campaigns Debug Logs were deactivated.', 'wpematico')
					],
					'wpe_allow_internal_feeds' => [
						'activate'	 => __('Feeds on private and local addresses are now allowed.', 'wpematico'),
						'deactivate' => __('Campaigns fetch feeds published on the public internet only.', 'wpematico')
					],
					'wpemdeleoptions'		   => [
						'activate'	 => __('Delete Options on Uninstall saved.', 'wpematico') . '<br>' .
							__('The actions are executed when the plugin is uninstalled.', 'wpematico'),
						'deactivate' => __('Delete Options on Uninstall cleared.', 'wpematico'),
						'caution'	 => true
					],
					'wpemdelecampaigns'		   => [
						'activate'	 => __('Delete Campaigns on Uninstall saved.', 'wpematico') . '<br>' .
							__('The actions are executed when the plugin is uninstalled.', 'wpematico'),
						'deactivate' => __('Delete Campaigns on Uninstall cleared.', 'wpematico'),
						'caution'	 => true
					]
				];

				foreach ($changes as $key => $messages) {
					if ($olddanger[$key] != $danger[$key]) {
						$type = $danger[$key] ? 'activate' : 'deactivate';

						$notices[] = [
							'text'	=> $messages[$type],
							'error' => isset($messages['caution']) && $danger[$key]
						];
					}
				}
			}

			// Print every notice.
			foreach ($notices as $notice) {
				WPeMatico::add_wp_notice([
					'text'	   => $notice['text'],
					'below-h2' => false,
					'type'	   => !empty($notice['error']) ? 'warning' : 'success'
				]);
			}

			//wp_redirect(admin_url('edit.php?post_type=wpematico&page=wpematico_tools&tab=debug_info&section=danger_zone'));
			$redirect_url = wp_get_referer() ?: admin_url('admin.php?page=wpematico_tools');
			wp_safe_redirect($redirect_url);
			exit;
		}

		/**
		 * Shows log file screen
		 * @global $wp_filesystem
		 * @return void
		 */
		public static function debug_log_file()
		{
			$danger = WPeMatico::get_danger_options();

			if (empty($danger['wpematico_debug_log_file'])) {
				printf(
					'<div class="notice notice-warning"><p>%s</p></div>',
					esc_html__('Debug mode is not enabled. Please enable it in the WPeMatico settings to view the debug log.', 'wpematico')
				);
				return;
			}

			$log_file = wpematico_get_log_file_path();

			global $wp_filesystem;
			if (empty($wp_filesystem)) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}

			$log_exists	 = $wp_filesystem->exists($log_file);
			$log_content = $log_exists ? $wp_filesystem->get_contents($log_file) : '';

			if (!empty($_POST['clear_log']) && $log_exists) {
				if (check_admin_referer('wpematico_debug_log_clear', 'wpematico_debug_log_nonce')) {
					$wp_filesystem->put_contents($log_file, '');
					$log_exists	 = false;
					$log_content = '';
				}
			}

			echo '<div class="wrap">';
			echo '<div class="notice notice-info inline"><p>';
			echo esc_html__('When debug mode is enabled, specific information will be shown here.', 'wpematico') . ' ';
			printf(
				'(<a href="%s" target="_blank">%s</a>)',
				esc_url('https://etruel.com/question/how-to-use-wpematico-log/'),
				esc_html__('Learn how to use the wpematico_log() function', 'wpematico')
			);
			echo '</p></div>';

			echo '<h2>' . esc_html__('WPeMatico code Logs', 'wpematico') . '</h2>';

			echo '<form method="post">';
			wp_nonce_field('wpematico_debug_log_clear', 'wpematico_debug_log_nonce', false);
			$download_nonce = wp_create_nonce('wpematico_debug_log_clear');

			echo '<textarea name="wpematico_debug_log_content" readonly rows="20" style="width:100%; font-family: monospace;">' . esc_textarea($log_content) . '</textarea><br><br>';

			submit_button(__('Clear Log', 'wpematico'), 'delete', 'clear_log', false);
			if ($log_content) {
				echo '&nbsp;';
				printf(
					'<a href="%s" class="button button-primary">%s</a>&nbsp;',
					esc_url(admin_url('admin-ajax.php?action=download_wpematico_log&nonce=' . $download_nonce)),
					esc_html__('Download Log', 'wpematico')
				);
				submit_button(__('Copy to Clipboard', 'wpematico'), 'secondary', 'copy_debug_log', false, array(
					'onclick' => "this.form['wpematico_debug_log_content'].focus(); this.form['wpematico_debug_log_content'].select(); document.execCommand('copy'); return false;"
				));
			} else {
				echo '<p><em>' . esc_html__('No log file found yet.', 'wpematico') . '</em></p>';
			}

			echo '</form>';
			echo '</div>';
		}

		public static function download_debug_log()
		{

			if (!current_user_can('manage_options') || !wp_verify_nonce($_GET['nonce'], 'wpematico_debug_log_clear')) {
				exit;
			}

			$log_file = wpematico_get_log_file_path();

			if (!file_exists($log_file)) {
				wp_die(esc_html__('Log file not found.', 'wpematico'), '', ['response' => 404]);
			}

			header('Content-Type: text/plain');
			header('Content-Disposition: attachment; filename="wpematico_debug.log"');
			readfile($log_file);
			exit;
		}

		/**
		 * 		Called by function admin_menu() on wpematico_class
		 */
		public static function styles()
		{
			global $cfg;
			wp_enqueue_style('WPematStylesheet');
			// Same sidebar as the Settings screen, so it needs the same stylesheet.
			wp_enqueue_style('wpemat-sidebar-css', WPEMATICO_PLUGIN_URL . 'assets/css/wpemat_sidebar.css', array(), WPEMATICO_VERSION);
			wp_enqueue_script('WPemattiptip');
			add_action('admin_head', array(__CLASS__, 'wpematico_tools_head'));
			wp_enqueue_script('postbox');
			// Enqueue jQuery UI and autocomplete
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-autocomplete');
			wp_enqueue_script('wpematico_settings_page', WPEMATICO_PLUGIN_URL . 'assets/js/tools_page.js', array('jquery', 'postbox'), WPEMATICO_VERSION, true);
			// wp_localize_script('wpematico_tools_page', 'ajax_object', array(
			// 	'nonce'    => wp_create_nonce('wpematico-tools-page-nonce')
			// ));
			// //			$allowedmimes = array_diff(explode(',', WPeMatico::get_images_allowed_mimes()), explode(',', $cfg['images_allowed_ext']));
			// $wpematico_object = array(
			// 	'text_invalid_email' => __('Invalid email.', 'wpematico'),
			// 	//				'current_img_mimes'	 => $allowedmimes,
			// );
			// wp_localize_script('wpematico_tools_page', 'wpematico_object', $wpematico_object);
			// /* Add screen option: user can choose between 1 or 2 columns (default 2) */
			// //add_screen_option('layout_columns', array('max' => 2, 'default' => 2) );
		}

		public static function wpematico_tools_head()
		{
		?>
			<style type="text/css">
				/* Feed List */
				.wpematico-feed-list .wpe-feed-list-title {
					margin: .6em 0 .2em;
					font-size: 1.3em;
				}

				.wpematico-feed-list table.wp-list-table code {
					word-break: break-all;
					background: #f0f0f1;
					padding: 2px 6px;
					border-radius: 3px;
				}

				.wpematico-feed-list .column-num {
					width: 40px;
				}

				.wpematico-feed-list .column-primary {
					width: 34%;
				}

				.wpematico-feed-list .column-status {
					width: 100px;
				}

				/* Icon-only actions: the three of them fit on one line, so the row keeps
				   the height of every other row in the table. */
				.wpematico-feed-list .column-actions {
					width: 90px;
				}

				.wpematico-feed-list .wpe-row-action {
					display: inline-block;
					padding: 2px;
					line-height: 1;
					vertical-align: middle;
					color: #50575e;
					text-decoration: none;
				}

				.wpematico-feed-list .wpe-row-action:hover,
				.wpematico-feed-list .wpe-row-action:focus {
					color: #2271b1;
				}

				.wpematico-feed-list .wpe-status-active {
					color: #46b450;
				}

				.wpematico-feed-list .wpe-status-inactive {
					color: #dc3232;
				}

				.wpematico-feed-list .wpe-never {
					color: #8c8f94;
					font-style: italic;
				}

				.wpematico-feed-list .status-label {
					margin-left: 4px;
					vertical-align: middle;
				}

				.wpematico-feed-list .column-lastrun {
					width: 170px;
				}

				/* Core makes the sort link a block, which would push the (?) icon to a
				   second line and make every header two rows tall. The nowrap keeps the
				   icon beside the label once the sort arrows widen the link. */
				.wpematico-feed-list thead th,
				.wpematico-feed-list tfoot th {
					white-space: nowrap;
				}

				.wpematico-feed-list thead th.sortable a,
				.wpematico-feed-list thead th.sorted a,
				.wpematico-feed-list tfoot th.sortable a,
				.wpematico-feed-list tfoot th.sorted a {
					display: inline-block;
					vertical-align: middle;
				}

				.wpematico-feed-list th .help_tip {
					vertical-align: middle;
					margin-left: 4px;
				}

				/* The table is table-layout:fixed, so the columns that are not sized
				   share whatever is left. Between the widest layout and the point where
				   WP stacks the rows (782px), that leftover gets small enough that a
				   nowrap header would render on top of the next column: let the labels
				   wrap there, and drop the decorative counter. */
				@media screen and (max-width: 1200px) {
					.wpematico-feed-list thead th,
					.wpematico-feed-list tfoot th {
						white-space: normal;
					}

					.wpematico-feed-list .column-lastrun {
						width: 150px;
					}
				}

				@media screen and (max-width: 1000px) {
					.wpematico-feed-list .column-num {
						display: none;
					}
				}

				/* Core hides .tablenav .actions on small screens, because there it means
				   the bulk-action selects. Ours is the search box, which is exactly what
				   you still want on a narrow screen. */
				@media screen and (max-width: 782px) {
					.wpematico-feed-list .tablenav.top .wpe-feed-list-searchbar {
						display: block;
						width: 100%;
					}

					.wpematico-feed-list .wpe-feed-list-searchbar .search-box input[type="search"] {
						flex: 1 1 auto;
						min-width: 0;
					}
				}

				/* Search on the left, pagination on the right, one row — the layout WP
				   gives its list tables. Core fixes .tablenav to height:30px, which the
				   second line of feedback would spill out of. */
				.wpematico-feed-list .tablenav.top {
					display: flex;
					align-items: flex-start;
					justify-content: space-between;
					flex-wrap: wrap;
					gap: 6px 16px;
					height: auto;
					margin: 6px 0 4px;
				}

				.wpematico-feed-list .tablenav.top > br.clear {
					display: none;   /* float clearing has no job inside a flex row */
				}

				.wpematico-feed-list .wpe-feed-list-searchbar {
					float: none;
					margin: 0;
					padding: 0;
				}

				.wpematico-feed-list .wpe-feed-list-searchbar .search-box {
					float: none;
					margin: 0;
					display: flex;
					align-items: center;
					gap: 6px;
				}

				.wpematico-feed-list .wpe-feed-list-searchbar .search-box input[type="search"] {
					float: none;
					margin: 0;
					min-width: 220px;
				}

				.wpematico-feed-list .tablenav.top .tablenav-pages {
					float: none;
					margin: 0;
				}

				.wpematico-feed-list .wpe-feed-list-searchinfo {
					/* Reserved even when empty, so the pagination does not jump a line
					   up and down while you type. */
					min-height: 1.5em;
					margin: 2px 0 0;
					line-height: 1.5em;
				}

				.wpematico-feed-list .wpe-feed-list-searchinfo span {
					margin-right: 12px;
					color: #50575e;
				}

				.wpematico-feed-list .wpe-filter-hint {
					font-style: italic;
					color: #8c8f94;
				}

				/* While the instant filter is on, the striping has to be recomputed:
				   :nth-child() keeps counting the rows that are hidden. */
				.wpematico-feed-list tbody.wpe-filtered > tr {
					background: transparent;
				}

				.wpematico-feed-list tbody.wpe-filtered > tr.wpe-odd {
					background: #f6f7f7;
				}

				.insidesec {
					display: inline-block;
					vertical-align: top;
				}

				.ui-autocomplete {
					float: left;
					box-shadow: 2px 2px 3px #888888;
					background: #FFF;
				}

				.ui-menu-item {
					list-style-type: none;
					padding: 10px;
				}

				.ui-menu-item:hover {
					background: #F1F1F1;
				}

				.postbox .hndle {
					border-bottom: 1px solid #ccd0d4;
				}

				.postbox .handlediv {
					float: right;
					text-align: center;
				}

				/* Red Button */

				.wp-core-ui .button-red {
					background: #BD2B2B;
					border-color: transparent;
					color: #fff;
					text-decoration: none;
					text-shadow: none;
				}

				.wp-core-ui .button-red.hover,
				.wp-core-ui .button-red:hover {
					color: #fff;
					border-color: transparent;
					background-color: #AC0F0F;
				}

				.wp-core-ui .button-red.focus,
				.wp-core-ui .button-red:focus {
					color: #fff;
					background-color: #AC0F0F;
					border-color: transparent;
					box-shadow: 0 0 0 1px #fff, 0 0 0 3px #AC0F0F;
				}

				.wp-core-ui .button-red.active,
				.wp-core-ui .button-red:active {
					color: #fff;
					background-color: #A20909;
					border-color: transparent;
				}

				.wp-core-ui .button-red[disabled],
				.wp-core-ui .button-red:disabled,
				.wp-core-ui .button-red-disabled {
					color: #fba6a8 !important;
					background: #DD383A !important;
					box-shadow: 0 0 0 transparent !important;
					border-color: transparent !important;
					text-shadow: 0 0 0 transparent !important;
				}
			</style>

			<?php
		}

		public static function tools_form()
		{
			global $cfg, $current_screen, $helptip;

			// Tools are rendered for administrators only.
			if (!current_user_can('manage_options')) {
				return;
			}

			if (isset($_GET['page']) && $_GET['page'] === 'wpematico_tools') :
				$allowed = false;
				if (isset($_GET['tab']) && $_GET['tab'] === 'tools') {
					$allowed = true;
				} elseif (!isset($_GET['tab']) && !isset($_GET['section'])) {
					$allowed = true;
				} elseif (isset($_GET['section']) && $_GET['section'] === 'tools' && !isset($_GET['tab'])) {
					$allowed = true;
				}
				if ($allowed) :
			?>

					<div class="wpe_wrap">
						<?php
						$nonce		   = wp_create_nonce('wpematico-tools');
						$action_export = "wpematico_export_settings";
						$action		   = '?action=' . $action_export . '&_wpnonce=' . $nonce;
						$linkExport	   = admin_url("admin.php" . $action);

						$action_import = "wpematico_import_settings";
						$action		   = '?action=' . $action_import . '&_wpnonce=' . $nonce;
						$linkImport	   = admin_url("admin.php" . $action);

						//$button_export = '<a href="' . $linkExport . '" class="button" title="' . esc_attr(__("Export and download settings", 'wpematico')) . '">' . esc_html__('Export Settings', 'wpematico') . '</a>';
						?>
						<div class="postbox">
							<h3 class="hndle ui-sortable-handle"><span class="dashicons dashicons-arrow-up-alt"></span> <span><?php esc_html_e('Export Settings', 'wpematico') ?></span></h3>
							<div class="inside">
								<p><?php esc_html_e('Export the WPeMatico settings for this site as a .json file. This allows you to easily import the configuration into another site.', 'wpematico') ?></p>
								<p>
									<?php //echo $button_export; 
									?>
									<?php echo '<a href="' . esc_attr($linkExport) . '" class="button" title="' . esc_attr__("Export and download settings", 'wpematico') . '">' . esc_html__('Export Settings', 'wpematico') . '</a>'; ?>
								</p>
							</div><!-- .inside -->
						</div>
						<form action="<?php echo esc_attr($linkImport); ?>" id="importsettings" method='post' ENCTYPE='multipart/form-data'>
							<?php wp_nonce_field('import-settings', 'wpemimport_nonce'); ?>
							<div class="postbox">
								<h3 class="hndle ui-sortable-handle"><span class="dashicons dashicons-arrow-down-alt"></span> <span><?php esc_html_e('Import Settings', 'wpematico') ?></span></h3>
								<div class="inside">
									<p><?php esc_html_e('Import the WPeMatico settings for this site.', 'wpematico') ?></p>
									<p>
										<input type="hidden" name="wpematico-action" value="import_settings" />
										<input style="display:none;" type="file" class="button" name='txtsettings' id='txtsettings'>
										<a id="importcpg" class="button" href="Javascript:void(0);" title="<?php esc_attr_e("Upload and import a settings", 'wpematico') ?>"><?php esc_html_e('Import settings', 'wpematico') ?></a>
										<script>
											(function($) {
												$('#importcpg').on('click', function() {
													$('#txtsettings').trigger('click');
												});
												var message = "<?php esc_attr_e('The import will overwrite the current configuration of WPeMatico (and its addons), do you agree?', 'wpematico'); ?>";
												$('#txtsettings').on('change', function() {
													if (confirm(message)) {
														$('#importsettings').trigger('submit');
													} else {
														$('#txtsettings').val('');
													}
												});
											})(jQuery);
										</script>
									</p>
								</div><!-- .inside -->
							</div>

						</form>
					</div><!-- .wrap -->
<?php
				endif;
			endif;
		}

		/**
		 * Render the Migration Toolkit in its own Tools section.
		 * @since 2.9
		 */
		public static function wpematico_tools_section_migration()
		{
			if (isset($_GET['page']) && $_GET['page'] === 'wpematico_tools') {
				if (class_exists('WPeMatico_Migration_Toolkit')) {
					echo '<div class="wpe_wrap">';
					WPeMatico_Migration_Toolkit::render_migration_toolkit_box();
					echo '</div>';
				}
			}
		}

		/**
		 * Contextual Help tabs for the Tools screen.
		 *
		 * @param WP_Screen $screen The screen being rendered.
		 */
		public static function tools_help($screen = null)
		{
			// Used to also require post_type=wpematico and to build its own
			// WP_Screen from a hardcoded id (with a stray trailing space). Tools
			// moved under the WPeMatico menu in 2.9 and its URL carries no
			// post_type, so the condition never matched and the Help tab was
			// empty on every tab of the page. Take the live screen instead.
			if (!($screen instanceof WP_Screen)) {
				$screen = get_current_screen();
			}
			if (!$screen || !isset($_GET['page']) || $_GET['page'] !== 'wpematico_tools') {
				return;
			}
			if (isset($_GET['tab']) && $_GET['tab'] !== 'tools') {
				return;
			}
			$index = 0;
			foreach (wpematico_helptools() as $key => $section) {
				$tabcontent = '';
				foreach ($section as $section_key => $sdata) {
					$tabcontent .= '<p><strong>' . $sdata['title'] . '</strong><br />' .
						$sdata['tip'] . '</p>';
					$tabcontent .= (isset($sdata['plustip'])) ? '<p style="margin-top: 2px;margin-left: 7px;">' . $sdata['plustip'] . '</p>' : '';
				}
				$screen->add_help_tab(array(
					// Section names are translated, so they cannot be the tab id.
					'id'	  => 'wpematico-tools-help-' . (++$index),
					'title'	  => $key,
					'content' => $tabcontent,
				));
			}
		}
	}

endif;
/**
 * Whether the campaign logs must be deleted: wpe_debug_logs_campaign is being
 * turned off and the deletion was asked for.
 */
function wff_should_delete_logs(array $new, array $old, array $post): bool
{
	return !$new['wpe_debug_logs_campaign'] && !empty($old['wpe_debug_logs_campaign']) && !empty($post['wpe_delete_debug_logs_campaign']);
}

/**
 * Deletes every campaign log.
 */
function wff_delete_all_campaign_logs(): bool
{
	$campaigns = get_posts([
		'post_type'	  => 'wpematico',
		'numberposts' => -1,
		'fields'	  => 'ids',
	]);

	$success = true;

	foreach ($campaigns as $campaign_id) {
		if (!delete_post_meta($campaign_id, 'last_campaign_log')) {
			$success = false;
		}
	}

	return $success;
}




WPeMatico_Tools::hooks();
