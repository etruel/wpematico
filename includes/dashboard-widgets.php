<?php

/**
 * The WPeMatico widgets on the WordPress dashboard.
 *
 * @package WPeMatico
 * @since 2.9
 */
if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

if (!class_exists('WPeMatico_Dashboard_Widgets')) :

	class WPeMatico_Dashboard_Widgets {

		const CHART_TRANSIENT = 'wpematico_dashboard_chart';
		const NEWS_TRANSIENT  = 'wpematico_dashboard_news';
		const NEWS_BACKOFF    = 'wpematico_dashboard_news_backoff';
		const NEWS_FEED       = 'https://etruel.com/feed/';
		const NEWS_SITE       = 'https://etruel.com/';
		const NEWS_HELP       = 'https://etruel.com/faqs/wpematico/';

		public static function hooks() {
			// Not admin_init: that fires on every admin request, admin-ajax and
			// admin-post included, to decide something only the dashboard can use.
			add_action('wp_dashboard_setup', array(__CLASS__, 'register'));
			add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'));
		}

		/**
		 * Whether the current user may see these widgets, per the Settings page.
		 *
		 * @global array $cfg
		 * @return bool
		 */
		public static function user_may_see() {
			global $cfg;

			if (!empty($cfg['disabledashboard'])) {
				return false;
			}

			$post_type = get_post_type_object('wpematico');
			if (!$post_type || !current_user_can($post_type->cap->edit_others_posts)) {
				return false;
			}

			$allowed = (isset($cfg['roles_widget']) && is_array($cfg['roles_widget']))
					? $cfg['roles_widget']
					: array('administrator' => 'administrator');

			$user = wp_get_current_user();
			if (empty($user->ID)) {
				return false;
			}

			foreach ((array) $user->roles as $role) {
				// isset(), not array_search(): the latter returns a key, and a key of 0
				// is falsy, so a numerically indexed option silently hid the widget.
				if (isset($allowed[$role]) || in_array($role, $allowed, true)) {
					return true;
				}
			}

			return false;
		}

		public static function register() {
			if (!self::user_may_see()) {
				return;
			}

			$summary = __('WPeMatico Summary', 'wpematico');
			$news    = __('WPeMatico News', 'wpematico');

			// The title accepts markup, so the logo goes in it the way Rank Math and
			// core's own widgets do. __widget_basename is what keeps the plain name in
			// the Screen Options checkbox list, which prints the title unescaped.
			wp_add_dashboard_widget(
					'wpematico_widget',
					'<span class="wpem-hicon is-chip">' . wpematico_logo_svg(14) . '</span>' . esc_html($summary),
					array(__CLASS__, 'render_summary'),
					null,
					array('__widget_basename' => $summary)
			);

			wp_add_dashboard_widget(
					'wpematico_news_widget',
					'<span class="wpem-hicon">' . wpematico_logo_svg(18) . '</span>' . esc_html($news),
					array(__CLASS__, 'render_news'),
					null,
					array('__widget_basename' => $news)
			);
		}

		public static function assets($hook) {
			if ('index.php' !== $hook || !self::user_may_see()) {
				return;
			}
			wp_enqueue_style('wpematico-dashboard-widgets', WPEMATICO_PLUGIN_URL . 'assets/css/dashboard-widgets.css', array(), WPEMATICO_VERSION);
			wp_enqueue_script('wpematico-dashboard-widgets', WPEMATICO_PLUGIN_URL . 'assets/js/dashboard-widgets.js', array(), WPEMATICO_VERSION, true);
		}

		/* ------------------------------------------------------------------ *
		 *  Data
		 * ------------------------------------------------------------------ */

		/**
		 * How many days the activity chart covers.
		 *
		 * @return int
		 */
		public static function chart_days() {
			$days = (int) apply_filters('wpematico_dashboard_chart_days', 7);
			return ($days < 1) ? 1 : $days;
		}

		/**
		 * Imported posts per day for the chart window, plus the all-time total.
		 *
		 * Cached, because it is the only part of the widget whose cost grows with the
		 * size of the site. Everything else reads a handful of rows.
		 *
		 * @return array array('days' => array(Y-m-d => int), 'total' => int)
		 */
		public static function chart() {
			$cached = get_transient(self::CHART_TRANSIENT);
			if (is_array($cached) && isset($cached['days'])) {
				return $cached;
			}

			$data = array(
				'days'  => self::query_posts_per_day(self::chart_days()),
				'total' => self::query_all_time(),
			);

			set_transient(self::CHART_TRANSIENT, $data, apply_filters('wpematico_dashboard_chart_ttl', 5 * MINUTE_IN_SECONDS));

			return $data;
		}

		public static function flush_chart() {
			delete_transient(self::CHART_TRANSIENT);
		}

		/**
		 * Imported posts grouped by day, for the last $days days.
		 *
		 * ★ STRAIGHT_JOIN is load-bearing, do not remove it. Left to itself MySQL
		 * drives this from wp_postmeta on the meta_key index, which walks every
		 * wpe_campaignid row the site has ever written — the whole archive — to keep
		 * the few that fall inside the window. Forcing wp_posts first turns it into a
		 * range scan of type_status_date covering only the window, which is what makes
		 * the chart affordable on a site importing a thousand posts a day. Verified
		 * with EXPLAIN: `range` on type_status_date versus `ref` on meta_key.
		 *
		 * @param  int $days
		 * @return array Y-m-d => count, one entry per day, zero-filled.
		 */
		protected static function query_posts_per_day($days) {
			global $wpdb;

			$out = array();
			for ($i = $days - 1; $i >= 0; $i--) {
				$out[wp_date('Y-m-d', time() - ($i * DAY_IN_SECONDS))] = 0;
			}

			$types = get_post_types(array('public' => true), 'names');
			$types = apply_filters('wpematico_dashboard_chart_post_types', array_values($types));
			if (empty($types)) {
				return $out;
			}
			$statuses = array('publish', 'draft', 'pending', 'future', 'private');

			$type_ph   = implode(',', array_fill(0, count($types), '%s'));
			$status_ph = implode(',', array_fill(0, count($statuses), '%s'));
			$since     = wp_date('Y-m-d 00:00:00', time() - (($days - 1) * DAY_IN_SECONDS));

			$sql = $wpdb->prepare(
					"SELECT STRAIGHT_JOIN DATE(p.post_date) AS d, COUNT(*) AS c
					 FROM {$wpdb->posts} p
					 INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = 'wpe_campaignid'
					 WHERE p.post_type IN ($type_ph)
					   AND p.post_status IN ($status_ph)
					   AND p.post_date >= %s
					 GROUP BY d",
					array_merge($types, $statuses, array($since))
			);

			foreach ((array) $wpdb->get_results($sql) as $row) {
				if (isset($out[$row->d])) {
					$out[$row->d] = (int) $row->c;
				}
			}

			return $out;
		}

		/**
		 * Every post WPeMatico ever imported, from the per-campaign counter.
		 *
		 * @return int
		 */
		protected static function query_all_time() {
			global $wpdb;
			return (int) $wpdb->get_var("SELECT COALESCE(SUM(meta_value), 0) FROM {$wpdb->postmeta} WHERE meta_key = 'postscount'");
		}

		/**
		 * How many campaigns are switched on.
		 *
		 * @return int
		 */
		public static function active_count() {
			global $wpdb;
			return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'activated' AND meta_value = '1'");
		}

		/**
		 * How many campaigns exist at all, switched on or not.
		 *
		 * @return int
		 */
		public static function total_count() {
			global $wpdb;
			return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wpematico' AND post_status NOT IN ('trash', 'auto-draft')");
		}

		/**
		 * The soonest scheduled run among the active campaigns.
		 *
		 * @return int UTC timestamp, 0 when nothing is scheduled.
		 */
		public static function next_run() {
			global $wpdb;
			return (int) $wpdb->get_var($wpdb->prepare(
									"SELECT MIN(CAST(nr.meta_value AS UNSIGNED))
									 FROM {$wpdb->postmeta} nr
									 INNER JOIN {$wpdb->postmeta} act ON act.post_id = nr.post_id AND act.meta_key = 'activated' AND act.meta_value = '1'
									 WHERE nr.meta_key = 'cronnextrun' AND CAST(nr.meta_value AS UNSIGNED) >= %d",
									time()
			));
		}

		/**
		 * Campaign ids ordered by one of the individual meta counters.
		 *
		 * @param  string $meta_key  'lastrun' or 'cronnextrun'.
		 * @param  string $order     'DESC' or 'ASC'.
		 * @param  int    $limit
		 * @param  bool   $active_only
		 * @return int[]
		 */
		protected static function campaign_ids_by($meta_key, $order, $limit, $active_only = false) {
			global $wpdb;

			$order = ('ASC' === strtoupper($order)) ? 'ASC' : 'DESC';
			$join  = $active_only
					? "INNER JOIN {$wpdb->postmeta} act ON act.post_id = m.post_id AND act.meta_key = 'activated' AND act.meta_value = '1'"
					: '';

			$sql = $wpdb->prepare(
					"SELECT m.post_id
					 FROM {$wpdb->postmeta} m
					 $join
					 WHERE m.meta_key = %s AND CAST(m.meta_value AS UNSIGNED) > 0
					 ORDER BY CAST(m.meta_value AS UNSIGNED) $order
					 LIMIT %d",
					$meta_key,
					$limit
			);

			return array_map('intval', (array) $wpdb->get_col($sql));
		}

		/**
		 * The stored campaign data of one campaign, read raw.
		 *
		 * ★ Never WPeMatico::get_campaign() here. That runs the whole
		 * wpematico_check_campaigndata chain, every addon included, per campaign — the
		 * one cost in this screen that grows with both the number of campaigns and the
		 * number of addons installed. The widget only reads, so raw meta is enough.
		 *
		 * @param  int $campaign_id
		 * @return array
		 */
		protected static function raw_campaign($campaign_id) {
			$raw = get_post_meta($campaign_id, 'campaign_data', true);
			return is_array($raw) ? $raw : array();
		}

		/**
		 * Seconds the campaign has been running, or 0.
		 *
		 * Reads the lock meta directly rather than calling get_campaign_running_since(),
		 * which falls back to get_campaign() and clears stale locks — writes this
		 * read-only screen has no business doing.
		 *
		 * @param  int $campaign_id
		 * @return int
		 */
		protected static function running_for($campaign_id) {
			$lock = (int) get_post_meta($campaign_id, WPeMatico::FETCH_LOCK_META, true);
			if ($lock <= 0) {
				return 0;
			}
			$elapsed = time() - $lock;
			$ttl     = WPeMatico::get_fetch_lock_ttl();
			if ($ttl > 0 && $elapsed >= $ttl) {
				return 0;
			}
			return ($elapsed > 0) ? $elapsed : 1;
		}

		/**
		 * The last runs, newest first.
		 *
		 * @param  int $limit
		 * @return array
		 */
		public static function recent_runs($limit = 5) {
			$rows = array();

			foreach (self::campaign_ids_by('lastrun', 'DESC', $limit) as $id) {
				$raw       = self::raw_campaign($id);
				$timeout   = WPeMatico::get_campaign_last_timeout($id);
				$lastrun   = (int) get_post_meta($id, 'lastrun', true);
				$posts     = isset($raw['lastpostscount']) ? (int) $raw['lastpostscount'] : 0;
				$runtime   = isset($raw['lastruntime']) ? (int) $raw['lastruntime'] : 0;
				$running   = self::running_for($id);
				$timed_out = (!empty($timeout) && $timeout['time'] >= $lastrun);

				$rows[] = array(
					'id'       => $id,
					'title'    => self::title($id),
					'lastrun'  => $lastrun,
					'posts'    => $posts,
					'runtime'  => $runtime,
					'running'  => $running,
					'timeout'  => $timed_out ? (int) $timeout['runtime'] : 0,
				);
			}

			return $rows;
		}

		/**
		 * The next scheduled runs, soonest first.
		 *
		 * @param  int $limit
		 * @return array
		 */
		public static function upcoming($limit = 5) {
			$rows = array();

			foreach (self::campaign_ids_by('cronnextrun', 'ASC', $limit, true) as $id) {
				$raw = self::raw_campaign($id);
				$rows[] = array(
					'id'      => $id,
					'title'   => self::title($id),
					'when'    => (int) get_post_meta($id, 'cronnextrun', true),
					'running' => self::running_for($id),
					'cron'    => isset($raw['cron']) ? $raw['cron'] : '',
				);
			}

			return $rows;
		}

		/**
		 * Campaigns worth looking at: the last run timed out, or it brought nothing.
		 *
		 * @param  int $limit
		 * @return array
		 */
		public static function attention($limit = 8) {
			$rows = array();

			foreach (self::campaign_ids_by('lastrun', 'DESC', 40) as $id) {
				if (count($rows) >= $limit) {
					break;
				}

				$lastrun = (int) get_post_meta($id, 'lastrun', true);
				$timeout = WPeMatico::get_campaign_last_timeout($id);

				if (!empty($timeout) && $timeout['time'] >= $lastrun) {
					$rows[] = array(
						'id'     => $id,
						'title'  => self::title($id),
						'when'   => (int) $timeout['time'],
						'kind'   => 'timeout',
						'detail' => (int) $timeout['runtime'],
					);
					continue;
				}

				$raw = self::raw_campaign($id);
				if (!empty($raw['activated']) && isset($raw['lastpostscount']) && 0 === (int) $raw['lastpostscount']) {
					$rows[] = array(
						'id'     => $id,
						'title'  => self::title($id),
						'when'   => $lastrun,
						'kind'   => 'empty',
						'detail' => 0,
					);
				}
			}

			return $rows;
		}

		/* ------------------------------------------------------------------ *
		 *  Rendering helpers
		 * ------------------------------------------------------------------ */

		/**
		 * A campaign title, ready to be escaped on output.
		 *
		 * get_the_title() returns the texturized title, so a dash comes back as the
		 * entity `&#8211;`. Escaping that again encodes the ampersand and prints the
		 * entity as visible text, so it is decoded here first.
		 *
		 * @param  int $campaign_id
		 * @return string
		 */
		protected static function title($campaign_id) {
			return html_entity_decode(get_the_title($campaign_id), ENT_QUOTES, 'UTF-8');
		}

		protected static function edit_url($campaign_id) {
			return admin_url('post.php?post=' . (int) $campaign_id . '&action=edit');
		}

		protected static function exact_date($timestamp) {
			return wp_date(get_option('date_format') . ' ' . get_option('time_format'), (int) $timestamp);
		}

		/**
		 * "2 hours ago" / "in 14 min", with the exact date kept in the title attribute.
		 *
		 * @param  int  $timestamp
		 * @param  bool $future
		 * @return string
		 */
		protected static function relative($timestamp, $future = false) {
			$timestamp = (int) $timestamp;
			if ($timestamp <= 0) {
				return '';
			}
			$diff = human_time_diff($timestamp, time());
			/* translators: %s: a length of time, e.g. "2 hours". */
			return $future ? sprintf(__('in %s', 'wpematico'), $diff)
					/* translators: %s: a length of time, e.g. "2 hours". */
					: sprintf(__('%s ago', 'wpematico'), $diff);
		}

		protected static function seconds($seconds) {
			/* translators: %s: a number of seconds. */
			return sprintf(__('%ss', 'wpematico'), (int) $seconds);
		}

		/* ------------------------------------------------------------------ *
		 *  Summary widget
		 * ------------------------------------------------------------------ */

		public static function render_summary() {
			$chart    = self::chart();
			$days     = $chart['days'];
			$today    = end($days);
			$active   = self::active_count();
			$total    = self::total_count();
			$next     = self::next_run();
			$recent   = self::recent_runs(5);
			$upcoming = self::upcoming(5);
			$alerts   = self::attention();

			$campaigns_url = admin_url('edit.php?post_type=wpematico');
			?>
			<div class="wpem-dash">

				<div class="wpem-kpis">
					<a class="wpem-kpi" href="<?php echo esc_url($campaigns_url); ?>">
						<span class="wpem-kpi-n"><?php echo esc_html(number_format_i18n((int) $today)); ?></span>
						<span class="wpem-kpi-l"><?php esc_html_e('Posts today', 'wpematico'); ?></span>
					</a>
					<a class="wpem-kpi" href="<?php echo esc_url($campaigns_url); ?>">
						<span class="wpem-kpi-n is-ok"><?php echo esc_html(number_format_i18n($active)); ?><small class="wpem-kpi-of">/<?php echo esc_html(number_format_i18n($total)); ?></small></span>
						<span class="wpem-kpi-l"><?php esc_html_e('Active / total', 'wpematico'); ?></span>
					</a>
					<a class="wpem-kpi" href="<?php echo esc_url($campaigns_url); ?>"
					   title="<?php echo $next ? esc_attr(self::exact_date($next)) : ''; ?>">
						<span class="wpem-kpi-n"><?php echo $next ? esc_html(human_time_diff($next, time())) : '&mdash;'; ?></span>
						<span class="wpem-kpi-l"><?php esc_html_e('Next run', 'wpematico'); ?></span>
					</a>
					<a class="wpem-kpi" href="<?php echo esc_url($campaigns_url); ?>">
						<span class="wpem-kpi-n is-neutral"><?php echo esc_html(number_format_i18n((int) $chart['total'])); ?></span>
						<span class="wpem-kpi-l"><?php esc_html_e('Posts all time', 'wpematico'); ?></span>
					</a>
				</div>

				<?php self::render_chart($days); ?>

				<?php
				$tabs = array(
					'recent' => __('Recent', 'wpematico'),
					'next'   => __('Up next', 'wpematico'),
				);
				if (!empty($alerts)) {
					$tabs['attention'] = __('Needs a look', 'wpematico');
				}
				// The tab that has something to say opens first, but it keeps its place
				// in the row: reordering the tabs between loads is what makes a toolbar
				// impossible to learn.
				$selected = empty($alerts) ? 'recent' : 'attention';
				?>
				<div class="wpem-tabs" role="tablist" aria-label="<?php esc_attr_e('Campaign views', 'wpematico'); ?>">
					<?php foreach ($tabs as $key => $label) : ?>
						<button type="button" class="wpem-tab" role="tab"
								id="wpem-tab-<?php echo esc_attr($key); ?>"
								aria-controls="wpem-panel-<?php echo esc_attr($key); ?>"
								aria-selected="<?php echo ($key === $selected) ? 'true' : 'false'; ?>">
							<?php echo esc_html($label); ?>
							<?php if ('attention' === $key) : ?>
								<span class="wpem-count"><?php echo esc_html(number_format_i18n(count($alerts))); ?></span>
							<?php endif; ?>
						</button>
					<?php endforeach; ?>
				</div>

				<div class="wpem-panel" role="tabpanel" id="wpem-panel-recent"
					 aria-labelledby="wpem-tab-recent" <?php echo ('recent' === $selected) ? '' : 'hidden'; ?>>
					<?php self::render_recent($recent); ?>
				</div>

				<div class="wpem-panel" role="tabpanel" id="wpem-panel-next"
					 aria-labelledby="wpem-tab-next" <?php echo ('next' === $selected) ? '' : 'hidden'; ?>>
					<?php self::render_upcoming($upcoming); ?>
				</div>

				<?php if (!empty($alerts)) : ?>
					<div class="wpem-panel" role="tabpanel" id="wpem-panel-attention"
						 aria-labelledby="wpem-tab-attention" <?php echo ('attention' === $selected) ? '' : 'hidden'; ?>>
						<?php self::render_attention($alerts); ?>
					</div>
				<?php endif; ?>

				<div class="wpem-foot">
					<a href="<?php echo esc_url($campaigns_url); ?>"><?php esc_html_e('Campaigns', 'wpematico'); ?></a>
					<a href="<?php echo esc_url(admin_url('post-new.php?post_type=wpematico')); ?>"><?php esc_html_e('Add new', 'wpematico'); ?></a>
					<a href="<?php echo esc_url(admin_url('admin.php?page=wpematico_settings')); ?>"><?php esc_html_e('Settings', 'wpematico'); ?></a>
				</div>

			</div>
			<?php
		}

		/**
		 * The activity chart, as one bar per day.
		 *
		 * @param array $days Y-m-d => count
		 */
		protected static function render_chart($days) {
			$count = count($days);
			if (!$count) {
				return;
			}

			$max   = max(1, max($days));
			$gap   = 5;
			$width = 300;
			$bar   = ($width - (($count - 1) * $gap)) / $count;
			$last  = $count - 1;
			$i     = 0;
			?>
			<div class="wpem-chart">
				<svg viewBox="0 0 <?php echo esc_attr($width); ?> 45" preserveAspectRatio="none" role="img"
					 aria-label="<?php echo esc_attr(sprintf(
							/* translators: %s: number of days. */
							__('Posts imported per day over the last %s days', 'wpematico'),
							number_format_i18n($count)
					 )); ?>">
					<line x1="0" y1="44.5" x2="<?php echo esc_attr($width); ?>" y2="44.5" class="wpem-chart-base" />
					<?php foreach ($days as $day => $value) :
						$height = ($value > 0) ? max(2, round(40 * $value / $max)) : 1;
						$x      = round($i * ($bar + $gap), 2);
						$y      = 45 - $height;
						// A day that brought nothing is drawn as a grey stub, today
						// included: in the brand colour a 1px bar reads as a filled
						// baseline, and it said "today has posts" when it had none.
						$class  = ($value > 0) ? (($i === $last) ? ' is-today' : '') : ' is-empty';
						?>
						<rect class="wpem-bar<?php echo esc_attr($class); ?>"
							  x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>"
							  width="<?php echo esc_attr(round($bar, 2)); ?>" height="<?php echo esc_attr($height); ?>" rx="2" />
						<?php $i++; endforeach; ?>
				</svg>
				<div class="wpem-chart-days" style="grid-template-columns: repeat(<?php echo esc_attr($count); ?>, 1fr);">
					<?php
					$i = 0;
					foreach ($days as $day => $value) :
						$stamp = strtotime($day . ' 12:00:00');
						$label = ($i === $last) ? __('Today', 'wpematico') : wp_date('D', $stamp);
						?>
						<span class="<?php echo ($i === $last) ? 'is-today' : ''; ?>"
							  title="<?php echo esc_attr(wp_date(get_option('date_format'), $stamp)); ?>">
							<?php echo esc_html($label . ' ' . number_format_i18n($value)); ?>
						</span>
						<?php $i++; endforeach; ?>
				</div>
			</div>
			<?php
		}

		protected static function render_recent($rows) {
			if (empty($rows)) {
				self::render_empty(
						__('No campaign has run yet.', 'wpematico'),
						__('Create a campaign and WPeMatico will start bringing posts in.', 'wpematico'),
						admin_url('post-new.php?post_type=wpematico'),
						__('Add your first campaign', 'wpematico')
				);
				return;
			}
			?>
			<ul class="wpem-rows">
				<?php foreach ($rows as $row) : ?>
					<li class="wpem-row">
						<?php
						if ($row['running']) {
							$dot = 'is-running';
						} elseif ($row['timeout']) {
							$dot = 'is-bad';
						} elseif ($row['posts'] > 0) {
							$dot = 'is-ok';
						} else {
							$dot = 'is-zero';
						}
						?>
						<span class="wpem-dot <?php echo esc_attr($dot); ?>"></span>
						<a class="wpem-name" href="<?php echo esc_url(self::edit_url($row['id'])); ?>"><?php echo esc_html($row['title']); ?></a>

						<?php if ($row['running']) : ?>
							<span class="wpem-pill is-running"><?php
								/* translators: %s: a number of seconds. */
								echo esc_html(sprintf(__('running %s', 'wpematico'), self::seconds($row['running'])));
							?></span>
						<?php else : ?>
							<span class="wpem-when" title="<?php echo esc_attr(self::exact_date($row['lastrun'])); ?>"><?php
								echo esc_html(self::relative($row['lastrun']));
							?></span>
							<?php if ($row['timeout']) : ?>
								<span class="wpem-pill is-bad"><?php
									/* translators: %s: a number of seconds. */
									echo esc_html(sprintf(__('timed out %s', 'wpematico'), self::seconds($row['timeout'])));
								?></span>
							<?php else : ?>
								<span class="wpem-pill <?php echo ($row['posts'] > 0) ? 'is-ok' : ''; ?>"><?php
									echo esc_html(($row['posts'] > 0) ? '+' . number_format_i18n($row['posts']) : '0');
								?></span>
								<span class="wpem-pill"><?php echo esc_html(self::seconds($row['runtime'])); ?></span>
							<?php endif; ?>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php
		}

		protected static function render_upcoming($rows) {
			if (empty($rows)) {
				self::render_empty(
						__('Nothing is scheduled.', 'wpematico'),
						__('Campaigns run on a schedule once you switch them on.', 'wpematico'),
						admin_url('edit.php?post_type=wpematico'),
						__('Go to campaigns', 'wpematico')
				);
				return;
			}
			?>
			<ul class="wpem-rows">
				<?php foreach ($rows as $row) : ?>
					<li class="wpem-row">
						<span class="wpem-dot <?php echo $row['running'] ? 'is-running' : ''; ?>"></span>
						<a class="wpem-name" href="<?php echo esc_url(self::edit_url($row['id'])); ?>"><?php echo esc_html($row['title']); ?></a>
						<?php if ($row['running']) : ?>
							<span class="wpem-pill is-running"><?php
								/* translators: %s: a number of seconds. */
								echo esc_html(sprintf(__('running %s', 'wpematico'), self::seconds($row['running'])));
							?></span>
						<?php else : ?>
							<span class="wpem-pill" title="<?php echo esc_attr(self::exact_date($row['when'])); ?>"><?php
								echo esc_html(self::relative($row['when'], true));
							?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php
		}

		protected static function render_attention($rows) {
			?>
			<ul class="wpem-rows">
				<?php foreach ($rows as $row) : ?>
					<li class="wpem-row">
						<span class="wpem-dot <?php echo ('timeout' === $row['kind']) ? 'is-bad' : 'is-zero'; ?>"></span>
						<a class="wpem-name" href="<?php echo esc_url(self::edit_url($row['id'])); ?>"><?php echo esc_html($row['title']); ?></a>
						<span class="wpem-when" title="<?php echo esc_attr(self::exact_date($row['when'])); ?>"><?php
							echo esc_html(self::relative($row['when']));
						?></span>
						<?php if ('timeout' === $row['kind']) : ?>
							<span class="wpem-pill is-bad"><?php
								/* translators: %s: a number of seconds. */
								echo esc_html(sprintf(__('timed out %s', 'wpematico'), self::seconds($row['detail'])));
							?></span>
						<?php else : ?>
							<span class="wpem-pill is-warn"><?php esc_html_e('nothing new', 'wpematico'); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php
		}

		protected static function render_empty($title, $text, $url, $label) {
			?>
			<div class="wpem-empty">
				<b><?php echo esc_html($title); ?></b>
				<p><?php echo esc_html($text); ?></p>
				<a class="button button-primary button-small" href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
			</div>
			<?php
		}

		/* ------------------------------------------------------------------ *
		 *  News widget
		 * ------------------------------------------------------------------ */

		/**
		 * The timeout for the news feed, in seconds.
		 *
		 * The plugin-wide SimplePie timeout is meant for fetching a campaign's feed and
		 * runs into the minutes; a dashboard widget cannot wait that long.
		 *
		 * @return int
		 */
		public static function news_feed_timeout() {
			return (int) apply_filters('wpematico_news_feed_timeout', 8);
		}

		/**
		 * The latest posts from the WPeMatico blog.
		 *
		 * ★ Ask SimplePie's own error(), never is_wp_error(): fetchFeed() always returns
		 * a SimplePie object, so a failed fetch would otherwise be cached as "there is
		 * no news" for half a day. A failure sets a short backoff instead.
		 *
		 * @return array
		 */
		public static function news() {
			$cached = get_transient(self::NEWS_TRANSIENT);
			if (is_array($cached)) {
				return $cached;
			}
			if (get_transient(self::NEWS_BACKOFF)) {
				return array();
			}

			$url = apply_filters('wpematico_news_feed_url', self::NEWS_FEED);

			add_filter('wpe_simplepie_timeout', array(__CLASS__, 'news_feed_timeout'), 99);
			$feed = WPeMatico_functions::fetchFeed($url, true, 10);
			remove_filter('wpe_simplepie_timeout', array(__CLASS__, 'news_feed_timeout'), 99);

			$failed = !is_object($feed)
					|| !method_exists($feed, 'get_items')
					|| (method_exists($feed, 'error') && $feed->error());

			$items = array();
			if (!$failed) {
				foreach ($feed->get_items() as $item) {
					// Titles on the blog occasionally carry markup, which would print as
					// literal tags once the text is escaped on output.
					$title = trim(html_entity_decode(wp_strip_all_tags((string) $item->get_title()), ENT_QUOTES, 'UTF-8'));
					$link  = (string) $item->get_permalink();
					if ('' === $title || '' === $link) {
						continue;
					}

					// The blog publishes a featured image as the item enclosure, which is
					// what lets this widget show more than a list of links.
					$image     = '';
					$enclosure = $item->get_enclosure();
					if ($enclosure && !empty($enclosure->link)) {
						$image = (string) $enclosure->link;
					}

					$category   = '';
					$categories = $item->get_categories();
					if (!empty($categories)) {
						$category = trim(html_entity_decode((string) $categories[0]->get_label(), ENT_QUOTES, 'UTF-8'));
					}

					$excerpt = trim(wp_strip_all_tags((string) $item->get_description()));

					$items[] = array(
						'title'    => $title,
						'link'     => $link,
						'date'     => (int) $item->get_date('U'),
						'image'    => $image,
						'category' => $category,
						'excerpt'  => $excerpt,
					);
					if (count($items) >= (int) apply_filters('wpematico_news_items', 4)) {
						break;
					}
				}
			}

			if (empty($items)) {
				set_transient(self::NEWS_BACKOFF, 1, apply_filters('wpematico_news_feed_backoff', HOUR_IN_SECONDS));
				return array();
			}

			set_transient(self::NEWS_TRANSIENT, $items, apply_filters('wpematico_news_ttl', 12 * HOUR_IN_SECONDS));

			return $items;
		}

		public static function flush_news() {
			delete_transient(self::NEWS_TRANSIENT);
			delete_transient(self::NEWS_BACKOFF);
		}

		public static function render_news() {
			$items = self::news();
			?>
			<div class="wpem-dash wpem-news">
				<?php if (empty($items)) : ?>
					<p class="wpem-news-none"><?php esc_html_e('The blog could not be reached right now. It will be tried again shortly.', 'wpematico'); ?></p>
				<?php else :
					$hero = array_shift($items);
					$new  = ($hero['date'] > (time() - (21 * DAY_IN_SECONDS)));
					?>
					<a class="wpem-hero" href="<?php echo esc_url($hero['link']); ?>" target="_blank" rel="noopener noreferrer">
						<?php if ($hero['image']) : ?>
							<span class="wpem-hero-img">
								<img src="<?php echo esc_url($hero['image']); ?>" alt="" loading="lazy" />
								<?php if ($new) : ?>
									<span class="wpem-news-badge"><?php esc_html_e('New', 'wpematico'); ?></span>
								<?php endif; ?>
							</span>
						<?php endif; ?>
						<span class="wpem-hero-body">
							<span class="wpem-hero-meta">
								<?php if ($hero['category']) : ?>
									<span class="wpem-chip"><?php echo esc_html($hero['category']); ?></span>
								<?php endif; ?>
								<span class="wpem-when" title="<?php echo esc_attr(self::exact_date($hero['date'])); ?>"><?php
									echo esc_html(self::relative($hero['date']));
								?></span>
							</span>
							<span class="wpem-hero-title"><?php echo esc_html($hero['title']); ?></span>
							<?php if ($hero['excerpt']) : ?>
								<span class="wpem-hero-excerpt"><?php echo esc_html($hero['excerpt']); ?></span>
							<?php endif; ?>
						</span>
					</a>

					<?php if (!empty($items)) : ?>
						<ul class="wpem-news-list">
							<?php foreach ($items as $item) : ?>
								<li>
									<a href="<?php echo esc_url($item['link']); ?>" target="_blank" rel="noopener noreferrer">
										<?php if ($item['image']) : ?>
											<span class="wpem-news-thumb"><img src="<?php echo esc_url($item['image']); ?>" alt="" loading="lazy" /></span>
										<?php endif; ?>
										<span class="wpem-news-body">
											<span class="wpem-news-name"><?php echo esc_html($item['title']); ?></span>
											<span class="wpem-when" title="<?php echo esc_attr(self::exact_date($item['date'])); ?>"><?php
												echo esc_html(self::relative($item['date']));
											?></span>
										</span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				<?php endif; ?>

				<div class="wpem-foot">
					<a href="<?php echo esc_url(self::NEWS_SITE); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Blog', 'wpematico'); ?></a>
					<a href="<?php echo esc_url(self::NEWS_HELP); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Help', 'wpematico'); ?></a>
					<a class="wpem-foot-pro" href="<?php echo esc_url(admin_url('admin.php?page=wpemaddons')); ?>"><?php esc_html_e('Go Pro', 'wpematico'); ?></a>
				</div>
			</div>
			<?php
		}

	}

	WPeMatico_Dashboard_Widgets::hooks();

endif;
