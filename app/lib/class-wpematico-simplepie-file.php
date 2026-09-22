<?php

/**
 * The HTTP layer SimplePie uses to fetch a feed.
 *
 * SimplePie asks for one address at a time: when a feed answers with a redirect it builds a
 * new request for the address it was pointed to, and it does the same while looking for the
 * feed of a web page. So the address a fetch finally connects to is not always the one the
 * campaign stores.
 *
 * Every one of those addresses is resolved here through WPeMatico::validate_feed_url(), the
 * same rule the stored address goes through, so a feed reads what its URL says it reads. The
 * verdict is cached per address for the request, so a feed that does not redirect costs
 * exactly what it used to.
 *
 * Registered on the SimplePie instance by WPeMatico::fetchFeed().
 *
 * @package     WPeMatico
 * @since       2.8.27
 */
if (!defined('ABSPATH'))
	exit;

/**
 * SimplePie 1.8 moved its classes into a namespace. Both names are supported, so the same
 * class works on every WordPress this plugin runs on.
 */
if (!class_exists('WPeMatico_SimplePie_File_Base', false)) {
	if (class_exists('SimplePie\File')) {
		class_alias('SimplePie\File', 'WPeMatico_SimplePie_File_Base');
	} elseif (class_exists('SimplePie_File')) {
		class_alias('SimplePie_File', 'WPeMatico_SimplePie_File_Base');
	}
}

if (class_exists('WPeMatico_SimplePie_File_Base', false) && !class_exists('WPeMatico_SimplePie_File', false)) :

	class WPeMatico_SimplePie_File extends WPeMatico_SimplePie_File_Base {

		/**
		 * Requests one address, after resolving where it points to.
		 *
		 * The signature carries no type declarations on purpose: SimplePie 1.9 types its own
		 * and older releases do not, and a subclass may always widen them.
		 *
		 * @param   string   $url
		 * @param   integer  $timeout
		 * @param   integer  $redirects
		 * @param   array    $headers
		 * @param   string   $useragent
		 * @param   boolean  $force_fsockopen
		 * @param   array    $curl_options
		 */
		public function __construct($url, $timeout = 10, $redirects = 5, $headers = null, $useragent = null, $force_fsockopen = false, $curl_options = array()) {
			$destination = WPeMatico::validate_feed_url($url);

			if (is_wp_error($destination)) {
				$this->url			 = $url;
				$this->permanent_url = $url;
				$this->useragent	 = $useragent;
				$this->success		 = false;
				$this->error		 = $destination->get_error_message();
				// A request that was not made has no response of its own, and leaving the one
				// that pointed here in place would be read as the feed. SimplePie reports the
				// reason above instead.
				$this->status_code	 = 0;
				$this->headers		 = array();
				$this->body			 = '';

				/* translators: %s Reason the feed was not fetched. */
				trigger_error(sprintf(__('Feed not fetched: %s', 'wpematico'), esc_html($this->error)), E_USER_WARNING);  // Log

				return;
			}

			parent :: __construct($destination, $timeout, $redirects, $headers, $useragent, $force_fsockopen, $curl_options);
		}

	}

endif;
