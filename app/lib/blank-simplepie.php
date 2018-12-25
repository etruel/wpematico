<?php
/**
* It will be used to create SimplePie objects from any data.
* @package     Blank SimplePie
* @since       1.0.0
*/
if (!class_exists('Blank_SimplePie')) :
class Blank_SimplePie {
	public $items = array();
	public $feed_url = '';
	public $title = '';
	public $description = '';
	public $image = null;
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
}
endif;
if (!class_exists('Blank_SimplePie_Item')) :
class Blank_SimplePie_Item {
	
	public $title = '';
	public $content = '';
	public $link = '';
	public $permalink = '';
	public $date = '';
	public $author = false;
	public $post_meta = array();
	function __construct($title = '', $content = '', $link = '', $date = '', $author = false, $post_meta = array()) {
		
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
}
endif;
if (!class_exists('Blank_SimplePie_Item_Author')) :
class Blank_SimplePie_Item_Author {
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

?>