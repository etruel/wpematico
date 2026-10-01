<?php

// don't load directly 

if (!defined('ABSPATH')) {

	header('Status: 403 Forbidden');

	header('HTTP/1.1 403 Forbidden');

	exit();

}



add_action('admin_head', 'wpematico_debug_head');



function wpematico_debug_head() {

	if ((isset($_GET['page']) && $_GET['page'] == 'wpematico_tools') &&

//			(isset($_GET['post_type']) && $_GET['post_type'] == 'wpematico') &&

			(isset($_GET['tab']) && $_GET['tab'] == 'debug_info') &&

			(!isset($_GET['section']) or $_GET['section'] == 'debug_file')) {

		?>

		<script type="text/javascript" language="javascript">

			jQuery(function () {

				jQuery(".help_tip").tipTip({maxWidth: "300px", edgeOffset: 5, fadeIn: 50, fadeOut: 50, keepAlive: true, attribute: "data-tip"});

			});

		</script>

		<?php

	}

}



add_action('wp_ajax_wpematico_get_feed_file', 'wpematico_feed_viewer');

/**
 * Feed Viewer AJAX endpoint.
 *
 * Requests a URL the same way a campaign does and returns what came back, so the
 * answer can be read before (or instead of) creating a campaign on it.
 *
 * The reply carries a 'status' the UI turns into the colour of the notice:
 *   'ok'      the URL is a feed and SimplePie parsed it,
 *   'notfeed' it answers over HTTP but the parser rejected it,
 *   'error'   nothing usable came back (network, DNS, TLS, permissions).
 *
 * @since 1.2.4
 * @return void
 */
function wpematico_feed_viewer() {
	check_ajax_referer('wpematico-feedviewer');

	// This used to gate on the referer containing "post_type=wpematico&page=wpematico_tools…".
	// Tools moved under the WPeMatico menu in 2.9 and that parameter is gone from the URL,
	// so the gate rejected every legitimate request and the endpoint answered 0 to
	// everything. What it was really guarding is the ability to make the server fetch an
	// arbitrary URL, which is a capability check.
	if (!current_user_can('manage_options')) {
		wpematico_feed_viewer_reply('error', '<b>' . esc_html__('You do not have sufficient permissions to use this tool.', 'wpematico') . '</b>');
	}

	$url	= isset($_POST['url']) ? esc_url_raw(trim(wp_unslash($_POST['url']))) : '';
	$scheme = $url ? strtolower((string) wp_parse_url($url, PHP_URL_SCHEME)) : '';
	if (empty($url) || !in_array($scheme, array('http', 'https'), true)) {
		wpematico_feed_viewer_reply('error', '<b>' . esc_html__('Enter a valid feed URL starting with http:// or https://', 'wpematico') . '</b>');
	}

	$fetch_feed_params = array(
		'url'			=> $url,
		'stupidly_fast'	=> true,
		'max'			=> 0,
		'order_by_date'	=> false,
		'force_feed'	=> false,
	);
	$fetch_feed_params = apply_filters('wpematico_feed_viewer_params', $fetch_feed_params, $url);

	// A campaign type may publish its source under an address that is not the feed
	// itself -- a Vimeo profile, channel or group is what the user pastes, and what
	// the eye icon of the feeds box opens here. The filter above turns it into the
	// feed the campaign really fetches, so ask for that one and say so below; asking
	// for the pasted address instead reported a failure on a source that works.
	$typed	 = $url;
	$url	 = (isset($fetch_feed_params['url']) && is_string($fetch_feed_params['url'])) ? $fetch_feed_params['url'] : $url;
	$resolved_note = ($url === $typed) ? '' :
			'<br />' . sprintf(
					/* translators: %s The feed URL the pasted address resolves to. */
					esc_html__('The address resolves to the feed %s, which is the one a campaign fetches.', 'wpematico'),
					'<code>' . esc_html($url) . '</code>'
			);

	$feed	= WPeMatico::fetchFeed($fetch_feed_params);
	$errors = $feed->error();

	if (empty($errors)) {
		/* translators: %s The Feed URL */
		$label	 = '<b>' . sprintf(esc_html__('The feed %s has been parsed successfully.', 'wpematico'), '<code>' . esc_html($typed) . '</code>') . '</b>';
		$label	.= $resolved_note;
		$label	.= wpematico_feed_viewer_summary($feed, $url);
		$label	.= wpematico_feed_viewer_headers_html(isset($feed->data['headers']) ? $feed->data['headers'] : array());

		wpematico_feed_viewer_reply('ok', $label, (string) $feed->get_raw_data());
	}

	// Not a feed (or not readable as one). Ask the URL again as a plain page so the
	// user can see what is actually being served there.
	/* translators: %s The Feed URL */
	$label	= '<b>' . sprintf(esc_html__('The feed %s cannot be obtained.', 'wpematico'), '<code>' . esc_html($typed) . '</code>') . '</b>';
	$label .= $resolved_note;
	$parser_said = is_array($errors) ? implode(' | ', array_filter($errors)) : $errors;
	if (!empty($parser_said)) {
		$label .= '<br />' . esc_html__('Parser says:', 'wpematico') . ' ' . esc_html($parser_said);
	}
	$label .= '<br />' . esc_html__('Obtaining URL Contents with WP Remote Request.', 'wpematico');

	// Deliberately not routed through WPeMatico::validate_feed_url(): showing what a
	// private address is really serving is the point of this half of the screen, and
	// this endpoint is manage_options. Keep both in mind before adding the check.
	$timeout  = (int) apply_filters('wpematico_feed_viewer_request_timeout', 15, $url);
	$response = wp_remote_request($url, array(
		'timeout'	 => $timeout,
		'user-agent' => apply_filters('wpematico_simplepie_user_agent', 'WPeMatico Feed Viewer/' . WPEMATICO_VERSION, $url),
	));

	if (is_wp_error($response)) {
		// print_r() on is_wp_error() used to be printed here, which is the boolean
		// true — never the reason. Report the actual error instead.
		$label	.= '<br /><b>' . esc_html__('ERROR. See below for details', 'wpematico') . '</b>';
		$body	 = '';
		foreach ($response->get_error_codes() as $code) {
			$body .= '[' . $code . '] ' . $response->get_error_message($code) . "\n";
		}
		wpematico_feed_viewer_reply('error', $label, $body);
	}

	$code = (int) wp_remote_retrieve_response_code($response);
	if ($code) {
		if (200 === $code) {
			/* translators: %s URL */
			$label .= '<br />' . sprintf(esc_html__('The URL %s has been obtained.', 'wpematico'), '<code>' . esc_html($url) . '</code>');
		} else {
			/* translators: %s URL */
			$label .= '<br />' . sprintf(esc_html__('The URL %s cannot be obtained successfully.', 'wpematico'), '<code>' . esc_html($url) . '</code>');
		}
		$label .= '<br />Code =&gt; ' . $code . ' - ' . esc_html(wp_remote_retrieve_response_message($response));
	} else {
		$label .= '<br /><b>' . esc_html__('ERROR Headers.', 'wpematico') . '</b>';
	}
	$label .= wpematico_feed_viewer_headers_html(wp_remote_retrieve_headers($response));

	$body	 = (string) wp_remote_retrieve_body($response);
	$charset = get_bloginfo('charset');
	if (preg_match('/charset=([a-zA-Z0-9_\-]+)/i', (string) wp_remote_retrieve_header($response, 'content-type'), $m)) {
		$charset = $m[1];
	}

	wpematico_feed_viewer_reply(200 === $code ? 'notfeed' : 'error', $label, $body, $charset);
}

/**
 * Send the Feed Viewer answer and stop.
 *
 * @since 2.9
 * @param string $status  'ok', 'notfeed' or 'error'.
 * @param string $label   Summary HTML shown in the notice box.
 * @param string $message Raw content for the textarea.
 * @param string $charset Charset $message is in, when it is known not to be UTF-8.
 * @return void
 */
function wpematico_feed_viewer_reply($status, $label, $message = '', $charset = '') {
	wp_send_json(array(
		'success' => ('ok' === $status),
		'status'  => $status,
		'label'	  => $label,
		'message' => wpematico_feed_viewer_prepare_body($message, $charset),
	));
}

/**
 * Make a raw response body safe to hand to the browser as JSON.
 *
 * Feeds are still published in ISO-8859-1 and friends; those bytes are not valid
 * UTF-8, and wp_json_encode() would strip them, silently mangling the very content
 * the tool exists to show. Convert instead. Oversized bodies are cut so a 20 MB
 * answer does not freeze the tab.
 *
 * @since 2.9
 * @param string $body    Raw body.
 * @param string $charset Charset of $body, if known.
 * @return string
 */
function wpematico_feed_viewer_prepare_body($body, $charset = '') {
	if ('' === $body) {
		return '';
	}

	$max = (int) apply_filters('wpematico_feed_viewer_max_bytes', 1048576);
	if ($max > 0 && strlen($body) > $max) {
		$body = substr($body, 0, $max) . "\n\n" .
				sprintf(
					/* translators: %s Size, already formatted (e.g. "1 MB"). */
					__('[ ... truncated by the Feed Viewer at %s. The campaign still reads the whole feed. ]', 'wpematico'),
					size_format($max)
				);
	}

	if (function_exists('mb_check_encoding') && !mb_check_encoding($body, 'UTF-8')) {
		if (empty($charset) && preg_match('/<\?xml[^>]+encoding=["\']([a-zA-Z0-9_\-]+)["\']/i', substr($body, 0, 200), $m)) {
			$charset = $m[1];
		}
		// No @ suppression: on PHP 8 an unknown charset raises a ValueError, and a
		// suppressed error still lands in error_get_last(), which paints the admin
		// with the php-error class on the next screen.
		try {
			$converted = mb_convert_encoding($body, 'UTF-8', $charset ? $charset : 'ISO-8859-1');
			if (is_string($converted) && '' !== $converted) {
				$body = $converted;
			}
		} catch (\Throwable $e) {
			$body = wp_check_invalid_utf8($body, true);
		}
	}

	return $body;
}

/**
 * One-line summary of a parsed feed: what it is and how much it carries.
 *
 * @since 2.9
 * @param object $feed     SimplePie instance.
 * @param string $asked_for URL the user typed, to tell autodiscovery apart.
 * @return string HTML.
 */
function wpematico_feed_viewer_summary($feed, $asked_for = '') {
	$type  = (int) $feed->get_type();
	$named = __('Unknown', 'wpematico');
	if ($type & $feed::TYPE_ATOM_ALL) {
		$named = 'Atom';
	} elseif ($type & $feed::TYPE_RSS_ALL) {
		$named = 'RSS';
	}

	$items = (int) $feed->get_item_quantity();
	$title = (string) $feed->get_title();

	$out  = '<br /><b>' . esc_html__('Summary.', 'wpematico') . '</b>';
	$out .= '<br />' . esc_html__('Format', 'wpematico') . ' =&gt; ' . esc_html($named);
	if ('' !== $title) {
		$out .= '<br />' . esc_html__('Title', 'wpematico') . ' =&gt; ' . esc_html($title);
	}
	$out .= '<br />' . esc_html__('Items', 'wpematico') . ' =&gt; ' . $items;
	if (0 === $items) {
		$out .= ' <em>' . esc_html__('(valid feed, but it is empty right now)', 'wpematico') . '</em>';
	}

	// The parser follows the <link rel="alternate"> of a page, so the address that
	// answered may not be the one that was typed. Say so, it is the difference
	// between "my URL works" and "the site happened to advertise a feed".
	$resolved = method_exists($feed, 'subscribe_url') ? (string) $feed->subscribe_url() : '';
	if ('' !== $resolved && '' !== $asked_for && untrailingslashit($resolved) !== untrailingslashit($asked_for)) {
		$out .= '<br />' . esc_html__('Feed found by autodiscovery', 'wpematico') . ' =&gt; <code>' . esc_html($resolved) . '</code>';
	}
	if (method_exists($feed, 'status_code') && $feed->status_code()) {
		$out .= '<br />' . esc_html__('HTTP code', 'wpematico') . ' =&gt; ' . (int) $feed->status_code();
	}

	return $out;
}

/**
 * Render a list of response headers.
 *
 * SimplePie only records them when the feed travelled over HTTP, and a repeated
 * header can arrive as an array, so neither the key nor a plain string can be
 * assumed here.
 *
 * @since 2.9
 * @param iterable $headers Response headers.
 * @return string HTML.
 */
function wpematico_feed_viewer_headers_html($headers) {
	if (empty($headers) || (!is_array($headers) && !is_object($headers))) {
		return '';
	}

	$out = '<br /><b>' . esc_html__('Headers.', 'wpematico') . '</b>';
	foreach ($headers as $key => $value) {
		if (is_array($value)) {
			$value = implode(', ', array_map('strval', $value));
		}
		$out .= '<br />' . esc_html($key) . ' =&gt; ' . esc_html((string) $value);
	}

	return $out;
}



/**
 * Render the Feed Viewer section of the Tools page.
 *
 * @since       1.2.4
 * @return      void
 */
add_action('wpematico_tools_section_feed_viewer', 'wpematico_tools_section_feed_viewer');

function wpematico_tools_section_feed_viewer() {
	if (!isset($_GET['page']) || $_GET['page'] !== 'wpematico_tools') {
		return;
	}
	// 'tools' is the default tab, so a URL without &tab= lands here too. The old
	// check demanded it explicitly and rendered an empty panel otherwise.
	if (isset($_GET['tab']) && $_GET['tab'] !== 'tools') {
		return;
	}
	if (!current_user_can('manage_options')) {
		return;
	}

	// Arriving from the eye icon of a campaign feed, or from the Feed List, carries
	// the URL to inspect; prefill the field and fetch it on load.
	$prefill = isset($_GET['feedlink']) ? esc_url_raw(wp_unslash($_GET['feedlink'])) : '';

	$helptip = wpematico_help_feed_viewer('tips');
	?>
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
			<?php wpematico_status_rightcolumn(); ?>

			<div id="postbox-container-2" class="postbox-container">
				<table class="widefat wpematico-system-status-debug" cellspacing="0">
					<tbody>
						<tr>
							<td colspan="3" data-export-label="WPeMatico Status">
								<p class="text">
									<?php esc_html_e('Paste a feed link here and the content will be shown on the textarea.', 'wpematico'); ?>
									<?php wpematico_help_tip(isset($helptip['feed_viewer_intro']) ? $helptip['feed_viewer_intro'] : ''); ?>
								</p>

								<div id="seefeed">
									<form id="wpematico-feedviewer-form" method="post" dir="ltr" onsubmit="return false;">
										<label for="feedlink"><b><?php esc_html_e('Feed URL.', 'wpematico'); ?></b>
											<?php wpematico_help_tip(isset($helptip['feed_viewer_url']) ? $helptip['feed_viewer_url'] : ''); ?>
										</label>
										<input class="large-text" id="feedlink" value="<?php echo esc_attr($prefill); ?>" type="url" name="feedlink"
											   placeholder="https://example.com/feed/"
											   autocomplete="off" spellcheck="false" /><br />
										<p class="bsubmit">
											<a id="getfeedbutton" class="button-primary" href="#"><?php esc_html_e('Get Feed', 'wpematico'); ?></a>
											<?php wpematico_help_tip(isset($helptip['feed_viewer_get']) ? $helptip['feed_viewer_get'] : ''); ?>
											<span class="spinner" id="getfeedspinner" style="float:none;margin-top:0;"></span>
										</p>

										<?php // Not .update-message: that core class paints a spinner/arrow glyph
										// that stays on top of whatever notice colour we set. ?>
										<div class="wpematico-feedviewer-result notice inline notice-warning notice-alt">
											<p id="headersresponse"><?php esc_html_e('Fill in a Feed URL and click the Get Feed Button.', 'wpematico'); ?></p>
										</div>

										<div style="width: 100%; box-sizing: border-box;">
											<textarea readonly="readonly" id="wpematico-feedinfo" name="wpematico-feedinfo" style="width: 100%;min-height: 370px; box-sizing: border-box;" placeholder="<?php esc_attr_e('Get Feed and see here its contents.', 'wpematico'); ?>"></textarea>
											<?php // No referer field: the endpoint checks the nonce and the capability,
											// not the URL the request came from. ?>
											<?php wp_nonce_field('wpematico-feedviewer', '_wpnonce', false); ?>
											<p>
												<a href="#" id="wpematico-feedinfo-selectall" class="button button-small"><?php esc_html_e('SELECT ALL', 'wpematico'); ?></a>
												<a href="#" id="wpematico-feedinfo-copy" class="button button-small"><?php esc_html_e('Copy', 'wpematico'); ?></a>
												<?php wpematico_help_tip(isset($helptip['feed_viewer_raw']) ? $helptip['feed_viewer_raw'] : ''); ?>
											</p>
										</div>
									</form>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
				<p></p>
			</div>        <!--  postbox-container-2 -->
		</div> <!-- #post-body -->
	</div> <!-- #poststuff -->

	<script type="text/javascript">
		jQuery(document).ready(function ($) {
			var $form	 = $('#wpematico-feedviewer-form'),
				$link	 = $('#feedlink'),
				$notice	 = $form.find('.wpematico-feedviewer-result'),
				$label	 = $('#headersresponse'),
				$out	 = $('#wpematico-feedinfo'),
				$spinner = $('#getfeedspinner'),
				$button	 = $('#getfeedbutton'),
				running	 = false;

			var strings = {
				empty:	 <?php echo wp_json_encode(__('Fill in a Feed URL and click the Get Feed Button.', 'wpematico')); ?>,
				working: <?php echo wp_json_encode(__('Requesting the URL, please wait...', 'wpematico')); ?>,
				failed:	 <?php echo wp_json_encode(__('The request could not be completed. Check the browser console and your server error log.', 'wpematico')); ?>,
				copied:	 <?php echo wp_json_encode(__('Copied to the clipboard.', 'wpematico')); ?>
			};

			function setNotice(type) {
				$notice.removeClass('notice-warning notice-error notice-success updating-message updated-message')
						.addClass('notice-' + type);
			}

			function getFeed() {
				if (running) {
					return;
				}
				var url = $.trim($link.val());
				if (url === '') {
					setNotice('warning');
					$label.text(strings.empty);
					$link.trigger('focus');
					return;
				}

				running = true;
				$button.addClass('disabled');
				$spinner.addClass('is-active');
				setNotice('warning');
				$label.text(strings.working);
				$out.val('');

				$.post(ajaxurl, {
					action:	  'wpematico_get_feed_file',
					url:	  url,
					_wpnonce: $form.find('#_wpnonce').val()
				}, function (response) {
					if (!response || typeof response !== 'object') {
						setNotice('error');
						$label.text(strings.failed);
						return;
					}
					// 'ok' | 'notfeed' | 'error' decide the colour of the box.
					setNotice(response.status === 'ok' ? 'success' : (response.status === 'notfeed' ? 'warning' : 'error'));
					$label.html(response.label);
					$out.val(response.message);
				}, 'json').fail(function () {
					setNotice('error');
					$label.text(strings.failed);
					$out.val('');
				}).always(function () {
					running = false;
					$button.removeClass('disabled');
					$spinner.removeClass('is-active');
				});
			}

			$button.on('click', function (e) {
				e.preventDefault();
				getFeed();
			});

			$link.on('keydown', function (e) {
				if (e.keyCode === 13) {   // Enter fetches instead of submitting the form.
					e.preventDefault();
					getFeed();
				}
			});

			$('#wpematico-feedinfo-selectall').on('click', function (e) {
				e.preventDefault();
				$out.trigger('focus').trigger('select');
			});

			$('#wpematico-feedinfo-copy').on('click', function (e) {
				e.preventDefault();
				$out.trigger('focus').trigger('select');
				var done = function () {
					var $me = $('#wpematico-feedinfo-copy'), old = $me.text();
					$me.text(strings.copied);
					window.setTimeout(function () { $me.text(old); }, 1500);
				};
				if (window.navigator.clipboard && window.isSecureContext) {
					window.navigator.clipboard.writeText($out.val()).then(done);
				} else if (document.execCommand('copy')) {
					done();
				}
			});

			if ($.fn.tipTip) {
				$('#seefeed').find('.help_tip').tipTip({maxWidth: '350px', edgeOffset: 5, fadeIn: 50, fadeOut: 50, keepAlive: true, defaultPosition: 'right'});
			}

			<?php if (!empty($prefill)) : ?>
			getFeed();   // Arrived with ?feedlink=, so show the result straight away.
			<?php endif; ?>
		});
	</script>
	<?php
}




function wpematico_FriendlyErrorType($type) {

	switch ($type) {

		case E_ERROR: // 1 //

			return 'E_ERROR';

		case E_WARNING: // 2 //

			return 'E_WARNING';

		case E_PARSE: // 4 //

			return 'E_PARSE';

		case E_NOTICE: // 8 //

			return 'E_NOTICE';

		case E_CORE_ERROR: // 16 //

			return 'E_CORE_ERROR';

		case E_CORE_WARNING: // 32 //

			return 'E_CORE_WARNING';

		case E_COMPILE_ERROR: // 64 //

			return 'E_COMPILE_ERROR';

		case E_COMPILE_WARNING: // 128 //

			return 'E_COMPILE_WARNING';

		case E_USER_ERROR: // 256 //

			return 'E_USER_ERROR';

		case E_USER_WARNING: // 512 //

			return 'E_USER_WARNING';

		case E_USER_NOTICE: // 1024 //

			return 'E_USER_NOTICE';

		// Literal 2048 instead of E_STRICT: PHP 8.4 deprecated the constant itself, and a
		// case label is evaluated on every call that does not match an earlier one, so just
		// naming it here emitted "Constant E_STRICT is deprecated". Nothing raises 2048 since
		// PHP 8.0; the mapping is kept for legacy values.
		case 2048: // E_STRICT //

			return 'E_STRICT';

		case E_RECOVERABLE_ERROR: // 4096 //

			return 'E_RECOVERABLE_ERROR';

		case E_DEPRECATED: // 8192 //

			return 'E_DEPRECATED';

		case E_USER_DEPRECATED: // 16384 //

			return 'E_USER_DEPRECATED';

	}

	return "";

}



/**

 * Display the debug info tab

 *

 * @since       1.2.4

 * @return      void

 */

function wpematico_tools_section_debug_file() {

	global $current_screen;

	if (!isset($current_screen))

		wp_die(esc_html__('Invalid request.', 'wpematico'), esc_html__('Invalid request', 'wpematico'), array('response' => 400));

	?>

	<div id="poststuff">

		<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">

			<?php wpematico_status_rightcolumn(); ?>

			<?php do_action('wpematico_system_status_page_before'); ?>

			<div id="postbox-container-2" class="postbox-container">

				<table class="widefat wpematico-system-status-debug" cellspacing="0">

					<tbody>

						<tr>

							<td colspan="3" data-export-label="WPeMatico Status">

								<p class="text">

									<?php esc_html_e('Use this file to get support on ', 'wpematico'); ?><a href="https://etruel.com/support/" target="_blank" rel="follow">etruel's website</a>.

								</p>

								<span class="get-system-status">

									<a href="javascript:" onclick='jQuery("#debug-report").slideDown();

												jQuery(this).parent().fadeOut();' class="button-primary debug-report"><?php _e('Get System Report', 'wpematico'); ?></a>

									<span class="system-report-msg"><?php _e('Click the button to see and download the system report.', 'wpematico'); ?></span>

								</span>

								<div id="debug-report" style="display: none;">

									<form action="<?php echo esc_url(admin_url('admin.php?page=wpematico_tools&tab=debug_info')); ?>" method="post" dir="ltr">

										<label><input class="checkbox" value="1" type="checkbox" name="alsophpinfo" /> <?php _e('Include also PHPInfo() if available.', 'wpematico'); ?></label><br/>

										<label><input class="checkbox" value="1" type="checkbox" checked="checked" name="alsocampaignslogs" /> <?php _e('Include also Last Campaigns Log.', 'wpematico'); ?></label><br/>

										<?php do_action('wpematico_debug_page_form_options'); ?>

										<input type="hidden" name="wpematico-action" value="download_debug_info" />

										<p class="submit">

											<?php submit_button('Download Debug Info File', 'primary', 'wpematico-download-debug-info', false); ?>

										</p>

										<div style="max-width: 650px;">

											<textarea readonly="readonly" id="debug-info-textarea" name="wpematico-sysinfo"

													  title="<?php _e('To copy the system info, click below then press Ctrl + C (PC) or Cmd + C (Mac).', 'wpematico'); ?>"

													  style="width: 100%;min-height: 370px;"

													  ><?php

														  echo wpematico_debug_info_get();

														  ?></textarea>

											<?php wp_nonce_field('wpematico-tools'); ?>

											<label onclick="jQuery('#debug-info-textarea').trigger('focus');

														jQuery('#debug-info-textarea').select()" ><?php _e('SELECT ALL', 'wpematico'); ?></label>

										</div>

									</form>

									<p></p>

								</div>

							</td>

						</tr>

					</tbody>

				</table>

				<p></p>

				<?php wpematico_show_data_info(); ?>

				<?php
				// Fills in the connectivity rows once the page is up. Only asks when the
				// result is not cached yet.
				if (null === wpematico_remote_connectivity()['checked']) : ?>
					<script>
						jQuery(function ($) {
							var $cells = $('.wpem-connectivity');
							if (!$cells.length) { return; }
							$.post(ajaxurl, {
								action: 'wpematico_check_connectivity',
								nonce: <?php echo wp_json_encode(wp_create_nonce('wpematico-connectivity')); ?>
							}).done(function (r) {
								if (!r || !r.success) { return; }
								if (r.data.report) { $('#debug-info-textarea').val(r.data.report); }
								$cells.each(function () {
									var ok = r.data[$(this).data('check')];
									$(this).html(ok
										? '<mark class="yes">&#10004;</mark>'
										: '<mark class="error"><?php echo esc_js(__('The connection to etruel.com failed. Some plugins features may not work. Please contact your hosting provider and make sure that https://etruel.com/downloads/feed/ is not blocked.', 'wpematico')); ?></mark>');
								});
							}).fail(function () {
								$cells.html('<mark class="error"><?php echo esc_js(__('The check could not be completed.', 'wpematico')); ?></mark>');
							});
						});
					</script>
				<?php endif; ?>

			</div>		<!--  postbox-container-2 -->

		</div> <!-- #post-body -->

	</div> <!-- #poststuff -->

	<?php

}



add_action('wpematico_tools_section_debug_file', 'wpematico_tools_section_debug_file');



function wpematico_status_rightcolumn() {

	// Renders the shared sidebar registry, the same one the Settings screen uses.
	// Addons reshape this one through wpematico_sidebar_widgets_tools.
	$section = isset($_GET['section']) ? sanitize_text_field(wp_unslash($_GET['section'])) : 'debug_file';

	?>
	<div id="postbox-container-1" class="postbox-container wpematico_sidebar">

		<div id="side-sortables" class="meta-box-sortables ui-sortable">

			<?php
			// wpematico_wp_ratings is not fired here on purpose: that box fetches remote
			// content, and this screen is kept free of blocking requests.
			WPeMatico_Settings_widgets::render_sidebar('tools', 'debug_info', $section);
			?>

		</div>

	</div>		<!--  postbox-container-1 -->

	<?php
}



function wpematico_get_plugin_new_version($plugin) {

	static $plugin_updates = array(); // Cache received responses.

	$response			   = '';

	if (empty($plugin_updates)) {

		$plugin_updates = get_site_transient('update_plugins');

		if ($plugin_updates === false) {

			$plugin_updates = new stdClass();

		}

	}

	if (!isset($plugin_updates->response)) {

		$plugin_updates->response = array();

	}

	foreach ($plugin_updates->response as $r_plugin => $value) {

		if ($r_plugin == $plugin) {

			$response = $value->new_version;

			break;

		}

	}



	return $response;

}



function wpematico_disk_total_space($echo = TRUE) {

	if (function_exists('disk_total_space')) {

		$bytes = disk_total_space(".");

		if (is_float($bytes)) {

			$si_prefix = array('B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB');

			$base	   = 1024;

			$class	   = min((int) log($bytes, $base), count($si_prefix) - 1);

			if ($echo) {

				echo sprintf('%1.2f', $bytes / pow($base, $class)) . ' ' . $si_prefix[$class] . '<br />';

			} else {

				return sprintf('%1.2f', $bytes / pow($base, $class)) . ' ' . $si_prefix[$class];

			}

		} else {

			if ($echo) {

				_e('ERROR: disk_total_space() is not available on this server.', 'wpematico') . '<br />';

			} else {

				__('ERROR: disk_total_space() is not available on this server.', 'wpematico') . '<br />';

			}

		}

	} else {

		return FALSE;

	}

}



function wpematico_disk_free_space($echo = TRUE) {

	if (function_exists('disk_free_space')) {

		$bytes = disk_free_space(".");

		if (is_float($bytes)) {

			$si_prefix = array('B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB');

			$base	   = 1024;

			$class	   = min((int) log($bytes, $base), count($si_prefix) - 1);

			if ($echo) {

				echo sprintf('%1.2f', $bytes / pow($base, $class)) . ' ' . $si_prefix[$class] . '<br />';

			} else {

				return sprintf('%1.2f', $bytes / pow($base, $class)) . ' ' . $si_prefix[$class];

			}

		} else {

			if ($echo) {

				_e('ERROR: disk_free_space() is not available on this server.', 'wpematico') . '<br />';

			} else {

				__('ERROR: disk_free_space() is not available on this server.', 'wpematico') . '<br />';

			}

		}

	} else {



		return FALSE;

	}

}



function wpematico_get_option_active_plugins() {

	static $option_active_plugins = array();

	if (empty($option_active_plugins)) {

		$option_active_plugins = (array) get_option('active_plugins', array());

	}

	return $option_active_plugins;

}



function wpematico_get_active_plugins() {

	static $wpematico_active_plugins = array();

	if (empty($wpematico_active_plugins)) {

		$active_plugins = wpematico_get_option_active_plugins();

		if (is_multisite()) {

			$active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));

		}

		foreach ($active_plugins as $plugin) {

			$wpematico_active_plugins[] = array(

				'new_version'	 => wpematico_get_plugin_new_version($plugin),

				'plugin_data'	 => file_exists(WP_PLUGIN_DIR . '/' . $plugin) ? get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin) : array(),

				'dirname'		 => dirname($plugin),

				'version_string' => 'Version',

				'network_string' => '',

			);

		}

	}

	return $wpematico_active_plugins;

}



function wpematico_getFileSystemMethod() {

	if (defined('MAINWP_SAVE_FS_METHOD')) {

		return MAINWP_SAVE_FS_METHOD;

	}

	$fs = get_filesystem_method();



	return $fs;

}



function wpematico_campaigns_info_data() {

	static $campaigns_info = null;

	if (null !== $campaigns_info) {

		return $campaigns_info;

	}

	$campaigns_info = array();

	$published = (int) wp_count_posts('wpematico')->publish;

	if ($published) {

		$campaigns_info[] = array('published_campaigns' => $published);

	}

	// Only ids and one meta key are needed, so skip the post objects, the term cache
	// and the row count: this walks every campaign on the site.
	$campaign_ids = get_posts(array(
		'post_type'				 => 'wpematico',
		'posts_per_page'		 => -1,
		'post_status'			 => 'any',
		'fields'				 => 'ids',
		'no_found_rows'			 => true,
		'update_post_term_cache' => false,
	));

	foreach ($campaign_ids as $campaign_id) {

		foreach (get_post_meta($campaign_id, 'campaign_data') as $campaign) {

			$campaigns_info[] = array($campaign);

		}

	}

	return $campaigns_info;

}



/**
 * Returns the cached result of the store reachability test.
 *
 * Never contacts the network: it only reads the stored result, so rendering the System
 * Status screen is not held up by an HTTP request. A null value means the test has not
 * run yet on this site, and the screen requests it over AJAX once the page is up.
 *
 * @since 2.9
 * @return array{get:?bool, post:?bool, checked:?int}
 */
function wpematico_remote_connectivity() {
	$cached = get_transient('wpematico_remote_connectivity');
	if (is_array($cached) && isset($cached['get'], $cached['post'])) {
		return array(
			'get'	  => (bool) $cached['get'],
			'post'	  => (bool) $cached['post'],
			'checked' => isset($cached['checked']) ? (int) $cached['checked'] : null,
		);
	}
	return array('get' => null, 'post' => null, 'checked' => null);
}

/**
 * Tests whether the store can be reached, and caches the result for 12 hours.
 *
 * The read test uses HEAD so the response body is not downloaded, and the timeout is
 * explicit and generous enough that a slow but working host is not reported as broken.
 *
 * @since 2.9
 * @return array{get:bool, post:bool, checked:int}
 */
function wpematico_run_remote_connectivity_probe() {
	$url	 = apply_filters('wpematico_connectivity_check_url', 'https://etruel.com/downloads/feed/');
	$timeout = (int) apply_filters('wpematico_connectivity_check_timeout', 15);
	$args	 = array('timeout' => $timeout, 'redirection' => 2, 'user-agent' => 'wpematico-debug');

	$ok = static function ($response) {
		if (is_wp_error($response)) {
			return false;
		}
		$code = (int) wp_remote_retrieve_response_code($response);
		return ($code >= 200 && $code < 300);
	};

	$result = array(
		'get'	  => $ok(wp_remote_request($url, $args + array('method' => 'HEAD'))),
		'post'	  => $ok(wp_remote_post($url, $args + array('decompress' => false))),
		'checked' => time(),
	);
	set_transient('wpematico_remote_connectivity', $result, 12 * HOUR_IN_SECONDS);
	return $result;
}

/**
 * AJAX: run the probe the screen asked for and hand back the two rows' verdicts.
 *
 * @since 2.9
 */
function wpematico_ajax_check_connectivity() {
	check_ajax_referer('wpematico-connectivity', 'nonce');
	if (!current_user_can('manage_options')) {
		wp_send_json_error(array('message' => __('You are not allowed to do this.', 'wpematico')), 403);
	}
	delete_transient('wpematico_remote_connectivity');
	$result = wpematico_run_remote_connectivity_probe();
	wp_send_json_success(array(
		'get'	  => (bool) $result['get'],
		'post'	  => (bool) $result['post'],
		'checked' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $result['checked']),
		// The report was rendered before this test ran, so send a fresh one: it is the
		// text the download button posts back.
		'report'  => wpematico_debug_info_get(),
	));
}
add_action('wp_ajax_wpematico_check_connectivity', 'wpematico_ajax_check_connectivity');

/**
 * Renders the value cell of a connectivity row.
 *
 * A null value means the test has not run yet on this site; the cell then shows a
 * spinner, and the result is filled in over AJAX.
 *
 * @since 2.9
 * @param string    $which 'get' or 'post'
 * @param bool|null $works
 * @return string
 */
function wpematico_connectivity_cell($which, $works) {
	$fn = ('post' === $which) ? 'wp_remote_post()' : 'wp_remote_get()';
	if (null === $works) {
		return '<span class="spinner is-active" style="float:none;margin:0 6px 0 0"></span>'
			. esc_html__('Checking the connection to etruel.com…', 'wpematico');
	}
	if ($works) {
		return '<mark class="yes">&#10004;</mark>';
	}
	return '<mark class="error">' . sprintf(
		/* translators: %1$s: PHP function name. %2$s: the URL that could not be reached. */
		esc_html__('%1$s failed. Some plugins features may not work. Please contact your hosting provider and make sure that %2$s is not blocked.', 'wpematico'),
		esc_html($fn),
		'https://etruel.com/downloads/feed/'
	) . '</mark>';
}

/**
 * Checks whether this server's HTML Tidy library honours the "do not wrap" setting.
 *
 * Some builds of libtidy wrap the text after every word instead, which reaches imported
 * posts as a line break between all words on sites using the Full Content addon.
 *
 * @since 2.9
 * @return string 'na' when Tidy is not installed, 'ok', or 'broken'
 */
function wpematico_tidy_wrap_status() {
	static $status = null;
	if (null !== $status) {
		return $status;
	}
	if (!function_exists('tidy_parse_string')) {
		return $status = 'na';
	}
	$tidy = tidy_parse_string(
		'<p>uno dos tres cuatro cinco seis siete ocho nueve diez once doce trece</p>',
		array('show-body-only' => true, 'char-encoding' => 'utf8', 'wrap' => 0),
		'UTF8'
	);
	tidy_clean_repair($tidy);
	return $status = (substr_count(trim((string) $tidy->value), "\n") > 1) ? 'broken' : 'ok';
}

/**

 *

 * @global object $wpdb

 * @staticvar array $vars

 * @return type array $vars to extract

 */

function wpematico_debug_data() {

	static $vars = array();

	if (empty($vars)) {

		global $wpdb;

		if (!class_exists('Browser'))

			require_once dirname(__FILE__) . '/lib/browser.php';  //https://github.com/cbschuld/Browser.php



		$vars['browser'] = new Browser();



		$vars['campaigns_info'] = wpematico_campaigns_info_data();

		// Get theme info

		if (get_bloginfo('version') < '3.4') {

			$theme_data	   = get_theme_data(get_stylesheet_directory() . '/style.css');

			$vars['theme'] = $theme_data['Name'] . ' ' . $theme_data['Version'];

		} else {

			$theme_data	   = wp_get_theme();

			$vars['theme'] = $theme_data->Name . ' ' . $theme_data->Version;

		}



		// Try to identify the hosting provider

		$vars['host'] = wpematico_get_host();

		$vars['environment_type'] = function_exists('wp_get_environment_type') ? wp_get_environment_type() : 'production';



		$vars['home_url']		  = home_url();

		$vars['site_url']		  = site_url();

		$vars['is_multisite']	  = is_multisite();

		$vars['db_version']		  = $wpdb->db_version();

		// Read from the plugin header, which is what WordPress itself enforces.
		$vars['php_required'] = wpematico_required_php();

		$vars['php_ok']			  = version_compare(phpversion(), $vars['php_required'], '>=');

		// Cached result only. The test itself runs over AJAX once the screen is up, so
		// rendering never waits on the network. See wpematico_remote_connectivity().
		$connectivity			  = wpematico_remote_connectivity();

		$vars['remote_checked']	  = $connectivity['checked'];

		$vars['remote_get_work']  = $connectivity['get'];

		$vars['remote_post_work'] = $connectivity['post'];



		$vars['front_page_id'] = get_option('page_on_front');

		$vars['blog_page_id']  = get_option('page_for_posts');



		$disk_total = wpematico_disk_total_space(false);

		$disk_free	= wpematico_disk_free_space(false);

		$server_sw	= isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '';

		if ((stripos($server_sw, 'apache') !== false) || ($disk_total && $disk_free)) {

			$vars['disk_total_space'] = $disk_total;

			$vars['disk_free_space']  = $disk_free;

		} else {

			$vars['disk_total_space'] = 'N/A';

			$vars['disk_free_space']  = 'N/A';

		}



		$vars['fsmethod'] = wpematico_getFileSystemMethod();



		$vars['professional_help'] = '<a href="https://etruel.com/downloads/wpematico-professional/" target="_blank">WPeMatico Professional</a>';

		$vars['cache_help']		   = '<a href="https://etruel.com/downloads/wpematico-cache/" target="_blank">WPeMatico Cache</a>';

		$vars['mmf_help']		   = '<a href="https://etruel.com/downloads/wpematico-make-feed-good/" target="_blank">Make Me Feed</a>';

		$vars['polyglot_help']	   = '<a href="https://etruel.com/downloads/wpematico-polyglot/" target="_blank">WPeMatico PolyGlot</a>';

		$vars['full_help']		   = '<a href="https://etruel.com/downloads/wpematico-full-content/" target="_blank">WPeMatico Full Content</a>';

		$vars['better_help']	   = '<a href="https://etruel.com/downloads/wpematico-better-excerpts/" target="_blank">WPeMatico Better Excerpts</a>';

		$vars['chinese_help']	   = '<a href="https://etruel.com/downloads/wpematico-chinese-tags/" target="_blank">WPeMatico Chinese Tags</a>';

		$vars['facebook_help']	   = '<a href="https://etruel.com/downloads/wpematico-facebook-fetcher/" target="_blank">WPeMatico Facebook Fetcher</a>';

		$vars['thumbnail_help']	   = '<a href="https://etruel.com/downloads/wpematico-thumbnail-scratcher/" target="_blank">WPeMatico Thumbnail Scratcher</a>';

		$vars['smtp_help']		   = '<a href="https://etruel.com/downloads/wpematico-smtp/" target="_blank">WPeMatico SMTP</a>';



		$vars['pcre_ok']	 = extension_loaded('pcre');

		$vars['curl_ok']	 = function_exists('curl_exec');

		//$vars['curl_ok'] 		= extension_loaded('curl');

		$vars['zlib_ok']	 = extension_loaded('zlib');

		$vars['mbstring_ok'] = extension_loaded('mbstring');

		$vars['iconv_ok']	 = extension_loaded('iconv');

		$vars['ssl_ok']		 = extension_loaded('openssl');


		$vars['ZipArchive']	 = class_exists('ZipArchive');

		$vars['DOMDocument'] = class_exists('DOMDocument');

		$vars['GD_ok']		 = (extension_loaded('gd') && function_exists('gd_info'));



		if (function_exists('apache_get_modules')) {

			$vars['apache_get_modules'] = true;

			$apache_modules				= apache_get_modules();

			$vars['m_rewrite_ok']		= in_array('mod_rewrite', $apache_modules);

			$vars['m_mime_ok']			= in_array('mod_mime', $apache_modules);

			$vars['m_deflate_ok']		= in_array('mod_deflate', $apache_modules);

		} else {

			$vars['apache_get_modules'] = false;

			$vars['m_rewrite_ok']		= (isset($_SERVER['HTTP_MOD_REWRITE']) && $_SERVER['HTTP_MOD_REWRITE'] == 'On') ? true : FALSE;

			$vars['m_mime_ok']			= FALSE;

			$vars['m_deflate_ok']		= FALSE;

		}

		if (extension_loaded('xmlreader')) {

			$vars['xml_ok'] = true;

		} elseif (extension_loaded('xml')) {

			$parser_check	= xml_parser_create();

			xml_parse_into_struct($parser_check, '<foo>&amp;</foo>', $values);

			xml_parser_free($parser_check);

			$vars['xml_ok'] = isset($values[0]['value']);

		} else {

			$vars['xml_ok'] = false;

		}



		$vars['wp_memory']			 = wpematico_let_to_num(WP_MEMORY_LIMIT);

		$vars['wp_max_upload_size']	 = wp_max_upload_size();

		$vars['permalink_structure'] = get_option('permalink_structure') ? get_option('permalink_structure') : 'Default';

		$vars['show_on_front']		 = get_option('show_on_front');

		// Only show page specs if frontpage is set to 'page'

		if ($vars['show_on_front'] == 'page') {

			$front_page_id			  = get_option('page_on_front');

			$blog_page_id			  = get_option('page_for_posts');

			$vars['wp_front_page_id'] = ($front_page_id != 0 ? get_the_title($front_page_id) . ' (#' . $front_page_id . ')' : 'Unset');

			$vars['wp_blog_page_id']  = ($blog_page_id != 0 ? get_the_title($blog_page_id) . ' (#' . $blog_page_id . ')' : 'Unset');

		}

		$vars['db_prefix']	= strlen($wpdb->prefix);

		$vars['post_stati'] = implode(', ', get_post_stati());



		$vars['active_plugins'] = wpematico_get_active_plugins();

		$vars['muplugins']		= get_mu_plugins();

		if (!is_array($vars['muplugins'])) {

			$vars['muplugins'] = array();

		}



		if (function_exists('ini_get')) {

			$vars['allow_url_fopen']	 = ini_get('allow_url_fopen');

			$vars['memory']				 = wpematico_let_to_num(ini_get('memory_limit'));

			$vars['time_limit']			 = ini_get('max_execution_time');

			$vars['ini_set']			 = ini_set('max_execution_time', $vars['time_limit']) === false ? false : true;

			$vars['disable_functions']	 = ini_get('disable_functions');

			$vars['upload_max_filesize'] = ini_get('upload_max_filesize');

			$vars['post_max_size']		 = ini_get('post_max_size');

			$vars['max_input_vars']		 = ini_get('max_input_vars');

			$vars['required_input_vars'] = 0; // 12000 + ( 500 + 1000 );	// 1000 = theme options



			$vars['display_errors'] = ini_get('display_errors');



			$vars['session_name']			  = ini_get('session.name');

			$vars['session_cookie_path']	  = ini_get('session.cookie_path');

			$vars['session_save_path']		  = ini_get('session.save_path');

			$vars['session_use_cookies']	  = ini_get('session.use_cookies');

			$vars['session_use_only_cookies'] = ini_get('session.use_only_cookies');



			$vars['suhosin_max_input_vars']		   = ini_get('suhosin.post.max_vars');

			$vars['suhosin_required_input_vars']   = 0; //$required_input_vars + ( 500 + 1000 );

			$vars['suhosin_max_request_vars']	   = ini_get('suhosin.request.max_vars');

			$vars['suhosin_required_request_vars'] = 0; //$suhosin_required_request_vars + ( 500 + 1000 );

			$vars['suhosin_max_value_length']	   = ini_get("suhosin.post.max_value_length");

			$vars['recommended_max_value_length']  = 0; //2000000;

		}

		$vars['cron_array'] = _get_cron_array();

		$vars['schedules']	= wp_get_schedules();

	}



	return $vars;

}



/**

 * Shows all data into a table

 */

function wpematico_show_data_info() {

	$debug_data = wpematico_debug_data();

	extract($debug_data);

	?>

	<h3 class="screen-reader-text"><?php _e('Server Environment', 'wpematico'); ?></h3>

	<div class="wpe_table-responsive">

		<table class="widefat debug-section wpe_table" cellspacing="0">

			<thead>

				<tr>

					<th colspan="3" class="debug-section-title" data-export-label="Server Environment"><?php _e('Server Environment', 'wpematico'); ?></th>

				</tr>

			</thead>

			<tbody>

				<?php if ($host) : ?>

					<tr>

						<td data-export-label="Hosting Provider"><?php _e('Hosting Provider:', 'wpematico'); ?></td>

						<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('Information about the hosting provider of your site.', 'wpematico') . '">[?]</a>'; ?></td>

						<td><?php echo $host; ?></td>

					</tr>

				<?php endif; ?>

				<tr>

					<td data-export-label="Environment Type"><?php _e('Environment Type:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('What WordPress considers this site to be: production, staging, development or local. Set with WP_ENVIRONMENT_TYPE.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php echo ('production' === $environment_type)
							? '<mark class="yes">' . esc_html($environment_type) . '</mark>'
							: '<mark class="no">' . esc_html($environment_type) . '</mark>'; ?></td>

				</tr>

				<tr>

					<td data-export-label="Server Info"><?php _e('Server Info:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('Information about the web server that is currently hosting your site.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><strong><?php echo esc_html(isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : ''); ?></strong> 

						<?php

						if (strpos(strtolower(isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : ''), 'litespeed')) {

							/* translators: %s The name of the Software of Server */

							echo '<mark class="error">' . sprintf(__('Some users have reported problems with %s.', 'wpematico'), $_SERVER['SERVER_SOFTWARE']) . '</mark>';

						}

						?>

					</td>

				</tr>

				<tr>

					<td data-export-label="MySQL Version"><?php _e('MySQL Version:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('The version of MySQL installed on your hosting server.', 'wpematico') . '">[?]</a>'; ?></td>

					<td>

						<?php echo $db_version; ?>

					</td>

				</tr>

				<tr>

					<td data-export-label="PHP Version"><?php _e('PHP Version:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('The version of PHP installed on your hosting server.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php

						if (!$php_ok) {

							echo '<mark class="error">' . sprintf(
									/* translators: %1$s: the PHP version running. %2$s: the minimum PHP version WPeMatico needs. */
									esc_html__('%1$s - WPeMatico requires PHP %2$s or newer.', 'wpematico'),
									esc_html(phpversion()),
									esc_html($php_required)
							) . '</mark>';

						} else {

							echo '<mark class="yes">' . esc_html(phpversion()) . '</mark>';

						}

						?></td>

				</tr>

				<tr>

					<td data-export-label="Disk Total Space"><?php _e('Disk Total Space:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('The total size of a filesystem or disk partition.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php echo $disk_total_space; ?></td>

				</tr>



				<tr>

					<td data-export-label="Disk Free Space"><?php _e('Disk Free Space:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('The free space on that filesystem or disk partition.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php echo $disk_free_space; ?></td>

				</tr>

				<?php if (stripos(isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '', 'apache') !== false && $apache_get_modules) : ?>

					<tr>

						<td data-export-label="Mod Rewrite"><?php _e('Mod Rewrite:', 'wpematico'); ?></td>

						<td class="help"><?php

							echo '<a href="#" class="help_tip" data-tip="' .

							/* translators: %1$s apache module. %2$s plugin name  */

							esc_attr(sprintf(__('%1$s is required by %2$s.', 'wpematico'), 'Mod Rewrite', $cache_help)) . '">[?]</a>';

							?></td>

						<td><?php

							echo ($m_rewrite_ok) ? '<mark class="yes">&#10004;</mark>' : '<mark class="' . (defined('WPEMATICO_CACHE_VERSION') ? 'error' : 'error-no-install') . '">' .

									/* translators: %1$s apache module. %2$s plugin name  */

									sprintf(__('%1$s is not installed on your server, but is recommended by %2$s.', 'wpematico'), 'Mod Rewrite', 'some addons') . '</mark>';

							?></td>

					</tr>

					<tr>

						<td data-export-label="Mod Mime"><?php _e('Mod Mime:', 'wpematico'); ?></td>

						<td class="help"><?php

							echo '<a href="#" class="help_tip" data-tip="' .

							/* translators: %1$s apache module. %2$s plugin name  */

							esc_attr(sprintf(__('%1$s is required by %2$s.', 'wpematico'), 'Mod Mime', $cache_help)) . '">[?]</a>';

							?></td>

						<td><?php

							echo ($m_mime_ok) ? '<mark class="yes">&#10004;</mark>' : '<mark class="' . (defined('WPEMATICO_CACHE_VERSION') ? 'error' : 'error-no-install') . '">' .

									/* translators: %1$s apache module. %2$s plugin name  */

									sprintf(__('%1$s is not installed on your server, but is recommended by %2$s.', 'wpematico'), 'Mod Mime', 'some addons') . '</mark>';

							?></td>

					</tr>

					<tr>

						<td data-export-label="Mod Deflate"><?php _e('Mod Deflate:', 'wpematico'); ?></td>

						<td class="help"><?php

							echo '<a href="#" class="help_tip" data-tip="' .

							/* translators: %1$s apache module. %2$s plugin name  */

							esc_attr(sprintf(__('%1$s is required by %2$s.', 'wpematico'), 'Mod Deflate', $cache_help)) . '">[?]</a>';

							?></td>

						<td><?php

							echo ($m_deflate_ok) ? '<mark class="yes">&#10004;</mark>' : '<mark class="' . (defined('WPEMATICO_CACHE_VERSION') ? 'error' : 'error-no-install') . '">' .

									/* translators: %1$s apache module. %2$s plugin name  */

									sprintf(__('%1$s is not installed on your server, but is recommended by %2$s.', 'wpematico'), 'Mod Deflate', 'some addons') . '</mark>';

							?></td>

					</tr>

				<?php endif; ?>

			</tbody>

		</table>

	</div>



	<h3 class="screen-reader-text"><?php _e('PHP Environment', 'wpematico'); ?></h3>

	<div class="wpe_table-responsive">

		<table class="widefat debug-section wpe_table" cellspacing="0">

			<thead>

				<tr>

					<th colspan="3" class="debug-section-title" data-export-label="PHP Environment"><?php _e('PHP Environment', 'wpematico'); ?></th>

				</tr>

			</thead>

			<tbody>

				<?php if (function_exists('ini_get')) : ?>

					<tr>

						<td data-export-label="PHP Post Max Size"><?php _e('PHP Post Max Size:', 'wpematico'); ?></td>

						<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('The largest file size that can be contained in one post.', 'wpematico') . '">[?]</a>'; ?></td>

						<td><?php echo size_format(wpematico_let_to_num($post_max_size)); ?></td>

					</tr>

					<tr>

						<td data-export-label="PHP Max Input Vars"><?php _e('PHP Max Input Vars:', 'wpematico'); ?></td>

						<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('How many variables your server accepts in a single request.', 'wpematico') . '">[?]</a>'; ?></td>

						<?php

						?>

						<td><?php

							if ($max_input_vars < $required_input_vars) {

								echo '<mark class="error">' .

								/* translators: %1$s current value. %2$s Recommended Value. */

								sprintf(__('%1$s - Recommended Value: %2$s.', 'wpematico') . '<br />' . __('Max input vars limitation could truncate POST data.', 'wpematico'),

										$max_input_vars,

										'<strong>' . $required_input_vars . '</strong>') .

								'</mark>';

							} else {

								echo '<mark class="yes">' . $max_input_vars . '</mark>';

							}

							?></td>

					</tr>

					<tr>

						<td data-export-label="PHP Time Limit"><?php _e('PHP Time Limit:', 'wpematico'); ?></td>

						<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('How many seconds your site may spend on a single operation before the server stops it.', 'wpematico') . '">[?]</a>'; ?></td>

						<td><?php

							if ($time_limit < 180 && $time_limit != 0) {

								echo '<mark class="no">' .

								sprintf(

										/* translators: %1$s current Time limit value. %2$s URL. */

										__('%1$s - We recommend setting max execution time to at least 180. ', 'wpematico')

										. '<br />'

										. __('To give a campaign 5 minutes to run without timeouts, ', 'wpematico')

										. '<strong>300</strong>'

										. __('seconds of max execution time is required.', 'wpematico')

										. '<br />'

										. __('See: ', 'wpematico')

										. '<a href="%2$s" target="_blank">' . __('Increasing max execution to PHP', 'wpematico') . '</a>',

										$time_limit,

										'http://codex.wordpress.org/Common_WordPress_Errors#Maximum_execution_time_exceeded') .

								'</mark>';

							} else {

								echo '<mark class="yes">' . $time_limit . '</mark>';

								if ($time_limit < 300 && $time_limit != 0) {

									echo '<br /><mark class="no">' . __('Current time limit is sufficient, but if you want to give 5 minutes to run without timeouts to each campaign, the required time is 300.', 'wpematico') . '</mark>';

								}

							}

							?></td>

					</tr>

					<tr>

						<td data-export-label="PHP Memory Limit"><?php _e('PHP Memory Limit:', 'wpematico'); ?></td>

						<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('The maximum amount of memory (RAM) that your PHP allows in this server.', 'wpematico') . '">[?]</a>'; ?></td>

						<td><?php

							if ($memory < 128000000) {

								echo '<mark class="error">' . sprintf(

										/* translators: %1$s current PHP Memory limit value. %2$s URL. */

										__('%s - We recommend setting memory to at least', 'wpematico') . '<strong>128MB</strong>'

										. '<br />'

										. __('Please define memory limit in php.ini file.', 'wpematico'),

										size_format($memory),

										'http://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP')

								. '</mark>';

							} else {

								echo '<mark class="yes">' . size_format($memory) . '</mark>';

							}

							?></td>

					</tr>

					<tr>

						<td data-export-label="Allow URL fopen"><?php _e('Allow URL fopen:', 'wpematico'); ?></td>

						<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('Enables the URL-aware fopen wrappers that enable accessing URL object like files.', 'wpematico') . '">[?]</a>'; ?></td>

						<td><?php

							if ($allow_url_fopen) {

								echo '<mark class="yes">' . 'On' . '</mark>';

							} else {

								echo '<mark class="error">Off - ' . sprintf(

										__('We recommend turning Allow URL fopen on. ', 'wpematico')

										. '<br />'

										. __('See: ', 'wpematico')

										. '<a href="%s" target="_blank">'

										. __('PHP: Allow URL fopen.', 'wpematico')

										. '</a>.',

										'http://php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen')

								. '</mark>';

							}

							?></td>

					</tr>

					<tr>

						<td data-export-label="ini_set"><?php _e('ini_set:', 'wpematico'); ?></td>

						<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('Lets a script change a PHP setting while it runs; the previous value comes back when it ends. ', 'wpematico') . '">[?]</a>'; ?></td>

						<td><?php

							if ($ini_set) {

								echo '<mark class="yes">' . 'On' . '</mark>';

							} else {

								echo '<mark class="no">Off - ' . sprintf(

										__('We recommend enabling ini_set() on your server. ', 'wpematico')

										. '<br />'

										. __('See: ', 'wpematico')

										. '<a href="%s" target="_blank">PHP: ini_set. </a>.',

										'http://php.net/manual/en/function.ini-set.php')

								. '</mark>';

							}

							?></td>

					</tr>

					<tr>

						<td data-export-label="PHP Disabled Functions"><?php _e('PHP Disabled Functions:', 'wpematico'); ?></td>

						<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('PHP disabled functions to avoid potential unknown vulnerabilities.', 'wpematico') . '">[?]</a>'; ?></td>

						<td><?php echo str_replace(',', ',<br/>', $disable_functions); ?></td>

					</tr>

					<tr>

						<td data-export-label="PHP Display Errors"><?php _e('PHP Display Errors:', 'wpematico'); ?></td>

						<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('Shows or hides the PHP errors and warnings of your site.', 'wpematico') . '">[?]</a>'; ?></td>

						<td><?php echo ($display_errors ? __('On', 'wpematico') . ' (' . $display_errors . ')' : 'N/A'); ?></td>

					</tr>



				<?php endif; ?>

				<tr>

					<td data-export-label="PHP Current error_reporting levels"><?php _e('PHP Current error_reporting levels:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('PHP error_reporting — Shows which PHP errors are currently reported. ', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php

						$errLvl = error_reporting();

						for ($i = 0; $i < 15; $i++) {

							print wpematico_FriendlyErrorType($errLvl & pow(2, $i)) . "<br>\n";

						}

						?></td>

				</tr>

				<?php ?>

				<tr>

					<td data-export-label="cURL (php.net/curl)"><?php _e('cURL (php.net/curl):', 'wpematico'); ?></td>

					<td class="help"><?php

						/* translators: %1$s current value. %2$s Plugins list. */

						echo '<a href="#" class="help_tip" data-tip="' . esc_attr(sprintf(__('%1$s is required by %2$s.', 'wpematico'), 'cURL (php.net/curl)', 'WPeMatico Core, ' . $professional_help . ', ' . $cache_help . ', ' . $mmf_help . ', ' . $polyglot_help)) . '">[?]</a>';

						?></td>

					<td><?php

						/* translators: %1$s current value. %2$s Plugins list. */

						echo ($curl_ok) ? '<mark class="yes">' . esc_html(WPeMatico::get_curl_version()) . '</mark>' : '<mark class="error">' . sprintf(__('%1$s is not installed on your server, but is recommended by %2$s.', 'wpematico'), 'cURL (php.net/curl)', 'some addons and SimplePie') . '</mark>';

						?></td>

				</tr>

				<tr>

					<td data-export-label="ZipArchive"><?php _e('ZipArchive:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('ZipArchive is recommended. They can be used to import and export zip files.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php

						/* translators: %1$s current value. %2$s Plugins list. */

						echo $ZipArchive ? '<mark class="yes">&#10004;</mark>' : '<mark class="error">' . sprintf(__('%1$s is not installed on your server, but is recommended by %2$s.', 'wpematico'), 'ZipArchive', 'WPeMatico Core') . '</mark>';

						?></td>

				</tr>

				<tr>

					<td data-export-label="DOMDocument"><?php _e('DOMDocument:', 'wpematico'); ?></td>

					<td class="help"><?php

						/* translators: %1$s current value. %2$s Plugins list. */

						echo '<a href="#" class="help_tip" data-tip="' . esc_attr(sprintf(__('%1$s is recommended by %2$s.', 'wpematico'), 'DOMDocument', 'WPeMatico Core')) . '">[?]</a>';

						?></td>

					<td><?php

						/* translators: %1$s current value. %2$s Plugins list. */

						echo $DOMDocument ? '<mark class="yes">&#10004;</mark>' : '<mark class="error">' . sprintf(__('%1$s is not installed on your server, but is recommended by %2$s.', 'wpematico'), 'DOMDocument', 'some addons') . '</mark>';

						?></td>

				</tr>

				<tr>

					<td data-export-label="Tidy"><?php _e('Tidy (HTML):', 'wpematico'); ?></td>

					<td class="help"><?php

						echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('Full Content passes every fetched page through Tidy. Some builds of the library ignore the "do not wrap" setting and break the text after every word, which shows up as a line break between all the words of the imported post.', 'wpematico') . '">[?]</a>';

						?></td>

					<td><?php

						$tidy_status = wpematico_tidy_wrap_status();

						if ('na' === $tidy_status) {

							echo '<mark class="yes">' . esc_html__('Not installed (not needed unless you use Full Content).', 'wpematico') . '</mark>';

						} elseif ('ok' === $tidy_status) {

							echo '<mark class="yes">&#10004; ' . esc_html(tidy_get_release()) . '</mark>';

						} else {

							echo '<mark class="error">' . sprintf(

								/* translators: %s: the libtidy release date reported by the server. */

								esc_html__('Broken line wrapping (libtidy %s). This build ignores "wrap: 0" and breaks the text after every word. Posts imported with the Full Content addon will show a line break between all words. Ask your host to update libtidy.', 'wpematico'),

								esc_html(tidy_get_release())

							) . '</mark>';

						}

						?></td>

				</tr>

				<tr>

					<td data-export-label="GD Library"><?php _e('GD Library:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('WPeMatico uses this library to resize images and speed up your site\'s loading time', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php

						/* translators: %1$s current value. %2$s Plugins list. */

						echo $GD_ok ? '<mark class="yes">&#10004;</mark>' : '<mark class="error">' . sprintf(__('%1$s is not installed on your server, but is recommended by %2$s.', 'wpematico'), 'GD', 'WPeMatico Core') . '</mark>';

						?></td>

				</tr>

				<tr>

					<td data-export-label="XML (php.net/xml)"><?php _e('XML (php.net/xml):', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('XML (php.net/xml) is required.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php

						/* translators: %1$s current value. %2$s Plugins list. */

						echo ($xml_ok) ? '<mark class="yes">&#10004;</mark>' : '<mark class="error">' . sprintf(__('%1$s is not installed on your server, but is recommended by %2$s.', 'wpematico'), 'XML (php.net/xml)', 'WPeMatico Core') . '</mark>';

						?></td>

				</tr>

				<tr>

					<td data-export-label="PCRE (php.net/pcre)"><?php _e('PCRE (php.net/pcre):', 'wpematico'); ?></td>

					<td class="help"><?php

						/* translators: %1$s current value. %2$s Plugins list. */

						echo '<a href="#" class="help_tip" data-tip="' . esc_attr(sprintf(__('%1$s is required by %2$s.', 'wpematico'), 'PCRE (php.net/pcre)', 'WPeMatico Core, ' . $professional_help . ', ' . $full_help . ', ' . $better_help . ', ' . $cache_help . ', ' . $chinese_help . ', ' . $facebook_help . ', ' . $mmf_help . ', ' . $thumbnail_help . ', ' . $thumbnail_help . '')) . '">[?]</a>';

						?></td>

					<td><?php

						/* translators: %1$s current value. %2$s Plugins list. */

						echo ($pcre_ok) ? '<mark class="yes">&#10004;</mark>' : '<mark class="error">' . sprintf(__('%1$s is not installed on your server, but is recommended by %2$s.', 'wpematico'), 'PCRE (php.net/pcre)', 'some addons and WPeMatico Core') . '</mark>';

						?></td>

				</tr>

				<tr>

					<td data-export-label="Zlib (php.net/zlib)"><?php _e('Zlib (php.net/zlib):', 'wpematico'); ?></td>

					<td class="help"><?php

						/* translators: %1$s current value. %2$s Plugins list. */

						echo '<a href="#" class="help_tip" data-tip="' . esc_attr(sprintf(__('%1$s is required by %2$s.', 'wpematico'), 'Zlib (php.net/zlib)', 'WPeMatico Core, ' . $cache_help)) . '">[?]</a>';

						?></td>

					<td><?php

						/* translators: %1$s current value. %2$s Plugins list. */

						echo ($zlib_ok) ? '<mark class="yes">&#10004;</mark>' : '<mark class="error">' . sprintf(__('%1$s is not installed on your server, but is recommended by %2$s.', 'wpematico'), 'Zlib (php.net/zlib)', 'some addons and WPeMatico Core') . '</mark>';

						?></td>

				</tr>

				<tr>

					<td data-export-label="php.net/mbstring"><?php _e('php.net/mbstring:', 'wpematico'); ?></td>

					<td class="help"><?php

						/* translators: %1$s current value. %2$s Plugins list. */

						echo '<a href="#" class="help_tip" data-tip="' . esc_attr(sprintf(__('%1$s is required by %2$s.', 'wpematico'), 'php.net/mbstring', 'WPeMatico Core, ' . $full_help . ', ' . $chinese_help . ', ' . $mmf_help)) . '">[?]</a>';

						?></td>

					<td><?php

						/* translators: %1$s current value. %2$s Plugins list. */

						echo ($mbstring_ok) ? '<mark class="yes">&#10004;</mark>' : '<mark class="error">' . sprintf(__('%1$s is not installed on your server, but is recommended by %2$s.', 'wpematico'), 'php.net/mbstring', 'some addons and WPeMatico Core') . '</mark>';

						?></td>

				</tr>

				<tr>

					<td data-export-label="iconv (php.net/iconv)"><?php _e('iconv (php.net/iconv):', 'wpematico'); ?></td>

					<td class="help"><?php

						/* translators: %1$s current value. %2$s Plugins list. */

						echo '<a href="#" class="help_tip" data-tip="' . esc_attr(sprintf(__('%1$s is required by %2$s.', 'wpematico'), 'iconv (php.net/iconv)', 'WPeMatico Core, ' . $full_help . ', ' . $mmf_help)) . '">[?]</a>';

						?></td>

					<td><?php

						/* translators: %1$s current value. %2$s Plugins list. */

						echo ($iconv_ok) ? '<mark class="yes">&#10004;</mark>' : '<mark class="error">' . sprintf(__('%1$s is not installed on your server, but is recommended by %2$s.', 'wpematico'), 'iconv (php.net/iconv)', 'some addons and WPeMatico Core') . '</mark>';

						?></td>

				</tr>

				<tr>

					<td data-export-label="OpenSSL (php.net/openssl)"><?php _e('OpenSSL (php.net/openssl):', 'wpematico'); ?></td>

					<td class="help"><?php

						/* translators: %1$s current value. %2$s Plugins list. */

						echo '<a href="#" class="help_tip" data-tip="' . esc_attr(sprintf(__('%1$s is required by %2$s.', 'wpematico'), 'OpenSSL (php.net/openssl)', $smtp_help)) . '">[?]</a>';

						?></td>

					<td><?php

						/* translators: %1$s current value. %2$s Plugins list. */

						echo ($ssl_ok) ? '<mark class="yes">&#10004;</mark>' : '<mark class="' . (defined('WPESMTP_VERSION') ? 'error' : 'error-no-install') . '">' . sprintf(__('%1$s is not installed on your server, but is recommended by %2$s.', 'wpematico'), 'OpenSSL (php.net/openssl)', 'some addons') . '</mark>';

						?></td>

				</tr>


				<tr>

					<td data-export-label="Session enabled"><?php echo '$_SESSION ' . __('enabled:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('PHP session configuration. https://www.php.net/manual/en/reserved.variables.session.php', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php echo isset($_SESSION) ? '&#10004;' : '&ndash;'; ?></td>

				</tr>

				<?php if (isset($_SESSION)) : ?>

					<tr>

						<td data-export-label="Session Name"><?php _e('Session Name:', 'wpematico'); ?></td>

						<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('The Session Name.', 'wpematico') . '">[?]</a>'; ?></td>

						<td><?php echo esc_html($session_name); ?></td>

					</tr>

					<tr>

						<td data-export-label="Cookie Path"><?php _e('Cookie Path:', 'wpematico'); ?></td>

						<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('The Session Cookie Path.', 'wpematico') . '">[?]</a>'; ?></td>

						<td><?php echo esc_html($session_cookie_path); ?></td>

					</tr>

					<tr>

						<td data-export-label="Save Path"><?php _e('Save Path:', 'wpematico'); ?></td>

						<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('The Session Save Path.', 'wpematico') . '">[?]</a>'; ?></td>

						<td><?php echo esc_html($session_save_path); ?></td>

					</tr>

					<tr>

						<td data-export-label="Use Cookies"><?php _e('Use Cookies:', 'wpematico'); ?></td>

						<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('Use Cookies.', 'wpematico') . '">[?]</a>'; ?></td>

						<td><?php echo ($session_use_cookies) ? '&#10004;' : '&ndash;'; ?></td>

					</tr>

					<tr>

						<td data-export-label="Use Only Cookies"><?php _e('Use Only Cookies:', 'wpematico'); ?></td>

						<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('Use Only Cookies.', 'wpematico') . '">[?]</a>'; ?></td>

						<td><?php echo ($session_use_only_cookies) ? '&#10004;' : '&ndash;'; ?></td>

					</tr>

				<?php endif; ?>



			</tbody>

		</table>

	</div>



	<h3 class="screen-reader-text"><?php _e('WordPress Environment', 'wpematico'); ?></h3>

	<div class="wpe_table-responsive">

		<table class="widefat debug-section wpe_table" cellspacing="0">

			<thead>

				<tr>

					<th colspan="3" class="debug-section-title" data-export-label="WordPress Environment"><?php _e('WordPress Environment', 'wpematico'); ?></th>

				</tr>

			</thead>

			<tbody>

				<tr>

					<td data-export-label="User Browser"><?php _e('User Browser:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('The browser you are using right now.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php echo "<pre style='margin: 0;font-size: 11px;'>$browser</pre>"; ?></td>

				</tr>

				<tr>

					<td data-export-label="Home URL"><?php _e('Home URL:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('The URL of your site\'s homepage.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php echo $home_url; ?></td>

				</tr>

				<tr>

					<td data-export-label="Site URL"><?php _e('Site URL:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('The root URL of your site.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php echo $site_url; ?></td>

				</tr>

				<tr>

					<td data-export-label="WP Version"><?php _e('WP Version:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('The version of WordPress installed on your site.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php echo bloginfo('version'); ?></td>

				</tr>

				<tr>

					<td data-export-label="WP Multisite"><?php _e('WP Multisite:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('Whether or not you have WordPress Multisite enabled.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php

						if ($is_multisite) {

							echo '<mark class="no">' . '&#10004;' . __('WPeMatico was not fully tested in Multisite. Test it and give us your comments on the ', 'wpematico') . '<a href="https://wordpress.org/support/plugin/wpematico/" target="_blank">' . __('forums', 'wpematico') . '</a>' . '</mark>';

						} else {

							echo '<mark class="yes">' . __('No', 'wpematico') . '</mark>';

						}

						?>

					</td>

				</tr>

				<tr>

					<td data-export-label="Simple Pie VERSION"><?php _e('Simple Pie VERSION:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('Minimum version required is 1.5.1.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php

						$from_wordpress = false;

						if (!class_exists('SimplePie')) {

							if (is_file(ABSPATH . WPINC . '/class-simplepie.php')) {

								include_once(ABSPATH . WPINC . '/class-simplepie.php');

								$from_wordpress = true;

							} else if (is_file(ABSPATH . 'wp-admin/includes/class-simplepie.php')) {

								include_once(ABSPATH . 'wp-admin/includes/class-simplepie.php');

								$from_wordpress = true;

							}

						}



						if ($from_wordpress) {

							echo '

					<code>'

							/* translators: %s Current SimplePie Version. */

							. sprintf(__('USING SimplePie %s included in WordPress', 'wpematico'), SIMPLEPIE_VERSION) . '</code>

				';

						}

						?>

					</td>

				</tr>

				<tr>

					<td data-export-label="Language WPLANG"><?php _e('Language WPLANG:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('The current language set in wp-config.php, WPLANG constant. Default = en_US', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php echo get_locale() ?></td>

				</tr>

				<tr>

					<td data-export-label="Language Setting"><?php _e('Language Setting:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('The current language used by WordPress. Default = English', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php echo (get_option('WPLANG') ? get_option('WPLANG') : 'Default') ?></td>

				</tr>



				<tr>

					<td data-export-label="Permalink Structure"><?php _e('Permalink Structure:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('The root URL of your site.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php echo $permalink_structure; ?></td>

				</tr>

				<tr>

					<td data-export-label="Active Theme"><?php _e('Active Theme:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('The version of WordPress installed on your site.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php echo $theme; ?></td>

				</tr>

				<tr>

					<td data-export-label="Show On Front"><?php _e('Show On Front:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('WordPress option Show On Front.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php echo $show_on_front; ?>

						<?php

						if ($show_on_front == 'page') {

							echo '<br>  Page On Front:  ' . ($wp_front_page_id != 'Unset' ? '<mark class="yes">' . $wp_front_page_id . '</mark>' : '<mark class="no">' . $wp_front_page_id . '</mark>') . '<br>';

							echo ' Page For Posts: ' . ($wp_blog_page_id != 'Unset' ? '<mark class="yes">' . $wp_blog_page_id . '</mark>' : '<mark class="no">' . $wp_blog_page_id . '</mark>');

						}

						?>

					</td>

				</tr>



				<tr>

					<td data-export-label="WP Remote Get"><?php _e('WP Remote Get:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('WPeMatico uses this method to communicate with the different RSS feeds and remote websites.', 'wpematico') . '">[?]</a>'; ?></td>

					<td class="wpem-connectivity" data-check="get"><?php echo wpematico_connectivity_cell('get', $remote_get_work); ?></td>

				</tr>

				<tr>

					<td data-export-label="WP Remote Post"><?php _e('WP Remote Post:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('WPeMatico uses this method to communicate with the different RSS feeds and remote websites', 'wpematico') . '">[?]</a>'; ?></td>

					<td class="wpem-connectivity" data-check="post"><?php echo wpematico_connectivity_cell('post', $remote_post_work); ?></td>

				</tr>

				<tr>

					<td data-export-label="Table Prefix"><?php _e('Table Prefix:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('The prefix of the DB tables names.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php echo 'Length: ' . $db_prefix . '   Status: ' . ($db_prefix > 16 ? '<mark class="error">ERROR: Too long</mark>' : '<mark class="yes">Acceptable</mark>') ?></td>

				</tr>

				<tr>

					<td data-export-label="WP Memory Limit"><?php _e('WP Memory Limit:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('The maximum amount of memory (RAM) that your site can use at one time.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php

						if ($wp_memory < 128000000) {

							/* translators: %s Current Memory in MB. */

							echo '<mark class="no">' . sprintf(__('%s - We recommend setting memory to at least ', 'wpematico') . '<strong>128MB</strong>. <br />' . __('Please define memory limit in wp-config.php file. To learn how, see: ', 'wpematico') . '<a href="%s" target="_blank">' . __('Increasing memory allocated to PHP.', 'wpematico') . '</a>', size_format($wp_memory), 'https://wordpress.org/support/article/editing-wp-config-php/#increasing-memory-allocated-to-php') . '</mark>';

						} else {

							echo '<mark class="yes">' . size_format($wp_memory) . '</mark>';

						}

						?></td>

				</tr>

				<tr>

					<td data-export-label="WP Max Upload Size"><?php _e('WP Max Upload Size:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('The largest file size that can be uploaded to your WordPress installation.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php echo size_format($wp_max_upload_size); ?></td>

				</tr>

				<tr>

					<td data-export-label="Registered post statuses"><?php _e('Registered post statuses:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('Post statuses registered by post types or plugins.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php echo str_replace(',', ',<br/>', $post_stati); ?></td>

				</tr>

				<tr>

					<td data-export-label="FileSystem-Method"><?php _e('FileSystem Method:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('FileSystem Method is used for WordPress\' own automatic updates feature. Most common value is "direct".', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php

						if ('direct' !== $fsmethod)

							echo '<mark class="no">' . $fsmethod . '</mark>';

						else

							echo '<mark class="yes">' . $fsmethod . '</mark>';

						?></td>

				</tr>

				<tr>

					<td data-export-label="WP Debug Mode"><?php _e('WP Debug Mode:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('Displays whether or not WordPress is in Debug Mode.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php

						if (defined('WP_DEBUG') && WP_DEBUG)

							echo '<mark class="no">' . '&#10004;' . '</mark>';

						else

							echo '<mark class="yes">' . '&ndash;' . '</mark>';

						?></td>

				</tr>

				<tr>

					<td data-export-label="WP Debug Log Mode"><?php _e('WP Debug Log Mode:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('Displays whether or not WordPress is writing its Debug in a file.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php

						if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG)

							echo '<mark class="no">' . '&#10004;' . '</mark>';

						else

							echo '<mark class="yes">' . '&ndash;' . '</mark>';

						?></td>

				</tr>

				<tr>

					<td data-export-label="WP Debug Display"><?php _e('WP Debug Mode Display:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('Displays whether or not WordPress is showing in its site all warnings and errors reported by its Debug Mode.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php

						if (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY)

							echo '<mark class="no">' . '&#10004;' . '</mark>';

						else

							echo '<mark class="yes">' . '&ndash;' . '</mark>';

						?></td>

				</tr>

				<tr>

					<td data-export-label="WP Cron"><?php _e('WP Cron:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('The cron function of WordPress.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php

						if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON)

							echo '<mark class="no">' . '&ndash;' . esc_attr__('With the cron function off, campaigns run only by hand or from an external cron.', 'wpematico') . '</mark>';

						else

							echo '<mark class="yes">' . '&#10004;' . '</mark>';

						?></td>

				</tr>

				<tr>

					<td data-export-label="WP Cron Lock Timeout"><?php _e('WP Cron Lock Timeout:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('Defines a period of time in which only one cronjob will be fired. Since WordPress 3.3. Value: time in seconds (Default: 60).', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php

						if (defined('WP_CRON_LOCK_TIMEOUT'))

							echo WP_CRON_LOCK_TIMEOUT == 60 ? '<mark class="yes">' . 60 . '</mark>' : '<mark class="error">' . WP_CRON_LOCK_TIMEOUT . '</mark>';

						else

							echo '<mark class="no">' . '&ndash;' . '</mark>';

						?></td>

				</tr>

				<tr>

					<td data-export-label="Alternate WP Cron"><?php _e('Alternate WP Cron:', 'wpematico'); ?></td>

					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__('Some servers disable the functionality that enables WordPress Cron to work properly. This constant provides an easy fix that should work on any server.', 'wpematico') . '">[?]</a>'; ?></td>

					<td><?php

						if (defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON)

							echo '<mark class="no">' . '&#10004;' . '</mark>';

						else

							echo '<mark class="yes">' . '&ndash;' . '</mark>';

						?> </td>

				</tr>

			</tbody>

		</table>

	</div>



	<?php //if (count( (array) $muplugins ) > 0 ) : 	   ?>

	<h3 class="screen-reader-text"><?php _e('Must-Use Plugins', 'wpematico'); ?></h3>

	<div class="wpe_table-responsive">

		<table class="widefat debug-section wpe_table wpe_table-plugins" cellspacing="0" id="status-muplugins">

			<thead>

				<tr>

					<th colspan="3" class="debug-section-title" data-export-label="Must-Use Plugins (<?php echo count($muplugins); ?>)"><?php _e('Must-Use Plugins', 'wpematico'); ?> (<?php echo count($muplugins); ?>)</th>

				</tr>

			</thead>

			<tbody>

				<?php

				foreach ($muplugins as $plugin => $plugin_data) {

					$new_version	= array_key_exists('new_version', $plugin_data) ? $plugin_data['new_version'] : '';

					$dirname		= array_key_exists('dirname', $plugin_data) ? $plugin_data['dirname'] : '';

					$version_string = array_key_exists('version_string', $plugin_data) ? $plugin_data['version_string'] : '';

					$network_string = array_key_exists('network_string', $plugin_data) ? $plugin_data['network_string'] : '';



					if (!empty($plugin_data['Name'])) {



						// link the plugin name to the plugin url if available

						$plugin_name = esc_html($plugin_data['Name']);



						if (!empty($plugin_data['PluginURI'])) {

							$plugin_name = '<a href="' . esc_url($plugin_data['PluginURI']) . '" title="' . __('Visit plugin homepage', 'wpematico') . '">' . $plugin_name . '</a>';

						}

						?>

						<tr>

							<td><?php echo $plugin_name; ?></td>

							<td class="wpe-plugin-version"><?php echo esc_html($plugin_data['Version']); ?>

								<?php if (!empty($new_version)) : ?>

									<span class="wpe-plugin-update"><?php

										/* translators: %s New Version number. */

										printf(esc_html__('update to %s', 'wpematico'), esc_html($new_version));

										?></span>

								<?php endif; ?>

							</td>

							<td><?php

								/* translators: %s plugin author. */

								printf(_x('by %s', 'by author', 'wpematico'), $plugin_data['Author']);

								?>

							</td>

						</tr>

						<?php

					}

				}

				?>

			</tbody>

		</table>

	</div>

	<?php //endif; // (count($muplugins ) > 0 	   ?>



	<h3 class="screen-reader-text"><?php _e('Active Plugins', 'wpematico'); ?></h3>

	<div class="wpe_table-responsive">

		<table class="widefat debug-section wpe_table wpe_table-plugins" cellspacing="0" id="status-plugins">

			<thead>

				<tr>

					<th colspan="3" class="debug-section-title" data-export-label="Active Plugins (<?php echo count((array) $active_plugins); ?>)"><?php _e('Active Plugins', 'wpematico'); ?> (<?php echo count((array) $active_plugins); ?>)</th>

				</tr>

			</thead>

			<tbody>

				<?php

				foreach ($active_plugins as $plugin) {

					$new_version	= $plugin['new_version'];

					$plugin_data	= $plugin['plugin_data'];

					$dirname		= $plugin['dirname'];

					$version_string = $plugin['version_string'];

					$network_string = $plugin['network_string'];



					if (!empty($plugin_data['Name'])) {



						// link the plugin name to the plugin url if available

						$plugin_name = esc_html($plugin_data['Name']);



						if (!empty($plugin_data['PluginURI'])) {

							$plugin_name = '<a href="' . esc_url($plugin_data['PluginURI']) . '" title="' . __('Visit plugin homepage', 'wpematico') . '">' . $plugin_name . '</a>';

						}

						?>

						<tr>

							<td><?php echo $plugin_name; ?></td>

							<td class="wpe-plugin-version"><?php echo esc_html($plugin_data['Version']); ?>

								<?php if (!empty($new_version)) : ?>

									<span class="wpe-plugin-update"><?php

										/* translators: %s New Version number. */

										printf(esc_html__('update to %s', 'wpematico'), esc_html($new_version));

										?></span>

								<?php endif; ?>

							</td>

							<td><?php

								/* translators: %s plugin author. */

								printf(_x('by %s', 'by author', 'wpematico'), $plugin_data['Author']);

								?>

							</td>

						</tr>

						<?php

					}

				}

				?>

			</tbody>

		</table>

	</div>



	<h3 class="screen-reader-text"><?php _e('Campaigns', 'wpematico'); ?></h3>

	<div class="wpe_table-responsive">

		<table class="widefat debug-section wpe_table wpe_table-grid" cellspacing="0">

			<thead>

				<tr>

					<th colspan="8" class="debug-section-title" data-export-label="Campaigns"><?php
						printf(
								/* translators: %s: number of campaigns on the site. */
								esc_html__('Campaigns (%s)', 'wpematico'),
								esc_html(isset($debug_data['campaigns_info'][0]['published_campaigns']) ? $debug_data['campaigns_info'][0]['published_campaigns'] : 0)
						);
						?></th>

				</tr>

				<tr>

					<th scope="col" class="manage-column"><?php esc_html_e('ID', 'wpematico') ?></th>

					<th scope="col" class="manage-column"><?php esc_html_e('Type', 'wpematico') ?></th>

					<th scope="col" class="manage-column"><?php esc_html_e('Publish as', 'wpematico') ?></th>

					<th scope="col" class="manage-column"><?php esc_html_e('Status', 'wpematico') ?></th>

					<th scope="col" class="manage-column num"><?php esc_html_e('Feeds', 'wpematico') ?></th>

					<th scope="col" class="manage-column num"><?php esc_html_e('Max items', 'wpematico') ?></th>

					<th scope="col" class="manage-column"><?php esc_html_e('Last Run', 'wpematico') ?></th>

					<th scope="col" class="manage-column"><?php esc_html_e('Next Run', 'wpematico') ?></th>

				</tr>

			</thead>

			<tbody>

				<?php foreach (array_slice($debug_data['campaigns_info'], 1) as $campaign) { ?>

					<tr>

						<td><?php echo esc_html($campaign[0]['ID']); ?></td>

						<td><?php echo esc_html($campaign[0]['campaign_type']); ?></td>

						<td><?php echo esc_html($campaign[0]['campaign_customposttype']); ?></td>

						<td><?php echo esc_html($campaign[0]['campaign_posttype']); ?></td>

						<td class="num"><?php echo count($campaign[0]['campaign_feeds']); ?></td>

						<td class="num"><?php echo esc_html($campaign[0]['campaign_max']); ?></td>

						<td class="wpe-date"><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), $campaign[0]['lastrun'])); ?></td>

						<td class="wpe-date"><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), $campaign[0]['cronnextrun'])); ?></td>

					</tr>

				<?php } ?>

			</tbody>

		</table>

	</div>



	<h3 class="screen-reader-text"><?php _e('Cron Schedules', 'wpematico'); ?></h3>

	<div class="wpe_table-responsive">

		<table class="widefat debug-section wpe_table wpe_table-grid" cellspacing="0" id="status">

			<thead>

				<tr>

					<th colspan="3" class="debug-section-title" data-export-label="Cron Schedules"><?php _e('Cron Schedules', 'wpematico'); ?> </th>

				</tr>

				<tr>

					<th scope="col" class="manage-column column-posts" style="">

						<span><?php _e('Next due', 'wpematico'); ?></span></th>

					<th scope="col" class="manage-column column-posts" style="">

						<span><?php _e('Schedule', 'wpematico'); ?></span></th>

					<th scope="col" class="manage-column column-posts" style="">

						<span><?php _e('Hook', 'wpematico'); ?></span></th>

				</tr>

			</thead>

			<tbody id="the-sites-list" class="list:sites">

				<?php

				foreach ($cron_array as $time => $cron) {

					foreach ($cron as $hook => $cron_info) {

						foreach ($cron_info as $key => $schedule) {

							?>

							<tr>

								<td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), $time)); ?></td>

								<td><?php echo esc_html((isset($schedule['schedule']) && isset($schedules[$schedule['schedule']]) && isset($schedules[$schedule['schedule']]['display'])) ? $schedules[$schedule['schedule']]['display'] : ''); ?> </td>

								<td><?php echo esc_html($hook); ?></td>

							</tr>

							<?php

						}

					}

				}

				?>

			</tbody>

		</table>

	</div>

	<?php

}



/**

 * Get system info

 *

 * @since       1.2.4

 * @access      public

 * @global      object $wpdb Used to query the database using the WordPress Database API

 * @return      string $return A string containing the info to output

 */

function wpematico_debug_info_get() {

	global $current_user;

	$cfg		= get_option(WPeMatico::OPTION_KEY);

	$cfg		= apply_filters('wpematico_check_options', $cfg);

	$debug_data = wpematico_debug_data();

	extract($debug_data);



	$return = '### Begin Debug Info ###' . "\n\n";



	$return .= "" . '-- Server Environment' . "\n\n";

	// Can we determine the site's host?

	if ($host) {

		$return .= 'Hosting Provider:         ' . $host . "\n";

		$return = apply_filters('wpematico_sysinfo_after_host_info', $return);

	}



	// Server configuration (really just versioning)

	$return .= 'Environment Type:         ' . $environment_type . "\n";
	$return .= 'WebServer Info:           ' . $_SERVER['SERVER_SOFTWARE'] . "\n";

	$return .= 'MySQL Version:            ' . $db_version . "\n";

	$return .= 'PHP Version:              ' . esc_html(phpversion()) . "\n";

	$return .= 'Disk Total Space:         ' . $disk_total_space . "\n";

	$return .= 'Disk Free Space:          ' . $disk_free_space . "\n";

	$return = apply_filters('wpematico_sysinfo_after_webserver_config', $return);



	$return .= "\n" . '-- Required Apache Mods' . "\n";

	if (stripos(isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '', 'apache') === false) {

		$return .= "\n" . '-- NO SERVER Apache Mods' . "\n";

	} else {

		if ($apache_get_modules) {

			$return .= 'Mod Rewrite:             ' . (($m_rewrite_ok) ? 'Enabled' : 'Disabled') . "\n";

			$return .= 'Mod Mime:                ' . (($m_mime_ok) ? 'Enabled' : 'Disabled') . "\n";

			$return .= 'Mod Deflate:             ' . (($m_deflate_ok) ? 'Enabled' : 'Disabled') . "\n";

		}

	}



	$return = apply_filters('wpematico_sysinfo_after_apache_mods', $return);



	$return .= "\n" . '-- PHP Environment' . "\n\n";



	$return .= 'Post Max Size:           ' . $post_max_size . "\n";

	$return .= 'Max Input Vars:          ' . $max_input_vars . "\n";

	$return .= 'PHP Time Limit:          ' . $time_limit . "\n";

	$return .= 'PHP Memory Limit:        ' . size_format($memory) . "\n";

//	$return .= 'Upload Max Filesize:     ' . $upload_max_filesize . "\n";

	$return .= 'Allow URL fopen:         ' . ($allow_url_fopen ? 'On' : 'Off') . "\n";

	$return .= 'ini_set:         ' . ($ini_set ? 'On' : 'Off') . "\n";

	$return .= 'Disabled Functions:      ' . $disable_functions . "\n";

	$return .= 'Display Errors:          ' . ($display_errors ? 'On (' . $display_errors . ')' : 'N/A') . "\n";

	if ($display_errors) {

		$return .= 'error_reporting levels:  ';

		$errLvl = error_reporting();

		for ($i = 0; $i < 15; $i++) {

			$return .= wpematico_FriendlyErrorType($errLvl & pow(2, $i)) . ", ";

		}

	}



	$return = apply_filters('wpematico_sysinfo_after_php_config', $return);



	// PHP extensions and such

	$return .= "\n\n" . '-- PHP Extensions' . "\n\n";



	// SimplePie required extensions and such	

	$return .= 'cURL (php.net/curl):     ' . (($curl_ok) ? WPeMatico::get_curl_version() : 'Disabled') . "\n";

	$return .= 'ZipArchive:              ' . (($ZipArchive) ? 'Enabled' : 'Disabled') . "\n";

	$return .= 'DOMDocument:             ' . (($DOMDocument) ? 'Enabled' : 'Disabled') . "\n";
	$wpem_tidy = wpematico_tidy_wrap_status();
	$return .= 'Tidy (HTML):             ' . ('na' === $wpem_tidy ? 'Not installed' : (('ok' === $wpem_tidy ? 'OK' : 'BROKEN wrap (breaks a line after every word)') . ' - libtidy ' . tidy_get_release())) . "\n";

	$return .= 'GD Library:              ' . (($GD_ok) ? 'Enabled' : 'Disabled') . "\n";

	$return .= 'XML (php.net/xml):       ' . (($xml_ok) ? 'Enabled, and sane' : 'Disabled, or broken') . "\n";

	$return .= 'PCRE (php.net/pcre):     ' . (($pcre_ok) ? 'Enabled' : 'Disabled') . "\n";

	$return .= 'Zlib (php.net/zlib):     ' . (($zlib_ok) ? 'Enabled' : 'Disabled') . "\n";

	$return .= 'php.net/mbstring:        ' . (($mbstring_ok) ? 'Enabled' : 'Disabled') . "\n";

	$return .= 'iconv (php.net/iconv):   ' . (($iconv_ok) ? 'Enabled' : 'Disabled') . "\n";

	$return .= 'OpenSSL(php.net/openssl):' . (($ssl_ok) ? 'Enabled' : 'Disabled') . "\n";


//	$return .= 'fsockopen:               ' . ( function_exists( 'fsockopen' ) ? 'Supported' : 'Not Supported' ) . "\n";

//	$return .= 'SOAP Client:             ' . ( class_exists( 'SoapClient' ) ? 'Installed' : 'Not Installed' ) . "\n";



	$return = apply_filters('wpematico_sysinfo_after_simplepie_ext', $return);

	$return = apply_filters('wpematico_sysinfo_after_php_ext', $return);



	// Session stuff

	$return .= "\n" . '-- Session Configuration' . "\n";

	$return .= 'Session:                  ' . (isset($_SESSION) ? 'Enabled' : 'Disabled') . "\n";



	// The rest of this is only relevant is session is enabled

	if (isset($_SESSION)) {

		$return .= 'Session Name:             ' . esc_html($session_name) . "\n";

		$return .= 'Cookie Path:              ' . esc_html($session_cookie_path) . "\n";

		$return .= 'Save Path:                ' . esc_html($session_save_path) . "\n";

		$return .= 'Use Cookies:              ' . ($session_use_cookies ? 'On' : 'Off') . "\n";

		$return .= 'Use Only Cookies:         ' . ($session_use_only_cookies ? 'On' : 'Off') . "\n";

	}



	$return = apply_filters('wpematico_sysinfo_after_session_config', $return);



	// Start with the basics...

	$return .= "\n" . '-- WordPress Environment' . "\n\n";

	// The local users' browser information, handled by the Browser class

	$return .= "" . '-- User Browser' . "\n";

	$return .= $browser . "\n";

	$return = apply_filters('wpematico_sysinfo_after_user_browser', $return);



	$return .= 'Home URL:                 ' . $home_url . "\n";

	$return .= 'Site URL:                 ' . $site_url . "\n";



	$return .= 'Version:                  ' . get_bloginfo('version') . "\n";

	$return .= 'Multisite:                ' . ($is_multisite ? 'Yes' : 'No') . "\n";

	$return .= 'Admin Email:              ' . get_option('admin_email') . "\n";

	$return .= 'Current User Email:       ' . $current_user->user_email . "\n";



	$return = apply_filters('wpematico_sysinfo_after_site_info', $return);



	// WordPress configuration

	$return .= "\n" . '-- WordPress Configuration' . "\n";

	$return .= 'Language WPLANG:          ' . get_locale() . "\n";

	$return .= 'Language Setting:         ' . (get_option('WPLANG') ? get_option('WPLANG') : 'Default') . "\n";

	$return .= 'Permalink Structure:      ' . $permalink_structure . "\n";

	$return .= 'Active Theme:             ' . $theme . "\n";

	$return .= 'Show On Front:            ' . $show_on_front . "\n";

	// Only show page specs if frontpage is set to 'page'

	if (get_option('show_on_front') == 'page') {

		$return .= 'Page On Front:            ' . $wp_front_page_id . "\n";

		$return .= 'Page For Posts:           ' . $wp_blog_page_id . "\n";

	}

	$return .= 'Remote Get:               ' . (null === $remote_get_work ? 'not checked yet' : ($remote_get_work ? 'wp_remote_get() works' : 'wp_remote_get() does not work')) . "\n";

	$return .= 'Remote Post:              ' . (null === $remote_post_work ? 'not checked yet' : ($remote_post_work ? 'wp_remote_post() works' : 'wp_remote_post() does not work')) . "\n";

	$return .= 'Table Prefix:             ' . 'Length: ' . $db_prefix . '   Status: ' . ($db_prefix > 16 ? 'ERROR: Too long' : 'Acceptable') . "\n";



	$return .= 'Memory Limit:             ' . size_format($wp_memory) . "\n";

	$return .= 'WP Max Upload Size:       ' . size_format($wp_max_upload_size) . "\n";

	$return .= 'Registered post statuses: ' . $post_stati . "\n";



	$return .= 'FileSystem Method:        ' . $fsmethod . "\n";

	$return .= 'WP_DEBUG:                 ' . (defined('WP_DEBUG') ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set') . "\n";

	$return .= 'WP_DEBUG_LOG:             ' . (defined('WP_DEBUG_LOG') ? WP_DEBUG_LOG ? 'Enabled' : 'Disabled' : 'Not set') . "\n";

	$return .= 'WP_DEBUG_DISPLAY:         ' . (defined('WP_DEBUG_DISPLAY') ? WP_DEBUG_DISPLAY ? 'Enabled' : 'Disabled' : 'Not set') . "\n";



	$return .= 'DISABLE_WP_CRON:          ' . (defined('DISABLE_WP_CRON') ? DISABLE_WP_CRON ? 'True' : 'False' : 'Not set') . "\n";

	$return .= 'WP_CRON_LOCK_TIMEOUT:     ' . (defined('WP_CRON_LOCK_TIMEOUT') ? WP_CRON_LOCK_TIMEOUT : 'Not set') . "\n";

	$return .= 'ALTERNATE_WP_CRON:        ' . (defined('ALTERNATE_WP_CRON') ? ALTERNATE_WP_CRON ? 'Enabled' : 'Disabled' : 'Not set') . "\n";



	$return = apply_filters('wpematico_sysinfo_after_wordpress_config', $return);



	$return .= "\n" . '-- Campaigns' . "\n\n";

	$tcpg	= isset($debug_data['campaigns_info'][0]['published_campaigns']) ? $debug_data['campaigns_info'][0]['published_campaigns'] : 0;

	$return .= "Total campaigns:    " . $tcpg . "\n\n";

	foreach (array_slice($debug_data['campaigns_info'], 1) as $campaign) {



		$return .= 'Campaign ID:        ' . $campaign[0]['ID'] . "\n";

		$return .= 'Campaign type:      ' . $campaign[0]['campaign_type'] . "\n";

		$return .= 'Publish as:         ' . $campaign[0]['campaign_customposttype'] . "\n";

		$return .= 'Campaign Status:    ' . $campaign[0]['campaign_posttype'] . "\n";

		$return .= 'Number of feeds:    ' . count($campaign[0]['campaign_feeds']) . "\n";

		$return .= 'Max items:          ' . $campaign[0]['campaign_max'] . "\n";

		$return .= 'Last Run:           ' . wp_date(get_option('date_format') . ' ' . get_option('time_format'), $campaign[0]['lastrun']) . "\n";

		$return .= 'Next Run:           ' . wp_date(get_option('date_format') . ' ' . get_option('time_format'), $campaign[0]['cronnextrun']) . "\n\n";

	}



	$return = apply_filters('wpematico_sysinfo_after_campaigns_infos', $return);



	// WPeMatico configuration

	$return .= "\n" . '-- WPeMatico Configuration' . "\n\n";

	$return .= 'Version:                  ' . WPeMatico::$version . "\n";



	foreach ($cfg as $name => $value):

		if (wpematico_option_blacklisted($name))

			continue;

		$value	= sanitize_option($name, $value);

		$return .= $name . ":\t\t" . ((is_array($value)) ? print_r($value, 1) : esc_html($value)) . "\n";

	endforeach;



	$return = apply_filters('wpematico_sysinfo_after_wpematico_config', $return);



	// Must-use plugins

	if (!empty($muplugins)) {

		$return .= "\n" . '-- Must-Use Plugins (' . count((array) $muplugins) . ')' . "\n\n";



		foreach ($muplugins as $plugin => $plugin_data) {

			$return .= $plugin_data['Name'] . ': ' . $plugin_data['Version'] . "\n";

		}



		$return = apply_filters('wpematico_sysinfo_after_wordpress_mu_plugins', $return);

	}



	// WordPress active plugins

	$return .= "\n" . '-- WordPress Active Plugins (' . count((array) $active_plugins) . ')' . "\n\n";

	foreach ($active_plugins as $key => $plugin) {

		$new_version = $plugin['new_version'];

		$plugin_data = $plugin['plugin_data'];



		if (!empty($plugin_data['Name'])) {

			$plugin_name = esc_html($plugin_data['Name']);

			$return		 .= $plugin_name . ': ' . $plugin_data['Version'] . (!empty($new_version) ? ' (needs update - ' . $new_version . ')' : '') . "\n";

		}

	}

	$return = apply_filters('wpematico_sysinfo_after_wordpress_plugins', $return);



	// WordPress inactive plugins

	$plugins = get_plugins();

	$return	 .= "\n" . '-- WordPress Inactive Plugins' . "\n\n";

	foreach ($plugins as $plugin_path => $plugin) {

		if (in_array($plugin_path, wpematico_get_option_active_plugins()))

			continue;

		$new_version = wpematico_get_plugin_new_version($plugin_path);

		$return		 .= $plugin['Name'] . ': ' . $plugin['Version'] . (!empty($new_version) ? ' (needs update - ' . $new_version . ')' : '') . "\n";

	}



	$return = apply_filters('wpematico_sysinfo_after_wordpress_plugins_inactive', $return);



	// WordPress scheduled crons

	$return .= "\n" . '-- WordPress Cron Schedules' . "\n\n";

	$return .= __('Next due', 'wpematico');

	$return .= ': ';

	$return .= __('Schedule', 'wpematico');

	$return .= ': ';

	$return .= __('Hook', 'wpematico');

	$return .= "\n";

	if (isset($cron_array)) {

		foreach ($cron_array as $time => $cron) {

			foreach ($cron as $hook => $cron_info) {

				foreach ($cron_info as $key => $schedule) {

					$return .= esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), $time));

					$return .= ': ';

					$return .= esc_html((isset($schedule['schedule']) && isset($schedules[$schedule['schedule']]) && isset($schedules[$schedule['schedule']]['display'])) ? $schedules[$schedule['schedule']]['display'] : '');

					$return .= ': ';

					$return .= esc_html($hook) . "\n";

				}

			}

		}

	}

	$return = apply_filters('wpematico_sysinfo_after_wordpress_scheduled_crons', $return);



	// WordPress CONSTANTS filtering users & passwords

	$return .= "\n" . '-- WordPress user Defined Constants' . "\n\n";



	$debug_constants = array();



	$debug_constants['KB_IN_BYTES']			= (defined('KB_IN_BYTES') ? KB_IN_BYTES : 'undefined');

	$debug_constants['MB_IN_BYTES']			= (defined('MB_IN_BYTES') ? MB_IN_BYTES : 'undefined');

	$debug_constants['GB_IN_BYTES']			= (defined('GB_IN_BYTES') ? GB_IN_BYTES : 'undefined');

	$debug_constants['TB_IN_BYTES']			= (defined('TB_IN_BYTES') ? TB_IN_BYTES : 'undefined');

	$debug_constants['WP_MEMORY_LIMIT']		= (defined('WP_MEMORY_LIMIT') ? WP_MEMORY_LIMIT : 'undefined');

	$debug_constants['WP_MAX_MEMORY_LIMIT'] = (defined('WP_MAX_MEMORY_LIMIT') ? WP_MAX_MEMORY_LIMIT : 'undefined');

	$debug_constants['WP_CONTENT_DIR']		= (defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : 'undefined');

	$debug_constants['WP_DEBUG']			= (defined('WP_DEBUG') ? WP_DEBUG : 'undefined');

	$debug_constants['WP_DEBUG_DISPLAY']	= (defined('WP_DEBUG_DISPLAY') ? WP_DEBUG_DISPLAY : 'undefined');

	$debug_constants['WP_DEBUG_LOG']		= (defined('WP_DEBUG_LOG') ? WP_DEBUG_LOG : 'undefined');

	$debug_constants['WP_CACHE']			= (defined('WP_CACHE') ? WP_CACHE : 'undefined');

	$debug_constants['SCRIPT_DEBUG']		= (defined('SCRIPT_DEBUG') ? SCRIPT_DEBUG : 'undefined');

	$debug_constants['MEDIA_TRASH']			= (defined('MEDIA_TRASH') ? MEDIA_TRASH : 'undefined');

	$debug_constants['SHORTINIT']			= (defined('SHORTINIT') ? SHORTINIT : 'undefined');



	$debug_constants['MINUTE_IN_SECONDS'] = (defined('MINUTE_IN_SECONDS') ? MINUTE_IN_SECONDS : 'undefined');

	$debug_constants['HOUR_IN_SECONDS']	  = (defined('HOUR_IN_SECONDS') ? HOUR_IN_SECONDS : 'undefined');

	$debug_constants['DAY_IN_SECONDS']	  = (defined('DAY_IN_SECONDS') ? DAY_IN_SECONDS : 'undefined');

	$debug_constants['WEEK_IN_SECONDS']	  = (defined('WEEK_IN_SECONDS') ? WEEK_IN_SECONDS : 'undefined');

	$debug_constants['MONTH_IN_SECONDS']  = (defined('MONTH_IN_SECONDS') ? MONTH_IN_SECONDS : 'undefined');

	$debug_constants['YEAR_IN_SECONDS']	  = (defined('YEAR_IN_SECONDS') ? YEAR_IN_SECONDS : 'undefined');

	$debug_constants['WP_CONTENT_URL']	  = (defined('WP_CONTENT_URL') ? WP_CONTENT_URL : 'undefined');

	$debug_constants['WP_PLUGIN_DIR']	  = (defined('WP_PLUGIN_DIR') ? WP_PLUGIN_DIR : 'undefined');

	$debug_constants['WP_PLUGIN_URL']	  = (defined('WP_PLUGIN_URL') ? WP_PLUGIN_URL : 'undefined');

	$debug_constants['PLUGINDIR']		  = (defined('PLUGINDIR') ? PLUGINDIR : 'undefined');

	$debug_constants['WPMU_PLUGIN_DIR']	  = (defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : 'undefined');

	$debug_constants['WPMU_PLUGIN_URL']	  = (defined('WPMU_PLUGIN_URL') ? WPMU_PLUGIN_URL : 'undefined');

	$debug_constants['MUPLUGINDIR']		  = (defined('MUPLUGINDIR') ? MUPLUGINDIR : 'undefined');



	$debug_constants['FORCE_SSL_ADMIN']		 = (defined('FORCE_SSL_ADMIN') ? FORCE_SSL_ADMIN : 'undefined');

	$debug_constants['FORCE_SSL_LOGIN']		 = (defined('FORCE_SSL_LOGIN') ? FORCE_SSL_LOGIN : 'undefined');

	$debug_constants['AUTOSAVE_INTERVAL']	 = (defined('AUTOSAVE_INTERVAL') ? AUTOSAVE_INTERVAL : 'undefined');

	$debug_constants['EMPTY_TRASH_DAYS']	 = (defined('EMPTY_TRASH_DAYS') ? EMPTY_TRASH_DAYS : 'undefined');

	$debug_constants['WP_POST_REVISIONS']	 = (defined('WP_POST_REVISIONS') ? WP_POST_REVISIONS : 'undefined');

	$debug_constants['WP_CRON_LOCK_TIMEOUT'] = (defined('WP_CRON_LOCK_TIMEOUT') ? WP_CRON_LOCK_TIMEOUT : 'undefined');

	$debug_constants['TEMPLATEPATH']		 = (defined('TEMPLATEPATH') ? TEMPLATEPATH : 'undefined');

	$debug_constants['STYLESHEETPATH']		 = (defined('STYLESHEETPATH') ? STYLESHEETPATH : 'undefined');

	$debug_constants['WP_DEFAULT_THEME']	 = (defined('WP_DEFAULT_THEME') ? WP_DEFAULT_THEME : 'undefined');

	$debug_constants['DISABLE_WP_CRON']		 = (defined('DISABLE_WP_CRON') ? DISABLE_WP_CRON : 'undefined');

	$debug_constants['ALTERNATE_WP_CRON']	 = (defined('ALTERNATE_WP_CRON') ? ALTERNATE_WP_CRON : 'undefined');



	$wp_constants = get_defined_constants(1);

	if (!empty($wp_constants['user'])) {

		foreach ($wp_constants['user'] as $key => $value) {



			if (stripos($key, 'WPEM_') !== false || stripos($key, 'WPEMATICO') !== false) {

				$debug_constants[$key] = $value;

			}

		}

	}





	$debug_constants = apply_filters('wpematico_debug_constants', $debug_constants);



	$return .= print_r($debug_constants, 1);



	$return = apply_filters('wpematico_sysinfo_after_get_defined_constants', $return);



	$return .= "\n\n" . '### End Debug Info ###';



	return $return;

}



/**

 * Generates a System Info download file

 *

 * @since       2.0

 * @return      void

 */

function wpematico_debug_info_download() {

	// System information is administrator territory.
	if (!current_user_can('manage_options')) {

		wp_die(esc_html__('You are not allowed to do this.', 'wpematico'), esc_html__('Permission denied', 'wpematico'), array('response' => 403));

	}

	check_admin_referer('wpematico-tools');

	nocache_headers();



	header('Content-Type: text/plain');

	header('Content-Disposition: attachment; filename="wpematico-debug-info.txt"');



	//echo sanitize_textarea_field($_POST['wpematico-sysinfo']); 

	echo wp_strip_all_tags($_POST['wpematico-sysinfo']);



	if (!empty($_POST['alsophpinfo'])) {

		echo "\n\n" . '-- PHPInfo --' . "\n\n";

		echo 'PHPInfo:                  ' . ((!strpos(ini_get('disable_functions'), 'phpinfo')) ? 'Enabled' : 'Disabled') . "\n\n";

		if (!strpos(ini_get('disable_functions'), 'phpinfo')) :

			unset($_REQUEST["wpematico-sysinfo"]);

			unset($_POST["wpematico-sysinfo"]);

			phpinfo();

		endif;

	}



	do_action('wpematico_download_debug_file_extra_data');



	if (!empty($_POST['alsocampaignslogs'])) {

		echo "\n\n" . '-- LAST CAMPAIGNS LOG --' . "<br />\n\n";

		$args	   = array(

			'orderby'	  => 'ID',

			'order'		  => 'ASC',

			'post_type'	  => 'wpematico',

			'numberposts' => -1

		);

		$campaigns = get_posts($args);

		foreach ($campaigns as $post):

			echo "<br />\n\n" . '### CAMPAIGN ID Name:     ' . $post->ID . ' ' . get_the_title($post->ID) . "<br />\n\n";

			echo get_post_meta($post->ID, 'last_campaign_log', true);

		endforeach;

	}

	echo "\n\n" . '-- ENDFILE --' . "\n";

	die();



// +++ COMENTADO if I want it parsed without html

//	$return = wp_strip_all_tags( $_POST['wpematico-sysinfo'] );

//	if( $_POST['alsophpinfo']==1 ) {

//		$return .= "\n\n" . '-- PHPInfo --' . "\n\n";  

//		$return .= 'PHPInfo:                  ' . ( (!strpos(ini_get( 'disable_functions' ),'phpinfo')) ? 'Enabled' : 'Disabled' ) . "\n\n";

//		if (!strpos(ini_get( 'disable_functions' ),'phpinfo')) :

//			ob_start();

//			phpinfo();

//			$phpinfo = ob_get_contents();

//			ob_end_clean();

//			$phpinfo = str_replace("</td","  </td",$phpinfo);

//			$return .= wp_strip_all_tags($phpinfo);

//			$return .= $phpinfo;

//		endif;

//	}

//	echo $return;

//	die();

}



add_action('wpematico_download_debug_info', 'wpematico_debug_info_download');



function wpematico_option_blacklisted($setting) {

	// TODO: add other tools from premium modules

	$blacklisted = array(

		'mailsendmail',

		'mailsecure',

		'mailhost',

		'mailport',

		'mailuser',

		'mailpass',

	);

	return in_array($setting, $blacklisted);

}



/**

 * wpematico_let_to_num function.

 *

 * This function transforms the php.ini notation for numbers (like '2M') to an integer.

 *

 * @since 1.6.3

 *

 * @param $size

 * @return int

 */

function wpematico_let_to_num($size) {

	$l	 = substr($size, -1);

	$ret = substr($size, 0, -1);

	switch (strtoupper($l)) {

		case 'P':

			$ret *= 1024;

		case 'T':

			$ret *= 1024;

		case 'G':

			$ret *= 1024;

		case 'M':

			$ret *= 1024;

		case 'K':

			$ret *= 1024;

	}

	return $ret;

}

