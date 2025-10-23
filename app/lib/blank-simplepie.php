<?php
/**
* It will be used to create SimplePie objects from any data.
* @package     Blank SimplePie
* @since       1.0.0
*/
if (!class_exists('WPeMatico_SimplePie')) :
class WPeMatico_SimplePie {
	public $items = array();
	public $feed_url = '';
	public $title = '';
	public $description = '';
	public $image = null;
	public $raw_data = '';

	function __construct($feed_url = '', $title = '', $description = '') {
		$this->feed_url = $feed_url;
		$this->title = $title;
		$this->description = $description;
	}
	public function get_items() {
		return $this->items;
	}
	public function addItem($item) {
		$this->items[] = $item;
	}
	public function get_title() {
		return $this->title;
	}
	public function get_description() {
		return $this->description;
	}
	public function get_image_url() {
		return $this->image;
	}
	public function error() {
		return false;
	}
}
endif;
if (!class_exists('WPeMatico_SimplePie_Item')) :
class WPeMatico_SimplePie_Item {
	
	public $title = '';
	public $content = '';
	public $link = '';
	public $permalink = '';
	public $date = '';
	public $author = false;
	public $post_meta = array();
	public $categories = array();
	public $data = array();
	public $enclosures = array();
	function __construct($title = '', $content = '', $link = '', $date = '', $author = '', $post_meta = array()) {
		
		$this->title = $title;
		$this->content = $content;
		$this->link = $link;
		$this->permalink = $link;
		$this->date = $date;
		$this->author = $author;
		$this->post_meta = $post_meta;
	}
	public function get_title() {
		return $this->title;
	}
	public function get_content() {
		return $this->content;
	}
	public function get_permalink() {
		return $this->permalink;
	}
	public function get_link() {
		return $this->link;
	}
	public function get_date($date_format = 'j F Y, g:i a') {
		return date($date_format, strtotime($this->date));
	}
	public function set_author($author = '') {
		$this->author = $author;
	}
	public function get_author() {
		return $this->author;
	}
	public function get_description() {
		return $this->content;
	}
	public function get_post_meta($key) {
		$ret = '';
		if (isset($this->post_meta[$key])) {
			$ret = $this->post_meta[$key];
		}
		return $ret;
	}
	public function set_post_meta($key, $value) {
		$this->post_meta[$key] = $value;
	}
	public function set_feed($feed) {
		return $this->feed = $feed;
	}
	public function get_feed() {
		return $this->feed;
	}
	public function get_categories() {
		return $this->categories;
	}
	public function add_category($category) {
		$this->categories[] = $category;
	}

	public function get_item_tags($namespace, $tag) {
		if (isset($this->data['child'][$namespace][$tag]))
		{
			return $this->data['child'][$namespace][$tag];
		}
		else
		{
			return null;
		}
	}
	public function get_enclosures() {
		return $this->enclosures;
	}
	public function add_enclosures($enclosure) {
		return $this->enclosures[] = $enclosure;
	}
	

}
endif;
if (!class_exists('WPeMatico_SimplePie_Item_Author')) :
class WPeMatico_SimplePie_Item_Author {
	public $name = '';
	public $link = '';
	function __construct($name = '', $link = '') {
		$this->name = $name;
		$this->link = $link;
	}
	function get_name() {
		return $this->name;
	}
	function get_link() {
		return $this->link;
	}
}
endif;

if (!class_exists('WPeMatico_SimplePie_Category')) :
class WPeMatico_SimplePie_Category {
	
	var $term;

	var $scheme;

	var $label;

	var $type;

	public function __construct($term = null, $scheme = null, $label = null, $type = null)
	{
		$this->term = $term;
		$this->scheme = $scheme;
		$this->label = $label;
		$this->type = $type;
	}

	public function get_term()
	{
		return $this->term;
	}

	public function get_scheme()
	{
		return $this->scheme;
	}

	public function get_label($strict = false)
	{
		if ($this->label === null && $strict !== true)
		{
			return $this->get_term();
		}
		return $this->label;
	}
	
	public function get_type()
	{
		return $this->type;
	}
}
endif;
if (!class_exists('WPeMatico_SimplePie_Enclosure')) :
class WPeMatico_SimplePie_Enclosure {

	var $bitrate;
	var $captions;
	var $categories;
	var $channels;
	var $copyright;
	var $credits;
	var $description;
	var $duration;
	var $expression;
	var $framerate;
	var $handler;
	var $hashes;
	var $height;
	var $javascript;
	var $keywords;
	var $lang;
	var $length;
	var $link;
	var $medium;
	var $player;
	var $ratings;
	var $restrictions;
	var $samplingrate;
	var $thumbnails;
	var $title;
	var $type;
	var $width;

	public function __construct($link = null, $type = null, $length = null, $javascript = null, $bitrate = null, $captions = null, $categories = null, $channels = null, $copyright = null, $credits = null, $description = null, $duration = null, $expression = null, $framerate = null, $hashes = null, $height = null, $keywords = null, $lang = null, $medium = null, $player = null, $ratings = null, $restrictions = null, $samplingrate = null, $thumbnails = null, $title = null, $width = null)
	{
		$this->bitrate = $bitrate;
		$this->captions = $captions;
		$this->categories = $categories;
		$this->channels = $channels;
		$this->copyright = $copyright;
		$this->credits = $credits;
		$this->description = $description;
		$this->duration = $duration;
		$this->expression = $expression;
		$this->framerate = $framerate;
		$this->hashes = $hashes;
		$this->height = $height;
		$this->keywords = $keywords;
		$this->lang = $lang;
		$this->length = $length;
		$this->link = $link;
		$this->medium = $medium;
		$this->player = $player;
		$this->ratings = $ratings;
		$this->restrictions = $restrictions;
		$this->samplingrate = $samplingrate;
		$this->thumbnails = $thumbnails;
		$this->title = $title;
		$this->type = $type;
		$this->width = $width;

	}

	/**
	 * String-ified version
	 *
	 * @return string
	 */
	public function __toString()
	{
		// There is no $this->data here
		return md5(serialize($this));
	}


	public function get_bitrate()
	{
		if ($this->bitrate !== null)
		{
			return $this->bitrate;
		}
		else
		{
			return null;
		}
	}

	public function get_caption($key = 0)
	{
		$captions = $this->get_captions();
		if (isset($captions[$key]))
		{
			return $captions[$key];
		}
		else
		{
			return null;
		}
	}

	public function get_captions()
	{
		if ($this->captions !== null)
		{
			return $this->captions;
		}
		else
		{
			return null;
		}
	}

	public function get_category($key = 0)
	{
		$categories = $this->get_categories();
		if (isset($categories[$key]))
		{
			return $categories[$key];
		}
		else
		{
			return null;
		}
	}

	public function get_categories()
	{
		if ($this->categories !== null)
		{
			return $this->categories;
		}
		else
		{
			return null;
		}
	}
	public function get_channels()
	{
		if ($this->channels !== null)
		{
			return $this->channels;
		}
		else
		{
			return null;
		}
	}

	public function get_copyright()
	{
		if ($this->copyright !== null)
		{
			return $this->copyright;
		}
		else
		{
			return null;
		}
	}

	public function get_credit($key = 0)
	{
		$credits = $this->get_credits();
		if (isset($credits[$key]))
		{
			return $credits[$key];
		}
		else
		{
			return null;
		}
	}
	public function get_credits()
	{
		if ($this->credits !== null)
		{
			return $this->credits;
		}
		else
		{
			return null;
		}
	}
	public function get_description()
	{
		if ($this->description !== null)
		{
			return $this->description;
		}
		else
		{
			return null;
		}
	}

	public function get_expression()
	{
		if ($this->expression !== null)
		{
			return $this->expression;
		}
		else
		{
			return 'full';
		}
	}

	public function get_framerate()
	{
		if ($this->framerate !== null)
		{
			return $this->framerate;
		}
		else
		{
			return null;
		}
	}

	public function get_hash($key = 0)
	{
		$hashes = $this->get_hashes();
		if (isset($hashes[$key]))
		{
			return $hashes[$key];
		}
		else
		{
			return null;
		}
	}
	public function get_hashes()
	{
		if ($this->hashes !== null)
		{
			return $this->hashes;
		}
		else
		{
			return null;
		}
	}
	public function get_height()
	{
		if ($this->height !== null)
		{
			return $this->height;
		}
		else
		{
			return null;
		}
	}
	public function get_language()
	{
		if ($this->lang !== null)
		{
			return $this->lang;
		}
		else
		{
			return null;
		}
	}
	public function get_keyword($key = 0)
	{
		$keywords = $this->get_keywords();
		if (isset($keywords[$key]))
		{
			return $keywords[$key];
		}
		else
		{
			return null;
		}
	}
	public function get_keywords()
	{
		if ($this->keywords !== null)
		{
			return $this->keywords;
		}
		else
		{
			return null;
		}
	}
	public function get_length()
	{
		if ($this->length !== null)
		{
			return $this->length;
		}
		else
		{
			return null;
		}
	}

	public function get_link()
	{
		if ($this->link !== null)
		{
			return urldecode($this->link);
		}
		else
		{
			return null;
		}
	}
	public function get_medium()
	{
		if ($this->medium !== null)
		{
			return $this->medium;
		}
		else
		{
			return null;
		}
	}
	public function get_player()
	{
		if ($this->player !== null)
		{
			return $this->player;
		}
		else
		{
			return null;
		}
	}

	public function get_rating($key = 0)
	{
		$ratings = $this->get_ratings();
		if (isset($ratings[$key]))
		{
			return $ratings[$key];
		}
		else
		{
			return null;
		}
	}

	public function get_ratings()
	{
		if ($this->ratings !== null)
		{
			return $this->ratings;
		}
		else
		{
			return null;
		}
	}

	public function get_restriction($key = 0)
	{
		$restrictions = $this->get_restrictions();
		if (isset($restrictions[$key]))
		{
			return $restrictions[$key];
		}
		else
		{
			return null;
		}
	}
	public function get_restrictions()
	{
		if ($this->restrictions !== null)
		{
			return $this->restrictions;
		}
		else
		{
			return null;
		}
	}

	public function get_sampling_rate()
	{
		if ($this->samplingrate !== null)
		{
			return $this->samplingrate;
		}
		else
		{
			return null;
		}
	}

	public function get_size()
	{
		$length = $this->get_length();
		if ($length !== null)
		{
			return round($length/1048576, 2);
		}
		else
		{
			return null;
		}
	}

	public function get_thumbnail($key = 0)
	{
		$thumbnails = $this->get_thumbnails();
		if (isset($thumbnails[$key]))
		{
			return $thumbnails[$key];
		}
		else
		{
			return null;
		}
	}

	public function get_thumbnails()
	{
		if ($this->thumbnails !== null)
		{
			return $this->thumbnails;
		}
		else
		{
			return null;
		}
	}

	public function get_title()
	{
		if ($this->title !== null)
		{
			return $this->title;
		}
		else
		{
			return null;
		}
	}

	public function get_type()
	{
		if ($this->type !== null)
		{
			return $this->type;
		}
		else
		{
			return null;
		}
	}


	public function get_width()
	{
		if ($this->width !== null)
		{
			return $this->width;
		}
		else
		{
			return null;
		}
	}

	
}

endif;

if(!defined('WPEMATICO_NAMESPACE_RSS_20')){
	define('WPEMATICO_NAMESPACE_RSS_20', '');
}

?>