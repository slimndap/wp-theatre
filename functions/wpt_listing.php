<?php
class WPT_Listing {
	public function __toString() {
		return $this->html();
	}
	
	public function __invoke($filters=array()) {
		return $this->get($filters);
	}

	function defaults() {
		return array();
	}

	function get($filters=array()) {
		$filters = wp_parse_args( $filters, $this->defaults() );

		$key = 'WPT_Listing_'.md5(serialize($filters));
		$listing = wp_cache_get($key,'wp_theatre');
		if ( false === $listing ) {
			$listing = $this->load($filters);
			wp_cache_set($key,$listing,'wp_theatre');
		} else {
		}
		return $listing;				
	}
	
	public function html($args=array()) {
		return '';
	}

	function load() {
		return array();
	}
	
}
?>