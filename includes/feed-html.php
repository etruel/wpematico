<?php
/**
 * The markup a feed brings into a post: what is kept, what is rebuilt and what is left out.
 *
 * @package WPeMatico
 * @since 2.9.1
 */
if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit;
}

if (!class_exists('WPeMatico_Feed_HTML')) {

	class WPeMatico_Feed_HTML {

		/**
		 * Template tags whose value is plain text from the feed, and may sit inside an attribute.
		 */
		const TEXT_TEMPLATE_TAGS = array('{title}', '{author}', '{feedtitle}', '{campaigntitle}', '{source_name}', '{item_date}', '{item_time}', '{campaignid}');

		/**
		 * Whether this campaign stores the content exactly as the feed sends it.
		 * Only the administrator's "Let campaigns skip the content filters" plus the
		 * campaign's own "Store the content unfiltered" say so.
		 *
		 * @param array $campaign Campaign data.
		 * @return boolean
		 */
		public static function keeps_raw($campaign) {
			global $cfg;
			return !empty($cfg['woutfilter']) && !empty($campaign['campaign_woutfilter']);
		}

		/**
		 * Tags and attributes kept from a feed, in wp_kses() format: what WordPress allows
		 * in a post, plus video players and the responsive images WordPress prints itself.
		 *
		 * @return array
		 */
		public static function allowed_html() {
			$allowed			= wp_kses_allowed_html('post');
			$allowed['iframe']	= array(
				'src'			 => true,
				'width'			 => true,
				'height'		 => true,
				'title'			 => true,
				'class'			 => true,
				'id'			 => true,
				'name'			 => true,
				// Video services wrap the player in a positioned box and put the geometry in a
				// style attribute; wp_kses() runs it through safecss_filter_attr().
				'style'			 => true,
				'frameborder'	 => true,
				'scrolling'		 => true,
				'marginwidth'	 => true,
				'marginheight'	 => true,
				'loading'		 => true,
				'referrerpolicy' => true,
				'sandbox'		 => true,
				'allow'			 => true,
				'allowfullscreen' => true,
			);
			$allowed['img'] = array_merge(isset($allowed['img']) ? $allowed['img'] : array(), array(
				'srcset'		 => true,
				'sizes'			 => true,
				'decoding'		 => true,
				'fetchpriority'	 => true,
				'loading'		 => true,
			));
			$allowed['picture'] = array(
				'class'	 => true,
				'id'	 => true,
			);
			$allowed['source'] = array(
				'src'	 => true,
				'srcset' => true,
				'sizes'	 => true,
				'type'	 => true,
				'media'	 => true,
				'width'	 => true,
				'height' => true,
			);

			/**
			 * Filters the tags and attributes a feed item keeps.
			 *
			 * @since 2.9.1
			 * @param array $allowed Tags and attributes, in wp_kses() format.
			 */
			return apply_filters('wpematico_feed_allowed_html', $allowed);
		}

		/**
		 * The social network scripts an item may keep, as patterns over host + path.
		 * A post from a network arrives as a blockquote plus the network's script; the
		 * blockquote is markup and survives, and the script is put back from this list.
		 *
		 * @return array Regular expressions.
		 */
		public static function embed_scripts() {
			/**
			 * Filters the embed scripts kept from a feed, as patterns over host + path.
			 *
			 * @since 2.9.1
			 * @param array $patterns Regular expressions.
			 */
			return apply_filters('wpematico_feed_embed_scripts', array(
				'#^platform\.(twitter|x)\.com/widgets\.js$#',
				'#^(www\.)?instagram\.com/embed\.js$#',
				'#^platform\.instagram\.com/[a-z]{2}_[a-z]{2}/embeds\.js$#',
				'#^www\.threads\.(net|com)/embed\.js$#',
				'#^www\.tiktok\.com/embed\.js$#',
				'#^embed\.bsky\.app/static/embed\.js$#',
				'#^embed\.reddit\.com/widgets\.js$#',
				'#^assets\.pinterest\.com/js/pinit\.js$#',
				'#^assets\.tumblr\.com/post\.js$#',
				'#^connect\.facebook\.net/[a-z]{2}_[a-z]{2}/sdk\.js$#',
			));
		}

		/**
		 * The content of an item, as it will be published.
		 *
		 * @param string $html     Item content.
		 * @param array  $campaign Campaign data.
		 * @return string
		 */
		public static function content($html, $campaign = array()) {
			$html = (string) $html;
			if ('' === trim($html) || self::keeps_raw($campaign)) {
				return $html;
			}

			$scripts = self::embed_script_urls($html);

			$html = self::images(self::strip_code($html));
			$html = wp_kses($html, self::allowed_html());
			// A frame whose address wp_kses() had to strip points nowhere: it goes whole.
			$html = preg_replace_callback('#<iframe\b[^>]*>.*?</iframe\s*>#is', function ($frame) {
				return preg_match('#^<iframe\b[^>]*\ssrc\s*=\s*["\']?(https?:)?//#i', $frame[0]) ? $frame[0] : '';
			}, $html);

			foreach ($scripts as $src) {
				$html .= "\n" . '<script async src="' . esc_url($src) . '" charset="utf-8"></script>';
			}

			return $html;
		}

		/**
		 * A title or another short text of an item: the inline markup WordPress allows there.
		 *
		 * @param string $text
		 * @return string
		 */
		public static function text($text) {
			return wp_kses(self::strip_code((string) $text), 'data');
		}

		/**
		 * The excerpt of an item: the markup WordPress allows in a post.
		 *
		 * @param string $excerpt
		 * @return string
		 */
		public static function excerpt($excerpt) {
			return wp_kses(self::strip_code((string) $excerpt), 'post');
		}

		/**
		 * The values of the post template tags. Addresses become safe addresses, text stays
		 * text inside an attribute too, and markup goes through the rules of the content.
		 * {content} is already sanitized when the template is parsed.
		 *
		 * @param array $vars     Tag => value.
		 * @param array $campaign Campaign data.
		 * @return array
		 */
		public static function template_vars($vars, $campaign = array()) {
			if (self::keeps_raw($campaign)) {
				return $vars;
			}
			foreach ($vars as $tag => $value) {
				if ('{content}' === $tag || !is_scalar($value)) {
					continue;
				}
				$value = (string) $value;
				if (preg_match('/(url|link|logo|favicon)\}$/i', $tag)) {
					$vars[$tag] = esc_url($value);
				} elseif (in_array($tag, self::TEXT_TEMPLATE_TAGS, true)) {
					$vars[$tag] = str_replace(array('"', "'"), array('&quot;', '&#039;'), self::text($value));
				} else {
					$vars[$tag] = wp_kses(self::strip_code($value), self::allowed_html());
				}
			}
			return $vars;
		}

		/**
		 * Scripts, styles and noscript fallbacks go with what they hold: wp_kses() keeps the
		 * text of a tag it removes, and code would show as words in the post.
		 *
		 * @param string $html
		 * @return string
		 */
		protected static function strip_code($html) {
			return preg_replace('#<(script|style|noscript)\b[^>]*>.*?</\1\s*>#is', '', $html);
		}

		/**
		 * The embed scripts of the content that are on the list, as https addresses.
		 *
		 * @param string $html
		 * @return array
		 */
		protected static function embed_script_urls($html) {
			$found = array();
			if (!preg_match_all('#<script\b[^>]*\bsrc\s*=\s*["\']?([^"\'\s>]+)#i', $html, $matches)) {
				return $found;
			}
			$patterns = self::embed_scripts();
			foreach ($matches[1] as $src) {
				$src	 = html_entity_decode($src, ENT_QUOTES, 'UTF-8');
				$parts	 = wp_parse_url((0 === strpos($src, '//')) ? 'https:' . $src : $src);
				if (empty($parts['host']) || empty($parts['path']) || (isset($parts['scheme']) && !in_array(strtolower($parts['scheme']), array('http', 'https'), true))) {
					continue;
				}
				$where = strtolower($parts['host'] . $parts['path']);
				foreach ($patterns as $pattern) {
					if (preg_match($pattern, $where)) {
						// The query is dropped; the fragment stays, it is how Facebook's SDK reads its options.
						$url = 'https://' . strtolower($parts['host']) . $parts['path'] . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
						$found[$url] = $url;
						break;
					}
				}
			}
			return array_values($found);
		}

		/**
		 * Responsive and lazy-loaded images: every srcset keeps only addresses a post may
		 * hold, and an image whose src is an inline placeholder gets its real address.
		 *
		 * @param string $html
		 * @return string
		 */
		protected static function images($html) {
			if (!class_exists('WP_HTML_Tag_Processor') || (false === stripos($html, '<img') && false === stripos($html, '<source'))) {
				return $html;
			}
			$tags = new WP_HTML_Tag_Processor($html);
			while ($tags->next_tag()) {
				$tag = $tags->get_tag();
				if ('IMG' !== $tag && 'SOURCE' !== $tag) {
					continue;
				}

				$srcset = $tags->get_attribute('srcset');
				if (is_string($srcset)) {
					$srcset = self::srcset($srcset);
					if ('' === $srcset) {
						$tags->remove_attribute('srcset');
					} else {
						$tags->set_attribute('srcset', $srcset);
					}
				}

				$src = $tags->get_attribute('src');
				if ('IMG' !== $tag || !is_string($src) || 0 !== stripos(ltrim($src), 'data:')) {
					continue;
				}
				$real = '';
				if (is_string($srcset) && '' !== $srcset) {
					$first	 = explode(',', $srcset);
					$real	 = (string) strtok(trim($first[0]), " \t\n\r");
				} else {
					foreach (array('data-src', 'data-lazy-src', 'data-original') as $attribute) {
						$lazy = $tags->get_attribute($attribute);
						if (is_string($lazy) && '' !== trim($lazy) && self::is_allowed_url(trim($lazy))) {
							$real = trim($lazy);
							break;
						}
					}
				}
				// Only an absolute address: the tag processor runs a src through esc_url(),
				// which reads a bare "image.jpg" as a host name.
				if (preg_match('#^(https?:)?//#i', $real)) {
					$tags->set_attribute('src', $real);
				} else {
					$tags->remove_attribute('src');
				}
			}
			return $tags->get_updated_html();
		}

		/**
		 * A srcset with only the candidates whose address a post may hold.
		 *
		 * @param string $srcset
		 * @return string
		 */
		protected static function srcset($srcset) {
			$kept = array();
			foreach (preg_split('/,\s+/', trim($srcset)) as $candidate) {
				$candidate = trim($candidate);
				$url	   = strtok($candidate, " \t\n\r");
				if (false !== $url && self::is_allowed_url($url)) {
					$kept[] = $candidate;
				}
			}
			return implode(', ', $kept);
		}

		/**
		 * Whether an address uses a protocol WordPress allows in a post. Relative ones do.
		 *
		 * @param string $url
		 * @return boolean
		 */
		protected static function is_allowed_url($url) {
			return 0 !== stripos($url, 'data:') && wp_kses_bad_protocol($url, wp_allowed_protocols()) === $url;
		}
	}
}
